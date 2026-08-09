# Daftar Uji Black Box — CivitasOne

> **Status:** Dokumen kerja (living checklist).
> **Dibuat:** 2026-08-09, berdasarkan kondisi kode di branch `main` (setelah KRS/Nilai/Absensi menjadi bisa ditulis).
> Skenario di sini menguji sistem dari luar — lewat REST API `/api/v1/...` dan/atau UI frontend — tanpa bergantung pada detail implementasi. Cakupannya **seluruh 18 modul backend + platform Super Admin**, termasuk modul yang isinya baru read-only, supaya dokumen ini jadi peta lengkap kondisi aplikasi, bukan cuma bagian yang sudah selesai. Untuk cakupan bisnis lengkap lihat [RANCANGAN-APLIKASI.md](./RANCANGAN-APLIKASI.md); untuk status implementasi frontend lihat [FRONTEND-IMPLEMENTASI.md](./FRONTEND-IMPLEMENTASI.md) (dokumen itu sendiri sudah agak basi terhadap Modul Akademik per sesi ini — lihat §10-14).

## Cara Membaca Dokumen Ini

Setiap modul punya label status di judul seksi:

| Label | Arti |
|---|---|
| ✅ CRUD penuh | create/update/delete benar-benar berfungsi, ada test otomatis (Pest) yang membuktikannya |
| 📖 Read-only | backend cuma punya `index`/`show` — **tidak ada** endpoint create/update/delete sama sekali, bukan cuma UI yang belum dibangun |
| 🧩 UI belum tersambung | halaman frontend sudah ada, tapi datanya statis/hardcoded (mock), belum memanggil API asli |

Kolom **Prioritas**: **Tinggi** (jalur kritikal — keamanan, integritas data, isolasi tenant, uang), **Sedang** (fungsi inti tapi bukan penentu keamanan/data), **Rendah** (kosmetik, edge case jarang).

Semua request API (kecuali `login`, `forgot-password`, `reset-password`) butuh header `Authorization: Bearer <token>`. Endpoint yang beroperasi pada data satu universitas butuh header `X-University-ID: <id>` kalau akun punya membership di lebih dari satu universitas (akun dengan satu membership default resolve otomatis — lihat [MULTI-TENANT-ARCHITECTURE.md](./MULTI-TENANT-ARCHITECTURE.md) §10). Semua response sukses berbentuk `{success: true, message, data, meta}`; semua response gagal berbentuk `{success: false, message, errors}`.

## Akun Uji

Pakai akun dari [README.md](../README.md) §Akun Sample. Catatan koreksi per kondisi kode saat ini (README belum diperbarui untuk ini): role **`dosen`** dan **`academic_administrator`** (`akademik`) **sudah punya** permission admin di modul Akademik, bukan lagi kosong seperti tertulis di README:

- `dosen`: `students.read`, `study_programs.read`, `classes.read`, `courses.read`, `krs.read`, `grades.read`, `grades.create`, `grades.update`, `attendance.read`, `attendance.create`, `attendance.update`
- `akademik` (academic_administrator): ditambah `krs.create`, `krs.update` di atas daftar yang sudah ada di README

Role `mahasiswa` dan `dosenpa` **masih** tanpa permission admin sama sekali (self-service belum ada — lihat §11 catatan).

---

## 1. Autentikasi & Sesi — ✅ CRUD penuh (self-service)

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| AUTH-01 | Login berhasil | Akun `admin@undigital.test` aktif | `POST /login` | `{email, password: "password"}` | 200, `data.token` terisi, `data.user` berisi id/name/email | Tinggi |
| AUTH-02 | Login gagal — password salah | — | `POST /login` | email valid, password salah | 422, pesan generik "Email atau password salah." (tidak membocorkan apakah email terdaftar) | Tinggi |
| AUTH-03 | Login gagal — email tidak terdaftar | — | `POST /login` | email acak, password apa saja | 422, pesan sama persis dengan AUTH-02 (anti user-enumeration) | Tinggi |
| AUTH-04 | Rate limit percobaan login | — | `POST /login` gagal berulang (default 5×) dari IP+email yang sama, lalu coba lagi | 6 percobaan berturut-turut dengan password salah | Percobaan ke-6 (atau setelah limit `security.max_login_attempts` terlampaui): 429, "Terlalu banyak percobaan login." | Tinggi |
| AUTH-05 | Login akun nonaktif | Set `users.is_active = false` untuk satu akun uji | `POST /login` | kredensial akun nonaktif | 403, "Akun Anda tidak aktif." | Sedang |
| AUTH-06 | Ambil profil & permission sendiri | Sudah login | `GET /me` | — | 200, `data.user`, `data.roles`, `data.permissions` sesuai role akun | Tinggi |
| AUTH-07 | Akses endpoint terproteksi tanpa token | — | `GET /me` tanpa header Authorization | — | 401 | Tinggi |
| AUTH-08 | Lupa password | Email terdaftar | `POST /forgot-password` | `{email}` | 200, "Tautan reset password telah dikirim." (email terkirim lewat Mail channel) | Sedang |
| AUTH-09 | Lupa password — email tidak terdaftar | — | `POST /forgot-password` | email acak | Perilaku Laravel Password broker standar — verifikasi tidak bocor status pendaftaran email di response | Rendah |
| AUTH-10 | Reset password — token valid | Sudah minta reset di AUTH-08, token dari email/log diambil | `POST /reset-password` | `{token, email, password, password_confirmation}` | 200, password berubah, bisa login dengan password baru | Tinggi |
| AUTH-11 | Reset password — token kedaluwarsa/salah | — | `POST /reset-password` | token acak | 422, "Token reset password tidak valid atau sudah kedaluwarsa." | Sedang |
| AUTH-12 | Logout | Sudah login | `POST /logout` | — | 200; token yang dipakai tidak bisa dipakai lagi untuk request berikutnya | Tinggi |
| AUTH-13 | Logout dari semua perangkat | Login dari ≥2 device/token berbeda | `POST /logout-all` | — | 200; seluruh token milik user tercabut | Sedang |
| AUTH-14 | Lihat daftar sesi aktif | Sudah login | `GET /sessions` | — | 200, daftar sesi (device, last_activity_at) milik user sendiri saja | Sedang |
| AUTH-15 | Cabut satu sesi | Ada ≥2 sesi aktif | `DELETE /sessions/{id}` untuk sesi lain (bukan sesi yang sedang dipakai) | — | 200, sesi tercabut, token sesi itu langsung tidak valid | Sedang |
| AUTH-16 | Cabut sesi milik user lain | Ada 2 akun berbeda | User A coba `DELETE /sessions/{id}` dengan `{id}` milik user B | — | 403/404 (tidak boleh mencabut sesi orang lain) | Tinggi |
| AUTH-17 | Lihat daftar device | Sudah login, pernah login dari device tertentu | `GET /devices` | — | 200, daftar device milik user sendiri | Rendah |

---

