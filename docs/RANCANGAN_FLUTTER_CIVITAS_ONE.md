# Rancangan Aplikasi Android Civitas One (Flutter)

> Dokumen ini adalah rancangan teknis lengkap untuk membangun aplikasi Android **Civitas One** dengan **Flutter**, diturunkan seluruhnya dari kode sumber aplikasi web React yang sudah ada di `frontend/`. Setiap fitur di dokumen ini punya padanan langsung di kode React yang dirujuk; bila tidak ada padanan langsung, alternatifnya dijelaskan secara eksplisit. Bagian yang tidak bisa dipastikan dari kode ditandai `⚠️ PERLU KONFIRMASI`.

---

## BAB 1 — Ringkasan Proyek

### 1.1 Tujuan Aplikasi

Civitas One adalah Sistem Informasi Akademik Universitas (SIAKAD) multi-tenant (multi-universitas) berbasis peran. Aplikasi web React yang ada saat ini (`frontend/`) melayani tiga kelompok pengguna dalam satu shell aplikasi:

1. **Operasional universitas** — admin/staf mengelola data akademik, keuangan, dan administrasi satu universitas (mahasiswa, dosen, pegawai, program studi, kelas, KRS, nilai, absensi, tagihan, beasiswa, skripsi, magang/MBKM, perpustakaan, alumni, pengumuman, laporan, persetujuan, dan pengaturan RBAC).
2. **Portal Mahasiswa** — mahasiswa melihat & mengelola data akademik miliknya sendiri (KRS, jadwal, KHS, transkrip, nilai, absensi, tugas, kuis, cuti, surat, beasiswa, tagihan, bimbingan, pengumuman, evaluasi dosen, wisuda).
3. **Platform Super Admin** — mengelola seluruh universitas tenant dari satu akun (CRUD universitas, statistik lintas-universitas, dan "Support Session" untuk masuk sementara ke konteks satu universitas demi keperluan dukungan teknis).

Aplikasi Android ini **meniru penuh** cakupan fitur web tersebut sebagai companion app native, memakai backend REST API yang sama (`VITE_API_BASE_URL`, default `http://localhost:8000/api/v1`), dengan tambahan kapabilitas khas Android modern (biometrik, notifikasi push, mode offline, dsb — lihat BAB 8).

⚠️ **PERLU KONFIRMASI**: dua modul di React (Portal Mahasiswa selain Profil, dan seluruh alur "Ajukan..." di Portal) memakai **data contoh statis (hardcoded)** dan belum tersambung ke endpoint API sungguhan — lihat komentar di `frontend/src/constants/nav.ts` ("Fase ini murni UI — halaman-halamannya belum tersambung ke API sungguhan") dan komentar serupa di tiap halaman Portal. Rancangan Flutter di dokumen ini tetap mendesain layer data/domain penuh (repository, model, provider) untuk modul-modul tersebut sesuai konvensi REST yang dipakai modul lain, tetapi implementasinya harus menunggu endpoint backend benar tersedia — ditandai secara eksplisit di tiap bagian terkait.

### 1.2 Target Pengguna

| Peran (role slug backend) | Deskripsi | Menu utama |
|---|---|---|
| `super_admin` | Mengelola seluruh tenant universitas di platform | Dashboard Platform, Manajemen Universitas, Keamanan Platform, Persetujuan, Pengaturan; menu bisnis tenant (Akademik dst.) muncul setelah memilih universitas lewat Tenant Switcher |
| `student` (mahasiswa) | Mahasiswa aktif satu universitas | Menu Portal Mahasiswa (Profil, Akademik, Perkuliahan, Pengajuan, Tagihan, Bimbingan, Pengumuman, Evaluasi Dosen, Wisuda) |
| Peran tenant lain (admin universitas, staf akademik, staf keuangan, dosen, dll — slug bebas sesuai RBAC) | Operasional satu universitas, akses granular per **permission** (bukan per nama role) | Menu bisnis tenant (Akademik, Mahasiswa, Dosen, Pegawai, Keuangan, Skripsi, Magang/MBKM, Perpustakaan, Alumni, Pengumuman, Laporan), Persetujuan, Pengaturan — tiap item disaring oleh permission slug pengguna |

Model akses BUKAN role-based UI sederhana, melainkan **permission-based**: `AuthUser.permissions: string[]` dari `GET /me` menentukan item menu & aksi apa yang terlihat (lihat `usePermission`, `RequirePermission`, `routePermissions.ts`). Hanya dua flag role yang dibaca eksplisit oleh UI: `roles.includes('super_admin')` dan `roles.includes('student')` (lihat `useIsSuperAdmin`, `useIsStudent`); role tenant lainnya sepenuhnya digerakkan oleh daftar `permissions`, bukan nama role — Flutter **wajib** mereplikasi mekanisme ini apa adanya (lihat BAB 3.4 & BAB 7).

### 1.3 Ruang Lingkup

- Replikasi penuh seluruh 71 halaman React (BAB 6) sebagai Screen Flutter, memakai endpoint REST yang sama (BAB 2.2).
- Replikasi penuh design system (warna, tipografi, radius, shadow — BAB 5) dan struktur navigasi/RBAC (BAB 7).
- Penambahan kapabilitas native Android yang relevan (BAB 8) yang tidak (dan tidak bisa) ada di versi web: biometrik, notifikasi push, mode offline, deep link, kamera/upload native, shortcut home screen, dsb.
- **Di luar ruang lingkup Fase 1**: fitur backend yang belum diekspos ke frontend React sama sekali (mis. refresh token — lihat BAB 11), dan business logic submit sungguhan di halaman Portal Mahasiswa yang di React masih murni tampilan (lihat 1.1).

### 1.4 Versi Flutter & Dart

| Item | Versi target |
|---|---|
| Flutter SDK | ≥ 3.35.x (channel stable) |
| Dart SDK | ≥ 3.9.0 |
| Kotlin (Android Gradle) | ≥ 2.0.x |
| Android Gradle Plugin | ≥ 8.5 |
| compileSdk | 35 (Android 15) |
| minSdk | **24** (Android 7.0 Nougat) — ditetapkan oleh permintaan proyek |
| targetSdk | 35 (Android 15) — wajib untuk Edge-to-edge default & predictive back (BAB 8.2) |

⚠️ **PERLU KONFIRMASI**: versi Flutter/Dart di atas adalah versi stabil terbaru yang diketahui pada saat dokumen ini disusun. Jalankan `flutter --version` & `flutter upgrade` saat bootstrap proyek untuk memastikan memakai rilis stable terbaru yang tersedia, karena rilis baru dapat muncul setelah dokumen ini ditulis.

### 1.5 Daftar Role Pengguna (ringkas, lihat 1.2)

1. Super Admin (`super_admin`)
2. Mahasiswa (`student`)
3. Admin/Staf Universitas — granular per permission (Akademik, Keuangan, Kemahasiswaan, dst.)

---

## BAB 2 — Hasil Analisis Aplikasi React

Analisis dilakukan dengan menelusuri seluruh isi `frontend/src/` — 71 halaman (`pages/`), 18 komponen `ui/`, 6 komponen `layout/`, 11 komponen `dashboard/`, 1 komponen `shared/`, 7 hooks, 3 Zustand store, 18 service module (`services/`), 18 file tipe (`types/`), konfigurasi Tailwind & token CSS, serta `App.tsx`/router.

### 2.1 Tabel Halaman React → Screen Flutter

| # | Halaman React (file) | Route React | Screen Flutter | Modul/Fitur |
|---|---|---|---|---|
| 1 | `auth/LoginPage.tsx` | `/login` | `LoginScreen` | Auth |
| 2 | `auth/ForgotPasswordPage.tsx` | `/forgot-password` | `ForgotPasswordScreen` | Auth |
| 3 | `auth/ResetPasswordPage.tsx` | `/reset-password` | `ResetPasswordScreen` | Auth |
| 4 | `dashboard/DashboardPage.tsx` | `/dashboard` (tenant biasa) | `DashboardScreen` | Dashboard |
| 5 | `platform/PlatformDashboardPage.tsx` | `/dashboard` (Super Admin, belum pilih tenant) | `PlatformDashboardScreen` | Platform |
| 6 | `portal/PortalDashboardPage.tsx` | `/dashboard` (mahasiswa) | `PortalDashboardScreen` | Portal |
| 7 | `errors/AccessDeniedPage.tsx` | (inline, semua path) | `AccessDeniedScreen` | Shared |
| 8 | `platform/UniversitiesPage.tsx` | `/platform/universitas` | `UniversitiesScreen` | Platform |
| 9 | `platform/SupportSessionsPage.tsx` | `/platform/keamanan` | `PlatformSecurityScreen` | Platform |
| 10 | `academic/StudentsPage.tsx` | `/mahasiswa` | `StudentsScreen` | Akademik |
| 11 | `academic/StudentDetailPage.tsx` | `/mahasiswa/:id` | `StudentDetailScreen` | Akademik |
| 12 | `academic/LecturersPage.tsx` | `/dosen` | `LecturersScreen` | Akademik |
| 13 | `academic/LecturerDetailPage.tsx` | `/dosen/:id` | `LecturerDetailScreen` | Akademik |
| 14 | `academic/EmployeesPage.tsx` | `/pegawai` | `EmployeesScreen` | Akademik |
| 15 | `academic/EmployeeDetailPage.tsx` | `/pegawai/:id` | `EmployeeDetailScreen` | Akademik |
| 16 | `academic/StudyProgramsPage.tsx` | `/akademik/program-studi` | `StudyProgramsScreen` | Akademik |
| 17 | `academic/StudyProgramDetailPage.tsx` | `/akademik/program-studi/:id` | `StudyProgramDetailScreen` | Akademik |
| 18 | `academic/ClassSectionsPage.tsx` | `/akademik/kelas-jadwal` | `ClassSectionsScreen` | Akademik |
| 19 | `academic/ClassSectionDetailPage.tsx` | `/akademik/kelas-jadwal/:id` | `ClassSectionDetailScreen` | Akademik |
| 20 | `academic/CurriculumsPage.tsx` | `/akademik/kurikulum` | `CurriculumsScreen` | Akademik |
| 21 | `academic/CurriculumDetailPage.tsx` | `/akademik/kurikulum/:id` | `CurriculumDetailScreen` | Akademik |
| 22 | `academic/CoursesPage.tsx` | `/akademik/mata-kuliah` | `CoursesScreen` | Akademik |
| 23 | `academic/CourseDetailPage.tsx` | `/akademik/mata-kuliah/:id` | `CourseDetailScreen` | Akademik |
| 24 | `academic/KrsPage.tsx` | `/akademik/krs` | `KrsScreen` | Akademik |
| 25 | `academic/GradesPage.tsx` | `/akademik/penilaian` | `GradesScreen` | Akademik |
| 26 | `academic/AttendancePage.tsx` | `/akademik/absensi` | `AttendanceScreen` | Akademik |
| 27 | `finance/InvoicesPage.tsx` | `/keuangan/tagihan` | `InvoicesScreen` | Keuangan |
| 28 | `finance/InvoiceDetailPage.tsx` | `/keuangan/tagihan/:id` | `InvoiceDetailScreen` | Keuangan |
| 29 | `finance/PaymentsPage.tsx` | `/keuangan/pembayaran` | `PaymentsScreen` | Keuangan |
| 30 | `scholarship/ScholarshipsPage.tsx` | `/keuangan/beasiswa` | `ScholarshipsScreen` | Keuangan |
| 31 | `scholarship/ScholarshipDetailPage.tsx` | `/keuangan/beasiswa/:id` | `ScholarshipDetailScreen` | Keuangan |
| 32 | `approvals/ApprovalRequestsPage.tsx` | `/persetujuan` | `ApprovalRequestsScreen` | Persetujuan |
| 33 | `approvals/ApprovalRequestDetailPage.tsx` | `/persetujuan/:id` | `ApprovalRequestDetailScreen` | Persetujuan |
| 34 | `approvals/ApprovalWorkflowsPage.tsx` | `/persetujuan/alur` | `ApprovalWorkflowsScreen` | Persetujuan |
| 35 | `thesis/ThesesPage.tsx` | `/skripsi` | `ThesesScreen` | Skripsi |
| 36 | `thesis/ThesisDetailPage.tsx` | `/skripsi/:id` | `ThesisDetailScreen` | Skripsi |
| 37 | `internship/InternshipsPage.tsx` | `/magang-mbkm` | `InternshipsScreen` | Magang/MBKM |
| 38 | `internship/InternshipDetailPage.tsx` | `/magang-mbkm/:id` | `InternshipDetailScreen` | Magang/MBKM |
| 39 | `library/BooksPage.tsx` | `/perpustakaan` | `BooksScreen` | Perpustakaan |
| 40 | `library/BookDetailPage.tsx` | `/perpustakaan/:id` | `BookDetailScreen` | Perpustakaan |
| 41 | `alumni/AlumniPage.tsx` | `/alumni` | `AlumniScreen` | Alumni |
| 42 | `alumni/AlumniDetailPage.tsx` | `/alumni/:id` | `AlumniDetailScreen` | Alumni |
| 43 | `announcement/AnnouncementsPage.tsx` | `/pengumuman` | `AnnouncementsScreen` | Pengumuman |
| 44 | `announcement/AnnouncementDetailPage.tsx` | `/pengumuman/:id` | `AnnouncementDetailScreen` | Pengumuman |
| 45 | `report/ReportsPage.tsx` | `/laporan` | `ReportsScreen` | Laporan |
| 46 | `settings/RolesPage.tsx` | `/pengaturan/roles` | `RolesScreen` | Pengaturan |
| 47 | `settings/PermissionsPage.tsx` | `/pengaturan/permissions` | `PermissionsScreen` | Pengaturan |
| 48 | `settings/UserRolesPage.tsx` | `/pengaturan/user-roles` | `UserRolesScreen` | Pengaturan |
| 49 | `settings/SystemSettingsPage.tsx` | `/pengaturan/system-settings` | `SystemSettingsScreen` | Pengaturan |
| 50 | `settings/FeatureFlagsPage.tsx` | `/pengaturan/feature-flags` | `FeatureFlagsScreen` | Pengaturan |
| 51 | `settings/NotificationsPage.tsx` | `/pengaturan/notifikasi` | `NotificationSettingsScreen` | Pengaturan |
| 52 | `settings/AuditLogPage.tsx` | `/pengaturan/audit-log` | `AuditLogScreen` | Pengaturan |
| 53 | `settings/SecuritySessionsPage.tsx` | `/pengaturan/keamanan` | `SecuritySessionsScreen` | Pengaturan |
| 54 | `portal/PortalProfilePage.tsx` | `/portal/profil` | `PortalProfileScreen` | Portal |
| 55 | `portal/PortalKrsPage.tsx` | `/portal/krs` | `PortalKrsScreen` | Portal |
| 56 | `portal/PortalSchedulePage.tsx` | `/portal/jadwal-kuliah` | `PortalScheduleScreen` | Portal |
| 57 | `portal/PortalKhsPage.tsx` | `/portal/khs` | `PortalKhsScreen` | Portal |
| 58 | `portal/PortalTranscriptPage.tsx` | `/portal/transkrip` | `PortalTranscriptScreen` | Portal |
| 59 | `portal/PortalGradesPage.tsx` | `/portal/nilai` | `PortalGradesScreen` | Portal |
| 60 | `portal/PortalAttendancePage.tsx` | `/portal/absensi` | `PortalAttendanceScreen` | Portal |
| 61 | `portal/PortalAssignmentsPage.tsx` | `/portal/tugas` | `PortalAssignmentsScreen` | Portal |
| 62 | `portal/PortalQuizzesPage.tsx` | `/portal/kuis` | `PortalQuizzesScreen` | Portal |
| 63 | `portal/PortalLeaveRequestPage.tsx` | `/portal/cuti` | `PortalLeaveRequestScreen` | Portal |
| 64 | `portal/PortalLetterRequestPage.tsx` | `/portal/surat` | `PortalLetterRequestScreen` | Portal |
| 65 | `portal/PortalScholarshipPage.tsx` | `/portal/beasiswa` | `PortalScholarshipScreen` | Portal |
| 66 | `portal/PortalInvoicesPage.tsx` | `/portal/tagihan` | `PortalInvoicesScreen` | Portal |
| 67 | `portal/PortalAcademicAdvisingPage.tsx` | `/portal/bimbingan-akademik` | `PortalAcademicAdvisingScreen` | Portal |
| 68 | `portal/PortalThesisAdvisingPage.tsx` | `/portal/bimbingan-skripsi` | `PortalThesisAdvisingScreen` | Portal |
| 69 | `portal/PortalAnnouncementsPage.tsx` | `/portal/pengumuman` | `PortalAnnouncementsScreen` | Portal |
| 70 | `portal/PortalLecturerEvaluationPage.tsx` | `/portal/evaluasi-dosen` | `PortalLecturerEvaluationScreen` | Portal |
| 71 | `portal/PortalGraduationPage.tsx` | `/portal/wisuda` | `PortalGraduationScreen` | Portal |

Rincian tiap-tiap screen (wireframe, widget, state, API, validasi, navigasi) ada di **BAB 6**.

### 2.2 Tabel Endpoint API

Base URL: `VITE_API_BASE_URL` (`.env`, default `http://localhost:8000/api/v1`). Semua request memakai header `Accept: application/json`; endpoint terautentikasi menambahkan `Authorization: Bearer <token>` otomatis lewat interceptor (`services/api.ts`). Ketika Super Admin sedang dalam mode tenant (Tenant Switcher aktif), interceptor menambahkan header `X-University-ID: <id>` pada **semua** request — pengguna tenant biasa tidak pernah mengirim header ini (backend resolve dari membership default mereka).

Format respons sukses: `{ success: true, message: string|null, data: T, meta?: {current_page, per_page, total, last_page} }`. Format error: `{ success: false, message: string, errors?: {field: string[]} }`, dipetakan jadi `NormalizedApiError { status, message, errors }` oleh response interceptor; status `401` otomatis memicu `clearSession()` (logout paksa).

| Method | Endpoint | Deskripsi | Auth | Dipakai di |
|---|---|---|---|---|
| POST | `/login` | Login, body `{email, password}` → `{token, user}` | Publik | authService.login |
| GET | `/me` | Profil user login → `{user, roles[], permissions[]}` | Bearer | authService.login (lanjutan), getCurrentUser |
| POST | `/logout` | Logout sesi aktif | Bearer | SecuritySessionsScreen (implisit lewat Header) |
| POST | `/logout-all` | Logout dari semua perangkat | Bearer | SecuritySessionsScreen |
| POST | `/forgot-password` | Kirim tautan reset password, body `{email}` | Publik | ForgotPasswordScreen |
| POST | `/reset-password` | Set password baru, body `{token, email, password, password_confirmation}` | Publik | ResetPasswordScreen |
| GET | `/sessions` | Daftar sesi login aktif | Bearer | SecuritySessionsScreen |
| DELETE | `/sessions/{id}` | Cabut satu sesi | Bearer | SecuritySessionsScreen |
| GET | `/devices` | Daftar perangkat terdaftar | Bearer | SecuritySessionsScreen |
| GET | `/dashboard` | Statistik dashboard tenant (query filter academic_term_id, faculty_id, study_program_id, date_from, date_to) — field summary/charts di-omit sesuai permission pengguna | Bearer | DashboardScreen |
| GET | `/students` | List mahasiswa (pagination + filter + search) | Bearer, `students.read` | StudentsScreen |
| GET | `/students/{id}` | Detail mahasiswa | Bearer, `students.read` | StudentDetailScreen |
| GET | `/students/{id}/transcript` | Transkrip (IPK, total SKS, IP per periode) | Bearer, `krs.read` | StudentDetailScreen |
| GET | `/lecturers` | List dosen | Bearer, `lecturers.read` | LecturersScreen |
| GET | `/lecturers/{id}` | Detail dosen | Bearer, `lecturers.read` | LecturerDetailScreen |
| GET | `/employees` | List pegawai | Bearer, `employees.read` | EmployeesScreen |
| GET | `/employees/{id}` | Detail pegawai | Bearer, `employees.read` | EmployeeDetailScreen |
| GET | `/study-programs` | List program studi | Bearer, `study_programs.read` | StudyProgramsScreen |
| GET | `/study-programs/{id}` | Detail program studi | Bearer, `study_programs.read` | StudyProgramDetailScreen |
| GET | `/class-sections` | List kelas & jadwal | Bearer, `classes.read` | ClassSectionsScreen |
| GET | `/class-sections/{id}` | Detail kelas | Bearer, `classes.read` | ClassSectionDetailScreen |
| POST | `/class-sections/{id}/attendances` | Rekam absensi 1 batch pertemuan, body `{meeting_number, meeting_date, entries:[{krs_item_id,status,notes?}]}` | Bearer, `attendance.create`/`update` | AttendanceScreen |
| GET | `/curriculums` | List kurikulum | Bearer, `curriculums.read` | CurriculumsScreen |
| GET | `/curriculums/{id}` | Detail kurikulum + daftar mata kuliah | Bearer, `curriculums.read` | CurriculumDetailScreen |
| GET | `/courses` | List mata kuliah | Bearer, `courses.read` | CoursesScreen |
| GET | `/courses/{id}` | Detail mata kuliah | Bearer, `courses.read` | CourseDetailScreen |
| GET | `/krs-items` | List KRS | Bearer, `krs.read` | KrsScreen, StudentDetailScreen |
| POST | `/krs-items` | Tambah KRS, body `{student_id, class_section_id}` | Bearer, `krs.create` | KrsScreen |
| PATCH | `/krs-items/{id}/drop` | Batalkan KRS | Bearer, `krs.update` | KrsScreen |
| GET | `/grades` | List nilai | Bearer, `grades.read` | GradesScreen, StudentDetailScreen |
| PUT | `/krs-items/{id}/grade` | Input/ubah nilai, body `{score, letter_grade?}` | Bearer, `grades.create`/`update` | GradesScreen |
| GET | `/attendances` | List absensi | Bearer, `attendance.read` | AttendanceScreen, StudentDetailScreen |
| GET | `/invoices` | List tagihan | Bearer, `invoices.read` | InvoicesScreen |
| GET | `/invoices/{id}` | Detail tagihan + riwayat pembayaran | Bearer, `invoices.read` | InvoiceDetailScreen |
| GET | `/payments` | List pembayaran (query top-level `date_from`,`date_to`) | Bearer, `invoices.read` | PaymentsScreen |
| GET | `/scholarships` | List beasiswa | Bearer, `scholarships.read` | ScholarshipsScreen |
| GET | `/scholarships/{id}` | Detail beasiswa + daftar pengajuan | Bearer, `scholarships.read` | ScholarshipDetailScreen |
| GET | `/scholarship-applications` | List pengajuan beasiswa | Bearer, `scholarships.read` | StudentDetailScreen |
| GET | `/approval-workflows` | List alur persetujuan | Bearer, `approval_workflows.read` | ApprovalWorkflowsScreen |
| GET | `/approval-workflows/{id}` | Detail alur | Bearer, `approval_workflows.read` | (tidak dipakai UI saat ini) |
| GET | `/approval-requests` | List pengajuan persetujuan (data milik sendiri atau scope lebih luas sesuai izin) | Bearer | ApprovalRequestsScreen |
| GET | `/approval-requests/{id}` | Detail pengajuan + riwayat | Bearer (self-scoped oleh API) | ApprovalRequestDetailScreen |
| POST | `/approval-request-steps/{id}/approve` | Setujui, body `{comment?}` | Bearer, harus `can_act` | ApprovalRequestDetailScreen |
| POST | `/approval-request-steps/{id}/reject` | Tolak, body `{comment}` (wajib) | Bearer, harus `can_act` | ApprovalRequestDetailScreen |
| POST | `/approval-request-steps/{id}/delegate` | Delegasikan, body `{delegate_to, comment?}` | Bearer, harus `can_act` | ApprovalRequestDetailScreen |
| GET | `/theses` | List skripsi | Bearer, `theses.read` | ThesesScreen |
| GET | `/theses/{id}` | Detail skripsi | Bearer, `theses.read` | ThesisDetailScreen |
| GET | `/internships` | List magang/MBKM | Bearer, `internships.read` | InternshipsScreen |
| GET | `/internships/{id}` | Detail magang/MBKM | Bearer, `internships.read` | InternshipDetailScreen |
| GET | `/books` | List buku | Bearer, `books.read` | BooksScreen |
| GET | `/books/{id}` | Detail buku + riwayat peminjaman | Bearer, `books.read` | BookDetailScreen |
| GET | `/alumni` | List alumni | Bearer, `alumni.read` | AlumniScreen |
| GET | `/alumni/{id}` | Detail alumni | Bearer, `alumni.read` | AlumniDetailScreen |
| GET | `/announcements` | List pengumuman | Bearer, `announcements.read` | AnnouncementsScreen |
| GET | `/announcements/{id}` | Detail pengumuman | Bearer, `announcements.read` | AnnouncementDetailScreen |
| GET | `/reports` | Daftar jenis laporan tersedia | Bearer, `reports.read` | ReportsScreen |
| GET | `/reports/{type}` | Data laporan (`?export=csv` untuk unduh CSV) | Bearer, `reports.read` | ReportsScreen |
| GET | `/roles` | List role | Bearer, `roles.read` | RolesScreen |
| GET | `/roles/{id}` | Detail role | Bearer, `roles.read` | — |
| POST | `/roles` | Tambah role, body `{name, description?}` | Bearer, `roles.create` | RolesScreen |
| PUT | `/roles/{id}` | Ubah role | Bearer, `roles.update` | RolesScreen |
| DELETE | `/roles/{id}` | Hapus role (bukan role sistem) | Bearer, `roles.delete` | RolesScreen |
| PUT | `/roles/{id}/permissions` | Sinkron permission role, body `{permission_ids:[]}` | Bearer, `roles.update` | RolesScreen |
| GET | `/permissions` | List permission | Bearer, `permissions.read` | PermissionsScreen, RolesScreen |
| POST | `/permissions` | Tambah permission, body `{name, resource, scope, action, description?}` | Bearer, `permissions.create` | PermissionsScreen |
| PUT | `/permissions/{id}` | Ubah permission (name/description) | Bearer, `permissions.update` | PermissionsScreen |
| DELETE | `/permissions/{id}` | Hapus permission (bukan sistem) | Bearer, `permissions.delete` | PermissionsScreen |
| GET | `/users` | Cari user (untuk assign role / delegasi) | Bearer | UserRolesScreen, ApprovalRequestDetailScreen |
| GET | `/users/{id}/roles` | Role milik user | Bearer | UserRolesScreen |
| POST | `/users/{id}/roles` | Assign role, body `{role_id, expires_at?}` | Bearer, `user_roles.create` | UserRolesScreen |
| DELETE | `/users/{id}/roles/{roleId}` | Cabut role dari user | Bearer, `user_roles.delete` | UserRolesScreen |
| GET | `/system-settings` | List pengaturan sistem | Bearer, `system_settings.read` | SystemSettingsScreen |
| PUT | `/system-settings/{id}` | Ubah nilai, body `{value}` | Bearer, `system_settings.update` | SystemSettingsScreen |
| GET | `/feature-flags` | List feature flag | Bearer, `feature_flags.read` | FeatureFlagsScreen |
| PUT | `/feature-flags/{id}` | Toggle, body `{is_enabled}` | Bearer, `feature_flags.update` | FeatureFlagsScreen |
| GET | `/notification-templates` | List template notifikasi | Bearer, `notification_templates.read` | NotificationSettingsScreen |
| POST | `/notification-templates` | Tambah template | Bearer, `notification_templates.create` | NotificationSettingsScreen |
| PUT | `/notification-templates/{id}` | Ubah template | Bearer, `notification_templates.update` | NotificationSettingsScreen |
| GET | `/notification-channels` | List channel (database/mail/push/whatsapp/sms) | Bearer | NotificationSettingsScreen |
| PUT | `/notification-channels/{id}` | Toggle channel, body `{is_enabled}` | Bearer, `notification_channels.update` | NotificationSettingsScreen |
| GET | `/notification-preferences` | Preferensi notifikasi milik user login | Bearer | NotificationSettingsScreen |
| POST | `/notification-preferences` | Upsert preferensi, body `{channel, notification_type?, is_enabled}` | Bearer | NotificationSettingsScreen |
| GET | `/audit-logs` | List audit log | Bearer, `audit_logs.read` | AuditLogScreen |
| GET | `/audit-logs/{id}` | Detail audit log | Bearer, `audit_logs.read` | — |
| POST | `/file-uploads` | Upload file, `multipart/form-data {file, is_public}` | Bearer | FileUploadField (dipakai lintas modul) |
| GET | `/file-uploads/{id}` | Metadata file | Bearer | FileUploadField |
| GET | `/file-uploads/{id}/download` | Unduh file (respons blob biner) | Bearer | FileUploadField |
| DELETE | `/file-uploads/{id}` | Hapus file | Bearer | FileUploadField |
| GET | `/platform/universities` | List universitas (Super Admin) | Bearer, `platform_universities.read` | UniversitiesScreen, TenantSwitcher |
| GET | `/platform/universities/{id}` | Detail universitas | Bearer, `platform_universities.read` | UniversitiesScreen |
| POST | `/platform/universities` | Tambah universitas | Bearer, `platform_universities.create` | UniversitiesScreen |
| PATCH | `/platform/universities/{id}` | Ubah universitas | Bearer, `platform_universities.update` | UniversitiesScreen |
| POST | `/platform/universities/{id}/activate` | Aktifkan universitas | Bearer, `platform_universities.update` | UniversitiesScreen |
| POST | `/platform/universities/{id}/suspend` | Suspend universitas | Bearer, `platform_universities.update` | UniversitiesScreen |
| GET | `/platform/statistics` | Statistik lintas-universitas | Bearer, Super Admin | PlatformDashboardScreen |
| GET | `/platform/support-sessions` | Riwayat Support Session | Bearer, `support_sessions.read` | PlatformSecurityScreen |
| POST | `/platform/support-sessions` | Mulai Support Session, body `{university_id, reason}` | Bearer, Super Admin | Tenant Switcher |
| POST | `/platform/support-sessions/{id}/end` | Akhiri Support Session | Bearer, Super Admin | Tenant Switcher, TenantModeBanner |

Total **~85 endpoint**. Semua endpoint list (kecuali `/dashboard`, `/platform/statistics`, `/reports`, `/reports/{type}`, `/notification-channels`, `/notification-preferences`, `/sessions`, `/devices`, `/users/{id}/roles`) memakai kontrak query yang seragam: `page`, `per_page`, `search`, `filter[kolom]=nilai`, `sort` (lihat `utils/listParams.ts` — `toQueryParams`), dan membalas `{data:[], meta:{current_page,per_page,total,last_page}}`.

### 2.3 Ringkasan Design System yang Diekstrak

