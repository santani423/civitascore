# 3. Alur Kerja & Kalender Panitia

[← Akses, Peran, dan Hak Akses](02-akses-dan-peran.md) · [Daftar isi](README.md) · [Berikutnya: Website PMB →](10-website-pmb.md)

Bab ini merangkum **urutan pekerjaan** panitia dalam satu periode PMB. Setiap butir ditautkan ke bab yang menjelaskan cara melakukannya.

## Fase 0 — Persiapan sistem (sekali, oleh administrator sistem)

- [ ] Migrasi database, *seeder* peran & izin, dan data awal PMB per kampus (`pmb:install`). → [Administrasi Sistem](50-administrasi-sistem.md)
- [ ] Penjadwal (`schedule:run`) aktif setiap menit. → [§4 Penjadwal](50-administrasi-sistem.md#4-penjadwal-scheduler)
- [ ] Domain website PMB, email pengirim, (opsional) CAPTCHA, gateway pembayaran, SMS. → [§5 Variabel lingkungan](50-administrasi-sistem.md#5-variabel-lingkungan-env)
- [ ] Peran diberikan kepada staf (Admin PMB, verifikator, keuangan, panitia seleksi, pengesah, petugas daftar ulang, penguji). → [Akses & Peran](02-akses-dan-peran.md#3-peran-role-pmb)

## Fase 1 — Konfigurasi periode (Administrator PMB, Panitia Seleksi)

Urutan yang disarankan (sama dengan [16 §4](../../pmb/16-catatan-implementasi.md)):

1. [ ] **Jalur masuk, sistem kuliah, jenis dokumen.** → [Master Data](21-master-data.md)
2. [ ] **Template formulir**: atur field inti, tambah field, **Publikasikan**. → [Template Formulir](22-template-formulir.md)
3. [ ] **Skema seleksi**: komponen & bobot, ambang, uji **Simulasi**, **Kunci skema** (Panitia Seleksi). → [Skema Seleksi](23-skema-seleksi.md)
4. [ ] **Periode** baru. → [Periode](20-periode-gelombang-kuota-biaya.md#2-membuat-periode)
5. [ ] **Gelombang** (jadwal lengkap). → [Gelombang](20-periode-gelombang-kuota-biaya.md#3-gelombang)
6. [ ] **Buka jalur** pada setiap gelombang (biaya, waktu bayar, template, skema). → [Gelombang-jalur](20-periode-gelombang-kuota-biaya.md#4-membuka-jalur-pada-gelombang-gelombang-jalur)
7. [ ] **Kuota** per prodi × jalur × sistem kuliah. → [Kuota](20-periode-gelombang-kuota-biaya.md#6-kuota)
8. [ ] **Persyaratan dokumen** per gelombang-jalur. → [Persyaratan dokumen](20-periode-gelombang-kuota-biaya.md#5-persyaratan-dokumen)
9. [ ] **Komponen biaya** (uang pangkal, SPP, cicilan). → [Biaya](20-periode-gelombang-kuota-biaya.md#7-komponen-biaya)
10. [ ] **Konten**: Kebijakan Privasi dan Ketentuan Pendaftaran (**wajib** terbit sebelum publikasi periode), Pernyataan pendaftar (disarankan), FAQ, panduan, berita, banner, info daftar ulang & orientasi; **Profil prodi**. → [Konten Website & Profil Prodi](24-konten-website-profil-prodi.md)
11. [ ] **Pengaturan**: kontak panitia, rekening transfer, batas aplikasi, durasi klaim, format NIM, voucher. → [Pengaturan PMB](25-pengaturan-pmb.md)
12. [ ] **Publikasikan periode** (daftar periksa harus lengkap). → [Publikasi periode](20-periode-gelombang-kuota-biaya.md#8-publikasi-tutup-dan-arsip-periode)
13. [ ] Lokasi & ruang ujian. → [Ujian Seleksi](34-ujian-seleksi.md#2-lokasi-dan-ruang)

## Fase 2 — Masa pendaftaran

| Siapa | Pekerjaan rutin | Bab |
|---|---|---|
| Keuangan | Verifikasi bukti transfer (tab **Bukti manual**), catat pembayaran loket, cek tagihan bermasalah | [Keuangan PMB](33-keuangan-pmb.md) |
| Verifikator | **Ambil berikutnya** di antrean, periksa dokumen, verifikasi / minta perbaikan / tolak | [Verifikasi](32-verifikasi-administrasi.md) |
| Admin PMB | Tugaskan verifikator, setujui galeri pas foto massal, tangani duplikat & pemulihan akun, pendaftaran loket | [Verifikasi](32-verifikasi-administrasi.md), [Data Pendaftar](31-data-pendaftar.md) |
| Admin/operator | Balas **Pesan pendaftar**, kirim **broadcast** pengingat | [Komunikasi](39-komunikasi.md) |
| Admin PMB | Pantau dashboard; perpanjang gelombang bila perlu (dengan alasan) | [Dashboard](30-dashboard-pmb.md), [Perpanjang](20-periode-gelombang-kuota-biaya.md#perpanjang-pendaftaran) |

## Fase 3 — Ujian (jalur berujian)

1. [ ] Buat **jadwal ujian** per gelombang (komponen seleksi, mode luring/CBT/wawancara). → [Ujian](34-ujian-seleksi.md#3-jadwal-ujian)
2. [ ] Tambah **sesi** dan ruang, **Tempatkan otomatis** peserta terverifikasi, **Bagi petugas**.
3. [ ] Untuk CBT: isi **bank soal**.
4. [ ] **Publikasikan** jadwal → kartu peserta terbit dan pendaftar diberi tahu.
5. [ ] Hari-H: **check-in** peserta (pindai QR / ubah status).
6. [ ] Penguji mengisi **nilai** (CBT dinilai otomatis), lalu **Kunci nilai** sesi. → [Penilaian Ujian](35-penilaian-ujian.md)
7. [ ] (Opsional) **Impor nilai** dari berkas. → [Proses Seleksi](36-proses-seleksi-dan-pengesahan.md#4-impor-nilai)
8. [ ] Proses **permintaan jadwal ulang**.

## Fase 4 — Seleksi & pengesahan

1. [ ] Periksa **Matriks nilai** per gelombang-jalur.
2. [ ] **Jalankan seleksi** → **Mulai tinjau** → override/penetapan seri bila perlu.
3. [ ] **Ajukan pengesahan** (Panitia Seleksi).
4. [ ] Ketua Panitia (Admin PMB) lalu Pejabat Pengesah **menyetujui** di menu **Persetujuan**. → [Pengesahan](36-proses-seleksi-dan-pengesahan.md#7-pengesahan-oleh-pejabat-menu-persetujuan)
5. [ ] Batch pengumuman terjadwal otomatis pada waktu pengumuman gelombang.

## Fase 5 — Pengumuman & daftar ulang

1. [ ] Hasil terbit otomatis; LoA diterbitkan untuk yang diterima. → [Pengumuman](37-pengumuman-dan-keputusan.md)
2. [ ] Beri **beasiswa jalur masuk** bila ada (sebelum tagihan biaya awal dibayar).
3. [ ] Pantau **Kursi & cadangan**; **Promosikan** cadangan bila ada kursi kosong.
4. [ ] Keuangan memverifikasi pembayaran biaya awal.
5. [ ] Petugas daftar ulang memeriksa, menandai **pemeriksaan dokumen asli**, dan **Setujui** → konversi mahasiswa & NIM. → [Daftar Ulang](38-daftar-ulang-dan-konversi.md)
6. [ ] Tangani konversi yang gagal (**Coba lagi**).

## Fase 6 — Penutupan

1. [ ] **Tutup** periode, lalu **Arsipkan**. → [Periode](20-periode-gelombang-kuota-biaya.md#8-publikasi-tutup-dan-arsip-periode)
2. [ ] Ekspor laporan akhir (rekap pendaftar, keuangan, kuota, kelengkapan PDDikti). → [Laporan](40-laporan.md)
3. [ ] Selesaikan refund & rekonsiliasi. → [Keuangan](33-keuangan-pmb.md)
4. [ ] Tinjau audit & akses data sensitif. → [Audit](41-audit.md)

---

[← Akses, Peran, dan Hak Akses](02-akses-dan-peran.md) · [Daftar isi](README.md) · [Berikutnya: Website PMB →](10-website-pmb.md)
