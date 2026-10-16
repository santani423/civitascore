# Manual Book Modul Keuangan (KEU) — Civitas One

| | |
|---|---|
| **Versi dokumen** | 1.0 |
| **Tanggal** | 16 Oktober 2026 |
| **Acuan rancangan** | [`docs/RANCANGAN_SISTEM_ADMINISTRASI_KEUANGAN_MAHASISWA_KEU.md`](../../RANCANGAN_SISTEM_ADMINISTRASI_KEUANGAN_MAHASISWA_KEU.md) |
| **Acuan implementasi** | Kode aplikasi Civitas One cabang `feature/pmb-admission` (backend `app/Modules/Finance`, frontend `frontend/src/keu`) dan [Catatan Implementasi KEU](../../keu/catatan-implementasi.md) |
| **Pembaca** | Kepala Bagian Keuangan, staf keuangan, kasir/petugas loket, verifikator pembayaran, pejabat penyetuju, pimpinan, BAAK, auditor, mahasiswa, dan administrator sistem |

Manual ini menjelaskan **cara memakai** setiap menu Modul KEU sesuai peran: tujuan fitur, siapa yang dapat memakainya, lokasi menu, langkah kerja, data yang perlu disiapkan, aturan yang dijaga sistem, hasil yang diharapkan, dan cara menangani kendala.

> **Tentang gambar layar** — Manual versi ini **belum menyertakan tangkapan layar**. Gambar baru akan ditambahkan setelah aplikasi dijalankan di lingkungan uji kampus dengan data contoh; manual tidak memuat gambar rekaan. Nama menu, tombol, dan pesan yang dikutip di sini diambil langsung dari aplikasi.

> **Tentang pembayaran online** — Pembayaran online (virtual account/QRIS) **hanya aktif bila kampus sudah memiliki akun penyedia pembayaran dan kredensialnya dipasang oleh administrator sistem**. Pada instalasi tanpa kredensial, mahasiswa membayar **tunai di loket** atau **transfer manual dengan unggah bukti**. Lihat [bab 42](42-batasan-dan-integrasi.md).

---

## Mulai dari mana?

| Saya adalah… | Baca bagian |
|---|---|
| **Mahasiswa** | [Panduan Mahasiswa](30-panduan-per-peran.md#1-mahasiswa) |
| **Kepala Bagian Keuangan** (menyetujui, mengatur kebijakan) | [Pengenalan](01-pendahuluan.md), [Akses & Peran](02-akses-dan-peran.md), [Tarif](10-tarif-dan-komponen-biaya.md), [Tagihan](11-tagihan.md), lalu [Panduan per Peran](30-panduan-per-peran.md#3-kepala-bagian-keuangan) |
| **Staf keuangan** | [Tagihan](11-tagihan.md), [Pembayaran](12-pembayaran.md), [Verifikasi](13-verifikasi-dan-kuitansi.md), [Piutang](18-piutang-dan-tunggakan.md) |
| **Kasir / petugas loket** | [Pembayaran tunai](12-pembayaran.md#2-pembayaran-tunai-di-loket-kasir) |
| **Verifikator pembayaran** | [Verifikasi & Kuitansi](13-verifikasi-dan-kuitansi.md), [Rekonsiliasi](20-rekonsiliasi-dan-refund.md) |
| **Pimpinan (Rektor, Wakil Rektor, Pemilik)** | [Dashboard](03-dashboard-dan-menu.md), [Laporan](21-laporan-dan-ekspor.md) |
| **BAAK / akademik** | [Cekal Keuangan](19-cekal-keuangan.md) |
| **Auditor** | [Laporan](21-laporan-dan-ekspor.md), [Akses & Peran](02-akses-dan-peran.md) |
| **Administrator sistem / TI** | [Batasan & Integrasi](42-batasan-dan-integrasi.md), [Catatan Implementasi](../../keu/catatan-implementasi.md) |

---

## Daftar isi

Nomor dalam kurung menunjukkan butir isi manual yang diwajibkan (1–21).

### Bagian umum

1. [Pendahuluan, Ruang Lingkup, dan Daftar Fitur](01-pendahuluan.md) — (1) (2)
2. [Persyaratan Akses dan Hak Akses](02-akses-dan-peran.md) — (3)
3. [Dashboard Keuangan dan Struktur Menu](03-dashboard-dan-menu.md) — (4) (5)

### Bagian fitur

10. [Tarif dan Komponen Biaya](10-tarif-dan-komponen-biaya.md) — (6)
11. [Tagihan: Penerbitan Massal, Tagihan Manual, Tagihan SKS, Penyesuaian](11-tagihan.md) — (7)
12. [Pembayaran: Tunai, Transfer Manual, Online](12-pembayaran.md) — (8)
13. [Verifikasi Pembayaran dan Kuitansi](13-verifikasi-dan-kuitansi.md) — (9)
14. [Beasiswa, Potongan, dan Voucher](14-beasiswa-potongan-voucher.md) — (10)
15. [Denda dan Deposit](15-denda-dan-deposit.md) — (11)
16. [Golongan UKT](16-ukt.md) — (12)
17. [Cicilan](17-cicilan.md) — (13)
18. [Monitoring Piutang dan Tunggakan](18-piutang-dan-tunggakan.md) — (14)
19. [Cekal Keuangan dan Integrasi Akademik](19-cekal-keuangan.md) — (15)
20. [Rekonsiliasi dan Pengembalian Dana](20-rekonsiliasi-dan-refund.md) — (16)
21. [Laporan dan Ekspor Data](21-laporan-dan-ekspor.md) — (17)

### Bagian peran & lampiran

30. [Panduan Penggunaan Berdasarkan Peran](30-panduan-per-peran.md) — (18)
40. [Penanganan Masalah dan Pesan Kesalahan](40-masalah-dan-pesan-kesalahan.md) — (19)
41. [FAQ dan Prosedur Operasional](41-faq-dan-prosedur.md) — (20)
42. [Batasan Fitur, Dependensi, dan Integrasi yang Belum Aktif](42-batasan-dan-integrasi.md) — (21)

---

## Konvensi penulisan

- **Tebal** = nama menu, tab, tombol, atau kolom persis seperti di layar. Contoh: **Keuangan → Tagihan → Tagihan manual**.
- `kode` = nilai teknis (kode izin, kode status, nama berkas).
- Nominal ditulis dalam rupiah; sistem menghitung sampai sen sehingga tidak ada selisih pembulatan antarlaporan.
- "Petugas lain" berarti akun pengguna yang **berbeda** dari pembuat/pengaju — aturan pemisahan tugas (lihat [bab 2 §4](02-akses-dan-peran.md#4-pemisahan-tugas-maker-checker)).
