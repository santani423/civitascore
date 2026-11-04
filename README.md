# CivitasOne

Sistem Manajemen Akademik Universitas — **multi-tenant** (banyak universitas dalam satu instalasi), backend Laravel (API) + frontend React (SPA). Lihat [docs/RANCANGAN-APLIKASI.md](docs/RANCANGAN-APLIKASI.md) untuk cakupan bisnis lengkap, [docs/MULTI-TENANT-ARCHITECTURE.md](docs/MULTI-TENANT-ARCHITECTURE.md) untuk arsitektur tenant isolation, [docs/FRONTEND-IMPLEMENTASI.md](docs/FRONTEND-IMPLEMENTASI.md) untuk status implementasi frontend, dan [docs/BLACK-BOX-TESTING.md](docs/BLACK-BOX-TESTING.md) untuk daftar skenario uji per modul.

## Akun Sample (Login)

> **Jangan tertukar**: setiap universitas juga punya field `email` sendiri (mis. `info@undigital.test`, terlihat di halaman Manajemen Universitas) — itu cuma alamat kontak institusi, **bukan akun login**. Akun login selalu berupa `User` terpisah seperti `admin@undigital.test` pada tabel di bawah.

### Super Admin (platform, lintas-tenant)

| Email | Password |
|---|---|
| `superadmin@civitasone.local` | `ChangeMe123!` |

Kredensial dari `SUPER_ADMIN_EMAIL` / `SUPER_ADMIN_PASSWORD` di `.env` (lihat `config/app.php` → `super_admin`). Akses `/api/v1/platform/*`, bypass seluruh isolasi tenant.

### Akun baseline (tanpa universitas)

| Email | Password | Role | Akses |
|---|---|---|---|
| `staff@civitasone.test` | `password` | `staff` | Tidak ada permission — login berhasil, tapi semua endpoint admin ditolak (403). Dipakai untuk membuktikan RBAC menolak dengan benar, bukan cuma "belum login". |

### Akun per universitas (3 universitas demo, password semua `password`)

Setiap universitas punya **satu akun untuk setiap role organisasi** yang ada di sistem (bukan cuma 4 role "headline" seperti sebelumnya) — total 15 akun × 3 universitas. Formatnya `<local-part>@<domain>`; domain masing-masing universitas:

| Universitas | Domain |
|---|---|
| Universitas Nusantara Digital | `undigital.test` |
| Institut Teknologi Mandala | `itmandala.test` |
| STIKes Sejahtera | `stikessejahtera.test` |

| Local-part | Role | Nama akun (UND) | Akses (permission) |
|---|---|---|---|
| `owner` | University Owner | Owner UND | `tenant_profile.*`, `roles.read`, `user_roles.read/create`, `audit_logs.read`, `system_settings.*`, `feature_flags.*`, `approval_workflows.create/read/delete`, `users.read` — kontrol penuh atas tenant |
| `admin` | University Administrator | Admin UND | `tenant_profile.*`, `roles.read`, `user_roles.read/create`, `audit_logs.read`, `approval_workflows.read`, `users.read` — operasional tenant, tanpa ubah pengaturan/feature flag |
| `rektor`¹ | Rektor | Rektor UND | `audit_logs.read`, `approval_requests.read`, `users.read` — pengawasan tertinggi |
| `wakilrektor` | Wakil Rektor | Wakil Rektor UND | `approval_requests.read`, `users.read` |
| `dekan` | Dekan | Dekan UND | `approval_requests.read`, `users.read` |
| `kaprodi` | Ketua Program Studi | Ketua Program Studi UND | `approval_requests.read`, `users.read` |
| `akademik` | Bagian Akademik | Bagian Akademik UND | `approval_requests.read`, `notification_templates.create/read/update`, `file_uploads.read`, `users.read`, `krs.create/update` — bisa mendaftarkan/membatalkan KRS mahasiswa |
| `dosen` | Dosen | Dosen UND | `students.read`, `study_programs.read`, `classes.read`, `courses.read`, `krs.read`, `grades.read/create/update`, `attendance.read/create/update` — bisa input nilai dan rekam kehadiran kelas |
| `dosenpa` | Dosen Pembimbing Akademik | Dosen Pembimbing Akademik UND | Tidak ada permission admin |
| `mahasiswa` | Mahasiswa | Mahasiswa UND | Tidak ada permission admin |
| `pegawai` | Pegawai | Pegawai UND | Tidak ada permission admin |
| `keuangan` | Bagian Keuangan | Bagian Keuangan UND | `approval_requests.read`, `file_uploads.read` |
| `sdm` | Bagian SDM | Bagian SDM UND | `user_roles.read`, `users.read` |
| `pustakawan` | Pustakawan | Pustakawan UND | `file_uploads.read/delete` |
| `auditor` | Auditor | Auditor UND | `audit_logs.read`, `approval_requests.read` |

