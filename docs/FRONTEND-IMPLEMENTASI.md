# Rencana Implementasi Frontend

> **Status:** Dokumen kerja (living checklist), bukan blueprint bisnis.
> **Dibuat:** 2026-07-18 · **Diperbarui:** 2026-08-09 (setelah KRS/Nilai/Absensi jadi modul pertama yang punya operasi tulis penuh)
> Dokumen ini fokus pada langkah teknis untuk menyalakan fitur-fitur di `frontend/` satu per satu — apa yang sudah jalan, apa yang masih *mock*, dan urutan pengerjaan yang masuk akal berdasarkan API backend yang sudah tersedia. Untuk cakupan bisnis lengkap (role, modul, alur kerja), lihat [RANCANGAN-APLIKASI.md](./RANCANGAN-APLIKASI.md). Untuk daftar skenario uji lengkap per modul (termasuk yang read-only), lihat [BLACK-BOX-TESTING.md](./BLACK-BOX-TESTING.md) — dokumen itu jadi sumber kebenaran status per-modul, dokumen ini fokus ke *urutan kerja frontend selanjutnya* supaya keduanya tidak duplikat dan sama-sama basi seperti versi sebelumnya.

## 1. Kondisi saat ini

Versi sebelumnya dokumen ini (18 Juli) menyebut hampir semua modul akademik/bisnis "belum ada backend" dan `authService.ts` masih mock. Itu sudah tidak akurat sejak lama — berikut kondisi sebenarnya per commit terakhir.

**Fase 1 (Auth) dan Fase 2 (Pengaturan) di dokumen versi lama — keduanya SELESAI**, lihat §3 dan §4 di bawah untuk detail; checklist-nya dibiarkan tercentang sebagai riwayat, bukan dihapus.

**Backend** (`app/Modules/`, Laravel + Sanctum, permission per-route via middleware `permission:xxx`):

| Kelompok modul | Status | Keterangan |
|---|---|---|
| Auth, UserManagement (RBAC), Tenancy, SystemSetting, Notification, FileManagement, ApprovalWorkflow, AuditLog, Dashboard | ✅ CRUD penuh | Fondasi platform — sudah matang, teruji otomatis |
| Academic — KRS, Nilai, Absensi, Transkrip | ✅ CRUD penuh | Baru selesai 2026-08-09 — lihat §5.1 |
| Academic — Mahasiswa, Dosen, Pegawai, Prodi, Kelas, Kurikulum, Mata Kuliah | 📖 Read-only | Data induk sudah bisa dilihat/difilter, belum bisa diinput lewat sistem |
| Finance, Scholarship, Library, Internship, Alumni, Thesis, Announcement, Report | 📖 Read-only | Model, migrasi, policy, dan halaman list/detail semuanya ada — tapi nol endpoint create/update/delete |

Detail endpoint per modul (field filter, validasi, status code) ada di [BLACK-BOX-TESTING.md](./BLACK-BOX-TESTING.md) §1-23 — tidak diulang di sini supaya tidak ada dua tempat yang harus disinkronkan manual.

**Frontend** (`frontend/`, React 19 + Vite + TS + Tailwind + Zustand + React Router + Recharts):

- Shell aplikasi, tema, komponen UI dasar, routing dengan guard — semua sudah matang dan stabil (tidak berubah sejak versi dokumen sebelumnya).
- `PlaceholderPage` **sudah hilang total dari `App.tsx`** — setiap modul di atas, termasuk yang read-only, sudah punya halaman list + detail nyata yang memanggil service API asli (`services/<domain>Service.ts`).
- Halaman untuk modul read-only (Finance, Scholarship, Library, dst.) sengaja **tidak** punya tombol create/update/delete — itu bukan halaman yang belum selesai, itu cermin jujur dari backend yang memang belum ada endpoint tulisnya. Jangan tambah tombol di sana sebelum §5.1 checklist modul itu selesai.
- **Portal Mahasiswa** (`/portal/*`, 18 halaman) adalah pengecualian: dibangun 2026-07-25 di luar urutan dokumen ini, dan **seluruhnya masih data statis** — nol pemanggilan API. Lihat §5.2.