Sumber: `frontend/tailwind.config.ts`, `frontend/src/styles/tokens.css`, `frontend/src/index.css`.

**Warna brand** — diambil dari lambang Atma Jaya (perisai hijau tua + burung merpati emas/oranye, lihat komentar di `tailwind.config.ts`):

| Token | Light | Dark |
|---|---|---|
| `primary` (hijau) | `#166534` | `#3f9e68` |
| `primary-hover` | `#114f29` | `#68bb88` |
| `accent` (oranye) | `#d9791a` | `#f0ad53` |
| `accent-hover` | `#b35f12` | `#f6c989` |
| `success` | `#166534` | `#3f9e68` |
| `warning` | `#ca8a04` | `#fbbf24` |
| `danger` | `#dc2626` | `#f87171` |
| `info` | `#0284c7` | `#38bdf8` |
| `background` | `#f8fafc` | `#0a0f1a` |
| `surface` | `#ffffff` | `#111827` |
| `surface-hover` | `#f1f5f9` | `#1a2333` |
| `border` | `#e2e8f0` | `#26324a` |
| `border-strong` | `#cbd5e1` | `#35435f` |
| `text-primary` | `#0f172a` | `#f1f5f9` |
| `text-secondary` | `#475569` | `#94a3b8` |
| `text-tertiary` | `#94a3b8` | `#64748b` |
| `text-inverted` | `#f8fafc` | `#0f172a` |

Skala penuh `primary` (50→950) dan `accent` (50→900) juga didefinisikan statis di `tailwind.config.ts` (tidak ikut berubah di dark mode — dipakai untuk nuansa Badge/Avatar/hover state, lihat BAB 5.1).

**Tipografi**: font `Inter` (dimuat dari Google Fonts di `index.html`, weight 400/500/600/700), fallback `ui-sans-serif, system-ui, sans-serif`.

**Radius**: default Tailwind (`rounded-lg`=0.5rem umum dipakai di komponen), kustom `xl`=0.875rem (14px), `2xl`=1.125rem (18px) — dipakai untuk Card & Modal.

**Shadow**: `card` = `0 1px 2px rgb(0 0 0/0.04), 0 1px 3px 1px rgb(0 0 0/0.06)`; `popover` = `0 4px 6px -1px rgb(0 0 0/0.08), 0 10px 15px -3px rgb(0 0 0/0.08)`.

**Spacing**: mengikuti skala default Tailwind (4px grid: 1=4px, 2=8px, 3=12px, 4=16px, 5=20px, 6=24px...) — dipakai konsisten di seluruh komponen (`p-5`, `gap-4`, `px-4 py-2.5`, dst).

**Ikon**: `lucide-react` — dipetakan ke paket Dart `lucide_icons` agar 1:1 nama ikon (lihat BAB 5.4).

**Dark mode**: toggle manual (`ThemeToggle`, `useThemeStore`) — hanya **light/dark**, default awal dari `prefers-color-scheme`; persisten via `localStorage` (`civitasone-theme`). Diterapkan lewat class `.dark` di `<html>` (`useThemeSync`), bukan lewat mekanisme `prefers-color-scheme` runtime — artinya Flutter **wajib menambah opsi ketiga "Ikuti Sistem"** karena itu mandat BAB 5 dokumen ini (kapabilitas native yang wajar untuk Android, bukan fitur yang diada-adakan — lihat BAB 5.3).

### 2.4 Autentikasi & Role — Ringkasan Mekanisme

- **Login**: `POST /login` (email+password) → token, lalu `GET /me` (pakai token itu) → `{user, roles[], permissions[]}`. Digabung jadi `AuthSession{user: AuthUser, token}` dan disimpan di `authStore` (Zustand + `persist` ke `localStorage`, key `civitasone-auth`).
- **Header auth**: tiap request melampirkan `Authorization: Bearer <token>` dari `authStore.session.token` lewat axios request interceptor.
- **401 global**: response interceptor menghapus sesi (`clearSession()`) begitu server balas 401 di request manapun — efeknya `ProtectedRoute` otomatis redirect ke `/login` di render berikutnya.
- **Otorisasi UI**: `usePermission(slug | slug[])` — OR-match terhadap `authStore.session.user.permissions`. Dipakai untuk: (a) route guard (`RequirePermission`, menampilkan `AccessDeniedPage` inline, URL tidak berubah), (b) menyembunyikan item sidebar (`Sidebar.tsx` → `filterNavItems`), (c) menyembunyikan tombol aksi per halaman (mis. tombol "Tambah", "Ubah", "Hapus").
- **Deteksi role khusus**: hanya dua yang dibaca eksplisit — `roles.includes('super_admin')` dan `roles.includes('student')`. Ini menentukan **set menu & dashboard mana** yang dirender (Platform vs Portal vs tenant biasa), independen dari sistem permission granular di atas.
- **Multi-tenant (Super Admin)**: `tenantStore` (Zustand+persist, key `civitasone-tenant`) menyimpan `selectedUniversity{id,name}` dan `supportSessionId`. Diisi lewat `TenantSwitcher` (modal pilih universitas + alasan wajib → `POST /platform/support-sessions`). Selama aktif, **semua** request menyertakan header `X-University-ID`, sebuah `TenantModeBanner` permanen tampil di seluruh halaman, dan menu bisnis tenant (Akademik dst.) disisipkan ke menu Super Admin. Keluar mode tenant memanggil `POST /platform/support-sessions/{id}/end`.
- **Lupa/atur ulang password**: alur token-by-email standar (`/forgot-password` → email berisi link `?token=&email=` → `/reset-password`).
- **Manajemen sesi mandiri (self-service)**: halaman "Keamanan" (`/pengaturan/keamanan`) — daftar sesi aktif (`GET /sessions`, cabut via `DELETE /sessions/{id}`), daftar perangkat (`GET /devices`), dan "Keluar dari Semua Perangkat" (`POST /logout-all`). Halaman ini **tidak** memerlukan permission slug — hanya butuh login (self-service, bukan resource RBAC).

---

## BAB 3 — Arsitektur Aplikasi

### 3.1 Pola Arsitektur: Clean Architecture

Aplikasi dibagi 3 lapisan per fitur, ditambah `core/` lintas-fitur:

- **Presentation** — Widget (Screen/Page), Riverpod `Notifier`/`AsyncNotifier` per screen atau per grup state, dan model UI-state ringan (mis. `ListUiState<T>`). Tidak pernah memanggil Dio/Repository langsung — selalu lewat Notifier.
- **Domain** — Entity murni Dart (immutable, tanpa dependency Flutter/Dio), abstrak `Repository` (interface), dan `UseCase` (opsional, dipakai bila ada logika non-trivial di luar CRUD, mis. `SubmitApprovalActionUseCase`, `RecordAttendanceBatchUseCase`). Untuk modul CRUD sederhana (mayoritas modul akademik/master data), Notifier boleh memanggil Repository langsung tanpa UseCase perantara agar tidak over-engineering.
- **Data** — `RemoteDataSource` (Dio, 1:1 dengan tabel endpoint BAB 2.2), `LocalDataSource` (Hive, untuk cache **read-only** data referensi — lihat BAB 8.6), `Model` (subclass Entity + `fromJson`/`toJson`, lihat BAB 10), dan `RepositoryImpl` yang mengorkestrasi Remote+Local (cache-first untuk data referensi, network-first untuk data transaksional).

Kaidah dependency: `presentation → domain ← data`. `domain` tidak pernah mengimpor apa pun dari `data` atau `presentation`.

### 3.2 State Management: Riverpod

**Dipilih: Riverpod** (`flutter_riverpod` + `riverpod_annotation`, code-gen via `riverpod_generator`/`build_runner`), bukan BLoC.

**Alasan**:
1. **Kecocokan pola sumber**: React memakai hooks generik yang dipakai berulang di puluhan halaman — `useFetch` (fetch-on-mount + refetch + error) dan `usePaginatedList` (page/search/filter/sort + meta). Ini peta hampir 1:1 ke `AsyncNotifier`/`FutureProvider.family` dan sebuah `ListNotifier<T>` generik di Riverpod — sementara BLoC memaksa mendefinisikan Event/State class terpisah untuk tiap pola yang sudah generik di sumbernya, menambah boilerplate tanpa manfaat baru.
2. **Tidak butuh BuildContext** untuk membaca state di luar widget tree (mis. interceptor Dio perlu baca token terkini — sama seperti `useAuthStore.getState()` dipakai langsung di `services/api.ts` React, bukan lewat komponen). `ref.read(authProvider)` dari `ProviderContainer` global cocok untuk pola ini; BLoC/Cubit tidak didesain untuk dibaca dari luar widget tree.
3. **Granularitas rebuild** setara `Zustand` selector (`useAuthStore((s) => s.session)`) — `ref.watch(provider.select(...))` memberi kontrol yang sama presisinya.
4. **Testability**: Notifier di-`override` lewat `ProviderScope(overrides:[...])` di widget test tanpa perlu wrapper BLoC provider tambahan.

*(Jika tim lebih familiar BLoC, arsitektur di BAB 3.1/3.3/3.4 tetap berlaku — cukup ganti Notifier→Cubit/Bloc dan Provider→BlocProvider; keputusan ini didokumentasikan agar konsisten, bukan mutlak tak bisa diubah.)*

### 3.3 Diagram Alur Data

```
┌──────────────────────────────────────────────────────────────────────┐
│                              PRESENTATION                             │
│   Screen (ConsumerWidget)  ──ref.watch──▶  Notifier / AsyncNotifier   │
│        ▲                                          │                   │
│        └──────────────── rebuild on state change ──┘                  │
└───────────────────────────────┼───────────────────────────────────────┘
                                 │ memanggil method (mis. loadPage(), submit())
┌───────────────────────────────▼───────────────────────────────────────┐
│                                DOMAIN                                  │
│   Repository (abstract interface)   Entity (Student, Invoice, ...)     │
│   UseCase (opsional, logic non-CRUD)                                   │
└───────────────────────────────┼───────────────────────────────────────┘
                                 │ diimplementasikan oleh
┌───────────────────────────────▼───────────────────────────────────────┐
│                                 DATA                                   │
│   RepositoryImpl                                                       │
│     ├──▶ RemoteDataSource ──▶ Dio (+ AuthInterceptor, TenantInterceptor,│
│     │                          ErrorInterceptor)  ──▶  REST API        │
│     └──▶ LocalDataSource  ──▶ Hive (cache data referensi, read-only)   │
│   Model (JSON ⇄ Entity, fromJson/toJson/copyWith)                      │
└──────────────────────────────────────────────────────────────────────┘
```

Untuk pola list berpaginasi (padanan `usePaginatedList`), alurnya:

```
Screen ──watch──▶ studentsListProvider (family by ListQuery)
                        │
                        ▼
                 ListNotifier<Student>
                   .loadPage(page) ──▶ StudentRepository.index(ListQuery)
                                            │
                                            ▼
                                  StudentRemoteDataSource.index(query)
                                            │  GET /students?page=&per_page=&search=&filter[x]=&sort=
                                            ▼
                                      ApiSuccessResponse<List<StudentModel>>
                                            │
                          ◀── PaginatedResult<Student>{data, meta} ──┘
Screen ◀── state: ListUiState.loading | .data(items, meta) | .error(message) | .empty
```

### 3.4 Dependency Injection & Error Handling Terpusat

**DI**: Riverpod provider **adalah** mekanisme DI — tidak perlu paket DI tambahan (`get_it`, dsb). Struktur berlapis:

```dart
final dioProvider = Provider<Dio>((ref) => buildDio(ref));                 // core/network
final secureStorageProvider = Provider((ref) => const FlutterSecureStorage());
final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepositoryImpl(ref.watch(dioProvider), ref.watch(secureStorageProvider)),
);
final authControllerProvider = AsyncNotifierProvider<AuthController, AuthSession?>(AuthController.new);
```

**Error handling terpusat** — meniru `services/api.ts` React persis:

```dart
// core/network/dio_client.dart
Dio buildDio(Ref ref) {
  final dio = Dio(BaseOptions(baseUrl: Env.apiBaseUrl, connectTimeout: const Duration(seconds: 15)));

  dio.interceptors.add(InterceptorsWrapper(
    onRequest: (options, handler) async {
      final token = await ref.read(secureStorageProvider).read(key: StorageKeys.token);
      if (token != null) options.headers['Authorization'] = 'Bearer $token';

      final universityId = ref.read(tenantControllerProvider).selectedUniversity?.id;
      if (universityId != null) options.headers['X-University-ID'] = universityId;

      handler.next(options);
    },
    onError: (DioException error, handler) async {
      if (error.response?.statusCode == 401) {
        await ref.read(authControllerProvider.notifier).clearSession();
      }
      handler.next(error);
    },
  ));

  return dio;
}

// Dipetakan di setiap RemoteDataSource lewat try/catch tunggal:
class ApiFailure implements Exception {
  const ApiFailure({required this.status, required this.message, this.errors});
  final int? status;
  final String message;
  final Map<String, List<String>>? errors;

  factory ApiFailure.fromDioException(DioException e) => ApiFailure(
        status: e.response?.statusCode,
        message: e.response?.data?['message'] ?? 'Terjadi kesalahan. Silakan coba lagi.',
        errors: (e.response?.data?['errors'] as Map?)?.map(
          (k, v) => MapEntry(k as String, List<String>.from(v as List)),
        ),
      );
}
```

Semua Notifier menangkap `ApiFailure` dan mengekspos `errors` map ke form (padanan `applyServerErrors` React — lihat BAB 6 pola form) serta `message` ke Snackbar/inline `Alert` widget.

---

## BAB 4 — Struktur Folder Project

```
lib/
├── main.dart                       # entry point, ProviderScope, bootstrap (dotenv, Hive.init)
├── main_dev.dart                   # entry point flavor dev (--dart-define=FLAVOR=dev)
├── main_staging.dart                # entry point flavor staging
├── main_prod.dart                  # entry point flavor prod
├── app.dart                        # MaterialApp.router, ThemeData, locale, GoRouter root
│
├── core/
│   ├── config/
│   │   ├── env.dart                 # Env.apiBaseUrl dari --dart-define, padanan .env React
│   │   └── flavor.dart
│   ├── network/
│   │   ├── dio_client.dart          # buildDio() + interceptor auth/tenant/error (3.4)
│   │   ├── api_failure.dart         # padanan NormalizedApiError
│   │   ├── api_envelope.dart        # ApiSuccessResponse<T>, ApiPaginationMeta, ListQuery
│   │   └── list_query_mapper.dart   # padanan utils/listParams.ts (toQueryParams)
│   ├── storage/
│   │   ├── secure_storage.dart      # token, refresh flag → flutter_secure_storage
│   │   ├── local_prefs.dart         # theme/locale/tenant pilihan → shared_preferences
│   │   └── hive_boxes.dart          # cache read-only data referensi (BAB 8.6)
│   ├── router/
│   │   ├── app_router.dart          # GoRouter: seluruh route + ShellRoute (7.1)
│   │   ├── route_paths.dart         # padanan constants/routes.ts (ROUTES)
│   │   ├── route_guard.dart         # padanan ProtectedRoute/PublicOnlyRoute
│   │   └── route_permissions.dart   # padanan routes/routePermissions.ts
│   ├── theme/
│   │   ├── app_colors.dart          # token warna BAB 5.1 (light+dark)
│   │   ├── app_theme.dart           # ThemeData light/dark, ColorScheme.fromSeed (5.2)
│   │   ├── app_text_theme.dart      # TextTheme dari Inter (5.4)
│   │   └── theme_controller.dart    # ThemeMode state (persisted) — padanan themeStore.ts
│   ├── permission/
│   │   ├── permission_gate.dart     # padanan RequirePermission/usePermission
│   │   └── nav_items.dart           # padanan constants/nav.ts (NAV_ITEMS dkk)
│   ├── widgets/                     # padanan components/ui/* (BAB 5.6, dipakai lintas fitur)
│   │   ├── app_button.dart
│   │   ├── app_badge.dart
│   │   ├── app_text_field.dart
│   │   ├── app_dropdown_field.dart
│   │   ├── app_card.dart
│   │   ├── app_data_table.dart      # atau list_view berbasis Card di layar sempit (6.0)
│   │   ├── app_modal.dart           # showModalBottomSheet/Dialog wrapper
│   │   ├── app_alert.dart
│   │   ├── app_empty_state.dart
│   │   ├── app_skeleton.dart
│   │   ├── app_avatar.dart
│   │   ├── app_breadcrumb.dart
│   │   ├── app_pagination_footer.dart
│   │   └── file_upload_field.dart   # padanan components/shared/FileUploadField.tsx
│   ├── utils/
│   │   ├── formatters.dart          # padanan utils/formatters.ts (angka, Rupiah, tanggal relatif)
│   │   ├── humanize_slug.dart
│   │   └── validators.dart          # padanan skema Zod (email, min length, dst)
│   └── l10n/
│       ├── app_id.arb
│       └── app_en.arb
│
├── features/
│   ├── auth/
│   │   ├── data/    (auth_remote_data_source.dart, auth_repository_impl.dart, models: auth_user_model.dart, auth_session_model.dart, user_session_model.dart, user_device_model.dart)
│   │   ├── domain/  (entities: auth_user.dart, auth_session.dart; auth_repository.dart)
│   │   └── presentation/
│   │       ├── controllers/ (auth_controller.dart, login_form_controller.dart)
│   │       └── screens/ (login_screen.dart, forgot_password_screen.dart, reset_password_screen.dart,
│   │                      security_sessions_screen.dart)
│   ├── dashboard/
│   │   ├── data/ dashboard_remote_data_source.dart, dashboard_repository_impl.dart, models/dashboard_response_model.dart
│   │   ├── domain/ dashboard_repository.dart, entities/*.dart (dashboard_summary, dashboard_charts, ...)
│   │   └── presentation/
│   │       ├── controllers/dashboard_controller.dart
│   │       ├── screens/dashboard_screen.dart
│   │       └── widgets/ (summary_cards_grid.dart, student_growth_chart.dart, faculty_distribution_chart.dart,
│   │                     payment_trend_chart.dart, payment_status_chart.dart, student_status_chart.dart,
│   │                     staff_by_unit_chart.dart, active_classes_by_program_chart.dart, approval_status_chart.dart,
│   │                     recent_activity_list.dart, academic_agenda_list.dart, pending_approvals_table.dart,
│   │                     dashboard_filter_bar.dart)
│   ├── platform/                    # Super Admin: universities, support sessions, platform dashboard
│   │   ├── data/ ...
│   │   ├── domain/ ...
│   │   └── presentation/screens/ (platform_dashboard_screen.dart, universities_screen.dart,
│   │                              platform_security_screen.dart)
│   ├── tenancy/                     # Tenant Switcher + TenantModeBanner (dipakai lintas fitur, bukan screen sendiri)
│   │   ├── data/ university_remote_data_source.dart (index only, dipakai TenantSwitcher)
│   │   ├── domain/ tenant.dart
│   │   └── presentation/ (tenant_controller.dart, tenant_switcher.dart, tenant_mode_banner.dart)
│   ├── academic/                    # Mahasiswa, Dosen, Pegawai, ProgramStudi, Kelas, Kurikulum, MataKuliah, KRS, Nilai, Absensi
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   │       ├── controllers/
│   │       └── screens/ (students_screen.dart, student_detail_screen.dart, lecturers_screen.dart, ...
│   │                     17 screen sesuai tabel 2.1)
│   ├── finance/                     # Invoices, Payments
│   ├── scholarship/                 # Scholarships (admin) — dipakai juga oleh academic/student_detail & portal
│   ├── approval/                    # ApprovalRequests, ApprovalRequestDetail, ApprovalWorkflows
│   ├── thesis/
│   ├── internship/
│   ├── library/
│   ├── alumni/
│   ├── announcement/
│   ├── report/
│   ├── settings/                    # Roles, Permissions, UserRoles, SystemSettings, FeatureFlags,
│   │                                # NotificationSettings, AuditLog (SecuritySessions ada di auth/)
│   ├── file_upload/                 # FileUpload repository dipakai lintas modul (shared)
│   └── portal/                      # 19 screen Portal Mahasiswa (lihat 2.1) — ⚠️ backend belum ada, lihat 1.1
│       ├── data/ (siap, menunggu endpoint)
│       ├── domain/
│       └── presentation/screens/
│
└── shared/
    ├── entities/ (list_params.dart, paginated_result.dart, api_pagination_meta.dart — dipakai semua fitur)
    └── extensions/ (date_time_ext.dart, string_ext.dart)

test/
├── core/ (dio_client_test.dart, list_query_mapper_test.dart, ...)
├── features/<fitur>/data/ (repository_impl_test.dart per fitur)
├── features/<fitur>/presentation/ (controller_test.dart per fitur)
└── widget/ (golden/widget test per screen kunci — BAB 13)

integration_test/
├── login_flow_test.dart
├── approval_flow_test.dart
└── student_crud_flow_test.dart
```

Prinsip pembagian: **per-fitur** (`features/<nama-modul>/{data,domain,presentation}`), sejalan dengan pembagian folder `pages/<modul>/` di React — memudahkan pemetaan langsung saat porting satu modul demi satu modul (BAB 12).

---

## BAB 5 — Design System & Theming

### 5.1 Palet Warna Lengkap (Light & Dark)

Diturunkan 1:1 dari `tokens.css` (BAB 2.3) + skala statis `tailwind.config.ts`:

```dart
// core/theme/app_colors.dart
import 'package:flutter/material.dart';

abstract final class AppColorsLight {
  static const primary = Color(0xFF166534);
  static const primaryHover = Color(0xFF114F29);
  static const accent = Color(0xFFD9791A);
  static const accentHover = Color(0xFFB35F12);
  static const success = Color(0xFF166534);
  static const warning = Color(0xFFCA8A04);
  static const danger = Color(0xFFDC2626);
  static const info = Color(0xFF0284C7);
  static const background = Color(0xFFF8FAFC);
  static const surface = Color(0xFFFFFFFF);
  static const surfaceHover = Color(0xFFF1F5F9);
  static const border = Color(0xFFE2E8F0);
  static const borderStrong = Color(0xFFCBD5E1);
  static const textPrimary = Color(0xFF0F172A);
  static const textSecondary = Color(0xFF475569);
  static const textTertiary = Color(0xFF94A3B8);
  static const textInverted = Color(0xFFF8FAFC);
}

abstract final class AppColorsDark {
  static const primary = Color(0xFF3F9E68);
  static const primaryHover = Color(0xFF68BB88);
  static const accent = Color(0xFFF0AD53);
  static const accentHover = Color(0xFFF6C989);
  static const success = Color(0xFF3F9E68);
  static const warning = Color(0xFFFBBF24);
  static const danger = Color(0xFFF87171);
  static const info = Color(0xFF38BDF8);
  static const background = Color(0xFF0A0F1A);
  static const surface = Color(0xFF111827);
  static const surfaceHover = Color(0xFF1A2333);
  static const border = Color(0xFF26324A);
  static const borderStrong = Color(0xFF35435F);
  static const textPrimary = Color(0xFFF1F5F9);
  static const textSecondary = Color(0xFF94A3B8);
  static const textTertiary = Color(0xFF64748B);
  static const textInverted = Color(0xFF0F172A);
}

/// Skala statis `primary` (dipakai Badge/Avatar/hover — sama di kedua tema, dari tailwind.config.ts)
abstract final class AppColorsPrimaryScale {
  static const s50 = Color(0xFFEAF6EE);
  static const s100 = Color(0xFFCDEAD6);
  static const s200 = Color(0xFF9CD5AF);
  static const s300 = Color(0xFF68BB88);
  static const s400 = Color(0xFF3F9E68);
  static const s500 = Color(0xFF237F4C);
  static const s600 = Color(0xFF166534);
  static const s700 = Color(0xFF114F29);
  static const s800 = Color(0xFF0D3D20);
  static const s900 = Color(0xFF0A2F19);
  static const s950 = Color(0xFF051A0E);
}

abstract final class AppColorsAccentScale {
  static const s50 = Color(0xFFFDF3E7);
  static const s100 = Color(0xFFFBE4C4);
  static const s200 = Color(0xFFF6C989);
  static const s300 = Color(0xFFF0AD53);
  static const s400 = Color(0xFFE9932E);
  static const s500 = Color(0xFFD9791A);
  static const s600 = Color(0xFFB35F12);
  static const s700 = Color(0xFF8C4A0F);
  static const s800 = Color(0xFF66360B);
  static const s900 = Color(0xFF452507);
}
```

### 5.2 Implementasi Material 3 + `ColorScheme.fromSeed`

```dart
// core/theme/app_theme.dart
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'app_colors.dart';

abstract final class AppTheme {
  static ThemeData light({ColorScheme? dynamicScheme}) => _build(
        brightness: Brightness.light,
        colors: AppColorsLight.primary,
        scheme: (dynamicScheme?.brightness == Brightness.light ? dynamicScheme : null) ??
            ColorScheme.fromSeed(
              seedColor: AppColorsLight.primary,
              brightness: Brightness.light,
            ).copyWith(
              secondary: AppColorsLight.accent,
              error: AppColorsLight.danger,
              surface: AppColorsLight.surface,
            ),
      );

  static ThemeData dark({ColorScheme? dynamicScheme}) => _build(
        brightness: Brightness.dark,
        colors: AppColorsDark.primary,
        scheme: (dynamicScheme?.brightness == Brightness.dark ? dynamicScheme : null) ??
            ColorScheme.fromSeed(
              seedColor: AppColorsDark.primary,
              brightness: Brightness.dark,
            ).copyWith(
              secondary: AppColorsDark.accent,
              error: AppColorsDark.danger,
              surface: AppColorsDark.surface,
            ),
      );

  static ThemeData _build({required Brightness brightness, required Color colors, required ColorScheme scheme}) {
    final isDark = brightness == Brightness.dark;
    final palette = isDark ? AppColorsDark.textPrimary : AppColorsLight.textPrimary;
    final background = isDark ? AppColorsDark.background : AppColorsLight.background;
    final borderColor = isDark ? AppColorsDark.border : AppColorsLight.border;

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: scheme,
      scaffoldBackgroundColor: background,
      fontFamily: GoogleFonts.inter().fontFamily,
      textTheme: AppTextTheme.build(palette),
      cardTheme: CardThemeData(
        color: scheme.surface,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14), // xl = 0.875rem
          side: BorderSide(color: borderColor),
        ),
      ),
      dialogTheme: DialogThemeData(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)), // 2xl = 1.125rem
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: scheme.surface,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: borderColor)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      ),
      appBarTheme: AppBarTheme(backgroundColor: scheme.surface, foregroundColor: palette, elevation: 0),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: scheme.primary,
          foregroundColor: scheme.onPrimary,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          minimumSize: const Size(0, 40),
        ),
      ),
    );
  }
}
```

### 5.3 Dark Mode: Light / Dark / Ikuti Sistem

React hanya punya toggle light/dark manual (BAB 2.3). Karena BAB 5 dokumen ini mewajibkan mode **"Ikuti Sistem"**, `ThemeMode` diperluas jadi 3 opsi native Flutter (`ThemeMode.light/.dark/.system`), tetap **persisten** seperti perilaku `useThemeStore` React (persist ke storage, bukan cuma in-memory):

```dart
// core/theme/theme_controller.dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

const _prefsKey = 'civitasone-theme'; // sama dengan key localStorage React, untuk konsistensi konsep

class ThemeController extends Notifier<ThemeMode> {
  @override
  ThemeMode build() {
    _restore();
    return ThemeMode.system; // default: ikuti sistem (React default: prefers-color-scheme sekali saat load)
  }

  Future<void> _restore() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString(_prefsKey);
    if (saved != null) state = ThemeMode.values.byName(saved);
  }

  Future<void> setTheme(ThemeMode mode) async {
    state = mode;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefsKey, mode.name);
  }
}

final themeControllerProvider = NotifierProvider<ThemeController, ThemeMode>(ThemeController.new);
```

Contoh pemakaian di `app.dart` (padanan `useThemeSync` + `App()` React):

```dart
class CivitasOneApp extends ConsumerWidget {
  const CivitasOneApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final themeMode = ref.watch(themeControllerProvider);
    final dynamicColors = ref.watch(dynamicColorSchemeProvider); // BAB 8.1

    return MaterialApp.router(
      title: 'CivitasOne',
      themeMode: themeMode,
      theme: AppTheme.light(dynamicScheme: dynamicColors?.light),
      darkTheme: AppTheme.dark(dynamicScheme: dynamicColors?.dark),
      routerConfig: ref.watch(appRouterProvider),
      locale: ref.watch(localeControllerProvider), // BAB 8.12
      supportedLocales: const [Locale('id'), Locale('en')],
      localizationsDelegates: AppLocalizations.localizationsDelegates,
    );
  }
}
```

`ThemeToggle` React (satu tombol toggle 2 state) menjadi widget pengaturan 3-opsi di `SecuritySessionsScreen`/pengaturan tampilan mobile (`SegmentedButton<ThemeMode>` dengan label "Terang / Gelap / Ikuti Sistem").

### 5.4 Tipografi, Spacing, Radius, Elevation

**TextTheme** (skala Tailwind `text-xs`→`text-2xl` dipetakan ke Material `TextTheme`):

```dart
// core/theme/app_text_theme.dart
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

abstract final class AppTextTheme {
  static TextTheme build(Color primaryColor) => GoogleFonts.interTextTheme().copyWith(
        headlineSmall: GoogleFonts.inter(fontSize: 24, fontWeight: FontWeight.w600, letterSpacing: -0.3, color: primaryColor), // h1 login
        titleLarge: GoogleFonts.inter(fontSize: 20, fontWeight: FontWeight.w600, letterSpacing: -0.3, color: primaryColor),    // PageHeader title (sm:text-2xl)
        titleMedium: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.w600, color: primaryColor),                        // Card title
        bodyLarge: GoogleFonts.inter(fontSize: 16, color: primaryColor),
        bodyMedium: GoogleFonts.inter(fontSize: 14, color: primaryColor),                                                      // teks umum (text-sm)
        bodySmall: GoogleFonts.inter(fontSize: 12, color: primaryColor),                                                       // text-xs (caption tabel, badge)
        labelLarge: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.w500, color: primaryColor),                         // label form, tombol
      );
}
```

**Spacing scale** (grid 4px, konstanta agar konsisten dipakai lintas widget — padanan `gap-*`/`p-*` Tailwind):

```dart
abstract final class AppSpacing {
  static const xs = 4.0;
  static const sm = 8.0;
  static const md = 12.0;
  static const lg = 16.0;
  static const xl = 20.0;
  static const xl2 = 24.0;
}
```

**Radius**: `AppRadius.sm = 8` (input/tombol, `rounded-lg`), `AppRadius.lg = 14` (Card/`.xl`), `AppRadius.xl = 18` (Modal/`.2xl`), `AppRadius.full = 999` (Badge/Avatar, `rounded-full`).

