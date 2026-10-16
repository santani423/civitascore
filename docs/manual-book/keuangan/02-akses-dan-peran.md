# 2. Persyaratan Akses dan Hak Akses

[← Pendahuluan](01-pendahuluan.md) · [Daftar isi](README.md) · [Berikutnya: Dashboard & Menu →](03-dashboard-dan-menu.md)

## 1. Persyaratan akses

- Akun Civitas One yang aktif dan terdaftar sebagai anggota universitas (dibuat administrator kampus di **Pengaturan → Role Pengguna**).
- Peran yang memuat izin KEU (lihat §3). Menu **Keuangan** dan setiap sub-menunya **hanya tampil** bila akun memiliki izin baca untuk halaman tersebut; tombol aksi (setujui, verifikasi, ekspor, dll.) hanya tampil bila akun memiliki izin aksinya.
- Mahasiswa memakai akun portal mahasiswa yang **terhubung dengan data mahasiswa di SIAKAD**. Bila akun tidak terhubung, portal keuangan menampilkan pesan *"Akun ini tidak terhubung dengan data mahasiswa."* — hubungi BAAK.
- Peramban modern (Chrome, Edge, Firefox, Safari versi terbaru). Kuitansi dibuka sebagai PDF di tab baru; izinkan pop-up untuk alamat aplikasi.

Semua aturan akses **juga diperiksa di server**: menyembunyikan tombol hanyalah kenyamanan tampilan. Permintaan tanpa izin ditolak dengan pesan *"Anda tidak memiliki izin untuk mengakses resource ini."* (kode 403).

## 2. Izin (permission) KEU

Izin berbentuk `sumber.aksi`. Aksi standar: `read` (lihat), `create` (buat/ajukan), `update` (ubah/proses), `delete` (hapus), `approve` (setujui/tolak), `finalize` (tuntaskan/tutup), `export` (ekspor).

| Sumber | Untuk |
|---|---|
| `finance_dashboard` | Dashboard Keuangan |
| `fee_components`, `fee_rates` | Komponen biaya; tabel & baris tarif (`approve` = memberlakukan tabel tarif) |
| `invoices` | Tagihan (`create` tagihan manual & terbitkan draf, `update` hapus draf & catatan penagihan, `export` ekspor tagihan) |
| `billing_runs` | Penerbitan massal (`approve` = setujui & terbitkan) |
| `invoice_adjustments` | Penyesuaian tagihan (`approve` = setujui/tolak) |
| `installments` | Skema & pengajuan cicilan |
| `penalties` | Aturan denda & denda berjalan |
| `financial_aid`, `vouchers` | Beasiswa/potongan; voucher |
| `ukt` | Golongan & penetapan UKT |
| `payment_channels` | Kanal pembayaran |
| `payment_transactions` | Transaksi (`create` catat tunai, `update` batalkan/cek ulang, `approve` verifikasi bukti & balik transaksi) |
| `cashier_sessions` | Sesi kas (`create` buka/tutup/catat setoran, `approve` verifikasi setoran) |
| `reconciliations` | Rekonsiliasi (`finalize` = tutup batch) |
| `refunds` | Pengembalian dana (`approve` setujui/tolak, `finalize` catat transfer/gagal) |
| `student_deposits` | Saldo deposit (`update` = pakai deposit untuk tagihan) |
| `finance_clearance` | Cekal keuangan (`create` beri dispensasi, `update` aturan & evaluasi, `approve` buka cekal & putuskan dispensasi) |
| `finance_reports` | Laporan & ekspor |
| `finance_settings` | Pengaturan kebijakan KEU |
| `student_finance` | Layanan keuangan mandiri mahasiswa |
| `academic_blocks` | Status cekal untuk BAAK |

## 3. Peran bawaan

Peran berikut dibuat oleh seeder (`OrganizationalRoleSeeder`). Administrator kampus dapat menyalin dan menyesuaikannya di **Pengaturan → Role**.

