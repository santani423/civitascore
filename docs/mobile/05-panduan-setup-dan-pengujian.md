# Panduan Setup & Pengujian Aplikasi Mobile Civitas One

> **Per:** 27 Oktober 2026. Melengkapi `04-arsitektur-mobile.md` §10. Perintah dijalankan dari folder `mobile/` kecuali disebut lain.

## 1. Prasyarat

- Flutter 3.44 / Dart 3.12 (`flutter --version`), Android SDK + emulator (AVD arm64 untuk Mac Apple Silicon).
- `flutter pub get` setelah menarik perubahan `pubspec.yaml`.

## 2. Pemeriksaan kualitas (sama dengan CI `mobile-ci.yml`)

```bash
dart format --output=none --set-exit-if-changed lib test
flutter analyze
flutter test                                   # unit + kontrak API + widget
flutter test integration_test/app_flow_test.dart --flavor dev -d emulator-5554
```

Integration test memakai `FakeApi` (tanpa backend) dan memasang build uji di emulator; pasang ulang APK dev setelahnya bila ingin mencoba aplikasi secara manual.

## 3. Menjalankan di emulator

```bash
flutter emulators                       # daftar AVD
flutter emulators --launch flutter_arm64
flutter build apk --flavor dev -t lib/main_dev.dart --debug --dart-define=UNIVERSITY_CODE=UAJ
adb install -r build/app/outputs/flutter-apk/app-dev-debug.apk
```

Atau `flutter run --flavor dev -t lib/main_dev.dart --dart-define=UNIVERSITY_CODE=UAJ` untuk hot reload. Flavor dev menuju `http://10.0.2.2:8000/api/v1` (alias `localhost` mesin host dari emulator). `UNIVERSITY_CODE` hanya dipakai login mahasiswa (NIM); dosen masuk dengan email.

## 4. Backend lokal terisolasi (WAJIB dibaca)

`.env` di root repo **mengarah ke basis data produksi**. Jangan menjalankan `php artisan serve`, `migrate`, atau `db:seed` dengan `.env` tersebut untuk menguji aplikasi — login, presensi, dan input nilai dari emulator akan menulis ke data produksi.

Gunakan berkas lingkungan terpisah. Laravel memuat `.env.{APP_ENV}` **sebagai pengganti** `.env` bila variabel proses `APP_ENV` diset dan berkasnya ada, dan `artisan serve` meneruskan `APP_ENV` ke proses server.

1. Buat `.env.mobilelocal` di root repo (jangan di-commit; tambahkan ke `.git/info/exclude`):

   ```dotenv
   APP_NAME=CivitasOne
   APP_ENV=mobilelocal
   APP_KEY=            # isi: php -r 'echo "base64:".base64_encode(random_bytes(32));'
   APP_DEBUG=true
   APP_URL=http://10.0.2.2:8000   # URL bertanda tangan (PDF) dapat dibuka dari emulator
   APP_LOCALE=en
   APP_FALLBACK_LOCALE=en
   LOG_CHANNEL=stack
   LOG_STACK=single
   DB_CONNECTION=sqlite
   DB_DATABASE=/path/absolut/ke/app/database/mobilelocal.sqlite
   SESSION_DRIVER=file
   CACHE_STORE=file
   QUEUE_CONNECTION=sync
   MAIL_MAILER=log
   SUPER_ADMIN_EMAIL=admin@civitasone.test
   SUPER_ADMIN_PASSWORD=password
   ```

2. Verifikasi **sebelum** menulis apa pun — `database.default` harus `sqlite` dan host mysql harus kembali ke default `127.0.0.1` (bukti `.env` produksi tidak dimuat):

   ```bash
   touch database/mobilelocal.sqlite            # *.sqlite* sudah di-ignore git
   APP_ENV=mobilelocal php artisan config:show database.default
   APP_ENV=mobilelocal php artisan config:show database.connections.mysql.host
   ```

3. Migrasi, seed, dan jalankan server:

   ```bash
   APP_ENV=mobilelocal php artisan migrate --database=sqlite --no-interaction
   APP_ENV=mobilelocal php artisan db:seed --database=sqlite --no-interaction
   APP_ENV=mobilelocal php artisan serve --host=127.0.0.1 --port=8000
   ```

4. Setelah login pertama dari emulator, pastikan token tercatat di SQLite lokal: `sqlite3 database/mobilelocal.sqlite "select count(*) from personal_access_tokens;"`.

### Akun demo (hasil seeder)

| Akun | Kata sandi | Keterangan |
|---|---|---|
| `dosen@atmajaya.com` | `password` | Dosen + Dosen PA, Universitas Atma Jaya (UAJ) |
| `dosenpa@atmajaya.com` | `password` | Dosen PA, UAJ |

Seeder bawaan membuat sesi ujian PMB Atma Jaya beserta penugasan `dosen@atmajaya.com` (pengawas CBT Lab 1 dan pewawancara Jalur Prestasi, tiga gelombang). Sesi Gelombang 1 sudah selesai dan nilainya terkunci; sesi Gelombang 2 berisi peserta terjadwal (CBT dan wawancara) untuk mencoba modul *Tugas ujian PMB*. Daftar akun pendaftar demo ada di [README](../../README.md#demo-pmb--universitas-atma-jaya-uaj).

## 5. Keterbatasan emulator

- Kamera virtual AVD sering tidak tersedia, sehingga pratinjau pemindai QR hitam; gunakan ikon keyboard ("Masukkan kode") atau uji di perangkat fisik.
- `adb shell input text` dapat menjatuhkan karakter pada string panjang (mis. token QR kartu); masukkan manual atau lewat papan klip.
- Build debug pertama kali lambat dibuka (±15 detik) di emulator.