## 2. Prinsip pengerjaan

1. **Backend duluan, baru UI tulis.** Halaman list/detail read-only boleh dibangun begitu API `index`/`show` tersedia (ini sudah kondisi semua modul, lihat §1). Yang harus menunggu backend adalah **tombol create/update/delete** — jangan pasang tombol itu ke halaman sebelum endpoint tulisnya benar-benar ada, walau secara UI gampang ditambahkan. Urutkan pengerjaan sesuai prioritas di §5.1.
2. **Satu pola integrasi API, dipakai berulang.** `authService.ts` sudah menulis pola yang benar: fungsi async, error dilempar sebagai `NormalizedApiError`, komentar berisi endpoint asli. Ikuti pola yang sama untuk service baru — page dan store tidak perlu tahu bahwa datanya masih/baru real.
3. **Gating berbasis permission.** Backend sudah pakai middleware `permission:modul.aksi` (contoh: `roles.read`, `feature_flags.update`). Frontend perlu menyimpan daftar permission user (dari `/me`) dan menyembunyikan/menonaktifkan aksi (tombol create/update/delete) yang tidak diizinkan — jangan hanya mengandalkan 403 dari server.
4. **Jangan bongkar struktur yang sudah ada.** `ROUTES`, `NAV_ITEMS`, `ApiClient`, `authStore` sudah punya bentuk yang benar; modul baru cukup mengikuti pola yang sama (`constants/routes.ts` → `constants/nav.ts` → `pages/<domain>/` → `services/<domain>Service.ts`).

## 3. Fase 1 — Sambungkan Auth ke backend nyata — ✅ SELESAI

Prasyarat semua fase berikutnya: session harus asli, bukan token palsu. **Semua item di bawah sudah beres**, dicek lewat `frontend/src/pages/auth/` (LoginPage, ForgotPasswordPage, ResetPasswordPage ada) dan `authService.ts` (nol referensi `DEMO_ACCOUNT`/`MOCK_USER`).

- [x] `authService.login` memanggil `POST /login` lewat `apiClient`.
- [x] `authService.getCurrentUser` memanggil `GET /me`.
- [x] `authService.logout` memanggil `POST /logout`.
- [x] `AuthUser`/`AuthSession` type disesuaikan dengan response asli `AuthController` (termasuk daftar permission/role).
- [x] Daftar permission user tersimpan di `authStore`, dipakai lewat hook `usePermission('resource.aksi')` (lihat `src/hooks/usePermission.ts`) untuk gating UI di seluruh halaman.
- [x] Halaman/flow **lupa password** dan **reset password** ada.
- [x] Halaman **Sessions & Devices** (Pengaturan → Keamanan, `SecuritySessionsPage.tsx`) ada, memakai `GET sessions`, `DELETE sessions/{id}`, `GET devices`, `POST logout-all`.
- [x] `DEMO_ACCOUNT`/`MOCK_USER` sudah dihapus total.

## 4. Fase 2 — Modul Pengaturan & platform — ✅ SELESAI

Semua sub-halaman di bawah rute `/pengaturan` sudah dipecah jadi halaman sendiri-sendiri (bukan lagi satu placeholder), mengikuti pola `akademik.*` dengan children di `NAV_ITEMS`.

- [x] **Roles & Permissions** — `RolesPage.tsx`, `PermissionsPage.tsx`, `UserRolesPage.tsx`. Sync permission per role dan assign role ke user berfungsi penuh.
- [x] **System Settings** — `SystemSettingsPage.tsx`.
- [x] **Feature Flags** — `FeatureFlagsPage.tsx`.
- [x] **Notifikasi** — `NotificationsPage.tsx` (template, channel, preferensi).
- [x] **Audit Log** — `AuditLogPage.tsx`, read-only dengan filter, memakai `DataTable`.
- [x] **File upload generik** — dipakai sebagai komponen reusable lintas modul di atas `POST/GET/DELETE file-uploads` dan `download`.
- [x] **Approval Center** — `pages/approvals/`, memakai `approval-requests`, `approval-workflows`, aksi approve/reject/delegate. `PendingApprovalsTable` di dashboard tersambung ke API yang sama.