| Peran | Nama di aplikasi | Ringkasan hak KEU |
|---|---|---|
| `finance_manager` | **Kepala Bagian Keuangan** | Semua izin KEU: menyetujui tarif, penerbitan, penyesuaian, aturan denda, beasiswa, refund, dispensasi; mengubah pengaturan & kanal. |
| `finance_administrator` | **Bagian Keuangan** (staf) | Semua izin baca; membuat tarif (draf), simulasi penerbitan, tagihan manual, pengajuan penyesuaian, cicilan, denda, beasiswa, voucher, UKT; memverifikasi bukti transfer & membalik transaksi; kasir; rekonsiliasi & menutup batch; mengajukan & mencatat transfer refund; dispensasi dalam batas petugas; ekspor. **Tidak** dapat menyetujui tarif, penerbitan massal, penyesuaian, aturan denda, beasiswa, refund, maupun mengubah pengaturan. |
| `finance_cashier` | **Kasir Keuangan** | Dashboard, lihat tagihan & transaksi, buka/tutup sesi kas, catat pembayaran tunai, catat setoran, lihat deposit. |
| `vice_rector` | Wakil Rektor | Semua izin baca + ekspor; menyetujui tarif, penerbitan, penyesuaian, aturan denda, beasiswa, refund, dispensasi. |
| `rector` | Rektor | Dashboard, laporan, ekspor. |
| `university_owner` | Pemilik/Yayasan | Dashboard & laporan. |
| `university_administrator` | Admin Universitas | Dashboard, laporan, kanal pembayaran, melihat pengaturan. |
| `auditor` | Auditor | Semua izin baca + ekspor (tanpa mengubah apa pun). |
| `academic_administrator` | BAAK | Melihat status cekal keuangan. |
| `student` | Mahasiswa | Portal keuangan (lihat & bayar milik sendiri). |

**Verifikator pembayaran** tidak dibuat sebagai peran tersendiri: verifikasi bukti membutuhkan `payment_transactions.read` + `payment_transactions.approve`, yang dimiliki Bagian Keuangan dan Kepala Bagian Keuangan. Bila kampus ingin memisahkan verifikator, buat peran baru berisi kedua izin tersebut.

## 4. Pemisahan tugas (maker-checker)

Sistem menolak tindakan berikut bila dilakukan oleh **akun yang sama** dengan pembuat/pengaju, dengan pesan *"… tidak dapat menyetujui … sendiri (pemisahan tugas)"* (kode `SELF_APPROVAL_FORBIDDEN`):

| Pembuat / pengaju | Tidak boleh |
|---|---|
| Penyusun/pengaju tabel tarif | Menyetujui atau mengembalikan tabel tarif itu |
| Pembuat simulasi penerbitan massal | Menyetujui/menolak penerbitan itu |
| Pengaju penyesuaian tagihan | Menyetujui/menolak penyesuaian itu |
| Penyusun/pengaju aturan denda | Menyetujui aturan itu |
| Pengaju penetapan beasiswa/potongan | Menyetujui/menolak penetapan itu |
| Pengaju cicilan khusus | Menyetujui/menolak cicilan itu |
| Pengaju refund | Menyetujui/menolak refund itu |
| Pengunggah bukti transfer (mahasiswa) | Memverifikasi buktinya sendiri |
| Pencatat (kasir) atau verifikator transaksi | Membalik transaksi itu |
| Kasir pemilik sesi kas | Memverifikasi setoran sesinya |
| Petugas yang mencocokkan manual pada rekonsiliasi | Menutup batch yang sama |
| Pengaju dispensasi | Memutuskan dispensasi itu |

Pastikan setiap kampus memiliki **minimal dua** akun keuangan dengan izin yang sesuai agar persetujuan dapat berjalan.

## 5. Data sensitif & jejak audit

- Nomor rekening penerima refund disimpan terenkripsi dan tampil **tersamar**. Petugas berizin `refunds.approve`/`refunds.finalize` dapat membuka nomor asli lewat ikon mata; pembukaan tersebut tercatat di **Pengaturan → Audit Log**.
- Ekspor data (tagihan, laporan) **wajib diberi alasan** dan tercatat di audit log.
- Persetujuan, penolakan, pembalikan, pembukaan cekal manual, perubahan pengaturan, dan perubahan kanal tercatat beserta pelaku dan waktunya.
- Mahasiswa hanya dapat mengakses data miliknya sendiri; mencoba membuka tagihan/kuitansi mahasiswa lain menghasilkan *"Data tidak ditemukan"* (404).
