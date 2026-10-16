# 1. Pendahuluan, Ruang Lingkup, dan Daftar Fitur

[Daftar isi](README.md) · [Berikutnya: Akses & Peran →](02-akses-dan-peran.md)

## 1. Tujuan Modul KEU

Modul **Administrasi Keuangan Mahasiswa (KEU)** mengelola seluruh kewajiban keuangan mahasiswa sejak tagihan diterbitkan sampai dana diterima, dicocokkan dengan rekening bank, dan dilaporkan. Tujuannya:

1. **Tagihan tepat** — nominal dihitung dari tabel tarif resmi yang berversi, bukan diketik manual, dan tidak pernah tertagih dua kali.
2. **Pembayaran tercatat lengkap** — setiap rupiah yang masuk (tunai, transfer, online, deposit) punya transaksi, kuitansi bernomor, dan alokasi ke tagihan.
3. **Kontrol internal** — perubahan penting (tarif, penerbitan massal, penyesuaian, refund, pembalikan) wajib disetujui petugas lain dan tercatat di log audit.
4. **Terhubung dengan akademik** — data mahasiswa, prodi, semester, dan KRS diambil dari SIAKAD; kewajiban yang belum dipenuhi dapat **mencekal** KRS dan ujian secara otomatis, dan cekal dicabut otomatis begitu dibayar.
5. **Transparan bagi mahasiswa** — mahasiswa melihat tagihan, membayar, mengunggah bukti, mengajukan cicilan/dispensasi/refund dari portal.

## 2. Konsep kunci

| Istilah | Arti |
|---|---|
| **Komponen biaya** | Jenis biaya (BPP, UKT, biaya per SKS, DPP, praktikum, wisuda, cuti, administrasi, …). |
| **Tabel tarif** | Daftar nominal per komponen untuk satu tahun akademik, dapat dibedakan per prodi, angkatan, golongan UKT, jalur masuk, kelas, dan tingkat semester. Berversi; versi yang sudah berlaku tidak dapat diubah. |
| **Tagihan** | Kewajiban satu mahasiswa. Nilai **bruto** dikurangi **potongan** (beasiswa/voucher) ditambah/dikurangi **penyesuaian** = **neto**. |
| **Transaksi** | Uang yang diterima (tunai, transfer manual, online) atau dipakai dari deposit. Satu transaksi dapat dialokasikan ke tagihan atau termin. |
| **Alokasi** | Bagian transaksi yang melunasi tagihan/termin tertentu. |
| **Deposit** | Saldo titipan mahasiswa: kelebihan bayar, koreksi tagihan, atau titipan. Dapat dipakai untuk tagihan berikutnya atau dikembalikan (refund). |
| **Termin** | Bagian tagihan yang dicicil, dengan jatuh tempo sendiri. |
| **Tunggakan** | Sisa tagihan/termin yang sudah lewat jatuh tempo **dan** masa tenggang (bawaan 3 hari). |
| **Cekal keuangan** | Pembatasan layanan akademik (KRS, ujian) karena kewajiban keuangan belum dipenuhi. Disimpan dan ditegakkan SIAKAD. |
| **Dispensasi** | Izin sementara memakai layanan yang dicekal, per layanan, dengan batas waktu. |
| **Rekonsiliasi** | Pencocokan transaksi di sistem dengan mutasi rekening bank, laporan penyedia, atau setoran kas. |

### 2.1 Tiga lapis status

KEU memisahkan tiga status agar tidak tertukar:

| Lapis | Status yang mungkin |
|---|---|
| **Tagihan** | Draf, Belum Dibayar, Dibayar Sebagian, Lunas, Dibatalkan, Dibebaskan (+ penanda **Menunggak**) |
| **Transaksi** | Dibuat, Menunggu pembayaran, Menunggu verifikasi, Berhasil, Gagal, Kedaluwarsa, Dibatalkan, Dibalik, Dikembalikan |
| **Rekonsiliasi transaksi** | Tidak perlu, Menunggu setoran, Belum direkonsiliasi, Cocok, Pengecualian, Diselesaikan |

Contoh: transfer yang sudah diverifikasi berstatus transaksi **Berhasil**, tagihannya **Lunas**, tetapi rekonsiliasinya masih **Belum direkonsiliasi** sampai dicocokkan dengan mutasi bank.

## 3. Ruang lingkup dan daftar fitur

