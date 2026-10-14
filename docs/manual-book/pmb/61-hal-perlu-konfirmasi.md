# 61. Hal yang Perlu Dikonfirmasi & Kendala yang Diketahui

[← Perbedaan Rancangan dan Implementasi](60-perbedaan-rancangan-implementasi.md) · [Daftar isi](README.md) · [Berikutnya: FAQ & Pemecahan Masalah →](62-faq-dan-pemecahan-masalah.md)

## 1. Perlu konfirmasi

### 1.1 Ketergantungan pihak eksternal

| # | Hal | Status di aplikasi | Yang dibutuhkan |
|---|---|---|---|
| E1 | Gateway pembayaran (Midtrans: VA/QRIS) | Adapter & webhook tersedia, diuji dengan respons tiruan. | Kredensial sandbox/produksi dan uji coba di sandbox Midtrans. Tanpa itu hanya **transfer manual** dan **loket** yang tersedia. |
| E2 | SMS/WhatsApp (OTP, notifikasi, broadcast SMS) | Driver `log` (tidak terkirim) dan `http` generik. | Kontrak & spesifikasi API penyedia; aktifkan `pmb.sms.enabled`. |
| E3 | CAPTCHA (Cloudflare Turnstile) | Terintegrasi; default nonaktif. | *Site key* & *secret*. |
| E4 | Antivirus berkas unggahan | Kontrak pemindai tersedia; bawaan belum memindai. | ClamAV atau layanan pemindai. |
| E5 | Integrasi CRM (MauKuliah) | Endpoint penerima *lead* tersedia. | Spesifikasi API CRM untuk sinkronisasi dua arah. |
| E6 | Tanda tangan elektronik (TTE) surat penerimaan | LoA PDF + QR verifikasi publik. | Penyedia TTE tersertifikasi. |
| E7 | CBT eksternal | Webhook HMAC tersedia. | Spesifikasi penyedia. |
| E8 | Versi PHP server | Kode berjalan di PHP 8.3, tetapi `composer.lock` memuat `openspout` yang mensyaratkan PHP ≥ 8.4. | Samakan versi PHP server atau perbarui lock sebelum produksi. |
| E9 | Format mutasi bank untuk rekonsiliasi | Impor CSV/XLSX (tanggal, keterangan, nominal, referensi). | Contoh mutasi dari bank kampus untuk uji impor. |
| E10 | Pelaporan ke PDDikti | Modul PMB mengumpulkan data lanjutan dan menyediakan laporan kelengkapan. | Mekanisme pengiriman ke PDDikti (Feeder/Neo Feeder) berada di luar modul PMB dan perlu dipastikan. |

### 1.2 Kebijakan yang harus ditetapkan kampus

| # | Kebijakan | Default aplikasi | Acuan |
|---|---|---|---|
| K1 | Maksimal pendaftaran aktif per periode | 1 | Q-CFG-05 (rekomendasi 2), F1 |
| K2 | Pejabat pengesah langkah 2 | Peran *Pejabat Pengesah PMB* | Q-ANN-01 (rekomendasi WR I) |
| K3 | Format NIM | `{YY}{PRODI}{SEQ:4}` | Q-REG-01 |
| K4 | Kebijakan refund biaya pendaftaran & biaya awal | Refund melalui persetujuan Kepala Keuangan | Q-PAY-04 |
| K5 | Minimum pembayaran untuk daftar ulang | Cicilan pertama | Q-PAY-05, BR-33 |
| K6 | Batas perbaikan dokumen | 72 jam | Q-FRM-04, BR-24 |
| K7 | Perpanjangan daftar ulang maksimal | 7 hari | Q-REG-04, BR-34 |
| K8 | Promosi cadangan otomatis | Nonaktif (manual) | Q-SEL-05, BR-32 |
| K9 | Status kuota di website | Tidak ditampilkan | Q-CFG-03 |
| K10 | Domain email institusi untuk akun mahasiswa | Kosong (pakai email pribadi) | Q-REG-06, BR-38 |
| K11 | Masa retensi data pendaftar tidak diterima | 2 tahun | Q-PDP-03, BR-41 |
| K12 | Penanggung jawab data pribadi (DPO) & isi Kebijakan Privasi | Harus disediakan kampus | Q-PDP-01 |
| K13 | Persetujuan wali bagi pendaftar < 18 tahun | Jenis persetujuan `guardian_consent` sudah ada di model data, tetapi belum ada isian/alur persetujuan wali di formulir | Q-PDP-02 |

### 1.3 Perilaku aplikasi yang perlu dipastikan sesuai harapan kampus

