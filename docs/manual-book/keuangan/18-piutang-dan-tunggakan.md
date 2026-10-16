# 18. Monitoring Piutang dan Tunggakan

[← Cicilan](17-cicilan.md) · [Daftar isi](README.md) · [Berikutnya: Cekal Keuangan →](19-cekal-keuangan.md)

## 1. Pengertian

- **Piutang** = sisa tagihan berstatus Belum Dibayar atau Dibayar Sebagian.
- **Tunggakan** = piutang yang sudah lewat **jatuh tempo + masa tenggang** (bawaan 3 hari, *Pengaturan Keuangan*). Untuk tagihan bercicilan, tunggakan dihitung per termin.
- Penanda **Menunggak** diperbarui setiap kali tagihan berubah dan oleh pekerjaan harian (00:30).

## 2. Alat pemantauan

| Alat | Lokasi | Isi |
|---|---|---|
| Kartu **Piutang berjalan**, **Tunggakan**, **Umur piutang (aging)**, **Rincian per dimensi**, **Prioritas penagihan** | **Dashboard Keuangan** ([bab 3](03-dashboard-dan-menu.md#2-dashboard-keuangan)) | Gambaran keseluruhan dengan filter semester/fakultas/prodi/angkatan |
| Filter **Menunggak** & **Status: Belum lunas (semua)** | **Tagihan** | Daftar tagihan bermasalah |
| Laporan **Piutang per Mahasiswa**, **Tunggakan**, **Umur Piutang (Aging)** | **Laporan Keuangan** ([bab 21](21-laporan-dan-ekspor.md)) | Data lengkap + ekspor CSV |
| **Profil keuangan mahasiswa** | Klik NIM di daftar mana pun | Ringkasan satu mahasiswa |

## 3. Profil keuangan mahasiswa

**Siapa:** `invoices.read`. **Lokasi:** klik NIM mahasiswa (mis. dari Tagihan, Dashboard, Deposit, Cekal).

Ringkasan: **Sisa tagihan**, **Tunggakan**, **Deposit tersedia**, **Denda berjalan**, **Bebas tanggungan** (Ya/Belum), serta jumlah bukti transfer yang menunggu verifikasi.

Tab:

- **Tagihan** — semua tagihan mahasiswa.
- **Transaksi** — 50 transaksi terakhir.
- **Kartu piutang** — mutasi kronologis: tagihan (+), potongan & pembayaran (−), pembalikan, pembatalan, dengan **saldo berjalan** dan **saldo akhir**.
- **Mutasi deposit** — riwayat saldo deposit (izin `student_deposits.read`).
- **Beasiswa & UKT** — penetapan potongan dan riwayat golongan UKT.
- **Catatan penagihan** — riwayat kontak penagihan.

## 4. Catatan penagihan

| | |
|---|---|
| **Tujuan** | Mencatat upaya penagihan dan janji bayar mahasiswa/orang tua. |
| **Siapa** | `invoices.update`. |

Profil keuangan mahasiswa → **Catatan penagihan** → pilih **Media kontak** (Telepon, WhatsApp, Email, Tatap muka, Surat, Lainnya), isi **Catatan**, opsional **Janji bayar (Rp)** dan **Tanggal janji bayar** → **Simpan**. Catatan tampil di tab **Catatan penagihan** beserta pencatat dan waktunya.

## 5. Pengingat otomatis

Pekerjaan harian mengirim notifikasi *"Tagihan … sisa … jatuh tempo …"* ke mahasiswa pada hari yang diatur di **Jadwal pengingat jatuh tempo** (bawaan 7, 3, 1 hari sebelum dan 1, 7 hari sesudah jatuh tempo). Notifikasi dikirim lewat mekanisme notifikasi Civitas One yang sudah ada (dalam aplikasi; email/WA mengikuti konfigurasi notifikasi kampus).

## 6. Prosedur penagihan yang disarankan

1. Mingguan: buka **Dashboard Keuangan → Prioritas penagihan** atau laporan **Tunggakan**.
2. Hubungi mahasiswa; catat hasilnya di **Catatan penagihan**.
3. Bila mahasiswa kesulitan: tawarkan **cicilan** ([bab 17](17-cicilan.md)) atau, untuk keperluan KRS/ujian yang mendesak, **dispensasi** cekal ([bab 19](19-cekal-keuangan.md)).
4. Pantau kembali pada tanggal janji bayar.
