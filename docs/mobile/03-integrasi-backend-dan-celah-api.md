# Integrasi Backend & Celah API untuk Aplikasi Mobile Civitas One

> **Per:** 18 Oktober 2026. **Target:** aplikasi Android (Flutter) untuk Mahasiswa, Dosen, dan Dosen PA. Dokumen ini adalah kontrak integrasi yang dipakai aplikasi, ditambah daftar endpoint/infrastruktur yang **belum ada** di backend. Semua fakta diverifikasi terhadap kode (`app/Modules/*`, `bootstrap/app.php`), bukan hanya dokumen rancangan.

## 1. Konvensi API yang wajib diikuti aplikasi

| Topik | Perilaku backend (terverifikasi) | Sumber kode |
|---|---|---|
| Base URL | Semua rute modul di bawah `/api/v1` (dimuat otomatis dari `app/Modules/*/Routes/api.php`), dibungkus middleware `tenant.resolve`. | `routes/api.php` |
| Autentikasi | Laravel Sanctum, `Authorization: Bearer <token>`. Token berlaku tanpa kedaluwarsa (abilities `*`), **tidak ada refresh token**; logout mencabut token. Login gagal → 422, terkunci → 429. | `Modules/Auth` |
| Login | `POST /login` dengan `email` **atau** `nim` + `password` (+ data perangkat opsional), tenant dari header `X-University-ID` atau field `university_code`. Respons: `{token, user{id,name,email,nim,must_change_password}}`. | `AuthController@login` |
| Konteks pengguna | `GET /me` → user + `roles[]` + `permissions[]` saja (tanpa data mahasiswa/dosen/universitas). Data mahasiswa dari `GET /student/profile`; dosen dari `GET /lecturer-profile`; profil SDM dari `GET /me/hr/profile`. | `AuthController@me` |
| Penentuan peran | Mahasiswa = role `student`; Dosen = permission `lecturer_profile.read` (role `lecturer`); Dosen PA = permission `krs_advising.read` (role `academic_advisor` atau dosen). Pola sama dengan portal web. | seeder `OrganizationalRoleSeeder`, `frontend/src/hooks/useIsStudent.ts` |
| Tenant | `ResolveUniversityMiddleware`: header `X-University-ID` → domain host → keanggotaan default user. Rute KEU & SDM memakai `tenant.access`. Aplikasi mengirim `X-University-ID` bila diketahui (build per universitas lewat konfigurasi), selain itu backend memakai keanggotaan default. | `bootstrap/app.php`, `app/Support/Tenancy` |
| Envelope | Sukses `{success:true, message, data, meta?}`; paginasi `meta{current_page, per_page, total, last_page}`; galat `{success:false, message, errors}`. Catatan: PHP dapat mengirim `meta`/`errors` kosong sebagai `[]` (bukan objek) — klien harus toleran. | `app/Support/Http` |
| Daftar & filter | `filter[kolom]=`, `search=`, `sort=`, `per_page` ≤ 100. Beberapa daftar tidak berpaginasi (materi, tugas, semester, kalender, `me/hr/*` maks 100). | `ListQuery` |
| Galat bisnis | 409 dengan `errors.code`, mis. `SCHEDULE_CONFLICT`, `ACADEMIC_BLOCKED` (cekal; hanya ditegakkan pada KRS & ujian), `CHANNEL_NOT_CONNECTED` (Xendit belum dikonfigurasi). 403 izin, 404, 422 validasi (`errors` per field), 429 throttle. Pesan galat backend berbahasa Indonesia. | C2/C4 |
| Bahasa | Backend tidak membaca `Accept-Language`; aplikasi tetap mengirimnya (siap untuk masa depan) dan menerjemahkan label UI sendiri. Label status dari server (`*_label`) berbahasa Indonesia. | C1 |
| Uang & angka | Nominal KEU berupa string desimal 2 digit (`"1500000.00"`); skor/nilai juga string desimal (`"85.00"`). | C4, C2 |
| Unggah berkas | Dua langkah: `POST /file-uploads` (multipart field `file`; pdf, jpg, png, doc, docx, xls, xlsx; default maks 10 MB) → kirim `id` berkas ke endpoint domain (bukti transfer, lampiran tugas, lampiran cuti). | `FileManagement` |
| Unduh berkas/PDF | KHS, KRS, transkrip, surat, kuitansi, hasil ujian: endpoint GET mengembalikan PDF dan **wajib** header Bearer (tidak ada signed URL). Aplikasi mengunduh lewat klien HTTP terautentikasi lalu membuka berkas lokal. | C2, C4 |
| Idempotensi | `POST /student/finance/payments/online` mewajibkan header `Idempotency-Key`. | C4 |
| Notifikasi | Hanya kanal `database` (in-app) dan `mail`. `GET /notifications` (meta `unread_count`), `GET /notifications/unread-count`, `PATCH /notifications/{id}/read`, `POST /notifications/read-all`. Field tautan berisi path web (`/portal/...`), bukan deep link. | `Modules/Notification` |

## 2. Keputusan integrasi aplikasi

1. **Build per universitas.** Kode/ID universitas diberikan saat build (`--dart-define`), dikirim sebagai `university_code` saat login dan `X-University-ID` bila ID tersedia. Pemilih universitas tidak dibuat karena tidak ada endpoint publik penemuan universitas.
2. **Tanpa data tiruan.** Fitur yang backend-nya belum ada ditampilkan sebagai entri berlabel "Menunggu backend" atau tidak ditampilkan; tidak ada data contoh di aplikasi produksi.
3. **Cache baca offline.** Respons GET untuk data milik pengguna (jadwal, nilai, KHS, tagihan, pengumuman) di-cache lokal dan ditampilkan dengan penanda "data terakhir" saat offline; aksi tulis memerlukan koneksi.
4. **Pemetaan tautan notifikasi.** Path web dari notifikasi dipetakan ke rute aplikasi di sisi klien.

## 3. Inventaris endpoint yang dapat dipakai Mahasiswa / Dosen / Dosen PA

Dihasilkan dari audit kode (C1–C9). Peran = siapa yang lolos middleware izin. Detail kontrak (field request/response) ada di catatan audit dan diimplementasikan di model Dart masing-masing fitur.

