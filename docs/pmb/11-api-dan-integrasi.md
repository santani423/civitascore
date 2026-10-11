# 11 — Rancangan API dan Integrasi

[← Kembali ke dokumen utama](../RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md)

> **Status klaim:** seluruh endpoint di dokumen ini adalah **rancangan API baru untuk CivitasOne**. Tidak ada endpoint yang diklaim tersedia di SEVIMA. Integrasi eksternal (gateway, CBT, CRM, PDDikti, TTE) ditandai **perlu verifikasi** karena bergantung pada dokumentasi & kontrak penyedia.

---

# A. Rancangan API

## A.1 Konvensi umum (mengikuti kode yang ada)

| Aspek | Ketentuan |
|---|---|
| Base URL | `/api/v1` (`routes/api.php` memuat `app/Modules/Admission/Routes/api.php` otomatis, middleware `tenant.resolve`) |
| Tenant | Diresolusi dari domain (website PMB) / header `X-University-ID` / membership. `university_id` **tidak pernah** diterima di body/query |
| Format sukses | `App\Support\Http\ApiResponse::success()` → `{"success": true, "message": "...", "data": {...}, "meta": {}}` |
| Format daftar | `ApiResponse::paginated()` → `meta: {current_page, per_page, total, last_page}` |
| Query daftar | `ListQuery::paginate()` → `?search=`, `?filter[kolom]=a,b`, `?sort=-kolom`, `?per_page=` (dibatasi maksimum ListQuery) |
| Format error | `ApiResponse::error()` → `{"success": false, "message": "...", "errors": {...}}`; error domain memakai `errors.code` (pola `ConflictException`) |
| Kode HTTP | 200 OK · 201 Created · 202 Accepted (diproses asinkron) · 204 No Content · 400 tenant tak teresolusi · 401 tidak terautentikasi · 403 tidak berizin · 404 tidak ditemukan / di luar scope · 409 konflik status/versi/duplikat · 410 kedaluwarsa · 422 validasi / transisi ilegal · 423 terkunci · 429 rate limit · 503 penyedia eksternal tidak tersedia |
| Kode error domain (contoh) | `INVALID_TRANSITION`, `VERSION_CONFLICT`, `WAVE_CLOSED`, `APPLICATION_LIMIT_REACHED`, `IDENTITY_ALREADY_REGISTERED`, `PAYMENT_REQUIRED`, `INCOMPLETE_APPLICATION`, `DOCUMENT_LOCKED`, `INVOICE_NOT_PAYABLE`, `AMOUNT_MISMATCH`, `QUOTA_FULL`, `SCHEME_NOT_LOCKED`, `SCORES_LOCKED`, `SESSION_FULL`, `SCHEDULE_CONFLICT`, `SELF_APPROVAL_FORBIDDEN`, `DEADLINE_PASSED`, `CONVERSION_IN_PROGRESS` |
| Idempotensi | Header `Idempotency-Key` (UUID/ULID, ≤ 100 karakter) **wajib** untuk endpoint bertanda **[IDEM]**. Disimpan 24 jam (Redis + kolom tabel terkait): request ulang dengan kunci & body sama → respons pertama diulang; kunci sama, body berbeda → 422 `IDEMPOTENCY_KEY_REUSED` |
| Optimistic lock | Endpoint ubah status/data yang dapat diedit bersamaan menerima `lock_version` di body; tidak cocok → 409 `VERSION_CONFLICT` dengan `errors.current_version` |
| Waktu | ISO 8601 UTC di API; klien menampilkan dalam `universities.timezone` |
| Uang | String desimal (`"250000.00"`) agar tidak hilang presisi |
| Audit | Semua endpoint mutasi staf menulis `audit_logs`; endpoint mutasi pendaftar menulis history status bila mengubah status |
| Keamanan | HTTPS wajib; `auth:sanctum` + ability; rate limit per grup (A.2); CORS hanya domain tenant; respons portal `Cache-Control: no-store` |

## A.2 Grup, autentikasi, dan rate limit

| Grup | Prefiks | Auth | Rate limit (usulan) |
|---|---|---|---|
| Publik | `/pmb/public` | — | 60/menit/IP; `status-check` 5/menit/IP + CAPTCHA |
| Autentikasi pendaftar | `/pmb/applicant/auth` | — / token | register 5/jam/IP; login 10/menit/IP + 5 gagal/15 menit/akun; OTP 5/jam/tujuan |
| Portal pendaftar | `/pmb/applicant` | Sanctum, tokenable `PmbApplicantAccount`, ability `applicant` | 120/menit/akun; unggah 20/menit |
| Admin | `/pmb/admin` | `auth:sanctum` (users) + `permission:*` + policy | 300/menit/pengguna; ekspor 10/jam |
| Webhook | `/pmb/webhooks` | Tanda tangan HMAC (+ allowlist IP bila penyedia mendukung) | 600/menit/penyedia |
| Integrasi | `/pmb/integrations` | Token klien (Sanctum ability `pmb:integration:<scope>`) | 60/menit/klien |

Kolom tabel endpoint: **Method** · **URL** (relatif `/api/v1`) · **Tujuan** · **Role/Permission** · **Parameter & body utama** · **Sukses** · **Error khas** · Catatan (IDEM = idempoten wajib, PAG = paginated).

## A.3 Authentication (pendaftar)

| Method | URL | Tujuan | Role | Parameter & body | Sukses | Error khas | Catatan |
|---|---|---|---|---|---|---|---|
| POST | `/pmb/applicant/auth/register` | Registrasi akun | Publik | `name, email, phone?, password, password_confirmation, consents[] (privacy_policy, terms, marketing_optional?), info_source?, captcha_token` | 201 `{account: {id, email, status}}` | 422 validasi; 429 | Email yang sudah terdaftar → **201 dengan respons identik** + email ke pemilik ("ada upaya daftar dengan email Anda") — anti-enumerasi |
| POST | `/pmb/applicant/auth/email/verify` | Verifikasi tautan email | Publik | `token` | 200 | 410 kedaluwarsa; 422 tidak valid | Token sekali pakai |
| POST | `/pmb/applicant/auth/email/resend` | Kirim ulang verifikasi | Token (unverified) | — | 202 | 429 | |
| POST | `/pmb/applicant/auth/otp/send` | Kirim OTP | Token / publik (reset) | `channel, purpose, destination?` | 202 | 429 | Respons sama walau tujuan tidak terdaftar |
| POST | `/pmb/applicant/auth/otp/verify` | Verifikasi OTP | Token / publik | `purpose, destination?, code` | 200 (reset: `reset_token`) | 422; 423 percobaan habis | |
| POST | `/pmb/applicant/auth/login` | Login | Publik | `login (email/phone), password, device_name` | 200 `{token, expires_at, account}` | 401 kredensial; 423 terkunci; 429 | Pesan generik untuk kredensial salah |
| POST | `/pmb/applicant/auth/logout` | Logout perangkat ini | Pendaftar | — | 204 | — | |
| POST | `/pmb/applicant/auth/logout-all` | Cabut semua token | Pendaftar | — | 204 | — | |
| POST | `/pmb/applicant/auth/password/forgot` | Minta reset | Publik | `email` | 202 (selalu) | 429 | Anti-enumerasi |
| POST | `/pmb/applicant/auth/password/reset` | Reset sandi | Publik | `token, password, password_confirmation` | 200 | 410; 422 | Mencabut semua token |
| GET | `/pmb/applicant/auth/sessions` | Daftar sesi aktif | Pendaftar | — | 200 `[{id, device_name, last_used_at, ip_masked, current}]` | — | |
| DELETE | `/pmb/applicant/auth/sessions/{tokenId}` | Cabut sesi | Pendaftar | — | 204 | 404 | |
| POST | `/pmb/applicant/auth/recovery-requests` | Pemulihan akun (kehilangan email/HP) | Publik | `full_name, birth_date, identity_number, new_email, identity_document (file), captcha_token` | 202 | 429 | Diproses petugas |

