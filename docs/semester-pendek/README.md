# Rancangan Modul Semester Pendek

> **Status:** Dokumen desain teknis — **belum diimplementasikan**. Disusun dari kondisi kode aktual per 2026-10-06 (backend `app/Modules/*`, frontend `frontend/src`, mobile `mobile/lib`), bukan dari asumsi.
> **Dibuat:** 2026-10-06
> **Pelengkap:** [RANCANGAN-AKUN-AKADEMIK.md](../RANCANGAN-AKUN-AKADEMIK.md) (peta modul akademik & gap), [RANCANGAN-AKUN-DOSEN.md](../RANCANGAN-AKUN-DOSEN.md) §8 (celah kepemilikan kelas), [RANCANGAN-AKUN-MAHASISWA.md](../RANCANGAN-AKUN-MAHASISWA.md) §4.3 (portal belum tersambung), [RANCANGAN-APLIKASI.md](../RANCANGAN-APLIKASI.md) §4.10/§4.16/§4.17/§6 (blueprint KRS, penilaian, transkrip, workflow perubahan nilai), [MULTI-TENANT-ARCHITECTURE.md](../MULTI-TENANT-ARCHITECTURE.md).
>
> Konvensi penandaan di seluruh paket dokumen ini: **✅ Ada** (sudah di kode, dipakai ulang apa adanya) · **🔧 Ubah** (ada, perlu diperluas) · **🆕 Baru** (belum ada sama sekali) · **⛔ Prasyarat** (gap umum yang bukan milik Semester Pendek, tapi memblokirnya).

## Daftar Dokumen

| # | Dokumen | Isi |
|---|---|---|
| 1 | **README.md** (dokumen ini) | Ringkasan eksekutif, keputusan arsitektur, perbandingan sistem lama vs kebutuhan, siklus hidup ringkas |
| 2 | [aturan-bisnis.md](./aturan-bisnis.md) | Definisi, model periode, siklus status, kelayakan mahasiswa, penawaran kelas, mengulang mata kuliah, alur KRS, bentrok jadwal, absensi, edge case |
| 3 | [peran-dan-izin.md](./peran-dan-izin.md) | Matriks role × aksi, permission baru, aturan kepemilikan objek |
| 4 | [desain-database.md](./desain-database.md) | Tabel yang dipakai ulang, kolom/tabel baru, migrasi, konfigurasi per-tenant |
| 5 | [desain-api.md](./desain-api.md) | Endpoint sesuai konvensi `/api/v1` yang ada |
| 6 | [penilaian-dan-transkrip.md](./penilaian-dan-transkrip.md) | Ujian, LMS, penilaian, finalisasi, revisi nilai, IPS/IPK, transkrip |
| 7 | [pembayaran-dan-notifikasi.md](./pembayaran-dan-notifikasi.md) | Tagihan SP, status pembayaran, integrasi Finance, notifikasi |
| 8 | [frontend-dan-mobile.md](./frontend-dan-mobile.md) | Halaman web admin/mahasiswa/dosen, dashboard, laporan, cakupan Flutter |
| 9 | [keamanan-audit-integritas.md](./keamanan-audit-integritas.md) | Otorisasi, isolasi tenant, race condition kapasitas, audit log |
| 10 | [pengujian-dan-penerimaan.md](./pengujian-dan-penerimaan.md) | Strategi uji & kriteria penerimaan Given/When/Then |
| 11 | [rencana-implementasi.md](./rencana-implementasi.md) | Urutan fase, checklist, apa yang sengaja **tidak** dibangun |
| 12 | [tahapan-pengembangan.md](./tahapan-pengembangan.md) | Rekonsiliasi rancangan dengan kode per 2026-10-08, tahapan kerja per PR, gerbang rilis, keterlacakan, estimasi |
| 13 | [black-box-testing.md](./black-box-testing.md) | Checklist uji black box dari halaman frontend (QA/UAT): langkah per menu & tombol, status bisa-diuji-sekarang / halaman belum ada / menunggu tahap, temuan menu tanpa halaman |

---

## 1. Ringkasan Eksekutif

### 1.1 Apa itu Semester Pendek

