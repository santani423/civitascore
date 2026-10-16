# Catatan Implementasi Modul KEU — Administrasi Keuangan Mahasiswa

| | |
|---|---|
| **Tanggal** | 16 Oktober 2026 |
| **Cabang** | `feature/pmb-admission` (belum di-commit saat dokumen ditulis) |
| **Rancangan acuan** | [`docs/RANCANGAN_SISTEM_ADMINISTRASI_KEUANGAN_MAHASISWA_KEU.md`](../RANCANGAN_SISTEM_ADMINISTRASI_KEUANGAN_MAHASISWA_KEU.md) (kebutuhan — tidak diubah) |
| **Manual pengguna** | [`docs/manual-book/keuangan/`](../manual-book/keuangan/README.md) |
| **Pembaca** | Developer & administrator sistem. Bacaan wajib sebelum mengubah `app/Modules/Finance` atau mesin cekal SIAKAD. |

Dokumen ini mencatat **apa yang benar-benar dibangun**, keputusan teknis yang diambil, konfigurasi, ketergantungan eksternal, dan status tiap fitur. Kode BR-KEU/FR/KD mengacu ke dokumen rancangan.

---

## 1. Ringkasan implementasi

Modul KEU dibangun **di atas modul Finance yang sudah ada** (tabel `invoices` & `payments`, endpoint `GET /invoices`, `GET /invoices/{id}`, `GET /payments`, posting tagihan PMB) — bukan modul baru yang menduplikasi. Kolom lama dipertahankan artinya sehingga Dashboard utama, laporan lama, dan posting PMB tetap benar:

- `invoices.amount` = neto kewajiban saat ini; `invoices.paid_amount` = Σ alokasi berstatus *posted*.
- `payments` = **alokasi** dana ke tagihan/termin (bukan uang masuk); uang masuk dicatat di `payment_transactions`.

Cakupan yang dibangun: komponen biaya & tabel tarif berversi, penerbitan tagihan massal (simulasi → persetujuan), tagihan manual/draf, tagihan biaya SKS otomatis dari KRS, penyesuaian tagihan (maker-checker), pembayaran tunai (sesi kasir), transfer manual + verifikasi bukti, pembayaran online melalui adapter penyedia (aktif hanya bila kredensial ada), pembayaran sebagian, alokasi ke termin, kuitansi PDF, pembalikan, deposit & refund, rekonsiliasi, beasiswa/potongan, voucher, denda, UKT, cicilan, monitoring piutang/tunggakan, cekal keuangan terintegrasi SIAKAD (dengan dispensasi & riwayat), dashboard, 13 laporan dengan ekspor CSV, layanan mandiri mahasiswa, dan RBAC per peran.

## 2. Peta kode