**Elevation/Shadow**: dua shadow kustom React (`card`, `popover`) dipetakan jadi `BoxShadow` konstan (dipakai manual di widget kustom seperti dropdown/notification panel, karena Card sendiri di React tidak pakai shadow tebal — cukup `elevation: 0` + border seperti di 5.2):

```dart
abstract final class AppShadows {
  static const card = [BoxShadow(color: Color(0x0A000000), blurRadius: 3, offset: Offset(0, 1))];
  static const popover = [BoxShadow(color: Color(0x14000000), blurRadius: 15, offset: Offset(0, 10))];
}
```

### 5.5 Dynamic Color (Material You) untuk Android 12+

Dibahas rinci di **BAB 8.1** (bagian implementasi teknis paket `dynamic_color`); ringkasan di sini: pada Android 12+, `DynamicColorBuilder` membaca palet wallpaper pengguna dan menghasilkan `ColorScheme` sistem. Skema tersebut **dipakai sebagai basis**, lalu `primary`/`secondary` di-override tetap ke warna brand (`AppColorsLight/Dark.primary`/`.accent`) — Dynamic Color di sini hanya memengaruhi nuansa tersier/permukaan agar tetap terasa "menyatu" dengan wallpaper pengguna, tanpa kehilangan identitas hijau-oranye Atma Jaya. Pengguna bisa mematikan Dynamic Color dari Pengaturan aplikasi (kembali ke seed color murni) — opsi ini disimpan bersebelahan dengan `ThemeMode` di `shared_preferences`.

### 5.6 Widget UI Dasar (padanan `components/ui/*`)

| Komponen React | Widget Flutter | Catatan pemetaan |
|---|---|---|
| `Button.tsx` (variant primary/secondary/outline/ghost/danger, size sm/md/lg, `isLoading`, `leftIcon`) | `AppButton` (wrapper `FilledButton`/`OutlinedButton`/`TextButton` + `CircularProgressIndicator` saat loading, disabled otomatis saat `isLoading`) | 5 variant, 3 ukuran — persis |
| `Badge.tsx` (6 varian warna) | `AppBadge` (`Container` rounded-full + `Text`, warna dari `AppColorsPrimaryScale`/semantic) | — |
| `Input.tsx` / `PasswordInput.tsx` | `AppTextField` (`TextFormField` + label, error text, leading icon, toggle visibility utk password) | — |
| `Select.tsx` | `AppDropdownField<T>` (`DropdownButtonFormField`) | — |
| `Checkbox.tsx` | `AppCheckbox` (`Checkbox` + label `Row`) | — |
| `Card.tsx` (title, description, actions, noPadding) | `AppCard` | — |
| `Modal.tsx` | `AppModal.show()` — `showDialog` di layar lebar, `showModalBottomSheet` di layar sempit (heuristik `MediaQuery` lebar < 600dp) | Web selalu dialog tengah; mobile pakai bottom sheet agar ergonomis satu tangan |
| `DataTable.tsx` | `AppDataTable<T>` — di layar mobile dirender sebagai **daftar Card** per baris (bukan tabel scroll horizontal, buruk di layar sempit); di tablet/layar lebar (≥900dp) dirender sebagai `DataTable`/`SliverList` tabel asli | Adaptasi wajar dari layout web ke mobile-first, kolom sama, hanya representasi visual yang berubah |
| `EmptyState.tsx` | `AppEmptyState` | — |
| `Skeleton.tsx` / `SkeletonCard` / `SkeletonRow` | `AppSkeleton` (`shimmer` package) | BAB 8.7 |
| `Avatar.tsx` | `AppAvatar` (inisial dari nama, atau `CachedNetworkImage`) | — |
| `Tooltip.tsx` | `Tooltip` (bawaan Flutter) | — |
| `Breadcrumb.tsx` | `AppBreadcrumb` — di mobile ditampilkan ringkas sebagai `AppBar` back button + judul (breadcrumb penuh kurang berguna di layar sempit, digantikan navigasi back standar) | Adaptasi mobile |
| `ListPagination.tsx` | `AppPaginationFooter` (tombol Sebelumnya/Berikutnya + label) **atau** infinite-scroll otomatis (BAB 8.7) — kedua pola disediakan, list panjang pakai infinite-scroll, list pendek/tabel admin kompleks pakai footer eksplisit | — |
| `Alert.tsx` | `AppAlert` (4 varian: info/success/warning/danger) | — |
| `PageHeader.tsx` | `AppScreenHeader` (judul + deskripsi + actions, breadcrumb→back button) | — |
| `FileUploadField.tsx` | `FileUploadField` (BAB 8.9) | — |
| `StatCard` (`components/ui/StatCard.tsx`) | `AppStatCard` | dipakai Dashboard & Portal Dashboard |

---

## BAB 6 — Rincian Setiap Halaman

Format tiap entri: **Screen & route**, **Tujuan**, **Wireframe ASCII**, **Widget dipakai**, **State dikelola**, **API dipanggil**, **Validasi & pesan error**, **Navigasi**. Pola berulang lintas hampir semua halaman List (search bar → tabel/list Card → paginasi) dan Detail (header Card ringkasan → beberapa Card sub-data) dijelaskan sekali di 6.0 lalu dirujuk singkat di tiap entri agar tidak berulang secara verbatim.

### 6.0 Pola Umum (dirujuk oleh entri di bawah)

**Pola List** (`ListScreenPattern`): `AppScreenHeader` (judul, breadcrumb→back) → baris filter (`AppTextField` cari + 0-2 `AppDropdownField` filter + tombol "Cari"/"Terapkan Filter") → `AppDataTable`/daftar `AppCard` → `AppPaginationFooter`. State: `loading` (skeleton baris), `data` (list terisi), `empty` (`AppEmptyState` dengan pesan spesifik modul), `error` (`AppAlert` danger + tombol coba lagi). Ditenagai `ListNotifier<T>` (padanan `usePaginatedList`): field `page, search, filter, sort, data, meta, isLoading, error`.

**Pola Detail** (`DetailScreenPattern`): `AppScreenHeader` (judul, tombol Kembali) → Card ringkasan (grid 2-3 kolom field statis + Badge status) → 0-n Card sub-data terkait (tabel ringkas 5 baris terbaru + link "Lihat Semua" ke List terfilter). State: `loading`, `data`, `error` — tanpa `empty` di level halaman (kalau ID tidak ditemukan, backend balas 404 → ditampilkan sebagai `error`).

**Pola Filter deep-link** (padanan `pickFilterParams`/`buildDashboardLink`): saat Screen dibuka dari tap kartu/chart Dashboard, GoRouter query parameter (mis. `/mahasiswa?status=active`) di-seed sekali ke `initialFilter` Notifier saat `initState`, persis kontrak `usePaginatedList({initialFilter})`.

---

### 6.1 Modul Auth

#### 6.1.1 LoginScreen — `/login`
**Tujuan**: autentikasi awal.
**Wireframe**:
```
┌───────────────────────────────┐
│ [Logo] CivitasOne               │
│ Sistem Manajemen Akademik...    │
│                                  │
│ [Alert error, kondisional]      │
│                                  │
│ Email atau Username              │
│ [___________________] ✉         │
│ Kata Sandi                       │
│ [___________________] 👁         │
│ [x] Ingat saya      Lupa sandi? │
│                                  │
│ [   Masuk (loading spinner)   ] │
│                                  │
│ Versi 0.1.0-fase1   © 2026 ...  │
└───────────────────────────────┘
```
**Widget**: `AppTextField` (email), `AppPasswordField`, `Checkbox` "Ingat saya", `AppButton` primary lebar penuh `isLoading`, `AppAlert` danger dismissible, `TextButton` link "Lupa kata sandi?".
**State**: form idle → submitting (`isLoading` tombol) → sukses (navigasi replace ke `/dashboard` atau `redirect` query) → gagal (`AppAlert` pesan dari `ApiFailure.message`).
**API**: `POST /login` lalu `GET /me` (lihat 2.4).
**Validasi**: email wajib+format valid; password wajib, minimal 6 karakter — client-side (padanan skema Zod `loginSchema`) sebelum submit; error server (mis. kredensial salah) tampil sebagai `AppAlert` top-level (bukan per-field, karena endpoint ini tidak balas per-field `errors`).
**Navigasi**: masuk dari cold start (belum ada sesi) atau redirect dari route terproteksi; keluar ke `ForgotPasswordScreen` (link), atau ke `DashboardScreen`/`PlatformDashboardScreen`/`PortalDashboardScreen` (dipilih otomatis oleh `DashboardRoute` — lihat 6.2) setelah sukses, mengembalikan ke tujuan awal (`from`) bila ada.

#### 6.1.2 ForgotPasswordScreen — `/forgot-password`
**Tujuan**: minta tautan reset password via email.
**Wireframe**: identik kerangka Login (logo+judul kecil) → `AppTextField` Email → tombol "Kirim Tautan Reset" → setelah sukses, form diganti `AppAlert` success ("Jika email terdaftar, tautan sudah dikirim...") → link "Kembali ke halaman masuk".
**Widget**: `AppTextField`, `AppButton`, `AppAlert` (danger utk error, success utk konfirmasi), `TextButton` back.
**State**: idle → submitting → `sent=true` (form disembunyikan, alert success tampil) | error (alert danger).
**API**: `POST /forgot-password`.
**Validasi**: email wajib + format valid.
**Navigasi**: masuk dari link di `LoginScreen`; keluar kembali ke `LoginScreen`.

#### 6.1.3 ResetPasswordScreen — `/reset-password?token=&email=`
**Tujuan**: set password baru dari link email.
**Wireframe**: bila `token`/`email` query kosong → `AppAlert` danger "Tautan tidak valid" saja. Bila ada → judul "Atur Ulang Kata Sandi" → `AppPasswordField` baru → `AppPasswordField` konfirmasi → tombol submit → link back.
**Widget**: `AppPasswordField` ×2, `AppButton`, `AppAlert`.
**State**: token/email hilang → tampilan invalid statis; ada → idle/submitting/error; sukses → navigasi replace ke `/login` membawa flag `passwordResetSuccess` (ditampilkan sebagai snackbar/alert di LoginScreen — ⚠️ React menyimpan lewat router `state`, di Flutter memakai `extra` GoRouter atau query flag sementara).
**API**: `POST /reset-password` `{token, email, password, password_confirmation}`.
**Validasi**: password minimal 8 karakter; konfirmasi wajib sama persis dengan password (custom validator, padanan `.refine()` Zod) — pesan error tampil di field `password_confirmation`.
**Navigasi**: masuk dari deep link email (App Link, lihat BAB 8.8); keluar ke `LoginScreen`.

#### 6.1.4 SecuritySessionsScreen — `/pengaturan/keamanan`
**Tujuan**: kelola sesi login & perangkat milik akun sendiri (self-service, tanpa permission slug — hanya butuh login).
**Wireframe**:
```
┌ Keamanan          [Keluar dari Semua Perangkat] ┐
│ Sesi Aktif                                       │
│ ┌─────────────────────────────────────────────┐ │
│ │ 📱 iPhone 15 · Safari   IP  Aktif  [Cabut]   │ │
│ │ 💻 Chrome Windows       IP  Aktif  [Cabut]   │ │
│ └─────────────────────────────────────────────┘ │
│ Perangkat Terdaftar                              │
│ ┌─────────────────────────────────────────────┐ │
│ │ Perangkat | Tipe | Platform | Dipercaya | ... │ │
│ └─────────────────────────────────────────────┘ │
└───────────────────────────────────────────────────┘
```
**Widget**: `AppScreenHeader` + tombol danger, 2× `AppCard` noPadding berisi `AppDataTable`/list (Sesi Aktif: perangkat, IP, aktivitas terakhir, status Aktif/Dicabut, tombol Cabut; Perangkat Terdaftar: nama, tipe badge, platform, badge Dipercaya, terakhir dipakai), `AppSkeleton` list saat loading tiap Card independen.
**State**: dua sumber data independen (sessions, devices) masing-masing `loading/data/error`; per-baris `revokingId` loading lokal saat mencabut; `isLoggingOutAll` saat proses logout-all.
**API**: `GET /sessions`, `DELETE /sessions/{id}`, `GET /devices`, `POST /logout-all`.
**Validasi**: tidak ada form input.
**Navigasi**: masuk dari menu "Pengaturan → Keamanan" (semua role, tanpa gate permission) atau dari avatar dropdown "Profil Saya" di Header; aksi "Keluar dari Semua Perangkat" & 401 global membawa ke `LoginScreen`.

---

### 6.2 Dashboard (3 varian, dipilih otomatis oleh routing — padanan `DashboardRoute`)

Ketiganya berbagi route `/dashboard`; pemilihan varian meniru logic React persis:
```dart
Widget dashboardRouteBuilder(WidgetRef ref) {
  final isSuperAdmin = ref.watch(isSuperAdminProvider);
  final isStudent = ref.watch(isStudentProvider);
  final tenantSelected = ref.watch(tenantControllerProvider).selectedUniversity != null;
  if (isSuperAdmin && !tenantSelected) return const PlatformDashboardScreen();
  if (isStudent) return const PortalDashboardScreen();
  return const DashboardScreen();
}
```

#### 6.2.1 DashboardScreen (tenant biasa) — `/dashboard`
**Tujuan**: ringkasan statistik operasional satu universitas, dengan kartu/chart yang bisa diklik untuk lompat ke List terfilter.
**Wireframe**:
```
┌ Dashboard           Selamat datang kembali, {nama} ┐
│ [Filter: Periode ▾] [Fakultas ▾] [Program Studi ▾]  │
│ ┌────┐┌────┐┌────┐┌────┐  ← StatCard (s.d. 8, hanya│
│ │1234││ 56 ││ 12 ││ 8  │    yang diizinkan permission│
│ └────┘└────┘└────┘└────┘    pengguna ditampilkan)   │
│ ┌─────────────────┐┌─────────────────┐              │
│ │ Grafik Mahasiswa││ Sebaran Fakultas│  (tap titik/  │
│ │ per Tahun (area)││ (bar)           │   bar → List) │
│ └─────────────────┘└─────────────────┘              │
│ ┌─────────────────┐┌─────────────────┐              │
│ │ Tren Pembayaran ││ Status Tagihan  │              │
│ └─────────────────┘└─────────────────┘              │
│ ┌─────────────────┐┌─────────────────┐              │
│ │ Status Mahasiswa││ Staf per Unit   │              │
│ └─────────────────┘└─────────────────┘              │
│ ┌─────────────────┐┌─────────────────┐              │
│ │ Kelas per Prodi ││ Status Persetujuan               │
│ └─────────────────┘└─────────────────┘              │
│ ┌───────────────────────┐┌───────────┐              │
│ │ Persetujuan Menunggu   ││ Agenda    │              │
│ └───────────────────────┘└───────────┘              │
│ ┌────────────────────────────────────┐              │
│ │ Aktivitas Terbaru                    │              │
│ └────────────────────────────────────┘              │
└───────────────────────────────────────────────────────┘
```
**Widget**: `DashboardFilterBar` (3 dropdown: periode akademik/fakultas/prodi — hanya tampil bila `filters` dari API tidak kosong), `SummaryCardsGrid` (s.d. 8 `AppStatCard`, kartu yang key-nya tak ada di respons `summary` — karena permission — **tidak dirender sama sekali**, bukan tampil "0"), 8 chart (`fl_chart`: `StudentGrowthChart`=area, `FacultyDistributionChart`=bar, `PaymentTrendChart`=line/bar, `PaymentStatusChart`=pie, `StudentStatusChart`=pie, `StaffByUnitChart`=grouped bar, `ActiveClassesByProgramChart`=bar, `ApprovalStatusChart`=pie — chart terakhir hanya render bila datanya ada di respons), `PendingApprovalsTable`, `AcademicAgendaList`, `RecentActivityList`.
**State**: `loading` (skeleton grid+chart), `data`, `error` (`AppAlert`); kasus khusus **"tidak ada statistik sama sekali"** (`summary` dan `charts` kosong-kosong karena permission pengguna minim) → `AppEmptyState` alih-alih grid rusak.
**API**: `GET /dashboard?academic_term_id=&faculty_id=&study_program_id=&date_from=&date_to=`.
**Validasi**: tidak ada form; filter dropdown murni memicu refetch.
**Navigasi**: setiap kartu/chart yang punya `onTap` melakukan `context.go(...)` ke List terkait dengan query filter terpasang (mis. tap kartu "Tagihan Belum Dibayar" → `/keuangan/tagihan?status=unpaid,partial`; tap titik grafik tahun → `/mahasiswa?admission_year=2024`; tap bulan tren pembayaran → `/keuangan/pembayaran?date_from=...&date_to=...`, dikonversi dari "YYYY-MM" ke rentang tanggal penuh persis logic `monthToDateRange`).

#### 6.2.2 PlatformDashboardScreen (Super Admin, belum pilih tenant) — `/dashboard`
**Tujuan**: ringkasan lintas-universitas untuk Super Admin sebelum masuk ke satu tenant.
**Wireframe**:
```
┌ Dashboard Platform    Selamat datang, {nama} ┐
│ ┌────┐┌────┐┌────┐┌────┐  (Total Universitas,│
│ │ 12 ││ 9  ││456 ││480 │  Universitas Aktif,  │
│ └────┘└────┘└────┘└────┘  Total Pengguna, Membership)│
│ Ringkasan Seluruh Universitas                  │
│ ┌────┐┌────┐┌────┐┌────┐┌────┐┌────┐          │
│ │Mhs ││Dsn ││Pgw ││Prodi││Tag ││Appr│          │
│ └────┘└────┘└────┘└────┘└────┘└────┘          │
│ Universitas per Status                          │
│ [Badge: Aktif: 9] [Badge: Trial: 2] [Badge:...]│
│ ┌ Tabel Perbandingan Universitas ──────────────┐│
│ │ Nama | Mhs | Dsn | Pgw | Tagihan | Persetujuan││
│ └───────────────────────────────────────────────┘│
└─────────────────────────────────────────────────┘
```
**Widget**: 4 `AppStatCard` inti, 6 `AppStatCard` ringkasan (skeleton saat loading), grup `AppBadge` per status universitas, `UniversityComparisonTable` (tabel/list Card per universitas: mahasiswa, dosen, pegawai, tagihan belum bayar, persetujuan pending).
**State**: `loading` (skeleton), `data`, `error`.
**API**: `GET /platform/statistics`.
**Validasi**: tidak ada.
**Navigasi**: aktif hanya untuk Super Admin sebelum memilih tenant; setelah Tenant Switcher aktif, Dashboard beralih ke `DashboardScreen` biasa (data tenant terpilih). Baris tabel perbandingan tidak menavigasi ke mana pun di React (murni tabel data).

#### 6.2.3 PortalDashboardScreen (mahasiswa) — `/dashboard`
**Tujuan**: ringkasan aktivitas akademik mahasiswa (⚠️ seluruh isi memakai **data contoh statis** — lihat 1.1).
**Wireframe**:
```
┌ Dashboard Mahasiswa   Selamat datang, {nama}   ┐
│ ┌────┐┌────┐┌────┐┌────┐  (IP Semester, IPK,   │
│ │3.72││3.65││Rp5jt││Disetujui│ Tagihan Aktif, Status KRS)│
│ └────┘└────┘└────┘└────┘                        │
│ ┌ Jadwal Hari Ini ──────┐┌ Tugas Mendekati Deadline┐│
│ │ 08:00 Struktur Data.. │Lihat Semua│ ...          │Lihat Semua│
│ └───────────────────────┘└──────────────────────────┘│
│ ┌ Pengumuman ───────────┐┌ Status Pengajuan Surat  ┐│
│ ┌ Jadwal Bimbingan ─────┐┌ Kalender Akademik       ┐│
└───────────────────────────────────────────────────┘
```
**Widget**: 4 `AppStatCard`, 6 `DashboardCard` (Card + ikon judul + link "Lihat Semua" ke screen Portal terkait) berisi list ringkas item.
**State**: statis (tidak ada fetch — data contoh hardcoded di kode, sama seperti React).
**API**: **tidak ada** (⚠️ murni tampilan, lihat 1.1) — struktur data & layar sudah didesain agar tinggal disambungkan begitu ada endpoint `GET /portal/dashboard` (atau gabungan endpoint yang relevan) di backend.
**Validasi**: tidak ada.
**Navigasi**: setiap `DashboardCard` action → screen Portal terkait (Jadwal, Tugas, Pengumuman, Surat, Bimbingan Akademik).

---

### 6.3 Modul Errors

#### 6.3.1 AccessDeniedScreen (inline, semua path terproteksi)
**Tujuan**: diberikan sebagai pengganti Screen tujuan ketika pengguna login tapi tidak punya permission yang disyaratkan route tersebut — URL tetap sama (bukan redirect), padanan `RequirePermission`.
**Wireframe**:
```
┌────────────────────────────────────┐
│              🛡 (ikon)                │
│  Anda tidak memiliki akses ke        │
│  halaman ini                          │
│  Hubungi admin universitas Anda...   │
│      [ Kembali ke Dashboard ]        │
└────────────────────────────────────┘
```
**Widget**: `AppEmptyState` (ikon shield-alert) + `AppButton` outline "Kembali ke Dashboard".
**State**: statis.
**API**: tidak ada.
**Validasi**: tidak ada.
**Navigasi**: dirender oleh `PermissionGate` (BAB 3.4/7.3) menggantikan body Screen tujuan; tombol kembali ke `/dashboard`.

---

### 6.4 Modul Platform (Super Admin)

#### 6.4.1 UniversitiesScreen — `/platform/universitas`
**Tujuan**: CRUD universitas tenant + aktivasi/suspend.
**Wireframe**:
```
┌ Manajemen Universitas      [+ Tambah Universitas] ┐
│ [ Cari nama, kode, atau slug... ]                   │
│ ┌ Universitas UNIKA │ Jenis │ Status │ [Detail][Ubah]┐
│ │ Atma Jaya (AJY)    │ Univ. │ Aktif  │              │
│ └──────────────────────────────────────────────────┘│
│ Halaman 1 dari 3 (45 universitas)  [Sebelumnya][Berikutnya]│
└──────────────────────────────────────────────────────┘
Modal Tambah/Ubah: Kode, Nama Singkat, Nama Universitas, Jenis
Institusi, Akreditasi, Email Institusi, Telepon, Website.
Modal Detail: Kode, Status, Jenis, Akreditasi, Email, Domain,
Langganan Aktif (paket+status+berakhir), tombol Aktifkan/Suspend.
```
**Widget**: `AppScreenHeader`+tombol create (gated `platform_universities.create`), `AppTextField` cari, `AppDataTable`, `AppPaginationFooter`, `AppModal` form (create/update, gated `platform_universities.update`), `AppModal` detail dengan aksi Aktifkan/Suspend.
**State**: list `loading/data/error`; form modal `formError` per-field + top-level (`applyServerErrors`); detail modal `loading/data/error` independen + `isActing` saat aktifkan/suspend.
**API**: `GET /platform/universities` (search), `POST`/`PATCH /platform/universities/{id}`, `GET /platform/universities/{id}`, `POST .../activate`, `POST .../suspend`.
**Validasi**: `code` & `name` wajib (maks 50/255), `email` format valid bila diisi, field lain opsional — semua dikirim `null` bila string kosong.
**Navigasi**: masuk dari menu Platform (Super Admin only); Detail & Ubah dibuka sebagai modal di atas list (tidak pindah screen).

#### 6.4.2 PlatformSecurityScreen — `/platform/keamanan`
**Tujuan**: audit trail Support Session — riwayat setiap kali Super Admin masuk ke konteks satu universitas.
**Wireframe**:
```
┌ Keamanan Platform  Riwayat Support Session per masuk-tenant ┐
│ ┌ Universitas │ Alasan │ Mulai │ Status ─────────────────┐  │
│ │ Atma Jaya    │ Bantu..│ 2j lalu│ Sedang berlangsung     │  │
│ │ oleh Budi     │        │        │                        │  │
│ └────────────────────────────────────────────────────────┘  │
│ Halaman 1 dari 2      [Sebelumnya][Berikutnya]               │
└────────────────────────────────────────────────────────────┘
```
**Widget**: `AppDataTable` (Universitas+oleh siapa, Alasan (2 baris maks), Mulai relatif, Badge status Berlangsung/Berakhir), `AppPaginationFooter`.
**State**: `loading/data/error`, read-only (tidak ada aksi tulis di halaman ini).
**API**: `GET /platform/support-sessions`.
**Validasi**: tidak ada.
**Navigasi**: masuk dari menu Platform; tidak ada navigasi keluar selain sidebar.

---

### 6.5 Modul Akademik

#### 6.5.1 StudentsScreen — `/mahasiswa`
**Tujuan**: List mahasiswa satu universitas, mengikuti `ListScreenPattern` (6.0).
**Wireframe**:
```
┌ Mahasiswa   Daftar mahasiswa terdaftar...      ┐
│ [Cari: nama/NIM] [Status ▾]  [Terapkan Filter] │
│ ┌ Nama/NIM │Prodi│Angkatan│Status│Email│Terdaftar┐
│ │ Budi S.   │ TI  │ 2024   │●Aktif│ ..  │ 2024-08 │
│ │ 202400512 │     │        │      │     │         │
│ └──────────────────────────────────────────────┘│
│ Halaman 1 dari 5 (98 mahasiswa)  [◀][▶]         │
└──────────────────────────────────────────────────┘
```
**Widget**: `ListScreenPattern` lengkap; Badge status 5 warna (Aktif=success, Cuti=warning, Lulus=info, Nonaktif=neutral, Drop Out=danger); baris tabel dapat di-tap → Detail.
**State**: sesuai 6.0; filter awal dapat diseed dari deep-link Dashboard (`status`, `study_program_id`, `admission_year`).
**API**: `GET /students` (search, `filter[status]`, `filter[study_program_id]`, `filter[admission_year]`, pagination).
**Validasi**: tidak ada form tulis di halaman ini (read-only list).
**Navigasi**: masuk dari menu Akademik→Mahasiswa atau dari kartu/chart Dashboard; tap baris → `StudentDetailScreen`.

#### 6.5.2 StudentDetailScreen — `/mahasiswa/:id`
**Tujuan**: profil lengkap satu mahasiswa + ringkasan lintas-modul (transkrip, KRS, nilai, absensi, beasiswa, skripsi, magang) — **setiap Card sub-data hanya dirender bila pengguna punya permission `read` modul terkait**, mengikuti `DetailScreenPattern`.
**Wireframe**:
```
┌ Detail Mahasiswa                    [Kembali] ┐
│ Budi Santoso              NIM 202400512         │
│ Status: ●Aktif  Prodi: TI  Angkatan: 2024        │
│ Email: budi@..  Terdaftar: .. Lulus: -           │
│ [Lihat Tagihan Mahasiswa Ini]  (jika invoices.read)│
│                                                    │
│ ┌ Transkrip ── IPK 3.65 │ Total SKS 96 ──────────┐│
│ │ Tabel periode: SKS, IP                          ││
│ └──────────────────────────────────────────────────┘│
│ ┌ KRS (5 terbaru)              [Lihat Semua]      ┐│
│ ┌ Nilai (5 terbaru)             [Lihat Semua]      ┐│
│ ┌ Absensi (5 terbaru)           [Lihat Semua]      ┐│
│ ┌ Beasiswa                      [Lihat Semua]      ┐│
│ ┌ Skripsi                       [Lihat Semua]      ┐│
│ ┌ Magang dan MBKM                [Lihat Semua]      ┐│
└────────────────────────────────────────────────────┘
```
**Widget**: `DetailScreenPattern` header, `AppStatCard`×2 (IPK, Total SKS) di dalam Card Transkrip, 6 Card sub-data masing-masing `AppDataTable` ringkas 5 baris.
**State**: 7 sumber data independen (`student`, `transcript`, `krsItems`, `grades`, `attendances`, `scholarshipApplications`, `theses`, `internships`) masing-masing `loading/data/error`; Card sub-data yang permission-nya tidak dimiliki **tidak melakukan fetch sama sekali** (padanan `canSeeX` guard sebelum panggil API — bukan cuma sembunyi UI, tapi juga hindari request percuma).
**API**: `GET /students/{id}`, `GET /students/{id}/transcript`, `GET /krs-items?filter[student_id]=&per_page=5&sort=-created_at`, `GET /grades?filter[student_id]=&per_page=5&sort=-created_at`, `GET /attendances?filter[student_id]=&per_page=5&sort=-meeting_date`, `GET /scholarship-applications?filter[student_id]=&per_page=5&sort=-submitted_at`, `GET /theses?filter[student_id]=&per_page=5`, `GET /internships?filter[student_id]=&per_page=5&sort=-start_date`.
**Validasi**: tidak ada.
**Navigasi**: masuk dari tap baris `StudentsScreen`; tombol "Lihat Semua"/"Lihat Tagihan" → List modul terkait terfilter `student_id`; tombol Kembali → `StudentsScreen`.

#### 6.5.3 LecturersScreen — `/dosen`
**Tujuan**: List dosen. **Wireframe**: `ListScreenPattern` — kolom Nama+NIDN, Fakultas, Email, Badge Aktif/Nonaktif; filter: cari nama/NIDN saja (tanpa dropdown status). **Widget/State**: sama 6.0. **API**: `GET /lecturers` (`filter[faculty_id]`, `filter[is_active]` dari deep-link). **Validasi**: tidak ada. **Navigasi**: menu Akademik→Dosen atau chart Dashboard "Staf per Unit"; tap baris → `LecturerDetailScreen`.

#### 6.5.4 LecturerDetailScreen — `/dosen/:id`
**Tujuan**: profil dosen (halaman ringkas, tanpa sub-data lintas-modul). **Wireframe**: Card tunggal — Nama, NIDN, Badge Aktif/Nonaktif, Fakultas, Email. **Widget**: `DetailScreenPattern` minimal (1 Card saja). **State**: `loading/data/error`. **API**: `GET /lecturers/{id}`. **Validasi**: tidak ada. **Navigasi**: dari `LecturersScreen`; tombol Kembali kesana.

#### 6.5.5 EmployeesScreen — `/pegawai`
**Tujuan**: List pegawai non-dosen. **Wireframe**: `ListScreenPattern` — kolom Nama+Jabatan, Unit Kerja, Email, Badge status; cari nama/jabatan. **Widget/State**: 6.0. **API**: `GET /employees` (`filter[unit_kerja]`, `filter[is_active]`). **Validasi**: tidak ada. **Navigasi**: menu Akademik→Pegawai; tap baris → `EmployeeDetailScreen`.

#### 6.5.6 EmployeeDetailScreen — `/pegawai/:id`
**Tujuan**: profil pegawai. **Wireframe**: 1 Card — Nama, Jabatan, Badge status, Unit Kerja, Email. **Widget/State/Validasi**: sama pola 6.5.4. **API**: `GET /employees/{id}`. **Navigasi**: dari `EmployeesScreen`.

