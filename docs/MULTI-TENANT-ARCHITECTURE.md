# Arsitektur Multi-University (Multi-Tenant)

> **Status:** Implementasi Fase 1 multi-tenant — backend saja, sesuai permintaan.
> **Dibuat:** 2026-07-19

Laporan ini merangkum evaluasi dan perombakan backend agar mendukung banyak universitas dalam satu instalasi, sesuai brief "Perombakan Backend Menjadi Sistem Multi-University". Ditulis mengikuti struktur output yang diminta (section 26 brief asli).

## 1. Arsitektur yang dipilih

**Shared application, shared database, tenant isolation via kolom `university_id`** — bukan database-per-tenant atau schema-per-tenant.

**Alasan:**
- Skala saat ini (3 universitas demo, modul bisnis akademik belum ada) tidak butuh isolasi fisik level database.
- Codebase **sudah punya scaffold untuk pola ini, belum dipakai**: `app/Support/Scoping/{InstitutionContextResolver,ScopesToInstitution,BelongsToInstitutionScope}.php` dan kolom `user_roles.scope_type`/`scope_id` — komentar di kode itu sendiri eksplisit menyebut "Later phases swap this binding for a resolver that reads the authenticated user's university/campus/faculty/study-program." Pola shared-DB-dengan-scope inilah yang discaffold, jadi paling konsisten untuk dilanjutkan, bukan dirombak ke pendekatan lain.
- Shared-DB lebih murah dioperasikan dan lebih mudah untuk laporan lintas-tenant (Super Admin) dibanding database-per-tenant.
- Mayoritas tabel bisnis yang ada hari ini (roles, file_uploads, approval_*, audit_logs, notification_templates) adalah OLTP kecil-menengah — belum ada indikasi kebutuhan isolasi fisik.

## 2. Klasifikasi tabel

### GLOBAL (platform-wide, tidak boleh punya `university_id`)
`permissions`, `system_settings`, `module_settings`, `feature_flags`, `notification_channels`, `modules` (katalog modul bisnis, baru), `subscription_plans` (baru), `users`, `personal_access_tokens`, `sessions`, `cache*`, `jobs*`.

### TENANT (wajib `university_id`, diisolasi ketat via global scope)
`file_uploads`, `audit_logs`, `activity_logs`, `approval_workflows`, `approval_requests`, `approval_request_steps`, `university_domains`, `university_settings`, `university_modules`, `university_feature_flags`, `university_subscriptions`, `university_subscription_histories`, `user_universities`, `support_sessions`.

### HYBRID (`university_id` nullable — null = template/grant global, terisi = milik satu tenant)
`roles` (null = role template seperti `super_admin`/`staff`/`dosen`; terisi = role kustom tenant), `user_roles` (null = grant global seperti super_admin; terisi = grant berlaku hanya di tenant itu), `notification_templates` (null tidak dipakai pada fase ini — lihat §9 keterbatasan).

### Tidak diskop (keputusan sadar, alasan di §9)
`user_sessions`, `user_devices`, `login_histories`, `notifications`, `notification_logs`, `approval_workflow_steps`, `approval_actions`, `approval_histories`.

## 3. Tabel baru (modul `Tenancy`, 11 tabel)

`universities`, `university_domains`, `university_settings`, `modules`, `university_modules`, `university_feature_flags`, `subscription_plans`, `university_subscriptions`, `university_subscription_histories`, `user_universities`, `support_sessions`.

Field `universities` mengikuti persis daftar di brief (code, slug, name, short_name, legal_name, education_institution_type, accreditation, email, phone, website, status, timezone, locale, currency, date_format, logo_file_id, primary_color, secondary_color, is_active, activated_at, suspended_at, created_by).

**Penyederhanaan sadar dari brief:** brief meminta banyak tabel config terpisah (`university_brandings`, `university_security_settings`, `university_notification_settings`, `university_storage_settings`). Ini digabung jadi satu tabel `university_settings` (key/value + kolom `group`), meniru pola `system_settings` yang sudah ada — `group` value seperti `branding.*`, `security.*` memisahkan kategorinya secara logis tanpa lima tabel yang hampir identik. `subscription_invoices`/`subscription_payments`/`subscription_usage_records`/`subscription_limits` **tidak dibangun** — `subscription_plans.limits` (json) menampung batasan paket, dan billing/invoice sungguhan di luar cakupan (tidak ada payment gateway terintegrasi).

## 4. Tabel yang diubah (menambah `university_id`)

