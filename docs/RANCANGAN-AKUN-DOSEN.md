# Rancangan Akun & Fitur Dosen

> **Status:** Dokumen desain, disusun dari kondisi kode aktual (backend `app/Modules/Academic`, `app/Modules/Dashboard`, frontend `frontend/src`, mobile `mobile/lib/features/exam`) per sesi ini.
> **Dibuat:** 2026-09-11
> Pelengkap [RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md) (yang membahas seluruh Modul Akademik lintas role — dokumen ini mempersempit ke satu role: **`lecturer`** / "Dosen"), [RANCANGAN-APLIKASI.md](./RANCANGAN-APLIKASI.md) §3.6 & §4.4 (visi dosen di blueprint besar), dan [BLACK-BOX-TESTING.md](./BLACK-BOX-TESTING.md) §11-14 (skenario NIL-09/ABS-08 khusus dosen). Nama tabel/model/endpoint di sini merujuk persis ke kode yang ada; kalau baru rancangan (belum diimplementasikan), ditandai eksplisit.

## Daftar Isi

1. [Apa itu "Akun Dosen"](#1-apa-itu-akun-dosen)
2. [Ringkasan Akses (Permission)](#2-ringkasan-akses-permission)
3. [Struktur Data Terkait Dosen](#3-struktur-data-terkait-dosen)
4. [Fitur — Status Implementasi & Detail](#4-fitur--status-implementasi--detail)
   - [4.1 Data Induk yang Bisa Dilihat](#41-data-induk-yang-bisa-dilihat--read-only)
   - [4.2 Penilaian (Nilai)](#42-penilaian-nilai--crud-penuh)
   - [4.3 Absensi](#43-absensi--crud-penuh-batch-per-pertemuan)
   - [4.4 Ujian & Bank Soal](#44-ujian--bank-soal--crud-penuh)
   - [4.5 Dashboard Dosen](#45-dashboard-dosen)
   - [4.6 Evaluasi Dosen (diterima dari mahasiswa)](#46-evaluasi-dosen-diterima-dari-mahasiswa--belum-dibangun)
   - [4.7 Dosen Pembimbing Akademik](#47-dosen-pembimbing-akademik--role-terpisah-belum-dibangun)
   - [4.8 Profil & Akun Login Dosen](#48-profil--akun-login-dosen)
5. [Endpoint API Milik Dosen](#5-endpoint-api-milik-dosen)
6. [Aturan Bisnis yang Langsung Menyentuh Dosen](#6-aturan-bisnis-yang-langsung-menyentuh-dosen)
7. [Alur Kerja Dosen](#7-alur-kerja-dosen)
8. [Celah & Risiko Khusus Akun Dosen](#8-celah--risiko-khusus-akun-dosen)
9. [Status Frontend & Mobile](#9-status-frontend--mobile)
10. [Ringkasan Gap Implementasi](#10-ringkasan-gap-implementasi)
11. [Rekomendasi Prioritas Lanjutan](#11-rekomendasi-prioritas-lanjutan)

---

## 1. Apa itu "Akun Dosen"

Seperti "akun akademik", istilah "akun dosen" di CivitasOne merujuk ke dua hal yang **tidak otomatis terhubung**:

1. **Role `lecturer`** (label: **"Dosen"**) — satu baris di `OrganizationalRoleSeeder::ROLE_LABELS`, dipegang oleh akun login (`users` + `user_roles`) yang dipakai untuk masuk ke sistem dan menjalankan aksi (menilai, mengabsen, membuat ujian).
2. **Record `lecturers`** (tabel `app/Modules/Academic/Models/Lecturer.php`) — data induk kepegawaian dosen: NIDN, nama, email, fakultas, status aktif. Ini murni **data profil**, dibaca lewat menu "Dosen" di panel admin.

**Kedua hal ini tidak punya identity link.** Berbeda dari `Student` yang sudah punya kolom `user_id` (§3 [RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md)), model `Lecturer` **tidak** punya kolom yang menghubungkannya ke baris `User` tertentu. Akibatnya sistem tidak tahu "dosen yang sedang login ini record `lecturers` yang mana" — setiap aksi dosen (menilai, mengabsen, membuat ujian) hanya divalidasi lewat **permission role**, bukan lewat kepemilikan data. Ini konsekuensi yang mengalir ke hampir seluruh bagian dokumen ini (§3, §8).

Selain role `lecturer`, ada satu role lagi yang secara konsep juga "dosen" tapi **sengaja dipisah**: **`academic_advisor`** ("Dosen Pembimbing Akademik" / Dosen PA) — lihat §4.7. Dokumen ini fokus ke role `lecturer`; `academic_advisor` dibahas sebagai perbandingan karena statusnya jauh lebih mentah (nol permission, nol modul).

---

## 2. Ringkasan Akses (Permission)

Permission role `lecturer`, persis dari `OrganizationalRoleSeeder::ROLE_PERMISSIONS['lecturer']`:

```
students.read
study_programs.read, classes.read, courses.read
krs.read
grades.read, grades.create, grades.update
attendance.read, attendance.create, attendance.update
exams.read, exams.create, exams.update, exams.delete, exams.publish
exam_attempts.read, exam_attempts.create, exam_attempts.update
question_bank.read, question_bank.create, question_bank.update, question_bank.delete
```

**Yang sengaja/kebetulan tidak dimiliki dosen** (perlu diperhatikan karena berefek nyata ke UI — lihat §8):
- `lecturers.read` — dosen **tidak bisa** membuka menu "Dosen" (`/dosen`) di panel admin sendiri, walau menu itu tampil di `NAV_ITEMS` untuk role lain. Gating permission konsisten (403 di backend), tapi kontras dengan intuisi "dosen harusnya bisa lihat direktori dosen".
- `employees.read`, `invoices.read`, `curriculums.read`, `approval_requests.read` — dosen tidak melihat data pegawai, keuangan, authoring kurikulum, atau daftar approval tenant-wide.
- Tidak ada permission `lecturers.update`/`create` sama sekali di sistem (§4.1 [RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md) — data induk dosen memang belum bisa diinput lewat sistem untuk role manapun).

Dicek dengan `dosen@undigital.test` / `dosen@itmandala.test` / `dosen@stikessejahtera.test` (password `password`, satu akun per universitas — lihat README.md §Akun Sample).

---

## 3. Struktur Data Terkait Dosen

```
lecturers            nidn, name, email, is_active, faculty_id (nullable)
                     — TIDAK punya user_id. Tidak ada cara aman menghubungkan
                     auth()->user() yang login ke baris lecturers spesifik.

class_sections       course_id, academic_term_id, class_code, capacity
                     — TIDAK punya kolom lecturer_id / dosen pengampu sama
                     sekali. Sistem tidak tahu kelas mana diampu dosen mana.

krs_items            student_id, class_section_id, status
  ├─ grades          score, letter_grade — diisi dosen lewat krs_item_id
  └─ attendances     meeting_number, meeting_date, status — diisi dosen
                     lewat class_section_id (batch)

exams                class_section_id, creator (siapa yang membuat — tapi
                     TIDAK divalidasi bahwa creator = dosen pengampu kelas
                     itu, karena tidak ada data pengampu untuk dibandingkan)
```

**Implikasi struktural langsung:**
- Karena `class_sections` tidak punya `lecturer_id`, **tidak ada satu pun query di backend** yang bisa menjawab "kelas apa saja yang diampu dosen X" — baik untuk keperluan tampilan ("kelas saya") maupun keamanan (validasi kepemilikan). Ini akar dari celah NIL-09/ABS-08 (§8.1) dan alasan Dashboard Dosen (§4.5) tidak bisa dipersonalisasi.
- `Grade` dan `Attendance` terikat ke `KrsItem`/`ClassSection`, bukan ke dosen — begitu permission `grades.create`/`attendance.create` dimiliki, siapa pun dengan role itu bisa menulis ke kelas mana pun di tenant-nya (§6, §8.1).
- `exams.creator` menyimpan siapa yang membuat ujian, tapi ini **audit trail**, bukan **kontrol akses** — `ExamPolicy::manage()` hanya cek permission slug (`exams.create`/`exams.update`), tidak cek `creator === auth()->id()` maupun kepemilikan kelas.

---

## 4. Fitur — Status Implementasi & Detail

Legenda: **✅ CRUD penuh** · **📖 Read-only** · **🧩 Terbangun tapi belum tersambung** · **❌ Belum dibangun**.

### 4.1 Data Induk yang Bisa Dilihat — 📖 Read-only

Dosen bisa membaca (tidak menulis) empat dari tujuh resource data induk Modul Akademik:

| Resource | Endpoint | Permission | Catatan |
|---|---|---|---|
| Mahasiswa | `GET /students`, `GET /students/{id}` (+ `.../transcript`) | `students.read` | Tidak dibatasi ke mahasiswa di kelas yang diampu — dosen bisa lihat mahasiswa mana pun di tenant. |
| Program Studi | `GET /study-programs` | `study_programs.read` | — |
| Kelas & Jadwal | `GET /class-sections` | `classes.read` | Dipakai untuk memilih `class_section_id` saat mengabsen/membuat ujian. |
| Mata Kuliah | `GET /courses` | `courses.read` | — |

**Tidak bisa dilihat dosen:** direktori Dosen (§2), Pegawai, Kurikulum, Tagihan/Invoice.

### 4.2 Penilaian (Nilai) — ✅ CRUD penuh

| Aksi | Endpoint | Permission |
|---|---|---|
| Lihat nilai | `GET /grades` | `grades.read` |
| Input/ubah nilai | `PUT /krs-items/{id}/grade` | `grades.create`, `grades.update` |

- `score` 0–100 wajib; `letter_grade` opsional (dihitung otomatis — §6.2) atau override manual.
- Satu `KrsItem` = satu `Grade` (`updateOrCreate`) — input ulang mengoreksi baris yang sama.
- KRS berstatus `dropped` tidak bisa dinilai (409).
- **Tidak ada validasi kepemilikan kelas** — lihat §8.1.

### 4.3 Absensi — ✅ CRUD penuh (batch per pertemuan)

| Aksi | Endpoint | Permission |
|---|---|---|
| Lihat absensi | `GET /attendances` | `attendance.read` |
| Rekam satu pertemuan (batch) | `POST /class-sections/{id}/attendances` | `attendance.create`, `attendance.update` |

- Payload: `{meeting_number, meeting_date, entries: [{krs_item_id, status, notes?}]}`.
- Transaksional — satu entri invalid (bukan peserta `enrolled` aktif) menolak seluruh batch.
- Rekam ulang `meeting_number` yang sama meng-update, bukan menduplikasi.
- **Tidak ada validasi kepemilikan kelas** — lihat §8.1.

### 4.4 Ujian & Bank Soal — ✅ CRUD penuh

Submodul paling lengkap milik dosen — satu-satunya role dengan hak tulis penuh (`exams.*`, `question_bank.*`, `exam_attempts.*`).

- **Konfigurasi ujian:** `POST/PUT/DELETE /exams`, terikat ke satu `class_section_id`, tiga parameter independen (`question_selection_mode`: all/random/manual, `randomize_questions`, `randomize_options` — detail penuh di [EXAM_RANDOMIZATION.md](./EXAM_RANDOMIZATION.md)).
- **Soal:** `POST /exams/{exam}/questions` (manual) atau `POST /exams/{exam}/questions/apply-bank` (dari pool `question_bank_items` reusable lintas ujian).
- **Bank Soal:** `POST/PUT/DELETE /question-bank` — pool soal milik dosen, dipakai ulang di ujian mana pun.
- **Publish:** `PATCH /exams/{exam}/publish` — divalidasi (§EXAM_RANDOMIZATION.md §8): pool tidak kosong, `questions_per_participant <= pool`, mode manual punya cukup soal terpilih, tiap soal punya tepat satu jawaban benar.
- **Melihat hasil kerja mahasiswa:** dosen punya `exam_attempts.read/create/update` — bisa melihat/mengelola attempt lewat endpoint admin (`krs-items/{id}/exams/{exam}/attempt`, `exam-attempts/{id}/answer|submit`), **bukan** endpoint self-service `student/exam-attempts/*` (itu punya `exam_participation.*`, khusus mahasiswa sendiri).
- **Keamanan jawaban:** `ExamQuestionResource` (yang dilihat dosen, menyertakan `is_correct`) sengaja terpisah dari resource yang dilihat mahasiswa.
- **Belum ada validasi kepemilikan kelas** juga di sini secara implisit — dosen mana pun (dengan `exams.create`) bisa membuat ujian untuk `class_section_id` mana pun, sama seperti Nilai/Absensi (§8.1), meski belum ada skenario uji terdokumentasi khusus untuk ini seperti NIL-09/ABS-08.

**Yang belum dibangun:** layar pengerjaan ujian sungguhan mahasiswa di Flutter (bukan tanggung jawab akun dosen, tapi menentukan apakah ujian yang dosen publish benar-benar bisa dikerjakan — lihat §4.6 [RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md)).

### 4.5 Dashboard Dosen

Sistem **tidak** punya dashboard khusus per-role — satu `DashboardPage.tsx` / `GET /dashboard` yang sama untuk semua role, dengan kartu/chart yang **muncul-atau-hilang** menurut permission (`DashboardController::SUMMARY_PERMISSIONS`/`CHART_PERMISSIONS`), bukan dipersonalisasi ke konten "milik dosen ini".

**Yang benar-benar tampil untuk role `lecturer`** (hasil filter permission §2 terhadap `DashboardStatsService::buildStats()`):

| Kartu/chart | Tampil? | Alasan |
|---|:---:|---|
| `total_students`, `active_students` | ✅ | punya `students.read` |
| `total_study_programs` | ✅ | punya `study_programs.read` |
| `active_classes` | ✅ | punya `classes.read` |
| `student_growth`, `student_status`, `students_by_program`, `active_classes_by_program` | ✅ | permission sama seperti di atas |
| `pending_approvals` | ✅ (selalu tampil) | tanpa `approval_requests.read`, dihitung hanya submission milik dosen sendiri — bukan tenant-wide |
| `total_lecturers`, `total_employees`, `staff_by_unit` | ❌ | tidak punya `lecturers.read`/`employees.read` |
| `unpaid_invoices`, `payment_trend`, `invoice_status` | ❌ | tidak punya `invoices.read` |
| `approval_status` (chart) | ❌ | tidak punya `approval_requests.read` |

**Kesenjangan besar dengan blueprint** (`RANCANGAN-APLIKASI.md` §"Dashboard Dosen"): blueprint membayangkan dashboard berisi *Jadwal mengajar*, *Pertemuan hari ini*, *Tugas yang perlu diperiksa*, *Nilai yang belum lengkap*, **Kelas yang diampu**, **Mahasiswa bimbingan**, *Jadwal sidang*, *Aktivitas penelitian* — **tidak satu pun dari ini ada**. Dashboard aktual adalah statistik admin-akademik tenant-wide yang dipangkas oleh permission, bukan ringkasan kerja personal dosen. Ini konsekuensi langsung dari tidak adanya `class_sections.lecturer_id` (§3) — sistem secara struktural tidak bisa menjawab "kelas saya" untuk membangun kartu semacam itu.

### 4.6 Evaluasi Dosen (diterima dari mahasiswa) — ❌ Belum dibangun

Mahasiswa (lewat Portal, `PortalLecturerEvaluationPage.tsx`) punya halaman **"Evaluasi Dosen"** untuk menilai dosen pengampu (materi, kejelasan, ketepatan waktu, kesediaan membantu). **Status: 🧩 UI ada, data statis di komponen** — sama seperti seluruh Portal Mahasiswa (§4.7 [RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md)), tidak ada model/endpoint backend sama sekali.

Dari sisi dosen: **tidak ada satu pun** cara untuk melihat hasil evaluasi yang (nantinya) masuk dari mahasiswa — belum ada halaman "Evaluasi yang Saya Terima" atau semacamnya, karena fiturnya sendiri di sisi mahasiswa belum tersambung ke backend.

### 4.7 Dosen Pembimbing Akademik — role terpisah, belum dibangun

`academic_advisor` (label "Dosen Pembimbing Akademik") **bukan** varian dari role `lecturer` — ini role terpisah di `OrganizationalRoleSeeder`, diseed **sengaja tanpa permission apa pun**. Blueprint (§3.7, §4.19) membayangkan dosen PA bisa melihat mahasiswa bimbingan, menyetujui/menolak KRS, memberi catatan akademik, memantau IP/IPK.

**Tidak ada satu pun**: tabel relasi mahasiswa↔dosen-wali, endpoint approval KRS oleh dosen PA, model catatan bimbingan. Diblokir oleh prasyarat yang sama seperti §8.1: butuh identity link Dosen→User dulu, supaya "mahasiswa bimbingan saya" bisa di-scope aman ke dosen yang login. Dalam praktiknya, satu orang dosen kemungkinan besar perlu **dua role sekaligus** (`lecturer` + `academic_advisor`) begitu fitur ini dibangun — bukan satu role gabungan.

### 4.8 Profil & Akun Login Dosen

- Login dosen memakai flow umum `POST /login` (Sanctum) di `app/Modules/Auth/` — sama seperti seluruh role, tidak ada flow login terpisah untuk dosen.
- **⚠️ Catatan working tree saat ini (belum di-commit):** `app/Modules/Auth/Routes/api.php` di baris login berubah dari `Route::post('login', ...)` menjadi `Route::get('login', ...)` — kalau ini bukan perubahan yang disengaja, endpoint login akan menerima kredensial lewat query string (GET), yang salah secara semantik HTTP dan berisiko kredensial ter-log di access log/browser history/proxy. Layak dicek sebelum di-commit; di luar cakupan dokumen ini jadi tidak diperbaiki di sini.
- Tidak ada halaman "Profil Dosen" khusus untuk dosen mengubah data dirinya sendiri (foto, kontak, dsb.) — konsisten dengan §4.1: data induk `lecturers` memang belum ada endpoint tulis untuk role manapun.

---

## 5. Endpoint API Milik Dosen

Subset dari referensi lengkap Modul Akademik ([RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md) §5) yang permission-nya benar-benar dimiliki role `lecturer`:

```
GET    students                                    students.read
GET    students/{student}                          students.read
GET    students/{student}/transcript                students.read
GET    study-programs                                study_programs.read
GET    class-sections                                classes.read
GET    class-sections/{classSection}                 classes.read
GET    courses                                       courses.read

GET    krs-items                                     krs.read

GET    grades                                        grades.read
PUT    krs-items/{krsItem}/grade                       grades.create, grades.update

GET    attendances                                   attendance.read
POST   class-sections/{classSection}/attendances       attendance.create, attendance.update

GET    exams                                         exams.read
POST   exams                                         exams.create
PUT    exams/{exam}                                    exams.update
DELETE exams/{exam}                                    exams.delete
PATCH  exams/{exam}/publish                            exams.publish
POST   exams/{exam}/questions                          exams.create, exams.update
PUT    exam-questions/{examQuestion}                    exams.update
DELETE exam-questions/{examQuestion}                    exams.update, exams.delete
POST   exams/{exam}/questions/apply-bank                exams.create, exams.update

GET    question-bank                                 question_bank.read
POST   question-bank                                 question_bank.create
PUT    question-bank/{questionBankItem}                question_bank.update
DELETE question-bank/{questionBankItem}                question_bank.delete

POST   krs-items/{krsItem}/exams/{exam}/attempt         exam_attempts.create
PUT    exam-attempts/{examAttempt}/answer                exam_attempts.update
PATCH  exam-attempts/{examAttempt}/submit                exam_attempts.update

GET    dashboard                                     (tanpa gate khusus — isi dipangkas per permission, §4.5)
```

Semua di bawah `auth:sanctum` + resolusi tenant (`X-University-ID`).

---

## 6. Aturan Bisnis yang Langsung Menyentuh Dosen

Detail penuh ada di [RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md) §6 — ringkasan yang relevan untuk aksi dosen:

**6.1 Konversi skor → huruf** (`LetterGrade::fromScore()`, dipakai saat dosen input nilai tanpa `letter_grade` manual):

| Skor | Huruf | Bobot |
|---:|:---:|---:|
| ≥ 85 | A | 4,00 |
| 75–84 | AB | 3,50 |
| 65–74 | B | 3,00 |
| 55–64 | BC | 2,50 |
| 45–54 | C | 2,00 |
| 35–44 | D | 1,00 |
| < 35 | E | 0,00 |

Hardcoded di enum — belum bisa dikustomisasi per universitas.

**6.2 Status kehadiran** yang bisa dipilih dosen saat mengabsen (`AttendanceStatus`): `present` (Hadir), `permitted` (Izin), `sick` (Sakit), `absent` (Alpa) — 4 status, murni input manual per pertemuan (tidak ada QR/GPS/wajah).

**6.3 Isolasi tenant** — dosen di satu universitas tidak pernah melihat/mengubah data universitas lain; akses by-id ke resource tenant lain → 404, bukan 403.

---

## 7. Alur Kerja Dosen

**Siklus Nilai/Absensi satu semester:**

```
1. lecturer: GET /class-sections?filter[academic_term_id]=... — lihat kelas
   yang (secara konvensi, bukan validasi sistem) ia ampu

2. lecturer: POST /class-sections/{id}/attendances (per pertemuan, berulang)
   → satu baris Attendance per KrsItem per meeting_number

3. lecturer: PUT /krs-items/{id}/grade {score} → letter_grade otomatis
   (§6.1) atau override manual
```

**Siklus Ujian:**

```
1. lecturer: POST /exams {class_section_id, questions_per_participant,
   question_selection_mode, randomize_questions, randomize_options}

2. lecturer: POST /exams/{exam}/questions (berulang) — atau
   POST /exams/{exam}/questions/apply-bank dari Bank Soal miliknya

3. lecturer: PATCH /exams/{exam}/publish
   → divalidasi: pool tidak kosong, questions_per_participant <= pool,
   mode manual punya cukup soal terpilih, tiap soal punya tepat satu
   jawaban benar

4. (mahasiswa mengerjakan lewat jalur self-service terpisah — di luar
   cakupan akun dosen, lihat §4.6 RANCANGAN-AKUN-AKADEMIK.md)
```

Kedua alur di atas **tidak divalidasi terhadap "apakah dosen ini benar-benar pengampu kelas ini"** — lihat §8.1.

---

## 8. Celah & Risiko Khusus Akun Dosen

### 8.1 Dosen bisa bertindak di kelas siapa pun (celah paling signifikan)

Terdokumentasikan sengaja di [BLACK-BOX-TESTING.md](./BLACK-BOX-TESTING.md) §12-13 (skenario **NIL-09**, **ABS-08**): `PUT /krs-items/{id}/grade`, `POST /class-sections/{id}/attendances`, dan (secara implisit, belum ada skenario uji terdokumentasi) `POST /exams` menerima aksi **dosen mana pun** yang punya permission terkait, untuk **kelas mana pun** di tenant-nya — bukan hanya kelas yang benar-benar ia ampu.

**Akar masalah struktural (§3):** `class_sections` tidak punya kolom `lecturer_id`, jadi tidak ada data untuk dibandingkan sekalipun `GradePolicy`/`AttendancePolicy`/`ExamPolicy` mau menambahkan pengecekan kepemilikan. Perbaikan butuh dua langkah berurutan:
1. Tambah relasi eksplisit dosen↔kelas (kolom `lecturer_id` di `class_sections`, atau tabel pivot kalau satu kelas bisa punya >1 dosen pengampu).
2. Identity link `Lecturer.user_id` (poin 8.2) — supaya `auth()->user()` bisa dipetakan balik ke baris `Lecturer`, baru kemudian ke `class_sections.lecturer_id`.

### 8.2 Tidak ada identity link Lecturer → User

Beda dari `Student.user_id` yang sudah ada dan dibackfill otomatis (`StudentUserAccountSeeder`), `Lecturer` **tidak** punya kolom setara. Ini memblokir **tiga hal sekaligus**: validasi kepemilikan kelas (8.1), Bimbingan Akademik/Dosen PA (§4.7), dan personalisasi Dashboard Dosen (§4.5 — "kelas saya", "mahasiswa bimbingan saya").

### 8.3 Dosen tidak bisa melihat direktori Dosen sendiri

Permission `lecturers.read` tidak ada di daftar permission role `lecturer` (§2) — konsisten di backend (403) tapi berpotensi membingungkan dari sisi UX kalau menu "Dosen" muncul di navigasi tanpa dijelaskan kenapa dosen sendiri tidak bisa membukanya. Perlu diperiksa apakah ini keputusan produk yang disengaja atau permission yang lupa ditambahkan.

### 8.4 Dashboard tidak dipersonalisasi

Dashboard yang dilihat dosen adalah statistik admin-akademik tenant-wide yang dipangkas permission (§4.5) — bukan ringkasan kerja personal ("kelas saya hari ini", "nilai yang belum saya input"). Ini bukan bug, tapi kesenjangan besar dari visi blueprint yang perlu disadari sebelum dianggap "dashboard dosen sudah ada".

### 8.5 Evaluasi Dosen: dosen tidak bisa melihat hasilnya

Bahkan setelah fitur evaluasi dosen di sisi mahasiswa (§4.6) tersambung ke backend suatu saat, saat ini **belum ada rencana konkret** (tabel/endpoint) untuk sisi dosen melihat rekap evaluasi yang diterimanya — perlu didesain bersamaan, bukan cuma "sambungkan form mahasiswa".

---

## 9. Status Frontend & Mobile

### Web (React 19 + Vite + TS, `frontend/src/`)

- Dosen memakai **panel admin yang sama** dengan role lain — tidak ada UI terpisah "khusus dosen". Menu yang terlihat/terbuka murni hasil gating `usePermission()` per item `NAV_ITEMS`.
- Halaman yang benar-benar bisa dipakai dosen penuh: **Penilaian** (`GradesPage.tsx`), **Absensi** (`AttendancePage.tsx`), **Ujian** (`ExamsPage.tsx`, `ExamDetailPage.tsx`), **Bank Soal** (`QuestionBankPage.tsx`) — semua memakai `services/academicService.ts` (API asli, bukan data statis), form tulis lewat `Modal` + `react-hook-form` + `zod`.
- Halaman yang tampil tapi read-only untuk dosen: `StudentsPage.tsx`, `StudyProgramsPage.tsx`, `ClassSectionsPage.tsx`, `CoursesPage.tsx` — tombol tulis otomatis tersembunyi (dosen tidak punya permission `.create`/`.update` di resource-resource ini).
- Halaman yang **tidak muncul sama sekali** untuk dosen (permission gate menutup menu-nya): `LecturersPage.tsx`, `EmployeesPage.tsx`, halaman Kurikulum, halaman Keuangan.
- Dashboard (`DashboardPage.tsx`) sama untuk semua role — lihat §4.5 untuk kartu yang benar-benar tampil.

### Mobile (Flutter, `mobile/lib/features/`)

- **Hanya submodul Ujian** yang punya implementasi Flutter, dan hanya sisi konfigurasi (dosen/admin) — bukan sisi mahasiswa mengerjakan. `exam_list_screen.dart`, `exam_form_screen.dart` (wizard 3 langkah), `question_bank_screen.dart`, `exam_preview_screen.dart`, digerbangi `PermissionGate` (`exams.read`, `exams.create,exams.update`) di `app_router.dart`.
- **Tidak ada** implementasi Flutter untuk Nilai atau Absensi — dosen yang ingin menilai/mengabsen lewat mobile saat ini **tidak bisa**, harus lewat web.
- `mobile/lib/features/dashboard/presentation/screens/dashboard_screen.dart` masih **placeholder Fase 0** (`"Dashboard penuh (BAB 6.2) menyusul di Fase 1."`) — dashboard dosen versi mobile belum ada sama sekali, baik yang generik maupun yang personal.
- Login dosen di mobile memakai flow umum `mobile/lib/features/auth/` — tidak ada pembeda alur berdasarkan role.

---

## 10. Ringkasan Gap Implementasi

| Fitur | Status |
|---|---|
| Login & sesi dosen (web + mobile) | ✅ Berfungsi (lihat catatan working tree §4.8 soal `login` route) |
| Lihat data induk terbatas (mahasiswa, prodi, kelas, mata kuliah) | ✅ Backend + frontend web |
| Input/ubah Nilai | ✅ Backend + frontend web — mobile ❌ |
| Rekam Absensi (batch per pertemuan) | ✅ Backend + frontend web — mobile ❌ |
| Ujian & Bank Soal (konfigurasi, publish) | ✅ Backend + frontend web + mobile Flutter |
| Melihat hasil pengerjaan ujian mahasiswa (attempt) | ✅ Endpoint ada (`exam_attempts.read`) — belum ada layar rekap nilai ujian agregat |
| Dashboard ringkasan tenant (dipangkas permission) | ✅ Web — mobile 🧩 placeholder Fase 0 |
| Dashboard personal ("kelas saya", "nilai belum diinput") | ❌ Butuh `class_sections.lecturer_id` dulu |
| Validasi dosen = pengampu kelas (Nilai/Absensi/Ujian) | ❌ Celah terdokumentasi (§8.1) |
| Identity link Lecturer → User | ❌ Belum ada |
| Direktori Dosen (lihat profil dosen lain) | ❌ Dosen tidak punya `lecturers.read` |
| Profil Dosen (ubah data diri sendiri) | ❌ Tidak ada endpoint tulis `lecturers` untuk role manapun |
| Evaluasi Dosen (melihat hasil evaluasi dari mahasiswa) | ❌ Bahkan sisi mahasiswanya masih data statis |
| Dosen Pembimbing Akademik (role `academic_advisor`) | ❌ Role terpisah, nol permission, nol modul |
| Jadwal mengajar terstruktur (hari/jam/ruangan) | ❌ `class_sections` tidak punya kolom jadwal sama sekali |

---

## 11. Rekomendasi Prioritas Lanjutan

1. **Tambah `class_sections.lecturer_id`** (atau tabel pivot bila multi-dosen per kelas) — perubahan skema paling murah yang membuka jalan untuk poin 2-4 sekaligus.
2. **Identity link `Lecturer.user_id`**, mengikuti pola `Student.user_id` yang sudah terbukti jalan (kolom nullable-unique + seeder backfill).
3. **Validasi kepemilikan kelas** di `GradePolicy`/`AttendancePolicy`/`ExamPolicy` begitu poin 1-2 tersedia — menutup celah NIL-09/ABS-08 secara nyata, bukan cuma didokumentasikan.
4. **Dashboard personal dosen** ("kelas saya hari ini", "nilai belum lengkap per kelas") — nilai bisnis tinggi begitu poin 1 ada, dan sekarang secara struktural tidak mungkin dibangun.
5. **Cek dan bereskan perubahan `login` route** (`GET` vs `POST`) yang saat ini ada di working tree tapi belum di-commit (§4.8) — sebelum menyentuh area lain di Modul Auth.
6. **Putuskan status `lecturers.read` untuk role `lecturer`** — sengaja dikecualikan atau perlu ditambahkan agar dosen bisa lihat direktori dosen sendiri.
7. **Fitur Evaluasi Dosen** dan **Bimbingan Akademik (Dosen PA)** — dua modul bernilai bisnis tinggi berikutnya, tapi keduanya butuh desain data baru dari nol dan bergantung pada poin 2 (identity link) sebagai prasyarat.
