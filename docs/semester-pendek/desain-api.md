# Semester Pendek — Desain API

> Bagian dari [paket rancangan Semester Pendek](./README.md).

## 1. Konvensi yang Diikuti (dari kode yang ada)

| Aspek | Konvensi | Sumber |
|---|---|---|
| Prefix | `/api/v1/…`, dipasang otomatis per modul dari `app/Modules/*/Routes/api.php`, di bawah middleware `tenant.resolve` | `routes/api.php` |
| Autentikasi | `auth:sanctum` | semua `Routes/api.php` |
| Otorisasi kasar | `->middleware('permission:a.b')` (koma = OR) | `EnsurePermission` |
| Otorisasi objek | `$this->authorize(...)` di controller / policy self-service via `app()` | `ExamParticipationPolicy` |
| Nama resource | kebab-case jamak: `class-sections`, `krs-items`, `academic-terms` | Academic routes |
| Transisi status | `PATCH /{resource}/{id}/{aksi}` (`/drop`, `/publish`, `/submit`) | KRS, Exams |
| Self-service mahasiswa | Prefix `student/` | `student/exams`, `student/exam-attempts` |
| List | `?search=`, `?filter[kolom]=`, `?sort=kolom` / `-kolom`, `?per_page=` | `ListQuery::paginate()` |
| Respons | `{success, message, data, meta}`; error `{success:false, message, errors}` | `ApiResponse` |
| Error status | 401 tanpa login · 403 tanpa permission · 404 tidak ada / tenant lain · 409 konflik state (`ConflictException`) · 422 validasi | |
| Pesan | Bahasa Indonesia | seluruh controller |

**Bukan** `/api/academic/short-semesters` atau `/api/admin/...`: proyek ini tidak memakai prefix per peran untuk admin, dan SP adalah term — dikelola lewat resource `academic-terms` dengan `filter[semester]=pendek`.

### 1.1 Kode error mesin

🔧 `ConflictException` diperluas dengan argumen opsional `?string $code`; bila diisi, dirender sebagai `errors.code`. Kompatibel mundur (semua pemanggil lama tanpa kode).

```json
HTTP 409
{
  "success": false,
  "message": "Melebihi batas maksimum 9 SKS untuk semester ini (sudah mengambil 6 SKS).",
  "errors": { "code": "SKS_LIMIT_EXCEEDED", "class_section_id": "01J..." }
}
```