#### 6.5.7 StudyProgramsScreen — `/akademik/program-studi`
**Tujuan**: List program studi + ringkasan jumlah mahasiswa/kelas. **Wireframe**: `ListScreenPattern` — kolom Nama+Kode+Jenjang, Fakultas, jumlah Mahasiswa, jumlah Kelas, Badge status; cari nama/kode. **Widget/State**: 6.0. **API**: `GET /study-programs` (`filter[faculty_id]`, `filter[is_active]`). **Validasi**: tidak ada. **Navigasi**: menu Akademik→Program Studi; tap baris → `StudyProgramDetailScreen`.

#### 6.5.8 StudyProgramDetailScreen — `/akademik/program-studi/:id`
**Tujuan**: profil program studi + tautan pintas ke Mahasiswa/Kelas terfilter prodi ini (bila permission `students.read`/`classes.read`). **Wireframe**: Card — Nama, Kode+Jenjang, Badge status, Fakultas, Jumlah Mahasiswa, Jumlah Kelas → tombol "Lihat Mahasiswa"/"Lihat Kelas". **Widget**: `DetailScreenPattern` 1 Card + tombol aksi kondisional permission. **State**: `loading/data/error`. **API**: `GET /study-programs/{id}`. **Validasi**: tidak ada. **Navigasi**: dari `StudyProgramsScreen`; tombol aksi → `StudentsScreen`/`ClassSectionsScreen` terfilter `study_program_id`.

#### 6.5.9 ClassSectionsScreen — `/akademik/kelas-jadwal`
**Tujuan**: List kelas & jadwal perkuliahan. **Wireframe**: `ListScreenPattern` — kolom Kelas (mata kuliah+kode+kode kelas), Program Studi, Periode, "Terisi/Kapasitas", Badge status; cari nama MK/kode kelas. **Widget/State**: 6.0. **API**: `GET /class-sections` (`filter[study_program_id]`, `filter[academic_term_id]`, `filter[is_active]`). **Validasi**: tidak ada. **Navigasi**: menu Akademik→Kelas dan Jadwal, atau chart Dashboard "Kelas Aktif per Prodi"; tap baris → `ClassSectionDetailScreen`.

#### 6.5.10 ClassSectionDetailScreen — `/akademik/kelas-jadwal/:id`
**Tujuan**: detail satu kelas. **Wireframe**: Card — Nama MK+kode, Badge status, Program Studi, Periode Akademik, SKS, Kapasitas, Mahasiswa Terdaftar (grid 2×3). **Widget**: `DetailScreenPattern` 1 Card. **State**: `loading/data/error`. **API**: `GET /class-sections/{id}`. **Validasi**: tidak ada. **Navigasi**: dari `ClassSectionsScreen`.

#### 6.5.11 CurriculumsScreen — `/akademik/kurikulum`
**Tujuan**: List kurikulum per prodi. **Wireframe**: `ListScreenPattern` — kolom Nama+Tahun Ajaran, Program Studi, jumlah Mata Kuliah, Badge status; cari nama/tahun. **Widget/State**: 6.0. **API**: `GET /curriculums` (`filter[study_program_id]`, `filter[is_active]`). **Validasi**: tidak ada. **Navigasi**: menu Akademik→Kurikulum; tap baris → `CurriculumDetailScreen`.

#### 6.5.12 CurriculumDetailScreen — `/akademik/kurikulum/:id`
**Tujuan**: detail kurikulum + daftar seluruh mata kuliahnya. **Wireframe**: Card ringkasan (Nama, Tahun Ajaran, Badge status, Program Studi, Jumlah Mata Kuliah) → Card "Mata Kuliah" berisi list (kode · nama · SKS · semester), tiap baris tap → `CourseDetailScreen`. **Widget**: `DetailScreenPattern` + `ListTile` list. **State**: `loading/data/error`; list mata kuliah kosong → pesan "Belum ada mata kuliah pada kurikulum ini." **API**: `GET /curriculums/{id}` (respons sudah menyertakan array `courses`, tidak perlu request terpisah). **Validasi**: tidak ada. **Navigasi**: dari `CurriculumsScreen`; tiap baris mata kuliah → `CourseDetailScreen`.

#### 6.5.13 CoursesScreen — `/akademik/mata-kuliah`
**Tujuan**: List mata kuliah. **Wireframe**: `ListScreenPattern` — kolom Nama+Kode, Program Studi, Kurikulum, SKS, Semester, Badge status; cari nama/kode. **Widget/State**: 6.0. **API**: `GET /courses` (`filter[study_program_id]`, `filter[curriculum_id]`, `filter[semester_level]`, `filter[is_active]`). **Validasi**: tidak ada. **Navigasi**: menu Akademik→Mata Kuliah; tap baris → `CourseDetailScreen`.

#### 6.5.14 CourseDetailScreen — `/akademik/mata-kuliah/:id`
**Tujuan**: detail mata kuliah. **Wireframe**: Card — Nama+Kode, Badge status, Program Studi, Kurikulum (tautan → `CurriculumDetailScreen`), SKS, Semester. **Widget**: `DetailScreenPattern` 1 Card. **State**: `loading/data/error`. **API**: `GET /courses/{id}`. **Validasi**: tidak ada. **Navigasi**: dari `CoursesScreen` atau `CurriculumDetailScreen`; link Kurikulum → `CurriculumDetailScreen`.

#### 6.5.15 KrsScreen — `/akademik/krs`
**Tujuan**: List Kartu Rencana Studi lintas-mahasiswa (admin akademik) + tambah pendaftaran KRS baru + batalkan KRS.
**Wireframe**:
```
┌ KRS   Kartu Rencana Studi...          [+ Tambah KRS] ┐
│ ┌ Mahasiswa │MK│Periode│Status │Nilai│Aksi ──────────┐│
│ │ Budi/NIM   │..│..     │●Terdaftar│-  │[Batalkan]   ││
│ └────────────────────────────────────────────────────┘│
│ Halaman 1 dari 8   [Sebelumnya][Berikutnya]           │
└─────────────────────────────────────────────────────────┘
Modal Tambah: Select Mahasiswa, Select Kelas → [Daftarkan]
Modal Batalkan: konfirmasi teks → [Batalkan KRS] (danger)
```
**Widget**: `ListScreenPattern` (tanpa search bar — hanya filter dari deep-link), Badge status (Terdaftar=success, Dibatalkan=neutral), kolom Aksi "Batalkan" (gated `krs.update`, hanya tampil bila status masih `enrolled`); `AppModal` EnrollKrs (2 `AppDropdownField` async-loaded 100 opsi mahasiswa & kelas); `AppModal` konfirmasi Drop.
**State**: list `loading/data/error`; modal enroll: `loading` opsi (fetch paralel students+classSections), `formError`/per-field; modal drop: `isDropping`, `dropError`.
**API**: `GET /krs-items` (`filter[student_id]`, `filter[class_section_id]`, `filter[academic_term_id]`, `filter[status]`), `POST /krs-items` `{student_id, class_section_id}` (gated `krs.create`), `PATCH /krs-items/{id}/drop` (gated `krs.update`); opsi modal: `GET /students?per_page=100`, `GET /class-sections?per_page=100`.
**Validasi**: `student_id` & `class_section_id` wajib dipilih.
**Navigasi**: menu Akademik→KRS, atau dari `StudentDetailScreen` "Lihat Semua KRS" terfilter `student_id`.

#### 6.5.16 GradesScreen — `/akademik/penilaian`
**Tujuan**: List nilai mahasiswa per mata kuliah + input/ubah nilai lewat alur 2 langkah (pilih peserta KRS aktif → isi skor/nilai huruf).
**Wireframe**:
```
┌ Penilaian    Nilai yang sudah diinput dosen  [+ Input Nilai] ┐
│ ┌ Mahasiswa │MK│Periode│Nilai Huruf│Skor│Aksi ─────────────┐ │
│ │ Budi/NIM   │..│..     │  A        │ 90 │[Ubah Nilai]     │ │
│ └───────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
Modal 1 "Pilih Mahasiswa": Select peserta KRS aktif → [Lanjut]
Modal 2 "Input Nilai": ringkasan nama+MK (read-only) → Skor
(0-100) → Nilai Huruf (opsional, otomatis dari skor) → [Simpan Nilai]
```
**Widget**: `ListScreenPattern`, Badge nilai huruf 7 warna (A/AB=success, B/BC=info, C=warning, D/E=danger), kolom Aksi "Ubah Nilai" (gated `grades.create`/`update` OR-match); 2 `AppModal` berurutan (Picker → Form).
**State**: list; picker modal `loading` 100 opsi KRS `status=enrolled`; form modal `formError`/per-field.
**API**: `GET /grades` (`filter[student_id]`, `filter[letter_grade]`), `PUT /krs-items/{krsItemId}/grade` `{score, letter_grade?}`; opsi picker: `GET /krs-items?per_page=100&filter[status]=enrolled`.
**Validasi**: `score` wajib, numerik 0–100; `letter_grade` opsional (dikosongkan → backend hitung otomatis dari skor).
**Navigasi**: menu Akademik→Penilaian, atau `StudentDetailScreen` "Lihat Semua Nilai".

#### 6.5.17 AttendanceScreen — `/akademik/absensi`
**Tujuan**: List riwayat absensi + rekam kehadiran 1 batch pertemuan kelas sekaligus (semua peserta KRS aktif kelas tsb).
**Wireframe**:
```
┌ Absensi  Riwayat kehadiran per pertemuan  [+ Rekam Kehadiran]┐
│ ┌ Tanggal│Pertemuan│Mahasiswa│MK│Status ─────────────────────┐│
│ │ ..      │ Ke-5     │Budi/NIM │..│●Hadir                    ││
│ └──────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
Modal "Rekam Kehadiran": Select Kelas → Pertemuan ke- + Tanggal
→ (setelah kelas dipilih) daftar peserta KRS aktif, tiap baris
Select status (Hadir/Izin/Sakit/Alpa, default Hadir) → [Simpan]
```
**Widget**: `ListScreenPattern`, Badge status 4 warna (Hadir=success, Izin=info, Sakit=warning, Alpa=danger); `AppModal` rekam — `AppDropdownField` kelas (memicu fetch roster saat berubah), 2 field (nomor pertemuan, tanggal — `AppDatePicker`), list roster dengan `AppDropdownField` status per-baris, tombol Simpan.
**State**: list; modal — `loadingClassSections`, `roster` (null=belum pilih kelas, []=kosong, terisi), `statuses: Map<krsItemId,status>` (default `present`), `isSubmitting`.
**API**: `GET /attendances` (`filter[student_id]`, `filter[class_section_id]`, `filter[status]`), `GET /class-sections?per_page=100`, `GET /krs-items?per_page=100&filter[class_section_id]=&filter[status]=enrolled` (saat kelas dipilih), `POST /class-sections/{id}/attendances` `{meeting_number, meeting_date, entries:[{krs_item_id,status,notes?}]}`.
**Validasi**: kelas wajib dipilih sebelum submit; tombol Simpan disabled bila roster kosong/belum dimuat.
**Navigasi**: menu Akademik→Absensi, atau `StudentDetailScreen` "Lihat Semua Absensi".

---

### 6.6 Modul Keuangan

#### 6.6.1 InvoicesScreen — `/keuangan/tagihan`
**Tujuan**: List tagihan mahasiswa. **Wireframe**: `ListScreenPattern` — cari (periode) + dropdown Status (opsi gabungan "Belum Lunas (semua)"=`unpaid,partial`, Belum Dibayar, Dibayar Sebagian, Lunas) + tombol Terapkan; kolom Tagihan (nama+NIM+periode), Jumlah (Rupiah), Terbayar (Rupiah), Badge status, Jatuh Tempo. **Widget/State**: 6.0. **API**: `GET /invoices` (`filter[status]`, `filter[student_id]`). **Validasi**: tidak ada. **Navigasi**: menu Keuangan→Tagihan, atau kartu Dashboard "Tagihan Belum Dibayar"/`StudentDetailScreen`; tap baris → `InvoiceDetailScreen`.

#### 6.6.2 InvoiceDetailScreen — `/keuangan/tagihan/:id`
**Tujuan**: detail tagihan + riwayat pembayarannya. **Wireframe**: Card ringkasan (Nama+NIM+periode, Badge status, Jumlah Tagihan, Terbayar, **Sisa Tagihan** (dihitung `amount - paid_amount`), Jatuh Tempo) + tombol "Lihat Profil Mahasiswa" (gated `students.read`) → Card "Riwayat Pembayaran" (tabel tanggal/jumlah/metode). **Widget**: `DetailScreenPattern`. **State**: `loading/data/error`. **API**: `GET /invoices/{id}` (payments sudah termuat dalam respons). **Validasi**: tidak ada. **Navigasi**: dari `InvoicesScreen`; link mahasiswa → `StudentDetailScreen`.

#### 6.6.3 PaymentsScreen — `/keuangan/pembayaran`
**Tujuan**: List seluruh pembayaran tercatat, dapat dibuka dengan rentang tanggal ter-preset dari chart Dashboard. **Wireframe**: `ListScreenPattern` (tanpa search/filter UI — hanya deep-link `date_from`/`date_to` sekali di awal, deskripsi header menampilkan rentang aktif atau "Seluruh pembayaran tagihan yang tercatat"); kolom Tanggal, Mahasiswa+Periode Tagihan, Jumlah (Rupiah), Metode, link "Lihat Tagihan". **Widget/State**: 6.0 (fetcher digabung dengan `date_from`/`date_to` tetap di setiap request paginasi). **API**: `GET /payments?date_from=&date_to=` + query list standar. **Validasi**: tidak ada. **Navigasi**: menu Keuangan→Pembayaran, atau chart Dashboard "Tren Pembayaran" (klik bulan); link "Lihat Tagihan" → `InvoiceDetailScreen`.

#### 6.6.4 ScholarshipsScreen — `/keuangan/beasiswa`
**Tujuan**: List program beasiswa. **Wireframe**: `ListScreenPattern` — cari nama/penyedia; kolom Nama+Tahun Ajaran, Penyedia, jumlah Pendaftar, Badge status. **Widget/State**: 6.0. **API**: `GET /scholarships` (`filter[is_active]`). **Validasi**: tidak ada. **Navigasi**: menu Keuangan→Beasiswa; tap baris → `ScholarshipDetailScreen`.

#### 6.6.5 ScholarshipDetailScreen — `/keuangan/beasiswa/:id`
**Tujuan**: detail program beasiswa + daftar seluruh pengajuannya. **Wireframe**: Card ringkasan (Nama, Tahun Ajaran, Badge status, Penyedia, Kuota, Jumlah Bantuan (Rupiah), Pendaftaran Dibuka/Ditutup) → Card "Pengajuan Beasiswa" (list nama+NIM pendaftar + Badge status pengajuan). **Widget**: `DetailScreenPattern`. **State**: `loading/data/error`; list pengajuan kosong → "Belum ada pengajuan untuk beasiswa ini." **API**: `GET /scholarships/{id}` (applications sudah termuat). **Validasi**: tidak ada. **Navigasi**: dari `ScholarshipsScreen`.

---

### 6.7 Modul Persetujuan

#### 6.7.1 ApprovalRequestsScreen — `/persetujuan`
**Tujuan**: List pengajuan persetujuan — data milik sendiri, atau lebih luas bila punya `approval_requests.read`. **Wireframe**: `ListScreenPattern` (tanpa search — hanya filter status dari deep-link); kolom Jenis Pengajuan (nama kelas dari `requestable_type`, mis. `LeaveRequest`→"Leave Request"), Badge status (Diajukan=info, Diproses=warning, Disetujui=success, Ditolak=danger), Diajukan (waktu relatif), tombol "Lihat Detail". **Widget/State**: 6.0. **API**: `GET /approval-requests` (`filter[status]`). **Validasi**: tidak ada. **Navigasi**: menu Persetujuan→Pengajuan, atau kartu Dashboard "Menunggu Persetujuan"/tabel `PendingApprovalsTable` di Dashboard; tap baris → `ApprovalRequestDetailScreen`. ⚠️ **Catatan RBAC khusus**: route ini **tidak digate** oleh permission `approval_requests.read` di level akses (walau dipakai untuk menyaring visibilitas item sidebar) — backend sendiri yang membatasi hanya menunjukkan milik sendiri/assigned approver bila pengguna tidak punya akses lebih luas (lihat `routePermissions.ts` komentar `routePermissions.delete(ROUTES.persetujuan)`); Flutter **wajib** meniru: route guard tidak memblokir halaman ini sama sekali untuk pengguna login manapun.

#### 6.7.2 ApprovalRequestDetailScreen — `/persetujuan/:id`
**Tujuan**: detail satu pengajuan + riwayat + aksi Setujui/Tolak/Delegasikan (hanya bila `can_act=true` dari API **dan** status masih `submitted`/`in_progress`).
**Wireframe**:
```
┌ Detail Pengajuan                       [Kembali] ┐
│ Leave Request           ●Diproses                  │
│ Diajukan 2 hari lalu                                │
│ Catatan: ...                                        │
│ [Setujui] [Tolak] [Delegasikan]  (hanya jika can_act)│
│                                                      │
│ ┌ Riwayat ───────────────────────────────────────┐ │
│ │ Diajukan — 2 hari lalu                          │ │
│ │ Lanjut ke langkah berikutnya — 1 hari lalu      │ │
│ └──────────────────────────────────────────────────┘│
└──────────────────────────────────────────────────────┘
Modal Setujui: Komentar (opsional) → [Setujui]
Modal Tolak: Alasan Penolakan (wajib) → [Tolak] (danger)
Modal Delegasikan: Cari Pengguna (debounced search, min 2 karakter)
  → pilih dari hasil → Komentar (opsional) → [Delegasikan]
```
**Widget**: `DetailScreenPattern`, Card riwayat (`ListTile` per histori: label event, deskripsi, waktu relatif), 3 `AppModal` aksi.
**State**: `loading/data/error`; `activeModal: none|approve|reject|delegate`; tiap modal `isSubmitting` sendiri; modal Tolak punya `validationError` lokal (komentar wajib diisi sebelum submit); modal Delegasikan punya `query/results/selectedUser` (debounce 0ms di React karena langsung call tiap keystroke ≥2 char — Flutter sebaiknya tambah debounce ~300ms sebagai perbaikan wajar, bukan penyimpangan fitur).
**API**: `GET /approval-requests/{id}`, `POST /approval-request-steps/{stepId}/approve` `{comment?}`, `POST .../reject` `{comment}`, `POST .../delegate` `{delegate_to, comment?}`; pencarian delegasi: `GET /users?search=&per_page=10`.
**Validasi**: Tolak — komentar wajib (tidak boleh kosong/whitespace); Delegasikan — tombol Delegasikan disabled sampai satu user dipilih dari hasil pencarian.
**Navigasi**: dari `ApprovalRequestsScreen` atau notifikasi; setelah aksi sukses, data di layar diperbarui in-place (bukan navigasi keluar) dan modal tertutup; error submit → `AppAlert` top-level + modal ikut tertutup (padanan `onClose()` dipanggil di catch React).

#### 6.7.3 ApprovalWorkflowsScreen — `/persetujuan/alur`
**Tujuan**: lihat konfigurasi alur persetujuan (read-only di fase ini). **Wireframe**: `ListScreenPattern` tanpa search/filter — kolom Nama Alur+Deskripsi, Berlaku Untuk (dari `workflowable_type`), Jumlah Langkah, Badge status. Deskripsi Card: "Konfigurasi alur persetujuan (baca saja pada tahap ini)". **Widget/State**: 6.0 tanpa paginasi footer di React (list belum dipotong halaman di UI meski API tetap paginated). **API**: `GET /approval-workflows`. **Validasi**: tidak ada — tidak ada aksi tulis. **Navigasi**: menu Persetujuan→Alur Persetujuan; tidak ada Detail screen (baris tidak dapat di-tap).

---

### 6.8 Modul Skripsi

#### 6.8.1 ThesesScreen — `/skripsi`
**Tujuan**: List skripsi mahasiswa. **Wireframe**: `ListScreenPattern` — cari judul; kolom Judul+Nama/NIM mahasiswa, Pembimbing, Badge status (6 warna: Pengajuan Judul=neutral, Bimbingan/Seminar Proposal=info, Penelitian/Sidang=warning, Selesai=success). **Widget/State**: 6.0. **API**: `GET /theses` (`filter[status]`, `filter[thesis_type]`). **Validasi**: tidak ada. **Navigasi**: menu Skripsi, atau `StudentDetailScreen`; tap baris → `ThesisDetailScreen`.

#### 6.8.2 ThesisDetailScreen — `/skripsi/:id`
**Tujuan**: detail skripsi. **Wireframe**: Card — Judul, Nama/NIM, Badge status, Jenis (skripsi/tesis/disertasi), Pembimbing, Diajukan, Selesai. **Widget**: `DetailScreenPattern` 1 Card. **State**: `loading/data/error`. **API**: `GET /theses/{id}`. **Validasi**: tidak ada. **Navigasi**: dari `ThesesScreen`.

---

### 6.9 Modul Magang dan MBKM

#### 6.9.1 InternshipsScreen — `/magang-mbkm`
**Tujuan**: List magang/KKN/MBKM. **Wireframe**: `ListScreenPattern` — cari nama instansi; kolom Mahasiswa, Instansi+Posisi, Badge Jenis Program (info: Magang/KKN/MBKM), Badge status (Terdaftar=neutral, Berlangsung=info, Selesai=success, Dibatalkan=danger). **Widget/State**: 6.0. **API**: `GET /internships` (`filter[status]`, `filter[program_type]`). **Validasi**: tidak ada. **Navigasi**: menu Magang dan MBKM, atau `StudentDetailScreen`; tap baris → `InternshipDetailScreen`.

#### 6.9.2 InternshipDetailScreen — `/magang-mbkm/:id`
**Tujuan**: detail satu magang/MBKM. **Wireframe**: Card — Instansi, Nama/NIM mahasiswa, Badge status, Badge Jenis Program, Posisi, Pembimbing, Mulai, Selesai, SKS Dikonversi. **Widget**: `DetailScreenPattern` 1 Card. **State**: `loading/data/error`. **API**: `GET /internships/{id}`. **Validasi**: tidak ada. **Navigasi**: dari `InternshipsScreen`.

---

### 6.10 Modul Perpustakaan

#### 6.10.1 BooksScreen — `/perpustakaan`
**Tujuan**: katalog buku. **Wireframe**: `ListScreenPattern` — cari judul/penulis/ISBN; kolom Judul+Penulis, Kategori, Stok, jumlah Dipinjam, Badge status. **Widget/State**: 6.0. **API**: `GET /books` (`filter[category]`, `filter[is_active]`). **Validasi**: tidak ada. **Navigasi**: menu Perpustakaan; tap baris → `BookDetailScreen`.

#### 6.10.2 BookDetailScreen — `/perpustakaan/:id`
**Tujuan**: detail buku + riwayat peminjaman. **Wireframe**: Card ringkasan (Judul, Penulis, Badge status, Penerbit, ISBN, Kategori, Stok) → Card "Riwayat Peminjaman" (list nama/NIM peminjam + rentang tanggal pinjam-jatuh tempo + Badge status pinjaman: Dipinjam=info, Dikembalikan=success, Terlambat=danger). **Widget**: `DetailScreenPattern`. **State**: `loading/data/error`; riwayat kosong → "Belum ada riwayat peminjaman untuk buku ini." **API**: `GET /books/{id}` (loans sudah termuat). **Validasi**: tidak ada. **Navigasi**: dari `BooksScreen`.

---

### 6.11 Modul Alumni

#### 6.11.1 AlumniScreen — `/alumni`
**Tujuan**: List profil alumni. **Wireframe**: `ListScreenPattern` (tanpa search bar — hanya filter deep-link `employment_status`/`graduation_year`); kolom Nama/NIM, Program Studi, Tahun Lulus, Badge Status Pekerjaan (5 warna), Badge Terverifikasi. **Widget/State**: 6.0. **API**: `GET /alumni` (`filter[employment_status]`, `filter[graduation_year]`). **Validasi**: tidak ada. **Navigasi**: menu Alumni; tap baris → `AlumniDetailScreen`.

#### 6.11.2 AlumniDetailScreen — `/alumni/:id`
**Tujuan**: detail alumni. **Wireframe**: Card — Nama/NIM+Prodi, Badge Status Pekerjaan, Tahun Lulus, Badge Terverifikasi, Perusahaan, Jabatan, Masa Tunggu Kerja (bulan). **Widget**: `DetailScreenPattern` 1 Card. **State**: `loading/data/error`. **API**: `GET /alumni/{id}`. **Validasi**: tidak ada. **Navigasi**: dari `AlumniScreen`.

---

### 6.12 Modul Pengumuman

#### 6.12.1 AnnouncementsScreen — `/pengumuman`
**Tujuan**: List pengumuman universitas. **Wireframe**: `ListScreenPattern` — cari judul; kolom Judul (ikon pin bila `is_pinned`), Badge Cakupan (Universitas/Fakultas/Program Studi), Dibuat Oleh, Tanggal Terbit. **Widget/State**: 6.0. **API**: `GET /announcements` (`filter[target_scope]`, `filter[is_pinned]`). **Validasi**: tidak ada. **Navigasi**: menu Pengumuman; tap baris → `AnnouncementDetailScreen`.

#### 6.12.2 AnnouncementDetailScreen — `/pengumuman/:id`
**Tujuan**: baca pengumuman lengkap. **Wireframe**: Card — Judul, Cakupan+Tanggal Terbit sebagai deskripsi, Dibuat Oleh, Ditandai Penting (Ya/Tidak), lalu isi pengumuman lengkap (`whitespace-pre-line` → `Text` dengan `\n` dihormati). **Widget**: `DetailScreenPattern` 1 Card. **State**: `loading/data/error`. **API**: `GET /announcements/{id}`. **Validasi**: tidak ada. **Navigasi**: dari `AnnouncementsScreen`.

---

### 6.13 Modul Laporan

#### 6.13.1 ReportsScreen — `/laporan`
**Tujuan**: pilih jenis laporan (mahasiswa/akademik/keuangan/sdm), lihat ringkasan+tabel data, unduh CSV.
**Wireframe**:
```
┌ Laporan   Laporan dan ekspor data...            ┐
│ Jenis Laporan [Mahasiswa ▾]      [⬇ Unduh CSV]  │
│ ─────────────────────────────────────────────── │
│ Total Baris: 480   Aktif: 420   Cuti: 12   ...   │
│ [Alert warning: "Menampilkan 100 dari 480 baris.  │
│  Gunakan Unduh CSV untuk data lengkap."]          │
│ ┌ Tabel kolom dinamis sesuai jenis laporan ─────┐ │
│ └────────────────────────────────────────────────┘│
└────────────────────────────────────────────────────┘
```
**Widget**: `AppDropdownField` jenis laporan (opsi dari API), `AppButton` outline unduh (disabled sampai data laporan termuat), Card ringkasan (grid key-value dari `summary`, jumlah dinamis→numerik diformat, non-numerik apa adanya), `AppAlert` warning kondisional (`truncated=true`), `AppDataTable` dengan **kolom dinamis** (`columns` dari respons, bukan hardcode) menampilkan **baris apa adanya dari API** (tanpa paginasi klien — backend membatasi jumlah baris preview, unduh CSV untuk data penuh).
**State**: `reportTypes: loading/data`, `report: loading/data/error` (refetch tiap `selectedType` berubah), `isDownloading` saat proses unduh CSV.
**API**: `GET /reports` (daftar jenis), `GET /reports/{type}`, `GET /reports/{type}?export=csv` (unduh, respons biner — di Flutter disimpan via `Directory`/`share_plus`, bukan trigger `<a download>` seperti web; lihat BAB 8.9 untuk pola simpan file device).
**Validasi**: tidak ada form input selain pemilihan jenis laporan.
**Navigasi**: menu Laporan; tidak ada navigasi keluar ke halaman lain.

---

### 6.14 Modul Pengaturan

#### 6.14.1 RolesScreen — `/pengaturan/roles`
**Tujuan**: CRUD role + kelola permission per role.
**Wireframe**:
```
┌ Role                              [+ Tambah Role] ┐
│ [ Cari nama atau slug role... ]                     │
│ ┌ Role/slug │Deskripsi│Tipe   │[Kelola Permission][Ubah][Hapus]┐
│ │ Admin Prodi│ ..      │Kustom │                               │
│ └──────────────────────────────────────────────────────────┘  │
│ Halaman 1 dari 2 (18 role)  [Sebelumnya][Berikutnya]          │
└────────────────────────────────────────────────────────────────┘
Modal Tambah/Ubah: Nama Role, Deskripsi → [Simpan]
Modal Kelola Permission: checklist scroll seluruh permission
  (nama+slug) → [Simpan]
Modal Hapus: konfirmasi → [Hapus] (danger, disabled utk role sistem)
```
**Widget**: `AppScreenHeader`+tombol create (gated `roles.create`), `AppTextField` cari, `AppDataTable` (Badge Tipe Sistem/Kustom), tombol per-baris "Kelola Permission" (selalu tampil), "Ubah" (gated `roles.update`), "Hapus" (gated `roles.delete`, disembunyikan untuk role sistem), 3 `AppModal`.
**State**: list; form modal per-field errors; permission modal — `allPermissions loading/data`, `selected: Set<id>` (checklist), `isSaving`; delete modal `isDeleting`.
**API**: `GET /roles` (search), `POST`/`PUT /roles/{id}`, `DELETE /roles/{id}`, `PUT /roles/{id}/permissions` `{permission_ids:[]}`, opsi: `GET /permissions?per_page=100`.
**Validasi**: `name` wajib (maks 100), `description` opsional (maks 500).
**Navigasi**: menu Pengaturan→Role; semua aksi via modal, tidak pindah screen.

#### 6.14.2 PermissionsScreen — `/pengaturan/permissions`
**Tujuan**: CRUD permission granular (resource+scope+action).
**Wireframe**:
```
┌ Permission                     [+ Tambah Permission] ┐
│ [ Cari nama, slug, atau resource... ]                  │
│ ┌ Nama/slug │Resource│Scope│Aksi │[Ubah][Hapus] ──────┐│
│ │ Baca Mahasiswa│students│●menu│●read│                 ││
│ └────────────────────────────────────────────────────┘│
└──────────────────────────────────────────────────────────┘
Modal Tambah: Nama, Resource, Scope(dropdown 4), Aksi(dropdown 10),
  Deskripsi → [Simpan]
Modal Ubah: Nama, Deskripsi saja (resource/scope/action immutable)
```
**Widget**: `AppDataTable` (2 Badge kolom: Scope=info, Aksi=primary), `AppModal` create (5 field, 2 dropdown enum) & update (2 field saja — field lain dikunci setelah dibuat), `AppModal` delete (disabled utk permission sistem).
**State**: sama pola 6.14.1.
**API**: `GET /permissions` (search), `POST /permissions`, `PUT /permissions/{id}` (hanya `name`/`description`), `DELETE /permissions/{id}`.
**Validasi**: create — `name`(maks 150), `resource`(maks 100), `scope`∈{menu,module,endpoint,data}, `action`∈{create,read,update,delete,approve,reject,export,import,publish,finalize} wajib pilih salah satu; `description` opsional maks 500.
**Navigasi**: menu Pengaturan→Permission.