## 2. Manajemen Peran & Izin (RBAC) — ✅ CRUD penuh

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| RBAC-01 | List roles | Login sebagai `owner@undigital.test` | `GET /roles` | — | 200, daftar role global + role kustom milik tenant sendiri | Sedang |
| RBAC-02 | Buat role kustom | `roles.create` | `POST /roles` | `{name: "Panitia Wisuda", description: "..."}` | 201, role baru tersimpan, `slug` ter-generate otomatis dari name | Sedang |
| RBAC-03 | Buat role — nama duplikat dalam tenant yang sama | Role dengan nama sama sudah ada | `POST /roles` | `{name: "Panitia Wisuda"}` (sama persis) | 201 tetap berhasil dengan `slug` bersuffix (mis. `panitia_wisuda_2`) — **bukan** ditolak | Rendah |
| RBAC-04 | Ubah role | Role kustom milik tenant ada | `PUT /roles/{id}` | `{name: "Panitia Wisuda 2027"}` | 200, nama berubah | Sedang |
| RBAC-05 | Hapus role sistem | Pilih role `slug=student` (is_system=true) | `DELETE /roles/{id}` | — | 409, "Role sistem tidak dapat dihapus." | Tinggi |
| RBAC-06 | Hapus role kustom | Role kustom tanpa user terpasang | `DELETE /roles/{id}` | — | 200, role terhapus | Sedang |
| RBAC-07 | Sinkronkan permission ke role | Role kustom ada, ada beberapa Permission id valid | `PUT /roles/{id}/permissions` | `{permission_ids: [...]}` | 200, permission role ter-replace persis dengan yang dikirim (bukan ditambah) | Tinggi |
| RBAC-08 | Sinkronkan permission — id tidak valid | — | `PUT /roles/{id}/permissions` | `{permission_ids: ["id-acak"]}` | 422 validasi gagal (`Rule::exists`) | Sedang |
| RBAC-09 | List permissions | `permissions.read` | `GET /permissions` | — | 200, seluruh permission sistem | Rendah |
| RBAC-10 | Buat permission baru | `permissions.create` | `POST /permissions` | `{name, resource, scope, action}` valid | 201 | Rendah |
| RBAC-11 | Hapus permission sistem | Permission `is_system=true` | `DELETE /permissions/{id}` | — | 409 ditolak (paralel dengan RBAC-05) | Sedang |
| RBAC-12 | List role milik satu user | `user_roles.read` | `GET /users/{user}/roles` | — | 200, daftar role + `university_id` per assignment | Sedang |
| RBAC-13 | Assign role ke user | `user_roles.create`, role & user valid | `POST /users/{user}/roles` | `{role_id}` | 201, mulai request berikutnya user itu punya permission dari role baru — **cek dengan login ulang/`GET /me`** | Tinggi |
| RBAC-14 | Assign role dengan masa berlaku | — | `POST /users/{user}/roles` | `{role_id, expires_at: "<tanggal lampau>"}` | 422 — `expires_at` harus `after:now` | Rendah |
| RBAC-15 | Cabut role dari user | Assignment ada | `DELETE /users/{user}/roles/{role}` | — | 200, permission dari role itu langsung tidak berlaku lagi | Tinggi |
| RBAC-16 | Endpoint RBAC diakses tanpa permission | Login sebagai `staff@civitasone.test` (tanpa permission) | `GET /roles` | — | 403 untuk semua endpoint di atas — bukti RBAC benar-benar menolak, bukan cuma "belum login" | Tinggi |
| RBAC-17 | List users | `users.read` | `GET /users?search=admin` | — | 200, hasil ter-filter oleh nama/email yang cocok | Sedang |

---

## 3. Platform Super Admin — ✅ CRUD penuh (lintas-tenant)

Semua endpoint di bawah prefix `/platform/*`. Hanya `superadmin@civitasone.local` yang seharusnya punya akses (role `super_admin` satu-satunya pemegang permission `platform_*`).

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| PLAT-01 | List universitas | Login sebagai Super Admin | `GET /platform/universities` | — | 200, seluruh universitas lintas-tenant (bukan cuma satu) | Tinggi |
| PLAT-02 | Buat universitas baru | `platform_universities.create` | `POST /platform/universities` | `{code, name}` (field wajib minimal) | 201, universitas baru berstatus awal (biasanya nonaktif/pending — cek `status`) | Tinggi |
| PLAT-03 | Buat universitas — code duplikat | Code sudah dipakai universitas lain | `POST /platform/universities` | `{code: "<code-existing>", name: "..."}` | 422, unique validation gagal | Sedang |
| PLAT-04 | Detail universitas | — | `GET /platform/universities/{id}` | — | 200, profil lengkap universitas | Rendah |
| PLAT-05 | Ubah profil universitas | — | `PATCH /platform/universities/{id}` | `{name: "Nama Baru"}` | 200, field berubah, field lain tidak ikut ter-reset (partial update) | Sedang |
| PLAT-06 | Aktivasi universitas | Universitas berstatus nonaktif/suspended | `POST /platform/universities/{id}/activate` | — | 200, status jadi aktif; setelah ini user tenant tersebut bisa login & pakai sistem | Tinggi |
| PLAT-07 | Suspend universitas | Universitas aktif, ada user tenant login | `POST /platform/universities/{id}/suspend` | — | 200, status suspended; user tenant tersebut ditolak akses berikutnya (cek lewat AUTH sebagai user tenant itu) | Tinggi |
| PLAT-08 | Statistik platform | — | `GET /platform/statistics` | — | 200, agregat lintas-tenant (jumlah universitas, user, dst.) | Rendah |
| PLAT-09 | Mulai support session | `support_sessions.create`, ada universitas target | `POST /platform/support-sessions` | `{university_id, reason: "Bantu troubleshoot tagihan"}` | 201, sesi tercatat; Super Admin sekarang bisa mengakses data tenant itu dengan jejak audit | Tinggi |
| PLAT-10 | Mulai support session tanpa alasan | — | `POST /platform/support-sessions` | `{university_id}` tanpa `reason` | 422 — `reason` wajib | Sedang |
| PLAT-11 | Akhiri support session | Sesi aktif ada | `POST /platform/support-sessions/{id}/end` | — | 200, sesi berakhir, akses ke tenant itu lewat sesi ini berhenti | Sedang |
| PLAT-12 | List support sessions | — | `GET /platform/support-sessions` | — | 200, riwayat sesi lintas-tenant untuk audit | Rendah |
| PLAT-13 | User tenant biasa akses endpoint platform | Login sebagai `owner@undigital.test` (bukan super admin) | `GET /platform/universities` | — | 403 — bukti endpoint platform benar-benar terpisah dari akses tenant, walau owner adalah admin tertinggi di tenant-nya | Tinggi |
| PLAT-14 | Super Admin tanpa pilih tenant akses data bisnis | Login Super Admin, **tidak** kirim `X-University-ID` | `GET /students` | — | Ditolak/kosong — Super Admin wajib pilih universitas dulu lewat Tenant Switcher, tidak otomatis melihat data bisnis universitas manapun | Tinggi |

---