**Semester Pendek (SP)** adalah periode akademik tambahan yang berlangsung di antara dua semester reguler — umumnya setelah Semester Genap dan sebelum Semester Ganjil tahun akademik berikutnya (± 6–8 minggu). Dalam waktu yang lebih singkat, kelas berjalan lebih intensif (beberapa pertemuan per minggu), dengan batas SKS jauh lebih kecil dari semester reguler.

Tujuan akademik yang umum (semuanya **dapat dikonfigurasi per universitas** — bukan aturan tetap):

- Mengulang mata kuliah dengan nilai rendah/tidak lulus (D/E, atau sampai batas yang ditetapkan kampus)
- Memperbaiki IPK
- Melengkapi SKS yang tertinggal
- Mempercepat masa studi dengan mengambil mata kuliah baru yang ditawarkan
- Memenuhi mata kuliah prasyarat sebelum semester reguler berikutnya

### 1.2 Mengapa CivitasOne membutuhkannya

CivitasOne adalah SaaS multi-tenant (`universities` + `TenantScoped`). Hampir semua perguruan tinggi di Indonesia menjalankan SP. Saat ini `AcademicSemester` **hanya** punya `ganjil` dan `genap` — tidak ada cara merepresentasikan SP sama sekali tanpa memalsukan data sebagai salah satu semester reguler (yang akan merusak IP per semester, batas SKS, dan transkrip).

### 1.3 Dari sisi bisnis vs sisi sistem

| Sudut pandang | Ringkasan |
|---|---|
| **Akademik/bisnis** | SP adalah semester "mini": Bagian Akademik membuka periode, memilih mata kuliah yang ditawarkan, menetapkan dosen & jadwal; mahasiswa yang memenuhi syarat mendaftar (KRS), membayar biaya per SKS, mengikuti perkuliahan, ujian, dan mendapat nilai. Nilai lama **tidak dihapus** — riwayat percobaan tetap ada, kebijakan kampus menentukan nilai mana yang dihitung ke IPK. |
| **Arsitektur sistem** | SP **bukan sistem baru**. SP adalah satu baris `academic_terms` dengan `semester = 'pendek'`. Seluruh rantai yang sudah ada — `class_sections` → `krs_items` → `grades`/`attendances`/`exam_attempts` — sudah terikat ke `academic_term_id`, sehingga otomatis berlaku. Yang baru hanyalah **aturan** yang bergantung pada jenis periode (batas SKS, kelayakan, biaya, siklus status) dan beberapa **gap umum** yang memang belum ada di modul akademik (jadwal, dosen pengampu, prasyarat, finalisasi nilai, kebijakan mengulang). |

### 1.4 Siapa yang memakai

| Role (slug di `OrganizationalRoleSeeder`) | Peran di SP |
|---|---|
| `academic_administrator` (Bagian Akademik) | Membuat & mengelola periode SP, membuka kelas, menetapkan dosen & jadwal, menyetujui KRS (bila dikonfigurasi), memfinalisasi nilai |
| `head_of_study_program` (Kaprodi) | Memantau, opsional menjadi approver KRS/perubahan nilai lewat ApprovalWorkflow |
| `academic_advisor` (Dosen PA) | Approver KRS **hanya setelah** relasi dosen-wali dibangun (⛔ belum ada) |
| `lecturer` (Dosen) | Mengajar kelas SP yang **ditugaskan kepadanya**: absensi, ujian, nilai — **tidak** membuat periode/kelas |
| `student` (Mahasiswa) | Mendaftar mata kuliah yang memenuhi syarat, membayar, kuliah, ujian, melihat nilai |
| `finance_administrator` (Bagian Keuangan) | Memverifikasi pembayaran tagihan SP |
| `super_admin` | Bypass semua permission (perilaku `PermissionRegistry` yang ada), tidak punya peran operasional khusus |

Detail lengkap: [peran-dan-izin.md](./peran-dan-izin.md).

---

## 2. Keputusan Arsitektur Utama

### 2.1 SP adalah *Academic Period* khusus, bukan sistem akademik terpisah

```
Tahun Akademik 2026/2027            (string academic_terms.academic_year — tidak ada tabel tahun akademik)
    │
    ├── academic_terms (semester = ganjil)
    ├── academic_terms (semester = genap)
    └── academic_terms (semester = pendek)   ← 🆕 satu nilai enum, bukan tabel baru
              │
              └── class_sections (academic_term_id = SP)        ✅ = "penawaran mata kuliah SP"
                        └── krs_items (academic_term_id = SP)   ✅ = pendaftaran SP
                              ├── grades                        ✅
                              ├── attendances                   ✅
                              └── exam_attempts                 ✅ (lewat exams.class_section_id)
```

