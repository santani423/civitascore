# 40. Penanganan Masalah Umum dan Pesan Kesalahan

[← Panduan per Peran](30-panduan-per-peran.md) · [Daftar isi](README.md) · [Berikutnya: FAQ & Prosedur →](41-faq-dan-prosedur.md)

## 1. Pesan umum

| Pesan | Penyebab | Penanganan |
|---|---|---|
| *Anda tidak memiliki izin untuk mengakses resource ini.* | Peran akun tidak memuat izin yang dibutuhkan | Minta administrator menambah izin/peran ([bab 2](02-akses-dan-peran.md)) |
| *Data tidak ditemukan.* | Data sudah dihapus, milik kampus lain, atau (mahasiswa) milik mahasiswa lain | Periksa kembali tautan/nomor |
| *Validasi data gagal.* + pesan di bawah isian | Isian kosong/format salah | Perbaiki isian yang ditandai |
| *Terlalu banyak permintaan. Coba lagi nanti.* | Batas frekuensi (mis. pembuatan pembayaran 20/menit, voucher 10/menit) | Tunggu sebentar |
| *Akun ini tidak terhubung dengan data mahasiswa.* | Akun portal tidak tertaut ke data mahasiswa SIAKAD | Hubungi BAAK |
| *… tidak dapat menyetujui … sendiri (pemisahan tugas).* | Penyetuju = pembuat/pengaju | Minta petugas lain menyetujui |

## 2. Tarif & penerbitan

