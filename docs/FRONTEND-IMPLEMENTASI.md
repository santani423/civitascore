# Rencana Implementasi Frontend

> **Status:** Dokumen kerja (living checklist), bukan blueprint bisnis.
> **Dibuat:** 2026-07-18
> Dokumen ini fokus pada langkah teknis untuk menyalakan fitur-fitur di `frontend/` satu per satu — apa yang sudah jalan, apa yang masih *mock*, dan urutan pengerjaan yang masuk akal berdasarkan API backend yang sudah tersedia. Untuk cakupan bisnis lengkap (role, modul, alur kerja), lihat [RANCANGAN-APLIKASI.md](./RANCANGAN-APLIKASI.md).

## 1. Kondisi saat ini

**Frontend** (`frontend/`, React 19 + Vite + TS + Tailwind + Zustand + React Router + Recharts):

- Sudah ada: shell aplikasi (`Sidebar`, `Header`, `DashboardLayout`, `AuthLayout`), sistem tema (light/dark), komponen UI dasar (`Button`, `Card`, `DataTable`, `Modal`, `Select`, dll di `src/components/ui/`), routing dengan guard (`ProtectedRoute`, `PublicOnlyRoute`).
- Login (`LoginPage`) dan session (`authStore`) sudah berfungsi, **tapi** `authService.ts` masih mensimulasikan login/me/logout secara lokal (`DEMO_ACCOUNT`), belum memanggil backend.
- Dashboard (`DashboardPage`) sudah tersusun rapi tapi seluruh chart/kartunya kemungkinan memakai data statis dari `src/data/` — belum ditarik dari API.
- 18 rute sisanya (Kurikulum, Mata Kuliah, Kelas & Jadwal, KRS, Absensi, Penilaian, Mahasiswa, Dosen, Pegawai, Tagihan, Beasiswa, Skripsi, Magang/MBKM, Perpustakaan, Alumni, Pengumuman, Laporan, Pengaturan) semuanya masih `PlaceholderPage` — belum ada UI sama sekali.

**Backend** (`app/Modules/`, Laravel + Sanctum, permission per-route via middleware `permission:xxx`):

Modul yang **sudah punya API siap pakai** (`routes/api.php` → `Modules/*/Routes/api.php`, prefix `/api/v1`):

| Modul backend | Endpoint | Untuk fitur frontend apa |
|---|---|---|
| `Auth` | `login`, `forgot-password`, `reset-password`, `me`, `logout`, `logout-all`, `sessions`, `devices` | Login, ganti password, manajemen sesi/perangkat |
| `UserManagement` | `roles`, `permissions`, `users/{user}/roles` | Pengaturan → Roles & Permissions |
| `SystemSetting` | `system-settings`, `feature-flags` | Pengaturan → System Settings, Feature Flags |
| `Notification` | `notification-templates`, `notification-channels`, `notification-preferences` | Pengaturan → Notifikasi |
| `FileManagement` | `file-uploads` (store/show/download/destroy) | Upload dokumen di form manapun (skripsi, KRS, dll) |
| `ApprovalWorkflow` | `approval-workflows`, `approval-requests`, `approval-request-steps/{id}/approve|reject|delegate` | Pusat persetujuan generik (dipakai lintas modul) |
| `AuditLog` | `audit-logs` | Pengaturan → Audit Log |

**Belum ada modul backend** untuk domain akademik/bisnis inti: Kurikulum, Mata Kuliah, Kelas & Jadwal, KRS, Absensi, Penilaian, Mahasiswa, Dosen, Pegawai, Keuangan (Tagihan/Beasiswa), Skripsi, Magang/MBKM, Perpustakaan, Alumni, Pengumuman, Laporan. Ini konsisten dengan sidebar yang masih placeholder — **frontend sudah lebih dulu dari backend di area ini.**

## 2. Prinsip pengerjaan

