# 12. Pembayaran: Tunai, Transfer Manual, Online

[← Tagihan](11-tagihan.md) · [Daftar isi](README.md) · [Berikutnya: Verifikasi & Kuitansi →](13-verifikasi-dan-kuitansi.md)

## 1. Prinsip umum

- Semua uang masuk dicatat sebagai **transaksi** dan dialokasikan ke tagihan atau termin.
- **Pembayaran sebagian** diperbolehkan: tagihan menjadi **Dibayar Sebagian** dan sisanya tetap terbuka.
- **Kelebihan bayar** otomatis masuk **deposit** mahasiswa (mahasiswa menerima notifikasi *"Saldo deposit Anda bertambah …"*).
- Untuk tagihan bercicilan, pembayaran mengisi **termin berurutan** (termin yang dipilih lebih dulu, lalu termin berikutnya).
- Setiap transaksi berhasil memperoleh **nomor kuitansi** `KWT-<tahun><bulan>-<urut>` dan mahasiswa menerima notifikasi *"Pembayaran … telah diterima dan dicatat."*
- Status tagihan dihitung ulang otomatis; bila lunas, cekal keuangan yang terkait dievaluasi ulang dan dicabut otomatis ([bab 19](19-cekal-keuangan.md)).

## 2. Pembayaran tunai di loket (kasir)

| | |
|---|---|
| **Tujuan** | Menerima pembayaran tunai dengan pertanggungjawaban per sesi kas. |
| **Siapa** | Kasir Keuangan, Bagian Keuangan (`cashier_sessions.create` + `payment_transactions.create`). Verifikasi setoran: `cashier_sessions.approve` (bukan kasir yang sama). |
| **Lokasi** | **Keuangan → Loket Kasir**. |
| **Data disiapkan** | Uang kembalian awal (kas awal); NIM/nama mahasiswa; slip setoran bank di akhir sesi. |

### 2.1 Membuka sesi kas

1. Klik **Buka sesi kas**, isi **Kas awal / uang kembalian (Rp)**, klik **Simpan**.
2. Kartu sesi tampil: nomor sesi (`KAS-…`), waktu buka, kas awal, **Penerimaan tunai (sistem)**.

Satu kasir hanya dapat memiliki satu sesi terbuka: *"Anda masih memiliki sesi kas yang terbuka. Tutup sesi tersebut terlebih dahulu."*

### 2.2 Menerima pembayaran

1. Pada kartu **Terima pembayaran tunai**, cari mahasiswa (minimal 3 karakter NIM/nama) dan pilih.
2. Sistem menampilkan **Total sisa tagihan**, **Tunggakan**, dan **Saldo deposit** mahasiswa.
3. Pilih **Bayar untuk**: tagihan tertentu, termin tertentu (↳ Termin n), atau **Titipan (masuk deposit)**. Nominal terisi otomatis dengan sisa.
4. Ubah **Nominal diterima** bila mahasiswa membayar sebagian/lebih; isi **Nama penyetor** bila bukan mahasiswa sendiri.
5. Klik **Catat pembayaran**. Pesan *"Pembayaran tunai dicatat. Kuitansi: KWT-…"* tampil dengan tombol **Cetak kuitansi** (PDF).

**Validasi:** nominal > 0; tidak melebihi *batas nominal tunai per transaksi* bila diatur; tagihan harus milik mahasiswa terpilih dan masih memiliki sisa; hanya dalam sesi terbuka milik kasir sendiri (*"Pembayaran tunai hanya dapat dicatat dalam sesi kas terbuka milik Anda."*).

### 2.3 Menutup sesi & mencatat setoran

1. Klik **Tutup sesi …**. Dialog menampilkan nilai yang seharusnya ada (kas awal + penerimaan, dikurangi transaksi yang dibalik). Isi **Hasil hitung fisik (Rp)** dan **Catatan** (wajib bila ada selisih). Klik **Simpan**. Selisih tercatat di kolom **Selisih** (merah bila tidak nol); transaksi **tidak** diubah.
2. Setelah menyetor ke bank, pada baris sesi berstatus **Ditutup** klik **Catat setoran**, isi **Nomor referensi setoran** dan unggah **Bukti setoran** (opsional).
3. Petugas lain berizin verifikasi klik **Verifikasi setoran** → status **Terverifikasi**.

Setoran kas juga dapat dicocokkan dengan mutasi bank lewat [rekonsiliasi sumber *Setoran kas loket*](20-rekonsiliasi-dan-refund.md).

## 3. Transfer manual dengan unggah bukti

