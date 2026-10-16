# 10. Tarif dan Komponen Biaya

[← Dashboard & Menu](03-dashboard-dan-menu.md) · [Daftar isi](README.md) · [Berikutnya: Tagihan →](11-tagihan.md)

**Menu:** **Keuangan → Tarif & Komponen** (tab **Tabel tarif** dan **Komponen biaya**).

## 1. Komponen biaya

| | |
|---|---|
| **Tujuan** | Mendaftar jenis biaya yang dapat ditagihkan beserta sifatnya. |
| **Siapa** | Lihat: `fee_components.read`. Tambah/ubah/hapus: `fee_components.create/update/delete` (Bagian Keuangan, Kepala Bagian Keuangan). |
| **Data disiapkan** | Daftar biaya resmi kampus (SK tarif), kategori PDDikti tiap biaya. |

**Langkah menambah komponen**

1. Buka tab **Komponen biaya**, klik **Komponen**.
2. Isi **Kode** (huruf/angka/garis, mis. `BPP`), **Nama**, **Kategori** (SPP/BPP Tetap, UKT, DPP/Uang Pangkal, Biaya per SKS, Praktikum, Ujian, Tugas Akhir, Wisuda, Biaya Cuti, Administrasi, Denda, Lainnya), **Frekuensi** (Sekali, Per semester, Per tahun, Sesuai kebutuhan).
3. Centang sifat yang berlaku: **Wajib**, **Boleh dicicil**, **Boleh mendapat potongan/beasiswa**, **Dapat dikembalikan**, **Dihitung untuk cekal**, **Aktif**.
4. Opsional: **Kategori pelaporan PDDikti**, **Kode akun**, **Keterangan**. Klik **Simpan**.

**Aturan & validasi**

