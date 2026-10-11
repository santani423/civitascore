# Penjelasan Lengkap Modul SEVIMA Platform

Dokumen ini menjelaskan **apa fungsi setiap modul dan fitur** di SEVIMA Platform: untuk apa dipakai, siapa penggunanya, dan di paket mana tersedia.

Untuk perbandingan harga dan centang per paket, lihat [SEVIMA_Platform_Proposal.md](SEVIMA_Platform_Proposal.md).

> **Cara membaca**
> - **Tersedia di** menunjukkan paket terendah yang sudah mendapatkan modul/fitur tersebut. Paket di atasnya otomatis juga mendapatkannya.
> - Urutan paket: **Growth → Business → Professional → Premium → Enterprise**
> - Tanda `(*)` = fitur masih dalam tahap development.
> - Penjelasan fitur di dokumen ini adalah uraian fungsional berdasarkan nama fitur pada penawaran. Detail teknis dan alur kerja aktual sebaiknya dikonfirmasi saat demo dengan pihak SEVIMA.

---

## Daftar Isi

**A. Dashboard & Pelaporan**
1. [Smart Dashboard Pimpinan Universitas](#1-smart-dashboard-pimpinan-universitas)
2. [Integrasi PDDikti Neo Feeder](#2-integrasi-pddikti-neo-feeder)

**B. Penerimaan Mahasiswa**
3. [PMB — Penerimaan Mahasiswa Baru Online](#3-pmb--penerimaan-mahasiswa-baru-online)
4. [CRM CAMABA](#4-crm-camaba)
5. [Maukuliah — Marketplace Pencarian Kampus](#5-maukuliah--marketplace-pencarian-kampus)
6. [RPL — Rekognisi Pembelajaran Lampau](#6-rpl--rekognisi-pembelajaran-lampau)

**C. Akademik & Pembelajaran**
7. [SIAKAD — Administrasi Akademik & Operasional Kampus](#7-siakad--administrasi-akademik--operasional-kampus)
8. [EDLINK — Modul Pembelajaran (LMS)](#8-edlink--modul-pembelajaran-lms)
9. [CBT & CBT AI — Ujian Online](#9-cbt--cbt-ai--ujian-online)
10. [OBE — Kurikulum Outcome Based Education](#10-obe--kurikulum-outcome-based-education)
11. [EDOM — Evaluasi Dosen oleh Mahasiswa](#11-edom--evaluasi-dosen-oleh-mahasiswa)

**D. Keuangan**
12. [KEU — Administrasi Keuangan Mahasiswa](#12-keu--administrasi-keuangan-mahasiswa)

**E. Kemahasiswaan & Kelulusan**
13. [Poin Kemahasiswaan](#13-poin-kemahasiswaan)
14. [Yudisium & Wisuda](#14-yudisium--wisuda)
15. [SKPI — Surat Keterangan Pendamping Ijazah](#15-skpi--surat-keterangan-pendamping-ijazah)
16. [TRACER — Tracer Study](#16-tracer--tracer-study)
17. [Karirlink — Career Center](#17-karirlink--career-center)

**F. SDM & Penelitian**
18. [SDM — Pengelolaan Kepegawaian](#18-sdm--pengelolaan-kepegawaian)
19. [Litabmas — Penelitian & Pengabdian Masyarakat](#19-litabmas--penelitian--pengabdian-masyarakat)

**G. Mutu & Akreditasi**
20. [SPME — Manajemen Akreditasi](#20-spme--manajemen-akreditasi)
21. [SPMI — Sistem Penjaminan Mutu Internal](#21-spmi--sistem-penjaminan-mutu-internal)
22. [Dashboard IKU](#22-dashboard-iku)
23. [Manajemen Kerjasama](#23-manajemen-kerjasama)

**H. Layanan Administrasi & Integrasi**
24. [Tata Usaha](#24-tata-usaha)
25. [Integrasi Sistem Lain](#25-integrasi-sistem-lain)

**I. Add On**
26. [Add On Sesuai Jenis Perguruan Tinggi](#26-add-on-sesuai-jenis-perguruan-tinggi)
27. [Add On Berbayar](#27-add-on-berbayar)

**J. Layanan Implementasi**
28. [Implementasi & Training](#28-implementasi--training)

**Lampiran**
- [Peta Modul per Paket](#peta-modul-per-paket)
- [Glosarium](#glosarium)

---

# A. Dashboard & Pelaporan

## 1. Smart Dashboard Pimpinan Universitas

**Ringkasan:** pusat informasi untuk rektor, wakil rektor, dekan, dan kaprodi. Data dari semua modul dirangkum dalam laporan dan grafik, sehingga pimpinan bisa memantau kondisi kampus tanpa meminta rekap manual ke tiap unit.

**Pengguna:** pimpinan universitas, fakultas, dan program studi; Lembaga Penjaminan Mutu.

**Tersedia di:** semua paket.
- Growth & Business: bentuk **Report** (laporan).
- Professional ke atas: **Report & Dashboard** (laporan + dashboard visual interaktif).

| Fitur | Penjelasan | Tersedia di |
|---|---|---|
| Analitik Mahasiswa Aktif & Status Akademik | Jumlah mahasiswa per status (aktif, cuti, non-aktif, lulus, DO) per prodi/angkatan, beserta trennya dari semester ke semester. | Growth |
| Monitoring Realisasi Perkuliahan | Membandingkan jumlah pertemuan yang direncanakan dengan yang benar-benar terlaksana, per kelas dan per dosen. | Growth |
| Monitoring Progress Pelaporan PDDIKTI | Persentase data yang sudah dilaporkan ke PDDikti, agar pimpinan tahu prodi mana yang tertinggal. | Growth |
| Analitik Kehadiran Mahasiswa | Rekap tingkat kehadiran mahasiswa untuk mendeteksi mahasiswa berisiko (jarang hadir). | Growth |
| Monitoring Sumber Daya Manusia | Gambaran SDM kampus, terdiri dari dua bagian di bawah. | Business |
| ↳ Statistik & Profil Dosen | Komposisi dosen berdasarkan jabatan fungsional, pendidikan terakhir, sertifikasi, dan rasio dosen:mahasiswa. | Business |
| ↳ Analitik Tenaga Kependidikan & Layanan Administratif | Data tendik (staf administrasi) dan kinerja layanan administrasi. | Business |
| Monitoring Status Akreditasi PT & Prodi | Status dan masa berlaku akreditasi institusi dan tiap prodi, sehingga jadwal reakreditasi tidak terlewat. | Growth |
| Monitoring Keuangan Akademik | Ringkasan pemasukan dari mahasiswa (SPP/UKT), tagihan, dan piutang. | Growth |
| Monitoring Aktivitas PMB | Jumlah pendaftar, yang membayar formulir, yang lulus seleksi, dan yang daftar ulang per jalur/gelombang. | Growth |
| Monitoring Outcome Pengisi Tracer Study | Tingkat respons tracer study dan ringkasan hasilnya (waktu tunggu kerja, kesesuaian bidang, dll). | Growth |
| Monitoring Outcome Mahasiswa | Capaian lulusan: IPK, masa studi, dan prestasi. | Growth |
| Prediksi Kelulusan Mahasiswa (Analisis AI) | AI memprediksi mahasiswa yang berpotensi terlambat lulus atau putus studi dari pola nilai, kehadiran, dan pembayaran, agar bisa diintervensi lebih awal. | Growth |
| RPS Otomatis | Membantu dosen menyusun Rencana Pembelajaran Semester secara otomatis (dibantu AI) berdasarkan data mata kuliah dan capaian pembelajaran. | Growth |
| Generator Soal Ujian Berbasis CBT | Membuat soal ujian otomatis dengan AI yang langsung bisa dipakai di modul CBT. | Business |
| SEVIMA AI Assistant | Asisten digital (chatbot) yang membantu pengguna mencari informasi dan menjalankan tugas di sistem. | Business |
| Penerapan Kurikulum dengan Bantuan AI | AI membantu menyusun dan memetakan kurikulum (misalnya CPL ke mata kuliah). | Professional |

**Manfaat utama:** keputusan berbasis data, deteksi dini masalah (mahasiswa berisiko, pelaporan tertinggal), dan persiapan akreditasi yang lebih mudah.

---

## 2. Integrasi PDDikti Neo Feeder

**Ringkasan:** PDDikti (Pangkalan Data Pendidikan Tinggi) adalah database nasional milik Kemendikbudristek. Setiap kampus **wajib** melaporkan data mahasiswa, dosen, perkuliahan, dan kelulusan setiap semester melalui aplikasi **Neo Feeder**. Modul ini menghubungkan SIAKAD langsung ke Neo Feeder, sehingga data tidak perlu diinput dua kali.

**Pengguna:** operator PDDikti, BAAK/biro akademik.

**Tersedia di:** semua paket (integrasi 100%).

| Fitur | Penjelasan |
|---|---|
| Integrasi data akademik, biaya masuk & SPP | Data mahasiswa, KRS, nilai, serta biaya masuk dan SPP per semester dikirim otomatis dari SIAKAD ke Neo Feeder. |
| Neo Feeder Online & update otomatis | Neo Feeder dikelola secara online oleh SEVIMA dan diperbarui otomatis saat ada versi baru dari Dikti. Kampus tidak perlu meng-update server sendiri. |
| Monitoring persentase pelaporan | Menampilkan berapa persen data yang sudah terkirim per kategori/prodi. |
| Komparasi data SIAKAD vs Neo Feeder | Menunjukkan data yang berbeda antara SIAKAD dan PDDikti, misalnya jumlah SKS atau status mahasiswa. |
| Evaluasi & sinkronisasi data | Memeriksa validitas data, lalu menyinkronkan perbaikannya ke Neo Feeder. |
| Riwayat aktivitas pelaporan | Log kapan, apa, dan oleh siapa data dikirim, untuk audit dan penelusuran. |
| Simulasi Penomoran Ijazah & Sertifikat Profesi Nasional (PISN) | Simulasi pengajuan nomor ijazah nasional, untuk memastikan data lulusan memenuhi syarat sebelum pengajuan resmi. |
| Daftar Perubahan Data Mahasiswa (PDM) | Mengelola pengajuan perubahan data mahasiswa yang sudah tercatat di PDDikti, seperti nama, NIK, atau tanggal lahir. |
| Status Kelayakan SIERRA | Mengecek kelayakan data kampus untuk dinilai di SIERRA (sistem penilaian/pemeringkatan kinerja PT berbasis data PDDikti). |

**Manfaat utama:** pelaporan PDDikti lebih cepat dan akurat, risiko sanksi akibat data tidak dilaporkan berkurang, dan data lulusan siap untuk PISN.

---

# B. Penerimaan Mahasiswa

## 3. PMB — Penerimaan Mahasiswa Baru Online

**Ringkasan:** mengelola seluruh proses penerimaan mahasiswa baru, mulai dari website pendaftaran, pembayaran formulir, ujian seleksi, pengumuman, hingga calon mahasiswa resmi menjadi mahasiswa (mendapat NIM).

**Pengguna:** panitia PMB, bagian keuangan, calon mahasiswa.

**Tersedia di:** semua paket.

### 3.1 Website PMB
| Fitur | Penjelasan |
|---|---|
| Website PMB sesuai branding kampus (domain ac.id) | Halaman pendaftaran memakai logo, warna, dan domain resmi kampus. |
| Portal pendaftaran sesuai jalur & gelombang | Calon mahasiswa memilih jalur (reguler, prestasi, beasiswa, dll) dan gelombang pendaftaran. |
| Informasi program studi | Panitia mengelola deskripsi, akreditasi, dan prospek tiap prodi yang tampil di website. |
| Informasi biaya kuliah & brosur | Menampilkan rincian biaya dan brosur digital yang bisa diunduh. |

### 3.2 Manajemen Siklus PMB
| Fitur | Penjelasan |
|---|---|
| Letter of Acceptance (LoA) | Menerbitkan surat penerimaan resmi untuk pendaftar yang lulus seleksi. |
| Jalur, gelombang, prasyarat & sistem kuliah | Mengatur jalur pendaftaran, jadwal gelombang, syarat dokumen, dan sistem kuliah (reguler/karyawan/daring). |
| Ujian seleksi, ruang & rekomendasi kelulusan | Mengatur jadwal dan ruang ujian, lalu menghasilkan rekomendasi lulus/tidak dari nilai seleksi. |
| Pengumuman jadwal ujian | Jadwal ujian ditampilkan ke pendaftar melalui portal. |
| Formulir & tagihan pendaftaran (lebih lengkap) | Membuat formulir pendaftaran dan tagihan biaya formulir, dengan opsi yang lebih lengkap. |
| Setting NIM & ekspor menjadi mahasiswa | Pendaftar yang sudah daftar ulang otomatis mendapat NIM dan dipindahkan ke SIAKAD sebagai mahasiswa aktif. |
| Laporan rekapitulasi | Rekap jumlah pendaftar, status pembayaran, dan sumber informasi (tahu kampus dari mana), sebagai bahan evaluasi promosi. |

### 3.3 Fitur Pendukung PMB
| Fitur | Penjelasan | Tersedia di |
|---|---|---|
| Pembayaran tagihan pendaftaran online | Bayar formulir via virtual account/e-commerce, terverifikasi otomatis. | Growth |
| Form online & upload berkas | Pendaftar mengisi data dan mengunggah dokumen persyaratan secara online. | Growth |
| Integrasi SEVIMA CRM (MauKuliah) | Peminat dari platform Maukuliah otomatis masuk ke data PMB. | Growth |
| Jalur pindahan, pascasarjana, RPL | Mendukung pendaftaran mahasiswa pindahan, S2/S3, dan jalur RPL. | Growth |
| Pembagian ruangan, penguji & peserta | Membagi peserta ke ruang ujian dan menugaskan penguji secara otomatis sesuai jadwal. | Growth |
| KIP Kuliah | Pengelolaan pendaftar penerima bantuan Kartu Indonesia Pintar Kuliah. | Growth |
| One Day Service (ODS) | Daftar, tes, dan pengumuman dalam satu hari. | Growth |
| ↳ Seleksi terintegrasi CBT | Tes seleksi ODS dikerjakan langsung di modul CBT dan dinilai otomatis. | Business |
| Quick chat ke panitia | Pendaftar bisa bertanya langsung ke panitia lewat chat. | Growth |
| Notifikasi email pengumuman | Email otomatis untuk setiap tahap (pembayaran diterima, jadwal ujian, hasil seleksi). | Growth |
| Kirim info via email & SMS | Panitia mengirim informasi massal ke pendaftar. | Growth |

**Manfaat utama:** pendaftaran 100% online, panitia tidak perlu rekap manual, dan pendaftar langsung terhubung ke SIAKAD tanpa input ulang.

---

## 4. CRM CAMABA

**Ringkasan:** CRM (Customer Relationship Management) untuk calon mahasiswa baru (CAMABA). Fokusnya **sebelum** orang mendaftar: mengelola peminat (leads) dan menindaklanjutinya sampai mereka benar-benar mendaftar.

**Pengguna:** tim marketing/promosi kampus, panitia PMB.

**Tersedia di:** Business ke atas.

| Fitur | Penjelasan | Tersedia di |
|---|---|---|
| Data kontak peminat | Database orang yang pernah menunjukkan minat (dari pameran, sosialisasi sekolah, website, media sosial). | Business |
| Penilaian interaksi kontak | Skor ketertarikan peminat berdasarkan interaksinya, misalnya membuka email, bertanya, atau mengunjungi website. | Business |
| Alur prospek (pipeline) | Tahapan peminat: kenal → tertarik → daftar → bayar → daftar ulang. | Business |
| Status follow up | Mencatat siapa yang sudah/belum dihubungi dan hasil komunikasinya. | Business |
| Dasbor & analitik | Rasio konversi peminat menjadi pendaftar dan efektivitas tiap kanal promosi. | Business |
| Custom landing page | Membuat halaman promosi khusus, misalnya untuk kampanye jalur prestasi. | Professional |

**Manfaat utama:** konversi peminat menjadi mahasiswa meningkat dan kanal promosi paling efektif bisa diukur.

---

## 5. Maukuliah — Marketplace Pencarian Kampus

**Ringkasan:** Maukuliah.id adalah platform publik tempat calon mahasiswa mencari dan membandingkan kampus. Kampus pelanggan SEVIMA mendapat profil dan eksposur di platform ini.

**Pengguna:** calon mahasiswa (publik), tim promosi kampus.

**Tersedia di:** semua paket.

| Fitur | Penjelasan | Tersedia di |
|---|---|---|
| Profil kampus di maukuliah.id | Halaman profil kampus yang bisa ditemukan calon mahasiswa. | Growth |
| Tes potensi | Tes minat bakat (RIASEC), gaya belajar, dan kepribadian (MBTI) untuk membantu calon mahasiswa memilih jurusan. | Growth |
| Try out seleksi masuk PTN | Latihan soal seleksi masuk perguruan tinggi negeri, untuk menarik siswa SMA ke platform. | Business |

---

## 6. RPL — Rekognisi Pembelajaran Lampau

**Ringkasan:** RPL adalah program pemerintah untuk **mengakui pengalaman kerja atau pendidikan sebelumnya** sebagai SKS. Contoh: karyawan berpengalaman 10 tahun bisa mendapat konversi sejumlah mata kuliah sehingga masa studinya lebih singkat. Modul ini mengelola proses dari pendaftaran sampai penetapan mata kuliah yang diakui.

**Pengguna:** panitia RPL, asesor, prodi, pendaftar RPL.

**Tersedia di:** Professional ke atas (juga tersedia sebagai add on Rp 4.000.000/bulan).

| Fitur | Penjelasan |
|---|---|
| Syarat dokumen RPL | Mengatur dokumen yang wajib diunggah (sertifikat, surat pengalaman kerja, portofolio). |
| Jenis program & jalur pendaftaran | Mengatur jenis RPL (RPL tipe A/B, perolehan SKS/transfer) dan jalurnya. |
| Tarif formulir | Biaya pendaftaran RPL. |
| Mata kuliah RPL | Daftar mata kuliah yang bisa diajukan untuk direkognisi. |
| Jadwal & komposisi seleksi | Jadwal asesmen dan bobot komponennya (portofolio, wawancara, tes). |
| Verifikasi berkas & distribusi asesor | Memeriksa kelengkapan berkas dan membagi pendaftar ke asesor (penilai). |
| Pengisian nilai & kelulusan | Asesor mengisi nilai; sistem menentukan mata kuliah yang diakui. |
| Rekapitulasi pendaftar RPL | Laporan jumlah dan status pendaftar RPL. |

---

# C. Akademik & Pembelajaran

## 7. SIAKAD — Administrasi Akademik & Operasional Kampus

**Ringkasan:** SIAKAD (Sistem Informasi Akademik) adalah **inti** platform. Semua kegiatan akademik mahasiswa dari masuk sampai lulus tercatat di sini: data mahasiswa dan dosen, kurikulum, jadwal kuliah, KRS, presensi, nilai, hingga tugas akhir.

**Pengguna:** BAAK, prodi, dosen, mahasiswa, orang tua.

**Tersedia di:** semua paket.

### 7.1 Data Master
| Fitur | Penjelasan |
|---|---|
| Data & aktivitas mahasiswa | Biodata, status, riwayat akademik, dan aktivitas mahasiswa. |
| Data dosen | Biodata, homebase prodi, dan riwayat mengajar dosen. |

### 7.2 Siklus Perkuliahan
| Fitur | Penjelasan |
|---|---|
| Kelas, jadwal & dosen pengajar | Membuka kelas per semester, mengatur jadwal, dan menugaskan dosen. |
| Jurnal mengajar & RPS | Dosen mencatat materi tiap pertemuan (jurnal) dan menghubungkannya dengan RPS. |
| Mata kuliah, kurikulum, prasyarat & ekuivalensi | Mengelola kurikulum, mata kuliah prasyarat, dan penyetaraan mata kuliah antar-kurikulum lama dan baru. |
| Unsur bobot nilai | Mengatur komponen nilai (tugas, UTS, UAS, dll) dan konversi angka ke huruf (A, B+, B, ...). |
| Utilitas ruang kuliah | Memantau pemakaian ruang dan mencegah jadwal bentrok. |
| Paket mata kuliah | Paket KRS otomatis, misalnya untuk mahasiswa semester awal. |

### 7.3 KRS, Presensi & Nilai
| Fitur | Penjelasan |
|---|---|
| KRS online (lebih lengkap) | Mahasiswa memilih mata kuliah per semester secara online; dosen wali menyetujui. |
| Presensi mahasiswa | Pencatatan kehadiran per pertemuan. |
| Pengisian nilai online oleh dosen | Dosen menginput nilai langsung; sudah termasuk pengaturan skala nilai. |
| Perwalian / dosen penasehat | Penetapan dosen wali, persetujuan KRS, dan catatan konsultasi bimbingan akademik. |

### 7.4 Pencekalan (Blokir Otomatis)
Pencekalan memblokir akses mahasiswa ke layanan tertentu jika belum memenuhi syarat. Dispensasi (pengecualian) tetap bisa diberikan.

| Jenis Cekal | Penjelasan |
|---|---|
| Cekal keuangan untuk KRS & ujian | Mahasiswa yang belum melunasi tagihan tidak bisa mengisi KRS/ikut ujian. |
| Cekal akademik untuk KRS | Misalnya: belum lulus prasyarat atau IPK tidak memenuhi batas SKS. |
| Cekal kemahasiswaan untuk yudisium | Belum memenuhi poin kemahasiswaan/ada pelanggaran. |
| Cekal perpustakaan untuk yudisium | Masih memiliki pinjaman buku atau denda. |
| Cekal minimal kehadiran untuk ujian | Kehadiran di bawah batas minimal (misal 75%). |

### 7.5 Layanan Akademik Lain
| Fitur | Penjelasan |
|---|---|
| Transfer mahasiswa & double degree | Mahasiswa pindahan (konversi nilai) dan program gelar ganda. |
| Proposal & tugas akhir | Pengajuan judul, penetapan pembimbing, dan seminar/sidang. |
| Bimbingan tugas akhir | Catatan bimbingan antara mahasiswa dan dosen pembimbing. |
| Pengajuan cuti studi | Mahasiswa mengajukan cuti secara online. |
| Kalender akademik | Jadwal KRS, perkuliahan, UTS, UAS, dan libur. |
| Pemutihan nilai | Proses perbaikan/penghapusan nilai sesuai kebijakan kampus. |
| Penjadwalan kelas kelompok | Penjadwalan untuk kelas berbasis kelompok (praktikum, tutorial). |
| Info ke mahasiswa via email & SMS | Pengumuman massal ke mahasiswa. |
| Login sebagai orang tua | Orang tua bisa memantau nilai, kehadiran, dan tagihan anaknya. |

### 7.6 MBKM — Merdeka Belajar Kampus Merdeka
MBKM adalah program yang memberi mahasiswa hak belajar hingga 3 semester di luar prodi, misalnya magang, pertukaran pelajar, proyek desa, atau riset.

| Fitur | Penjelasan | Tersedia di |
|---|---|---|
| Pertukaran pelajar antar prodi | Mahasiswa internal mengambil kelas di prodi lain, atau mahasiswa eksternal belajar di kampus ini. | Growth |
| 8 aktivitas kampus merdeka sesuai IKU | Magang, pertukaran pelajar, asistensi mengajar, penelitian, proyek kemanusiaan, wirausaha, studi independen, dan KKN tematik. | Growth |
| Konversi mata kuliah | Kegiatan MBKM dikonversi menjadi SKS/mata kuliah. | Growth |
| Logbook & evaluasi | Mahasiswa mengisi logbook kegiatan; pembimbing mengevaluasi. | Growth |
| Penawaran aktivitas MBKM | Kampus mempublikasikan program MBKM yang bisa diikuti. | Growth |
| Mitra perguruan tinggi | Data kampus/instansi mitra MBKM. | Growth |
| Perhitungan pencapaian IKU | Otomatis menghitung kontribusi MBKM terhadap Indikator Kinerja Utama. | Enterprise |

---

## 8. EDLINK — Modul Pembelajaran (LMS)

**Ringkasan:** EDLINK adalah **LMS** (Learning Management System), yaitu ruang kelas digital tempat dosen membagikan materi, tugas, dan kuis, serta tempat perkuliahan hybrid/online berlangsung. Tersedia versi web dan aplikasi mobile.

**Pengguna:** dosen, mahasiswa, prodi.

**Tersedia di:** semua paket. Premium & Enterprise mendapat versi **Whitelabel**: web dan aplikasi Android memakai nama dan logo kampus sendiri.

### Kapasitas per paket
| | Growth | Business | Professional | Premium | Enterprise |
|---|---|---|---|---|---|
| Storage | 50 GB | 100 GB | 200 GB | 400 GB | 750 GB |
| Kelas akademik | Unlimited | Unlimited | Unlimited | Unlimited | Unlimited |
| Kelas umum | 10/semester | 10/semester | 10/semester | 10/semester | 10/semester |

> **Kelas akademik** = kelas yang berasal dari jadwal SIAKAD. **Kelas umum** = kelas non-akademik, misalnya pelatihan atau komunitas.

### Fitur
| Fitur | Penjelasan | Tersedia di |
|---|---|---|
| Perkuliahan hybrid (Web & Android) | Kuliah tatap muka dan daring dalam satu platform. | Growth |
| Dashboard aktivitas pembelajaran | Keaktifan dosen dan mahasiswa di kelas online. | Growth |
| Aplikasi mobile Android | Akses kelas lewat HP. | Growth |
| Presensi QR Code | Mahasiswa memindai QR yang ditampilkan dosen untuk absen. | Growth |
| Integrasi SPADA | Terhubung ke SPADA Indonesia (Sistem Pembelajaran Daring nasional Kemendikbud). | Growth |
| Smart dashboard monitoring perkuliahan online | Pimpinan memantau keterlaksanaan perkuliahan daring. | Growth |
| Manajemen & duplikasi bahan ajar (*) | Materi bisa disalin ke kelas/semester lain tanpa upload ulang. | Growth |
| MOOC | Kursus online terbuka yang bisa diikuti publik. | Growth |
| Gamification (*) | Poin, lencana, dan papan peringkat untuk meningkatkan motivasi belajar. | Growth |
| Student Engagement Score | Skor keterlibatan mahasiswa dari aktivitasnya di kelas. | Business |
| Integrasi CBT | Ujian di EDLINK memakai mesin CBT. | Business |
| Presensi pegawai | Absensi pegawai lewat aplikasi. | Business |
| Lecture Performance Score | Skor kinerja dosen dalam mengajar online (keaktifan, kelengkapan materi). | Business |
| Proctoring | Pengawasan ujian online (kamera, deteksi pindah tab) untuk mencegah kecurangan. | Professional |
| Penilaian dokumen PDF | Dosen memberi koreksi/anotasi langsung di file tugas PDF. | Professional |
| Aplikasi mobile iOS | Versi iPhone. | Professional |
| Presensi AI | Absen dengan pengenalan wajah. | Premium |
| Video interaktif | Video pembelajaran yang disisipi pertanyaan/kuis. | Premium |

---

## 9. CBT & CBT AI — Ujian Online

**Ringkasan:** CBT (Computer Based Test) adalah sistem ujian berbasis komputer: bank soal, penjadwalan ujian, pengerjaan online, dan penilaian otomatis. CBT AI menambahkan kemampuan **membuat soal secara otomatis** menggunakan AI.

**Pengguna:** dosen, panitia ujian, panitia PMB, mahasiswa.

**Tersedia di:** Business ke atas.

| Modul | Penjelasan |
|---|---|
| CBT — Modul Test Online | Bank soal, pengacakan soal, timer, dan penilaian otomatis. Dipakai untuk UTS/UAS, kuis, dan seleksi PMB. |
| CBT AI | Dosen memasukkan topik/materi, lalu AI menghasilkan draf soal yang bisa direvisi sebelum dipakai. |

---

## 10. OBE — Kurikulum Outcome Based Education

**Ringkasan:** OBE adalah pendekatan kurikulum yang dimulai dari **hasil akhir** (profil lulusan yang diharapkan), lalu diturunkan ke capaian pembelajaran dan mata kuliah. OBE menjadi tuntutan akreditasi (LAM/BAN-PT) dan akreditasi internasional.

Alurnya: **Profil Lulusan → CPL → Mata Kuliah → CPMK → RPS & Penilaian.**

**Pengguna:** prodi, tim kurikulum, dosen, LPM.

**Tersedia di:** Professional ke atas (juga add on Rp 3.000.000/bulan; versi OBE AI mulai Rp 3.000.000/bulan untuk paket Professional ke atas).

| Fitur | Penjelasan |
|---|---|
| Profil lulusan | Mendefinisikan peran lulusan, misalnya "Software Engineer" atau "Peneliti". |
| Capaian Pembelajaran Lulusan (CPL) | Kemampuan yang harus dimiliki lulusan (sikap, pengetahuan, keterampilan umum & khusus). |
| Mata kuliah & RPS | Mata kuliah beserta Rencana Pembelajaran Semesternya. |
| Kurikulum prodi | Struktur kurikulum per prodi. |
| Pemetaan CPL ke mata kuliah | Menentukan mata kuliah mana yang mendukung tiap CPL. |
| Pemetaan CPMK | Capaian Pembelajaran Mata Kuliah, yaitu turunan CPL di level mata kuliah. |
| Pemetaan profil lulusan ke CPL | Menghubungkan profil lulusan dengan CPL. |
| Rencana pembelajaran & evaluasi | Metode belajar dan asesmen untuk mengukur CPMK. |
| Komposisi nilai perkuliahan | Bobot penilaian yang terhubung ke CPMK, sehingga ketercapaian CPL tiap mahasiswa bisa dihitung. |

**Manfaat utama:** ketercapaian CPL bisa diukur dengan data, bukan perkiraan. Ini menjadi bukti kuat saat akreditasi.

---

## 11. EDOM — Evaluasi Dosen oleh Mahasiswa

**Ringkasan:** kuesioner yang diisi mahasiswa untuk menilai kualitas mengajar dosen setiap semester. Hasilnya dipakai untuk evaluasi dosen dan penjaminan mutu.

**Pengguna:** LPM, prodi, mahasiswa, dosen.

**Tersedia di:** semua paket.

| Fitur | Penjelasan |
|---|---|
| Form EDOM kustom | Kampus menyusun sendiri pertanyaan dan skala penilaiannya. |
| Pengisian oleh mahasiswa | Mahasiswa mengisi secara online. Pengisian bisa dijadikan syarat sebelum melihat nilai. |
| Rekap per dosen & per kelas | Hasil penilaian per dosen dan per kelas sebagai dasar pembinaan. |

---

# D. Keuangan

## 12. KEU — Administrasi Keuangan Mahasiswa

**Ringkasan:** mengelola semua tagihan dan pembayaran dari mahasiswa, seperti SPP/UKT, biaya per SKS, denda, dan cicilan, beserta pemantauan piutang.

**Pengguna:** bagian keuangan, mahasiswa, pimpinan.

**Tersedia di:** semua paket.

| Fitur | Penjelasan | Tersedia di |
|---|---|---|
| Pembayaran online | Bayar via bank/e-commerce dan terverifikasi otomatis. Jumlah channel: Growth & Business **3**, Professional **4**, Premium **5**, Enterprise **6**. | Growth |
| Tarif uang kuliah (lebih lengkap) | Mengatur tarif per angkatan, prodi, jalur, dan kelas. | Growth |
| Pembayaran tunai & upload bukti bayar | Untuk pembayaran di loket atau transfer manual. | Growth |
| Monitoring piutang & status pembayaran | Daftar mahasiswa yang belum lunas dan total piutang kampus. | Growth |
| Tagihan wajib & per SKS | Tagihan tetap (SPP) dan tagihan variabel berdasarkan jumlah SKS yang diambil. | Growth |
| Beasiswa / potongan | Potongan tagihan untuk penerima beasiswa. | Growth |
| Denda | Denda keterlambatan pembayaran. | Growth |
| Deposit | Saldo titipan mahasiswa yang bisa dipakai untuk tagihan berikutnya. | Growth |
| Voucher tagihan | Kode voucher potongan, misalnya untuk promosi PMB. | Growth |
| Pengaturan & validasi penerima UKT | Penetapan golongan UKT (Uang Kuliah Tunggal) per mahasiswa. | Growth |
| Cicilan tagihan | Tagihan bisa dibayar bertahap. | Growth |

> Terhubung dengan **cekal keuangan** di SIAKAD: mahasiswa yang belum lunas otomatis tidak bisa KRS/ujian.

---

# E. Kemahasiswaan & Kelulusan

## 13. Poin Kemahasiswaan

**Ringkasan:** mencatat kegiatan non-akademik mahasiswa dalam bentuk poin, misalnya organisasi, lomba, dan seminar. Poin ini sering menjadi syarat yudisium.

**Pengguna:** bagian kemahasiswaan, mahasiswa.

**Tersedia di:** semua paket.

| Fitur | Penjelasan |
|---|---|
| Aktivitas & prestasi | Mahasiswa mengajukan kegiatan/prestasi; kemahasiswaan memverifikasi dan memberi poin. |
| Pelanggaran | Pencatatan pelanggaran tata tertib beserta sanksinya. |
| Beasiswa mahasiswa | Pengelolaan penerima beasiswa. |
| Surat keterangan aktif kuliah | Pengajuan dan penerbitan surat keterangan mahasiswa aktif. |

---

## 14. Yudisium & Wisuda

**Ringkasan:** yudisium adalah penetapan kelulusan mahasiswa secara resmi, dan wisuda adalah upacara kelulusannya. Modul ini mengatur keduanya.

**Pengguna:** BAAK, fakultas, panitia wisuda, mahasiswa tingkat akhir.

**Tersedia di:** semua paket.

| Fitur | Penjelasan |
|---|---|
| Periode & syarat yudisium | Menentukan jadwal yudisium dan syaratnya (SKS, IPK, bebas tunggakan, poin, dll). |
| Peserta & status kelulusan | Daftar peserta dan predikat kelulusan (cumlaude, sangat memuaskan, dll). |
| Penomoran dokumen | Nomor SK yudisium, ijazah, dan transkrip dibuat otomatis. |
| Eligible yudisium | Sistem otomatis mengecek mahasiswa yang memenuhi syarat. |
| Periode, peserta & buku wisuda | Pendaftaran wisuda dan pembuatan buku wisuda (daftar wisudawan). |

---

## 15. SKPI — Surat Keterangan Pendamping Ijazah

**Ringkasan:** SKPI adalah dokumen resmi pendamping ijazah yang memuat capaian dan prestasi lulusan (sertifikat, organisasi, magang) dalam dua bahasa. SKPI menjadi nilai tambah lulusan saat melamar kerja.

**Tersedia di:** semua paket.

---

## 16. TRACER — Tracer Study

**Ringkasan:** tracer study adalah survei untuk alumni dan pengguna lulusan (perusahaan) guna mengetahui waktu tunggu kerja, kesesuaian bidang, dan kepuasan pengguna. Datanya **wajib** untuk akreditasi dan pelaporan IKU.

**Pengguna:** pusat karir, LPM, alumni, perusahaan pengguna lulusan.

**Tersedia di:** semua paket.

| Fitur | Penjelasan | Kapasitas |
|---|---|---|
| Kuesioner & unduh Excel | Membuat kuesioner sendiri dan mengunduh hasilnya. | — |
| Kuesioner standar Kemdikbud & BAN-PT | Template kuesioner sesuai standar nasional dan akreditasi. | — |
| Jumlah kuesioner terkirim | Batas kuesioner yang bisa dikirim. | Growth 50, Business 50, Professional 200, Premium 300, Enterprise 400 |
| Email ke alumni & pengguna lulusan | Kuesioner dikirim lewat email. | — |
| Reminder pengisian | Pengingat otomatis untuk yang belum mengisi. | — |
| Detail status responden | Siapa yang sudah dan belum mengisi. | — |
| Status karir alumni | Data alumni: bekerja, wirausaha, studi lanjut, atau mencari kerja. | — |
| Dashboard outcome sesuai APT & APS | Hasil tracer sesuai format akreditasi institusi (APT) dan prodi (APS). | — |

---

## 17. Karirlink — Career Center

**Ringkasan:** pusat karir digital yang menghubungkan mahasiswa/alumni dengan perusahaan: info lowongan, lamaran kerja, dan relasi dengan mitra industri.

**Pengguna:** pusat karir, mahasiswa, alumni, perusahaan mitra.

**Tersedia di:** modul lengkap mulai Professional. Fitur dasar melalui marketplace karirlink.id tersedia di semua paket.

| Fitur | Penjelasan | Tersedia di |
|---|---|---|
| Marketplace karirlink.id | Platform lowongan kerja bersama yang bisa diakses mahasiswa/alumni. | Growth |
| Portal karir (Web & Mobile) | Growth & Business: tampilan **default** karirlink. Professional ke atas: **whitelabel** dengan nama dan logo kampus. | Growth |
| Unduh CV lulusan | Perusahaan/kampus mengunduh CV alumni. | Growth |
| Melamar via aplikasi | Alumni melamar langsung dari platform. | Growth |
| Undang mitra perusahaan | Kampus mengundang perusahaan bergabung. | Growth |
| Post & monitoring lowongan | Perusahaan mitra memasang dan memantau lowongan. | Growth |
| Manajemen relasi | Data dan riwayat hubungan dengan perusahaan mitra. | Growth |
| Publikasi hasil tracer study | Hasil tracer ditampilkan di portal karir. | Growth |
| Tips & artikel karir | Konten pengembangan karir. | Professional |
| Rekomendasi lulusan ke mitra | Kampus merekomendasikan lulusan tertentu ke perusahaan. | Professional |

---

# F. SDM & Penelitian

## 18. SDM — Pengelolaan Kepegawaian

**Ringkasan:** mengelola data dan administrasi dosen serta tenaga kependidikan: presensi, cuti, kinerja dosen, remunerasi, sampai sinkronisasi dengan SISTER (sistem SDM dosen nasional milik Kemendikbud).

**Pengguna:** bagian kepegawaian/SDM, dosen, tendik, pimpinan.

**Tersedia di:** Business ke atas. Modul Kepegawaian SISTER juga tersedia sebagai add on Rp 3.000.000/bulan, mulai paket Business.

| Fitur | Penjelasan | Tersedia di |
|---|---|---|
| Data kepegawaian | Biodata, riwayat jabatan, pangkat, dan pendidikan pegawai. | Business |
| Presensi online / fingerprint | Absen dari aplikasi atau terintegrasi dengan mesin sidik jari. | Business |
| Laporan kehadiran & kegiatan | Rekap kehadiran dan aktivitas pegawai. | Business |
| Izin, cuti & validasi kehadiran | Pengajuan izin/cuti online dengan persetujuan atasan. | Business |
| Integrasi SISTER Cloud Dikti | Sinkronisasi data dosen dengan SISTER, sehingga dosen tidak perlu input dua kali. | Business |
| Data penelitian & pengabdian | Riwayat penelitian, pengabdian, dan publikasi dosen. | Business |
| BKD — Beban Kinerja Dosen | Laporan kinerja dosen per semester (pendidikan, penelitian, pengabdian, penunjang), yang wajib untuk dosen bersertifikat. | Business |
| Remunerasi dosen | Perhitungan tunjangan/insentif berdasarkan kinerja. | Business |
| Honor & vakasi (*) | Perhitungan honor mengajar dan honor koreksi/pengawasan ujian (vakasi). | Business |
| Portofolio dosen (*) | Profil lengkap capaian dosen. | Business |
| PAK — Perhitungan Angka Kredit (*) | Menghitung angka kredit untuk kenaikan jabatan fungsional dosen (Asisten Ahli → Lektor → Lektor Kepala → Guru Besar). | Professional |

---

## 19. Litabmas — Penelitian & Pengabdian Masyarakat

**Ringkasan:** sistem untuk LPPM (Lembaga Penelitian dan Pengabdian kepada Masyarakat) yang mengelola **hibah internal** penelitian dan pengabdian dosen, mulai dari pembukaan pendanaan hingga laporan akhir.

**Pengguna:** LPPM, dosen peneliti, reviewer.

**Tersedia di:** Premium ke atas.

| Tahap | Penjelasan |
|---|---|
| Perencanaan pendanaan | Menentukan sumber dana, klaster pendanaan (misalnya penelitian dasar/terapan), akun kolaborasi, dan komposisi penilaian proposal. |
| Integrasi data | Data mahasiswa, dosen, dan publikasi dosen otomatis tersedia untuk pengusulan. |
| Pengumuman pendanaan | Publikasi pembukaan hibah ke dosen. |
| Proses seleksi | Seleksi administrasi → seleksi nominasi (review) → penetapan proposal yang didanai. |
| Pelaksanaan | Pemantauan kegiatan penelitian/pengabdian yang berjalan. |
| Penilaian & pelaporan | Laporan kemajuan dan laporan akhir beserta penilaiannya. |
| Pendanaan & penerimaan proposal | Pengelolaan dana yang dicairkan dan proposal yang diterima. |

---

# G. Mutu & Akreditasi

## 20. SPME — Manajemen Akreditasi

**Ringkasan:** SPME (Sistem Penjaminan Mutu Eksternal) membantu kampus menyiapkan akreditasi dari BAN-PT. Data dari SIAKAD, SDM, keuangan, dan modul lain otomatis diolah ke format instrumen akreditasi.

**Pengguna:** LPM, tim akreditasi prodi/institusi.

**Tersedia di:** Professional ke atas. Premium & Enterprise **termasuk Simulasi Akreditasi**, yaitu perkiraan skor akreditasi sebelum pengajuan resmi.

| Fitur | Penjelasan |
|---|---|
| IAPT 4.0 | Instrumen Akreditasi Perguruan Tinggi (level institusi) versi 4.0 dari BAN-PT. |
| IAPS 4.0 | Instrumen Akreditasi Program Studi versi 4.0 dari BAN-PT. |

---

## 21. SPMI — Sistem Penjaminan Mutu Internal

**Ringkasan:** SPMI adalah sistem mutu yang **wajib** dijalankan kampus sendiri (siklus PPEPP: Penetapan, Pelaksanaan, Evaluasi, Pengendalian, Peningkatan). Bagian terpentingnya adalah **AMI (Audit Mutu Internal)**: auditor internal menilai unit-unit kampus terhadap standar mutu.

**Pengguna:** LPM, auditor internal, unit yang diaudit (auditee).

**Tersedia di:** Professional ke atas.

| Fitur | Penjelasan |
|---|---|
| Dokumen mutu | Kebijakan, manual, standar, dan formulir SPMI. |
| Instrumen LKPS & LED | Lembar Kinerja Program Studi (data kuantitatif) dan Laporan Evaluasi Diri (narasi). |
| Matriks penilaian | Rubrik penilaian tiap indikator. |
| Peringkat SPMI & bobot indikator | Bobot setiap indikator dan kategori peringkat. |
| Periode & target AMI | Jadwal audit dan target capaian. |
| SK auditor & surat tugas | Penetapan auditor dan penugasannya. |
| Instrumen LK & LED | Lembar Kerja audit dan Laporan Evaluasi Diri unit. |
| Penilaian, temuan & berita acara | Hasil audit, temuan ketidaksesuaian, dan berita acara. |
| Laporan hasil akhir SPMI | Laporan akhir audit sebagai dasar perbaikan. |

---

## 22. Dashboard IKU

**Ringkasan:** IKU (Indikator Kinerja Utama) adalah 8 indikator kinerja perguruan tinggi yang ditetapkan Kemendikbudristek. Contohnya: lulusan mendapat pekerjaan layak, mahasiswa berkegiatan di luar kampus (MBKM), dosen berkegiatan di luar kampus, praktisi mengajar, hasil kerja dosen dimanfaatkan masyarakat, kerja sama prodi dengan mitra, kelas kolaboratif, dan prodi berstandar internasional. Dashboard ini menghitung capaian IKU dari data modul-modul lain.

**Tersedia di:** Professional ke atas.

---

## 23. Manajemen Kerjasama

**Ringkasan:** mengelola kerja sama kampus dengan mitra (instansi, industri, kampus lain), mulai dari dokumen hingga pelaporannya ke IKU dan SPMI.

**Tersedia di:** Professional ke atas. Sebagian fitur masih development (*).

| Fitur | Penjelasan |
|---|---|
| Data mitra | Profil instansi mitra. |
| Data kerja sama | Dokumen MoU (nota kesepahaman), MoA (perjanjian kerja sama), dan IA (Implementation Arrangement), beserta masa berlakunya. |
| Integrasi Dashboard IKU (*) | Kerja sama otomatis dihitung untuk IKU "kerja sama dengan mitra". |
| Integrasi SPMI (*) | Data kerja sama dipakai sebagai bukti di audit mutu. |

---

# H. Layanan Administrasi & Integrasi

## 24. Tata Usaha

**Ringkasan:** layanan helpdesk dan pembuatan dokumen resmi akademik dengan format sesuai desain kampus.

**Tersedia di:** semua paket.

| Fitur | Penjelasan |
|---|---|
| Tiket pertanyaan & keluhan | Mahasiswa/pegawai mengajukan pertanyaan atau keluhan, yang dilacak sampai selesai. |
| Custom laporan/dokumen | SEVIMA menyesuaikan tampilan dokumen resmi sesuai format kampus: |
| ↳ KTM, KRS, KHS | Kartu Tanda Mahasiswa, Kartu Rencana Studi, Kartu Hasil Studi. |
| ↳ Transkrip lulusan & sementara | Kustomisasi 1x per jenjang (D3/S1/S2, dst). |
| ↳ SKPI | Kustomisasi 1x. |
| ↳ Ijazah | Kustomisasi 1x per jenjang. |
| ↳ Kartu ujian UTS/UAS | Kustomisasi 1x. |
| ↳ Sertifikat profesi | Untuk program profesi (Ners, Apoteker, PPG, dll). |

> **Catatan:** kustomisasi berlaku **1 kali**. Perubahan desain berikutnya kemungkinan dikenakan biaya. Sebaiknya ditanyakan ke SEVIMA.

---

## 25. Integrasi Sistem Lain

| Integrasi | Penjelasan | Tersedia di |
|---|---|---|
| Open API | Antarmuka untuk menghubungkan SEVIMA dengan aplikasi lain milik kampus, misalnya website, aplikasi internal, atau sistem perpustakaan lain. | Professional |
| Integrasi SLiMS | Terhubung dengan SLiMS (Senayan Library Management System), aplikasi perpustakaan open source yang banyak dipakai kampus. Mendukung cekal perpustakaan. | Premium |
| Integrasi BIOS | BIOS adalah sistem pelaporan dari Kementerian Keuangan untuk **Badan Layanan Umum (BLU)**, relevan untuk PTN BLU. Mengirim data keuangan, SDM pendidikan, dan layanan. | Professional |
| Tanda Tangan Elektronik (TTE) (***) | Dokumen akademik ditandatangani secara elektronik dan tersertifikasi. **Biaya integrasi & aktivasi TTE tidak termasuk paket.** | Business |

---

# I. Add On

## 26. Add On Sesuai Jenis Perguruan Tinggi

Fitur-fitur ini **sudah termasuk paket**, tetapi hanya diaktifkan untuk kampus dengan jenis tertentu.

| Add On | Untuk Kampus | Penjelasan | Tersedia di |
|---|---|---|---|
| Pelaporan EMIS KEMENAG | PTKI (kampus di bawah Kementerian Agama: UIN, IAIN, STAI, dll) | EMIS adalah sistem data pendidikan Kemenag, padanan PDDikti untuk kampus keagamaan Islam. Data master mahasiswa & lulusan tersedia mulai Growth; data dosen & riwayat dosen mulai Business. | Growth / Business |
| Laporan evaluasi pembelajaran Politeknik | Politeknik | Format laporan evaluasi khusus pendidikan vokasi. | Growth |
| Pelaporan PPSDM Migas | Kampus di bawah Kementerian ESDM (bidang minyak & gas) | Pelaporan ke Pusat Pengembangan SDM Migas. | Growth |
| Pelaporan PPSDM PU | Kampus kedinasan (tertulis: di bawah Kementerian Perhubungan) | Pelaporan ke PPSDM terkait. | Growth |
| Modul Kampus Kesehatan | Kampus kesehatan (keperawatan, kebidanan, kedokteran, farmasi) | UKOM (*): pengelolaan Uji Kompetensi nasional bagi lulusan kesehatan. Kepaniteraan Klinik (*): pencatatan praktik klinik/koas di rumah sakit. | Growth |
| Pendidikan Profesi Guru (PPG) | Kampus LPTK penyelenggara PPG | Pengelolaan program profesi guru. Kapasitas tambahan: Rp 4.000.000/bulan per 1.000 mahasiswa PPG. | Business |
| Integrasi SIMPONI KEMENKEU | PTN/kampus kedinasan | SIMPONI adalah sistem pembayaran PNBP (Penerimaan Negara Bukan Pajak) milik Kemenkeu. Pembayaran mahasiswa tercatat sebagai PNBP. | Business |

---

## 27. Add On Berbayar

### 27.1 Add On dalam Kontrak Paket

| Add On | Penjelasan | Minimal Paket | Harga / Bulan |
|---|---|---|---:|
| Outcome Based Education AI | Modul OBE yang dibantu AI untuk menyusun CPL, CPMK, dan RPS. | Professional | mulai Rp 3.000.000 |
| Manajemen OBE | Modul OBE (lihat [bagian 10](#10-obe--kurikulum-outcome-based-education)) untuk paket yang belum mendapatkannya. | — | Rp 3.000.000 |
| RPL | Modul RPL (lihat [bagian 6](#6-rpl--rekognisi-pembelajaran-lampau)) untuk paket yang belum mendapatkannya. | — | Rp 4.000.000 |
| Modul Kepegawaian SISTER | Integrasi kepegawaian dengan SISTER Dikti. | Business | Rp 3.000.000 |
| Kapasitas mahasiswa PPG | Tambahan kuota per 1.000 mahasiswa PPG. | — | Rp 4.000.000 |

### 27.2 Add On dengan Kontrak Terpisah

| Add On | Penjelasan | Minimal Paket | Harga |
|---|---|---|---:|
| Finance Cloud | Sistem akuntansi kampus (jurnal, buku besar, neraca, laba rugi) yang terintegrasi dengan modul keuangan mahasiswa. Modul KEU hanya mengurus tagihan mahasiswa, sedangkan Finance Cloud mengurus akuntansi seluruh kampus. | — | Rp 4.000.000 / bulan |
| e-Office | Surat menyurat digital: surat masuk/keluar, disposisi pimpinan, dan arsip. | Professional | Rp 3.000.000 / bulan |
| e-Sign Ijazah | Tanda tangan elektronik khusus ijazah. | Business | Inisiasi Rp 4.500.000 + Rp 15.000 / dokumen |
| Modul Khusus Fakultas Kedokteran | Penjadwalan kedokteran; perkuliahan kedokteran (RPS, realisasi, presensi mahasiswa & dosen); perkuliahan **blok** dengan kurikulum OBE (sistem pendidikan kedokteran yang menggabungkan beberapa mata kuliah dalam satu tema/blok); laporan beban mengajar; pelaporan Neofeeder. | Business | Rp 30.000.000 / bulan |

---

# J. Layanan Implementasi

## 28. Implementasi & Training

| Layanan | Penjelasan | Growth | Business | Professional | Premium | Enterprise |
|---|---|---|---|---|---|---|
| Implementasi | Instalasi dan konfigurasi sistem. | ✅ | ✅ | ✅ | ✅ | ✅ |
| Migrasi data awal | Memindahkan data lama ke SEVIMA. | Neofeeder + Excel | Neofeeder + Excel | Neofeeder + Excel | Neofeeder + Excel | + database sistem lama |
| Onboarding training | Pelatihan awal untuk pengguna (maks 3 jam/sesi). | 10 sesi | 14 sesi | 18 sesi | 24 sesi | 30 sesi |
| Account Manager dedicated | Kontak khusus SEVIMA yang mendampingi kampus. | — | — | — | — | 2 AM |
| Refreshment training private | Pelatihan ulang khusus untuk kampus Anda. | 1 | 2 | 2 | 3 | 4 |
| Bimtek bulanan | Bimbingan teknis rutin bersama (gabungan dengan kampus lain). | ✅ | ✅ | ✅ | ✅ | ✅ |

**Biaya tambahan:** pelatihan atau visitasi di luar kuota dikenakan **Rp 1.500.000 per sesi per trainer**.

**Migrasi data — artinya bagi kampus:**
- **Neofeeder + Excel:** data diambil dari Neo Feeder dan file Excel yang disiapkan kampus. Data historis yang hanya ada di sistem lama perlu diekspor sendiri ke Excel.
- **Sistem lama (Enterprise):** SEVIMA langsung menarik data dari database aplikasi lama kampus.

---

# Lampiran

## Peta Modul per Paket

| Modul | Growth | Business | Professional | Premium | Enterprise |
|---|:-:|:-:|:-:|:-:|:-:|
| Smart Dashboard | Report | Report | Report & Dashboard | Report & Dashboard | Report & Dashboard |
| Integrasi PDDikti Neo Feeder | ✅ | ✅ | ✅ | ✅ | ✅ |
| PMB Online | ✅ | ✅ | ✅ | ✅ | ✅ |
| Maukuliah | ✅ | ✅ | ✅ | ✅ | ✅ |
| SIAKAD (termasuk MBKM) | ✅ | ✅ | ✅ | ✅ | ✅ + IKU |
| KEU — Keuangan Mahasiswa | 3 channel | 3 channel | 4 channel | 5 channel | 6 channel |
| EDLINK (LMS) | ✅ | ✅ | ✅ | Whitelabel | Whitelabel |
| EDOM | ✅ | ✅ | ✅ | ✅ | ✅ |
| Yudisium & Wisuda | ✅ | ✅ | ✅ | ✅ | ✅ |
| Poin Kemahasiswaan | ✅ | ✅ | ✅ | ✅ | ✅ |
| SKPI | ✅ | ✅ | ✅ | ✅ | ✅ |
| Tracer Study | ✅ | ✅ | ✅ | ✅ | ✅ |
| Tata Usaha | ✅ | ✅ | ✅ | ✅ | ✅ |
| CBT & CBT AI | — | ✅ | ✅ | ✅ | ✅ |
| SDM — Kepegawaian | — | ✅ | ✅ | ✅ | ✅ |
| CRM CAMABA | — | ✅ | ✅ | ✅ | ✅ |
| TTE (biaya aktivasi terpisah) | — | ✅ | ✅ | ✅ | ✅ |
| RPL | — | — | ✅ | ✅ | ✅ |
| OBE | — | — | ✅ | ✅ | ✅ |
| Karirlink (lengkap) | — | — | ✅ | ✅ | ✅ |
| SPME — Akreditasi | — | — | ✅ | ✅ + Simulasi | ✅ + Simulasi |
| SPMI | — | — | ✅ | ✅ | ✅ |
| Dashboard IKU | — | — | ✅ | ✅ | ✅ |
| Manajemen Kerjasama (*) | — | — | ✅ | ✅ | ✅ |
| Open API | — | — | ✅ | ✅ | ✅ |
| Integrasi BIOS | — | — | ✅ | ✅ | ✅ |
| Litabmas | — | — | — | ✅ | ✅ |
| Integrasi SLiMS | — | — | — | ✅ | ✅ |

---

## Glosarium

| Istilah | Kepanjangan / Arti |
|---|---|
| AMI | Audit Mutu Internal, yaitu audit oleh auditor internal kampus dalam siklus SPMI. |
| APT / APS | Akreditasi Perguruan Tinggi / Akreditasi Program Studi. |
| BAAK | Biro Administrasi Akademik dan Kemahasiswaan. |
| BAN-PT | Badan Akreditasi Nasional Perguruan Tinggi. |
| BKD | Beban Kerja/Kinerja Dosen, laporan kinerja Tridharma dosen per semester. |
| BLU | Badan Layanan Umum, status pengelolaan keuangan PTN tertentu. |
| CAMABA | Calon Mahasiswa Baru. |
| CBT | Computer Based Test, ujian berbasis komputer. |
| CPL | Capaian Pembelajaran Lulusan. |
| CPMK | Capaian Pembelajaran Mata Kuliah. |
| CRM | Customer Relationship Management. |
| EDOM | Evaluasi Dosen oleh Mahasiswa. |
| EMIS | Education Management Information System milik Kemenag. |
| IAPT / IAPS | Instrumen Akreditasi Perguruan Tinggi / Program Studi. |
| IKU | Indikator Kinerja Utama perguruan tinggi (Kemendikbudristek). |
| KHS | Kartu Hasil Studi (nilai per semester). |
| KIP Kuliah | Kartu Indonesia Pintar Kuliah, bantuan biaya kuliah dari pemerintah. |
| KRS | Kartu Rencana Studi (mata kuliah yang diambil per semester). |
| KTM | Kartu Tanda Mahasiswa. |
| LED | Laporan Evaluasi Diri. |
| LKPS | Lembar Kinerja Program Studi. |
| LMS | Learning Management System, platform pembelajaran online. |
| LoA | Letter of Acceptance, surat penerimaan. |
| LPM / LPPM | Lembaga Penjaminan Mutu / Lembaga Penelitian dan Pengabdian kepada Masyarakat. |
| MBKM | Merdeka Belajar Kampus Merdeka. |
| MoU / MoA / IA | Memorandum of Understanding / Memorandum of Agreement / Implementation Arrangement. |
| MOOC | Massive Open Online Course. |
| NIM | Nomor Induk Mahasiswa. |
| OBE | Outcome Based Education. |
| ODS | One Day Service (daftar–tes–pengumuman dalam sehari). |
| PAK | Penilaian/Perhitungan Angka Kredit dosen. |
| PDDikti | Pangkalan Data Pendidikan Tinggi. |
| PDM | Perubahan Data Mahasiswa di PDDikti. |
| PISN | Penomoran Ijazah dan Sertifikat Profesi Nasional. |
| PMB | Penerimaan Mahasiswa Baru. |
| PNBP | Penerimaan Negara Bukan Pajak. |
| PPG | Pendidikan Profesi Guru. |
| Proctoring | Pengawasan ujian online. |
| RPL | Rekognisi Pembelajaran Lampau. |
| RPS | Rencana Pembelajaran Semester. |
| SIAKAD | Sistem Informasi Akademik. |
| SIMPONI | Sistem Informasi PNBP Online (Kemenkeu). |
| SISTER | Sistem Informasi Sumberdaya Terintegrasi (SDM dosen, Kemendikbud). |
| SKPI | Surat Keterangan Pendamping Ijazah. |
| SKS | Satuan Kredit Semester. |
| SLiMS | Senayan Library Management System (aplikasi perpustakaan). |
| SPADA | Sistem Pembelajaran Daring Indonesia. |
| SPME / SPMI | Sistem Penjaminan Mutu Eksternal / Internal. |
| Tendik | Tenaga kependidikan (staf non-dosen). |
| TTE | Tanda Tangan Elektronik. |
| UKOM | Uji Kompetensi (lulusan bidang kesehatan). |
| UKT | Uang Kuliah Tunggal. |
| Whitelabel | Aplikasi/portal memakai nama dan logo kampus sendiri, bukan merek SEVIMA. |
| Yudisium | Penetapan kelulusan mahasiswa secara resmi. |
