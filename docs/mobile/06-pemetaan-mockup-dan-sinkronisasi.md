# Pemetaan Mockup, Fitur Web, dan Sinkronisasi Android

> **Per:** 5 November 2026. **Cakupan:** Android — beranda (mahasiswa dan dosen), Menu Utama, hub modul (Akademik, Kuliah, Keuangan, Akun), dan Pengaturan. Modul teaching dosen dan tugas ujian PMB belum diubah (lihat §7).
> Melengkapi [`02-matriks-fitur.md`](02-matriks-fitur.md) (status per kebutuhan `MOB-*`) dan [`03-integrasi-backend-dan-celah-api.md`](03-integrasi-backend-dan-celah-api.md) (kontrak API). Dokumen ini memetakan mockup, Web App, dan Android dalam satu tabel.

## 1. Sumber yang diaudit

| Sumber | Lokasi | Peran dalam audit |
|---|---|---|
| Menu portal mahasiswa (web) | [`frontend/src/constants/nav.ts`](../../frontend/src/constants/nav.ts) → `PORTAL_NAV_ITEMS` | Acuan fitur dan permission menu |
| Layanan API portal mahasiswa (web) | [`frontend/src/services/studentPortalService.ts`](../../frontend/src/services/studentPortalService.ts) | Acuan endpoint; dibandingkan dengan Android (§4) |
| Modul One (UI) | `frontend/src/one/` | **Hanya data mock dan tidak memanggil API** (lihat komentar di `OneApp.tsx`). Dipakai sebagai referensi visual, bukan sumber logika bisnis |
| Backend | `app/Modules/*/Routes/api.php`, `OrganizationalRoleSeeder` | Sumber kebenaran endpoint dan permission |
| Android | `mobile/lib/core/router/route_paths.dart`, `mobile/lib/features/*/data/*_api.dart` | Rute dan endpoint yang dipakai |
| Mockup | Dua lampiran gambar (beranda, menu, ikon, pengaturan) | Acuan visual |

## 2. Tabel pemetaan fitur (mahasiswa)

Legenda status: **Integrated** = memakai endpoint backend yang sama dengan web. **Partially Integrated** = endpoint ada, tetapi sebagian data belum tersedia. **Not Implemented** = belum ada di Android. **Blocked** = tidak dapat dibuat karena izin backend atau prasyarat eksternal.