1. **Backend duluan, baru UI.** Jangan bangun halaman penuh untuk modul yang API-nya belum ada — cukup placeholder yang sudah berjalan sekarang. Urutkan pengerjaan sesuai ketersediaan API di tabel atas.
2. **Satu pola integrasi API, dipakai berulang.** `authService.ts` sudah menulis pola yang benar: fungsi async, error dilempar sebagai `NormalizedApiError`, komentar berisi endpoint asli. Ikuti pola yang sama untuk service baru — page dan store tidak perlu tahu bahwa datanya masih/baru real.
3. **Gating berbasis permission.** Backend sudah pakai middleware `permission:modul.aksi` (contoh: `roles.read`, `feature_flags.update`). Frontend perlu menyimpan daftar permission user (dari `/me`) dan menyembunyikan/menonaktifkan aksi (tombol create/update/delete) yang tidak diizinkan — jangan hanya mengandalkan 403 dari server.
4. **Jangan bongkar struktur yang sudah ada.** `ROUTES`, `NAV_ITEMS`, `ApiClient`, `authStore` sudah punya bentuk yang benar; modul baru cukup mengikuti pola yang sama (`constants/routes.ts` → `constants/nav.ts` → `pages/<domain>/` → `services/<domain>Service.ts`).

## 3. Fase 1 — Sambungkan Auth ke backend nyata

Prasyarat semua fase berikutnya: session harus asli, bukan token palsu.

- [ ] Ganti isi `authService.login` untuk memanggil `POST /auth/login` lewat `apiClient` (baris yang sudah di-comment di file sudah menunjukkan bentuknya).
- [ ] Ganti `authService.getCurrentUser` untuk memanggil `GET /me`.
- [ ] Ganti `authService.logout` untuk memanggil `POST /logout`.
- [ ] Sesuaikan `AuthUser`/`AuthSession` type (`src/types/auth.ts`) dengan bentuk response asli dari `AuthController` (termasuk daftar permission/role, bukan cuma `role: string`).
- [ ] Simpan daftar permission user di `authStore` agar bisa dipakai untuk gating UI (lihat Fase 2).
- [ ] Tambahkan halaman/flow **lupa password** dan **reset password** (endpoint `forgot-password`, `reset-password` sudah ada di backend, belum ada UI-nya).
- [ ] Tambahkan halaman **Sessions & Devices** (Pengaturan → Keamanan) memakai `GET sessions`, `DELETE sessions/{id}`, `GET devices`, `POST logout-all`.
- [ ] Hapus `DEMO_ACCOUNT`/`MOCK_USER` setelah integrasi asli terverifikasi jalan (jangan biarkan dua jalur hidup berdampingan).

## 4. Fase 2 — Modul Pengaturan & platform (API sudah ada)

Semua ini masuk ke rute `/pengaturan` (saat ini satu placeholder tunggal — perlu dipecah jadi sub-halaman/tab, mengikuti pola `akademik.*` yang sudah pakai children di `NAV_ITEMS`).

- [ ] **Roles & Permissions** — tabel roles (`GET/POST/PUT/DELETE roles`), tabel permissions (`GET/POST/PUT/DELETE permissions`), form sync permission per role (`PUT roles/{id}/permissions`), assign role ke user (`GET/POST/DELETE users/{user}/roles`).
- [ ] **System Settings** — daftar setting + form edit per item (`GET system-settings`, `PUT system-settings/{id}`).
- [ ] **Feature Flags** — toggle list (`GET feature-flags`, `PUT feature-flags/{id}`).
- [ ] **Notifikasi** — template notifikasi (`GET/POST/PUT notification-templates`), channel (`GET/PUT notification-channels`), preferensi per user (`GET/POST notification-preferences`).
- [ ] **Audit Log** — tabel read-only dengan filter & detail (`GET audit-logs`, `GET audit-logs/{id}`) memakai `DataTable` yang sudah ada.
- [ ] **File upload generik** — komponen upload reusable (dipakai lintas modul nanti) di atas `POST/GET/DELETE file-uploads` dan `download`.
- [ ] **Approval Center** — halaman/widget "Menunggu Persetujuan Saya" memakai `approval-requests`, `approval-workflows`, aksi approve/reject/delegate pada `approval-request-steps/{id}`. `PendingApprovalsTable` di dashboard sudah ada bentuknya — sambungkan ke API ini, lalu buat halaman detail penuh di luar dashboard.