| Tabel | Modul pemilik | Nullable | Constraint yang ikut berubah |
|---|---|---|---|
| `roles` | UserManagement | ya (hybrid) | unique `slug` → unique `(university_id, slug)` |
| `user_roles` | UserManagement | ya (hybrid) | — |
| `file_uploads` | FileManagement | ya | — |
| `audit_logs` | AuditLog | ya | — |
| `activity_logs` | AuditLog | ya | — |
| `approval_workflows` | ApprovalWorkflow | ya | unique `(workflowable_type,name)` → `(university_id,workflowable_type,name)` |
| `approval_requests` | ApprovalWorkflow | ya | — |
| `approval_request_steps` | ApprovalWorkflow | ya | ditambahkan untuk defense-in-depth — endpoint approve/reject/delegate route-model-bind langsung ke tabel ini, bukan lewat parent `approval_requests` |
| `notification_templates` | Notification | ya | unique `(event_key,channel)` → `(university_id,event_key,channel)` |

Semua migration ada di `app/Modules/<Modul>/Database/Migrations/2026_07_19_02xxxx_*.php`, additive (tidak menghapus data), dan sudah diuji dua arah (`up`/`down`).

## 5. Model tenant-aware & global scope

**`App\Support\Tenancy\TenantContext`** — holder request-scoped untuk "universitas mana yang sedang aktif", di-bind sebagai singleton.

**`App\Support\Scoping\UniversityInstitutionContextResolver`** — implementasi nyata dari `InstitutionContextResolver` (scaffold lama), menggantikan `NullInstitutionContextResolver` (dihapus, sudah tidak dipakai). Membaca `TenantContext`, mengembalikan `['university_id' => $id]` atau `[]`.

**`App\Support\Tenancy\TenantScoped`** (trait baru) — dipasang di setiap model TENANT:
```php
class FileUpload extends Model implements ScopesToInstitution
{
    use TenantScoped; // + trait lain yang sudah ada
    ...
}
```
Efeknya: (a) mendaftarkan `BelongsToInstitutionScope` (scope lama, sekarang aktif) sebagai global scope, (b) auto-isi `university_id` dari `TenantContext` saat `creating()` bila belum diisi manual.

**Penting — konteks platform (`TenantContext` kosong) sengaja TIDAK membatasi apa pun.** Ini realisasi persis dari `BelongsToInstitutionScope` yang sudah ada sebelumnya (skip kolom yang tidak ada di context array). Artinya: rute `/platform/*` (tanpa tenant terpilih) melihat semua data lintas tenant — sesuai kebutuhan Super Admin; rute yang lewat `ResolveUniversityMiddleware` dengan tenant aktif otomatis terfilter.

**Role tidak pakai `TenantScoped`** — sengaja. Role bersifat hybrid-visible (role global harus tetap terlihat dari tenant manapun untuk bisa dipakai), jadi filtering query-level tidak cocok; visibilitas & mutasi diatur di `RolePolicy` (lihat §7) bukan di layer query.

## 6. Middleware & routing

- **`tenant.resolve`** (`ResolveUniversityMiddleware`) — dipasang **global** di `routes/api.php` pada level `Route::prefix('v1')`, jadi berlaku untuk SEMUA request `/api/v1/*` termasuk yang belum login. Resolusi tenant: header `X-University-ID` (diklaim, belum tervalidasi) → domain kustom (`university_domains`) → membership aktif `is_default` milik user (kalau login). Selalu set `TenantContext`, tak pernah menolak request.
- **`tenant.access`** (`EnsureUniversityAccessMiddleware`) — dipasang selektif di grup rute `/tenant/*` (self-service). Menolak (400) bila tenant tak teresolusi, menolak (403) bila user bukan anggota aktif universitas itu, **bypass total untuk role `super_admin`**.
- **Kenapa tidak dipasang di semua rute bisnis** (roles, file-uploads, audit-logs, dst.)? Karena proteksi sesungguhnya untuk endpoint yang sudah permission-gated datang dari **`PermissionRegistry` yang sekarang tenant-aware** (§7) — grant permission milik tenant A tidak berlaku saat context resolve ke tenant B, terlepas dari header apa yang dikirim client. `tenant.access` khusus dipakai di endpoint yang TIDAK context permission-gated (mis. `/tenant/profile`, cukup butuh "apakah dia anggota").

## 7. RBAC tenant-aware

**`PermissionRegistry::contextForUser()`** — sekarang membaca `TenantContext` secara internal (constructor-injected), **signature publik tidak berubah** (14 Policy yang memanggil `hasPermissionTo()`/`hasRole()` tidak perlu disentuh). Query `user_roles` sekarang menambah kondisi:
```sql
WHERE user_roles.university_id IS NULL OR user_roles.university_id = :current_tenant
```
Grant dengan `university_id = null` = global (contoh: `super_admin`), berlaku di semua tenant. Cache key berubah dari `permissions:user:{id}` → `permissions:user:{id}:tenant:{id|platform}` — permission tenant A dan B untuk user yang sama tidak saling timpa di cache.

