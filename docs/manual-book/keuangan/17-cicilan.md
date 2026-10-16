# 17. Cicilan

[← UKT](16-ukt.md) · [Daftar isi](README.md) · [Berikutnya: Piutang & Tunggakan →](18-piutang-dan-tunggakan.md)

**Menu:** **Keuangan → Cicilan** (tab **Pengajuan** dan **Skema cicilan**); detail tagihan → **Cicilan**; portal → detail tagihan → **Ajukan cicilan**.

## 1. Skema cicilan

| | |
|---|---|
| **Tujuan** | Menyediakan pola cicilan standar yang dapat dipilih mahasiswa. |
| **Siapa** | Buat: `installments.create`; ubah: `installments.update`. |

Tab **Skema cicilan** → **Skema**: isi **Kode**, **Nama**, **Berlaku untuk kelompok tagihan**, **Termin** (JSON), opsional **Pembayaran pertama minimal**, centang **Disetujui otomatis bila memakai skema ini**, **Syarat: tidak ada tunggakan pada tagihan lain**, **Aktif**.

Contoh isian **Termin**:

```json
[{"percent": 40, "due_offset_days": 14}, {"percent": 30, "due_offset_days": 45}, {"percent": 30, "due_offset_days": 75}]
```

`due_offset_days` = hari sejak tagihan terbit; dapat diganti `due_date` (tanggal tetap, `YYYY-MM-DD`). Jumlah persen **harus 100**; jumlah termin 2–12.

`php artisan keu:install` membuat skema contoh **CICIL3** (40/30/30) dalam keadaan **nonaktif**. Perubahan skema tidak mengubah jadwal yang sudah berjalan.

## 2. Pengajuan cicilan

| | |
|---|---|
| **Tujuan** | Membagi sisa tagihan menjadi beberapa termin dengan jatuh tempo masing-masing. |
| **Siapa** | Mahasiswa (portal) atau petugas `installments.create` atas nama mahasiswa. Persetujuan cicilan khusus: `installments.approve`, bukan pengaju yang sama. |

**Mahasiswa:** buka tagihan yang belum lunas → **Ajukan cicilan** → pilih **Skema** → opsional **Alasan** → **Simpan**. Tombol hanya muncul bila ada skema aktif untuk kelompok tagihan tersebut dan tagihan belum dicicil.

**Petugas:** detail tagihan → **Cicilan** → pilih **Skema**, atau isi **Termin khusus (JSON)** beserta **Alasan** (wajib untuk termin khusus). Bila tagihan sudah memiliki jadwal, dialog menjadi **Penjadwalan ulang cicilan** (wajib termin khusus).

**Hasil**

- Skema dengan **disetujui otomatis**: jadwal termin langsung dibuat (*"Cicilan disetujui; jadwal termin tersedia."*).
- Termin khusus atau skema tanpa persetujuan otomatis: status **Menunggu**; petugas lain memutuskan di tab **Pengajuan** (**Setujui** / **Tolak** dengan alasan). Mahasiswa diberi tahu keputusannya.
- Jumlah termin = sisa tagihan (pembayaran yang sudah ada diperhitungkan); termin terakhir menyerap selisih pembulatan.
- Penjadwalan ulang menyimpan jadwal lama sebagai riwayat (*digantikan*); pembayaran termin lama tetap diakui.

**Aturan & validasi**

| Kondisi | Pesan |
|---|---|
| Tagihan lunas/dibatalkan | *"Tagihan ini tidak memiliki sisa kewajiban untuk dicicil."* |
| Ada pengajuan lain menunggu | *"Masih ada pengajuan cicilan yang menunggu keputusan untuk tagihan ini."* |
| Sudah ada jadwal (pengajuan baru) | *"Tagihan ini sudah memiliki jadwal cicilan aktif. Ajukan penjadwalan ulang bila perlu."* |
| Skema mensyaratkan bebas tunggakan | *"Cicilan tidak dapat diajukan karena masih ada tunggakan pada tagihan lain (BR-KEU-30)."* |
| Persen tidak 100 / termin < 2 atau > 12 | *"Jumlah persentase termin harus 100%."* / *"Cicilan terdiri atas 2 sampai 12 termin."* |
| Termin khusus tanpa alasan (mahasiswa) | *"Pengajuan cicilan khusus wajib disertai alasan."* |

## 3. Membayar termin & tunggakan termin

- Mahasiswa memilih termin pada **Bayar untuk** (portal) atau kasir memilih **↳ Termin n**. Pembayaran yang melebihi termin terpilih otomatis mengisi termin berikutnya.
- Termin lewat jatuh tempo + tenggang ditandai **Terlambat**; tagihan ditandai **Menunggak**; denda (bila ada aturan aktif) dihitung **per termin**.
- Cekal dengan syarat **Termin Pertama Lunas** atau **Kewajiban Jatuh Tempo Lunas** memperhitungkan jadwal termin ([bab 19](19-cekal-keuangan.md)).

## 4. Membatalkan jadwal

Detail tagihan → tab **Cicilan** → **Batalkan jadwal** + alasan (`installments.approve`). Tagihan kembali ke jatuh tempo semula; pembayaran termin yang sudah masuk tetap tercatat.