#### 6.14.3 UserRolesScreen — `/pengaturan/user-roles`
**Tujuan**: assign/cabut role ke satu user tertentu — layout 2 kolom (cari user | kelola role user terpilih).
**Wireframe**:
```
┌ Role Pengguna                                        ┐
│ ┌ Cari Pengguna ──┐┌ Role — {nama terpilih} ─────────┐│
│ │ [ Cari nama/email]│ [Badge Admin ×][Badge Dosen ×] ││
│ │ Budi Santoso       │ Tambah Role                    ││
│ │ Siti Aminah        │ [Role ▾][Berlaku Hingga][+]    ││
│ └────────────────────┘└──────────────────────────────┘│
└───────────────────────────────────────────────────────┘
```
**Widget**: 2 `AppCard` sisi-berdampingan (di layar sempit ditumpuk vertikal — Card kiri collapsible/sheet terpisah): kiri — `AppTextField` cari (debounce 350ms, min 2 karakter) + list hasil tap-to-select; kanan — `AppEmptyState` bila belum pilih user, atau chip `AppBadge` per role (dengan tombol × cabut, gated `user_roles.delete`) + form tambah (`AppDropdownField` role yang belum dimiliki + `AppDatePicker` opsional "Berlaku Hingga" + tombol Tambahkan, gated `user_roles.create`).
**State**: `query/results/isSearching/searchError`; `selectedUser`; `userRoles: loading/data/error` (refetch tiap ganti user); `allRoles` (dimuat sekali); `selectedRoleId/expiresAt/isAssigning`; `revokingRoleId` per-chip.
**API**: `GET /roles?per_page=100`, `GET /users?search=&per_page=10`, `GET /users/{id}/roles`, `POST /users/{id}/roles` `{role_id, expires_at?}`, `DELETE /users/{id}/roles/{roleId}`.
**Validasi**: pencarian user aktif hanya bila query ≥2 karakter; tombol Tambahkan disabled sampai role dipilih.
**Navigasi**: menu Pengaturan→Role Pengguna.

#### 6.14.4 SystemSettingsScreen — `/pengaturan/system-settings`
**Tujuan**: lihat & ubah pengaturan sistem (key-value bertipe). **Wireframe**: `ListScreenPattern` (cari key/grup, tanpa filter dropdown) — kolom Key+Deskripsi, Grup, Nilai (tampilan sesuai tipe: boolean→"Aktif"/"Nonaktif", json→string JSON, lainnya apa adanya), Badge Publik Ya/Tidak, tombol "Ubah" (gated `system_settings.update`); `AppModal` edit — input berbeda per tipe (`boolean`→`Switch`, lainnya→`AppTextField`, `json` multi-baris). **State**: list; modal `value`/`boolValue` lokal, `isSaving`. **API**: `GET /system-settings` (search), `PUT /system-settings/{id}` `{value}` (boolean dikirim sebagai string `"true"`/`"false"`). **Validasi**: tidak ada validasi format eksplisit di client — nilai dikirim apa adanya sesuai tipe. **Navigasi**: menu Pengaturan→Pengaturan Sistem.

#### 6.14.5 FeatureFlagsScreen — `/pengaturan/feature-flags`
**Tujuan**: toggle on/off feature flag. **Wireframe**: `ListScreenPattern` (cari key/nama) — kolom Nama+Key, Deskripsi, kolom `Checkbox`/`Switch` Aktif langsung di baris (disabled bila tanpa permission `feature_flags.update` atau sedang toggling). **State**: list; `togglingId` per-baris. **API**: `GET /feature-flags` (search), `PUT /feature-flags/{id}` `{is_enabled}`. **Validasi**: tidak ada. **Navigasi**: menu Pengaturan→Feature Flag.

#### 6.14.6 NotificationSettingsScreen — `/pengaturan/notifikasi`
**Tujuan**: 3 sub-bagian dalam satu halaman — Template Notifikasi (CRUD), Channel Notifikasi (toggle sistem-wide: database/mail/push/whatsapp/sms), Preferensi Notifikasi Saya (toggle pribadi + tambah tipe baru).
**Wireframe**:
```
┌ Notifikasi                                             ┐
│ ┌ Template Notifikasi          [+ Tambah Template]    ┐│
│ │ Nama/event_key │Badge Channel│Subjek│Badge Status│Ubah││
│ └────────────────────────────────────────────────────┘│
│ ┌ Channel Notifikasi (aktif/nonaktifkan jalur kirim)   ┐│
│ │ Database          [Switch]                            ││
│ │ Email              [Switch]                            ││
│ │ Push                [Switch]                            ││
│ └────────────────────────────────────────────────────┘│
│ ┌ Preferensi Notifikasi Saya                            ┐│
│ │ approval.submitted (database)   [Switch]              ││
│ │ Channel[▾] Tipe Notifikasi[___] [Tambah]              ││
│ └────────────────────────────────────────────────────┘│
└────────────────────────────────────────────────────────┘
```
**Widget**: 3 `AppCard` noPadding terpisah; Card 1: `AppDataTable` + `AppModal` form (Event Key, Nama, `AppDropdownField` Channel, Subjek opsional, textarea Isi Template — gated create/update terpisah); Card 2: list `Switch` per channel (gated `notification_channels.update`); Card 3: list `Switch` per preferensi + form tambah baru (dropdown channel + text tipe notifikasi bebas + tombol Tambah).
**State**: `templates: ListNotifier`; `channels: loading/data/error` + `togglingChannel`; `preferences: loading/data/error` + `newPrefChannel/newPrefType/isSavingPreference`.
**API**: `GET/POST/PUT /notification-templates`, `GET/PUT /notification-channels`, `GET/POST /notification-preferences`.
**Validasi**: Template — `event_key`(maks150)+`name`(maks150)+`channel`(enum) wajib, `body_template` wajib, `subject` opsional(maks200); Preferensi tambah — tipe notifikasi tidak boleh kosong sebelum tombol Tambah aktif.
**Navigasi**: menu Pengaturan→Notifikasi.

#### 6.14.7 AuditLogScreen — `/pengaturan/audit-log`
**Tujuan**: telusuri jejak perubahan data sistem.
**Wireframe**:
```
┌ Audit Log                                              ┐
│ [Aksi ▾] [Tipe Entitas: ____] [Terapkan Filter]        │
│ ┌ Waktu │Pengguna│Badge Aksi│Entitas│[Detail] ────────┐│
│ │ 3j lalu│Budi     │●updated  │Role   │                ││
│ └────────────────────────────────────────────────────┘│
│ Halaman 1 dari 12 (230 entri)  [Sebelumnya][Berikutnya]│
└────────────────────────────────────────────────────────┘
Modal Detail: Pengguna, Aksi, Entitas#id, IP, Nilai Lama (JSON
pre-formatted), Nilai Baru (JSON pre-formatted)
```
**Widget**: `AppDropdownField` Aksi (6 opsi: created/updated/deleted/restored/exported/imported), `AppTextField` Tipe Entitas (free text, contoh placeholder path kelas backend), `AppDataTable` (Badge Aksi 6 warna), `AppModal` detail dengan 2 blok `SelectableText` monospace JSON.
**State**: list; `actionFilter/entityFilter` lokal (diterapkan manual via tombol, bukan realtime); `detail: AuditLogEntry?` untuk modal.
**API**: `GET /audit-logs` (`filter[action]`, `filter[auditable_type]`).
**Validasi**: tidak ada — filter murni query, backend yang memvalidasi nilai.
**Navigasi**: menu Pengaturan→Audit Log.

#### 6.14.8 (lihat 6.1.4) SecuritySessionsScreen
Sudah dijabarkan penuh di **6.1.4** karena secara konseptual bagian dari Auth (self-service), meski route-nya berada di path `/pengaturan/keamanan` di dalam grup menu Pengaturan.

---

### 6.15 Modul Portal Mahasiswa

⚠️ **Berlaku untuk seluruh 6.15**: di React, semua halaman ini memakai **data contoh statis** (konstanta hardcoded di kode, bukan hasil fetch API) — lihat catatan di `constants/nav.ts` dan komentar identik di tiap file halaman Portal. Layer data/domain Flutter tetap didesain penuh (Repository+Model+endpoint yang secara wajar mengikuti konvensi REST modul lain), tetapi **status implementasi aktualnya menunggu backend menyediakan endpoint Portal** — ditandai `⚠️ Backend belum tersedia` di kolom API tiap entri. Wireframe & widget tetap dirujuk presisi dari struktur data contoh yang ada di React agar saat backend siap, Screen tinggal disambungkan tanpa desain ulang.

#### 6.15.1 PortalDashboardScreen
Sudah dijabarkan di **6.2.3**.

#### 6.15.2 PortalProfileScreen — `/portal/profil`
**Tujuan**: lihat data pribadi mahasiswa + ajukan perubahan data (nama/alamat/telepon/email) + riwayat pengajuan perubahan.
**Wireframe**:
```
┌ Profil Saya                                     ┐
│ ┌ Data Pribadi — NIM 202400512 ─────────────────┐│
│ │ Nama, NIM, Prodi, Angkatan, Email, Telepon,     ││
│ │ Alamat, Badge Status Aktif                       ││
│ └──────────────────────────────────────────────────┘│
│ ┌ Ajukan Perubahan Data ─────────────────────────┐ │
│ │ Jenis Data[▾] Data Baru[___] Alasan[textarea]   │ │
│ │                          [Ajukan Perubahan]      │ │
│ └────────────────────────────────────────────────┘ │
│ ┌ Riwayat Pengajuan Perubahan Data ──────────────┐ │
│ │ Tanggal│Jenis Data│Badge Status ──────────────  │ │
│ └────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────┘
```
**Widget**: Card data pribadi (grid field statis), Card form (`AppDropdownField` jenis data: Alamat/Telepon/Email/Nama, `AppTextField` data baru, textarea alasan, tombol submit), Card riwayat `AppDataTable` (Badge status 3 warna: Diajukan=neutral, Disetujui=success, Ditolak=danger).
**State**: statis (data contoh); form belum tersambung submit sungguhan.
**API**: ⚠️ Backend belum tersedia — usulan: `GET /portal/profile`, `POST /portal/profile-change-requests`, `GET /portal/profile-change-requests`.
**Validasi**: (rancangan) jenis data & data baru wajib diisi; alasan wajib diisi.
**Navigasi**: menu Portal→Profil Saya.

#### 6.15.3 PortalKrsScreen — `/portal/krs`
**Tujuan**: lihat KRS semester berjalan + form tambah mata kuliah (murni tampilan). **Wireframe**: Card ringkasan (Total SKS Diambil, Maksimal SKS, Badge Status KRS) → Card "Mata Kuliah Diambil" (`AppDataTable`: Kode, MK, SKS, Kelas, Dosen, Jadwal) → Card "Tambah Mata Kuliah" (`AppDropdownField` MK + `AppDropdownField` Kelas + tombol "Tambahkan ke KRS", tanpa logic tambah/hapus sungguhan pada tabel di atas). **State**: statis. **API**: ⚠️ Backend belum tersedia — usulan reuse `GET /krs-items?filter[student_id]=me` dan `POST /krs-items` bila `student_id` diri sendiri diizinkan API. **Validasi**: (rancangan) MK & Kelas wajib dipilih. **Navigasi**: menu Portal→Akademik→KRS.

#### 6.15.4 PortalScheduleScreen — `/portal/jadwal-kuliah`
**Tujuan**: jadwal kuliah mingguan. **Wireframe**: 5 Card (Senin–Jumat), tiap Card list mata kuliah hari itu (jam, MK, ruang+dosen). **Widget**: `AppCard`×5 + `ListTile` per kelas. **State**: statis. **API**: ⚠️ Backend belum tersedia — usulan `GET /portal/schedule`. **Validasi**: tidak ada. **Navigasi**: menu Portal→Akademik→Jadwal Kuliah.

#### 6.15.5 PortalKhsScreen — `/portal/khs`
**Tujuan**: Kartu Hasil Studi per semester (dipilih dari dropdown). **Wireframe**: `AppDropdownField` Semester → Card Ringkasan Semester (IP Semester, SKS Diambil, SKS Lulus, Badge Status) → Card "Rincian Nilai per Mata Kuliah" (`AppDataTable`: Kode, MK, SKS, Badge Nilai Huruf, Bobot, Nilai Angka). **State**: statis, ganti semester di dropdown belum memuat data berbeda sungguhan (React: `defaultValue` saja, tidak ada `onChange` handler nyata — tetap direplikasi persis, siap disambung `onChange→refetch` begitu backend ada). **API**: ⚠️ Backend belum tersedia — usulan `GET /portal/khs?academic_term_id=`. **Validasi**: tidak ada. **Navigasi**: menu Portal→Akademik→KHS.

#### 6.15.6 PortalTranscriptScreen — `/portal/transkrip`
**Tujuan**: transkrip sementara (bukan dokumen resmi) + unduh PDF. **Wireframe**: `AppScreenHeader`+tombol "Unduh PDF" → Card "Ringkasan Akademik" (Total SKS Lulus, IPK, Semester Berjalan, Badge Status Akademik) → 1 Card per semester (`AppDataTable` nilai + baris footer "IP Semester: x.xx"). Deskripsi header menegaskan dokumen ini tidak resmi. **State**: statis. **API**: ⚠️ Backend belum tersedia — usulan `GET /portal/transcript`, unduh PDF via `GET /portal/transcript/pdf` (blob, lihat BAB 8.9 pola simpan file). **Validasi**: tidak ada. **Navigasi**: menu Portal→Akademik→Transkrip Sementara.

#### 6.15.7 PortalGradesScreen — `/portal/nilai`
**Tujuan**: seluruh nilai mata kuliah yang pernah diambil (lintas semester, list datar). **Wireframe**: Card noPadding, `AppDataTable`: MK, Periode, SKS, Badge Nilai Huruf (atau "Belum dinilai" bila `null`), Skor. **State**: statis. **API**: ⚠️ Backend belum tersedia — usulan `GET /grades?filter[student_id]=me`. **Validasi**: tidak ada. **Navigasi**: menu Portal→Akademik→Nilai.

#### 6.15.8 PortalAttendanceScreen — `/portal/absensi`
**Tujuan**: presensi kelas hari ini (tombol check-in) + ringkasan rekap + riwayat. **Wireframe**: Card "Presensi Kelas Hari Ini" (per kelas: sudah presensi→Badge success, belum→tombol "Lakukan Presensi") → grid 4 `AppStatCard` (Hadir/Izin/Sakit/Alpa) → Card "Riwayat Kehadiran" (`AppDataTable`: Tanggal, MK, Pertemuan ke-, Badge Status). **State**: statis; tombol "Lakukan Presensi" belum memanggil apa pun. **API**: ⚠️ Backend belum tersedia — usulan `GET /portal/attendance/today`, `POST /portal/attendance/check-in`, `GET /attendances?filter[student_id]=me`. **Validasi**: tidak ada. **Navigasi**: menu Portal→Akademik→Absensi.

#### 6.15.9 PortalAssignmentsScreen — `/portal/tugas`
**Tujuan**: daftar tugas kuliah + tombol kumpulkan. **Wireframe**: Card noPadding, `AppDataTable`: Judul Tugas, MK, Tenggat, Badge Status (Belum Dikumpulkan=warning, Dikumpulkan=success, Terlambat=danger, Dinilai=info dgn skor di label), kolom Aksi tombol "Kumpulkan" (hanya utk status Belum Dikumpulkan/Terlambat). **State**: statis. **API**: ⚠️ Backend belum tersedia — usulan `GET /portal/assignments`, `POST /portal/assignments/{id}/submit` (multipart, lihat FileUploadField). **Validasi**: tidak ada di list; submission (rancangan) butuh file terlampir. **Navigasi**: menu Portal→Perkuliahan→Tugas.

#### 6.15.10 PortalQuizzesScreen — `/portal/kuis`
**Tujuan**: daftar kuis + mulai/lanjutkan kuis. **Wireframe**: Card noPadding, `AppDataTable`: Judul Kuis, MK, Durasi, Batas Waktu, Badge Status (Belum Dikerjakan=warning, Sedang Berlangsung=info, Selesai=success dgn skor), kolom Aksi ("Mulai Kuis"/"Lanjutkan", disembunyikan bila Selesai). **State**: statis. **API**: ⚠️ Backend belum tersedia — usulan `GET /portal/quizzes`, `POST /portal/quizzes/{id}/start`, `POST /portal/quizzes/{id}/submit`. **Validasi**: tidak ada di list. **Navigasi**: menu Portal→Perkuliahan→Kuis; tombol Mulai/Lanjutkan (rancangan) → screen pengerjaan kuis terpisah (belum ada padanan di React — di luar cakupan dokumen ini, ⚠️ perlu perancangan lanjutan bila backend sudah siap).

#### 6.15.11 PortalLeaveRequestScreen — `/portal/cuti`
**Tujuan**: ajukan cuti akademik + riwayat pengajuan. **Wireframe**: Card "Ajukan Cuti Baru" (`AppDropdownField` Semester Cuti + `AppDropdownField` Jenis Cuti [Akademik/Sakit/Melahirkan] + textarea Alasan → tombol "Ajukan Cuti") → Card "Riwayat Pengajuan Cuti" (`AppDataTable`: Semester, Jenis, Tanggal, Badge Status 3 warna). **State**: statis. **API**: ⚠️ Backend belum tersedia — usulan `POST /portal/leave-requests`, `GET /portal/leave-requests`. **Validasi**: (rancangan) semester & jenis wajib dipilih, alasan wajib diisi. **Navigasi**: menu Portal→Pengajuan→Cuti.

#### 6.15.12 PortalLetterRequestScreen — `/portal/surat`
**Tujuan**: ajukan surat (4 jenis: Aktif Kuliah, Bebas Perpustakaan, Pengantar Penelitian, Lulus Sementara) + riwayat + unduh surat selesai. **Wireframe**: Card "Ajukan Surat Baru" (`AppDropdownField` Jenis Surat + textarea Keperluan → tombol) → Card "Riwayat Pengajuan Surat" (`AppDataTable`: Jenis, Tanggal, Badge Status 4 warna, kolom Aksi "Unduh" muncul hanya utk status Selesai). **State**: statis. **API**: ⚠️ Backend belum tersedia — usulan `POST /portal/letter-requests`, `GET /portal/letter-requests`, `GET /portal/letter-requests/{id}/download`. **Validasi**: (rancangan) jenis surat wajib dipilih, keperluan wajib diisi. **Navigasi**: menu Portal→Pengajuan→Surat.

#### 6.15.13 PortalScholarshipScreen — `/portal/beasiswa`
**Tujuan**: lihat beasiswa yang sedang dibuka + ajukan + riwayat pengajuan pribadi. **Wireframe**: Card "Beasiswa yang Sedang Dibuka" (`AppDataTable`: Nama, Penyedia, Besaran (Rupiah), Batas Pendaftaran, tombol "Ajukan") → Card "Riwayat Pengajuan Beasiswa Saya" (`AppDataTable`: Nama, Tanggal, Badge Status 4 warna). **State**: statis. **API**: modul beasiswa admin **sudah ada** (`GET /scholarships`, `GET /scholarship-applications`) — Portal tinggal menyaring `is_active=true` & `student_id=me`; endpoint **submit pengajuan mahasiswa** (`POST /scholarship-applications` dari sisi mahasiswa) ⚠️ belum terlihat di `scholarshipService.ts` React (hanya ada `index`, tanpa `create`) — perlu konfirmasi backend. **Validasi**: tidak ada di client saat ini. **Navigasi**: menu Portal→Pengajuan→Beasiswa.

#### 6.15.14 PortalInvoicesScreen — `/portal/tagihan`
**Tujuan**: tagihan & riwayat pembayaran pribadi + tombol bayar. **Wireframe**: Card ringkasan (Total Tagihan Semester Ini, Sudah Dibayar, Sisa Tagihan (merah bila >0), Jatuh Tempo) → Card "Tagihan Saya" (`AppDataTable`: Periode, Jumlah, Terbayar, Badge Status, tombol "Bayar Sekarang" bila belum lunas) → Card "Riwayat Pembayaran" (`AppDataTable`: Tanggal, Jumlah, Metode). **State**: statis. **API**: modul keuangan admin sudah ada (`GET /invoices?filter[student_id]=me`, `GET /payments`); endpoint **inisiasi pembayaran mahasiswa** (payment gateway) ⚠️ belum ada di React sama sekali — perlu penentuan penyedia pembayaran (VA/e-wallet) dari tim backend sebelum diimplementasi. **Validasi**: tidak ada di client saat ini. **Navigasi**: menu Portal→Tagihan.

#### 6.15.15 PortalAcademicAdvisingScreen — `/portal/bimbingan-akademik`
**Tujuan**: info dosen PA + jadwal bimbingan berikutnya + riwayat. **Wireframe**: Card "Dosen Pembimbing Akademik" (Nama, NIDN, Email, Badge Aktif) → Card "Jadwal Bimbingan Berikutnya" (+tombol "Ajukan Jadwal Baru") → Card "Riwayat Bimbingan" (`AppDataTable`: Tanggal, Topik, Catatan Dosen, Badge Status 3 warna). **State**: statis. **API**: ⚠️ Backend belum tersedia — usulan `GET /portal/academic-advisor`, `GET /portal/academic-advising-sessions`, `POST /portal/academic-advising-sessions`. **Validasi**: tidak ada saat ini. **Navigasi**: menu Portal→Bimbingan→Bimbingan Akademik.

#### 6.15.16 PortalThesisAdvisingScreen — `/portal/bimbingan-skripsi`
**Tujuan**: status skripsi pribadi + logbook bimbingan + ajukan jadwal bimbingan baru. **Wireframe**: Card judul skripsi (deskripsi "Dosen Pembimbing: ...") — Badge status skripsi (6 warna, sama enum admin), Tanggal Pengajuan, Progres teks bebas → Card "Logbook Bimbingan" (`AppDataTable`: Tanggal, Sesi ke-, Catatan, Badge Status 2 warna) → Card "Ajukan Jadwal Bimbingan Baru" (`AppDatePicker` + textarea Agenda → tombol). **State**: statis. **API**: modul skripsi admin sudah ada (`GET /theses?filter[student_id]=me`); logbook & pengajuan jadwal ⚠️ belum ada endpoint — usulan `GET /theses/{id}/logbook`, `POST /theses/{id}/advising-sessions`. **Validasi**: (rancangan) tanggal & agenda wajib diisi. **Navigasi**: menu Portal→Bimbingan→Bimbingan Skripsi.

#### 6.15.17 PortalAnnouncementsScreen — `/portal/pengumuman`
**Tujuan**: feed pengumuman untuk mahasiswa (kartu, bukan tabel). **Wireframe**: list `AppCard` (satu per pengumuman) — judul (+ikon pin bila penting), "Diterbitkan {tanggal} · {cakupan}", cuplikan 2 baris (`maxLines:2, overflow:ellipsis`). Tap kartu → (rancangan) buka detail penuh, meski React belum memasang link tap di sini. **State**: statis. **API**: modul pengumuman admin **sudah ada** dan langsung bisa dipakai — `GET /announcements` (backend menyaring cakupan otomatis sesuai fakultas/prodi mahasiswa, sama seperti dashboard men-scope data by permission). **Validasi**: tidak ada. **Navigasi**: menu Portal→Pengumuman; (rancangan) tap kartu → `AnnouncementDetailScreen` (dapat memakai screen yang sama dengan 6.12.2, karena struktur data identik `Announcement`).

#### 6.15.18 PortalLecturerEvaluationScreen — `/portal/evaluasi-dosen`
**Tujuan**: isi evaluasi dosen per mata kuliah, anonim, per semester. **Wireframe**: Card noPadding `AppDataTable` (MK, Dosen, Badge Status Belum/Sudah Diisi, tombol "Isi Evaluasi") → Card "Contoh Formulir Evaluasi" (pratinjau statis: 4 pertanyaan rating 1–5 sebagai tombol bulat, textarea saran, tombol "Kirim Evaluasi"). **State**: statis; formulir pratinjau belum terhubung ke baris tabel manapun (React: form contoh generik, bukan per-MK terpilih — direplikasi apa adanya, dengan catatan desain nyata perlu form-per-evaluasi terikat baris saat backend siap). **API**: ⚠️ Backend belum tersedia — usulan `GET /portal/lecturer-evaluations`, `POST /portal/lecturer-evaluations/{id}/submit` `{ratings:[...], comment}`. **Validasi**: (rancangan) seluruh pertanyaan wajib diberi rating sebelum kirim. **Navigasi**: menu Portal→Evaluasi Dosen.

#### 6.15.19 PortalGraduationScreen — `/portal/wisuda`
**Tujuan**: cek kelayakan wisuda + form pendaftaran + status pendaftaran. **Wireframe**: Card "Status Kelayakan Wisuda" (Badge besar "Memenuhi Syarat Wisuda" + grid: Total SKS Lulus, IPK Minimum, Badge Skripsi Selesai, Badge Bebas Tanggungan Perpustakaan, Badge Bebas Tanggungan Keuangan) → Card "Formulir Pendaftaran Wisuda" (`AppDropdownField` Periode Wisuda, `AppTextField` Ukuran Toga, textarea Catatan → tombol "Daftar Wisuda") → Card "Status Pendaftaran" (`AppDataTable`: Periode, Tanggal Daftar, Badge Status). **State**: statis. **API**: ⚠️ Backend belum tersedia — usulan `GET /portal/graduation-eligibility`, `POST /portal/graduation-registrations`, `GET /portal/graduation-registrations`. **Validasi**: (rancangan) periode wajib dipilih. **Navigasi**: menu Portal→Pendaftaran Wisuda.

---

## BAB 7 — Navigasi

### 7.1 Struktur `go_router` Lengkap

Padanan `constants/routes.ts` (`ROUTES`) + `App.tsx` (`APP_ROUTES`). Path memakai skema yang **identik** dengan React (memudahkan pemetaan deep link — BAB 8.8) — hanya prefix cukup ditambah bila diperlukan konsistensi native (di sini tetap dipertahankan sama persis).

```dart
// core/router/route_paths.dart
abstract final class RoutePaths {
  static const login = '/login';
  static const forgotPassword = '/forgot-password';
  static const resetPassword = '/reset-password';
  static const dashboard = '/dashboard';

  static const persetujuan = '/persetujuan';
  static const persetujuanDetail = '/persetujuan/:id';
  static const persetujuanWorkflow = '/persetujuan/alur';

  static const akademikKurikulum = '/akademik/kurikulum';
  static const akademikKurikulumDetail = '/akademik/kurikulum/:id';
  static const akademikMataKuliah = '/akademik/mata-kuliah';
  static const akademikMataKuliahDetail = '/akademik/mata-kuliah/:id';
  static const akademikKelasJadwal = '/akademik/kelas-jadwal';
  static const akademikKelasJadwalDetail = '/akademik/kelas-jadwal/:id';
  static const akademikProgramStudi = '/akademik/program-studi';
  static const akademikProgramStudiDetail = '/akademik/program-studi/:id';
  static const akademikKrs = '/akademik/krs';
  static const akademikAbsensi = '/akademik/absensi';
  static const akademikPenilaian = '/akademik/penilaian';

  static const mahasiswa = '/mahasiswa';
  static const mahasiswaDetail = '/mahasiswa/:id';
  static const dosen = '/dosen';
  static const dosenDetail = '/dosen/:id';
  static const pegawai = '/pegawai';
  static const pegawaiDetail = '/pegawai/:id';

  static const keuanganTagihan = '/keuangan/tagihan';
  static const keuanganTagihanDetail = '/keuangan/tagihan/:id';
  static const keuanganPembayaran = '/keuangan/pembayaran';
  static const keuanganBeasiswa = '/keuangan/beasiswa';
  static const keuanganBeasiswaDetail = '/keuangan/beasiswa/:id';

  static const skripsi = '/skripsi';
  static const skripsiDetail = '/skripsi/:id';
  static const magangMbkm = '/magang-mbkm';
  static const magangMbkmDetail = '/magang-mbkm/:id';
  static const perpustakaan = '/perpustakaan';
  static const perpustakaanDetail = '/perpustakaan/:id';
  static const alumni = '/alumni';
  static const alumniDetail = '/alumni/:id';
  static const pengumuman = '/pengumuman';
  static const pengumumanDetail = '/pengumuman/:id';
  static const laporan = '/laporan';

  static const pengaturanRoles = '/pengaturan/roles';
  static const pengaturanPermissions = '/pengaturan/permissions';
  static const pengaturanUserRoles = '/pengaturan/user-roles';
  static const pengaturanSystemSettings = '/pengaturan/system-settings';
  static const pengaturanFeatureFlags = '/pengaturan/feature-flags';
  static const pengaturanNotifikasi = '/pengaturan/notifikasi';
  static const pengaturanAuditLog = '/pengaturan/audit-log';
  static const pengaturanKeamanan = '/pengaturan/keamanan';

  static const platformUniversitas = '/platform/universitas';
  static const platformKeamanan = '/platform/keamanan';

  static const portalProfil = '/portal/profil';
  static const portalKrs = '/portal/krs';
  static const portalJadwal = '/portal/jadwal-kuliah';
  static const portalKhs = '/portal/khs';
  static const portalTranskrip = '/portal/transkrip';
  static const portalNilai = '/portal/nilai';
  static const portalAbsensi = '/portal/absensi';
  static const portalTugas = '/portal/tugas';
  static const portalKuis = '/portal/kuis';
  static const portalCuti = '/portal/cuti';
  static const portalSurat = '/portal/surat';
  static const portalBeasiswa = '/portal/beasiswa';
  static const portalTagihan = '/portal/tagihan';
  static const portalBimbinganAkademik = '/portal/bimbingan-akademik';
  static const portalBimbinganSkripsi = '/portal/bimbingan-skripsi';
  static const portalPengumuman = '/portal/pengumuman';
  static const portalEvaluasiDosen = '/portal/evaluasi-dosen';
  static const portalWisuda = '/portal/wisuda';
}
```

