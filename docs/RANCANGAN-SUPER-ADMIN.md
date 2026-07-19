# Rancangan Fitur Super Admin (Platform Management)

> **Status:** Dokumen desain (belum semua diimplementasikan — lihat §10 untuk status per fitur).
> **Dibuat:** 2026-07-19
> Pelengkap [RANCANGAN-APLIKASI.md](./RANCANGAN-APLIKASI.md) §3.1 dan [MULTI-TENANT-ARCHITECTURE.md](./MULTI-TENANT-ARCHITECTURE.md). Dokumen itu menjelaskan *arsitektur* multi-tenant (tabel, model, middleware); dokumen ini menjelaskan *fitur* yang dilihat dan dipakai Super Admin di atas arsitektur tersebut. Nama tabel/model/endpoint yang disebut di sini merujuk persis ke yang sudah ada di kode — kalau belum ada, ditandai eksplisit di §10.

## Daftar Isi

1. [Prinsip Utama](#1-prinsip-utama)
2. [Peta Navigasi Super Admin](#2-peta-navigasi-super-admin)
3. [A. Manajemen Aplikasi/Platform](#3-a-manajemen-aplikasiplatform)
4. [B. Hak Akses Fitur Secara Lengkap](#4-b-hak-akses-fitur-secara-lengkap)
5. [C. Biaya Bulanan / Billing per Universitas](#5-c-biaya-bulanan--billing-per-universitas)
6. [D. Keamanan Aplikasi](#6-d-keamanan-aplikasi)
7. [E. Hak General Lainnya](#7-e-hak-general-lainnya)
8. [Aturan Isolasi Data Bisnis](#8-aturan-isolasi-data-bisnis)
9. [Matriks Permission Baru yang Dibutuhkan](#9-matriks-permission-baru-yang-dibutuhkan)
10. [Ringkasan Gap Implementasi](#10-ringkasan-gap-implementasi)

---

## 1. Prinsip Utama

**Super Admin mengelola platform, bukan operasional harian satu universitas.** Ini bukan sekadar gaya bahasa — ini aturan navigasi yang keras:

> **Tidak ada satu pun layar Super Admin yang menampilkan data bisnis milik satu universitas tertentu (mahasiswa, akademik, dosen, pegawai, keuangan, skripsi, magang/MBKM, perpustakaan, alumni) kecuali Super Admin sudah eksplisit memilih universitas itu lebih dulu.**

Login sebagai Super Admin **tidak otomatis** membuka data tenant manapun. Yang muncul begitu login adalah **Dashboard Platform** (§2) — statistik agregat lintas-tenant (jumlah, bukan detail individu), daftar universitas, status langganan, kesehatan sistem. Untuk melihat data spesifik satu universitas, Super Admin harus lewat **Tenant Switcher**.

### Mekanisme penegak (sudah ada di backend, tinggal dipakai UI)

Aturan di atas bukan cuma janji UX — sudah ada penegaknya di level backend:

- `App\Support\Tenancy\TenantContext` — menyimpan "universitas mana yang aktif" per-request. Kosong secara default untuk Super Admin.
- `BelongsToInstitutionScope` (global scope) — setiap model tenant (`FileUpload`, `AuditLog`, `ApprovalRequest`, dst.) otomatis **tidak difilter sama sekali** kalau `TenantContext` kosong — tapi ini berarti *terlihat lintas semua tenant sekaligus*, bukan "tidak terlihat." Makanya aturan §1 harus ditegakkan di **level UI/routing**, bukan cuma dibiarkan ke query scope: layar Super Admin secara sadar tidak pernah memanggil endpoint bisnis tenant tanpa `X-University-ID` — endpoint macam itu (mahasiswa, KRS, dst.) memang belum ada (§8), tapi begitu ada, kontrak ini wajib dipegang.
- `EnsureUniversityAccessMiddleware` (`tenant.access`) — dipasang di rute `/tenant/*`. Super Admin bypass cek membership (lihat `MULTI-TENANT-ARCHITECTURE.md` §6-7), tapi **tetap butuh `TenantContext` terisi** — kalau tidak, request ditolak 400 "Universitas tidak dapat ditentukan." Artinya secara desain pun, mustahil hit endpoint tenant tanpa memilih universitas dulu.

### Tenant Switcher (komponen UI baru — §10: belum ada)

Satu komponen, dipasang di header/sidebar khusus akun dengan role `super_admin`:

```
[🔍 Pilih Universitas ▾]  ← saat belum pilih, semua menu "data tenant" disabled/hidden
```

Begitu dipilih:
1. Frontend simpan `university_id` terpilih (mis. di state global), kirim sebagai header `X-University-ID` di semua request berikutnya.
2. Banner permanen muncul di seluruh layar: **"Anda sedang melihat data: Universitas Nusantara Digital [Ganti / Keluar dari mode tenant]"** — supaya tidak ada ambiguitas sedang di scope mana.
3. Masuk mode tenant dengan cara ini **wajib dicatat** ke `support_sessions` (lihat §6) — alasan wajib diisi sebelum switcher aktif.
4. "Keluar dari mode tenant" mengosongkan `TenantContext`, kembali ke Dashboard Platform.

## 2. Peta Navigasi Super Admin

```
Dashboard Platform          ← halaman awal, agregat lintas-tenant saja
├── Manajemen Universitas   (§3)
├── Modul & Fitur           (§3)
├── Langganan & Biaya       (§5)
├── Hak Akses & Role        (§4)
├── Keamanan                (§6)
├── Pengaturan Global       (§7)
└── ── garis pemisah ──
    [Pilih Universitas ▾]  → begitu dipilih, baru muncul menu tenant:
        Profil & Pengaturan Universitas
        (nanti, begitu modulnya ada) Akademik, Mahasiswa, Dosen,
        Pegawai, Keuangan, Skripsi, Magang & MBKM, Perpustakaan, Alumni
```

Pemisahan visual ini disengaja: menu di atas garis = platform (selalu terlihat), menu di bawah garis = tenant (baru terlihat/aktif setelah Tenant Switcher dipakai).

## 3. A. Manajemen Aplikasi/Platform

### 3.1 Manajemen Universitas

Dasar sudah ada (`Modules\Tenancy\Controllers\UniversityController` — index/store/show/update/activate/suspend). Rancangan lengkap:

- **Daftar universitas** — tabel dengan filter status (`draft`/`trial`/`active`/`suspended`/`expired`/`terminated`), pencarian, kolom ringkas: nama, kode, paket langganan aktif, jumlah pengguna, tanggal aktivasi.
- **Wizard onboarding universitas baru** (bukan cuma form flat): Step 1 identitas (code, name, education_institution_type, accreditation) → Step 2 domain (`university_domains`) → Step 3 paket langganan awal → Step 4 modul yang diaktifkan → Step 5 buat akun admin pertama universitas itu (`user_universities` + role `university_administrator`, persis pola yang dipakai `UniversitySeeder`).
- **Detail universitas** — tab: Profil, Domain, Modul Aktif, Langganan, Anggota (ringkas: jumlah per `membership_type`, bukan daftar detail pribadi), Log Aktivitas (audit_log terfilter ke universitas ini saja).
- **Lifecycle status** — tombol aksi eksplisit per transisi (bukan dropdown bebas): "Aktifkan" (`activate()`, sudah ada), "Suspend" (`suspend()`, sudah ada — wajib isi alasan, tersimpan ke `university_subscription_histories`), "Perpanjang Trial", "Terminasi" (soft-delete, perlu konfirmasi ganda — ireversibel dari UI, hanya lewat akses database langsung yang bisa undo).

### 3.2 Manajemen Domain

CRUD `university_domains` per universitas — tambah domain kustom, tandai primary, status verifikasi (untuk custom domain non-`.test`, perlu verifikasi DNS — dicatat sebagai kebutuhan lanjutan, belum ada mekanisme verifikasi otomatis).

### 3.3 Katalog Modul & Fitur

- **Katalog modul global** (`modules` — CRUD, cuma Super Admin) — daftar modul bisnis yang *bisa* diaktifkan universitas manapun (akademik, keuangan, perpustakaan, dst. — 15 modul sudah diseed lewat `TenancySeeder`).
- **Toggle modul per universitas** (`university_modules`) — checklist di halaman detail universitas. Rancangan tambahan: **dependency antar modul** (mis. modul "payroll" mensyaratkan modul "hr" aktif dulu) — validasi ini belum ada di backend, perlu ditambahkan di `UniversityModule`/service terkait saat modul ini dibangun.
- **Feature flag per universitas** (`university_feature_flags`, override dari `feature_flags` global) — toggle granular di bawah level modul, mis. "izinkan delegasi approval" hanya untuk universitas tertentu meski modul approval-nya sama.

### 3.4 Kesehatan Sistem & Monitoring

- Ringkasan: status queue (`jobs`/`failed_jobs`), pemakaian storage per disk, error rate terbaru (dari log aplikasi — perlu agregasi, belum ada endpoint-nya).
- Pemakaian API per tenant (jumlah request per hari/bulan) — berguna ganda untuk §5 (deteksi mendekati limit paket) dan §6 (deteksi anomali).
- Versi aplikasi & changelog rilis — statis/manual untuk saat ini (belum ada sistem versioning otomatis).

## 4. B. Hak Akses Fitur Secara Lengkap

Backend RBAC sudah tenant-aware sepenuhnya (`roles`/`permissions`/`user_roles`, lihat `MULTI-TENANT-ARCHITECTURE.md` §7) — bagian ini merancang **UI manajemen**-nya di sisi Super Admin, di atas mekanisme yang sudah jalan.

- **Manajemen Role Global (Template)** — CRUD role dengan `university_id = null` (mis. `super_admin`, `staff`, `university_administrator`, `dosen`, `mahasiswa` — sudah diseed lewat `OrganizationalRoleSeeder`). Hanya Super Admin yang boleh ubah role global (`RolePolicy::isMutable()` sudah menegakkan ini). UI: matrix checklist permission × role, bukan form satu-per-satu.
- **Manajemen Permission (Katalog Global)** — CRUD `permissions` (scope/action/resource) — dasar sudah ada di `PermissionController`, tinggal UI-nya. Permission baru untuk fitur di dokumen ini didaftar di §9.
- **Lihat (bukan kelola) hak akses per tenant** — Super Admin bisa melihat "siapa punya role apa di universitas mana" secara **agregat** (mis. tabel: Universitas × jumlah pengguna per role) tanpa perlu masuk mode tenant untuk hal ini — ini murni metadata `user_roles`/`user_universities`, bukan data bisnis, jadi tidak melanggar §1. Untuk **mengubah** role spesifik seorang pengguna di satu universitas, tetap wajib lewat Tenant Switcher (itu tindakan administratif di dalam tenant tersebut).
- **Role kustom tenant tidak bisa dibuat/diubah dari sisi Super Admin** — itu domain admin universitas masing-masing (`university_administrator`), sesuai `RolePolicy` yang sudah membedakan role global vs kustom.

## 5. C. Biaya Bulanan / Langganan per Universitas

Backend punya kerangkanya (`subscription_plans`, `university_subscriptions`, `university_subscription_histories` — lihat `MULTI-TENANT-ARCHITECTURE.md` §3, §9 poin 3) tapi **belum ada billing/invoicing nyata**. Ini rancangan untuk melengkapinya.

### 5.1 Manajemen Paket Langganan

CRUD `subscription_plans` — nama, harga, `billing_period`, `limits` (json: max_users, max_storage_gb, dll — sudah dipakai `TenancySeeder` untuk plan Trial/Starter/Professional/Enterprise). UI: tabel plan + form edit limit per plan.

### 5.2 Status Langganan per Universitas

Di tab "Langganan" pada detail universitas (§3.1):
- Plan aktif, status (`trial`/`active`/`past_due`/`suspended`/`cancelled`/`expired`), tanggal mulai/berakhir periode, sisa masa trial.
- **Pemakaian vs limit** — progress bar per item `limits` (mis. "247/500 pengguna", "8.2/50 GB storage") — dihitung real-time dari data aktual (jumlah `user_universities` aktif, total `size_bytes` di `file_uploads` milik tenant itu).
- Riwayat perubahan paket (`university_subscription_histories` — sudah ada tabelnya, event seperti `upgraded`/`downgraded`/`suspended`/`trial_started`).
- Tombol aksi: Upgrade/Downgrade paket, Perpanjang, Beri Grace Period manual.

### 5.3 Invoice & Pembayaran (kebutuhan struktur baru — belum ada)

Perlu tabel baru (di luar cakupan implementasi task ini, dicatat sebagai desain):
```
subscription_invoices    (id, university_id, subscription_plan_id, period_start, period_end,
                           amount, status: draft|issued|paid|overdue|void, issued_at, due_at, paid_at)
subscription_payments    (id, invoice_id, amount, method, paid_at, reference)
```
- Halaman daftar invoice per universitas + lintas-universitas (untuk rekonsiliasi keuangan platform).
- **Tidak ada integrasi payment gateway di rancangan tahap ini** — pencatatan status pembayaran manual oleh tim finance platform (mirip AR/AP manual), bukan otomatis. Integrasi gateway (Midtrans/Xendit dsb.) adalah pekerjaan lanjutan terpisah.
- **Notifikasi otomatis**: H-7 sebelum jatuh tempo, saat overdue, saat mendekati/melewati limit paket — lewat `notification_templates` global (event_key baru, mis. `billing.invoice_due`, `billing.limit_warning`).
- **Aksi otomatis saat overdue melewati grace period**: `university.status` → `suspended` (pakai `UniversityService::suspend()` yang sudah ada) — perlu scheduled job baru (belum ada), dijalankan harian.

### 5.4 Laporan Pendapatan Platform

Ringkasan lintas-tenant: total MRR (Monthly Recurring Revenue), breakdown per plan, tren universitas baru/churn — murni angka agregat, tidak melanggar §1 karena tidak menyentuh data bisnis internal tenant.

## 6. D. Keamanan Aplikasi

### 6.1 Audit Log Lintas-Tenant

`AuditLogController`/`audit_logs` sudah ada dan sudah tenant-scoped (§`MULTI-TENANT-ARCHITECTURE.md` §5). Untuk Super Admin di konteks platform (tanpa Tenant Switcher aktif), scope kosong → **terlihat semua tenant sekaligus** (perilaku `BelongsToInstitutionScope` yang sudah ada, lihat §1). Rancangan UI: dashboard audit log dengan kolom "Universitas" ditambahkan (saat ini `AuditLogResource` belum expose nama universitas secara human-readable — cukup tambah `university.name` via `with('university')`), filter per universitas/aksi/tanggal/pengguna.

### 6.2 Manajemen Sesi & Perangkat Platform-Wide

Perluasan dari `SessionController`/`DeviceController` (Fase 1, per-user) — Super Admin bisa lihat (bukan buka isinya, cuma metadata) sesi aktif di seluruh platform: siapa login dari mana, kapan, device apa. Berguna untuk deteksi akun bocor. Aksi: paksa logout satu sesi/semua sesi satu pengguna.

### 6.3 Kebijakan Keamanan Global

Sudah ada tempatnya (`system_settings`, group `security` — contoh yang sudah diseed: `security.max_login_attempts`). Perluasan rancangan, semua lewat mekanisme `system_settings` yang sama (tidak perlu tabel baru):
- `security.session_timeout_minutes`
- `security.password_min_length` / kompleksitas (saat ini hardcoded di `AppServiceProvider::configureDefaults()` — jadi hanya beda by environment, bukan configurable; jadikan `system_settings` kalau perlu diubah tanpa deploy)
- `security.require_2fa_for_super_admin` (2FA belum diimplementasikan sama sekali di backend — kebutuhan baru signifikan, di luar cakupan seeder ringan)

### 6.4 IP Allowlist per Universitas (kebutuhan baru)

Untuk universitas yang mensyaratkan akses admin hanya dari IP kampus tertentu — perlu tabel baru `university_ip_allowlists` (university_id, cidr_range, description) + middleware baru yang mengecek `$request->ip()` terhadap daftar ini pada rute `/tenant/*`. Opsional per universitas (kosong = tidak dibatasi).

### 6.5 Deteksi Anomali

Rancangan aturan sederhana berbasis data yang sudah ada (`login_histories`, `user_sessions`):
- Login sukses dari >2 negara/kota berbeda dalam 24 jam → flag.
- Lebih dari N `LoginHistoryStatus::Failed` berturut-turut lintas banyak akun dari 1 IP dalam waktu singkat → indikasi brute-force terdistribusi (rate limiting per-akun sudah ada di `AuthenticateUserAction`, ini levelnya cross-account, belum ada).
- Volume request API tidak wajar dari 1 tenant → dikaitkan ke §3.4 (monitoring pemakaian).

Ini murni **rules-based**, bukan ML — cukup untuk tahap ini, dicatat sebagai backlog nyata (belum ada job/listener yang menghitungnya).

### 6.6 Support Session — Satu-Satunya Jalur Sah Melihat Data Tenant untuk Dukungan Teknis

Tabel `support_sessions` **sudah ada** (§`MULTI-TENANT-ARCHITECTURE.md` §9 poin 4: "baru berupa tabel, belum ada endpoint/flow"). Rancangan alurnya:

1. Super Admin klik "Masuk sebagai Universitas" (Tenant Switcher, §1) dengan tujuan **dukungan teknis** (dibedakan dari "lihat data agregat" yang tidak butuh masuk tenant sama sekali).
2. Wajib isi `reason` (free text) — divalidasi tidak boleh kosong.
3. Sistem catat baris baru di `support_sessions` (`super_admin_id`, `university_id`, `reason`, `started_at`).
4. Selama sesi aktif, **setiap aksi mutasi** yang dilakukan (create/update/delete) dicatat juga ke `actions_performed` (json array) di baris `support_sessions` yang sama — bukan cuma `audit_logs` biasa, supaya gampang direview "apa saja yang Super Admin lakukan saat masuk sebagai tenant X."
5. Klik "Keluar dari mode tenant" → `ended_at` diisi otomatis.
6. Dashboard "Riwayat Support Session" — daftar semua sesi bantuan yang pernah dilakukan Super Admin manapun, dengan durasi dan ringkasan aksi — untuk akuntabilitas.

**Ini eksplisit terpisah dari akses admin biasa** — di tahap ini, satu-satunya cara Super Admin "masuk ke tenant" ya lewat alur ini (Tenant Switcher = Support Session). Kalau nanti ada kebutuhan Super Admin masuk tanpa harus isi alasan (mis. sekadar demo), itu perlu keputusan produk terpisah — defaultnya tetap wajib dicatat.

## 7. E. Hak General Lainnya

- **Pengaturan Global** (`system_settings`) — sudah ada UI dasarnya (`SystemSettingsPage` di frontend, dari fase implementasi sebelumnya) — cukup diperluas grup-nya seiring kebutuhan §6.3.
- **Feature Flag Global** (`feature_flags`) — sudah ada UI-nya (`FeatureFlagsPage`).
- **Katalog & Template Notifikasi Default** — `notification_templates` dengan `university_id = null` sekarang **tidak dipakai sebagai fallback lintas-tenant** (lihat keterbatasan §`MULTI-TENANT-ARCHITECTURE.md` §9 poin 8 — tiap tenant butuh template sendiri). Untuk hak general Super Admin: kelola *katalog* event_key yang tersedia (dokumentasi/referensi apa saja notifikasi yang bisa di-trigger sistem), bukan isi template per tenant.
- **Master Data Global** — daftar `education_institution_type`, `accreditation` saat ini free-text di form universitas (§3.1). Rancangan: jadikan tabel referensi kecil (`education_institution_types`, `accreditation_levels`) yang dikelola Super Admin, dipakai sebagai dropdown — konsistensi data lintas universitas, bukan typo bebas.
- **Mode Maintenance Platform** — Laravel sudah punya `php artisan down`/`up` bawaan (`APP_MAINTENANCE_DRIVER=file`, lihat `config/app.php`) — rancangan UI: tombol di dashboard untuk trigger maintenance mode terjadwal, dengan pesan kustom, tanpa perlu akses server langsung. Perlu endpoint baru yang memanggil Artisan command ini.
- **Ekspor Laporan Agregat** — unduh CSV/Excel ringkasan lintas-tenant (jumlah universitas per status, total pengguna per bulan, dst.) — agregat murni, konsisten dengan §1 (tidak membocorkan data individu tenant).

## 8. Aturan Isolasi Data Bisnis

Domain berikut **wajib** memilih universitas dulu (lewat Tenant Switcher §1) sebelum bisa diakses — berlaku untuk siapapun termasuk Super Admin:

| Domain | Status modul saat ini |
|---|---|
| Mahasiswa | Belum ada tabel/modul (Fase 3, lihat `FRONTEND-IMPLEMENTASI.md`) |
| Akademik (kurikulum, mata kuliah, kelas, jadwal, KRS, absensi, penilaian) | Belum ada tabel/modul |
| Dosen | Belum ada tabel/modul |
| Pegawai | Belum ada tabel/modul |
| Keuangan Universitas (tagihan, beasiswa) | Belum ada tabel/modul — **beda** dari §5 (billing platform-ke-universitas), ini keuangan universitas-ke-mahasiswa |
| Skripsi | Belum ada tabel/modul |
| Magang dan MBKM | Belum ada tabel/modul |
| Perpustakaan | Belum ada tabel/modul |
| Alumni | Belum ada tabel/modul |

**Kontrak desain untuk modul-modul ini nanti** (begitu dibangun, sesuai Fase 3 `FRONTEND-IMPLEMENTASI.md`):

1. Setiap tabel bisnis baru **wajib** kolom `university_id` (tidak nullable — beda dari tabel hybrid seperti `roles`; data mahasiswa/dosen/dst. tidak punya makna "global").
2. Model **wajib** pakai `TenantScoped` trait (§`MULTI-TENANT-ARCHITECTURE.md` §5) — tidak ada pengecualian "biar Super Admin bisa lihat semua" di level query; kalau Super Admin butuh lihat, dia lewat Tenant Switcher seperti semua orang.
3. Rute API modul-modul ini **wajib** ditambahkan ke grup middleware `tenant.access`, bukan cuma `auth:sanctum` — beda dari pola saat ini di mana `tenant.access` cuma dipasang di `/tenant/*` (§`MULTI-TENANT-ARCHITECTURE.md` §6) karena endpoint yang ada sekarang semuanya permission-gated secara memadai; modul akademik baru harus eksplisit pakai keduanya untuk defense-in-depth yang sama seperti alasan `approval_request_steps` ditambah scoping di §`MULTI-TENANT-ARCHITECTURE.md` §9 poin (temuan `ApprovalRequestStep`).
4. Tidak ada rute "list semua mahasiswa lintas universitas" untuk siapapun kecuali lewat laporan agregat murni angka (§7) — kalau ada kebutuhan bisnis nyata untuk itu, itu keputusan produk baru yang harus didiskusikan eksplisit, bukan default.

## 9. Matriks Permission Baru yang Dibutuhkan

Mengikuti pola `resource.action` yang sudah dipakai di seluruh backend (`RolePermissionSeeder::PERMISSION_MAP`, lihat `MULTI-TENANT-ARCHITECTURE.md` §7). Permission yang **sudah ada**: `platform_universities.{create,read,update}`, `platform_statistics.read`, `tenant_profile.{read,update}`. Permission baru yang perlu ditambahkan seiring fitur di dokumen ini dibangun:

| Slug | Untuk fitur | Bagian |
|---|---|---|
| `platform_modules.update` | Toggle modul/feature-flag per universitas dari sisi platform | §3.3 |
| `platform_billing.read` | Lihat status langganan & invoice lintas-tenant | §5 |
| `platform_billing.update` | Ubah paket, invoice, aksi suspend-karena-overdue | §5 |
| `platform_security.read` | Dashboard audit log lintas-tenant, sesi platform-wide, hasil deteksi anomali | §6.1, §6.2, §6.5 |
| `platform_security.update` | Ubah kebijakan keamanan global, kelola IP allowlist | §6.3, §6.4 |
| `support_sessions.create` | Memulai Support Session (masuk sebagai tenant untuk dukungan) | §6.6 |
| `support_sessions.read` | Lihat riwayat Support Session siapapun | §6.6 |
| `platform_master_data.update` | Kelola tabel referensi (jenis institusi, akreditasi) | §7 |
| `platform_maintenance.update` | Trigger/matikan mode maintenance | §7 |

Semua permission ini secara desain **hanya** disertakan di role global `super_admin` (yang sudah bypass semua cek lewat `PermissionRegistry::userHasPermission()`) — tidak perlu di-assign eksplisit kecuali suatu saat ada role platform lain di bawah Super Admin (mis. "Platform Support Staff" dengan akses terbatas ke §6.6 saja tanpa §5/§3).

## 10. Ringkasan Gap Implementasi

| Fitur | Status |
|---|---|
| CRUD universitas + activate/suspend (backend) | ✅ Ada (`UniversityController`) |
| CRUD universitas + activate/suspend (frontend) | ✅ Ada (`UniversitiesPage`, menu "Manajemen Universitas") |
| Statistik platform dasar (backend) | ✅ Ada (`PlatformStatisticsController`) |
| Statistik platform dasar (frontend) | ✅ Ada (`PlatformDashboardPage`, halaman Dashboard Super Admin otomatis beda dari dashboard tenant biasa) |
| Menu Super Admin terpisah dari menu tenant | ✅ Ada (`PLATFORM_NAV_ITEMS` vs `NAV_ITEMS`, `useIsSuperAdmin()`) — Super Admin tidak lagi melihat Akademik/Mahasiswa/Dosen/Pegawai/Keuangan/Skripsi/Magang-MBKM/Perpustakaan/Alumni/Pengumuman/Laporan di sidebar |
| Permission matrix §9 (9 slug baru) | ✅ Diseed, otomatis masuk role `super_admin` |
| Manajemen domain | ⚠️ Model ada (`UniversityDomain`), belum ada Controller/UI khusus |
| Katalog modul + toggle per universitas | ⚠️ Model & seeder ada, belum ada Controller/UI untuk diubah lewat platform |
| Feature flag per universitas | ⚠️ Model ada, belum ada Controller/UI |
| Manajemen role/permission global | ⚠️ Backend penuh (tenant-aware), UI frontend ada dasarnya (`RolesPage`/`PermissionsPage`) tapi belum ada tampilan "lihat hak akses lintas-tenant" (§4) |
| Paket & status langganan | ⚠️ Model ada (`SubscriptionPlan`, `UniversitySubscription`), belum ada Controller/UI |
| Invoice & pembayaran | ❌ Tabel belum ada sama sekali (§5.3) |
| Notifikasi billing otomatis | ❌ Belum ada |
| Suspend otomatis saat overdue | ❌ Belum ada (butuh scheduled job) |
| Audit log lintas-tenant (UI) | ⚠️ Backend ada, UI frontend perlu tambahan filter+kolom universitas |
| Sesi/perangkat platform-wide | ❌ Belum ada (baru ada per-user di Fase 1) |
| Kebijakan keamanan global tambahan | ⚠️ Sebagian ada (`system_settings`), 2FA belum ada sama sekali |
| IP allowlist per universitas | ❌ Belum ada |
| Deteksi anomali | ❌ Belum ada |
| Support Session (alur nyata) | ✅ Ada (`SupportSessionController`: start/end/riwayat, `SupportSessionPolicy` — ownership check saat mengakhiri sesi) — pencatatan otomatis `actions_performed` per-aksi mutasi masih belum ada (lihat catatan di bawah) |
| Tenant Switcher (UI) | ✅ Ada (`TenantSwitcher` di Header + `TenantModeBanner` permanen di semua halaman + `tenantStore` + header `X-University-ID` disuntik otomatis lewat interceptor `api.ts`) — sidebar Super Admin otomatis menambahkan menu bisnis tenant begitu switcher aktif, hilang lagi begitu keluar |
| Master data referensi (institution type, akreditasi) | ❌ Masih free-text |
| Mode maintenance via UI | ❌ Belum ada (command Artisan-nya sudah ada bawaan Laravel) |
| Ekspor laporan agregat | ❌ Belum ada |

**Catatan keterbatasan Support Session saat ini:** sesi tercatat lengkap (siapa, universitas mana, kapan mulai/berakhir, alasan, IP), tapi kolom `actions_performed` belum diisi otomatis — butuh middleware/listener terpisah yang menyadap tiap mutasi selama sesi aktif, ditunda sebagai kerja lanjutan. Sesi yang ditinggal tanpa diklik "Keluar" (mis. tab ditutup) juga tetap `ended_at = null` selamanya — tidak ada auto-expire.

**Rekomendasi urutan pengerjaan lanjutan**: (1) UI untuk yang masih ⚠️ (modul/domain/feature-flag/subscription toggle — modelnya sudah ada, tinggal Controller+Resource+frontend, pola sama persis dengan `UniversityController` yang sudah ada); (2) Keamanan (§6) dan Billing lanjutan (§5.3) sebagai fase berikutnya, karena butuh tabel baru dan keputusan bisnis tambahan (payment gateway mana, kebijakan 2FA seperti apa) yang di luar cakupan dokumen desain ini.
