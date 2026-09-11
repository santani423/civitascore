# Rancangan Akun & Modul Akademik

> **Status:** Dokumen desain, disusun dari kondisi kode aktual (backend `app/Modules/Academic`, frontend `frontend/src`, mobile `mobile/lib/features/exam`) per sesi ini — bukan cuma ringkasan blueprint lama.
> **Dibuat:** 2026-09-11
> Pelengkap [RANCANGAN-APLIKASI.md](./RANCANGAN-APLIKASI.md) §3.4-§3.9 dan §4.1-§4.20 (roles dan modul akademik di blueprint besar), [BLACK-BOX-TESTING.md](./BLACK-BOX-TESTING.md) §10-14 (skenario uji), [EXAM_MODULE.md](./EXAM_MODULE.md) & [EXAM_RANDOMIZATION.md](./EXAM_RANDOMIZATION.md) (submodul Ujian), dan [FRONTEND-IMPLEMENTASI.md](./FRONTEND-IMPLEMENTASI.md) (status pengerjaan frontend). Dokumen-dokumen itu menjelaskan potongan-potongan terpisah (arsitektur, skenario uji, submodul); dokumen ini menyatukannya jadi satu gambaran lengkap **akun akademik** — role `academic_administrator` ("Bagian Akademik") dan seluruh role lain yang beririsan dengannya di atas Modul Akademik — mulai dari struktur data sampai status implementasi baris per baris. Nama tabel/model/endpoint di sini merujuk persis ke kode yang ada; kalau baru rancangan (belum diimplementasikan), ditandai eksplisit.

## Daftar Isi