| Modul | Fitur Web (`PORTAL_NAV_ITEMS`) | Fitur Android (rute) | API | Status |
|---|---|---|---|---|
| Dashboard | Dashboard | Beranda (`/s/home`) | `GET /student/dashboard`, `GET /student/finance/summary`, `GET /notifications/unread-count` | Integrated (visual sesuai mockup, §3) |
| Dashboard | — (mockup: Menu Utama) | Menu Utama (`/s/menu`) | Tidak ada; navigasi lokal yang difilter permission | Integrated (baru) |
| Profil | Profil | Profil (`/s/profile`) | `GET /student/profile` | Integrated (ubah profil: lihat 02-matriks `MOB-AUTH-17`) |
| Akademik | Akademik Saya | Tab Akademik (`/s/academic`) | `GET /student/academic-summary` | Integrated |
| Akademik | KRS | KRS (`/s/krs`), penawaran, riwayat | `/student/krs*` | Integrated |
| Akademik | Jadwal | Jadwal Kuliah (`/s/schedule`) | `GET /student/schedule` | Integrated |
| Akademik | KHS | KHS (`/s/khs`) | `GET /student/khs` | Integrated |
| Akademik | Nilai | Nilai (`/s/grades`) | `GET /student/grades` | Integrated |
| Akademik | Transkrip | Transkrip (`/s/transcript`) | `GET /student/transcript` | Integrated |
| Akademik | Presensi | Presensi (`/s/attendance`) | `GET /student/attendance` | Integrated |
| Akademik | Dokumen | Dokumen Akademik (`/s/documents`) | `GET /student/documents` | Integrated |
| Akademik | Riwayat (API tersedia di web service) | Riwayat akademik (`/s/academic-history`) | `GET /student/academic-history` | Integrated |
| Akademik | Kalender | Kalender Akademik (`/s/calendar`) | `GET /student/calendar` | Integrated |
| Perkuliahan | Mata Kuliah | Mata Kuliah (`/s/courses`) | `GET /student/courses` | Integrated |
| Perkuliahan | Materi | Materi (`/s/materials`) | `GET /student/materials` | Integrated |
| Perkuliahan | Tugas | Tugas (`/s/assignments`) + pengumpulan | `/student/assignments*` | Integrated |
| Ujian | Ujian | Ujian (`/s/exams`), pengerjaan, hasil | `/student/exams*`, `/student/exam-attempts*` | Integrated |
| Keuangan | Keuangan | Tab Keuangan (`/s/finance`): tagihan, bayar, mutasi, UKT, cekal | `/student/finance/*` | Integrated |
| Keuangan | — (mockup: Pembayaran) | Pembayaran → `/s/finance` | `/student/finance/*` | Integrated |
| Kemahasiswaan | — (mockup: Beasiswa) | Beasiswa & potongan (`/s/finance/awards`) | `/student/finance/*` | Integrated |
| Kemahasiswaan | — (mockup: Kegiatan, organisasi, poin) | Halaman "Menunggu backend" (`/pending/feature/student_affairs`) | Belum ada | Not Implemented (berlabel, tanpa data tiruan) |
| Layanan | Pengajuan | Layanan Administrasi (`/s/requests`) | `/student/requests*` | Integrated |
| Layanan | Pengumuman | Pusat Informasi / Pengumuman (`/s/announcements`) | `GET /student/announcements` | Partially Integrated (tidak ada gambar di API; kategori belum ditampilkan) |
| Layanan | Notifikasi | Notifikasi (`/notifications`) | `/notifications*` | Integrated |
| Layanan | — (mockup: Perpustakaan) | Tidak ditampilkan | `books.read` tidak dimiliki role `student` | Blocked (izin backend, §5) |
| Pengaturan | Pengaturan | Pengaturan (`/settings`), sesi (`/settings/security`), preferensi notifikasi (`/notifications/preferences`) | Lihat 02-matriks `MOB-AUTH-11`, `MOB-PLAT-15` | Integrated (visual sesuai mockup, §3) |
| Pengaturan | — (mockup: Kebijakan privasi) | Entri "Belum tersedia" (`/pending/feature/privacy_consent`) | Belum didefinisikan | Not Implemented (`MOB-AUTH-21`) |
| Pengaturan | — (mockup: Verifikasi akun, Pusat bantuan) | Tidak ditampilkan | Tidak ada backend atau sumber konten | Not Implemented (§5) |
| Notifikasi push | — | FCM | Perlu endpoint token perangkat di backend | **Blocked** (§7) |

## 3. Mockup → implementasi

| Bagian mockup | Implementasi di Android | Status |
|---|---|---|
| Header: logo UAJ, notifikasi | AppBar: `BrandAppBarTitle` dan `NotificationBellButton` | Sesuai |
| Hero foto kampus, sapaan, nama, deskripsi | `CampusHeroBanner` (`core/widgets/campus_hero.dart`) dengan foto `assets/images/kampus_fasad.webp` (salinan dari `frontend/src/landing/assets/photos/`) dan gradien navy. Tinggi minimum 260 dp, tumbuh mengikuti teks saat skala font besar | Sesuai |
| Kartu semester (periode, "Aktif hingga …") | `SemesterCard` di tepi bawah hero: nama periode, "Aktif hingga {tanggal}" dari `academic.current_term.end_date`, dan status KRS | Sesuai |
| Ringkasan akademik | Tetap: IPK, IPS, SKS lulus, progres SKS, dosen wali | Sesuai |
| Menu cepat 3×2 | Akademik, Keuangan, Kemahasiswaan, Layanan Administrasi, Jadwal, Lainnya | Dimodifikasi: "Perpustakaan" diganti "Jadwal" (§5) |
| Pengumuman terbaru | Judul "Pengumuman terbaru", thumbnail ikon, chip "Disematkan" dan "x belum dibaca" | Partially: `AnnouncementResource` tidak memuat gambar, dan kategori belum ditampilkan (yang ada: `target_scope`, `audience`) |
| Agenda | Jadwal hari ini dan kelas berikutnya di beranda | Sesuai sebagai bagian beranda |
| Navigasi bawah: Beranda · Notifikasi · Agenda · Profil | Tetap: Beranda · Akademik · Kuliah · Keuangan · Akun | **Keputusan menunggu** (§5) |
| Menu Utama: header, pencarian, chip, kartu | `StudentMenuScreen` (`/s/menu`): pencarian, chip Semua / Akademik / Kemahasiswaan / Layanan, kartu `ServiceCard` dengan ikon, deskripsi, chevron, label "Segera hadir" | Sesuai |
| Pengaturan: kartu profil, akun & keamanan, preferensi, bantuan | `SettingsScreen`: kartu profil, grup "Akun & keamanan" (ubah profil, ganti kata sandi, sesi), grup "Preferensi aplikasi" (mode tampilan, bahasa, biometrik, pengaturan notifikasi), grup "Bantuan & informasi" (tentang, kebijakan privasi) | Sesuai, kecuali Verifikasi akun dan Pusat bantuan (§5) |
| Hub modul (Akademik, Kuliah, Keuangan, Akun, beranda dosen) | `ServiceCard`: kartu seragam dengan Menu Utama, termasuk entri "Menunggu backend" | Sesuai. Modul teaching dan PMB dosen masih `MenuTile` (§7) |
| Palet warna | `core/theme/app_colors.dart` sudah memakai nilai mockup: terang #EA5B0C, #1E293B, #FFF7ED, #10B981, #3B82F6, #EF4444; gelap #101318, #1C222C, #252C38, #F8FAFC, #A1AAB8, #343B47, #FF791F | Sesuai (dicek di kode) |

