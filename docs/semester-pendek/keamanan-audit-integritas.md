# Semester Pendek — Keamanan, Integritas Data & Audit

> Bagian dari [paket rancangan Semester Pendek](./README.md).

## 1. Otorisasi

Setiap aksi sensitif divalidasi **di server**, dua lapis — sama dengan pola yang ada:

1. **Rute**: `->middleware('permission:resource.action')` (`EnsurePermission` → `PermissionRegistry`, cache per user per tenant, bypass `super_admin`).
2. **Objek**: `$this->authorize(...)` di controller (Policy auto-discovery) atau policy self-service via `app()` (pola `ExamParticipationPolicy`).

Peran UI (`usePermission`, `PermissionGate`) **tidak pernah** menjadi satu-satunya pengaman.

| Aset dilindungi | Rute | Objek |
|---|---|---|
| Modifikasi nilai | `grades.create,grades.update` | Dosen harus pengampu (`class_section_lecturers`); status bukan `finalized`; term bukan `archived` |
| Finalisasi nilai | `grades.finalize` | Term SP harus `grading`; semua nilai kelas `submitted` |
| Revisi nilai | `grades.update` / `grades.approve` | Pengampu atau admin; keputusan oleh approver yang ditugaskan |
| Penawaran kelas | `classes.create/update` | Term pada status yang mengizinkan |
| Penugasan dosen | `classes.update` | Dosen aktif, tenant sama |
| Override pendaftaran / prasyarat | `krs.create` + `krs.approve` | `reason` wajib; hanya daftar override yang diizinkan |
| KRS mandiri | `krs_participation.*` | `student_id` dari sesi; kepemilikan item |
| Status pembayaran | `invoices.update` | Status hanya turunan dari pembayaran tercatat; tidak ada set status langsung |
| Status periode | `academic_terms.update` | Mesin transisi; status tidak di `$fillable` jalur `PUT` |
| Transkrip | (baca) `students.read` / `krs_participation.read` | Tidak ada endpoint tulis; hanya berubah lewat nilai |

## 2. Isolasi Tenant & IDOR

| Ancaman | Mitigasi (konvensi yang ada) |
|---|---|
| ID milik tenant lain di body request | Resolve semua ID lewat `Model::query()->findOrFail()` ter-scope `BelongsToInstitutionScope` → 404. **Jangan** `Rule::exists` (query mentah lintas tenant) |
| Route model binding lintas tenant | Model `TenantScoped` → binding ikut ter-scope → 404 |
| Mahasiswa mengakses KRS/nilai/tagihan orang lain | Endpoint `student/*` tidak menerima `student_id`; daftar selalu `where('student_id', auth()->user()->student->id)`; aksi per item dicek policy kepemilikan |
| Dosen mengakses kelas lain | Daftar difilter ke kelas yang diampu; aksi dicek policy (403) |
| Mahasiswa melihat term DRAFT | Scope `visibleToStudents()` → 404 |
| Join lintas tabel TenantScoped | Hindari `join()` antar model ber-scope (kolom `university_id` ambigu — catatan di migrasi `krs_items`); pakai `whereHas` / kolom terdenormalisasi |
| Relasi baru tanpa `university_id` | Semua tabel baru wajib `university_id` + `TenantScoped`; diuji di `TenantIsolationTest` |

## 3. Konkurensi & Integritas Data

### 3.1 Masalah

```
Kapasitas = 30, terisi = 29.
Mahasiswa A dan B submit di milidetik yang sama.
Tanpa kunci: keduanya membaca 29 → keduanya lolos → 31 peserta.  ✗
```

Kasus kedua yang **belum** ditangani kode saat ini:

```
Mahasiswa A (sisa kuota 3 SKS) mengirim dua permintaan paralel untuk dua kelas berbeda (3 SKS masing-masing).
Kunci saat ini hanya pada baris class_sections → dua kunci berbeda → keduanya membaca total 6 → keduanya lolos → 12 SKS.  ✗
```

### 3.2 Desain

`enroll()` sudah membuka `DB::transaction` dan `ClassSection::lockForUpdate()`. Diperluas menjadi:

```
DB::transaction:
  1. Student::lockForUpdate()->findOrFail(studentId)
        ← menserialkan SEMUA pendaftaran milik satu mahasiswa
          (batas SKS, duplikat MK, bentrok jadwal mahasiswa jadi aman)
  2. ClassSection::lockForUpdate()->whereIn(ids)->orderBy('id')->get()
        ← kunci kelas dalam urutan ID deterministik: mencegah deadlock
          antara dua mahasiswa yang memilih {X, Y} dan {Y, X}
  3. Lepas hold kedaluwarsa untuk kelas-kelas itu (lazy expiry)
  4. Hitung peserta (enrolled + pending aktif) per kelas  → bandingkan capacity
  5. Pipeline kelayakan lainnya
  6. Insert / update krs_items
COMMIT → event (notifikasi, approval submit, invoice) dikirim afterCommit
```

Urutan kunci global selalu **mahasiswa → kelas (ID naik)**. Jalur lain yang mengunci kelas (`cancel`, ubah kapasitas) hanya mengunci kelas — tidak pernah kelas lalu mahasiswa — sehingga tidak ada siklus.

Penjaga lapis kedua di database:
- Unique `(student_id, class_section_id)` (✅ ada) — permintaan ganda ke kelas sama gagal di DB meskipun logika terlewati.
- Kapasitas tidak bisa dinyatakan sebagai constraint SQL lintas baris; kunci baris `class_sections` adalah mekanismenya (sudah dipakai, konsisten).

### 3.3 Catatan pengujian

