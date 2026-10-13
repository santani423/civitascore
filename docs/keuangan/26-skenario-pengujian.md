# 26 — Skenario Pengujian

> **Status:** rancangan. Disusun 2026-10-13.
> Kembali ke [README](README.md) · Aturan: [25](25-business-rules-dan-validasi.md) · Acceptance criteria: [27](27-acceptance-criteria.md) · Pola uji repository: Pest 4 (`php artisan test`), SQLite in-memory untuk uji fungsional

Dokumen ini adalah katalog test case `KEU-TC-<AREA>-NNN`. Setiap test case memuat prasyarat, langkah, data uji, hasil yang diharapkan, prioritas, dan aturan bisnis yang diuji. Prioritas: **P1** wajib lulus sebelum fitur dinyatakan selesai ([28 §7](28-roadmap-implementasi.md#7-definition-of-done-umum)), **P2** wajib sebelum UAT, **P3** dapat menyusul.

## Daftar Isi

1. [Strategi Pengujian](#1-strategi-pengujian)
2. [Data Uji Baku](#2-data-uji-baku)
3. [Pemetaan Skenario Wajib](#3-pemetaan-skenario-wajib)
4. [Master (MST)](#4-master-mst)
5. [Tarif (TAR)](#5-tarif-tar)
6. [Tagihan (INV)](#6-tagihan-inv)
7. [Pembayaran Umum (PAY)](#7-pembayaran-umum-pay)
8. [Pembayaran Online (PG)](#8-pembayaran-online-pg)
9. [Tunai dan Manual (MAN)](#9-tunai-dan-manual-man)
10. [Cicilan (INS)](#10-cicilan-ins)
11. [Beasiswa dan Potongan (SCH)](#11-beasiswa-dan-potongan-sch)
12. [Voucher (VCH)](#12-voucher-vch)
13. [Denda (LTF)](#13-denda-ltf)
14. [Deposit (DEP)](#14-deposit-dep)
15. [UKT (UKT)](#15-ukt-ukt)
16. [Piutang (AR)](#16-piutang-ar)
17. [Cekal (HLD)](#17-cekal-hld)
18. [Rekonsiliasi, Refund, Koreksi, Periode (REC)](#18-rekonsiliasi-refund-koreksi-periode-rec)
19. [Hak Akses (RBAC)](#19-hak-akses-rbac)
20. [API](#20-api)
21. [Laporan (RPT)](#21-laporan-rpt)
22. [Notifikasi (NTF)](#22-notifikasi-ntf)
23. [Keamanan (SEC)](#23-keamanan-sec)
24. [UI](#24-ui)

---

## 1. Strategi Pengujian

| Jenis | Cakupan | Alat / lingkungan |
|---|---|---|
| Unit | Kalkulator (tarif, potongan, cicilan, denda, waterfall, umur piutang), pemetaan status, pembulatan | Pest, tanpa DB |
| Fungsional (feature) | Endpoint + service + DB per fitur, termasuk otorisasi | Pest + SQLite in-memory (pola `FinanceListEndpointsTest`) |
| Konkurensi | Alokasi, deposit, voucher, penomoran, webhook ganda, eksekusi run | **MySQL 8** (bukan SQLite), proses paralel (beberapa koneksi/worker) yang dimulai bersamaan dengan *barrier*; diulang ≥ 20 kali |
| Integrasi | Gateway (`FakeGatewayAdapter` + *replay* payload sandbox provider), outbox → SIAKAD projector, notifikasi, FileManagement | Pest + antrean `sync` dan `redis` |
| Keamanan | IDOR, isolasi tenant, SoD, webhook, berkas, masking, rate limit | Pest + uji manual terarah |
| Kinerja | Tagihan massal 10.000, ekspor 100.000 baris, 500 webhook/menit (*contoh*) | Lingkungan staging |
| UI/E2E | Alur portal bayar, verifikasi bukti, loket | Uji manual terstruktur / otomatisasi browser bila tersedia |
| Invarian | `finance:verify-integrity` dijalankan di akhir setiap suite fungsional | Pest *afterAll* |

Konvensi: nama test menyebut ID TC dan ID aturan, mis. `it('KEU-TC-PG-004 KEU-BR-004 webhook berulang tidak menggandakan pembayaran')`.

## 2. Data Uji Baku

| Kode | Isi |
|---|---|
| **T-UND** | Tenant *Universitas Nusantara Digital*, zona `Asia/Jakarta`, `finance.sod.enabled = true` |
| **T-ITM** | Tenant kedua (*Institut Teknologi Mandala*) untuk uji isolasi |
| **TERM-G1 / TERM-G2** | 2026/2027 Ganjil (mulai 1 Agt 2026, selesai 31 Jan 2027) / Genap |
| **TARIF-2026** | Aturan R1–R10 [05 §9.1](05-rancangan-tarif-uang-kuliah.md#91-contoh-a--model-spp--per-sks-pts) |
| **UKT-2026** | Matriks [05 §9.2](05-rancangan-tarif-uang-kuliah.md#92-contoh-b--model-ukt-ptn) |
| **MHS** | Rina (IF 2024, UKT-4), Andi (IF 2024, SNBT, REGULER, 21 SKS, 2 praktikum), Budi (IF 2026, MANDIRI, KARYAWAN, 18 SKS), Citra (TS 2025, 9 SKS), Dewi (TS 2023, `leave`), Fajar (IF 2024) |
| **PENGGUNA** | `fa1` (administrator keuangan), `fo1`, `fo2` (staf keuangan), `cs1` (loket), `fm1` (bendahara), `ia1` (integrasi), `au1` (auditor), `kp-if` (kaprodi IF, scope prodi), `dw1` (dosen wali Andi), akun mahasiswa per MHS |
| **KANAL** | `va_bni` (online, biaya Rp4.000 ditanggung mahasiswa), `qris` (online), `manual_transfer_bni`, `cashier` |
| **GW** | Akun gateway sandbox dengan `FakeGatewayAdapter` (dapat diatur: sukses, timeout, nominal berbeda, tanda tangan salah) |

## 3. Pemetaan Skenario Wajib

| Skenario wajib (permintaan bisnis) | Test case |
|---|---|
| Tagihan sesuai tarif | TAR-001, INV-001 |
| Tarif tidak ditemukan | TAR-003 |
| Tarif ganda yang saling bertentangan | TAR-004 |
| Tagihan massal | INV-005, INV-006 |
| Pembayaran penuh | PAY-001 |
| Pembayaran sebagian | PAY-002 |
| Pembayaran ganda | PG-008 |
| Webhook dikirim berulang | PG-004 |
| Webhook tidak valid | PG-005, PG-013 |
| Pembayaran terlambat | PG-007 (diterima setelah kedaluwarsa), LTF-001, LTF-002 (setelah jatuh tempo) |
| Nominal pembayaran tidak sesuai | PG-009, MAN-005 |
| Bukti transfer ditolak | MAN-004 |
| Cicilan tidak sesuai total | INS-002 |
| Beasiswa penuh dan parsial | SCH-001, SCH-002 |
| Voucher kedaluwarsa | VCH-002 |
| Voucher digunakan bersamaan | VCH-004 |
| Denda setelah jatuh tempo | LTF-001 |
| Deposit digunakan secara bersamaan | DEP-004 |
| Pengembalian dana | REC-004, DEP-006 |
| Reversal transaksi | REC-001, REC-002 |
| Rekonsiliasi dengan selisih | REC-007 |
| Perubahan UKT | UKT-003, UKT-005 |
| Cekal otomatis | HLD-001 |
| Pembukaan cekal setelah pembayaran terverifikasi | HLD-002 |
| Kegagalan sinkronisasi akademik | HLD-005 |
| Pengguna tanpa izin mencoba menyetujui pembayaran | RBAC-002, MAN-009 |
| Laporan piutang dibandingkan dengan transaksi sumber | AR-001 |
| Penutupan periode | REC-010, REC-011 |

## 4. Master (MST)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-MST-001 | Buat komponen valid (positif) | P2 | `fa1` | POST `/fee-components` | `code: "prak 2"`, kategori `practicum`, basis `per_course` | 201; kode tersimpan `PRAK2`; kategori `late_fee` dengan basis selain `manual` ditolak 422 | 020, 021 |
| KEU-TC-MST-002 | Kode komponen duplikat (negatif) | P2 | Komponen `SPP` ada | POST kode `spp` | — | 422 kode sudah dipakai | 020 |
| KEU-TC-MST-003 | Hapus komponen yang dirujuk (negatif) | P2 | `SPP` dirujuk R1 | DELETE `/fee-components/{SPP}` | — | 409; komponen tetap; aksi nonaktifkan berhasil dan tagihan lama tetap menampilkan `SPP` | 020 |
| KEU-TC-MST-004 | Rekening terenkripsi, unik, termasking (keamanan) | P1 | `ia1` | Buat rekening `0123 4567 89`; buat lagi `0123456789`; GET daftar; GET `/reveal` sebagai `fo1` lalu `ia1` | — | Kedua kali 422 (hash sama); kolom DB tidak berisi plaintext; daftar `•••• 6789`; `fo1` 403 pada reveal; `ia1` 200 + audit `viewed_sensitive` | 022, 306 |
| KEU-TC-MST-005 | Penomoran paralel (konkurensi) | P1 | MySQL | 50 proses menerbitkan tagihan bersamaan | 50 draf | 50 nomor unik `TGH-2026-*`, tanpa duplikat; tidak ada *deadlock* yang gagal permanen | 024, 010 |
| KEU-TC-MST-006 | Pembulatan *half up* (batas) | P1 | — | Hitung 2% × 8.300.025 dan 10% × 3.500.005 | `rounding.mode = half_up`, `scale = 0` | 166.001 (166.000,50 → 166.001) dan 350.001 (350.000,5 → 350.001); tidak ada float | 011 |
| KEU-TC-MST-007 | Ubah pengaturan diaudit & tidak surut (positif) | P2 | Tagihan Andi terbit | `fa1` ubah `finance.discount.percentage_base`; `fa1` coba ubah `finance.sod.enabled`; `fm1` ubah dengan alasan | — | Perubahan pertama berhasil + audit nilai lama/baru; tagihan Andi tidak berubah; `fa1` 403 untuk kunci sensitif; `fm1` berhasil, `forced_override` tercatat | 036, 034 |

## 5. Tarif (TAR)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-TAR-001 | Resolusi aturan paling spesifik (positif) | P1 | TARIF-2026 `published` | GET `/tariffs/resolve` untuk Andi dan Budi, TERM-G1 | MHS | Andi: SPP R2 (272), SKS R4 21×150.000, total 11.200.000; Budi: SPP R3 (304), SKS R5 18×175.000, DPP R8, total 16.850.000; kandidat & skor tercantum | 029 |
| KEU-TC-TAR-002 | Per SKS dengan min/max/free units (batas) | P1 | TARIF-2026 + aturan paket | Resolve Citra (9 SKS, min 12); mahasiswa paket 24 SKS & 18 SKS (`free_units` 20) | — | Citra 12×150.000 = 1.800.000; paket 24 SKS → 4×200.000; 18 SKS → tidak ada baris SKS | 032 |
| KEU-TC-TAR-003 | Tarif tidak ditemukan (negatif) | P1 | Mahasiswa S1 Manajemen tanpa aturan SPP | Resolve; POST `/invoices`; billing run | — | Resolve: galat `TARIFF_NOT_FOUND` untuk SPP; POST 422; item run `failed` `TARIFF_NOT_FOUND`; tidak ada tagihan sebagian | 031 |
| KEU-TC-TAR-004 | Tarif ganda bertentangan (negatif) | P1 | Draf berisi R2 dan R11 (angkatan 2025–2027) | Submit; buat R11 di jadwal lain lalu publish | — | Submit 409 `TARIFF_AMBIGUOUS` dengan pasangan (R2, R11); publish jadwal lain ditolak saat rentang beririsan | 030 |
| KEU-TC-TAR-005 | Penyusun tidak dapat menyetujui (keamanan) | P1 | `fa1` mengajukan jadwal; `fa1` juga diberi `tariffs.approve` | `fa1` approve; `fm1` approve; `fa1` publish | — | `fa1` 403 `SELF_APPROVAL_FORBIDDEN`; `fm1` → `approved`; publish berhasil → `published` | 026, 300 |
| KEU-TC-TAR-006 | Revisi tidak mengubah tagihan lama (positif) | P1 | Tagihan Andi terbit dari TARIF-2026 v1 | Revisi v2 (SPP IF 7.500.000) berlaku TERM-G2, publish; buka tagihan Andi | — | Tagihan Andi tetap 7.000.000 dengan `tariff_schedule_id` v1; resolve TERM-G2 memakai v2; edit aturan v1 → 409 | 002, 025, 034 |
| KEU-TC-TAR-007 | Rentang jadwal (negatif/positif) | P2 | v1 berlaku mulai G1 tanpa batas | Publish v2 mulai G2 | — | v1 `effective_until = G1` otomatis; dua jadwal `code` sama tidak beririsan | 027 |
| KEU-TC-TAR-008 | Aturan identik (negatif) | P2 | Draf | Simpan dua aturan kriteria identik | — | Kedua 422 (unique `criteria_hash`) | 028 |
| KEU-TC-TAR-009 | Kriteria tidak konsisten (negatif) | P3 | Draf | Aturan prodi IF + fakultas Ekonomi; UKT tanpa golongan; jalur saat kolom belum ada | — | Pemblokir validasi untuk dua pertama; aturan jalur ditandai `unreachable` | 033, 035 |

## 6. Tagihan (INV)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-INV-001 | Terbitkan tagihan individual (positif) | P1 | UKT-2026, Rina UKT-4, beasiswa 50% praktikum aktif | POST `/invoices` draf; POST `/issue` | Rina, TERM-G1, `due_date` 2026-09-15 | Baris & snapshot benar; bruto 6.450.000, potongan 350.000, neto 6.100.000; nomor `TGH-2026-…`; `unpaid`; `due_date < issued` ditolak 422 | 001, 042, 046 |
| KEU-TC-INV-002 | Tagihan semester ganda (negatif, konkurensi) | P1 | Tagihan semester Rina G1 terbit | POST lagi; dua permintaan paralel untuk mahasiswa baru | — | 409 `ALREADY_BILLED`; paralel: satu tagihan | 041 |
| KEU-TC-INV-003 | Draf tertutup (positif/negatif) | P2 | Draf Andi | Mahasiswa GET daftar & detail; buat transaksi; laporan piutang | — | Tidak tampil (404 detail); transaksi 409 `INVOICE_NOT_PAYABLE`; piutang tidak berubah | 040, 055 |
| KEU-TC-INV-004 | Tagihan per SKS dari KRS `enrolled` (positif) | P1 | Andi 21 SKS `enrolled` + 3 SKS `pending` | Resolve & terbitkan | — | SKS 21 (bukan 24); snapshot `courses` berisi MK `enrolled` | 045, 032 |
| KEU-TC-INV-005 | Tagihan massal (positif) | P1 | 6 MHS, satu tanpa tarif (Manajemen), Dewi `leave` | Buat run (semua prodi, mode draf) → pratinjau → eksekusi → terbitkan draf | — | Pratinjau: 4 dihitung, 1 `TARIFF_NOT_FOUND`, 1 `STUDENT_INACTIVE`; eksekusi tanpa pratinjau ditolak; hasil `created`/`failed`/`skipped` sesuai; total = Σ pratinjau | 052 |
| KEU-TC-INV-006 | Run ulang idempoten (konkurensi) | P1 | Run selesai | Jalankan ulang; dua eksekusi paralel; salin run ke G2 | — | Mahasiswa tertagih → `already_billed`; tidak ada duplikat; run paralel kedua 409; salinan run berstatus `draft` | 053, 059 |
| KEU-TC-INV-007 | Batal dengan alokasi (negatif) | P1 | Tagihan `partial` | Ajukan & setujui pembatalan | — | Penerapan `failed` / 409 `INVOICE_HAS_ALLOCATIONS`; tagihan tetap | 047 |
| KEU-TC-INV-008 | Batal berizin + efek berantai (positif) | P1 | Tagihan `unpaid` dengan voucher, cicilan `active`, denda anak `unpaid`, transaksi VA `pending` | Ajukan; `fm1` setujui; batalkan VA dulu; ajukan ulang & setujui | — | Ditolak selama VA aktif; setelah VA batal: `cancelled`, voucher `cancelled` (kuota kembali), cicilan `cancelled`, denda anak `cancelled`, outbox `InvoiceCancelled`, histori lengkap | 048, 049 |
| KEU-TC-INV-009 | Tagihan nol (batas) | P2 | Beasiswa penuh KIP | Terbitkan tagihan UKT penerima KIP (ADM tidak dapat dipotong) | — | Bila hanya UKT: `amount = 0` → `paid` saat terbit; bila ada ADM: `unpaid` sebesar ADM | 050 |
| KEU-TC-INV-010 | Tagihan susulan (positif) | P2 | Andi tertagih 21 SKS | Tambah 3 SKS `enrolled`; jalankan evaluasi delta dua kali | — | Satu tagihan `supplementary` 3×150.000 = 450.000 dengan `parent_invoice_id`; evaluasi kedua `already_billed` | 051 |
| KEU-TC-INV-011 | Koreksi di bawah dibayar (negatif) | P1 | Tagihan 7.000.000 dibayar 6.000.000 | Koreksi −2.000.000 | — | Penerapan ditolak (`failed`) kecuali disertai pembalikan alokasi 1.000.000; baris asli tidak berubah | 056, 043 |
| KEU-TC-INV-012 | Posting PMB ganda (integrasi) | P1 | — | `postExternalInvoice(source pmb_invoice X)` dua kali (juga paralel) | — | Satu tagihan `admission`; panggilan kedua mengembalikan tagihan sama | 057 |
| KEU-TC-INV-013 | Sengketa kedua (negatif) | P3 | Sengketa `open` | Ajukan sengketa lagi; terima tanpa koreksi/catatan | — | 409; penerimaan ditolak 422 | 054 |
| KEU-TC-INV-014 | Hapus mahasiswa bertagihan (negatif) | P2 | Rina bertagihan | Hapus mahasiswa via Akademik | — | 409; tagihan & pembayaran tetap | 009 |
| KEU-TC-INV-015 | Perpanjangan jatuh tempo (positif) | P3 | Tagihan jatuh tempo 15 Sep | Ajukan `DUE_DATE_EXTENSION` 30 Sep; setujui | — | `due_date` 30 Sep, `original_due_date` 15 Sep, histori tercatat | 058 |
| KEU-TC-INV-016 | Status tidak layak (negatif) | P2 | Dewi `leave` | POST tagihan `semester` untuk Dewi; tagihan `additional` CUTI | — | `semester` 422; `additional` berhasil | 044 |

## 7. Pembayaran Umum (PAY)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-PAY-001 | Pembayaran penuh (positif) | P1 | Tagihan Rina 6.050.000 `unpaid`; sesi kas `cs1` | `cs1` POST `/payments` tunai 6.050.000 | — | `verified`; alokasi 6.050.000; tagihan `paid`, `paid_off_at`; satu kuitansi `KWT-…`; `amount ≤ 0` ditolak 422 | 042, 078, 070 |
| KEU-TC-PAY-002 | Pembayaran sebagian (positif) | P1 | Sama | Bayar 4.000.000 | `partial_allowed = true` | `partial`, sisa 2.050.000; dengan `partial_allowed = false` loket ditolak 422 | 081 |
| KEU-TC-PAY-003 | Kelebihan bayar (positif) | P1 | Sisa 2.050.000 | Bayar 2.500.000 | — | Alokasi 2.050.000; `unapplied_amount` 450.000; invarian kas terpenuhi | 076 |
| KEU-TC-PAY-004 | Alokasi melebihi sisa (negatif) | P1 | Pembayaran dengan dana belum diterapkan 450.000; tagihan sisa 300.000 | POST alokasi 400.000 | — | 422 `ALLOCATION_EXCEEDS_OUTSTANDING`; alokasi 500.000 dari dana 450.000 → 422 `ALLOCATION_EXCEEDS_AVAILABLE` | 005, 072 |
| KEU-TC-PAY-005 | Alokasi ke mahasiswa lain (keamanan) | P1 | Pembayaran Rina | Alokasi ke tagihan Andi | — | 422; tidak ada baris alokasi | 073 |
| KEU-TC-PAY-006 | Alokasi FIFO (positif) | P2 | Rina: tagihan A jatuh tempo 15 Agt sisa 1.000.000, denda jatuh tempo 15 Agt 50.000, tagihan B 15 Sep sisa 2.000.000 | Bayar 1.500.000 tanpa sasaran | `invoice_type_priority` default | A 1.000.000, B 500.000, denda 0 (denda terakhir pada jatuh tempo sama) | 074 |
| KEU-TC-PAY-007 | Verifikasi paralel atas tagihan sama (konkurensi) | P1 | MySQL; tagihan sisa 1.000.000; dua bukti 800.000 `pending` | `fo1` dan `fo2` memverifikasi bersamaan dengan alokasi 800.000 | — | Satu alokasi 800.000, yang lain dibatasi 200.000 (sisa 600.000 menjadi dana belum diterapkan) atau ditolak sesuai input; `paid_amount ≤ amount` | 010, 005 |
| KEU-TC-PAY-008 | Biaya admin bukan pelunasan (positif) | P2 | VA `va_bni` biaya mahasiswa 4.000 | Bayar transaksi total 2.054.000 | — | `payments.amount` 2.050.000, `customer_fee_amount` 4.000; alokasi 2.050.000; laporan pembayaran memisahkan biaya | 077, 104 |
| KEU-TC-PAY-009 | Pembayaran pending (negatif/positif) | P1 | Transfer `pending_verification` | POST alokasi; mahasiswa membatalkan pengajuan; perekam membatalkan setelah diverifikasi | — | Alokasi 409; pembatalan sebelum diperiksa → `cancelled`; setelah `verified` → 409 | 071, 083 |
| KEU-TC-PAY-010 | Realokasi berizin (positif) | P2 | Alokasi 500.000 ke tagihan A (keliru) | Ajukan `reallocation` ke B; `fo1` setujui; `fm1` setujui | — | `fo1` 403; setelah `fm1`: alokasi −500.000 di A (reversal_of_id) + 500.000 di B dalam satu transaksi | 080 |

## 8. Pembayaran Online (PG)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-PG-001 | Buat VA idempoten (positif) | P1 | Kanal `va_bni` aktif | Mahasiswa POST transaksi dua kali dengan `Idempotency-Key` sama; ketiga dengan kunci sama payload beda | Sisa 2.050.000 | Respons sama (header `Idempotent-Replayed`), satu transaksi `pending`, `total_amount` 2.054.000; ketiga 409 `IDEMPOTENCY_KEY_REUSED` | 017, 094 |
| KEU-TC-PG-002 | Transaksi kedua untuk tagihan sama (negatif) | P1 | Transaksi `pending` | Buat transaksi QRIS untuk tagihan sama | — | 409 `ACTIVE_TRANSACTION_EXISTS` | 093 |
| KEU-TC-PG-003 | Redirect tanpa webhook (keamanan) | P1 | Transaksi `pending` | Panggil URL kembali dari provider dengan parameter "success" | — | Status tetap `pending`; tagihan tidak berubah; UI "Menunggu konfirmasi" | 091, 003 |
| KEU-TC-PG-004 | Webhook berulang (idempoten) | P1 | Transaksi `pending` | Kirim webhook `paid` valid 3× (juga 2× paralel) | Payload sama | Satu `payments`, satu alokasi; event ke-2/3 `duplicate`, respons 200 | 004, 092 |
| KEU-TC-PG-005 | Webhook tidak valid (keamanan) | P1 | — | Kirim webhook tanda tangan salah | — | 401; tersimpan `invalid_signature`; tidak ada perubahan transaksi | 302 |
| KEU-TC-PG-006 | Callback tidak berurutan (integrasi) | P1 | Transaksi `paid` | Kirim `pending` lalu `expired` | — | Diabaikan (`ignored`); status tetap `paid` | 096 |
| KEU-TC-PG-007 | Bayar setelah kedaluwarsa (positif) | P1 | Transaksi `expired`; tagihan sudah lunas via loket | Webhook `paid` valid + inquire `paid` | — | Transaksi `paid`; pembayaran `verified` seluruhnya dana belum diterapkan; staf diberi tahu | 097 |
| KEU-TC-PG-008 | Pembayaran ganda (positif) | P1 | VA A (lokal `expired`) dan VA B dibuat untuk tagihan 2.050.000 | Bayar B lalu webhook A `paid` | — | Dua pembayaran; B melunasi; A seluruhnya dana belum diterapkan 2.050.000; notifikasi | 102 |
| KEU-TC-PG-009 | Nominal tidak sesuai (negatif) | P1 | Transaksi total 2.054.000 | Webhook `paid` dengan nominal 2.000.000 | — | Transaksi `paid` (`amount_mismatch`); pembayaran `pending_verification` amount 1.996.000; webhook `mismatch`; tagihan tidak berubah | 095 |
| KEU-TC-PG-010 | Kuota kanal (negatif, konkurensi) | P2 | Entitlement 3; 3 kanal online aktif | Aktifkan kanal ke-4; dua aktivasi paralel saat 2 aktif | — | 409 `CHANNEL_LIMIT_EXCEEDED`; paralel: hanya satu berhasil; kanal `cashier` tetap dapat diaktifkan | 090 |
| KEU-TC-PG-011 | Kanal terganggu (negatif) | P2 | `FakeGatewayAdapter` gagal 5× | Buat transaksi berulang | — | `health_status = degraded` lalu `down`; kanal hilang dari daftar; POST 409 `CHANNEL_UNAVAILABLE`; pemulihan setelah 3 uji sukses | 103 |
| KEU-TC-PG-012 | Kedaluwarsa dicek ke provider (integrasi) | P1 | Transaksi `pending` lewat `expires_at`; provider menyatakan `paid` | Jalankan job kedaluwarsa | — | Transaksi `paid` + pembayaran (bukan `expired`) | 098 |
| KEU-TC-PG-013 | Replay webhook (keamanan) | P1 | Webhook lama tervalidasi | Kirim ulang dengan stempel waktu 2 jam lalu | — | 401 (bila stempel didukung) atau `duplicate`; tanpa efek | 303 |
| KEU-TC-PG-014 | Akun gateway tenant lain (keamanan) | P1 | T-UND & T-ITM | Kirim webhook milik transaksi T-UND ke URL akun T-ITM | — | `invalid_signature` atau `ignored`; transaksi T-UND tidak berubah | 101 |
| KEU-TC-PG-015 | Polling (integrasi) | P2 | Transaksi `pending` 20 menit tanpa webhook; provider `paid` | Jalankan job polling | — | Transaksi `paid` dan pembayaran dibuat lewat jalur sama | 105 |
| KEU-TC-PG-016 | Kredensial tidak bocor (keamanan) | P1 | Akun dengan kredensial | GET akun; periksa log & audit setelah ubah | — | Respons hanya `credentials_configured` + 4 karakter; tidak ada nilai rahasia di log/`audit_logs` | 100, 305 |
| KEU-TC-PG-017 | Batal lalu tetap dibayar (positif) | P2 | Provider tidak mendukung pembatalan | Mahasiswa batalkan transaksi; kemudian webhook `paid` | — | Status lokal `cancelled` (`cancelled_local_only`), lalu `paid` diterima dan dicatat | 099, 097 |

## 9. Tunai dan Manual (MAN)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-MAN-001 | Loket + kuitansi (positif) | P1 | `cs1` membuka sesi (kas awal 500.000) | Bayar tunai 2.500.000 untuk tagihan sisa 2.050.000; cetak; cetak ulang; verifikasi publik kode | — | `verified`; kuitansi dengan alokasi & dana belum diterapkan 450.000; cetak ulang "SALINAN"; halaman publik "Sah", nama termasking | 110, 111 |
| KEU-TC-MAN-002 | Tanpa sesi terbuka (negatif) | P2 | Tidak ada sesi | Catat tunai | — | 409 | 110 |
| KEU-TC-MAN-003 | Unggah bukti → verifikasi (positif) | P1 | Kanal `manual_transfer_bni` | Mahasiswa unggah bukti 2.050.000 (rekening aktif); `fo1` verifikasi | — | `pending_verification` lalu `verified`; alokasi; notifikasi; rekening nonaktif ditolak 422 | 112, 120 |
| KEU-TC-MAN-004 | Bukti ditolak (negatif) | P1 | Bukti `submitted` | `fo1` tolak tanpa kode; dengan `NOT_FOUND_IN_STATEMENT` | — | Tanpa kode 422; dengan kode → `rejected`, bukti `rejected`, mahasiswa diberi tahu; tidak ada alokasi | 116 |
| KEU-TC-MAN-005 | Nominal mutasi berbeda (batas) | P1 | Klaim 2.050.000; mutasi 2.000.000 | Verifikasi 2.000.000 tanpa alasan; dengan alasan | — | Tanpa alasan 422; dengan alasan: `amount` 2.000.000, klaim tetap tercatat, tagihan `partial` | 115 |
| KEU-TC-MAN-006 | Referensi duplikat (negatif) | P1 | Pembayaran `verified` ref `TRF123` | Verifikasi pengajuan lain dengan ref `trf 123` | — | 409 `DUPLICATE_REFERENCE` | 114 |
| KEU-TC-MAN-007 | Checksum duplikat (negatif) | P2 | Bukti A `approved` | Mahasiswa lain unggah berkas identik; verifikasi tanpa/ dengan alasan *override* | — | Peringatan `duplicate_of_proof_id`; tanpa alasan 422; dengan alasan berhasil + `forced_override` | 114 |
| KEU-TC-MAN-008 | Berkas tidak valid (batas/keamanan) | P2 | — | Unggah 6 MB; `.exe` berganti nama `.pdf`; PDF 4,9 MB | — | 422, 422 (MIME isi), 201 | 113, 304 |
| KEU-TC-MAN-009 | Perekam = verifikator (keamanan) | P1 | `fo1` mencatat transfer atas nama mahasiswa | `fo1` verifikasi; `fo2` verifikasi | — | `fo1` 403 `SELF_APPROVAL_FORBIDDEN`; `fo2` berhasil | 300 |
| KEU-TC-MAN-010 | Tutup sesi dengan selisih (positif) | P2 | Sesi: kas awal 500.000, tunai 2.500.000 | `cs1` tutup dengan hitung 2.950.000 tanpa catatan; dengan catatan; `cs1` verifikasi; `fo1` verifikasi | — | Tanpa catatan 422; selisih −50.000 tersimpan, notifikasi bendahara; `cs1` 403; `fo1` → `verified` | 119 |
| KEU-TC-MAN-011 | Tanggal transfer (batas) | P3 | — | Unggah tanggal besok; 61 hari lalu; 60 hari lalu | `max_backdate_days = 60` | 422, 422, 201 | 118 |
| KEU-TC-MAN-012 | Dua verifikator bersamaan (konkurensi) | P1 | Pengajuan `pending` | `fo1` & `fo2` verifikasi dengan `lock_version` sama | — | Satu berhasil; lainnya 409 `STALE_VERSION`/`INVALID_STATUS_TRANSITION`; satu alokasi | 013 |
| KEU-TC-MAN-013 | Kode unik transfer (positif) | P3 | `unique_code_enabled = true` | Buat instruksi; transfer nominal + kode; unggah; verifikasi | Sisa 2.050.000, kode 123 | Instruksi 2.050.123; `customer_fee_amount` 123; alokasi 2.050.000; instruksi tanpa bukti > 3 hari → `cancelled` | 117 |

## 10. Cicilan (INS)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-INS-001 | Rencana 4 termin DP 30% (positif) | P1 | Tagihan Andi 11.200.000; `installment.enabled` | `fo1` susun & ajukan; `fm1` setujui | [10 §3.3](10-rancangan-cicilan-tagihan.md#33-contoh) | Termin 3.255.000 / 2.531.666 / 2.531.666 / 2.531.668; excluded 350.000; `active`; `due_date` tagihan 30 Nov; `original_due_date` disimpan; `fo1` tidak dapat menyetujui | 126, 127, 129, 135 |
| KEU-TC-INS-002 | Total tidak sesuai (negatif) | P1 | — | Custom termin Σ 10.800.000 | — | 422 `INSTALLMENT_TOTAL_MISMATCH` | 126 |
| KEU-TC-INS-003 | Batas termin & DP (batas) | P2 | `max_terms = 4`, DP min 25% | 5 termin; DP 20%; jatuh tempo menurun; termin terakhir setelah akhir term | — | Semua 422 dengan pesan spesifik | 127 |
| KEU-TC-INS-004 | Pembulatan (batas) | P2 | — | `equal`, sisa 7.595.000 / 3 | — | 2.531.666, 2.531.666, 2.531.668 | 128 |
| KEU-TC-INS-005 | Urutan alokasi (positif) | P1 | Rencana INS-001 aktif | Bayar 4.000.000 | — | 350.000 di luar jadwal (`installment_term_id` null), termin 1 `paid`, termin 2 `partial` 395.000 | 075 |
| KEU-TC-INS-006 | Pelunasan dipercepat (positif) | P2 | Setelah INS-005 | Bayar 7.200.000 | — | Termin 2–4 `paid`; tagihan `paid`; rencana `completed` | 131, 136 |
| KEU-TC-INS-007 | Penjadwalan ulang (positif) | P2 | Setelah INS-005 | Reschedule sisa ke 3 termin baru; setujui | — | v1 `superseded`, termin 1 tetap `paid`, termin 2 lama `cancelled` dengan `paid_amount` 395.000 tersimpan; v2 Σ = 7.200.000 | 132 |
| KEU-TC-INS-008 | Tidak dihitung ganda (positif) | P1 | Rencana aktif | Laporan piutang & RPT-09 | — | Piutang = sisa tagihan (7.200.000), bukan tagihan + termin | 130 |
| KEU-TC-INS-009 | Penyesuaian otomatis (positif) | P2 | Rencana aktif setelah INS-005 | Terapkan potongan susulan −1.000.000 pada SPP | — | Versi baru otomatis: termin 4 menjadi 1.531.668; histori `auto_rebalanced`; INV-10/11 lulus | 134 |
| KEU-TC-INS-010 | Pembatalan rencana (positif) | P2 | Rencana aktif | Ajukan batal dengan tanggal baru; setujui | — | Termin belum lunas `cancelled`; `due_date` tagihan = tanggal baru; `has_active_installment_plan = false` | 133 |
| KEU-TC-INS-011 | Syarat cicilan (negatif) | P2 | Tagihan dengan transaksi VA `pending`; tagihan `late_fee` | Mahasiswa ajukan cicilan | — | 409 `ACTIVE_TRANSACTION_EXISTS`; tagihan denda 422 | 125 |

## 11. Beasiswa dan Potongan (SCH)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-SCH-001 | Beasiswa penuh (positif) | P1 | Skema KIP `full`, penerima aktif | Terbitkan tagihan UKT + ADM (ADM tidak dapat dipotong) | — | Baris beasiswa −nominal UKT; ADM tetap; tidak ada komponen negatif | 145, 142 |
| KEU-TC-SCH-002 | Parsial + potongan dari sisa (positif) | P1 | Beasiswa 50% (tahap scholarship), saudara 10% (tahap discount) | Hitung dengan basis `remaining_component` lalu `gross_component` | SPP 7.000.000 | 3.150.000 dan 2.800.000 sesuai [11 §5.5](11-rancangan-beasiswa-dan-potongan.md#55-contoh-b--pengaruh-basis-persentase) | 140, 141 |
| KEU-TC-SCH-003 | Melebihi batas (batas) | P1 | Skema fixed 8.000.000 untuk SPP 7.000.000; `max_percentage` praktikum 50% | Hitung | — | Potongan SPP dibatasi 7.000.000; praktikum ≤ 50% | 142, 143 |
| KEU-TC-SCH-004 | Stacking group (negatif) | P2 | Dua skema grup `PRESTASI`, prioritas 10 & 20 | Hitung | — | Hanya prioritas 10 diterapkan; yang lain tercatat dilewati | 144 |
| KEU-TC-SCH-005 | Tidak layak / ganda (negatif) | P2 | Skema `min_gpa 3,25`; mahasiswa IPK 3,0; penetapan aktif lain | Ajukan penerima; ajukan skema sama dua kali | — | 422 kelayakan; 409 penetapan aktif ganda | 146, 147 |
| KEU-TC-SCH-006 | Kuota penerima (batas) | P3 | Kuota 1; dua pengajuan | Setujui keduanya | — | Kedua ditolak kuota | 148 |
| KEU-TC-SCH-007 | Pencabutan tidak surut (positif) | P1 | Tagihan G1 terbit dengan beasiswa | Cabut berlaku G2; ubah nilai skema | — | Tagihan G1 tetap; tagihan G2 tanpa beasiswa; snapshot G1 menyimpan parameter lama | 150, 154 |
| KEU-TC-SCH-008 | Penerapan susulan (positif) | P2 | Tagihan SPP 7.000.000 lunas | Beasiswa 50% disetujui; terapkan ke tagihan terbit | — | Alokasi dibalik 3.500.000; baris −3.500.000; tagihan tetap `paid`; dana belum diterapkan 3.500.000 | 151 |
| KEU-TC-SCH-009 | Pengaju = penyetuju (keamanan) | P1 | `fo1` mengajukan penerima | `fo1` setujui (diberi izin approve) | — | 403 `SELF_APPROVAL_FORBIDDEN` | 149, 300 |
| KEU-TC-SCH-010 | Potongan manual tanpa persetujuan (negatif) | P2 | — | Ajukan `manual_discount`; cek tagihan sebelum disetujui | — | Tagihan tidak berubah sampai `applied` | 153 |
| KEU-TC-SCH-011 | Evaluasi berkala (positif) | P3 | Penerima dengan IPK turun | Jalankan evaluasi | — | `evaluation_note` terisi; status tetap `active`; masuk antrean tinjauan | 152 |

## 12. Voucher (VCH)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-VCH-001 | Voucher valid (positif) | P1 | `ALUMNI50` aktif | Rina terapkan `alumni50` | Tagihan 6.100.000 | Baris −50.000 ke ADM; `used_count` +1; tagihan 6.050.000; kode tersimpan normal | 160, 165, 166 |
| KEU-TC-VCH-002 | Kedaluwarsa (negatif) | P1 | `valid_until` kemarin | Terapkan | — | 422 `VOUCHER_EXPIRED`; voucher yang sudah diterapkan sebelumnya tetap | 161 |
| KEU-TC-VCH-003 | Dua kali (negatif) | P1 | VCH-001 | Terapkan lagi; terapkan ke tagihan lain melebihi kuota per mahasiswa | — | 409 `VOUCHER_ALREADY_USED` keduanya | 163, 164 |
| KEU-TC-VCH-004 | Bersamaan pada sisa kuota 1 (konkurensi) | P1 | MySQL; kuota tersisa 1 | 20 mahasiswa menerapkan bersamaan | — | Tepat 1 berhasil; 19 `VOUCHER_QUOTA_EXHAUSTED`; `used_count` = kuota | 162 |
| KEU-TC-VCH-005 | Tidak layak (negatif) | P2 | Voucher khusus prodi TS; voucher non-*stacking* sudah ada | Andi (IF) terapkan; terapkan voucher kedua | — | 422 `VOUCHER_NOT_ELIGIBLE` keduanya | 165, 167 |
| KEU-TC-VCH-006 | Batal tagihan / lepas (positif) | P2 | Voucher `applied` | Mahasiswa lepas (tanpa pembayaran); tagihan lain dibatalkan | — | Lepas: baris `adjustment` +50.000, `released`, kuota kembali; batal: `cancelled`, kuota sesuai pengaturan | 170, 171 |
| KEU-TC-VCH-007 | Reservasi kedaluwarsa (positif) | P3 | Reservasi PMB 30 menit | Lewati waktu; jalankan job | — | `released` (`reservation_expired`), `reserved_count` −1 | 169 |
| KEU-TC-VCH-008 | Percobaan berulang (keamanan) | P2 | — | 11 kode salah dalam 15 menit | — | Percobaan ke-11 → 429; pesan seragam untuk kode tidak ada/nonaktif | 173 |
| KEU-TC-VCH-009 | Batas maksimum persen (batas) | P2 | `PMB2026` 10% maks 500.000 | Basis 8.000.000 | — | Potongan 500.000 (bukan 800.000) | 166 |
| KEU-TC-VCH-010 | Tagihan berbayar/transaksi aktif (negatif) | P2 | Tagihan `partial`; tagihan dengan VA `pending` | Mahasiswa terapkan; staf terapkan pada `partial` | — | Mahasiswa 409 keduanya; staf berhasil bila nominal ≥ dibayar | 168 |
| KEU-TC-VCH-011 | Persetujuan voucher (keamanan) | P2 | `fa1` membuat voucher | Terapkan sebelum disetujui; `fa1` setujui | — | `VOUCHER_INVALID`; `fa1` 403 `SELF_APPROVAL_FORBIDDEN` | 172, 300 |

## 13. Denda (LTF)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-LTF-001 | Denda setelah jatuh tempo + tenggang (positif) | P1 | Aturan `DENDA-SMT` (2%/bulan, tenggang 7, maks 10%); tagihan Citra jatuh tempo 15 Sep | Jalankan job 22 Sep dan 23 Sep | [13 §4.4](13-rancangan-denda.md#44-contoh) | 22 Sep: tidak ada; 23 Sep: 166.000 pada tagihan `late_fee` baru (`parent_invoice_id`); tagihan denda tidak pernah menjadi dasar denda | 181, 182, 187, 190 |
| KEU-TC-LTF-002 | Batas 23:59 zona tenant (batas) | P1 | Jatuh tempo 15 Sep; tenggang 0 | Pembayaran `paid_at` 15 Sep 23:59 WIB (= 16:59 UTC); jalankan job 16 Sep | Tenant WIB, juga WIT | Tidak ada denda; untuk tenant WIT batas mengikuti WIT | 181, 012 |
| KEU-TC-LTF-003 | Job dua kali (idempoten) | P1 | Setelah LTF-001 | Jalankan job 23 Sep lagi; dua job paralel | — | Tidak ada bucket ganda (unique `scope_key`) | 185 |
| KEU-TC-LTF-004 | Dasar setelah bayar sebagian (positif) | P1 | Setelah LTF-001 | Bayar 4.000.000 pada 10 Okt; job 23 Okt | — | Bucket Okt 91.000 (2% × 4.550.000) | 183 |
| KEU-TC-LTF-005 | Plafon (batas) | P2 | Plafon 10% = 830.000; denda kumulatif 800.000 | Bucket berikutnya 166.000 | — | Bucket dibatasi 30.000; selanjutnya tidak dibentuk | 184 |
| KEU-TC-LTF-006 | Setelah lunas (positif) | P1 | Pokok lunas 20 Nov | Job 23 Nov | — | Tidak ada bucket baru; denda lama tetap terutang | 186 |
| KEU-TC-LTF-007 | Pembebasan (positif) | P2 | Bucket 166.000 belum dibayar | `fo1` ajukan waiver; `fm1` setujui | — | Baris `late_fee_waiver` −166.000; assessment `waived`; tagihan denda `paid` (nol) | 188 |
| KEU-TC-LTF-008 | Verifikasi terlambat dibalik (positif) | P2 | Transfer `paid_at` 14 Sep, diverifikasi 25 Sep setelah bucket 23 Sep terbentuk | Verifikasi | — | Bucket 23 Sep dibalik otomatis (`PAID_ON_TIME_VERIFIED_LATE`); koreksi salah hitung manual memakai `LATE_FEE_ERROR` | 189, 191 |
| KEU-TC-LTF-009 | Mengejar bucket (integrasi) | P2 | Aturan harian Rp5.000; job mati 3 hari | Jalankan job hari ke-4 | — | 3 bucket dengan tanggal masing-masing dan dasar per tanggal | 192 |
| KEU-TC-LTF-010 | Per termin (positif) | P2 | Rencana INS-001, termin 2 terlambat | Job | `apply_per_installment_term` | Denda dihitung dari porsi dasar-denda termin 2 saja; termin 3–4 tidak didenda | 183 |
| KEU-TC-LTF-011 | Denda nonaktif (negatif) | P2 | `late_fee.enabled = false` | Jalankan job | — | Tidak ada assessment | 180 |
| KEU-TC-LTF-012 | Aturan berubah (positif) | P3 | Bucket Sep terbentuk 2% | Ubah aturan 3%; job Okt | — | Bucket Sep tetap 2%; Okt 3% | 193 |

## 14. Deposit (DEP)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-DEP-001 | Konversi kelebihan bayar (positif) | P1 | `PAY-…5502` dana belum diterapkan 450.000 | POST convert 450.000 | — | Akun `DEP-2024110012` dibuat; kredit 450.000; `deposited_amount` 450.000; `unapplied` 0 | 204, 210 |
| KEU-TC-DEP-002 | Pakai deposit (positif) | P1 | Saldo 450.000; tagihan genap 6.050.000 | `fo1` pakai 300.000; mahasiswa pakai (saklar mati lalu nyala) | — | Debit 300.000 + alokasi `deposit`; saldo 150.000; mahasiswa 403 saat saklar mati | 205, 206 |
| KEU-TC-DEP-003 | Melebihi saldo (negatif) | P1 | Tersedia 150.000 | Pakai 200.000 | — | 422 `DEPOSIT_INSUFFICIENT` | 006 |
| KEU-TC-DEP-004 | Pemakaian paralel (konkurensi) | P1 | MySQL; saldo 450.000 | Dua pemakaian 300.000 bersamaan (diulang 20×) | — | Selalu satu berhasil, satu `DEPOSIT_INSUFFICIENT`; saldo tidak pernah negatif | 006, 010 |
| KEU-TC-DEP-005 | Saldo = Σ mutasi (positif) | P1 | Rangkaian kredit/debit/penyesuaian | Jalankan `finance:verify-integrity` | — | `INV-07`, `INV-08` lulus; penyesuaian tanpa persetujuan tidak berefek | 200, 212 |
| KEU-TC-DEP-006 | Refund deposit (positif) | P1 | Saldo 150.000 | Ajukan refund 150.000; coba pakai 100.000; setujui; selesaikan dengan bukti | — | Ditahan (tersedia 0) → pemakaian ditolak; selesai: debit `refund`, saldo 0 | 208, 201 |
| KEU-TC-DEP-007 | Akun dibekukan/ditutup (negatif) | P2 | Akun `frozen`; akun bersaldo | Pakai; refund; tutup akun bersaldo | — | 409 `DEPOSIT_FROZEN`; penutupan ditolak 409 | 209, 211 |
| KEU-TC-DEP-008 | Batal pemakaian (positif) | P2 | Pemakaian 300.000 | `allocation_reversal` disetujui | — | Alokasi −300.000; kredit `invoice_payment_reversal`; saldo kembali 450.000 | 207 |
| KEU-TC-DEP-009 | Konversi ganda (idempoten) | P1 | — | Konversi dari pembayaran sama dua kali (kunci beda) | — | Kedua ditolak (unique sumber / dana tidak tersedia) | 202 |
| KEU-TC-DEP-010 | Top-up via gateway (positif) | P3 | `deposit.topup_enabled`, kanal `allow_deposit_topup` | Mahasiswa top-up 1.000.000; webhook `paid` | — | Pembayaran `purpose = deposit_topup`, kredit `topup` 1.000.000; saklar mati → 403 | 106, 203 |

## 15. UKT (UKT)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-UKT-001 | Tagihan sesuai golongan (positif) | P1 | Rina UKT-4 aktif, beasiswa aktif | Terbitkan tagihan | — | UKT 5.500.000 (aturan IF × UKT-4); snapshot `ukt_group_code`; golongan tetap UKT-4 walau ada beasiswa | 220, 222, 230 |
| KEU-TC-UKT-002 | Tanpa penetapan (negatif) | P1 | Mahasiswa baru tanpa penetapan | Resolve / billing run | — | `UKT_GROUP_MISSING`; tidak ditagih golongan default | 222 |
| KEU-TC-UKT-003 | Perubahan golongan (positif) | P1 | Tagihan G1 UKT-4 terbit | Penetapan UKT-3 mulai G2 disetujui | — | Tagihan G1 tetap 5.500.000; tagihan G2 3.000.000; UKT-4 `effective_until = G1` | 224 |
| KEU-TC-UKT-004 | Tumpang tindih (negatif) | P2 | UKT-4 aktif G1–∞ | Penetapan UKT-5 G1 untuk mahasiswa sama tanpa maksud pengganti | — | 409 | 221 |
| KEU-TC-UKT-005 | Keberatan diterima (positif) | P2 | Penetapan UKT-4, dalam batas waktu | Mahasiswa ajukan; `fo1` tinjau & terima → penetapan baru; `fo1` setujui penetapan; `fm1` setujui | — | `fo1` 403 pada penetapan hasil keberatan; `fm1` → aktif `basis = appeal` | 225, 226, 223 |
| KEU-TC-UKT-006 | Keberatan kedua / lewat waktu (negatif) | P3 | Keberatan `under_review` | Ajukan lagi; ajukan setelah 31 hari | — | 409; 422 | 225 |
| KEU-TC-UKT-007 | Validasi tarif (positif) | P2 | Golongan UKT-8 tanpa tarif TS, ada mahasiswa TS UKT-8 | GET `/ukt-validation/tariffs` | — | Temuan kritis TS × UKT-8 | 227 |
| KEU-TC-UKT-008 | Dokumen rahasia (keamanan) | P2 | Dokumen penetapan Rina | Unduh sebagai `kp-if`, `fo1`, Rina, mahasiswa lain | — | `kp-if` 403, `fo1` 200 + `downloaded`, Rina 200, lainnya 404 | 228 |
| KEU-TC-UKT-009 | Impor massal (positif/negatif) | P3 | Berkas 100 baris (3 NIM salah) | Impor | — | 97 draf; laporan galat 3 baris; persetujuan per baris dengan SoD | 229 |

## 16. Piutang (AR)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-AR-001 | Piutang = sumber (positif) | P1 | Dataset [16 §10](16-rancangan-monitoring-piutang.md#10-contoh-perhitungan) | GET `/finance-dashboard/summary` dan `/receivables/items` (as of 15 Okt 2026) | — | `receivable` 20.366.000 = Σ daftar sumber; deposit Fajar tidak mengurangi piutang; `net_receivable` 20.366.000 | 240, 243, 213 |
| KEU-TC-AR-002 | Pending tidak mengurangi (positif) | P1 | Bukti Citra 650.000 `pending` | Ringkasan | — | Piutang tetap; `payments_pending` 650.000 | 241 |
| KEU-TC-AR-003 | Batas bucket (batas) | P2 | Jatuh tempo 15 Sep | As of 15 Okt (30 hari), 16 Okt (31 hari), 15 Sep (0) | — | `d1_30`, `d31_60`, `not_due` | 242 |
| KEU-TC-AR-004 | Batal & draf tidak dihitung (positif) | P1 | Dataset + draf | Ringkasan | — | `issued_all` 66.416.000, `issued_effective` 49.566.000; draf tidak muncul | 240 |
| KEU-TC-AR-005 | Scope kaprodi (keamanan) | P1 | Scope ditegakkan | `kp-if` membuka ringkasan & detail tagihan Citra (TS) | — | Hanya IF (14.050.000); detail Citra 404 | 245 |
| KEU-TC-AR-006 | Snapshot replace (idempoten) | P2 | Snapshot 14 Okt ada | Jalankan ulang; jalankan untuk tanggal periode `closed` | — | Baris diganti tanpa duplikat; periode tertutup tidak berubah | 247 |
| KEU-TC-AR-007 | Tingkat pelunasan (positif) | P2 | Dataset | RPT-05 | — | 58,91% | 248 |

## 17. Cekal (HLD)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-HLD-001 | Cekal otomatis (positif) | P1 | `hold.enabled`; kebijakan `CKL-UJIAN` (exam, ambang 500.000, tenggang 7); Budi sisa 6.850.000 jatuh tempo 15 Sep | Job evaluasi 23 Sep | — | Cekal `active` layanan `exam`; `finance_hold_invoices` berisi tagihan Budi; histori `imposed` (sistem, kebijakan); outbox `FinanceHoldChanged` | 261, 262, 008 |
| KEU-TC-HLD-002 | Buka setelah verifikasi (integrasi) | P1 | HLD-001; SIAKAD projector aktif | Pembayaran 6.850.000 terverifikasi | — | Cekal `released` (`paid`) dan `academic_blocks` `revoked` ≤ 60 detik; `sync_status = synced` | 263, 008 |
| KEU-TC-HLD-003 | Di bawah ambang / tenggang / nonaktif (batas) | P2 | Sisa 400.000; atau baru 3 hari terlambat; atau `hold.enabled = false` | Evaluasi | — | Tidak ada cekal pada ketiga kondisi | 261, 260 |
| KEU-TC-HLD-004 | Pengecualian sebagian (positif) | P1 | Cekal `krs` + `exam` | `fo1` ajukan pengecualian `exam` 14 hari; `fm1` setujui; uji akses | — | Ujian diizinkan, KRS tetap 409 `ACADEMIC_BLOCKED`; status tetap `active` (sebagian); pengecualian > `max_exemption_days` ditolak 422 | 265 |
| KEU-TC-HLD-005 | Gagal sinkronisasi (integrasi) | P1 | Projector dibuat gagal | Ubah cekal; jalankan worker sampai retry habis | — | Retry *backoff* 8×; `sync_status = failed`, outbox `dead`, alert; blokir lama tetap (tidak dianggap terbuka); resync manual berhasil setelah pulih | 267, 273, 008 |
| KEU-TC-HLD-006 | Bypass frontend (keamanan) | P1 | Andi tercekal `krs` | Panggil langsung `POST` KRS submit/addItem dan `POST /student/exams/{exam}/start` | — | 409 `ACADEMIC_BLOCKED` di backend (mode `direct` dan `projection`) | 268 |
| KEU-TC-HLD-007 | Pembukaan manual (negatif/positif) | P2 | Cekal manual oleh `fo1`; cekal otomatis | `fo1` lepas tanpa alasan; ajukan; `fo1` setujui; `fm1` setujui; ajukan pembukaan cekal otomatis | — | 422; 403 `SELF_APPROVAL_FORBIDDEN`; `fm1` → `released (manual)`; cekal otomatis → ditolak, diarahkan ke pengecualian | 266 |
| KEU-TC-HLD-008 | Dosen wali tanpa nominal (keamanan) | P1 | Andi tercekal | `dw1` GET `/students/{andi}/finance-clearance` | — | Status & alasan tanpa `basis_outstanding_amount` | 270 |
| KEU-TC-HLD-009 | Cicilan per termin (positif) | P2 | Andi bercicilan, termin jatuh tempo lunas | Evaluasi | `use_installment_due_dates` | Tidak dicekal walau sisa tagihan 7.200.000 | 272 |
| KEU-TC-HLD-010 | Evaluasi harian & rekonsiliasi (integrasi) | P2 | Event `PaymentVerified` dihapus dari antrean (simulasi hilang); proyeksi SIAKAD diubah manual | Job evaluasi & rekonsiliasi | — | Cekal dibuka oleh job harian; ketidakcocokan proyeksi terdeteksi dan disinkron ulang | 271, 269 |
| KEU-TC-HLD-011 | Pending tidak membuka (negatif) | P2 | Tercekal; bukti `pending` | Evaluasi | — | Tetap `active`; portal menampilkan "pembayaran sedang diverifikasi" | 264 |

## 18. Rekonsiliasi, Refund, Koreksi, Periode (REC)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-REC-001 | Reversal pembayaran loket (positif) | P1 | [19 §5.4](19-rancangan-database-dan-relasi.md#54-reversal-pembayaran-yang-salah-catat) | `fo1` ajukan `payment_reversal` (`WRONG_STUDENT`); `fm1` setujui; catat ulang ke mahasiswa benar | — | Alokasi −2.050.000 (`reversal_of_id`), status tagihan kembali `partial`, kuitansi `voided`, pembayaran `reversed`, outbox `PaymentReversed`; baris asli tetap | 079, 281, 280, 121 |
| KEU-TC-REC-002 | Pembalikan berantai (negatif/positif) | P1 | Kelebihan 450.000 sudah deposit, 300.000 terpakai | Ajukan reversal pembayaran; setujui | — | `failed` dengan langkah: balik pemakaian deposit dulu; setelah dibalik, reversal berhasil | 295 |
| KEU-TC-REC-003 | Reversal ganda (negatif) | P1 | Alokasi sudah dibalik | Ajukan pembalikan alokasi yang sama | — | 409 (unique `reversal_of_id`) | 281 |
| KEU-TC-REC-004 | Refund kelebihan bayar (positif) | P1 | Dana belum diterapkan 450.000 | Ajukan refund 450.000 (`bank_transfer`); alokasikan 100.000 dari dana sama; `fo1` setujui; `fm1` setujui; selesaikan tanpa bukti; dengan bukti | Refund > ambang tingkat 2 di variasi lain | Dana ditahan (alokasi ditolak); `fo1` 403; selesai tanpa bukti 422; selesai: `refunded_amount` 450.000; variasi > ambang memerlukan `vice_rector` | 282, 283, 284, 294 |
| KEU-TC-REC-005 | Refund > dana (negatif) | P1 | Dana 450.000 | Ajukan 500.000 | — | 422 | 282 |
| KEU-TC-REC-006 | Tidak ada ubah/hapus langsung (keamanan) | P1 | Pembayaran `verified` | Cari endpoint PUT/DELETE pembayaran/alokasi/mutasi; panggil service update nominal | — | Tidak ada route; service melempar galat; `write_off` tidak tersedia | 007, 281 |
| KEU-TC-REC-007 | Rekonsiliasi dengan selisih (positif) | P1 | Batch settlement [18 §3.6](18-rancangan-rekonsiliasi-dan-penyesuaian.md#36-contoh-batch-settlement) | Impor; cocokkan; tutup dengan item terbuka; selesaikan (eskalasi dengan alasan); tutup | — | Status item sesuai contoh; penutupan pertama 409; selisih −50.000 tercatat; abaikan tanpa alasan 422; batch `closed`; biaya provider terisi | 287, 296, 104 |
| KEU-TC-REC-008 | Ada di provider, tidak di sistem (integrasi) | P1 | Transaksi `pending` yang di settlement `paid` | Selesaikan dengan `recheck_marked_paid` | — | Pembayaran dibuat lewat jalur cek status (bukan input manual) | 288 |
| KEU-TC-REC-009 | Impor ganda (negatif) | P2 | Berkas sudah diimpor | Impor ulang berkas sama | — | 409 | 286 |
| KEU-TC-REC-010 | Tutup periode (positif/negatif) | P1 | Okt 2026 dengan sesi kas belum diverifikasi | Tutup; verifikasi sesi; tutup; catat pembayaran `posting_date` 30 Okt; verifikasi transfer bertanggal 28 Okt | — | Pertama ditolak dengan daftar pemblokir; kedua `closed` + `closing_summary`; pencatatan 409 `PERIOD_CLOSED`; verifikasi → `posting_date` = hari ini (Nov) | 290, 014, 023 |
| KEU-TC-REC-011 | Buka kembali periode (keamanan) | P2 | Okt `closed` | `fm1` ajukan; `fm1` setujui; `vice_rector` setujui | — | `fm1` 403 pada persetujuan sendiri; setelah dua tingkat `open`; anomali ANM-10 | 291 |
| KEU-TC-REC-012 | Dana tak teridentifikasi (positif) | P2 | Mutasi 1.000.000 tanpa petunjuk | Selesaikan `unidentified_payment_created`; identifikasi ke Andi; verifikasi oleh staf lain | — | `bank_import`, `student_id` null, `pending_verification`; setelah identifikasi & verifikasi → alokasi FIFO | 289 |
| KEU-TC-REC-013 | Jurnal seimbang (positif) | P2 | Okt `closed`; satu komponen tanpa kode akun | Ekspor; lengkapi kode; ekspor ulang | — | Pertama gagal dengan daftar komponen; kedua Σ debit = Σ kredit | 293 |
| KEU-TC-REC-014 | Refund gagal (negatif) | P2 | Refund `processing` | Tandai `fail` | — | Penahanan dilepas; ledger tidak berubah | 285 |
| KEU-TC-REC-015 | Write-off nonaktif (negatif) | P3 | — | Ajukan `write_off` | — | 422 | 292 |

## 19. Hak Akses (RBAC)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-RBAC-001 | Tanpa permission (keamanan) | P1 | Akun `staff` baseline | Panggil seluruh endpoint tulis KEU (dihasilkan dari daftar route) | — | 403 untuk semua | 301 |
| KEU-TC-RBAC-002 | Menyetujui pembayaran tanpa izin (keamanan) | P1 | `cs1` (tanpa `payments.approve`) | POST `/payments/{id}/verify` | — | 403 | 301 |
| KEU-TC-RBAC-003 | Auditor baca saja (keamanan) | P1 | `au1` | GET laporan & audit; POST/PUT/DELETE apa pun | — | GET 200; tulis 403 | 301 |
| KEU-TC-RBAC-004 | Super admin (keamanan) | P2 | `super_admin` | Verifikasi pembayaran tanpa header alasan; dengan alasan; menyetujui pengajuannya sendiri | — | 403; 200 + `forced_override`; SoD tetap 403 | 313 |
| KEU-TC-RBAC-005 | Mahasiswa mengakses milik lain (keamanan) | P1 | Rina & Andi | Rina GET `/student/finance/invoices/{andi}`, kuitansi Andi, kirim `student_id` Andi di body | — | 404; `student_id` diabaikan | 312, 016 |

## 20. API

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-API-001 | Kunci dipakai ulang (negatif) | P1 | — | Dua POST [IDEM] kunci sama payload beda | — | 409 `IDEMPOTENCY_KEY_REUSED` | 017 |
| KEU-TC-API-002 | Kunci sedang diproses (konkurensi) | P1 | Handler diperlambat | Dua POST kunci sama bersamaan | — | Satu 201, satu 409 `IDEMPOTENCY_IN_PROGRESS`; satu sumber daya | 017 |
| KEU-TC-API-003 | Uang sebagai string (positif) | P2 | — | GET tagihan & pembayaran | — | Semua field uang string dua desimal | 011 |
| KEU-TC-API-004 | `lock_version` usang (negatif) | P2 | Draf tagihan | Dua PUT dengan `lock_version` 0 | — | Kedua 409 `STALE_VERSION` | 013 |
| KEU-TC-API-005 | Outbox saat rollback (integrasi) | P1 | Paksa galat setelah insert outbox dalam transaksi | Jalankan operasi | — | Tidak ada baris outbox maupun notifikasi | 018 |
| KEU-TC-API-006 | Kompatibilitas endpoint lama (regresi) | P1 | Data hasil migrasi | Jalankan `FinanceListEndpointsTest` + GET `/payments` dengan `invoices.read` | — | Lulus; field lama tetap | — |
| KEU-TC-API-007 | `ListQuery` (positif) | P3 | 120 tagihan | `filter[status]=unpaid,partial&sort=-due_date&per_page=100`; `per_page=500` | — | Filter & urutan benar; `per_page` dibatasi 100; `meta` lengkap | — |

## 21. Laporan (RPT)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-RPT-001 | Pembayaran per periode (positif) | P1 | Dataset + satu reversal di bulan berikutnya | RPT-02 Sep–Okt | — | Total = Σ `payments` `verified` per `posting_date`; reversal dikurangkan di bulannya; biaya admin terpisah | 244, 082 |
| KEU-TC-RPT-002 | Periode tertutup stabil (positif) | P1 | Okt `closed` | Koreksi di Nov; jalankan ulang RPT-02 & RPT-04 Okt | — | Angka Okt sama dengan `closing_summary` dan snapshot beku | 244, 014 |
| KEU-TC-RPT-003 | Ekspor berizin & diaudit (keamanan) | P1 | `fo1` (tanpa export), `fm1` | Ekspor RPT-03 | — | `fo1` 403; `fm1` 202 → berkas ber-*watermark*, rekening termasking, audit `exported` dengan filter | 246, 308 |
| KEU-TC-RPT-004 | Ekspor besar asinkron (kinerja) | P2 | 100.000 baris | Ekspor XLSX | — | Selesai tanpa timeout HTTP; tautan kedaluwarsa 24 jam | 246 |
| KEU-TC-RPT-005 | Cicilan tidak ganda (positif) | P2 | Rencana aktif | RPT-09 | — | Total dari tagihan; baris rekonsiliasi termin cocok | 130 |

## 22. Notifikasi (NTF)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-NTF-001 | Tagihan terbit (positif) | P2 | Mahasiswa dengan akun | Terbitkan; terbitkan draf (tanpa issue) | — | `finance.invoice_issued` database + mail sekali; draf tidak memicu | — |
| KEU-TC-NTF-002 | Verifikasi + kuitansi (positif) | P2 | — | Verifikasi pembayaran | — | `finance.payment_verified` dengan tautan kuitansi | — |
| KEU-TC-NTF-003 | Setelah commit (integrasi) | P1 | Paksa rollback setelah dispatch dijadwalkan | Jalankan | — | Tidak ada notifikasi; pemrosesan ulang event tidak menggandakan | 018 |
| KEU-TC-NTF-004 | Pengingat tidak ke tagihan lunas (positif) | P2 | Tagihan jatuh tempo +7 hari: satu lunas, satu belum | Job pengingat | — | Hanya yang belum lunas; satu digest per mahasiswa | — |
| KEU-TC-NTF-005 | Dosen wali tanpa nominal (keamanan) | P2 | Mahasiswa bimbingan tercekal | Job ringkasan | — | Isi tanpa nominal | 270 |

## 23. Keamanan (SEC)

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-SEC-001 | Isolasi tenant (keamanan) | P1 | T-UND & T-ITM | Staf T-ITM GET/POST memakai id tagihan, pembayaran, berkas T-UND (diulang untuk setiap endpoint) | — | 404 seragam | 015 |
| KEU-TC-SEC-002 | Bukti milik lain (keamanan) | P1 | Bukti Rina | Andi unduh berkas bukti Rina; `cs1` unduh | — | 404; `cs1` sesuai izin `payments.read` | 304 |
| KEU-TC-SEC-003 | Masking (keamanan) | P1 | Rekening & refund | Periksa API, UI, ekspor, `audit_logs`, log aplikasi | — | Hanya 4 digit akhir; kode verifikasi kuitansi tidak membocorkan NIM | 306 |
| KEU-TC-SEC-004 | Audit verifikasi lengkap (positif) | P1 | — | Verifikasi dengan nominal berbeda | — | `audit_logs` `verified`: pelaku, waktu, IP, nilai klaim vs nominal, alasan; histori status | 307 |
| KEU-TC-SEC-005 | Pembatasan laju (keamanan) | P2 | — | 11 transaksi online dalam satu jam | — | Ke-11 → 429 | 311 |
| KEU-TC-SEC-006 | Deteksi anomali (positif) | P2 | `fo1` mengajukan 6 reversal dalam 7 hari; dua refund ke rekening sama untuk dua mahasiswa | Job anomali | — | ANM-01 dan ANM-02 terpicu; notifikasi ke bendahara & auditor | 310 |
| KEU-TC-SEC-007 | Transisi atomik (positif) | P1 | Paksa galat saat menulis histori | Verifikasi pembayaran | — | Seluruh transaksi rollback; status tidak berubah | 013 |
| KEU-TC-SEC-008 | Audit immutable (keamanan) | P2 | — | Cari route ubah/hapus `audit_logs`/`finance_status_histories` | — | Tidak ada | 314 |
| KEU-TC-SEC-009 | Lihat nomor penuh tercatat (positif) | P2 | — | `ia1` reveal rekening | — | `viewed_sensitive` dengan pelaku & waktu | 306, 307 |
| KEU-TC-SEC-010 | Alasan wajib (negatif) | P2 | — | Tolak bukti, batalkan tagihan, bekukan deposit tanpa alasan | — | 422 untuk semua | 019, 280 |
| KEU-TC-SEC-011 | Retensi payload webhook (positif) | P3 | Event 401 hari | Job retensi | — | `payload = {"purged": true}`; metadata & hasil pemrosesan tetap | 309 |

## 24. UI

| ID | Judul (jenis) | Prio | Prasyarat | Langkah | Data uji | Hasil diharapkan | BR |
|---|---|---|---|---|---|---|---|
| KEU-TC-UI-001 | Tombol sesuai izin (keamanan) | P2 | `fo1`, `cs1` | Buka detail tagihan & verifikasi | — | Tombol tanpa izin tersembunyi; memanggil API langsung tetap 403 | 301 |
| KEU-TC-UI-002 | Konfirmasi kritis (positif) | P2 | — | Batalkan tagihan, ajukan reversal, publikasi tarif | — | Dialog menampilkan ringkasan dampak & alasan wajib | — |
| KEU-TC-UI-003 | Portal ponsel (positif) | P3 | Lebar 375 px | Alur lihat tagihan → bayar VA → instruksi → kuitansi | — | Dapat diselesaikan tanpa gulir horizontal; tombol salin VA berfungsi | — |
| KEU-TC-UI-004 | State halaman (positif) | P3 | — | Daftar kosong, API lambat, API 500 | — | Empty state berisi aksi berikut; skeleton; alert dengan "Coba lagi" | — |