```dart
// core/router/app_router.dart
final appRouterProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authControllerProvider);

  return GoRouter(
    initialLocation: RoutePaths.dashboard,
    refreshListenable: GoRouterRefreshStream(ref.watch(authStateChangesProvider.stream)),
    redirect: (context, state) {
      final isAuthenticated = authState.valueOrNull != null;
      final isAuthRoute = [RoutePaths.login, RoutePaths.forgotPassword, RoutePaths.resetPassword]
          .contains(state.matchedLocation);

      if (!isAuthenticated && !isAuthRoute) {
        return Uri(path: RoutePaths.login, queryParameters: {'from': state.matchedLocation}).toString();
      }
      if (isAuthenticated && isAuthRoute) return RoutePaths.dashboard;
      return null;
    },
    routes: [
      // --- Publik (padanan PublicOnlyRoute + AuthLayout) ---
      GoRoute(path: RoutePaths.login, builder: (_, __) => const LoginScreen()),
      GoRoute(path: RoutePaths.forgotPassword, builder: (_, __) => const ForgotPasswordScreen()),
      GoRoute(
        path: RoutePaths.resetPassword,
        builder: (_, state) => ResetPasswordScreen(
          token: state.uri.queryParameters['token'],
          email: state.uri.queryParameters['email'],
        ),
      ),

      // --- Terproteksi, dibungkus ShellRoute (padanan ProtectedRoute + DashboardLayout) ---
      ShellRoute(
        builder: (context, state, child) => AppShell(child: child), // Drawer + Header + TenantModeBanner
        routes: [
          GoRoute(
            path: RoutePaths.dashboard,
            builder: (_, __) => const DashboardRouteBuilder(), // pemilihan varian, lihat 6.2
          ),
          ...academicRoutes,   // lihat 7.1 lanjutan per modul
          ...financeRoutes,
          ...approvalRoutes,
          ...thesisRoutes,
          ...internshipRoutes,
          ...libraryRoutes,
          ...alumniRoutes,
          ...announcementRoutes,
          ...reportRoutes,
          ...settingsRoutes,
          ...platformRoutes,
          ...portalRoutes,
        ],
      ),
    ],
    errorBuilder: (_, __) => const NotFoundRedirectScreen(), // -> redirect ke /dashboard, padanan Route path="*"
  );
});
```

Tiap `GoRoute` dalam grup modul (`academicRoutes` dkk) dibungkus `PermissionGate` persis pola `RequirePermission` di React:

```dart
GoRoute(
  path: RoutePaths.mahasiswa,
  builder: (_, __) => const PermissionGate(permission: 'students.read', child: StudentsScreen()),
),
GoRoute(
  path: RoutePaths.mahasiswaDetail,
  builder: (_, state) => PermissionGate(
    permission: 'students.read',
    child: StudentDetailScreen(id: state.pathParameters['id']!),
  ),
),
```

Rute detail yang **tidak** disertakan di `DETAIL_ROUTE_PERMISSIONS` React (`/persetujuan`, `/persetujuan/:id`) sengaja **tidak** dibungkus `PermissionGate` sama sekali — mengikuti alasan yang sama persis di komentar `routePermissions.ts` (BAB 2.4): backend `ApprovalRequestPolicy` sudah mengizinkan requester/assigned-approver melihat pengajuannya sendiri tanpa `approval_requests.read`; menutup route di client akan salah memblokir akses yang justru diizinkan API.

### 7.2 Navigasi Utama (Drawer) & Quick Access (Bottom Nav)

React memakai satu Sidebar kolaps untuk **seluruh** menu (≤12 grup, ~40 item termasuk submenu) — pada layar mobile sempit ini tidak muat sebagai bottom navigation bar biasa (maksimal wajar 3–5 tab). Desain adaptasi:

- **Drawer** (`NavigationDrawer` Material 3) — padanan langsung `Sidebar.tsx`: berisi **seluruh** struktur menu tergantung konteks (lihat 7.3), termasuk grup expand/collapse (`ExpansionTile` padanan submenu accordion `openSubmenus` React).
- **Bottom Navigation Bar** (opsional, quick access 4 item tetap: Dashboard, Cari, Notifikasi, Profil/Menu — item terakhir membuka Drawer) — **bukan** padanan langsung dari React (web tidak punya bottom nav), murni pola ergonomis mobile standar untuk item yang paling sering diakses; seluruh 71 halaman tetap dapat dijangkau lewat Drawer, bottom nav hanya jalan pintas.
- **AppBar** per Screen — padanan `Header.tsx`: tombol buka Drawer (hamburger), search (khusus Screen list yang punya search bar sendiri, header search global React saat ini juga belum tersambung logic sungguhan — lihat catatan `searchValue` di `Header.tsx` yang tidak dipakai memfilter apa pun, ⚠️ replikasi sebagai UI saja), `TenantSwitcher` (Super Admin), `ThemeToggle` singkat (ikon, buka bottom sheet 3-opsi — lihat 5.3), ikon Notifikasi (badge titik merah bila ada unread — data contoh statis di React `data/mock/notifications.ts`, ⚠️ perlu endpoint sungguhan sebelum produksi, lihat BAB 8.3), avatar dropdown (Profil Saya→`SecuritySessionsScreen`, Pengaturan→`SystemSettingsScreen`, Keluar).

### 7.3 Struktur Menu per Konteks (padanan `constants/nav.ts`)

```dart
// core/permission/nav_items.dart — 1:1 dengan NAV_ITEMS/PLATFORM_NAV_ITEMS/PORTAL_NAV_ITEMS/TENANT_BUSINESS_NAV_ITEMS
final tenantBusinessNavItems = <NavItem>[
  NavItem.group('Akademik', icon: LucideIcons.graduationCap, path: RoutePaths.akademikKrs, children: [
    NavChild('Program Studi', RoutePaths.akademikProgramStudi, permission: 'study_programs.read'),
    NavChild('Kurikulum', RoutePaths.akademikKurikulum, permission: 'curriculums.read'),
    NavChild('Mata Kuliah', RoutePaths.akademikMataKuliah, permission: 'courses.read'),
    NavChild('Kelas dan Jadwal', RoutePaths.akademikKelasJadwal, permission: 'classes.read'),
    NavChild('KRS', RoutePaths.akademikKrs, permission: 'krs.read'),
    NavChild('Absensi', RoutePaths.akademikAbsensi, permission: 'attendance.read'),
    NavChild('Penilaian', RoutePaths.akademikPenilaian, permission: 'grades.read'),
  ]),
  NavItem.single('Mahasiswa', icon: LucideIcons.users, path: RoutePaths.mahasiswa, permission: 'students.read'),
  NavItem.single('Dosen', icon: LucideIcons.userRound, path: RoutePaths.dosen, permission: 'lecturers.read'),
  NavItem.single('Pegawai', icon: LucideIcons.briefcase, path: RoutePaths.pegawai, permission: 'employees.read'),
  NavItem.group('Keuangan', icon: LucideIcons.wallet, path: RoutePaths.keuanganTagihan, children: [
    NavChild('Tagihan', RoutePaths.keuanganTagihan, permission: 'invoices.read'),
    NavChild('Pembayaran', RoutePaths.keuanganPembayaran, permission: 'invoices.read'),
    NavChild('Beasiswa', RoutePaths.keuanganBeasiswa, permission: 'scholarships.read'),
  ]),
  NavItem.single('Skripsi', icon: LucideIcons.scrollText, path: RoutePaths.skripsi, permission: 'theses.read'),
  NavItem.single('Magang dan MBKM', icon: LucideIcons.handshake, path: RoutePaths.magangMbkm, permission: 'internships.read'),
  NavItem.single('Perpustakaan', icon: LucideIcons.library, path: RoutePaths.perpustakaan, permission: 'books.read'),
  NavItem.single('Alumni', icon: LucideIcons.userCheck, path: RoutePaths.alumni, permission: 'alumni.read'),
  NavItem.single('Pengumuman', icon: LucideIcons.megaphone, path: RoutePaths.pengumuman, permission: 'announcements.read'),
  NavItem.single('Laporan', icon: LucideIcons.barChart3, path: RoutePaths.laporan, permission: 'reports.read'),
];
```

Logika penyaringan Drawer mengikuti `Sidebar.tsx` **persis**:

1. **Super Admin, belum pilih tenant** → hanya `PLATFORM_NAV_ITEMS` (Dashboard, Manajemen Universitas, Keamanan Platform, Persetujuan, Pengaturan).
2. **Super Admin, sudah pilih tenant** (Tenant Switcher aktif) → `PLATFORM_NAV_ITEMS` 3 item pertama + `tenantBusinessNavItems` disisipkan di tengah + sisa `PLATFORM_NAV_ITEMS` (Persetujuan, Pengaturan) — lalu **seluruhnya** disaring lagi lewat `filterNavItems` per permission pengguna.
3. **Mahasiswa** (`isStudent`) → `PORTAL_NAV_ITEMS` **tanpa** filter permission sama sekali (menu Portal murni kepemilikan data, bukan RBAC — lihat komentar `nav.ts`).
4. **Tenant biasa** (bukan Super Admin, bukan mahasiswa) → `NAV_ITEMS` (Dashboard + `tenantBusinessNavItems` + Persetujuan + Pengaturan), disaring `filterNavItems`.

Aturan `filterNavItems`: item tanpa `children` disembunyikan bila tidak punya salah satu `permission` yang diminta (OR-match); item **dengan** `children` disaring lewat anak-anaknya saja (permission di level induk diabaikan) — bila seluruh anak tersaring habis, induk ikut disembunyikan.

### 7.4 Route Guard untuk Halaman yang Butuh Login

Dua lapis guard, identik pembagian tanggung jawab React:

1. **`redirect` di `GoRouter`** (7.1) — padanan `ProtectedRoute`/`PublicOnlyRoute`: memutuskan apakah pengguna boleh berada di grup route publik vs terproteksi sama sekali, berdasar `authControllerProvider` (ada/tidaknya sesi tersimpan). Tujuan awal (`from`) disimpan sebagai query param agar setelah login berhasil, pengguna diarahkan balik kesana (padanan `location.state.from`).
2. **`PermissionGate` widget** (BAB 3.4) — padanan `RequirePermission`: dipasang di `builder` tiap `GoRoute` individual yang butuh permission spesifik; merender `AccessDeniedScreen` (6.3.1) **di tempat** (bukan redirect) kalau permission tidak dipenuhi, sehingga path URL/back-stack tetap konsisten dengan yang diketik/ditap pengguna.

### 7.5 Penanganan Deep Link

Dibahas rinci di **BAB 8.8**; ringkasan keterkaitan dengan router: seluruh path di 7.1 didaftarkan sebagai **App Link** Android (`https://<domain-app>/...`) dan **custom scheme** (`civitasone://...`) sekaligus, keduanya diarahkan ke `GoRouter` yang sama sehingga logic redirect/permission di 7.1–7.4 otomatis berlaku juga untuk link masuk dari luar aplikasi (notifikasi push, email reset password, share link, dsb) — persis bagaimana `BrowserRouter` React memproses URL apa pun yang diketik langsung di browser.

---

## BAB 8 — Fitur Android Modern

Untuk tiap fitur: tujuan, package, cara implementasi, permission, catatan versi Android.

### 8.1 Dark Mode + Dynamic Color (Material You)

**Tujuan**: tema visual yang konsisten dengan preferensi tampilan sistem Android sekaligus mempertahankan identitas brand (BAB 5.1/5.3/5.5).
**Package**: `dynamic_color: ^1.7.0`, `google_fonts` (sudah dibahas BAB 5).
**Implementasi**:
```dart
// app.dart
DynamicColorBuilder(
  builder: (lightDynamic, darkDynamic) {
    final useDynamic = ref.watch(useDynamicColorProvider); // toggle di Pengaturan, default true
    return MaterialApp.router(
      theme: AppTheme.light(dynamicScheme: useDynamic ? lightDynamic : null),
      darkTheme: AppTheme.dark(dynamicScheme: useDynamic ? darkDynamic : null),
      themeMode: ref.watch(themeControllerProvider),
      // ...
    );
  },
);
```
**Permission**: tidak ada permission runtime — API `DynamicColorBuilder` membaca `android.content.res.Resources` sistem otomatis.
**Catatan versi**: Dynamic Color API (`android.app.WallpaperColors`) hanya tersedia Android 12+ (API 31); `dynamic_color` package otomatis fallback `null` (skema seed statis dipakai) di Android 7–11 (minSdk 24) — tidak perlu percabangan manual, package sudah menangani.

### 8.2 Edge-to-Edge Display & Predictive Back Gesture (Android 13+/14+)

**Tujuan**: tampilan penuh-layar modern (konten mengalir di balik status bar/navigation bar) dan gestur kembali prediktif (preview halaman sebelumnya saat swipe-back, Android 14+).
**Package**: tidak ada paket tambahan — API Flutter native (`SystemUiMode`, `PopScope`) + konfigurasi `AndroidManifest.xml`/`styles.xml`.
**Implementasi**:
```dart
// main.dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Colors.transparent,
    systemNavigationBarColor: Colors.transparent,
  ));
  runApp(const ProviderScope(child: CivitasOneApp()));
}
```
```xml
<!-- android/app/src/main/res/values/styles.xml -->
<style name="LaunchTheme" parent="@android:style/Theme.Black.NoTitleBar">
  <item name="android:windowLayoutInDisplayCutoutMode">shortEdges</item>
</style>
```
Predictive back — set `android:enableOnBackInvokedCallback="true"` di `<application>` manifest (wajib untuk targetSdk 34+), lalu tiap Screen dengan state belum tersimpan (mis. form modal terbuka) membungkus body dengan `PopScope(canPop: !hasUnsavedChanges, onPopInvokedWithResult: ...)` — padanan konfirmasi "keluar tanpa menyimpan" yang di React biasanya ditangani lewat `onClose` modal + `beforeunload` browser (tidak ada padanan langsung, ini penguatan native).
**Permission**: tidak ada.
**Catatan versi**: Edge-to-edge otomatis **wajib** (tidak bisa dimatikan) mulai targetSdk 35 (Android 15) — konfigurasi di atas menyiapkan app agar sudah edge-to-edge sejak sekarang, bukan menunggu dipaksa OS. Predictive back butuh targetSdk ≥34 dan Android 14 di perangkat; di Android 13 ke bawah, tombol back berperilaku standar (fallback otomatis, tidak crash).

### 8.3 Push Notification (FCM) + Notifikasi Lokal + Notification Channel

**Tujuan**: mendorong notifikasi (persetujuan baru, tagihan jatuh tempo, pengumuman, dsb) — dipetakan langsung dari channel `push` yang **sudah dimodelkan di backend** (`notification_channels`/`notification_templates`/`notification_preferences`, lihat 2.2 & 6.14.6), bukan fitur yang diada-adakan.
**Package**: `firebase_messaging: ^15.x`, `flutter_local_notifications: ^18.x`, `firebase_core: ^3.x`.
**Implementasi**:
1. Daftarkan project Firebase, unduh `google-services.json` ke `android/app/`.
2. Buat notification channel per jenis (mapping dari `notification_type` yang sama dipakai `NotificationPreferenceScreen`, mis. `approval_updates`, `academic_updates`, `finance_updates`, `announcements`):
```dart
const approvalChannel = AndroidNotificationChannel(
  'approval_updates', 'Persetujuan', description: 'Notifikasi status pengajuan persetujuan',
  importance: Importance.high,
);
await flutterLocalNotificationsPlugin
    .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
    ?.createNotificationChannel(approvalChannel);
```
3. Kirim FCM token perangkat ke backend saat login sukses — ⚠️ **PERLU KONFIRMASI**: React tidak punya endpoint registrasi token FCM (service `notificationService.ts` hanya mengelola channel & preference, bukan token device); backend perlu menambah endpoint semacam `POST /devices/fcm-token` sebelum push benar-benar terkirim.
4. `FirebaseMessaging.onMessage.listen` (foreground) → tampilkan via `flutter_local_notifications`; `onBackgroundMessage` (top-level function) untuk saat app tertutup; tap notifikasi → `GoRouter` push ke path terkait (mis. notifikasi persetujuan → `/persetujuan/{id}`, deep link internal — BAB 8.8).
5. Toggle channel & preference tetap memakai `NotificationSettingsScreen` (6.14.6) yang sudah ada — nilai `is_enabled` per channel/tipe dihormati sebelum menampilkan notifikasi lokal (walau FCM tetap diterima, notifikasi hanya ditampilkan bila `is_enabled=true` untuk channel `push` & tipe terkait, mengurangi gangguan sesuai preferensi pengguna).
**Permission**: `POST_NOTIFICATIONS` (Android 13+/API 33, runtime permission wajib diminta eksplisit — di bawahnya otomatis granted), `<uses-permission android:name="com.google.android.c2dm.permission.RECEIVE"/>` (ditambahkan otomatis oleh plugin).
**Catatan versi**: `POST_NOTIFICATIONS` runtime permission **hanya** relevan Android 13+; minSdk 24 tetap didukung (notifikasi tampil tanpa perlu minta izin di Android 7–12).

### 8.4 Autentikasi Biometrik (Fingerprint/Face) untuk Login Cepat

**Tujuan**: mempercepat re-login tanpa mengetik ulang password setiap buka app. React **tidak** punya alur ini (web LoginPage hanya email+password, BAB 6.1.1) — kapabilitas ini murni tambahan native yang wajar untuk mobile, **bukan** menggantikan alur login sungguhan: biometrik hanya membuka kembali **sesi yang sudah tersimpan** dari login password terakhir (tidak pernah menjadi metode autentikasi awal ke backend).
**Package**: `local_auth: ^2.x`.
**Implementasi**: setelah login password sukses pertama kali, tanyakan opt-in "Aktifkan Masuk Cepat dengan Sidik Jari/Wajah?" → bila ya, tandai `biometricEnabled=true` di `shared_preferences` (bukan menyimpan kredensial biometrik apa pun — hanya flag). Saat app dibuka kembali dengan sesi tersimpan (token valid di `flutter_secure_storage`) dan `biometricEnabled=true`, tampilkan layar kunci ringan yang meminta `LocalAuthentication().authenticate(localizedReason: 'Verifikasi identitas Anda untuk melanjutkan')` sebelum mengizinkan masuk ke `DashboardScreen` — gagal/batal → tetap bisa fallback masuk ulang lewat password (tidak mengunci akun).
**Permission**: `<uses-permission android:name="android.permission.USE_BIOMETRIC"/>` (untuk Android 9+/API 28; di bawahnya `local_auth` fallback ke `USE_FINGERPRINT`, sudah ditangani plugin).
**Catatan versi**: Biometric Prompt API modern butuh API 23+ (jauh di bawah minSdk 24, jadi selalu tersedia); tampilan/perilaku prompt sedikit berbeda API 28+ (BiometricManager) vs 23–27 (FingerprintManager lama) — sepenuhnya ditangani `local_auth`, tidak perlu percabangan manual.

### 8.5 Penyimpanan Token Aman (`flutter_secure_storage`)

**Tujuan**: mengganti `localStorage` (React, rentan diakses skrip lain di konteks web) dengan penyimpanan terenkripsi native Android — sudah wajib dibahas mendalam di **BAB 11.1**, ringkasan implementasi teknis di sini.
**Package**: `flutter_secure_storage: ^9.x`.
**Implementasi**: `FlutterSecureStorage` dikonfigurasi dengan `AndroidOptions(encryptedSharedPreferences: true)` — menyimpan `token` (padanan `session.token` di `authStore`) dan flag sensitif lain (mis. `biometricEnabled`, BAB 8.4). Data **non-sensitif** (theme mode, locale, `selectedUniversity` Tenant Switcher — padanan `tenantStore`/`themeStore`) tetap di `shared_preferences` biasa, karena tidak membawa risiko keamanan berarti bila terbaca (beda perlakuan ini disengaja, bukan kelalaian — token adalah satu-satunya kredensial akses API).
**Permission**: tidak ada permission runtime — `EncryptedSharedPreferences` memakai Android Keystore di balik layar.
**Catatan versi**: `EncryptedSharedPreferences` (Jetpack Security library) didukung penuh dari minSdk 23 ke atas — kompatibel dengan target minSdk 24 dokumen ini.

### 8.6 Mode Offline: Cache Lokal + Deteksi Koneksi + Banner Offline

**Tujuan**: aplikasi tetap bisa menampilkan data yang pernah dimuat saat koneksi hilang (umum di lingkungan kampus dengan konektivitas tidak stabil). ⚠️ React **tidak** punya mekanisme offline apa pun (tidak ada service worker/cache API terpasang) — ini murni penguatan native yang wajar sesuai mandat BAB 8, diterapkan hati-hati agar **tidak** menyesatkan (data lama harus jelas ditandai "data tersimpan", bukan seolah real-time).
**Package**: `connectivity_plus: ^6.x`, `hive_ce: ^2.x` (atau `hive`+`hive_flutter`).
**Implementasi**:
1. `ConnectivityController` (Riverpod `StreamProvider` di atas `Connectivity().onConnectivityChanged`) — dipakai `AppShell` (7.1) untuk menampilkan `OfflineBanner` tetap di atas layar (mirip pola `TenantModeBanner`, BAB 6/2.4) saat status `none`.
2. **Cache read-only untuk data referensi yang jarang berubah** (bukan seluruh data transaksional): daftar Program Studi, Kurikulum, Mata Kuliah, opsi filter Dashboard (`DashboardFilterOptions`), dan hasil `GET /me` (permission) — disimpan di Hive box terpisah per entity, dengan `cachedAt` timestamp. Saat offline, List Screen modul-modul ini menampilkan data cache + label kecil "Data tersimpan {waktu relatif}, sambungkan kembali untuk memperbarui" alih-alih `AppEmptyState`/`AppAlert` error kosong.
3. **Data transaksional** (tagihan, nilai, absensi, persetujuan, dsb) **tidak** di-cache sebagai kebijakan default — tetap menampilkan `AppAlert` error "Tidak ada koneksi internet" dengan tombol "Coba Lagi", karena data ini berubah cepat dan cache basi berisiko menyesatkan keputusan (mis. status pembayaran). ⚠️ Bila tim produk kelak memutuskan sebagian data transaksional tertentu perlu offline-first (mis. jadwal kuliah harian), evaluasi ulang per-kasus, jangan generalisasi.
4. Aksi tulis (submit form) yang gagal karena offline ditolak eksplisit dengan pesan jelas — **tidak** memakai antrian sinkronisasi otomatis (offline write queue) di Fase 1, karena berisiko konflik dengan validasi server-side (mis. slot kelas penuh) yang baru diketahui saat online — dicatat sebagai potensi Fase lanjutan, bukan default aman.
**Permission**: `ACCESS_NETWORK_STATE` (ditambahkan otomatis oleh `connectivity_plus`).
**Catatan versi**: tidak ada batasan versi Android spesifik; Hive murni penyimpanan file lokal, kompatibel seluruh minSdk 24+.

### 8.7 Pull-to-Refresh, Infinite Scroll, Skeleton/Shimmer Loading

**Tujuan**: pola loading data yang lebih natural di mobile dibanding tombol "Sebelumnya/Berikutnya" React (BAB 5.6 `ListPagination`), tanpa mengubah kontrak API paginasi backend.
**Package**: `shimmer: ^3.x` (skeleton), bawaan Flutter (`RefreshIndicator`, `ScrollController`).
**Implementasi**:
- **Pull-to-refresh**: setiap `ListScreenPattern` (6.0) dibungkus `RefreshIndicator(onRefresh: () => notifier.refetch())` — padanan tombol refresh implisit yang di React biasanya dilakukan lewat re-render `useEffect` dependency berubah; di mobile digantikan gestur tarik-turun standar Android.
- **Infinite scroll**: `ListNotifier` (3.3) menambah method `loadNextPage()` dipicu `ScrollController.addListener` saat jarak ke akhir list < 300px, **menyambung** ke halaman berikutnya dari `meta.current_page`/`meta.last_page` yang sama persis dipakai `AppPaginationFooter` — kedua pola (footer tombol eksplisit vs infinite-scroll) tersedia sebagai varian `ListScreenPattern`, dipilih per-modul: List dengan volume data besar & tak perlu presisi halaman (mis. Pengumuman, Alumni) → infinite-scroll; List administratif yang datanya sering dirujuk per-halaman (mis. Audit Log, Role, Permission) → footer eksplisit (memudahkan komunikasi "halaman berapa" antar staf).
- **Skeleton/shimmer**: `AppSkeleton` (5.6) dibungkus `Shimmer.fromColors` (warna dasar `surfaceHover`, highlight sedikit lebih terang) saat `state.isLoading` — padanan visual `Skeleton.tsx`/`SkeletonCard`/`SkeletonRow` React, tapi dengan animasi shimmer bergerak (peningkatan wajar dari `animate-pulse` CSS statis React ke idiom shimmer mobile yang lebih umum).
**Permission**: tidak ada.
**Catatan versi**: tidak ada batasan versi.

### 8.8 Deep Link & App Link

**Tujuan**: membuka path aplikasi langsung dari luar (notifikasi push BAB 8.3, email reset password BAB 6.1.3, share link antar pengguna).
**Package**: bawaan `go_router` (`GoRouter` sudah menangani parsing URI) + konfigurasi native.
**Implementasi**:
```xml
<!-- android/app/src/main/AndroidManifest.xml, di dalam <activity> utama -->
<intent-filter android:autoVerify="true">
  <action android:name="android.intent.action.VIEW" />
  <category android:name="android.intent.category.DEFAULT" />
  <category android:name="android.intent.category.BROWSABLE" />
  <data android:scheme="https" android:host="app.civitasone.example" />
</intent-filter>
<intent-filter>
  <action android:name="android.intent.action.VIEW" />
  <category android:name="android.intent.category.DEFAULT" />
  <category android:name="android.intent.category.BROWSABLE" />
  <data android:scheme="civitasone" />
</intent-filter>
```
Path yang dipetakan identik BAB 7.1 (mis. `https://app.civitasone.example/persetujuan/{id}` dan `civitasone://persetujuan/{id}` sama-sama membuka `ApprovalRequestDetailScreen`). Verifikasi App Link (`autoVerify`) butuh file `assetlinks.json` di-hosting pada domain terkait dengan fingerprint SHA-256 sertifikat rilis aplikasi.
**Permission**: tidak ada permission runtime; App Link verification otomatis oleh sistem.
**Catatan versi**: `android:autoVerify` App Link (domain terverifikasi, tanpa dialog pilih app) berfungsi penuh sejak Android 6 (API 23) — didukung penuh di minSdk 24.
⚠️ **PERLU KONFIRMASI**: domain resmi untuk App Link (`app.civitasone.example` di atas adalah placeholder) belum ditentukan — perlu dikoordinasikan dengan tim backend/infra sebelum rilis produksi, karena harus sama dengan domain yang mem-hosting `assetlinks.json` dan idealnya selaras dengan domain API (`VITE_API_BASE_URL`).

### 8.9 Upload/Ambil File & Kamera (Permission Handler)

**Tujuan**: padanan native `FileUploadField.tsx` (BAB 5.6) yang di web memakai `<input type=file>` — dipetakan ke endpoint `POST /file-uploads` yang sama persis (2.2).
**Package**: `file_picker: ^8.x` (pilih file dari penyimpanan), `image_picker: ^1.x` (kamera/galeri khusus foto), `permission_handler: ^11.x`, `dio` (multipart upload, sudah ada di 3.4).
**Implementasi**:
```dart
Future<void> pickAndUpload(WidgetRef ref, {required bool isPublic}) async {
  final source = await showModalBottomSheet<_PickSource>(...); // "Ambil Foto" | "Pilih dari Galeri" | "Pilih Berkas"
  final XFile? picked = switch (source) {
    _PickSource.camera => await ImagePicker().pickImage(source: ImageSource.camera, imageQuality: 85),
    _PickSource.gallery => await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 85),
    _PickSource.file => (await FilePicker.platform.pickFiles())?.files.single.let((f) => XFile(f.path!)),
    null => null,
  };
  if (picked == null) return;

  final formData = FormData.fromMap({
    'file': await MultipartFile.fromFile(picked.path, filename: picked.name),
    'is_public': isPublic.toString(),
  });
  await ref.read(fileUploadRepositoryProvider).upload(formData); // POST /file-uploads, header multipart/form-data
}
```
Unduh berkas (`GET /file-uploads/{id}/download`, respons blob — sama pola `reportService.downloadCsv`, 6.13.1) memakai `dio.download()` ke `getApplicationDocumentsDirectory()`/`getExternalStorageDirectory()`, lalu `OpenFilex.open(path)` atau `Share.shareXFiles([...])` (padanan trigger `<a download>` browser, tapi hasil akhirnya file tersimpan di perangkat + opsi buka/bagikan — lebih berguna di konteks native dibanding sekadar "unduh").
**Permission**: `CAMERA` (untuk `ImageSource.camera`), `READ_MEDIA_IMAGES`/`READ_MEDIA_VIDEO` (Android 13+, API 33) menggantikan `READ_EXTERNAL_STORAGE` (Android 12 ke bawah) — `permission_handler` + `image_picker`/`file_picker` versi terbaru sudah menangani percabangan ini otomatis via manifest merge, tapi **runtime request** tetap wajib dilakukan eksplisit sebelum memanggil picker (`permission_handler.Permission.camera.request()`), dengan penjelasan alasan (rationale dialog) sebelum meminta bila `shouldShowRequestRationale=true`.
**Catatan versi**: skema permission media Android 13+ (granular per tipe) berbeda dari Android ≤12 (`READ_EXTERNAL_STORAGE` tunggal) — wajib deklarasi manifest ganda (`maxSdkVersion="32"` untuk yang lama), sudah jadi tanggung jawab plugin versi terbaru, tetap perlu diverifikasi manual di `AndroidManifest.xml` hasil generate.

### 8.10 Home Screen Shortcuts dan/atau Widget

**Tujuan**: akses cepat ke aksi paling sering dipakai tanpa membuka app dulu — kapabilitas native murni (React/web tidak punya padanan sama sekali), dipilih berdasarkan aksi yang paling sering dipakai tiap peran (lihat BAB 6).
**Package**: `quick_actions: ^1.x` (shortcut, ditekan-lama ikon app).
**Implementasi**:
```dart
final quickActions = QuickActions();
await quickActions.initialize((type) {
  switch (type) {
    case 'action_krs': context.go(RoutePaths.akademikKrs); // atau portalKrs, tergantung role login terakhir
    case 'action_persetujuan': context.go(RoutePaths.persetujuan);
    case 'action_absensi': context.go(RoutePaths.akademikAbsensi); // atau portalAbsensi
  }
});
await quickActions.setShortcutItems(shortcutsForCurrentRole(lastKnownRole)); // 3-4 item, disesuaikan role terakhir login
```
Karena shortcut Android statis-per-sesi (tidak bisa membaca state Riverpod saat app belum jalan), daftar shortcut disegarkan **setiap kali login sukses/ganti tenant** berdasar role terakhir yang diketahui (disimpan di `shared_preferences`), bukan realtime.
**Widget App (home screen widget)**: Fase lanjutan (⚠️ opsional, di luar cakupan wajib dokumen ini) — memerlukan implementasi native Kotlin terpisah (`Glance` App Widget API) menampilkan ringkasan (mis. "Jadwal Hari Ini" untuk mahasiswa, "3 Persetujuan Menunggu" untuk admin), berkomunikasi dengan Flutter lewat `home_widget` package + shared storage. Dicatat sebagai rekomendasi Fase 6+ (BAB 12), bukan blocker rilis awal.
**Permission**: tidak ada permission runtime untuk shortcut; App Widget (bila dikerjakan) butuh deklarasi `<receiver>` khusus di manifest, bukan runtime permission.
**Catatan versi**: `quick_actions` (static shortcuts) didukung sejak API 25 (Android 7.1) — **catatan penting**: minSdk dokumen ini adalah 24 (Android 7.0 murni, sebelum 7.1), sehingga shortcut **tidak akan tersedia** di perangkat Android 7.0 tepat (API 24) — implementasi harus memeriksa `Build.VERSION.SDK_INT` dan menyembunyikan UI terkait shortcut secara graceful di perangkat API 24, tanpa memengaruhi fitur lain.