## A.4 Applicant profile

| Method | URL | Tujuan | Role | Body | Sukses | Error khas |
|---|---|---|---|---|---|---|
| GET | `/pmb/applicant/me` | Akun + ringkasan aplikasi | Pendaftar | — | 200 | — |
| PATCH | `/pmb/applicant/me` | Ubah nama tampilan/HP | Pendaftar | `name?, phone?` | 200 | 422 |
| PUT | `/pmb/applicant/me/password` | Ubah sandi | Pendaftar | `current_password, password, password_confirmation` | 204 | 422 |
| POST | `/pmb/applicant/me/email-change` | Ganti email (verifikasi ke email baru) | Pendaftar | `new_email, password` | 202 | 422 |
| GET | `/pmb/applicant/profile` | Profil identitas (NIK dimasking kecuali `?reveal=identity` oleh pemilik) | Pendaftar | — | 200 | — |
| PUT | `/pmb/applicant/profile` | Simpan profil (parsial diperbolehkan) | Pendaftar | Field D.2.1 + `lock_version` | 200 | 409 `IDENTITY_ALREADY_REGISTERED`, `VERSION_CONFLICT`; 423 bila terkunci (ada aplikasi submitted → field identitas readonly) |
| GET/POST | `/pmb/applicant/educations` | Daftar / tambah riwayat pendidikan | Pendaftar | Field D.2.2 | 200 / 201 | 422 |
| PUT/DELETE | `/pmb/applicant/educations/{id}` | Ubah / hapus | Pendaftar | — | 200 / 204 | 423 bila dirujuk aplikasi submitted |
| PUT | `/pmb/applicant/educations/{id}/report-scores` | Ganti set nilai rapor | Pendaftar | `scores: [{semester, subject_code, score}]` | 200 | 422 |
| GET/POST | `/pmb/applicant/guardians` | Orang tua/wali | Pendaftar | Field D.2.4 | 200 / 201 | 422 |
| PUT/DELETE | `/pmb/applicant/guardians/{id}` | Ubah / hapus | Pendaftar | — | 200 / 204 | — |
| GET/POST | `/pmb/applicant/achievements` | Prestasi | Pendaftar | Field D.2.4 | 200 / 201 | 422 |
| PUT/DELETE | `/pmb/applicant/achievements/{id}` | Ubah / hapus | Pendaftar | — | 200 / 204 | 423 |
| GET | `/pmb/applicant/notifications` | Notifikasi in-app | Pendaftar | PAG | 200 | — |
| POST | `/pmb/applicant/notifications/{id}/read` | Tandai dibaca | Pendaftar | — | 204 | — |

## A.5 Application form (pendaftar)

| Method | URL | Tujuan | Body / parameter | Sukses | Error khas | Catatan |
|---|---|---|---|---|---|---|
| GET | `/pmb/applicant/open-wave-paths` | Gelombang-jalur yang dapat didaftar | — | 200 `[{wave_path_id, wave, path, fee, closes_at, max_choices, eligible}]` | — | `eligible=false` + alasan bila BR-04 tidak terpenuhi |
| GET | `/pmb/applicant/applications` | Daftar aplikasi milik akun | — | 200 (dengan `current_stage`) | — | |
| POST | `/pmb/applicant/applications` | Buat draft | `wave_path_id` | 201 | 409 `APPLICATION_LIMIT_REACHED`; 422 `WAVE_CLOSED`; 403 akun belum terverifikasi | **[IDEM]** |
| GET | `/pmb/applicant/applications/{id}` | Detail lengkap (form schema + nilai + status) | — | 200 | 404 | Termasuk definisi field dinamis yang tampil |
| PATCH | `/pmb/applicant/applications/{id}` | Simpan bagian (autosave) | `section, data{}, lock_version` | 200 `{lock_version}` | 409 `VERSION_CONFLICT`; 423 `APPLICATION_LOCKED` | Hanya `draft` atau field yang dibuka saat `revision_requested` |
| PUT | `/pmb/applicant/applications/{id}/choices` | Simpan pilihan prodi | `choices: [{priority, study_program_id, study_system_id}], lock_version` | 200 | 422 kombinasi tidak ditawarkan; 423 | |
| PUT | `/pmb/applicant/applications/{id}/answers` | Simpan field tambahan | `answers: {key: value}, lock_version` | 200 | 422 | Field tersembunyi dibuang |
| GET | `/pmb/applicant/applications/{id}/completeness` | Checklist kelengkapan | — | 200 `{steps: [{key, complete, missing: [...]}], can_submit}` | — | |
| POST | `/pmb/applicant/applications/{id}/submit` | Kirim formulir | `declarations: [content_id...], lock_version` | 200 `{registration_number, form_status}` | 422 `INCOMPLETE_APPLICATION` (`errors.missing`); 402-gaya → 409 `PAYMENT_REQUIRED`; 422 `WAVE_CLOSED` | **[IDEM]**; submit ulang yang sukses mengembalikan hasil sama |
| POST | `/pmb/applicant/applications/{id}/withdraw` | Tarik aplikasi | `reason?` | 200 | 422 `INVALID_TRANSITION` (keputusan sudah published) | |
| DELETE | `/pmb/applicant/applications/{id}` | Hapus draft (→ `void`) | — | 204 | 422 bukan draft | |

## A.6 Document management

| Method | URL | Tujuan | Role | Body | Sukses | Error khas |
|---|---|---|---|---|---|---|
| GET | `/pmb/applicant/applications/{id}/documents` | Daftar persyaratan & status | Pendaftar | `?stage=` | 200 | — |
| POST | `/pmb/applicant/applications/{id}/documents/{documentId}/versions` | Unggah versi baru (multipart `file`) | Pendaftar | `file` | 201 `{version_no, status: uploaded, scan_status: pending}` | 422 tipe/ukuran; 423 `DOCUMENT_LOCKED`; 413 | 
| GET | `/pmb/applicant/applications/{id}/documents/{documentId}/download` | URL unduh bertanda tangan (5 menit) | Pendaftar | — | 200 `{url, expires_at}` | 409 file belum `clean` |
| GET | `/pmb/admin/applications/{id}/documents` | Dokumen + versi + review | `pmb_applications.read` | — | 200 | 404 di luar scope |
| GET | `/pmb/admin/document-versions/{versionId}/download` | Unduh/pratinjau | `pmb_documents.download` (+ `pmb_sensitive.read` bila sensitif) | `purpose` | 200 `{url}` | 403 | 
| POST | `/pmb/admin/application-documents/{documentId}/reviews` | Keputusan dokumen | `pmb_verification.decide` / `pmb_reregistration.decide` | `decision, reason_code?, note_for_applicant?, internal_note?, version_id` | 201 | 409 versi bukan yang terbaru; 422 transisi |

## A.7 Payment