| Lapisan | Lokasi | Isi |
|---|---|---|
| Migrasi | `Finance/Database/Migrations/2026_10_16_100000_extend_invoices_and_payments_for_keu.php` | Perluasan **aditif** `invoices` (nomor, semester, kelompok, bruto/potongan/penyesuaian, status `string(20)`, tunggakan, snapshot tarif, `lock_version`) & `payments` (transaksi, termin, jenis alokasi, status, pembalikan). Backfill: bruto = neto, tagihan PMB → kelompok `admission`. `down()` menolak rollback bila sudah ada status baru. |
| | `.../2026_10_16_100100_create_keu_tables.php` | 32 tabel KEU (lihat §4). |
| | `Academic/Database/Migrations/2026_10_16_110000_create_academic_blocks_tables.php` | `academic_blocks`, `academic_block_exemptions`, `academic_block_histories` — cekal milik SIAKAD. |
| Model & enum | `Finance/Models` (34), `Finance/Enums` (33), `Academic/Models/AcademicBlock*`, `Academic/Enums/{AcademicBlockStatus,BlockExemptionStatus}` | Semua model `TenantScoped` + ULID. Enum ber-`label()` Bahasa Indonesia. |
| Layanan domain | `Finance/Services` (27) | Seluruh aturan bisnis; controller tipis. Inti: `InvoiceLedger` (satu-satunya penghitung status), `PaymentPostingService` (satu-satunya jalur uang masuk), `BillingService`, `TariffResolver`, `AdjustmentService`, `DepositService`, `ClearanceService`. |
| Cekal SIAKAD | `Academic/Services/AcademicBlockService.php` | Simpan/cabut cekal idempoten, dispensasi per layanan, riwayat append-only, `assertAllowed()` di titik layanan. |
| Penyedia pembayaran | `Finance/Payments` | Kontrak `PaymentGateway`, `GatewayRegistry`, adapter `XenditGateway` (Xendit Invoice API). |
| Pendukung | `Finance/Support` | `Money` (hitung dalam sen), `FinanceNumbering` (nomor berurutan per tenant), `Approvals` (SoD), `KeuPermission` (katalog izin & peran), `KeuSettings`, `Presenter`, `AuditMask`. |
| HTTP | `Finance/Routes/api.php`, `Finance/Controllers/{Admin,Student}`, `WebhookController`, `ReceiptController` | ±150 endpoint: admin `/api/v1/finance/*`, mahasiswa `/api/v1/student/finance/*`, webhook publik `POST /api/v1/finance/webhooks/payments/{provider}`. |
| Konsol & jadwal | `Finance/Console/{KeuInstallCommand,KeuDailyCommand,KeuRecheckPaymentsCommand}`, `routes/console.php` | `keu:install`, `keu:daily` (00:30), `keu:recheck-payments` (tiap 10 menit). |
| Event & listener | `Finance/Events/StudentFinanceChanged`, `Finance/Listeners/*`, `Academic/Events/KrsSubmissionApproved` | Evaluasi ulang cekal saat kewajiban berubah; tagihan SKS saat KRS disetujui. |
| Tampilan | `Finance/Views/pdf/receipt.blade.php` | Kuitansi PDF (dompdf). |
| Konfigurasi | `config/keu.php`, `config/services.php`, `.env.example` (`XENDIT_*`) | Kuota kanal per paket, kredensial Xendit. |
| Frontend | `frontend/src/keu` | `routes.ts`, `api.ts`, `types.ts`, `components.tsx`, `permissions.ts`, `lookups.ts`, `columns.tsx`, `statementCsv.ts`, `adminNav.ts`, `AdminRoutes.tsx`, `pages/admin/*` (9 berkas, 24 halaman), `pages/portal/PortalFinancePages.tsx`. |
| Tes | `Finance/Tests/Feature/Keu*Test.php`, `tests/Helpers/Keu.php` | 41 tes KEU baru (lihat §11). |

## 3. Integrasi antarmodul

| Modul | Arah | Bentuk integrasi |
|---|---|---|
| **Academic (SIAKAD)** | KEU membaca | Mahasiswa (`students`: prodi, angkatan, jalur, sistem kuliah, status), `study_programs`, `faculties`, `academic_terms`, `krs_items` (jumlah SKS untuk tarif per SKS). Tidak ada salinan data mahasiswa di KEU. |
| | KEU → SIAKAD | Cekal: `ClearanceService` menghitung kekurangan per aturan lalu memanggil `AcademicBlockService::impose/revoke` (sumber `finance`). |
| | SIAKAD menegakkan | `KrsPlanService` (tambah/hapus/ajukan KRS & persetujuan dosen wali), `StudentExamController::start`, dan `PublicExamController::access` (tautan ujian berbasis NIM) memanggil `assertAllowed()` → 409 `ACADEMIC_BLOCKED`. Ringkasan KRS menampilkan pesan cekal. |
| | SIAKAD → KEU | Event `KrsSubmissionApproved` → listener `BillCreditsOnKrsApproval` menerbitkan/menyesuaikan tagihan SKS (pengaturan `keu.credit_fee.auto_bill_on_krs_approval`). Kegagalan tidak membatalkan persetujuan KRS. |
| **Admission (PMB)** | PMB → Finance | `AdmissionPostingService` tetap dipakai; tagihan hasil posting PMB berkelompok `admission` dan ikut tampil di KEU (riwayat, kartu piutang, laporan). |
| **FileManagement** | KEU memakai | Bukti transfer, bukti setoran kasir, bukti refund, dokumen beasiswa & UKT diunggah lewat `POST /file-uploads` lalu ditautkan dengan `FileAttacher`. `PaymentProof` mengimplementasikan `RestrictsFileAccess` (petugas `payment_transactions.read` atau mahasiswa pemilik). |
| **AuditLog** | KEU menulis | Persetujuan/penolakan, pembalikan, pembukaan nomor rekening refund, ekspor (beserta alasan), perubahan pengaturan, pembukaan cekal manual. |
| **Notification** | KEU menulis | 15 templat `keu.*` (`FinanceNotifier`) dibuat otomatis bila belum ada; tautan ke `/portal/keuangan`. |
| **Tenancy** | KEU membaca | Paket langganan aktif (`university_subscriptions` → `subscription_plans.limits`) untuk kuota kanal. |
| **Scholarship** | Tautan opsional | `discount_policies.scholarship_id` menautkan jenis potongan KEU ke program beasiswa yang sudah ada. Menu Beasiswa lama tetap (label "Program Beasiswa"). |
| **Dashboard utama** | Kompatibel | `DashboardStatsService` memakai label status dari enum; tautan Dashboard ke `/keuangan/tagihan?status=` & `/keuangan/pembayaran?date_from=` tetap berfungsi. |
| **UserManagement** | Seeder | `RolePermissionSeeder` (+22 resource KEU), `OrganizationalRoleSeeder` (peran baru `finance_manager`, `finance_cashier`; izin KEU digabung dari `KeuPermission::roles()`). |