1. [Apa itu "Akun Akademik"](#1-apa-itu-akun-akademik)
2. [Peta Role di Sekitar Modul Akademik](#2-peta-role-di-sekitar-modul-akademik)
3. [Struktur Data](#3-struktur-data)
4. [Modul — Status Implementasi & Fitur Detail](#4-modul--status-implementasi--fitur-detail)
   - [4.1 Data Induk Akademik](#41-data-induk-akademik--read-only)
   - [4.2 KRS (Kartu Rencana Studi)](#42-krs-kartu-rencana-studi--crud-penuh)
   - [4.3 Penilaian (Nilai)](#43-penilaian-nilai--crud-penuh)
   - [4.4 Absensi](#44-absensi--crud-penuh-batch-per-pertemuan)
   - [4.5 Transkrip, IP & IPK](#45-transkrip-ip--ipk--murni-perhitungan)
   - [4.6 Ujian & Bank Soal](#46-ujian--bank-soal)
   - [4.7 Portal Mahasiswa — Sisi Akademik](#47-portal-mahasiswa--sisi-akademik)
   - [4.8 Bimbingan Akademik (Dosen PA)](#48-bimbingan-akademik-dosen-pa--belum-dibangun)
   - [4.9 Modul Blueprint Lain yang Belum Dibangun](#49-modul-blueprint-lain-yang-belum-dibangun)
5. [Endpoint API — Referensi Lengkap](#5-endpoint-api--referensi-lengkap)
6. [Aturan Bisnis Kunci](#6-aturan-bisnis-kunci)
7. [Alur Kerja Utama](#7-alur-kerja-utama)
8. [Celah & Risiko yang Sudah Didokumentasikan Sengaja](#8-celah--risiko-yang-sudah-didokumentasikan-sengaja)
9. [Status Frontend](#9-status-frontend)
10. [Ringkasan Gap Implementasi](#10-ringkasan-gap-implementasi)
11. [Rekomendasi Prioritas Lanjutan](#11-rekomendasi-prioritas-lanjutan)

---

## 1. Apa itu "Akun Akademik"

"Akun akademik" di CivitasOne bukan satu akun tunggal — istilah ini merujuk ke dua hal yang saling terkait:

1. **Role `academic_administrator`** (label: **"Bagian Akademik"**) — akun operasional yang menjalankan administrasi akademik sehari-hari: mendaftarkan KRS mahasiswa, mengelola pengumuman, memantau kelas/kurikulum/mata kuliah. Ini adalah "akun akademik" dalam arti sempit — satu baris di `OrganizationalRoleSeeder`.
2. **Modul Akademik** (`app/Modules/Academic`) — domain bisnis yang jauh lebih besar dari satu role: data induk mahasiswa/dosen/pegawai, kurikulum & mata kuliah, kelas & jadwal, KRS, penilaian, absensi, transkrip/IP/IPK, dan ujian. Modul ini **dipakai lintas banyak role sekaligus** — `academic_administrator` cuma satu dari sepuluh role yang punya akses ke sebagian isinya.

Dokumen ini membahas keduanya: siapa saja pemegang "akun akademik" (§2), dan apa saja yang bisa mereka lakukan lewat Modul Akademik (§4 dst).

Modul Akademik hidup sepenuhnya di `app/Modules/Academic/` (Laravel) — 14 model Eloquent, 14 controller, 5 request class, 12 policy, 3 service, 130+ test case Pest — dan punya cermin di `frontend/src/pages/academic/` (React, admin) serta `mobile/lib/features/exam/` (Flutter, khusus konfigurasi ujian dosen/admin).

---

## 2. Peta Role di Sekitar Modul Akademik

Sepuluh dari lima belas role sistem (`OrganizationalRoleSeeder::ROLE_LABELS`) menyentuh Modul Akademik dengan derajat akses berbeda-beda. Tabel di bawah dari yang paling luas ke paling sempit:

| Role (slug) | Label | Sifat akses ke Modul Akademik |
|---|---|---|
| `university_owner` | University Owner | Baca semua resource akademik (`students`…`question_bank`) + kontrol penuh atas RBAC/pengaturan tenant. Tidak menulis data akademik langsung — itu tugas role operasional di bawahnya. |
| `university_administrator` | University Administrator | Sama seperti `university_owner` untuk cakupan baca akademik, tanpa hak ubah `system_settings`. |
| `rector` | Rektor | Baca semua resource akademik (untuk laporan/monitoring lintas fakultas), tanpa hak tulis. |
| `vice_rector` | Wakil Rektor | Sama seperti Rektor, cakupan baca sedikit lebih sempit (tanpa `employees.read`/`invoices.read`). |
| `dean` | Dekan | Baca akademik (untuk fakultasnya secara konseptual — **catatan:** belum ada scoping per-fakultas di level query, akses baca berlaku ke seluruh data tenant). |
| `head_of_study_program` | Ketua Program Studi | Sama seperti Dekan — baca penuh, belum ada scoping per-prodi otomatis. |
| **`academic_administrator`** | **Bagian Akademik** | **Akun akademik inti.** Baca semua resource akademik **+ tulis KRS** (`krs.create`, `krs.update`) — satu-satunya role admin non-dosen yang bisa mendaftarkan/membatalkan KRS mahasiswa atas nama mereka. Juga kelola `notification_templates` (pengumuman lewat kanal notifikasi). |
| `lecturer` | Dosen | Baca data induk terbatas (mahasiswa, prodi, kelas, mata kuliah — **tidak** termasuk `students` di luar itu) + tulis penuh **Nilai** dan **Absensi** untuk kelas + kelola **Ujian** & **Bank Soal** penuh (`exams.*`, `question_bank.*`, `exam_attempts.*`). |
| `student` | Mahasiswa | **Tidak** punya `krs.create`/`grades.read` dsb. secara langsung — hanya `exam_participation.*` (mengerjakan ujian miliknya sendiri, lewat `StudentExamController`, digerbangi kepemilikan `KrsItem` di level objek oleh `ExamParticipationPolicy`, bukan cuma permission slug). |
| `academic_advisor` | Dosen Pembimbing Akademik | **Sengaja tanpa permission admin** — fitur bimbingan (§4.8) di blueprint (RANCANGAN-APLIKASI.md §3.7/§4.19) butuh identity link Dosen→User dulu sebelum bisa dapat akses aman ke mahasiswa bimbingannya sendiri; belum dibangun. |
| `auditor` | Auditor | Baca semua resource akademik, murni untuk keperluan audit — sama luasnya dengan Rektor tapi tanpa dashboard operasional. |

Role lain (`finance_administrator`, `hr_administrator`, `library_administrator`, `employee`) tidak menyentuh Modul Akademik sama sekali.

**Yang paling relevan disebut "akun akademik"** dalam praktik sehari-hari adalah kombinasi **`academic_administrator`** (operator data & KRS) dan **`lecturer`** (operator nilai/absensi/ujian) — keduanya satu-satunya role dengan hak *tulis* nyata di modul ini saat ini.

---

## 3. Struktur Data

Skema aktual (ULID sebagai primary key di semua tabel, `university_id` wajib di setiap tabel — lihat `TenantScoped`/`BelongsToInstitutionScope`, [MULTI-TENANT-ARCHITECTURE.md](./MULTI-TENANT-ARCHITECTURE.md) §5):

```
universities (Tenancy)
 └─ faculties                  code, name, is_active
     └─ study_programs         code, name, degree_level, is_active
         ├─ students           nim, name, email, admission_year, status, enrolled_at,
         │                     graduated_at, user_id (nullable → identity link)
         ├─ lecturers          nidn, name, email, is_active, faculty_id (nullable)
         ├─ class_sections     course_id, academic_term_id, class_code, capacity, is_active
         └─ curriculums        name, academic_year, is_active
             └─ courses        code, name, credits, semester_level, is_active

 employees                      unit_kerja, name, email, position, is_active
 academic_terms                 academic_year, semester (ganjil|genap), is_current, start_date, end_date

 krs_items                      student_id, class_section_id, academic_term_id (diturunkan
                                 dari class_section), status (enrolled|dropped)
                                 unique(student_id, class_section_id) — drop+daftar-ulang
                                 memakai baris yang sama, bukan baris baru
   ├─ grades                    (1:1 dari krs_item) score, letter_grade, submitted_at
   └─ attendances                (1:N per pertemuan) meeting_number, meeting_date, status, notes
                                 unique(krs_item_id, meeting_number)

 exams                          class_section_id, title, questions_per_participant,
                                 question_selection_mode (all|random|manual),
                                 randomize_questions, randomize_options, max_attempts,
                                 published_at, schedule, creator
   ├─ exam_questions            text, points, order_index, is_selected
   │   └─ exam_question_options text, is_correct (ULID stabil — dipakai sbg identitas jawaban)
   ├─ exam_attempts              krs_item_id, question_order[], option_order{}, status, score
   │   └─ exam_attempt_answers  jawaban peserta per soal
   └─ question_bank_items        pool soal reusable lintas ujian
       └─ question_bank_item_options
```

**Relasi kunci:**
- `Student.user_id` → `users.id` (nullable, unique) — identity link yang **sudah ada** (migrasi `2026_08_26_173744_add_user_id_to_students_table.php`, dibackfill otomatis oleh `StudentUserAccountSeeder` setiap `db:seed`). **Belum ada** identity link setara untuk `Lecturer`/`Employee` → `User`.
- `Grade` dan `Attendance` terikat ke `KrsItem`, **bukan** langsung ke `Student`/`User` — pola ini dipertahankan konsisten (termasuk `ExamAttempt`) karena baris KRS sudah menyatukan identitas mahasiswa + kelas + periode dalam satu referensi.
- `ClassSection.course_id` menggantikan kolom `course_name` string lama (migrasi `..._030002_replace_course_name_with_course_id...`) — kelas sekarang benar-benar terhubung ke Mata Kuliah, bukan teks bebas.

---

## 4. Modul — Status Implementasi & Fitur Detail

Legenda status: **✅ CRUD penuh** · **📖 Read-only** (index/show saja, endpoint tulis memang tidak ada — 404/405, bukan 403) · **🧩 Terbangun tapi belum tersambung** · **❌ Belum dibangun sama sekali** (baru ada di blueprint).

### 4.1 Data Induk Akademik — 📖 Read-only

Tujuh resource, pola identik: `index` (list, filter, search, pagination) + `show` (detail). **Tidak ada** create/update/delete di backend — data induk hanya bisa dilihat, belum bisa diinput lewat sistem ini (harus lewat seeder/database langsung untuk saat ini).

| Resource | Endpoint | Permission | Filter yang didukung |
|---|---|---|---|
| Mahasiswa | `GET /students`, `GET /students/{id}` | `students.read` | `search` (nama/NIM), `filter[status]`, `filter[admission_year]`, `filter[study_program_id]` |
| Dosen | `GET /lecturers`, `GET /lecturers/{id}` | `lecturers.read` | `filter[is_active]`, `filter[faculty_id]` |
| Pegawai | `GET /employees`, `GET /employees/{id}` | `employees.read` | `filter[is_active]`, `filter[unit_kerja]` |
| Program Studi | `GET /study-programs`, `GET /study-programs/{id}` | `study_programs.read` | — (menyertakan `students_count`/`class_sections_count` teragregasi) |
| Kelas & Jadwal | `GET /class-sections`, `GET /class-sections/{id}` | `classes.read` | `filter[academic_term_id]`, `filter[study_program_id]`, `filter[is_active]` (menyertakan `enrolled_count`, dan `course_name`/`course_code`/`credits` dari relasi) |
| Kurikulum | `GET /curriculums`, `GET /curriculums/{id}` | `curriculums.read` | — (`courses_count`; detail menyertakan `data.courses` penuh) |
| Mata Kuliah | `GET /courses`, `GET /courses/{id}` | `courses.read` | `filter[curriculum_id]`, `filter[semester_level]` |

Setiap permission benar-benar terpisah — punya `students.read` tidak otomatis membuka `lecturers.read`, dst.

### 4.2 KRS (Kartu Rencana Studi) — ✅ CRUD penuh

Selesai dibangun 2026-08-09, sebagai modul akademik pertama dengan operasi tulis nyata.

| Aksi | Endpoint | Permission | Aturan |
|---|---|---|---|
| Daftar KRS | `GET /krs-items` | `krs.read` | Filter: `student_id`, `class_section_id`, `academic_term_id`, `status` |
| Daftarkan mahasiswa ke kelas | `POST /krs-items` | `krs.create` | Lihat §6.1 untuk aturan lengkap |
| Batalkan (drop) | `PATCH /krs-items/{id}/drop` | `krs.update` | Ditolak (409) kalau sudah ada nilai atau sudah `dropped` sebelumnya |

**Perilaku desain penting:**
- `academic_term_id` **diturunkan otomatis** dari `class_section` yang dipilih — klien tidak pernah mengirimkannya.
- Enrollment unik per (`student_id`, `class_section_id`) **tanpa memandang status** — mahasiswa yang drop lalu daftar ulang ke kelas yang sama memakai baris KRS yang sama (status kembali ke `enrolled`), bukan baris baru.
- Validasi berurutan saat `POST`: kelas aktif → belum pernah `enrolled` di kelas ini → kelas belum penuh (`enrolled_count < capacity`) → tidak melebihi batas SKS (§6.1).
- Akses lintas-tenant (`student_id`/`class_section_id` milik universitas lain) menghasilkan 404, bukan 500 — `findOrFail` sudah ter-scope tenant otomatis lewat `TenantScoped`.

### 4.3 Penilaian (Nilai) — ✅ CRUD penuh

| Aksi | Endpoint | Permission |
|---|---|---|
| Daftar nilai | `GET /grades` | `grades.read` (filter: `student_id`, `letter_grade`) |
| Input/ubah nilai | `PUT /krs-items/{id}/grade` | `grades.create`, `grades.update` |

**Aturan:**
- `score` wajib, rentang 0–100.
- `letter_grade` **opsional** — kalau tidak dikirim, dihitung otomatis dari `score` (§6.2). Kalau dikirim manual, override konversi otomatis (skor tetap tersimpan apa adanya, huruf ikut input manual).
- Satu `KrsItem` hanya punya satu `Grade` (`updateOrCreate` by `krs_item_id`) — input ulang mengubah baris yang sama, tidak membuat duplikat.
- KRS berstatus `dropped` tidak bisa dinilai (409).
- **Belum ada validasi kepemilikan kelas** — dosen bisa menilai KRS kelas mana pun di tenant-nya, bukan hanya kelas yang benar-benar diampu. Lihat §8.

### 4.4 Absensi — ✅ CRUD penuh (batch per pertemuan)

| Aksi | Endpoint | Permission |
|---|---|---|
| Daftar absensi | `GET /attendances` | `attendance.read` (filter: `class_section_id`, `date_from`, `date_to`, `status`) |
| Rekam kehadiran satu pertemuan (batch) | `POST /class-sections/{id}/attendances` | `attendance.create`, `attendance.update` |

**Payload:** `{meeting_number, meeting_date, entries: [{krs_item_id, status, notes?}]}`.

**Aturan:**
- `status` harus salah satu dari `present`, `permitted`, `sick`, `absent`.
- Transaksional (all-or-nothing) — kalau satu entri menyebut `krs_item_id` yang bukan peserta `enrolled` aktif di kelas itu, seluruh batch ditolak (409), tidak ada satu baris pun tersimpan.
- Rekam ulang pertemuan yang sama (`meeting_number` sama) meng-**update** baris yang ada per (`krs_item_id`, `meeting_number`), bukan menduplikasi.
- **Belum ada validasi kepemilikan kelas** — sama seperti Nilai, dosen belum dibatasi hanya kelas yang benar-benar diampunya. Lihat §8.

### 4.5 Transkrip, IP & IPK — ✅ (murni perhitungan, tidak ada endpoint tulis)

| Endpoint | Permission |
|---|---|
| `GET /students/{id}/transcript` | `students.read` |

Response: `{terms: [{academic_term_id, label, sks, ip}], ipk, total_sks}`.

**Formula** (`AcademicRecordService::transcript()`):
- IP per semester = (Σ SKS mata kuliah × bobot huruf) ÷ (Σ SKS mata kuliah) pada semester itu, dibulatkan 2 desimal.
- IPK = total poin seluruh semester ÷ total SKS seluruh semester.
- Hanya `KrsItem` berstatus `enrolled` dengan `letter_grade` terisi yang dihitung — baris `dropped` **tidak pernah** ikut, bahkan kalau (secara data lama) baris itu entah bagaimana tetap punya nilai.
- Mahasiswa tanpa nilai sama sekali: `terms: []`, `ipk: 0`, `total_sks: 0` (bukan error).

Fungsi `maxSks()` di service yang sama juga dipakai KRS (§6.1) — mengambil IP semester **sebelumnya** (`academic_term` dengan `start_date` terbaru sebelum term saat ini) untuk menentukan batas SKS semester berjalan.

### 4.6 Ujian & Bank Soal

Submodul paling matang secara desain, konfigurasi (dosen/admin) selesai di backend + mobile Flutter; pengerjaan ujian sungguhan oleh mahasiswa **sebagian** sudah tersambung (endpoint ada, layar belum).

**Model:** `exams` (konfigurasi satu ujian, terikat `class_sections`) → `exam_questions` (pool soal) → `exam_question_options` (opsi jawaban, ULID stabil) ; `exam_attempts` (satu percobaan peserta) → `exam_attempt_answers`. `question_bank_items`/`question_bank_item_options` adalah pool soal **reusable** lintas ujian (`POST /exams/{exam}/questions/apply-bank` menerapkannya ke satu ujian).

**Tiga parameter independen mengontrol perilaku ujian** (detail lengkap: [EXAM_RANDOMIZATION.md](./EXAM_RANDOMIZATION.md)):

| Parameter | Pilihan | Efek |
|---|---|---|
| `question_selection_mode` | `all` / `random` / `manual` | Seluruh pool dikirim / dipilih acak sejumlah `questions_per_participant` / dosen menandai set tetap (`is_selected`) |
| `randomize_questions` | boolean | Urutan tampil soal diacak per percobaan atau tidak |
| `randomize_options` | boolean | Urutan tampil opsi A/B/C/D diacak per percobaan atau tidak |

**Stabilitas percobaan:** begitu `startAttempt()` dipanggil pertama kali untuk satu (ujian, peserta), backend menyimpan snapshot `question_order`/`option_order` — refresh, keluar-masuk, atau kehilangan koneksi tidak pernah mengubah penugasan soal yang sudah diberikan.

**Keamanan:** backend satu-satunya sumber kebenaran untuk jawaban benar, skor, dan urutan tampil. `ExamQuestionResource` (admin, menyertakan `is_correct`) sengaja terpisah dari `ExamAttemptResource`/`StudentExamAttemptResource` (peserta, **tidak pernah** menyertakan `is_correct`).

**Endpoint:** lihat tabel lengkap di §5.

**Permission:** role `lecturer` dapat `exams.*`/`question_bank.*`/`exam_attempts.*` penuh; seluruh role baca-akademik (`university_owner`, `university_administrator`, `rector`, `vice_rector`, `dean`, `head_of_study_program`, `academic_administrator`, `auditor`) dapat `exams.read`; role `student` dapat `exam_participation.*` (self-service, dibatasi kepemilikan `KrsItem` di level objek — §2).

**Yang belum dibangun:** layar pengerjaan ujian sungguhan untuk mahasiswa (timer berjalan, navigasi antar-soal, submit) di Flutter — endpoint backend (`StudentExamController`: start/answer/submit) sudah tersedia dan teruji, implementasinya tinggal mengonsumsi API yang ada.

### 4.7 Portal Mahasiswa — Sisi Akademik

`frontend/src/pages/portal/` (React, 18 halaman, dibangun 2026-07-25) menyediakan menu "Akademik" untuk mahasiswa: KRS, Jadwal, KHS, Transkrip, Nilai, Absensi (plus Tugas/Kuis/Cuti/Surat/dll di luar cakupan Akademik — lihat §4.9).

**Status: 🧩 terbangun tapi belum tersambung.** Seluruh halaman portal memakai **data statis di komponen** — nol pemanggilan API. Ini bukan bug fungsional untuk dilaporkan, tapi kondisi arsitektur yang perlu diperbaiki secara sadar:
- Halaman render tanpa error, tapi data yang tampil **sama di setiap login** — bukan data akun yang sedang login.
- Submit form (mis. isi KRS lewat `/portal/krs`) **tidak mengirim network request** ke `/api/v1/*` — bukti submit tidak benar-benar tersimpan.
- Mahasiswa yang sungguhan sudah punya KRS asli lewat panel admin (§4.2) **tidak** melihatnya muncul di `/portal/krs` — dua jalur data belum disatukan.

Yang **secara teknis sudah bisa** disambungkan sekarang (endpoint sudah ada, identity link `Student.user_id` sudah ada — §3): KRS, Jadwal, KHS, Transkrip, Nilai, Absensi di portal. Yang masih butuh modul backend baru sebelum bisa disambungkan: Tugas, Kuis, Cuti, Surat, Bimbingan Akademik/Skripsi, Evaluasi Dosen, Wisuda (§4.9).

### 4.8 Bimbingan Akademik (Dosen PA) — ❌ Belum dibangun

Sesuai blueprint (`RANCANGAN-APLIKASI.md` §3.7, §4.19): dosen pembimbing akademik (role `academic_advisor`) seharusnya bisa melihat mahasiswa bimbingan, memeriksa & menyetujui/menolak KRS, memberi catatan akademik, memantau IP/IPK, menjadwalkan konsultasi.

**Tidak ada satu pun**: tabel relasi mahasiswa↔dosen-wali, endpoint approval KRS oleh dosen PA, model catatan bimbingan. Role `academic_advisor` sengaja diseed tanpa permission apa pun (§2) — menunggu identity link Dosen→User (setara `Student.user_id`) sebagai prasyarat, supaya "mahasiswa bimbingan saya" bisa di-scope dengan aman ke dosen yang login, bukan cuma dibatasi lewat UI.

### 4.9 Modul Blueprint Lain yang Belum Dibangun

Bagian dari cakupan "akademik" di blueprint besar (`RANCANGAN-APLIKASI.md`) yang **belum ada satu pun endpoint/tabel** di `app/Modules/`:

| Modul (§ blueprint) | Cakupan yang direncanakan |
|---|---|
| §4.6 Kurikulum (authoring) | Backend hanya `curriculums`/`courses` read-only (§4.1) — fitur pembuatan/versi kurikulum, prasyarat mata kuliah, RPS, konversi kurikulum lama→baru belum ada UI/endpoint tulis. |
| §4.9 Jadwal Perkuliahan | Penyusunan jadwal drag-and-drop, deteksi bentrok dosen/mahasiswa/ruangan, jadwal pengganti — belum ada. `class_sections` saat ini tidak punya kolom hari/jam/ruangan sama sekali. |
| §4.11 Perkuliahan (pertemuan) | Jurnal mengajar, materi, tautan online, diskusi kelas — belum ada. |
| §4.12 Tugas Mahasiswa | Tugas individu/kelompok, pengumpulan, deteksi keterlambatan — belum ada modul `Assignment`. |
| §4.14 (metode lanjutan) | Absensi saat ini hanya manual oleh dosen (§4.4) — QR Code, PIN, GPS/geofencing, pengenalan wajah di blueprint belum diimplementasikan. |
| Cuti mahasiswa, Surat/Dokumen, Wisuda, Evaluasi Dosen, Kuis | Disebut di Portal Mahasiswa (§4.7) tapi **tidak ada satu pun** modul backend — bukan kasus "tinggal sambungkan API", tapi "bangun dari nol". |

---

## 5. Endpoint API — Referensi Lengkap

Semua di bawah `auth:sanctum` + resolusi tenant (`X-University-ID`), didefinisikan di `app/Modules/Academic/Routes/api.php`:

```
GET    students                                    students.read
GET    students/{student}                          students.read
GET    students/{student}/transcript                students.read

GET    lecturers                                    lecturers.read
GET    lecturers/{lecturer}                          lecturers.read

GET    employees                                    employees.read
GET    employees/{employee}                          employees.read

GET    study-programs                                study_programs.read
GET    study-programs/{studyProgram}                 study_programs.read

GET    class-sections                                classes.read
GET    class-sections/{classSection}                 classes.read

GET    curriculums                                   curriculums.read
GET    curriculums/{curriculum}                       curriculums.read

GET    courses                                       courses.read
GET    courses/{course}                                courses.read

GET    krs-items                                     krs.read
POST   krs-items                                     krs.create
PATCH  krs-items/{krsItem}/drop                        krs.update

GET    grades                                        grades.read
PUT    krs-items/{krsItem}/grade                       grades.create, grades.update

GET    attendances                                   attendance.read
POST   class-sections/{classSection}/attendances       attendance.create, attendance.update

GET    exams                                         exams.read
POST   exams                                         exams.create
GET    exams/{exam}                                   exams.read
PUT    exams/{exam}                                    exams.update
DELETE exams/{exam}                                    exams.delete
PATCH  exams/{exam}/publish                            exams.publish
GET    exams/{exam}/questions                          exams.read
POST   exams/{exam}/questions                          exams.create, exams.update
PUT    exam-questions/{examQuestion}                    exams.update
DELETE exam-questions/{examQuestion}                    exams.update, exams.delete
POST   exams/{exam}/questions/apply-bank                exams.create, exams.update

GET    question-bank                                 question_bank.read
POST   question-bank                                 question_bank.create
GET    question-bank/{questionBankItem}                question_bank.read
PUT    question-bank/{questionBankItem}                question_bank.update
DELETE question-bank/{questionBankItem}                question_bank.delete

POST   krs-items/{krsItem}/exams/{exam}/attempt         exam_attempts.create
PUT    exam-attempts/{examAttempt}/answer                exam_attempts.update
PATCH  exam-attempts/{examAttempt}/submit                exam_attempts.update

# Portal Mahasiswa — self-service, terpisah dari exam-attempts di atas
GET    student/exams                                  exam_participation.read
GET    student/exams/{exam}                            exam_participation.read
POST   student/exams/{exam}/start                       exam_participation.create
GET    student/exam-attempts/{examAttempt}               exam_participation.read
PUT    student/exam-attempts/{examAttempt}/answer          exam_participation.update
PATCH  student/exam-attempts/{examAttempt}/submit           exam_participation.update
```

Semua endpoint `index` memakai helper `ListQuery` yang sama (pagination default `per_page=20`, `search`, `sort`, `filter[...]` ter-whitelist per resource) — field yang tidak di-whitelist diabaikan, bukan diteruskan mentah ke SQL.

---

## 6. Aturan Bisnis Kunci

### 6.1 Batas Maksimum SKS per Semester (KRS)

Ditentukan dari IP semester **sebelumnya** (`AcademicRecordService::maxSks()`):

| IP semester sebelumnya | Maksimum SKS |
|---|---:|
| < 2,00 | 18 |
| 2,00 – 2,49 | 20 |
| 2,50 – 2,99 | 22 |
| ≥ 3,00 | 24 |
| Belum punya riwayat nilai (semester pertama) | 24 (default) |

Dicek setiap kali `POST /krs-items` — total SKS kelas yang sudah `enrolled` di semester berjalan + SKS kelas baru tidak boleh melebihi batas ini, kalau dilanggar → 409 dengan pesan menyebutkan batas dan SKS yang sudah diambil.

**Catatan:** tabel ini **hardcoded** di `AcademicRecordService::SKS_LIMIT_TIERS`, belum jadi `system_settings` yang bisa dikonfigurasi admin — beda dari niat blueprint §4.10 ("Ketentuan ini harus dapat dikonfigurasi melalui halaman admin").

### 6.2 Konversi Skor ke Nilai Huruf

Implementasi aktual (`LetterGrade::fromScore()`) — **7 tingkat**, beda dari tabel 9-tingkat di blueprint §4.16 (yang punya A-/B+/B-/C+ dan skala berbeda):

| Skor | Huruf | Bobot (IP) |
|---:|:---:|---:|
| ≥ 85 | A | 4,00 |
| 75–84 | AB | 3,50 |
| 65–74 | B | 3,00 |
| 55–64 | BC | 2,50 |
| 45–54 | C | 2,00 |
| 35–44 | D | 1,00 |
| < 35 | E | 0,00 |

Skala ini juga **hardcoded** di enum, belum bisa dikustomisasi per universitas lewat UI (beda dari niat blueprint "Skala nilai harus dapat disesuaikan oleh administrator").

### 6.3 Status Kehadiran

Implementasi aktual (`AttendanceStatus`) — **4 status**: `present` (Hadir), `permitted` (Izin), `sick` (Sakit), `absent` (Alpa). Blueprint §4.14 menyebut 6 status (+ Terlambat, Dispensasi) dan metode absensi lanjutan (QR/GPS/wajah) — belum ada, absensi murni input manual oleh dosen per pertemuan.

### 6.4 Status Mahasiswa

`StudentStatus`: `active`, `leave` (cuti), `graduated` (lulus), `inactive` (nonaktif), `dropped_out`. Lebih ringkas dari 9 status di blueprint §4.3 (belum ada `pindah`/`meninggal_dunia` sebagai status terpisah, dan tidak ada status "calon mahasiswa" karena modul PMB (§4.9 blueprint) belum dibangun).

### 6.5 Isolasi Multi-Tenant

Berlaku untuk seluruh resource Akademik tanpa kecuali (`TenantScoped` trait): data universitas lain tidak pernah muncul di list, akses by-id ke resource milik tenant lain menghasilkan 404 (bukan 403 — dianggap tidak ada, konsisten dengan pola KRS-13/TNT-02 di [BLACK-BOX-TESTING.md](./BLACK-BOX-TESTING.md) §11 & §25).

---

## 7. Alur Kerja Utama

**Alur KRS → Nilai → Absensi → Transkrip** (satu siklus semester, dieksekusi lewat panel admin oleh `academic_administrator`/`lecturer`, **bukan** lewat Portal Mahasiswa yang masih terputus — §4.7):

```
1. academic_administrator: POST /krs-items {student_id, class_section_id}
   → sistem cek kelas aktif, kapasitas, dan batas SKS (§6.1) → status "enrolled"

2. lecturer: POST /class-sections/{id}/attendances (per pertemuan, berulang
   selama satu semester) → satu baris Attendance per KrsItem per meeting_number

3. lecturer: PUT /krs-items/{id}/grade {score} → letter_grade otomatis
   (§6.2) atau override manual

4. (opsional) academic_administrator: PATCH /krs-items/{id}/drop
   → hanya berhasil kalau KRS belum punya Grade

5. siapa pun dengan students.read: GET /students/{id}/transcript
   → IP per semester + IPK dihitung on-the-fly dari seluruh Grade
   berstatus "enrolled" milik mahasiswa itu (§4.5)
```

**Alur Ujian** (dosen menyiapkan, mahasiswa mengerjakan — §4.6):

```
1. lecturer: POST /exams {class_section_id, questions_per_participant,
   question_selection_mode, randomize_questions, randomize_options}

2. lecturer: POST /exams/{exam}/questions (berulang) — atau
   POST /exams/{exam}/questions/apply-bank dari question-bank yang sudah ada

3. lecturer: PATCH /exams/{exam}/publish
   → divalidasi (§EXAM_RANDOMIZATION.md §8): pool tidak kosong,
   questions_per_participant <= pool, mode manual punya cukup soal terpilih,
   tiap soal punya tepat satu jawaban benar

4. student: POST /student/exams/{exam}/start
   → backend snapshot question_order/option_order sekali, stabil selamanya
   untuk attempt itu

5. student: PUT /student/exam-attempts/{id}/answer (berulang per soal)
   → PATCH .../submit
```

---

## 8. Celah & Risiko yang Sudah Didokumentasikan Sengaja

Bukan bug baru ditemukan — celah yang **dengan sengaja dibiarkan tercatat** di [BLACK-BOX-TESTING.md](./BLACK-BOX-TESTING.md) §12-13 (skenario NIL-09/ABS-08) supaya tidak terlupa sebelum modul ini dianggap "selesai":

1. **Dosen belum divalidasi harus pengampu kelas.** `PUT /krs-items/{id}/grade` dan `POST /class-sections/{id}/attendances` saat ini menerima aksi dosen mana pun (dengan `grades.create`/`attendance.create`) untuk kelas mana pun di tenant-nya — belum ada pengecekan "apakah dosen ini benar-benar diampu ke `class_section` ini". Perbaikannya butuh relasi eksplisit dosen↔kelas (yang saat ini tidak ada di `class_sections`) plus pengecekan di `GradePolicy`/`AttendancePolicy`.
2. **Portal Mahasiswa dan data akademik nyata adalah dua jalur terpisah** (§4.7) — risiko terbesar dari sisi produk: pengguna bisa mengira submit KRS lewat portal benar-benar tersimpan padahal tidak ada request yang terkirim sama sekali.
3. **Identity link Dosen/Pegawai → User tidak ada** (beda dari `Student.user_id` yang sudah ada) — memblokir Bimbingan Akademik (§4.8) dan validasi kepemilikan kelas di atas (poin 1) sekaligus, karena keduanya butuh cara aman mengaitkan `auth()->user()` ke baris `Lecturer` miliknya.
4. **Dekan/Kaprodi belum ter-scope ke fakultas/prodi masing-masing** — permission mereka secara teknis membuka baca ke seluruh data tenant, bukan cuma unit organisasi mereka (§2, baris `dean`/`head_of_study_program`).
5. **Batas SKS dan skala nilai huruf hardcoded** (§6.1, §6.2) — tidak bisa dikonfigurasi per universitas lewat UI seperti niat blueprint, padahal aturan ini realistis berbeda antar kampus.

---

## 9. Status Frontend

### Web (React 19 + Vite + TS, `frontend/src/`)

- Menu **"Akademik"** di `NAV_ITEMS` (`constants/nav.ts`) berisi 8 halaman: Program Studi, Kurikulum, Mata Kuliah, Kelas dan Jadwal, KRS, Absensi, Penilaian, Ujian, Bank Soal — masing-masing digerbangi permission (`study_programs.read`, dst. — lihat §2/§5).
- **Mahasiswa**, **Dosen**, **Pegawai** adalah menu top-level terpisah (bukan submenu Akademik) — `ROUTES.mahasiswa`/`dosen`/`pegawai`.
- Seluruh halaman list/detail (termasuk yang read-only §4.1) memanggil service API asli (`services/academicService.ts`) — tidak ada `PlaceholderPage` tersisa.
- Form tulis (`EnrollKrsModal`, `GradeFormModal`, dst. di `KrsPage.tsx`/`GradesPage.tsx`) sudah ada untuk KRS/Nilai/Absensi, memakai pola `Modal` + `react-hook-form` + `zod`, gating tombol lewat `usePermission()`.
- Menu **Portal Mahasiswa** (`/portal/*`) punya submenu "Akademik" sendiri (KRS, Jadwal, KHS, Nilai, Transkrip Sementara, Absensi) — lihat status terputusnya di §4.7.

### Mobile (Flutter, `mobile/lib/features/exam/`)

- Hanya submodul **Ujian** yang punya implementasi Flutter sejauh ini: domain (entities, `ExamRepository`, `ExamValidation`), data (`ExamRemoteDataSource`/`ExamRepositoryImpl`), presentation (`ExamFormScreen` — wizard 3 langkah, `QuestionBankScreen`, `ExamPreviewScreen`, `ExamListScreen`). Rute: `/akademik/ujian`, `/akademik/ujian/baru`, `/akademik/ujian/:id/edit`, `/akademik/ujian/:id/soal`, digerbangi `PermissionGate`.
- Modul akademik lain (data induk, KRS, Nilai, Absensi, Transkrip) **belum** punya implementasi Flutter — mobile app saat ini fokus di konfigurasi ujian sisi dosen/admin.

---

## 10. Ringkasan Gap Implementasi

| Fitur | Status |
|---|---|
| Data induk Mahasiswa/Dosen/Pegawai/Prodi/Kelas/Kurikulum/Mata Kuliah (baca) | ✅ Backend + frontend web |
| Input/ubah data induk (mahasiswa baru, dosen baru, dst.) | ❌ Tidak ada endpoint tulis sama sekali |
| KRS (daftar/batalkan, validasi kapasitas & batas SKS) | ✅ Backend + frontend web |
| Penilaian (input, konversi otomatis, override manual) | ✅ Backend + frontend web |
| Absensi (batch per pertemuan) | ✅ Backend + frontend web |
| Transkrip/IP/IPK (perhitungan) | ✅ Backend + frontend web |
| Ujian (konfigurasi, bank soal, randomisasi, publish) | ✅ Backend + frontend web + mobile Flutter (sisi dosen/admin) |
| Ujian (pengerjaan sungguhan sisi mahasiswa) | 🧩 Endpoint backend ada & teruji, layar Flutter belum dibangun |
| Portal Mahasiswa — Akademik (KRS/Jadwal/KHS/Nilai/Absensi/Transkrip) | 🧩 UI ada, nol pemanggilan API — data statis |
| Identity link Student → User | ✅ Ada (`students.user_id`, `StudentUserAccountSeeder`) |
| Identity link Lecturer/Employee → User | ❌ Belum ada |
| Validasi dosen = pengampu kelas (Nilai/Absensi) | ❌ Belum ada — celah terdokumentasi (§8) |
| Bimbingan Akademik (Dosen PA) | ❌ Belum ada tabel/endpoint sama sekali |
| Jadwal Perkuliahan (drag-and-drop, deteksi bentrok) | ❌ Belum ada |
| Tugas/Kuis mahasiswa | ❌ Belum ada |
| Cuti/Surat/Wisuda/Evaluasi Dosen | ❌ Belum ada |
| Batas SKS & skala nilai huruf — konfigurasi per universitas | ❌ Hardcoded di kode, bukan `system_settings` |
| Metode absensi lanjutan (QR/GPS/wajah) | ❌ Belum ada — murni input manual |
| Scoping baca Dekan/Kaprodi ke fakultas/prodi sendiri | ❌ Belum ada — akses baca efektif ke seluruh tenant |

---

## 11. Rekomendasi Prioritas Lanjutan

Urutan realistis berdasarkan apa yang sudah punya fondasi vs yang butuh desain dari nol:

1. **Validasi kepemilikan kelas untuk dosen** (§8 poin 1) — risiko keamanan/integritas data paling murah diperbaiki karena hanya butuh tambah relasi dosen↔kelas + satu pengecekan di `GradePolicy`/`AttendancePolicy`, tidak butuh tabel/modul baru.
2. **Identity link Lecturer → User** — prasyarat langsung untuk poin 1 secara aman, dan untuk Bimbingan Akademik (§4.8). Ikuti pola `Student.user_id` yang sudah terbukti jalan.
3. **Sambungkan Portal Mahasiswa (Akademik) ke API asli** — enam halaman (KRS, Jadwal, KHS, Nilai, Absensi, Transkrip) secara teknis sudah bisa dikerjakan sekarang karena `Student.user_id` sudah ada; ini pekerjaan integrasi, bukan pekerjaan modul backend baru.
4. **Selesaikan layar pengerjaan ujian mahasiswa di Flutter** — endpoint backend sudah lengkap dan teruji (`StudentExamController`), murni pekerjaan UI.
5. **Jadikan batas SKS dan skala nilai huruf konfigurasi per universitas** (lewat `system_settings`, mengikuti pola yang sudah dipakai modul lain) — supaya beda kebijakan akademik antar kampus tidak butuh perubahan kode.
6. **Bimbingan Akademik** dan **Jadwal Perkuliahan** — dua modul blueprint dengan nilai bisnis tertinggi berikutnya, tapi keduanya butuh desain data baru dari nol (relasi dosen-wali↔mahasiswa; kolom hari/jam/ruangan + deteksi bentrok di `class_sections`).
