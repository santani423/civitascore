# 30. Dashboard PMB

[← Pengaturan PMB](25-pengaturan-pmb.md) · [Daftar isi](README.md) · [Berikutnya: Data Pendaftar →](31-data-pendaftar.md)

**Untuk:** semua peran dengan `pmb_reports.read`: Admin PMB, verifikator, panitia seleksi, pengesah, petugas daftar ulang, keuangan, pimpinan, dekan/kaprodi (terbatas cakupannya), auditor.
**Menu:** **PMB → Dashboard PMB**.

![Dashboard PMB](assets/30-01-dashboard.png)
*Gambar 30.1 — Dashboard PMB untuk satu periode.*

## 1. Memilih periode

Gunakan pilihan di kanan atas (**Semua periode** atau periode tertentu). Pilih periode tertentu agar tabel **Capaian kuota** terisi (pada *Semua periode* tabel ini menampilkan *"Belum ada kuota."*).

## 2. Isi dashboard

| Bagian | Isi |
|---|---|
| **Akun pendaftar** | Jumlah akun dan yang terverifikasi. |
| **Formulir terkirim** | Jumlah formulir terkirim dan draf yang belum dikirim. |
| **Bayar pendaftaran** | Persentase & jumlah tagihan pendaftaran yang lunas. |
| **Peserta seleksi** | Pendaftar yang masuk tahap seleksi, serta jumlah dokumen yang menunggu tinjauan. |
| **Diterima** | Jumlah diterima, cadangan, dan tidak diterima. |
| **Resmi mahasiswa** | Jumlah yang selesai daftar ulang & konversi, serta *yield* (resmi mahasiswa ÷ diterima). |
| **Funnel** | Akun → Formulir terkirim → Bayar pendaftaran → Terverifikasi → Diterima → Resmi mahasiswa. |
| **Tren 30 hari** | Grafik harian akun baru, formulir terkirim, pembayaran, dan mahasiswa baru. |
| **Capaian kuota** | Per prodi × jalur × sistem: kapasitas, diterima aktif, mahasiswa, persentase capaian. |
| **Pendapatan PMB** | Total pendapatan, rincian biaya pendaftaran & biaya awal, piutang biaya awal, refund. Hanya tampil bagi yang memiliki izin melihat pembayaran. |
| **Verifikasi (SLA)** | Median dan persentil-90 lama verifikasi (jam) serta tautan **Buka antrean verifikasi**. |
| **Sumber informasi** | Sumber informasi pendaftar dan persentase konversinya, serta persentase kelengkapan data PDDikti. |

Dekan/kaprodi dan peran ber-cakupan lain hanya melihat angka untuk prodi dalam cakupannya.

> **Beda rancangan** — Rancangan menyebut dashboard per peran (ADM-02 verifikasi, ADM-03 keuangan, ADM-04 seleksi, ADM-05 daftar ulang, ADM-06 pimpinan) dan *perbandingan antarperiode* (KPI-16). Versi ini menyediakan satu Dashboard PMB untuk semua peran (data dibatasi izin & cakupan), ditambah ringkasan statistik di halaman **Verifikasi** dan **Keuangan PMB**. Perbandingan antarperiode tersedia di API (`dashboard/comparison`) tetapi belum ditampilkan di layar. Lihat [lampiran 60](60-perbedaan-rancangan-implementasi.md).

---

[← Pengaturan PMB](25-pengaturan-pmb.md) · [Daftar isi](README.md) · [Berikutnya: Data Pendaftar →](31-data-pendaftar.md)
