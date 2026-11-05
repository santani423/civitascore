# One Atma Talent — Pengujian dan Verifikasi Fase 2

> **Per:** 5 November 2026, branch `feat/one-atma-talent` (dibuat dari `main`). Ditulis untuk tim QA dan pengembang yang melanjutkan fitur ini.

## 1. Baseline dan Hasil Akhir

| Titik | Hasil |
|---|---|
| Baseline `main` (sebelum perubahan, tree bersih, SQLite in-memory) | **427 tes lulus** |
| Akhir (setelah Fase 2) | **452 tes lulus**, 3710 assertion, 0 gagal (sekitar 58 detik) |
| Tes baru Talenta | 25 (lihat §2) |

Angka baseline sebelumnya (440) berasal dari branch `feat/attendance-qr-checkin`, yang membawa fitur attendance. Angka itu tidak dipakai lagi sebagai pembanding karena branch baru dibuat dari `main`.

## 2. Tes Otomatis

| File | Jumlah | Cakupan |
|---|---|---|
| `Tests/Unit/RiasecScorerTest.php` | 6 | Butir terbalik, rata-rata versus jumlah, seri, Holland Code, dimensi kosong, nilai di luar skala. |
| `Tests/Feature/AssessmentTalentTest.php` | 6 | Alur peserta lengkap (persetujuan, mulai, simpan, kirim, hasil), versi terkunci, tujuan seleksi ditolak, waktu habis, kepemilikan data, staf tanpa izin. |
| `Tests/Feature/AssessmentAdminTest.php` | 7 | Persetujuan dua pihak dengan pemisahan tugas, kembali dan tolak, satu versi terbuka per jenis dan diff, arsip dan hapus draft, bank soal, izin per role, audit. |
| `Tests/Feature/AssessmentMonitoringTest.php` | 6 | Daftar dan filter, privasi jawaban, review menahan publikasi, invalidasi, dashboard, `pmb:tick` pada batas waktu, batas peran pendaftar versus staf. |

Menjalankan:

```bash
php artisan test --filter=Assessment          # 25 tes Talenta
php artisan test                              # suite penuh
PAO_DISABLE=1 php artisan test <path>         # jika output kosong dan kode keluar 1 (lihat catatan di bawah)
```

Catatan: `php artisan test` dapat mencetak output kosong dan keluar dengan kode 1 saat ada error fatal di file tes. Wrapper `laravel/pao` menelan output. Jalankan ulang dengan `PAO_DISABLE=1` untuk melihat error sebenarnya.

Seluruh tes memakai SQLite in-memory dari `phpunit.xml`. Tidak ada tes yang menyentuh `.env`, dan tidak ada migrasi yang dijalankan ke database lain.

## 3. Pemetaan Regresi (prompt bagian 14)

| Item | Status | Bukti |
|---|---|---|
| Peserta masih dapat mengerjakan RIASEC | Lulus | `AssessmentTalentTest` alur lengkap. |
| Autosave tetap bekerja | Lulus di API | Simpan batch dan kirim ulang (upsert) diuji. Perilaku di browser peserta belum diuji ulang di fase ini. |
| Timer tetap menggunakan waktu server | Lulus | `server_time` dikirim; penutupan diuji pada batas waktu persis. |
| Submit tetap bekerja | Lulus | Alur lengkap, kirim dua kali (idempoten), kirim dengan butir kosong ditolak. |
| Scoring menghasilkan hasil yang sama untuk input sama | Lulus | Algoritma `riasec-mean-v1` tidak diubah. Tes unit mengunci hasil untuk input tertentu. |
| Hasil peserta hanya tampil setelah dipublikasikan | Lulus | `RESULT_NOT_PUBLISHED` (409) sebelum terbit; invalidasi dan sesi tanpa skor tidak pernah tampil. |
| Tidak ada kebocoran kunci jawaban | Lulus | Payload peserta hanya berisi `id`, `stem`, `value`. Tes memeriksa `not->toHaveKeys(['dimension','is_reversed'])`. |
| Permission peserta dan admin tidak tertukar | Lulus | Token pendaftar ditolak di rute admin; akun staf ditolak di rute pendaftar (`AssessmentMonitoringTest`). |

## 4. Verifikasi Statis dan Build

| Pemeriksaan | Hasil |
|---|---|
| `vendor/bin/pint --test` pada file yang diubah | Lulus. |
| PHPStan pada file Talenta (`Services/Assessment`, controller, model, listener, tick) | Bersih, kecuali satu temuan di `Listeners/SyncApprovalDecisions.php:53` (`$request->university_id`). Baris itu sudah ada di `main` dan tidak diubah di fase ini. |
| `npx tsc -p tsconfig.app.json --noEmit` | Lulus. |
| `npx oxlint` pada file Talenta dan rute PMB | 0 error. Peringatan `react(only-export-components)` yang tersisa berasal dari `PmbRoutes.tsx` dan `AdminRoutes.tsx` yang sudah ada sebelumnya. |
| `npx vite build --outDir <folder sementara>` | Lulus. Muncul peringatan chunk di atas 500 kB. Tidak diselidiki apakah sudah ada di `main`. |
| `frontend/dist` | Tidak diubah. Build dijalankan ke folder sementara karena `dist` di-track git. |

