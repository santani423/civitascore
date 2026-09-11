# Rancangan Akun & Fitur Mahasiswa

> **Status:** Dokumen desain, disusun dari kondisi kode aktual (backend `app/Modules/Academic`, `app/Modules/Dashboard`, frontend `frontend/src/pages/portal`, mobile `mobile/lib/`) per sesi ini.
> **Dibuat:** 2026-09-11
> Pelengkap [RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md) (Modul Akademik lintas role) dan [RANCANGAN-AKUN-DOSEN.md](./RANCANGAN-AKUN-DOSEN.md) (role `lecturer`, pola dokumen yang sama). Nama tabel/model/endpoint di sini merujuk persis ke kode yang ada; kalau baru rancangan (belum diimplementasikan), ditandai eksplisit. Dibanding dua dokumen sebelumnya, temuan intinya beda arah: sisi dosen kelebihan hak tulis tanpa validasi kepemilikan; sisi mahasiswa **hampir tidak punya hak baca/tulis sama sekali** di luar satu submodul (Ujian) — lihat §8.1 untuk kenapa ini bukan sekadar "belum sempat disambungkan".

## Daftar Isi

1. [Apa itu "Akun Mahasiswa"](#1-apa-itu-akun-mahasiswa)
2. [Ringkasan Akses (Permission)](#2-ringkasan-akses-permission)
3. [Struktur Data Terkait Mahasiswa](#3-struktur-data-terkait-mahasiswa)
4. [Fitur — Status Implementasi & Detail](#4-fitur--status-implementasi--detail)
   - [4.1 Portal Mahasiswa — Peta 21 Halaman](#41-portal-mahasiswa--peta-21-halaman)
   - [4.2 Ujian — Satu-satunya Fitur Self-Service Nyata](#42-ujian--satu-satunya-fitur-self-service-nyata)
   - [4.3 KRS, Nilai, Absensi, Transkrip — Terlihat, Tidak Bisa Dipakai](#43-krs-nilai-absensi-transkrip--terlihat-tidak-bisa-dipakai)
   - [4.4 Dashboard Mahasiswa](#44-dashboard-mahasiswa--nyaris-kosong)
   - [4.5 Modul Lain di Portal](#45-modul-lain-di-portal--belum-ada-backend-sama-sekali)
   - [4.6 Login & Identitas](#46-login--identitas)
5. [Endpoint API Milik Mahasiswa](#5-endpoint-api-milik-mahasiswa)
6. [Aturan Bisnis yang Langsung Menyentuh Mahasiswa](#6-aturan-bisnis-yang-langsung-menyentuh-mahasiswa)
7. [Alur Kerja Mahasiswa](#7-alur-kerja-mahasiswa)
8. [Celah & Risiko Khusus Akun Mahasiswa](#8-celah--risiko-khusus-akun-mahasiswa)
9. [Status Frontend & Mobile](#9-status-frontend--mobile)
10. [Ringkasan Gap Implementasi](#10-ringkasan-gap-implementasi)
11. [Rekomendasi Prioritas Lanjutan](#11-rekomendasi-prioritas-lanjutan)

---

## 1. Apa itu "Akun Mahasiswa"

- **Role `student`** (label: **"Mahasiswa"**) — satu baris di `OrganizationalRoleSeeder::ROLE_LABELS`, dengan permission paling sempit di seluruh sistem (§2).
- **Record `students`** (`app/Modules/Academic/Models/Student.php`) — data induk mahasiswa: NIM, nama, email, prodi, status, tahun masuk.

**Beda dari `Lecturer`, `Student` sudah punya identity link penuh:** kolom `user_id` (nullable, unique) menghubungkan record ke baris `users`, dibackfill otomatis oleh `StudentUserAccountSeeder` di setiap `db:seed` — setiap mahasiswa punya akun login sendiri, bukan berbagi satu akun demo `mahasiswa@...`. Password default hasil seeding adalah **NIM mahasiswa itu sendiri**, email fallback (kalau `Student.email` kosong) berpola `{nim}@student.{kode-universitas}.local`.

Konsekuensinya: di seluruh sistem, `Student` adalah **satu-satunya** entitas akademik dengan identity link yang benar-benar dipakai untuk otorisasi object-level (`ExamParticipationPolicy`, §3) — bukan sekadar kolom yang ada tapi belum dipakai (bandingkan dengan `Lecturer.user_id` yang tidak ada sama sekali, [RANCANGAN-AKUN-DOSEN.md](./RANCANGAN-AKUN-DOSEN.md) §3).

"Akun Mahasiswa" dalam praktik berarti login ke **Portal Mahasiswa** (`/portal/*`, React, 21 halaman) — bukan panel admin. Mahasiswa **tidak** melihat menu admin (Akademik, Dosen, Mahasiswa, dst.) karena permission-nya memang tidak mengizinkan satu pun (§2).

---

## 2. Ringkasan Akses (Permission)

Permission role `student`, persis dari `OrganizationalRoleSeeder::ROLE_PERMISSIONS['student']` — **hanya tiga**, semuanya untuk satu submodul:

```
exam_participation.read
exam_participation.create
exam_participation.update
```

**Tidak ada** `students.read`, `krs.read`/`krs.create`, `grades.read`, `attendance.read`, `classes.read`, `courses.read`, `study_programs.read` — nol permission baca sama sekali di luar ujian. Ini berarti:

- Mahasiswa **tidak bisa** memanggil `GET /students/{id}/transcript` (miliknya sendiri sekalipun) — endpoint itu butuh `students.read`.
- Mahasiswa **tidak bisa** memanggil `GET /krs-items`, `GET /grades`, `GET /attendances` — semua butuh permission yang tidak dimiliki role ini.
- Satu-satunya jalur yang bisa dipakai mahasiswa adalah enam endpoint `student/exams*` dan `student/exam-attempts*` (§5), yang memang dirancang khusus self-service dan tidak memakai permission admin di atas.

Login test: `mahasiswa@undigital.test` / `mahasiswa@itmandala.test` / `mahasiswa@stikessejahtera.test` (password `password` — akun demo bersama, beda dari akun per-NIM hasil `StudentUserAccountSeeder` yang passwordnya NIM masing-masing).

---

## 3. Struktur Data Terkait Mahasiswa

```
students             nim, name, email, admission_year, status, enrolled_at,
                     graduated_at, user_id (→ users.id, sudah ada & terpakai)

krs_items            student_id, class_section_id, status (enrolled|dropped)
  ├─ grades          score, letter_grade
  └─ attendances     meeting_number, status

exams                class_section_id, is_published, starts_at, ends_at,
                     duration_minutes, max_attempts, show_result_after_submission
  └─ exam_attempts   krs_item_id, attempt_number, status, question_order[],
                     option_order{}, score, started_at, submitted_at
      └─ exam_attempt_answers
```

**Satu-satunya tempat di seluruh Modul Akademik dengan otorisasi object-level nyata** (bukan cuma permission slug) adalah `ExamParticipationPolicy`:

```php
startOwn(User $user, KrsItem $krsItem):
    $user->hasPermissionTo('exam_participation.create')
    && $user->student !== null
    && $user->student->id === $krsItem->student_id   // ← cross-check kepemilikan

recordOwn(User $user, ExamAttempt $attempt):
    ... && $user->student->id === $attempt->krsItem->student_id
```

`StudentExamController` **tidak pernah** menerima `krs_item_id` dari input klien — selalu diresolusi lewat `ExamService::resolveEligibleKrsItem($exam, $student)` dari identitas `$user->student` yang login, lalu di-cross-check ulang oleh policy di atas sebelum memproses apa pun (defense-in-depth terhadap manipulasi ID). Pola ini **tidak ditiru** oleh `GradePolicy`/`AttendancePolicy`/`KrsItemPolicy`/`StudentPolicy` — keempatnya murni cek permission slug, tanpa konsep "punya sendiri" (relevan untuk §8.1).

---

## 4. Fitur — Status Implementasi & Detail

Legenda: **✅ Terhubung ke API asli** · **🧩 UI ada, data statis (tidak terhubung)** · **❌ Belum dibangun sama sekali**.

### 4.1 Portal Mahasiswa — Peta 21 Halaman

`frontend/src/pages/portal/`, React, di bawah menu "Portal" (menu tenant biasa, terpisah dari panel admin). Dari 21 halaman, **hanya 2 yang benar-benar memanggil API**:

| Halaman | Status | Keterangan |
|---|:---:|---|
| `PortalExamsPage.tsx` | ✅ | Daftar ujian yang dipublish + status pengerjaan, lewat `studentExamService` |
| `PortalExamTakingPage.tsx` | ✅ | Layar pengerjaan ujian sungguhan — lihat §4.2 |
| `PortalDashboardPage.tsx` | 🧩 | Data statis |
| `PortalKrsPage.tsx` | 🧩 | Data statis — submit KRS tidak mengirim request apa pun |
| `PortalSchedulePage.tsx` | 🧩 | Data statis |
| `PortalKhsPage.tsx` | 🧩 | Data statis |
| `PortalTranscriptPage.tsx` | 🧩 | Data statis |
| `PortalGradesPage.tsx` | 🧩 | Data statis |
| `PortalAttendancePage.tsx` | 🧩 | Data statis |
| `PortalAssignmentsPage.tsx` (Tugas) | 🧩 | Data statis, **tidak ada** modul backend `Assignment` |
| `PortalQuizzesPage.tsx` (Kuis) | 🧩 | Data statis, tidak ada modul backend |
| `PortalLeaveRequestPage.tsx` (Cuti) | 🧩 | Data statis, tidak ada modul backend |
| `PortalLetterRequestPage.tsx` (Surat) | 🧩 | Data statis, tidak ada modul backend |
| `PortalScholarshipPage.tsx` (Beasiswa) | 🧩 | Data statis, tidak ada modul backend |
| `PortalInvoicesPage.tsx` (Tagihan) | 🧩 | Data statis — modul Finance ada di backend, tapi belum ada endpoint self-service mahasiswa untuk itu |
| `PortalAcademicAdvisingPage.tsx` (Bimbingan Akademik) | 🧩 | Data statis, bergantung pada Dosen PA (§4.7 [RANCANGAN-AKUN-DOSEN.md](./RANCANGAN-AKUN-DOSEN.md)) yang belum dibangun |
| `PortalThesisAdvisingPage.tsx` (Bimbingan Skripsi) | 🧩 | Data statis, tidak ada modul backend |
| `PortalAnnouncementsPage.tsx` (Pengumuman) | 🧩 | Data statis |
| `PortalGraduationPage.tsx` (Wisuda) | 🧩 | Data statis, tidak ada modul backend |
| `PortalLecturerEvaluationPage.tsx` (Evaluasi Dosen) | 🧩 | Data statis — lihat §4.6 [RANCANGAN-AKUN-DOSEN.md](./RANCANGAN-AKUN-DOSEN.md) |
| `PortalProfilePage.tsx` | 🧩 | Data statis — mahasiswa belum bisa lihat/ubah profil sendiri lewat API |

### 4.2 Ujian — Satu-satunya Fitur Self-Service Nyata

`StudentExamController` (backend) + `PortalExamsPage`/`PortalExamTakingPage` (frontend) — jalur produk yang benar-benar selesai ujung ke ujung untuk mahasiswa.

**Alur teknis:**
1. `GET /student/exams` — daftar ujian **published** dari kelas yang diikuti mahasiswa (`KrsItem` berstatus `enrolled`), masing-masing dengan status personal: `upcoming` / `available` / `in_progress` / `completed` / `expired` (`ExamService::computeStudentStatus()`).
2. `POST /student/exams/{exam}/start` — idempotent: memanggil ulang saat sudah ada attempt `in_progress` mengembalikan attempt yang sama, bukan membuat baru. Ditolak (409) kalau belum masuk jadwal (`starts_at`) atau sudah lewat (`ends_at`), atau `attempt_number` melebihi `max_attempts`.
3. **Penugasan soal dibekukan sekali** saat attempt dibuat (`question_order[]`, `option_order{}` disimpan di baris `exam_attempts`) — refresh/keluar-masuk tidak pernah mengubah soal atau urutan yang sudah ditugaskan (§EXAM_RANDOMIZATION.md).
4. `PUT /student/exam-attempts/{id}/answer` — autosave tiap kali mahasiswa memilih/mengubah jawaban satu soal; validasi bahwa `exam_question_id`/`exam_question_option_id` benar-benar bagian dari penugasan attempt ini (menolak manipulasi ID).
5. **Batas waktu efektif** = mana yang lebih dulu antara `started_at + duration_minutes` dan `exam.ends_at` (`ExamService::attemptDeadline()`). Dicek lazy di setiap akses (`finalizeIfExpired()`, dipanggil dari `show`/`answer`/`submit`/`index`) — begitu waktu habis, attempt otomatis ditutup dan diskor di akses berikutnya, **tanpa** scheduled job terpisah.
6. `PATCH /student/exam-attempts/{id}/submit` — skor dihitung otomatis dari proporsi poin soal pilihan-ganda yang benar.
7. **Hasil hanya terlihat kalau** `exam.show_result_after_submission = true` **dan** attempt sudah `Submitted` — kalau tidak, response tetap 200 tapi field `score` disembunyikan (`isResultVisible()`), demikian juga `is_correct` tiap opsi tidak pernah dikirim ke resource mahasiswa (`StudentExamAttemptResource`, terpisah dari `ExamQuestionResource` sisi dosen).

**Keamanan berlapis:** permission slug (`exam_participation.*`) → `ExamParticipationPolicy` object-level (`$user->student->id === ...`) → `ExamService::resolveEligibleKrsItem()` (server yang menentukan `KrsItem`, bukan klien) → validasi jawaban terhadap `question_order`/`option_order` snapshot. Empat lapis ini satu-satunya di seluruh sistem yang benar-benar mengikat aksi ke identitas mahasiswa yang login.

### 4.3 KRS, Nilai, Absensi, Transkrip — Terlihat, Tidak Bisa Dipakai

Empat halaman ini ada di Portal dan **terlihat lengkap** secara visual, tapi statusnya bukan "tinggal disambungkan ke API yang sudah ada" — lihat penjelasan kenapa di §8.1. Ringkas:

| Fitur | Endpoint admin yang ada | Permission dibutuhkan | Dimiliki `student`? |
|---|---|---|:---:|
| Lihat KRS sendiri | `GET /krs-items?filter[student_id]=...` | `krs.read` | ❌ |
| Isi/batalkan KRS sendiri | `POST /krs-items`, `PATCH .../drop` | `krs.create`, `krs.update` | ❌ |
| Lihat nilai sendiri | `GET /grades?filter[student_id]=...` | `grades.read` | ❌ |
| Lihat absensi sendiri | `GET /attendances` | `attendance.read` | ❌ |
| Lihat transkrip/IP/IPK sendiri | `GET /students/{id}/transcript` | `students.read` | ❌ |

Bahkan kalau salah satu permission ini "sekadar ditambahkan" ke role `student`, hasilnya **membuka akses ke seluruh data tenant** (semua mahasiswa, bukan cuma diri sendiri) — karena keempat policy-nya tidak punya pengecekan kepemilikan seperti `ExamParticipationPolicy` (§3, §8.1). Menyambungkan fitur ini butuh endpoint self-service baru, bukan cuma permission baru.

### 4.4 Dashboard Mahasiswa — Nyaris Kosong

`GET /dashboard` (sama untuk semua role, `DashboardController`) memfilter kartu/chart berdasarkan permission (lihat detail mekanisme di [RANCANGAN-AKUN-DOSEN.md](./RANCANGAN-AKUN-DOSEN.md) §4.5). Karena role `student` **tidak punya satu pun** permission dari daftar `SUMMARY_PERMISSIONS`/`CHART_PERMISSIONS` (`students.read`, `lecturers.read`, `employees.read`, `study_programs.read`, `classes.read`, `invoices.read`, `approval_requests.read`):

- Seluruh kartu ringkasan (`total_students`, dst.) — **tersembunyi semua**.
- Seluruh chart — **tersembunyi semua**.
- `pending_approvals` tetap dihitung (selalu tampil), tapi scoped ke pengajuan approval milik mahasiswa sendiri — yang dalam praktiknya **selalu 0** karena belum ada alur approval yang bisa diajukan mahasiswa (Cuti/Surat/dsb. belum dibangun, §4.5).
- Hasil akhir: `hasAnyStat` bernilai `false`, `DashboardPage.tsx` menampilkan `EmptyState` — **"Belum ada statistik untuk ditampilkan."** — untuk setiap mahasiswa yang login ke panel/dashboard umum ini (`PortalDashboardPage.tsx` yang terpisah di Portal masih data statis, §4.1).

Ini bukan bug — tapi juga bukan sesuatu yang "sudah bekerja untuk mahasiswa", karena dashboard umum ini memang tidak pernah dirancang untuk konten personal mahasiswa (tidak ada kartu "IP semester ini", "SKS diambil", dsb.).

### 4.5 Modul Lain di Portal — Belum Ada Backend Sama Sekali

Sesuai §4.1: Tugas, Kuis, Cuti, Surat, Beasiswa, Bimbingan Akademik, Bimbingan Skripsi, Wisaudah, Evaluasi Dosen — **tidak ada satu pun** tabel/controller/endpoint di `app/Modules/`. Halaman-halamannya di Portal murni maket visual.

### 4.6 Login & Identitas

- Login mahasiswa memakai flow umum `POST /login` (Sanctum, `app/Modules/Auth/`) — sama seperti role lain, tidak ada flow terpisah.
- **⚠️ Catatan working tree saat ini (belum di-commit, sama seperti dicatat di [RANCANGAN-AKUN-DOSEN.md](./RANCANGAN-AKUN-DOSEN.md) §4.8):** `app/Modules/Auth/Routes/api.php` mengubah route login dari `POST` menjadi `GET` — kalau bukan disengaja, ini merusak login untuk semua role termasuk mahasiswa. Di luar cakupan dokumen ini untuk diperbaiki.
- Password default akun mahasiswa hasil seeding = **NIM** (§1) — perlu dipastikan ada alur ganti password wajib di produksi nanti (di luar cakupan seeder demo ini, tapi relevan untuk keamanan produksi).

---

## 5. Endpoint API Milik Mahasiswa

**Seluruh** endpoint yang benar-benar bisa dipanggil role `student` di sistem, tanpa kecuali:

```
GET    student/exams                                  exam_participation.read
GET    student/exams/{exam}                            exam_participation.read
POST   student/exams/{exam}/start                       exam_participation.create
GET    student/exam-attempts/{examAttempt}               exam_participation.read
PUT    student/exam-attempts/{examAttempt}/answer          exam_participation.update
PATCH  student/exam-attempts/{examAttempt}/submit           exam_participation.update

GET    dashboard                                     (tanpa gate khusus — hasilnya kosong, §4.4)
```

Tujuh baris ini adalah **seluruh permukaan API** yang dimiliki akun mahasiswa — dibanding puluhan endpoint yang dimiliki `academic_administrator` atau `lecturer` ([RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md) §5).

---

## 6. Aturan Bisnis yang Langsung Menyentuh Mahasiswa

**6.1 Batas percobaan ujian** — `attempt_number` tidak boleh melebihi `exam.max_attempts`; percobaan baru hanya dibuat kalau percobaan sebelumnya sudah `Submitted` (in-progress selalu dikembalikan, bukan dibuat ulang).

**6.2 Batas waktu** — deadline efektif = `min(started_at + duration_minutes, exam.ends_at)`. Melewati deadline menutup & menskor attempt otomatis di akses berikutnya (§4.2 poin 5), mahasiswa tidak bisa menjawab lagi setelah itu (409 "Waktu ujian sudah habis").

**6.3 Kelayakan ikut ujian** — mahasiswa hanya bisa memulai ujian kalau `KrsItem`-nya untuk `class_section` ujian tersebut berstatus `enrolled` (bukan `dropped`) — dicek server-side lewat `resolveEligibleKrsItem()`, tidak bisa dimanipulasi dari klien.

**6.4 Visibilitas hasil** — diatur per-ujian oleh dosen (`show_result_after_submission`), bukan pengaturan global — mahasiswa tidak punya kontrol atas ini.

**6.5 Isolasi tenant** — berlaku sama seperti seluruh Modul Akademik (`TenantScoped`) — mahasiswa universitas lain tidak pernah muncul di data yang sama.

---

## 7. Alur Kerja Mahasiswa

```
1. student: login → GET /dashboard → kosong (§4.4), tidak informatif untuk
   kegiatan akademik sehari-hari

2. student: GET /student/exams → lihat ujian published dari kelas yang
   diikuti (KrsItem enrolled), dengan status personal per ujian

3. student: POST /student/exams/{exam}/start → attempt dibuat/dilanjutkan,
   soal+urutan dibekukan

4. student: PUT /student/exam-attempts/{id}/answer (berulang, autosave
   per soal, sampai waktu habis atau mahasiswa submit manual)

5. student: PATCH /student/exam-attempts/{id}/submit → skor dihitung,
   hasil terlihat hanya jika exam.show_result_after_submission = true
```

**Tidak ada alur kerja lain yang bisa dijalankan mahasiswa lewat sistem saat ini** — KRS, lihat nilai, lihat absensi, lihat transkrip, semuanya masih harus lewat `academic_administrator`/`lecturer` di panel admin atas nama mahasiswa (lihat alur KRS→Nilai→Absensi→Transkrip di [RANCANGAN-AKUN-AKADEMIK.md](./RANCANGAN-AKUN-AKADEMIK.md) §7, yang secara eksplisit dijalankan **bukan** oleh mahasiswa).

---

## 8. Celah & Risiko Khusus Akun Mahasiswa

### 8.1 Menyambungkan Portal bukan sekadar "panggil API yang sudah ada"

Klaim yang gampang muncul dari luar: "endpoint KRS/Nilai/Absensi/Transkrip sudah ada, identity link `Student.user_id` sudah ada, tinggal sambungkan Portal ke situ." **Ini tidak cukup, dan berbahaya kalau dilakukan naif** (§3, §4.3): `GradePolicy`, `AttendancePolicy`, `KrsItemPolicy`, `StudentPolicy` semuanya murni cek permission slug tanpa pengecekan kepemilikan. Memberi role `student` permission `grades.read` misalnya akan membuka **`GET /grades` tanpa filter kepemilikan** — satu mahasiswa bisa melihat nilai seluruh mahasiswa lain di tenant yang sama. Jalan yang benar adalah membangun endpoint self-service baru (mengikuti pola `student/exams*` + `ExamParticipationPolicy`, §3, §4.2) — bukan menambah permission admin ke role mahasiswa.

### 8.2 Portal adalah risiko UX/kepercayaan terbesar di seluruh sistem

19 dari 21 halaman Portal terlihat fungsional lengkap tapi murni maket. Risiko konkret: mahasiswa mengisi form KRS di `/portal/krs`, form terlihat submit sukses (tidak ada indikasi kegagalan karena memang tidak ada request yang dikirim), tapi **tidak ada apa pun yang tersimpan**. Sama untuk Evaluasi Dosen, Cuti, Surat, dsb. Ini bukan bug tersembunyi — tapi juga belum ada guardrail (banner "fitur belum aktif", disabled state, dsb.) yang mencegah kesalahpahaman ini di UI saat ini.

### 8.3 Dashboard umum tidak berguna untuk mahasiswa

§4.4 — mahasiswa yang membuka dashboard utama (di luar Portal) hanya melihat pesan kosong. Kalau dashboard ini pernah dimaksudkan juga untuk mahasiswa, perlu kartu yang benar-benar relevan untuk mereka (IP semester berjalan, SKS diambil, ujian mendatang) — bukan versi terpangkas dari dashboard admin.

### 8.4 Mobile: nol fitur untuk mahasiswa

`mobile/lib/core/router/app_router.dart` hanya punya rute login, dashboard (placeholder Fase 0), pengaturan keamanan, dan Ujian — dan rute Ujian itu sendiri digerbangi `exams.read`/`exams.create,exams.update`, permission milik **dosen**, bukan `exam_participation.*` milik mahasiswa. Kalau mahasiswa login ke aplikasi mobile hari ini, **tidak ada satu layar akademik pun** yang bisa mereka akses — bahkan versi mobile dari fitur ujian self-service yang sudah matang di web (§4.2) belum punya rute maupun layar sama sekali.

### 8.5 Password default = NIM

Hasil seeding (`StudentUserAccountSeeder`) memberi setiap mahasiswa password awal = NIM mereka sendiri, yang biasanya bukan rahasia (tercetak di KTM, ijazah, dsb.). Wajar untuk lingkungan demo/seed, tapi kalau pola ini terbawa ke alur onboarding produksi tanpa paksaan ganti password di login pertama, ini jadi celah keamanan nyata — layak dipastikan sebelum go-live.

---

## 9. Status Frontend & Mobile

### Web (React 19 + Vite + TS, `frontend/src/pages/portal/`)

- Menu terpisah dari panel admin — mahasiswa hanya melihat menu "Portal", tidak ada satu pun menu admin yang terbuka untuknya (§2).
- 21 halaman total; **2 terhubung ke API asli** (Ujian — §4.1, §4.2), **19 masih data statis**.
- Tidak ada indikator visual di UI yang membedakan halaman yang "hidup" dari yang "maket" — dari sudut pandang mahasiswa yang login, semuanya terlihat sama-sama fungsional.

### Mobile (Flutter, `mobile/lib/`)

- **Tidak ada fitur mahasiswa sama sekali** — lihat §8.4. Aplikasi mobile saat ini murni untuk dosen/admin (konfigurasi ujian) dan infrastruktur auth umum (login, lupa password, sesi keamanan).

---

## 10. Ringkasan Gap Implementasi

| Fitur | Status |
|---|---|
| Login & sesi mahasiswa (per-NIM, identity link) | ✅ Backend lengkap, teruji |
| Mengerjakan ujian (lihat daftar, kerjakan, autosave, submit, lihat hasil) | ✅ Backend + frontend web — mobile ❌ |
| Melihat KRS/Nilai/Absensi/Transkrip milik sendiri | ❌ Endpoint self-service belum ada (bukan cuma UI belum disambung — §8.1) |
| Mengisi/membatalkan KRS sendiri | ❌ Sama sekali tidak ada — hanya `academic_administrator` yang bisa |
| Dashboard ringkasan personal (IP, SKS, ujian mendatang) | ❌ Dashboard umum kosong untuk role ini (§4.4) |
| Tugas, Kuis | ❌ Tidak ada modul backend |
| Cuti, Surat, Beasiswa, Wisuda | ❌ Tidak ada modul backend |
| Bimbingan Akademik, Bimbingan Skripsi | ❌ Tidak ada modul backend, bergantung pada Dosen PA yang juga belum ada |
| Evaluasi Dosen (mengisi) | ❌ UI ada, backend tidak ada |
| Pengumuman | ❌ UI ada, backend tidak ada |
| Profil (lihat/ubah data diri) | ❌ Tidak ada endpoint self-service |
| Aplikasi mobile untuk mahasiswa | ❌ Nol fitur — bahkan Ujian yang sudah matang di web belum ada di mobile |

---

## 11. Rekomendasi Prioritas Lanjutan

1. **Bangun endpoint self-service KRS/Nilai/Absensi/Transkrip** mengikuti pola `student/exams*` + `ExamParticipationPolicy` (§3, §8.1) — permission baru (`krs_self_service.*` atau semacamnya) yang di-scope object-level ke `$user->student`, bukan sekadar mewarisi permission admin. Ini pekerjaan desain backend baru, bukan "tinggal sambungkan".
2. **Tambahkan guardrail UI** di 19 halaman Portal yang masih statis — minimal banner/disabled-state yang jujur, supaya mahasiswa tidak mengira submit form benar-benar tersimpan (§8.2) sebelum poin 1 selesai per-modul.
3. **Rancang ulang konten dashboard untuk role mahasiswa** — kartu "IP semester ini", "SKS diambil semester ini", "ujian mendatang" — begitu poin 1 tersedia untuk dijadikan sumber data (§8.3, §4.4).
4. **Bawa fitur Ujian self-service yang sudah matang di web ke mobile** — nilai bisnis tinggi dan berisiko rendah karena backend & pola keamanan sudah selesai dan teruji (§4.2); murni pekerjaan UI Flutter + routing dengan permission `exam_participation.*`.
5. **Pastikan alur ganti password wajib di login pertama** sebelum pola "password = NIM" (§8.5) terbawa ke lingkungan produksi.
6. **Cek dan bereskan perubahan `login` route** (`GET` vs `POST`) yang saat ini ada di working tree tapi belum di-commit (§4.6) — sama seperti dicatat di [RANCANGAN-AKUN-DOSEN.md](./RANCANGAN-AKUN-DOSEN.md), berlaku untuk seluruh role termasuk mahasiswa.