| | |
|---|---|
| **Tujuan** | Mahasiswa mentransfer ke rekening kampus lalu mengunggah bukti untuk diverifikasi. |
| **Siapa** | Mahasiswa (`student_finance.create`). Verifikasi: [bab 13](13-verifikasi-dan-kuitansi.md). |
| **Syarat** | Pengaturan **Rekening penerimaan kampus untuk transfer manual** sudah diisi ([bab 3 §4.2](03-dashboard-dan-menu.md#42-parameter-kebijakan)). |

**Langkah mahasiswa** (portal **Keuangan** → klik tagihan → kartu **Bayar tagihan**):

1. Bila tagihan dicicil, pilih **Bayar untuk** (seluruh sisa atau termin tertentu).
2. Pada kotak **Transfer manual ke rekening kampus**, klik **Buat instruksi transfer**.
3. Kartu **Pembayaran yang sedang berjalan** menampilkan nominal **tepat** yang harus ditransfer (sudah termasuk **kode unik** 3 digit bila diaktifkan), daftar rekening kampus, dan batas waktu unggah bukti (3 hari).
4. Transfer **tepat sejumlah nominal** tersebut.
5. Klik **Unggah bukti transfer**, pilih berkas (JPG/PNG/PDF, maks. 2 MB), isi **Bank pengirim**, **Nama pemilik rekening pengirim**, **Tanggal transfer**, **Nominal yang ditransfer**. Klik **Simpan**.
6. Status menjadi **Menunggu verifikasi**. Setelah disetujui, tagihan diperbarui dan kuitansi tersedia; bila ditolak, mahasiswa menerima notifikasi berisi alasan dan dapat **Unggah ulang bukti**.

**Aturan**

- Tanggal transfer tidak boleh di masa depan. **Tanggal transfer** (bukan tanggal verifikasi) menjadi tanggal bayar efektif — denda tidak bertambah karena antrean verifikasi.
- Berkas yang sama persis dengan bukti transaksi lain ditandai **Bukti ganda?** untuk verifikator.
- Berkas harus diunggah oleh akun mahasiswa sendiri.
- Membuat instruksi baru atau pembayaran online untuk tagihan yang sama membatalkan instruksi transfer manual yang belum diberi bukti.
- Instruksi yang lewat batas waktu tanpa bukti menjadi **Kedaluwarsa** (pekerjaan harian).

## 4. Pembayaran online (VA/QRIS)

> **Status:** hanya tersedia bila kampus memiliki akun penyedia pembayaran, kredensialnya dipasang administrator sistem, dan kanal online diaktifkan di **Pengaturan Keuangan**. Pada instalasi tanpa kredensial, kotak **Pembayaran online** di portal menampilkan *"Pembayaran online (virtual account/QRIS) belum tersedia di kampus Anda…"* dan mahasiswa memakai transfer manual atau tunai. Adapter penyedia (Midtrans) **belum diuji dengan akun nyata** — lihat [bab 42](42-batasan-dan-integrasi.md).

**Alur bila aktif**

1. Mahasiswa memilih kanal (mis. *Virtual Account BNI*) pada kotak **Pembayaran online**. Bila biaya admin ditanggung mahasiswa, nominal biaya tampil di tombol.
2. Sistem membuat transaksi **Menunggu pembayaran** dan menampilkan **Nomor virtual account** atau **kode QR**, langkah pembayaran, dan masa berlaku.
3. Mahasiswa membayar lewat bank/aplikasi. Penyedia mengirim notifikasi ke sistem; sistem **memeriksa ulang status ke penyedia** sebelum mencatat pembayaran.
4. Bila notifikasi terlambat, mahasiswa dapat klik **Cek status**; sistem juga memeriksa otomatis tiap 10 menit.

**Aturan**

- Satu tagihan hanya punya satu instruksi online aktif; membuat instruksi baru membatalkan yang lama.
- Status yang dibawa halaman kembali dari penyedia **tidak pernah** dianggap bukti bayar; hanya hasil pemeriksaan ke penyedia yang dipakai.
- Bila nominal dari penyedia berbeda dengan transaksi, pembayaran **tidak dicatat** dan transaksi ditandai **Pengecualian** untuk diperiksa petugas.
- Pembayaran yang diterima setelah instruksi kedaluwarsa/dibatalkan tetap dicatat, ditandai **Perlu resolusi**; dana yang tidak teralokasi masuk deposit.

## 5. Membayar dengan saldo deposit

**Siapa:** mahasiswa (portal) atau petugas `student_deposits.update`.
**Lokasi:** portal → detail tagihan → **Pakai saldo deposit**; petugas → detail tagihan → **Bayar dari deposit**.

Kosongkan nominal untuk memakai saldo sebanyak mungkin (maksimal sisa tagihan/termin). Transaksi bermetode **Deposit** tercatat berhasil seketika. Bila saldo kosong: *"Saldo deposit kosong atau tagihan sudah lunas."*

## 6. Membatalkan transaksi yang belum dibayar

Transaksi berstatus **Dibuat** atau **Menunggu pembayaran** dapat dibatalkan oleh mahasiswa (portal → **Batalkan**) atau petugas (`payment_transactions.update`, detail transaksi → **Batalkan**). Transaksi yang sudah **Menunggu verifikasi** atau **Berhasil** tidak dapat dibatalkan dengan cara ini: *"Hanya transaksi yang belum dibayar yang dapat dibatalkan."* — untuk transaksi berhasil gunakan **Balik transaksi** ([bab 13 §3](13-verifikasi-dan-kuitansi.md#3-membalik-transaksi)).