## 5. Pengujian Browser

Dilakukan dengan Chromium dari cache Playwright di mesin ini (`chromium-1243`) dan Vite dev server. **Ini bukan pengujian end-to-end terhadap backend nyata.** API dibalas dengan fixture yang ditulis khusus untuk memeriksa kontrak frontend dan rendering.

| Pemeriksaan | Hasil |
|---|---|
| 14 pemeriksaan: 8 halaman admin Talenta (dashboard, asesmen, detail jenis, versi, bank soal, peserta, sesi, audit), 4 tab versi, 2 tab sesi | Semua dirender dengan teks yang diharapkan. Nol error runtime dan nol error konsol. |
| Tab versi (Pertanyaan, Persetujuan, Perbandingan, Riwayat) dan tab sesi (Jawaban, Review) | Dapat diklik tanpa error. |
| Pengguna hanya baca (tanpa izin ubah/keputusan) | Tombol Setujui, Kembalikan, Tolak, Ajukan, Terbitkan, Tambah dimensi, Tambah pertanyaan, Tandai perlu review, Terbitkan hasil, dan Invalidasi tidak tampil. Halaman tetap dirender. |

Skrip pemeriksaan tidak dimasukkan ke repo, karena bergantung pada cache Playwright di path lokal. Jika diperlukan di CI, pasang Playwright sebagai dev dependency terlebih dahulu.

Yang belum diuji di browser: halaman peserta (`AssessmentPages.tsx`) setelah perubahan status `invalidated`. Build dan type-check lulus, tetapi rendering belum diperiksa ulang.

## 6. MySQL

**Belum diuji.** Tidak ada database MySQL pengembangan yang sudah diverifikasi di lingkungan ini, dan `.env` lokal mengarah ke produksi, sehingga tidak dipakai.

Yang perlu dicek sebelum deploy di MySQL:

- Migrasi `2026_11_05_100000_create_pmb_assessment_tables.php` dan `2026_11_06_100000_extend_pmb_assessment_admin.php` berjalan tanpa error pada MySQL.
- Unique `(version_id, code)` di `pmb_assessment_items` berperilaku benar dengan kode `NULL` (MySQL mengizinkan banyak `NULL` pada unique index).
- Query dashboard berjalan dengan `date()` dan `substr()` pada MySQL.
- Penguncian `lockForUpdate()` pada `close()` dan `publish()` berjalan pada InnoDB.

## 7. Checklist Acceptance Criteria (prompt bagian 16)

| # | Kriteria | Status |
|---|---|---|
| 1 | Dashboard admin memakai data aktual | Terpenuhi. Tidak ada data dummy. Empty state tersedia. |
| 2 | Admin mengelola asesmen lewat UI | Terpenuhi (jenis, versi, dimensi, pertanyaan, persetujuan, publikasi, arsip). |
| 3 | Admin mengelola bank soal | Terpenuhi: telusuri, pratinjau, salin. Buat dan ubah di tab Pertanyaan versi. |
| 4 | Versi instrumen dapat dikelola | Terpenuhi: buat, salin, ajukan, setujui, kembalikan, tolak, terbitkan, arsipkan, hapus draft, diff. |
| 5 | Versi yang dipakai tidak berubah secara destruktif | Terpenuhi. Dikunci per status; revisi lewat salinan. |
| 6 | Admin memonitor peserta | Terpenuhi. |
| 7 | Admin melihat detail sesi | Terpenuhi. |
| 8 | Admin melakukan review hasil | Terpenuhi: catatan, tandai, selesaikan review. |
| 9 | Publikasi hasil memiliki kontrol izin | Terpenuhi. `pmb_assessment_results.publish` dan checklist. |
| 10 | Approval memiliki histori | Terpenuhi lewat engine ApprovalWorkflow dan audit. |
| 11 | Sesi kedaluwarsa diproses otomatis | Terpenuhi lewat `pmb:tick` (lihat [04](04-session-expiration.md)). Membutuhkan cron `schedule:run`. |
| 12 | Audit log tersedia | Terpenuhi: halaman Audit Talenta dan riwayat per versi. |
| 13 | Alur peserta RIASEC tetap berfungsi | Terpenuhi (tes dan regresi di §3). |
| 14 | Automated tests berhasil | Terpenuhi: 452 lulus. |
| 15 | Frontend type check dan build berhasil | Terpenuhi (§4). |
| 16 | Tidak ada perubahan tidak disengaja pada modul PMB lain | Terpenuhi sejauh tes dan `git diff` menunjukkan. Perubahan di luar Talenta: `ConsentType` (tambah nilai), `PmbApprovalService` (tambah jenis), `SyncApprovalDecisions` (tambah satu cabang), `PmbTickCommand` (tambah satu langkah), dan seeder izin. Tidak ada perubahan perilaku pada alur PMB lain. |

## 8. Yang Belum Dilakukan

- Pengujian MySQL (§6).
- Pengujian browser end-to-end terhadap backend dengan data PMB nyata.
- Pengujian aksesibilitas WCAG 2.2 AA dengan alat khusus.
- Pengujian beban untuk dashboard dan penjadwal pada volume peserta besar.
- Pengujian ulang halaman peserta di browser setelah perubahan `invalidated`.
- Commit. Perubahan belum di-commit sesuai instruksi.