## 4. Model data & tiga lapis status

Status **dipisah** (rancangan §9): status tagihan (`draft, unpaid, partial, paid, cancelled, waived`, plus penanda `is_overdue`), status transaksi (`created, pending, awaiting_verification, success, failed, expired, cancelled, reversed, refunded`), dan status rekonsiliasi (`not_required, pending_deposit, unreconciled, matched, exception, resolved`).

Tabel KEU: `fee_components, ukt_groups, fee_rate_tables, fee_rates, student_ukt_assignments, ukt_change_requests, billing_runs, billing_run_items, invoice_items, invoice_adjustments, installment_plans, installment_requests, invoice_installments, penalty_rules, penalty_accruals, discount_policies, financial_aid_awards, vouchers, voucher_redemptions, payment_channels, payment_transactions, payment_callbacks, payment_proofs, cashier_sessions, student_deposit_accounts, student_deposit_entries, reconciliation_batches, reconciliation_items, refunds, finance_sequences, collection_notes, finance_clearance_rules`.

Kunci unik pencegah duplikasi: `invoice_items.active_key` (mahasiswa:semester:komponen:kelompok), `penalty_accruals.active_key`, `academic_blocks.active_key`, `payment_transactions.{transaction_number, idempotency_key, receipt_number, (provider, provider_reference)}`, `payment_callbacks.(provider, provider_event_id)`, `invoices.(university_id, invoice_number)`.

## 5. Aturan bisnis utama yang ditegakkan server

