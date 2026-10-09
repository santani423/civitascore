# Semester Pendek — Langkah-Langkah Pengujian lewat Frontend

> Bagian dari [paket rancangan Semester Pendek](./README.md).
> **Dibuat:** 2026-10-08 · **Basis kode:** `main` @ `7260310` (R-02)
>
> Panduan ini berisi **urutan kerja** untuk penguji: apa yang disiapkan, akun mana yang dipakai, menu dan tombol mana yang diklik, dan apa yang harus terlihat di layar — dari awal sampai akhir. Daftar kasus lengkap beserta prioritasnya ada di [black-box-testing.md](./black-box-testing.md); kolom **Ref** di setiap langkah menunjuk ID kasus di dokumen itu.
>
> Semua langkah dilakukan dari aplikasi web sebagai pengguna biasa. Langkah yang belum bisa dilakukan dari halaman ditandai **[Developer]** dan caranya ada di [Lampiran A](#lampiran-a--bantuan-developer).

## Daftar Isi

1. [Gambaran](#1-gambaran)
2. [Persiapan Lingkungan](#2-persiapan-lingkungan)
3. [Bagian A — Pengujian yang Bisa Dijalankan Sekarang](#3-bagian-a--pengujian-yang-bisa-dijalankan-sekarang)
4. [Bagian B — Alur Lengkap Satu Periode SP](#4-bagian-b--alur-lengkap-satu-periode-sp)
5. [Setelah Pengujian](#5-setelah-pengujian)
- [Lampiran A — Bantuan Developer](#lampiran-a--bantuan-developer)

---

## 1. Gambaran

Fitur Semester Pendek (SP) dibangun bertahap. Karena itu pengujian dibagi dua:

| Bagian | Kapan dijalankan | Isi | Perkiraan waktu |
|---|---|---|---|
| **A** | **Sekarang** | Bagian aplikasi yang sudah jadi dan akan dipakai SP: feature flag, dosen & jadwal kelas (deteksi bentrok), KRS mahasiswa, persetujuan KRS, kepemilikan kelas dosen, absensi, nilai, ujian, transkrip. Diuji di periode berjalan **Ganjil 2026/2027**. | 2–3 hari |
| **B** | Setelah setiap fase SP selesai (lihat [tahapan-pengembangan.md §3](./tahapan-pengembangan.md#3-peta-tahapan--gerbang-rilis)) | Satu siklus SP penuh: membuat periode SP → membuka kelas → pendaftaran → persetujuan → pembayaran → perkuliahan → nilai → transkrip → arsip. Nama menu/tombol mengikuti rancangan dan bisa berubah. | 3–4 hari per putaran |

Cara membaca tabel langkah:

- **Langkah** — apa yang dilakukan. `Akademik › KRS` = klik menu Akademik di sidebar lalu submenu KRS. Teks **tebal** = tombol yang diklik.
- **Yang harus terlihat** — hasil yang diharapkan di layar. Teks dalam tanda kutip harus tampil persis.
- **Ref** — ID kasus di [black-box-testing.md](./black-box-testing.md).
- Bila hasilnya berbeda, catat sebagai **Gagal**, ambil tangkapan layar, lalu lanjutkan ke langkah berikutnya kecuali langkah itu menjadi prasyarat langkah setelahnya (tandai langkah-langkah yang terhalang sebagai **Terblokir**).

---

## 2. Persiapan Lingkungan

### 2.1 Menjalankan aplikasi

> ⚠️ Pengujian ini **membuat data** (mahasiswa, KRS, nilai, ujian) dan mengubah feature flag. Jalankan di lingkungan **lokal atau staging**, jangan di server produksi.

1. Backend: `php artisan migrate:fresh --seed`, lalu `php artisan serve` (API di `http://localhost:8000/api/v1`).
2. Frontend: buka `frontend/.env`. Saat ini `VITE_API_BASE_URL` mengarah ke `http://beoneatma.santani.dev/api/v1`. Untuk pengujian lokal, ubah menjadi `http://localhost:8000/api/v1`, lalu jalankan `npm run dev` dan buka `http://localhost:5173`.
3. Pastikan frontend dan backend memakai data yang sama dengan developer yang membantu langkah **[Developer]**.

### 2.2 Browser

Banyak langkah melibatkan beberapa peran sekaligus (mahasiswa mengajukan, dosen wali menyetujui, mahasiswa memeriksa). Siapkan **satu profil browser per peran** (atau satu jendela biasa + beberapa jendela penyamaran) supaya semuanya tetap login bersamaan. Beri nama jendela/profil sesuai kode akun di bawah.

### 2.3 Akun

Password akun demo: `password`. Akun yang dibuat seeder untuk mahasiswa/dosen juga ber-password `password`, tetapi **wajib ganti password saat login pertama** (halaman "Ubah Password" muncul otomatis) — catat password barunya.

| Kode | Peran | Akun |
|---|---|---|
| `AKD` | Bagian Akademik | `akademik@undigital.test` |
| `DSN-X` | Dosen pengampu | `dosen@undigital.test` |
| `DSN-Y` | Dosen pengampu kedua | Satu dosen dari `Dosen` (lihat Sesi A0), login dengan email dosen itu |
| `WALI` | Dosen wali | `dosenpa@undigital.test` |
| `M1`…`M4` | Mahasiswa uji | Dibuat di Sesi A0 |
| `KEU` | Bagian Keuangan | `keuangan@undigital.test` |
| `OWN` | Owner universitas | `owner@undigital.test` |
| `AUD` | Auditor | `auditor@undigital.test` |
| `AKD-B`, `OWN-B` | Universitas lain (uji isolasi) | `akademik@itmandala.test`, `owner@itmandala.test` |
| `SA` | Super Admin | `superadmin@civitasone.local` / `ChangeMe123!` |
| `STF` | Tanpa hak akses | `staff@civitasone.test` |

### 2.4 Kondisi data demo yang perlu diketahui

| Kondisi | Akibat untuk pengujian |
|---|---|
| Periode berjalan: **Ganjil 2026/2027** (1 Agu 2026 – 31 Jan 2027). Periode sebelumnya Genap 2025/2026 berakhir 31 Jul 2026 | Semua langkah Bagian A memakai Ganjil 2026/2027 |
| Kelas demo **belum punya dosen pengampu maupun jadwal** | Diisi di Sesi A3 |
| Periode KRS Ganjil 2026/2027 **belum diatur** | Halaman KRS mahasiswa tertutup sampai developer membukanya (A0) |
| Setiap mahasiswa aktif di data demo **sudah punya KRS "Terdaftar"** yang dibuat seeder | Halaman KRS mereka terkunci (status "Disetujui", "KRS semester ini sudah disetujui dan dikunci. Hubungi Bagian Akademik untuk perubahan KRS."). Karena itu uji KRS memakai **mahasiswa baru** `M1`…`M4` |
| Akun `mahasiswa@undigital.test` belum tentu tertaut ke data mahasiswa | Bila portal menampilkan "Akun ini tidak tertaut ke data mahasiswa.", jangan dipakai — pakai `M1`…`M4` |
| Belum ada halaman Persetujuan KRS, pengaturan periode KRS, prasyarat MK, dan dosen wali | Bagian itu dibantu developer (Lampiran A) |

### 2.5 Mencatat hasil

Salin tabel ini ke spreadsheet, satu baris per langkah:

| Sesi | No | Ref | Hasil (Lulus / Gagal / Terblokir) | Catatan & nama berkas tangkapan layar | Penguji | Tanggal |
|---|---|---|---|---|---|---|

Format laporan bug untuk setiap langkah **Gagal**:

```
Judul      : [Ref] ringkasan singkat, mis. "[JDW-08] Bentrok ruangan tidak terdeteksi bila huruf kecil"
Sesi/No    : A3 / 9
Akun       : akademik@undigital.test
Halaman    : http://localhost:5173/akademik/kelas-jadwal/<id>
Langkah    : 1) … 2) … 3) …
Diharapkan : (salin dari kolom "Yang harus terlihat")
Aktual     : …
Bukti      : tangkapan layar / rekaman
Browser    : Chrome 1xx, lebar layar …
Prioritas  : (salin dari black-box-testing.md)
```

---

## 3. Bagian A — Pengujian yang Bisa Dijalankan Sekarang

Jalankan sesi **berurutan** — data yang dibuat di sesi awal dipakai sesi berikutnya.

### Sesi A0 — Menyiapkan data

| No | Pelaku | Langkah | Yang harus terlihat |
|---|---|---|---|
| 1 | `AKD` | `Akademik › Kelas dan Jadwal`. Pilih **satu program studi** (sebut `P`) yang punya minimal 5 kelas aktif di Ganjil 2026/2027 | Daftar kelas dengan kolom Dosen Pengampu "Belum ditetapkan" |
| 2 | `AKD` | Catat 5 kelas prodi `P` dengan mata kuliah **berbeda**: `K-1` (MK A), `K-2` (MK B), `K-3` (MK C), `K-4` (MK D), `K-5` (MK E). Catat juga satu MK lain prodi `P` yang **tidak** punya kelas di daftar ini sebagai `MK-PRA` | — |
| 3 | `AKD` | `Dosen`: pilih satu dosen aktif yang punya email (bukan `dosen@undigital.test`) sebagai `DSN-Y`, catat nama & emailnya | — |
| 4 | `AKD` | `Mahasiswa` › **Tambah Mahasiswa** — buat `M1`: Program Studi `P`, NIM `SP-UJI-01`, Nama "Uji SP Satu", Email `uji.sp1@undigital.test`, Angkatan 2024, Status **Aktif**, Tanggal Terdaftar hari ini | Mahasiswa baru muncul di daftar |
| 5 | `AKD` | Ulangi untuk `M2` (`SP-UJI-02`, `uji.sp2@undigital.test`, Aktif), `M3` (`SP-UJI-03`, `uji.sp3@undigital.test`, Status **Cuti**), `M4` (`SP-UJI-04`, `uji.sp4@undigital.test`, Aktif) | Keempatnya muncul; `M3` berstatus Cuti |
| 6 | Developer | Buat akun login untuk mahasiswa baru (Lampiran A.1) | — |
| 7 | Developer | Buka periode KRS Ganjil 2026/2027: mulai 7 hari lalu, selesai 14 hari lagi (A.3) | — |
| 8 | Developer | Jadikan `WALI` dosen wali `M1` dan `M2` (A.4) | — |
| 9 | Developer | Prasyarat: MK D (`K-4`) mensyaratkan `MK-PRA` minimal C (A.5) | — |
| 10 | Developer | Riwayat nilai Genap 2025/2026: `M1` MK A = **D**, `M2` MK B = **B**, `M4` MK A = **D** (A.8) | — |
| 11 | Penguji | Login `M1`…`M4` dan `DSN-Y` satu per satu dengan password `password` | Halaman "Ubah Password" muncul; setelah diganti, masuk ke Dashboard. Catat password baru |

### Sesi A1 — Hak akses menu

| No | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|
| 1 | Login `M1`, perhatikan sidebar | Menu portal (Akademik Saya, KRS, Jadwal, KHS, Nilai, Transkrip, Presensi, Dokumen, Perkuliahan, Ujian, …); tidak ada Kelas dan Jadwal, Penilaian, Pengaturan | NAV-01 |
| 2 | Sebagai `M1`, ketik `/akademik/kelas-jadwal` di address bar | "Anda tidak memiliki akses ke halaman ini" | NAV-02 |
| 3 | Login `AKD`, buka menu Akademik | Ada Kelas dan Jadwal, KRS, Persetujuan KRS; tidak ada menu portal mahasiswa | NAV-03 |
| 4 | Sebagai `AKD`, klik `Akademik › Persetujuan KRS` | **Temuan T-1:** saat ini dialihkan ke Dashboard. Catat sebagai Gagal (halaman belum ada) | NAV-05 |
| 5 | Login `DSN-X`, klik `Akademik › Kelas Saya` | **Temuan T-2:** dialihkan ke Dashboard. Catat sebagai Gagal | NAV-06 |
| 6 | Login `STF`; ketik `/akademik/krs` | Sidebar hanya menu umum; URL → "Anda tidak memiliki akses ke halaman ini" | NAV-04 |
| 7 | Login `SA`, perhatikan sidebar; lalu pilih Universitas Nusantara Digital di Tenant Switcher | Sebelum memilih: hanya menu platform. Sesudah: menu Akademik, Keuangan, dst. muncul | NAV-09 |

### Sesi A2 — Feature flag

| No | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|
| 1 | Login `OWN`, `Pengaturan › Feature Flag` | Deskripsi "Perubahan hanya berlaku untuk universitas Anda."; baris "Semester Pendek" dan "Kepemilikan Kelas Dosen" dengan Sumber "Mengikuti global (nonaktif)" | FLG-01 |
| 2 | Ketik "pendek" di kolom pencarian | Hanya baris "Semester Pendek" | FLG-08 |
| 3 | Hapus pencarian. Centang **Aktif** pada "Semester Pendek" | Tercentang; Sumber menjadi badge "Diatur universitas" + tombol **Ikuti global** | FLG-02 |
| 4 | Di jendela lain, login `OWN-B`, buka Feature Flag | "Semester Pendek" tetap "Mengikuti global (nonaktif)" | FLG-03 |
| 5 | Kembali ke `OWN`, klik **Ikuti global** | Sumber kembali "Mengikuti global (nonaktif)", checkbox kosong | FLG-04 |
| 6 | Login `SA` **tanpa** memilih universitas, buka Feature Flag | Deskripsi "Nilai global — default untuk semua universitas yang tidak mengaturnya sendiri."; tidak ada kolom Sumber | FLG-05 |
| 7 | Login akun yang punya menu Feature Flag tapi tidak boleh mengubah (bila ada); login `AKD` | Akun hanya-lihat: checkbox tidak bisa diklik. `AKD`: menu Feature Flag tidak muncul; `/pengaturan/feature-flags` → "Anda tidak memiliki akses ke halaman ini" | FLG-06 |

> "Kepemilikan Kelas Dosen" **belum** dinyalakan di sini — dinyalakan di Sesi A8 setelah semua kelas uji punya dosen.

### Sesi A3 — Dosen & jadwal kelas

Semua langkah sebagai `AKD`: `Akademik › Kelas dan Jadwal` → klik nama kelas → kartu "Dosen & Jadwal" → **Ubah**.

| No | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|
| 1 | Di daftar kelas: Dosen Pengampu = "Belum ditetapkan" → **Terapkan Filter**; lalu ketik nama MK A di Cari + Enter | Hanya kelas tanpa dosen; lalu hanya kelas MK A | KLS-02, KLS-03 |
| 2 | Buka `K-1` | "Detail Kelas" berisi Status, Program Studi, Periode Akademik, SKS, Kapasitas, Mahasiswa Terdaftar; kartu "Dosen & Jadwal" dengan "Belum ditetapkan" dan "Belum ada jadwal." | KLS-04 |
| 3 | **Ubah** → di "Cari nama, NIDN, atau email dosen..." ketik nama acak | "Tidak ada dosen aktif yang cocok dengan pencarian." | JDW-02 |
| 4 | Pilih `DSN-X`. **Tambah Jadwal**: Senin, 10:00, 10:00, R.301 → **Simpan** | "Jam selesai harus setelah jam mulai." di bawah jam selesai; modal tetap terbuka | JDW-03 |
| 5 | Kosongkan jam mulai → **Simpan** | "Isi jam mulai." | JDW-04 |
| 6 | Ubah baris menjadi Senin 08:00–10:30 R.301 → **Simpan** | Modal tertutup; kartu menampilkan nama & email `DSN-X` dan "Senin, 08:00–10:30 · R.301" | JDW-01 |
| 7 | Kembali ke daftar | Baris `K-1` menampilkan dosen dan jadwalnya | JDW-01 |
| 8 | Buka `K-2` → **Ubah** → pilih `DSN-Y`, **Tambah Jadwal** dua baris: Senin 08:00–10:00 dan Senin 09:00–11:00, ruangan kosong → **Simpan** | Alert merah; badge merah "Bentrok antarjadwal" di **kedua** baris; teks "Bentrok ruangan dan antarjadwal tidak dapat dipaksa — ubah hari, jam, atau ruangan."; tidak ada pilihan paksa | JDW-07 |
| 9 | Hapus baris kedua (ikon tempat sampah); ubah baris pertama menjadi Senin 09:00–11:00, ruangan ` r.301 ` (spasi + huruf kecil) → **Simpan** | Badge merah "Bentrok ruangan" + "Ruangan R.301 sudah dipakai kelas …"; tidak ada pilihan paksa | JDW-08 |
| 10 | Ubah jadi Senin 10:30–12:00 R.301 → **Simpan** | Tersimpan — bersinggungan tepat dengan `K-1` (selesai 10:30) tidak bentrok | JDW-10 (a) |
| 11 | **Ubah** lagi: Senin 10:00–12:00 R.301 → **Simpan** | Bentrok ruangan | JDW-10 (b) |
| 12 | Ganti hari menjadi Selasa → **Simpan** | Tersimpan | JDW-10 (c) |
| 13 | **Ubah** `K-2`: Rabu 08:00–10:00, ruangan kosong → **Simpan**. Lalu **Ubah**: tambahkan baris Senin 08:00–10:00 ruangan `online` → **Simpan** | Keduanya tersimpan (ruangan kosong/daring tidak pernah bentrok) | JDW-09 |
| 14 | **Ubah** `K-2`: hapus baris Senin; set akhir: `DSN-Y`, Rabu 08:00–10:00, R.302 → **Simpan** | Tersimpan | — |
| 15 | Buka `K-3` → **Ubah** → pilih `DSN-X`, Senin 09:00–11:00, R.303 → **Simpan** | Badge **kuning** "Bentrok dosen" + "Jadwal bentrok dengan kelas … yang juga diampu …"; muncul checkbox "Paksa simpan meski jadwal dosen bentrok" | JDW-11 |
| 16 | Ganti ruangan menjadi R.301 → **Simpan** | Badge "Bentrok dosen" **dan** "Bentrok ruangan"; checkbox paksa **tidak** muncul | JDW-14 |
| 17 | Kembalikan ruangan R.303 → **Simpan** → centang paksa → isi Alasan "singkat" | Kolom "Alasan" muncul dengan petunjuk "Dicatat di audit log bersama daftar bentroknya."; tombol berubah merah "Paksa Simpan"; setelah diklik: "Alasan minimal 10 karakter." | JDW-12 |
| 18 | Alasan: "Uji paksa bentrok dosen untuk pengujian" → **Paksa Simpan** | Modal tertutup; jadwal tersimpan dengan `DSN-X` | JDW-13 |
| 19 | **Ubah** `K-3`: ganti dosen menjadi `DSN-Y` (jadwal tetap Senin 09:00–11:00 R.303) → **Simpan** | Tersimpan tanpa bentrok. Akhir: `K-3` bentrok **waktu** dengan `K-1` (Senin) tetapi beda dosen & ruangan — dipakai Sesi A5 | JDW-17 |
| 20 | Buka `K-4` → `DSN-Y`, Kamis 08:00–10:00 R.304. Buka `K-5` → `DSN-Y`, Jumat 08:00–10:00 R.305 | Keduanya tersimpan | — |
| 21 | Buka `K-5` → **Ubah** → **Tambah Jadwal** sampai 7 baris | Tombol **Tambah Jadwal** hilang setelah baris ke-7. Klik **Batal** | JDW-05, JDW-19 |
| 22 | Muat ulang `K-5` | Jadwal tetap satu baris (Jumat); perubahan tadi tidak tersimpan | JDW-19 |
| 23 | Login `DSN-X`, buka detail `K-1` | Kartu "Dosen & Jadwal" tanpa tombol **Ubah** | JDW-20 |

> JDW-06, JDW-15, JDW-16, JDW-18 dijalankan di Sesi A9 setelah kelas punya peserta, absensi, dan nilai.

### Sesi A4 — KRS mahasiswa

Login `M1` → `Akademik › KRS`.

| No | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|
| 1 | Buka KRS | Subjudul "Semester 2026/2027 Ganjil"; kartu Status KRS, Beban SKS ("0 / x SKS"), Periode KRS (tanggal + badge "Dibuka"), "Dosen wali: {nama WALI}" | KRS-01 |
| 2 | Gulir ke "Mata Kuliah Ditawarkan" | Kelas prodi `P` dengan "Kelas …", nama dosen, jadwal, "Sisa n dari m kursi", tombol **Pilih Kelas**; kelas tanpa jadwal: "Jadwal belum ditetapkan" | KRS-02 |
| 3 | Ketik kata acak di "Cari kode atau nama mata kuliah" | "Tidak ada mata kuliah yang ditawarkan." + "Coba kata kunci lain." Hapus pencarian | KRS-05 |
| 4 | **Pilih Kelas** pada `K-1` | Alert hijau "Mata kuliah ditambahkan ke KRS."; `K-1` muncul di "Mata Kuliah Dipilih" berbadge "Draft"; kartu MK A berbadge "Dipilih"; Beban SKS bertambah; sisa kursi `K-1` berkurang 1 | KRS-03 |
| 5 | **Pilih Kelas** pada `K-2` | Ditambahkan seperti langkah 4 | KRS-03 |
| 6 | **Hapus** pada `K-2` | "Mata kuliah dihapus dari KRS."; sisa kursi `K-2` kembali | KRS-04 |
| 7 | Klik **Pilih Kelas** `K-2` dua kali dengan cepat | Hanya satu `K-2` di "Mata Kuliah Dipilih" | KRS-17 |
| 8 | **Cetak KRS** | PDF `KRS-2026-2027-Ganjil.pdf` terunduh berisi `K-1` & `K-2` | KRS-10 |
| 9 | Riwayat KRS → **Tampilkan**, lalu **Sembunyikan** | "Belum ada riwayat KRS." (mahasiswa baru); bagian tertutup lagi | KRS-11 |

### Sesi A5 — Alasan kelas tidak bisa diambil

Lanjutan sebagai `M1` (sudah memilih `K-1` & `K-2`).

| No | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|
| 1 | Lihat kartu MK A | Badge kuning "Mengulang (nilai D)" | ELG-05 |
| 2 | Lihat `K-3` (Senin 09:00–11:00) | Teks merah "Bentrok dengan: …" menyebut MK A; **Pilih Kelas** nonaktif | ELG-02 |
| 3 | Lihat kartu MK D (`K-4`) | Bagian "Prasyarat" dengan badge merah `MK-PRA`; badge "Tidak dapat diambil"; Alert kuning "Prasyarat belum terpenuhi: {kode} {nama MK-PRA}."; tombol nonaktif | ELG-03 |
| 4 | Login `M2`, buka KRS, lihat kartu MK B (`K-2`) | Badge "Tidak dapat diambil"; Alert kuning "Sudah lulus dengan nilai B." | ELG-04 |
| 5 | Login `M3` (Cuti), buka KRS | Alert "Status akademik Anda saat ini Cuti. Pengisian KRS hanya untuk mahasiswa berstatus Aktif."; tidak bisa memilih kelas | ELG-07 |
| 6 | [Developer] Sisakan **1 kursi** di `K-5` (A.7) | — | — |
| 7 | Buka KRS `M1` dan `M2` di dua jendela; muat ulang keduanya | `K-5` menampilkan "Sisa 1 dari … kursi" di kedua jendela | — |
| 8 | Jendela `M1`: **Pilih Kelas** `K-5`. Lalu jendela `M2` (jangan dimuat ulang): **Pilih Kelas** `K-5` | `M1` berhasil; `M2` mendapat Alert merah "Kelas … sudah penuh. Silakan pilih kelas lain." | KRS-16 |
| 9 | Muat ulang jendela `M2` | `K-5` menampilkan "Kelas penuh" merah; **Pilih Kelas** nonaktif | ELG-01 |
| 10 | Sebagai `M1`, pilih kelas lain prodi `P` satu per satu sampai Beban SKS mendekati batas, lalu pilih satu lagi yang melewati batas | Alert merah "SKS yang dipilih melebihi batas maksimum semester ini (…)"; kelas terakhir tidak masuk. (Bila kelas prodi `P` tidak cukup untuk mencapai batas, tandai **Terblokir**) | ELG-06 |
| 11 | **Hapus** kelas-kelas tambahan dari langkah 10 sehingga tersisa `K-1`, `K-2`, `K-5` | — | — |
| 12 | Sebagai `M2`, **Pilih Kelas** `K-1` | Ditambahkan (dipakai Sesi A6 & A9) | — |

### Sesi A6 — Pengajuan & persetujuan KRS

Halaman Persetujuan KRS belum ada (T-1), sehingga keputusan dosen wali dilakukan developer (Lampiran A.6). Penguji memeriksa hasilnya di halaman mahasiswa.

| No | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|
| 1 | `M1`: **Ajukan KRS** | Modal "Ajukan KRS ke dosen wali?" menyebut 3 mata kuliah dan total SKS-nya | KRS-06 |
| 2 | **Ya, Ajukan** | "KRS berhasil diajukan dan menunggu persetujuan dosen wali."; badge item "Menunggu Persetujuan"; Alert info "KRS sudah diajukan dan sedang menunggu persetujuan dosen wali."; tombol **Hapus** hilang, **Pilih Kelas** nonaktif; muncul **Tarik Pengajuan** | KRS-06 |
| 3 | **Tarik Pengajuan** | "Pengajuan KRS ditarik kembali. KRS dapat diubah lagi."; item kembali "Draft" dan bisa dihapus | KRS-07 |
| 4 | **Ajukan KRS** → **Ya, Ajukan** lagi | Kembali "Menunggu Persetujuan" | KRS-06 |
| 5 | Buka `Notifikasi` (portal `M1`); login `WALI`, buka ikon notifikasi | `M1` & `WALI` masing-masing menerima notifikasi pengajuan KRS | NTF-01 |
| 6 | [Developer] **Setujui** pengajuan `M1` (A.6) | — | — |
| 7 | `M1`: muat ulang KRS | Item "Terdaftar"; Alert hijau "KRS semester ini sudah disetujui dan dikunci. Hubungi Bagian Akademik untuk perubahan KRS."; kartu Status KRS menampilkan "Diputuskan … oleh …"; notifikasi persetujuan masuk | KRS-08, NTF-02 |
| 8 | `M2`: **Ajukan KRS** → **Ya, Ajukan** | "Menunggu Persetujuan" | — |
| 9 | [Developer] **Tolak** pengajuan `M2` dengan catatan "Uji tolak: tambah satu mata kuliah" (A.6) | — | — |
| 10 | `M2`: muat ulang KRS | Alert merah "KRS ditolak dosen wali" berisi "Uji tolak: tambah satu mata kuliah — perbaiki KRS Anda lalu ajukan kembali."; item kembali "Draft"; sisa kursi `K-1` **tidak** bertambah (kursi tetap dipegang); notifikasi penolakan masuk | KRS-09, NTF-02 |
| 11 | `M2`: **Ajukan KRS** → **Ya, Ajukan** (jangan disetujui) | "Menunggu Persetujuan" — `M2` dipakai sebagai peserta belum disetujui di Sesi A9 | — |
| 12 | [Developer] Ubah periode KRS sehingga baru dibuka **besok** (A.3). `M1` muat ulang KRS | Alert info "Periode KRS belum dibuka. KRS dapat diisi mulai … sampai …"; badge "Ditutup" | KRS-12 |
| 13 | [Developer] Periode KRS berakhir **kemarin**. Muat ulang | "Periode KRS sudah ditutup pada …" | KRS-13 |
| 14 | `M2`: cari tombol **Tarik Pengajuan** | Tombol tidak ada lagi karena periode sudah ditutup; pengajuan tetap "Menunggu Persetujuan" | KRS-07 (tambahan) |
| 15 | [Developer] Kosongkan periode KRS. Muat ulang | Periode KRS "Belum ditetapkan"; "Periode KRS semester ini belum dibuka oleh Bagian Akademik." | KRS-14 |
| 16 | [Developer] Buka lagi periode KRS (7 hari lalu – 14 hari lagi). Login `M4`, buka KRS dan biarkan halaman terbuka. [Developer] tutup periode (berakhir kemarin). Tanpa memuat ulang, `M4` klik **Pilih Kelas** | Alert merah "Periode KRS sudah ditutup …" — server menolak walau tampilan masih lama | KRS-15 |
| 17 | [Developer] Buka lagi periode KRS (7 hari lalu – 14 hari lagi) | — | — |

### Sesi A7 — KRS oleh Bagian Akademik

Login `AKD` → `Akademik › KRS`.

| No | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|
| 1 | Buka KRS | Kolom Mahasiswa, Mata Kuliah, Periode, Status, Nilai; KRS `M1` berstatus Terdaftar | ADM-01 |
| 2 | **Tambah KRS** → Mahasiswa `M4`, Kelas `K-1` → simpan | Modal tertutup; baris `M4` – MK A "Terdaftar" | ADM-02 |
| 3 | **Tambah KRS** → `M4`, `K-1` lagi | Alert merah di modal; tidak ada baris baru | ADM-03 |
| 4 | **Tambah KRS** → `M4`, `K-5` (penuh) | Alert merah di modal (kelas penuh) | ADM-03 |
| 5 | **Tambah KRS** → `M4`, `K-2` → simpan. Lalu **Batalkan** pada baris itu | Modal "Batalkan KRS" bertuliskan "Batalkan pendaftaran Uji SP Empat pada {MK B}?"; **Batalkan KRS** → status "Dibatalkan"; tombol **Batalkan** hanya ada di baris Terdaftar | ADM-04 |
| 6 | Login `M4`, buka KRS | Status KRS "Disetujui"; Alert "KRS semester ini sudah disetujui dan dikunci. Hubungi Bagian Akademik untuk perubahan KRS."; **Pilih Kelas** nonaktif (KRS yang diisi admin langsung dianggap final) | — |
| 7 | Login `DSN-X`, buka `Akademik › KRS` | Tidak ada tombol **Tambah KRS** dan kolom Aksi | ADM-05 |

### Sesi A8 — Kepemilikan kelas dosen

Pembagian kelas: `DSN-X` = `K-1`; `DSN-Y` = `K-2`, `K-3`, `K-4`, `K-5`.

| No | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|
| 1 | Sebelum flag menyala: login `DSN-X`, `Akademik › Kelas dan Jadwal` | Semua kelas universitas tampil (perilaku lama) | OWN-08 |
| 2 | Login `OWN-B`, nyalakan "Kepemilikan Kelas Dosen" di universitas B saja. Kembali ke `DSN-X`, muat ulang | Tetap semua kelas tampil | OWN-09 |
| 3 | Login `OWN` (UND), nyalakan "Kepemilikan Kelas Dosen" | Badge "Diatur universitas" | FLG-07 |
| 4 | `DSN-X`: muat ulang Kelas dan Jadwal | Hanya `K-1` | OWN-01 |
| 5 | Salin URL detail `K-3` dari jendela `AKD`, tempel di jendela `DSN-X` | Alert merah (tidak punya izin); data `K-3` tidak tampil | OWN-02 |
| 6 | `DSN-X`: `Akademik › Penilaian` › **Input Nilai** → buka dropdown "Mahasiswa · Mata Kuliah" | Hanya peserta `K-1` yang sudah Terdaftar (`M1`, `M4`) | OWN-03 |
| 7 | `DSN-X`: buka `Akademik › Penilaian` dan `Akademik › KRS` | Hanya baris kelas `K-1` | OWN-04 |
| 8 | `DSN-X`: `Akademik › Absensi` › **Rekam Kehadiran** → dropdown Kelas | Hanya `K-1` | OWN-05 |
| 9 | `DSN-X`: `Akademik › Ujian` › **Buat Ujian** → dropdown Kelas | Hanya `K-1` | OWN-06 |
| 10 | `AKD`: buka halaman-halaman yang sama | Semua kelas tampil | OWN-07 |

### Sesi A9 — Absensi, nilai, dan ujian

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `DSN-X` | `Akademik › Absensi` › **Rekam Kehadiran** → Kelas `K-1`, Pertemuan ke- 1, Tanggal hari ini | Daftar peserta: `M1` dan `M4`. **`M2` tidak ada** (KRS-nya belum disetujui) | LRN-02 (a) |
| 2 | `DSN-X` | Atur `M1` Hadir, `M4` Izin → **Simpan Kehadiran** | Tabel Absensi menampilkan dua baris "Ke-1" | LRN-01 |
| 3 | `DSN-Y` | **Rekam Kehadiran** → pilih kelas yang belum punya peserta Terdaftar (mis. `K-3`) | "Belum ada mahasiswa terdaftar aktif di kelas ini."; **Simpan Kehadiran** nonaktif | LRN-04 |
| 4 | `DSN-X` | `Akademik › Penilaian` › **Input Nilai** → buka pilihan | `M2` tidak ada di pilihan | LRN-02 (b) |
| 5 | `DSN-X` | Pilih `M4` · MK A → Skor `101` → simpan | Pesan validasi di kolom Skor; tidak tersimpan | GRD-03 |
| 6 | `DSN-X` | Skor `78`, Nilai Huruf kosong → simpan | Baris `M4` MK A: Nilai Huruf B, Skor 78 | GRD-01 |
| 7 | `DSN-X` | **Input Nilai** `M1` · MK A: Skor 78, Nilai Huruf A | Nilai Huruf A (pilihan manual dipakai) | GRD-02 |
| 8 | `AKD` | `Akademik › KRS` → **Batalkan** pada baris `M4` – MK A | Alert merah "KRS yang sudah dinilai tidak dapat dibatalkan." | — |
| 9 | `DSN-X` | `Akademik › Ujian` › **Buat Ujian**: Kelas `K-1`, Judul "Kuis Uji SP", Durasi 30, Waktu Mulai sekarang, Waktu Selesai besok, Jumlah Soal per Peserta 1 → **Buat Ujian** | Ujian baru di daftar | LRN-05 |
| 10 | `DSN-X` | Buka ujian → **Tambah Soal** (satu soal pilihan ganda) → **Publish Ujian** → kartu "Akses Ujian" › **Generate Link** → **Copy Link** | Status ujian berubah terpublikasi; link tersalin ("Tersalin!") | LRN-05 |
| 11 | `M1` | `Ujian` | "Kuis Uji SP" tampil dengan tombol **Mulai Ujian**; kerjakan dan kumpulkan → hasil tampil | PRT-05 |
| 12 | `M2` | `Ujian` | "Kuis Uji SP" **tidak** tampil | LRN-02 (c) |
| 13 | Siapa saja | Buka link ujian di jendela penyamaran → isi NIM `SP-UJI-02` (`M2`) | "Anda tidak terdaftar pada kelas untuk ujian ini." | LRN-03 |
| 14 | `AKD` | Detail `K-1` → **Ubah** → ganti dosen ke `DSN-Y` (jadwal tetap) → **Simpan**; lalu kembalikan ke `DSN-X` | Tidak ada Alert kuning; absensi & nilai `K-1` tetap ada di halaman Absensi dan Penilaian | JDW-17, JDW-18 |
| 15 | `AKD` | Detail `K-1` → **Ubah** → pindahkan jadwal ke Jumat 08:00–10:00 R.301 → **Simpan** (`M1` juga punya `K-5` Jumat 08:00–10:00) | Tersimpan; Alert kuning "Jadwal baru beririsan dengan kelas lain yang diambil peserta" berisi "Uji SP Satu (SP-UJI-01) — {MK E} …"; bisa ditutup | JDW-16 |
| 16 | `AKD` | Kembalikan `K-1` ke Senin 08:00–10:30 R.301 | Tersimpan | — |
| 17 | `AKD` | [Developer] nonaktifkan sementara satu kelas lain, beri jadwal Senin 08:00–10:30 R.301; lalu buat jadwal yang sama di kelas aktif lain | Tersimpan tanpa bentrok (kelas nonaktif diabaikan). Opsional — lewati bila developer tidak tersedia | JDW-15 |
| 18 | `AKD` | Pilih kelas tanpa peserta, **Ubah** → hapus semua baris jadwal → **Simpan** | Modal menampilkan "Belum ada jadwal." sebelum simpan; kartu "Belum ada jadwal." sesudahnya | JDW-06 |

### Sesi A10 — Nilai, KHS, dan transkrip

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `M4` | `Akademik › Nilai` | Kartu Genap 2025/2026 (MK A = D) dan Ganjil 2026/2027 (MK A = B) | TRN-01 |
| 2 | `M4` | `Akademik › KHS` → "Pilih semester" Genap 2025/2026, lalu Ganjil 2026/2027 | IP masing-masing semester: 1,00 lalu 3,00 | TRN-02 |
| 3 | `M4` | `Akademik › Transkrip` | Kedua percobaan MK A terlihat di periodenya; **IPK 3,00** dan total SKS = SKS MK A **satu kali** (bukan dua kali) | TRN-03 |

### Sesi A11 — Audit log

Login `AUD` → `Pengaturan › Audit Log`.

| No | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|
| 1 | Filter Tipe Entitas = kelas | Entri `updated` dari Sesi A3 dengan pengguna Bagian Akademik | AUD-01 |
| 2 | Klik entri perubahan `K-1` | "Detail Audit Log": dosen & jadwal sebelum/sesudah | AUD-01 |
| 3 | Filter Aksi = `forced_override` | Satu entri untuk `K-3` (Sesi A3 no. 18); detail berisi daftar bentrok dan alasan "Uji paksa bentrok dosen untuk pengujian" | AUD-02 |
| 4 | Periksa detail entri mana pun | Tidak ada tombol ubah/hapus | AUD-09 |

### Sesi A12 — Keamanan dan isolasi universitas

| No | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|
| 1 | Login `M1`, `DSN-X`, `KEU` bergantian; ketik `/akademik/krs`, `/pengaturan/feature-flags`, `/keuangan/tagihan` | Untuk setiap URL yang menunya tidak tampil di akun itu: "Anda tidak memiliki akses ke halaman ini" | SEC-01 |
| 2 | Login `AKD-B`, buka detail sebuah kelas dan sebuah ujian, salin URL-nya. Login `AKD` (UND), tempel URL tersebut | Alert "Data tidak ditemukan."; tidak ada data universitas B | SEC-02 |
| 3 | Bandingkan detail `K-1` dan halaman KRS admin antara `AKD`, `DSN-X`, `KEU` | Tombol **Ubah**, **Tambah KRS**, **Batalkan** hanya muncul untuk `AKD` | SEC-03 |
| 4 | Sebagai `M1`, logout → tombol Back browser | Kembali ke halaman login; data KRS tidak tampil | SEC-04 |
| 5 | Login `M1`, ketik `/akademik/persetujuan-krs` | "Anda tidak memiliki akses ke halaman ini" | APV-10 |

### Sesi A13 — Tampilan

| No | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|
| 1 | Buka Kelas dan Jadwal dan KRS portal dengan koneksi lambat (mis. tethering lemah) | "Memuat..." atau kerangka tampil sebelum data | UX-01 |
| 2 | Cari kelas dengan kata acak; buka KRS mahasiswa baru tanpa pilihan | "Belum ada kelas." / "Belum ada KRS untuk semester ini." | UX-02 |
| 3 | Matikan internet, buka halaman portal | Alert merah + tombol coba lagi | UX-03 |
| 4 | Buka KRS portal dan modal Dosen & Jadwal di ponsel atau jendela < 768 px | Tabel bisa digulir horizontal, kartu menumpuk, tombol utama terlihat, halaman tidak bergeser ke samping | UX-04 |
| 5 | Tutup Alert dengan tanda silang | Alert hilang | UX-06 |

**Akhir Bagian A:** matikan kembali "Kepemilikan Kelas Dosen" di UND dan ITM bila lingkungan dipakai pengujian lain. Rekap hasil sesuai §5.

---

## 4. Bagian B — Alur Lengkap Satu Periode SP

Jalankan setelah fase terkait selesai. Setiap sesi mencantumkan tahap minimal yang dibutuhkan. Nama menu/tombol mengikuti rancangan [frontend-dan-mobile.md](./frontend-dan-mobile.md) — sesuaikan dengan label final.

**Tanggal yang dipakai** (data demo memakai Ganjil 2026/2027 sebagai periode berjalan):

| Periode | Tanggal kuliah | Keterangan |
|---|---|---|
| Genap 2025/2026 | 1 Feb – 31 Jul 2026 | Data demo |
| Ganjil 2026/2027 | 1 Agu 2026 – 31 Jan 2027 | Data demo, periode berjalan |
| Genap 2026/2027 | 1 Feb – 30 Jun 2027 | Dibuat di B2 |
| **SP 2026/2027** | **5 Jul – 20 Agu 2027** | Dibuat di B2. Periode KRS SP: **hari ini – 30 Jun 2027** supaya pendaftaran bisa diuji sekarang |

Data uji: pakai `M1`, `M2`, `M3`, `M4` dari Bagian A, ditambah `M5` (IP Genap 1,80, tidak ikut SP), `M6` (IP Genap 1,80, ikut SP), `M7` (IP Genap 3,20) untuk uji batas SKS, dibuat dengan cara yang sama seperti A0.

### Sesi B1 — Mengaktifkan dan mengatur SP · Tahap 1.5

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `AKD` | Pastikan flag "Semester Pendek" UND nonaktif. `Akademik › Periode Akademik` › **Tambah Periode**, Semester Pendek, isi lengkap → **Simpan** | "Fitur Semester Pendek belum diaktifkan untuk universitas ini."; periode tidak bertambah | FLG-09 |
| 2 | `OWN` | Feature Flag: nyalakan "Semester Pendek" | Badge "Diatur universitas" | FLG-02 |
| 3 | `OWN` | `Pengaturan › Akademik` | Nilai default: maks SKS SP 9, minimum peserta 5, kehadiran minimum 75%, persetujuan "dosen wali", nilai ulang "nilai tertinggi" | SET-01 |
| 4 | `OWN` | Isi maks SKS SP `0`, lalu `abc` → **Simpan** | Pesan validasi; tidak tersimpan | SET-04 |
| 5 | `OWN` | Atur: biaya Rp0/SKS (putaran gratis dulu), maks SKS 9, batas ulang C, MK baru boleh, jatuh tempo 3 hari → **Simpan** | Tersimpan | — |
| 6 | `OWN` | Ubah kebijakan nilai ulang ke "nilai terakhir" | Peringatan IPK dihitung ulang + konfirmasi. Batalkan | SET-05 |
| 7 | `OWN-B` | Buka Pengaturan Akademik | Nilai universitas B tidak ikut berubah | SET-03 |
| 8 | `DSN-X`, `M1` | Ketik `/pengaturan/akademik` | "Anda tidak memiliki akses ke halaman ini" | SET-06 |

### Sesi B2 — Membuat periode SP · Tahap 1.5

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `AKD` | Sidebar Akademik | Ada "Periode Akademik"; `M1`/`DSN-X` tidak melihatnya | NAV-07 |
| 2 | `AKD` | **Tambah Periode**: SP 2025/2026, kuliah 5 Jul – 20 Agu 2026 → **Simpan** | "Tanggal Semester Pendek beririsan dengan 2025/2026 Genap." | TRM-04 |
| 3 | `AKD` | Isi tahun `2026/2028`, lalu `26/27`; tanggal selesai sebelum tanggal mulai; maks SKS `0` dan `25` | Pesan validasi per kolom | TRM-05 |
| 4 | `AKD` | **Tambah Periode**: Genap 2026/2027, 1 Feb – 30 Jun 2027 → **Simpan** | Periode Genap baru tampil | — |
| 5 | `AKD` | **Tambah Periode**: SP 2026/2027, 5 Jul – 20 Agu 2027, maks SKS kosong → **Simpan** | Label "2026/2027 Pendek", status "Draft", batas efektif 9 SKS (dari pengaturan) | TRM-02, TRM-06 |
| 6 | `AKD` | Tambah SP 2026/2027 sekali lagi | Alert merah; tidak ada periode baru | TRM-03 |
| 7 | `M1` | Periksa KRS, Jadwal, sidebar | SP belum terlihat di mana pun; tidak ada menu Semester Pendek | TRM-02, NAV-08 |
| 8 | `AKD` | SP → **Ubah** → tanggal selesai 21 Agu 2027 → **Simpan** | Tanggal berubah; status tetap Draft | TRM-07 |
| 9 | `AKD` | Buat periode SP percobaan lain (mis. SP 2027/2028) lalu **Hapus** | Terhapus (Draft tanpa kelas) | TRM-09 |
| 10 | `AKD` | Detail SP → form Periode KRS: tutup sebelum buka | "Tanggal akhir KRS harus sama dengan atau setelah tanggal mulai." | TRM-12 |
| 11 | `AKD` | Periode KRS: tutup 8 Jul 2027 (3 hari setelah kuliah mulai) | Pesan validasi | TRM-13 |
| 12 | `AKD` | Periode KRS: hari ini – 30 Jun 2027 → simpan | Tersimpan | TRM-11 |
| 13 | `AKD` | Ubah Status pada SP Draft | Hanya pilihan "Direncanakan" | STS-01 |

### Sesi B3 — Membuka kelas SP · Tahap 2.4

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `AKD` | Ubah Status → Pendaftaran Dibuka (belum ada kelas) | Pilihan belum tersedia atau Alert merah | STS-03 |
| 2 | `AKD` | Ubah Status → Direncanakan | Badge "Direncanakan"; tercatat di Riwayat Status | STS-02 |
| 3 | `AKD` | Tab **Kelas** › **Buka Kelas**: MK A, kode `SP-A`, kapasitas `0` / `501`, minimum 40 dengan kapasitas 30 | Pesan validasi | KLS-07 |
| 4 | `AKD` | **Buka Kelas**: MK A `SP-A` kap. 30 min. 5; MK A `SP-B` kap. 30 min. 5; MK C `SP-C`; `MK-NEW` (MK yang belum pernah diambil) `SP-N` | Empat kelas tampil di tab Kelas dengan kolom Peserta & Status | KLS-05, KLS-06 |
| 5 | `AKD` | Dosen & Jadwal: `SP-A` `DSN-X` Senin 08:00–10:30 R.301; `SP-B` `DSN-Y` Selasa 08:00–10:30 R.302; `SP-C` `DSN-Y` Senin 09:00–11:00 R.303; `SP-N` `DSN-X` Rabu 08:00–10:00 R.304 | Tersimpan; ulangi beberapa uji bentrok Sesi A3 di kelas SP (ruangan, dosen, paksa) | JDW-01…14 |
| 6 | `AKD` | Kolom Periode di Kelas dan Jadwal, KRS, Penilaian | "2026/2027 Pendek" — tidak pernah "Genap" | TRM-14 |
| 7 | `AKD` | SP → **Ubah** | Kolom Semester & Tahun Akademik nonaktif (sudah ada kelas) | TRM-08 |
| 8 | `AKD` | **Hapus** SP | Tidak bisa (sudah ada kelas) | TRM-10 |

### Sesi B4 — Membuka pendaftaran · Tahap 3.4

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `M1` | Sidebar portal | Menu "Semester Pendek" muncul (SP Direncanakan) | NAV-08 |
| 2 | `M1` | KRS → pilih periode "2026/2027 Pendek" | "Pendaftaran dibuka …"; kelas terlihat, **Pilih Kelas** nonaktif | KRS-22 |
| 3 | `AKD` | Ubah Status → Pendaftaran Dibuka | Badge berubah | STS-02 |
| 4 | `M1`, `M3` | Buka Notifikasi | `M1` menerima pemberitahuan pendaftaran SP dibuka; `M3` (Cuti) tidak | NTF-03 |
| 5 | `M1` | KRS periode SP | Subjudul "Semester 2026/2027 Pendek"; Beban SKS "/ 9 SKS" dengan penjelasan batas tetap SP | KRS-18 |
| 6 | `M1` | Buka KRS tanpa memilih periode | Tetap periode reguler berjalan (Ganjil 2026/2027) | KRS-20 |

### Sesi B5 — Mahasiswa memilih mata kuliah SP · Tahap 3.2–3.4

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `M1` (MK A = D) | Lihat `SP-A` | Badge "Mengulang (nilai D)"; bisa dipilih | ELG-08 |
| 2 | `M1` | **Pilih Kelas** `SP-A` | Ditambahkan; Beban SKS bertambah | KRS-03 |
| 3 | `M1` | Lihat `SP-B` (MK A juga) | Tidak bisa dipilih: "Anda sudah mengambil mata kuliah ini di kelas lain." | ELG-14 |
| 4 | `M1` | Lihat `SP-C` (Senin 09:00) | "Bentrok dengan: …" | ELG-02 |
| 5 | `M2` (MK B = B) | Buka kelas SP untuk MK B bila ada; atau ubah sementara batas ulang ke C dan uji MK yang nilainya B | "Nilai B tidak memenuhi syarat untuk diulang (maksimal C)." | ELG-09 |
| 6 | `OWN` | Kosongkan batas ulang; `M2` muat ulang | Kelas yang tadi ditolak sekarang bisa dipilih. Kembalikan batas ulang C | ELG-10 |
| 7 | `OWN` | Matikan "MK baru boleh"; `M1` lihat `SP-N` | "Tidak dapat diambil" + alasan. Nyalakan kembali | ELG-11 |
| 8 | `M3` (Cuti) | Buka KRS SP | Ditolak karena status tidak aktif | ELG-07 |
| 9 | [Developer] | Beri `M4` tagihan lain yang lewat jatuh tempo | — | — |
| 10 | `M4` | Buka KRS SP, pilih kelas | "Masih ada tagihan yang belum lunas." | ELG-12 |
| 11 | `M1` | Pilih kelas sampai > 9 SKS | Ditolak dengan pesan batas 9 SKS | ELG-17 |
| 12 | `M1` | Pilih 2 kelas; `AKD` turunkan maks SKS SP menjadi 3; `M1` **Ajukan KRS** | Ditolak soal batas SKS; status item tidak berubah. Kembalikan maks SKS 9 | ELG-19 |
| 13 | `M1` | Periode SP percobaan berstatus Draft (buat bila perlu) | Tidak ada di pilihan periode | KRS-19 |

### Sesi B6 — Persetujuan · Tahap 3.3–3.4

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `M1` | KRS SP (hanya `SP-A`) → **Ajukan KRS** → **Ya, Ajukan** | "Menunggu Persetujuan" | KRS-06 |
| 2 | `WALI` | `Akademik › Persetujuan KRS` | Halaman terbuka; hanya mahasiswa perwaliannya; filter periode "2026/2027 Pendek" menyaring pengajuan SP | NAV-05, APV-01, APV-08 |
| 3 | `WALI` | Buka pengajuan `M1` | MK, total SKS, batas SKS, tanggal diajukan | APV-02 |
| 4 | `WALI` | **Tolak** dengan alasan kosong, lalu "abc" | "Tuliskan alasan penolakan supaya mahasiswa dapat memperbaiki KRS-nya." / "Alasan penolakan minimal 5 karakter." | APV-04 |
| 5 | `WALI` | Buka pengajuan yang sama di dua jendela; jendela 1 **Setujui**, jendela 2 **Tolak** | Jendela 1 "KRS disetujui."; jendela 2 "KRS ini sudah diputuskan sebelumnya atau belum diajukan." | APV-03, APV-06 |
| 6 | `M1` | KRS SP | Biaya Rp0 → item langsung "Terdaftar" | PAY-03 |
| 7 | `AKD` | Persetujuan KRS | Pengajuan semua mahasiswa terlihat | APV-07 |
| 8 | `OWN` | Mode persetujuan "otomatis". `M2` mengajukan KRS SP | Modal tidak menyebut dosen wali; langsung "Disetujui"; tidak masuk antrean `WALI` | KRS-23, APV-09 |
| 9 | `AKD` | `Akademik › KRS` → filter periode SP & status | KRS SP saja | ADM-06 |
| 10 | `AKD` | **Tambah KRS** `M3` (Cuti) ke `SP-A` | "Hanya mahasiswa berstatus aktif yang dapat mendaftar." | ADM-07 |
| 11 | `AKD` | Modal **Tambah KRS**: bagian "Override aturan" → centang prasyarat, alasan "ok" | "minimal 10 karakter"; isi alasan lengkap → tersimpan & tercatat di Audit Log | ADM-09…11 |

> Kembalikan mode persetujuan ke "dosen wali" setelah langkah 8.

### Sesi B7 — Tagihan dan pembayaran · Tahap 4.5

`OWN` mengubah biaya menjadi **Rp150.000/SKS** sebelum sesi ini. Ulangi pengajuan dengan mahasiswa yang belum mendaftar SP.

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `M5` | Ajukan KRS SP Basis Data (3 SKS); `WALI` **Tolak** | Tidak ada tagihan untuk `M5` | PAY-05 |
| 2 | `M5` | Ajukan lagi; `WALI` **Setujui** | KRS "Menunggu pembayaran" + Rp450.000 + jatuh tempo + tautan Tagihan | KRS-24 |
| 3 | `M5` | Portal › **Tagihan** | Tagihan Rp450.000 "Belum Bayar", periode "2026/2027 Pendek", jatuh tempo hari ini + 3; hanya tagihan milik sendiri | PAY-02, PAY-04 |
| 4 | `M5` | Notifikasi | Pemberitahuan tagihan terbit | NTF-04 |
| 5 | `AKD` | Detail tagihan | Tidak ada tombol **Catat Pembayaran** | PAY-15 |
| 6 | `KEU` | `Keuangan › Tagihan` → filter periode SP → detail tagihan `M5` | Tidak ada kontrol untuk mengubah status langsung | PAY-06, PAY-11 |
| 7 | `KEU` | **Catat Pembayaran**: Rp0; tanggal besok; metode kosong | Pesan per kolom | PAY-10 |
| 8 | `KEU` | **Catat Pembayaran** Rp200.000, transfer, ref "TRF-1" | "Sebagian"; Riwayat Pembayaran bertambah; KRS `M5` masih menunggu pembayaran | PAY-07 |
| 9 | `KEU` | **Catat Pembayaran** Rp300.000 | Ditolak (melebihi sisa) | PAY-09 |
| 10 | `KEU` | **Catat Pembayaran** Rp250.000 | "Lunas"; KRS `M5` "Terdaftar"; `M5` mendapat notifikasi | PAY-08, NTF-05 |
| 11 | `KEU` | Detail tagihan lunas | Tombol **Batalkan** & **Catat Pembayaran** tidak ada | PAY-13, PAY-14 |
| 12 | `M6` | Ajukan & disetujui → tagihan Belum Bayar. `KEU` **Batalkan** + alasan | "Dibatalkan" | PAY-12 |
| 13 | `M7` | Ajukan & disetujui → **batalkan item** sendiri sebelum bayar | Item "Dibatalkan"; tagihan dibatalkan | KRS-26 |
| 14 | `M5` | Coba batalkan item yang sudah lunas | Tidak bisa | KRS-27 |
| 15 | [Developer] | Isi kelas `SP-A` hingga penuh; buat satu peserta lain menunggu pembayaran dengan jatuh tempo kemarin | — | — |
| 16 | `M2` | Pilih `SP-A` | Berhasil; peserta yang kedaluwarsa menjadi "Dibatalkan", tagihannya "Dibatalkan", dan ia menerima notifikasi kedaluwarsa | PAY-17 |
| 17 | [Developer] | Ulangi kondisi kedaluwarsa tanpa ada yang mendaftar; jalankan proses harian (`php artisan academic:expire-short-term-holds`) | — | — |
| 18 | Mahasiswa terkait, `KEU` | Muat ulang KRS & Tagihan | Hasil sama dengan langkah 16 | PAY-18 |
| 19 | `AKD` | `Akademik › KRS` → **Batalkan** KRS SP `M5` yang sudah lunas; coba tanpa alasan dulu | Tanpa alasan tidak bisa; dengan alasan → "Dibatalkan"; `M5` muncul di tab Pembayaran › "Perlu refund", tagihan tetap "Lunas" | ADM-12, PAY-20 |

### Sesi B8 — Menutup pendaftaran dan membatalkan kelas sepi · Tahap 2.4, 3.5

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | Mahasiswa uji | Pilih kelas SP tapi **jangan** ajukan (biarkan Draft); catat sisa kursi | — | — |
| 2 | `AKD` | Ubah Status → Pendaftaran Ditutup | Item Draft menjadi "Dibatalkan", sisa kursi bertambah; item Menunggu/Terdaftar tidak berubah | STS-09 |
| 3 | `M1` | KRS SP | Baca saja; **Pilih Kelas** nonaktif | KRS-25 |
| 4 | `AKD` | Ringkasan SP | Kartu "Perlu tindakan": kelas di bawah minimum (`SP-C`, `SP-N`) | RPT-03 |
| 5 | `AKD` | `SP-C` → **Batalkan Kelas** tanpa alasan | Tidak bisa dikonfirmasi | KLS-11 |
| 6 | `AKD` | **Batalkan Kelas** + "Peserta di bawah minimum 5 orang" | Modal menyebut jumlah peserta & tagihan terdampak sebelum konfirmasi; status "Batal"; KRS pesertanya "Dibatalkan"; peserta & dosen mendapat notifikasi | KLS-10, NTF-06 |
| 7 | `AKD` | Ubah kapasitas `SP-A` lebih kecil dari jumlah peserta | "Kapasitas tidak boleh lebih kecil dari {n} peserta terdaftar." | KLS-09 |
| 8 | `AKD` | Ubah Status → Pendaftaran Dibuka (masih sebelum 5 Jul 2027) | Berhasil; kembalikan ke Pendaftaran Ditutup | STS-05 |

### Sesi B9 — Perkuliahan · Tahap 3.4, 5.4

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `AKD` | Ubah Status → Berlangsung; buka Dashboard | Dashboard tetap menampilkan Ganjil 2026/2027 | STS-02, TRM-16, RPT-01 |
| 2 | `M1` | `Akademik › Jadwal`, `Presensi`, `Perkuliahan › Mata Kuliah/Materi/Tugas`, Dashboard | Kelas `SP-A` tampil bersama kelas Ganjil | PRT-02…04, PRT-06 |
| 3 | `M1` | Semester Pendek › Ringkasan | Status SP, aturan, status KRS saya | PRT-07 |
| 4 | `DSN-X` | Pilih periode SP di halaman kelasnya | Hanya kelas SP miliknya | LRN-11 |
| 5 | `DSN-X` | **Rekam Kehadiran** `SP-A` 8 pertemuan (`M1`: 5 hadir, 1 izin, 1 sakit, 1 alpa) | Hanya peserta Terdaftar di daftar | LRN-01, LRN-02 |
| 6 | `M1` | Presensi | 87,5%; setelah `OWN` mematikan "izin/sakit dihitung hadir": 62,5% | LRN-06 |
| 7 | `DSN-X` | Buat & publish ujian `SP-A` | Seperti Sesi A9 no. 9–10 | LRN-05 |
| 8 | `OWN` | Nyalakan "kehadiran memblokir ujian". `M1` **Mulai Ujian** | "Kehadiran Anda 62% di bawah batas 75%." | LRN-08 |
| 9 | `OWN` | Matikan lagi. `M1` **Mulai Ujian** | Ujian dimulai; kerjakan sampai selesai | LRN-09, PRT-05 |

### Sesi B10 — Penilaian · Tahap 5.5

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `DSN-X` | **Input Nilai** `M1` di `SP-A`: skor 78 | Status "Draft" | GRD-06 |
| 2 | `M1` | Nilai, KHS, Transkrip | Nilai SP belum tampil | GRD-06, TRN-06 |
| 3 | `DSN-X` | **Input Nilai** di kelas Ganjil | Status "Disubmit"; langsung tampil di portal | GRD-07 |
| 4 | `AKD` | Ubah Status → Penilaian | `DSN-X` mendapat pengingat batas nilai | STS-02 |
| 5 | `DSN-X` | **Submit Nilai Kelas** `SP-A` saat masih ada peserta tanpa nilai | Alert merah + daftar NIM & nama yang belum dinilai | GRD-08 |
| 6 | `DSN-X` | Lengkapi nilai → **Submit Nilai Kelas** | Semua "Disubmit"; `AKD` mendapat notifikasi | GRD-09 |
| 7 | `DSN-X` | Cari tombol **Finalisasi Kelas** / **Kembalikan** | Tidak ada | GRD-13 |
| 8 | `AKD` | **Kembalikan** tanpa alasan, lalu "Nilai UAS belum masuk" | Tanpa alasan ditolak; dengan alasan semua kembali "Draft" | GRD-10 |
| 9 | `AKD` | **Finalisasi Kelas** saat masih Draft | Alert merah | GRD-12 |
| 10 | `DSN-X`, `AKD` | Submit ulang, lalu **Finalisasi Kelas** | Semua "Final" | GRD-11 |
| 11 | `DSN-X`, `AKD` | **Input Nilai** peserta yang sudah Final | "Nilai sudah difinalisasi; ajukan revisi nilai." | GRD-14 |
| 12 | `DSN-X` | **Ajukan Revisi** nilai `M1` (C → B) dengan alasan < 20 karakter, lalu lengkap | Pendek ditolak; lengkap → "Menunggu"; tombol ajukan nonaktif selama menunggu | GRD-15 |
| 13 | `DSN-X` | Buka revisi miliknya | Tidak ada tombol **Setujui** | GRD-18 |
| 14 | Penyetuju | **Setujui** | Nilai jadi B; **Riwayat** menampilkan C → B, pelaku, alasan; `M1` dinotifikasi | GRD-16, GRD-19, NTF-09 |
| 15 | `DSN-X`, penyetuju | Ajukan revisi lain → **Tolak** + alasan | Nilai tidak berubah | GRD-17 |
| 16 | `AKD` | Biarkan satu kelas belum final → Ubah Status → Selesai | Ditolak; ada opsi Paksa + alasan | STS-07 |
| 17 | `AKD` | Paksa dengan alasan < 10 karakter, lalu lengkap | Pendek ditolak; lengkap → "Selesai", alasan tampil di Riwayat Status | STS-08 |

### Sesi B11 — Hasil studi · Tahap 6.1, 1.2

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `M1` | Notifikasi | Pemberitahuan nilai SP dirilis | NTF-09 |
| 2 | `M1` | `Akademik › Transkrip` | Kartu "2026/2027 Pendek": MK A bertanda "U"; MK A nilai D di Genap 2025/2026 diberi keterangan tidak dihitung; IPK memakai nilai SP; total SKS MK A dihitung sekali | TRN-04 |
| 3 | `M1` | `Akademik › KHS` → "2026/2027 Pendek" | IPS SP dan rincian nilai SP | TRN-07 |
| 4 | `M1` | `Akademik › Nilai`, Profil | Judul nilai SP tidak menjadi semester baru; nomor semester tidak bertambah | TRM-15 |
| 5 | `M1` | `Akademik › Dokumen` → unduh KHS SP & Transkrip | PDF berlabel "2026/2027 Pendek" dengan tanda "U" | TRN-08 |
| 6 | `OWN` | Ubah kebijakan nilai ulang (dengan data: MK SP bernilai E) | "nilai tertinggi" memakai D; "nilai terakhir" memakai E | TRN-05 |
| 7 | [Developer] | Siapkan periode Ganjil 2027/2028 dengan KRS dibuka | — | — |
| 8 | `M5`, `M6`, `M7` | KRS Ganjil 2027/2028 → Beban SKS | `M5` "/ 18 SKS"; `M6` "/ 18 SKS" (atau "/ 24 SKS" bila pengaturan "IP SP ikut menentukan batas SKS" aktif); `M7` "/ 24 SKS" | TRN-09 |

### Sesi B12 — Arsip · Tahap 1.5

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `AKD` | Ubah Status → Diarsipkan | Modal konfirmasi dengan alasan | STS-11 |
| 2 | `AKD`, `DSN-X` | Coba: **Ubah** Dosen & Jadwal `SP-A`, **Tambah KRS**, **Rekam Kehadiran**, **Buat Ujian**, **Input Nilai**, **Ajukan Revisi** | Setiap aksi tidak tersedia atau ditolak | STS-10, JDW-21 |
| 3 | `AKD`, `M1` | Buka kelas, KRS, nilai, transkrip SP | Semua data tetap bisa dilihat | STS-10 |

### Sesi B13 — Laporan, notifikasi, dan audit · Tahap 6.2–6.4

| No | Pelaku | Langkah | Yang harus terlihat | Ref |
|---|---|---|---|---|
| 1 | `AKD` | Ringkasan SP | Angka cocok dengan hitungan manual di tab Kelas, Pendaftaran, Pembayaran | RPT-02 |
| 2 | `AKD` | `Laporan` → pilih jenis SP tanpa periode | Diminta memilih periode | RPT-06 |
| 3 | `AKD` | Lima laporan SP untuk periode SP; **Ekspor CSV** & **Ekspor PDF** | Kolom sesuai rancangan; CSV terbuka benar di Excel | RPT-04, RPT-05 |
| 4 | `DSN-X`, `M1` | Ketik `/laporan` | "Anda tidak memiliki akses ke halaman ini" | RPT-07 |
| 5 | Semua | Periksa isi seluruh notifikasi yang diterima selama Bagian B | Nama, periode, MK, jumlah, tanggal terisi; tidak ada teks mentah `{...}` | NTF-07, NTF-08, NTF-10 |
| 6 | `M5` | Matikan notifikasi di Pengaturan portal; picu tagihan baru | Notifikasi tagihan tetap masuk | NTF-04 |
| 7 | `AUD` | Audit Log: filter entitas periode, kelas, KRS, tagihan, nilai | Perubahan status SP, pembatalan kelas, override, pembayaran, finalisasi & revisi nilai, ekspor — masing-masing dengan pelaku dan alasan yang benar | AUD-03…08 |
| 8 | `M1` | Cari cara melihat KRS/nilai/tagihan `M2` (menu, URL, pilihan) | Tidak ada | SEC-06 |
| 9 | `M1` | Tempel URL detail periode SP Draft (dari `AKD`) | "Data tidak ditemukan." | SEC-05 |

### Sesi B14 — Ulangi dengan konfigurasi lain dan regresi

1. Ulangi Sesi B4–B11 secara ringkas untuk tiga kombinasi lain ([black-box-testing.md §23](./black-box-testing.md#23-skenario-end-to-end)): otomatis + gratis, dosen wali + gratis, otomatis + berbayar.
2. Jalankan ulang Bagian A Sesi A3–A10 pada periode Ganjil (regresi, [black-box-testing.md §22](./black-box-testing.md#22-regresi-semester-reguler)). Perubahan yang **disengaja**: mahasiswa tidak aktif dan mata kuliah sama di dua kelas ditolak di jalur admin, nilai final tidak bisa diubah langsung, SP tidak dihitung sebagai semester sebelumnya untuk batas SKS.

---

## 5. Setelah Pengujian

1. Pastikan setiap langkah punya hasil: **Lulus**, **Gagal** (dengan laporan bug), atau **Terblokir** (sebutkan langkah penyebabnya).
2. Buat rekap per sesi: jumlah Lulus / Gagal / Terblokir, dan daftar bug berprioritas **Tinggi**.
3. Sesuai [black-box-testing.md §24.3](./black-box-testing.md#243-urutan-mengikuti-gerbang-rilis): semua kasus prioritas **Tinggi** pada gerbang yang diuji harus lulus sebelum lanjut ke gerbang berikutnya.
4. Kembalikan lingkungan: matikan feature flag yang dinyalakan, atau jalankan ulang `php artisan migrate:fresh --seed` bila lingkungan khusus pengujian.

---

## Lampiran A — Bantuan Developer

Dipakai untuk langkah bertanda **[Developer]** selama halamannya belum ada. Semua perintah dijalankan di lingkungan pengujian yang sama.

### A.1 Membuat akun login mahasiswa baru

Mahasiswa yang dibuat dari `Mahasiswa › Tambah Mahasiswa` belum punya akun. Seeder ini membuatkan akun dengan email mahasiswa dan password `password` (wajib diganti saat login pertama):

```bash
php artisan db:seed --class="Modules\\Academic\\Database\\Seeders\\StudentUserAccountSeeder"
```

### A.2 Token API Bagian Akademik

A.3–A.6 memakai endpoint yang sudah ada, dengan akun Bagian Akademik (punya izin `krs.update`, `students.update`, `courses.update`, `krs.approve`). Contoh di Git Bash, butuh `curl` dan `jq`:

```bash
API=http://localhost:8000/api/v1
TOKEN=$(curl -s -X POST "$API/login" -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"akademik@undigital.test","password":"password"}' | jq -r '.data.token')
H=(-H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json')

# Mencari ID yang dibutuhkan
curl -s "${H[@]}" "$API/academic-terms" | jq '.data'
curl -s "${H[@]}" "$API/students?search=SP-UJI" | jq '.data[] | {id, nim, name}'
curl -s "${H[@]}" "$API/lecturers?search=Pembimbing" | jq '.data[] | {id, name}'
curl -s "${H[@]}" "$API/courses?search=<nama MK>" | jq '.data[] | {id, code, name}'
```

### A.3 Mengatur periode KRS

```bash
curl -s -X PATCH "${H[@]}" "$API/academic-terms/<ID_TERM>/krs-period" \
  -d '{"krs_start_date":"2026-10-01","krs_end_date":"2026-10-22"}'
```

- "Belum dibuka": `krs_start_date` besok. "Sudah ditutup": `krs_end_date` kemarin.
- Mengosongkan: `{"krs_start_date":null,"krs_end_date":null}`.

### A.4 Menetapkan dosen wali

```bash
curl -s -X PATCH "${H[@]}" "$API/students/<ID_MAHASISWA>/academic-advisor" -d '{"lecturer_id":"<ID_DOSEN_WALI>"}'
```

`<ID_DOSEN_WALI>` = data dosen milik akun `dosenpa@undigital.test`.

### A.5 Mengatur prasyarat mata kuliah

```bash
curl -s -X PUT "${H[@]}" "$API/courses/<ID_MK_D>/prerequisites" \
  -d '{"prerequisites":[{"course_id":"<ID_MK_PRA>","min_letter_grade":"C"}]}'
```

### A.6 Menyetujui / menolak pengajuan KRS

```bash
curl -s "${H[@]}" "$API/krs-submissions?status=submitted" | jq '.data'   # cari pengajuan M1/M2, ambil id-nya
curl -s -X POST "${H[@]}" "$API/krs-submissions/<ID_PENGAJUAN>/approve" -d '{"note":null}'
curl -s -X POST "${H[@]}" "$API/krs-submissions/<ID_PENGAJUAN>/reject"  -d '{"note":"Uji tolak: tambah satu mata kuliah"}'
```

Untuk meniru dosen wali, login dengan `dosenpa@undigital.test` di A.2 (dosen wali hanya bisa memutuskan pengajuan mahasiswa perwaliannya).

### A.7 Menyisakan satu kursi di sebuah kelas

```bash
php artisan tinker
```

```php
use Modules\Academic\Models\{ClassSection, KrsItem};

$cs = ClassSection::findOrFail('<ID_K-5>');
$terisi = KrsItem::where('class_section_id', $cs->id)->whereIn('status', ['draft', 'pending', 'enrolled'])->count();
$cs->forceFill(['capacity' => $terisi + 1])->save();
```

### A.8 Riwayat nilai semester lalu

Membuat percobaan mata kuliah di Genap 2025/2026 (kelas arsip tiruan dari kelas berjalan dengan MK yang sama), lalu nilainya:

```php
use Modules\Tenancy\Models\University;
use Modules\Academic\Models\{AcademicTerm, ClassSection, KrsItem, Grade, Student};

$u    = University::where('code', 'UND')->firstOrFail();
$lalu = AcademicTerm::where('university_id', $u->id)->where('academic_year', '2025/2026')->where('semester', 'genap')->firstOrFail();

$riwayat = function (string $nim, string $kelasSekarangId, string $huruf, float $skor) use ($u, $lalu) {
    $mhs  = Student::where('university_id', $u->id)->where('nim', $nim)->firstOrFail();
    $asal = ClassSection::findOrFail($kelasSekarangId);

    $lama = $asal->replicate();
    $lama->forceFill([
        'academic_term_id' => $lalu->id,
        'class_code' => 'LAMA-'.$asal->class_code,
        'is_active' => false,
        'lecturer_id' => null,
    ])->save();

    $krs = (new KrsItem)->forceFill([
        'university_id' => $u->id, 'student_id' => $mhs->id, 'class_section_id' => $lama->id,
        'academic_term_id' => $lalu->id, 'status' => 'enrolled',
    ]);
    $krs->save();

    (new Grade)->forceFill([
        'university_id' => $u->id, 'krs_item_id' => $krs->id,
        'score' => $skor, 'letter_grade' => $huruf, 'submitted_at' => now(),
    ])->save();
};

$riwayat('SP-UJI-01', '<ID_K-1>', 'D', 45);  // M1: MK A = D
$riwayat('SP-UJI-02', '<ID_K-2>', 'B', 72);  // M2: MK B = B
$riwayat('SP-UJI-04', '<ID_K-1>', 'D', 45);  // M4: MK A = D
```

Kelas tiruan dibuat nonaktif sehingga tidak muncul di penawaran dan tidak ikut dicek bentrok.