¹ Khusus **STIKes Sejahtera**, local-part rektor adalah `ketua@stikessejahtera.test` (nama role tetap `rector`, cuma label akun disesuaikan — "Ketua" bukan "Rektor").

Contoh: dosen di Institut Teknologi Mandala login dengan `dosen@itmandala.test` / `password`; bagian keuangan STIKes login dengan `keuangan@stikessejahtera.test` / `password`.

#### Email lengkap: Admin, Dosen, Mahasiswa per universitas

Kombinasi role yang paling sering dipakai untuk demo — email penuh, siap pakai (password semua `password`):

| Universitas | Admin | Dosen | Mahasiswa |
|---|---|---|---|
| Universitas Nusantara Digital | `admin@undigital.test` | `dosen@undigital.test` | `mahasiswa@undigital.test` |
| Institut Teknologi Mandala | `admin@itmandala.test` | `dosen@itmandala.test` | `mahasiswa@itmandala.test` |
| STIKes Sejahtera | `admin@stikessejahtera.test` | `dosen@stikessejahtera.test` | `mahasiswa@stikessejahtera.test` |

Login tidak perlu header khusus — universitas aktif otomatis resolve dari membership default akun tersebut. Untuk pilih tenant manual (mis. akun dengan membership di >1 universitas), kirim header `X-University-ID: <id-universitas>`. Detail lengkap di [docs/MULTI-TENANT-ARCHITECTURE.md](docs/MULTI-TENANT-ARCHITECTURE.md) §10.

> Role tanpa permission admin di atas (dosen PA, mahasiswa, pegawai) memang sengaja kosong — self-service (mis. mahasiswa mengisi KRS-nya sendiri) butuh identity link `Student`/`Lecturer` → `User` yang belum ada, jadi belum aman diberi permission (lihat [docs/FRONTEND-IMPLEMENTASI.md](docs/FRONTEND-IMPLEMENTASI.md) §5). Akun-akun itu tetap berguna untuk membuktikan RBAC menolak akses admin dengan benar. Untuk peta lengkap fitur mana yang bisa diuji end-to-end vs mana yang belum, lihat [docs/BLACK-BOX-TESTING.md](docs/BLACK-BOX-TESTING.md).

### Demo PMB — Universitas Atma Jaya (UAJ)

`php artisan migrate --seed` menyiapkan PMB 2027/2028 Atma Jaya lengkap untuk demo ([AtmaJayaAdmissionSeeder](app/Modules/Admission/Database/Seeders/AtmaJayaAdmissionSeeder.php) + [AtmaJayaApplicantDemoSeeder](app/Modules/Admission/Database/Seeders/AtmaJayaApplicantDemoSeeder.php)). Tanggal relatif terhadap hari seeding:

| Gelombang | Status | Isi demo |
|---|---|---|
| Gelombang 1 | sudah diumumkan | CBT & wawancara selesai, 3 run seleksi disahkan & terbit, daftar ulang berjalan, 5 mahasiswa baru ber-NIM |
| Gelombang 2 | sedang dibuka | pendaftar di antrean bayar, verifikasi, revisi, dan terjadwal ujian/wawancara |
| Gelombang 3 | terjadwal | — |

