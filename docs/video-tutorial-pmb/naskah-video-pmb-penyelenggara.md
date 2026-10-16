# Naskah Produksi — Video Tutorial PMB untuk Penyelenggara

| | |
|---|---|
| **Judul** | Video Tutorial PMB untuk Penyelenggara |
| **Sasaran penonton** | Panitia & staf PMB: Administrator PMB, petugas verifikasi, petugas keuangan, panitia seleksi, penguji/pengawas, pejabat pengesah, petugas daftar ulang, pimpinan/dekan/kaprodi, dan auditor. |
| **Tujuan** | Penonton memahami peran masing-masing dan dapat menjalankan seluruh alur operasional PMB di aplikasi: konfigurasi, verifikasi, keuangan, ujian, seleksi, pengesahan, pengumuman, daftar ulang, komunikasi, laporan, dan audit. |
| **Durasi** | 18:37 (26 adegan, 98 kalimat narasi) |
| **Berkas video** | [`tutorial-pmb-penyelenggara.mp4`](tutorial-pmb-penyelenggara.mp4) — MP4 H.264, 1920 × 1080, 25 fps, audio AAC; takarir tertanam |
| **Pengisi suara** | Suara sintetis Microsoft Edge `id-ID-GadisNeural` (Bahasa Indonesia). Dapat diganti suara manusia dengan naskah yang sama. |
| **Panduan tertulis pendamping** | [MANUAL-BOOK-PMB-PENYELENGGARA](../manual-book/pmb/MANUAL-BOOK-PMB-PENYELENGGARA.md) |
| **Sumber visual** | 46 screenshot aplikasi yang berjalan, dari [`docs/manual-book/pmb/assets`](../manual-book/pmb/assets/) (data demo fiktif *Universitas Nusantara Digital*) |
| **Acuan isi** | Rancangan [`docs/pmb/01–16`](../pmb/) dan manual book PMB per bab [`docs/manual-book/pmb`](../manual-book/pmb/README.md), dicocokkan dengan kode aplikasi terkini |
| **Sumber produksi** | [`source/penyelenggara.script.mjs`](source/penyelenggara.script.mjs) (data naskah), [`source/player.html`](source/player.html) (pemutar), cara membangun ulang di bagian akhir |

> Naskah ini **dibangkitkan otomatis** dari data yang sama dengan video (`build_naskah.mjs`), sehingga narasi, urutan adegan, tindakan layar, teks pendukung, dan waktu selalu sesuai dengan berkas video. Untuk mengubah isi, ubah `source/penyelenggara.script.mjs`, lalu bangun ulang audio, naskah, dan video.

## Daftar isi

