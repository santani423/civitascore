# Semester Pendek — Desain Database

> Bagian dari [paket rancangan Semester Pendek](./README.md). Mengikuti konvensi skema yang ada: PK `ulid`, `university_id` wajib + `foreignUlid(...)->constrained('universities')->cascadeOnDelete()`, `timestampsTz()`, enum kolom dari `array_column(Enum::cases(), 'value')`, index komposit diawali `university_id`, model memakai `HasUlids` + `TenantScoped` + `implements ScopesToInstitution`. Migrasi diletakkan di `app/Modules/{Module}/Database/Migrations/`. Database produksi MySQL (`.env`), tes SQLite (`phpunit.xml`).

## 1. ERD

```
                              universities (Tenancy)
                                     │ 1
         ┌───────────────────────────┼──────────────────────────────────┐
         │                           │                                  │
   academic_terms 🔧           study_programs ✅                   university_settings ✅
   + status, registration_*,         │                              (kebijakan SP per tenant)
     max_credits                     ├── students ✅ (user_id ✅)
   semester += 'pendek'              ├── lecturers ✅ + user_id ⛔🆕
         │ 1                         └── curriculums ✅
         │                                  └── courses ✅ ──── course_prerequisites 🆕 (self M:N)
         │ N                                       │ 1
   class_sections 🔧 ────────────────────────────────┘ N
   + min_participants, cancelled_at, cancellation_reason
         │ 1
         ├── N class_section_lecturers ⛔🆕 ── N:1 ── lecturers
         ├── N class_schedules ✅
         ├── N exams ✅ ── exam_questions ✅ / exam_attempts ✅ / exam_grade_ranges ✅
         │ 1
         │ N
   krs_items 🔧  ──────────── N:1 ─────────── invoices 🔧 (+ academic_term_id, status += cancelled)
   status += pending, rejected                      │ 1
   + invoice_id                                     └── N payments ✅ (+ reference)
         │ 1
         ├── 1 grades 🔧 (+ status, finalized_at, finalized_by)
         │        └── N grade_revision_requests 🆕
         ├── N attendances ✅
         └── N exam_attempts ✅

   Lintas modul (polimorfik, ✅ tanpa perubahan skema):
   approval_requests.requestable → krs_items | grade_revision_requests
   audit_logs.auditable          → academic_terms | class_sections | krs_items | grades | ...
   activity_logs.subject         → (event domain SP)
```

Legenda: ✅ dipakai apa adanya · 🔧 kolom/enum ditambah · 🆕 tabel baru · ⛔ prasyarat umum (bukan khusus SP).

## 2. Tabel yang Dipakai Ulang Tanpa Perubahan Skema

| Tabel | Peran di SP |
|---|---|
| `students` | Peserta; `status`, `study_program_id`, `user_id` untuk self-service |
| `study_programs`, `faculties` | Scope penawaran, konteks `WorkflowSelector` |
| `curriculums`, `courses` | Master MK; `credits` untuk SKS & biaya |
| `attendances` | Absensi SP |
| `exams` + turunannya, `question_bank_items` | Ujian SP |
| `approval_workflows`, `approval_workflow_steps`, `approval_requests`, `approval_request_steps`, `approval_actions`, `approval_histories` | Persetujuan KRS & revisi nilai |
| `audit_logs`, `activity_logs` | Jejak audit |
| `notifications`, `notification_templates`, `notification_logs`, `user_notification_preferences` | Notifikasi |
| `university_settings` | Kebijakan SP per tenant |

## 3. Perubahan & Tabel Baru

### 3.1 `academic_terms` 🔧

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---|---|---|
| `semester` | enum (`ganjil`,`genap`,`pendek`) | tidak | — | **ubah**: tambah nilai `pendek` |
| `status` | enum `AcademicTermStatus` (`draft`,`planned`,`registration_open`,`registration_closed`,`ongoing`,`grading`,`completed`,`archived`) | tidak | `draft` | 🆕. Backfill lihat §4 |
| `registration_starts_at` | timestampTz | ya | null | 🆕 |
| `registration_ends_at` | timestampTz | ya | null | 🆕 |
| `max_credits` | unsignedTinyInteger | ya | null | 🆕. `null` = pakai setting tenant |

