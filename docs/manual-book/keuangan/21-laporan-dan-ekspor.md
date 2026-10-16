# 21. Laporan dan Ekspor Data

[← Rekonsiliasi & Refund](20-rekonsiliasi-dan-refund.md) · [Daftar isi](README.md) · [Berikutnya: Panduan per Peran →](30-panduan-per-peran.md)

| | |
|---|---|
| **Tujuan** | Menyajikan data keuangan untuk pengendalian, pimpinan, dan audit. |
| **Siapa** | Lihat: `finance_reports.read` (peran keuangan, pimpinan, auditor, admin universitas). Ekspor: `finance_reports.export`. |
| **Lokasi** | **Keuangan → Laporan Keuangan**. |

## 1. Jenis laporan

| Laporan | Isi utama |
|---|---|
| **Laporan Tagihan** | Tagihan per mahasiswa: bruto, potongan, neto, dibayar, sisa, status |
| **Laporan Penerimaan (Pembayaran)** | Uang masuk per transaksi berhasil |
| **Laporan Piutang per Mahasiswa** | Jumlah tagihan terbuka, piutang, tunggakan, jatuh tempo tertua per mahasiswa |
| **Laporan Tunggakan** | Tagihan menunggak |
| **Umur Piutang (Aging)** | Piutang per kelompok umur |
| **Laporan Beasiswa & Potongan** | Potongan yang diberikan |
| **Laporan Denda** | Denda berjalan & ditagihkan |
| **Laporan Saldo Deposit** | Saldo, ditahan, tersedia per mahasiswa |
| **Laporan Cicilan** | Jadwal & status termin |
| **Laporan Transaksi (Manual, Tunai, Online)** | Semua transaksi dengan status |
| **Laporan Rekonsiliasi** | Batch & pengecualian |
| **Laporan Pengembalian Dana** | Refund & statusnya |
| **Laporan Aktivitas Petugas** | Jejak aksi petugas pada data keuangan (dari log audit: pembuatan, persetujuan, penolakan, pembalikan, dll.) beserta jumlah per petugas |

## 2. Menampilkan laporan

1. Pilih **Jenis laporan**.
2. Atur filter: **Semester**, **Fakultas**, **Prodi**, **Angkatan**, **Kelompok**, **Metode bayar**, **Dari/Sampai** (tanggal bayar untuk laporan transaksi/penerimaan), **NIM/nama**. Klik **Terapkan**.
3. Kartu ringkasan menampilkan total sesuai jenis laporan (mis. **Total piutang**, **Total tunggakan**, **Per metode**) dan jumlah baris. Tabel menampilkan **maksimal 500 baris**; bila lebih, muncul keterangan *"Menampilkan 500 dari n baris. Ekspor CSV untuk data lengkap."*

Angka laporan dihitung langsung dari data tagihan & transaksi yang sama dengan Dashboard dan profil mahasiswa, sehingga konsisten (diuji otomatis). Tagihan berstatus Draf dan Dibatalkan tidak dihitung sebagai piutang.

## 3. Ekspor CSV

1. Klik **Ekspor CSV**.
2. Isi **Alasan** ekspor (minimal 10 karakter), klik **Unduh CSV**.
3. Berkas `<jenis>.csv` (UTF-8, terbaca di Excel) berisi **seluruh** baris sesuai filter yang sedang diterapkan.

Daftar **Tagihan** juga memiliki tombol **Ekspor CSV** (`invoices.export`) dengan filter status & semester.

**Aturan:** setiap ekspor tercatat di audit log beserta pengekspor, filter, jumlah baris, dan alasan. Isi sel yang diawali `=`, `+`, `-`, `@` dinetralkan agar tidak dieksekusi sebagai rumus spreadsheet.

> **Batasan:** ekspor tersedia dalam **CSV**; format XLSX/PDF dan laporan PDDikti otomatis belum tersedia.