**Staf** (password `password`): `pmb.admin@atmajaya.com` (Administrator PMB), `pmb.pengesah@` (Pejabat Pengesah), `pmb.verifikator@` (Verifikasi), `pmb.seleksi@` (Panitia Seleksi), `pmb.daftarulang@` (Daftar Ulang), `pmb.keuangan@` (Keuangan PMB), `keuangan@atmajaya.com` (Kepala Keuangan — menyetujui refund), `dosen@atmajaya.com` (pengawas CBT & pewawancara).

**Pendaftar** (`@example.com`, password `Pendaftar-2027!`, login di portal `/pmb/portal`):

| Tahap | Akun |
|---|---|
| Akun baru tanpa pendaftaran | `calon.mahasiswa`, `rafael.gunawan`, `stella.maris.tanjung` |
| Draft | `raka.firmansyah` (identitas saja), `angela.christina` (tinggal unggah dokumen) |
| Belum bayar / menunggu bayar | `hana.kusuma` (+ chat belum dijawab), `daniel.simatupang` (instruksi transfer aktif) |
| Bukti transfer menunggu / ditolak keuangan | `olivia.tjahjadi`, `yusuf.habibie.akbar` / `bella.anastasya` |
| Antrean verifikasi | `gilang.saputra` (+ permintaan pemulihan akun), `natalia.sembiring`, `kenneth.halim` (voucher `EARLYBIRD27`) |
| Sedang diperiksa / revisi / kirim ulang | `ivan.kristanto` / `sekar.ayuningtyas` / `timotius.wibowo` |
| Terverifikasi | `agnes.kartika.putri` (+ klaim identitas ganda dari `agnes.kartika`), `benedictus.raharjo` (CBT G2, minta jadwal ulang), `lucia.permatasari` (wawancara G2) |
| Ditolak administrasi / mengundurkan diri | `felicia.angelina` / `aulia.rahma` (refund menunggu persetujuan) |
| Tidak lulus / cadangan | `rizky.maulana`, `fajar.ramadhan`, `jessica.wongso` (tidak hadir CBT) / `samuel.pardede` |
| Diterima — belum konfirmasi | `nadia.putri.rahmawati`, `putri.ayu.lestari` (promosi cadangan) |
| Daftar ulang berjalan | `dimas.aditya.nugroho` (baru konfirmasi), `yohana.sitompul` (mengisi data), `theodorus.bayu.aji` (bukti bayar menunggu), `maria.goretti.lado` (diminta revisi) |
| Menunggu petugas daftar ulang | `bagus.pratama`, `cecilia.anindya` (siap disetujui) |
| Menolak tawaran | `kevin.tanoto`, `michael.hartanto` |
| Resmi mahasiswa (NIM) | `andreas.wijaya`, `clarissa.halim`, `stefanus.hariyanto` (kelas karyawan), `gabriel.santoso`, `vincentius.adi.nugraha` |

Data demo pendaftar dibuat sekali; untuk mengulang dengan tanggal baru jalankan `php artisan migrate:fresh --seed`.

### Mengganti kredensial Super Admin

Ubah `SUPER_ADMIN_EMAIL` dan `SUPER_ADMIN_PASSWORD` di `.env`, lalu jalankan ulang seeder (aman, idempotent — pakai `updateOrCreate`):

```bash
php artisan db:seed
```

## Menjalankan Proyek

### Backend

```bash
composer install
cp .env.example .env   # sesuaikan DB_*, lalu:
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Backend butuh **Redis** (cache permission, session, queue — lihat `CACHE_STORE`/`SESSION_DRIVER`/`QUEUE_CONNECTION` di `.env`). Untuk dev lokal tanpa Docker:

```bash
brew install redis
brew services start redis
```

Lalu pastikan `REDIS_HOST=127.0.0.1` di `.env` (bukan `redis` — hostname itu hanya resolve di dalam jaringan Docker Laravel Sail, lihat `compose.yaml`).

### Frontend

```bash
cd frontend
npm install
cp .env.example .env   # VITE_API_BASE_URL harus mengarah ke backend di atas
npm run dev
```

Frontend berjalan di `http://localhost:5173` dan API di `http://localhost:8000/api/v1` secara default.

## Test

```bash
php artisan test          # backend (Pest)
cd frontend && npm run build && npm run lint   # type-check + lint frontend
```
