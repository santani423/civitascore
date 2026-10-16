# 11. Tagihan: Penerbitan Massal, Tagihan Manual, Tagihan SKS, Penyesuaian

[← Tarif & Komponen](10-tarif-dan-komponen-biaya.md) · [Daftar isi](README.md) · [Berikutnya: Pembayaran →](12-pembayaran.md)

## 1. Daftar tagihan

| | |
|---|---|
| **Tujuan** | Mencari dan memantau seluruh tagihan mahasiswa. |
| **Siapa** | `invoices.read` (seluruh peran keuangan, kasir, auditor). |
| **Lokasi** | **Keuangan → Tagihan**. |

Filter: **Cari** (NIM, nama, nomor tagihan, periode), **ID mahasiswa**, **Status** (termasuk **Belum lunas (semua)**), **Kelompok** (Registrasi Semester, Biaya SKS, Insidental, Denda, Biaya Awal (PMB), Semester Pendek, Wisuda, Saldo Awal), **Semester**, **Prodi**, **Menunggak**, **Jatuh tempo dari/sampai**.

Kolom: nomor tagihan (dengan kelompok & periode), mahasiswa, neto, dibayar, sisa, jatuh tempo, status (+ label merah **Menunggak** bila lewat tenggang). Tagihan lama sebelum Modul KEU (mis. hasil daftar ulang PMB) juga tampil; nomornya ditandai **(lama)** bila belum bernomor.

**Nomor tagihan** berformat `TGH-<tahun><semester>-<urut>` dan unik per kampus.

## 2. Penerbitan tagihan massal

| | |
|---|---|
| **Tujuan** | Menerbitkan tagihan semester untuk banyak mahasiswa dari tabel tarif yang berlaku, tanpa tagihan ganda. |
| **Siapa** | Simulasi & ajukan: `billing_runs.create` (Bagian Keuangan). Setujui/tolak: `billing_runs.approve` (Kepala Bagian Keuangan, Wakil Rektor) — akun berbeda dari pembuat simulasi. |
| **Lokasi** | **Keuangan → Penerbitan Massal**. |
| **Data disiapkan** | Tabel tarif tahun akademik **sudah berlaku**; golongan UKT mahasiswa (bila memakai komponen UKT); beasiswa/potongan yang sudah disetujui; tanggal jatuh tempo resmi. |

**Langkah**

1. Klik **Simulasi baru**, isi:
   - **Semester**, **Kelompok tagihan** (Registrasi Semester, Insidental, atau Wisuda),
   - **Komponen biaya** (boleh lebih dari satu),
   - **Jatuh tempo** (tidak boleh sebelum hari ini),
   - opsional **Batasi program studi** dan **Batasi angkatan** (JSON, mis. `[2024, 2025]`).
2. Klik **Simpan**. Sistem menghitung setiap mahasiswa berstatus aktif (sesuai pengaturan *status mahasiswa yang menerima tagihan wajib*) dan membuka halaman hasil simulasi. **Belum ada tagihan yang dibuat.**
3. Periksa kartu ringkasan: **Mahasiswa dihitung**, **Siap terbit**, **Dilewati**, **Total bruto**, **Total neto**, serta **Alasan dilewati**. Tabel rincian per mahasiswa dapat difilter (Siap/Dilewati/Terbit/Gagal).
4. Bila sesuai, klik **Ajukan persetujuan**. Bila tidak, klik **Batalkan** dan buat simulasi baru.
5. Penyetuju membuka penerbitan → **Setujui & terbitkan**, atau **Tolak** dengan alasan.

**Hasil:** tagihan terbit untuk setiap mahasiswa berstatus *Siap*, mahasiswa menerima notifikasi *"Tagihan … telah terbit"*, dan kartu menampilkan *"Terbit n tagihan senilai … · dilewati … · gagal …"*. Perhitungan **diulang saat posting** sehingga perubahan (mis. beasiswa baru disetujui) ikut terhitung.

**Alasan mahasiswa dilewati**