| Aturan | Penegakan |
|---|---|
| Tarif berversi; tabel terbit tidak dapat diubah (BR-KEU-01) | 409 `RATE_TABLE_IMMUTABLE`; ubah lewat salin → versi baru. |
| Baris tarif bertabrakan memblokir pengajuan (BR-KEU-02) | `TariffResolver::conflicts()` → 409 `RATE_CONFLICT`. |
| Baris paling spesifik menang (BR-KEU-04) | Skor: prodi 64, UKT 32, jalur 16, kelas 8, semester 4, angkatan 2. |
| Tidak ada tagihan ganda (BR-KEU-07) | `invoice_items.active_key` unik; simulasi kedua → `ALREADY_BILLED`. |
| Tagihan terbit hanya berubah via penyesuaian beralasan (BR-KEU-08) | `AdjustmentService`; void ditolak bila sudah ada pembayaran/transaksi berjalan (409 `INVOICE_HAS_PAYMENTS`). |
| Status tagihan selalu turunan (BR-KEU-09) | Hanya `InvoiceLedger::recalculate()` yang menulis status/nominal. |
| Satu jalur uang masuk, idempoten (BR-KEU-41) | `PaymentPostingService::settle()` mengunci transaksi; transaksi sukses tidak dialokasikan dua kali. |
| Kelebihan bayar → deposit (BR-KEU-46/47) | Sisa alokasi dikreditkan ke deposit; penurunan tagihan yang sudah dibayar → alokasi `credit_out` + kredit deposit. |
| Saldo deposit tidak pernah negatif (BR-KEU-51) | Row lock akun; 409 `INSUFFICIENT_DEPOSIT`. |
| Refund hanya dari deposit, dana ditahan (KD-KEU-08, BR-KEU-58..60) | `hold` saat diajukan, `release` saat ditolak/gagal/batal, `payout` saat selesai. |
| Pemisahan tugas (SOD-KEU-*) | `Approvals::assertDistinct` → 409 `SELF_APPROVAL_FORBIDDEN` pada: tabel tarif, billing run, penyesuaian, aturan denda, beasiswa, cicilan khusus, refund, verifikasi bukti (pengunggah ≠ verifikator), pembalikan (pencatat/verifikator ≠ pembalik), verifikasi setoran kasir, penutupan rekonsiliasi (pencocok manual ≠ penutup), dispensasi. |
| Tunai hanya dalam sesi kas terbuka milik kasir (BR-KEU-49) | 409 `CASHIER_SESSION_REQUIRED`; batas per transaksi `keu.cash.max_per_transaction`. |
| Tanggal bayar efektif transfer manual = tanggal transfer di bukti (BR-KEU-37) | Denda dihitung sampai tanggal itu, bukan tanggal verifikasi. |
| Denda tidak atas denda; satu accrual per target (BR-KEU-36..40) | Tagihan kelompok `penalty` dikecualikan; `penalty_accruals.active_key`. |
| Voucher: tagihan belum dibayar, sebelum jatuh tempo, tanpa transaksi berjalan, kuota dijaga lock, percobaan salah dibatasi (BR-KEU-27..29) | Kode galat `VOUCHER_INVALID`, `VOUCHER_EXHAUSTED`, `VOUCHER_NOT_APPLICABLE`, `PENDING_TRANSACTION_EXISTS`, `VOUCHER_THROTTLED`. |
| Σ termin = sisa tagihan (BR-KEU-31) | Termin terakhir menyerap pembulatan; penyesuaian me-*rebalance* termin belum lunas. |
| Status dari front-end tidak pernah mengubah transaksi (BR-KEU-41) | Halaman kembali dari penyedia & tombol *Cek status* hanya memicu cek ulang ke API penyedia. |
| Callback: tanda tangan, dedupe, cek ulang (BR-KEU-44) | Callback tanpa tanda tangan sah → 401 & dicatat; event duplikat → 200 tanpa efek; nominal berbeda → `exception`, tidak diposting. |
| Kuota kanal online per paket | 409 `CHANNEL_QUOTA_EXCEEDED`; offline & sandbox tidak dihitung. |
| Cekal dicabut otomatis saat syarat terpenuhi (BR-KEU-66) | Event `StudentFinanceChanged` → evaluasi ulang sinkron di transaksi yang sama. |
| Ekspor wajib alasan & tercatat (BR-KEU-73) | Alasan ≥ 10 karakter; sel CSV diawali `= + - @` dinetralkan. |

## 6. Keputusan teknis & penyimpangan dari rancangan