## 4. Sinkronisasi API web ↔ Android

Endpoint berikut dipakai **oleh kedua platform** dengan path yang sama, sehingga data yang diubah di web terbaca di Android saat dimuat ulang:

- Beranda dan lonceng: `/student/dashboard`, `/notifications/unread-count`, `/notifications`
- Akademik: `/student/academic-summary`, `/student/academic-history`, `/student/khs`, `/student/grades`, `/student/transcript`, `/student/documents`, `/student/profile`
- Kuliah: `/student/schedule`, `/student/calendar`, `/student/attendance`, `/student/courses`, `/student/materials`, `/student/assignments`
- KRS: `/student/krs`, `/student/krs/offerings`, `/student/krs/history`, `/student/krs/items`, `/student/krs/submit`, `/student/krs/cancel`
- Layanan: `/student/requests`, `/student/requests/options`, `/student/announcements`

Yang **belum** sama: `/student/exams*` dan `/student/finance/*` dipakai Android, tetapi tidak tercantum di layanan web mahasiswa yang diaudit (`studentPortalService.ts`); keduanya ada di backend. Pemeriksaan kesetaraannya belum dilakukan.

Tahap ini **tidak** menambah endpoint, tidak mengubah kontrak API, dan tidak mengubah basis data. Sinkronisasi real-time tidak diasumsikan: data diperbarui saat beranda ditarik ulang (pull-to-refresh) atau modul dibuka ulang.

## 5. Keputusan dan alasannya

1. **Perpustakaan tidak ditampilkan untuk mahasiswa.** Rute `GET /books` memerlukan `books.read`, dan permission itu tidak ada di role `student` (`OrganizationalRoleSeeder`). Menampilkannya berarti menampilkan fitur yang akan ditolak backend. Jika mahasiswa perlu katalog, itu perubahan izin di backend dan memerlukan persetujuan.
2. **Slot "Perpustakaan" di menu cepat diganti "Jadwal".** Mockup memuat enam slot; untuk mahasiswa slot Perpustakaan tidak tersedia.
3. **Menu Utama memfilter layanan dengan permission yang sama seperti menu portal web.** Ini hanya penyaringan tampilan. Otorisasi tetap di backend.
4. **Navigasi bawah tetap lima tab.** Mockup menampilkan empat tab, tetapi mengubahnya berarti menghilangkan akses langsung ke Akademik atau Keuangan dari bawah. Aturan proyek melarang menghapus fitur yang sudah ada. **Perlu keputusan Anda:** (a) tetap lima tab, atau (b) empat tab dengan Akademik dan Kuliah dipindah ke Menu Utama.
5. **Foto hero diambil dari aset landing web** yang sudah ada di repo, bukan dari sumber luar. Mockup tidak menyertakan berkas foto asli.
6. **Modul One di web tidak dijadikan acuan logika bisnis.** Modul itu berjalan di atas data mock.
7. **Verifikasi akun dan Pusat bantuan tidak ditampilkan.** Keduanya tidak punya backend atau sumber konten di repo; halaman bantuan di web (`one/pages/Support.tsx`) hanya mock. Kebijakan privasi ditampilkan sebagai entri "Belum tersedia" yang sudah tercatat di matriks (`MOB-AUTH-21`).
8. **Hero beranda (mahasiswa dan dosen) memakai satu widget `CampusHeroBanner`.** Hero tumbuh mengikuti teks, sehingga tidak overflow saat skala font besar atau nama panjang.
9. **Layanan Akun ikut memakai `ServiceCard`.** Sebelumnya baris `MenuTile` di tab Akun menempel ke tepi layar, sehingga ikon terpotong. Cacat ini sudah ada di `HEAD`; gutter sekarang disediakan oleh `ListView`.

