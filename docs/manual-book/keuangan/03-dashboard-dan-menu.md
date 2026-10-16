# 3. Dashboard Keuangan dan Struktur Menu

[← Akses & Peran](02-akses-dan-peran.md) · [Daftar isi](README.md) · [Berikutnya: Tarif & Komponen →](10-tarif-dan-komponen-biaya.md)

## 1. Struktur menu Keuangan (petugas)

Menu **Keuangan** berada di sidebar kiri. Sub-menu yang tampil mengikuti izin akun.

| Sub-menu | Alamat | Fungsi | Izin baca |
|---|---|---|---|
| **Dashboard Keuangan** | `/keuangan` | Ringkasan angka, antrean kerja, aging, rincian per dimensi | `finance_dashboard.read` |
| **Tagihan** | `/keuangan/tagihan` | Daftar & detail tagihan, tagihan manual, ekspor | `invoices.read` |
| **Penerbitan Massal** | `/keuangan/penerbitan` | Simulasi & penerbitan tagihan semester | `billing_runs.read` |
| **Penyesuaian Tagihan** | `/keuangan/penyesuaian` | Antrean persetujuan penyesuaian | `invoice_adjustments.read` |
| **Loket Kasir** | `/keuangan/kasir` | Sesi kas, pembayaran tunai, setoran | `cashier_sessions.read` |
| **Verifikasi Transfer** | `/keuangan/verifikasi-transfer` | Antrean bukti transfer manual | `payment_transactions.approve` |
| **Transaksi Pembayaran** | `/keuangan/pembayaran` | Semua transaksi & detailnya | `payment_transactions.read` |
| **Rekonsiliasi** | `/keuangan/rekonsiliasi` | Batch pencocokan mutasi | `reconciliations.read` |
| **Deposit & Refund** | `/keuangan/deposit` | Saldo deposit, pengembalian dana | `student_deposits.read` / `refunds.read` |
| **Cicilan** | `/keuangan/cicilan` | Pengajuan & skema cicilan | `installments.read` |
| **Denda** | `/keuangan/denda` | Denda berjalan & aturan denda | `penalties.read` |
| **Beasiswa, Potongan & Voucher** | `/keuangan/potongan` | Penetapan, jenis potongan, voucher | `financial_aid.read` / `vouchers.read` |
| **UKT** | `/keuangan/ukt` | Golongan, penetapan, peninjauan UKT | `ukt.read` |
| **Cekal Keuangan** | `/keuangan/cekal` | Mahasiswa tercekal, dispensasi, aturan | `finance_clearance.read` |
| **Tarif & Komponen** | `/keuangan/tarif` | Tabel tarif & komponen biaya | `fee_rates.read` / `fee_components.read` |
| **Laporan Keuangan** | `/keuangan/laporan` | 13 laporan & ekspor CSV | `finance_reports.read` |
| **Pengaturan Keuangan** | `/keuangan/pengaturan` | Kanal pembayaran & parameter kebijakan | `finance_settings.read` / `payment_channels.read` |
| **Program Beasiswa** | `/keuangan/beasiswa` | Modul Beasiswa yang sudah ada sebelumnya (program/pendaftar beasiswa) | `scholarships.read` |

Halaman detail yang dibuka dari daftar: detail tagihan, detail penerbitan, detail transaksi, detail rekonsiliasi, detail tabel tarif, detail cekal, dan **profil keuangan mahasiswa** (`/keuangan/mahasiswa/{id}` — dibuka dengan mengklik NIM di daftar mana pun).