Pola yang dipakai di sini (route → nav entry → page → service, gating tombol lewat `usePermission`) adalah **template baku** untuk semua fase berikutnya — lihat §5 untuk modul mana yang masih butuh ini dikerjakan.

## 5. Fase 3 — Modul akademik/bisnis

Prediksi "menunggu backend" di versi dokumen sebelumnya sudah dilewati — backend read-only untuk *seluruh* modul di bawah sudah ada, dan frontend-nya sudah tersambung (list + filter + detail nyata, bukan placeholder). Yang tersisa adalah menyalakan operasi tulis (create/update/delete) satu modul per satu, plus dua area terpisah yang butuh perhatian khusus (§5.1 dan §5.2).

### 5.1 Status per modul & langkah tulis berikutnya

| Prioritas | Modul | Status | Langkah selanjutnya |
|---|---|---|---|
| — | KRS, Nilai, Absensi, Transkrip | ✅ Selesai (2026-08-09) | Tidak ada — dipakai sebagai contoh pola untuk baris di bawah. Endpoint: `POST krs-items`, `PATCH krs-items/{id}/drop`, `PUT krs-items/{id}/grade`, `POST class-sections/{id}/attendances`, `GET students/{id}/transcript`. |
| P0 | Keuangan — Tagihan & Pembayaran | 📖 Read-only | Backend: endpoint buat tagihan (`POST invoices`) dan catat pembayaran (`POST payments`, verifikasi manual dulu — payment gateway belum ada). Ini gap bisnis paling kritikal: kampus belum bisa pakai sistem ini untuk operasional keuangan nyata sama sekali. |
| P0 | Identity link `Student`/`Lecturer` → `User` | Tidak ada | Tambah kolom `user_id` nullable di `students`/`lecturers`, lalu endpoint self-service mahasiswa (`krs.create` khusus milik sendiri) dan dosen (grading dibatasi ke kelas yang benar-benar diampu — lihat catatan NIL-09/ABS-08 di [BLACK-BOX-TESTING.md](./BLACK-BOX-TESTING.md) §12-13). Tanpa ini, Portal Mahasiswa (§5.2) tidak mungkin disambungkan dengan aman. |
| P1 | Skripsi | 📖 Read-only | Endpoint tulis untuk alur pengajuan judul → bimbingan → sidang → nilai (lihat RANCANGAN-APLIKASI.md §4.20). |
| P1 | Perpustakaan | 📖 Read-only | Endpoint pinjam/kembali buku + denda. |
| P1 | Beasiswa | 📖 Read-only | Endpoint pengajuan mahasiswa + verifikasi/persetujuan bagian keuangan. |
| P1 | Magang/PKL/KKN/MBKM | 📖 Read-only | Endpoint pendaftaran, logbook, penilaian. |
| P2 | Alumni | 📖 Read-only | Endpoint konversi mahasiswa lulus → alumni, tracer study. |
| P2 | Pengumuman | 📖 Read-only | Endpoint buat/publish pengumuman (saat ini cuma bisa diisi lewat seeder). |
| P2 | Laporan | 📖 Read-only (memang didesain begitu) | Bukan gap — laporan itu agregat, tidak perlu operasi tulis. Kalau mau ditambah: lebih banyak jenis laporan di `ReportService::REPORT_TYPES`. |

Pola kerja untuk tiap baris P0/P1 di atas (sama seperti yang dipakai KRS/Nilai/Absensi — lihat `app/Modules/Academic/Services/AcademicRecordService.php` sebagai referensi konkret):

