# Naskah Produksi — Video Tutorial PMB untuk Calon Mahasiswa

| | |
|---|---|
| **Judul** | Video Tutorial PMB untuk Calon Mahasiswa |
| **Sasaran penonton** | Calon mahasiswa (pendaftar) dan orang tua/wali yang mendampingi; pengguna awam, tanpa latar belakang teknis. |
| **Tujuan** | Penonton dapat mendaftar secara mandiri dari membuat akun sampai resmi menjadi mahasiswa dan memperoleh NIM. |
| **Durasi** | 17:28 (24 adegan, 105 kalimat narasi) |
| **Berkas video** | [`tutorial-pmb-calon-mahasiswa.mp4`](tutorial-pmb-calon-mahasiswa.mp4) — MP4 H.264, 1920 × 1080, 25 fps, audio AAC; takarir tertanam |
| **Pengisi suara** | Suara sintetis Microsoft Edge `id-ID-GadisNeural` (Bahasa Indonesia). Dapat diganti suara manusia dengan naskah yang sama. |
| **Panduan tertulis pendamping** | [MANUAL-BOOK-PMB-CALON-MAHASISWA](../manual-book/pmb/MANUAL-BOOK-PMB-CALON-MAHASISWA.md) |
| **Sumber visual** | 37 screenshot aplikasi yang berjalan, dari [`docs/manual-book/pmb/assets`](../manual-book/pmb/assets/) (data demo fiktif *Universitas Nusantara Digital*) |
| **Acuan isi** | Rancangan [`docs/pmb/01–16`](../pmb/) dan manual book PMB per bab [`docs/manual-book/pmb`](../manual-book/pmb/README.md), dicocokkan dengan kode aplikasi terkini |
| **Sumber produksi** | [`source/calon-mahasiswa.script.mjs`](source/calon-mahasiswa.script.mjs) (data naskah), [`source/player.html`](source/player.html) (pemutar), cara membangun ulang di bagian akhir |

> Naskah ini **dibangkitkan otomatis** dari data yang sama dengan video (`build_naskah.mjs`), sehingga narasi, urutan adegan, tindakan layar, teks pendukung, dan waktu selalu sesuai dengan berkas video. Untuk mengubah isi, ubah `source/calon-mahasiswa.script.mjs`, lalu bangun ulang audio, naskah, dan video.

## Daftar isi

