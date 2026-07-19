# CivitasOne

Sistem Manajemen Akademik Universitas — **multi-tenant** (banyak universitas dalam satu instalasi), backend Laravel (API) + frontend React (SPA). Lihat [docs/RANCANGAN-APLIKASI.md](docs/RANCANGAN-APLIKASI.md) untuk cakupan bisnis lengkap, [docs/MULTI-TENANT-ARCHITECTURE.md](docs/MULTI-TENANT-ARCHITECTURE.md) untuk arsitektur tenant isolation, dan [docs/FRONTEND-IMPLEMENTASI.md](docs/FRONTEND-IMPLEMENTASI.md) untuk status implementasi frontend.

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
| `akademik` | Bagian Akademik | Bagian Akademik UND | `approval_requests.read`, `notification_templates.create/read/update`, `file_uploads.read`, `users.read` |
| `dosen` | Dosen | Dosen UND | Tidak ada permission admin — akan pakai modul Akademik begitu dibangun |
| `dosenpa` | Dosen Pembimbing Akademik | Dosen Pembimbing Akademik UND | Tidak ada permission admin |
| `mahasiswa` | Mahasiswa | Mahasiswa UND | Tidak ada permission admin |
| `pegawai` | Pegawai | Pegawai UND | Tidak ada permission admin |
| `keuangan` | Bagian Keuangan | Bagian Keuangan UND | `approval_requests.read`, `file_uploads.read` |
| `sdm` | Bagian SDM | Bagian SDM UND | `user_roles.read`, `users.read` |
| `pustakawan` | Pustakawan | Pustakawan UND | `file_uploads.read/delete` |
| `auditor` | Auditor | Auditor UND | `audit_logs.read`, `approval_requests.read` |

¹ Khusus **STIKes Sejahtera**, local-part rektor adalah `ketua@stikessejahtera.test` (nama role tetap `rector`, cuma label akun disesuaikan — "Ketua" bukan "Rektor").

Contoh: dosen di Institut Teknologi Mandala login dengan `dosen@itmandala.test` / `password`; bagian keuangan STIKes login dengan `keuangan@stikessejahtera.test` / `password`.

Login tidak perlu header khusus — universitas aktif otomatis resolve dari membership default akun tersebut. Untuk pilih tenant manual (mis. akun dengan membership di >1 universitas), kirim header `X-University-ID: <id-universitas>`. Detail lengkap di [docs/MULTI-TENANT-ARCHITECTURE.md](docs/MULTI-TENANT-ARCHITECTURE.md) §10.

> Role tanpa permission admin di atas (dosen, dosen PA, mahasiswa, pegawai) memang sengaja kosong — belum ada modul akademik/kepegawaian yang jadi domain izin mereka. Akun-akun itu tetap berguna untuk membuktikan RBAC menolak akses admin dengan benar. Lihat [docs/FRONTEND-IMPLEMENTASI.md](docs/FRONTEND-IMPLEMENTASI.md) bagian Fase 3 untuk rencana modul-modul tersebut.

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