Mahasiswa melihat menu **Keuangan** di portal (`/portal/keuangan`), lihat [Panduan Mahasiswa](30-panduan-per-peran.md#1-mahasiswa).

## 2. Dashboard Keuangan

**Tujuan:** memantau kesehatan keuangan mahasiswa dan pekerjaan yang menunggu.
**Siapa:** pemegang `finance_dashboard.read` (seluruh peran keuangan, pimpinan, auditor, admin universitas).
**Lokasi:** **Keuangan → Dashboard Keuangan**.

### 2.1 Filter

Di bagian atas: **Semester**, **Fakultas**, **Program studi**, **Angkatan**, dan rentang **Penerimaan dari … sampai**. Filter semester/fakultas/prodi/angkatan memengaruhi angka tagihan dan piutang; rentang tanggal memengaruhi angka penerimaan (bawaan 30 hari terakhir). Angka berubah begitu filter dipilih.

### 2.2 Kartu ringkasan

| Kartu | Arti |
|---|---|
| **Total tagihan (neto)** | Jumlah neto tagihan terbit (tanpa draf & dibatalkan), dengan keterangan bruto dan potongan |
| **Penerimaan periode** | Uang masuk berhasil pada rentang tanggal (tidak termasuk pemakaian deposit), jumlah transaksi |
| **Piutang berjalan** | Sisa tagihan berstatus Belum Dibayar/Dibayar Sebagian; keterangan jumlah yang jatuh tempo ≤ 7 hari |
| **Tunggakan** | Bagian piutang yang sudah lewat jatuh tempo + tenggang |
| **Tingkat kolektibilitas** | Dibayar ÷ ditagihkan (%) |
| **Saldo deposit mahasiswa** | Total saldo deposit seluruh mahasiswa |
| **Mahasiswa tercekal (keuangan)** | Jumlah mahasiswa dengan cekal keuangan aktif |
| **Menunggu persetujuan** | Penyesuaian + penerbitan + beasiswa + cicilan yang menunggu keputusan |

### 2.3 Antrean kerja

Empat kotak tautan: **Bukti transfer menunggu verifikasi**, **Transaksi online pending > 24 jam**, **Pengecualian rekonsiliasi terbuka**, **Pengembalian dana berjalan**. Angka berwarna jingga berarti ada pekerjaan; klik untuk membuka halamannya.

### 2.4 Grafik & tabel

- **Tren penerimaan** — batang per hari (per bulan bila rentang > 62 hari).
- **Umur piutang (aging)** — Belum jatuh tempo, 1–30, 31–60, 61–90, 91–180, > 180 hari.
- **Rincian per dimensi** — tab **Program studi**, **Fakultas**, **Angkatan**, **Status mahasiswa** berisi jumlah tagihan, ditagihkan, dibayar, piutang, tunggakan.
- **Penerimaan terbaru** — 10 transaksi terakhir (klik kuitansi untuk detail).
- **Prioritas penagihan** — 10 tagihan menunggak dengan sisa terbesar (klik NIM untuk profil keuangan mahasiswa).

**Catatan:** Dashboard utama Civitas One (menu **Dashboard**) tetap menampilkan grafik tagihan & pembayaran lamanya; klik pada grafik tersebut membuka **Tagihan** atau **Transaksi Pembayaran** KEU dengan filter yang sesuai.

## 3. Pola halaman yang sama di semua menu

- **Daftar** memiliki kotak **Cari** (NIM, nama, nomor), filter, dan tombol **Terapkan**; navigasi halaman di bawah tabel.
- **Pesan hasil** muncul di atas halaman (hijau = berhasil, merah = gagal) dan dapat ditutup.
- **Dialog formulir** menandai isian wajib dengan `*`. Bila isian ditolak server, pesan muncul di bawah isian terkait.
- **Dialog alasan** (tolak, batalkan, ekspor, buka cekal) mewajibkan alasan (umumnya minimal 10 karakter) yang tercatat di audit.

## 4. Pengaturan Keuangan

**Siapa:** melihat — `finance_settings.read` / `payment_channels.read`; mengubah — Kepala Bagian Keuangan (`finance_settings.update`, `payment_channels.update`) dan Admin Universitas (kanal).
**Lokasi:** **Keuangan → Pengaturan Keuangan**.

### 4.1 Kanal pembayaran & kuota paket

Kartu **Kanal pembayaran** menampilkan:

- **Kuota kanal online produksi** — *terpakai dari batas*, nama paket langganan, dan sumber batas (*batas paket langganan*, *acuan paket*, *penimpaan Super Admin*, atau *bawaan*). Tunai, transfer manual, dan kanal **sandbox** tidak dihitung. Batas acuan per paket: Growth 3, Business 3, Professional 4, Premium 5, Enterprise 6.
- Peringatan bila **belum ada penyedia pembayaran online yang terkonfigurasi di server** — artinya kanal online belum dapat dipakai bertransaksi.
- Tabel kanal: nama & kode, biaya admin dan penanggungnya, lingkungan (sandbox/produksi), **Dihitung kuota**, **Terhubung** (Ya/Belum), status.

Langkah menambah kanal online:

1. Klik **Kanal**, isi **Kode**, **Nama tampilan**, **Kategori** (Bank/E-commerce/Offline), **Penyedia** (mis. `midtrans`), **Metode** (`va`, `qris`, …), **Kode bank** (untuk VA Midtrans: `bni`, `bri`, `bca`, `permata`, `cimb`, atau `mandiri`), **Biaya admin** dan **Biaya ditanggung**, **Masa berlaku instruksi (menit)**, **Lingkungan**, **Instruksi pembayaran**.
2. Klik **Simpan**. Kanal baru berstatus **Nonaktif**.
3. Klik **Aktifkan**. Untuk kanal produksi yang dihitung kuota, sistem menolak bila kuota habis: *"Kuota kanal pembayaran online paket Anda sudah terpakai (x dari y). Nonaktifkan kanal lain atau tingkatkan paket."*
4. Kanal online yang **Terhubung: Belum** tidak ditawarkan ke mahasiswa walaupun aktif.

Tombol lain: **Pemeliharaan** (sementara tidak ditawarkan, tetap dihitung kuota), **Nonaktifkan**, **Ubah**. Penimpaan kuota hanya dapat dilakukan Super Admin platform.

### 4.2 Parameter kebijakan

Kartu **Parameter kebijakan** menampilkan setiap pengaturan dengan keterangannya. Ubah nilai lalu klik **Simpan perubahan**. Yang paling penting sebelum operasional:

| Pengaturan | Isi |
|---|---|
| **Rekening penerimaan kampus untuk transfer manual** (`keu.bank_accounts`) | **Wajib diisi** sebelum mahasiswa memakai transfer manual. Format: `[{"bank":"BNI","account_number":"1234567890","account_name":"Universitas X"}]` |
| **Masa tenggang setelah jatuh tempo** | Hari sebelum sisa tagihan dihitung tunggakan (bawaan 3) |
| **Mode tarif** | `cohort` (tarif terkunci angkatan) atau `current` (tabel tahun berjalan) |
| **Tambahkan kode unik 3 digit pada transfer manual** | Memudahkan pencocokan mutasi |
| **Batas nominal tunai per transaksi** | 0 = tanpa batas |
| **Terbitkan tagihan SKS otomatis saat KRS disetujui** | Ya/Tidak |
| **Batas dispensasi petugas / maksimal** | Hari (bawaan 7 / 30) |
| **Jadwal pengingat jatuh tempo** | Hari relatif terhadap jatuh tempo, mis. `[-7,-3,-1,1,7]` |

Perubahan tercatat di audit log. Isian JSON yang salah format ditolak dengan pesan *"Format JSON … tidak valid."*
