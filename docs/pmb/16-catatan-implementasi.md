# 16 — Catatan Implementasi Modul PMB

Dokumen ini mencatat **bagaimana** rancangan PMB (dokumen 01–15) diimplementasikan di CivitasOne: struktur kode, keputusan atas kontradiksi/celah dokumen, cara operasional, dan hal yang masih memerlukan pihak eksternal. Bacaan wajib sebelum mengubah modul `app/Modules/Admission`.

## 1. Peta kode

| Lapisan | Lokasi | Isi |
|---|---|---|
| Migrasi | `app/Modules/Admission/Database/Migrations` | 74 tabel `pmb_*` (konfigurasi, pendaftar & aplikasi, keuangan, ujian/seleksi/keputusan, komunikasi & dukungan) |
| Integrasi modul lain | `Academic/Database/Migrations/2026_10_13_110000_*`, `Finance/Database/Migrations/2026_10_13_110100_*` | Kolom asal PMB pada `students`, `student_profiles`, `invoices.source_*`, `payments.reference/channel` — **aditif, tanpa mengubah data lama** |
| Model & enum | `Admission/Models`, `Admission/Enums` | Model `TenantScoped` (ULID) + enum mesin keadaan (`allowedTransitions()`) |
| Layanan domain | `Admission/Services/*` | Seluruh aturan bisnis (BR-*) — controller tipis |
| Mesin seleksi | `Admission/Selection` | Kalkulator & alokator murni & deterministik (+ hash SHA-256) |
| Outbox | `Admission/Outbox` | Efek samping (notifikasi, kartu ujian, LoA, konversi, ekspor) ditulis satu transaksi dengan perubahan data |
| HTTP | `Admission/Routes/api.php`, `Controllers/{Applicant,Admin}`, `Http/Middleware` | 328+ endpoint di `/api/v1/pmb/*` |
| Penjadwal | `Console/PmbTickCommand` (tiap menit), `PmbDailyCommand` (00:15), `PmbInstallCommand` | Bekerja tanpa worker antrean (shared hosting) |
| Frontend | `frontend/src/pmb` | Website publik `/pmb/*`, portal pendaftar `/pmb/portal/*`, admin `/admin/pmb/*` |
| Tes | `Admission/Tests/{Unit,Feature}` | Unit mesin seleksi & QR, end-to-end, keamanan, operasional |

## 2. Keputusan atas kontradiksi & celah dokumen

| Kode | Topik | Keputusan |
|---|---|---|
| D-01 | Nama modul | Kunci modul `admission` (bukan `pmb`); prefix tabel/permission tetap `pmb_`. |
| D-02 | Event approval | Mesin ApprovalWorkflow hanya punya `ApprovalRequestApproved/Rejected/Returned`; listener `SyncApprovalDecisions` memetakan ke run seleksi, refund, dan `pmb_change_requests`. |
| D-03 | Pemisahan tugas (SoD) | Kontrak opsional `RequiresDistinctApprover` pada ApprovalWorkflow: pengaju & pihak yang dikecualikan (mis. verifikator bukti bayar) tidak dapat menyetujui → 409 `SELF_APPROVAL_FORBIDDEN`. Modul lain tidak terdampak. |
| D-04 | Slug permission | Nama fungsional dokumen 08 dipetakan ke action standar `PermissionAction` (lihat docblock `Support/PmbPermission`). |
| F1 | BR-04 | Default 1 aplikasi aktif per periode; dapat diubah lewat `pmb.max_active_applications_per_period`. |
| F2 | Batas perbaikan | `min(sekarang + pmb.revision_window_hours, form_lock_at gelombang)`. |
| F3 | Cicilan biaya awal | Diizinkan per komponen (`installable` + rencana Σ100%); minimum daftar ulang = `pmb.enrollment_min_payment`. |
| F4 | Transaksi pending | Satu transaksi pending per tagihan lintas kanal; ganti kanal = batalkan dulu. |
| F5 | Transaksi gagal | Tagihan kembali ke `partially_paid` bila sudah ada pembayaran, selain itu `unpaid`. |
| F12 | Override formulir | Admin dapat membuka kembali `locked → submitted` untuk koreksi. |
| F13 | Penarikan | Pendaftar hanya dapat menarik sebelum hasil terbit. |
| F14 | Nomor peserta | Diawali kode periode agar tidak bertabrakan antarperiode. |
| F15 | Urutan NIM | Kunci urutan = semua token format selain `{SEQ:n}` yang telah dirender (mis. `27TI`). |
| D-05 | Publikasi tepat waktu tanpa scheduler | Pemicu malas: endpoint hasil & cek status memanggil `publishDue()` (dengan cache lock). |
| D-06 | Rahasia di outbox | Placeholder `otp_code`, `reset_url`, `verification_url` disegel (`Crypt`) di payload outbox, tidak pernah tersimpan polos. |
| D-07 | Cache | Payload `Cache::remember` dinormalisasi ke array murni (Collection bersarang rusak saat dibaca dari Redis). |