| Alasan | Artinya | Tindakan |
|---|---|---|
| Belum ada tabel tarif berlaku | Tidak ada tabel berlaku untuk tahun akademik/angkatan mahasiswa | Berlakukan tabel tarif |
| Tarif tidak ditemukan | Tidak ada baris yang cocok untuk komponen tersebut | Tambah baris tarif (versi baru) |
| Sudah ditagihkan | Komponen yang sama sudah ditagih untuk semester & kelompok yang sama | Tidak perlu tindakan (mencegah tagihan ganda) |
| Golongan UKT belum ditetapkan | Komponen UKT tetapi mahasiswa belum punya golongan | Tetapkan golongan ([bab 16](16-ukt.md)) |
| Nominal nol | Tarif menghasilkan nol | Periksa tarif |

**Kondisi khusus**

- Simulasi tanpa satu pun mahasiswa *Siap* tidak dapat diajukan: *"Tidak ada tagihan yang siap diterbitkan pada simulasi ini."*
- Dua penyetuju yang menekan tombol bersamaan: hanya satu yang memproses; yang lain menerima *"Penerbitan ini sedang/sudah diproses penyetuju lain."*
- Bila satu mahasiswa gagal diproses, mahasiswa lain tetap terbit; baris gagal berstatus **Gagal** dengan pesan kesalahannya.
- Komponen berkategori **Biaya Cuti** saja otomatis menyasar mahasiswa berstatus **cuti**.

## 3. Tagihan manual / insidental

| | |
|---|---|
| **Tujuan** | Menagih biaya di luar jadwal (cetak ulang kartu, praktikum susulan, dsb.). |
| **Siapa** | `invoices.create`. |
| **Lokasi** | **Keuangan → Tagihan → Tagihan manual**. |

1. Cari dan pilih mahasiswa (minimal 3 karakter NIM/nama).
2. Pilih **Semester** (opsional), **Kelompok tagihan**, **Jatuh tempo**, opsional **Periode**.
3. Isi **Rincian biaya**: **Komponen** (opsional), **Uraian**, **Nominal**. Tambah baris dengan **Tambah baris**; total tampil otomatis.
4. Opsional **Catatan internal** (tidak terlihat mahasiswa).
5. Centang **Simpan sebagai draf** bila belum ingin diterbitkan, lalu klik **Terbitkan** / **Simpan draf**.

**Aturan:** jatuh tempo tidak boleh lewat; nominal setiap baris > 0; kelompok Denda dan Biaya Awal (PMB) tidak dapat dibuat manual (denda dibuat sistem, biaya awal dari PMB).

**Draf:** tidak terlihat mahasiswa dan tidak dihitung piutang. Buka detail draf → **Terbitkan** (mahasiswa diberi tahu) atau **Hapus draf** (status menjadi Dibatalkan).

## 4. Tagihan biaya SKS dari KRS

| | |
|---|---|
| **Tujuan** | Menagih biaya per SKS sesuai mata kuliah yang diambil. |
| **Siapa** | Otomatis oleh sistem. |
| **Syarat** | Komponen berkategori **Biaya per SKS** aktif; baris tarif **Per SKS** pada tabel berlaku; pengaturan *Terbitkan tagihan SKS otomatis saat KRS disetujui* = Ya. |

**Cara kerja:** saat dosen wali **menyetujui KRS** mahasiswa, sistem menghitung jumlah SKS berstatus terdaftar × tarif per SKS (memperhitungkan SKS minimum/maksimum) dan:

- menerbitkan **tagihan Biaya SKS** baru (jatuh tempo = hari ini + *jatuh tempo tagihan SKS*, bawaan 21 hari), atau
- bila sudah ada, membuat **penyesuaian otomatis** sebesar selisih SKS (tambah SKS → penambahan; kurang SKS → pengurangan, hanya sampai *batas tanggal pengurangan SKS* bila diisi).

Kegagalan menghitung tagihan SKS **tidak membatalkan** persetujuan KRS; petugas dapat memeriksa tagihan mahasiswa tersebut.