| # | Fitur | Status di aplikasi | Bab |
|---|---|---|---|
| 1 | Dashboard keuangan (tagihan, penerimaan, piutang, tunggakan, aging, antrean kerja) | Tersedia | [3](03-dashboard-dan-menu.md) |
| 2 | Komponen biaya & tabel tarif berversi dengan persetujuan, deteksi konflik, pratinjau | Tersedia | [10](10-tarif-dan-komponen-biaya.md) |
| 3 | Penerbitan tagihan massal (simulasi → persetujuan → terbit) tanpa tagihan ganda | Tersedia | [11](11-tagihan.md) |
| 4 | Tagihan manual/insidental & draf | Tersedia | [11](11-tagihan.md) |
| 5 | Tagihan biaya SKS otomatis saat KRS disetujui dosen wali | Tersedia | [11](11-tagihan.md) |
| 6 | Penyesuaian tagihan (naik, turun, pembebasan, pembebasan denda, pembatalan) dengan persetujuan | Tersedia | [11](11-tagihan.md) |
| 7 | Pembayaran tunai di loket dengan sesi kas, kuitansi PDF, setoran & verifikasi setoran | Tersedia | [12](12-pembayaran.md) |
| 8 | Transfer manual dengan kode unik, unggah bukti, verifikasi | Tersedia | [12](12-pembayaran.md), [13](13-verifikasi-dan-kuitansi.md) |
| 9 | Pembayaran online (VA/QRIS) | **Hanya aktif bila penyedia dikonfigurasi** | [12](12-pembayaran.md), [42](42-batasan-dan-integrasi.md) |
| 10 | Pembayaran sebagian, alokasi ke termin, kelebihan otomatis ke deposit | Tersedia | [12](12-pembayaran.md) |
| 11 | Pembalikan transaksi dengan pemisahan tugas | Tersedia | [13](13-verifikasi-dan-kuitansi.md) |
| 12 | Beasiswa/potongan per mahasiswa & voucher kode | Tersedia | [14](14-beasiswa-potongan-voucher.md) |
| 13 | Denda keterlambatan (berjalan → ditagihkan) | Tersedia | [15](15-denda-dan-deposit.md) |
| 14 | Deposit (saldo titipan) | Tersedia | [15](15-denda-dan-deposit.md) |
| 15 | Golongan UKT, penetapan, peninjauan | Tersedia (tanpa instrumen skoring) | [16](16-ukt.md) |
| 16 | Cicilan (skema standar & khusus, jadwal ulang) | Tersedia | [17](17-cicilan.md) |
| 17 | Monitoring piutang & tunggakan, kartu piutang, catatan penagihan | Tersedia | [18](18-piutang-dan-tunggakan.md) |
| 18 | Cekal keuangan untuk KRS & ujian, dispensasi, buka manual, riwayat | Tersedia (KRS & ujian) | [19](19-cekal-keuangan.md) |
| 19 | Rekonsiliasi mutasi bank / settlement / setoran kas | Tersedia (impor CSV) | [20](20-rekonsiliasi-dan-refund.md) |
| 20 | Pengembalian dana (refund) dari deposit | Tersedia (transfer dicatat manual) | [20](20-rekonsiliasi-dan-refund.md) |
| 21 | 13 jenis laporan + ekspor CSV beralasan | Tersedia | [21](21-laporan-dan-ekspor.md) |
| 22 | Portal keuangan mahasiswa | Tersedia | [30](30-panduan-per-peran.md#1-mahasiswa) |
| 23 | Pengaturan kebijakan & kanal pembayaran dengan kuota paket | Tersedia | [3](03-dashboard-dan-menu.md#4-pengaturan-keuangan) |

Di luar cakupan versi ini (lihat [bab 42](42-batasan-dan-integrasi.md)): integrasi bank host-to-host, refund otomatis lewat penyedia, klaim ke pemberi beasiswa, tutup buku & jurnal akuntansi, surat bebas tanggungan PDF, portal orang tua, pembatasan data per fakultas/prodi, ekspor XLSX/PDF laporan.

## 4. Alur besar satu semester

1. **Persiapan** (Kepala Bagian Keuangan): periksa komponen biaya → susun tabel tarif tahun akademik → ajukan → disetujui pejabat lain → berlaku. Atur skema cicilan, aturan denda, aturan cekal, jenis beasiswa, rekening penerimaan transfer.
2. **Penerbitan**: staf membuat **simulasi penerbitan massal** → periksa hasil → ajukan → penyetuju menerbitkan. Mahasiswa menerima notifikasi tagihan.
3. **KRS**: saat KRS disetujui dosen wali, tagihan biaya SKS terbit/disesuaikan otomatis (bila tarif per SKS dipakai).
4. **Pembayaran**: mahasiswa membayar tunai di loket, transfer manual (unggah bukti), atau online (bila aktif). Verifikator memeriksa bukti. Kelebihan masuk deposit.
5. **Pengendalian**: denda dihitung harian, tunggakan ditandai, cekal KRS/ujian terbit dan tercabut otomatis, dispensasi diberikan bila perlu.
6. **Rekonsiliasi**: mutasi rekening diimpor dan dicocokkan; setoran kas loket diverifikasi.
7. **Pelaporan**: dashboard & laporan untuk pimpinan; ekspor CSV beralasan.
