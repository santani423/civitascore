# CivitasOne — Mobile App

Aplikasi Android CivitasOne (Sistem Informasi Akademik Universitas multi-tenant),
dibangun dengan Flutter. Rancangan lengkap (arsitektur, design system, seluruh
71 halaman, API, keamanan, rencana fase) ada di
[`../docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md`](../docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md) —
dokumen ini hanya ringkasan praktis untuk menjalankan proyek.

## Status

Aplikasi mahasiswa & dosen (Android) dengan 19 modul fitur yang terintegrasi ke
backend: auth & sesi, beranda per peran, akademik (KHS, transkrip, dokumen),
jadwal & presensi, KRS, perkuliahan/LMS, ujian (mahasiswa & dosen, bank soal,
laporan attempt), keuangan, layanan administrasi, pengumuman & notifikasi,
perwalian KRS, layanan mandiri SDM, dan **tugas ujian PMB** (pengawas/penguji:
presensi via QR kartu & input nilai). Tiga bahasa (ID/EN/ZH), tema terang/gelap,
cache offline terenkripsi. Fitur yang backend-nya belum ada ditampilkan sebagai
"Menunggu backend" tanpa data tiruan.

Dokumentasi mobile: [`../docs/mobile/`](../docs/mobile/) — audit (01), matriks
fitur (02), kontrak API & celah backend (03), arsitektur (04), setup & pengujian (05).

## Tech stack

- Flutter (SDK `^3.12.2`, Dart) + Material 3
- State management: Riverpod (`flutter_riverpod`)
- Routing: `go_router`
- Networking: `dio`
- Storage: `flutter_secure_storage` (token), `shared_preferences` (preferensi),
  `hive_ce` (cache data referensi)

## Menjalankan proyek

```bash
flutter pub get

# Jalankan flavor dev (default target backend: http://10.0.2.2:8000/api/v1,
# alias localhost dari emulator Android)
flutter run --flavor dev -t lib/main_dev.dart

# Arahkan ke backend lain bila perlu:
flutter run --flavor dev -t lib/main_dev.dart \
  --dart-define=API_BASE_URL=http://192.168.1.10:8000/api/v1
```

Backend Laravel (di root repo `../`) harus berjalan agar alur login bisa diuji
end-to-end. **Jangan** memakai `.env` root apa adanya — isinya mengarah ke basis
data produksi. Jalankan backend lokal terisolasi (SQLite, `APP_ENV=mobilelocal`)
sesuai [`../docs/mobile/05-panduan-setup-dan-pengujian.md`](../docs/mobile/05-panduan-setup-dan-pengujian.md) §4.

## Build

```bash
# Debug APK (uji lokal)
flutter build apk --flavor dev -t lib/main_dev.dart --debug

# Release (lihat BAB 13.3 untuk signing config produksi)
flutter build appbundle --flavor prod -t lib/main_prod.dart --release \
  --obfuscate --split-debug-info=build/debug-info \
  --dart-define=API_BASE_URL=https://api.civitasone.example/api/v1
```

## Kualitas kode

```bash
dart format --output=none --set-exit-if-changed lib test
flutter analyze
flutter test
```

CI (`.github/workflows/mobile-ci.yml`) menjalankan ketiga perintah di atas plus
build debug APK pada setiap push/PR yang menyentuh folder `mobile/`.