| Method | URL | Tujuan | Role | Body / parameter | Sukses | Error khas | Catatan |
|---|---|---|---|---|---|---|---|
| GET | `/pmb/applicant/payment-channels` | Kanal aktif | Pendaftar | `?invoice_id=` | 200 | — | |
| GET | `/pmb/applicant/applications/{id}/invoices` | Tagihan aplikasi (dibuat lazy untuk pendaftaran) | Pendaftar | — | 200 | — | |
| POST | `/pmb/applicant/invoices/{id}/voucher` | Terapkan voucher | Pendaftar | `code` | 200 (tagihan terbaru) | 422 tidak berlaku; 409 sudah dipakai | **[IDEM]**; Phase 2 |
| POST | `/pmb/applicant/invoices/{id}/transactions` | Buat transaksi | Pendaftar | `channel, amount?` (cicilan) | 201 `{transaction: {id, status, va_number?, qr_string?, redirect_url?, expires_at, instructions}}` | 409 `INVOICE_NOT_PAYABLE`; 503 gateway | **[IDEM]** |
| GET | `/pmb/applicant/transactions/{id}` | Status transaksi (memicu pengecekan ke gateway bila pending > 2 menit, di-throttle) | Pendaftar | — | 200 | — | Tidak menerima status dari klien |
| POST | `/pmb/applicant/transactions/{id}/cancel` | Batalkan pending | Pendaftar | — | 200 | 422 | |
| POST | `/pmb/applicant/transactions/{id}/manual-proof` | Unggah bukti transfer | Pendaftar | multipart `file, sender_bank, sender_name, transfer_date, transferred_amount` | 201 | 422; 409 sudah ada bukti aktif | **[IDEM]** |
| GET | `/pmb/applicant/invoices/{id}/receipt` | Kuitansi PDF | Pendaftar | — | 200 `{url}` | 409 belum lunas | |
| POST | `/pmb/webhooks/payments/{provider}` | Callback gateway | Tanda tangan | Payload penyedia | 200 / 401 | — | Lihat [05 §F.4](05-spesifikasi-pembayaran-dan-verifikasi.md#f4-penerimaan-callback-webhook--alur-aman) |
| GET | `/pmb/admin/invoices` | Daftar tagihan | `pmb_payments.read` | filter: type, status, wave, path, needs_resolution | 200 | — | PAG |
| GET | `/pmb/admin/transactions` | Daftar transaksi | `pmb_payments.read` | filter: status, channel, provider, tanggal | 200 | — | PAG |
| POST | `/pmb/admin/transactions/{id}/recheck` | Cek ulang status ke gateway | `pmb_payments.reconcile` | — | 200 | 503 | |
| GET | `/pmb/admin/manual-proofs` | Antrean bukti manual | `pmb_payments.verify_manual` | filter status | 200 | — | PAG |
| POST | `/pmb/admin/manual-proofs/{id}/decision` | Setujui/tolak bukti | `pmb_payments.verify_manual` | `decision, reason_code?, note?` | 200 | 409 sudah diputuskan; 403 SoD | **[IDEM]** |
| POST | `/pmb/admin/invoices/{id}/cashier-payments` | Catat pembayaran tunai loket | `pmb_payments.verify_manual` | `amount, receipt_number, paid_at` | 201 | 422 `AMOUNT_MISMATCH` | **[IDEM]** |
| POST | `/pmb/admin/invoices/{id}/resolve` | Selesaikan `needs_resolution` (BR-18) | `pmb_payments.reconcile` (+ `pmb_admin` untuk buka kembali aplikasi) | `resolution: accept_and_reopen|refund, note` | 200 | 422 | |
| GET/POST | `/pmb/admin/reconciliation-batches` | Daftar / buat batch (tarik settlement) | `pmb_payments.reconcile` | `provider, statement_date` | 200 / 202 | 409 batch tanggal sama | |
| POST | `/pmb/admin/reconciliation-batches/{id}/import` | Impor mutasi bank CSV | `pmb_payments.reconcile` | multipart `file` | 202 | 422 format | |
| PATCH | `/pmb/admin/reconciliation-items/{id}` | Resolusi item | `pmb_payments.reconcile` | `resolution, note` | 200 | — | |
| POST | `/pmb/admin/reconciliation-batches/{id}/close` | Tutup batch | `pmb_payments.reconcile` | — | 200 | 422 item belum selesai | |
| GET/POST | `/pmb/admin/refunds` | Daftar / ajukan refund | `pmb_refunds.request` | `transaction_id, amount, reason_code, reason_note, beneficiary_*` | 200 / 201 | 422 > sisa; 403 SoD | **[IDEM]** |
| POST | `/pmb/admin/refunds/{id}/complete` | Catat refund selesai | `pmb_refunds.request` | `proof_file_id, completed_at` | 200 | 422 belum approved | |

## A.8 Verification

| Method | URL | Tujuan | Permission | Body / parameter | Sukses | Error khas |
|---|---|---|---|---|---|---|
| GET | `/pmb/admin/applications` | Daftar & antrean | `pmb_applications.read` | `search, filter[period_id], filter[wave_id], filter[admission_path_id], filter[study_program_id], filter[study_system_id], filter[form_status], filter[verification_status], filter[registration_payment_status], filter[selection_status], filter[assignee]=me, filter[has_flag], sort=-submitted_at, per_page` — PAG | 200 | — |
| GET | `/pmb/admin/applications/{id}` | Detail (sensitif dimasking) | `pmb_applications.read` | — | 200 | 404 di luar scope |
| POST | `/pmb/admin/applications/{id}/reveal` | Buka field sensitif | `pmb_sensitive.read` | `fields[], purpose` | 200 nilai | 403 | 
| POST | `/pmb/admin/verification/claim-next` | Ambil aplikasi berikutnya | `pmb_verification.decide` | `filter{}` | 200 `{application_id}` / 204 kosong | — |
| POST | `/pmb/admin/applications/{id}/claim` | Klaim aplikasi tertentu | `pmb_verification.decide` | — | 200 | 409 `ALREADY_CLAIMED` |
| POST | `/pmb/admin/applications/{id}/release` | Lepas klaim | Pemilik klaim / `pmb_verification.assign` | — | 204 | — |
| POST | `/pmb/admin/verification/assignments` | Bagi tugas (push) | `pmb_verification.assign` | `application_ids[] | filter{}, reviewer_ids[], strategy` | 202 | — |
| POST | `/pmb/admin/applications/{id}/verify` | Verifikasi | `pmb_verification.decide` + klaim | `lock_version, note?` | 200 | 422 `INVALID_TRANSITION` / `errors.unmet` (BR-22); 409 versi/klaim |
| POST | `/pmb/admin/applications/{id}/request-revision` | Minta perbaikan | `pmb_verification.decide` + klaim | `targets: {fields[], documents[]}, note_for_applicant, due_at?, lock_version` | 200 | 422 lewat `form_lock_at` |
| POST | `/pmb/admin/applications/{id}/reject` | Tolak administrasi | `pmb_verification.decide` + klaim | `reason_code, note_for_applicant, internal_note?, lock_version` | 200 | 422 |
| POST | `/pmb/admin/applications/{id}/reopen` | Buka kembali verified/rejected | `pmb_verification.override` | `reason` | 200 | 422 sudah dinilai |
| POST | `/pmb/admin/verification/bulk-verify` | Verifikasi massal aman | `pmb_verification.decide` | `application_ids[] (≤100)` | 200 `{succeeded[], failed[{id, code}]}` | — | **[IDEM]**; Phase 2 |
| POST | `/pmb/admin/applications/{id}/notes` | Catatan | `pmb_applications.read` | `visibility, body` | 201 | — |
| PATCH | `/pmb/admin/applications/{id}/corrections` | Koreksi data oleh petugas | `pmb_applications.correct` | `changes{field: value}, reason` | 200 | 422 |
| POST | `/pmb/admin/assisted-applications` | Akun + aplikasi atas nama pendaftar | `pmb_applications.assisted_create` | Data registrasi + `wave_path_id` | 201 | 409 identitas terdaftar | **[IDEM]** |
| GET | `/pmb/admin/duplicate-flags` | Daftar flag | `pmb_verification.decide` | PAG | 200 | — |
| PATCH | `/pmb/admin/duplicate-flags/{id}` | Selesaikan flag | `pmb_verification.override` | `status, resolution_note` | 200 | — |

## A.9 Admission configuration

Pola: `GET` (list, PAG) / `POST` (create) pada koleksi; `GET` / `PATCH` / `DELETE` pada item. Semua `PATCH` menerima `reason` bila mengubah konfigurasi yang sudah dipakai (BR-03) dan mengembalikan 409 `CONFIG_IN_USE` bila perubahan ditolak.

| Resource | URL koleksi | Aksi tambahan | Permission |
|---|---|---|---|
| Periode | `/pmb/admin/periods` | `POST /{id}/publish` (422 `errors.incomplete[]`), `POST /{id}/close`, `POST /{id}/archive` | `pmb_config.manage` / `.publish` |
| Gelombang | `/pmb/admin/periods/{periodId}/waves` | `PATCH /pmb/admin/waves/{id}/extend` (`registration_closes_at`, `reason`) | `pmb_config.manage` |
| Jalur | `/pmb/admin/admission-paths` | — | `pmb_config.manage` |
| Sistem kuliah | `/pmb/admin/study-systems` | — | `pmb_config.manage` |
| Gelombang-jalur | `/pmb/admin/waves/{waveId}/paths` | `PATCH /pmb/admin/wave-paths/{id}/toggle-open` (`is_open`, `reason`) | `pmb_config.manage` |
| Kuota | `/pmb/admin/periods/{periodId}/quotas` | `PUT /pmb/admin/periods/{periodId}/quotas/bulk` (upsert matriks) | `pmb_config.manage` |
| Jenis dokumen | `/pmb/admin/document-types` | — | `pmb_config.manage` |
| Persyaratan dokumen | `/pmb/admin/wave-paths/{id}/document-requirements` | `PUT .../bulk` | `pmb_config.manage` |
| Template formulir | `/pmb/admin/form-templates` | `/{id}/fields` (CRUD), `POST /{id}/publish`, `POST /{id}/new-version`, `GET /{id}/preview` | `pmb_config.manage` / `.publish` |
| Komponen biaya | `/pmb/admin/periods/{periodId}/fee-components` | `PUT .../bulk`, `POST .../copy-from/{otherPeriodId}` | `pmb_fees.manage` |
| Skema seleksi | `/pmb/admin/selection-schemes` | `/{id}/components` (CRUD), `POST /{id}/lock`, `POST /{id}/new-version`, `POST /{id}/simulate` (data contoh → hasil hitung, tidak disimpan) | `pmb_selection.manage` / `.lock_scheme` |
| Profil prodi | `/pmb/admin/program-profiles` | `POST /{id}/publish` | `pmb_program_profile.manage` (scope) |
| Konten CMS | `/pmb/admin/contents` | `POST /{id}/publish`, `POST /{id}/unpublish`, `POST /{id}/new-version` | `pmb_content.manage` |
| Pengaturan | `/pmb/admin/settings` (GET, PUT) | — | `pmb_config.manage` |
| Pengaturan NIM | `/pmb/admin/nim-settings` (GET, PUT) | `POST /preview` (contoh NIM tanpa mengonsumsi sequence) | `pmb_nim.configure` |

## A.10 Examination

| Method | URL | Tujuan | Permission | Body | Sukses | Error khas |
|---|---|---|---|---|---|---|
| CRUD | `/pmb/admin/exam-venues`, `/pmb/admin/exam-venues/{id}/rooms` | Lokasi & ruang | `pmb_exams.manage` | — | — | 409 ruang dipakai |
| CRUD | `/pmb/admin/waves/{waveId}/exam-schedules` | Jadwal | `pmb_exams.manage` | Field FR-EXM-01 | 201 | 422 di luar jendela ujian |
| POST | `/pmb/admin/exam-schedules/{id}/publish` | Publikasi jadwal | `pmb_exams.manage` | — | 200 | 422 belum ada sesi |
| CRUD | `/pmb/admin/exam-schedules/{id}/sessions` | Sesi | `pmb_exams.manage` | `room_id, starts_at, ends_at, capacity` | 201 | 409 `SCHEDULE_CONFLICT`; 422 kapasitas > ruang |
| POST | `/pmb/admin/exam-schedules/{id}/auto-assign` | Bagi peserta otomatis | `pmb_exams.manage` | `strategy, include_unassigned_only` | 202 `{job_id}` | 409 kapasitas total kurang |
| POST | `/pmb/admin/exam-participants/{id}/move` | Pindah sesi | `pmb_exams.manage` | `session_id, reason` | 200 | 409 `SESSION_FULL` / `SCHEDULE_CONFLICT` |
| PUT | `/pmb/admin/exam-sessions/{id}/examiners` | Tetapkan penguji | `pmb_exams.manage` | `examiners: [{user_id, role}]` | 200 | 409 bentrok |
| POST | `/pmb/admin/exam-sessions/{id}/check-in` | Check-in QR/manual | `pmb_exams.attendance` (A) | `participant_token | participant_id, status` | 200 | 422 token bukan sesi ini |
| GET | `/pmb/admin/exam-sessions/{id}/attendance-sheet` | Daftar hadir PDF | `pmb_exams.attendance` (A) | — | 200 `{url}` | — |
| GET | `/pmb/admin/examiner/sessions` | Sesi saya | `pmb_scores.input` | — | 200 | — |
| PUT | `/pmb/admin/exam-sessions/{id}/scores` | Input nilai peserta sesi | `pmb_scores.input` (A) | `scores: [{participant_id, raw_score?, rubric_scores?, passed?, note?}]` | 200 | 423 `SCORES_LOCKED`; 422 rentang |
| POST | `/pmb/admin/score-imports` | Unggah & validasi impor | `pmb_scores.import` | multipart `file, selection_component_id, schedule_id` | 202 → hasil validasi `{valid_rows, errors[]}` | 422 |
| POST | `/pmb/admin/score-imports/{id}/commit` | Terapkan impor (atomik) | `pmb_scores.import` | — | 200 | 409 data berubah sejak validasi |
| POST | `/pmb/admin/exam-sessions/{id}/lock-scores` | Kunci nilai sesi | `pmb_scores.lock` | — | 200 | 422 masih ada peserta hadir tanpa nilai |
| GET | `/pmb/applicant/applications/{id}/exams` | Jadwal ujian saya | Pendaftar | — | 200 | — |
| GET | `/pmb/applicant/exam-participants/{id}/card` | Kartu peserta | Pendaftar | — | 200 `{url}` | 409 belum terbit |
| POST | `/pmb/applicant/exam-participants/{id}/reschedule-requests` | Ajukan jadwal ulang | Pendaftar | `reason, attachment?` | 201 | 422 batas reschedule |

## A.11 Selection

| Method | URL | Tujuan | Permission | Body | Sukses | Error khas |
|---|---|---|---|---|---|---|
| GET | `/pmb/admin/wave-paths/{id}/scores` | Matriks nilai | `pmb_selection.manage` | PAG, filter komponen/kelengkapan | 200 | — |
| PATCH | `/pmb/admin/selection-scores/{id}` | Koreksi nilai belum terkunci | `pmb_selection.manage` | `raw_score|passed, reason` | 200 | 423 `SCORES_LOCKED` |
| POST | `/pmb/admin/score-corrections` | Ajukan koreksi nilai terkunci | `pmb_results.correct` | `selection_score_id, new_value, reason, evidence_file_id?` | 201 (approval dibuat) | — |
| POST | `/pmb/admin/selection-runs` | Jalankan perhitungan | `pmb_selection.manage` | `wave_path_id` | 202 `{run_id, status: draft}` | 422 `SCHEME_NOT_LOCKED`, `errors.incomplete_scores` |
| GET | `/pmb/admin/selection-runs` | Daftar run | `pmb_results.read_unpublished` | PAG | 200 | — |
| GET | `/pmb/admin/selection-runs/{id}` | Ringkasan run | `pmb_results.read_unpublished` | — | 200 | — |
| GET | `/pmb/admin/selection-runs/{id}/results` | Hasil per aplikasi | `pmb_results.read_unpublished` | `filter[recommendation], filter[quota_id], sort=rank_global` PAG | 200 | — |
| POST | `/pmb/admin/selection-runs/{id}/review` | Mulai review | `pmb_selection.manage` | — | 200 | 422 transisi |
| PATCH | `/pmb/admin/selection-run-results/{id}/override` | Override individual | `pmb_selection.manage` | `recommendation, quota_id?, reason` | 200 | 422 bukan `under_review`; 409 `QUOTA_FULL` |
| POST | `/pmb/admin/selection-runs/{id}/resolve-ties` | Urutan manual untuk seri | `pmb_selection.manage` | `groups: [{application_ids_ordered[], reason}]` | 200 | 422 |
| POST | `/pmb/admin/selection-runs/{id}/submit` | Ajukan pengesahan | `pmb_selection.submit` | `note` | 200 `{approval_request_id}` | 422 ties belum selesai |
| GET | `/pmb/admin/selection-runs/{id}/export` | Ekspor hasil | `pmb_reports.export` | `format, reason` | 202 | — |

Pengesahan memakai endpoint approval engine yang sudah ada: `POST /api/v1/approval-request-steps/{step}/approve|reject|return|delegate` dan `GET /api/v1/approval-requests[/{id}]`. Listener `ApprovalCompleted` untuk `requestable_type = pmb_selection_run` mengubah run menjadi `approved`/`under_review`/`rejected`.

## A.12 Announcement

| Method | URL | Tujuan | Role | Body | Sukses | Error khas |
|---|---|---|---|---|---|---|
| GET | `/pmb/admin/announcement-batches` | Daftar batch | `pmb_results.read_unpublished` | PAG | 200 | — |
| PATCH | `/pmb/admin/announcement-batches/{id}` | Undur jadwal publikasi | `pmb_config.manage` | `scheduled_publish_at (≥ semula), reason` | 200 | 422 memajukan jadwal → perlu approval |
| POST | `/pmb/admin/announcement-batches/{id}/cancel` | Batalkan publikasi terjadwal | `pmb_config.manage` | `reason` | 200 | 422 sudah terbit |
| GET | `/pmb/admin/decisions` | Daftar keputusan | `pmb_applications.read` (+ unpublished: `pmb_results.read_unpublished`) | PAG | 200 | — |
| POST | `/pmb/admin/result-corrections` | Ajukan koreksi hasil terbit | `pmb_results.correct` | `application_ids[], reason, evidence_file_id?, proposed{}` | 201 | — |
| POST | `/pmb/admin/quotas/{id}/promote-waitlist` | Promosikan cadangan berikutnya | `pmb_waitlist.promote` | `count (default 1), deadline_at?` | 200 `{promoted[]}` | 409 `QUOTA_FULL`; 404 cadangan habis | **[IDEM]** |
| GET | `/pmb/applicant/applications/{id}/result` | Hasil saya | Pendaftar | — | 200 (404 sebelum publikasi — sama dengan "belum ada hasil") | — |
| GET | `/pmb/applicant/applications/{id}/loa` | LoA | Pendaftar | — | 200 `{url}` | 409 belum terbit |
| POST | `/pmb/public/status-check` | Cek status/hasil publik | Publik | `registration_number, birth_date, captcha_token` | 200 `{stage, decision_summary?}` | 404 generik; 429 |
| GET | `/pmb/public/loa/{verificationCode}` | Verifikasi keaslian LoA | Publik | — | 200 `{valid, name_masked, program, issued_at, revoked}` | 404 |

## A.13 Re-registration

| Method | URL | Tujuan | Role | Body | Sukses | Error khas |
|---|---|---|---|---|---|---|
| GET | `/pmb/applicant/applications/{id}/re-registration` | Status, checklist, tagihan | Pendaftar | — | 200 | 404 tidak ada |
| POST | `/pmb/applicant/applications/{id}/re-registration/accept` | Terima tawaran | Pendaftar | `declarations[], lock_version` | 200 `{status: confirmed, withdrawn_application_ids[]}` | 422 `DEADLINE_PASSED`; 409 | **[IDEM]** |
| POST | `/pmb/applicant/applications/{id}/re-registration/decline` | Tolak tawaran | Pendaftar | `reason?, confirm: true` | 200 | 422 | **[IDEM]** |
| PUT | `/pmb/applicant/applications/{id}/re-registration/data` | Data lanjutan (PDDikti) | Pendaftar | Field seksi `re_registration`, `lock_version` | 200 | 423 |
| POST | `/pmb/applicant/applications/{id}/re-registration/submit` | Ajukan validasi | Pendaftar | `lock_version` | 200 | 422 `errors.unmet_checks[]` |
| GET | `/pmb/applicant/applications/{id}/re-registration/proof` | Bukti registrasi | Pendaftar | — | 200 `{url}` | 409 belum enrolled |
| GET | `/pmb/admin/re-registrations` | Daftar | `pmb_reregistration.read` | filter status/prodi/deadline PAG | 200 | — |
| GET | `/pmb/admin/re-registrations/{id}` | Detail + checklist | `pmb_reregistration.read` | — | 200 | — |
| POST | `/pmb/admin/re-registrations/{id}/checks/{code}` | Tandai checklist manual | `pmb_reregistration.decide` | `status, note?` | 200 | 422 check otomatis |
| POST | `/pmb/admin/re-registrations/{id}/request-revision` | Minta perbaikan | `pmb_reregistration.decide` | `targets, note_for_applicant` | 200 | 422 |
| POST | `/pmb/admin/re-registrations/{id}/approve` | Persetujuan final | `pmb_reregistration.decide` | `lock_version` | 202 `{conversion_id}` | 422 `errors.unmet` (BR-33); 403 SoD | **[IDEM]** |
| POST | `/pmb/admin/re-registrations/{id}/extend` | Perpanjang batas | `pmb_reregistration.extend` | `extended_until, reason` | 200 | 422 melebihi maks |
| POST | `/pmb/admin/re-registrations/{id}/cancel` | Ajukan pembatalan | `pmb_results.correct` | `reason, evidence_file_id` | 201 (approval) | 422 sudah enrolled |
| POST | `/pmb/admin/re-registrations/{id}/restore` | Pulihkan declined/expired | `pmb_reregistration.extend` (+ `pmb_payments.reconcile` untuk `LATE_CALLBACK`) | `reason_code, note` | 200 | 409 kursi sudah dipakai |
| POST | `/pmb/admin/re-registrations/bulk-extensions` | Perpanjangan massal | `pmb_reregistration.extend` | `filter{}, extended_until, reason` | 201 (approval) | — |

## A.14 Student integration

| Method | URL | Tujuan | Permission | Body | Sukses | Error khas |
|---|---|---|---|---|---|---|
| GET | `/pmb/admin/conversions` | Antrean konversi | `pmb_conversion.read` | filter status PAG | 200 | — |
| GET | `/pmb/admin/conversions/{id}` | Detail, langkah, error | `pmb_conversion.read` | — | 200 | — |
| POST | `/pmb/admin/conversions/{id}/retry` | Coba lagi | `pmb_conversion.retry` | — | 202 | 409 `CONVERSION_IN_PROGRESS`; 422 sudah succeeded |

Integrasi ke modul Academic & Finance dilakukan **in-process** (service class), bukan HTTP — lihat B.1/B.2.

## A.15 Reporting

| Method | URL | Tujuan | Permission | Parameter | Sukses |
|---|---|---|---|---|---|
| GET | `/pmb/admin/dashboard/summary` | KPI dashboard | `pmb_reports.read` | `period_id, wave_id?, admission_path_id?, study_program_id?` | 200 (cache ≤ 5 menit) |
| GET | `/pmb/admin/dashboard/trend` | Tren harian | `pmb_reports.read` | `period_id, metric[], from, to` | 200 |
| GET | `/pmb/admin/dashboard/comparison` | Perbandingan antarperiode | `pmb_reports.read` | `period_ids[], metric, relative_day_max` | 200 |
| GET | `/pmb/admin/reports/{code}` | Laporan tabel (`applicants`, `payments`, `info-sources`, `selection`, `re-registration`, `finance`, `quota`, `pddikti-completeness`) | `pmb_reports.read` (+ scope) | filter sesuai laporan, PAG | 200 |
| POST | `/pmb/admin/reports/{code}/exports` | Ekspor | `pmb_reports.export` | `format (xlsx/csv/pdf), filters, columns[], reason` | 202 `{export_id}` |
| GET | `/pmb/admin/exports/{id}` | Status & tautan unduh | Pemilik ekspor | — | 200 `{status, url?, expires_at}` |

## A.16 Notification & communication

| Method | URL | Tujuan | Permission | Body | Sukses |
|---|---|---|---|---|---|
| GET | `/pmb/admin/notification-templates` | Template `pmb.*` tenant | `pmb_config.manage` | — | 200 |
| PUT | `/pmb/admin/notification-templates/{id}` | Ubah template | `pmb_config.manage` | `subject, body_template, is_active` (placeholder divalidasi) | 200 |
| POST | `/pmb/admin/notification-templates/{id}/test` | Kirim uji ke diri sendiri | `pmb_config.manage` | — | 202 |
| GET | `/pmb/admin/notification-logs` | Riwayat pengiriman | `pmb_audit.read` / `pmb_config.manage` | filter event, channel, status PAG | 200 |
| GET/POST | `/pmb/admin/broadcasts` | Daftar / buat broadcast | `pmb_communication.broadcast` | `title, channel, audience_filter, subject, body` | 200 / 201 |
| POST | `/pmb/admin/broadcasts/{id}/preview-audience` | Hitung penerima | idem | — | 200 `{count}` |
| POST | `/pmb/admin/broadcasts/{id}/schedule` | Jadwalkan/kirim | idem | `scheduled_at?` | 200 |
| POST | `/pmb/admin/broadcasts/{id}/cancel` | Batalkan | idem | — | 200 |
| GET/POST | `/pmb/applicant/chat-threads` | Daftar / buat percakapan | Pendaftar | `subject, application_id?, message` | 200 / 201 |
| GET/POST | `/pmb/applicant/chat-threads/{id}/messages` | Pesan | Pendaftar | `body, attachment?` | 200 / 201 |
| GET | `/pmb/admin/chat-threads` | Kotak masuk panitia | `pmb_communication.chat` | filter status/assignee PAG | 200 |
| POST | `/pmb/admin/chat-threads/{id}/messages` | Balas | `pmb_communication.chat` | `body, attachment?` | 201 |
| PATCH | `/pmb/admin/chat-threads/{id}` | Tugaskan / tutup | `pmb_communication.chat` | `assigned_user_id?, status?` | 200 |

## A.17 Administration & publik

| Method | URL | Tujuan | Permission |
|---|---|---|---|
| GET | `/pmb/admin/audit-logs` | Audit log PMB (filter auditable, actor, action, tanggal) | `pmb_audit.read` |
| GET | `/pmb/admin/sensitive-access-logs` | Log akses data sensitif | `pmb_audit.read` |
| GET | `/pmb/admin/export-logs` | Log ekspor | `pmb_audit.read` |
| GET | `/pmb/admin/applications/{id}/timeline` | Gabungan history status + audit per aplikasi | `pmb_applications.read` |
| GET | `/pmb/public/site` | Branding, menu, kontak, legal aktif | Publik |
| GET | `/pmb/public/periods/active` | Periode, gelombang, linimasa | Publik |
| GET | `/pmb/public/programs`, `/pmb/public/programs/{slug}` | Prodi | Publik |
| GET | `/pmb/public/paths`, `/pmb/public/paths/{code}` | Jalur & persyaratan | Publik |
| GET | `/pmb/public/fees` | Biaya terpublikasi (filter prodi/sistem) | Publik |
| GET | `/pmb/public/contents?type=` , `/pmb/public/contents/{type}/{slug}` | CMS | Publik |
| GET | `/pmb/public/sitemap.xml` | Sitemap per tenant | Publik |
| POST | `/pmb/integrations/leads` | Terima lead CRM/Maukuliah (Phase 3, perlu verifikasi format) | Token klien `pmb:integration:leads` |
| POST | `/pmb/webhooks/cbt/{provider}` | Terima nilai CBT (Phase 2) | Tanda tangan |

## A.18 Spesifikasi rinci endpoint kritis

### A.18.1 `POST /pmb/applicant/applications/{id}/submit`

**Header:** `Authorization: Bearer <token>`, `Idempotency-Key: 01J9...`

**Request:**
```json
{ "declarations": ["01J9DECL...A", "01J9DECL...B", "01J9DECL...C"], "lock_version": 7 }
```

**Validasi server (urutan):** kepemilikan → `form_status ∈ {draft, revision_requested}` → wave path `is_open` & `now < registration_closes_at` (kecuali `revision_requested`: `now < form_lock_at`) → seluruh field wajib (inti + dinamis terlihat) → pilihan prodi valid → dokumen wajib tahap `application` berstatus ≥ `uploaded` & scan `clean` → deklarasi = versi aktif → `payment_timing = before_submit` ⇒ tagihan `paid` → `lock_version` cocok.

**Sukses 200:**
```json
{
  "success": true,
  "message": "Formulir berhasil dikirim.",
  "data": {
    "id": "01J9APP...",
    "registration_number": "PMB2027-REG-000123",
    "form_status": "submitted",
    "verification_status": "not_started",
    "current_stage": "under_verification",
    "submitted_at": "2027-03-02T03:15:22Z",
    "lock_version": 8
  },
  "meta": {}
}
```

**Error 422 (tidak lengkap):**
```json
{
  "success": false,
  "message": "Formulir belum lengkap.",
  "errors": {
    "code": "INCOMPLETE_APPLICATION",
    "missing": [
      { "step": "education", "field": "graduation_year" },
      { "step": "documents", "document_type": "REPORT_CARD" }
    ]
  }
}
```

**Lainnya:** 409 `PAYMENT_REQUIRED` (`errors.invoice_id`), 409 `VERSION_CONFLICT`, 422 `WAVE_CLOSED`. **Efek:** transaksi tunggal (status, nomor, snapshot, consent, history, audit, outbox `ApplicationSubmitted`). **Audit:** history `form: draft→submitted`.

### A.18.2 `POST /pmb/applicant/invoices/{id}/transactions`

**Request:** `{ "channel": "va_bni" }` + `Idempotency-Key`.

**Proses:** lock tagihan → status ∈ {unpaid, pending, partially_paid} & `now < due_at` → batalkan transaksi `pending` lain pada kanal sama (BR-16) → buat transaksi `created` → panggil adapter (timeout 10 detik) → `pending` + detail.

**Sukses 201:**
```json
{
  "success": true,
  "message": "Instruksi pembayaran dibuat.",
  "data": {
    "transaction": {
      "id": "01J9TRX...",
      "status": "pending",
      "channel": "va_bni",
      "amount": "250000.00",
      "va_number": "8808123456789012",
      "expires_at": "2027-03-03T03:20:00Z",
      "instructions": [{ "title": "ATM BNI", "steps": ["..."] }]
    },
    "invoice": { "id": "01J9INV...", "status": "pending", "amount_net": "250000.00" }
  },
  "meta": {}
}
```

**Error:** 409 `INVOICE_NOT_PAYABLE` (paid/expired/cancelled), 503 `PAYMENT_PROVIDER_UNAVAILABLE` (transaksi ditandai `failed`, aman untuk diulang dengan kunci idempotensi baru).

### A.18.3 `POST /pmb/webhooks/payments/{provider}`

**Request:** payload penyedia apa adanya; header tanda tangan penyedia (mis. `X-Signature`, `X-Timestamp`).

**Respons:** `200 {"success": true, "message": "accepted"}` untuk event valid baru maupun duplikat; `401` tanda tangan tidak valid. Tidak pernah mengembalikan detail internal. Proses lanjut asinkron ([05 §F.4](05-spesifikasi-pembayaran-dan-verifikasi.md#f4-penerimaan-callback-webhook--alur-aman)).

### A.18.4 `POST /pmb/admin/applications/{id}/verify`

**Request:** `{ "lock_version": 12, "note": "Lengkap" }`

**Validasi:** permission + scope + klaim aktif milik pemanggil (atau `pmb_admin`) → `verification_status = in_review` → BR-22.

**Error 422 contoh:**
```json
{
  "success": false,
  "message": "Aplikasi belum memenuhi syarat verifikasi.",
  "errors": { "code": "INVALID_TRANSITION", "unmet": ["DOCUMENT_NOT_APPROVED:REPORT_CARD", "OPEN_DUPLICATE_FLAG"] }
}
```

**Efek:** `verification_status=verified`, `verified_at`, `form_status=locked`, `selection_status=eligible`, dokumen dikunci, penugasan `completed`, history, audit, outbox `ApplicationVerified` (notifikasi).

### A.18.5 `POST /pmb/admin/selection-runs`

**Request:** `{ "wave_path_id": "01J9WP..." }`

**Validasi:** skema wave path `locked`; semua aplikasi `eligible`/`scheduled` memiliki nilai komponen wajib atau berstatus `absent`/`disqualified` (jika tidak → 422 dengan `errors.incomplete_scores: [{application_id, component}]`, maks 50 contoh + jumlah); nilai sesi sudah `scores_locked_at`; tidak ada run `pending_approval`/`approved` belum terbit untuk wave path yang sama.

**Sukses 202:** `{ "run_id": "01J9RUN...", "status": "draft" }`. Job menghitung → `computed`. Klien memantau `GET /selection-runs/{id}`.

### A.18.6 `POST /pmb/admin/re-registrations/{id}/approve`

**Request:** `{ "lock_version": 4 }` + `Idempotency-Key`.

**Validasi:** permission; status `submitted`; seluruh checklist `passed`/`not_applicable`; tagihan biaya awal memenuhi minimum; SoD (pemanggil bukan verifikator bukti bayar manual untuk tagihan ini).

**Sukses 202:**
```json
{
  "success": true,
  "message": "Daftar ulang disetujui. Pembentukan data mahasiswa sedang diproses.",
  "data": { "re_registration_status": "approved", "conversion": { "id": "01J9CNV...", "status": "pending" } },
  "meta": {}
}
```

### A.18.7 `POST /pmb/public/status-check`

**Request:** `{ "registration_number": "PMB2027-REG-000123", "birth_date": "2008-05-17", "captcha_token": "..." }`

**Sukses 200:** `{ "stage": "result_available", "stage_label": "Hasil seleksi tersedia", "decision_summary": { "decision": "accepted", "program": "S1 Informatika", "study_system": "Reguler" } }` — `decision_summary` hanya bila keputusan `published`. Kombinasi salah → **404 dengan pesan generik** "Data tidak ditemukan." (waktu respons dinormalkan untuk menghindari *timing attack*).

---

# B. Strategi Integrasi

Setiap integrasi eksternal diimplementasikan sebagai **adapter** di balik interface di `app/Modules/Admission/Integrations/*`, dengan konfigurasi per tenant di `university_settings` (secret disimpan terenkripsi atau di secret manager, bukan di DB polos).

| ID | Kebutuhan integrasi | Bagian | Label | Fase |
|---|---|---|---|---|
| FR-INT-01 | Ekspor pendaftar menjadi mahasiswa ke modul Akademik | B.1 | E (SRC-PMB-10) | MVP |
| FR-INT-02 | Posting tagihan & pembayaran ke modul Keuangan | B.2 | I (SRC-KEU-*) | MVP |
| FR-INT-03 | Integrasi CRM / Maukuliah (penerimaan lead, atribusi) | B.7 | E (SRC-PMB-14) | Phase 3 |
| FR-INT-04 | Integrasi payment gateway | B.3 | E (SRC-PMB-12) | MVP |
| FR-INT-05 | Integrasi CBT | B.5 | E (SRC-PMB-18, SRC-CBT-01) | Phase 2 |
| FR-INT-06 | Kesiapan data PDDikti, TTE LoA, SIMPONI/EMIS kondisional | B.6, B.8 | I (SRC-NEO-01, SRC-TTE-01, SRC-SIM-01, SRC-EMIS-01) | Phase 3 |

## B.1 Sistem akademik (modul Academic CivitasOne)

| Aspek | Rancangan |
|---|---|
| Arah | PMB → Academic |
| Mekanisme | Panggilan service in-process `Modules\Academic\Services\AdmissionEnrollmentService::createFromAdmission(AdmissionEnrollmentData $dto)` (kontrak baru di modul Academic). Modul Admission tidak menulis langsung ke tabel `students` |
| Data | Identitas, prodi, `admission_year`, `enrolled_at`, NIM, email, HP, alamat, foto (file dokumen `PHOTO`), field PDDikti, `admission_application_id`, kode jalur & sistem kuliah, data orang tua (untuk fitur "login orang tua" bila ada) |
| Konsistensi | Transaksi DB yang sama dengan penomoran NIM (K.6); outbox untuk langkah lanjutan |
| Idempotensi | `students.admission_application_id` unique — pemanggilan ulang mengembalikan mahasiswa yang sudah ada |
| Kegagalan | [07 §K.7](07-spesifikasi-daftar-ulang-jalur-khusus-pelaporan.md#k7-pemulihan-saat-integrasi-akademik-gagal) |
| Akun mahasiswa | Membuat `users` + `user_universities` + role `student` (role template yang ada di `OrganizationalRoleSeeder`) + `students.user_id`; `must_change_password = true` (kolom yang ada) dan tautan aktivasi dikirim; BR-38 untuk email yang sudah ada |

## B.2 Sistem keuangan (modul Finance CivitasOne)

| Aspek | Rancangan |
|---|---|
| Arah | PMB → Finance |
| Kapan | Setelah mahasiswa terbentuk (langkah `finance_posted`) |
| Data | Tagihan `initial_tuition` (dan opsional `registration_fee` sebagai histori pendapatan [K]) → `invoices` (`student_id`, `period` = kode term masuk, `amount`, `paid_amount`, `status`, `due_date`, `source_type='pmb_invoice'`, `source_id`) + `payments` per transaksi sukses (`reference`, `channel`) |
| Sisa cicilan | Termin cicilan yang belum jatuh tempo diposting sebagai `invoices` terpisah dengan `due_date` masing-masing sehingga cekal keuangan SIAKAD (bila ada) bekerja di modul Finance |
| Idempotensi | Unique (`source_type`, `source_id`) |
| Rekonsiliasi | Laporan selisih PMB vs Finance per periode (FR-RPT-07) |

## B.3 Payment gateway

| Aspek | Rancangan |
|---|---|
| Kontrak | `PaymentGatewayContract`: `createCharge(Invoice, channel, amount, idempotencyKey)`, `getStatus(providerReference)`, `cancel(providerReference)`, `refund(providerReference, amount)` (opsional), `verifyWebhook(Request): WebhookEvent`, `fetchSettlement(date)` |
| Kanal | VA bank, QRIS, e-wallet/e-commerce sesuai penyedia & paket (BR-17) |
| Keamanan | Secret per tenant; verifikasi tanda tangan; double-check status; allowlist IP bila tersedia; TLS 1.2+; payload disimpan tanpa data kartu (tidak ada kartu kredit di MVP → di luar cakupan PCI DSS penuh) |
| Ketersediaan | Timeout 10 detik; circuit breaker (≥ 5 kegagalan/menit → kanal ditandai sementara tidak tersedia di UI); polling cadangan & rekonsiliasi harian |
| Perlu verifikasi | Format webhook, ketersediaan API status/settlement/refund, biaya admin, batas waktu VA — bergantung penyedia terpilih (Q-PAY-01) |

## B.4 Email dan SMS/OTP

| Aspek | Rancangan |
|---|---|
| Mekanisme | Modul Notification yang ada (`notification_templates`, `notification_logs`, channel config); pengiriman via queue |
| Email | Provider transaksional (SMTP/API) dengan domain pengirim tenant (SPF, DKIM, DMARC); bounce/complaint webhook memperbarui `notification_logs` |
| SMS/WA OTP | Adapter penyedia (Phase 2); rate limit per tujuan; template OTP tanpa data lain; WA memerlukan template yang disetujui penyedia |
| Kegagalan | Retry 3× eksponensial; kanal cadangan (OTP SMS gagal → tawarkan email) |

## B.5 Sistem ujian (CBT)

| Opsi | Rancangan | Catatan |
|---|---|---|
| Internal (rekomendasi jangka panjang) | Generalisasi `exams`: `class_section_id` → nullable + `examinee` polymorphic (`krs_item` / `pmb_exam_participant`), atau tabel ujian PMB yang memakai ulang `question_bank_items`, `exam_questions`, pengacakan, `exam_violations` | Refactor modul Academic; perlu analisis dampak terhadap test ujian yang ada (EXAM_MODULE.md, EXAM_RANDOMIZATION.md) |
| Eksternal | Kirim daftar peserta & token (API penyedia) → peserta mengerjakan di sistem CBT → nilai diterima via webhook `/pmb/webhooks/cbt/{provider}` atau tarik berkala → `pmb_selection_scores.source = cbt_sync` | Perlu verifikasi API penyedia |
| Proctoring | Hanya bila CBT mendukung; pelanggaran dicatat sebagai catatan & dapat memicu `disqualified` oleh panitia (bukan otomatis) | — |

## B.6 PDDikti Neo Feeder

| Aspek | Rancangan |
|---|---|
| Posisi | PMB tidak mengirim langsung ke Neo Feeder. Pelaporan mahasiswa baru dilakukan oleh integrasi akademik (di luar dokumen ini) |
| Peran PMB | Mengumpulkan & memvalidasi field yang dibutuhkan PDDikti saat daftar ulang (Q-REG-05), menyimpan biaya masuk (sumber data "biaya masuk" [E SRC-NEO-01]) yang dapat dibaca integrasi akademik, menyediakan KPI-19 kelengkapan data |
| Perlu verifikasi | Daftar field wajib & referensi kode (agama, wilayah, jenis tinggal, jalur masuk PDDikti) dari dokumentasi Neo Feeder versi berlaku |

## B.7 CRM / Maukuliah

| Aspek | Rancangan |
|---|---|
| Arah | Masuk (lead) dan keluar (status konversi, opsional) |
| Mekanisme | `POST /pmb/integrations/leads` menerima lead (nama, email, HP, minat prodi, sumber) → disimpan sebagai referensi (`crm_lead_reference`) saat lead mendaftar; event konversi (registered, paid, accepted, enrolled) dapat dikirim balik via webhook keluar [R] |
| Privasi | Lead dari pihak ketiga hanya dipakai bila ada dasar persetujuan yang sah dari sumbernya; PMB tidak mengirim email pemasaran ke lead tanpa consent `marketing_optional` |
| Perlu verifikasi | Format & autentikasi API CRM SEVIMA/Maukuliah tidak tersedia di sumber — integrasi hanya dapat dirancang setelah dokumentasi diperoleh (INK-01) |

## B.8 Layanan lain yang dibutuhkan

| Layanan | Kebutuhan | Fase |
|---|---|---|
| Antivirus (ClamAV atau layanan setara) | Pemindaian unggahan | MVP |
| CAPTCHA (mis. Turnstile/reCAPTCHA) | Registrasi, cek status, lupa sandi | MVP |
| Object storage S3-compatible | Dokumen privat | MVP |
| PDF renderer (mis. Browsershot/DomPDF) | Kartu ujian, LoA, kuitansi, bukti registrasi | MVP |
| TTE (penyelenggara sertifikasi elektronik berinduk) | Tanda tangan LoA/bukti registrasi | Phase 3, biaya terpisah [E SRC-TTE-01] |
| SIMPONI (PTN/kedinasan) | Kanal pembayaran PNBP | Phase 3, kondisional |
| EMIS (PTKI) | Pelaporan mahasiswa baru PTKI (via integrasi akademik) | Phase 3, kondisional |
| SIM KIP Kuliah | Verifikasi status KIP | Tidak diintegrasikan sampai akses resmi tersedia (B-08) |