| Domain | Metode | Path | Peran | Auth/izin | Fungsi |
|---|---|---|---|---|---|
| `academic_records` | GET | `/api/v1/class-sections` | Dosen | sanctum + permission:classes.read | Class section list |
| `academic_records` | GET | `/api/v1/class-sections/{id}` | Dosen | sanctum + permission:classes.read + policy view | Class detail |
| `academic_records` | GET | `/api/v1/courses` | Dosen | permission:courses.read | Courses |
| `academic_records` | GET | `/api/v1/courses/{id}` | Dosen | permission:courses.read | Course detail |
| `academic_records` | GET | `/api/v1/courses/{id}/prerequisites` | Dosen | permission:courses.read | Prerequisites |
| `academic_records` | GET | `/api/v1/krs-items` | Dosen | permission:krs.read | Roster source (krs_item ids) |
| `academic_records` | GET | `/api/v1/student/academic-history` | Mahasiswa | sanctum + permission:student_portal.read | Timeline of admission/terms/status/graduation |
| `academic_records` | GET | `/api/v1/student/academic-summary` | Mahasiswa | sanctum + permission:student_portal.read | Semester, IPK, SKS, advisor, curriculum, max credits |
| `academic_records` | GET | `/api/v1/student/documents` | Mahasiswa | sanctum + permission:student_portal.read | List downloadable PDFs |
| `academic_records` | GET | `/api/v1/student/documents/khs/{academicTerm}` | Mahasiswa | sanctum + permission:student_portal.read | KHS PDF download |
| `academic_records` | GET | `/api/v1/student/documents/khs/{termId}` | Mahasiswa | sanctum + student_portal.read | KHS PDF |
| `academic_records` | GET | `/api/v1/student/documents/krs` | Mahasiswa | sanctum + permission:student_portal.read | KRS PDF download |
| `academic_records` | GET | `/api/v1/student/documents/transcript` | Mahasiswa | sanctum + permission:student_portal.read | Transcript PDF download |
| `academic_records` | GET | `/api/v1/student/grades` | Mahasiswa | sanctum + permission:student_portal.read | Grades by term |
| `academic_records` | GET | `/api/v1/student/khs` | Mahasiswa | sanctum + permission:student_portal.read | KHS for one term |
| `academic_records` | GET | `/api/v1/student/transcript` | Mahasiswa | sanctum + permission:student_portal.read | Transcript (best attempts) |
| `academic_records` | GET | `/api/v1/students` | Dosen | permission:students.read | Students / class roster via filter[class_section_id] |
| `academic_records` | GET | `/api/v1/students/{id}` | Dosen | permission:students.read | Student detail |
| `academic_records` | GET | `/api/v1/students/{id}/transcript` | Dosen PA, Dosen | permission:students.read | Transcript summary |
| `academic_records` | GET | `/api/v1/study-programs` | Dosen | permission:study_programs.read | Study programs |
| `announcements_notifications` | GET | `/api/v1/notification-preferences` | Mahasiswa, Dosen, Dosen PA | sanctum | List own channel prefs |
| `announcements_notifications` | GET | `/api/v1/notifications` | Mahasiswa, Dosen, Dosen PA | sanctum (no permission) | Own in-app notification center |
| `announcements_notifications` | GET | `/api/v1/notifications/unread-count` | Mahasiswa, Dosen, Dosen PA | sanctum | Badge count |
| `announcements_notifications` | GET | `/api/v1/student/announcements` | Mahasiswa | sanctum + permission:student_portal.read | Announcement feed (paginated) |
| `announcements_notifications` | GET | `/api/v1/student/announcements/{announcement}` | Mahasiswa | sanctum + permission:student_portal.read + viewAsStudent | Announcement detail |
| `announcements_notifications` | GET | `/api/v1/student/announcements/{id}` | Mahasiswa | sanctum + student_portal.read | Announcement detail (service only) |
| `announcements_notifications` | PATCH | `/api/v1/notifications/{id}/read` | Mahasiswa, Dosen, Dosen PA | sanctum (any role) | Mark read |
| `announcements_notifications` | PATCH | `/api/v1/notifications/{notification}/read` | Mahasiswa, Dosen, Dosen PA | sanctum | Mark one read |
| `announcements_notifications` | POST | `/api/v1/notification-preferences` | Mahasiswa, Dosen, Dosen PA | sanctum | Upsert pref |
| `announcements_notifications` | POST | `/api/v1/notifications/read-all` | Mahasiswa, Dosen, Dosen PA | sanctum | Mark all read |
| `announcements_notifications` | POST | `/api/v1/student/announcements/{announcement}/read` | Mahasiswa | sanctum + permission:student_portal.read | Mark read |
| `announcements_notifications` | POST | `/api/v1/student/announcements/{id}/read` | Mahasiswa | sanctum + student_portal.read | Mark read |
| `attendance` | GET | `/api/v1/attendances` | Dosen | permission:attendance.read | Attendance records |
| `attendance` | GET | `/api/v1/student/attendance` | Mahasiswa | sanctum + permission:student_portal.read | Attendance summary per course |
| `attendance` | GET | `/api/v1/student/attendance/{krsItemId}` | Mahasiswa | sanctum + student_portal.read | Meeting-level attendance |
| `attendance` | GET | `/api/v1/student/attendance/{krsItem}` | Mahasiswa | sanctum + permission:student_portal.read + KrsItemPolicy@viewOwn | Per-meeting attendance for a course |
| `attendance` | POST | `/api/v1/class-sections/{cs}/attendances` | Dosen | permission:attendance.create\|update + policy record | Batch record attendance for a meeting (upsert) |
| `attendance` | POST | `/api/v1/class-sections/{id}/attendances` | Dosen | sanctum + attendance.create\|update | Record attendance batch |
| `auth_account` | DELETE | `/api/v1/sessions/{id}` | Mahasiswa, Dosen, Dosen PA | sanctum | Revoke session |
| `auth_account` | DELETE | `/api/v1/sessions/{session}` | Mahasiswa, Dosen, Dosen PA | auth:sanctum + owner policy | Revoke a session (and its token) |
| `auth_account` | GET | `/api/v1/devices` | Mahasiswa, Dosen, Dosen PA | auth:sanctum | List own devices |
| `auth_account` | GET | `/api/v1/me` | Mahasiswa, Dosen, Dosen PA | auth:sanctum | Current user, roles, permissions |
| `auth_account` | GET | `/api/v1/sessions` | Mahasiswa, Dosen, Dosen PA | auth:sanctum | List own login sessions |
| `auth_account` | GET | `/api/v1/student/profile` | Mahasiswa | sanctum + permission:student_portal.read | Own profile |
| `auth_account` | PATCH | `/api/v1/student/profile` | Mahasiswa | sanctum + permission:student_portal.update | Update phone/address/photo |
| `auth_account` | POST | `/api/v1/change-password` | Mahasiswa, Dosen, Dosen PA | auth:sanctum | Change password; clears must_change_password |
| `auth_account` | POST | `/api/v1/forgot-password` | Mahasiswa, Dosen, Dosen PA | public | Email reset link |
| `auth_account` | POST | `/api/v1/login` | Mahasiswa, Dosen, Dosen PA | public (tenant via X-University-ID/domain/university_code for NIM) | Login by email or NIM |
| `auth_account` | POST | `/api/v1/logout` | Mahasiswa, Dosen, Dosen PA | auth:sanctum | Revoke current token |
| `auth_account` | POST | `/api/v1/logout-all` | Mahasiswa, Dosen, Dosen PA | auth:sanctum | Revoke all tokens |
| `auth_account` | POST | `/api/v1/reset-password` | Mahasiswa, Dosen, Dosen PA | public | Reset with token |
| `dashboard_home` | GET | `/api/v1/dashboard` | Mahasiswa, Dosen, Dosen PA | sanctum (tenant required) | Permission-filtered institution stats |
| `dashboard_home` | GET | `/api/v1/student/dashboard` | Mahasiswa | sanctum + permission:student_portal.read | Aggregated home: profile, academic, krs, today schedule, next class, attendance, upcoming exams, due assignments, recent grades, announcemen |
| `exam_cbt` | DELETE | `/api/v1/exam-questions/{id}` | Dosen | permission:exams.update\|delete | Delete question |
| `exam_cbt` | DELETE | `/api/v1/exams/{id}` | Dosen | permission:exams.delete | Delete exam |
| `exam_cbt` | DELETE | `/api/v1/question-bank/{id}` | Dosen | permission:question_bank.delete | Delete bank item |
| `exam_cbt` | GET | `/api/v1/exam-attempts/{attemptId}/violations` | Dosen | sanctum + exam_attempts.read | Violation timeline |
| `exam_cbt` | GET | `/api/v1/exam-attempts/{id}/violations` | Dosen | permission:exam_attempts.read | Attempt violation timeline |
| `exam_cbt` | GET | `/api/v1/exams` | Dosen | permission:exams.read | Exams list |
| `exam_cbt` | GET | `/api/v1/exams/{id}` | Dosen | permission:exams.read | Exam detail |
| `exam_cbt` | GET | `/api/v1/exams/{id}/download` | Dosen | permission:exams.read | Exam PDF |
| `exam_cbt` | GET | `/api/v1/exams/{id}/grade-ranges` | Dosen | permission:exams.read | Grade ranges |
| `exam_cbt` | GET | `/api/v1/exams/{id}/participants` | Dosen | permission:exam_attempts.read | Participant status |
| `exam_cbt` | GET | `/api/v1/exams/{id}/questions` | Dosen | permission:exams.read | Questions |
| `exam_cbt` | GET | `/api/v1/exams/{id}/recap` | Dosen | permission:exams.read | Recap |
| `exam_cbt` | GET | `/api/v1/exams/{id}/recap/export` | Dosen | permission:exams.read | Export recap |
| `exam_cbt` | GET | `/api/v1/exams/{id}/violations/recent` | Dosen | permission:exam_attempts.read | Poll violations |
| `exam_cbt` | GET | `/api/v1/public/exam-attempts/{sessionToken}` | Mahasiswa | sanctum + session token in URL | Attempt resource + student{name,nim} |
| `exam_cbt` | GET | `/api/v1/public/exam-attempts/{sessionToken}/result` | Mahasiswa | sanctum + session token | Result |
| `exam_cbt` | GET | `/api/v1/public/exam-attempts/{sessionToken}/result/pdf` | Mahasiswa | sanctum + session token | Result PDF |
| `exam_cbt` | GET | `/api/v1/public/exams/{accessToken}` | Mahasiswa | sanctum + none (tenant.resolve only) | Public exam info by link/QR token |
| `exam_cbt` | GET | `/api/v1/question-bank` | Dosen | permission:question_bank.read | Question bank list |
| `exam_cbt` | GET | `/api/v1/question-bank/{id}` | Dosen | permission:question_bank.read | Bank item |
| `exam_cbt` | GET | `/api/v1/student/exam-attempts/{attemptId}` | Mahasiswa | sanctum + exam_participation.read | Reload attempt |
| `exam_cbt` | GET | `/api/v1/student/exam-attempts/{attemptId}/result` | Mahasiswa | sanctum + exam_participation.read | Result with corrections |
| `exam_cbt` | GET | `/api/v1/student/exam-attempts/{attemptId}/result/pdf` | Mahasiswa | sanctum + exam_participation.read | Result PDF |
| `exam_cbt` | GET | `/api/v1/student/exam-attempts/{examAttempt}` | Mahasiswa | sanctum + permission:exam_participation.read + recordOwn | Reload attempt (lazy auto-finalise on expiry) |
| `exam_cbt` | GET | `/api/v1/student/exam-attempts/{examAttempt}/result` | Mahasiswa | sanctum + permission:exam_participation.read + recordOwn | Detailed result with answer key |
| `exam_cbt` | GET | `/api/v1/student/exam-attempts/{examAttempt}/result/pdf` | Mahasiswa | sanctum + permission:exam_participation.read + recordOwn | Result PDF download |
| `exam_cbt` | GET | `/api/v1/student/exams` | Mahasiswa | sanctum + permission:exam_participation.read | List published exams of enrolled classes with personal status |
| `exam_cbt` | GET | `/api/v1/student/exams/{examId}` | Mahasiswa | sanctum + exam_participation.read | Exam status |
| `exam_cbt` | GET | `/api/v1/student/exams/{exam}` | Mahasiswa | sanctum + permission:exam_participation.read | Exam detail |
| `exam_cbt` | GET/POST | `/api/v1/exams/{exam}/questions` | Dosen | permission:exams.read / exams.create,exams.update | Question bank list/add |
| `exam_cbt` | GET/PUT/DELETE | `/api/v1/exams/{exam}` | Dosen | permission:exams.read/update/delete | Show/update/delete exam |
| `exam_cbt` | PATCH | `/api/v1/exam-attempts/{id}/submit` | Dosen | permission:exam_attempts.update | Proctor submits attempt |
| `exam_cbt` | PATCH | `/api/v1/exams/{exam}/publish` | Dosen | permission:exams.publish | Publish exam |
| `exam_cbt` | PATCH | `/api/v1/exams/{id}/access-link` | Dosen | permission:exams.update | Generate public access token |
| `exam_cbt` | PATCH | `/api/v1/exams/{id}/publish` | Dosen | permission:exams.publish | Publish exam |
| `exam_cbt` | PATCH | `/api/v1/public/exam-attempts/{sessionToken}/submit` | Mahasiswa | sanctum + session token | Submit |
| `exam_cbt` | PATCH | `/api/v1/student/exam-attempts/{attemptId}/submit` | Mahasiswa | sanctum + exam_participation.update | Submit attempt |
| `exam_cbt` | PATCH | `/api/v1/student/exam-attempts/{examAttempt}/submit` | Mahasiswa | sanctum + permission:exam_participation.update + recordOwn | Submit and auto-score |
| `exam_cbt` | POST | `/api/v1/exams` | Dosen | permission:exams.create + policy manage | Create exam |
| `exam_cbt` | POST | `/api/v1/exams/{id}/questions` | Dosen | permission:exams.create\|update | Add question |
| `exam_cbt` | POST | `/api/v1/exams/{id}/questions/apply-bank` | Dosen | permission:exams.create\|update | Copy bank questions |
| `exam_cbt` | POST | `/api/v1/krs-items/{krs}/exams/{exam}/attempt` | Dosen | permission:exam_attempts.create | Proctor starts attempt on behalf of student |
| `exam_cbt` | POST | `/api/v1/public/exam-attempts/{sessionToken}/violations` | Mahasiswa | sanctum + session token | Violation |
| `exam_cbt` | POST | `/api/v1/public/exams/{accessToken}/access` | Mahasiswa | sanctum + throttle:10,1 | Start public attempt by NIM, returns session_token |
| `exam_cbt` | POST | `/api/v1/question-bank` | Dosen | permission:question_bank.create | Create bank item |
| `exam_cbt` | POST | `/api/v1/student/exam-attempts/{attemptId}/violations` | Mahasiswa | sanctum + exam_participation.update | Report anti-cheat violation (-1 point each) |
| `exam_cbt` | POST | `/api/v1/student/exam-attempts/{examAttempt}/violations` | Mahasiswa | sanctum + permission:exam_participation.update + recordOwn | Report anti-cheat violation (-1 point each) |
| `exam_cbt` | POST | `/api/v1/student/exams/{examId}/start` | Mahasiswa | sanctum + exam_participation.create | Start/resume attempt (idempotent) |
| `exam_cbt` | POST | `/api/v1/student/exams/{exam}/start` | Mahasiswa | sanctum + permission:exam_participation.create + ExamParticipationPolicy@startOwn | Start or resume attempt |
| `exam_cbt` | PUT | `/api/v1/exam-attempts/{id}/answer` | Dosen | permission:exam_attempts.update | Proctor records answer |
| `exam_cbt` | PUT | `/api/v1/exam-questions/{id}` | Dosen | permission:exams.update | Update question |
| `exam_cbt` | PUT | `/api/v1/exams/{id}` | Dosen | permission:exams.update | Update exam |
| `exam_cbt` | PUT | `/api/v1/exams/{id}/grade-ranges` | Dosen | permission:exams.update | Set grade ranges |
| `exam_cbt` | PUT | `/api/v1/public/exam-attempts/{sessionToken}/answer` | Mahasiswa | sanctum + session token | Autosave answer |
| `exam_cbt` | PUT | `/api/v1/question-bank/{id}` | Dosen | permission:question_bank.update | Update bank item |
| `exam_cbt` | PUT | `/api/v1/student/exam-attempts/{attemptId}/answer` | Mahasiswa | sanctum + exam_participation.update | Autosave answer |
| `exam_cbt` | PUT | `/api/v1/student/exam-attempts/{examAttempt}/answer` | Mahasiswa | sanctum + permission:exam_participation.update + recordOwn | Autosave one answer |
| `exam_cbt` | PUT/DELETE | `/api/v1/exam-questions/{examQuestion}` | Dosen | permission:exams.update | Edit/delete question |
| `finance` | GET | `/api/v1/student/finance/awards` | Mahasiswa | student_finance.read | Scholarships/discounts (active, revoked) |
| `finance` | GET | `/api/v1/student/finance/channels` | Mahasiswa | student_finance.read | Payment channels + manual transfer info |
| `finance` | GET | `/api/v1/student/finance/deposit` | Mahasiswa | student_finance.read | Deposit balance, ledger, refunds |
| `finance` | GET | `/api/v1/student/finance/invoices` | Mahasiswa | sanctum + tenant.access + student_finance.read | Paginated own invoices (non-draft) |
| `finance` | GET | `/api/v1/student/finance/invoices/{id}` | Mahasiswa | sanctum + student_finance.read\|create | Invoice detail + pending transactions |
| `finance` | GET | `/api/v1/student/finance/invoices/{id}/installment-plans` | Mahasiswa | sanctum + student_finance.read\|create | Plans |
| `finance` | GET | `/api/v1/student/finance/invoices/{invoice}` | Mahasiswa | sanctum + tenant.access + student_finance.read | Invoice detail with items, installments, payments, penalty invoices, pending transactions |
| `finance` | GET | `/api/v1/student/finance/invoices/{invoice}/installment-plans` | Mahasiswa | student_finance.read | Available installment schemes |
| `finance` | GET | `/api/v1/student/finance/statement` | Mahasiswa | student_finance.read | Receivable ledger |
| `finance` | GET | `/api/v1/student/finance/summary` | Mahasiswa | sanctum + tenant.access + permission:student_finance.read | Finance home: outstanding, arrears, next due, deposit, cekal services, clearance, blocks |
| `finance` | GET | `/api/v1/student/finance/transactions` | Mahasiswa | student_finance.read | Paginated payment transactions |
| `finance` | GET | `/api/v1/student/finance/transactions/{id}/receipt` | Mahasiswa | sanctum + student_finance.read\|create | Receipt PDF |
| `finance` | GET | `/api/v1/student/finance/transactions/{transaction}/receipt` | Mahasiswa | student_finance.read | Receipt PDF |
| `finance` | GET | `/api/v1/student/finance/ukt` | Mahasiswa | student_finance.read | UKT group, options, change requests |
| `finance` | POST | `/api/v1/student/finance/blocks/{blockId}/dispensations` | Mahasiswa | sanctum + student_finance.read\|create | Dispensation request |
| `finance` | POST | `/api/v1/student/finance/blocks/{block}/dispensations` | Mahasiswa | student_finance.create | Request cekal dispensation |
| `finance` | POST | `/api/v1/student/finance/invoices/{id}/deposit` | Mahasiswa | sanctum + student_finance.read\|create | Pay with deposit |
| `finance` | POST | `/api/v1/student/finance/invoices/{id}/installments` | Mahasiswa | sanctum + student_finance.read\|create | Request plan |
| `finance` | POST | `/api/v1/student/finance/invoices/{id}/voucher` | Mahasiswa | sanctum + student_finance.read\|create | Apply voucher |
| `finance` | POST | `/api/v1/student/finance/invoices/{invoice}/deposit` | Mahasiswa | student_finance.create | Pay invoice/installment from deposit |
| `finance` | POST | `/api/v1/student/finance/invoices/{invoice}/installments` | Mahasiswa | student_finance.create | Request installment schedule |
| `finance` | POST | `/api/v1/student/finance/invoices/{invoice}/voucher` | Mahasiswa | student_finance.create, throttle 10/min | Redeem voucher code |
| `finance` | POST | `/api/v1/student/finance/payments/manual` | Mahasiswa | student_finance.create, throttle 20/min | Create manual transfer instruction (unique code) |
| `finance` | POST | `/api/v1/student/finance/payments/online` | Mahasiswa | student_finance.create, throttle 20/min | Create Xendit invoice for invoice/installment |
| `finance` | POST | `/api/v1/student/finance/refunds` | Mahasiswa | student_finance.create | Request deposit refund |
| `finance` | POST | `/api/v1/student/finance/transactions/{id}/cancel` | Mahasiswa | sanctum + student_finance.read\|create | Cancel pending |
| `finance` | POST | `/api/v1/student/finance/transactions/{id}/proof` | Mahasiswa | sanctum + student_finance.read\|create | Transfer proof |
| `finance` | POST | `/api/v1/student/finance/transactions/{id}/refresh` | Mahasiswa | sanctum + student_finance.read\|create | Refresh provider status |
| `finance` | POST | `/api/v1/student/finance/transactions/{transaction}/cancel` | Mahasiswa | student_finance.create | Cancel unpaid transaction |
| `finance` | POST | `/api/v1/student/finance/transactions/{transaction}/proof` | Mahasiswa | student_finance.create | Submit transfer proof |
| `finance` | POST | `/api/v1/student/finance/transactions/{transaction}/refresh` | Mahasiswa | student_finance.create, throttle 30/min | Recheck Xendit status after returning from checkout |
| `finance` | POST | `/api/v1/student/finance/ukt-requests` | Mahasiswa | student_finance.create | Request UKT review |
| `grading_obe` | GET | `/api/v1/grades` | Dosen | permission:grades.read | Grades list |
| `grading_obe` | PUT | `/api/v1/krs-items/{id}/grade` | Dosen | permission:grades.create\|update + policy manage | Upsert final grade for krs item |
| `grading_obe` | PUT | `/api/v1/krs-items/{krsItemId}/grade` | Dosen | sanctum + grades.create\|update | Input grade |
| `krs_registration` | DELETE | `/api/v1/student/krs/items/{krsItemId}` | Mahasiswa | sanctum + krs_self_service.update | Remove draft item |
| `krs_registration` | DELETE | `/api/v1/student/krs/items/{krsItem}` | Mahasiswa | sanctum + permission:krs_self_service.update + KrsItemPolicy@viewOwn | Remove draft item |
| `krs_registration` | GET | `/api/v1/krs-submissions` | Dosen PA, Dosen | permission:krs.approve\|krs_advising.read | Advisor approval queue (advisees only) |
| `krs_registration` | GET | `/api/v1/krs-submissions/{id}` | Dosen PA, Dosen | permission:krs.approve\|krs_advising.read + advisee check | Submission detail |
| `krs_registration` | GET | `/api/v1/student/krs` | Mahasiswa | sanctum + permission:krs_self_service.read | KRS overview of current term |
| `krs_registration` | GET | `/api/v1/student/krs/history` | Mahasiswa | sanctum + permission:krs_self_service.read | KRS history per term |
| `krs_registration` | GET | `/api/v1/student/krs/offerings` | Mahasiswa | sanctum + permission:krs_self_service.read | Offered classes with prerequisites, seats, conflicts |
| `krs_registration` | POST | `/api/v1/krs-submissions/{id}/approve` | Dosen PA, Dosen | permission:krs.approve\|krs_advising.approve + advisee check | Approve KRS |
| `krs_registration` | POST | `/api/v1/krs-submissions/{id}/reject` | Dosen PA, Dosen | permission:krs.approve\|krs_advising.approve + advisee check | Reject KRS |
| `krs_registration` | POST | `/api/v1/student/krs/cancel` | Mahasiswa | sanctum + permission:krs_self_service.update | Withdraw submitted KRS (within window) |
| `krs_registration` | POST | `/api/v1/student/krs/items` | Mahasiswa | sanctum + permission:krs_self_service.create | Add class to KRS draft |
| `krs_registration` | POST | `/api/v1/student/krs/submit` | Mahasiswa | sanctum + permission:krs_self_service.update | Submit KRS to advisor |
| `lecturer_hr` | GET | `/api/v1/hr/options` | Dosen, Dosen PA | sanctum + tenant.access + permission:hr_*.read or hr_self_service.read | HR enum/options lookup for forms |
| `lecturer_hr` | GET | `/api/v1/lecturer-profile` | Dosen | permission:lecturer_profile.read | Own lecturer profile |
| `lecturer_hr` | GET | `/api/v1/me/hr/leave-requests` | Dosen, Dosen PA | sanctum + tenant.access + permission:hr_self_service.read | Own leave list (max 100) |
| `lecturer_hr` | GET | `/api/v1/me/hr/profile` | Dosen, Dosen PA | sanctum + tenant.access + permission:hr_self_service.read | Own HR profile + annual leave quota |
| `lecturer_hr` | GET | `/api/v1/me/hr/requests` | Dosen, Dosen PA | sanctum + tenant.access + permission:hr_self_service.read | Own HR requests list (max 100) |
| `lecturer_hr` | PATCH | `/api/v1/me/hr/leave-requests/{id}/cancel` | Dosen, Dosen PA | sanctum + tenant.access + permission:hr_self_service.update | Cancel draft or pending leave |
| `lecturer_hr` | PATCH | `/api/v1/me/hr/requests/{id}/cancel` | Dosen, Dosen PA | sanctum + tenant.access + permission:hr_self_service.update | Cancel pending/returned request |
| `lecturer_hr` | POST | `/api/v1/me/hr/leave-requests` | Dosen, Dosen PA | sanctum + tenant.access + permission:hr_self_service.create | Create (and by default submit) leave |
| `lecturer_hr` | POST | `/api/v1/me/hr/leave-requests/{id}/submit` | Dosen, Dosen PA | sanctum + tenant.access + permission:hr_self_service.update | Submit draft leave |
| `lecturer_hr` | POST | `/api/v1/me/hr/requests` | Dosen, Dosen PA | sanctum + tenant.access + permission:hr_self_service.create | Submit HR request (transfer\|data_change\|promotion\|document\|other) |
| `lecturer_hr` | PUT | `/api/v1/lecturer-profile` | Dosen | permission:lecturer_profile.update | Update own phone |
| `letters_services` | GET | `/api/v1/student/requests` | Mahasiswa | sanctum + permission:student_requests.read | Own requests (paginated) |
| `letters_services` | GET | `/api/v1/student/requests/options` | Mahasiswa | sanctum + permission:student_requests.read | Letter types and data-change fields |
| `letters_services` | GET | `/api/v1/student/requests/{id}` | Mahasiswa | sanctum + student_requests.read | Request detail |
| `letters_services` | GET | `/api/v1/student/requests/{id}/letter` | Mahasiswa | sanctum + student_requests.read | Approved letter PDF |
| `letters_services` | GET | `/api/v1/student/requests/{studentRequest}` | Mahasiswa | sanctum + permission:student_requests.read + viewOwn | Request detail with history |
| `letters_services` | GET | `/api/v1/student/requests/{studentRequest}/letter` | Mahasiswa | sanctum + permission:student_requests.read + viewOwn | Approved letter PDF |
| `letters_services` | POST | `/api/v1/student/requests` | Mahasiswa | sanctum + permission:student_requests.create | Create (and optionally submit) request |
| `letters_services` | POST | `/api/v1/student/requests/{id}/cancel` | Mahasiswa | sanctum + student_requests.update | Cancel draft/submitted |
| `letters_services` | POST | `/api/v1/student/requests/{id}/submit` | Mahasiswa | sanctum + student_requests.update | Submit draft |
| `letters_services` | POST | `/api/v1/student/requests/{studentRequest}/cancel` | Mahasiswa | sanctum + permission:student_requests.update + viewOwn | Cancel draft/submitted request |
| `letters_services` | POST | `/api/v1/student/requests/{studentRequest}/submit` | Mahasiswa | sanctum + permission:student_requests.update + viewOwn | Submit draft for review |
| `letters_services` | PUT | `/api/v1/student/requests/{id}` | Mahasiswa | sanctum + student_requests.update | Edit draft (no web UI) |
| `letters_services` | PUT | `/api/v1/student/requests/{studentRequest}` | Mahasiswa | sanctum + permission:student_requests.update + viewOwn | Edit draft |
| `lms` | DELETE | `/api/v1/assignments/{id}` | Dosen | permission:assignments.delete + canManage | Delete assignment |
| `lms` | DELETE | `/api/v1/course-materials/{id}` | Dosen | permission:course_materials.delete + canManage | Delete material |
| `lms` | GET | `/api/v1/assignments/{id}/submissions` | Dosen | permission:assignment_submissions.read + canManage | Roster with submissions |
| `lms` | GET | `/api/v1/class-sections/{cs}/assignments` | Dosen | permission:assignments.read + canManage | List assignments |
| `lms` | GET | `/api/v1/class-sections/{cs}/materials` | Dosen | permission:course_materials.read + canManage | List materials |
| `lms` | GET | `/api/v1/class-sections/{id}/assignments` | Dosen | sanctum + assignments.read | Assignments |
| `lms` | GET | `/api/v1/class-sections/{id}/materials` | Dosen | sanctum + course_materials.read | Materials |
| `lms` | GET | `/api/v1/student/assignments` | Mahasiswa | sanctum + permission:student_portal.read | Assignments with per-student status |
| `lms` | GET | `/api/v1/student/assignments/{assignment}` | Mahasiswa | sanctum + permission:student_portal.read + AssignmentPolicy@viewAsStudent | Assignment detail |
| `lms` | GET | `/api/v1/student/assignments/{id}` | Mahasiswa | sanctum + student_portal.read | Assignment detail |
| `lms` | GET | `/api/v1/student/courses` | Mahasiswa | sanctum + permission:student_portal.read | Current-term enrolled courses with counts |
| `lms` | GET | `/api/v1/student/courses/{classSectionId}` | Mahasiswa | sanctum + student_portal.read | Course detail |
| `lms` | GET | `/api/v1/student/courses/{classSection}` | Mahasiswa | sanctum + permission:student_portal.read + ClassSectionPolicy@viewAsStudent | Course detail: meetings/materials, assignments, exams, attendance |
| `lms` | GET | `/api/v1/student/materials` | Mahasiswa | sanctum + permission:student_portal.read | All published materials grouped by course |
| `lms` | POST | `/api/v1/class-sections/{cs}/assignments` | Dosen | permission:assignments.create + canManage | Create assignment |
| `lms` | POST | `/api/v1/class-sections/{cs}/materials` | Dosen | permission:course_materials.create + canManage | Create material |
| `lms` | POST | `/api/v1/class-sections/{id}/assignments` | Dosen | sanctum + assignments.create | Create assignment |
| `lms` | POST | `/api/v1/class-sections/{id}/materials` | Dosen | sanctum + course_materials.create | Create material |
| `lms` | POST | `/api/v1/student/assignments/{assignment}/submission` | Mahasiswa | sanctum + permission:assignment_submissions.create + AssignmentPolicy@submit | Submit assignment (file id from /file-uploads and/or notes) |
| `lms` | POST | `/api/v1/student/assignments/{id}/submission` | Mahasiswa | sanctum + assignment_submissions.create | Submit/resubmit assignment |
| `lms` | PUT | `/api/v1/assignment-submissions/{id}/grade` | Dosen | permission:assignment_submissions.update + canManage | Grade submission |
| `lms` | PUT | `/api/v1/assignments/{id}` | Dosen | permission:assignments.update + canManage | Update assignment |
| `lms` | PUT | `/api/v1/course-materials/{id}` | Dosen | permission:course_materials.update + canManage | Update material |
| `other` | GET | `/api/v1/approval-requests` | Mahasiswa, Dosen, Dosen PA | sanctum | Own approval requests (all tenant for approval_requests.read) |
| `other` | GET | `/api/v1/approval-requests/{approvalRequest}` | Mahasiswa, Dosen, Dosen PA | sanctum + requester\|current approver\|approval_requests.read | Detail with histories, can_act |
| `other` | GET | `/api/v1/file-uploads/{id}/download` | Mahasiswa, Dosen, Dosen PA | sanctum | Download blob |
| `other` | GET | `/api/v1/file-uploads/{id}/preview` | Mahasiswa, Dosen, Dosen PA | sanctum | Image blob |
| `other` | POST | `/api/v1/approval-requests/{approvalRequest}/resubmit` | Mahasiswa, Dosen, Dosen PA | sanctum + requester only | Resubmit returned request |
| `platform_mobile` | DELETE | `/api/v1/file-uploads/{fileUpload}` | Mahasiswa, Dosen, Dosen PA | sanctum + policy delete | Delete own unattached upload |
| `platform_mobile` | GET | `/api/v1/file-uploads/{fileUpload}` | Mahasiswa, Dosen, Dosen PA | sanctum + FileUploadPolicy@view | File metadata |
| `platform_mobile` | GET | `/api/v1/file-uploads/{fileUpload}/download` | Mahasiswa, Dosen, Dosen PA | sanctum + sanctum + FileUploadPolicy | Download file stream |
| `platform_mobile` | GET | `/api/v1/file-uploads/{fileUpload}/preview` | Mahasiswa, Dosen, Dosen PA | sanctum + sanctum + FileUploadPolicy | Inline preview pdf/png/jpg only |
| `platform_mobile` | GET | `/api/v1/tenant/profile` | Mahasiswa, Dosen, Dosen PA | auth:sanctum + tenant.access | University profile/branding |
| `platform_mobile` | GET | `/api/v1/tenant/settings` | Mahasiswa, Dosen, Dosen PA | auth:sanctum + tenant.access | Tenant settings |
| `platform_mobile` | POST | `/api/v1/file-uploads` | Mahasiswa, Dosen, Dosen PA | sanctum + sanctum only | Upload file (prereq for assignment submission, profile photo, request attachment) |
| `schedule_calendar` | GET | `/api/v1/academic-calendar-events` | Dosen | permission:academic_calendar.read | Academic calendar |
| `schedule_calendar` | GET | `/api/v1/academic-terms` | Dosen PA, Dosen | any of classes.read,krs.read,krs_advising.read,academic_calendar.read,course_materials.rea | Academic terms |
| `schedule_calendar` | GET | `/api/v1/student/calendar` | Mahasiswa | sanctum + permission:student_portal.read | Personal calendar events in range |
| `schedule_calendar` | GET | `/api/v1/student/schedule` | Mahasiswa | sanctum + permission:student_portal.read | Weekly slots, today, next class |
| `schedule_calendar` | GET | `/api/v1/teaching/classes` | Dosen | sanctum + permission:course_materials.read\|assignments.read | Lecturer's own classes (current term default) with schedules |

