# CivitasOne — Mobile App

Aplikasi Android CivitasOne (Sistem Informasi Akademik Universitas multi-tenant),
dibangun dengan Flutter. Rancangan lengkap (arsitektur, design system, seluruh
71 halaman, API, keamanan, rencana fase) ada di
[`../docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md`](../docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md) —
dokumen ini hanya ringkasan praktis untuk menjalankan proyek.

## Status

**Fase 0 — Bootstrap** selesai: struktur proyek, 3 flavor (dev/staging/prod),
`core/network` (Dio + interceptor auth), `core/storage` (secure storage,
shared_preferences, Hive), `core/theme` (light/dark, Material 3), `core/router`
(go_router, route guard publik/terproteksi), dan modul **Auth** penuh (Login,
Lupa Kata Sandi, Atur Ulang Kata Sandi, Keamanan/sesi & perangkat).

Sisanya (Dashboard penuh, modul Akademik/Keuangan/dst., fitur Android modern,
i18n) menyusul bertahap sesuai BAB 12 dokumen rancangan.

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

Backend Laravel (di root repo `../`) harus berjalan (`php artisan serve`) agar
alur login bisa diuji end-to-end.

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
