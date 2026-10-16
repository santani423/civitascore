# 42. Batasan Fitur, Dependensi, dan Integrasi yang Belum Aktif

[← FAQ & Prosedur](41-faq-dan-prosedur.md) · [Daftar isi](README.md)

Lampiran ini mencatat apa yang **belum tersedia** atau **bergantung pada pihak luar** pada versi ini (16 Oktober 2026), agar pengguna tidak mengandalkan fitur yang belum aktif. Rincian teknis: [Catatan Implementasi §9–§10](../../keu/catatan-implementasi.md#9-ketergantungan-eksternal).

## 1. Integrasi eksternal

| Integrasi | Status | Dampak & cara kerja sementara |
|---|---|---|
| **Pembayaran online (VA/QRIS/e-wallet) melalui Xendit** | Adapter tersedia, **belum diuji dengan akun nyata**; **tidak aktif** tanpa kredensial di server | Kanal online tampil **Terhubung: Belum** dan tidak ditawarkan ke mahasiswa. Gunakan tunai di loket dan transfer manual. Aktivasi memerlukan: akun penyedia kampus, kredensial dipasang administrator sistem (`XENDIT_SECRET_KEY`, `XENDIT_WEBHOOK_TOKEN`), uji dengan kunci test mode Xendit, lalu kanal produksi diaktifkan. |
| **Bank host-to-host / VA langsung bank** | **Belum dibangun** | Gunakan transfer manual + rekonsiliasi mutasi CSV. |
| **Refund otomatis lewat bank/penyedia** | **Belum dibangun** | Transfer refund dilakukan manual lalu dicatat. |
| **Format mutasi khusus tiap bank** | **Belum ada** | Impor CSV generik (tanggal, keterangan, nominal, referensi) — sesuaikan kolom hasil ekspor internet banking. |
| **Notifikasi email/WhatsApp** | Mengikuti modul Notifikasi Civitas One | KEU mengirim notifikasi dalam aplikasi lewat templat `keu.*`; pengiriman ke saluran lain bergantung konfigurasi notifikasi kampus. |
| **Pelaporan PDDikti** | **Belum ada** | Kategori PDDikti disimpan per komponen; ekspor PDDikti otomatis belum tersedia. |
| **Akuntansi / jurnal (GL)** | **Belum ada** | Kode akun disimpan per komponen; tutup buku dan jurnal belum tersedia. |

## 2. Batasan fungsional

| Area | Batasan |
|---|---|
| Cekal | Hanya **Pengisian KRS** dan **Ujian** yang ditegakkan. Nilai, transkrip, yudisium, wisuda belum dapat dicekal. |
| Surat bebas tanggungan | Status tersedia di aplikasi; **surat PDF belum tersedia**. |
| Tarif | Dasar hitung **Tetap** dan **Per SKS**; tarif per mata kuliah belum ada. Tingkat semester tidak mengurangkan semester cuti. |
| UKT | Instrumen penilaian/skoring kemampuan ekonomi belum ada; golongan ditetapkan manual. |
| Beasiswa | Klaim/penagihan ke pemberi beasiswa (sponsor) belum ada. |
| Persetujuan | Satu tingkat persetujuan (pembuat ≠ penyetuju); alur bertingkat belum ada. |
| Cakupan data | Pimpinan fakultas/prodi (Dekan, Kaprodi) belum dibatasi ke data unitnya; peran pimpinan melihat seluruh kampus. |
| Laporan | Ekspor **CSV** saja; tampilan maks. 500 baris (ekspor berisi semua). |
| Rekonsiliasi | CSV dibaca di peramban dan dikirim sebagai data (maks. 5.000 baris per impor). |
| Portal | Portal orang tua/wali dan aplikasi mobile belum ada. |
| Penerbitan massal | Rincian per mahasiswa di layar dibatasi 1.000 baris per penerbitan. |
| Tampilan | Manual ini belum memuat tangkapan layar; antarmuka diverifikasi lewat build & pengujian API otomatis, belum lewat uji penerimaan pengguna di kampus. |

## 3. Ketergantungan operasional

| Kebutuhan | Penanggung jawab |
|---|---|
| Penjadwal server berjalan tiap menit (`schedule:run`) — untuk tunggakan, denda harian, kedaluwarsa instruksi, pengingat, evaluasi cekal, cek ulang pembayaran online | Administrator sistem |
| Data awal (`php artisan keu:install`) dan seeder peran/izin | Administrator sistem |
| Rekening penerimaan transfer diisi di **Pengaturan Keuangan** | Kepala Bagian Keuangan |
| Minimal dua akun keuangan agar persetujuan (maker-checker) dapat berjalan | Admin universitas |
| Data mahasiswa, prodi, semester berjalan, dan KRS terkelola di SIAKAD | BAAK |
| Pemetaan paket langganan CivitasOne ke kuota kanal (Growth/Business/Professional/Premium/Enterprise) | Manajemen produk (keputusan terbuka di rancangan) |
