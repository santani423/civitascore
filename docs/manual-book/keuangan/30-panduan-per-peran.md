# 30. Panduan Penggunaan Berdasarkan Peran

[← Laporan & Ekspor](21-laporan-dan-ekspor.md) · [Daftar isi](README.md) · [Berikutnya: Masalah & Pesan Kesalahan →](40-masalah-dan-pesan-kesalahan.md)

## 1. Mahasiswa

**Menu:** portal mahasiswa → **Keuangan** (`/portal/keuangan`).

### 1.1 Ringkasan

Bagian atas menampilkan **Sisa tagihan**, **Tunggakan**, **Jatuh tempo berikutnya** (termasuk termin), dan **Saldo deposit**. Di bawahnya:

- pemberitahuan bila ada bukti transfer yang sedang diverifikasi;
- kartu **Cekal keuangan** (bila ada): layanan yang dibatasi, alasan, kekurangan, status dispensasi, tombol **Ajukan dispensasi**;
- status layanan **Pengisian KRS** dan **Ujian** (*dapat diakses* / *diblokir*) dan **Bebas tanggungan keuangan** (Ya/Belum).

Tab: **Tagihan**, **Riwayat pembayaran**, **Deposit & refund**, **Kartu piutang**, **UKT**, **Beasiswa/potongan**.

### 1.2 Membayar tagihan

