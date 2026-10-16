# 26 — Rancangan API dan Integrasi

> **Status:** rancangan · Disusun 2026-10-13 (commit `47043ca`) · Kembali ke [README](README.md)
> **Dokumen acuan endpoint.** Pola mengikuti kode yang ada: prefix `/api/v1` (`routes/api.php` memuat `app/Modules/*/Routes/api.php` dalam grup `tenant.resolve`), envelope `ApiResponse`, daftar dengan `ListQuery`, `ConflictException` → 409.

## Daftar Isi

1. [Konvensi](#1-konvensi)
2. [Otorisasi dan Middleware](#2-otorisasi-dan-middleware)
3. [Kemahasiswaan](#3-kemahasiswaan)
4. [Yudisium](#4-yudisium)
5. [Wisuda](#5-wisuda)
6. [Dokumen Resmi](#6-dokumen-resmi)
7. [SKPI](#7-skpi)
8. [Tracer Study](#8-tracer-study)
9. [Career Center](#9-career-center)
10. [Endpoint Publik dan Webhook](#10-endpoint-publik-dan-webhook)
11. [Kontrak In-Process dan Event](#11-kontrak-in-process-dan-event)
12. [Integrasi Antarmodul](#12-integrasi-antarmodul)
13. [Kode Error](#13-kode-error)

---

## 1. Konvensi

| Aspek | Ketentuan (Ada / Baru) |
|---|---|
| Base URL | `/api/v1` (Ada) |
| Envelope sukses | `{ "success": true, "message": "Berhasil.", "data": …, "meta": {…} }` (Ada, `ApiResponse::success`) |
| Envelope daftar | `meta: { current_page, per_page, total, last_page }` (Ada, `ApiResponse::paginated`) |
| Envelope galat | `{ "success": false, "message": "…", "errors": { "code": ["…"], "field": ["…"] } }` (Ada, `ApiResponse::error`). Rancangan menetapkan `errors.code[0]` sebagai kode mesin ([§13](#13-kode-error)) |
| Daftar | `?search=`, `?filter[col]=a,b` (whereIn), `?sort=-col`, `?per_page=` ≤ 100, default 20 (Ada, `ListQuery`). Setiap endpoint daftar mendeklarasikan kolom `searchable`/`filterable`/`sortable` |
| Penamaan resource | Jamak, kebab-case, sesuai nama tabel ([25](25-rancangan-database-dan-erd.md)): `activity-submissions`, `yudisium-registrations`, `issued_documents` → `official-documents` (pengecualian: resource generik dokumen) |
| Aksi non-CRUD | `POST /{resource}/{id}/{verb}` (`submit`, `approve`, `reject`, `publish`, `finalize`). Tidak ada `PATCH {status}` generik |
| Idempotensi | Header `Idempotency-Key` (ULID/UUID klien) **wajib** pada endpoint bertanda **[I]**. Disimpan di `idempotency_keys` (rancangan KEU). Kunci sama + payload sama → respons tersimpan. Kunci sama + payload berbeda → 409 `IDEMPOTENCY_KEY_REUSED` |
| Konkurensi | Entitas dengan `lock_version` menerima `lock_version` di body aksi. Tidak cocok → 409 `STALE_VERSION` |
| Waktu | ISO 8601 UTC (`toIso8601String()`, pola resource Ada). Tanggal murni `YYYY-MM-DD` |
| Bahasa pesan | Bahasa Indonesia (pola Ada) |
| Audit | Kolom **Audit** di tabel endpoint: `C/U/D` = `Auditable` otomatis, nama aksi = `AuditLogService::recordAction()` |

**Format tabel endpoint di bawah.** Setiap baris: Method · URL · Tujuan · Aktor · Permission · Fitur (entitlement) · [I] idempoten · Audit. Validasi, filter, dan error khusus dicantumkan di dokumen fitur yang ditautkan. Contoh request/response diberikan untuk endpoint utama tiap kelompok.

## 2. Otorisasi dan Middleware

Urutan lapis mengikuti [academic-learning/11 §2](../academic-learning/11-roles-permissions.md):

```php
// Usulan (Baru) — contoh grup Kemahasiswaan
Route::prefix('student-affairs')
    ->middleware(['auth:sanctum', 'tenant.access', 'feature:student_affairs.core'])
    ->group(function () {
        Route::get('activity-submissions', [ActivitySubmissionController::class, 'index'])
            ->middleware('permission:activity_submissions.read');
    });
```

| Lapis | Komponen | Status | Gagal |
|---|---|---|---|
| Tenant | `tenant.resolve` (Ada), `tenant.access` (Ada: `EnsureUniversityAccessMiddleware`) | Ada — **wajib** dipasang di semua grup modul ini (saat ini hanya `/tenant` dan `/hr` yang memakainya) | 403 |
| Paket | `feature:` → `EnsureFeatureEntitled` (Baru, rancangan `academic-learning`) | Baru | 403 `not_in_plan` / `disabled` |
| Permission | `permission:` (Ada, `EnsurePermission`, OR) | Ada | 403 |
| Scope data | Policy + `AcademicScope` (Baru, rancangan `academic-learning`) untuk `f`/`p`/`w` | Baru | 404 (objek di luar scope) |
| Konteks perusahaan | `company.context` → `ResolveCompanyContext` (Baru): header `X-Company-ID` + keanggotaan `active` + perusahaan tidak `suspended` | Baru | 403 |
| Aturan bisnis | Service domain | — | 409 / 422 |

Rate limit (Laravel `throttle`): endpoint publik ([§10](#10-endpoint-publik-dan-webhook)), layanan mandiri yang memicu pemrosesan berat (`/student/graduation/eligibility/recheck`), dan API perusahaan (`throttle:120,1` per pengguna).

## 3. Kemahasiswaan

Prefix staf `/student-affairs`, layanan mandiri `/student/affairs`. Fitur: `student_affairs.core`.

| Method | URL | Tujuan | Aktor | Permission | [I] | Audit |
|---|---|---|---|---|---|---|
| GET | `/student-affairs/activity-categories` | Daftar kategori | Staf | `activity_categories.read` | | |
| POST | `/student-affairs/activity-categories` | Buat | Admin | `activity_categories.create` | | C |
| PUT | `/student-affairs/activity-categories/{category}` | Ubah | Admin | `activity_categories.update` | | U |
| POST | `/student-affairs/activity-categories/{category}/publish` | Aktifkan | Admin | `activity_categories.update` | | U |
| DELETE | `/student-affairs/activity-categories/{category}` | Hapus draf | Admin | `activity_categories.delete` | | D |
| GET/POST | `/student-affairs/activity-categories/{category}/point-rules` | Aturan poin | Admin | `.read`/`.update` | | C |
| GET | `/student-affairs/activity-submissions` | Antrean verifikasi | Petugas | `activity_submissions.read` | | |
| GET | `/student-affairs/activity-submissions/{submission}` | Detail + flag + riwayat | Petugas | `activity_submissions.read` | | `viewed_sensitive` bila bukti dibuka |
| POST | `…/{submission}/start-review` | Ambil | Petugas | `activity_submissions.approve` | | |
| POST | `…/{submission}/approve` | Setujui + poin | Petugas | `activity_submissions.approve` | ✓ | `approved` |
| POST | `…/{submission}/request-revision` | Kembalikan | Petugas | `activity_submissions.approve` | | `returned` |
| POST | `…/{submission}/reject` | Tolak | Petugas | `activity_submissions.reject` | | `rejected` |
| POST | `…/{submission}/revoke` | Cabut | Manager | `activity_submissions.update` | ✓ | `forced_override` |
| GET/POST | `/student-affairs/activity-events` | Kegiatan terdaftar | Petugas/penyelenggara | `activity_events.read/create` | | C |
| POST | `/student-affairs/activity-events/{event}/participants/import` | Impor peserta | Petugas | `activity_events.update` | ✓ | `imported` |
| POST | `/student-affairs/activity-events/{event}/verify` | Verifikasi event | Manager | `activity_events.approve` | | `verified` |
| GET | `/student-affairs/students/{student}/points` | Poin mahasiswa | Staf/dosen wali | `student_points.read` | | |
| POST | `/student-affairs/students/{student}/point-corrections` | Koreksi | Petugas | `student_points.update` | ✓ | `updated` |
| GET | `/student-affairs/point-appeals` | Banding | Petugas | `point_appeals.read` | | |
| POST | `/student-affairs/point-appeals/{appeal}/decide` | Putus banding | Manager | `point_appeals.approve` | | `approved`/`rejected` |
| POST/GET | `/student-affairs/violation-reports` | Laporan | Pelapor/petugas | `violation_reports.create/read` | | C |
| POST | `/student-affairs/violation-reports/{report}/screen` | Saring | Petugas disiplin | `violation_reports.update` | | U |
| GET | `/student-affairs/violation-cases[/{case}]` | Kasus | Petugas disiplin | `violation_cases.read` | | `viewed_sensitive` |
| POST | `/student-affairs/violation-cases/{case}/hearings`, `/findings` | Pemeriksaan & temuan | Petugas | `violation_cases.update` | | C/U |
| POST | `/student-affairs/violation-cases/{case}/decisions` | Draf keputusan | Petugas | `disciplinary_decisions.create` | | C |
| POST | `/student-affairs/disciplinary-decisions/{decision}/submit` | Ajukan approval | Petugas | `disciplinary_decisions.create` | ✓ | |
| POST | `/student-affairs/sanction-appeals/{appeal}/decide` | Putus keberatan | Pejabat | `sanction_appeals.approve` | ✓ | `approved` |
| GET/POST/PUT | `/scholarships[/{scholarship}]` | Program (GET Ada) | Petugas | `scholarships.read/create/update` | | C/U |
| POST | `/scholarships/{scholarship}/open`, `/close` | Buka/tutup | Petugas | `scholarships.publish` | | U |
| GET | `/scholarship-applications` (Ada) | Pendaftar | Petugas | `scholarship_applications.read` (transisi: juga `scholarships.read`) | | |
| POST | `/scholarship-applications/{application}/verify-documents`, `/reviews` | Seleksi | Petugas/panitia | `scholarship_applications.update` | | U |
| POST | `/scholarships/{scholarship}/award-batches` | Usul penerima | Petugas | `scholarship_awards.create` | ✓ | C |
| GET | `/scholarship-awards` | Penerima | Staf, Keuangan | `scholarship_awards.read` | | |
| POST | `/scholarship-awards/{award}/suspend`, `/resume`, `/terminate`, `/extend` | Perubahan (via approval) | Petugas | `scholarship_awards.update` | ✓ | U |
| GET | `/student/affairs/points` | Poin saya | Mahasiswa | `student_affairs_self_service.read` | | |
| GET/POST/PUT | `/student/affairs/activity-submissions[/{submission}]` | Pengajuan saya | Mahasiswa | `student_affairs_self_service.*` | | C/U |
| POST | `/student/affairs/activity-submissions/{submission}/submit`, `/withdraw`, `/appeal` | Aksi | Mahasiswa | `.update`/`.create` | ✓ (submit) | |
| GET | `/student/affairs/discipline` ; POST `…/disciplinary-decisions/{decision}/appeal`, `…/violation-cases/{case}/statement` | Kedisiplinan saya | Mahasiswa | `student_affairs_self_service.*` | | C |
| GET | `/student/affairs/scholarships` ; POST `…/{scholarship}/applications` ; POST `/student/affairs/scholarship-awards/{award}/accept`, `/decline` | Beasiswa saya | Mahasiswa | `student_affairs_self_service.*` | | C/U |
| — | Surat aktif: `/student/requests` (Ada) | Lihat [09 §13](09-rancangan-surat-keterangan-aktif.md#13-kontrak-api-konseptual) | | | | |

**Contoh — `POST /student-affairs/activity-submissions/{submission}/approve`**

```http
POST /api/v1/student-affairs/activity-submissions/01JA…/approve
Idempotency-Key: 01JB…
X-University-ID: 01J9…

{ "awarded_points": "42.00", "reason": "Juara 1 tingkat nasional dengan 120 peserta", "lock_version": 3 }
```

```json
{ "success": true, "message": "Pengajuan disetujui.",
  "data": { "id": "01JA…", "status": "approved", "awarded_points": "42.00", "counted_points": "42.00",
            "ledger_entry_id": "01JC…", "requires_approval": false }, "meta": {} }
```

Galat: 409 `INVALID_STATUS_TRANSITION`, 409 `STALE_VERSION`, 409 `OVERRIDE_REQUIRES_APPROVAL` (poin di luar rentang; respons memuat `approval_request_id` yang dibuat), 403 `SELF_APPROVAL_FORBIDDEN`.

## 4. Yudisium

Prefix staf `/graduation`, mandiri `/student/graduation`. Fitur: `graduation.core`.

| Method | URL | Tujuan | Aktor | Permission | [I] | Audit |
|---|---|---|---|---|---|---|
| GET/POST/PUT | `/graduation/yudisium-periods[/{period}]` | Periode | BAAK | `yudisium_periods.*` | | C/U |
| POST | `/graduation/yudisium-periods/{period}/open`, `/close`, `/reopen`, `/cancel` | Status | BAAK | `yudisium_periods.publish` | | U |
| POST | `/graduation/yudisium-periods/{period}/finalize` | Finalisasi | BAAK | `yudisium_periods.finalize` | ✓ | U |
| GET | `/graduation/requirement-checkers` | Katalog checker | Staf | `graduation_requirement_sets.read` | | |
| GET/POST | `/graduation/requirement-sets` ; POST `/{set}/versions` ; PUT `/graduation/requirement-set-versions/{version}` | Aturan | BAAK/fakultas/prodi | `graduation_requirement_sets.read/create/update` | | C/U |
| POST | `/graduation/requirement-set-versions/{version}/simulate` | Simulasi | BAAK | `graduation_requirement_sets.update` | | |
| POST | `/graduation/requirement-set-versions/{version}/publish` | Publikasi | Pejabat | `graduation_requirement_sets.publish` | | `approved` |
| POST | `/graduation/eligibility-checks` | Periksa satu | Staf | `graduation_eligibility.create` | | |
| POST | `/graduation/yudisium-periods/{period}/eligibility-batches` ; GET `/graduation/eligibility-batches/{batch}` | Periksa massal | Staf | `graduation_eligibility.create/read` | ✓ | |
| GET | `/graduation/yudisium-periods/{period}/candidates` | Calon peserta | Staf | `graduation_eligibility.read` | | |
| GET | `/graduation/students/{student}/eligibility[/history]` | Hasil | Staf/dosen | `graduation_eligibility.read` | | |
| POST | `/graduation/eligibility-check-items/{item}/resolve` | Item manual | Staf | `graduation_eligibility.create` | | `verified` |
| POST | `/graduation/requirement-exemptions` | Pengecualian | Staf → pejabat | `graduation_requirement_exemptions.create` | ✓ | C |
| GET/POST | `/graduation/yudisium-registrations` | Pendaftar / daftarkan | Staf | `yudisium_registrations.read/create` | | C |
| POST | `/graduation/yudisium-registrations/{registration}/verify` | Verifikasi tahap | Prodi/fakultas/BAAK | `yudisium_registrations.approve` (+`.reject`) | ✓ | `approved`/`returned`/`rejected` |
| POST | `/graduation/yudisium-registrations/{registration}/documents/{document}/verify` | Verifikasi berkas | Staf | `yudisium_registrations.approve` | | `verified` |
| GET/POST/PUT | `/graduation/yudisium-meetings[/{meeting}]` | Rapat | BAAK | `yudisium_meetings.*` | | C/U |
| POST | `/graduation/yudisium-meetings/{meeting}/decisions` | Keputusan massal | BAAK | `yudisium_meetings.update` | | C |
| POST | `/graduation/yudisium-meetings/{meeting}/submit-for-approval` | Ajukan pengesahan | BAAK | `yudisium_meetings.update` | ✓ | |
| POST | `/graduation/decision-batches/{batch}/finalize` | Finalisasi | BAAK | `graduation_decisions.finalize` | ✓ | `approved` |
| GET | `/graduation/decisions[/{decision}]` | Keputusan | Staf | `graduation_decisions.read` | | |
| GET | `/graduation/decisions/export` | Ekspor lulusan | Staf | `graduation_decisions.export` | | `exported` |
| POST | `/graduation/decisions/{decision}/corrections` ; POST `/graduation/decision-corrections/{correction}/decide` | Koreksi/pembatalan | Staf / pejabat | `graduation_decision_corrections.create/approve` | ✓ | `forced_override` |
| GET/POST | `/graduation/honor-rules` ; POST `/{rule}/publish` | Predikat | BAAK | `graduation_honor_rules.*` | | C/U |
| GET | `/student/graduation/requirements`, `/eligibility` | Status saya | Mahasiswa | `graduation_self_service.read` | | |
| POST | `/student/graduation/eligibility/recheck` | Periksa ulang | Mahasiswa | `graduation_self_service.create` | | |
| GET/POST | `/student/graduation/registrations` ; POST `/{registration}/documents`, `/submit`, `/withdraw` | Pendaftaran saya | Mahasiswa | `graduation_self_service.*` | ✓ (submit) | C/U |

**Contoh — `GET /graduation/students/{student}/eligibility?period_id=`**: lihat [11 §8](11-rancangan-eligibilitas-yudisium.md#8-data-input-dan-output).

**Contoh — `POST /graduation/decision-batches/{batch}/finalize`**

```json
// 200
{ "success": true, "message": "Finalisasi selesai.",
  "data": { "batch_id": "01J…", "finalized": 118, "failed": 2,
            "failures": [ { "student_id": "01J…", "code": "STUDENT_STATUS_CHANGED", "message": "Status mahasiswa berubah menjadi cuti." } ],
            "decree_document_status": "queued" } }
```

## 5. Wisuda

| Method | URL | Tujuan | Aktor | Permission | [I] | Audit |
|---|---|---|---|---|---|---|
| GET/POST/PUT | `/graduation/ceremonies[/{ceremony}]` | Periode wisuda | Panitia | `graduation_ceremonies.*` | | C/U |
| POST | `/graduation/ceremonies/{ceremony}/open`, `/close`, `/finalize`, `/complete`, `/cancel` | Status | Panitia | `graduation_ceremonies.publish`/`.finalize` | | U |
| GET/POST/PUT | `/graduation/ceremonies/{ceremony}/sessions` | Sesi | Panitia | `ceremony_sessions.*` | | C/U |
| POST | `/graduation/ceremonies/{ceremony}/allocate-sessions` | Alokasi otomatis | Panitia | `ceremony_sessions.update` | ✓ | U |
| GET | `/graduation/ceremony-registrations` | Peserta | Panitia | `ceremony_registrations.read` | | |
| POST | `/graduation/ceremony-registrations/{registration}/verify`, `/reject` | Validasi | Panitia | `ceremony_registrations.approve/reject` | | `verified`/`rejected` |
| PUT | `/graduation/ceremony-registrations/{registration}/session` | Pindah sesi | Panitia | `ceremony_sessions.update` | | U |
| GET | `/graduation/ceremonies/{ceremony}/checkin-manifest` | Manifes luring | Petugas check-in | `ceremony_attendance.read` | | |
| POST | `/graduation/ceremony-checkins` | Check-in | Petugas | `ceremony_attendance.create` | ✓ | |
| POST | `/graduation/ceremony-checkins/sync` | Sinkron luring | Petugas | `ceremony_attendance.create` | ✓ | |
| GET/POST | `/graduation/ceremonies/{ceremony}/book-editions` | Buku | Panitia | `graduation_books.read/create` | | C |
| POST | `/graduation/book-editions/{edition}/open-review`, `/lock`, `/publish` | Siklus buku | Panitia/pejabat | `graduation_books.update/publish` | | U/`approved` |
| POST | `/graduation/book-entries/{entry}/corrections` | Koreksi tampilan | Prodi/lulusan | `graduation_book_corrections.create` / mandiri | | C |
| GET | `/graduation/ceremonies/{ceremony}/graduates/export` | Ekspor | Panitia | `ceremony_registrations.export` | | `exported` |
| GET/POST | `/student/graduation/ceremonies`, `/student/graduation/ceremony-registrations` | Daftar | Lulusan | `graduation_self_service.*` | ✓ | C |
| POST | `/student/graduation/ceremony-registrations/{registration}/confirm` | Konfirmasi | Lulusan | `graduation_self_service.update` | | U |
| GET | `/student/graduation/ceremony-registrations/{registration}/card` | Kartu PDF | Lulusan | `graduation_self_service.read` | | |

**Contoh — `POST /graduation/ceremony-checkins`**: body `{ "token": "…", "session_id": "01J…", "device_id": "tab-07" }` → `200 { "result": "checked_in", "graduate": { "name": "…", "study_program": "…", "seat": "C-112" } }` atau `200 { "result": "duplicate_scan", "first_checked_in_at": "…" }` atau 409 `WRONG_SESSION` / `NOT_CONFIRMED`, 404 `INVALID_TOKEN`.

## 6. Dokumen Resmi

Prefix `/official-documents`. Fitur: modul `document`. Endpoint di [13 §13](13-rancangan-penomoran-dokumen-kelulusan.md#13-kontrak-api-konseptual). Ringkasan audit: penerbitan `C` + `issued_document_events`, unduhan staf `downloaded`, pembatalan `approved`, impor PIN `imported`.

**Contoh — `POST /official-documents/issue`**

```json
// request
{ "document_type": "final_transcript", "source_type": "graduation_decision", "source_id": "01J…",
  "language": "id", "issuing_unit": { "type": "faculty", "id": "01J…" } }
// 201
{ "success": true, "data": { "id": "01J…", "status": "pending_signature", "official_number": "087/FTI/TRK/XI/2026",
  "verification_url": "https://…/verifikasi/K7Q2…", "file_sha256": "9f2c…" } }
```

Galat: 409 `NUMBERING_SCHEME_MISSING`, `TEMPLATE_MISSING`, `SIGNATORY_INACTIVE`, `DOCUMENT_ALREADY_ISSUED` (sumber sudah punya dokumen aktif; data dokumen terkait dikembalikan), 403 `DOCUMENT_TYPE_NOT_ASSIGNED`.

## 7. SKPI

Prefix `/skpi`, mandiri `/student/skpi`. Fitur `skpi.core`. Endpoint di [15 §13](15-rancangan-skpi.md#13-kontrak-api-konseptual). Audit: verifikasi `verified`, peninjauan terjemahan `verified`, persetujuan `approved`, penerbitan `C`.

## 8. Tracer Study

Prefix `/tracer`. Fitur `tracer.core`. Endpoint di [16 §13](16-rancangan-tracer-study.md#13-kontrak-api-konseptual), [17 §13](17-rancangan-kuesioner-dan-survei.md#13-kontrak-api-konseptual), [18 §13](18-rancangan-distribusi-dan-reminder-tracer.md#13-kontrak-api-konseptual), dan [19 §13](19-rancangan-dashboard-dan-laporan-tracer.md#13-kontrak-api-konseptual). Pembatasan paket: `POST /tracer/periods/{period}/campaigns` memeriksa kuota `tracer.invitations.quota` di service (bukan middleware), karena perlu jumlah target.

**Contoh — `POST /tracer/periods/{period}/campaigns`** (kuota tidak cukup):

```json
// 409
{ "success": false, "message": "Kuota kuesioner terkirim tidak mencukupi.",
  "errors": { "code": ["QUOTA_EXCEEDED"],
    "quota": [ { "feature_key": "tracer.invitations.quota", "limit": 200, "consumed": 180, "reserved": 5,
                 "remaining": 15, "requested": 40, "window": "SUB:01J…:2", "window_ends_at": "2027-03-01T00:00:00Z",
                 "upgrade_hint": "Paket Premium menyediakan 300 kuesioner." } ] } }
```

Kirim ulang dengan `partial_send_policy = send_within_quota` mengirim 15 target menurut urutan prioritas.

## 9. Career Center

| Prefix | Konteks | Fitur |
|---|---|---|
| `/career-center/*` | Staf tenant | `career.portal` (+ fitur per endpoint: `career.articles`, `career.graduate_recommendation`, `career.crm`, `career.company_invitations`, `career.job_posting`) |
| `/career/*`, `/me/career/*` | Mahasiswa/alumni | `career.marketplace` (melihat), `career.applications` (melamar) |
| `/company/*` | Perusahaan (`company.context`) | Tidak ada entitlement tenant pada konteks perusahaan. Pembatasan per tenant target diperiksa saat aksi menyangkut tenant (mis. rekomendasi) |
| `/platform/career/*` | Platform | Permission platform |

Endpoint di [20 §13](20-rancangan-career-center-karirlink.md#13-kontrak-api-konseptual), [21 §13](21-rancangan-lowongan-dan-lamaran-kerja.md#13-kontrak-api-konseptual), [22 §13](22-rancangan-mitra-perusahaan-dan-crm.md#13-kontrak-api-konseptual), [23 §13](23-rancangan-rekomendasi-lulusan.md#13-kontrak-api-konseptual). Pembatasan paket ([34](34-matriks-paket-dan-entitlement.md)) dikembalikan sebagai 403 `{"errors":{"code":["FEATURE_NOT_IN_PLAN"],"feature":["not_in_plan"],"minimum_plan":["professional"]}}`.

**Contoh — `POST /career/jobs/{posting}/applications`**

```json
// request
{ "career_document_ids": { "cv": "01J…" }, "screening_answers": [ { "q": 1, "a": "Bisa mulai Januari 2027" } ],
  "share_phone": false, "consent": { "purpose": "job_application", "consent_text_version": "2026-10" } }
// 201
{ "success": true, "data": { "id": "01J…", "status": "submitted", "submitted_at": "2026-10-13T03:00:00Z" } }
```

Galat: 409 `DUPLICATE_APPLICATION`, 409 `POSTING_CLOSED`, 422 `REQUIRED_DOCUMENT_MISSING`, 429 `TOO_MANY_APPLICATIONS`.

## 10. Endpoint Publik dan Webhook

| Method | URL | Tujuan | Pengaman |
|---|---|---|---|
| GET | `/public/verify/{code}` | Verifikasi dokumen | `throttle:20,1`, respons minimal, `PublicIssuedDocumentResource` |
| POST | `/public/verify/{code}/compare` | Cocokkan hash PDF unggahan (tidak disimpan) | `throttle:5,1`, maks 10 MB |
| GET/PUT/POST | `/public/surveys/{token}`, `/draft`, `/submit` | Survei tracer | Token hash, `throttle:30,1`, `PublicSurveyResource`, Idempotency-Key submit |
| GET/POST | `/public/company-invitations/{token}`, `/accept` | Undangan perusahaan | Token sekali pakai |
| POST | `/company/register` | Registrasi perusahaan | `throttle:5,60`, captcha |
| GET | `/public/career/{tenant_slug}/portal`, `/jobs`, `/jobs/{posting}`, `/articles` | Portal karier publik | Cache 5 menit, tanpa data pelamar |
| POST | `/webhooks/mail/{provider}` | Event bounce/keluhan | Verifikasi tanda tangan penyedia, dedupe `provider_event_id` |

Rute publik **tidak** memakai `auth:sanctum`. Tenant di-resolve dari token/kode (bukan dari header klien) untuk mencegah pemalsuan konteks.

## 11. Kontrak In-Process dan Event

Seluruh modul berada dalam satu aplikasi Laravel (monolit modular). Integrasi memakai **kontrak PHP (interface)** untuk baca sinkron dan **event Laravel** (`ShouldDispatchAfterCommit` / listener `afterCommit`) untuk reaksi asinkron ([KK-KD-26](33-glossary-dan-keputusan-bisnis.md#2-keputusan-desain-kk-kd)). Pola ini sudah dipakai (`SyncStudentRequestDecision`, sinkron dalam transaksi).

### 11.1 Kontrak baca/tulis

| Kontrak | Pemilik | Konsumen | Sinkron? | Status |
|---|---|---|---|---|
| `StudentTranscriptReader` | Akademik | Eligibility, Predikat, Transkrip akhir | Ya | Baru (membungkus `StudentGradeService`, `AcademicRecordService`, Ada) |
| `StudentStatusTransitionContract::graduate/revertGraduation/applyDisciplinaryEffect` | Akademik | Yudisium, Disiplin | Ya, dalam transaksi | Baru |
| `FinanceClearanceService::check($student, $service)` | KEU | Eligibility | Ya | Baru (rancangan KEU 17 §9) |
| `FinanceObligationReader::isSettled($invoiceId)` | KEU | Wisuda | Ya | Baru (diusulkan ke KEU) |
| `LibraryClearanceReader` | Perpustakaan | Eligibility | Ya | Baru (`book_loans` Ada) |
| `ThesisStatusReader` | Thesis | Eligibility | Ya | Baru (`theses` Ada) |
| `AcademicBlockService::check` | SIAKAD | Eligibility | Ya | Baru (rancangan `academic-learning`) |
| `StudentPointsReader` | Kemahasiswaan | Eligibility, Portal | Ya | Baru |
| `DisciplinaryRecordReader` | Kemahasiswaan | Eligibility, Beasiswa, Surat | Ya | Baru |
| `ScholarshipAwardReader` | Scholarship | KEU | Ya | Baru |
| `GraduationRecordReader` | Yudisium | Wisuda, SKPI, Tracer, Alumni, Karier | Ya | Baru |
| `OfficialDocumentService::issue` + `DocumentDataProvider` | OfficialDocument | Semua penerbit | Ya | Baru |
| `FeatureEntitlementService` / `FeatureUsageService` | Tenancy | Semua | Ya | Baru |

### 11.2 Event

| Event | Pemancar | Pendengar | Idempotensi listener |
|---|---|---|---|
| `ActivitySubmissionApproved` / `Revoked` | Kemahasiswaan | SKPI (kandidat), Eligibility (stale) | `skpi_achievements.source_key` unik |
| `StudentPointLedgerChanged` | Kemahasiswaan | Eligibility (stale + debounce) | Debounce key |
| `DisciplinaryDecisionChanged` | Kemahasiswaan | Eligibility, SIAKAD block projector | `source_reference_id` |
| `ScholarshipAwardActivated/Suspended/Resumed/Terminated/PeriodExtended` | Scholarship | KEU | `award_id+event+term` |
| `GradesPublished`, `StudentStatusChanged` | Akademik (Baru) | Eligibility, Beasiswa | Debounce |
| `InvoiceStatusChanged`, `FinanceHoldChanged` | KEU (rancangan) | Eligibility, Wisuda | Debounce / status idempoten |
| `GraduationEligibilityEvaluated` | Yudisium | Notifikasi | `dedupe_key` |
| `GraduationDecisionFinalized` / `Corrected` / `Annulled` | Yudisium | Alumni, Wisuda, SKPI, Tracer, Karier (role `alumni`), OfficialDocument (antrean terbit) | `decision_id+event` |
| `CeremonyRegistrationSubmitted` / `Cancelled` | Wisuda | KEU | `registration_id+event` |
| `OfficialDocumentIssued` / `Revoked` | OfficialDocument | Notifikasi, SKPI, Portal | `document_id+event` |
| `TracerResponseSubmitted` | Tracer | Alumni (catatan karier atas consent), Agregat | `response_id` |
| `CareerApplicationStatusChanged` | Career | Notifikasi | `application_id+to_status+history_id` |

Kegagalan listener asinkron: retry 3× backoff (1/5/15 menit) lalu `failed_jobs` (Laravel, Ada) + entri pemantauan. **Rekonsiliasi** terjadwal: `graduation:reconcile-alumni` (keputusan final ↔ `alumni`), `scholarship:reconcile-discounts` (award ↔ `student_discounts`), `tracer:reconcile-quota` (ledger ↔ undangan), `documents:reconcile-sources` (sumber yang semestinya punya dokumen). Rekonsiliasi hanya melaporkan selisih, dan perbaikan dilakukan melalui aksi berizin.

### 11.3 Integrasi eksternal (adaptor)

| Adaptor | Tujuan | Status |
|---|---|---|
| `DocumentSigningProvider` | Tanda tangan elektronik tersertifikasi | Opsional, [KK-Q-09](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q) |
| `MailEventWebhookParser` per penyedia | Bounce/keluhan | Bergantung penyedia ([KK-Q-20](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q)) |
| Pelaporan kelulusan nasional / PIN | Impor PIN, ekspor data lulusan | Format impor/ekspor berkas dulu, integrasi API menyusul ([KK-Q-13](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q)) |
| Marketplace eksternal | Bila marketplace bukan internal | [KK-Q-03](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q) |

## 12. Integrasi Antarmodul

| Modul | Status di repo | Data yang dipakai | Arah & mekanisme | Catatan anti-duplikasi |
|---|---|---|---|---|
| **PMB** | **Hanya dokumen** (`docs/pmb`) | Identitas & riwayat pendidikan sebelum kuliah (untuk profil karier/SKPI bila diperlukan) | Tidak ada integrasi langsung. Data mengalir PMB → `students` (Akademik) saat konversi, lalu modul ini membaca `students` | Modul ini tidak membaca tabel PMB |
| **Akademik** | Ada | `students`, `study_programs`, `faculties`, `curriculums`, `courses`, `grades`, `academic_terms`, `student_status_histories`, `student_requests` | Kontrak §11.1 | IPK/SKS tidak dihitung ulang. Status mahasiswa diubah hanya oleh Akademik |
| **Keuangan** | Ada terbatas (`invoices`, `payments` read-only); KEU dirancang | Status cekal, status lunas tagihan wisuda, potongan beasiswa | Kontrak + event | Tidak ada perhitungan saldo, nominal, atau potongan di modul ini |
| **Perpustakaan** | Ada (`book_loans`) | Pinjaman aktif/terlambat, denda | `LibraryClearanceReader` | Pelunasan denda belum terstruktur ([KK-Q-28](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q)) |
| **Thesis** | Ada (`theses`) | Status `selesai`, `completed_at`, judul, pembimbing | `ThesisStatusReader` | Judul untuk ijazah/SKPI dikonfirmasi mahasiswa saat pendaftaran yudisium |
| **Internship** | Ada (`internships`) | Magang selesai | Baca terjadwal | — |
| **Dokumen** | Baru (`OfficialDocument`) | Template, nomor, PDF, tanda tangan, verifikasi | Kontrak | Satu layanan untuk semua jenis dokumen |
| **Notifikasi** | Ada (`database`, `mail`) | Template, preferensi, log | `NotificationDispatcher` (diperluas) | [27](27-rancangan-notifikasi-dan-email.md) |
| **ApprovalWorkflow** | Ada | Persetujuan berjenjang | `ApprovalRequestService::submit` + event | Keputusan disalin balik (pola `SyncStudentRequestDecision`) |
| **Alumni** | Ada (minimal) | Ringkasan karier | Listener keputusan & tracer | Jawaban tracer tetap di tracer |
| **SIAKAD cekal** | Dirancang (`academic-learning`) | `academic_blocks` | Kontrak + proyeksi keputusan disiplin | Modul ini tidak menulis `academic_blocks` langsung, tetapi melalui projector SIAKAD |

## 13. Kode Error

Kode umum mengikuti kanon KEU ([keuangan/README §5.8](../keuangan/README.md)) agar konsisten di seluruh aplikasi. Kode khusus modul ini:

| Kode | HTTP | Arti | Dokumen |
|---|---|---|---|
| `VALIDATION_FAILED` | 422 | Validasi Form Request | Umum |
| `INVALID_STATUS_TRANSITION` | 409 | Transisi status tidak diizinkan | Umum |
| `STALE_VERSION` | 409 | `lock_version` tidak cocok | Umum |
| `IDEMPOTENCY_KEY_REUSED` / `IDEMPOTENCY_IN_PROGRESS` | 409 | Idempotensi | Umum |
| `SELF_APPROVAL_FORBIDDEN` | 403 | Penyetuju = pembuat | [28 §6](28-keamanan-privasi-dan-audit.md#6-pemisahan-tugas) |
| `CONFLICT_OF_INTEREST` | 403 | Penyetuju terkait kasus (pelapor) | [07](07-rancangan-pelanggaran-dan-sanksi.md) |
| `FEATURE_NOT_IN_PLAN` | 403 | Fitur tidak termasuk paket (`errors.feature = ["not_in_plan"]`) | [34](34-matriks-paket-dan-entitlement.md) |
| `FEATURE_DISABLED` | 403 | Fitur dimatikan tenant (`errors.feature = ["disabled"]`) | [34](34-matriks-paket-dan-entitlement.md) |
| `QUOTA_EXCEEDED` | 409 | Kuota paket tidak cukup | [18](18-rancangan-distribusi-dan-reminder-tracer.md) |
| `DUPLICATE_SUBMISSION` / `DUPLICATE_EVIDENCE` | 409 | Pengajuan aktivitas ganda | [06](06-rancangan-aktivitas-dan-prestasi.md) |
| `SUBMISSION_WINDOW_CLOSED` / `APPEAL_WINDOW_CLOSED` | 409 / 422 | Batas waktu | [06](06-rancangan-aktivitas-dan-prestasi.md), [05](05-rancangan-poin-kemahasiswaan.md) |
| `STUDENT_STATUS_NOT_ALLOWED` / `STUDENT_STATUS_CHANGED` | 409 | Status mahasiswa tidak memenuhi | [06](06-rancangan-aktivitas-dan-prestasi.md), [09](09-rancangan-surat-keterangan-aktif.md) |
| `OVERRIDE_REQUIRES_APPROVAL` | 409 | Poin di luar rentang | [05](05-rancangan-poin-kemahasiswaan.md) |
| `RULE_OVERLAP` / `CATEGORY_IN_USE` | 409 | Master poin | [05](05-rancangan-poin-kemahasiswaan.md) |
| `HEARING_REQUIRED` | 409 | Hak didengar belum dipenuhi | [07](07-rancangan-pelanggaran-dan-sanksi.md) |
| `QUOTA_EXCEEDED` (beasiswa) | 409 | Kuota penerima | [08](08-rancangan-beasiswa-mahasiswa.md) |
| `NO_CURRENT_TERM` / `TOO_MANY_ACTIVE_REQUESTS` | 409 | Surat | [09](09-rancangan-surat-keterangan-aktif.md) |
| `PERIOD_NOT_OPEN` / `UNIT_NOT_PARTICIPATING` / `ALREADY_GRADUATED` | 409 | Yudisium | [12](12-rancangan-peserta-dan-penetapan-kelulusan.md) |
| `ELIGIBILITY_STALE` | 409 | Hasil stale saat aksi (bila pemeriksaan ulang sinkron gagal) | [11](11-rancangan-eligibilitas-yudisium.md) |
| `NOT_ELIGIBLE` | 409 | Eligibility tidak memenuhi untuk aksi | [12](12-rancangan-peserta-dan-penetapan-kelulusan.md) |
| `NUMBERING_SCHEME_MISSING` / `TEMPLATE_MISSING` / `SIGNATORY_INACTIVE` / `NUMBER_ALLOCATION_CONFLICT` / `DOCUMENT_ALREADY_ISSUED` / `DOCUMENT_TYPE_NOT_ASSIGNED` | 409 / 403 | Dokumen resmi | [13](13-rancangan-penomoran-dokumen-kelulusan.md) |
| `NOT_ELIGIBLE_FOR_CEREMONY` / `SESSION_FULL` / `WRONG_SESSION` / `NOT_CONFIRMED` / `INVALID_TOKEN` | 409 / 404 | Wisuda | [14](14-rancangan-wisuda-dan-buku-wisuda.md) |
| `TRANSLATION_NOT_REVIEWED` / `UNVERIFIED_ACHIEVEMENT` / `DISPLAY_LIMIT_EXCEEDED` | 409 | SKPI | [15](15-rancangan-skpi.md) |
| `ALREADY_SUBMITTED` / `SURVEY_UNAVAILABLE` | 409 / 410 | Survei | [17](17-rancangan-kuesioner-dan-survei.md) |
| `DUPLICATE_APPLICATION` / `POSTING_CLOSED` / `REQUIRED_DOCUMENT_MISSING` / `TOO_MANY_APPLICATIONS` | 409 / 422 / 429 | Lamaran | [21](21-rancangan-lowongan-dan-lamaran-kerja.md) |
| `COMPANY_NOT_VERIFIED` / `MEMBER_NOT_ACTIVE` / `PARTNERSHIP_NOT_ACTIVE` | 403 | Perusahaan | [22](22-rancangan-mitra-perusahaan-dan-crm.md) |
| `CONSENT_REQUIRED` / `PROTECTED_ATTRIBUTE_CRITERIA` | 409 / 422 | Rekomendasi | [23](23-rancangan-rekomendasi-lulusan.md) |

`SURVEY_UNAVAILABLE` (410) dipakai untuk token tidak dikenal, kedaluwarsa, atau dicabut dengan pesan yang sama ([17 AF-QST-02](17-rancangan-kuesioner-dan-survei.md#6-alur-alternatif-dan-kegagalan)).