Daftar kode: [aturan-bisnis.md §4.1](./aturan-bisnis.md#41-pipeline).

---

## 2. Endpoint Admin / Akademik

### 2.1 Periode (`academic-terms`) 🆕 — umum, dipakai SP

| Method | URL | Permission | Keterangan |
|---|---|---|---|
| GET | `/academic-terms` | `academic_terms.read` | filter: `semester`, `status`, `academic_year`, `is_current`; sort: `start_date` |
| POST | `/academic-terms` | `academic_terms.create` | status awal selalu `draft` |
| GET | `/academic-terms/{term}` | `academic_terms.read` | + `class_sections_count`, `krs_summary` |
| PUT | `/academic-terms/{term}` | `academic_terms.update` | tidak menerima `status`, `semester`, `academic_year` setelah ada kelas |
| PATCH | `/academic-terms/{term}/status` | `academic_terms.update` | transisi siklus hidup |
| DELETE | `/academic-terms/{term}` | `academic_terms.delete` | hanya DRAFT tanpa kelas |
| GET | `/academic-terms/{term}/summary` | `academic_terms.read` | angka dashboard SP ([frontend-dan-mobile.md §2.1](./frontend-dan-mobile.md#21-dashboard-sp)) |

**`POST /academic-terms`**

```json
{
  "academic_year": "2026/2027",
  "semester": "pendek",
  "start_date": "2027-07-05",
  "end_date": "2027-08-20",
  "registration_starts_at": "2027-06-21T08:00:00+07:00",
  "registration_ends_at": "2027-06-30T23:59:59+07:00",
  "max_credits": 9
}
```

- 201 → `AcademicTermResource` `{id, academic_year, semester, label, status, is_current, start_date, end_date, registration_starts_at, registration_ends_at, max_credits, effective_max_credits}` (`effective_max_credits` = kolom ?? setting).
- 409 SP tahun itu sudah ada · 409 tanggal beririsan · 422 validasi ([aturan-bisnis.md §11.1](./aturan-bisnis.md#111-validasi-input-formrequest--422)).
- Audit: `Auditable` (created).

**`PATCH /academic-terms/{term}/status`**

```json
{ "status": "registration_open", "reason": null }
```

- 200 → resource terbaru.
- 409 transisi tidak diizinkan · 409 prasyarat transisi gagal (mis. `→ registration_open` tanpa kelas aktif; `→ completed` dengan nilai belum final dan tanpa `force`).
- Body opsional `force: true` + `reason` (min 10) hanya untuk `grading → completed`.
- Audit: `Auditable` (updated: status old/new + reason) + `activity_logs` `SHORT_TERM_STATUS_CHANGED`.
- Efek samping (setelah commit, via event `AcademicTermStatusChanged`): notifikasi sesuai status.

### 2.2 Kelas (`class-sections`) 🔧 — tulis 🆕

| Method | URL | Permission | Keterangan |
|---|---|---|---|
| GET | `/class-sections` | `classes.read` | ✅ ada; 🔧 filter tambahan `lecturer_id`, `has_schedule`; untuk dosen tanpa permission admin: otomatis hanya kelas yang diampu |
| GET | `/class-sections/{id}` | `classes.read` | ✅ ada; 🔧 + `lecturers`, `schedules`, `enrolled_count`, `pending_count`, `min_participants` |
| POST | `/class-sections` | `classes.create` | term harus DRAFT/PLANNED/REGISTRATION_OPEN/REGISTRATION_CLOSED |
| PUT | `/class-sections/{id}` | `classes.update` | `capacity`, `min_participants`, `class_code`, `is_active`; tidak boleh pindah term bila ada peserta |
| PATCH | `/class-sections/{id}/cancel` | `classes.update` | body `{reason}`; [aturan-bisnis.md §5.3](./aturan-bisnis.md#53-pembatalan-kelas-sepi) |
| PUT | `/class-sections/{id}/lecturers` | `classes.update` | sync dosen; cek bentrok dosen; `force` + `reason` |
| PUT | `/class-sections/{id}/schedules` | `classes.update` | sync jadwal; cek bentrok dosen & ruang; respons memuat `warnings.student_conflicts` |

**`POST /class-sections`**

```json
{
  "academic_term_id": "01J...",
  "course_id": "01J...",
  "study_program_id": "01J...",
  "class_code": "SP-A",
  "capacity": 30,
  "min_participants": 5
}
```

- `course_id` harus milik `study_program_id` yang sama (dicek di service).
- 201 · 404 ID tenant lain · 409 term tidak menerima kelas baru.

**`PUT /class-sections/{id}/schedules`**

```json
{
  "schedules": [
    { "day_of_week": 1, "starts_at": "08:00", "ends_at": "10:30", "room": "R.301" },
    { "day_of_week": 3, "starts_at": "08:00", "ends_at": "10:30", "room": "R.301" }
  ],
  "force": false,
  "reason": null
}
```

- 200 `{data: ClassSectionResource, meta: {warnings: {student_conflicts: [{student_id, nim, name, conflicting_class_code}]}}}`
- 409 `SCHEDULE_CONFLICT` dengan `errors.conflicts: [{type: "room"|"lecturer", class_section_id, class_code, day_of_week, starts_at, ends_at}]`.

**`PUT /class-sections/{id}/lecturers`**

```json
{ "lecturers": [ { "lecturer_id": "01J...", "role": "coordinator" } ], "force": false, "reason": null }
```

- 409 dosen tidak aktif (`lecturers.is_active = false`) · 409 bentrok jadwal dosen (kecuali `force`).
- Audit: `activity_logs` `LECTURER_ASSIGNED`. Notifikasi `short_term.teaching_assigned` ke dosen yang punya `user_id`.

### 2.3 Prasyarat 🆕 (opsional)

| Method | URL | Permission |
|---|---|---|
| GET | `/courses/{course}/prerequisites` | `courses.read` |
| PUT | `/courses/{course}/prerequisites` | `course_prerequisites.update` — body `{prerequisites: [{course_id, min_letter_grade}]}`; 409 siklus |

### 2.4 KRS (admin) 🔧

| Method | URL | Permission | Perubahan |
|---|---|---|---|
| GET | `/krs-items` | `krs.read` | ✅; status filter kini juga `pending`, `rejected`; 🔧 `include=approval,invoice` |
| POST | `/krs-items` | `krs.create` (+ `krs.approve` bila memakai `override`) | 🔧 seluruh pipeline kelayakan; body baru opsional `override[]`, `reason` |
| PATCH | `/krs-items/{id}/drop` | `krs.update` | ✅; 🔧 `reason` wajib untuk term SP; menyesuaikan invoice |

Contoh override:

```json
{
  "student_id": "01J...",
  "class_section_id": "01J...",
  "override": ["prerequisite"],
  "reason": "Prasyarat sudah dipenuhi lewat konversi transfer kredit (SK Dekan No. 12/2027)."
}
```

- 403 bila `override` dikirim tanpa `krs.approve`.
- Audit: `Auditable` (KrsItem created) + `activity_logs` `PREREQUISITE_OVERRIDDEN` dengan `{student_id, class_section_id, overrides, reason}`.

Persetujuan/penolakan KRS memakai endpoint ✅ yang ada:
`POST /approval-request-steps/{step}/approve` · `POST /approval-request-steps/{step}/reject` · `GET /approval-requests?filter[requestable_type]=krs_item`.

### 2.5 Nilai 🔧

| Method | URL | Permission | Keterangan |
|---|---|---|---|
| PUT | `/krs-items/{id}/grade` | `grades.create,grades.update` | ✅; 🔧 + kepemilikan dosen; 409 bila `finalized` atau term ARCHIVED |
| GET | `/grades` | `grades.read` | ✅; 🔧 filter `status`, `academic_term_id`; dosen → hanya kelasnya |
| PATCH | `/class-sections/{id}/grades/submit` | `grades.update` + pengampu | draft → submitted (semua peserta) |
| PATCH | `/class-sections/{id}/grades/return` | `grades.finalize` | submitted → draft, body `{reason}` |
| PATCH | `/class-sections/{id}/grades/finalize` | `grades.finalize` | submitted → finalized |
| GET | `/grades/{grade}/history` | `grades.read` + policy | dari `audit_logs` |
| POST | `/grades/{grade}/revision-requests` | `grades.update` + pengampu, atau `grades.finalize` | body `{score, letter_grade?, reason}` |
| GET | `/grade-revision-requests` | `grades.read` | filter `status`, `academic_term_id` |
| PATCH | `/grade-revision-requests/{id}/approve` | `grades.approve` | fallback bila tanpa workflow |
| PATCH | `/grade-revision-requests/{id}/reject` | `grades.approve` | body `{reason}` |

**`PATCH /class-sections/{id}/grades/submit`** → 200 `{data: {submitted: 24}}` · 409 `GRADES_INCOMPLETE` dengan `errors.missing: [{krs_item_id, nim, name}]` · 409 term SP bukan ONGOING/GRADING.

### 2.6 Keuangan 🔧

| Method | URL | Permission | Keterangan |
|---|---|---|---|
| GET | `/invoices` | `invoices.read` | ✅; 🔧 filter `academic_term_id` |
| POST | `/invoices/{invoice}/payments` | `invoices.update` | 🆕 catat pembayaran manual |
| PATCH | `/invoices/{invoice}/cancel` | `invoices.update` | 🆕 body `{reason}`; hanya bila `paid_amount = 0` |

**`POST /invoices/{invoice}/payments`**

```json
{ "amount": 450000, "paid_at": "2027-06-25", "method": "transfer", "reference": "TRF-88231" }
```

- Transaksi + `lockForUpdate` invoice: tambah `payments`, `paid_amount += amount`, status `partial`/`paid`.
- 409 invoice `cancelled`/`paid` · 422 `amount` > sisa.
- Saat menjadi `paid`: event `InvoicePaid` → listener mempromosikan `krs_items` terkait `pending` → `enrolled` (bila sudah disetujui).
- Audit: `Auditable` (Payment created, Invoice updated) + `activity_logs` `PAYMENT_CONFIRMED`.

### 2.7 Laporan 🔧

`GET /reports/{type}?filter[academic_term_id]=…&format=csv|pdf` (✅ `ReportController::show` ada; 🔧 parameter `format` baru, mengikuti pola `ExamController::exportRecap()` — CSV via `streamDownload` + `fputcsv`, PDF via dompdf; tanpa `format` tetap JSON seperti sekarang) — tipe baru: `sp-enrollment`, `sp-courses`, `sp-grades`, `sp-finance`, `sp-summary`. Kolom: [frontend-dan-mobile.md §4](./frontend-dan-mobile.md#4-laporan). Permission `reports.read`; ekspor dicatat `audit_logs` action `exported` (nilai enum ✅ ada).

---

## 3. Endpoint Mahasiswa (self-service) 🆕

Semua di bawah `auth:sanctum`, prefix `student/`, otorisasi `KrsParticipationPolicy` ([peran-dan-izin.md §5.2](./peran-dan-izin.md#52-mahasiswa--krs-sendiri)). Identitas mahasiswa selalu dari `auth()->user()->student`; 403 bila user bukan mahasiswa.

| Method | URL | Permission |
|---|---|---|
| GET | `/student/academic-terms` | `krs_participation.read` |
| GET | `/student/academic-terms/{term}/offering` | `krs_participation.read` |
| GET | `/student/krs-items` | `krs_participation.read` |
| POST | `/student/krs-items` | `krs_participation.create` |
| PATCH | `/student/krs-items/{krsItem}/cancel` | `krs_participation.update` |
| GET | `/student/schedule` | `krs_participation.read` |
| GET | `/student/attendances` | `krs_participation.read` |
| GET | `/student/transcript` | `krs_participation.read` |
| GET | `/student/invoices` | `krs_participation.read` |

Endpoint `student/schedule`, `student/attendances`, `student/transcript`, `student/invoices` **bukan khusus SP** — inilah endpoint yang dibutuhkan untuk menyambungkan portal (MAHASISWA §4.3) dan menerima `filter[academic_term_id]`.

### 3.1 `GET /student/academic-terms/{term}/offering`

404 bila term `draft` atau milik tenant lain.

```json
{
  "success": true,
  "data": {
    "term": { "id": "01J...", "label": "2026/2027 Pendek", "status": "registration_open",
              "registration_ends_at": "2027-06-30T23:59:59+07:00" },
    "rules": { "max_credits": 9, "min_credits": 0, "fee_per_credit": 150000,
               "taken_credits": 3, "allow_new_courses": true },
    "classes": [
      {
        "class_section_id": "01J...",
        "class_code": "SP-A",
        "course": { "id": "01J...", "code": "IF302", "name": "Basis Data", "credits": 3 },
        "lecturers": [ { "name": "Dr. Siti Nurhaliza", "role": "coordinator" } ],
        "schedules": [ { "day_of_week": 1, "starts_at": "08:00", "ends_at": "10:30", "room": "R.301" } ],
        "capacity": 30,
        "remaining_seats": 4,
        "fee": 450000,
        "is_repeat": true,
        "previous_grade": "D",
        "eligibility": { "eligible": true, "reasons": [] }
      }
    ]
  }
}
```

`eligibility.reasons[]` = `[{code, message}]` dari mode evaluasi pipeline (tanpa kunci). Informasional saja — submit memvalidasi ulang.

### 3.2 `POST /student/krs-items`

```json
{ "class_section_ids": ["01J...", "01J..."] }
```

- Atomik: satu transaksi, kunci baris mahasiswa lalu kelas-kelas terurut ID ([keamanan-audit-integritas.md §3](./keamanan-audit-integritas.md#3-konkurensi--integritas-data)). Satu gagal → semua dibatalkan.
- 201 → `{data: {items: [KrsItemResource], invoice: InvoiceResource|null, approval_required: bool}}`.
- 409 dengan `errors.code` + `errors.class_section_id` kelas yang gagal.
- 403 bukan mahasiswa · 404 kelas tenant lain/term draft.
- Audit: `Auditable` per KrsItem (`user_id` = mahasiswa) + `activity_logs` `ENROLLMENT_CREATED`.

### 3.3 `PATCH /student/krs-items/{krsItem}/cancel`

- 403 bukan miliknya (policy) — **bukan 404**, mengikuti `ExamParticipationPolicy` (KRS-nya memang ada di tenant yang sama; kepemilikan diperiksa setelah route model binding).
- 409 di luar jendela / sudah dibayar / sudah ada aktivitas / di bawah min SKS.
- 200 → item `dropped`, invoice disesuaikan.

---

## 4. Endpoint Dosen 🆕

Bergantung pada `lecturers.user_id` (⛔).

| Method | URL | Permission | Keterangan |
|---|---|---|---|
| GET | `/lecturer/class-sections` | `classes.read` | kelas yang diampu; filter `academic_term_id`, `semester` |

Semua aksi lain (absensi, nilai, ujian) memakai endpoint yang sudah ada dengan policy kepemilikan.

---

## 5. Ringkasan Audit per Endpoint

| Endpoint | `audit_logs` (Auditable) | `activity_logs` event |
|---|---|---|
| POST/PUT/DELETE `academic-terms` | ✅ | `SHORT_TERM_CREATED` / `SHORT_TERM_UPDATED` (hanya term `pendek`) |
| PATCH `academic-terms/{id}/status` | ✅ | `SHORT_TERM_STATUS_CHANGED` |
| POST/PUT `class-sections` | ✅ | `COURSE_OFFERED` / `CLASS_CREATED` |
| PATCH `class-sections/{id}/cancel` | ✅ | `CLASS_CANCELLED` |
| PUT `class-sections/{id}/lecturers` | — | `LECTURER_ASSIGNED` |
| PUT `class-sections/{id}/schedules` | — | `CLASS_SCHEDULE_CHANGED` |
| POST `krs-items`, `student/krs-items` | ✅ | `ENROLLMENT_CREATED` (+ `PREREQUISITE_OVERRIDDEN`) |
| Approval KRS (listener) | ✅ | `ENROLLMENT_APPROVED` / `ENROLLMENT_REJECTED` |
| PATCH drop/cancel | ✅ | `ENROLLMENT_CANCELLED` |
| POST `invoices/{id}/payments` | ✅ | `PAYMENT_CONFIRMED` |
| PATCH grades submit/return/finalize | ✅ (per grade) | `GRADE_SUBMITTED` / `GRADE_RETURNED` / `GRADE_FINALIZED` |
| POST revision-requests | ✅ | `GRADE_REVISION_REQUESTED` |
| Revisi diterapkan | ✅ | `GRADE_CHANGED` |
| GET `reports/{type}?format=` | ✅ action `exported` | — |