- Kode unik per kampus. Kode diubah otomatis menjadi huruf besar.
- Komponen yang sudah dipakai di tabel tarif atau tagihan **tidak dapat dihapus**: *"Komponen sudah dipakai tarif/tagihan; nonaktifkan saja."* Hapus centang **Aktif** untuk berhenti memakainya.
- Komponen **Boleh dicicil** dan **Boleh mendapat potongan** menentukan apakah beasiswa/potongan dapat memotong komponen ini.
- Komponen berkategori **Biaya per SKS** dipakai tagihan SKS otomatis dari KRS ([bab 11 §4](11-tagihan.md#4-tagihan-biaya-sks-dari-krs)).

**Komponen bawaan** — perintah `php artisan keu:install` (dijalankan administrator sistem) membuat: BPP, UKT (nonaktif), SKS, DPP, PRAKTIKUM, WISUDA, CUTI, ADMIN. Sesuaikan nama/sifatnya dengan kebijakan kampus.

## 2. Tabel tarif

| | |
|---|---|
| **Tujuan** | Menetapkan nominal setiap komponen per tahun akademik, dengan pembeda prodi, angkatan, golongan UKT, jalur masuk, kelas/sistem kuliah, dan tingkat semester. |
| **Siapa** | Lihat: `fee_rates.read`. Susun: `fee_rates.create/update` (Bagian Keuangan). Setujui/kembalikan: `fee_rates.approve` (Kepala Bagian Keuangan, Wakil Rektor) — **bukan** penyusun yang sama. |
| **Data disiapkan** | SK tarif tahun akademik; daftar prodi; golongan UKT (bila ada) — [bab 16](16-ukt.md). |

### 2.1 Membuat tabel baru

1. Tab **Tabel tarif** → **Tabel baru**.
2. Isi **Kode** (mis. `TRF`), **Nama**, **Tahun akademik** (format `2026/2027`), opsional **Mulai berlaku semester** dan **Catatan / dasar SK**. Klik **Simpan**.
3. Halaman detail tabel terbuka dengan status **Draf**.

Tabel dengan **kode sama** dengan tabel lama otomatis menjadi **versi berikutnya**.

### 2.2 Menyalin tabel menjadi versi baru (kenaikan tarif)

1. Pada daftar tabel, klik **Salin jadi versi baru** di baris tabel sumber.
2. Isi kode/nama/tahun akademik baru dan **Kenaikan (%)** (boleh negatif hingga −50%). Nominal hasil salinan dibulatkan ke ratusan rupiah.
3. Periksa dan ubah baris yang perlu.

### 2.3 Menambah baris tarif

1. Pada detail tabel berstatus **Draf**, klik **Baris tarif**.
2. Pilih **Komponen**, **Dasar hitung** (**Tetap** atau **Per SKS**), isi **Nominal** (untuk per SKS: nominal per SKS).
3. Isi pembeda bila perlu — kosong berarti berlaku untuk semua: **Program studi**, **Golongan UKT**, **Angkatan dari/sampai**, **Semester ke- dari/sampai**, **Kode jalur masuk**, **Kode kelas/sistem kuliah**. Untuk per SKS dapat diisi **SKS minimum/maksimum ditagih**.
4. Klik **Simpan**. Kolom **Spesifisitas** menunjukkan seberapa spesifik baris itu.

**Cara sistem memilih tarif:** bila beberapa baris cocok untuk seorang mahasiswa, baris **paling spesifik** yang dipakai, dengan bobot: prodi > golongan UKT > jalur > kelas/sistem > tingkat semester > angkatan. Contoh: baris umum BPP Rp5.000.000 dan baris BPP khusus prodi Sistem Informasi Rp4.000.000 → mahasiswa Sistem Informasi ditagih Rp4.000.000.

### 2.4 Konflik tarif

Dua baris dengan pembeda **identik** dan rentang angkatan/semester yang **beririsan** dianggap bertabrakan. Kartu ringkasan menampilkan jumlah **Konflik**, baris yang bertabrakan bertanda **Konflik**, dan tabel **tidak dapat diajukan**: *"Terdapat n pasangan baris tarif yang bertabrakan (BR-KEU-02). Perbaiki sebelum diajukan."* Perbaiki rentang atau hapus salah satu baris.

### 2.5 Pengajuan dan persetujuan

1. Penyusun klik **Ajukan persetujuan** (tabel harus memiliki minimal satu baris dan tanpa konflik).
2. Penyetuju (akun lain) membuka tabel → **Setujui & berlakukan**, atau **Kembalikan** dengan alasan (tabel kembali ke Draf dengan catatan "Dikembalikan").
3. Saat disetujui, tabel berstatus **Berlaku**; versi lama berkode sama otomatis **Diarsipkan**.

**Hasil:** penerbitan tagihan berikutnya memakai tabel yang berlaku. Tagihan yang sudah terbit **tidak berubah** — setiap tagihan menyimpan salinan tarif yang dipakainya.

**Kondisi khusus**

- Tabel yang sudah diajukan/berlaku tidak dapat diubah: *"Tabel tarif yang sudah diajukan/berlaku tidak dapat diubah; buat versi baru (BR-KEU-01)."*
- Penyusun yang mencoba menyetujui tabelnya sendiri ditolak (pemisahan tugas).
- Dengan **Mode tarif** `cohort` (bawaan), mahasiswa memakai tabel tahun akademik **angkatannya** bila ada; bila tidak ada, tabel tahun berjalan.
- Tingkat semester dihitung dari tahun angkatan dan semester berjalan; **semester cuti belum dikurangkan** (lihat [bab 42](42-batasan-dan-integrasi.md)).

## 3. Pratinjau tagihan

| | |
|---|---|
| **Tujuan** | Melihat perhitungan tagihan seorang mahasiswa sebelum penerbitan, termasuk potongan beasiswa aktif. |
| **Siapa** | `fee_rates.read`. |
| **Lokasi** | Detail tabel tarif → **Pratinjau tagihan**. |

1. Cari mahasiswa (minimal 3 karakter NIM/nama), pilih **Semester** dan **Komponen**.
2. Klik **Hitung**. Tampil rincian baris, bruto, potongan, neto, dan tingkat semester.

Pratinjau memakai tabel yang **berlaku** (bukan draf yang sedang disusun). Bila tidak dapat dihitung, sistem menjelaskan alasannya, mis. *"Belum ada tabel tarif berlaku."*, *"Tarif tidak ditemukan: BPP"*, atau *"Golongan UKT belum ditetapkan."*