| # | Perilaku | Keterangan |
|---|---|---|
| P1 | Tagihan biaya pendaftaran terbit saat langkah **Pembayaran** dibuka, termasuk pada jalur *bayar setelah kirim formulir*. | Pendaftar dapat membayar sebelum mengirim formulir; pesan *"dibuat setelah formulir dikirim"* tidak pernah tampil. Kasir hanya dapat mencatat pembayaran setelah tagihan terbit. |
| P2 | Label tahap draf jalur berbayar adalah *Menunggu pembayaran*. | Sesuai urutan evaluasi [09 §3](../../pmb/09-workflow-dan-state-machine.md); label dapat diganti lewat `pmb.status_labels`. |
| P3 | Galeri pas foto menampilkan foto dari draf dan dari pendaftaran yang ditolak. | Verifikator perlu memilih foto dengan cermat. |
| P4 | Mode kuota *Batas per gelombang* tanpa isian batas per gelombang di layar. | Gunakan *Kuota periode*. |
| P5 | Tipe konten *Template hasil* dan *Brosur* belum dipakai di portal/website. | — |
| P6 | Field tambahan bertahap *Daftar ulang* belum tampil di halaman daftar ulang portal. | API sudah menerima jawabannya. |

## 2. Kendala yang diketahui pada implementasi

Kendala berikut **ditemukan saat menyusun manual ini** dengan menjalankan aplikasi. Sesuai lingkup pekerjaan (tidak mengubah kode aplikasi), kendala ini **belum diperbaiki** dan dicatat untuk ditindaklanjuti tim pengembang.

| # | Kendala | Gejala | Dampak | Usulan perbaikan |
|---|---|---|---|---|
| B1 | Notifikasi dalam aplikasi pendaftar gagal disimpan | Log notifikasi berisi entri *Menunggu* dengan galat *"Kanal gagal: database"*; halaman **Notifikasi** portal kosong. | Pendaftar tidak menerima notifikasi dalam aplikasi; email tetap terkirim. Antrean mencoba ulang terus-menerus. | Kolom `notifications.id` bertipe ULID (26 karakter), sedangkan `PmbMessageNotification` memakai ID UUID bawaan Laravel. Pakai trait `HasUlidId` seperti notifikasi modul lain. |
| B2 | Kolom **Tanggal lahir** tampak kosong pada form identitas (Profil & formulir) | Isian `dd/mm/yyyy` kosong padahal tanggal lahir tersimpan. | Membingungkan pendaftar; data aman selama tidak diubah. | API mengirim tanggal sebagai *timestamp* ISO (`2008-06-15T00:00:00.000000Z`), sedangkan isian tanggal membutuhkan `YYYY-MM-DD`. |
| B3 | Kunci jawaban CBT tidak tampil di bank soal | Kolom **Kunci** kosong; dialog **Ubah** soal tidak terisi kunci. | Admin harus mengisi ulang kunci saat mengubah soal. | Sertakan kunci jawaban pada endpoint admin saja (tetap tersembunyi bagi peserta). |
| B4 | ID transaksi untuk refund tidak tersedia di layar | Form **Ajukan refund** meminta ID internal; transaksi loket & gateway hanya menampilkan referensi. | Refund pembayaran loket/gateway sulit diajukan dari layar. | Tampilkan ID transaksi, atau sediakan tombol **Ajukan refund** dari detail tagihan/transaksi. |
| B5 | Kolom komponen lulus/tidak pada tabel hasil run tampil `0.00` | Contoh kolom **ADM**. | Dapat disalahartikan sebagai nilai nol. | Tampilkan *L/TL* untuk komponen lulus/tidak. |
| B6 | Nama item cicilan berulang | *"Uang Pangkal (cicilan 1) (cicilan 1)"*. | Kosmetik. | Hindari penambahan akhiran ganda pada tampilan. |
| B7 | Penghitung langkah konversi `5/4` | Pembagi tetap 4, sementara konversi mencatat 5 langkah. | Kosmetik. | Sesuaikan pembagi dengan jumlah langkah. |
| B8 | Beberapa kode sistem tampil apa adanya | Contoh: `DOCUMENT_NOT_APPROVED:PHOTO`, `Dokumen: PHOTO`, `below_threshold`, `social_media`, `male`. | Pengguna perlu tabel arti kode (tersedia di manual ini). | Terjemahkan kode menjadi label. |
| B9 | Contoh nomor pendaftaran pada **Cek Status** tidak sesuai format | Placeholder `PMB27-G1-REG-00001`; format sebenarnya `PMB2027-REG-000001`. | Membingungkan. | Samakan placeholder dengan format sebenarnya. |
| B10 | Antrean verifikasi menampilkan pendaftaran yang belum lunas | Pendaftaran tersebut tidak dapat diklaim (*"Biaya pendaftaran belum lunas."*). | Membingungkan verifikator. | Terapkan filter pembayaran pada daftar antrean. |
| B11 | Beberapa dialog meminta ID teknis | **Pindah sesi** (ID sesi tujuan), **Koreksi hasil** (ID aplikasi/kuota), **Indikasi duplikat** (ID akun sekunder). | Petugas perlu menyalin ID dari alamat halaman. | Ganti dengan pilihan (dropdown/pencarian). |

---

[← Perbedaan Rancangan dan Implementasi](60-perbedaan-rancangan-implementasi.md) · [Daftar isi](README.md) · [Berikutnya: FAQ & Pemecahan Masalah →](62-faq-dan-pemecahan-masalah.md)
