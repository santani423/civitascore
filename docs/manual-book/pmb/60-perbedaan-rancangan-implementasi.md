# 60. Perbedaan Rancangan dan Implementasi

[← Administrasi Sistem](50-administrasi-sistem.md) · [Daftar isi](README.md) · [Berikutnya: Hal yang Perlu Dikonfirmasi →](61-hal-perlu-konfirmasi.md)

Lampiran ini mencatat bagian **rancangan** ([`docs/pmb`](../../pmb/)) yang **diimplementasikan berbeda** atau **belum tersedia** di aplikasi saat manual ini disusun (14 Oktober 2026). Setiap butir sudah diperiksa pada aplikasi yang berjalan dan pada kodenya. Keputusan resmi atas kontradiksi rancangan (D-01…D-07, F1…F15) tercatat di [16 — Catatan Implementasi §2](../../pmb/16-catatan-implementasi.md#2-keputusan-atas-kontradiksi--celah-dokumen); lampiran ini berfokus pada hal yang **dirasakan pengguna**.

Kolom **Dampak bagi pengguna** menjelaskan cara kerja yang berlaku sekarang.

## 1. Portal pendaftar & website

| # | Rancangan | Implementasi saat ini | Dampak bagi pengguna |
|---|---|---|---|
| 1 | Formulir dengan simpan otomatis, indikator *"Tersimpan otomatis"*, dan antrean lokal saat *offline* ([12](../../pmb/12-ui-ux-sitemap.md) POR-04, prinsip *offline*) | Simpan manual per langkah (**Simpan data diri**, **Simpan pilihan**, dst.); tidak ada mode *offline*. | Pendaftar harus menekan tombol simpan sebelum berpindah langkah. ([bab 12](12-formulir-pendaftaran.md#4-mengisi-formulir)) |
| 2 | Halaman sukses kirim formulir dengan nomor besar, tombol salin, dan **unduh bukti pendaftaran** (POR-04) | Pesan sukses berisi nomor pendaftaran; langkah *Pernyataan & kirim* menampilkan nomor & waktu kirim; nomor juga dikirim lewat email. Tidak ada berkas bukti pendaftaran. | Simpan nomor pendaftaran dari layar/email. Bukti PDF tersedia setelah **daftar ulang** (bukti registrasi). |
| 3 | Unggah dokumen *drag & drop*, kamera di ponsel, kompresi sisi klien, indikator *sedang dipindai* (POR-05) | Tombol pilih berkas standar; ukuran dicek sebelum unggah; status pemindaian ditampilkan tanpa pembaruan otomatis. | Pendaftar memperkecil berkas sendiri bila terlalu besar. |
| 4 | Halaman pembayaran: *polling* status 15 detik, konfeti saat sukses (POR-06) | Status diperbarui saat halaman dimuat ulang atau tombol **Cek status** (gateway). | Muat ulang halaman setelah membayar lewat gateway. |
| 5 | Tolak tawaran dengan konfirmasi dua langkah (ketik "TOLAK") (POR-09) | Satu dialog **Tolak tawaran** dengan alasan opsional; penolakan dapat dipulihkan panitia ≤ 24 jam. | Pendaftar perlu berhati-hati; hubungi panitia bila salah tolak. |
| 6 | Akun: riwayat persetujuan (*consent*) dan pengajuan hapus akun (POR-12) | Pengaturan persetujuan promosi, unduh data pribadi, dan **Nonaktifkan akun**. Riwayat consent tersimpan di sistem tetapi tidak ditampilkan ke pendaftar. | Permintaan penghapusan data diproses lewat panitia/DPO kampus. |
| 7 | Beranda tanpa periode terbuka menampilkan formulir *"beri tahu saya"* (PUB-01) | Hanya pesan *"Pendaftaran belum dibuka…"* atau tanggal pembukaan gelombang berikutnya. | Calon pendaftar memantau halaman **Jadwal**. |
| 8 | Field tambahan formulir dengan aturan tampil bersyarat (FR-CFG-08) | Aturan dievaluasi server untuk menentukan kewajiban isian, tetapi portal **menampilkan semua** field tambahan. Field bertahap *Daftar ulang* belum tampil di halaman daftar ulang portal. | Panitia sebaiknya tidak mengandalkan aturan tampil untuk menyembunyikan pertanyaan dari pendaftar. |
| 9 | Notifikasi dalam aplikasi (POR-11) | Halaman tersedia, tetapi penyimpanan notifikasi pendaftar gagal pada versi yang diuji (lihat [lampiran 61 §2](61-hal-perlu-konfirmasi.md#2-kendala-yang-diketahui-pada-implementasi)). | Pendaftar memantau email dan Beranda portal. |

## 2. Admin PMB

| # | Rancangan | Implementasi saat ini | Dampak bagi pengguna |
|---|---|---|---|
| 10 | Pintasan keyboard `J`/`K`/`A`/`R`, panel data ↔ dokumen berdampingan (prinsip petugas volume tinggi, ADM-31/32) | Tombol & tab; dokumen dibuka di tab baru. | Verifikator bekerja dengan tetikus; gunakan **Ambil berikutnya** untuk mempercepat. |
| 11 | Penampil dokumen dalam halaman (*zoom*, rotasi, navigasi halaman) | Dokumen dibuka di tab baru peramban (penampil PDF/gambar bawaan peramban). | — |
| 12 | Tabel dengan filter tersimpan, pilihan kolom, ekspor dari tabel (ADM-30, `DataTable`) | Filter dasar per halaman; ekspor lewat menu **Laporan PMB**. | Gunakan Laporan untuk data lengkap. |
| 13 | Dashboard khusus per peran (ADM-02 verifikasi, ADM-03 keuangan, ADM-04 seleksi, ADM-05 daftar ulang, ADM-06 pimpinan) dan perbandingan antarperiode (KPI-16) | Satu **Dashboard PMB** (data dibatasi izin & cakupan) + statistik di halaman Verifikasi & Keuangan. Perbandingan periode ada di API (`dashboard/comparison`) tetapi belum di layar. | Pimpinan memakai Dashboard PMB dan Laporan. |
| 14 | Laporan *Funnel* dan *Perbandingan Periode* | Funnel ada di Dashboard; perbandingan periode belum ada di layar. | — |
| 15 | Kuota dalam bentuk matriks dengan impor/ekspor XLSX (ADM-13) | Tabel baris yang dapat diedit + **Salin dari periode**. Mode *Batas per gelombang* belum memiliki isian batas per gelombang. | Gunakan mode *Kuota periode*. |
| 16 | Editor template: urutan *drag*, pembuat kondisi visual, pratinjau langsung mobile/desktop (ADM-16) | Isian **Urutan** (angka); opsi/validasi/aturan tampil dalam JSON; **Pratinjau skema** berupa JSON. | Admin perlu teliti menulis JSON (contoh di [bab 22](22-template-formulir.md)). |
| 17 | Editor skema: urutan tie-break *drag*, simulasi dari unggahan (ADM-17) | Angka **Prioritas tie-break** per komponen; simulasi dengan isian JSON. | — |
| 18 | Check-in dengan kamera tablet & foto peserta (ADM-52) | Isian token QR (pemindai kode batang sebagai keyboard) dan ubah status manual. **Pindah sesi** meminta ID sesi tujuan. | Siapkan pemindai kode batang/QR USB; untuk pindah sesi, utamakan alur **Permintaan jadwal ulang**. |
| 19 | Penilaian: simpan otomatis, navigasi cepat antarpeserta (ADM-53) | Satu tombol **Simpan nilai** per sesi. | Simpan berkala. |
| 20 | Penempatan peserta: beberapa strategi | Tombol layar memakai strategi *nomor pendaftaran*; strategi *per prodi* & *aksesibilitas* hanya tersedia di API. | Atur ruang ramah disabilitas secara manual bila perlu. |
| 21 | Halaman persetujuan khusus PMB (ringkasan run, override, hash) dengan aksi setujui/**kembalikan**/tolak (ADM-60) | Halaman **Persetujuan** umum Civitas One: **Setujui**, **Tolak**, **Delegasikan**. Ringkasan run dibaca di detail run PMB. Aksi *kembalikan* belum ada tombolnya. | Pengesah membuka detail run sebelum memutuskan; untuk "mengembalikan", tolak dengan alasan lalu panitia menjalankan run baru. |
| 22 | Layanan bantuan berbasis tiket | Pesan (chat) dengan status & penanggung jawab, plus antrean **Pemulihan akun**. | — |
| 23 | Koreksi hasil memilih aplikasi dari daftar | Dialog meminta **ID aplikasi** (dan ID kuota pada usulan). | Salin ID dari alamat halaman detail pendaftar ([bab 37](37-pengumuman-dan-keputusan.md#4-koreksi-hasil--pengajuan-perubahan)). |
| 24 | Tipe konten *Brosur* dan *Template hasil* | Dapat dibuat, tetapi belum ditampilkan/dipakai di website & portal. | Brosur prodi diunggah lewat **Profil Prodi**. |

## 3. Kebijakan default yang berbeda dari rekomendasi rancangan

| # | Rekomendasi rancangan ([02 §4](../../pmb/02-asumsi-keputusan-pertanyaan.md)) | Default aplikasi | Cara mengubah |
|---|---|---|---|
| 25 | Q-CFG-05: maksimal 2 aplikasi aktif per periode | 1 (keputusan F1) | Pengaturan `pmb.max_active_applications_per_period` |
| 26 | Q-ANN-01: pengesahan dua langkah Ketua Panitia → WR I | Ketua Panitia (Admin PMB) → **Pejabat Pengesah PMB** (`admission_approver`) | Berikan peran `admission_approver` kepada WR I, atau ubah langkah alur di modul Persetujuan |
| 27 | Q-PAY-06: voucher sebagai Phase 2 | Voucher sudah tersedia | **Pengaturan PMB → Voucher** |

---

[← Administrasi Sistem](50-administrasi-sistem.md) · [Daftar isi](README.md) · [Berikutnya: Hal yang Perlu Dikonfirmasi →](61-hal-perlu-konfirmasi.md)
