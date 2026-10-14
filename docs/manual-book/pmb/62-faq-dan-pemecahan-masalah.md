# 62. FAQ & Pemecahan Masalah

[← Hal yang Perlu Dikonfirmasi](61-hal-perlu-konfirmasi.md) · [Daftar isi](README.md) · [Berikutnya: Glosarium & Daftar Status →](63-glosarium-status.md)

Pesan yang dikutip di sini adalah pesan yang benar-benar ditampilkan aplikasi.

## 1. Untuk calon mahasiswa (pendaftar)

| Pertanyaan / pesan | Penyebab | Solusi |
|---|---|---|
| Email verifikasi tidak masuk. | Email salah ketik, masuk folder spam, atau tautan kedaluwarsa (24 jam). | Periksa folder spam. Masuk ke portal lalu klik **Kirim ulang email verifikasi**. Bila email salah ketik, buat akun baru dengan email yang benar atau hubungi panitia. |
| *"Kampus tidak dapat ditentukan dari alamat ini…"* | Website dibuka dari alamat yang tidak terdaftar untuk kampus. | Buka website PMB lewat alamat resmi kampus, atau tambahkan `?kampus=KODE` pada alamat. |
| *"Terlalu banyak percobaan…"* | Batas percobaan (login 10/menit, cek status 5/menit, daftar akun 5/jam). | Tunggu sebentar lalu coba lagi. |
| Tombol **Daftar jalur ini** tidak bisa diklik. | Akun belum terverifikasi, atau sudah mencapai batas pendaftaran aktif per periode (alasannya tampil di kartu). | Verifikasi akun; atau lanjutkan pendaftaran yang sudah ada dari Beranda. |
| *"Pendaftaran gelombang ini sedang tidak dibuka."* | Gelombang belum dibuka/sudah ditutup, atau jalur ditutup panitia. | Lihat halaman **Jadwal**; pilih gelombang yang sedang dibuka. |
| *"Data telah diubah di tab/perangkat lain. Muat ulang."* | Formulir disimpan dari tab/perangkat lain. | Muat ulang halaman, lalu ulangi perubahan. |
| Tombol **Kirim formulir** nonaktif. | Ada langkah bertanda ⚠, pernyataan belum dicentang, atau (jalur *bayar sebelum kirim*) biaya belum lunas. | Buka langkah bertanda ⚠ dan lengkapi isian di kotak **Belum lengkap**. |
| *"Resolusi gambar minimal 400×600 piksel."* / *"Rasio foto harus 3:4 (toleransi 10%)."* | Pas foto terlalu kecil atau tidak berasio 3:4 potret. | Potong foto menjadi 3:4 (misalnya 600×800 piksel), lalu unggah ulang. |
| *"Ukuran berkas melebihi … MB."* | Berkas lebih besar dari batas jenis dokumen. | Perkecil/kompres berkas (misalnya simpan PDF dengan kualitas lebih rendah). |
| Kolom **Tanggal lahir** tampak kosong. | Kendala tampilan yang diketahui. | Data tetap tersimpan; isi ulang hanya bila ingin mengubah ([lampiran 61](61-hal-perlu-konfirmasi.md) B2). |
| Bukti transfer belum diverifikasi. | Menunggu pengecekan mutasi oleh keuangan. | Tunggu maksimal 1 hari kerja; tanyakan lewat **Pesan** bila lebih lama. Pastikan nominal transfer **tepat** termasuk kode unik. |
| Ingin membayar tunai. | — | Datang ke loket kampus dengan nomor tagihan; buka langkah **Pembayaran** minimal sekali agar tagihan terbit. |
| *"Ujian hanya dapat dikerjakan pada waktu sesi."* / *"Ujian belum dibuka."* | Di luar jam sesi atau jadwal belum terbit. | Buka tepat pada jam sesi di kartu peserta. |
| *"Anda tidak dapat mengikuti sesi ini."* | Status kehadiran *Tidak hadir*, *Izin*, atau *Didiskualifikasi*. | Hubungi pengawas/panitia. |
| Halaman hasil *"Hasil seleksi belum tersedia"*. | Belum waktu pengumuman atau hasil belum disahkan. | Tunggu sesuai jadwal pengumuman. |
| Salah menolak tawaran. | — | Segera hubungi panitia; penolakan dapat dipulihkan maksimal 24 jam. |
| *"Akun tidak aktif. Hubungi panitia PMB."* | Akun dibekukan panitia atau dinonaktifkan. | Hubungi panitia lewat kontak resmi. |
| Notifikasi di portal kosong. | Kendala yang diketahui ([lampiran 61](61-hal-perlu-konfirmasi.md) B1). | Pantau email dan Beranda portal. |

## 2. Untuk panitia & staf

### 2.1 Konfigurasi

