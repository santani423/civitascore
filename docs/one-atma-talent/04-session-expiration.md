# One Atma Talent — Penutupan Sesi Kedaluwarsa

> **Per:** 5 November 2026. **Terkait:** [03](03-admin-management.md), [07](07-testing-and-verification.md).

## 1. Yang Dilakukan

Sesi `in_progress` yang melewati `deadline_at` ditutup secara otomatis oleh `pmb:tick`, yang sudah berjalan setiap menit untuk seluruh modul PMB. Tidak ada jadwal baru: Fase 2 menambahkan satu langkah ke penjadwal yang sudah ada.

- Penjadwal: `AdmissionServiceProvider` — `pmb:tick` setiap menit, `withoutOverlapping(10)`.
- Langkah: `PmbTickCommand`, per tenant aktif, label `expirasi asesmen [{universityId}]`.
- Logika: `AssessmentSessionService::expireDue()` → `expireIfDue()` → `close()`.

## 2. Aturan

| Kondisi sesi saat diproses | Hasil |
|---|---|
| `in_progress`, `deadline_at <= now()`, semua butir terjawab | Dinilai. Status `expired`. Skor tersimpan, dapat diterbitkan. |
| `in_progress`, `deadline_at <= now()`, ada butir kosong | Ditutup **tanpa skor**. Status `expired`, `holland_code` null. Tidak dapat diterbitkan (`NO_SCORES`). |
| `in_progress`, `deadline_at` masih di masa depan | Tidak disentuh. |
| `in_progress`, tanpa `deadline_at` (tanpa batas waktu) | Tidak disentuh. |
| `submitted`, `expired`, `published`, `invalidated` | Tidak disentuh. Status final tidak pernah berubah oleh penjadwal. |

Batas `deadline_at` bersifat inklusif: pada detik yang sama dengan batas, sesi sudah ditutup (`now() >= deadline`).

## 3. Idempotensi dan Konsistensi

- **Penguncian baris.** `close()` mengambil `lockForUpdate()` pada sesi dan memeriksa status ulang di dalam transaksi. Dua proses yang berjalan bersamaan tidak akan menilai sesi yang sama dua kali.
- **Hitungan akurat.** `expireDue()` hanya menghitung sesi yang benar-benar berpindah status. Menjalankan ulang tidak menambah hitungan.
- **Waktu server.** Semua perbandingan memakai `now()` server. Waktu di perangkat peserta tidak dipakai.
- **Event append-only.** Setiap penutupan mencatat satu baris `expired` di `pmb_assessment_session_events` dengan `actor = system`, `answered`, `total`, dan `scored`. Tes memastikan jumlah event tidak berubah saat dijalankan ulang.
- **Lazy expiry.** Sesi juga ditutup saat diakses peserta atau admin (`payload()`, `saveResponses()`, `submit()`), sehingga peserta tidak pernah melihat sesi yang sudah lewat batas sebagai "masih berjalan" meskipun penjadwal terlambat.

## 4. Konfigurasi Server

Penjadwal membutuhkan satu entri cron per server. Ini sudah menjadi syarat modul PMB (lihat `docs/manual-book/pmb/50-administrasi-sistem.md` §4):

```cron
* * * * * cd /path/ke/app && php artisan schedule:run >> /dev/null 2>&1
```

Verifikasi di server setelah deploy:

```bash
php artisan schedule:list | grep pmb:tick       # harus tampil everyMinute
php artisan pmb:tick                            # satu kali manual; aman dijalankan ulang
```

Catatan: jangan menjalankan `pmb:tick` manual di server produksi tanpa memeriksa `.env` dan target database terlebih dahulu. Tick juga menjalankan langkah PMB lain (publikasi terjadwal, outbox, konversi, polling pembayaran). Lihat catatan memori proyek tentang `.env` yang mengarah ke produksi.

## 5. Pengujian

`Tests/Feature/AssessmentMonitoringTest.php`, tes *"pmb:tick menutup sesi tepat pada batas waktu, sekali saja, dan tidak menyentuh sesi final"*:

1. Tiga sesi dibuat: satu parsial, satu lengkap belum dikirim, satu sudah dikirim.
2. Waktu diatur satu detik sebelum batas paling awal. `pmb:tick` dijalankan. Tidak ada sesi yang berubah.
3. Waktu diatur tepat ke batas paling akhir. `pmb:tick` dijalankan. Sesi parsial `expired` tanpa skor, sesi lengkap `expired` dengan skor, sesi terkirim tetap `submitted`.
4. `pmb:tick` dijalankan ulang. Jumlah event `expired` tetap dua.

Batas waktu dibaca dari data sesi, bukan dihitung ulang di tes. Dua peserta yang mulai pada detik berbeda memang punya batas berbeda; tes sebelumnya gagal karena asumsi ini, dan sudah diperbaiki.

## 6. Belum Selesai

- Pemberitahuan kepada peserta saat sesi ditutup otomatis. Belum ada.
- Perpanjangan waktu oleh admin. Sengaja tidak disediakan (prompt Fase 2, bagian 8).
- Pengujian pada MySQL dengan beban banyak sesi. Lihat [07](07-testing-and-verification.md).
