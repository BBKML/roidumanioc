<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Connexion Google (OAuth2). Laisser vide pour désactiver le bouton « Continuer avec Google ».
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    // Hébergement vidéo Bunny Stream (recommandé pour les formations payantes).
    // Avec `token_key`, les URL d'intégration sont signées et expirent -> non partageables.
    'bunny' => [
        'library_id' => env('BUNNY_STREAM_LIBRARY_ID'),
        'token_key' => env('BUNNY_STREAM_TOKEN_KEY'),
        'token_ttl' => (int) env('BUNNY_STREAM_TOKEN_TTL', 4 * 3600),
    ],

];