## 4. Tenant Self-Service — ✅ CRUD penuh (scoped ke tenant sendiri)

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| TEN-01 | Lihat profil tenant sendiri | Login `owner@undigital.test` | `GET /tenant/profile` | — | 200, profil Universitas Nusantara Digital saja | Sedang |
| TEN-02 | Lihat pengaturan tenant | — | `GET /tenant/settings` | — | 200, daftar setting milik tenant sendiri | Rendah |
| TEN-03 | Ubah/tambah pengaturan tenant | — | `PUT /tenant/settings` | `{key: "academic.max_sks_default", value: "24", type: "integer"}` | 200, setting ter-upsert | Sedang |
| TEN-04 | Akses profil tenant lain lewat header | Login user tenant A, tidak punya membership di tenant B | `GET /tenant/profile` dengan `X-University-ID: <id-tenant-B>` | — | 403 — `tenant.access` middleware menolak, bukti isolasi tenant di level middleware, bukan cuma query scope | Tinggi |

---

## 5. Pengaturan Sistem & Feature Flag — ✅ CRUD penuh (update saja, tanpa create/delete)

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| SET-01 | List system settings | `system_settings.read` | `GET /system-settings` | — | 200 | Rendah |
| SET-02 | Ubah nilai setting | `system_settings.update` | `PUT /system-settings/{id}` | `{value: "10"}` | 200, nilai berubah; efeknya langsung terasa (mis. `security.max_login_attempts` yang dipakai AUTH-04) | Sedang |
| SET-03 | List feature flags | `feature_flags.read` | `GET /feature-flags` | — | 200 | Rendah |
| SET-04 | Matikan feature flag | `feature_flags.update`, ada flag yang mempengaruhi fitur lain (mis. delegasi approval — lihat APPR-09) | `PUT /feature-flags/{id}` | `{is_enabled: false}` | 200; fitur yang digerbangi flag ini langsung nonaktif untuk request berikutnya | Tinggi |
| SET-05 | Nyalakan kembali feature flag | Lanjutan SET-04 | `PUT /feature-flags/{id}` | `{is_enabled: true}` | 200; fitur aktif lagi | Sedang |
| SET-06 | Akses tanpa permission | Login `staff@civitasone.test` | `PUT /system-settings/{id}` | apa saja | 403 | Sedang |

---

## 6. Notifikasi — ✅ CRUD penuh (template & channel), tapi pengiriman nyata terbatas

**Catatan penting:** hanya channel **Database** dan **Mail** yang punya pengirim sungguhan. **Push, WhatsApp, SMS** cuma kerangka enum+skema — mengaktifkannya ditolak validasi (lihat NOTIF-05). Jangan uji "notifikasi WhatsApp benar-benar terkirim" — itu belum ada.

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| NOTIF-01 | Buat template notifikasi | `notification_templates.create` | `POST /notification-templates` | `{event_key: "krs.approved", name: "KRS Disetujui", channel: "mail", body_template: "Halo {{name}}, KRS Anda disetujui."}` | 201 | Sedang |
| NOTIF-02 | Ubah template | `notification_templates.update` | `PUT /notification-templates/{id}` | `{subject: "KRS Disetujui"}` | 200 | Rendah |
| NOTIF-03 | List channel notifikasi | `notification_channels.read` | `GET /notification-channels` | — | 200, 5 channel: database, mail, push, whatsapp, sms | Rendah |
| NOTIF-04 | Aktifkan channel yang sudah terimplementasi | `notification_channels.update` | `PUT /notification-channels/{id}` (channel=`mail` atau `database`) | `{is_enabled: true}` | 200 | Sedang |
| NOTIF-05 | Aktifkan channel yang belum terimplementasi | — | `PUT /notification-channels/{id}` (channel=`whatsapp`/`push`/`sms`) | `{is_enabled: true}` | 422, "Channel notifikasi 'whatsapp' belum memiliki implementasi pengiriman dan tidak dapat diaktifkan." — **ini justru perilaku benar, bukan bug** | Tinggi |
| NOTIF-06 | Matikan channel apa pun | — | `PUT /notification-channels/{id}` | `{is_enabled: false}` | 200 — mematikan selalu boleh, termasuk channel belum terimplementasi | Rendah |
| NOTIF-07 | Lihat preferensi notifikasi sendiri | Login user mana pun | `GET /notification-preferences` | — | 200, preferensi milik user sendiri saja | Rendah |
| NOTIF-08 | Set preferensi notifikasi | — | `POST /notification-preferences` | `{channel: "mail", is_enabled: false}` | 200/201, preferensi tersimpan; notifikasi channel itu tidak lagi terkirim ke user ini | Sedang |
| NOTIF-09 | Trigger notifikasi nyata (mis. lewat aksi approval) | Template aktif untuk event tertentu, channel database/mail aktif | Picu event (mis. approve satu approval step — lihat §8) | — | Ada baris baru di log notifikasi dengan status `sent` untuk channel database/mail | Sedang |

---

## 7. Unggah Berkas — ✅ CRUD penuh

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| FILE-01 | Unggah file valid | Login, ada file PDF < batas ukuran | `POST /file-uploads` (multipart) | `file` (pdf, mis. 500KB), `is_public=false` | 201, record tersimpan, file ada di storage | Tinggi |
| FILE-02 | Unggah file tipe tidak diizinkan | — | `POST /file-uploads` | file `.exe` atau `.zip` | 422 — hanya `pdf,jpg,jpeg,png,doc,docx,xls,xlsx` yang diterima | Tinggi |
| FILE-03 | Unggah file melebihi batas ukuran | System setting `file.max_upload_size_mb` (default 10MB) | `POST /file-uploads` | file > 10MB | 422 | Sedang |
| FILE-04 | Lihat detail file | File milik sendiri | `GET /file-uploads/{id}` | — | 200 | Rendah |
| FILE-05 | Unduh file | — | `GET /file-uploads/{id}/download` | — | 200, stream file dengan nama asli | Sedang |
| FILE-06 | Akses file publik oleh user lain | File di-upload dengan `is_public=true` | User B (bukan pengunggah) `GET /file-uploads/{id}` | — | 200 — file publik bisa diakses siapa saja yang login | Sedang |
| FILE-07 | Akses file privat oleh user lain | File `is_public=false` | User B (bukan pengunggah, tanpa `file_uploads.read`) coba akses | — | 403/404 | Tinggi |
| FILE-08 | Hapus file | Pengunggah sendiri atau `file_uploads.delete` | `DELETE /file-uploads/{id}` | — | 200, file terhapus dari storage & DB | Sedang |

---

