# Rancangan Akun & Fitur SDM (Kepegawaian)

> **Status:** Dokumen desain. Bagian "kondisi saat ini" disusun dari kode aktual (backend `app/Modules/Academic` [Lecturer/Employee], `app/Modules/UserManagement`, `app/Modules/Dashboard`, `app/Modules/Report`, `app/Modules/ApprovalWorkflow`, frontend `frontend/src`). Bagian "rancangan target" adalah usulan yang **belum diimplementasikan** dan ditandai eksplisit.
> **Dibuat:** 2026-10-06
> Pelengkap [RANCANGAN-AKUN-DOSEN.md](./RANCANGAN-AKUN-DOSEN.md) (role `lecturer` sebagai *pengguna*), [RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md), dan [RANCANGAN-APLIKASI.md](./RANCANGAN-APLIKASI.md) §3.9, §3.11, §4.5, §4.13, §4.15, "Laporan SDM", "Dashboard Pegawai", "Tahap 5". Dokumen ini membahas dua role: **`hr_administrator`** ("Bagian SDM", pengelola) dan **`employee`** ("Pegawai", subjek/self-service).

## Daftar Isi

1. [Apa itu "Akun SDM"](#1-apa-itu-akun-sdm)
2. [Ringkasan Akses (Permission) Saat Ini](#2-ringkasan-akses-permission-saat-ini)
3. [Struktur Data Saat Ini](#3-struktur-data-saat-ini)
4. [Fitur — Status Implementasi Saat Ini](#4-fitur--status-implementasi-saat-ini)
5. [Endpoint API yang Bisa Dipakai SDM Saat Ini](#5-endpoint-api-yang-bisa-dipakai-sdm-saat-ini)
6. [Celah & Risiko Saat Ini](#6-celah--risiko-saat-ini)
7. [Rancangan Target — Ruang Lingkup Modul Kepegawaian](#7-rancangan-target--ruang-lingkup-modul-kepegawaian)
8. [Rancangan Target — Model Data](#8-rancangan-target--model-data)
9. [Rancangan Target — Role & Permission](#9-rancangan-target--role--permission)
10. [Rancangan Target — Endpoint API](#10-rancangan-target--endpoint-api)
11. [Rancangan Target — Aturan Bisnis](#11-rancangan-target--aturan-bisnis)
12. [Rancangan Target — Alur Kerja](#12-rancangan-target--alur-kerja)
13. [Rancangan Target — Dashboard & Laporan](#13-rancangan-target--dashboard--laporan)
14. [Rancangan Target — Frontend & Mobile](#14-rancangan-target--frontend--mobile)
15. [Keamanan, Privasi & Audit](#15-keamanan-privasi--audit)
16. [Rencana Implementasi Bertahap](#16-rencana-implementasi-bertahap)
17. [Keputusan Terbuka](#17-keputusan-terbuka)

---

## 1. Apa itu "Akun SDM"

Istilah "akun SDM" di CivitasOne merujuk ke tiga hal yang saat ini **tidak saling terhubung**:

1. **Role `hr_administrator`** (label **"Bagian SDM"**) — role pengelola. Dipegang akun login (`users` + `user_roles`) staf bagian kepegawaian. Demo: `sdm@undigital.test`, `sdm@itmandala.test`, `sdm@stikessejahtera.test` (password `password`).
2. **Role `employee`** (label **"Pegawai"**) — role tenaga kependidikan sebagai *pengguna sistem*. Diseed **tanpa permission apa pun** (komentar di `OrganizationalRoleSeeder`: *"employee belum punya modul kepegawaian sama sekali"*). Demo: `pegawai@<domain>`.
3. **Record data induk SDM** — dua tabel terpisah:
   - `lecturers` (`Modules\Academic\Models\Lecturer`) — data induk dosen.
   - `employees` (`Modules\Academic\Models\Employee`) — data induk tenaga kependidikan.

**Tidak ada identity link** dari `Lecturer` maupun `Employee` ke `User` (beda dengan `Student.user_id`). Akibatnya sistem tidak bisa menjawab "pegawai yang sedang login ini record `employees` yang mana" — prasyarat mutlak untuk semua fitur self-service (presensi, cuti, slip gaji) dan untuk approval berbasis jabatan (§6.5).

Catatan penempatan: model `Lecturer`/`Employee` saat ini tinggal di **Modul Akademik**, bukan modul kepegawaian tersendiri. Rancangan target (§8) mengusulkan modul baru `app/Modules/HumanResource`.

---

## 2. Ringkasan Akses (Permission) Saat Ini

Persis dari `OrganizationalRoleSeeder::ROLE_PERMISSIONS['hr_administrator']`:

```
user_roles.read, users.read
employees.read
lecturers.read, lecturers.create, lecturers.update, lecturers.delete
```

Role `employee`: **kosong**.

**Yang tidak dimiliki `hr_administrator`** (berefek nyata, lihat §6):
- `reports.read` — Bagian SDM **tidak bisa membuka laporan "Dosen dan Pegawai"** (`GET /reports/sdm`), padahal laporan itu memang laporan SDM.
- `user_roles.create` / `user_roles.delete` — SDM bisa melihat role pengguna, tapi tidak bisa memberi/mencabut role (mis. memberi role `lecturer` ke dosen baru). Itu hak `university_owner`/`university_administrator`.
- `approval_requests.read` — tidak melihat daftar approval tenant-wide.
- `employees.create/update/delete` — **tidak ada di katalog permission sama sekali** (`RolePermissionSeeder::PERMISSION_MAP['employees']` hanya `Read`). Data pegawai tidak bisa diinput lewat sistem oleh role mana pun.

Siapa lagi yang membaca data SDM: `university_owner`, `university_administrator`, `rector`, `auditor` (`lecturers.read` + `employees.read`); `vice_rector`, `dean`, `head_of_study_program` (hanya `lecturers.read`).

⚠️ **README.md §Akun Sample sudah usang** untuk baris `sdm` — masih tertulis `user_roles.read`, `users.read` saja, belum mencerminkan `employees.read` dan `lecturers.*`.

---

## 3. Struktur Data Saat Ini

```
lecturers     id (ulid), university_id, faculty_id (nullable, nullOnDelete),
              nidn, name, email (nullable), is_active, timestamps
              UNIQUE (university_id, nidn)
              — TIDAK punya user_id, NIP, status kepegawaian, jabatan
                fungsional, pangkat/golongan, tanggal masuk, dsb.

employees     id (ulid), university_id, unit_kerja (string bebas), name,
              email (nullable), position (string bebas, nullable), is_active,
              timestamps
              INDEX (university_id, unit_kerja)
              — TIDAK punya user_id, nomor induk pegawai (NIP/NIK), status
                kerja, jenis kontrak, tanggal masuk, atasan langsung.
              — unit_kerja & position adalah teks bebas, bukan FK ke master
                unit/jabatan.
```

Keduanya `TenantScoped` (isolasi per universitas, akses lintas tenant → 404).

**Implikasi struktural:**
- Dosen dan pegawai adalah dua tabel paralel tanpa induk bersama. Satu orang yang berstatus dosen sekaligus menjabat struktural (mis. Kepala Biro) tidak bisa dimodelkan tanpa duplikasi.
- `unit_kerja` teks bebas → laporan/chart `staff_by_unit` menggabungkan "nama fakultas" (dosen) dengan "unit_kerja" (pegawai) berdasarkan kecocokan string (`DashboardStatsService::staffByUnit()`).
- Tidak ada riwayat (jabatan, pangkat, kontrak, mutasi) — setiap perubahan menimpa nilai lama.

---

## 4. Fitur — Status Implementasi Saat Ini

Legenda: **✅ CRUD penuh** · **📖 Read-only** · **🧩 Terbangun tapi belum tersambung** · **❌ Belum dibangun**.

| Fitur (blueprint §3.11) | Status | Catatan |
|---|:---:|---|
| Kelola data dosen | ✅ | `LecturerController` store/update/destroy, `StoreLecturerRequest`/`UpdateLecturerRequest` (NIDN unik per tenant), UI `LecturersPage.tsx` + `LecturerDetailPage.tsx` (form modal, tombol digating `lecturers.create/update/delete`). Ada `LecturerControllerTest`. Field hanya NIDN, nama, email, fakultas, aktif. |
| Kelola data pegawai | 📖 | Hanya `GET /employees`, `GET /employees/{id}`. `EmployeesPage.tsx`/`EmployeeDetailPage.tsx` read-only (filter `unit_kerja`, `is_active`). Data hanya masuk lewat seeder. |
| Lihat pengguna & role | 📖 | `GET /users`, `GET /users/{user}/roles`. |
| Kontrak kerja | ❌ | — |
| Jabatan (struktural/fungsional) | ❌ | `employees.position` teks bebas; dosen tidak punya field jabatan. |
| Kepangkatan / golongan | ❌ | — |
| Cuti & izin | ❌ | — |
| Absensi kerja (presensi) | ❌ | — |
| Payroll & slip gaji | ❌ | Kode modul `payroll` ada di katalog modul tenant (`TenancySeeder`), tapi tanpa implementasi. |
| Penilaian kinerja | ❌ | — |
| Pelatihan | ❌ | — |
| Dokumen kepegawaian | ❌ | Modul `FileManagement` sudah ada dan bisa dipakai ulang (§8). |
| Tugas pegawai (blueprint §4.13) | ❌ | — |
| Dashboard SDM | 🧩 | Dashboard generik; untuk SDM tampil `total_lecturers`, `total_employees`, chart `staff_by_unit`, `pending_approvals` (milik sendiri). |
| Laporan SDM | 🧩 | `ReportService::buildSdm()` ada (nama, jenis, unit, status) — tapi SDM tidak punya `reports.read`. |
| Self-service pegawai (role `employee`) | ❌ | Nol permission, nol layar. |
| Mobile (Flutter) | ❌ | `mobile/lib/features` hanya `auth`, `dashboard` (placeholder), `exam`. |

Feature flag modul `hr` dan `payroll` tercatat di katalog modul tenant, tetapi **tidak ada middleware yang menegakkannya** — menu Dosen/Pegawai tetap tampil walau modul `hr` tidak aktif untuk tenant tersebut.

---

## 5. Endpoint API yang Bisa Dipakai SDM Saat Ini

```
GET    users                         users.read
GET    users/{user}/roles            user_roles.read

GET    lecturers                     lecturers.read   (search: name,nidn · filter: faculty_id,is_active)
GET    lecturers/{lecturer}          lecturers.read
POST   lecturers                     lecturers.create
PUT    lecturers/{lecturer}          lecturers.update
DELETE lecturers/{lecturer}          lecturers.delete (hard delete)

GET    employees                     employees.read   (search: name,position · filter: unit_kerja,is_active)
GET    employees/{employee}          employees.read

GET    dashboard                     (dipangkas per permission)
```

Semua di bawah `auth:sanctum` + resolusi tenant (`X-University-ID`).

---

## 6. Celah & Risiko Saat Ini

### 6.1 Hapus dosen = hard delete tanpa jejak
`DELETE /lecturers/{id}` memanggil `$lecturer->delete()` langsung — tanpa soft delete dan tanpa audit log (model `Lecturer` tidak memakai mekanisme audit seperti `User`/`Role`). Data induk kepegawaian seharusnya tidak pernah benar-benar hilang; yang benar adalah menonaktifkan (`is_active = false`) atau mencatat tanggal keluar. Begitu `class_sections.lecturer_id` ditambahkan (rekomendasi RANCANGAN-AKUN-DOSEN.md §11), hard delete juga akan merusak riwayat pengampu.

### 6.2 Data pegawai tidak bisa dikelola sama sekali
Tidak ada permission `employees.create/update/delete` dan tidak ada endpoint tulis. Bagian SDM — satu-satunya role yang secara tugas mengelola pegawai — hanya bisa melihat.

### 6.3 SDM tidak bisa membuka laporan SDM
`GET /reports/sdm` digate `reports.read`, yang tidak dimiliki `hr_administrator`. Laporan SDM justru hanya bisa dibuka oleh owner/admin/pimpinan/auditor.

### 6.4 Data dosen ↔ akun login dikelola terpisah
SDM bisa membuat record `lecturers`, tapi tidak bisa membuat akun login atau memberi role `lecturer` (tidak punya `user_roles.create`). Onboarding dosen baru butuh dua orang/dua langkah tanpa keterkaitan data.

### 6.5 Approver berbasis jabatan diblokir
`ApproverResolver` melempar `LogicException` untuk `ApprovalApproverType::Position` dengan pesan *"akan diimplementasikan bersama modul Kepegawaian"*. Alur persetujuan "atasan langsung" / "Kepala Unit" tidak mungkin dibangun sebelum ada master jabatan + penempatan pegawai (§8).

### 6.6 Field sensitif belum ada, tapi rancangan harus siap
Begitu NIK, NPWP, rekening, dan gaji masuk (§8), data SDM menjadi data pribadi paling sensitif di sistem. Saat ini semua role dengan `employees.read` melihat seluruh kolom resource — pola ini **tidak boleh** dibawa ke field sensitif (lihat §15).

---

## 7. Rancangan Target — Ruang Lingkup Modul Kepegawaian

Modul baru **`app/Modules/HumanResource`** (kode modul tenant: `hr`, sub-fitur `payroll` tetap modul terpisah). Submodul:

| # | Submodul | Pengguna utama | Prioritas |
|---|---|---|:---:|
| A | Data Induk Pegawai (dosen + tendik, satu induk) | SDM | P1 |
| B | Organisasi: Unit Kerja & Jabatan, Penempatan | SDM | P1 |
| C | Identity link & provisioning akun | SDM, Admin | P1 |
| D | Kontrak & Riwayat (jabatan, pangkat, mutasi) | SDM | P2 |
| E | Dokumen Kepegawaian | SDM, Pegawai | P2 |
| F | Cuti & Izin (+ approval) | Pegawai, Atasan, SDM | P2 |
| G | Presensi Kerja (check-in/out, shift, koreksi) | Pegawai, SDM | P3 |
| H | Penilaian Kinerja | Atasan, Pegawai, SDM | P3 |
| I | Pelatihan & Pengembangan | SDM, Pegawai | P4 |
| J | Payroll & Slip Gaji | SDM/Payroll, Pegawai | P4 |
| K | Tugas Pegawai (blueprint §4.13) | Atasan, Pegawai | P4 |
| L | Dashboard & Laporan SDM | SDM, Pimpinan | Tiap fase |

Di luar cakupan dokumen ini: BKD/portofolio dosen dan Tridharma (modul akademik/penelitian), rekrutmen.

---

## 8. Rancangan Target — Model Data

Semua tabel: `id` ULID, `university_id` (TenantScoped), `timestampsTz`. Tabel induk memakai `softDeletes`.

### 8.1 Induk pegawai

Pendekatan: **satu tabel induk `employees`** untuk semua SDM, dengan `employee_type` dosen/tendik; `lecturers` dipertahankan sebagai **ekstensi akademik** dosen (NIDN, fakultas) yang menunjuk ke `employees`. Ini menghindari duplikasi orang yang sekaligus dosen dan pejabat struktural, dan membuat cuti/presensi/payroll cukup satu jalur.

```
employees  (diperluas)
  user_id              ulid nullable, UNIQUE per tenant  → users   [identity link]
  employee_number      string, UNIQUE (university_id, employee_number)  [NIP/NIK pegawai]
  employee_type        enum: lecturer | staff
  name, email, phone
  gender, birth_place, birth_date
  national_id_number   string nullable, terenkripsi            [NIK KTP — sensitif]
  tax_number           string nullable, terenkripsi            [NPWP — sensitif]
  employment_status    enum: permanent | contract | honorary | probation | outsourced
  work_status          enum: active | on_leave | suspended | retired | resigned | terminated | deceased
  hired_at             date
  terminated_at        date nullable
  termination_reason   string nullable
  is_active            boolean  (diturunkan dari work_status, dipertahankan untuk kompatibilitas filter)
  unit_kerja           (dipertahankan sementara → diganti work_unit_id lewat penempatan, lalu dihapus)
  position             (dipertahankan sementara → diganti positions, lalu dihapus)
  softDeletes

lecturers  (diperluas)
  employee_id          ulid nullable → employees, UNIQUE        [backfill lalu wajib]
  user_id              ulid nullable                            [atau diturunkan via employee.user_id — lihat §17]
  functional_rank      enum: none | asisten_ahli | lektor | lektor_kepala | guru_besar
  softDeletes
```

### 8.2 Organisasi

```
work_units         code, name, type (rectorate|faculty|bureau|unit|study_program),
                   parent_id (self, nullable), faculty_id nullable, is_active
                   UNIQUE (university_id, code)

positions          code, name, kind (structural|functional|staff),
                   work_unit_id nullable, grade_level nullable,
                   reports_to_position_id nullable (self)     [rantai atasan]

employee_assignments   (penempatan; satu pegawai bisa >1 jabatan, mis. dosen + Kaprodi)
                   employee_id, position_id, work_unit_id,
                   is_primary bool, starts_at, ends_at nullable,
                   decree_number (SK), decree_file_id nullable → file uploads
```

`ApprovalApproverType::Position` di-resolve lewat `employee_assignments` aktif untuk `position_id` tersebut (§11.6).

### 8.3 Riwayat & kontrak

```
employee_contracts     employee_id, contract_number, type, starts_at, ends_at nullable,
                       base_salary (decimal, terenkripsi/akses terbatas), file_id, status
employee_rank_histories   employee_id, rank/golongan, effective_at, decree_number, file_id
employee_mutations     employee_id, from_assignment_id, to_assignment_id, effective_at, reason
employee_educations    employee_id, level, institution, major, graduated_year, file_id
employee_family_members  employee_id, name, relation, birth_date  (untuk tunjangan)
employee_bank_accounts   employee_id, bank, account_number (terenkripsi), holder_name, is_primary
```

### 8.4 Dokumen

```
employee_documents     employee_id, type (ktp|npwp|ijazah|sk|kontrak|sertifikat|lainnya),
                       file_id → Modules\FileManagement, expires_at nullable,
                       verified_by nullable, verified_at nullable
```

### 8.5 Cuti & izin

```
leave_types        code, name, annual_quota_days nullable, requires_attachment bool,
                   is_paid bool, counts_weekends bool, min_service_months
leave_balances     employee_id, leave_type_id, year, quota, used, carried_over
                   UNIQUE (employee_id, leave_type_id, year)
leave_requests     employee_id, leave_type_id, starts_at, ends_at, days,
                   reason, attachment_file_id nullable,
                   status (draft|submitted|approved|rejected|cancelled),
                   approval_request_id → ApprovalWorkflow
holidays           date, name   (kalender libur tenant)
```

### 8.6 Presensi kerja

```
work_shifts        name, starts_at (time), ends_at (time), late_tolerance_minutes, days_of_week
employee_shifts    employee_id, work_shift_id, starts_at, ends_at nullable
work_locations     name, latitude, longitude, radius_m           [geofence]
attendance_logs    employee_id, type (check_in|check_out), occurred_at,
                   source (web|mobile|fingerprint|manual),
                   latitude/longitude nullable, photo_file_id nullable, work_location_id nullable
daily_attendances  employee_id, date, check_in_at, check_out_at,
                   status (present|late|absent|leave|permit|business_trip|holiday),
                   late_minutes, overtime_minutes
                   UNIQUE (employee_id, date)
attendance_corrections   employee_id, date, requested_check_in/out, reason, status, approval_request_id
```

Nama tabel sengaja berbeda dari `attendances` (presensi mahasiswa per pertemuan) agar tidak tercampur.

### 8.7 Kinerja, pelatihan, payroll

```
performance_periods    name, starts_at, ends_at, status
performance_reviews    employee_id, period_id, reviewer_employee_id, score, rating,
                       notes, status (draft|self_review|manager_review|final)
performance_review_items  review_id, indicator, weight, target, achievement, score

trainings              title, organizer, starts_at, ends_at, hours
training_participants  training_id, employee_id, status, certificate_file_id

payroll_components     code, name, kind (earning|deduction), calc (fixed|percentage|formula), is_taxable
employee_payroll_components  employee_id, component_id, amount
payroll_runs           period (YYYY-MM), status (draft|calculated|approved|paid|locked)
payslips               payroll_run_id, employee_id, gross, deductions, tax, net, snapshot (json)
payslip_lines          payslip_id, component_id, amount
```

`payslips.snapshot` menyimpan salinan komponen saat dihitung, supaya slip lama tidak berubah ketika master komponen diubah.

---

## 9. Rancangan Target — Role & Permission

### 9.1 Katalog permission baru (`RolePermissionSeeder::PERMISSION_MAP`)

```
employees              read, create, update, delete          (+ create/update/delete baru)
employee_sensitive     read, update                          (NIK, NPWP, rekening, gaji pokok)
work_units             read, create, update, delete
positions              read, create, update, delete
employee_assignments   read, create, update, delete
employee_contracts     read, create, update
employee_documents     read, create, update, delete
leave_types            read, create, update
leave_requests         read, create, update                  (tenant-wide, untuk SDM)
attendance_logs        read, update                          (tenant-wide + koreksi)
work_shifts            read, create, update, delete
performance_reviews    read, create, update
trainings              read, create, update, delete
payroll                read, create, update, approve
payslips               read
employee_self          read, create, update                  (self-service; selalu di-scope ke user login)
reports                read                                  (sudah ada)
```

`employee_self.*` sengaja resource terpisah — mengikuti pola `exam_participation` vs `exam_attempts`: memberikannya tidak pernah bisa membuka data pegawai lain, dan kepemilikan tetap dicek ulang di policy level objek.

### 9.2 Pemetaan role

| Role | Permission target |
|---|---|
| `hr_administrator` (Bagian SDM) | `employees.*`, `lecturers.*`, `work_units.*`, `positions.*`, `employee_assignments.*`, `employee_contracts.*`, `employee_documents.*`, `leave_types.*`, `leave_requests.*`, `attendance_logs.*`, `work_shifts.*`, `performance_reviews.*`, `trainings.*`, `employee_sensitive.read/update`, `payslips.read`, `reports.read`, `users.read`, `user_roles.read`, **`user_roles.create` terbatas** (lihat §11.3), `approval_requests.read` |
| `payroll_administrator` (**role baru**, "Bagian Penggajian") | `payroll.*`, `payslips.read`, `employee_sensitive.read`, `employees.read` |
| `employee` (Pegawai) | `employee_self.*` |
| `lecturer` (Dosen) | tambah `employee_self.*` (dosen juga pegawai: cuti, presensi, slip gaji) |
| Atasan (dekan, kaprodi, kepala unit) | **tidak lewat permission role**, melainkan lewat penempatan jabatan: berhak menyetujui cuti/koreksi presensi dan menilai kinerja bawahan langsung (§11.6) |
| `university_owner`, `university_administrator`, `rector`, `auditor` | `.read` pada seluruh resource SDM non-sensitif + `reports.read`; **tanpa** `employee_sensitive.*` kecuali diputuskan lain (§17) |

---

## 10. Rancangan Target — Endpoint API

Prefix modul `HumanResource`, semua `auth:sanctum` + tenant.

**Data induk & organisasi (SDM)**
```
GET|POST           employees
GET|PUT|DELETE     employees/{employee}                 DELETE = soft delete, hanya bila belum punya riwayat
PATCH              employees/{employee}/terminate        {terminated_at, reason, work_status}
GET|PUT            employees/{employee}/sensitive        employee_sensitive.read/update
POST               employees/{employee}/account          buat/tautkan akun login (§11.3)
GET|POST           employees/import                      impor CSV/XLSX (validasi dulu, commit kemudian)
GET                employees/export

GET|POST           work-units · GET|PUT|DELETE work-units/{id}
GET|POST           positions  · GET|PUT|DELETE positions/{id}
GET|POST           employees/{employee}/assignments · PUT|DELETE employee-assignments/{id}
GET|POST           employees/{employee}/contracts   · PUT employee-contracts/{id}
GET|POST           employees/{employee}/documents   · DELETE employee-documents/{id}
PATCH              employee-documents/{id}/verify
GET                employees/{employee}/history          gabungan riwayat jabatan/pangkat/mutasi
```

**Cuti, presensi, kinerja, pelatihan (SDM/atasan)**
```
GET|POST           leave-types · PUT leave-types/{id}
GET                leave-requests                       filter status/unit/periode
GET|PUT            leave-balances?employee_id=&year=    penyesuaian manual kuota (tercatat audit)
GET|POST           holidays · DELETE holidays/{id}
GET|POST           work-shifts · PUT|DELETE work-shifts/{id}
GET|POST           work-locations · PUT|DELETE work-locations/{id}
GET                attendance/daily                     rekap harian tenant
GET                attendance/recap?period=YYYY-MM
GET|POST           performance-periods · GET|POST performance-reviews · PUT performance-reviews/{id}
GET|POST           trainings · POST trainings/{id}/participants
```

**Payroll**
```
GET|POST           payroll-components · PUT payroll-components/{id}
GET|POST           payroll-runs
POST               payroll-runs/{run}/calculate
PATCH              payroll-runs/{run}/approve          payroll.approve (≠ pembuat run)
PATCH              payroll-runs/{run}/lock
GET                payroll-runs/{run}/payslips
```

**Self-service pegawai (`employee_self.*`, selalu diturunkan dari `auth()->user()->employee`, tanpa parameter `employee_id`)**
```
GET                me/employee                          profil sendiri (field sensitif dimasking)
PUT                me/employee                          hanya field kontak; perubahan data inti → pengajuan ke SDM
GET|POST           me/documents
GET                me/leave-balances
GET|POST           me/leave-requests · PATCH me/leave-requests/{id}/cancel
POST               me/attendance/check-in · POST me/attendance/check-out
GET                me/attendance?period=YYYY-MM
POST               me/attendance-corrections
GET                me/payslips · GET me/payslips/{id}/pdf
GET                me/performance-reviews · PUT me/performance-reviews/{id}/self-review
GET                me/subordinates/leave-requests       (atasan) persetujuan via ApprovalWorkflow
```

Persetujuan cuti/koreksi presensi memakai endpoint `ApprovalWorkflow` yang sudah ada (approve/reject step), bukan endpoint baru.

---

## 11. Rancangan Target — Aturan Bisnis

**11.1 Nomor induk.** `employee_number` unik per tenant, tidak bisa diubah setelah ada riwayat (kontrak/presensi/payslip). NIDN tetap unik per tenant di `lecturers`.

**11.2 Tidak ada hard delete.** Pegawai yang keluar → `terminate` (isi `terminated_at`, `work_status`), akun login otomatis dinonaktifkan (token Sanctum dicabut, role dicabut), data tetap ada untuk riwayat/payroll/audit. Soft delete hanya untuk record salah input yang belum punya riwayat apa pun.

**11.3 Provisioning akun.** `POST employees/{id}/account` membuat (atau menautkan berdasarkan email) `users` + membership tenant + role default sesuai `employee_type` (`employee` atau `lecturer`). SDM **hanya** boleh memberi role dari daftar putih (`employee`, `lecturer`, `academic_advisor`); role berprivilese (owner, admin, keuangan, SDM, auditor) tetap hanya oleh `university_owner`/`university_administrator`. Ini diterapkan di policy, bukan sekadar UI.

**11.4 Cuti.**
- Hari cuti dihitung hari kerja (kecuali `counts_weekends`), dikurangi `holidays`.
- Pengajuan ditolak bila saldo tidak cukup, tumpang tindih dengan cuti lain yang `submitted/approved`, atau masa kerja < `min_service_months`.
- Saldo dikurangi saat **approved**, dikembalikan saat dibatalkan sebelum tanggal mulai.
- Selama cuti approved, `daily_attendances.status = leave` otomatis.
- Carry-over tahunan dijalankan job terjadwal awal tahun (batas maksimal per `leave_type`, keputusan §17).

**11.5 Presensi.**
- Satu check-in dan satu check-out per hari (check-in kedua ditolak; koreksi lewat `attendance_corrections`).
- `late_minutes` = check-in − (jam mulai shift + toleransi), minimal 0.
- Bila `work_location` diwajibkan, check-in di luar radius geofence ditolak (mobile mengirim koordinat; server yang memvalidasi).
- Rekap harian `daily_attendances` dihasilkan job di akhir hari; pegawai tanpa log, tanpa cuti, dan bukan hari libur → `absent`.

**11.6 Atasan & approval.** Atasan langsung = pemegang `employee_assignments` aktif pada `positions.reports_to_position_id` dari jabatan primer pegawai. `ApproverResolver` untuk `ApprovalApproverType::Position` di-resolve ke `user_id` pemegang jabatan itu **saat step menjadi aktif**, dengan fallback ke `hr_administrator` bila jabatan kosong. Pegawai tidak boleh menyetujui pengajuannya sendiri.

**11.7 Payroll.**
- Alur status `draft → calculated → approved → paid → locked`; setelah `locked` tidak bisa diubah, koreksi lewat run susulan.
- Approver run ≠ pembuat run (four-eyes).
- Potongan keterlambatan/absen bersumber dari `daily_attendances` periode itu; cuti tidak dibayar dari `leave_requests` dengan `is_paid = false`.
- Pajak PPh 21 dan BPJS diperlakukan sebagai komponen ber-`calc = formula`; tarif disimpan sebagai konfigurasi tenant, bukan hardcoded.

**11.8 Kontrak.** Notifikasi otomatis (modul `Notification`) ke SDM 60/30/7 hari sebelum `employee_contracts.ends_at` (blueprint: "Kontrak pegawai akan berakhir").

---

## 12. Rancangan Target — Alur Kerja

**Onboarding pegawai/dosen baru**
```
1. SDM: POST /employees {employee_number, employee_type, data diri, hired_at}
2. SDM: POST /employees/{id}/assignments {position_id, work_unit_id, is_primary}
3. SDM: POST /employees/{id}/contracts (bila kontrak)
4. (dosen) SDM: POST /lecturers {employee_id, nidn, faculty_id}
5. SDM: POST /employees/{id}/account → user dibuat, role employee/lecturer
   → email undangan set password (modul Notification)
6. SDM: unggah dokumen (KTP, ijazah, SK) → verifikasi
```

**Pengajuan cuti**
```
1. Pegawai: GET /me/leave-balances
2. Pegawai: POST /me/leave-requests → ApprovalRequest dibuat
3. Atasan langsung: approve/reject di ApprovalWorkflow (step 1)
4. (opsional, per leave_type) SDM: approve (step 2)
5. Approved → saldo berkurang, daily_attendances ditandai leave, notifikasi ke pegawai
```

**Presensi harian**
```
1. Pegawai: POST /me/attendance/check-in (mobile: GPS + foto)
2. Pegawai: POST /me/attendance/check-out
3. Job akhir hari → daily_attendances
4. Lupa check-out → POST /me/attendance-corrections → approval atasan
```

**Payroll bulanan**
```
1. Payroll: POST /payroll-runs {period}
2. Payroll: POST /payroll-runs/{run}/calculate (ambil komponen + rekap presensi + cuti)
3. Approver: PATCH /payroll-runs/{run}/approve
4. Payroll: tandai paid → lock → slip tersedia di /me/payslips
```

**Pegawai keluar**
```
1. SDM: PATCH /employees/{id}/terminate
2. Sistem: akun dinonaktifkan, role dicabut, penempatan & kontrak aktif ditutup per terminated_at
3. Payroll terakhir dihitung proporsional di run periode berjalan
```

---

## 13. Rancangan Target — Dashboard & Laporan

**Dashboard SDM** (kartu baru di `DashboardStatsService`, digate permission SDM):
- Total pegawai per `employee_type` dan `employment_status`
- Hadir / terlambat / cuti / tanpa keterangan hari ini
- Pengajuan cuti & koreksi presensi menunggu
- Kontrak berakhir ≤ 60 hari
- Dokumen kedaluwarsa / belum diverifikasi
- Chart: komposisi per unit (`staff_by_unit` diganti berbasis `work_units`, bukan pencocokan string), tren kehadiran bulanan

**Dashboard Pegawai** (blueprint "Dashboard Pegawai", self-service): status presensi hari ini, saldo cuti, pengajuan berjalan, slip gaji terakhir, tugas aktif, pengumuman.

**Laporan SDM** (`ReportService`, perluas `buildSdm()` + jenis baru): daftar pegawai (dengan filter unit/status), rekap kehadiran, rekap cuti, kontrak, beban kerja, kinerja, rekap payroll (hanya untuk `payroll.read`). Semua bisa diekspor (pola `exams/{exam}/recap/export` yang sudah ada).

---

## 14. Rancangan Target — Frontend & Mobile

**Web (`frontend/src`)**
- Pindahkan `LecturersPage`/`EmployeesPage` ke grup menu baru **"SDM"** di `constants/nav.ts`: Pegawai, Dosen, Unit & Jabatan, Kontrak, Cuti, Presensi, Kinerja, Pelatihan, Payroll, Laporan SDM.
- `EmployeesPage` mendapat form tambah/ubah (pola `LecturerFormModal`: `Modal` + `react-hook-form` + `zod`), aksi "Nonaktifkan/Keluarkan" menggantikan "Hapus", tab detail: Profil, Penempatan, Kontrak, Dokumen, Riwayat, Cuti, Presensi.
- Form dosen mendapat pemilih fakultas (saat ini belum ada endpoint `GET /faculties`; perlu ditambahkan).
- Grup menu **"Saya"** untuk self-service (`employee_self.*`): Profil, Presensi, Cuti, Slip Gaji, Kinerja.
- Gating menu juga memeriksa modul tenant `hr`/`payroll` aktif.

**Mobile (Flutter, `mobile/lib/features/hr`)** — prioritas pada self-service: check-in/out dengan GPS + foto, pengajuan cuti, persetujuan cuti untuk atasan, slip gaji (PDF), notifikasi push.

---

## 15. Keamanan, Privasi & Audit

- **Field sensitif** (NIK, NPWP, rekening, gaji, kontrak bernilai) disimpan dengan cast `encrypted`, dikeluarkan lewat resource terpisah (`EmployeeSensitiveResource`) yang hanya dipakai endpoint ber-`employee_sensitive.read`. `EmployeeResource` biasa tidak pernah memuat field ini. Mengikuti pola pemisahan `ExamQuestionResource` dosen vs mahasiswa.
- **Self-service** tidak menerima `employee_id` dari klien; selalu `auth()->user()->employee`, dicek ulang di policy objek.
- **Audit log** wajib untuk: create/update/terminate pegawai, perubahan field sensitif, penyesuaian saldo cuti, koreksi presensi, seluruh transisi payroll run, pemberian role lewat provisioning akun.
- **Isolasi tenant** tetap via `TenantScoped`; validasi FK (unit, jabatan, fakultas) memakai `findOrFail` ter-scope seperti `LecturerController::store()`, bukan `Rule::exists`.
- **Bukti presensi** (foto, koordinat) punya masa retensi (keputusan §17) dan hanya bisa dilihat SDM + pegawai bersangkutan.
- Ekspor laporan yang memuat data sensitif dicatat di audit log beserta pengekspornya.

---

## 16. Rencana Implementasi Bertahap

**Fase 0 — Perbaikan cepat (tanpa skema baru)**
1. Tambah `reports.read` ke `hr_administrator` (§6.3).
2. Ganti hard delete dosen dengan soft delete atau tolak delete bila sudah dipakai; tambah audit (§6.1).
3. Tambah `employees.create/update/delete` + endpoint tulis + form `EmployeesPage` dengan field yang sudah ada (§6.2).
4. Perbarui README.md §Akun Sample baris `sdm` dan `pegawai`.
5. Tegakkan gating modul tenant `hr` di route & menu.

**Fase 1 — Fondasi (P1)**: buat `app/Modules/HumanResource`; perluas `employees` (§8.1) + `employee_id` di `lecturers` dengan seeder backfill; `work_units`, `positions`, `employee_assignments`; identity link `employees.user_id` + `POST employees/{id}/account`; resolver `ApprovalApproverType::Position`.

**Fase 2 — Administrasi (P2)**: kontrak & riwayat, dokumen (reuse `FileManagement`), cuti + saldo + libur + approval, self-service profil/cuti di web, permission `employee_self.*` untuk `employee` dan `lecturer`.

**Fase 3 — Operasional (P3)**: presensi (shift, lokasi, check-in/out, job rekap, koreksi), Flutter self-service, penilaian kinerja, dashboard SDM & pegawai.

**Fase 4 — Lanjutan (P4)**: payroll + slip gaji + role `payroll_administrator`, pelatihan, tugas pegawai, integrasi mesin fingerprint, laporan lengkap.

Setiap fase menyertakan feature test per endpoint (pola `LecturerControllerTest`), skenario black-box baru di [BLACK-BOX-TESTING.md](./BLACK-BOX-TESTING.md), dan uji isolasi tenant.

---

## 17. Keputusan Terbuka

1. **Satu induk vs dua tabel paralel.** Rancangan ini memilih `employees` sebagai induk dan `lecturers` sebagai ekstensi. Alternatifnya mempertahankan dua tabel terpisah (lebih sedikit migrasi, tapi cuti/presensi/payroll harus polimorfik).
2. **Letak `user_id` dosen:** di `employees` saja (diturunkan ke dosen lewat `employee_id`) atau juga di `lecturers` (sesuai rekomendasi RANCANGAN-AKUN-DOSEN.md §11 poin 2). Disarankan cukup di `employees` agar tidak ada dua sumber kebenaran.
3. **Siapa melihat data sensitif:** apakah `university_owner`/`rector`/`auditor` boleh `employee_sensitive.read`.
4. **Approval cuti:** cukup atasan langsung, atau atasan + SDM; apakah dapat dikonfigurasi per `leave_type`.
5. **Aturan carry-over cuti** dan kuota default (mis. 12 hari/tahun).
6. **Metode presensi wajib** per tenant: web, mobile+GPS, foto, fingerprint — dan masa retensi foto/koordinat.
7. **Payroll:** dibangun di dalam CivitasOne atau cukup ekspor ke sistem payroll eksternal.
8. **Kewenangan SDM memberi role:** daftar putih role yang boleh diberikan SDM (§11.3).
