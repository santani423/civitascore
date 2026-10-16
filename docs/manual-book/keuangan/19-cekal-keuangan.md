# 19. Cekal Keuangan dan Integrasi Akademik

[← Piutang & Tunggakan](18-piutang-dan-tunggakan.md) · [Daftar isi](README.md) · [Berikutnya: Rekonsiliasi & Refund →](20-rekonsiliasi-dan-refund.md)

**Menu:** **Keuangan → Cekal Keuangan** (tab **Mahasiswa tercekal**, **Dispensasi**, **Aturan cekal**).

## 1. Cara kerja

1. **Aturan cekal** (dibuat Bagian Keuangan) menentukan kapan kewajiban dianggap belum terpenuhi.
2. Sistem **mengevaluasi** setiap mahasiswa aktif: setiap kali tagihan/pembayaran mahasiswa berubah, setiap hari pukul 00:30, atau saat petugas menekan **Evaluasi semua**.
3. Bila ada kekurangan, cekal **diterbitkan** dan disimpan di SIAKAD; mahasiswa diberi tahu *"Layanan … dibatasi: …"*.
4. SIAKAD **menolak di server** layanan yang dicekal:
   - **Pengisian KRS** — menambah/menghapus mata kuliah, mengajukan KRS, dan **persetujuan KRS oleh dosen wali** (diperiksa ulang saat persetujuan). Halaman KRS mahasiswa menampilkan pesan *"Pengisian KRS diblokir oleh cekal: …"*.
   - **Ujian** — memulai ujian online, baik dari portal mahasiswa maupun lewat tautan ujian berbasis NIM.
   Pesan yang diterima: *"<Layanan> tidak dapat dilakukan karena terdapat cekal: <alasan>"*.
5. Begitu kewajiban dipenuhi (pembayaran, penyesuaian, cicilan), cekal **dicabut otomatis** dan mahasiswa diberi tahu *"Cekal keuangan (…) telah dicabut."*

> **Batasan:** pada versi ini hanya **Pengisian KRS** dan **Ujian** yang ditegakkan. Melihat nilai, transkrip, yudisium, dan wisuda **belum** dapat dicekal.

## 2. Aturan cekal

| | |
|---|---|
| **Siapa** | Lihat: `finance_clearance.read`; buat/ubah & evaluasi: `finance_clearance.update`. |
| **Lokasi** | Tab **Aturan cekal** → **Aturan** / **Ubah**. |

Isian:

| Isian | Arti |
|---|---|
| **Layanan yang dibatasi** | Pengisian KRS dan/atau Ujian |
| **Syarat** | **Lunas** (seluruh sisa), **Termin Pertama Lunas**, **Kewajiban Jatuh Tempo Lunas** (hanya tagihan/termin yang sudah jatuh tempo + tenggang aturan) |
| **Tagihan yang dihitung** | Semester berjalan, Semester sebelumnya, Semester berjalan & sebelumnya, Semua tagihan |
| **Kelompok tagihan** | Kosong = semua kelompok |
| **Tenggang setelah jatuh tempo** | Hari (untuk syarat jatuh tempo) |
| **Kekurangan minimal agar dicekal** | Mencegah cekal karena selisih kecil |
| **Khusus program studi**, **Aktif mulai/sampai**, **Termasuk tagihan denda**, **Aktif** | Cakupan aturan |

`php artisan keu:install` membuat aturan contoh **KEU-KRS**, **KEU-TUNGGAKAN**, **KEU-UJIAN** dalam keadaan **nonaktif**.

**Simulasi sebelum mengaktifkan:** klik **Simulasi** pada baris aturan → sistem menampilkan *"Simulasi …: n dari m mahasiswa aktif akan tercekal bila aturan ini aktif. Tidak ada cekal yang diterbitkan."* beserta daftar mahasiswa dan kekurangannya. Setelah yakin, **Ubah** → centang **Aktif** → **Simpan**, lalu **Evaluasi semua** (tab **Mahasiswa tercekal**) atau tunggu evaluasi harian.