| Kode | Topik | Keputusan |
|---|---|---|
| IMP-01 | Mesin persetujuan | Maker-checker **di dalam modul** (status + `Approvals::assertDistinct`), bukan mesin ApprovalWorkflow generik: persetujuan KEU satu langkah dan perlu konteks keuangan di layar. Alur multi-level belum ada. |
| IMP-02 | Prefix endpoint | Endpoint admin baru di `/finance/*`; tiga endpoint lama (`/invoices`, `/invoices/{id}`, `/payments`) dipertahankan untuk kompatibilitas. |
| IMP-03 | Kepemilikan cekal | Tabel cekal milik SIAKAD (`academic_blocks*`). Tabel aturan generik `academic_block_rules` **belum** dibuat; aturan keuangan di `finance_clearance_rules`. |
| IMP-04 | Layanan yang dapat dicekal | Hanya `krs` dan `exam` (`AcademicBlockService::ENFORCED_SERVICES`) yang ditegakkan di server. Nilai, transkrip, yudisium, wisuda tercantum di katalog tetapi **belum ditegakkan**, sehingga tidak dapat dipilih pada aturan cekal KEU. |
| IMP-05 | Kuota kanal | Urutan: penimpaan Super Admin (`keu.payment_channels.limit_override`) → `subscription_plans.limits.keu_payment_channels` → `config/keu.php` per kode paket → bawaan 3. Nilai acuan Growth/Business 3, Professional 4, Premium 5, Enterprise 6 **tidak diubah**. Paket CivitasOne saat ini (`trial/starter/professional/enterprise`) dipetakan di `config/keu.php`; keputusan final pemetaan paket tetap terbuka (rancangan Q-KEU-02). |
| IMP-06 | Payment gateway Xendit | `XENDIT_*` (dipakai bersama PMB). Satu transaksi = satu invoice Xendit yang dibatasi ke kanal pilihan (`payment_methods` dari `method` + `bank_code` kanal: VA `bca/bni/bri/mandiri/permata/bsi/cimb/bjb`, `qris`, e-wallet `ovo/dana/shopeepay/linkaja`, ritel `alfamart/indomaret`); mahasiswa membayar di halaman checkout Xendit (`redirect_url`). `external_id` = `KEU-<id transaksi>`. Satu akun Xendit hanya punya satu URL callback invoice, jadi callback masuk lewat `POST /api/v1/webhooks/xendit` dan diarahkan ke KEU menurut prefiks `KEU-`; token `x-callback-token` diverifikasi dan status dicek ulang lewat `GET /v2/invoices/{id}`; cek ulang berkala (`keu:recheck-payments`) menjadi cadangan. **Belum diverifikasi dengan akun nyata** (diuji dengan respons tiruan). |
| IMP-07 | Impor mutasi CSV | Endpoint menerima `rows` (JSON) atau `file_upload_id` CSV, tetapi `POST /file-uploads` hanya menerima pdf/jpg/png/doc/xls. UI membaca CSV **di peramban** (`statementCsv.ts`, parser nominal sama dengan server) lalu mengirim `rows` — FileManagement tidak diubah. |
| IMP-08 | Tingkat semester tarif | Dihitung dari tahun angkatan & semester berjalan; **cuti belum dikurangkan**. |
| IMP-09 | Pembukaan cekal manual | Mencabut cekal; evaluasi berikutnya dapat menerbitkannya lagi bila kewajiban belum dipenuhi. Untuk pengecualian sementara gunakan dispensasi. Pesan ini ditampilkan di UI. |
| IMP-10 | Atomisitas posting | Persetujuan bukti, pencatatan tunai, dan pemakaian deposit melakukan posting **di dalam transaksi DB yang sama** (savepoint) — bila posting gagal, bukti tetap *submitted*, kas sesi tidak bertambah, deposit tidak terdebit. |
| IMP-11 | Persetujuan billing run | Klaim atomik (`UPDATE … WHERE status = pending_approval AND approved_by IS NULL`) mencegah dua penyetuju memposting bersamaan. |
| IMP-12 | Filter tanggal transaksi | `date_from/date_to` memakai tanggal bayar efektif (`COALESCE(paid_at, created_at)`) agar sesuai grafik penerimaan Dashboard. |
| IMP-13 | Halaman lama | `frontend/src/pages/finance/*` (tagihan, detail, pembayaran) dan mock statis `PortalInvoicesPage` diganti halaman KEU pada path yang sama; `services/financeService.ts` & `types/finance.ts` dihapus karena tidak dipakai lagi. |
| IMP-14 | Rentang ID transaksi | Nomor: `TGH` tagihan, `KWT` kuitansi, `TRX` transaksi, `ADJ` penyesuaian, `RFD` refund, `BR` billing run, `KAS` sesi kas, `REK` rekonsiliasi — per tenant, berurutan (`finance_sequences` + row lock). |
| IMP-15 | Satu instruksi transfer aktif | Membuat instruksi transfer manual baru membatalkan instruksi lama (tanpa bukti) untuk target yang sama; membuat pembayaran online membatalkan instruksi online/transfer yang belum dibayar. |
| IMP-16 | Golongan UKT per semester | Tarif UKT memakai penetapan terbaru yang **sudah efektif** untuk semester yang ditagih (`TariffResolver::activeUktGroupId($student, $term)`); penetapan "mulai semester depan" tidak mengubah tagihan semester berjalan. |

## 7. Keamanan