## 4. Celah backend per fitur

Kebutuhan yang belum dipenuhi backend, per baris matriks (baris *Ditunda* berprioritas P2/P3 tidak dicantumkan).

| Fitur (ID) | Celah backend |
|---|---|
| MOB-AUTH-01 Login dengan email + kata sandi (dosen / dosen PA) | Login hanya mengembalikan id,name,email,nim,must_change_password; tidak ada roles/permissions/profil (perlu /me + /lecturer-profile + /tenant/profile). |
| MOB-AUTH-01 Login dengan email + kata sandi (dosen / dosen PA) | Token tidak pernah kedaluwarsa dan tidak ada refresh (lihat MOB-AUTH-15). |
| MOB-AUTH-02 Login mahasiswa dengan NIM + kata sandi (kode universitas) | Tidak ada endpoint publik untuk resolusi university_code -> ULID/branding sebelum login (gap C1). |
| MOB-AUTH-02 Login mahasiswa dengan NIM + kata sandi (kode universitas) | Respons login tidak menyertakan universitas. |
| MOB-AUTH-03 Kirim identitas perangkat saat login (nama, platform, device | Tidak ada endpoint memperbarui/mengganti nama/mempercayai perangkat; hanya GET /devices. |
| MOB-AUTH-03 Kirim identitas perangkat saat login (nama, platform, device | Tidak ada kolom/endpoint push_token (lihat MOB-PLAT-14). |
| MOB-AUTH-05 Keluar (logout) dan penanganan sesi dicabut (401) | Tidak ada notifikasi realtime saat sesi dicabut; terdeteksi hanya pada request berikutnya. |
| MOB-AUTH-07 Minta tautan reset kata sandi lewat email | Hanya berbasis email; mahasiswa dengan NIM/email placeholder tidak dapat memulihkan sandi sendiri. |
| MOB-AUTH-07 Minta tautan reset kata sandi lewat email | Tautan menuju web; tidak ada App Link/kode OTP in-app. |
| MOB-AUTH-08 Atur ulang kata sandi dari tautan email (deep link ke aplika | Email reset menautkan ke FRONTEND_URL (web), bukan domain App Link; perlu endpoint/URL mobile atau halaman web yang meneruskan ke aplikasi (assetlinks.json). |
| MOB-AUTH-08 Atur ulang kata sandi dari tautan email (deep link ke aplika | Domain resmi App Link belum ditetapkan. |
| MOB-AUTH-09 Ubah kata sandi (sukarela) | Token lain tidak dicabut setelah ganti sandi (gap C1 'revoke-on-password-change'). |
| MOB-AUTH-10 Alur ganti sandi wajib saat login pertama (must_change_passw | must_change_password tidak ditegakkan server pada endpoint lain (klien dapat dilewati via API langsung). |
| MOB-AUTH-11 Lihat dan cabut sesi aktif / daftar perangkat | last_activity_at tidak akurat; tidak ada penamaan/kepercayaan perangkat; tidak ada notifikasi 'perangkat baru' ke aplikasi (ada notifikasi legacy NewDeviceDetected di inbox). |
| MOB-AUTH-13 Proteksi login: rate limit dan kebijakan kata sandi di UI | Kebijakan sandi berbeda per lingkungan; tidak ada endpoint yang mengekspos kebijakan sandi ke klien. |
| MOB-AUTH-14 Muat konteks peran dan izin (/me) untuk menentukan pengalama | /me tidak memuat profil mahasiswa/dosen, universitas, features/limits (D14: data.features); klien butuh panggilan tambahan (student/profile, lecturer-profile, tenant/profile). |
| MOB-AUTH-14 Muat konteks peran dan izin (/me) untuk menentukan pengalama | Tidak ada indikator advisee untuk dosen PA (diturunkan dari role academic_advisor / students.academic_advisor_id). |
| MOB-AUTH-15 Kedaluwarsa token / refresh token dan revoke-saat-ganti-sand | Konfigurasi expiry Sanctum, endpoint refresh/rotate (bila dipilih), revoke token lain pada change-password. |
| MOB-AUTH-16 Lihat profil mahasiswa (biodata, akademik) | Foto profil tidak memiliki URL siap pakai (hanya file_id + Bearer). |
| MOB-AUTH-18 Ajukan perubahan data pribadi (nama, email, tempat/tanggal l | Alasan perubahan tidak ada di payload kode (desain meminta 'Alasan'). |
| MOB-AUTH-19 Lihat profil dosen / dosen PA dan ubah nomor telepon | Resource tidak mengekspos nidk, serdos_number, academic_rank, expertise; tidak ada foto/avatar. |
| MOB-AUTH-21 Persetujuan data pribadi dan hak subjek data (UU PDP) | Endpoint consent/data-subject-request belum ada atau belum didefinisikan. |
| MOB-AUTH-22 Isolasi data per peran dan kepemilikan objek (sisi klien) | Pembatasan server atas kelas dosen bergantung flag academic.lecturer_ownership (OFF default) = risiko akses lintas kelas. |
| MOB-AUTH-24 Aktivasi akun mahasiswa baru hasil konversi PMB | Tautan aktivasi ke web, bukan mobile; email placeholder tak dapat menerima reset. |
| MOB-DASH-01 Beranda mahasiswa: ringkasan profil dan akademik (IPK, IPS,  | Pencarian foto hanya file_id (butuh fetch Bearer). |
| MOB-DASH-01 Beranda mahasiswa: ringkasan profil dan akademik (IPK, IPS,  | Tidak ada pagination/ETag; respons berat tanpa cache header. |
| MOB-DASH-02 Jadwal kuliah hari ini dan kelas berikutnya (beranda mahasis | Tidak ada pengecualian libur/kuliah pengganti dalam jadwal mingguan. |
| MOB-DASH-08 Peringatan beranda (alerts) dan pemetaan tautan web ke layar | Tautan berupa path web; tidak ada type/id terstruktur untuk deep link (gap C2 'Mobile deep links'). |
| MOB-DASH-09 Kartu ringkasan keuangan di beranda (tunggakan, jatuh tempo  | Ringkasan keuangan tidak ikut di /student/dashboard (panggilan terpisah dan butuh tenant.access). |
| MOB-DASH-10 Badge notifikasi belum dibaca di beranda dan navigasi (semua | Notifikasi legacy tidak memiliki event_key/message; link adalah path web. |
| MOB-DASH-11 Widget tambahan beranda mahasiswa: pengajuan surat, jadwal b | Tidak ada endpoint jadwal bimbingan akademik (/advising/* hanya di rancangan). |
| MOB-DASH-13 Beranda dosen: jadwal mengajar hari ini dan daftar kelas dia | Tidak ada endpoint ringkasan beranda dosen; jadwal diturunkan di klien dari /teaching/classes; tidak ada sesi pertemuan per tanggal. |
| MOB-DASH-14 Beranda dosen: ringkasan beban mengajar (kelas, SKS, ujian a | Tidak ada agregat; N panggilan (per kelas) bila ingin menghitung. |
| MOB-DASH-16 Beranda dosen: tugas perlu diperiksa dan nilai belum lengkap | Endpoint agregat 'antrean penilaian dosen' belum ada; mobile tidak boleh melakukan N+1 berat. |
| MOB-DASH-17 Pengumuman untuk dosen | Perlu endpoint feed pengumuman untuk dosen (mis. GET /announcements/feed) atau pemberian izin announcements.read terbatas. |
| MOB-DASH-18 Widget dosen lain: mahasiswa bimbingan, jadwal sidang, aktiv | Perlu endpoint /advising/students (rencana) dan feed sidang/penelitian dosen. |
| MOB-PLAT-02 Internasionalisasi: infrastruktur ARB dan bahasa Indonesia ( | Pesan server tidak dilokalisasi (tanpa Accept-Language); pesan validasi tetap Indonesia. |
| MOB-PLAT-03 Bahasa Inggris (EN) | Tidak ada locale negotiation server. |
| MOB-PLAT-09 Mode offline: banner konektivitas dan cache baca | Tidak ada ETag/If-Modified-Since atau header cache. |
| MOB-PLAT-14 Notifikasi push (FCM) | Endpoint token, driver FCM, aktivasi channel push, payload dengan event_key+id untuk deep link. |
| MOB-PLAT-15 Pengaturan aplikasi | Preferensi per event_key tidak berfungsi; kanal push belum aktif. |
| MOB-PLAT-16 Deep link dan App Links | Tidak ada deep link terstruktur (type/id); Xendit return URL; reset password web. |
| MOB-PLAT-19 Branding dan profil universitas (tenant) | logo hanya logo_file_id (butuh Bearer fetch); tidak ada endpoint publik pra-login. |
| MOB-PLAT-20 Gerbang router ganti sandi wajib | Penegakan server belum ada. |
| MOB-PLAT-21 Penanganan tenant: build satu-universitas vs X-University-ID | Tidak ada endpoint publik penemuan universitas (code/domain -> ULID/branding). |
| MOB-PLAT-23 Klien API: envelope, paginasi, dan pemetaan kode error | Daftar error code belum tersentral. |
| MOB-PLAT-24 Unggah, unduh, dan pratinjau berkas (kamera/galeri/penyimpan | Tidak ada URL bertanda tangan; FileUploadResource tanpa url; batas ukuran tidak dapat dibaca klien (butuh config). |
| MOB-PLAT-25 Idempotensi dan penguncian optimistik pada aksi tulis | Idempotensi belum diterapkan untuk KRS, tugas, ujian; STALE_VERSION belum ada. |
| MOB-PLAT-26 Gerbang fitur/entitlement dari server (menu tersembunyi) | Endpoint konfigurasi publik/terotentikasi untuk bendera fitur dan batas unggah. |
| MOB-PLAT-27 Versi aplikasi, paksa pembaruan, mode pemeliharaan | Endpoint app-config publik. |
| MOB-ACAD-03 Lihat nilai seluruh mata kuliah per semester | Respons penuh tanpa filter semester/paginasi (C2 gap) |
| MOB-ACAD-03 Lihat nilai seluruh mata kuliah per semester | Tidak ada publikasi nilai (is_published) maupun komponen nilai |
| MOB-ACAD-03 Lihat nilai seluruh mata kuliah per semester | Cekal/EDOM gate belum ditegakkan (lihat MOB-ACAD-09) |
| MOB-ACAD-04 Lihat KHS per semester (IPS, SKS, rincian nilai) | Belum ada gerbang EDOM (403 edom_incomplete, D12 desain) maupun publikasi nilai |
| MOB-ACAD-05 Lihat transkrip sementara dengan IPK | Belum ada penanda Konversi/Pemutihan, attempt_number, counts_toward_ipk (desain D26/D15) |
| MOB-ACAD-07 Unduh KHS PDF | QR validasi + tanda tangan elektronik belum ada |
| MOB-ACAD-08 Unduh transkrip PDF | Cekal 'transcript' belum ditegakkan di kode saat ini; QR/TTE belum ada |
| MOB-ACAD-09 Gerbang akses nilai/transkrip (cekal keuangan, EDOM) | Tidak ada penegakan cekal view_grades/transcript (AcademicBlockService::ENFORCED_SERVICES) |
| MOB-ACAD-09 Gerbang akses nilai/transkrip (cekal keuangan, EDOM) | Tidak ada modul EDOM dan gerbangnya |
| MOB-ACAD-09 Gerbang akses nilai/transkrip (cekal keuangan, EDOM) | Nama endpoint/kode galat belum final (403 edom_incomplete vs 409 EDOM_REQUIRED) |
| MOB-ACAD-16 Katalog program studi, mata kuliah, dan prasyarat | Tidak ada katalog kurikulum/MK bagi mahasiswa |
| MOB-ACAD-16 Katalog program studi, mata kuliah, dan prasyarat | Dosen tidak punya curriculums.read |
| MOB-ACAD-16 Katalog program studi, mata kuliah, dan prasyarat | Ekuivalensi MK dan kurikulum_courses belum ada |
| MOB-ACAD-17 Dosen: cari dan lihat daftar serta detail mahasiswa | Tidak ada filter academic_advisor_id; tidak ada pembatasan kepemilikan (privasi data seluruh mahasiswa tenant) |
| MOB-ACAD-18 Dosen: lihat transkrip ringkas mahasiswa | Dosen PA murni tidak bisa; tidak ada pembatasan hanya mahasiswa bimbingan; tidak ada KHS per semester untuk dosen |
| MOB-ACAD-19 Dosen: daftar kelas yang diampu (dengan jadwal) | Tidak ada team teaching (class_section_lecturers) |
| MOB-ACAD-19 Dosen: daftar kelas yang diampu (dengan jadwal) | Halaman web 'Kelas Saya' belum ada |
| MOB-ACAD-20 Dosen: roster peserta kelas | Tidak ada endpoint roster khusus per kelas dengan kepemilikan |
| MOB-ACAD-21 Notifikasi nilai terbit/direvisi | Belum ada publikasi nilai dan notifikasi terkait |
| MOB-KRS-01 Lihat KRS semester berjalan | Belum ada blockers[] terstruktur (G1-G5 desain D10) - hanya notices teks |
| MOB-KRS-01 Lihat KRS semester berjalan | Belum ada status revision_requested/locked |
| MOB-KRS-02 Lihat penawaran kelas KRS dengan pemeriksaan kelayakan | Penawaran lintas prodi/MBKM dan paket MK belum ada |
| MOB-KRS-03 Tambah kelas ke draft KRS | Kode galat bertipe untuk PREREQUISITE_NOT_MET/CLASS_FULL/CREDIT_LIMIT_EXCEEDED/PERIOD_CLOSED belum ada (hanya pesan teks) |
| MOB-KRS-03 Tambah kelas ke draft KRS | Tidak ada Idempotency-Key |
| MOB-KRS-05 Ajukan KRS ke dosen wali | Daftar pelanggaran lengkap (violations[]) belum ada |
| MOB-KRS-05 Ajukan KRS ke dosen wali | Idempotency-Key belum ada |
| MOB-KRS-05 Ajukan KRS ke dosen wali | Tidak ada validasi 'dry-run' (lihat MOB-KRS-16) |
| MOB-KRS-07 Batas beban SKS berdasarkan IPS dan indikator SKS | Tier hardcoded, belum dapat dikonfigurasi per tenant |
| MOB-KRS-07 Batas beban SKS berdasarkan IPS dan indikator SKS | SKS khusus Semester Pendek (maks 9) belum ada |
| MOB-KRS-08 Deteksi bentrok jadwal dan kelas penuh saat memilih kelas | Jadwal berstatus draft/in_review/published dan jadwal cancelled belum dimodelkan |
| MOB-KRS-09 Validasi prasyarat dan MK yang sudah lulus/mengulang | Ekuivalensi MK dan kebijakan retake terkonfigurasi belum ada |
| MOB-KRS-10 Penegakan periode KRS | Kode galat PERIOD_CLOSED bertipe belum ada; Add/Drop period belum ada |
| MOB-KRS-11 Cekal (blokir) akademik/keuangan pada KRS dengan banner dan  | Tidak ada GET /student/finance/clearance (status per layanan, alasan, jatuh tempo dispensasi) - mobile hanya melihat cekal lewat notices/409 |
| MOB-KRS-11 Cekal (blokir) akademik/keuangan pada KRS dengan banner dan  | blockers[] terstruktur belum ada |
| MOB-KRS-12 Lihat riwayat KRS per semester | Tidak ada paginasi/filter term; tidak ada event log persetujuan (krs_submission_events) |
| MOB-KRS-14 Status keputusan KRS dan umpan balik penolakan dosen PA | Catatan revisi per baris belum ada |
| MOB-KRS-14 Status keputusan KRS dan umpan balik penolakan dosen PA | Push notification (FCM) belum ada |
| MOB-KRS-15 Perubahan KRS setelah terkunci (add/drop, permintaan perubah | Seluruh alur change request, periode add/drop dan status locked belum ada |
| MOB-KRS-18 Semester Pendek: ajukan KRS multi-kelas | Modul semester pendek belum ada di routes |
| MOB-KRS-19 Semester Pendek: lihat KRS SP dengan status persetujuan/pemb | Belum ada |
| MOB-KRS-21 Dosen PA: antrean pengajuan KRS mahasiswa bimbingan | Tidak ada /advising/* (desain) maupun tab Revisi; web belum punya halaman (route persetujuanKrs tak terdaftar) |
| MOB-KRS-22 Dosen PA: setujui KRS mahasiswa bimbingan | Persetujuan massal (web-only desain) dan /advising/* alias belum ada |
| MOB-KRS-23 Dosen PA: tolak/kembalikan KRS dengan catatan | Catatan per baris, reopen, request-revision alias belum ada |
| MOB-KRS-24 Notifikasi alur KRS (pengajuan masuk ke dosen PA, keputusan  | Push/FCM registrasi perangkat belum ada |
| MOB-KRS-24 Notifikasi alur KRS (pengajuan masuk ke dosen PA, keputusan  | Notifikasi 'krs_reopened/expired' dan pengingat H-3 belum ada |
| MOB-KRS-26 Dosen PA: daftar mahasiswa bimbingan dan ringkasan progres | Tidak ada endpoint advisee; /students tak punya filter academic_advisor_id dan role advisor murni tak punya students.read |
| MOB-KRS-26 Dosen PA: daftar mahasiswa bimbingan dan ringkasan progres | Tidak ada ringkasan IPS/IPK/presensi per advisee |
| MOB-KRS-27 Dosen PA: lihat data akademik mahasiswa bimbingan (transkrip | Tidak ada KHS/presensi/cekal per advisee; advisor murni tanpa students.read; tidak ada scoping advisee |
| MOB-KRS-28 Dosen PA: peringatan mahasiswa berisiko | Belum ada |
| MOB-KRS-29 Catatan bimbingan akademik/konsultasi (dosen PA mencatat, ma | Modul Bimbingan belum ada di app/Modules |
| MOB-KRS-30 Mahasiswa: halaman dosen PA (profil, jadwal konsultasi, riwa | Sesi bimbingan dan permintaan jadwal belum ada; NIDN/email dosen PA belum dipastikan di respons |
| MOB-KRS-32 Dosen PA: status cekal keuangan advisee (tanpa nominal) | Endpoint finance-clearance per mahasiswa belum ada; daftar blok memerlukan finance_clearance.read |
| MOB-SCHED-01 Lihat jadwal kuliah mingguan (mahasiswa) | Tidak ada status publikasi jadwal, jadwal pengganti, atau notifikasi perubahan jadwal |
| MOB-SCHED-03 Kalender akademik dan agenda pribadi mahasiswa | Tidak ada ekspor/sinkron kalender eksternal; jadwal praktikum/pengganti/sesi per tanggal (lecture_sessions) belum ada; kelas hanya term berjalan |
| MOB-SCHED-04 Kalender akademik untuk dosen | Tidak ada kalender gabungan dosen (kelas+ujian+kegiatan); tidak ada sinkron kalender eksternal |
| MOB-SCHED-05 Jadwal mengajar mingguan dosen | Tidak ada GET /teaching/schedule bertanggal (desain D14: filter rentang tanggal) |
| MOB-SCHED-05 Jadwal mengajar mingguan dosen | Tidak ada penggantian jadwal/ruang dan notifikasi perubahan |
| MOB-SCHED-05 Jadwal mengajar mingguan dosen | Web dosen tidak punya layar jadwal (widget 'Jadwal mengajar' tidak dibangun) |
| MOB-SCHED-06 Pertemuan/kelas hari ini (dosen) | Tidak ada endpoint sesi per tanggal/penggantian (lecture_sessions Baru) |
| MOB-SCHED-07 Jadwal ujian UTS/UAS dan ruang ujian | Jadwal ujian terpusat, ruang, pengawas, kartu ujian, ujian susulan belum ada |
| MOB-SCHED-08 Notifikasi perubahan jadwal (ruang, dosen, pengganti, dibata | Revisi jadwal, status jadwal, event perubahan dan push belum ada |
| MOB-SCHED-10 Lihat semester aktif dan periode KRS | Periode terikat (add/drop, UTS, UAS, input nilai, EDOM: academic_term_periods) belum ada |
| MOB-ATT-04 Peringatan kehadiran di bawah batas minimum | Tidak ada push/notifikasi saat kehadiran turun di bawah batas |
| MOB-ATT-05 Cek kelayakan ikut ujian berdasarkan kehadiran | GET /krs-items/{krsItem}/exam-eligibility (rancangan D14) belum ada |
| MOB-ATT-05 Cek kelayakan ikut ujian berdasarkan kehadiran | Tidak ada enforcement di server saat mulai ujian |
| MOB-ATT-05 Cek kelayakan ikut ujian berdasarkan kehadiran | Kebijakan: apakah memblokir atau hanya memperingatkan belum diputuskan |
| MOB-ATT-06 Presensi mandiri dengan QR (scan QR dosen) | Diimplementasikan: `POST /api/v1/student/attendance/qr-scan` (kontrak D14 dipilih); sesi dan QR dosen di `POST /class-sections/{id}/attendance-sessions`, `GET /attendance-sessions/{id}/qr`, `POST /attendance-sessions/{id}/close` |
| MOB-ATT-06 Presensi mandiri dengan QR (scan QR dosen) | Diganti desainnya: tabel `attendance_sessions` dan kolom `attendances.method`; token dihitung dari secret sesi dan slot 30 detik, tidak disimpan |
| MOB-ATT-06 Presensi mandiri dengan QR (scan QR dosen) | Aturan geofence/lokasi belum ditetapkan |
| MOB-ATT-08 Ajukan izin/sakit dengan surat pendukung | Tidak ada endpoint pengajuan izin presensi; belum jelas apakah berupa attendance-correction (D14) |
| MOB-ATT-09 Ajukan koreksi presensi (mahasiswa) | POST /attendance-corrections (rancangan D14) belum ada |
| MOB-ATT-09 Ajukan koreksi presensi (mahasiswa) | Bukti: POST /file-uploads sudah ada |
| MOB-ATT-10 Rekam presensi per pertemuan (batch) | Kepemilikan kelas tidak dipaksa saat flag OFF (dosen dapat merekam kelas lain) |
| MOB-ATT-10 Rekam presensi per pertemuan (batch) | Tanggal tidak boleh di masa depan (zona waktu universitas) dan harus berada dalam periode semester kelas |
| MOB-ATT-11 Koreksi presensi yang sudah direkam (oleh dosen) | Setiap perubahan tercatat di `audit_logs` (nilai lama dan baru, pengguna, waktu). Alasan opsional dikirim sebagai `reason` |
| MOB-ATT-11 Koreksi presensi yang sudah direkam (oleh dosen) | Tidak ada endpoint ambil pertemuan tunggal; harus filter dari /attendances |
| MOB-ATT-12 Lihat riwayat/daftar presensi kelas (dosen) | Tidak ada endpoint daftar pertemuan per kelas; harus group by meeting_number di klien |
| MOB-ATT-13 Rekap presensi per kelas / per mahasiswa (dosen) | GET /class-sections/{cs}/attendance-recap (D14) belum ada; agregasi klien rapuh untuk kelas besar |
| MOB-ATT-13 Rekap presensi per kelas / per mahasiswa (dosen) | per_page maks 100 (kemungkinan perlu iterasi halaman) |
| MOB-ATT-14 Pantau kehadiran mahasiswa bimbingan (dosen PA) | Tidak ada endpoint /advising/* kehadiran; attendance.read tidak diberikan ke academic_advisor |
| MOB-ATT-15 Buka/tutup sesi presensi QR dan rotasi token (dosen) | POST /teaching/lecture-sessions/{id}/qr/open\|close atau /qr-tokens (dua versi kontrak) |
| MOB-ATT-15 Buka/tutup sesi presensi QR dan rotasi token (dosen) | lecture_sessions belum ada |
| MOB-ATT-16 Sesi perkuliahan dan jurnal mengajar (BAP) | GET/POST /teaching/lecture-sessions* belum ada |
| MOB-ATT-16 Sesi perkuliahan dan jurnal mengajar (BAP) | Realisasi RPS /obe/... belum ada |
| MOB-ATT-17 Persetujuan permohonan izin dan koreksi presensi (dosen) | Endpoint /attendance-corrections belum ada |
| MOB-LMS-01 Daftar kelas yang saya ampu (Kelas Saya) | Tidak ada endpoint jadwal-saya (my schedule) khusus; turunkan dari schedules kelas |
| MOB-LMS-02 Daftar peserta kelas | Tidak ada endpoint roster khusus dengan scoping kepemilikan permanen |
| MOB-LMS-04 Detail mata kuliah (pertemuan, materi, tugas, ujian, kehadir | Tidak ada forum/pengumuman kelas/progres materi dalam respons |
| MOB-LMS-05 Lihat dan unduh materi kuliah | Tidak ada tracking progres/versi materi |
| MOB-LMS-08 Kumpulkan tugas (berkas dan/atau catatan) | Tidak ada tugas kelompok |
| MOB-LMS-08 Kumpulkan tugas (berkas dan/atau catatan) | Batas FileUpload (10MB sistem) dapat lebih kecil dari max_file_size_mb tugas (maks 50) |
| MOB-LMS-10 Lihat nilai dan umpan balik tugas | Tidak ada push FCM |
| MOB-LMS-12 Tambah materi (file/link/video/teks) | Tidak ada versi materi (D11 /lms/materials/{id}/versions) |
| MOB-LMS-14 Buat tugas (dosen) | Tidak ada tugas kelompok |
| MOB-LMS-16 Lihat pengumpulan tugas (roster) | Tidak ada pagination/filter |
| MOB-LMS-17 Nilai pengumpulan tugas dengan umpan balik | Tidak ada penilaian massal; tidak ada anotasi PDF |
| MOB-LMS-18 Unduh berkas pengumpulan mahasiswa (dosen) | Otorisasi download oleh dosen pengampu terhadap berkas mahasiswa belum terverifikasi (endpoint FileManagement) |
| MOB-LMS-19 Kirim nilai tugas ke komponen nilai akhir | Tidak ada komponen/bobot nilai, tidak ada send-to-grades |
| MOB-LMS-20 Kuis LMS (mahasiswa) | Tidak ada /portal/quizzes atau konteks lms_class pada ujian |
| MOB-LMS-22 Forum diskusi kelas | GET/POST /lms/classes/{id}/threads, /lms/threads/{id}/posts, PATCH /lms/posts/{id}/hide belum ada |
| MOB-LMS-23 Pengumuman kelas | GET/POST /lms/classes/{id}/announcements belum ada; dosen tidak punya feed |
| MOB-LMS-25 Catat progres materi | Kontrak tak konsisten: POST /lms/materials/{id}/progress (D11) vs /student/lms/materials/{courseMaterial}/progress (D14) |
| MOB-LMS-32 Rekap aktivitas kelas (ringkasan pengumpulan) | Tidak ada metrik aktivitas/engagement |
| MOB-EXAM-01 Daftar ujian saya dengan status pribadi | Tidak ada field 'diblokir cekal' pada StudentExamResource (blokir baru diketahui saat start -> 409). |
| MOB-EXAM-01 Daftar ujian saya dengan status pribadi | Tidak ada field server_now/deadline sehingga mobile harus menghitung sendiri. |
| MOB-EXAM-02 Checklist persiapan ujian & tips anti-pelanggaran | Tidak ada (konten statis). |
| MOB-EXAM-03 Mulai / lanjutkan percobaan ujian | Tidak ada deadline/remaining_seconds/server_now di StudentExamAttemptResource (hitung min(started_at+duration, exam.ends_at)). |
| MOB-EXAM-03 Mulai / lanjutkan percobaan ujian | Pemeriksaan kehadiran minimum (ATTENDANCE_BELOW_MINIMUM) belum ada di kode. |
| MOB-EXAM-04 Jawab soal dengan autosave per soal dan navigasi soal | Tidak ada client_seq/idempotensi urutan (lihat MOB-EXAM-14). |
| MOB-EXAM-05 Timer ujian berbasis server, peringatan, dan auto-submit | Tidak ada deadline/remaining_seconds/server_now pada attempt resource; klien menghitung dan menangani clock skew. |
| MOB-EXAM-05 Timer ujian berbasis server, peringatan, dan auto-submit | Tidak ada auto-submit terjadwal (hanya lazy). |
| MOB-EXAM-07 Deteksi pelanggaran ujian dan penalti skor (pemetaan mobile) | Enum bertipe browser; belum ada tipe mobile (app_background, screenshot, split_screen) -> dipetakan ke tipe yang ada. |
| MOB-EXAM-07 Deteksi pelanggaran ujian dan penalti skor (pemetaan mobile) | Tidak ada kebijakan auto-submit server setelah N pelanggaran. |
| MOB-EXAM-07 Deteksi pelanggaran ujian dan penalti skor (pemetaan mobile) | Penalti tidak dapat dikonfigurasi per ujian. |
| MOB-EXAM-07 Deteksi pelanggaran ujian dan penalti skor (pemetaan mobile) | Catatan: ExamViolationAndScoringTest termasuk 17 tes gagal bawaan (fixture hilang). |
| MOB-EXAM-08 Lihat hasil ujian dan review jawaban | Tidak ada pembukaan hasil terpisah per jadwal (hanya flag show_result_after_submission); 'publish-results' (D14) belum ada. |
| MOB-EXAM-11 Buka ujian dari tautan (deep link) atau pindai QR dosen | Tidak ada endpoint resolve token -> exam_id untuk pengguna login (hanya /public/exams/{token} yang tidak mengembalikan id). |
| MOB-EXAM-11 Buka ujian dari tautan (deep link) atau pindai QR dosen | Belum ada Android App Links/assetlinks.json dan host portal produksi. |
| MOB-EXAM-11 Buka ujian dari tautan (deep link) atau pindai QR dosen | Perlu paket pemindai QR (mobile_scanner). |
| MOB-EXAM-12 Penolakan akses ujian karena cekal keuangan/dispensasi (UTS/ | Tidak ada pre-check di daftar ujian (hanya terlihat saat start). |
| MOB-EXAM-12 Penolakan akses ujian karena cekal keuangan/dispensasi (UTS/ | Sumber cekal otomatis dari KEU (SIAKAD A.8) belum terverifikasi lengkap; docs KEU menyebut belum dibangun per 15 Okt 2026. |
| MOB-EXAM-13 Blokir ujian karena kehadiran di bawah minimum | Kode tidak memeriksa kehadiran saat start; kode error ATTENDANCE_BELOW_MINIMUM belum ada. |
| MOB-EXAM-14 Antrean jawaban offline dengan client_seq dan sinkron ulang | Body answer hanya {exam_question_id, exam_question_option_id}; client_seq/ANSWER_OUT_OF_ORDER/ATTEMPT_CLOSED/rate limit belum ada. |
| MOB-EXAM-18 Daftar ujian dengan status publikasi (dosen) | Tanpa filter kepemilikan server (flag OFF) - dosen bisa melihat ujian kelas lain. |
| MOB-EXAM-19 Buat ujian untuk kelas (durasi, percobaan, jadwal, jumlah so | Tanpa cek kepemilikan kelas (NIL ownership flag OFF). |
| MOB-EXAM-25 Kelola bank soal per mata kuliah | Belum ada entitas 'bank' berstatus/workflow (hanya butir). |
| MOB-EXAM-27 Rentang nilai huruf dan bobot ujian | Dokumen D06 menyebut endpoint tidak terdokumentasi; kode menyediakannya (kode menang). |
| MOB-EXAM-28 Buat dan bagikan tautan akses ujian + QR (regenerasi) | Host portal untuk tautan perlu dikonfirmasi (mock portal.kampus.ac.id). |
| MOB-EXAM-29 Pantau peserta ujian secara langsung dengan peringatan pelan | Tidak ada push real-time; tanpa filter pencarian pada participants. |
| MOB-EXAM-31 Rekap nilai ujian dengan pencarian, filter status, dan ekspo | Dokumen D06 menyebut tak ada endpoint; kode ada. |
| MOB-EXAM-33 Unduh naskah ujian PDF (opsional dengan kunci jawaban) | D06 menyebut tak ada endpoint; kode menyediakan. |
| MOB-EXAM-34 Pratinjau ujian sebagai mahasiswa (lokal) | Tidak perlu backend. |
| MOB-EXAM-37 Penilaian manual esai (antrean penilaian) dan rilis hasil | Backend belum ada; soal esai belum didukung. |
| MOB-EXAM-38 Kirim skor ujian ke komponen nilai kelas | Bergantung komponen nilai (MOB-GRADE-05). |
| MOB-GRADE-01 Lihat daftar nilai kelas (dosen) | Tanpa endpoint grade-sheet gabungan; klien menggabungkan dua daftar. |
| MOB-GRADE-01 Lihat daftar nilai kelas (dosen) | Tidak ada pembatasan kelas milik sendiri di server (flag OFF). |
| MOB-GRADE-02 Input / ubah nilai akhir mahasiswa dengan konversi huruf oto | Tidak ada cek bahwa dosen mengampu kelas (NIL-09) bila flag academic.lecturer_ownership OFF. |
| MOB-GRADE-02 Input / ubah nilai akhir mahasiswa dengan konversi huruf oto | Tanpa status draft/final: setiap simpan langsung final bagi mahasiswa. |
| MOB-GRADE-03 Input nilai massal per kelas (berurutan di klien) | Tidak ada bulk save atomik/versi (D14: PUT grade-sheet/scores), tidak ada impor Excel. |
| MOB-GRADE-05 Susun komponen nilai dan bobot kelas | Seluruh modul komponen belum dibangun (STATUS A.6); skala nilai hardcoded. |
| MOB-GRADE-06 Lembar nilai per komponen (simpan batch dengan versi) | Belum ada; menggantikan PUT per krs-item. |
| MOB-GRADE-07 Validasi/tarik skor LMS dan CBT ke lembar nilai | Belum ada tabel assessment_scores/endpoint. |
| MOB-GRADE-08 Ajukan / finalisasi lembar nilai untuk publikasi | Belum ada alur finalisasi/persetujuan/kunci/publikasi/notifikasi nilai. |
| MOB-GRADE-10 Ajukan revisi nilai setelah publikasi (dosen) | Belum ada tabel grade_revisions/endpoint. |
| MOB-EDOM-01 Daftar evaluasi dosen wajib (EDOM) periode berjalan | Modul EDOM tidak ada di backend (tidak ada rute edom/evaluation di app/Modules/*/Routes/api.php). |
| MOB-EDOM-01 Daftar evaluasi dosen wajib (EDOM) periode berjalan | Halaman web PortalLecturerEvaluationPage.tsx adalah mock tidak berrute (/portal/evaluasi-dosen hanya konstanta). |
| MOB-EDOM-02 Isi dan kirim formulir EDOM untuk satu dosen/kelas | Modul EDOM tidak ada di backend (tidak ada rute edom/evaluation di app/Modules/*/Routes/api.php). |
| MOB-EDOM-02 Isi dan kirim formulir EDOM untuk satu dosen/kelas | Halaman web PortalLecturerEvaluationPage.tsx adalah mock tidak berrute (/portal/evaluasi-dosen hanya konstanta). |
| MOB-EDOM-03 Draf jawaban EDOM anonim tersimpan di perangkat | Bergantung kontrak EDOM. |
| MOB-EDOM-04 Pemberitahuan privasi dan anonimitas EDOM | Kontrak EDOM belum ada. |
| MOB-EDOM-05 Syarat EDOM sebelum melihat nilai (grade gate) | Kode /student/grades tidak memuat marker; belum ada gerbang. |
| MOB-EDOM-06 Laporan EDOM saya (dosen) | Belum ada. Lecturer role tidak punya menu EDOM. |
| MOB-FIN-03 Detail tagihan: rincian komponen, termin, alokasi pembayaran | Tidak ada riwayat/timeline perubahan tagihan khusus mahasiswa |
| MOB-FIN-03 Detail tagihan: rincian komponen, termin, alokasi pembayaran | rate_snapshot dan notes selalu null |
| MOB-FIN-04 Unduh PDF tagihan dengan tautan pembayaran | Tidak ada endpoint PDF tagihan mahasiswa (hanya kuitansi). Kontrak yang dibutuhkan: GET /api/v1/student/finance/invoices/{invoice}/pdf (mengikuti pola receipt) |
| MOB-FIN-07 Bayar tagihan/termin secara online (VA, QRIS, e-wallet, gera | Kode backend lengkap tetapi belum teruji dengan akun Xendit nyata (STATUS §8 'Belum'); tanpa XENDIT_SECRET_KEY kanal online kosong |
| MOB-FIN-07 Bayar tagihan/termin secara online (VA, QRIS, e-wallet, gera | Hanya pembayaran penuh (tidak ada nominal parsial online) |
| MOB-FIN-07 Bayar tagihan/termin secara online (VA, QRIS, e-wallet, gera | qr_string selalu null (QRIS lewat halaman checkout), va_number hanya jika Xendit mengembalikannya |
| MOB-FIN-07 Bayar tagihan/termin secara online (VA, QRIS, e-wallet, gera | Tidak ada push saat sukses (hanya notifikasi database keu.payment_succeeded) |
| MOB-FIN-08 Cek/segarkan status transaksi online | Tidak ada GET detail transaksi tunggal (hanya list/refresh) |
| MOB-FIN-08 Cek/segarkan status transaksi online | Tidak ada push saat status berubah |
| MOB-FIN-09 Batalkan instruksi/transaksi yang belum dibayar | Tidak ada pembatalan pengajuan manual setelah bukti dikirim (awaiting_verification) |
| MOB-FIN-10 Bayar via transfer bank manual dengan kode unik (instruksi p | Struktur bank_accounts tidak terdefinisi/tervalidasi (parsing klien harus defensif) |
| MOB-FIN-11 Unggah bukti transfer dan lacak verifikasi (termasuk unggah  | Mahasiswa tidak dapat membatalkan/mengganti bukti yang sedang diverifikasi (docs: 'Replace proof / cancel manual submission' hanya sebagian) |
| MOB-FIN-12 Riwayat transaksi pembayaran | Tidak ada filter status |
| MOB-FIN-12 Riwayat transaksi pembayaran | Tidak ada GET /transactions/{id} (detail tunggal) untuk mahasiswa |
| MOB-FIN-14 Kartu piutang / rekening koran mahasiswa (statement) | Tanpa paginasi/filter periode (bisa berat untuk akun lama) |
| MOB-FIN-14 Kartu piutang / rekening koran mahasiswa (statement) | Tidak ada ekspor PDF rekening koran mahasiswa |
| MOB-FIN-15 Bayar tagihan/termin dengan saldo deposit | Flag desain 'self-use deposit controlled by flag' tidak ditemukan di kode; selalu aktif |
| MOB-FIN-16 Saldo deposit, mutasi, dan status pengembalian dana | Mutasi dibatasi 200 entri terbaru tanpa paginasi |
| MOB-FIN-17 Top-up/titip deposit | Top-up online tidak ada |
| MOB-FIN-18 Ajukan pengembalian dana dari deposit (refund) | Tidak ada pembatalan refund oleh mahasiswa |
| MOB-FIN-18 Ajukan pengembalian dana dari deposit (refund) | Refund via API penyedia (FR-RFD-06, P3) belum ada; eksekusi oleh staf |
| MOB-FIN-19 Gunakan kode voucher pada tagihan belum lunas | Tidak ada pratinjau potongan sebelum menerapkan |
| MOB-FIN-19 Gunakan kode voucher pada tagihan belum lunas | Tidak ada 'lepas voucher' oleh mahasiswa (docs D19 merancang) |
| MOB-FIN-20 Ajukan cicilan (skema standar atau khusus) | Web hanya mengirim installment_plan_id (+reason); termin khusus & reschedule didukung API tapi belum dipakai UI web |
| MOB-FIN-22 Daftar pengajuan cicilan saya dan pembatalannya | Tidak ada GET daftar pengajuan cicilan mahasiswa dan tidak ada pembatalan. Kontrak dibutuhkan: GET /student/finance/installment-requests, POST .../{id}/cancel. Sementara itu hanya notifikasi keu.installment_decided dan jadwal pada detail tagihan |
| MOB-FIN-23 Pembayaran sebagian (partial) dan kelebihan bayar menjadi de | Nominal parsial online tidak tersedia (selalu penuh) |
| MOB-FIN-24 Lihat beasiswa, bantuan, dan potongan aktif/dicabut | Tidak ada estimasi efek award per tagihan selain item pada detail |
| MOB-FIN-26 Transparansi denda keterlambatan (tagihan denda otomatis) | Tidak ada endpoint penjelasan aturan/rumus denda dan estimasi berjalan per tagihan |
| MOB-FIN-27 Lihat kelompok UKT dan riwayat pengajuan peninjauan UKT | UKT STATUS: instrumen skoring/pengajuan data ekonomi/SK belum lengkap (STATUS §8) |
| MOB-FIN-28 Ajukan peninjauan/keberatan UKT dengan bukti | Tidak ada pembatalan oleh mahasiswa |
| MOB-FIN-30 Status cekal keuangan per layanan, penyebab, dan cara penyel | Cekal untuk nilai/transkrip/yudisium/wisuda tidak ditegakkan (hanya krs & exam) |
| MOB-FIN-30 Status cekal keuangan per layanan, penyebab, dan cara penyel | Tidak ada endpoint 'cekal akademik' terpisah (SIAKAD Tahap 3 'Baru'); hanya cekal sumber finance yang dapat dilihat mahasiswa |
| MOB-FIN-31 Ajukan dispensasi cekal dan lacak keputusannya | Tidak ada GET daftar dispensasi sendiri dan tidak ada pembatalan oleh mahasiswa (hanya via summary.blocks[].exemptions) |
| MOB-FIN-31 Ajukan dispensasi cekal dan lacak keputusannya | Janji bayar (FR-CKL-05) hanya field informasi |
| MOB-FIN-33 Unduh surat keterangan bebas tanggungan keuangan (PDF) | Perlu endpoint PDF surat bebas tanggungan (mis. GET /student/finance/clearance/letter) |
| MOB-FIN-34 Notifikasi keuangan dalam aplikasi (tagihan terbit, pembayar | Tidak ada endpoint registrasi token FCM/push |
| MOB-FIN-34 Notifikasi keuangan dalam aplikasi (tagihan terbit, pembayar | Tautan notifikasi berupa URL web, bukan rute app |
| MOB-FIN-35 Tagihan SKS otomatis setelah KRS disetujui dan penyesuaian t | Perilaku add/drop (penyesuaian tagihan) belum terverifikasi; tidak ada tampilan estimasi biaya SKS sebelum KRS disetujui |
| MOB-FIN-37 Dosen PA melihat status cekal keuangan mahasiswa bimbingan ( | Perlu endpoint/field baru (mis. flag has_financial_block pada list advisees KRS atau GET /advising/students/{id}/blocks) + permission scoped advisor; hanya boolean tanpa nominal |
| MOB-SP-01 Daftar term semester pendek yang terlihat mahasiswa | Perlu GET /student/academic-terms (filter semester=pendek) + permission student |
| MOB-SP-02 Jelajahi penawaran kelas SP beserta kelayakan (eligibility) | GET /student/academic-terms/{term}/offering belum ada |
| MOB-SP-03 Pilih kelas SP (keranjang), cek bentrok/batas SKS, dan kirim | Dukungan term SP pada /student/krs/*, kebijakan eligibility, kapasitas |
| MOB-SP-05 Tagihan SP dan status pembayarannya (melalui portal Keuangan | Sisi KEU siap; modul SP yang menerbitkan tagihan (InvoiceIssuer, FinanceBillingService::postExternalInvoice) belum ada sehingga tagihan SP belum akan muncul |
| MOB-SP-06 Status KRS SP 'Menunggu pembayaran' dengan tautan ke Tagihan | Bergantung seluruh modul SP |
| MOB-SRV-01 Lihat daftar pengajuan saya | Tidak ada filter status/type di server (filter di klien). |
| MOB-SRV-02 Lihat detail pengajuan, tahap persetujuan, dan riwayat | Tidak ada id ApprovalRequest di resource sehingga detail langkah multi-tahap harus lewat GET /approval-requests (filter requested_by saya). |
| MOB-SRV-03 Ajukan cuti akademik | Jenis cuti (sakit/melahirkan), lampiran wajib per jenis, rekomendasi dosen wali, periode batas pengajuan: belum ada. |
| MOB-SRV-04 Ajukan aktif kembali setelah cuti | Mahasiswa status leave: perlu dipastikan login/akses student/requests tidak diblokir (login diblokir hanya untuk inactive/dropped_out/resigned) - inactive tidak bisa login sehingga aktif kembali dari inactive tidak dapat dilakukan lewat app. |
| MOB-SRV-05 Ajukan perubahan data pribadi | Tidak ada bukti dokumen wajib per field. |
| MOB-SRV-06 Ajukan surat keterangan (aktif kuliah, berkelakuan baik, rek | Field tujuan/instansi/bahasa/jumlah salinan, batas pengajuan terbuka, penomoran surat resmi: belum ada. |
| MOB-SRV-07 Unduh PDF surat yang sudah disetujui | Nomor surat resmi, QR verifikasi, pembekuan berkas, log akses unduh: belum ada. |
| MOB-SRV-10 Unggah lampiran pengajuan (kamera/berkas) | Batas ukuran/tipe per jenis pengajuan belum ada (gap C2). |
| MOB-SRV-11 Ajukan ulang pengajuan yang dikembalikan (returned) | Tautan StudentRequest -> ApprovalRequest tidak diekspos; status returned tidak tercermin di StudentRequestResource; perlu verifikasi apakah workflow default menyediakan aksi return untuk pengajuan mahasiswa. |
| MOB-SRV-12 Notifikasi perubahan status pengajuan | Belum terverifikasi apakah StudentRequestService mengirim notifikasi keputusan; tidak ada endpoint registrasi push/FCM; link web perlu dipetakan ke layar mobile. |
| MOB-SRV-13 Dosen/PA: setujui, tolak, kembalikan, atau delegasikan langk | Tidak ada daftar "tugas persetujuan saya" (GET /approval-requests hanya requested_by saya tanpa approval_requests.read); langkah workflow mahasiswa tidak melibatkan dosen. |
| MOB-SRV-14 Dosen wali: beri rekomendasi atas pengajuan cuti anak bimbin | Workflow multi-langkah + inbox dosen wali + endpoint PA. |
| MOB-KMHS-01 Lihat poin kemahasiswaan saya (total, per kategori, riwayat) | Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12). |
| MOB-KMHS-02 Ajukan kegiatan/prestasi dengan bukti | Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12). |
| MOB-KMHS-03 Lacak pengajuan kegiatan dan umpan balik petugas | Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12). |
| MOB-KMHS-04 Banding keputusan poin / pengajuan ditolak | Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12). |
| MOB-KMHS-06 Lihat kasus disiplin/sanksi saya, ajukan pembelaan dan keber | Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12). |
| MOB-KMHS-10 Jelajahi program beasiswa yang cocok | Endpoint mahasiswa belum ada; /scholarships 403 untuk student. |
| MOB-KMHS-10 Jelajahi program beasiswa yang cocok | Modul read-only admin (STATUS §10). |
| MOB-KMHS-11 Ajukan beasiswa dan lacak seleksi | Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12). |
| MOB-KMHS-11 Ajukan beasiswa dan lacak seleksi | /scholarship-applications tidak ter-scope ke mahasiswa. |
| MOB-GRAD-01 Lihat syarat dan periode yudisium | Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18). |
| MOB-GRAD-02 Lihat checklist kelayakan yudisium | Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18). |
| MOB-GRAD-04 Daftar yudisium dan unggah dokumen persyaratan | Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18). |
| MOB-GRAD-05 Perbaiki pendaftaran yudisium yang dikembalikan/ditolak | Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18). |
| MOB-GRAD-05 Perbaiki pendaftaran yudisium yang dikembalikan/ditolak | Kontrak endpoint belum ada. |
| MOB-GRAD-06 Lihat hasil kelulusan (keputusan, predikat, tanggal) | Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18). |
| MOB-GRAD-06 Lihat hasil kelulusan (keputusan, predikat, tanggal) | Endpoint baca hasil student belum didefinisikan. |
| MOB-GRAD-07 Lihat blokir yudisium (keuangan, perpustakaan, kemahasiswaan | Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18). |
| MOB-GRAD-07 Lihat blokir yudisium (keuangan, perpustakaan, kemahasiswaan | Data pinjaman perpustakaan tidak tersedia. |
| MOB-GRAD-08 Daftar wisuda | Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18). |
| MOB-GRAD-09 Konfirmasi kehadiran wisuda dan kartu peserta QR | Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18). |
| MOB-GRAD-12 Unduh dokumen kelulusan (SK, transkrip, ijazah, SKL) | Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18). |
| MOB-THES-01 Lihat skripsi/tugas akhir saya (judul, jenis, status) | Modul Thesis hanya read-only untuk admin (GET /theses, /theses/{id}; permission theses.read tidak dimiliki student/lecturer; tanpa scoping 'milik sendiri'). Tidak ada tabel/endpoint bimbingan, seminar, sidang. |
| MOB-THES-01 Lihat skripsi/tugas akhir saya (judul, jenis, status) | Perlu endpoint mis. GET /api/v1/student/thesis (own, tanpa permission admin) |
| MOB-THES-02 Ajukan judul/proposal skripsi | Endpoint tulis tugas akhir tidak ada |
| MOB-THES-02 Ajukan judul/proposal skripsi | Kontrak API tidak didefinisikan rinci di dokumen |
| MOB-THES-03 Lihat pembimbing dan jadwal tahapan tugas akhir | Tidak ada tabel/endpoint supervisor, jadwal seminar/sidang |
| MOB-THES-04 Catat bimbingan skripsi (logbook) dan ajukan sesi bimbingan | Tidak ada tabel supervision session/logbook |
| MOB-THES-05 Daftar mahasiswa bimbingan TA dan verifikasi logbook bimbing | Dosen tidak punya theses.read; tidak ada filter 'pembimbing = saya' |
| MOB-THES-05 Daftar mahasiswa bimbingan TA dan verifikasi logbook bimbing | Tidak ada endpoint logbook/sesi |
| MOB-THES-06 Pendaftaran seminar proposal dan sidang skripsi | Tidak ada tabel/endpoint seminar, sidang, penguji |
| MOB-THES-08 Lihat data magang/PKL/KKN saya | Tidak ada akses student/lecturer, tidak ada scoping milik sendiri |
| MOB-THES-08 Lihat data magang/PKL/KKN saya | Perlu mis. GET /api/v1/student/internships |
| MOB-LIB-01 Cari katalog perpustakaan | Modul Library hanya GET /books & /books/{id} untuk admin (books.read, tidak dimiliki student/lecturer). Tidak ada my-loans; BookResource show menyertakan pinjaman semua mahasiswa (kebocoran data bila dibuka apa adanya). |
| MOB-LIB-01 Cari katalog perpustakaan | Perlu route/permission baca katalog untuk student & lecturer dengan resource tanpa daftar pinjaman |
| MOB-LIB-02 Pinjaman aktif dan riwayat peminjaman saya | Tidak ada endpoint my-loans |
| MOB-ANN-03 Unduh lampiran pengumuman | Izin akses file lampiran untuk mahasiswa perlu diuji end-to-end (tidak diverifikasi) |
| MOB-ANN-04 Pengumuman untuk dosen dan dosen PA | Tidak ada feed dosen; perlu mis. GET /lecturer/announcements atau izin announcements.read + filter audience |
| MOB-ANN-04 Pengumuman untuk dosen dan dosen PA | Tidak ada read-tracking untuk dosen |
| MOB-ANN-05 Buat pengumuman kelas oleh dosen | Tidak ada target 'kelas'; permission tidak diberikan ke dosen |
| MOB-ANN-06 Pusat notifikasi (daftar, filter belum dibaca) | Payload tidak bertipe (tak ada type/entity_id/route) |
| MOB-ANN-09 Deep link notifikasi ke layar aplikasi | Tak ada payload terstruktur {type, entity_id, route}; perlu mapper link->route di aplikasi atau tambahan backend |
| MOB-ANN-10 Notifikasi push (FCM) per jenis dan deep link | Endpoint registrasi/rotasi token FCM |
| MOB-ANN-10 Notifikasi push (FCM) per jenis dan deep link | Pengirim push (FCM HTTP v1) + kredensial Firebase |
| MOB-ANN-10 Notifikasi push (FCM) per jenis dan deep link | Opt-in/out per channel (tabel preferensi ada) |
| MOB-ANN-11 Preferensi saluran notifikasi | Per-event preference tak efektif |
| MOB-ANN-11 Preferensi saluran notifikasi | Tidak ada endpoint daftar event/channel yang tersedia |
| MOB-ANN-12 Notifikasi keuangan mahasiswa (tagihan, bayar, cekal, UKT, r | Push & email tidak ada |
| MOB-ANN-12 Notifikasi keuangan mahasiswa (tagihan, bayar, cekal, UKT, r | Pengingat jatuh tempo bergantung scheduler (belum diverifikasi aktif) |
| MOB-ANN-13 Notifikasi akademik mahasiswa (KRS, tugas, nilai tugas) | Notifikasi: perubahan jadwal, nilai akhir, pengingat EDOM, SP belum ada |
| MOB-ANN-14 Notifikasi dosen dan PA (permintaan persetujuan KRS, langkah | Event jadwal/SP dosen belum ada |
| MOB-ANN-14 Notifikasi dosen dan PA (permintaan persetujuan KRS, langkah | Halaman notifikasi web hanya untuk mahasiswa |
| MOB-ANN-15 Notifikasi HR untuk dosen (keputusan pengajuan) | Notifikasi returned mungkin tidak terkirim |
| MOB-ANN-15 Notifikasi HR untuk dosen (keputusan pengajuan) | Tidak ada push |
| MOB-HR-01 Profil dosen mandiri (lihat) | Tidak memuat serdos/NIDK/jabatan akademik (ada di /me/hr/profile) |
| MOB-HR-03 Profil kepegawaian saya (Profil SDM) dan atribut dosen | Identitas: /me/hr bergantung employees.user_id (404 bila tidak ada), berbeda dari lecturers.user_id yang dipakai /lecturer-profile |
| MOB-HR-03 Profil kepegawaian saya (Profil SDM) dan atribut dosen | Hanya snapshot saat ini, tanpa riwayat |
| MOB-HR-04 Saldo cuti tahunan | Belum ada endpoint saldo terpisah; struktur field harus dibaca dari notes C6 |
| MOB-HR-05 Ajukan cuti/izin | Pegawai pengganti (substitute) disebut di dokumen tetapi tak ada field |
| MOB-HR-05 Ajukan cuti/izin | Tidak ada UI web |
| MOB-HR-06 Daftar dan status pengajuan cuti saya | Tanpa pagination; tanpa timeline persetujuan |
| MOB-HR-07 Kirim draf cuti | Tidak ada endpoint edit draf (updateDraft ada di service tanpa route) |
| MOB-HR-09 Revisi dan ajukan ulang pengajuan yang dikembalikan (cuti/HR | Tidak ada route revisi isi (updateDraft/resubmit di HrRequestService belum diekspos) |
| MOB-HR-09 Revisi dan ajukan ulang pengajuan yang dikembalikan (cuti/HR | Mapping hr_request -> approval_request id belum jelas di resource |
| MOB-HR-10 Pengajuan perubahan data pribadi (data_change) | Penerapan perubahan setelah disetujui ke master data belum diverifikasi |
| MOB-HR-11 Surat keterangan kerja/aktif mengajar (permintaan dokumen) | Tidak ada penerbitan/unduh surat setelah disetujui |
| MOB-HR-14 Daftar dan status pengajuan HR saya | Timeline persetujuan tidak dimuat |
| MOB-HR-15 Timeline persetujuan pengajuan saya | HrRequestResource belum menyertakan approval_request_id |
| MOB-HR-15 Timeline persetujuan pengajuan saya | Daftar tidak memuat histories |
| MOB-HR-17 Opsi/lookup form HR | Struktur respons belum diinspeksi |
| MOB-HR-18 Kehadiran kerja saya (presensi pegawai) | Tidak ada endpoint/tabel |
| MOB-HR-21 Riwayat pendidikan dosen dan unggah ijazah | Tidak ada /me/hr/educations |
| MOB-HR-22 Dokumen kepegawaian: unggah, verifikasi, kedaluwarsa, keleng | Tidak ada /me/hr/documents |
| MOB-HR-23 Riwayat karier (jabatan, pangkat, jabatan akademik) | Tidak ada /me/hr/* riwayat |
| MOB-HR-24 Pelatihan dan sertifikasi (termasuk serdos); catat pelatihan | Tidak ada /me/hr/* |
| MOB-HR-26 Catat aktivitas penelitian, pengabdian (PkM), dan publikasi | Tidak ada /me/hr/activities |
| MOB-HR-29 Beban mengajar dan riwayat mengajar dosen | Tidak ada endpoint riwayat lintas semester/agregat SKS |
| MOB-OTH-01 Daftar sesi ujian PMB yang ditugaskan kepada saya (penguji/p | Tidak ada filter tanggal/status; respons tidak membedakan tenant selain header X-University-ID |
| MOB-OTH-02 Presensi peserta ujian PMB (pengawas/penguji) | Butuh permission pmb_exam_attendance.update (ada pada role dosen) |
| MOB-OTH-02 Presensi peserta ujian PMB (pengawas/penguji) | Pindai QR membutuhkan paket kamera; offline tidak didukung |
| MOB-OTH-03 Input nilai seleksi PMB (nilai mentah/rubrik) oleh penguji | Penguji sengaja tak diberi pmb_applications.read, jadi tak ada data pendaftar selain nama/nomor peserta |
| MOB-OTH-03 Input nilai seleksi PMB (nilai mentah/rubrik) oleh penguji | Tidak ada penyimpanan draf lokal/offline |
| MOB-OTH-03 Input nilai seleksi PMB (nilai mentah/rubrik) oleh penguji | Catatan nilai tidak dapat dihapus: `ScoreService::write` memakai `$note ?: $score->note`, sehingga `note: null` mempertahankan catatan lama (aplikasi menolak pengosongan catatan dengan pesan jelas). Portal web `ScoringPage` juga memakai `scores[scores.length - 1]` tanpa memeriksa `examiner_user_id` — bagi pemegang `pmb_exams.update` atau saat ada baris impor bersama, skor penguji lain dapat tampil sebagai milik sendiri (aplikasi mobile sudah memilih baris milik pengguna). |
| MOB-OTH-04 Lihat pengajuan persetujuan saya dan statusnya | Mahasiswa umumnya daftar kosong (belum ada alur mahasiswa yang memakainya) |

## 5. Kebutuhan infrastruktur lintas fitur (belum ada)

| Kebutuhan | Dampak di aplikasi | Usulan |
|---|---|---|
| Registrasi token push (FCM) + pengirim kanal push | Tidak ada notifikasi push; aplikasi memakai pusat notifikasi in-app (polling jumlah belum dibaca). | Endpoint `POST/DELETE /me/devices/push-token` (kolom `user_devices.push_token` sudah ada), implementasi kanal `push` di modul Notification, berkas konfigurasi Firebase. |
| Deep link / App Links (reset sandi, notifikasi, pembayaran) | Tautan reset sandi & notifikasi menuju web; kembali dari Xendit tidak otomatis ke aplikasi. | Domain App Links (`assetlinks.json`), `redirect_url` Xendit ke skema aplikasi, field tautan notifikasi berbasis rute (bukan path web). |
| Kebijakan masa berlaku token | Token Sanctum tanpa kedaluwarsa; risiko bila perangkat hilang (dimitigasi kunci biometrik & cabut sesi). | `expiration` Sanctum untuk token mobile + refresh, atau rotasi saat login ulang. |
| Feed pengumuman untuk dosen | Dosen tidak punya feed pengumuman (izin `announcements.read` tidak diberikan). | Endpoint `GET /lecturer/announcements` atau target pengumuman per peran. |
| Daftar mahasiswa bimbingan untuk Dosen PA | PA hanya melihat antrean `krs-submissions`; tidak ada daftar/progres advisee. | Endpoint `/advising/students` (sudah dirancang di `academic-learning/08`). |
| Penegakan kepemilikan kelas | Flag `academic.lecturer_ownership` default OFF: dosen dapat mengakses nilai/presensi/ujian kelas lain lewat API. Aplikasi hanya menampilkan kelas dari `GET /teaching/classes`. | Aktifkan flag per tenant sebelum rilis. |
| Layanan mandiri SDM untuk dosen tanpa `employees.user_id` | `/me/hr/*` mengembalikan 404 bila akun dosen tidak tertaut ke data pegawai. | Samakan resolusi identitas dengan `/lecturer-profile`. |
| Kredensial Xendit | Tanpa `XENDIT_SECRET_KEY`, pembayaran online mengembalikan 409 `CHANNEL_NOT_CONNECTED`; aplikasi menampilkan kanal transfer manual. | Konfigurasi kredensial produksi + uji akun nyata. |
| Konfigurasi klien (fitur aktif, batas) | Tidak ada endpoint konfigurasi/feature flag yang dapat dibaca klien. | `data.features` di `/me` (dirancang di `academic-learning/08`). |
