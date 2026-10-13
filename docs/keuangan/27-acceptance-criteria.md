# 27 — Acceptance Criteria

> **Status:** rancangan. Disusun 2026-10-13.
> Kembali ke [README](README.md) · Test case: [26](26-skenario-pengujian.md) · Definition of Done: [28 §7](28-roadmap-implementasi.md#7-definition-of-done-umum)

Dokumen ini menetapkan kriteria penerimaan terukur `KEU-AC-<AREA>-NN` untuk setiap modul, beserta **bukti** yang wajib tersedia sebelum fitur dinyatakan selesai. Kriteria dinyatakan sebagai pernyataan yang dapat diuji benar/salah.

## Daftar Isi

1. [Bukti Pengujian yang Diwajibkan](#1-bukti-pengujian-yang-diwajibkan)
2. [Kriteria Umum (Semua Fitur)](#2-kriteria-umum-semua-fitur)
3. [Checklist per Modul](#3-checklist-per-modul)
4. [Prosedur Penerimaan](#4-prosedur-penerimaan)

---

## 1. Bukti Pengujian yang Diwajibkan

| Kode bukti | Bentuk | Keterangan |
|---|---|---|
| **B-AUTO** | Laporan CI (`php artisan test`) dengan nama test yang menyebut ID TC/BR | Semua TC P1 area fitur lulus |
| **B-CONC** | Log uji konkurensi pada MySQL (jumlah ulangan, hasil per ulangan) | Untuk kriteria bertanda konkurensi |
| **B-INV** | Keluaran `finance:verify-integrity` tanpa pelanggaran pada data uji | Setelah suite fitur dijalankan |
| **B-API** | Contoh permintaan/respons (koleksi API) termasuk kasus galat | Untuk endpoint baru |
| **B-UI** | Tangkapan layar desktop & ponsel (empty/loading/error/sukses) | Untuk halaman baru |
| **B-AUD** | Contoh baris `audit_logs` & `finance_status_histories` untuk tindakan sensitif | Untuk fitur dengan persetujuan/koreksi |
| **B-UAT** | Berita acara UAT ditandatangani pemilik proses (bagian keuangan) | Per fase |
| **B-SEC** | Hasil uji keamanan terarah (IDOR, tenant, SoD, webhook) | Fase 4–5 |
| **B-PERF** | Hasil uji kinerja (waktu, sumber daya) | Untuk kriteria kinerja |

## 2. Kriteria Umum (Semua Fitur)

| No | Kriteria | Bukti |
|---|---|---|
| U-1 | Setiap aturan `KEU-BR-*` milik fitur memiliki minimal satu test otomatis yang lulus | B-AUTO |
| U-2 | Setiap transisi status menulis `finance_status_histories` dan `audit_logs` dalam transaksi yang sama | B-AUTO, B-AUD |
| U-3 | Pengguna hanya dapat melihat dan mengubah data sesuai kewenangan dan cakupannya | B-AUTO (`RBAC`, `SEC`) |
| U-4 | Tidak ada data finansial terverifikasi yang dihapus atau diubah nominalnya | B-AUTO (`KEU-TC-REC-006`) |
| U-5 | Uang di API berupa string desimal; tidak ada float dalam perhitungan | B-AUTO (`KEU-TC-API-003`), Larastan |
| U-6 | Larastan, Pint, build & lint frontend bersih | CI |
| U-7 | Dokumen KEU diperbarui bila implementasi menyimpang | Tinjauan PR |

## 3. Checklist per Modul

### 3.1 Master (MST)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-MST-01 | Komponen, rekening, dan periode dapat dikelola; komponen/rekening yang dirujuk tidak dapat dihapus; nomor rekening tersimpan terenkripsi dan tampil termasking | B-AUTO, B-UI | MST-001…004 |
| KEU-AC-MST-02 | 50 dokumen yang dibuat bersamaan memperoleh nomor unik tanpa duplikat | B-CONC | MST-005 |
| KEU-AC-MST-03 | Seluruh kunci [04 §5.2](04-master-data-dan-konfigurasi.md#52-katalog-kunci) tersedia, tervalidasi, diaudit; kunci sensitif hanya bendahara; perubahan tidak mengubah data terbit | B-AUTO, B-AUD | MST-007 |
| KEU-AC-MST-04 | Pembulatan *half up* ke rupiah penuh konsisten di semua kalkulator | B-AUTO | MST-006 |

### 3.2 Tarif (TAR)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-TAR-01 | Jadwal tarif hanya dapat dipakai setelah disetujui (oleh orang berbeda) dan dipublikasikan; jadwal terpublikasi tidak dapat diubah | B-AUTO, B-AUD | TAR-005, TAR-007 |
| KEU-AC-TAR-02 | Resolusi tarif deterministik dan respons pratinjau menyebut aturan terpilih, skor, dan kandidat | B-AUTO, B-API | TAR-001, TAR-002 |
| KEU-AC-TAR-03 | Konflik setara dan aturan identik terblokir sebelum publikasi | B-AUTO | TAR-004, TAR-008, TAR-009 |
| KEU-AC-TAR-04 | Komponen wajib tanpa tarif tidak menghasilkan tagihan sebagian dan dilaporkan per mahasiswa | B-AUTO | TAR-003 |
| KEU-AC-TAR-05 | Revisi tarif tidak mengubah satu pun baris tagihan terbit (dibandingkan *checksum* baris sebelum/sesudah) | B-AUTO | TAR-006 |
| KEU-AC-TAR-06 | Seluruh contoh [05 §9](05-rancangan-tarif-uang-kuliah.md#9-contoh-konfigurasi-dan-perhitungan) direproduksi oleh test otomatis dengan hasil persis sama | B-AUTO | TAR-001, TAR-002 |

### 3.3 Tagihan (INV)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-INV-01 | Sistem menghasilkan tagihan dengan komponen biaya yang benar berdasarkan konfigurasi yang berlaku, termasuk kelayakan status mahasiswa; draf tidak terlihat mahasiswa; tagihan bernilai nol langsung lunas | B-AUTO | INV-001, INV-016, INV-003, INV-009 |
| KEU-AC-INV-02 | Total tagihan dapat ditelusuri ke rincian komponen, potongan, denda terkait, dan pembayaran (riwayat lengkap) | B-AUTO, B-UI | INV-001, PAY-001 |
| KEU-AC-INV-03 | Tagihan massal melalui pratinjau, dapat diulang tanpa duplikat, dan menolak eksekusi paralel | B-AUTO, B-CONC, B-PERF (10.000 tagihan < 15 menit) | INV-005, INV-006, INV-002 |
| KEU-AC-INV-04 | Tagihan per SKS memakai SKS `enrolled`; perubahan SKS menghasilkan susulan tanpa duplikat | B-AUTO | INV-004, INV-010 |
| KEU-AC-INV-05 | Pembatalan & koreksi hanya lewat penyesuaian berizin, dengan efek berantai, tanpa menghapus histori; mahasiswa berdata keuangan tidak dapat dihapus | B-AUTO, B-AUD | INV-007, INV-008, INV-011, INV-015, INV-014 |
| KEU-AC-INV-06 | Sengketa dapat diajukan dan diputus; satu sengketa aktif per tagihan | B-AUTO | INV-013 |
| KEU-AC-INV-07 | Posting tagihan eksternal idempoten | B-AUTO | INV-012 |
| KEU-AC-INV-08 | Endpoint dan field lama tetap berfungsi | B-AUTO | API-006 |

### 3.4 Pembayaran Umum (PAY)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-PAY-01 | Alokasi tidak pernah melebihi sisa tagihan maupun dana tersedia, hanya ke tagihan mahasiswa yang sama, mengikuti strategi FIFO, dan mendukung pembayaran sebagian sesuai pengaturan | B-AUTO | PAY-004, PAY-005, PAY-009, PAY-002, PAY-006 |
| KEU-AC-PAY-02 | Kelebihan bayar tercatat sebagai dana belum diterapkan dan dapat dialokasikan, dikonversi, atau dikembalikan | B-AUTO | PAY-003, DEP-001 |
| KEU-AC-PAY-03 | Setiap pembayaran terverifikasi memiliki tepat satu kuitansi aktif | B-AUTO, B-UI | PAY-001 |
| KEU-AC-PAY-04 | Alokasi bersamaan pada tagihan yang sama tidak melanggar invarian (20 ulangan) | B-CONC, B-INV | PAY-007 |
| KEU-AC-PAY-05 | Dashboard dan laporan hanya menghitung pembayaran terverifikasi; biaya admin dipisahkan | B-AUTO | AR-002, PAY-008 |
| KEU-AC-PAY-06 | Realokasi memerlukan persetujuan orang berbeda dan tercatat sebagai pasangan pembalik + alokasi baru | B-AUTO, B-AUD | PAY-010 |

### 3.5 Pembayaran Online (PG)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-PG-01 | Webhook berulang (termasuk paralel) tidak membuat transaksi pembayaran ganda; callback tidak berurutan tidak memundurkan status | B-AUTO, B-CONC | PG-004, PG-006 |
| KEU-AC-PG-02 | Webhook bertanda tangan tidak valid atau *replay* tersimpan untuk forensik tanpa efek apa pun | B-AUTO, B-SEC | PG-005, PG-013, PG-014 |
| KEU-AC-PG-03 | Redirect browser tidak pernah mengubah status pembayaran/tagihan | B-AUTO, B-UI | PG-003 |
| KEU-AC-PG-04 | Pembayaran setelah kedaluwarsa, ganda, dan bernominal berbeda tercatat sesuai rancangan | B-AUTO | PG-007, PG-008, PG-009, PG-012, PG-017 |
| KEU-AC-PG-05 | Jumlah kanal online aktif tidak dapat melebihi entitlement, termasuk saat aktivasi bersamaan | B-AUTO, B-CONC | PG-010 |
| KEU-AC-PG-06 | Status transaksi, pembayaran, dan tagihan terpisah; pembuatan transaksi idempoten; satu transaksi aktif per tagihan | B-AUTO, B-API | PG-001, PG-002 |
| KEU-AC-PG-07 | Gangguan provider menurunkan/menyembunyikan kanal dan pulih otomatis; transaksi tanpa webhook terselesaikan lewat polling | B-AUTO | PG-011, PG-015 |
| KEU-AC-PG-08 | Kredensial tidak muncul di respons, log, atau audit | B-AUTO, B-SEC | PG-016 |
| KEU-AC-PG-09 | Uji kontrak terhadap sandbox provider terpilih lulus (pembuatan, webhook nyata, cek status, kedaluwarsa) | B-UAT, B-API | — (uji kontrak provider) |

### 3.6 Tunai dan Manual (MAN)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-MAN-01 | Pembayaran tunai hanya dalam sesi kas; sesi ditutup dengan hitung fisik dan diverifikasi orang lain | B-AUTO, B-UI | MAN-001, MAN-002, MAN-010 |
| KEU-AC-MAN-02 | Bukti → verifikasi berdasarkan nominal mutasi → alokasi → kuitansi; kode unik bekerja bila diaktifkan | B-AUTO, B-UI | MAN-003, MAN-005, MAN-013 |
| KEU-AC-MAN-03 | Penolakan selalu berkode alasan dan diberitahukan | B-AUTO | MAN-004 |
| KEU-AC-MAN-04 | Referensi ganda diblokir; berkas ganda diperingatkan; berkas tidak valid ditolak | B-AUTO | MAN-006, MAN-007, MAN-008, MAN-011 |
| KEU-AC-MAN-05 | Perekam tidak dapat memverifikasi transaksinya sendiri; dua verifikator bersamaan tidak menghasilkan dua alokasi | B-AUTO, B-CONC | MAN-009, MAN-012 |
| KEU-AC-MAN-06 | Kuitansi dapat diverifikasi publik tanpa membocorkan data pribadi | B-AUTO, B-SEC | MAN-001, SEC-003 |

### 3.7 Cicilan (INS)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-INS-01 | Σ termin + bagian non-cicilan = sisa tagihan saat aktivasi; batas kebijakan ditegakkan; pembulatan ke termin terakhir | B-AUTO, B-INV (`INV-10`) | INS-001…004 |
| KEU-AC-INS-02 | Pembayaran diterapkan berurutan; pelunasan dipercepat menutup rencana | B-AUTO, B-INV (`INV-11`) | INS-005, INS-006 |
| KEU-AC-INS-03 | Penjadwalan ulang menghasilkan versi baru berizin; pembatalan berizin | B-AUTO, B-AUD | INS-007, INS-010, INS-011 |
| KEU-AC-INS-04 | Laporan tidak menghitung tagihan dan termin dua kali | B-AUTO | INS-008, RPT-005 |
| KEU-AC-INS-05 | Perubahan nominal tagihan menyesuaikan jadwal otomatis | B-AUTO | INS-009 |

### 3.8 Beasiswa dan Potongan (SCH)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-SCH-01 | Beasiswa penuh dan parsial menghasilkan nominal persis sesuai contoh [11 §5](11-rancangan-beasiswa-dan-potongan.md#5-urutan-perhitungan) | B-AUTO | SCH-001, SCH-002 |
| KEU-AC-SCH-02 | Urutan tahap dan basis persentase mengikuti pengaturan; grup penumpukan ditegakkan | B-AUTO | SCH-002, SCH-004 |
| KEU-AC-SCH-03 | Potongan tidak pernah melebihi komponen/batas; tidak ganda; hanya untuk penerima layak dalam kuota; evaluasi berkala hanya menandai | B-AUTO | SCH-003, SCH-005, SCH-006, SCH-011 |
| KEU-AC-SCH-04 | Pencabutan/perubahan skema tidak mengubah tagihan terbit; penerapan susulan lewat penyesuaian | B-AUTO | SCH-007, SCH-008 |
| KEU-AC-SCH-05 | Penetapan & potongan manual memerlukan persetujuan orang berbeda | B-AUTO, B-AUD | SCH-009, SCH-010 |

### 3.9 Voucher (VCH)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-VCH-01 | Validasi masa berlaku, kelayakan, minimum, batas maksimal, dan penggabungan berjalan sesuai rancangan | B-AUTO | VCH-001, VCH-002, VCH-005, VCH-009, VCH-010 |
| KEU-AC-VCH-02 | 20 penerapan bersamaan pada sisa kuota 1 menghasilkan tepat satu keberhasilan (20 ulangan) | B-CONC | VCH-004 |
| KEU-AC-VCH-03 | Voucher tidak dapat dipakai dua kali pada tagihan sama atau melebihi kuota per mahasiswa | B-AUTO | VCH-003 |
| KEU-AC-VCH-04 | Pembatalan tagihan/pelepasan mengembalikan kuota sesuai pengaturan; reservasi kedaluwarsa dilepas | B-AUTO | VCH-006, VCH-007 |
| KEU-AC-VCH-05 | Riwayat penggunaan lengkap; percobaan kode dibatasi; voucher harus disetujui orang berbeda | B-AUTO | VCH-008, VCH-011 |

### 3.10 Denda (LTF)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-LTF-01 | Denda sesuai aturan (tenggang, dasar sisa pokok, plafon, per termin) dan sama dengan contoh [13 §4.4](13-rancangan-denda.md#44-contoh) | B-AUTO | LTF-001, LTF-004, LTF-005, LTF-010 |
| KEU-AC-LTF-02 | Job idempoten dan mengejar bucket terlewat | B-AUTO, B-CONC | LTF-003, LTF-009 |
| KEU-AC-LTF-03 | Batas hari mengikuti zona waktu tenant | B-AUTO | LTF-002 |
| KEU-AC-LTF-04 | Denda berhenti setelah lunas; verifikasi terlambat atas pembayaran tepat waktu mengoreksi denda | B-AUTO | LTF-006, LTF-008 |
| KEU-AC-LTF-05 | Pembebasan memerlukan persetujuan; denda nonaktif bila saklar mati; perubahan aturan ke depan | B-AUTO, B-AUD | LTF-007, LTF-011, LTF-012 |

### 3.11 Deposit (DEP)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-DEP-01 | Saldo deposit konsisten dengan seluruh mutasinya | B-AUTO, B-INV (`INV-07`, `INV-08`) | DEP-005 |
| KEU-AC-DEP-02 | Pemakaian tidak pernah melebihi saldo tersedia, termasuk pemakaian bersamaan (20 ulangan) | B-AUTO, B-CONC | DEP-003, DEP-004 |
| KEU-AC-DEP-03 | Refund menahan dana saat diajukan dan mengubah ledger hanya saat selesai | B-AUTO | DEP-006 |
| KEU-AC-DEP-04 | Setiap mutasi tertelusur ke sumber; konversi/pemakaian ganda dicegah; pembatalan lewat pembalik | B-AUTO | DEP-001, DEP-002, DEP-008, DEP-009, DEP-010 |
| KEU-AC-DEP-05 | Pembekuan dan penutupan ditegakkan | B-AUTO | DEP-007 |

### 3.12 UKT (UKT)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-UKT-01 | Penetapan aktif menentukan tarif UKT; tanpa penetapan, UKT tidak ditagih | B-AUTO | UKT-001, UKT-002 |
| KEU-AC-UKT-02 | Rentang penetapan aktif tidak tumpang tindih | B-AUTO | UKT-004 |
| KEU-AC-UKT-03 | Keberatan dalam batas waktu menghasilkan penetapan baru berizin | B-AUTO, B-AUD | UKT-005, UKT-006 |
| KEU-AC-UKT-04 | Perubahan golongan tidak mengubah tagihan terbit | B-AUTO | UKT-003 |
| KEU-AC-UKT-05 | Validasi tarif & penerima menemukan celah; dokumen hanya untuk yang berwenang; impor tervalidasi | B-AUTO, B-SEC | UKT-007, UKT-008, UKT-009 |

### 3.13 Piutang (AR)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-AR-01 | Piutang pada dashboard dapat ditelusuri hingga tagihan dan alokasi pembayaran, dengan total sama | B-AUTO, B-UI | AR-001 |
| KEU-AC-AR-02 | Tagihan dibatalkan, draf, pembayaran belum terverifikasi, dan reversal diperlakukan konsisten di semua indikator | B-AUTO | AR-002, AR-004 |
| KEU-AC-AR-03 | Kategori umur dapat dikonfigurasi dan tepat di batas hari | B-AUTO | AR-003 |
| KEU-AC-AR-04 | Filter dan snapshot bekerja; snapshot periode tertutup beku | B-AUTO | AR-006, AR-007 |
| KEU-AC-AR-05 | Dekan/kaprodi hanya melihat unitnya | B-AUTO, B-SEC | AR-005 |

### 3.14 Cekal (HLD)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-HLD-01 | Cekal otomatis terbit/tidak terbit sesuai kebijakan (ambang, tenggang, per termin) | B-AUTO | HLD-001, HLD-003, HLD-009 |
| KEU-AC-HLD-02 | Cekal keuangan tidak dapat dilewati hanya dengan memanipulasi frontend atau memanggil API langsung | B-AUTO, B-SEC | HLD-006 |
| KEU-AC-HLD-03 | Cekal terbuka ≤ 60 detik setelah pembayaran terverifikasi (p95 pada staging); pembayaran pending tidak membuka | B-AUTO, B-PERF | HLD-002, HLD-011 |
| KEU-AC-HLD-04 | Pengecualian berlaku per layanan dan berbatas waktu; pembukaan manual berizin | B-AUTO, B-AUD | HLD-004, HLD-007 |
| KEU-AC-HLD-05 | Audit menjawab siapa menetapkan, kapan berubah, dasar tunggakan, dan siapa memberi pengecualian | B-AUD | HLD-001, HLD-004 |
| KEU-AC-HLD-06 | Kegagalan sinkronisasi terpantau, di-retry, direkonsiliasi, dan tidak dianggap pembukaan | B-AUTO | HLD-005, HLD-010 |
| KEU-AC-HLD-07 | Nominal tunggakan tidak terlihat oleh peran akademik | B-AUTO, B-SEC | HLD-008, NTF-005 |

### 3.15 Rekonsiliasi, Refund, Koreksi, Periode (REC)

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-REC-01 | Koreksi pembayaran/alokasi memakai reversal tanpa menghapus histori, termasuk pembalikan berantai | B-AUTO, B-AUD | REC-001, REC-002, REC-003, REC-006 |
| KEU-AC-REC-02 | Refund menahan dana, memerlukan persetujuan (bertingkat di atas ambang), dan selesai hanya dengan bukti | B-AUTO, B-AUD | REC-004, REC-005, REC-014 |
| KEU-AC-REC-03 | Rekonsiliasi mendeteksi selisih, transaksi hilang, dan berkas ganda | B-AUTO | REC-007, REC-008, REC-009 |
| KEU-AC-REC-04 | Periode tertutup menolak transaksi baru; pembukaan kembali dua tingkat | B-AUTO, B-AUD | REC-010, REC-011 |
| KEU-AC-REC-05 | Ekspor akuntansi seimbang dan menolak komponen tanpa kode akun | B-AUTO | REC-013 |
| KEU-AC-REC-06 | Dana tak teridentifikasi tercatat dan dapat diidentifikasi dengan verifikasi orang lain | B-AUTO | REC-012 |
| KEU-AC-REC-07 | Batch hanya dapat ditutup tanpa item terbuka; *write-off* tidak tersedia | B-AUTO | REC-007, REC-015 |

### 3.16 Hak Akses (RBAC), API, UI, Laporan, Notifikasi, Keamanan

| ID | Kriteria | Bukti | TC |
|---|---|---|---|
| KEU-AC-RBAC-01 | Matriks [03 §4](03-role-dan-hak-akses.md#4-matriks-hak-akses) ditegakkan backend untuk setiap endpoint (uji dihasilkan dari daftar route) | B-AUTO | RBAC-001, RBAC-002, RBAC-003 |
| KEU-AC-RBAC-02 | Pemisahan tugas ditegakkan pada mode langsung dan workflow | B-AUTO | MAN-009, TAR-005, SCH-009 |
| KEU-AC-RBAC-03 | Cakupan fakultas/prodi diterapkan | B-AUTO | AR-005 |
| KEU-AC-RBAC-04 | Mahasiswa hanya mengakses datanya; super admin hanya *break-glass* | B-AUTO, B-SEC | RBAC-004, RBAC-005 |
| KEU-AC-API-01 | Idempotensi bekerja untuk semua endpoint [IDEM] | B-AUTO, B-CONC | API-001, API-002, PG-001 |
| KEU-AC-API-02 | Kode galat sesuai kanon; uang sebagai string; `lock_version` ditegakkan | B-AUTO, B-API | API-003, API-004 |
| KEU-AC-API-03 | Event hanya terkirim setelah commit dan konsumen idempoten | B-AUTO | API-005, NTF-003 |
| KEU-AC-API-04 | Endpoint lama kompatibel | B-AUTO | API-006, API-007 |
| KEU-AC-UI-01 | Menu dan tombol mengikuti izin; API tetap menolak | B-UI, B-AUTO | UI-001 |
| KEU-AC-UI-02 | Setiap halaman memiliki state kosong, memuat, galat | B-UI | UI-004 |
| KEU-AC-UI-03 | Tindakan kritis memiliki konfirmasi dengan ringkasan dampak | B-UI | UI-002 |
| KEU-AC-UI-04 | Portal mahasiswa dapat dipakai penuh di ponsel 375 px | B-UI | UI-003 |
| KEU-AC-UI-05 | Istilah dan label status sesuai [21 §7](21-rancangan-ui-ux-dan-menu.md#7-label-status-dan-istilah) | B-UI | — |
| KEU-AC-RPT-01 | 18 laporan tersedia sesuai definisi [22 §4](22-rancangan-laporan-dan-dashboard.md#4-definisi-laporan) | B-UI, B-AUTO | RPT-001, RPT-005 |
| KEU-AC-RPT-02 | Angka setiap laporan sama dengan Σ sumber pada data uji | B-AUTO | AR-001, RPT-001 |
| KEU-AC-RPT-03 | Ekspor dibatasi izin, ber-*watermark*, termasking, dan tercatat | B-AUTO, B-AUD | RPT-003 |
| KEU-AC-RPT-04 | Ekspor 100.000 baris berjalan asinkron tanpa timeout; laporan berpaginasi p95 < 2 detik | B-PERF | RPT-004 |
| KEU-AC-RPT-05 | Laporan periode tertutup stabil setelah koreksi periode berikutnya | B-AUTO | RPT-002 |
| KEU-AC-NTF-01 | Notifikasi tepat event dan penerima; pengingat tidak dikirim ke tagihan lunas | B-AUTO | NTF-001, NTF-002, NTF-004 |
| KEU-AC-NTF-02 | Notifikasi hanya setelah commit, tanpa duplikasi | B-AUTO | NTF-003 |
| KEU-AC-NTF-03 | Isi notifikasi tidak membocorkan data sensitif | B-AUTO | NTF-005 |
| KEU-AC-SEC-01 | Seluruh tindakan sensitif dalam katalog [24 §8](24-keamanan-audit-dan-kontrol-internal.md#8-katalog-audit) tercatat dengan pelaku, waktu, alasan | B-AUD | SEC-004, SEC-009, SEC-010 |
| KEU-AC-SEC-02 | Isolasi tenant dan data milik sendiri pada semua endpoint | B-AUTO, B-SEC | SEC-001, RBAC-005 |
| KEU-AC-SEC-03 | Webhook aman dari pemalsuan & *replay*; endpoint sensitif dibatasi laju | B-SEC | PG-005, PG-013, SEC-005 |
| KEU-AC-SEC-04 | Berkas privat, tervalidasi, aksesnya diaudit | B-AUTO, B-SEC | SEC-002, MAN-008 |
| KEU-AC-SEC-05 | Enkripsi & masking berlaku di API, UI, ekspor, log | B-SEC | SEC-003, PG-016 |
| KEU-AC-SEC-06 | Aturan anomali terpicu pada data uji | B-AUTO | SEC-006 |
| KEU-AC-SEC-07 | Retensi berjalan; audit tidak dapat diubah; transisi atomik | B-AUTO | SEC-007, SEC-008, SEC-011 |

## 4. Prosedur Penerimaan

| Langkah | Pelaku | Keluaran |
|---|---|---|
| 1. Pengembang melampirkan B-AUTO, B-CONC, B-INV, B-API pada PR | Tim pengembang | PR siap tinjau |
| 2. QA menjalankan TC P1/P2 area fitur dan uji eksploratif | QA | Laporan uji |
| 3. Tinjauan keamanan untuk fitur bertanda SEC/PG/HLD | Security/Tech Lead | B-SEC |
| 4. UAT dengan skenario nyata kampus pilot (data anonim) | Bagian keuangan | B-UAT |
| 5. Pemeriksaan dokumen (rancangan diperbarui bila berbeda) | Tech Lead | Dokumen KEU mutakhir |
| 6. Keputusan rilis per tenant di balik *feature flag* ([28 §8](28-roadmap-implementasi.md#8-strategi-rilis-dan-migrasi-data)) | PO | Rilis |