1. Tab **Tagihan** → klik **Bayar** (atau nomor tagihan).
2. Halaman tagihan menampilkan jumlah, dibayar, sisa, jatuh tempo, rincian, jadwal cicilan, dan pembayaran tercatat.
3. Pada kartu **Bayar tagihan**:
   - bila tagihan dicicil, pilih **Bayar untuk**: seluruh sisa atau termin tertentu;
   - **Pembayaran online**: pilih kanal (hanya tampil bila kampus sudah mengaktifkannya);
   - **Transfer manual ke rekening kampus** → **Buat instruksi transfer** → transfer **tepat** sesuai nominal (termasuk kode unik) → **Unggah bukti transfer** ([bab 12 §3](12-pembayaran.md#3-transfer-manual-dengan-unggah-bukti));
   - **Pakai saldo deposit** (bila ada saldo);
   - **Pakai kode voucher** (tagihan belum dibayar sama sekali);
   - **Ajukan cicilan** (bila ada skema untuk tagihan ini).
4. Status pembayaran tampil di kartu **Pembayaran yang sedang berjalan**: *Menunggu pembayaran*, *Menunggu verifikasi*, atau hilang setelah berhasil. Gunakan **Cek status** untuk pembayaran online, **Batalkan** untuk instruksi yang tidak jadi dipakai.

Bayar tunai: datang ke loket Bagian Keuangan dengan menyebutkan NIM; minta kuitansi.

### 1.3 Riwayat pembayaran & kuitansi

Tab **Riwayat pembayaran**: semua transaksi dengan status. **Kuitansi** membuka PDF kuitansi transaksi berhasil. Bukti yang ditolak menampilkan alasannya; klik **Unggah ulang bukti**.

### 1.4 Deposit & refund

Tab **Deposit & refund**: saldo, ditahan, tersedia, mutasi deposit, dan daftar pengajuan pengembalian dana. Bila saldo tersedia > 0, **Ajukan pengembalian dana** → isi nominal, alasan, penjelasan, rekening (milik sendiri atau orang tua/wali). Dana ditahan sampai diputuskan.

### 1.5 Kartu piutang, UKT, beasiswa

- **Kartu piutang** — seluruh mutasi kewajiban dengan saldo berjalan.
- **UKT** — golongan saat ini, riwayat pengajuan, **Ajukan peninjauan UKT** ([bab 16 §3](16-ukt.md#3-pengajuan-peninjauan-ukt)).
- **Beasiswa/potongan** — penetapan yang aktif/dicabut.

### 1.6 Notifikasi yang diterima mahasiswa

Tagihan baru, pembayaran diterima, bukti ditolak, pembayaran dibatalkan, saldo deposit bertambah, keputusan cicilan, tagihan denda, keputusan UKT, status refund, cekal diterbitkan/dicabut, keputusan dispensasi, dan pengingat jatuh tempo — melalui menu **Notifikasi** portal (saluran lain mengikuti konfigurasi notifikasi kampus).

## 2. Staf Bagian Keuangan

| Kegiatan | Menu | Bab |
|---|---|---|
| Menyusun tarif (draf) & mengajukan | Tarif & Komponen | [10](10-tarif-dan-komponen-biaya.md) |
| Simulasi penerbitan & mengajukan | Penerbitan Massal | [11 §2](11-tagihan.md#2-penerbitan-tagihan-massal) |
| Tagihan manual, terbitkan draf | Tagihan | [11 §3](11-tagihan.md#3-tagihan-manual--insidental) |
| Mengajukan penyesuaian | Tagihan → detail | [11 §6](11-tagihan.md#6-penyesuaian-tagihan) |
| Verifikasi bukti transfer | Verifikasi Transfer | [13](13-verifikasi-dan-kuitansi.md) |
| Membalik transaksi (bukan yang dicatat/diverifikasi sendiri) | Transaksi Pembayaran | [13 §3](13-verifikasi-dan-kuitansi.md#3-membalik-transaksi) |
| Mengajukan penetapan beasiswa, membuat voucher | Beasiswa, Potongan & Voucher | [14](14-beasiswa-potongan-voucher.md) |
| Aturan denda (draf), hitung/tagihkan denda | Denda | [15](15-denda-dan-deposit.md) |
| Penetapan & peninjauan UKT | UKT | [16](16-ukt.md) |
| Cicilan (skema, persetujuan cicilan khusus pengajuan orang lain) | Cicilan | [17](17-cicilan.md) |
| Penagihan & catatan penagihan | Dashboard, profil mahasiswa | [18](18-piutang-dan-tunggakan.md) |
| Aturan cekal, evaluasi, dispensasi ≤ batas petugas | Cekal Keuangan | [19](19-cekal-keuangan.md) |
| Rekonsiliasi & menutup batch | Rekonsiliasi | [20 §1](20-rekonsiliasi-dan-refund.md#1-rekonsiliasi) |
| Mengajukan refund, mencatat transfer refund | Deposit & Refund | [20 §2](20-rekonsiliasi-dan-refund.md#2-pengembalian-dana-refund) |
| Laporan & ekspor | Laporan Keuangan | [21](21-laporan-dan-ekspor.md) |

## 3. Kepala Bagian Keuangan

Semua kegiatan staf, ditambah persetujuan (tidak boleh atas pengajuan sendiri):

- **Setujui & berlakukan** tabel tarif; **Setujui & terbitkan** penerbitan massal;
- **Setujui/Tolak** penyesuaian tagihan, aturan denda, penetapan beasiswa, refund;
- memutuskan dispensasi melebihi batas petugas, **Buka cekal manual**;
- mengubah **Pengaturan Keuangan** (rekening penerimaan, tenggang, kebijakan) dan kanal pembayaran.

Rutinitas harian yang disarankan: buka **Dashboard Keuangan** → periksa **Menunggu persetujuan** dan antrean kerja → putuskan antrean di **Penyesuaian Tagihan**, **Penerbitan Massal**, **Cicilan**, **Deposit & Refund**, **Cekal Keuangan → Dispensasi**.

## 4. Kasir

1. Awal shift: **Loket Kasir → Buka sesi kas**.
2. Melayani pembayaran: cari mahasiswa → pilih tagihan/termin → nominal → **Catat pembayaran** → **Cetak kuitansi** ([bab 12 §2](12-pembayaran.md#2-pembayaran-tunai-di-loket-kasir)).
3. Akhir shift: **Tutup sesi** dengan hasil hitung fisik → setor ke bank → **Catat setoran**.
4. Petugas lain memverifikasi setoran.

Kasir dapat melihat tagihan dan transaksi, tetapi tidak dapat menyesuaikan tagihan, memverifikasi bukti transfer, atau membalik transaksi.

## 5. Pejabat penyetuju (Wakil Rektor)

Menu yang relevan: **Tarif & Komponen**, **Penerbitan Massal**, **Penyesuaian Tagihan**, **Denda**, **Beasiswa, Potongan & Voucher**, **Deposit & Refund**, **Cekal Keuangan → Dispensasi**. Buka item berstatus *Menunggu persetujuan*, periksa detail (rincian simulasi, alasan, dokumen), lalu **Setujui** atau **Tolak** dengan alasan.

## 6. Pimpinan (Rektor, Pemilik/Yayasan)

**Dashboard Keuangan** (filter semester/fakultas/prodi/angkatan) dan **Laporan Keuangan** (Rektor dapat mengekspor). Tidak ada tombol perubahan data.

## 7. BAAK / Administrasi Akademik

**Keuangan → Cekal Keuangan** untuk melihat mahasiswa tercekal dan riwayatnya. Pertanyaan pembukaan cekal/dispensasi diteruskan ke Bagian Keuangan. Status bebas tanggungan dapat dilihat pada profil keuangan mahasiswa (bila memiliki izin `invoices.read`).

## 8. Auditor

Semua halaman KEU dalam mode baca + ekspor. Bukti kontrol: kolom pembuat/penyetuju pada tarif, penerbitan, penyesuaian, refund; riwayat cekal; **Laporan Aktivitas Petugas**; **Pengaturan → Audit Log** (perubahan pengaturan, ekspor, pembukaan rekening, pembalikan).

## 9. Administrator sistem / TI

Pemasangan, perintah `keu:install`, penjadwal, kredensial penyedia: lihat [Catatan Implementasi §11](../../keu/catatan-implementasi.md#11-menjalankan--menguji) dan [bab 42](42-batasan-dan-integrasi.md).