| Pesan / gejala | Penyebab | Solusi |
|---|---|---|
| *"Semester masuk tidak ditemukan. Buat terlebih dahulu di modul Akademik."* / *"Tahun masuk PMB harus semester ganjil."* | Semester masuk belum ada/bukan ganjil. | Buat semester ganjil tahun akademik masuk di modul Akademik. |
| *"Tanggal tutup harus setelah tanggal buka."*, *"Batas bayar tidak boleh sebelum tanggal tutup pendaftaran."*, *"Pengumuman tidak boleh sebelum ujian selesai."*, dll. | Urutan jadwal gelombang tidak konsisten. | Ikuti urutan: buka < tutup ≤ batas bayar ≤ batas perbaikan ≤ ujian ≤ pengumuman ≤ daftar ulang ([bab 20 §3](20-periode-gelombang-kuota-biaya.md#3-gelombang)). |
| *"Jalur ini sudah dibuka pada gelombang lain yang jadwalnya tumpang tindih (BR-02)."* | Jalur yang sama dibuka di dua gelombang yang jadwalnya berimpit. | Ubah jadwal agar tidak tumpang tindih, atau gunakan jalur lain. |
| *"Template formulir harus berstatus published."* / *"Skema seleksi harus berstatus locked."* | Template/skema masih draf. | Publikasikan template / kunci skema terlebih dahulu. |
| *"Alasan perubahan wajib diisi karena pendaftaran sudah berjalan."* | Mengubah jadwal/kuota/biaya/persyaratan setelah ada pendaftar. | Isi kolom alasan perubahan. |
| *"Kapasitas tidak boleh di bawah jumlah yang sudah diterima (n)."* | Menurunkan kapasitas di bawah jumlah diterima. | Naikkan kapasitas, atau batalkan penerimaan lewat prosedur resmi. |
| *"Total persentase rencana cicilan harus 100%."* | Rencana cicilan JSON tidak berjumlah 100. | Perbaiki persen cicilan. |
| Tombol **Publikasikan** periode menampilkan daftar kekurangan. | Daftar periksa publikasi belum terpenuhi. | Lengkapi butir yang disebutkan ([bab 20 §8](20-periode-gelombang-kuota-biaya.md#8-publikasi-tutup-dan-arsip-periode)). |
| Penyimpanan pengaturan gagal. | Isian JSON tidak valid. | Periksa tanda kutip ganda, koma, dan kurung. |
| Pendaftar tercatat di kampus lain. | Domain backend/website dipetakan ke kampus yang salah. | Periksa domain kampus ([bab 50 §3](50-administrasi-sistem.md#3-domain-website-pmb)). |

### 2.2 Operasional

| Pesan / gejala | Penyebab | Solusi |
|---|---|---|
| *"Biaya pendaftaran belum lunas."* saat klaim. | Pengaturan mewajibkan lunas sebelum verifikasi. | Tunggu pembayaran diverifikasi keuangan. |
| Tombol **Verifikasi** nonaktif. | Ada dokumen wajib belum disetujui (`DOCUMENT_NOT_APPROVED:<kode>`) atau belum lunas (`PAYMENT_NOT_SATISFIED`). | Tinjau dokumen yang disebut; pastikan pembayaran lunas. |
| *"Antrean kosong."* | Tidak ada pendaftaran siap verifikasi untuk filter jalur tersebut. | Ganti filter jalur atau periksa tab Antrean. |
| *"Tanggal ujian harus dalam jendela ujian gelombang (I.2)."* | Tanggal ujian di luar jendela ujian gelombang. | Sesuaikan tanggal, ubah jendela ujian gelombang, atau gunakan gelombang ODS. |
| **Tempatkan otomatis** menyisakan peserta tanpa kursi. | Kapasitas sesi kurang. | Tambah sesi atau kapasitas, lalu tempatkan lagi. |
| Peserta tidak bisa dinilai. | Status kehadiran bukan *Hadir*. | Catat kehadiran di detail sesi. |
| *"Bank soal belum tersedia."* | CBT tanpa soal. | Tambah soal pada jadwal CBT. |
| **Ajukan pengesahan** nonaktif. | Ada skor seri di batas kuota. | **Tetapkan urutan** kelompok seri ([bab 36 §5](36-proses-seleksi-dan-pengesahan.md#5-menjalankan-dan-meninjau-run)). |
| *"Pengaju tidak boleh memutuskan pengajuannya sendiri."* / *"Anda tidak boleh memutuskan pengajuan ini (pemisahan tugas)."* | Aturan pemisahan tugas. | Minta pejabat lain menyetujui. |
| *"Pilihan prodi tidak dapat diubah setelah hasil seleksi ditetapkan."* | Mengubah pilihan setelah ada keputusan. | Gunakan **Koreksi hasil** ([bab 37 §4](37-pengumuman-dan-keputusan.md#4-koreksi-hasil--pengajuan-perubahan)). |
| *"Program studi/sistem kuliah ini tidak ditawarkan pada jalur ini."* | Kombinasi tidak ada di kuota yang ditawarkan. | Pilih kombinasi yang ditawarkan atau tambahkan kuota. |
| *"Nominal harus antara … dan …"* | Nominal cicilan di bawah minimum atau di atas sisa. | Sesuaikan nominal. |
| Konversi **Gagal** dengan `USER_EMAIL_CONFLICT`. | Email pendaftar sudah dipakai akun lain. | **Coba lagi** dengan email pengganti ([bab 38 §5](38-daftar-ulang-dan-konversi.md#5-konversi-mahasiswa)). |
| Banyak log notifikasi *Menunggu* dengan *"Kanal gagal: database"*. | Kendala yang diketahui ([lampiran 61](61-hal-perlu-konfirmasi.md) B1). | Email tetap terkirim; laporkan ke tim pengembang. |
| Status gelombang tidak berubah, notifikasi/konversi tidak berjalan. | Penjadwal (cron) tidak aktif. | Periksa cron `schedule:run` ([bab 50 §4](50-administrasi-sistem.md#4-penjadwal-scheduler)). |

---

[← Hal yang Perlu Dikonfirmasi](61-hal-perlu-konfirmasi.md) · [Daftar isi](README.md) · [Berikutnya: Glosarium & Daftar Status →](63-glosarium-status.md)