## 8. Alur Persetujuan (Approval Workflow) — ✅ CRUD penuh, mesin state paling matang di sistem

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| APPR-01 | Buat workflow persetujuan | `approval_workflows.create` | `POST /approval-workflows` | `{name, workflowable_type, steps: [{name, approver_type: "role", approver_role_id}]}` | 201, workflow + langkah-langkahnya tersimpan berurutan | Tinggi |
| APPR-02 | Buat workflow tanpa steps | — | `POST /approval-workflows` | `{name, workflowable_type, steps: []}` | 422 — `steps` minimal 1 | Sedang |
| APPR-03 | List workflow | `approval_workflows.read` | `GET /approval-workflows` | — | 200 | Rendah |
| APPR-04 | Hapus workflow | `approval_workflows.delete` | `DELETE /approval-workflows/{id}` | — | 200 | Sedang |
| APPR-05 | List permintaan persetujuan — user biasa | Login user **tanpa** `approval_requests.read`, punya beberapa pengajuan sendiri + ada pengajuan orang lain | `GET /approval-requests` | — | 200, **hanya** menampilkan pengajuan yang diajukan user ini sendiri (`requested_by`), bukan seluruh tenant | Tinggi |
| APPR-06 | List permintaan persetujuan — punya `approval_requests.read` | Login `owner@undigital.test` | `GET /approval-requests` | — | 200, menampilkan seluruh pengajuan di tenant, bukan cuma milik sendiri | Tinggi |
| APPR-07 | Setujui langkah — sebagai approver yang ditugaskan | Ada approval request dengan step aktif ditugaskan ke user ini | `POST /approval-request-steps/{id}/approve` | `{comment: "Disetujui"}` (opsional) | 200, step berpindah status Approved; kalau ada step berikutnya, request pindah ke step itu; kalau ini step terakhir, request selesai Approved | Tinggi |
| APPR-08 | Setujui langkah — bukan approver yang ditugaskan | User lain (bukan approver step ini) | `POST /approval-request-steps/{id}/approve` | — | 403 | Tinggi |
| APPR-09 | Setujui langkah yang sudah diputuskan | Step sudah Approved/Rejected sebelumnya | `POST /approval-request-steps/{id}/approve` | — | 409, "Langkah persetujuan ini sudah diproses atau belum menjadi giliran saat ini." | Tinggi |
| APPR-10 | Tolak langkah tanpa komentar | Sebagai approver | `POST /approval-request-steps/{id}/reject` | `{}` (tanpa `comment`) | 422 — `comment` wajib untuk penolakan | Sedang |
| APPR-11 | Tolak langkah dengan aksi StopWorkflow | Step dikonfigurasi `action_on_reject=stop_workflow` | `POST /approval-request-steps/{id}/reject` | `{comment: "Tidak memenuhi syarat"}` | 200, seluruh request langsung berstatus Rejected, tidak lanjut ke step mana pun | Tinggi |
| APPR-12 | Tolak langkah dengan aksi ReturnToPreviousStep | Step ke-2 dari 2, dikonfigurasi `return_to_previous_step` | `POST /approval-request-steps/{id}/reject` | `{comment: "Perlu revisi"}` | 200, request kembali ke step 1 (bukan langsung Rejected) | Sedang |
| APPR-13 | Tolak step pertama dengan ReturnToPreviousStep (tidak ada step sebelumnya) | Step ke-1, dikonfigurasi `return_to_previous_step` | `POST /approval-request-steps/{id}/reject` | `{comment: "..."}` | 200, karena tidak ada step sebelumnya, otomatis fallback ke StopWorkflow — request Rejected | Sedang |
| APPR-14 | Delegasikan langkah — flag aktif | Feature flag delegasi ON (lihat SET-04/05), sebagai approver | `POST /approval-request-steps/{id}/delegate` | `{delegate_to: "<user_id>", comment: "Saya cuti"}` | 200, step berpindah ke user tujuan delegasi | Sedang |
| APPR-15 | Delegasikan langkah — flag mati | Feature flag delegasi OFF | `POST /approval-request-steps/{id}/delegate` | sama seperti APPR-14 | 403 — delegasi terlarang selama flag mati | Tinggi |
| APPR-16 | Lihat detail permintaan — bystander tanpa kepentingan | User yang bukan requester maupun approver, tanpa `approval_requests.read` | `GET /approval-requests/{id}` | — | 403/404 — tidak bisa melihat pengajuan orang lain | Tinggi |
| APPR-17 | `can_act` pada response detail | Sebagai approver step aktif | `GET /approval-requests/{id}` | — | `data.can_act = true`; setelah request selesai (lulus semua step), `can_act` kembali `false` untuk approver yang sama | Sedang |

---

## 9. Log Audit — 📖 Read-only (memang didesain begitu — log tidak boleh diubah/dihapus lewat API)

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| AUDIT-01 | List audit log | `audit_logs.read` | `GET /audit-logs` | — | 200, daftar perubahan data (siapa, kapan, data sebelum/sesudah) | Sedang |
| AUDIT-02 | Filter audit log | — | `GET /audit-logs?filter[action]=updated&filter[auditable_type]=...` | — | 200, hasil ter-filter sesuai `action`/`user_id`/`auditable_type` | Rendah |
| AUDIT-03 | Detail satu entri log | — | `GET /audit-logs/{id}` | — | 200 | Rendah |
| AUDIT-04 | Verifikasi log tercipta otomatis | — | Lakukan aksi apa pun yang mengubah data (mis. RBAC-04 ubah role) | — | Muncul entri baru di `GET /audit-logs` yang merekam aksi tersebut | Tinggi |
| AUDIT-05 | Tanpa permission | Login `staff@civitasone.test` | `GET /audit-logs` | — | 403 | Sedang |

---

## 10. Akademik — Data Induk (Mahasiswa, Dosen, Pegawai, Prodi, Kelas, Kurikulum, Mata Kuliah) — 📖 Read-only

Tujuh resource ini (`students`, `lecturers`, `employees`, `study-programs`, `class-sections`, `curriculums`, `courses`) semuanya berpola sama: `index` + `show` saja, **tidak ada** create/update/delete di backend sama sekali — data induk mahasiswa/dosen/dsb. hanya bisa dilihat, belum bisa diinput lewat sistem ini.

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| AKM-01 | List mahasiswa + pencarian | `students.read` | `GET /students?search=budi` | — | 200, hasil cocok nama/NIM mengandung "budi" | Sedang |
| AKM-02 | Filter mahasiswa per status/prodi/angkatan | — | `GET /students?filter[status]=active&filter[admission_year]=2024` | — | 200, hasil sesuai kombinasi filter | Sedang |
| AKM-03 | Detail mahasiswa | — | `GET /students/{id}` | — | 200, termasuk relasi `study_program` | Rendah |
| AKM-04 | Detail mahasiswa — id tidak ada | — | `GET /students/{id-acak}` | — | 404 | Sedang |
| AKM-05 | Coba buat mahasiswa baru lewat API | — | `POST /students` | data mahasiswa lengkap | 404/405 — route tidak ada, **bukan** 403. Bedakan dua kondisi ini: 403 = ditolak permission, 404/405 = fitur memang belum dibangun | Tinggi |
| AKM-06 | List dosen + filter fakultas/status aktif | `lecturers.read` | `GET /lecturers?filter[is_active]=true` | — | 200 | Sedang |
| AKM-07 | List pegawai + filter status aktif | `employees.read` | `GET /employees?filter[is_active]=true` | — | 200 | Rendah |
| AKM-08 | List program studi | `study_programs.read` | `GET /study-programs` | — | 200, termasuk `students_count`/`class_sections_count` teragregasi | Rendah |
| AKM-09 | List kelas & jadwal + filter prodi/term/aktif | `classes.read` | `GET /class-sections?filter[academic_term_id]=...` | — | 200, termasuk `enrolled_count` (jumlah KRS aktif di kelas itu) | Sedang |
| AKM-10 | Detail kelas menampilkan mata kuliah terkait | — | `GET /class-sections/{id}` | — | 200, `course_name`/`course_code`/`credits` ikut termuat dari relasi | Rendah |
| AKM-11 | List kurikulum + jumlah mata kuliah | `curriculums.read` | `GET /curriculums` | — | 200, `courses_count` per kurikulum | Rendah |
| AKM-12 | Detail kurikulum menampilkan daftar mata kuliah | — | `GET /curriculums/{id}` | — | 200, `data.courses` berisi seluruh mata kuliah kurikulum itu | Rendah |
| AKM-13 | List mata kuliah + filter kurikulum/semester | `courses.read` | `GET /courses?filter[semester_level]=1` | — | 200 | Sedang |
| AKM-14 | Akses salah satu resource di atas tanpa permission-nya | Login `staff@civitasone.test` | `GET /students`, `/lecturers`, dst. satu per satu | — | 403 di semua endpoint — permission per-resource benar-benar terpisah (permission `students.read` tidak otomatis membuka `lecturers.read`) | Tinggi |

