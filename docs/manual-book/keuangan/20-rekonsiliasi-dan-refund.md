# 20. Rekonsiliasi dan Pengembalian Dana

[← Cekal Keuangan](19-cekal-keuangan.md) · [Daftar isi](README.md) · [Berikutnya: Laporan & Ekspor →](21-laporan-dan-ekspor.md)

## 1. Rekonsiliasi

| | |
|---|---|
| **Tujuan** | Memastikan setiap transaksi di sistem benar-benar ada di rekening/laporan penyedia, dan setiap dana masuk di rekening tercatat di sistem. |
| **Siapa** | Lihat: `reconciliations.read`; buat & impor: `reconciliations.create/update`; tutup batch: `reconciliations.finalize`. |
| **Lokasi** | **Keuangan → Rekonsiliasi**. |
| **Data disiapkan** | Ekspor mutasi rekening penerimaan kampus dalam **CSV** dengan kolom: tanggal, keterangan, nominal, referensi. |

### 1.1 Membuat batch & mengimpor mutasi

1. **Batch baru** → pilih **Sumber**: *Mutasi rekening bank*, *Laporan settlement penyedia* (pilih **Kanal**), atau *Setoran kas loket*; isi **Tanggal mutasi** (tidak boleh di masa depan). Halaman batch terbuka.
2. **Impor mutasi** → pilih berkas CSV. Sistem membaca berkas **di peramban** dan menampilkan pratinjau hingga 50 baris pertama.
   - Pemisah kolom koma atau titik koma; baris judul diabaikan.
   - Tanggal: `YYYY-MM-DD` atau `DD/MM/YYYY`.
   - Nominal format Indonesia (`1.400.000,00`) maupun internasional didukung.
   - Untuk *Setoran kas loket*, kolom referensi diisi **nomor referensi setoran** yang dicatat kasir.
3. Opsional centang **Tandai transaksi sistem pada tanggal ini yang tidak ada di mutasi**.
4. Klik **Impor n baris** (maksimal 5.000 baris). Pesan *"Impor selesai: x cocok, y pengecualian."*

### 1.2 Cara pencocokan

| Langkah | Kriteria |
|---|---|
| 1 | **Referensi** sama dengan nomor transaksi (`TRX-…`), nomor kuitansi, atau referensi penyedia |
| 2 | Bila tanpa referensi: transaksi **transfer/online berhasil** yang belum direkonsiliasi dengan **nominal sama** dan tanggal bayar ±1 hari |
| 3 | Setoran kas: total transaksi tunai berhasil pada sesi kas dengan referensi setoran tersebut |

**Hasil per baris:** **Cocok**, **Nominal berbeda**, **Tidak teridentifikasi** (tidak ada transaksi yang cocok), **Duplikat** (transaksi sudah dicocokkan sebelumnya), **Tidak ada di sistem** (transaksi ditemukan tetapi belum berhasil), **Tidak ada di mutasi**. Transaksi yang cocok berubah status rekonsiliasinya menjadi **Cocok**; yang bermasalah menjadi **Pengecualian**.

### 1.3 Menyelesaikan pengecualian

Klik **Selesaikan** pada baris bermasalah, pilih **Penyelesaian**, isi **Catatan**:

| Penyelesaian | Kapan |
|---|---|
| **Dicocokkan manual dengan transaksi** | Transaksinya ada tetapi referensi berbeda — isi **ID transaksi sistem** |
| **Diklaim & dialokasikan ke mahasiswa** | Dana **tidak teridentifikasi** milik mahasiswa tertentu — pilih mahasiswa; dana dicatat sebagai pembayaran mahasiswa itu dan masuk **deposit** (lalu dapat dipakai untuk tagihan) |
| **Dikembalikan ke pengirim** | Dana salah kirim dikembalikan |
| **Dieskalasi ke bank/penyedia** | Perlu klarifikasi pihak luar |
| **Selisih diterima dengan alasan** | Selisih kecil yang diterima |
| **Diabaikan dengan alasan** | Bukan pembayaran mahasiswa (mis. setoran internal) |

Dana tak teridentifikasi **tidak pernah** masuk ke mahasiswa mana pun sebelum diklaim secara eksplisit.

### 1.4 Menutup batch

Klik **Tutup batch** (`reconciliations.finalize`). Ditolak bila masih ada pengecualian: *"Masih ada n baris pengecualian yang belum diselesaikan (BR-KEU-55)."* Petugas yang mencocokkan manual tidak dapat menutup batch yang sama. Batch tertutup tidak dapat diubah.

## 2. Pengembalian dana (refund)

| | |
|---|---|
| **Tujuan** | Mengembalikan saldo deposit mahasiswa ke rekening bank. |
| **Siapa** | Ajukan: mahasiswa (portal) atau petugas `refunds.create`. Setujui/tolak: `refunds.approve` (Kepala Bagian Keuangan, Wakil Rektor), bukan pengaju. Catat transfer/gagal: `refunds.finalize`. |
| **Lokasi** | **Keuangan → Deposit & Refund** → tab **Pengembalian dana**; portal → **Deposit & refund** → **Ajukan pengembalian dana**. |
| **Data disiapkan** | Bank, nomor rekening (6–20 digit), nama pemilik rekening; surat kuasa bila rekening atas nama pihak lain. |

**Prinsip:** refund **selalu diambil dari saldo deposit**. Kelebihan bayar, pembayaran ganda, atau tagihan yang dikoreksi lebih dulu menjadi deposit (otomatis), lalu baru dapat direfund.

**Langkah**

1. **Ajukan refund** (petugas: pilih mahasiswa) → isi **Nominal**, **Alasan** (Kelebihan bayar, Pembayaran ganda, Tagihan dibatalkan/dikoreksi, Mengundurkan diri, Cuti akademik, Mahasiswa meninggal dunia (ke ahli waris), Sisa saldo setelah lulus/keluar, Lainnya), **Penjelasan**, data rekening dan **Hubungan pemilik rekening** (mahasiswa sendiri, orang tua/wali, atau pihak lain — wajib centang **Surat kuasa sudah diterima & diperiksa**; pilihan pihak lain hanya untuk petugas).
2. Saldo sebesar nominal **ditahan** (saldo tersedia berkurang). Status **Menunggu persetujuan**.
3. Penyetuju klik **Setujui** atau **Tolak** (alasan wajib; dana yang ditahan dilepas).
4. Petugas melakukan transfer **di luar sistem** (internet banking kampus), lalu klik **Catat transfer**, isi **Nomor referensi transfer** dan unggah **Bukti transfer** (opsional). Status **Selesai**; saldo berkurang.
5. Bila transfer gagal (rekening tidak valid), klik **Gagal** + alasan; dana kembali ke saldo tersedia.

Pengajuan yang belum diputuskan dapat **Dibatalkan** (dana dilepas). Mahasiswa menerima notifikasi setiap perubahan status.

**Validasi:** nominal ≤ saldo tersedia (*"Saldo deposit tersedia tidak mencukupi (…)."*); rekening pihak lain tanpa surat kuasa ditolak (*"Rekening atas nama pihak lain memerlukan surat kuasa (BR-KEU-59)."*).

**Kerahasiaan rekening:** nomor rekening tampil tersamar. Penyetuju/pelaksana dapat menekan ikon mata untuk melihat nomor lengkap; tindakan ini tercatat di audit log.

> **Batasan:** transfer refund otomatis melalui API bank/penyedia **belum tersedia**; transfer dilakukan manual lalu dicatat.
