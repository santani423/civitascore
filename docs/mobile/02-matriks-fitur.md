# Matriks Fitur Aplikasi Mobile Civitas One (Mahasiswa & Dosen)

> **Per:** 18 Oktober 2026. **Target:** aplikasi Android (Flutter); iOS menyusul pada fase berikutnya.
> **Sumber:** audit seluruh `docs/` (41 pembaca paralel; lihat `01-audit-dokumentasi.md`) dicocokkan dengan kode backend (`app/Modules/*/Routes`), portal web (`frontend/src`), dan aplikasi Flutter yang ada (`mobile/`). Bila dokumen dan kode berbeda, **kode yang dipakai**.

## 1. Cakupan peran

| Peran | Slug backend | Keterangan |
|---|---|---|
| Mahasiswa | `student` | `student_portal.*`, `krs_self_service.*`, `student_requests.*`, `exam_participation.*`, `assignment_submissions.create`, `student_finance.read/create` |
| Dosen | `lecturer` | perkuliahan (materi, tugas, nilai tugas), presensi, nilai, ujian & bank soal, profil dosen, `krs_advising.*`, layanan mandiri SDM |
| Dosen PA | `academic_advisor` (atau dosen dengan `krs_advising.*`) | persetujuan KRS mahasiswa perwalian, layanan mandiri SDM |

## 2. Legenda

- **Status backend:** ✅ tersedia (endpoint ada & dapat dipanggil peran tsb.) · 🟡 sebagian · ❌ belum ada.
- **Status mobile (sebelum pembangunan ini):** ✅ selesai · 🟡 sebagian · — belum ada. Kolom ini menggambarkan kondisi **sebelum** fase pembangunan aplikasi Android ini; status hasil implementasi dicatat di `04-status-implementasi-mobile.md`.
- **Prioritas:** P0 kebutuhan harian inti dengan backend tersedia · P1 penting · P2 berguna · P3 opsional.
- **Cakupan rekomendasi:** *Bangun + integrasi* (fungsi nyata ke API) · *Label "Menunggu backend"* (entri berlabel jelas, **tanpa data tiruan**; endpoint yang dibutuhkan dicatat di `03-integrasi-backend-dan-celah-api.md`) · *Ditunda* (belum jelas, nilai rendah, butuh infrastruktur besar, atau bukan fitur mobile mahasiswa/dosen).

## 3. Ringkasan angka

Total **470** baris kebutuhan.

| Cakupan | Jumlah |
|---|---|
| Bangun + integrasi | 240 |
| Label "Menunggu backend" | 92 |
| Ditunda | 138 |

| Status backend | Jumlah |
|---|---|
| ✅ tersedia | 192 |
| 🟡 sebagian | 68 |
| ❌ belum ada | 210 |

| Prioritas | Jumlah |
|---|---|
| P0 | 92 |
| P1 | 108 |
| P2 | 117 |
| P3 | 153 |

| Peran | Baris yang melibatkan |
|---|---|
| Mahasiswa | 318 |
| Dosen | 237 |
| Dosen PA | 116 |

| Domain | Baris |
|---|---|
| `finance` | 43 |
| `exam_cbt` | 41 |
| `lms` | 37 |
| `platform_mobile` | 36 |
| `lecturer_hr` | 35 |
| `krs_registration` | 34 |
| `graduation_career` | 25 |
| `auth_account` | 24 |
| `dashboard_home` | 24 |
| `academic_records` | 21 |
| `student_affairs` | 19 |
| `announcements_notifications` | 19 |
| `attendance` | 18 |
| `grading_obe` | 18 |
| `letters_services` | 17 |
| `thesis_internship` | 15 |
| `schedule_calendar` | 11 |
| `other` | 10 |
| `short_semester` | 9 |
| `edom_survey` | 8 |
| `library` | 6 |

## 4. Tabel ringkas seluruh kebutuhan

| ID | Fitur | Peran | Backend | Web | Mobile awal | Prioritas | Cakupan |
|---|---|---|---|---|---|---|---|
| MOB-AUTH-01 | Login dengan email + kata sandi (dosen / dosen PA) | Dosen, Dosen PA | ✅ tersedia | pages/LoginPage.tsx (tab 'lainnya': email + password min 6) | partial (login_screen.dart: email+sandi jalan; validator email, 'Ingat saya' tidak berfungsi, tidak kirim info perangkat, tidak kirim X-University-ID) | P0 | Bangun + integrasi |
| MOB-AUTH-02 | Login mahasiswa dengan NIM + kata sandi (kode universitas) | Mahasiswa | ✅ tersedia | pages/LoginPage.tsx (tab mahasiswa, field NIM) | none (aplikasi hanya mengirim {email,password}; tidak ada field NIM / university_code) | P0 | Bangun + integrasi |
| MOB-AUTH-03 | Kirim identitas perangkat saat login (nama, platform, device_identifier) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | none | none (mobile tidak mengirim info perangkat) | P1 | Bangun + integrasi |
| MOB-AUTH-04 | Pulihkan sesi saat aplikasi dibuka (cold start, aman offline) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | none (SPA bergantung pada cookie/localStorage; 401 membersihkan sesi) | partial (restore ada tetapi logout saat offline) | P0 | Bangun + integrasi |
| MOB-AUTH-05 | Keluar (logout) dan penanganan sesi dicabut (401) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | layout header (logout) + interceptor 401 | done (logout + clearSession pada 401; belum menghapus cache karena belum ada cache) | P0 | Bangun + integrasi |
| MOB-AUTH-06 | Keluar dari semua perangkat | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | SecuritySessionsPage.tsx | done (security_sessions_screen) | P1 | Bangun + integrasi |
| MOB-AUTH-07 | Minta tautan reset kata sandi lewat email | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | pages/ForgotPasswordPage.tsx | done (forgot_password_screen) | P0 | Bangun + integrasi |
| MOB-AUTH-08 | Atur ulang kata sandi dari tautan email (deep link ke aplikasi) | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | pages/ResetPasswordPage.tsx | partial (layar ada, token/email via query; tanpa intent filter/App Link) | P1 | Bangun + integrasi |
| MOB-AUTH-09 | Ubah kata sandi (sukarela) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | ChangePasswordPage.tsx (/ubah-password) | — | P1 | Bangun + integrasi |
| MOB-AUTH-10 | Alur ganti sandi wajib saat login pertama (must_change_password) | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | ChangePasswordPage.tsx + route guard | — | P0 | Bangun + integrasi |
| MOB-AUTH-11 | Lihat dan cabut sesi aktif / daftar perangkat | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | SecuritySessionsPage.tsx | done (security_sessions_screen: sesi + perangkat + revoke + logout-all) | P1 | Bangun + integrasi |
| MOB-AUTH-12 | Autentikasi dua faktor (2FA/MFA) | Mahasiswa, Dosen, Dosen PA | ❌ belum ada | none (prototype one/Account.tsx menampilkan toggle 2FA statis) | — | P3 | Ditunda |
| MOB-AUTH-13 | Proteksi login: rate limit dan kebijakan kata sandi di UI | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | forms (zod) | partial (validator klien min 6; belum menangani 429 khusus) | P2 | Bangun + integrasi |
| MOB-AUTH-14 | Muat konteks peran dan izin (/me) untuk menentukan pengalaman pengguna | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | hooks/useIsStudent.ts; nav.ts filter permission | partial (AuthUser + PermissionGate; tidak ada isLecturer/isAdvisor, parsing rapuh) | P0 | Bangun + integrasi |
| MOB-AUTH-15 | Kedaluwarsa token / refresh token dan revoke-saat-ganti-sandi | Mahasiswa, Dosen, Dosen PA | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-AUTH-16 | Lihat profil mahasiswa (biodata, akademik) | Mahasiswa | ✅ tersedia | pages/portal/PortalProfilePage.tsx (real API) | none (dashboard placeholder saja) | P0 | Bangun + integrasi |
| MOB-AUTH-17 | Ubah nomor telepon, alamat, dan foto profil (mahasiswa) | Mahasiswa | ✅ tersedia | pages/portal/PortalProfilePage.tsx | — | P1 | Bangun + integrasi |
| MOB-AUTH-18 | Ajukan perubahan data pribadi (nama, email, tempat/tanggal lahir, gender) | Mahasiswa | ✅ tersedia | pages/portal (pengajuan data) real API | — | P2 | Bangun + integrasi |
| MOB-AUTH-19 | Lihat profil dosen / dosen PA dan ubah nomor telepon | Dosen, Dosen PA | ✅ tersedia | pages/LecturerProfilePage.tsx (/profil-dosen) | — | P0 | Bangun + integrasi |
| MOB-AUTH-20 | Foto profil dosen | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-AUTH-21 | Persetujuan data pribadi dan hak subjek data (UU PDP) | Mahasiswa, Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-AUTH-22 | Isolasi data per peran dan kepemilikan objek (sisi klien) | Mahasiswa, Dosen | 🟡 sebagian | n/a | — | P1 | Bangun + integrasi |
| MOB-AUTH-23 | Pusat keamanan akun (ringkasan) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | SecuritySessionsPage.tsx | partial (security_sessions_screen) | P2 | Bangun + integrasi |
| MOB-AUTH-24 | Aktivasi akun mahasiswa baru hasil konversi PMB | Mahasiswa | 🟡 sebagian | pages/ChangePasswordPage.tsx | — | P1 | Bangun + integrasi |
| MOB-DASH-01 | Beranda mahasiswa: ringkasan profil dan akademik (IPK, IPS, SKS, status KRS, dosen PA) | Mahasiswa | ✅ tersedia | pages/portal/PortalDashboardPage.tsx (real API) | partial (dashboard_screen hanya placeholder: sapaan + logout + tombol keamanan) | P0 | Bangun + integrasi |
| MOB-DASH-02 | Jadwal kuliah hari ini dan kelas berikutnya (beranda mahasiswa) | Mahasiswa | ✅ tersedia | PortalDashboardPage.tsx | — | P0 | Bangun + integrasi |
| MOB-DASH-03 | Ringkasan absensi dan peringatan kehadiran di bawah minimum (beranda) | Mahasiswa | ✅ tersedia | PortalDashboardPage.tsx | — | P1 | Bangun + integrasi |
| MOB-DASH-04 | Ujian mendatang (beranda mahasiswa) | Mahasiswa | ✅ tersedia | PortalDashboardPage.tsx | — | P1 | Bangun + integrasi |
| MOB-DASH-05 | Tugas mendekati tenggat (beranda mahasiswa) | Mahasiswa | ✅ tersedia | PortalDashboardPage.tsx | — | P1 | Bangun + integrasi |
| MOB-DASH-06 | Nilai terbaru (beranda mahasiswa) | Mahasiswa | ✅ tersedia | PortalDashboardPage.tsx | — | P2 | Bangun + integrasi |
| MOB-DASH-07 | Pengumuman terbaru dan jumlah belum dibaca (beranda mahasiswa) | Mahasiswa | ✅ tersedia | PortalDashboardPage.tsx + PortalAnnouncementsPage | — | P1 | Bangun + integrasi |
| MOB-DASH-08 | Peringatan beranda (alerts) dan pemetaan tautan web ke layar mobile | Mahasiswa | 🟡 sebagian | PortalDashboardPage.tsx | — | P1 | Bangun + integrasi |
| MOB-DASH-09 | Kartu ringkasan keuangan di beranda (tunggakan, jatuh tempo terdekat, deposit, cekal) | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx | — | P1 | Bangun + integrasi |
| MOB-DASH-10 | Badge notifikasi belum dibaca di beranda dan navigasi (semua peran) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | header notification bell (polling) | — | P1 | Bangun + integrasi |
| MOB-DASH-11 | Widget tambahan beranda mahasiswa: pengajuan surat, jadwal bimbingan, kalender akademik | Mahasiswa | 🟡 sebagian | PortalCalendarPage; PortalAcademicAdvisingPage = mock | — | P2 | Bangun + integrasi |
| MOB-DASH-12 | Pemilihan beranda berdasarkan peran (mahasiswa / dosen / dosen PA) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | DashboardRoute (useIsStudent) di web | partial (hanya satu placeholder) | P0 | Bangun + integrasi |
| MOB-DASH-13 | Beranda dosen: jadwal mengajar hari ini dan daftar kelas diampu | Dosen | 🟡 sebagian | DashboardPage.tsx (admin stats + LecturerSummaryCard); jadwal mengajar hanya di  | — | P0 | Bangun + integrasi |
| MOB-DASH-14 | Beranda dosen: ringkasan beban mengajar (kelas, SKS, ujian aktif, bank soal) | Dosen | 🟡 sebagian | LecturerSummaryCard (web) | partial (modul ujian ada; belum ada kartu beranda) | P2 | Bangun + integrasi |
| MOB-DASH-15 | Beranda dosen PA: antrean persetujuan KRS mahasiswa bimbingan | Dosen PA | ✅ tersedia | none (nav ada, halaman belum dirutekan) | — | P0 | Bangun + integrasi |
| MOB-DASH-16 | Beranda dosen: tugas perlu diperiksa dan nilai belum lengkap | Dosen | 🟡 sebagian | none | — | P2 | Bangun + integrasi |
| MOB-DASH-17 | Pengumuman untuk dosen | Dosen, Dosen PA | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-DASH-18 | Widget dosen lain: mahasiswa bimbingan, jadwal sidang, aktivitas penelitian | Dosen, Dosen PA | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-DASH-19 | Pemilih tampilan multi-peran (Pengajar / Dosen Wali) | Dosen, Dosen PA | ✅ tersedia | none | — | P2 | Bangun + integrasi |
| MOB-DASH-20 | Statistik institusi (GET /dashboard) untuk dosen | Dosen | ✅ tersedia | pages/DashboardPage.tsx | — | P3 | Ditunda |
| MOB-DASH-21 | Aksi cepat beranda per peran | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | LecturerSummaryCard (quick links) | — | P3 | Bangun + integrasi |
| MOB-DASH-22 | Agenda akademik di beranda (kalender akademik) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | PortalCalendarPage.tsx | — | P2 | Bangun + integrasi |
| MOB-DASH-23 | Dashboard LMS (sesi pertemuan, QR presensi, jurnal, EDOM) mahasiswa/dosen | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-DASH-24 | Laporan kelas dan ekspor untuk dosen | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-PLAT-01 | Tema terang / gelap / ikuti sistem | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | one/store/prefs.ts (mock), layout web light/dark | partial (token light/dark + persist; tanpa UI toggle; widget tidak sadar-tema) | P1 | Bangun + integrasi |
| MOB-PLAT-02 | Internasionalisasi: infrastruktur ARB dan bahasa Indonesia (default) | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | one/i18n (mock, 733 key) | — | P1 | Bangun + integrasi |
| MOB-PLAT-03 | Bahasa Inggris (EN) | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | one/i18n | — | P2 | Bangun + integrasi |
| MOB-PLAT-04 | Bahasa Mandarin sederhana (ZH) | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | one/i18n (zh) | — | P3 | Ditunda |
| MOB-PLAT-05 | Penyimpanan token aman | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | n/a | partial (secure storage ada; allowBackup/pembacaan per-request belum dirapikan) | P0 | Bangun + integrasi |
| MOB-PLAT-06 | Navigasi berbasis peran: shell mahasiswa | Mahasiswa | ✅ tersedia | nav.ts PORTAL_NAV_ITEMS | — | P0 | Bangun + integrasi |
| MOB-PLAT-07 | Navigasi berbasis peran: shell dosen dan dosen PA | Dosen, Dosen PA | ✅ tersedia | nav.ts (admin-flavoured) | — | P0 | Bangun + integrasi |
| MOB-PLAT-08 | Pembatasan UI berdasarkan izin dan layar Akses Ditolak | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | route guards | done (PermissionGate + guard router) | P0 | Bangun + integrasi |
| MOB-PLAT-09 | Mode offline: banner konektivitas dan cache baca | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | none | — | P1 | Bangun + integrasi |
| MOB-PLAT-10 | Penanganan kegagalan jaringan: timeout, retry, pemetaan error | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | axios timeout 15s | 🟡 | P0 | Bangun + integrasi |
| MOB-PLAT-11 | Pull-to-refresh, skeleton, empty/error state | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | components/portal/PortalState.tsx | — | P1 | Bangun + integrasi |
| MOB-PLAT-12 | Buka kunci biometrik | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | none | — | P1 | Bangun + integrasi |
| MOB-PLAT-13 | Kunci otomatis setelah tidak aktif | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | none | — | P2 | Bangun + integrasi |
| MOB-PLAT-14 | Notifikasi push (FCM) | Mahasiswa, Dosen, Dosen PA | ❌ belum ada | none (web polling) | — | P1 | Label "Menunggu backend" |
| MOB-PLAT-15 | Pengaturan aplikasi | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | mock (one/pages/Account.tsx) | — | P1 | Bangun + integrasi |
| MOB-PLAT-16 | Deep link dan App Links | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | none | — | P1 | Bangun + integrasi |
| MOB-PLAT-17 | Aksesibilitas | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | one prefs (highContrast, fontScale, reducedMotion mock) | — | P1 | Bangun + integrasi |
| MOB-PLAT-18 | Perlindungan screenshot pada layar sensitif | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | none | — | P2 | Bangun + integrasi |
| MOB-PLAT-19 | Branding dan profil universitas (tenant) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | none | — | P2 | Bangun + integrasi |
| MOB-PLAT-20 | Gerbang router ganti sandi wajib | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | route guard web | — | P0 | Bangun + integrasi |
| MOB-PLAT-21 | Penanganan tenant: build satu-universitas vs X-University-ID | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | X-University-ID hanya dari Tenant Switcher Super Admin | partial (pembaca header ada, penulis tidak ada) | P0 | Bangun + integrasi |
| MOB-PLAT-22 | Zona waktu tenant dan format tanggal/angka/mata uang | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | formatters web | — | P1 | Bangun + integrasi |
| MOB-PLAT-23 | Klien API: envelope, paginasi, dan pemetaan kode error | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | axios + zod | partial (ApiFailure rapuh) | P0 | Bangun + integrasi |
| MOB-PLAT-24 | Unggah, unduh, dan pratinjau berkas (kamera/galeri/penyimpanan) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | axios blob download | — | P0 | Bangun + integrasi |
| MOB-PLAT-25 | Idempotensi dan penguncian optimistik pada aksi tulis | Mahasiswa, Dosen | 🟡 sebagian | keu portal | — | P1 | Bangun + integrasi |
| MOB-PLAT-26 | Gerbang fitur/entitlement dari server (menu tersembunyi) | Mahasiswa, Dosen, Dosen PA | ❌ belum ada | none | — | P1 | Label "Menunggu backend" |
| MOB-PLAT-27 | Versi aplikasi, paksa pembaruan, mode pemeliharaan | Mahasiswa, Dosen, Dosen PA | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-PLAT-28 | Pencarian global | Mahasiswa, Dosen | ❌ belum ada | mock (one/ command palette) | — | P3 | Ditunda |
| MOB-PLAT-29 | Pelaporan crash dan analitik ramah privasi | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | none | — | P3 | Ditunda |
| MOB-PLAT-30 | Hardening keamanan rilis: pinning sertifikat, obfuscation, backup | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | n/a | 🟡 | P1 | Bangun + integrasi |
| MOB-PLAT-31 | Kesiapan rilis Android | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | n/a | 🟡 | P1 | Bangun + integrasi |
| MOB-PLAT-32 | Edge-to-edge, predictive back, penjaga perubahan belum disimpan | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | n/a | — | P3 | Bangun + integrasi |
| MOB-PLAT-33 | Splash native dan layar pemulihan sesi | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | n/a | — | P2 | Bangun + integrasi |
| MOB-PLAT-34 | Dukungan iOS (tahap lanjut) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | n/a | — | P3 | Ditunda |
| MOB-PLAT-35 | Fondasi teknis: migrasi Riverpod 3, rapikan dependensi, pengujian | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | n/a | 🟡 | P1 | Bangun + integrasi |
| MOB-PLAT-36 | Whitelabel per kampus (flavor) | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-ACAD-01 | Lihat ringkasan akademik mahasiswa | Mahasiswa | ✅ tersedia | pages/portal/PortalAcademicPage.tsx | — | P0 | Bangun + integrasi |
| MOB-ACAD-02 | Lihat riwayat akademik (timeline masuk, semester, perubahan status, kelulusan) | Mahasiswa | ✅ tersedia | pages/portal/PortalAcademicPage.tsx | — | P2 | Bangun + integrasi |
| MOB-ACAD-03 | Lihat nilai seluruh mata kuliah per semester | Mahasiswa | ✅ tersedia | pages/portal/PortalGradesPage.tsx | — | P0 | Bangun + integrasi |
| MOB-ACAD-04 | Lihat KHS per semester (IPS, SKS, rincian nilai) | Mahasiswa | ✅ tersedia | pages/portal/PortalKhsPage.tsx | — | P0 | Bangun + integrasi |
| MOB-ACAD-05 | Lihat transkrip sementara dengan IPK | Mahasiswa | ✅ tersedia | pages/portal/PortalTranscriptPage.tsx | — | P0 | Bangun + integrasi |
| MOB-ACAD-06 | Daftar dokumen akademik yang dapat diunduh | Mahasiswa | ✅ tersedia | pages/portal/PortalDocumentsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-ACAD-07 | Unduh KHS PDF | Mahasiswa | ✅ tersedia | pages/portal/PortalKhsPage.tsx / PortalDocumentsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-ACAD-08 | Unduh transkrip PDF | Mahasiswa | ✅ tersedia | pages/portal/PortalTranscriptPage.tsx | — | P1 | Bangun + integrasi |
| MOB-ACAD-09 | Gerbang akses nilai/transkrip (cekal keuangan, EDOM) | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-ACAD-10 | Detail nilai per komponen (tugas, UTS, UAS, dsb.) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-ACAD-11 | Lihat konversi SKS/transfer kredit dan penanda MK ulang/pemutihan pada transkrip | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-ACAD-12 | Dokumen resmi kelulusan (SK, transkrip final, SKL, SKPI, ijazah) dengan QR | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-ACAD-13 | Lihat profil mahasiswa dan ubah data terbatas | Mahasiswa | ✅ tersedia | pages/portal/PortalProfilePage.tsx | — | P1 | Bangun + integrasi |
| MOB-ACAD-14 | Ajukan cuti studi dan layanan administrasi akademik | Mahasiswa | ✅ tersedia | pages/portal/PortalRequestsPage.tsx | — | P2 | Bangun + integrasi |
| MOB-ACAD-15 | Laporan mandiri mahasiswa (katalog laporan akademik) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-ACAD-16 | Katalog program studi, mata kuliah, dan prasyarat | Dosen, Mahasiswa | 🟡 sebagian | pages/academic/StudyProgramsPage.tsx, CoursesPage.tsx (dosen); mahasiswa: none | — | P3 | Bangun + integrasi |
| MOB-ACAD-17 | Dosen: cari dan lihat daftar serta detail mahasiswa | Dosen | ✅ tersedia | pages/academic/StudentsPage.tsx, StudentDetailPage.tsx | — | P2 | Bangun + integrasi |
| MOB-ACAD-18 | Dosen: lihat transkrip ringkas mahasiswa | Dosen, Dosen PA | ✅ tersedia | pages/academic/StudentDetailPage.tsx | — | P1 | Bangun + integrasi |
| MOB-ACAD-19 | Dosen: daftar kelas yang diampu (dengan jadwal) | Dosen | ✅ tersedia | none (nav 'Kelas Saya' tanpa halaman); kelas admin: pages/academic/ClassSections | — | P0 | Bangun + integrasi |
| MOB-ACAD-20 | Dosen: roster peserta kelas | Dosen | ✅ tersedia | pages/academic/ClassSectionDetailPage.tsx, KrsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-ACAD-21 | Notifikasi nilai terbit/direvisi | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-KRS-01 | Lihat KRS semester berjalan | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx | — | P0 | Bangun + integrasi |
| MOB-KRS-02 | Lihat penawaran kelas KRS dengan pemeriksaan kelayakan | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx | — | P0 | Bangun + integrasi |
| MOB-KRS-03 | Tambah kelas ke draft KRS | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx | — | P0 | Bangun + integrasi |
| MOB-KRS-04 | Hapus kelas dari draft KRS | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx | — | P0 | Bangun + integrasi |
| MOB-KRS-05 | Ajukan KRS ke dosen wali | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx | — | P0 | Bangun + integrasi |
| MOB-KRS-06 | Tarik kembali pengajuan KRS | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-KRS-07 | Batas beban SKS berdasarkan IPS dan indikator SKS | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx | — | P0 | Bangun + integrasi |
| MOB-KRS-08 | Deteksi bentrok jadwal dan kelas penuh saat memilih kelas | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx | — | P0 | Bangun + integrasi |
| MOB-KRS-09 | Validasi prasyarat dan MK yang sudah lulus/mengulang | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-KRS-10 | Penegakan periode KRS | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx | — | P0 | Bangun + integrasi |
| MOB-KRS-11 | Cekal (blokir) akademik/keuangan pada KRS dengan banner dan alasan | Mahasiswa | 🟡 sebagian | pages/portal/PortalKrsPage.tsx (notices); keuangan: keu/pages/portal/ | — | P0 | Bangun + integrasi |
| MOB-KRS-12 | Lihat riwayat KRS per semester | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-KRS-13 | Cetak/unduh KRS PDF | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx, PortalDocumentsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-KRS-14 | Status keputusan KRS dan umpan balik penolakan dosen PA | Mahasiswa | ✅ tersedia | pages/portal/PortalKrsPage.tsx, PortalNotificationsPage.tsx | — | P0 | Bangun + integrasi |
| MOB-KRS-15 | Perubahan KRS setelah terkunci (add/drop, permintaan perubahan) | Mahasiswa, Dosen PA | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-KRS-16 | Simulasi validasi KRS (dry-run) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KRS-17 | Paket KRS otomatis semester awal | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KRS-18 | Semester Pendek: ajukan KRS multi-kelas | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-KRS-19 | Semester Pendek: lihat KRS SP dengan status persetujuan/pembayaran | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-KRS-20 | Semester Pendek: batalkan item KRS SP | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KRS-21 | Dosen PA: antrean pengajuan KRS mahasiswa bimbingan | Dosen PA, Dosen | ✅ tersedia | none (nav 'Persetujuan KRS' tanpa halaman; tipe ada di studentPortalService.ts) | — | P0 | Bangun + integrasi |
| MOB-KRS-22 | Dosen PA: setujui KRS mahasiswa bimbingan | Dosen PA, Dosen | ✅ tersedia | none (tipe saja) | — | P0 | Bangun + integrasi |
| MOB-KRS-23 | Dosen PA: tolak/kembalikan KRS dengan catatan | Dosen PA, Dosen | ✅ tersedia | none (tipe saja) | — | P0 | Bangun + integrasi |
| MOB-KRS-24 | Notifikasi alur KRS (pengajuan masuk ke dosen PA, keputusan ke mahasiswa) | Mahasiswa, Dosen PA | 🟡 sebagian | pages/portal/PortalNotificationsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-KRS-25 | Dosen PA: persetujuan massal KRS | Dosen PA | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KRS-26 | Dosen PA: daftar mahasiswa bimbingan dan ringkasan progres | Dosen PA | ❌ belum ada | none (halaman mock PortalAcademicAdvisingPage untuk mahasiswa saja) | — | P1 | Label "Menunggu backend" |
| MOB-KRS-27 | Dosen PA: lihat data akademik mahasiswa bimbingan (transkrip, KHS) | Dosen PA, Dosen | 🟡 sebagian | pages/academic/StudentDetailPage.tsx | — | P1 | Bangun + integrasi |
| MOB-KRS-28 | Dosen PA: peringatan mahasiswa berisiko | Dosen PA | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-KRS-29 | Catatan bimbingan akademik/konsultasi (dosen PA mencatat, mahasiswa melihat yang dibagikan) | Dosen PA, Mahasiswa | ❌ belum ada | mahasiswa: mock tanpa route (PortalAcademicAdvisingPage.tsx); dosen: none | — | P2 | Label "Menunggu backend" |
| MOB-KRS-30 | Mahasiswa: halaman dosen PA (profil, jadwal konsultasi, riwayat) | Mahasiswa | 🟡 sebagian | mock tanpa route (PortalAcademicAdvisingPage.tsx) | — | P2 | Label "Menunggu backend" |
| MOB-KRS-31 | Penjadwalan konsultasi bimbingan | Dosen PA, Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KRS-32 | Dosen PA: status cekal keuangan advisee (tanpa nominal) | Dosen PA, Dosen | 🟡 sebagian | none | — | P2 | Bangun + integrasi |
| MOB-KRS-33 | Persetujuan KRS lewat ApprovalWorkflow generik di mobile | Dosen PA | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KRS-34 | Tagihan SKS otomatis saat KRS disetujui (dampak ke mahasiswa) | Mahasiswa, Dosen PA | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx | — | P2 | Ditunda |
| MOB-SCHED-01 | Lihat jadwal kuliah mingguan (mahasiswa) | Mahasiswa | ✅ tersedia | pages/portal/PortalSchedulePage.tsx | — | P0 | Bangun + integrasi |
| MOB-SCHED-02 | Lihat jadwal hari ini dan kuliah berikutnya (mahasiswa) | Mahasiswa | ✅ tersedia | pages/portal/PortalSchedulePage.tsx (tab Hari Ini) | — | P0 | Bangun + integrasi |
| MOB-SCHED-03 | Kalender akademik dan agenda pribadi mahasiswa | Mahasiswa | ✅ tersedia | pages/portal/PortalCalendarPage.tsx | — | P1 | Bangun + integrasi |
| MOB-SCHED-04 | Kalender akademik untuk dosen | Dosen, Dosen PA | ✅ tersedia | none (admin: pengaturan kalender; web dosen tidak punya halaman kalender) | — | P1 | Bangun + integrasi |
| MOB-SCHED-05 | Jadwal mengajar mingguan dosen | Dosen | 🟡 sebagian | none (nav 'Kelas Saya' tanpa halaman) | — | P0 | Bangun + integrasi |
| MOB-SCHED-06 | Pertemuan/kelas hari ini (dosen) | Dosen | 🟡 sebagian | none | — | P1 | Bangun + integrasi |
| MOB-SCHED-07 | Jadwal ujian UTS/UAS dan ruang ujian | Mahasiswa, Dosen | 🟡 sebagian | pages/portal/PortalCalendarPage.tsx, PortalExamsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-SCHED-08 | Notifikasi perubahan jadwal (ruang, dosen, pengganti, dibatalkan) | Mahasiswa, Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-SCHED-09 | Ruang dan sesi kuliah (lecture sessions), jadwal praktikum/tutorial | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-SCHED-10 | Lihat semester aktif dan periode KRS | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | pages/academic/KrsPage.tsx (admin/dosen) | — | P1 | Bangun + integrasi |
| MOB-SCHED-11 | Integrasi kalender eksternal (Google Calendar/ICS) | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-ATT-01 | Lihat ringkasan kehadiran per mata kuliah (semester berjalan) | Mahasiswa | ✅ tersedia | pages/portal/PortalAttendancePage.tsx (terhubung ke /student/attendance); one/pa | — | P0 | Bangun + integrasi |
| MOB-ATT-02 | Pilih semester untuk riwayat kehadiran | Mahasiswa | ✅ tersedia | pages/portal/PortalAttendancePage.tsx (terhubung ke /student/attendance); one/pa | — | P1 | Bangun + integrasi |
| MOB-ATT-03 | Lihat detail kehadiran per pertemuan | Mahasiswa | ✅ tersedia | pages/portal/PortalAttendancePage.tsx (terhubung ke /student/attendance); one/pa | — | P0 | Bangun + integrasi |
| MOB-ATT-04 | Peringatan kehadiran di bawah batas minimum | Mahasiswa | ✅ tersedia | one/pages/Learning.tsx (mock 75% + at-risk); web portal memakai dashboard alerts | — | P0 | Bangun + integrasi |
| MOB-ATT-05 | Cek kelayakan ikut ujian berdasarkan kehadiran | Mahasiswa, Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-ATT-06 | Presensi mandiri dengan QR (scan QR dosen) | Mahasiswa | ❌ belum ada | none (web portal tanpa check-in; one/ mock tombol Lakukan Presensi) | — | P1 | Label "Menunggu backend" |
| MOB-ATT-07 | Presensi mandiri non-QR (PIN, GPS/geofence, NFC, wajah/AI) | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-ATT-08 | Ajukan izin/sakit dengan surat pendukung | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-ATT-09 | Ajukan koreksi presensi (mahasiswa) | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-ATT-10 | Rekam presensi per pertemuan (batch) | Dosen | ✅ tersedia | pages/academic/AttendancePage.tsx (RecordAttendanceModal; gaya admin) | — | P0 | Bangun + integrasi |
| MOB-ATT-11 | Koreksi presensi yang sudah direkam (oleh dosen) | Dosen | 🟡 sebagian | pages/academic/AttendancePage.tsx | — | P1 | Bangun + integrasi |
| MOB-ATT-12 | Lihat riwayat/daftar presensi kelas (dosen) | Dosen | ✅ tersedia | pages/academic/AttendancePage.tsx | — | P1 | Bangun + integrasi |
| MOB-ATT-13 | Rekap presensi per kelas / per mahasiswa (dosen) | Dosen | 🟡 sebagian | none (AttendancePage hanya daftar) | — | P1 | Bangun + integrasi |
| MOB-ATT-14 | Pantau kehadiran mahasiswa bimbingan (dosen PA) | Dosen PA | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-ATT-15 | Buka/tutup sesi presensi QR dan rotasi token (dosen) | Dosen | ❌ belum ada | none | — | P1 | Label "Menunggu backend" |
| MOB-ATT-16 | Sesi perkuliahan dan jurnal mengajar (BAP) | Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-ATT-17 | Persetujuan permohonan izin dan koreksi presensi (dosen) | Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-ATT-18 | Presensi dosen (kehadiran mengajar) | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LMS-01 | Daftar kelas yang saya ampu (Kelas Saya) | Dosen | ✅ tersedia | Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route K | — | P0 | Bangun + integrasi |
| MOB-LMS-02 | Daftar peserta kelas | Dosen | ✅ tersedia | pages/academic/ (picker di AttendancePage/GradesPage) | — | P0 | Bangun + integrasi |
| MOB-LMS-03 | Daftar mata kuliah semester berjalan (LMS mahasiswa) | Mahasiswa | ✅ tersedia | pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS leng | — | P0 | Bangun + integrasi |
| MOB-LMS-04 | Detail mata kuliah (pertemuan, materi, tugas, ujian, kehadiran) | Mahasiswa | ✅ tersedia | pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS leng | — | P0 | Bangun + integrasi |
| MOB-LMS-05 | Lihat dan unduh materi kuliah | Mahasiswa | ✅ tersedia | pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS leng | — | P0 | Bangun + integrasi |
| MOB-LMS-06 | Daftar tugas dengan filter status | Mahasiswa | ✅ tersedia | pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS leng | — | P0 | Bangun + integrasi |
| MOB-LMS-07 | Detail tugas | Mahasiswa | ✅ tersedia | pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS leng | — | P0 | Bangun + integrasi |
| MOB-LMS-08 | Kumpulkan tugas (berkas dan/atau catatan) | Mahasiswa | ✅ tersedia | pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS leng | — | P0 | Bangun + integrasi |
| MOB-LMS-09 | Kumpulkan ulang / revisi tugas | Mahasiswa | ✅ tersedia | pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS leng | — | P1 | Bangun + integrasi |
| MOB-LMS-10 | Lihat nilai dan umpan balik tugas | Mahasiswa | ✅ tersedia | pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS leng | — | P0 | Bangun + integrasi |
| MOB-LMS-11 | Daftar materi per kelas (dosen) | Dosen | ✅ tersedia | Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route K | — | P0 | Bangun + integrasi |
| MOB-LMS-12 | Tambah materi (file/link/video/teks) | Dosen | ✅ tersedia | Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route K | — | P0 | Bangun + integrasi |
| MOB-LMS-13 | Ubah, hapus, terbitkan/tarik materi | Dosen | ✅ tersedia | Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route K | — | P1 | Bangun + integrasi |
| MOB-LMS-14 | Buat tugas (dosen) | Dosen | ✅ tersedia | Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route K | — | P0 | Bangun + integrasi |
| MOB-LMS-15 | Daftar, ubah, hapus tugas (dosen) | Dosen | ✅ tersedia | Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route K | — | P1 | Bangun + integrasi |
| MOB-LMS-16 | Lihat pengumpulan tugas (roster) | Dosen | ✅ tersedia | Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route K | — | P0 | Bangun + integrasi |
| MOB-LMS-17 | Nilai pengumpulan tugas dengan umpan balik | Dosen | ✅ tersedia | Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route K | — | P0 | Bangun + integrasi |
| MOB-LMS-18 | Unduh berkas pengumpulan mahasiswa (dosen) | Dosen | 🟡 sebagian | Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route K | — | P0 | Bangun + integrasi |
| MOB-LMS-19 | Kirim nilai tugas ke komponen nilai akhir | Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-LMS-20 | Kuis LMS (mahasiswa) | Mahasiswa | ❌ belum ada | mock pages/portal/PortalQuizzesPage.tsx | — | P2 | Label "Menunggu backend" |
| MOB-LMS-21 | Buat kuis LMS (dosen) | Dosen | 🟡 sebagian | one/exams pages (ujian) | 🟡 | P2 | Ditunda |
| MOB-LMS-22 | Forum diskusi kelas | Mahasiswa, Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-LMS-23 | Pengumuman kelas | Mahasiswa, Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-LMS-24 | Kelas LMS umum (non-akademik), gabung dengan kode, aktif/arsip | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LMS-25 | Catat progres materi | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-LMS-26 | Versi materi dan duplikasi ke semester berikutnya | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LMS-27 | Penilaian PDF dengan anotasi | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LMS-28 | Gamifikasi (poin, lencana, leaderboard) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LMS-29 | Student Engagement Score | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LMS-30 | Lecture Performance Score dan dashboard dosen | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LMS-31 | Logging aktivitas klien (antrean offline) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LMS-32 | Rekap aktivitas kelas (ringkasan pengumpulan) | Dosen | 🟡 sebagian | none | — | P2 | Bangun + integrasi |
| MOB-LMS-33 | Ringkasan LMS mahasiswa bimbingan (dosen PA) | Dosen PA | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LMS-34 | RPS dan realisasi pembelajaran | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LMS-35 | MOOC, SPADA, dan video pembelajaran interaktif | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LMS-36 | Progres CPMK, rekomendasi, sertifikat penyelesaian | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LMS-37 | Tugas kelompok | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-EXAM-01 | Daftar ujian saya dengan status pribadi | Mahasiswa | ✅ tersedia | pages/portal/PortalExamsPage.tsx | — | P0 | Bangun + integrasi |
| MOB-EXAM-02 | Checklist persiapan ujian & tips anti-pelanggaran | Mahasiswa | ✅ tersedia | none (hanya video/tutorial) | — | P3 | Bangun + integrasi |
| MOB-EXAM-03 | Mulai / lanjutkan percobaan ujian | Mahasiswa | ✅ tersedia | pages/portal/PortalExamTakingPage.tsx | — | P0 | Bangun + integrasi |
| MOB-EXAM-04 | Jawab soal dengan autosave per soal dan navigasi soal | Mahasiswa | ✅ tersedia | pages/portal/PortalExamTakingPage.tsx | — | P0 | Bangun + integrasi |
| MOB-EXAM-05 | Timer ujian berbasis server, peringatan, dan auto-submit | Mahasiswa | 🟡 sebagian | pages/portal/PortalExamTakingPage.tsx (timer server) | — | P0 | Bangun + integrasi |
| MOB-EXAM-06 | Kumpulkan ujian dengan dialog konfirmasi | Mahasiswa | ✅ tersedia | pages/portal/PortalExamTakingPage.tsx | — | P0 | Bangun + integrasi |
| MOB-EXAM-07 | Deteksi pelanggaran ujian dan penalti skor (pemetaan mobile) | Mahasiswa | 🟡 sebagian | pages/portal/PortalExamTakingPage.tsx (browser events) | — | P0 | Bangun + integrasi |
| MOB-EXAM-08 | Lihat hasil ujian dan review jawaban | Mahasiswa | ✅ tersedia | pages/portal/PortalExamResultPage.tsx | — | P1 | Bangun + integrasi |
| MOB-EXAM-09 | Unduh PDF hasil ujian | Mahasiswa | ✅ tersedia | pages/portal/PortalExamResultPage.tsx | — | P2 | Bangun + integrasi |
| MOB-EXAM-10 | Ujian publik via tautan + NIM tanpa login | Mahasiswa | ✅ tersedia | exam-public/* (route /exam/:accessToken) | — | P2 | Ditunda |
| MOB-EXAM-11 | Buka ujian dari tautan (deep link) atau pindai QR dosen | Mahasiswa | ❌ belum ada | web: halaman /exam/:accessToken | — | P2 | Label "Menunggu backend" |
| MOB-EXAM-12 | Penolakan akses ujian karena cekal keuangan/dispensasi (UTS/UAS) | Mahasiswa | 🟡 sebagian | pages/portal/PortalExamTakingPage.tsx (error) | — | P1 | Bangun + integrasi |
| MOB-EXAM-13 | Blokir ujian karena kehadiran di bawah minimum | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-EXAM-14 | Antrean jawaban offline dengan client_seq dan sinkron ulang | Mahasiswa | ❌ belum ada | none (web tanpa antrean) | — | P2 | Label "Menunggu backend" |
| MOB-EXAM-15 | Soal esai/isian singkat untuk mahasiswa | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-EXAM-16 | Proctoring kamera dan persetujuan (consent) | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-EXAM-17 | Penanganan ujian susulan | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-EXAM-18 | Daftar ujian dengan status publikasi (dosen) | Dosen | ✅ tersedia | pages/academic/ExamsPage.tsx | partial (ExamListScreen; per_page=100, abaikan meta paginasi, chip warna hardcode) | P0 | Bangun + integrasi |
| MOB-EXAM-19 | Buat ujian untuk kelas (durasi, percobaan, jadwal, jumlah soal) | Dosen | ✅ tersedia | pages/academic/ExamsPage.tsx | partial (wizard 3 langkah; ID kelas diketik manual, belum ada jadwal mulai/selesai, bobot) | P0 | Bangun + integrasi |
| MOB-EXAM-20 | Ubah pengaturan ujian | Dosen | ✅ tersedia | pages/academic/ExamDetailPage.tsx | partial (route /akademik/ujian/:id/edit) | P1 | Bangun + integrasi |
| MOB-EXAM-21 | Hapus ujian | Dosen | ✅ tersedia | pages/academic/ExamsPage.tsx | partial (tanpa dialog konfirmasi) | P2 | Bangun + integrasi |
| MOB-EXAM-22 | Konfigurasi mode seleksi soal (all/random/manual) dan pengacakan | Dosen | ✅ tersedia | pages/academic/ExamsPage.tsx | done (QuestionSelectionMode, validasi lokal exam_validation.dart) | P1 | Bangun + integrasi |
| MOB-EXAM-23 | Kelola soal ujian (tambah, ubah, hapus, tandai dipilih) | Dosen | ✅ tersedia | pages/academic/ExamDetailPage.tsx | partial (question_bank_screen berfungsi sebagai editor soal ujian; tanpa konfirmasi hapus, error mentah) | P0 | Bangun + integrasi |
| MOB-EXAM-24 | Terapkan soal dari bank soal ke ujian | Dosen | ✅ tersedia | pages/academic/ExamDetailPage.tsx (Terapkan) | — | P1 | Bangun + integrasi |
| MOB-EXAM-25 | Kelola bank soal per mata kuliah | Dosen | ✅ tersedia | pages/academic/QuestionBankPage.tsx (nama dari STATUS; verifikasi) | partial/unclear (STATUS menyebut UI bank di Flutter; catatan C9 hanya editor soal ujian) | P1 | Bangun + integrasi |
| MOB-EXAM-26 | Validasi dan publikasi ujian (Distribusi Soal) | Dosen | ✅ tersedia | pages/academic/ExamDetailPage.tsx | partial (langkah 3 wizard; izin exams.publish belum dicek di PermissionGate) | P0 | Bangun + integrasi |
| MOB-EXAM-27 | Rentang nilai huruf dan bobot ujian | Dosen | ✅ tersedia | pages/academic/ExamDetailPage.tsx | — | P2 | Bangun + integrasi |
| MOB-EXAM-28 | Buat dan bagikan tautan akses ujian + QR (regenerasi) | Dosen | ✅ tersedia | pages/academic/ExamDetailPage.tsx | — | P1 | Bangun + integrasi |
| MOB-EXAM-29 | Pantau peserta ujian secara langsung dengan peringatan pelanggaran | Dosen | ✅ tersedia | pages/academic/ExamDetailPage.tsx | — | P1 | Bangun + integrasi |
| MOB-EXAM-30 | Linimasa pelanggaran per mahasiswa | Dosen | ✅ tersedia | pages/academic/ExamRecapPage.tsx (modal) | — | P2 | Bangun + integrasi |
| MOB-EXAM-31 | Rekap nilai ujian dengan pencarian, filter status, dan ekspor CSV/PDF | Dosen | ✅ tersedia | pages/academic/ExamRecapPage.tsx | — | P1 | Bangun + integrasi |
| MOB-EXAM-32 | Rumus skor ujian (Raw, Final, Grade, Weighted) | Mahasiswa, Dosen | ✅ tersedia | pages/portal/PortalExamResultPage.tsx; ExamRecapPage.tsx | — | P1 | Bangun + integrasi |
| MOB-EXAM-33 | Unduh naskah ujian PDF (opsional dengan kunci jawaban) | Dosen | ✅ tersedia | pages/academic/ExamDetailPage.tsx | — | P3 | Bangun + integrasi |
| MOB-EXAM-34 | Pratinjau ujian sebagai mahasiswa (lokal) | Dosen | ✅ tersedia | none | done (ExamPreviewScreen; Navigator.push di luar go_router) | P3 | Bangun + integrasi |
| MOB-EXAM-35 | Rekam jawaban/kumpul atas nama mahasiswa (mode pengawas) | Dosen | ✅ tersedia | none | — | P3 | Ditunda |
| MOB-EXAM-36 | Keputusan integritas: batalkan pelanggaran, perpanjang waktu, buka kembali percobaan | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-EXAM-37 | Penilaian manual esai (antrean penilaian) dan rilis hasil | Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-EXAM-38 | Kirim skor ujian ke komponen nilai kelas | Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-EXAM-39 | Generator soal berbasis AI (cbt.ai) | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-EXAM-40 | Penentuan pengawas, berita acara, dan rekap kehadiran ujian | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-EXAM-41 | Dosen penguji/pengawas ujian PMB (Penilaian Ujian) | Dosen | 🟡 sebagian | modul PMB staf web | — | P3 | Ditunda |
| MOB-GRADE-01 | Lihat daftar nilai kelas (dosen) | Dosen | ✅ tersedia | pages/academic/GradesPage.tsx | — | P0 | Bangun + integrasi |
| MOB-GRADE-02 | Input / ubah nilai akhir mahasiswa dengan konversi huruf otomatis | Dosen | ✅ tersedia | pages/academic/GradesPage.tsx (GradeFormModal) | — | P0 | Bangun + integrasi |
| MOB-GRADE-03 | Input nilai massal per kelas (berurutan di klien) | Dosen | 🟡 sebagian | pages/academic/GradesPage.tsx (satu per satu) | — | P1 | Bangun + integrasi |
| MOB-GRADE-04 | Impor nilai dari Excel | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRADE-05 | Susun komponen nilai dan bobot kelas | Dosen | ❌ belum ada | none | — | P1 | Label "Menunggu backend" |
| MOB-GRADE-06 | Lembar nilai per komponen (simpan batch dengan versi) | Dosen | ❌ belum ada | none | — | P1 | Label "Menunggu backend" |
| MOB-GRADE-07 | Validasi/tarik skor LMS dan CBT ke lembar nilai | Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-GRADE-08 | Ajukan / finalisasi lembar nilai untuk publikasi | Dosen | ❌ belum ada | none | — | P1 | Label "Menunggu backend" |
| MOB-GRADE-09 | Cetak / ekspor daftar nilai kelas | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRADE-10 | Ajukan revisi nilai setelah publikasi (dosen) | Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-GRADE-11 | Ajukan buka kembali lembar nilai | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRADE-12 | Keberatan / remedial nilai oleh mahasiswa | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRADE-13 | Lihat RPS kelas yang disahkan (mahasiswa) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRADE-14 | Susun dan ajukan RPS (dosen), termasuk draf AI | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRADE-15 | CPMK, pemetaan CPL, dan pemetaan komponen nilai ke CPMK | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRADE-16 | Capaian CPMK kelas dan realisasi RPS (dosen) | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRADE-17 | Capaian CPL pribadi (mahasiswa) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRADE-18 | Capaian CPL mahasiswa bimbingan (dosen wali) | Dosen PA | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-EDOM-01 | Daftar evaluasi dosen wajib (EDOM) periode berjalan | Mahasiswa | ❌ belum ada | mock (pages/portal/PortalLecturerEvaluationPage.tsx; tidak berrute) | — | P1 | Label "Menunggu backend" |
| MOB-EDOM-02 | Isi dan kirim formulir EDOM untuk satu dosen/kelas | Mahasiswa | ❌ belum ada | mock (PortalLecturerEvaluationPage.tsx) | — | P1 | Label "Menunggu backend" |
| MOB-EDOM-03 | Draf jawaban EDOM anonim tersimpan di perangkat | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-EDOM-04 | Pemberitahuan privasi dan anonimitas EDOM | Mahasiswa | ❌ belum ada | none | — | P1 | Label "Menunggu backend" |
| MOB-EDOM-05 | Syarat EDOM sebelum melihat nilai (grade gate) | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-EDOM-06 | Laporan EDOM saya (dosen) | Dosen | ❌ belum ada | none (tanpa halaman) | — | P2 | Label "Menunggu backend" |
| MOB-EDOM-07 | Ekspor PDF laporan EDOM pribadi (dosen) | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-EDOM-08 | Survei dan kuesioner umum | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-FIN-01 | Ringkasan keuangan mahasiswa (beranda Keuangan) | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (PortalFinancePage, stat cards) | — | P0 | Bangun + integrasi |
| MOB-FIN-02 | Daftar tagihan saya (Tagihan Saya) dengan filter status | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (tab Tagihan) | — | P0 | Bangun + integrasi |
| MOB-FIN-03 | Detail tagihan: rincian komponen, termin, alokasi pembayaran, denda, transaksi tertunda | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (halaman /portal/keuangan/tagihan/:id) | — | P0 | Bangun + integrasi |
| MOB-FIN-04 | Unduh PDF tagihan dengan tautan pembayaran | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-FIN-05 | Riwayat/timeline perubahan tagihan dan koreksi | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-FIN-06 | Ajukan sengketa/keberatan tagihan | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-FIN-07 | Bayar tagihan/termin secara online (VA, QRIS, e-wallet, gerai) via Xendit hosted checkout | Mahasiswa | 🟡 sebagian | keu/pages/portal/PortalFinancePages.tsx (tombol Bayar -> Xendit redirect, Cek st | — | P1 | Bangun + integrasi |
| MOB-FIN-08 | Cek/segarkan status transaksi online | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (aksi 'Cek status' di tab Riwayat) | — | P1 | Bangun + integrasi |
| MOB-FIN-09 | Batalkan instruksi/transaksi yang belum dibayar | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (aksi Batalkan) | — | P1 | Bangun + integrasi |
| MOB-FIN-10 | Bayar via transfer bank manual dengan kode unik (instruksi pembayaran) | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (dialog Transfer manual) | — | P0 | Bangun + integrasi |
| MOB-FIN-11 | Unggah bukti transfer dan lacak verifikasi (termasuk unggah ulang setelah ditolak) | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (form Unggah bukti) | — | P0 | Bangun + integrasi |
| MOB-FIN-12 | Riwayat transaksi pembayaran | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (tab Riwayat pembayaran) | — | P0 | Bangun + integrasi |
| MOB-FIN-13 | Lihat/unduh kuitansi pembayaran (PDF) | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (aksi Kuitansi) | — | P1 | Bangun + integrasi |
| MOB-FIN-14 | Kartu piutang / rekening koran mahasiswa (statement) | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (tab Kartu piutang) | — | P1 | Bangun + integrasi |
| MOB-FIN-15 | Bayar tagihan/termin dengan saldo deposit | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (dialog Pakai deposit) | — | P2 | Bangun + integrasi |
| MOB-FIN-16 | Saldo deposit, mutasi, dan status pengembalian dana | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (tab Deposit & refund) | — | P1 | Bangun + integrasi |
| MOB-FIN-17 | Top-up/titip deposit | Mahasiswa | 🟡 sebagian | Tidak ada UI top-up eksplisit di PortalFinancePages (API tersedia) | — | P3 | Bangun + integrasi |
| MOB-FIN-18 | Ajukan pengembalian dana dari deposit (refund) | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (tab Deposit & refund) | — | P2 | Bangun + integrasi |
| MOB-FIN-19 | Gunakan kode voucher pada tagihan belum lunas | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (dialog Voucher di detail tagihan) | — | P2 | Bangun + integrasi |
| MOB-FIN-20 | Ajukan cicilan (skema standar atau khusus) | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (dialog Cicilan; plan saja) | — | P1 | Bangun + integrasi |
| MOB-FIN-21 | Jadwal dan status termin cicilan | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (detail tagihan) | — | P1 | Bangun + integrasi |
| MOB-FIN-22 | Daftar pengajuan cicilan saya dan pembatalannya | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-FIN-23 | Pembayaran sebagian (partial) dan kelebihan bayar menjadi deposit | Mahasiswa | 🟡 sebagian | keu/pages/portal/PortalFinancePages.tsx (alur manual) | — | P2 | Bangun + integrasi |
| MOB-FIN-24 | Lihat beasiswa, bantuan, dan potongan aktif/dicabut | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (tab Beasiswa/potongan) | — | P2 | Bangun + integrasi |
| MOB-FIN-25 | Telusuri beasiswa terbuka dan ajukan beasiswa | Mahasiswa | ❌ belum ada | mock (PortalScholarshipPage.tsx, data contoh, tidak dirutekan) | — | P3 | Ditunda |
| MOB-FIN-26 | Transparansi denda keterlambatan (tagihan denda otomatis) | Mahasiswa | 🟡 sebagian | keu/pages/portal/PortalFinancePages.tsx (denda pada detail/ringkasan) | — | P2 | Bangun + integrasi |
| MOB-FIN-27 | Lihat kelompok UKT dan riwayat pengajuan peninjauan UKT | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (tab UKT) | — | P2 | Bangun + integrasi |
| MOB-FIN-28 | Ajukan peninjauan/keberatan UKT dengan bukti | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (form peninjauan UKT) | — | P2 | Bangun + integrasi |
| MOB-FIN-29 | Pengajuan data ekonomi dan penetapan UKT (skoring) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-FIN-30 | Status cekal keuangan per layanan, penyebab, dan cara penyelesaian | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (kartu Cekal keuangan + chip layanan) | — | P0 | Bangun + integrasi |
| MOB-FIN-31 | Ajukan dispensasi cekal dan lacak keputusannya | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (form Ajukan dispensasi) | — | P1 | Bangun + integrasi |
| MOB-FIN-32 | Status bebas tanggungan keuangan (clearance) | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (chip Bebas tanggungan) | — | P1 | Bangun + integrasi |
| MOB-FIN-33 | Unduh surat keterangan bebas tanggungan keuangan (PDF) | Mahasiswa | ❌ belum ada | none | — | P3 | Label "Menunggu backend" |
| MOB-FIN-34 | Notifikasi keuangan dalam aplikasi (tagihan terbit, pembayaran, bukti ditolak, cekal, dll.) | Mahasiswa | 🟡 sebagian | PortalNotificationsPage (portal umum) | — | P1 | Bangun + integrasi |
| MOB-FIN-35 | Tagihan SKS otomatis setelah KRS disetujui dan penyesuaian tambah/drop | Mahasiswa, Dosen PA | 🟡 sebagian | keu/pages/portal/PortalFinancePages.tsx (muncul di daftar tagihan) | — | P2 | Bangun + integrasi |
| MOB-FIN-36 | Biaya cuti: tagihan cuti menggantikan tagihan registrasi | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-FIN-37 | Dosen PA melihat status cekal keuangan mahasiswa bimbingan (tanpa nominal) | Dosen PA, Dosen | ❌ belum ada | none | — | P1 | Label "Menunggu backend" |
| MOB-FIN-38 | Dosen PA mengajukan dispensasi keuangan untuk mahasiswa bimbingan | Dosen PA | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-FIN-39 | Ringkasan eskalasi tunggakan dan risiko pembayaran advisee untuk dosen PA (informasi saja) | Dosen PA | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-FIN-40 | Klaim dana masuk tak teridentifikasi dengan bukti | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-FIN-41 | Bayar beberapa tagihan dalam satu transaksi | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-FIN-42 | Verifikasi publik kuitansi lewat QR (pemindai) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-FIN-43 | Pembayaran tunai di loket (sisi mahasiswa: riwayat dan kuitansi) | Mahasiswa | ✅ tersedia | keu/pages/portal/PortalFinancePages.tsx (muncul di riwayat) | — | P3 | Bangun + integrasi |
| MOB-SP-01 | Daftar term semester pendek yang terlihat mahasiswa | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-SP-02 | Jelajahi penawaran kelas SP beserta kelayakan (eligibility) | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-SP-03 | Pilih kelas SP (keranjang), cek bentrok/batas SKS, dan kirim KRS SP | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-SP-04 | Ringkasan SP mahasiswa (status, aturan, hitung mundur, jadwal, presensi, IPS SP) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-SP-05 | Tagihan SP dan status pembayarannya (melalui portal Keuangan) | Mahasiswa | 🟡 sebagian | keu/pages/portal/PortalFinancePages.tsx (daftar tagihan generik) | — | P2 | Bangun + integrasi |
| MOB-SP-06 | Status KRS SP 'Menunggu pembayaran' dengan tautan ke Tagihan | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-SP-07 | Refund/penyesuaian ke deposit bila kelas SP dibatalkan setelah bayar | Mahasiswa | 🟡 sebagian | keu/pages/portal/PortalFinancePages.tsx (alur refund generik) | — | P3 | Ditunda |
| MOB-SP-08 | Jadwal, presensi, ujian, dan nilai kelas SP untuk mahasiswa | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-SP-09 | Dosen: daftar kelas SP yang diampu | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-SRV-01 | Lihat daftar pengajuan saya | Mahasiswa | ✅ tersedia | pages/portal/PortalRequestsPage.tsx (nyata, route ROUTES.portal.pengajuan) | — | P0 | Bangun + integrasi |
| MOB-SRV-02 | Lihat detail pengajuan, tahap persetujuan, dan riwayat | Mahasiswa | ✅ tersedia | pages/portal/PortalRequestsPage.tsx (nyata, route ROUTES.portal.pengajuan) | — | P0 | Bangun + integrasi |
| MOB-SRV-03 | Ajukan cuti akademik | Mahasiswa | ✅ tersedia | pages/portal/PortalRequestsPage.tsx (nyata); PortalLeaveRequestPage.tsx = mock t | — | P0 | Bangun + integrasi |
| MOB-SRV-04 | Ajukan aktif kembali setelah cuti | Mahasiswa | ✅ tersedia | pages/portal/PortalRequestsPage.tsx (nyata, route ROUTES.portal.pengajuan) | — | P1 | Bangun + integrasi |
| MOB-SRV-05 | Ajukan perubahan data pribadi | Mahasiswa | ✅ tersedia | pages/portal/PortalRequestsPage.tsx (nyata, route ROUTES.portal.pengajuan) | — | P1 | Bangun + integrasi |
| MOB-SRV-06 | Ajukan surat keterangan (aktif kuliah, berkelakuan baik, rekomendasi, lainnya) | Mahasiswa | ✅ tersedia | pages/portal/PortalRequestsPage.tsx (nyata, route ROUTES.portal.pengajuan); Port | — | P0 | Bangun + integrasi |
| MOB-SRV-07 | Unduh PDF surat yang sudah disetujui | Mahasiswa | ✅ tersedia | pages/portal/PortalRequestsPage.tsx (nyata) | — | P0 | Bangun + integrasi |
| MOB-SRV-08 | Simpan draft, ubah draft, dan kirim draft pengajuan | Mahasiswa | ✅ tersedia | PUT/draft-edit tidak ada UI web ("no web UI") | — | P1 | Bangun + integrasi |
| MOB-SRV-09 | Batalkan pengajuan | Mahasiswa | ✅ tersedia | pages/portal/PortalRequestsPage.tsx (nyata, route ROUTES.portal.pengajuan) | — | P1 | Bangun + integrasi |
| MOB-SRV-10 | Unggah lampiran pengajuan (kamera/berkas) | Mahasiswa | ✅ tersedia | pages/portal/PortalRequestsPage.tsx (cek apakah ada picker lampiran; tidak diver | — | P2 | Bangun + integrasi |
| MOB-SRV-11 | Ajukan ulang pengajuan yang dikembalikan (returned) | Mahasiswa | 🟡 sebagian | none (tidak diverifikasi di PortalRequestsPage) | — | P2 | Label "Menunggu backend" |
| MOB-SRV-12 | Notifikasi perubahan status pengajuan | Mahasiswa | 🟡 sebagian | pages/portal/PortalNotificationsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-SRV-13 | Dosen/PA: setujui, tolak, kembalikan, atau delegasikan langkah persetujuan | Dosen, Dosen PA | 🟡 sebagian | none untuk dosen (portal staff admin saja, "no web page") | — | P2 | Label "Menunggu backend" |
| MOB-SRV-14 | Dosen wali: beri rekomendasi atas pengajuan cuti anak bimbingan | Dosen PA | ❌ belum ada | none | — | P3 | Label "Menunggu backend" |
| MOB-SRV-15 | Kirim pengaduan / tiket layanan dan lacak | Mahasiswa, Dosen | ❌ belum ada | mock (one/pages/Support.tsx) | — | P3 | Ditunda |
| MOB-SRV-16 | Katalog layanan mahasiswa (KTM, IT, konseling, kesehatan, asrama) | Mahasiswa | ❌ belum ada | mock (one/pages/Support.tsx) | — | P3 | Ditunda |
| MOB-SRV-17 | Verifikasi keaslian dokumen (kode/QR) - pemindai di app | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KMHS-01 | Lihat poin kemahasiswaan saya (total, per kategori, riwayat) | Mahasiswa | ❌ belum ada | none (tidak ada halaman) | — | P2 | Label "Menunggu backend" |
| MOB-KMHS-02 | Ajukan kegiatan/prestasi dengan bukti | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-KMHS-03 | Lacak pengajuan kegiatan dan umpan balik petugas | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-KMHS-04 | Banding keputusan poin / pengajuan ditolak | Mahasiswa | ❌ belum ada | none | — | P3 | Label "Menunggu backend" |
| MOB-KMHS-05 | Jelajahi dan klaim kegiatan terdaftar | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KMHS-06 | Lihat kasus disiplin/sanksi saya, ajukan pembelaan dan keberatan | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-KMHS-07 | Laporkan pelanggaran (mahasiswa/dosen) | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KMHS-08 | Dosen wali: lihat poin dan pengajuan kegiatan anak bimbingan | Dosen PA, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KMHS-09 | Dosen wali: ringkasan sanksi final anak bimbingan | Dosen PA | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KMHS-10 | Jelajahi program beasiswa yang cocok | Mahasiswa | ❌ belum ada | pages/portal/PortalScholarshipPage.tsx (mock, tidak ter-route) | — | P2 | Label "Menunggu backend" |
| MOB-KMHS-11 | Ajukan beasiswa dan lacak seleksi | Mahasiswa | ❌ belum ada | PortalScholarshipPage.tsx mock | — | P2 | Label "Menunggu backend" |
| MOB-KMHS-12 | Terima/tolak penawaran beasiswa dan lihat evaluasi | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KMHS-13 | Beasiswa/potongan biaya saya (read-only) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KMHS-14 | Organisasi dan kegiatan mahasiswa (proposal, dana, presensi, LPJ) | Mahasiswa | ❌ belum ada | mock (one/pages/Campus.tsx) | — | P3 | Ditunda |
| MOB-KMHS-15 | Tugas akhir/skripsi dan bimbingan (mahasiswa dan dosen pembimbing) | Mahasiswa, Dosen | ❌ belum ada | PortalThesisAdvisingPage.tsx mock tidak ter-route | — | P2 | Ditunda |
| MOB-KMHS-16 | Magang/MBKM: apply, logbook, konversi; umpan balik pembimbing | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KMHS-17 | Pinjaman buku dan denda saya | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KMHS-18 | Pengaturan akses wali/orang tua dan persetujuan mahasiswa | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-KMHS-19 | Notifikasi kemahasiswaan (pengajuan, disiplin, beasiswa) | Mahasiswa, Dosen PA | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRAD-01 | Lihat syarat dan periode yudisium | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P2 | Label "Menunggu backend" |
| MOB-GRAD-02 | Lihat checklist kelayakan yudisium | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P1 | Label "Menunggu backend" |
| MOB-GRAD-03 | Minta pengecekan ulang kelayakan | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P3 | Ditunda |
| MOB-GRAD-04 | Daftar yudisium dan unggah dokumen persyaratan | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P1 | Label "Menunggu backend" |
| MOB-GRAD-05 | Perbaiki pendaftaran yudisium yang dikembalikan/ditolak | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P2 | Label "Menunggu backend" |
| MOB-GRAD-06 | Lihat hasil kelulusan (keputusan, predikat, tanggal) | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P2 | Label "Menunggu backend" |
| MOB-GRAD-07 | Lihat blokir yudisium (keuangan, perpustakaan, kemahasiswaan) | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P2 | Label "Menunggu backend" |
| MOB-GRAD-08 | Daftar wisuda | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P2 | Label "Menunggu backend" |
| MOB-GRAD-09 | Konfirmasi kehadiran wisuda dan kartu peserta QR | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P2 | Label "Menunggu backend" |
| MOB-GRAD-10 | Bayar biaya wisuda | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P3 | Ditunda |
| MOB-GRAD-11 | Tinjau data buku wisuda (masa koreksi) | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P3 | Ditunda |
| MOB-GRAD-12 | Unduh dokumen kelulusan (SK, transkrip, ijazah, SKL) | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P2 | Label "Menunggu backend" |
| MOB-GRAD-13 | SKPI: lihat dan ajukan capaian tambahan | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P3 | Ditunda |
| MOB-GRAD-14 | SKPI: terjemahan Inggris dan pilih capaian ditampilkan | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P3 | Ditunda |
| MOB-GRAD-15 | SKPI: pratinjau draft, konfirmasi data, unduh SKPI terbit | Mahasiswa | ❌ belum ada | PortalGraduationPage.tsx (mock/static, tidak ter-route) | — | P3 | Ditunda |
| MOB-GRAD-16 | Kaprodi/Dekan: setujui draf SKPI | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRAD-17 | Dosen PA/pembimbing: lihat kelayakan dan status yudisium mahasiswa bimbingan | Dosen, Dosen PA | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRAD-18 | Profil alumni (swalayan karier/pekerjaan) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRAD-19 | Isi tracer study (tautan token atau portal alumni) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRAD-20 | Profil karier (CV, keahlian, pengalaman) dan visibilitas | Mahasiswa | ❌ belum ada | none (mock one/pages/LibraryCareer.tsx) | — | P3 | Ditunda |
| MOB-GRAD-21 | Kelola CV/dokumen dan lihat log akses CV | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRAD-22 | Telusuri lowongan kerja | Mahasiswa | ❌ belum ada | mock (one/pages/LibraryCareer.tsx) | — | P3 | Ditunda |
| MOB-GRAD-23 | Lamar lowongan dan lacak lamaran saya | Mahasiswa | ❌ belum ada | mock (one/pages/LibraryCareer.tsx) | — | P3 | Ditunda |
| MOB-GRAD-24 | Persetujuan rekomendasi lulusan (opt-in alumni) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-GRAD-25 | Notifikasi yudisium, wisuda, SKPI, tracer, karier | Mahasiswa | ❌ belum ada | none | — | P2 | Ditunda |
| MOB-THES-01 | Lihat skripsi/tugas akhir saya (judul, jenis, status) | Mahasiswa | ❌ belum ada | mock (pages/portal/PortalThesisAdvisingPage.tsx — data mock, tidak terhubung API | — | P2 | Label "Menunggu backend" |
| MOB-THES-02 | Ajukan judul/proposal skripsi | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-THES-03 | Lihat pembimbing dan jadwal tahapan tugas akhir | Mahasiswa, Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-THES-04 | Catat bimbingan skripsi (logbook) dan ajukan sesi bimbingan | Mahasiswa | ❌ belum ada | mock (pages/portal/PortalThesisAdvisingPage.tsx, 3 dari 8 sesi mock) | — | P2 | Label "Menunggu backend" |
| MOB-THES-05 | Daftar mahasiswa bimbingan TA dan verifikasi logbook bimbingan | Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-THES-06 | Pendaftaran seminar proposal dan sidang skripsi | Mahasiswa | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-THES-07 | Penilaian seminar/sidang, revisi, dan berita acara oleh dosen | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-THES-08 | Lihat data magang/PKL/KKN saya | Mahasiswa | ❌ belum ada | none | — | P3 | Label "Menunggu backend" |
| MOB-THES-09 | Pendaftaran dan penempatan magang/PKL/KKN | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-THES-10 | Logbook, presensi, laporan, dan sertifikat magang | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-THES-11 | Bimbing dan nilai magang (dosen pembimbing) | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-THES-12 | Katalog dan pendaftaran program MBKM | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-THES-13 | Logbook MBKM mahasiswa dan peninjauan dosen supervisor | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-THES-14 | Konversi mata kuliah MBKM ke SKS | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-THES-15 | Pertukaran pelajar antar program studi | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LIB-01 | Cari katalog perpustakaan | Mahasiswa, Dosen | 🟡 sebagian | mock (one/pages/LibraryCareer.tsx — static); admin pages/library | — | P3 | Label "Menunggu backend" |
| MOB-LIB-02 | Pinjaman aktif dan riwayat peminjaman saya | Mahasiswa, Dosen | ❌ belum ada | mock (one/pages/LibraryCareer.tsx tab borrowed/history) | — | P3 | Label "Menunggu backend" |
| MOB-LIB-03 | Perpanjang pinjaman dan reservasi buku | Mahasiswa, Dosen | ❌ belum ada | mock (one/ LibraryCareer borrow/reserve, state lokal) | — | P3 | Ditunda |
| MOB-LIB-04 | Denda perpustakaan | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LIB-05 | Kartu anggota digital dan surat bebas pustaka | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-LIB-06 | Perpustakaan digital, e-journal, dan integrasi SLiMS | Mahasiswa, Dosen | ❌ belum ada | mock (one/pages/LibraryCareer.tsx) | — | P3 | Ditunda |
| MOB-ANN-01 | Daftar pengumuman mahasiswa (filter, cari, pin) | Mahasiswa | ✅ tersedia | pages/portal/PortalAnnouncementsPage.tsx (lengkap) | — | P0 | Bangun + integrasi |
| MOB-ANN-02 | Detail pengumuman dan tandai dibaca | Mahasiswa | ✅ tersedia | pages/portal/PortalAnnouncementsPage.tsx (modal detail) | — | P0 | Bangun + integrasi |
| MOB-ANN-03 | Unduh lampiran pengumuman | Mahasiswa | ✅ tersedia | pages/portal/PortalAnnouncementsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-ANN-04 | Pengumuman untuk dosen dan dosen PA | Dosen, Dosen PA | ❌ belum ada | none (hanya mahasiswa) | — | P1 | Label "Menunggu backend" |
| MOB-ANN-05 | Buat pengumuman kelas oleh dosen | Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-ANN-06 | Pusat notifikasi (daftar, filter belum dibaca) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | pages/portal/PortalNotificationsPage.tsx (mahasiswa); dosen: tidak ada halaman | — | P0 | Bangun + integrasi |
| MOB-ANN-07 | Badge jumlah notifikasi belum dibaca | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | pages/portal/PortalNotificationsPage.tsx / topbar | — | P0 | Bangun + integrasi |
| MOB-ANN-08 | Tandai notifikasi dibaca (satu dan semua) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | pages/portal/PortalNotificationsPage.tsx | — | P0 | Bangun + integrasi |
| MOB-ANN-09 | Deep link notifikasi ke layar aplikasi | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | pages/portal/PortalNotificationsPage.tsx (navigasi ke link web) | — | P1 | Bangun + integrasi |
| MOB-ANN-10 | Notifikasi push (FCM) per jenis dan deep link | Mahasiswa, Dosen, Dosen PA | ❌ belum ada | none | — | P1 | Label "Menunggu backend" |
| MOB-ANN-11 | Preferensi saluran notifikasi | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | admin/settings (NotificationsPage); portal mahasiswa: tidak ada | — | P2 | Bangun + integrasi |
| MOB-ANN-12 | Notifikasi keuangan mahasiswa (tagihan, bayar, cekal, UKT, refund) | Mahasiswa | 🟡 sebagian | pages/portal/PortalNotificationsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-ANN-13 | Notifikasi akademik mahasiswa (KRS, tugas, nilai tugas) | Mahasiswa | 🟡 sebagian | pages/portal/PortalNotificationsPage.tsx | — | P1 | Bangun + integrasi |
| MOB-ANN-14 | Notifikasi dosen dan PA (permintaan persetujuan KRS, langkah persetujuan) | Dosen, Dosen PA | 🟡 sebagian | none (tidak ada halaman dosen) | — | P1 | Bangun + integrasi |
| MOB-ANN-15 | Notifikasi HR untuk dosen (keputusan pengajuan) | Dosen, Dosen PA | 🟡 sebagian | none | — | P2 | Bangun + integrasi |
| MOB-ANN-16 | Notifikasi kemahasiswaan, kelulusan, beasiswa, karier, dan SP | Mahasiswa, Dosen PA | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-ANN-17 | Ringkasan harian PA untuk mahasiswa bimbingan tercekal | Dosen PA, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-ANN-18 | Pengumuman massal via email/SMS/WhatsApp | Mahasiswa | 🟡 sebagian | none | — | P3 | Ditunda |
| MOB-ANN-19 | Pusat pesan dengan staf PMB dan notifikasi pendaftar | Mahasiswa, Dosen | ❌ belum ada | frontend/src/pmb (portal pendaftar) | — | P3 | Ditunda |
| MOB-HR-01 | Profil dosen mandiri (lihat) | Dosen | ✅ tersedia | pages/lecturer/LecturerProfilePage.tsx (lengkap) | — | P1 | Bangun + integrasi |
| MOB-HR-02 | Ubah nomor telepon dosen | Dosen | ✅ tersedia | pages/lecturer/LecturerProfilePage.tsx | — | P2 | Bangun + integrasi |
| MOB-HR-03 | Profil kepegawaian saya (Profil SDM) dan atribut dosen | Dosen, Dosen PA | 🟡 sebagian | none (backend only) | — | P1 | Bangun + integrasi |
| MOB-HR-04 | Saldo cuti tahunan | Dosen, Dosen PA | 🟡 sebagian | none | — | P1 | Bangun + integrasi |
| MOB-HR-05 | Ajukan cuti/izin | Dosen, Dosen PA | ✅ tersedia | none (backend only) | — | P1 | Bangun + integrasi |
| MOB-HR-06 | Daftar dan status pengajuan cuti saya | Dosen, Dosen PA | ✅ tersedia | none | — | P1 | Bangun + integrasi |
| MOB-HR-07 | Kirim draf cuti | Dosen, Dosen PA | ✅ tersedia | none | — | P2 | Bangun + integrasi |
| MOB-HR-08 | Batalkan pengajuan cuti | Dosen, Dosen PA | ✅ tersedia | none | — | P1 | Bangun + integrasi |
| MOB-HR-09 | Revisi dan ajukan ulang pengajuan yang dikembalikan (cuti/HR) | Dosen, Dosen PA | 🟡 sebagian | none | — | P2 | Label "Menunggu backend" |
| MOB-HR-10 | Pengajuan perubahan data pribadi (data_change) | Dosen, Dosen PA | ✅ tersedia | none | — | P2 | Bangun + integrasi |
| MOB-HR-11 | Surat keterangan kerja/aktif mengajar (permintaan dokumen) | Dosen, Dosen PA | 🟡 sebagian | none | — | P3 | Bangun + integrasi |
| MOB-HR-12 | Pengajuan kenaikan jabatan akademik / pangkat | Dosen, Dosen PA | 🟡 sebagian | none | — | P3 | Ditunda |
| MOB-HR-13 | Pengajuan mutasi/perubahan posisi | Dosen, Dosen PA | 🟡 sebagian | none | — | P3 | Ditunda |
| MOB-HR-14 | Daftar dan status pengajuan HR saya | Dosen, Dosen PA | ✅ tersedia | none | — | P1 | Bangun + integrasi |
| MOB-HR-15 | Timeline persetujuan pengajuan saya | Dosen, Dosen PA | 🟡 sebagian | none | — | P2 | Bangun + integrasi |
| MOB-HR-16 | Batalkan pengajuan HR | Dosen, Dosen PA | ✅ tersedia | none | — | P2 | Bangun + integrasi |
| MOB-HR-17 | Opsi/lookup form HR | Dosen, Dosen PA | 🟡 sebagian | none | — | P2 | Bangun + integrasi |
| MOB-HR-18 | Kehadiran kerja saya (presensi pegawai) | Dosen | ❌ belum ada | none | — | P2 | Label "Menunggu backend" |
| MOB-HR-19 | Ajukan koreksi presensi | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-HR-20 | Check-in/out presensi via aplikasi (GPS, geofence, foto) | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-HR-21 | Riwayat pendidikan dosen dan unggah ijazah | Dosen | ❌ belum ada | none | — | P3 | Label "Menunggu backend" |
| MOB-HR-22 | Dokumen kepegawaian: unggah, verifikasi, kedaluwarsa, kelengkapan | Dosen | ❌ belum ada | none | — | P3 | Label "Menunggu backend" |
| MOB-HR-23 | Riwayat karier (jabatan, pangkat, jabatan akademik) | Dosen | ❌ belum ada | none | — | P3 | Label "Menunggu backend" |
| MOB-HR-24 | Pelatihan dan sertifikasi (termasuk serdos); catat pelatihan eksternal | Dosen | ❌ belum ada | none | — | P3 | Label "Menunggu backend" |
| MOB-HR-25 | Penilaian kinerja: penilaian diri dan hasil | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-HR-26 | Catat aktivitas penelitian, pengabdian (PkM), dan publikasi | Dosen | ❌ belum ada | none | — | P3 | Label "Menunggu backend" |
| MOB-HR-27 | Hibah penelitian/PkM internal (Litabmas) | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-HR-28 | Sinkronisasi data dosen dengan SISTER | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-HR-29 | Beban mengajar dan riwayat mengajar dosen | Dosen | 🟡 sebagian | none | — | P2 | Bangun + integrasi |
| MOB-HR-30 | Laporan BKD per semester | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-HR-31 | Slip gaji dosen | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-HR-32 | Lembur dan perjalanan dinas | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-HR-33 | Surat peringatan (SP1/SP2/SP3) saya | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-HR-34 | Remunerasi, honor/vakasi, PAK | Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-HR-35 | Setujui, tolak, kembalikan, atau delegasikan pengajuan HR bawahan | Dosen, Dosen PA | 🟡 sebagian | pages/approvals/* (admin, butuh approval_requests.read) | — | P3 | Ditunda |
| MOB-OTH-01 | Daftar sesi ujian PMB yang ditugaskan kepada saya (penguji/pengawas) | Dosen | ✅ tersedia | pmb/pages/admin/ExamPages.tsx (ScoringPage ADM-53) | — | P2 | Bangun + integrasi |
| MOB-OTH-02 | Presensi peserta ujian PMB (pengawas/penguji) | Dosen | ✅ tersedia | pmb/pages/admin/ExamPages.tsx (ExamSessionPage) | — | P2 | Bangun + integrasi |
| MOB-OTH-03 | Input nilai seleksi PMB (nilai mentah/rubrik) oleh penguji | Dosen | ✅ tersedia | pmb/pages/admin/ExamPages.tsx (ScoringPage ADM-53) | — | P2 | Bangun + integrasi |
| MOB-OTH-04 | Lihat pengajuan persetujuan saya dan statusnya | Mahasiswa, Dosen, Dosen PA | 🟡 sebagian | pages/approvals/* (admin) | — | P2 | Bangun + integrasi |
| MOB-OTH-05 | Bertindak atas langkah persetujuan yang ditugaskan (approve/reject/return/delegate) | Dosen, Dosen PA | 🟡 sebagian | pages/approvals/ApprovalRequestDetailPage.tsx (admin) | — | P3 | Ditunda |
| MOB-OTH-06 | Unggah dan unduh berkas (infrastruktur) | Mahasiswa, Dosen, Dosen PA | ✅ tersedia | komponen unggah di pages/portal/* | — | P1 | Bangun + integrasi |
| MOB-OTH-07 | Portal wali/orang tua (hanya baca) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-OTH-08 | Asisten AI / chatbot kampus | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-OTH-09 | Modul kesehatan: UKOM dan kepaniteraan klinik | Mahasiswa, Dosen | ❌ belum ada | none | — | P3 | Ditunda |
| MOB-OTH-10 | Batas paket (kuota tracer dan tier fitur karier) | Mahasiswa | ❌ belum ada | none | — | P3 | Ditunda |

## 5. Rincian per domain

Rincian berikut (aturan bisnis, kriteria penerimaan/pengujian, endpoint, celah backend, dependensi, sumber) disusun oleh sintesis per kelompok domain dan diverifikasi terhadap rute backend.

## Matriks Fitur G1 - Auth, Platform Mobile, Dashboard (Civitas One Android)

Cakupan: mahasiswa, dosen, dosen PA. Kode mengalahkan dokumen; rute diverifikasi pada `app/Modules/*/Routes/api.php`. Total baris: 84.

### Auth & Akun

Domain ini mencakup login, sesi, kata sandi, profil, dan identitas peran. Backend Sanctum sudah lengkap untuk jalur dasar (login NIM/email, logout, ubah/lupa/reset sandi, sesi & perangkat). Aplikasi Flutter saat ini baru memiliki login email, lupa/reset sandi, serta layar sesi/perangkat; celah utama: login NIM, info perangkat, pemulihan sesi offline, profil, dan sandi wajib ganti.

#### MOB-AUTH-01 - Login dengan email + kata sandi (dosen / dosen PA)
- **Peran:** lecturer, advisor
- **Deskripsi:** Dosen dan dosen PA masuk dengan email dan kata sandi. Aplikasi menerima token Sanctum dari POST /login lalu memanggil GET /me untuk memuat peran dan izin. Setelah berhasil, pengguna diarahkan ke beranda sesuai peran (lihat MOB-DASH-12/13).
- **Aturan bisnis:**
  - Kode: POST /login menerima email ATAU nim + password; mengembalikan data{token,user{id,name,email,nim,must_change_password}} saja (tanpa roles; roles lewat /me).
  - Kode: batas percobaan login = setting security.max_login_attempts default 5, decay 60 detik, per identifier+IP; saat terkunci respons 429 dengan pesan 'Validasi data gagal.' dan errors.email|nim[0] berisi pesan kunci (bukan 'Terlalu banyak permintaan').
  - Kode: token Sanctum abilities ['*'], tanpa kedaluwarsa dan tanpa refresh token.
  - Dokumen (D01) menetapkan validasi klien: email wajib/format valid, sandi min 6 karakter; pesan kesalahan server tampil sebagai alert tingkat atas.
  - Kode mobile saat ini: label 'Email atau Username' tetapi validator memaksa format email; checkbox 'Ingat saya' tidak dipakai (token selalu disimpan).
- **Kriteria penerimaan/pengujian:**
  - Given email+sandi benar dan akun berperan lecturer When login Then token tersimpan di secure storage, /me dimuat, dan pengguna masuk ke beranda dosen (login_flow_test D01).
  - Given sandi salah When login Then alert 'kredensial salah' tampil dan token tidak tersimpan (login_flow_test D01).
  - Given 5 kali gagal berturut-turut When percobaan ke-6 Then pesan kunci dari errors.email[0] ditampilkan beserta HTTP 429 tanpa crash.
  - Given akun tidak punya peran/izin yang dikenal When login Then tampil layar 'Akses Ditolak', bukan beranda kosong.
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/login`, `GET /api/v1/me`
- **Celah backend:** Login hanya mengembalikan id,name,email,nim,must_change_password; tidak ada roles/permissions/profil (perlu /me + /lecturer-profile + /tenant/profile).; Token tidak pernah kedaluwarsa dan tidak ada refresh (lihat MOB-AUTH-15).
- **Status web:** pages/LoginPage.tsx (tab 'lainnya': email + password min 6)
- **Status mobile saat ini:** partial (login_screen.dart: email+sandi jalan; validator email, 'Ingat saya' tidak berfungsi, tidak kirim info perangkat, tidak kirim X-University-ID)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-03, MOB-AUTH-04, MOB-AUTH-14, MOB-PLAT-21
- **Catatan:** Dosen PA adalah peran tambahan (academic_advisor) pada akun dosen yang sama; satu login untuk keduanya.
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.1 / 2.2; docs/STATUS-IMPLEMENTASI.md#1 Fondasi Platform; #14; docs/RANCANGAN-AKUN-DOSEN.md#9; docs/BLACK-BOX-TESTING.md#1 (AUTH-04, AUTH-07); code: app/Modules/Auth/Controllers/AuthController.php; mobile/lib/features/auth (C9)

#### MOB-AUTH-02 - Login mahasiswa dengan NIM + kata sandi (kode universitas)
- **Peran:** student
- **Deskripsi:** Mahasiswa masuk dengan NIM dan kata sandi. Karena NIM unik per universitas, backend membutuhkan konteks tenant: header X-University-ID, domain, atau university_code pada body login.
- **Aturan bisnis:**
  - Kode: /login menerima 'nim' (alternatif email) + password + university_code opsional; tenant dari X-University-ID -> domain host -> membership default pengguna.
  - Kode: kata sandi awal akun mahasiswa hasil seeding = NIM dan must_change_password=true (MAHASISWA §8.5/§11) sehingga ganti sandi wajib (MOB-AUTH-10).
  - Web: tab 'mahasiswa' meminta NIM; sandi hanya min(1) di klien.
  - Email mahasiswa bisa berupa placeholder (@student.<univ>.local) yang tidak dapat menerima email reset (MOB-AUTH-07).
- **Kriteria penerimaan/pengujian:**
  - Given NIM+sandi benar+university_code valid When login Then token + user (nim terisi) diterima.
  - Given NIM sama ada di dua universitas dan tanpa university_code When login Then aplikasi menampilkan kesalahan tenant yang dapat dipahami (bukan crash).
  - Given build satu-universitas When login Then university_code dikirim otomatis dari konfigurasi flavor tanpa input pengguna.
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/login`
- **Celah backend:** Tidak ada endpoint publik untuk resolusi university_code -> ULID/branding sebelum login (gap C1).; Respons login tidak menyertakan universitas.
- **Status web:** pages/LoginPage.tsx (tab mahasiswa, field NIM)
- **Status mobile saat ini:** none (aplikasi hanya mengirim {email,password}; tidak ada field NIM / university_code)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-21, MOB-AUTH-10
- **Catatan:** Keputusan tenant (build single-university vs picker) menentukan UI; lihat unresolved.
- **Sumber:** docs/RANCANGAN-AKUN-MAHASISWA.md#8.5, #11.5; docs/MULTI-TENANT-ARCHITECTURE.md; docs/BLACK-BOX-TESTING.md#1; code: app/Modules/Auth/Tests/Feature/StudentLoginTest.php; mobile login_screen.dart (C9)

#### MOB-AUTH-03 - Kirim identitas perangkat saat login (nama, platform, device_identifier)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Saat login aplikasi mengirim X-Device-Name, X-Device-Platform dan device_identifier agar sesi/perangkat yang tampil di daftar keamanan bermakna ('Pixel 7 - Android') dan deteksi perangkat baru bekerja.
- **Aturan bisnis:**
  - Kode: /login menerima device_identifier opsional dan header X-Device-Name, X-Device-Platform; /devices mengembalikan {id,device_name,device_type,platform,is_trusted,last_used_at}.
  - device_identifier harus stabil (installation ID acak yang disimpan di secure storage), bukan IMEI/Android ID (privasi).
- **Kriteria penerimaan/pengujian:**
  - Given login sukses When membuka Keamanan > Perangkat Then baris perangkat memuat nama model dan platform 'android'.
  - Given aplikasi dipasang ulang When login Then device_identifier baru dibuat dan perangkat lama tetap terdaftar sebagai sesi terpisah.
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/login (device_identifier, X-Device-Name, X-Device-Platform)`, `GET /api/v1/devices`
- **Celah backend:** Tidak ada endpoint memperbarui/mengganti nama/mempercayai perangkat; hanya GET /devices.; Tidak ada kolom/endpoint push_token (lihat MOB-PLAT-14).
- **Status web:** none
- **Status mobile saat ini:** none (mobile tidak mengirim info perangkat)
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-01, MOB-AUTH-11, package: device_info_plus
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.3 (Keamanan); code: C1 login req; C9 'no device info sent'

#### MOB-AUTH-04 - Pulihkan sesi saat aplikasi dibuka (cold start, aman offline)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Saat aplikasi dibuka, token dibaca dari secure storage dan /me dipanggil. Kegagalan jaringan TIDAK boleh menghapus token; hanya 401 yang mengakhiri sesi. Profil/peran/izin terakhir dipakai dari cache lokal saat offline.
- **Aturan bisnis:**
  - Kode mobile (bug terverifikasi C9): AuthController.build menghapus token pada SEMUA ApiFailure termasuk connectionError -> pengguna ter-logout bila membuka aplikasi offline.
  - Kode: tidak ada route splash; layar terlindungi bisa tampil dengan sesi null saat restore.
  - Dokumen D01: tidak ada refresh token; setiap 401 = logout paksa (clearSession).
  - Cache lokal berisi hanya data non-sensitif minimal (id, nama, peran, izin); token tetap hanya di secure storage.
- **Kriteria penerimaan/pengujian:**
  - Given token valid tersimpan dan perangkat offline When aplikasi dibuka Then pengguna tetap masuk dengan peran/izin cache dan banner offline tampil.
  - Given token dicabut di server (401) When /me dipanggil Then sesi dibersihkan dan layar login tampil dengan pesan 'Sesi berakhir'.
  - Given proses restore berjalan When layar pertama Then splash tampil (bukan layar terlindungi kosong).
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/me`
- **Celah backend:** -
- **Status web:** none (SPA bergantung pada cookie/localStorage; 401 membersihkan sesi)
- **Status mobile saat ini:** partial (restore ada tetapi logout saat offline)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-09, MOB-PLAT-10, MOB-PLAT-05, MOB-PLAT-33
- **Catatan:** Perbaikan bug keandalan; prioritas P0 karena mempengaruhi setiap pengguna dengan jaringan buruk.
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#11.2; code: mobile AuthController.build (C9 gap 'Offline-safe session restore'); docs/STATUS-IMPLEMENTASI.md#14

#### MOB-AUTH-05 - Keluar (logout) dan penanganan sesi dicabut (401)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Pengguna dapat keluar dari perangkat ini. Bila server mencabut token (logout dari perangkat lain / dicabut Super Admin), setiap 401 membersihkan sesi lokal dan mengarahkan ke login.
- **Aturan bisnis:**
  - Kode: POST /logout mencabut token saat ini; respons hanya pesan.
  - 401 'Tidak terautentikasi.'; 401 dari /login (sandi salah) tidak boleh mengosongkan state sesi.
  - Saat logout, hapus token, cache user, preferensi biometrik sesi, dan cache data akademik/keuangan di perangkat.
- **Kriteria penerimaan/pengujian:**
  - Given tombol Keluar ditekan When konfirmasi Then POST /logout dipanggil, data lokal sensitif dihapus, layar login tampil (login_flow_test D01).
  - Given token dicabut dari perangkat lain When request berikutnya mengembalikan 401 Then aplikasi kembali ke login tanpa loop.
  - Given logout saat offline When Keluar Then sesi lokal tetap dihapus dan permintaan revoke dicoba/ditandai tertunda.
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/logout`
- **Celah backend:** Tidak ada notifikasi realtime saat sesi dicabut; terdeteksi hanya pada request berikutnya.
- **Status web:** layout header (logout) + interceptor 401
- **Status mobile saat ini:** done (logout + clearSession pada 401; belum menghapus cache karena belum ada cache)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-04
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.3; docs/STATUS-IMPLEMENTASI.md#1; docs/BLACK-BOX-TESTING.md#1; docs/RANCANGAN-SUPER-ADMIN.md#6.2

#### MOB-AUTH-06 - Keluar dari semua perangkat
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Pengguna mencabut seluruh token miliknya dari layar Keamanan.
- **Aturan bisnis:**
  - Kode: POST /logout-all mencabut semua token termasuk token perangkat ini; konfirmasi sebelum eksekusi.
  - Dokumen (PMB/auth pattern): setelah ganti sandi disarankan revoke sesi lain (kode saat ini tidak melakukannya).
- **Kriteria penerimaan/pengujian:**
  - Given tombol 'Keluar dari semua perangkat' When dikonfirmasi Then POST /logout-all dipanggil dan aplikasi kembali ke login.
  - Given perangkat lain aktif When logout-all Then perangkat lain mendapat 401 pada request berikutnya.
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/logout-all`
- **Celah backend:** -
- **Status web:** SecuritySessionsPage.tsx
- **Status mobile saat ini:** done (security_sessions_screen)
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-11
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.3; docs/STATUS-IMPLEMENTASI.md#1; docs/pmb/11-api-dan-integrasi.md#A.2

#### MOB-AUTH-07 - Minta tautan reset kata sandi lewat email
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Layar Lupa Sandi: pengguna mengisi email dan menerima tautan reset. Pesan sukses netral (anti-enumerasi).
- **Aturan bisnis:**
  - Kode: POST /forgot-password {email}; 422 bila pengiriman gagal.
  - Dokumen: pesan netral 'Jika akun terdaftar, tautan atau kode pengaturan ulang telah dikirim.'
  - Kode: tautan email mengarah ke FRONTEND_URL/reset-password (web), bukan aplikasi.
  - Mahasiswa dengan email placeholder tidak bisa menerima email -> butuh jalur alternatif (admin reset).
- **Kriteria penerimaan/pengujian:**
  - Given email terdaftar atau tidak When dikirim Then pesan sukses identik ditampilkan.
  - Given email bukan format valid When submit Then validasi klien mencegah request.
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/forgot-password`
- **Celah backend:** Hanya berbasis email; mahasiswa dengan NIM/email placeholder tidak dapat memulihkan sandi sendiri.; Tautan menuju web; tidak ada App Link/kode OTP in-app.
- **Status web:** pages/ForgotPasswordPage.tsx
- **Status mobile saat ini:** done (forgot_password_screen)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-08, MOB-AUTH-24
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.1; docs/pmb/11 / manual-book PMB (anti-enumeration); docs/STATUS-IMPLEMENTASI.md#1; code: C1 forgot-password

#### MOB-AUTH-08 - Atur ulang kata sandi dari tautan email (deep link ke aplikasi)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Tautan reset dari email membuka aplikasi (App Link / skema civitasone://) dengan token+email, lalu pengguna mengisi sandi baru.
- **Aturan bisnis:**
  - Kode: POST /reset-password {token,email,password,password_confirmation}; TTL token 60 menit; kebijakan produksi min 12 karakter kompleks (setting), lokal min 8.
  - Dokumen D01: App Links https://app.civitasone.example/* (domain placeholder, PERLU KONFIRMASI) + custom scheme.
  - Bendera sukses setelah reset melewati go_router (catatan 'open point' di README mobile).
- **Kriteria penerimaan/pengujian:**
  - Given tautan reset valid When dibuka di perangkat dengan aplikasi Then layar Atur Ulang terbuka dengan token+email terisi.
  - Given token kedaluwarsa When submit Then pesan kesalahan jelas dan tombol 'Minta tautan baru'.
  - Given reset sukses When selesai Then arahkan ke login (tidak auto-login).
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/reset-password`
- **Celah backend:** Email reset menautkan ke FRONTEND_URL (web), bukan domain App Link; perlu endpoint/URL mobile atau halaman web yang meneruskan ke aplikasi (assetlinks.json).; Domain resmi App Link belum ditetapkan.
- **Status web:** pages/ResetPasswordPage.tsx
- **Status mobile saat ini:** partial (layar ada, token/email via query; tanpa intent filter/App Link)
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-16
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.1 / 8.8; code: C9 reset-password screen

#### MOB-AUTH-09 - Ubah kata sandi (sukarela)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Dari Pengaturan/Keamanan pengguna mengganti sandi dengan memasukkan sandi saat ini dan sandi baru + konfirmasi.
- **Aturan bisnis:**
  - Kode: POST /change-password {current_password,password(min 8),password_confirmation}; 422 errors.current_password bila salah.
  - Kode: perubahan sandi menghapus must_change_password tetapi TIDAK mencabut token lain.
  - Web: sandi baru harus berbeda dari sandi lama; produksi min 12 kompleks bila diaktifkan.
- **Kriteria penerimaan/pengujian:**
  - Given sandi lama benar dan sandi baru valid When submit Then sukses dan pengguna tetap masuk.
  - Given sandi lama salah When submit Then error pada field 'sandi saat ini'.
  - Given sandi baru sama dengan lama When submit Then ditolak (klien).
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/change-password`
- **Celah backend:** Token lain tidak dicabut setelah ganti sandi (gap C1 'revoke-on-password-change').
- **Status web:** ChangePasswordPage.tsx (/ubah-password)
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-10, MOB-AUTH-14
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#1; docs/BLACK-BOX-TESTING.md#1; code: C1 change-password; C7 'Forced password change'

#### MOB-AUTH-10 - Alur ganti sandi wajib saat login pertama (must_change_password)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Bila user.must_change_password=true (mis. akun mahasiswa seed/konversi PMB), setelah login pengguna dipaksa ke layar Ubah Sandi dan tidak dapat membuka layar lain sebelum selesai.
- **Aturan bisnis:**
  - Kode: must_change_password dikembalikan oleh /login dan /me; dibersihkan oleh POST /change-password.
  - Kode: server TIDAK memaksa flag ini pada route lain -> penegakan hanya di klien (lihat MOB-PLAT-20).
  - Web: must_change_password memaksa /ubah-password; sandi baru min 8 dan berbeda dari sandi sekarang.
  - Mahasiswa hasil konversi PMB menerima flag true dan tautan reset web (MOB-AUTH-24).
- **Kriteria penerimaan/pengujian:**
  - Given must_change_password=true When login Then layar Ubah Sandi tampil, tombol back/navigasi lain tertutup.
  - Given sandi berhasil diubah When selesai Then flag dibersihkan (re-fetch /me) dan beranda terbuka.
  - Given aplikasi dimatikan di tengah alur When dibuka lagi Then pengguna kembali ke layar Ubah Sandi.
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/change-password`, `GET /api/v1/me`
- **Celah backend:** must_change_password tidak ditegakkan server pada endpoint lain (klien dapat dilewati via API langsung).
- **Status web:** ChangePasswordPage.tsx + route guard
- **Status mobile saat ini:** none
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-09, MOB-PLAT-20
- **Sumber:** docs/RANCANGAN-AKUN-MAHASISWA.md#8.5, #11.5; docs/pmb/12-ui-ux-sitemap.md#AUT-01; code: C7 forced password change; C1 gap 'must_change_password not enforced'

#### MOB-AUTH-11 - Lihat dan cabut sesi aktif / daftar perangkat
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Layar Keamanan menampilkan sesi login (perangkat, IP, aktivitas terakhir, status) dan perangkat terdaftar; pengguna dapat mencabut satu sesi.
- **Aturan bisnis:**
  - Kode: GET /sessions -> data[{id,device{id,device_name,platform},ip_address,user_agent,last_activity_at,is_active,revoked_at,created_at}] (tidak dipaginasi); DELETE /sessions/{id} 403 bila bukan pemilik.
  - Kode: last_activity_at tidak diperbarui setiap request (catatan C1).
  - Mencabut sesi saat ini = logout lokal.
- **Kriteria penerimaan/pengujian:**
  - Given daftar sesi When dibuka Then sesi aktif diberi tanda 'Perangkat ini' dan sesi tercabut ditampilkan sebagai nonaktif.
  - Given sesi lain dicabut When konfirmasi Then DELETE /sessions/{id} dipanggil dan baris menjadi nonaktif.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/sessions`, `DELETE /api/v1/sessions/{session}`, `GET /api/v1/devices`
- **Celah backend:** last_activity_at tidak akurat; tidak ada penamaan/kepercayaan perangkat; tidak ada notifikasi 'perangkat baru' ke aplikasi (ada notifikasi legacy NewDeviceDetected di inbox).
- **Status web:** SecuritySessionsPage.tsx
- **Status mobile saat ini:** done (security_sessions_screen: sesi + perangkat + revoke + logout-all)
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-03, MOB-AUTH-06
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#1 (✅ web + mobile); docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.3; docs/RANCANGAN-SUPER-ADMIN.md#6.2; docs/BLACK-BOX-TESTING.md#1

#### MOB-AUTH-12 - Autentikasi dua faktor (2FA/MFA)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Verifikasi dua faktor saat login. Tercantum dalam rancangan sebagai MVP tahap 1 tetapi belum dibangun.
- **Aturan bisnis:**
  - STATUS (17 Okt 2026): 2FA ❌ belum dibuat; RANCANGAN-APLIKASI §10 menyebut MFA.
- **Kriteria penerimaan/pengujian:**
  - Given 2FA aktif When login Then langkah kode OTP/TOTP diminta (belum dapat diuji; backend belum ada).
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Tidak ada endpoint enrol/verify/recovery 2FA; kontrak tidak didefinisikan.
- **Status web:** none (prototype one/Account.tsx menampilkan toggle 2FA statis)
- **Status mobile saat ini:** none
- **Prioritas:** P3 | **Cakupan rekomendasi:** `defer`
- **Dependensi:** backend: modul 2FA
- **Catatan:** Tunda; biometrik lokal (MOB-PLAT-12) bukan pengganti 2FA.
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#1 'Belum dibuat'; docs/RANCANGAN-APLIKASI.md#10; docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md(tidak dirancang untuk mobile); docs/RANCANGAN-SUPER-ADMIN.md (no 2FA)

#### MOB-AUTH-13 - Proteksi login: rate limit dan kebijakan kata sandi di UI
- **Peran:** student, lecturer, advisor
- **Deskripsi:** UI menampilkan aturan sandi dan menangani 429 / lockout dengan pesan jelas serta hitung mundur bila tersedia.
- **Aturan bisnis:**
  - Kode: lockout 5 percobaan/60 detik per identifier+IP (setting security.max_login_attempts); throttle lain pada route tertentu: 429 'Terlalu banyak permintaan. Coba lagi nanti.'
  - Kebijakan sandi: min 8 (change-password), produksi min 12 kompleks (reset); pola PMB: min 8 huruf+angka (dokumen).
  - Tidak ada deteksi anomali (platform-wide ❌ doc 2026-07-19).
- **Kriteria penerimaan/pengujian:**
  - Given 429 When login Then pesan lockout tampil dan tombol Masuk dinonaktifkan sementara.
  - Given sandi baru tidak memenuhi kebijakan When submit Then error per aturan ditampilkan (server 422 errors.password).
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/login`, `POST /api/v1/change-password`, `POST /api/v1/reset-password`
- **Celah backend:** Kebijakan sandi berbeda per lingkungan; tidak ada endpoint yang mengekspos kebijakan sandi ke klien.
- **Status web:** forms (zod)
- **Status mobile saat ini:** partial (validator klien min 6; belum menangani 429 khusus)
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-10
- **Sumber:** docs/RANCANGAN-SUPER-ADMIN.md#login protection; docs/BLACK-BOX-TESTING.md#1 (AUTH-04); code: C1 conventions

#### MOB-AUTH-14 - Muat konteks peran dan izin (/me) untuk menentukan pengalaman pengguna
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Setelah login/restore aplikasi memanggil /me; roles[] dan permissions[] menentukan beranda dan menu (student / lecturer / advisor) lewat PermissionGate. Peran: student, lecturer, academic_advisor, head_of_study_program, dean.
- **Aturan bisnis:**
  - Kode: /me -> data{user{id,name,email,is_active,must_change_password,nim,...},roles[],permissions[]}; tanpa objek student/lecturer/university.
  - Pengalaman: student bila roles memuat 'student' (permission student_portal.read); lecturer bila lecturer_profile.read; advisor bila krs_advising.read/academic_advisor; satu akun dapat memiliki beberapa peran.
  - Kode: permission cocok persis; super_admin bypass hanya di server; PermissionGate mendukung daftar koma = OR.
  - Kode mobile: AuthUser.id di-cast String (rapuh), tidak ada helper isLecturer/isAdvisor.
- **Kriteria penerimaan/pengujian:**
  - Given roles=[student] When login Then hanya navigasi mahasiswa tampil.
  - Given roles=[lecturer,academic_advisor] When login Then navigasi dosen + tab Perwalian tampil.
  - Given akun non-student/non-lecturer When login Then 'Akses Ditolak' dengan opsi logout.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/me`
- **Celah backend:** /me tidak memuat profil mahasiswa/dosen, universitas, features/limits (D14: data.features); klien butuh panggilan tambahan (student/profile, lecturer-profile, tenant/profile).; Tidak ada indikator advisee untuk dosen PA (diturunkan dari role academic_advisor / students.academic_advisor_id).
- **Status web:** hooks/useIsStudent.ts; nav.ts filter permission
- **Status mobile saat ini:** partial (AuthUser + PermissionGate; tidak ada isLecturer/isAdvisor, parsing rapuh)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-06, MOB-PLAT-07, MOB-PLAT-08
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#2.4, #13.1; docs/STATUS-IMPLEMENTASI.md#14; code: C1 Roles; C7 'Role->experience'; C9 AuthUser

#### MOB-AUTH-15 - Kedaluwarsa token / refresh token dan revoke-saat-ganti-sandi
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Keamanan token mobile: kedaluwarsa absolut (usulan audit 30 hari), refresh atau login ulang, dan pencabutan token lain setelah ganti sandi.
- **Aturan bisnis:**
  - Kode: sanctum.expiration null; tidak ada refresh; change-password tidak mencabut token lain.
  - Dokumen: 13-security-audit usulan kedaluwarsa absolut 30 hari untuk mobile; PMB menggunakan 12 jam sliding (applicant guard).
- **Kriteria penerimaan/pengujian:**
  - Given token melewati masa berlaku When request Then 401 dan layar login (belum dapat diuji; backend belum ada).
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Konfigurasi expiry Sanctum, endpoint refresh/rotate (bila dipilih), revoke token lain pada change-password.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** backend: Auth module
- **Catatan:** Aplikasi sudah menangani 401 -> login (MOB-AUTH-05) sehingga siap bila backend menambah expiry.
- **Sumber:** docs/academic-learning/13-security-audit.md#2; docs/pmb/11-api-dan-integrasi.md#A.2; code: C1 gap

#### MOB-AUTH-16 - Lihat profil mahasiswa (biodata, akademik)
- **Peran:** student
- **Deskripsi:** Layar Profil menampilkan data pribadi, kontak, alamat, foto, ringkasan akademik, dosen PA, dan penanda bidang yang boleh diedit vs hanya lewat pengajuan.
- **Aturan bisnis:**
  - Kode: GET /student/profile -> {id,personal{name,nim,birth_place,birth_date,gender,gender_label},contact{email,phone},address,photo{file_id,mime_type},academic{...},editable_fields:[phone,address,photo],request_only_fields:[name,email,birth_place,tanggal_lahir,gender],pending_data_change}.
  - Permission student_portal.read; akun non-mahasiswa -> 403 'Akun ini tidak tertaut ke data mahasiswa.'
  - Foto hanya file_id; ditampilkan via GET /file-uploads/{id}/preview dengan header Bearer (tanpa URL bertanda tangan).
- **Kriteria penerimaan/pengujian:**
  - Given mahasiswa login When membuka Profil Then data sesuai respons dan foto dimuat dengan Authorization header.
  - Given pending_data_change != null When Profil Then banner 'Pengajuan perubahan data sedang ditinjau' tampil.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/profile`, `GET /api/v1/file-uploads/{fileUpload}/preview`
- **Celah backend:** Foto profil tidak memiliki URL siap pakai (hanya file_id + Bearer).
- **Status web:** pages/portal/PortalProfilePage.tsx (real API)
- **Status mobile saat ini:** none (dashboard placeholder saja)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-17, MOB-PLAT-24
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#6 (profil ✅); docs/RANCANGAN-AKUN-MAHASISWA.md#4; docs/RANCANGAN-AKUN-MAHASISWA.md#8.3; code: C2 GET /student/profile

#### MOB-AUTH-17 - Ubah nomor telepon, alamat, dan foto profil (mahasiswa)
- **Peran:** student
- **Deskripsi:** Mahasiswa memperbarui telepon, alamat, dan foto. Foto diunggah dulu lewat /file-uploads lalu photo_file_id dikirim ke PATCH /student/profile.
- **Aturan bisnis:**
  - Kode: PATCH /student/profile (student_portal.update) body sometimes: phone (string max30, regex /^\+?[0-9\s\-()]{6,30}$/), address (max500), photo_file_id (ULID).
  - Foto: jpg/jpeg/png, maks 2 MB, diunggah oleh pengguna yang sama, status file 'clean', belum terlampir di tempat lain; pesan 422 spesifik.
  - Catatan: C7 mencatat izin web student_portal.read pada PATCH; kode backend memakai student_portal.update (perhatikan, keduanya diberikan ke role student).
- **Kriteria penerimaan/pengujian:**
  - Given telepon valid When disimpan Then respons 200 'Profil berhasil diperbarui.' dan UI menampilkan nilai baru.
  - Given foto > 2 MB When dipilih Then ditolak di klien dan, bila lolos, 422 ditampilkan.
  - Given telepon tidak valid When submit Then error field dari errors.phone.
- **Status backend:** `available`
- **Endpoint:** `PATCH /api/v1/student/profile`, `POST /api/v1/file-uploads`
- **Celah backend:** -
- **Status web:** pages/portal/PortalProfilePage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-16, MOB-PLAT-24, package: image_picker, image_cropper (opsional)
- **Sumber:** code: C2 PATCH /student/profile; docs/STATUS-IMPLEMENTASI.md#6; docs/RANCANGAN-AKUN-MAHASISWA.md#4

#### MOB-AUTH-18 - Ajukan perubahan data pribadi (nama, email, tempat/tanggal lahir, gender)
- **Peran:** student
- **Deskripsi:** Bidang yang tidak bisa diedit langsung diajukan sebagai permintaan perubahan data yang melalui persetujuan; riwayat pengajuan tampil dengan status.
- **Aturan bisnis:**
  - Kode: POST /student/requests type=data_change payload {changes:{name?,email?,birth_place?,tanggal_lahir? (sebelum hari ini),gender? male|female}} minimal satu bidang; email unik antar user.
  - Status: draft|submitted|approved|rejected|cancelled; persetujuan memperbarui student + user name/email.
  - Desain D01 menyebut jenis data Alamat/Telepon/Email/Nama dan field Alasan; kode memakai daftar data_change_fields dari GET /student/requests/options.
- **Kriteria penerimaan/pengujian:**
  - Given perubahan nama diajukan When submit Then pengajuan 'Sedang Ditinjau' muncul di riwayat dan Profil menampilkan pending_data_change.
  - Given pengajuan pending When mengajukan lagi Then pesan 409/422 dari server ditampilkan.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/requests/options`, `POST /api/v1/student/requests`, `GET /api/v1/student/requests`
- **Celah backend:** Alasan perubahan tidak ada di payload kode (desain meminta 'Alasan').
- **Status web:** pages/portal (pengajuan data) real API
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-16, (row pengajuan mahasiswa di grup lain)
- **Catatan:** Layar pengajuan umum (cuti/surat) berada di grup lain; ini hanya jalur masuk dari Profil.
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.15.2; docs/RANCANGAN-AKUN-MAHASISWA.md#4; code: C2 student/requests data_change

#### MOB-AUTH-19 - Lihat profil dosen / dosen PA dan ubah nomor telepon
- **Peran:** lecturer, advisor
- **Deskripsi:** Layar Profil Saya untuk dosen: NIDN, NIP, fakultas, status kepegawaian, jabatan fungsional, pendidikan tertinggi, tanggal bergabung, akun. Dosen hanya dapat mengubah nomor telepon.
- **Aturan bisnis:**
  - Kode: GET /lecturer-profile (lecturer_profile.read) -> id,user_id,faculty_id,faculty_name,nidn,nip,name,email,phone,employment_status,functional_rank,highest_education,hired_at,is_active,has_account,account{...}.
  - PUT /lecturer-profile (lecturer_profile.update) body hanya phone (nullable, maks 30).
  - Akun belum tertaut -> 404 'Akun Anda belum tertaut ke data dosen. Hubungi Bagian SDM.'; route tanpa tenant.access.
  - lecturer.id dari profil dibutuhkan sebagai filter kelas (filter[lecturer_id]).
  - Perubahan telepon tidak disinkronkan ke employees.phone (catatan C6).
- **Kriteria penerimaan/pengujian:**
  - Given dosen tertaut When membuka Profil Then data tampil dan id disimpan untuk filter kelas.
  - Given akun tidak tertaut When membuka Profil Then pesan 404 server tampil dengan petunjuk hubungi SDM.
  - Given telepon > 30 karakter When simpan Then ditolak.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/lecturer-profile`, `PUT /api/v1/lecturer-profile`
- **Celah backend:** Resource tidak mengekspos nidk, serdos_number, academic_rank, expertise; tidak ada foto/avatar.
- **Status web:** pages/LecturerProfilePage.tsx (/profil-dosen)
- **Status mobile saat ini:** none
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-12, MOB-AUTH-14
- **Catatan:** Dosen PA memakai profil dosen yang sama; employee-only (tanpa lecturers.user_id) tidak mendapat profil.
- **Sumber:** docs/RANCANGAN-AKUN-DOSEN.md#4.5; code: C3 lecturer-profile; C7 LecturerProfilePage; docs/STATUS-IMPLEMENTASI.md#1

#### MOB-AUTH-20 - Foto profil dosen
- **Peran:** lecturer
- **Deskripsi:** Dosen mengunggah/menampilkan foto profil.
- **Aturan bisnis:**
  - Belum ada endpoint foto dosen; hanya mahasiswa memiliki photo_file_id.
- **Kriteria penerimaan/pengujian:**
  - (tidak dapat diuji; backend belum ada)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Tambahkan photo_file_id pada PUT /lecturer-profile + resource.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3 | **Cakupan rekomendasi:** `defer`
- **Dependensi:** backend: lecturer profile
- **Sumber:** code: C3 'No photo/avatar endpoint'

#### MOB-AUTH-21 - Persetujuan data pribadi dan hak subjek data (UU PDP)
- **Peran:** student, lecturer
- **Deskripsi:** Melihat data milik sendiri, persetujuan (consent), log akses CV, mencabut persetujuan, dan permintaan subjek data sesuai UU PDP.
- **Aturan bisnis:**
  - Dokumen: modul review UU PDP berprioritas Kritis (RANCANGAN-APLIKASI §21.3); privasi mandiri mahasiswa: lihat data sendiri, consent, log akses CV, cabut consent (kemahasiswaan-kelulusan, rancangan 13 Okt 2026).
  - Belum ada kode; kontrak endpoint tidak didefinisikan di bundle ini.
- **Kriteria penerimaan/pengujian:**
  - Given consent dicabut When disimpan Then log akses berikutnya mencerminkan pencabutan (belum dapat diuji).
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Endpoint consent/data-subject-request belum ada atau belum didefinisikan.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** backend: modul privasi
- **Catatan:** Tampilkan hanya entri berlabel 'Belum tersedia' di Pengaturan > Privasi; tanpa data palsu.
- **Sumber:** docs/RANCANGAN-APLIKASI.md#21.3; docs/kemahasiswaan-kelulusan (privacy self-service, commit 47043ca)

#### MOB-AUTH-22 - Isolasi data per peran dan kepemilikan objek (sisi klien)
- **Peran:** student, lecturer
- **Deskripsi:** Aplikasi tidak pernah mengirim student_id/lecturer_id sebagai otoritas; pengenal berasal dari token. Untuk dosen, daftar kelas selalu difilter ke kelas sendiri karena server belum membatasi akses.
- **Aturan bisnis:**
  - Kode: identitas mahasiswa selalu dari students.user_id; objek milik orang lain -> 404.
  - Kode: kepemilikan kelas dosen untuk nilai/absensi/ujian hanya ditegakkan bila flag academic.lecturer_ownership ON (default OFF) -> klien wajib memfilter lewat GET /teaching/classes.
  - materi/tugas/penilaian tugas selalu mensyaratkan mengajar kelas atau classes.update.
- **Kriteria penerimaan/pengujian:**
  - Given dosen When membuka Nilai/Absensi/Ujian Then hanya kelas dari /teaching/classes yang dapat dipilih.
  - Given mahasiswa mengakses id objek orang lain When API mengembalikan 404 Then UI menampilkan 'Data tidak ditemukan'.
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/teaching/classes`
- **Celah backend:** Pembatasan server atas kelas dosen bergantung flag academic.lecturer_ownership (OFF default) = risiko akses lintas kelas.
- **Status web:** n/a
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-13
- **Catatan:** Lihat pertanyaan tidak terselesaikan tentang flag ownership.
- **Sumber:** docs/RANCANGAN-AKUN-DOSEN.md; code: C3 ownership; C2 object-level; docs/STATUS-IMPLEMENTASI.md#2

#### MOB-AUTH-23 - Pusat keamanan akun (ringkasan)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Satu layar Keamanan yang menghimpun: ganti sandi, sesi/perangkat, logout semua, biometrik, auto-lock.
- **Aturan bisnis:**
  - Gabungan dari MOB-AUTH-06/09/11 dan MOB-PLAT-12/13; layar security_sessions saat ini hanya mencakup sesi.
- **Kriteria penerimaan/pengujian:**
  - Given Pengaturan > Keamanan When dibuka Then semua entri di atas tersedia sesuai peran.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/sessions`, `POST /api/v1/change-password`, `POST /api/v1/logout-all`
- **Celah backend:** -
- **Status web:** SecuritySessionsPage.tsx
- **Status mobile saat ini:** partial (security_sessions_screen)
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-06, MOB-AUTH-09, MOB-AUTH-11, MOB-PLAT-12
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#1; docs/manual-book/pmb (pusat keamanan akun, pola)

#### MOB-AUTH-24 - Aktivasi akun mahasiswa baru hasil konversi PMB
- **Peran:** student
- **Deskripsi:** Mahasiswa baru yang dikonversi dari PMB menerima NIM, akun, dan sandi awal; login pertama memaksa ganti sandi. Tautan aktivasi saat ini ke web.
- **Aturan bisnis:**
  - Kode: konversi menghasilkan akun dengan must_change_password=true dan tautan reset ke FRONTEND_URL (web).
  - Dokumen: PMB design menyebut NIM + akun idempoten; students.admission_application_id belum ada pada saat dokumen (D28).
  - Email placeholder mahasiswa tidak dapat menerima reset.
- **Kriteria penerimaan/pengujian:**
  - Given akun baru When login pertama Then alur MOB-AUTH-10 berjalan.
  - Given tautan aktivasi dari email When dibuka di ponsel Then diteruskan ke aplikasi bila terpasang (butuh MOB-AUTH-08).
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/login`, `POST /api/v1/reset-password`, `POST /api/v1/change-password`
- **Celah backend:** Tautan aktivasi ke web, bukan mobile; email placeholder tak dapat menerima reset.
- **Status web:** pages/ChangePasswordPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-02, MOB-AUTH-08, MOB-AUTH-10
- **Sumber:** docs/RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md; docs/pmb/ (re-registration); code: C5 gap 'Mobile activation deep link'

### Platform Mobile

Domain ini adalah kapabilitas lintas fitur aplikasi: tema, i18n, keamanan token, navigasi per peran, offline, biometrik, push (backend belum ada), deep link, aksesibilitas, proteksi screenshot, pengaturan, tenant, berkas, dan kesiapan rilis. Sebagian besar client-only (backend_status 'available' berarti tidak butuh backend baru).

#### MOB-PLAT-01 - Tema terang / gelap / ikuti sistem
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Pengguna memilih tema Terang, Gelap, atau Ikuti Sistem (default). Warna semantik (success/warning/info/danger) harus sadar-tema.
- **Aturan bisnis:**
  - Kode mobile: ThemeController menyimpan mode di SharedPreferences key civitasone.theme (D01 menyebut 'civitasone-theme'); default ThemeMode.system; TIDAK ada UI toggle.
  - AppAlert dan danger AppButton hardcode warna light (app_alert.dart:20-40, app_button.dart:99-100) sehingga salah di mode gelap; butuh ThemeExtension.
  - Dynamic Color (Material You) opsional (Fase 6); palet one/ (light canvas #F6F8FB, dark canvas #080C15) hanya referensi UX, aplikasi memakai hijau #166534/#3F9E68 + oranye.
- **Kriteria penerimaan/pengujian:**
  - Given pilihan 'Gelap' When aplikasi dibuka ulang Then tema gelap dipertahankan.
  - Given mode gelap When AppAlert/danger tampil Then kontras >= 4.5:1.
  - Given 'Ikuti Sistem' When tema OS berubah Then aplikasi mengikuti.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** one/store/prefs.ts (mock), layout web light/dark
- **Status mobile saat ini:** partial (token light/dark + persist; tanpa UI toggle; widget tidak sadar-tema)
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-15
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#5.3; docs/STATUS-IMPLEMENTASI.md#14; code: C9 theme; C8 dark palette

#### MOB-PLAT-02 - Internasionalisasi: infrastruktur ARB dan bahasa Indonesia (default)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Seluruh teks UI dipindah ke ARB/gen-l10n; Indonesia sebagai bahasa default dan sumber.
- **Aturan bisnis:**
  - Kode mobile: tidak ada ARB/AppLocalizations; sekitar 80+ literal Indonesia di ~15 berkas (termasuk validators, api_failure, exam_validation); locale dikunci 'id'.
  - Kode backend: tidak ada Accept-Language; semua pesan server Bahasa Indonesia; APP_LOCALE default en.
  - Format id-ID: tanggal 'd MMM y', 24 jam, Rupiah tanpa desimal, IPK 2 desimal koma.
- **Kriteria penerimaan/pengujian:**
  - Given bahasa Indonesia When mana pun Then tidak ada literal hardcode di widget (lint/grep).
  - Given pesan server Indonesia When tampil di bahasa lain Then diterima apa adanya (batasan diketahui).
- **Status backend:** `partial`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** Pesan server tidak dilokalisasi (tanpa Accept-Language); pesan validasi tetap Indonesia.
- **Status web:** one/i18n (mock, 733 key)
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-03, MOB-PLAT-04, MOB-PLAT-35
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.x; docs/STATUS-IMPLEMENTASI.md#14 (multi-bahasa ❌); code: C9 'i18n absent'; C1 Locale

#### MOB-PLAT-03 - Bahasa Inggris (EN)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Terjemahan EN dan pemilih bahasa; fallback ke ID untuk key hilang.
- **Aturan bisnis:**
  - D01: Indonesia default, Inggris tersedia; kamus one/ memakai en sebagai sumber kebenaran (733 key) yang dapat dipakai ulang.
  - Notifikasi server tetap Indonesia sampai backend mendukung locale (planned: notification_templates.locale).
- **Kriteria penerimaan/pengujian:**
  - Given bahasa EN When memilih Then seluruh UI aplikasi berganti tanpa restart; teks server tetap ID.
- **Status backend:** `partial`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** Tidak ada locale negotiation server.
- **Status web:** one/i18n
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-02
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.x; code: C8 i18n structure; docs/kemahasiswaan-kelulusan/27 (notification locale planned)

#### MOB-PLAT-04 - Bahasa Mandarin sederhana (ZH)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Terjemahan ZH (zh-CN) dengan istilah akademik baku dan fallback font CJK.
- **Aturan bisnis:**
  - Hanya prototype one/ memiliki ZH (parity 733 key; istilah: 学分, 学号, 出勤/请假/迟到/缺勤); D01 hanya menjanjikan ID/EN.
  - Perlu font CJK (NotoSansSC) atau fallback sistem.
- **Kriteria penerimaan/pengujian:**
  - Given bahasa ZH When memilih Then UI tampil tanpa glyph kosong (tofu).
- **Status backend:** `partial`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** Backend tidak menyediakan ZH.
- **Status web:** one/i18n (zh)
- **Status mobile saat ini:** none
- **Prioritas:** P3 | **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-PLAT-02
- **Catatan:** Tunda sampai ada permintaan bisnis; bukan komitmen dokumen mobile.
- **Sumber:** code: C8 i18n/terminology; docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md

#### MOB-PLAT-05 - Penyimpanan token aman
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Token hanya di Keystore-backed secure storage; tidak pernah di SharedPreferences, Hive, log, atau analitik.
- **Aturan bisnis:**
  - Kode: flutter_secure_storage 10.3.1, key civitasone.auth_token; token dibaca pada setiap request.
  - Kode: android:allowBackup belum false; resetOnError belum diatur.
  - D01 §11.1: token hanya secure storage; preferensi non-sensitif di shared_preferences.
- **Kriteria penerimaan/pengujian:**
  - Given token tersimpan When dump SharedPreferences/log Then token tidak ditemukan.
  - Given backup/restore Android When restore Then token tidak ikut (allowBackup=false).
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** n/a
- **Status mobile saat ini:** partial (secure storage ada; allowBackup/pembacaan per-request belum dirapikan)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-30
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#11.1; code: C9 token storage

#### MOB-PLAT-06 - Navigasi berbasis peran: shell mahasiswa
- **Peran:** student
- **Deskripsi:** Bottom navigation mahasiswa (usulan: Beranda · Jadwal · Kelas · Ujian · Akun) + menu 'Lainnya' (KRS, KHS, Transkrip, Presensi, Pengajuan, Kalender, Keuangan).
- **Aturan bisnis:**
  - Kode mobile: AppShell kosong (Scaffold polos); 65 RoutePaths mirror admin web, hanya 10 terdaftar; portal routes (/portal/krs ... /portal/tagihan) dapat jadi daftar periksa.
  - Web one/: bottom bar Home, Schedule, Courses, Notifications(badge), Menu. Item terkunci (fitur nonaktif) disembunyikan tanpa ruang kosong.
  - Rute dilindungi PermissionGate dan guard router.
- **Kriteria penerimaan/pengujian:**
  - Given mahasiswa When login Then hanya tab mahasiswa tampil dan tab aktif bertahan saat pindah.
  - Given item fitur nonaktif When render Then tidak tampil.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** nav.ts PORTAL_NAV_ITEMS
- **Status mobile saat ini:** none
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-14, MOB-PLAT-08, MOB-PLAT-26
- **Sumber:** docs/academic-learning/09-ui-ux-specification.md#5.5; code: C9 AppShell; C8 navigation

#### MOB-PLAT-07 - Navigasi berbasis peran: shell dosen dan dosen PA
- **Peran:** lecturer, advisor
- **Deskripsi:** Bottom nav dosen (usulan: Beranda · Jadwal · Kelas Saya · Persetujuan (PA) · Akun) + 'Lainnya' (Input nilai, Absensi, Ujian, Bank soal, Perwalian, HR self-service).
- **Aturan bisnis:**
  - Tab 'Persetujuan' hanya bila krs_advising.read; HR self-service bila hr_self_service.*; Ujian bila exams.*.
  - Tidak ada UI web khusus advisor; web nav 'Kelas Saya'/'Persetujuan KRS' tanpa halaman.
- **Kriteria penerimaan/pengujian:**
  - Given dosen non-PA When login Then tab Persetujuan tidak tampil.
  - Given dosen PA When login Then tab Persetujuan tampil dengan badge antrean.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** nav.ts (admin-flavoured)
- **Status mobile saat ini:** none
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-14, MOB-DASH-15
- **Sumber:** docs/academic-learning/09-ui-ux-specification.md#5.5; code: C7 gap 3; C3 permissions

#### MOB-PLAT-08 - Pembatasan UI berdasarkan izin dan layar Akses Ditolak
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Menu/tombol hanya tampil bila izin ada; akses langsung ke rute tanpa izin menampilkan 'Akses Ditolak'.
- **Aturan bisnis:**
  - Kode: PermissionGate (daftar koma = OR); catatan: rute publish ujian butuh exams.publish yang tidak dicek gate.
  - Server tetap otoritas: 403 'Anda tidak memiliki izin untuk mengakses resource ini.'
- **Kriteria penerimaan/pengujian:**
  - Given tanpa izin When membuka rute Then layar Akses Ditolak.
  - Given 403 dari server When aksi Then pesan ditampilkan tanpa crash.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** route guards
- **Status mobile saat ini:** done (PermissionGate + guard router)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-14
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#2.4; docs/STATUS-IMPLEMENTASI.md#14

#### MOB-PLAT-09 - Mode offline: banner konektivitas dan cache baca
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Banner offline, cache baca data referensi/ringkasan dengan label umur cache ('Terakhir diperbarui'), tulis ditolak saat offline.
- **Aturan bisnis:**
  - D01: cache baca Hive untuk data referensi dan /me; data transaksional (tagihan, nilai, absensi, persetujuan) TIDAK di-cache; tanpa antrean tulis.
  - D15 (academic-learning): cache baca jadwal/KHS/transkrip/materi, KRS read-only, antrean jawaban ujian terenkripsi -> KONFLIK dengan D01 (lihat unresolved).
  - Kode: hive_ce dan connectivity_plus dideklarasikan tetapi tidak dipakai; Wi-Fi tanpa internet harus dianggap offline (heartbeat).
- **Kriteria penerimaan/pengujian:**
  - Given offline When membuka beranda Then banner tampil dan data cache berlabel umur.
  - Given offline When submit Then pesan 'Tidak dapat dikirim saat offline' tanpa antrean.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** Tidak ada ETag/If-Modified-Since atau header cache.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-04, MOB-PLAT-10
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.6; docs/academic-learning/09-ui-ux-specification.md#9.3; docs/STATUS-IMPLEMENTASI.md#14 (offline ❌)

#### MOB-PLAT-10 - Penanganan kegagalan jaringan: timeout, retry, pemetaan error
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Dio memiliki connect/receive/send timeout, retry terbatas untuk GET idempoten, dan pesan ramah untuk timeout/tanpa koneksi.
- **Aturan bisnis:**
  - Kode: hanya connectTimeout 15 dtk; tanpa receive/sendTimeout, retry, logging; ApiFailure.fromDioException dapat crash bila tipe message/errors tak terduga.
  - Throttle 429 pada route tertentu; hormati Retry-After bila ada.
- **Kriteria penerimaan/pengujian:**
  - Given server lambat > timeout When request Then pesan 'Koneksi lambat' dan tombol Coba lagi.
  - Given respons malformed When parse Then tidak crash.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** axios timeout 15s
- **Status mobile saat ini:** partial
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-23
- **Sumber:** code: C9 Dio/Network hardening; docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#11.2

#### MOB-PLAT-11 - Pull-to-refresh, skeleton, empty/error state
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Pola state standar: skeleton, empty state, error + Coba lagi, pull-to-refresh, infinite scroll.
- **Aturan bisnis:**
  - Widget belum ada (shimmer dependency tak terpakai); paginasi hanya /student/requests dan /student/announcements, endpoint lain mengembalikan array penuh.
- **Kriteria penerimaan/pengujian:**
  - Given daftar kosong When dimuat Then empty state; Given gagal Then error + retry.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** components/portal/PortalState.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-23
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.x; code: C9 widgets

#### MOB-PLAT-12 - Buka kunci biometrik
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Opt-in setelah login sandi: sidik jari/wajah membuka sesi tersimpan; sandi tetap fallback.
- **Aturan bisnis:**
  - D01: local_auth; hanya flag disimpan, token tetap di secure storage; bukan metode autentikasi utama.
  - STATUS: biometrik ❌; tidak butuh backend.
- **Kriteria penerimaan/pengujian:**
  - Given biometrik aktif When aplikasi dibuka Then prompt biometrik; gagal 3x -> login sandi.
  - Given biometrik dimatikan di OS When dibuka Then fallback ke sandi.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-13, package: local_auth
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.4; docs/STATUS-IMPLEMENTASI.md#14; docs/proposal PDF#6.7

#### MOB-PLAT-13 - Kunci otomatis setelah tidak aktif
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Setelah N menit di latar (usulan 15) aplikasi menampilkan kunci biometrik tanpa menghapus token.
- **Aturan bisnis:**
  - D01 §11.2 (usulan, 'could'); interval dapat diatur di Pengaturan.
- **Kriteria penerimaan/pengujian:**
  - Given di latar > N menit When kembali Then kunci tampil.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-12
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#11.2

#### MOB-PLAT-14 - Notifikasi push (FCM)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Menerima push untuk persetujuan, akademik, keuangan, pengumuman; ketukan membuka layar terkait.
- **Aturan bisnis:**
  - Kode: NotificationChannel::Push tidak diimplementasikan; kolom user_devices.push_token ada tetapi tanpa endpoint daftar/ubah/hapus dan tanpa pengirim; mengaktifkan channel push ditolak 422.
  - D01: kanal Android approval_updates, academic_updates, finance_updates, announcements; izin POST_NOTIFICATIONS (Android 13+); hormati preferensi.
  - Konten push tidak boleh memuat nilai/NIK rinci.
- **Kriteria penerimaan/pengujian:**
  - Given token terdaftar When event terjadi Then push diterima dan ketukan membuka layar (belum dapat diuji; backend belum ada).
- **Status backend:** `missing`
- **Endpoint:** `(diusulkan) POST /api/v1/devices/push-token`, `(diusulkan) DELETE /api/v1/devices/{device}/push-token`
- **Celah backend:** Endpoint token, driver FCM, aktivasi channel push, payload dengan event_key+id untuk deep link.
- **Status web:** none (web polling)
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-PLAT-16, MOB-DASH-10, infra: Firebase/FCM + google-services
- **Catatan:** Tampilkan 'Notifikasi push: menunggu backend'; notifikasi in-app tetap tersedia.
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.3; docs/BLACK-BOX-TESTING.md#6 (NOTIF-05); code: C5 Notification; C1 gap

#### MOB-PLAT-15 - Pengaturan aplikasi
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Layar Pengaturan: tema, bahasa, ukuran teks, biometrik/kunci otomatis, preferensi notifikasi, keamanan, tentang/versi, bantuan, logout.
- **Aturan bisnis:**
  - Preferensi notifikasi: GET/POST /notification-preferences (channel database|mail|push|whatsapp|sms, notification_type default '*'); opt-out per event_key tidak bekerja (dicocokkan ke nama kelas), hanya '*' per channel.
  - Prototype web one/Account.tsx dan Support.tsx = mock.
- **Kriteria penerimaan/pengujian:**
  - Given Pengaturan When ubah tema/bahasa Then berlaku langsung dan persisten.
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/notification-preferences`, `POST /api/v1/notification-preferences`
- **Celah backend:** Preferensi per event_key tidak berfungsi; kanal push belum aktif.
- **Status web:** mock (one/pages/Account.tsx)
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-01, MOB-PLAT-03, MOB-PLAT-12, MOB-PLAT-17
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#6 (pengaturan 🧩); code: C8 Account; C5 preferences

#### MOB-PLAT-16 - Deep link dan App Links
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Tautan https App Link dan skema civitasone:// membuka layar tertentu melalui guard auth/izin yang sama; tautan notifikasi dipetakan ke rute mobile.
- **Aturan bisnis:**
  - Kode: tidak ada intent filter; link notifikasi/alert berupa path web (/akademik/persetujuan-krs, /portal/keuangan, /portal/tugas/{id}); event_key mengenali tipe.
  - Domain App Link resmi PERLU KONFIRMASI (app.civitasone.example placeholder); Xendit checkout tidak punya redirect URL sukses/gagal.
- **Kriteria penerimaan/pengujian:**
  - Given tautan ke tugas When dibuka belum login Then login lalu diteruskan ke tugas (parameter from).
  - Given path web tidak dikenal When dibuka Then beranda.
- **Status backend:** `partial`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** Tidak ada deep link terstruktur (type/id); Xendit return URL; reset password web.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-08, MOB-DASH-08, MOB-PLAT-14
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.8; docs/STATUS-IMPLEMENTASI.md#14; code: C5, C2, C4 gaps

#### MOB-PLAT-17 - Aksesibilitas
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Skala teks dinamis, kontras WCAG AA, label semantik TalkBack, status tidak hanya warna.
- **Aturan bisnis:**
  - D01: text scale clamp 0.85x-1.6x; kontras >= 4.5:1; badge selalu bertulisan; semantics label pada tombol ikon; MergeSemantics per baris kartu; target sentuh >= 44 px (PMB); portal harus lolos audit tanpa pelanggaran kritis (KK-NFR-09).
- **Kriteria penerimaan/pengujian:**
  - Given teks 1.6x When layar utama Then tidak terpotong/overflow.
  - Given TalkBack When navigasi Then semua kontrol berlabel.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** one prefs (highContrast, fontScale, reducedMotion mock)
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-15
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.11; docs/kemahasiswaan-kelulusan/ (KK-NFR-09); docs/academic-learning/09-ui-ux-specification.md

#### MOB-PLAT-18 - Perlindungan screenshot pada layar sensitif
- **Peran:** student, lecturer, advisor
- **Deskripsi:** FLAG_SECURE per layar: detail tagihan, daftar tagihan, sesi keamanan, kunci biometrik (bukan global).
- **Aturan bisnis:**
  - D01 §11.5; tidak butuh backend; perlu didokumentasikan perilaku pada penyelenggara ujian (proctoring) dan notifikasi tangkapan.
- **Kriteria penerimaan/pengujian:**
  - Given layar sensitif When screenshot Then hitam/ditolak OS; layar lain normal.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** package: flutter_windowmanager atau MethodChannel
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#11.5; docs/proposal PDF#6.7

#### MOB-PLAT-19 - Branding dan profil universitas (tenant)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Menampilkan nama/logo/warna universitas dari profil tenant, plus locale, timezone, currency.
- **Aturan bisnis:**
  - Kode: GET /tenant/profile (auth + tenant.access) -> id,code,name,short_name,timezone,locale,currency,primary_color,secondary_color,domains; GET /tenant/settings (PUT admin-only).
  - Aplikasi memakai identitas hijau/oranye sendiri; warna tenant opsional.
- **Kriteria penerimaan/pengujian:**
  - Given tenant profile When splash/Beranda Then nama universitas tampil; gagal -> default.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/tenant/profile`, `GET /api/v1/tenant/settings`
- **Celah backend:** logo hanya logo_file_id (butuh Bearer fetch); tidak ada endpoint publik pra-login.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-21
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#1 (⚙️ API saja); code: C1 tenant

#### MOB-PLAT-20 - Gerbang router ganti sandi wajib
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Seluruh navigasi diblokir ke layar ubah sandi selama must_change_password=true.
- **Aturan bisnis:**
  - Server tidak menegakkan flag; guard klien membaca /me dan login.
- **Kriteria penerimaan/pengujian:**
  - Given flag true When deep link/rute apa pun Then diarahkan ke Ubah Sandi.
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/me`
- **Celah backend:** Penegakan server belum ada.
- **Status web:** route guard web
- **Status mobile saat ini:** none
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-10
- **Sumber:** code: C1 gap; C7 forced password change

#### MOB-PLAT-21 - Penanganan tenant: build satu-universitas vs X-University-ID
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Pilih strategi tenant: (a) build satu universitas (flavor) dengan konfigurasi tetap, atau (b) pemilih universitas.
- **Aturan bisnis:**
  - Kode backend: tenant = X-University-ID (ULID) -> domain host -> membership default aktif; tenant.access: 400 bila tak ada tenant, 403 bila bukan anggota; /login NIM butuh university_code atau header.
  - Kode mobile: kunci civitasone.selected_university_id dibaca tetapi TIDAK PERNAH ditulis -> header tak pernah dikirim; login/forgot-password tanpa tenant.
  - D01 menetapkan mahasiswa/dosen tidak mengirim header; tetapi D06/D13/D16 dan student finance (tenant.access) menganjurkan mengirim X-University-ID bila anggota > 1 universitas.
  - Rekomendasi audit: flavor menyimpan university_code; setelah login ambil ULID dari /tenant/profile dan simpan di sesi untuk header.
- **Kriteria penerimaan/pengujian:**
  - Given build Atma Jaya When login NIM Then university_code dikirim otomatis.
  - Given pengguna dengan 2 keanggotaan When login Then dipilihkan universitas dan header dikirim sejak itu.
  - Given 403 bukan anggota When request Then pesan jelas + opsi ganti universitas.
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/login (university_code)`, `GET /api/v1/tenant/profile`
- **Celah backend:** Tidak ada endpoint publik penemuan universitas (code/domain -> ULID/branding).
- **Status web:** X-University-ID hanya dari Tenant Switcher Super Admin
- **Status mobile saat ini:** partial (pembaca header ada, penulis tidak ada)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-02, MOB-DASH-09
- **Catatan:** Putusan produk diperlukan: single vs multi universitas.
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#2.2; docs/STATUS-IMPLEMENTASI.md#14; docs/MULTI-TENANT-ARCHITECTURE.md; code: C9 tenant header; C1 gap 'Public university discovery'

#### MOB-PLAT-22 - Zona waktu tenant dan format tanggal/angka/mata uang
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Seluruh tanggal/tenggat ditampilkan di zona waktu universitas (default Asia/Jakarta) dengan sufiks WIB/WITA/WIT bila relevan; format id-ID.
- **Aturan bisnis:**
  - Server: timestamp ISO-8601 dengan offset; tanggal Y-m-d; jam 'HH:MM'; jatuh tempo berlaku sampai 23:59:59 waktu tenant.
  - Uang: string desimal; Rp7.500.000; angka decimal-cast (score) berupa string, ipk/persen float; package intl belum dipakai.
- **Kriteria penerimaan/pengujian:**
  - Given tenant WITA When tenggat tampil Then jam sesuai WITA dengan sufiks.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/tenant/profile`
- **Celah backend:** -
- **Status web:** formatters web
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-19, MOB-PLAT-02
- **Sumber:** docs/keuangan/README.md#5.1; docs/academic-learning/09-ui-ux-specification.md#8.1; code: C2 number/time formats

#### MOB-PLAT-23 - Klien API: envelope, paginasi, dan pemetaan kode error
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Parser tunggal untuk {success,message,data,meta,errors}, paginasi, dan kode error bisnis (409 ACADEMIC_BLOCKED, SCHEDULE_CONFLICT, IDEMPOTENCY_REQUIRED; 422 field errors; 429).
- **Aturan bisnis:**
  - meta/errors kosong dapat berupa [] (array) bukan objek -> parsing longgar; meta paginasi hanya current_page,per_page,total,last_page.
  - Cabang berdasarkan errors.code, bukan pesan.
  - 401 'Tidak terautentikasi.'; 403; 404 (juga untuk objek milik orang lain); 429; 500 generik.
- **Kriteria penerimaan/pengujian:**
  - Given 409 dengan errors.code Then UI menampilkan blokir/konflik terstruktur.
  - Given meta=[] When parse Then tidak crash.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** Daftar error code belum tersentral.
- **Status web:** axios + zod
- **Status mobile saat ini:** partial (ApiFailure rapuh)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-10
- **Sumber:** code: C1/C2/C4 conventions; docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md

#### MOB-PLAT-24 - Unggah, unduh, dan pratinjau berkas (kamera/galeri/penyimpanan)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Pilih berkas dari kamera/galeri/penyimpanan, unggah lewat /file-uploads, lalu unduh/pratinjau/bagikan dengan header Authorization (PDF KHS, kuitansi, surat).
- **Aturan bisnis:**
  - POST /file-uploads multipart field 'file' (pdf,jpg,jpeg,png,doc,docx,xls,xlsx; maks setting file.max_upload_size_mb default 10 MB; is_public opsional); hasil id dipakai di field file_upload_id/photo_file_id/attachment_file_id.
  - Batas per fitur: foto profil jpg/png <= 2 MB; bukti bayar jpg/png/pdf <= 2 MB; bukti UKT <= 5 MB; file harus status Clean dan milik pengguna.
  - Unduh/preview butuh Bearer (tanpa URL bertanda tangan); preview hanya pdf/png/jpg (415 lainnya); DELETE hanya unggahan sendiri yang belum terlampir.
- **Kriteria penerimaan/pengujian:**
  - Given foto > batas When unggah Then ditolak sebelum dikirim.
  - Given PDF kuitansi When ketuk Then diunduh dengan header dan dapat dibuka/dibagikan.
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/file-uploads`, `GET /api/v1/file-uploads/{fileUpload}`, `GET /api/v1/file-uploads/{fileUpload}/download`, `GET /api/v1/file-uploads/{fileUpload}/preview`, `DELETE /api/v1/file-uploads/{fileUpload}`
- **Celah backend:** Tidak ada URL bertanda tangan; FileUploadResource tanpa url; batas ukuran tidak dapat dibaca klien (butuh config).
- **Status web:** axios blob download
- **Status mobile saat ini:** none
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-27, package: file_picker, image_picker, open_filex, share_plus
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.x; docs/STATUS-IMPLEMENTASI.md#1; code: C2/C5 file-uploads; C4 receipts

#### MOB-PLAT-25 - Idempotensi dan penguncian optimistik pada aksi tulis
- **Peran:** student, lecturer
- **Deskripsi:** Kirim Idempotency-Key pada POST sensitif dan tangani konflik versi.
- **Aturan bisnis:**
  - Kode: POST /student/finance/payments/online mewajibkan Idempotency-Key (<=100 karakter) bila tidak -> 409 IDEMPOTENCY_REQUIRED; header Idempotent-Replayed pada ulang.
  - Rancangan: lock_version/STALE_VERSION, If-Match (belum luas di kode); PMB: idempotensi 24 jam implemented.
- **Kriteria penerimaan/pengujian:**
  - Given ketuk dua kali Bayar When dikirim Then satu transaksi (key sama per form open).
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/student/finance/payments/online (Idempotency-Key)`
- **Celah backend:** Idempotensi belum diterapkan untuk KRS, tugas, ujian; STALE_VERSION belum ada.
- **Status web:** keu portal
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-23
- **Sumber:** code: C4 idempotency; docs/keuangan/README.md#5.8; docs/academic-learning/06-integrasi-arsitektur.md#10

#### MOB-PLAT-26 - Gerbang fitur/entitlement dari server (menu tersembunyi)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Menu dan widget fitur yang tidak aktif/tidak ada pada paket tenant disembunyikan tanpa ruang kosong.
- **Aturan bisnis:**
  - Kode: tidak ada features/limits pada /me; GET /feature-flags butuh feature_flags.read (admin saja); flag academic.lecturer_ownership dan SHORT_TERM tidak dapat dibaca klien.
  - Rancangan D14: /me data.features dan data.limits; 403 not_in_plan/disabled.
- **Kriteria penerimaan/pengujian:**
  - Given fitur nonaktif When render Then entri tidak muncul.
- **Status backend:** `missing`
- **Endpoint:** `(diusulkan) GET /api/v1/me -> data.features, data.limits`
- **Celah backend:** Endpoint konfigurasi publik/terotentikasi untuk bendera fitur dan batas unggah.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-PLAT-08
- **Catatan:** Sementara gunakan izin /me saja.
- **Sumber:** docs/academic-learning/11-roles-permissions.md#2.3; docs/academic-learning/07-database-erd.md; code: C5 gap 'client-readable feature flags'

#### MOB-PLAT-27 - Versi aplikasi, paksa pembaruan, mode pemeliharaan
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Cek versi minimum dan status pemeliharaan saat start.
- **Aturan bisnis:**
  - Kode: tidak ada endpoint; versi login footer 0.1.0-fase0 hardcoded (gunakan package_info_plus).
- **Kriteria penerimaan/pengujian:**
  - Given versi < minimum When start Then layar 'Perbarui aplikasi' (belum dapat diuji).
- **Status backend:** `missing`
- **Endpoint:** `(diusulkan) GET /api/v1/app-config`
- **Celah backend:** Endpoint app-config publik.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-PLAT-31
- **Sumber:** code: C1 gap 'App version / force-update'

#### MOB-PLAT-28 - Pencarian global
- **Peran:** student, lecturer
- **Deskripsi:** Pencarian lintas menu/data sesuai izin.
- **Aturan bisnis:**
  - Belum ada backend (STATUS: 🧩); command palette hanya di prototype one/.
- **Kriteria penerimaan/pengujian:**
  - (tidak dapat diuji)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Endpoint pencarian global.
- **Status web:** mock (one/ command palette)
- **Status mobile saat ini:** none
- **Prioritas:** P3 | **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#1; docs/RANCANGAN-APLIKASI.md

#### MOB-PLAT-29 - Pelaporan crash dan analitik ramah privasi
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Crashlytics/analitik tanpa PII dan tanpa payload EDOM.
- **Aturan bisnis:**
  - D01: perlu persetujuan privasi (PERLU KONFIRMASI); dilarang mencatat jawaban EDOM/ujian; token tidak boleh masuk log.
- **Kriteria penerimaan/pengujian:**
  - Given crash When dilaporkan Then tidak memuat NIM/token.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3 | **Cakupan rekomendasi:** `defer`
- **Dependensi:** infra: Firebase
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.14; docs/academic-learning/05-edom.md#7.2

#### MOB-PLAT-30 - Hardening keamanan rilis: pinning sertifikat, obfuscation, backup
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Pinning SHA-256 pada staging/prod, build rilis obfuscated dengan simbol privat, allowBackup=false, network security per flavor.
- **Aturan bisnis:**
  - Kode: Env.apiPinnedCertSha256 dideklarasikan tak dipakai; networkSecurityConfig mengizinkan cleartext 10.0.2.2/localhost di semua flavor; pinning PERLU KONFIRMASI (rencana rotasi).
- **Kriteria penerimaan/pengujian:**
  - Given sertifikat tak cocok When koneksi Then ditolak.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** n/a
- **Status mobile saat ini:** partial
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-31
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#11.4; code: C9 android config

#### MOB-PLAT-31 - Kesiapan rilis Android
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Signing rilis, applicationId final, ikon adaptif, splash, izin runtime, CI rilis.
- **Aturan bisnis:**
  - Kode: signing memakai debug; applicationId dev.civitasone.civitasone placeholder; ikon default; assets kosong; CI debug saja dan flutter analyze gagal karena 2 info deprecated; minSdk 24, target/compile 36 (D01 menyebut 35).
- **Kriteria penerimaan/pengujian:**
  - Given tag rilis When CI Then AAB bertanda tangan dihasilkan.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** n/a
- **Status mobile saat ini:** partial
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-35
- **Sumber:** code: C9 Android/CI; docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#1.4

#### MOB-PLAT-32 - Edge-to-edge, predictive back, penjaga perubahan belum disimpan
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Layout edge-to-edge dan konfirmasi keluar pada form berubah.
- **Aturan bisnis:**
  - D01 Fase 6; exam preview memakai Navigator.push di luar go_router.
- **Kriteria penerimaan/pengujian:**
  - Given form berubah When back Then dialog konfirmasi.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** n/a
- **Status mobile saat ini:** none
- **Prioritas:** P3 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** -
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.x

#### MOB-PLAT-33 - Splash native dan layar pemulihan sesi
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Splash menutup waktu restore sesi agar tidak ada kedip layar terlindungi.
- **Aturan bisnis:**
  - Kode: tidak ada route splash; launch screen putih default; logo PERLU KONFIRMASI.
- **Kriteria penerimaan/pengujian:**
  - Given cold start When restore Then splash sampai /me selesai.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** n/a
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-04
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.x; code: C9

#### MOB-PLAT-34 - Dukungan iOS (tahap lanjut)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Folder ios/, signing Apple, push APNs, App Links.
- **Aturan bisnis:**
  - Kode: ios/ tidak ada; D01 Android-only; rencana iOS kemudian (flag lms.ios_app di rancangan).
- **Kriteria penerimaan/pengujian:**
  - (di luar rilis awal)
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** n/a
- **Status mobile saat ini:** none
- **Prioritas:** P3 | **Cakupan rekomendasi:** `defer`
- **Dependensi:** infra: akun Apple Developer
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#1.4; docs/academic-learning/15-implementation-roadmap.md#5.10

#### MOB-PLAT-35 - Fondasi teknis: migrasi Riverpod 3, rapikan dependensi, pengujian
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Migrasi Riverpod 2->3 (FamilyAsyncNotifier, valueOrNull), hapus dependensi tak terpakai, tambah tes auth/router/Dio/repo, perbaiki CI.
- **Aturan bisnis:**
  - Kode: 21 tes lulus tetapi tanpa tes auth/router/Dio/model; dependensi freezed/json_serializable/hive tak dipakai; controller dibuat ulang di build pada exam form.
- **Kriteria penerimaan/pengujian:**
  - Given CI When push Then format, analyze, test, build lulus.
- **Status backend:** `available`
- **Endpoint:** client-only (tidak memerlukan backend)
- **Celah backend:** -
- **Status web:** n/a
- **Status mobile saat ini:** partial
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** -
- **Sumber:** code: C9

#### MOB-PLAT-36 - Whitelabel per kampus (flavor)
- **Peran:** student, lecturer
- **Deskripsi:** Aplikasi bermerek kampus dengan tenant terkunci dan penolakan X-University-ID tak cocok.
- **Aturan bisnis:**
  - Dokumen academic-learning D15/D11: dikendalikan flag lms.whitelabel; SEVIMA tier Premium; belum ada entitlement.
- **Kriteria penerimaan/pengujian:**
  - (di luar cakupan awal)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Entitlement whitelabel.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3 | **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-PLAT-21
- **Sumber:** docs/academic-learning/09-ui-ux-specification.md#9.4; docs/academic-learning/02-edlink-lms.md#7.7

### Dashboard / Beranda

Domain ini mencakup beranda mahasiswa (satu panggilan GET /student/dashboard sudah lengkap) dan beranda dosen/dosen PA (belum ada endpoint ringkasan; diturunkan dari /teaching/classes, /krs-submissions, /notifications). GET /dashboard adalah statistik institusi, bukan beranda personal.

#### MOB-DASH-01 - Beranda mahasiswa: ringkasan profil dan akademik (IPK, IPS, SKS, status KRS, dosen PA)
- **Peran:** student
- **Deskripsi:** Bagian atas beranda: sapaan, nama/NIM/prodi/semester/status, IPK, IPS terakhir, SKS lulus/semester berjalan/batas, status KRS (termasuk tenggat dan catatan keputusan), dan dosen PA. Satu panggilan API untuk seluruh beranda.
- **Aturan bisnis:**
  - Kode: GET /student/dashboard (student_portal.read) -> data{profile{name,nim,photo_file_id,study_program,faculty,admission_year,semester,status{value,label}},academic{ipk:float,last_ips{academic_term_id,label,ips},total_credits,passed_credits,current_term_credits,max_credits,remaining_credits,current_term,academic_advisor},krs{status,status_label,total_credits,max_credits,course_count,is_open,krs_end_date,decision_note}}.
  - ipk/ips float (pembulatan 2 desimal); SKS int; tampilkan IPK dengan koma desimal id-ID.
  - Hanya mahasiswa; non-mahasiswa -> 403.
  - Desain RANCANGAN-AKUN-MAHASISWA §4.4: kartu IP semester ini, SKS diambil, ujian mendatang.
- **Kriteria penerimaan/pengujian:**
  - Given mahasiswa aktif When beranda dibuka Then kartu IPK/IPS/SKS/KRS terisi dari satu request /student/dashboard.
  - Given krs.is_open=true When beranda Then tombol 'Isi KRS' mengarah ke layar KRS dan tenggat krs_end_date tampil.
  - Given jaringan gagal When beranda Then pesan error dengan tombol Coba lagi (bukan layar kosong).
  - Given IPK null/0 (mahasiswa baru) When beranda Then tampil '-' tanpa crash.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/dashboard`
- **Celah backend:** Pencarian foto hanya file_id (butuh fetch Bearer).; Tidak ada pagination/ETag; respons berat tanpa cache header.
- **Status web:** pages/portal/PortalDashboardPage.tsx (real API)
- **Status mobile saat ini:** partial (dashboard_screen hanya placeholder: sapaan + logout + tombol keamanan)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-14, MOB-PLAT-23, MOB-PLAT-22
- **Catatan:** one/pages/Dashboard.tsx adalah mock statis (referensi UX saja).
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#6 (dashboard ✅); docs/RANCANGAN-AKUN-MAHASISWA.md#4.4, #8.3; docs/academic-learning/09-ui-ux-specification.md (student home); code: C2 §2 GET /student/dashboard; StudentDashboardService

#### MOB-DASH-02 - Jadwal kuliah hari ini dan kelas berikutnya (beranda mahasiswa)
- **Peran:** student
- **Deskripsi:** Daftar slot kuliah hari ini dengan status upcoming/ongoing/finished dan kartu 'kelas berikutnya' (waktu mulai, ruang, dosen).
- **Aturan bisnis:**
  - Kode: today_schedule[] = slot+{date,status:'upcoming|ongoing|finished'}; next_class = slot+{date,starts_at}|null; hari ISO 1..7 (1=Senin), waktu 'HH:MM', 'sekarang' dihitung dengan zona waktu universitas (AcademicClock, default Asia/Jakarta).
  - Jadwal mingguan berulang tanpa pengecualian libur (tidak ada ekspansi kalender).
- **Kriteria penerimaan/pengujian:**
  - Given ada kuliah hari ini When beranda Then daftar urut waktu dengan status benar menurut jam tenant.
  - Given tidak ada kuliah When beranda Then empty state 'Tidak ada kuliah hari ini'.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/dashboard`
- **Celah backend:** Tidak ada pengecualian libur/kuliah pengganti dalam jadwal mingguan.
- **Status web:** PortalDashboardPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-01, (jadwal kuliah lengkap di grup akademik)
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#6; docs/RANCANGAN-AKUN-MAHASISWA.md#4.4; docs/RANCANGAN-APLIKASI.md#5 (Jadwal hari ini); code: C2 today_schedule/next_class

#### MOB-DASH-03 - Ringkasan absensi dan peringatan kehadiran di bawah minimum (beranda)
- **Peran:** student
- **Deskripsi:** Persentase kehadiran keseluruhan dan daftar mata kuliah dengan kehadiran di bawah ambang minimum.
- **Aturan bisnis:**
  - Kode: attendance{overall{total_meetings,present,permitted,sick,absent,percentage|null},minimum_percent:float,below_minimum:[course attendance rows]}.
  - percentage bisa null bila belum ada pertemuan.
- **Kriteria penerimaan/pengujian:**
  - Given kehadiran < minimum_percent pada suatu MK When beranda Then MK tampil dengan penanda peringatan (ikon + teks, tidak hanya warna).
  - Given belum ada pertemuan When beranda Then tampil 'Belum ada data absensi'.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/dashboard`
- **Celah backend:** -
- **Status web:** PortalDashboardPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-01
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#6; code: C2 attendance block; docs/RANCANGAN-APLIKASI.md#5

#### MOB-DASH-04 - Ujian mendatang (beranda mahasiswa)
- **Peran:** student
- **Deskripsi:** Hingga 5 ujian terdekat dengan status upcoming/available/in_progress dan jendela waktu.
- **Aturan bisnis:**
  - Kode: upcoming_exams[] = {id,title,course_name,starts_at,ends_at,duration_minutes,status}, maksimum 5; alert ujian dibuat bila <= 72 jam.
  - Server adalah otoritas waktu ujian.
- **Kriteria penerimaan/pengujian:**
  - Given ujian tersedia When beranda Then kartu menampilkan tombol 'Mulai' bila status=available, menuju layar ujian (grup lain).
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/dashboard`
- **Celah backend:** -
- **Status web:** PortalDashboardPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-01, (modul ujian mahasiswa di grup lain)
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#6; code: C2 upcoming_exams; docs/RANCANGAN-AKUN-MAHASISWA.md#4.4

#### MOB-DASH-05 - Tugas mendekati tenggat (beranda mahasiswa)
- **Peran:** student
- **Deskripsi:** Hingga 5 tugas belum dikumpulkan yang jatuh tempo dalam 7 hari.
- **Aturan bisnis:**
  - Kode: assignments_due[] = baris tugas status not_submitted, due <= 7 hari, maks 5; alert tugas dibuat bila <= 48 jam.
- **Kriteria penerimaan/pengujian:**
  - Given tugas jatuh tempo < 48 jam When beranda Then ditampilkan dengan penanda mendesak dan menuju detail tugas.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/dashboard`
- **Celah backend:** -
- **Status web:** PortalDashboardPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-01
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#6; code: C2 assignments_due

#### MOB-DASH-06 - Nilai terbaru (beranda mahasiswa)
- **Peran:** student
- **Deskripsi:** Hingga 5 nilai yang dipublikasikan dalam 14 hari terakhir (kode MK, nama, term, nilai huruf).
- **Aturan bisnis:**
  - Kode: recent_grades[] = {course_code,course_name,term_label,letter_grade,graded_at} (14 hari, maks 5).
- **Kriteria penerimaan/pengujian:**
  - Given nilai baru When beranda Then muncul dalam daftar; tanpa nilai -> bagian disembunyikan.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/dashboard`
- **Celah backend:** -
- **Status web:** PortalDashboardPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-01
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#6; code: C2 recent_grades

#### MOB-DASH-07 - Pengumuman terbaru dan jumlah belum dibaca (beranda mahasiswa)
- **Peran:** student
- **Deskripsi:** Tiga pengumuman terbaru + hitung belum dibaca; ketuk membuka detail dan menandai dibaca.
- **Aturan bisnis:**
  - Kode: announcements{unread_count,latest[{id,title,published_at,is_pinned,is_read}]}; feed lengkap GET /student/announcements (per_page 1..50, default 10), POST /student/announcements/{id}/read.
  - Pengumuman tak terlihat -> 404.
- **Kriteria penerimaan/pengujian:**
  - Given pengumuman belum dibaca When ditekan Then detail terbuka dan POST read dipanggil, badge berkurang.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/dashboard`, `GET /api/v1/student/announcements`, `GET /api/v1/student/announcements/{announcement}`, `POST /api/v1/student/announcements/{announcement}/read`
- **Celah backend:** -
- **Status web:** PortalDashboardPage.tsx + PortalAnnouncementsPage
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-01, (feed pengumuman di grup notifikasi)
- **Sumber:** docs/STATUS-IMPLEMENTASI.md#6; code: C5 student announcements

#### MOB-DASH-08 - Peringatan beranda (alerts) dan pemetaan tautan web ke layar mobile
- **Peran:** student
- **Deskripsi:** Banner peringatan prioritas (status akademik, KRS, ujian, tugas, nilai, absensi, pengumuman) dengan severity info/success/warning/danger yang membuka layar terkait.
- **Aturan bisnis:**
  - Kode: alerts[] = {type:'status|krs|exam|assignment|grade|attendance|announcement',severity,message(ID),link:'/portal/...'|null}.
  - link adalah rute WEB (/portal/krs, /portal/ujian, /portal/tugas/{id}, /portal/nilai, /portal/absensi, /portal/pengumuman, /portal/akademik): klien harus memetakan ke rute sendiri; link tak dikenal diabaikan.
- **Kriteria penerimaan/pengujian:**
  - Given alert type=krs When ditekan Then layar KRS terbuka.
  - Given link tidak dikenal When ditekan Then tidak crash dan layar beranda tetap.
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/student/dashboard`
- **Celah backend:** Tautan berupa path web; tidak ada type/id terstruktur untuk deep link (gap C2 'Mobile deep links').
- **Status web:** PortalDashboardPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-16, MOB-DASH-01
- **Sumber:** code: C2 alerts; code: C5 'notification links are web paths'

#### MOB-DASH-09 - Kartu ringkasan keuangan di beranda (tunggakan, jatuh tempo terdekat, deposit, cekal)
- **Peran:** student
- **Deskripsi:** Kartu 'Keuangan' menampilkan total tagihan outstanding, jatuh tempo terdekat, saldo deposit, dan status cekal; menuju modul keuangan.
- **Aturan bisnis:**
  - Kode: GET /student/finance/summary (auth + tenant.access + student_finance.read); jumlah uang string desimal 2 digit; status ber-label *_label Indonesia.
  - Hanya role student memiliki student_finance.*; tanpa X-University-ID/tenant -> 400/403 (tenant.access).
  - Format Rp7.500.000 (tanpa desimal bila ,00); hari jatuh tempo dalam zona waktu tenant.
- **Kriteria penerimaan/pengujian:**
  - Given ada tagihan jatuh tempo When beranda Then kartu menampilkan nominal & tanggal terdekat dan menuju daftar tagihan.
  - Given summary gagal When beranda Then kartu keuangan menampilkan error lokal tanpa memblokir kartu lain.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/summary`
- **Celah backend:** Ringkasan keuangan tidak ikut di /student/dashboard (panggilan terpisah dan butuh tenant.access).
- **Status web:** keu/pages/portal/PortalFinancePages.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-21, MOB-PLAT-22, (modul keuangan mahasiswa di grup lain)
- **Sumber:** docs/keuangan/20-rancangan-api-dan-integrasi.md#5; code: C4 student/finance/summary; docs/STATUS-IMPLEMENTASI.md(keuangan ✅ API)

#### MOB-DASH-10 - Badge notifikasi belum dibaca di beranda dan navigasi (semua peran)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Ikon lonceng dengan jumlah notifikasi belum dibaca; menuju pusat notifikasi.
- **Aturan bisnis:**
  - Kode: GET /notifications/unread-count -> {unread_count}; /student/dashboard juga memuat notifications_unread; endpoint notifikasi hanya butuh login (tanpa permission).
  - Dosen menerima notifikasi student.krs_review_requested dan notifikasi HR di pusat notifikasi.
- **Kriteria penerimaan/pengujian:**
  - Given 3 belum dibaca When beranda Then badge '3'; setelah buka semua -> badge hilang.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/notifications/unread-count`, `GET /api/v1/notifications`
- **Celah backend:** Notifikasi legacy tidak memiliki event_key/message; link adalah path web.
- **Status web:** header notification bell (polling)
- **Status mobile saat ini:** none
- **Prioritas:** P1 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** (pusat notifikasi di grup notifikasi), MOB-PLAT-14
- **Sumber:** code: C5 Notification; docs/video-tutorial-ujian (bell di header dosen)

#### MOB-DASH-11 - Widget tambahan beranda mahasiswa: pengajuan surat, jadwal bimbingan, kalender akademik
- **Peran:** student
- **Deskripsi:** Kartu status pengajuan surat/cuti terakhir, jadwal bimbingan akademik, dan agenda kalender akademik terdekat.
- **Aturan bisnis:**
  - Dokumen RANCANGAN-APLIKASI §5 mencantumkan: Status pengajuan surat, Jadwal bimbingan, Kalender akademik.
  - Kode: GET /student/requests (paginasi per_page<=100) dan GET /student/calendar tersedia; endpoint jadwal bimbingan akademik/skripsi TIDAK ada (halaman web PortalAcademicAdvisingPage adalah mock).
- **Kriteria penerimaan/pengujian:**
  - Given pengajuan 'Sedang Ditinjau' When beranda Then kartu menunjukkan judul+status.
  - Given tidak ada backend bimbingan When beranda Then kartu bimbingan tidak ditampilkan atau berlabel 'Belum tersedia' tanpa data.
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/student/requests`, `GET /api/v1/student/calendar`
- **Celah backend:** Tidak ada endpoint jadwal bimbingan akademik (/advising/* hanya di rancangan).
- **Status web:** PortalCalendarPage; PortalAcademicAdvisingPage = mock
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-01
- **Sumber:** docs/RANCANGAN-APLIKASI.md#5 Dashboard Mahasiswa; code: C2 student/requests, student/calendar; C7 mock pages

#### MOB-DASH-12 - Pemilihan beranda berdasarkan peran (mahasiswa / dosen / dosen PA)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Satu rute beranda yang memilih varian: mahasiswa -> beranda mahasiswa; dosen -> beranda dosen; dosen PA -> beranda dosen dengan blok perwalian. Akun multi-peran memakai MOB-DASH-19.
- **Aturan bisnis:**
  - D01 (rancangan admin-parity) memilih 3 varian: Super Admin tanpa tenant, student, dan semua lainnya (dosen = DashboardScreen admin); keputusan audit: aplikasi untuk end-user HANYA memiliki varian student dan lecturer/advisor.
  - Penentuan: roles memuat 'student' -> student; lecturer_profile.read -> lecturer; krs_advising.read -> blok PA.
  - Super Admin / admin_staff berada di luar cakupan aplikasi.
- **Kriteria penerimaan/pengujian:**
  - Given login mahasiswa Then beranda mahasiswa; login dosen Then beranda dosen (login_flow_test D01 3 varian).
  - Given peran tidak dikenal Then 'Akses Ditolak'.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/me`
- **Celah backend:** -
- **Status web:** DashboardRoute (useIsStudent) di web
- **Status mobile saat ini:** partial (hanya satu placeholder)
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-14, MOB-PLAT-06, MOB-PLAT-07
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.2, #2.4; docs/STATUS-IMPLEMENTASI.md#14; docs/academic-learning/09-ui-ux-specification.md#4.1

#### MOB-DASH-13 - Beranda dosen: jadwal mengajar hari ini dan daftar kelas diampu
- **Peran:** lecturer
- **Deskripsi:** Kartu 'Jadwal Mengajar Hari Ini' (waktu, mata kuliah, kelas, ruang) dan daftar kelas diampu semester berjalan, diturunkan dari GET /teaching/classes.
- **Aturan bisnis:**
  - Kode: GET /teaching/classes (course_materials.read atau assignments.read) default term = is_current; non-classes.update hanya kelas milik sendiri (selalu); paginasi per_page 1..100 default 20; item memuat schedules[{day_of_week 1..7,day_label,start_time,end_time,room}].
  - Tidak ada endpoint 'jadwal saya': jadwal hari ini = filter schedules di mana day_of_week = hari ini (zona waktu tenant); mingguan berulang, tanpa pengganti/online link.
  - Tutorial web: kartu 'Kelas Diampu', 'Ujian Aktif', 'Soal di Bank Soal' dan tabel 'Jadwal Mengajar Hari Ini' (Waktu, Mata Kuliah, Kelas, Ruang).
- **Kriteria penerimaan/pengujian:**
  - Given dosen mengampu 3 kelas dengan 2 jadwal hari Senin When Senin Then dua slot tampil urut waktu.
  - Given belum ada kelas di term berjalan When beranda Then empty state.
  - Given akun dosen belum tertaut When beranda Then pesan 'hubungi Bagian SDM' (bukan crash).
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/teaching/classes`, `GET /api/v1/lecturer-profile`, `GET /api/v1/academic-terms`
- **Celah backend:** Tidak ada endpoint ringkasan beranda dosen; jadwal diturunkan di klien dari /teaching/classes; tidak ada sesi pertemuan per tanggal.
- **Status web:** DashboardPage.tsx (admin stats + LecturerSummaryCard); jadwal mengajar hanya di tutorial mock
- **Status mobile saat ini:** none
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-AUTH-19, MOB-AUTH-22, MOB-PLAT-22
- **Catatan:** Beranda web dosen adalah statistik admin, bukan teaching-oriented (C7).
- **Sumber:** docs/RANCANGAN-AKUN-DOSEN.md#4.5, #8.4; docs/video-tutorial-ujian/source-dosen/tutorial.html#dashBody; code: C3 GET /teaching/classes; D06 row 70

#### MOB-DASH-14 - Beranda dosen: ringkasan beban mengajar (kelas, SKS, ujian aktif, bank soal)
- **Peran:** lecturer
- **Deskripsi:** Kartu statistik pribadi dosen: jumlah kelas diampu, total SKS, ujian aktif/draft, jumlah soal bank soal.
- **Aturan bisnis:**
  - Kode: kelas & SKS dari /teaching/classes; ujian dari GET /exams (exams.read) difilter class_section_id milik sendiri; bank soal dari endpoint question-bank (question_bank.*).
  - Dengan flag ownership OFF, GET /exams dapat mengembalikan ujian kelas lain -> filter di klien.
- **Kriteria penerimaan/pengujian:**
  - Given 4 kelas & 2 ujian aktif When beranda Then kartu menampilkan 4 dan 2 hanya dari kelas sendiri.
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/teaching/classes`, `GET /api/v1/exams`, `GET /api/v1/question-bank (lihat grup ujian dosen)`
- **Celah backend:** Tidak ada agregat; N panggilan (per kelas) bila ingin menghitung.
- **Status web:** LecturerSummaryCard (web)
- **Status mobile saat ini:** partial (modul ujian ada; belum ada kartu beranda)
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-13, MOB-AUTH-22
- **Sumber:** docs/video-tutorial-ujian/source-dosen (Kelas Diampu, Ujian Aktif, Soal di Bank Soal); code: C3 exams; C9 exam list

#### MOB-DASH-15 - Beranda dosen PA: antrean persetujuan KRS mahasiswa bimbingan
- **Peran:** advisor
- **Deskripsi:** Kartu 'KRS menunggu persetujuan' dengan jumlah, KRS tertua, dan jalan pintas ke layar persetujuan.
- **Aturan bisnis:**
  - Kode: GET /krs-submissions (krs.approve atau krs_advising.read) default status=submitted, urut submitted_at ASC (tertua dulu), paginasi per_page 1..100 default 20; meta.total = jumlah antrean.
  - Dosen PA memperoleh notifikasi student.krs_review_requested saat mahasiswa mengajukan; link notifikasi /akademik/persetujuan-krs (web).
  - Halaman web 'Persetujuan KRS' tampil di nav tetapi rutenya belum ada (C7).
- **Kriteria penerimaan/pengujian:**
  - Given 5 KRS submitted When beranda Then kartu '5 menunggu' dan KRS tertua ditampilkan.
  - Given antrean kosong When beranda Then 'Tidak ada KRS menunggu'.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/krs-submissions?status=submitted&per_page=1`
- **Celah backend:** -
- **Status web:** none (nav ada, halaman belum dirutekan)
- **Status mobile saat ini:** none
- **Prioritas:** P0 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** (row persetujuan KRS di grup akademik dosen), MOB-AUTH-14
- **Sumber:** docs/RANCANGAN-AKUN-DOSEN.md#4.5 (KRS advisor); docs/academic-learning/09-ui-ux-specification.md#4.4; code: C3 krs-submissions; C7 gap 3

#### MOB-DASH-16 - Beranda dosen: tugas perlu diperiksa dan nilai belum lengkap
- **Peran:** lecturer
- **Deskripsi:** Kartu aksi: jumlah pengumpulan tugas belum dinilai dan KRS item belum bernilai per kelas.
- **Aturan bisnis:**
  - Dokumen RANCANGAN-APLIKASI §5 dan academic-learning menyebut widget ini.
  - Kode: tidak ada endpoint agregat; data hanya bisa diturunkan dari GET /assignments/{id}/submissions per tugas dan GET krs-items per kelas (N+1).
- **Kriteria penerimaan/pengujian:**
  - Given tugas dengan submission belum dinilai When beranda Then jumlah tampil (hanya bila agregasi backend tersedia).
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/class-sections/{classSection}/assignments`, `GET /api/v1/assignments/{assignment}/submissions`
- **Celah backend:** Endpoint agregat 'antrean penilaian dosen' belum ada; mobile tidak boleh melakukan N+1 berat.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-13
- **Catatan:** Tandai pilot: tampilkan hanya per kelas saat user membuka kelas, kartu beranda menunggu agregat.
- **Sumber:** docs/RANCANGAN-APLIKASI.md#5 Dashboard Dosen; docs/academic-learning/09-ui-ux-specification.md#4.4; code: C3 submissions

#### MOB-DASH-17 - Pengumuman untuk dosen
- **Peran:** lecturer, advisor
- **Deskripsi:** Umpan pengumuman universitas/fakultas untuk dosen di beranda.
- **Aturan bisnis:**
  - Kode: dosen tidak mempunyai announcements.read; umpan pengumuman hanya /student/announcements untuk mahasiswa.
  - Dokumen: widget 'Pengumuman' dosen pada RANCANGAN-APLIKASI §5 dan academic-learning 4.4.
- **Kriteria penerimaan/pengujian:**
  - (tidak dapat diuji; backend belum ada)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Perlu endpoint feed pengumuman untuk dosen (mis. GET /announcements/feed) atau pemberian izin announcements.read terbatas.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** backend: Announcement module
- **Sumber:** docs/RANCANGAN-APLIKASI.md#5 Dashboard Dosen; code: C5 Announcement

#### MOB-DASH-18 - Widget dosen lain: mahasiswa bimbingan, jadwal sidang, aktivitas penelitian
- **Peran:** lecturer, advisor
- **Deskripsi:** Kartu 'Mahasiswa Bimbingan', 'Jadwal Sidang', 'Aktivitas Penelitian' dari rancangan beranda dosen.
- **Aturan bisnis:**
  - Kode: tidak ada endpoint daftar mahasiswa bimbingan khusus dosen PA (hanya GET /students dengan students.read dan GET /krs-submissions untuk yang sudah mengajukan); filter academic_advisor_id pada /students belum terverifikasi.
  - Sidang/penelitian dosen: tidak ada endpoint untuk lecturer (modul thesis admin-only).
- **Kriteria penerimaan/pengujian:**
  - (tidak dapat diuji; backend belum ada)
- **Status backend:** `missing`
- **Endpoint:** `GET /api/v1/students (students.read; filter perwalian belum terverifikasi)`
- **Celah backend:** Perlu endpoint /advising/students (rencana) dan feed sidang/penelitian dosen.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** backend: advising module
- **Catatan:** Entri berlabel 'Menunggu backend'; tanpa data palsu.
- **Sumber:** docs/RANCANGAN-APLIKASI.md#5; docs/RANCANGAN-AKUN-DOSEN.md#4.5; code: C3 / C5 thesis restricted

#### MOB-DASH-19 - Pemilih tampilan multi-peran (Pengajar / Dosen Wali)
- **Peran:** lecturer, advisor
- **Deskripsi:** Akun dengan beberapa peran (dosen + PA) dapat berpindah tampilan; pilihan terakhir disimpan lokal; default = cakupan terluas.
- **Aturan bisnis:**
  - Dokumen academic-learning 09 §4.1: tab 'Pengajar · Dosen Wali · Kaprodi', pilihan disimpan lokal.
  - Kaprodi/dekan di luar cakupan end-user dosen mobile (belum ada cakupan scope di /me).
- **Kriteria penerimaan/pengujian:**
  - Given akun lecturer+academic_advisor When beranda Then tab Pengajar dan Dosen Wali tersedia dan pilihan bertahan setelah restart.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/me`
- **Celah backend:** -
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-12
- **Sumber:** docs/academic-learning/09-ui-ux-specification.md#4.1

#### MOB-DASH-20 - Statistik institusi (GET /dashboard) untuk dosen
- **Peran:** lecturer
- **Deskripsi:** Statistik tenant yang difilter izin: total mahasiswa/aktif, program studi, kelas aktif, grafik pertumbuhan/status/prodi; pending_approvals hanya milik sendiri.
- **Aturan bisnis:**
  - Kode: GET /dashboard (auth + tenant) cache 300 dtk; parameter academic_term_id, faculty_id, study_program_id, date_from, date_to; summary difilter per izin; 422 'Pilih universitas terlebih dahulu.' bila tenant tak ada.
  - Untuk dosen: total_students, active_students, total_study_programs, active_classes + 4 grafik; mahasiswa tidak mendapat data berguna (hanya filters).
  - Bukan teaching-oriented; D01 mengklasifikasi 'unclear'.
- **Kriteria penerimaan/pengujian:**
  - Given dosen When membuka Statistik Then kartu/grafik hanya yang diizinkan tampil; kosong -> empty state 'Belum ada statistik'.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/dashboard`
- **Celah backend:** Tidak personal; endpoint filters satu-satunya sumber academic_term_id berjalan.
- **Status web:** pages/DashboardPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P3 | **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-DASH-13
- **Catatan:** Nilai rendah untuk pengguna akhir; hanya filters.academic_terms yang berguna (id term berjalan).
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.2.1; docs/RANCANGAN-AKUN-DOSEN.md#8.4; code: C5 Dashboard; C7

#### MOB-DASH-21 - Aksi cepat beranda per peran
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Tombol jalan pintas per peran: mahasiswa (KRS, Jadwal, Tagihan, Presensi), dosen (Nilai, Absensi, Ujian, Persetujuan KRS).
- **Aturan bisnis:**
  - D01: quick actions per peran; widget layar utama opsional (di luar wajib). Aksi disembunyikan bila izin/fitur tidak ada.
- **Kriteria penerimaan/pengujian:**
  - Given izin tidak dimiliki When beranda Then aksi tersebut tidak muncul.
- **Status backend:** `available`
- **Endpoint:** -
- **Celah backend:** -
- **Status web:** LecturerSummaryCard (quick links)
- **Status mobile saat ini:** none
- **Prioritas:** P3 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-PLAT-08, MOB-PLAT-26
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.x (quick actions); docs/RANCANGAN-AKUN-DOSEN.md#8.4 (LecturerSummaryCard quick links)

#### MOB-DASH-22 - Agenda akademik di beranda (kalender akademik)
- **Peran:** student, lecturer, advisor
- **Deskripsi:** Agenda terdekat: periode KRS, UTS/UAS, tenggat nilai, wisuda dari kalender akademik.
- **Aturan bisnis:**
  - Kode: student -> GET /student/calendar; dosen -> GET /academic-calendar-events (academic_calendar.read), limit 500 tanpa paginasi.
  - Kategori: krs/exam/payment/grading/graduation (AcademicAgendaList D01).
- **Kriteria penerimaan/pengujian:**
  - Given event dalam 7 hari When beranda Then ditampilkan urut tanggal.
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/calendar`, `GET /api/v1/academic-calendar-events`
- **Celah backend:** -
- **Status web:** PortalCalendarPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P2 | **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-DASH-01, MOB-DASH-13
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.2.1; code: C2 student/calendar; C3 calendar events

#### MOB-DASH-23 - Dashboard LMS (sesi pertemuan, QR presensi, jurnal, EDOM) mahasiswa/dosen
- **Peran:** student, lecturer
- **Deskripsi:** KPI sesi dilaksanakan, Mulai sesi / Tampilkan QR / Isi jurnal, realisasi RPS, statistik LMS, agregat EDOM sendiri.
- **Aturan bisnis:**
  - Dokumen academic-learning 2026-10-11: 'Baru' (rancangan saja); tidak ada endpoint sesi/QR/EDOM di kode.
- **Kriteria penerimaan/pengujian:**
  - (tidak dapat diuji; backend belum ada)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Seluruh modul LMS sesi/QR/EDOM belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3 | **Cakupan rekomendasi:** `defer`
- **Dependensi:** backend: LMS/EDLINK
- **Sumber:** docs/academic-learning/02-edlink-lms.md; docs/academic-learning/09-ui-ux-specification.md#4.4

#### MOB-DASH-24 - Laporan kelas dan ekspor untuk dosen
- **Peran:** lecturer
- **Deskripsi:** Laporan kelas dan ekspor dari beranda dosen.
- **Aturan bisnis:**
  - Kode: dosen tidak memiliki izin reports.*; modul Report admin/staf saja.
  - academic-learning 12: 'Baru'.
- **Kriteria penerimaan/pengujian:**
  - (tidak dapat diuji)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Endpoint laporan dosen belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3 | **Cakupan rekomendasi:** `defer`
- **Dependensi:** backend: Report
- **Sumber:** docs/academic-learning/12-reports-analytics.md; code: C5 permissions

### Pertanyaan belum terselesaikan

1. Tenant mobile: build satu universitas (flavor + university_code/X-University-ID dari /tenant/profile) atau pemilih universitas? D01 menyatakan tidak ada header, tetapi student finance (tenant.access) dan akun multi-keanggotaan membutuhkannya; tidak ada endpoint publik penemuan universitas.
2. Flag academic.lecturer_ownership default OFF: dosen dapat membaca/menulis nilai, absensi, ujian kelas mana pun di tenant. Apakah backend akan mengaktifkannya sebelum rilis, atau klien wajib memfilter kelas (MOB-AUTH-22)?
3. Strategi cache offline: D01 (hanya data referensi, tanpa data transaksional) bertentangan dengan academic-learning 09 (cache jadwal/KHS/transkrip, antrean jawaban ujian). Mana yang berlaku?
4. Push FCM: kontrak endpoint token (POST/DELETE /devices push-token), driver FCM, dan aktivasi kanal push belum ada; siapa pemilik dan kapan?
5. Domain resmi App Link (app.civitasone.example placeholder) dan format tautan reset password/aktivasi ke aplikasi belum ditetapkan; email mahasiswa placeholder (@student.<univ>.local) tidak menerima reset.
6. Endpoint mahasiswa bimbingan untuk dosen PA: apakah GET /students mendukung filter academic_advisor_id (belum terverifikasi) atau perlu /advising/students?
7. Apakah ZH (Mandarin) menjadi cakupan aplikasi? Hanya prototype one/ yang memilikinya; D01 hanya ID/EN.
8. must_change_password tidak ditegakkan server dan token tidak kedaluwarsa/dicabut saat ganti sandi: apakah backend akan menambahkannya?
9. Izin PATCH /student/profile: C7 mencatat student_portal.read, kode C2 student_portal.update; keduanya dimiliki role student, tetapi perlu konfirmasi bila role disesuaikan.

## Matriks Fitur G2 - Akademik, KRS, Jadwal (Civitas One Android)

Grup: G2-academic-krs-schedule. Domain: academic_records (ACAD), krs_registration (KRS), schedule_calendar (SCHED). Status berdasarkan kode (rute `app/Modules/*/Routes/api.php`, catatan C2/C3/C7) dan `docs/STATUS-IMPLEMENTASI.md` 17 Okt 2026; kode menang atas dokumen.

Catatan global: semua unduhan PDF butuh Bearer token; semua 409 membawa pesan Indonesia (`message`); hanya `SCHEDULE_CONFLICT` dan `ACADEMIC_BLOCKED` punya `errors.code`. Status mobile saat ini `none` untuk semua baris (aplikasi baru punya login, sesi keamanan, dasbor placeholder, ujian dosen).

### Domain academic_records (ACAD)

Portal akademik mahasiswa (ringkasan, nilai, KHS, transkrip, dokumen PDF, profil) sudah lengkap di backend dan web. Sisi dosen: daftar kelas, roster, pencarian mahasiswa, transkrip ringkas. Celah utama: gerbang cekal/EDOM untuk nilai, publikasi nilai, komponen nilai, dan dokumen resmi ber-QR.

#### MOB-ACAD-01 - Lihat ringkasan akademik mahasiswa
- **Peran**: mahasiswa
- **Deskripsi**: Kartu ringkasan: semester berjalan, IPK, IPS terakhir, SKS total/lulus/sisa, SKS KRS semester ini, batas SKS, status mahasiswa, dosen PA, kurikulum dan program studi.
- **Aturan bisnis**:
  - semester = (tahun mulai term - admission_year)*2 + (ganjil?1:2), minimum 1 (kode)
  - Status: active/leave/graduated/inactive/dropped_out/resigned (label Aktif, Cuti, Lulus, Nonaktif, Drop Out, Mengundurkan Diri); inactive/dropped_out/resigned tidak bisa login (kode). Dokumen D02 menyebut daftar status lain (Calon mahasiswa, Pindah, Meninggal dunia) yang TIDAK ada di enum kode.
  - IPK/IPS memakai nilai terbaik untuk MK ulang; bobot A4.0 AB3.5 B3.0 BC2.5 C2.0 D1.0 E0.0
- **Kriteria penerimaan/pengujian**:
  - Given mahasiswa login When membuka Akademik Saya Then IPK, total SKS, SKS lulus, semester, status dan dosen PA tampil sesuai GET /student/academic-summary
  - Given kurikulum tanpa total_credits When progres studi dirender Then bar progres disembunyikan dengan catatan, bukan error
  - Given mahasiswa tanpa nilai Then ipk=0 dan tidak ada error (TRX-03)
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/academic-summary`
- **Celah backend**: tidak ada
- **Status web**: pages/portal/PortalAcademicPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Autentikasi (G1); Router/shell Flutter (C9)
- **Sumber**: `AUDIT/D03-role-accounts-status.md#STATUS §6`; `AUDIT/D02-app-design.md`; `AUDIT/C2-be-academic-student.md#3`; `AUDIT/C7-fe-portals.md#3.3`
- **Catatan**: Doc D02 memetakan fitur 'status akademik' sebagai MUST; kode sudah lengkap.

#### MOB-ACAD-02 - Lihat riwayat akademik (timeline masuk, semester, perubahan status, kelulusan)
- **Peran**: mahasiswa
- **Deskripsi**: Timeline kronologis admission/term/status/graduation dengan judul dan deskripsi.
- **Aturan bisnis**:
  - Riwayat status dicatat otomatis (StudentObserver)
  - Diurutkan menaik berdasarkan tanggal, tidak dipaginasi
- **Kriteria penerimaan/pengujian**:
  - Given perubahan status mahasiswa (mis. cuti) When timeline dibuka Then entri tipe status muncul berurutan tanggal
  - Given riwayat kosong Then tampilan kosong yang jelas
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/academic-history`
- **Celah backend**: tidak ada
- **Status web**: pages/portal/PortalAcademicPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-ACAD-01
- **Sumber**: `AUDIT/D03-role-accounts-status.md`; `AUDIT/D02-app-design.md (student_status_histories)`; `AUDIT/C2-be-academic-student.md#3`

#### MOB-ACAD-03 - Lihat nilai seluruh mata kuliah per semester
- **Peran**: mahasiswa
- **Deskripsi**: Daftar nilai dikelompokkan per semester: skor, huruf, bobot, mutu, IPS per semester, IPK, SKS total dan lulus. MK belum dinilai tampil 'Belum dinilai'.
- **Aturan bisnis**:
  - Skala huruf KODE (hardcoded LetterGrade): >=85 A; >=75 AB; >=65 B; >=55 BC; >=45 C; >=35 D; <35 E; lulus minimal C (bobot 2.0)
  - Hanya item KRS berstatus Enrolled; item dropped tidak dihitung
  - Doc D02 memberi contoh skala lain (A- dst, 85-100 A 4.00) dan skala dapat dikonfigurasi admin: TIDAK berlaku; kode memakai skala 7 tingkat hardcoded
  - MK ulang: nilai terbaik dihitung untuk IPK
  - Kode saat ini TIDAK menyaring is_published (belum ada fitur publikasi nilai) dan TIDAK menerapkan cekal view_grades
- **Kriteria penerimaan/pengujian**:
  - Given mahasiswa punya 2 semester bernilai When membuka Nilai Then dua kartu semester dengan IPS dan IPK sama dengan respons API
  - Given letter_grade null Then label 'Belum dinilai'
  - Given IP dihitung manual (A 4sks + B 2sks) Then IPS=3.33 (TRX-02)
  - Warna lencana: A/AB sukses, B/BC info, C peringatan, D/E bahaya
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/grades`
- **Celah backend**: Respons penuh tanpa filter semester/paginasi (C2 gap); Tidak ada publikasi nilai (is_published) maupun komponen nilai; Cekal/EDOM gate belum ditegakkan (lihat MOB-ACAD-09)
- **Status web**: pages/portal/PortalGradesPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-ACAD-01
- **Sumber**: `AUDIT/D03-role-accounts-status.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/D01-flutter-design.md (PortalGradesScreen)`; `AUDIT/C2-be-academic-student.md#5`

#### MOB-ACAD-04 - Lihat KHS per semester (IPS, SKS, rincian nilai)
- **Peran**: mahasiswa
- **Deskripsi**: Pilih semester lalu lihat ringkasan (SKS semester/diambil, IPS, IPK s.d. semester itu, SKS kumulatif) dan tabel nilai per MK.
- **Aturan bisnis**:
  - IPS = sum(SKS x bobot)/sum(SKS) dibulatkan 2 desimal; hanya MK yang sudah dinilai (all_graded=false -> tampilkan peringatan 'sebagian MK belum dinilai')
  - Default semester: term terakhir yang punya nilai, bila tidak ada term terakhir
  - academic_term_id tak dikenal -> 409 'Semester tidak ditemukan pada riwayat studi Anda.'
  - Tanpa KRS enrolled: data {terms:[],term:null,rows:[],summary:null}
- **Kriteria penerimaan/pengujian**:
  - Given dropdown semester When memilih semester lain Then KHS di-refetch dengan academic_term_id baru
  - Given IPS dihitung Then selaras dengan rumus dan 2 desimal
  - Given belum ada KRS Then tampil 'KHS belum tersedia.'
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/khs?academic_term_id=`
- **Celah backend**: Belum ada gerbang EDOM (403 edom_incomplete, D12 desain) maupun publikasi nilai
- **Status web**: pages/portal/PortalKhsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-ACAD-03
- **Sumber**: `AUDIT/D01-flutter-design.md (PortalKhsScreen)`; `AUDIT/D02-app-design.md (MVP #13)`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/C2-be-academic-student.md#5`

#### MOB-ACAD-05 - Lihat transkrip sementara dengan IPK
- **Peran**: mahasiswa
- **Deskripsi**: Transkrip per semester: kode MK, SKS, bobot, mutu, huruf, penanda 'Diulang Nx'; ringkasan IPK, total SKS, SKS lulus, jumlah MK. Header harus menyatakan dokumen tidak resmi.
- **Aturan bisnis**:
  - Satu baris per MK (percobaan terbaik); attempts>1 -> lencana 'Diulang Nx'
  - IPK = total mutu / total SKS semua semester
  - Transkrip resmi dikeluarkan BAAK lewat permohonan (D02 4.25) - mobile hanya transkrip sementara
  - Transkrip resmi dapat diblokir cekal 'transcript' (desain), kode belum menegakkan
- **Kriteria penerimaan/pengujian**:
  - Given mahasiswa tanpa nilai Then rows kosong, ipk=0, tanpa error
  - Given MK diulang Then hanya nilai terbaik dihitung dan atribut attempts tampil
  - Given header Then ada label 'Transkrip Sementara - bukan dokumen resmi'
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/transcript`
- **Celah backend**: Belum ada penanda Konversi/Pemutihan, attempt_number, counts_toward_ipk (desain D26/D15)
- **Status web**: pages/portal/PortalTranscriptPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-ACAD-03
- **Sumber**: `AUDIT/D01-flutter-design.md (PortalTranscriptScreen)`; `AUDIT/D02-app-design.md (MVP #14)`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D06-exam-docs-videos.md (TRX-01..05)`; `AUDIT/C2-be-academic-student.md#5`
- **Catatan**: D01 mengusulkan endpoint /portal/transcript; kode sebenarnya GET /student/transcript. Note: bentuk respons (rows/summary/terms) sedikit berbeda dari klaim D03 (terms,ipk,total_sks pada /students/{id}/transcript).

#### MOB-ACAD-06 - Daftar dokumen akademik yang dapat diunduh
- **Peran**: mahasiswa
- **Deskripsi**: Daftar PDF (KRS per semester, KHS per semester ber-KRS enrolled, satu transkrip, surat disetujui) dengan judul, deskripsi, url relatif dan nama berkas.
- **Aturan bisnis**:
  - type: krs|khs|transcript|letter
  - Setiap unduhan butuh Bearer token (tidak bisa dibuka via URL publik) - gunakan klien HTTP + simpan ke berkas lalu buka/bagikan
- **Kriteria penerimaan/pengujian**:
  - Given dokumen tersedia When membuka Dokumen Then daftar sesuai GET /student/documents dan tombol Unduh memakai url dari API
  - Given daftar kosong Then 'Belum ada dokumen yang tersedia.'
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/documents`
- **Celah backend**: tidak ada
- **Status web**: pages/portal/PortalDocumentsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Flutter: path_provider, open_filex/share_plus; MOB-ACAD-07..08; MOB-KRS-13
- **Sumber**: `AUDIT/D03-role-accounts-status.md (Unduh PDF)`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/C2-be-academic-student.md#2`

#### MOB-ACAD-07 - Unduh KHS PDF
- **Peran**: mahasiswa
- **Deskripsi**: Unduh KHS satu semester sebagai PDF (format kampus).
- **Aturan bisnis**:
  - 404 bila tidak ada KRS Enrolled pada semester itu
  - Nama berkas khs-<nim>-<term>.pdf
  - Dokumen PDF tidak membawa QR/TTE (QR validasi hanya desain D02/D07)
- **Kriteria penerimaan/pengujian**:
  - Given semester dengan KRS enrolled When Unduh PDF Then berkas application/pdf tersimpan dan dapat dibuka
  - Given semester tanpa KRS enrolled Then pesan galat ramah, bukan crash
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/documents/khs/{academicTerm}`
- **Celah backend**: QR validasi + tanda tangan elektronik belum ada
- **Status web**: pages/portal/PortalKhsPage.tsx / PortalDocumentsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-ACAD-04; Flutter: path_provider, open_filex/share_plus
- **Sumber**: `AUDIT/D03-role-accounts-status.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/C2-be-academic-student.md#2`

#### MOB-ACAD-08 - Unduh transkrip PDF
- **Peran**: mahasiswa
- **Deskripsi**: Unduh transkrip (sementara) sebagai PDF.
- **Aturan bisnis**:
  - 409 'Transkrip belum tersedia karena belum ada nilai.' bila tak ada baris bernilai
  - Dapat ditolak server (cekal/clearance) dengan pesan - tampilkan pesan server apa adanya
  - Nama berkas transkrip-<nim>.pdf
- **Kriteria penerimaan/pengujian**:
  - Given ada nilai When Unduh Then PDF tersimpan
  - Given belum ada nilai Then pesan 409 ditampilkan
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/documents/transcript`
- **Celah backend**: Cekal 'transcript' belum ditegakkan di kode saat ini; QR/TTE belum ada
- **Status web**: pages/portal/PortalTranscriptPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-ACAD-05; Flutter: path_provider, open_filex/share_plus
- **Sumber**: `AUDIT/D01-flutter-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/C2-be-academic-student.md#2`

#### MOB-ACAD-09 - Gerbang akses nilai/transkrip (cekal keuangan, EDOM)
- **Peran**: mahasiswa
- **Deskripsi**: Penyembunyian nilai semester atau pemblokiran transkrip bila ada cekal (view_grades, transcript) atau EDOM belum diisi; backend mengembalikan 403/409 dengan daftar penyebab.
- **Aturan bisnis**:
  - Desain: 403 errors.gate=edom_incomplete + pending_targets + closes_at (D12); alternatif 409 EDOM_REQUIRED (D14 OD-10); 409 ACADEMIC_BLOCKED + errors.blocks[] (D19)
  - gate_enabled default false per tenant
  - Tidak membatasi: transkrip resmi, IPS/IPK, KRS berikutnya, akses dosen
  - KODE: ENFORCED_SERVICES = krs, exam saja
- **Kriteria penerimaan/pengujian**:
  - Given gerbang aktif dan EDOM belum diisi When membuka KHS Then layar menampilkan daftar target EDOM tertunda, bukan data kosong
  - Given 409 ACADEMIC_BLOCKED Then pesan cekal + cara penyelesaian tampil dan aksi dinonaktifkan
- **Status backend**: `missing`
- **Endpoint**: tidak ada
- **Celah backend**: Tidak ada penegakan cekal view_grades/transcript (AcademicBlockService::ENFORCED_SERVICES); Tidak ada modul EDOM dan gerbangnya; Nama endpoint/kode galat belum final (403 edom_incomplete vs 409 EDOM_REQUIRED)
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_contract_pending_backend (label 'Menunggu backend')
- **Dependensi**: Backend: EDOM (D12), cekal engine (C4); MOB-KRS-11
- **Sumber**: `AUDIT/D12-al-obe-edom-arch.md`; `AUDIT/D14-al-api-roles.md`; `AUDIT/D04-keu-master.md`; `AUDIT/D19-keu-11-18.md`; `AUDIT/C2-be-academic-student.md#5`
- **Catatan**: Mobile cukup menangani galat 403/409 generik pada layar nilai agar siap saat backend menambah gerbang.

#### MOB-ACAD-10 - Detail nilai per komponen (tugas, UTS, UAS, dsb.)
- **Peran**: mahasiswa
- **Deskripsi**: Rincian komponen nilai setelah dipublikasikan.
- **Aturan bisnis**:
  - Hanya setelah nilai dipublikasikan (desain)
  - Akses komponen untuk dosen wali bergantung OD-28
- **Kriteria penerimaan/pengujian**:
  - Given nilai terpublikasi When membuka detail MK Then komponen tampil
- **Status backend**: `missing`
- **Endpoint**: tidak ada
- **Celah backend**: Tidak ada komponen nilai/publikasi nilai di backend; endpoint desain GET /student/grades/{krsItem}/components
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: Backend: grade components (D14)
- **Sumber**: `AUDIT/D14-al-api-roles.md`; `AUDIT/C3-be-academic-lecturer.md#5`

#### MOB-ACAD-11 - Lihat konversi SKS/transfer kredit dan penanda MK ulang/pemutihan pada transkrip
- **Peran**: mahasiswa
- **Deskripsi**: Baris konversi/transfer-in dan penanda Konversi/Mengulang/Pemutihan pada transkrip dan KHS.
- **Aturan bisnis**:
  - Total SKS pengakuan <= batas konfigurasi; kaprodi menyetujui; konversi yang sudah diterapkan immutable (D16)
  - Hanya jumlah percobaan ('Diulang Nx') yang ada di kode
- **Kriteria penerimaan/pengujian**:
  - Given konversi diterapkan Then muncul di transkrip dengan penanda
- **Status backend**: `missing`
- **Endpoint**: tidak ada
- **Celah backend**: Tabel credit_conversion_items belum ada; tidak ada pemutihan/kebijakan ulang yang dapat diatur
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: Backend: credit conversion
- **Sumber**: `AUDIT/D13-al-erd.md`; `AUDIT/D16-al-reports-sec-tests-roadmap.md`; `AUDIT/D15-al-uiux-rules.md`

#### MOB-ACAD-12 - Dokumen resmi kelulusan (SK, transkrip final, SKL, SKPI, ijazah) dengan QR
- **Peran**: mahasiswa
- **Deskripsi**: Unduh dokumen resmi terbit dengan kode QR verifikasi dan TTE.
- **Aturan bisnis**:
  - Snapshot dan berkas immutable setelah terbit (KEL-BR-063)
  - Dokumen dicabut/diganti tetap dapat diverifikasi
- **Kriteria penerimaan/pengujian**:
  - Given dokumen terbit When mahasiswa mengunduh Then PDF dengan QR tersimpan (KEL-AC-16/17)
- **Status backend**: `missing`
- **Endpoint**: tidak ada
- **Celah backend**: GET /official-documents/{document}/file belum ada; TTE/QR belum ada
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: Backend: kelulusan/official documents; TTE (SEVIMA Business)
- **Sumber**: `AUDIT/D23-kk-10-18.md`; `AUDIT/D05-sdm-superadmin-sevima.md`; `AUDIT/D07-sevima-pmb-design.md`
- **Catatan**: Termasuk domain kemahasiswaan-kelulusan; hanya dicatat di sini karena ada pada bundel academic_records.

#### MOB-ACAD-13 - Lihat profil mahasiswa dan ubah data terbatas
- **Peran**: mahasiswa
- **Deskripsi**: Profil (nama, NIM, kontak, alamat, foto, ringkasan akademik). Hanya phone, address, photo yang dapat diubah langsung; data lain lewat pengajuan data_change.
- **Aturan bisnis**:
  - phone regex /^\+?[0-9\s\-()]{6,30}$/; address <=500; photo jpg/jpeg/png <=2 MB melalui POST /file-uploads lalu photo_file_id
  - name, email, birth_place, tanggal_lahir, gender read-only -> request type data_change
- **Kriteria penerimaan/pengujian**:
  - Given PATCH phone valid Then 200 dan profil terbarui
  - Given foto >2MB Then 422 'Ukuran berkas melebihi batas 2 MB.'
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/profile`; `PATCH /api/v1/student/profile`; `POST /api/v1/file-uploads`; `POST /api/v1/student/requests (type data_change)`
- **Celah backend**: tidak ada
- **Status web**: pages/portal/PortalProfilePage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Upload berkas (C2 sec 10); Flutter: image_picker
- **Sumber**: `AUDIT/D08-proposal-pdf.md (profil)`; `AUDIT/C2-be-academic-student.md#3`
- **Catatan**: Kemungkinan tumpang tindih dengan grup profil/layanan mahasiswa; diverifikasi pada routes.php:142,153.

#### MOB-ACAD-14 - Ajukan cuti studi dan layanan administrasi akademik
- **Peran**: mahasiswa
- **Deskripsi**: Pengajuan cuti/aktif kembali/perubahan data/surat/lainnya beserta status dan unduh surat.
- **Aturan bisnis**:
  - type: leave|reactivation|data_change|letter|other; status draft/submitted/approved/rejected/cancelled
- **Kriteria penerimaan/pengujian**:
  - Given draft pengajuan cuti When disubmit Then status submitted dan muncul di daftar
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/requests`; `GET /api/v1/student/requests/options`; `POST /api/v1/student/requests`; `PUT /api/v1/student/requests/{id}`; `POST /api/v1/student/requests/{id}/submit`; `POST /api/v1/student/requests/{id}/cancel`; `GET /api/v1/student/requests/{id}/letter`
- **Celah backend**: tidak ada
- **Status web**: pages/portal/PortalRequestsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Dicakup grup layanan mahasiswa - jangan implementasi ganda
- **Sumber**: `AUDIT/D07-sevima-pmb-design.md (cuti studi)`; `AUDIT/C2-be-academic-student.md#8`; `AUDIT/C7-fe-portals.md#3`
- **Catatan**: Hanya dirujuk di sini; implementasi utama di grup lain (student_requests).

#### MOB-ACAD-15 - Laporan mandiri mahasiswa (katalog laporan akademik)
- **Peran**: mahasiswa
- **Deskripsi**: Katalog laporan self-view (status, rekap KRS, jadwal, presensi, nilai, IPS/IPK, transkrip sementara, cekal, aktivitas LMS, hasil ujian).
- **Aturan bisnis**:
  - Server menghapus kolom tak berwenang (BR-SEC-RPT-02)
- **Kriteria penerimaan/pengujian**:
  - Given tipe laporan Then hanya kolom berizin yang dikembalikan
- **Status backend**: `missing`
- **Endpoint**: tidak ada
- **Celah backend**: GET /academic-reports belum ada; hanya /reports legacy
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: Backend: academic reports
- **Sumber**: `AUDIT/D16-al-reports-sec-tests-roadmap.md`

#### MOB-ACAD-16 - Katalog program studi, mata kuliah, dan prasyarat
- **Peran**: dosen, mahasiswa
- **Deskripsi**: Dosen: daftar/detail prodi, MK (filter kurikulum/semester) dan prasyarat. Mahasiswa: tidak ada endpoint katalog; prasyarat hanya terlihat lewat penawaran KRS.
- **Aturan bisnis**:
  - Dosen punya study_programs.read dan courses.read tetapi TIDAK curriculums.read
  - Mahasiswa tidak punya courses.read; prasyarat per MK (min_grade default C) hanya via /student/krs/offerings
- **Kriteria penerimaan/pengujian**:
  - Given dosen When membuka MK Then daftar dengan filter semester_level dan kurikulum
  - Given dosen membuka prasyarat MK Then daftar prasyarat tampil
- **Status backend**: `partial`
- **Endpoint**: `GET /api/v1/study-programs`; `GET /api/v1/courses`; `GET /api/v1/courses/{id}`; `GET /api/v1/courses/{id}/prerequisites`; `GET /api/v1/student/krs/offerings (mahasiswa, prasyarat saja)`
- **Celah backend**: Tidak ada katalog kurikulum/MK bagi mahasiswa; Dosen tidak punya curriculums.read; Ekuivalensi MK dan kurikulum_courses belum ada
- **Status web**: pages/academic/StudyProgramsPage.tsx, CoursesPage.tsx (dosen); mahasiswa: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Dipakai sebagai pemilih kelas/MK
- **Sumber**: `AUDIT/D06-exam-docs-videos.md (AKM-08/11/12/13)`; `AUDIT/D07-sevima-pmb-design.md`; `AUDIT/D13-al-erd.md`; `AUDIT/C3-be-academic-lecturer.md`

#### MOB-ACAD-17 - Dosen: cari dan lihat daftar serta detail mahasiswa
- **Peran**: dosen
- **Deskripsi**: Daftar mahasiswa (cari nama/NIM; filter status, angkatan, prodi, kelas) dan detail baca-saja.
- **Aturan bisnis**:
  - Index paginasi default per_page 20; filter whitelisted
  - Tidak dibatasi ke mahasiswa kelas sendiri (kode); POST /students tidak ada (read-only)
  - Peran academic_advisor murni TIDAK punya students.read
- **Kriteria penerimaan/pengujian**:
  - Given pencarian 'budi' Then hasil memuat nama/NIM cocok (AKM-01)
  - Given id tak ada Then 404 (AKM-04)
  - Given peran advisor-only Then 403
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/students`; `GET /api/v1/students/{id}`
- **Celah backend**: Tidak ada filter academic_advisor_id; tidak ada pembatasan kepemilikan (privasi data seluruh mahasiswa tenant)
- **Status web**: pages/academic/StudentsPage.tsx, StudentDetailPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Perlu keputusan privasi (unresolved)
- **Sumber**: `AUDIT/D03-role-accounts-status.md`; `AUDIT/D06-exam-docs-videos.md (AKM-01..05)`; `AUDIT/C3-be-academic-lecturer.md`

#### MOB-ACAD-18 - Dosen: lihat transkrip ringkas mahasiswa
- **Peran**: dosen, dosen PA
- **Deskripsi**: Ringkasan per semester (SKS, IP), IPK, total dan SKS lulus untuk satu mahasiswa.
- **Aturan bisnis**:
  - IP per semester = sum(sks x bobot)/sum(sks), 2 desimal; dropped tidak dihitung; tanpa nilai -> terms [], ipk 0 (TRX-01..04)
  - Butuh students.read: peran lecturer ya; academic_advisor murni TIDAK (TRX-05 403)
- **Kriteria penerimaan/pengujian**:
  - Given dua semester Then IP semester dan IPK benar (TRX-01/02)
  - Given tanpa permission Then 403 (TRX-05)
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/students/{id}/transcript`
- **Celah backend**: Dosen PA murni tidak bisa; tidak ada pembatasan hanya mahasiswa bimbingan; tidak ada KHS per semester untuk dosen
- **Status web**: pages/academic/StudentDetailPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-ACAD-17; MOB-KRS-27
- **Sumber**: `AUDIT/D03-role-accounts-status.md`; `AUDIT/D06-exam-docs-videos.md`; `AUDIT/C3-be-academic-lecturer.md`
- **Catatan**: Berlaku bagi dosen yang juga PA karena seeder memberi role lecturer dan academic_advisor.

#### MOB-ACAD-19 - Dosen: daftar kelas yang diampu (dengan jadwal)
- **Peran**: dosen
- **Deskripsi**: Daftar kelas semester berjalan (atau term terpilih) dengan MK, kode kelas, jadwal mingguan, kapasitas, jumlah terdaftar, jumlah materi/tugas.
- **Aturan bisnis**:
  - Selalu dibatasi lecturer_id sendiri kecuali punya classes.update
  - Query academic_term_id, search, per_page 1-100; respons terpaginasi
  - Alternatif GET /class-sections (classes.read) tidak dibatasi kecuali flag academic.lecturer_ownership aktif
  - Tim pengajar (koordinator/anggota) belum ada: satu dosen per kelas
- **Kriteria penerimaan/pengujian**:
  - Given dosen login When membuka Kelas Saya Then hanya kelas miliknya yang tampil
  - Given term lain dipilih Then daftar kelas term itu
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/teaching/classes`; `GET /api/v1/class-sections`; `GET /api/v1/class-sections/{id}`
- **Celah backend**: Tidak ada team teaching (class_section_lecturers); Halaman web 'Kelas Saya' belum ada
- **Status web**: none (nav 'Kelas Saya' tanpa halaman); kelas admin: pages/academic/ClassSectionsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Dipakai grup ujian/presensi/nilai (pemilih kelas); MOB-ACAD-20
- **Sumber**: `AUDIT/D14-al-api-roles.md`; `AUDIT/D13-al-erd.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/C3-be-academic-lecturer.md#2`; `AUDIT/C7-fe-portals.md#2`
- **Catatan**: Pemilih kelas ini juga menutup masalah 'ID Kelas' berupa teks mentah pada wizard ujian mobile (C9).

#### MOB-ACAD-20 - Dosen: roster peserta kelas
- **Peran**: dosen
- **Deskripsi**: Daftar mahasiswa terdaftar di satu kelas (nama, NIM, status KRS), memuat krs_item_id untuk presensi/nilai.
- **Aturan bisnis**:
  - Sumber: GET /krs-items?filter[class_section_id]&filter[status]=enrolled (krs.read) atau GET /students?filter[class_section_id]
  - Filter status enrolled|dropped; tidak ada pembatasan kepemilikan pada krs-items
- **Kriteria penerimaan/pengujian**:
  - Given kelas dengan 3 peserta enrolled Then 3 baris dengan krs_item_id
  - Given peserta drop Then tidak tampil pada filter enrolled
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/krs-items`; `GET /api/v1/students?filter[class_section_id]=`
- **Celah backend**: Tidak ada endpoint roster khusus per kelas dengan kepemilikan
- **Status web**: pages/academic/ClassSectionDetailPage.tsx, KrsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-ACAD-19
- **Sumber**: `AUDIT/D03-role-accounts-status.md`; `AUDIT/D06-exam-docs-videos.md (KRS-11, AKM-09)`; `AUDIT/C3-be-academic-lecturer.md`

#### MOB-ACAD-21 - Notifikasi nilai terbit/direvisi
- **Peran**: mahasiswa
- **Deskripsi**: Notifikasi saat nilai dipublikasikan atau direvisi.
- **Aturan bisnis**:
  - Event student.grade_published / student.grade_revised (desain D15)
  - KODE: dosen yang menginput nilai tidak memicu notifikasi ke mahasiswa; belum ada publish/lock nilai (C3 gap 5)
- **Kriteria penerimaan/pengujian**:
  - Given nilai dipublikasikan Then mahasiswa menerima notifikasi
- **Status backend**: `missing`
- **Endpoint**: tidak ada
- **Celah backend**: Belum ada publikasi nilai dan notifikasi terkait
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_contract_pending_backend (label 'Menunggu backend')
- **Dependensi**: Backend: publikasi nilai; Infrastruktur push
- **Sumber**: `AUDIT/D15-al-uiux-rules.md`; `AUDIT/C3-be-academic-lecturer.md#5`

### Domain krs_registration (KRS)

Swalayan KRS mahasiswa (penawaran, tambah/hapus, ajukan, tarik, riwayat, PDF) dan persetujuan dosen PA (antrean, setujui, tolak) sudah ada di backend; web punya halaman mahasiswa tetapi halaman Persetujuan KRS dosen belum ada. Belum ada: status revision_requested/locked, add/drop, perwalian (daftar advisee, catatan, risiko), semester pendek, endpoint clearance mahasiswa.

#### MOB-KRS-01 - Lihat KRS semester berjalan
- **Peran**: mahasiswa
- **Deskripsi**: Ikhtisar KRS term berjalan: status, daftar kelas terpilih (draft/pending/enrolled), total SKS, batas SKS, sisa SKS, periode KRS, dosen wali, catatan keputusan, dan notices alasan KRS tidak dapat diedit.
- **Aturan bisnis**:
  - status: no_active_term|not_started|draft|submitted|approved|rejected (KODE). Status revision_requested/locked/cancelled hanya desain
  - can_edit = notices kosong; notices mencakup: mahasiswa tidak Active, periode KRS tutup/belum buka, cekal krs, sudah diajukan, sudah disetujui (terkunci)
  - Item status draft|pending|enrolled (dropped tak tampil)
- **Kriteria penerimaan/pengujian**:
  - Given periode KRS belum dibuka When membuka KRS Then notices menjelaskan tanggal buka dan tombol aksi nonaktif
  - Given KRS disetujui Then status approved dan tak dapat diedit
  - Given mahasiswa non-Active Then notices menyatakan sebab
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/krs`
- **Celah backend**: Belum ada blockers[] terstruktur (G1-G5 desain D10) - hanya notices teks; Belum ada status revision_requested/locked
- **Status web**: pages/portal/PortalKrsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-02..06
- **Sumber**: `AUDIT/D01-flutter-design.md (PortalKrsScreen)`; `AUDIT/D10-al-siakad.md`; `AUDIT/D14-al-api-roles.md`; `AUDIT/C2-be-academic-student.md#7`; `AUDIT/C7-fe-portals.md#3.4`
- **Catatan**: D01 menyebut 'tidak ada backend untuk mahasiswa' dan enum hanya enrolled/dropped: usang. Kode: swalayan KRS lengkap.

#### MOB-KRS-02 - Lihat penawaran kelas KRS dengan pemeriksaan kelayakan
- **Peran**: mahasiswa
- **Deskripsi**: Daftar MK ditawarkan (prodi sama, term berjalan, kelas aktif) dengan prasyarat (min_grade, satisfied), nilai terbaik, penanda mengulang, blockers, kelas (kapasitas, kursi terpakai/sisa, penuh, konflik jadwal).
- **Aturan bisnis**:
  - Ditawarkan = kelas term berjalan, prodi mahasiswa, aktif, MK aktif; urut semester_level lalu kode
  - Kursi = draft+pending+enrolled vs kapasitas
  - Pencarian MK client-side; ekspansi kartu MK menampilkan prasyarat dan kelas
- **Kriteria penerimaan/pengujian**:
  - Given prasyarat belum lulus Then MK berlabel 'Tidak dapat diambil' beserta prasyarat merah
  - Given kelas penuh Then 'Kelas penuh' dan tombol pilih nonaktif
  - Given dua kelas bentrok Then konflik jadwal tampil sebelum dipilih
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/krs/offerings`
- **Celah backend**: Penawaran lintas prodi/MBKM dan paket MK belum ada
- **Status web**: pages/portal/PortalKrsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-01
- **Sumber**: `AUDIT/D03-role-accounts-status.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/D10-al-siakad.md (R1-R7)`; `AUDIT/C2-be-academic-student.md#7`

#### MOB-KRS-03 - Tambah kelas ke draft KRS
- **Peran**: mahasiswa
- **Deskripsi**: Memilih satu kelas dari penawaran dan menambahkannya ke draft KRS; server memvalidasi semua aturan dan mengembalikan ikhtisar terbaru.
- **Aturan bisnis**:
  - Body {class_section_id} (wajib, maks 26 karakter); sukses 201 'Mata kuliah ditambahkan ke KRS.'
  - 409 ConflictException bila: kelas tidak tersedia, mahasiswa tak Active, periode tutup, ACADEMIC_BLOCKED, sudah diajukan/disetujui, MK sama sudah ada, MK sudah lulus (>=C), prasyarat belum terpenuhi (min grade default C), kelas penuh, SKS melebihi batas, SCHEDULE_CONFLICT
  - errors.code hanya diisi untuk SCHEDULE_CONFLICT (errors.conflicts[]) dan ACADEMIC_BLOCKED (errors.blocks[]); lainnya hanya pesan teks Indonesia
  - Sentuhan jam selesai=jam mulai bukan bentrok
  - Mahasiswa tak pernah mengirim student_id
- **Kriteria penerimaan/pengujian**:
  - Given kelas valid When Tambah Then 201 dan total_credits bertambah (TS-SIA-01)
  - Given prasyarat belum lulus Then 409 dengan pesan, UI tetap konsisten (TS-SIA-02)
  - Given dua klien berebut kursi terakhir Then satu sukses, satu 409 'penuh' (TS-SIA-06)
- **Status backend**: `available`
- **Endpoint**: `POST /api/v1/student/krs/items`
- **Celah backend**: Kode galat bertipe untuk PREREQUISITE_NOT_MET/CLASS_FULL/CREDIT_LIMIT_EXCEEDED/PERIOD_CLOSED belum ada (hanya pesan teks); Tidak ada Idempotency-Key
- **Status web**: pages/portal/PortalKrsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-02
- **Sumber**: `AUDIT/D01-flutter-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D06-exam-docs-videos.md (KRS-01..13)`; `AUDIT/D10-al-siakad.md`; `AUDIT/D16-al-reports-sec-tests-roadmap.md (TS-SIA-01..06)`; `AUDIT/C2-be-academic-student.md#7`
- **Catatan**: Mobile harus menampilkan 'message' server apa adanya; jangan menebak kode galat selain SCHEDULE_CONFLICT dan ACADEMIC_BLOCKED.

#### MOB-KRS-04 - Hapus kelas dari draft KRS
- **Peran**: mahasiswa
- **Deskripsi**: Menghapus item draft dari KRS.
- **Aturan bisnis**:
  - Hanya status draft; item pending/enrolled -> 409 'Mata kuliah ini sudah diajukan atau disetujui...'
  - Item milik orang lain -> 404 (policy viewOwn); tombol Hapus hanya bila can_edit
- **Kriteria penerimaan/pengujian**:
  - Given item draft When Hapus Then 200 'Mata kuliah dihapus dari KRS.' dan ikhtisar diperbarui
  - Given KRS sudah diajukan Then Hapus tersembunyi
- **Status backend**: `available`
- **Endpoint**: `DELETE /api/v1/student/krs/items/{krsItem}`
- **Celah backend**: tidak ada
- **Status web**: pages/portal/PortalKrsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-01
- **Sumber**: `AUDIT/D14-al-api-roles.md`; `AUDIT/C2-be-academic-student.md#7`

#### MOB-KRS-05 - Ajukan KRS ke dosen wali
- **Peran**: mahasiswa
- **Deskripsi**: Mengirim draft KRS untuk disetujui dosen PA; draft menjadi pending, submission submitted; dosen PA diberi notifikasi.
- **Aturan bisnis**:
  - Wajib ada minimal satu draft ('Belum ada mata kuliah di KRS Anda...'); total SKS <= batas; bentrok dicek ulang
  - Konfirmasi: 'Setelah diajukan, KRS tidak dapat diubah kecuali Anda menarik kembali selama periode KRS dibuka'
  - Tidak ada SKS minimum di kode (min_sks default 0 hanya desain)
  - Penjelasan semua pelanggaran sekaligus (409 KRS_VALIDATION_FAILED errors.violations[]) hanya DESAIN D14; kode memberi pesan tunggal
- **Kriteria penerimaan/pengujian**:
  - Given draft valid When Ajukan Then status submitted, item pending
  - Given draft kosong Then 409 dengan pesan dan UI tidak berubah
  - Given ACADEMIC_BLOCKED Then banner cekal dan aksi dinonaktifkan
- **Status backend**: `available`
- **Endpoint**: `POST /api/v1/student/krs/submit`
- **Celah backend**: Daftar pelanggaran lengkap (violations[]) belum ada; Idempotency-Key belum ada; Tidak ada validasi 'dry-run' (lihat MOB-KRS-16)
- **Status web**: pages/portal/PortalKrsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-03; MOB-KRS-24 (notifikasi)
- **Sumber**: `AUDIT/D03-role-accounts-status.md`; `AUDIT/D10-al-siakad.md (S1/S2)`; `AUDIT/D14-al-api-roles.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/C2-be-academic-student.md#7`

#### MOB-KRS-06 - Tarik kembali pengajuan KRS
- **Peran**: mahasiswa
- **Deskripsi**: Membatalkan pengajuan yang menunggu keputusan sehingga KRS kembali draft dan dapat diubah.
- **Aturan bisnis**:
  - Hanya status submitted dan periode KRS masih buka; selain itu 409
  - Item pending kembali draft
- **Kriteria penerimaan/pengujian**:
  - Given KRS submitted dan periode buka When Tarik Pengajuan Then status draft dan item dapat diedit
  - Given periode tutup Then 409
- **Status backend**: `available`
- **Endpoint**: `POST /api/v1/student/krs/cancel`
- **Celah backend**: tidak ada
- **Status web**: pages/portal/PortalKrsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-05
- **Sumber**: `AUDIT/D03-role-accounts-status.md`; `AUDIT/D10-al-siakad.md`; `AUDIT/D14-al-api-roles.md`; `AUDIT/C2-be-academic-student.md#7`

#### MOB-KRS-07 - Batas beban SKS berdasarkan IPS dan indikator SKS
- **Peran**: mahasiswa
- **Deskripsi**: Progress SKS terpilih terhadap batas (dari IPS semester sebelumnya) dengan peringatan mendekati/melewati batas.
- **Aturan bisnis**:
  - Batas dari IPS term terakhir bernilai sebelum term target: IPS>=3.00 -> 24; >=2.50 -> 22; >=2.00 -> 20; <2.00 -> 18; tanpa riwayat -> 24 (KODE, hardcoded AcademicRecordService::maxSksForTerm; sama dengan D02/D08)
  - Konfigurasi admin (academic.krs.max_sks_tiers) hanya DESAIN
  - Kejadian melebihi batas pada tambah/ajukan -> 409
  - UI web: peringatan >=90%, bahaya >100%
- **Kriteria penerimaan/pengujian**:
  - Given IPS 2.50 Then max_credits=22; IPS 2.49 Then 20 (TS-SIA-03)
  - Given total tepat di batas Then boleh; +1 SKS Then 409
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/krs (max_credits, remaining_credits)`; `POST /api/v1/student/krs/items`; `POST /api/v1/student/krs/submit`
- **Celah backend**: Tier hardcoded, belum dapat dikonfigurasi per tenant; SKS khusus Semester Pendek (maks 9) belum ada
- **Status web**: pages/portal/PortalKrsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-01
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/D16-al-reports-sec-tests-roadmap.md (TS-SIA-03)`; `AUDIT/C2-be-academic-student.md#7`

#### MOB-KRS-08 - Deteksi bentrok jadwal dan kelas penuh saat memilih kelas
- **Peran**: mahasiswa
- **Deskripsi**: Server menolak kelas yang bentrok dengan kelas terpilih atau penuh; penawaran menampilkan konflik dan sisa kursi lebih dulu.
- **Aturan bisnis**:
  - Bentrok = irisan hari+jam; jam selesai sama dengan jam mulai bukan bentrok
  - Kursi dihitung draft+pending+enrolled; item dropped tak dihitung
  - 409 SCHEDULE_CONFLICT membawa errors.conflicts[]
- **Kriteria penerimaan/pengujian**:
  - Given dua kelas Senin 08:00-09:40 dan 09:40-11:20 Then tidak bentrok (TS-SIA-04)
  - Given kelas penuh Then 409 (TS-SIA-05)
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/krs/offerings`; `POST /api/v1/student/krs/items`
- **Celah backend**: Jadwal berstatus draft/in_review/published dan jadwal cancelled belum dimodelkan
- **Status web**: pages/portal/PortalKrsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-02; MOB-KRS-03
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D10-al-siakad.md (R5,R7)`; `AUDIT/D16-al-reports-sec-tests-roadmap.md (TS-SIA-04..06)`; `AUDIT/C2-be-academic-student.md#7`

#### MOB-KRS-09 - Validasi prasyarat dan MK yang sudah lulus/mengulang
- **Peran**: mahasiswa
- **Deskripsi**: MK dengan prasyarat belum terpenuhi atau sudah lulus tidak dapat dipilih; MK tidak lulus dapat diulang.
- **Aturan bisnis**:
  - Prasyarat memuat min_grade (default C); satisfied dihitung server
  - MK sudah lulus (>=C) ditolak; 'is_retake' menandai pengulangan nilai < C (kode). Aturan retake lanjutan (batas nilai mengulang) belum dapat dikonfigurasi
  - Ekuivalensi MK belum ada
- **Kriteria penerimaan/pengujian**:
  - Given prasyarat B belum lulus Then 409 PREREQUISITE_NOT_MET (TS-SIA-02) dan prasyarat tampil merah
  - Given nilai D pada MK Then MK ditandai Mengulang
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/krs/offerings`; `POST /api/v1/student/krs/items`
- **Celah backend**: Ekuivalensi MK dan kebijakan retake terkonfigurasi belum ada
- **Status web**: pages/portal/PortalKrsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-02
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D10-al-siakad.md (R3,R4)`; `AUDIT/D16-al-reports-sec-tests-roadmap.md (TS-SIA-01/02)`; `AUDIT/C2-be-academic-student.md#7`

#### MOB-KRS-10 - Penegakan periode KRS
- **Peran**: mahasiswa
- **Deskripsi**: Aksi tulis KRS ditolak di luar periode; layar menjadi baca-saja dengan keterangan tanggal.
- **Aturan bisnis**:
  - Window = academic_terms.krs_start_date..krs_end_date pada zona waktu universitas; is_krs_open = is_current dan hari ini dalam jendela
  - Batas akhir hari terakhir diterima sampai detik terakhir (TS-SIA-10)
  - Pengaturan periode oleh admin (PATCH /academic-terms/{id}/krs-period) bukan fitur mobile
- **Kriteria penerimaan/pengujian**:
  - Given hari setelah krs_end_date When tambah/ajukan/tarik Then 409 dan UI baca-saja
  - Given hari terakhir pukul 23:59 Then aksi diterima
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/krs`; `GET /api/v1/academic-terms (dosen/PA)`
- **Celah backend**: Kode galat PERIOD_CLOSED bertipe belum ada; Add/Drop period belum ada
- **Status web**: pages/portal/PortalKrsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-01
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D10-al-siakad.md (G1)`; `AUDIT/D16-al-reports-sec-tests-roadmap.md (TS-SIA-10)`; `AUDIT/C2-be-academic-student.md#7`

#### MOB-KRS-11 - Cekal (blokir) akademik/keuangan pada KRS dengan banner dan alasan
- **Peran**: mahasiswa
- **Deskripsi**: KRS tidak dapat diisi/diajukan bila ada cekal layanan 'krs'; banner menjelaskan alasan, unit penanggung jawab dan cara menyelesaikan. Nominal tagihan tidak ditampilkan.
- **Aturan bisnis**:
  - KODE: AcademicBlockService::ENFORCED_SERVICES = krs, exam; notices KRS memuat 'Pengisian KRS diblokir oleh cekal: <alasan>'
  - 409 errors.code=ACADEMIC_BLOCKED + errors.blocks[] (block_type, reason, unit, since, instruksi)
  - Dispensasi per layanan: hanya layanan yang dikecualikan terbuka (AC-CKL-02)
  - Cekal dicabut <=5 menit setelah pembayaran (AC-CKL-01, desain)
- **Kriteria penerimaan/pengujian**:
  - Given cekal aktif When tambah/ajukan Then 409 ACADEMIC_BLOCKED dan banner alasan
  - Given dispensasi hanya untuk exam_midterm Then KRS tetap 409 (AC-CKL-02)
  - Given pembayaran lunas Then cekal hilang dan KRS dapat diisi (AC-CKL-01)
- **Status backend**: `partial`
- **Endpoint**: `GET /api/v1/student/krs (notices)`; `POST /api/v1/student/krs/items`; `POST /api/v1/student/krs/submit`; `POST /api/v1/student/finance/blocks/{block}/dispensations (ajukan dispensasi, grup keuangan)`
- **Celah backend**: Tidak ada GET /student/finance/clearance (status per layanan, alasan, jatuh tempo dispensasi) - mobile hanya melihat cekal lewat notices/409; blockers[] terstruktur belum ada
- **Status web**: pages/portal/PortalKrsPage.tsx (notices); keuangan: keu/pages/portal/
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Grup keuangan (blok & dispensasi); MOB-ACAD-09
- **Sumber**: `AUDIT/D04-keu-master.md`; `AUDIT/D05-sdm-superadmin-sevima.md`; `AUDIT/D07-sevima-pmb-design.md`; `AUDIT/D10-al-siakad.md (G3)`; `AUDIT/D16-al-reports-sec-tests-roadmap.md (TS-SIA-07..09)`; `AUDIT/D19-keu-11-18.md`; `AUDIT/D20-keu-19-20.md`; `AUDIT/D21-keu-21-29.md`; `AUDIT/C2-be-academic-student.md#7`
- **Catatan**: D21/D20 menyatakan 'belum diimplementasi' untuk cekal pada KRS: kode menunjukkan KRS sudah menegakkan; yang belum adalah endpoint clearance mahasiswa dan grade_view/transcript.

#### MOB-KRS-12 - Lihat riwayat KRS per semester
- **Peran**: mahasiswa
- **Deskripsi**: Riwayat KRS term lampau: status, total SKS, jumlah MK, submission, daftar MK dengan nilai huruf.
- **Aturan bisnis**:
  - Terbaru lebih dulu, tanpa paginasi
  - Timeline event (krs_submission_events) hanya desain
- **Kriteria penerimaan/pengujian**:
  - Given tiga semester Then tiga entri urut terbaru
  - Given riwayat kosong Then tampilan kosong
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/krs/history`
- **Celah backend**: Tidak ada paginasi/filter term; tidak ada event log persetujuan (krs_submission_events)
- **Status web**: pages/portal/PortalKrsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-01
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/D13-al-erd.md`; `AUDIT/C2-be-academic-student.md#7`

#### MOB-KRS-13 - Cetak/unduh KRS PDF
- **Peran**: mahasiswa
- **Deskripsi**: Mengunduh KRS semester sebagai PDF.
- **Aturan bisnis**:
  - Default term berjalan bila academic_term_id kosong; 409 'Belum ada KRS pada semester tersebut.' bila tak ada item draft/pending/enrolled; 404 bila tak ada term
  - Nama berkas krs-<nim>-<term>.pdf
  - Tombol hanya muncul bila ada item
- **Kriteria penerimaan/pengujian**:
  - Given KRS berisi item When Cetak Then PDF tersimpan
  - Given KRS kosong Then pesan 409
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/documents/krs?academic_term_id=`
- **Celah backend**: tidak ada
- **Status web**: pages/portal/PortalKrsPage.tsx, PortalDocumentsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-ACAD-06; Flutter: path_provider, open_filex/share_plus
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/C2-be-academic-student.md#2`

#### MOB-KRS-14 - Status keputusan KRS dan umpan balik penolakan dosen PA
- **Peran**: mahasiswa
- **Deskripsi**: Menampilkan siapa/kapan memutuskan, catatan penolakan (decision_note) dan petunjuk memperbaiki lalu mengajukan ulang; notifikasi disetujui/ditolak.
- **Aturan bisnis**:
  - Ditolak: item kembali draft, status rejected, catatan wajib (5-1000 karakter) dari dosen; mahasiswa mengedit ulang dan mengajukan lagi
  - Notifikasi student.krs_approved / student.krs_rejected lewat /notifications (tanpa permission)
  - Status revision_requested, catatan per baris, krs_revision_count hanya desain; 'rejected' legacy dipetakan ke revision_requested nanti
- **Kriteria penerimaan/pengujian**:
  - Given dosen menolak dengan catatan Then banner bahaya 'KRS ditolak dosen wali' + catatan tampil dan item dapat diedit
  - Given dosen menyetujui Then status approved, item enrolled, notifikasi masuk
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/krs`; `GET /api/v1/notifications`
- **Celah backend**: Catatan revisi per baris belum ada; Push notification (FCM) belum ada
- **Status web**: pages/portal/PortalKrsPage.tsx, PortalNotificationsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-01; MOB-KRS-24
- **Sumber**: `AUDIT/D03-role-accounts-status.md`; `AUDIT/D06-exam-docs-videos.md (krs.approved)`; `AUDIT/D10-al-siakad.md`; `AUDIT/D15-al-uiux-rules.md (BR-SIA-KRS-11)`; `AUDIT/C2-be-academic-student.md#7`

#### MOB-KRS-15 - Perubahan KRS setelah terkunci (add/drop, permintaan perubahan)
- **Peran**: mahasiswa, dosen PA
- **Deskripsi**: Mahasiswa meminta tambah/hapus MK pada periode add/drop; dosen PA menyetujui/menolak.
- **Aturan bisnis**:
  - Periode add/drop (krs_change) dengan max_changes 2 dan drop_deadline_week 4 (contoh/desain); semua R1-R7 dan cekal berlaku
  - KODE: setelah disetujui KRS terkunci tanpa jalur mahasiswa; hanya admin PATCH /krs-items/{id}/drop (krs.update)
  - Idempotency-Key wajib (desain)
- **Kriteria penerimaan/pengujian**:
  - Given periode add/drop dan KRS terkunci When mahasiswa mengajukan perubahan Then status menunggu persetujuan PA
  - Given batas perubahan tercapai Then ditolak
- **Status backend**: `missing`
- **Endpoint**: (desain) POST /api/v1/student/krs/change-requests; (desain) GET /api/v1/krs-change-requests; (desain) POST /api/v1/krs-change-requests/{id}/approve|reject; (admin) PATCH /api/v1/krs-items/{id}/drop
- **Celah backend**: Seluruh alur change request, periode add/drop dan status locked belum ada
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_contract_pending_backend (label 'Menunggu backend')
- **Dependensi**: Backend: add/drop (OD-09); MOB-KRS-22/23
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/D10-al-siakad.md`; `AUDIT/D14-al-api-roles.md`; `AUDIT/D15-al-uiux-rules.md`; `AUDIT/D16-al-reports-sec-tests-roadmap.md`

#### MOB-KRS-16 - Simulasi validasi KRS (dry-run)
- **Peran**: mahasiswa
- **Deskripsi**: Melihat semua pelanggaran KRS tanpa mengajukan.
- **Aturan bisnis**:
  - Mengembalikan violations[] tanpa mengubah data
- **Kriteria penerimaan/pengujian**:
  - Given draft bermasalah Then daftar pelanggaran lengkap
- **Status backend**: `missing`
- **Endpoint**: (desain) GET /api/v1/student/krs/validation
- **Celah backend**: Endpoint belum ada
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: Backend D14
- **Sumber**: `AUDIT/D14-al-api-roles.md`

#### MOB-KRS-17 - Paket KRS otomatis semester awal
- **Peran**: mahasiswa
- **Deskripsi**: Sistem membuat draft KRS dari paket MK untuk semester awal.
- **Aturan bisnis**:
  - academic.krs.package_semesters [1,2] (desain); baris gagal dilaporkan, tidak dipaksa; idempoten; mahasiswa dicekal dilewati
- **Kriteria penerimaan/pengujian**:
  - Given mahasiswa semester 1 Then draft berisi MK paket
- **Status backend**: `missing`
- **Endpoint**: tidak ada
- **Celah backend**: Paket MK dan penerapan massal belum ada
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: Backend: course packages
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D05-sdm-superadmin-sevima.md`; `AUDIT/D07-sevima-pmb-design.md`; `AUDIT/D10-al-siakad.md`; `AUDIT/D16-al-reports-sec-tests-roadmap.md`; `AUDIT/D28-pmb-01-09.md`

#### MOB-KRS-18 - Semester Pendek: ajukan KRS multi-kelas
- **Peran**: mahasiswa
- **Deskripsi**: Keranjang hingga 10 kelas SP dengan total SKS vs maks dan total biaya; submit atomik.
- **Aturan bisnis**:
  - Maks SKS SP default 9 (term.max_credits ?? academic.short_term.max_credits); class_section_ids min1 max10 distinct; seluruh batch ditolak bila satu kelas gagal; jendela REGISTRATION_OPEN
- **Kriteria penerimaan/pengujian**:
  - Given 3 kelas valid Then semua tercatat pending; Given satu gagal Then batch ditolak 409 dengan class_section_id
- **Status backend**: `missing`
- **Endpoint**: (desain) POST /api/v1/student/krs-items
- **Celah backend**: Modul semester pendek belum ada di routes
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_contract_pending_backend (label 'Menunggu backend')
- **Dependensi**: Backend: semester pendek; Keuangan (invoice SP)
- **Sumber**: `AUDIT/D26-sp-core.md`; `AUDIT/D27-sp-testing-plan-video.md`; `AUDIT/D10-al-siakad.md`
- **Catatan**: Nama endpoint POST /student/krs-items bentrok secara konsep dengan /student/krs/items (reguler) - perlu keputusan kontrak.

#### MOB-KRS-19 - Semester Pendek: lihat KRS SP dengan status persetujuan/pembayaran
- **Peran**: mahasiswa
- **Deskripsi**: KRS Saya SP: item pending/enrolled/rejected/dropped dengan sub-tahap menunggu persetujuan/pembayaran.
- **Aturan bisnis**:
  - Pending menahan kursi dan menghitung SKS tetapi tidak dapat presensi/nilai/ujian
- **Kriteria penerimaan/pengujian**:
  - Given item pending Then label 'menunggu persetujuan'
- **Status backend**: `missing`
- **Endpoint**: (desain) GET /api/v1/student/krs-items
- **Celah backend**: Belum ada
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_contract_pending_backend (label 'Menunggu backend')
- **Dependensi**: MOB-KRS-18
- **Sumber**: `AUDIT/D26-sp-core.md`; `AUDIT/D27-sp-testing-plan-video.md`

#### MOB-KRS-20 - Semester Pendek: batalkan item KRS SP
- **Peran**: mahasiswa
- **Deskripsi**: Batalkan item pending atau enrolled berbiaya 0 selama registrasi buka.
- **Aturan bisnis**:
  - Tidak boleh bila sudah dibayar atau ada nilai/presensi/attempt ujian; SKS_BELOW_MINIMUM bila total <min tetapi >0; tagihan disesuaikan
- **Kriteria penerimaan/pengujian**:
  - Given item belum dibayar When batalkan Then item dropped dan tagihan disesuaikan
- **Status backend**: `missing`
- **Endpoint**: (desain) PATCH /api/v1/student/krs-items/{krsItem}/cancel
- **Celah backend**: Belum ada
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: MOB-KRS-18
- **Sumber**: `AUDIT/D26-sp-core.md`; `AUDIT/D27-sp-testing-plan-video.md`

#### MOB-KRS-21 - Dosen PA: antrean pengajuan KRS mahasiswa bimbingan
- **Peran**: dosen PA, dosen
- **Deskripsi**: Daftar pengajuan KRS mahasiswa bimbingan (default status submitted), dengan filter status, term dan pencarian nama/NIM; urut tertua dulu.
- **Aturan bisnis**:
  - Syarat permission: krs.approve ATAU krs_advising.read; advisor hanya melihat mahasiswa dengan academic_advisor_id = dirinya (filter diam-diam); krs.approve melihat semua
  - status: all|draft|submitted|approved|rejected (default submitted); per_page 1-100 (20)
  - Respons memuat mahasiswa, term, item kelas, total/maks SKS
- **Kriteria penerimaan/pengujian**:
  - Given dua advisee mengajukan Then antrean menampilkan keduanya dan bukan mahasiswa lain
  - Given non-advisee Then detail 403
  - Given status=all Then riwayat keputusan tampil
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/krs-submissions`; `GET /api/v1/krs-submissions/{id}`
- **Celah backend**: Tidak ada /advising/* (desain) maupun tab Revisi; web belum punya halaman (route persetujuanKrs tak terdaftar)
- **Status web**: none (nav 'Persetujuan KRS' tanpa halaman; tipe ada di studentPortalService.ts)
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Dosen perlu role lecturer+academic_advisor atau krs_advising.*
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D05-sdm-superadmin-sevima.md`; `AUDIT/D10-al-siakad.md`; `AUDIT/D13-al-erd.md`; `AUDIT/D15-al-uiux-rules.md`; `AUDIT/D27-sp-testing-plan-video.md`; `AUDIT/C3-be-academic-lecturer.md#3`
- **Catatan**: D06 menyatakan advisor tanpa permission: usang; seeder kini memberi krs_advising.* pada lecturer dan academic_advisor.

#### MOB-KRS-22 - Dosen PA: setujui KRS mahasiswa bimbingan
- **Peran**: dosen PA, dosen
- **Deskripsi**: Menyetujui pengajuan (catatan opsional <=1000 karakter): item pending menjadi enrolled, status approved, mahasiswa dinotifikasi.
- **Aturan bisnis**:
  - Hanya status submitted, selain itu 409 'KRS ini sudah diputuskan sebelumnya atau belum diajukan.'
  - Cek ulang cekal saat persetujuan -> bisa 409/422 ACADEMIC_BLOCKED
  - Persetujuan memicu event KrsSubmissionApproved dan penerbitan tagihan SKS otomatis (credit_fee.due_days 21, auto_bill_on_krs_approval true); kegagalan tagihan tidak menggagalkan persetujuan
  - Non-advisee -> 403
- **Kriteria penerimaan/pengujian**:
  - Given submitted When Setujui Then status approved dan item enrolled (APV-01)
  - Given sudah diputuskan Then 409
  - Given mahasiswa dicekal Then 409 ACADEMIC_BLOCKED dan tombol dosen menampilkan alasan
- **Status backend**: `available`
- **Endpoint**: `POST /api/v1/krs-submissions/{id}/approve`
- **Celah backend**: Persetujuan massal (web-only desain) dan /advising/* alias belum ada
- **Status web**: none (tipe saja)
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-21
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D09-samples-notes.md`; `AUDIT/D10-al-siakad.md`; `AUDIT/D30-manual-keu-video-pmb.md`; `AUDIT/C3-be-academic-lecturer.md#3`

#### MOB-KRS-23 - Dosen PA: tolak/kembalikan KRS dengan catatan
- **Peran**: dosen PA, dosen
- **Deskripsi**: Menolak pengajuan dengan alasan wajib; item kembali draft dan mahasiswa memperbaiki lalu mengajukan ulang.
- **Aturan bisnis**:
  - note wajib, 5-1000 karakter ('Tuliskan alasan penolakan...', 'Alasan penolakan minimal 5 karakter.')
  - Hanya status submitted; non-advisee 403
  - KODE: status rejected (bukan revision_requested); catatan per baris dan alias request-revision hanya desain
- **Kriteria penerimaan/pengujian**:
  - Given tanpa catatan Then 422
  - Given catatan valid Then status rejected, item draft, notifikasi student.krs_rejected
- **Status backend**: `available`
- **Endpoint**: `POST /api/v1/krs-submissions/{id}/reject`
- **Celah backend**: Catatan per baris, reopen, request-revision alias belum ada
- **Status web**: none (tipe saja)
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-21; MOB-KRS-14
- **Sumber**: `AUDIT/D10-al-siakad.md`; `AUDIT/D14-al-api-roles.md`; `AUDIT/D15-al-uiux-rules.md`; `AUDIT/D27-sp-testing-plan-video.md`; `AUDIT/C3-be-academic-lecturer.md#3`

#### MOB-KRS-24 - Notifikasi alur KRS (pengajuan masuk ke dosen PA, keputusan ke mahasiswa)
- **Peran**: mahasiswa, dosen PA
- **Deskripsi**: Notifikasi dalam aplikasi untuk: pengajuan KRS baru (dosen PA) dan disetujui/ditolak (mahasiswa).
- **Aturan bisnis**:
  - Event: student.krs_review_requested (advisor), student.krs_approved, student.krs_rejected
  - /notifications tidak memerlukan permission; push tidak ada endpoint registrasi perangkat
  - Link notifikasi berformat rute web (/portal/krs) - perlu pemetaan ke layar Flutter
- **Kriteria penerimaan/pengujian**:
  - Given mahasiswa mengajukan KRS Then dosen PA melihat notifikasi baru
  - Given keputusan Then mahasiswa melihat notifikasi dan tap membuka layar KRS
- **Status backend**: `partial`
- **Endpoint**: `GET /api/v1/notifications`; `PATCH /api/v1/notifications/{id}/read`; `POST /api/v1/notifications/read-all`
- **Celah backend**: Push/FCM registrasi perangkat belum ada; Notifikasi 'krs_reopened/expired' dan pengingat H-3 belum ada
- **Status web**: pages/portal/PortalNotificationsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Grup notifikasi (G?); Infrastruktur push
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D10-al-siakad.md`; `AUDIT/D15-al-uiux-rules.md`; `AUDIT/C3-be-academic-lecturer.md#3`
- **Catatan**: Endpoint notifikasi rinci dicatat pada grup notifikasi.

#### MOB-KRS-25 - Dosen PA: persetujuan massal KRS
- **Peran**: dosen PA
- **Deskripsi**: Menyetujui banyak pengajuan sekaligus (web-only dalam desain).
- **Aturan bisnis**:
  - Desain: bulk approve hanya web
- **Kriteria penerimaan/pengujian**:
  - -
- **Status backend**: `missing`
- **Endpoint**: tidak ada
- **Celah backend**: Tidak ada endpoint bulk
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: -
- **Sumber**: `AUDIT/D15-al-uiux-rules.md`

#### MOB-KRS-26 - Dosen PA: daftar mahasiswa bimbingan dan ringkasan progres
- **Peran**: dosen PA
- **Deskripsi**: Daftar advisee dengan status, semester, SKS lulus, IPS terakhir, IPK, rerata presensi, status KRS, cekal aktif, penanda risiko; widget dashboard 'Mahasiswa bimbingan'.
- **Aturan bisnis**:
  - Hanya mahasiswa dengan academic_advisor_id = dosen (WAL-02); max_students_per_advisor 40 hanya peringatan (desain)
  - Alternatif sementara (partial): turunkan dari GET /krs-submissions?status=all (hanya mahasiswa yang pernah mengajukan)
- **Kriteria penerimaan/pengujian**:
  - Given dosen PA dengan 5 advisee Then daftar memuat 5 mahasiswa dengan IPS/IPK
- **Status backend**: `missing`
- **Endpoint**: (desain) GET /api/v1/advising/students; (desain) GET /api/v1/advising/students/{student}
- **Celah backend**: Tidak ada endpoint advisee; /students tak punya filter academic_advisor_id dan role advisor murni tak punya students.read; Tidak ada ringkasan IPS/IPK/presensi per advisee
- **Status web**: none (halaman mock PortalAcademicAdvisingPage untuk mahasiswa saja)
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_contract_pending_backend (label 'Menunggu backend')
- **Dependensi**: Backend: modul Perwalian (/advising/*)
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/D10-al-siakad.md`; `AUDIT/D14-al-api-roles.md`; `AUDIT/D15-al-uiux-rules.md`; `AUDIT/D16-al-reports-sec-tests-roadmap.md`; `AUDIT/C3-be-academic-lecturer.md (gap)`
- **Catatan**: Tampilkan entri berlabel 'Menunggu backend'; boleh dipasangkan dengan antrean KRS (MOB-KRS-21) sebagai satu-satunya data advisee nyata.

#### MOB-KRS-27 - Dosen PA: lihat data akademik mahasiswa bimbingan (transkrip, KHS)
- **Peran**: dosen PA, dosen
- **Deskripsi**: Biodata, KRS, nilai akhir/IPS/IPK, transkrip, presensi, status cekal tanpa nominal.
- **Aturan bisnis**:
  - NIK dimasking; nominal tagihan tidak pernah ditampilkan (CKL-09); komponen nilai bergantung OD-28
  - KODE: GET /students/{id}/transcript (students.read) tersedia untuk role lecturer, bukan untuk academic_advisor murni; tidak dibatasi advisee
- **Kriteria penerimaan/pengujian**:
  - Given dosen PA membuka advisee Then transkrip tampil; Given advisor murni Then 403
- **Status backend**: `partial`
- **Endpoint**: `GET /api/v1/students/{id}`; `GET /api/v1/students/{id}/transcript`
- **Celah backend**: Tidak ada KHS/presensi/cekal per advisee; advisor murni tanpa students.read; tidak ada scoping advisee
- **Status web**: pages/academic/StudentDetailPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-ACAD-18; MOB-KRS-26
- **Sumber**: `AUDIT/D10-al-siakad.md`; `AUDIT/D16-al-reports-sec-tests-roadmap.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/C3-be-academic-lecturer.md`

#### MOB-KRS-28 - Dosen PA: peringatan mahasiswa berisiko
- **Peran**: dosen PA
- **Deskripsi**: Penanda risiko komputasi: IPS<2.00, IPK<2.00, penurunan IPS>=0.75, SKS tertinggal, presensi di bawah ambang+5, belum KRS H-3, >=2 nilai gagal, masa studi mendekati batas.
- **Aturan bisnis**:
  - Level medium 2 flag, high 3 flag; flag tidak memblokir; tidak terlihat mahasiswa; ambang konfigurasi (WAL-04)
- **Kriteria penerimaan/pengujian**:
  - Given IPS 1.9 Then flag IPS<2.00 dan level sesuai
- **Status backend**: `missing`
- **Endpoint**: (desain) GET /api/v1/advising/at-risk-students
- **Celah backend**: Belum ada
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_contract_pending_backend (label 'Menunggu backend')
- **Dependensi**: MOB-KRS-26; Notifikasi push
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D10-al-siakad.md`; `AUDIT/D14-al-api-roles.md`; `AUDIT/D15-al-uiux-rules.md`; `AUDIT/D16-al-reports-sec-tests-roadmap.md`

#### MOB-KRS-29 - Catatan bimbingan akademik/konsultasi (dosen PA mencatat, mahasiswa melihat yang dibagikan)
- **Peran**: dosen PA, mahasiswa
- **Deskripsi**: Dosen mencatat konsultasi (jenis, ringkasan, tindak lanjut, visibilitas shared|advisor_only); mahasiswa hanya melihat yang dibagikan.
- **Aturan bisnis**:
  - Penulis dapat mengubah dalam 24 jam (WAL-03) atau 7 hari (D14 contoh), setelahnya append-only
  - advisor_only tidak terlihat mahasiswa
- **Kriteria penerimaan/pengujian**:
  - Given catatan advisor_only Then mahasiswa tak melihatnya
- **Status backend**: `missing`
- **Endpoint**: (desain) GET/POST /api/v1/advising/students/{student}/records; (desain) PUT /api/v1/advising-records/{id}
- **Celah backend**: Modul Bimbingan belum ada di app/Modules
- **Status web**: mahasiswa: mock tanpa route (PortalAcademicAdvisingPage.tsx); dosen: none
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_contract_pending_backend (label 'Menunggu backend')
- **Dependensi**: Backend: Perwalian
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D05-sdm-superadmin-sevima.md`; `AUDIT/D07-sevima-pmb-design.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/D10-al-siakad.md`; `AUDIT/D13-al-erd.md`; `AUDIT/D14-al-api-roles.md`; `AUDIT/D16-al-reports-sec-tests-roadmap.md`

#### MOB-KRS-30 - Mahasiswa: halaman dosen PA (profil, jadwal konsultasi, riwayat)
- **Peran**: mahasiswa
- **Deskripsi**: Kartu dosen PA, jadwal bimbingan berikutnya, tombol Ajukan Jadwal Baru, riwayat bimbingan.
- **Aturan bisnis**:
  - Identitas dosen PA tersedia pada academic-summary/KRS; jadwal dan riwayat tidak ada
- **Kriteria penerimaan/pengujian**:
  - Given dosen PA ditetapkan Then nama tampil; Given belum Then 'Belum ditetapkan'
- **Status backend**: `partial`
- **Endpoint**: `GET /api/v1/student/academic-summary (academic_advisor)`; `GET /api/v1/student/krs (academic_advisor)`; (desain) GET/POST /api/v1/portal/academic-advising-sessions
- **Celah backend**: Sesi bimbingan dan permintaan jadwal belum ada; NIDN/email dosen PA belum dipastikan di respons
- **Status web**: mock tanpa route (PortalAcademicAdvisingPage.tsx)
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_contract_pending_backend (label 'Menunggu backend')
- **Dependensi**: MOB-KRS-29
- **Sumber**: `AUDIT/D01-flutter-design.md`; `AUDIT/D02-app-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D06-exam-docs-videos.md`; `AUDIT/D15-al-uiux-rules.md`
- **Catatan**: Bagian nama dosen PA dapat dibangun terintegrasi; sisanya labeled.

#### MOB-KRS-31 - Penjadwalan konsultasi bimbingan
- **Peran**: dosen PA, mahasiswa
- **Deskripsi**: Dosen menjadwalkan konsultasi; mahasiswa melihat jadwal bimbingan dan notifikasi.
- **Aturan bisnis**:
  - Notifikasi 'Jadwal bimbingan' (desain)
- **Kriteria penerimaan/pengujian**:
  - Given jadwal dibuat Then mahasiswa menerima notifikasi
- **Status backend**: `missing`
- **Endpoint**: tidak ada
- **Celah backend**: Belum ada
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: MOB-KRS-29
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D07-sevima-pmb-design.md`

#### MOB-KRS-32 - Dosen PA: status cekal keuangan advisee (tanpa nominal)
- **Peran**: dosen PA, dosen
- **Deskripsi**: Lencana 'Cekal keuangan: KRS, Ujian' + alasan umum + 'Hubungi Bagian Keuangan' di daftar advisee, profil dan layar persetujuan.
- **Aturan bisnis**:
  - Nominal tidak terlihat peran akademik (KEU-BR-270); DW tidak membuat/menyetujui dispensasi
  - KODE: persetujuan KRS sudah menegakkan cek cekal (409 ACADEMIC_BLOCKED)
- **Kriteria penerimaan/pengujian**:
  - Given advisee dicekal When dosen membuka detail Then lencana tampil tanpa nominal (KEU-AC-HLD-07)
- **Status backend**: `partial`
- **Endpoint**: `POST /api/v1/krs-submissions/{id}/approve (409 ACADEMIC_BLOCKED)`; (desain) GET /api/v1/students/{student}/finance-clearance
- **Celah backend**: Endpoint finance-clearance per mahasiswa belum ada; daftar blok memerlukan finance_clearance.read
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Grup keuangan; MOB-KRS-22
- **Sumber**: `AUDIT/D19-keu-11-18.md`; `AUDIT/D21-keu-21-29.md`; `AUDIT/D30-manual-keu-video-pmb.md`; `AUDIT/D09-samples-notes.md`
- **Catatan**: Bangun hanya penanganan 409 saat setujui; lencana menunggu backend.

#### MOB-KRS-33 - Persetujuan KRS lewat ApprovalWorkflow generik di mobile
- **Peran**: dosen PA
- **Deskripsi**: Menyetujui KRS via POST /approval-request-steps/{step}/approve.
- **Aturan bisnis**:
  - Ditunda ke fitur persetujuan umum; KRS reguler memakai /krs-submissions
- **Kriteria penerimaan/pengujian**:
  - -
- **Status backend**: `missing`
- **Endpoint**: `POST /api/v1/approval-request-steps/{step}/approve|reject`; `GET /api/v1/approval-requests`
- **Celah backend**: Dosen tidak memiliki approval_requests.read; jalur KRS reguler sudah tersedia
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: MOB-KRS-22
- **Sumber**: `AUDIT/D26-sp-core.md`

#### MOB-KRS-34 - Tagihan SKS otomatis saat KRS disetujui (dampak ke mahasiswa)
- **Peran**: mahasiswa, dosen PA
- **Deskripsi**: Setelah persetujuan, tagihan Biaya SKS terbit/disesuaikan otomatis; mahasiswa melihatnya pada modul keuangan.
- **Aturan bisnis**:
  - Setelan 'Terbitkan tagihan SKS otomatis saat KRS disetujui'; kegagalan tidak memblokir persetujuan; SKS invoice bukan syarat KRS
- **Kriteria penerimaan/pengujian**:
  - Given KRS disetujui Then tagihan SKS muncul pada /student/finance/invoices
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/finance/invoices`
- **Celah backend**: tidak ada
- **Status web**: keu/pages/portal/PortalFinancePages.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: Grup keuangan
- **Sumber**: `AUDIT/D30-manual-keu-video-pmb.md`; `AUDIT/D09-samples-notes.md`; `AUDIT/D04-keu-master.md`
- **Catatan**: Tidak ada layar mobile tersendiri; dicakup grup keuangan.

### Domain schedule_calendar (SCHED)

Mahasiswa: jadwal mingguan/hari ini dan kalender pribadi tersedia. Dosen: hanya jadwal mingguan turunan dari `/teaching/classes` dan kalender akademik baca-saja; tidak ada jadwal bertanggal, penggantian, atau notifikasi perubahan.

#### MOB-SCHED-01 - Lihat jadwal kuliah mingguan (mahasiswa)
- **Peran**: mahasiswa
- **Deskripsi**: Jadwal mingguan per hari (Senin-Jumat; Sabtu/Minggu bila ada) berisi MK, jam, ruang, dosen, SKS, dan lencana 'Menunggu persetujuan KRS' untuk kelas pending.
- **Aturan bisnis**:
  - Sumber: term berjalan, KRS item Enrolled + Pending, urut hari lalu jam mulai
  - Ruang dan jadwal berupa teks bebas; satu dosen per kelas
  - Tanpa term berjalan: term null, slots []
  - Jadwal tidak boleh di-cache offline secara default (transaksional); screenshot diizinkan (tanpa FLAG_SECURE)
  - Jadwal berstatus draft/in_review/published, kelas pengganti dan revisi (D13) belum ada
- **Kriteria penerimaan/pengujian**:
  - Given KRS disetujui Then semua kelas muncul pada hari yang benar
  - Given KRS masih diajukan Then kelas tampil dengan lencana menunggu persetujuan
  - Given belum ada jadwal Then 'Belum ada jadwal kuliah.' + tombol Buka KRS
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/schedule`
- **Celah backend**: Tidak ada status publikasi jadwal, jadwal pengganti, atau notifikasi perubahan jadwal
- **Status web**: pages/portal/PortalSchedulePage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-KRS-05
- **Sumber**: `AUDIT/D01-flutter-design.md (PortalScheduleScreen)`; `AUDIT/D02-app-design.md (MVP #8)`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/D10-al-siakad.md (JDW-04)`; `AUDIT/D13-al-erd.md`; `AUDIT/D26-sp-core.md`; `AUDIT/C2-be-academic-student.md#4`; `AUDIT/C8-fe-one-app.md (mock one/pages/Learning.tsx)`
- **Catatan**: Prototipe /one (Learning.tsx SchedulePage) hanya mock; jangan jadi acuan data. D01 usang: backend sudah ada.

#### MOB-SCHED-02 - Lihat jadwal hari ini dan kuliah berikutnya (mahasiswa)
- **Peran**: mahasiswa
- **Deskripsi**: Kartu 'Hari ini' dan 'Kuliah Berikutnya' (tanggal, jam mulai), untuk dasbor dan layar Jadwal.
- **Aturan bisnis**:
  - Respons memuat now (ISO), timezone Asia/Jakarta, is_in_term, today[] dengan status, next_class
  - Bila !is_in_term tampilkan catatan di luar masa perkuliahan
  - Gunakan waktu server (now) bukan jam perangkat untuk menentukan 'hari ini'
- **Kriteria penerimaan/pengujian**:
  - Given hari ada kuliah Then today[] tampil berurut jam
  - Given di luar masa kuliah Then catatan 'di luar semester' tampil
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/schedule`
- **Celah backend**: tidak ada
- **Status web**: pages/portal/PortalSchedulePage.tsx (tab Hari Ini)
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-SCHED-01
- **Sumber**: `AUDIT/D02-app-design.md ('Jadwal hari ini')`; `AUDIT/D27-sp-testing-plan-video.md (PRT-01)`; `AUDIT/C2-be-academic-student.md#4`; `AUDIT/C7-fe-portals.md#3.5`

#### MOB-SCHED-03 - Kalender akademik dan agenda pribadi mahasiswa
- **Peran**: mahasiswa
- **Deskripsi**: Kalender (bulan/minggu/agenda) menggabungkan kegiatan akademik (registrasi, KRS, kuliah, UTS, UAS, libur, wisuda), awal/akhir semester, jendela KRS, pertemuan kelas, ujian terpublikasi dan tenggat tugas.
- **Aturan bisnis**:
  - from dan to wajib (YYYY-MM-DD), to>=from, rentang maks 62 hari (422 pada 'to')
  - event: id, type academic|class|exam|assignment, category (+label), title, start/end, all_day, location, link
  - Pertemuan kelas hanya untuk term berjalan dan item Enrolled
  - link berupa rute web (/portal/...) - petakan ke layar Flutter
  - Kegagalan kalender tidak menghalangi ujian (D11)
- **Kriteria penerimaan/pengujian**:
  - Given rentang 62 hari Then 200; 63 hari Then 422
  - Given ujian terpublikasi Then muncul dengan category exam
  - Given tugas berbatas waktu Then muncul assignment_deadline
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/student/calendar?from=&to=`
- **Celah backend**: Tidak ada ekspor/sinkron kalender eksternal; jadwal praktikum/pengganti/sesi per tanggal (lecture_sessions) belum ada; kelas hanya term berjalan
- **Status web**: pages/portal/PortalCalendarPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Mapper link web -> rute Flutter; MOB-SCHED-01
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D05-sdm-superadmin-sevima.md`; `AUDIT/D07-sevima-pmb-design.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/D10-al-siakad.md`; `AUDIT/D11-al-lms-cbt.md`; `AUDIT/D13-al-erd.md`; `AUDIT/D15-al-uiux-rules.md`; `AUDIT/C2-be-academic-student.md#4`

#### MOB-SCHED-04 - Kalender akademik untuk dosen
- **Peran**: dosen, dosen PA
- **Deskripsi**: Daftar kegiatan kalender akademik (kategori, rentang tanggal, term, prodi) untuk dosen.
- **Aturan bisnis**:
  - academic_calendar.read dimiliki dosen (hanya baca); respons tidak dipaginasi, batas 500 baris; query academic_term_id, from, to
  - Dosen tidak mendapat /student/calendar (khusus mahasiswa); tidak ada agenda pribadi dosen (pertemuan, ujian)
  - academic_advisor murni tidak punya academic_calendar.read
- **Kriteria penerimaan/pengujian**:
  - Given rentang bulan Then kegiatan terurut start_date
  - Given advisor murni Then 403
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/academic-calendar-events`; `GET /api/v1/academic-terms`
- **Celah backend**: Tidak ada kalender gabungan dosen (kelas+ujian+kegiatan); tidak ada sinkron kalender eksternal
- **Status web**: none (admin: pengaturan kalender; web dosen tidak punya halaman kalender)
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-SCHED-10
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D05-sdm-superadmin-sevima.md`; `AUDIT/D07-sevima-pmb-design.md`; `AUDIT/D10-al-siakad.md`; `AUDIT/C3-be-academic-lecturer.md#4`; `AUDIT/C7-fe-portals.md (gap: lecturer schedule)`

#### MOB-SCHED-05 - Jadwal mengajar mingguan dosen
- **Peran**: dosen
- **Deskripsi**: Timetable mingguan dosen dari seluruh kelas yang diampu pada term berjalan: hari, jam, ruang, MK, kode kelas.
- **Aturan bisnis**:
  - Diturunkan klien dari GET /teaching/classes: ratakan data[].schedules[] x course.name/class_code/room; berulang mingguan menurut day_of_week (1=Senin..7=Minggu)
  - Tanpa pengecualian tanggal: libur, kelas pengganti, perubahan ruang tidak diketahui
  - Dibatasi kelas milik sendiri kecuali classes.update
  - Jadwal draft/in_review disembunyikan (desain) - belum ada
- **Kriteria penerimaan/pengujian**:
  - Given dosen mengampu 3 kelas Then jadwal mingguan memuat semua slot pada hari yang benar
  - Given term lain dipilih Then jadwal term itu
- **Status backend**: `partial`
- **Endpoint**: `GET /api/v1/teaching/classes`
- **Celah backend**: Tidak ada GET /teaching/schedule bertanggal (desain D14: filter rentang tanggal); Tidak ada penggantian jadwal/ruang dan notifikasi perubahan; Web dosen tidak punya layar jadwal (widget 'Jadwal mengajar' tidak dibangun)
- **Status web**: none (nav 'Kelas Saya' tanpa halaman)
- **Status mobile saat ini**: none
- **Prioritas**: P0
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-ACAD-19
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D05-sdm-superadmin-sevima.md`; `AUDIT/D07-sevima-pmb-design.md`; `AUDIT/D10-al-siakad.md (JDW-06)`; `AUDIT/D13-al-erd.md`; `AUDIT/D14-al-api-roles.md`; `AUDIT/C3-be-academic-lecturer.md#2`; `AUDIT/gaps (C3: Lecturer timetable by date)`
- **Catatan**: Dinilai partial karena data jadwal tersedia tetapi tidak ada endpoint jadwal bertanggal; implementasi mobile dapat nyata dengan penurunan klien.

#### MOB-SCHED-06 - Pertemuan/kelas hari ini (dosen)
- **Peran**: dosen
- **Deskripsi**: Daftar kelas yang mengajar hari ini berdasarkan jadwal mingguan dan tanggal server; titik masuk ke presensi.
- **Aturan bisnis**:
  - Diturunkan dari jadwal mingguan + tanggal hari ini + rentang term; belum ada sesi per tanggal (lecture_sessions: meeting_number, status scheduled/held/cancelled/rescheduled) dan tidak tahu libur/kegiatan kalender kecuali digabung dengan academic-calendar-events
  - Zona waktu universitas Asia/Jakarta
- **Kriteria penerimaan/pengujian**:
  - Given hari Senin dengan 2 kelas Then 2 kartu hari ini berurut jam
  - Given libur pada kalender akademik Then ditandai libur
- **Status backend**: `partial`
- **Endpoint**: `GET /api/v1/teaching/classes`; `GET /api/v1/academic-calendar-events`; `GET /api/v1/academic-terms`
- **Celah backend**: Tidak ada endpoint sesi per tanggal/penggantian (lecture_sessions Baru)
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: MOB-SCHED-05; MOB-SCHED-04
- **Sumber**: `AUDIT/D02-app-design.md ('Pertemuan hari ini')`; `AUDIT/D15-al-uiux-rules.md`; `AUDIT/D13-al-erd.md`; `AUDIT/C3-be-academic-lecturer.md`

#### MOB-SCHED-07 - Jadwal ujian UTS/UAS dan ruang ujian
- **Peran**: mahasiswa, dosen
- **Deskripsi**: Daftar jadwal ujian per mahasiswa/dosen dengan ruang dan waktu.
- **Aturan bisnis**:
  - KODE: tidak ada jadwal UTS/UAS terpusat, kartu ujian, ruang & pengawas; ujian CBT terpublikasi (starts_at) muncul pada /student/calendar kategori exam dan pada daftar /student/exams (grup ujian)
  - Kartu ujian UTS/UAS (SEVIMA) tidak ada
  - Dosen: daftar ujian dari modul ujian (exams.read), bukan jadwal terpusat
- **Kriteria penerimaan/pengujian**:
  - Given ujian terpublikasi dengan starts_at Then muncul di kalender mahasiswa
- **Status backend**: `partial`
- **Endpoint**: `GET /api/v1/student/calendar (type exam)`; `GET /api/v1/student/exams (grup ujian)`; `GET /api/v1/exams (dosen, grup ujian)`
- **Celah backend**: Jadwal ujian terpusat, ruang, pengawas, kartu ujian, ujian susulan belum ada
- **Status web**: pages/portal/PortalCalendarPage.tsx, PortalExamsPage.tsx
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Grup ujian (CBT)
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/D11-al-lms-cbt.md`; `AUDIT/D07-sevima-pmb-design.md`
- **Catatan**: Tahap awal: tampilkan ujian CBT di kalender; label 'Belum tersedia' untuk jadwal UTS/UAS terpusat.

#### MOB-SCHED-08 - Notifikasi perubahan jadwal (ruang, dosen, pengganti, dibatalkan)
- **Peran**: mahasiswa, dosen
- **Deskripsi**: Notifikasi saat jadwal berubah; push bila perubahan kurang dari 24 jam sebelum kuliah.
- **Aturan bisnis**:
  - BR-SIA-JDW-08: notifikasi <=1 menit, push bila perubahan <24 jam; revisi jadwal wajib change_reason (D13)
- **Kriteria penerimaan/pengujian**:
  - Given ruang diubah Then mahasiswa dan dosen menerima notifikasi
- **Status backend**: `missing`
- **Endpoint**: tidak ada
- **Celah backend**: Revisi jadwal, status jadwal, event perubahan dan push belum ada
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P2
- **Cakupan rekomendasi**: build_contract_pending_backend (label 'Menunggu backend')
- **Dependensi**: Backend jadwal berstatus/revisi; Infrastruktur push (FCM)
- **Sumber**: `AUDIT/D02-app-design.md`; `AUDIT/D08-proposal-pdf.md`; `AUDIT/D13-al-erd.md`; `AUDIT/D15-al-uiux-rules.md`; `AUDIT/D16-al-reports-sec-tests-roadmap.md`

#### MOB-SCHED-09 - Ruang dan sesi kuliah (lecture sessions), jadwal praktikum/tutorial
- **Peran**: mahasiswa, dosen
- **Deskripsi**: Detail ruang (kode, gedung, lantai, kapasitas), sesi per pertemuan (online/hybrid, URL), jenis jadwal teori/praktikum/tutorial.
- **Aturan bisnis**:
  - Ruang kini teks bebas pada jadwal; tabel rooms/lecture_sessions/schedule_type direncanakan (Baru)
- **Kriteria penerimaan/pengujian**:
  - -
- **Status backend**: `missing`
- **Endpoint**: tidak ada
- **Celah backend**: Tabel rooms, lecture_sessions, schedule_type belum ada
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: Backend D13
- **Sumber**: `AUDIT/D13-al-erd.md`; `AUDIT/D05-sdm-superadmin-sevima.md`; `AUDIT/D15-al-uiux-rules.md`

#### MOB-SCHED-10 - Lihat semester aktif dan periode KRS
- **Peran**: mahasiswa, dosen, dosen PA
- **Deskripsi**: Daftar semester dengan penanda berjalan dan jendela KRS (krs_start_date, krs_end_date, is_krs_open); dipakai pemilih term dan header.
- **Aturan bisnis**:
  - is_krs_open = is_current dan hari ini dalam jendela (zona waktu universitas)
  - /academic-terms butuh salah satu: classes.read, krs.read, krs_advising.read, academic_calendar.read, course_materials.read (terbuka untuk dosen dan advisor murni); tidak dipaginasi, terbaru dulu
  - Mahasiswa memperoleh term berjalan via /student/krs dan /student/schedule, bukan /academic-terms
- **Kriteria penerimaan/pengujian**:
  - Given dosen membuka pemilih semester Then daftar term terbaru dulu dan term berjalan ditandai
  - Given advisor murni Then 200 (krs_advising.read)
- **Status backend**: `available`
- **Endpoint**: `GET /api/v1/academic-terms`
- **Celah backend**: Periode terikat (add/drop, UTS, UAS, input nilai, EDOM: academic_term_periods) belum ada
- **Status web**: pages/academic/KrsPage.tsx (admin/dosen)
- **Status mobile saat ini**: none
- **Prioritas**: P1
- **Cakupan rekomendasi**: build_integrated (bangun terintegrasi)
- **Dependensi**: Dipakai MOB-ACAD-19, MOB-KRS-21
- **Sumber**: `AUDIT/D13-al-erd.md`; `AUDIT/D03-role-accounts-status.md`; `AUDIT/C3-be-academic-lecturer.md#4`

#### MOB-SCHED-11 - Integrasi kalender eksternal (Google Calendar/ICS)
- **Peran**: mahasiswa, dosen
- **Deskripsi**: Tambahkan jadwal/kalender akademik ke kalender perangkat atau Google Calendar.
- **Aturan bisnis**:
  - Disebut sebagai integrasi (D02); tidak ada endpoint
- **Kriteria penerimaan/pengujian**:
  - -
- **Status backend**: `missing`
- **Endpoint**: tidak ada
- **Celah backend**: Tidak ada ekspor ICS/feed; dapat diimplementasi sisi klien dari data yang ada
- **Status web**: none
- **Status mobile saat ini**: none
- **Prioritas**: P3
- **Cakupan rekomendasi**: defer (tunda)
- **Dependensi**: Flutter: add_2_calendar atau sejenis
- **Sumber**: `AUDIT/D02-app-design.md`

### Pertanyaan belum terjawab

1. Dosen PA: bagaimana advisor mendapatkan daftar mahasiswa bimbingan (endpoint /advising/students belum ada; /students tanpa filter academic_advisor_id) dan apakah role academic_advisor murni perlu students.read/academic_calendar.read?
2. Skala nilai dan IPS/IPK: kode memakai skala hardcoded 7 tingkat (A>=85...E<35) dan tidak menyaring is_published; dokumen D02 memberi skala berbeda (A-, B+ ...) dan 'dapat dikonfigurasi'. Mana yang berlaku untuk mobile dan kapan publikasi nilai/EDOM gate dibangun?
3. Kontrak galat KRS: kode hanya memberi errors.code untuk SCHEDULE_CONFLICT dan ACADEMIC_BLOCKED; dokumen mendesain KRS_VALIDATION_FAILED/violations[], PERIOD_CLOSED, revision_requested, locked, Idempotency-Key. Apakah mobile diminta menunggu kontrak ini atau memakai pesan teks 409?
4. Semester Pendek: endpoint POST /student/krs-items (D26) vs /student/krs/items yang ada; D26 169 menyatakan persetujuan SP bergantung relasi dosen wali dan ApprovalWorkflow - belum jelas jalur final.
5. Jadwal dosen bertanggal (GET /teaching/schedule) dan sesi kuliah per tanggal belum ada; apakah mobile cukup menurunkan timetable mingguan sisi klien?
6. Status cekal bagi mahasiswa: tidak ada GET /student/finance/clearance (D20 desain); apakah mobile cukup membaca notices KRS + 409 errors.blocks[] sampai endpoint tersedia?
7. Dokumen D02 'Dokumen mahasiswa' (student_documents) tidak menyebut apakah mahasiswa mengunggah atau hanya melihat; tidak dibuat baris.
8. Privasi: GET /students dan /students/{id}/transcript terbuka untuk semua dosen tanpa pembatasan kelas/advisee; perlu keputusan sebelum dosen mobile menampilkannya.
9. Fitur D09 'rapor SMA' (KHS pattern) hanya analogi desain dan tidak dimasukkan sebagai baris.

## Matriks Fitur G3 - Presensi dan LMS

Grup: G3-attendance-lms. Domain: attendance (ATT), lms (LMS). Acuan: kode menang atas dokumen; status per 17 Okt 2026.

### Presensi

Mahasiswa hanya dapat MELIHAT presensi (ringkasan dan detail per pertemuan) lewat /student/attendance; dosen merekam batch per pertemuan dan melihat daftar. Belum ada: presensi QR/self check-in, sesi perkuliahan, jurnal/BAP, koreksi, rekap per kelas, eligibility ujian.

#### MOB-ATT-01 - Lihat ringkasan kehadiran per mata kuliah (semester berjalan)
- Peran: student
- Deskripsi: Daftar mata kuliah KRS Enrolled dengan jumlah pertemuan, hadir/izin/sakit/alpa, persentase, dan penanda di bawah minimum, ditambah ringkasan keseluruhan.
- Aturan bisnis:
  - percentage = present/total_meetings*100, 1 desimal; null bila belum ada pertemuan (kode)
  - Hanya izin/sakit TIDAK dihitung hadir: persentase hanya dari status present (kode; dokumen D02 tidak merinci)
  - minimum_percent dari setting academic.min_attendance_percent, default 75 (kode; sesuai docs 75%)
  - is_below_minimum true bila percentage < minimum_percent
  - Default semester = semester berjalan, jika tak ada = semester terakhir yang punya KRS Enrolled
- Kriteria penerimaan:
  - Given mahasiswa punya KRS Enrolled When buka Kehadiran Then tiap MK tampil total_meetings, present/permitted/sick/absent, percentage
  - Given persentase < minimum_percent Then MK ditandai di bawah batas dengan teks jelas
  - Given belum ada pertemuan Then percentage ditampilkan "-" bukan 0%
  - Given belum ada KRS Enrolled Then empty state, bukan error
- Status backend: available
- Endpoint: `GET /api/v1/student/attendance`
- Celah backend: -
- Status web: pages/portal/PortalAttendancePage.tsx (terhubung ke /student/attendance); one/pages/Learning.tsx AttendancePage = mock
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-DASH (G1) untuk login/shell
- Sumber: D03#6 (presensi mahasiswa), D14 (GET /student/attendance), D10-al-siakad, C2-be-academic-student.md#4, app/Modules/Academic StudentAcademicRecordController@attendance, D01#6.15.8, D08
- Catatan: Docs D01/D06 menyebut backend belum ada/attendance.read tak diberikan ke mahasiswa; kode: endpoint khusus mahasiswa sudah ada (student_portal.read).

#### MOB-ATT-02 - Pilih semester untuk riwayat kehadiran
- Peran: student
- Deskripsi: Mengganti semester pada ringkasan kehadiran (daftar semua semester ber-KRS Enrolled dikembalikan di field terms).
- Aturan bisnis:
  - Query academic_term_id opsional (ULID)
  - 409 "Semester tidak ditemukan pada riwayat KRS Anda." untuk semester tak dikenal
- Kriteria penerimaan:
  - Given terms>1 When pilih semester lain Then data dimuat ulang untuk term itu
  - Given term tak valid Then pesan 409 ditampilkan
- Status backend: available
- Endpoint: `GET /api/v1/student/attendance?academic_term_id=`
- Celah backend: -
- Status web: pages/portal/PortalAttendancePage.tsx (terhubung ke /student/attendance); one/pages/Learning.tsx AttendancePage = mock
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-ATT-01
- Sumber: C2-be-academic-student.md#4

#### MOB-ATT-03 - Lihat detail kehadiran per pertemuan
- Peran: student
- Deskripsi: Per MK: daftar pertemuan (nomor, tanggal, status Hadir/Izin/Sakit/Alpa, catatan), total dan persentase.
- Aturan bisnis:
  - Status: present=Hadir, permitted=Izin, sick=Sakit, absent=Alpa
  - Satu status per (krs_item, meeting_number); koreksi dosen menimpa
  - krsItem milik mahasiswa lain => 404
- Kriteria penerimaan:
  - Given ketuk MK Then tampil meetings[] berurut dengan status_label
  - Given krsItem bukan milik sendiri Then 404 ditangani tanpa membocorkan data
- Status backend: available
- Endpoint: `GET /api/v1/student/attendance/{krsItem}`
- Celah backend: -
- Status web: pages/portal/PortalAttendancePage.tsx (terhubung ke /student/attendance); one/pages/Learning.tsx AttendancePage = mock
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-ATT-01
- Sumber: D14, D03#6, C2-be-academic-student.md#4

#### MOB-ATT-04 - Peringatan kehadiran di bawah batas minimum
- Peran: student
- Deskripsi: Banner/alert untuk MK dengan kehadiran di bawah minimum (di dashboard dan di layar kehadiran).
- Aturan bisnis:
  - Dashboard memuat attendance.below_minimum[] dan alerts type "attendance" (kode)
  - Ambang 75% default, dapat diubah per universitas
- Kriteria penerimaan:
  - Given ada MK is_below_minimum Then banner peringatan menyebut MK dan persentase
  - Given semua di atas batas Then tidak ada banner
- Status backend: available
- Endpoint: `GET /api/v1/student/dashboard`; `GET /api/v1/student/attendance`
- Celah backend: Tidak ada push/notifikasi saat kehadiran turun di bawah batas
- Status web: one/pages/Learning.tsx (mock 75% + at-risk); web portal memakai dashboard alerts
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-ATT-01
- Sumber: D02 (peringatan minimum), D15, C2-be-academic-student.md#dashboard, D03

#### MOB-ATT-05 - Cek kelayakan ikut ujian berdasarkan kehadiran
- Peran: student, lecturer
- Deskripsi: Status apakah kehadiran memenuhi ambang untuk mengikuti UTS/UAS (blokir ujian).
- Aturan bisnis:
  - Dokumen: ambang minimum kehadiran memblokir ujian (SEVIMA benchmark)
  - Kode: BELUM ada enforcement di exam attempt maupun endpoint eligibility; hanya info is_below_minimum
- Kriteria penerimaan:
  - Given kehadiran < minimum Then layar ujian menampilkan peringatan (label "informasi")
  - Given backend eligibility ada Then tombol mulai ujian mengikuti hasilnya
- Status backend: missing
- Endpoint: -
- Celah backend: GET /krs-items/{krsItem}/exam-eligibility (rancangan D14) belum ada; Tidak ada enforcement di server saat mulai ujian; Kebijakan: apakah memblokir atau hanya memperingatkan belum diputuskan
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: MOB-ATT-01, grup ujian (exams)
- Sumber: D14 (exam-eligibility), D07, D16, D27
- Catatan: Sementara gunakan is_below_minimum dari ATT-01 sebagai peringatan informatif.

#### MOB-ATT-06 - Presensi mandiri dengan QR (scan QR dosen)
- Peran: student
- Deskripsi: Mahasiswa memindai QR dinamis yang ditampilkan dosen untuk mencatat hadir; ada rancangan geofence dan sync offline.
- Aturan bisnis:
  - Rancangan: token QR berputar (rotating), geofence opsional, idempotensi
  - Kode: TIDAK ada QR/self check-in; presensi hanya dicatat dosen
  - Play Store Data Safety menyatakan lokasi tidak dikumpulkan (D01) - konflik bila memakai GPS/geofence
  - Daftar "Presensi Kelas Hari Ini" membutuhkan jadwal + sesi kuliah (lecture_sessions belum ada)
- Kriteria penerimaan:
  - Given token QR valid dan mahasiswa Enrolled Then status present tercatat sekali (idempotent)
  - Given token kedaluwarsa/salah kelas Then ditolak dengan pesan jelas
  - Given tanpa backend Then menu menampilkan "Menunggu backend" tanpa data palsu
- Status backend: missing
- Endpoint: -
- Celah backend: Kontrak endpoint belum final: D11 /lms/attendance/qr-check-in vs D14 /student/attendance/qr-scan; Tabel attendance_qr_tokens, lecture_sessions, kolom method/lecture_session_id belum ada; Aturan geofence/lokasi belum ditetapkan
- Status web: none (web portal tanpa check-in; one/ mock tombol Lakukan Presensi)
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: MOB-ATT-15 (sisi dosen), Flutter: mobile_scanner/camera, permission CAMERA, Backend lecture_sessions
- Sumber: D11 (POST /lms/attendance/qr-check-in), D14 (POST /student/attendance/qr-scan), D13, D16, D10, D05, D07, D08, D02, D01#6.15.8
- Catatan: Perlu keputusan produk sebelum dibangun.

#### MOB-ATT-07 - Presensi mandiri non-QR (PIN, GPS/geofence, NFC, wajah/AI)
- Peran: student, lecturer
- Deskripsi: Metode presensi alternatif di luar QR.
- Aturan bisnis:
  - Disebut D02/D03/D05/D07 sebagai opsi; SEVIMA Premium untuk Presensi AI
  - Kode: tidak ada
- Kriteria penerimaan:
  - Tidak diimplementasikan di mobile tahap ini
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada endpoint/kontrak; kebutuhan infrastruktur biometrik & privasi
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D02, D03#3, D05, D07, D13, D16
- Catatan: Defer: infrastruktur besar, risiko privasi.

#### MOB-ATT-08 - Ajukan izin/sakit dengan surat pendukung
- Peran: student
- Deskripsi: Mahasiswa mengajukan izin tidak hadir dengan unggahan surat; dosen menyetujui.
- Aturan bisnis:
  - Hanya di D02; tidak ada status/aturan rinci
  - Kode: tidak ada endpoint presensi-izin (student/requests hanya leave/reactivation/data_change/letter/other)
- Kriteria penerimaan:
  - Given pengajuan terkirim Then status "Sedang Ditinjau" bisa dilihat
  - Given tanpa backend Then entri berlabel "Belum tersedia"
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada endpoint pengajuan izin presensi; belum jelas apakah berupa attendance-correction (D14)
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: MOB-ATT-09, POST /file-uploads
- Sumber: D02
- Catatan: Kemungkinan digabung dengan koreksi presensi.

#### MOB-ATT-09 - Ajukan koreksi presensi (mahasiswa)
- Peran: student
- Deskripsi: Mahasiswa mengajukan koreksi bila presensi salah, dengan bukti berkas.
- Aturan bisnis:
  - Rancangan: status pengajuan + persetujuan dosen; bukti via /file-uploads
  - Kode: belum ada
- Kriteria penerimaan:
  - Given mahasiswa mengisi alasan+bukti Then pengajuan tercatat dan dapat dilacak
  - Given tanpa backend Then label "Menunggu backend"
- Status backend: missing
- Endpoint: -
- Celah backend: POST /attendance-corrections (rancangan D14) belum ada; Bukti: POST /file-uploads sudah ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: POST /api/v1/file-uploads, MOB-ATT-17
- Sumber: D10, D13, D14, D15, D16

#### MOB-ATT-10 - Rekam presensi per pertemuan (batch)
- Peran: lecturer
- Deskripsi: Dosen memilih kelas, nomor pertemuan, tanggal, lalu menetapkan status tiap mahasiswa Enrolled dalam satu kiriman.
- Aturan bisnis:
  - meeting_number integer 1..255; meeting_date wajib; entries min 1
  - Status: present|permitted|sick|absent, catatan <=255
  - Upsert per (krs_item_id, meeting_number)
  - 409 "Salah satu mahasiswa bukan peserta aktif kelas ini." bila ada krs_item non-Enrolled; seluruh batch rollback
  - Kepemilikan kelas hanya ditegakkan bila feature flag academic.lecturer_ownership ON (default OFF) dan user tanpa classes.update
  - Tidak ada batas pertemuan 16 di server (docs menyebut 16 pertemuan)
- Kriteria penerimaan:
  - Given kelas diampu dosen When kirim batch Then 201 dan semua entri tersimpan
  - Given pertemuan sama dikirim ulang Then status tertimpa (koreksi)
  - Given satu krs_item tidak Enrolled Then 409 dan tak ada yang tersimpan
  - AC D03/BLACK-BOX §13: CRUD batch per pertemuan
- Status backend: available
- Endpoint: `POST /api/v1/class-sections/{classSection}/attendances`; `GET /api/v1/teaching/classes`; `GET /api/v1/krs-items?filter[class_section_id]=X&filter[status]=enrolled`
- Celah backend: Kepemilikan kelas tidak dipaksa saat flag OFF (dosen dapat merekam kelas lain); Tidak ada validasi tanggal vs jadwal / duplikat pertemuan
- Status web: pages/academic/AttendancePage.tsx (RecordAttendanceModal; gaya admin)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-01, MOB-LMS-02
- Sumber: D03#3, D06, D08, D10, D13, D14, D16, D26, D27, C3-be-academic-lecturer.md#Attendance, AttendanceController@batchStore
- Catatan: Idempotensi: kirim ulang aman karena upsert.

#### MOB-ATT-11 - Koreksi presensi yang sudah direkam (oleh dosen)
- Peran: lecturer
- Deskripsi: Dosen memuat presensi pertemuan sebelumnya dan mengubah status.
- Aturan bisnis:
  - Via upsert POST batch yang sama; tidak ada audit/alasan koreksi (kode)
  - Dokumen merancang alur koreksi berotorisasi (corrections) - belum ada
- Kriteria penerimaan:
  - Given pertemuan N sudah ada When kirim status baru Then status terbarui dan terbaca di student/attendance
- Status backend: partial
- Endpoint: `POST /api/v1/class-sections/{classSection}/attendances`; `GET /api/v1/attendances?filter[class_section_id]=X`
- Celah backend: Tidak ada log alasan/riwayat koreksi; Tidak ada endpoint ambil pertemuan tunggal; harus filter dari /attendances
- Status web: pages/academic/AttendancePage.tsx
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-ATT-10, MOB-ATT-12
- Sumber: D14, C3-be-academic-lecturer.md

#### MOB-ATT-12 - Lihat riwayat/daftar presensi kelas (dosen)
- Peran: lecturer
- Deskripsi: Daftar catatan presensi terfilter kelas/mahasiswa/status/rentang tanggal.
- Aturan bisnis:
  - Filter: student_id, class_section_id, status, date_from, date_to; sort meeting_date|meeting_number (default meeting_date desc)
  - Paginated (page/per_page)
  - Tanpa flag kepemilikan, dosen dapat melihat presensi semua kelas
- Kriteria penerimaan:
  - Given filter kelas When muat Then item paginated sesuai filter
  - Given halaman berikutnya Then dimuat tanpa duplikasi
- Status backend: available
- Endpoint: `GET /api/v1/attendances`
- Celah backend: Tidak ada endpoint daftar pertemuan per kelas; harus group by meeting_number di klien
- Status web: pages/academic/AttendancePage.tsx
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-01
- Sumber: D01, D03#3, D26, C3-be-academic-lecturer.md

#### MOB-ATT-13 - Rekap presensi per kelas / per mahasiswa (dosen)
- Peran: lecturer
- Deskripsi: Rekap jumlah hadir/izin/sakit/alpa dan persentase per mahasiswa dalam kelas, untuk memantau mahasiswa berisiko.
- Aturan bisnis:
  - Ambang 75% berlaku (setting)
  - Server belum menyediakan rekap; agregasi di klien dari /attendances?per_page=100
- Kriteria penerimaan:
  - Given kelas dengan pertemuan Then tabel rekap per mahasiswa dengan persen
  - Given mahasiswa < ambang Then ditandai
- Status backend: partial
- Endpoint: `GET /api/v1/attendances?filter[class_section_id]=X&per_page=100`; `GET /api/v1/krs-items?filter[class_section_id]=X&filter[status]=enrolled`
- Celah backend: GET /class-sections/{cs}/attendance-recap (D14) belum ada; agregasi klien rapuh untuk kelas besar; per_page maks 100 (kemungkinan perlu iterasi halaman)
- Status web: none (AttendancePage hanya daftar)
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-ATT-12
- Sumber: D02, D14 (attendance-recap), C3-be-academic-lecturer.md#gap 6, gaps
- Catatan: Beralih ke endpoint recap bila tersedia.

#### MOB-ATT-14 - Pantau kehadiran mahasiswa bimbingan (dosen PA)
- Peran: advisor
- Deskripsi: Dosen PA melihat rekap kehadiran mahasiswa bimbingannya.
- Aturan bisnis:
  - Dokumen D02: recap per course/student/semester untuk lecturer & advisor
  - Kode: peran academic_advisor tidak punya attendance.read; tak ada endpoint advisee-attendance
- Kriteria penerimaan:
  - Given dosen PA Then daftar bimbingan dengan persentase kehadiran (menunggu backend)
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada endpoint /advising/* kehadiran; attendance.read tidak diberikan ke academic_advisor
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: grup KRS/advising
- Sumber: D02, D11 (aturan)

#### MOB-ATT-15 - Buka/tutup sesi presensi QR dan rotasi token (dosen)
- Peran: lecturer
- Deskripsi: Dosen membuka sesi, menampilkan QR berputar, melihat hitungan live, menutup sesi.
- Aturan bisnis:
  - Rancangan: token berputar tiap N detik (nilai belum ditetapkan), dapat ditutup
- Kriteria penerimaan:
  - Given sesi dibuka Then QR berganti sesuai interval
  - Given sesi ditutup Then scan baru ditolak
- Status backend: missing
- Endpoint: -
- Celah backend: POST /teaching/lecture-sessions/{id}/qr/open|close atau /qr-tokens (dua versi kontrak); lecture_sessions belum ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: MOB-ATT-06, MOB-ATT-16
- Sumber: D11, D14, D15, D16, D13

#### MOB-ATT-16 - Sesi perkuliahan dan jurnal mengajar (BAP)
- Peran: lecturer
- Deskripsi: Dosen membuka pertemuan, mengisi jurnal/materi realisasi, deviasi RPS, konfirmasi ketua kelas.
- Aturan bisnis:
  - Dokumen: lecture_sessions dimiliki SIAKAD; jurnal & RPS deviasi baru
  - Kode: tidak ada lecture-sessions maupun jurnal
- Kriteria penerimaan:
  - Given pertemuan Then jurnal tersimpan terkait sesi (menunggu backend)
- Status backend: missing
- Endpoint: -
- Celah backend: GET/POST /teaching/lecture-sessions* belum ada; Realisasi RPS /obe/... belum ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: MOB-LMS-34
- Sumber: D02, D03, D05, D07, D08, D10, D11, D12, D13, D14, D16

#### MOB-ATT-17 - Persetujuan permohonan izin dan koreksi presensi (dosen)
- Peran: lecturer
- Deskripsi: Dosen menyetujui/menolak pengajuan koreksi dari mahasiswa.
- Aturan bisnis:
  - Rancangan: GET/POST /attendance-corrections, approve/reject
- Kriteria penerimaan:
  - Given pengajuan masuk Then dosen dapat approve/reject dengan alasan (menunggu backend)
- Status backend: missing
- Endpoint: -
- Celah backend: Endpoint /attendance-corrections belum ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: MOB-ATT-09
- Sumber: D02, D14, D15, D16

#### MOB-ATT-18 - Presensi dosen (kehadiran mengajar)
- Peran: lecturer
- Deskripsi: Pencatatan kehadiran dosen pada pertemuan.
- Aturan bisnis:
  - Hanya di D02; tak ada aturan
- Kriteria penerimaan:
  - -
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada definisi/endpoint
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D02
- Catatan: Perlu kejelasan: terkait HR attendance atau lecture_sessions.

### LMS

Sisi mahasiswa (mata kuliah, materi, tugas, pengumpulan) dan sisi dosen (kelas, materi, tugas, penilaian) tersedia di backend; halaman web dosen belum ada. Forum, pengumuman kelas, progres materi, gamifikasi, kelas LMS umum belum ada.

#### MOB-LMS-01 - Daftar kelas yang saya ampu (Kelas Saya)
- Peran: lecturer
- Deskripsi: Daftar kelas ajar semester berjalan dengan MK, jadwal, kapasitas, jumlah peserta, jumlah materi dan tugas.
- Aturan bisnis:
  - Default semester = is_current; filter academic_term_id, search kode kelas/nama MK
  - Non-classes.update hanya melihat kelas lecturer_id sendiri (selalu, tanpa flag)
  - Akun dosen tak terhubung ke lecturers => daftar kosong
  - per_page 1-100 default 20
- Kriteria penerimaan:
  - Given dosen pengampu Then hanya kelas sendiri tampil dengan counts
  - Given akun tak tertaut Then empty state berpesan
  - Given search Then hasil terfilter
- Status backend: available
- Endpoint: `GET /api/v1/teaching/classes`; `GET /api/v1/class-sections/{id}`
- Celah backend: Tidak ada endpoint jadwal-saya (my schedule) khusus; turunkan dari schedules kelas
- Status web: Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route Kelas Saya belum terdaftar; nav mengarah ke dashboard)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: -
- Sumber: D01, D02, D03#4, D10, D14, D15, D27 (T-2), C3-be-academic-lecturer.md#teaching/classes, C7 gaps

#### MOB-LMS-02 - Daftar peserta kelas
- Peran: lecturer
- Deskripsi: Daftar mahasiswa Enrolled suatu kelas (nama, NIM, krs_item_id) sebagai dasar presensi dan nilai.
- Aturan bisnis:
  - Hanya KRS status enrolled; krs_item_id diperlukan untuk presensi/nilai
  - Tanpa flag kepemilikan dosen dapat melihat peserta kelas lain; /students tanpa filter kelas membuka seluruh mahasiswa (risiko privasi - pakai filter kelas saja)
- Kriteria penerimaan:
  - Given kelas Then daftar peserta sesuai enrolled_count
  - Given kelas tanpa peserta Then empty state
- Status backend: available
- Endpoint: `GET /api/v1/krs-items?filter[class_section_id]=X&filter[status]=enrolled`; `GET /api/v1/students?filter[class_section_id]=X`
- Celah backend: Tidak ada endpoint roster khusus dengan scoping kepemilikan permanen
- Status web: pages/academic/ (picker di AttendancePage/GradesPage)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-01
- Sumber: D02, D01, C3-be-academic-lecturer.md

#### MOB-LMS-03 - Daftar mata kuliah semester berjalan (LMS mahasiswa)
- Peran: student
- Deskripsi: Kartu mata kuliah KRS Enrolled dengan jumlah materi, tugas, tugas belum dikumpulkan, dan ringkasan kehadiran.
- Aturan bisnis:
  - Hanya semester berjalan
  - Menyertakan krs_item_id untuk tautan ke kehadiran
- Kriteria penerimaan:
  - Given KRS Enrolled Then kartu MK dengan counts tampil
  - Given tidak ada KRS Then empty state
- Status backend: available
- Endpoint: `GET /api/v1/student/courses`
- Celah backend: -
- Status web: pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS lengkap)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: -
- Sumber: D14, D11, D15, D03#4, C2-be-academic-student.md#4

#### MOB-LMS-04 - Detail mata kuliah (pertemuan, materi, tugas, ujian, kehadiran)
- Peran: student
- Deskripsi: Halaman ruang kuliah: materi per pertemuan, tugas, ujian terjadwal, ringkasan kehadiran.
- Aturan bisnis:
  - Materi dikelompokkan "Pertemuan N" atau "Materi Umum" (meeting_number null)
  - Hanya materi/tugas terbit; 404/409 bila tidak terdaftar
- Kriteria penerimaan:
  - Given terdaftar Then meetings/assignments/exams/attendance tampil
  - Given tidak terdaftar Then pesan 409 tanpa data
- Status backend: available
- Endpoint: `GET /api/v1/student/courses/{classSection}`
- Celah backend: Tidak ada forum/pengumuman kelas/progres materi dalam respons
- Status web: pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS lengkap)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-03
- Sumber: D14, D15 (Parsial), C2-be-academic-student.md#4

#### MOB-LMS-05 - Lihat dan unduh materi kuliah
- Peran: student
- Deskripsi: Daftar materi per MK (file, link, video, teks) dan unduh berkas.
- Aturan bisnis:
  - Tipe materi: file|link|video|text
  - Unduh via /file-uploads/{id}/download dengan header Authorization (bukan signed URL)
  - Materi hanya tampil bila is_published
- Kriteria penerimaan:
  - Given materi file When ketuk Then berkas terunduh/terbuka
  - Given materi link/video Then dibuka di browser eksternal
  - Given materi teks Then isi tampil in-app
- Status backend: available
- Endpoint: `GET /api/v1/student/materials`; `GET /api/v1/student/courses/{classSection}`; `GET /api/v1/file-uploads/{id}/download`
- Celah backend: Tidak ada tracking progres/versi materi
- Status web: pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS lengkap)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: Flutter: url_launcher, open_file/path_provider
- Sumber: D03#4, D11, D13, D14, D01

#### MOB-LMS-06 - Daftar tugas dengan filter status
- Peran: student
- Deskripsi: Daftar tugas semester berjalan dengan status per mahasiswa dan counts.
- Aturan bisnis:
  - Filter status: all|not_submitted|submitted|late|graded|missed
  - Urut due_at naik; counts tak terpengaruh filter
  - Logika status: graded jika graded_at; late jika submission.is_late; submitted; missed bila lewat tenggat tanpa allow_late; else not_submitted
- Kriteria penerimaan:
  - Given tab status Then daftar sesuai filter dan badge counts
  - Given tugas lewat tenggat tanpa pengumpulan Then status missed
- Status backend: available
- Endpoint: `GET /api/v1/student/assignments?status=`
- Celah backend: -
- Status web: pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS lengkap)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: -
- Sumber: D01, D02, D03#4, D11, D15, C2-be-academic-student.md

#### MOB-LMS-07 - Detail tugas
- Peran: student
- Deskripsi: Deskripsi, tenggat, lampiran dosen, aturan berkas, status pengumpulan.
- Aturan bisnis:
  - can_submit = tidak graded AND (belum lewat tenggat OR allow_late) AND (belum ada submission OR allow_resubmission)
  - Batas ukuran 1-50 MB, ekstensi default pdf,doc,docx,xls,xlsx,jpg,jpeg,png
- Kriteria penerimaan:
  - Given tugas Then field aturan tampil dan tombol kumpulkan mengikuti can_submit
- Status backend: available
- Endpoint: `GET /api/v1/student/assignments/{assignment}`
- Celah backend: -
- Status web: pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS lengkap)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-06
- Sumber: C2-be-academic-student.md#4

#### MOB-LMS-08 - Kumpulkan tugas (berkas dan/atau catatan)
- Peran: student
- Deskripsi: Mahasiswa mengunggah berkas lalu mengirim submission dengan catatan.
- Aturan bisnis:
  - Langkah 1 POST /file-uploads (multipart, max 10MB default sistem; mimes pdf,jpg,jpeg,png,doc,docx,xls,xlsx), langkah 2 JSON {file_upload_id?<=26, notes?<=5000}
  - Minimal salah satu berkas atau catatan, jika tidak 422
  - 409 bila lewat tenggat tanpa allow_late/sudah dinilai/tidak boleh kumpul ulang; 422 ekstensi/ukuran melanggar aturan tugas
  - Pengumpulan setelah tenggat ditandai is_late
- Kriteria penerimaan:
  - Given tugas terbuka When unggah berkas valid dan kirim Then 200 "Tugas berhasil dikumpulkan." status submitted
  - Given lewat tenggat tanpa allow_late Then 409 pesan ditampilkan
  - Given ekstensi tak diizinkan Then validasi klien dan 422 server ditangani
- Status backend: available
- Endpoint: `POST /api/v1/file-uploads`; `POST /api/v1/student/assignments/{assignment}/submission`
- Celah backend: Tidak ada tugas kelompok; Batas FileUpload (10MB sistem) dapat lebih kecil dari max_file_size_mb tugas (maks 50)
- Status web: pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS lengkap)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-07, Flutter: file_picker
- Sumber: D01, D02, D03#4, D11, D13, D16, C2-be-academic-student.md
- Catatan: Docs D06/D01 menyebut backend tugas belum ada; kode sudah lengkap.

#### MOB-LMS-09 - Kumpulkan ulang / revisi tugas
- Peran: student
- Deskripsi: Mengirim ulang submission bila allow_resubmission aktif.
- Aturan bisnis:
  - Hanya bila allow_resubmission true dan belum dinilai
  - submission_count bertambah
- Kriteria penerimaan:
  - Given allow_resubmission true dan belum dinilai Then pengiriman ulang diterima
  - Given false Then 409 ditampilkan
- Status backend: available
- Endpoint: `POST /api/v1/student/assignments/{assignment}/submission`
- Celah backend: -
- Status web: pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS lengkap)
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-08
- Sumber: D02, D11, C2-be-academic-student.md

#### MOB-LMS-10 - Lihat nilai dan umpan balik tugas
- Peran: student
- Deskripsi: Skor, umpan balik dosen, waktu dinilai pada tugas.
- Aturan bisnis:
  - score <= max_score; decimal sebagai string
  - Mahasiswa diberi notifikasi saat dinilai (student.assignment_graded)
- Kriteria penerimaan:
  - Given tugas graded Then skor/max dan feedback tampil
- Status backend: available
- Endpoint: `GET /api/v1/student/assignments/{assignment}`; `GET /api/v1/notifications`
- Celah backend: Tidak ada push FCM
- Status web: pages/portal/ (PortalCourses/PortalMaterials/PortalAssignments; student LMS lengkap)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-07, Notifikasi in-app (G1)
- Sumber: D03#4, D01, C3-be-academic-lecturer.md
- Catatan: Verifikasi bentuk field skor pada assignmentRow.submission saat integrasi.

#### MOB-LMS-11 - Daftar materi per kelas (dosen)
- Peran: lecturer
- Deskripsi: Daftar materi kelas dengan tipe, pertemuan, status terbit.
- Aturan bisnis:
  - Tidak dipaginasi; urut meeting_number (NULL terakhir) lalu created_at
  - canManage: harus pengampu atau classes.update (selalu ditegakkan, 403 "Anda bukan dosen pengampu kelas ini.")
- Kriteria penerimaan:
  - Given kelas sendiri Then daftar materi; Given kelas lain Then 403 ditangani
- Status backend: available
- Endpoint: `GET /api/v1/class-sections/{cs}/materials`
- Celah backend: -
- Status web: Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route Kelas Saya belum terdaftar; nav mengarah ke dashboard)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-01
- Sumber: D02, D03#4, D14, D15, C3-be-academic-lecturer.md#Materials

#### MOB-LMS-12 - Tambah materi (file/link/video/teks)
- Peran: lecturer
- Deskripsi: Dosen menambah materi per pertemuan.
- Aturan bisnis:
  - type wajib file|link|video|text; field wajib per tipe (url/file_upload_id/teks) else 422
  - meeting_number 1..32 opsional; title <=255
  - is_published mengatur visibilitas
- Kriteria penerimaan:
  - Given tipe link tanpa url Then 422
  - Given file valid Then 201 dan terlihat mahasiswa bila terbit
- Status backend: available
- Endpoint: `POST /api/v1/file-uploads`; `POST /api/v1/class-sections/{cs}/materials`
- Celah backend: Tidak ada versi materi (D11 /lms/materials/{id}/versions)
- Status web: Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route Kelas Saya belum terdaftar; nav mengarah ke dashboard)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-11, Flutter: file_picker
- Sumber: D02, D03#4, D11, D14, D01

#### MOB-LMS-13 - Ubah, hapus, terbitkan/tarik materi
- Peran: lecturer
- Deskripsi: Edit metadata/berkas, toggle is_published, hapus materi.
- Aturan bisnis:
  - PUT field "sometimes"; DELETE mengembalikan data null
- Kriteria penerimaan:
  - Given ubah judul Then tersimpan
  - Given hapus Then hilang dari daftar mahasiswa
- Status backend: available
- Endpoint: `PUT /api/v1/course-materials/{id}`; `DELETE /api/v1/course-materials/{id}`
- Celah backend: -
- Status web: Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route Kelas Saya belum terdaftar; nav mengarah ke dashboard)
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-11
- Sumber: D03#4, D14

#### MOB-LMS-14 - Buat tugas (dosen)
- Peran: lecturer
- Deskripsi: Membuat tugas dengan tenggat, aturan keterlambatan, kumpul ulang, ukuran/ekstensi, skor maksimal, lampiran.
- Aturan bisnis:
  - due_at harus setelah sekarang; max_file_size_mb 1-50; max_score 1-1000
  - allowed_extensions subset ekstensi default
  - Mahasiswa Enrolled dinotifikasi saat terbit pertama kali
- Kriteria penerimaan:
  - Given data valid Then 201 "Tugas dibuat."
  - Given due_at lampau Then 422
- Status backend: available
- Endpoint: `POST /api/v1/class-sections/{cs}/assignments`; `POST /api/v1/file-uploads`
- Celah backend: Tidak ada tugas kelompok
- Status web: Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route Kelas Saya belum terdaftar; nav mengarah ke dashboard)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-01
- Sumber: D02, D03#4, D11, D14, D01

#### MOB-LMS-15 - Daftar, ubah, hapus tugas (dosen)
- Peran: lecturer
- Deskripsi: Mengelola tugas kelas.
- Aturan bisnis:
  - DELETE ditolak 409 bila sudah ada pengumpulan
  - Daftar tidak dipaginasi, urut due_at; submissions_count/graded_count tersedia
- Kriteria penerimaan:
  - Given ada submission Then hapus ditolak dengan pesan 409
- Status backend: available
- Endpoint: `GET /api/v1/class-sections/{cs}/assignments`; `PUT /api/v1/assignments/{id}`; `DELETE /api/v1/assignments/{id}`
- Celah backend: -
- Status web: Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route Kelas Saya belum terdaftar; nav mengarah ke dashboard)
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-14
- Sumber: D03#4, C3-be-academic-lecturer.md

#### MOB-LMS-16 - Lihat pengumpulan tugas (roster)
- Peran: lecturer
- Deskripsi: Daftar seluruh mahasiswa Enrolled dengan status pengumpulan dan detail submission.
- Aturan bisnis:
  - Status not_submitted|submitted|late|graded|missed; urut NIM
  - Roster penuh termasuk yang belum mengumpulkan
- Kriteria penerimaan:
  - Given tugas Then roster lengkap dengan status
- Status backend: available
- Endpoint: `GET /api/v1/assignments/{id}/submissions`
- Celah backend: Tidak ada pagination/filter
- Status web: Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route Kelas Saya belum terdaftar; nav mengarah ke dashboard)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-14
- Sumber: D03#4, D15, C3-be-academic-lecturer.md

#### MOB-LMS-17 - Nilai pengumpulan tugas dengan umpan balik
- Peran: lecturer
- Deskripsi: Memberi skor dan komentar.
- Aturan bisnis:
  - score wajib >=0 dan <= max_score (422 "Nilai maksimal tugas ini {max}.")
  - feedback <=5000
  - Mahasiswa dinotifikasi
- Kriteria penerimaan:
  - Given skor valid Then tersimpan dan mahasiswa melihat
  - Given skor > max Then 422
- Status backend: available
- Endpoint: `PUT /api/v1/assignment-submissions/{id}/grade`
- Celah backend: Tidak ada penilaian massal; tidak ada anotasi PDF
- Status web: Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route Kelas Saya belum terdaftar; nav mengarah ke dashboard)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-16
- Sumber: D02, D03#4, D11, D14, D01

#### MOB-LMS-18 - Unduh berkas pengumpulan mahasiswa (dosen)
- Peran: lecturer
- Deskripsi: Membuka berkas yang diunggah mahasiswa untuk dinilai.
- Aturan bisnis:
  - File dari field submission.file; download via /file-uploads/{id}/download
- Kriteria penerimaan:
  - Given submission berkas When ketuk Then berkas terbuka
- Status backend: partial
- Endpoint: `GET /api/v1/file-uploads/{id}/download`
- Celah backend: Otorisasi download oleh dosen pengampu terhadap berkas mahasiswa belum terverifikasi (endpoint FileManagement)
- Status web: Tidak ada halaman web (typed di studentPortalService.ts/teachingService, route Kelas Saya belum terdaftar; nav mengarah ke dashboard)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-16
- Sumber: C3-be-academic-lecturer.md#submissions
- Catatan: Uji di integrasi; jika 403, perlu perbaikan backend.

#### MOB-LMS-19 - Kirim nilai tugas ke komponen nilai akhir
- Peran: lecturer
- Deskripsi: Mendorong nilai tugas ke komponen penilaian kelas.
- Aturan bisnis:
  - Kode: hanya nilai akhir tunggal /krs-items/{id}/grade, tanpa komponen berbobot
- Kriteria penerimaan:
  - Given menunggu backend Then entri berlabel
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada komponen/bobot nilai, tidak ada send-to-grades
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: grup nilai
- Sumber: D11, D16

#### MOB-LMS-20 - Kuis LMS (mahasiswa)
- Peran: student
- Deskripsi: Daftar dan pengerjaan kuis di konteks kelas.
- Aturan bisnis:
  - Kode: tidak ada modul Kuis LMS; mesin ujian /student/exams ada (CBT) dan dicakup grup ujian
  - Halaman PortalQuizzesPage web = mock
- Kriteria penerimaan:
  - Given kuis diwakili ujian Then dapat dibuka dari modul ujian
- Status backend: missing
- Endpoint: `(ujian terkait) GET /api/v1/student/exams`
- Celah backend: Tidak ada /portal/quizzes atau konteks lms_class pada ujian
- Status web: mock pages/portal/PortalQuizzesPage.tsx
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: grup ujian (exams)
- Sumber: D01, D02, D06, D11, D15, D27
- Catatan: Hindari menduplikasi modul ujian.

#### MOB-LMS-21 - Buat kuis LMS (dosen)
- Peran: lecturer
- Deskripsi: Dosen membuat kuis untuk kelas.
- Aturan bisnis:
  - Kode: pembuatan ujian/bank soal sudah ada (exams.*, question_bank.*) dan aplikasi mobile punya wizard ujian; tidak ada entitas "kuis LMS" terpisah
- Kriteria penerimaan:
  - Given kuis = ujian Then dibuat lewat wizard ujian
- Status backend: partial
- Endpoint: `POST /api/v1/exams (lihat grup ujian)`
- Celah backend: Tidak ada kuis non-ujian
- Status web: one/exams pages (ujian)
- Status mobile saat ini: partial
- Prioritas: P2
- Cakupan rekomendasi: defer
- Dependensi: grup ujian
- Sumber: D02, D11
- Catatan: Dicakup grup ujian.

#### MOB-LMS-22 - Forum diskusi kelas
- Peran: student, lecturer
- Deskripsi: Thread dan balasan per kelas, moderasi.
- Aturan bisnis:
  - Kode: tidak ada; rekomendasi D11
- Kriteria penerimaan:
  - Given menunggu backend Then label "Belum tersedia"
- Status backend: missing
- Endpoint: -
- Celah backend: GET/POST /lms/classes/{id}/threads, /lms/threads/{id}/posts, PATCH /lms/posts/{id}/hide belum ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: MOB-LMS-04
- Sumber: D02, D11, D13, D14

#### MOB-LMS-23 - Pengumuman kelas
- Peran: student, lecturer
- Deskripsi: Dosen mengumumkan ke peserta kelas; mahasiswa membaca.
- Aturan bisnis:
  - Kode: tidak ada; mahasiswa punya feed /student/announcements (umum), dosen tanpa announcements.read
- Kriteria penerimaan:
  - Given menunggu backend Then label
- Status backend: missing
- Endpoint: `GET /api/v1/student/announcements (feed umum)`
- Celah backend: GET/POST /lms/classes/{id}/announcements belum ada; dosen tidak punya feed
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: -
- Sumber: D11, D14, D03

#### MOB-LMS-24 - Kelas LMS umum (non-akademik), gabung dengan kode, aktif/arsip
- Peran: student, lecturer
- Deskripsi: Kelas umum, join code, aktivasi/arsip kelas LMS.
- Aturan bisnis:
  - Kuota lms.general_classes_per_term (OD-27)
- Kriteria penerimaan:
  - Defer
- Status backend: missing
- Endpoint: -
- Celah backend: /lms/classes* belum ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D11, D13

#### MOB-LMS-25 - Catat progres materi
- Peran: student
- Deskripsi: Menandai materi dilihat/diunduh/selesai/persen video.
- Aturan bisnis:
  - Rancangan: view|download|mark_done|video_percent
- Kriteria penerimaan:
  - Given menunggu backend Then label
- Status backend: missing
- Endpoint: -
- Celah backend: Kontrak tak konsisten: POST /lms/materials/{id}/progress (D11) vs /student/lms/materials/{courseMaterial}/progress (D14)
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: build_contract_pending_backend
- Dependensi: MOB-LMS-05
- Sumber: D11, D14, D13

#### MOB-LMS-26 - Versi materi dan duplikasi ke semester berikutnya
- Peran: lecturer
- Deskripsi: Mengelola versi dan menyalin materi antarkelas/semester.
- Aturan bisnis:
  - SEVIMA: dalam pengembangan
- Kriteria penerimaan:
  - Defer
- Status backend: missing
- Endpoint: -
- Celah backend: POST /lms/materials/{id}/versions, /lms/classes/{id}/duplicate-from belum ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D05, D07, D11, D13

#### MOB-LMS-27 - Penilaian PDF dengan anotasi
- Peran: student, lecturer
- Deskripsi: Anotasi inline pada PDF tugas.
- Aturan bisnis:
  - SEVIMA Professional
- Kriteria penerimaan:
  - Defer
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D05, D07, D11, D13

#### MOB-LMS-28 - Gamifikasi (poin, lencana, leaderboard)
- Peran: student
- Deskripsi: Poin dan leaderboard aktivitas belajar.
- Aturan bisnis:
  - Gated feature lms.gamification
- Kriteria penerimaan:
  - Defer
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D05, D07, D11, D13, D14

#### MOB-LMS-29 - Student Engagement Score
- Peran: student
- Deskripsi: Skor keterlibatan mahasiswa.
- Aturan bisnis:
  - Feature lms.engagement_score
- Kriteria penerimaan:
  - Defer
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D05, D07, D11, D13, D16

#### MOB-LMS-30 - Lecture Performance Score dan dashboard dosen
- Peran: lecturer
- Deskripsi: Skor performa mengajar dan dashboard LMS dosen.
- Aturan bisnis:
  - Feature lms.lecture_performance_score
- Kriteria penerimaan:
  - Defer
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D05, D07, D11, D16

#### MOB-LMS-31 - Logging aktivitas klien (antrean offline)
- Peran: student
- Deskripsi: Kirim event aktivitas belajar batch.
- Aturan bisnis:
  - Rancangan POST /lms/activity-events/batch
- Kriteria penerimaan:
  - Defer
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D11

#### MOB-LMS-32 - Rekap aktivitas kelas (ringkasan pengumpulan)
- Peran: lecturer
- Deskripsi: Ringkasan jumlah pengumpulan/penilaian tugas dan materi per kelas.
- Aturan bisnis:
  - Dapat dihitung dari submissions_count/graded_count dan materials_count
- Kriteria penerimaan:
  - Given kelas Then ringkasan tampil
- Status backend: partial
- Endpoint: `GET /api/v1/teaching/classes`; `GET /api/v1/class-sections/{cs}/assignments`
- Celah backend: Tidak ada metrik aktivitas/engagement
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: build_integrated
- Dependensi: MOB-LMS-01
- Sumber: D02

#### MOB-LMS-33 - Ringkasan LMS mahasiswa bimbingan (dosen PA)
- Peran: advisor
- Deskripsi: Dosen PA melihat keterlibatan LMS mahasiswa bimbingan.
- Aturan bisnis:
  - Hanya aturan di D11
- Kriteria penerimaan:
  - Defer
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada endpoint
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D11

#### MOB-LMS-34 - RPS dan realisasi pembelajaran
- Peran: lecturer
- Deskripsi: RPS semester (dan RPS otomatis) serta realisasi.
- Aturan bisnis:
  - SEVIMA benchmark; OBE belum dibangun
- Kriteria penerimaan:
  - Defer
- Status backend: missing
- Endpoint: -
- Celah backend: /obe/class-sections/{id}/realization belum ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D07, D12

#### MOB-LMS-35 - MOOC, SPADA, dan video pembelajaran interaktif
- Peran: student, lecturer
- Deskripsi: Fitur ekosistem SEVIMA/Kemdikbud.
- Aturan bisnis:
  - Tier Growth/Premium
- Kriteria penerimaan:
  - Defer
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D05, D07, D11

#### MOB-LMS-36 - Progres CPMK, rekomendasi, sertifikat penyelesaian
- Peran: student, lecturer
- Deskripsi: E-learning lanjutan (opsional).
- Aturan bisnis:
  - Review module, prioritas opsional
- Kriteria penerimaan:
  - Defer
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D02

#### MOB-LMS-37 - Tugas kelompok
- Peran: student, lecturer
- Deskripsi: Tugas individual atau kelompok.
- Aturan bisnis:
  - Hanya D02; kode: tugas individual saja
- Kriteria penerimaan:
  - Defer
- Status backend: missing
- Endpoint: -
- Celah backend: Tidak ada entitas grup
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: defer
- Dependensi: -
- Sumber: D02

### Pertanyaan belum terselesaikan

- Kontrak QR presensi bersaing: D11 POST /lms/attendance/qr-check-in vs D14 POST /student/attendance/qr-scan; belum ada keputusan, juga konflik GPS vs pernyataan Play Store Data Safety "lokasi tidak dikumpulkan"
- Apakah ambang kehadiran minimum (75%) memblokir ujian (hard block) atau hanya peringatan; belum ada endpoint exam-eligibility
- Otorisasi unduh berkas submission mahasiswa oleh dosen via /file-uploads/{id}/download belum terverifikasi; juga batas FileUpload 10MB vs max_file_size_mb tugas hingga 50MB
- Flag academic.lecturer_ownership default OFF: dosen dapat merekam/melihat presensi kelas lain; belum diketahui nilai flag di produksi
- Persentase kehadiran menghitung hanya present (izin/sakit tidak dihitung hadir) - perlu konfirmasi kebijakan akademik
- Kontrak progres materi tidak konsisten antara D11 dan D14; dokumen D06/D01/D26 usang (menyebut backend belum ada) - kode adalah acuan

## Matriks Fitur G4 - Ujian CBT, Penilaian/OBE, EDOM

Grup G4-exam-grading-edom; total 67 baris. Sumber kebenaran: kode (rute `app/Modules/Academic/Routes/api.php`) mengungguli dokumen. Dokumen blueprint academic-learning/* (11-13 Okt 2026) untuk penilaian per komponen, OBE dan EDOM adalah desain saja dan tidak ada endpoint-nya.

### Domain exam_cbt (ID MOB-EXAM-NN)

Backend CBT (pilihan ganda saja) lengkap untuk mahasiswa (`/student/exams*`, `/student/exam-attempts*`) dan dosen (`/exams*`, `/question-bank*`, rekap/ekspor/violasi). Mobile saat ini hanya punya konfigurasi ujian dosen (daftar, wizard, editor soal, pratinjau); belum ada pengerjaan ujian mahasiswa. Esai, proctoring kamera, AI soal, dan auto-submit terjadwal belum ada.

#### MOB-EXAM-01 - Daftar ujian saya dengan status pribadi

- **Peran:** student
- **Deskripsi:** Mahasiswa melihat ujian terpublikasi dari kelas tempat KRS-nya Enrolled, dengan status upcoming/available/in_progress/completed/expired, jadwal, durasi, sisa percobaan, dan skor bila boleh ditampilkan.
- **Aturan bisnis:**
  - Hanya ujian published pada kelas dengan KrsItem Enrolled (bukan dropped).
  - status dihitung ExamService::computeStudentStatus: upcoming|available|in_progress|completed|expired.
  - Respons TIDAK dipaginasi; urut published_at terbaru.
  - score/result_visible hanya terisi bila exam.show_result_after_submission=true dan attempt submitted.
- **Kriteria penerimaan:**
  - Given mahasiswa punya KRS Enrolled di kelas X dan ujian X published When buka daftar Then ujian tampil dengan status benar.
  - Given ujian draft Then tidak tampil; GET detail ujian draft -> 404.
  - Given KRS dropped Then GET detail -> 409 'Anda tidak terdaftar pada kelas untuk ujian ini.'
  - Given jaringan mati Then tampil state error dengan tombol coba lagi (bukan data palsu).
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/student/exams
  - GET /api/v1/student/exams/{exam}
- **Celah backend:**
  - Tidak ada field 'diblokir cekal' pada StudentExamResource (blokir baru diketahui saat start -> 409).
  - Tidak ada field server_now/deadline sehingga mobile harus menghitung sendiri.
- **Status web:** pages/portal/PortalExamsPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Login/tenant (G1); MOB-EXAM-03
- **Sumber:**
  - docs/RANCANGAN-AKUN-MAHASISWA.md#§4.2
  - docs/RANCANGAN-APLIKASI.md#4.18
  - docs/STATUS-IMPLEMENTASI.md#§5 Ujian Daring
  - docs/academic-learning/15-uiux-rules (D15 141)
  - app/Modules/Academic/Controllers/StudentExamController.php; app/Modules/Academic/Routes/api.php:118-126
- **Catatan:** Role student memiliki exam_participation.read. Dosen/advisor TIDAK dapat memanggil (403).

#### MOB-EXAM-02 - Checklist persiapan ujian & tips anti-pelanggaran

- **Peran:** student
- **Deskripsi:** Panduan statis sebelum mulai: perangkat/baterai/jaringan, Jangan Ganggu, tutup aplikasi lain, jangan tekan Home, ringkasan aturan.
- **Aturan bisnis:**
  - Konten statis (tidak butuh backend).
  - Wajib ditampilkan/ dikonfirmasi ringkas sebelum 'Mulai Ujian' (rekomendasi, bukan aturan server).
- **Kriteria penerimaan:**
  - Given mahasiswa menekan Mulai di layar detail Then layar aturan menampilkan: waktu berjalan dari Mulai, setiap pelanggaran -1 poin, jawaban autosave.
- **Status backend:** `available`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Tidak ada (konten statis).
- **Status web:** none (hanya video/tutorial)
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-01
- **Sumber:**
  - docs/video-tutorial-ujian/source/script.json#prep
  - docs/video-tutorial-ujian/source/script.json#rules

#### MOB-EXAM-03 - Mulai / lanjutkan percobaan ujian

- **Peran:** student
- **Deskripsi:** Memulai percobaan baru atau melanjutkan percobaan in_progress (idempoten). Soal, urutan soal dan urutan opsi diacak SEKALI saat start dan stabil setelah refresh/keluar-masuk aplikasi/putus koneksi.
- **Aturan bisnis:**
  - Idempoten: attempt in_progress dikembalikan, tidak diduplikasi.
  - 409 sebelum starts_at ('Ujian belum dapat dikerjakan — jadwal dimulai pada <ISO>.'), sesudah ends_at ('Ujian sudah berakhir.'), belum publish ('Ujian belum dipublikasikan.'), lewat max_attempts ('Batas maksimum percobaan ujian sudah tercapai.').
  - Klien tidak pernah mengirim krs_item_id; server resolve KRS Enrolled dan ExamParticipationPolicy@startOwn memeriksa kepemilikan.
  - Cekal 'exam' ditegakkan: 409 dengan errors.code=ACADEMIC_BLOCKED + errors.blocks[] (kode: AcademicBlockService ENFORCED_SERVICES=['krs','exam']).
  - Mode seleksi all|random|manual; randomize_questions; randomize_options; percobaan baru hanya setelah percobaan sebelumnya submitted.
  - Respons tidak pernah memuat is_correct.
- **Kriteria penerimaan:**
  - Given start dipanggil dua kali saat in_progress Then attempt id & urutan soal sama (TS-CBT-01).
  - Given sebelum starts_at / sesudah ends_at / melebihi max_attempts Then 409 dengan pesan di atas.
  - Given cekal aktif Then UI menampilkan alasan dari errors.blocks[].reason, tanpa memulai timer.
  - Given aplikasi dimatikan lalu dibuka Then start -> attempt yang sama dengan jawaban tersimpan.
- **Status backend:** `available`
- **Endpoint:**
  - POST /api/v1/student/exams/{exam}/start
  - GET /api/v1/student/exam-attempts/{examAttempt}
- **Celah backend:**
  - Tidak ada deadline/remaining_seconds/server_now di StudentExamAttemptResource (hitung min(started_at+duration, exam.ends_at)).
  - Pemeriksaan kehadiran minimum (ATTENDANCE_BELOW_MINIMUM) belum ada di kode.
- **Status web:** pages/portal/PortalExamTakingPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-01; MOB-EXAM-12
- **Sumber:**
  - docs/RANCANGAN-AKUN-MAHASISWA.md#§3,§4.2,§6
  - docs/EXAM_RANDOMIZATION.md#6
  - docs/academic-learning/14-test-scenarios.md#TS-CBT-01
  - docs/STATUS-IMPLEMENTASI.md#§5 Ujian Daring
  - app/Modules/Academic/Controllers/StudentExamController.php; app/Modules/Academic/Routes/api.php:118-126

#### MOB-EXAM-04 - Jawab soal dengan autosave per soal dan navigasi soal

- **Peran:** student
- **Deskripsi:** Setiap pilihan jawaban disimpan ke server saat dipilih/diubah; navigator nomor soal; navigasi mundur mengikuti allow_back_navigation; status hijau untuk soal terjawab; jawaban dapat dikosongkan.
- **Aturan bisnis:**
  - PUT answer {exam_question_id, exam_question_option_id|null}; null menghapus jawaban (updateOrCreate).
  - 409 bila attempt sudah submit/kedaluwarsa ('Waktu ujian sudah habis, jawaban tidak bisa disimpan lagi.'), soal bukan bagian snapshot, atau opsi bukan milik soal.
  - Hanya soal pilihan ganda (MCQ) yang didukung.
  - allow_back_navigation=false -> UI tidak boleh kembali ke soal sebelumnya (diterapkan klien; server tidak memaksa).
- **Kriteria penerimaan:**
  - Given mahasiswa memilih opsi Then PUT dikirim dan indikator 'tersimpan' muncul; reload menampilkan pilihan yang sama.
  - Given opsi manipulasi/di luar snapshot Then ditolak 409 dan UI memberi pesan.
  - Given jawaban setelah deadline Then 409 dan UI langsung menutup ujian (hasil dimuat ulang).
- **Status backend:** `available`
- **Endpoint:**
  - PUT /api/v1/student/exam-attempts/{examAttempt}/answer
  - GET /api/v1/student/exam-attempts/{examAttempt}
- **Celah backend:**
  - Tidak ada client_seq/idempotensi urutan (lihat MOB-EXAM-14).
- **Status web:** pages/portal/PortalExamTakingPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-03; MOB-EXAM-05; Flutter: antrean retry lokal sederhana
- **Sumber:**
  - docs/RANCANGAN-AKUN-MAHASISWA.md#§4.2
  - docs/video-tutorial-ujian/source/script.json#answer
  - docs/STATUS-IMPLEMENTASI.md#§5 Ujian Daring
  - app/Modules/Academic/Controllers/StudentExamController.php; app/Modules/Academic/Routes/api.php:118-126

#### MOB-EXAM-05 - Timer ujian berbasis server, peringatan, dan auto-submit

- **Peran:** student
- **Deskripsi:** Hitung mundur mm:ss di header; kuning <=300 dtk ('Waktu ujian hampir habis.'); merah <=60 dtk ('Waktu hampir habis! Ujian akan dikumpulkan otomatis.'); saat nol ujian dikumpulkan otomatis.
- **Aturan bisnis:**
  - Deadline efektif = min(started_at + duration_minutes, exam.ends_at) (ExamService::attemptDeadline).
  - Server hanya menutup attempt secara LAZY (finalizeIfExpired saat show/answer/submit/index) - tidak ada job terjadwal.
  - Waktu tetap berjalan saat aplikasi ditutup/HP mati.
  - Soal tak terjawab dihitung salah.
- **Kriteria penerimaan:**
  - Given 5 menit tersisa Then timer kuning + peringatan; 1 menit -> merah + peringatan.
  - Given waktu habis Then klien memanggil submit/GET attempt dan menampilkan status submitted.
  - Given jam perangkat bergeser Then sisa waktu tetap benar (dikalibrasi dari header Date server) dan server menjadi acuan akhir.
- **Status backend:** `partial`
- **Endpoint:**
  - GET /api/v1/student/exams/{exam}
  - GET /api/v1/student/exam-attempts/{examAttempt}
  - PATCH /api/v1/student/exam-attempts/{examAttempt}/submit
- **Celah backend:**
  - Tidak ada deadline/remaining_seconds/server_now pada attempt resource; klien menghitung dan menangani clock skew.
  - Tidak ada auto-submit terjadwal (hanya lazy).
- **Status web:** pages/portal/PortalExamTakingPage.tsx (timer server)
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-03
- **Sumber:**
  - docs/EXAM_RANDOMIZATION.md#7,#10
  - docs/video-tutorial-ujian/source/script.json#timer
  - docs/academic-learning/14-test-scenarios.md#TS-CBT-09..11
  - docs/STATUS-IMPLEMENTASI.md#§5 Ujian Daring
- **Catatan:** Backend minta ditambah server_now + deadline_at untuk kemudahan mobile.

#### MOB-EXAM-06 - Kumpulkan ujian dengan dialog konfirmasi

- **Peran:** student
- **Deskripsi:** Mahasiswa mengumpulkan ujian; skor dihitung otomatis; dialog konfirmasi menampilkan jumlah soal belum terjawab.
- **Aturan bisnis:**
  - PATCH submit idempoten (200 'Ujian berhasil dikumpulkan.').
  - Skor = poin benar/total poin*100 (bulat 2 desimal) - penalti, minimum 0.
  - Grade dari rentang nilai ujian bila ada, jika tidak LetterGrade::fromScore (>=85 A, 75 AB, 65 B, 55 BC, 45 C, 35 D, <35 E).
  - weighted_score = skor*weight_percentage/100 bila ujian berbobot.
- **Kriteria penerimaan:**
  - Given submit ditekan Then dialog konfirmasi; setelah konfirmasi attempt berstatus submitted.
  - Given submit ulang Then respons sukses tanpa mengubah skor.
- **Status backend:** `available`
- **Endpoint:**
  - PATCH /api/v1/student/exam-attempts/{examAttempt}/submit
- **Celah backend:**
  - (tidak ada)
- **Status web:** pages/portal/PortalExamTakingPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-04
- **Sumber:**
  - docs/video-tutorial-ujian/source/script.json#submit
  - docs/STATUS-IMPLEMENTASI.md#§5 Ujian Daring
  - app/Modules/Academic/Controllers/StudentExamController.php; app/Modules/Academic/Routes/api.php:118-126

#### MOB-EXAM-07 - Deteksi pelanggaran ujian dan penalti skor (pemetaan mobile)

- **Peran:** student
- **Deskripsi:** Aplikasi mendeteksi pindah aplikasi/kehilangan fokus/layar dan melaporkan ke server; setiap pelanggaran -1 poin; badge merah dan peringatan 'Pelanggaran terdeteksi ({n}x). Nilai akan dikurangi {n} poin.'
- **Aturan bisnis:**
  - violation_type enum: tab_switch, window_blur, fullscreen_exit, copy_attempt, paste_attempt, context_menu (berorientasi browser).
  - Setiap pelanggaran -1.0 poin seragam, akumulasi langsung; tidak dapat dihapus; TIDAK ada auto-submit setelah N pelanggaran (kebijakan klien saja).
  - 409 'Ujian ini sudah dikumpulkan, pelanggaran tidak lagi dicatat.'
  - Pemetaan mobile yang disarankan: AppLifecycleState.paused/inactive -> tab_switch/window_blur; keluar mode imersif -> fullscreen_exit; salin/tempel -> copy_attempt/paste_attempt.
  - Gunakan FLAG_SECURE/immersive mode di Android untuk mencegah screenshot (keputusan klien).
- **Kriteria penerimaan:**
  - Given app masuk background saat ujian Then POST violations tipe tab_switch dan penalti bertambah 1 (80 -> 79).
  - Given ujian sudah submitted Then 409 diabaikan dengan tenang di UI.
  - Given 3 kali keluar app Then violation_count=3 dan penalty_score=3.00.
- **Status backend:** `partial`
- **Endpoint:**
  - POST /api/v1/student/exam-attempts/{examAttempt}/violations
- **Celah backend:**
  - Enum bertipe browser; belum ada tipe mobile (app_background, screenshot, split_screen) -> dipetakan ke tipe yang ada.
  - Tidak ada kebijakan auto-submit server setelah N pelanggaran.
  - Penalti tidak dapat dikonfigurasi per ujian.
  - Catatan: ExamViolationAndScoringTest termasuk 17 tes gagal bawaan (fixture hilang).
- **Status web:** pages/portal/PortalExamTakingPage.tsx (browser events)
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-04; Flutter: WidgetsBindingObserver
- **Sumber:**
  - docs/video-tutorial-ujian/source/script.json#rules
  - docs/video-tutorial-ujian/source/script.json#example
  - docs/STATUS-IMPLEMENTASI.md#§5 Ujian Daring
  - docs/academic-learning/13-security-audit.md

#### MOB-EXAM-08 - Lihat hasil ujian dan review jawaban

- **Peran:** student
- **Deskripsi:** Setelah submit dan bila diizinkan dosen, mahasiswa melihat skor mentah, penalti, skor akhir, grade, skor berbobot, jumlah benar/salah, dan review tiap soal dengan kunci.
- **Aturan bisnis:**
  - Hanya bila attempt submitted DAN exam.show_result_after_submission=true; selain itu 409 'Ujian belum dikumpulkan, hasil belum tersedia.' / 'Hasil ujian belum tersedia. Menunggu dipublikasikan oleh dosen.'
  - Hasil memuat correct_option_id per soal; explanation selalu null.
  - 403 untuk attempt milik mahasiswa lain.
- **Kriteria penerimaan:**
  - Given show_result=true Then ringkasan + review soal tampil.
  - Given show_result=false Then pesan 'Menunggu dipublikasikan oleh dosen' tanpa angka.
  - Given attempt orang lain Then 403.
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/student/exam-attempts/{examAttempt}/result
- **Celah backend:**
  - Tidak ada pembukaan hasil terpisah per jadwal (hanya flag show_result_after_submission); 'publish-results' (D14) belum ada.
- **Status web:** pages/portal/PortalExamResultPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-06
- **Sumber:**
  - docs/RANCANGAN-AKUN-MAHASISWA.md#§4.2
  - docs/video-tutorial-ujian/source/script.json#result
  - docs/STATUS-IMPLEMENTASI.md#§5 Ujian Daring
  - app/Modules/Academic/Controllers/StudentExamController.php; app/Modules/Academic/Routes/api.php:118-126

#### MOB-EXAM-09 - Unduh PDF hasil ujian

- **Peran:** student
- **Deskripsi:** Mahasiswa mengunduh/membagikan PDF hasil ujian (hasil-ujian-<slug>-<id>.pdf).
- **Aturan bisnis:**
  - Guard sama dengan hasil (submitted + show_result_after_submission).
  - Content-Type application/pdf attachment.
- **Kriteria penerimaan:**
  - Given hasil tersedia Then PDF terunduh dan terbuka di penampil PDF.
  - Given hasil belum tersedia Then 409 ditampilkan.
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/student/exam-attempts/{examAttempt}/result/pdf
- **Celah backend:**
  - (tidak ada)
- **Status web:** pages/portal/PortalExamResultPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-08; Flutter: path_provider + open_filex/share_plus
- **Sumber:**
  - docs/STATUS-IMPLEMENTASI.md#§5 Ujian Daring
  - app/Modules/Academic/Controllers/StudentExamController.php; app/Modules/Academic/Routes/api.php:118-126

#### MOB-EXAM-10 - Ujian publik via tautan + NIM tanpa login

- **Peran:** student
- **Deskripsi:** Jalur tanpa login: info ujian dari accessToken, POST access dengan NIM -> session_token, lalu jawab/violasi/submit/hasil memakai session token.
- **Aturan bisnis:**
  - throttle 10/menit pada access; session_token 60 karakter (hash sha256 di server), berlaku hingga deadline + 24 jam.
  - Pesan 409: 'NIM mahasiswa tidak ditemukan.', 'Ujian belum tersedia.', 'Ujian telah berakhir.', 'Anda sudah menyelesaikan ujian ini.', ACADEMIC_BLOCKED.
  - Token regenerasi dosen membuat tautan lama 404 'Ujian tidak ditemukan atau link sudah tidak berlaku.'
  - KEAMANAN: NIM saja = identitas; siapa pun yang tahu NIM + tautan dapat mengerjakan. Mobile sebaiknya memakai alur terautentikasi.
- **Kriteria penerimaan:**
  - Given token valid + NIM terdaftar Then session_token diterima dan ujian dapat dikerjakan.
  - Given token lama Then pesan tautan tidak berlaku.
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/public/exams/{accessToken}
  - POST /api/v1/public/exams/{accessToken}/access
  - GET /api/v1/public/exam-attempts/{sessionToken}
  - PUT /api/v1/public/exam-attempts/{sessionToken}/answer
  - PATCH /api/v1/public/exam-attempts/{sessionToken}/submit
  - POST /api/v1/public/exam-attempts/{sessionToken}/violations
  - GET /api/v1/public/exam-attempts/{sessionToken}/result
  - GET /api/v1/public/exam-attempts/{sessionToken}/result/pdf
- **Celah backend:**
  - Masih di bawah tenant.resolve: kirim X-University-ID.
  - Tidak ada pemetaan accessToken -> ujian untuk pengguna login.
- **Status web:** exam-public/* (route /exam/:accessToken)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-EXAM-11
- **Sumber:**
  - docs/video-tutorial-ujian/source/script.json#access
  - docs/video-tutorial-ujian/source/script.json#errors
  - docs/STATUS-IMPLEMENTASI.md#§5 Ujian Daring; §13
  - app/Modules/Academic/Controllers/PublicExamController.php
- **Catatan:** Rekomendasi: defer; mahasiswa memakai alur terautentikasi (MOB-EXAM-01/03). Dipertimbangkan hanya untuk tamu tanpa akun app.

#### MOB-EXAM-11 - Buka ujian dari tautan (deep link) atau pindai QR dosen

- **Peran:** student
- **Deskripsi:** Mahasiswa mengetuk tautan ujian dari grup kelas atau memindai QR di proyektor, lalu aplikasi langsung membuka ujian terkait.
- **Aturan bisnis:**
  - Format tautan https://<host>/exam/<token>; regenerasi tautan menonaktifkan tautan & QR lama.
  - Tautan bersifat rahasia (hanya untuk kelas).
- **Kriteria penerimaan:**
  - Given tautan ujian diketuk dan app terpasang Then app terbuka di layar ujian tersebut (setelah login).
  - Given token tidak berlaku Then pesan jelas.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Tidak ada endpoint resolve token -> exam_id untuk pengguna login (hanya /public/exams/{token} yang tidak mengembalikan id).
  - Belum ada Android App Links/assetlinks.json dan host portal produksi.
  - Perlu paket pemindai QR (mobile_scanner).
- **Status web:** web: halaman /exam/:accessToken
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: GET /student/exams/by-token/{token} (usulan); Infrastruktur: App Links; Flutter: mobile_scanner, app_links
- **Sumber:**
  - docs/video-tutorial-ujian/source/script.json#access (D06 46,47)
- **Catatan:** Alternatif sementara: tampilkan daftar ujian (MOB-EXAM-01).

#### MOB-EXAM-12 - Penolakan akses ujian karena cekal keuangan/dispensasi (UTS/UAS)

- **Peran:** student
- **Deskripsi:** Mulai ujian ditolak bila ada cekal layanan 'exam' (mis. tagihan UTS/UAS belum lunas pada referensi H-7); dispensasi disetujui memulihkan akses sampai valid_until.
- **Aturan bisnis:**
  - POST start -> 409 errors.code=ACADEMIC_BLOCKED, errors.blocks[{block_id,block_type,source,reason,since}].
  - Aturan KEU: exam_midterm semua tagihan semester berjalan jatuh tempo sebelum referensi H-7 harus lunas; exam_final kecuali cicilan jatuh tempo setelah referensi; denda dikecualikan default.
  - Kode: hanya service 'krs' dan 'exam' yang ditegakkan (AcademicBlockService.php:44).
- **Kriteria penerimaan:**
  - Given cekal aktif Then layar menampilkan alasan + arahan ke Keuangan (tautan ke tagihan).
  - Given dispensasi disetujui Then start berhasil.
- **Status backend:** `partial`
- **Endpoint:**
  - POST /api/v1/student/exams/{exam}/start
- **Celah backend:**
  - Tidak ada pre-check di daftar ujian (hanya terlihat saat start).
  - Sumber cekal otomatis dari KEU (SIAKAD A.8) belum terverifikasi lengkap; docs KEU menyebut belum dibangun per 15 Okt 2026.
- **Status web:** pages/portal/PortalExamTakingPage.tsx (error)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-03; Modul KEU (cekal)
- **Sumber:**
  - docs/RANCANGAN_SISTEM_ADMINISTRASI_KEUANGAN_MAHASISWA_KEU.md#§6/F-14,§10.3,§15.2 AC-CKL-02
  - docs/academic-learning/03-cbt-cbt-ai.md#4.2
  - app/Modules/Academic (AcademicBlockService)
- **Catatan:** Tampilkan blocks[].reason apa adanya.

#### MOB-EXAM-13 - Blokir ujian karena kehadiran di bawah minimum

- **Peran:** student
- **Deskripsi:** Peserta tidak boleh memulai UTS/UAS bila kehadiran < batas minimum (contoh 75%, OD-06); dispensasi memulihkan.
- **Aturan bisnis:**
  - 409 ATTENDANCE_BELOW_MINIMUM (target); BR-CBT-PT-01..05.
- **Kriteria penerimaan:**
  - Given kehadiran 60% < 75% Then start -> 409 ATTENDANCE_BELOW_MINIMUM dan UI menampilkan persentase.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Kode tidak memeriksa kehadiran saat start; kode error ATTENDANCE_BELOW_MINIMUM belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: pemeriksaan kehadiran pada start; MOB-EXAM-03
- **Sumber:**
  - docs/academic-learning/03-cbt-cbt-ai.md#4.2
  - docs/academic-learning/06-integrasi-arsitektur.md#7.3
  - docs/SEVIMA_Penjelasan_Modul.md#7.4
- **Catatan:** Mobile cukup menangani kode 409 generik sampai backend siap.

#### MOB-EXAM-14 - Antrean jawaban offline dengan client_seq dan sinkron ulang

- **Peran:** student
- **Deskripsi:** Jawaban disimpan lokal saat offline dan dikirim berurutan saat koneksi kembali; timer tetap berjalan; jawaban setelah deadline ditolak.
- **Aturan bisnis:**
  - Idempoten per soal; client_seq lebih lama -> 409 ANSWER_OUT_OF_ORDER; ATTEMPT_CLOSED setelah deadline; masa tenggang academic.cbt.reconnect_grace_minutes; rate limit 120/menit per attempt (target).
- **Kriteria penerimaan:**
  - TS-CBT-02/03: Given offline 2 menit lalu online Then jawaban terkirim berurutan dan skor sama dengan saat online.
- **Status backend:** `missing`
- **Endpoint:**
  - PUT /api/v1/student/exam-attempts/{examAttempt}/answer (tanpa client_seq)
- **Celah backend:**
  - Body answer hanya {exam_question_id, exam_question_option_id}; client_seq/ANSWER_OUT_OF_ORDER/ATTEMPT_CLOSED/rate limit belum ada.
- **Status web:** none (web tanpa antrean)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: client_seq pada answer; Flutter: connectivity_plus + Hive
- **Sumber:**
  - docs/academic-learning/14-test-scenarios.md#TS-CBT-02,03
  - docs/academic-learning/13-security-audit.md#11
  - docs/academic-learning/06-integrasi-arsitektur.md#10.2
- **Catatan:** Retry sederhana tetap dibangun di MOB-EXAM-04 (last-write-wins per soal, aman karena updateOrCreate).

#### MOB-EXAM-15 - Soal esai/isian singkat untuk mahasiswa

- **Peran:** student
- **Deskripsi:** Menjawab soal esai/isian singkat di ujian.
- **Aturan bisnis:**
  - Doc: kolom manual_score, rubrik, anonymous grading (D11).
- **Kriteria penerimaan:**
  - Given soal esai Then jawaban teks tersimpan dan menunggu penilaian dosen.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Kode hanya MCQ; tidak ada jawaban teks, antrean penilaian, rilis hasil.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-EXAM-37
- **Sumber:**
  - docs/academic-learning/03-cbt-cbt-ai.md
  - docs/STATUS-IMPLEMENTASI.md#§5 (03-cbt C.1-C.5 belum)

#### MOB-EXAM-16 - Proctoring kamera dan persetujuan (consent)

- **Peran:** student, lecturer
- **Deskripsi:** Pengawasan kamera/snapshot dengan persetujuan peserta.
- **Aturan bisnis:**
  - Doc: tabel proctoring snapshots, consent; Business/Professional add-on.
- **Kriteria penerimaan:**
  - Given consent ditolak Then ujian tidak dimulai (target).
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Seluruh infrastruktur snapshot/penyimpanan/kebijakan privasi belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Infrastruktur penyimpanan media; Kebijakan privasi
- **Sumber:**
  - docs/academic-learning/03-cbt-cbt-ai.md
  - docs/academic-learning/13-security-audit.md
  - docs/SEVIMA_Penjelasan_Modul.md

#### MOB-EXAM-17 - Penanganan ujian susulan

- **Peran:** student, lecturer
- **Deskripsi:** Alur ujian susulan (tidak ikut ujian).
- **Aturan bisnis:**
  - Aktor dan alur belum didefinisikan di RANCANGAN-APLIKASI.
- **Kriteria penerimaan:**
  - Tidak ada (belum spesifik).
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Tidak ada spesifikasi maupun endpoint.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:**
  - docs/RANCANGAN-APLIKASI.md#4.18
- **Catatan:** Lihat pertanyaan terbuka.

#### MOB-EXAM-18 - Daftar ujian dengan status publikasi (dosen)

- **Peran:** lecturer
- **Deskripsi:** Dosen melihat daftar ujian (filter per kelas), status Draft/Dipublikasikan, jumlah pool, mode, jadwal.
- **Aturan bisnis:**
  - Filter filter[class_section_id], is_published; sort created_at|title; paginated.
  - Filter kepemilikan kelas HANYA bila flag academic.lecturer_ownership aktif (default OFF) -> mobile wajib memfilter kelas milik dosen sendiri (GET /lecturer-profile + /class-sections?filter[lecturer_id]).
- **Kriteria penerimaan:**
  - Given dosen punya 3 ujian Then tampil dengan chip Draft/Dipublikasikan.
  - Given flag ownership OFF Then daftar tetap hanya ujian kelas milik dosen (filter klien).
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/exams
- **Celah backend:**
  - Tanpa filter kepemilikan server (flag OFF) - dosen bisa melihat ujian kelas lain.
- **Status web:** pages/academic/ExamsPage.tsx
- **Status mobile saat ini:** partial (ExamListScreen; per_page=100, abaikan meta paginasi, chip warna hardcode)
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-19 (pemilih kelas ajar)
- **Sumber:**
  - docs/video-tutorial-ujian/source-dosen/script.json
  - docs/EXAM_MODULE.md#Flutter
  - docs/STATUS-IMPLEMENTASI.md#§5 Ujian Daring
  - app/Modules/Academic/Routes/api.php:84-114

#### MOB-EXAM-19 - Buat ujian untuk kelas (durasi, percobaan, jadwal, jumlah soal)

- **Peran:** lecturer
- **Deskripsi:** Dosen membuat ujian PG untuk satu kelas: judul, durasi, batas percobaan, jadwal opsional, jumlah soal per peserta, mode seleksi, flag acak, navigasi mundur, tampilkan hasil, bobot.
- **Aturan bisnis:**
  - Wajib: class_section_id, title, duration_minutes>=1, questions_per_participant>=1 ('Jumlah soal harus lebih dari 0.'), question_selection_mode.
  - ends_at harus setelah starts_at; max_attempts>=1 (umumnya 1); weight_percentage 0-100 opsional.
  - Kelas tidak dapat diubah setelah ujian dibuat; ujian baru berstatus Draft.
  - Respons 201 ExamResource.
- **Kriteria penerimaan:**
  - Given payload valid Then 201 dan ujian muncul sebagai Draft.
  - Given questions_per_participant=0 Then pesan validasi tampil di field.
  - Given ends_at <= starts_at Then validasi menolak.
- **Status backend:** `available`
- **Endpoint:**
  - POST /api/v1/exams
  - GET /api/v1/class-sections
- **Celah backend:**
  - Tanpa cek kepemilikan kelas (NIL ownership flag OFF).
- **Status web:** pages/academic/ExamsPage.tsx
- **Status mobile saat ini:** partial (wizard 3 langkah; ID kelas diketik manual, belum ada jadwal mulai/selesai, bobot)
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Pemilih kelas dari kelas ajar (G3 teaching/classes)
- **Sumber:**
  - docs/video-tutorial-ujian/source-dosen/script.json#create
  - docs/EXAM_MODULE.md#Model Data
  - docs/STATUS-IMPLEMENTASI.md#§5 Ujian Daring
  - app/Modules/Academic/Routes/api.php:84-114
- **Catatan:** Mobile: ganti input UUID dengan picker kelas ajar.

#### MOB-EXAM-20 - Ubah pengaturan ujian

- **Peran:** lecturer
- **Deskripsi:** Dosen mengubah pengaturan ujian (sometimes-rules) selama belum terpublikasi/bermasalah.
- **Aturan bisnis:**
  - PUT parsial; kelas tidak dapat diganti.
  - Setelah publish, soal terkunci (aturan soal).
- **Kriteria penerimaan:**
  - Given ujian draft Then perubahan tersimpan; field tidak dikirim tetap.
- **Status backend:** `available`
- **Endpoint:**
  - PUT /api/v1/exams/{id}
  - GET /api/v1/exams/{id}
- **Celah backend:**
  - (tidak ada)
- **Status web:** pages/academic/ExamDetailPage.tsx
- **Status mobile saat ini:** partial (route /akademik/ujian/:id/edit)
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-19
- **Sumber:**
  - docs/video-tutorial-ujian/source-dosen/script.json#edit
  - docs/EXAM_MODULE.md
  - app/Modules/Academic/Routes/api.php:84-114

#### MOB-EXAM-21 - Hapus ujian

- **Peran:** lecturer
- **Deskripsi:** Dosen menghapus ujian yang belum punya percobaan peserta.
- **Aturan bisnis:**
  - 409 'Ujian yang sudah memiliki percobaan peserta tidak dapat dihapus.'
  - Wajib dialog konfirmasi.
- **Kriteria penerimaan:**
  - Given ujian tanpa attempt Then terhapus; Given ada attempt Then 409 ditampilkan.
- **Status backend:** `available`
- **Endpoint:**
  - DELETE /api/v1/exams/{id}
- **Celah backend:**
  - (tidak ada)
- **Status web:** pages/academic/ExamsPage.tsx
- **Status mobile saat ini:** partial (tanpa dialog konfirmasi)
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-18
- **Sumber:**
  - app/Modules/Academic/Routes/api.php:84-114
  - docs/STATUS-IMPLEMENTASI.md#§5

#### MOB-EXAM-22 - Konfigurasi mode seleksi soal (all/random/manual) dan pengacakan

- **Peran:** lecturer
- **Deskripsi:** Dosen menentukan soal yang diterima peserta dan apakah urutan soal/opsi diacak per percobaan.
- **Aturan bisnis:**
  - all: semua soal pool; random: questions_per_participant dipilih acak per peserta; manual: soal is_selected, sama untuk semua urut order_index.
  - randomize_questions mengacak urutan; randomize_options mengacak opsi.
  - question_pool_size = jumlah exam_questions.
  - Publish ditolak bila manual terpilih < questions_per_participant.
- **Kriteria penerimaan:**
  - Given pool 12, qpp 10, mode random Then tiap peserta mendapat 10 soal berbeda.
  - Given mode manual dan terpilih 8 < 10 Then publish ditolak dengan pesan 'Soal belum mencukupi...'.
- **Status backend:** `available`
- **Endpoint:**
  - POST /api/v1/exams
  - PUT /api/v1/exams/{id}
- **Celah backend:**
  - (tidak ada)
- **Status web:** pages/academic/ExamsPage.tsx
- **Status mobile saat ini:** done (QuestionSelectionMode, validasi lokal exam_validation.dart)
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-19
- **Sumber:**
  - docs/EXAM_RANDOMIZATION.md#1-#5,#9
  - docs/EXAM_MODULE.md
  - docs/video-tutorial-ujian/source-dosen/script.json#create

#### MOB-EXAM-23 - Kelola soal ujian (tambah, ubah, hapus, tandai dipilih)

- **Peran:** lecturer
- **Deskripsi:** Dosen mengelola soal pilihan ganda khusus ujian: teks, poin, urutan, opsi (min 2, tepat 1 benar), is_selected.
- **Aturan bisnis:**
  - Opsi minimal 2 dan tepat satu is_correct.
  - Setelah publish soal tidak dapat ditambah/diubah/dihapus.
  - GET soal menampilkan is_correct (hanya untuk dosen).
- **Kriteria penerimaan:**
  - Given opsi benar ganda Then validasi menolak.
  - Given ujian published Then tombol tambah/ubah/hapus tidak tersedia dan server menolak.
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/exams/{id}/questions
  - POST /api/v1/exams/{id}/questions
  - PUT /api/v1/exam-questions/{id}
  - DELETE /api/v1/exam-questions/{id}
- **Celah backend:**
  - (tidak ada)
- **Status web:** pages/academic/ExamDetailPage.tsx
- **Status mobile saat ini:** partial (question_bank_screen berfungsi sebagai editor soal ujian; tanpa konfirmasi hapus, error mentah)
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-18
- **Sumber:**
  - docs/video-tutorial-ujian/source-dosen/script.json#questions
  - docs/EXAM_MODULE.md
  - app/Modules/Academic/Routes/api.php:84-114

#### MOB-EXAM-24 - Terapkan soal dari bank soal ke ujian

- **Peran:** lecturer
- **Deskripsi:** Dosen memilih butir bank soal (per mata kuliah) lalu menyalinnya ke ujian.
- **Aturan bisnis:**
  - Body question_bank_item_ids[]; usage_count bank bertambah (dari ExamQuestion bersumber bank).
  - Hanya sebelum publish.
- **Kriteria penerimaan:**
  - Given 5 butir dipilih Then 5 soal baru muncul di ujian dan usage_count naik.
- **Status backend:** `available`
- **Endpoint:**
  - POST /api/v1/exams/{id}/questions/apply-bank
  - GET /api/v1/question-bank
- **Celah backend:**
  - (tidak ada)
- **Status web:** pages/academic/ExamDetailPage.tsx (Terapkan)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-25
- **Sumber:**
  - docs/video-tutorial-ujian/source-dosen/script.json#questions
  - app/Modules/Academic/Routes/api.php:84-114

#### MOB-EXAM-25 - Kelola bank soal per mata kuliah

- **Peran:** lecturer
- **Deskripsi:** CRUD butir bank soal PG (course_id, teks, poin, opsi) dengan pencarian dan filter mata kuliah.
- **Aturan bisnis:**
  - search question_text; filter course_id; paginated.
  - Opsi minimal 2, satu benar.
  - Bank soal / workflow review (D11 'banks') belum ada: hanya items.
- **Kriteria penerimaan:**
  - Given dosen menambah butir Then muncul di daftar dan dapat diterapkan ke ujian.
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/question-bank
  - POST /api/v1/question-bank
  - GET /api/v1/question-bank/{id}
  - PUT /api/v1/question-bank/{id}
  - DELETE /api/v1/question-bank/{id}
- **Celah backend:**
  - Belum ada entitas 'bank' berstatus/workflow (hanya butir).
- **Status web:** pages/academic/QuestionBankPage.tsx (nama dari STATUS; verifikasi)
- **Status mobile saat ini:** partial/unclear (STATUS menyebut UI bank di Flutter; catatan C9 hanya editor soal ujian)
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Flutter
- **Sumber:**
  - docs/EXAM_MODULE.md
  - docs/video-tutorial-ujian/source-dosen/script.json#bank
  - docs/academic-learning/03-cbt-cbt-ai.md
  - app/Modules/Academic/Routes/api.php:84-114
- **Catatan:** Lihat unresolved tentang UI bank soal.

#### MOB-EXAM-26 - Validasi dan publikasi ujian (Distribusi Soal)

- **Peran:** lecturer
- **Deskripsi:** Kartu ringkasan 'Setiap peserta akan mendapatkan {qpp} soal ... dari {pool} soal' dengan error validasi; tombol Publish nonaktif selama ada error; setelah publish soal terkunci.
- **Aturan bisnis:**
  - Error: pool kosong; qpp > pool; manual terpilih < qpp; soal tanpa kunci/opsi.
  - ExamService::publish() final enforcer; izin exams.publish diperlukan (gate Flutter hanya mengecek exams.create/update -> server bisa 403).
  - Setelah publish: 'Ujian siap diakses sesuai jadwal'.
- **Kriteria penerimaan:**
  - Given qpp 15 pool 12 Then error merah dan Publish nonaktif; setelah 10 Then publish sukses -> Dipublikasikan.
- **Status backend:** `available`
- **Endpoint:**
  - PATCH /api/v1/exams/{id}/publish
- **Celah backend:**
  - (tidak ada)
- **Status web:** pages/academic/ExamDetailPage.tsx
- **Status mobile saat ini:** partial (langkah 3 wizard; izin exams.publish belum dicek di PermissionGate)
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-23; MOB-EXAM-24
- **Sumber:**
  - docs/EXAM_RANDOMIZATION.md#2,#3,#8
  - docs/EXAM_MODULE.md
  - docs/video-tutorial-ujian/source-dosen/script.json#publish

#### MOB-EXAM-27 - Rentang nilai huruf dan bobot ujian

- **Peran:** lecturer
- **Deskripsi:** Dosen menetapkan rentang nilai huruf per ujian (A,AB,B,BC,C,D,E) dan bobot ujian (%).
- **Aturan bisnis:**
  - Rentang tidak boleh tumpang-tindih atau berlubang (tulis 79,99 bukan 79); default A>=85, AB>=75, B>=65, BC>=55, C>=45, D>=35, E<35.
  - Bobot opsional (weight_percentage 0-100 pada ujian).
  - PUT ranges: ranges[{grade,min_score,max_score}]; tidak boleh grade ganda.
- **Kriteria penerimaan:**
  - Given rentang tidak mencakup 79,5 Then validasi menolak.
  - Given bobot 30% dan skor akhir 78 Then weighted 23.4.
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/exams/{id}/grade-ranges
  - PUT /api/v1/exams/{id}/grade-ranges
  - PUT /api/v1/exams/{id}
- **Celah backend:**
  - Dokumen D06 menyebut endpoint tidak terdokumentasi; kode menyediakannya (kode menang).
- **Status web:** pages/academic/ExamDetailPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-19
- **Sumber:**
  - docs/video-tutorial-ujian/source-dosen/script.json#grading
  - docs/video-tutorial-ujian/source-dosen/tutorial.html
  - app/Modules/Academic (ExamGradeRangeController)

#### MOB-EXAM-28 - Buat dan bagikan tautan akses ujian + QR (regenerasi)

- **Peran:** lecturer
- **Deskripsi:** Dosen membuat/menggenerasi ulang tautan akses unik dan QR untuk ujian terpublikasi; salin tautan, bagikan, unduh/tampilkan QR.
- **Aturan bisnis:**
  - PATCH access-link menghasilkan access_token baru; token lama langsung tidak berlaku; peserta yang sedang mengerjakan tidak terpengaruh.
  - Format tautan https://<host>/exam/{token}; QR digenerasi di klien.
  - access_token terkirim plaintext ke pemegang exams.read.
- **Kriteria penerimaan:**
  - Given Generate Link Then status Aktif + URL + QR; Regenerate -> token baru, token lama 404.
- **Status backend:** `available`
- **Endpoint:**
  - PATCH /api/v1/exams/{id}/access-link
- **Celah backend:**
  - Host portal untuk tautan perlu dikonfirmasi (mock portal.kampus.ac.id).
- **Status web:** pages/academic/ExamDetailPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Flutter: qr_flutter, share_plus
- **Sumber:**
  - docs/video-tutorial-ujian/source-dosen/script.json#access
  - app/Modules/Academic (ExamController@generateAccessLink)
  - app/Modules/Academic/Routes/api.php:84-114

#### MOB-EXAM-29 - Pantau peserta ujian secara langsung dengan peringatan pelanggaran

- **Peran:** lecturer
- **Deskripsi:** Daftar peserta (NIM, nama, raw, penalti, akhir, grade, pelanggaran, status) dengan refresh otomatis berkala dan alert pelanggaran terbaru.
- **Aturan bisnis:**
  - Status not_started|in_progress|completed.
  - Polling violations/recent?since=<ISO> tiap ~5 dtk; tidak ada push/WebSocket.
  - Hanya peserta KRS aktif kelas.
- **Kriteria penerimaan:**
  - Given mahasiswa berpindah app Then dalam <= 5-10 dtk alert muncul 'Nama (NIM): jenis - Pelanggaran #n - Penalti -1 - HH.MM.SS' dan kolom Pelanggaran bertambah.
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/exams/{id}/participants
  - GET /api/v1/exams/{id}/violations/recent
- **Celah backend:**
  - Tidak ada push real-time; tanpa filter pencarian pada participants.
- **Status web:** pages/academic/ExamDetailPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-18
- **Sumber:**
  - docs/video-tutorial-ujian/source-dosen/script.json#monitor
  - app/Modules/Academic/Routes/api.php:84-114

#### MOB-EXAM-30 - Linimasa pelanggaran per mahasiswa

- **Peran:** lecturer
- **Deskripsi:** Detail pelanggaran satu percobaan: ringkasan skor + daftar pelanggaran (jenis, waktu).
- **Aturan bisnis:**
  - Pelanggaran tidak dapat dihapus; dipakai sebagai bukti keberatan.
- **Kriteria penerimaan:**
  - Given mahasiswa 2 pelanggaran Then timeline menampilkan 2 entri; total penalti -2.00.
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/exam-attempts/{id}/violations
- **Celah backend:**
  - (tidak ada)
- **Status web:** pages/academic/ExamRecapPage.tsx (modal)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-32
- **Sumber:**
  - docs/video-tutorial-ujian/source-dosen/script.json#violations
  - app/Modules/Academic/Routes/api.php:84-114

#### MOB-EXAM-31 - Rekap nilai ujian dengan pencarian, filter status, dan ekspor CSV/PDF

- **Peran:** lecturer
- **Deskripsi:** Ringkasan (total, selesai, sedang, belum, rata-rata, tertinggi/terendah) dan tabel nilai; ekspor CSV/PDF.
- **Aturan bisnis:**
  - Statistik dari peserta yang selesai.
  - Ekspor: format=csv|pdf, file rekap-nilai-{exam}.csv/.pdf.
  - Pencarian/filter dilakukan klien (endpoint tanpa filter).
- **Kriteria penerimaan:**
  - Given 32 peserta 30 selesai Then ringkasan sesuai; ekspor menghasilkan file.
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/exams/{id}/recap
  - GET /api/v1/exams/{id}/recap/export?format=csv|pdf
- **Celah backend:**
  - Dokumen D06 menyebut tak ada endpoint; kode ada.
- **Status web:** pages/academic/ExamRecapPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Flutter: share_plus
- **Sumber:**
  - docs/video-tutorial-ujian/source-dosen/script.json#recap
  - app/Modules/Academic/Routes/api.php:84-114

#### MOB-EXAM-32 - Rumus skor ujian (Raw, Final, Grade, Weighted)

- **Peran:** student, lecturer
- **Deskripsi:** Aturan skor yang ditampilkan konsisten di pantauan, linimasa, rekap, dan hasil mahasiswa.
- **Aturan bisnis:**
  - Raw=(poin benar/total poin)*100 (2 desimal); Final=Raw-penalti (1/pelanggaran), min 0.
  - Grade dari rentang ujian atau default (>=85 A ... <35 E); Weighted=Final*bobot%.
  - Backend adalah sumber tunggal; mobile hanya menampilkan, tidak menghitung ulang.
- **Kriteria penerimaan:**
  - Contoh: 8/10 benar -> Raw 80; 2 pelanggaran -> 78; B 70-79.99; bobot 30% -> 23.4.
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/exams/{id}/recap
  - GET /api/v1/student/exam-attempts/{examAttempt}/result
- **Celah backend:**
  - (tidak ada)
- **Status web:** pages/portal/PortalExamResultPage.tsx; ExamRecapPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-EXAM-08; MOB-EXAM-31
- **Sumber:**
  - docs/EXAM_RANDOMIZATION.md#7
  - docs/video-tutorial-ujian/source-dosen/script.json#scoring
  - docs/video-tutorial-ujian/source/script.json#result

#### MOB-EXAM-33 - Unduh naskah ujian PDF (opsional dengan kunci jawaban)

- **Peran:** lecturer
- **Deskripsi:** Dosen mengunduh naskah ujian, dengan kunci jawaban bila with_answers=true.
- **Aturan bisnis:**
  - with_answers membutuhkan juga exams.update (STATUS/C7); header 'NASKAH UJIAN - {title}'.
- **Kriteria penerimaan:**
  - Given with_answers=true Then opsi benar bertanda centang.
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/exams/{id}/download?with_answers=1
- **Celah backend:**
  - D06 menyebut tak ada endpoint; kode menyediakan.
- **Status web:** pages/academic/ExamDetailPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Flutter: path_provider
- **Sumber:**
  - docs/video-tutorial-ujian/source-dosen/script.json#download
  - app/Modules/Academic/Routes/api.php:84-114

#### MOB-EXAM-34 - Pratinjau ujian sebagai mahasiswa (lokal)

- **Peran:** lecturer
- **Deskripsi:** Dosen pratinjau tampilan ujian (data lokal, tanpa membuat attempt).
- **Aturan bisnis:**
  - Tidak memanggil endpoint attempt.
- **Kriteria penerimaan:**
  - Given ujian draft Then pratinjau menampilkan soal sesuai konfigurasi.
- **Status backend:** `available`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Tidak perlu backend.
- **Status web:** none
- **Status mobile saat ini:** done (ExamPreviewScreen; Navigator.push di luar go_router)
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** -
- **Sumber:**
  - docs/EXAM_MODULE.md#Flutter

#### MOB-EXAM-35 - Rekam jawaban/kumpul atas nama mahasiswa (mode pengawas)

- **Peran:** lecturer
- **Deskripsi:** Endpoint admin/pengawas untuk memulai, menjawab, mengumpulkan percobaan atas nama mahasiswa.
- **Aturan bisnis:**
  - Bukan self-service mahasiswa; izin exam_attempts.create/update.
- **Kriteria penerimaan:**
  - Given pengawas memulai attempt untuk KRS Then attempt dibuat.
- **Status backend:** `available`
- **Endpoint:**
  - POST /api/v1/krs-items/{krs}/exams/{exam}/attempt
  - PUT /api/v1/exam-attempts/{id}/answer
  - PATCH /api/v1/exam-attempts/{id}/submit
- **Celah backend:**
  - (tidak ada)
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:**
  - app/Modules/Academic/Routes/api.php:110-114
- **Catatan:** Tidak ada kebutuhan mobile terdokumentasi.

#### MOB-EXAM-36 - Keputusan integritas: batalkan pelanggaran, perpanjang waktu, buka kembali percobaan

- **Peran:** lecturer
- **Deskripsi:** Peninjauan pelanggaran oleh pengawas/komite dan tindakan (void, extend-time, reopen).
- **Aturan bisnis:**
  - BR-CBT-PR-05: void hanya oleh komite.
- **Kriteria penerimaan:**
  - Given komite menyetujui void Then penalti dihapus (target).
- **Status backend:** `missing`
- **Endpoint:**
  - (rencana) PATCH /exam-violations/{id}/review
  - (rencana) POST /exam-attempts/{id}/integrity-decision
  - (rencana) PATCH /exam-attempts/{id}/extend-time
  - (rencana) PATCH /exam-attempts/{id}/reopen
- **Celah backend:**
  - Seluruh endpoint belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Peran exam_proctor/komite
- **Sumber:**
  - docs/academic-learning/03-cbt-cbt-ai.md
  - docs/academic-learning/14-api-roles

#### MOB-EXAM-37 - Penilaian manual esai (antrean penilaian) dan rilis hasil

- **Peran:** lecturer
- **Deskripsi:** Dosen menilai jawaban esai/isian, regrade bila kunci dikoreksi, lalu merilis hasil.
- **Aturan bisnis:**
  - BR-CBT-GR-01..06; manual_score 0..poin; penilaian anonim.
- **Kriteria penerimaan:**
  - Given antrean berisi jawaban Then dosen memberi skor dan hasil dapat dirilis.
- **Status backend:** `missing`
- **Endpoint:**
  - (rencana) GET /exams/{id}/grading-queue
  - (rencana) PUT /exam-attempt-answers/{id}/grade
  - (rencana) PATCH /exams/{id}/publish-results
- **Celah backend:**
  - Backend belum ada; soal esai belum didukung.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-EXAM-15
- **Sumber:**
  - docs/academic-learning/03-cbt-cbt-ai.md
  - docs/academic-learning/14-api-roles

#### MOB-EXAM-38 - Kirim skor ujian ke komponen nilai kelas

- **Peran:** lecturer
- **Deskripsi:** Skor ujian tervalidasi dikirim ke komponen nilai (idempoten; kosong bukan nol).
- **Aturan bisnis:**
  - BR-CBT-INT-04..09; 409 grades_already_published.
- **Kriteria penerimaan:**
  - Given skor tervalidasi Then komponen menerima nilai ternormalisasi (skor/maks*100).
- **Status backend:** `missing`
- **Endpoint:**
  - (rencana) POST /exams/{id}/send-to-grades
- **Celah backend:**
  - Bergantung komponen nilai (MOB-GRADE-05).
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-GRADE-05
- **Sumber:**
  - docs/academic-learning/03-cbt-cbt-ai.md
  - docs/academic-learning/06-integrasi-arsitektur.md

#### MOB-EXAM-39 - Generator soal berbasis AI (cbt.ai)

- **Peran:** lecturer
- **Deskripsi:** Draf soal dari AI yang wajib ditinjau dosen.
- **Aturan bisnis:**
  - Wajib review manusia; provider OD-11.
- **Kriteria penerimaan:**
  - Given draf AI Then tidak masuk ujian sebelum disetujui.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Belum ada backend/provider.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Provider AI
- **Sumber:**
  - docs/academic-learning/03-cbt-cbt-ai.md
  - docs/SEVIMA_Penjelasan_Modul.md

#### MOB-EXAM-40 - Penentuan pengawas, berita acara, dan rekap kehadiran ujian

- **Peran:** lecturer
- **Deskripsi:** Pengawas ditentukan admin; berita acara ujian dan rekap kehadiran.
- **Aturan bisnis:**
  - Aktor belum jelas; dokumen tidak menyebut dosen sebagai pengawas.
- **Kriteria penerimaan:**
  - Tidak ada (belum spesifik).
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Tidak ada spesifikasi/endpoint.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:**
  - docs/RANCANGAN-APLIKASI.md#4.18

#### MOB-EXAM-41 - Dosen penguji/pengawas ujian PMB (Penilaian Ujian)

- **Peran:** lecturer
- **Deskripsi:** Dosen yang ditugaskan melihat sesi ujian PMB dan mencatat kehadiran/skor peserta (aplikasi staf web).
- **Aturan bisnis:**
  - Hanya sesi yang ditugaskan; hanya status Hadir yang dinilai; nilai terkunci butuh persetujuan pejabat; data peserta minimal.
- **Kriteria penerimaan:**
  - Given sesi ditugaskan Then hanya sesi itu terlihat.
- **Status backend:** `partial`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Izin pmb_scores.create & pmb_exam_attendance.update ada pada role lecturer; endpoint/kontrak detail dikaji di grup PMB.
- **Status web:** modul PMB staf web
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Grup PMB
- **Sumber:**
  - docs/manual-pmb (D30, D32)

### Domain grading_obe (ID MOB-GRADE-NN)

Kode hanya menyediakan nilai akhir tunggal per KRS item (`PUT /krs-items/{id}/grade`) dan `GET /grades`. Tidak ada komponen nilai, bobot, finalisasi/publikasi, revisi, atau OBE (CPMK/CPL/RPS). Mobile belum punya layar nilai.

#### MOB-GRADE-01 - Lihat daftar nilai kelas (dosen)

- **Peran:** lecturer
- **Deskripsi:** Dosen melihat nilai yang sudah diinput per kelas: roster (krs-items enrolled) digabung dengan nilai.
- **Aturan bisnis:**
  - GET /grades filter student_id, class_section_id, letter_grade; sort created_at|score; paginated.
  - Roster dan krs_item_id dari GET /krs-items?filter[class_section_id]=X&filter[status]=enrolled (krs.read).
  - Ownership server hanya bila flag aktif (default OFF): filter kelas milik dosen di klien.
- **Kriteria penerimaan:**
  - Given kelas 30 mahasiswa 10 sudah dinilai Then 30 baris tampil, 10 berisi nilai, 20 'Belum dinilai'.
- **Status backend:** `available`
- **Endpoint:**
  - GET /api/v1/grades
  - GET /api/v1/krs-items
  - GET /api/v1/class-sections
- **Celah backend:**
  - Tanpa endpoint grade-sheet gabungan; klien menggabungkan dua daftar.
  - Tidak ada pembatasan kelas milik sendiri di server (flag OFF).
- **Status web:** pages/academic/GradesPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Pemilih kelas ajar (G3)
- **Sumber:**
  - docs/RANCANGAN-AKUN-DOSEN.md#§4.2
  - docs/video-tutorial (D06 71)
  - app/Modules/Academic/Controllers/GradeController.php

#### MOB-GRADE-02 - Input / ubah nilai akhir mahasiswa dengan konversi huruf otomatis

- **Peran:** lecturer
- **Deskripsi:** Dosen memasukkan skor 0-100 per KRS item; huruf dihitung otomatis atau dioverride.
- **Aturan bisnis:**
  - score wajib 0-100; letter_grade opsional (A,AB,B,BC,C,D,E) - bila dikirim menimpa konversi dan skor tetap.
  - Skala (LetterGrade::fromScore, hardcoded; tidak per-tenant): >=85 A 4.00; 75-84 AB 3.50; 65-74 B 3.00; 55-64 BC 2.50; 45-54 C 2.00; 35-44 D 1.00; <35 E 0.00.
  - Satu Grade per KrsItem (updateOrCreate); input ulang mengoreksi baris yang sama; submitted_at=now().
  - 409 'Tidak dapat menilai KRS yang sudah dibatalkan.' (dropped) dan 409 'KRS ini belum disetujui dosen wali, sehingga belum dapat dinilai.' (belum Enrolled).
  - Tidak ada langkah publish/kunci/notifikasi: nilai langsung terlihat mahasiswa di /student/grades (kode).
- **Kriteria penerimaan:**
  - Given skor 78 tanpa override Then huruf AB.
  - Given skor 90 dengan letter_grade B Then tersimpan B, skor 90.
  - Given skor 101 Then validasi 422.
  - Given KRS dropped Then 409 tampil.
- **Status backend:** `available`
- **Endpoint:**
  - PUT /api/v1/krs-items/{krsItem}/grade
- **Celah backend:**
  - Tidak ada cek bahwa dosen mengampu kelas (NIL-09) bila flag academic.lecturer_ownership OFF.
  - Tanpa status draft/final: setiap simpan langsung final bagi mahasiswa.
- **Status web:** pages/academic/GradesPage.tsx (GradeFormModal)
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-GRADE-01
- **Sumber:**
  - docs/RANCANGAN-AKUN-DOSEN.md#§4.2,§6.1,§8.1
  - docs/BLACK-BOX-TESTING.md#§12 (GRD-01..04, NIL-09)
  - docs/STATUS-IMPLEMENTASI.md#§3
  - app/Modules/Academic/Routes/api.php:79

#### MOB-GRADE-03 - Input nilai massal per kelas (berurutan di klien)

- **Peran:** lecturer
- **Deskripsi:** Dosen mengisi banyak nilai sekaligus dari satu layar lembar nilai.
- **Aturan bisnis:**
  - Tidak ada endpoint bulk: klien mengulang PUT per krs_item; harus menampilkan keberhasilan/kegagalan per baris dan retry baris gagal.
  - Impor Excel tidak ada.
- **Kriteria penerimaan:**
  - Given 30 baris diisi Then 30 PUT dikirim; baris gagal ditandai dan dapat diulang.
- **Status backend:** `partial`
- **Endpoint:**
  - PUT /api/v1/krs-items/{krsItem}/grade (loop)
- **Celah backend:**
  - Tidak ada bulk save atomik/versi (D14: PUT grade-sheet/scores), tidak ada impor Excel.
- **Status web:** pages/academic/GradesPage.tsx (satu per satu)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-GRADE-02
- **Sumber:**
  - docs/RANCANGAN-APLIKASI.md (MVP #12)
  - docs/STATUS-IMPLEMENTASI.md (gap bulk)

#### MOB-GRADE-04 - Impor nilai dari Excel

- **Peran:** lecturer
- **Deskripsi:** Impor lembar nilai dari berkas Excel.
- **Aturan bisnis:**
  - Doc: MVP #12 input satu/massal/Excel.
- **Kriteria penerimaan:**
  - Given berkas valid Then nilai terisi (target).
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Tidak ada endpoint impor.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:**
  - docs/RANCANGAN-APLIKASI.md

#### MOB-GRADE-05 - Susun komponen nilai dan bobot kelas

- **Peran:** lecturer
- **Deskripsi:** Dosen mengatur komponen penilaian (Tugas, UTS, UAS, dst.) dan bobot total 100%.
- **Aturan bisnis:**
  - Total bobot 100% (error WEIGHT_TOTAL_INVALID, target).
  - Tabel assessment_components, grade_scales (baru).
- **Kriteria penerimaan:**
  - Given bobot total 90% Then simpan ditolak WEIGHT_TOTAL_INVALID.
- **Status backend:** `missing`
- **Endpoint:**
  - (rencana) GET /class-sections/{classSection}/assessment-components
  - (rencana) PUT /class-sections/{classSection}/assessment-components
- **Celah backend:**
  - Seluruh modul komponen belum dibangun (STATUS A.6); skala nilai hardcoded.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend penilaian
- **Sumber:**
  - docs/academic-learning/04-siakad-penilaian.md (D10)
  - docs/academic-learning/14-api-roles
  - docs/academic-learning/13-erd
- **Catatan:** Kontrak dari D14.

#### MOB-GRADE-06 - Lembar nilai per komponen (simpan batch dengan versi)

- **Peran:** lecturer
- **Deskripsi:** Dosen mengisi nilai per komponen per mahasiswa; nilai akhir dihitung; null = belum diisi.
- **Aturan bisnis:**
  - score 0-100; null=belum diisi; GRADE_INCOMPLETE memblokir submit; periode penilaian; IK pada batch.
- **Kriteria penerimaan:**
  - Given komponen belum lengkap Then submit ditolak GRADE_INCOMPLETE.
- **Status backend:** `missing`
- **Endpoint:**
  - (rencana) GET /class-sections/{classSection}/grade-sheet
  - (rencana) PUT /class-sections/{classSection}/grade-sheet/scores
- **Celah backend:**
  - Belum ada; menggantikan PUT per krs-item.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-GRADE-05
- **Sumber:**
  - docs/academic-learning/14-api-roles (D14 131)
  - docs/academic-learning/15-uiux-rules (D15 137)

#### MOB-GRADE-07 - Validasi/tarik skor LMS dan CBT ke lembar nilai

- **Peran:** lecturer
- **Deskripsi:** Skor tugas/CBT diusulkan ke lembar nilai; dosen memvalidasi atau menarik ulang.
- **Aturan bisnis:**
  - BR-SIA-INT-02; normalisasi skor/maks*100; perubahan pasca publish hanya via revisi.
- **Kriteria penerimaan:**
  - Given skor sumber berubah Then badge 'Ada pembaruan dari sumber'.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Belum ada tabel assessment_scores/endpoint.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-GRADE-05; MOB-EXAM-38
- **Sumber:**
  - docs/academic-learning/06-integrasi-arsitektur.md#R5

#### MOB-GRADE-08 - Ajukan / finalisasi lembar nilai untuk publikasi

- **Peran:** lecturer
- **Deskripsi:** Dosen mengirim (submit) lembar nilai kelas; verifikasi/pengesahan; nilai dipublikasikan dan dikunci.
- **Aturan bisnis:**
  - grade_submissions (baru); GRADE_INCOMPLETE memblokir; setelah publish perubahan hanya lewat revisi.
  - Saat ini (kode): tidak ada submit/publikasi; nilai langsung terlihat mahasiswa.
- **Kriteria penerimaan:**
  - Given semua nilai lengkap Then submit sukses dan status menjadi submitted.
- **Status backend:** `missing`
- **Endpoint:**
  - (rencana) POST /class-sections/{classSection}/grade-sheet/submit
- **Celah backend:**
  - Belum ada alur finalisasi/persetujuan/kunci/publikasi/notifikasi nilai.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-GRADE-05; MOB-GRADE-06; Push notifikasi
- **Sumber:**
  - docs/academic-learning/04-siakad-penilaian.md
  - docs/RANCANGAN-APLIKASI.md
  - docs/STATUS-IMPLEMENTASI.md#§3 (A.6)

#### MOB-GRADE-09 - Cetak / ekspor daftar nilai kelas

- **Peran:** lecturer
- **Deskripsi:** Dosen mencetak atau mengekspor daftar nilai kelas.
- **Aturan bisnis:**
  - Doc: 'cetak daftar nilai'.
- **Kriteria penerimaan:**
  - Given kelas dinilai Then PDF/CSV daftar nilai terunduh (target).
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Tidak ada endpoint ekspor nilai kelas (hanya rekap ujian).
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-GRADE-01
- **Sumber:**
  - docs/RANCANGAN-APLIKASI.md
- **Catatan:** Sementara ekspor lokal dari data GET /grades dimungkinkan.

#### MOB-GRADE-10 - Ajukan revisi nilai setelah publikasi (dosen)

- **Peran:** lecturer
- **Deskripsi:** Dosen mengajukan revisi nilai akhir melalui alur persetujuan dengan alasan.
- **Aturan bisnis:**
  - Koreksi dalam 30 hari; remedial maks nilai D, 1 per mata kuliah (D10).
- **Kriteria penerimaan:**
  - Given nilai final Then pengajuan revisi berstatus menunggu persetujuan.
- **Status backend:** `missing`
- **Endpoint:**
  - (rencana) POST /class-sections/{classSection}/grade-revisions
- **Celah backend:**
  - Belum ada tabel grade_revisions/endpoint.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-GRADE-08
- **Sumber:**
  - docs/academic-learning/04-siakad-penilaian.md
  - docs/academic-learning/14-api-roles
  - docs/semester-pendek (D26,D27)

#### MOB-GRADE-11 - Ajukan buka kembali lembar nilai

- **Peran:** lecturer
- **Deskripsi:** Dosen meminta lembar nilai dibuka kembali.
- **Aturan bisnis:**
  - Desain 2026-10-11.
- **Kriteria penerimaan:**
  - Given lembar final Then permintaan terkirim.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-GRADE-08
- **Sumber:**
  - docs/academic-learning/15-uiux-rules (D15 139)

#### MOB-GRADE-12 - Keberatan / remedial nilai oleh mahasiswa

- **Peran:** student
- **Deskripsi:** Mahasiswa mengajukan keberatan atau remedial setelah nilai terbit.
- **Aturan bisnis:**
  - Remedial maks D, 1 per MK.
- **Kriteria penerimaan:**
  - Given nilai terbit Then mahasiswa dapat mengajukan keberatan (target).
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:**
  - docs/academic-learning/04-siakad-penilaian.md

#### MOB-GRADE-13 - Lihat RPS kelas yang disahkan (mahasiswa)

- **Peran:** student
- **Deskripsi:** Mahasiswa melihat RPS kelasnya.
- **Aturan bisnis:**
  - Hanya RPS berstatus disahkan.
- **Kriteria penerimaan:**
  - Given RPS disahkan Then tampil.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Modul OBE belum dibangun.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:**
  - docs/academic-learning/05-obe-rps (D12)

#### MOB-GRADE-14 - Susun dan ajukan RPS (dosen), termasuk draf AI

- **Peran:** lecturer
- **Deskripsi:** Dosen menyusun RPS dan mengajukannya untuk pengesahan.
- **Aturan bisnis:**
  - Fitur add-on OBE.
- **Kriteria penerimaan:**
  - Given RPS lengkap Then dapat diajukan.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:**
  - docs/academic-learning/05-obe-rps
  - docs/SEVIMA_Penjelasan_Modul.md

#### MOB-GRADE-15 - CPMK, pemetaan CPL, dan pemetaan komponen nilai ke CPMK

- **Peran:** lecturer
- **Deskripsi:** Koordinator mengubah CPMK dan memetakan ke CPL; dosen memetakan komponen nilai ke CPMK.
- **Aturan bisnis:**
  - Bergantung komponen nilai.
- **Kriteria penerimaan:**
  - Given pemetaan 100% Then valid.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-GRADE-05
- **Sumber:**
  - docs/academic-learning/05-obe-rps (D12 108,110)

#### MOB-GRADE-16 - Capaian CPMK kelas dan realisasi RPS (dosen)

- **Peran:** lecturer
- **Deskripsi:** Dosen melihat capaian CPMK kelas dan realisasi RPS.
- **Aturan bisnis:**
  - Laporan opsional.
- **Kriteria penerimaan:**
  - Given nilai komponen Then capaian dihitung.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-GRADE-05
- **Sumber:**
  - docs/academic-learning/05-obe-rps
  - docs/RANCANGAN-APLIKASI.md (CPMK)

#### MOB-GRADE-17 - Capaian CPL pribadi (mahasiswa)

- **Peran:** student
- **Deskripsi:** Mahasiswa melihat capaian CPL/CPMK sendiri.
- **Aturan bisnis:**
  - Opsional.
- **Kriteria penerimaan:**
  - Given data capaian Then grafik tampil.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:**
  - docs/academic-learning/05-obe-rps (D12 106)

#### MOB-GRADE-18 - Capaian CPL mahasiswa bimbingan (dosen wali)

- **Peran:** advisor
- **Deskripsi:** Dosen wali melihat capaian CPL per mahasiswa bimbingan.
- **Aturan bisnis:**
  - Status belum jelas.
- **Kriteria penerimaan:**
  - Given mahasiswa bimbingan Then CPL tampil (target).
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Belum ada; juga tidak ada endpoint daftar mahasiswa bimbingan.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:**
  - docs/academic-learning/05-obe-rps (D12 112)

### Domain edom_survey (ID MOB-EDOM-NN)

Tidak ada backend EDOM. Web hanya mock tidak berrute. Seluruh baris adalah kontrak rencana (design 11 Okt 2026) dan tidak boleh memakai data palsu di app.

#### MOB-EDOM-01 - Daftar evaluasi dosen wajib (EDOM) periode berjalan

- **Peran:** student
- **Deskripsi:** Mahasiswa melihat target dosen x kelas pada periode terbuka dengan status Belum diisi/Sudah diisi/Dibebaskan/Kedaluwarsa, tenggat, ringkasan '3 dari 7 evaluasi selesai'.
- **Aturan bisnis:**
  - Tim pengajar: satu baris per dosen.
  - Kelayakan: krs_items.status=enrolled, students.status=active; opsi kehadiran minimum dan KRS disetujui.
  - Pembuatan partisipasi: saat periode dibuka (D12) vs saat form dibuka (06 R7) - konflik desain.
- **Kriteria penerimaan:**
  - Given periode terbuka dan 7 target Then 7 baris dengan status benar.
  - Given tidak ada periode Then keadaan kosong jelas.
- **Status backend:** `missing`
- **Endpoint:**
  - (rencana) GET /api/v1/student/edom
- **Celah backend:**
  - Modul EDOM tidak ada di backend (tidak ada rute edom/evaluation di app/Modules/*/Routes/api.php).
  - Halaman web PortalLecturerEvaluationPage.tsx adalah mock tidak berrute (/portal/evaluasi-dosen hanya konstanta).
- **Status web:** mock (pages/portal/PortalLecturerEvaluationPage.tsx; tidak berrute)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend EDOM (Tahap 5)
- **Sumber:**
  - docs/academic-learning/07-edom (D12 101)
  - docs/RANCANGAN-APLIKASI-FLUTTER (D01)
  - docs/STATUS-IMPLEMENTASI.md#§6

#### MOB-EDOM-02 - Isi dan kirim formulir EDOM untuk satu dosen/kelas

- **Peran:** student
- **Deskripsi:** Formulir per target dengan butir berkelompok (likert, scale, single_choice, multiple_choice, text), indikator progres butir wajib, konfirmasi 'Jawaban tidak dapat diubah setelah dikirim'.
- **Aturan bisnis:**
  - BR-EDM-FIL-01 hanya bila periode terbuka (jam server) dan partisipasi pending.
  - FIL-02 butir wajib terisi, nilai dalam skala atau N/A bila diizinkan; 422 menyebut kode butir.
  - FIL-03 teks maks academic.edom.max_text_length (1000).
  - FIL-05 idempoten; kirim kedua -> 409 'Evaluasi sudah dikirim'.
  - FIL-06 respons tidak mengembalikan id jawaban; FIL-07 anonim tidak dapat diubah/ditarik; FRM-05 form tidak menanyakan identitas.
  - ELG-03 tanpa partisipasi pending -> 403.
  - Versi lama web mock: 4 pertanyaan skala 1-5 + saran.
- **Kriteria penerimaan:**
  - Given semua butir wajib terisi Then submit sukses dan status Sudah diisi.
  - Given kirim ulang Then 409 'Evaluasi sudah dikirim'.
  - Given periode tutup Then submit ditolak.
- **Status backend:** `missing`
- **Endpoint:**
  - (rencana) GET /api/v1/student/edom/targets/{targetId}
  - (rencana) POST /api/v1/student/edom/targets/{targetId}/submit
- **Celah backend:**
  - Modul EDOM tidak ada di backend (tidak ada rute edom/evaluation di app/Modules/*/Routes/api.php).
  - Halaman web PortalLecturerEvaluationPage.tsx adalah mock tidak berrute (/portal/evaluasi-dosen hanya konstanta).
- **Status web:** mock (PortalLecturerEvaluationPage.tsx)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-EDOM-01
- **Sumber:**
  - docs/academic-learning/07-edom (D12 102)
  - docs/academic-learning/14-test-scenarios.md

#### MOB-EDOM-03 - Draf jawaban EDOM anonim tersimpan di perangkat

- **Peran:** student
- **Deskripsi:** Pada mode anonim draf hanya disimpan di perangkat (secure storage) dengan kunci edom-draft:{targetId}, dihapus setelah submit atau periode tutup.
- **Aturan bisnis:**
  - Draf tidak berpindah antar perangkat; teks UI 'Draf tersimpan di perangkat ini'.
- **Kriteria penerimaan:**
  - Given isi sebagian lalu tutup app Then draf pulih; setelah submit draf terhapus.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Bergantung kontrak EDOM.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-EDOM-02; Flutter: flutter_secure_storage
- **Sumber:**
  - docs/academic-learning/07-edom (D12 103)

#### MOB-EDOM-04 - Pemberitahuan privasi dan anonimitas EDOM

- **Peran:** student
- **Deskripsi:** Pemberitahuan tidak dapat ditutup sebelum mengisi, memuat mode anonimitas dan n_min nyata; jawaban disimpan terpisah tanpa kunci penghubung; body submit tidak dicatat di log.
- **Aturan bisnis:**
  - BR-EDM-PRV-01..12; request body /student/edom/*/submit dikecualikan dari log (juga log klien).
- **Kriteria penerimaan:**
  - Given form dibuka Then notis tampil dengan n_min dari konfigurasi dan hanya hilang setelah konfirmasi.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Kontrak EDOM belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-EDOM-02
- **Sumber:**
  - docs/academic-learning/07-edom (D12 105)

#### MOB-EDOM-05 - Syarat EDOM sebelum melihat nilai (grade gate)

- **Peran:** student
- **Deskripsi:** Nilai kelas dengan EDOM belum diisi disembunyikan (penanda EDOM_REQUIRED) sampai selesai.
- **Aturan bisnis:**
  - Opsi per tenant (OD-10); tidak memengaruhi IPK/transkrip resmi/orang tua; berakhir setelah periode tutup.
- **Kriteria penerimaan:**
  - Given EDOM belum diisi dan opsi aktif Then nilai kelas tersebut tertutup dengan ajakan mengisi.
- **Status backend:** `missing`
- **Endpoint:**
  - (rencana) GET /api/v1/student/grades (EDOM_REQUIRED marker)
- **Celah backend:**
  - Kode /student/grades tidak memuat marker; belum ada gerbang.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-EDOM-01; Fitur nilai mahasiswa (grup akademik)
- **Sumber:**
  - docs/academic-learning/07-edom (D16 157)
  - docs/SEVIMA_Penjelasan_Modul.md

#### MOB-EDOM-06 - Laporan EDOM saya (dosen)

- **Peran:** lecturer
- **Deskripsi:** Setelah periode dipublikasikan dan responden >= n_min: indeks kategori, rerata butir, distribusi, radar, tren, komentar termoderasi, rerata prodi sebagai rentang, perbandingan antar-semester, aspek perlu perhatian.
- **Aturan bisnis:**
  - Dosen tidak pernah melihat siapa yang mengisi; tidak ada hitungan responden saat periode berjalan.
  - Disembunyikan bila responden < n_min (default 5, lantai 3).
  - Keandalan: low bila partisipasi <30% atau responden <10.
  - Perhatian: indeks<60, < rerata prodi-1SD, tren turun >=10.
  - Tanpa peringkat (BR-EDM-RPT-05); hanya target sendiri (PRV-05).
- **Kriteria penerimaan:**
  - Given responden 4 < n_min 5 Then laporan ditekan dengan pesan.
  - Given periode dipublikasikan Then hanya laporan milik dosen tersebut.
- **Status backend:** `missing`
- **Endpoint:**
  - (rencana) GET /api/v1/teaching/edom/reports
- **Celah backend:**
  - Belum ada. Lecturer role tidak punya menu EDOM.
- **Status web:** none (tanpa halaman)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend EDOM
- **Sumber:**
  - docs/academic-learning/07-edom (D12 104)
  - docs/RANCANGAN-AKUN-DOSEN.md#§4.6,§8.5

#### MOB-EDOM-07 - Ekspor PDF laporan EDOM pribadi (dosen)

- **Peran:** lecturer
- **Deskripsi:** Dosen mengekspor laporan EDOM sendiri ke PDF.
- **Aturan bisnis:**
  - Hanya target sendiri.
- **Kriteria penerimaan:**
  - Given laporan tersedia Then PDF terunduh.
- **Status backend:** `missing`
- **Endpoint:**
  - (rencana) GET /api/v1/edom/reports/{id}/export?format=pdf
- **Celah backend:**
  - Belum ada.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-EDOM-06
- **Sumber:**
  - docs/academic-learning/07-edom (D12 104)

#### MOB-EDOM-08 - Survei dan kuesioner umum

- **Peran:** student, lecturer
- **Deskripsi:** Menjawab survei/kuesioner umum kampus.
- **Aturan bisnis:**
  - Modul opsional (Prioritas Opsional).
- **Kriteria penerimaan:**
  - Belum ada.
- **Status backend:** `missing`
- **Endpoint:**
  - (tidak ada)
- **Celah backend:**
  - Tidak ada spesifikasi/endpoint.
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:**
  - docs/RANCANGAN-APLIKASI.md

### Pertanyaan terbuka

1. Apakah mahasiswa boleh melihat nilai di aplikasi sebelum ada alur publikasi nilai? Kode saat ini langsung menampilkan Grade di /student/grades begitu dosen menyimpan (tanpa finalisasi/kunci); desain target (grade_submissions) belum ada.
2. Bagaimana mobile harus menangani anti-curang (enum pelanggaran bertipe browser, tanpa auto-submit)? Perlu keputusan produk: pemetaan lifecycle -> tab_switch/window_blur, FLAG_SECURE, ambang auto-submit di klien, dan apakah backend menambah tipe pelanggaran mobile + deadline_at/server_now.
3. Apakah UI bank soal Flutter benar-ada? STATUS/D11 menyebut 'bank UI' tetapi catatan C9 hanya menemukan question_bank_screen sebagai editor soal ujian (tidak memanggil /question-bank).
4. Apakah ujian lewat tautan publik + NIM (identitas hanya NIM) boleh dipakai di mobile, dan bagaimana pemetaan token tautan/QR ke ujian terautentikasi (perlu endpoint resolve + App Links)?
5. Feature flag academic.lecturer_ownership default OFF: dosen dapat menilai/mengelola ujian kelas mana pun. Apakah diaktifkan sebelum rilis mobile (selain filter kelas klien)?
6. Modul EDOM (periode, form anonim, laporan dosen) hanya desain 11 Okt 2026; kapan backend dibangun dan konflik desain pembuatan partisipasi (saat periode dibuka vs saat form dibuka) harus diputuskan.
7. Ujian susulan, berita acara/penentuan pengawas ujian, dan peran dosen sebagai pengawas belum didefinisikan di dokumen.
8. Penilaian ujian PMB oleh dosen penguji/pengawas: kontrak endpoint berada di grup PMB; konfirmasi apakah termasuk scope app dosen.
9. Cek pelanggaran 'cekal' keuangan UTS/UAS: sumber cekal otomatis dari KEU belum terverifikasi lengkap (docs KEU menyebut SIAKAD A.8 belum dibangun per 15 Okt); hanya penegakan 409 ACADEMIC_BLOCKED pada start yang terverifikasi.
10. Nilai mahasiswa (KHS/transkrip/IPK) dan jadwal ujian di kalender berada di grup lain; baris mobile untuk tampilan nilai mahasiswa tidak dibuat di G4 (dirujuk silang).

## Matriks Fitur G5 — Keuangan Mahasiswa & Semester Pendek (Civitas One Android)

Cakupan: domain `finance` (prefix MOB-FIN) dan `short_semester` (prefix MOB-SP). Sumber kebenaran: kode (routes + AUDIT/C4-be-finance.md); dokumen docs/keuangan/* (12-13 Okt) menyebut 'design only' padahal API mahasiswa `/api/v1/student/finance/*` (22 endpoint) sudah ada dan web portal `keu/pages/portal/PortalFinancePages.tsx` sudah lengkap. Aplikasi mobile saat ini belum memiliki layar keuangan sama sekali.

Konvensi umum keuangan: auth:sanctum + tenant.access (header `X-University-ID`), permission `student_finance.read|create` (hanya role student; dosen dan dosen PA tidak punya akses keuangan). Envelope `{success,message,data,meta}`; nominal string desimal; 409 dengan `errors.code` untuk kesalahan bisnis; objek milik orang lain 404.

### Domain Keuangan (FIN)

Fitur mahasiswa: ringkasan, tagihan, pembayaran (online Xendit hosted checkout, transfer manual + bukti, deposit), kuitansi, cicilan, voucher, refund, beasiswa, UKT, cekal & dispensasi. Fitur dosen PA hanya berupa celah backend.

#### MOB-FIN-01 — Ringkasan keuangan mahasiswa (beranda Keuangan)

- **Peran:** student
- **Deskripsi:** Kartu ringkasan: sisa tagihan (outstanding), tunggakan (arrears), jumlah tagihan terbuka, jatuh tempo berikutnya (tanggal, nominal, termin ke-n), saldo deposit tersedia, jumlah bukti transfer menunggu verifikasi, status layanan KRS/ujian (diblokir/dapat diakses), status bebas tanggungan, dan kartu cekal aktif.
- **Aturan bisnis:**
  - Semua endpoint: auth:sanctum + tenant.access (header X-University-ID) + permission student_finance.read (GET) / student_finance.create (POST); hanya role student.
  - Nominal berupa string desimal 2 digit (mis. "1500000.00"); jangan di-parse sebagai float untuk perbandingan; format Rp/id-ID di klien.
  - outstanding = jumlah seluruh tagihan terbuka (unpaid|partial); arrears = tagihan terbuka yang is_overdue
  - next_due = termin belum lunas paling awal, jika tidak ada = tagihan terbuka paling awal (installment_seq null)
  - services hanya krs dan exam (hanya dua layanan yang ditegakkan di kode, bukan enam seperti desain web/docs)
  - Data transaksional tidak di-cache offline; layar memakai FLAG_SECURE (RANCANGAN_FLUTTER 6.15.14)
- **Kriteria penerimaan/pengujian:**
  - Given mahasiswa login When membuka Keuangan Then GET /student/finance/summary dipanggil dan 4 kartu angka tampil dari data server
  - Given pending_verification>0 When ringkasan tampil Then muncul info 'N bukti transfer sedang diverifikasi'
  - Given akun belum tertaut ke data mahasiswa When memanggil Then 403 'Akun ini tidak terhubung dengan data mahasiswa.' ditampilkan ramah
  - Given nominal Rp 1.500.000,00 When ditampilkan Then format 'Rp 1.500.000' tanpa pembulatan float
  - Offline: tampil state error/retry, bukan data cache
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/summary`
- **Celah backend:** -
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (PortalFinancePage, stat cards)
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Auth/tenant header (X-University-ID) dari scaffold login; Flutter: flutter_secure_storage tidak perlu; FLAG_SECURE via plugin (mis. flutter_windowmanager/screen_protector)
- **Catatan:** Docs keuangan (12-13 Okt) menyebut 'design only' tetapi kode sudah ada (22 endpoint student/finance).
- **Sumber:** docs/keuangan/03-*.md (FR-PRT-01); docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.15.14; docs/manual (D30) Ringkasan keuangan; AUDIT/C4-be-finance.md#GET /summary; app/Modules/Finance/Routes/api.php

#### MOB-FIN-02 — Daftar tagihan saya (Tagihan Saya) dengan filter status

- **Peran:** student
- **Deskripsi:** Daftar tagihan non-draft (semester/registrasi, biaya SKS, insidental, denda, biaya awal PMB, semester pendek, wisuda, saldo awal) berurutan due_date menurun, dengan status, sisa, jatuh tempo dan penanda terlambat.
- **Aturan bisnis:**
  - Semua endpoint: auth:sanctum + tenant.access (header X-University-ID) + permission student_finance.read (GET) / student_finance.create (POST); hanya role student.
  - Nominal berupa string desimal 2 digit (mis. "1500000.00"); jangan di-parse sebagai float untuk perbandingan; format Rp/id-ID di klien.
  - Draf tidak pernah ditampilkan (404 pada detail)
  - per_page default 25, rentang 1..100; filter status daftar dipisah koma (unpaid,partial) tanpa validasi
  - Status: unpaid Belum Dibayar, partial Dibayar Sebagian, paid Lunas, cancelled Dibatalkan, waived Dibebaskan; sisa = outstanding
  - bill_group: registration, credit, incidental, penalty, admission, short_term, graduation, opening_balance (tagihan PMB dan SP muncul di daftar yang sama)
- **Kriteria penerimaan/pengujian:**
  - Given 30 tagihan When scroll ke bawah Then halaman berikutnya dimuat (meta.current_page/last_page)
  - Given filter 'Belum lunas' When dipilih Then request memakai status=unpaid,partial
  - Given tagihan lewat jatuh tempo When tampil Then badge Terlambat (is_overdue)
  - KEU-AC-INV (daftar tagihan portal) terpenuhi
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/invoices?status=&per_page=&page=`
- **Celah backend:** -
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (tab Tagihan)
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-01
- **Catatan:** Rancangan Flutter menulis endpoint lama GET /invoices?filter[student_id]=me dan GET /payments; itu butuh invoices.read (tidak dimiliki student) -> pakai /student/finance/*.
- **Sumber:** docs/keuangan/06-rancangan-tagihan-mahasiswa.md; docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.15.14; AUDIT/D30-manual-keu-video-pmb.md; AUDIT/C4-be-finance.md#GET /invoices

#### MOB-FIN-03 — Detail tagihan: rincian komponen, termin, alokasi pembayaran, denda, transaksi tertunda

- **Peran:** student
- **Deskripsi:** Detail satu tagihan: item biaya/potongan/beasiswa/voucher/denda, jadwal termin, alokasi pembayaran, tagihan denda turunan, dan transaksi pending/menunggu verifikasi, serta tombol aksi (Bayar, Ajukan cicilan, Pakai voucher/deposit).
- **Aturan bisnis:**
  - Semua endpoint: auth:sanctum + tenant.access (header X-University-ID) + permission student_finance.read (GET) / student_finance.create (POST); hanya role student.
  - Nominal berupa string desimal 2 digit (mis. "1500000.00"); jangan di-parse sebagai float untuk perbandingan; format Rp/id-ID di klien.
  - Tagihan milik mahasiswa lain/draf -> 404
  - Item line_type: charge, aid, discount, subsidy, voucher, waiver, penalty, adjustment
  - adjustments disembunyikan dari mahasiswa (selalu [])
- **Kriteria penerimaan/pengujian:**
  - Given tagihan punya 3 item When detail dibuka Then 3 baris dengan label line_type_label tampil dan total = amount
  - Given ada pending_transactions When detail dibuka Then instruksi bayar yang masih aktif tampil dengan link ke instruksi/unggah bukti
  - Given id milik orang lain When dipanggil Then 404 ditangani 'Tagihan tidak ditemukan'
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/invoices/{invoice}`
- **Celah backend:** Tidak ada riwayat/timeline perubahan tagihan khusus mahasiswa; rate_snapshot dan notes selalu null
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (halaman /portal/keuangan/tagihan/:id)
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-02
- **Sumber:** docs/keuangan/06-rancangan-tagihan-mahasiswa.md (INV detail); AUDIT/C4-be-finance.md#GET /invoices/{invoice}; keu/pages/portal/PortalFinancePages.tsx

#### MOB-FIN-04 — Unduh PDF tagihan dengan tautan pembayaran

- **Peran:** student
- **Deskripsi:** Mahasiswa mengunduh/membagikan tagihan resmi (PDF) berisi tautan/instruksi pembayaran (FR-TAG-07).
- **Aturan bisnis:**
  - Hanya tagihan sendiri; PDF diambil dengan header Authorization (bukan URL polos)
- **Kriteria penerimaan/pengujian:**
  - Given tagihan non-draf When 'Unduh PDF' Then berkas PDF terbuka/dibagikan
  - Given tagihan orang lain Then 404
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Tidak ada endpoint PDF tagihan mahasiswa (hanya kuitansi). Kontrak yang dibutuhkan: GET /api/v1/student/finance/invoices/{invoice}/pdf (mengikuti pola receipt)
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: endpoint PDF tagihan
- **Catatan:** Ditandai 'Belum tersedia' bila ditampilkan.
- **Sumber:** docs/keuangan/06-rancangan-tagihan-mahasiswa.md; docs/keuangan/README.md (FR-TAG-07)

#### MOB-FIN-05 — Riwayat/timeline perubahan tagihan dan koreksi

- **Peran:** student
- **Deskripsi:** Timeline status/alokasi/koreksi per tagihan (rancangan doc 06).
- **Aturan bisnis:**
  - Endpoint riwayat khusus mahasiswa belum dispesifikasikan (doc 06: 'student-specific history endpoint not specified')
- **Kriteria penerimaan/pengujian:**
  - Given tagihan dikoreksi When detail dibuka Then riwayat koreksi terlihat (belum dapat diuji sampai backend ada)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Tidak ada endpoint; sebagian informasi turunan dapat diambil dari alokasi pada detail dan GET /student/finance/statement
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-FIN-03; MOB-FIN-14
- **Catatan:** Cukup lewat kartu piutang untuk saat ini.
- **Sumber:** docs/keuangan/06-rancangan-tagihan-mahasiswa.md

#### MOB-FIN-06 — Ajukan sengketa/keberatan tagihan

- **Peran:** student
- **Deskripsi:** Mahasiswa mengajukan sengketa atas tagihan (rancangan 'Design only').
- **Aturan bisnis:**
  - Belum ada kebijakan/kontrak endpoint
- **Kriteria penerimaan/pengujian:**
  - Given tagihan When 'Ajukan sengketa' Then pengajuan tercatat (tidak dapat diuji)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Tidak ada endpoint POST sengketa mahasiswa
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Backend baru
- **Sumber:** docs/keuangan/06-rancangan-tagihan-mahasiswa.md; docs/keuangan/07..09 (sengketa)

#### MOB-FIN-07 — Bayar tagihan/termin secara online (VA, QRIS, e-wallet, gerai) via Xendit hosted checkout

- **Peran:** student
- **Deskripsi:** Pilih kanal online (GET /channels), buat transaksi (POST payments/online dengan header Idempotency-Key), lalu buka redirect_url (halaman checkout Xendit) di browser dalam aplikasi/eksternal; setelah kembali, tekan Cek status. Nominal selalu penuh (sisa tagihan atau sisa termin).
- **Aturan bisnis:**
  - Semua endpoint: auth:sanctum + tenant.access (header X-University-ID) + permission student_finance.read (GET) / student_finance.create (POST); hanya role student.
  - Header Idempotency-Key wajib (<=100 karakter, gunakan UUID per niat bayar); tanpa itu 409 IDEMPOTENCY_REQUIRED; key sama dengan target/kanal berbeda -> 409 IDEMPOTENCY_MISMATCH
  - target_type invoice|installment; nominal tidak dapat dipilih (penuh); fee kanal ditambahkan bila fee_bearer=student
  - Kanal online hanya muncul bila XENDIT_SECRET_KEY terisi; jika tidak: channels.online=[] dan 409 CHANNEL_NOT_CONNECTED
  - Instruksi pending lama untuk target yang sama otomatis dibatalkan
  - Throttle 20/menit; kegagalan Xendit -> 409 GATEWAY_UNAVAILABLE
  - Kebenaran status ditentukan server (webhook + recheck); klien tidak pernah menandai lunas sendiri
  - Kode kanal: va (BCA,BNI,BRI,MANDIRI,PERMATA,BSI,CIMB,BJB), ewallet (OVO,DANA,SHOPEEPAY,LINKAJA), retail (ALFAMART,INDOMARET), qris; transaksi kedaluwarsa menurut channel.expiry_minutes
- **Kriteria penerimaan/pengujian:**
  - Given kanal online tersedia When memilih kanal dan 'Bayar' Then 201 Transaction pending dan redirect_url dibuka
  - Given request diulang dengan Idempotency-Key sama When jaringan putus lalu coba lagi Then transaksi yang sama dikembalikan, tidak ganda
  - Given channels.online kosong When layar bayar dibuka Then opsi online disembunyikan/dilabeli 'Belum aktif', transfer manual tetap tersedia
  - Given pembayaran selesai di checkout When kembali ke aplikasi dan 'Cek status' Then status berubah success dan kuitansi tersedia
  - KEU-AC (pembayaran online) sesuai doc 08
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/student/finance/channels`; `POST /api/v1/student/finance/payments/online (Idempotency-Key)`; `POST /api/v1/student/finance/transactions/{transaction}/refresh`
- **Celah backend:** Kode backend lengkap tetapi belum teruji dengan akun Xendit nyata (STATUS §8 'Belum'); tanpa XENDIT_SECRET_KEY kanal online kosong; Hanya pembayaran penuh (tidak ada nominal parsial online); qr_string selalu null (QRIS lewat halaman checkout), va_number hanya jika Xendit mengembalikannya; Tidak ada push saat sukses (hanya notifikasi database keu.payment_succeeded)
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (tombol Bayar -> Xendit redirect, Cek status)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-02/03; Kredensial Xendit di server (XENDIT_SECRET_KEY, XENDIT_WEBHOOK_TOKEN); Flutter: url_launcher / flutter_inappwebview atau custom tabs; app link/deep link untuk kembali; Pola idempotensi (uuid)
- **Catatan:** UI sebaiknya bekerja tanpa kanal online (state kosong yang jelas).
- **Sumber:** docs/keuangan/08-* (pembayaran online); docs/keuangan/README.md F-09; AUDIT/C4-be-finance.md#POST /payments/online; AUDIT/D09-samples-notes.md

#### MOB-FIN-08 — Cek/segarkan status transaksi online

- **Peran:** student
- **Deskripsi:** Setelah kembali dari checkout, mahasiswa menekan 'Cek status' agar server menanyakan status ke Xendit.
- **Aturan bisnis:**
  - Hanya berefek pada transaksi status pending; throttle 30/menit; klien tidak mempercayai status lokal
  - Status: created, pending, awaiting_verification, success, failed, expired, cancelled, reversed, refunded
- **Kriteria penerimaan/pengujian:**
  - Given transaksi pending dan sudah dibayar When refresh Then status success
  - Given transaksi bukan pending When refresh Then data dikembalikan tanpa perubahan
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/student/finance/transactions/{transaction}/refresh`
- **Celah backend:** Tidak ada GET detail transaksi tunggal (hanya list/refresh); Tidak ada push saat status berubah
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (aksi 'Cek status' di tab Riwayat)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-07
- **Sumber:** AUDIT/C4-be-finance.md#POST /transactions/{transaction}/refresh; docs/keuangan/08-*

#### MOB-FIN-09 — Batalkan instruksi/transaksi yang belum dibayar

- **Peran:** student
- **Deskripsi:** Membatalkan transaksi online/manual berstatus created/pending (online juga mengakhiri invoice Xendit).
- **Aturan bisnis:**
  - Hanya created|pending; selain itu 409 'Hanya transaksi yang belum dibayar yang dapat dibatalkan.'
  - Transaksi awaiting_verification tidak dapat dibatalkan mahasiswa
- **Kriteria penerimaan/pengujian:**
  - Given transaksi pending When Batalkan dan konfirmasi Then status cancelled
  - Given transaksi success When Batalkan Then tombol tidak tersedia / 409 ditampilkan
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/student/finance/transactions/{transaction}/cancel`
- **Celah backend:** Tidak ada pembatalan pengajuan manual setelah bukti dikirim (awaiting_verification)
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (aksi Batalkan)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-12
- **Sumber:** AUDIT/C4-be-finance.md#POST /transactions/{transaction}/cancel; docs/keuangan/08-*

#### MOB-FIN-10 — Bayar via transfer bank manual dengan kode unik (instruksi pembayaran)

- **Peran:** student
- **Deskripsi:** Membuat instruksi transfer manual untuk tagihan/termin/deposit: nominal (termasuk kode unik), rekening kampus, batas waktu; dapat disalin ke clipboard.
- **Aturan bisnis:**
  - Semua endpoint: auth:sanctum + tenant.access (header X-University-ID) + permission student_finance.read (GET) / student_finance.create (POST); hanya role student.
  - target_type invoice|installment|deposit; target_id wajib kecuali deposit; amount wajib untuk deposit, opsional lainnya (default = sisa; nominal positif apa pun diizinkan = pembayaran sebagian)
  - Kode unik 1..999 bila setting keu.manual_transfer.unique_code aktif (kode docs PMB 3 digit); nominal = dasar + kode
  - Kedaluwarsa +3 hari; instruksi pending sebelumnya untuk target sama otomatis dibatalkan
  - bank_accounts = JSON bebas dari setting keu.bank_accounts (default []; bentuk tidak divalidasi, web mengasumsikan {bank,account_number,account_name})
  - Throttle 20/menit
- **Kriteria penerimaan/pengujian:**
  - Given tagihan terbuka When 'Transfer manual' Then 201 transaksi pending dengan nominal+kode unik dan daftar rekening
  - Given bank_accounts kosong When instruksi tampil Then pesan 'Hubungi Bagian Keuangan' (tidak crash)
  - Given nominal diubah sebagian When dibuat Then amount = input + kode unik
  - Instruksi menampilkan nominal persis dan tombol salin
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/channels`; `POST /api/v1/student/finance/payments/manual`
- **Celah backend:** Struktur bank_accounts tidak terdefinisi/tervalidasi (parsing klien harus defensif)
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (dialog Transfer manual)
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-03; MOB-FIN-11
- **Sumber:** docs/keuangan/README.md F-10; AUDIT/D31-manual-pmb-applicant.md (pola); AUDIT/C4-be-finance.md#POST /payments/manual

#### MOB-FIN-11 — Unggah bukti transfer dan lacak verifikasi (termasuk unggah ulang setelah ditolak)

- **Peran:** student
- **Deskripsi:** Pilih foto/berkas bukti, unggah ke /file-uploads, lalu kirim data bukti (bank pengirim, nama pengirim, tanggal, nominal). Status menjadi awaiting_verification; bila ditolak (failed) mahasiswa melihat alasan dan unggah ulang.
- **Aturan bisnis:**
  - Alur dua langkah: POST /api/v1/file-uploads (multipart field file) lalu POST proof JSON (BUKAN multipart)
  - File jpg/jpeg/png/pdf, maksimum 2 MB (batas FileAttacher, lebih ketat dari 10 MB umum), harus Clean (dipindai) dan milik user yang sama
  - sender_bank<=60, sender_name<=150, transfer_date<=hari ini, transferred_amount>0
  - Hanya transaksi manual_transfer berstatus pending atau failed; selain itu 409
  - Alasan penolakan reason_code: NOT_FOUND_IN_STATEMENT, AMOUNT_MISMATCH, UNREADABLE, DUPLICATE_PROOF, WRONG_ACCOUNT, OTHER; duplicate_warning bila checksum sama
  - Pengguna diberi petunjuk nominal tepat termasuk kode unik
- **Kriteria penerimaan/pengujian:**
  - Given transaksi manual pending When unggah JPG 1,5 MB dan isi 4 field Then status awaiting_verification
  - Given berkas 3 MB When dipilih Then ditolak di klien 'Ukuran berkas maksimal 2 MB'
  - Given bukti ditolak AMOUNT_MISMATCH When membuka transaksi Then alasan terlihat dan 'Unggah ulang bukti' aktif
  - Given tanggal transfer di masa depan Then validasi 422 ditampilkan
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/file-uploads (multipart)`; `POST /api/v1/student/finance/transactions/{transaction}/proof`
- **Celah backend:** Mahasiswa tidak dapat membatalkan/mengganti bukti yang sedang diverifikasi (docs: 'Replace proof / cancel manual submission' hanya sebagian)
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (form Unggah bukti)
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-10; Flutter: image_picker / file_picker, kompresi gambar <=2 MB; Endpoint /file-uploads (FileManagement)
- **Catatan:** Alur verifikasi oleh staf KEU di luar cakupan app.
- **Sumber:** docs/keuangan/README.md F-10 / FR-PRT-03; AUDIT/C4-be-finance.md#POST /transactions/{transaction}/proof; AUDIT/C5-be-other-modules.md (file-uploads)

#### MOB-FIN-12 — Riwayat transaksi pembayaran

- **Peran:** student
- **Deskripsi:** Daftar transaksi terbaru (nomor transaksi, metode, nominal, status, kanal, bukti) dengan aksi sesuai status (kuitansi, unggah bukti, cek status, batalkan).
- **Aturan bisnis:**
  - Semua endpoint: auth:sanctum + tenant.access (header X-University-ID) + permission student_finance.read (GET) / student_finance.create (POST); hanya role student.
  - Paginasi per_page 25 default; urutan terbaru; metode: online, manual_transfer, cash (Tunai Loket), deposit
  - Daftar hanya memuat channel + proof; allocations null
- **Kriteria penerimaan/pengujian:**
  - Given 40 transaksi When scroll Then paginasi berjalan
  - Given transaksi success dengan receipt_number When item tampil Then aksi Kuitansi tersedia
  - Given transaksi failed manual Then 'Unggah ulang bukti'
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/transactions?per_page=&page=`
- **Celah backend:** Tidak ada filter status; Tidak ada GET /transactions/{id} (detail tunggal) untuk mahasiswa
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (tab Riwayat pembayaran)
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-08; MOB-FIN-09; MOB-FIN-11; MOB-FIN-13
- **Catatan:** Pembayaran tunai di loket hanya tampil sebagai riwayat/kuitansi; mahasiswa tidak memulai pembayaran tunai dari app.
- **Sumber:** docs/keuangan/README.md FR-PRT-04; AUDIT/C4-be-finance.md#GET /transactions

#### MOB-FIN-13 — Lihat/unduh kuitansi pembayaran (PDF)

- **Peran:** student
- **Deskripsi:** Mengunduh kuitansi A5 landscape untuk pembayaran berhasil/dikembalikan dan membagikannya.
- **Aturan bisnis:**
  - 409 'Kuitansi hanya tersedia untuk pembayaran yang berhasil.' kecuali status success|refunded dan receipt_number ada
  - Perlu header Bearer: unduh via HTTP klien lalu simpan/tampilkan (tidak bisa URL polos)
  - Nama berkas <receipt_number>.pdf
- **Kriteria penerimaan/pengujian:**
  - Given transaksi success When Kuitansi Then PDF terbuka di viewer dan dapat dibagikan
  - Given transaksi pending Then tombol tidak tampil atau 409 ramah
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/transactions/{transaction}/receipt`
- **Celah backend:** -
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (aksi Kuitansi)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-12; Flutter: dio/http + path_provider + open_filex/share_plus (viewer PDF)
- **Sumber:** docs/keuangan/README.md FR-PON-07; AUDIT/C4-be-finance.md#GET /transactions/{transaction}/receipt

#### MOB-FIN-14 — Kartu piutang / rekening koran mahasiswa (statement)

- **Peran:** student
- **Deskripsi:** Buku besar kronologis tagihan dan pembayaran dengan saldo berjalan.
- **Aturan bisnis:**
  - Entri bertanda nominal (positif/negatif), tipe: Tagihan, label line_type, Pembayaran, Pembayaran (deposit), Kelebihan ke deposit, Pembalikan pembayaran, Tagihan dibatalkan
  - Tidak dipaginasi (muat seluruhnya)
- **Kriteria penerimaan/pengujian:**
  - Given riwayat 100 entri When tab dibuka Then semua entri urut tanggal dan saldo akhir = balance
  - Given pembayaran dibalik When tampil Then entri 'Pembalikan pembayaran' terlihat
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/statement`
- **Celah backend:** Tanpa paginasi/filter periode (bisa berat untuk akun lama); Tidak ada ekspor PDF rekening koran mahasiswa
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (tab Kartu piutang)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-01
- **Sumber:** docs/keuangan/README.md FR-RFD-01, FR-PRT-04; docs/keuangan/ (RPT-01 portal); AUDIT/C4-be-finance.md#GET /statement

#### MOB-FIN-15 — Bayar tagihan/termin dengan saldo deposit

- **Peran:** student
- **Deskripsi:** Menggunakan saldo deposit tersedia untuk melunasi (sebagian/penuh) tagihan atau termin tertentu; langsung berhasil.
- **Aturan bisnis:**
  - Semua endpoint: auth:sanctum + tenant.access (header X-University-ID) + permission student_finance.read (GET) / student_finance.create (POST); hanya role student.
  - amount opsional; default = min(saldo tersedia, sisa tagihan) dan dibatasi sisa; installment_id harus milik tagihan (else 404)
  - 409 INSUFFICIENT_DEPOSIT 'Saldo deposit kosong atau tagihan sudah lunas.'
  - Saldo tersedia = balance - held_amount (refund tertahan)
- **Kriteria penerimaan/pengujian:**
  - Given saldo 500rb dan sisa 2jt When pakai deposit Then terbayar 500rb dan transaksi method=deposit status success
  - Given saldo 0 Then aksi dinonaktifkan/409 ditampilkan
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/student/finance/invoices/{invoice}/deposit`
- **Celah backend:** Flag desain 'self-use deposit controlled by flag' tidak ditemukan di kode; selalu aktif
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (dialog Pakai deposit)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-03; MOB-FIN-16
- **Sumber:** docs/keuangan/README.md FR-DEP-02, FR-DEP-03; AUDIT/C4-be-finance.md#POST /invoices/{invoice}/deposit

#### MOB-FIN-16 — Saldo deposit, mutasi, dan status pengembalian dana

- **Peran:** student
- **Deskripsi:** Menampilkan saldo, dana tertahan, saldo tersedia, 200 mutasi terakhir, dan daftar pengajuan refund beserta statusnya.
- **Aturan bisnis:**
  - Nominal berupa string desimal 2 digit (mis. "1500000.00"); jangan di-parse sebagai float untuk perbandingan; format Rp/id-ID di klien.
  - Jenis mutasi: overpayment, top_up, adjustment_credit, allocation, refund_hold, refund_release, refund_payout, reversal
  - Nomor rekening penerima refund dimask oleh server (AuditMask)
  - Status refund: pending_approval, approved, rejected, completed, failed, cancelled
- **Kriteria penerimaan/pengujian:**
  - Given kelebihan bayar When tab Deposit dibuka Then mutasi 'Kelebihan Bayar' dan saldo bertambah
  - Given refund pending When tampil Then saldo tertahan (held_amount) tercermin dan rekening termask
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/deposit`
- **Celah backend:** Mutasi dibatasi 200 entri terbaru tanpa paginasi
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (tab Deposit & refund)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-01
- **Sumber:** docs/keuangan/README.md FR-DEP-01, FR-RFD-05; AUDIT/C4-be-finance.md#GET /deposit

#### MOB-FIN-17 — Top-up/titip deposit

- **Peran:** student
- **Deskripsi:** Mengisi saldo deposit: via transfer manual (kode unik + bukti) tersedia; via online belum.
- **Aturan bisnis:**
  - POST payments/manual target_type=deposit dengan amount>0 wajib
  - Top-up online (VA/QRIS) tidak didukung oleh POST payments/online (hanya invoice|installment)
- **Kriteria penerimaan/pengujian:**
  - Given nominal 1jt When 'Top-up' Then instruksi manual dibuat dan setelah bukti disetujui saldo bertambah (entri top_up)
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/student/finance/payments/manual (target_type=deposit)`; `POST /api/v1/student/finance/transactions/{transaction}/proof`
- **Celah backend:** Top-up online tidak ada
- **Status web:** Tidak ada UI top-up eksplisit di PortalFinancePages (API tersedia)
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-10; MOB-FIN-11; MOB-FIN-16
- **Sumber:** docs/keuangan/README.md FR-DEP-04 (P2); AUDIT/C4-be-finance.md#POST /payments/manual

#### MOB-FIN-18 — Ajukan pengembalian dana dari deposit (refund)

- **Peran:** student
- **Deskripsi:** Form pengajuan refund: nominal, alasan (kode + catatan), rekening penerima, hubungan penerima.
- **Aturan bisnis:**
  - Semua endpoint: auth:sanctum + tenant.access (header X-University-ID) + permission student_finance.read (GET) / student_finance.create (POST); hanya role student.
  - Hanya dari saldo deposit; saldo ditahan sampai diputuskan
  - reason_code: OVERPAYMENT, DUPLICATE_PAYMENT, CANCELLED_INVOICE, WITHDRAWAL, LEAVE, DECEASED, GRADUATED_BALANCE, OTHER (web hanya menawarkan tanpa DECEASED)
  - reason_note 10..1000; beneficiary_bank<=60; beneficiary_account ^[0-9]{6,20}$; beneficiary_name<=150; beneficiary_relation self|parent
  - Mahasiswa tidak dapat membatalkan refund sendiri
- **Kriteria penerimaan/pengujian:**
  - Given saldo tersedia>0 When submit lengkap Then 201 status pending_approval dan held_amount naik
  - Given rekening 'abc' Then validasi 422
  - Given saldo tersedia 0 Then tombol tidak tampil
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/student/finance/refunds`; `GET /api/v1/student/finance/deposit`
- **Celah backend:** Tidak ada pembatalan refund oleh mahasiswa; Refund via API penyedia (FR-RFD-06, P3) belum ada; eksekusi oleh staf
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (tab Deposit & refund)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-16
- **Sumber:** docs/keuangan/README.md FR-RFD-05; AUDIT/C4-be-finance.md#POST /refunds

#### MOB-FIN-19 — Gunakan kode voucher pada tagihan belum lunas

- **Peran:** student
- **Deskripsi:** Input kode voucher dan hasilkan potongan (discount_amount).
- **Aturan bisnis:**
  - Kode 4..30 karakter (di-uppercase server)
  - Throttle 10/menit; 5 kode salah -> VOUCHER_THROTTLED 30 menit (keu.voucher.max_failed_attempts)
  - 409: VOUCHER_INVALID, VOUCHER_NOT_APPLICABLE, VOUCHER_THROTTLED
- **Kriteria penerimaan/pengujian:**
  - Given kode valid When diterapkan Then potongan muncul dan sisa tagihan berkurang
  - Given 5 kali salah Then pesan tunggu 30 menit dan input dinonaktifkan
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/student/finance/invoices/{invoice}/voucher`
- **Celah backend:** Tidak ada pratinjau potongan sebelum menerapkan; Tidak ada 'lepas voucher' oleh mahasiswa (docs D19 merancang)
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (dialog Voucher di detail tagihan)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-03
- **Sumber:** docs/keuangan/README.md F-06 (P2); docs/keuangan/ (voucher); AUDIT/C4-be-finance.md#POST /invoices/{invoice}/voucher

#### MOB-FIN-20 — Ajukan cicilan (skema standar atau khusus)

- **Peran:** student
- **Deskripsi:** Memilih skema cicilan aktif (GET installment-plans) atau mengajukan termin khusus beserta alasan; skema auto_approve langsung disetujui.
- **Aturan bisnis:**
  - Semua endpoint: auth:sanctum + tenant.access (header X-University-ID) + permission student_finance.read (GET) / student_finance.create (POST); hanya role student.
  - Pilih installment_plan_id ATAU terms (2..12 termin; percent>0<=100; due_date atau due_offset_days 0..365)
  - Termin khusus wajib reason (<=1000) -> 409 'Pengajuan cicilan khusus wajib disertai alasan.'
  - kind new|reschedule (default new)
  - Hasil: approved (auto_approve) 'Cicilan disetujui; jadwal termin tersedia.' atau submitted
  - Ditolak 409 bila: ada pengajuan menunggu, sudah ada jadwal aktif (untuk new), tunggakan melanggar BR-KEU-30, skema tidak aktif/tidak berlaku untuk bill_group
  - Jumlah persen termin harus 100 (aturan skema PMB 'sum 100')
- **Kriteria penerimaan/pengujian:**
  - Given tagihan layak When memilih skema 3 termin Then 201 {id,status}
  - Given pengajuan masih submitted When mengajukan lagi Then pesan 409 ditampilkan
  - Given termin khusus tanpa alasan Then validasi sebelum kirim
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/invoices/{invoice}/installment-plans`; `POST /api/v1/student/finance/invoices/{invoice}/installments`
- **Celah backend:** Web hanya mengirim installment_plan_id (+reason); termin khusus & reschedule didukung API tapi belum dipakai UI web
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (dialog Cicilan; plan saja)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-03; MOB-FIN-21
- **Sumber:** docs/keuangan/README.md FR-CCL-01/02; AUDIT/C4-be-finance.md#POST /invoices/{invoice}/installments; AUDIT/D31-manual-pmb-applicant.md

#### MOB-FIN-21 — Jadwal dan status termin cicilan

- **Peran:** student
- **Deskripsi:** Menampilkan jadwal termin pada detail tagihan: urutan, nominal, terbayar, sisa, jatuh tempo, status (Terjadwal/Dibayar Sebagian/Lunas/Diganti Jadwal Baru/Terlambat) dan membayar per termin.
- **Aturan bisnis:**
  - Label status menjadi 'Terlambat' bila lewat jatuh tempo
  - Pembayaran per termin lewat target_type=installment
  - schedule_version dan status superseded untuk jadwal lama
- **Kriteria penerimaan/pengujian:**
  - Given cicilan aktif When detail dibuka Then termin tampil dengan status benar
  - Given termin 1 lunas When tampil Then status Lunas dan termin 2 menjadi next_due
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/invoices/{invoice}`
- **Celah backend:** -
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (detail tagihan)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-03; MOB-FIN-20
- **Sumber:** docs/keuangan/README.md FR-CCL-03/04/07; AUDIT/C4-be-finance.md#Installment

#### MOB-FIN-22 — Daftar pengajuan cicilan saya dan pembatalannya

- **Peran:** student
- **Deskripsi:** Melihat status pengajuan cicilan (submitted/approved/rejected/cancelled) dan membatalkan.
- **Aturan bisnis:**
  - InstallmentRequestStatus: submitted, approved, rejected, cancelled
- **Kriteria penerimaan/pengujian:**
  - Given pengajuan submitted When membuka daftar Then status terlihat (belum dapat diuji)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Tidak ada GET daftar pengajuan cicilan mahasiswa dan tidak ada pembatalan. Kontrak dibutuhkan: GET /student/finance/installment-requests, POST .../{id}/cancel. Sementara itu hanya notifikasi keu.installment_decided dan jadwal pada detail tagihan
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend baru
- **Sumber:** AUDIT/C4-be-finance.md (gaps); docs/keuangan/README.md

#### MOB-FIN-23 — Pembayaran sebagian (partial) dan kelebihan bayar menjadi deposit

- **Peran:** student
- **Deskripsi:** Mahasiswa dapat membayar sebagian lewat transfer manual (nominal bebas >0); kelebihan bayar masuk deposit otomatis dan alokasi ke termin.
- **Aturan bisnis:**
  - Manual: nominal positif apa pun; Online: selalu penuh
  - Kelebihan bayar -> deposit (entri overpayment); alokasi ke termin oleh PaymentPostingService
- **Kriteria penerimaan/pengujian:**
  - Given tagihan 2jt When transfer manual 1jt dan disetujui Then status tagihan Dibayar Sebagian (partial)
  - Given transfer lebih besar dari sisa Then selisih menjadi deposit
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/student/finance/payments/manual`
- **Celah backend:** Nominal parsial online tidak tersedia (selalu penuh)
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (alur manual)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-10
- **Catatan:** Digabung ke UI MOB-FIN-10 (input nominal opsional).
- **Sumber:** docs/keuangan/README.md (pembayaran sebagian); AUDIT/D30-manual-keu-video-pmb.md; AUDIT/C4-be-finance.md#POST /payments/manual

#### MOB-FIN-24 — Lihat beasiswa, bantuan, dan potongan aktif/dicabut

- **Peran:** student
- **Deskripsi:** Daftar award (beasiswa, potongan, subsidi, KIP Kuliah) dengan jenis, perhitungan, periode, status, dan alasan pencabutan.
- **Aturan bisnis:**
  - AidKind: scholarship, discount, subsidy, kip; status: active, revoked (pending_approval/rejected tidak ditampilkan ke mahasiswa)
  - Efek pada tagihan terlihat sebagai item aid/discount/subsidy di detail tagihan
- **Kriteria penerimaan/pengujian:**
  - Given 2 award aktif When tab dibuka Then 2 kartu tampil
  - Given award dicabut Then badge 'Dicabut' dan alasan terlihat
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/awards`
- **Celah backend:** Tidak ada estimasi efek award per tagihan selain item pada detail
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (tab Beasiswa/potongan)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-03
- **Sumber:** docs/keuangan/README.md F-05; AUDIT/C4-be-finance.md#GET /awards

#### MOB-FIN-25 — Telusuri beasiswa terbuka dan ajukan beasiswa

- **Peran:** student
- **Deskripsi:** Daftar program beasiswa terbuka dan pengajuan/pelacakan aplikasi oleh mahasiswa.
- **Aturan bisnis:**
  - Modul Scholarship saat ini read-only dan admin-only (scholarships.read); tidak ada endpoint submit mahasiswa
- **Kriteria penerimaan/pengujian:**
  - Given program dibuka When mahasiswa melihat Then daftar tampil (tidak dapat diuji)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Perlu GET /student/scholarships (terbuka), POST apply, GET aplikasi sendiri, dengan permission student; saat ini 403 untuk student
- **Status web:** mock (PortalScholarshipPage.tsx, data contoh, tidak dirutekan)
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Backend baru (modul Scholarship)
- **Sumber:** docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md (D01); AUDIT/C5-be-other-modules.md#Scholarship; AUDIT/D06-exam-docs-videos.md

#### MOB-FIN-26 — Transparansi denda keterlambatan (tagihan denda otomatis)

- **Peran:** student
- **Deskripsi:** Tagihan denda tampil sebagai tagihan terpisah (bill_group penalty) dan terlampir pada penalty_invoices di detail tagihan induk; ringkasan menampilkan clearance.accruing_penalty.
- **Aturan bisnis:**
  - Denda diterbitkan otomatis sebagai tagihan Denda (notifikasi keu.penalty_issued); kebijakan denda off secara default menurut docs (KEU-Q-06) – verifikasi per tenant
  - Rumus/aturan denda tidak diekspos sebagai teks ke mahasiswa
- **Kriteria penerimaan/pengujian:**
  - Given tagihan terlambat When detail dibuka Then penalty_invoices tampil dengan nominal dan statusnya
  - Given ada denda berjalan When ringkasan tampil Then accruing_penalty terlihat
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/student/finance/invoices/{invoice}`; `GET /api/v1/student/finance/summary`
- **Celah backend:** Tidak ada endpoint penjelasan aturan/rumus denda dan estimasi berjalan per tagihan
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (denda pada detail/ringkasan)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-03
- **Sumber:** docs/keuangan/README.md F-08; AUDIT/C4-be-finance.md#GET /invoices/{invoice}

#### MOB-FIN-27 — Lihat kelompok UKT dan riwayat pengajuan peninjauan UKT

- **Peran:** student
- **Deskripsi:** Kelompok UKT aktif (dasar penetapan, nomor SK, berlaku sejak), daftar kelompok, dan pengajuan peninjauan beserta statusnya.
- **Aturan bisnis:**
  - UKT opsional untuk kampus SPP tetap (KEU-Q-13)
  - Status pengajuan: submitted, approved, rejected, cancelled
- **Kriteria penerimaan/pengujian:**
  - Given UKT ditetapkan When tab UKT dibuka Then kelompok dan SK tampil
  - Given tenant tanpa UKT When dibuka Then state kosong
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/ukt`
- **Celah backend:** UKT STATUS: instrumen skoring/pengajuan data ekonomi/SK belum lengkap (STATUS §8)
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (tab UKT)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** -
- **Sumber:** docs/keuangan/README.md F-04; AUDIT/C4-be-finance.md#GET /ukt

#### MOB-FIN-28 — Ajukan peninjauan/keberatan UKT dengan bukti

- **Peran:** student
- **Deskripsi:** Form alasan (min 20 karakter), kelompok diminta (opsional), dan unggah bukti.
- **Aturan bisnis:**
  - reason 20..2000; evidence_file_id wajib via /file-uploads (pdf/jpg/jpeg/png, <=5 MB)
  - Hanya satu pengajuan terbuka: 409 'Masih ada pengajuan perubahan UKT yang sedang diproses.'
- **Kriteria penerimaan/pengujian:**
  - Given alasan 25 karakter + bukti PDF 3 MB When submit Then 201 {id}
  - Given pengajuan terbuka Then 409 ditampilkan
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/file-uploads`; `POST /api/v1/student/finance/ukt-requests`
- **Celah backend:** Tidak ada pembatalan oleh mahasiswa
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (form peninjauan UKT)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-27; Flutter: file_picker
- **Sumber:** docs/keuangan/README.md F-04; AUDIT/C4-be-finance.md#POST /ukt-requests

#### MOB-FIN-29 — Pengajuan data ekonomi dan penetapan UKT (skoring)

- **Peran:** student
- **Deskripsi:** Mahasiswa mengisi data ekonomi untuk penentuan UKT dan memantau verifikasinya.
- **Aturan bisnis:**
  - Belum ada instrumen skoring/pengajuan data ekonomi (STATUS §8)
- **Kriteria penerimaan/pengujian:**
  - Tidak dapat diuji
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Perlu endpoint data ekonomi, skoring, SK
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Backend baru
- **Sumber:** docs/STATUS-IMPLEMENTASI.md §8; docs/keuangan/README.md F-04

#### MOB-FIN-30 — Status cekal keuangan per layanan, penyebab, dan cara penyelesaian

- **Peran:** student
- **Deskripsi:** Kartu cekal aktif/berdispensasi: layanan terbatas, alasan, kekurangan (shortfall), tanggal efektif, dispensasi; chip layanan KRS/Ujian diblokir/dapat diakses; error 409 ACADEMIC_BLOCKED di KRS/ujian diarahkan ke layar ini.
- **Aturan bisnis:**
  - Cekal dibuat otomatis oleh ClearanceService (rule finance_clearance_rules) hanya untuk mahasiswa berstatus Aktif; dicabut otomatis saat shortfall terpenuhi
  - Layanan yang DITEGAKKAN hanya krs dan exam (view_grades, transcript, yudisium, graduation belum ditegakkan walau dapat tercantum di aturan/UI web)
  - Dispensasi aktif (valid_from<=now<valid_until) mengangkat blok untuk layanan itu
  - Error 409: errors.code=ACADEMIC_BLOCKED dengan blocks[{block_id,block_type:'financial',source:'finance',reason,since}]; nominal disembunyikan di error tetapi ada di summary.blocks[].shortfall (BR-SIA-CKL-09)
  - Status blok: active, exempted, revoked
- **Kriteria penerimaan/pengujian:**
  - Given cekal aktif When Keuangan dibuka Then kartu cekal menampilkan alasan dan kekurangan
  - Given KRS dicoba saat cekal When 409 ACADEMIC_BLOCKED Then app menampilkan alasan dan tautan ke Keuangan
  - Given dispensasi aktif When tampil Then layanan berstatus dapat diakses sampai valid_until
  - Given cekal dicabut Then notifikasi keu.block_revoked dan kartu hilang
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/summary`
- **Celah backend:** Cekal untuk nilai/transkrip/yudisium/wisuda tidak ditegakkan (hanya krs & exam); Tidak ada endpoint 'cekal akademik' terpisah (SIAKAD Tahap 3 'Baru'); hanya cekal sumber finance yang dapat dilihat mahasiswa
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (kartu Cekal keuangan + chip layanan)
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-01; Penanganan error 409 ACADEMIC_BLOCKED di modul KRS/ujian
- **Catatan:** Dokumen SIAKAD (D10/D13/D14) menandai cekal 'Baru'; kode menunjukkan cekal keuangan sudah berfungsi untuk KRS & ujian.
- **Sumber:** docs/keuangan/README.md F-14 (FR-CKL-01..04); AUDIT/C4-be-finance.md#4 Cekal; AUDIT/D30-manual-keu-video-pmb.md

#### MOB-FIN-31 — Ajukan dispensasi cekal dan lacak keputusannya

- **Peran:** student
- **Deskripsi:** Form dispensasi: layanan, berlaku sampai (maks 30 hari), alasan, janji bayar (nominal/tanggal). Status eksemption: pending, active, rejected, revoked, expired.
- **Aturan bisnis:**
  - service salah satu: krs, exam, view_grades, transcript, yudisium, graduation (harus layanan yang dibatasi cekal itu)
  - valid_until > sekarang dan maksimal 30 hari (setting keu.dispensation.max_days) -> 409 'Dispensasi maksimal 30 hari.'
  - reason 20..1000; promise_amount>0; promise_date>=hari ini
  - {block} harus cekal milik sendiri bersumber finance (else 404); cekal sudah dicabut -> 409
  - Tidak pernah otomatis disetujui; staf butuh finance_clearance.approve dan bukan pemohon sendiri
  - Notifikasi keu.exemption_decided
- **Kriteria penerimaan/pengujian:**
  - Given cekal aktif When submit dispensasi valid Then 201 pending tampil di kartu cekal
  - Given valid_until 45 hari Then 409 pesan maksimal 30 hari
  - Given keputusan disetujui When notifikasi masuk Then layanan terbuka sampai valid_until
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/student/finance/blocks/{block}/dispensations`; `GET /api/v1/student/finance/summary (blocks[].exemptions)`
- **Celah backend:** Tidak ada GET daftar dispensasi sendiri dan tidak ada pembatalan oleh mahasiswa (hanya via summary.blocks[].exemptions); Janji bayar (FR-CKL-05) hanya field informasi
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (form Ajukan dispensasi)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-30
- **Sumber:** docs/keuangan/README.md FR-CKL-04; AUDIT/C4-be-finance.md#POST /blocks/{block}/dispensations

#### MOB-FIN-32 — Status bebas tanggungan keuangan (clearance)

- **Peran:** student
- **Deskripsi:** Menampilkan clearance: clear (Ya/Belum), outstanding, accruing_penalty, pending_verification.
- **Aturan bisnis:**
  - Syarat clearance dari ClearanceRequirement: fully_paid, due_items_paid, first_installment_paid
- **Kriteria penerimaan/pengujian:**
  - Given tidak ada tagihan terbuka When ringkasan tampil Then 'Bebas tanggungan: Ya'
  - Given ada sisa Then 'Belum' dengan nominal
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/summary`
- **Celah backend:** -
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (chip Bebas tanggungan)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-01
- **Sumber:** docs/keuangan/README.md FR-CKL-06; AUDIT/C4-be-finance.md#GET /summary

#### MOB-FIN-33 — Unduh surat keterangan bebas tanggungan keuangan (PDF)

- **Peran:** student
- **Deskripsi:** Surat bebas tanggungan resmi untuk yudisium/wisuda.
- **Aturan bisnis:**
  - FR-CKL clearance letter (MVP/P2 di docs) belum tersedia (D09: 'surat bebas tanggungan PDF not available')
- **Kriteria penerimaan/pengujian:**
  - Given clearance clear When Unduh surat Then PDF (tidak dapat diuji)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Perlu endpoint PDF surat bebas tanggungan (mis. GET /student/finance/clearance/letter)
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend baru
- **Sumber:** docs/keuangan/README.md (clearance letter); AUDIT/D09-samples-notes.md

#### MOB-FIN-34 — Notifikasi keuangan dalam aplikasi (tagihan terbit, pembayaran, bukti ditolak, cekal, dll.)

- **Peran:** student
- **Deskripsi:** Notifikasi database dengan template keu.* ditampilkan di pusat notifikasi dan membawa tautan ke layar Keuangan terkait; push belum ada.
- **Aturan bisnis:**
  - Template keu.*: invoice_issued, payment_succeeded, proof_rejected, payment_reversed, deposit_credited, installment_decided, penalty_issued, aid_decided, ukt_decided, refund_status, block_imposed, block_revoked, exemption_decided, due_reminder
  - link berisi rute web (/portal/keuangan) -> app perlu memetakan ke layar native
  - Tidak ada push/FCM: pembaruan hanya saat aplikasi membuka daftar (polling)
  - /notifications tidak butuh permission; PATCH /notifications/{id}/read, POST /notifications/read-all
- **Kriteria penerimaan/pengujian:**
  - Given invoice terbit When membuka Notifikasi Then item keu.invoice_issued tampil dan ketuk membuka Tagihan
  - Given item dibaca When PATCH read Then unread_count turun
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/notifications`; `GET /api/v1/notifications/unread-count`; `PATCH /api/v1/notifications/{id}/read`; `POST /api/v1/notifications/read-all`
- **Celah backend:** Tidak ada endpoint registrasi token FCM/push; Tautan notifikasi berupa URL web, bukan rute app
- **Status web:** PortalNotificationsPage (portal umum)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Infrastruktur push (FCM) + endpoint registrasi perangkat (backend); Pemetaan link->deep link
- **Catatan:** Bagian push dibawa ke grup lain (notifikasi); baris ini hanya konten keuangan.
- **Sumber:** AUDIT/C4-be-finance.md#0 Conventions; AUDIT/C5-be-other-modules.md#Notification

#### MOB-FIN-35 — Tagihan SKS otomatis setelah KRS disetujui dan penyesuaian tambah/drop

- **Peran:** student, advisor
- **Deskripsi:** Tagihan Biaya SKS terbit otomatis dari KRS; mahasiswa melihatnya di daftar tagihan (bill_group credit). Persetujuan dosen PA berdampak pada penerbitan tagihan.
- **Aturan bisnis:**
  - STATUS-IMPLEMENTASI menyatakan 'tagihan SKS otomatis dari KRS' ✅ (docs keuangan 15 Okt menyebut event KRS belum dibangun – kode menang)
  - Penyesuaian tambah/drop (supplementary) belum terverifikasi di kode
- **Kriteria penerimaan/pengujian:**
  - Given KRS disetujui When tagihan terbit Then muncul di MOB-FIN-02 dengan bill_group credit
  - Given tambah/drop setelah persetujuan Then tagihan susulan/penyesuaian terlihat (perlu verifikasi)
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/student/finance/invoices`
- **Celah backend:** Perilaku add/drop (penyesuaian tagihan) belum terverifikasi; tidak ada tampilan estimasi biaya SKS sebelum KRS disetujui
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (muncul di daftar tagihan)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-02; KRS (grup academic)
- **Catatan:** Peran advisor hanya sebagai pemicu persetujuan KRS; tidak ada UI keuangan untuk dosen.
- **Sumber:** docs/keuangan/README.md FR-TAG-02/03; docs/STATUS-IMPLEMENTASI.md §8

#### MOB-FIN-36 — Biaya cuti: tagihan cuti menggantikan tagihan registrasi

- **Peran:** student
- **Deskripsi:** Mahasiswa cuti menerima tagihan biaya cuti (bukan registrasi) (FR-TAG-08).
- **Aturan bisnis:**
  - Per docs 15 Okt: modul cuti ada tetapi integrasi KEU belum ada; tidak terverifikasi di kode
- **Kriteria penerimaan/pengujian:**
  - Tidak dapat diuji
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Integrasi cuti->KEU belum terverifikasi; tidak ada kontrak khusus mahasiswa
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Pengajuan cuti (grup student-requests)
- **Sumber:** docs/keuangan/README.md FR-TAG-08

#### MOB-FIN-37 — Dosen PA melihat status cekal keuangan mahasiswa bimbingan (tanpa nominal)

- **Peran:** advisor, lecturer
- **Deskripsi:** Dosen PA/dosen melihat penanda cekal keuangan advisee (tanpa jumlah) untuk membantu bimbingan KRS.
- **Aturan bisnis:**
  - Dosen/academic_advisor TIDAK punya permission finance maupun academic_blocks.read
  - Satu-satunya sinyal saat ini: 409 ACADEMIC_BLOCKED saat menyetujui KRS bila mahasiswa terblokir setelah ajuan
  - Nominal tidak boleh ditampilkan (FR-CKL-08)
- **Kriteria penerimaan/pengujian:**
  - Given advisee terkena cekal When dosen PA membuka daftar bimbingan Then ikon/penanda cekal tampil tanpa nominal (tidak dapat diuji)
  - Given approve KRS Then error 409 ACADEMIC_BLOCKED ditampilkan dengan pesan jelas (dapat diuji sekarang)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Perlu endpoint/field baru (mis. flag has_financial_block pada list advisees KRS atau GET /advising/students/{id}/blocks) + permission scoped advisor; hanya boolean tanpa nominal
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: permission + scoping advisee; MOB advising KRS (grup academic)
- **Catatan:** Penanganan 409 ACADEMIC_BLOCKED pada persetujuan KRS sudah bisa dibangun (build_integrated) di fitur KRS advisor.
- **Sumber:** docs/keuangan/README.md FR-CKL-08; AUDIT/C4-be-finance.md#4 Cekal; AUDIT/C3-be-academic-lecturer.md

#### MOB-FIN-38 — Dosen PA mengajukan dispensasi keuangan untuk mahasiswa bimbingan

- **Peran:** advisor
- **Deskripsi:** Dosen PA membantu mengajukan dispensasi cekal untuk advisee.
- **Aturan bisnis:**
  - Tidak ada permission/endpoint advisor; dispensasi hanya oleh mahasiswa
- **Kriteria penerimaan/pengujian:**
  - Tidak dapat diuji
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Perlu endpoint advisor dan kebijakan
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-FIN-37
- **Sumber:** docs/keuangan/README.md F-14

#### MOB-FIN-39 — Ringkasan eskalasi tunggakan dan risiko pembayaran advisee untuk dosen PA (informasi saja)

- **Peran:** advisor
- **Deskripsi:** Rekomendasi docs, analitik Fase 3.
- **Aturan bisnis:**
  - Hanya rekomendasi; bergantung analitik
- **Kriteria penerimaan/pengujian:**
  - Tidak dapat diuji
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Seluruh backend belum ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-FIN-37
- **Sumber:** docs/keuangan/README.md (rekomendasi)

#### MOB-FIN-40 — Klaim dana masuk tak teridentifikasi dengan bukti

- **Peran:** student
- **Deskripsi:** Mahasiswa mengklaim dana tak teridentifikasi (FR-REK-04).
- **Aturan bisnis:**
  - Cara pengajuan oleh mahasiswa tidak dispesifikasikan
- **Kriteria penerimaan/pengujian:**
  - Tidak dapat diuji
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Tidak ada endpoint mahasiswa
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Backend baru
- **Sumber:** docs/keuangan/README.md FR-REK-04

#### MOB-FIN-41 — Bayar beberapa tagihan dalam satu transaksi

- **Peran:** student
- **Deskripsi:** Pembayaran gabungan (FR-PON-06, P2).
- **Aturan bisnis:**
  - POST payments/online/manual hanya satu target per transaksi
- **Kriteria penerimaan/pengujian:**
  - Tidak dapat diuji
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Perlu dukungan multi-target pada payments/*
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Backend baru
- **Sumber:** docs/keuangan/README.md FR-PON-06

#### MOB-FIN-42 — Verifikasi publik kuitansi lewat QR (pemindai)

- **Peran:** student
- **Deskripsi:** Kuitansi PDF memuat QR verifikasi publik (FR-PON-07); pemindaian publik bukan fitur student.
- **Aturan bisnis:**
  - Endpoint verifikasi publik kuitansi tidak ditemukan di routes
- **Kriteria penerimaan/pengujian:**
  - Tidak dapat diuji
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Tidak ada endpoint public receipt verify
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Backend baru
- **Sumber:** docs/keuangan/README.md FR-PON-07

#### MOB-FIN-43 — Pembayaran tunai di loket (sisi mahasiswa: riwayat dan kuitansi)

- **Peran:** student
- **Deskripsi:** Pembayaran tunai dicatat kasir; mahasiswa hanya melihat transaksi method=cash dan kuitansinya.
- **Aturan bisnis:**
  - Tidak ada endpoint mahasiswa untuk memulai pembayaran tunai
- **Kriteria penerimaan/pengujian:**
  - Given kasir mencatat tunai When mahasiswa membuka Riwayat Then transaksi 'Tunai (Loket)' dan kuitansi terlihat
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/finance/transactions`; `GET /api/v1/student/finance/transactions/{transaction}/receipt`
- **Celah backend:** -
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (muncul di riwayat)
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-12; MOB-FIN-13
- **Catatan:** Tidak ada UI terpisah; dicakup MOB-FIN-12.
- **Sumber:** AUDIT/D30-manual-keu-video-pmb.md; docs/keuangan/README.md

### Domain Semester Pendek (SP)

Seluruh modul SP belum dibangun di backend (hanya flag academic.short_term dan BillGroup short_term di KEU). Semua baris SP berupa kontrak dari docs/semester-pendek; tampilkan hanya entri berlabel 'Belum tersedia' tanpa data palsu.

#### MOB-SP-01 — Daftar term semester pendek yang terlihat mahasiswa

- **Peran:** student
- **Deskripsi:** Menu 'Semester Pendek' hanya muncul bila ada term SP berstatus >= PLANNED; draft = 404.
- **Aturan bisnis:**
  - Modul Semester Pendek belum ada sama sekali (STATUS-IMPLEMENTASI §3/§11: hanya flag academic.short_term; tidak ada route SP di app/Modules/*/Routes/api.php).
  - Term DRAFT tak terlihat (404); PLANNED: daftar kelas terlihat tetapi registrasi nonaktif
  - Kontrak docs: GET /student/academic-terms?filter[semester]=pendek (belum ada; /academic-terms yang ada butuh permission non-student)
- **Kriteria penerimaan/pengujian:**
  - Given tidak ada term SP When app dibuka Then menu SP tersembunyi
  - Given term PLANNED When dibuka Then daftar kelas tampil tetapi tombol Daftar nonaktif
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Perlu GET /student/academic-terms (filter semester=pendek) + permission student
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: modul SP penuh
- **Catatan:** Tampilkan hanya entri berlabel 'Belum tersedia' tanpa data palsu.
- **Sumber:** docs/semester-pendek/aturan-bisnis.md#7.1; docs/semester-pendek/frontend-dan-mobile.md#3; docs/semester-pendek/peran-dan-izin.md#5.3; docs/STATUS-IMPLEMENTASI.md

#### MOB-SP-02 — Jelajahi penawaran kelas SP beserta kelayakan (eligibility)

- **Peran:** student
- **Deskripsi:** Kartu kelas: mata kuliah, SKS, kode kelas, dosen, jadwal, kapasitas/sisa kursi, biaya, baru/ulang, nilai sebelumnya, eligibility {eligible, reasons[{code,message}]}, dan aturan {max_credits,min_credits,fee_per_credit,taken_credits,allow_new_courses}. Kelas tak layak ditampilkan redup dengan alasan.
- **Aturan bisnis:**
  - Modul Semester Pendek belum ada sama sekali (STATUS-IMPLEMENTASI §3/§11: hanya flag academic.short_term; tidak ada route SP di app/Modules/*/Routes/api.php).
  - Server memvalidasi ulang saat submit
  - Biaya = SKS x academic.short_term.fee_per_credit
  - Kontrak: GET /student/academic-terms/{term}/offering (404 untuk draft)
  - Catatan: endpoint existing GET /student/krs/offerings adalah penawaran KRS reguler, bukan SP
- **Kriteria penerimaan/pengujian:**
  - KRS-18, KRS-19, KRS-21, KRS-22, NAV-08, ELG-01..20 (docs/semester-pendek/black-box-testing.md)
  - Given kelas penuh When tampil Then redup dengan alasan kapasitas
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** GET /student/academic-terms/{term}/offering belum ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-SP-01; Backend SP
- **Sumber:** docs/semester-pendek/desain-api.md#3.1; docs/semester-pendek/frontend-dan-mobile.md#3.1; docs/semester-pendek/aturan-bisnis.md#4.1

#### MOB-SP-03 — Pilih kelas SP (keranjang), cek bentrok/batas SKS, dan kirim KRS SP

- **Peran:** student
- **Deskripsi:** Alur: pilih kelas -> keranjang (total SKS vs batas, total biaya, cek bentrok) -> Daftar -> konfirmasi -> Kirim KRS; lalu persetujuan dan status KRS SP.
- **Aturan bisnis:**
  - Modul Semester Pendek belum ada sama sekali (STATUS-IMPLEMENTASI §3/§11: hanya flag academic.short_term; tidak ada route SP di app/Modules/*/Routes/api.php).
  - Kontrak docs: GET student/krs/terms, POST student/krs/items, POST student/krs/submit (versi SP; endpoint /student/krs/* reguler ada tetapi belum mengenal term SP)
- **Kriteria penerimaan/pengujian:**
  - KRS-18, KRS-19, KRS-21, KRS-22 (black-box-testing §8-9)
  - Given total SKS > max_credits When submit Then ditolak dengan pesan
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Dukungan term SP pada /student/krs/*, kebijakan eligibility, kapasitas
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-SP-02; KRS reguler (grup academic); Backend SP
- **Sumber:** docs/semester-pendek/tahapan-pengembangan.md#3.1; docs/semester-pendek/tahapan-pengembangan.md#3.4; docs/proposal/Proposal-Civitas-One-Atma-Jaya.pdf#6.3

#### MOB-SP-04 — Ringkasan SP mahasiswa (status, aturan, hitung mundur, jadwal, presensi, IPS SP)

- **Peran:** student
- **Deskripsi:** Halaman Ringkasan: status term, aturan, hitung mundur penutupan, badge status (kuning Menunggu persetujuan, oranye Menunggu pembayaran, hijau Terdaftar), jadwal, presensi, ujian, 'Nilai sedang diproses'; saat COMPLETED menampilkan IPS SP dan dampak IPK.
- **Aturan bisnis:**
  - Modul Semester Pendek belum ada sama sekali (STATUS-IMPLEMENTASI §3/§11: hanya flag academic.short_term; tidak ada route SP di app/Modules/*/Routes/api.php).
- **Kriteria penerimaan/pengujian:**
  - PRT-07, KRS-25 (black-box-testing §14)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Tidak ada endpoint ringkasan SP
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-SP-01..03
- **Sumber:** docs/semester-pendek/frontend-dan-mobile.md#3.2; docs/semester-pendek/black-box-testing.md#14

#### MOB-SP-05 — Tagihan SP dan status pembayarannya (melalui portal Keuangan)

- **Peran:** student
- **Deskripsi:** Tagihan SP bill_group short_term tampil di daftar tagihan yang sama; biaya = SKS x fee_per_credit; jatuh tempo payment_due_days (3 hari docs). Docs SP menyebut 'tanpa pembayaran online, Keuangan mencatat manual'; di kode KEU mahasiswa dapat membayar via manual/online.
- **Aturan bisnis:**
  - Tagihan SP idempoten pada (source_type, source_id); invoice dibatalkan bila kedaluwarsa dan paid=0
  - Pembayaran setelah kedaluwarsa -> 409 (admin daftar ulang atau refund)
  - Kode: nomor tagihan digit term S = 3 untuk pendek; BillGroup::ShortTerm ada di kode KEU
  - Endpoint docs SP GET /student/invoices TIDAK ada; gunakan /student/finance/invoices
  - Konflik docs: SP (no online payment) vs KEU (online/manual tersedia) -> kode KEU berlaku
- **Kriteria penerimaan/pengujian:**
  - Given tagihan SP diposting When daftar tagihan dibuka Then bill_group_label 'Semester Pendek' tampil (AC-11, PAY-02..05)
  - Given kedaluwarsa tanpa bayar Then tagihan cancelled (AC-12/13, PAY-17..19)
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/student/finance/invoices`; `GET /api/v1/student/finance/invoices/{invoice}`
- **Celah backend:** Sisi KEU siap; modul SP yang menerbitkan tagihan (InvoiceIssuer, FinanceBillingService::postExternalInvoice) belum ada sehingga tagihan SP belum akan muncul
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (daftar tagihan generik)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-FIN-02/03; Modul SP (penerbit tagihan)
- **Catatan:** Tidak perlu UI terpisah; cukup memastikan label bill_group dan filter.
- **Sumber:** docs/semester-pendek/pembayaran-dan-notifikasi.md#2; docs/keuangan/06-rancangan-tagihan-mahasiswa.md#7; docs/RANCANGAN_SISTEM_ADMINISTRASI_KEUANGAN_MAHASISWA_KEU.md#10.2; AUDIT/C4-be-finance.md#3 Enums

#### MOB-SP-06 — Status KRS SP 'Menunggu pembayaran' dengan tautan ke Tagihan

- **Peran:** student
- **Deskripsi:** Setelah KRS SP disetujui dan fee>0, tagihan terbit (due +3 hari), KRS tetap pending, badge oranye dengan nominal dan batas bayar; fee 0 langsung terdaftar; lewat jatuh tempo -> KRS gugur, kursi dilepas.
- **Aturan bisnis:**
  - Modul Semester Pendek belum ada sama sekali (STATUS-IMPLEMENTASI §3/§11: hanya flag academic.short_term; tidak ada route SP di app/Modules/*/Routes/api.php).
  - payment_due_days=3
  - Pembayaran lunas -> item enrolled + notifikasi
- **Kriteria penerimaan/pengujian:**
  - KRS-24, PAY-02..05, PAY-08, PAY-17..19, AC-11, AC-12, AC-13
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Bergantung seluruh modul SP
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-SP-03; MOB-SP-05
- **Sumber:** docs/semester-pendek/black-box-testing.md#12; docs/semester-pendek/pengujian-dan-penerimaan.md#AC-11; docs/semester-pendek/tahapan-pengembangan.md#4.5

#### MOB-SP-07 — Refund/penyesuaian ke deposit bila kelas SP dibatalkan setelah bayar

- **Peran:** student
- **Deskripsi:** Kelas SP dibatalkan -> penyesuaian turun ke deposit -> mahasiswa ajukan refund (EC-KEU-19).
- **Aturan bisnis:**
  - Alur refund KEU ada (MOB-FIN-16/18) tetapi pemicu SP tidak ada
- **Kriteria penerimaan/pengujian:**
  - Given kelas dibatalkan setelah bayar When tagihan disesuaikan Then saldo deposit bertambah
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/student/finance/deposit`; `POST /api/v1/student/finance/refunds`
- **Celah backend:** Pemicu pembatalan SP belum ada
- **Status web:** keu/pages/portal/PortalFinancePages.tsx (alur refund generik)
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-FIN-16; MOB-FIN-18; Modul SP
- **Sumber:** docs/RANCANGAN_SISTEM_ADMINISTRASI_KEUANGAN_MAHASISWA_KEU.md#12 EC-KEU-19

#### MOB-SP-08 — Jadwal, presensi, ujian, dan nilai kelas SP untuk mahasiswa

- **Peran:** student
- **Deskripsi:** Kelas SP muncul di jadwal/presensi/nilai portal setelah terdaftar; IPS SP dan dampak ke IPK.
- **Aturan bisnis:**
  - Modul Semester Pendek belum ada sama sekali (STATUS-IMPLEMENTASI §3/§11: hanya flag academic.short_term; tidak ada route SP di app/Modules/*/Routes/api.php).
  - Endpoint portal reguler ada (/student/schedule, /student/attendance, /student/grades) tetapi belum mengenal term SP
- **Kriteria penerimaan/pengujian:**
  - PRT-07 (black-box §14)
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Dukungan term SP di endpoint portal
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-SP-03; Grup academic student portal
- **Sumber:** docs/semester-pendek/black-box-testing.md#14; docs/proposal/Proposal-Civitas-One-Atma-Jaya.pdf#6.3

#### MOB-SP-09 — Dosen: daftar kelas SP yang diampu

- **Peran:** lecturer
- **Deskripsi:** Dosen melihat hanya kelas SP yang diampu (filter academic_term_id, semester).
- **Aturan bisnis:**
  - Modul Semester Pendek belum ada sama sekali (STATUS-IMPLEMENTASI §3/§11: hanya flag academic.short_term; tidak ada route SP di app/Modules/*/Routes/api.php).
  - Kontrak docs: GET /lecturer/class-sections (belum ada); bergantung lecturers.user_id dan class_section_lecturers
  - Endpoint existing reguler: GET /teaching/classes dan GET /class-sections?filter[lecturer_id]= (tanpa semantik SP)
- **Kriteria penerimaan/pengujian:**
  - Given dosen mengampu 2 kelas SP When membuka Then hanya 2 kelas; kelas lain 404
- **Status backend:** `missing`
- **Endpoint:** -
- **Celah backend:** Modul SP, filter semester=pendek, pemetaan dosen-kelas
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Backend SP; Fitur teaching dosen (grup academic-lecturer)
- **Sumber:** docs/semester-pendek/desain-api.md#4; docs/semester-pendek/peran-dan-izin.md#5.1

### Pertanyaan belum terjawab

- Apakah XENDIT_SECRET_KEY/kanal online akan aktif di produksi (dan penyedia lain)? Tanpa itu MOB-FIN-07 hanya menampilkan transfer manual.
- Format bank_accounts (keu.bank_accounts) belum dibakukan; web mengasumsikan {bank,account_number,account_name}. Perlu kontrak tetap agar app tidak salah parsing.
- Apakah cekal view_grades/transcript/yudisium/graduation akan ditegakkan (saat ini hanya krs & exam) dan apakah dosen PA akan mendapat flag cekal advisee (MOB-FIN-37)?
- Apakah penyesuaian tagihan tambah/drop KRS dan biaya cuti sudah terhubung ke KEU (STATUS vs docs bertentangan)?
- Kapan modul Semester Pendek dibangun dan apakah pembayaran SP mengikuti KEU (online/manual) atau dicatat manual oleh Keuangan seperti di docs SP?
- Kebijakan denda (KEU-Q-06), UKT (KEU-Q-13), dan cicilan (KEU-Q-10) per tenant: aktif atau tidak?
- Push notification/FCM belum ada (tidak ada endpoint registrasi token); notifikasi keu.* hanya via polling.

## Matriks Fitur G6 - Kemahasiswaan, Layanan Surat, Kelulusan & Karier

Grup: G6-affairs-services-graduation (ID: MOB-KMHS, MOB-SRV, MOB-GRAD). Kode mengalahkan dokumen: hanya `/api/v1/student/requests*` yang ada; endpoint `/student/affairs/*`, `/student/graduation/*`, `/student/skpi/*`, `/me/career/*` TIDAK ada di routes.

### Layanan & Surat (SRV)

Layanan surat/pengajuan mahasiswa sudah lengkap di backend (cuti, aktif kembali, perubahan data, surat keterangan + PDF, draft/ubah/kirim/batal). Web portal: PortalRequestsPage nyata; halaman Leave/Letter mock tidak ter-route.

#### MOB-SRV-01 - Lihat daftar pengajuan saya
- Peran: student
- Deskripsi: Daftar pengajuan layanan akademik milik mahasiswa (cuti, aktif kembali, perubahan data, surat keterangan, lainnya) dengan status, terbaru dahulu.
- Aturan bisnis:
  - Role student memiliki student_requests.read/create/update; akun tanpa data mahasiswa (dosen/pegawai) -> 403 'Akun ini tidak tertaut ke data mahasiswa.'; pengajuan milik orang lain -> 404 (viewOwn).
  - Paginasi per_page 1..100 (default 20), meta current_page/per_page/total/last_page.
  - type: leave|reactivation|data_change|letter|other; status: draft|submitted|approved|rejected|cancelled (label Draft/Sedang Ditinjau/Disetujui/Ditolak/Dibatalkan).
  - Item list tanpa history; has_letter menandai surat siap unduh.
- Kriteria penerimaan:
  - Given mahasiswa login When membuka daftar Then hanya pengajuan miliknya tampil, terbaru dahulu, dengan badge status.
  - Given >20 pengajuan When scroll Then halaman berikutnya dimuat via page.
  - Given akun dosen When memanggil endpoint Then 403 (tidak ditampilkan menu).
- Status backend: **available**
- Endpoint: `GET /api/v1/student/requests?per_page&page`
- Celah backend: Tidak ada filter status/type di server (filter di klien).
- Status web: pages/portal/PortalRequestsPage.tsx (nyata, route ROUTES.portal.pengajuan)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: `build_integrated`
- Dependensi: MOB-AUTH (login); Flutter: dio/http + pagination
- Sumber: docs/STATUS-IMPLEMENTASI.md#3; D03-role-accounts-status; D08-proposal-pdf; code: app/Modules/Academic/Routes/api.php:165-172, StudentRequestService/StudentRequestController
- Catatan: Backend dibangun setelah dokumen desain; STATUS-IMPLEMENTASI menandai selesai.

#### MOB-SRV-02 - Lihat detail pengajuan, tahap persetujuan, dan riwayat
- Peran: student
- Deskripsi: Detail satu pengajuan: payload, lampiran, tahap saat ini (current_step), reviewer, catatan keputusan, dan riwayat event.
- Aturan bisnis:
  - Resource: title, description, payload, letter_type_label, attachment{id,name,size_bytes}, term{id,label}, submitted_at, decided_at, reviewer, decision_note, cancelled_at, current_step{name,status}, can_act=false untuk mahasiswa, history[{event,description,created_at}], has_letter.
  - Pengajuan orang lain -> 404.
- Kriteria penerimaan:
  - Given pengajuan disetujui When buka detail Then tampil reviewer, decided_at, catatan, riwayat kronologis.
  - Given pengajuan ditolak When buka detail Then decision_note ditampilkan jelas.
  - Given id milik orang lain Then tampil error 404 ramah.
- Status backend: **available**
- Endpoint: `GET /api/v1/student/requests/{studentRequest}`
- Celah backend: Tidak ada id ApprovalRequest di resource sehingga detail langkah multi-tahap harus lewat GET /approval-requests (filter requested_by saya).
- Status web: pages/portal/PortalRequestsPage.tsx (nyata, route ROUTES.portal.pengajuan)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: `build_integrated`
- Dependensi: MOB-SRV-01
- Sumber: docs/STATUS-IMPLEMENTASI.md#3; D03-role-accounts-status; D08-proposal-pdf; code: app/Modules/Academic/Routes/api.php:165-172, StudentRequestService/StudentRequestController; D03#Track request approval status

#### MOB-SRV-03 - Ajukan cuti akademik
- Peran: student
- Deskripsi: Mahasiswa aktif mengajukan cuti akademik untuk semester tertentu (default semester berjalan) dan memantau persetujuan.
- Aturan bisnis:
  - payload leave {academic_term_id?} default term berjalan; title 5..150; description max 2000.
  - Hanya mahasiswa berstatus Aktif (409 "Cuti akademik hanya dapat diajukan oleh mahasiswa berstatus Aktif.").
  - Duplikat pengajuan type sama yang sedang ditinjau -> 409.
  - Saat disetujui status mahasiswa menjadi leave.
  - Alur persetujuan default 1 langkah oleh role academic_administrator (kode); dokumen D02 membayangkan multi-langkah + rekomendasi dosen wali (belum ada).
  - Kode berbeda dari mock web: PortalLeaveRequestPage memakai jenis Akademik/Sakit/Melahirkan, backend hanya satu jenis leave.
- Kriteria penerimaan:
  - Given mahasiswa Aktif When submit cuti dengan judul valid Then 201 status submitted "Sedang Ditinjau".
  - Given status bukan Aktif Then 409 dengan pesan backend ditampilkan.
  - Given sudah ada cuti submitted When ajukan lagi Then 409 duplikat.
  - Given disetujui Then status mahasiswa (profil) menjadi Cuti.
- Status backend: **available**
- Endpoint: `POST /api/v1/student/requests {type:"leave",title,description?,payload?:{academic_term_id},submit:true}`
- Celah backend: Jenis cuti (sakit/melahirkan), lampiran wajib per jenis, rekomendasi dosen wali, periode batas pengajuan: belum ada.
- Status web: pages/portal/PortalRequestsPage.tsx (nyata); PortalLeaveRequestPage.tsx = mock tidak ter-route
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: `build_integrated`
- Dependensi: MOB-SRV-01; MOB-SRV-10 (lampiran opsional)
- Sumber: docs/STATUS-IMPLEMENTASI.md#3; D03-role-accounts-status; D08-proposal-pdf; code: app/Modules/Academic/Routes/api.php:165-172, StudentRequestService/StudentRequestController; D15-al-uiux-rules#Leave request; D16-al-reports-sec-tests-roadmap#Cuti; D01-flutter-design

#### MOB-SRV-04 - Ajukan aktif kembali setelah cuti
- Peran: student
- Deskripsi: Mahasiswa berstatus cuti/nonaktif mengajukan aktif kembali.
- Aturan bisnis:
  - payload reactivation {}; hanya status leave/inactive (409 jika Aktif).
  - Saat disetujui status menjadi active.
  - Duplikat pengajuan sedang ditinjau -> 409.
- Kriteria penerimaan:
  - Given mahasiswa berstatus Cuti When ajukan aktif kembali Then submitted.
  - Given mahasiswa Aktif Then 409 pesan backend ditampilkan.
  - Given disetujui Then status Aktif.
- Status backend: **available**
- Endpoint: `POST /api/v1/student/requests {type:"reactivation"}`
- Celah backend: Mahasiswa status leave: perlu dipastikan login/akses student/requests tidak diblokir (login diblokir hanya untuk inactive/dropped_out/resigned) - inactive tidak bisa login sehingga aktif kembali dari inactive tidak dapat dilakukan lewat app.
- Status web: pages/portal/PortalRequestsPage.tsx (nyata, route ROUTES.portal.pengajuan)
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: `build_integrated`
- Dependensi: MOB-SRV-01
- Sumber: docs/STATUS-IMPLEMENTASI.md#3; D03-role-accounts-status; D08-proposal-pdf; code: app/Modules/Academic/Routes/api.php:165-172, StudentRequestService/StudentRequestController; D15#Reactivation

#### MOB-SRV-05 - Ajukan perubahan data pribadi
- Peran: student
- Deskripsi: Mahasiswa meminta perubahan data yang read-only di profil (nama, email, tempat lahir, tanggal lahir, jenis kelamin).
- Aturan bisnis:
  - payload data_change {changes:{name?,email?,birth_place?,tanggal_lahir?,gender?}} minimal satu field.
  - tanggal_lahir harus sebelum hari ini; gender male|female; email unik di antara user.
  - Opsi field dari GET /student/requests/options.data_change_fields.
  - Saat disetujui student (+ nama/email user) diperbarui.
  - Telepon/alamat/foto diedit langsung lewat PATCH /student/profile (bukan pengajuan).
- Kriteria penerimaan:
  - Given mahasiswa mengisi email yang sudah dipakai When submit Then 422 pada payload.changes.email.
  - Given tidak ada field berubah Then validasi menolak.
  - Given disetujui Then profil menampilkan data baru dan pending_data_change hilang.
- Status backend: **available**
- Endpoint: `GET /api/v1/student/requests/options`; `POST /api/v1/student/requests {type:"data_change",payload:{changes}}`; `GET /api/v1/student/profile (pending_data_change)`
- Celah backend: Tidak ada bukti dokumen wajib per field.
- Status web: pages/portal/PortalRequestsPage.tsx (nyata, route ROUTES.portal.pengajuan)
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: `build_integrated`
- Dependensi: MOB-SRV-01; profil mahasiswa (group akademik)
- Sumber: docs/STATUS-IMPLEMENTASI.md#3; D03-role-accounts-status; D08-proposal-pdf; code: app/Modules/Academic/Routes/api.php:165-172, StudentRequestService/StudentRequestController; D10/D13 (Perubahan data)

#### MOB-SRV-06 - Ajukan surat keterangan (aktif kuliah, berkelakuan baik, rekomendasi, lainnya)
- Peran: student
- Deskripsi: Mahasiswa meminta surat resmi dengan jenis dan keperluan, memantau status.
- Aturan bisnis:
  - payload letter {letter_type: active_student|good_conduct|recommendation|other (wajib), purpose (wajib, max 255)}.
  - Surat active_student hanya untuk status Aktif.
  - Label jenis diambil dari GET /student/requests/options.
  - Dokumen desain KEM (D22) menambah: tujuan/instansi, bahasa, maks 3 pengajuan terbuka (TOO_MANY_ACTIVE_REQUESTS), NO_CURRENT_TERM: belum ada di kode.
- Kriteria penerimaan:
  - Given status Aktif When ajukan active_student dengan purpose Then submitted.
  - Given purpose kosong Then 422 payload.purpose.
  - Given status Cuti When ajukan active_student Then 409.
- Status backend: **available**
- Endpoint: `GET /api/v1/student/requests/options`; `POST /api/v1/student/requests {type:"letter",payload:{letter_type,purpose}}`
- Celah backend: Field tujuan/instansi/bahasa/jumlah salinan, batas pengajuan terbuka, penomoran surat resmi: belum ada.
- Status web: pages/portal/PortalRequestsPage.tsx (nyata, route ROUTES.portal.pengajuan); PortalLetterRequestPage.tsx mock tidak ter-route
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: `build_integrated`
- Dependensi: MOB-SRV-01
- Sumber: docs/STATUS-IMPLEMENTASI.md#3; D03-role-accounts-status; D08-proposal-pdf; code: app/Modules/Academic/Routes/api.php:165-172, StudentRequestService/StudentRequestController; D22-kk-01-09#Request active-student letter; D25#Request surat keterangan aktif; D05

#### MOB-SRV-07 - Unduh PDF surat yang sudah disetujui
- Peran: student
- Deskripsi: Mahasiswa mengunduh dan membagikan PDF surat keterangan setelah pengajuan disetujui.
- Aturan bisnis:
  - Hanya pengajuan type letter berstatus approved; selain itu 409 "Surat hanya tersedia untuk pengajuan yang sudah disetujui.".
  - PDF Dompdf, nama berkas surat-<nim>-<Ymd>.pdf, Content-Disposition attachment.
  - Perlu header Authorization Bearer (bukan signed URL) - unduh via klien HTTP, simpan, buka dengan penampil PDF.
  - Kode: PDF dirender ulang setiap unduh (belum dibekukan/ber-QR/ber-nomor resmi seperti rancangan D22 KEM-BR-072).
  - Daftar dokumen mahasiswa GET /student/documents juga memuat surat disetujui.
- Kriteria penerimaan:
  - Given letter approved When tap Unduh Then PDF tersimpan dan dapat dibuka/dibagikan.
  - Given belum disetujui Then tombol disembunyikan (has_letter=false) / 409 ditangani.
  - Given token kedaluwarsa Then 401 -> alur login.
- Status backend: **available**
- Endpoint: `GET /api/v1/student/requests/{studentRequest}/letter`; `GET /api/v1/student/documents`
- Celah backend: Nomor surat resmi, QR verifikasi, pembekuan berkas, log akses unduh: belum ada.
- Status web: pages/portal/PortalRequestsPage.tsx (nyata)
- Status mobile saat ini: none
- Prioritas: P0
- Cakupan rekomendasi: `build_integrated`
- Dependensi: MOB-SRV-06; Flutter: path_provider, open_filex/share_plus, pdf viewer
- Sumber: docs/STATUS-IMPLEMENTASI.md#3; D03-role-accounts-status; D08-proposal-pdf; code: app/Modules/Academic/Routes/api.php:165-172, StudentRequestService/StudentRequestController; D22#Download issued active-student letter PDF

#### MOB-SRV-08 - Simpan draft, ubah draft, dan kirim draft pengajuan
- Peran: student
- Deskripsi: Menyimpan pengajuan sebagai draft, mengubahnya, lalu mengirim.
- Aturan bisnis:
  - POST dengan submit=false -> draft ("Draft pengajuan disimpan."); PUT hanya untuk draft (409 "Pengajuan yang sudah dikirim tidak dapat diubah lagi."); type tidak boleh diubah.
  - POST /{id}/submit hanya draft (409 jika sudah dikirim); membuat ApprovalRequest.
- Kriteria penerimaan:
  - Given draft When ubah judul Then tersimpan; Given submitted When PUT Then 409.
  - Given draft When Kirim Then status submitted dan masuk tahap persetujuan.
- Status backend: **available**
- Endpoint: `POST /api/v1/student/requests {submit:false}`; `PUT /api/v1/student/requests/{id}`; `POST /api/v1/student/requests/{id}/submit`
- Celah backend: -
- Status web: PUT/draft-edit tidak ada UI web ("no web UI")
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: `build_integrated`
- Dependensi: MOB-SRV-03..06
- Sumber: docs/STATUS-IMPLEMENTASI.md#3; D03-role-accounts-status; D08-proposal-pdf; code: app/Modules/Academic/Routes/api.php:165-172, StudentRequestService/StudentRequestController

#### MOB-SRV-09 - Batalkan pengajuan
- Peran: student
- Deskripsi: Menarik pengajuan yang masih draft atau sedang ditinjau.
- Aturan bisnis:
  - Hanya draft/submitted; selain itu 409 "Hanya pengajuan yang masih draft atau sedang ditinjau yang dapat dibatalkan.".
  - Konfirmasi dialog sebelum membatalkan.
- Kriteria penerimaan:
  - Given submitted When batalkan Then status Dibatalkan, cancelled_at terisi.
  - Given approved Then aksi tidak ditawarkan / 409 ditangani.
- Status backend: **available**
- Endpoint: `POST /api/v1/student/requests/{id}/cancel`
- Celah backend: -
- Status web: pages/portal/PortalRequestsPage.tsx (nyata, route ROUTES.portal.pengajuan)
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: `build_integrated`
- Dependensi: MOB-SRV-02
- Sumber: docs/STATUS-IMPLEMENTASI.md#3; D03-role-accounts-status; D08-proposal-pdf; code: app/Modules/Academic/Routes/api.php:165-172, StudentRequestService/StudentRequestController

#### MOB-SRV-10 - Unggah lampiran pengajuan (kamera/berkas)
- Peran: student
- Deskripsi: Melampirkan satu berkas pendukung pada pengajuan lewat upload file lalu attachment_file_id.
- Aturan bisnis:
  - POST /file-uploads multipart field file (mimes pdf,jpg,jpeg,png,doc,docx,xls,xlsx; maks file.max_upload_size_mb default 10 MB); status harus clean; hanya uploader yang dapat melampirkan; belum terlampir di tempat lain.
  - attachment_file_id max 26 karakter ULID, satu lampiran per pengajuan.
  - Tidak ada batas per jenis pengajuan (gap backend).
  - Pola umum PMB D31: foto 1MB, KTP 2MB, rapor 5MB - itu untuk PMB, bukan pengajuan ini.
- Kriteria penerimaan:
  - Given berkas 12 MB When unggah Then ditolak sebelum/sesudah dengan pesan ukuran.
  - Given berkas valid When submit dengan attachment_file_id Then detail menampilkan nama dan ukuran lampiran.
- Status backend: **available**
- Endpoint: `POST /api/v1/file-uploads`; `GET /api/v1/file-uploads/{id}/download`
- Celah backend: Batas ukuran/tipe per jenis pengajuan belum ada (gap C2).
- Status web: pages/portal/PortalRequestsPage.tsx (cek apakah ada picker lampiran; tidak diverifikasi)
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_integrated`
- Dependensi: Flutter: image_picker, file_picker
- Sumber: C2-be-academic-student#8; C5-be-other-modules (file-uploads); D31-manual-pmb-applicant

#### MOB-SRV-11 - Ajukan ulang pengajuan yang dikembalikan (returned)
- Peran: student
- Deskripsi: Jika langkah persetujuan mengembalikan pengajuan untuk perbaikan, mahasiswa memperbaiki dan mengajukan ulang.
- Aturan bisnis:
  - Approval engine: status submitted|in_progress|returned|approved|rejected; POST /approval-requests/{id}/resubmit khusus pemohon; can_resubmit pada resource.
  - StudentRequest.status hanya draft|submitted|approved|rejected|cancelled (tidak ada returned), dan resource StudentRequest tidak mengekspos approval_request_id.
  - Aksi return oleh approver mensyaratkan komentar (max 1000).
- Kriteria penerimaan:
  - Given pengajuan returned When mahasiswa buka Then alasan dan tombol Ajukan Ulang tampil.
  - Given resubmit Then status kembali in_progress.
- Status backend: **partial**
- Endpoint: `GET /api/v1/approval-requests?filter[status]=returned`; `GET /api/v1/approval-requests/{id}`; `POST /api/v1/approval-requests/{id}/resubmit`
- Celah backend: Tautan StudentRequest -> ApprovalRequest tidak diekspos; status returned tidak tercermin di StudentRequestResource; perlu verifikasi apakah workflow default menyediakan aksi return untuk pengajuan mahasiswa.
- Status web: none (tidak diverifikasi di PortalRequestsPage)
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: MOB-SRV-02; backend: ekspos approval_request_id/status returned
- Sumber: D03-role-accounts-status#resubmit returned; C5-be-other-modules (ApprovalWorkflow)
- Catatan: Pilihan: tampilkan sebagai "Menunggu backend" sampai tautan tersedia.

#### MOB-SRV-12 - Notifikasi perubahan status pengajuan
- Peran: student
- Deskripsi: Mahasiswa menerima pemberitahuan ketika pengajuan disetujui/ditolak/dikembalikan atau surat terbit.
- Aturan bisnis:
  - GET /notifications, /notifications/unread-count, POST /notifications/read-all, PATCH /notifications/{id}/read (auth saja).
  - Channel yang aktif hanya database (+mail); push belum ada; link berisi path web, bukan deep link mobile.
  - Notifikasi dokumen terbit/dicabut (D25) hanya rancangan.
- Kriteria penerimaan:
  - Given pengajuan diputuskan When buka tab notifikasi Then item tampil (jika backend mengirim event) dan dapat ditandai dibaca.
- Status backend: **partial**
- Endpoint: `GET /api/v1/notifications`; `GET /api/v1/notifications/unread-count`; `PATCH /api/v1/notifications/{id}/read`; `POST /api/v1/notifications/read-all`
- Celah backend: Belum terverifikasi apakah StudentRequestService mengirim notifikasi keputusan; tidak ada endpoint registrasi push/FCM; link web perlu dipetakan ke layar mobile.
- Status web: pages/portal/PortalNotificationsPage.tsx
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: `build_integrated`
- Dependensi: Infrastruktur push (FCM) - belum ada; MOB-notifikasi (group lain)
- Sumber: D25-kk-27-34#Document issued/revoked; C5-be-other-modules#Notification

#### MOB-SRV-13 - Dosen/PA: setujui, tolak, kembalikan, atau delegasikan langkah persetujuan
- Peran: lecturer, advisor
- Deskripsi: Pejabat/dosen yang ditugaskan sebagai approver pada langkah workflow dapat memutuskan permintaan.
- Aturan bisnis:
  - Endpoint POST /approval-request-steps/{id}/approve|reject|return|delegate; reject/return wajib komentar max 1000; delegate_to user id (perlu flag delegasi).
  - GET /approval-requests/{id} diizinkan untuk approver langkah saat ini.
  - Kode: workflow bawaan pengajuan mahasiswa hanya satu langkah oleh role academic_administrator - dosen/PA tidak menerima pengajuan mahasiswa; dokumen D02 membayangkan rekomendasi dosen wali.
  - GET /student-requests (staff) butuh approval_requests.read (admin) dan tidak diberikan ke dosen.
- Kriteria penerimaan:
  - Given dosen ditetapkan sebagai approver When buka permintaan Then tombol aksi tampil dan komentar wajib untuk reject/return.
- Status backend: **partial**
- Endpoint: `GET /api/v1/approval-requests/{id}`; `POST /api/v1/approval-request-steps/{id}/approve|reject|return|delegate`
- Celah backend: Tidak ada daftar "tugas persetujuan saya" (GET /approval-requests hanya requested_by saya tanpa approval_requests.read); langkah workflow mahasiswa tidak melibatkan dosen.
- Status web: none untuk dosen (portal staff admin saja, "no web page")
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: backend: inbox approver, konfigurasi workflow dengan langkah dosen wali; push notification
- Sumber: D01-flutter-design#Approve, reject or delegate; D06-exam-docs-videos#approval workflow; D02-app-design#Give recommendation
- Catatan: Perlu verifikasi permission dosen pada approval-request-steps (tidak terdaftar di bundle).

#### MOB-SRV-14 - Dosen wali: beri rekomendasi atas pengajuan cuti anak bimbingan
- Peran: advisor
- Deskripsi: Dosen PA memberi rekomendasi/persetujuan awal pada cuti mahasiswa bimbingan sebelum keputusan akademik.
- Aturan bisnis:
  - Hanya rancangan D02 (Tahap 4 Cuti); tidak ada langkah dosen wali di workflow kode.
- Kriteria penerimaan:
  - Given cuti anak bimbingan When dosen PA buka Then terlihat dan dapat merekomendasikan setuju/tidak dengan catatan.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/advising/leave-requests (kontrak belum didefinisikan)`
- Celah backend: Workflow multi-langkah + inbox dosen wali + endpoint PA.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: MOB-SRV-13; MOB-SRV-03
- Sumber: D02-app-design#Give recommendation on advisee leave request

#### MOB-SRV-15 - Kirim pengaduan / tiket layanan dan lacak
- Peran: student, lecturer
- Deskripsi: Mengirim pertanyaan/pengaduan dan melacak sampai selesai (helpdesk).
- Aturan bisnis:
  - Hanya rancangan (STATUS §12 Pengaduan & layanan mahasiswa belum dibangun); web mock one/pages/Support.tsx.
- Kriteria penerimaan:
  - Given pengguna When mengirim tiket Then tiket muncul dengan status dan balasan petugas.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] POST/GET /api/v1/student/tickets (tidak ada kontrak)`
- Celah backend: Belum ada modul/endpoint backend; Controller/route tidak ditemukan di app/Modules/*/Routes/api.php. Kontrak endpoint belum didefinisikan.
- Status web: mock (one/pages/Support.tsx)
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: backend modul helpdesk
- Sumber: D02#Submit complaint; D05#helpdesk; D03#Student complaints and services; C8 one/pages/Support.tsx

#### MOB-SRV-16 - Katalog layanan mahasiswa (KTM, IT, konseling, kesehatan, asrama)
- Peran: student
- Deskripsi: Katalog layanan kampus dengan pelacakan permintaan.
- Aturan bisnis:
  - Hanya mock static di web (one); backend hanya mendukung surat/cuti/aktif/perubahan data.
- Kriteria penerimaan:
  - Given katalog When pilih layanan tanpa backend Then tidak ada data palsu; tampil label "Belum tersedia".
- Status backend: **missing**
- Endpoint: -
- Celah backend: Tidak ada modul layanan selain student requests.
- Status web: mock (one/pages/Support.tsx)
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: C8-fe-one-app (Support.tsx)

#### MOB-SRV-17 - Verifikasi keaslian dokumen (kode/QR) - pemindai di app
- Peran: student
- Deskripsi: Memverifikasi surat/dokumen akademik via kode/QR publik.
- Aturan bisnis:
  - Rancangan: kode 128-bit disimpan ter-hash, URL tanpa NIM, throttle 20/menit/IP; compare hash PDF 5/menit.
- Kriteria penerimaan:
  - Given kode valid When scan Then tampil status keaslian tanpa NIM.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/public/verify/{code}`; `[BELUM ADA] POST /api/v1/public/verify/{code}/compare`
- Celah backend: Seluruh modul penerbitan dokumen/QR.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: Flutter: mobile_scanner
- Sumber: D22#Public verification; D23#Public document authenticity; D24#Public survey and public document verification
- Catatan: Fitur publik; bukan kebutuhan harian mahasiswa.

### Kemahasiswaan (KMHS)

Modul kemahasiswaan (poin, disiplin, beasiswa, organisasi) belum dibangun. Hanya endpoint baca admin-only (scholarships, theses, internships, books) yang ada dan tidak dapat diakses student/lecturer.

#### MOB-KMHS-01 - Lihat poin kemahasiswaan saya (total, per kategori, riwayat)
- Peran: student
- Deskripsi: Total poin, rincian per kategori, ledger riwayat poin.
- Aturan bisnis:
  - Ledger append-only (award/correction/revocation/appeal_adjustment) KEM-BR-002.
  - Poin hanya setelah verifikasi (KEM-BR-001); kelebihan cap periode tidak dihitung (KEM-BR-005).
  - Poin yudisium hanya dari kategori counts_for_graduation.
- Kriteria penerimaan:
  - Given mahasiswa When buka Poin Then total dan riwayat tampil dengan status.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student/affairs/points`
- Celah backend: Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12).
- Status web: none (tidak ada halaman)
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: -
- Sumber: D22-kk-01-09; D24-kk-19-26; D03; D05

#### MOB-KMHS-02 - Ajukan kegiatan/prestasi dengan bukti
- Peran: student
- Deskripsi: Wizard pengajuan kegiatan/prestasi untuk poin: kategori, tanggal, bukti, kirim.
- Aturan bisnis:
  - Bukti pdf/jpg/jpeg/png maks student_affairs.evidence.max_size_mb default 5 (<= file.max_upload_size_mb 10), cek MIME isi (KEM-BR-021).
  - Semua jenis bukti wajib kategori terunggah & clean (KEM-BR-020).
  - Tanggal: mulai<=selesai<=hari ini (zona waktu tenant).
  - Idempotency-Key pada submit.
- Kriteria penerimaan:
  - Given bukti kurang When kirim Then 422; Given lengkap Then status submitted.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student/affairs/activity-submissions`; `[BELUM ADA] POST /api/v1/student/affairs/activity-submissions`; `[BELUM ADA] PUT /api/v1/student/affairs/activity-submissions/{submission}`; `[BELUM ADA] POST /api/v1/student/affairs/activity-submissions/{submission}/submit`; `[BELUM ADA] POST /api/v1/student/affairs/activity-submissions/{submission}/withdraw`; `[BELUM ADA] POST /api/v1/file-uploads (ada)`
- Celah backend: Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12).
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: image_picker/file_picker; MOB-SRV-10
- Sumber: D22; D25#Submit activity/achievement for points (must); D02; D05

#### MOB-KMHS-03 - Lacak pengajuan kegiatan dan umpan balik petugas
- Peran: student
- Deskripsi: Status pengajuan (submitted, revisi, ditolak, disetujui) dan catatan petugas.
- Aturan bisnis:
  - Alasan penolakan/revisi wajib (KEM-BR-027); SLA lewat hanya notifikasi manajer, tanpa persetujuan otomatis.
- Kriteria penerimaan:
  - Given revisi diminta Then catatan tampil dan dapat diperbaiki/kirim ulang.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student/affairs/activity-submissions`
- Celah backend: Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12).
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: MOB-KMHS-02
- Sumber: D22; D25

#### MOB-KMHS-04 - Banding keputusan poin / pengajuan ditolak
- Peran: student
- Deskripsi: Mengajukan banding atas penolakan atau keputusan poin.
- Aturan bisnis:
  - Jendela student_affairs.points.appeal_window_days default 14 (KEM-BR-011); maks satu banding aktif per pengajuan; terlambat -> 422 APPEAL_WINDOW_CLOSED.
- Kriteria penerimaan:
  - Given dalam 14 hari When banding Then diterima; Given lewat Then ditolak 422.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] POST /api/v1/student/affairs/activity-submissions/{submission}/appeal`
- Celah backend: Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12).
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: MOB-KMHS-03
- Sumber: D22; D02

#### MOB-KMHS-05 - Jelajahi dan klaim kegiatan terdaftar
- Peran: student
- Deskripsi: Mencari kegiatan resmi yang sudah terdaftar dan mengklaim kepesertaan/peran.
- Aturan bisnis:
  - Satu pengajuan aktif per (kegiatan, peran) KEM-BR-025; auto_award hanya oleh student_affairs_manager.
- Kriteria penerimaan:
  - Given kegiatan terdaftar When klaim Then pengajuan terbuat.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student/affairs/activity-events`
- Celah backend: Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12).
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D22

#### MOB-KMHS-06 - Lihat kasus disiplin/sanksi saya, ajukan pembelaan dan keberatan
- Peran: student
- Deskripsi: Melihat kasus, keputusan, sanksi aktif; mengirim pernyataan pembelaan dan keberatan.
- Aturan bisnis:
  - Jendela keberatan student_affairs.discipline.appeal_window_days default 14 sejak notifikasi (KEM-BR-034); satu keberatan aktif per keputusan.
  - Sanksi tetap berlaku selama keberatan kecuali suspension_granted.
  - Sanksi aktif = final AND effective_from <= hari ini < effective_until (null = tidak terbatas).
  - Data sensitif: laporan/pelapor tidak ditampilkan.
- Kriteria penerimaan:
  - Given sanksi final When buka Then detail dan tombol keberatan tampil hingga batas hari.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student/affairs/discipline`; `[BELUM ADA] POST /api/v1/student/affairs/violation-cases/{case}/statement`; `[BELUM ADA] POST /api/v1/student/affairs/disciplinary-decisions/{decision}/appeal`
- Celah backend: Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12).
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: -
- Sumber: D22; D25#Discipline notifications; D02; D05#View violations

#### MOB-KMHS-07 - Laporkan pelanggaran (mahasiswa/dosen)
- Peran: student, lecturer
- Deskripsi: Melapor dugaan pelanggaran disiplin.
- Aturan bisnis:
  - Laporan hanyalah klaim tanpa sanksi (KEM-BR-030); incident_at <= sekarang; pelapor hanya melihat status; bukti pdf/jpg/jpeg/png/mp4; kerahasiaan identitas pelapor opsional (KEM-BR-036).
  - Endpoint dosen berprefiks staf: POST /student-affairs/violation-reports; endpoint mahasiswa belum ditentukan.
- Kriteria penerimaan:
  - Given laporan terkirim Then pelapor melihat status saja.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] POST /api/v1/student-affairs/violation-reports (dosen)`
- Celah backend: Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12).; Endpoint mahasiswa tidak didefinisikan.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D22#Lecturer files a violation report

#### MOB-KMHS-08 - Dosen wali: lihat poin dan pengajuan kegiatan anak bimbingan
- Peran: advisor, lecturer
- Deskripsi: Pandangan baca poin dan pengajuan kegiatan mahasiswa bimbingan.
- Aturan bisnis:
  - Permission student_points.read + activity_submissions.read scope wali; dosen wali tidak memverifikasi kegiatan.
- Kriteria penerimaan:
  - Given PA When buka mahasiswa bimbingan Then poin tampil; selain bimbingan 403.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student-affairs/students/{student}/points`
- Celah backend: Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12).; Role academic_advisor belum punya permission terkait; UX dosen belum didefinisikan.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D22; D24#Lecturer/dosen wali read access; D02

#### MOB-KMHS-09 - Dosen wali: ringkasan sanksi final anak bimbingan
- Peran: advisor
- Deskripsi: Ringkasan keputusan disiplin final anak bimbingan dan notifikasinya.
- Aturan bisnis:
  - Setting student_affairs.discipline.notify_advisor_on_final default false; hanya sanksi final, laporan/kasus berjalan tidak ditampilkan.
- Kriteria penerimaan:
  - Given sanksi final & setting aktif Then PA menerima ringkasan.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (permission disciplinary_decisions.read scope wali; endpoint belum ada)`
- Celah backend: Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12).
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D22; D25#Lecturer role

#### MOB-KMHS-10 - Jelajahi program beasiswa yang cocok
- Peran: student
- Deskripsi: Daftar program beasiswa terbuka dengan pra-cek syarat otomatis.
- Aturan bisnis:
  - Pra-cek: gpa_min, student_status, semester_range, tidak ada sanksi aktif.
  - Pendaftaran hanya dalam registration_start..registration_end.
  - Kode: GET /scholarships ada tetapi memerlukan scholarships.read yang TIDAK dimiliki student maupun dosen, tanpa scoping data sendiri.
- Kriteria penerimaan:
  - Given program terbuka Then tampil dengan hasil pra-cek syarat.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student/affairs/scholarships`; `[BELUM ADA] (ada tapi admin-only) GET /api/v1/scholarships`
- Celah backend: Endpoint mahasiswa belum ada; /scholarships 403 untuk student.; Modul read-only admin (STATUS §10).
- Status web: pages/portal/PortalScholarshipPage.tsx (mock, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: -
- Sumber: D22; D02; D03#Apply for scholarships

#### MOB-KMHS-11 - Ajukan beasiswa dan lacak seleksi
- Peran: student
- Deskripsi: Mengajukan beasiswa dengan dokumen dan memantau status seleksi.
- Aturan bisnis:
  - Satu pengajuan per program per mahasiswa (KEM-BR-051); syarat otomatis gagal memblokir kecuali soft (V-SCH-03); program ditutup saat draft -> tidak bisa submit (AF-SCH-05); data ekonomi terenkripsi, hanya panitia seleksi (KEM-BR-059).
  - Status ada di model: submitted/under_review/...
- Kriteria penerimaan:
  - Given syarat lolos When ajukan Then status submitted dan dapat dilacak.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] POST /api/v1/student/affairs/scholarships/{scholarship}/applications`; `[BELUM ADA] GET /api/v1/scholarship-applications (admin-only, ada)`
- Celah backend: Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12).; /scholarship-applications tidak ter-scope ke mahasiswa.
- Status web: PortalScholarshipPage.tsx mock
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: MOB-KMHS-10; MOB-SRV-10
- Sumber: D22; D25; D02#Apply for scholarship

#### MOB-KMHS-12 - Terima/tolak penawaran beasiswa dan lihat evaluasi
- Peran: student
- Deskripsi: Menerima/menolak award, melihat periode dan evaluasi.
- Aturan bisnis:
  - requires_acceptance + acceptance_deadline; lewat batas -> declined dan kuota kembali.
  - Evaluasi gagal tidak otomatis terminasi (KEM-BR-056); terminasi berlaku sejak term (KEM-BR-057); perubahan status (cuti/DO/lulus) memicu evaluasi (KEM-BR-058).
- Kriteria penerimaan:
  - Given penawaran When terima sebelum batas Then award aktif.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] POST /api/v1/student/affairs/scholarship-awards/{award}/accept`; `[BELUM ADA] POST /api/v1/student/affairs/scholarship-awards/{award}/decline`
- Celah backend: Modul Kemahasiswaan (poin, disiplin, beasiswa) belum dibangun (STATUS §11/§12).
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D22; D25#Scholarship result/award notifications

#### MOB-KMHS-13 - Beasiswa/potongan biaya saya (read-only)
- Peran: student
- Deskripsi: Melihat beasiswa/diskon yang diterima dan efeknya pada tagihan.
- Aturan bisnis:
  - Hanya rancangan keuangan (keu-20/21); diskon penerimaan baru PMB sudah ada (D32 Implemented) tetapi bukan endpoint student.
- Kriteria penerimaan:
  - Given beasiswa aktif Then tampil periode dan nominal potongan.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (rencana: bagian student finance; tidak ada)`
- Celah backend: Tidak ada endpoint; student finance API (22 endpoint) tidak memuat beasiswa.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: MOB-KEU group (finance)
- Sumber: D20-keu-19-20; D21-keu-21-29; D32-manual-pmb-committee

#### MOB-KMHS-14 - Organisasi dan kegiatan mahasiswa (proposal, dana, presensi, LPJ)
- Peran: student
- Deskripsi: Fitur organisasi kemahasiswaan.
- Aturan bisnis:
  - STATUS §12: belum dibangun; web one/pages/Campus.tsx mock/static.
- Kriteria penerimaan:
  - Tidak ada data palsu; tampil label belum tersedia bila ditampilkan.
- Status backend: **missing**
- Endpoint: -
- Celah backend: Seluruh modul.
- Status web: mock (one/pages/Campus.tsx)
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D02; D03; C8 Campus.tsx

#### MOB-KMHS-15 - Tugas akhir/skripsi dan bimbingan (mahasiswa dan dosen pembimbing)
- Peran: student, lecturer
- Deskripsi: Melihat tugas akhir, jadwal bimbingan; dosen melihat mahasiswa bimbingan.
- Aturan bisnis:
  - Kode: GET /theses, /theses/{id} read-only admin (theses.read), tidak di-scope.
  - Gap C5: butuh my thesis & bimbingan, lecturer supervisees.
- Kriteria penerimaan:
  - Given mahasiswa When buka Then tugas akhirnya saja.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (ada, admin-only) GET /api/v1/theses`
- Celah backend: Student/lecturer tidak punya theses.read; tanpa scoping; tidak ada bimbingan/log.
- Status web: PortalThesisAdvisingPage.tsx mock tidak ter-route
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: C5-be-other-modules; C7 PortalThesisAdvisingPage (mock)
- Catatan: Backend status: missing untuk peran mahasiswa/dosen walau ada endpoint admin.

#### MOB-KMHS-16 - Magang/MBKM: apply, logbook, konversi; umpan balik pembimbing
- Peran: student, lecturer
- Deskripsi: MBKM dan magang.
- Aturan bisnis:
  - Kode: GET /internships read-only admin-only; MBKM logbook/konversi "Baru" (OD-14).
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (ada, admin-only) GET /api/v1/internships`
- Celah backend: Tidak ada endpoint own-data, logbook, konversi.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D16#MBKM; C5

#### MOB-KMHS-17 - Pinjaman buku dan denda saya
- Peran: student
- Deskripsi: Pinjaman perpustakaan aktif dan denda.
- Aturan bisnis:
  - Kode: GET /books read-only; tidak ada loans; blok yudisium perpus butuh data pinjaman.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (tidak ada endpoint loans)`
- Celah backend: my book loans/fines.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: C5 gap; D05#library blocks

#### MOB-KMHS-18 - Pengaturan akses wali/orang tua dan persetujuan mahasiswa
- Peran: student
- Deskripsi: Mahasiswa mengatur akses terbatas orang tua/wali.
- Aturan bisnis:
  - "Baru"/rencana (OD-21); belum ada.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: -
- Celah backend: Seluruh fitur.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D10; D14; D16

#### MOB-KMHS-19 - Notifikasi kemahasiswaan (pengajuan, disiplin, beasiswa)
- Peran: student, advisor
- Deskripsi: Notifikasi status pengajuan kegiatan, kasus disiplin, hasil beasiswa.
- Aturan bisnis:
  - Rancangan D25; infrastruktur /notifications ada tetapi event ini belum dikirim.
- Kriteria penerimaan:
  - Given event terjadi Then masuk daftar notifikasi.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (pakai GET /api/v1/notifications)`
- Celah backend: Event/template belum ada; push belum ada.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: MOB-SRV-12
- Sumber: D25-kk-27-34

### Kelulusan & Karier (GRAD)

Yudisium, wisuda, SKPI, tracer study, dan Karirlink masih rancangan; halaman web berupa mock yang tidak ter-route.

#### MOB-GRAD-01 - Lihat syarat dan periode yudisium
- Peran: student
- Deskripsi: Persyaratan yang berlaku dan periode pendaftaran.
- Aturan bisnis:
  - Ambang dari konfigurasi kampus tanpa default tersembunyi (KEL-BR-008); bobot kekhususan periode32>kurikulum16>prodi8>rentang masuk4>fakultas2>jenjang1; tanpa kecocokan -> manual_review NO_RULE_SET.
- Kriteria penerimaan:
  - Given periode terbuka Then syarat tampil.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student/graduation/requirements`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: -
- Sumber: D23

#### MOB-GRAD-02 - Lihat checklist kelayakan yudisium
- Peran: student
- Deskripsi: Hasil cek tiap persyaratan (SKS, IPK, skripsi, keuangan, perpustakaan, poin) dan status agregat.
- Aturan bisnis:
  - Hanya persyaratan wajib memengaruhi agregat; source_unavailable tidak pernah lulus (KEL-BR-021).
  - Hasil basi bila sumber berubah atau umur > graduation.eligibility.max_age_hours (72).
  - Item keuangan tanpa nominal (KEL-BR-026).
- Kriteria penerimaan:
  - Given eligibility Then tiap persyaratan lulus/gagal/perlu verifikasi tampil.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student/graduation/eligibility`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: -
- Sumber: D23 (must); D25; D02; D05#Yudisium eligibility
- Catatan: Halaman web ada tetapi static mock; data akademik pendukung (IPK/SKS) tersedia dari API student portal lain.

#### MOB-GRAD-03 - Minta pengecekan ulang kelayakan
- Peran: student
- Deskripsi: Memicu hitung ulang kelayakan.
- Aturan bisnis:
  - graduation.eligibility.self_recheck_per_hour default 3 -> 429.
- Kriteria penerimaan:
  - Given >3 kali per jam Then 429 ditangani.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] POST /api/v1/student/graduation/eligibility/recheck`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: MOB-GRAD-02
- Sumber: D23

#### MOB-GRAD-04 - Daftar yudisium dan unggah dokumen persyaratan
- Peran: student
- Deskripsi: Membuat pendaftaran yudisium, mengunggah dokumen, kirim.
- Aturan bisnis:
  - Submit hanya bila eligibility eligible/exempted/pending_verification untuk dokumen verifiable (KEL-BR-040).
  - 409 PERIOD_NOT_OPEN, UNIT_NOT_PARTICIPATING, ALREADY_GRADUATED; satu pendaftaran aktif per periode.
  - Selisih data -> lewat student request data_change (KEL-BR-043) - ADA di kode.
- Kriteria penerimaan:
  - Given periode terbuka dan layak Then terkirim; Given ditutup Then 409.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student/graduation/registrations`; `[BELUM ADA] POST /api/v1/student/graduation/registrations`; `[BELUM ADA] POST /api/v1/student/graduation/registrations/{registration}/documents`; `[BELUM ADA] POST /api/v1/student/graduation/registrations/{registration}/submit`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P1
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: MOB-GRAD-02; MOB-SRV-05; MOB-SRV-10
- Sumber: D23 (must); D25; D05

#### MOB-GRAD-05 - Perbaiki pendaftaran yudisium yang dikembalikan/ditolak
- Peran: student
- Deskripsi: Melihat daftar item bermasalah terstruktur dan memperbaiki.
- Aturan bisnis:
  - Alasan dan daftar item bermasalah wajib (KEL-BR-042).
- Kriteria penerimaan:
  - Given dikembalikan Then item bermasalah tampil dan dapat diunggah ulang.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (endpoint belum didefinisikan)`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).; Kontrak endpoint belum ada.
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: MOB-GRAD-04
- Sumber: D23; D25

#### MOB-GRAD-06 - Lihat hasil kelulusan (keputusan, predikat, tanggal)
- Peran: student
- Deskripsi: Hasil yudisium: lulus, predikat, tanggal kelulusan.
- Aturan bisnis:
  - Predikat dari aturan konfigurable tanpa ambang default (KEL-BR-046); tanggal lulus per kebijakan, bukan waktu input (KEL-BR-047).
- Kriteria penerimaan:
  - Given keputusan final Then hasil tampil.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (tidak ada endpoint baca student eksplisit)`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).; Endpoint baca hasil student belum didefinisikan.
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: MOB-GRAD-04
- Sumber: D23; D02; D05

#### MOB-GRAD-07 - Lihat blokir yudisium (keuangan, perpustakaan, kemahasiswaan)
- Peran: student
- Deskripsi: Penghambat yudisium dari tunggakan, pinjaman, sanksi.
- Aturan bisnis:
  - Item keuangan tanpa nominal (KEL-BR-026); blok perpus/kemahasiswaan OD-22.
  - Student finance API ada (tagihan) sehingga sebagian status keuangan dapat dibaca terpisah, tetapi tidak ada endpoint clearance.
- Kriteria penerimaan:
  - Given tunggakan Then item keuangan gagal tampil tanpa nominal.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (bagian dari GET /student/graduation/eligibility)`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).; Data pinjaman perpustakaan tidak tersedia.
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: MOB-GRAD-02; MOB-KMHS-17
- Sumber: D04-keu-master; D05; D07; D16

#### MOB-GRAD-08 - Daftar wisuda
- Peran: student
- Deskripsi: Mendaftar wisuda pada sesi, memilih busana, pendamping.
- Aturan bisnis:
  - Basis final_decision default; satu wisuda per lulusan (WIS-BR-002); diblokir jika sanksi membatasi graduation; 409 NOT_ELIGIBLE_FOR_CEREMONY/SESSION_FULL; pendamping <= batas (WIS-BR-005).
- Kriteria penerimaan:
  - Given layak dan kuota ada Then terdaftar.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student/graduation/ceremonies`; `[BELUM ADA] POST /api/v1/student/graduation/ceremony-registrations`; `[BELUM ADA] GET /api/v1/student/graduation/ceremony-registrations`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: MOB-GRAD-06
- Sumber: D23; D25; D05; D01

#### MOB-GRAD-09 - Konfirmasi kehadiran wisuda dan kartu peserta QR
- Peran: student
- Deskripsi: Konfirmasi hadir dan menampilkan kartu QR.
- Aturan bisnis:
  - Check-in hanya peserta terkonfirmasi sesi sesuai (WIS-BR-008); QR berlaku hingga akhir hari wisuda; tanpa konfirmasi tidak dapat kursi.
- Kriteria penerimaan:
  - Given dikonfirmasi Then kartu QR tampil/offline.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] POST /api/v1/student/graduation/ceremony-registrations/{registration}/confirm`; `[BELUM ADA] GET /api/v1/student/graduation/ceremony-registrations/{registration}/card`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: MOB-GRAD-08; Flutter: qr_flutter
- Sumber: D23; D25

#### MOB-GRAD-10 - Bayar biaya wisuda
- Peran: student
- Deskripsi: Tagihan wisuda via keuangan.
- Aturan bisnis:
  - Tagihan dibuat modul keuangan; student finance API ada, tetapi tidak ada jenis tagihan wisuda dari modul kelulusan (D04 menyebut modul "belum ada").
- Kriteria penerimaan:
  - Given tagihan wisuda Then muncul di tagihan mahasiswa.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (memanfaatkan /api/v1/student/finance/* bila tagihan dibuat)`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).; Integrasi tagihan wisuda belum ada; payment gateway belum ada.
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: payment gateway; MOB-GRAD-08
- Sumber: D04-keu-master; D25#Wisuda registration, payment

#### MOB-GRAD-11 - Tinjau data buku wisuda (masa koreksi)
- Peran: student
- Deskripsi: Memeriksa entri buku wisuda.
- Aturan bisnis:
  - Entri dari snapshot resmi (WIS-BR-009); tanpa NIK/alamat/telepon/email; foto hanya dengan persetujuan (WIS-BR-011).
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (tidak ada endpoint student)`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D23

#### MOB-GRAD-12 - Unduh dokumen kelulusan (SK, transkrip, ijazah, SKL)
- Peran: student
- Deskripsi: Mengunduh dokumen resmi kelulusan.
- Aturan bisnis:
  - Rancangan D25. Catatan: transkrip PDF sudah ada di kode lewat GET /api/v1/student/documents/transcript (group akademik).
- Kriteria penerimaan:
  - Given dokumen terbit Then dapat diunduh.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (tidak ada endpoint graduation documents)`; `[BELUM ADA] (ada, group akademik) GET /api/v1/student/documents/transcript`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `build_contract_pending_backend`
- Dependensi: MOB-SRV-07
- Sumber: D25; D03#SKPI and verifiable official graduation documents
- Catatan: Sebagian (transkrip) available di endpoint lain.

#### MOB-GRAD-13 - SKPI: lihat dan ajukan capaian tambahan
- Peran: student
- Deskripsi: Melihat capaian terkumpul dan menambah capaian dengan bukti.
- Aturan bisnis:
  - Bukti wajib untuk capaian SKPI-native (V-SKPI-03); judul <=200, deskripsi <=500; capaian belum terverifikasi tidak pernah terbit (SKPI-BR-001).
- Kriteria penerimaan:
  - Given capaian dengan bukti Then masuk antrean verifikasi.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student/skpi/achievements`; `[BELUM ADA] POST /api/v1/student/skpi/achievements`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D23; D02; D03; D05; D07

#### MOB-GRAD-14 - SKPI: terjemahan Inggris dan pilih capaian ditampilkan
- Peran: student
- Deskripsi: Review terjemahan dan seleksi capaian.
- Aturan bisnis:
  - Terjemahan wajib direview sebelum draft dikirim (SKPI-BR-005); batas per kategori (SKPI-BR-008); no_translate untuk nama/institusi.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] PUT /api/v1/student/skpi/achievements/{achievement}`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D23

#### MOB-GRAD-15 - SKPI: pratinjau draft, konfirmasi data, unduh SKPI terbit
- Peran: student
- Deskripsi: Mengecek draft, mengakui kebenaran, mengunduh SKPI.
- Aturan bisnis:
  - Terbit hanya setelah yudisium final lulus (SKPI-BR-003); data akademik dari snapshot keputusan (SKPI-BR-011); 409 TRANSLATION_NOT_REVIEWED.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/student/skpi/document`; `[BELUM ADA] POST /api/v1/student/skpi/document`; `[BELUM ADA] POST /api/v1/student/skpi/document/acknowledge`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: PortalGraduationPage.tsx (mock/static, tidak ter-route)
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D23; D25

#### MOB-GRAD-16 - Kaprodi/Dekan: setujui draf SKPI
- Peran: lecturer
- Deskripsi: Pejabat dosen menyetujui/menolak draft SKPI.
- Aturan bisnis:
  - Permission skpi_documents.approve/reject; scope prodi/fakultas; tidak ada UI mobile terdefinisi.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] POST /api/v1/skpi/documents/{document}/submit (kontrak dokumen)`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).; Role lecturer belum memiliki permission terkait.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D23

#### MOB-GRAD-17 - Dosen PA/pembimbing: lihat kelayakan dan status yudisium mahasiswa bimbingan
- Peran: lecturer, advisor
- Deskripsi: Ringkasan kelayakan dan pendaftaran yudisium mahasiswa bimbingan/dibimbing.
- Aturan bisnis:
  - Permission graduation_eligibility.read scope wali/pembimbing; lingkup via theses.supervisor_lecturer_id.
- Kriteria penerimaan:
  - Given PA When buka anak bimbingan Then ringkasan tampil; selain bimbingan 403.
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/graduation/students/{student}/eligibility`; `[BELUM ADA] GET /api/v1/graduation/students/{student}/eligibility/history`; `[BELUM ADA] GET /api/v1/graduation/yudisium-registrations`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).; UX dosen belum didefinisikan.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D23; D24

#### MOB-GRAD-18 - Profil alumni (swalayan karier/pekerjaan)
- Peran: student
- Deskripsi: Alumni memperbarui data pekerjaan.
- Aturan bisnis:
  - employment_status nullable; unknown != belum_bekerja (TRACER-BR-012); pendapatan opsional terenkripsi (TRACER-BR-016).
  - Kode: GET /alumni hanya admin (alumni.read), read-only.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/me/alumni/profile`; `[BELUM ADA] PUT /api/v1/me/alumni/profile`; `[BELUM ADA] (ada, admin-only) GET /api/v1/alumni`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D23; C5
- Catatan: Peran alumni (pasca-lulus) belum didefinisikan di app mahasiswa/dosen.

#### MOB-GRAD-19 - Isi tracer study (tautan token atau portal alumni)
- Peran: student
- Deskripsi: Mengisi kuesioner tracer study.
- Aturan bisnis:
  - Satu respons per target per periode (TRACER-BR-007); Idempotency-Key; 409 ALREADY_SUBMITTED; validasi hanya pertanyaan terlihat (TRACER-BR-019); edit setelah kirim default false.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/public/surveys/{token}`; `[BELUM ADA] PUT /api/v1/public/surveys/{token}/draft`; `[BELUM ADA] POST /api/v1/public/surveys/{token}/submit`; `[BELUM ADA] GET /api/v1/me/alumni/surveys`; `[BELUM ADA] GET /api/v1/me/alumni/surveys/{response}`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D23; D25; D05; D07; D03

#### MOB-GRAD-20 - Profil karier (CV, keahlian, pengalaman) dan visibilitas
- Peran: student
- Deskripsi: Profil karier pribadi, default privat, persetujuan visibilitas.
- Aturan bisnis:
  - Default visibilitas private; ekspektasi gaji privat/terenkripsi; nama dari data mahasiswa tidak dapat diubah; satu profil per user per tenant; KARIR-BR-008.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET/PUT /api/v1/me/career/profile`; `[BELUM ADA] POST/PUT/DELETE /api/v1/me/career/profile/items[/{item}]`; `[BELUM ADA] PUT /api/v1/me/career/profile/visibility`
- Celah backend: Seluruh modul Karirlink belum ada (tidak ada /me/career/*).
- Status web: none (mock one/pages/LibraryCareer.tsx)
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D24

#### MOB-GRAD-21 - Kelola CV/dokumen dan lihat log akses CV
- Peran: student
- Deskripsi: Unggah/buat CV PDF dan lihat siapa yang melihat.
- Aturan bisnis:
  - PDF <=5 MB; retensi tidak aktif 24 bulan lalu hapus setelah 30 hari peringatan; snapshot terkirim independen (KARIR-BR-020); setiap unduhan dicatat.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET/POST/DELETE /api/v1/me/career/documents`; `[BELUM ADA] POST /api/v1/me/career/documents/generate-cv`; `[BELUM ADA] GET /api/v1/me/career/access-log`
- Celah backend: Modul Karirlink belum ada.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D24

#### MOB-GRAD-22 - Telusuri lowongan kerja
- Peran: student
- Deskripsi: Daftar dan detail lowongan.
- Aturan bisnis:
  - Entitlement career.marketplace; lowongan published dan belum lewat batas.
  - Web one/pages/LibraryCareer.tsx adalah mock/static.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/career/jobs`; `[BELUM ADA] GET /api/v1/career/jobs/{posting}`; `[BELUM ADA] POST /api/v1/career/abuse-reports`
- Celah backend: Modul Karirlink belum ada.
- Status web: mock (one/pages/LibraryCareer.tsx)
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D24; D02; D05; D07; D03

#### MOB-GRAD-23 - Lamar lowongan dan lacak lamaran saya
- Peran: student
- Deskripsi: Melamar dengan dokumen dan menjawab skrining, melacak status, menarik.
- Aturan bisnis:
  - Satu lamaran aktif per lowongan (KARIR-BR-005); batas 20/hari; CV PDF <=5 MB; status submitted, reviewed, shortlisted, interview, offered, accepted, rejected, withdrawn.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] GET /api/v1/career/jobs/{posting}/application-eligibility`; `[BELUM ADA] POST /api/v1/career/jobs/{posting}/applications`; `[BELUM ADA] GET /api/v1/me/career/applications`; `[BELUM ADA] POST /api/v1/me/career/applications/{application}/withdraw`
- Celah backend: Modul Karirlink belum ada.
- Status web: mock (one/pages/LibraryCareer.tsx)
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D24; D25

#### MOB-GRAD-24 - Persetujuan rekomendasi lulusan (opt-in alumni)
- Peran: student
- Deskripsi: Opt-in dan consent pembagian data ke mitra.
- Aturan bisnis:
  - Dua lapis consent; data tak dibagi sebelum consent spesifik (KARIR-BR-010); paket Professional+.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] PUT /api/v1/me/career/profile/recommendation-opt-in`; `[BELUM ADA] GET /api/v1/me/career/recommendation-consents`; `[BELUM ADA] POST .../{consent}/grant|decline|revoke`
- Celah backend: Modul Karirlink belum ada.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P3
- Cakupan rekomendasi: `defer`
- Dependensi: -
- Sumber: D24

#### MOB-GRAD-25 - Notifikasi yudisium, wisuda, SKPI, tracer, karier
- Peran: student
- Deskripsi: Notifikasi kejadian kelulusan dan karier.
- Aturan bisnis:
  - Rancangan D25; tracer via email dengan unsubscribe satu klik; infrastruktur /notifications ada namun event belum dikirim.
- Kriteria penerimaan:
  - —
- Status backend: **missing**
- Endpoint: `[BELUM ADA] (pakai GET /api/v1/notifications)`
- Celah backend: Modul Kelulusan (yudisium/wisuda/SKPI/tracer) belum dibangun (STATUS §11, kemahasiswaan-kelulusan/10-18).; Push belum ada.
- Status web: none
- Status mobile saat ini: none
- Prioritas: P2
- Cakupan rekomendasi: `defer`
- Dependensi: MOB-SRV-12
- Sumber: D25-kk-27-34

### Belum terselesaikan

- Apakah StudentRequestService mengirim notifikasi database saat keputusan (disetujui/ditolak/dikembalikan)? Belum terverifikasi di kode.
- Bagaimana mahasiswa melihat status "returned" dan mengajukan ulang: perlu ekspos approval_request_id/status returned di StudentRequestResource.
- Apakah dosen/PA mendapat permission approval-request-steps dan workflow pengajuan mahasiswa akan menyertakan langkah dosen wali (rekomendasi cuti)?
- Mahasiswa berstatus inactive diblokir login: aktif kembali dari inactive tidak bisa lewat app (hanya dari status leave).
- Apakah PortalRequestsPage mendukung unggah lampiran; kontrak endpoint beberapa fitur (laporan pelanggaran mahasiswa, hasil kelulusan, perbaikan pendaftaran yudisium) belum didefinisikan di dokumen.
- Peran alumni (pasca lulus: tracer, profil alumni, karier) tidak masuk lingkup student/lecturer app; perlu keputusan produk.

## Matriks Fitur G7 — Skripsi/Magang, Perpustakaan, Pengumuman/Notifikasi, Dosen/SDM, Lainnya

Aplikasi: Civitas One Android (Flutter) untuk mahasiswa, dosen, dan dosen PA. Status diverifikasi terhadap kode (`app/Modules/*/Routes/api.php`); kode menang atas dokumen.

Jumlah baris: 85

### Skripsi, Magang, dan MBKM (THES)

Skripsi/tugas akhir, magang/PKL/KKN, dan MBKM. Di kode hanya ada modul Thesis dan Internship read-only untuk admin/staf (permission theses.read, internships.read yang tidak dimiliki mahasiswa maupun dosen, tanpa pembatasan 'milik sendiri'). Tidak ada endpoint bimbingan, seminar, sidang, logbook, maupun MBKM. Halaman web mahasiswa (PortalThesisAdvisingPage) berisi data mock. Semua baris yang penting ditandai 'build_contract_pending_backend' (entri berlabel, tanpa data palsu).

#### MOB-THES-01 — Lihat skripsi/tugas akhir saya (judul, jenis, status)

- **Peran:** student
- **Deskripsi:** Mahasiswa melihat record skripsi/tesis miliknya: judul, jenis (skripsi/tesis), status tahap, pembimbing.
- **Aturan bisnis:**
  - Hanya record milik mahasiswa login (scoping by student_id)
  - Status mengikuti enum thesis (prototipe web: proposal, bimbingan, seminar_proposal, penelitian, sidang, selesai)
- **Kriteria penerimaan/pengujian:**
  - Given mahasiswa punya skripsi, When membuka menu Skripsi, Then judul/status/pembimbing tampil
  - Given belum ada skripsi, Then empty state 'Belum ada tugas akhir'
  - Given mahasiswa lain, Then tidak dapat melihat record orang lain (403/404)
- **Status backend:** `missing`
- **Endpoint:** `(admin saja) GET /api/v1/theses, GET /api/v1/theses/{id}`
- **Celah backend:** Modul Thesis hanya read-only untuk admin (GET /theses, /theses/{id}; permission theses.read tidak dimiliki student/lecturer; tanpa scoping 'milik sendiri'). Tidak ada tabel/endpoint bimbingan, seminar, sidang.; Perlu endpoint mis. GET /api/v1/student/thesis (own, tanpa permission admin)
- **Status web:** mock (pages/portal/PortalThesisAdvisingPage.tsx — data mock, tidak terhubung API)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: modul tugas akhir mahasiswa
- **Sumber:** RANCANGAN_FLUTTER_CIVITAS_ONE.md (read thesis status); docs/STATUS-IMPLEMENTASI.md §3; code: app/Modules/Thesis
- **Catatan:** Tampilkan entri berlabel 'Menunggu backend' tanpa data palsu.

#### MOB-THES-02 — Ajukan judul/proposal skripsi

- **Peran:** student
- **Deskripsi:** Mahasiswa mengajukan judul dan proposal tugas akhir untuk disetujui koordinator/kaprodi dan penetapan pembimbing.
- **Aturan bisnis:**
  - Syarat kelayakan (SKS minimal, status aktif) belum terdefinisi di dokumen
  - Alur: diajukan -> disetujui/ditolak -> pembimbing ditetapkan (rancangan)
- **Kriteria penerimaan/pengujian:**
  - Given syarat terpenuhi, When mengirim judul+abstrak, Then status 'diajukan' dan notifikasi ke kaprodi (rancangan)
- **Status backend:** `missing`
- **Endpoint:** `(belum ada) mis. POST /api/v1/student/thesis/proposals`
- **Celah backend:** Endpoint tulis tugas akhir tidak ada; Kontrak API tidak didefinisikan rinci di dokumen
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-THES-01; Backend tugas akhir; Upload berkas: POST /file-uploads (ada)
- **Sumber:** RANCANGAN-APLIKASI.md Tahap 6; docs/academic-learning (D14 §thesis proposal & guidance: read Ada; rest Baru)

#### MOB-THES-03 — Lihat pembimbing dan jadwal tahapan tugas akhir

- **Peran:** student, lecturer
- **Deskripsi:** Menampilkan dosen pembimbing 1/2, status tahapan, dan jadwal seminar/sidang yang telah ditetapkan.
- **Aturan bisnis:**
  - Multi-pembimbing (blueprint), penguji ditetapkan koordinator
- **Kriteria penerimaan/pengujian:**
  - Given pembimbing sudah ditetapkan, Then nama dan peran tampil di detail skripsi
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada tabel/endpoint supervisor, jadwal seminar/sidang
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-THES-01
- **Sumber:** docs/STATUS-IMPLEMENTASI.md §3 (multi-pembimbing, penguji = belum); RANCANGAN-AKUN-DOSEN.md

#### MOB-THES-04 — Catat bimbingan skripsi (logbook) dan ajukan sesi bimbingan

- **Peran:** student
- **Deskripsi:** Mahasiswa mencatat pertemuan bimbingan (tanggal, topik, hasil, lampiran) dan mengajukan jadwal sesi ke pembimbing.
- **Aturan bisnis:**
  - Logbook diverifikasi pembimbing; jumlah minimal bimbingan sebelum seminar (nilai belum terdefinisi)
- **Kriteria penerimaan/pengujian:**
  - Given pembimbing aktif, When menyimpan catatan bimbingan, Then status 'menunggu verifikasi'
  - Given pembimbing mengesahkan, Then status 'disahkan' dan tampil di riwayat
- **Status backend:** `missing`
- **Endpoint:** `(belum ada) mis. GET/POST /api/v1/student/thesis/supervisions`
- **Celah backend:** Tidak ada tabel supervision session/logbook
- **Status web:** mock (pages/portal/PortalThesisAdvisingPage.tsx, 3 dari 8 sesi mock)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-THES-03
- **Sumber:** RANCANGAN_FLUTTER_CIVITAS_ONE.md (thesis supervision logbook); RANCANGAN-APLIKASI.md Tahap 6; SEVIMA (semua paket): log bimbingan TA

#### MOB-THES-05 — Daftar mahasiswa bimbingan TA dan verifikasi logbook bimbingan

- **Peran:** lecturer
- **Deskripsi:** Dosen pembimbing melihat daftar mahasiswa bimbingan skripsi, membaca logbook, mengesahkan/menolak catatan, menjadwalkan sesi.
- **Aturan bisnis:**
  - Hanya mahasiswa yang dibimbing dosen tsb (object-level)
- **Kriteria penerimaan/pengujian:**
  - Given dosen pembimbing, When membuka 'Bimbingan TA', Then hanya mahasiswa bimbingannya tampil
  - Given logbook menunggu, When disahkan, Then mahasiswa menerima notifikasi
- **Status backend:** `missing`
- **Endpoint:** `(admin) GET /api/v1/theses (theses.read tidak diberikan ke dosen)`
- **Celah backend:** Dosen tidak punya theses.read; tidak ada filter 'pembimbing = saya'; Tidak ada endpoint logbook/sesi
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-THES-04
- **Sumber:** RANCANGAN-AKUN-DOSEN.md (bimbingan skripsi); docs/STATUS-IMPLEMENTASI.md §7 (bimbingan skripsi, jadwal sidang = belum); D15 §thesis supervision (lecturer): rancangan

#### MOB-THES-06 — Pendaftaran seminar proposal dan sidang skripsi

- **Peran:** student
- **Deskripsi:** Mahasiswa mendaftar seminar proposal/sidang, melihat jadwal, ruang, penguji, dan mengunggah berkas persyaratan.
- **Aturan bisnis:**
  - Syarat: bimbingan minimal, persetujuan pembimbing (rancangan)
- **Kriteria penerimaan/pengujian:**
  - Given syarat terpenuhi, When mendaftar, Then jadwal/penguji tampil setelah ditetapkan panitia
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada tabel/endpoint seminar, sidang, penguji
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-THES-03
- **Sumber:** RANCANGAN-APLIKASI.md Tahap 6; D13 ERD (detail tables Baru); STATUS §3

#### MOB-THES-07 — Penilaian seminar/sidang, revisi, dan berita acara oleh dosen

- **Peran:** lecturer
- **Deskripsi:** Dosen pembimbing/penguji memberi nilai (rubrik) pada seminar/sidang, mencatat revisi, dan mengesahkan hasil.
- **Aturan bisnis:**
  - Rubrik nilai & bobot pembimbing/penguji belum terdefinisi
- **Kriteria penerimaan/pengujian:**
  - Given jadwal sidang, When penguji mengisi nilai, Then nilai tersimpan dan terkunci setelah disahkan
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada endpoint
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-THES-05; MOB-THES-06
- **Sumber:** RANCANGAN-AKUN-DOSEN.md; STATUS §7
- **Catatan:** Defer: butuh modul besar dan keputusan rubrik.

#### MOB-THES-08 — Lihat data magang/PKL/KKN saya

- **Peran:** student
- **Deskripsi:** Mahasiswa melihat record magang: institusi, jenis program, periode, status.
- **Aturan bisnis:**
  - Hanya record milik sendiri
- **Kriteria penerimaan/pengujian:**
  - Given ada record magang, Then daftar+status tampil
  - Given tidak ada, Then empty state
- **Status backend:** `missing`
- **Endpoint:** `(admin) GET /api/v1/internships, /internships/{id} (internships.read)`
- **Celah backend:** Tidak ada akses student/lecturer, tidak ada scoping milik sendiri; Perlu mis. GET /api/v1/student/internships
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: endpoint student internships
- **Sumber:** docs/STATUS-IMPLEMENTASI.md §10 (Magang/KKN ✅ read-only); BLACK-BOX-TESTING.md §18

#### MOB-THES-09 — Pendaftaran dan penempatan magang/PKL/KKN

- **Peran:** student
- **Deskripsi:** Mahasiswa mendaftar program magang/KKN, memilih institusi, dan memantau persetujuan penempatan.
- **Aturan bisnis:**
  - Persetujuan kaprodi/koordinator (rancangan)
- **Kriteria penerimaan/pengujian:**
  - Given periode pendaftaran dibuka, When mengajukan, Then status 'diajukan'
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Endpoint tulis tidak ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Backend: modul magang (tulis)
- **Sumber:** RANCANGAN-APLIKASI.md Tahap 6 (Magang, KKN); STATUS §10

#### MOB-THES-10 — Logbook, presensi, laporan, dan sertifikat magang

- **Peran:** student
- **Deskripsi:** Mahasiswa mengisi logbook harian magang, presensi, mengunggah laporan akhir dan mengunduh sertifikat.
- **Aturan bisnis:**
  - Logbook diverifikasi dosen pembimbing magang
- **Kriteria penerimaan/pengujian:**
  - Given magang aktif, When logbook diisi, Then tersimpan menunggu verifikasi
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada endpoint
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-THES-09
- **Sumber:** RANCANGAN-APLIKASI.md Tahap 6

#### MOB-THES-11 — Bimbing dan nilai magang (dosen pembimbing)

- **Peran:** lecturer
- **Deskripsi:** Dosen pembimbing magang memverifikasi logbook dan memberi nilai akhir magang.
- **Aturan bisnis:**
  - Object-level: hanya mahasiswa bimbingan
- **Kriteria penerimaan/pengujian:**
  - Given logbook masuk, When dosen mengesahkan, Then status berubah dan mahasiswa dinotifikasi
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada endpoint
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-THES-10
- **Sumber:** RANCANGAN-APLIKASI.md Tahap 6

#### MOB-THES-12 — Katalog dan pendaftaran program MBKM

- **Peran:** student
- **Deskripsi:** Mahasiswa menelusuri katalog MBKM (magang, studi independen, pertukaran) dan mendaftar.
- **Aturan bisnis:**
  - Tahap 7 pada roadmap; IKU Enterprise only (SIAKAD doc)
- **Kriteria penerimaan/pengujian:**
  - Given katalog terbit, When mahasiswa mendaftar, Then status 'diajukan'
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Modul MBKM belum ada (tidak ada tabel/route)
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** RANCANGAN-APLIKASI.md Tahap 6/7 (MBKM); D13/D14 (MBKM Baru, planned); SEVIMA Growth tier

#### MOB-THES-13 — Logbook MBKM mahasiswa dan peninjauan dosen supervisor

- **Peran:** student, lecturer
- **Deskripsi:** Mahasiswa mengisi logbook kegiatan MBKM; dosen supervisor meninjau dan menilai.
- **Aturan bisnis:**
  - Belum terdefinisi rinci
- **Kriteria penerimaan/pengujian:**
  - Given kegiatan aktif, When logbook diisi, Then supervisor dapat meninjau
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Modul MBKM belum ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-THES-12
- **Sumber:** D14 (MBKM logbook review & assessment: planned); D15

#### MOB-THES-14 — Konversi mata kuliah MBKM ke SKS

- **Peran:** student
- **Deskripsi:** Mahasiswa mengajukan dan melihat hasil konversi kegiatan MBKM ke mata kuliah/SKS.
- **Aturan bisnis:**
  - Persetujuan kaprodi; hasil masuk transkrip (rancangan)
- **Kriteria penerimaan/pengujian:**
  - Given kegiatan selesai, When konversi disetujui, Then SKS tampil di transkrip
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-THES-12
- **Sumber:** SEVIMA Growth tier; D07

#### MOB-THES-15 — Pertukaran pelajar antar program studi

- **Peran:** student
- **Deskripsi:** Pendaftaran pertukaran pelajar (benchmark SEVIMA).
- **Aturan bisnis:**
  - Tidak terdefinisi di CivitasOne
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada rancangan CivitasOne
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** SEVIMA Growth tier (D05)
- **Catatan:** Belum ada rancangan internal.

### Perpustakaan (LIB)

Perpustakaan. Backend hanya GET /books (admin, books.read); tidak ada endpoint pinjaman milik sendiri, dan GET /books/{id} membocorkan pinjaman semua mahasiswa sehingga tidak boleh dibuka apa adanya. Prototipe web (one/pages/LibraryCareer.tsx) adalah mock statis.

#### MOB-LIB-01 — Cari katalog perpustakaan

- **Peran:** student, lecturer
- **Deskripsi:** Pencarian buku berdasarkan judul/penulis/ISBN, filter kategori, lihat ketersediaan eksemplar.
- **Aturan bisnis:**
  - Hanya buku is_active
  - Hasil publik tidak boleh memuat data pinjaman orang lain
- **Kriteria penerimaan/pengujian:**
  - Given kata kunci, When mencari, Then daftar buku + stok tersedia
  - Given buku tidak aktif, Then tidak muncul
- **Status backend:** `partial`
- **Endpoint:** `(admin) GET /api/v1/books?search=&filter[category]=&filter[is_active]=`; `GET /api/v1/books/{id}`
- **Celah backend:** Modul Library hanya GET /books & /books/{id} untuk admin (books.read, tidak dimiliki student/lecturer). Tidak ada my-loans; BookResource show menyertakan pinjaman semua mahasiswa (kebocoran data bila dibuka apa adanya).; Perlu route/permission baca katalog untuk student & lecturer dengan resource tanpa daftar pinjaman
- **Status web:** mock (one/pages/LibraryCareer.tsx — static); admin pages/library
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: permission books.read untuk student/lecturer + resource publik
- **Sumber:** docs/STATUS-IMPLEMENTASI.md §10 (Perpustakaan ✅ katalog & pinjaman read-only); BLACK-BOX-TESTING.md §17; RANCANGAN-APLIKASI.md Tahap 6

#### MOB-LIB-02 — Pinjaman aktif dan riwayat peminjaman saya

- **Peran:** student, lecturer
- **Deskripsi:** Daftar buku sedang dipinjam (jatuh tempo) dan riwayat peminjaman.
- **Aturan bisnis:**
  - Hanya pinjaman milik sendiri
- **Kriteria penerimaan/pengujian:**
  - Given ada pinjaman, Then judul/tgl pinjam/jatuh tempo tampil
- **Status backend:** `missing`
- **Endpoint:** `(belum ada) mis. GET /api/v1/student/library/loans`
- **Celah backend:** Tidak ada endpoint my-loans
- **Status web:** mock (one/pages/LibraryCareer.tsx tab borrowed/history)
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-LIB-01
- **Sumber:** RANCANGAN-APLIKASI.md Tahap 6; STATUS §10

#### MOB-LIB-03 — Perpanjang pinjaman dan reservasi buku

- **Peran:** student, lecturer
- **Deskripsi:** Perpanjangan pinjaman (batas kali) dan reservasi buku yang sedang dipinjam.
- **Aturan bisnis:**
  - Batas perpanjangan belum terdefinisi
- **Kriteria penerimaan/pengujian:**
  - Given belum jatuh tempo & tidak ada reservasi, When perpanjang, Then jatuh tempo bertambah
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada endpoint tulis (modul read-only)
- **Status web:** mock (one/ LibraryCareer borrow/reserve, state lokal)
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-LIB-02
- **Sumber:** RANCANGAN-APLIKASI.md Tahap 6

#### MOB-LIB-04 — Denda perpustakaan

- **Peran:** student, lecturer
- **Deskripsi:** Melihat denda keterlambatan dan statusnya; integrasi tagihan keuangan (rancangan).
- **Aturan bisnis:**
  - Tarif denda belum terdefinisi
- **Kriteria penerimaan/pengujian:**
  - Given pinjaman terlambat, Then denda terhitung dan tampil
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-LIB-02
- **Sumber:** STATUS §10 (denda); RANCANGAN-APLIKASI.md Tahap 6

#### MOB-LIB-05 — Kartu anggota digital dan surat bebas pustaka

- **Peran:** student, lecturer
- **Deskripsi:** Kartu anggota perpustakaan (QR) dan status bebas pustaka untuk yudisium/wisuda.
- **Aturan bisnis:**
  - Bebas pustaka bila tanpa pinjaman/denda
- **Kriteria penerimaan/pengujian:**
  - Given tak ada tunggakan, Then status 'bebas pustaka' tampil
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-LIB-02
- **Sumber:** RANCANGAN-APLIKASI.md Tahap 6; D08 (RENCANA)

#### MOB-LIB-06 — Perpustakaan digital, e-journal, dan integrasi SLiMS

- **Peran:** student, lecturer
- **Deskripsi:** Akses koleksi digital/e-journal dan integrasi SLiMS (benchmark SEVIMA Premium+).
- **Aturan bisnis:**
  - Tidak ada di rancangan internal
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada rancangan/integrasi
- **Status web:** mock (one/pages/LibraryCareer.tsx)
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** SEVIMA (D07); one/pages/LibraryCareer.tsx tab digital/e-journal (mock)

### Pengumuman dan Notifikasi (ANN)

Pengumuman dan notifikasi. Tersedia penuh untuk mahasiswa: feed pengumuman tertarget dengan penanda dibaca, serta pusat notifikasi (database channel) untuk semua peran. Belum ada: feed pengumuman dosen, push/FCM, deep link terstruktur, preferensi per event. Event notifikasi yang sudah terkirim: keuangan (keu.*), KRS, tugas, HR.

#### MOB-ANN-01 — Daftar pengumuman mahasiswa (filter, cari, pin)

- **Peran:** student
- **Deskripsi:** Feed pengumuman tertarget: semua/belum dibaca/program studi/fakultas/angkatan/semester/khusus mahasiswa, pencarian, pin di atas, jumlah belum dibaca.
- **Aturan bisnis:**
  - Urutan: pinned dulu lalu published_at desc
  - Hanya audience all/students; target_scope & tahun angkatan/semester dicocokkan dengan profil mahasiswa
  - filter=all|unread|study_program|faculty|admission_year|semester|students; search<=100; per_page 1..50 (default 10)
  - meta.unread_count disertakan
  - Wajib memiliki student ter-link (kode)
- **Kriteria penerimaan/pengujian:**
  - Given mahasiswa, When membuka Pengumuman, Then pinned tampil paling atas
  - Given filter unread, Then hanya belum dibaca
  - Given pengumuman prodi lain, Then tidak tampil
  - Pagination 10/halaman mengikuti meta
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/announcements`
- **Celah backend:** (tidak ada)
- **Status web:** pages/portal/PortalAnnouncementsPage.tsx (lengkap)
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Auth /login (sudah ada)
- **Sumber:** docs/STATUS-IMPLEMENTASI.md §6; D02 MVP #15; RANCANGAN-AKUN-MAHASISWA.md; Code: app/Modules/Announcement, app/Modules/Notification/Routes/api.php

#### MOB-ANN-02 — Detail pengumuman dan tandai dibaca

- **Peran:** student
- **Deskripsi:** Membuka detail pengumuman; otomatis menandai dibaca; badge belum-dibaca diperbarui.
- **Aturan bisnis:**
  - Idempoten
  - 404 'Data tidak ditemukan.' bila tidak ditargetkan ke mahasiswa
  - Kegagalan POST read diabaikan di web (UI tetap)
- **Kriteria penerimaan/pengujian:**
  - Given pengumuman belum dibaca, When dibuka, Then POST read dipanggil dan unread_count berkurang
  - Given pengumuman tidak ditargetkan, Then 404
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/student/announcements/{announcement}`; `POST /api/v1/student/announcements/{announcement}/read`
- **Celah backend:** (tidak ada)
- **Status web:** pages/portal/PortalAnnouncementsPage.tsx (modal detail)
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-ANN-01
- **Sumber:** docs/STATUS-IMPLEMENTASI.md §6 (tanda dibaca ✅); Code: app/Modules/Announcement, app/Modules/Notification

#### MOB-ANN-03 — Unduh lampiran pengumuman

- **Peran:** student
- **Deskripsi:** Mengunduh lampiran (PortalFileRef) dari pengumuman.
- **Aturan bisnis:**
  - Unduh via file-uploads dengan token pengguna
- **Kriteria penerimaan/pengujian:**
  - Given pengumuman berlampiran, When tap lampiran, Then file diunduh/dibuka
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/file-uploads/{id}/download`; `GET /api/v1/file-uploads/{id}/preview`
- **Celah backend:** Izin akses file lampiran untuk mahasiswa perlu diuji end-to-end (tidak diverifikasi)
- **Status web:** pages/portal/PortalAnnouncementsPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-ANN-02; Flutter: dio download + open_filex
- **Sumber:** code: AnnouncementResource.attachment; frontend PortalAnnouncementsPage

#### MOB-ANN-04 — Pengumuman untuk dosen dan dosen PA

- **Peran:** lecturer, advisor
- **Deskripsi:** Dosen/PA melihat pengumuman beraudience lecturers/staff/all yang ditargetkan ke fakultas/prodi.
- **Aturan bisnis:**
  - Audience lecturers/staff ada di data tetapi tak ada feed
- **Kriteria penerimaan/pengujian:**
  - Given dosen, When membuka Pengumuman, Then daftar sesuai audience
- **Status backend:** `missing`
- **Endpoint:** `(admin) GET /api/v1/announcements (permission announcements.read — tidak dimiliki dosen => 403)`
- **Celah backend:** Tidak ada feed dosen; perlu mis. GET /lecturer/announcements atau izin announcements.read + filter audience; Tidak ada read-tracking untuk dosen
- **Status web:** none (hanya mahasiswa)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: endpoint feed dosen
- **Sumber:** D01 (Fase 4); D02 MVP #15; D06 (BLACK-BOX-TESTING §21 read-only); C2/C3/C5 gaps
- **Catatan:** Menu dosen: tampil label 'Menunggu backend'.

#### MOB-ANN-05 — Buat pengumuman kelas oleh dosen

- **Peran:** lecturer
- **Deskripsi:** Dosen memposting pengumuman ke mahasiswa kelas yang diampu.
- **Aturan bisnis:**
  - Target hanya kelas yang diampu (object-level)
- **Kriteria penerimaan/pengujian:**
  - Given dosen pengampu, When memposting, Then mahasiswa kelas menerima notifikasi
- **Status backend:** `missing`
- **Endpoint:** `(admin) POST /api/v1/announcements (announcements.create; dosen tidak punya)`
- **Celah backend:** Tidak ada target 'kelas'; permission tidak diberikan ke dosen
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-ANN-04; Backend: target kelas pada pengumuman
- **Sumber:** D02; D13 ERD (Baru, planned)

#### MOB-ANN-06 — Pusat notifikasi (daftar, filter belum dibaca)

- **Peran:** student, lecturer, advisor
- **Deskripsi:** Daftar notifikasi in-app milik sendiri dengan filter belum dibaca dan pagination.
- **Aturan bisnis:**
  - Tanpa permission, semua user terautentikasi
  - unread=bool; per_page 1..50 (default 15)
  - Hanya channel database
  - event_key/message bisa null untuk notifikasi legacy (persetujuan/perangkat baru) -> fallback teks diperlukan
  - Item: {id,event_key,message,link,read_at,created_at}
- **Kriteria penerimaan/pengujian:**
  - Given ada notifikasi, Then terurut terbaru dengan penanda belum dibaca
  - Given message null, Then tampil teks fallback berdasarkan event_key/tipe
  - Given filter unread, Then hanya belum dibaca
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/notifications`
- **Celah backend:** Payload tidak bertipe (tak ada type/entity_id/route)
- **Status web:** pages/portal/PortalNotificationsPage.tsx (mahasiswa); dosen: tidak ada halaman
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Auth
- **Sumber:** D02 MVP #16; D03 STATUS §1; D31 (buggy: penyimpanan notifikasi PMB); Code: app/Modules/Announcement, app/Modules/Notification/Notification/Routes/api.php

#### MOB-ANN-07 — Badge jumlah notifikasi belum dibaca

- **Peran:** student, lecturer, advisor
- **Deskripsi:** Indikator jumlah belum dibaca di ikon lonceng/dashboard.
- **Aturan bisnis:**
  - unread_count juga ada di dashboard mahasiswa (notifications_unread)
- **Kriteria penerimaan/pengujian:**
  - Given 3 belum dibaca, Then badge menampilkan 3
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/notifications/unread-count`
- **Celah backend:** (tidak ada)
- **Status web:** pages/portal/PortalNotificationsPage.tsx / topbar
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-ANN-06
- **Sumber:** D01; code Notification

#### MOB-ANN-08 — Tandai notifikasi dibaca (satu dan semua)

- **Peran:** student, lecturer, advisor
- **Deskripsi:** Tandai satu notifikasi dibaca saat dibuka dan 'Tandai semua dibaca'.
- **Aturan bisnis:**
  - Satu: HTTP PATCH (bukan POST)
  - 404 'Notifikasi tidak ditemukan.' bila bukan milik sendiri
  - read-all mengembalikan unread_count 0
- **Kriteria penerimaan/pengujian:**
  - Given notifikasi dibuka, Then PATCH read dan badge berkurang
  - Given read-all, Then semua read_at terisi
- **Status backend:** `available`
- **Endpoint:** `PATCH /api/v1/notifications/{notification}/read`; `POST /api/v1/notifications/read-all`
- **Celah backend:** (tidak ada)
- **Status web:** pages/portal/PortalNotificationsPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P0
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-ANN-06
- **Sumber:** code Notification routes; C7 §20

#### MOB-ANN-09 — Deep link notifikasi ke layar aplikasi

- **Peran:** student, lecturer, advisor
- **Deskripsi:** Tap notifikasi membuka layar terkait (KRS, tagihan, tugas, persetujuan, dll).
- **Aturan bisnis:**
  - link = path web (mis. /portal/keuangan, /akademik/persetujuan-krs) -> aplikasi harus memetakan
  - event_key satu-satunya petunjuk semantik
- **Kriteria penerimaan/pengujian:**
  - Given link /portal/keuangan, When tap, Then membuka layar Keuangan
  - Given link tak dikenal, Then buka detail teks saja
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/notifications (link, event_key)`
- **Celah backend:** Tak ada payload terstruktur {type, entity_id, route}; perlu mapper link->route di aplikasi atau tambahan backend
- **Status web:** pages/portal/PortalNotificationsPage.tsx (navigasi ke link web)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-ANN-06; Router Flutter (go_router)
- **Sumber:** D01 (deep-link on tap); C5/C7 gaps

#### MOB-ANN-10 — Notifikasi push (FCM) per jenis dan deep link

- **Peran:** student, lecturer, advisor
- **Deskripsi:** Menerima push saat aplikasi tertutup, dengan preferensi per jenis dan deep link.
- **Aturan bisnis:**
  - Channel push belum diimplementasi (isImplemented hanya database & mail)
  - user_devices.push_token ada tetapi tak ada endpoint registrasi
- **Kriteria penerimaan/pengujian:**
  - Given token terdaftar, When event terjadi, Then push diterima dan tap membuka layar terkait
- **Status backend:** `missing`
- **Endpoint:** `(belum ada) mis. POST /api/v1/devices/push-token`; `GET /api/v1/devices (hanya baca)`
- **Celah backend:** Endpoint registrasi/rotasi token FCM; Pengirim push (FCM HTTP v1) + kredensial Firebase; Opt-in/out per channel (tabel preferensi ada)
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Infrastruktur: Firebase project + google-services.json; Flutter: firebase_messaging; MOB-ANN-09; Backend: registrasi token + pengirim FCM
- **Sumber:** D01 (PERLU KONFIRMASI); D03 STATUS §1 Belum push; C2/C4/C5/C6/C9
- **Catatan:** Bukan P0 karena belum ada backend; tampilkan notifikasi lewat polling inbox sementara.

#### MOB-ANN-11 — Preferensi saluran notifikasi

- **Peran:** student, lecturer, advisor
- **Deskripsi:** Mengaktifkan/menonaktifkan saluran notifikasi (database/mail/push/whatsapp/sms).
- **Aturan bisnis:**
  - Default opt-in; hanya baris yang disimpan dikembalikan
  - POST upsert: channel wajib, notification_type default '*', is_enabled wajib
  - Per-event (event_key) TIDAK berfungsi: pencocokan memakai nama kelas notifikasi; hanya '*' per channel efektif
  - Hanya database & mail diimplementasi
- **Kriteria penerimaan/pengujian:**
  - Given user menonaktifkan mail, When event, Then email tidak dikirim
  - Given toggle push, Then tersimpan walau channel belum aktif
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/notification-preferences`; `POST /api/v1/notification-preferences`
- **Celah backend:** Per-event preference tak efektif; Tidak ada endpoint daftar event/channel yang tersedia
- **Status web:** admin/settings (NotificationsPage); portal mahasiswa: tidak ada
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-ANN-06
- **Sumber:** D03 STATUS §1 (preferences ✅); D06 BLACK-BOX-TESTING §6; C5

#### MOB-ANN-12 — Notifikasi keuangan mahasiswa (tagihan, bayar, cekal, UKT, refund)

- **Peran:** student
- **Deskripsi:** Notifikasi in-app untuk event keuangan: invoice_issued, payment_succeeded, proof_rejected, payment_reversed, deposit_credited, installment_decided, penalty_issued, aid_decided, ukt_decided, refund_status, block_imposed/revoked, exemption_decided, due_reminder.
- **Aturan bisnis:**
  - Hanya channel database (link default /portal/keuangan); tanpa email/push
  - Dokumen desain menyebut sebagian 'design only'; kode: FinanceNotifier sudah ada (kode menang)
- **Kriteria penerimaan/pengujian:**
  - Given tagihan terbit, Then notifikasi muncul di inbox
  - Given pembayaran sukses, Then notifikasi payment_succeeded
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/notifications`
- **Celah backend:** Push & email tidak ada; Pengingat jatuh tempo bergantung scheduler (belum diverifikasi aktif)
- **Status web:** pages/portal/PortalNotificationsPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-ANN-06; MOB-ANN-08; MOB-ANN-09
- **Sumber:** docs/keuangan (D17-D21); D30 manual (tersedia); C4-be-finance

#### MOB-ANN-13 — Notifikasi akademik mahasiswa (KRS, tugas, nilai tugas)

- **Peran:** student
- **Deskripsi:** Notifikasi in-app: student.krs_approved/rejected, student.assignment_published/graded, dst.
- **Aturan bisnis:**
  - Terkirim: KRS disetujui/ditolak, tugas terbit, nilai tugas
  - Belum ada: perubahan jadwal, nilai akhir (grade entry tanpa notifikasi), pengingat EDOM, notifikasi SP
- **Kriteria penerimaan/pengujian:**
  - Given KRS disetujui, Then notifikasi student.krs_approved muncul
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/notifications`
- **Celah backend:** Notifikasi: perubahan jadwal, nilai akhir, pengingat EDOM, SP belum ada
- **Status web:** pages/portal/PortalNotificationsPage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-ANN-06; MOB-ANN-09
- **Sumber:** D27 (design only); D10 (jadwal berubah: Baru); D12 (pengingat EDOM: design only); C3 §KRS/§grading

#### MOB-ANN-14 — Notifikasi dosen dan PA (permintaan persetujuan KRS, langkah persetujuan)

- **Peran:** lecturer, advisor
- **Deskripsi:** Notifikasi untuk dosen/PA: student.krs_review_requested, approval step assigned, perangkat baru; SP kelas (planned).
- **Aturan bisnis:**
  - Notifikasi persetujuan/perangkat baru legacy: message/event_key null
  - Notifikasi 'Kelas Saya' & pilihan kelas SP: planned
- **Kriteria penerimaan/pengujian:**
  - Given mahasiswa mengajukan KRS, Then PA menerima notifikasi student.krs_review_requested
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/notifications`
- **Celah backend:** Event jadwal/SP dosen belum ada; Halaman notifikasi web hanya untuk mahasiswa
- **Status web:** none (tidak ada halaman dosen)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-ANN-06; MOB-ANN-09
- **Sumber:** D26/D27 (planned); C3 §97; C5

#### MOB-ANN-15 — Notifikasi HR untuk dosen (keputusan pengajuan)

- **Peran:** lecturer, advisor
- **Deskripsi:** Notifikasi hr.request_decided / hr.request_returned.
- **Aturan bisnis:**
  - Channel database saja
  - Template hr.request_returned tidak ada di TEMPLATES (kemungkinan tidak terkirim; belum diverifikasi)
- **Kriteria penerimaan/pengujian:**
  - Given pengajuan disetujui, Then notifikasi 'Pengajuan ... telah disetujui' muncul
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/notifications`
- **Celah backend:** Notifikasi returned mungkin tidak terkirim; Tidak ada push
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-ANN-06; MOB-HR-14
- **Sumber:** C6 §4; D05 Fase 3

#### MOB-ANN-16 — Notifikasi kemahasiswaan, kelulusan, beasiswa, karier, dan SP

- **Peran:** student, advisor
- **Deskripsi:** Notifikasi aktivitas/poin, disiplin, beasiswa, yudisium, wisuda, SKPI, tracer, karier, surat terbit, SP.
- **Aturan bisnis:**
  - Semua event key masih rancangan; modul sumber belum dibangun (kecuali surat approved)
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Event & modul belum ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Modul kemahasiswaan/kelulusan/SP
- **Sumber:** D22-D26 (design only); D29 (mahasiswa baru terdaftar: could)
- **Catatan:** Ditangani saat modul sumber tersedia; otomatis lewat pusat notifikasi.

#### MOB-ANN-17 — Ringkasan harian PA untuk mahasiswa bimbingan tercekal

- **Peran:** advisor, lecturer
- **Deskripsi:** Digest harian mahasiswa bimbingan yang kena cekal akademik/keuangan.
- **Aturan bisnis:**
  - Rancangan 13 Okt 2026
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada job digest/endpoint
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D21 (design only)

#### MOB-ANN-18 — Pengumuman massal via email/SMS/WhatsApp

- **Peran:** student
- **Deskripsi:** Siaran pengumuman akademik via email/SMS/WA (benchmark SEVIMA).
- **Aturan bisnis:**
  - Email diimplementasi; SMS/WA belum (channel isImplemented false)
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `partial`
- **Endpoint:** (tidak ada)
- **Celah backend:** Dikelola admin; tidak ada fungsi di sisi aplikasi mahasiswa
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D05, D07, D10, D16
- **Catatan:** Bukan fitur layar mobile. Backend partial tetapi bukan layar mobile (siaran dikirim admin; hasil tampil sebagai email).

#### MOB-ANN-19 — Pusat pesan dengan staf PMB dan notifikasi pendaftar

- **Peran:** student, lecturer
- **Deskripsi:** Percakapan pendaftar-staf PMB dan notifikasi pendaftar (jam sepi 21:00-06:00 untuk SMS/WA).
- **Aturan bisnis:**
  - Fitur untuk pendaftar (applicant), bukan mahasiswa/dosen
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Autentikasi pendaftar terpisah (/pmb/applicant/*)
- **Status web:** frontend/src/pmb (portal pendaftar)
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D31 manual; D29
- **Catatan:** Di luar cakupan aplikasi mahasiswa/dosen.

### Dosen dan SDM (HR)

Profil dosen dan SDM mandiri. Tersedia: /lecturer-profile (lihat + ubah telepon, web lengkap) dan /me/hr/* (profil SDM, cuti, pengajuan HR; backend lengkap tanpa UI web). Belum ada (hanya lewat /hr/* admin): riwayat pendidikan/karier/dokumen/pelatihan/kinerja/aktivitas penelitian, presensi pegawai, BKD, payroll, dll. Peran dosen PA (academic_advisor) memiliki hr_self_service.* yang sama.

#### MOB-HR-01 — Profil dosen mandiri (lihat)

- **Peran:** lecturer
- **Deskripsi:** Melihat data dosen: nama, NIDN, NIP, fakultas, email, telepon, status kepegawaian, jabatan fungsional, pendidikan terakhir, mulai bertugas, status aktif.
- **Aturan bisnis:**
  - 404 bila user tidak ter-link ke lecturers
  - Data kepegawaian dikelola Bagian SDM (read-only)
- **Kriteria penerimaan/pengujian:**
  - Given dosen login, Then data profil tampil
  - Given akun tak ter-link dosen, Then pesan 'profil tidak ditemukan'
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/lecturer-profile`
- **Celah backend:** Tidak memuat serdos/NIDK/jabatan akademik (ada di /me/hr/profile)
- **Status web:** pages/lecturer/LecturerProfilePage.tsx (lengkap)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Auth
- **Sumber:** D03 §3; D08 (TERSEDIA); C3; C7 §Profil Saya

#### MOB-HR-02 — Ubah nomor telepon dosen

- **Peran:** lecturer
- **Deskripsi:** Dosen mengedit nomor telepon kontak.
- **Aturan bisnis:**
  - Hanya phone: nullable, maks 30 karakter (key wajib ada)
  - Tidak disinkron ke employees.phone
  - Perubahan data lain lewat pengajuan data_change ke SDM
- **Kriteria penerimaan/pengujian:**
  - Given phone 31 karakter, Then 422 'Nomor telepon maksimal 30 karakter.'
  - Given valid, Then profil diperbarui
- **Status backend:** `available`
- **Endpoint:** `PUT /api/v1/lecturer-profile`
- **Celah backend:** (tidak ada)
- **Status web:** pages/lecturer/LecturerProfilePage.tsx
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-HR-01
- **Sumber:** C3; C7 §Profil Saya

#### MOB-HR-03 — Profil kepegawaian saya (Profil SDM) dan atribut dosen

- **Peran:** lecturer, advisor
- **Deskripsi:** Profil pegawai: data kepegawaian, pangkat/golongan, jabatan, serta atribut dosen (NIDN, NIDK, serdos, jabatan akademik, keahlian, status, mulai mengajar).
- **Aturan bisnis:**
  - Butuh tenant.access + hr_self_service.read
  - 404 bila employees.user_id kosong
- **Kriteria penerimaan/pengujian:**
  - Given pegawai ter-link, Then data pegawai+atribut dosen tampil
  - Given tak ter-link, Then pesan 404 yang jelas
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/me/hr/profile`
- **Celah backend:** Identitas: /me/hr bergantung employees.user_id (404 bila tidak ada), berbeda dari lecturers.user_id yang dipakai /lecturer-profile; Hanya snapshot saat ini, tanpa riwayat
- **Status web:** none (backend only)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Backend: backfill employees.user_id / LecturerIdentity
- **Sumber:** D05 (Profil Saya); D03 §9; C6-be-hr.md; app/Modules/HumanResource/Routes/api.php (me/hr)

#### MOB-HR-04 — Saldo cuti tahunan

- **Peran:** lecturer, advisor
- **Deskripsi:** Melihat kuota/sisa cuti tahunan.
- **Aturan bisnis:**
  - Disertakan dalam respons profil (annual leave quota)
- **Kriteria penerimaan/pengujian:**
  - Given tahun berjalan, Then kuota/terpakai/sisa tampil
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/me/hr/profile`
- **Celah backend:** Belum ada endpoint saldo terpisah; struktur field harus dibaca dari notes C6
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-HR-03
- **Sumber:** D05; C6

#### MOB-HR-05 — Ajukan cuti/izin

- **Peran:** lecturer, advisor
- **Deskripsi:** Membuat pengajuan cuti/izin (jenis, rentang tanggal, alasan, lampiran) dan langsung mengirim atau simpan draf.
- **Aturan bisnis:**
  - Field: leave_type, start_date, end_date(>=start), reason(maks 2000), attachment_file_id (wajib jika sakit & submit), submit(bool default true)
  - 422 bila tidak ada hari kerja; 409 bila overlap/kuota habis/pegawai nonaktif
  - Lampiran diunggah dulu lewat POST /file-uploads
- **Kriteria penerimaan/pengujian:**
  - Given sakit tanpa lampiran, When submit, Then ditolak validasi
  - Given tanggal overlap, Then 409
  - Given valid, Then 201 status pending approval
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/me/hr/leave-requests`; `POST /api/v1/file-uploads`
- **Celah backend:** Pegawai pengganti (substitute) disebut di dokumen tetapi tak ada field; Tidak ada UI web
- **Status web:** none (backend only)
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-HR-03; MOB-HR-17; MOB-OTH-06
- **Sumber:** D05 Fase 3; D02; D08 (SEBAGIAN backend); C6-be-hr.md; app/Modules/HumanResource/Routes/api.php (me/hr)

#### MOB-HR-06 — Daftar dan status pengajuan cuti saya

- **Peran:** lecturer, advisor
- **Deskripsi:** Daftar cuti saya beserta status (draf, menunggu, disetujui, ditolak, dikembalikan, dibatalkan).
- **Aturan bisnis:**
  - Maksimal 100 item, tanpa pagination
- **Kriteria penerimaan/pengujian:**
  - Given ada cuti, Then daftar terbaru dengan status tampil
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/me/hr/leave-requests`
- **Celah backend:** Tanpa pagination; tanpa timeline persetujuan
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-HR-03
- **Sumber:** C6; D05

#### MOB-HR-07 — Kirim draf cuti

- **Peran:** lecturer, advisor
- **Deskripsi:** Mengirim draf cuti untuk persetujuan.
- **Aturan bisnis:**
  - 409 bila bukan draf; 404 bila bukan pemilik
- **Kriteria penerimaan/pengujian:**
  - Given draf, When kirim, Then status menunggu persetujuan
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/me/hr/leave-requests/{id}/submit`
- **Celah backend:** Tidak ada endpoint edit draf (updateDraft ada di service tanpa route)
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-HR-06
- **Sumber:** C6

#### MOB-HR-08 — Batalkan pengajuan cuti

- **Peran:** lecturer, advisor
- **Deskripsi:** Membatalkan cuti berstatus draf atau menunggu.
- **Aturan bisnis:**
  - 409 untuk approved/rejected/returned/cancelled
- **Kriteria penerimaan/pengujian:**
  - Given pending, When batal, Then status cancelled
  - Given approved, Then 409
- **Status backend:** `available`
- **Endpoint:** `PATCH /api/v1/me/hr/leave-requests/{id}/cancel`
- **Celah backend:** (tidak ada)
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-HR-06
- **Sumber:** C6; D05

#### MOB-HR-09 — Revisi dan ajukan ulang pengajuan yang dikembalikan (cuti/HR)

- **Peran:** lecturer, advisor
- **Deskripsi:** Pengajuan berstatus returned dapat diperbaiki lalu diajukan ulang.
- **Aturan bisnis:**
  - Status returned saat ini jalan buntu bagi pemohon
  - resubmit generik hanya mengubah status tanpa edit isi
- **Kriteria penerimaan/pengujian:**
  - Given returned, When diperbaiki dan diajukan ulang, Then status kembali menunggu
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/approval-requests/{approvalRequest}/resubmit (requester only; hanya status returned)`
- **Celah backend:** Tidak ada route revisi isi (updateDraft/resubmit di HrRequestService belum diekspos); Mapping hr_request -> approval_request id belum jelas di resource
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** MOB-HR-06; MOB-HR-14; Backend: route revisi + ekspose approval_request_id
- **Sumber:** D05 (Not in ApprovalWorkflow...); C6 gaps

#### MOB-HR-10 — Pengajuan perubahan data pribadi (data_change)

- **Peran:** lecturer, advisor
- **Deskripsi:** Mengajukan perubahan nama, email, telepon, alamat, jenis kelamin, tempat/tanggal lahir, NIK ke SDM.
- **Aturan bisnis:**
  - POST /me/hr/requests type=data_change wajib payload.changes{...}
  - Judul maks 255; deskripsi; lampiran opsional
  - Tipe 'leave' ditolak di endpoint ini
- **Kriteria penerimaan/pengujian:**
  - Given perubahan valid, When kirim, Then 201 status pending
  - Given changes kosong, Then 422
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/me/hr/requests`
- **Celah backend:** Penerapan perubahan setelah disetujui ke master data belum diverifikasi
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-HR-14; MOB-HR-17
- **Sumber:** D05; C6

#### MOB-HR-11 — Surat keterangan kerja/aktif mengajar (permintaan dokumen)

- **Peran:** lecturer, advisor
- **Deskripsi:** Meminta dokumen surat keterangan lewat pengajuan HR tipe document.
- **Aturan bisnis:**
  - type=document; penerbitan dokumen (PDF) belum ada
- **Kriteria penerimaan/pengujian:**
  - Given pengajuan document, Then status dapat dipantau
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/me/hr/requests`
- **Celah backend:** Tidak ada penerbitan/unduh surat setelah disetujui
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-HR-14; MOB-HR-17
- **Sumber:** D05 Fase 3

#### MOB-HR-12 — Pengajuan kenaikan jabatan akademik / pangkat

- **Peran:** lecturer, advisor
- **Deskripsi:** Mengajukan promosi jabatan fungsional/pangkat dengan pengecekan kelayakan.
- **Aturan bisnis:**
  - type=promotion generik; checklist kelayakan (angka kredit/PAK) belum ada
- **Kriteria penerimaan/pengujian:**
  - Given pengajuan promotion, Then tercatat pending
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/me/hr/requests`
- **Celah backend:** Tidak ada modul kelayakan/PAK; payload bebas
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-HR-14; MOB-HR-17
- **Sumber:** D05 Fase 2/3
- **Catatan:** Partial tetapi defer: tanpa checklist kelayakan/PAK hanya form bebas; kurang bernilai.

#### MOB-HR-13 — Pengajuan mutasi/perubahan posisi

- **Peran:** lecturer, advisor
- **Deskripsi:** Mengajukan transfer/perubahan jabatan.
- **Aturan bisnis:**
  - type=transfer generik
- **Kriteria penerimaan/pengujian:**
  - Given pengajuan transfer, Then tercatat pending
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/me/hr/requests`
- **Celah backend:** Tidak ada form terstruktur
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-HR-14; MOB-HR-17
- **Sumber:** D05 Fase 3
- **Catatan:** Partial tetapi defer: form bebas, nilai rendah.

#### MOB-HR-14 — Daftar dan status pengajuan HR saya

- **Peran:** lecturer, advisor
- **Deskripsi:** Daftar pengajuan HR (transfer, data_change, promotion, document, other) beserta status dan catatan keputusan.
- **Aturan bisnis:**
  - Maks 100 item, tanpa pagination
  - Field: type,title,description,payload,status,approval_note,approved_at,rejected_at
- **Kriteria penerimaan/pengujian:**
  - Given ada pengajuan, Then daftar dengan status dan catatan tampil
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/me/hr/requests`
- **Celah backend:** Timeline persetujuan tidak dimuat
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-HR-03
- **Sumber:** D05; C6

#### MOB-HR-15 — Timeline persetujuan pengajuan saya

- **Peran:** lecturer, advisor
- **Deskripsi:** Riwayat langkah persetujuan (siapa, kapan, catatan) untuk pengajuan.
- **Aturan bisnis:**
  - GET /approval-requests/{id} memuat histories dan can_act; akses oleh pemohon/approver saat ini
- **Kriteria penerimaan/pengujian:**
  - Given pengajuan, When buka detail, Then histories tampil urut waktu
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/approval-requests`; `GET /api/v1/approval-requests/{approvalRequest}`
- **Celah backend:** HrRequestResource belum menyertakan approval_request_id; Daftar tidak memuat histories
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-HR-14; MOB-OTH-04
- **Sumber:** D05; C6 gaps

#### MOB-HR-16 — Batalkan pengajuan HR

- **Peran:** lecturer, advisor
- **Deskripsi:** Membatalkan pengajuan HR berstatus pending/returned.
- **Aturan bisnis:**
  - 409 selain pending/returned
- **Kriteria penerimaan/pengujian:**
  - Given pending, When batal, Then cancelled
- **Status backend:** `available`
- **Endpoint:** `PATCH /api/v1/me/hr/requests/{id}/cancel`
- **Celah backend:** (tidak ada)
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-HR-14
- **Sumber:** C6

#### MOB-HR-17 — Opsi/lookup form HR

- **Peran:** lecturer, advisor
- **Deskripsi:** Mengambil enum/opsi (jenis cuti, jenis pengajuan) untuk form.
- **Aturan bisnis:**
  - Isi respons belum diverifikasi
- **Kriteria penerimaan/pengujian:**
  - Given form dibuka, Then dropdown terisi
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/hr/options`
- **Celah backend:** Struktur respons belum diinspeksi
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Auth
- **Sumber:** C6

#### MOB-HR-18 — Kehadiran kerja saya (presensi pegawai)

- **Peran:** lecturer
- **Deskripsi:** Melihat rekap kehadiran kerja dosen/pegawai.
- **Aturan bisnis:**
  - Modul kehadiran pegawai belum ada (STATUS §9)
- **Kriteria penerimaan/pengujian:**
  - Given periode, Then rekap hadir/izin tampil
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada endpoint/tabel
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: modul kehadiran
- **Sumber:** D05 Fase 3 A-D; D03 §9; STATUS §4

#### MOB-HR-19 — Ajukan koreksi presensi

- **Peran:** lecturer
- **Deskripsi:** Koreksi lupa check-in/out.
- **Aturan bisnis:**
  - Kehadiran tahap D
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-HR-18
- **Sumber:** D05

#### MOB-HR-20 — Check-in/out presensi via aplikasi (GPS, geofence, foto)

- **Peran:** lecturer
- **Deskripsi:** Presensi mandiri dari aplikasi dengan lokasi.
- **Aturan bisnis:**
  - Opsional tahap E; butuh kebijakan geofence
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada; butuh infrastruktur lokasi/foto
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-HR-18; Flutter: geolocator, camera
- **Sumber:** D05 §5.11; D07 (fingerprint/online)

#### MOB-HR-21 — Riwayat pendidikan dosen dan unggah ijazah

- **Peran:** lecturer
- **Deskripsi:** Menambah riwayat pendidikan dengan unggah ijazah/transkrip.
- **Aturan bisnis:**
  - Verifikasi SDM
- **Kriteria penerimaan/pengujian:**
  - Given data baru, Then status menunggu verifikasi
- **Status backend:** `missing`
- **Endpoint:** `(admin) /api/v1/hr/educations`
- **Celah backend:** Tidak ada /me/hr/educations
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: /me/hr/*
- **Sumber:** D05 Fase 2; C6 gap

#### MOB-HR-22 — Dokumen kepegawaian: unggah, verifikasi, kedaluwarsa, kelengkapan

- **Peran:** lecturer
- **Deskripsi:** Mengelola dokumen pegawai dan melihat status verifikasi/kedaluwarsa.
- **Aturan bisnis:**
  - Dokumen kedaluwarsa memerlukan pengingat
- **Kriteria penerimaan/pengujian:**
  - Given dokumen terunggah, Then status verifikasi tampil
- **Status backend:** `missing`
- **Endpoint:** `(admin) /api/v1/hr/documents`; `POST /api/v1/file-uploads`
- **Celah backend:** Tidak ada /me/hr/documents
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: /me/hr/*
- **Sumber:** D05; C6 gap

#### MOB-HR-23 — Riwayat karier (jabatan, pangkat, jabatan akademik)

- **Peran:** lecturer
- **Deskripsi:** Timeline posisi, pangkat/golongan, jabatan akademik.
- **Aturan bisnis:**
  - Hanya snapshot di /me/hr/profile
- **Kriteria penerimaan/pengujian:**
  - Given riwayat, Then timeline tampil
- **Status backend:** `missing`
- **Endpoint:** `(admin) /api/v1/hr/positions, /hr/ranks, /hr/academic-ranks`
- **Celah backend:** Tidak ada /me/hr/* riwayat
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: /me/hr/*
- **Sumber:** D05 Fase 2; C6 gap; D03 §9 (jabatan akademik ⚙️)

#### MOB-HR-24 — Pelatihan dan sertifikasi (termasuk serdos); catat pelatihan eksternal

- **Peran:** lecturer
- **Deskripsi:** Melihat pelatihan/sertifikasi dan mencatat pelatihan eksternal.
- **Aturan bisnis:**
  - Verifikasi SDM
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** `(admin) /api/v1/hr/trainings, /hr/certifications`
- **Celah backend:** Tidak ada /me/hr/*
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: /me/hr/*
- **Sumber:** D05 Fase 4; D03 §9

#### MOB-HR-25 — Penilaian kinerja: penilaian diri dan hasil

- **Peran:** lecturer
- **Deskripsi:** Mengisi penilaian diri dan melihat hasil penilaian kinerja.
- **Aturan bisnis:**
  - Fase 4
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** `(admin) /api/v1/hr/performance-reviews`
- **Celah backend:** Tidak ada /me/hr/*
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** Backend: /me/hr/*
- **Sumber:** D05 Fase 4

#### MOB-HR-26 — Catat aktivitas penelitian, pengabdian (PkM), dan publikasi

- **Peran:** lecturer
- **Deskripsi:** Dosen mencatat/mengelola penelitian, PkM, publikasi untuk portofolio dan BKD.
- **Aturan bisnis:**
  - Tahap 6 roadmap; SEVIMA Business tier
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** `(admin) /api/v1/hr/activities`
- **Celah backend:** Tidak ada /me/hr/activities
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `build_contract_pending_backend`
- **Dependensi:** Backend: /me/hr/*
- **Sumber:** D02 Tahap 6; D05; D08 (RENCANA)

#### MOB-HR-27 — Hibah penelitian/PkM internal (Litabmas)

- **Peran:** lecturer
- **Deskripsi:** Proposal, pelaksanaan, laporan hibah internal.
- **Aturan bisnis:**
  - SEVIMA Premium+
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada rancangan
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D05; D07

#### MOB-HR-28 — Sinkronisasi data dosen dengan SISTER

- **Peran:** lecturer
- **Deskripsi:** Integrasi data dosen dengan SISTER (tanpa input ganda).
- **Aturan bisnis:**
  - Backend-to-backend
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D05; D07
- **Catatan:** Bukan fitur layar mobile.

#### MOB-HR-29 — Beban mengajar dan riwayat mengajar dosen

- **Peran:** lecturer
- **Deskripsi:** Rekap kelas/SKS yang diampu per semester dan riwayat mengajar lengkap.
- **Aturan bisnis:**
  - Dapat diturunkan dari endpoint kelas dosen (lihat group lain) untuk semester berjalan
- **Kriteria penerimaan/pengujian:**
  - Given semester, Then total SKS dan kelas tampil
- **Status backend:** `partial`
- **Endpoint:** `(turunan) endpoint kelas dosen di G-teaching`
- **Celah backend:** Tidak ada endpoint riwayat lintas semester/agregat SKS
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Group teaching rows
- **Sumber:** D02; D03 §7; D14 (riwayat mengajar: Baru)
- **Catatan:** Mulai dari semester berjalan; riwayat menunggu backend.

#### MOB-HR-30 — Laporan BKD per semester

- **Peran:** lecturer
- **Deskripsi:** Rekap Beban Kinerja Dosen (Tridharma) per semester.
- **Aturan bisnis:**
  - Fase 5; SEVIMA Business+
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada modul BKD
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-HR-29; Backend: modul BKD
- **Sumber:** D05; D07; D03 §7

#### MOB-HR-31 — Slip gaji dosen

- **Peran:** lecturer
- **Deskripsi:** Melihat slip gaji bulanan.
- **Aturan bisnis:**
  - Sensitif; Fase 5
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada modul payroll
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D05; D03 §9

#### MOB-HR-32 — Lembur dan perjalanan dinas

- **Peran:** lecturer
- **Deskripsi:** Pengajuan lembur dan perjalanan dinas.
- **Aturan bisnis:**
  - Fase 5
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D05

#### MOB-HR-33 — Surat peringatan (SP1/SP2/SP3) saya

- **Peran:** lecturer
- **Deskripsi:** Melihat surat peringatan pegawai.
- **Aturan bisnis:**
  - Fase 5
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D05

#### MOB-HR-34 — Remunerasi, honor/vakasi, PAK

- **Peran:** lecturer
- **Deskripsi:** Remunerasi berbasis kinerja dan honor mengajar/ujian.
- **Aturan bisnis:**
  - SEVIMA Business+
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D05; D07

#### MOB-HR-35 — Setujui, tolak, kembalikan, atau delegasikan pengajuan HR bawahan

- **Peran:** lecturer, advisor
- **Deskripsi:** Dosen sebagai atasan memproses langkah persetujuan pengajuan bawahan.
- **Aturan bisnis:**
  - Hanya bila dosen memegang langkah (policy 'act')
  - Bergantung konfigurasi workflow
- **Kriteria penerimaan/pengujian:**
  - Given langkah ditugaskan, When approve/reject, Then status berubah dan pemohon dinotifikasi
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/approval-request-steps/{step}/approve|reject|return|delegate`
- **Celah backend:** Tidak ada daftar 'ditugaskan ke saya'
- **Status web:** pages/approvals/* (admin, butuh approval_requests.read)
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-OTH-05
- **Sumber:** D05 (inbox generik); C5 gaps
- **Catatan:** Backend partial tetapi defer: tanpa daftar 'ditugaskan kepada saya' dosen tidak dapat menemukan langkah yang harus diproses (hanya lewat notifikasi legacy bermuatan id).

### Lainnya (OTH)

Lainnya: tugas PMB untuk dosen (penguji/pengawas; lingkup diputuskan sebagai modul kondisional), persetujuan generik, unggah berkas, serta fitur di luar cakupan (wali, chatbot, kesehatan).

#### MOB-OTH-01 — Daftar sesi ujian PMB yang ditugaskan kepada saya (penguji/pengawas)

- **Peran:** lecturer
- **Deskripsi:** Dosen yang ditugasi sebagai penguji/pewawancara/pengawas melihat sesi ujian seleksi PMB miliknya.
- **Aturan bisnis:**
  - Lingkup: hanya dosen yang ditugaskan di exam_session_examiners (peran proctor|examiner|interviewer); selain itu daftar kosong
  - Permission diberikan ke semua dosen (pmb_scores.create + pmb_exam_attendance.update); pembatasan sesi di ExamService::assertSessionStaff
  - Maks 200 sesi, urut starts_at desc
  - Item: id, code, starts_at, ends_at, schedule, mode, component, room, participants_count, scores_locked, roles
- **Kriteria penerimaan/pengujian:**
  - Given dosen ditugaskan, Then sesi tampil dengan peran
  - Given tidak ditugaskan, Then daftar kosong dan menu disembunyikan
  - Given sesi terkunci, Then ditandai terkunci
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/pmb/admin/examiner/sessions`
- **Celah backend:** Tidak ada filter tanggal/status; respons tidak membedakan tenant selain header X-University-ID
- **Status web:** pmb/pages/admin/ExamPages.tsx (ScoringPage ADM-53)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Auth; Header tenant
- **Sumber:** docs/pmb/08-rbac.md; OrganizationalRoleSeeder.php; code: Admission ExamController::mySessions; Prefix aktual /api/v1/pmb/admin/* (middleware auth:sanctum, pmb.staff, tenant.access, throttle:pmb-admin)
- **Catatan:** Keputusan lingkup: bangun sebagai modul 'Tugas PMB' kondisional, tampil hanya bila daftar sesi tidak kosong.

#### MOB-OTH-02 — Presensi peserta ujian PMB (pengawas/penguji)

- **Peran:** lecturer
- **Deskripsi:** Mencatat kehadiran peserta (hadir/absen/izin/diskualifikasi) per sesi, via pindai QR kartu ujian atau pilih peserta; unduh daftar hadir PDF.
- **Aturan bisnis:**
  - Peran sesi: proctor, examiner, interviewer
  - Token QR kartu: format a.b.c, HMAC; salah -> 422 'Kode QR kartu tidak valid.'
  - Peserta harus anggota sesi (422 WRONG_SESSION)
  - Perubahan kehadiran setelah sesi berakhir +2 jam hanya oleh panitia (403)
  - Status absent mengubah status seleksi pendaftar dan memicu notifikasi pendaftar
- **Kriteria penerimaan/pengujian:**
  - Given QR valid peserta sesi, When dipindai, Then status present dan checked_in_at terisi
  - Given QR milik sesi lain, Then 422
  - Given sesi sudah lewat >2 jam, Then 403
  - Given attendance-sheet, Then URL PDF sementara
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/pmb/admin/exam-sessions/{examSession}`; `POST /api/v1/pmb/admin/exam-sessions/{examSession}/check-in`; `GET /api/v1/pmb/admin/exam-sessions/{examSession}/attendance-sheet`
- **Celah backend:** Butuh permission pmb_exam_attendance.update (ada pada role dosen); Pindai QR membutuhkan paket kamera; offline tidak didukung
- **Status web:** pmb/pages/admin/ExamPages.tsx (ExamSessionPage)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-OTH-01; Flutter: mobile_scanner
- **Sumber:** docs/pmb/08-rbac.md; docs/pmb/06; code: ExamController::session/checkIn/attendanceSheet; ExamService::checkIn

#### MOB-OTH-03 — Input nilai seleksi PMB (nilai mentah/rubrik) oleh penguji

- **Peran:** lecturer
- **Deskripsi:** Penguji/pewawancara memasukkan nilai peserta per sesi: raw_score atau rubric_scores, passed, catatan; bulk hingga 500 baris.
- **Aturan bisnis:**
  - Hanya peran examiner/interviewer pada sesi tsb (403 'Anda tidak ditugaskan sebagai penguji pada sesi ini.'); proctor tidak boleh
  - Sesi terkunci -> 423 SCORES_LOCKED
  - Peserta absent/excused/disqualified dilewati
  - Jika rubric diisi dan komponen berkriteria, raw dihitung dari rubrik; validasi rentang raw_min..raw_max
  - Komponen wawancara/portofolio multi-penguji: nilai disimpan per penguji; non-manajer hanya melihat nilainya sendiri
  - rows 1..500; note maks 255; peserta Registered otomatis menjadi Present
  - Pengunci nilai (lock-scores, pmb_scores.lock) hanya panitia, bukan dosen
- **Kriteria penerimaan/pengujian:**
  - Given penguji ditugaskan, When menyimpan nilai valid, Then 'Nilai disimpan.' dan saved=jumlah baris
  - Given nilai di luar rentang, Then ditolak
  - Given sesi terkunci, Then 423 dan input dinonaktifkan
  - Given bukan penguji sesi, Then 403
- **Status backend:** `available`
- **Endpoint:** `GET /api/v1/pmb/admin/exam-sessions/{examSession}/scores`; `PUT /api/v1/pmb/admin/exam-sessions/{examSession}/scores`
- **Celah backend:** Penguji sengaja tak diberi pmb_applications.read, jadi tak ada data pendaftar selain nama/nomor peserta; Tidak ada penyimpanan draf lokal/offline
- **Status web:** pmb/pages/admin/ExamPages.tsx (ScoringPage ADM-53)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** MOB-OTH-01; MOB-OTH-02
- **Sumber:** docs/pmb/06-spesifikasi-seleksi-ujian-pengumuman.md; docs/pmb/08-rbac.md; code: ExamController::sessionScores/inputScores; ScoreService::inputSessionScores
- **Catatan:** Skala rubrik dari komponen (rubric.criteria).

#### MOB-OTH-04 — Lihat pengajuan persetujuan saya dan statusnya

- **Peran:** student, lecturer, advisor
- **Deskripsi:** Daftar approval request milik sendiri dan detail dengan riwayat.
- **Aturan bisnis:**
  - Tanpa approval_requests.read hanya pengajuan sendiri
  - Detail diakses oleh pemohon, approver saat ini, atau approval_requests.read
  - Pembuat request: PMB dan HR (HrRequestService memakai ApprovalRequestService::submit); catatan C5 'hanya PMB' sudah usang
- **Kriteria penerimaan/pengujian:**
  - Given pemohon, Then daftar milik sendiri tampil
  - Given bukan pemohon/approver, Then 403/404
- **Status backend:** `partial`
- **Endpoint:** `GET /api/v1/approval-requests`; `GET /api/v1/approval-requests/{approvalRequest}`
- **Celah backend:** Mahasiswa umumnya daftar kosong (belum ada alur mahasiswa yang memakainya)
- **Status web:** pages/approvals/* (admin)
- **Status mobile saat ini:** none
- **Prioritas:** P2
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Auth
- **Sumber:** C5; code HumanResource/HrRequestService

#### MOB-OTH-05 — Bertindak atas langkah persetujuan yang ditugaskan (approve/reject/return/delegate)

- **Peran:** lecturer, advisor
- **Deskripsi:** Dosen/PA sebagai approver memproses langkah persetujuan generik.
- **Aturan bisnis:**
  - Policy 'act' dan 'delegate' menentukan hak; approver bertipe Role: siapa pemegang role pertama yang bertindak
  - Catatan komentar pada keputusan
- **Kriteria penerimaan/pengujian:**
  - Given langkah aktif, When setuju, Then request maju
- **Status backend:** `partial`
- **Endpoint:** `POST /api/v1/approval-request-steps/{step}/approve`; `POST /api/v1/approval-request-steps/{step}/reject`; `POST /api/v1/approval-request-steps/{step}/return`; `POST /api/v1/approval-request-steps/{step}/delegate`
- **Celah backend:** Tidak ada inbox 'ditugaskan kepada saya' tanpa approval_requests.read; notifikasi ApprovalStepAssigned hanya memuat id (message null)
- **Status web:** pages/approvals/ApprovalRequestDetailPage.tsx (admin)
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** MOB-OTH-04; MOB-ANN-06
- **Sumber:** D06 §8; C5 gaps
- **Catatan:** Persetujuan KRS PA ditangani group akademik (krs_advising), bukan baris ini. Backend partial tetapi defer: sama dengan MOB-HR-35 (tak ada inbox approver).

#### MOB-OTH-06 — Unggah dan unduh berkas (infrastruktur)

- **Peran:** student, lecturer, advisor
- **Deskripsi:** Unggah (multipart) dan unduh/pratinjau berkas untuk lampiran cuti, pengajuan, bukti bayar, tugas.
- **Aturan bisnis:**
  - POST multipart file + is_public; ukuran maks default 10 MB; mime pdf,jpg,jpeg,png,doc,docx,xls,xlsx
  - Berkas diteruskan ke fitur lewat id (file_upload_id)
- **Kriteria penerimaan/pengujian:**
  - Given file valid, When unggah, Then id dikembalikan
  - Given melebihi batas, Then 422
- **Status backend:** `available`
- **Endpoint:** `POST /api/v1/file-uploads`; `GET /api/v1/file-uploads/{id}/preview`; `GET /api/v1/file-uploads/{id}/download`
- **Celah backend:** (tidak ada)
- **Status web:** komponen unggah di pages/portal/*
- **Status mobile saat ini:** none
- **Prioritas:** P1
- **Cakupan rekomendasi:** `build_integrated`
- **Dependensi:** Flutter: file_picker, image_picker, dio
- **Sumber:** C4 (file-uploads); C8

#### MOB-OTH-07 — Portal wali/orang tua (hanya baca)

- **Peran:** student
- **Deskripsi:** Wali mahasiswa melihat data akademik/keuangan (read-only).
- **Aturan bisnis:**
  - Peran guardian dan student_guardians belum ada/aktif
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada role/endpoint
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D04; D15 (rancangan)
- **Catatan:** Bukan fitur mahasiswa/dosen.

#### MOB-OTH-08 — Asisten AI / chatbot kampus

- **Peran:** student, lecturer
- **Deskripsi:** Asisten digital (benchmark SEVIMA Business+).
- **Aturan bisnis:**
  - Tidak ada rancangan CivitasOne
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D05; D07

#### MOB-OTH-09 — Modul kesehatan: UKOM dan kepaniteraan klinik

- **Peran:** student, lecturer
- **Deskripsi:** Pencatatan UKOM dan kepaniteraan klinik (fakultas kesehatan).
- **Aturan bisnis:**
  - Benchmark SEVIMA; tidak ada rancangan
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D07

#### MOB-OTH-10 — Batas paket (kuota tracer dan tier fitur karier)

- **Peran:** student
- **Deskripsi:** Kuota tracer/fitur karier per paket langganan.
- **Aturan bisnis:**
  - Pengaturan admin/komersial
- **Kriteria penerimaan/pengujian:**
  - -
- **Status backend:** `missing`
- **Endpoint:** (tidak ada)
- **Celah backend:** Tidak ada
- **Status web:** none
- **Status mobile saat ini:** none
- **Prioritas:** P3
- **Cakupan rekomendasi:** `defer`
- **Dependensi:** -
- **Sumber:** D25
- **Catatan:** Bukan fitur layar mobile.

### Pertanyaan belum terjawab

1. Lingkup PMB penguji/pengawas di aplikasi dosen: dibangun kondisional (menu hanya bila GET /pmb/admin/examiner/sessions tidak kosong). Perlu konfirmasi produk apakah dosen penguji PMB memakai aplikasi mobile, dan kebijakan keamanan (token tenant, throttle pmb-admin, pindai QR kartu).
2. Feed pengumuman untuk dosen/PA: apakah backend akan menambah GET /lecturer/announcements atau memberi announcements.read + filter audience? Hingga itu, MOB-ANN-04 hanya entri 'Menunggu backend'.
3. Push FCM: tidak ada endpoint registrasi push_token (user_devices.push_token nullable ada) dan tidak ada pengirim. Perlu keputusan prioritas backend dan kredensial Firebase.
4. Unified identity HR: /me/hr/* bergantung employees.user_id (404 jika kosong) sedangkan /lecturer-profile memakai lecturers.user_id. Perlu backfill/penyatuan sebelum MOB-HR-03..17 dapat diandalkan.
5. Thesis/internship/library: siapa yang membuka modul read-only admin untuk student/lecturer? Perlu endpoint 'milik sendiri' (tanpa kebocoran pinjaman semua mahasiswa pada GET /books/{id}); scope Fase 4/Tahap 6 belum dijadwalkan.
6. Struktur respons GET /api/v1/hr/options dan field saldo cuti di GET /me/hr/profile belum diverifikasi rinci; template notifikasi hr.request_returned mungkin tidak ada (tidak terkirim).
7. Revisi/ajukan ulang pengajuan returned: approval-requests/{id}/resubmit ada, tetapi HrRequestService::resubmit/updateDraft tidak punya route dan HrRequestResource belum memuat approval_request_id.
8. Preferensi notifikasi per event tidak efektif (matcher memakai nama kelas notifikasi, bukan event_key) sehingga UI toggle per jenis akan menyesatkan sampai backend diperbaiki.
9. Izin unduh lampiran pengumuman oleh mahasiswa via /file-uploads/{id}/download belum diverifikasi end-to-end.
10. Catatan C5 'hanya PMB yang membuat approval request' usang: HrRequestService juga memanggil ApprovalRequestService::submit.

## 6. Pertanyaan terbuka dari sintesis matriks

- (G1) Tenant mobile: build satu universitas (flavor + university_code/X-University-ID dari /tenant/profile) atau pemilih universitas? D01 menyatakan tidak ada header, tetapi student finance (tenant.access) dan akun multi-keanggotaan membutuhkannya; tidak ada endpoint publik penemuan universitas.
- (G1) Flag academic.lecturer_ownership default OFF: dosen dapat membaca/menulis nilai, absensi, ujian kelas mana pun di tenant. Apakah backend akan mengaktifkannya sebelum rilis, atau klien wajib memfilter kelas (MOB-AUTH-22)?
- (G1) Strategi cache offline: D01 (hanya data referensi, tanpa data transaksional) bertentangan dengan academic-learning 09 (cache jadwal/KHS/transkrip, antrean jawaban ujian). Mana yang berlaku?
- (G1) Push FCM: kontrak endpoint token (POST/DELETE /devices push-token), driver FCM, dan aktivasi kanal push belum ada; siapa pemilik dan kapan?
- (G1) Domain resmi App Link (app.civitasone.example placeholder) dan format tautan reset password/aktivasi ke aplikasi belum ditetapkan; email mahasiswa placeholder (@student.<univ>.local) tidak menerima reset.
- (G1) Endpoint mahasiswa bimbingan untuk dosen PA: apakah GET /students mendukung filter academic_advisor_id (belum terverifikasi) atau perlu /advising/students?
- (G1) Apakah ZH (Mandarin) menjadi cakupan aplikasi? Hanya prototype one/ yang memilikinya; D01 hanya ID/EN.
- (G1) must_change_password tidak ditegakkan server dan token tidak kedaluwarsa/dicabut saat ganti sandi: apakah backend akan menambahkannya?
- (G1) Izin PATCH /student/profile: C7 mencatat student_portal.read, kode C2 student_portal.update; keduanya dimiliki role student, tetapi perlu konfirmasi bila role disesuaikan.
- (G2) Dosen PA: bagaimana advisor mendapatkan daftar mahasiswa bimbingan (endpoint /advising/students belum ada; /students tanpa filter academic_advisor_id) dan apakah role academic_advisor murni perlu students.read/academic_calendar.read?
- (G2) Skala nilai dan IPS/IPK: kode memakai skala hardcoded 7 tingkat (A>=85...E<35) dan tidak menyaring is_published; dokumen D02 memberi skala berbeda (A-, B+ ...) dan 'dapat dikonfigurasi'. Mana yang berlaku untuk mobile dan kapan publikasi nilai/EDOM gate dibangun?
- (G2) Kontrak galat KRS: kode hanya memberi errors.code untuk SCHEDULE_CONFLICT dan ACADEMIC_BLOCKED; dokumen mendesain KRS_VALIDATION_FAILED/violations[], PERIOD_CLOSED, revision_requested, locked, Idempotency-Key. Apakah mobile diminta menunggu kontrak ini atau memakai pesan teks 409?
- (G2) Semester Pendek: endpoint POST /student/krs-items (D26) vs /student/krs/items yang ada; D26 169 menyatakan persetujuan SP bergantung relasi dosen wali dan ApprovalWorkflow - belum jelas jalur final.
- (G2) Jadwal dosen bertanggal (GET /teaching/schedule) dan sesi kuliah per tanggal belum ada; apakah mobile cukup menurunkan timetable mingguan sisi klien?
- (G2) Status cekal bagi mahasiswa: tidak ada GET /student/finance/clearance (D20 desain); apakah mobile cukup membaca notices KRS + 409 errors.blocks[] sampai endpoint tersedia?
- (G2) Dokumen D02 'Dokumen mahasiswa' (student_documents) tidak menyebut apakah mahasiswa mengunggah atau hanya melihat; tidak dibuat baris.
- (G2) Privasi: GET /students dan /students/{id}/transcript terbuka untuk semua dosen tanpa pembatasan kelas/advisee; perlu keputusan sebelum dosen mobile menampilkannya.
- (G2) Fitur D09 'rapor SMA' (KHS pattern) hanya analogi desain dan tidak dimasukkan sebagai baris.
- (G3) Kontrak QR presensi bersaing: D11 POST /lms/attendance/qr-check-in vs D14 POST /student/attendance/qr-scan; belum ada keputusan, juga konflik GPS vs pernyataan Play Store Data Safety "lokasi tidak dikumpulkan"
- (G3) Apakah ambang kehadiran minimum (75%) memblokir ujian (hard block) atau hanya peringatan; belum ada endpoint exam-eligibility
- (G3) Otorisasi unduh berkas submission mahasiswa oleh dosen via /file-uploads/{id}/download belum terverifikasi; juga batas FileUpload 10MB vs max_file_size_mb tugas hingga 50MB
- (G3) Flag academic.lecturer_ownership default OFF: dosen dapat merekam/melihat presensi kelas lain; belum diketahui nilai flag di produksi
- (G3) Persentase kehadiran menghitung hanya present (izin/sakit tidak dihitung hadir) - perlu konfirmasi kebijakan akademik
- (G3) Kontrak progres materi tidak konsisten antara D11 dan D14; dokumen D06/D01/D26 usang (menyebut backend belum ada) - kode adalah acuan
- (G4) Apakah mahasiswa boleh melihat nilai di aplikasi sebelum ada alur publikasi nilai? Kode saat ini langsung menampilkan Grade di /student/grades begitu dosen menyimpan (tanpa finalisasi/kunci); desain target (grade_submissions) belum ada.
- (G4) Bagaimana mobile harus menangani anti-curang (enum pelanggaran bertipe browser, tanpa auto-submit)? Perlu keputusan produk: pemetaan lifecycle -> tab_switch/window_blur, FLAG_SECURE, ambang auto-submit di klien, dan apakah backend menambah tipe pelanggaran mobile + deadline_at/server_now.
- (G4) Apakah UI bank soal Flutter benar-ada? STATUS/D11 menyebut 'bank UI' tetapi catatan C9 hanya menemukan question_bank_screen sebagai editor soal ujian (tidak memanggil /question-bank).
- (G4) Apakah ujian lewat tautan publik + NIM (identitas hanya NIM) boleh dipakai di mobile, dan bagaimana pemetaan token tautan/QR ke ujian terautentikasi (perlu endpoint resolve + App Links)?
- (G4) Feature flag academic.lecturer_ownership default OFF: dosen dapat menilai/mengelola ujian kelas mana pun. Apakah diaktifkan sebelum rilis mobile (selain filter kelas klien)?
- (G4) Modul EDOM (periode, form anonim, laporan dosen) hanya desain 11 Okt 2026; kapan backend dibangun dan konflik desain pembuatan partisipasi (saat periode dibuka vs saat form dibuka) harus diputuskan.
- (G4) Ujian susulan, berita acara/penentuan pengawas ujian, dan peran dosen sebagai pengawas belum didefinisikan di dokumen.
- (G4) Penilaian ujian PMB oleh dosen penguji/pengawas: kontrak endpoint berada di grup PMB; konfirmasi apakah termasuk scope app dosen.
- (G4) Cek pelanggaran 'cekal' keuangan UTS/UAS: sumber cekal otomatis dari KEU belum terverifikasi lengkap (docs KEU menyebut SIAKAD A.8 belum dibangun per 15 Okt); hanya penegakan 409 ACADEMIC_BLOCKED pada start yang terverifikasi.
- (G4) Nilai mahasiswa (KHS/transkrip/IPK) dan jadwal ujian di kalender berada di grup lain; baris mobile untuk tampilan nilai mahasiswa tidak dibuat di G4 (dirujuk silang).
- (G5) Apakah XENDIT_SECRET_KEY/kanal online akan aktif di produksi (dan penyedia lain)? Tanpa itu MOB-FIN-07 hanya menampilkan transfer manual.
- (G5) Format bank_accounts (keu.bank_accounts) belum dibakukan; web mengasumsikan {bank,account_number,account_name}. Perlu kontrak tetap agar app tidak salah parsing.
- (G5) Apakah cekal view_grades/transcript/yudisium/graduation akan ditegakkan (saat ini hanya krs & exam) dan apakah dosen PA akan mendapat flag cekal advisee (MOB-FIN-37)?
- (G5) Apakah penyesuaian tagihan tambah/drop KRS dan biaya cuti sudah terhubung ke KEU (STATUS vs docs bertentangan)?
- (G5) Kapan modul Semester Pendek dibangun dan apakah pembayaran SP mengikuti KEU (online/manual) atau dicatat manual oleh Keuangan seperti di docs SP?
- (G5) Kebijakan denda (KEU-Q-06), UKT (KEU-Q-13), dan cicilan (KEU-Q-10) per tenant: aktif atau tidak?
- (G5) Push notification/FCM belum ada (tidak ada endpoint registrasi token); notifikasi keu.* hanya via polling.
- (G6) Apakah StudentRequestService mengirim notifikasi database saat keputusan (disetujui/ditolak/dikembalikan)? Belum terverifikasi di kode.
- (G6) Bagaimana mahasiswa melihat status "returned" dan mengajukan ulang: perlu ekspos approval_request_id/status returned di StudentRequestResource.
- (G6) Apakah dosen/PA mendapat permission approval-request-steps dan workflow pengajuan mahasiswa akan menyertakan langkah dosen wali (rekomendasi cuti)?
- (G6) Mahasiswa berstatus inactive diblokir login: aktif kembali dari inactive tidak bisa lewat app (hanya dari status leave).
- (G6) Apakah PortalRequestsPage mendukung unggah lampiran; kontrak endpoint beberapa fitur (laporan pelanggaran mahasiswa, hasil kelulusan, perbaikan pendaftaran yudisium) belum didefinisikan di dokumen.
- (G6) Peran alumni (pasca lulus: tracer, profil alumni, karier) tidak masuk lingkup student/lecturer app; perlu keputusan produk.
- (G7) Lingkup PMB penguji/pengawas di aplikasi dosen: dibangun kondisional (menu hanya bila GET /pmb/admin/examiner/sessions tidak kosong). Perlu konfirmasi produk apakah dosen penguji PMB memakai aplikasi mobile, dan kebijakan keamanan (token tenant, throttle pmb-admin, pindai QR kartu).
- (G7) Feed pengumuman untuk dosen/PA: apakah backend akan menambah GET /lecturer/announcements atau memberi announcements.read + filter audience? Hingga itu, MOB-ANN-04 hanya entri 'Menunggu backend'.
- (G7) Push FCM: tidak ada endpoint registrasi push_token (user_devices.push_token nullable ada) dan tidak ada pengirim. Perlu keputusan prioritas backend dan kredensial Firebase.
- (G7) Unified identity HR: /me/hr/* bergantung employees.user_id (404 jika kosong) sedangkan /lecturer-profile memakai lecturers.user_id. Perlu backfill/penyatuan sebelum MOB-HR-03..17 dapat diandalkan.
- (G7) Thesis/internship/library: siapa yang membuka modul read-only admin untuk student/lecturer? Perlu endpoint 'milik sendiri' (tanpa kebocoran pinjaman semua mahasiswa pada GET /books/{id}); scope Fase 4/Tahap 6 belum dijadwalkan.
- (G7) Struktur respons GET /api/v1/hr/options dan field saldo cuti di GET /me/hr/profile belum diverifikasi rinci; template notifikasi hr.request_returned mungkin tidak ada (tidak terkirim).
- (G7) Revisi/ajukan ulang pengajuan returned: approval-requests/{id}/resubmit ada, tetapi HrRequestService::resubmit/updateDraft tidak punya route dan HrRequestResource belum memuat approval_request_id.
- (G7) Preferensi notifikasi per event tidak efektif (matcher memakai nama kelas notifikasi, bukan event_key) sehingga UI toggle per jenis akan menyesatkan sampai backend diperbaiki.
- (G7) Izin unduh lampiran pengumuman oleh mahasiswa via /file-uploads/{id}/download belum diverifikasi end-to-end.
- (G7) Catatan C5 'hanya PMB yang membuat approval request' usang: HrRequestService juga memanggil ApprovalRequestService::submit.