## 3. Keamanan yang diterapkan

- **Isolasi tenant**: semua model `TenantScoped`; token pendaftar mengikat tenant dari akun (`EnsureApplicant`) dan ditolak bila header menunjuk tenant lain; portal tidak memakai route model binding (kepemilikan via relasi akun → 404 identik).
- **RBAC + scope**: permission per endpoint; staf ber-scope fakultas/prodi dibatasi di query (`ScopeResolver`), bukan disembunyikan di UI.
- **Data sensitif**: NIK, paspor, nama ibu, KIP, rekening refund, jawaban sensitif dienkripsi; tampil tersamar; pembukaan nilai asli wajib tujuan & tercatat (`pmb_sensitive_access_logs`).
- **Berkas**: deteksi MIME via `finfo`, re-encode gambar, validasi PDF, path per tenant, `VirusScanner`, unduhan hanya lewat URL bertanda tangan berumur pendek.
- **Idempotensi**: header `Idempotency-Key` disimpan di DB 24 jam (replay identik; kunci dipakai ulang dengan isi berbeda → 422).
- **Webhook**: token `x-callback-token` Xendit (`XENDIT_WEBHOOK_TOKEN`) + verifikasi ulang status ke API penyedia (`GET /v2/invoices/{id}`); callback duplikat → no-op. URL callback bersama PMB & KEU: `POST /api/v1/webhooks/xendit` (diarahkan menurut prefiks `external_id` `PMB-`/`KEU-`).
- **Ekspor**: alasan wajib, dicatat, hanya dapat diunduh pemiliknya, kedaluwarsa 24 jam; kolom CSV diawali `= + - @` dinetralkan.

## 4. Operasional

1. Jalankan migrasi: `php artisan migrate` (aditif).
2. Seeder permission & role: `php artisan db:seed --class="Modules\\UserManagement\\Database\\Seeders\\RolePermissionSeeder"` lalu `OrganizationalRoleSeeder` (role baru: `admission_administrator`, `admission_approver`, `admission_verifier`, `admission_selection_committee`, `admission_registrar`).
3. Data awal per kampus: `php artisan pmb:install {kode_kampus?}` — jenis dokumen default (04 §E.2), sistem kuliah, alur persetujuan default. Aman diulang.
4. Penjadwal: `* * * * * php artisan schedule:run` (menjalankan `pmb:tick` & `pmb:daily`). Worker antrean opsional.
5. Variabel lingkungan: lihat blok `XENDIT_*` & `PMB_*` di `.env.example` dan `VITE_PMB_UNIVERSITY_CODE` di `frontend/.env.example`.
6. Urutan konfigurasi admin: Jalur & Dokumen → Template Formulir (publikasi) → Skema Seleksi (kunci) → Periode → Gelombang → Gelombang-jalur → Kuota → Persyaratan dokumen → Biaya → Konten legal (Kebijakan Privasi & Ketentuan) → Publikasi periode (daftar periksa).

## 5. Ketergantungan eksternal / belum terverifikasi

| Butir | Status | Yang dibutuhkan |
|---|---|---|
| Gateway Xendit (Invoice API: VA, QRIS, e-wallet, ritel) | Adapter lengkap & teruji dengan respons tiruan (HTTP fake) | Secret key & webhook token + uji di test mode Xendit |
| SMS/WhatsApp | Driver `log` & `http` generik | Kontrak & spesifikasi API penyedia |
| CAPTCHA Turnstile | Terintegrasi | Site key & secret |
| Antivirus | Kontrak `VirusScanner`; terpasang `NullVirusScanner` | ClamAV/penyedia pemindai |
| Integrasi CRM (MauKuliah) | Endpoint penerima lead | Spesifikasi API CRM untuk sinkron dua arah |
| Tanda tangan elektronik LoA | LoA PDF + QR verifikasi publik | Penyedia TTE tersertifikasi |
| CBT eksternal | Webhook HMAC tersedia | Spesifikasi penyedia |
| Lingkungan PHP | Kode berjalan di PHP 8.3 | `composer.lock` memuat `openspout` yang mensyaratkan PHP ≥ 8.4 — samakan versi PHP server atau perbarui lock |