---

## 11. Akademik — KRS (Kartu Rencana Studi) — ✅ CRUD penuh (baru dibangun sesi ini)

**Catatan arsitektur penting untuk desain skenario:** enrollment (student_id + class_section_id) unik — mahasiswa yang pernah *drop* dari suatu kelas dan mendaftar ulang ke kelas **yang sama** akan **memakai baris KRS yang sama** (status dikembalikan ke `enrolled`), bukan baris baru. `academic_term_id` diturunkan otomatis dari kelas yang dipilih, klien tidak mengirimkannya.

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| KRS-01 | Daftarkan mahasiswa ke kelas | `krs.create`, kelas aktif & belum penuh, mahasiswa belum ambil kelas ini | `POST /krs-items` | `{student_id, class_section_id}` | 201, status `enrolled` | Tinggi |
| KRS-02 | Daftarkan ke kelas yang sudah diambil | Mahasiswa sudah `enrolled` di kelas itu | `POST /krs-items` | sama seperti KRS-01 | 409, "Mahasiswa sudah terdaftar pada kelas ini." | Tinggi |
| KRS-03 | Daftarkan ke kelas penuh | `enrolled_count` kelas = `capacity` | `POST /krs-items` | — | 409, "Kelas sudah penuh." | Tinggi |
| KRS-04 | Daftarkan ke kelas nonaktif | `class_sections.is_active = false` | `POST /krs-items` | — | 409, "Kelas ini sudah tidak aktif dan tidak menerima peserta baru." | Sedang |
| KRS-05 | Daftarkan melebihi batas SKS | Mahasiswa sudah ambil SKS sejumlah batas maksimum berdasar IP semester lalu (lihat tabel §4.10 RANCANGAN-APLIKASI.md: IP<2.00→18, 2.00–2.49→20, 2.50–2.99→22, ≥3.00→24) | `POST /krs-items` untuk kelas tambahan yang membuat total SKS melebihi batas | — | 409, pesan menyebutkan batas SKS dan SKS yang sudah diambil | Tinggi |
| KRS-06 | Daftarkan mahasiswa baru tanpa riwayat IP | Mahasiswa belum pernah punya nilai di semester manapun | `POST /krs-items` sampai 24 SKS | — | Berhasil sampai 24 SKS (batas default), ke-25 SKS baru ditolak | Sedang |
| KRS-07 | Batalkan (drop) KRS aktif tanpa nilai | KRS berstatus `enrolled`, belum ada nilai | `PATCH /krs-items/{id}/drop` | — | 200, status jadi `dropped` | Tinggi |
| KRS-08 | Batalkan KRS yang sudah dinilai | KRS sudah punya `Grade` | `PATCH /krs-items/{id}/drop` | — | 409, "KRS yang sudah dinilai tidak dapat dibatalkan." | Tinggi |
| KRS-09 | Batalkan KRS yang sudah dibatalkan sebelumnya | Status sudah `dropped` | `PATCH /krs-items/{id}/drop` | — | 409, "KRS ini sudah dibatalkan sebelumnya." | Sedang |
| KRS-10 | Daftar ulang setelah drop | Lanjutan KRS-07 | `POST /krs-items` dengan `student_id`+`class_section_id` yang sama | — | 201, **id KRS sama** dengan baris yang di-drop, status kembali `enrolled` (verifikasi lewat `GET /krs-items` bahwa jumlah baris tidak bertambah) | Sedang |
| KRS-11 | List KRS + filter mahasiswa/kelas/term/status | `krs.read` | `GET /krs-items?filter[status]=enrolled&filter[student_id]=...` | — | 200 | Sedang |
| KRS-12 | Daftarkan tanpa permission `krs.create` | Login sebagai `mahasiswa@undigital.test` (belum ada self-service) atau role lain tanpa `krs.create` | `POST /krs-items` | — | 403 | Tinggi |
| KRS-13 | Daftarkan dengan `student_id`/`class_section_id` dari universitas lain | Login admin tenant A, id milik tenant B | `POST /krs-items` | `{student_id: "<id-tenant-B>", class_section_id: "<id-tenant-A>"}` | 404 (bukan 500) — `findOrFail` ter-scope tenant, id lintas-tenant dianggap tidak ditemukan | Tinggi |

---