Index: tambah `(university_id, semester, status)` — query "SP yang sedang dibuka" untuk portal.
Unique: **tetap** `(university_id, academic_year, semester)` → satu SP per tahun.
Soft delete: **tidak** (konsisten dengan tabel akademik lain; penghapusan dibatasi DRAFT tanpa kelas di service).
Audit: trait `Auditable` pada model.
Model: `$fillable` **tanpa** `status` untuk jalur `PUT`; status diubah oleh `AcademicTermService::transition()` dengan `forceFill`. Tambah cast `status`, `registration_*` → `datetime`. Perbaiki `label()` jadi `match`.

> **Catatan migrasi enum:** mengubah `enum` di MySQL lewat `$table->enum('semester', [...])->change()` (didukung native sejak Laravel 11 untuk MySQL & SQLite — SQLite di-rebuild tabelnya). Urutkan nilai lama terlebih dulu agar data tidak tersentuh. Sama untuk `krs_items.status` dan `invoices.status`.

### 3.2 `class_sections` 🔧

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `min_participants` | unsignedSmallInteger | ya | 🆕 `null` = setting `academic.short_term.min_participants` |
| `cancelled_at` | timestampTz | ya | 🆕 |
| `cancellation_reason` | string(255) | ya | 🆕 |

Index tambahan: tidak perlu (index `(university_id, is_active, academic_term_id)` yang ada sudah mencakup daftar penawaran).
Audit: `Auditable`.

### 3.3 `class_schedules` ✅ (umum)