- **Isolasi tenant**: semua model `TenantScoped`; query agregat laporan memakai query builder dengan filter `university_id` eksplisit (`FinanceQuery`) agar tidak ambigu saat join. Webhook tanpa konteks tenant mengaktifkan tenant milik transaksi yang ditemukan.
- **Isolasi mahasiswa**: endpoint `/student/finance/*` selalu meresolusi mahasiswa dari akun login; objek milik mahasiswa lain → 404 (tanpa route model binding).
- **RBAC**: middleware `permission:` di setiap endpoint (OR-match); aksi persetujuan memeriksa SoD di layanan. Tes `KeuRbacSeedTest` memastikan setiap slug di route ada di katalog dan peran ter-*seed*.
- **Uang**: dihitung dalam sen (integer) — tidak ada float pada nominal.
- **Data sensitif**: nomor rekening refund dienkripsi (`encrypted` cast) dan ditampilkan tersamar; pembukaan nilai asli hanya untuk izin `refunds.approve/finalize` dan tercatat di audit (`ViewedSensitive`).
- **Idempotensi**: `Idempotency-Key` wajib pada pembayaran online (kunci yang dipakai ulang untuk permintaan berbeda → 409 `IDEMPOTENCY_MISMATCH`).
- **Rate limit**: webhook 120/menit, pembuatan pembayaran 20/menit, voucher 10/menit + blokir 30 menit setelah percobaan salah berulang.

## 8. Konfigurasi

### 8.1 Pengaturan per kampus (`system_settings`, kunci `keu.*`)

Diubah lewat **Keuangan → Pengaturan Keuangan** (tercatat audit).

| Kunci | Bawaan | Arti |
|---|---|---|
| `keu.rate_lock_mode` | `cohort` | `cohort`: tarif terkunci angkatan; `current`: tabel tahun berjalan |
| `keu.rounding.unit` | 1 | Pembulatan nominal (rupiah) |
| `keu.billing.eligible_statuses` | `["active"]` | Status mahasiswa yang ditagih wajib |
| `keu.credit_fee.auto_bill_on_krs_approval` | true | Tagihan SKS otomatis saat KRS disetujui |
| `keu.credit_fee.due_days` | 21 | Jatuh tempo tagihan SKS |
| `keu.credit_fee.drop_credit_deadline` | kosong | Batas pengurangan SKS masih dikreditkan |
| `keu.discount.stacking_mode` | `sequential` | Cara gabung potongan |
| `keu.overdue.grace_days` | 3 | Tenggang sebelum dihitung tunggakan |
| `keu.manual_transfer.unique_code` | true | Kode unik 3 digit transfer manual |
| `keu.bank_accounts` | `[]` | Rekening penerimaan transfer manual (**wajib diisi** sebelum transfer manual dipakai) |
| `keu.payment.pending_ttl_minutes` | 1440 | Masa berlaku instruksi online |
| `keu.cash.max_per_transaction` | `0` | Batas tunai per transaksi (0 = tanpa batas) |
| `keu.dispensation.staff_max_days` / `max_days` | 7 / 30 | Batas dispensasi petugas / maksimal |
| `keu.clearance.pending_proof_grace_hours` | 0 | Bukti menunggu verifikasi dianggap memenuhi cekal selama X jam |
| `keu.penalty.due_days` | 7 | Jatuh tempo tagihan denda |
| `keu.voucher.max_failed_attempts` | 5 | Percobaan voucher salah sebelum diblokir 30 menit |
| `keu.reminder.offsets_days` | `[-7,-3,-1,1,7]` | Jadwal pengingat jatuh tempo |
| `keu.payment_channels.limit_override` | 0 | Penimpaan kuota kanal (hanya Super Admin) |

### 8.2 Lingkungan (`.env`)

`XENDIT_SECRET_KEY`, `XENDIT_WEBHOOK_TOKEN`, `XENDIT_BASE_URL`, `XENDIT_TIMEOUT` (lihat `.env.example`). Tanpa secret key, adapter tidak didaftarkan dan seluruh kanal online tampil **Belum terhubung**. Di Dashboard Xendit, isi URL callback *Invoices paid* dengan `<APP_URL>/api/v1/webhooks/xendit`.

## 9. Ketergantungan eksternal