- [ ] Backend: Service dengan aturan bisnis (bukan langsung di Controller), Request class untuk validasi, Policy method untuk permission, route baru, test Pest.
- [ ] Seeder: tambah permission `<resource>.create`/`<resource>.update` ke `RolePermissionSeeder`, lalu berikan ke role yang relevan di `OrganizationalRoleSeeder`.
- [ ] Frontend: tambah method di `services/<domain>Service.ts` (pola `enroll`/`upsert`/`recordBatch` di `academicService.ts`), form/modal di halaman terkait pakai `Modal` + `react-hook-form` + `zod` (pola `EnrollKrsModal`/`GradeFormModal` di `KrsPage.tsx`/`GradesPage.tsx`), gating tombol lewat `usePermission`.
- [ ] Update baris modul itu di tabel §5.1 ini dan di [BLACK-BOX-TESTING.md](./BLACK-BOX-TESTING.md) jadi ✅ setelah selesai — supaya kedua dokumen ini tidak basi lagi seperti kejadian sebelumnya.

### 5.2 Portal Mahasiswa — butuh penanganan terpisah, bukan sekadar "lanjutkan §5.1"

18 halaman di `/portal/*` (dibangun 2026-07-25) memakai data statis di komponen, nol pemanggilan API — lihat [BLACK-BOX-TESTING.md](./BLACK-BOX-TESTING.md) §24. Sebagian modul backend yang dibutuhkannya (Cuti, Bimbingan Akademik/Skripsi, Wisuda, Evaluasi Dosen, Kuis, Tugas) **tidak ada satu pun** di `app/Modules/` — ini bukan kasus "tinggal sambungkan ke API", tapi "bangun modul backend dari nol" untuk sebagian besar halamannya.

- [ ] KRS/Jadwal/KHS/Transkrip/Nilai/Absensi portal **bisa** disambungkan sekarang ke endpoint yang sudah ada di §5.1 baris "Selesai" — **tapi** butuh identity link `Student` → `User` dulu (baris P0 di atas) supaya "KRS saya" benar-benar scoped ke mahasiswa yang login, bukan mengekspos endpoint admin ke role mahasiswa.
- [ ] Cuti, Surat, Bimbingan Akademik/Skripsi, Evaluasi Dosen, Wisuda, Kuis, Tugas: modul backend baru, prioritasnya menyusul P0/P1 di §5.1 — jangan dikerjakan duluan hanya karena UI-nya sudah kadung ada.

## 6. Hal lintas-modul yang perlu disiapkan sekali, dipakai semua fase

- [x] **Layer permission** — `usePermission('resource.aksi')` (`src/hooks/usePermission.ts`), OR-match untuk array permission (`usePermission(['grades.create', 'grades.update'])`), dipakai konsisten di seluruh halaman untuk gating tombol.
- [ ] **Pola query/cache data server** — masih belum ada React Query/SWR; dipakai `usePaginatedList`/`useFetch` (hook custom sendiri di `src/hooks/`) sebagai gantinya. Cukup untuk skala saat ini, tapi kalau jumlah halaman terus bertambah (§5.1) pertimbangkan migrasi supaya tidak menulis `useEffect` fetch manual berulang-ulang.
- [x] **Error & loading state standar** — `NormalizedApiError` ditangani konsisten lewat `Alert` + `applyServerErrors` (mapping error field backend → react-hook-form), loading pakai kombinasi `isLoading`/skeleton text sederhana. Pola ini sudah dipakai di seluruh modul, termasuk form baru KRS/Nilai/Absensi.
- [x] **Pagination & filter standar** — `usePaginatedList` + `ListPagination` dipakai di semua endpoint list.
- [ ] **Environment config** — belum diverifikasi ulang; pastikan `VITE_API_BASE_URL` diset benar per environment (dev/staging/prod) sebelum deploy pertama.

## 7. Definition of done per fitur

Satu fitur dianggap selesai kalau:

- Data ditarik dari API asli (tidak ada mock tersisa di service-nya).
- Aksi create/update/delete mengembalikan feedback (sukses/gagal) yang terlihat user.
- Tombol aksi disembunyikan/dinonaktifkan sesuai permission user.
- State loading & empty state ditangani (pakai `Skeleton`/`EmptyState` yang sudah ada).
- Route terdaftar di `constants/routes.ts` dan `constants/nav.ts`, komponen halaman nyata terpasang di `App.tsx`.
