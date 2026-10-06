# Semester Pendek — Aturan Bisnis

> Bagian dari [paket rancangan Semester Pendek](./README.md). Semua nilai default di bawah adalah **default sistem**; nilai sebenarnya dibaca dari `university_settings` per tenant (daftar kunci: [desain-database.md §5](./desain-database.md#5-konfigurasi-per-tenant-university_settings)).

## Daftar Isi

1. [Definisi](#1-definisi)
2. [Model Periode](#2-model-periode)
3. [Siklus Hidup Periode SP](#3-siklus-hidup-periode-sp)
4. [Kelayakan Mahasiswa](#4-kelayakan-mahasiswa)
5. [Penawaran Mata Kuliah (Kelas SP)](#5-penawaran-mata-kuliah-kelas-sp)
6. [Mengulang Mata Kuliah](#6-mengulang-mata-kuliah)
7. [Alur KRS SP](#7-alur-krs-sp)
8. [Deteksi Bentrok Jadwal](#8-deteksi-bentrok-jadwal)
9. [Absensi](#9-absensi)
10. [Edge Case](#10-edge-case)
11. [Ringkasan Aturan Validasi](#11-ringkasan-aturan-validasi)

---

## 1. Definisi

**Semester Pendek** adalah `academic_terms` dengan `semester = 'pendek'`: periode akademik tambahan dalam satu tahun akademik, di luar Ganjil dan Genap, dengan durasi lebih singkat, kelas lebih intensif, dan batas SKS lebih kecil.

- **Satu SP per tahun akademik per tenant.** Ditegakkan oleh unique index yang sudah ada `(university_id, academic_year, semester)`. Kampus yang menjalankan dua SP per tahun (jarang) **tidak didukung** di fase ini — lihat [rencana-implementasi.md §4](./rencana-implementasi.md#4-yang-sengaja-tidak-dibangun).
- **Posisi waktu**: ditentukan oleh `start_date`/`end_date`, bukan oleh nama. Sistem tidak mengasumsikan SP selalu setelah Genap; urutan kronologis selalu dari `start_date` (konsisten dengan `maxSks()` yang sudah mengurutkan dengan `start_date`).
- **Label**: `"{academic_year} Pendek"`, mis. `2026/2027 Pendek`. Sama dengan format `label()` yang ada (`2026/2027 Ganjil`) dan format `invoices.period` di seeder/factory.

### 1.1 Tujuan yang didukung & bagaimana dikendalikan

| Tujuan | Dikendalikan oleh |
|---|---|
| Mengulang mata kuliah bernilai rendah | `academic.short_term.retake_max_letter_grade` (mis. `C` = hanya C/D/E boleh diulang; `null` = nilai apa pun) |
| Memperbaiki IPK | `academic.repeat_grade_policy` (`highest`/`latest`) — [penilaian-dan-transkrip.md §5](./penilaian-dan-transkrip.md#5-ips-ipk--mata-kuliah-ulang) |
| Melengkapi SKS / mempercepat studi | `academic.short_term.allow_new_courses` (bool) |
| Mengambil mata kuliah tertentu saja | Admin hanya membuka `class_sections` untuk mata kuliah itu |
| Memenuhi prasyarat | `course_prerequisites` (opsional — bila tabel kosong, pemeriksaan prasyarat dilewati) |

---

## 2. Model Periode

### 2.1 Perbandingan field yang diusulkan prompt vs skema yang ada

| Field konseptual | Di skema saat ini | Keputusan |
|---|---|---|
| `academic_year_id` | `academic_terms.academic_year` (string `"2026/2027"`) | **Reuse string.** Tidak ada entitas tahun akademik yang butuh atribut sendiri; `curriculums.academic_year` juga string. Tabel `academic_years` = overengineering. |
| `name` | Diturunkan oleh `label()` | **Reuse** (perbaiki untuk `pendek`) |
| `type` | `semester` enum | **Perluas** enum: `ganjil`, `genap`, `pendek` |
| `start_date`, `end_date` | ✅ ada | Reuse |
| `registration_start`, `registration_end` | ❌ | 🆕 `registration_starts_at`, `registration_ends_at` (`timestampTz`, nullable) |
| `max_sks` | ❌ (tier hardcoded) | 🆕 `max_credits` (nullable — `null` = pakai setting tenant) |
| `min_sks` | ❌ | **Setting tenant** `academic.short_term.min_credits` (default 0). Tidak per-term: jarang berbeda antar SP di kampus yang sama. |
| `status` | ❌ (hanya `is_current`) | 🆕 `status` enum `AcademicTermStatus` |
| `allow_course_repeat` | — | **Selalu boleh** di SP (itu tujuan utamanya); dibatasi lewat `retake_max_letter_grade` |
| `allow_new_course` | — | **Setting tenant** `academic.short_term.allow_new_courses` |
| `requires_advisor_approval` | — | **Tidak jadi kolom.** Ditentukan oleh ada/tidaknya `approval_workflows` aktif untuk `workflowable_type = 'krs_item.short_term'` (modul ApprovalWorkflow sudah mendukung kondisi & urutan approver) |
| `requires_financial_clearance` | — | **Setting tenant** `academic.short_term.require_no_outstanding_invoices` |
| `minimum_attendance_percentage` | — | **Setting tenant** `academic.min_attendance_percentage` (umum) + override `academic.short_term.min_attendance_percentage` |

Prinsip: **kolom di `academic_terms` hanya untuk hal yang realistis berbeda antar periode** (tanggal, status, batas SKS khusus). Kebijakan kampus yang stabil → `university_settings`.

### 2.2 `is_current` dan SP

`is_current` **tidak** dipindah ke term SP. Alasan: `DashboardStatsService` memakai `is_current` sebagai default filter dashboard, dan tes-tes yang ada mengasumsikan term berjalan = term reguler. Visibilitas SP untuk mahasiswa ditentukan oleh `status` (lihat §3), bukan `is_current`. Dashboard SP memakai filter `academic_term_id` eksplisit (fitur filter ✅ sudah ada).

### 2.3 Validasi tanggal term SP

| Aturan | Pesan (409/422) |
|---|---|
| `start_date < end_date` | 422 `end_date` harus setelah `start_date` |
| `registration_starts_at < registration_ends_at` (bila keduanya diisi) | 422 |
| `registration_ends_at <= start_date` + `academic.short_term.late_registration_days` (default 0) | 422 — pendaftaran tidak boleh ditutup setelah kuliah berjalan lebih dari N hari |
| Rentang `[start_date, end_date]` **tidak boleh beririsan** dengan term lain milik tenant yang sama | 409 "Tanggal Semester Pendek beririsan dengan {label}." — wajib, karena `maxSks()`, urutan transkrip, dan bentrok jadwal lintas periode bergantung pada urutan `start_date` yang tidak ambigu |
| `academic_year` harus berformat `YYYY/YYYY+1` | 422 |
| Satu SP per `academic_year` | 409 (unique index yang ada) |

---

## 3. Siklus Hidup Periode SP

### 3.1 Diagram transisi

```
                 ┌─────────────────────────────┐
                 ▼                             │ (kembali, belum ada KRS)
            ┌─────────┐   publish    ┌─────────┴┐  open    ┌───────────────────┐
  create ──►│  DRAFT  │─────────────►│ PLANNED  │─────────►│ REGISTRATION_OPEN │
            └─────────┘              └──────────┘          └─────────┬─────────┘
                                                              close  │  ▲ reopen
                                                                     ▼  │ (sebelum start_date)
                                                         ┌──────────────┴──────┐
                                                         │ REGISTRATION_CLOSED │
                                                         └──────────┬──────────┘
                                                              start │
                                                                    ▼
                                                              ┌──────────┐
                                                              │ ONGOING  │
                                                              └────┬─────┘
                                                     begin grading │
                                                                   ▼
                                                              ┌──────────┐
                                                              │ GRADING  │
                                                              └────┬─────┘
                                                complete (semua    │
                                                nilai finalized)   ▼
                                                              ┌───────────┐  archive  ┌──────────┐
                                                              │ COMPLETED │──────────►│ ARCHIVED │
                                                              └───────────┘           └──────────┘
```

Transisi selain yang digambar **ditolak** (409 "Transisi status dari X ke Y tidak diizinkan."). Transisi dijalankan **hanya** lewat `PATCH /academic-terms/{id}/status` — kolom `status` tidak ada di `$fillable` untuk endpoint `PUT` (perlindungan mass assignment).

### 3.2 Detail per status

| Status | Arti | Yang boleh mengubah | Transisi keluar | Pembatasan | Konsekuensi bisnis |
|---|---|---|---|---|---|
| **DRAFT** | Sedang dikonfigurasi | `academic_administrator` | → PLANNED | Tidak terlihat oleh mahasiswa & dosen. Boleh dihapus (`DELETE`) bila belum ada `class_sections`. | — |
| **PLANNED** | Diumumkan, pendaftaran belum dibuka | idem | → REGISTRATION_OPEN, → DRAFT (bila 0 `krs_items`) | Kelas, dosen, jadwal masih bebas diubah. Mahasiswa bisa melihat daftar kelas tapi tombol daftar nonaktif. | Notifikasi `short_term.announced` (opsional) |
| **REGISTRATION_OPEN** | Mahasiswa bisa mendaftar | idem | → REGISTRATION_CLOSED | Minimal 1 `class_section` aktif saat masuk status ini. Pendaftaran mandiri hanya diterima bila juga `now()` di dalam `[registration_starts_at, registration_ends_at]` (bila diisi) — jendela waktu ditegakkan **tanpa** butuh cron. Kapasitas kelas tidak boleh diturunkan di bawah jumlah peserta `pending`+`enrolled`. | Notifikasi `short_term.registration_opened` ke mahasiswa yang layak |
| **REGISTRATION_CLOSED** | Pendaftaran mandiri ditutup; admin memproses sisa persetujuan/pembayaran, membatalkan kelas di bawah minimum | idem | → ONGOING, → REGISTRATION_OPEN (hanya bila `now() < start_date`) | Mahasiswa tidak bisa daftar/batal sendiri. Admin masih bisa `POST /krs-items` (dengan `reason`, tercatat audit). | Admin menjalankan pembatalan kelas sepi (§5.3) |
| **ONGOING** | Perkuliahan berjalan | idem | → GRADING | Tidak ada pendaftaran baru kecuali admin (late add, wajib `reason`). Item `pending` yang belum lunas otomatis kedaluwarsa (lihat [pembayaran-dan-notifikasi.md §2.4](./pembayaran-dan-notifikasi.md#24-kedaluwarsa)). Absensi & ujian aktif. | — |
| **GRADING** | Periode input nilai | idem | → COMPLETED | Dosen input/submit nilai; admin finalisasi. Absensi tetap bisa dikoreksi. | Notifikasi `short_term.grading_deadline` ke dosen |
| **COMPLETED** | Seluruh nilai final | idem | → ARCHIVED | Masuk hanya bila **semua** `grades` peserta `enrolled` berstatus `finalized` (atau admin memaksa dengan `reason`, tercatat). Nilai hanya bisa diubah lewat revisi nilai. | Nilai tampil di KHS/transkrip; notifikasi `short_term.grades_released` |
| **ARCHIVED** | Arsip baca-saja | idem | — (final) | Semua operasi tulis yang menyentuh term ini ditolak 409, **termasuk** revisi nilai (harus lewat proses manual di luar sistem, atau un-archive oleh `super_admin` — tidak disediakan endpoint). | Data tetap dibaca untuk transkrip/IPK |

### 3.3 Penegakan untuk semester reguler

Kolom `status` ditambahkan ke **semua** `academic_terms` (backfill: `is_current = true` → `ongoing`, `end_date < today` → `completed`, sisanya → `planned`), tetapi **pada fase pertama penegakan status hanya berlaku untuk term `pendek`**. Alasan: admin saat ini mendaftarkan KRS reguler kapan saja lewat `POST /krs-items`; menegakkan jendela pendaftaran untuk reguler adalah perubahan perilaku yang butuh keputusan terpisah (blueprint §4.10 "Pengaturan periode KRS"). Desainnya sudah siap untuk diperluas.

---

## 4. Kelayakan Mahasiswa

### 4.1 Pipeline

Dijalankan oleh satu kelas baru `Modules\Academic\Services\EnrollmentEligibility` (dipanggil dari `AcademicRecordService::enroll()` **di dalam** transaksi & kunci yang sudah ada). Urutan dari yang termurah dan paling umum gagal:

```
 1. Status periode & jendela pendaftaran     (term SP)
 2. Status mahasiswa                          (semua term — 🔧 belum dicek sama sekali hari ini)
 3. Kewajiban keuangan                        (term SP, bila setting aktif)
 4. Kelas aktif & tidak dibatalkan            (✅ ada: is_active)
 5. Kelas milik prodi mahasiswa               (term SP)
 6. Duplikat: kelas yang sama                 (✅ ada)
 7. Duplikat: mata kuliah yang sama, kelas lain, term sama   (🆕 semua term)
 8. Kelayakan mata kuliah: baru vs ulang      (term SP)
 9. Prasyarat                                 (semua term, bila course_prerequisites terisi)
10. Batas SKS                                 (✅ ada, 🔧 cabang SP)
11. Bentrok jadwal mahasiswa                  (semua term, bila jadwal terisi)
12. Kapasitas                                 (✅ ada, 🔧 hitung pending juga)
                    │
                    ▼
     Lolos → buat/aktifkan krs_item (status pending atau enrolled, lihat §7)
     Gagal → ConflictException (409) dengan pesan bahasa Indonesia + `code` mesin
```

Untuk **tampilan** daftar kelas mahasiswa, pemeriksaan 1–11 juga dijalankan dalam mode "evaluasi" (tanpa kunci, tanpa menulis) sehingga UI dapat menampilkan alasan sebelum mahasiswa menekan daftar (`eligibility: {eligible: false, reasons: [...]}` — lihat [desain-api.md](./desain-api.md#31-get-studentacademic-termstermoffering)). Hasil evaluasi tampilan **tidak pernah** dipercaya saat submit — submit selalu memvalidasi ulang di dalam kunci.

Respons error mengikuti `ApiResponse::error()` yang ada, ditambah kunci `errors.code` agar frontend bisa memetakan pesan:

| # | Pemeriksaan | Kondisi gagal | `code` | Pesan |
|---|---|---|---|---|
| 1 | Periode | `term.status ≠ registration_open`, atau di luar jendela waktu | `TERM_NOT_OPEN` | "Pendaftaran Semester Pendek belum dibuka atau sudah ditutup." |
| 2 | Status mahasiswa | `status ≠ active` | `STUDENT_NOT_ACTIVE` | "Hanya mahasiswa berstatus aktif yang dapat mendaftar." |
| 3 | Keuangan | Ada `invoices` `unpaid`/`partial` dengan `due_date < today` (bukan tagihan SP itu sendiri) | `OUTSTANDING_INVOICE` | "Masih ada tagihan yang belum lunas." |
| 4 | Kelas | `is_active = false` atau `cancelled_at ≠ null` | `CLASS_INACTIVE` | (pesan yang ada) "Kelas ini sudah tidak aktif…" |
| 5 | Prodi | `class_section.study_program_id ≠ student.study_program_id` | `CLASS_OTHER_PROGRAM` | "Kelas ini bukan untuk program studi Anda." |
| 6 | Duplikat kelas | Sudah `pending`/`enrolled` di kelas ini | `ALREADY_ENROLLED` | (pesan yang ada) |
| 7 | Duplikat MK | Sudah `pending`/`enrolled` di kelas lain dengan `course_id` sama di term sama | `COURSE_ALREADY_TAKEN` | "Anda sudah mengambil mata kuliah ini di kelas lain." |
| 8 | Baru vs ulang | Lihat §6.3 | `COURSE_NOT_RETAKABLE` / `NEW_COURSE_NOT_ALLOWED` / `COURSE_IN_PROGRESS` | — |
| 9 | Prasyarat | Ada prasyarat yang belum lulus | `PREREQUISITE_NOT_MET` | "Prasyarat belum terpenuhi: {kode MK}." |
| 10 | SKS | Total `pending`+`enrolled` di term + SKS kelas > batas | `SKS_LIMIT_EXCEEDED` | (pesan yang ada) |
| 11 | Bentrok | Lihat §8 | `SCHEDULE_CONFLICT` | "Jadwal bentrok dengan {MK} ({hari jam})." |
| 12 | Kapasitas | `pending`(belum kedaluwarsa)+`enrolled` ≥ `capacity` | `CLASS_FULL` | (pesan yang ada) "Kelas sudah penuh." |

### 4.2 Status mahasiswa

| `StudentStatus` | Boleh daftar SP? |
|---|---|
| `active` | ✅ |
| `leave` (cuti) | ❌ |
| `inactive` | ❌ |
| `graduated` | ❌ |
| `dropped_out` | ❌ |

Pemeriksaan ini juga ditambahkan untuk term reguler — saat ini `enroll()` menerima mahasiswa berstatus apa pun. Ini perubahan perilaku kecil yang **disengaja**; dicatat di tes regresi.

### 4.3 Kewajiban keuangan

Bila `academic.short_term.require_no_outstanding_invoices = true` (default): tolak bila mahasiswa punya `invoices` berstatus `unpaid`/`partial` yang `due_date`-nya sudah lewat. Tagihan SP yang sedang berjalan untuk term yang sama **dikecualikan** (supaya mahasiswa tetap bisa menambah mata kuliah sebelum membayar).

### 4.4 Duplikat

- **Kelas yang sama**: sudah ditangani unique index `(student_id, class_section_id)` + pengecekan di `enroll()`. Baris `dropped`/`rejected` dipakai ulang (perilaku yang ada).
- **Mata kuliah yang sama, kelas lain, term yang sama**: 🆕 — saat ini mahasiswa bisa terdaftar di Basis Data kelas A **dan** kelas B. Wajib ditutup untuk semua term.
- **Permintaan ganda serentak** (double-click, retry jaringan): diserialisasi oleh kunci baris mahasiswa (lihat [keamanan-audit-integritas.md §3](./keamanan-audit-integritas.md#3-konkurensi--integritas-data)); permintaan kedua melihat baris pertama dan gagal di pemeriksaan 6.

### 4.5 Batas SKS

```
maxSks(student, classSection):
    term = classSection.academicTerm

    if term.semester == Pendek:
        return term.max_credits
            ?? setting('academic.short_term.max_credits', default 9)

    # Semester reguler — perilaku lama, dengan satu perbaikan:
    lastTerm = term reguler (ganjil/genap) terakhir sebelum term.start_date
               ← SP DIKECUALIKAN kecuali setting
                 'academic.short_term.counts_toward_next_term_sks_quota' = true
    ...tier IP seperti sekarang
```

Contoh kasus yang wajib benar:

| Mahasiswa | IP Genap 2026/2027 | Ikut SP 2026/2027? | Batas SKS Ganjil 2027/2028 |
|---|---|---|---|
| A | 1,80 | Tidak | **18** (hari ini akan jadi 24 — bug §3.1 README) |
| B | 1,80 | Ya, IP SP 3,50 | **18** (default setting); **24** bila `counts_toward_next_term_sks_quota = true` |
| C | 3,20 | Tidak | 24 |

`min_credits` SP (default 0) hanya diperiksa saat mahasiswa **membatalkan** item: pembatalan yang membuat total di bawah minimum tetapi > 0 ditolak (`SKS_BELOW_MINIMUM`). Pembatalan seluruh item selalu boleh.

---

## 5. Penawaran Mata Kuliah (Kelas SP)

### 5.1 Model

```
courses (✅ master MK: code, name, credits, curriculum_id)
   │
   └── class_sections (✅, academic_term_id = term SP)  ← "penawaran SP" = kelas di term SP
          ├── class_section_lecturers   (🆕 umum)  dosen pengampu
          ├── class_section_schedules   (🆕 umum)  hari/jam/ruang
          └── krs_items                 (✅)        peserta
```

Tidak ada tabel `course_offerings`: dalam skema ini, satu `class_section` sudah merupakan pasangan *(mata kuliah × periode × kelas)* — persis definisi penawaran. Menambah lapisan offering di atasnya hanya untuk SP akan menjadi satu-satunya tempat di sistem yang punya lapisan itu.

### 5.2 Atribut per penawaran

| Atribut | Sumber |
|---|---|
| Mata kuliah, kode, nama, SKS | ✅ `courses` via `class_sections.course_id` |
| Kurikulum | ✅ `courses.curriculum_id` |
| Prasyarat | 🆕 `course_prerequisites` (per MK, bukan per kelas) |
| Kapasitas | ✅ `class_sections.capacity` |
| Minimum peserta | 🆕 `class_sections.min_participants` (nullable → setting `academic.short_term.min_participants`, default 5) |
| Ketersediaan pendaftaran | Turunan: `term.status` + jendela + `is_active` + `cancelled_at` + sisa kursi |
| Biaya | Turunan: `credits × setting('academic.short_term.fee_per_credit')`. Tidak per kelas di fase ini. |
| Dosen | 🆕 `class_section_lecturers` |
| Jadwal & ruang | 🆕 `class_section_schedules` |
| Status | ✅ `is_active` + 🆕 `cancelled_at`/`cancellation_reason` |

### 5.3 Pembatalan kelas sepi

Saat term `REGISTRATION_CLOSED`, dashboard admin menandai kelas dengan `pending+enrolled < min_participants`. Admin memutuskan per kelas (tidak otomatis — keputusan akademik):

`PATCH /class-sections/{id}/cancel {reason}` dalam satu transaksi:
1. `cancelled_at = now()`, `is_active = false`, `cancellation_reason`.
2. Semua `krs_items` `pending`/`enrolled` di kelas itu → `dropped`.
3. Tagihan terkait: bila `unpaid` dan semua item di tagihan itu batal → `cancelled`; bila hanya sebagian → jumlah tagihan dikurangi (hanya bila belum ada pembayaran); bila sudah dibayar → ditandai untuk refund manual oleh Keuangan (lihat [pembayaran-dan-notifikasi.md §2.6](./pembayaran-dan-notifikasi.md#26-refund)).
4. Notifikasi `short_term.class_cancelled` ke peserta & dosen.
5. Audit: `Auditable` pada `ClassSection` + `activity_logs` event `CLASS_CANCELLED`.

Pembatalan ditolak (409) bila kelas sudah punya `grades` atau `exam_attempts`.

---

## 6. Mengulang Mata Kuliah

### 6.1 Prinsip: riwayat tidak pernah dihapus atau ditimpa

```
Basis Data (IF302, 3 SKS)

Percobaan #1   2026/2027 Ganjil   krs_item #K1 → grade D   ← TETAP ADA
Percobaan #2   2026/2027 Pendek   krs_item #K2 → grade B   ← baris baru
```

Ini **otomatis** benar dengan skema saat ini: unique index KRS adalah `(student_id, class_section_id)`, dan kelas SP adalah `class_section` berbeda dari kelas Ganjil, sehingga percobaan baru selalu baris `krs_items` + `grades` baru. **Tidak perlu** kolom `attempt_number` atau `replaced_by` yang tersimpan — keduanya diturunkan saat dibaca (lihat §6.2), sehingga tidak bisa tidak sinkron.

### 6.2 Identitas "mata kuliah yang sama"

Dua percobaan dianggap mata kuliah yang sama bila `class_sections.course_id` sama.

**Keterbatasan yang disadari:** bila kurikulum berganti dan MK lama punya `course_id` berbeda dengan MK pengganti, sistem tidak tahu keduanya setara. Penyetaraan MK (blueprint §4.17 "Konversi nilai") adalah fitur umum terpisah (`course_equivalences`) — **tidak** dibangun untuk SP.

Turunan saat dibaca:
- `attempt_number` = urutan `krs_items` bernilai untuk `(student, course_id)` menurut `academic_terms.start_date`.
- `is_repeat` = `attempt_number > 1`.
- `counts_toward_ipk` = hasil kebijakan §6.4.

### 6.3 Kelayakan "baru" vs "ulang" di SP

```
prev = percobaan sebelumnya mahasiswa untuk course_id ini (krs_items status enrolled), terbaru dulu

if prev ada DAN prev belum punya letter_grade DAN term prev belum COMPLETED/ARCHIVED:
    tolak COURSE_IN_PROGRESS  "Mata kuliah ini sedang Anda ambil di {label}."

if prev tidak ada:                                  # mata kuliah BARU
    if setting allow_new_courses == false:  tolak NEW_COURSE_NOT_ALLOWED
    else: lanjut

if prev ada (sudah bernilai):                       # mata kuliah ULANG
    best = nilai efektif saat ini menurut kebijakan (§6.4)
    limit = setting retake_max_letter_grade          # mis. 'C'
    if limit != null AND best.weight() > LetterGrade(limit).weight():
        tolak COURSE_NOT_RETAKABLE  "Nilai {best} tidak memenuhi syarat untuk diulang (maksimal {limit})."
```

### 6.4 Kebijakan nilai yang dihitung

`academic.repeat_grade_policy` (setting tenant, **berlaku untuk semua periode**, bukan hanya SP):

| Nilai | Aturan | Seri (bobot sama) |
|---|---|---|
| `highest` (default) | Percobaan dengan `letter_grade.weight()` tertinggi | Ambil yang terbaru |
| `latest` | Percobaan terbaru (menurut `start_date` term) yang nilainya sudah **dihitung** (`grades.status ≠ draft`, lihat [penilaian-dan-transkrip.md §2.2](./penilaian-dan-transkrip.md#22-status-nilai)) | — |

Kebijakan institusional lain (mis. "nilai SP maksimal B", "rata-rata") **tidak** diimplementasikan sebagai kode; bila dibutuhkan, ditambah sebagai nilai enum baru beserta strateginya. Detail perhitungan & contoh: [penilaian-dan-transkrip.md §5](./penilaian-dan-transkrip.md#5-ips-ipk--mata-kuliah-ulang).

---

## 7. Alur KRS SP

### 7.1 Alur mahasiswa

```
Mahasiswa login (role student, students.user_id ✅)
    │
    ▼
GET student/academic-terms?filter[semester]=pendek       ← term berstatus ≥ PLANNED
    │
    ▼
GET student/academic-terms/{term}/offering                ← kelas + sisa kursi + jadwal + eligibility per kelas
    │
    ▼
Pilih satu atau lebih kelas (UI menghitung total SKS & biaya sementara)
    │
    ▼
POST student/krs-items {class_section_ids: [...]}         ← atomik: semua lolos atau semua ditolak
    │  pemeriksaan §4.1 berjalan di dalam transaksi + kunci
    ▼
krs_items dibuat, status = pending
    │
    ├── Workflow persetujuan aktif? ──Ya──► ApprovalRequest per item (requestable = KrsItem)
    │                                         │ approve ──► lanjut ke pembayaran
    │                                         │ reject  ──► status = rejected, kursi dilepas
    │
    └── Tidak ────────────────────────────► langsung ke pembayaran
                                              │
                         biaya = 0? ──Ya──►  status = enrolled
                                              │
                                              ▼ Tidak
                         Invoice dibuat (unpaid, due_date = now + payment_due_days)
                         krs_items.invoice_id = invoice
                                              │
                         Keuangan catat pembayaran → invoice paid
                                              │
                                              ▼
                         status = enrolled (event-driven, lihat pembayaran-dan-notifikasi.md)
```

### 7.2 Status `krs_items`

| Status | Arti | Menahan kursi? | Dihitung SKS? | Bisa diabsen/dinilai/ikut ujian? |
|---|---|---|---|---|
| `pending` 🆕 | Menunggu persetujuan dan/atau pembayaran | ✅ (sampai kedaluwarsa) | ✅ | ❌ |
| `enrolled` ✅ | Terdaftar penuh | ✅ | ✅ | ✅ |
| `rejected` 🆕 | Ditolak approver | ❌ | ❌ | ❌ |
| `dropped` ✅ | Dibatalkan mahasiswa/admin/sistem (kedaluwarsa, kelas batal) | ❌ | ❌ | ❌ |

Sub-tahap `pending` **tidak** disimpan sebagai status terpisah — diturunkan dari data terkait supaya tidak ada dua sumber kebenaran:
- *Menunggu persetujuan*: ada `approval_requests` untuk item ini dengan status `submitted`/`in_progress`.
- *Menunggu pembayaran*: `invoice_id` terisi dan invoice `unpaid`/`partial`.

Karena semua kode yang ada (absensi, nilai, ujian, transkrip) sudah memfilter `status = enrolled`, item `pending` **otomatis** tidak bisa diabsen/dinilai/ikut ujian — tidak ada perubahan yang dibutuhkan di sana.

### 7.3 Persetujuan: otomatis vs dengan approver

| Konfigurasi | Perilaku |
|---|---|
| Tidak ada `approval_workflows` aktif untuk `krs_item.short_term` | **Otomatis**: langsung ke tahap pembayaran |
| Ada workflow aktif (dipilih oleh `WorkflowSelector` dengan konteks `{study_program_id, faculty_id}`) | `ApprovalRequestService::submit()` per item; approver = role (`academic_administrator`, `head_of_study_program`) atau user tertentu — **sudah didukung** `ApprovalApproverType` |
| Approver = Dosen PA | ⛔ Butuh relasi dosen-wali (belum ada). Sampai itu ada, gunakan approver berbasis role. |

Listener baru `ApplyKrsApprovalDecision` mendengarkan event yang **sudah ada** `ApprovalRequestApproved` / `ApprovalRequestRejected` dan bertindak hanya bila `requestable` adalah `KrsItem`.

Persetujuan dilakukan **per item** (bukan per paket KRS) karena: (a) modul approval butuh satu model `requestable`, dan tidak ada model "paket KRS"; (b) mahasiswa SP biasanya mengambil 1–3 MK; (c) approver bisa menyetujui sebagian. Menambah tabel header `krs_submissions` hanya untuk ini ditolak (lihat [rencana-implementasi.md §4](./rencana-implementasi.md#4-yang-sengaja-tidak-dibangun)).

### 7.4 Pembatalan oleh mahasiswa

`PATCH student/krs-items/{id}/cancel` — diperbolehkan bila:
- term `REGISTRATION_OPEN` (dan di dalam jendela), **dan**
- item `pending`, atau `enrolled` dengan biaya 0, **dan**
- belum ada `grades`/`attendances`/`exam_attempts` (aturan `drop()` yang ada diperluas).

Item `enrolled` yang sudah dibayar **tidak** bisa dibatalkan mandiri — harus lewat admin (konsekuensi refund). Pembatalan item `pending` dengan invoice `unpaid` → jumlah invoice disesuaikan atau invoice `cancelled` bila kosong.

---

## 8. Deteksi Bentrok Jadwal

### 8.1 Data

`class_section_schedules` (🆕, lihat [desain-database.md §3.3](./desain-database.md#33-class_section_schedules--umum)): `day_of_week` (1=Senin…7=Minggu, ISO-8601), `starts_at` (TIME), `ends_at` (TIME), `room` (string nullable), opsional `effective_from`/`effective_until` (DATE) untuk SP yang intensif hanya di minggu tertentu.

### 8.2 Logika

Dua slot `a`, `b` bentrok bila **semua** benar:

```
a.day_of_week == b.day_of_week
AND a.starts_at < b.ends_at          ← interval setengah-terbuka [start, end)
AND b.starts_at < a.ends_at
AND rentang tanggal efektif a dan b beririsan
    (rentang efektif = [effective_from ?? term.start_date, effective_until ?? term.end_date])
```

Setengah-terbuka berarti 09:00–11:00 dan 11:00–12:00 **tidak** bentrok; 09:00–11:00 dan 10:00–12:00 **bentrok**.

Karena tanggal efektif ikut dibandingkan, slot SP dan slot reguler tidak pernah bentrok selama term-nya tidak beririsan (dan §2.3 melarang irisan) — pemeriksaan tetap generik tanpa cabang khusus SP.

### 8.3 Tiga jenis bentrok

| Jenis | Dibandingkan dengan | Kapan dicek | Sifat |
|---|---|---|---|
| **Mahasiswa** | Slot semua kelas tempat mahasiswa `pending`/`enrolled` | Saat mendaftar (§4.1 #11) | **Blokir** (409) |
| **Dosen** | Slot semua kelas yang diampu dosen itu (`class_section_lecturers`) | Saat admin menugaskan dosen atau mengubah jadwal | **Blokir** (409), admin bisa memaksa dengan `force: true` + `reason` (dosen kadang memang mengajar gabungan) |
| **Ruangan** | Slot lain dengan `room` sama (dinormalisasi: trim + lowercase + spasi tunggal) | Saat admin menyimpan jadwal | **Blokir** (409); dilewati bila `room` kosong atau bernilai `online` |

Ruangan adalah string bebas karena modul Sarana & Prasarana (blueprint §4.27) belum ada. Bila modul itu dibuat, kolom diganti `room_id` dan aturan perbandingan tetap.

### 8.4 Perubahan jadwal setelah pendaftaran

Admin mengubah jadwal kelas yang sudah punya peserta → sistem **tidak** memblokir, tetapi mengembalikan daftar mahasiswa yang menjadi bentrok (`warnings.student_conflicts[]`) dan mengirim notifikasi `short_term.schedule_changed` ke semua peserta. Alasan: mahasiswa tidak bisa dibatalkan otomatis atas keputusan admin; admin menyelesaikannya secara manual.

---

## 9. Absensi

### 9.1 Reuse penuh

```
class_sections (term SP)
    └── krs_items (enrolled)
          └── attendances (meeting_number, meeting_date, status, notes)   ✅ apa adanya
```

Endpoint `POST /class-sections/{id}/attendances` (batch, transaksional) tidak berubah, kecuali tambahan pemeriksaan kepemilikan dosen (⛔ prasyarat umum) dan penolakan bila term `ARCHIVED`.

### 9.2 Pemetaan status

Prompt menyebut Present/Late/Excused/Absent; sistem memakai `AttendanceStatus` yang ada:

| Konsep | `AttendanceStatus` | Dihitung hadir untuk persentase? |
|---|---|---|
| Present | `present` | ✅ |
| Late | — (tidak ada; dicatat `present` + `notes`) | ✅ |
| Excused | `permitted` (izin), `sick` (sakit) | Sesuai setting `academic.attendance_excused_counts_as_present` (default `true`) |
| Absent | `absent` | ❌ |

Menambah status `late` adalah keputusan umum (blueprint §4.14 menyebutnya), bukan kebutuhan SP — tidak dibuat di sini.

### 9.3 Minimum kehadiran

```
persentase = hadir / jumlah pertemuan yang SUDAH dicatat untuk kelas itu × 100
             (pertemuan = DISTINCT meeting_number di attendances kelas tsb.)

batas = setting('academic.short_term.min_attendance_percentage')
        ?? setting('academic.min_attendance_percentage', default 75)
```

Dihitung saat dibaca (method baru `AcademicRecordService::attendanceRate(KrsItem)`), tidak disimpan.

**Efek** (masing-masing dapat dimatikan per tenant):

| Efek | Setting | Default |
|---|---|---|
| Tidak boleh mulai ujian (akhir) | `academic.attendance_blocks_exam` | `false` |
| Peringatan ke dosen saat input nilai | selalu | — |
| Nilai otomatis E | **tidak dibuat** — keputusan dosen/akademik, bukan sistem | — |

Bila `attendance_blocks_exam = true`, `ExamService::resolveEligibleKrsItem()` menolak dengan `ConflictException` "Kehadiran Anda {x}% di bawah batas {y}%." — satu-satunya perubahan pada modul Ujian, dan berlaku umum.

---

## 10. Edge Case

| # | Kasus | Perilaku sistem |
|---|---|---|
| E1 | Kelas penuh | 409 `CLASS_FULL`. UI menampilkan "Penuh" dan menonaktifkan tombol. Tidak ada daftar tunggu di fase ini. |
| E2 | Mahasiswa membatalkan pendaftaran | §7.4. Kursi langsung dilepas (karena dihitung dari status). |
| E3 | Pendaftaran ditutup saat mahasiswa sedang mengisi | Submit ditolak `TERM_NOT_OPEN` (pemeriksaan di server, bukan UI). Item yang sudah `pending` tetap diproses. |
| E4 | Kelas dibatalkan | §5.3. |
| E5 | Dosen berhalangan | Admin mengganti dosen lewat `PUT /class-sections/{id}/lecturers` (cek bentrok dosen baru). Nilai/absensi yang sudah ada tetap (terikat `krs_item`, bukan dosen). Notifikasi ke peserta. |
| E6 | Jadwal berubah setelah pendaftaran | §8.4. |
| E7 | Mahasiswa menjadi nonaktif setelah terdaftar | Item yang ada **tidak** dibatalkan otomatis (status mahasiswa bisa dikoreksi). Pendaftaran baru ditolak. Dashboard admin menandai peserta non-aktif. |
| E8 | Pembayaran kedaluwarsa | Item `pending` → `dropped`, invoice → `cancelled`, kursi dilepas. Dijalankan *lazy* (di dalam `enroll()` untuk kelas yang dikunci, dan saat membaca daftar) **dan** oleh command terjadwal. [pembayaran-dan-notifikasi.md §2.4](./pembayaran-dan-notifikasi.md#24-kedaluwarsa) |
| E9 | Pembayaran gagal | Tidak ada gateway saat ini; pembayaran dicatat manual hanya bila sukses. Untuk gateway masa depan: status transaksi gagal tidak mengubah invoice. |
| E10 | Webhook ganda | Idempoten lewat unique `(provider, external_id)` — rancangan masa depan, [pembayaran-dan-notifikasi.md §2.7](./pembayaran-dan-notifikasi.md#27-payment-gateway-masa-depan). |
| E11 | Permintaan pendaftaran ganda | §4.4 — kunci baris mahasiswa + unique index. Respons kedua 409 `ALREADY_ENROLLED`. |
| E12 | Mahasiswa mengulang MK | §6. |
| E13 | Tidak ada nilai sebelumnya | Diperlakukan sebagai MK baru (§6.3). |
| E14 | Prasyarat belum terpenuhi | 409 `PREREQUISITE_NOT_MET`. |
| E15 | Prasyarat di-override | Hanya admin lewat `POST /krs-items` dengan `override: ["prerequisite"]` + `reason` (wajib, min 10 karakter), permission `krs.approve`. Dicatat `activity_logs` `PREREQUISITE_OVERRIDDEN`. Override yang diizinkan: `prerequisite`, `sks_limit`, `schedule_conflict`, `capacity`. **Tidak** bisa override: status mahasiswa, tenant, duplikat MK. |
| E16 | Nilai sudah final | `PUT /krs-items/{id}/grade` → 409 "Nilai sudah difinalisasi; ajukan revisi nilai." |
| E17 | Koreksi nilai diminta | Revisi nilai lewat ApprovalWorkflow. [penilaian-dan-transkrip.md §4](./penilaian-dan-transkrip.md#4-revisi-nilai). |
| E18 | SP diarsipkan | Semua tulis 409 (§3.2). Baca tetap. |
| E19 | Tanggal periode beririsan | 409 saat simpan (§2.3). |
| E20 | Mahasiswa melebihi SKS | 409 `SKS_LIMIT_EXCEEDED`. Untuk submit multi-kelas: seluruh batch ditolak, pesan menyebut total. |
| E21 | Bentrok jadwal dosen | 409 saat penugasan (§8.3), bisa `force` oleh admin. |
| E22 | Bentrok ruangan | 409 saat simpan jadwal. |
| E23 | KRS dari periode lain | Tidak memengaruhi batas SKS SP (dihitung per `academic_term_id`). Bentrok jadwal lintas periode hanya mungkin bila tanggal efektif beririsan (§8.2). MK yang masih berjalan di periode lain → `COURSE_IN_PROGRESS` (§6.3). |
| E24 | Perbedaan kebijakan antar universitas | Seluruh variasi lewat `university_settings` & `approval_workflows` per tenant; tidak ada cabang kode per universitas. |
| E25 | Kapasitas diturunkan di bawah jumlah peserta | 409 "Kapasitas tidak boleh lebih kecil dari {n} peserta terdaftar." |
| E26 | Kelas dipindah ke term lain setelah ada peserta | Ditolak 409 (`academic_term_id` di `krs_items` didenormalisasi; memindah kelas akan membuat data tidak konsisten). |
| E27 | Term SP dihapus | Hanya status DRAFT tanpa kelas. Selain itu 409. (FK `cascadeOnDelete` yang ada di `class_sections`/`krs_items` **tidak boleh** sampai terpicu untuk term yang punya data.) |

---

## 11. Ringkasan Aturan Validasi

### 11.1 Validasi input (FormRequest → 422)

| Request | Aturan |
|---|---|
| `StoreAcademicTermRequest` | `academic_year` regex `^\d{4}/\d{4}$` dan tahun kedua = pertama + 1; `semester` `Rule::enum(AcademicSemester)`; `start_date`/`end_date` date, `end_date` after `start_date`; `registration_starts_at`/`registration_ends_at` nullable date, berurutan; `max_credits` nullable integer 1–24 |
| `UpdateAcademicTermStatusRequest` | `status` `Rule::enum(AcademicTermStatus)`; `reason` nullable string max 255 (wajib untuk transisi paksa) |
| `StoreClassSectionRequest` | `course_id`, `academic_term_id`, `study_program_id` required string (keberadaan dicek di service via `findOrFail` ter-scope — konvensi `StoreKrsItemRequest`); `class_code` required max 10; `capacity` integer 1–500; `min_participants` nullable integer ≤ `capacity` |
| `SyncClassSectionSchedulesRequest` | `schedules` array min 1; tiap item `day_of_week` integer 1–7, `starts_at`/`ends_at` `date_format:H:i`, `ends_at` after `starts_at`; `room` nullable max 50; `force` boolean; `reason` required_if force |
| `SyncClassSectionLecturersRequest` | `lecturers` array min 1; tiap item `lecturer_id` string, `role` in `coordinator,member`; tepat satu `coordinator` |
| `StoreStudentKrsRequest` | `class_section_ids` array min 1 max 10, distinct, tiap item string. **Tidak** menerima `student_id` — diambil dari `auth()->user()->student` |
| `StoreKrsItemRequest` (admin, diperluas) | + `override` nullable array in `prerequisite,sks_limit,schedule_conflict,capacity`; `reason` required_with override, min 10 |
| `UpsertGradeRequest` | tidak berubah |
| `RequestGradeRevisionRequest` | `score` 0–100; `letter_grade` nullable enum; `reason` required min 20 |
| `RecordPaymentRequest` | `amount` numeric > 0 ≤ sisa tagihan; `paid_at` date ≤ today; `method` string max 50; `reference` nullable max 100 |

### 11.2 Validasi bisnis (service → 409 `ConflictException`)

Semua pemeriksaan §4.1, transisi status §3, aturan tanggal §2.3, bentrok §8, pembatalan §5.3/§7.4, finalisasi & revisi nilai ([penilaian-dan-transkrip.md](./penilaian-dan-transkrip.md)).

### 11.3 Validasi tenant (→ 404)

Setiap ID (term, kelas, MK, dosen, mahasiswa) di-resolve lewat `Model::query()->findOrFail()` yang ter-scope `TenantScoped` — **tidak pernah** `Rule::exists` (query mentah, bocor lintas tenant). Konvensi ini sudah didokumentasikan di `StoreKrsItemRequest` dan `StudentController::store()`.
