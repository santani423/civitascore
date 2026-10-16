# 16. Golongan UKT

[← Denda & Deposit](15-denda-dan-deposit.md) · [Daftar isi](README.md) · [Berikutnya: Cicilan →](17-cicilan.md)

**Menu:** **Keuangan → UKT** (tab **Penetapan**, **Pengajuan peninjauan**, **Golongan**).

> Halaman menampilkan catatan: *instrumen penilaian kemampuan ekonomi (skoring otomatis) belum tersedia* — golongan ditetapkan petugas berdasarkan SK/hasil verifikasi.

Kampus yang tidak memakai skema UKT dapat mengabaikan menu ini (komponen **UKT** bawaan dalam keadaan nonaktif).

## 1. Golongan UKT

| | |
|---|---|
| **Tujuan** | Mendaftar golongan/kelompok UKT. Nominal per golongan diatur di **tabel tarif** (baris komponen kategori **UKT** dengan pembeda **Golongan UKT**, [bab 10](10-tarif-dan-komponen-biaya.md)). |
| **Siapa** | `ukt.create` / `ukt.update`. |

Tab **Golongan** → **Golongan**: isi **Kode**, **Nama**, **Tingkat** (1 = terendah), **Keterangan**, **Aktif**. Kolom **Mahasiswa aktif** menunjukkan jumlah mahasiswa yang saat ini ditetapkan pada golongan tersebut.

## 2. Penetapan golongan ke mahasiswa

| | |
|---|---|
| **Tujuan** | Menetapkan golongan UKT seorang/sekelompok mahasiswa. |
| **Siapa** | `ukt.create`. |
| **Data disiapkan** | SK penetapan UKT, daftar NIM. |

1. Tab **Penetapan** → **Tetapkan golongan**.
2. Cari dan klik mahasiswa satu per satu (yang terpilih tampil sebagai label; klik label untuk menghapus). Maksimal 500 mahasiswa per kali.
3. Pilih **Golongan**, **Berlaku mulai semester** (kosong = segera), isi **Nomor SK** dan **Dasar penetapan**. Klik **Simpan**.

**Aturan & hasil**

- Penetapan baru **menggantikan** penetapan sebelumnya (tetap tersimpan sebagai riwayat; filter **Riwayat → Tampilkan riwayat**).
- Tagihan memakai golongan yang **berlaku pada semester yang ditagih**: penetapan "mulai semester depan" tidak mengubah tagihan semester ini.
- Tagihan yang sudah terbit **tidak berubah**. Bila perlu, ajukan penyesuaian ([bab 11 §6](11-tagihan.md#6-penyesuaian-tagihan)).
- Mahasiswa tanpa golongan dilewati saat penerbitan komponen UKT dengan alasan *Golongan UKT belum ditetapkan*.
- Golongan nonaktif tidak dapat dipakai: *"Golongan UKT ini tidak aktif."*

## 3. Pengajuan peninjauan UKT

| | |
|---|---|
| **Tujuan** | Mahasiswa mengajukan perubahan golongan karena perubahan kemampuan ekonomi. |
| **Siapa** | Mahasiswa mengajukan (portal); petugas `ukt.approve` memutuskan. |

**Mahasiswa:** portal **Keuangan → tab UKT → Ajukan peninjauan UKT**; pilih **Golongan yang diharapkan** (opsional), isi **Alasan** (minimal 20 karakter), lampirkan **Bukti pendukung** (PDF/JPG/PNG, maks. 5 MB). Hanya satu pengajuan dapat diproses dalam satu waktu: *"Masih ada pengajuan perubahan UKT yang sedang diproses."*

**Petugas:** tab **Pengajuan peninjauan** (filter **Menunggu**) → unduh bukti (ikon berkas) → **Setujui** (pilih **Golongan baru**, **Berlaku mulai semester**, **Catatan keputusan**) atau **Tolak** (catatan wajib). Persetujuan otomatis membuat penetapan baru; mahasiswa diberi tahu hasilnya.
