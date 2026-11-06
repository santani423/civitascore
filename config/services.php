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

    /*
    | Payment gateway pembayaran online PMB & KEU. Mode test/live mengikuti
    | jenis secret key (xnd_development_… / xnd_production_…). Tanpa
    | secret key, adapter tidak didaftarkan dan hanya kanal offline
    | (transfer manual & loket) yang dapat dipakai.
    */
    'xendit' => [
        'secret_key' => env('XENDIT_SECRET_KEY'),
        // Token verifikasi callback (header x-callback-token) dari Dashboard Xendit → Webhooks.
        'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),
        'base_url' => env('XENDIT_BASE_URL', 'https://api.xendit.co'),
        'timeout' => (int) env('XENDIT_TIMEOUT', 10),
    ],

    /*
    | Google Sign-In (OAuth 2.0 / OpenID Connect) untuk akun pendaftar PMB —
    | dipakai lewat Laravel Socialite. Client secret hanya dipakai backend
    | (tukar authorization code) dan tidak pernah dikirim ke frontend.
    | Tanpa client ID/secret, tombol Google disembunyikan dan endpoint-nya
    | menjawab 503. `redirect` harus sama persis dengan "Authorized redirect
    | URI" di Google Cloud Console.
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI') ?: rtrim((string) env('APP_URL', 'http://localhost'), '/').'/api/v1/pmb/applicant/auth/google/callback',
    ],

];