| Kebutuhan | Status |
|---|---|
| Akun & kredensial Xendit | **Belum tersedia** — adapter ditulis & diuji dengan respons tiruan, belum diuji terhadap API nyata (test mode maupun live). Kredensial bersifat platform (satu akun untuk semua tenant). |
| Integrasi bank H2H / VA langsung bank, e-commerce lain | **Belum dibangun** — hanya kontrak `PaymentGateway` yang siap untuk adapter baru. |
| Refund otomatis via API penyedia | **Belum dibangun** — transfer refund dilakukan manual lalu dicatat. |
| Format mutasi bank resmi tiap bank | Belum ada; impor memakai CSV generik (tanggal, keterangan, nominal, referensi). |
| Penjadwal (`schedule:run` tiap menit) | Wajib untuk denda harian, tunggakan, kedaluwarsa, pengingat, evaluasi cekal, cek ulang online. |

## 10. Status fitur

| Fitur | Status | Catatan |
|---|---|---|
| Komponen biaya, tabel tarif berversi, pratinjau tarif | Selesai | Tarif `per_course` belum ada (hanya flat & per SKS). |
| Penerbitan massal (simulasi → setujui) | Selesai | Batas tampilan 1.000 baris per run. |
| Tagihan manual & draf, tagihan SKS dari KRS | Selesai | |
| Penyesuaian (naik/turun/bebas/bebas denda/void) | Selesai | |
| Tunai & sesi kasir, kuitansi PDF | Selesai | |
| Transfer manual + verifikasi bukti | Selesai | |
| Pembayaran online | **Parsial / belum aktif** | Alur, webhook, idempotensi, cek ulang selesai & diuji dengan adapter palsu; adapter Xendit belum diverifikasi dengan akun nyata; tidak aktif tanpa kredensial. |
| Pembayaran sebagian, alokasi termin, kelebihan → deposit | Selesai | |
| Pembalikan transaksi | Selesai | |
| Deposit & refund | Selesai | Transfer refund manual. |
| Rekonsiliasi (mutasi bank, settlement, setoran kas) | Selesai | Impor CSV generik. |
| Beasiswa/potongan, voucher | Selesai | Klaim ke sponsor (penagihan ke pemberi beasiswa) belum ada. |
| Denda | Selesai | |
| UKT | **Parsial** | Golongan, penetapan, peninjauan selesai; instrumen skoring kemampuan ekonomi belum ada. |
| Cicilan | Selesai | |
| Piutang & tunggakan, catatan penagihan | Selesai | |
| Cekal keuangan ↔ SIAKAD | **Parsial** | Ditegakkan untuk KRS & ujian; layanan lain belum. Surat bebas tanggungan (PDF) belum ada (status tersedia via API/halaman). |
| Dashboard & 13 laporan + ekspor CSV | Selesai | Ekspor XLSX/PDF belum ada. |
| Portal mahasiswa | Selesai | Portal orang tua/wali & aplikasi mobile belum ada. |
| Tutup buku periode, jurnal akuntansi | Belum | Kode akun (`gl_account_code`) baru disimpan. |
| Pembatasan data per fakultas/prodi (dekan/kaprodi) | Belum | Peran pimpinan melihat data seluruh kampus. |

## 11. Menjalankan & menguji

### 11.1 Pemasangan

> **Peringatan** — lakukan pada basis data pengembangan/staging terlebih dahulu dan cadangkan basis data produksi sebelum migrasi. Migrasi `extend_invoices_and_payments_for_keu` mengubah kolom `invoices.status` dari enum ke string (data lama tetap valid).

1. `php artisan migrate`
2. `php artisan db:seed --class="Modules\\UserManagement\\Database\\Seeders\\RolePermissionSeeder"` lalu `--class="Modules\\UserManagement\\Database\\Seeders\\OrganizationalRoleSeeder"` (peran baru: `finance_manager` Kepala Bagian Keuangan, `finance_cashier` Kasir Keuangan).
3. `php artisan keu:install {kode_kampus?}` — komponen bawaan (BPP, UKT nonaktif, SKS, DPP, PRAKTIKUM, WISUDA, CUTI, ADMIN), kanal offline `CASH` & `MANUAL_TRANSFER` (aktif, tidak dihitung kuota), skema `CICIL3` (nonaktif), aturan cekal contoh `KEU-KRS`, `KEU-TUNGGAKAN`, `KEU-UJIAN` (nonaktif). Aman diulang.
4. Penjadwal: `* * * * * php artisan schedule:run` (menjalankan `keu:daily` 00:30 dan `keu:recheck-payments` tiap 10 menit).
5. Frontend: `cd frontend && npm run build`.