## 12. Akademik — Penilaian (Nilai) — ✅ CRUD penuh (baru dibangun sesi ini)

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| NIL-01 | Input nilai — huruf otomatis dari skor | `grades.create`, KRS berstatus `enrolled` belum dinilai | `PUT /krs-items/{id}/grade` | `{score: 90}` | 200, `letter_grade` otomatis `A` (skor ≥85) | Tinggi |
| NIL-02 | Konversi skor ke huruf — batas ambang | — | `PUT /krs-items/{id}/grade` untuk skor 84, 74, 64, 54, 44, 34 | `{score: <masing-masing>}` | Huruf berturut-turut: `AB, B, BC, C, D, E` (verifikasi tiap batas ambang persis, bukan hanya satu titik) | Tinggi |
| NIL-03 | Override manual nilai huruf | — | `PUT /krs-items/{id}/grade` | `{score: 90, letter_grade: "B"}` | 200, `letter_grade` = `B` (skor tetap tersimpan 90, tapi huruf ikut input manual, bukan hasil konversi) | Sedang |
| NIL-04 | Ubah nilai yang sudah ada | Sudah ada Grade sebelumnya untuk KRS ini | `PUT /krs-items/{id}/grade` | `{score: 95}` | 200, **baris Grade yang sama ter-update** (bukan baris baru) — cek lewat `GET /grades` jumlah baris tidak bertambah | Tinggi |
| NIL-05 | Nilai tanpa skor | — | `PUT /krs-items/{id}/grade` | `{}` (tanpa `score`) | 422 — `score` wajib | Sedang |
| NIL-06 | Skor di luar rentang | — | `PUT /krs-items/{id}/grade` | `{score: 150}` atau `{score: -5}` | 422 — skor harus 0–100 | Sedang |
| NIL-07 | Nilai huruf tidak valid | — | `PUT /krs-items/{id}/grade` | `{score: 80, letter_grade: "Z"}` | 422 | Rendah |
| NIL-08 | Nilai KRS yang sudah dibatalkan | KRS berstatus `dropped` | `PUT /krs-items/{id}/grade` | `{score: 80}` | 409, "Tidak dapat menilai KRS yang sudah dibatalkan." | Tinggi |
| NIL-09 | Input nilai sebagai dosen pengampu | Login `dosen@undigital.test` (sekarang punya `grades.create`/`grades.update`) | `PUT /krs-items/{id}/grade` | `{score: 88}` | 200 — **catatan:** saat ini dosen bisa menilai KRS **kelas mana pun** di tenant-nya, belum dibatasi hanya kelas yang benar-benar diampunya (belum ada validasi kepemilikan kelas terhadap dosen) — uji ini untuk mendokumentasikan celah tersebut, bukan meng-assert-nya "aman" | Tinggi |
| NIL-10 | List nilai + filter mahasiswa/huruf | `grades.read` | `GET /grades?filter[letter_grade]=A` | — | 200 | Rendah |
| NIL-11 | Input nilai tanpa permission | Login role tanpa `grades.create`/`grades.update` | `PUT /krs-items/{id}/grade` | — | 403 | Tinggi |

---

## 13. Akademik — Absensi — ✅ CRUD penuh (baru dibangun sesi ini, batch per pertemuan)

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| ABS-01 | Rekam kehadiran satu kelas sekaligus | `attendance.create`, kelas punya ≥2 mahasiswa `enrolled` | `POST /class-sections/{id}/attendances` | `{meeting_number: 1, meeting_date: "2026-08-10", entries: [{krs_item_id, status: "present"}, {krs_item_id, status: "absent", notes: "Tanpa keterangan"}]}` | 201, satu baris `Attendance` per entry | Tinggi |
| ABS-02 | Rekam ulang pertemuan yang sama | Lanjutan ABS-01 | `POST /class-sections/{id}/attendances` dengan `meeting_number` sama, status berbeda | entries dengan status baru untuk krs_item_id yang sama | 201, **baris lama ter-update**, bukan duplikat (cek `GET /attendances` jumlah baris tidak bertambah untuk kombinasi krs_item+meeting_number yang sama) | Tinggi |
| ABS-03 | Rekam kehadiran mahasiswa di luar kelas ini | `krs_item_id` valid tapi bukan peserta `enrolled` di `class_section_id` ini | `POST /class-sections/{id}/attendances` | `entries: [{krs_item_id: "<krs-item-kelas-lain>", status: "present"}]` | 409, "Salah satu mahasiswa bukan peserta aktif kelas ini." — dan **tidak ada satu pun** baris tersimpan dari batch itu (transaksional, all-or-nothing) | Tinggi |
| ABS-04 | Status kehadiran tidak valid | — | `POST /class-sections/{id}/attendances` | `entries: [{krs_item_id, status: "bolos"}]` | 422 — hanya `present, permitted, sick, absent` yang valid | Sedang |
| ABS-05 | Entries kosong | — | `POST /class-sections/{id}/attendances` | `{meeting_number: 1, meeting_date: "...", entries: []}` | 422 — `entries` minimal 1 | Rendah |
| ABS-06 | List absensi + filter kelas/tanggal/status | `attendance.read` | `GET /attendances?filter[class_section_id]=...&date_from=...&date_to=...` | — | 200 | Sedang |
| ABS-07 | Rekam kehadiran tanpa permission | Role tanpa `attendance.create`/`attendance.update` | `POST /class-sections/{id}/attendances` | — | 403 | Tinggi |
| ABS-08 | Rekam kehadiran sebagai dosen | Login `dosen@undigital.test` | `POST /class-sections/{id}/attendances` | data valid | 200/201 — sama seperti NIL-09, dosen belum divalidasi harus pengampu kelas tersebut; dokumentasikan sebagai temuan | Sedang |

---

## 14. Akademik — Transkrip, IP & IPK — ✅ (baru dibangun sesi ini, murni perhitungan — tidak ada endpoint tulis)

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| TRX-01 | Transkrip mahasiswa dengan nilai di 2 semester | Ada nilai huruf di ≥2 `academic_term` berbeda | `GET /students/{id}/transcript` | — | 200, `data.terms` berisi satu baris per semester dengan `sks` dan `ip` benar; `data.ipk` = total bobot ÷ total sks seluruh semester; `data.total_sks` = jumlah sks lulus | Tinggi |
| TRX-02 | Verifikasi rumus IP satu semester | Skor diketahui (mis. A(4 sks) + B(2 sks)) | Hitung manual: (4×4.00 + 2×3.00) ÷ 6 = 3.33 | `GET /students/{id}/transcript` | `ip` semester itu = 3.33 (dibulatkan 2 desimal) | Tinggi |
| TRX-03 | Mahasiswa belum punya nilai sama sekali | Mahasiswa baru, belum ada Grade | `GET /students/{id}/transcript` | — | 200, `data.terms = []`, `ipk = 0`, `total_sks = 0` (bukan error) | Sedang |
| TRX-04 | KRS yang di-drop tidak dihitung | Ada KRS berstatus `dropped` yang (secara data lama) tetap punya nilai | `GET /students/{id}/transcript` | — | SKS & poin dari KRS yang `dropped` **tidak** ikut ke IP/IPK | Tinggi |
| TRX-05 | Transkrip tanpa permission | Role tanpa `students.read` | `GET /students/{id}/transcript` | — | 403 | Sedang |

---

## 15. Keuangan (Tagihan & Pembayaran) — 📖 Read-only

Belum ada endpoint buat tagihan, catat pembayaran, atau verifikasi — hanya bisa melihat data yang sudah ada di database (lewat seeder). **Ini gap paling kritikal secara bisnis**: kampus belum bisa memakai modul ini untuk operasional keuangan nyata.

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| FIN-01 | List tagihan + filter status/mahasiswa | `invoices.read` | `GET /invoices?filter[status]=unpaid` | — | 200 | Sedang |
| FIN-02 | Cari tagihan per periode | — | `GET /invoices?search=2026` | — | 200, cocok field `period` | Rendah |
| FIN-03 | Detail tagihan | — | `GET /invoices/{id}` | — | 200 | Rendah |
| FIN-04 | List pembayaran + filter invoice | `invoices.read` | `GET /payments?filter[invoice_id]=...` | — | 200 | Rendah |
| FIN-05 | Coba buat tagihan baru | — | `POST /invoices` | data tagihan | 404/405 — endpoint memang tidak ada | Tinggi |
| FIN-06 | Coba catat pembayaran manual | — | `POST /payments` | data pembayaran | 404/405 — endpoint memang tidak ada | Tinggi |

