<?php

/*
| Konfigurasi infrastruktur modul PMB (Admission). Pengaturan bisnis per
| tenant ada di university_settings grup `pmb` (lihat
| Modules\Admission\Support\PmbSettings).
*/

return [
    // Disk privat untuk dokumen pendaftar (FR-DOC-03). Gunakan disk S3-compatible privat di produksi.
    'storage_disk' => env('PMB_STORAGE_DISK', 'local'),

    // Masa berlaku URL unduh bertanda tangan (menit).
    'download_url_ttl_minutes' => 5,

    'captcha' => [
        // turnstile | none. `none` ditolak di production (lihat CaptchaVerifierFactory).
        'driver' => env('PMB_CAPTCHA_DRIVER', 'none'),
        'turnstile_secret' => env('PMB_TURNSTILE_SECRET'),
        'turnstile_site_key' => env('PMB_TURNSTILE_SITE_KEY'),
    ],

    'payment' => [
        // Gateway online aktif: null (hanya transfer manual & loket) | midtrans.
        'gateway' => env('PMB_PAYMENT_GATEWAY'),
        'midtrans' => [
            'server_key' => env('PMB_MIDTRANS_SERVER_KEY'),
            'is_production' => (bool) env('PMB_MIDTRANS_PRODUCTION', false),
        ],
        'gateway_timeout_seconds' => 10,
        // Toleransi timestamp webhook (detik) — 05 §F.4.
        'webhook_tolerance_seconds' => 300,
    ],

    'sms' => [
        // log | http. `log` hanya mencatat (tidak mengirim) — untuk lingkungan tanpa penyedia.
        'driver' => env('PMB_SMS_DRIVER', 'log'),
        'http_endpoint' => env('PMB_SMS_HTTP_ENDPOINT'),
        'http_token' => env('PMB_SMS_HTTP_TOKEN'),
    ],

    'cbt' => [
        // Secret HMAC untuk webhook nilai CBT eksternal (FR-EXM-09).
        'webhook_secret' => env('PMB_CBT_WEBHOOK_SECRET'),
    ],

    'integrations' => [
        // Token klien statis per tenant untuk lead CRM/Maukuliah dibuat via Sanctum (ability pmb:integration:leads).
        'lead_ability' => 'pmb:integration:leads',
    ],

    // Token pendaftar: umur sliding & maksimal token aktif (FR-ACC-03).
    'applicant_token_ttl_hours' => 12,
    'applicant_max_tokens' => 5,

    // Frontend: tautan di email (verifikasi, reset, portal).
    'frontend_url' => env('PMB_FRONTEND_URL', env('FRONTEND_URL', 'http://localhost:5173')),
];
