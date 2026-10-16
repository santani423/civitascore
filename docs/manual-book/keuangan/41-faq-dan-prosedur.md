# 41. FAQ dan Prosedur Operasional

[← Masalah & Pesan Kesalahan](40-masalah-dan-pesan-kesalahan.md) · [Daftar isi](README.md) · [Berikutnya: Batasan & Integrasi →](42-batasan-dan-integrasi.md)

## 1. FAQ

**Mengapa tagihan tidak bisa diedit langsung?**
Agar setiap perubahan kewajiban punya alasan, penyetuju, dan jejak audit. Gunakan penyesuaian ([bab 11 §6](11-tagihan.md#6-penyesuaian-tagihan)).

**Apakah penerbitan massal bisa menagih mahasiswa dua kali?**
Tidak. Kombinasi mahasiswa + semester + komponen + kelompok hanya dapat ditagih sekali; simulasi berikutnya menandainya *Sudah ditagihkan*.

**Tarif naik di tengah tahun; apakah tagihan lama ikut berubah?**
Tidak. Tagihan menyimpan tarif yang dipakai saat terbit. Tarif baru berlaku untuk tagihan berikutnya.

**Mahasiswa membayar lebih. Ke mana kelebihannya?**
Otomatis masuk **deposit**; dapat dipakai untuk tagihan berikutnya atau dikembalikan lewat refund.

**Mahasiswa membayar dua kali untuk tagihan yang sama.**
Pembayaran kedua tidak teralokasi (tagihan sudah lunas) → masuk deposit dan transaksinya ditandai **Perlu resolusi**. Tawarkan pemakaian deposit atau ajukan refund.

**Denda dihitung sampai kapan untuk transfer yang diverifikasi terlambat?**
Sampai **tanggal transfer pada bukti**, bukan tanggal verifikasi.

**Mahasiswa sudah bayar tetapi masih tidak bisa KRS.**
Pastikan pembayaran sudah **Berhasil** (bukan *Menunggu verifikasi*) dan tagihan yang disyaratkan aturan cekal sudah memenuhi syarat. Cekal dievaluasi ulang otomatis setiap kali pembayaran tercatat; petugas dapat menekan **Evaluasi semua**.

**Bisakah mahasiswa KRS sebelum lunas?**
Bisa bila aturan cekal memakai syarat **Termin Pertama Lunas**/**Kewajiban Jatuh Tempo Lunas**, atau dengan **dispensasi**.

**Apakah pembayaran online (VA/QRIS) sudah bisa dipakai?**
Hanya bila kampus sudah memiliki akun penyedia pembayaran dan administrator sistem memasang kredensialnya. Lihat **Pengaturan Keuangan → Kanal pembayaran**: kolom **Terhubung** harus *Ya*.

**Berapa kanal pembayaran online yang boleh aktif?**
Sesuai paket langganan: Growth 3, Business 3, Professional 4, Premium 5, Enterprise 6. Tunai, transfer manual, dan kanal sandbox tidak dihitung.

**Bagaimana mengembalikan uang mahasiswa yang mengundurkan diri?**
Batalkan/kurangi tagihan yang belum digunakan lewat penyesuaian (pembayaran yang sudah ada menjadi deposit), lalu ajukan refund dari deposit ([bab 20 §2](20-rekonsiliasi-dan-refund.md#2-pengembalian-dana-refund)).

**Siapa yang menyetujui bila Kepala Bagian Keuangan sendiri yang mengajukan?**
Pejabat lain berizin persetujuan (mis. Wakil Rektor). Sistem tidak mengizinkan menyetujui pengajuan sendiri.

## 2. Prosedur operasional (SOP) yang disarankan

### 2.1 Awal tahun akademik

1. Periksa **komponen biaya**.
2. **Salin** tabel tarif tahun lalu jadi versi baru (isi kenaikan %), sesuaikan baris, **Pratinjau tagihan** beberapa mahasiswa contoh, **Ajukan persetujuan**.
3. Penyetuju memeriksa & **Setujui & berlakukan**.
4. Perbarui **skema cicilan**, **aturan denda**, **aturan cekal** (jalankan **Simulasi**), **jenis beasiswa/potongan**, **rekening penerimaan**.

### 2.2 Awal semester

1. Pastikan penetapan **UKT** dan **beasiswa** semester ini sudah disetujui.
2. **Penerbitan Massal** → simulasi per kelompok (mis. per fakultas) → periksa *Dilewati* → ajukan → disetujui.
3. Aktifkan aturan cekal KRS sebelum masa KRS dibuka.

### 2.3 Harian

1. Kasir: buka sesi → layani → tutup sesi → catat setoran.
2. Verifikator: kosongkan antrean **Verifikasi Transfer** (target: hari yang sama).
3. Kepala Bagian: putuskan item **Menunggu persetujuan**.
4. Pantau antrean kerja di Dashboard (online pending > 24 jam, pengecualian rekonsiliasi, refund).

### 2.4 Mingguan

1. **Rekonsiliasi** mutasi rekening per hari; selesaikan pengecualian; tutup batch.
2. Verifikasi setoran kasir minggu berjalan.
3. Penagihan: **Prioritas penagihan** / laporan **Tunggakan** → hubungi → **Catatan penagihan**.

### 2.5 Akhir semester

1. **Tagihkan** denda berjalan yang masih tersisa (opsional, sesuai kebijakan).
2. Ekspor laporan **Piutang per Mahasiswa**, **Umur Piutang**, **Penerimaan**, **Beasiswa & Potongan** (dengan alasan ekspor).
3. Tindak lanjuti saldo deposit mahasiswa yang lulus/keluar (refund *Sisa saldo setelah lulus/keluar*).
