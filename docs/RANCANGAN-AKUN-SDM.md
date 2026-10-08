# Rancangan Akun & Modul SDM (Kepegawaian)

> **Status:** Dokumen desain, disusun dari kondisi kode aktual (backend `app/Modules/Academic` untuk data Dosen/Pegawai, `app/Modules/Report`, `app/Modules/Dashboard`, `app/Modules/ApprovalWorkflow`, `app/Modules/Notification`, `app/Modules/AuditLog`, `app/Modules/FileManagement`, `OrganizationalRoleSeeder`, frontend `frontend/src`) per sesi ini, ditambah rancangan modul SDM yang belum dibangun.
> **Dibuat:** 2026-10-06 · **Revisi:** 2026-10-06 (struktur disusun ulang mengikuti 22 modul SDM yang ditetapkan)
> Pelengkap [RANCANGAN-APLIKASI.md](./RANCANGAN-APLIKASI.md) §3.9 (Pegawai), §3.11 (Bagian SDM), §4.4 (Manajemen Dosen), §4.5 (Manajemen Pegawai), §4.13 (Tugas Pegawai), §4.15 (Absensi Dosen & Pegawai), "Laporan SDM", "Dashboard Pegawai", dan Tahap 5 roadmap. Dokumen ini mengikuti pola [RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md) dan [RANCANGAN-AKUN-DOSEN.md](./RANCANGAN-AKUN-DOSEN.md). Nama tabel/model/endpoint yang **sudah ada** merujuk persis ke kode. Yang **baru rancangan** ditandai eksplisit dengan ❌ / *(rancangan)*.

## Daftar Isi