Setiap sub-halaman di atas butuh: route baru di `constants/routes.ts`, entri di `constants/nav.ts` (ganti `PlaceholderPage` di `App.tsx` dengan komponen nyata), service di `services/`, dan gating tombol create/update/delete sesuai permission dari Fase 1.

## 5. Fase 3 — Modul akademik/bisnis (menunggu backend)

Belum bisa dikerjakan penuh karena modul backend-nya belum ada. Urutan yang disarankan begitu backend module tersedia satu per satu (ikuti urutan ketergantungan data: identitas dulu, baru transaksi akademik):

1. Mahasiswa, Dosen, Pegawai (data master — dibutuhkan modul lain sebagai referensi)
2. Kurikulum, Mata Kuliah (master data akademik)
3. Kelas & Jadwal (butuh Mata Kuliah + Dosen)
4. KRS (butuh Mahasiswa + Kelas)
5. Absensi, Penilaian (butuh KRS)
6. Keuangan: Tagihan, Beasiswa
7. Skripsi, Magang & MBKM
8. Perpustakaan, Alumni, Pengumuman, Laporan

Untuk tiap modul di atas, saat backend-nya sudah ada:

- [ ] Buat `services/<domain>Service.ts` mengikuti pola `authService.ts`.
- [ ] Ganti entri `PLACEHOLDER_ROUTES` yang relevan di `App.tsx` dengan komponen halaman nyata (list + detail/form minimal).
- [ ] Pakai komponen `DataTable`, `Card`, `EmptyState`, `Modal` yang sudah ada — jangan bikin ulang.
- [ ] Tambahkan gating permission sesuai konvensi backend (`permission:<modul>.<aksi>`).

Sampai fase ini dimulai, **jangan** membangun UI penuh untuk rute-rute ini — placeholder yang sekarang sudah cukup dan sengaja dirancang begitu (lihat komentar di `PlaceholderPage.tsx`).

## 6. Hal lintas-modul yang perlu disiapkan sekali, dipakai semua fase

- [ ] **Layer permission** — hook `usePermission('roles.read')` atau helper serupa yang membaca dari `authStore`, dipakai untuk gating tombol dan (opsional) menyembunyikan item `NAV_ITEMS` yang usernya tidak punya akses.
- [ ] **Pola query/cache data server** — saat ini belum ada React Query/SWR; tentukan sebelum Fase 2 meluas ke banyak halaman supaya tidak menulis `useEffect` fetch manual berulang-ulang di setiap page.
- [ ] **Error & loading state standar** — pastikan `NormalizedApiError` dari `api.ts` ditangani konsisten (toast/alert), pakai `Skeleton` yang sudah ada untuk loading.
- [ ] **Pagination & filter standar** untuk `DataTable` — banyak endpoint list (`roles`, `permissions`, `audit-logs`, dst.) akan butuh pola yang sama.
- [ ] **Environment config** — pastikan `VITE_API_BASE_URL` diset benar per environment (dev/staging/prod), bukan cuma default `localhost:8000`.

## 7. Definition of done per fitur

Satu fitur dianggap selesai kalau:

- Data ditarik dari API asli (tidak ada mock tersisa di service-nya).
- Aksi create/update/delete mengembalikan feedback (sukses/gagal) yang terlihat user.
- Tombol aksi disembunyikan/dinonaktifkan sesuai permission user.
- State loading & empty state ditangani (pakai `Skeleton`/`EmptyState` yang sudah ada).
- Route terdaftar di `constants/routes.ts` dan `constants/nav.ts`, `PlaceholderPage` untuk rute itu sudah dihapus dari `App.tsx`.
