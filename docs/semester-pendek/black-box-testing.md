# Semester Pendek — Daftar Uji Black Box (Frontend)

> Bagian dari [paket rancangan Semester Pendek](./README.md).
> **Dibuat:** 2026-10-08 · **Basis kode:** `main` @ `7260310` (R-02)
>
> Semua pengujian di dokumen ini dilakukan **dari halaman aplikasi web** (`frontend/`), sebagai pengguna biasa: login, klik menu, isi form, tekan tombol, lalu periksa apa yang tampil di layar. Penguji **tidak** memakai Postman/API, DevTools, atau database.
>
> - Strategi tes otomatis (Pest) dan kriteria penerimaan AC-01…AC-22 ada di [pengujian-dan-penerimaan.md](./pengujian-dan-penerimaan.md). Kode AC dicantumkan di kolom Skenario bila kasusnya membuktikan AC tersebut.
> - Alur, nama status, dan aturan mengikuti keputusan rekonsiliasi di [tahapan-pengembangan.md §1](./tahapan-pengembangan.md#1-rekonsiliasi-rancangan-vs-kode-saat-ini): KRS SP memakai halaman KRS portal yang sudah ada (dengan pilihan periode), persetujuan oleh dosen wali atau otomatis, satu dosen pengampu per kelas.
> - Halaman yang **belum dibangun** memakai nama menu/tombol dari rancangan [frontend-dan-mobile.md](./frontend-dan-mobile.md). Bila label final berbeda, perbarui dokumen ini saat halamannya selesai.
> - Aplikasi `/one` (Civitas One redesign) **di luar cakupan**: masih mockup dengan data tiruan, belum tersambung ke backend.

## Daftar Isi

- [Cara Membaca](#cara-membaca)
- [Akun Uji](#akun-uji)
- [Persiapan Data](#persiapan-data)
- [Temuan Awal: Menu & Halaman yang Belum Ada](#temuan-awal-menu--halaman-yang-belum-ada)
1. [Navigasi & Hak Akses Menu](#1-navigasi--hak-akses-menu)
2. [Feature Flag](#2-feature-flag)
3. [Pengaturan Akademik](#3-pengaturan-akademik)
4. [Periode Akademik](#4-periode-akademik)
5. [Status Periode](#5-status-periode)
6. [Kelas dan Jadwal](#6-kelas-dan-jadwal)
7. [Dosen & Jadwal Kelas](#7-dosen--jadwal-kelas)
8. [KRS Mahasiswa (Portal)](#8-krs-mahasiswa-portal)
9. [Kelayakan Mata Kuliah SP di Halaman KRS](#9-kelayakan-mata-kuliah-sp-di-halaman-krs)
10. [Persetujuan KRS (Dosen Wali)](#10-persetujuan-krs-dosen-wali)
11. [KRS oleh Bagian Akademik](#11-krs-oleh-bagian-akademik)
12. [Tagihan & Pembayaran](#12-tagihan--pembayaran)
13. [Perkuliahan Dosen: Kepemilikan Kelas, Absensi, Ujian](#13-perkuliahan-dosen-kepemilikan-kelas-absensi-ujian)
14. [Portal Mahasiswa: Jadwal, Presensi, Ujian, Mata Kuliah](#14-portal-mahasiswa-jadwal-presensi-ujian-mata-kuliah)
15. [Penilaian](#15-penilaian)
16. [Nilai, KHS & Transkrip (Portal)](#16-nilai-khs--transkrip-portal)
17. [Dashboard & Laporan](#17-dashboard--laporan)
18. [Notifikasi](#18-notifikasi)
19. [Audit Log](#19-audit-log)
20. [Keamanan dari Sisi Halaman](#20-keamanan-dari-sisi-halaman)
21. [Tampilan & Kenyamanan](#21-tampilan--kenyamanan)
22. [Regresi Semester Reguler](#22-regresi-semester-reguler)
23. [Skenario End-to-End](#23-skenario-end-to-end)
24. [Ringkasan](#24-ringkasan)

---

## Cara Membaca

Kolom **Status**:

| Label | Arti |
|---|---|
| ✅ | Halaman dan fiturnya sudah ada — **bisa diuji sekarang**. Karena periode SP belum bisa dibuat (Tahap 1.1), kasus ✅ diuji pada periode **reguler**; halaman yang sama dipakai untuk SP. |
| 🧩 | Fitur sudah ada di backend, tetapi **halamannya belum ada** — belum bisa diuji dari frontend. Kasus tetap ditulis supaya langsung bisa dipakai saat halamannya dibuat; saat ini dicatat sebagai temuan. |
| ⏳ x.y | Fitur dan halamannya belum dibangun. Uji setelah Tahap x.y di [tahapan-pengembangan.md](./tahapan-pengembangan.md#4-detail-tahapan) selesai. |

Kolom **Prioritas**: **Tinggi** (nilai akademik, uang, hak akses, isolasi data antar-universitas), **Sedang** (fungsi inti), **Rendah** (kosmetik, kasus jarang).

**Penulisan langkah:** `Akademik › Kelas dan Jadwal` = klik menu Akademik di sidebar, lalu submenu Kelas dan Jadwal. Teks **tebal** = tombol atau tautan yang diklik. Teks dalam tanda kutip = teks yang harus tampil persis di layar.

**Aturan umum untuk setiap kasus gagal/ditolak:** pesan kesalahan tampil di layar dalam bahasa Indonesia (Alert merah di atas form/halaman atau teks merah di bawah kolom), lalu **muat ulang halaman** dan pastikan tidak ada data yang ikut berubah. Pemeriksaan ini tidak ditulis ulang di setiap baris.

---

## Akun Uji

Password semua akun `password`, kecuali Super Admin. Daftar lengkap: [README.md](../../README.md#akun-sample-login).

| Kode | Peran | Akun (Universitas A = UND) | Akun Universitas B (uji isolasi) |
|---|---|---|---|
| `AKD` | Bagian Akademik | `akademik@undigital.test` | `akademik@itmandala.test` |
| `DSN-X` | Dosen pengampu | `dosen@undigital.test` | `dosen@itmandala.test` |
| `DSN-Y` | Dosen pengampu lain | akun dosen kedua (siapkan, lihat Persiapan Data) | — |
| `WALI` | Dosen wali | `dosenpa@undigital.test` | — |
| `MHS` | Mahasiswa | `mahasiswa@undigital.test` + akun M2…M7 (Persiapan Data) | `mahasiswa@itmandala.test` |
| `KEU` | Bagian Keuangan | `keuangan@undigital.test` | — |
| `OWN` | Owner universitas | `owner@undigital.test` | `owner@itmandala.test` |
| `AUD` | Auditor | `auditor@undigital.test` | — |
| `SA` | Super Admin | `superadmin@civitasone.local` / `ChangeMe123!` | — |
| `STF` | Tanpa hak akses | `staff@civitasone.test` | — |

Sebelum mulai, login dengan setiap akun dan catat menu apa saja yang muncul di sidebar. Bila menu yang seharusnya ada tidak muncul (lihat §1), catat sebagai **bug hak akses**, bukan bug fitur.

---

## Persiapan Data

Sebagian data belum bisa dibuat dari halaman mana pun. Kolom **Cara** menunjukkan siapa yang menyiapkannya.

| Kode | Data | Cara |
|---|---|---|
| `T-GNP` | Periode Genap 2026/2027 sebagai periode berjalan, 2027-02-01 – 2027-06-30 | Sudah ada di data demo / ⏳ 1.5 halaman Periode Akademik |
| `T-SP` | Periode SP 2026/2027, kuliah 2027-07-05 – 2027-08-20, periode KRS 2027-06-21 – 2027-07-02 | ⏳ 1.5 halaman Periode Akademik |
| Periode KRS | Tanggal buka–tutup KRS sebuah periode | 🧩 belum ada halaman — **developer** menyiapkan |
| Kelas | `SP-A` & `SP-B` (Basis Data, kapasitas 30), kelas Algoritma, kelas MK baru | ⏳ 2.4 tombol Buka Kelas; untuk kelas reguler pakai data demo |
| Dosen pengampu & jadwal | Kelas `SP-A` diampu `DSN-X`, `SP-B` diampu `DSN-Y` | ✅ `Akademik › Kelas dan Jadwal` › detail › **Ubah** |
| Akun login dosen | `DSN-Y` punya akun login | ✅ `Dosen` › detail dosen (fitur akun dosen) |
| Prasyarat MK | MK `MK-PRQ` mensyaratkan Algoritma minimal C | 🧩 belum ada halaman — **developer** |
| Dosen wali | `M1` dibimbing `WALI` | 🧩 belum ada halaman — **developer** |
| Riwayat nilai | `M1` Basis Data **D**, `M2` Basis Data **B** di periode lampau | ✅ `Akademik › KRS` › **Tambah KRS** lalu `Akademik › Penilaian` › **Input Nilai** |
| Status mahasiswa | `M3` berstatus **Cuti** | **Developer** |
| Tunggakan | `M4` punya tagihan lain belum lunas yang lewat jatuh tempo | **Developer** (belum ada halaman buat tagihan) |
| IP untuk batas SKS | `M5` IP Genap 1,80 tidak ikut SP; `M6` IP Genap 1,80 ikut SP (IP SP 3,50); `M7` IP Genap 3,20 | ✅ lewat Input Nilai, atau developer |
| Pengaturan SP | Biaya Rp150.000/SKS, maks 9 SKS, batas ulang C, MK baru boleh, minimum peserta 5, kehadiran minimum 75%, mode persetujuan "dosen wali", jatuh tempo 3 hari | ⏳ 1.5 halaman Pengaturan Akademik |

---

## Temuan Awal: Menu & Halaman yang Belum Ada

Ditemukan saat menyusun dokumen ini. Semuanya memengaruhi pengujian SP dari frontend:

| # | Temuan | Dampak | Status |
|---|---|---|---|
| T-1 | Menu `Akademik › Persetujuan KRS` tampil di sidebar, tetapi halamannya belum ada — klik menu **dialihkan ke Dashboard** | Dosen wali tidak bisa menyetujui/menolak KRS dari aplikasi | 🧩 dibangun di Tahap 3.4 |
| T-2 | Menu `Akademik › Kelas Saya` sama: dialihkan ke Dashboard | Dosen tidak bisa mengelola materi & tugas kelasnya dari aplikasi | 🧩 |
| T-3 | Menu `Akademik › Pengajuan Mahasiswa` sama: dialihkan ke Dashboard | Bukan fitur SP, dicatat saja | 🧩 |
| T-4 | Tidak ada halaman untuk mengatur **periode KRS**, **prasyarat MK**, dan **dosen wali** | Data harus disiapkan developer; Bagian Akademik belum bisa mandiri | 🧩 |
| T-5 | Portal mahasiswa tidak punya menu **Tagihan** (halamannya masih data statis dan belum dipasang) | Mahasiswa tidak bisa melihat tagihan SP | ⏳ 4.5 |
| T-6 | Halaman `Akademik › KRS` tidak punya filter periode/status | Admin sulit memisahkan KRS SP dan reguler | ⏳ 3.4 |

---

## 1. Navigasi & Hak Akses Menu

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| NAV-01 | ✅ | Mahasiswa melihat menu portal | Login `MHS` | Perhatikan sidebar | — | Menu portal: Dashboard, Profil, Akademik (Akademik Saya, KRS, Jadwal, KHS, Nilai, Transkrip, Presensi, Dokumen), Perkuliahan, Ujian, dst. Menu admin (Kelas dan Jadwal, Penilaian, Pengaturan) **tidak** ada | Tinggi |
| NAV-02 | ✅ | Mahasiswa membuka halaman admin lewat URL | Login `MHS` | Ketik langsung `/akademik/kelas-jadwal` di address bar | — | Halaman "Anda tidak memiliki akses ke halaman ini" | Tinggi |
| NAV-03 | ✅ | Bagian Akademik melihat menu akademik | Login `AKD` | Perhatikan sidebar › Akademik | — | Kelas dan Jadwal, KRS tampil; menu portal mahasiswa tidak tampil | Sedang |
| NAV-04 | ✅ | Akun tanpa hak akses | Login `STF` | Perhatikan sidebar; ketik `/akademik/krs` | — | Sidebar hanya berisi menu umum; URL langsung → "Anda tidak memiliki akses ke halaman ini" | Sedang |
| NAV-05 | 🧩 | Menu Persetujuan KRS membuka halamannya | Login `WALI` atau `AKD` | `Akademik › Persetujuan KRS` | — | Halaman antrean persetujuan KRS terbuka. **Saat ini gagal: dialihkan ke Dashboard (T-1)** | Tinggi |
| NAV-06 | 🧩 | Menu Kelas Saya membuka halamannya | Login `DSN-X` | `Akademik › Kelas Saya` | — | Daftar kelas yang diampu terbuka. **Saat ini gagal: dialihkan ke Dashboard (T-2)** | Sedang |
| NAV-07 | ⏳ 1.5 | Menu Periode Akademik | Login `AKD` | Sidebar › Akademik | — | Ada submenu "Periode Akademik"; login `MHS`/`DSN-X` tidak melihat menu ini | Sedang |
| NAV-08 | ⏳ 3.4 | Menu Semester Pendek di portal | Login `MHS`; SP berstatus Draft, lalu Direncanakan | Perhatikan sidebar portal | — | Saat SP Draft: menu "Semester Pendek" tidak ada. Setelah Direncanakan: muncul | Sedang |
| NAV-09 | ✅ | Super Admin harus memilih universitas | Login `SA` | Perhatikan sidebar sebelum dan sesudah memilih universitas di Tenant Switcher | — | Sebelum memilih: hanya menu platform. Setelah memilih UND: menu Akademik, Keuangan, dst. muncul | Sedang |

---

## 2. Feature Flag

Halaman `Pengaturan › Feature Flag`. Dua flag terkait SP: **"Semester Pendek"** (`academic.short_term`) dan **"Kepemilikan Kelas Dosen"** (`academic.lecturer_ownership`). Keduanya awalnya nonaktif.

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| FLG-01 | ✅ | Tampilan di level universitas | Login `OWN` UND | `Pengaturan › Feature Flag` | — | Deskripsi halaman "Perubahan hanya berlaku untuk universitas Anda."; baris "Semester Pendek" kolom Sumber "Mengikuti global (nonaktif)", checkbox Aktif tidak dicentang | Sedang |
| FLG-02 | ✅ | Nyalakan SP untuk satu universitas | Lanjutan FLG-01 | Centang kolom **Aktif** baris "Semester Pendek" | — | Checkbox tercentang; kolom Sumber berubah menjadi badge "Diatur universitas" + tombol **Ikuti global** | Tinggi |
| FLG-03 | ✅ | Universitas lain tidak ikut berubah | Lanjutan FLG-02 | Logout, login `owner@itmandala.test`, buka Feature Flag | — | "Semester Pendek" tetap "Mengikuti global (nonaktif)" dan tidak tercentang | Tinggi |
| FLG-04 | ✅ | Kembali ke nilai global | Lanjutan FLG-02 | Klik **Ikuti global** | — | Sumber kembali "Mengikuti global (nonaktif)", checkbox kembali sesuai nilai global | Sedang |
| FLG-05 | ✅ | Super Admin mengubah nilai global | Login `SA` **tanpa** memilih universitas | `Pengaturan › Feature Flag`, centang "Semester Pendek" | — | Deskripsi "Nilai global — default untuk semua universitas yang tidak mengaturnya sendiri."; kolom Sumber tidak ada. Login `OWN` UND (tanpa override) → tampil "Mengikuti global (aktif)" | Sedang |
| FLG-06 | ✅ | Pengguna tanpa hak ubah | Login akun dengan izin lihat feature flag saja | Buka Feature Flag | — | Checkbox Aktif nonaktif (tidak bisa diklik); tombol **Ikuti global** tidak ada. Akun tanpa izin lihat: menu Feature Flag tidak muncul | Tinggi |
| FLG-07 | ✅ | Flag Kepemilikan Kelas Dosen | Login `OWN` UND | Nyalakan "Kepemilikan Kelas Dosen" | — | Sama seperti FLG-02; efeknya diuji di §13 | Tinggi |
| FLG-08 | ✅ | Pencarian flag | — | Ketik "pendek" di kolom pencarian | — | Hanya baris "Semester Pendek" tampil | Rendah |
| FLG-09 | ⏳ 1.5 | Buat SP saat flag nonaktif | Flag SP nonaktif di UND | `Akademik › Periode Akademik` › **Tambah Periode**, pilih Semester "Pendek", isi lengkap, **Simpan** | — | Alert merah "Fitur Semester Pendek belum diaktifkan untuk universitas ini."; periode tidak bertambah. Periode Ganjil/Genap tetap bisa dibuat | Tinggi |
| FLG-10 | ⏳ 3.4 | SP tersembunyi saat flag dimatikan lagi | SP sudah ada, lalu flag dimatikan | Login `MHS`, perhatikan portal | — | Menu/pilihan Semester Pendek hilang; setelah flag dinyalakan lagi, data SP muncul kembali utuh | Sedang |

---

## 3. Pengaturan Akademik

Halaman rancangan `Pengaturan › Akademik` (`/pengaturan/akademik`).

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| SET-01 | ⏳ 1.5 | Tampilan nilai default | Login `OWN`, belum pernah diatur | Buka Pengaturan Akademik | — | Maks SKS SP 9, minimum peserta 5, kehadiran minimum 75%, mode persetujuan "dosen wali", kebijakan nilai ulang "nilai tertinggi"; setiap kolom punya penjelasan satu kalimat | Sedang |
| SET-02 | ⏳ 1.5 | Ubah batas SKS SP | — | Ubah Maks SKS SP menjadi 6, **Simpan**; login `MHS`, buka KRS periode SP | `6` | Pesan sukses; halaman KRS mahasiswa langsung menampilkan "/ 6 SKS" (tanpa menunggu) | Tinggi |
| SET-03 | ⏳ 1.5 | Pengaturan tidak bocor ke universitas lain | SET-02 sudah dilakukan di UND | Login `owner@itmandala.test`, buka Pengaturan Akademik | — | Nilai milik universitas B sendiri (default 9) | Tinggi |
| SET-04 | ⏳ 1.5 | Isian tidak valid | — | Isi Maks SKS SP dengan `0`, `abc`, kosong; **Simpan** | — | Pesan validasi di bawah kolom; tidak tersimpan | Sedang |
| SET-05 | ⏳ 1.5 | Peringatan ganti kebijakan nilai ulang | — | Ubah "Kebijakan nilai mata kuliah ulang" ke "nilai terakhir" | — | Muncul peringatan bahwa IPK seluruh mahasiswa akan dihitung ulang, dengan konfirmasi | Sedang |
| SET-06 | ⏳ 1.5 | Hak akses | Login `DSN-X`, `MHS` | Ketik `/pengaturan/akademik` | — | "Anda tidak memiliki akses ke halaman ini" | Tinggi |

---

## 4. Periode Akademik

Halaman rancangan `Akademik › Periode Akademik` (`/akademik/periode`).

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| TRM-01 | ⏳ 1.5 | Daftar periode | Login `AKD` | Buka Periode Akademik | — | Tabel: Tahun Akademik, Semester (badge; SP = "Pendek"), Status (badge berwarna), Tanggal Kuliah, Periode KRS, Jumlah Kelas; filter semester/status/tahun | Sedang |
| TRM-02 | ⏳ 1.5 | Buat SP (AC-01) | Flag SP aktif | **Tambah Periode** → isi → **Simpan** | Tahun `2026/2027`, Semester Pendek, kuliah 05-07-2027 s.d. 20-08-2027, maks SKS 9 | Periode baru tampil: label "2026/2027 Pendek", status "Draft". Login `MHS`: SP ini **tidak** terlihat di mana pun | Tinggi |
| TRM-03 | ⏳ 1.5 | SP kedua di tahun yang sama (AC-02) | TRM-02 sudah ada | Tambah SP 2026/2027 lagi | Tanggal lain | Alert merah; periode tidak bertambah | Tinggi |
| TRM-04 | ⏳ 1.5 | Tanggal beririsan (AC-02) | Genap berakhir 30-06-2027 | Tambah SP dengan tanggal mulai 25-06-2027 | — | Alert merah "Tanggal Semester Pendek beririsan dengan 2026/2027 Genap." | Tinggi |
| TRM-05 | ⏳ 1.5 | Validasi form | — | Isi tahun `2026/2028`, `26/27`; tanggal selesai sebelum tanggal mulai; maks SKS `0` dan `25` | — | Pesan merah di bawah kolom terkait; tidak tersimpan | Sedang |
| TRM-06 | ⏳ 1.5 | Maks SKS dikosongkan | Pengaturan maks SKS SP = 6 | Buat SP tanpa mengisi maks SKS | — | Detail periode menampilkan batas efektif 6 SKS | Sedang |
| TRM-07 | ⏳ 1.5 | Ubah periode | SP Draft | **Ubah** → ganti tanggal selesai → **Simpan** | — | Tanggal berubah; status tetap Draft (form tidak punya kolom status) | Sedang |
| TRM-08 | ⏳ 1.5 | Kolom terkunci setelah ada kelas | SP sudah punya kelas | Buka form **Ubah** | — | Kolom Semester dan Tahun Akademik nonaktif | Sedang |
| TRM-09 | ⏳ 1.5 | Hapus SP Draft tanpa kelas | SP Draft, 0 kelas | **Hapus** → konfirmasi | — | SP hilang dari daftar | Sedang |
| TRM-10 | ⏳ 1.5 | Hapus SP yang punya kelas / bukan Draft (E27) | SP punya kelas atau status Direncanakan | **Hapus** | — | Tombol tidak ada/nonaktif, atau Alert merah; SP & kelasnya tetap ada | Tinggi |
| TRM-11 | ⏳ 1.5 | Atur periode KRS SP | SP Direncanakan | Detail periode › form Periode KRS | Buka 21-06-2027, tutup 02-07-2027 | Tersimpan; tampil di detail dan di halaman KRS mahasiswa | Tinggi |
| TRM-12 | ⏳ 1.5 | Periode KRS terbalik | — | Tanggal tutup sebelum tanggal buka | — | "Tanggal akhir KRS harus sama dengan atau setelah tanggal mulai." | Sedang |
| TRM-13 | ⏳ 1.5 | Periode KRS ditutup terlalu lambat | — | Tanggal tutup KRS 3 hari setelah kuliah SP dimulai | — | Pesan validasi; tidak tersimpan | Sedang |
| TRM-14 | ⏳ 1.1 | Label "Pendek" konsisten | SP punya kelas, KRS, nilai | Periksa: Kelas dan Jadwal (kolom Periode), KRS admin, Penilaian, portal KRS/Jadwal/KHS/Transkrip, PDF KHS & Transkrip | — | Selalu "2026/2027 Pendek" — **tidak pernah** "Genap" | Tinggi |
| TRM-15 | ⏳ 1.1 | SP tidak menambah nomor semester | `M1` semester 2 setelah Genap | Login `M1`, buka `Akademik › Nilai` dan Profil | — | Judul nilai SP tidak menjadi "Semester 3"; profil tetap semester 2 | Sedang |
| TRM-16 | ⏳ 1.3 | SP tidak menjadi periode berjalan | SP berstatus Berlangsung | Login `AKD`, buka Dashboard; login `MHS` buka KRS tanpa memilih periode | — | Dashboard & KRS default tetap menampilkan Genap | Tinggi |

---

## 5. Status Periode

Di detail periode (rancangan): dropdown **Ubah Status**. Urutan sah: Draft → Direncanakan → Pendaftaran Dibuka ⇄ Pendaftaran Ditutup → Berlangsung → Penilaian → Selesai → Diarsipkan, ditambah Direncanakan → Draft (bila belum ada KRS).

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| STS-01 | ⏳ 1.5 | Dropdown hanya menampilkan langkah sah | SP Draft | Buka **Ubah Status** | — | Hanya "Direncanakan". Ulangi di setiap status: hanya tujuan sah yang muncul | Tinggi |
| STS-02 | ⏳ 1.5 | Jalankan setiap transisi sah (AC-03) | Prasyarat transisi terpenuhi | Pilih status tujuan → konfirmasi | — | Badge status berubah; tercatat di tab Riwayat Status (pelaku, waktu, dari → ke) | Tinggi |
| STS-03 | ⏳ 1.5 | Buka pendaftaran tanpa kelas aktif | SP Direncanakan, 0 kelas aktif | Ubah Status → Pendaftaran Dibuka | — | Alert merah; status tetap | Tinggi |
| STS-04 | ⏳ 1.5 | Buka pendaftaran tanpa periode KRS | SP Direncanakan, periode KRS kosong | Ubah Status → Pendaftaran Dibuka | — | Alert merah; status tetap | Sedang |
| STS-05 | ⏳ 1.5 | Buka ulang pendaftaran | SP Pendaftaran Ditutup; (a) sebelum tanggal mulai kuliah, (b) sesudahnya | Ubah Status → Pendaftaran Dibuka | — | (a) berhasil; (b) pilihan tidak tersedia atau Alert merah | Sedang |
| STS-06 | ⏳ 1.5 | Kembali ke Draft saat sudah ada KRS | SP Direncanakan, ada mahasiswa memilih kelas | Ubah Status → Draft | — | Alert merah; status tetap | Sedang |
| STS-07 | ⏳ 5.5 | Selesaikan saat nilai belum final | SP Penilaian, ada nilai belum final | Ubah Status → Selesai | — | Ditolak dengan pesan; ada opsi "Paksa" yang meminta alasan | Tinggi |
| STS-08 | ⏳ 5.5 | Paksa selesai | Lanjutan STS-07 | Centang Paksa, isi alasan, konfirmasi | Alasan < 10 karakter, lalu ≥ 10 | Alasan pendek ditolak di form; alasan valid → status Selesai, alasan tampil di Riwayat Status & Audit Log | Tinggi |
| STS-09 | ⏳ 3.5 | Tutup pendaftaran melepas kursi draft | Mahasiswa punya mata kuliah berstatus Draft (belum diajukan) | Ubah Status → Pendaftaran Ditutup; cek sisa kursi kelas & KRS mahasiswa | — | Item Draft berubah "Dibatalkan"; sisa kursi bertambah; item Menunggu/Terdaftar tidak berubah | Tinggi |
| STS-10 | ⏳ 1.5 | Arsip mengunci semua perubahan (AC-20) | SP Diarsipkan | Coba: **Ubah** Dosen & Jadwal, **Tambah KRS**, **Rekam Kehadiran**, **Buat Ujian**, **Input Nilai**, ajukan revisi nilai | — | Tombol tidak tersedia atau Alert merah di setiap percobaan; semua data SP tetap bisa **dilihat**; transkrip tetap memuat SP | Tinggi |
| STS-11 | ⏳ 1.5 | Arsip & Selesai paksa butuh konfirmasi | — | Pilih Diarsipkan | — | Modal konfirmasi dengan kolom alasan sebelum disimpan | Sedang |

---

## 6. Kelas dan Jadwal

Halaman `Akademik › Kelas dan Jadwal`.

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| KLS-01 | ✅ | Daftar kelas | Login `AKD` | Buka Kelas dan Jadwal | — | Judul "Kelas dan Jadwal"; kolom Kelas, Program Studi, Periode, Dosen Pengampu, Jadwal, Terisi/Kapasitas, Status. Kelas tanpa dosen → badge kuning "Belum ditetapkan"; tanpa jadwal → "Belum ada" | Sedang |
| KLS-02 | ✅ | Filter kelas tanpa dosen | Ada kelas dengan & tanpa dosen | Dosen Pengampu = "Belum ditetapkan" → **Terapkan Filter** | — | Hanya kelas berbadge "Belum ditetapkan"; pilih "Semua" → semua kembali | Sedang |
| KLS-03 | ✅ | Cari kelas | — | Ketik nama MK atau kode kelas, tekan Enter | "Basis" | Hanya kelas yang cocok | Rendah |
| KLS-04 | ✅ | Detail kelas | — | Klik nama kelas | — | "Detail Kelas": Status, Program Studi, Periode Akademik, SKS, Kapasitas, Mahasiswa Terdaftar; kartu "Dosen & Jadwal" | Sedang |
| KLS-05 | ⏳ 2.4 | Tab Kelas di detail periode SP | SP ada | Periode Akademik › SP › tab **Kelas** | — | Hanya kelas SP; kolom tambahan Peserta (terdaftar/menunggu/kapasitas), Status (Aktif / Batal / Di bawah minimum) | Sedang |
| KLS-06 | ⏳ 2.4 | Buka kelas SP | SP Direncanakan | **Buka Kelas** → pilih MK (hanya MK prodi terpilih), kode kelas, kapasitas, minimum peserta → **Simpan** | `SP-A`, 30, 5 | Kelas baru tampil di tab Kelas | Tinggi |
| KLS-07 | ⏳ 2.4 | Validasi form Buka Kelas | — | Kapasitas `0` / `501`; minimum peserta 40 dengan kapasitas 30; kode kelas 11 karakter | — | Pesan merah di bawah kolom; tidak tersimpan | Sedang |
| KLS-08 | ⏳ 2.4 | Buka kelas di SP yang sudah berjalan | SP Berlangsung | Klik **Buka Kelas** | — | Tombol tidak tersedia, atau Alert merah saat menyimpan | Sedang |
| KLS-09 | ⏳ 2.4 | Turunkan kapasitas di bawah peserta (E25) | `SP-A` punya 12 peserta | Ubah kapasitas jadi 10 → Simpan | — | Alert merah "Kapasitas tidak boleh lebih kecil dari 12 peserta terdaftar." | Tinggi |
| KLS-10 | ⏳ 2.4 | Batalkan kelas sepi | SP Pendaftaran Ditutup, `SP-B` 3 peserta | **Batalkan Kelas** | Alasan "Peserta di bawah minimum 5 orang" | Modal menyebut jumlah peserta & tagihan terdampak **sebelum** konfirmasi; setelah konfirmasi: status "Batal"; KRS ketiga mahasiswa jadi "Dibatalkan" | Tinggi |
| KLS-11 | ⏳ 2.4 | Batalkan tanpa alasan | — | **Batalkan Kelas**, alasan kosong | — | Tombol konfirmasi tidak bisa dipakai / pesan "wajib diisi" | Sedang |
| KLS-12 | ⏳ 2.4 | Batalkan kelas yang sudah punya nilai/ujian | Kelas punya nilai atau ujian sudah dikerjakan | **Batalkan Kelas** | — | Alert merah; kelas & KRS tidak berubah | Tinggi |
| KLS-13 | ⏳ 2.4 | Kelas batal hilang dari pilihan mahasiswa | Lanjutan KLS-10 | Login `MHS`, buka KRS periode SP | — | `SP-B` tidak bisa dipilih | Sedang |

---

## 7. Dosen & Jadwal Kelas

**Bisa diuji sekarang di kelas reguler.** Langkah awal semua baris: login `AKD` → `Akademik › Kelas dan Jadwal` → klik nama kelas → kartu "Dosen & Jadwal" → **Ubah** → modal "Dosen & Jadwal". Bentrok hanya dicek terhadap kelas **aktif** lain di **periode yang sama**.

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| JDW-01 | ✅ | Tetapkan dosen & jadwal (AC-05) | Kelas tanpa dosen & jadwal | Cari dosen → pilih di dropdown Dosen pengampu → **Tambah Jadwal** dua kali → isi → **Simpan** | `DSN-X`; Senin 08:00–10:30 R.301; Rabu 08:00–10:30 R.301 | Modal tertutup; kartu menampilkan nama & email dosen dan "Senin, 08:00–10:30 · R.301", "Rabu, 08:00–10:30 · R.301"; halaman daftar menampilkan hal yang sama | Tinggi |
| JDW-02 | ✅ | Cari dosen | — | Ketik di "Cari nama, NIDN, atau email dosen..." | Nama yang tidak ada | "Tidak ada dosen aktif yang cocok dengan pencarian."; dosen nonaktif tidak pernah muncul di dropdown | Sedang |
| JDW-03 | ✅ | Jam selesai ≤ jam mulai | — | Isi baris jadwal → **Simpan** | Mulai 10:00, selesai 10:00 | Teks merah "Jam selesai harus setelah jam mulai." di bawah jam selesai; modal tidak tertutup | Sedang |
| JDW-04 | ✅ | Jam kosong / ruangan terlalu panjang | — | **Simpan** | Jam mulai kosong; ruangan 101 karakter | "Isi jam mulai." / "Isi jam selesai." / "Maksimal 100 karakter." | Rendah |
| JDW-05 | ✅ | Maksimal 7 jadwal | — | Klik **Tambah Jadwal** sampai 7 baris | — | Tombol **Tambah Jadwal** hilang setelah baris ke-7 | Rendah |
| JDW-06 | ✅ | Kosongkan jadwal | Kelas punya jadwal | Klik ikon tempat sampah di setiap baris → **Simpan** | — | Modal menampilkan "Belum ada jadwal." sebelum simpan; setelah simpan kartu menampilkan "Belum ada jadwal." | Rendah |
| JDW-07 | ✅ | Dua baris dalam kiriman saling bentrok | — | Isi dua baris → **Simpan** | Senin 08:00–10:00 dan Senin 09:00–11:00 | Alert merah di atas; badge merah "Bentrok antarjadwal" di **kedua** baris; teks "Bentrok ruangan dan antarjadwal tidak dapat dipaksa — ubah hari, jam, atau ruangan."; tidak ada pilihan paksa | Tinggi |
| JDW-08 | ✅ | Bentrok ruangan (E22) | Kelas aktif lain di periode sama: Senin 09:00–11:00 R.301 | Isi jadwal → **Simpan** | Senin 08:00–10:30, ruangan ` r.301 ` (huruf kecil & spasi) | Badge merah "Bentrok ruangan" + "Ruangan R.301 sudah dipakai kelas …"; tidak ada pilihan paksa (penulisan ruangan beda huruf/spasi tetap dianggap sama) | Tinggi |
| JDW-09 | ✅ | Ruangan kosong / daring | Kelas lain di jam yang sama | **Simpan** dengan ruangan kosong, lalu `online`, lalu `daring` | — | Tersimpan tanpa bentrok (petunjuk di modal: "Kosongkan ruangan untuk kelas daring.") | Sedang |
| JDW-10 | ✅ | Batas jam bersinggungan | Kelas aktif lain Senin 09:00–11:00 R.301 | **Simpan** tiga variasi | (a) Senin 11:00–12:00 R.301; (b) Senin 10:00–12:00 R.301; (c) Selasa 09:00–11:00 R.301 | (a) tersimpan — bersinggungan tepat tidak bentrok; (b) bentrok ruangan; (c) tersimpan | Tinggi |
| JDW-11 | ✅ | Bentrok dosen (E21) | `DSN-X` mengampu kelas aktif lain Senin 09:00 di periode sama | Pilih `DSN-X`, isi Senin 08:00–10:30 ruang lain → **Simpan** | — | Badge kuning "Bentrok dosen" + "Jadwal bentrok dengan kelas … yang juga diampu …"; muncul checkbox "Paksa simpan meski jadwal dosen bentrok" | Tinggi |
| JDW-12 | ✅ | Paksa: alasan wajib | Lanjutan JDW-11 | Centang paksa | Alasan "singkat" | Kolom "Alasan" muncul dengan petunjuk "Dicatat di audit log bersama daftar bentroknya."; tombol berubah merah "Paksa Simpan"; alasan < 10 karakter → "Alasan minimal 10 karakter." | Sedang |
| JDW-13 | ✅ | Paksa simpan berhasil | Lanjutan JDW-12 | Isi alasan valid → **Paksa Simpan** | "Dosen mengajar kelas gabungan SP-A dan SP-B" | Modal tertutup, jadwal tersimpan; entri baru di Audit Log (lihat AUD-02) | Tinggi |
| JDW-14 | ✅ | Bentrok campuran tidak bisa dipaksa | Bentrok dosen **dan** ruangan sekaligus | **Simpan** | — | Kedua badge tampil; checkbox paksa **tidak** muncul | Tinggi |
| JDW-15 | ✅ | Kelas nonaktif & periode lain diabaikan | Kelas nonaktif, dan kelas di periode lain, memakai jam & ruangan sama | **Simpan** jadwal yang sama | — | Tersimpan tanpa bentrok | Sedang |
| JDW-16 | ✅ | Ubah jadwal kelas berpeserta (E6) | Peserta kelas ini juga mengambil kelas lain Rabu 08:00 | Pindahkan jadwal ke Rabu 08:00–10:00 → **Simpan** | — | Tersimpan (tidak diblokir); Alert kuning "Jadwal baru beririsan dengan kelas lain yang diambil peserta" berisi "Nama (NIM) — MK kelas, jadwal"; bisa ditutup | Tinggi |
| JDW-17 | ✅ | Ganti dosen saja | Kelas berpeserta | Ganti dosen, jadwal tidak diubah → **Simpan** | — | Tidak ada Alert kuning | Sedang |
| JDW-18 | ✅ | Ganti dosen yang berhalangan (E5) | Kelas punya absensi & nilai | Ganti dosen → **Simpan** | — | Absensi (`Akademik › Absensi`) dan nilai (`Akademik › Penilaian`) kelas itu tetap utuh | Sedang |
| JDW-19 | ✅ | Batal tanpa menyimpan | — | Ubah isian → **Batal** | — | Modal tertutup; data kelas tidak berubah | Rendah |
| JDW-20 | ✅ | Dosen tidak bisa mengubah | Login `DSN-X` | Buka detail kelas | — | Kartu "Dosen & Jadwal" tampil tanpa tombol **Ubah** | Tinggi |
| JDW-21 | ⏳ 1.5 | Periode diarsipkan | Kelas di SP Diarsipkan | **Ubah** → **Simpan** | — | Tombol tidak ada, atau Alert merah | Sedang |

---

## 8. KRS Mahasiswa (Portal)

Halaman `Akademik › KRS` di portal mahasiswa ("Kartu Rencana Studi (KRS)"). Tahap 3.4 menambah **pilihan periode** di halaman ini (atau menu "Semester Pendek" yang membukanya untuk periode SP). Kasus ✅ diuji di periode Genap yang periode KRS-nya sedang dibuka (lihat Persiapan Data).

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| KRS-01 | ✅ | Ringkasan halaman | Login `M1` | Buka KRS | — | Subjudul "Semester 2026/2027 Genap"; kartu Status KRS, Beban SKS ("0 / 24 SKS"), Periode KRS (tanggal + badge "Dibuka") dan "Dosen wali: …" | Sedang |
| KRS-02 | ✅ | Daftar mata kuliah ditawarkan | — | Gulir ke "Mata Kuliah Ditawarkan" | — | Per mata kuliah: daftar kelas berisi "Kelas X", dosen (atau "Dosen belum ditetapkan"), jadwal (atau "Jadwal belum ditetapkan"), "Sisa n dari m kursi", tombol **Pilih Kelas** | Sedang |
| KRS-03 | ✅ | Pilih kelas | Kelas layak & masih ada kursi | **Pilih Kelas** | — | Alert hijau "Mata kuliah ditambahkan ke KRS."; muncul di "Mata Kuliah Dipilih" dengan badge "Draft"; kartu MK berbadge "Dipilih", kelas berbadge "Kelas dipilih"; Beban SKS bertambah; sisa kursi berkurang 1 | Tinggi |
| KRS-04 | ✅ | Hapus pilihan | Ada item Draft | **Hapus** pada item | — | "Mata kuliah dihapus dari KRS."; sisa kursi kembali | Sedang |
| KRS-05 | ✅ | Cari mata kuliah | — | Ketik di "Cari kode atau nama mata kuliah" | Kata acak | "Tidak ada mata kuliah yang ditawarkan." + "Coba kata kunci lain." | Rendah |
| KRS-06 | ✅ | Ajukan KRS (AC-06) | Ada item Draft | **Ajukan KRS** | — | Modal "Ajukan KRS ke dosen wali?" menyebut jumlah MK & total SKS; **Ya, Ajukan** → "KRS berhasil diajukan dan menunggu persetujuan dosen wali."; badge item "Menunggu Persetujuan"; tombol **Hapus** & **Pilih Kelas** hilang/nonaktif; muncul **Tarik Pengajuan** | Tinggi |
| KRS-07 | ✅ | Tarik pengajuan | Lanjutan KRS-06, periode KRS masih buka | **Tarik Pengajuan** | — | "Pengajuan KRS ditarik kembali. KRS dapat diubah lagi."; item kembali bisa dihapus/ditambah | Sedang |
| KRS-08 | ✅ | KRS disetujui | Dosen wali menyetujui (sementara lewat developer, T-1) | Muat ulang KRS | — | Item "Terdaftar"; Alert hijau "KRS semester ini sudah disetujui dan dikunci. Hubungi Bagian Akademik untuk perubahan KRS." | Tinggi |
| KRS-09 | ✅ | KRS ditolak | Dosen wali menolak dengan alasan (sementara lewat developer, T-1) | Muat ulang KRS | — | Alert merah "KRS ditolak dosen wali" berisi alasan + "— perbaiki KRS Anda lalu ajukan kembali."; item kembali "Draft" dan kursi tetap dipegang; bisa diubah & diajukan ulang | Tinggi |
| KRS-10 | ✅ | Cetak KRS | Ada item | **Cetak KRS** | — | Berkas PDF `KRS-2026-2027-Genap.pdf` terunduh berisi daftar MK | Rendah |
| KRS-11 | ✅ | Riwayat KRS | Ada KRS semester lalu | Riwayat KRS → **Tampilkan** | — | Daftar KRS sebelumnya beserta status; **Sembunyikan** menutupnya | Rendah |
| KRS-12 | ✅ | Periode KRS belum dibuka | Tanggal buka di masa depan | Buka KRS | — | Alert info "Periode KRS belum dibuka. KRS dapat diisi mulai … sampai …"; badge "Ditutup"; **Pilih Kelas** nonaktif | Tinggi |
| KRS-13 | ✅ | Periode KRS sudah lewat | Tanggal tutup di masa lalu | Buka KRS | — | "Periode KRS sudah ditutup pada …"; **Pilih Kelas** nonaktif | Tinggi |
| KRS-14 | ✅ | Periode KRS belum diatur | Tanggal KRS kosong | Buka KRS | — | Periode KRS "Belum ditetapkan"; "Periode KRS semester ini belum dibuka oleh Bagian Akademik." | Sedang |
| KRS-15 | ✅ | Periode ditutup saat halaman masih terbuka (E3) | Halaman KRS terbuka, lalu periode ditutup developer | Tanpa memuat ulang, klik **Pilih Kelas** | — | Alert merah "Periode KRS sudah ditutup …" — pengecekan dilakukan server, bukan hanya tampilan | Tinggi |
| KRS-16 | ✅ | Dua tab, kursi terakhir | Kelas tersisa 1 kursi; buka KRS di dua jendela browser dengan dua akun mahasiswa berbeda | Jendela 1 **Pilih Kelas**; jendela 2 (belum dimuat ulang) **Pilih Kelas** | — | Jendela 1 berhasil; jendela 2 Alert merah "Kelas … sudah penuh. Silakan pilih kelas lain." | Tinggi |
| KRS-17 | ✅ | Klik ganda (E11) | — | Klik **Pilih Kelas** dua kali cepat | — | Hanya satu item di "Mata Kuliah Dipilih" | Sedang |
| KRS-18 | ⏳ 3.4 | Pilih periode SP | SP Pendaftaran Dibuka, flag aktif | Pilih periode "2026/2027 Pendek" (atau menu Semester Pendek › Pilih Mata Kuliah) | — | Subjudul "Semester 2026/2027 Pendek"; Beban SKS "/ 9 SKS"; teks batas SKS menjelaskan batas tetap SP (bukan "dihitung dari IP semester sebelumnya") | Tinggi |
| KRS-19 | ⏳ 3.4 | Periode SP Draft tidak bisa dipilih | Ada SP Draft | Buka pilihan periode | — | SP Draft tidak ada di daftar | Tinggi |
| KRS-20 | ⏳ 3.4 | Tanpa memilih periode = perilaku lama | — | Buka KRS dari menu biasa | — | Menampilkan periode reguler berjalan, sama seperti sebelum ada SP | Tinggi |
| KRS-21 | ⏳ 3.4 | Informasi biaya | Biaya Rp150.000/SKS | Lihat kelas Basis Data 3 SKS di periode SP | — | Biaya "Rp450.000" per kelas; ringkasan pilihan menampilkan total SKS vs maksimum dan total biaya | Tinggi |
| KRS-22 | ⏳ 3.4 | Tampilan sebelum pendaftaran dibuka | SP Direncanakan | Buka KRS periode SP | — | "Pendaftaran dibuka {tanggal}."; daftar kelas terlihat, tombol **Pilih Kelas** nonaktif | Sedang |
| KRS-23 | ⏳ 3.3 | Mode persetujuan otomatis | Pengaturan mode persetujuan "otomatis" | Pilih kelas → **Ajukan KRS** | — | Modal tidak menyebut "dosen wali"; setelah diajukan status langsung "Disetujui", tanpa antrean | Tinggi |
| KRS-24 | ⏳ 4.5 | Menunggu pembayaran | Biaya > 0, KRS SP disetujui | Buka KRS periode SP | — | Badge oranye "Menunggu pembayaran" + jumlah + jatuh tempo + tautan ke Tagihan | Tinggi |
| KRS-25 | ⏳ 3.4 | Tampilan per status SP | Ubah status SP satu per satu | Buka KRS periode SP di setiap status | — | Sesuai [frontend-dan-mobile.md §3.2](./frontend-dan-mobile.md#32-keadaan-ui-per-status): Ditutup → baca saja; Berlangsung/Penilaian → pilihan MK tersembunyi; Selesai → IPS SP & nilai per MK | Sedang |
| KRS-26 | ⏳ 4.3 | Batalkan item SP yang belum dibayar (AC-13) | Item Menunggu pembayaran, pendaftaran masih dibuka | Batalkan item | — | Item "Dibatalkan"; tagihan berkurang atau dibatalkan | Tinggi |
| KRS-27 | ⏳ 4.3 | Batalkan item yang sudah dibayar (AC-13) | Item Terdaftar & lunas | Cari tombol batal | — | Tombol tidak ada, atau Alert merah; item tidak berubah | Tinggi |

---

## 9. Kelayakan Mata Kuliah SP di Halaman KRS

Mahasiswa harus tahu **mengapa** sebuah kelas tidak bisa dipilih. Kelas yang tidak layak tetap ditampilkan, dengan alasannya. Kolom "Hasil" menjelaskan apa yang tampil di halaman KRS (pilih periode SP bila ⏳). Untuk setiap baris, **muat ulang** halaman dan pastikan tidak ada item yang tersimpan.

| ID | Status | Skenario | Prasyarat | Hasil yang diharapkan di halaman KRS | Prioritas |
|---|---|---|---|---|---|
| ELG-01 | ✅ | Kelas penuh (E1) | Kursi kelas habis | Teks merah "Kelas penuh"; **Pilih Kelas** nonaktif | Tinggi |
| ELG-02 | ✅ | Bentrok jadwal dengan pilihan sendiri | Sudah memilih kelas Senin 08:00–10:30 | Kelas lain di Senin 09:00: teks merah "Bentrok dengan: …"; **Pilih Kelas** nonaktif | Tinggi |
| ELG-03 | ✅ | Prasyarat belum lulus (E14) | `M1` belum lulus Algoritma; `MK-PRQ` mensyaratkannya | Kartu `MK-PRQ`: bagian "Prasyarat" dengan badge merah Algoritma; badge "Tidak dapat diambil"; Alert kuning "Prasyarat belum terpenuhi: IF305 Algoritma."; tombol nonaktif | Tinggi |
| ELG-04 | ✅ | Sudah lulus — periode **reguler** | `M2` lulus Basis Data B, isi KRS Genap | Badge "Tidak dapat diambil"; Alert kuning "Sudah lulus dengan nilai B." (aturan reguler tidak berubah karena SP) | Tinggi |
| ELG-05 | ✅ | Mengulang nilai D — periode reguler | `M1` Basis Data D | Badge kuning "Mengulang (nilai D)"; kelas bisa dipilih | Sedang |
| ELG-06 | ✅ | Melebihi batas SKS | SKS terpilih hampir mencapai batas | **Pilih Kelas** kelas 3 SKS → Alert merah "SKS yang dipilih melebihi batas maksimum semester ini (…)."; bar Beban SKS tidak melewati batas | Tinggi |
| ELG-07 | ✅ | Mahasiswa cuti | Login `M3` | Alert "Status akademik Anda saat ini Cuti. Pengisian KRS hanya untuk mahasiswa berstatus Aktif."; tidak bisa memilih | Tinggi |
| ELG-08 | ⏳ 3.2 | Boleh mengulang di SP | `M1` (Basis Data D), batas ulang C | Kelas Basis Data SP: badge "Mengulang (nilai D)"; bisa dipilih | Tinggi |
| ELG-09 | ⏳ 3.2 | Nilai terlalu tinggi untuk diulang di SP | `M2` (Basis Data B), batas ulang C | Badge "Tidak dapat diambil"; alasan "Nilai B tidak memenuhi syarat untuk diulang (maksimal C)." | Tinggi |
| ELG-10 | ⏳ 3.2 | Tanpa batas ulang | Batas ulang dikosongkan, login `M2` | Kelas Basis Data SP bisa dipilih | Sedang |
| ELG-11 | ⏳ 3.2 | MK baru dilarang di SP | Pengaturan "MK baru boleh" dimatikan | Kelas `MK-NEW`: badge "Tidak dapat diambil" + alasannya | Sedang |
| ELG-12 | ⏳ 3.2 | Tunggakan tagihan | Login `M4` | Alasan "Masih ada tagihan yang belum lunas."; tidak bisa memilih | Tinggi |
| ELG-13 | ⏳ 4.2 | Tagihan SP sendiri bukan tunggakan | `M1` sudah punya tagihan SP belum jatuh tempo | Kelas SP kedua **tetap bisa** dipilih | Sedang |
| ELG-14 | ⏳ 3.2 | MK sama di kelas lain | `M1` sudah memilih `SP-A` (Basis Data) | `SP-B` (Basis Data) tidak bisa dipilih; alasan "Anda sudah mengambil mata kuliah ini di kelas lain." | Tinggi |
| ELG-15 | ⏳ 3.2 | MK masih berjalan di periode lain | `M1` sedang mengambil Basis Data di periode yang belum selesai | Alasan "Mata kuliah ini sedang Anda ambil di {periode}." | Sedang |
| ELG-16 | ⏳ 3.2 | Kelas prodi lain | Kelas untuk prodi berbeda | Kelas tidak tampil, atau tampil dengan alasan "Kelas ini bukan untuk program studi Anda." | Sedang |
| ELG-17 | ⏳ 1.2 | Batas SKS SP (E20) | `M1` sudah 9 SKS (batas 9) | Kelas berikutnya ditolak dengan pesan yang menyebut batas 9 SKS | Tinggi |
| ELG-18 | ⏳ 3.2 | Pendaftaran SP belum/tidak lagi dibuka | SP Direncanakan, atau di luar tanggal KRS SP | Pesan "Pendaftaran Semester Pendek belum dibuka atau sudah ditutup."; tidak bisa memilih | Tinggi |
| ELG-19 | ⏳ 3.2 | Ajukan memeriksa ulang seluruh pilihan (AC-08) | `M1` memilih 2 kelas, lalu admin menurunkan maks SKS SP jadi 3 | **Ajukan KRS** → Alert merah soal batas SKS; **tidak ada** item yang berubah status | Tinggi |
| ELG-20 | ⏳ 3.2 | Minimum SKS saat menghapus | Minimum SKS SP 6, `M1` punya 2 item × 3 SKS | **Hapus** satu item → ditolak dengan pesan minimum SKS; menghapus **semua** item tetap boleh | Rendah |

---

## 10. Persetujuan KRS (Dosen Wali)

Menu `Akademik › Persetujuan KRS`. **Halamannya belum ada (T-1)** — semua kasus 🧩 sampai Tahap 3.4. Pesan di bawah sudah dipakai backend, jadi harus tampil persis saat halamannya dibuat.

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| APV-01 | 🧩 | Antrean dosen wali | `M1` (perwalian `WALI`) & mahasiswa lain sudah mengajukan KRS | Login `WALI` → Persetujuan KRS | — | Hanya pengajuan mahasiswa perwaliannya; pencarian & filter status berfungsi | Tinggi |
| APV-02 | 🧩 | Detail pengajuan | — | Klik satu pengajuan | — | Daftar MK, total SKS, batas SKS, tanggal diajukan | Sedang |
| APV-03 | 🧩 | Setujui | — | **Setujui** (catatan opsional) | — | "KRS disetujui."; pengajuan hilang dari antrean menunggu; KRS mahasiswa "Terdaftar" (cek KRS-08) | Tinggi |
| APV-04 | 🧩 | Tolak tanpa alasan | — | **Tolak** dengan alasan kosong, lalu "abc" | — | "Tuliskan alasan penolakan supaya mahasiswa dapat memperbaiki KRS-nya." / "Alasan penolakan minimal 5 karakter." | Sedang |
| APV-05 | 🧩 | Tolak (AC-10) | — | **Tolak** | "Ambil Algoritma dulu" | "KRS ditolak dan dikembalikan ke mahasiswa."; mahasiswa melihat KRS-09 | Tinggi |
| APV-06 | 🧩 | Diputuskan dua kali | Dua jendela membuka pengajuan yang sama | Jendela 1 **Setujui**, jendela 2 **Tolak** | — | Jendela 2: "KRS ini sudah diputuskan sebelumnya atau belum diajukan." | Sedang |
| APV-07 | 🧩 | Bagian Akademik melihat semua | Login `AKD` | Buka Persetujuan KRS | — | Pengajuan semua mahasiswa, termasuk yang tidak punya dosen wali | Sedang |
| APV-08 | ⏳ 3.3 | Antrean per periode | Ada pengajuan Genap & SP | Filter periode "2026/2027 Pendek" | — | Hanya pengajuan SP | Sedang |
| APV-09 | ⏳ 3.3 | Mode otomatis | Mode persetujuan "otomatis" | Mahasiswa mengajukan KRS SP; buka antrean | — | Pengajuan SP tidak masuk antrean (langsung disetujui) | Sedang |
| APV-10 | ✅ | Mahasiswa tidak bisa membuka | Login `MHS` | Ketik `/akademik/persetujuan-krs` | — | "Anda tidak memiliki akses ke halaman ini" | Tinggi |

---

## 11. KRS oleh Bagian Akademik

Halaman `Akademik › KRS` (admin).

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| ADM-01 | ✅ | Daftar KRS | Login `AKD` | Buka KRS | — | Kolom Mahasiswa, Mata Kuliah, Periode, Status, Nilai ("Belum dinilai" bila kosong) | Sedang |
| ADM-02 | ✅ | Daftarkan mahasiswa | — | **Tambah KRS** → pilih Mahasiswa & Kelas → simpan | `M1`, kelas reguler | Modal tertutup; baris baru berstatus "Terdaftar" | Tinggi |
| ADM-03 | ✅ | Daftarkan ke kelas penuh / duplikat | Kelas penuh, atau mahasiswa sudah di kelas itu | **Tambah KRS** | — | Alert merah di dalam modal; tidak ada baris baru | Tinggi |
| ADM-04 | ✅ | Batalkan KRS mahasiswa | Baris berstatus Terdaftar | **Batalkan** → modal "Batalkan KRS" ("Batalkan pendaftaran {nama} pada {MK}?") → **Batalkan KRS** | — | Status baris menjadi "Dibatalkan"; tombol **Batalkan** hanya ada pada baris Terdaftar | Sedang |
| ADM-05 | ✅ | Dosen tidak bisa menambah/membatalkan | Login `DSN-X` | Buka KRS | — | Tidak ada tombol **Tambah KRS**, tidak ada kolom Aksi | Tinggi |
| ADM-06 | ⏳ 3.4 | Filter periode & status | — | Filter periode SP, status "Menunggu"/"Draft" | — | Hanya KRS sesuai filter (saat ini filter belum ada, T-6) | Sedang |
| ADM-07 | ⏳ 3.2 | Mahasiswa tidak aktif ditolak | — | **Tambah KRS** untuk `M3` (Cuti) | — | Alert merah "Hanya mahasiswa berstatus aktif yang dapat mendaftar." — berlaku juga untuk periode reguler | Tinggi |
| ADM-08 | ⏳ 3.2 | MK sama di dua kelas | `M1` sudah di `SP-A` | **Tambah KRS** `M1` ke `SP-B` | — | Alert merah bahwa mahasiswa sudah mengambil mata kuliah ini di kelas lain | Tinggi |
| ADM-09 | ⏳ 3.4 | Bagian override hanya untuk yang berhak | Akun tanpa hak override, lalu akun dengan hak | Buka modal **Tambah KRS** | — | Tanpa hak: bagian "Override aturan" tidak ada. Dengan hak: checkbox per aturan (prasyarat, batas SKS, bentrok jadwal, kapasitas) + kolom alasan | Tinggi |
| ADM-10 | ⏳ 3.4 | Override prasyarat (E15) | Mahasiswa belum memenuhi prasyarat | Centang override prasyarat, alasan → simpan | Alasan "Konversi transfer kredit, SK Dekan 12/2027" | Baris baru tersimpan; tercatat di Audit Log | Tinggi |
| ADM-11 | ⏳ 3.4 | Override tanpa alasan cukup | — | Centang override, alasan "ok" | — | Pesan "minimal 10 karakter"; tidak tersimpan | Sedang |
| ADM-12 | ⏳ 4.3 | Batalkan KRS SP wajib alasan | Baris KRS SP Terdaftar | **Batalkan** | Alasan kosong | Modal meminta alasan; tanpa alasan tidak bisa dikonfirmasi | Sedang |

---

## 12. Tagihan & Pembayaran

Halaman `Keuangan › Tagihan` (admin) dan menu Tagihan di portal (belum ada, T-5).

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| PAY-01 | ✅ | Daftar & detail tagihan (baca saja) | Login akun dengan izin lihat tagihan | `Keuangan › Tagihan`; filter Status; klik satu tagihan | — | Kolom Tagihan, Jumlah, Terbayar, Status, Jatuh Tempo; detail menampilkan "Riwayat Pembayaran" | Sedang |
| PAY-02 | ⏳ 4.5 | Mahasiswa melihat tagihannya | Login `MHS` | Portal › **Tagihan** | — | Menu ada (saat ini tidak ada, T-5); hanya tagihan milik sendiri; "Riwayat Pembayaran" | Tinggi |
| PAY-03 | ⏳ 4.2 | SP gratis tanpa tagihan | Biaya per SKS 0 | KRS SP `M1` disetujui; cek Tagihan admin & portal | — | Tidak ada tagihan baru; KRS langsung "Terdaftar" | Tinggi |
| PAY-04 | ⏳ 4.2 | Tagihan terbit setelah disetujui (AC-11) | Biaya Rp150.000/SKS | KRS SP `M1` (Basis Data 3 SKS) disetujui; buka Tagihan | — | Tagihan "Belum Bayar" Rp450.000, periode "2026/2027 Pendek", jatuh tempo hari ini + 3; KRS `M1` tetap "Menunggu pembayaran" | Tinggi |
| PAY-05 | ⏳ 4.2 | Ditolak = tidak ditagih | KRS SP ditolak | Cek Tagihan | — | Tidak ada tagihan untuk mahasiswa itu | Tinggi |
| PAY-06 | ⏳ 4.5 | Filter periode | Login `KEU` | Filter periode SP | — | Hanya tagihan SP | Sedang |
| PAY-07 | ⏳ 4.5 | Catat pembayaran sebagian | Tagihan Rp450.000 | Detail tagihan › **Catat Pembayaran** | Rp200.000, tanggal hari ini, transfer, ref "TRF-1" | Status "Sebagian"; Terbayar Rp200.000; Riwayat Pembayaran bertambah; KRS mahasiswa masih menunggu pembayaran | Tinggi |
| PAY-08 | ⏳ 4.5 | Lunasi | Lanjutan PAY-07 | **Catat Pembayaran** | Rp250.000 | Status "Lunas"; KRS `M1` jadi "Terdaftar"; `M1` menerima notifikasi pembayaran | Tinggi |
| PAY-09 | ⏳ 4.5 | Bayar melebihi sisa | Sisa Rp250.000 | **Catat Pembayaran** | Rp300.000 | Pesan di kolom jumlah; tidak tersimpan | Tinggi |
| PAY-10 | ⏳ 4.5 | Validasi form pembayaran | — | **Catat Pembayaran** | Jumlah 0 / minus; tanggal besok; metode kosong | Pesan per kolom; tidak tersimpan | Sedang |
| PAY-11 | ⏳ 4.5 | Status tidak bisa diubah manual | Tagihan Belum Bayar | Cari cara mengubah status menjadi Lunas di detail tagihan | — | Tidak ada kontrol ubah status; status hanya berubah lewat Catat Pembayaran / Batalkan | Tinggi |
| PAY-12 | ⏳ 4.5 | Batalkan tagihan belum dibayar | Tagihan Belum Bayar, terbayar 0 | **Batalkan** + alasan | — | Status "Dibatalkan" | Sedang |
| PAY-13 | ⏳ 4.5 | Batalkan tagihan yang sudah dibayar | Status Sebagian/Lunas | Cari tombol **Batalkan** | — | Tombol tidak ada, atau Alert merah | Tinggi |
| PAY-14 | ⏳ 4.5 | Bayar tagihan yang dibatalkan/lunas | — | Cari tombol **Catat Pembayaran** | — | Tombol tidak ada, atau Alert merah | Tinggi |
| PAY-15 | ⏳ 4.5 | Hanya Keuangan yang mencatat | Login `AKD`, `MHS` | Buka detail tagihan | — | Tombol **Catat Pembayaran** tidak ada | Tinggi |
| PAY-16 | ⏳ 4.5 | Jatuh tempo lewat ditandai | Tagihan lewat jatuh tempo | Lihat daftar | — | Tanggal jatuh tempo berwarna merah | Rendah |
| PAY-17 | ⏳ 4.4 | Kedaluwarsa melepas kursi (AC-12) | `M1` belum bayar, jatuh tempo kemarin, kelas tersisa 0 kursi | Login mahasiswa lain, pilih kelas itu | — | Mahasiswa lain **berhasil**; KRS `M1` jadi "Dibatalkan", tagihannya "Dibatalkan"; `M1` menerima notifikasi kedaluwarsa | Tinggi |
| PAY-18 | ⏳ 4.4 | Kedaluwarsa setelah semalam | Kondisi sama, tanpa ada yang mendaftar | Keesokan harinya buka KRS `M1` & Tagihan | — | Hasil sama dengan PAY-17 (proses harian berjalan) | Tinggi |
| PAY-19 | ⏳ 4.4 | Tagihan sebagian yang lewat jatuh tempo | Tagihan Sebagian lewat jatuh tempo | Buka KRS mahasiswa keesokan harinya | — | KRS **tidak** dibatalkan otomatis; tagihan ditandai untuk ditindaklanjuti | Sedang |
| PAY-20 | ⏳ 4.5 | Daftar perlu refund | Admin membatalkan KRS SP yang sudah lunas | Periode SP › tab Pembayaran | — | Mahasiswa itu muncul di daftar "Perlu refund"; tagihan tetap "Lunas" | Tinggi |

---

## 13. Perkuliahan Dosen: Kepemilikan Kelas, Absensi, Ujian

### 13.1 Kepemilikan kelas dosen (AC-15)

**Bisa diuji sekarang.** Nyalakan flag "Kepemilikan Kelas Dosen" untuk UND (FLG-07). `DSN-X` mengampu kelas A, `DSN-Y` mengampu kelas B, keduanya punya peserta.

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| OWN-01 | ✅ | Daftar kelas hanya milik sendiri | Login `DSN-X` | `Akademik › Kelas dan Jadwal` | — | Hanya kelas A | Tinggi |
| OWN-02 | ✅ | Buka kelas orang lain lewat URL | Salin URL detail kelas B (dari akun `AKD`) | Login `DSN-X`, tempel URL | — | Alert merah (tidak punya izin); data kelas B tidak tampil | Tinggi |
| OWN-03 | ✅ | Input nilai hanya peserta kelas sendiri | — | `Akademik › Penilaian` › **Input Nilai** › dropdown "Mahasiswa · Mata Kuliah" | — | Hanya peserta kelas A | Tinggi |
| OWN-04 | ✅ | Daftar nilai & KRS terbatas | — | Buka `Akademik › Penilaian` dan `Akademik › KRS` | — | Hanya baris kelas A | Tinggi |
| OWN-05 | ✅ | Rekam kehadiran hanya kelas sendiri | — | `Akademik › Absensi` › **Rekam Kehadiran** › dropdown Kelas | — | Hanya kelas A | Tinggi |
| OWN-06 | ✅ | Ujian hanya kelas sendiri | Ada ujian kelas B | `Akademik › Ujian`; **Buat Ujian** › dropdown Kelas; tempel URL detail ujian kelas B | — | Daftar hanya ujian kelas A; dropdown hanya kelas A; URL ujian kelas B → Alert merah | Tinggi |
| OWN-07 | ✅ | Bagian Akademik tetap melihat semua | Login `AKD` | Buka halaman yang sama | — | Kelas A & B tampil | Tinggi |
| OWN-08 | ✅ | Flag nonaktif | Matikan flag | Login `DSN-X`, ulangi OWN-01 | — | Kelas A & B tampil (perilaku lama) | Sedang |
| OWN-09 | ✅ | Flag hanya aktif di universitas lain | Flag aktif di ITM, nonaktif di UND | Login `DSN-X` (UND) | — | Tidak dibatasi | Sedang |

### 13.2 Absensi & ujian kelas SP

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| LRN-01 | ✅ | Rekam kehadiran | Login `DSN-X` | **Rekam Kehadiran** → Kelas, Pertemuan ke-, Tanggal → atur status per mahasiswa → **Simpan Kehadiran** | Pertemuan 1 | Riwayat baru tampil di tabel Absensi (Tanggal, "Ke-1", Mahasiswa, Status) | Sedang |
| LRN-02 | ✅ | Peserta yang belum disetujui tidak ikut kuliah (AC-14) | `M1` mengajukan KRS kelas A tetapi belum disetujui | (a) **Rekam Kehadiran** kelas A; (b) **Input Nilai**; (c) login `M1` buka Portal › Ujian | — | (a) `M1` tidak ada di daftar peserta; (b) `M1` tidak ada di pilihan; (c) ujian kelas A tidak muncul | Tinggi |
| LRN-03 | ✅ | Peserta belum disetujui lewat link ujian publik | Lanjutan LRN-02, ujian kelas A sudah di-publish | Buka link/QR ujian → isi NIM `M1` | — | "Anda tidak terdaftar pada kelas untuk ujian ini." | Tinggi |
| LRN-04 | ✅ | Kelas tanpa peserta aktif | Kelas tanpa peserta Terdaftar | **Rekam Kehadiran**, pilih kelas itu | — | "Belum ada mahasiswa terdaftar aktif di kelas ini."; **Simpan Kehadiran** nonaktif | Rendah |
| LRN-05 | ✅ | Ujian kelas SP sama dengan reguler | Kelas SP punya peserta Terdaftar (setelah Fase 3) | **Buat Ujian** → isi → **Buat Ujian**; tambah soal; **Publish Ujian**; **Copy Link**; mahasiswa **Mulai Ujian** | — | Semua berjalan seperti ujian reguler; Rekap Nilai berisi peserta SP | Sedang |
| LRN-06 | ⏳ 5.4 | Persentase kehadiran | 8 pertemuan: 5 hadir, 1 izin, 1 sakit, 1 alpa | Lihat kehadiran mahasiswa (dosen & portal) | Pengaturan "izin/sakit dihitung hadir" aktif, lalu nonaktif | 87,5%, lalu 62,5% | Sedang |
| LRN-07 | ⏳ 5.4 | Belum ada pertemuan | 0 pertemuan | Lihat kehadiran | — | "-" atau kosong, **bukan** 0% | Rendah |
| LRN-08 | ⏳ 5.4 | Kehadiran kurang memblokir ujian | Pengaturan "kehadiran memblokir ujian" aktif, kehadiran `M1` 62% | Login `M1`, **Mulai Ujian** | — | "Kehadiran Anda 62% di bawah batas 75%." | Sedang |
| LRN-09 | ⏳ 5.4 | Tidak memblokir bila pengaturan mati | Pengaturan mati | **Mulai Ujian** | — | Ujian dimulai | Sedang |
| LRN-10 | 🧩 | Materi & tugas kelas SP | Halaman Kelas Saya (T-2) | Dosen unggah materi & buat tugas di kelas SP; mahasiswa SP membuka Perkuliahan › Materi/Tugas | — | Materi & tugas tampil hanya untuk peserta kelas itu | Sedang |
| LRN-11 | ⏳ 3.4 | Pilih periode di kelas dosen | `DSN-X` mengampu kelas Genap & SP | Pilih periode SP di halaman kelas dosen | — | Hanya kelas SP | Sedang |

---

## 14. Portal Mahasiswa: Jadwal, Presensi, Ujian, Mata Kuliah

Saat SP berjalan, periode Genap masih menjadi periode berjalan — portal harus menampilkan **keduanya**.

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| PRT-01 | ✅ | Jadwal kuliah reguler | Login `M1` dengan KRS Terdaftar | `Akademik › Jadwal` | — | "Jadwal Kuliah": kartu "Hari ini — …", "Kuliah Berikutnya", jadwal per hari | Sedang |
| PRT-02 | ⏳ 3.4 | Jadwal memuat kelas SP | `M1` Terdaftar di kelas SP Berlangsung | `Akademik › Jadwal` | — | Kelas SP tampil bersama kelas Genap | Tinggi |
| PRT-03 | ⏳ 3.4 | Presensi memuat kelas SP | Lanjutan | `Akademik › Presensi` | — | Rekap kehadiran kelas SP tampil | Tinggi |
| PRT-04 | ⏳ 3.4 | Mata kuliah, materi, tugas SP | Lanjutan | `Perkuliahan › Mata Kuliah / Materi / Tugas` | — | Kelas SP tampil | Tinggi |
| PRT-05 | ✅ | Ujian kelas SP | Lanjutan, ujian SP di-publish | `Ujian` | — | Ujian kelas SP tampil, **Mulai Ujian** berfungsi | Tinggi |
| PRT-06 | ⏳ 3.4 | Dashboard mahasiswa | Lanjutan | Dashboard | — | Jadwal hari ini & ringkasan mencakup kelas SP | Sedang |
| PRT-07 | ⏳ 3.4 | Ringkasan Semester Pendek | SP Pendaftaran Dibuka | Menu Semester Pendek › Ringkasan | — | Status SP, aturan (maks SKS, biaya/SKS), hitung mundur penutupan, status KRS saya | Sedang |

---

## 15. Penilaian

Halaman `Akademik › Penilaian`.

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| GRD-01 | ✅ | Input nilai | Login `DSN-X` | **Input Nilai** → pilih peserta → modal "Input Nilai": Skor (0–100), Nilai Huruf opsional → simpan | Skor 78, huruf kosong | Baris tampil dengan Nilai Huruf B dan Skor 78 | Tinggi |
| GRD-02 | ✅ | Nilai huruf manual | — | Isi skor & pilih huruf | Skor 78, huruf A | Nilai Huruf A (manual mengalahkan otomatis) | Sedang |
| GRD-03 | ✅ | Skor di luar rentang | — | Isi skor | `101`, `-1`, kosong | Pesan validasi di kolom Skor; tidak tersimpan | Sedang |
| GRD-04 | ✅ | Mahasiswa tidak bisa input nilai | Login `MHS` | Ketik `/akademik/penilaian` | — | "Anda tidak memiliki akses ke halaman ini" | Tinggi |
| GRD-05 | ⏳ 5.5 | Kolom status & filter periode | Ada nilai SP | Filter periode SP | — | Kolom Status: Draft / Disubmit / Final | Sedang |
| GRD-06 | ⏳ 5.1 | Nilai SP tersimpan sebagai draft | SP Berlangsung | **Input Nilai** peserta SP | Skor 78 | Status "Draft"; login `M1`: nilai belum tampil di Portal › Nilai/KHS/Transkrip | Tinggi |
| GRD-07 | ⏳ 5.1 | Nilai reguler tetap langsung tampil | Kelas Genap | **Input Nilai** | Skor 78 | Status "Disubmit"; langsung tampil di portal mahasiswa (perilaku sekarang) | Tinggi |
| GRD-08 | ⏳ 5.5 | Submit nilai kelas belum lengkap | SP Penilaian, 2 peserta belum dinilai | **Submit Nilai Kelas** | — | Alert merah + daftar NIM & nama yang belum dinilai | Tinggi |
| GRD-09 | ⏳ 5.5 | Submit nilai kelas (AC-16) | Semua peserta dinilai | **Submit Nilai Kelas** → konfirmasi | — | Semua "Disubmit"; `AKD` mendapat notifikasi | Tinggi |
| GRD-10 | ⏳ 5.5 | Kembalikan ke dosen | Login `AKD`, nilai Disubmit | **Kembalikan** + alasan | Alasan kosong, lalu "Nilai UAS belum masuk" | Tanpa alasan ditolak; dengan alasan semua kembali "Draft" dan dosen dinotifikasi | Sedang |
| GRD-11 | ⏳ 5.5 | Finalisasi kelas (AC-16) | Semua Disubmit, SP Penilaian | **Finalisasi Kelas** → konfirmasi | — | Semua "Final" | Tinggi |
| GRD-12 | ⏳ 5.5 | Finalisasi saat masih ada draft | Sebagian Draft | **Finalisasi Kelas** | — | Alert merah; tidak ada yang berubah | Tinggi |
| GRD-13 | ⏳ 5.5 | Dosen tidak bisa finalisasi/mengembalikan | Login `DSN-X` | Cari tombol **Finalisasi Kelas** / **Kembalikan** | — | Tidak ada | Tinggi |
| GRD-14 | ⏳ 5.5 | Ubah nilai final (E16) | Nilai Final | **Input Nilai** peserta itu (dosen **dan** `AKD`) | — | "Nilai sudah difinalisasi; ajukan revisi nilai." | Tinggi |
| GRD-15 | ⏳ 5.5 | Ajukan revisi nilai (AC-17) | Nilai Final C | **Ajukan Revisi** | Skor 78, alasan ≥ 20 karakter | Revisi berstatus Menunggu; alasan < 20 karakter ditolak; tombol ajukan nonaktif selama masih ada revisi menunggu | Tinggi |
| GRD-16 | ⏳ 5.5 | Setujui revisi | Login penyetuju | Daftar revisi › **Setujui** | — | Nilai menjadi B; riwayat nilai menampilkan C → B, pelaku, alasan; mahasiswa dinotifikasi | Tinggi |
| GRD-17 | ⏳ 5.5 | Tolak revisi | — | **Tolak** + alasan | — | Nilai tetap C; pengaju dinotifikasi | Sedang |
| GRD-18 | ⏳ 5.5 | Pengaju tidak bisa menyetujui sendiri | Login `DSN-X` | Buka revisi miliknya | — | Tombol **Setujui** tidak ada | Tinggi |
| GRD-19 | ⏳ 5.5 | Riwayat nilai | Nilai pernah berubah | **Riwayat** pada baris nilai | — | Daftar perubahan: lama → baru, pelaku, waktu, alasan | Sedang |

---

## 16. Nilai, KHS & Transkrip (Portal)

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| TRN-01 | ✅ | Halaman Nilai | Login mahasiswa dengan nilai | `Akademik › Nilai` | — | Kartu per periode "Semester N — {periode}" berisi nilai per MK | Sedang |
| TRN-02 | ✅ | KHS per semester | — | `Akademik › KHS` → dropdown "Pilih semester" | — | Rincian nilai & IP semester terpilih | Sedang |
| TRN-03 | ✅ | MK ulang tidak dihitung ganda di IPK | Mahasiswa uji: Basis Data D (Ganjil), B (Genap), Algoritma B (Ganjil) | `Akademik › Transkrip` | — | IPK 3,00 dan total 6 SKS (bukan 2,33 / 9 SKS); kedua percobaan Basis Data tetap terlihat di periodenya | Tinggi |
| TRN-04 | ⏳ 6.1 | Transkrip dengan SP (AC-18) | `M1`: Basis Data D (Ganjil), Algoritma B (Ganjil), Basis Data B (SP, final) | `Akademik › Transkrip` | — | Kartu "2026/2027 Pendek" berisi Basis Data B bertanda "U"; Basis Data D di Ganjil diberi keterangan tidak dihitung ("diulang di 2026/2027 Pendek"); IPK 3,00; total 6 SKS | Tinggi |
| TRN-05 | ⏳ 6.1 | Kebijakan nilai ulang | Basis Data SP mendapat E | Lihat transkrip saat kebijakan "nilai tertinggi", lalu "nilai terakhir" | — | Tertinggi: IPK memakai D; terakhir: memakai E | Tinggi |
| TRN-06 | ⏳ 6.1 | Nilai SP belum final tidak tampil | Nilai SP Disubmit | Buka Nilai, KHS, Transkrip | — | Nilai SP tidak tampil / "-"; IPK tidak memasukkannya | Tinggi |
| TRN-07 | ⏳ 6.1 | KHS Semester Pendek | Nilai SP final | KHS › pilih "2026/2027 Pendek" | — | IPS SP 3,00 dan rincian nilai SP | Sedang |
| TRN-08 | ⏳ 6.1 | Dokumen PDF | Nilai SP final | `Akademik › Dokumen` › unduh KHS SP & Transkrip | — | PDF berlabel "2026/2027 Pendek", tanda "U", keterangan percobaan yang tidak dihitung | Sedang |
| TRN-09 | ⏳ 1.2 | Batas SKS setelah SP (AC-19) | `M5`, `M6`, `M7` (Persiapan Data); periode Ganjil 2027/2028 dibuka | Login masing-masing, buka KRS Ganjil 2027/2028, lihat Beban SKS | — | `M5` "/ 18 SKS"; `M6` "/ 18 SKS" (atau "/ 24 SKS" bila pengaturan "IP SP ikut menentukan batas SKS" aktif); `M7` "/ 24 SKS" | Tinggi |

---

## 17. Dashboard & Laporan

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Input | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|---|
| RPT-01 | ✅ | Dashboard default tidak berubah | SP ada | Login `AKD` → Dashboard | — | Angka tetap untuk periode berjalan (Genap), bukan SP | Sedang |
| RPT-02 | ⏳ 6.2 | Ringkasan SP | Data SP campuran | Periode Akademik › SP (tab ringkasan) | — | Kartu: mahasiswa terdaftar, disetujui/terdaftar, menunggu (persetujuan/bayar), lunas/belum lunas, kelas/dosen — angka cocok dengan hitungan manual di tab Kelas, Pendaftaran, Pembayaran | Tinggi |
| RPT-03 | ⏳ 6.2 | Kartu "Perlu tindakan" | SP Pendaftaran Ditutup, lalu Penilaian | Lihat ringkasan | — | Ditutup: kelas di bawah minimum, kelas hampir penuh, kelas tanpa dosen/jadwal. Penilaian: kelas belum submit/belum final. Tautan **lihat** membuka daftar terkait | Sedang |
| RPT-04 | ⏳ 6.3 | Laporan SP | Login akun dengan izin laporan | `Laporan` › pilih jenis laporan SP (pendaftaran, mata kuliah, nilai, keuangan, ringkasan) + periode SP | — | Tabel dengan kolom sesuai [frontend-dan-mobile.md §4](./frontend-dan-mobile.md#4-laporan) | Sedang |
| RPT-05 | ⏳ 6.3 | Ekspor | — | **Ekspor CSV**, **Ekspor PDF** | — | Berkas terunduh; CSV dibuka di Excel tanpa huruf rusak; ekspor tercatat di Audit Log | Sedang |
| RPT-06 | ⏳ 6.3 | Laporan tanpa memilih periode | — | Pilih jenis SP tanpa periode | — | Diminta memilih periode | Rendah |
| RPT-07 | ⏳ 6.3 | Hak akses laporan | Login `DSN-X`, `MHS` | Ketik `/laporan` | — | "Anda tidak memiliki akses ke halaman ini" | Tinggi |

---

## 18. Notifikasi

Periksa di `Notifikasi` (portal mahasiswa) atau ikon lonceng (pengguna lain). Aksi yang gagal (pesan merah) **tidak boleh** menghasilkan notifikasi.

| ID | Status | Kejadian | Penerima | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|
| NTF-01 | ✅ | Mahasiswa mengajukan KRS | Mahasiswa & dosen wali | Masing-masing satu notifikasi | Sedang |
| NTF-02 | ✅ | KRS disetujui / ditolak | Mahasiswa | Notifikasi; penolakan memuat alasan; klik notifikasi membuka halaman KRS | Sedang |
| NTF-03 | ⏳ 6.4 | Pendaftaran SP dibuka (AC-03) | Mahasiswa aktif di prodi yang punya kelas SP | Mahasiswa prodi lain & mahasiswa cuti tidak menerima | Sedang |
| NTF-04 | ⏳ 6.4 | Tagihan terbit | Mahasiswa | Memuat jumlah & jatuh tempo; tetap terkirim walau mahasiswa mematikan notifikasi di Pengaturan (wajib) | Tinggi |
| NTF-05 | ⏳ 6.4 | Pembayaran lunas / kedaluwarsa | Mahasiswa | Wajib | Tinggi |
| NTF-06 | ⏳ 6.4 | Kelas dibatalkan | Peserta & dosen | Wajib, memuat alasan | Tinggi |
| NTF-07 | ⏳ 6.4 | Jadwal kelas berubah | Peserta & dosen | Notifikasi diterima | Sedang |
| NTF-08 | ⏳ 6.4 | Dosen ditetapkan ke kelas SP | Dosen (punya akun) | Notifikasi diterima | Sedang |
| NTF-09 | ⏳ 6.4 | Nilai SP dirilis (status Selesai) / revisi nilai | Mahasiswa | Notifikasi diterima | Sedang |
| NTF-10 | ⏳ 6.4 | Isi notifikasi | Semua | Nama, periode, MK, jumlah, tanggal terisi — tidak ada teks mentah seperti `{student_name}` | Sedang |

---

## 19. Audit Log

Halaman `Pengaturan › Audit Log` (login `AUD` atau `OWN`): filter **Aksi** dan **Tipe Entitas**, klik baris untuk "Detail Audit Log" (nilai lama/baru & alasan).

| ID | Status | Kejadian | Yang dicari di Audit Log | Prioritas |
|---|---|---|---|---|
| AUD-01 | ✅ | Ganti dosen / jadwal kelas (JDW-01) | Aksi `updated`, entitas kelas, pengguna `AKD`; detail berisi dosen & jadwal sebelum/sesudah | Sedang |
| AUD-02 | ✅ | Paksa bentrok dosen (JDW-13) | Aksi `forced_override`; detail berisi daftar bentrok dan alasan yang diketik | Tinggi |
| AUD-03 | ⏳ 1.5 | Buat/ubah/hapus SP, ubah status | Aksi `created`/`updated`/`deleted`; status lama → baru; alasan paksa | Tinggi |
| AUD-04 | ⏳ 2.4 | Buka/ubah/batalkan kelas | Kapasitas lama → baru; alasan pembatalan | Sedang |
| AUD-05 | ⏳ 3.4 | Override aturan KRS | Entri KRS baru + alasan override | Tinggi |
| AUD-06 | ⏳ 4.5 | Catat pembayaran / batalkan tagihan | Pembayaran baru; status & terbayar tagihan lama → baru | Tinggi |
| AUD-07 | ⏳ 5.5 | Submit/kembalikan/finalisasi/revisi nilai | Nilai & status lama → baru, pelaku, alasan | Tinggi |
| AUD-08 | ⏳ 6.3 | Ekspor laporan | Aksi `exported` | Rendah |
| AUD-09 | ✅ | Log tidak bisa diubah | Buka detail entri mana pun: tidak ada tombol ubah/hapus | Sedang |

> Event linimasa di tabel `activity_logs` (mis. `LECTURER_ASSIGNED`, `CLASS_CANCELLED`) belum punya halaman, sehingga tidak diuji dari frontend.

---

## 20. Keamanan dari Sisi Halaman

| ID | Status | Skenario | Prasyarat | Langkah di halaman | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|---|
| SEC-01 | ✅ | URL halaman admin tanpa izin | Login `MHS`, `DSN-X`, `KEU` bergantian | Ketik URL halaman yang menunya tidak tampil untuk akun itu (`/akademik/krs`, `/pengaturan/feature-flags`, `/keuangan/tagihan`, …) | "Anda tidak memiliki akses ke halaman ini" | Tinggi |
| SEC-02 | ✅ | Data universitas lain lewat URL (AC-21) | Login `akademik@itmandala.test`, salin URL detail kelas/ujian/tagihan; logout | Login `AKD` (UND), tempel URL itu | Alert "Data tidak ditemukan."; tidak ada data universitas B yang tampil | Tinggi |
| SEC-03 | ✅ | Tombol tersembunyi sesuai peran | Login `DSN-X`, `MHS`, `KEU` | Buka halaman yang sama dengan `AKD` | Tombol **Ubah** (Dosen & Jadwal), **Tambah KRS**, **Batalkan** tidak muncul untuk akun yang tidak berhak | Tinggi |
| SEC-04 | ✅ | Logout | Login, buka halaman KRS | Logout → tombol Back di browser | Dialihkan ke halaman login; data tidak tampil | Sedang |
| SEC-05 | ⏳ 1.5 | Periode SP Draft tidak terlihat | SP Draft | Login `MHS`, cari SP di semua halaman portal; tempel URL detail SP dari akun admin | Tidak muncul; URL → "Data tidak ditemukan." | Tinggi |
| SEC-06 | ⏳ 3.4 | KRS mahasiswa lain | Dua akun mahasiswa | Login `M1`, cari cara (menu, URL, pilihan) untuk melihat/mengubah KRS, nilai, atau tagihan `M2` | Tidak ada; hanya data milik sendiri yang tampil | Tinggi |

---

## 21. Tampilan & Kenyamanan

| ID | Status | Skenario | Langkah | Hasil yang diharapkan | Prioritas |
|---|---|---|---|---|---|
| UX-01 | ✅ | Memuat data | Buka halaman Kelas dan Jadwal / KRS dengan koneksi lambat | Tampil "Memuat..." atau kerangka (skeleton), bukan halaman kosong | Rendah |
| UX-02 | ✅ | Data kosong | Buka halaman tanpa data | Kalimat yang jelas: "Belum ada kelas.", "Belum ada KRS untuk semester ini.", "Belum ada nilai." | Rendah |
| UX-03 | ✅ | Gagal memuat | Putuskan koneksi internet, buka halaman portal | Alert merah + tombol coba lagi | Rendah |
| UX-04 | ✅ | Layar ponsel | Buka KRS portal & modal Dosen & Jadwal di ponsel atau jendela sempit (< 768 px) | Tabel bisa digulir horizontal; kartu menumpuk; tombol utama terlihat; tidak ada geser horizontal di level halaman | Sedang |
| UX-05 | ✅ | Pesan mudah dipahami | Picu penolakan (kelas penuh, bentrok) | Pesan bahasa Indonesia yang bisa dipahami mahasiswa, bukan kode teknis | Sedang |
| UX-06 | ✅ | Alert bisa ditutup | Klik tanda silang pada Alert | Alert hilang | Rendah |
| UX-07 | ⏳ 1.5 | Badge status periode | Lihat daftar Periode Akademik | Setiap status punya warna berbeda dan konsisten di semua halaman | Rendah |

---

## 22. Regresi Semester Reguler

Jalankan ulang setelah setiap tahap SP selesai. Fitur reguler harus tetap berjalan tanpa memilih SP.

| ID | Status | Area | Yang harus tetap | Perubahan yang disengaja | Prioritas |
|---|---|---|---|---|---|
| REG-01 | ✅ | KRS mahasiswa (KRS-01…17) | Semua langkah berjalan tanpa memilih periode | — | Tinggi |
| REG-02 | ✅ | KRS admin (ADM-01…05) | Tambah, batalkan, kelas penuh, duplikat | Mahasiswa tidak aktif & MK sama di dua kelas ditolak (⏳ 3.2) | Tinggi |
| REG-03 | ✅ | Label periode | "2026/2027 Ganjil", "2026/2027 Genap" | + "… Pendek" | Sedang |
| REG-04 | ✅ | Penilaian (GRD-01…04) | Input nilai, huruf otomatis/manual, langsung tampil di portal | Nilai final tidak bisa diubah langsung (⏳ 5.1) | Tinggi |
| REG-05 | ✅ | Absensi (LRN-01) | Rekam kehadiran per pertemuan | Hanya kelas sendiri bila flag kepemilikan aktif | Tinggi |
| REG-06 | ✅ | Ujian | Buat, bank soal, acak, publish, link/QR, kerjakan, rekap, unduh | Periode arsip menolak perubahan (⏳ 5.4) | Tinggi |
| REG-07 | ✅ | Nilai, KHS, Transkrip (TRN-01…03) | Tampilan & IPK sama | — | Tinggi |
| REG-08 | ✅ | Dashboard | Periode berjalan sebagai default | — | Sedang |
| REG-09 | ✅ | Batas SKS reguler | "/ 18", "/ 20", "/ 22", "/ 24 SKS" sesuai IP semester reguler terakhir | SP tidak dihitung sebagai semester sebelumnya (⏳ 1.2) | Tinggi |
| REG-10 | ✅ | Jadwal & dosen (JDW-01…20) | Sama | — | Tinggi |

---

## 23. Skenario End-to-End

Empat kombinasi, dijalankan dari halaman dengan beberapa jendela browser (satu per peran, pakai mode penyamaran atau profil browser terpisah). Semua ⏳ sampai Fase 6 selesai.

| ID | Pengaturan |
|---|---|
| E2E-01 | Persetujuan otomatis, biaya Rp0 |
| E2E-02 | Persetujuan dosen wali, biaya Rp0 |
| E2E-03 | Persetujuan otomatis, biaya Rp150.000/SKS |
| E2E-04 | Persetujuan dosen wali, biaya Rp150.000/SKS (alur terlengkap) |

**Langkah E2E-04** (lewati langkah persetujuan/pembayaran sesuai kombinasi):

| # | Peran | Halaman & aksi | Yang dicek di layar |
|---|---|---|---|
| 1 | `OWN` | Feature Flag: aktifkan "Semester Pendek" & "Kepemilikan Kelas Dosen"; Pengaturan Akademik: isi kebijakan SP | Badge "Diatur universitas"; pengaturan tersimpan |
| 2 | `AKD` | Periode Akademik › **Tambah Periode** SP 2026/2027 | Status "Draft"; `MHS` belum melihat SP |
| 3 | `AKD` | Ubah Status → Direncanakan; tab Kelas › **Buka Kelas** Basis Data SP-A; **Ubah** Dosen & Jadwal (`DSN-X`, Senin 08:00–10:30 R.301); atur periode KRS | Kelas tampil dengan dosen & jadwal |
| 4 | `AKD` | Ubah Status → Pendaftaran Dibuka | `M1` menerima notifikasi pendaftaran dibuka |
| 5 | `M1` | KRS periode SP › Basis Data: badge "Mengulang (nilai D)" › **Pilih Kelas** › **Ajukan KRS** › **Ya, Ajukan** | Status "Menunggu Persetujuan" |
| 6 | `WALI` | Persetujuan KRS › pengajuan `M1` › **Setujui** | "KRS disetujui." |
| 7 | `M1` | KRS & Tagihan | "Menunggu pembayaran", tagihan Rp450.000 |
| 8 | `KEU` | Tagihan › detail › **Catat Pembayaran** Rp450.000 | Tagihan "Lunas"; KRS `M1` "Terdaftar"; notifikasi pembayaran |
| 9 | `AKD` | Ubah Status → Pendaftaran Ditutup → Berlangsung | Pilihan kelas yang tak diajukan dilepas; `M1` melihat kelas SP di Jadwal |
| 10 | `DSN-X` | Absensi › **Rekam Kehadiran** 8 pertemuan; Ujian › **Buat Ujian**, **Publish Ujian** | Hanya kelas SP miliknya yang bisa dipilih |
| 11 | `M1` | Ujian › **Mulai Ujian** → kerjakan → kumpulkan | Hasil ujian tampil |
| 12 | `AKD` | Ubah Status → Penilaian | `DSN-X` menerima pengingat nilai |
| 13 | `DSN-X` | Penilaian › **Input Nilai** B › **Submit Nilai Kelas** | Status "Disubmit"; `M1` belum melihat nilai |
| 14 | `AKD` | **Finalisasi Kelas** → Ubah Status → Selesai | Nilai "Final"; `M1` menerima notifikasi nilai dirilis |
| 15 | `M1` | Nilai, KHS, Transkrip | Basis Data B bertanda "U"; IPK memakai B; total SKS tidak ganda |
| 16 | `AKD` | Ubah Status → Diarsipkan; coba ubah jadwal, KRS, nilai | Semua perubahan ditolak; data tetap bisa dilihat |
| 17 | `AUD` | Audit Log, filter entitas terkait | Semua perubahan langkah 2–16 tercatat dengan pengguna yang benar |

---

## 24. Ringkasan

### 24.1 Bisa diuji dari halaman sekarang (✅)

Diuji pada periode reguler — halaman yang sama nantinya dipakai SP:

| Area | Kasus |
|---|---|
| Navigasi & hak akses menu | NAV-01…04, NAV-09, APV-10, SEC-01…04 |
| Feature flag per universitas | FLG-01…08 |
| Kelas dan Jadwal, Dosen & Jadwal (bentrok, paksa, peringatan peserta) | KLS-01…04, JDW-01…20, AUD-01, AUD-02, AUD-09 |
| KRS mahasiswa & tampilan kelayakan | KRS-01…17, ELG-01…07 |
| KRS admin | ADM-01…05 |
| Kepemilikan kelas dosen | OWN-01…09 |
| Absensi, ujian, peserta belum disetujui | LRN-01…05, PRT-01, PRT-05 |
| Penilaian & transkrip | GRD-01…04, TRN-01…03 |
| Tagihan (baca saja), dashboard, notifikasi KRS | PAY-01, RPT-01, NTF-01…02 |
| Tampilan & regresi | UX-01…06, REG-01…10 |

### 24.2 Belum bisa diuji dari halaman (🧩)

Fitur ada di backend, halamannya belum: NAV-05, NAV-06, APV-01…07, LRN-10, serta penyiapan periode KRS, prasyarat MK, dan dosen wali (Temuan T-1…T-4). Selama halaman Persetujuan KRS belum ada, alur KRS lengkap (pengajuan → persetujuan → terdaftar) hanya bisa diuji bila developer membantu langkah persetujuannya.

### 24.3 Urutan mengikuti gerbang rilis

| Gerbang ([tahapan-pengembangan.md §3](./tahapan-pengembangan.md#3-peta-tahapan--gerbang-rilis)) | Kasus yang dibuka |
|---|---|
| **G1** Fondasi aman | OWN (flag aktif), TRM-14…15, TRN-09 |
| **G2** SP bisa disiapkan | NAV-07, FLG-09, SET, TRM, STS-01…06, KLS-05…13, JDW-21, SEC-05 |
| **G3** SP gratis end-to-end | KRS-18…23, ELG-08…20, APV (semua), ADM-06…11, LRN-11, PRT-02…07, STS-09, **E2E-01, E2E-02** |
| **G4** SP berbayar end-to-end | PAY-02…20, KRS-24, KRS-26…27, ADM-12, **E2E-03, E2E-04** sampai langkah 9 |
| **G5** Siklus akademik penuh | GRD-05…19, LRN-06…09, TRN-04…08, STS-07…08, STS-10…11 |
| **G6** Go-live pilot | RPT-02…07, NTF-03…10, AUD-03…08, SEC-06, UX-07, **E2E-01…04 lengkap**, REG penuh |

Semua kasus prioritas **Tinggi** di sebuah gerbang wajib lulus sebelum lanjut ke gerbang berikutnya. Kasus **Sedang**/**Rendah** yang gagal boleh dicatat sebagai bug yang diketahui, asalkan tidak menyangkut nilai, uang, atau data universitas lain.