**Bypass `super_admin`** tetap satu tempat (`PermissionRegistry::userHasPermission()`), berlaku lintas-tenant karena grant-nya global.

**`RolePolicy`** dipecah jadi `isVisible()` (baca — role global ATAU role tenant aktif) dan `isMutable()` (ubah/hapus/sync-permission — role harus PERSIS milik tenant aktif, role global cuma bisa diubah lewat context super_admin). Ini mencegah tenant admin membaca/mengedit role tenant lain meski permission slug-nya sama.

## 8. API: Platform vs Tenant

**Platform** (`/api/v1/platform/*`, gated `platform_universities.*`/`platform_statistics.read`, hanya `super_admin` yang punya permission ini): CRUD universitas + activate/suspend + statistik agregat. **Tidak** dibungkus `tenant.access` — memang lintas-tenant.

**Tenant** (`/api/v1/tenant/*`, gated `tenant.access` + `tenant_profile.*`): profil universitas aktif (read-only untuk siapapun anggota aktif) dan `university_settings` (baca/tulis, permission-gated).

`university_id` **tidak pernah diterima dari body/query client** untuk menentukan data siapa yang diubah — selalu diambil dari `TenantContext` (lihat komentar eksplisit di `UniversitySettingController`).

## 9. Keterbatasan yang diketahui (jangan dianggap "belum sempat", ini keputusan sadar)

1. **Modul akademik/keuangan belum ada.** Tabel mahasiswa/dosen/KRS/nilai/tagihan sama sekali belum ada di backend ini (masih placeholder di frontend, lihat `docs/FRONTEND-IMPLEMENTASI.md` Fase 3). Karena itu, data "kondisi" yang diminta brief untuk 3 universitas (mahasiswa aktif/cuti, tagihan lunas/cicilan, dst.) **tidak diseed** — tidak ada tabel untuk menampungnya. Yang diseed adalah semua yang SUDAH ada modulnya: identitas & branding universitas, subscription, modul aktif, RBAC + akun demo per role, approval workflow+request contoh, file upload contoh, audit log contoh, notification template.
2. **Approval workflow *builder* tidak dibangun sebagai fitur admin** — hanya data model + 1 contoh workflow per tenant via seeder. Konsisten dengan `ApproverResolver`'s existing comment bahwa Position-type approver "belum didukung pada Fase 1."
3. **Billing/invoicing tidak nyata** — `university_subscriptions.status` bisa diubah lewat service (`UniversityService`), tapi tidak ada integrasi payment gateway atau generate invoice PDF.
4. **"Support session" (impersonation Super Admin ke tenant) baru berupa tabel (`support_sessions`), belum ada endpoint/flow yang benar-benar menulis ke sana atau mewajibkannya.** Bypass `super_admin` di `EnsureUniversityAccessMiddleware`/`PermissionRegistry` sudah aktif dan aman (lihat §7), tapi belum "tercatat" otomatis sesuai spirit brief §3 ("Setiap sesi harus... reason, started_at, ended_at..."). Ini area follow-up yang jelas.
5. **Constraint unique `(university_id, slug)` pada `roles`** secara teori mengizinkan dua baris `university_id = NULL` dengan `slug` sama (perilaku unique index MySQL: NULL dianggap berbeda satu sama lain). Tidak berisiko praktis karena role global cuma dibuat lewat seeder yang idempotent (`updateOrCreate` dengan slug eksplisit), tapi bukan constraint DB-level murni.
6. **Mode seeder "performance" (puluhan universitas, ratusan ribu mahasiswa) tidak dibangun** — di luar kemampuan realistis satu sesi kerja dan nilainya rendah tanpa modul akademik untuk digenerate datanya.
7. **File fisik tidak benar-benar disimpan per-folder tenant** (`universities/{id}/...`) — `FileUploadService::store()` masih pakai path lama (`uploads/{uploader_id}/...`). Kolom `university_id` sudah ada dan terisi otomatis untuk isolasi query/akses, tapi path-builder disk belum diubah — follow-up yang jelas dan berisiko rendah (tidak ada kebocoran data karena akses tetap lewat DB row + policy, bukan path tebak-tebakan).
8. **Notification template tidak punya fallback global** — setiap tenant butuh template sendiri per `event_key`+`channel` (dipenuhi di seeder), beda dengan desain awal yang mempertimbangkan `null` = default lintas-tenant.

## 10. Akun demo

Semua password: `password` (kecuali Super Admin, lihat `.env` `SUPER_ADMIN_PASSWORD`).

