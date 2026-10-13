# 03 — Role dan Hak Akses

> **Status:** rancangan. Disusun 2026-10-13.
> Kembali ke [README](README.md) · Kanon role & permission: [README §5.5–§5.6](README.md#55-kanon-role) · Keamanan: [24](24-keamanan-audit-dan-kontrol-internal.md)

Dokumen ini adalah rujukan resmi untuk role, permission, cakupan data, persetujuan, dan pemisahan tugas modul KEU. Bila dokumen lain menyebut hak akses yang berbeda, dokumen inilah yang berlaku ([README §2](README.md#2-urutan-membaca-dan-dependensi)).

## Daftar Isi

1. [Mekanisme RBAC yang Sudah Ada](#1-mekanisme-rbac-yang-sudah-ada)
2. [Role](#2-role)
3. [Katalog Permission](#3-katalog-permission)
4. [Matriks Hak Akses](#4-matriks-hak-akses)
5. [Cakupan Data (FinanceScope)](#5-cakupan-data-financescope)
6. [Persetujuan](#6-persetujuan)
7. [Pemisahan Tugas (SoD)](#7-pemisahan-tugas-sod)
8. [Visibilitas Tingkat Kolom](#8-visibilitas-tingkat-kolom)
9. [Implementasi](#9-implementasi)

---

## 1. Mekanisme RBAC yang Sudah Ada

| Komponen | Perilaku saat ini | Dampak ke KEU |
|---|---|---|
| Tabel `roles`, `permissions`, `role_permissions`, `user_roles` | RBAC kustom per tenant. `user_roles` punya `scope_type`, `scope_id`, `expires_at` | Dipakai apa adanya |
| Slug permission `resource.action` | `action` dibatasi enum `PermissionAction`: `create, read, update, delete, approve, reject, export, import, publish, finalize` | Semua permission KEU memakai action dari enum ini |
| Middleware `permission:a,b` | Lolos bila pengguna memiliki **salah satu** permission | Endpoint yang dapat diakses beberapa role memakai daftar OR |
| `super_admin` | Melewati seluruh pemeriksaan permission dan isolasi tenant | Dibatasi oleh guard khusus untuk tindakan finansial ([KEU-BR-313](25-business-rules-dan-validasi.md)) |
| Scope fakultas/prodi | Kolom ada, tetapi **belum ditegakkan** (GAP-06) | KEU menyiapkan `FinanceScope` (§5) |
| ApprovalWorkflow | Approver langkah ditentukan `ApproverResolver` (role/user/position/atasan/kepala unit) dan bertindak di `POST /approval-request-steps/{step}/approve`. Tidak ada pencegahan *self-approval* (GAP-05) | KEU memeriksa ulang SoD sebelum menerapkan hasil persetujuan (§6, §7) |

## 2. Role

| Role (slug) | Label UI | Status | Ringkasan tanggung jawab | Kode kolom matriks |
|---|---|---|---|---|
| `super_admin` | Super Admin | Ada | Entitlement paket, kesehatan integrasi lintas tenant. Tindakan finansial tenant hanya *break-glass* | SA |
| `university_owner` | Pemilik Universitas | Ada | Pengaturan tenant; baca ringkasan keuangan | UO |
| `university_administrator` | Admin Universitas | Ada | Operasional tenant; baca ringkasan keuangan | UA |
| `finance_administrator` | Administrator Keuangan | Ada (diperluas) | Master data, tarif (draf), tagihan, kebijakan potongan/denda/cicilan/cekal | FA |
| `finance_officer` | Staf Keuangan | **Baru** | Verifikasi pembayaran, input manual, cicilan, deposit, koreksi & refund (pengajuan), rekonsiliasi | FO |
| `cashier` | Petugas Loket | **Baru** | Pembayaran tunai, sesi kas, kuitansi | CS |
| `finance_manager` | Kepala Bagian Keuangan / Bendahara | **Baru** | Penyetuju transaksi sensitif, penutupan periode, pengaturan sensitif | FM |
| `integration_administrator` | Administrator Integrasi | **Baru** [K] | Kredensial gateway, kanal, webhook, outbox, sinkronisasi cekal | IA |
| `rector`, `vice_rector` | Rektor / Wakil Rektor | Ada | Dashboard seluruh tenant; penyetuju tingkat lanjut di ApprovalWorkflow | RK |
| `dean`, `head_of_study_program` | Dekan / Kaprodi | Ada | Ringkasan piutang dalam scope; status cekal mahasiswa dalam scope | DK |
| `academic_administrator` | Bagian Akademik (BAAK) | Ada | Status cekal untuk layanan akademik | AA |
| `academic_advisor`, `lecturer` | Dosen Wali / Dosen | Ada | Status cekal mahasiswa bimbingan, tanpa nominal | DW |
| `auditor` | Auditor Internal | Ada | Baca seluruh data dan audit, ekspor terbatas | AU |
| `student` | Mahasiswa | Ada | Layanan mandiri keuangan atas data miliknya | MH |

> `finance_officer` dan `cashier` dapat digabung pada kampus kecil dengan memberi kedua role kepada satu akun. Pemisahan tugas tetap berlaku per tindakan (§7), bukan per role.

## 3. Katalog Permission

Kanon slug ada di [README §5.6](README.md#56-kanon-permission-slug-resourceaction). Tabel berikut menjelaskan makna setiap permission dalam operasi nyata.

| Permission | Makna operasional |
|---|---|
| `fee_components.read/create/update/delete` | Melihat, membuat, mengubah, menonaktifkan/mengaktifkan, menghapus komponen yang belum dirujuk |
| `tariffs.read` | Melihat jadwal & aturan tarif, validasi konflik, pratinjau `GET /tariffs/resolve` |
| `tariffs.create/update/delete` | Menyusun draf jadwal tarif, mengimpor aturan, mengajukan persetujuan, membuat revisi, menghapus draf |
| `tariffs.approve/reject` | Menyetujui/menolak jadwal tarif |
| `tariffs.publish` | Memberlakukan jadwal yang sudah disetujui |
| `ukt_groups.read/create/update` | Master golongan UKT |
| `ukt_determinations.read/create/update` | Melihat, menyusun, mengajukan penetapan UKT |
| `ukt_determinations.approve/reject` | Menyetujui/menolak penetapan; mencabut penetapan aktif |
| `ukt_determinations.import/export` | Impor massal / ekspor penetapan |
| `ukt_appeals.read/update/approve/reject` | Melihat, meninjau, memutuskan keberatan UKT |
| `invoices.read` (**Ada**) | Melihat tagihan dan riwayat. Juga membuka kolom nominal pada data cekal (§8) |
| `invoices.create` | Membuat tagihan individual dan menerbitkan draf |
| `invoices.update` | Mengubah draf; menerapkan/melepas voucher atas nama mahasiswa |
| `invoices.delete` | Membuang draf (menjadi `cancelled`, alasan `draft_discarded`) |
| `invoices.approve` | Menyetujui tagihan manual di atas ambang `finance.approval.thresholds.manual_invoice` |
| `invoices.export` | Ekspor daftar tagihan |
| `billing_runs.read/create/finalize` | Melihat, menyusun & mempratinjau, menjalankan tagihan massal |
| `installment_plans.read/create/update/approve/reject` | Melihat, mengajukan, menjadwalkan ulang/membatalkan, menyetujui/menolak cicilan |
| `invoice_disputes.read/update/approve/reject` | Melihat, mengajukan atau meninjau sengketa, menerima/menolak sengketa |
| `payments.read` | Melihat pembayaran, transaksi online, bukti bayar |
| `payments.create` | Mencatat pembayaran manual/tunai, membatalkan pembayaran `pending_verification` miliknya, mengalokasikan dana belum diterapkan, cek status transaksi ke provider |
| `payments.approve/reject` | Memverifikasi/menolak pembayaran manual dan pembayaran `mismatch` |
| `payments.export` | Ekspor pembayaran |
| `receipts.read/create/update` | Melihat & mencetak, menerbitkan ulang (salinan), mengganti kuitansi karena koreksi data non-nominal |
| `cashier_sessions.read/create/update/approve` | Melihat, membuka, menutup, memverifikasi sesi kas |
| `payment_channels.read/create/update` | Mengelola kanal (aktivasi dibatasi kuota). `update` juga membuka nomor rekening penuh |
| `payment_gateway_accounts.read/create/update` | Mengelola akun & kredensial provider, uji koneksi |
| `discount_schemes.read/create/update` | Mengelola skema beasiswa/potongan |
| `student_discounts.read/create/update/approve/reject` | Menetapkan, menangguhkan, mencabut, menyetujui penerima |
| `vouchers.read/create/update/approve/reject` | Mengelola dan menyetujui voucher |
| `late_fee_rules.read/create/update` | Mengelola aturan denda, simulasi, melihat denda terbentuk |
| `deposits.read/create/update/export` | Melihat ledger, konversi kelebihan bayar ke deposit, memakai/membekukan/menutup akun, ekspor |
| `finance_adjustments.read/create/approve/reject` | Mengajukan dan memutuskan koreksi, reversal, realokasi, pembatalan tagihan, potongan manual, pembebasan denda, penyesuaian deposit |
| `refunds.read/create/update/approve/reject` | Mengajukan, memproses/menyelesaikan, menyetujui/menolak refund |
| `reconciliations.read/create/import/update/finalize` | Membuat batch, impor berkas, mencocokkan & menyelesaikan item, menutup batch |
| `finance_hold_policies.read/create/update` | Mengelola dan mensimulasikan kebijakan cekal |
| `finance_holds.read` | Melihat status cekal (nominal hanya bila juga memiliki `invoices.read`) |
| `finance_holds.create/update` | Cekal manual, evaluasi ulang, sinkronisasi ulang, mengajukan pembukaan manual |
| `finance_holds.approve` | **Tambahan kanon** — menyetujui pembukaan cekal manual ([KEU-KD-32](29-glossary-dan-keputusan-bisnis.md#2-keputusan-desain-keu-kd)) |
| `finance_holds.export` | Ekspor daftar cekal |
| `finance_hold_exemptions.read/create/approve/reject` | Mengajukan dan memutuskan pengecualian; `approve` juga untuk mencabut pengecualian aktif |
| `finance_periods.read/update/finalize` | Melihat periode, mengajukan pembukaan kembali, menutup periode |
| `finance_reports.read/export` | Membuka dan mengekspor laporan KEU |
| `finance_dashboard.read` | Membuka dashboard keuangan |
| `finance_settings.read/update` | Melihat dan mengubah pengaturan `finance.*` |
| `finance_integrations.read/update` | Memantau webhook/outbox/sinkronisasi; memproses ulang |
| `finance_self_service.read/create/update` | Layanan mandiri mahasiswa atas data miliknya |

## 4. Matriks Hak Akses

Kode aksi: **R** read · **C** create · **U** update · **D** delete · **A** approve · **J** reject · **P** publish · **F** finalize · **I** import · **E** export. Sel kosong = tidak ada akses. `s` = dibatasi scope (§5). `SA` melewati semua pemeriksaan permission, tetapi tetap tunduk pada §7 dan [KEU-BR-313](25-business-rules-dan-validasi.md).

| Resource | FA | FO | CS | FM | IA | UO/UA | RK | DK | AA | DW | AU | MH |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| `fee_components` | RCUD | R | R | R | | R | R | | | | R | |
| `tariffs` | RCUDP | R | | RAJP | | R | R | | | | R | |
| `ukt_groups` | RCU | R | | R | | R | R | | | | R | |
| `ukt_determinations` | RCUIE | RCU | | RAJE | | R | R | Rs | | | RE | |
| `ukt_appeals` | RU | RU | | RAJ | | | R | | | | R | |
| `invoices` | RCUDE | RU | R | RAE | | R | RE | Rs | | | RE | |
| `billing_runs` | RCF | R | | R | | | | | | | R | |
| `installment_plans` | RCU | RCU | R | RAJ | | | | | | | R | |
| `invoice_disputes` | RUAJ | RU | | RAJ | | | | | | | R | |
| `payments` | RCE | RCAJ | RC | RAJE | R | R | R | | | | RE | |
| `receipts` | R | RCU | RC | RU | | | | | | | R | |
| `cashier_sessions` | R | RA | RCU | RA | | | | | | | R | |
| `payment_channels` | RCU | R | | R | RCU | | | | | | R | |
| `payment_gateway_accounts` | | | | R | RCU | | | | | | R | |
| `discount_schemes` | RCU | R | | R | | R | R | | | | R | |
| `student_discounts` | RCU | RCU | | RAJ | | | R | Rs | | | R | |
| `vouchers` | RCU | R | | RAJ | | | | | | | R | |
| `late_fee_rules` | RCU | R | | R | | | | | | | R | |
| `deposits` | RCUE | RCU | R | RE | | | | | | | RE | |
| `finance_adjustments` | RC | RC | | RAJ | | | R | | | | R | |
| `refunds` | RCU | RCU | | RAJ | | | RAJ¹ | | | | R | |
| `reconciliations` | RCIU | RCIU | | RF | R | | | | | | R | |
| `finance_hold_policies` | RCU | R | | R | | | | | R | | R | |
| `finance_holds` | RCUE | RCU | | RAE | R | | R | Rs | R | Rs | RE | |
| `finance_hold_exemptions` | RC | RC | | RAJ | | | | | R | | R | |
| `finance_periods` | R | R | | RUF | | | R | | | | R | |
| `finance_reports` | RE | R | | RE | | R | RE | Rs | | | RE | |
| `finance_dashboard` | R | R | R | R | | R | R | Rs | | | R | |
| `finance_settings` | RU² | | | RU | R | R | | | | | R | |
| `finance_integrations` | | | | R | RU | | | | | | R | |
| `finance_self_service` | | | | | | | | | | | | RCU |

¹ `rector`/`vice_rector` menyetujui refund dan pembukaan kembali periode sebagai **langkah kedua** ApprovalWorkflow di atas ambang (§6), bukan lewat endpoint domain.
² `finance_administrator` tidak dapat mengubah kunci sensitif: `finance.sod.enabled`, `finance.approval.thresholds`, `finance.retention.*`, `finance.numbering.prefixes` (hanya `finance_manager`) ([04 §5](04-master-data-dan-konfigurasi.md)).

Hak akses role yang sudah ada **tidak dicabut**: `finance_administrator` tetap memiliki `approval_requests.read`, `file_uploads.read`, `invoices.read`, `scholarships.read`. Role akademik (dekan, kaprodi, dosen wali, dosen, BAAK) hanya diberi permission KEU yang tercantum di matriks ini.

## 5. Cakupan Data (FinanceScope)

`FinanceScope` adalah penyaring tambahan setelah permission lolos. Implementasinya memakai `user_roles.scope_type/scope_id` sesuai rancangan `AcademicScope` di `academic-learning` ([KEU-A-10](29-glossary-dan-keputusan-bisnis.md#4-asumsi-keu-a)).

| Scope | Siapa | Penyaring yang diterapkan | Status |
|---|---|---|---|
| `tenant` | Role keuangan, pimpinan universitas, auditor, BAAK | `university_id = tenant aktif` (global scope `TenantScoped`, sudah ada) | Ada |
| `faculty` | Dekan | `students.study_program_id ∈ prodi milik fakultas scope` | **Bergantung** penegakan scope (GAP-06) |
| `study_program` | Kaprodi | `students.study_program_id = scope_id` | **Bergantung** GAP-06 |
| `advisee` | Dosen wali | `students.academic_advisor_id = lecturer milik pengguna` | Relasi `academic_advisor_id` sudah dipakai `krs_advising.*` |
| `class` | Dosen | Mahasiswa yang terdaftar (`krs_items` `enrolled`) di kelas yang diampu | Mengikuti aturan akademik |
| `self` | Mahasiswa | `students.user_id = auth()->id()`; `student_id` **tidak pernah** diterima dari klien ([KEU-BR-016](25-business-rules-dan-validasi.md)) | `students.user_id` sudah ada |

**Aturan sementara sebelum GAP-06 selesai:** role dengan scope `faculty`/`study_program` **tidak** diberi akses laporan dan daftar tagihan (pilihan aman), bukan diberi akses seluruh tenant ([28 §4](28-roadmap-implementasi.md#4-dependensi-lintas-tim)).

Penegakan scope dilakukan pada query (repository/service), bukan di frontend. Endpoint detail (`/invoices/{id}`) yang menunjuk data di luar scope mengembalikan **404**, sama dengan perilaku isolasi tenant.

## 6. Persetujuan

### 6.1 Dua mode persetujuan

| Mode | Kapan dipakai | Cara kerja |
|---|---|---|
| **Langsung** | Tidak ada `approval_workflows` aktif untuk kode tersebut, atau nominal di bawah ambang tingkat kedua | Penyetuju memanggil endpoint domain (mis. `POST /refunds/{id}/approve`) dan wajib memiliki `<resource>.approve`. Guard SoD dijalankan di service |
| **Workflow** | Ada `approval_workflows` aktif untuk kode tersebut (dipilih `WorkflowSelector`), atau nominal di atas ambang | Pengajuan memanggil `ApprovalRequestService::submit($requestable, $workflow, $requester)`. Approver bertindak di `POST /approval-request-steps/{step}/approve`. Saat event `ApprovalRequestApproved` diterima, listener KEU **memeriksa ulang SoD** terhadap seluruh approver, lalu menerapkan transisi domain dalam satu transaksi. Bila SoD dilanggar, transisi tidak diterapkan, entitas ditandai `rejected` dengan alasan `SELF_APPROVAL_FORBIDDEN`, dan `finance_manager` diberi tahu |

Status entitas KEU selama menunggu persetujuan adalah `pending_approval` (kanon [README §5.4](README.md#54-kanon-status)). Entitas menyimpan `approval_request_id`, `approved_by`, `approved_at`.

### 6.2 Katalog workflow keuangan

| Kode workflow | Entitas (`requestable`) | Penyetuju default | Tingkat kedua (ambang *contoh* [K]) |
|---|---|---|---|
| `finance_tariff_schedule` | `tariff_schedules` | `finance_manager` | `vice_rector` bila jadwal baru untuk tahun akademik berikutnya [K] |
| `finance_manual_invoice` | `invoices` (draf manual) | `finance_manager` | — (aktif bila nominal ≥ `manual_invoice`, *contoh* Rp25.000.000) |
| `finance_billing_run` | `billing_runs` | `finance_manager` | — (aktif bila `billing_run.enabled = true`) |
| `finance_adjustment` | `finance_adjustments` | `finance_manager` | `vice_rector` bila \|nominal\| ≥ `adjustment_level2` (*contoh* Rp10.000.000) |
| `finance_installment_plan` | `installment_plans` | `finance_manager` | — |
| `finance_student_discount` | `student_discounts` | `finance_manager` | — |
| `finance_voucher` | `vouchers` | `finance_manager` | — |
| `finance_ukt_determination` | `ukt_determinations` | `finance_manager` | — |
| `finance_refund` | `refunds` | `finance_manager` | `vice_rector` bila nominal ≥ `refund_level2` (*contoh* Rp10.000.000) ([KEU-Q-08](29-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-keu-q)) |
| `finance_hold_exemption` | `finance_hold_exemptions` | `finance_manager` | — |
| `finance_hold_release` | `finance_holds` (pembukaan manual) | `finance_manager` | — |
| `finance_period_reopen` | `finance_periods` | `finance_manager` | `vice_rector` (selalu) ([KEU-Q-16](29-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-keu-q)) |

Ambang disimpan di `finance.approval.thresholds` ([04 §5](04-master-data-dan-konfigurasi.md)).

### 6.3 Aksi yang **tidak** memerlukan persetujuan

| Aksi | Alasan | Kontrol pengganti |
|---|---|---|
| Penerbitan tagihan sesuai tarif `published` | Tarif sudah disetujui | Pratinjau, audit, sengketa |
| Verifikasi pembayaran manual | Verifikasi adalah kontrol itu sendiri | SoD perekam ≠ verifikator |
| Pembayaran tunai di loket | Uang diterima fisik | Sesi kas ditutup dan diverifikasi pengguna lain ([KEU-KD-25](29-glossary-dan-keputusan-bisnis.md#2-keputusan-desain-keu-kd)) |
| Alokasi dana belum diterapkan ke tagihan milik mahasiswa yang sama | Tidak mengubah total uang mahasiswa | Audit |
| Konversi kelebihan bayar ke deposit | Dana tetap milik mahasiswa yang sama | Audit |
| Pemakaian deposit untuk tagihan sendiri | Sama dengan di atas | Audit, kunci baris |
| Cekal/pembukaan otomatis oleh sistem | Mengikuti kebijakan yang disetujui | Histori, sinkronisasi |

## 7. Pemisahan Tugas (SoD)

Pemisahan tugas aktif bila `finance.sod.enabled = true` (default **true** [R]). Guard `SegregationOfDutiesGuard` dijalankan di service sebelum setiap aksi penyetujuan, baik mode langsung maupun workflow ([KEU-KD-17](29-glossary-dan-keputusan-bisnis.md#2-keputusan-desain-keu-kd), [KEU-BR-300](25-business-rules-dan-validasi.md)). Pelanggaran → 403 `SELF_APPROVAL_FORBIDDEN`.

| Aksi pemeriksa | Pembuat yang tidak boleh sama | Kolom pembanding |
|---|---|---|
| Menyetujui jadwal tarif | Penyusun/pengaju | `tariff_schedules.submitted_by` |
| Memverifikasi pembayaran manual | Perekam pembayaran (staf) | `payments.recorded_by` |
| Memverifikasi sesi kas | Petugas sesi | `cashier_sessions.cashier_user_id` |
| Menyetujui koreksi/reversal/pembatalan/realokasi/potongan manual/pembebasan denda | Pengaju | `finance_adjustments.requested_by` |
| Menyetujui refund | Pengaju | `refunds.requested_by` |
| Menyelesaikan refund (`completed`) | Penyetuju (disarankan [R]) | `refunds.approved_by` |
| Menyetujui rencana cicilan (diajukan staf) | Pengaju | `installment_plans.requested_by` |
| Menyetujui penerima beasiswa/potongan | Pengaju | `student_discounts.requested_by` |
| Menyetujui voucher | Pembuat | `vouchers.created_by` |
| Menyetujui penetapan UKT | Pengaju | `ukt_determinations.submitted_by` |
| Memutus keberatan UKT | Peninjau yang juga pengaju penetapan asal | `ukt_determinations.submitted_by` |
| Menyetujui pengecualian cekal | Pengaju | `finance_hold_exemptions.requested_by` |
| Menyetujui pembukaan cekal manual | Pengaju pembukaan | Riwayat `release_requested` di `finance_status_histories` |
| Menutup batch rekonsiliasi | Pengimpor (disarankan [R]) | `reconciliation_batches.imported_by` |
| Membuka kembali periode | Pengaju | `finance_periods` + `approval_requests.requester` |

Ketentuan tambahan:

1. Mahasiswa yang mengajukan sesuatu (bukti bayar, cicilan, keberatan) bukan "pembuat" dalam arti SoD staf; verifikator tetap staf yang berwenang.
2. Bila hanya ada satu staf keuangan dengan hak setuju (kampus kecil), tenant dapat menonaktifkan SoD. Perubahan `finance.sod.enabled` hanya oleh `finance_manager`, wajib alasan, dicatat `audit_logs` sebagai `forced_override`, dan muncul di laporan audit ([KEU-RPT-18](22-rancangan-laporan-dan-dashboard.md)).
3. **Rekomendasi perbaikan ApprovalWorkflow** (di luar KEU): `ApproverResolver` mengecualikan pemohon dari daftar approver langkah. Sebelum itu tersedia, pemeriksaan ulang di listener KEU tetap wajib.

## 8. Visibilitas Tingkat Kolom

| Data | Ditampilkan penuh kepada | Ditampilkan terbatas kepada | Bentuk terbatas |
|---|---|---|---|
| Nominal tunggakan pada data cekal | Pemilik `invoices.read` | AA, DW, DK tanpa `invoices.read` | Hanya status, alasan umum, unit penanggung jawab, instruksi ([KEU-BR-270](25-business-rules-dan-validasi.md)) |
| Nomor rekening kampus | Pemilik `payment_channels.update` (aksi "lihat penuh" diaudit `viewed_sensitive`); mahasiswa pada instruksi bayar | Peran lain | `•••• 1234` |
| Nomor rekening penerima refund | Pemroses refund (`refunds.update`) saat memproses | Peran lain | `•••• 1234` |
| Kredensial gateway | Tidak pernah dikembalikan | Semua | `credentials_configured: true` + 4 karakter terakhir |
| Ringkasan penilaian & dokumen UKT | FA, FO, FM, AU | Lainnya | Tidak ditampilkan |
| Data pribadi pengirim transfer | FO, FM, AU | CS | Nama saja |

## 9. Implementasi

| Langkah | Detail |
|---|---|
| Seeder permission | Tambahkan resource KEU ke `RolePermissionSeeder` dengan action sesuai §3 |
| Seeder role | Tambahkan `finance_officer`, `cashier`, `finance_manager`, `integration_administrator` ke `OrganizationalRoleSeeder::ROLE_LABELS` dan matriks §4. Akun demo per universitas: `keuangan-staf@`, `kasir@`, `bendahara@`, `integrasi@` [R] |
| Middleware route | `auth:sanctum`, `tenant.access` (ditambahkan juga ke route Finance lama), `permission:<slug>` |
| Policy | `InvoicePolicy`, `PaymentPolicy` (Ada) diperluas; policy baru per model. Policy memeriksa scope (§5) dan kepemilikan untuk mahasiswa |
| Guard | `SegregationOfDutiesGuard`, `SuperAdminFinanceGuard` ([KEU-BR-313](25-business-rules-dan-validasi.md)), `FinanceScope` |
| Resource API | Field nominal/rekening disaring berdasarkan §8 di `JsonResource`, bukan di frontend |
| Frontend | Menu dan tombol memakai permission yang sama ([21](21-rancangan-ui-ux-dan-menu.md)); penyembunyian tombol hanya kenyamanan, bukan kontrol |
| Uji | `KEU-TC-RBAC-*` dan `KEU-TC-SEC-*` ([26](26-skenario-pengujian.md)) |