## 6. Verifikasi

- `dart format`: bersih. `flutter analyze`: tanpa isu.
- `flutter test`: **359 lulus**, naik dari 332 pada baseline (+27 tes): 8 untuk Menu Utama, 8 untuk Pengaturan (termasuk tiga ukuran layar), 1 navigasi "Kemahasiswaan", dan 10 tes responsif.
- **Tes responsif** (`student_home/responsive_test.dart`): beranda dan Menu Utama di 360×640, 411×891, dan 800×1280, dalam mode terang dan gelap, serta nama panjang dengan skala teks 1.3 di 360 dp. Tes ini juga memastikan lebar hero sama dengan lebar konten. Font Plus Jakarta Sans dimuat di tes itu, karena font default tes (Ahem) jauh lebih lebar dan menghasilkan overflow palsu.
- **Bug yang ditemukan lewat verifikasi dan sudah diperbaiki:** hero overflow saat skala font besar; hero menyusut ke lebar teks; chip "Menunggu backend" overflow di layar sempit; baris Bahasa overflow karena nilai di kanan; gutter tab Akun hilang.
- **Build APK debug** (flavor dev): berhasil.
- **Perangkat** (`emulator-5554`, Android arm64): sesi mahasiswa yang sudah ada di data emulator dipakai untuk memeriksa beranda (hero, kartu semester, menu cepat 3 kolom, ringkasan akademik), Menu Utama, tab Akun, dan Pengaturan. Backend lokal `mobilelocal` (SQLite) dijalankan sementara untuk pengujian dan sudah dihentikan.
- **Belum dilakukan:** beranda dosen di perangkat (perlu login dosen; sesi emulator saat ini mahasiswa), hub modul di perangkat, layar tablet di perangkat, dan uji sinkronisasi dua arah web ↔ Android (memerlukan web yang berjalan dan perubahan data lintas platform).

## 7. Belum selesai

1. **Profil detail** (`academic_records/profile_screen.dart`) belum disesuaikan visualnya. Kartu profil di Pengaturan dan Akun sudah sesuai.
2. **Modul teaching dan PMB dosen** (`teaching/*`, `pmb_duty/*`) masih memakai `MenuTile`. Layar Layanan (pengajuan) sudah memakai kartu `AppSurfaceCard` dan tidak diubah.
3. **Beranda dosen:** hero sudah sama dengan mahasiswa, tetapi belum diverifikasi di perangkat.
4. **Gambar pengumuman** di `AnnouncementResource`, bila mockup ingin foto sungguhan dan bukan ikon. Ini perubahan kontrak API dan memerlukan persetujuan.
5. **Keputusan navigasi bawah** (§5.4) dan **izin katalog Perpustakaan** untuk mahasiswa (§5.1).
6. **Notifikasi push (FCM): Blocked.** Prasyaratnya belum ada di repo: tidak ada `google-services.json` di `mobile/android/app/`, plugin Firebase belum dikonfigurasi di Gradle maupun `pubspec.yaml`, dan backend belum punya endpoint token perangkat (`app/Modules/*/Routes`). Ini butuh proyek Firebase milik tim serta perubahan kontrak API yang perlu persetujuan.
7. **Uji sinkronisasi lintas platform** dan **layar tablet di perangkat**, serta pemeriksaan mode gelap di perangkat untuk semua layar.