| Universitas | Domain | Admin | Rektor/Ketua | Dosen | Mahasiswa |
|---|---|---|---|---|---|
| Universitas Nusantara Digital (Enterprise, 15 modul aktif) | undigital.test | admin@undigital.test | rektor@undigital.test | dosen@undigital.test | mahasiswa@undigital.test |
| Institut Teknologi Mandala (Professional, 6 modul aktif) | itmandala.test | admin@itmandala.test | rektor@itmandala.test | dosen@itmandala.test | mahasiswa@itmandala.test |
| STIKes Sejahtera (Trial, 3 modul aktif) | stikessejahtera.test | admin@stikessejahtera.test | ketua@stikessejahtera.test | dosen@stikessejahtera.test | mahasiswa@stikessejahtera.test |

Plus Super Admin global (`.env`: `SUPER_ADMIN_EMAIL`/`SUPER_ADMIN_PASSWORD`) — akses `/platform/*`, bypass semua isolasi tenant.

**Cara memilih tenant saat panggil API:** kirim header `X-University-ID: <id-universitas>`, atau tidak usah sama sekali kalau user itu hanya anggota satu universitas dengan `is_default=true` (resolusi otomatis — sudah dibuktikan lewat curl langsung ke `/api/v1/me` tanpa header apapun).

## 11. Test isolasi tenant

`app/Modules/Tenancy/Tests/Feature/TenantIsolationTest.php` (7 test, semua lulus):
1. Permission yang di-grant di tenant A tidak berlaku saat context resolve ke tenant B (maupun context platform/null).
2. File upload milik tenant A tidak terlihat query saat context tenant B, terlihat lagi saat context platform (null).
3. Tenant admin bisa baca/ubah role kustom tenant sendiri, tapi 403 saat mencoba akses role kustom tenant lain meski permission slug sama.
4. `EnsureUniversityAccessMiddleware` menolak (403) user tanpa membership di tenant yang diresolusi.
5. `EnsureUniversityAccessMiddleware` mengizinkan anggota aktif dan mengembalikan profil tenant yang benar.
6. `super_admin` bypass membership check sepenuhnya.
7. User dengan membership di 2 universitas melihat data berbeda tergantung header `X-University-ID` yang dikirim.

Ditambah **verifikasi manual langsung lewat curl** (bukan cuma test otomatis) terhadap server nyata: login demo account → auto-resolve tenant tanpa header → `/tenant/profile` benar → ganti header ke tenant lain tanpa membership → 403 → login Super Admin → `/platform/universities` & `/platform/statistics` sukses.

## 12. Cara menjalankan

```bash
php artisan migrate:fresh --seed   # aman, semua seeder idempotent (updateOrCreate/firstOrCreate)
php artisan test                   # 86 test, semua modul termasuk Tenancy
```
Re-run `php artisan db:seed` tanpa `--fresh` juga aman (dibuktikan: jumlah baris tidak berubah pada percobaan kedua).

## 13. File yang dibuat/diubah

**Baru (modul `Tenancy` lengkap + infrastruktur tenancy):** lihat struktur di `app/Modules/Tenancy/` (Controllers, Database/Migrations, Database/Seeders, Database/Factories, Enums, Models, Policies, Requests, Resources, Routes, Services, Tests) — 11 migration, 11 model, 4 factory, 3 seeder, 4 controller, 1 policy, 3 request, 2 resource, 1 route file, 1 test file. Plus `app/Support/Tenancy/{TenantContext,TenantScoped}.php`, `app/Support/Scoping/UniversityInstitutionContextResolver.php`, `app/Http/Middleware/{ResolveUniversityMiddleware,EnsureUniversityAccessMiddleware}.php`, `app/Modules/UserManagement/Database/Seeders/OrganizationalRoleSeeder.php`, 9 migration `add_university_id_to_*` di modul FileManagement/AuditLog/ApprovalWorkflow/Notification/UserManagement.

**Diubah:** `app/Providers/AppServiceProvider.php` (binding resolver + singleton TenantContext), `bootstrap/app.php` (alias middleware), `routes/api.php` (middleware global `tenant.resolve`), `database/seeders/DatabaseSeeder.php`, `phpunit.xml` (testsuite baru), model+policy+service+resource di UserManagement/FileManagement/AuditLog/ApprovalWorkflow/Notification (lihat §4-§7), `tests/Unit/InstitutionScopingTest.php` (nama test disesuaikan, perilaku tidak berubah).

**Dihapus:** `app/Support/Scoping/NullInstitutionContextResolver.php` (digantikan `UniversityInstitutionContextResolver`, sudah tidak dipakai di manapun).
