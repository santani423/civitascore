# Semester Pendek — Tahapan Pengembangan

> Bagian dari [paket rancangan Semester Pendek](./README.md).
> **Dibuat:** 2026-10-08 · **Basis kode:** `main` @ `30340f1`
> **Hubungan dengan [rencana-implementasi.md](./rencana-implementasi.md):** dokumen itu menetapkan *cakupan* (fase, yang sengaja tidak dibangun, risiko). Dokumen ini menurunkannya menjadi *urutan kerja yang bisa dieksekusi* — tahap demi tahap, masing-masing ≈ satu PR — dan menyesuaikannya dengan kode terbaru, yang sudah banyak berubah sejak paket rancangan ditulis (§1).

## Daftar Isi

1. [Rekonsiliasi: Rancangan vs Kode Saat Ini](#1-rekonsiliasi-rancangan-vs-kode-saat-ini)
2. [Prinsip Eksekusi](#2-prinsip-eksekusi)
3. [Peta Tahapan & Gerbang Rilis](#3-peta-tahapan--gerbang-rilis)
4. [Detail Tahapan](#4-detail-tahapan)
5. [Matriks Keterlacakan](#5-matriks-keterlacakan)
6. [Kriteria Penerimaan yang Perlu Disesuaikan](#6-kriteria-penerimaan-yang-perlu-disesuaikan)
7. [Risiko Tambahan](#7-risiko-tambahan)
8. [Ringkasan Estimasi](#8-ringkasan-estimasi)

---

## 1. Rekonsiliasi: Rancangan vs Kode Saat Ini

Paket rancangan disusun dari kode per 2026-10-06. Migrasi bertanggal `2026_10_06_*` yang masuk setelahnya membangun sebagian besar "prasyarat" dan beberapa fitur yang oleh rancangan dianggap belum ada — dalam bentuk yang **berbeda** dari usulan rancangan. Membangun versi rancangan di atasnya akan menghasilkan dua mekanisme untuk hal yang sama. Karena itu tahapan di dokumen ini berpijak pada kode yang ada, dengan keputusan berikut (dikunci di Tahap 0.1):

| # | Topik | Rancangan menyebut | Kode sekarang | Keputusan untuk SP |
|---|---|---|---|---|
| R-01 | Dosen pengampu | Tabel `class_section_lecturers` (koordinator/anggota) ⛔ | Kolom `class_sections.lecturer_id` (satu dosen penanggung jawab) + `PUT class-sections/{id}/teaching` | **Pakai yang ada.** Tim pengajar (pivot) adalah fitur umum terpisah dan tidak memblokir SP. |
| R-02 | Jadwal & bentrok | `class_section_schedules` (+ `effective_from/until`) 🆕 | `class_schedules` (`day_of_week`, `start_time`, `end_time`, `room`) + `ClassSchedule::overlaps()`; bentrok dosen/ruang dicek per term di `ClassTeachingService` (sebelumnya `AcademicAdministrationController::assertNoClash()`); bentrok mahasiswa di `KrsPlanService` | **Pakai yang ada.** Tanggal efektif tidak dibuat: bentrok hanya dibandingkan dalam term yang sama dan tanggal term dilarang beririsan ([aturan-bisnis.md §2.3](./aturan-bisnis.md#23-validasi-tanggal-term-sp)), jadi slot SP dan reguler tidak mungkin bentrok. Hanya disempurnakan (Tahap 2.2). ✅ Selesai; rancangan sudah disinkronkan. |
| R-03 | Identitas dosen | `lecturers.user_id` ⛔ | ✅ Ada (`LecturerAccountService`, `LecturerUserAccountSeeder`), **tetapi** `ClassSectionAccess::lecturerFor()` mencari lewat `employees.user_id → lecturers.employee_id`, sedangkan `LecturerProfileController` lewat `lecturers.user_id` | Satukan ke satu jalur sebelum kepemilikan ditegakkan (Tahap 0.4). |
| R-04 | KRS mandiri & persetujuan | `POST student/krs-items` batch atomik; persetujuan **per item** via ApprovalWorkflow `krs_item.short_term`; tabel header `krs_submissions` **ditolak** | `KrsPlanService` + `krs_submissions` (satu kartu KRS per mahasiswa per term: `draft → submitted → approved/rejected`), diputuskan dosen wali (`students.academic_advisor_id`) atau Bagian Akademik; endpoint `student/krs/*`; status item `draft/pending/enrolled/dropped` | **Pakai alur yang ada**, diperluas agar menerima term SP (Tahap 3.1–3.3). Persetujuan SP diatur setting `academic.short_term.approval_mode` = `advisor` (alur yang ada) atau `auto`. ApprovalWorkflow per item, status item `rejected`, dan endpoint `student/krs-items` **tidak** dibuat. ⚠️ Membalik keputusan rancangan — wajib dikonfirmasi tech lead sebelum Fase 3. |
| R-05 | Jendela pendaftaran | `registration_starts_at/ends_at` (timestamp) 🆕 | `academic_terms.krs_start_date/krs_end_date` (date) + `AcademicTerm::isKrsOpen()`, yang **mensyaratkan `is_current`** | **Pakai kolom yang ada.** `isKrsOpen()` diperluas: term pendek terbuka bila `status = registration_open` dan hari ini di dalam rentang, tanpa syarat `is_current` (SP tidak pernah `is_current`, [aturan-bisnis.md §2.2](./aturan-bisnis.md#22-is_current-dan-sp)). |
| R-06 | Batas SKS | Bug: `maxSks()` mengambil term terakhir secara global → mahasiswa yang tidak ikut SP dapat 24 SKS | `maxSksForTerm()` sudah **per mahasiswa** (term terakhir yang ia punya nilai) | Bug global sudah tidak ada. Sisa: cabang SP (batas tetap) dan SP tidak dihitung sebagai "semester sebelumnya" bagi mahasiswa yang ikut SP (Tahap 1.2). |
| R-07 | IPK mata kuliah ulang | Bug: dihitung dua kali | `bestAttempts()` sudah mengambil nilai terbaik per MK (seri → terbaru) = kebijakan `highest` | Bug sudah tidak ada; **IPK mahasiswa tidak berubah** oleh SP. Sisa: `items[]` di transkrip, abaikan nilai `draft`, kebijakan `latest` opsional (Tahap 5.1, 6.1). Keputusan akademik "IPK berubah" di rencana-implementasi Fase 2 tidak lagi diperlukan. |
| R-08 | Prasyarat MK | `course_prerequisites` 🆕 opsional (Fase 5 rancangan) | ✅ Tabel, `GET/PUT courses/{id}/prerequisites`, ditegakkan di `KrsPlanService::addItem()` | Fase 5 rancangan praktis selesai. Sisa: penegakan di jalur admin + override (Tahap 3.2). Deteksi siklus hanya 1 tingkat — cukup untuk SP, dicatat sebagai utang teknis. |
| R-09 | LMS | Tidak ada; tidak dibangun untuk SP | ✅ `course_materials`, `assignments`, `assignment_submissions`, terikat `class_section_id` | Otomatis berlaku untuk kelas SP. Sisa: portal/dosen bisa melihat kelas SP walau bukan term `is_current` (Tahap 3.4) + penjaga term `archived` (Tahap 5.4). |
| R-10 | Flag fitur per tenant | `university_feature_flags` ✅ dipakai untuk rollout kepemilikan | Tabel ada (dirancang sebagai override flag global), **tetapi tidak dibaca kode mana pun**; `FeatureFlagService::isEnabled()` dan `FeatureFlagsPage` hanya mengenal flag global | Tambahkan pembacaan override per tenant (Tahap 0.5). Dipakai untuk rollout kepemilikan dosen **dan** untuk membuka SP per tenant. |
| R-11 | Tipe kolom status | Kolom `enum` + `->change()` | Migrasi terbaru sengaja memakai `string(20)` + cast enum (`krs_items.status`, `students.status`) agar status baru tidak butuh `ALTER` | Kolom status baru (`academic_terms.status`, `grades.status`, `grade_revision_requests.status`) memakai `string(20)` + cast. `invoices.status` (masih `enum`) diubah ke `string(20)` saat `cancelled` ditambahkan. |
| R-12 | Notifikasi langkah KRS | Event `short_term.krs_submitted/approved/rejected` | `StudentNotificationService` sudah mengirim `student.krs_submitted`, `student.krs_approved`, `student.krs_rejected`, `student.krs_review_requested` | Pakai event yang ada untuk langkah KRS. Event `short_term.*` hanya untuk yang khas SP (tagihan, pembayaran, kelas batal, nilai dirilis, dst.). |
| R-13 | UI persetujuan KRS | Halaman `pages/approvals` | Endpoint `krs-submissions` ✅, `krsApprovalService` di frontend ✅, rute `/akademik/persetujuan-krs` terdaftar di `constants/routes.ts` — **halamannya belum ada** | Bangun halamannya (umum, berguna untuk reguler juga) di Tahap 3.4. |
| R-14 | Permission | `krs_participation.*` 🆕, `classes.create/update` 🆕, `krs.approve` 🆕 | ✅ `krs_self_service.read/create/update`, `classes.update`, `krs.approve`, `krs_advising.read/approve` sudah ada | Pakai yang ada. Yang benar-benar baru: `academic_terms.*`, `classes.create`, `grades.finalize`, `grades.approve`, `invoices.update`. |

**Gap yang masih benar-benar ada** (inilah isi Fase 0–7): nilai `pendek` dan semua asumsi "dua semester"; siklus status term dan CRUD term; endpoint tulis kelas, minimum peserta, pembatalan kelas; `KrsPlanService` yang terkunci ke `currentTerm()`; aturan kelayakan khas SP; seluruh sisi tulis Keuangan (tagihan, pembayaran, kedaluwarsa); status/finalisasi/revisi/riwayat nilai; kepemilikan dosen pada nilai, absensi, dan ujian; layar portal yang menganggap hanya ada satu term aktif; dashboard, laporan, dan notifikasi SP; pembaca setting dan flag per tenant.

---

## 2. Prinsip Eksekusi

- **Satu tahap ≈ satu PR** yang bisa di-merge sendiri, dengan `composer ci:check` dan build frontend hijau.
- **Rilis gelap.** Kode masuk `main` dan produksi secara bertahap, tetapi term `pendek` hanya bisa dibuat di tenant yang flag `academic.short_term`-nya menyala (Tahap 0.5 & 1.4). Flag itu baru dinyalakan untuk tenant pilot di Gerbang G6, sehingga tahap yang belum lengkap (mis. KRS tanpa pembayaran) tidak pernah dipakai sungguhan.
- **Perubahan perilaku semester reguler** hanya yang tercantum di butir "Dampak reguler" setiap tahap. PR-nya memperbarui tes regresi terkait dengan penjelasan ([pengujian-dan-penerimaan.md §1.6](./pengujian-dan-penerimaan.md#16-regresi)).
- **Server selalu memvalidasi**; gating UI hanya kenyamanan ([keamanan-audit-integritas.md §1](./keamanan-audit-integritas.md#1-otorisasi)).
- **Backend dulu, UI menyusul per fase**, supaya UI dibangun di atas API yang stabil. Setiap tahap backend selesai bersama tesnya.
- **Ukuran** (perkiraan kasar, satu developer): **S** ≤ 2 hari · **M** 3–5 hari · **L** 6–10 hari.

---

## 3. Peta Tahapan & Gerbang Rilis

```
 Fase 0           Fase 1            Fase 2           Fase 3            Fase 4
 Fondasi    ──►   Periode SP  ──►   Kelas SP   ──►   KRS SP      ──►   Pembayaran ──┐
 (umum)           │                                                                  │
                  │                                                                  ├──► Fase 6 ────────► Fase 7
                  └──────────────►  Fase 5  Penilaian & perkuliahan  ────────────────┘     Transkrip,        Penerimaan
                                    (lajur paralel, butuh 0.8 + 1.3)                       dashboard,        & go-live
                                                                                           laporan, notif
                                                                       Fase 8 Mobile (opsional, setelah G6)
```

| Gerbang | Setelah | Yang bisa didemokan / dijamin |
|---|---|---|
| **G1** Fondasi aman | Fase 0 + Tahap 1.1–1.2 | Di produksi tanpa efek bagi pengguna kecuali perubahan berflag; dosen bisa dibatasi ke kelasnya per tenant |
| **G2** SP bisa disiapkan | Fase 1 + Fase 2 | (staging) Bagian Akademik membuat term SP, membuka kelas, menetapkan dosen & jadwal, membuka pendaftaran |
| **G3** SP gratis end-to-end | Fase 3 | (staging) Mahasiswa memilih & mengajukan KRS SP, disetujui (dosen wali/otomatis), terdaftar, melihat jadwal & materi SP |
| **G4** SP berbayar end-to-end | Fase 4 | (staging) Tagihan terbit, Keuangan mencatat pembayaran, item jadi `enrolled`, hold kedaluwarsa dilepas |
| **G5** Siklus akademik penuh | Fase 5 + Tahap 6.1 | (staging) Absensi, ujian, nilai draft → submit → final, revisi nilai, transkrip dengan penanda ulang, term `completed` → `archived` |
| **G6** Go-live pilot | Fase 6 + Fase 7 | Flag `academic.short_term` dinyalakan untuk tenant pilot |

---

## 4. Detail Tahapan

Format setiap tahap: **Kerjakan** · **Tes / AC** (kode AC merujuk [pengujian-dan-penerimaan.md §2](./pengujian-dan-penerimaan.md#2-kriteria-penerimaan)) · **Dampak reguler** (bila ada).

### Fase 0 — Fondasi (umum, bukan khusus SP)

#### 0.1 Kunci keputusan rekonsiliasi & sinkronkan paket rancangan · S
- **Kerjakan:** tech lead dan Bagian Akademik mengonfirmasi R-01…R-14 (terutama R-04). Perbarui bagian rancangan yang terdampak: README §3, §3.1, §7; desain-database §3.3, §3.4, §3.6, §3.7; desain-api §2.2 & §3; aturan-bisnis §7; peran-dan-izin §2.
- **Selesai bila:** tidak ada lagi bagian rancangan yang menyuruh membangun sesuatu yang sudah ada di kode.

#### 0.2 Perkakas uji & CI · S
- **Kerjakan:**
  - `phpunit.xml`: daftarkan `app/Modules/Academic/Tests/Unit` (dan `app/Modules/Finance/Tests/Unit` bila dibuat) di testsuite `Unit`. `tests/Pest.php` sudah mengikat wildcard, tetapi `phpunit.xml` memakai daftar eksplisit.
  - `.github/workflows/tests.yml`: job kedua dengan service MySQL 8 yang menjalankan `--group=mysql`; job utama (SQLite) mengecualikan grup itu.
  - Helper `makeShortTermFixture()` mengikuti pola `makeEnrollmentFixture()` di `KrsEnrollmentTest.php`: tenant, term Genap berjalan + term SP, prodi, MK, kelas, mahasiswa ber-nilai D.
- **Selesai bila:** CI hijau dengan kedua job.

#### 0.3 `ConflictException` dengan kode mesin · S
- **Kerjakan:** argumen opsional `?string $code` (+ konteks) dirender sebagai `errors.code` ([desain-api.md §1.1](./desain-api.md#11-kode-error-mesin)). Kompatibel mundur.
- **Tes:** 409 dengan dan tanpa kode.

#### 0.4 Satu jalur identitas dosen · S
- **Kerjakan:** `ClassSectionAccess::lecturerFor()` mencari `lecturers.user_id` lebih dulu, lalu fallback ke rantai `employees.user_id → lecturers.employee_id`. `KrsPlanService::notifyReviewers()` (kini memakai `academicAdvisor->employee->user`) dan `LecturerProfileController` memakai jalur yang sama.
- **Tes:** dosen yang hanya punya `user_id`, hanya tautan pegawai, dan keduanya.
- **Dampak reguler:** dosen yang hanya punya `lecturers.user_id` kini dikenali sebagai pengampu dan dosen wali (perbaikan).

#### 0.5 Flag fitur per tenant · S
- **Kerjakan:** `FeatureFlagService::isEnabled()` membaca override `university_feature_flags` untuk tenant aktif, lalu flag global, sesuai komentar migrasi tabelnya. Cache per tenant, dibersihkan saat flag diubah. Flag baru: `academic.short_term` (term SP boleh dibuat & terlihat) dan `academic.lecturer_ownership` (penegakan Tahap 0.8). Cara menyalakan per tenant: perluas `FeatureFlagsPage` agar bisa mengatur override tenant, atau minimal command/seeder untuk tenant pilot.
- **Tes:** override menang atas global; tenant lain tidak terpengaruh; tanpa override → nilai global.

#### 0.6 `AcademicPolicySettings` · S
- **Kerjakan:** pembaca bertipe atas `university_settings` (`group = academic`) dengan default di kode, di-cache per tenant dan dibersihkan saat `PUT tenant/settings`. Kunci: daftar [desain-database.md §5](./desain-database.md#5-konfigurasi-per-tenant-university_settings), **ditambah** `academic.short_term.approval_mode` (`advisor` | `auto`, default `advisor`).
- **Tes unit:** default, override, cast tipe, isolasi tenant.

#### 0.7 UI "Dosen & Jadwal" + daftar kelas tanpa dosen · M
- **Kerjakan:** endpoint `PUT class-sections/{id}/teaching` ✅ sudah ada, tetapi belum ada UI yang memanggilnya. Bangun panel di `ClassSectionDetailPage` (pilih dosen pengampu; baris hari/jam/ruang; tampilkan 409 bentrok) dan filter "tanpa dosen" di `ClassSectionsPage`.
- **Mengapa di Fase 0:** Bagian Akademik harus mengisi dosen pengampu kelas reguler sebelum kepemilikan ditegakkan (0.8). Panel yang sama dipakai SP di Fase 2.

#### 0.8 Penegakan kepemilikan dosen (di balik flag) · M
- **Kerjakan:**
  - `GradePolicy::manage()`, `AttendancePolicy`, `ExamPolicy` menerima model dan memakai `ClassSectionAccess::canManage()` ✅ (sudah dipakai materi & tugas). Controller meneruskan model ke `authorize()`.
  - `GET grades`, `attendances`, `class-sections`, `exams` difilter ke kelas yang diampu untuk pengguna tanpa `classes.update` (pola `LearningService` untuk `teaching/classes`).
  - Aktif hanya bila flag `academic.lecturer_ownership` menyala. Urutan rilis: merge (flag mati) → admin mengisi dosen lewat 0.7 → nyalakan per tenant → jadikan default.
- **Tes / AC:** **AC-15**. `GradeRecordingTest`, `AttendanceRecordingTest`, `Exam*Test` diperbarui: dosen fixture ditugaskan ke kelasnya.
- **Dampak reguler:** **ya, disengaja dan berflag** — dosen tidak bisa lagi menilai/mengabsen kelas yang bukan miliknya.

### Fase 1 — Periode Semester Pendek

Bergantung pada: 0.3, 0.5, 0.6.

#### 1.1 Nilai `pendek` dan semua asumsi "dua semester" · S
- **Kerjakan:**
  - `AcademicSemester::Pendek`; migrasi kolom `academic_terms.semester` (masih `enum`) — tambah nilai, atau ubah ke `string(20)` (R-11).
  - `AcademicTerm::label()` → `match` (`2026/2027 Pendek`).
  - `StudentAcademicService::semesterNumber()` (kini `Ganjil ? 1 : 2`): term pendek tidak menambah nomor semester — kembalikan nomor semester Genap tahun akademik yang sama.
  - Telusuri asumsi dua nilai lain: factory, `PortalFormatter::term()`, tipe & badge semester di frontend, PDF KHS/transkrip.
- **Tes:** label ketiga nilai; `semesterNumber()` untuk SP.

#### 1.2 Batas SKS sadar SP · S
- **Kerjakan:** di `AcademicRecordService::maxSksForTerm()`:
  - term pendek → `term.max_credits ?? setting academic.short_term.max_credits` (default 9);
  - term reguler → saat mencari "semester terakhir yang punya nilai", lewati term pendek kecuali `academic.short_term.counts_toward_next_term_sks_quota = true`.
- **Tes / AC:** kasus mahasiswa A/B/C [aturan-bisnis.md §4.5](./aturan-bisnis.md#45-batas-sks) → **AC-19**.
- **Dampak reguler:** hanya bagi mahasiswa yang pernah ikut SP (belum ada di produksi).

#### 1.3 Siklus hidup term · M
- **Kerjakan:**
  - Migrasi: `academic_terms.status` `string(20)` default `draft` + backfill (`is_current` → `ongoing`, `end_date < today` → `completed`, lainnya → `planned`); `max_credits` nullable; index `(university_id, semester, status)`.
  - `AcademicTermStatus` + tabel transisi sah ([aturan-bisnis.md §3.1](./aturan-bisnis.md#31-diagram-transisi)). `AcademicTermService::transition()` dengan `lockForUpdate` dan guard: → `registration_open` butuh ≥ 1 kelas aktif dan periode KRS terisi; `registration_closed → registration_open` hanya sebelum `start_date`; `planned → draft` hanya tanpa KRS. Guard `grading → completed` menyusul di 5.2.
  - `isKrsOpen()` diperluas sesuai R-05; perilaku reguler tidak berubah.
  - `AcademicTerm::assertWritable()` (409 bila `archived`), dipanggil setiap jalur tulis di tahap berikutnya. Scope `visibleToStudents()` (bukan `draft`).
  - `Auditable` pada `AcademicTerm`; event `AcademicTermStatusChanged` (afterCommit) + `activity_logs` `SHORT_TERM_STATUS_CHANGED`.
  - Penegakan status hanya untuk term `pendek` ([aturan-bisnis.md §3.3](./aturan-bisnis.md#33-penegakan-untuk-semester-reguler)).
- **Tes unit:** matriks transisi lengkap, termasuk semua transisi terlarang.

#### 1.4 API periode akademik · M
- **Kerjakan:**
  - `GET academic-terms` ✅ (daftar polos) diperluas: filter `semester`, `status`, `academic_year`; field `status`, `max_credits`, `effective_max_credits`. Bentuk lama dipertahankan (dipakai portal & kalender).
  - Baru: `GET academic-terms/{id}`, `POST`, `PUT`, `DELETE` (hanya `draft` tanpa kelas), `PATCH academic-terms/{id}/status`. `PATCH academic-terms/{id}/krs-period` ✅ tetap menjadi cara mengatur jendela pendaftaran SP.
  - Validasi [aturan-bisnis.md §2.3](./aturan-bisnis.md#23-validasi-tanggal-term-sp) & §11.1: format tahun, tanggal beririsan 409, satu SP per tahun 409, `krs_end_date ≤ start_date + late_registration_days`.
  - `semester = pendek` ditolak 409 ("Fitur Semester Pendek belum diaktifkan untuk universitas ini.") bila flag `academic.short_term` mati.
  - Permission `academic_terms.read/create/update/delete` di `RolePermissionSeeder::PERMISSION_MAP` + `OrganizationalRoleSeeder` ([peran-dan-izin.md §3](./peran-dan-izin.md#3-pemberian-ke-role-organizationalroleseederrole_permissions)).
- **Tes / AC:** **AC-01, AC-02, AC-03**, AC-04 (bagian periode), AC-21.

#### 1.5 UI Periode Akademik & Pengaturan Akademik · M
- **Kerjakan:**
  - Menu "Periode Akademik" (`academic_terms.read`), rute `/akademik/periode` dan `/akademik/periode/:id` ([frontend-dan-mobile.md §2.2–2.3](./frontend-dan-mobile.md#2223-daftar--form-periode)): daftar, form, dropdown "Ubah Status" yang hanya menampilkan transisi sah, modal alasan, form periode KRS.
  - Halaman `/pengaturan/akademik` untuk kunci `academic.*` via `PUT tenant/settings` ✅ ([frontend-dan-mobile.md §2.10](./frontend-dan-mobile.md#210-pengaturan-kebijakan-sp)).
  - Tipe di `types/academic.ts`, fungsi di `services/academicService.ts`.

### Fase 2 — Penawaran Kelas SP

Bergantung pada: 1.3, 1.4.

#### 2.1 Endpoint tulis kelas · M
- **Kerjakan:**
  - Migrasi `class_sections`: `min_participants`, `cancelled_at`, `cancellation_reason`.
  - `POST class-sections` dan `PUT class-sections/{id}`: MK harus milik prodi; term pendek harus berstatus `draft`…`registration_closed`; kapasitas tidak boleh di bawah peserta aktif (E25); kelas berpeserta tidak boleh pindah term (E26); `assertWritable()`.
  - Permission baru `classes.create` (`classes.update` ✅ sudah ada).
  - `Auditable` pada `ClassSection`; resource + `min_participants` dan jumlah draft/pending/enrolled.
- **Tes:** validasi, 404 lintas tenant, E25, E26.

#### 2.2 Penyempurnaan jadwal & dosen pengampu · S–M
- **Kerjakan** pada `PUT class-sections/{id}/teaching` ✅:
  - Normalisasi ruang (trim, spasi tunggal, huruf kecil); lewati ruang kosong/`online`.
  - Bentrok dosen boleh dipaksa dengan `force: true` + `reason`; bentrok ruang tidak.
  - 409 dengan `code` `SCHEDULE_CONFLICT` + `errors.conflicts[]`.
  - Jadwal kelas yang sudah berpeserta diubah → `meta.warnings.student_conflicts[]` ([aturan-bisnis.md §8.4](./aturan-bisnis.md#84-perubahan-jadwal-setelah-pendaftaran)).
  - `activity_logs` `CLASS_SCHEDULE_CHANGED` / `LECTURER_ASSIGNED`; tolak bila term `archived`.
  - Panel UI dari 0.7: checkbox "Paksa" + alasan, peringatan konflik mahasiswa.
- **Tes / AC:** **AC-05**.
- **Status:** ✅ selesai (R-01 + R-02), kecuali penolakan term `archived` yang menunggu `assertWritable()` di Tahap 1.3. Tambahan R-02: konflik menunjuk baris kiriman (`row`/`other_row`) dan ditandai per baris di UI; kelas nonaktif tidak ikut bentrok; penyimpanan diserialkan per term (kunci `academic_terms`) agar bentrok ruang tidak lolos lewat permintaan paralel; `student_conflicts` hanya bila jadwal berubah dan tanpa duplikat; bentrok mahasiswa di KRS kini juga 409 `SCHEDULE_CONFLICT` + `errors.conflicts[]`; unit test `ClassScheduleTest` (didaftarkan di `phpunit.xml`).

#### 2.3 Pembatalan kelas · M
- **Kerjakan:** `PATCH class-sections/{id}/cancel {reason}` — kunci kelas; tolak bila sudah ada nilai atau `exam_attempts`; isi `cancelled_at`, `is_active = false`; item `draft/pending/enrolled` → `dropped`; hitung ulang total `krs_submissions` terkait; `activity_logs` `CLASS_CANCELLED`. Penanganan tagihan ditambahkan di 4.3, notifikasi di 6.4.
- **Tes:** pembatalan sukses; kelas bernilai → 409.

#### 2.4 UI kelas SP · M
- **Kerjakan:** tab "Kelas" di detail periode → `ClassSectionsPage` dengan term terkunci; modal "Buka Kelas"; kolom peserta (enrolled/pending/kapasitas), dosen, jadwal, status (Aktif / Batal / Di bawah minimum); modal "Batalkan Kelas" yang menampilkan jumlah peserta terdampak.

### Fase 3 — KRS Semester Pendek

Bergantung pada: Fase 2 dan konfirmasi R-04.

#### 3.1 `KrsPlanService` menerima term eksplisit · M
- **Kerjakan:**
  - Metode publik (`overview`, `offerings`, `addItem`, `removeItem`, `submit`, `cancelSubmission`) menerima `AcademicTerm`. `requireCurrentTerm()` diganti resolver term yang KRS-nya bisa diisi mahasiswa: term reguler berjalan, atau term pendek yang `visibleToStudents()`.
  - Endpoint `student/krs*` ✅ menerima `academic_term_id` opsional (default = term berjalan, jadi perilaku lama tidak berubah). Baru: `GET student/krs/terms` — term yang punya KRS untuk mahasiswa (reguler berjalan + SP ≥ `planned`). Term `draft` → 404.
- **Tes:** `StudentKrsSelfServiceTest` tetap hijau **tanpa diubah**; tes baru untuk term SP.

#### 3.2 Pipeline kelayakan + aturan khas SP · M–L
- **Kerjakan:**
  - Pemeriksaan di `addItem()` dan `offerings()` kini terduplikasi (exception vs `blockers`). Ekstrak ke satu `EnrollmentEligibility` dengan mode **evaluasi** (tampilan, tanpa kunci) dan **tegakkan** (di dalam transaksi & kunci), keluaran ber-`code` ([aturan-bisnis.md §4.1](./aturan-bisnis.md#41-pipeline)).
  - Aturan baru, hanya untuk term pendek: `TERM_NOT_OPEN` (status + jendela), `OUTSTANDING_INVOICE`, `COURSE_IN_PROGRESS`, `NEW_COURSE_NOT_ALLOWED`, kelas batal, batas SKS dari 1.2, `SKS_BELOW_MINIMUM` saat menghapus item. MK yang sudah lulus **boleh** diulang sampai batas `retake_max_letter_grade` (`COURSE_NOT_RETAKABLE`). Di reguler, aturan yang ada ("sudah lulus ≥ C tidak bisa diambil lagi") tetap.
  - Jalur admin `AcademicRecordService::enroll()` (`POST krs-items`): kunci mahasiswa lalu kelas (urutan sama dengan `KrsPlanService`, mencegah deadlock); cek status mahasiswa aktif, MK yang sama di kelas lain, prasyarat, aturan SP. Tambah `override[]` + `reason` (min 10 karakter) dengan `krs.approve` ✅ dan `activity_logs` `PREREQUISITE_OVERRIDDEN`.
- **Tes / AC:** **AC-07** (setiap `code`), AC-06.
- **Dampak reguler:** admin tidak lagi bisa mendaftarkan mahasiswa non-aktif, atau MK yang sama di dua kelas (disengaja, [pengujian-dan-penerimaan.md §1.6](./pengujian-dan-penerimaan.md#16-regresi)).

#### 3.3 Mode persetujuan SP · S–M
- **Kerjakan:**
  - `approval_mode = advisor`: alur `krs_submissions` ✅ apa adanya (dosen wali, fallback Bagian Akademik).
  - `approval_mode = auto`: `submit()` untuk term pendek langsung `approved` di transaksi yang sama (`decided_by = null`, catatan "Disetujui otomatis").
  - Setelah disetujui: biaya 0 → item `enrolled` (perilaku `approve()` ✅). Biaya > 0 ditangani 4.2 — sampai Fase 4 selesai, SP hanya diuji dengan `fee_per_credit = 0`.
  - Antrean `GET krs-submissions` bisa difilter per term/semester.
- **Tes / AC:** **AC-10** (disesuaikan, §6).

#### 3.4 Portal, tampilan multi-term & persetujuan KRS · L
- **Kerjakan:**
  - **Portal "Semester Pendek"** ([frontend-dan-mobile.md §3](./frontend-dan-mobile.md#3-halaman-mahasiswa-portal)): Ringkasan (aturan, status, hitung mundur), Pilih MK (`PortalKrsPage` untuk term SP: badge Ulang/Baru, alasan tidak layak, keranjang SKS & biaya), KRS Saya.
  - **Multi-term:** layanan portal yang memakai `currentTerm()` — `StudentScheduleService`, `LearningService` (mata kuliah, materi, tugas), `StudentDashboardService`, dan default `StudentAttendanceService` — juga menyertakan term pendek tempat mahasiswa punya KRS aktif dengan status `registration_closed`…`grading`. Tanpa ini, mahasiswa SP tidak melihat jadwal dan materi kelas SP-nya.
  - **Dosen:** `GET teaching/classes` ✅ sudah menerima `academic_term_id`; UI cukup menambah pemilih term.
  - **Admin:** halaman Persetujuan KRS di `/akademik/persetujuan-krs` (R-13); `KrsPage` dengan filter term, status `draft/pending`, dan bagian override (hanya bila `usePermission('krs.approve')`).
- **Tes:** portal dengan Genap `is_current` + SP `ongoing` menampilkan kelas keduanya.

#### 3.5 Pelepasan kursi & uji konkurensi · S–M
- **Kerjakan:** saat SP berpindah ke `registration_closed`, item `draft` yang tidak pernah diajukan → `dropped` (kursi dilepas). Pengajuan `submitted` yang belum diputuskan tampil di dashboard (6.2).
- **Tes `@group mysql`:** kapasitas 29/30 dengan 10 pendaftar paralel → tepat satu sukses (**AC-09**); satu mahasiswa mengirim dua permintaan paralel yang melebihi SKS; jalur admin dan mandiri bersamaan tanpa deadlock.

### Fase 4 — Pembayaran

Bergantung pada: Fase 3.

#### 4.1 Skema keuangan · S
- **Kerjakan:** `invoices.academic_term_id` (nullable FK); `invoices.status` → `string(20)` + nilai `cancelled`; `payments.reference`, `payments.recorded_by`; `krs_items.invoice_id`. `Auditable` pada `Invoice` dan `Payment`.

#### 4.2 Penerbitan tagihan · M
- **Kerjakan:**
  - Satu titik keputusan saat item SP siap bayar (disetujui `advisor`/`auto`, atau didaftarkan admin): biaya 0 → `enrolled`; biaya > 0 → item tetap `pending`, terbit tagihan `unpaid` (`credits × fee_per_credit`, `due_date = today + payment_due_days`, `period = label()`), dan `krs_items.invoice_id` diisi. Aturan penggabungan tagihan: [pembayaran-dan-notifikasi.md §2.2](./pembayaran-dan-notifikasi.md#22-kapan-tagihan-dibuat).
  - `seatsTaken()` dan `creditsTaken()`: item `pending` yang tagihannya lewat jatuh tempo tidak lagi menahan kursi ([desain-database.md §3.7](./desain-database.md#37-krs_items-)).
  - Portal menurunkan sub-status "Menunggu pembayaran" dari tagihan.
- **Tes:** tarif 0 → tanpa tagihan; tarif > 0 → tagihan sesuai SKS; hold kedaluwarsa tidak dihitung kapasitas.

#### 4.3 Catat pembayaran & batalkan tagihan · M
- **Kerjakan:**
  - `POST invoices/{id}/payments` (kunci tagihan; status selalu turunan dari `paid_amount`); `PATCH invoices/{id}/cancel` (hanya bila belum ada pembayaran). Permission `invoices.update` → `finance_administrator`.
  - Event `InvoicePaid` (afterCommit) → listener mempromosikan item `pending` milik tagihan itu menjadi `enrolled`.
  - Pembatalan diperluas: mahasiswa membatalkan item SP `pending` ([aturan-bisnis.md §7.4](./aturan-bisnis.md#74-pembatalan-oleh-mahasiswa); perluasan `DELETE student/krs/items/{id}` atau aksi baru) → tagihan disesuaikan/dibatalkan; admin drop item lunas → `REFUND_REQUIRED`; pembatalan kelas (2.3) ikut menyesuaikan tagihan.
- **Tes / AC:** **AC-11, AC-13**; `@group mysql` dua pencatatan pembayaran paralel melebihi sisa.

#### 4.4 Kedaluwarsa · M
- **Kerjakan:** jalur *lazy* (saat kelas dikunci untuk pendaftaran dan saat ringkasan KRS dibuka) + command `academic:expire-short-term-holds`, dijadwalkan harian di `routes/console.php` (scheduler ✅ sudah dipakai `hr:daily-maintenance`).
- **Tes / AC:** **AC-12**, lewat jalur lazy maupun command.

#### 4.5 UI pembayaran · M
- **Kerjakan:**
  - Endpoint umum `GET student/invoices`; sambungkan `PortalInvoicesPage` (kini data statis dan belum dirutekan).
  - `InvoicesPage`: filter periode, modal "Catat Pembayaran", aksi "Batalkan". Tab "Pembayaran" di detail periode, termasuk daftar "perlu refund".

### Fase 5 — Penilaian & Perkuliahan (lajur paralel)

Bergantung pada: 0.8, 1.3. Bisa dikerjakan bersamaan dengan Fase 2–4 oleh developer lain.

#### 5.1 Status nilai · M
- **Kerjakan:**
  - Migrasi `grades.status` (`string(20)`, backfill `submitted`), `finalized_at`, `finalized_by`; enum `GradeStatus`; `Auditable` pada `Grade`.
  - `recordGrade()`: kunci baris; term pendek → `draft`, reguler → `submitted`; nilai `finalized` → 409 (E16); term `archived` → 409.
  - `gradedRecords()` — sumber tunggal IP/IPK — mengabaikan `draft`. Mahasiswa hanya melihat nilai SP yang `finalized`.
- **Dampak reguler:** nilai reguler baru berstatus `submitted` (setara perilaku sekarang); `GradeRecordingTest` disesuaikan.

#### 5.2 Submit, kembalikan, finalisasi per kelas · M
- **Kerjakan:** `PATCH class-sections/{id}/grades/submit|return|finalize` ([penilaian-dan-transkrip.md §2.3](./penilaian-dan-transkrip.md#23-alur-finalisasi)); permission baru `grades.finalize`; submit hanya oleh dosen pengampu; 409 `GRADES_INCOMPLETE`. Tambahkan guard `grading → completed` di `AcademicTermService` (semua nilai peserta `enrolled` final, atau `force` + `reason`).
- **Tes / AC:** **AC-16**, AC-20 (bagian penyelesaian).

#### 5.3 Riwayat & revisi nilai · M–L
- **Kerjakan:**
  - `GET grades/{id}/history` dari `audit_logs`; tambahkan `auditable_id` ke filter `AuditLogController`.
  - Tabel `grade_revision_requests` + layanan (snapshot `old_*`, maksimal satu `pending` per nilai, `lockForUpdate`, snapshot basi → 409).
  - Persetujuan lewat ApprovalWorkflow mengikuti pola **`StudentRequestService` + `SyncStudentRequestDecision`** ✅. Fallback `PATCH grade-revision-requests/{id}/approve|reject` dengan permission baru `grades.approve`.
- **Tes / AC:** **AC-17**.

#### 5.4 Absensi, ujian, LMS & term arsip · S–M
- **Kerjakan:**
  - `attendanceRate(KrsItem)` + setting `min_attendance_percentage` dan `attendance_excused_counts_as_present`.
  - `attendance_blocks_exam` di `ExamService::resolveEligibleKrsItem()`.
  - `assertWritable()` di absensi batch, `ExamService` (create/update/publish), materi & tugas (`TeachingController`), dan pengumpulan tugas.
- **Tes / AC:** **AC-14**, AC-20 (arsip menolak tulis).

#### 5.5 UI penilaian · M
- **Kerjakan:** `GradesPage` dengan filter term, kolom status, "Submit Nilai Kelas" (dosen), "Kembalikan" dan "Finalisasi" (admin), modal & daftar revisi, riwayat nilai. Tab "Nilai" di detail periode.

### Fase 6 — Transkrip, Dashboard, Laporan, Notifikasi

Bergantung pada: Fase 4 dan Fase 5 (6.1 cukup Fase 5).

#### 6.1 Transkrip & KHS · S–M
- **Kerjakan:**
  - `transcript()` ditambah `items[]` (`attempt_number`, `is_repeat`, `counts_toward_ipk`), `semester` per term, dan `repeat_grade_policy`. Kunci lama tidak berubah bentuk.
  - Kebijakan `latest` (opsional): strategi di samping `bestAttempts()`. Default `highest` = perilaku sekarang.
  - Portal dan PDF (`StudentDocumentService`): penanda "U"; nilai SP non-final disembunyikan.
- **Tes / AC:** **AC-18**; `TranscriptTest` tetap hijau.

#### 6.2 Ringkasan & dashboard SP · M
- **Kerjakan:** `GET academic-terms/{id}/summary` (metrik [frontend-dan-mobile.md §2.1](./frontend-dan-mobile.md#21-dashboard-sp)) + kartu "Perlu tindakan" sesuai status term.

#### 6.3 Laporan · M
- **Kerjakan:** tipe `sp-enrollment`, `sp-courses`, `sp-grades`, `sp-finance`, `sp-summary` di `ReportService::build()`; parameter `format=csv|pdf` mengikuti `ExamController::exportRecap()`; audit `exported`; pembaruan `ReportsPage`.

#### 6.4 Notifikasi · M
- **Kerjakan:**
  - Event `short_term.*` yang khas SP ([pembayaran-dan-notifikasi.md §3.2](./pembayaran-dan-notifikasi.md#32-daftar-event), dikurangi yang sudah ditangani `student.krs_*` — R-12), dikirim listener afterCommit.
  - `ShortTermNotificationTemplateSeeder`.
  - Satu command harian untuk pengingat (`registration_closing`, `attendance_reminder`, `grading_deadline`, `unfinalized_grades`, `registration_summary`, `new_enrollment`).
- **Tes:** `Notification::fake` per event key.

### Fase 7 — Pengujian Penerimaan & Go-Live

#### 7.1 Skenario end-to-end · M
- **Kerjakan:** empat skenario Pest panjang — `approval_mode` (`auto`/`advisor`) × biaya (0/berbayar) — mengikuti [pengujian-dan-penerimaan.md §1.4](./pengujian-dan-penerimaan.md#14-integrasi-end-to-end).

#### 7.2 Keamanan & isolasi tenant · S
- **Kerjakan:** matriks otorisasi [pengujian-dan-penerimaan.md §1.3](./pengujian-dan-penerimaan.md#13-otorisasi); `TenantIsolationTest` mencakup tabel dan kolom baru; pastikan kolom state tidak bisa diisi lewat mass assignment.

#### 7.3 Uji beban & gladi migrasi · S–M
- **Kerjakan:** uji beban (k6/JMeter, ±200 pengguna virtual ke satu kelas berkapasitas 30) di staging MySQL; gladi migrasi + backfill (`academic_terms.status`, `grades.status`) pada salinan data produksi.

#### 7.4 UAT & go-live pilot · M
- **Kerjakan:**
  - UAT bersama Bagian Akademik dan Keuangan atas AC-01…AC-22 (yang disesuaikan, lihat §6).
  - Runbook: cron `schedule:run` aktif, pengaturan `academic.*` tenant terisi, dosen pengampu terisi, flag `academic.lecturer_ownership` menyala, lalu flag `academic.short_term` dinyalakan untuk tenant pilot.
  - Perbarui video `docs/video-semester-pendek` (saat ini menyebut "fitur ini belum diterapkan").

### Fase 8 — Mobile (opsional)

Mengikuti [frontend-dan-mobile.md §5](./frontend-dan-mobile.md#5-mobile-flutter): absensi dosen sebagai fitur umum, lalu `features/academic_student` (ringkasan SP, KRS Saya, jadwal, nilai) memakai endpoint `student/*` yang sama. Bukan bagian dari definisi "SP selesai".

---

## 5. Matriks Keterlacakan

Setiap butir [checklist kesiapan](./rencana-implementasi.md#3-checklist-kesiapan) dipetakan ke tahap yang menyelesaikannya. "✅ ada" = sudah ada di kode sebelum dokumen ini.

| Butir checklist | Kondisi kode | Tahap |
|---|---|---|
| Identity link `lecturers.user_id` | ✅ ada (dua jalur) | 0.4 |
| Dosen pengampu + kepemilikan ditegakkan | ✅ `lecturer_id`; kepemilikan belum | 0.7, 0.8 |
| `AcademicTerm::label()` mendukung pendek | belum | 1.1 |
| Academic Period (enum, status, jendela, `max_credits`) | jendela ✅ (`krs_*`) | 1.1, 1.3, 1.4 |
| Konfigurasi SP per tenant | tabel ✅, pembaca belum | 0.6, 1.5 |
| Penawaran mata kuliah / manajemen kelas | baca saja | 2.1, 2.3, 2.4 |
| Penugasan dosen (+ bentrok dosen) | ✅ ada | 2.2 ✅ |
| Jadwal (+ bentrok ruang) | ✅ ada | 2.2 ✅ |
| Kelayakan mahasiswa | ✅ sebagian (reguler) | 3.2 |
| KRS mandiri | ✅ ada (term berjalan saja) | 3.1, 3.4 |
| Alur persetujuan | ✅ ada (dosen wali) | 3.3 |
| Pembayaran (tagihan, catat, kedaluwarsa) | baca saja | 4.1–4.5 |
| Enrollment (promosi `pending` → `enrolled`) | ✅ via persetujuan | 4.2, 4.3 |
| Konkurensi kapasitas & SKS | ✅ sebagian (kunci mahasiswa di jalur mandiri) | 3.2, 3.5, 4.3 |
| Absensi (persentase + minimum) | ✅ pencatatan | 5.4 |
| Integrasi LMS | ✅ ada (R-09) | 3.4, 5.4 |
| Integrasi ujian | ✅ ada | 5.4 |
| Penilaian berstatus | belum | 5.1 |
| Finalisasi nilai per kelas | belum | 5.2 |
| Revisi nilai via approval + audit | belum | 5.3 |
| Transkrip (`items[]`, penanda ulang) | ✅ dasar | 6.1 |
| IPS/IPK (kebijakan ulang; `maxSks` abaikan SP) | ✅ `highest` | 1.2, 6.1 |
| Notifikasi | ✅ infrastruktur | 6.4 |
| Laporan (5 tipe, CSV/PDF) | ✅ infrastruktur | 6.3 |
| Audit log | ✅ infrastruktur | 1.3, 2.1, 4.1, 5.1, 5.3, 7.2 |
| Keamanan (permission, policy, tenant, mass assignment) | ✅ konvensi | 0.8, 1.4, 7.2 |
| Frontend admin | sebagian | 0.7, 1.5, 2.4, 3.4, 4.5, 5.5, 6.2, 6.3 |
| Frontend portal mahasiswa | ✅ KRS reguler | 3.4, 4.5, 6.1 |
| Flutter (opsional) | — | Fase 8 |
| Uji unit / feature / otorisasi | — | setiap tahap; 7.2 |
| Uji end-to-end | — | 7.1 |
| Uji konkurensi (MySQL) | belum ada di CI | 0.2, 3.5, 4.3, 7.3 |
| Regresi | — | setiap tahap |
| `phpunit.xml` mendaftarkan `Academic/Tests/Unit` | belum | 0.2 |

---

## 6. Kriteria Penerimaan yang Perlu Disesuaikan

Akibat R-01, R-04, dan R-05, beberapa AC di [pengujian-dan-penerimaan.md §2](./pengujian-dan-penerimaan.md#2-kriteria-penerimaan) diubah bunyinya (bukan dilonggarkan) di Tahap 0.1:

| AC | Bunyi rancangan | Bunyi yang disesuaikan |
|---|---|---|
| AC-05 | Penugasan dosen dan jadwal lewat endpoint terpisah | Lewat `PUT class-sections/{id}/teaching`; bentrok dosen bisa dipaksa dengan `force` + `reason` |
| AC-06 | `POST /student/krs-items {class_section_ids}` → item `pending` | `POST student/krs/items` per kelas lalu `POST student/krs/submit` (dengan `academic_term_id` SP) → item `pending` (`advisor`) atau langsung disetujui (`auto`) |
| AC-08 | Batch atomik: satu kelas gagal → semua batal | Setiap penambahan kelas divalidasi sendiri; `submit` memvalidasi ulang seluruh rencana (SKS & bentrok) secara atomik — gagal → tidak ada item yang berubah status |
| AC-10 | Ada/tidaknya workflow `krs_item.short_term`; ditolak → item `rejected`, kursi dilepas | `approval_mode = auto/advisor`; ditolak → pengajuan `rejected`, item kembali `draft` (perilaku yang ada) dan kursi dilepas saat mahasiswa menghapusnya atau pendaftaran ditutup (3.5) |
| AC-15 | Dosen pengampu via `class_section_lecturers` | Via `class_sections.lecturer_id` |

---

## 7. Risiko Tambahan

Melengkapi [rencana-implementasi.md §5](./rencana-implementasi.md#5-risiko) dengan risiko yang muncul dari kondisi kode sekarang:

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Kursi ditahan sejak status `draft` (`KrsItemStatus::seatHolding()`) | Mahasiswa bisa "menimbun" kursi SP tanpa mengajukan atau membayar | Draft dilepas saat `registration_closed` (3.5); hold berbayar kedaluwarsa (4.4); dipantau di dashboard (6.2). Batas waktu draft bisa ditambahkan nanti bila perlu. |
| Portal menganggap hanya ada satu term aktif (`currentTerm()`) | Mahasiswa SP tidak melihat jadwal/materi/tugas SP | Tampilan multi-term (3.4) + tes dengan Genap `is_current` dan SP `ongoing` |
| Dua jalur identitas dosen | Dosen yang sah ditolak sebagai pengampu atau dosen wali | 0.4 diselesaikan sebelum 0.8 |
| `university_feature_flags` belum pernah dibaca kode | Perilaku override belum teruji | Tes di 0.5; semua flag baru default mati |
| Penegakan kepemilikan memutus akses dosen di kelas reguler yang belum punya `lecturer_id` | Dosen tidak bisa input nilai/absensi | UI pengisian (0.7) + flag per tenant (0.8) |
| Paket rancangan tidak sinkron dengan kode | Developer membangun ulang yang sudah ada | 0.1 sebagai tahap pertama |
| CI belum punya MySQL | Race condition lolos ke produksi | Job MySQL di 0.2, dipakai sejak Fase 3 |

---

## 8. Ringkasan Estimasi

Perkiraan kasar untuk perencanaan, belum dikalibrasi dengan kecepatan tim — kalibrasi ulang setelah Fase 0 selesai.

| Fase | Tahap | Hari-orang (±) |
|---|---|---|
| 0 Fondasi | 0.1–0.8 | 16–20 |
| 1 Periode SP | 1.1–1.5 | 15–19 |
| 2 Kelas SP | 2.1–2.4 | 12–16 |
| 3 KRS SP | 3.1–3.5 | 22–30 |
| 4 Pembayaran | 4.1–4.5 | 15–20 |
| 5 Penilaian & perkuliahan | 5.1–5.5 | 18–24 |
| 6 Transkrip, dashboard, laporan, notifikasi | 6.1–6.4 | 13–17 |
| 7 Penerimaan & go-live | 7.1–7.4 | 12–16 |
| **Total (tanpa Fase 8)** | | **± 125–160** |

Jalur kritis: Fase 0 → 1 → 2 → 3 → 4 → 6 → 7. Dengan tiga developer — satu di jalur kritis backend, satu di lajur paralel Fase 5, satu frontend yang mengikuti setiap fase — perkiraan sampai G6 sekitar **3–4 bulan kalender**.
