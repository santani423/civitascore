# Arsitektur Aplikasi Android Civitas One (Mahasiswa & Dosen)

> **Per:** 18 Oktober 2026. **Kode:** `mobile/` (paket Dart `civitasone`). **Target fase ini:** Android. iOS menyusul — arsitektur sengaja tidak memakai API khusus Android di logika inti (bagian khusus platform dibungkus, mis. `SecureScreen`, `AppLockController`).
> Dokumen ini menjelaskan keputusan desain yang diambil dari audit (`01-audit-dokumentasi.md`), matriks fitur (`02-matriks-fitur.md`), dan kontrak backend (`03-integrasi-backend-dan-celah-api.md`).

## 1. Teknologi

| Lapisan | Pilihan | Alasan |
|---|---|---|
| Framework | Flutter 3.44 / Dart 3.12, Material 3 | Melanjutkan proyek `mobile/` yang ada; satu basis kode untuk Android (dan iOS nanti). |
| State management | Riverpod 2.6 (`Provider`, `FutureProvider`, `Notifier`, `AsyncNotifier`) | Sudah dipakai scaffold; DI dan state terpusat, mudah di-*override* saat tes. |
| Navigasi | go_router 18 (`StatefulShellRoute` per peran) | Bottom navigation dengan riwayat per tab, redirect terpusat. |
| HTTP | Dio + `ApiClient` (envelope Civitas One) | Interceptor token/tenant/bahasa, pemetaan galat ke `ApiFailure`. |
| Penyimpanan aman | `flutter_secure_storage` (Android Keystore) | Token Sanctum, ID universitas, salinan `/me`, kunci enkripsi cache. |
| Cache offline | Hive CE terenkripsi AES-256 (`cache_responses_v1`) | Data baca milik pengguna tetap tampil saat offline; dihapus saat logout. |
| Preferensi | SharedPreferences | Bahasa, tema, kunci biometrik, peran aktif (bukan data sensitif). |
| Model | Ditulis manual (`fromJson` toleran) | Tanpa *codegen* sehingga modul dapat dikerjakan paralel; tahan terhadap angka-sebagai-string dan `[]` dari PHP. |

## 2. Struktur folder

```
mobile/lib/
  main_dev.dart · main_staging.dart · main_prod.dart · bootstrap.dart · app.dart
  core/
    config/       Env (API_BASE_URL, UNIVERSITY_CODE, UNIVERSITY_ID), Flavor
    l10n/         AppLanguage (id/en/zh), LocaleController, CoreStrings, pick()
    network/      ApiClient, ApiFailure, envelope & pembaca JSON, Dio, konektivitas
    storage/      secure storage, prefs, Hive (cache terenkripsi)
    session/      AppExperience (mahasiswa/dosen), sinyal 401
    security/     AppLockController (biometrik), SecureScreen (FLAG_SECURE)
    router/       RoutePaths (semua rute), app_router (redirect), shells (bottom nav)
    pending/      katalog fitur "Menunggu backend" (tanpa data tiruan)
    theme/        token warna terang/gelap, AppPalette (warna semantik), tipografi
    utils/        formatter tanggal/uang/angka, validator terlokalisasi, aksi berkas
    widgets/      state views, ui kit, daftar berpaginasi, banner offline, lonceng notifikasi
  features/<modul>/
    <modul>_routes.dart        layar tab + daftar GoRoute modul
    data/<modul>_api.dart      pemanggilan endpoint
    domain/*.dart              model + label status terlokalisasi
    presentation/              providers, screens, widgets
    l10n/<modul>_strings.dart  string ID/EN/ZH modul
```

Modul fitur: `auth`, `account`, `student_home`, `academic_records`, `schedule_attendance`, `krs`, `learning`, `exam_student`, `finance`, `student_services`, `announcements`, `notifications`, `lecturer_home`, `teaching`, `lecturer_assessment`, `exam` (ujian dosen), `advising`, `hr`, `pmb_duty` (tugas ujian PMB: pengawas/penguji/pewawancara).

## 3. Alur data

```
Widget ──watch──▶ Provider (Riverpod) ──▶ <Modul>Api ──▶ ApiClient ──▶ Dio + interceptor ──▶ /api/v1/...
   ▲                    │                                   │
   └── AsyncValue ◀─────┘          cache Hive (GET cache:true) ◀┘  (dipakai saat offline)
```