> **Rekonsiliasi R-02:** tabel ini sudah ada (migrasi `2026_10_06_200001`) dan menggantikan usulan `class_section_schedules`. Tidak ada `effective_from`/`effective_until`: bentrok hanya dibandingkan di dalam satu term, dan tanggal term dilarang beririsan ([aturan-bisnis.md §8.2](./aturan-bisnis.md#82-logika)). Tidak perlu migrasi untuk SP.

| Kolom | Tipe | Null | FK / Keterangan |
|---|---|---|---|
| `id` | ulid PK | — | |
| `university_id` | ulid | tidak | → `universities` cascade |
| `class_section_id` | ulid | tidak | → `class_sections` cascade |
| `day_of_week` | unsignedTinyInteger | tidak | 1=Senin … 7=Minggu (ISO-8601) |
| `start_time` | time | tidak | |
| `end_time` | time | tidak | > `start_time` (divalidasi di request) |
| `room` | string(100) | ya | Disimpan setelah trim + spasi tunggal (huruf dipertahankan); dibandingkan tanpa membedakan huruf besar/kecil |
| `created_at`, `updated_at` | timestampTz | | |

Index: `(university_id, class_section_id)`, `(university_id, day_of_week)` (kandidat bentrok per hari).
Unique: tidak (satu kelas bisa punya dua slot di hari yang sama, mis. teori & praktikum).
Soft delete: tidak — jadwal diganti utuh lewat `PUT class-sections/{id}/teaching`; riwayat lewat audit.
Audit: satu `audit_logs` `updated` pada kelas (snapshot dosen + jadwal sebelum/sesudah; alasan paksa di kolom `reason`) + `activity_logs` `CLASS_SCHEDULE_CHANGED` — bukan `Auditable` per baris, karena sync menghapus+membuat ulang.

### 3.4 `class_section_lecturers` ⛔🆕 (umum)

| Kolom | Tipe | Null | FK / Keterangan |
|---|---|---|---|
| `id` | ulid PK | | |
| `university_id` | ulid | tidak | → `universities` cascade |
| `class_section_id` | ulid | tidak | → `class_sections` cascade |
| `lecturer_id` | ulid | tidak | → `lecturers` **restrictOnDelete** (dosen yang pernah mengampu tidak boleh terhapus diam-diam) |
| `role` | enum (`coordinator`,`member`) | tidak | default `coordinator` |
| `created_at`, `updated_at` | timestampTz | | |

Unique: `(class_section_id, lecturer_id)`.
Index: `(university_id, lecturer_id)` — "kelas saya".
Audit: `activity_logs` `LECTURER_ASSIGNED` / `LECTURER_UNASSIGNED`.

### 3.5 `lecturers.user_id` ⛔🆕 (umum)

`foreignUlid('user_id')->nullable()->unique()->constrained('users')->nullOnDelete()` — pola identik migrasi `2026_08_26_173744_add_user_id_to_students_table.php`. Tambah relasi `User::lecturer(): HasOne`.

### 3.6 `course_prerequisites` 🆕 (umum, opsional)

| Kolom | Tipe | Null | FK / Keterangan |
|---|---|---|---|
| `id` | ulid PK | | |
| `university_id` | ulid | tidak | → `universities` cascade |
| `course_id` | ulid | tidak | → `courses` cascade |
| `prerequisite_course_id` | ulid | tidak | → `courses` cascade |
| `min_letter_grade` | enum `LetterGrade` | tidak | default `D` (lulus) |
| timestamps | | | |

Unique: `(course_id, prerequisite_course_id)`. Check di service: `course_id ≠ prerequisite_course_id`, tidak ada siklus (DFS saat simpan).
Bila tabel kosong untuk sebuah MK, pemeriksaan prasyarat dilewati — fitur ini bisa dikirim belakangan tanpa memblokir SP.

### 3.7 `krs_items` 🔧

| Kolom | Perubahan |
|---|---|
| `status` | enum += `pending`, `rejected` |
| `invoice_id` | 🆕 `foreignUlid()->nullable()->constrained('invoices')->nullOnDelete()` |

Index tambahan: `(university_id, academic_term_id, status)` (dashboard & kapasitas), `(student_id, academic_term_id, status)` (batas SKS, sudah sering di-query `enroll()`).
Unique: **tetap** `(student_id, class_section_id)`.
Audit: `Auditable` (setiap perubahan status tercatat dengan old/new).

> **Penghitungan kapasitas** (berubah dari `status = enrolled` menjadi):
> `status = enrolled` **OR** (`status = pending` **AND** (`invoice_id IS NULL` **OR** invoice `due_date >= today`))
> Item `pending` dengan invoice kedaluwarsa tidak menahan kursi bahkan sebelum dibersihkan.

### 3.8 `grades` 🔧

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `status` | enum `GradeStatus` (`draft`,`submitted`,`finalized`) | tidak | 🆕 default `draft` |
| `finalized_at` | timestampTz | ya | 🆕 |
| `finalized_by` | ulid → `users` nullOnDelete | ya | 🆕 |

`submitted_at` yang ada **dipertahankan dengan arti lama** (waktu terakhir nilai diinput) supaya `GradeResource` & frontend tidak berubah makna.
Index: `(university_id, status)`.
Audit: `Auditable` — setiap perubahan `score`/`letter_grade`/`status` tercatat old/new + `reason`.

### 3.9 `grade_revision_requests` 🆕 (umum)

| Kolom | Tipe | Null | FK / Keterangan |
|---|---|---|---|
| `id` | ulid PK | | |
| `university_id` | ulid | tidak | → `universities` cascade |
| `grade_id` | ulid | tidak | → `grades` cascade |
| `requested_by` | ulid | tidak | → `users` restrictOnDelete |
| `old_score` | decimal(5,2) | ya | snapshot saat pengajuan |
| `old_letter_grade` | enum `LetterGrade` | ya | snapshot |
| `new_score` | decimal(5,2) | tidak | |
| `new_letter_grade` | enum `LetterGrade` | tidak | dihitung dari `new_score` bila tidak dikirim |
| `reason` | text | tidak | |
| `status` | enum (`pending`,`approved`,`rejected`,`cancelled`) | tidak | |
| `decided_at` | timestampTz | ya | |
| `applied_at` | timestampTz | ya | saat nilai benar-benar diubah |
| timestamps | | | |

Index: `(university_id, status)`, `(grade_id)`.
Constraint bisnis: maksimal satu `pending` per `grade_id` (dicek di service di bawah `lockForUpdate` baris `grades`).
Soft delete: tidak — tidak pernah dihapus.

### 3.10 `invoices` 🔧 / `payments` 🔧

| Tabel | Kolom | Perubahan |
|---|---|---|
| `invoices` | `academic_term_id` | 🆕 `foreignUlid()->nullable()->constrained('academic_terms')->nullOnDelete()` — tagihan non-akademik tetap null |
| `invoices` | `status` | enum += `cancelled` |
| `invoices` | `period` | tetap diisi `term->label()` (kompatibel dengan tampilan & pencarian yang ada) |
| `payments` | `reference` | 🆕 string(100) nullable — nomor bukti transfer/VA |
| `payments` | `recorded_by` | 🆕 ulid → `users` nullOnDelete, nullable |

Index: `invoices (university_id, academic_term_id, status)`.
Audit: `Auditable` pada `Invoice` dan `Payment`.

## 4. Strategi Migrasi & Backfill

| Langkah | Isi |
|---|---|
| 1 | Tambah `pendek` ke `academic_terms.semester` |
| 2 | Tambah `academic_terms.status` dengan default `draft`, lalu backfill: `is_current = true` → `ongoing`; `end_date < today` → `completed`; lainnya → `planned` |
| 3 | `grades.status`: backfill semua baris yang ada → `submitted` (tetap bisa diubah seperti sekarang; **tidak** di-finalize otomatis — keputusan akademik) |
| 4 | `krs_items.status`, `invoices.status` enum diperluas (tanpa backfill) |
| 5 | Tabel baru dibuat kosong |
| 6 | `class_section_lecturers`: tidak bisa di-backfill otomatis (data relasi tidak ada). Lihat Fase 0 [rencana-implementasi.md](./rencana-implementasi.md#fase-0--prasyarat-umum) |

Seluruh migrasi reversibel (`down()` menghapus kolom/tabel; untuk enum, `down()` menolak bila ada baris memakai nilai baru — gagal keras lebih baik dari kehilangan data).

## 5. Konfigurasi per Tenant (`university_settings`)

Tabel ✅ sudah ada: unik `(university_id, key)`, kolom `type` (`SettingValueType`), `group`, `is_public`. Semua kunci di bawah memakai `group = 'academic'`. Dibaca lewat satu helper baru `AcademicPolicySettings` (cache per tenant, mengikuti pola `SystemSettingService::get()` dengan default di kode).

| Key | Tipe | Default | Berlaku |
|---|---|---|---|
| `academic.short_term.max_credits` | integer | `9` | SP |
| `academic.short_term.min_credits` | integer | `0` | SP |
| `academic.short_term.allow_new_courses` | boolean | `true` | SP |
| `academic.short_term.retake_max_letter_grade` | string (LetterGrade / kosong) | kosong = semua | SP |
| `academic.short_term.fee_per_credit` | integer (rupiah) | `0` (= tanpa tagihan) | SP |
| `academic.short_term.payment_due_days` | integer | `3` | SP |
| `academic.short_term.require_no_outstanding_invoices` | boolean | `true` | SP |
| `academic.short_term.min_participants` | integer | `5` | SP |
| `academic.short_term.counts_toward_next_term_sks_quota` | boolean | `false` | SP → reguler berikutnya |
| `academic.short_term.min_attendance_percentage` | integer / kosong | kosong = pakai umum | SP |
| `academic.short_term.late_registration_days` | integer | `0` | SP |
| `academic.repeat_grade_policy` | string `highest`/`latest` | `highest` | **Semua** periode |
| `academic.transcript_repeat_display` | string `all_attempts`/`effective_only` | `all_attempts` | **Semua** periode |
| `academic.min_attendance_percentage` | integer | `75` | **Semua** periode |
| `academic.attendance_excused_counts_as_present` | boolean | `true` | **Semua** periode |
| `academic.attendance_blocks_exam` | boolean | `false` | **Semua** periode |

`is_public = true` untuk kunci yang dibutuhkan portal mahasiswa (max/min credits, fee, allow_new_courses, retake limit) agar UI bisa menampilkan aturan tanpa endpoint khusus.

Diubah lewat endpoint yang ✅ sudah ada `PUT /tenant/settings` (permission `tenant_profile.update`). Halaman pengaturan akademik di frontend: [frontend-dan-mobile.md §2.10](./frontend-dan-mobile.md#210-pengaturan-kebijakan-sp).

## 6. Kesadaran Tenant

Semua tabel 🆕 punya `university_id` + `TenantScoped`. Semua unique index yang memuat data bisnis sudah implisit per tenant karena memuat FK ke baris ber-tenant (`class_section_id`, `course_id`, `grade_id`) — tidak ada unique global baru. Tidak ada FK lintas tenant yang bisa terbentuk karena semua ID input di-resolve lewat query ter-scope (lihat [keamanan-audit-integritas.md §2](./keamanan-audit-integritas.md#2-isolasi-tenant--idor)).