1. [Ringkasan adegan](#ringkasan-adegan)
2. [Pedoman produksi](#pedoman-produksi)
3. [Naskah per adegan](#naskah-per-adegan)
4. [Lampiran A — Ejaan pengucapan untuk pengisi suara](#lampiran-a--ejaan-pengucapan-untuk-pengisi-suara)
5. [Lampiran B — Cara membangun ulang video](#lampiran-b--cara-membangun-ulang-video)

## Ringkasan adegan

| No | Adegan | Mulai | Durasi | Jenis visual |
|---:|---|---:|---:|---|
| 0 | [Pembuka](#adegan-0) | 0:00 | 38 dtk | Kartu judul |
| 1 | [Tujuh Tahap Pendaftaran](#adegan-1) | 0:38 | 49 dtk | Diagram alur |
| 2 | [Yang Perlu Disiapkan](#adegan-2) | 1:26 | 51 dtk | Kartu informasi |
| 3 | [Website PMB](#adegan-3) | 2:17 | 35 dtk | Screenshot aplikasi + sorotan |
| 4 | [Prodi, Jalur, Biaya, Jadwal](#adegan-4) | 2:52 | 1 mnt 12 dtk | Screenshot aplikasi + sorotan |
| 5 | [Membuat Akun](#adegan-5) | 4:03 | 46 dtk | Screenshot aplikasi + sorotan |
| 6 | [Masuk & Verifikasi](#adegan-6) | 4:49 | 32 dtk | Screenshot aplikasi + sorotan |
| 7 | [Memilih Gelombang & Jalur](#adegan-7) | 5:21 | 44 dtk | Screenshot aplikasi + sorotan |
| 8 | [Formulir: Data Diri](#adegan-8) | 6:06 | 41 dtk | Screenshot aplikasi + sorotan |
| 9 | [Formulir: Pendidikan](#adegan-9) | 6:47 | 45 dtk | Screenshot aplikasi + sorotan |
| 10 | [Formulir: Pilihan Prodi](#adegan-10) | 7:31 | 19 dtk | Screenshot aplikasi + sorotan |
| 11 | [Formulir: Data Pendukung](#adegan-11) | 7:50 | 31 dtk | Screenshot aplikasi + sorotan |
| 12 | [Formulir: Dokumen](#adegan-12) | 8:21 | 32 dtk | Screenshot aplikasi + sorotan |
| 13 | [Membayar Biaya Pendaftaran](#adegan-13) | 8:54 | 1 mnt 2 dtk | Screenshot aplikasi + sorotan |
| 14 | [Mengirim Formulir](#adegan-14) | 9:56 | 41 dtk | Screenshot aplikasi + sorotan |
| 15 | [Bila Diminta Perbaikan](#adegan-15) | 10:36 | 30 dtk | Screenshot aplikasi + sorotan |
| 16 | [Ujian & Kartu Peserta](#adegan-16) | 11:07 | 1 mnt 14 dtk | Screenshot aplikasi + sorotan |
| 17 | [Hasil Seleksi & Surat Penerimaan](#adegan-17) | 12:21 | 54 dtk | Screenshot aplikasi + sorotan |
| 18 | [Menerima atau Menolak Tawaran](#adegan-18) | 13:15 | 29 dtk | Screenshot aplikasi + sorotan |
| 19 | [Daftar Ulang](#adegan-19) | 13:44 | 45 dtk | Screenshot aplikasi + sorotan |
| 20 | [Resmi Menjadi Mahasiswa](#adegan-20) | 14:29 | 31 dtk | Screenshot aplikasi + sorotan |
| 21 | [Pesan, Akun & Cek Status](#adegan-21) | 15:00 | 33 dtk | Screenshot aplikasi + sorotan |
| 22 | [Kendala Umum](#adegan-22) | 15:33 | 1 mnt 15 dtk | Kartu informasi |
| 23 | [Ringkasan](#adegan-23) | 16:48 | 40 dtk | Ringkasan & penutup |

## Pedoman produksi

| Aspek | Ketentuan |
|---|---|
| Tata letak layar | Bilah atas: logo, nama aplikasi, nama panduan, penanda bagian (*Bagian n/N · judul*). Tengah: jendela peramban berisi screenshot (kiri) dan **kartu teks pendukung** (kanan). Bawah: takarir narasi dan bilah progres. |
| Gerak kamera | Kamera bergeser/zoom halus ke area yang dibicarakan pada awal setiap kalimat. Area penting diberi **bingkai sorotan kuning**; tombol yang "diklik" ditunjukkan kursor dengan riak hijau. |
| Teks pendukung | Kartu kanan memuat langkah bernomor, nama tombol/menu persis seperti di aplikasi (tebal), serta kotak *Info* (biru), *Perhatian* (kuning), *Berhasil* (hijau), *Akibat* (merah). |
| Narasi | Bahasa Indonesia baku yang sederhana, menyapa penonton dengan "Anda", satu gagasan per kalimat. Nama tombol dibacakan persis seperti di layar. |
| Takarir | Selalu tampil (aksesibilitas penonton tunarungu & menonton tanpa suara). |
| Data pada layar | Seluruh nama, nomor, dan nominal adalah **data demo fiktif**. Tampilan kampus dapat berbeda karena logo, warna, jalur, biaya, dan pengaturan masing-masing kampus. |
| Rekaman ulang dari aplikasi asli (opsional) | Ikuti baris **Arahan demonstrasi** pada setiap adegan. Gunakan akun demo per peran, resolusi 1440 × 900, zoom peramban 100%, dan sembunyikan data pribadi asli. |

## Naskah per adegan

<a id="adegan-0"></a>
### Adegan 0 — Pembuka

**Waktu:** 0:00–0:38 (38 dtk) · **Jenis:** Kartu judul

**Visual:** Kartu judul dengan delapan topik yang muncul bertahap.

**Teks layar:** judul "Mendaftar Kuliah lewat PMB Online"; subjudul "Langkah demi langkah dari membuat akun sampai resmi menjadi mahasiswa dan mendapatkan NIM."; label "Video Tutorial · Calon Mahasiswa".

**Butir yang ditampilkan:**

1. **Website PMB**
2. **Akun**
3. **Formulir**
4. **Dokumen & bayar**
5. **Ujian**
6. **Hasil seleksi**
7. **Daftar ulang**
8. **NIM**

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 0:00 | Halo, calon mahasiswa! Selamat datang di video panduan pendaftaran mahasiswa baru secara online. | Tampilkan judul video. |  |
| 2 | 0:09 | Kita akan mencoba seluruh langkahnya: membuka website PMB, membuat akun, mengisi formulir, mengunggah dokumen, dan membayar biaya pendaftaran. | Munculkan empat topik pertama. | Muncul: Website PMB, Akun, Formulir, Dokumen & bayar |
| 3 | 0:20 | Lalu mengikuti ujian bila jalur Anda memakai ujian, melihat hasil seleksi, melakukan daftar ulang, sampai resmi menjadi mahasiswa dengan NIM. | Munculkan empat topik berikutnya. | Muncul: Ujian, Hasil seleksi, Daftar ulang, NIM |
| 4 | 0:30 | Semua langkah dapat dilakukan sendiri dari laptop atau ponsel, cukup dengan peramban internet. | Tahan tampilan judul. |  |

<a id="adegan-1"></a>
### Adegan 1 — Tujuh Tahap Pendaftaran

**Waktu:** 0:38–1:26 (49 dtk) · **Jenis:** Diagram alur

**Visual:** Diagram tujuh tahap; tahap yang sedang dijelaskan diberi bingkai hijau.

**Teks layar:** judul "Tujuh tahap pendaftaran"; subjudul "Tahap yang sama tampil sebagai linimasa pada setiap kartu pendaftaran di Beranda portal.".

**Butir yang ditampilkan:**

1. **Isi formulir** — Pilih jalur, lengkapi data, pilih prodi, unggah dokumen.
2. **Pembayaran** — Bayar biaya pendaftaran sesuai jalur.
3. **Verifikasi** — Panitia memeriksa data dan dokumen.
4. **Seleksi** — Ujian atau penilaian sesuai jalur.
5. **Pengumuman** — Hasil terbit sesuai jadwal gelombang.
6. **Daftar ulang** — Terima tawaran, lengkapi data, bayar biaya awal.
7. **Mahasiswa** — NIM terbit, akun mahasiswa dikirim ke email.

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 0:38 | Sebelum mulai, kenali dulu tujuh tahap pendaftaran. Tahap ini juga tampil sebagai linimasa di portal Anda. | Tampilkan judul diagram. |  |
| 2 | 0:47 | Pertama, isi formulir: pilih jalur, lengkapi data diri, pilih program studi, dan unggah dokumen. | Sorot tahap 1. | Muncul: Isi formulir |
| 3 | 0:56 | Kedua, bayar biaya pendaftaran. Ketiga, panitia memverifikasi data dan dokumen Anda. | Sorot tahap 2 dan 3. | Muncul: Pembayaran, Verifikasi |
| 4 | 1:05 | Keempat, seleksi. Bentuknya bisa ujian berbasis komputer atau penilaian nilai rapor, tergantung jalur. | Sorot tahap 4. | Muncul: Seleksi |
| 5 | 1:14 | Kelima, pengumuman hasil sesuai jadwal. Keenam, daftar ulang bagi yang diterima. | Sorot tahap 5 dan 6. | Muncul: Pengumuman, Daftar ulang |
| 6 | 1:21 | Dan terakhir, Anda resmi menjadi mahasiswa dengan NIM. | Sorot tahap 7. | Muncul: Mahasiswa |

<a id="adegan-2"></a>
### Adegan 2 — Yang Perlu Disiapkan

**Waktu:** 1:26–2:17 (51 dtk) · **Jenis:** Kartu informasi

**Visual:** Enam kartu persiapan.

**Teks layar:** judul "Siapkan sebelum mendaftar"; subjudul "Ukuran & format pasti untuk setiap jalur tercantum pada halaman Jalur di website PMB.".

**Butir yang ditampilkan:**

1. **Email & nomor HP aktif** — Untuk verifikasi akun, masuk, dan menerima pemberitahuan.
2. **Data identitas** — NIK 16 digit (WNI) atau paspor (WNA); tempat & tanggal lahir sesuai dokumen.
3. **Data sekolah & rapor** — Nama sekolah, NPSN, NISN, jurusan, tahun lulus, nilai semester 1–5.
4. **Data orang tua/wali** — Nama, nomor HP, pendidikan, pekerjaan.
5. **Pas foto digital** — Rasio 3:4 potret, minimal 400×600 piksel, latar polos (default maks 1 MB).
6. **Pindaian dokumen** — Misalnya KTP/kartu pelajar (maks 2 MB) dan rapor 1 PDF (maks 5 MB).

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 1:27 | Siapkan beberapa hal ini lebih dulu supaya pengisian berjalan lancar. | Tampilkan judul. |  |
| 2 | 1:32 | Email dan nomor HP yang aktif. Email dipakai untuk verifikasi akun dan menerima pemberitahuan dari panitia. | Munculkan & sorot kartu Email. | Muncul: Email & nomor HP aktif |
| 3 | 1:41 | Data identitas sesuai dokumen resmi: NIK enam belas digit bagi WNI, atau nomor paspor bagi WNA. | Sorot kartu Data identitas. | Muncul: Data identitas |
| 4 | 1:50 | Data sekolah asal dan nilai rapor semester satu sampai lima, serta data orang tua atau wali. | Sorot kartu Data sekolah dan Orang tua. | Muncul: Data sekolah & rapor, Data orang tua/wali |
| 5 | 1:57 | Pas foto digital berwarna dengan rasio tiga banding empat, minimal empat ratus kali enam ratus piksel, dan berlatar polos. | Sorot kartu Pas foto. | Muncul: Pas foto digital |
| 6 | 2:06 | Serta pindaian dokumen sesuai jalur, misalnya KTP dan rapor. Format dan ukuran maksimalnya tercantum pada setiap persyaratan. | Sorot kartu Pindaian dokumen. | Muncul: Pindaian dokumen |

<a id="adegan-3"></a>
### Adegan 3 — Website PMB

**Waktu:** 2:17–2:52 (35 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot beranda website PMB (10-01).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb`
**Arahan demonstrasi (bila merekam aplikasi asli):** Buka alamat website PMB kampus tanpa login; gulir dari hero ke kartu gelombang, lalu arahkan kursor ke menu atas.
**Aset:** [`10-01-beranda.png`](../manual-book/pmb/assets/10-01-beranda.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 2:18 | Langkah pertama, buka website PMB kampus. Semua informasi pendaftaran bisa dilihat tanpa perlu masuk. | Tampilkan beranda penuh. | [1] — **Website PMB** — Halaman publik berisi informasi gelombang, jalur, program studi, biaya, jadwal, panduan, FAQ, berita, dan kontak panitia. |
| 2 | 2:27 | Bagian atas menampilkan hitung mundur penutupan gelombang yang sedang dibuka. | Zoom ke kotak hitung mundur. | **Hitung mundur** — Sisa waktu sampai pendaftaran gelombang yang sedang **Dibuka** ditutup. |
| 3 | 2:32 | Kartu gelombang pendaftaran memuat status, rentang tanggal, tanggal pengumuman, serta jalur dan biaya pendaftarannya. | Geser ke kartu gelombang dan sorot. | **Gelombang pendaftaran** — • Status **Dibuka**, **Segera**, atau **Ditutup** • Rentang pendaftaran & tanggal pengumuman • Jalur yang dibuka beserta biaya pendaftarannya |
| 4 | 2:41 | Gunakan menu di bagian atas untuk melihat program studi, jalur, biaya, jadwal, panduan, FAQ, dan berita. | Kembali ke atas, sorot menu utama. | **Menu website** — **Prodi · Jalur · Biaya · Jadwal · Panduan · FAQ · Berita · Cek Status · Kontak**, serta tombol **Masuk** dan **Daftar**. |

<a id="adegan-4"></a>
### Adegan 4 — Prodi, Jalur, Biaya, Jadwal

**Waktu:** 2:52–4:03 (1 mnt 12 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot detail prodi (10-05), detail jalur (10-07), biaya (10-08), jadwal (10-09).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/prodi/teknik-informatika`
**Arahan demonstrasi (bila merekam aplikasi asli):** Buka Prodi → klik satu prodi; buka Jalur → klik jalur Reguler; buka Biaya → pilih prodi & sistem kuliah; buka Jadwal.
**Aset:** [`10-05-prodi-detail.png`](../manual-book/pmb/assets/10-05-prodi-detail.png), [`10-07-jalur-detail.png`](../manual-book/pmb/assets/10-07-jalur-detail.png), [`10-08-biaya.png`](../manual-book/pmb/assets/10-08-biaya.png), [`10-09-jadwal.png`](../manual-book/pmb/assets/10-09-jadwal.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 2:53 | Di menu Prodi, klik salah satu program studi untuk melihat deskripsi, akreditasi, gelar, lama studi, prospek karier, dan rincian biaya. | Tampilkan detail prodi, sorot kartu Ringkasan. | [2] — **Program studi** — Deskripsi, akreditasi, gelar, lama studi, prospek karier, rincian biaya, dan jalur yang membuka prodi tersebut. |
| 2 | 3:04 | Kotak Jalur yang membuka menunjukkan jalur mana saja yang menerima prodi ini, lengkap dengan label kuota bila kampus mengaktifkannya. | Zoom ke kotak Jalur yang membuka. | **Jalur yang membuka** — Label **Kuota tersedia**, **Kuota terbatas**, atau **Kuota penuh** tampil bila kampus mengaktifkannya. Angka kuota tidak ditampilkan. |
| 3 | 3:13 | Di halaman jalur, pelajari syarat setiap gelombang: biaya pendaftaran, jumlah pilihan prodi, komponen seleksi, dan persyaratan dokumen. | Tampilkan detail Jalur Reguler, sorot kartu Gelombang 2. | [3] — **Detail jalur** — • Biaya pendaftaran & maksimal pilihan prodi • Komponen seleksi, misalnya rapor dan CBT • Persyaratan dokumen per gelombang |
| 4 | 3:23 | Perhatikan daftar persyaratan dokumen. Di sini tertulis dokumen wajib atau opsional, kapan diunggah, format, dan ukuran maksimalnya. | Zoom ke daftar Persyaratan dokumen. | **Persyaratan dokumen** — Contoh: **Pas foto** (wajib, JPEG/PNG maks 1 MB) dan **KTP/Kartu Pelajar** (wajib, maks 2 MB). Siapkan berkasnya sebelum mengisi formulir. |
| 5 | 3:35 | Menu Biaya menampilkan rincian biaya awal kuliah. Pilih program studi dan sistem kuliah untuk melihat komponen, nominal, dan skema cicilan. | Tampilkan halaman Biaya, klik pilihan Sistem kuliah, sorot tabel. | [4] — **Biaya kuliah** — Komponen biaya awal (misalnya Uang Pangkal, SPP Semester 1), nominal, skema cicilan, dan total biaya awal. — _Info:_ Potongan beasiswa jalur masuk/KIP diterapkan pada tagihan setelah pengumuman. |
| 6 | 3:46 | Menu Jadwal menampilkan linimasa setiap gelombang: pendaftaran, batas pembayaran, ujian, pengumuman, dan daftar ulang. | Tampilkan halaman Jadwal, sorot kartu Gelombang 2. | [5] — **Jadwal** — Catat batas pendaftaran, batas pembayaran, tanggal ujian, pengumuman, dan daftar ulang gelombang pilihan Anda. |
| 7 | 3:57 | Klik Tambahkan ke kalender agar seluruh jadwal masuk ke kalender di ponsel atau laptop Anda. | Klik tombol Tambahkan ke kalender. | **Tambahkan ke kalender** — Mengunduh berkas `jadwal-pmb.ics` yang dapat diimpor ke Google Calendar atau Outlook. |

<a id="adegan-5"></a>
### Adegan 5 — Membuat Akun

**Waktu:** 4:03–4:49 (46 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot formulir Daftar Akun PMB (11-01) dan halaman Cek email Anda (11-02).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/daftar`
**Arahan demonstrasi (bila merekam aplikasi asli):** Klik Daftar di website, isi formulir dengan data contoh, centang dua persetujuan, klik Daftar; tunjukkan halaman konfirmasi.
**Aset:** [`11-01-daftar-akun.png`](../manual-book/pmb/assets/11-01-daftar-akun.png), [`11-02-daftar-berhasil.png`](../manual-book/pmb/assets/11-02-daftar-berhasil.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 4:04 | Sekarang kita buat akun. Klik tombol Daftar di pojok kanan atas website. | Klik tombol Daftar di kanan atas. | [6] — **Buat akun pendaftar** — Satu akun dipakai untuk semua pendaftaran Anda di kampus tersebut. Akun pendaftar berbeda dari akun mahasiswa. |
| 2 | 4:10 | Isi nama lengkap persis seperti di ijazah, lalu email dan nomor HP yang aktif. | Zoom ke isian Nama, Email, Nomor HP. | **Data akun** — • **Nama lengkap** sesuai ijazah (min. 3 karakter) • **Email** aktif — untuk verifikasi & masuk • **Nomor HP (WhatsApp)** — opsional |
| 3 | 4:16 | Buat kata sandi minimal delapan karakter yang berisi huruf dan angka, lalu ulangi kata sandi yang sama. | Geser ke isian Kata sandi dan Ulangi kata sandi. | **Kata sandi** — Minimal **8 karakter**, berisi huruf dan angka. Indikator di bawahnya menunjukkan kekuatan sandi. — _Perhatian:_ Jangan bagikan kata sandi kepada siapa pun, termasuk panitia. |
| 4 | 4:23 | Pilih dari mana Anda mengetahui PMB kampus, lalu centang persetujuan Kebijakan Privasi dan Ketentuan Pendaftaran. | Sorot isian sumber informasi dan dua kotak centang. | **Persetujuan** — Centang **Kebijakan Privasi** dan **Ketentuan Pendaftaran** (wajib). Informasi promosi bersifat opsional dan dapat diubah kapan saja. |
| 5 | 4:32 | Klik Daftar. Tombol ini baru aktif setelah kedua persetujuan dicentang. | Klik tombol Daftar. | **Klik Daftar** — Selesaikan CAPTCHA bila ditampilkan, lalu klik **Daftar**. |
| 6 | 4:38 | Muncul halaman Cek email Anda. Buka email, lalu klik tautan verifikasi. Tautan ini berlaku dua puluh empat jam. | Tampilkan halaman Cek email Anda, sorot pesan hijau. | [7] — **Verifikasi email** — Buka email dari panitia dan klik tautan verifikasi (berlaku **24 jam**). Periksa juga folder spam. — _Info:_ Pesan yang sama tampil walaupun email sudah pernah terdaftar. Bila sudah punya akun, gunakan **Masuk** atau **Lupa kata sandi**. |

<a id="adegan-6"></a>
### Adegan 6 — Masuk & Verifikasi

**Waktu:** 4:49–5:21 (32 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot halaman Masuk Portal PMB (11-03) dan Beranda portal akun belum terverifikasi (11-07).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/masuk`
**Arahan demonstrasi (bila merekam aplikasi asli):** Buka Masuk, isi email & sandi; tunjukkan tautan Lupa kata sandi dan Tidak bisa akses email; masuk dengan akun yang belum diverifikasi untuk menampilkan peringatan.
**Aset:** [`11-03-masuk.png`](../manual-book/pmb/assets/11-03-masuk.png), [`11-07-belum-verifikasi.png`](../manual-book/pmb/assets/11-07-belum-verifikasi.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 4:50 | Setelah akun aktif, masuk lewat menu Masuk dengan email atau nomor HP dan kata sandi Anda. | Sorot isian lalu klik Masuk. | [8] — **Masuk ke portal** — Isi **Email atau nomor HP** dan **Kata sandi**, lalu klik **Masuk**. Sesi berlaku 12 jam sejak terakhir dipakai. |
| 2 | 4:57 | Lupa kata sandi? Klik Lupa kata sandi untuk menerima tautan pengaturan ulang. Bila kehilangan akses ke email dan HP sekaligus, klik Tidak bisa akses email. | Sorot dua tautan di bawah tombol Masuk. | **Lupa sandi & pemulihan** — • **Lupa kata sandi?** Tautan atur ulang dikirim ke email (atau kode OTP SMS bila kampus mengaktifkan SMS). • **Tidak bisa akses email?** Ajukan pemulihan dengan foto identitas; diproses panitia maks. 2 hari kerja. |
| 3 | 5:10 | Bila akun belum terverifikasi, Beranda portal menampilkan peringatan kuning. Klik Kirim ulang email verifikasi bila tautannya sudah kedaluwarsa. | Tampilkan Beranda portal dengan peringatan; klik Kirim ulang email verifikasi. | **Akun belum terverifikasi** — Klik **Kirim ulang email verifikasi**. Bila kampus mengaktifkan SMS, gunakan **Verifikasi lewat SMS** dengan kode OTP 6 digit. — _Perhatian:_ Akun yang belum terverifikasi belum bisa membuat pendaftaran. |

<a id="adegan-7"></a>
### Adegan 7 — Memilih Gelombang & Jalur

**Waktu:** 5:21–6:06 (44 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot Pilih Gelombang & Jalur (12-02) dan Beranda portal dengan satu draf (12-03).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/daftar`
**Arahan demonstrasi (bila merekam aplikasi asli):** Masuk portal dengan akun terverifikasi; klik Daftar Baru; tunjukkan kartu jalur; klik Daftar jalur ini; kembali ke Beranda.
**Aset:** [`12-02-daftar-baru.png`](../manual-book/pmb/assets/12-02-daftar-baru.png), [`12-03-portal-draf.png`](../manual-book/pmb/assets/12-03-portal-draf.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 5:22 | Di portal, klik Daftar Baru. Halaman ini hanya menampilkan jalur dan gelombang yang sedang dibuka. | Klik menu Daftar Baru. | [9] — **Pilih gelombang & jalur** — Menu portal: **Beranda · Profil · Daftar Baru · Pesan · Notifikasi · Akun**. Data di **Profil** dipakai ulang oleh semua pendaftaran Anda. |
| 2 | 5:30 | Setiap kartu menjelaskan jalur, biaya pendaftaran beserta waktu bayarnya, jumlah maksimal pilihan prodi, tanggal tutup, dan catatan panitia. | Zoom ke kartu Reguler — Gelombang 2. | **Isi kartu jalur** — • Biaya pendaftaran & waktu bayar (*sebelum* atau *setelah* kirim formulir) • Maksimal pilihan prodi • Tanggal tutup & catatan panitia |
| 3 | 5:41 | Klik Daftar jalur ini pada jalur pilihan Anda. Draf pendaftaran langsung dibuat dan formulir terbuka. | Klik tombol Daftar jalur ini. | **Daftar jalur ini** — Draf dibuat dan Anda langsung masuk ke formulir. — _Perhatian:_ Secara default satu akun hanya boleh memiliki **1 pendaftaran aktif per periode** (dapat diatur kampus). |
| 4 | 5:49 | Di Beranda, setiap pendaftaran tampil sebagai kartu dengan linimasa tahap, label status, batas waktu, dan tombol langkah berikutnya. | Tampilkan Beranda dengan kartu draf; sorot linimasa dan batas waktu. | [10] — **Beranda portal** — Linimasa **Isi formulir → Pembayaran → Verifikasi → Seleksi → Pengumuman → Daftar ulang → Mahasiswa**. Label kanan atas = tahap saat ini. |
| 5 | 5:59 | Klik Lanjutkan formulir untuk kembali mengisi kapan saja, selama gelombang masih dibuka. | Klik Lanjutkan formulir. | **Lanjutkan formulir** — Draf bisa dilanjutkan kapan saja selama pendaftaran gelombang masih dibuka. — _Perhatian:_ Draf yang belum dikirim saat gelombang ditutup dibatalkan otomatis (status *Tidak dikirim*). |

<a id="adegan-8"></a>
### Adegan 8 — Formulir: Data Diri

**Waktu:** 6:06–6:47 (41 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot langkah Data diri (12-06).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/formulir`
**Arahan demonstrasi (bila merekam aplikasi asli):** Buka formulir draf; tunjukkan menu tujuh langkah; isi data diri; tunjukkan peringatan NIK; klik Simpan data diri.
**Aset:** [`12-06-formulir-data-diri.png`](../manual-book/pmb/assets/12-06-formulir-data-diri.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 6:06 | Formulir terdiri dari tujuh langkah di sisi kiri. Centang hijau berarti langkah sudah lengkap, tanda seru kuning berarti masih ada yang kurang. | Sorot menu langkah di kiri. | [11] — **Tujuh langkah formulir** — **Data diri · Pendidikan · Pilihan prodi · Data pendukung · Dokumen · Pembayaran · Pernyataan & kirim**. — _Info:_ Ikon ✓ hijau = lengkap · ikon ! kuning = masih ada yang kurang. |
| 2 | 6:17 | Pada Data diri, isi nama lengkap, kewarganegaraan, NIK, tempat dan tanggal lahir, jenis kelamin, agama, dan alamat sesuai dokumen. | Zoom ke isian identitas. | **Data diri** — Isi sesuai KTP/akta dan ijazah. Tanda ***** berarti wajib. NIK tampil tersamar demi keamanan; untuk menggantinya klik kolom lalu ketik NIK lengkap. |
| 3 | 6:29 | Bila tanggal lahir tidak cocok dengan digit NIK, muncul peringatan kuning. Periksa kembali data Anda. | Sorot peringatan kuning. | **Peringatan NIK** — Peringatan tidak menghalangi penyimpanan, tetapi akan diperiksa verifikator. Pastikan data benar. |
| 4 | 6:37 | Klik Simpan data diri. Ingat, setiap langkah punya tombol simpan sendiri, jadi simpan dulu sebelum pindah langkah. | Geser ke bawah, klik Simpan data diri. | **Simpan setiap langkah** — Klik **Simpan data diri** (aktif bila ada perubahan). — _Perhatian:_ Perubahan yang belum disimpan akan hilang bila halaman ditutup atau Anda berpindah langkah. |

<a id="adegan-9"></a>
### Adegan 9 — Formulir: Pendidikan

**Waktu:** 6:47–7:31 (45 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot langkah Pendidikan (12-07) dan dialog Nilai rapor (12-08).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/formulir`
**Arahan demonstrasi (bila merekam aplikasi asli):** Klik Tambah riwayat pendidikan dan isi data sekolah; klik Nilai rapor, isi semester 1–5; klik Simpan nilai.
**Aset:** [`12-07-formulir-pendidikan.png`](../manual-book/pmb/assets/12-07-formulir-pendidikan.png), [`12-08-nilai-rapor.png`](../manual-book/pmb/assets/12-08-nilai-rapor.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 6:48 | Pada langkah Pendidikan, klik Tambah riwayat pendidikan, lalu isi data sekolah: jenjang, nama sekolah, NPSN, jurusan, tahun lulus, dan NISN. | Klik Tambah riwayat pendidikan. | [12] — **Riwayat pendidikan** — Isi jenjang, nama sekolah, NPSN, jurusan, tahun lulus, kota/provinsi, dan NISN. Centang **Masih kelas akhir** bila belum lulus. |
| 2 | 7:01 | Riwayat yang dipakai untuk pendaftaran ini diberi label Dipakai untuk pendaftaran. Bila belum, klik Pilih. | Zoom ke kartu riwayat; sorot label hijau. | **Riwayat utama** — Label **Dipakai untuk pendaftaran** menandai riwayat yang dinilai panitia. |
| 3 | 7:10 | Untuk lulusan SMA sederajat, klik Nilai rapor, lalu isi nilai semester satu sampai lima dengan skala nol sampai seratus. | Klik tombol Nilai rapor. | **Nilai rapor** — Mata pelajaran awal: Matematika, Bahasa Indonesia, Bahasa Inggris. Tambah mapel lain lewat **Tambah mata pelajaran**. |
| 4 | 7:19 | Tambahkan mata pelajaran lain bila perlu, lalu klik Simpan nilai. Nilai rapor bisa menjadi komponen penilaian seleksi, jadi isilah selengkap mungkin. | Tampilkan dialog Nilai rapor; sorot tabel; klik Simpan nilai. | **Simpan nilai** — Isi nilai semester 1–5 (skala 0–100), lalu klik **Simpan nilai**. — _Info:_ Komponen seleksi bersumber rapor memakai nilai ini, misalnya rata-rata semester 1–5. |

<a id="adegan-10"></a>
### Adegan 10 — Formulir: Pilihan Prodi

**Waktu:** 7:31–7:50 (19 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot langkah Pilihan prodi (12-09).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/formulir`
**Arahan demonstrasi (bila merekam aplikasi asli):** Pilih prodi pada Pilihan 1, klik Tambah pilihan, atur urutan dengan panah, klik Simpan pilihan.
**Aset:** [`12-09-formulir-pilihan-prodi.png`](../manual-book/pmb/assets/12-09-formulir-pilihan-prodi.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 7:32 | Di langkah Pilihan prodi, pilih program studi dan sistem kuliah pada Pilihan 1. | Klik dropdown Pilihan 1. | [13] — **Pilihan prodi** — Daftar berisi kombinasi **program studi — sistem kuliah** yang ditawarkan jalur Anda. |
| 2 | 7:38 | Bila jalur mengizinkan lebih dari satu pilihan, klik Tambah pilihan. Atur urutan prioritas dengan tombol panah, lalu klik Simpan pilihan. | Sorot Pilihan 2 dan tombol panah; klik Simpan pilihan. | **Urutan prioritas** — Gunakan panah ↑ ↓ untuk mengatur urutan dan ikon tempat sampah untuk menghapus. Klik **Simpan pilihan**. — _Info:_ Sistem menempatkan Anda di Pilihan 1 bila skor memenuhi dan kursi tersedia; bila tidak, baru dicoba Pilihan 2. |

<a id="adegan-11"></a>
### Adegan 11 — Formulir: Data Pendukung

**Waktu:** 7:50–8:21 (31 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot langkah Data pendukung (12-10).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/formulir`
**Arahan demonstrasi (bila merekam aplikasi asli):** Klik Tambah orang tua/wali dan isi; pilih Sumber informasi PMB; jawab isian tambahan; klik Simpan data pendukung.
**Aset:** [`12-10-formulir-data-pendukung.png`](../manual-book/pmb/assets/12-10-formulir-data-pendukung.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 7:51 | Langkah Data pendukung dimulai dengan data orang tua atau wali. Klik Tambah orang tua atau wali, lalu isi hubungan, nama, nomor HP, dan data lainnya. | Sorot daftar orang tua; klik Tambah orang tua/wali. | [14] — **Orang tua / wali** — Isi hubungan (Ayah, Ibu, Wali, Kontak darurat), nama lengkap, nomor HP, pendidikan, pekerjaan, dan penghasilan. |
| 2 | 8:04 | Pilih sumber informasi PMB, isi kode referal bila ada, dan centang minat beasiswa bila Anda berminat. | Geser ke kartu Data pendukung; sorot isian. | **Data pendukung** — • **Sumber informasi PMB** (wajib) • Kode referal (opsional) • Minat program beasiswa • Jalur KIP: Nomor KIP & nomor pendaftaran KIP Kuliah |
| 3 | 8:12 | Jawab juga isian tambahan dari kampus, misalnya ukuran jas almamater atau motivasi, lalu klik Simpan data pendukung. | Sorot Isian tambahan; klik Simpan data pendukung. | **Isian tambahan** — Pertanyaan tambahan berbeda per jalur. Field bertanda ***** wajib diisi. — _Info:_ Jalur Prestasi: tambahkan prestasi Anda, lalu unggah sertifikatnya di langkah Dokumen. |

<a id="adegan-12"></a>
### Adegan 12 — Formulir: Dokumen

**Waktu:** 8:21–8:54 (32 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot langkah Dokumen (13-01).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/formulir`
**Arahan demonstrasi (bila merekam aplikasi asli):** Buka langkah Dokumen; klik Unggah pada KTP dan pilih berkas; tunjukkan tombol Lihat dan Ganti pada pas foto.
**Aset:** [`13-01-dokumen.png`](../manual-book/pmb/assets/13-01-dokumen.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 8:22 | Di langkah Dokumen, tampil daftar dokumen yang diminta jalur Anda, lengkap dengan format, ukuran maksimal, dan instruksi panitia. | Sorot daftar dokumen. | [15] — **Unggah dokumen** — Tanda ***** merah = wajib. Setiap baris memuat format, ukuran maksimal, instruksi, versi berkas, dan status. |
| 2 | 8:32 | Klik Unggah, lalu pilih berkas dari perangkat Anda. Tunggu sampai muncul pesan Berkas diunggah. | Zoom; klik Unggah pada baris KTP. | **Cara mengunggah** — Klik **Unggah**, pilih berkas, lalu tunggu pesan *"Berkas diunggah."* Berkas yang melebihi batas ukuran ditolak sebelum dikirim. |
| 3 | 8:40 | Dokumen yang sudah diunggah bisa dilihat lewat tombol Lihat, atau diganti lewat tombol Ganti selama belum diperiksa. | Sorot tombol Lihat dan Ganti pada pas foto. | **Lihat & Ganti** — Status dokumen: **Belum diunggah · Diunggah · Diperiksa · Disetujui · Perlu perbaikan · Ditolak**. |
| 4 | 8:48 | Kotak Belum lengkap di bagian atas menyebutkan dokumen wajib yang belum diunggah. | Kembali ke tampilan penuh; sorot kotak Belum lengkap. | **Kotak Belum lengkap** — Menyebutkan apa saja yang masih kurang pada langkah ini. — _Perhatian:_ Pas foto minimal **400 × 600 piksel** dengan rasio **3 : 4** potret. Foto yang tidak memenuhi ditolak saat diunggah. |

<a id="adegan-13"></a>
### Adegan 13 — Membayar Biaya Pendaftaran

**Waktu:** 8:54–9:56 (1 mnt 2 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot langkah Pembayaran: tagihan (13-02), instruksi transfer dengan kode unik (13-03), tagihan lunas (13-04).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/formulir`
**Arahan demonstrasi (bila merekam aplikasi asli):** Buka langkah Pembayaran; pilih Transfer Bank; klik Buat instruksi pembayaran; tunjukkan nominal + kode unik; unggah bukti; tunjukkan status Lunas dan Unduh kuitansi.
**Aset:** [`13-02-pembayaran-draf.png`](../manual-book/pmb/assets/13-02-pembayaran-draf.png), [`13-03-pembayaran-menunggu-verifikasi.png`](../manual-book/pmb/assets/13-03-pembayaran-menunggu-verifikasi.png), [`13-04-pembayaran-lunas.png`](../manual-book/pmb/assets/13-04-pembayaran-lunas.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 8:54 | Langkah Pembayaran menampilkan tagihan biaya pendaftaran: nomor tagihan, total, sisa, dan jatuh tempo. | Zoom ke ringkasan tagihan. | [16] — **Tagihan pendaftaran** — Nomor tagihan `INV-…`, total, sudah dibayar, sisa, dan jatuh tempo (mengikuti batas pembayaran gelombang). — _Info:_ Jalur tanpa biaya menampilkan "Jalur ini tidak memungut biaya pendaftaran." |
| 2 | 9:03 | Pada Bayar sekarang, pilih metode Transfer Bank. Bila punya kode voucher, masukkan lalu klik Terapkan. Kemudian klik Buat instruksi pembayaran. | Sorot Metode pembayaran & Kode voucher; klik Buat instruksi pembayaran. | **Bayar sekarang** — • Pilih **Metode pembayaran** • (Opsional) **Kode voucher** → **Terapkan** • Klik **Buat instruksi pembayaran** |
| 3 | 9:16 | Instruksi transfer menampilkan nominal yang harus dibayar, termasuk tiga digit kode unik, serta rekening tujuan kampus. | Tampilkan instruksi transfer; sorot nominal dan langkah transfer. | **Transfer TEPAT sesuai nominal** — Nominal sudah termasuk **kode unik 3 digit** (contoh Rp250.663 untuk tagihan Rp250.000) agar mudah dicocokkan. Instruksi berlaku 24 jam (default). — _Perhatian:_ Transfer dengan nominal persis, jangan dibulatkan. |
| 4 | 9:24 | Setelah transfer, klik Unggah bukti transfer, isi data transfernya, lalu simpan. Statusnya menjadi menunggu verifikasi keuangan. | Sorot baris status bukti transfer. | **Unggah bukti transfer** — Isi foto/PDF bukti, bank pengirim, nama pemilik rekening, tanggal & nominal transfer, lalu **Simpan**. — _Info:_ Petugas keuangan memverifikasi paling lambat 1 hari kerja (target yang ditampilkan aplikasi). |
| 5 | 9:35 | Ingin bayar tunai? Datang ke loket kampus dan sebutkan nomor tagihan Anda. Bila kampus mengaktifkan pembayaran daring, tersedia juga virtual account atau QRIS. | Tampilan penuh; sorot nomor tagihan di judul kartu. | **Cara bayar lainnya** — • **Loket kampus (tunai)**: sebutkan nomor tagihan; petugas mencatat dan tagihan langsung lunas. • **Virtual Account / QRIS**: hanya bila kampus sudah mengaktifkan gateway pembayaran. — _Info:_ Buka langkah Pembayaran minimal sekali agar tagihan terbit sebelum ke loket. |
| 6 | 9:48 | Setelah diverifikasi, tagihan berstatus Lunas dan tombol Unduh kuitansi tersedia. | Tampilkan tagihan lunas; klik Unduh kuitansi. | **Lunas** — Unduh kuitansi sebagai bukti. **Riwayat transaksi** mencatat semua pembayaran Anda. — _Berhasil:_ Formulir yang sudah dikirim dan lunas masuk antrean verifikasi. |

<a id="adegan-14"></a>
### Adegan 14 — Mengirim Formulir

**Waktu:** 9:56–10:36 (41 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot langkah Pernyataan & kirim (12-11).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/formulir`
**Arahan demonstrasi (bila merekam aplikasi asli):** Buka langkah Pernyataan & kirim; centang pernyataan; tunjukkan tombol nonaktif bila ada langkah belum lengkap; lengkapi lalu klik Kirim formulir.
**Aset:** [`12-11-pernyataan-kirim.png`](../manual-book/pmb/assets/12-11-pernyataan-kirim.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 9:56 | Langkah terakhir: Pernyataan dan kirim. Baca pernyataannya, lalu centang kotak persetujuan. | Zoom ke kotak pernyataan; klik kotak centang. | [17] — **Pernyataan & kirim** — Baca setiap pernyataan, lalu centang **Saya menyatakan data yang saya isi benar dan menyetujui pernyataan di atas**. |
| 2 | 10:05 | Bila masih ada langkah yang belum lengkap, muncul peringatan dan tombol Kirim formulir belum bisa diklik. Lengkapi dulu langkah yang bertanda kuning. | Tampilan penuh; sorot peringatan dan langkah bertanda kuning. | **Belum lengkap?** — _Perhatian:_ Tombol **Kirim formulir** nonaktif selama ada langkah bertanda ! kuning atau pernyataan belum dicentang. |
| 3 | 10:15 | Perhatikan juga waktu bayar jalur Anda. Pada jalur bayar sebelum kirim, formulir baru bisa dikirim setelah tagihan lunas. | Sorot langkah Pembayaran di menu kiri. | **Waktu bayar** — • **Sebelum kirim formulir**: lunasi dulu, baru formulir bisa dikirim. • **Setelah kirim formulir**: kirim dulu, lalu bayar. Verifikasi dimulai setelah lunas. |
| 4 | 10:25 | Bila semua sudah lengkap, klik Kirim formulir. Nomor pendaftaran langsung terbit dan dikirim ke email. Simpan nomor ini baik-baik. | Klik Kirim formulir. | **Nomor pendaftaran** — Contoh `PMB2027-REG-000001`. Dipakai untuk **Cek Status** dan komunikasi dengan panitia. — _Perhatian:_ Kirim sebelum pendaftaran gelombang ditutup. Setelah dikirim, data identitas terkunci. |

<a id="adegan-15"></a>
### Adegan 15 — Bila Diminta Perbaikan

**Waktu:** 10:36–11:07 (30 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot Beranda portal saat perlu perbaikan (13-05) dan formulir mode perbaikan (13-06).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal`
**Arahan demonstrasi (bila merekam aplikasi asli):** Gunakan akun yang diminta perbaikan oleh verifikator; tunjukkan peringatan di Beranda; buka formulir, ganti dokumen yang diminta, kirim ulang.
**Aset:** [`13-05-portal-perlu-perbaikan.png`](../manual-book/pmb/assets/13-05-portal-perlu-perbaikan.png), [`13-06-formulir-perbaikan.png`](../manual-book/pmb/assets/13-06-formulir-perbaikan.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 10:37 | Setelah formulir dikirim, panitia memverifikasi data dan dokumen. Bila ada yang perlu diperbaiki, Beranda menampilkan peringatan beserta batas waktunya. | Zoom ke kartu pendaftaran; sorot peringatan perbaikan. | [18] — **Perlu perbaikan** — Tahap berubah menjadi **Perlu perbaikan**. Anda juga menerima email pemberitahuan. — _Perhatian:_ Batas perbaikan default **72 jam** sejak diminta, dan tidak melewati batas perbaikan formulir gelombang. |
| 2 | 10:49 | Buka formulir. Kotak kuning menjelaskan catatan panitia serta dokumen atau data yang harus diperbaiki. | Tampilkan formulir mode perbaikan; sorot kotak catatan. | **Catatan panitia** — Baca catatan dengan teliti. Hanya data/dokumen yang diminta yang bisa diubah; isian lain terkunci. |
| 3 | 10:58 | Ganti dokumen yang diminta, lalu buka Pernyataan dan kirim, centang pernyataan, dan klik Kirim formulir lagi. | Zoom ke baris pas foto; klik Ganti. | **Kirim ulang** — Setelah memperbaiki, buka **Pernyataan & kirim**, centang, lalu **Kirim formulir**. Pendaftaran kembali ke antrean verifikasi. |

<a id="adegan-16"></a>
### Adegan 16 — Ujian & Kartu Peserta

**Waktu:** 11:07–12:21 (1 mnt 14 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot Ujian & Kartu Peserta (14-02), halaman CBT (14-03), dan setelah ujian (14-04).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/ujian`
**Arahan demonstrasi (bila merekam aplikasi asli):** Dengan akun terverifikasi di jalur berujian: buka Ujian & kartu peserta; unduh kartu; pada jam sesi klik Mulai ujian CBT; jawab soal; kirim jawaban.
**Aset:** [`14-02-ujian-siap-cbt.png`](../manual-book/pmb/assets/14-02-ujian-siap-cbt.png), [`14-03-cbt-mengerjakan.png`](../manual-book/pmb/assets/14-03-cbt-mengerjakan.png), [`14-04-ujian-kartu-peserta.png`](../manual-book/pmb/assets/14-04-ujian-kartu-peserta.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 11:07 | Bila lolos verifikasi dan jalur Anda memakai ujian, buka Ujian dan kartu peserta dari Beranda. Di sini tampil nomor peserta, sesi, mode, dan lokasi ujian. | Zoom ke kartu ujian; sorot nomor peserta, sesi, lokasi. | [19] — **Ujian & kartu peserta** — Tampil setelah pendaftaran **terverifikasi**. Jalur tanpa ujian menampilkan pesan bahwa seleksi berdasarkan berkas. |
| 2 | 11:21 | Klik Unduh kartu peserta. Kartu memuat pas foto, nomor peserta, sesi, lokasi, tata tertib, dan kode QR untuk check-in. | Klik Unduh kartu peserta. | **Kartu peserta (PDF)** — Bawa kartu peserta (cetak atau di ponsel) dan identitas asli sesuai instruksi panitia. Jangan bagikan kartu ini. |
| 3 | 11:33 | Untuk ujian berbasis komputer, klik Mulai ujian CBT tepat pada waktu sesi. Di luar waktu sesi, ujian tidak dapat dibuka. | Klik Mulai ujian CBT. | **Mulai ujian CBT** — Hanya berfungsi selama waktu sesi. Memulai CBT otomatis mencatat Anda **Hadir**. |
| 4 | 11:44 | Pilih jawaban pada setiap soal; jawaban tersimpan otomatis. Pindah soal dengan Sebelumnya, Berikutnya, atau nomor di navigasi soal. | Tampilkan halaman CBT; sorot pilihan jawaban dan navigasi soal. | **Mengerjakan CBT** — • Jawaban tersimpan otomatis (*Tersimpan*) • Sisa waktu di kanan atas dihitung dari jam server • Nomor hijau = sudah dijawab — _Perhatian:_ Jangan berpindah tab atau aplikasi — tercatat sebagai pelanggaran. |
| 5 | 11:56 | Pada soal terakhir, klik Kirim jawaban. Bila waktu habis, jawaban dikirim otomatis. Bila koneksi terputus, buka lagi halaman ujian dan klik Lanjutkan ujian. | Sorot sisa waktu di kanan atas. | **Selesai mengerjakan** — Klik **Kirim jawaban** di soal terakhir. Waktu habis = terkirim otomatis. — _Info:_ Koneksi putus? Buka lagi halaman ujian, klik **Lanjutkan ujian**. Jawaban tersimpan tidak hilang; waktu tetap berjalan. |
| 6 | 12:10 | Setelah ujian, status berubah menjadi Hadir dan Jawaban CBT terkirim. Nilai CBT dihitung otomatis oleh sistem. | Tampilkan kartu ujian setelah ujian; sorot status. | **Setelah ujian** — _Info:_ Berhalangan? Klik **Ajukan jadwal ulang** sebelum ujian, isi alasan & bukti. Default maksimal 1 kali. — _Berhasil:_ Status **Hadir** dan **Jawaban CBT terkirim**. Hasil diumumkan sesuai jadwal. |

<a id="adegan-17"></a>
### Adegan 17 — Hasil Seleksi & Surat Penerimaan

**Waktu:** 12:21–13:15 (54 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot hasil Diterima (15-02), Cadangan (15-08), dan verifikasi surat penerimaan (10-17).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/hasil`
**Arahan demonstrasi (bila merekam aplikasi asli):** Setelah waktu pengumuman: buka Hasil seleksi; unduh surat penerimaan; pindai QR pada surat untuk membuka halaman verifikasi.
**Aset:** [`15-02-hasil-diterima.png`](../manual-book/pmb/assets/15-02-hasil-diterima.png), [`15-08-hasil-cadangan.png`](../manual-book/pmb/assets/15-08-hasil-cadangan.png), [`10-17-verifikasi-loa.png`](../manual-book/pmb/assets/10-17-verifikasi-loa.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 12:22 | Hasil seleksi terbit tepat pada waktu pengumuman gelombang, setelah disahkan pejabat kampus. Anda juga menerima pemberitahuan lewat email. | Zoom ke kotak keputusan. | [20] — **Hasil seleksi** — Buka tombol **Hasil seleksi** di Beranda. Sebelum waktunya tampil "Hasil seleksi belum tersedia". |
| 2 | 12:32 | Ada tiga kemungkinan keputusan. Diterima, artinya Anda diterima pada program studi dan sistem kuliah yang tertera, bisa di pilihan pertama atau pilihan berikutnya. | Tahan pada keputusan Diterima. | **Diterima** — _Berhasil:_ Unduh surat penerimaan dan lakukan **daftar ulang** sebelum batasnya. |
| 3 | 12:45 | Cadangan, artinya Anda masuk daftar cadangan dan akan diberi tahu bila ada kursi kosong. Tidak diterima, artinya Anda dapat mencoba gelombang atau jalur lain yang masih dibuka. | Tampilkan hasil Cadangan. | **Cadangan & Tidak diterima** — • **Cadangan**: bila kursi tersedia, panitia mempromosikan Anda menjadi Diterima dengan batas daftar ulang tersendiri (default 7 hari). • **Tidak diterima**: silakan coba gelombang/jalur lain yang masih dibuka. |
| 4 | 12:58 | Bagi yang diterima, klik Unduh surat penerimaan. Surat ini bernomor dan memiliki kode QR untuk memeriksa keasliannya. | Kembali ke hasil Diterima; klik Unduh surat penerimaan. | **Surat penerimaan (LoA)** — PDF bernomor, contoh `LOA/PMB2027/00001`, dengan kode QR verifikasi. |
| 5 | 13:08 | Siapa pun dapat memindai kode QR itu untuk memastikan surat asli dan masih berlaku. | Tampilkan halaman Verifikasi Surat Penerimaan. | **Verifikasi keaslian** — Halaman verifikasi menampilkan status **asli dan berlaku**, **dicabut**, atau **tidak berlaku**, dengan nama tersamar. |

<a id="adegan-18"></a>
### Adegan 18 — Menerima atau Menolak Tawaran

**Waktu:** 13:15–13:44 (29 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot konfirmasi tawaran (15-04) dan dialog Tolak tawaran (15-05).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/daftar-ulang`
**Arahan demonstrasi (bila merekam aplikasi asli):** Dari hasil Diterima klik Daftar ulang; tunjukkan Terima tawaran dan Tolak tawaran (dialog), lalu batalkan dialog.
**Aset:** [`15-04-konfirmasi-tawaran.png`](../manual-book/pmb/assets/15-04-konfirmasi-tawaran.png), [`15-05-tolak-tawaran.png`](../manual-book/pmb/assets/15-05-tolak-tawaran.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 13:16 | Klik Daftar ulang di halaman hasil. Langkah pertama adalah konfirmasi tawaran: terima atau tolak. | Sorot kartu Konfirmasi tawaran dan batas daftar ulang. | [21] — **Konfirmasi tawaran** — Status awal **Menunggu konfirmasi**. Perhatikan **Batas daftar ulang**. |
| 2 | 13:24 | Klik Terima tawaran untuk melanjutkan. Perlu diingat, menerima tawaran ini menarik semua pendaftaran Anda yang lain pada periode yang sama. | Klik Terima tawaran. | **Terima tawaran** — Centang pernyataan bila ada, lalu klik **Terima tawaran**. — _Perhatian:_ Menerima tawaran menarik semua pendaftaran Anda yang lain pada periode yang sama. |
| 3 | 13:34 | Bila tidak ingin melanjutkan, klik Tolak tawaran dan isi alasannya. Kursi Anda akan diberikan kepada pendaftar cadangan. | Tampilkan dialog Tolak tawaran. | **Tolak tawaran** — _Info:_ Salah menolak? Segera hubungi panitia — penolakan dapat dipulihkan maksimal 24 jam. — _Akibat:_ Kursi Anda diberikan kepada pendaftar cadangan. |

<a id="adegan-19"></a>
### Adegan 19 — Daftar Ulang

**Waktu:** 13:44–14:29 (45 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot halaman Daftar Ulang yang sedang berjalan (15-06), digulir dari atas ke bawah.
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/daftar-ulang`
**Arahan demonstrasi (bila merekam aplikasi asli):** Setelah menerima tawaran: tunjukkan Kelengkapan; buat instruksi pembayaran biaya awal; isi Data lanjutan (PDDikti) dan Simpan data; unggah dokumen daftar ulang; klik Kirim daftar ulang.
**Aset:** [`15-06-daftar-ulang-berjalan.png`](../manual-book/pmb/assets/15-06-daftar-ulang-berjalan.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 13:45 | Setelah menerima tawaran, halaman Daftar Ulang menampilkan daftar Kelengkapan. Setiap butir harus berstatus Lolos atau Tidak berlaku. | Sorot kartu Kelengkapan. | [22] — **Kelengkapan daftar ulang** — Tawaran diterima, data PDDikti, data orang tua, dokumen daftar ulang, pembayaran minimum, pemeriksaan dokumen asli, dan lainnya. — _Info:_ Status butir: **Lolos · Menunggu · Tidak lolos · Tidak berlaku**. |
| 2 | 13:55 | Bayar tagihan biaya awal kuliah. Perhatikan nilai Minimum untuk daftar ulang. Komponen yang dapat dicicil dipecah per cicilan. | Geser ke kartu Biaya Awal Kuliah; sorot Minimum untuk daftar ulang dan rincian cicilan. | **Biaya awal kuliah** — Bayar minimal sebesar **Minimum untuk daftar ulang** (default: cicilan pertama + komponen yang tidak dapat dicicil), atau langsung lunas. — _Info:_ Cara bayar sama: transfer + unggah bukti, loket, atau gateway bila aktif. |
| 3 | 14:06 | Isi Data lanjutan PDDikti, seperti agama, nama ibu kandung, alamat, kode wilayah, jenis tinggal, dan alat transportasi, lalu klik Simpan data. | Geser ke Data lanjutan (PDDikti); klik Simpan data. | **Data lanjutan (PDDikti)** — Data wajib pelaporan ke PDDikti. Nama ibu kandung disimpan terenkripsi. |
| 4 | 14:19 | Lengkapi data orang tua atau wali, unggah dokumen daftar ulang seperti ijazah atau surat keterangan lulus, lalu klik Kirim daftar ulang. | Geser ke bawah; sorot Dokumen Daftar Ulang; klik Kirim daftar ulang. | **Kirim daftar ulang** — Setelah kelengkapan otomatis terpenuhi, klik **Kirim daftar ulang**. Petugas memvalidasi, termasuk memeriksa dokumen asli. — _Perhatian:_ Selesaikan sebelum **batas daftar ulang**. Lewat batas, status *Kedaluwarsa* dan kursi dapat dialihkan ke cadangan. |

<a id="adegan-20"></a>
### Adegan 20 — Resmi Menjadi Mahasiswa

**Waktu:** 14:29–15:00 (31 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot daftar ulang selesai dengan NIM (15-07) dan Beranda portal mahasiswa baru (15-01).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/daftar-ulang`
**Arahan demonstrasi (bila merekam aplikasi asli):** Setelah petugas menyetujui daftar ulang dan konversi selesai: tunjukkan NIM, Unduh bukti registrasi, info orientasi; kembali ke Beranda.
**Aset:** [`15-07-daftar-ulang-selesai.png`](../manual-book/pmb/assets/15-07-daftar-ulang-selesai.png), [`15-01-portal-mahasiswa.png`](../manual-book/pmb/assets/15-01-portal-mahasiswa.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 14:30 | Setelah petugas menyetujui daftar ulang, sistem membuat data mahasiswa Anda. Status berubah menjadi Resmi mahasiswa dan NIM terbit. | Sorot status dan NIM. | [23] — **Resmi mahasiswa** — _Berhasil:_ Status **Resmi mahasiswa** dan **NIM** tampil, contoh `27TEK30001` (format mengikuti kampus). |
| 2 | 14:40 | Di bagian bawah, klik Unduh bukti registrasi. Informasi akun SIAKAD dikirim ke email Anda, beserta informasi orientasi mahasiswa baru. | Geser ke kartu Selamat; klik Unduh bukti registrasi. | **Bukti registrasi** — Unduh **bukti registrasi** (PDF). Akun SIAKAD dikirim ke email. Baca info orientasi mahasiswa baru. |
| 3 | 14:51 | Linimasa di Beranda portal kini selesai seluruhnya. Selamat, Anda resmi menjadi mahasiswa! | Tampilkan Beranda portal; sorot linimasa lengkap. | **Semua tahap selesai** — _Berhasil:_ Selamat! Anda resmi menjadi mahasiswa. |

<a id="adegan-21"></a>
### Adegan 21 — Pesan, Akun & Cek Status

**Waktu:** 15:00–15:33 (33 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot Pesan (16-01), Akun & Keamanan (16-04), dan Cek Status di website (10-13).
**Alamat di bilah peramban:** `pmb.kampus.ac.id/pmb/portal/pesan`
**Arahan demonstrasi (bila merekam aplikasi asli):** Buka Pesan → Pesan baru; buka Akun; buka Cek Status di website dan isi nomor pendaftaran + tanggal lahir.
**Aset:** [`16-01-pesan.png`](../manual-book/pmb/assets/16-01-pesan.png), [`16-04-akun.png`](../manual-book/pmb/assets/16-04-akun.png), [`10-13-cek-status.png`](../manual-book/pmb/assets/10-13-cek-status.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 15:01 | Ada pertanyaan? Buka menu Pesan, klik Pesan baru, lalu tulis subjek dan pertanyaan Anda. Balasan panitia muncul di percakapan yang sama. | Klik Pesan baru; sorot area percakapan. | [24] — **Pesan ke panitia** — Lampirkan berkas bila perlu (JPG, PNG, PDF). Angka merah menandai pesan yang belum dibaca. — _Perhatian:_ Panitia **tidak pernah** meminta kata sandi atau kode OTP Anda. |
| 2 | 15:13 | Menu Akun dipakai untuk mengubah data akun, kata sandi, dan email, mengelola sesi aktif, serta mengunduh salinan data pribadi Anda. | Tampilkan halaman Akun; sorot Sesi aktif dan Data pribadi saya. | **Akun & keamanan** — • Data akun, kata sandi, ganti email • **Sesi aktif**: cabut perangkat lain • **Unduh data saya** — hak subjek data (UU PDP) • **Nonaktifkan akun** bila tidak ada pendaftaran berjalan |
| 3 | 15:23 | Untuk mengecek status tanpa masuk portal, buka Cek Status di website, isi nomor pendaftaran dan tanggal lahir, lalu klik Cek. | Tampilkan Cek Status; klik Cek; sorot hasil. | **Cek Status (tanpa login)** — Isi **Nomor pendaftaran** dan **Tanggal lahir**, lalu **Cek**. Tampil tahap saat ini, hasil seleksi, dan program studi. — _Info:_ Dibatasi 5 kali per menit dari satu alamat internet. |

<a id="adegan-22"></a>
### Adegan 22 — Kendala Umum

**Waktu:** 15:33–16:48 (1 mnt 15 dtk) · **Jenis:** Kartu informasi

**Visual:** Enam kartu kendala dan solusi.

**Teks layar:** judul "Kendala umum & solusinya"; subjudul "Masih buntu? Tanyakan lewat menu Pesan di portal atau kontak panitia di website.".

**Butir yang ditampilkan:**

1. **Email verifikasi tidak masuk** — Periksa folder spam; masuk portal lalu klik Kirim ulang email verifikasi.
2. **"Daftar jalur ini" nonaktif** — Akun belum terverifikasi, atau sudah ada pendaftaran aktif pada periode yang sama.
3. **Pas foto / berkas ditolak** — Pas foto rasio 3:4 (mis. 600×800 piksel); kompres berkas yang melebihi batas ukuran.
4. **Bukti transfer belum diverifikasi** — Tunggu maks. 1 hari kerja; pastikan nominal tepat termasuk kode unik.
5. **Tanggal lahir / Notifikasi tampak kosong** — Kendala tampilan yang diketahui pada versi ini; data tetap tersimpan. Pantau email & Beranda.
6. **"Terlalu banyak percobaan" / "Data telah diubah di tab lain"** — Tunggu satu menit lalu coba lagi / muat ulang halaman, lalu ulangi perubahan.

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 15:33 | Berikut beberapa kendala yang sering ditemui dan cara mengatasinya. | Tampilkan judul. |  |
| 2 | 15:38 | Email verifikasi tidak masuk? Periksa folder spam, lalu kirim ulang dari Beranda portal. Tombol Daftar jalur ini tidak aktif? Pastikan akun sudah terverifikasi dan belum ada pendaftaran aktif lain di periode yang sama. | Sorot dua kartu pertama. | Muncul: Email verifikasi tidak masuk, "Daftar jalur ini" nonaktif |
| 3 | 15:56 | Pas foto ditolak? Potong menjadi rasio tiga banding empat, misalnya enam ratus kali delapan ratus piksel. Berkas terlalu besar? Kompres dulu sampai di bawah batas ukuran. | Sorot kartu berkas ditolak. | Muncul: Pas foto / berkas ditolak |
| 4 | 16:10 | Bukti transfer belum diverifikasi? Tunggu paling lambat satu hari kerja, dan pastikan nominal transfer tepat termasuk kode unik. | Sorot kartu bukti transfer. | Muncul: Bukti transfer belum diverifikasi |
| 5 | 16:20 | Pada versi aplikasi saat ini, kolom tanggal lahir dan halaman Notifikasi bisa tampak kosong. Data Anda tetap aman; pantau status lewat Beranda portal dan email. | Sorot kartu kendala tampilan. | Muncul: Tanggal lahir / Notifikasi tampak kosong |
| 6 | 16:33 | Muncul pesan terlalu banyak percobaan? Tunggu sebentar lalu coba lagi. Muncul pesan data telah diubah di tab lain? Muat ulang halaman, lalu ulangi perubahan Anda. | Sorot kartu terakhir. | Muncul: "Terlalu banyak percobaan" / "Data telah diubah di tab lain" |

<a id="adegan-23"></a>
### Adegan 23 — Ringkasan

**Waktu:** 16:48–17:28 (40 dtk) · **Jenis:** Ringkasan & penutup

**Visual:** Ringkasan delapan langkah, ditutup layar akhir hijau.

**Teks layar:** judul "Ringkasan langkah pendaftaran".

**Butir yang ditampilkan:**

1. **Buka website PMB & pelajari jalur**
2. **Buat akun & verifikasi email**
3. **Pilih gelombang & jalur**
4. **Isi formulir — simpan tiap langkah**
5. **Unggah dokumen & bayar biaya pendaftaran**
6. **Kirim formulir, simpan nomor pendaftaran**
7. **Ikuti ujian & pantau hasil**
8. **Daftar ulang sebelum batas → NIM**

**Layar penutup:** "Selamat mendaftar!" — Panduan tertulis lengkap: Manual Book PMB untuk Calon Mahasiswa

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 16:48 | Mari kita rangkum. Buka website PMB dan pelajari jalur, buat akun dan verifikasi email, lalu pilih gelombang dan jalur. | Munculkan langkah 1–3. | Muncul: Buka website PMB & pelajari jalur, Buat akun & verifikasi email, Pilih gelombang & jalur |
| 2 | 16:59 | Isi formulir dan simpan setiap langkah, unggah dokumen, bayar biaya pendaftaran, lalu kirim formulir dan simpan nomor pendaftaran Anda. | Munculkan langkah 4–6. | Muncul: Isi formulir — simpan tiap langkah, Unggah dokumen & bayar biaya pendaftaran, Kirim formulir, simpan nomor pendaftaran |
| 3 | 17:09 | Ikuti ujian bila ada, pantau hasil pada waktu pengumuman, dan selesaikan daftar ulang sebelum batasnya untuk mendapatkan NIM. | Munculkan langkah 7–8. | Muncul: Ikuti ujian & pantau hasil, Daftar ulang sebelum batas → NIM |
| 4 | 17:18 | Panduan tertulis lengkapnya ada di Manual Book PMB untuk Calon Mahasiswa. Selamat mendaftar, dan sampai jumpa di kampus! | Tampilkan layar penutup. |  |

## Lampiran A — Ejaan pengucapan untuk pengisi suara

Takarir memakai ejaan asli; pengisi suara (manusia maupun sintetis) membacakannya seperti berikut.

| Tulisan | Dibaca |
|---|---|
| PDDikti | pe de dikti |
| PMB | pe em be |
| CBT | si bi ti |
| KTP | ka te pe |
| NIK | nik |
| NIM | nim |
| NISN | en i es en |
| NPSN | en pe es en |
| QRIS | kyu-ris |
| QR | kyu ar |
| HP | ha pe |
| SIAKAD | siakad |
| WNI | warga negara Indonesia |
| WNA | warga negara asing |
| online | onlain |

## Lampiran B — Cara membangun ulang video

Prasyarat: Node.js 20+, Microsoft Edge (atau Chrome dengan `BROWSER=chrome`), dan koneksi internet untuk suara sintetis.

```bash
cd docs/video-tutorial-pmb/source
npm install
node build_audio.mjs calon-mahasiswa        # narasi -> calon-mahasiswa.narration.wav + calon-mahasiswa.timeline.js
node build_naskah.mjs calon-mahasiswa       # naskah ini
node render.mjs calon-mahasiswa snap 30,90  # cuplikan frame ke snaps/ untuk diperiksa
node render.mjs calon-mahasiswa video ../tutorial-pmb-calon-mahasiswa.mp4
```

Pratinjau langsung di peramban: buka `source/player.html?v=calon-mahasiswa&play` (tanpa audio) atau `&t=<detik>` untuk satu frame. Ganti suara dengan variabel `VOICE` (mis. `id-ID-ArdiNeural`).
