# 14. Beasiswa, Potongan, dan Voucher

[← Verifikasi & Kuitansi](13-verifikasi-dan-kuitansi.md) · [Daftar isi](README.md) · [Berikutnya: Denda & Deposit →](15-denda-dan-deposit.md)

**Menu:** **Keuangan → Beasiswa, Potongan & Voucher** (tab **Penetapan mahasiswa**, **Jenis beasiswa/potongan**, **Voucher**).

> Menu **Program Beasiswa** (modul Beasiswa yang sudah ada) mengelola program & seleksi penerima beasiswa. Menu KEU ini mengelola **potongan pada tagihan**. Jenis potongan KEU dapat ditautkan ke program beasiswa.

## 1. Jenis beasiswa/potongan

| | |
|---|---|
| **Tujuan** | Mendefinisikan aturan potongan yang dapat ditetapkan ke mahasiswa. |
| **Siapa** | Buat: `financial_aid.create`; ubah: `financial_aid.update`. |
| **Lokasi** | Tab **Jenis beasiswa/potongan** → **Jenis** / **Ubah**. |

Isian: **Kode**, **Nama**, **Jenis** (Beasiswa, Potongan, Subsidi, KIP Kuliah), **Pemberi dana**, **Cara hitung** (Persentase, Nominal, Penuh (100%)), **Nilai**, **Batas maksimal per komponen biaya**, **Komponen yang dipotong** (kosong = semua komponen yang *boleh mendapat potongan*), **Prioritas** (1 = diterapkan pertama), **Dapat digabung dengan potongan lain**, **Aktif**, **Keterangan**.

**Cara sistem menghitung potongan** (saat tagihan diterbitkan):

- Potongan dihitung **per komponen biaya** yang memenuhi syarat. Nilai **Nominal** dan **Batas maksimal** berlaku untuk **setiap** komponen yang dipotong — bila beasiswa nominal hanya untuk satu komponen, isi **Komponen yang dipotong**.
- Beberapa potongan diterapkan berurutan menurut **Prioritas**. Mode bawaan *sequential*: persentase dihitung atas sisa setelah potongan sebelumnya.
- Bila mahasiswa memiliki lebih dari satu potongan yang **tidak dapat digabung**, hanya yang **terbesar** yang dipakai.
- Potongan tidak pernah membuat komponen bernilai negatif.

Perubahan jenis hanya berlaku untuk penetapan & tagihan **baru**.

## 2. Penetapan beasiswa/potongan ke mahasiswa

| | |
|---|---|
| **Tujuan** | Memberikan potongan kepada mahasiswa tertentu untuk rentang semester tertentu. |
| **Siapa** | Ajukan: `financial_aid.create`. Setujui/tolak/cabut: `financial_aid.approve` — bukan pengaju yang sama. |
| **Data disiapkan** | SK/surat penetapan beasiswa, dokumen pendukung (PDF/JPG/PNG maks. 5 MB). |

**Langkah**

1. Tab **Penetapan mahasiswa** → **Penetapan**.
2. Cari & pilih mahasiswa, pilih **Jenis**, opsional ganti **Cara hitung**/**Nilai**/**Batas maksimal** (kosong = ikut jenis), **Mulai semester**, **Sampai semester** (kosong = seterusnya), isi **Dasar penetapan** (nomor SK), unggah **Dokumen pendukung**. Klik **Simpan** → status *Menunggu persetujuan*.
3. Penyetuju klik **Setujui** (status **Aktif**) atau **Tolak** (alasan wajib).

**Hasil:** potongan otomatis masuk pada **tagihan yang diterbitkan berikutnya** dalam rentang semester. Mahasiswa melihat penetapannya di portal tab **Beasiswa/potongan**.

**Menerapkan ke tagihan yang sudah terbit:** pada penetapan aktif klik **Terapkan ke tagihan**, pilih tagihan → sistem membuat **penyesuaian pengurangan** yang menunggu persetujuan ([bab 11 §6](11-tagihan.md#6-penyesuaian-tagihan)). Bila tagihan sudah dibayar, kelebihannya masuk deposit setelah disetujui.

**Mencabut:** **Cabut** + alasan. Berlaku untuk tagihan berikutnya; potongan pada tagihan yang sudah terbit tidak berubah otomatis (gunakan penyesuaian bila perlu).

**Kondisi khusus**

- Satu mahasiswa tidak dapat memiliki dua penetapan aktif/menunggu untuk jenis yang sama: *"Mahasiswa ini sudah memiliki penetapan aktif/menunggu untuk jenis yang sama."*
- Persentase di atas 100 ditolak.
- Penetapan yang sudah diterapkan ke tagihan yang sama tidak dapat diterapkan lagi.

## 3. Voucher

| | |
|---|---|
| **Tujuan** | Potongan berbasis kode (promosi, program khusus) yang dipakai sendiri oleh mahasiswa atau oleh petugas. |
| **Siapa** | Buat: `vouchers.create`; ubah, pakaikan, batalkan pemakaian: `vouchers.update`; mahasiswa: portal. |
| **Lokasi** | Tab **Voucher**; mahasiswa: portal → detail tagihan → **Pakai kode voucher**; petugas: detail tagihan → **Pakai voucher**. |

**Membuat voucher:** **Voucher** → isi **Kode** (4–30 karakter huruf/angka/tanda hubung), **Nama**, **Cara hitung**, **Nilai**, **Potongan maksimal**, **Minimal nilai tagihan**, **Berlaku untuk kelompok tagihan**, **Batasan penerima** (JSON, mis. `{"study_program_ids":["…"],"admission_years":[2026]}`), **Kuota total** (kosong = tak terbatas), **Batas per mahasiswa**, **Berlaku mulai/sampai**, **Status** (Aktif, Dijeda, Diarsipkan).

**Aturan pemakaian** (diperiksa server):

| Syarat | Pesan bila tidak terpenuhi |
|---|---|
| Kode dikenal | *"Kode voucher tidak dikenal."* |
| Voucher aktif & dalam masa berlaku | *"Voucher tidak aktif atau di luar masa berlaku."* |
| Kuota masih ada | *"Kuota voucher sudah habis."* |
| Belum melewati batas per mahasiswa | *"Batas pemakaian voucher untuk Anda sudah tercapai."* |
| Tagihan **belum dibayar sama sekali** | *"Voucher hanya dapat dipakai pada tagihan yang belum dibayar."* |
| Belum lewat jatuh tempo | *"Tagihan sudah melewati jatuh tempo."* |
| Tidak ada transaksi berjalan (instruksi transfer/online) | *"Batalkan dahulu transaksi pembayaran yang sedang berjalan untuk tagihan ini."* |
| Kelompok tagihan, prodi/angkatan/jalur, minimal tagihan sesuai | *"Voucher tidak berlaku untuk …"* / *"Nilai tagihan belum memenuhi syarat minimal voucher."* |

Percobaan kode yang salah berulang (bawaan 5 kali) memblokir pemakaian voucher oleh akun tersebut selama **30 menit**: *"Terlalu banyak percobaan kode voucher yang salah. Coba lagi dalam 30 menit."*

**Hasil:** tagihan dikurangi (tercatat sebagai penyesuaian *Voucher*), kuota bertambah satu. Kolom **Kuota** menunjukkan *terpakai / total*; tombol **Pemakaian** menampilkan daftar penukaran.

**Membatalkan pemakaian:** di daftar **Pemakaian**, klik **Batalkan** — hanya bila tagihan belum menerima pembayaran. Kuota dikembalikan. Voucher yang sudah dipakai hanya dapat diubah nama, status, masa berlaku, dan kuotanya.