| Pesan | Penanganan |
|---|---|
| *Tabel tarif yang sudah diajukan/berlaku tidak dapat diubah; buat versi baru (BR-KEU-01).* | **Salin jadi versi baru**, ubah, ajukan |
| *Terdapat n pasangan baris tarif yang bertabrakan …* | Perbaiki rentang angkatan/semester atau hapus baris ganda |
| *Tabel tarif belum memiliki baris tarif.* | Tambahkan baris sebelum mengajukan |
| *Komponen sudah dipakai tarif/tagihan; nonaktifkan saja.* | Nonaktifkan komponen |
| *Jatuh tempo tidak boleh sebelum hari ini (BR-KEU-13).* | Pilih tanggal hari ini atau setelahnya |
| *Komponen biaya tidak ditemukan atau nonaktif.* | Aktifkan komponen |
| *Tidak ada tagihan yang siap diterbitkan pada simulasi ini.* | Periksa **Alasan dilewati** ([bab 11 §2](11-tagihan.md#2-penerbitan-tagihan-massal)) |
| *Penerbitan ini sedang/sudah diproses penyetuju lain.* | Muat ulang halaman; tidak perlu tindakan |
| Mahasiswa banyak *Dilewati: Belum ada tabel tarif berlaku* | Tabel tarif belum disetujui, atau tahun akademik tabel tidak sesuai |

## 3. Tagihan & penyesuaian

| Pesan | Penanganan |
|---|---|
| *Masih ada penyesuaian lain yang menunggu persetujuan untuk tagihan ini.* | Putuskan/batalkan penyesuaian yang menunggu |
| *Tagihan yang sudah menerima pembayaran atau memiliki transaksi berjalan tidak dapat dibatalkan; gunakan penyesuaian turun (BR-KEU-12).* | Ajukan **Pengurangan**/**Pembebasan**; kelebihan bayar otomatis ke deposit |
| *Pengurangan melebihi nilai tagihan saat ini.* | Kurangi nominal |
| *Pembebasan denda hanya untuk tagihan denda.* | Gunakan **Pengurangan**/**Pembebasan** untuk tagihan biasa |
| *Tagihan berstatus … tidak dapat disesuaikan.* | Draf: hapus/terbitkan; Dibatalkan/Dibebaskan: tidak dapat diubah |

## 4. Pembayaran

| Pesan | Penanganan |
|---|---|
| *Pembayaran tunai hanya dapat dicatat dalam sesi kas terbuka milik Anda.* | **Buka sesi kas** terlebih dahulu |
| *Anda masih memiliki sesi kas yang terbuka. Tutup sesi tersebut terlebih dahulu.* | Tutup sesi lama |
| *Nominal melebihi batas pembayaran tunai per transaksi (…).* | Bagi pembayaran atau ubah batas di pengaturan |
| *Tagihan tidak ditemukan.* / *Termin cicilan tidak ditemukan.* | Tagihan bukan milik mahasiswa terpilih |
| *Tagihan ini tidak memiliki sisa kewajiban.* / *Termin ini sudah lunas.* | Pilih tagihan/termin lain atau **Titipan** |
| *Bukti hanya dapat diunggah untuk transfer manual yang menunggu atau ditolak.* | Buat instruksi transfer baru |
| *Berkas tidak valid atau tidak dapat dilampirkan.* | Unggah ulang dari akun sendiri; format JPG/PNG/PDF |
| *Ukuran berkas maksimal 2 MB.* | Perkecil berkas |
| *Bukti ini sudah diputuskan.* | Bukti sudah diproses petugas lain; muat ulang antrean |
| *Kanal pembayaran ini belum aktif atau belum terhubung ke penyedia. Gunakan kanal lain atau transfer manual.* | Pembayaran online belum dikonfigurasi ([bab 42](42-batasan-dan-integrasi.md)) |
| *Header Idempotency-Key wajib diisi.* / *Idempotency-Key sudah dipakai untuk permintaan berbeda.* | Muat ulang halaman lalu ulangi (pesan teknis dari aplikasi) |
| *Hanya transaksi berhasil yang dapat dibalik.* / *Hanya transaksi yang belum dibayar yang dapat dibatalkan.* | Pilih aksi sesuai status |
| *Pencatat atau verifikator transaksi tidak dapat membalik transaksi yang sama (pemisahan tugas).* | Minta petugas lain |
| *Saldo deposit kosong atau tagihan sudah lunas.* / *Saldo deposit tersedia tidak mencukupi (…).* | Periksa saldo tersedia (saldo − ditahan) |
| Mahasiswa sudah transfer tetapi status belum berubah | Pastikan bukti sudah diunggah (status *Menunggu verifikasi*); verifikator memproses antrean |
| Transfer nominal tidak sesuai instruksi | Verifikator mengisi **Nominal aktual diterima** saat menyetujui; kekurangan menjadi sisa, kelebihan ke deposit |
| Tidak ada rekening tampil di instruksi transfer | Isi **Rekening penerimaan kampus** di Pengaturan Keuangan |

## 5. Beasiswa, voucher, denda, cicilan, UKT

| Pesan | Penanganan |
|---|---|
| *Mahasiswa ini sudah memiliki penetapan aktif/menunggu untuk jenis yang sama.* | Cabut/putuskan penetapan lama |
| *Kode voucher tidak dikenal.* / *Kuota voucher sudah habis.* / *Voucher tidak aktif atau di luar masa berlaku.* | Periksa kode, kuota, masa berlaku |
| *Voucher hanya dapat dipakai pada tagihan yang belum dibayar.* | Voucher tidak dapat dipakai setelah ada pembayaran |
| *Batalkan dahulu transaksi pembayaran yang sedang berjalan untuk tagihan ini.* | Batalkan instruksi transfer/online yang belum dibayar |
| *Terlalu banyak percobaan kode voucher yang salah. Coba lagi dalam 30 menit.* | Tunggu 30 menit |
| *Aturan yang sudah diajukan/aktif tidak dapat diubah; nonaktifkan lalu buat aturan baru.* | Buat aturan denda baru |
| *Hanya denda berjalan yang dapat dihapus di sini; …* | Gunakan penyesuaian **Pembebasan Denda** pada tagihan denda |
| *Jumlah persentase termin harus 100%.* / *Cicilan terdiri atas 2 sampai 12 termin.* | Perbaiki termin |
| *Cicilan tidak dapat diajukan karena masih ada tunggakan pada tagihan lain (BR-KEU-30).* | Lunasi tunggakan lain atau gunakan skema tanpa syarat ini |
| *Masih ada pengajuan perubahan UKT yang sedang diproses.* | Tunggu keputusan pengajuan sebelumnya |

## 6. Cekal & rekonsiliasi & refund

| Pesan | Penanganan |
|---|---|
| *Pengisian KRS tidak dapat dilakukan karena terdapat cekal: …* / *Ujian tidak dapat dilakukan karena terdapat cekal: …* | Mahasiswa melunasi kewajiban (cekal dicabut otomatis) atau mengajukan dispensasi |
| Cekal tidak hilang setelah membayar | Bila bukti transfer masih *Menunggu verifikasi*, cekal tetap sampai disetujui. Bila sudah lunas, petugas klik **Evaluasi semua** |
| Cekal muncul lagi setelah dibuka manual | Kewajiban belum dipenuhi; gunakan dispensasi ([bab 19 §5](19-cekal-keuangan.md#5-membuka-cekal-manual)) |
| *Dispensasi maksimal 30 hari.* / *Batas akhir dispensasi harus setelah saat ini.* | Perpendek/perbaiki tanggal |
| *Masih ada n baris pengecualian yang belum diselesaikan (BR-KEU-55).* | Selesaikan semua pengecualian |
| *Alokasi ke mahasiswa hanya untuk dana tak teridentifikasi dan wajib memilih mahasiswa.* | Pilih mahasiswa; hanya untuk baris *Tidak teridentifikasi* |
| Impor CSV: *Tidak ada baris yang terbaca …* | Periksa urutan kolom: tanggal, keterangan, nominal, referensi |
| *Rekening atas nama pihak lain memerlukan surat kuasa (BR-KEU-59).* | Centang konfirmasi surat kuasa setelah memeriksanya |
| *Status pengembalian dana saat ini: …* | Aksi tidak sesuai status refund; muat ulang |
| *Kuota kanal pembayaran online paket Anda sudah terpakai …* | Nonaktifkan kanal lain atau tingkatkan paket |
