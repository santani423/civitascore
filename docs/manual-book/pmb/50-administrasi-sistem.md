# 50. Administrasi Sistem

[← Audit PMB](41-audit.md) · [Daftar isi](README.md) · [Berikutnya: Perbedaan Rancangan dan Implementasi →](60-perbedaan-rancangan-implementasi.md)

**Untuk:** administrator sistem/TI yang memasang dan merawat Civitas One. Ringkasan teknis lengkap ada di [16 — Catatan Implementasi](../../pmb/16-catatan-implementasi.md).

## 1. Prasyarat

| Komponen | Keterangan |
|---|---|
| Backend | Laravel (PHP). Kode PMB berjalan di PHP 8.3; **perhatikan**: `composer.lock` memuat pustaka `openspout` yang mensyaratkan PHP ≥ 8.4. Samakan versi PHP server atau perbarui lock (lihat [lampiran 61](61-hal-perlu-konfirmasi.md)). |
| Database | MySQL/MariaDB. Semua tabel PMB berawalan `pmb_`. |
| Frontend | React (Vite); kode PMB di `frontend/src/pmb`. |
| Penjadwal | Cron yang menjalankan `php artisan schedule:run` setiap menit. Worker antrean **opsional** (cocok untuk shared hosting). |
| Modul kampus | Modul **admission** (Penerimaan Mahasiswa Baru) harus aktif untuk universitas yang memakai PMB. |

## 2. Data awal, peran, dan izin

1. Jalankan migrasi (aditif, tidak mengubah data lama):

   ```bash
   php artisan migrate
   ```