### 8.11 Aksesibilitas: Kontras, Ukuran Teks Dinamis, Screen Reader

**Tujuan**: memastikan aplikasi dapat dipakai pengguna dengan keterbatasan penglihatan/motorik — sejalan dengan token warna React yang sudah dirancang mempertahankan kontras wajar di kedua tema (BAB 2.3/5.1).
**Package**: bawaan Flutter (`Semantics`, `MediaQuery.textScalerOf`).
**Implementasi**:
- **Ukuran teks dinamis**: `MaterialApp.builder` membungkus child dengan `MediaQuery(data: MediaQuery.of(context).copyWith(textScaler: TextScaler.linear(scaleClamped)))` — `scaleClamped` membatasi skala pengaturan sistem ke rentang wajar (mis. 0.85×–1.6×) agar layout Card/DataTable (BAB 5.6) tidak pecah pada skala ekstrem, sambil tetap menghormati preferensi pengguna dalam batas aman.
- **Kontras**: seluruh pasangan warna teks-di-atas-latar di BAB 5.1 sudah memenuhi rasio kontras WCAG AA (≥4.5:1 teks normal) baik di light maupun dark — diverifikasi manual sekali di awal implementasi tema (BAB 5.2) memakai alat kontras standar; ikon status (Badge) selalu disertai **label teks**, tidak pernah warna semata (mis. Badge status bukan cuma titik warna, tapi teks "Aktif"/"Nonaktif" — sudah demikian di React, dipertahankan).
- **Screen reader (TalkBack)**: setiap ikon-only button (mis. tombol Kembali AppBar, ikon Bell notifikasi, toggle password visibility) diberi `Semantics(label: '...', button: true)` atau properti `tooltip`/`semanticLabel` widget bawaan; `AppDataTable` varian mobile (list Card) memakai `MergeSemantics` per baris agar TalkBack membacakan satu baris sebagai satu unit koheren, bukan field terpisah-pisah.
- Pengujian: `flutter test` dengan `SemanticsTester` untuk Screen kunci (Login, Dashboard, Detail modul inti) + pengujian manual TalkBack sebelum rilis tiap Fase (BAB 12/13).
**Permission**: tidak ada.
**Catatan versi**: tidak ada batasan versi — API aksesibilitas Flutter/Android yang dipakai kompatibel sejak API level rendah.

### 8.12 Multi-Bahasa (ID/EN) dengan `flutter_localizations`

**Tujuan**: mendukung Bahasa Indonesia (default, sumber kebenaran) dan Inggris. ⚠️ React **tidak** punya infrastruktur i18n sama sekali — seluruh string UI hardcoded Bahasa Indonesia langsung di JSX (tidak ada library `i18next`/`react-intl` di `package.json`). Flutter memperkenalkan i18n sebagai kapabilitas baru; **string Indonesia diekstrak apa adanya dari UI React** (BAB 6) sebagai isi `app_id.arb`, lalu diterjemahkan ke `app_en.arb` — tidak ada sumber Inggris siap pakai untuk dijadikan rujukan, terjemahan Inggris perlu direview oleh tim yang menguasai istilah akademik lokal (banyak istilah seperti "KRS"/"KHS"/"MBKM" tidak punya padanan Inggris baku satu kata, kemungkinan tetap dipertahankan sebagai istilah + penjelasan).
**Package**: `flutter_localizations` (SDK), `intl: ^0.19.x`.
**Implementasi**: `l10n.yaml` di root + `lib/core/l10n/app_id.arb` (template utama) & `app_en.arb`, digenerate `flutter gen-l10n` menjadi kelas `AppLocalizations` yang dipakai lewat `AppLocalizations.of(context)!.loginTitle` dsb, menggantikan string literal di seluruh Screen BAB 6. `LocaleController` (Riverpod, persisted `shared_preferences`, mirip pola `ThemeController` 5.3) menyimpan pilihan bahasa eksplisit pengguna (bukan hanya ikut sistem — beda dengan Dark Mode yang defaultnya "Ikuti Sistem", locale defaultnya **Indonesia tetap** kecuali pengguna eksplisit memilih Inggris, karena basis pengguna dominan lokal).
**Permission**: tidak ada.
**Catatan versi**: tidak ada batasan versi Android.
⚠️ **PERLU KONFIRMASI**: cakupan Fase 1 — apakah seluruh 71 Screen (BAB 6) wajib diterjemahkan penuh sebelum rilis awal, atau ID-only dulu dengan struktur i18n disiapkan (arsitektur ARB sudah ada, tinggal isi bertahap)? Direkomendasikan opsi kedua agar tidak memperlambat Fase 1–4 (BAB 12).

### 8.13 Splash Screen Native (Android 12 Splash Screen API)

**Tujuan**: transisi buka-aplikasi yang mulus memakai API splash screen resmi Android 12+, dengan fallback tema custom di versi lebih lama — padanan visual dari `AuthLayout.tsx` (logo + nama app saat loading awal, BAB 6.1).
**Package**: `flutter_native_splash: ^2.x` (dev dependency, code generator).
**Implementasi**: `flutter_native_splash.yaml`:
```yaml
flutter_native_splash:
  color: "#F8FAFC"          # AppColorsLight.background
  color_dark: "#0A0F1A"     # AppColorsDark.background
  image: assets/splash/logo_atmajaya.png
  image_dark: assets/splash/logo_atmajaya.png
  android_12:
    image: assets/splash/logo_atmajaya_512.png
    icon_background_color: "#F8FAFC"
    icon_background_color_dark: "#0A0F1A"
  android: true
```
lalu `dart run flutter_native_splash:create`.
**Permission**: tidak ada.
**Catatan versi**: Splash Screen API resmi (`android:windowSplashScreenBackground` dsb) hanya berlaku native Android 12+ (API 31); `flutter_native_splash` otomatis menyediakan **fallback tema drawable klasik** untuk Android 7–11 (minSdk 24) agar tampilan tetap konsisten (logo+background sama, hanya mekanisme render OS yang berbeda di balik layar).
⚠️ **PERLU KONFIRMASI**: `logo-atmajaya.gif` di React (`frontend/src/assets/images/logo-atmajaya.gif`) adalah **GIF** — Splash Screen API Android tidak mendukung format GIF/animasi untuk splash icon. Perlu diekspor ulang sebagai PNG statis resolusi tinggi (disarankan 512×512 minimum) sebelum dipakai di konfigurasi di atas.

### 8.14 Crash Reporting & Analytics

**Tujuan**: memantau stabilitas aplikasi di produksi & memahami pola pemakaian fitur. ⚠️ React **tidak** memakai tool crash reporting/analytics apa pun (tidak ada Sentry/Google Analytics/dsb di `package.json`) — pilihan tool berikut adalah rekomendasi default, bukan keputusan final tim.
**Package**: `firebase_crashlytics: ^4.x`, `firebase_analytics: ^11.x`.
**Implementasi**:
```dart
// main.dart
FlutterError.onError = FirebaseCrashlytics.instance.recordFlutterFatalError;
PlatformDispatcher.instance.onError = (error, stack) {
  FirebaseCrashlytics.instance.recordError(error, stack, fatal: true);
  return true;
};
```
Event analytics kunci yang direkomendasikan dilacak (nonpribadi, agregat): `login_success`, `approval_action` (approve/reject/delegate), `krs_enrolled`, `attendance_recorded`, `report_downloaded`, `screen_view` otomatis per route (`FirebaseAnalyticsObserver` dipasang di `GoRouter.observers`). Data pribadi (nama, NIM, email) **tidak pernah** dikirim sebagai parameter event — hanya ID/slug/anonymized count.
**Permission**: `INTERNET` (sudah ada untuk kebutuhan API), tidak ada permission runtime tambahan.
**Catatan versi**: tidak ada batasan versi Android untuk Firebase SDK modern (kompatibel minSdk 24 ke atas — cek dokumentasi Firebase terbaru untuk minSdk minimum yang mungkin naik seiring waktu).
⚠️ **PERLU KONFIRMASI**: pilihan Firebase Crashlytics/Analytics perlu disetujui tim (ada alternatif seperti Sentry yang lebih tool-agnostic bila proyek berencana multi-platform non-Firebase), dan perlu persetujuan kebijakan privasi/data pengguna institusi pendidikan sebelum mengaktifkan analytics di produksi.

---

## BAB 9 — Daftar Package

### 9.1 Tabel Package

| Package | Versi | Fungsi | Alasan dipilih |
|---|---|---|---|
| `flutter_riverpod` | ^2.6.x | State management + DI (BAB 3.2/3.4) | Padanan langsung pola hooks generik React (`useFetch`/`usePaginatedList`), tanpa BuildContext untuk akses state di interceptor |
| `riverpod_annotation` + `riverpod_generator` (dev) | ^2.6.x / ^2.6.x | Code-gen Provider (kurangi boilerplate) | Mengurangi verbosity manual Provider declaration |
| `build_runner` (dev) | ^2.4.x | Jalankan code generator (riverpod/json_serializable/freezed) | Wajib untuk seluruh codegen di proyek |
| `go_router` | ^14.x | Routing deklaratif + deep link (BAB 7) | Padanan `react-router-dom`, mendukung guard/redirect/ShellRoute setara `ProtectedRoute`/`DashboardLayout` |
| `dio` | ^5.x | HTTP client + interceptor (BAB 3.4) | Padanan `axios`, mendukung interceptor request/response & multipart |
| `freezed` (dev) + `freezed_annotation` | ^2.5.x | Immutable model + `copyWith`/union (BAB 10) | Padanan pola immutable TypeScript interface + generate `fromJson/toJson/copyWith` konsisten |
| `json_serializable` (dev) + `json_annotation` | ^6.9.x / ^4.9.x | Codegen `fromJson`/`toJson` | Menghindari boilerplate parsing manual utk ~35 model (BAB 2.2/10) |
| `flutter_secure_storage` | ^9.x | Token/sesi terenkripsi (BAB 8.5/11.1) | Pengganti aman `localStorage` React |
| `shared_preferences` | ^2.3.x | Preferensi non-sensitif (tema, locale, tenant, BAB 5.3/8.12) | Padanan `localStorage` untuk data non-kredensial (persist Zustand React) |
| `hive_ce` + `hive_ce_flutter` | ^2.x | Cache offline data referensi (BAB 8.6) | Ringan, tanpa native DB engine, cocok cache read-only |
| `connectivity_plus` | ^6.x | Deteksi status koneksi (BAB 8.6) | Standar de-facto Flutter untuk network state |
| `google_fonts` | ^6.2.x | Font Inter (BAB 5.4) | Sama font family persis dengan `index.html` React (`fonts.googleapis.com`) |
| `dynamic_color` | ^1.7.x | Material You (BAB 8.1) | Implementasi resmi Google utk `DynamicColorBuilder` |
| `lucide_icons` (atau `lucide_icons_flutter`) | ^0.x (cek versi stabil terbaru saat bootstrap) | Ikon 1:1 `lucide-react` (BAB 2.3/5.6) | Parity ikon persis dengan sumber React |
| `fl_chart` | ^0.69.x | Chart Dashboard (area/bar/pie/line, BAB 6.2) | Padanan `recharts`, fleksibel & ringan untuk 8 tipe chart Dashboard |
| `shimmer` | ^3.x | Efek loading skeleton (BAB 8.7) | Padanan `animate-pulse` React dengan idiom shimmer mobile |
| `reactive_forms` + `reactive_forms_generator` (opsional) | ^17.x | Form group + validator (BAB 6, pola create/update) | Padanan `react-hook-form` + `zod` — validator sinkron, error per-field, `setError` server-side (`applyServerErrors`) |
| `intl` | ^0.19.x | Format angka/mata uang/tanggal (BAB 6, `formatters.ts`) | Padanan `Intl.NumberFormat('id-ID')`/`Intl.DateTimeFormat('id-ID')` React persis |
| `flutter_localizations` (SDK) | — | Multi-bahasa ID/EN (BAB 8.12) | Wajib untuk `MaterialApp.supportedLocales` |
| `local_auth` | ^2.3.x | Biometrik (BAB 8.4) | Wrapper resmi BiometricPrompt Android |
| `firebase_core`, `firebase_messaging` | ^3.x / ^15.x | Push notification (BAB 8.3) | Infrastruktur FCM standar |
| `flutter_local_notifications` | ^18.x | Render notifikasi lokal + channel (BAB 8.3) | Kontrol penuh notification channel per tipe |
| `firebase_crashlytics`, `firebase_analytics` | ^4.x / ^11.x | Crash reporting & analytics (BAB 8.14) | Terintegrasi ekosistem Firebase yang sama dgn FCM |
| `file_picker` | ^8.x | Pilih berkas non-gambar (BAB 8.9) | Padanan `<input type=file>` generik |
| `image_picker` | ^1.x | Ambil foto kamera/galeri (BAB 8.9) | UX native kamera, tidak ada padanan langsung React (web) |
| `permission_handler` | ^11.x | Kelola runtime permission (BAB 8.3/8.4/8.9) | Standar Flutter untuk seluruh permission Android |
| `path_provider` | ^2.x | Lokasi direktori penyimpanan file unduhan (BAB 8.9/6.13) | Diperlukan `dio.download()`/simpan file |
| `open_filex` | ^4.x | Buka file hasil unduh dengan app lain (BAB 8.9) | Padanan pengalaman klik unduhan browser |
| `share_plus` | ^10.x | Bagikan file/tautan (BAB 8.9) | Melengkapi alur unduh CSV/PDF/surat |
| `quick_actions` | ^1.x | Shortcut layar utama (BAB 8.10) | API resmi Flutter untuk App Shortcuts Android |
| `flutter_native_splash` (dev) | ^2.4.x | Splash screen Android 12 API (BAB 8.13) | Generator resmi, menangani fallback versi lama otomatis |
| `flutter_launcher_icons` (dev) | ^0.14.x | Generate app icon adaptif | Kebutuhan build standar, tidak ada padanan React (favicon statis) |
| `cached_network_image` | ^3.4.x | Avatar/gambar dari URL dengan cache | Optimasi wajar utk `Avatar.tsx` bila backend kelak sediakan foto profil |
| `mocktail` (dev) | ^1.x | Mock di unit test (BAB 13) | Alternatif `mockito` tanpa codegen, lebih ringkas utk test repository/notifier |
| `integration_test` (SDK, dev) | — | End-to-end test (BAB 13) | Framework resmi Flutter utk golden flow |

### 9.2 `pubspec.yaml` Siap Pakai

```yaml
name: civitasone
description: Aplikasi Android Civitas One — Sistem Informasi Akademik Universitas (SIAKAD) multi-tenant.
publish_to: 'none'
version: 0.1.0+1

environment:
  sdk: '>=3.9.0 <4.0.0'
  flutter: '>=3.35.0'

dependencies:
  flutter:
    sdk: flutter
  flutter_localizations:
    sdk: flutter

  # State management & DI
  flutter_riverpod: ^2.6.1
  riverpod_annotation: ^2.6.1

  # Routing
  go_router: ^14.8.1

  # Networking
  dio: ^5.7.0

  # Model & serialization
  freezed_annotation: ^2.5.7
  json_annotation: ^4.9.0

  # Storage
  flutter_secure_storage: ^9.2.2
  shared_preferences: ^2.3.5
  hive_ce: ^2.11.0
  hive_ce_flutter: ^2.2.0
  path_provider: ^2.1.5

  # Konektivitas & offline
  connectivity_plus: ^6.1.2

  # Design system
  google_fonts: ^6.2.1
  dynamic_color: ^1.7.0
  lucide_icons_flutter: ^3.0.1
  fl_chart: ^0.70.2
  shimmer: ^3.0.0
  cached_network_image: ^3.4.1

  # Form
  reactive_forms: ^17.0.1

  # Format & util
  intl: ^0.19.0

  # Biometrik
  local_auth: ^2.3.0

  # Push notification
  firebase_core: ^3.10.0
  firebase_messaging: ^15.2.0
  flutter_local_notifications: ^18.0.1

  # Crash & analytics
  firebase_crashlytics: ^4.3.0
  firebase_analytics: ^11.4.0

  # File & kamera
  file_picker: ^8.1.7
  image_picker: ^1.1.2
  permission_handler: ^11.3.1
  open_filex: ^4.5.0
  share_plus: ^10.1.4

  # Shortcut
  quick_actions: ^1.1.0

dev_dependencies:
  flutter_test:
    sdk: flutter
  integration_test:
    sdk: flutter

  build_runner: ^2.4.14
  riverpod_generator: ^2.6.3
  freezed: ^2.5.7
  json_serializable: ^6.9.0

  flutter_native_splash: ^2.4.4
  flutter_launcher_icons: ^0.14.2

  mocktail: ^1.0.4
  flutter_lints: ^5.0.0

flutter:
  uses-material-design: true
  generate: true # aktifkan gen-l10n (BAB 8.12)

  assets:
    - assets/images/
    - assets/splash/
```

⚠️ **PERLU KONFIRMASI**: nomor versi di atas adalah rilis stabil yang diketahui pada saat dokumen ini disusun — jalankan `flutter pub outdated` setelah `flutter create` awal proyek untuk menyesuaikan ke rilis terbaru yang kompatibel, terutama paket Firebase yang sering merilis versi baru.

---

## BAB 10 — Model Data

### 10.1 Pendekatan

Backend mengekspos **~35 entity** (BAB 2.2/18 file `types/*.ts` React). Menulis 35 class Dart secara manual akan menghasilkan boilerplate hampir identik berulang-ulang (constructor + `fromJson` + `toJson` + `copyWith` per field) — alih-alih itu, seluruh model memakai **satu pola codegen seragam** (`freezed` + `json_serializable`, sudah didaftarkan BAB 9), dicontohkan lengkap lewat **5 model kunci mewakili tiap bentuk data yang ada** (entity sederhana, entity dengan enum, entity ber-relasi nested, request payload, response envelope generik) — sisanya (BAB 10.3) cukup mengikuti pola yang sama persis terhadap field yang didaftarkan tabelnya, dijalankan lewat `dart run build_runner build --delete-conflicting-outputs`.

Konvensi seragam untuk **seluruh** model:
- Nama field JSON `snake_case` dari backend dipetakan ke Dart `camelCase` lewat anotasi `@JsonKey(name: '...')` (padanan field TypeScript interface React yang sengaja dibiarkan `snake_case` apa adanya — Dart tetap mengikuti gaya penamaan bahasa sendiri, `camelCase`, agar konsisten dengan konvensi Dart standar, **bukan** penyimpangan dari kontrak API).
- Field backend yang nullable (`string | null` di TypeScript) → `String?` Dart, field wajib → non-nullable.
- Enum backend (union string literal TypeScript, mis. `StudentStatus`) → `enum` Dart dengan `@JsonValue('slug_asli')` per konstanta, agar nilai serialisasi tetap persis string yang dipahami backend meski nama konstanta Dart pakai `camelCase`.
- Setiap model punya method domain `toEntity()`/factory `Model.fromEntity(...)` bila Entity domain (BAB 3.1) dipisah dari Model data — untuk kesederhanaan, dokumen ini memperbolehkan Model data **sekaligus** menjadi Entity domain pada modul CRUD sederhana (mayoritas data master/referensi), dan baru dipisah eksplisit pada modul dengan logika non-trivial (Approval, Dashboard).

### 10.2 Contoh Lengkap (5 Model Kunci)

**AuthUser & AuthSession** (`features/auth/data/models/auth_user_model.dart`) — entity sederhana + relasi ke sesi:

```dart
import 'package:freezed_annotation/freezed_annotation.dart';

part 'auth_user_model.freezed.dart';
part 'auth_user_model.g.dart';

@freezed
class AuthUserModel with _$AuthUserModel {
  const factory AuthUserModel({
    required String id,
    required String name,
    required String email,
    /// Label tampilan tunggal, diturunkan dari roles[0] — dihitung di data source (lihat AuthRepositoryImpl.login), bukan dari server langsung.
    required String role,
    required List<String> roles,
    required List<String> permissions,
    @JsonKey(name: 'avatar_url') String? avatarUrl,
  }) = _AuthUserModel;

  factory AuthUserModel.fromJson(Map<String, dynamic> json) => _$AuthUserModelFromJson(json);
}

@freezed
class AuthSessionModel with _$AuthSessionModel {
  const factory AuthSessionModel({
    required AuthUserModel user,
    required String token,
  }) = _AuthSessionModel;

  factory AuthSessionModel.fromJson(Map<String, dynamic> json) => _$AuthSessionModelFromJson(json);
}
```

**Student** (`features/academic/data/models/student_model.dart`) — entity dengan enum:

```dart
import 'package:freezed_annotation/freezed_annotation.dart';

part 'student_model.freezed.dart';
part 'student_model.g.dart';

enum StudentStatus {
  @JsonValue('active') active,
  @JsonValue('leave') leave,
  @JsonValue('graduated') graduated,
  @JsonValue('inactive') inactive,
  @JsonValue('dropped_out') droppedOut,
}

@freezed
class StudentModel with _$StudentModel {
  const factory StudentModel({
    required String id,
    @JsonKey(name: 'study_program_id') required String studyProgramId,
    @JsonKey(name: 'study_program_name') String? studyProgramName,
    required String nim,
    required String name,
    String? email,
    @JsonKey(name: 'admission_year') required int admissionYear,
    required StudentStatus status,
    @JsonKey(name: 'enrolled_at') required String enrolledAt,
    @JsonKey(name: 'graduated_at') String? graduatedAt,
    @JsonKey(name: 'created_at') required String createdAt,
  }) = _StudentModel;

  factory StudentModel.fromJson(Map<String, dynamic> json) => _$StudentModelFromJson(json);
}
```

**Invoice & Payment** (`features/finance/data/models/invoice_model.dart`) — entity ber-relasi nested (list `Payment` di dalam `Invoice`, padanan `payments: Payment[]` React):

```dart
import 'package:freezed_annotation/freezed_annotation.dart';

part 'invoice_model.freezed.dart';
part 'invoice_model.g.dart';

enum InvoiceStatus { @JsonValue('unpaid') unpaid, @JsonValue('partial') partial, @JsonValue('paid') paid }

@freezed
class PaymentModel with _$PaymentModel {
  const factory PaymentModel({
    required String id,
    @JsonKey(name: 'invoice_id') required String invoiceId,
    @JsonKey(name: 'invoice_period') String? invoicePeriod,
    @JsonKey(name: 'student_name') String? studentName,
    required String amount, // desimal dikirim backend sebagai string (lihat catatan 10.1 & types/finance.ts)
    @JsonKey(name: 'paid_at') required String paidAt,
    required String method,
    @JsonKey(name: 'created_at') required String createdAt,
  }) = _PaymentModel;

  factory PaymentModel.fromJson(Map<String, dynamic> json) => _$PaymentModelFromJson(json);
}

@freezed
class InvoiceModel with _$InvoiceModel {
  const factory InvoiceModel({
    required String id,
    @JsonKey(name: 'student_id') required String studentId,
    @JsonKey(name: 'student_name') String? studentName,
    @JsonKey(name: 'student_nim') String? studentNim,
    required String period,
    required String amount,
    @JsonKey(name: 'paid_amount') required String paidAmount,
    required InvoiceStatus status,
    @JsonKey(name: 'due_date') required String dueDate,
    required List<PaymentModel> payments,
    @JsonKey(name: 'created_at') required String createdAt,
  }) = _InvoiceModel;

  factory InvoiceModel.fromJson(Map<String, dynamic> json) => _$InvoiceModelFromJson(json);
}
```

**EnrollKrsPayload** (`features/academic/data/models/krs_payload.dart`) — request payload (bukan respons):

```dart
import 'package:freezed_annotation/freezed_annotation.dart';

part 'krs_payload.freezed.dart';
part 'krs_payload.g.dart';

@freezed
class EnrollKrsPayload with _$EnrollKrsPayload {
  const factory EnrollKrsPayload({
    @JsonKey(name: 'student_id') required String studentId,
    @JsonKey(name: 'class_section_id') required String classSectionId,
  }) = _EnrollKrsPayload;

  factory EnrollKrsPayload.fromJson(Map<String, dynamic> json) => _$EnrollKrsPayloadFromJson(json);
}
```

**ApiSuccessResponse & PaginatedResult** (`shared/entities/api_envelope.dart`) — response envelope generik, dipakai **seluruh** endpoint (padanan `types/api.ts`):

```dart
import 'package:freezed_annotation/freezed_annotation.dart';

part 'api_envelope.freezed.dart';
part 'api_envelope.g.dart';

@freezed
class ApiPaginationMeta with _$ApiPaginationMeta {
  const factory ApiPaginationMeta({
    @JsonKey(name: 'current_page') required int currentPage,
    @JsonKey(name: 'per_page') required int perPage,
    required int total,
    @JsonKey(name: 'last_page') required int lastPage,
  }) = _ApiPaginationMeta;

  factory ApiPaginationMeta.fromJson(Map<String, dynamic> json) => _$ApiPaginationMetaFromJson(json);
}

/// T tidak melalui json_serializable langsung (generic) — dipakai manual di tiap DataSource:
/// `ApiSuccessResponse.fromJson(raw, (json) => StudentModel.fromJson(json as Map<String, dynamic>))`.
class ApiSuccessResponse<T> {
  const ApiSuccessResponse({required this.success, required this.message, required this.data, this.meta});
  final bool success;
  final String? message;
  final T data;
  final ApiPaginationMeta? meta;

  factory ApiSuccessResponse.fromJson(Map<String, dynamic> json, T Function(dynamic) fromJsonT) {
    return ApiSuccessResponse(
      success: json['success'] as bool,
      message: json['message'] as String?,
      data: fromJsonT(json['data']),
      meta: json['meta'] == null ? null : ApiPaginationMeta.fromJson(json['meta'] as Map<String, dynamic>),
    );
  }
}

class PaginatedResult<T> {
  const PaginatedResult({required this.data, required this.meta});
  final List<T> data;
  final ApiPaginationMeta meta;
}
```

Pola pemanggilan seragam di tiap `RemoteDataSource` (padanan `academicService.ts` dkk):

```dart
Future<PaginatedResult<StudentModel>> index(ListQuery query) async {
  final response = await _dio.get('/students', queryParameters: query.toQueryParameters());
  final envelope = ApiSuccessResponse.fromJson(
    response.data as Map<String, dynamic>,
    (json) => (json as List).map((e) => StudentModel.fromJson(e as Map<String, dynamic>)).toList(),
  );
  return PaginatedResult(data: envelope.data, meta: envelope.meta!);
}
```

### 10.3 Tabel Field — Seluruh Model Lain (dikerjakan dengan pola identik 10.2)

Setiap baris = satu file `<nama>_model.dart` di folder `data/models/` fitur terkait, field mengikuti persis definisi `types/*.ts` sumber (BAB 2 menyebut file sumbernya).