## 5. Detail tagihan

Klik nomor tagihan. Halaman menampilkan:

- **Bruto, Potongan, Penyesuaian, Dibayar, Sisa**; identitas mahasiswa; status; jatuh tempo; tanggal terbit/lunas; menunggak sejak; sumber; tagihan induk (untuk tagihan denda); alasan pembatalan; catatan.
- Tab **Rincian** (baris biaya, potongan bernilai negatif berwarna hijau, tagihan denda terkait), **Pembayaran** (alokasi: tanggal, jenis, metode, referensi transaksi, status *Tercatat*/*Dibalik*), **Cicilan** (jadwal termin), **Penyesuaian** (riwayat penyesuaian dengan pengaju & pemutus).

Tombol aksi (tampil sesuai status & izin): **Terbitkan**, **Hapus draf**, **Ajukan penyesuaian**, **Cicilan**, **Pakai voucher**, **Bayar dari deposit**, **Batalkan jadwal** (tab Cicilan).

## 6. Penyesuaian tagihan

| | |
|---|---|
| **Tujuan** | Mengubah tagihan yang **sudah terbit** secara terkendali. Tagihan terbit tidak pernah diedit langsung. |
| **Siapa** | Ajukan: `invoice_adjustments.create`. Setujui/tolak: `invoice_adjustments.approve` — akun berbeda dari pengaju. |
| **Lokasi** | Detail tagihan → **Ajukan penyesuaian**; antrean di **Keuangan → Penyesuaian Tagihan**. |

**Jenis penyesuaian**

| Jenis | Efek |
|---|---|
| **Penambahan** | Menambah nilai tagihan |
| **Pengurangan** | Mengurangi nilai tagihan (koreksi tarif, beasiswa susulan) |
| **Pembebasan** | Membebaskan sebagian/seluruh kewajiban; bila seluruhnya dan belum ada pembayaran, status menjadi **Dibebaskan** |
| **Pembebasan Denda** | Hanya untuk tagihan kelompok Denda |
| **Pembatalan Tagihan** | Membatalkan seluruh tagihan (status **Dibatalkan**) |

**Langkah**

1. Detail tagihan → **Ajukan penyesuaian**. Pilih **Jenis**, isi **Nominal** (tidak perlu untuk pembatalan), **Kode alasan** (mis. `KOREKSI_TARIF`), dan **Penjelasan** (minimal 10 karakter). Klik **Simpan** → status *Menunggu persetujuan*.
2. Penyetuju membuka **Keuangan → Penyesuaian Tagihan** (filter *Menunggu persetujuan*) atau tab Penyesuaian di detail tagihan → **Setujui** atau **Tolak** (alasan wajib).
3. Setelah disetujui, tagihan dihitung ulang dan status penyesuaian menjadi **Diterapkan**.

**Aturan & validasi**

- Hanya satu penyesuaian menunggu per tagihan: *"Masih ada penyesuaian lain yang menunggu persetujuan untuk tagihan ini."*
- Pengurangan tidak boleh melebihi nilai tagihan saat ini.
- **Pembatalan ditolak** bila tagihan sudah menerima pembayaran atau ada transaksi berjalan: *"Tagihan yang sudah menerima pembayaran atau memiliki transaksi berjalan tidak dapat dibatalkan; gunakan penyesuaian turun (BR-KEU-12)."*
- Tagihan berstatus Draf, Dibatalkan, atau Dibebaskan tidak dapat disesuaikan.
- **Pengurangan atas tagihan yang sudah dibayar**: kelebihan bayar otomatis dipindahkan ke **deposit** mahasiswa (tercatat sebagai "Kelebihan ke deposit" pada tab Pembayaran dan "Kredit Penyesuaian" di mutasi deposit).
- Bila tagihan dicicil, termin yang belum lunas disesuaikan otomatis (dari termin terakhir) agar jumlah termin tetap sama dengan sisa tagihan.
- Pengaju dapat membatalkan pengajuannya yang belum diputuskan.
