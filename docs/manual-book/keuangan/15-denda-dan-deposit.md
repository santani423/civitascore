# 15. Denda dan Deposit

[← Beasiswa, Potongan & Voucher](14-beasiswa-potongan-voucher.md) · [Daftar isi](README.md) · [Berikutnya: UKT →](16-ukt.md)

## 1. Denda keterlambatan

**Menu:** **Keuangan → Denda** (tab **Denda berjalan** dan **Aturan denda**).

### 1.1 Aturan denda

| | |
|---|---|
| **Tujuan** | Menetapkan rumus denda sesuai SK kampus. Tanpa aturan aktif, **tidak ada denda** yang dihitung. |
| **Siapa** | Buat/ubah/ajukan: `penalties.create/update`. Setujui & nonaktifkan: `penalties.approve` — bukan penyusun/pengaju yang sama. |
| **Data disiapkan** | SK denda: besaran, satuan (per hari/bulan/sekali), tenggang, batas maksimal. |

**Langkah**

1. Tab **Aturan denda** → **Aturan**. Isi **Kode**, **Nama**, **Berlaku untuk kelompok tagihan**, **Tenggang (hari)**, **Cara hitung** (Nominal Tetap / Persentase), **Satuan periode** (Sekali, Per Hari, Per Bulan), **Persentase per periode** atau **Nominal per periode**, opsional **Batas maksimal (Rp)**, **Batas maksimal (% pokok)**, **Jumlah periode maksimal**, **Berlaku mulai/sampai**, **Dasar (nomor SK)**. Klik **Simpan** (status **Draf**).
2. Klik **Ajukan**. Penyetuju klik **Setujui** → **Aktif**.
3. Untuk menghentikan, penyetuju klik **Nonaktifkan** (denda berjalan tidak bertambah lagi).

Aturan yang sudah diajukan/aktif tidak dapat diubah; nonaktifkan lalu buat aturan baru.

### 1.2 Cara denda dihitung

- Denda dihitung **per tagihan atau per termin** yang terlambat, sejak jatuh tempo + tenggang aturan.
- **Tidak ada denda atas denda** (tagihan kelompok Denda dikecualikan).
- Contoh persentase per hari: pokok Rp1.000.000, 0,1% per hari, tenggang 3 hari, jatuh tempo 13 hari lalu → terlambat 10 hari → denda Rp10.000.
- Batas maksimal (rupiah atau % pokok) membatasi total denda.

### 1.3 Denda berjalan dan penagihan denda

- Pekerjaan harian (00:30) memperbarui **Denda berjalan**; petugas `penalties.update` dapat menekan **Hitung sekarang**.
- Saat pokok **lunas**, denda dihitung sampai **tanggal bayar efektif** (untuk transfer manual = tanggal transfer di bukti) lalu otomatis **ditagihkan** sebagai **tagihan Denda** terpisah yang tertaut ke tagihan pokok (jatuh tempo = *Jatuh tempo tagihan denda*, bawaan 7 hari). Mahasiswa diberi tahu.
- Petugas dapat menagihkan lebih awal dengan **Tagihkan** (mis. akhir semester).
- Denda berjalan dapat dihapus oleh penyetuju dengan **Hapus** + alasan. Denda yang **sudah ditagihkan** dihapus lewat penyesuaian **Pembebasan Denda** pada tagihan dendanya ([bab 11 §6](11-tagihan.md#6-penyesuaian-tagihan)).

Kolom tab **Denda berjalan**: tagihan pokok (dan termin), aturan, terlambat sejak, jumlah hari, denda, status (Berjalan, Ditagihkan beserta nomor tagihan denda, Dihapus, Berhenti).

## 2. Deposit (saldo titipan)

| | |
|---|---|
| **Tujuan** | Menampung kelebihan dana mahasiswa agar tidak hilang dan dapat dipakai/dikembalikan. |
| **Siapa** | Lihat: `student_deposits.read`; pakai untuk tagihan: `student_deposits.update`; mahasiswa: portal. |
| **Lokasi** | **Keuangan → Deposit & Refund** (tab **Saldo deposit**); profil keuangan mahasiswa → tab **Mutasi deposit**; portal → **Deposit & refund**. |

**Sumber saldo**

| Jenis mutasi | Terjadi saat |
|---|---|
| Kelebihan Bayar | Pembayaran melebihi sisa tagihan/termin |
| Titipan | Kasir mencatat tunai dengan target *Titipan* atau dana tak teridentifikasi diklaim ke mahasiswa |
| Kredit Penyesuaian | Pengurangan tagihan yang sudah dibayar |
| Dipakai untuk Tagihan | Saldo dipakai membayar tagihan |
| Ditahan untuk Refund / Penahanan Dilepas / Dikembalikan | Alur pengembalian dana ([bab 20](20-rekonsiliasi-dan-refund.md#2-pengembalian-dana-refund)) |
| Pembalikan | Transaksi asal dibalik |

**Saldo tersedia** = saldo − dana ditahan untuk refund. Saldo tersedia **tidak pernah negatif**; pemakaian melebihi saldo ditolak: *"Saldo deposit tersedia tidak mencukupi (…)."*

**Memakai saldo:** lihat [bab 12 §5](12-pembayaran.md#5-membayar-dengan-saldo-deposit).

Daftar **Saldo deposit** menampilkan mahasiswa bersaldo (centang filter **Saldo nol → Tampilkan** untuk semua), saldo, ditahan, tersedia, dan waktu mutasi terakhir.