**Ditolak:** `RegularKrs`/`ShortKrs`, `RegularGrade`/`ShortGrade`, `RegularExam`/`ShortExam`, `short_semesters`, `short_semester_enrollments`, `short_semester_course_offerings`, dst.

**Alasan (berdasarkan kode, bukan preferensi):**

1. **Rantai data sudah periode-agnostik.** `krs_items.academic_term_id` sudah didenormalisasi persis supaya KRS bisa difilter per periode (lihat komentar di migrasi `2026_07_20_030003_create_krs_items_table.php`). `Grade`, `Attendance`, `ExamAttempt` terikat ke `KrsItem`, bukan ke periode — jadi tidak ada satu pun yang perlu tahu "ini SP atau bukan".
2. **Perhitungan IP/IPK bergantung pada satu tabel nilai.** `AcademicRecordService::transcript()` mengelompokkan `Grade` per `academic_term_id`. Tabel `short_grades` terpisah berarti setiap perhitungan IPK, KHS, transkrip, laporan, dan dashboard harus `UNION` dua sumber — sumber bug yang pasti.
3. **Mengulang mata kuliah lintas periode** (D di Ganjil → B di SP) hanya bisa dibandingkan secara wajar kalau kedua percobaan ada di tabel yang sama dengan `course_id` yang sama.
4. **Isolasi tenant, policy, ListQuery, resource, frontend `KrsPage`/`GradesPage`/`AttendancePage`, modul Ujian** — semuanya langsung bekerja untuk SP tanpa duplikasi.
5. **Regresi minimal.** Semester reguler tetap memakai jalur kode yang sama; perilaku khusus SP diaktifkan oleh satu pemeriksaan `$term->semester === AcademicSemester::Pendek`.

### 2.2 Prinsip turunan

