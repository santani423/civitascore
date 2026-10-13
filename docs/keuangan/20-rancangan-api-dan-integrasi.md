# 20 — Rancangan API dan Integrasi

> **Status:** rancangan. Disusun 2026-10-13. **Endpoint yang sudah ada hanya** `GET /invoices`, `GET /invoices/{invoice}`, `GET /payments` (semua `invoices.read`). Seluruh endpoint lain di dokumen ini adalah rancangan.
> Kembali ke [README](README.md) · Kanon kode error: [README §5.8](README.md#58-kanon-kode-error-errorscode) · Kanon event: [README §5.9](README.md#59-kanon-event-integrasi) · Hak akses: [03](03-role-dan-hak-akses.md)

Dokumen ini adalah rujukan resmi endpoint dan kontrak integrasi KEU. Bila dokumen fitur menyebut endpoint yang berbeda, dokumen inilah yang berlaku ([README §2](README.md#2-urutan-membaca-dan-dependensi)).

## Daftar Isi

1. [Konvensi](#1-konvensi)
2. [Pemisahan Jenis API](#2-pemisahan-jenis-api)
3. [Kontrak Umum: Error, Idempotensi, Konkurensi](#3-kontrak-umum-error-idempotensi-konkurensi)
4. [Katalog Endpoint Staf](#4-katalog-endpoint-staf)
5. [Katalog Endpoint Mahasiswa](#5-katalog-endpoint-mahasiswa)
6. [Endpoint Callback Provider, Publik, dan Platform](#6-endpoint-callback-provider-publik-dan-platform)
7. [Spesifikasi Rinci Endpoint Utama](#7-spesifikasi-rinci-endpoint-utama)
8. [Event dan Outbox](#8-event-dan-outbox)
9. [Integrasi Antarmodul](#9-integrasi-antarmodul)
10. [Kompatibilitas Endpoint Lama](#10-kompatibilitas-endpoint-lama)
11. [Pembatasan Laju](#11-pembatasan-laju)

---

## 1. Konvensi

| Aspek | Ketentuan (mengikuti kode yang ada) |
|---|---|
| Basis URL | `/api/v1` (route modul dimuat dari `app/Modules/*/Routes/api.php` di bawah middleware `tenant.resolve`) |
| Middleware staf | `auth:sanctum`, `tenant.access`, `permission:<slug>` (OR untuk beberapa slug) |
| Middleware mahasiswa | `auth:sanctum`, `tenant.access`, `permission:finance_self_service.<action>`; mahasiswa ditentukan dari `students.user_id = auth()->id()` |
| Envelope sukses | `{ "success": true, "message": "…", "data": …, "meta": { … } }` (`ApiResponse::success`) |
| Envelope daftar | `meta`: `current_page`, `per_page`, `total`, `last_page` (`ApiResponse::paginated`) |
| Envelope error | `{ "success": false, "message": "…", "errors": { "code": "…", … } }`; validasi Form Request 422 berisi galat per field |
| Daftar | `ListQuery`: `search`, `filter[kolom]=a,b`, `sort=-kolom`, `per_page` ≤ 100 |
| Uang | **String desimal** dua angka (`"7500000.00"`), tidak pernah angka JSON ([KEU-BR-011](25-business-rules-dan-validasi.md)) |
| Waktu | ISO 8601 dengan offset (`2026-10-13T09:15:00+07:00`) menurut zona tenant; tanggal murni `YYYY-MM-DD` |
| Id | ULID; nomor dokumen manusiawi disertakan sebagai field terpisah (`invoice_number`) |
| Nama route | Kebab-case jamak (`/billing-runs`, `/finance-hold-policies`), aksi non-CRUD sebagai sub-path `POST /{resource}/{id}/{aksi}` (pola modul HR yang ada) |
| Status | Tidak ada `PATCH {status}` generik; transisi melalui endpoint aksi ([README §4](README.md#4-arsitektur-umum-modul-keu) prinsip 3) |

## 2. Pemisahan Jenis API

| Jenis | Prefiks | Autentikasi | Konsumen |
|---|---|---|---|
| **Staf** | `/api/v1/…` (tanpa prefiks modul, mengikuti `/invoices` yang ada) | Sanctum + permission KEU | Admin SPA (`frontend/`) |
| **Mahasiswa** | `/api/v1/student/finance/…` (pola `student/*` yang ada) | Sanctum + `finance_self_service.*`; `student_id` tidak pernah diterima dari klien ([KEU-BR-016](25-business-rules-dan-validasi.md)) | Portal mahasiswa, aplikasi mobile |
| **Callback provider** | `/api/v1/webhooks/payments/{paymentGatewayAccount}` | Tanpa sesi; tanda tangan provider ([KEU-BR-302](25-business-rules-dan-validasi.md)) | Payment gateway |
| **Publik** | `/api/v1/public/receipts/verify/{code}` | Tanpa sesi; dibatasi laju | Siapa pun yang memegang kuitansi |
| **Platform** | `/api/v1/platform/…` | Super Admin | Pengaturan entitlement |
| **In-process** | Kontrak PHP (service class) | — | PMB, Semester Pendek, SIAKAD (modul satu aplikasi; sesuai `pmb/11` "in-process, bukan HTTP") |

## 3. Kontrak Umum: Error, Idempotensi, Konkurensi

### 3.1 Contoh error

```json
{
  "success": false,
  "message": "Alokasi melebihi saldo terutang tagihan.",
  "errors": {
    "code": "ALLOCATION_EXCEEDS_OUTSTANDING",
    "invoice_id": "01JB2K9Q5C7Z3W8XR4TNM6V0PA",
    "outstanding_amount": "2050000.00",
    "requested_amount": "2500000.00"
  }
}
```

Kode error kanon: [README §5.8](README.md#58-kanon-kode-error-errorscode), ditambah `ACTIVE_TRANSACTION_EXISTS` (409, tagihan sedang berada dalam transaksi online aktif).

### 3.2 Idempotensi

| Aspek | Ketentuan ([KEU-BR-017](25-business-rules-dan-validasi.md)) |
|---|---|
| Header | `Idempotency-Key: <UUID/ULID>` |
| Wajib pada | Endpoint bertanda **[IDEM]**: pembuatan pembayaran/transaksi/alokasi/konversi/pemakaian deposit, penerapan voucher, penerbitan tagihan, eksekusi billing run, pengajuan mahasiswa yang membuat sumber daya keuangan |
| Penyimpanan | `idempotency_keys` (cakupan = pengguna + nama route + kunci), 24 jam ([19 §3.17.3](19-rancangan-database-dan-relasi.md#3173-idempotency_keys--baru-kandidat-komponen-bersama-appsupport)) |
| Kunci sama, payload sama | Respons pertama dikembalikan apa adanya (status dan body), header `Idempotent-Replayed: true` |
| Kunci sama, payload berbeda | 409 `IDEMPOTENCY_KEY_REUSED` |
| Permintaan pertama masih diproses | 409 `IDEMPOTENCY_IN_PROGRESS` (klien mencoba lagi setelah jeda) |
| Tanpa header pada endpoint [IDEM] | 422 `VALIDATION_FAILED` (`Idempotency-Key` wajib) |

### 3.3 Konkurensi

- Penyuntingan oleh manusia mengirim `lock_version`; tidak cocok → 409 `STALE_VERSION` ([KEU-BR-013](25-business-rules-dan-validasi.md)).
- Mutasi nominal memakai kunci baris dengan urutan baku ([KEU-BR-010](25-business-rules-dan-validasi.md)); klien tidak perlu mengirim apa pun.

### 3.4 Audit

Endpoint bertanda **[AUD]** menulis `audit_logs` (aksi sesuai [24 §8](24-keamanan-audit-dan-kontrol-internal.md)) dan, untuk transisi status, `finance_status_histories` dalam transaksi yang sama. Endpoint ekspor menulis `exported`; unduhan berkas sensitif menulis `downloaded`.

## 4. Katalog Endpoint Staf

Kolom **Idem/Aud**: `I` = [IDEM], `A` = [AUD].

### 4.1 Master dan konfigurasi

| Method | URL | Tujuan | Permission | Idem/Aud |
|---|---|---|---|---|
| GET | `/fee-components` | Daftar komponen | `fee_components.read` | |
| POST | `/fee-components` | Buat komponen | `fee_components.create` | A |
| GET / PUT | `/fee-components/{feeComponent}` | Detail / ubah | `.read` / `.update` | A |
| PATCH | `/fee-components/{feeComponent}/activate`, `/deactivate` | Aktif/nonaktif | `fee_components.update` | A |
| DELETE | `/fee-components/{feeComponent}` | Hapus (belum dirujuk) | `fee_components.delete` | A |
| GET / PUT | `/fee-components/{feeComponent}/courses` | Pemetaan MK berbiaya | `.read` / `.update` | A |
| GET / POST | `/bank-accounts` | Rekening penerima | `payment_channels.read` / `.create` | A |
| PUT | `/bank-accounts/{bankAccount}` | Ubah | `payment_channels.update` | A |
| GET | `/bank-accounts/{bankAccount}/reveal` | Nomor lengkap | `payment_channels.update` | A (`viewed_sensitive`) |
| GET / PUT | `/finance-settings` | Pengaturan `finance.*` | `finance_settings.read` / `.update` | A |
| GET | `/finance-periods` | Periode | `finance_periods.read` | |
| POST | `/finance-periods/{financePeriod}/close` | Tutup periode | `finance_periods.finalize` | A |
| POST | `/finance-periods/{financePeriod}/reopen` | Ajukan buka kembali | `finance_periods.update` | A |
| GET / POST | `/ukt-groups` | Golongan UKT | `ukt_groups.read` / `.create` | A |
| PUT | `/ukt-groups/{uktGroup}` | Ubah | `ukt_groups.update` | A |

### 4.2 Tarif

| Method | URL | Tujuan | Permission | Idem/Aud |
|---|---|---|---|---|
| GET / POST | `/tariff-schedules` | Jadwal tarif | `tariffs.read` / `.create` | A |
| GET / PUT / DELETE | `/tariff-schedules/{tariffSchedule}` | Detail / ubah draf / hapus draf | `.read` / `.update` / `.delete` | A |
| GET / POST | `/tariff-schedules/{tariffSchedule}/rules` | Aturan | `.read` / `.update` | A |
| PUT / DELETE | `/tariff-schedules/{tariffSchedule}/rules/{tariffRule}` | Ubah/hapus aturan draf | `tariffs.update` | A |
| POST | `/tariff-schedules/{tariffSchedule}/import` | Impor XLSX/CSV | `tariffs.update` | A (`imported`) |
| GET | `/tariff-schedules/{tariffSchedule}/export` | Ekspor | `tariffs.read` | A (`exported`) |
| GET | `/tariff-schedules/{tariffSchedule}/validation` | Validasi konflik/celah | `tariffs.read` | |
| POST | `/tariff-schedules/{tariffSchedule}/submit`, `/withdraw` | Ajukan/tarik | `tariffs.update` | A |
| POST | `/tariff-schedules/{tariffSchedule}/approve`, `/reject` | Putuskan | `tariffs.approve` / `.reject` | A |
| POST | `/tariff-schedules/{tariffSchedule}/publish`, `/archive` | Berlakukan/arsipkan | `tariffs.publish` | A |
| POST | `/tariff-schedules/{tariffSchedule}/revise` | Versi baru | `tariffs.create` | A |
| GET | `/tariffs/resolve` | Pratinjau tarif mahasiswa | `tariffs.read` | |

### 4.3 Tagihan, tagihan massal, cicilan, sengketa

| Method | URL | Tujuan | Permission | Idem/Aud |
|---|---|---|---|---|
| GET | `/invoices` (**Ada**) | Daftar | `invoices.read` | |
| GET | `/invoices/{invoice}` (**Ada**) | Detail | `invoices.read` | |
| POST | `/invoices` | Buat draf / terbitkan | `invoices.create` | I (bila terbit), A |
| PUT | `/invoices/{invoice}` | Ubah draf | `invoices.update` | A |
| POST | `/invoices/{invoice}/recalculate` | Hitung ulang draf | `invoices.update` | A |
| POST | `/invoices/{invoice}/issue` | Terbitkan | `invoices.create` | I, A |
| DELETE | `/invoices/{invoice}` | Buang draf | `invoices.delete` | A |
| POST | `/invoices/{invoice}/corrections` | Ajukan koreksi | `finance_adjustments.create` | A |
| POST | `/invoices/{invoice}/cancellation-requests` | Ajukan pembatalan | `finance_adjustments.create` | A |
| POST | `/invoices/{invoice}/manual-discounts` | Ajukan potongan manual | `finance_adjustments.create` | A |
| POST | `/invoices/{invoice}/vouchers` | Terapkan voucher (staf) | `invoices.update` | I, A |
| DELETE | `/invoices/{invoice}/vouchers/{voucherRedemption}` | Lepas voucher | `invoices.update` | A |
| GET | `/invoices/{invoice}/history`, `/pdf` | Riwayat / cetak | `invoices.read` | |
| GET | `/invoices/export` | Ekspor | `invoices.export` | A |
| POST | `/invoices/{invoice}/disputes` | Sengketa (staf) | `invoice_disputes.update` | A |
| GET | `/invoice-disputes` | Antrean sengketa | `invoice_disputes.read` | |
| POST | `/invoice-disputes/{invoiceDispute}/review`, `/accept`, `/reject` | Tinjau/putuskan | `.update` / `.approve` / `.reject` | A |
| GET / POST | `/billing-runs` | Tagihan massal | `billing_runs.read` / `.create` | A |
| GET | `/billing-runs/{billingRun}`, `/items` | Detail / hasil | `billing_runs.read` | |
| POST | `/billing-runs/{billingRun}/preview`, `/cancel`, `/duplicate` | Pratinjau / batal / salin | `billing_runs.create` | A |
| POST | `/billing-runs/{billingRun}/execute` | Eksekusi | `billing_runs.finalize` | I, A |
| POST | `/billing-runs/{billingRun}/issue-drafts` | Terbitkan draf run | `invoices.create` | I, A |
| POST | `/installment-plans/preview` | Hitung usulan | `installment_plans.read` | |
| GET | `/installment-plans`, `/{installmentPlan}` | Daftar / detail | `installment_plans.read` | |
| POST | `/invoices/{invoice}/installment-plans` | Susun rencana | `installment_plans.create` | A |
| POST | `/installment-plans/{installmentPlan}/submit` | Ajukan | `installment_plans.create` | A |
| POST | `/installment-plans/{installmentPlan}/approve`, `/reject` | Putuskan | `.approve` / `.reject` | A |
| POST | `/installment-plans/{installmentPlan}/reschedule`, `/cancel` | Jadwal ulang / batal | `installment_plans.update` | A |

### 4.4 Pembayaran, kuitansi, loket, online

| Method | URL | Tujuan | Permission | Idem/Aud |
|---|---|---|---|---|
| GET | `/payments` (**Ada**) | Daftar | `payments.read` (juga menerima `invoices.read` lama) | |
| GET | `/payments/{payment}` | Detail | `payments.read` | |
| POST | `/payments` | Catat tunai / transfer manual | `payments.create` | I, A |
| POST | `/payments/{payment}/verify`, `/reject` | Verifikasi / tolak | `payments.approve` / `.reject` | A |
| POST | `/payments/{payment}/cancel` | Batalkan (pending) | `payments.create` | A |
| POST | `/payments/{payment}/allocations` | Alokasi manual | `payments.create` | I, A |
| POST | `/payments/{payment}/auto-allocate` | Alokasi FIFO | `payments.create` | I, A |
| POST | `/payments/{payment}/convert-to-deposit` | Konversi ke deposit | `deposits.create` | I, A |
| POST | `/payments/{payment}/reversal-requests` | Ajukan reversal | `finance_adjustments.create` | A |
| POST | `/payments/{payment}/reallocation-requests` | Ajukan realokasi | `finance_adjustments.create` | A |
| POST | `/payment-allocations/{paymentAllocation}/reversal-requests` | Ajukan pembalikan alokasi | `finance_adjustments.create` | A |
| GET | `/payments/export` | Ekspor | `payments.export` | A |
| GET | `/payment-proofs` | Antrean bukti | `payments.read` | |
| GET | `/payment-proofs/{paymentProof}/file` | Unduh bukti | `payments.read` | A (`downloaded`) |
| GET | `/receipts`, `/receipts/{receipt}/pdf` | Kuitansi | `receipts.read` | |
| POST | `/receipts/{receipt}/reprint` | Salinan | `receipts.create` | A |
| POST | `/receipts/{receipt}/replace` | Ganti (data non-nominal) | `receipts.update` | A |
| GET / POST | `/cashier-sessions` | Sesi kas | `cashier_sessions.read` / `.create` | A |
| GET | `/cashier-sessions/{cashierSession}` | Detail | `cashier_sessions.read` | |
| POST | `/cashier-sessions/{cashierSession}/close` | Tutup | `cashier_sessions.update` | A |
| POST | `/cashier-sessions/{cashierSession}/verify`, `/reopen` | Verifikasi / buka ulang | `cashier_sessions.approve` | A |
| GET | `/payment-transactions`, `/{paymentTransaction}` | Transaksi online | `payments.read` | |
| POST | `/payment-transactions/{paymentTransaction}/check-status` | Cek status provider | `payments.create` | A |
| GET / POST | `/payment-gateway-accounts` | Akun provider | `payment_gateway_accounts.read` / `.create` | A |
| PUT | `/payment-gateway-accounts/{paymentGatewayAccount}` | Ubah (kredensial tidak dikembalikan) | `payment_gateway_accounts.update` | A |
| POST | `/payment-gateway-accounts/{paymentGatewayAccount}/test-connection` | Uji koneksi | `payment_gateway_accounts.update` | A |
| GET / POST | `/payment-channels` | Kanal | `payment_channels.read` / `.create` | A |
| PUT | `/payment-channels/{paymentChannel}` | Ubah | `payment_channels.update` | A |
| PATCH | `/payment-channels/{paymentChannel}/activate`, `/deactivate`, `/health` | Status | `payment_channels.update` | A |
| GET | `/payment-channels/quota` | Kuota terpakai | `payment_channels.read` | |
| GET | `/payment-webhook-events`, `/{paymentWebhookEvent}` | Pemantauan webhook | `finance_integrations.read` | |
| POST | `/payment-webhook-events/{paymentWebhookEvent}/reprocess` | Proses ulang | `finance_integrations.update` | A |
| GET | `/finance-outbox-events` | Pemantauan outbox | `finance_integrations.read` | |
| POST | `/finance-outbox-events/{financeOutboxEvent}/retry` | Kirim ulang | `finance_integrations.update` | A |

### 4.5 Beasiswa, potongan, voucher, denda

| Method | URL | Tujuan | Permission | Idem/Aud |
|---|---|---|---|---|
| GET / POST | `/discount-schemes` | Skema | `discount_schemes.read` / `.create` | A |
| GET / PUT | `/discount-schemes/{discountScheme}` | Detail / ubah | `.read` / `.update` | A |
| PATCH | `/discount-schemes/{discountScheme}/activate`, `/deactivate` | Status | `discount_schemes.update` | A |
| GET / POST | `/student-discounts` | Penerima | `student_discounts.read` / `.create` | A |
| POST | `/student-discounts/import` | Impor | `student_discounts.create` | A |
| POST | `/student-discounts/{studentDiscount}/approve`, `/reject` | Putuskan | `.approve` / `.reject` | A |
| POST | `/student-discounts/{studentDiscount}/suspend`, `/resume`, `/revoke` | Ubah status | `student_discounts.update` | A |
| POST | `/student-discounts/{studentDiscount}/apply-to-issued-invoices` | Penerapan susulan | `student_discounts.update` | A |
| GET | `/student-discounts/eligibility-review` | Hasil evaluasi | `student_discounts.read` | |
| POST | `/scholarship-applications/{scholarshipApplication}/award` | Tetapkan dari aplikasi | `student_discounts.create` | A |
| GET / POST | `/vouchers` | Voucher | `vouchers.read` / `.create` | A |
| GET / PUT | `/vouchers/{voucher}` | Detail / ubah | `.read` / `.update` | A |
| POST | `/vouchers/{voucher}/submit`, `/approve`, `/reject` | Persetujuan | `.update` / `.approve` / `.reject` | A |
| PATCH | `/vouchers/{voucher}/activate`, `/deactivate` | Status | `vouchers.update` | A |
| GET | `/vouchers/{voucher}/redemptions` | Riwayat | `vouchers.read` | |
| POST | `/vouchers/generate` | Kode massal | `vouchers.create` | A |
| GET / POST | `/late-fee-rules` | Aturan denda | `late_fee_rules.read` / `.create` | A |
| GET / PUT | `/late-fee-rules/{lateFeeRule}` | Detail / ubah | `.read` / `.update` | A |
| POST | `/late-fee-rules/{lateFeeRule}/simulate` | Simulasi | `late_fee_rules.read` | |
| GET | `/late-fee-assessments` | Denda terbentuk | `late_fee_rules.read` | |
| POST | `/late-fee-assessments/run` | Jalankan job | `late_fee_rules.update` | A |
| POST | `/late-fee-assessments/{lateFeeAssessment}/waive`, `/reverse` | Ajukan pembebasan/koreksi | `finance_adjustments.create` | A |

### 4.6 Deposit, UKT

| Method | URL | Tujuan | Permission | Idem/Aud |
|---|---|---|---|---|
| GET | `/deposit-accounts`, `/{depositAccount}`, `/{depositAccount}/transactions` | Saldo & mutasi | `deposits.read` | |
| POST | `/deposit-accounts/{depositAccount}/apply` | Pakai untuk tagihan | `deposits.update` | I, A |
| POST | `/deposit-accounts/{depositAccount}/freeze`, `/unfreeze`, `/close`, `/reopen` | Status akun | `deposits.update` | A |
| POST | `/deposit-accounts/{depositAccount}/adjustments` | Ajukan penyesuaian | `finance_adjustments.create` | A |
| GET | `/deposit-accounts/export` | Ekspor | `deposits.export` | A |
| GET / POST | `/ukt-determinations` | Penetapan | `ukt_determinations.read` / `.create` | A |
| GET / PUT | `/ukt-determinations/{uktDetermination}` | Detail / ubah draf | `.read` / `.update` | A |
| POST | `/ukt-determinations/{uktDetermination}/submit`, `/withdraw` | Ajukan / tarik | `ukt_determinations.update` | A |
| POST | `/ukt-determinations/{uktDetermination}/approve`, `/reject`, `/revoke` | Putuskan / cabut | `.approve` / `.reject` / `.approve` | A |
| POST | `/ukt-determinations/{uktDetermination}/documents` | Unggah dokumen | `ukt_determinations.update` | A |
| POST | `/ukt-determinations/import` | Impor | `ukt_determinations.import` | A |
| GET | `/ukt-determinations/export` | Ekspor | `ukt_determinations.export` | A |
| GET | `/ukt-appeals` | Keberatan | `ukt_appeals.read` | |
| POST | `/ukt-appeals/{uktAppeal}/review`, `/approve`, `/reject` | Tinjau/putuskan | `.update` / `.approve` / `.reject` | A |
| GET | `/ukt-validation/tariffs`, `/ukt-validation/recipients` | Validasi | `ukt_determinations.read` | |

### 4.7 Koreksi, refund, rekonsiliasi

| Method | URL | Tujuan | Permission | Idem/Aud |
|---|---|---|---|---|
| GET / POST | `/finance-adjustments` | Penyesuaian | `finance_adjustments.read` / `.create` | A |
| GET | `/finance-adjustments/{financeAdjustment}` | Detail + pratinjau efek | `finance_adjustments.read` | |
| POST | `/finance-adjustments/{financeAdjustment}/submit`, `/cancel` | Ajukan / tarik | `finance_adjustments.create` | A |
| POST | `/finance-adjustments/{financeAdjustment}/approve`, `/reject` | Putuskan (penerapan otomatis) | `.approve` / `.reject` | A |
| GET / POST | `/refunds` | Refund | `refunds.read` / `.create` | A |
| POST | `/refunds/{refund}/submit` | Ajukan (tahan dana) | `refunds.create` | I, A |
| POST | `/refunds/{refund}/approve`, `/reject` | Putuskan | `.approve` / `.reject` | A |
| POST | `/refunds/{refund}/process`, `/complete`, `/fail`, `/cancel` | Proses | `refunds.update` | A |
| GET / POST | `/reconciliation-batches` | Batch | `reconciliations.read` / `.create` | A |
| POST | `/reconciliation-batches/{reconciliationBatch}/import` | Impor | `reconciliations.import` | A |
| POST | `/reconciliation-batches/{reconciliationBatch}/match` | Cocokkan | `reconciliations.update` | A |
| GET | `/reconciliation-batches/{reconciliationBatch}/items` | Item | `reconciliations.read` | |
| POST | `/reconciliation-items/{reconciliationItem}/resolve` | Selesaikan | `reconciliations.update` | A |
| POST | `/reconciliation-batches/{reconciliationBatch}/close` | Tutup | `reconciliations.finalize` | A |
| GET / POST | `/accounting-exports` | Ekspor akuntansi | `finance_reports.read` / `.export` | A |

### 4.8 Piutang, cekal, laporan

| Method | URL | Tujuan | Permission | Idem/Aud |
|---|---|---|---|---|
| GET | `/finance-dashboard/summary` | Kartu dashboard | `finance_dashboard.read` | |
| GET | `/receivables/summary`, `/aging`, `/items`, `/students`, `/trend` | Monitoring piutang | `finance_reports.read` | |
| GET / POST | `/finance-hold-policies` | Kebijakan cekal | `finance_hold_policies.read` / `.create` | A |
| GET / PUT | `/finance-hold-policies/{financeHoldPolicy}` | Detail / ubah | `.read` / `.update` | A |
| POST | `/finance-hold-policies/{financeHoldPolicy}/simulate` | Simulasi | `finance_hold_policies.read` | |
| PATCH | `/finance-hold-policies/{financeHoldPolicy}/activate`, `/deactivate` | Status | `finance_hold_policies.update` | A |
| GET | `/finance-holds`, `/{financeHold}` | Cekal | `finance_holds.read` | |
| POST | `/finance-holds` | Cekal manual | `finance_holds.create` | A |
| POST | `/finance-holds/evaluate` | Evaluasi | `finance_holds.update` | A |
| POST | `/finance-holds/{financeHold}/release-requests` | Ajukan pembukaan (manual) | `finance_holds.update` | A |
| POST | `/finance-holds/{financeHold}/release` | Setujui pembukaan yang sudah diajukan (mode langsung; SoD terhadap pengaju) | `finance_holds.approve` | A |
| POST | `/finance-holds/{financeHold}/resync` | Sinkron ulang | `finance_holds.update` | A |
| GET | `/finance-holds/export` | Ekspor | `finance_holds.export` | A |
| POST | `/finance-holds/{financeHold}/exemptions` | Ajukan pengecualian | `finance_hold_exemptions.create` | A |
| POST | `/finance-hold-exemptions/{financeHoldExemption}/approve`, `/reject`, `/revoke` | Putuskan / cabut | `.approve` / `.reject` / `.approve` | A |
| GET | `/students/{student}/finance-clearance` | Status cekal per layanan | `finance_holds.read` | |
| GET | `/finance-reports` | Katalog laporan | `finance_reports.read` | |
| GET | `/finance-reports/{reportCode}/data` | Data laporan (paginasi) | `finance_reports.read` | |
| POST | `/finance-reports/{reportCode}/exports` | Ekspor asinkron | `finance_reports.export` | I, A (`exported`) |
| GET | `/finance-report-exports/{financeReportExport}` | Status & unduhan | `finance_reports.export` | A (`downloaded`) |

## 5. Katalog Endpoint Mahasiswa

Seluruhnya di bawah `/api/v1/student/finance`, hanya data milik mahasiswa yang login ([KEU-BR-312](25-business-rules-dan-validasi.md)).

| Method | URL | Tujuan | Permission | Idem |
|---|---|---|---|---|
| GET | `/summary` | Ringkasan: total terutang, jatuh tempo terdekat, deposit, status cekal | `finance_self_service.read` | |
| GET | `/invoices`, `/invoices/{invoice}`, `/invoices/{invoice}/pdf` | Tagihan (tanpa draf) | `.read` | |
| POST | `/invoices/{invoice}/disputes` | Sengketa | `.create` | I |
| POST | `/invoices/{invoice}/vouchers` | Terapkan voucher | `.update` | I |
| DELETE | `/invoices/{invoice}/vouchers/{voucherRedemption}` | Lepas voucher | `.update` | |
| POST | `/vouchers/validate` | Pratinjau voucher | `.read` | |
| POST | `/invoices/{invoice}/installment-requests` | Ajukan cicilan | `.create` | I |
| GET | `/installment-plans/{installmentPlan}` | Jadwal cicilan | `.read` | |
| GET | `/payment-channels` | Kanal aktif & sehat + biaya | `.read` | |
| POST | `/payment-transactions` | Buat transaksi online | `.create` | I |
| GET | `/payment-transactions`, `/payment-transactions/{paymentTransaction}` | Status transaksi | `.read` | |
| POST | `/payment-transactions/{paymentTransaction}/cancel` | Batalkan | `.update` | |
| GET | `/manual-payment-accounts` | Rekening transfer manual | `.read` | |
| POST | `/manual-payments` | Konfirmasi transfer + bukti | `.create` | I |
| PUT | `/manual-payments/{payment}/proof` | Ganti bukti | `.update` | |
| POST | `/manual-payments/{payment}/cancel` | Batalkan pengajuan | `.update` | |
| GET | `/payments`, `/receipts/{receipt}/pdf` | Riwayat & kuitansi | `.read` | |
| GET | `/deposit` | Saldo & mutasi deposit | `.read` | |
| POST | `/deposit/apply` | Pakai deposit (bila diizinkan) | `.update` | I |
| POST | `/deposit/topups` | Top-up (bila diizinkan) | `.create` | I |
| GET | `/scholarships` | Beasiswa/potongan saya | `.read` | |
| GET | `/ukt` | Golongan & riwayat UKT | `.read` | |
| POST | `/ukt-appeals` | Ajukan keberatan UKT | `.create` | I |
| GET | `/clearance` | Status cekal & cara menyelesaikan | `.read` | |

## 6. Endpoint Callback Provider, Publik, dan Platform

| Method | URL | Autentikasi | Keterangan |
|---|---|---|---|
| POST | `/webhooks/payments/{paymentGatewayAccount}` | Tanda tangan provider | §7.8; di luar `auth:sanctum`/`tenant.access`; tenant dari akun |
| GET | `/public/receipts/verify/{code}` | Publik, terbatas laju | Status kuitansi, data termasking ([09 §8](09-rancangan-pembayaran-tunai-dan-manual.md#8-kuitansi)) |
| PUT | `/platform/universities/{university}/entitlements/finance-payment-channels` | Super Admin | Kuota kanal online ([08 §4](08-rancangan-pembayaran-online-dan-gateway.md#4-kuota-kanal-per-paket)) |

## 7. Spesifikasi Rinci Endpoint Utama

### 7.1 Master tarif — `POST /tariff-schedules/{id}/rules` dan `GET /tariffs/resolve`

| Aspek | `POST /tariff-schedules/{tariffSchedule}/rules` |
|---|---|
| Tujuan | Menambah aturan tarif pada jadwal draf |
| Role | FA (`tariffs.update`) |
| Validasi | Jadwal `draft`; `lock_version` jadwal cocok; komponen aktif; kriteria konsisten ([KEU-BR-033](25-business-rules-dan-validasi.md)); `amount ≥ 0`; `criteria_hash` unik ([KEU-BR-028](25-business-rules-dan-validasi.md)) |
| Audit | `created` pada aturan |

```json
{
  "lock_version": 3,
  "fee_component_id": "01JB0FC00000000000000000SP",
  "study_program_id": "01JA0SP0000000000000000IF1",
  "admission_year_from": 2024,
  "admission_year_to": 2026,
  "study_system_code": "KARYAWAN",
  "amount": "8000000.00",
  "is_mandatory": true
}
```

Respons 201: aturan dengan `specificity_score: 304`, `criteria_hash`, dan `warnings` (mis. potensi bentrok dengan aturan lain di draf). Error: 409 `STALE_VERSION`, 409 `INVALID_STATUS_TRANSITION` (jadwal bukan draf), 422 `VALIDATION_FAILED`.

| Aspek | `GET /tariffs/resolve` |
|---|---|
| Parameter | `student_id` (wajib), `academic_term_id` (wajib), `fee_component_ids[]`, `schedule_id` (pratinjau draf) |
| Respons | Baris per komponen: aturan terpilih, skor, kandidat, satuan, nominal, galat per komponen |

```json
{
  "success": true,
  "message": "Berhasil.",
  "data": {
    "student": { "id": "01J…", "nim": "2026110045", "name": "Budi" },
    "academic_term": { "id": "01J…", "label": "2026/2027 Ganjil" },
    "lines": [
      {
        "fee_component": { "code": "SPP", "name": "SPP Semester" },
        "status": "resolved",
        "rule_id": "01J…R3",
        "schedule": { "code": "TARIF-2026", "version": 1 },
        "score": 304,
        "candidates": [ { "rule_id": "01J…R1", "score": 144 }, { "rule_id": "01J…R2", "score": 272 } ],
        "quantity": "1.00",
        "unit_amount": "8000000.00",
        "amount": "8000000.00"
      },
      {
        "fee_component": { "code": "SKS", "name": "Biaya per SKS" },
        "status": "resolved",
        "rule_id": "01J…R5",
        "score": 320,
        "units": { "raw": "18.00", "free_units": "0.00", "min_units": "12.00", "billable": "18.00" },
        "quantity": "18.00",
        "unit_amount": "175000.00",
        "amount": "3150000.00"
      }
    ],
    "errors": [],
    "total_amount": "16850000.00"
  },
  "meta": {}
}
```

### 7.2 Pembuatan tagihan — `POST /invoices`

| Aspek | Isi |
|---|---|
| Tujuan | Membuat tagihan individual (draf atau langsung terbit) |
| Role | FA (`invoices.create`) |
| Idempotensi | Wajib bila `mode = issue` |
| Validasi | Mahasiswa milik tenant dan berstatus layak ([KEU-BR-044](25-business-rules-dan-validasi.md)); term ada; `invoice_type` valid; `due_date` ≥ hari ini, ≤ 365 hari; komponen bertarif dihitung resolver (nominal manual hanya untuk komponen `manual`); `billing_key` belum aktif ([KEU-BR-041](25-business-rules-dan-validasi.md)); ambang persetujuan manual |
| Audit | `created`; histori `created`/`issued` |

```json
{
  "student_id": "01JA0ST000000000000000RINA",
  "academic_term_id": "01JA0AT00000000000002026G1",
  "invoice_type": "semester",
  "mode": "draft",
  "due_date": "2026-09-15",
  "components": { "from_tariff": true, "fee_component_ids": null },
  "manual_lines": [],
  "description": "Tagihan Semester Ganjil 2026/2027"
}
```

Respons 201:

```json
{
  "success": true,
  "message": "Draf tagihan dibuat.",
  "data": {
    "id": "01JB2K9Q5C7Z3W8XR4TNM6V0PA",
    "invoice_number": null,
    "status": "draft",
    "invoice_type": "semester",
    "period": "2026/2027 Ganjil",
    "gross_amount": "6450000.00",
    "discount_amount": "350000.00",
    "adjustment_amount": "0.00",
    "amount": "6100000.00",
    "paid_amount": "0.00",
    "outstanding_amount": "6100000.00",
    "due_date": "2026-09-15",
    "items": [
      { "line_no": 1, "item_type": "charge", "description": "UKT Golongan IV", "quantity": "1.00", "unit_amount": "5500000.00", "amount": "5500000.00" },
      { "line_no": 2, "item_type": "charge", "description": "Praktikum (2 MK × Rp350.000)", "quantity": "2.00", "unit_amount": "350000.00", "amount": "700000.00" },
      { "line_no": 3, "item_type": "charge", "description": "Administrasi", "quantity": "1.00", "unit_amount": "250000.00", "amount": "250000.00" },
      { "line_no": 4, "item_type": "scholarship", "applies_to_line_no": 2, "description": "Beasiswa Prestasi 50% praktikum", "amount": "-350000.00" }
    ],
    "lock_version": 0
  },
  "meta": {}
}
```

(Voucher `ALUMNI50` pada contoh [19 §5.1](19-rancangan-database-dan-relasi.md#51-mahasiswa--tagihan--detail-tagihan) diterapkan kemudian oleh mahasiswa sehingga nominal menjadi Rp6.050.000.)

Error: 422 `TARIFF_NOT_FOUND` (dengan daftar komponen), 409 `TARIFF_AMBIGUOUS`, 422 `UKT_GROUP_MISSING`, 409 `ALREADY_BILLED`, 422 `VALIDATION_FAILED`.

### 7.3 Daftar tagihan mahasiswa — `GET /student/finance/invoices`

| Aspek | Isi |
|---|---|
| Role | Mahasiswa (`finance_self_service.read`) |
| Filter | `filter[status]=unpaid,partial`, `filter[academic_term_id]`, `filter[invoice_type]`; `sort=due_date` (default), `-issued_at` |
| Paginasi | `per_page` ≤ 50 |
| Validasi | Hanya tagihan milik mahasiswa yang login; draf tidak pernah tampil |

```json
{
  "success": true,
  "message": "Berhasil.",
  "data": [
    {
      "id": "01JB2K9Q5C7Z3W8XR4TNM6V0PA",
      "invoice_number": "TGH-2026-0001234",
      "invoice_type": "semester",
      "period": "2026/2027 Ganjil",
      "amount": "6050000.00",
      "paid_amount": "4000000.00",
      "outstanding_amount": "2050000.00",
      "status": "partial",
      "status_label": "Dibayar Sebagian",
      "due_date": "2026-09-15",
      "is_overdue": true,
      "has_open_dispute": false,
      "has_active_installment_plan": false,
      "active_payment_transaction_id": null,
      "related_late_fee_outstanding": "0.00",
      "can_pay": true,
      "can_apply_voucher": false
    }
  ],
  "meta": { "current_page": 1, "per_page": 15, "total": 1, "last_page": 1 }
}
```

### 7.4 Detail tagihan — `GET /invoices/{invoice}`

| Aspek | Isi |
|---|---|
| Role | `invoices.read` + scope; mahasiswa memakai `/student/finance/invoices/{invoice}` (field staf disembunyikan) |
| Isi | Header (§7.2), `items` dengan `applies_to_line_no`, `allocations` (nomor pembayaran, nominal bertanda, tanggal, metode), `installment_plan` (termin), `late_fee_invoices`, `disputes`, `voucher_redemptions`, `snapshot` (staf saja), tautan riwayat |
| Error | 404 (tidak ada / tenant lain / di luar scope / milik mahasiswa lain) |

### 7.5 Pembuatan pembayaran — online dan manual

**`POST /student/finance/payment-transactions`** (mahasiswa, [IDEM]):

```json
{
  "payment_channel_id": "01JB0PC000000000000000VABNI",
  "items": [ { "invoice_id": "01JB2K9Q5C7Z3W8XR4TNM6V0PA", "amount": "2050000.00" } ]
}
```

Respons 201:

```json
{
  "success": true,
  "message": "Transaksi dibuat.",
  "data": {
    "id": "01JB3T…",
    "transaction_number": "TRX-2026-00000871",
    "status": "pending",
    "amount": "2050000.00",
    "customer_fee_amount": "4000.00",
    "total_amount": "2054000.00",
    "channel": { "code": "va_bni", "name": "Virtual Account BNI", "channel_type": "virtual_account" },
    "va_number": "8808123412345678",
    "qr_string": null,
    "redirect_url": null,
    "expires_at": "2026-09-14T10:30:00+07:00",
    "instructions": "1. Buka aplikasi/ATM BNI …"
  },
  "meta": {}
}
```

Respons 202 bila provider belum memastikan (`status = created`). Error: 409 `INVOICE_NOT_PAYABLE`, 409 `ACTIVE_TRANSACTION_EXISTS`, 409 `CHANNEL_UNAVAILABLE`, 422 (nominal), 409 `IDEMPOTENCY_KEY_REUSED`, 429.

**`POST /payments`** (staf, [IDEM], [AUD]) — tunai atau transfer manual:

```json
{
  "source": "cashier",
  "student_id": "01JA0ST000000000000000RINA",
  "amount": "2500000.00",
  "received_at": "2026-09-10T10:12:00+07:00",
  "payment_channel_id": "01JB0PC0000000000000CASHIER",
  "allocations": [ { "invoice_id": "01JB2K9Q5C7Z3W8XR4TNM6V0PA", "amount": "2050000.00" } ],
  "payer_name": "Rina Aprilia",
  "notes": null
}
```

Validasi: sesi kas `open` milik petugas untuk `cashier` ([KEU-BR-110](25-business-rules-dan-validasi.md)); untuk `manual_transfer`: `bank_account_id`, `external_reference` (opsional), berkas bukti (multipart); alokasi milik mahasiswa yang sama dan ≤ sisa. Respons 201: pembayaran (`status = verified` untuk tunai, `pending_verification` untuk transfer), alokasi, kuitansi (tunai).

### 7.6 Verifikasi pembayaran manual — `POST /payments/{payment}/verify`

| Aspek | Isi |
|---|---|
| Role | FO, FM (`payments.approve`) |
| Validasi | `status = pending_verification`; `lock_version`; SoD (`recorded_by ≠` pengguna, [KEU-BR-300](25-business-rules-dan-validasi.md)); `amount > 0`; bila `amount ≠ claimed_amount` → `reason` wajib; `reference_key` unik ([KEU-BR-114](25-business-rules-dan-validasi.md)); peringatan duplikat checksum memerlukan `override_reason` |
| Efek | `verified`, alokasi, kuitansi, histori, outbox `PaymentVerified`, notifikasi |
| Audit | `verified` (+ `forced_override` bila *override*) |

```json
{
  "lock_version": 0,
  "amount": "2500000.00",
  "external_reference": "TRF20260910ABC123",
  "allocations": [ { "invoice_id": "01JB2K9Q5C7Z3W8XR4TNM6V0PA", "amount": "2050000.00" } ],
  "reason": null,
  "override_reason": null
}
```

Error: 403 `SELF_APPROVAL_FORBIDDEN`, 409 `STALE_VERSION`, 409 `INVALID_STATUS_TRANSITION`, 409 `DUPLICATE_REFERENCE`, 422 `ALLOCATION_EXCEEDS_OUTSTANDING`/`ALLOCATION_EXCEEDS_AVAILABLE`, 409 `PERIOD_CLOSED` (tidak terjadi untuk verifikasi karena `posting_date` dialihkan ke periode terbuka, [07 §8](07-rancangan-pembayaran.md#8-tanggal-pembukuan)).

### 7.7 Callback provider — `POST /webhooks/payments/{paymentGatewayAccount}`

| Aspek | Isi |
|---|---|
| Autentikasi | Tanda tangan/token provider, diverifikasi adapter; stempel waktu ≤ 300 detik bila tersedia |
| Body | Format provider (disimpan mentah) |
| Respons | `200 {"received": true}` untuk event baru maupun duplikat; `401` untuk tanda tangan tidak valid; `404` untuk akun tidak dikenal/nonaktif; `413` untuk body terlalu besar |
| Idempotensi | Unique `(payment_gateway_account_id, dedupe_key)` |
| Pemrosesan | Asinkron ([08 §7.2](08-rancangan-pembayaran-online-dan-gateway.md#72-pemrosesan-di-job)) |
| Audit | `payment_webhook_events` (mentah) |

### 7.8 Penggunaan deposit — `POST /deposit-accounts/{depositAccount}/apply`

```json
{ "invoice_id": "01JB9X…GENAP", "amount": "300000.00" }
```

| Aspek | Isi |
|---|---|
| Role | FO, FA (`deposits.update`); mahasiswa lewat `POST /student/finance/deposit/apply` bila `finance.deposit.self_use_enabled` |
| Validasi | Akun `active`; tagihan milik mahasiswa sama, `unpaid/partial`, tanpa transaksi aktif; `amount ≤ tersedia` ([KEU-BR-006](25-business-rules-dan-validasi.md)) dan ≤ sisa |
| Respons 201 | `deposit_transaction` (`DPT-…`, `balance_after`), `allocation` (`ALC-…`), tagihan terbaru |
| Error | 422 `DEPOSIT_INSUFFICIENT`, 409 `DEPOSIT_FROZEN`, 422 `ALLOCATION_EXCEEDS_OUTSTANDING`, 409 `ACTIVE_TRANSACTION_EXISTS` |
| Idempotensi/Audit | [IDEM], `created` |

### 7.9 Penerapan voucher — `POST /student/finance/invoices/{invoice}/vouchers`

```json
{ "code": "alumni50" }
```

Respons 201: `redemption` (`status = applied`, `discount_amount: "50000.00"`), baris voucher, tagihan terbaru (`amount: "6050000.00"`). Error: 422 `VOUCHER_INVALID`, 422 `VOUCHER_EXPIRED`, 409 `VOUCHER_QUOTA_EXHAUSTED`, 409 `VOUCHER_ALREADY_USED`, 422 `VOUCHER_NOT_ELIGIBLE`, 409 `INVOICE_NOT_PAYABLE`, 409 `ACTIVE_TRANSACTION_EXISTS`, 429. [IDEM].

### 7.10 Penetapan UKT — `POST /ukt-determinations` lalu `/approve`

```json
{
  "student_id": "01JA0ST000000000000000RINA",
  "ukt_group_id": "01JB0UG0000000000000000UK3",
  "effective_from_term_id": "01JA0AT00000000000002026G2",
  "basis": "appeal",
  "ukt_appeal_id": "01JB7…",
  "decree_number": "SK-UKT/045/2026",
  "decree_date": "2026-08-25",
  "reason": "Keberatan diterima: orang tua kehilangan pekerjaan",
  "submit": true
}
```

Validasi: golongan aktif; tidak tumpang tindih dengan penetapan aktif lain kecuali sebagai pengganti ([KEU-BR-221](25-business-rules-dan-validasi.md)); `decree_number` wajib untuk `basis ≠ migration`. Respons 201 `pending_approval`. `POST /ukt-determinations/{id}/approve` (FM, SoD) → `active`, penetapan lama disesuaikan. Error: 403 `SELF_APPROVAL_FORBIDDEN`, 409 `INVALID_STATUS_TRANSITION`, 422.

### 7.11 Pengajuan beasiswa — `POST /student-discounts`

```json
{
  "student_id": "01JA0ST000000000000000RINA",
  "discount_scheme_id": "01JB0DS00000000000PRESTASI",
  "valid_from_term_id": "01JA0AT00000000000002026G1",
  "valid_until_term_id": "01JA0AT00000000000002027G2",
  "value_override": null,
  "decree_number": "SK-BSW/012/2026",
  "scholarship_application_id": null
}
```

Validasi: skema `active`; rentang dalam rentang skema; kelayakan ([KEU-BR-146](25-business-rules-dan-validasi.md)); belum ada penetapan aktif untuk pasangan mahasiswa–skema ([KEU-BR-147](25-business-rules-dan-validasi.md)). Respons 201 `pending_approval`. Persetujuan memeriksa kuota ([KEU-BR-148](25-business-rules-dan-validasi.md)).

### 7.12 Monitoring piutang — `GET /receivables/summary`

| Parameter | Contoh |
|---|---|
| `as_of` | `2026-10-15` |
| `group_by` | `faculty,study_program` |
| `filter[academic_term_id]`, `filter[invoice_type]`, `filter[admission_year]`, `filter[status]` | |

```json
{
  "success": true,
  "message": "Berhasil.",
  "data": {
    "as_of": "2026-10-15",
    "totals": {
      "issued_effective": "49566000.00",
      "allocations": "29200000.00",
      "receivable": "20366000.00",
      "receivable_not_due": "5063334.00",
      "receivable_due": "15302666.00",
      "aging": { "not_due": "5063334.00", "d1_30": "13802666.00", "d31_60": "0.00", "d61_90": "1500000.00", "d90_plus": "0.00" },
      "students_unpaid": 4,
      "collection_rate": "58.91"
    },
    "groups": [
      { "faculty": { "code": "FT" }, "study_program": { "code": "IF" }, "receivable": "14050000.00", "drilldown": "/api/v1/receivables/items?as_of=2026-10-15&filter[study_program_id]=01J…" }
    ]
  },
  "meta": {}
}
```

(Angka sesuai contoh [16 §10](16-rancangan-monitoring-piutang.md#10-contoh-perhitungan); kelompok IF = 6.850.000 + 7.200.000.)

### 7.13 Cekal keuangan — `GET /students/{student}/finance-clearance` dan pengecualian

```json
{
  "success": true,
  "message": "Berhasil.",
  "data": {
    "student_id": "01J…",
    "display_status": "blocked",
    "services": [
      { "service": "krs", "allowed": false, "hold_number": "CKL-2026-0000412", "reason": "Tagihan semester 2026/2027 Ganjil belum lunas", "since": "2026-09-23T00:15:00+07:00", "exemption": null },
      { "service": "exam", "allowed": true, "hold_number": "CKL-2026-0000412", "exemption": { "valid_until": "2026-10-31T23:59:59+07:00" } }
    ],
    "sync_status": "synced",
    "basis_outstanding_amount": "6850000.00"
  },
  "meta": {}
}
```

`basis_outstanding_amount` hanya disertakan bila pengguna memiliki `invoices.read` ([KEU-BR-270](25-business-rules-dan-validasi.md)).

`POST /finance-holds/{financeHold}/exemptions` (FO/FA): `{ "services": ["exam"], "valid_from": "…", "valid_until": "…", "reason": "…", "evidence_file_id": null }`. Validasi: layanan subset cekal; durasi ≤ `max_exemption_days`. Respons 201 `pending_approval` (atau `active` bila tanpa persetujuan).

### 7.14 Rekonsiliasi — `POST /reconciliation-batches/{reconciliationBatch}/import`

Multipart `file` (CSV/XLSX) + `parser` (mis. `bni_statement_csv`, `generic_template`) atau `fetch = provider`. Validasi: batch `draft`; checksum belum dipakai ([KEU-BR-286](25-business-rules-dan-validasi.md)); kolom wajib. Respons 202 (diproses di job) dengan `status = imported` setelah selesai; ringkasan `total_rows`. Error: 409 `DUPLICATE_REFERENCE` (berkas sama), 422 (format).

### 7.15 Laporan — `POST /finance-reports/{reportCode}/exports`

```json
{ "format": "xlsx", "filters": { "as_of": "2026-10-15", "academic_term_id": "01J…", "faculty_id": "01J…" } }
```

Validasi: kode laporan dikenal ([22](22-rancangan-laporan-dan-dashboard.md)); format didukung laporan; pengguna memiliki `finance_reports.export` dan scope. Respons 202 `{ "export_id": "…", "status": "queued" }`; `GET /finance-report-exports/{id}` → `ready` + URL unduhan sementara berizin. Audit `exported` dengan filter. [IDEM].

## 8. Event dan Outbox

Semua event lintas modul ditulis ke `finance_outbox_events` dalam transaksi perubahan, lalu dikirim worker setelah commit ([KEU-BR-018](25-business-rules-dan-validasi.md), [KEU-KD-22](29-glossary-dan-keputusan-bisnis.md#2-keputusan-desain-keu-kd)). Konsumen wajib idempoten terhadap `idempotency_key`.

| Event | `idempotency_key` | Payload inti | Konsumen |
|---|---|---|---|
| `InvoiceIssued` | `invoice:{id}:issued` | `invoice_id`, `student_id`, `invoice_number`, `invoice_type`, `academic_term_id`, `amount`, `due_date` | Notifikasi, akuntansi |
| `InvoiceStatusChanged` | `invoice:{id}:v{lock_version}` | `invoice_id`, `student_id`, `from_status`, `to_status`, `period`, `academic_term_id`, `outstanding_amount` | Evaluasi cekal, dashboard, SIAKAD (kontrak `academic-learning/06`) |
| `InvoiceCancelled` | `invoice:{id}:cancelled` | `invoice_id`, `source_type`, `source_id`, alasan | Voucher, Semester Pendek, akuntansi |
| `PaymentVerified` | `payment:{id}:verified` | `payment_id`, `student_id`, `amount`, `source`, `channel`, `allocations[]` | Notifikasi, cekal, akuntansi |
| `PaymentReversed` | `payment:{id}:reversed` | `payment_id`, `adjustment_id`, alokasi yang dibalik | Notifikasi, cekal, akuntansi |
| `PaymentTransactionStatusChanged` | `trx:{id}:v{lock_version}` | `payment_transaction_id`, `status`, `student_id` | Portal, pemantauan |
| `DepositBalanceChanged` | `deposit_tx:{id}` | `deposit_account_id`, `entry_type`, `amount`, `balance_after` | Notifikasi |
| `FinanceHoldChanged` | `hold:{id}:v{lock_version}` | [17 §7.1](17-rancangan-cekal-keuangan.md#71-kontrak) | SIAKAD `FinanceHoldProjector` |
| `FinanceHoldExemptionChanged` | `exemption:{id}:v{n}` | Pengecualian per layanan | SIAKAD |
| `UktDeterminationActivated` | `ukt:{id}:active` | `student_id`, golongan, term | Penagihan (hitung ulang draf) |
| `RefundCompleted` | `refund:{id}:completed` | `refund_id`, `amount`, `source_type` | Akuntansi |
| `FinancePeriodClosed` | `period:{code}:closed:v{n}` | `period_code`, `closing_summary` | Akuntansi |

Event yang **dikonsumsi** KEU (belum ada di kode, [KEU-A-11](29-glossary-dan-keputusan-bisnis.md#4-asumsi-keu-a)): `KrsSubmissionApproved`, `KrsSubmissionLocked`, `KrsItemDropped`, `StudentStatusChanged`, `ScholarshipApplicationApproved`.

## 9. Integrasi Antarmodul

| Modul | Arah | Mekanisme | Data | Status di repo | Bila belum tersedia |
|---|---|---|---|---|---|
| **PMB** | PMB → KEU | `FinanceBillingService::postExternalInvoice()` / `cancelExternalInvoice()`; `VoucherService` (konteks `admission`); `UktDeterminationService::createFromAdmission()`; lapisan gateway bersama | Tagihan biaya awal, pembayaran yang sudah diterima, voucher pendaftar, golongan UKT awal | PMB hanya dokumen | Uji dengan *fake*; impor manual |
| **Akademik** | KEU membaca | Model `Student`, `StudyProgram`, `Faculty`, `AcademicTerm` (satu aplikasi) | Identitas, prodi, angkatan, status, term | Ada | — |
| **KRS** | Akademik → KEU | Baca `krs_items` (`enrolled`) + event KRS | SKS & MK untuk tagihan per SKS/susulan | `krs_items` ada; event belum | Billing run terjadwal/manual ([06 §5](06-rancangan-tagihan-mahasiswa.md#5-tagihan-per-sks)) |
| **Semester Pendek** | SP → KEU | `postExternalInvoice()` (`source_type = short_term_krs`); `InvoiceCancelled` → SP | Tagihan SP per SKS | Hanya dokumen | — |
| **Ujian & layanan akademik** | KEU → SIAKAD | Outbox `FinanceHoldChanged` → `academic_blocks`; atau `FinanceClearanceService::check()` (mode `direct`) | Status cekal per layanan | Belum ada | Mode `direct` ([17 §9](17-rancangan-cekal-keuangan.md#9-jalur-sementara-financeclearanceguard)) |
| **Scholarship** | Scholarship → KEU | Baca `scholarship_applications` `approved`; event bila tersedia | Penerima beasiswa | Ada (read-only) | Penetapan manual dari daftar aplikasi |
| **Notifikasi** | KEU → Notification | `NotificationDispatcher::dispatch($user, $eventKey, $channels, $placeholders)` setelah commit | [23](23-rancangan-notifikasi.md) | Ada (`database`, `mail`) | — |
| **ApprovalWorkflow** | Dua arah | `ApprovalRequestService::submit()`; listener `ApprovalRequestApproved/Rejected` | Persetujuan | Ada | Mode persetujuan langsung |
| **FileManagement** | KEU → File | `FileAttacher`, stream berizin | Bukti, dokumen UKT, ekspor | Ada | — |
| **Payment gateway** | Dua arah | `PaymentGatewayContract` | Transaksi, webhook, status, refund, settlement | Belum ada | Fase 2–3 tanpa gateway |
| **Akuntansi** | KEU → luar | Ekspor berkas per periode (`accounting_export_batches`) | Jurnal ringkas | Tidak ada sistem | Berkas CSV/XLSX ([KEU-Q-18](29-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-keu-q)) |
| **Dashboard/Report/Platform** | KEU → konsumen | Query disesuaikan ([19 §6.3](19-rancangan-database-dan-relasi.md#63-penyesuaian-konsumen-yang-ada)) | Indikator | Ada | — |

**Catatan inkonsistensi (INK-08).** Rancangan Semester Pendek (`semester-pendek/pembayaran-dan-notifikasi.md`) mengusulkan `POST /invoices/{invoice}/payments` dan event `InvoicePaid`. KEU menggantinya dengan `POST /payments` (alokasi eksplisit) dan event `PaymentVerified` + `InvoiceStatusChanged` (`to_status = paid`). Modul SP perlu menyesuaikan diri saat dibangun.

## 10. Kompatibilitas Endpoint Lama

| Endpoint | Perubahan | Kompatibilitas |
|---|---|---|
| `GET /invoices` | Filter & field baru; tagihan `draft` dan `cancelled` tetap dapat difilter, tetapi default menampilkan semua status untuk staf | Field lama (`period`, `amount`, `paid_amount`, `status`, `due_date`) tetap; nilai `status` baru (`draft`, `cancelled`) ditambahkan ke tipe frontend |
| `GET /invoices/{invoice}` | Relasi tambahan | Field lama tetap |
| `GET /payments` | Field baru (`status`, `source`, …); permission `payments.read` **atau** `invoices.read` | Field lama (`invoice_id` untuk data lama, `amount`, `paid_at`, `method`) tetap |
| Middleware | Ditambahkan `tenant.access` | Pengguna tanpa membership aktif ditolak (sesuai pola modul lain) |

`FinanceListEndpointsTest` yang ada harus tetap lulus ([KEU-TC-API-006](26-skenario-pengujian.md)).

## 11. Pembatasan Laju

| Endpoint | Batas [R] | Kunci |
|---|---|---|
| `POST /student/finance/payment-transactions` | 10/jam | Pengguna |
| `POST /student/finance/invoices/{invoice}/vouchers` (gagal) | 10/15 menit | Pengguna (`finance.voucher.max_attempts`) |
| `POST /student/finance/manual-payments` | 20/hari | Pengguna |
| `GET /student/finance/payment-transactions/{id}` (memicu cek provider) | 1 cek provider/60 detik per transaksi | Transaksi |
| `GET /public/receipts/verify/{code}` | 30/menit | IP |
| `POST /webhooks/payments/{account}` | 600/menit | Akun gateway (di atas itu 429, provider akan mengirim ulang) |
| Ekspor laporan | 5 ekspor berjalan bersamaan | Pengguna |
