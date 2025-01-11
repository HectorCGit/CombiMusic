<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web'])->group(function () {
    Route::get('/auth/spotify', function () {
        Log::info('Redirecting to Spotify...');
        return Socialite::driver('spotify')->redirect();
    })->name('auth.spotify');

    Route::get('/auth/spotify/callback', function () {
        try {
            $user = $_GET['code'];


            $code = $_GET['code']; // Get the code from the callback URL


            $client = new Client([
                'verify' => false, // Disable SSL verification
            ]);

            $response = $client->post('https://accounts.spotify.com/api/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),
                    'client_id' => env('SPOTIFY_CLIENT_ID'),
                    'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
                ],
            ]);

            $body = json_decode($response->getBody(), true);

            $accessToken = $body['access_token'];
            $refreshToken = $body['refresh_token'];
            $expiresIn = $body['expires_in'];

            $response = $client->get('https://api.spotify.com/v1/me/playlists', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $user = json_decode($response->getBody(), true);

            // Display user info
            dd($user);

            //$user = Socialite::driver('spotify')->user();
            Log::info('User  retrieved: ', (array) $user);
            return redirect('/')->with('success', 'Successfully authenticated with Spotify!');
        } catch (\Exception $e) {
            dd($e);
            Log::error('Error during Spotify authentication: ' . $e->getMessage());
            return redirect('/')->with('error', 'Failed to authenticate with Spotify: ' . $e->getMessage());
        }
    })->name('auth.spotify.callback');
});
