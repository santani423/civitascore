# 35. Penilaian Ujian

[← Ujian Seleksi](34-ujian-seleksi.md) · [Daftar isi](README.md) · [Berikutnya: Proses Seleksi & Pengesahan →](36-proses-seleksi-dan-pengesahan.md)

**Untuk:** penguji/pewawancara/pengawas yang ditugaskan pada sesi (umumnya dosen, izin `pmb_scores.create` dan/atau `pmb_exam_attendance.update`), Panitia Seleksi, Administrator PMB.
**Menu:** **PMB → Penilaian Ujian**.

## 1. Sesi yang ditugaskan

Halaman **Penilaian Ujian** menampilkan **hanya sesi yang ditugaskan kepada Anda**: jadwal & kode sesi, waktu, komponen, jumlah peserta, dan label *terkunci* bila nilai sudah dikunci. Klik sesi untuk membuka lembar nilai.

![Penilaian ujian — penguji](assets/35-02-penilaian-pengawas.png)
*Gambar 35.1 — Tampilan dosen (pengawas): hanya menu Penilaian Ujian dan sesi yang ditugaskan.*

## 2. Mengisi nilai

![Lembar nilai](assets/35-01-penilaian-ujian.png)
*Gambar 35.2 — Lembar nilai sesi CBT (nilai dihitung otomatis dan sudah dikunci).*

Isian menyesuaikan tipe komponen:

| Tipe komponen | Isian |
|---|---|
| Angka | **Nilai** dalam rentang komponen (misalnya 0–100). |
| Lulus/tidak | Pilih **Lulus** atau **Tidak**. |
| Rubrik (wawancara/portofolio) | Nilai per kriteria rubrik (misalnya *Komunikasi*, *Motivasi*). Skor akhir dihitung dari bobot kriteria. |

1. Isi nilai dan **Catatan** (opsional) untuk setiap peserta.
2. Klik **Simpan nilai**. Pesan menunjukkan jumlah nilai yang disimpan.

- **Hanya peserta berstatus Hadir** yang dapat dinilai. Pastikan check-in sudah dicatat ([bab 34 §5](34-ujian-seleksi.md#5-hari-ujian-check-in--daftar-hadir)).
- Data peserta ditampilkan minimal (nama & nomor peserta, tanpa NIK/kontak).
- Untuk **CBT**, nilai dihitung **otomatis** dari jawaban peserta; penguji tidak perlu mengisi.
- Bila beberapa penguji menilai peserta yang sama, setiap penguji mengisi nilainya sendiri.

> **Beda rancangan** — Rancangan ADM-53 menyebut penyimpanan otomatis dan navigasi cepat antarpeserta. Versi ini menyimpan semua nilai dengan tombol **Simpan nilai**.

## 3. Mengunci nilai sesi

Setelah semua nilai sesi lengkap, Panitia Seleksi (`pmb_scores.finalize`) membuka detail sesi (**PMB → Ujian** → jadwal → sesi) dan klik **Kunci nilai**, lalu konfirmasi (*"Kunci nilai sesi? Perubahan selanjutnya memerlukan koreksi beralasan."*). Lembar nilai menampilkan *"Nilai sesi sudah dikunci."* dan isian menjadi baca saja.

Perubahan nilai yang sudah terkunci hanya dapat dilakukan melalui **pengajuan koreksi nilai** yang disetujui pejabat pengesah (lihat [bab 36 §3](36-proses-seleksi-dan-pengesahan.md#3-matriks-nilai-dan-koreksi-nilai)).

---

[← Ujian Seleksi](34-ujian-seleksi.md) · [Daftar isi](README.md) · [Berikutnya: Proses Seleksi & Pengesahan →](36-proses-seleksi-dan-pengesahan.md)