---

## 16. Beasiswa — 📖 Read-only

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| BEA-01 | List jenis beasiswa + pencarian nama/provider | `scholarships.read` | `GET /scholarships?search=KIP` | — | 200 | Rendah |
| BEA-02 | Filter beasiswa aktif | — | `GET /scholarships?filter[is_active]=true` | — | 200 | Rendah |
| BEA-03 | List pengajuan beasiswa + filter mahasiswa/status | `scholarships.read` | `GET /scholarship-applications?filter[status]=submitted` | — | 200 | Sedang |
| BEA-04 | Coba ajukan beasiswa (sisi mahasiswa) | — | `POST /scholarship-applications` | data pengajuan | 404/405 — endpoint memang tidak ada; portal mahasiswa yang menampilkan form ini (§24) **tidak benar-benar mengirim** ke sini | Tinggi |

---

## 17. Perpustakaan — 📖 Read-only

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| PUS-01 | List buku + pencarian judul/penulis/ISBN | `books.read` | `GET /books?search=<judul>` | — | 200 | Rendah |
| PUS-02 | Filter buku per kategori/status aktif | — | `GET /books?filter[category]=...` | — | 200 | Rendah |
| PUS-03 | Detail buku | — | `GET /books/{id}` | — | 200 | Rendah |
| PUS-04 | Coba pinjam buku | — | `POST /books/{id}/loans` (atau sejenis) | — | 404/405 — belum ada workflow peminjaman/pengembalian sama sekali | Tinggi |

---

## 18. Magang, PKL, KKN, MBKM — 📖 Read-only

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| MBKM-01 | List program + filter jenis/status/mahasiswa | `internships.read` | `GET /internships?filter[program_type]=mbkm&filter[status]=berlangsung` | — | 200 | Sedang |
| MBKM-02 | Cari per instansi | — | `GET /internships?search=<nama-instansi>` | — | 200 | Rendah |
| MBKM-03 | Detail program | — | `GET /internships/{id}` | — | 200 | Rendah |
| MBKM-04 | Coba daftar program magang baru | — | `POST /internships` | — | 404/405 | Sedang |

---

## 19. Alumni — 📖 Read-only

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| ALM-01 | List alumni + filter status kerja/tahun lulus | `alumni.read` | `GET /alumni?filter[employment_status]=bekerja&filter[graduation_year]=2025` | — | 200 | Rendah |
| ALM-02 | Detail alumni | — | `GET /alumni/{id}` | — | 200 | Rendah |
| ALM-03 | Coba konversi mahasiswa lulus jadi alumni | — | `POST /alumni` | — | 404/405 — belum ada proses konversi otomatis/manual | Sedang |

---

## 20. Skripsi/Tesis — 📖 Read-only

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| SKR-01 | List skripsi + filter status/jenis/mahasiswa | `theses.read` | `GET /theses?filter[status]=bimbingan` | — | 200 | Sedang |
| SKR-02 | Cari judul skripsi | — | `GET /theses?search=<kata-kunci>` | — | 200 | Rendah |
| SKR-03 | Detail skripsi | — | `GET /theses/{id}` | — | 200 | Rendah |
| SKR-04 | Coba ajukan judul skripsi baru | — | `POST /theses` | — | 404/405 — seluruh alur (ajukan judul → bimbingan → sidang → nilai) belum ada satu pun endpoint tulis | Tinggi |

---

## 21. Pengumuman — 📖 Read-only

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| ANN-01 | List pengumuman + filter target/pin | `announcements.read` | `GET /announcements?filter[is_pinned]=true` | — | 200 | Rendah |
| ANN-02 | Cari judul pengumuman | — | `GET /announcements?search=<kata-kunci>` | — | 200 | Rendah |
| ANN-03 | Detail pengumuman | — | `GET /announcements/{id}` | — | 200 | Rendah |
| ANN-04 | Coba buat pengumuman baru | `announcements.read` tidak termasuk create | `POST /announcements` | — | 404/405 | Sedang |

---

## 22. Laporan — 📖 Read-only (agregat), tapi ada fitur ekspor CSV yang layak diuji

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| LAP-01 | List jenis laporan tersedia | `reports.read` | `GET /reports` | — | 200, 4 jenis: `mahasiswa`, `akademik`, `keuangan`, `sdm` | Rendah |
| LAP-02 | Lihat laporan mahasiswa | — | `GET /reports/mahasiswa` | — | 200, `columns`, `rows` (maks 200 baris preview), `summary`, `total_rows`, `truncated` | Sedang |
| LAP-03 | Lihat laporan jenis tidak dikenal | — | `GET /reports/tidak-ada` | — | 404 | Sedang |
| LAP-04 | Ekspor laporan ke CSV | — | `GET /reports/akademik?export=csv` | — | 200, `Content-Type: text/csv`, file terunduh dengan nama `laporan-akademik-<tanggal>.csv`, header kolom sesuai `columns` | Tinggi |
| LAP-05 | Preview terpotong untuk data besar | Data laporan > 200 baris | `GET /reports/keuangan` | — | `data.truncated = true`, `data.rows` cuma 200 baris, tapi `data.total_rows` menunjukkan angka sebenarnya | Rendah |
| LAP-06 | Akses laporan tanpa permission | Role tanpa `reports.read` | `GET /reports` | — | 403 | Sedang |

---

## 23. Dashboard — 📖 Read-only, tapi logika penyaringan per-card layak diuji khusus

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| DASH-01 | Dashboard sebagai admin lengkap | Login `owner@undigital.test` | `GET /dashboard` | — | 200, seluruh summary card & chart yang permission-nya dipunyai muncul | Sedang |
| DASH-02 | Dashboard sebagai role terbatas | Login role yang cuma punya `students.read` (bukan `invoices.read`, dst.) | `GET /dashboard` | — | 200, tapi `data.summary` **tidak mengandung key** `unpaid_invoices` sama sekali (bukan `unpaid_invoices: 0`) — beda antara "tidak berwenang lihat" dan "datanya nol" | Tinggi |
| DASH-03 | Filter dashboard per program studi/term/tanggal | — | `GET /dashboard?study_program_id=...&date_from=...&date_to=...` | — | 200, angka menyesuaikan filter | Sedang |
| DASH-04 | Pending approvals — user biasa vs admin | Login tanpa `approval_requests.read` vs dengan | `GET /dashboard` dua kali (dua akun) | — | `summary.pending_approvals` untuk user biasa cuma menghitung pengajuan dia sendiri; untuk admin menghitung seluruh tenant (paralel dengan APPR-05/06) | Sedang |
| DASH-05 | Akses dashboard tanpa tenant terpilih | Login Super Admin, tanpa `X-University-ID` | `GET /dashboard` | — | 200 tapi `success:false`/pesan "Pilih universitas terlebih dahulu." (cek response body persis, bukan cuma status) | Sedang |

---

## 24. Portal Mahasiswa — 🧩 UI belum tersambung (jangan uji sebagai fitur fungsional)

