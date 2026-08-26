# Rancangan Aplikasi Manajemen Akademik Universitas

> **Status:** Dokumen landasan (blueprint) pengembangan.
> **Dibuat:** 2026-07-18
> Dokumen ini adalah acuan resmi untuk pengembangan sistem. Setiap modul, struktur data, role, dan alur kerja yang dibangun di repo ini harus merujuk ke dokumen ini sebagai sumber kebenaran. Perubahan besar pada cakupan (scope) sebaiknya didiskusikan dan dokumen ini diperbarui, bukan diabaikan.

## Daftar Isi

1. [Gambaran Umum](#1-gambaran-umum)
2. [Tujuan Sistem](#2-tujuan-sistem)
3. [Role dan Hak Akses Pengguna](#3-role-dan-hak-akses-pengguna)
   - [3.1 Super Admin](#31-super-admin)
   - [3.2 Rektor dan Wakil Rektor](#32-rektor-dan-wakil-rektor)
   - [3.3 Dekan](#33-dekan)
   - [3.4 Ketua Program Studi](#34-ketua-program-studi)
   - [3.5 Bagian Akademik](#35-bagian-akademik)
   - [3.6 Dosen](#36-dosen)
   - [3.7 Dosen Pembimbing Akademik](#37-dosen-pembimbing-akademik)
   - [3.8 Mahasiswa](#38-mahasiswa)
   - [3.9 Pegawai atau Tenaga Kependidikan](#39-pegawai-atau-tenaga-kependidikan)
   - [3.10 Bagian Keuangan](#310-bagian-keuangan)
   - [3.11 Bagian SDM](#311-bagian-sdm)
   - [3.12 Pustakawan](#312-pustakawan)
   - [3.13 Orang Tua atau Wali](#313-orang-tua-atau-wali)
4. [Modul Utama Sistem](#4-modul-utama-sistem)
   - [4.1 Modul Pengaturan Institusi](#41-modul-pengaturan-institusi)
   - [4.2 Modul Penerimaan Mahasiswa Baru](#42-modul-penerimaan-mahasiswa-baru)
   - [4.3 Modul Manajemen Mahasiswa](#43-modul-manajemen-mahasiswa)
   - [4.4 Modul Manajemen Dosen](#44-modul-manajemen-dosen)
   - [4.5 Modul Manajemen Pegawai](#45-modul-manajemen-pegawai)
   - [4.6 Modul Kurikulum](#46-modul-kurikulum)
   - [4.7 Modul Mata Kuliah](#47-modul-mata-kuliah)
   - [4.8 Modul Kelas Perkuliahan](#48-modul-kelas-perkuliahan)
   - [4.9 Modul Jadwal Perkuliahan](#49-modul-jadwal-perkuliahan)
   - [4.10 Modul KRS](#410-modul-krs)
   - [4.11 Modul Perkuliahan](#411-modul-perkuliahan)
   - [4.12 Modul Tugas Mahasiswa](#412-modul-tugas-mahasiswa)
   - [4.13 Modul Tugas Pegawai](#413-modul-tugas-pegawai)
   - [4.14 Modul Absensi Mahasiswa](#414-modul-absensi-mahasiswa)
   - [4.15 Modul Absensi Dosen dan Pegawai](#415-modul-absensi-dosen-dan-pegawai)
   - [4.16 Modul Penilaian](#416-modul-penilaian)
   - [4.17 Modul KHS, IP, IPK, dan Transkrip](#417-modul-khs-ip-ipk-dan-transkrip)
   - [4.18 Modul Ujian](#418-modul-ujian)
   - [4.19 Modul Bimbingan Akademik](#419-modul-bimbingan-akademik)
   - [4.20 Modul Skripsi, Tesis, dan Disertasi](#420-modul-skripsi-tesis-dan-disertasi)
   - [4.21 Modul Magang, PKL, dan KKN](#421-modul-magang-pkl-dan-kkn)
   - [4.22 Modul MBKM dan Konversi SKS](#422-modul-mbkm-dan-konversi-sks)
   - [4.23 Modul Keuangan Mahasiswa](#423-modul-keuangan-mahasiswa)
   - [4.24 Modul Beasiswa](#424-modul-beasiswa)
   - [4.25 Modul Surat dan Dokumen](#425-modul-surat-dan-dokumen)
   - [4.26 Modul Perpustakaan](#426-modul-perpustakaan)
   - [4.27 Modul Sarana dan Prasarana](#427-modul-sarana-dan-prasarana)
   - [4.28 Modul Penelitian](#428-modul-penelitian)
   - [4.29 Modul Pengabdian kepada Masyarakat](#429-modul-pengabdian-kepada-masyarakat)
   - [4.30 Modul Organisasi dan Kegiatan Mahasiswa](#430-modul-organisasi-dan-kegiatan-mahasiswa)
   - [4.31 Modul Pelanggaran dan Disiplin](#431-modul-pelanggaran-dan-disiplin)
   - [4.32 Modul Pengaduan dan Layanan Mahasiswa](#432-modul-pengaduan-dan-layanan-mahasiswa)
   - [4.33 Modul Evaluasi Dosen](#433-modul-evaluasi-dosen)
   - [4.34 Modul Kelulusan dan Wisuda](#434-modul-kelulusan-dan-wisuda)
   - [4.35 Modul Alumni](#435-modul-alumni)
   - [4.36 Modul Pengumuman dan Komunikasi](#436-modul-pengumuman-dan-komunikasi)
   - [4.37 Modul Rapat dan Agenda](#437-modul-rapat-dan-agenda)
   - [4.38 Modul Pelaporan dan Dashboard](#438-modul-pelaporan-dan-dashboard)
5. [Dashboard Berdasarkan Role](#5-dashboard-berdasarkan-role)
6. [Sistem Persetujuan](#6-sistem-persetujuan)
7. [Struktur Data Utama](#7-struktur-data-utama)
8. [Aturan Status Fitur](#8-aturan-status-fitur)
9. [Notifikasi Sistem](#9-notifikasi-sistem)
10. [Keamanan Sistem](#10-keamanan-sistem)
11. [Integrasi Eksternal](#11-integrasi-eksternal)
12. [Teknologi yang Direkomendasikan](#12-teknologi-yang-direkomendasikan)
13. [Arsitektur Sistem](#13-arsitektur-sistem)
14. [Tampilan dan Pengalaman Pengguna](#14-tampilan-dan-pengalaman-pengguna)
15. [Fitur Pencarian Global](#15-fitur-pencarian-global)
16. [Sistem Import dan Export](#16-sistem-import-dan-export)
17. [Kebutuhan Nonfungsional](#17-kebutuhan-nonfungsional)
18. [Tahapan Pengembangan](#18-tahapan-pengembangan)
19. [Prioritas Minimum Viable Product](#19-prioritas-minimum-viable-product)
20. [Kriteria Keberhasilan Sistem](#20-kriteria-keberhasilan-sistem)
21. [Modul Tambahan (Hasil Review)](#21-modul-tambahan-hasil-review)
    - [21.1 Modul Akreditasi (BAN-PT/LAM)](#211-modul-akreditasi-ban-ptlam)
    - [21.2 Modul Pelaporan PDDikti/Feeder](#212-modul-pelaporan-pddiktifeeder)
    - [21.3 Modul Kepatuhan Data Pribadi (UU PDP)](#213-modul-kepatuhan-data-pribadi-uu-pdp)
    - [21.4 Modul Keuangan Institusi](#214-modul-keuangan-institusi)
    - [21.5 Modul Kerjasama dan MoU](#215-modul-kerjasama-dan-mou)
    - [21.6 Modul Career Center Mahasiswa Aktif](#216-modul-career-center-mahasiswa-aktif)
    - [21.7 Modul E-Learning Lanjutan](#217-modul-e-learning-lanjutan)
    - [21.8 Modul Urusan Internasional](#218-modul-urusan-internasional)
    - [21.9 Modul Fasilitas Pendukung](#219-modul-fasilitas-pendukung)
    - [21.10 Modul Survei dan Kuesioner Umum](#2110-modul-survei-dan-kuesioner-umum)
    - [21.11 Modul API dan Developer Portal](#2111-modul-api-dan-developer-portal)

---

## 1. Gambaran Umum

Aplikasi Manajemen Akademik Universitas merupakan sistem terintegrasi untuk mengelola seluruh kegiatan akademik dan operasional kampus, mulai dari penerimaan mahasiswa baru, pengelolaan mahasiswa, dosen, pegawai, perkuliahan, tugas, absensi, penilaian, pembayaran, kelulusan, hingga alumni.

Sistem dirancang untuk mendukung:

* Multi-kampus
* Multi-fakultas
* Multi-program studi
* Multi-jenjang pendidikan
* Multi-tahun akademik
* Multi-semester
* Pengaturan hak akses berdasarkan role
* Integrasi dengan aplikasi mobile
* Pelaporan akademik dan akreditasi
* Integrasi dengan layanan eksternal

---

## 2. Tujuan Sistem

Sistem ini bertujuan untuk:

1. Memusatkan seluruh data universitas dalam satu sistem.
2. Mengurangi pekerjaan manual dan penggunaan dokumen terpisah.
3. Mempermudah mahasiswa dalam mengakses layanan akademik.
4. Membantu dosen mengelola perkuliahan dan penilaian.
5. Membantu pegawai menjalankan aktivitas administrasi.
6. Mempermudah pimpinan memantau performa universitas.
7. Menyediakan laporan akademik secara cepat dan akurat.
8. Menjaga keamanan, konsistensi, dan riwayat perubahan data.

---

## 3. Role dan Hak Akses Pengguna

### 3.1 Super Admin

Mengelola **platform**, bukan operasional harian satu universitas — sejak sistem menjadi multi-tenant (lihat [MULTI-TENANT-ARCHITECTURE.md](./MULTI-TENANT-ARCHITECTURE.md)), Super Admin tidak lagi otomatis melihat data bisnis satu universitas manapun (mahasiswa, akademik, dosen, pegawai, keuangan, skripsi, magang/MBKM, perpustakaan, alumni) — wajib pilih universitas dulu lewat Tenant Switcher.

Rancangan fitur lengkap (manajemen aplikasi, hak akses, biaya bulanan per universitas, keamanan, dan hak general lainnya) ada di dokumen terpisah: **[RANCANGAN-SUPER-ADMIN.md](./RANCANGAN-SUPER-ADMIN.md)**. Ringkasan singkat:

* Manajemen universitas, domain, modul & fitur per tenant
* Manajemen role & permission (global template maupun agregat lintas-tenant)
* Langganan & biaya bulanan per universitas
* Keamanan platform (audit log lintas-tenant, sesi, kebijakan keamanan, Support Session berjejak)
* Pengaturan global, master data, mode maintenance, laporan agregat

### 3.2 Rektor dan Wakil Rektor

Fitur:

* Dashboard statistik universitas
* Statistik mahasiswa aktif
* Statistik mahasiswa baru
* Statistik kelulusan
* Performa fakultas dan program studi
* Statistik dosen dan pegawai
* Laporan akademik
* Laporan keuangan
* Persetujuan kebijakan tertentu
* Monitoring akreditasi

### 3.3 Dekan

Fitur:

* Melihat data fakultas
* Monitoring program studi
* Monitoring dosen
* Monitoring mahasiswa
* Persetujuan kegiatan akademik
* Monitoring perkuliahan
* Statistik kelulusan fakultas
* Laporan performa fakultas

### 3.4 Ketua Program Studi

Fitur:

* Mengelola kurikulum
* Mengelola mata kuliah
* Menentukan dosen pengampu
* Memvalidasi jadwal
* Monitoring KRS mahasiswa
* Monitoring nilai
* Menentukan dosen pembimbing akademik
* Menentukan dosen pembimbing skripsi
* Monitoring mahasiswa bermasalah
* Mengelola konversi mata kuliah

### 3.5 Bagian Akademik

Fitur:

* Mengelola data mahasiswa
* Mengelola kalender akademik
* Mengelola kelas perkuliahan
* Mengelola jadwal
* Mengelola KRS dan KHS
* Memvalidasi perubahan data akademik
* Mengelola cuti mahasiswa
* Mengelola mutasi mahasiswa
* Mengelola kelulusan
* Mengelola transkrip
* Mengelola dokumen akademik

### 3.6 Dosen

Fitur:

* Melihat jadwal mengajar
* Melihat daftar mahasiswa
* Membuka pertemuan perkuliahan
* Mengisi jurnal mengajar
* Mengelola absensi
* Membagikan materi
* Membuat tugas dan kuis
* Memeriksa tugas
* Menginput nilai
* Melihat riwayat mengajar
* Melakukan bimbingan akademik
* Melakukan bimbingan skripsi
* Mengajukan penelitian dan pengabdian
* Melihat beban kerja dosen

### 3.7 Dosen Pembimbing Akademik

Fitur tambahan:

* Melihat mahasiswa bimbingan
* Memeriksa rencana studi
* Menyetujui atau menolak KRS
* Memberikan catatan akademik
* Melihat perkembangan IP dan IPK
* Melihat riwayat pelanggaran
* Menjadwalkan konsultasi

### 3.8 Mahasiswa

Fitur:

* Melihat profil mahasiswa
* Mengajukan perubahan data
* Mengisi KRS
* Melihat KHS
* Melihat transkrip sementara
* Melihat jadwal kuliah
* Melihat tugas
* Mengumpulkan tugas
* Mengikuti kuis
* Melihat absensi
* Melihat nilai
* Melakukan presensi
* Mengajukan cuti
* Mengajukan surat
* Mengajukan beasiswa
* Membayar tagihan
* Mengikuti bimbingan akademik
* Mengikuti bimbingan skripsi
* Melihat pengumuman
* Mengisi evaluasi dosen
* Mendaftar wisuda

### 3.9 Pegawai atau Tenaga Kependidikan

Fitur:

* Melihat profil pegawai
* Mengelola absensi kerja
* Mengajukan izin dan cuti
* Melihat tugas pegawai
* Mengelola disposisi pekerjaan
* Mengunggah laporan pekerjaan
* Melihat slip gaji
* Mengajukan reimbursement
* Mengelola dokumen administrasi

### 3.10 Bagian Keuangan

Fitur:

* Membuat komponen biaya
* Membuat tagihan mahasiswa
* Memvalidasi pembayaran
* Mengelola cicilan
* Mengelola beasiswa dan potongan
* Mengelola denda
* Mengelola refund
* Membuat laporan keuangan
* Rekonsiliasi pembayaran
* Integrasi payment gateway

### 3.11 Bagian SDM

Fitur:

* Mengelola data dosen dan pegawai
* Mengelola kontrak kerja
* Mengelola jabatan
* Mengelola kepangkatan
* Mengelola cuti
* Mengelola absensi kerja
* Mengelola payroll
* Mengelola penilaian kinerja
* Mengelola pelatihan pegawai
* Mengelola dokumen kepegawaian

### 3.12 Pustakawan

Fitur:

* Mengelola buku
* Mengelola kategori buku
* Mengelola anggota perpustakaan
* Mengelola peminjaman
* Mengelola pengembalian
* Mengelola denda
* Mengelola jurnal digital
* Mengelola repository karya ilmiah

### 3.13 Orang Tua atau Wali

Role ini bersifat opsional.

Fitur:

* Melihat status akademik mahasiswa
* Melihat jadwal
* Melihat absensi
* Melihat tagihan
* Melihat status pembayaran
* Melihat IP dan IPK
* Menerima pengumuman tertentu

---

## 4. Modul Utama Sistem

### 4.1 Modul Pengaturan Institusi

Mengelola struktur universitas.

Fitur:

* Data universitas
* Data kampus
* Fakultas
* Program studi
* Jenjang pendidikan
* Konsentrasi program studi
* Tahun akademik
* Semester
* Kalender akademik
* Hari libur
* Ruangan
* Gedung
* Logo dan identitas universitas
* Format nomor dokumen
* Pengaturan zona waktu
* Pengaturan bahasa
* Pengaturan skala penilaian

### 4.2 Modul Penerimaan Mahasiswa Baru

Fitur:

* Pembuatan periode penerimaan
* Jalur penerimaan
* Formulir pendaftaran online
* Pilihan program studi
* Upload dokumen pendaftaran
* Pembayaran biaya pendaftaran
* Verifikasi dokumen
* Penjadwalan ujian
* Ujian masuk online
* Penilaian ujian
* Wawancara
* Pengumuman kelulusan
* Daftar ulang
* Pembuatan NIM otomatis
* Konversi calon mahasiswa menjadi mahasiswa aktif
* Statistik pendaftar
* Laporan asal sekolah
* Laporan jalur penerimaan

Status calon mahasiswa:

* Draft
* Terdaftar
* Menunggu pembayaran
* Dokumen belum lengkap
* Dokumen terverifikasi
* Mengikuti seleksi
* Lulus
* Tidak lulus
* Daftar ulang
* Menjadi mahasiswa

### 4.3 Modul Manajemen Mahasiswa

Data yang dikelola:

* NIM
* Nama lengkap
* Foto
* Tempat dan tanggal lahir
* Jenis kelamin
* Nomor identitas
* Alamat
* Email
* Nomor telepon
* Data orang tua
* Data wali
* Asal sekolah
* Program studi
* Angkatan
* Kelas
* Status mahasiswa
* Dosen pembimbing akademik
* Informasi beasiswa
* Dokumen mahasiswa
* Riwayat akademik

Status mahasiswa:

* Calon mahasiswa
* Aktif
* Cuti
* Nonaktif
* Mengundurkan diri
* Pindah
* Drop out
* Lulus
* Meninggal dunia

Fitur:

* Import data mahasiswa
* Export data mahasiswa
* Perubahan status mahasiswa
* Pengajuan perubahan biodata
* Riwayat perubahan data
* Pengelolaan dokumen
* Pengelompokan berdasarkan angkatan
* Pengelompokan berdasarkan kelas
* Penetapan dosen pembimbing
* Catatan akademik
* Catatan pelanggaran
* Riwayat beasiswa
* Riwayat organisasi
* Riwayat prestasi

### 4.4 Modul Manajemen Dosen

Data dosen:

* NIDN atau NIDK
* Nomor pegawai
* Nama
* Gelar depan dan belakang
* Fakultas
* Program studi
* Jabatan akademik
* Pangkat
* Status dosen
* Bidang keahlian
* Pendidikan terakhir
* Sertifikasi
* Dokumen
* Riwayat mengajar
* Riwayat penelitian
* Riwayat pengabdian
* Beban kerja

Fitur:

* Penugasan dosen ke program studi
* Penetapan dosen pengampu
* Penetapan dosen wali
* Penetapan dosen pembimbing
* Pengelolaan jadwal mengajar
* Monitoring beban SKS
* Monitoring kehadiran dosen
* Pengelolaan sertifikasi
* Pengelolaan portofolio dosen
* Penilaian kinerja dosen
* Rekap BKD

### 4.5 Modul Manajemen Pegawai

Data pegawai:

* Nomor induk pegawai
* Nama
* Unit kerja
* Jabatan
* Status kerja
* Jenis kontrak
* Tanggal mulai bekerja
* Atasan langsung
* Data rekening
* Data pajak
* Dokumen pegawai

Fitur:

* Kontrak kerja
* Mutasi pegawai
* Promosi jabatan
* Penilaian kinerja
* Absensi pegawai
* Pengajuan izin
* Pengajuan cuti
* Lembur
* Tugas pegawai
* Surat peringatan
* Pelatihan
* Payroll
* Slip gaji
* Riwayat pekerjaan

### 4.6 Modul Kurikulum

Fitur:

* Pembuatan kurikulum
* Versi kurikulum
* Periode berlakunya kurikulum
* Struktur mata kuliah
* Mata kuliah wajib
* Mata kuliah pilihan
* Prasyarat mata kuliah
* Mata kuliah ekuivalen
* Distribusi mata kuliah per semester
* Total SKS kelulusan
* Kompetensi lulusan
* Capaian pembelajaran
* Rencana Pembelajaran Semester
* Konversi kurikulum lama ke kurikulum baru

### 4.7 Modul Mata Kuliah

Data mata kuliah:

* Kode mata kuliah
* Nama mata kuliah
* Program studi
* Semester rekomendasi
* Jumlah SKS
* SKS teori
* SKS praktik
* Mata kuliah prasyarat
* Jenis mata kuliah
* Metode pembelajaran
* Status aktif

Fitur:

* Import mata kuliah
* Duplikasi mata kuliah
* Penetapan prasyarat
* Penetapan dosen koordinator
* Pengelolaan RPS
* Pengelolaan materi
* Pengelolaan capaian pembelajaran

### 4.8 Modul Kelas Perkuliahan

Fitur:

* Membuka kelas per semester
* Menentukan kapasitas kelas
* Menentukan dosen pengampu
* Menentukan dosen pendamping
* Menentukan ruangan
* Menentukan jadwal
* Membatasi jumlah mahasiswa
* Menggabungkan kelas
* Memisahkan kelas
* Menutup kelas
* Mengelola kelas teori dan praktikum
* Melihat daftar peserta kelas

### 4.9 Modul Jadwal Perkuliahan

Fitur:

* Penyusunan jadwal
* Drag-and-drop jadwal
* Deteksi bentrok dosen
* Deteksi bentrok mahasiswa
* Deteksi bentrok ruangan
* Deteksi kapasitas ruangan
* Jadwal kuliah
* Jadwal praktikum
* Jadwal ujian
* Jadwal pengganti
* Perubahan ruangan
* Perubahan dosen
* Notifikasi perubahan jadwal
* Kalender akademik terintegrasi

### 4.10 Modul KRS

Fitur:

* Pengaturan periode KRS
* Pengisian KRS mahasiswa
* Batas maksimum SKS berdasarkan IP
* Validasi mata kuliah prasyarat
* Deteksi bentrok jadwal
* Deteksi kelas penuh
* Persetujuan dosen wali
* Perubahan KRS
* Pembatalan mata kuliah
* Cetak KRS
* Riwayat KRS
* Penguncian KRS
* KRS otomatis untuk program tertentu

Contoh aturan batas SKS:

* IP kurang dari 2,00: maksimal 18 SKS
* IP 2,00–2,49: maksimal 20 SKS
* IP 2,50–2,99: maksimal 22 SKS
* IP minimal 3,00: maksimal 24 SKS

Ketentuan ini harus dapat dikonfigurasi melalui halaman admin.

### 4.11 Modul Perkuliahan

Fitur:

* Pertemuan perkuliahan
* Jurnal mengajar
* Materi perkuliahan
* Video pembelajaran
* Tautan pertemuan online
* Diskusi kelas
* Pengumuman kelas
* Presensi mahasiswa
* Presensi dosen
* Tugas
* Kuis
* Ujian
* Rekap aktivitas kelas

Setiap pertemuan memiliki:

* Pertemuan ke-
* Tanggal
* Jam mulai
* Jam selesai
* Materi
* Metode pembelajaran
* Lokasi
* Status pertemuan
* Catatan dosen
* Bukti kegiatan

### 4.12 Modul Tugas Mahasiswa

Fitur:

* Membuat tugas individu
* Membuat tugas kelompok
* Menentukan deskripsi tugas
* Menentukan batas waktu
* Menambahkan lampiran
* Menentukan format file
* Menentukan ukuran maksimal file
* Mengatur keterlambatan
* Mengatur pengurangan nilai otomatis
* Mengumpulkan tugas
* Melakukan revisi
* Memberikan komentar
* Memberikan nilai
* Mendeteksi keterlambatan
* Riwayat pengumpulan
* Pemeriksaan kemiripan atau plagiarisme

Status tugas:

* Belum dimulai
* Sedang dikerjakan
* Sudah dikumpulkan
* Terlambat
* Perlu revisi
* Sudah dinilai

### 4.13 Modul Tugas Pegawai

Digunakan untuk mengelola pekerjaan tenaga kependidikan.

Fitur:

* Membuat tugas
* Menentukan PIC
* Menentukan prioritas
* Menentukan deadline
* Menambahkan checklist
* Menambahkan lampiran
* Menentukan reviewer
* Memberikan komentar
* Memantau progres
* Persetujuan hasil pekerjaan
* Riwayat perubahan tugas

Status tugas:

* Belum dimulai
* Dalam proses
* Menunggu pemeriksaan
* Revisi
* Selesai
* Dibatalkan

### 4.14 Modul Absensi Mahasiswa

Metode absensi:

* Absensi manual oleh dosen
* QR Code
* Kode PIN
* GPS
* Geofencing
* NFC
* Pengenalan wajah
* Integrasi mesin absensi

Status kehadiran:

* Hadir
* Terlambat
* Izin
* Sakit
* Alpa
* Dispensasi

Fitur:

* Batas waktu presensi
* Batas lokasi presensi
* Bukti foto
* Upload surat izin
* Persetujuan izin
* Koreksi absensi
* Rekap per mata kuliah
* Rekap per mahasiswa
* Rekap per semester
* Peringatan batas minimum kehadiran

### 4.15 Modul Absensi Dosen dan Pegawai

Fitur:

* Check-in
* Check-out
* GPS
* Geofencing
* Foto presensi
* Shift kerja
* Jadwal kerja
* Toleransi keterlambatan
* Lembur
* Izin
* Cuti
* Perjalanan dinas
* Rekap kehadiran
* Integrasi payroll
* Integrasi mesin fingerprint

### 4.16 Modul Penilaian

Komponen nilai dapat dikonfigurasi, seperti:

* Kehadiran
* Tugas
* Kuis
* Praktikum
* Ujian Tengah Semester
* Ujian Akhir Semester
* Proyek
* Presentasi
* Keaktifan
* Sikap

Fitur:

* Pengaturan bobot nilai
* Input nilai satu per satu
* Input nilai massal
* Import Excel
* Perhitungan nilai otomatis
* Konversi angka ke huruf
* Validasi total bobot
* Finalisasi nilai
* Persetujuan nilai
* Perubahan nilai
* Riwayat perubahan nilai
* Penguncian nilai
* Cetak daftar nilai

Contoh skala nilai:

| Nilai Angka | Nilai Huruf | Bobot |
| ----------- | ----------- | ----: |
| 85–100      | A           |  4,00 |
| 80–84       | A-          |  3,75 |
| 75–79       | B+          |  3,50 |
| 70–74       | B           |  3,00 |
| 65–69       | B-          |  2,75 |
| 60–64       | C+          |  2,50 |
| 55–59       | C           |  2,00 |
| 40–54       | D           |  1,00 |
| 0–39        | E           |  0,00 |

Skala nilai harus dapat disesuaikan oleh administrator.

### 4.17 Modul KHS, IP, IPK, dan Transkrip

Fitur:

* Perhitungan IP semester
* Perhitungan IPK
* KHS mahasiswa
* Transkrip sementara
* Transkrip final
* Riwayat nilai
* Pengulangan mata kuliah
* Pengambilan nilai terbaik
* Konversi nilai
* Transfer kredit
* Cetak dokumen
* QR Code validasi dokumen
* Tanda tangan elektronik

### 4.18 Modul Ujian

Fitur:

* Jadwal UTS
* Jadwal UAS
* Penentuan ruangan
* Penentuan pengawas
* Kartu ujian
* Validasi syarat mengikuti ujian
* Ujian berbasis komputer
* Bank soal
* Soal pilihan ganda
* Soal esai
* Randomisasi soal
* Batas waktu ujian
* Penilaian otomatis
* Berita acara ujian
* Rekap kehadiran ujian
* Penanganan ujian susulan

**Status implementasi:** konfigurasi ujian pilihan ganda lanjutan (question
pool, jumlah soal per peserta, pemilihan soal acak/manual/semua, pengacakan
urutan soal & opsi jawaban, stabilitas percobaan per peserta) sudah
diimplementasikan di backend (`app/Modules/Academic`, model `Exam`/
`ExamQuestion`/`ExamQuestionOption`/`ExamAttempt`) dan sisi konfigurasi
dosen/admin di Flutter (`mobile/lib/features/exam`). Layar pengerjaan ujian
sungguhan untuk mahasiswa belum dibangun. Detail lengkap: lihat
[EXAM_RANDOMIZATION.md](EXAM_RANDOMIZATION.md) dan [EXAM_MODULE.md](EXAM_MODULE.md).

### 4.19 Modul Bimbingan Akademik

Fitur:

* Penetapan dosen wali
* Daftar mahasiswa bimbingan
* Jadwal konsultasi
* Catatan bimbingan
* Persetujuan KRS
* Monitoring nilai
* Monitoring absensi
* Peringatan mahasiswa bermasalah
* Rekomendasi akademik
* Riwayat konsultasi

### 4.20 Modul Skripsi, Tesis, dan Disertasi

Fitur:

* Pengajuan judul
* Validasi persyaratan
* Penentuan pembimbing
* Penentuan penguji
* Seminar proposal
* Bimbingan
* Catatan revisi
* Upload dokumen
* Pemeriksaan plagiarisme
* Pendaftaran sidang
* Penjadwalan sidang
* Penilaian sidang
* Revisi pascasidang
* Persetujuan dokumen final
* Repository karya ilmiah

Status:

* Pengajuan judul
* Judul disetujui
* Bimbingan
* Seminar proposal
* Penelitian
* Pendaftaran sidang
* Sidang
* Revisi
* Selesai

### 4.21 Modul Magang, PKL, dan KKN

Fitur:

* Pendaftaran
* Pemilihan lokasi
* Penempatan mahasiswa
* Dosen pembimbing
* Pembimbing lapangan
* Logbook kegiatan
* Absensi
* Upload laporan
* Monitoring
* Penilaian
* Sertifikat
* Konversi nilai
* Konversi SKS

### 4.22 Modul MBKM dan Konversi SKS

Fitur:

* Program pertukaran mahasiswa
* Magang
* Studi independen
* Kampus mengajar
* Penelitian
* Proyek kemanusiaan
* Kewirausahaan
* Pengajuan program
* Persetujuan program studi
* Konversi kegiatan ke mata kuliah
* Konversi nilai
* Monitoring kegiatan
* Upload laporan

### 4.23 Modul Keuangan Mahasiswa

Fitur:

* Komponen biaya
* Uang kuliah
* Uang gedung
* Biaya praktikum
* Biaya ujian
* Biaya wisuda
* Biaya administrasi
* Tagihan otomatis
* Tagihan manual
* Cicilan
* Denda
* Potongan
* Beasiswa
* Pembayaran
* Verifikasi pembayaran
* Bukti pembayaran
* Refund
* Rekonsiliasi
* Laporan tunggakan

Metode pembayaran:

* Virtual account
* Transfer bank
* QRIS
* Payment gateway
* Pembayaran loket
* Kartu kredit atau debit

Status pembayaran:

* Belum dibayar
* Dibayar sebagian
* Lunas
* Menunggu verifikasi
* Gagal
* Kedaluwarsa
* Dikembalikan

### 4.24 Modul Beasiswa

Fitur:

* Jenis beasiswa
* Periode pendaftaran
* Persyaratan
* Pendaftaran mahasiswa
* Upload dokumen
* Verifikasi
* Seleksi
* Penilaian
* Persetujuan
* Penetapan penerima
* Besaran bantuan
* Masa berlaku
* Monitoring penerima
* Laporan beasiswa

### 4.25 Modul Surat dan Dokumen

Jenis pengajuan:

* Surat aktif kuliah
* Surat keterangan mahasiswa
* Surat rekomendasi
* Surat izin penelitian
* Surat pengantar magang
* Surat cuti
* Surat pindah
* Surat bebas administrasi
* Surat bebas perpustakaan
* Legalisasi dokumen
* Permohonan transkrip
* Permohonan ijazah

Fitur:

* Form pengajuan
* Nomor surat otomatis
* Workflow persetujuan
* Tanda tangan elektronik
* QR Code validasi
* Template dokumen
* Download PDF
* Riwayat dokumen
* Pelacakan status pengajuan

### 4.26 Modul Perpustakaan

Fitur:

* Katalog buku
* Buku fisik
* E-book
* Jurnal
* Repository
* Penulis
* Penerbit
* Rak buku
* Stok buku
* Barcode
* Peminjaman
* Pengembalian
* Perpanjangan
* Reservasi
* Denda
* Buku hilang
* Riwayat peminjaman
* Kartu anggota digital
* Integrasi status bebas perpustakaan

### 4.27 Modul Sarana dan Prasarana

Fitur:

* Data gedung
* Data ruangan
* Laboratorium
* Kendaraan
* Peralatan
* Inventaris
* Kondisi aset
* Lokasi aset
* Peminjaman ruangan
* Peminjaman alat
* Jadwal pemakaian
* Pemeliharaan
* Pelaporan kerusakan
* Penghapusan aset
* Riwayat aset

### 4.28 Modul Penelitian

Fitur:

* Pengajuan proposal penelitian
* Tim penelitian
* Anggaran
* Persetujuan
* Pendanaan
* Monitoring progres
* Laporan kemajuan
* Laporan akhir
* Publikasi
* Hak kekayaan intelektual
* Luaran penelitian
* Integrasi profil dosen

### 4.29 Modul Pengabdian kepada Masyarakat

Fitur:

* Pengajuan kegiatan
* Penentuan tim
* Lokasi kegiatan
* Anggaran
* Persetujuan
* Monitoring
* Dokumentasi
* Laporan
* Luaran kegiatan
* Penilaian kegiatan

### 4.30 Modul Organisasi dan Kegiatan Mahasiswa

Fitur:

* Data organisasi mahasiswa
* Struktur kepengurusan
* Program kerja
* Proposal kegiatan
* Pengajuan dana
* Persetujuan kegiatan
* Pelaksanaan kegiatan
* Absensi peserta
* Laporan pertanggungjawaban
* Prestasi mahasiswa
* Poin kegiatan mahasiswa
* Sertifikat kegiatan

### 4.31 Modul Pelanggaran dan Disiplin

Fitur:

* Jenis pelanggaran
* Tingkat pelanggaran
* Poin pelanggaran
* Bukti pelanggaran
* Pemeriksaan
* Sanksi
* Surat peringatan
* Banding
* Riwayat pelanggaran
* Notifikasi kepada mahasiswa
* Notifikasi kepada wali

### 4.32 Modul Pengaduan dan Layanan Mahasiswa

Fitur:

* Tiket pengaduan
* Kategori pengaduan
* Prioritas
* Unit tujuan
* Penanggung jawab
* Status penanganan
* Komentar
* Lampiran
* SLA layanan
* Survei kepuasan
* Riwayat pengaduan

Status:

* Diajukan
* Diverifikasi
* Diproses
* Menunggu mahasiswa
* Selesai
* Ditolak
* Ditutup

### 4.33 Modul Evaluasi Dosen

Fitur:

* Kuesioner evaluasi
* Periode evaluasi
* Penilaian anonim
* Evaluasi per mata kuliah
* Evaluasi dosen
* Rekap hasil
* Grafik evaluasi
* Catatan mahasiswa
* Rekomendasi peningkatan
* Pembatasan akses hasil evaluasi

### 4.34 Modul Kelulusan dan Wisuda

Fitur:

* Pemeriksaan syarat kelulusan
* Pemeriksaan total SKS
* Pemeriksaan IPK
* Pemeriksaan skripsi
* Pemeriksaan administrasi
* Pemeriksaan perpustakaan
* Yudisium
* Penetapan kelulusan
* Nomor ijazah
* Nomor transkrip
* Pendaftaran wisuda
* Pembayaran wisuda
* Ukuran toga
* Data pendamping
* Pembagian sesi wisuda
* Cetak ijazah
* Cetak transkrip
* SKPI

### 4.35 Modul Alumni

Fitur:

* Konversi mahasiswa menjadi alumni
* Profil alumni
* Tracer study
* Riwayat pekerjaan
* Data perusahaan
* Masa tunggu kerja
* Kesesuaian pekerjaan
* Survey pengguna lulusan
* Lowongan kerja
* Event alumni
* Donasi alumni
* Verifikasi data alumni

### 4.36 Modul Pengumuman dan Komunikasi

Fitur:

* Pengumuman universitas
* Pengumuman fakultas
* Pengumuman program studi
* Pengumuman kelas
* Target berdasarkan role
* Target berdasarkan angkatan
* Target berdasarkan kelas
* Notifikasi aplikasi
* Email
* WhatsApp
* SMS
* Push notification
* Jadwal publikasi
* Lampiran
* Status dibaca

### 4.37 Modul Rapat dan Agenda

Fitur:

* Jadwal rapat
* Undangan peserta
* Agenda rapat
* Presensi rapat
* Notulen
* Keputusan rapat
* Tugas hasil rapat
* Lampiran
* Pengingat
* Persetujuan notulen

### 4.38 Modul Pelaporan dan Dashboard

Laporan mahasiswa:

* Mahasiswa aktif
* Mahasiswa berdasarkan angkatan
* Mahasiswa berdasarkan program studi
* Mahasiswa cuti
* Mahasiswa nonaktif
* Mahasiswa drop out
* Mahasiswa lulus
* Mahasiswa penerima beasiswa

Laporan akademik:

* KRS
* KHS
* IP dan IPK
* Nilai
* Absensi
* Kelulusan
* Masa studi
* Rasio dosen dan mahasiswa
* Beban mengajar dosen

Laporan keuangan:

* Tagihan
* Pembayaran
* Tunggakan
* Beasiswa
* Potongan
* Pendapatan
* Rekonsiliasi

Laporan SDM:

* Kehadiran
* Cuti
* Beban kerja
* Kinerja
* Kontrak
* Payroll

Dashboard harus menyediakan:

* Grafik
* Filter tanggal
* Filter fakultas
* Filter program studi
* Export Excel
* Export PDF
* Drill-down data
* Perbandingan antartahun

---

## 5. Dashboard Berdasarkan Role

### Dashboard Mahasiswa

Menampilkan:

* Jadwal hari ini
* Tugas yang mendekati deadline
* Status absensi
* IP dan IPK
* Tagihan aktif
* Pengumuman
* Status KRS
* Status pengajuan surat
* Jadwal bimbingan
* Kalender akademik

### Dashboard Dosen

Menampilkan:

* Jadwal mengajar
* Pertemuan hari ini
* Tugas yang perlu diperiksa
* Nilai yang belum lengkap
* Kelas yang diampu
* Mahasiswa bimbingan
* Jadwal sidang
* Aktivitas penelitian
* Pengumuman

### Dashboard Pegawai

Menampilkan:

* Kehadiran hari ini
* Tugas aktif
* Pengajuan izin
* Jadwal rapat
* Pengumuman
* Target pekerjaan
* Dokumen yang menunggu persetujuan

### Dashboard Pimpinan

Menampilkan:

* Jumlah mahasiswa aktif
* Jumlah mahasiswa baru
* Jumlah lulusan
* Rata-rata IPK
* Persentase kehadiran
* Persentase kelulusan
* Tunggakan mahasiswa
* Performa program studi
* Performa dosen
* Status akreditasi
* Grafik pertumbuhan mahasiswa

---

## 6. Sistem Persetujuan

Sistem perlu memiliki approval workflow yang dinamis.

Contoh:

### Pengajuan Cuti Mahasiswa

1. Mahasiswa mengajukan cuti.
2. Dosen wali memberikan rekomendasi.
3. Program studi melakukan verifikasi.
4. Bagian akademik melakukan validasi.
5. Bagian keuangan memeriksa kewajiban pembayaran.
6. Dekan memberikan persetujuan.
7. Status mahasiswa berubah menjadi cuti.

### Pengajuan Perubahan Nilai

1. Dosen mengajukan perubahan.
2. Ketua program studi memeriksa.
3. Bagian akademik menyetujui.
4. Nilai diperbarui.
5. Riwayat nilai lama tetap disimpan.

Setiap workflow harus dapat dikonfigurasi berdasarkan:

* Jenis pengajuan
* Fakultas
* Program studi
* Nilai transaksi
* Jabatan pemberi persetujuan
* Urutan persetujuan

---

## 7. Struktur Data Utama

Berikut kelompok tabel utama yang direkomendasikan.

### Data Pengguna

* users
* roles
* permissions
* role_permissions
* user_roles
* user_sessions
* login_histories
* audit_logs

### Struktur Institusi

* universities
* campuses
* faculties
* study_programs
* education_levels
* academic_years
* semesters
* academic_calendars
* buildings
* rooms

### Mahasiswa

* students
* student_parents
* student_guardians
* student_addresses
* student_documents
* student_status_histories
* student_achievements
* student_violations
* student_organizations

### Dosen dan Pegawai

* lecturers
* lecturer_educations
* lecturer_certifications
* lecturer_positions
* employees
* employee_positions
* employee_contracts
* employee_documents
* employee_performance_reviews

### Kurikulum

* curriculums
* curriculum_courses
* courses
* course_prerequisites
* course_equivalences
* learning_outcomes
* lesson_plans

### Perkuliahan

* course_classes
* class_lecturers
* class_students
* schedules
* class_meetings
* teaching_journals
* learning_materials

### KRS dan Nilai

* study_plans
* study_plan_details
* grade_components
* student_grades
* grade_histories
* study_results
* transcripts

### Tugas dan Ujian

* assignments
* assignment_groups
* assignment_submissions
* assignment_reviews
* exams
* exam_questions
* exam_answers
* exam_results

### Absensi

* attendance_sessions
* student_attendances
* lecturer_attendances
* employee_attendances
* attendance_corrections
* leave_requests

### Keuangan

* fee_components
* student_bills
* student_bill_details
* payments
* payment_allocations
* discounts
* scholarships
* refunds
* payment_reconciliations

### Skripsi

* final_projects
* final_project_supervisors
* supervision_sessions
* seminar_schedules
* defense_schedules
* defense_examiners
* final_project_scores
* final_project_documents

### Dokumen

* document_templates
* document_requests
* document_approvals
* generated_documents
* digital_signatures

### Komunikasi

* announcements
* notifications
* notification_recipients
* messages
* discussions
* comments

### Akreditasi dan Kepatuhan

* accreditation_cycles
* accreditation_criteria
* accreditation_evidences
* accreditation_documents
* pddikti_sync_logs
* pddikti_field_mappings
* consent_records
* data_subject_requests
* data_retention_policies

### Keuangan Institusi

* annual_budgets
* budget_items
* procurements
* general_ledger_entries
* institution_financial_reports

### Kerjasama dan Karier

* partnerships
* partnership_documents
* job_postings
* job_applications
* career_profiles

### Fasilitas dan Survei (Opsional)

* dormitory_rooms
* dormitory_occupants
* health_clinic_visits
* survey_forms
* survey_questions
* survey_responses

### Pendukung

* approval_workflows
* approval_steps
* approval_requests
* approval_histories
* file_uploads
* system_settings
* activity_logs
* api_keys
* api_webhooks
* api_request_logs

---

## 8. Aturan Status Fitur

Setiap modul sebaiknya memiliki pengaturan status.

Contoh:

* `is_active`
* `is_visible`
* `is_required`
* `is_editable`
* `available_from`
* `available_until`

Jika suatu fitur dinonaktifkan:

* Menu tidak ditampilkan.
* Endpoint tidak dapat diakses.
* Tombol terkait tidak ditampilkan.
* Sistem tidak menyisakan ruang kosong.
* Notifikasi terkait tidak dikirim.
* Data lama tetap tersimpan.
* Aktivitas baru tidak dapat dibuat.

---

## 9. Notifikasi Sistem

Jenis notifikasi:

* Tugas baru
* Deadline tugas
* Perubahan jadwal
* KRS disetujui
* KRS ditolak
* Nilai dipublikasikan
* Tagihan baru
* Pembayaran berhasil
* Pembayaran jatuh tempo
* Absensi tidak memenuhi syarat
* Pengajuan surat selesai
* Jadwal bimbingan
* Jadwal sidang
* Pengumuman akademik
* Kontrak pegawai akan berakhir

Channel notifikasi:

* In-app notification
* Push notification
* Email
* WhatsApp
* SMS

Pengguna dapat mengatur preferensi notifikasi masing-masing.

---

## 10. Keamanan Sistem

Sistem harus memiliki:

* Role-Based Access Control
* Permission per menu dan aksi
* Multi-factor authentication
* Verifikasi email
* Pembatasan percobaan login
* Session management
* Audit log
* Log perubahan data
* Enkripsi data sensitif
* Backup otomatis
* Pemulihan data
* Pembatasan akses berdasarkan IP
* Deteksi perangkat baru
* Tanda tangan digital
* QR Code verifikasi dokumen
* Antivirus untuk file upload
* Validasi tipe file
* Pembatasan ukuran file

Setiap perubahan data penting harus menyimpan:

* Pengguna yang melakukan perubahan
* Waktu perubahan
* Data sebelum perubahan
* Data setelah perubahan
* Alasan perubahan
* Alamat IP
* Perangkat pengguna

---

## 11. Integrasi Eksternal

Sistem dapat diintegrasikan dengan:

* Payment gateway
* Virtual account bank
* WhatsApp API
* Email server
* SMS gateway
* Google Calendar
* Zoom
* Google Meet
* Microsoft Teams
* Sistem perpustakaan
* Mesin fingerprint
* Face recognition
* Sistem pelaporan pendidikan nasional
* Sistem akreditasi
* PDDikti atau Feeder
* Single Sign-On
* Google Workspace
* Microsoft 365
* Sistem plagiarism checker
* Sistem tanda tangan elektronik

---

## 12. Teknologi yang Direkomendasikan

### Backend

* Laravel
* REST API
* Laravel Sanctum atau OAuth 2.0
* Laravel Queue
* Laravel Scheduler
* WebSocket untuk notifikasi real-time

### Frontend Web

* React.js
* TypeScript
* Tailwind CSS
* TanStack Query
* React Hook Form
* Zod Validation

### Aplikasi Mobile

Pilihan:

* Flutter
* React Native

### Database

Pilihan utama:

* PostgreSQL

Alternatif:

* MySQL

### Pendukung

* Redis untuk cache dan queue
* MinIO atau Amazon S3 untuk penyimpanan file
* Docker untuk deployment
* Nginx sebagai web server
* GitHub Actions atau GitLab CI untuk CI/CD
* Sentry untuk error monitoring
* Grafana untuk monitoring sistem

---

## 13. Arsitektur Sistem

Disarankan menggunakan modular monolith pada tahap awal.

Contoh pembagian modul:

```text
Modules/
├── Authentication
├── Institution
├── Admission
├── Student
├── Lecturer
├── Employee
├── Curriculum
├── Course
├── Schedule
├── StudyPlan
├── Attendance
├── Assignment
├── Examination
├── Grading
├── Finance
├── Scholarship
├── FinalProject
├── Internship
├── Library
├── HumanResource
├── Research
├── CommunityService
├── Document
├── Notification
├── Reporting
└── SystemSetting
```

Setiap modul memiliki:

* Controller
* Service
* Repository
* Model
* Request validation
* Policy
* Event
* Listener
* Job
* Notification
* Test

Arsitektur ini lebih mudah dikembangkan dibandingkan langsung menggunakan microservices.

Microservices dapat dipertimbangkan ketika:

* Jumlah pengguna sangat besar.
* Beban transaksi tinggi.
* Sistem digunakan oleh banyak universitas.
* Tim pengembangan sudah terbagi berdasarkan layanan.
* Modul pembayaran atau notifikasi membutuhkan skala terpisah.

---

## 14. Tampilan dan Pengalaman Pengguna

Desain aplikasi harus:

* Modern
* Minimalis
* Responsif
* Mudah digunakan
* Mendukung desktop, tablet, dan mobile
* Mendukung dark mode
* Memiliki navigasi konsisten
* Memiliki pencarian global
* Memiliki filter data
* Memiliki pagination
* Mendukung import dan export
* Memiliki empty state
* Memiliki loading state
* Memiliki error state
* Memiliki konfirmasi sebelum penghapusan

Komponen utama:

* Sidebar berdasarkan role
* Top navigation
* Dashboard cards
* Data table
* Calendar
* Kanban tugas
* Timeline aktivitas
* Grafik
* Form bertahap
* Modal persetujuan
* Notification center

---

## 15. Fitur Pencarian Global

Pencarian global dapat menemukan:

* Mahasiswa
* Dosen
* Pegawai
* Mata kuliah
* Kelas
* Dokumen
* Pengumuman
* Tagihan
* Tugas
* Ruangan
* Buku perpustakaan

Hasil pencarian tetap dibatasi berdasarkan hak akses pengguna.

---

## 16. Sistem Import dan Export

Import mendukung:

* Excel
* CSV
* Template resmi sistem
* Preview sebelum proses
* Validasi data
* Laporan data gagal
* Proses background untuk data besar

Export mendukung:

* Excel
* CSV
* PDF
* Print

Setiap export data sensitif harus dicatat pada audit log.

---

## 17. Kebutuhan Nonfungsional

### Performa

* Waktu respons halaman maksimal 2–3 detik pada kondisi normal.
* Menggunakan pagination untuk data besar.
* Menggunakan cache untuk data master.
* Menggunakan queue untuk notifikasi dan laporan besar.
* Menggunakan indeks database.

### Skalabilitas

* Mendukung puluhan ribu mahasiswa.
* Mendukung beberapa kampus.
* Mendukung pemisahan penyimpanan file.
* Mendukung horizontal scaling.

### Ketersediaan

* Backup database harian.
* Backup file terjadwal.
* Monitoring server.
* Error logging.
* Disaster recovery procedure.

### Aksesibilitas

* Ukuran teks dapat dibaca.
* Kontras warna baik.
* Navigasi keyboard.
* Label form jelas.
* Pesan error mudah dipahami.

---

## 18. Tahapan Pengembangan

### Tahap 1: Fondasi Sistem

* Autentikasi
* Role dan permission
* Struktur universitas
* Tahun akademik
* Semester
* Pengaturan aplikasi
* Audit log

### Tahap 2: Data Utama

* Mahasiswa
* Dosen
* Pegawai
* Fakultas
* Program studi
* Kurikulum
* Mata kuliah
* Ruangan

### Tahap 3: Operasional Akademik

* Kelas
* Jadwal
* KRS
* Perkuliahan
* Absensi
* Tugas
* Ujian
* Nilai
* KHS
* Transkrip

### Tahap 4: Layanan Mahasiswa

* Pengajuan surat
* Cuti
* Bimbingan
* Beasiswa
* Pengaduan
* Pengumuman
* Notifikasi

### Tahap 5: Keuangan dan SDM

* Tagihan
* Pembayaran
* Beasiswa
* Absensi pegawai
* Cuti pegawai
* Payroll
* Penilaian kinerja

### Tahap 6: Modul Lanjutan

* Skripsi
* Magang
* KKN
* MBKM
* Penelitian
* Pengabdian
* Perpustakaan
* Alumni
* Wisuda

### Tahap 7: Integrasi dan Pelaporan

* Payment gateway
* WhatsApp
* PDDikti
* Fingerprint
* Tanda tangan elektronik
* Dashboard pimpinan
* Laporan akreditasi

---

## 19. Prioritas Minimum Viable Product

Versi pertama sebaiknya memuat:

1. Login dan hak akses.
2. Data fakultas dan program studi.
3. Data mahasiswa.
4. Data dosen.
5. Data mata kuliah.
6. Kurikulum.
7. Kelas perkuliahan.
8. Jadwal.
9. KRS.
10. Absensi mahasiswa.
11. Tugas.
12. Input nilai.
13. KHS.
14. Transkrip sementara.
15. Pengumuman.
16. Notifikasi.
17. Laporan akademik dasar.
18. Audit log.

Modul keuangan, SDM, perpustakaan, penelitian, dan alumni dapat dikembangkan pada tahap berikutnya.

---

## 20. Kriteria Keberhasilan Sistem

Sistem dianggap berhasil apabila:

* Mahasiswa dapat mengisi KRS secara mandiri.
* Sistem dapat mendeteksi bentrok jadwal.
* Dosen dapat mengelola perkuliahan dan nilai.
* Absensi dapat direkap otomatis.
* IP dan IPK dihitung secara akurat.
* Dokumen akademik dapat diterbitkan secara digital.
* Pimpinan dapat melihat statistik secara real-time.
* Setiap perubahan data dapat dilacak.
* Pengguna hanya dapat mengakses fitur sesuai hak aksesnya.
* Sistem tetap stabil ketika digunakan secara bersamaan.
* Data dapat diintegrasikan dengan sistem eksternal.
* Seluruh laporan utama dapat diekspor ke Excel dan PDF.

---

## 21. Modul Tambahan (Hasil Review)

Bagian ini adalah hasil review atas rancangan awal (Bagian 1–20). Setiap modul diberi label prioritas:

* **Kritis** — kebutuhan yang biasanya bersifat wajib (regulasi atau operasional inti) untuk universitas di Indonesia, sebaiknya masuk roadmap lebih awal daripada modul lanjutan di Bagian 18.
* **Penting** — akan terasa kurang begitu sistem dipakai secara nyata dalam skala penuh, tapi tidak menghalangi rilis awal.
* **Opsional** — tergantung skala, jenis kampus, dan fasilitas yang benar-benar dimiliki institusi.

### 21.1 Modul Akreditasi (BAN-PT/LAM)

**Prioritas: Kritis**

Fitur:

* Data instrumen akreditasi (9 kriteria BAN-PT / LAM)
* Pemetaan bukti dukung per kriteria
* Penyusunan Laporan Evaluasi Diri (LED)
* Penyusunan Laporan Kinerja Program Studi (LKPS)
* Repository dokumen akreditasi
* Siklus reakreditasi dan pengingat jatuh tempo
* Status akreditasi per program studi dan institusi
* Riwayat hasil akreditasi
* Tim penyusun borang per program studi
* Ekspor borang ke format resmi BAN-PT/LAM
* Dashboard kesiapan akreditasi

Modul ini menarik data dari hampir semua modul lain (dosen, mahasiswa, kurikulum, penelitian, pengabdian) sebagai bukti dukung, sehingga sebaiknya dibangun setelah data-data itu tersedia.

### 21.2 Modul Pelaporan PDDikti/Feeder

**Prioritas: Kritis**

Fitur:

* Mapping field data lokal ke skema PDDikti/Feeder
* Sinkronisasi data mahasiswa, dosen, mata kuliah, dan nilai per semester
* Validasi data sebelum pengiriman
* Log pengiriman dan status sinkronisasi
* Deteksi dan penanganan data gagal sync
* Riwayat pelaporan per semester
* Rekonsiliasi data lokal dengan data PDDikti
* Notifikasi kegagalan sinkronisasi
* Penjadwalan sinkronisasi otomatis

Pada Bagian 11, PDDikti/Feeder hanya disebut sebagai satu poin integrasi eksternal. Mengingat kompleksitas mapping dan wajibnya pelaporan setiap semester, modul ini layak berdiri sendiri, bukan sekadar checklist integrasi.

### 21.3 Modul Kepatuhan Data Pribadi (UU PDP)

**Prioritas: Kritis**

Fitur:

* Pencatatan persetujuan (consent) pengumpulan data pribadi
* Klasifikasi data pribadi umum dan spesifik
* Permintaan akses data oleh subjek data
* Permintaan koreksi data pribadi
* Permintaan penghapusan data pribadi (dengan batasan kewajiban arsip akademik)
* Kebijakan retensi data per jenis data
* Pencatatan tujuan pengolahan data
* Log akses data sensitif
* Pencatatan dan pelaporan insiden kebocoran data
* Dokumen kebijakan privasi yang dapat diperbarui dan dipublikasikan

Bagian 10 (Keamanan Sistem) sudah mencakup sisi teknis (enkripsi, audit log, RBAC), tapi belum mencakup kewajiban legal terkait consent dan hak subjek data sesuai UU Pelindungan Data Pribadi.

### 21.4 Modul Keuangan Institusi

**Prioritas: Penting**

Fitur:

* Rencana Kerja dan Anggaran Tahunan (RKAT)
* Anggaran per unit dan program studi
* Pengadaan barang dan jasa (procurement)
* Buku besar (general ledger)
* Laporan keuangan institusi
* Laporan ke yayasan atau kementerian
* Integrasi dengan Modul 4.23 Keuangan Mahasiswa
* Integrasi dengan Modul 4.27 Sarana dan Prasarana
* Approval anggaran berjenjang

Modul ini berbeda dari Modul 4.23 Keuangan Mahasiswa, yang hanya mengurus tagihan dan pembayaran mahasiswa, bukan keuangan institusi secara keseluruhan.

### 21.5 Modul Kerjasama dan MoU

**Prioritas: Penting**

Fitur:

* Data mitra (industri, perguruan tinggi lain, pemerintah)
* Jenis kerjasama
* Dokumen MoU dan MoA
* Masa berlaku dan pengingat perpanjangan
* Program yang terkait (magang, MBKM, penelitian bersama)
* Riwayat kerjasama
* Approval kerjasama baru
* Evaluasi kerjasama

### 21.6 Modul Career Center Mahasiswa Aktif

**Prioritas: Penting**

Fitur:

* Profil karier dan CV mahasiswa
* Lowongan magang dan kerja untuk mahasiswa aktif
* Matching berdasarkan program studi dan keahlian
* Pendaftaran ke lowongan
* Jadwal kunjungan industri dan job fair
* Pelatihan kesiapan kerja
* Riwayat pendaftaran lowongan
* Integrasi dengan Modul 4.21 Magang, PKL, dan KKN dan Modul 4.35 Alumni

Modul 4.35 Alumni sudah punya "lowongan kerja", tapi itu untuk lulusan. Mahasiswa aktif punya siklus kebutuhan karier yang berbeda (magang, kesiapan kerja) dan sebaiknya tidak dicampur dengan data alumni.

### 21.7 Modul E-Learning Lanjutan

**Prioritas: Opsional**

Fitur:

* Progres belajar per Capaian Pembelajaran Mata Kuliah (CPMK)
* Bank soal terhubung ke CPMK dan taksonomi kognitif
* Kuis interaktif dan gamifikasi
* Sertifikat penyelesaian modul
* Rekomendasi materi berdasarkan progres
* Forum diskusi terstruktur per topik
* Laporan pencapaian CPMK per mahasiswa dan per kelas

Modul 4.11 Perkuliahan sudah mencakup materi, video, dan diskusi dasar. Fitur di atas adalah pendalaman untuk kampus yang ingin LMS setara Moodle/Google Classroom.

### 21.8 Modul Urusan Internasional

**Prioritas: Opsional**

Fitur:

* Data mahasiswa asing
* Data dosen asing
* Dokumen visa dan izin tinggal (KITAS)
* Program mobilitas mahasiswa masuk dan keluar (inbound/outbound)
* Kemitraan universitas internasional
* Dukungan multi-bahasa untuk dokumen dan pengumuman
* Layanan pendampingan mahasiswa asing

Relevan hanya untuk universitas yang benar-benar punya program internasional atau mahasiswa/dosen asing.

### 21.9 Modul Fasilitas Pendukung

**Prioritas: Opsional**

Fitur:

* Manajemen asrama atau rumah susun mahasiswa
* Klinik kesehatan kampus
* Asuransi kesehatan mahasiswa
* Transportasi atau shuttle kampus
* Penjadwalan penggunaan fasilitas pendukung
* Integrasi dengan Modul 4.27 Sarana dan Prasarana

Relevan hanya jika kampus benar-benar memiliki fasilitas tersebut.

### 21.10 Modul Survei dan Kuesioner Umum

**Prioritas: Opsional**

Fitur:

* Pembuatan survei atau kuesioner generik
* Bank pertanyaan
* Target responden berdasarkan role, angkatan, atau unit
* Survei anonim atau teridentifikasi
* Rekap dan visualisasi hasil
* Ekspor hasil survei
* Dapat digunakan lintas kebutuhan (kepuasan layanan, evaluasi fasilitas, evaluasi kegiatan)

Modul 4.33 Evaluasi Dosen sebaiknya dibangun di atas mesin survei generik ini agar tidak ada duplikasi logika kuesioner.

### 21.11 Modul API dan Developer Portal

**Prioritas: Opsional**

Fitur:

* Manajemen API key
* Manajemen webhook
* Rate limiting per klien
* Dokumentasi API
* Log pemanggilan API
* Sandbox untuk pengujian integrasi

Dibutuhkan terutama untuk mendukung aplikasi mobile dan integrasi pihak ketiga yang disebut pada Bagian 11.