1. [Apa itu "Akun SDM"](#1-apa-itu-akun-sdm)
2. [Peta Role di Sekitar Data Kepegawaian](#2-peta-role-di-sekitar-data-kepegawaian)
3. [Peta 22 Modul SDM](#3-peta-22-modul-sdm)
4. [Struktur Data](#4-struktur-data)
   - [4.1 Skema Aktual](#41-skema-aktual)
   - [4.2 Skema Rancangan Modul `HumanResource`](#42-skema-rancangan-modul-humanresource)
5. [Modul Inti SDM: Fitur Detail](#5-modul-inti-sdm-fitur-detail)
   - [5.1 Dashboard SDM](#51-dashboard-sdm)
   - [5.2 Data Pegawai](#52-data-pegawai)
   - [5.3 Data Dosen](#53-data-dosen)
   - [5.4 Data Tenaga Kependidikan](#54-data-tenaga-kependidikan)
   - [5.5 Riwayat Pendidikan](#55-riwayat-pendidikan)
   - [5.6 Riwayat Jabatan](#56-riwayat-jabatan)
   - [5.7 Riwayat Pekerjaan](#57-riwayat-pekerjaan)
   - [5.8 Dokumen Kepegawaian](#58-dokumen-kepegawaian)
   - [5.9 Kontrak & Status Kepegawaian](#59-kontrak--status-kepegawaian)
   - [5.10 Cuti & Izin](#510-cuti--izin)
   - [5.11 Kehadiran](#511-kehadiran)
   - [5.12 Kinerja Pegawai](#512-kinerja-pegawai)
   - [5.13 Pelatihan & Sertifikasi](#513-pelatihan--sertifikasi)
   - [5.14 Jabatan Akademik](#514-jabatan-akademik)
   - [5.15 Kepangkatan](#515-kepangkatan)
   - [5.16 Penempatan & Mutasi](#516-penempatan--mutasi)
   - [5.17 Pengajuan SDM](#517-pengajuan-sdm)
   - [5.18 Persetujuan](#518-persetujuan)
   - [5.19 Laporan SDM](#519-laporan-sdm)
   - [5.20 Export Data](#520-export-data)
   - [5.21 Notifikasi SDM](#521-notifikasi-sdm)
   - [5.22 Audit Log SDM](#522-audit-log-sdm)
6. [Modul Pendukung Lanjutan (di luar 22 modul inti)](#6-modul-pendukung-lanjutan-di-luar-22-modul-inti)
7. [Permission: Katalog Aktual & Rancangan](#7-permission-katalog-aktual--rancangan)
8. [Endpoint API: Referensi](#8-endpoint-api-referensi)
9. [Aturan Bisnis Kunci](#9-aturan-bisnis-kunci)
10. [Alur Kerja Utama](#10-alur-kerja-utama)
11. [Celah & Risiko](#11-celah--risiko)
12. [Status Frontend & Struktur Menu](#12-status-frontend--struktur-menu)
13. [Ringkasan Gap Implementasi](#13-ringkasan-gap-implementasi)
14. [Rencana Bertahap & Prioritas](#14-rencana-bertahap--prioritas)

---

## 1. Apa itu "Akun SDM"

"Akun SDM" di CivitasOne merujuk ke dua hal yang saling terkait:

1. **Role `hr_administrator`** (label: **"Bagian SDM"**): akun operasional yang mengelola seluruh data dan proses kepegawaian, yaitu 22 modul di §3. Akun demo per tenant: `sdm@<domain>` (mis. `sdm@undigital.test`, `sdm@atmajaya.com`), dibuat oleh `DemoUniversitiesSeeder` dengan `MembershipType::Staff`.
2. **Domain Kepegawaian**: data dan proses tentang *orang yang bekerja* di universitas, yaitu **dosen** dan **tenaga kependidikan** (staf administrasi, laboran, teknisi, pustakawan, dsb.). Saat ini domain ini **belum punya modul sendiri**. Model `Lecturer` dan `Employee` tinggal di `app/Modules/Academic/` sebagai "data induk". Blueprint merencanakan modul terpisah `HumanResource`.

Role pasangan dari sisi pegawai:

- **Role `employee`** (label: **"Pegawai"**): tenaga kependidikan sebagai *pengguna akhir* yang mengajukan cuti, izin, perubahan data, dan melihat data dirinya sendiri. Akun demo `pegawai@<domain>`. Saat ini **sengaja tanpa permission apa pun** (komentar di `OrganizationalRoleSeeder`: *"employee belum punya modul kepegawaian sama sekali"*).
- **Role `lecturer`** (Dosen) juga pengguna akhir untuk hal kepegawaian (cuti, riwayat pendidikan, jabatan akademik, dokumen), di samping fungsi akademiknya.

Hubungannya mirip `academic_administrator` ↔ `student` di Modul Akademik: Bagian SDM adalah operator/back-office, sedangkan pegawai dan dosen adalah subjek data sekaligus pengaju (lewat modul **Pengajuan SDM**, §5.17).

**Kondisi singkat:** dari 22 modul SDM, **1 berjalan penuh** (Data Dosen, dengan field minimal), **6 sebagian** (Dashboard, Data Pegawai, Persetujuan, Laporan, Export, Notifikasi/Audit Log sebagai infrastruktur), dan **sisanya belum dibangun**. Rincian di §3.

---

## 2. Peta Role di Sekitar Data Kepegawaian

Diambil dari `OrganizationalRoleSeeder::ROLE_PERMISSIONS` (kondisi aktual):

| Role (slug) | Label | `lecturers.*` | `employees.*` | `reports.read` | `audit_logs.read` | `approval_requests.read` | Sifat akses ke data SDM |
|---|---|---|---|---|---|---|---|
| `university_owner` | University Owner | read | read | ✅ | ✅ | – | Baca seluruh data SDM. Kelola RBAC & `approval_workflows.*` (merancang alur persetujuan SDM). |
| `university_administrator` | University Administrator | read | read | ✅ | ✅ | – | Sama seperti owner untuk cakupan baca, tanpa `system_settings.update`. |
| `rector` | Rektor | read | read | ✅ | ✅ | ✅ | Monitoring + menyetujui pengajuan tingkat pimpinan. |
| `vice_rector` | Wakil Rektor | read | **–** | ✅ | – | ✅ | Tidak melihat menu Pegawai. |
| `dean` | Dekan | read | **–** | ✅ | – | ✅ | Baca dosen (belum ter-scope ke fakultasnya). |
| `head_of_study_program` | Ketua Program Studi | read | **–** | ✅ | – | ✅ | Sama seperti Dekan. |
| `academic_administrator` | Bagian Akademik | **–** | **–** | ✅ | – | ✅ | Tidak melihat direktori dosen/pegawai. |
| **`hr_administrator`** | **Bagian SDM** | **read, create, update, delete** | **read** | **–** | **–** | **–** | **Akun SDM inti.** Satu-satunya role non-owner dengan hak tulis Dosen. Juga `users.read`, `user_roles.read`. |
| `employee` | Pegawai | – | – | – | – | – | **Nol permission.** |
| `lecturer` | Dosen | – | – | – | – | – | Tidak melihat direktori dosen/pegawai. |
| `auditor` | Auditor | read | read | ✅ | ✅ | ✅ | Baca untuk audit. |

**Catatan penting:**

- `hr_administrator` **tidak punya** `reports.read`, `audit_logs.read`, maupun `approval_requests.read`. Tiga modul inti (Laporan SDM, Audit Log SDM, Persetujuan) tidak terjangkau oleh Bagian SDM sendiri. Lihat §11 poin 1.
- `super_admin` bypass semua cek lewat `PermissionRegistry::userHasPermission()`, tapi wajib memilih tenant dulu (lihat [RANCANGAN-SUPER-ADMIN.md](./RANCANGAN-SUPER-ADMIN.md)).

---

## 3. Peta 22 Modul SDM

Legenda status: **✅ Berjalan** · **🟡 Sebagian** (ada, tapi cakupan jauh di bawah target) · **📖 Read-only** · **🧩 Ada tapi tidak terjangkau** · **🏗️ Fondasi ada** (infrastruktur generik sudah dibangun, bagian SDM-nya belum) · **❌ Belum dibangun**.

| # | Modul | Keterangan | Status aktual | Fondasi yang dipakai ulang | Fase (§14) |
|---|---|---|---|---|---|
| 1 | **Dashboard SDM** | Ringkasan jumlah dosen, tendik, pegawai aktif, kontrak, cuti, statistik kepegawaian | 🟡 Kartu total dosen/pegawai + grafik staf per unit | `DashboardController`, `DashboardStatsService` | 1–3 |
| 2 | **Data Pegawai** | Data induk seluruh pegawai (dosen + tendik): identitas, NIP/NIDN, jabatan, unit kerja, status, kontak | 📖 `employees` read-only, field minimal | `Employee` | 1 |
| 3 | **Data Dosen** | NIDN/NIDK, pendidikan terakhir, jabatan akademik, bidang keahlian, status dosen, unit/prodi | ✅ CRUD, field minimal (NIDN, nama, email, fakultas) | `Lecturer`, `LecturerController` | 1 |
| 4 | **Data Tenaga Kependidikan** | Staf administrasi, laboran, teknisi, pustakawan, dsb. | ✅ Kategori, penugasan, kompetensi, rekap & rasio per fakultas (2026-10-08) | `Employee` | 1 |
| 5 | **Riwayat Pendidikan** | Pendidikan, institusi, tahun lulus, dokumen pendukung | ❌ | `file_uploads` | 2 |
| 6 | **Riwayat Jabatan** | Jabatan struktural & fungsional + periode menjabat | ❌ | – | 2 |
| 7 | **Riwayat Pekerjaan** | Riwayat pekerjaan/penempatan dari waktu ke waktu | ❌ | – | 2 |
| 8 | **Dokumen Kepegawaian** | KTP, KK, ijazah, sertifikat, SK, kontrak, dll. | 🏗️ | `FileManagement` | 2 |
| 9 | **Kontrak & Status Kepegawaian** | Tetap/kontrak/honorer, periode kontrak, tanggal berakhir | ❌ (hanya `is_active`) | – | 2 |
| 10 | **Cuti & Izin** | Pengajuan, persetujuan, monitoring cuti/izin | 🏗️ | `ApprovalWorkflow` | 3 |
| 11 | **Kehadiran** | Melihat & memonitor kehadiran pegawai (jika presensi terintegrasi) | ❌ | – | 3 |
| 12 | **Kinerja Pegawai** | Penilaian & evaluasi kinerja berkala | ❌ | `ApprovalWorkflow` | 4 |
| 13 | **Pelatihan & Sertifikasi** | Pelatihan, seminar, workshop, sertifikasi, pengembangan kompetensi | ❌ | `file_uploads` | 4 |
| 14 | **Jabatan Akademik** | Khusus dosen: Asisten Ahli → Lektor → Lektor Kepala → Profesor | ❌ | – | 2 |
| 15 | **Kepangkatan** | Pangkat/golongan & riwayat kenaikan pangkat | ❌ | – | 2 |
| 16 | **Penempatan & Mutasi** | Perpindahan antar unit, fakultas, prodi, atau jabatan | ❌ | `ApprovalWorkflow` | 3 |
| 17 | **Pengajuan SDM** | Cuti, perubahan data, kenaikan jabatan, mutasi, administrasi lain | 🏗️ | `ApprovalWorkflow` (`requestable` morph) | 3 |
| 18 | **Persetujuan** | Daftar pengajuan yang butuh tindakan SDM: menunggu/disetujui/ditolak/dikembalikan | 🟡 Inbox generik ada, belum ada status "dikembalikan ke pemohon" | `ApprovalWorkflow` | 0, 3 |
| 19 | **Laporan SDM** | Jumlah pegawai, komposisi dosen, status, pendidikan, jabatan, pangkat, statistik | 🧩 Satu laporan rekap, tidak terjangkau Bagian SDM | `ReportService` | 0, 1–4 |
| 20 | **Export Data** | Export ke Excel/PDF | 🟡 Hanya CSV di Laporan | `ReportController::streamCsv`, `barryvdh/laravel-dompdf` | 1 |
| 21 | **Notifikasi SDM** | Kontrak habis, dokumen kedaluwarsa, pengajuan baru, kenaikan jabatan, agenda | 🏗️ | `Notification` (template, kanal, dispatcher) | 2–3 |
| 22 | **Audit Log SDM** | Catat tambah/ubah/hapus/approve & perubahan data kepegawaian | 🏗️ Trait `Auditable` ada, **belum dipasang** di `Lecturer`/`Employee` | `AuditLog` | 0 |

---

## 4. Struktur Data

### 4.1 Skema Aktual

ULID sebagai primary key, `university_id` wajib, model memakai `TenantScoped` + `ScopesToInstitution` (lihat [MULTI-TENANT-ARCHITECTURE.md](./MULTI-TENANT-ARCHITECTURE.md)):

```
universities (Tenancy)
 ├─ faculties                code, name, is_active
 │   └─ lecturers            nidn, name, email, is_active, faculty_id (nullable, nullOnDelete)
 │                           unique(university_id, nidn)
 │                           index(university_id, faculty_id)
 │
 └─ employees                unit_kerja (string bebas), name, email, position (string bebas),
                             is_active
                             index(university_id, unit_kerja)
                             (TIDAK ada nomor induk pegawai, TIDAK ada unique key bisnis)

Tabel lain yang merujuk lecturers:
 theses.supervisor_lecturer_id        → lecturers.id  (nullOnDelete)
 internships.supervisor_lecturer_id   → lecturers.id  (nullOnDelete)

Tidak ada tabel yang merujuk employees.

Infrastruktur generik yang akan dipakai ulang:
 approval_workflows / approval_workflow_steps      alur persetujuan per tenant
 approval_requests (requestable morph)             satu pengajuan
   ├─ approval_request_steps                       status: pending|in_review|approved|rejected|skipped
   ├─ approval_actions
   └─ approval_histories
 file_uploads                                      berkas (upload/download/hapus)
 notification_templates / notification_logs / user_notification_preferences
                                                   kanal: database|mail|push|whatsapp|sms
 audit_logs                                        action: created|updated|deleted|restored|exported|imported
```

**Yang tidak ada di skema aktual:** nomor induk pegawai, NIDK, gelar, kategori pegawai (dosen/tendik) dan jenis tendik, prodi homebase dosen, jabatan akademik, pangkat, status kepegawaian, tanggal mulai kerja, atasan langsung, seluruh tabel riwayat, kontrak, cuti, kehadiran, kinerja, pelatihan, dokumen. Juga identity link `lecturers.user_id` / `employees.user_id` → `users.id` (bandingkan `students.user_id` yang sudah ada). `unit_kerja` dan `position` di `employees` masih **teks bebas**.

### 4.2 Skema Rancangan Modul `HumanResource`

*(rancangan, belum ada satu pun tabel di bawah ini)*

Usulan: modul baru `app/Modules/HumanResource/`. `Lecturer`/`Employee` tetap di `Academic` untuk fase awal supaya relasi yang sudah ada (`theses`, `internships`, `ReportService`, `DashboardStatsService`) tidak pecah. Arah ketergantungan dijaga satu arah: **HumanResource → Academic**, tidak pernah sebaliknya.

**Keputusan desain kunci: satu identitas kepegawaian (`personnel`) untuk dosen dan tendik.** Modul *Data Pegawai* (§5.2) adalah master seluruh pegawai. *Data Dosen* (§5.3) dan *Data Tenaga Kependidikan* (§5.4) adalah tampilan khusus di atasnya, masing-masing dengan atribut tambahan. Proses yang berlaku sama untuk keduanya (riwayat, dokumen, kontrak, cuti, kehadiran, kinerja, pelatihan, kepangkatan, mutasi, pengajuan) cukup satu tabel, bukan `lecturer_x` + `employee_x`.

```
── MASTER PEGAWAI (§5.2–5.4) ─────────────────────────────────────────────
personnel                         satu baris per orang yang bekerja di kampus
  id, university_id
  personnel_type                  enum: lecturer | education_staff
  personable_type, personable_id  morph → Lecturer | Employee (unique per tenant)
  user_id (nullable, unique)       identity link → users.id
  employee_number                  NIP/nomor pegawai, unique(university_id, employee_number)
  front_title, back_title          gelar depan/belakang
  full_name, gender, religion, marital_status
  birth_place, birth_date
  national_id (NIK, terenkripsi), family_card_number (KK, terenkripsi)
  email_office, email_personal, phone, address, emergency_contact_name, emergency_contact_phone
  employment_status_id → employment_statuses          (§5.9)
  work_unit_id → work_units                           (§5.16)
  structural_position_id → positions (nullable)       (§5.6)
  functional_position_id → positions (nullable)       (§5.6 / §5.14)
  rank_id → ranks (nullable)                          (§5.15)
  supervisor_personnel_id → personnel (nullable)      atasan langsung
  highest_education_id → personnel_educations (nullable, denormalisasi §5.5)
  joined_at, retired_at (nullable), retirement_age (default dari setting)
  is_active, inactive_reason (resign|retired|deceased|terminated|other)
  photo_file_id → file_uploads
  bank_name, bank_account_number, bank_account_name, tax_number (NPWP)   (terenkripsi)

lecturer_profiles                 atribut khusus dosen (§5.3), 1:1 dengan personnel
  personnel_id
  nidn (sinkron dgn lecturers.nidn), nidk, nuptk
  lecturer_status                 enum: permanent | non_permanent | guest | honorary (LB)
  homebase_study_program_id → study_programs
  expertise_field, expertise_subfield
  academic_rank_id → academic_ranks (nullable, denormalisasi §5.14)
  credit_points (angka kredit kumulatif)
  serdos_number, serdos_year      sertifikat pendidik (ringkasan, detail di §5.13)
  sinta_id, scopus_id, google_scholar_id (opsional)

education_staff_profiles          atribut khusus tendik (§5.4), 1:1 dengan personnel
  personnel_id
  staff_category                  enum: administration | laboratory | technician | librarian |
                                        archivist | it_staff | finance | security | driver | other
  functional_role                 mis. Pranata Laboratorium, Pustakawan Ahli, Pranata Komputer
  assigned_laboratory / assigned_facility (nullable, teks/relasi ke ruang)
  competency_certifications_summary

── REFERENSI ORGANISASI ──────────────────────────────────────────────────
work_units                        pohon unit kerja
  code, name, type (rectorate|faculty|study_program|bureau|unit|section|laboratory|library)
  parent_id → work_units, faculty_id / study_program_id (nullable), head_personnel_id, is_active
positions                         code, name, category (structural|functional_academic|functional_staff),
                                  grade_level, is_unique_holder (bool, mis. Dekan = satu orang), is_active
academic_ranks                    code, name (Asisten Ahli|Lektor|Lektor Kepala|Profesor),
                                  min_credit_points, order
ranks                             code, name (III/a…IV/e atau skema yayasan), order
employment_statuses               code, name (permanent|contract|honorary|probation|outsourcing|other),
                                  requires_contract (bool)

── RIWAYAT (§5.5–5.7, §5.14–5.16) ────────────────────────────────────────
personnel_educations              degree (SD…S3, Profesi, Spesialis), institution, country,
                                  major, entry_year, graduation_year, gpa, thesis_title,
                                  certificate_number, is_highest, diploma_file_id, transcript_file_id,
                                  verification_status (pending|verified|rejected), verified_by, verified_at
personnel_position_histories      position_id, position_category, work_unit_id,
                                  decree_number, decree_date, effective_from, effective_until,
                                  is_current, decree_file_id
personnel_work_histories          kind (internal_placement|external_employment)
                                  internal: work_unit_id, position_id, placement_history_id
                                  eksternal: company_name, job_title, start_date, end_date, reason_left,
                                  reference_file_id
academic_rank_histories           academic_rank_id, credit_points, decree_number, tmt (terhitung mulai
                                  tanggal), decree_file_id, status (proposed|approved|active)
rank_histories                    rank_id, decree_number, tmt, kind (regular|selection|adjustment),
                                  next_eligible_at, decree_file_id
placement_histories               (mutasi) from_work_unit_id, to_work_unit_id,
                                  from_position_id, to_position_id, kind (transfer|promotion|demotion|
                                  rotation|secondment), decree_number, effective_from, reason,
                                  hr_request_id (nullable), status (scheduled|applied|cancelled)

── DOKUMEN & KONTRAK (§5.8–5.9) ──────────────────────────────────────────
document_types                    code, name, is_mandatory, has_expiry, allowed_mimes, max_size_kb
personnel_documents               personnel_id, document_type_id, number, issued_at, expires_at,
                                  file_upload_id, status (pending|verified|rejected|expired),
                                  verified_by, verified_at, rejection_note
employment_contracts              personnel_id, employment_status_id, contract_number,
                                  contract_type (pkwt|pkwtt|honorary|outsourcing),
                                  start_date, end_date (nullable), base_salary (terenkripsi, opsional),
                                  previous_contract_id (perpanjangan), status (draft|active|expired|
                                  terminated), termination_reason, file_upload_id

── CUTI & KEHADIRAN (§5.10–5.11) ─────────────────────────────────────────
leave_types                       code, name, annual_quota_days, max_days_per_request,
                                  requires_attachment, is_paid, counts_working_days_only,
                                  applies_to (lecturer|education_staff|all), gender_restriction
leave_balances                    personnel_id, leave_type_id, year, quota, used, carried_over
leave_requests                    personnel_id, leave_type_id, start_date, end_date, days, reason,
                                  attachment_file_id, delegate_personnel_id (pengganti tugas),
                                  status (draft|pending|returned|approved|rejected|cancelled),
                                  hr_request_id → hr_requests
holidays                          date, name, is_national
work_schedules                    name, check_in_time, check_out_time, late_tolerance_minutes, work_days
personnel_schedules               personnel_id, work_schedule_id, effective_from
attendance_sources                name, type (manual|fingerprint_import|api|mobile_gps), config (json)
employee_attendances              personnel_id, date, check_in_at, check_out_at, source_id,
                                  status (present|late|absent|leave|sick|business_trip|holiday|wfh),
                                  late_minutes, early_leave_minutes, notes
                                  unique(personnel_id, date)
attendance_corrections            attendance_id, requested_values (json), reason, hr_request_id

── KINERJA & PELATIHAN (§5.12–5.13) ──────────────────────────────────────
performance_periods               name, start_date, end_date, status (draft|open|review|closed)
performance_indicators            name, category, weight, applies_to, scoring_guide
performance_reviews               personnel_id, performance_period_id, reviewer_personnel_id,
                                  self_score, reviewer_score, final_score, final_grade,
                                  status (self_assessment|reviewer|calibration|final), notes
performance_review_items          review_id, indicator_id, target, achievement, self_score, reviewer_score
trainings                         title, type (training|seminar|workshop|conference|course|certification),
                                  organizer, is_internal, start_date, end_date, hours, location, quota, cost
training_participants             training_id, personnel_id, role (participant|speaker|committee),
                                  status (registered|attended|completed|failed), certificate_file_id
personnel_certifications          personnel_id, name, issuer, number, issued_at, expires_at,
                                  category (serdos|competency|professional|language|other),
                                  file_upload_id, verification_status

── PENGAJUAN & PERSETUJUAN (§5.17–5.18) ──────────────────────────────────
hr_requests                       pengajuan SDM generik (payung semua jenis pengajuan)
  personnel_id, request_type (leave|permission|data_change|education_add|document_upload|
                              academic_rank_promotion|rank_promotion|position_change|transfer|
                              certificate_letter|other)
  subject, description, payload (json: perubahan yang diminta)
  requestable_type/id (nullable, mis. LeaveRequest, PlacementHistory)
  approval_request_id → approval_requests
  status (draft|submitted|returned|approved|rejected|cancelled|applied)
  submitted_at, decided_at, applied_at, applied_by
hr_request_attachments            hr_request_id, file_upload_id, label
```

**Prinsip yang dipertahankan dari Modul Akademik:**

- Seluruh tabel memakai `university_id` + `TenantScoped`. Akses by-id lintas tenant → 404, bukan 403.
- Validasi foreign key yang merujuk data tenant memakai `findOrFail` ter-scope, bukan `Rule::exists` (pola `LecturerController::store()`), supaya keberadaan data tenant lain tidak bocor.
- Unique key bisnis selalu komposit dengan `university_id` (pola `unique(university_id, nidn)`).
- Data sensitif (NIK, KK, rekening, NPWP, gaji) dienkripsi di kolom (`encrypted` cast) dan hanya ditampilkan di Resource jika user punya `personnel_sensitive.read` (§7).
- Seluruh model SDM memakai trait `Auditable` (§5.22).
- Data referensi (jenis cuti, jenis dokumen, status kepegawaian, pangkat, kategori tendik) adalah **tabel per tenant**, bukan enum hardcode. Pelajaran dari skala nilai huruf & batas SKS di Akademik ([RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md) §6.1–6.2).

---

## 5. Modul Inti SDM: Fitur Detail

### 5.1 Dashboard SDM

**Status: 🟡 Sebagian.** `DashboardController` + `DashboardStatsService`, digerbangi permission:

| Item aktual | Permission | Terlihat oleh `hr_administrator`? |
|---|---|---|
| `total_lecturers` | `lecturers.read` | ✅ |
| `total_employees` | `employees.read` | ✅ |
| Grafik `staff_by_unit` (dosen per fakultas + pegawai per `unit_kerja`) | `lecturers.read` **dan** `employees.read` | ✅ |

**Rancangan Dashboard SDM** (endpoint khusus `GET /hr/dashboard`, terpisah dari dashboard umum supaya tidak membebani `DashboardController`):

| Kelompok | Widget | Sumber |
|---|---|---|
| Ringkasan | Total pegawai, total dosen, total tendik, pegawai aktif, pegawai nonaktif bulan ini | `personnel` |
| Komposisi | Dosen per jabatan akademik; dosen per pendidikan terakhir (S2/S3); tendik per kategori; pegawai per status kepegawaian; per unit kerja; per gender; per kelompok usia | §5.3–5.5, §5.9, §5.14 |
| Kontrak | Kontrak aktif, kontrak berakhir ≤ 30/60/90 hari (daftar + tautan) | §5.9 |
| Cuti & kehadiran | Pegawai cuti hari ini, pengajuan cuti menunggu, % kehadiran bulan ini, terlambat hari ini | §5.10–5.11 |
| Pengajuan | Pengajuan SDM menunggu tindakan saya, dikembalikan, rata-rata waktu proses | §5.17–5.18 |
| Kepatuhan | Dokumen wajib belum lengkap, dokumen kedaluwarsa ≤ 30 hari, pegawai mendekati pensiun ≤ 12 bulan, kenaikan pangkat jatuh tempo | §5.8, §5.15 |
| Pengembangan | Jam pelatihan per pegawai tahun ini, sertifikasi akan kedaluwarsa, progres periode kinerja | §5.12–5.13 |

Filter: unit kerja, fakultas, prodi, jenis pegawai, rentang tanggal. Setiap widget punya drill-down ke daftar terfilter dan tombol export (§5.20). Widget hanya tampil jika user punya permission sumbernya (pola `SUMMARY_PERMISSIONS`/`CHART_PERMISSIONS` yang sudah ada).

### 5.2 Data Pegawai

**Status: 📖 Read-only.** Model `Modules\Academic\Models\Employee`.

| Aksi aktual | Endpoint | Permission | Catatan |
|---|---|---|---|
| Daftar | `GET /employees` | `employees.read` | `search` (nama, jabatan), `filter[unit_kerja]`, `filter[is_active]` |
| Detail | `GET /employees/{id}` | `employees.read` | `unit_kerja`, `name`, `email`, `position`, `is_active` |

Tidak ada create/update/delete. Katalog `RolePermissionSeeder` untuk `employees` hanya `Read`.

**Rancangan:** Data Pegawai menjadi **master seluruh pegawai** (dosen + tendik) di atas tabel `personnel` (§4.2).

- **Daftar pegawai** gabungan dosen & tendik: kolom foto, NIP, nama bergelar, jenis (Dosen/Tendik), unit kerja, jabatan, status kepegawaian, status aktif. Filter: jenis, unit, status kepegawaian, jabatan, pangkat, aktif/nonaktif, gender. Pencarian: nama, NIP, NIDN, email.
- **Tambah pegawai** (wizard): pilih jenis (Dosen/Tendik) → identitas → kepegawaian (NIP, status, unit, jabatan, atasan, TMT) → kontak → atribut khusus (§5.3 / §5.4). Membuat baris `personnel` + `lecturers`/`employees` + `lecturer_profiles`/`education_staff_profiles` dalam satu transaksi.
- **Profil pegawai** dengan tab: Identitas · Kepegawaian · Pendidikan (§5.5) · Jabatan (§5.6) · Pekerjaan (§5.7) · Pangkat (§5.15) · Jabatan Akademik (§5.14, dosen saja) · Kontrak (§5.9) · Dokumen (§5.8) · Pelatihan & Sertifikasi (§5.13) · Cuti (§5.10) · Kehadiran (§5.11) · Kinerja (§5.12) · Pengajuan (§5.17) · Log Perubahan (§5.22).
- **Ubah data**: oleh Bagian SDM langsung, atau oleh pegawai lewat Pengajuan Perubahan Data (§5.17) yang baru diterapkan setelah disetujui.
- **Nonaktifkan** (resign/pensiun/meninggal/diberhentikan) dengan alasan dan tanggal efektif, **bukan hapus**. Hapus permanen hanya untuk data salah input yang belum punya relasi apa pun (R13, §9).
- **Tautkan akun login** (`personnel.user_id`): prasyarat self-service dan Pengajuan SDM.
- **Import massal** dari Excel dengan template, validasi per baris, laporan baris gagal (§5.20).
- Data sensitif (NIK, KK, rekening, NPWP) tersembunyi kecuali `personnel_sensitive.read`.

**Perbaikan jangka pendek (Fase 1):** sebelum `personnel` ada, tambah kolom `employee_number` + `POST/PUT/DELETE /employees` dengan pola identik `LecturerController`, permission `employees.create/update/delete` untuk `hr_administrator`.

### 5.3 Data Dosen

**Status: ✅ CRUD penuh, field minimal.** Dibangun di commit `310fb70`, dideploy `3dba0d9`. Model `Modules\Academic\Models\Lecturer`.

| Aksi aktual | Endpoint | Permission | Catatan |
|---|---|---|---|
| Daftar | `GET /lecturers` | `lecturers.read` | `search` (nama, NIDN), `filter[faculty_id]`, `filter[is_active]` |
| Detail | `GET /lecturers/{id}` | `lecturers.read` | Menyertakan `faculty_name` |
| Tambah | `POST /lecturers` | `lecturers.create` | 201, "Dosen berhasil ditambahkan." |
| Ubah | `PUT /lecturers/{id}` | `lecturers.update` | Partial (`sometimes`) |
| Hapus | `DELETE /lecturers/{id}` | `lecturers.delete` | **Hard delete** (§11 poin 3) |

Validasi aktual (`StoreLecturerRequest`/`UpdateLecturerRequest`): `nidn` wajib, ≤ 50, **unik per tenant**; `name` wajib, ≤ 255; `email` opsional; `faculty_id` opsional, divalidasi `Faculty::query()->findOrFail()` ter-scope tenant (tenant lain → 404); `is_active` boolean. Test: `LecturerControllerTest.php` (7 test). Hanya `hr_administrator` (dan `super_admin`) yang bisa menulis.

**Rancangan atribut khusus dosen** (`lecturer_profiles`, §4.2):

| Kelompok | Field |
|---|---|
| Identitas dosen | NIDN, NIDK (dosen dengan perjanjian kerja), NUPTK; validasi: minimal salah satu NIDN/NIDK terisi untuk dosen tetap |
| Status dosen | Tetap / Tidak Tetap / Tamu / Luar Biasa (LB); tanggal mulai mengajar |
| Unit | Fakultas, **prodi homebase** (wajib untuk dosen tetap; dasar rasio dosen-mahasiswa per prodi) |
| Kompetensi | Bidang keahlian, sub-bidang |
| Pendidikan terakhir | Ditarik otomatis dari Riwayat Pendidikan (§5.5) yang `is_highest` dan terverifikasi |
| Jabatan akademik | Ditarik dari §5.14 (jabatan aktif + angka kredit) |
| Sertifikasi dosen | Nomor & tahun serdos, ringkasan dari §5.13 |
| Identitas riset | SINTA ID, Scopus ID, Google Scholar ID (opsional) |

**Fitur tambahan:** daftar dosen dengan filter prodi homebase/jabatan akademik/pendidikan/status dosen; indikator "dosen tetap S2 belum S3", "belum serdos", "belum punya NIDN"; ringkasan rasio dosen per prodi.

### 5.4 Data Tenaga Kependidikan

**Status: ✅ Berjalan (2026-10-08).** Dibangun di atas master `employees` (`employee_type = staff`), bukan tabel `education_staff_profiles` terpisah:

| Aspek | Implementasi |
|---|---|
| Kategori tendik | `employees.staff_category` (enum `StaffCategory`: administrasi, laboran, teknisi, pustakawan, arsiparis, pranata komputer/TI, keuangan, keamanan, pengemudi, lainnya). **Wajib** saat tendik ditambahkan; dilarang untuk dosen. Masih enum, belum tabel per tenant. |
| Jabatan fungsional | `employees.position_id` → master `positions` bertipe fungsional (mis. Pranata Laboratorium Pendidikan). Diubah lewat Riwayat Jabatan/Mutasi. |
| Penugasan | `work_unit_id` + `employees.assigned_facility` (laboratorium/fasilitas yang menjadi tanggung jawab). |
| Kompetensi | `employees.competency_summary` (ringkasan); detail sertifikat di Pelatihan & Sertifikasi. |
| Endpoint | `GET /hr/staff` (daftar, filter `staff_category`/`work_unit_id`/`employment_status`/`is_active`), `GET /hr/staff/summary` (rekap), tulis lewat `POST/PUT /hr/employees`. Export `GET /hr/employees/export` ikut filter kategori. |
| Rekap | Total/aktif/nonaktif/belum berkategori; per kategori, per unit kerja, per status kepegawaian; rasio tendik : mahasiswa aktif dan tendik : dosen aktif per fakultas + tingkat universitas. Fakultas tendik = `employees.faculty_id`, atau fakultas unit kerjanya. |
| Frontend | Menu **SDM → Data Tenaga Kependidikan** (`/sdm/tenaga-kependidikan`): daftar + filter, rekap komposisi (klik untuk menyaring), tabel rasio, form tambah/ubah, detail dengan nonaktifkan/aktifkan/hapus. |
| Test | `app/Modules/HumanResource/Tests/Feature/EducationStaffTest.php` |

Rancangan awal (sebelum implementasi):

**Rancangan** (`education_staff_profiles`, §4.2):

| Kelompok | Field |
|---|---|
| Kategori tendik | Administrasi · Laboran · Teknisi · Pustakawan · Arsiparis · Pranata Komputer/TI · Keuangan · Keamanan · Pengemudi · Lainnya (daftar dapat dikonfigurasi per tenant) |
| Jabatan fungsional tendik | mis. Pranata Laboratorium Pendidikan, Pustakawan Ahli Pertama, Arsiparis Terampil |
| Penugasan | Unit kerja; laboratorium/fasilitas yang menjadi tanggung jawab (untuk laboran/teknisi) |
| Kompetensi | Ringkasan sertifikasi kompetensi (detail §5.13) |

**Fitur:** daftar tendik dengan filter kategori/unit/status; rekap jumlah tendik per kategori dan per unit; rasio tendik terhadap mahasiswa/dosen per unit (untuk laporan akreditasi).

### 5.5 Riwayat Pendidikan

**Status: ❌.** Tabel `personnel_educations` (§4.2).

- Tambah/ubah/hapus riwayat pendidikan per pegawai: jenjang (SD…S3, Profesi, Spesialis), institusi, negara, program studi, tahun masuk & lulus, IPK, judul tugas akhir, nomor ijazah.
- Unggah **ijazah** dan **transkrip** (file wajib untuk jenjang D3 ke atas).
- Penanda **pendidikan tertinggi** (`is_highest`) otomatis berdasarkan urutan jenjang; hanya riwayat **terverifikasi** yang dipakai untuk "pendidikan terakhir" di profil dosen/laporan.
- **Verifikasi** oleh Bagian SDM (terverifikasi / ditolak + catatan).
- Pegawai dapat menambah riwayat sendiri lewat Pengajuan SDM jenis `education_add` (§5.17).
- Untuk ijazah luar negeri: kolom nomor penyetaraan.

### 5.6 Riwayat Jabatan

**Status: ❌.** Tabel `personnel_position_histories` + master `positions` (§4.2).

- Master jabatan: **struktural** (Rektor, Wakil Rektor, Dekan, Wakil Dekan, Kaprodi, Kepala Biro, Kepala Bagian, Kepala Lab) dan **fungsional** (jabatan akademik dosen §5.14, jabatan fungsional tendik §5.4).
- Setiap penetapan jabatan disertai SK: nomor, tanggal, TMT, tanggal berakhir (periode menjabat), file SK.
- Satu pegawai boleh memegang satu jabatan struktural dan satu jabatan fungsional sekaligus.
- Jabatan struktural yang `is_unique_holder` (mis. Dekan Fakultas X) hanya boleh dipegang satu orang aktif pada satu waktu (R3, §9).
- Timeline jabatan di profil; daftar pejabat struktural aktif; pengingat periode menjabat berakhir (§5.21).
- Membuka `ApprovalApproverType::Position` di ApprovalWorkflow: `ApproverResolver` saat ini melempar `LogicException` *"Approver berbasis jabatan (Position) belum didukung pada Fase 1, akan diimplementasikan bersama modul Kepegawaian."* Setelah `positions` + `personnel.user_id` ada, resolver mencari user aktif pemegang jabatan X (opsional: di unit kerja pemohon).

### 5.7 Riwayat Pekerjaan

**Status: ❌.** Tabel `personnel_work_histories` (§4.2).

Dua jenis catatan:

1. **Penempatan internal**: dibuat **otomatis** setiap kali Penempatan & Mutasi (§5.16) diterapkan atau saat pegawai pertama kali masuk. Isinya: unit kerja, jabatan, periode. Tidak diinput manual supaya konsisten dengan data mutasi.
2. **Pengalaman kerja eksternal** (sebelum bergabung): nama instansi, jabatan, periode, alasan berhenti, surat referensi/paklaring. Diinput oleh Bagian SDM atau diajukan pegawai.

Ditampilkan sebagai satu timeline gabungan di profil pegawai, dan dipakai untuk menghitung **masa kerja** (internal) serta **total pengalaman** (internal + eksternal).

### 5.8 Dokumen Kepegawaian

**Status: 🏗️ Fondasi ada.** Modul FileManagement (`POST /file-uploads`, `GET /file-uploads/{id}`, `GET /file-uploads/{id}/download`, `DELETE /file-uploads/{id}`).

- Master **jenis dokumen** per tenant: KTP, KK, NPWP, BPJS, ijazah, transkrip, sertifikat, SK pengangkatan, SK jabatan, SK pangkat, kontrak kerja, serdos, surat sehat, paklaring, lainnya. Setiap jenis punya: wajib/tidak, punya masa berlaku/tidak, tipe file & ukuran maksimum.
- Unggah dokumen per pegawai (oleh Bagian SDM, atau pegawai sendiri via Pengajuan SDM jenis `document_upload`).
- **Verifikasi**: menunggu → terverifikasi / ditolak (dengan catatan). Dokumen yang ditolak bisa diunggah ulang.
- **Masa berlaku**: dokumen dengan `expires_at` otomatis berstatus `expired`; notifikasi H-30 (§5.21).
- **Kelengkapan dokumen**: checklist dokumen wajib per pegawai + daftar pegawai yang dokumennya belum lengkap.
- Dokumen yang dihasilkan modul lain (SK di §5.6/§5.14–5.16, ijazah di §5.5, kontrak di §5.9, sertifikat di §5.13) otomatis terdaftar di sini, sehingga tab Dokumen menjadi arsip tunggal.
- Unduh satu dokumen atau ZIP seluruh dokumen pegawai. Setiap unduhan tercatat di Audit Log (§5.22).

### 5.9 Kontrak & Status Kepegawaian

**Status: ❌.** Saat ini hanya ada `is_active` di `lecturers`/`employees`. Tabel rancangan: `employment_statuses`, `employment_contracts` (§4.2).

- Master **status kepegawaian** per tenant: Tetap, Kontrak, Honorer, Masa Percobaan, Outsourcing, Lainnya. Status bertanda `requires_contract` wajib punya kontrak aktif.
- **Kontrak kerja**: nomor, jenis (PKWT/PKWTT/honorer/outsourcing), tanggal mulai & berakhir, file kontrak, (opsional) gaji pokok.
- **Perpanjangan**: membuat kontrak baru yang merujuk `previous_contract_id`, bukan mengubah tanggal kontrak lama, supaya riwayat utuh.
- **Pengakhiran**: status `terminated` + alasan + tanggal efektif; opsi langsung menonaktifkan pegawai.
- **Perubahan status** (mis. Kontrak → Tetap) disertai SK dan tercatat di riwayat.
- Job harian: kontrak lewat `end_date` → `expired`; notifikasi kontrak akan berakhir H-90/H-60/H-30 ke Bagian SDM dan atasan langsung (§5.21).
- Daftar kontrak dengan filter "akan berakhir dalam N hari".

### 5.10 Cuti & Izin

**Status: 🏗️ Fondasi ada.** ApprovalWorkflow (`ApprovalRequestService::submit(Model $requestable, ApprovalWorkflow $workflow, User $requester)`, `requestable` morph) dapat langsung dipakai dengan `LeaveRequest` sebagai `requestable`.

- Master **jenis cuti/izin** per tenant: Cuti Tahunan (mis. 12 hari), Cuti Sakit (lampiran surat dokter jika > N hari), Cuti Melahirkan, Cuti Alasan Penting, Cuti Besar, Cuti di Luar Tanggungan, Izin (jam/setengah hari/hari).
- **Saldo cuti** per tahun: kuota, terpakai, carry-over (maks. N hari, dapat dikonfigurasi).
- **Pengajuan** (pegawai/dosen, self-service): jenis, tanggal, alasan, lampiran, pegawai pengganti tugas. Masuk sebagai Pengajuan SDM jenis `leave`/`permission` (§5.17).
- Perhitungan hari kerja saja (mengikuti jadwal kerja & tabel `holidays`) jika `counts_working_days_only`.
- Validasi: saldo cukup, tidak tumpang tindih dengan cuti lain yang `pending`/`approved`, tanggal mulai ≥ hari ini (kecuali sakit, boleh mundur maks. N hari).
- **Persetujuan berjenjang**: atasan langsung → (opsional) kepala unit → Bagian SDM (§5.18).
- Saat disetujui: kurangi saldo, tandai kehadiran rentang tanggal sebagai `leave` (§5.11). Saat ditolak: saldo tidak berubah. Saat dikembalikan: pemohon dapat merevisi lalu mengajukan ulang.
- Pembatalan oleh pemohon saat masih `pending`; setelah `approved` hanya lewat Bagian SDM (saldo dikembalikan).
- **Monitoring**: kalender cuti per unit, daftar pegawai cuti hari ini, rekap saldo per pegawai.

### 5.11 Kehadiran

**Status: ❌.** Sesuai keterangan modul ("melihat dan memonitor data kehadiran jika sistem presensi terintegrasi"), fokus modul ini adalah **monitoring dan integrasi**, bukan membangun mesin presensi sendiri sebagai prioritas pertama. Ini berbeda dari Absensi Mahasiswa (`attendances`, per `KrsItem` per pertemuan) di Modul Akademik.

| Tahap | Fitur |
|---|---|
| A. Integrasi data | Sumber presensi (`attendance_sources`): import CSV/Excel dari mesin fingerprint, atau API dari sistem presensi pihak ketiga. Pemetaan ID mesin ↔ NIP. Log impor (berhasil/gagal per baris). |
| B. Monitoring | Rekap harian/bulanan per pegawai & per unit: hadir, terlambat, pulang cepat, tidak hadir, cuti, dinas, libur. Status `leave`/`business_trip` otomatis dari §5.10. Highlight pegawai dengan keterlambatan/alpa di atas ambang. |
| C. Aturan | Jadwal kerja & shift, toleransi keterlambatan, kalender libur. Perhitungan `late_minutes` otomatis. |
| D. Koreksi | Pengajuan koreksi kehadiran (lupa absen) lewat Pengajuan SDM → persetujuan atasan → baris diperbarui, nilai lama tercatat di Audit Log. |
| E. Presensi mandiri *(opsional, lanjutan)* | Check-in/out dari aplikasi mobile (GPS, geofence, foto). Hanya dibangun jika kampus tidak punya mesin presensi. Layar Flutter `features/hr_attendance/`. |

### 5.12 Kinerja Pegawai

**Status: ❌.** Tabel `performance_*` (§4.2).

- **Periode penilaian** (semesteran/tahunan) dengan status: draft → dibuka → review → ditutup.
- **Indikator & bobot** per kelompok: dosen (Tridharma: pengajaran, penelitian, pengabdian, penunjang; nilai pengajaran dapat ditarik dari BKD §6) dan tendik (sasaran kerja, kedisiplinan dari §5.11, perilaku kerja). Total bobot wajib 100% (R11).
- **Alur**: penilaian diri → penilaian atasan → kalibrasi Bagian SDM → final. Setiap tahap tercatat.
- **Predikat** dari skor (Sangat Baik/Baik/Cukup/Kurang/Sangat Kurang), rentangnya dapat dikonfigurasi per tenant.
- Riwayat nilai kinerja per pegawai; digunakan sebagai syarat pengajuan kenaikan pangkat/jabatan (§5.14–5.15) dan perpanjangan kontrak (§5.9).
- Rekap kinerja per unit dan distribusi predikat (§5.19).

### 5.13 Pelatihan & Sertifikasi

**Status: ❌.** Tabel `trainings`, `training_participants`, `personnel_certifications` (§4.2).

- **Kegiatan pengembangan**: pelatihan, seminar, workshop, konferensi, kursus, sertifikasi. Internal (diselenggarakan kampus, dengan kuota & pendaftaran) atau eksternal (dicatat setelah diikuti).
- **Peserta**: peran (peserta/pembicara/panitia), status kehadiran & kelulusan, unggah sertifikat.
- **Sertifikasi pegawai**: serdos, sertifikasi kompetensi/profesi, bahasa; nomor, penerbit, tanggal terbit & kedaluwarsa, file, status verifikasi. Notifikasi sertifikasi akan kedaluwarsa (§5.21).
- Pegawai dapat mencatat pelatihan eksternal sendiri lewat Pengajuan SDM (diverifikasi Bagian SDM).
- Rekap jam pelatihan per pegawai per tahun; daftar pegawai belum memenuhi target jam pelatihan (dapat dikonfigurasi).

### 5.14 Jabatan Akademik

**Status: ❌.** Khusus dosen. Tabel `academic_ranks`, `academic_rank_histories` (§4.2).

- Master jenjang: **Tenaga Pengajar → Asisten Ahli → Lektor → Lektor Kepala → Profesor**, masing-masing dengan angka kredit minimum (dapat dikonfigurasi).
- Riwayat jabatan akademik: jenjang, angka kredit, nomor SK, TMT, file SK.
- **Pengajuan kenaikan jabatan akademik** (Pengajuan SDM jenis `academic_rank_promotion`): dosen melampirkan berkas (PAK, karya ilmiah, dsb.) → Kaprodi → Dekan → Bagian SDM → (opsional) Rektor. Status: diusulkan → disetujui internal → SK terbit → aktif.
- Indikator **kelayakan**: masa kerja di jenjang sekarang, angka kredit kumulatif vs syarat jenjang berikutnya, pendidikan (Lektor Kepala/Profesor mensyaratkan S3).
- Statistik komposisi jabatan akademik per prodi/fakultas (bahan akreditasi, §5.19).

### 5.15 Kepangkatan

**Status: ❌.** Tabel `ranks`, `rank_histories` (§4.2).

- Master pangkat/golongan per tenant (skema PNS III/a…IV/e, atau skema internal yayasan).
- Riwayat kenaikan pangkat: pangkat, jenis kenaikan (reguler/pilihan/penyesuaian), nomor SK, TMT, file SK.
- **Kenaikan pangkat berkala**: `next_eligible_at` dihitung dari TMT terakhir + interval (mis. 4 tahun, dapat dikonfigurasi). Daftar pegawai jatuh tempo kenaikan pangkat + notifikasi (§5.21).
- Pengajuan kenaikan pangkat lewat Pengajuan SDM jenis `rank_promotion` (syarat: nilai kinerja 2 periode terakhir minimal "Baik", dapat dikonfigurasi).

### 5.16 Penempatan & Mutasi

**Status: ❌.** Tabel `work_units`, `placement_histories` (§4.2).

- **Unit kerja** berbentuk pohon (Rektorat → Biro → Bagian; Fakultas → Prodi; Laboratorium; Perpustakaan), sinkron opsional dengan `faculties`/`study_programs`, dengan kepala unit (dasar approver "atasan" pada persetujuan). Menggantikan `employees.unit_kerja` teks bebas; migrasi awal dari `DISTINCT unit_kerja`.
- Jenis perpindahan: **mutasi** (antar unit/fakultas/prodi), **promosi**, **demosi**, **rotasi**, **penugasan sementara** (secondment).
- Setiap perpindahan disertai SK dan TMT. Perpindahan dengan TMT di masa depan berstatus `scheduled` dan diterapkan otomatis oleh job harian ke `personnel.work_unit_id`/jabatan, sekaligus membuat baris Riwayat Pekerjaan (§5.7) dan Riwayat Jabatan (§5.6).
- Dapat diinisiasi oleh Bagian SDM langsung, atau lewat Pengajuan SDM jenis `transfer`/`position_change` (oleh pegawai atau atasan) dengan persetujuan.
- Untuk dosen: perubahan prodi homebase juga mengubah `lecturer_profiles.homebase_study_program_id` dan `lecturers.faculty_id`.
- Bagan organisasi read-only dari pohon unit kerja + pejabat aktif.

### 5.17 Pengajuan SDM

**Status: 🏗️ Fondasi ada.** Tabel payung `hr_requests` (§4.2) di atas ApprovalWorkflow.

**Satu pintu untuk semua pengajuan pegawai.** Setiap jenis pengajuan memakai pola yang sama: pegawai mengisi form → `hr_requests` dibuat → `ApprovalRequestService::submit()` dengan workflow yang dipetakan ke `request_type` → setelah disetujui, **handler** per jenis menerapkan perubahan.

| Jenis (`request_type`) | Isi pengajuan | Diterapkan saat disetujui |
|---|---|---|
| `leave` / `permission` | Cuti/izin (§5.10) | Saldo berkurang, kehadiran ditandai |
| `data_change` | Perubahan identitas/kontak/rekening/NPWP; `payload` berisi nilai lama & baru per field, + lampiran bukti | Field `personnel` diperbarui, nilai lama tercatat di Audit Log |
| `education_add` | Riwayat pendidikan baru + ijazah (§5.5) | Baris `personnel_educations` terverifikasi |
| `document_upload` | Dokumen kepegawaian (§5.8) | Dokumen berstatus terverifikasi |
| `academic_rank_promotion` | Kenaikan jabatan akademik (§5.14) | Baris `academic_rank_histories` |
| `rank_promotion` | Kenaikan pangkat (§5.15) | Baris `rank_histories` |
| `position_change` / `transfer` | Kenaikan jabatan / mutasi (§5.16) | Baris `placement_histories` (`scheduled`/`applied`) |
| `attendance_correction` | Koreksi kehadiran (§5.11) | Baris kehadiran diperbarui |
| `certificate_letter` | Surat keterangan kerja/aktif mengajar | Surat PDF dibuat (template) & dilampirkan |
| `other` | Kebutuhan administrasi lain (teks bebas + lampiran) | Ditutup manual oleh Bagian SDM |

- Halaman **"Pengajuan Saya"** untuk pegawai/dosen: daftar pengajuan, status, timeline persetujuan, revisi saat dikembalikan, batalkan saat masih menunggu.
- Halaman **"Semua Pengajuan"** untuk Bagian SDM: filter jenis/status/unit/tanggal.
- Bagian SDM juga dapat membuat pengajuan **atas nama** pegawai (mis. pegawai tidak punya akun).
- Perubahan diterapkan **di dalam transaksi** bersama event `ApprovalRequestApproved`; jika handler gagal, status pengajuan tetap `approved` tetapi `applied_at` kosong dan muncul di antrean "gagal diterapkan" untuk ditangani Bagian SDM.

### 5.18 Persetujuan

**Status: 🟡 Sebagian.** Modul ApprovalWorkflow generik sudah ada:

| Aktual | Endpoint | Catatan |
|---|---|---|
| Kelola alur | `GET/POST/DELETE /approval-workflows` | `approval_workflows.*` (owner) |
| Daftar & detail pengajuan | `GET /approval-requests`, `GET /approval-requests/{id}` | Tanpa middleware permission, disaring policy |
| Setujui / tolak / delegasikan | `POST /approval-request-steps/{step}/approve\|reject\|delegate` | Dicek `ApprovalRequestStepPolicy` |

Status request yang ada: `submitted`, `in_progress`, `approved`, `rejected`. Status step: `pending`, `in_review`, `approved`, `rejected`, `skipped`. Aksi saat tolak (`ApprovalRejectAction`): `stop_workflow` atau `return_to_previous_step`. Notifikasi `ApprovalStepAssigned` & `ApprovalRequestDecided` sudah ada. Frontend: menu Persetujuan (Pengajuan + Alur Persetujuan).

**Kekurangan untuk kebutuhan SDM:**

1. **Belum ada status "dikembalikan" ke pemohon.** `return_to_previous_step` mengembalikan ke step approver sebelumnya, bukan ke pemohon untuk revisi. Rancangan: tambah aksi `return` (`POST /approval-request-steps/{step}/return` dengan catatan wajib) → request berstatus **`returned`**, pemohon merevisi lalu `POST /approval-requests/{id}/resubmit` → workflow mulai lagi dari step pertama (atau step yang mengembalikan, dapat dikonfigurasi).
2. **Approver berbasis jabatan/atasan langsung belum didukung** (§5.6). Rancangan: tipe approver `position` dan `direct_supervisor` (dari `personnel.supervisor_personnel_id`), dan `unit_head` (kepala unit pemohon).
3. `hr_administrator` belum punya `approval_requests.read`.

**Rancangan tampilan Persetujuan SDM:** inbox dengan tab **Menunggu Tindakan Saya · Disetujui · Ditolak · Dikembalikan · Semua**; filter jenis pengajuan SDM, unit, tanggal; detail menampilkan isi pengajuan (termasuk diff nilai lama vs baru untuk `data_change`), lampiran, dan timeline `approval_histories`; aksi Setujui / Tolak / Kembalikan / Delegasikan dengan catatan; persetujuan massal untuk pengajuan sejenis (mis. 20 cuti); indikator SLA (lama menunggu).

### 5.19 Laporan SDM

**Status: 🧩 Ada, tapi tidak terjangkau Bagian SDM.** `ReportService::buildSdm()`, `GET /reports/sdm` (`reports.read`), "Dosen dan Pegawai": kolom Nama, Jenis, Unit/Fakultas, Status; ringkasan `total`, `dosen`, `pegawai`; unduh CSV.

Masalah: `hr_administrator` tidak punya `reports.read`, sementara Wakil Rektor/Dekan/Kaprodi/Bagian Akademik bisa melihat daftar pegawai lewat laporan ini tanpa `employees.read` (§11 poin 2).

**Rancangan katalog Laporan SDM:**

| Laporan | Isi | Sumber |
|---|---|---|
| Rekap Pegawai | Jumlah pegawai per jenis, unit, status aktif (yang ada sekarang, diperluas) | §5.2 |
| Komposisi Dosen | Per prodi homebase, status dosen, jabatan akademik, pendidikan, serdos; rasio dosen:mahasiswa per prodi | §5.3, §5.5, §5.14 + `students` |
| Komposisi Tendik | Per kategori, unit, pendidikan | §5.4 |
| Status Kepegawaian | Tetap/kontrak/honorer per unit; kontrak akan berakhir | §5.9 |
| Pendidikan | Distribusi jenjang pendidikan terakhir; dosen S2 vs S3 | §5.5 |
| Jabatan | Pejabat struktural aktif; jabatan fungsional | §5.6 |
| Kepangkatan | Distribusi pangkat; jatuh tempo kenaikan pangkat | §5.15 |
| Mutasi | Daftar perpindahan per periode | §5.16 |
| Cuti | Saldo & penggunaan per jenis, per unit | §5.10 |
| Kehadiran | % hadir, terlambat, alpa per pegawai/unit/bulan | §5.11 |
| Kinerja | Distribusi predikat per periode/unit | §5.12 |
| Pelatihan | Jam pelatihan per pegawai; sertifikasi aktif/kedaluwarsa | §5.13 |
| Demografi | Usia, gender, masa kerja, mendekati pensiun | §5.2 |
| Pengajuan | Volume pengajuan per jenis, rata-rata waktu proses | §5.17 |

Setiap laporan: filter (tanggal, unit, fakultas, prodi, jenis pegawai), grafik + tabel, drill-down, perbandingan antartahun (blueprint), export Excel/PDF (§5.20). Akses dicek per jenis laporan berdasarkan permission sumbernya, bukan hanya `reports.read`.

### 5.20 Export Data

**Status: 🟡 Sebagian.** Saat ini hanya `GET /reports/{type}?export=csv` (`ReportController::streamCsv`). Paket `barryvdh/laravel-dompdf` sudah terpasang (dipakai `ExamController` untuk PDF ujian). Belum ada library Excel.

**Rancangan:**

- **Excel (.xlsx)**: tambah library (mis. `maatwebsite/excel` atau `openspout/openspout` yang lebih ringan untuk data besar). Header bergaya, kolom tanggal/angka bertipe benar, satu sheet per laporan (atau multi-sheet untuk profil lengkap).
- **PDF**: memakai dompdf yang sudah ada, dengan kop surat universitas (logo & nama tenant) dan tanda tangan pejabat (dapat dikonfigurasi).
- **Cakupan export**: setiap daftar (Data Pegawai, Dosen, Tendik, Kontrak, Cuti, Kehadiran, Pengajuan) dan setiap laporan §5.19, **mengikuti filter yang sedang aktif**; profil lengkap satu pegawai (CV/biodata) ke PDF; daftar riwayat hidup dosen untuk akreditasi.
- **Pilihan kolom**: user memilih kolom yang diekspor; kolom sensitif hanya tersedia dengan `personnel_sensitive.read`.
- **Export besar** (> N baris) dijalankan sebagai **job antrean**, file disimpan sementara di FileManagement, user diberi notifikasi + tautan unduh (kedaluwarsa 24 jam).
- **Import** (pasangan dari export): template Excel untuk Data Pegawai dan Kehadiran, validasi per baris, laporan baris gagal.
- Setiap export/import tercatat di Audit Log dengan `AuditAction::Exported`/`Imported` (enum sudah ada).
- Permission terpisah `hr_exports.create` supaya hak melihat tidak otomatis berarti hak mengunduh massal.

### 5.21 Notifikasi SDM

**Status: 🏗️ Fondasi ada.** Modul Notification: `notification_templates` (CRUD, `notification_templates.*`), konfigurasi kanal (`database`, `mail`, `push`, `whatsapp`, `sms`), preferensi per user (`/notification-preferences`), `NotificationDispatcher`, `TemplateRenderer`, `NotificationLog`. Belum ada satu pun event SDM.

**Rancangan event notifikasi SDM** (setiap event = satu `notification_template` dengan key tetap, isi dapat diubah per tenant):

| Key template | Pemicu | Penerima | Waktu |
|---|---|---|---|
| `hr.contract_expiring` | Kontrak akan berakhir | Bagian SDM, atasan, pegawai | H-90, H-60, H-30 (job harian) |
| `hr.contract_expired` | Kontrak lewat tanggal | Bagian SDM | H+0 |
| `hr.document_expiring` | Dokumen/sertifikasi akan kedaluwarsa | Pegawai, Bagian SDM | H-30 |
| `hr.document_incomplete` | Dokumen wajib belum lengkap | Pegawai | mingguan |
| `hr.document_verified` / `hr.document_rejected` | Hasil verifikasi dokumen/pendidikan | Pegawai | saat terjadi |
| `hr.request_submitted` | Pengajuan SDM baru | Approver step pertama, Bagian SDM | saat terjadi (pakai `ApprovalStepAssigned`) |
| `hr.request_returned` | Pengajuan dikembalikan | Pemohon | saat terjadi |
| `hr.request_decided` | Pengajuan disetujui/ditolak | Pemohon | saat terjadi (pakai `ApprovalRequestDecided`) |
| `hr.leave_starting` | Cuti dimulai besok | Pemohon, pengganti tugas, atasan | H-1 |
| `hr.rank_promotion_due` | Jatuh tempo kenaikan pangkat | Bagian SDM, pegawai | H-90 |
| `hr.academic_rank_eligible` | Dosen memenuhi syarat naik jabatan akademik | Dosen, Kaprodi, Bagian SDM | saat syarat terpenuhi |
| `hr.position_term_ending` | Periode jabatan struktural berakhir | Bagian SDM, pimpinan | H-60 |
| `hr.placement_effective` | Mutasi/promosi berlaku | Pegawai, atasan lama & baru | TMT |
| `hr.retirement_upcoming` | Pegawai mendekati usia pensiun | Bagian SDM | H-365, H-180 |
| `hr.performance_period_open` / `hr.performance_review_due` | Periode kinerja dibuka / tenggat penilaian | Pegawai, atasan | saat dibuka, H-7 tenggat |
| `hr.training_reminder` | Pelatihan akan dimulai | Peserta | H-3 |
| `hr.export_ready` | File export besar selesai | Pengunduh | saat selesai |
| `hr.birthday` / `hr.work_anniversary` *(opsional)* | Ulang tahun / ulang tahun kerja | Unit kerja | hari H |

Implementasi: satu scheduled command harian `hr:dispatch-reminders` (idempotent: menyimpan penanda per event per objek supaya tidak terkirim ganda), ditambah listener untuk event real-time. Kanal default per event dapat diatur per tenant; user bisa mematikan kanal lewat preferensi, kecuali notifikasi wajib (kontrak berakhir ke Bagian SDM).

### 5.22 Audit Log SDM

**Status: 🏗️ Fondasi ada, belum terpasang.** Modul AuditLog: trait `Modules\AuditLog\Support\Auditable` (mencatat `created`/`updated` dengan nilai lama & baru/`deleted`/`restored`), `AuditAction` (`created`, `updated`, `deleted`, `restored`, `exported`, `imported`), `GET /audit-logs` (`audit_logs.read`, filter `action`, `user_id`, `auditable_type`).

**Masalah:** trait `Auditable` saat ini hanya dipasang di `Role`, `Permission`, `SystemSetting`, `FeatureFlag`. **`Lecturer` dan `Employee` tidak memakainya**, sehingga tambah/ubah/hapus dosen lewat CRUD yang sudah berjalan **tidak tercatat sama sekali**. `hr_administrator` juga tidak punya `audit_logs.read`.

**Rancangan:**

- Pasang `Auditable` di `Lecturer`, `Employee` (Fase 0), lalu di **seluruh** model SDM baru (§4.2).
- Kolom sensitif (NIK, rekening, NPWP, gaji) dicatat **ter-mask** di `old_values`/`new_values` (mis. `****1234`), supaya audit log tidak menjadi jalur kebocoran.
- Aksi non-CRUD yang juga dicatat: **approve/reject/return/delegate** (diambil dari `approval_histories`, atau tambah `AuditAction::Approved/Rejected/Returned`), **verify** dokumen/pendidikan, **export/import** (§5.20), **unduh dokumen** (§5.8), **lihat data sensitif**, **tautkan akun**.
- **Tampilan Audit Log SDM**: `GET /hr/audit-logs` = `audit_logs` yang difilter ke `auditable_type` model SDM; filter pegawai (subjek), pelaku, aksi, rentang tanggal; detail menampilkan diff nilai lama vs baru per field.
- Tab **"Log Perubahan"** di profil pegawai (§5.2): seluruh perubahan atas pegawai tersebut, lintas tabel.
- Permission terpisah `hr_audit_logs.read` (diberikan ke `hr_administrator`), sehingga Bagian SDM hanya melihat log domain SDM, bukan log RBAC/pengaturan sistem.
- Audit log bersifat append-only: tidak ada endpoint ubah/hapus.

---

## 6. Modul Pendukung Lanjutan (di luar 22 modul inti)

Fitur dari blueprint §3.9/§3.11/§4.4/§4.5/§4.13/§4.15 yang **tidak termasuk** 22 modul inti, dipertahankan sebagai rencana fase akhir. Semuanya bergantung pada modul inti.

| Modul | Ringkasan | Bergantung pada |
|---|---|---|
| **Payroll & Slip Gaji** | Komponen gaji (pendapatan/potongan), periode bulanan (`draft → calculated → approved → paid → locked`), PPh 21 (skema dapat dikonfigurasi), persetujuan SDM → Keuangan → Wakil Rektor II, slip gaji self-service (pemilik saja), ekspor file transfer bank. Permission terpisah `payroll.*`. | §5.9, §5.10, §5.11 |
| **Lembur & Perjalanan Dinas** | Pengajuan lembur (jam, tarif) & dinas (tujuan, tanggal, biaya) lewat Pengajuan SDM; dinas otomatis menandai kehadiran `business_trip`. | §5.11, §5.17 |
| **Surat Peringatan & Disiplin** | SP1/SP2/SP3 dengan masa berlaku, naik level otomatis, hanya terlihat SDM, pimpinan, dan pegawai bersangkutan. | §5.2, §5.8 |
| **Tugas Pegawai** (blueprint §4.13) | Tugas dengan PIC, prioritas, deadline, checklist, reviewer, komentar; status `not_started → in_progress → in_review → revision → done / cancelled`. | §5.16 (atasan/unit) |
| **BKD (Beban Kerja Dosen)** | SKS pengajaran/penelitian/pengabdian/penunjang per semester, verifikasi asesor; riwayat mengajar ditarik dari kelas yang diampu (butuh relasi dosen↔kelas, [RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md) §8 poin 1). | §5.3, §5.12 |
| **Portal Pegawai (Self-Service)** | Untuk role `employee` & `lecturer`: profil saya, pengajuan saya (§5.17), saldo cuti, kehadiran saya, dokumen saya, pelatihan saya, slip gaji. Endpoint prefix `me/…` dengan permission `self_service_hr.*` yang terpisah dari permission admin (pola `exam_participation.*` vs `exam_attempts.*`). Jangan membangun halaman dengan data statis sebelum API siap (pelajaran Portal Mahasiswa, [RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md) §4.7). | identity link `personnel.user_id` |

---

## 7. Permission: Katalog Aktual & Rancangan

### 7.1 Aktual (`RolePermissionSeeder`)

| Resource | Aksi yang ada |
|---|---|
| `lecturers` | read, create, update, delete |
| `employees` | read |
| `reports` | read |
| `audit_logs` | read |
| `approval_workflows` | read, create, delete |
| `approval_requests` | read |
| `notification_templates` | read, create, update |
| `file_uploads` | read, delete |

### 7.2 Rancangan

| Modul | Resource | Aksi | `hr_administrator` | Pimpinan (owner/admin/rektor) | `auditor` | `employee`/`lecturer` |
|---|---|---|---|---|---|---|
| 1 | `hr_dashboard` | read | ✅ | ✅ | ✅ | – |
| 2 | `employees` | create, update, delete | ✅ | – | – | – |
| 2 | `personnel` | read, create, update, deactivate, link_user | ✅ | read | read | – |
| 2 | `personnel_sensitive` | read | ✅ | – | – | – |
| 3 | `lecturers` | (sudah ada) | ✅ | read | read | – |
| 4 | `education_staff` | read, create, update | ✅ | read | read | – |
| 5–7 | `personnel_histories` | read, create, update, delete, verify | ✅ | read | read | – |
| 8 | `personnel_documents` | read, create, verify, delete | ✅ | – | read | – |
| 9 | `employment_contracts` | read, create, update, terminate | ✅ | read | read | – |
| 10 | `leave_requests` / `leave_types` | read, update, cancel / read, create, update | ✅ | read | read | – |
| 11 | `employee_attendances` | read, import, update | ✅ | read | read | – |
| 12 | `performance_reviews` | read, create, update, calibrate | ✅ | read | read | – |
| 13 | `trainings` / `personnel_certifications` | read, create, update, delete, verify | ✅ | read | read | – |
| 14 | `academic_ranks` | read, create, update | ✅ | read | read | – |
| 15 | `ranks` | read, create, update | ✅ | read | read | – |
| 16 | `work_units` / `positions` / `placements` | read, create, update, delete | ✅ | read | read | – |
| 17 | `hr_requests` | read, create (atas nama), update | ✅ | read | read | – |
| 18 | `approval_requests` | read (sudah ada) | ✅ | ✅ | ✅ | – |
| 19 | `reports` | read (sudah ada) + dicek per jenis | ✅ | ✅ | ✅ | – |
| 20 | `hr_exports` | create | ✅ | ✅ | ✅ | – |
| 21 | `notification_templates` | read, update (key `hr.*`) | ✅ | – | – | – |
| 22 | `hr_audit_logs` | read | ✅ | ✅ | ✅ | – |
| Self-service | `self_service_hr` | read, create, update | – | – | – | ✅ |

Hak menyetujui pengajuan bawahan **tidak** datang dari permission global, tetapi dari step ApprovalWorkflow yang ditugaskan ke user tersebut (atasan langsung, kepala unit, pemegang jabatan).

---

## 8. Endpoint API: Referensi

Semua di bawah `auth:sanctum` + resolusi tenant (`X-University-ID`).

### 8.1 Aktual

```
GET    lecturers                         lecturers.read     search: name,nidn  filter: faculty_id,is_active
POST   lecturers                         lecturers.create
GET    lecturers/{lecturer}              lecturers.read
PUT    lecturers/{lecturer}              lecturers.update
DELETE lecturers/{lecturer}              lecturers.delete

GET    employees                         employees.read     search: name,position  filter: unit_kerja,is_active
GET    employees/{employee}              employees.read

GET    reports                           reports.read
GET    reports/sdm[?export=csv]          reports.read

GET    dashboard                         (kartu & grafik sesuai permission)

GET    approval-workflows | POST | DELETE /{id}           approval_workflows.*
GET    approval-requests, approval-requests/{id}
POST   approval-request-steps/{step}/approve|reject|delegate

POST   file-uploads; GET file-uploads/{id}[/download]; DELETE file-uploads/{id}
GET    audit-logs, audit-logs/{id}                         audit_logs.read
GET|POST notification-templates; PUT notification-templates/{id}
GET    notification-channels; PUT notification-channels/{id}
GET|POST notification-preferences
```

### 8.2 Rancangan *(belum ada)*

```
# 1. Dashboard SDM
GET    hr/dashboard                                     hr_dashboard.read   ?work_unit_id&personnel_type&date_from&date_to

# 2–4. Data Pegawai / Dosen / Tendik
GET    personnel                                        personnel.read   filter: personnel_type,work_unit_id,
                                                                         employment_status_id,position_id,rank_id,is_active,gender
POST   personnel                                        personnel.create (wizard: identitas + profil dosen/tendik)
GET    personnel/{id}                                   personnel.read
PUT    personnel/{id}                                   personnel.update
PATCH  personnel/{id}/deactivate                        personnel.deactivate
POST   personnel/{id}/link-user                         personnel.link_user
POST   personnel/import                                 personnel.create
POST   employees; PUT|DELETE employees/{id}             employees.*   (Fase 1, sebelum personnel)
GET|PUT personnel/{id}/lecturer-profile                 lecturers.read / lecturers.update
GET|PUT personnel/{id}/education-staff-profile          education_staff.*

# 5–7. Riwayat
GET|POST       personnel/{id}/educations                personnel_histories.*
PUT|DELETE     personnel-educations/{id}
PATCH          personnel-educations/{id}/verify         personnel_histories.verify
GET|POST       personnel/{id}/position-histories
GET|POST       personnel/{id}/work-histories            (internal: read-only, dibuat otomatis)

# 8. Dokumen
GET|POST       document-types                           personnel_documents.*
GET|POST       personnel/{id}/documents
PATCH          personnel-documents/{id}/verify
GET            personnel/{id}/documents/archive         (ZIP)
GET            personnel-documents/completeness         (daftar kekurangan dokumen wajib)

# 9. Kontrak & Status
GET|POST       employment-statuses
GET|POST       employment-contracts                     employment_contracts.*  filter: expiring_within_days
PUT            employment-contracts/{id}
POST           employment-contracts/{id}/renew
PATCH          employment-contracts/{id}/terminate

# 10. Cuti & Izin
GET|POST       leave-types; GET leave-balances; GET leave-requests; PATCH leave-requests/{id}/cancel
GET            leave-calendar                           ?work_unit_id&month

# 11. Kehadiran
GET            employee-attendances                     employee_attendances.read  filter: personnel_id,work_unit_id,date_from,date_to,status
GET            employee-attendances/summary             (rekap per pegawai/unit/bulan)
POST           employee-attendances/import              employee_attendances.import
PUT            employee-attendances/{id}                employee_attendances.update
GET|POST       attendance-sources, work-schedules, holidays

# 12. Kinerja
GET|POST       performance-periods; PATCH performance-periods/{id}/open|close
GET|POST       performance-indicators
GET|POST       performance-reviews; PUT performance-reviews/{id}; PATCH performance-reviews/{id}/calibrate

# 13. Pelatihan & Sertifikasi
GET|POST       trainings; GET|POST trainings/{id}/participants
GET|POST       personnel/{id}/certifications; PATCH personnel-certifications/{id}/verify

# 14–15. Jabatan Akademik & Kepangkatan
GET|POST       academic-ranks; GET|POST personnel/{id}/academic-rank-histories
GET            academic-ranks/eligibility               (dosen yang memenuhi syarat naik jabatan)
GET|POST       ranks; GET|POST personnel/{id}/rank-histories
GET            ranks/promotions-due                     ?within_days=90

# 16. Penempatan & Mutasi
GET|POST       work-units; GET work-units/tree; PUT|DELETE work-units/{id}
GET|POST       positions
GET|POST       placements; PATCH placements/{id}/cancel
GET            org-chart

# 17. Pengajuan SDM
GET            hr-requests                              hr_requests.read  filter: request_type,status,work_unit_id,date_from,date_to
POST           hr-requests                              hr_requests.create (atas nama pegawai)
GET            hr-requests/{id}
GET            hr-requests/failed-to-apply

# 18. Persetujuan (perluasan ApprovalWorkflow)
POST           approval-request-steps/{step}/return     (catatan wajib)
POST           approval-requests/{id}/resubmit
POST           approval-request-steps/bulk-approve

# 19–20. Laporan & Export
GET            hr/reports                               reports.read (+ permission sumber per jenis)
GET            hr/reports/{type}?format=xlsx|pdf|csv    hr_exports.create untuk format file
POST           hr/exports                               hr_exports.create  {resource, filters, columns, format}
GET            hr/exports/{id}                          (status job + tautan unduh)
GET            personnel/{id}/biodata.pdf               hr_exports.create

# 22. Audit Log SDM
GET            hr/audit-logs                            hr_audit_logs.read  filter: personnel_id,user_id,action,date_from,date_to
GET            personnel/{id}/audit-logs                hr_audit_logs.read

# Self-service (role employee / lecturer), dibatasi ke personnel milik auth()->user()
GET            me/profile
GET|POST       me/hr-requests; GET me/hr-requests/{id}; PUT me/hr-requests/{id} (revisi saat returned)
PATCH          me/hr-requests/{id}/cancel
GET            me/leave-balances; GET me/attendances; GET|POST me/documents; GET me/trainings
```

Semua `index` memakai `ListQuery` (pagination default `per_page=20`, `search`, `sort`, `filter[...]` ter-whitelist), sama seperti endpoint yang sudah ada.

---

## 9. Aturan Bisnis Kunci

### 9.1 Aktual

1. **NIDN unik per tenant**, bukan global (`unique(university_id, nidn)`). Dosen yang sama boleh terdaftar di dua universitas dengan NIDN yang sama (realistis untuk dosen LB).
2. **Fakultas dosen wajib milik tenant yang sama.** `faculty_id` tenant lain → 404 (bukan 422).
3. **Hapus dosen = hard delete.** `theses.supervisor_lecturer_id` dan `internships.supervisor_lecturer_id` menjadi `NULL` (`nullOnDelete`). Lihat §11 poin 3.
4. **Isolasi multi-tenant** penuh (`TenantScoped`): list tidak pernah menampilkan data tenant lain, by-id lintas tenant → 404.

### 9.2 Rancangan

| # | Aturan | Konfigurasi per tenant? |
|---|---|---|
| R1 | NIP unik per tenant; tidak bisa diubah setelah pegawai punya kontrak/riwayat (hanya `super_admin`) | – |
| R2 | Pegawai berstatus kepegawaian `requires_contract` wajib punya tepat satu kontrak `active` | – |
| R3 | Jabatan struktural `is_unique_holder` hanya dipegang satu pegawai aktif pada satu waktu | – |
| R4 | Kuota cuti tahunan default 12 hari; carry-over maks. 6 hari; hangus 30 Juni tahun berikutnya | ✅ |
| R5 | Cuti dihitung hari kerja; tidak boleh tumpang tindih; saldo berkurang saat **disetujui** | sebagian |
| R6 | Toleransi keterlambatan default 15 menit | ✅ |
| R7 | Satu baris kehadiran per pegawai per hari (`unique(personnel_id, date)`) | – |
| R8 | "Pendidikan terakhir" hanya dari riwayat pendidikan **terverifikasi** | – |
| R9 | Kenaikan jabatan akademik mensyaratkan angka kredit minimum jenjang tujuan; Lektor Kepala/Profesor mensyaratkan S3 | ✅ (angka kredit) |
| R10 | Kenaikan pangkat berkala tiap 4 tahun sejak TMT terakhir | ✅ |
| R11 | Total bobot indikator kinerja per kelompok = 100% | – |
| R12 | Pegawai nonaktif tidak bisa mengajukan apa pun dan tidak muncul di pemilihan approver | – |
| R13 | Pegawai/dosen yang punya relasi (riwayat, kontrak, pembimbing skripsi/magang, pengajuan) **tidak bisa dihapus**, hanya dinonaktifkan (409 dengan pesan penyebab) | – |
| R14 | Perubahan dari Pengajuan SDM hanya diterapkan setelah seluruh step persetujuan `approved`; nilai lama tersimpan di Audit Log | – |
| R15 | Pengajuan `returned` dapat direvisi & diajukan ulang maks. N kali; setelah itu otomatis `rejected` | ✅ |
| R16 | Mutasi dengan TMT di masa depan tidak mengubah unit/jabatan sebelum TMT | – |
| R17 | Usia pensiun default 65 tahun (dosen) / 58 tahun (tendik); Profesor 70 tahun | ✅ |
| R18 | Data sensitif dimask di Resource, export, dan Audit Log kecuali `personnel_sensitive.read` | – |
| R19 | Audit log append-only; tidak ada endpoint ubah/hapus | – |

Aturan yang realistis berbeda antar kampus (kuota cuti, toleransi, angka kredit, interval pangkat, usia pensiun, predikat kinerja) **langsung** dibuat sebagai `system_settings`/tabel referensi per tenant, bukan hardcode.

---

## 10. Alur Kerja Utama

### 10.1 Onboarding Pegawai/Dosen Baru

```
1. hr_administrator: POST /personnel (wizard)                                      ❌
   - Fase 1 sementara: POST /lecturers ✅ atau POST /employees ❌
2. hr_administrator: POST /employment-contracts (+ file via /file-uploads)          ❌  §5.9
3. hr_administrator: POST /personnel/{id}/documents (KTP, ijazah, SK)                ❌  §5.8
4. hr_administrator: POST /personnel/{id}/educations → verify                        ❌  §5.5
5. university_owner/admin: buat akun + role employee/lecturer (user_roles)          ✅
   hr_administrator: POST /personnel/{id}/link-user                                  ❌
6. Notifikasi: hr.document_incomplete jika dokumen wajib belum lengkap               ❌  §5.21
   Seluruh langkah tercatat di Audit Log SDM                                         ❌  §5.22
```

### 10.2 Pengajuan SDM (contoh: Perubahan Data Rekening)

```
1. (sekali) university_owner: POST /approval-workflows untuk request_type=data_change
   step: [direct_supervisor] → [Role hr_administrator]
2. employee: POST /me/hr-requests {request_type: data_change,
   payload: {bank_account_number: {old: "****1234", new: "9876543210"}}, lampiran buku tabungan}
   → hr_requests "submitted" → ApprovalRequestService::submit()
   → notifikasi hr.request_submitted ke atasan
3. atasan: approve
4. hr_administrator: lihat diff → "Kembalikan" (catatan: "foto buku tabungan buram")
   → status "returned" → notifikasi hr.request_returned
5. employee: PUT /me/hr-requests/{id} (lampiran baru) → POST /approval-requests/{id}/resubmit
6. atasan & hr_administrator: approve → ApprovalRequestApproved
   → handler data_change: update personnel (terenkripsi), applied_at diisi
   → Audit Log: updated (nilai dimask) + approved
   → notifikasi hr.request_decided ke pemohon
```

### 10.3 Cuti

```
1. employee: POST /me/hr-requests {request_type: leave, leave_type_id, start_date, end_date, reason}
   → validasi saldo, overlap, hari kerja → LeaveRequest "pending"
2. atasan → hr_administrator: approve (atau kembalikan/tolak)
3. ApprovalRequestApproved → saldo berkurang, kehadiran rentang tanggal = "leave"
4. H-1: hr.leave_starting ke pemohon, pengganti tugas, atasan
```

### 10.4 Kenaikan Jabatan Akademik

```
1. Sistem: dosen memenuhi syarat (angka kredit, masa kerja, pendidikan)
   → notifikasi hr.academic_rank_eligible
2. lecturer: POST /me/hr-requests {request_type: academic_rank_promotion, lampiran PAK & karya}
3. Kaprodi → Dekan → hr_administrator: approve
4. hr_administrator: input SK (nomor, TMT, file) → academic_rank_histories "active"
   → lecturer_profiles.academic_rank_id diperbarui → Riwayat Jabatan & Dokumen terisi otomatis
```

### 10.5 Mutasi

```
1. hr_administrator: POST /placements {personnel_id, to_work_unit_id, to_position_id,
   kind: transfer, decree_number, effective_from: 2026-11-01}
   → status "scheduled" (TMT di masa depan)
2. Job harian pada 2026-11-01: terapkan → personnel.work_unit_id berubah,
   Riwayat Pekerjaan & Riwayat Jabatan bertambah, status "applied"
   → notifikasi hr.placement_effective
```

---

## 11. Celah & Risiko

1. **Bagian SDM tidak punya `reports.read`, `audit_logs.read`, `approval_requests.read`.** Modul Laporan SDM, Audit Log SDM, dan Persetujuan tidak terjangkau oleh role yang paling membutuhkannya. Perbaikan cepat di `OrganizationalRoleSeeder`.
2. **Laporan SDM membuka data pegawai ke role tanpa `employees.read`.** Wakil Rektor, Dekan, Kaprodi, Bagian Akademik bisa melihat daftar seluruh pegawai lewat `/reports/sdm`. Saat laporan SDM bertambah (kinerja, cuti), ini menjadi kebocoran serius. Perbaikan: `ReportController::show()` memeriksa permission sumber per jenis laporan.
3. **Hapus dosen adalah hard delete.** `DELETE /lecturers/{id}` menghapus permanen; skripsi/magang kehilangan pembimbing tanpa peringatan. Perbaikan: soft delete atau 409 jika masih direferensikan (R13).
4. **CRUD Dosen tidak tercatat di Audit Log.** `Lecturer`/`Employee` tidak memakai trait `Auditable`. Siapa menambah, mengubah, atau menghapus dosen tidak bisa ditelusuri. Perbaikan satu baris per model.
5. **Pegawai tidak punya nomor induk dan tidak punya unique key bisnis.** Duplikasi tidak bisa dicegah; import & integrasi presensi tidak punya kunci stabil.
6. **`unit_kerja` dan `position` teks bebas.** Variasi penulisan memecah satu unit menjadi beberapa di dashboard/laporan.
7. **Tidak ada identity link Lecturer/Employee → User.** Memblokir Pengajuan SDM, self-service, approver atasan/jabatan, dan validasi dosen pengampu di Akademik.
8. **`ApprovalApproverType::Position` melempar `LogicException`.** Workflow dengan approver tipe jabatan gagal saat runtime. Sebelum didukung, `StoreApprovalWorkflowRequest` sebaiknya menolak tipe `position`.
9. **ApprovalWorkflow belum punya status "dikembalikan ke pemohon"** (§5.18), padahal modul Persetujuan SDM mensyaratkannya.
10. **`hr_administrator` tidak bisa membuat akun login** (hanya `users.read`, `user_roles.read`). Onboarding selalu butuh owner/admin. Harus diputuskan eksplisit apakah ini pemisahan tugas yang disengaja.
11. **Data kepegawaian hidup di Modul Akademik.** Ketergantungan harus dijaga satu arah (HumanResource → Academic).
12. **Dekan/Kaprodi belum ter-scope ke unitnya**: `lecturers.read` berlaku ke seluruh dosen tenant.

---

## 12. Status Frontend & Struktur Menu

### 12.1 Aktual (React 19 + Vite + TS, `frontend/src/`)

- Menu top-level **"Dosen"** (`ROUTES.dosen`, `lecturers.read`) dan **"Pegawai"** (`ROUTES.pegawai`, `employees.read`) di `constants/nav.ts`.
- `pages/academic/LecturersPage.tsx`: list + filter + **Tambah Dosen** (`lecturers.create`) dengan `LecturerFormModal`.
- `pages/academic/LecturerDetailPage.tsx`: detail + **Ubah** (`lecturers.update`) + **Hapus Dosen** (`lecturers.delete`).
- `pages/academic/EmployeesPage.tsx` / `EmployeeDetailPage.tsx`: read-only.
- Menu **Persetujuan** (Pengajuan, Alur Persetujuan), **Laporan** (`reports.read`), dan Dashboard dengan `StaffByUnitChart`.
- Mobile Flutter: belum ada fitur SDM. [RANCANGAN_FLUTTER_CIVITAS_ONE.md](./RANCANGAN_FLUTTER_CIVITAS_ONE.md) §6.5.5–6.5.6 merencanakan `EmployeesScreen`/`EmployeeDetailScreen` (read-only).

### 12.2 Rancangan Menu "SDM" (22 modul)

Gating per permission dengan pola `NAV_ITEMS` + `usePermission()` yang sudah ada. Menu top-level "Dosen" dan "Pegawai" dipindah ke dalam grup SDM.

```
SDM
 ├─ Dashboard SDM                    hr_dashboard.read                 (1)
 ├─ Kepegawaian
 │   ├─ Data Pegawai                 personnel.read                    (2)
 │   ├─ Data Dosen                   lecturers.read                    (3)
 │   └─ Data Tenaga Kependidikan     education_staff.read              (4)
 ├─ Riwayat & Karier
 │   ├─ Riwayat Pendidikan           personnel_histories.read          (5)
 │   ├─ Riwayat Jabatan              personnel_histories.read          (6)
 │   ├─ Riwayat Pekerjaan            personnel_histories.read          (7)
 │   ├─ Jabatan Akademik             academic_ranks.read               (14)
 │   ├─ Kepangkatan                  ranks.read                        (15)
 │   └─ Penempatan & Mutasi          placements.read                   (16)
 ├─ Administrasi
 │   ├─ Dokumen Kepegawaian          personnel_documents.read          (8)
 │   ├─ Kontrak & Status             employment_contracts.read         (9)
 │   ├─ Cuti & Izin                  leave_requests.read               (10)
 │   └─ Kehadiran                    employee_attendances.read         (11)
 ├─ Pengembangan
 │   ├─ Kinerja Pegawai              performance_reviews.read          (12)
 │   └─ Pelatihan & Sertifikasi      trainings.read                    (13)
 ├─ Pengajuan & Persetujuan
 │   ├─ Pengajuan SDM                hr_requests.read                  (17)
 │   └─ Persetujuan                  approval_requests.read            (18)
 ├─ Laporan & Data
 │   ├─ Laporan SDM                  reports.read                      (19)
 │   └─ Export Data                  hr_exports.create                 (20)
 └─ Pengaturan SDM
     ├─ Notifikasi SDM               notification_templates.read       (21)
     ├─ Audit Log SDM                hr_audit_logs.read                (22)
     └─ Data Referensi               (jenis cuti, jenis dokumen, status kepegawaian,
                                      unit kerja, jabatan, pangkat, kategori tendik)

Portal Pegawai (role employee / lecturer, self_service_hr.*)
 ├─ Profil Saya
 ├─ Pengajuan Saya (cuti, izin, perubahan data, kenaikan jabatan, mutasi, dll.)
 ├─ Saldo Cuti & Kehadiran Saya
 ├─ Dokumen Saya
 └─ Pelatihan & Sertifikasi Saya
```

Halaman Profil Pegawai (§5.2) memakai tab, sehingga modul 5–16 dapat diakses dari dua arah: dari menu (lintas pegawai) dan dari profil (satu pegawai).

**Mobile (Flutter):** `features/hr_requests/` (ajukan & pantau pengajuan, termasuk cuti), `features/hr_approval/` (approve/kembalikan dari ponsel untuk atasan), `features/hr_profile/` (profil & dokumen saya); `features/hr_attendance/` hanya jika presensi mandiri (§5.11 tahap E) dibangun.

---

## 13. Ringkasan Gap Implementasi

| # | Modul | Sudah ada | Belum ada |
|---|---|---|---|
| 1 | Dashboard SDM | Total dosen/pegawai, grafik staf per unit | Komposisi, kontrak, cuti, kepatuhan, filter, drill-down |
| 2 | Data Pegawai | List/detail `employees` (read-only) | CRUD, NIP, identitas lengkap, wizard, profil bertab, nonaktifkan, import, tautkan akun |
| 3 | Data Dosen | CRUD NIDN/nama/email/fakultas | NIDK, prodi homebase, status dosen, keahlian, pendidikan & jabatan akademik terhubung |
| 4 | Data Tendik | (tersirat di `employees`) | Kategori tendik, jabatan fungsional, penugasan |
| 5 | Riwayat Pendidikan | – | Seluruhnya |
| 6 | Riwayat Jabatan | – | Seluruhnya + approver berbasis jabatan |
| 7 | Riwayat Pekerjaan | – | Seluruhnya |
| 8 | Dokumen Kepegawaian | FileManagement generik | Jenis dokumen, verifikasi, masa berlaku, kelengkapan |
| 9 | Kontrak & Status | `is_active` | Status kepegawaian, kontrak, perpanjangan, pengakhiran |
| 10 | Cuti & Izin | ApprovalWorkflow generik | Jenis cuti, saldo, pengajuan, kalender |
| 11 | Kehadiran | – | Integrasi/import, rekap, aturan, koreksi |
| 12 | Kinerja Pegawai | – | Seluruhnya |
| 13 | Pelatihan & Sertifikasi | – | Seluruhnya |
| 14 | Jabatan Akademik | – | Seluruhnya |
| 15 | Kepangkatan | – | Seluruhnya |
| 16 | Penempatan & Mutasi | – | Unit kerja, mutasi terjadwal, bagan organisasi |
| 17 | Pengajuan SDM | ApprovalWorkflow (`requestable` morph) | `hr_requests`, handler per jenis, Pengajuan Saya |
| 18 | Persetujuan | Inbox, approve/reject/delegate, notifikasi | Status "dikembalikan", resubmit, approver atasan/jabatan, bulk approve, akses SDM |
| 19 | Laporan SDM | 1 laporan rekap + CSV | 13 laporan lain, gating per jenis, akses SDM |
| 20 | Export Data | CSV laporan, dompdf terpasang | Excel, PDF berkop, export daftar terfilter, job antrean, import |
| 21 | Notifikasi SDM | Template, kanal, preferensi, dispatcher | 20 event SDM, job pengingat harian |
| 22 | Audit Log SDM | Trait `Auditable`, `/audit-logs` | Dipasang di model SDM, masking, aksi approve/export/unduh, tampilan SDM, akses SDM |

---

## 14. Rencana Bertahap & Prioritas

Setiap fase dapat dirilis sendiri. Nomor dalam kurung merujuk ke 22 modul di §3.

**Fase 0: Perbaikan cepat (tanpa tabel baru)**
1. Tambah `reports.read`, `audit_logs.read`, `approval_requests.read` ke `hr_administrator` (19, 22, 18).
2. Pasang trait `Auditable` di `Lecturer` dan `Employee` (22).
3. Gate laporan per jenis berdasarkan permission sumber (19).
4. Proteksi hapus dosen: soft delete atau 409 jika masih direferensikan.
5. Tolak approver tipe `position` saat membuat workflow sampai didukung (18).

**Fase 1: Master pegawai**
6. NIP + CRUD Pegawai pola `LecturerController` (2).
7. Tabel `personnel` + `lecturer_profiles` + `education_staff_profiles` + identity link `user_id` (2, 3, 4).
8. Data referensi: `work_units`, `positions`, `employment_statuses`, kategori tendik; migrasi dari `unit_kerja`/`position` teks (4, 16).
9. Export Excel/PDF untuk daftar pegawai + import Excel (20).
10. Dashboard SDM tahap 1: ringkasan & komposisi (1).

**Fase 2: Riwayat, dokumen, kontrak**
11. Riwayat Pendidikan, Jabatan, Pekerjaan (5, 6, 7).
12. Jabatan Akademik & Kepangkatan (14, 15).
13. Dokumen Kepegawaian + verifikasi + kelengkapan (8).
14. Kontrak & Status Kepegawaian (9).
15. Notifikasi SDM tahap 1: kontrak, dokumen, pangkat, pensiun + job harian (21).

**Fase 3: Pengajuan, persetujuan, cuti, kehadiran**
16. Perluasan ApprovalWorkflow: status `returned` + resubmit, approver `direct_supervisor`/`unit_head`/`position`, bulk approve (18).
17. Pengajuan SDM (`hr_requests` + handler per jenis) + Portal Pegawai minimal (17).
18. Cuti & Izin (10).
19. Penempatan & Mutasi terjadwal (16).
20. Kehadiran tahap A–D (integrasi, monitoring, aturan, koreksi) (11).
21. Notifikasi SDM tahap 2: pengajuan, cuti, mutasi (21); Dashboard SDM tahap 2 (1).

**Fase 4: Pengembangan**
22. Kinerja Pegawai (12).
23. Pelatihan & Sertifikasi (13).
24. Katalog Laporan SDM lengkap (19).

**Fase 5: Modul pendukung lanjutan (§6)**
25. Payroll & slip gaji, lembur & dinas, surat peringatan, tugas pegawai, BKD, presensi mandiri mobile.

**Definisi "selesai" per modul** (standar Modul Akademik): migration + model `TenantScoped` + `Auditable` + policy + request + resource + controller + test Pest (termasuk isolasi tenant & 403 per permission) + skenario di [BLACK-BOX-TESTING.md](./BLACK-BOX-TESTING.md) + halaman frontend dengan gating `usePermission()` + export (jika berupa daftar) + template notifikasi (jika ada event) + pembaruan dokumen ini.