SQLite (tes) mengabaikan `lockForUpdate` dan menserialkan seluruh penulisan — tes konkurensi **harus** dijalankan terhadap MySQL (lihat [pengujian-dan-penerimaan.md §1.5](./pengujian-dan-penerimaan.md#15-uji-konkurensi)).

### 3.4 Transaksi lain

| Operasi | Transaksi + kunci |
|---|---|
| Catat pembayaran | `Invoice::lockForUpdate()` — dua pencatatan serentak tidak bisa melewati `amount` |
| Finalisasi / submit nilai kelas | `ClassSection::lockForUpdate()` lalu update massal `grades` |
| Terapkan revisi nilai | `Grade::lockForUpdate()`; bandingkan snapshot `old_*` — basi → 409 |
| Transisi status term | `AcademicTerm::lockForUpdate()`; validasi transisi dari status terkunci, bukan dari status yang dibaca sebelumnya |
| Pembatalan kelas | `ClassSection::lockForUpdate()` + update item & invoice dalam transaksi yang sama |
| Ubah kapasitas | `ClassSection::lockForUpdate()`; tolak bila < peserta aktif |

### 3.5 Mass assignment

- Request class memakai `$request->validated()` saja (konvensi yang ada).
- Kolom state (`academic_terms.status`, `krs_items.status`, `grades.status`, `finalized_*`, `invoices.status`, `invoices.paid_amount`, `krs_items.invoice_id`, `university_id`) **tidak** pernah berasal dari input — di-set oleh service lewat `forceFill`/atribut eksplisit.
- `university_id` diisi `TenantScoped::creating` dari `TenantContext`, tidak dari request.

## 4. Audit Log

### 4.1 Dua mekanisme yang sudah ada

| Mekanisme | Tabel | Untuk |
|---|---|---|
| Trait `Auditable` → `AuditLogService::record()` | `audit_logs` (`user_id`, `auditable_type/id`, `action` ∈ created/updated/deleted/restored/exported/imported, `old_values`, `new_values`, `reason`, `ip_address`, `user_agent`, `created_at`, `university_id`) | Bukti perubahan data per baris (compliance) |
| `AuditLogService::log($logName, $description, $subject, $properties)` | `activity_logs` (`log_name` string bebas, `description`, `subject`, `properties` JSON) | Event domain yang bermakna bisnis |

`reason` diisi otomatis dari input `reason` atau header `X-Change-Reason` — frontend cukup mengirim `reason` di body untuk aksi yang mewajibkannya.

**Tidak** menambah nilai ke enum `AuditAction` (kolom DB enum; event domain bukan operasi CRUD). Event domain ditulis ke `activity_logs` dengan `log_name = 'academic.short_term'` (atau `'academic'` untuk event umum seperti nilai) dan `properties.event = '<KODE>'`.

### 4.2 Model yang diberi `Auditable`

`AcademicTerm`, `ClassSection`, `KrsItem`, `Grade`, `GradeRevisionRequest`, `Invoice`, `Payment`, `CoursePrerequisite`.

### 4.3 Event domain

| Kode | Subject | `properties` minimum |
|---|---|---|
| `SHORT_TERM_CREATED` | AcademicTerm | `academic_year` |
| `SHORT_TERM_UPDATED` | AcademicTerm | field yang berubah |
| `SHORT_TERM_STATUS_CHANGED` | AcademicTerm | `from`, `to`, `forced`, `reason` |
| `COURSE_OFFERED` / `CLASS_CREATED` | ClassSection | `course_id`, `capacity` |
| `CLASS_CANCELLED` | ClassSection | `reason`, `affected_krs_item_ids`, `affected_invoice_ids` |
| `CLASS_SCHEDULE_CHANGED` | ClassSection | `before[]`, `after[]`, `forced`, `reason` |
| `LECTURER_ASSIGNED` / `LECTURER_UNASSIGNED` | ClassSection | `lecturer_id`, `role`, `forced`, `reason` |
| `ENROLLMENT_CREATED` | KrsItem | `channel` (`student`/`admin`), `class_section_id` |
| `ENROLLMENT_APPROVED` / `ENROLLMENT_REJECTED` | KrsItem | `approval_request_id`, `approver_id`, `reason` |
| `ENROLLMENT_CANCELLED` / `ENROLLMENT_EXPIRED` | KrsItem | `by`, `reason`, `invoice_id` |
| `PREREQUISITE_OVERRIDDEN` | KrsItem | `overrides[]`, `reason` |
| `PAYMENT_CONFIRMED` | Invoice | `payment_id`, `amount`, `method`, `reference` |
| `REFUND_REQUIRED` | Invoice | `krs_item_ids`, `amount` |
| `GRADE_SUBMITTED` / `GRADE_RETURNED` / `GRADE_FINALIZED` | ClassSection | `count`, `reason` |
| `GRADE_REVISION_REQUESTED` | GradeRevisionRequest | `grade_id`, `old`, `new`, `reason` |
| `GRADE_CHANGED` | Grade | `revision_request_id`, `approval_request_id`, `old`, `new` |

Pemetaan ke bidang yang diminta: **actor** = `user_id` · **action** = `properties.event` / `action` · **entity** = `subject_type` / `auditable_type` · **entity_id** = `subject_id` / `auditable_id` · **before/after** = `old_values`/`new_values` (atau `properties.old/new`) · **reason** = `reason` / `properties.reason` · **timestamp** = `created_at`.

### 4.4 Siapa membaca

`audit_logs.read` (sudah dimiliki `auditor` dan role pemilik tenant). Riwayat nilai per grade untuk dosen/akademik lewat endpoint terbatas `GET /grades/{grade}/history` ([penilaian-dan-transkrip.md §3](./penilaian-dan-transkrip.md#3-riwayat-nilai)).