18 halaman di menu Portal (`/portal/*`: KRS, Jadwal, KHS, Transkrip, Nilai, Absensi, Tugas, Kuis, Cuti, Surat, Beasiswa, Tagihan, Bimbingan Akademik, Bimbingan Skripsi, Pengumuman, Evaluasi Dosen, Wisuda) **memakai data statis di komponen**, nol pemanggilan API. Modul backend untuk sebagian besar ini (Cuti, Bimbingan, Wisuda, Evaluasi Dosen, Kuis, Tugas) **tidak ada sama sekali** di `app/Modules/`.

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| PORTAL-01 | Halaman portal bisa dibuka | Login sebagai `mahasiswa@undigital.test` | Buka tiap halaman `/portal/*` satu per satu | — | Halaman render tanpa error React, tapi data yang tampil **sama di setiap login** (bukti data statis, bukan dari akun yang sedang login) | Sedang |
| PORTAL-02 | Isi KRS lewat portal mahasiswa | — | Coba isi form KRS di `/portal/krs` dan submit | — | Tidak ada network request ke `/api/v1/*` saat submit (cek tab Network browser) — **bukti submit tidak benar-benar tersimpan**, ini bukan bug funngsional untuk dilaporkan, tapi konfirmasi status "belum tersambung" | Tinggi |
| PORTAL-03 | Ajukan cuti/surat lewat portal | — | Isi & submit form di `/portal/cuti` atau `/portal/surat` | — | Sama seperti PORTAL-02 — tidak ada backend `LeaveRequest`/`DocumentRequest` module untuk menerimanya | Tinggi |
| PORTAL-04 | Data portal vs data backend nyata | Mahasiswa X sungguhan sudah punya KRS asli lewat §11 | Login sebagai mahasiswa X, buka `/portal/krs` | — | Data portal **tidak** menunjukkan KRS asli yang baru dibuat lewat admin panel — bukti dua jalur data belum disatukan | Tinggi |

---

## 25. Lintas-Modul: Isolasi Multi-Tenant

Skenario ini berlaku untuk **semua** modul tenant-scoped (Academic, Finance, Scholarship, Library, Internship, Alumni, Thesis, Announcement, Notification, dll.) — uji minimal di satu-dua modul mewakili tapi pola berlaku umum karena semua pakai `TenantScoped`/`BelongsToInstitutionScope` yang sama.

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| TNT-01 | Data universitas lain tidak bocor di list | Login admin Universitas A, ada data serupa di Universitas B | `GET /students` (atau resource tenant-scoped lain) | — | Hasil **hanya** berisi data Universitas A, walau total data di DB mencakup B & C juga | Tinggi |
| TNT-02 | Akses langsung by-id milik tenant lain | Tahu id valid milik Universitas B | Login sebagai admin Universitas A, `GET /students/{id-milik-B}` | — | 404 (dianggap tidak ada, bukan 403 — konsisten dengan KRS-13) | Tinggi |
| TNT-03 | User dengan membership di 2 universitas — switch tenant | Akun dengan `UserUniversity` aktif di 2 tenant | `GET /students` dengan `X-University-ID` tenant A, lalu ulangi dengan tenant B | header berbeda | Hasil berbeda sesuai tenant yang diminta di header, bukan tenant default | Tinggi |
| TNT-04 | User tanpa membership di tenant yang diminta | Akun cuma member di tenant A | Kirim `X-University-ID` milik tenant B (bukan member) | — | 403 (`tenant.access` middleware menolak) | Tinggi |

---

## 26. Lintas-Modul: Perilaku Generik List (Pagination, Search, Sort, Filter)

Semua endpoint `index` di sistem ini memakai helper `ListQuery` yang sama — cukup uji sekali dengan mendalam di satu-dua endpoint representatif (mis. `/students`, `/krs-items`), polanya berlaku untuk seluruh 25+ endpoint list lainnya.

| ID | Skenario | Precondition | Langkah | Input | Expected Result | Prioritas |
|---|---|---|---|---|---|---|
| GEN-01 | Pagination default | Data > 20 baris | `GET /students` | — | `meta.per_page=20` (default), `meta.current_page=1`, `meta.total`, `meta.last_page` konsisten dengan jumlah data | Sedang |
| GEN-02 | Pagination — halaman kedua | — | `GET /students?page=2` | — | Baris berbeda dari halaman 1, tidak ada duplikat/terlewat di batas halaman | Sedang |
| GEN-03 | Custom per_page | — | `GET /students?per_page=5` | — | `meta.per_page=5`, jumlah baris di `data` ≤5 | Rendah |
| GEN-04 | Search — hasil kosong | — | `GET /students?search=zzzznotfound` | — | 200, `data: []`, `meta.total: 0` (bukan error) | Rendah |
| GEN-05 | Sort ascending/descending | — | `GET /students?sort=name` lalu `GET /students?sort=-name` | — | Urutan hasil terbalik antara dua request | Rendah |
| GEN-06 | Sort dengan field yang tidak diizinkan | — | `GET /students?sort=password` (field sensitif/tidak ada di whitelist `sortable`) | — | Diabaikan (fallback default) atau 422 — **tidak boleh** meng-crash atau mengekspos field yang tidak dimaksudkan | Sedang |
| GEN-07 | Filter dengan field yang tidak di-whitelist | — | `GET /students?filter[password]=x` | — | Diabaikan, tidak mempengaruhi query — bukti `filterable` whitelist benar-benar membatasi, bukan meneruskan filter mentah ke SQL | Tinggi |

---

## Ringkasan Prioritas Pengujian

Kalau harus mengurutkan dari mana mulai testing manual dengan waktu terbatas:

1. **Tinggi dulu, lintas §25 (isolasi tenant) dan §2/§16 RBAC** — ini kelas bug yang paling berbahaya (kebocoran data antar-universitas, eskalasi privilege) dan paling murah dites lebih awal sebelum fitur baru ditambah di atasnya.
2. **§11–14 Akademik (KRS/Nilai/Absensi/Transkrip)** — baru dibangun sesi ini, paling berisiko punya bug regresi yang belum ketemu di luar 20 test otomatis yang sudah ada. Perhatikan khusus NIL-09/ABS-08 (dosen bisa menilai/absen kelas yang bukan miliknya) — ini bukan bug baru ditemukan hari ini, tapi celah yang didokumentasikan sengaja supaya tidak terlupa sebelum modul ini dianggap "selesai".
3. **§8 Approval Workflow** — mesin state paling kompleks, paling banyak kombinasi (reject action, feature flag delegasi, can_act) yang gampang salah kalau ada perubahan.
4. **§3–4 Platform Super Admin & Tenant Self-Service** — kesalahan di sini berarti seluruh universitas kena dampak, bukan satu user.
5. Sisanya (modul read-only §10, §15–21) — regresi paling murah karena permukaannya kecil (cuma list/filter/detail), tapi tetap perlu sekali jalan penuh supaya ketahuan kalau suatu saat menu ini "kelihatan" sudah bisa CRUD padahal belum (mis. tombol yang salah ditambahkan ke frontend duluan sebelum API-nya ada).