| Model | Sumber (`types/*.ts`) | Field (nama Dart camelCase — `@JsonKey` snake_case bila beda) |
|---|---|---|
| `LecturerModel` | `academic.ts` | id, facultyId?, facultyName?, nidn, name, email?, isActive, createdAt |
| `EmployeeModel` | `academic.ts` | id, unitKerja, name, email?, position?, isActive, createdAt |
| `StudyProgramModel` | `academic.ts` | id, facultyId, facultyName?, code, name, degreeLevel, isActive, studentsCount?, classSectionsCount?, createdAt |
| `ClassSectionModel` | `academic.ts` | id, studyProgramId, studyProgramName?, academicTermId, academicTermLabel?, courseId, courseName?, courseCode?, credits?, classCode, capacity, enrolledCount?, isActive, createdAt |
| `CurriculumModel` | `academic.ts` | id, studyProgramId, studyProgramName?, name, academicYear, isActive, coursesCount?, courses?: `List<CourseModel>`, createdAt |
| `CourseModel` | `academic.ts` | id, studyProgramId, studyProgramName?, curriculumId, curriculumName?, code, name, credits, semesterLevel, isActive, createdAt |
| `KrsItemModel` + `enum KrsItemStatus{enrolled,dropped}` | `academic.ts` | id, studentId, studentName?, studentNim?, classSectionId, courseName?, courseCode?, classCode?, academicTermId, academicTermLabel?, status, letterGrade?, createdAt |
| `GradeModel` + `enum LetterGrade{a,ab,b,bc,c,d,e}` (`@JsonValue('A')` dst) | `academic.ts` | id, krsItemId, studentId?, studentName?, studentNim?, courseName?, courseCode?, academicTermLabel?, letterGrade?, score?, submittedAt?, createdAt |
| `AttendanceModel` + `enum AttendanceStatus{present,permitted,sick,absent}` | `academic.ts` | id, krsItemId, studentId?, studentName?, studentNim?, courseName?, courseCode?, meetingNumber, meetingDate, status, notes?, createdAt |
| `UpsertGradePayload` | `academic.ts` | score (num), letterGrade? |
| `RecordAttendanceBatchPayload` + `AttendanceEntryPayload` | `academic.ts` | meetingNumber, meetingDate, entries: `List<AttendanceEntryPayload>` (krsItemId, status, notes?) |
| `TranscriptModel` + `TranscriptTermModel` | `academic.ts` | terms: `List<TranscriptTermModel>` (academicTermId, label, sks, ip), ipk, totalSks |
| `PaymentListParams` | `finance.ts` | dateFrom?, dateTo? + field `ListQuery` biasa (bukan model respons — dipakai sebagai query builder) |
| `ApprovalWorkflowModel` + `ApprovalWorkflowStepModel` + `enum ApprovalApproverType{role,user,position}` + `enum ApprovalRejectAction{stopWorkflow,returnToPreviousStep}` | `approval.ts` | id, name, workflowableType, conditions?: `Map<String,dynamic>`, isActive, description?, steps: `List<ApprovalWorkflowStepModel>` (id, sequence, name, approverType, approverRoleId?, approverUserId?, actionOnReject), createdAt? |
| `ApprovalRequestModel` + `ApprovalHistoryEntryModel` + `enum ApprovalRequestStatus{submitted,inProgress,approved,rejected}` + `enum ApprovalHistoryEvent{submitted,stepAdvanced,approved,rejected,returnedToPreviousStep}` | `approval.ts` | id, approvalWorkflowId, requestableType, requestableId, requestedBy, currentStepId?, status, submittedAt?, completedAt?, notes?, histories: `List<ApprovalHistoryEntryModel>` (id, event, actorId?, description?, metadata?, createdAt), canAct, createdAt? |
| `UniversityModel` + `UniversityActiveSubscriptionModel` + `enum UniversityStatus{draft,trial,active,suspended,expired,terminated}` | `tenancy.ts` | id, code, slug, name, shortName?, legalName?, educationInstitutionType?, accreditation?, email?, phone?, website?, status, timezone, locale, currency, dateFormat, primaryColor?, secondaryColor?, isActive, activatedAt?, suspendedAt?, domains?: `List<String>`, activeSubscription?: `UniversityActiveSubscriptionModel` (plan?, status, currentPeriodEndsAt?), createdAt? |
| `UniversityPayload` | `tenancyService.ts` | code, name, shortName?, legalName?, educationInstitutionType?, accreditation?, email?, phone?, website?, timezone?, locale?, currency?, primaryColor?, secondaryColor? |
| `UniversityComparisonRowModel` | `tenancy.ts` | id, name, students, lecturers, employees, unpaidInvoices, pendingApprovals |
| `PlatformStatisticsModel` | `tenancy.ts` | universitiesTotal, universitiesActive, universitiesByStatus: `Map<String,int>`, usersTotal, membershipsTotal, studentsTotal, lecturersTotal, employeesTotal, studyProgramsTotal, unpaidInvoicesTotal, pendingApprovalsTotal, byUniversity: `List<UniversityComparisonRowModel>` |
| `SupportSessionModel` | `tenancy.ts` | id, superAdminId, superAdminName?, universityId, universityName?, reason, startedAt?, endedAt?, ipAddress?, createdAt? |
| `RoleModel` | `userManagement.ts` | id, name, slug, description?, isSystem, permissions: `List<PermissionModel>`, createdAt? |
| `PermissionModel` + `enum PermissionScope{menu,module,endpoint,data}` + `enum PermissionAction{create,read,update,delete,approve,reject,export,import,publish,finalize}` | `userManagement.ts` | id, name, slug, scope, action, resource, description?, isSystem, createdAt? |
| `UserSummaryModel` | `userManagement.ts` | id, name, email, isActive |
| `RolePayload` / `PermissionPayload` | `userManagementService.ts` | name, description? / name, resource, scope, action, description? |
| `SystemSettingModel` + `enum SettingValueType{string,integer,boolean,json}` | `systemSetting.ts` | id, key, value: `dynamic` (string/num/bool/Map sesuai `type`), type, group?, description?, isPublic, updatedAt? |
| `FeatureFlagModel` | `systemSetting.ts` | id, key, name, description?, isEnabled, updatedAt? |
| `NotificationTemplateModel` + `enum NotificationChannel{database,mail,push,whatsapp,sms}` | `notification.ts` | id, eventKey, name, channel, subject?, bodyTemplate, isActive, createdAt? |
| `NotificationTemplatePayload` | `notificationService.ts` | eventKey, name, channel, subject?, bodyTemplate, isActive? |
| `NotificationChannelConfigModel` | `notification.ts` | id, code, name, isEnabled |
| `UserNotificationPreferenceModel` | `notification.ts` | id, channel, notificationType, isEnabled |
| `AuditLogEntryModel` + `enum AuditAction{created,updated,deleted,restored,exported,imported}` | `auditLog.ts` | id, userId?, userName?, auditableType, auditableId, action, oldValues?: `Map<String,dynamic>`, newValues?: `Map<String,dynamic>`, reason?, ipAddress?, userAgent?, createdAt |
| `FileUploadModel` + `enum FileUploadStatus{pending,scanning,clean,rejected}` | `fileUpload.ts` | id, originalName, mimeType, extension, sizeBytes, status, isPublic, uploadedBy?, createdAt? |
| `AlumniModel` + `enum AlumniEmploymentStatus{bekerja,wirausaha,melanjutkanStudi,mencariKerja,belumBekerja}` | `alumni.ts` | id, studentId, studentName?, studentNim?, studyProgramName?, graduationYear, employmentStatus, companyName?, jobTitle?, waitingPeriodMonths?, isVerified, createdAt |
| `AnnouncementModel` + `enum AnnouncementTargetScope{universitas,fakultas,programStudi}` | `announcement.ts` | id, title, body, targetScope, targetId?, isPinned, publishedAt, creatorName?, createdAt |
| `InternshipModel` + `enum InternshipProgramType{magang,kkn,mbkm}` + `enum InternshipStatus{terdaftar,berlangsung,selesai,dibatalkan}` | `internship.ts` | id, studentId, studentName?, studentNim?, programType, institutionName, position?, supervisorLecturerId?, supervisorName?, startDate, endDate?, status, sksConverted?, createdAt |
| `BookModel` + `BookLoanModel` + `enum BookLoanStatus{dipinjam,dikembalikan,terlambat}` | `library.ts` | id, title, author, publisher?, isbn?, category?, stock, isActive, loansCount?, loans?: `List<BookLoanModel>` (id, bookId, studentId, studentName?, studentNim?, borrowedAt, dueAt, returnedAt?, status, fineAmount?, createdAt), createdAt |
| `ScholarshipModel` + `ScholarshipApplicationModel` + `enum ScholarshipApplicationStatus{submitted,underReview,approved,rejected}` | `scholarship.ts` | id, name, provider, quota, amount, academicYear, registrationStart, registrationEnd, isActive, applicationsCount?, applications?: `List<ScholarshipApplicationModel>` (id, scholarshipId, scholarshipName?, studentId, studentName?, studentNim?, status, submittedAt, reviewedAt?, notes?), createdAt |
| `ThesisModel` + `enum ThesisType{skripsi,tesis,disertasi}` + `enum ThesisStatus{proposal,bimbingan,seminarProposal,penelitian,sidang,selesai}` | `thesis.ts` | id, studentId, studentName?, studentNim?, supervisorLecturerId?, supervisorName?, title, thesisType, status, submittedAt, completedAt?, createdAt |
| `ReportTypeOptionModel` + `enum ReportType{mahasiswa,akademik,keuangan,sdm}` | `report.ts` | type, label |
| `ReportColumnModel` / `ReportResultModel` | `report.ts` | key, label / type, label, columns: `List<ReportColumnModel>`, summary: `Map<String,dynamic>`, rows: `List<Map<String,dynamic>>`, totalRows, truncated |
| `UserSessionModel` + `UserSessionDeviceModel` | `auth.ts` | id, device?: `UserSessionDeviceModel` (id, deviceName?, platform?), ipAddress?, userAgent?, lastActivityAt?, isActive, revokedAt?, createdAt? |
| `UserDeviceModel` + `enum DeviceType{web,mobile,desktop,unknown}` | `auth.ts` | id, deviceName?, deviceType, platform?, isTrusted, lastUsedAt? |
| `DashboardSummaryModel` (seluruh field optional — omit sesuai permission, lihat 2.2/6.2.1) | `dashboard.ts` | totalStudents?, totalLecturers?, totalEmployees?, totalStudyPrograms?, activeStudents?, activeClasses?, unpaidInvoices?, pendingApprovals? |
| `DashboardChartsModel` (seluruh field optional) | `dashboard.ts` | studentGrowth?: `List<StudentGrowthPointModel>` (year, total), studentStatus?: `List<StatusCountModel>` (status, value, total), studentsByProgram?/activeClassesByProgram?: `List<ProgramCountModel>` (id, program, total), staffByUnit?: `List<StaffByUnitPointModel>` (unit, facultyId?, lecturers, employees), paymentTrend?: `List<PaymentTrendPointModel>` (month, total), invoiceStatus?: `List<InvoiceStatusSliceModel>` (status, value, total, amount), approvalStatus?: `List<ApprovalStatusCountModel>` (status, values, total) |
| `DashboardFilterOptionsModel` | `dashboard.ts` | academicTerms: `List<DashboardAcademicTermOptionModel>` (id, label, isCurrent), faculties: `List<DashboardFacultyOptionModel>` (id, name), studyPrograms: `List<DashboardStudyProgramOptionModel>` (id, name, facultyId) |
| `DashboardResponseModel` | `dashboard.ts` | summary: `DashboardSummaryModel`, charts: `DashboardChartsModel`, filters: `DashboardFilterOptionsModel` |
| `ActivityItemModel` + `enum ActivityType{...6 nilai}` | `dashboard.ts` | id, type, title, description, actor, timestamp |
| `AgendaItemModel` + `enum AgendaCategory{krs,exam,payment,grading,graduation}` | `dashboard.ts` | id, title, category, startDate, endDate |
| `NotificationItemModel` + `enum NotificationType{info,success,warning,danger}` | `dashboard.ts` (mock, lihat 7.2) | id, type, title, description, isRead, timestamp |
| `NavItem`/`NavChildItem` (bukan model API — konstanta navigasi, BAB 7.3) | `navigation.ts` | label, path, icon, children?, permission? |

---

## BAB 11 — Keamanan

### 11.1 Penyimpanan Token

Token bearer (`AuthSessionModel.token`, BAB 10.2) disimpan **hanya** di `flutter_secure_storage` dengan `AndroidOptions(encryptedSharedPreferences: true)` (BAB 8.5) — **tidak pernah** ditulis ke `shared_preferences`, Hive cache, log, atau dikirim ke Crashlytics/Analytics sebagai parameter event (BAB 8.14). Ini adalah peningkatan keamanan yang disengaja dibanding React, yang menyimpan seluruh `AuthSession` (termasuk token) di `localStorage` biasa lewat Zustand `persist` (`civitasone-auth`) — wajar untuk web SPA tapi bukan praktik terbaik di mobile karena `flutter_secure_storage` tersedia tanpa trade-off berarti.

Data non-sensitif yang di React ikut disimpan `persist` (`tenantStore`→`civitasone-tenant`, `themeStore`→`civitasone-theme`) tetap boleh di `shared_preferences` biasa (BAB 8.5) — pemisahan ini konsisten dengan prinsip *least privilege*: hanya kredensial akses yang butuh perlindungan setingkat Keystore.

### 11.2 Refresh Token & Auto-Logout

⚠️ **PERLU KONFIRMASI**: menelusuri `authService.ts` React (BAB 2.2), backend **hanya** mengekspos `/login`, `/logout`, `/logout-all`, `/forgot-password`, `/reset-password`, `/sessions`, `/devices` — **tidak ada** endpoint refresh token (`/refresh` atau semacamnya). Mekanisme kedaluwarsa token React murni reaktif: interceptor axios menangkap **401** dari request manapun lalu langsung `clearSession()` (paksa ke halaman login, BAB 2.4) — tidak ada percobaan refresh token diam-diam.

Flutter **mereplikasi persis** perilaku ini (bukan berasumsi ada refresh token yang belum tentu didukung backend):
```dart
onError: (DioException error, handler) async {
  if (error.response?.statusCode == 401) {
    await ref.read(authControllerProvider.notifier).clearSession(); // hapus token dari secure storage
    // GoRouter redirect (7.1) otomatis membawa ke LoginScreen begitu authControllerProvider berubah jadi null
  }
  handler.next(error);
},
```
**Auto-logout tambahan** (native, tidak ada padanan React tapi wajar untuk mobile): opsi "Kunci otomatis setelah tidak aktif" (mis. 15 menit di background) yang memicu ulang layar biometrik (BAB 8.4) tanpa menghapus token — beda dari 401 (yang menghapus token sungguhan). Kedua mekanisme ini dijelaskan terpisah agar tidak tertukar: **401 = sesi backend benar tidak valid lagi**; **auto-lock = lapisan privasi lokal saat perangkat idle**, sesi backend tetap hidup.

Jika kelak backend menambah endpoint refresh token, `AuthRepositoryImpl` cukup disisipi retry-once di `onError` sebelum memanggil `clearSession()` — desain interceptor saat ini (BAB 3.4) sudah menyediakan titik ekstensi itu tanpa perombakan arsitektur.

### 11.3 Obfuscation

Build rilis **wajib** memakai flag obfuscation bawaan Flutter untuk mempersulit reverse-engineering:
```bash
flutter build appbundle --obfuscate --split-debug-info=build/debug-info --flavor prod -t lib/main_prod.dart
```
`build/debug-info/*.symbols` **disimpan aman di luar repository publik** (mis. artifact CI privat) — dibutuhkan untuk men-deobfuscate stack trace Crashlytics (BAB 8.14) saat debugging crash produksi; tanpa symbol file ini, stack trace hasil obfuscation tidak terbaca sama sekali.

### 11.4 Certificate Pinning

⚠️ **PERLU KONFIRMASI**: React (web browser) tidak melakukan cert pinning (browser mengandalkan CA trust store standar OS/browser) — tidak ada rujukan konkret dari kode sumber untuk pin sertifikat spesifik. Untuk Flutter, direkomendasikan mengaktifkan pinning di build `prod`/`staging` (bukan `dev`, agar tidak menghalangi debugging via proxy lokal seperti Charles/Proxyman):
```dart
if (Env.flavor != Flavor.dev) {
  dio.httpClientAdapter = IOHttpClientAdapter(
    createHttpClient: () {
      final client = HttpClient(context: SecurityContext(withTrustedRoots: false));
      client.badCertificateCallback = (cert, host, port) => _matchesPinnedSha256(cert, Env.apiPinnedCertSha256);
      return client;
    },
  );
}
```
Nilai `Env.apiPinnedCertSha256` (fingerprint SHA-256 sertifikat server API produksi) **harus diminta ke tim DevOps/backend** sebelum fitur ini diaktifkan — memasang pin yang salah akan membuat aplikasi produksi gagal total terhubung ke API begitu dirilis. Wajib punya **rencana rotasi pin** (menyertakan pin sertifikat berikutnya sebelum sertifikat lama expired) agar tidak mem-brick aplikasi yang sudah terpasang saat sertifikat server diperbarui.

### 11.5 Proteksi Screenshot pada Halaman Sensitif

Tidak ada padanan langsung di web (browser tidak punya API mencegah screenshot) — ini murni penguatan native untuk data finansial/pribadi. Diterapkan `FLAG_SECURE` (mencegah screenshot **dan** preview di app switcher/recent apps) pada Screen berikut, via `flutter_windowmanager` atau kanal method native minimal:
- `InvoiceDetailScreen` (6.6.2), `PortalInvoicesScreen` (6.15.14) — nominal tagihan/pembayaran.
- `SecuritySessionsScreen` (6.1.4) — daftar perangkat & sesi aktif (informasi yang bisa disalahgunakan bila bocor).
- Layar kunci biometrik (8.4) itu sendiri.

`FLAG_SECURE` diset **per-Screen saat masuk**, dilepas **saat keluar** (bukan global sepanjang app), agar tidak mengganggu screenshot wajar di layar lain (mis. mahasiswa screenshot jadwal kuliah untuk dibagikan ke teman — kebutuhan nyata yang tidak boleh diblokir tanpa alasan).

### 11.6 Ringkasan Kaitan dengan Sistem Permission (RBAC)

Seluruh mekanisme di atas melengkapi, **bukan menggantikan**, model otorisasi granular yang sudah dijelaskan BAB 2.4/3.4/7.4 — permission (`usePermission`/`PermissionGate`) mengontrol *apa yang terlihat & bisa dilakukan* pengguna yang **sudah terautentikasi sah**; BAB 11 mengamankan *bagaimana kredensial itu sendiri disimpan & diverifikasi ulang*. Keduanya independen: pengguna dengan token valid tapi permission kosong tetap bisa login (hanya lihat Dashboard kosong, BAB 6.2.1), sementara token yang dicabut (401) langsung memutus akses apa pun tanpa memandang permission apa yang pernah dimiliki.

---

## BAB 12 — Rencana Pengembangan

Pembagian fase mengikuti kompleksitas & ketergantungan modul (infra dasar dulu, lalu modul bervolume tinggi/dipakai lintas-fitur, baru modul administratif/khusus-role) — bukan urutan acak.

### Fase 0 — Bootstrap Proyek & Infra Inti (estimasi: 1–1.5 minggu)
- [ ] `flutter create`, konfigurasi 3 flavor (dev/staging/prod, BAB 13.2), `pubspec.yaml` (BAB 9.2).
- [ ] `core/network` (Dio client, interceptor auth/tenant/error — BAB 3.4), `core/storage` (secure storage + shared_preferences + Hive init).
- [ ] `core/theme` penuh (BAB 5) + verifikasi kontras light/dark.
- [ ] `core/router` kerangka (BAB 7.1) — route publik (Login/ForgotPassword/ResetPassword) + `AppShell` kosong.
- [ ] Modul **Auth** penuh (6.1: Login, ForgotPassword, ResetPassword, SecuritySessions) + `PermissionGate`/`usePermission` equivalent (BAB 3.4/7.4).
- [ ] CI dasar (lint + `flutter test` + build debug APK per PR).

### Fase 1 — Dashboard & Navigasi Inti (estimasi: 1 minggu)
- [ ] `DashboardScreen` tenant biasa penuh (6.2.1) — 8 chart `fl_chart`, filter bar, deep-link ke List (bergantung modul Fase 2 sudah punya Screen kerangka minimal untuk dituju).
- [ ] `AppShell` penuh: Drawer dinamis per role (7.2/7.3), AppBar, `TenantSwitcher`/`TenantModeBanner` (bergantung modul Platform Fase 4, boleh dikerjakan sebagai stub dulu).
- [ ] `AccessDeniedScreen` (6.3.1).

### Fase 2 — Akademik Inti (estimasi: 2.5–3 minggu, modul terbesar)
- [ ] Mahasiswa, Dosen, Pegawai (List+Detail — 6.5.1–6.5.6).
- [ ] Program Studi, Kelas & Jadwal, Kurikulum, Mata Kuliah (List+Detail — 6.5.7–6.5.14).
- [ ] KRS, Penilaian, Absensi (6.5.15–6.5.17) — termasuk alur multi-langkah (enroll KRS, input nilai 2-step, rekam absensi batch).
- [ ] `StudentDetailScreen` disempurnakan setelah modul Keuangan/Skripsi/Magang/Beasiswa (Fase 3–4) tersedia, agar seluruh Card sub-data (6.5.2) bisa disambung — kerjakan versi minimal dulu (profil saja), lengkapi progresif.

### Fase 3 — Keuangan & Persetujuan (estimasi: 1.5–2 minggu)
- [ ] Tagihan, Pembayaran, Beasiswa admin (6.6).
- [ ] Persetujuan — List, Detail (approve/reject/delegate), Alur (6.7) — modul lintas-fitur penting (dipakai kartu Dashboard & notifikasi push Fase 6).

### Fase 4 — Modul Pendukung & Platform Super Admin (estimasi: 2–2.5 minggu)
- [ ] Skripsi, Magang/MBKM, Perpustakaan, Alumni, Pengumuman, Laporan (6.8–6.13).
- [ ] Platform: Dashboard Platform, Manajemen Universitas, Keamanan Platform (6.2.2, 6.4) + `TenantSwitcher` penuh (BAB 2.4).
- [ ] Pengaturan RBAC: Role, Permission, Role Pengguna, Pengaturan Sistem, Feature Flag, Notifikasi (template/channel/preferensi), Audit Log (6.14).

### Fase 5 — Portal Mahasiswa (estimasi: 1.5–2 minggu untuk UI penuh; sisanya bergantung ketersediaan backend)
- [ ] Seluruh 19 Screen Portal (6.15) sebagai **UI lengkap dengan data contoh** — paritas penuh dengan status React saat ini terlebih dulu.
- [ ] Sambungkan submodul yang **sudah** punya endpoint sungguhan lewat reuse modul admin (Beasiswa 6.15.13, Tagihan 6.15.14, Pengumuman 6.15.17, Skripsi status 6.15.16 sebagian) dengan query `student_id=me`/scoping otomatis backend.
- [ ] ⚠️ Item yang endpoint-nya belum ada (mayoritas 6.15) menunggu backend — dijadwalkan ulang begitu tersedia, **tidak memblokir rilis Fase 1** aplikasi (Portal boleh dirilis sebagai "Segera Hadir" pada akun mahasiswa bila backend belum siap saat target rilis, dikomunikasikan eksplisit ke pengguna, bukan tampil seolah berfungsi).

### Fase 6 — Pemolesan Native Android & Pengerasan Keamanan (estimasi: 2–2.5 minggu)
- [ ] BAB 8 lengkap: Dynamic Color, Edge-to-edge/predictive back, Push notification+channel, Biometrik, Mode offline dasar, Pull-to-refresh/infinite-scroll/shimmer di seluruh List, Deep link+App Link, Upload/kamera, Shortcut, Aksesibilitas, Multi-bahasa (minimal struktur ARB siap, isi bertahap), Splash native, Crash reporting+analytics.
- [ ] BAB 11 lengkap: obfuscation build, cert pinning (menunggu fingerprint dari DevOps), `FLAG_SECURE` halaman sensitif, audit ulang penyimpanan token.
- [ ] Uji aksesibilitas manual (TalkBack) & audit performa (startup time, jank scrolling List besar).

### Fase 7 — Stabilisasi, Testing Menyeluruh & Rilis (estimasi: 1.5–2 minggu)
- [ ] Cakupan unit/widget/integration test menyeluruh (BAB 13.1) untuk seluruh modul di atas.
- [ ] Uji regresi manual golden path per role (Super Admin, Admin Tenant, Mahasiswa).
- [ ] Checklist rilis Play Store (BAB 13.4), build `appbundle` produksi, submit internal testing track dulu sebelum production.

**Total estimasi kasar: ~14–17 minggu** untuk satu tim kecil (2–3 Flutter dev + 1 QA paruh waktu) mengerjakan seluruh cakupan dokumen ini secara berurutan sesuai fase; bisa dipercepat dengan paralelisasi modul independen dalam satu Fase (mis. Fase 2 & 3 sebagian bisa tumpang tindih bila tim cukup besar, karena tidak saling bergantung kuat kecuali lewat `StudentDetailScreen`).

---

## BAB 13 — Testing & Rilis

### 13.1 Strategi Testing

**Unit test** (`test/features/<fitur>/data|domain|presentation/`):
- `RepositoryImpl` — mock `Dio` (via `mocktail` + `DioAdapter`/`http_mock_adapter`) memverifikasi mapping request→`ApiFailure`/model sesuai kontrak BAB 2.2/10.
- `Notifier`/`Controller` — override provider dependency, verifikasi transisi state (`loading→data→error`), termasuk kasus permission-gated fetch (mis. `StudentDetailScreen` tidak memanggil endpoint transkrip bila `canSeeKrs=false`, persis BAB 6.5.2).
- `list_query_mapper_test.dart` — memverifikasi `toQueryParameters()` menghasilkan `filter[kolom]=nilai` identik `utils/listParams.ts` React (regresi kontrak backend).
- Validator form (BAB 6, padanan skema Zod) — kasus batas (email invalid, password <8 karakter reset, komentar kosong saat reject approval 6.7.2, dst).

**Widget test** (`test/widget/`): Screen kunci dirender dalam `ProviderScope` dengan override data statis — memverifikasi 4 state (`loading` tampil skeleton, `data` tampil konten benar, `error` tampil `AppAlert`+pesan, `empty` tampil `AppEmptyState`) untuk representatif tiap pola (BAB 6.0): satu List (`StudentsScreen`), satu Detail (`StudentDetailScreen`), satu form modal (`RolesScreen` create/update), satu alur multi-langkah (`GradesScreen` 2-step). Golden test (`matchesGoldenFile`) untuk komponen `core/widgets/*` (BAB 5.6) di kedua tema (light/dark) guna mencegah regresi visual token warna (BAB 5.1).

**Integration test** (`integration_test/`, dijalankan `flutter test integration_test` di emulator/perangkat nyata):
- `login_flow_test.dart` — login sukses→Dashboard sesuai role (3 varian 6.2), login gagal→pesan error, logout→kembali ke Login.
- `approval_flow_test.dart` — buka `ApprovalRequestsScreen`→Detail→Setujui→verifikasi status berubah in-place tanpa reload manual.
- `student_crud_flow_test.dart` — alur KRS: tambah KRS (6.5.15)→muncul di list→batalkan→status berubah `Dibatalkan`.
- Uji lintas-tema & lintas-permission (jalankan skenario sama dengan akun permission minim → pastikan tombol aksi tersembunyi, bukan sekadar disabled diam-diam yang membingungkan).

Target cakupan (coverage) minimal disarankan: **≥70% `data`+`domain`** (logic murni, mudah dan murah diuji), **≥50% `presentation`** (widget test representatif per pola, bukan tiap 1 dari 71 Screen wajib py widget test individual — prioritaskan pola & modul berisiko tinggi: Auth, Approval, KRS/Nilai/Absensi, RBAC Settings).

### 13.2 Konfigurasi Flavor (dev/staging/prod)

Tiga entry point (BAB 4) + `--dart-define` per lingkungan:

```dart
// core/config/env.dart
enum Flavor { dev, staging, prod }

abstract final class Env {
  static late final Flavor flavor;
  static late final String apiBaseUrl;
  static late final String apiPinnedCertSha256;

  static void init(Flavor f) {
    flavor = f;
    apiBaseUrl = const String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.0.2.2:8000/api/v1');
    apiPinnedCertSha256 = const String.fromEnvironment('API_PINNED_CERT_SHA256', defaultValue: '');
  }
}
```

```dart
// main_dev.dart
void main() { Env.init(Flavor.dev); bootstrap(); }
// main_staging.dart
void main() { Env.init(Flavor.staging); bootstrap(); }
// main_prod.dart
void main() { Env.init(Flavor.prod); bootstrap(); }
```

`android/app/build.gradle` — `productFlavors { dev { applicationIdSuffix '.dev'; resValue 'string','app_name','CivitasOne Dev' } staging { applicationIdSuffix '.staging'; resValue 'string','app_name','CivitasOne Staging' } prod { resValue 'string','app_name','CivitasOne' } }` — tiga `applicationId` berbeda memungkinkan dev/staging/prod terpasang **bersamaan** di satu perangkat untuk uji banding, padanan konseptual dari `.env`/`.env.example` React (BAB 2.2) yang cuma satu file aktif per build web.

Jalankan: `flutter run --flavor dev -t lib/main_dev.dart --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1` (`10.0.2.2` = alias localhost host dari emulator Android, padanan `http://localhost:8000/api/v1` di `.env` React BAB 2.2 yang dijalankan di browser desktop).

### 13.3 Langkah Build APK & AAB

```bash
# Debug (development lokal)
flutter run --flavor dev -t lib/main_dev.dart

# Release APK (uji internal, sideload)
flutter build apk --flavor prod -t lib/main_prod.dart --release \
  --obfuscate --split-debug-info=build/debug-info \
  --dart-define=API_BASE_URL=https://api.civitasone.example/api/v1 \
  --dart-define=API_PINNED_CERT_SHA256=<fingerprint-dari-devops>

# Release App Bundle (submit Play Store — wajib format .aab)
flutter build appbundle --flavor prod -t lib/main_prod.dart --release \
  --obfuscate --split-debug-info=build/debug-info \
  --dart-define=API_BASE_URL=https://api.civitasone.example/api/v1 \
  --dart-define=API_PINNED_CERT_SHA256=<fingerprint-dari-devops>
```
Signing: `android/key.properties` (di luar version control, didaftarkan `.gitignore`) berisi path keystore rilis + password, dirujuk `android/app/build.gradle` `signingConfigs.release` — keystore **wajib dicadangkan aman di luar repo** (kehilangan keystore rilis = tidak bisa update aplikasi yang sudah live di Play Store selamanya).

### 13.4 Checklist Rilis Play Store

- [ ] `targetSdk` sesuai kebijakan Play Store terbaru saat submit (BAB 1.4 — verifikasi ulang, kebijakan minimum targetSdk Play Store naik tiap tahun).
- [ ] App Bundle (`.aab`) ditandatangani dengan keystore rilis (bukan debug keystore) — App Signing by Google Play diaktifkan.
- [ ] `flutter build appbundle --obfuscate --split-debug-info` (BAB 11.3) + symbol file diarsipkan aman.
- [ ] Ikon adaptif (`flutter_launcher_icons`, BAB 9.1) di seluruh densitas, splash screen Android 12 API (BAB 8.13) dengan aset PNG (bukan GIF — lihat catatan ⚠️ 8.13).
- [ ] Deklarasi permission di `AndroidManifest.xml` sesuai **hanya** yang benar dipakai (kamera, notifikasi, storage, biometrik — BAB 8) — Play Console **Data Safety form** diisi jujur sesuai data yang benar dikumpulkan (nama, NIM, email, lokasi *tidak*, dst).
- [ ] Kebijakan privasi (privacy policy URL) tersedia & sesuai konteks institusi pendidikan (data mahasiswa — pertimbangan kepatuhan perlindungan data pribadi yang berlaku).
- [ ] `applicationId` produksi final (bukan `.dev`/`.staging`) & version code/name dinaikkan sesuai skema semver (BAB 9.2 `version: 0.1.0+1` → naikkan build number tiap submit).
- [ ] App Link `assetlinks.json` (BAB 8.8) sudah live di domain sebelum submit, agar verifikasi domain terlacak sejak awal review.
- [ ] Uji instalasi bersih di perangkat minSdk 24 fisik/emulator (bukan cuma perangkat modern) — pastikan fallback graceful fitur yang tidak tersedia di API rendah (shortcut 8.10, dynamic color 8.1, edge-to-edge lama 8.2).
- [ ] Uji upgrade in-place dari versi sebelumnya (bila bukan rilis pertama) — migrasi Hive box/`shared_preferences` schema bila ada perubahan struktur data lokal antar versi.
- [ ] Rilis bertahap (staged rollout, mis. 10%→50%→100%) diaktifkan di Play Console untuk rilis produksi pertama & tiap rilis besar berikutnya, guna mendeteksi crash rate tinggi (dipantau lewat Crashlytics, BAB 8.14) sebelum menjangkau seluruh pengguna.

---

*Dokumen ini disusun murni berdasarkan penelusuran kode sumber `frontend/` (React) yang ada di repositori pada saat penyusunan. Setiap keputusan desain di luar cakupan langsung kode sumber ditandai eksplisit (⚠️ PERLU KONFIRMASI) dan harus diverifikasi bersama tim produk/backend sebelum diimplementasikan sebagai keputusan final.*