### 11.2 Pengujian

- Tes otomatis: `php artisan test --parallel --processes=6` (sqlite in-memory dari `phpunit.xml` — tidak menyentuh basis data `.env`).
- Hanya KEU: `php artisan test app/Modules/Finance/Tests/Feature`.
- Frontend: `cd frontend && npm run build && npx oxlint src`.

Cakupan tes KEU (`app/Modules/Finance/Tests/Feature`):

| Berkas | Isi |
|---|---|
| `KeuPaymentTest` | Tunai sebagian→lunas & nomor kuitansi; sesi kas wajib & milik kasir; validasi server; kelebihan → deposit & pemakaian deposit; saldo kurang; transfer manual + verifikasi + `ALREADY_DECIDED`; tolak bukti & unggah ulang; berkas milik orang lain ditolak; pembalikan dengan SoD; selisih kas & verifikasi setoran; filter tanggal bayar efektif; instruksi transfer baru menggantikan yang lama. |
| `KeuBillingTest` | Tarif paling spesifik & imutabilitas & SoD; konflik tarif; billing run + SoD + idempotensi; mahasiswa tanpa tarif dilewati; tagihan manual/draf tak terlihat mahasiswa; penyesuaian SoD; void ditolak bila dibayar & penurunan → deposit; void tagihan belum dibayar; golongan UKT "mulai semester depan" tidak memengaruhi semester berjalan. |
| `KeuPolicyTest` | Cicilan standar otomatis & bayar termin; cicilan khusus perlu persetujuan; voucher kuota & transaksi berjalan; aturan denda SoD + kristalisasi saat lunas + tanpa denda ganda; plafon denda; refund hold/approve/complete & SoD/tolak. |
| `KeuIntegrationTest` | Online idempoten + callback bertanda tangan + cek ulang + dedupe (adapter palsu); nominal penyedia berbeda; kanal tanpa penyedia; kuota paket; rekonsiliasi cocok/tak teridentifikasi/duplikat & tutup; cekal KRS + dispensasi + cabut otomatis; dispensasi melebihi batas petugas; isolasi mahasiswa; isolasi tenant; konsistensi dashboard ↔ laporan ↔ ringkasan mahasiswa + ekspor; cekal ujian juga berlaku pada tautan ujian publik berbasis NIM. |
| `KeuRbacSeedTest` | Seluruh izin & peran KEU ter-*seed*; setiap izin di route Finance ada di katalog. |

Hasil terakhir (16 Oktober 2026): seluruh suite **415 tes — 398 lulus, 17 gagal**. Ke-17 kegagalan **sudah ada sebelum pekerjaan KEU** (baseline sebelum KEU: 374 tes, 357 lulus, 17 gagal — semuanya `makeParticipationClassSectionFixture()` tidak terdefinisi di `ExamViolationAndScoringTest` & `PublicExamAccessTest`) dan tidak terkait KEU. Seluruh 43 tes di `Finance/Tests/Feature` (41 baru + 2 lama) lulus. `npm run build` berhasil; `oxlint` tanpa error (1 peringatan KEU, pola yang sama dengan `pmb/AdminRoutes.tsx`).

## 12. Hal yang perlu diperhatikan saat mengubah modul

- Jangan menulis `invoices.status/amount/paid_amount` di luar `InvoiceLedger::recalculate()`.
- Uang masuk baru **harus** lewat `PaymentPostingService::settle()`.
- Layanan baru yang ingin dicekal: tegakkan dulu dengan `AcademicBlockService::assertAllowed()` di titik layanan, lalu tambahkan ke `ENFORCED_SERVICES`.
- Adapter penyedia baru: implementasikan `PaymentGateway`, daftarkan di `FinanceServiceProvider` **hanya bila kredensial ada**.
- Payload `Cache::remember` harus array murni (Collection bersarang rusak saat dibaca dari Redis).
