<?php

/*
| Modul KEU — Administrasi Keuangan Mahasiswa.
|
| `payment_channel_limits`: kuota kanal pembayaran ONLINE per paket.
| Acuan SEVIMA (Proposal §1/§3.6): Growth 3, Business 3, Professional 4,
| Premium 5, Enterprise 6. Paket CivitasOne saat ini trial/starter/
| professional/enterprise; pemetaannya masih keputusan terbuka (rancangan
| KEU INK-KEU-02, Q-KEU-02) — default di bawah memakai nilai acuan
| tertinggi dalam gabungan. Nilai `subscription_plans.limits.keu_payment_channels`
| (bila ada) selalu didahulukan sehingga keputusan final tidak perlu ubah kode.
*/

return [
    'payment_channel_limits' => [
        'growth' => 3,
        'business' => 3,
        'professional' => 4,
        'premium' => 5,
        'enterprise' => 6,
        'trial' => 3,
        'starter' => 3,
    ],

    // Kuota bila tenant tidak memiliki langganan aktif (paket acuan terendah).
    'default_payment_channel_limit' => 3,

    // Payment gateway = Xendit, kredensial di config/services.php (XENDIT_*).
    // Tanpa kredensial, kanal online tidak dapat dipakai (hanya tunai &
    // transfer manual) — sistem tidak pernah berpura-pura terhubung.
];