- **Interceptor**: `Authorization: Bearer <token>`, `X-University-ID` (dari `--dart-define` atau `GET /tenant/profile` setelah login), `Accept-Language`, `X-Device-Platform`. Respons 401 pada request ber-token memicu `unauthorizedSignalProvider` → sesi lokal dihapus.
- **Galat**: semua kegagalan menjadi `ApiFailure` (`kind`, `code` bisnis seperti `ACADEMIC_BLOCKED`, `fieldErrors` 422). Pesan transport (offline, timeout, server) diterjemahkan aplikasi; pesan bisnis backend (hanya berbahasa Indonesia) ditampilkan apa adanya.
- **Unggah**: dua langkah sesuai backend — `POST /file-uploads` (field `file`) lalu ID berkas dikirim ke endpoint domain.
- **PDF** (KHS, transkrip, KRS, kuitansi, surat, hasil ujian): diunduh dengan header Bearer ke direktori sementara lalu dibuka dengan penampil sistem.

## 4. Sesi, peran, dan keamanan

1. **Login** `POST /login`: mahasiswa dengan **NIM** (+ `university_code` dari build), dosen dengan **email**; identitas perangkat (`device_identifier` UUID tersimpan aman, `X-Device-Name`). Setelah token diterima → `GET /me` (peran & izin) → `GET /tenant/profile` (ID universitas) → cache lama dihapus.
2. **Pemulihan sesi**: saat aplikasi dibuka, token divalidasi ulang dengan `/me`. Bila server tak terjangkau, sesi dipulihkan dari salinan `/me` terakhir (**tidak logout saat offline** — memperbaiki perilaku scaffold lama). 401/403 → logout lokal.
3. **Ganti sandi wajib** (`must_change_password`): router memaksa layar ganti sandi sebelum shell.
4. **Pengalaman per peran** (`AppExperience`): mahasiswa = role `student`; dosen = izin `lecturer_profile.read`; dosen PA = izin `krs_advising.read`. Akun dengan dua pengalaman dapat beralih di tab Akun. Akun tanpa keduanya melihat layar "Akun belum didukung".
5. **Otorisasi UI**: tab dan aksi disembunyikan bila izin tidak ada; rute sensitif dibungkus `PermissionGate` (semantik OR seperti middleware backend). Otorisasi sebenarnya tetap di backend.
6. **Kunci biometrik** opsional (`local_auth`): aplikasi terkunci saat dibuka dan setelah 2 menit di latar belakang; token tetap di Keystore.
7. **FLAG_SECURE** (`SecureScreen`) pada layar pengerjaan ujian — mencegah tangkapan/rekaman layar.
8. **Android**: `allowBackup=false` + `data_extraction_rules` (token & cache tidak ikut cadangan), cleartext hanya untuk `10.0.2.2`/`localhost` (dev), signing rilis dari `android/key.properties`.

## 5. Navigasi

| Shell | Tab (urutan) | Catatan |
|---|---|---|
| Mahasiswa (`/s/...`) | Beranda · Akademik · Kuliah · Keuangan · Akun | Layar detail di-*push* di atas bottom bar. |
| Dosen/PA (`/l/...`) | Beranda · Mengajar · Jadwal · Perwalian · Akun | Mengajar & Jadwal hanya bila dosen; Perwalian hanya bila `krs_advising.read`. |
| Bersama | Notifikasi, Pengaturan, Sesi & perangkat, Fitur menunggu backend | Dapat dibuka dari kedua shell. |
| Tugas ujian PMB (`/l/pmb/...`) | Daftar sesi → detail & presensi → input nilai | **Modul kondisional** (MOB-OTH-01..03): entri di Beranda dosen hanya tampil bila `GET /pmb/admin/examiner/sessions` tidak kosong dan pengguna memiliki `pmb_scores.create`/`pmb_exams.update`. Pembatasan per sesi tetap di backend (`ExamService::assertSessionStaff`). |

Redirect terpusat (`resolveRedirect`, diuji unit): splash saat memulihkan sesi → login → kunci biometrik → ganti sandi wajib → tanpa-akses → beranda sesuai peran; pengalaman mahasiswa tidak dapat membuka rute `/l/...` dan sebaliknya.

## 6. Design system

- Token warna mengikuti `docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md` §5.1 (hijau `#166534`, aksen oranye `#D9791A`), selaras dengan logo Atma Jaya. Tema gelap memakai palet tersendiri.
- `AppPalette` (ThemeExtension) menyediakan warna semantik (sukses, peringatan, bahaya, info, netral, aksen) untuk chip status dan banner; widget **dilarang** memakai warna *hardcoded*.
- Komponen bersama: `AsyncValueView`, `PagedListView` (pull-to-refresh + infinite scroll), `SkeletonList`, `EmptyView`, `ErrorView`, `StatusChip`, `AppBanner`, `InfoRow`, `StatTile`, `MenuTile`, `SectionHeader`, `NotificationBellButton`, `OfflineBanner`.
- Responsif: tidak boleh *overflow* pada 360×640; pada layar ≥ 600 dp konten dibatasi lebar/ditata grid.
- Font platform (Roboto + fallback Noto CJK untuk Mandarin) — tidak ada unduhan font saat runtime.