| Prinsip | Konsekuensi |
|---|---|
| Kelas SP = `class_sections` biasa di term SP | Tidak ada tabel `course_offerings`. "Membuka mata kuliah untuk SP" = membuat `class_section` di term SP. |
| Aturan yang beda per jenis periode hidup di service, bukan di tabel | `AcademicRecordService::maxSks()` bercabang untuk `pendek`; kelayakan diekstrak ke satu kelas `EnrollmentEligibility`. |
| Kebijakan kampus = konfigurasi tenant, bukan kode | Dibaca dari `university_settings` (tabel ✅ ada, unik per `university_id` + `key`). Lihat [desain-database.md §5](./desain-database.md#5-konfigurasi-per-tenant-university_settings). |
| Gap umum dibangun **umum** | Jadwal, dosen pengampu, prasyarat, finalisasi nilai, kebijakan mengulang dibangun untuk semua periode — SP hanya pemakai pertamanya. |
| Persetujuan memakai modul yang ada | `ApprovalWorkflow` (✅ ada, `requestable` morph + `WorkflowSelector`) — bukan tabel approval baru. |
| Audit memakai modul yang ada | Trait `Auditable` + `AuditLogService::log()` (✅ ada) — bukan tabel audit baru. |

---

## 3. Perbandingan Sistem Saat Ini vs Kebutuhan SP

Diisi dari kode aktual. Kolom terakhir: **Reuse** = pakai apa adanya · **Perluas** = ubah yang ada · **Baru (umum)** = belum ada, dibangun untuk semua periode · **Baru (SP)** = khusus SP.

| Area | Kondisi CivitasOne saat ini (file rujukan) | Kebutuhan SP | Reuse / Baru |
|---|---|---|---|
| **Periode akademik** | `academic_terms` (`academic_year` string, `semester` enum `ganjil\|genap`, `is_current`, `start_date`, `end_date`); unik `(university_id, academic_year, semester)`. **Tidak ada endpoint API sama sekali** — hanya seeder. `AcademicTerm::label()` memakai ternary Ganjil/Genap. | Nilai `pendek`; status siklus hidup; jendela pendaftaran; batas SKS per-periode; CRUD oleh admin | **Perluas** enum + 4 kolom; **Baru (umum)** endpoint `academic-terms`; perbaiki `label()` |
| **Tahun akademik** | String bebas `"2026/2027"` di `academic_terms` & `curriculums` — tidak ada tabel | Pengelompokan Ganjil/Genap/Pendek per tahun | **Reuse** (tidak perlu tabel `academic_years`) |
| **Mata kuliah** | `courses` (`code` unik per tenant, `credits`, `semester_level`, `curriculum_id`); read-only | Dipakai sebagai master; prasyarat | **Reuse**; prasyarat **Baru (umum)** `course_prerequisites` |
| **Prasyarat** | Tidak ada (blueprint §7 menyebut `course_prerequisites`) | Validasi saat KRS, override admin | **Baru (umum)**, opsional di fase awal |
| **Kelas / penawaran** | `class_sections` (`course_id`, `academic_term_id`, `class_code`, `capacity`, `is_active`); **read-only, tidak ada endpoint tulis** | Admin membuat kelas SP, kapasitas, minimum peserta, pembatalan | **Perluas** (+`min_participants`, `cancelled_at`, `cancellation_reason`) + endpoint tulis **Baru (umum)** |
| **Dosen pengampu** | **Tidak ada relasi dosen↔kelas.** `lecturers` tidak punya `user_id`. Celah terdokumentasi (RANCANGAN-AKUN-DOSEN §8.1). | Dosen hanya melihat/menilai kelasnya | ⛔ **Prasyarat, Baru (umum)**: `lecturers.user_id` + `class_section_lecturers` |
| **Jadwal & ruangan** | *(per 2026-10-08)* ✅ `class_schedules` (hari/jam/ruang string) + `ClassSchedule::overlaps()`; bentrok dosen/ruang di `PUT class-sections/{id}/teaching`, bentrok mahasiswa di KRS mandiri; tidak ada tabel ruangan | Jadwal intensif, deteksi bentrok mahasiswa/dosen/ruang | **Reuse** (R-02): tanpa tanggal efektif — bentrok dibandingkan per term dan tanggal term tidak beririsan |
| **KRS** | `krs_items` (`status` `enrolled\|dropped`, unik `(student_id, class_section_id)`); `AcademicRecordService::enroll()` mengunci baris kelas (`lockForUpdate`), cek aktif, duplikat, kapasitas, batas SKS. **Tidak** cek status mahasiswa, **tidak** cek mata kuliah sama di kelas lain, **tidak** ada endpoint mandiri mahasiswa. | Self-service mahasiswa, status menunggu persetujuan/pembayaran, batas SKS SP, kelayakan mengulang | **Perluas** enum (+`pending`, `rejected`), +`invoice_id`; kelayakan diekstrak; endpoint `student/krs-items` **Baru (umum)** |
| **Batas SKS** | `SKS_LIMIT_TIERS` hardcoded (18/20/22/24) dari IP term sebelumnya (`maxSks()`) | Batas tetap SP (mis. 9 SKS); **SP tidak boleh menggeser perhitungan kuota semester reguler berikutnya** | **Perluas** `maxSks()` (lihat [aturan-bisnis.md §4.5](./aturan-bisnis.md#45-batas-sks)) |
| **Persetujuan KRS** | Tidak ada. Modul `ApprovalWorkflow` ✅ generik (workflow → steps → request polimorfik, event `ApprovalRequestApproved/Rejected`) tapi belum dipakai modul bisnis mana pun | Otomatis atau lewat approver yang dikonfigurasi | **Reuse** `ApprovalWorkflow` (`workflowable_type = 'krs_item.short_term'`) |
| **Absensi** | `attendances` per `krs_item` per `meeting_number`; status `present\|permitted\|sick\|absent`; batch per pertemuan | Sama; minimum kehadiran | **Reuse**; perhitungan persentase **Baru (umum)** |
| **LMS (materi/tugas/kuis/diskusi)** | **Tidak ada backend sama sekali.** `PortalAssignmentsPage`/`PortalQuizzesPage` = data statis | — | **Tidak dibangun untuk SP.** Saat LMS dibuat, otomatis ikut SP lewat `class_section_id`. |
| **Ujian** | Modul matang: `exams` terikat `class_section_id`, bank soal, randomisasi, jadwal `starts_at/ends_at`, `weight_percentage`, `exam_grade_ranges`, portal mahasiswa & akses publik | Sama persis | **Reuse 100%** — nol perubahan wajib |
| **Nilai** | `grades` 1:1 `krs_item` (`score`, `letter_grade`, `submitted_at`); `PUT` menimpa langsung; **tanpa status/finalisasi/riwayat**; `LetterGrade` 7 tingkat hardcoded | Draft → submit → finalisasi; revisi lewat persetujuan; riwayat | **Perluas** (+`status`, `finalized_at`, `finalized_by`, `Auditable`); `grade_revision_requests` **Baru (umum)** |
| **IPS/IPK** | `transcript()` menjumlahkan **semua** nilai — mata kuliah yang diulang **dihitung dua kali** di IPK | Kebijakan nilai tertinggi/terakhir | **Perluas** `transcript()` (kebijakan dari setting) |
| **Transkrip** | `GET /students/{id}/transcript` → `{terms[], ipk, total_sks}` | Tampilkan percobaan, penanda ulang | **Perluas** respons (field tambahan, kompatibel mundur) |
| **Pembayaran** | `invoices` (`period` string, `amount`, `paid_amount`, status `unpaid\|partial\|paid`, `due_date`), `payments` (manual: `paid_at`, `method`); **read-only, tanpa endpoint tulis, tanpa payment gateway** | Tagihan per SKS, kedaluwarsa, konfirmasi pembayaran | **Perluas** (+`academic_term_id`, status `cancelled`) + endpoint catat pembayaran **Baru (umum)**; gateway **di luar cakupan** |
| **Notifikasi** | `NotificationDispatcher::dispatch(user, eventKey, channels, placeholders)` + `notification_templates` per tenant; kanal `database\|mail\|push\|whatsapp\|sms` | Event SP | **Reuse** — hanya tambah event key & template |
| **Audit** | `audit_logs` (old/new, `reason` dari input `reason`/header `X-Change-Reason`) lewat trait `Auditable`; `activity_logs` lewat `AuditLogService::log()` | Jejak status periode, override, finalisasi/revisi nilai | **Reuse** — pasang `Auditable` di model terkait |
| **Laporan** | `ReportService::build(type)` (mahasiswa/akademik/keuangan/sdm); ekspor CSV (`fputcsv`) & PDF (dompdf) sudah dipakai di rekap ujian | Laporan SP | **Perluas** filter `academic_term_id` + tipe laporan baru |
| **Dashboard** | `DashboardStatsService` sudah menerima filter `academic_term_id` (default `is_current`) | Dashboard SP | **Reuse** filter; endpoint ringkasan SP **Baru (SP)** |
| **Multi-tenant** | `TenantScoped` di semua model akademik; validasi FK via `findOrFail` ter-scope (bukan `Rule::exists`) | Sama | **Reuse** konvensi |
| **Portal mahasiswa (web)** | 22 halaman `frontend/src/pages/portal/*` — **semua data statis**, kecuali Ujian | KRS/jadwal/nilai/tagihan SP | Bergantung pada penyambungan portal (⛔ prasyarat produk) |
| **Mobile (Flutter)** | Hanya `features/auth`, `dashboard`, `exam` (konfigurasi ujian sisi dosen/admin) | Opsional untuk mahasiswa | Lihat [frontend-dan-mobile.md §5](./frontend-dan-mobile.md#5-mobile-flutter) |

### 3.1 Temuan penting dari analisis kode

Empat hal ini **harus** ditangani sebelum SP masuk produksi, karena tanpa itu SP akan merusak data semester reguler:

1. **`maxSks()` akan salah setelah SP pertama ada.** Fungsi ini mengambil term dengan `start_date` terbaru sebelum term berjalan — **secara global, bukan per mahasiswa**. Setelah SP 2026/2027 dibuat, untuk Ganjil 2027/2028 term "sebelumnya" adalah SP. Mahasiswa yang **tidak** ikut SP tidak punya nilai di sana → `termGpa()` mengembalikan `null` → **semua mahasiswa itu mendapat batas default 24 SKS**, tidak peduli IP Genap mereka 1,5. Ini regresi untuk mayoritas mahasiswa.
2. **IPK menghitung mata kuliah ulang dua kali.** `transcript()` menjumlahkan setiap `Grade`. Database D (Ganjil) + Database B (SP) = 6 SKS di IPK, padahal seharusnya 3. Bug ini sudah ada untuk pengulangan di semester reguler, tapi SP membuatnya menjadi kasus utama.
3. **`AcademicTerm::label()` akan memberi label "Genap" pada SP** karena memakai `=== Ganjil ? 'Ganjil' : 'Genap'`. Label ini dipakai di transkrip dan resource.
4. **Dosen bisa menilai/mengabsen kelas mana pun** (permission-only, `GradePolicy::manage()`). Untuk SP — yang biasanya dibayar terpisah dan sering diampu dosen berbeda — ini tidak dapat diterima.

---

## 4. Siklus Hidup Ringkas

```
DRAFT ──► PLANNED ──► REGISTRATION_OPEN ──► REGISTRATION_CLOSED ──► ONGOING ──► GRADING ──► COMPLETED ──► ARCHIVED
  ▲          │               │   ▲                    │
  └──────────┘               └───┘ (buka ulang,       └──► (kembali ke REGISTRATION_OPEN untuk
 (kembali ke draft)        perpanjangan jendela)            perpanjangan, sebelum ONGOING)
```

Semua transisi oleh `academic_administrator` (permission `academic_terms.update`), tercatat di audit log. Detail per status, aturan transisi, dan konsekuensi: [aturan-bisnis.md §3](./aturan-bisnis.md#3-siklus-hidup-periode-sp).

---

## 5. Alur Utama (Ringkas)

```
Bagian Akademik                    Mahasiswa                         Dosen                 Keuangan
───────────────                    ─────────                         ─────                 ────────
Buat term SP (DRAFT)
Buka kelas + dosen + jadwal
PLANNED → REGISTRATION_OPEN ───►   Lihat kelas yang layak
                                   Pilih kelas → sistem validasi
                                   Submit KRS (status: pending)
[Approve — bila workflow aktif] ◄─
                                   Tagihan dibuat (unpaid) ───────────────────────────────► Catat pembayaran
                                   KRS → enrolled  ◄──────────────────────────────────────── (invoice paid)
REGISTRATION_CLOSED → ONGOING                                        Absensi, ujian
ONGOING → GRADING                                                    Input & submit nilai
Finalisasi nilai
GRADING → COMPLETED                Nilai & transkrip tampil
```

Alur lengkap: [aturan-bisnis.md §7](./aturan-bisnis.md#7-alur-krs-sp).

---

## 6. Data yang Harus Tetap Dipakai Bersama Semester Reguler

Tidak boleh ada salinan khusus SP untuk: `students`, `lecturers`, `courses`, `curriculums`, `study_programs`, `class_sections`, `krs_items`, `grades`, `attendances`, `exams` (+ seluruh turunannya), `question_bank_items`, `invoices`, `payments`, `audit_logs`, `activity_logs`, `notification_*`, `approval_*`, `university_settings`.

---

## 7. Prasyarat (Gap Umum yang Memblokir SP)

Ini **bukan** fitur SP, tapi SP tidak aman tanpanya. Semuanya sudah tercatat sebagai gap di dokumen RANCANGAN-AKUN-* yang ada:

| Prasyarat | Mengapa memblokir SP | Rujukan |
|---|---|---|
| Identity link `lecturers.user_id` | Tanpa ini, "kelas milik dosen yang login" tidak bisa ditentukan | AKADEMIK §8.3, DOSEN §8.2 |
| Relasi dosen↔kelas `class_section_lecturers` | Kepemilikan nilai/absensi, notifikasi dosen, bentrok jadwal dosen | AKADEMIK §8.1, DOSEN §8.1 |
| Endpoint tulis `academic-terms` & `class-sections` | Saat ini periode & kelas hanya bisa dibuat via seeder | — |
| Endpoint mandiri KRS mahasiswa | `POST /krs-items` hanya untuk admin (menerima `student_id` bebas) | MAHASISWA §4.3 |
| Perbaikan `maxSks()` & `transcript()` | Lihat §3.1 butir 1–2 | — |

Urutan pengerjaan: [rencana-implementasi.md](./rencana-implementasi.md).