1. [Ringkasan adegan](#ringkasan-adegan)
2. [Pedoman produksi](#pedoman-produksi)
3. [Naskah per adegan](#naskah-per-adegan)
4. [Lampiran A — Ejaan pengucapan untuk pengisi suara](#lampiran-a--ejaan-pengucapan-untuk-pengisi-suara)
5. [Lampiran B — Cara membangun ulang video](#lampiran-b--cara-membangun-ulang-video)

## Ringkasan adegan

| No | Adegan | Mulai | Durasi | Jenis visual |
|---:|---|---:|---:|---|
| 0 | [Pembuka](#adegan-0) | 0:00 | 40 dtk | Kartu judul |
| 1 | [Peran & Tugas](#adegan-1) | 0:40 | 58 dtk | Kartu informasi |
| 2 | [Alur Kerja Panitia](#adegan-2) | 1:38 | 57 dtk | Diagram alur |
| 3 | [Masuk & Menu PMB](#adegan-3) | 2:35 | 32 dtk | Screenshot aplikasi + sorotan |
| 4 | [Konfigurasi: Master Data](#adegan-4) | 3:08 | 36 dtk | Screenshot aplikasi + sorotan |
| 5 | [Konfigurasi: Template Formulir](#adegan-5) | 3:43 | 35 dtk | Screenshot aplikasi + sorotan |
| 6 | [Konfigurasi: Skema Seleksi](#adegan-6) | 4:18 | 47 dtk | Screenshot aplikasi + sorotan |
| 7 | [Konfigurasi: Periode & Gelombang](#adegan-7) | 5:05 | 48 dtk | Screenshot aplikasi + sorotan |
| 8 | [Konfigurasi: Membuka Jalur](#adegan-8) | 5:53 | 32 dtk | Screenshot aplikasi + sorotan |
| 9 | [Konfigurasi: Kuota & Biaya](#adegan-9) | 6:26 | 42 dtk | Screenshot aplikasi + sorotan |
| 10 | [Konten, Pengaturan & Publikasi](#adegan-10) | 7:08 | 43 dtk | Screenshot aplikasi + sorotan |
| 11 | [Dashboard PMB](#adegan-11) | 7:51 | 28 dtk | Screenshot aplikasi + sorotan |
| 12 | [Data Pendaftar](#adegan-12) | 8:19 | 49 dtk | Screenshot aplikasi + sorotan |
| 13 | [Verifikasi Administrasi](#adegan-13) | 9:08 | 1 mnt 15 dtk | Screenshot aplikasi + sorotan |
| 14 | [Keuangan PMB](#adegan-14) | 10:23 | 56 dtk | Screenshot aplikasi + sorotan |
| 15 | [Ujian Seleksi](#adegan-15) | 11:19 | 48 dtk | Screenshot aplikasi + sorotan |
| 16 | [Penilaian Ujian](#adegan-16) | 12:07 | 34 dtk | Screenshot aplikasi + sorotan |
| 17 | [Proses Seleksi](#adegan-17) | 12:40 | 46 dtk | Screenshot aplikasi + sorotan |
| 18 | [Pengesahan Hasil](#adegan-18) | 13:26 | 31 dtk | Screenshot aplikasi + sorotan |
| 19 | [Pengumuman & Keputusan](#adegan-19) | 13:58 | 41 dtk | Screenshot aplikasi + sorotan |
| 20 | [Daftar Ulang & Konversi](#adegan-20) | 14:39 | 49 dtk | Screenshot aplikasi + sorotan |
| 21 | [Komunikasi](#adegan-21) | 15:28 | 29 dtk | Screenshot aplikasi + sorotan |
| 22 | [Laporan & Audit](#adegan-22) | 15:57 | 39 dtk | Screenshot aplikasi + sorotan |
| 23 | [Kendala Umum](#adegan-23) | 16:36 | 48 dtk | Kartu informasi |
| 24 | [Perlu Dikonfirmasi Kampus](#adegan-24) | 17:24 | 36 dtk | Kartu informasi |
| 25 | [Ringkasan](#adegan-25) | 18:00 | 38 dtk | Ringkasan & penutup |

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

**Waktu:** 0:00–0:40 (40 dtk) · **Jenis:** Kartu judul

**Visual:** Kartu judul dengan sembilan topik yang muncul bertahap.

**Teks layar:** judul "Mengelola Penerimaan Mahasiswa Baru"; subjudul "Panduan operasional per peran: dari menyiapkan periode sampai calon mahasiswa resmi menjadi mahasiswa."; label "Video Tutorial · Penyelenggara PMB".

**Butir yang ditampilkan:**

1. **Peran & akses**
2. **Konfigurasi**
3. **Verifikasi**
4. **Keuangan**
5. **Ujian & nilai**
6. **Seleksi & pengesahan**
7. **Pengumuman**
8. **Daftar ulang**
9. **Komunikasi, laporan, audit**

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 0:00 | Halo, Bapak dan Ibu panitia PMB! Video ini memandu penggunaan modul Penerimaan Mahasiswa Baru di Civitas One dari sisi penyelenggara. | Tampilkan judul video. |  |
| 2 | 0:11 | Kita mulai dari peran dan hak akses, lalu konfigurasi periode, verifikasi berkas, dan keuangan. | Munculkan empat topik pertama. | Muncul: Peran & akses, Konfigurasi, Verifikasi, Keuangan |
| 3 | 0:20 | Setelah itu ujian dan penilaian, proses seleksi dan pengesahan, pengumuman, serta daftar ulang sampai konversi menjadi mahasiswa. | Munculkan empat topik berikutnya. | Muncul: Ujian & nilai, Seleksi & pengesahan, Pengumuman, Daftar ulang |
| 4 | 0:30 | Terakhir, komunikasi dengan pendaftar, laporan, dan audit. Silakan lompat ke bagian yang sesuai dengan peran Anda. | Munculkan topik terakhir. | Muncul: Komunikasi, laporan, audit |

<a id="adegan-1"></a>
### Adegan 1 — Peran & Tugas

**Waktu:** 0:40–1:38 (58 dtk) · **Jenis:** Kartu informasi

**Visual:** Sembilan kartu peran.

**Teks layar:** judul "Siapa mengerjakan apa?"; subjudul "Peran diberikan administrator lewat Pengaturan → Role Pengguna. Menu & tombol yang tampil mengikuti izin peran.".

**Butir yang ditampilkan:**

1. **Administrator PMB** — Konfigurasi, konten, pendaftar, pengumuman, daftar ulang. Langkah 1 pengesahan (Ketua Panitia).
2. **Petugas Verifikasi** — Memeriksa formulir & dokumen; pendaftaran loket.
3. **Petugas Keuangan** — Bukti transfer, loket, rekonsiliasi, refund.
4. **Panitia Seleksi** — Skema seleksi, ujian, nilai, run seleksi, ajukan pengesahan.
5. **Penguji / Pengawas (Dosen)** — Check-in & menilai peserta pada sesi yang ditugaskan.
6. **Pejabat Pengesah** — Mengesahkan hasil seleksi (langkah 2) & menyetujui koreksi.
7. **Petugas Daftar Ulang** — Memeriksa & menyetujui daftar ulang, memantau konversi.
8. **Pimpinan · Dekan · Kaprodi** — Dashboard & laporan; dekan/kaprodi terbatas prodinya.
9. **Auditor** — Baca data, laporan, dan Audit PMB.

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 0:41 | Modul PMB dijalankan oleh beberapa peran. Setiap peran hanya melihat menu dan tombol yang sesuai izinnya. | Tampilkan judul. |  |
| 2 | 0:49 | Administrator PMB mengatur konfigurasi dan operasional, sekaligus bertindak sebagai Ketua Panitia pada langkah pertama pengesahan hasil. | Sorot kartu Administrator PMB. | Muncul: Administrator PMB |
| 3 | 0:59 | Petugas Verifikasi memeriksa formulir dan dokumen. Petugas Keuangan memverifikasi pembayaran, rekonsiliasi, dan refund. | Sorot kartu Verifikasi dan Keuangan. | Muncul: Petugas Verifikasi, Petugas Keuangan |
| 4 | 1:09 | Panitia Seleksi mengelola skema, ujian, nilai, dan run seleksi. Dosen yang ditugaskan menjadi penguji atau pengawas sesi. | Sorot kartu Panitia Seleksi dan Penguji. | Muncul: Panitia Seleksi, Penguji / Pengawas (Dosen) |
| 5 | 1:20 | Pejabat Pengesah mengesahkan hasil seleksi. Petugas Daftar Ulang menyetujui daftar ulang yang menghasilkan mahasiswa baru. | Sorot kartu Pengesah dan Petugas Daftar Ulang. | Muncul: Pejabat Pengesah, Petugas Daftar Ulang |
| 6 | 1:29 | Pimpinan, dekan, dan kaprodi memantau dashboard dan laporan, sedangkan auditor membaca seluruh jejak audit. | Sorot kartu Pimpinan dan Auditor. | Muncul: Pimpinan · Dekan · Kaprodi, Auditor |

<a id="adegan-2"></a>
### Adegan 2 — Alur Kerja Panitia

**Waktu:** 1:38–2:35 (57 dtk) · **Jenis:** Diagram alur

**Visual:** Diagram tujuh fase kerja panitia.

**Teks layar:** judul "Alur kerja dalam satu periode".

**Butir yang ditampilkan:**

1. **Persiapan sistem** — Data awal, peran, penjadwal, domain. *(Admin sistem)*
2. **Konfigurasi** — Master data, template, skema, periode, kuota, biaya, konten → publikasi. *(Admin PMB · Panitia Seleksi)*
3. **Masa pendaftaran** — Verifikasi berkas & pembayaran, layanan pesan. *(Verifikator · Keuangan)*
4. **Ujian** — Jadwal, sesi, kartu peserta, check-in, nilai. *(Panitia · Penguji)*
5. **Seleksi & pengesahan** — Run seleksi, tinjau, ajukan, sahkan. *(Panitia Seleksi · Pengesah)*
6. **Pengumuman & daftar ulang** — Publikasi terjadwal, cadangan, daftar ulang, NIM. *(Admin · Keuangan · Petugas DU)*
7. **Penutupan** — Tutup & arsipkan periode, laporan akhir, audit. *(Admin PMB · Auditor)*

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 1:39 | Secara garis besar, pekerjaan panitia dalam satu periode PMB terbagi menjadi tujuh fase. | Tampilkan judul diagram. |  |
| 2 | 1:47 | Fase nol dan satu adalah persiapan: administrator sistem menyiapkan data awal, lalu Admin PMB dan Panitia Seleksi menyusun konfigurasi hingga periode dipublikasikan. | Sorot fase 0 dan 1. | Muncul: Persiapan sistem, Konfigurasi |
| 3 | 1:59 | Fase dua, masa pendaftaran: verifikator memeriksa berkas, keuangan memverifikasi pembayaran, dan panitia melayani pertanyaan. | Sorot fase 2. | Muncul: Masa pendaftaran |
| 4 | 2:09 | Fase tiga dan empat: pelaksanaan ujian dan penilaian, lalu run seleksi yang diajukan dan disahkan secara berjenjang. | Sorot fase 3 dan 4. | Muncul: Ujian, Seleksi & pengesahan |
| 5 | 2:18 | Fase lima, hasil terbit otomatis pada jadwal pengumuman, dilanjutkan daftar ulang dan pembentukan NIM. | Sorot fase 5. | Muncul: Pengumuman & daftar ulang |
| 6 | 2:26 | Fase enam, penutupan periode: tutup dan arsipkan periode, ekspor laporan akhir, dan tinjau audit. | Sorot fase 6. | Muncul: Penutupan |

<a id="adegan-3"></a>
### Adegan 3 — Masuk & Menu PMB

**Waktu:** 2:35–3:08 (32 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot aplikasi staf dengan grup menu PMB dan Konfigurasi PMB (02-02).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb`
**Arahan demonstrasi (bila merekam aplikasi asli):** Login di /login tab Dosen / Staf / Admin; buka sidebar grup PMB dan Konfigurasi PMB.
**Aset:** [`02-02-menu-pmb.png`](../manual-book/pmb/assets/02-02-menu-pmb.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 2:36 | Masuk ke Civitas One lewat halaman login staf, pilih tab Dosen, Staf, Admin, lalu isi email dan kata sandi Anda. | Tampilkan aplikasi staf setelah login. | [1] — **Login staf** — Buka `/login`, tab **Dosen / Staf / Admin**, isi **Email** & **Kata Sandi**, klik **Masuk**. |
| 2 | 2:45 | Di sidebar ada dua grup menu: PMB untuk pekerjaan operasional, dan Konfigurasi PMB untuk pengaturan. | Sorot grup menu PMB dan Konfigurasi PMB. | **Dua grup menu** — • **PMB**: Dashboard, Pendaftar, Verifikasi, Keuangan PMB, Ujian, Penilaian Ujian, Seleksi, Pengumuman, Daftar Ulang, Komunikasi, Laporan, Audit • **Konfigurasi PMB**: Periode & Gelombang, Jalur & Dokumen, Template, Skema, Konten, Profil Prodi, Pengaturan |
| 3 | 2:54 | Menu yang tampil mengikuti izin peran Anda. Pembatasan juga ditegakkan di server, termasuk aturan pemisahan tugas: pengaju tidak dapat menyetujui pengajuannya sendiri. | Tampilkan penuh; sorot nama & peran pengguna di kanan atas. | **Hak akses & pemisahan tugas** — Contoh: pengaju run seleksi ≠ pengesah; pengaju refund ≠ penyetuju; verifikator bukti bayar biaya awal ≠ penyetuju daftar ulang pendaftar yang sama. — _Perhatian:_ Data sensitif (NIK, nama ibu, KIP, rekening) tampil tersamar; membukanya wajib menyebut tujuan dan tercatat. |

<a id="adegan-4"></a>
### Adegan 4 — Konfigurasi: Master Data

**Waktu:** 3:08–3:43 (36 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot Master Data PMB, tab Jalur masuk (21-01).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/konfigurasi/master`
**Arahan demonstrasi (bila merekam aplikasi asli):** Konfigurasi PMB → Jalur & Dokumen; tunjukkan tab Jalur masuk, Sistem kuliah, Jenis dokumen; klik Tambah.
**Aset:** [`21-01-jalur-masuk.png`](../manual-book/pmb/assets/21-01-jalur-masuk.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 3:08 | Fase konfigurasi dikerjakan Administrator PMB. Mulai dari menu Konfigurasi PMB, Jalur dan Dokumen, yang berisi tiga tab: Jalur masuk, Sistem kuliah, dan Jenis dokumen. | Sorot tiga tab master data. | [2] — **Master data** — **Jalur masuk** (kode, nama, kategori, jenjang, berujian), **Sistem kuliah** (mis. Reguler, Kelas Karyawan), **Jenis dokumen** (format, ukuran maks, cek asli, sensitif). |
| 2 | 3:24 | Klik Tambah untuk membuat jalur, misalnya Reguler atau Prestasi. Kode jalur dipakai dalam nomor pendaftaran, dan kategori Prestasi memunculkan isian prestasi di formulir. | Klik tombol Tambah; sorot tabel jalur. | **Jalur masuk** — Kategori: reguler, prestasi, KIP/afirmasi, pindahan, pascasarjana, RPL, beasiswa, kerja sama, internasional. — _Info:_ Data awal dari `pmb:install`: 2 sistem kuliah & 15 jenis dokumen. Jalur dibuat sendiri oleh kampus. |
| 3 | 3:37 | Data yang sudah pernah dipakai sebaiknya dinonaktifkan, bukan dihapus. | Sorot kolom Status. | **Nonaktifkan, jangan hapus** — _Perhatian:_ Jalur nonaktif tidak dapat dibuka pada gelombang baru, tetapi data lama tetap utuh. |

<a id="adegan-5"></a>
### Adegan 5 — Konfigurasi: Template Formulir

**Waktu:** 3:43–4:18 (35 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot editor template formulir (22-02).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/konfigurasi/template`
**Arahan demonstrasi (bila merekam aplikasi asli):** Konfigurasi PMB → Template Formulir → Template baru; atur field inti; tambah field; Pratinjau skema; Publikasikan.
**Aset:** [`22-02-template-detail.png`](../manual-book/pmb/assets/22-02-template-detail.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 3:44 | Berikutnya Template Formulir. Template menentukan isian yang diminta dari pendaftar. Setiap field inti bisa dibuat wajib, opsional, atau disembunyikan. | Sorot kartu Aturan field inti. | [3] — **Template formulir** — Atur field inti (identitas, pendidikan, pilihan, data pendukung) lalu klik **Simpan aturan**. Tambah pertanyaan khusus kampus lewat **+ Field**. |
| 2 | 3:57 | Field tambahan, misalnya ukuran jas almamater atau motivasi, tampil di langkah Data pendukung pada portal pendaftar. | Geser ke Field tambahan. | **Field tambahan** — Tipe isian: teks, angka, tanggal, pilihan, centang, email, telepon, URL. Opsi, validasi, dan aturan tampil ditulis dalam format JSON. — _Info:_ Gunakan seksi `supporting` agar tanda "belum lengkap" muncul pada langkah yang benar. |
| 3 | 4:06 | Periksa dengan Pratinjau skema, lalu klik Publikasikan. Template yang terbit tidak bisa diubah; gunakan Buat versi baru untuk perubahan berikutnya. | Kembali ke atas; sorot tombol Terbit, Pratinjau skema, Buat versi baru. | **Berversi** — Status **Draf → Terbit → Pensiun**. Pendaftaran yang sudah berjalan tetap memakai versi lamanya. — _Perhatian:_ Hanya template berstatus **Terbit** yang bisa dipilih pada gelombang-jalur. |

<a id="adegan-6"></a>
### Adegan 6 — Konfigurasi: Skema Seleksi

**Waktu:** 4:18–5:05 (47 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot detail skema seleksi (23-02) dan simulasi (23-03).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/konfigurasi/skema`
**Arahan demonstrasi (bila merekam aplikasi asli):** Login sebagai Panitia Seleksi: Konfigurasi PMB → Skema Seleksi → Skema baru; + Komponen; Jalankan simulasi; Kunci skema.
**Aset:** [`23-02-skema-detail.png`](../manual-book/pmb/assets/23-02-skema-detail.png), [`23-03-skema-simulasi.png`](../manual-book/pmb/assets/23-03-skema-simulasi.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 4:19 | Skema seleksi disusun oleh Panitia Seleksi. Skema berisi komponen penilaian, misalnya kelengkapan administrasi, nilai rapor, dan tes CBT, beserta bobot dan ambang skor. | Sorot tabel komponen. | [4] — **Skema seleksi** — Setiap komponen: sumber nilai, tipe (angka atau lulus/tidak), rentang, **bobot**, nilai minimum, wajib, dan prioritas tie-break. |
| 2 | 4:33 | Skor akhir adalah rata-rata berbobot dari nilai yang dinormalisasi ke skala nol sampai seratus. Komponen lulus atau tidak harus lulus agar pendaftar eligible. | Sorot rumus di bawah judul Komponen. | **Cara menghitung** — Skor = Σ(bobot × nilai ternormalisasi) / Σ bobot. Contoh: rapor 90 (bobot 40) + TPA 100 (bobot 60) = **96**. — _Info:_ Pendaftar ditempatkan ke pilihan prioritas tertinggi yang kursinya masih ada dan ambangnya terpenuhi. |
| 3 | 4:45 | Uji skema dengan Jalankan simulasi memakai data contoh. Simulasi tidak menyimpan apa pun. | Tampilkan simulasi; klik Jalankan simulasi. | **Simulasi** — Isi contoh kuota dan nilai pendaftar (JSON), klik **Jalankan simulasi**, periksa skor, peringkat, dan penempatan. |
| 4 | 4:53 | Bila sudah benar, klik Kunci skema. Hanya skema terkunci yang bisa dipakai gelombang-jalur dan run seleksi; perubahan berikutnya melalui Buat versi baru. | Sorot label Terkunci dan Buat versi baru. | **Kunci skema** — _Perhatian:_ Skema terkunci tidak dapat diubah. Hasil run selalu memakai versi skema yang terkunci. |

<a id="adegan-7"></a>
### Adegan 7 — Konfigurasi: Periode & Gelombang

**Waktu:** 5:05–5:53 (48 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot detail periode tab Gelombang & jalur (20-02) dan form gelombang (20-03).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/konfigurasi/periode`
**Arahan demonstrasi (bila merekam aplikasi asli):** Konfigurasi PMB → Periode & Gelombang → Periode baru; buka detail; + Gelombang; isi jadwal berurutan; tunjukkan Perpanjang.
**Aset:** [`20-02-periode-gelombang-jalur.png`](../manual-book/pmb/assets/20-02-periode-gelombang-jalur.png), [`20-03-form-gelombang.png`](../manual-book/pmb/assets/20-03-form-gelombang.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 5:06 | Buka Periode dan Gelombang, lalu klik Periode baru. Isi kode, nama, tahun akademik masuk dengan semester ganjil, serta tanggal mulai dan selesai. | Tampilkan detail periode; sorot judul & kode periode. | [5] — **Periode PMB** — Satu siklus untuk satu tahun akademik masuk, contoh `PMB2027`. Kode periode menjadi awalan nomor pendaftaran. — _Info:_ Semester masuk (ganjil) harus sudah ada di modul Akademik. |
| 2 | 5:19 | Detail periode memiliki tiga tab: Gelombang dan jalur, Kuota, dan Biaya. Klik tambah Gelombang untuk membuat jadwal gelombang. | Sorot tab; klik + Gelombang. | **Tiga tab** — **Gelombang & jalur · Kuota · Biaya**. Pada versi terbaru, tabel di tab-tab ini dilengkapi baris filter (cari, status, fakultas, jalur, sistem). |
| 3 | 5:29 | Isi jadwal secara berurutan: pendaftaran dibuka dan ditutup, batas pembayaran, batas perbaikan formulir, ujian, pengumuman, lalu daftar ulang. | Tampilkan form Gelombang baru; sorot isian jadwal. | **Jadwal gelombang** — Urutan wajib: buka < tutup ≤ batas bayar ≤ batas perbaikan ≤ ujian ≤ pengumuman ≤ daftar ulang. — _Info:_ Centang **Gelombang ODS** untuk jadwal ujian fleksibel. |
| 4 | 5:42 | Status gelombang berubah otomatis sesuai jadwal. Untuk memperpanjang pendaftaran, klik Perpanjang, isi tanggal tutup baru dan alasannya. | Kembali ke detail periode; sorot status dan tombol Perpanjang. | **Perubahan setelah ada pendaftar** — _Perhatian:_ Perubahan jadwal, kuota, biaya, atau persyaratan wajib disertai **alasan** dan tercatat di audit. Biaya pendaftaran & syarat wajib tidak bisa diubah setelah ada formulir terkirim. |

<a id="adegan-8"></a>
### Adegan 8 — Konfigurasi: Membuka Jalur

**Waktu:** 5:53–6:26 (32 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot form Buka jalur pada gelombang (20-04) dan dialog Persyaratan dokumen (20-05).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/konfigurasi/periode`
**Arahan demonstrasi (bila merekam aplikasi asli):** Klik nama gelombang → + Buka jalur; isi biaya, waktu bayar, template & skema; Simpan; klik "N persyaratan" untuk mengatur dokumen.
**Aset:** [`20-04-form-gelombang-jalur.png`](../manual-book/pmb/assets/20-04-form-gelombang-jalur.png), [`20-05-persyaratan-dokumen.png`](../manual-book/pmb/assets/20-05-persyaratan-dokumen.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 5:54 | Klik nama gelombang, lalu Buka jalur. Isi biaya pendaftaran, waktu pembayaran, dan maksimal pilihan prodi. | Tampilkan form Buka jalur; sorot biaya, waktu bayar, maks pilihan. | [6] — **Gelombang-jalur** — **Biaya pendaftaran** (0 = gratis), **Waktu pembayaran** (sebelum/sesudah kirim formulir), **Maksimal pilihan prodi** (1–5). |
| 2 | 6:04 | Pilih template formulir yang terbit dan skema seleksi yang terkunci, lalu atur mode kuota dan catatan publik. Klik Simpan. | Sorot template, skema, mode kuota; klik Simpan. | **Template, skema, kuota** — Hanya template **terbit** dan skema **terkunci** yang dapat dipilih. — _Perhatian:_ Gunakan mode **Kuota periode**. Mode *Batas per gelombang* belum memiliki isian batas di layar. |
| 3 | 6:13 | Kemudian atur persyaratan dokumen lewat tautan persyaratan di tabel jalur: pilih jenis dokumen, tahap pendaftaran atau daftar ulang, wajib atau tidak, dan instruksinya. | Tampilkan dialog Persyaratan dokumen; sorot baris; klik Simpan. | **Persyaratan dokumen** — Urutan baris = urutan tampil di portal. Format & ukuran mengikuti master **Jenis dokumen**. — _Info:_ Dokumen tahap **Daftar ulang** (mis. ijazah/SKL) baru muncul bagi pendaftar saat daftar ulang. |

<a id="adegan-9"></a>
### Adegan 9 — Konfigurasi: Kuota & Biaya

**Waktu:** 6:26–7:08 (42 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot tab Kuota (20-07) dan form Komponen biaya (20-09).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/konfigurasi/periode`
**Arahan demonstrasi (bila merekam aplikasi asli):** Tab Kuota: ubah kapasitas/cadangan, Tambah baris, Simpan kuota. Tab Biaya: + Komponen, isi nominal, centang Dapat dicicil, Simpan.
**Aset:** [`20-07-kuota.png`](../manual-book/pmb/assets/20-07-kuota.png), [`20-09-form-komponen-biaya.png`](../manual-book/pmb/assets/20-09-form-komponen-biaya.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 6:26 | Di tab Kuota, isi kapasitas, cadangan, dan ambang skor untuk setiap kombinasi jalur, program studi, dan sistem kuliah. | Sorot tabel kuota dan kolom Kapasitas–Ambang. | [7] — **Kuota** — • **Kapasitas**: kursi diterima • **Cadangan**: 0 = tanpa batas • **Ambang skor**: opsional, menimpa ambang skema • **Ditawarkan**: hanya kombinasi tercentang yang bisa dipilih pendaftar |
| 2 | 6:37 | Tambah baris untuk kombinasi baru, atau salin dari periode sebelumnya. Isi alasan bila gelombang sudah dibuka, lalu klik Simpan kuota. | Sorot Tambah baris & Salin; klik Simpan kuota. | **Simpan kuota** — _Perhatian:_ Kapasitas tidak boleh lebih kecil dari jumlah yang sudah diterima. |
| 3 | 6:48 | Di tab Biaya, klik tambah Komponen untuk biaya awal kuliah per prodi dan sistem kuliah, misalnya uang pangkal dan SPP semester satu. | Tampilkan form Komponen biaya; sorot isian utama. | [8] — **Komponen biaya** — Program studi, sistem kuliah, khusus jalur (kosong = semua), kode, nama, dan nominal. |
| 4 | 6:58 | Centang Wajib untuk daftar ulang, Dapat dicicil beserta rencana cicilannya, dan Tampilkan di website agar muncul di halaman Biaya. | Sorot kotak centang. | **Cicilan** — Rencana cicilan berformat JSON, contoh `[{"percent":50,"due_days":0},{"percent":50,"due_days":90}]`. Total persen harus 100. |

<a id="adegan-10"></a>
### Adegan 10 — Konten, Pengaturan & Publikasi

**Waktu:** 7:08–7:51 (43 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot Konten Website PMB (24-01), Format NIM (25-02), dan header detail periode (20-02).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/konfigurasi/konten`
**Arahan demonstrasi (bila merekam aplikasi asli):** Konfigurasi PMB → Konten Website: terbitkan Kebijakan Privasi & Ketentuan; Pengaturan PMB tab Umum & Format NIM; Periode → Publikasikan.
**Aset:** [`24-01-konten-daftar.png`](../manual-book/pmb/assets/24-01-konten-daftar.png), [`25-02-format-nim.png`](../manual-book/pmb/assets/25-02-format-nim.png), [`20-02-periode-gelombang-jalur.png`](../manual-book/pmb/assets/20-02-periode-gelombang-jalur.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 7:08 | Di Konten Website, kelola FAQ, berita, panduan, banner, dan dokumen legal. Kebijakan Privasi dan Ketentuan Pendaftaran wajib terbit sebelum periode dipublikasikan. | Sorot baris Kebijakan Privasi dan Ketentuan Pendaftaran. | [9] — **Konten website** — Klik **Konten baru**, pilih tipe, isi judul & isi, **Simpan**, lalu **Terbitkan**. Konten berversi diubah lewat **Versi baru**. — _Info:_ Profil prodi (deskripsi, akreditasi, brosur) dikelola di **Profil Prodi**; deskripsinya kini memakai editor teks kaya. |
| 2 | 7:23 | Di Pengaturan PMB, tab Umum memuat kontak panitia, rekening transfer, batas pendaftaran aktif, dan aturan lain. Tab Format NIM mengatur pola NIM dengan token. | Tampilkan tab Format NIM; sorot format, token, dan contoh. | **Format NIM** — Token `{YY}` `{YYYY}` `{PRODI}` `{JALUR}` `{SISTEM}` `{SEQ:n}` (wajib satu SEQ). Klik **Pratinjau**, lalu **Simpan**. — _Perhatian:_ Tetapkan format resmi sebelum daftar ulang pertama. |
| 3 | 7:36 | Setelah semua siap, buka detail periode dan klik Publikasikan. Daftar periksa memastikan gelombang, template, kuota, dokumen legal, dan persyaratan dokumen sudah lengkap. | Tampilkan detail periode; sorot tombol status di kanan atas. | **Publikasi periode** — • ≥ 1 gelombang • Gelombang-jalur dengan template terbit • Kuota prodi yang ditawarkan • Kebijakan Privasi & Ketentuan terbit • Persyaratan dokumen tiap gelombang-jalur — _Berhasil:_ Status periode: **Draf → Terbit → Ditutup → Diarsipkan**. |

<a id="adegan-11"></a>
### Adegan 11 — Dashboard PMB

**Waktu:** 7:51–8:19 (28 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot Dashboard PMB untuk satu periode (30-01).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb`
**Arahan demonstrasi (bila merekam aplikasi asli):** PMB → Dashboard PMB; pilih periode; gulir ke Capaian kuota dan Pendapatan.
**Aset:** [`30-01-dashboard.png`](../manual-book/pmb/assets/30-01-dashboard.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 7:52 | Selama masa pendaftaran, pantau Dashboard PMB. Pilih periode di kanan atas untuk melihat angka periode tersebut. | Sorot pemilih periode dan kartu KPI. | [10] — **Dashboard PMB** — Akun pendaftar, formulir terkirim, persentase bayar, peserta seleksi, diterima, dan resmi mahasiswa beserta *yield*. |
| 2 | 8:01 | Funnel dan tren tiga puluh hari menunjukkan perkembangan pendaftar dari akun hingga resmi menjadi mahasiswa. | Sorot Funnel dan Tren 30 hari. | **Funnel & tren** — Akun → Formulir terkirim → Bayar → Terverifikasi → Diterima → Resmi mahasiswa. |
| 3 | 8:09 | Di bagian bawah ada capaian kuota per prodi, pendapatan PMB, waktu layanan verifikasi, dan sumber informasi pendaftar. | Geser ke bawah; sorot Capaian kuota dan kartu ringkasan. | **Capaian & layanan** — Dekan/kaprodi hanya melihat angka prodi dalam cakupannya. Kartu pendapatan hanya tampil bagi yang berizin melihat pembayaran. |

<a id="adegan-12"></a>
### Adegan 12 — Data Pendaftar

**Waktu:** 8:19–9:08 (49 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot daftar pendaftar (31-01), detail pendaftar (31-03), dialog data sensitif (31-04).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/pendaftar`
**Arahan demonstrasi (bila merekam aplikasi asli):** PMB → Pendaftar; filter; klik Pendaftaran loket; buka detail; klik ikon mata NIK dan isi tujuan.
**Aset:** [`31-01-pendaftar-daftar.png`](../manual-book/pmb/assets/31-01-pendaftar-daftar.png), [`31-03-detail-data.png`](../manual-book/pmb/assets/31-03-detail-data.png), [`31-04-buka-data-sensitif.png`](../manual-book/pmb/assets/31-04-buka-data-sensitif.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 8:20 | Menu Pendaftar menampilkan semua pendaftar dengan filter periode, jalur, prodi, dan status formulir, verifikasi, serta pembayaran. | Sorot baris filter dan tabel. | [11] — **Daftar pendaftar** — Cari nama, nomor pendaftaran, atau email. Klik nama untuk membuka detail. |
| 2 | 8:31 | Untuk calon mahasiswa yang datang langsung ke kampus, klik Pendaftaran loket. Akun dibuat atas namanya dan tautan aktivasi dikirim ke emailnya. | Klik Pendaftaran loket. | **Pendaftaran loket** — Isi nama, email, nomor HP, dan (opsional) langsung buat draf pada jalur yang dibuka. Pendaftar tetap mengirim formulirnya sendiri. |
| 3 | 8:41 | Detail pendaftar terdiri dari tab Data, Dokumen, Linimasa, dan Catatan. Admin PMB dapat mengoreksi identitas atau pilihan prodi dengan alasan. | Tampilkan detail pendaftar; sorot tab dan tombol Koreksi. | **Detail pendaftar** — • **Data**: akun, identitas, pilihan, pendidikan, pembayaran, seleksi & hasil • **Dokumen**: versi & riwayat tinjauan • **Linimasa**: semua perubahan status • **Catatan**: internal atau terlihat pendaftar |
| 4 | 8:54 | Data sensitif seperti NIK tampil tersamar. Untuk membukanya, klik ikon mata, isi tujuan akses, lalu Buka. Setiap pembukaan tercatat di audit. | Tampilkan dialog Buka data sensitif. | **Buka data sensitif** — _Perhatian:_ Hanya peran berizin. Pengguna, waktu, tujuan, dan alamat IP tercatat dan dapat diperiksa auditor. |

<a id="adegan-13"></a>
### Adegan 13 — Verifikasi Administrasi

**Waktu:** 9:08–10:23 (1 mnt 15 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot antrean verifikasi (32-01), pendaftaran yang diklaim (32-03), tinjau dokumen (32-05), minta perbaikan (32-06).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/verifikasi`
**Arahan demonstrasi (bila merekam aplikasi asli):** Login sebagai Petugas Verifikasi: PMB → Verifikasi → Ambil berikutnya; tab Dokumen → Lihat → Tinjau; putuskan Verifikasi / Minta perbaikan / Tolak.
**Aset:** [`32-01-verifikasi-antrean.png`](../manual-book/pmb/assets/32-01-verifikasi-antrean.png), [`32-03-detail-diklaim.png`](../manual-book/pmb/assets/32-03-detail-diklaim.png), [`32-05-tinjau-dokumen.png`](../manual-book/pmb/assets/32-05-tinjau-dokumen.png), [`32-06-minta-perbaikan.png`](../manual-book/pmb/assets/32-06-minta-perbaikan.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 9:09 | Petugas verifikasi bekerja di menu Verifikasi. Kartu di atas menunjukkan jumlah antrean, yang sedang diperiksa, milik saya, perlu perbaikan, dan indikasi duplikat. | Sorot kartu statistik. | [12] — **Verifikasi administrasi** — Hasilnya: **Terverifikasi**, **Perlu perbaikan**, atau **Ditolak**. Hanya pendaftaran terverifikasi yang masuk seleksi. |
| 2 | 9:22 | Klik Ambil berikutnya. Sistem mengklaim pendaftaran terlama yang siap diperiksa selama tiga puluh menit dan langsung membuka detailnya. | Klik Ambil berikutnya. | **Ambil berikutnya** — Klaim berlaku **30 menit**. **Lepas klaim** mengembalikannya ke antrean. — _Info:_ Pendaftaran yang belum lunas tampil di antrean tetapi belum dapat diklaim. |
| 3 | 9:32 | Periksa tab Data dan Dokumen. Bila tombol Verifikasi belum aktif, kotak kuning menyebutkan penyebabnya, misalnya dokumen wajib yang belum disetujui. | Tampilkan detail yang diklaim; sorot kotak Belum dapat diverifikasi dan tombol keputusan. | **Belum dapat diverifikasi** — `DOCUMENT_NOT_APPROVED:<kode>` = dokumen wajib belum disetujui. `PAYMENT_NOT_SATISFIED` = biaya belum lunas. |
| 4 | 9:43 | Pada tab Dokumen, klik Lihat untuk membuka berkas, lalu Tinjau. Pilih Setujui, Minta perbaikan, atau Tolak dengan kode alasan dan catatan. | Tampilkan dialog Tinjau dokumen. | **Tinjau dokumen** — Kode alasan: tidak terbaca, salah dokumen, halaman tidak lengkap, kedaluwarsa, nama/data tidak sesuai, belum dilegalisir, dugaan pemalsuan, pas foto tidak sesuai. |
| 5 | 9:56 | Setelah semua dokumen wajib disetujui, klik Verifikasi. Bila ada yang bisa diperbaiki, klik Minta perbaikan, pilih dokumen atau data yang dimaksud, dan tulis catatan untuk pendaftar. | Tampilkan dialog Minta perbaikan. | **Keputusan** — • **Verifikasi**: formulir terkunci, status *Siap seleksi* • **Minta perbaikan**: batas default 72 jam • **Tolak**: wajib kode alasan & catatan untuk pendaftar |
| 6 | 10:10 | Admin PMB juga dapat menugaskan petugas, memverifikasi massal jalur tertentu, menyetujui galeri pas foto, serta menangani indikasi duplikat dan pemulihan akun. | Kembali ke antrean; sorot tab Antrean dan Galeri pas foto. | **Fitur Admin PMB** — Tugaskan ke petugas · Verifikasi massal · Galeri pas foto · Indikasi duplikat · Pemulihan akun · Buka kembali keputusan. — _Info:_ Kenal pendaftar secara pribadi? Gunakan **Konflik kepentingan**. |

<a id="adegan-14"></a>
### Adegan 14 — Keuangan PMB

**Waktu:** 10:23–11:19 (56 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot Keuangan PMB tab Bukti manual (33-04), dialog verifikasi bukti (33-05), pembayaran loket (33-03), tab Refund (33-08).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/keuangan`
**Arahan demonstrasi (bila merekam aplikasi asli):** Login sebagai Petugas Keuangan: PMB → Keuangan PMB → Bukti manual → Lihat bukti → Putuskan; detail tagihan → Catat pembayaran loket; tab Refund → Ajukan refund.
**Aset:** [`33-04-bukti-manual.png`](../manual-book/pmb/assets/33-04-bukti-manual.png), [`33-05-putuskan-bukti.png`](../manual-book/pmb/assets/33-05-putuskan-bukti.png), [`33-03-catat-pembayaran-loket.png`](../manual-book/pmb/assets/33-03-catat-pembayaran-loket.png), [`33-08-refund.png`](../manual-book/pmb/assets/33-08-refund.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 10:24 | Petugas keuangan bekerja di menu Keuangan PMB, dengan tab Tagihan, Transaksi, Bukti manual, Rekonsiliasi, dan Refund. | Sorot kartu ringkasan dan tab. | [13] — **Keuangan PMB** — Kartu: pendapatan pendaftaran, pendapatan biaya awal, hari ini, bukti manual menunggu, refund berjalan. |
| 2 | 10:34 | Di tab Bukti manual, bandingkan nominal yang ditransfer dengan tagihan dan kode unik. Klik Lihat bukti, lalu cocokkan dengan mutasi rekening kampus. | Sorot kolom nominal; klik Lihat bukti. | **Bukti transfer manual** — Nominal berbeda ditandai merah; label **Mirip bukti lain** menandai dugaan bukti ganda. |
| 3 | 10:45 | Klik Putuskan. Pilih Setujui bila dana diterima, atau Tolak dengan alasan, misalnya tidak ada di mutasi atau nominal tidak sesuai. | Tampilkan dialog Verifikasi bukti transfer. | **Putuskan** — Disetujui: transaksi sukses, tagihan lunas/sebagian. Ditolak: pendaftar membuat instruksi baru. — _Perhatian:_ Verifikator bukti bayar biaya awal tidak dapat menyetujui daftar ulang maupun refund transaksi yang sama. |
| 4 | 10:56 | Untuk pembayaran tunai, buka detail tagihan dan klik Catat pembayaran loket. Isi nominal, nomor kuitansi, dan waktu bayar. | Tampilkan dialog Pembayaran loket. | **Pembayaran loket** — Transaksi tercatat sukses dengan referensi `CSH-<kuitansi>` dan status tagihan langsung diperbarui. — _Info:_ Tagihan pendaftaran terbit setelah pendaftar membuka langkah Pembayaran. |
| 5 | 11:07 | Tab Rekonsiliasi mencocokkan transaksi dengan mutasi bank melalui impor berkas. Tab Refund dipakai untuk mengajukan pengembalian dana yang disetujui kepala keuangan. | Tampilkan tab Refund; sorot Ajukan refund dan Refund massal. | **Rekonsiliasi & refund** — • **Rekonsiliasi**: Batch baru → Impor mutasi (CSV/XLSX) → Selesaikan item → Tutup batch • **Refund**: Ajukan → disetujui di menu **Persetujuan** → transfer → **Catat selesai** — _Info:_ Xendit: batch mendeteksi transaksi ganda & selisih; settlement tidak ditarik otomatis. |

<a id="adegan-15"></a>
### Adegan 15 — Ujian Seleksi

**Waktu:** 11:19–12:07 (48 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot form jadwal ujian (34-02), detail jadwal (34-03), check-in sesi (34-08).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/ujian`
**Arahan demonstrasi (bila merekam aplikasi asli):** PMB → Ujian: siapkan Lokasi & ruang; + Jadwal ujian; + Sesi; Tempatkan otomatis; Bagi petugas; + Soal (CBT); Publikasikan; hari-H buka sesi → Token QR.
**Aset:** [`34-02-form-jadwal-ujian.png`](../manual-book/pmb/assets/34-02-form-jadwal-ujian.png), [`34-03-jadwal-detail.png`](../manual-book/pmb/assets/34-03-jadwal-detail.png), [`34-08-sesi-checkin.png`](../manual-book/pmb/assets/34-08-sesi-checkin.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 11:19 | Untuk jalur berujian, Panitia membuka menu Ujian. Siapkan lokasi dan ruang, lalu klik tambah Jadwal ujian. | Tampilkan form Jadwal ujian. | [14] — **Jadwal ujian** — Judul, komponen seleksi yang dinilai, jalur peserta, mode (luring, daring terawasi, CBT, wawancara, portofolio), tanggal, dan instruksi. — _Info:_ Tanggal harus di dalam jendela ujian gelombang, kecuali gelombang ODS. |
| 2 | 11:29 | Di detail jadwal, tambahkan sesi beserta ruang dan kapasitas. Klik Tempatkan otomatis untuk membagi peserta terverifikasi ke sesi, lalu Bagi petugas. | Tampilkan detail jadwal; sorot tombol header dan tabel sesi. | **Sesi, peserta, petugas** — • **+ Sesi**: ruang, waktu, kapasitas • **Tempatkan otomatis (N)**: menurut nomor pendaftaran • **Petugas / Bagi petugas**: penguji, pewawancara, pengawas |
| 3 | 11:42 | Untuk CBT, isi bank soal lewat tambah Soal. Setelah lengkap, klik Publikasikan agar kartu peserta terbit dan peserta mendapat notifikasi. | Sorot Bank soal CBT. | **Bank soal & publikasi** — Isi pertanyaan, opsi (JSON), kunci jawaban, dan poin. — _Perhatian:_ Kunci jawaban tidak dikirim ulang ke peramban. Saat mengubah soal, isi ulang **Kunci jawaban**. |
| 4 | 11:53 | Pada hari ujian, buka detail sesi. Pindai kode QR di kartu peserta pada kolom Token QR untuk mencatat kehadiran, atau ubah status kehadiran secara manual. | Tampilkan detail sesi; sorot Token QR dan kolom Kehadiran. | **Check-in** — Pemindai kode batang berfungsi sebagai keyboard. Status: Terdaftar, Hadir, Tidak hadir, Izin, Didiskualifikasi. Cetak **Daftar hadir (PDF)**. — _Info:_ Peserta CBT otomatis tercatat Hadir saat mulai mengerjakan. |

<a id="adegan-16"></a>
### Adegan 16 — Penilaian Ujian

**Waktu:** 12:07–12:40 (34 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot menu Penilaian Ujian dari sisi dosen (35-02) dan lembar nilai (35-01).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/penilaian`
**Arahan demonstrasi (bila merekam aplikasi asli):** Login sebagai dosen penguji: PMB → Penilaian Ujian → pilih sesi → isi nilai → Simpan nilai. Login Panitia Seleksi: detail sesi → Kunci nilai.
**Aset:** [`35-02-penilaian-pengawas.png`](../manual-book/pmb/assets/35-02-penilaian-pengawas.png), [`35-01-penilaian-ujian.png`](../manual-book/pmb/assets/35-01-penilaian-ujian.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 12:07 | Dosen penguji atau pengawas hanya melihat menu Penilaian Ujian, berisi sesi yang ditugaskan kepadanya. | Sorot menu PMB dosen dan kartu sesi. | [15] — **Penilaian ujian** — Data peserta tampil minimal: nama dan nomor peserta, tanpa NIK atau kontak. |
| 2 | 12:15 | Pilih sesi, isi nilai sesuai rentang komponen, tambahkan catatan bila perlu, lalu klik Simpan nilai. Hanya peserta berstatus Hadir yang dapat dinilai. | Tampilkan lembar nilai; sorot kolom Nilai. | **Mengisi nilai** — Angka, Lulus/Tidak, atau nilai per kriteria rubrik untuk wawancara/portofolio. — _Info:_ Nilai CBT dihitung otomatis dari jawaban peserta. |
| 3 | 12:27 | Setelah nilai lengkap, Panitia Seleksi mengunci nilai sesi dari detail sesi. Perubahan nilai yang sudah terkunci harus melalui pengajuan koreksi yang disetujui pengesah. | Sorot pesan Nilai sesi sudah dikunci. | **Kunci nilai** — _Perhatian:_ Setelah dikunci, lembar nilai menjadi baca saja. Koreksi nilai terkunci memerlukan persetujuan Pejabat Pengesah. |

<a id="adegan-17"></a>
### Adegan 17 — Proses Seleksi

**Waktu:** 12:40–13:26 (46 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot Proses Seleksi tab Matriks nilai (36-02) dan detail run (36-04).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/seleksi`
**Arahan demonstrasi (bila merekam aplikasi asli):** Login Panitia Seleksi: PMB → Seleksi; pilih periode, gelombang, jalur; cek Matriks nilai; tab Run seleksi → Jalankan seleksi → Mulai tinjau → Override bila perlu → Ajukan pengesahan.
**Aset:** [`36-02-matriks-nilai.png`](../manual-book/pmb/assets/36-02-matriks-nilai.png), [`36-04-run-detail-terbit.png`](../manual-book/pmb/assets/36-04-run-detail-terbit.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 12:41 | Di menu Seleksi, pilih periode, gelombang, dan jalur. Periksa dulu tab Matriks nilai: ikon gembok berarti nilai sudah terkunci. | Sorot filter dan Matriks nilai. | [16] — **Proses seleksi** — Tab **Run seleksi · Matriks nilai · Impor nilai**. Nilai dari sistem lain dapat diimpor dari berkas XLSX/CSV berkolom `participant_number` dan `score`. |
| 2 | 12:52 | Di tab Run seleksi, klik Jalankan seleksi. Sistem menghitung skor, peringkat, dan penempatan berdasarkan skema terkunci dan kuota. | Tampilkan detail run; sorot Ringkasan dan tabel per kuota. | **Run seleksi** — Ringkasan: peserta, eligible, lulus, cadangan, tidak lulus, seri, ambang, dan sisa kursi per kuota. — _Info:_ **Hash** adalah sidik jari hasil perhitungan untuk memastikan hasil yang disahkan sama dengan yang dihitung. |
| 3 | 13:03 | Klik Mulai tinjau, lalu periksa tabel hasil: skor akhir, rekomendasi lulus, cadangan, atau tidak lulus, dan prodi tempat ditempatkan. | Geser ke tabel hasil; sorot kolom Rekomendasi dan Ditempatkan. | **Tinjau hasil** — Kode penyebab tidak lulus, mis. `below_threshold` (di bawah ambang), `absent` (tidak hadir), `quota_full_no_waitlist`. — _Info:_ **Override** mengubah rekomendasi satu pendaftar dengan alasan wajib. |
| 4 | 13:15 | Bila ada skor seri pada batas kuota, tetapkan urutannya lebih dulu. Setelah itu klik Ajukan pengesahan dan isi catatan pengajuan. | Kembali ke atas; sorot status run. | **Ajukan pengesahan** — Status run: Dihitung → Ditinjau → **Menunggu pengesahan** → Disetujui → Terbit. — _Perhatian:_ Tombol Ajukan pengesahan nonaktif selama masih ada seri pada batas kuota. |

<a id="adegan-18"></a>
### Adegan 18 — Pengesahan Hasil

**Waktu:** 13:26–13:58 (31 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot run menunggu pengesahan (36-05), daftar Persetujuan (36-06), dialog Setujui (36-08).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/seleksi/run/…`
**Arahan demonstrasi (bila merekam aplikasi asli):** Login Admin PMB lalu Pejabat Pengesah: Persetujuan → Pengajuan → Lihat Detail (SelectionRun) → Setujui/Tolak.
**Aset:** [`36-05-run-detail-menunggu-pengesahan.png`](../manual-book/pmb/assets/36-05-run-detail-menunggu-pengesahan.png), [`36-06-persetujuan-daftar.png`](../manual-book/pmb/assets/36-06-persetujuan-daftar.png), [`36-08-setujui-pengajuan.png`](../manual-book/pmb/assets/36-08-setujui-pengajuan.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 13:27 | Pengesahan hasil berlangsung dua langkah: pertama Ketua Panitia, yaitu Admin PMB, lalu Pejabat Pengesah. Kartu Pengesahan di detail run menunjukkan status setiap langkah. | Sorot kartu Pengesahan. | [17] — **Pengesahan berjenjang** — Langkah 1: **Ketua Panitia PMB** (Admin PMB). Langkah 2: **Pejabat Pengesah PMB**. — _Info:_ Kampus menentukan pejabat yang diberi peran Pejabat Pengesah (rancangan menyarankan WR I). |
| 2 | 13:41 | Pengesah membuka menu Persetujuan, lalu Lihat Detail pada pengajuan berjenis SelectionRun. | Tampilkan menu Persetujuan; sorot baris SelectionRun. | **Menu Persetujuan** — Pengajuan PMB tampil dengan jenis teknis: **SelectionRun**, **Refund**, atau **ChangeRequest** (koreksi, pembatalan, perpanjangan massal, percepatan publikasi). |
| 3 | 13:47 | Tinjau ringkasan run di menu Seleksi, lalu klik Setujui dengan komentar, atau Tolak dengan alasan agar panitia tahu apa yang harus diperbaiki. | Tampilkan dialog Setujui Pengajuan. | **Setujui atau tolak** — Aksi: **Setujui**, **Tolak**, **Delegasikan**. — _Perhatian:_ Pengaju run tidak dapat menyetujui langkah mana pun (`SELF_APPROVAL_FORBIDDEN`). Tidak ada tombol publikasi manual. |

<a id="adegan-19"></a>
### Adegan 19 — Pengumuman & Keputusan

**Waktu:** 13:58–14:39 (41 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot Pengumuman & Keputusan: Jadwal publikasi (37-02), Keputusan (37-01), Kursi & cadangan (37-03).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/pengumuman`
**Arahan demonstrasi (bila merekam aplikasi asli):** PMB → Pengumuman: tab Jadwal publikasi; tab Keputusan (Beasiswa); tab Kursi & cadangan (Promosikan); tab Koreksi & pengajuan.
**Aset:** [`37-02-jadwal-publikasi.png`](../manual-book/pmb/assets/37-02-jadwal-publikasi.png), [`37-01-keputusan.png`](../manual-book/pmb/assets/37-01-keputusan.png), [`37-03-kursi-cadangan.png`](../manual-book/pmb/assets/37-03-kursi-cadangan.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 13:58 | Setelah disahkan, sistem membuat batch pengumuman yang dijadwalkan pada waktu pengumuman gelombang. Hasil terbit otomatis, surat penerimaan dibuat, dan pendaftar diberi tahu. | Sorot info dan baris batch. | [18] — **Jadwal publikasi** — Status batch: Terjadwal → Menerbitkan → Terbit. **Ubah jadwal**: mempercepat perlu persetujuan, memundurkan berlaku langsung. |
| 2 | 14:12 | Tab Keputusan menampilkan keputusan setiap pendaftar, status publikasi, nomor surat penerimaan, dan status daftar ulang. Panitia Seleksi dapat memberikan beasiswa jalur masuk di sini. | Tampilkan tab Keputusan; sorot kolom keputusan dan LoA. | **Keputusan & beasiswa** — Keputusan: Diterima, Cadangan, Tidak diterima, Dibatalkan. — _Info:_ Berikan **Beasiswa** sebelum pendaftar membayar biaya awal agar potongan masuk ke tagihan. |
| 3 | 14:26 | Tab Kursi dan cadangan menyorot kursi kosong per kuota. Bila ada kursi kosong dan cadangan, Admin PMB klik Promosikan untuk menaikkan cadangan teratas menjadi diterima. | Tampilkan tab Kursi & cadangan; sorot kolom Kursi kosong. | **Promosi cadangan** — Cadangan yang dipromosikan mendapat tagihan biaya awal dan batas daftar ulang individual (default 7 hari). — _Info:_ Koreksi hasil setelah terbit diajukan di tab **Koreksi & pengajuan** dan memerlukan persetujuan Pejabat Pengesah. |

<a id="adegan-20"></a>
### Adegan 20 — Daftar Ulang & Konversi

**Waktu:** 14:39–15:28 (49 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot detail daftar ulang berjalan (38-04), selesai (38-02), dan tab Konversi mahasiswa (38-03).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/daftar-ulang`
**Arahan demonstrasi (bila merekam aplikasi asli):** Login Petugas Daftar Ulang: PMB → Daftar Ulang → prioritaskan status Terkirim → buka detail → Tandai pemeriksaan → Setujui; tab Konversi mahasiswa.
**Aset:** [`38-04-daftar-ulang-detail-berjalan.png`](../manual-book/pmb/assets/38-04-daftar-ulang-detail-berjalan.png), [`38-02-daftar-ulang-detail-selesai.png`](../manual-book/pmb/assets/38-02-daftar-ulang-detail-selesai.png), [`38-03-konversi.png`](../manual-book/pmb/assets/38-03-konversi.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 14:40 | Petugas daftar ulang membuka menu Daftar Ulang dan memprioritaskan pendaftar berstatus Terkirim. Detail daftar ulang menampilkan delapan butir pemeriksaan. | Sorot kartu Pemeriksaan. | [19] — **Pemeriksaan daftar ulang** — Tawaran diterima, data PDDikti, orang tua, dokumen final, surat sehat, pembayaran minimum, dokumen asli, tidak ada duplikat. |
| 2 | 14:51 | Saat pendaftar menunjukkan dokumen asli, klik Tandai pada butir Dokumen asli diperiksa dan pilih Lolos. | Klik Tandai. | **Tandai pemeriksaan** — Pilih status **Lolos**, **Tidak lolos**, atau **Tidak berlaku**, lalu isi catatan. |
| 3 | 14:58 | Bila semua butir lolos, klik Setujui. Konversi mahasiswa berjalan otomatis: data mahasiswa dan NIM dibuat, akun ditautkan, dan tagihan diposting ke modul Keuangan. | Tampilkan daftar ulang selesai; sorot status dan NIM. | **Setujui → konversi** — Status menjadi **Resmi mahasiswa**. Pendaftar melihat NIM dan dapat mengunduh bukti registrasi. — _Perhatian:_ Petugas yang memverifikasi bukti bayar biaya awal pendaftar ini tidak dapat menyetujuinya. |
| 4 | 15:12 | Tab Konversi mahasiswa memantau prosesnya. Bila gagal, klik Coba lagi. Admin PMB juga dapat memperpanjang batas, memulihkan penolakan, atau mengajukan perpanjangan massal. | Tampilkan tab Konversi mahasiswa; sorot tabel dan Perpanjangan massal. | **Konversi & tindak lanjut** — • **Coba lagi** untuk status Gagal/Perlu tindakan • `USER_EMAIL_CONFLICT`: isi email akun pengganti • **Perpanjang**, **Pulihkan**, **Batalkan penerimaan** (perlu persetujuan) |

<a id="adegan-21"></a>
### Adegan 21 — Komunikasi

**Waktu:** 15:28–15:57 (29 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot Komunikasi PMB: Pesan pendaftar (39-01) dan form Broadcast (39-03).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/komunikasi`
**Arahan demonstrasi (bila merekam aplikasi asli):** PMB → Komunikasi: balas pesan pendaftar; Broadcast baru → Hitung penerima → Kirim/jadwalkan.
**Aset:** [`39-01-pesan-pendaftar.png`](../manual-book/pmb/assets/39-01-pesan-pendaftar.png), [`39-03-form-broadcast.png`](../manual-book/pmb/assets/39-03-form-broadcast.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 15:29 | Menu Komunikasi berisi Pesan pendaftar, Broadcast, Template notifikasi, dan Log notifikasi. Di Pesan pendaftar, pilih penanggung jawab, atur status percakapan, lalu balas pertanyaan. | Sorot tab dan panel percakapan. | [20] — **Pesan pendaftar** — Status percakapan: **Dibuka**, **Menunggu pendaftar**, **Ditutup**. — _Perhatian:_ Jangan pernah meminta kata sandi atau kode OTP, dan jangan kirim data sensitif lewat chat. |
| 2 | 15:44 | Broadcast mengirim email atau SMS ke kelompok pendaftar, misalnya pengingat bagi yang masih draf. Hitung penerima dulu, lalu kirim sekarang atau jadwalkan. | Tampilkan form Broadcast; sorot filter audiens dan isi pesan. | **Broadcast** — Audiens: periode, jalur, status formulir, pembayaran, keputusan. Centang **Hanya yang menyetujui informasi promosi** untuk pesan promosi. — _Info:_ SMS/WA membutuhkan penyedia SMS yang dikonfigurasi kampus. |

<a id="adegan-22"></a>
### Adegan 22 — Laporan & Audit

**Waktu:** 15:57–16:36 (39 dtk) · **Jenis:** Screenshot aplikasi + sorotan

**Visual:** Screenshot Laporan PMB (40-01), dialog Ekspor (40-02), Audit PMB (41-01).
**Alamat di bilah peramban:** `civitasone.kampus.ac.id/admin/pmb/laporan`
**Arahan demonstrasi (bila merekam aplikasi asli):** PMB → Laporan PMB: pilih laporan & filter → Terapkan → Ekspor; tab Ekspor saya. PMB → Audit PMB.
**Aset:** [`40-01-laporan.png`](../manual-book/pmb/assets/40-01-laporan.png), [`40-02-form-ekspor.png`](../manual-book/pmb/assets/40-02-form-ekspor.png), [`41-01-audit-ringkasan.png`](../manual-book/pmb/assets/41-01-audit-ringkasan.png)

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 15:58 | Di Laporan PMB, pilih jenis laporan, misalnya rekap pendaftar, rekap pembayaran, sumber informasi, seleksi, atau keterisian kuota. Atur filter lalu klik Terapkan. | Sorot baris filter laporan. | [21] — **Laporan PMB** — Rekap Pendaftar · Rekap Pembayaran · Sumber Informasi · Laporan Seleksi · Daftar Ulang & Konversi · Keuangan · Keterisian Kuota · Kelengkapan PDDikti. |
| 2 | 16:13 | Klik Ekspor, pilih format dan kolom, lalu isi alasan ekspor. Berkas diunduh dari tab Ekspor saya dan kedaluwarsa dalam dua puluh empat jam. | Tampilkan dialog Ekspor laporan; sorot alasan ekspor. | **Ekspor** — Format XLSX, CSV, atau PDF. Hanya pembuatnya yang bisa mengunduh. — _Perhatian:_ Ekspor berisi data pribadi tercatat (pengguna, alasan, filter) dan diperiksa auditor. |
| 3 | 16:24 | Auditor dan Admin PMB memantau Audit PMB: ringkasan tiga puluh hari, jejak perubahan, akses data sensitif, dan log ekspor. | Tampilkan Audit PMB; sorot tab dan ringkasan. | **Audit PMB** — Hanya baca. Jejak perubahan menampilkan nilai **sebelum** dan **sesudah** beserta alasan. |

<a id="adegan-23"></a>
### Adegan 23 — Kendala Umum

**Waktu:** 16:36–17:24 (48 dtk) · **Jenis:** Kartu informasi

**Visual:** Enam kartu kendala dan solusi.

**Teks layar:** judul "Kendala umum & solusinya"; subjudul "Daftar lengkap pesan dan solusinya ada di Manual Book PMB untuk Penyelenggara.".

**Butir yang ditampilkan:**

1. **Publikasi periode gagal** — Lengkapi butir daftar periksa: template terbit, kuota ditawarkan, dokumen legal terbit, persyaratan dokumen.
2. **Tombol Verifikasi nonaktif** — Ada dokumen wajib belum disetujui atau biaya belum lunas — baca kode di kotak kuning.
3. **Ajukan pengesahan nonaktif** — Ada skor seri di batas kuota — Tetapkan urutan dulu.
4. **"Pengaju tidak boleh memutuskan…"** — Aturan pemisahan tugas — minta pejabat lain menyetujui.
5. **Konversi gagal (USER_EMAIL_CONFLICT)** — Coba lagi dengan email akun mahasiswa pengganti.
6. **Status tidak berubah / notifikasi tertahan** — Pastikan penjadwal (cron schedule:run) aktif. Kanal notifikasi dalam aplikasi pendaftar masih bermasalah; email tetap terkirim.

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 16:37 | Berikut kendala yang paling sering ditemui panitia. | Tampilkan judul. |  |
| 2 | 16:40 | Publikasi periode gagal? Lengkapi butir daftar periksa. Tombol Verifikasi nonaktif? Baca kode di kotak kuning: dokumen wajib belum disetujui atau biaya belum lunas. | Sorot dua kartu pertama. | Muncul: Publikasi periode gagal, Tombol Verifikasi nonaktif |
| 3 | 16:55 | Tombol Ajukan pengesahan nonaktif berarti masih ada skor seri pada batas kuota. Pesan pengaju tidak boleh memutuskan berarti aturan pemisahan tugas; minta pejabat lain menyetujui. | Sorot kartu 3 dan 4. | Muncul: Ajukan pengesahan nonaktif, "Pengaju tidak boleh memutuskan…" |
| 4 | 17:09 | Konversi gagal karena email sudah dipakai? Klik Coba lagi dengan email pengganti. Status atau notifikasi tidak berjalan? Pastikan penjadwal server aktif setiap menit. | Sorot kartu 5 dan 6. | Muncul: Konversi gagal (USER_EMAIL_CONFLICT), Status tidak berubah / notifikasi tertahan |

<a id="adegan-24"></a>
### Adegan 24 — Perlu Dikonfirmasi Kampus

**Waktu:** 17:24–18:00 (36 dtk) · **Jenis:** Kartu informasi

**Visual:** Enam kartu hal yang perlu dikonfirmasi.

**Teks layar:** judul "Hal yang perlu dikonfirmasi sebelum go-live"; subjudul "Bagian ini belum final di rancangan atau bergantung pada pihak eksternal.".

**Butir yang ditampilkan:**

1. **Gateway pembayaran (Xendit)** — Adapter tersedia, perlu kredensial & uji coba. Tanpa itu: transfer manual & loket.
2. **SMS / WhatsApp** — Perlu kontrak & API penyedia; tanpa itu OTP & broadcast SMS tidak terkirim.
3. **Antivirus & CAPTCHA** — Pemindai berkas bawaan belum memindai; CAPTCHA perlu kunci Turnstile.
4. **TTE surat penerimaan** — LoA saat ini PDF + QR verifikasi; tanda tangan elektronik tersertifikasi belum ada.
5. **Kebijakan kampus** — Maks. pendaftaran aktif (default 1), pejabat pengesah, format NIM, refund, minimum bayar daftar ulang.
6. **PDDikti & retensi data** — Pengiriman ke PDDikti di luar modul PMB; masa retensi & DPO ditetapkan kampus.

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 17:25 | Beberapa hal masih perlu dikonfirmasi kampus sebelum PMB dipakai penuh. | Tampilkan judul. |  |
| 2 | 17:30 | Integrasi eksternal: gateway pembayaran Xendit, penyedia SMS atau WhatsApp, pemindai antivirus, dan kunci CAPTCHA. Tanpa gateway, pembayaran memakai transfer manual dan loket. | Sorot tiga kartu pertama. | Muncul: Gateway pembayaran (Xendit), SMS / WhatsApp, Antivirus & CAPTCHA |
| 3 | 17:45 | Kebijakan kampus: tanda tangan elektronik surat penerimaan, batas pendaftaran aktif, pejabat pengesah, format NIM, kebijakan refund, serta pelaporan PDDikti dan masa retensi data. | Sorot tiga kartu berikutnya. | Muncul: TTE surat penerimaan, Kebijakan kampus, PDDikti & retensi data |

<a id="adegan-25"></a>
### Adegan 25 — Ringkasan

**Waktu:** 18:00–18:37 (38 dtk) · **Jenis:** Ringkasan & penutup

**Visual:** Ringkasan delapan langkah, ditutup layar akhir hijau.

**Teks layar:** judul "Ringkasan alur penyelenggara".

**Butir yang ditampilkan:**

1. **Konfigurasi & publikasi periode**
2. **Verifikasi berkas & pembayaran**
3. **Ujian, check-in & kunci nilai**
4. **Run seleksi → ajukan pengesahan**
5. **Pengesahan berjenjang → publikasi otomatis**
6. **Promosi cadangan & daftar ulang**
7. **Konversi mahasiswa & NIM**
8. **Laporan, audit & penutupan**

**Layar penutup:** "Selamat menyelenggarakan PMB!" — Panduan tertulis lengkap: Manual Book PMB untuk Penyelenggara

| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |
|---:|---:|---|---|---|
| 1 | 18:00 | Mari kita rangkum. Siapkan konfigurasi dan publikasikan periode, lalu verifikasi berkas dan pembayaran pendaftar. | Munculkan langkah 1–2. | Muncul: Konfigurasi & publikasi periode, Verifikasi berkas & pembayaran |
| 2 | 18:10 | Laksanakan ujian dan kunci nilai, jalankan seleksi, ajukan pengesahan, dan biarkan hasil terbit otomatis setelah disahkan. | Munculkan langkah 3–5. | Muncul: Ujian, check-in & kunci nilai, Run seleksi → ajukan pengesahan, Pengesahan berjenjang → publikasi otomatis |
| 3 | 18:20 | Kelola cadangan dan daftar ulang sampai konversi mahasiswa selesai, lalu tutup periode dengan laporan dan audit. | Munculkan langkah 6–8. | Muncul: Promosi cadangan & daftar ulang, Konversi mahasiswa & NIM, Laporan, audit & penutupan |
| 4 | 18:27 | Panduan lengkap per peran ada di Manual Book PMB untuk Penyelenggara. Terima kasih, dan selamat menyelenggarakan PMB! | Tampilkan layar penutup. |  |

## Lampiran A — Ejaan pengucapan untuk pengisi suara

Takarir memakai ejaan asli; pengisi suara (manusia maupun sintetis) membacakannya seperti berikut.

| Tulisan | Dibaca |
|---|---|
| PDDikti | pe de dikti |
| PMB | pe em be |
| CBT | si bi ti |
| NIK | nik |
| NIM | nim |
| SMS | es em es |
| QR | kyu ar |
| SPP | es pe pe |
| CAPTCHA | kepcha |
| WhatsApp | wotsap |
| Admin | admin |
| dashboard | dasbor |
| Civitas One | sivitas wan |
| SelectionRun | selection ran |
| Xendit | zendit |
| Run | ran |
| batch | bets |
| Broadcast | brodkas |
| auditor | auditor |

## Lampiran B — Cara membangun ulang video

Prasyarat: Node.js 20+, Microsoft Edge (atau Chrome dengan `BROWSER=chrome`), dan koneksi internet untuk suara sintetis.

```bash
cd docs/video-tutorial-pmb/source
npm install
node build_audio.mjs penyelenggara        # narasi -> penyelenggara.narration.wav + penyelenggara.timeline.js
node build_naskah.mjs penyelenggara       # naskah ini
node render.mjs penyelenggara snap 30,90  # cuplikan frame ke snaps/ untuk diperiksa
node render.mjs penyelenggara video ../tutorial-pmb-penyelenggara.mp4
```

Pratinjau langsung di peramban: buka `source/player.html?v=penyelenggara&play` (tanpa audio) atau `&t=<detik>` untuk satu frame. Ganti suara dengan variabel `VOICE` (mis. `id-ID-ArdiNeural`).
