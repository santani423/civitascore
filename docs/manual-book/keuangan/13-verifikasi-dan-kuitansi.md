# 13. Verifikasi Pembayaran dan Kuitansi

[← Pembayaran](12-pembayaran.md) · [Daftar isi](README.md) · [Berikutnya: Beasiswa, Potongan & Voucher →](14-beasiswa-potongan-voucher.md)

## 1. Verifikasi bukti transfer manual

| | |
|---|---|
| **Tujuan** | Memastikan dana transfer benar-benar masuk sebelum dicatat sebagai pembayaran. |
| **Siapa** | `payment_transactions.approve` (Bagian Keuangan, Kepala Bagian Keuangan, atau peran verifikator buatan kampus). Mahasiswa pengunggah tidak dapat memverifikasi buktinya sendiri. |
| **Lokasi** | **Keuangan → Verifikasi Transfer** (antrean urut dari yang paling lama). |
| **Data disiapkan** | Akses mutasi rekening kampus (internet banking) untuk mencocokkan. |

**Kolom antrean:** transaksi & waktu kirim, mahasiswa, **Instruksi** (nominal yang diminta + kode unik), **Bukti** (nominal di bukti, bank, nama pengirim, tanggal transfer), **Catatan** (label **Bukti ganda?** dan **Nominal beda**), aksi. Filter di kanan atas: **Menunggu verifikasi**, **Disetujui**, **Ditolak** (dua yang terakhir menampilkan pemeriksa & waktu).

**Langkah menyetujui**

1. Klik ikon berkas untuk **mengunduh bukti** dan periksa.
2. Cocokkan dengan mutasi rekening: nominal, tanggal, nama pengirim.
3. Klik **Setujui**. Bila dana yang masuk berbeda dari bukti, isi **Nominal aktual diterima** — kekurangan tetap menjadi sisa tagihan, kelebihan masuk deposit. Isi **Catatan** bila perlu. Klik **Simpan**.
4. Hasil: transaksi **Berhasil**, kuitansi terbit, tagihan diperbarui, mahasiswa diberi tahu.

**Langkah menolak**

1. Klik **Tolak**, pilih **Alasan**: *Tidak ditemukan di mutasi rekening*, *Nominal tidak sesuai*, *Bukti tidak terbaca*, *Bukti ganda*, *Rekening tujuan salah*, *Lainnya*; isi **Keterangan untuk mahasiswa**.
2. Hasil: transaksi **Gagal**, mahasiswa menerima notifikasi *"Bukti transfer … ditolak: <alasan>. Silakan unggah ulang."*

**Kondisi khusus**

- Bukti yang sudah diputuskan tidak dapat diputuskan lagi: *"Bukti ini sudah diputuskan."* (mencegah dua verifikator memproses bersamaan).
- Persetujuan dan pencatatan pembayaran terjadi dalam satu langkah: bila pencatatan gagal, bukti tetap di antrean.
- Bukti yang menunggu verifikasi dapat dianggap memenuhi syarat cekal selama X jam bila pengaturan *Bukti transfer menunggu verifikasi dianggap memenuhi cekal* diisi (bawaan 0 = tidak).

## 2. Kuitansi

| | |
|---|---|
| **Tujuan** | Bukti resmi penerimaan pembayaran. |
| **Siapa** | Petugas `payment_transactions.read`; mahasiswa untuk transaksinya sendiri. |
| **Lokasi** | Petugas: **Transaksi Pembayaran** → detail → **Kuitansi**; **Loket Kasir** → **Cetak kuitansi** setelah mencatat tunai. Mahasiswa: portal **Keuangan → Riwayat pembayaran → Kuitansi**. |

Kuitansi terbit **hanya untuk transaksi berhasil**, bernomor unik `KWT-<tahun><bulan>-<urut>`, dan dibuka sebagai PDF di tab baru (izinkan pop-up). Mahasiswa yang mencoba membuka kuitansi milik mahasiswa lain menerima *"Data tidak ditemukan."*

## 3. Membalik transaksi

| | |
|---|---|
| **Tujuan** | Membatalkan transaksi yang sudah tercatat berhasil karena kesalahan (salah mahasiswa, uang palsu, transfer ternyata tidak masuk). |
| **Siapa** | `payment_transactions.approve`, **bukan** pencatat (kasir) maupun verifikator transaksi tersebut. |
| **Lokasi** | **Transaksi Pembayaran** → detail transaksi → **Balik transaksi**. |

1. Klik **Balik transaksi**, isi **Alasan** (minimal 10 karakter), klik **Balik transaksi**.
2. Hasil: alokasi dibatalkan (tercatat *Dibalik* pada tagihan), tagihan dihitung ulang, kelebihan yang sempat masuk deposit ditarik kembali, transaksi berstatus **Dibalik**, mahasiswa menerima notifikasi pembatalan. Untuk transaksi tunai, nilai yang dibalik mengurangi kas yang seharusnya ada saat sesi ditutup.

**Kondisi khusus**

- Hanya transaksi **Berhasil** yang dapat dibalik.
- Bila kelebihan bayar dari transaksi itu **sudah dipakai** dari deposit (mis. untuk tagihan lain), pembalikan ditolak karena saldo deposit tidak cukup — balik dulu pemakaian deposit tersebut atau tangani lewat penyesuaian.
- Pembalikan tercatat di audit log.

## 4. Halaman Transaksi Pembayaran

**Lokasi:** **Keuangan → Transaksi Pembayaran**.

Filter: **Cari** (nomor transaksi, kuitansi, referensi penyedia, NIM/nama), **Status**, **Metode** (Online, Transfer Manual, Tunai, Deposit), **Rekonsiliasi**, **Perlu resolusi**, **Dibayar dari/sampai** (tanggal bayar efektif).

Detail transaksi menampilkan mahasiswa, penyetor, metode/kanal, nominal & biaya kanal, status, nomor kuitansi, waktu bayar efektif, status rekonsiliasi, target (tagihan/termin/titipan), kode unik, nomor VA, pencatat, verifikator, alasan gagal/pembalikan, **Bukti transfer** (dengan tombol unduh dan peringatan bukti ganda), dan **Alokasi ke tagihan**. Tombol: **Kuitansi**, **Cek status ke penyedia** (online, pending), **Batalkan** (belum dibayar), **Balik transaksi** (berhasil).

Label **Perlu resolusi** berarti dana diterima setelah transaksi kedaluwarsa/dibatalkan atau tidak dapat dialokasikan ke tagihan (mis. tagihan sudah lunas lewat kanal lain) — dana tersebut sudah masuk deposit mahasiswa; tindak lanjuti dengan mahasiswa (pakai untuk tagihan lain atau refund).