## 7. Multibahasa

- Bahasa: Indonesia (default), English, 简体中文. Dipilih dari layar login atau Pengaturan; tersimpan per perangkat.
- Pola string bertipe per modul: `pick(context, id: …, en: …, zh: …)` — ketiga terjemahan wajib (galat kompilasi bila terlewat).
- Tanggal/angka diformat sesuai bahasa (`intl`); uang selalu Rupiah (`Rp 1.500.000`).
- Keterbatasan: pesan galat bisnis dan label status dari backend hanya tersedia dalam bahasa Indonesia; status yang dikenal dipetakan ke label terjemahan di klien.

## 8. Offline

- Banner global saat tidak ada koneksi.
- GET data milik pengguna memakai `cache: true`: bila jaringan gagal, respons terakhir dari cache terenkripsi ditampilkan.
- Aksi tulis (KRS, pembayaran, pengajuan, nilai, presensi) membutuhkan koneksi; kegagalan ditampilkan dengan pesan yang jelas.
- Pengecualian: respons `/pmb/admin/*` (tugas ujian PMB) berisi nama pendaftar dan dikirim dengan `Cache-Control: no-store`, sehingga **tidak** disimpan ke cache offline.

## 8a. Tugas ujian PMB (`pmb_duty`)

- **Presensi** (pengawas/penguji/pewawancara, `pmb_exam_attendance.update`): pindai QR kartu ujian (`mobile_scanner`, mode beruntun untuk antrean peserta), masukkan kode secara manual, atau pilih status per peserta. Format token kartu (`participantId.sessionId.hmac32`) divalidasi di klien sebelum memanggil `POST …/check-in`. Status *Tidak hadir* dan *Diskualifikasi* meminta konfirmasi karena mengubah status seleksi dan mengirim notifikasi ke pendaftar. Lebih dari 2 jam setelah sesi berakhir, layar menjadi baca-saja (aturan backend; hanya panitia). Daftar hadir PDF dibuka lewat URL bertanda tangan berumur pendek.
- **Nilai** (hanya penguji/pewawancara): tiga bentuk komponen — angka dengan rentang `raw_min..raw_max`, lulus/tidak (`pass_fail`), dan rubrik berbobot (pratinjau nilai = rata-rata berbobot, rumus sama dengan `SelectionCalculator::rubricRaw`). Disimpan per peserta (`PUT …/scores` satu baris) dengan alur "Simpan & berikutnya" (peserta berikutnya dicari berdasarkan ID, berhenti bila sesi terkunci); rentang divalidasi di klien karena satu baris salah menolak seluruh batch. Input angka: maks. 2 desimal, `.`/`,` sebagai pemisah desimal, pemisah ribuan ditolak. Galat simpan (423 terkunci, 403, `saved=0`) tampil **di dalam** sheet. Sesi yang dikunci panitia menjadi baca-saja.
- **Skor milik sendiri:** respons nilai bisa memuat baris penguji lain (pemegang `pmb_exams.update`) dan baris bersama hasil impor (`examiner_user_id` null). Komponen wawancara/portofolio hanya memakai baris `examiner_user_id` = pengguna; komponen lain memakai baris bersama. Ringkasan "x/y dinilai" hanya menghitung peserta yang dapat dinilai.
- **Pemindai beruntun:** kartu yang sudah berhasil di-check-in tidak dikirim ulang selama layar pemindai terbuka (tiap panggilan menimpa `checked_in_at` dan menambah jejak audit).

## 9. Fitur tanpa backend

Fitur yang terdokumentasi tetapi belum memiliki backend (EDOM, semester pendek, poin kemahasiswaan, yudisium/wisuda, skripsi, presensi QR, jurnal mengajar, komponen nilai, daftar mahasiswa bimbingan, pengumuman dosen, notifikasi push, dll.) ditampilkan sebagai entri berlabel **"Menunggu backend"** yang menjelaskan endpoint yang dibutuhkan dan sumber dokumentasinya (`core/pending/pending_features.dart`). **Tidak ada data tiruan** di aplikasi.

## 10. Pengujian

- Unit: `ApiClient`/`ApiFailure`, validator, redirect router, pemetaan model per modul.
- Kontrak API: tiap modul menguji pemanggilan endpoint & parsing dengan `FakeApi` (adapter HTTP khusus tes) memakai JSON berbentuk persis respons backend.
- Widget: layar utama per modul dalam ID/EN/ZH, mode gelap, dan ukuran layar Android (360×640, 411×891, 800×1280).
- Integrasi: `integration_test` dijalankan di emulator Android (lihat `05-panduan-setup-dan-pengujian.md`).