2. Pasang izin dan peran PMB:

   ```bash
   php artisan db:seed --class="Modules\\UserManagement\\Database\\Seeders\\RolePermissionSeeder"
   php artisan db:seed --class="Modules\\UserManagement\\Database\\Seeders\\OrganizationalRoleSeeder"
   ```

   Peran baru: `admission_administrator`, `admission_approver`, `admission_verifier`, `admission_selection_committee`, `admission_registrar`. Peran yang sudah ada (rektor, wakil rektor, dekan, kaprodi, dosen, keuangan, auditor) mendapat izin PMB sesuai [Akses & Peran](02-akses-dan-peran.md#4-menu-yang-tampil-per-peran).

3. Siapkan data awal PMB per kampus:

   ```bash
   php artisan pmb:install            # semua universitas
   php artisan pmb:install UND        # satu universitas (ID atau kode)
   ```

   Membuat 15 jenis dokumen default, sistem kuliah *Reguler* & *Kelas Karyawan*, dan alur persetujuan default PMB. **Aman diulang**: data yang sudah ada tidak diduplikasi. Pada pemasangan baru, *seeder* universitas juga menjalankan langkah ini.

4. Berikan peran kepada staf melalui **Pengaturan → Role Pengguna** (dengan cakupan fakultas/prodi untuk dekan/kaprodi bila perlu).

> **Catatan** — Bila migrasi PMB pernah gagal di tengah jalan (MySQL tidak membatalkan DDL), migrasi berikutnya otomatis membersihkan tabel sisa **yang masih kosong**. Bila tabel sisa sudah berisi data, migrasi berhenti dengan pesan yang jelas tanpa menghapus apa pun; periksa secara manual.

## 3. Domain website PMB

Website PMB mengenali kampus dari **domain** yang didaftarkan pada data universitas (tabel `university_domains`, terlihat di menu platform **Manajemen Universitas**). Alternatif untuk pengembangan/SPA lintas domain:

- frontend mengirim header kode kampus dari parameter `?kampus=KODE` atau variabel `VITE_PMB_UNIVERSITY_CODE`;
- staf yang login memakai kampus aktif dari sesi.

> **Penting** — Domain yang terdaftar untuk satu kampus selalu diutamakan. Pastikan domain backend/API PMB dipetakan ke kampus yang benar, karena domain yang salah petakan membuat pendaftar tercatat di kampus lain.

## 4. Penjadwal (scheduler)

Tambahkan cron:

```cron
* * * * * cd /path/ke/civitasone && php artisan schedule:run >> /dev/null 2>&1
```

| Perintah | Jadwal | Tugas |
|---|---|---|
| `pmb:tick` | Setiap menit | Publikasi hasil terjadwal; pengiriman notifikasi & efek samping (antrean *outbox*); konversi mahasiswa; pemrosesan ulang callback; polling status transaksi gateway; kedaluwarsa transaksi; klaim verifikasi kedaluwarsa; pembaruan status gelombang; kirim otomatis CBT yang waktunya habis. |
| `pmb:daily` | Harian 00:15 | Draf tidak dikirim → batal; perbaikan kedaluwarsa; tagihan kedaluwarsa; daftar ulang kedaluwarsa; pengingat; heuristik duplikat; rekonsiliasi harian & kuota; snapshot statistik; penutupan periode; retensi data bulanan; pembersihan OTP/idempotensi. Satu langkah dapat dijalankan dengan `--only=<langkah>`. |

> **Catatan** — Tanpa penjadwal, publikasi hasil tetap terjadi saat pendaftar membuka halaman hasil/Cek Status, tetapi notifikasi, kedaluwarsa, dan konversi tidak berjalan. Pastikan cron aktif.

## 5. Variabel lingkungan (.env)

| Variabel | Fungsi |
|---|---|
| `PMB_STORAGE_DISK` | Disk penyimpanan berkas PMB (default `local`, privat). Unduhan selalu lewat URL bertanda tangan berumur pendek. |
| `PMB_CAPTCHA_DRIVER` | `none` atau `turnstile` (Cloudflare Turnstile). |
| `PMB_TURNSTILE_SITE_KEY`, `PMB_TURNSTILE_SECRET` | Kunci Turnstile. |
| `XENDIT_SECRET_KEY` | Secret API key Xendit (dipakai bersama modul Keuangan). Kosong = hanya transfer manual & loket. |
| `XENDIT_WEBHOOK_TOKEN` | Token verifikasi callback Xendit (header `x-callback-token`). URL callback *Invoices paid* di Dashboard Xendit: `<APP_URL>/api/v1/webhooks/xendit`. |
| `PMB_SMS_DRIVER` | `log` (hanya dicatat, tidak terkirim) atau `http` (penyedia generik). |
| `PMB_SMS_HTTP_ENDPOINT`, `PMB_SMS_HTTP_TOKEN` | Endpoint & token penyedia SMS/WA. |
| `PMB_CBT_WEBHOOK_SECRET` | Rahasia HMAC untuk sistem CBT eksternal. |
| `PMB_FRONTEND_URL` | Alamat frontend untuk tautan di email (verifikasi, atur sandi, portal). |
| `VITE_PMB_UNIVERSITY_CODE` (frontend) | Kode kampus default untuk website PMB bila tidak dikenali dari domain. |

**Endpoint integrasi** (di bawah `/api/v1/pmb`):

| Endpoint | Pengaman | Fungsi |
|---|---|---|
| `POST webhooks/payments/{provider}` | Token callback Xendit (`x-callback-token`) + verifikasi ulang status ke API Xendit | Notifikasi pembayaran. Callback ganda diabaikan. |
| `POST webhooks/cbt/{provider}` | HMAC (`PMB_CBT_WEBHOOK_SECRET`) | Nilai dari CBT eksternal. |
| `POST integrations/leads` | Token klien staf berizin | Penerimaan *lead* dari CRM/portal mitra. |

## 6. Keamanan & perawatan

- **Data sensitif** terenkripsi di database; kunci aplikasi (`APP_KEY`) wajib dicadangkan dengan aman. Kehilangan `APP_KEY` membuat data terenkripsi tidak terbaca.
- **Rahasia notifikasi** (OTP, tautan atur sandi/verifikasi) disegel terenkripsi di antrean *outbox*.
- **Idempotensi**: aksi pembayaran & keputusan penting memakai `Idempotency-Key` (disimpan 24 jam) sehingga klik ganda tidak menggandakan transaksi.
- **Batas laju**: halaman publik 60/menit/IP; cek status 5/menit; pendaftaran akun 5/jam; login 10/menit; unggah 20/menit; ekspor 10/jam.
- **Antivirus**: kontrak pemindai sudah tersedia, tetapi bawaan (`NullVirusScanner`) belum memindai. Pasang ClamAV/penyedia sebelum produksi (lihat [lampiran 61](61-hal-perlu-konfirmasi.md)).
- **Pencadangan**: cadangkan database dan disk penyimpanan berkas PMB secara berkala.

---

[← Audit PMB](41-audit.md) · [Daftar isi](README.md) · [Berikutnya: Perbedaan Rancangan dan Implementasi →](60-perbedaan-rancangan-implementasi.md)