Menonaktifkan aturan membuat cekal yang lahir dari aturan itu dicabut pada evaluasi berikutnya.

## 3. Daftar mahasiswa tercekal

Tab **Mahasiswa tercekal** (bawaan: Aktif & Didispensasi penuh; filter **Status** untuk melihat yang **Dicabut**). Kolom: mahasiswa, aturan, layanan dibatasi, kekurangan saat dievaluasi, sejak, status. **Detail** membuka halaman cekal:

- identitas, status, layanan, kekurangan, alasan;
- daftar **Dispensasi**;
- **Riwayat** append-only: *Cekal diterbitkan*, *Dispensasi diajukan/diberikan/ditolak/dicabut/kedaluwarsa*, *Seluruh layanan didispensasi*, *Cekal aktif kembali*, *Cekal dicabut*, lengkap dengan pelaku (atau *Sistem*), waktu, dan keterangan.

BAAK (`academic_blocks.read` + `finance_clearance.read`) dapat melihat daftar ini.

## 4. Dispensasi

| | |
|---|---|
| **Tujuan** | Mengizinkan sementara layanan yang dicekal (mis. KRS sambil menunggu pembayaran beasiswa). |
| **Siapa** | Beri: `finance_clearance.create`; putuskan & cabut: `finance_clearance.approve`. Mahasiswa dapat mengajukan dari portal. |

**Petugas memberi dispensasi:** detail cekal → **Beri dispensasi** → pilih **Layanan**, **Berlaku sampai**, **Alasan**, opsional **Janji bayar** & **Tanggal janji bayar**.

- Lama ≤ *batas dispensasi petugas* (bawaan 7 hari) atau pemberi berizin `finance_clearance.approve` → langsung **Aktif**.
- Lebih lama → **Menunggu** persetujuan pejabat (bukan pemberi yang sama).
- Melebihi *lama dispensasi maksimal* (bawaan 30 hari) ditolak: *"Dispensasi maksimal 30 hari."*

**Mahasiswa mengajukan:** portal **Keuangan** → kartu **Cekal keuangan** → **Ajukan dispensasi** → pilih layanan, **Dibutuhkan sampai**, **Alasan** (minimal 20 karakter), opsional janji bayar. Pengajuan mahasiswa selalu **Menunggu** keputusan petugas.

**Memutuskan:** tab **Dispensasi** (filter **Menunggu**) → **Setujui** atau **Tolak** (alasan wajib). Dispensasi aktif dapat **Dicabut** dengan alasan.

**Hasil:** selama dispensasi aktif dan berlaku, layanan tersebut dapat dipakai walau cekal masih ada. Bila seluruh layanan cekal didispensasi, status cekal menjadi **Didispensasi penuh**. Dispensasi yang lewat masa berlakunya otomatis **Kedaluwarsa** (pekerjaan harian) dan cekal **aktif kembali**.

## 5. Membuka cekal manual

**Siapa:** `finance_clearance.approve`. **Lokasi:** detail cekal → **Buka cekal manual** + alasan.

Pembukaan manual tercatat di audit. **Perhatian:** bila kewajiban belum dipenuhi, evaluasi otomatis berikutnya akan **menerbitkan cekal lagi**. Untuk pengecualian sementara, gunakan **dispensasi**.

## 6. Status bebas tanggungan keuangan

Ringkasan **Bebas tanggungan** (profil keuangan mahasiswa & portal mahasiswa) bernilai **Ya** bila tidak ada sisa tagihan di semester mana pun, tidak ada denda berjalan, dan tidak ada bukti transfer yang menunggu verifikasi. Data ini dapat dipakai TU/BAAK untuk yudisium. **Surat keterangan bebas tanggungan (PDF) belum tersedia** pada versi ini.
