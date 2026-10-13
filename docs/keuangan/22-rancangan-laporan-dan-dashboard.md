# 22 — Rancangan Laporan dan Dashboard

> **Status:** rancangan. Disusun 2026-10-13. Laporan keuangan yang ada saat ini hanya `ReportService::buildKeuangan()` (daftar tagihan sederhana) dan widget `DashboardStatsService` (`unpaid_invoices`, `payment_trend`, `invoice_status`), yang akan disesuaikan ([19 §6.3](19-rancangan-database-dan-relasi.md#63-penyesuaian-konsumen-yang-ada)).
> Kembali ke [README](README.md) · Rumus indikator: [16 §4](16-rancangan-monitoring-piutang.md#4-rumus-indikator) · Aturan: `KEU-BR-240`–`259` ([25](25-business-rules-dan-validasi.md))

Dokumen ini mendefinisikan dashboard per audiens dan 18 laporan (`KEU-RPT-01`…`18`). Setiap laporan memiliki sumber data, rumus, filter, kolom, pengelompokan, hak akses, dan format ekspor. Semua definisi angka memakai rumus baku [16 §4](16-rancangan-monitoring-piutang.md#4-rumus-indikator) agar laporan yang berbeda tidak menghasilkan angka yang berbeda untuk hal yang sama.

## Daftar Isi

1. [Prinsip](#1-prinsip)
2. [Dashboard per Audiens](#2-dashboard-per-audiens)
3. [Katalog Laporan](#3-katalog-laporan)
4. [Definisi Laporan](#4-definisi-laporan)
5. [Ekspor](#5-ekspor)
6. [Konsistensi dan Kinerja](#6-konsistensi-dan-kinerja)
7. [Acceptance Criteria dan Test Case](#7-acceptance-criteria-dan-test-case)

---

## 1. Prinsip

1. **Satu definisi untuk satu angka.** Laporan memakai layanan perhitungan yang sama dengan dashboard (`ReceivableQueryService`, `PaymentReportService`), bukan query tersendiri per halaman.
2. **Laporan arus** (pembayaran, penerbitan, denda, refund) berbasis `posting_date`/`issued_at` dalam rentang; **laporan posisi** (piutang, umur, deposit) berbasis tanggal acuan ([KEU-BR-244](25-business-rules-dan-validasi.md)).
3. **Periode tertutup stabil**: laporan untuk rentang dalam periode `closed` menghasilkan angka yang sama setelah koreksi di periode berikutnya ([KEU-BR-014](25-business-rules-dan-validasi.md)).
4. **Scope** diterapkan sebelum filter; dekan/kaprodi hanya melihat unitnya ([KEU-BR-245](25-business-rules-dan-validasi.md)).
5. **Dapat ditelusuri**: setiap baris agregat memiliki tautan ke daftar sumber ([KEU-BR-243](25-business-rules-dan-validasi.md)).

## 2. Dashboard per Audiens

| Audiens | Halaman | Isi utama |
|---|---|---|
| Staf keuangan / petugas loket | `/keuangan/dashboard` (tampilan operasional) | Antrean kerja: bukti menunggu verifikasi (dengan umur), dana belum diterapkan, sengketa terbuka, item rekonsiliasi terbuka, sesi kas belum ditutup, pembayaran hari ini per kanal |
| Administrator keuangan | Sama + tab Penagihan | Status tagihan massal, `TARIFF_NOT_FOUND`/`UKT_GROUP_MISSING` pada pratinjau terakhir, cicilan dengan termin terlambat, denda terbentuk minggu ini |
| Bendahara | Sama + tab Kontrol | Kotak persetujuan, selisih kas, pelanggaran invarian, kegagalan sinkronisasi cekal, status periode berjalan |
| Pimpinan universitas | `/keuangan/dashboard` (tampilan pimpinan) | Piutang total & tren 12 bulan, umur piutang, tingkat pelunasan per fakultas dan angkatan, pembayaran per kanal, nilai beasiswa per sumber dana |
| Dekan / kaprodi | Tampilan pimpinan dengan scope | Sama, terbatas pada unitnya; mahasiswa belum lunas dalam scope (tanpa rincian bukti/rekening) |
| Auditor | Laporan audit & daftar sumber | Akses baca seluruh laporan; ringkasan tindakan sensitif (*override*, reversal, pembukaan periode) |
| Administrator integrasi | `/keuangan/integrasi` | Kesehatan kanal, webhook gagal/tidak valid, outbox `failed/dead`, sinkronisasi cekal |

Widget platform yang sudah ada disesuaikan: `unpaid_invoices` = jumlah tagihan `unpaid/partial` (tanpa `draft`/`cancelled`); `payment_trend` = `payments_verified` per bulan menurut `posting_date`; `invoice_status` memakai label baru.

## 3. Katalog Laporan

| Kode | Laporan | Jenis | Audiens utama | Ekspor |
|---|---|---|---|---|
| KEU-RPT-01 | Tagihan per mahasiswa (rekening koran mahasiswa) | Posisi + riwayat | FA, FO, mahasiswa (versi portal) | XLSX, PDF |
| KEU-RPT-02 | Pembayaran per periode | Arus | FA, FM, RK | CSV, XLSX, PDF (ringkasan) |
| KEU-RPT-03 | Tunggakan mahasiswa | Posisi | FA, FO, FM, DK | CSV, XLSX |
| KEU-RPT-04 | Umur piutang | Posisi | FM, RK, DK | XLSX, PDF |
| KEU-RPT-05 | Pelunasan per angkatan | Kohort | RK, DK, FM | XLSX, PDF |
| KEU-RPT-06 | Pembayaran per kanal | Arus | FM, IA | CSV, XLSX |
| KEU-RPT-07 | Pembayaran loket | Arus | FM, FO | XLSX, PDF (laporan sesi) |
| KEU-RPT-08 | Transaksi online | Arus | IA, FM | CSV, XLSX |
| KEU-RPT-09 | Cicilan | Posisi | FA, FM | XLSX |
| KEU-RPT-10 | Beasiswa dan potongan | Arus + posisi | FM, RK | XLSX, PDF |
| KEU-RPT-11 | Penggunaan voucher | Arus | FA, FM | CSV, XLSX |
| KEU-RPT-12 | Denda | Arus | FM | CSV, XLSX |
| KEU-RPT-13 | Saldo dan mutasi deposit | Posisi + arus | FM, AU | XLSX, PDF |
| KEU-RPT-14 | Penetapan UKT | Posisi | FA, FM, RK | XLSX |
| KEU-RPT-15 | Rekonsiliasi | Arus | FM, AU | XLSX, PDF |
| KEU-RPT-16 | Pengembalian dana dan reversal | Arus | FM, AU | XLSX |
| KEU-RPT-17 | Cekal keuangan | Posisi + arus | FM, AA (tanpa nominal) | CSV, XLSX |
| KEU-RPT-18 | Audit perubahan | Arus | AU, FM | CSV, XLSX (terbatas) |

## 4. Definisi Laporan

### KEU-RPT-01 — Tagihan per Mahasiswa

| Aspek | Isi |
|---|---|
| Tujuan | Rekening koran keuangan seorang mahasiswa (atau sekelompok) |
| Sumber | `invoices`, `invoice_items`, `payment_allocations`, `payments`, `deposit_transactions`, `installment_terms` |
| Filter | Mahasiswa (NIM) atau kelompok (prodi, angkatan), rentang term, jenis tagihan, status, tanggal acuan |
| Kolom | Nomor tagihan, periode, jenis, tanggal terbit, jatuh tempo, bruto, potongan, penyesuaian, neto, dibayar, sisa, status; mode rinci menambah baris komponen (tarif, versi, aturan) dan alokasi (nomor pembayaran, tanggal, kanal) |
| Pengelompokan | Per mahasiswa → per tagihan; subtotal per mahasiswa: Σ neto, Σ dibayar, Σ sisa, deposit tersedia, dana belum diterapkan, piutang bersih |
| Rumus | [06 §3](06-rancangan-tagihan-mahasiswa.md#3-definisi-nominal) |
| Hak akses | `finance_reports.read` (FA, FO, FM, AU, RK; DK dalam scope); mahasiswa memperoleh versi portalnya sendiri |
| Ekspor | XLSX; PDF "Rekening Koran Mahasiswa" per mahasiswa |

### KEU-RPT-02 — Pembayaran per Periode

| Aspek | Isi |
|---|---|
| Sumber | `payments` (`verified` dan `reversed`), `payment_allocations`, `payment_channels` |
| Filter | Rentang `posting_date`, sumber, kanal, prodi, angkatan, rekening penerima |
| Kolom | Tanggal, jumlah transaksi, Σ diterima, Σ biaya admin dibayar mahasiswa (terpisah), Σ dialokasikan ke tagihan, Σ dikonversi ke deposit, Σ dana belum diterapkan pada akhir periode, Σ reversal dalam periode, neto |
| Pengelompokan | Harian / mingguan / bulanan; opsional per kanal atau sumber |
| Rumus | `payments_verified` [16 §4](16-rancangan-monitoring-piutang.md#4-rumus-indikator) (reversal dikurangkan pada periode reversal dibukukan) |
| Hak akses | FA, FM, RK, AU (FO baca tanpa ekspor) |
| Ekspor | CSV, XLSX, PDF ringkasan |

### KEU-RPT-03 — Tunggakan Mahasiswa

| Aspek | Isi |
|---|---|
| Sumber | `invoices`/`installment_terms` (bagian jatuh tempo), `students`, `finance_holds` |
| Filter | Tanggal acuan, term, jenis, fakultas/prodi, angkatan, status mahasiswa, bucket umur minimum, ambang nominal |
| Kolom | NIM, nama, prodi, angkatan, status mahasiswa, jumlah tagihan terlambat, Σ jatuh tempo terutang, bucket terburuk, jatuh tempo tertua, pembayaran pending, status cekal, kontak (surel kampus) |
| Pengelompokan | Per mahasiswa; subtotal per prodi |
| Rumus | `receivable_due` per mahasiswa |
| Hak akses | FA, FO, FM, AU, RK; DK dalam scope |
| Ekspor | CSV, XLSX |

### KEU-RPT-04 — Umur Piutang

| Aspek | Isi |
|---|---|
| Sumber | Bagian sisa tagihan (§[16 §6](16-rancangan-monitoring-piutang.md#6-umur-piutang)); snapshot untuk tanggal lalu |
| Filter | Tanggal acuan, term, jenis tagihan, fakultas/prodi, angkatan |
| Kolom | Dimensi, belum jatuh tempo, 1–30, 31–60, 61–90, > 90 (sesuai `finance.aging.buckets`), total, % per bucket |
| Pengelompokan | Fakultas → prodi; atau angkatan; atau jenis tagihan |
| Rumus | `aging[b]`; Σ bucket = `receivable` |
| Hak akses | FA, FM, RK, AU; DK dalam scope |
| Ekspor | XLSX, PDF |

### KEU-RPT-05 — Pelunasan per Angkatan

| Aspek | Isi |
|---|---|
| Sumber | Tagihan efektif kohort (term × jenis) dan alokasinya s.d. tanggal acuan |
| Filter | Term (kohort), jenis tagihan, fakultas/prodi, tanggal acuan |
| Kolom | Angkatan, jumlah mahasiswa bertagihan, Σ nominal, Σ dibayar, Σ sisa, `collection_rate`, `students_paid_rate`, rata-rata hari bayar setelah terbit |
| Pengelompokan | Angkatan; opsional × prodi |
| Rumus | [16 §4](16-rancangan-monitoring-piutang.md#4-rumus-indikator) |
| Hak akses | FA, FM, RK, AU; DK dalam scope |
| Ekspor | XLSX, PDF |

### KEU-RPT-06 — Pembayaran per Kanal

| Aspek | Isi |
|---|---|
| Sumber | `payments` `verified`, `payment_channels`, `reconciliation_items` (biaya provider) |
| Filter | Rentang `posting_date`, kanal, provider, jenis kanal |
| Kolom | Kanal, jumlah pembayaran, Σ nominal, Σ biaya ditanggung mahasiswa, Σ biaya provider ditanggung kampus (dari settlement), neto diterima, rata-rata nominal, porsi % |
| Pengelompokan | Kanal; opsional per bulan |
| Hak akses | FM, IA, AU, FA |
| Ekspor | CSV, XLSX |

### KEU-RPT-07 — Pembayaran Loket

| Aspek | Isi |
|---|---|
| Sumber | `cashier_sessions`, `payments` `source = cashier`, `refunds` `method = cash` |
| Filter | Rentang tanggal, petugas, status sesi |
| Kolom | Nomor sesi, petugas, buka–tutup, kas awal, jumlah transaksi, Σ tunai, Σ EDC, refund tunai, kas diharapkan, kas dihitung, selisih, diverifikasi oleh |
| Pengelompokan | Per sesi; subtotal per petugas dan per hari |
| Hak akses | FM, FO, AU (CS: sesi miliknya) |
| Ekspor | XLSX; PDF laporan sesi (untuk tanda tangan setoran) |

### KEU-RPT-08 — Transaksi Online

| Aspek | Isi |
|---|---|
| Sumber | `payment_transactions`, `payment_webhook_events` |
| Filter | Rentang pembuatan, kanal, status, `status_reason` |
| Kolom | Kanal, dibuat, berhasil, kedaluwarsa, gagal, dibatalkan, tingkat keberhasilan (%), rata-rata waktu sampai bayar, pembayaran setelah kedaluwarsa, selisih nominal, webhook tidak valid |
| Pengelompokan | Kanal × hari/bulan |
| Hak akses | IA, FM, AU, FA |
| Ekspor | CSV, XLSX |

### KEU-RPT-09 — Cicilan

| Aspek | Isi |
|---|---|
| Sumber | `installment_plans`, `installment_terms`, `invoices` |
| Filter | Status rencana, term, prodi, termin terlambat, tanggal acuan |
| Kolom | Nomor rencana, mahasiswa, tagihan, versi, nominal dicicil, bagian di luar jadwal, termin (n, jatuh tempo, nominal, dibayar, status), sisa tagihan |
| Pengelompokan | Per rencana; baris rekonsiliasi "Σ sisa termin + terutang di luar jadwal = sisa tagihan" ([10 §9](10-rancangan-cicilan-tagihan.md#9-pelaporan-tanpa-hitung-ganda)) |
| Total | Diambil dari tagihan, **bukan** penjumlahan termin + tagihan ([KEU-BR-130](25-business-rules-dan-validasi.md)) |
| Hak akses | FA, FM, FO, AU |
| Ekspor | XLSX |

### KEU-RPT-10 — Beasiswa dan Potongan

| Aspek | Isi |
|---|---|
| Sumber | `invoice_items` pengurang (`scholarship`, `discount`, `voucher`, `manual_discount`) pada tagihan tidak `cancelled`, `discount_schemes`, `student_discounts` |
| Filter | Term, skema, jenis skema, sumber dana, fakultas/prodi, angkatan |
| Kolom | Skema, sumber dana, jumlah penerima, kuota, Σ potongan, Σ potongan per komponen, rata-rata per penerima |
| Pengelompokan | Skema → prodi; atau sumber dana |
| Rumus | Σ −`amount` baris pengurang (nilai positif); potongan manual ditampilkan terpisah |
| Hak akses | FA, FM, RK, AU; DK dalam scope |
| Ekspor | XLSX, PDF |

### KEU-RPT-11 — Penggunaan Voucher

| Aspek | Isi |
|---|---|
| Sumber | `vouchers`, `voucher_redemptions`, `invoice_items` `voucher` |
| Filter | Voucher, konteks, rentang penerapan, status redemption |
| Kolom | Kode, konteks, kuota total, terpakai (`applied`), ditahan (`reserved`), dilepas/dibatalkan, Σ potongan, prodi pengguna |
| Hak akses | FA, FM, AU |
| Ekspor | CSV, XLSX |

### KEU-RPT-12 — Denda

| Aspek | Isi |
|---|---|
| Sumber | `late_fee_assessments`, tagihan `late_fee`, alokasinya |
| Filter | Rentang `bucket_date`, aturan, term, prodi, status |
| Kolom | Σ dibentuk, Σ dibebaskan, Σ dibalik (koreksi), Σ dibayar, Σ sisa, jumlah mahasiswa terkena |
| Pengelompokan | Aturan → bulan; opsional prodi |
| Hak akses | FM, FA, AU |
| Ekspor | CSV, XLSX |

### KEU-RPT-13 — Saldo dan Mutasi Deposit

| Aspek | Isi |
|---|---|
| Sumber | `deposit_accounts`, `deposit_transactions` |
| Filter | Rentang `posting_date`, mahasiswa, prodi, status mahasiswa, status akun |
| Kolom | Saldo awal, kredit per jenis (konversi, top-up, penyesuaian, pembalikan), debit per jenis (pemakaian, refund, penyesuaian), saldo akhir, ditahan, tersedia |
| Pengelompokan | Total tenant; per mahasiswa (rinci) |
| Rumus | Saldo awal + Σ kredit − Σ debit = saldo akhir ([14 §9](14-rancangan-deposit-mahasiswa.md#9-rekonsiliasi-saldo)) |
| Hak akses | FM, FA, AU |
| Ekspor | XLSX, PDF |

### KEU-RPT-14 — Penetapan UKT

| Aspek | Isi |
|---|---|
| Sumber | `ukt_determinations`, `ukt_appeals`, `ukt_groups`, validasi [15 §7](15-rancangan-ukt-dan-validasi-penerima.md#7-validasi-tarif-dan-validasi-penerima) |
| Filter | Term, prodi, angkatan, golongan, dasar penetapan |
| Kolom | Golongan × prodi × angkatan (jumlah mahasiswa), perubahan golongan dalam periode (naik/turun), keberatan (diajukan/diterima/ditolak), temuan validasi |
| Hak akses | FA, FM, RK, AU; DK dalam scope (tanpa dokumen) |
| Ekspor | XLSX |

### KEU-RPT-15 — Rekonsiliasi

| Aspek | Isi |
|---|---|
| Sumber | `reconciliation_batches`, `reconciliation_items` |
| Filter | Rentang tanggal mutasi/settlement, sumber, rekening/provider, status |
| Kolom | Batch, rentang, baris, cocok, pengecualian per jenis, Σ sumber, Σ sistem, selisih, item terbuka, penyelesaian per tindakan, penutup batch |
| Hak akses | FM, FO, AU |
| Ekspor | XLSX, PDF |

### KEU-RPT-16 — Pengembalian Dana dan Reversal

| Aspek | Isi |
|---|---|
| Sumber | `refunds`, `finance_adjustments` (tipe reversal/koreksi/pembatalan/potongan manual/pembebasan) |
| Filter | Rentang `posting_date`/pengajuan, tipe, status, kode alasan, pengaju, penyetuju |
| Kolom | Nomor, tipe, mahasiswa, sasaran, nominal, alasan, pengaju, penyetuju (dan tingkat kedua), waktu, status |
| Pengelompokan | Tipe → alasan |
| Hak akses | FM, AU, FA |
| Ekspor | XLSX |

### KEU-RPT-17 — Cekal Keuangan

| Aspek | Isi |
|---|---|
| Sumber | `finance_holds`, `finance_hold_invoices`, `finance_hold_exemptions`, `finance_status_histories`, hasil rekonsiliasi status |
| Filter | Status tampilan, layanan, kebijakan, term, prodi, rentang tanggal |
| Kolom | Mahasiswa, kebijakan, layanan, status, sejak, dasar tunggakan (hanya bila `invoices.read`), pengecualian aktif, sinkronisasi; ringkasan: waktu rata-rata pembukaan setelah pembayaran, jumlah kegagalan sinkronisasi, ketidakcocokan rekonsiliasi |
| Hak akses | FA, FM, AU, RK; AA dan DK (scope) **tanpa** kolom nominal |
| Ekspor | CSV, XLSX |

### KEU-RPT-18 — Audit Perubahan

| Aspek | Isi |
|---|---|
| Sumber | `audit_logs` (entitas modul KEU), `finance_status_histories` |
| Filter | Rentang waktu, pelaku, aksi (`created`, `updated`, `approved`, `rejected`, `verified`, `exported`, `viewed_sensitive`, `forced_override`, …), jenis entitas, nomor dokumen |
| Kolom | Waktu, pelaku, peran, aksi, entitas & nomor, ringkasan perubahan (nilai lama → baru, termasking), alasan, IP, perangkat |
| Sorotan | Tindakan *override*, perubahan pengaturan sensitif (termasuk SoD), pembukaan periode, reversal oleh pengguna yang sama berulang ([24 §12](24-keamanan-audit-dan-kontrol-internal.md)) |
| Hak akses | AU, FM (baca); ekspor hanya AU dan FM, selalu tercatat |
| Ekspor | CSV, XLSX dengan *watermark* |

## 5. Ekspor

| Aspek | Ketentuan ([KEU-BR-246](25-business-rules-dan-validasi.md), [KEU-BR-308](25-business-rules-dan-validasi.md)) |
|---|---|
| Format | CSV (data mentah), XLSX (berformat, `openspout`), PDF (laporan resmi, `dompdf`); format per laporan di §3 |
| Mekanisme | ≤ 5.000 baris: sinkron. Lebih besar atau PDF: job antrean (*chunk* 1.000 baris), berkas disimpan sebagai `file_uploads` privat; unduhan lewat tautan terautentikasi, kedaluwarsa 24 jam |
| Hak akses | `finance_reports.export` + scope pengguna; laporan berisi data sensitif (RPT-13 rinci, RPT-18) hanya FM/AU |
| Audit | Setiap ekspor: `audit_logs` `exported` dengan kode laporan, filter, jumlah baris, format; unduhan berkas: `downloaded` |
| Masking | Nomor rekening hanya 4 digit akhir; tidak ada kredensial; nominal disembunyikan untuk peran tanpa `invoices.read` |
| *Watermark* | Kepala berkas: "Rahasia — diekspor oleh {nama} ({peran}) pada {waktu zona tenant}" |
| Minimisasi | Kolom pribadi di luar keperluan (alamat, telepon) tidak disertakan kecuali laporan memerlukannya |
| Batas | Maks 5 ekspor berjalan per pengguna; maks 500.000 baris per ekspor (lebih dari itu harus dipersempit filter) |

## 6. Konsistensi dan Kinerja

| Aspek | Ketentuan |
|---|---|
| Uji konsistensi | Test otomatis membandingkan total setiap laporan dengan Σ baris sumber pada data uji ([KEU-TC-AR-001](26-skenario-pengujian.md), [KEU-TC-RPT-001](26-skenario-pengujian.md)) |
| Periode tertutup | Laporan arus untuk periode `closed` dibandingkan dengan `finance_periods.closing_summary` ([KEU-TC-RPT-002](26-skenario-pengujian.md)) |
| Snapshot | Laporan posisi historis memakai `receivable_snapshots` ([16 §5](16-rancangan-monitoring-piutang.md#5-data-waktu-nyata-dan-snapshot)) |
| Indeks | Semua filter utama memiliki indeks komposit berawalan `university_id` ([19 §9](19-rancangan-database-dan-relasi.md#9-ringkasan-indeks-dan-volume)) |
| Waktu respons | Laporan berpaginasi p95 < 2 detik untuk tenant 10.000 mahasiswa [R]; ekspor besar asinkron |

## 7. Acceptance Criteria dan Test Case

| Acceptance criteria | Test case |
|---|---|
| [KEU-AC-RPT-01](27-acceptance-criteria.md) 18 laporan tersedia dengan definisi di dokumen ini | [KEU-TC-RPT-001](26-skenario-pengujian.md), [KEU-TC-RPT-005](26-skenario-pengujian.md) |
| [KEU-AC-RPT-02](27-acceptance-criteria.md) Angka = Σ sumber | [KEU-TC-AR-001](26-skenario-pengujian.md), [KEU-TC-RPT-001](26-skenario-pengujian.md) |
| [KEU-AC-RPT-03](27-acceptance-criteria.md) Ekspor dibatasi izin dan tercatat | [KEU-TC-RPT-003](26-skenario-pengujian.md) |
| [KEU-AC-RPT-04](27-acceptance-criteria.md) Kinerja & ekspor asinkron | [KEU-TC-RPT-004](26-skenario-pengujian.md) |
| [KEU-AC-RPT-05](27-acceptance-criteria.md) Periode tertutup stabil | [KEU-TC-RPT-002](26-skenario-pengujian.md) |
