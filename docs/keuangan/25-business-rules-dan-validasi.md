# 25 — Business Rules dan Validasi

> **Status:** rancangan. Disusun 2026-10-13.
> Kembali ke [README](README.md) · Skema ID dan rentang nomor: [README §5.7](README.md#57-kanon-skema-id-dokumen) · Kode error: [README §5.8](README.md#58-kanon-kode-error-errorscode) · Test case: [26](26-skenario-pengujian.md)

Dokumen ini adalah **katalog resmi** aturan bisnis modul KEU. Teks dan ID aturan di dokumen ini berlaku bila berbeda dengan dokumen lain ([README §2](README.md#2-urutan-membaca-dan-dependensi)). Setiap aturan wajib memiliki minimal satu test otomatis yang menyebut ID aturan pada nama test ([28 §7](28-roadmap-implementasi.md#7-definition-of-done-umum)).

## Daftar Isi

1. [Format dan Cara Membaca](#1-format-dan-cara-membaca)
2. [Umum dan Lintas Fitur (001–019)](#2-umum-dan-lintas-fitur-001019)
3. [Master dan Tarif (020–039)](#3-master-dan-tarif-020039)
4. [Tagihan (040–069)](#4-tagihan-040069)
5. [Pembayaran Umum dan Alokasi (070–089)](#5-pembayaran-umum-dan-alokasi-070089)
6. [Pembayaran Online (090–109)](#6-pembayaran-online-090109)
7. [Pembayaran Tunai dan Manual (110–124)](#7-pembayaran-tunai-dan-manual-110124)
8. [Cicilan (125–139)](#8-cicilan-125139)
9. [Beasiswa dan Potongan (140–159)](#9-beasiswa-dan-potongan-140159)
10. [Voucher (160–179)](#10-voucher-160179)
11. [Denda (180–199)](#11-denda-180199)
12. [Deposit (200–219)](#12-deposit-200219)
13. [UKT (220–239)](#13-ukt-220239)
14. [Piutang dan Laporan (240–259)](#14-piutang-dan-laporan-240259)
15. [Cekal (260–279)](#15-cekal-260279)
16. [Rekonsiliasi, Refund, Koreksi, Periode (280–299)](#16-rekonsiliasi-refund-koreksi-periode-280299)
17. [Keamanan dan Audit (300–319)](#17-keamanan-dan-audit-300319)

---

## 1. Format dan Cara Membaca

Setiap baris memuat sembilan unsur yang diminta: **ID**, **Nama**, **Deskripsi**, **Alasan bisnis**, **Pemicu** (kapan aturan diperiksa), **Validasi** (cara memeriksa), **Kegagalan** (respons/penanganan), **Modul** terdampak, dan **TC** terkait. Singkatan TC: `INV-001` = `KEU-TC-INV-001`. Nomor yang tidak terpakai dalam suatu rentang dicadangkan untuk aturan berikutnya.

Lapisan penegakan yang dimaksud kolom Validasi: **FR** Form Request, **SV** service domain (dalam transaksi), **DB** constraint database, **JOB** job terjadwal, **PL** Policy/permission.

## 2. Umum dan Lintas Fitur (001–019)

`KEU-BR-001`–`008` adalah rumusan permintaan bisnis, dipakai apa adanya.

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-001 | Nomor tagihan unik | Setiap tagihan harus memiliki nomor unik | Rujukan tunggal bagi mahasiswa, kuitansi, dan auditor | Penerbitan tagihan | SV `FinanceSequenceService` (kunci baris); DB unique `(university_id, invoice_number)` | Transaksi gagal dan diulang; nomor tidak dipakai ulang | Tagihan | INV-001 |
| KEU-BR-002 | Snapshot tarif | Tagihan yang sudah diterbitkan menyimpan snapshot tarif | Perubahan master tidak boleh mengubah kewajiban yang sudah terbit | Pembuatan baris `charge` | SV menyalin `unit_amount`, `tariff_rule_id`, `tariff_schedule_id`, atribut komponen, `snapshot` | Baris tanpa snapshot ditolak (galat sistem) | Tarif, Tagihan | TAR-006 |
| KEU-BR-003 | Pembayaran diakui setelah verifikasi | Pembayaran online baru dianggap terverifikasi setelah proses validasi provider berhasil; secara umum hanya `payments.status = verified` yang diakui sebagai uang masuk | Mencegah pelunasan palsu | Pembuatan/verifikasi pembayaran, perhitungan laporan | SV: gateway hanya dari webhook valid + konfirmasi; manual hanya oleh verifikator | Status tetap `pending_verification`; tidak ada alokasi | Pembayaran, Laporan | PG-003, MAN-003 |
| KEU-BR-004 | Tidak diproses dua kali | Pembayaran yang sama tidak boleh diproses dua kali | Mencegah saldo dan kas berlebih | Webhook, pembuatan pembayaran, idempotensi API | DB unique `dedupe_key`, `payments.payment_transaction_id`, `reference_key`; SV kunci baris transaksi | Duplikat diabaikan (200 untuk webhook) atau 409 | Online, Manual | PG-004 |
| KEU-BR-005 | Alokasi dibatasi | Alokasi pembayaran tidak boleh melebihi nominal yang dapat dialokasikan | Tagihan tidak boleh "lebih lunas"; dana tidak boleh dipakai dua kali | Setiap alokasi | SV kunci `payments` & `invoices`; DB CHECK `paid_amount ≤ amount`, invarian kas | 422 `ALLOCATION_EXCEEDS_OUTSTANDING`/`_AVAILABLE` | Pembayaran | PAY-004, PAY-007 |
| KEU-BR-006 | Deposit dibatasi | Deposit tidak boleh digunakan melebihi saldo tersedia | Saldo titipan tidak boleh negatif | Pemakaian/refund deposit | SV kunci `deposit_accounts`; DB CHECK `balance ≥ 0`, `held ≤ balance` | 422 `DEPOSIT_INSUFFICIENT` | Deposit | DEP-003, DEP-004 |
| KEU-BR-007 | Jejak audit transaksi terverifikasi | Perubahan transaksi terverifikasi wajib mempertahankan jejak audit | Bukti bagi auditor; laporan lama tidak berubah diam-diam | Setiap koreksi | SV: tidak ada `UPDATE` nominal/`DELETE`; koreksi via `finance_adjustments` + baris pembalik | Operasi langsung tidak tersedia (tidak ada endpoint); percobaan di service melempar galat | Semua | REC-006 |
| KEU-BR-008 | Cekal sesuai kebijakan & sinkronisasi | Status cekal harus diperbarui sesuai kebijakan kampus dan hasil sinkronisasi akademik | Layanan akademik dibatasi adil dan terbuka segera setelah lunas | Event pembayaran/tagihan, job harian | SV `HoldEvaluator`; outbox + konfirmasi sinkronisasi | `sync_status = failed` + alert | Cekal, SIAKAD | HLD-001, HLD-002, HLD-005 |
| KEU-BR-009 | Mahasiswa berdata keuangan tidak dihapus | Mahasiswa yang memiliki tagihan, pembayaran, atau deposit tidak dapat dihapus | FK lama `cascade` dapat menghapus histori keuangan | Penghapusan mahasiswa (Akademik) | SV pemeriksaan sebelum hapus; FK baru `restrict` | 409 dengan pesan "memiliki data keuangan" | Akademik, KEU | INV-014 |
| KEU-BR-010 | Urutan kunci baku | Kunci baris diambil berurutan: `finance_sequences` → `vouchers` → `deposit_accounts` → `payment_transactions` → `payments` → `invoices` (urut id) → `installment_terms` | Mencegah *deadlock* dan kondisi balapan | Setiap operasi nominal | Review kode + uji konkurensi | *Deadlock* → transaksi diulang otomatis maksimal 3 kali | Semua | PAY-007, DEP-004, MST-005 |
| KEU-BR-011 | Aritmetika desimal | Perhitungan uang memakai desimal; hasil persen dibulatkan ke rupiah penuh *half up* (konfigurasi); API mengirim uang sebagai string | Akurasi sampai rupiah | Setiap perhitungan & serialisasi | SV `Money`/BCMath; uji properti | Galat sistem bila float terdeteksi (statis: Larastan rule) | Semua | MST-006, API-003 |
| KEU-BR-012 | Batas hari zona tenant | Jatuh tempo, bucket denda, posisi laporan, dan tahun nomor dihitung dalam `universities.timezone`; disimpan UTC | Jatuh tempo mengikuti jam lokal kampus | Perhitungan tanggal | SV memakai `TenantClock` | — | Semua | LTF-002 |
| KEU-BR-013 | Transisi status atomik | Status hanya diubah service domain; setiap transisi menulis histori dan audit dalam transaksi yang sama; penyuntingan memakai `lock_version` | Status konsisten dan dapat dijelaskan | Setiap transisi | SV; tidak ada endpoint `PATCH status` | 409 `INVALID_STATUS_TRANSITION` / `STALE_VERSION` | Semua | SEC-007, API-004, MAN-012 |
| KEU-BR-014 | Periode tertutup terkunci | Transaksi dengan `posting_date` di periode `closed`/`closing` ditolak; koreksi dibukukan di periode terbuka | Laporan periode tertutup tidak berubah | Pembuatan efek finansial | SV memeriksa `finance_periods` | 409 `PERIOD_CLOSED` (atau `posting_date` dialihkan untuk verifikasi terlambat) | Semua | REC-010, RPT-002 |
| KEU-BR-015 | Isolasi tenant | Semua data dan berkas dibatasi `university_id` | Multi-tenant | Setiap query | `TenantScoped`, `Rule::exists` bertenant | 404 | Semua | SEC-001 |
| KEU-BR-016 | Mahasiswa tanpa `student_id` dari klien | Endpoint mahasiswa menentukan mahasiswa dari sesi (`students.user_id`) | Mencegah IDOR | Setiap endpoint `/student/finance` | PL + SV | 404 untuk data bukan milik | Portal | RBAC-005 |
| KEU-BR-017 | Idempotency-Key | Operasi yang membuat efek finansial wajib `Idempotency-Key` | Klik ganda/jaringan putus tidak menggandakan transaksi | Endpoint [IDEM] | Middleware + `idempotency_keys` | 422 (tanpa kunci), 409 `IDEMPOTENCY_KEY_REUSED`/`_IN_PROGRESS` | API | PG-001, API-001, API-002 |
| KEU-BR-018 | Outbox atomik | Event lintas modul ditulis ke outbox dalam transaksi perubahan; dikirim setelah commit; notifikasi setelah commit | Rollback tidak meninggalkan event/notifikasi palsu | Setiap perubahan yang memancarkan event | SV + worker | Retry dengan *backoff*; `dead` + alert | Integrasi, Notifikasi | API-005, NTF-003 |
| KEU-BR-019 | Alasan wajib aksi sensitif | Penolakan, pembatalan, koreksi, reversal, refund, pembebasan, pencabutan, pembekuan, *override*, perubahan pengaturan sensitif wajib alasan | Akuntabilitas | Aksi terkait | FR `reason` wajib (≥ 10 karakter) | 422 | Semua | SEC-010 |

## 3. Master dan Tarif (020–039)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-020 | Kode komponen | Kode komponen unik per tenant (huruf besar); komponen yang dirujuk tidak dapat dihapus, hanya dinonaktifkan | Histori tagihan tetap utuh | Buat/hapus komponen | DB unique; SV cek referensi | 422 / 409 | Master | MST-001, MST-002, MST-003 |
| KEU-BR-021 | Komponen denda | Kategori `late_fee` wajib `charge_basis = manual` dan tidak dipakai di tarif | Denda dihitung oleh aturan denda | Simpan komponen/aturan tarif | FR | 422 | Master, Denda | MST-001 |
| KEU-BR-022 | Rekening aman & unik | Nomor rekening terenkripsi, unik per tenant (hash), tidak dihapus bila dirujuk | Keamanan & integritas | Simpan rekening | SV + DB unique hash | 422 / 409 | Master | MST-004 |
| KEU-BR-023 | Periode tidak tumpang tindih | Periode bulanan unik per `YYYY-MM`, dibuat otomatis | Penguncian per bulan konsisten | Job & transaksi pertama | DB unique; SV | — | Periode | REC-010 |
| KEU-BR-024 | Penomoran | Nomor dokumen per tenant per tahun; celah diizinkan, pemakaian ulang dilarang | Nomor manusiawi & unik | Pembuatan dokumen | SV kunci baris; DB unique | Diulang | Semua | MST-005 |
| KEU-BR-025 | Jadwal tarif immutable | Jadwal `published` dan aturannya tidak dapat diubah; revisi = versi baru | Snapshot & audit | Ubah jadwal | SV status | 409 `INVALID_STATUS_TRANSITION` | Tarif | TAR-006 |
| KEU-BR-026 | Persetujuan tarif | Jadwal wajib disetujui sebelum dipublikasikan; penyetuju ≠ pengaju | Kontrol harga | Approve/publish | SV + SoD | 403 `SELF_APPROVAL_FORBIDDEN` / 409 | Tarif | TAR-005 |
| KEU-BR-027 | Rentang jadwal | Rentang term dua jadwal `published` ber-`code` sama tidak tumpang tindih; versi lama dibatasi otomatis saat publikasi | Satu tarif berlaku per waktu | Publikasi | SV | 409 | Tarif | TAR-007 |
| KEU-BR-028 | Aturan identik | Dua aturan dengan kriteria identik dalam satu jadwal ditolak | Mencegah duplikasi | Simpan aturan | DB unique `criteria_hash` | 422 | Tarif | TAR-008 |
| KEU-BR-029 | Skor spesifisitas | Bila beberapa aturan cocok, aturan dengan skor tertinggi dipilih | Deterministik & dapat dijelaskan | Resolusi tarif | SV `TariffResolver` | — | Tarif | TAR-001 |
| KEU-BR-030 | Konflik setara | Aturan berskor sama dengan nilai beririsan memblokir submit/publikasi | Mencegah tarif ganda bertentangan | Submit, publish, resolusi | SV validasi | 409 `TARIFF_AMBIGUOUS` | Tarif, Tagihan | TAR-004 |
| KEU-BR-031 | Tarif tidak ditemukan | Komponen wajib tanpa aturan cocok tidak ditagih; mahasiswa dilaporkan | Tidak menagih sebagian diam-diam | Resolusi | SV | 422 `TARIFF_NOT_FOUND`; item run `failed` | Tarif, Tagihan | TAR-003 |
| KEU-BR-032 | Per SKS / per MK | Satuan dihitung dari `krs_items` `enrolled`; `free_units`, `min_units`, `max_units` diterapkan; nol satuan → tidak ditagih | Tarif variabel akurat | Resolusi | SV | — | Tarif, Tagihan | TAR-002, INV-004 |
| KEU-BR-033 | Konsistensi kriteria | Prodi harus anggota fakultas yang diisi; `from ≤ to`; `min ≤ max`; komponen UKT wajib golongan; `per_course` wajib pemetaan MK | Aturan bermakna | Simpan/validasi | FR + SV | 422 / pemblokir validasi | Tarif | TAR-009 |
| KEU-BR-034 | Master tidak surut | Perubahan tarif, atribut komponen, skema, pemetaan MK tidak mengubah tagihan terbit | Kepastian kewajiban | Perubahan master | SV (snapshot) | — | Master, Tarif | TAR-006, MST-007 |
| KEU-BR-035 | Kriteria jalur/kelas | Aturan berkriteria jalur/kelas hanya cocok bila data mahasiswa tersedia; bila tidak, ditandai *unreachable* | Kolom belum ada ([KEU-A-04](29-glossary-dan-keputusan-bisnis.md#4-asumsi-keu-a)) | Validasi & resolusi | SV | Peringatan validasi | Tarif | TAR-009 |
| KEU-BR-036 | Pengaturan ke depan | Perubahan `finance.*` berlaku ke depan, divalidasi tipenya, dan diaudit | Tidak mengubah histori | Simpan pengaturan | FR skema per kunci; PL kunci sensitif | 422 / 403 | Pengaturan | MST-007 |

## 4. Tagihan (040–069)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-040 | Draf tertutup | Tagihan `draft` tidak tampil ke mahasiswa, tidak dihitung piutang, tidak dapat dibayar | Draf belum kewajiban | Query portal/laporan/pembayaran | SV filter | 404 (portal) / `INVOICE_NOT_PAYABLE` | Tagihan | INV-003 |
| KEU-BR-041 | Satu tagihan per kunci | Satu tagihan aktif per `billing_key` (mis. semester per mahasiswa per term) | Mencegah tagihan ganda | Pembuatan | DB unique `billing_active_key` | 409 `ALREADY_BILLED` | Tagihan | INV-002 |
| KEU-BR-042 | Nominal & status turunan | `amount = Σ baris`; status `unpaid/partial/paid` diturunkan dari `paid_amount` | Konsistensi | Setiap perubahan baris/alokasi | SV + DB CHECK; `INV-01`–`04` | Galat sistem | Tagihan | INV-001, PAY-001 |
| KEU-BR-043 | Baris immutable | Baris tagihan terbit tidak diubah/dihapus; perubahan lewat baris baru hasil penyesuaian | Histori utuh | Koreksi | SV | 409 | Tagihan | INV-011 |
| KEU-BR-044 | Kelayakan status | Tagihan semester hanya untuk status di `finance.invoice.eligible_student_statuses` | Tidak menagih mahasiswa nonaktif | Penagihan | SV | Item `skipped` (`STUDENT_INACTIVE`) / 422 | Tagihan | INV-016 |
| KEU-BR-045 | Dasar SKS | SKS dari `krs_items` `enrolled` saat penagihan; perubahan setelahnya → susulan/koreksi | Sesuai kebijakan akademik ([KEU-Q-12](29-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-keu-q)) | Penagihan per SKS | SV | — | Tagihan, KRS | INV-004 |
| KEU-BR-046 | Jatuh tempo valid | `due_date ≥` tanggal terbit dan ≤ 365 hari | Jatuh tempo masuk akal | Buat/terbit | FR | 422 | Tagihan | INV-001 |
| KEU-BR-047 | Batal tanpa alokasi | Pembatalan hanya bila `paid_amount = 0` | Uang yang sudah diterapkan harus dikembalikan dulu | Penerapan pembatalan | SV | 409 `INVOICE_HAS_ALLOCATIONS` | Tagihan | INV-007 |
| KEU-BR-048 | Pembatalan berizin | Pembatalan tagihan terbit lewat `invoice_cancellation` dengan alasan dan persetujuan | Kontrol | Pengajuan | SV + SoD | 403 / 422 | Tagihan | INV-008 |
| KEU-BR-049 | Efek berantai pembatalan | Pembatalan melepas voucher, membatalkan cicilan & denda anak belum dibayar, mengevaluasi cekal | Konsistensi lintas fitur | Penerapan pembatalan | SV satu transaksi | Rollback penuh | Tagihan, Voucher, Cicilan, Cekal | INV-008, VCH-006 |
| KEU-BR-050 | Tagihan nol | Tagihan dengan `amount = 0` langsung `paid` saat terbit | Beasiswa penuh tidak menimbulkan piutang | Penerbitan | SV | — | Tagihan | INV-009 |
| KEU-BR-051 | Susulan & tambahan | Tagihan susulan/tambahan adalah tagihan mandiri dengan kunci anti-ganda sendiri | Penagihan tambahan rapi | Pembuatan | SV + DB | 409 `ALREADY_BILLED` | Tagihan | INV-010 |
| KEU-BR-052 | Massal wajib pratinjau | Billing run wajib pratinjau berlaku (≤ TTL); satu run `processing` per term & tipe | Mencegah tagihan massal salah | Eksekusi | SV + kunci | 409 | Tagihan | INV-005 |
| KEU-BR-053 | Run idempoten | Run dapat diulang; mahasiswa tertagih menjadi `already_billed` | Pemulihan aman | Eksekusi ulang | DB unique `billing_active_key` | Item `already_billed` | Tagihan | INV-006 |
| KEU-BR-054 | Sengketa tunggal | Satu sengketa aktif per tagihan; efek pada denda/cekal sesuai konfigurasi | Penanganan teratur | Pengajuan sengketa | DB unique `active_key` | 409 | Tagihan | INV-013 |
| KEU-BR-055 | Tagihan dapat dibayar | Pembayaran/alokasi hanya untuk `unpaid/partial` | Konsistensi | Transaksi, alokasi | SV | 409 `INVOICE_NOT_PAYABLE` | Tagihan, Pembayaran | INV-003 |
| KEU-BR-056 | Koreksi ≥ dibayar | Koreksi/pembebasan tidak membuat `amount < paid_amount`; kelebihan dibalik dulu | Tidak ada saldo negatif | Penerapan penyesuaian | SV + DB CHECK | `failed` / 422 | Tagihan | INV-011 |
| KEU-BR-057 | Posting eksternal idempoten | Posting PMB/SP unik per `source_type:source_id` | Integrasi aman diulang | `postExternalInvoice` | DB unique `source_key` | Mengembalikan tagihan yang ada | Tagihan, PMB, SP | INV-012 |
| KEU-BR-058 | Perpanjangan jatuh tempo berizin | Perpanjangan lewat `invoice_correction` `DUE_DATE_EXTENSION`; `original_due_date` tetap | Kontrol keringanan | Pengajuan | SV + persetujuan | 403 / 422 | Tagihan | INV-015 |
| KEU-BR-059 | Tagihan berulang | Penagihan berulang dilakukan dengan billing run per term (salinan kriteria) yang tetap melewati pratinjau | Tidak ada penerbitan otomatis tanpa tinjauan | Pembuatan run | SV | — | Tagihan | INV-006 |

## 5. Pembayaran Umum dan Alokasi (070–089)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-070 | Validitas pembayaran | `amount > 0`; `student_id` wajib kecuali dana tak teridentifikasi `bank_import` | Data penerimaan bermakna | Pembuatan | FR + DB CHECK | 422 | Pembayaran | PAY-001 |
| KEU-BR-071 | Alokasi dari verified | Alokasi hanya dari pembayaran `verified` atau debit deposit | Uang belum pasti tidak melunasi | Alokasi | SV; `INV-09` | 409 `INVALID_STATUS_TRANSITION` | Pembayaran | PAY-009 |
| KEU-BR-072 | Alokasi ≤ sisa | Setiap alokasi ≤ `outstanding_amount` tagihan saat itu | Tidak lebih lunas | Alokasi | SV kunci | 422 `ALLOCATION_EXCEEDS_OUTSTANDING` | Pembayaran | PAY-004 |
| KEU-BR-073 | Mahasiswa sama | Alokasi hanya ke tagihan milik mahasiswa pemilik pembayaran/deposit | Mencegah pemindahan dana lintas mahasiswa | Alokasi | SV | 422 | Pembayaran | PAY-005 |
| KEU-BR-074 | Strategi otomatis | Sisa dana dialokasikan FIFO jatuh tempo, lalu prioritas tipe, lalu tanggal terbit; tagihan dalam transaksi online aktif dilewati | Prediktabel | Alokasi otomatis | SV | — | Pembayaran | PAY-006 |
| KEU-BR-075 | Alokasi bercicilan | Dana mengisi bagian di luar jadwal dulu, lalu termin berurutan | Jadwal tidak berlubang | Alokasi ke tagihan bercicilan | SV | — | Pembayaran, Cicilan | INS-005 |
| KEU-BR-076 | Kelebihan bayar | Sisa dana menjadi `unapplied_amount`, ditangani sesuai `finance.overpayment.default_action` | Dana tidak hilang | Setelah alokasi | SV + CHECK invarian kas | — | Pembayaran | PAY-003 |
| KEU-BR-077 | Biaya admin bukan pelunasan | `customer_fee_amount` tidak dialokasikan ke tagihan dan tidak dihitung sebagai pembayaran kewajiban | Laporan akurat | Pembuatan pembayaran | SV | — | Pembayaran, Online | PAY-008 |
| KEU-BR-078 | Kuitansi | Setiap pembayaran `verified` memiliki tepat satu kuitansi aktif | Bukti bagi mahasiswa | Verifikasi | SV + DB unique `active_key` | Galat sistem | Pembayaran | PAY-001 |
| KEU-BR-079 | Reversal pembayaran | Reversal membalik semua alokasi aktif, konversi deposit, kuitansi, dan menandai `reversed` | Efek turunan tidak tertinggal | Penerapan `payment_reversal` | SV satu transaksi | `failed` bila turunan belum dibalik | Pembayaran | REC-001 |
| KEU-BR-080 | Realokasi | Pemindahan alokasi antartagihan (mahasiswa sama) = pembalikan + alokasi baru, berizin | Jejak lengkap | `reallocation` | SV + persetujuan | 403 / 422 | Pembayaran | PAY-010 |
| KEU-BR-081 | Parsial & minimum | Pembayaran parsial sesuai `finance.payment.partial_allowed`; minimum per transaksi online | Kebijakan kampus | Pembuatan transaksi/pembayaran | FR + SV | 422 | Pembayaran, Online | PAY-002 |
| KEU-BR-082 | Tanggal pembukuan | `posting_date` sesuai `finance.posting_date_basis`; bila jatuh di periode tertutup → tanggal verifikasi | Laporan periodik konsisten | Verifikasi | SV | — | Pembayaran, Laporan | RPT-001 |
| KEU-BR-083 | Pembatalan pending | Pembayaran `pending_verification` dapat dibatalkan perekam/mahasiswa sebelum diperiksa; tercatat `cancelled` | Koreksi sebelum verifikasi tanpa birokrasi | Pembatalan | SV | 409 bila sudah diverifikasi | Pembayaran | PAY-009 |

## 6. Pembayaran Online (090–109)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-090 | Kuota kanal | Kanal online aktif ≤ entitlement paket; loket & transfer manual tidak dihitung [K] | Ketentuan paket | Aktivasi kanal | SV kunci baris | 409 `CHANNEL_LIMIT_EXCEEDED` | Online | PG-010 |
| KEU-BR-091 | Tidak percaya redirect | Status bayar hanya dari webhook valid + konfirmasi provider, atau cek status; redirect browser tidak mengubah status | Mencegah status palsu | Kembali dari provider | SV | — | Online, Portal | PG-003 |
| KEU-BR-092 | Simpan lalu proses | Webhook disimpan mentah sebelum diproses; duplikat ditolak constraint | Forensik & idempotensi | Webhook masuk | DB unique `(account, dedupe_key)` | 200 `duplicate` | Online | PG-004 |
| KEU-BR-093 | Satu transaksi aktif | Satu tagihan hanya dalam satu transaksi online aktif | Mencegah bayar ganda | Buat transaksi | DB unique `pending_key` | 409 `ACTIVE_TRANSACTION_EXISTS` | Online | PG-002 |
| KEU-BR-094 | Komposisi nominal | `amount = Σ item`, item ≤ sisa; `total = amount + customer_fee` | Nominal benar | Buat transaksi | FR + SV | 422 | Online | PG-001 |
| KEU-BR-095 | Selisih nominal | Nominal provider ≠ total → pembayaran `pending_verification`, webhook `mismatch` | Uang tidak dialokasikan otomatis bila ganjil | Pemrosesan `paid` | SV | Antrean verifikasi | Online | PG-009 |
| KEU-BR-096 | Transisi maju | Status transaksi tidak mundur; callback tidak berurutan diabaikan | Ketahanan urutan | Pemrosesan webhook | SV peringkat status | `ignored` | Online | PG-006 |
| KEU-BR-097 | Bayar setelah kedaluwarsa | Konfirmasi `paid` setelah `expired/cancelled` tetap dicatat sebagai penerimaan | Uang nyata harus tercatat | Webhook/cek status | SV | — | Online | PG-007, PG-017 |
| KEU-BR-098 | Kedaluwarsa dikonfirmasi | Sebelum menandai `expired`, sistem cek status ke provider (bila didukung) | Mencegah melewatkan pembayaran | Job kedaluwarsa | JOB | Transaksi diproses lunas bila ternyata dibayar | Online | PG-012 |
| KEU-BR-099 | Pembatalan oleh mahasiswa | Hanya `created/pending`; batal di provider bila didukung, selain itu lokal | Mahasiswa dapat mengganti kanal | Pembatalan | SV | 409 | Online | PG-017 |
| KEU-BR-100 | Kredensial terenkripsi | Kredensial & secret terenkripsi; tidak pernah dikembalikan/di-log | Keamanan | Simpan/baca akun | SV + `$hidden` | — | Online | PG-016 |
| KEU-BR-101 | Tenant dari akun | Tenant webhook ditentukan dari id akun pada URL | Isolasi | Webhook | SV | `ignored`/`invalid_signature` | Online | PG-014 |
| KEU-BR-102 | Pembayaran ganda | Dua transaksi lunas untuk tagihan sama → keduanya dicatat; kelebihan menjadi dana belum diterapkan | Uang nyata tercatat, dapat dikembalikan | Pemrosesan `paid` | SV | Notifikasi staf | Online | PG-008 |
| KEU-BR-103 | Kanal terganggu | Kanal `down`/nonaktif tidak ditawarkan untuk transaksi baru | Pengalaman & keandalan | Daftar kanal, buat transaksi | SV | 409 `CHANNEL_UNAVAILABLE` | Online | PG-011 |
| KEU-BR-104 | Penanggung biaya | Biaya kanal mengikuti `fee_bearer`; biaya provider dari settlement dicatat terpisah | Transparansi biaya | Buat transaksi, rekonsiliasi | SV | — | Online, Rekonsiliasi | PAY-008, REC-007 |
| KEU-BR-105 | Polling | Transaksi `pending` tanpa webhook dicek berkala dengan *backoff* | Pembayaran tidak terlewat | Job | JOB | — | Online | PG-015 |
| KEU-BR-106 | Top-up via gateway | Top-up deposit online hanya bila diizinkan dan kanal mendukung | Kebijakan | Buat transaksi top-up | SV | 403 / 409 | Online, Deposit | DEP-010 |

## 7. Pembayaran Tunai dan Manual (110–124)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-110 | Sesi kas wajib | Tunai hanya dicatat dalam sesi `open` milik petugas; satu sesi terbuka per petugas | Akuntabilitas kas | Catat tunai | SV + DB unique | 409 | Manual | MAN-001, MAN-002 |
| KEU-BR-111 | Tunai terverifikasi | Pembayaran tunai `verified` saat dicatat; kontrol lewat penutupan & verifikasi sesi | Uang diterima fisik ([KEU-KD-25](29-glossary-dan-keputusan-bisnis.md#2-keputusan-desain-keu-kd)) | Catat tunai | SV | — | Manual | MAN-001 |
| KEU-BR-112 | Transfer menunggu verifikasi | Transfer manual dicatat `pending_verification` sampai diverifikasi staf | Bukti belum tentu dana | Unggah/catat transfer | SV | — | Manual | MAN-003 |
| KEU-BR-113 | Berkas bukti | Tipe `pdf/jpg/jpeg/png`, ukuran ≤ batas, checksum, pemindaian virus bila aktif | Keamanan & keterbacaan | Unggah | FR + FileManagement | 422 | Manual | MAN-008 |
| KEU-BR-114 | Duplikasi referensi & bukti | Referensi bank unik per kanal (memblokir); checksum sama memicu peringatan dan *override* beralasan | Mencegah klaim ganda | Unggah, verifikasi | DB unique `reference_key`; SV checksum | 409 `DUPLICATE_REFERENCE` / peringatan | Manual | MAN-006, MAN-007 |
| KEU-BR-115 | Verifikasi atas mutasi | Nominal terverifikasi = nominal mutasi; beda dengan klaim wajib alasan | Uang nyata | Verifikasi | FR + SV | 422 | Manual | MAN-005 |
| KEU-BR-116 | Penolakan berkode | Penolakan wajib kode alasan + catatan; final untuk pengajuan itu | Mahasiswa tahu tindak lanjut | Penolakan | FR | 422 | Manual | MAN-004 |
| KEU-BR-117 | Kode unik [K] | Bila aktif, instruksi transfer memuat kode unik yang dicatat sebagai biaya, bukan pelunasan | Memudahkan pencocokan | Buat instruksi | SV | — | Manual | MAN-013 |
| KEU-BR-118 | Tanggal transfer | Tidak di masa depan; tidak lebih tua dari `max_backdate_days` | Data masuk akal | Unggah | FR | 422 | Manual | MAN-011 |
| KEU-BR-119 | Penutupan sesi | Sesi ditutup dengan hitung fisik; selisih ≠ 0 wajib catatan; diverifikasi pengguna lain | Kontrol kas | Tutup/verifikasi | SV + SoD | 422 / 403 | Manual | MAN-010 |
| KEU-BR-120 | Rekening aktif | Transfer manual hanya ke rekening aktif dengan kanal `manual_transfer` aktif | Uang masuk ke rekening resmi | Unggah | FR | 422 | Manual | MAN-003 |
| KEU-BR-121 | Koreksi via reversal | Pembayaran manual terverifikasi dikoreksi lewat reversal berizin | Histori utuh | Koreksi | SV | 409 | Manual | REC-001 |

## 8. Cicilan (125–139)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-125 | Syarat cicilan | Tipe diizinkan, `unpaid/partial`, tanpa rencana aktif, tanpa transaksi online aktif | Mencegah konflik | Pengajuan | SV + DB unique | 409 / 422 | Cicilan | INS-011 |
| KEU-BR-126 | Total jadwal | Σ termin = nominal dicicil; nominal dicicil + bagian non-cicilan = sisa tagihan saat aktivasi | Tidak ada kewajiban hilang/ganda | Simpan/aktivasi | SV; `INV-10` | 422 `INSTALLMENT_TOTAL_MISMATCH` | Cicilan | INS-001, INS-002 |
| KEU-BR-127 | Batas jadwal | *n* ≤ maks termin; DP ≥ minimal; jatuh tempo naik tegas, ≥ hari ini, ≤ akhir term bila dikonfigurasi | Kebijakan kampus | Simpan | FR + SV | 422 | Cicilan | INS-003 |
| KEU-BR-128 | Pembulatan termin | Rupiah penuh; selisih ke termin terakhir | Akurasi | Pembentukan | SV | — | Cicilan | INS-004 |
| KEU-BR-129 | Persetujuan rencana | Rencana memerlukan persetujuan; penyetuju ≠ pengaju | Kontrol keringanan | Approve | SV + SoD | 403 | Cicilan | INS-001 |
| KEU-BR-130 | Tidak dihitung ganda | Piutang dari tagihan; termin hanya membagi waktu | Laporan benar | Laporan | SV laporan | — | Cicilan, Laporan | INS-008, RPT-005 |
| KEU-BR-131 | Pelunasan dipercepat | Pembayaran melebihi termin berjalan melunasi termin berikutnya | Fleksibilitas | Alokasi | SV | — | Cicilan | INS-006 |
| KEU-BR-132 | Jadwal ulang berversi | Penjadwalan ulang membuat versi baru berizin atas sisa; termin lunas tetap | Histori | Reschedule | SV | 409 | Cicilan | INS-007 |
| KEU-BR-133 | Pembatalan rencana | Termin belum lunas dibatalkan; jatuh tempo tagihan dikembalikan/ditetapkan; berizin | Kontrol | Cancel | SV + persetujuan | 403 | Cicilan | INS-010 |
| KEU-BR-134 | Penyesuaian otomatis | Perubahan nominal tagihan saat rencana aktif menghasilkan versi baru otomatis (turun dari termin akhir; naik ke termin akhir/bagian di luar jadwal) | Jadwal selalu cocok dengan sisa | Perubahan nominal | SV | Rollback perubahan pemicu | Cicilan | INS-009 |
| KEU-BR-135 | Jatuh tempo efektif | Saat rencana aktif `invoices.due_date` = termin terakhir; `original_due_date` disimpan | Konsistensi tampilan | Aktivasi | SV | — | Cicilan | INS-001 |
| KEU-BR-136 | Rencana selesai | Rencana `completed` saat tagihan `paid` | Status rapi | Pelunasan | SV | — | Cicilan | INS-006 |

## 9. Beasiswa dan Potongan (140–159)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-140 | Urutan terkonfigurasi | Urutan tahap pengurang mengikuti `finance.calculation.sequence` | Kebijakan berbeda per kampus | Perhitungan tagihan | SV | — | Beasiswa | SCH-002 |
| KEU-BR-141 | Basis persentase | Persen dihitung dari sisa atau bruto komponen sesuai konfigurasi | Kebijakan eksplisit | Perhitungan | SV | — | Beasiswa | SCH-002 |
| KEU-BR-142 | Komponen tidak negatif | Σ pengurang per komponen ≤ nominal komponen | Tidak ada kewajiban negatif | Setiap baris pengurang | SV + invarian | Potongan dibatasi / 422 `DISCOUNT_EXCEEDS_LIMIT` | Beasiswa, Voucher | SCH-001, SCH-003 |
| KEU-BR-143 | Batas potongan | `max_percentage` per komponen dan `max_amount_per_invoice` per skema ditegakkan | Anggaran beasiswa | Perhitungan | SV | Dibatasi | Beasiswa | SCH-003 |
| KEU-BR-144 | Grup penumpukan | Skema satu `stacking_group` tidak digabung; prioritas terkecil menang | Mencegah potongan ganda antarprogram | Perhitungan | SV | Skema lain dilewati (tercatat) | Beasiswa | SCH-004 |
| KEU-BR-145 | Komponen dapat dipotong | Hanya baris `is_discountable` | Biaya tertentu tidak boleh dipotong | Perhitungan | SV | — | Beasiswa | SCH-001 |
| KEU-BR-146 | Kelayakan penerima | Penetapan aktif, berlaku pada term, memenuhi syarat skema dan jenis tagihan | Ketepatan sasaran | Penetapan & perhitungan | SV | 422 / tidak diterapkan | Beasiswa | SCH-005 |
| KEU-BR-147 | Satu penetapan aktif | Satu penetapan `pending/active` per mahasiswa × skema | Mencegah dobel | Pengajuan | DB unique `active_key` | 409 | Beasiswa | SCH-005 |
| KEU-BR-148 | Kuota penerima | Penerima aktif per term ≤ kuota skema | Anggaran | Persetujuan | SV kunci skema | 409 | Beasiswa | SCH-006 |
| KEU-BR-149 | Persetujuan penerima | Penetapan memerlukan persetujuan (bila diwajibkan); SoD | Kontrol | Approve | SV + SoD | 403 | Beasiswa | SCH-009 |
| KEU-BR-150 | Pencabutan tidak surut | Pencabutan berlaku mulai term yang ditetapkan; tagihan terbit tidak berubah tanpa koreksi eksplisit | Kepastian | Revoke | SV | — | Beasiswa | SCH-007 |
| KEU-BR-151 | Penerapan susulan | Penetapan setelah tagihan terbit diterapkan lewat `invoice_correction` (`SCHOLARSHIP_LATE_APPLY`), termasuk pembalikan kelebihan alokasi | Hak penerima terpenuhi | Aktivasi penetapan | SV | `failed` bila periode tertutup dsb. | Beasiswa, Tagihan | SCH-008 |
| KEU-BR-152 | Evaluasi berkala | Kelayakan dievaluasi berkala; hasil hanya menandai, keputusan oleh staf | Tidak ada pencabutan otomatis yang keliru | Job awal term | JOB | — | Beasiswa | SCH-011 |
| KEU-BR-153 | Potongan manual berizin | Potongan di luar skema hanya lewat `manual_discount` berizin | Kontrol diskresi | Pengajuan | SV + SoD | 403 / tidak berefek sebelum disetujui | Beasiswa | SCH-010 |
| KEU-BR-154 | Snapshot parameter | Parameter skema/voucher disalin ke `invoice_items.snapshot` | Perubahan skema tidak surut | Pembuatan baris | SV | — | Beasiswa | SCH-007 |

## 10. Voucher (160–179)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-160 | Kode unik | Kode dinormalisasi (huruf besar, tanpa spasi), unik per tenant | Tidak ambigu | Buat voucher | DB unique | 422 | Voucher | VCH-001 |
| KEU-BR-161 | Masa berlaku | Dipakai hanya bila `valid_from ≤ now < valid_until`; yang sudah diterapkan tetap berlaku | Kepastian promosi | Penerapan | SV | 422 `VOUCHER_EXPIRED` | Voucher | VCH-002 |
| KEU-BR-162 | Kuota total | `used + reserved ≤ total_quota` termasuk saat bersamaan | Anggaran promosi | Penerapan/reservasi | SV kunci + DB CHECK | 409 `VOUCHER_QUOTA_EXHAUSTED` | Voucher | VCH-004 |
| KEU-BR-163 | Sekali per tagihan | Voucher sama tidak dua kali pada tagihan sama | Mencegah potongan ganda | Penerapan | DB unique `active_key` | 409 `VOUCHER_ALREADY_USED` | Voucher | VCH-003 |
| KEU-BR-164 | Kuota per mahasiswa | Pemakaian aktif per subjek ≤ `per_student_quota` | Keadilan | Penerapan | SV dalam kunci | 409 `VOUCHER_ALREADY_USED` | Voucher | VCH-003 |
| KEU-BR-165 | Kelayakan voucher | Antartipe DAN, dalam tipe ATAU (prodi, fakultas, angkatan, jalur, komponen, jenis tagihan) | Sasaran program | Penerapan | SV | 422 `VOUCHER_NOT_ELIGIBLE` | Voucher | VCH-001, VCH-005 |
| KEU-BR-166 | Minimum & batas | Basis layak ≥ minimum; potongan ≤ batas maksimal dan ≤ sisa komponen | Kendali nilai | Penerapan | SV | 422 / dibatasi | Voucher | VCH-001, VCH-009 |
| KEU-BR-167 | Penggabungan voucher | Voucher lain pada tagihan yang sama hanya bila keduanya `stacking_allowed` | Kendali promosi | Penerapan | SV | 422 `VOUCHER_NOT_ELIGIBLE` | Voucher | VCH-005 |
| KEU-BR-168 | Tagihan yang dapat diberi voucher | Mahasiswa: `unpaid` tanpa alokasi & transaksi aktif; staf: juga `partial` bila nominal ≥ dibayar | Tidak mengubah tagihan yang sedang dibayar | Penerapan | SV | 409 `INVOICE_NOT_PAYABLE`/`ACTIVE_TRANSACTION_EXISTS` | Voucher | VCH-010 |
| KEU-BR-169 | Reservasi | Reservasi hanya untuk draf/checkout PMB; kedaluwarsa dilepas otomatis | Kuota tidak tertahan selamanya | Reservasi, job | JOB + SV | — | Voucher | VCH-007 |
| KEU-BR-170 | Pembatalan tagihan | Redemption `cancelled`; kuota kembali sesuai konfigurasi | Konsistensi | Pembatalan tagihan | SV | — | Voucher | VCH-006 |
| KEU-BR-171 | Pelepasan oleh pengguna | Pelepasan sebelum ada pembayaran menambah baris `adjustment` dan mengembalikan kuota | Baris tagihan immutable | Pelepasan | SV | 409 | Voucher | VCH-006 |
| KEU-BR-172 | Persetujuan voucher | Voucher hanya dapat dipakai setelah disetujui; pembuat ≠ penyetuju | Kontrol promosi | Approve | SV + SoD | 403 / `VOUCHER_INVALID` | Voucher | VCH-011 |
| KEU-BR-173 | Batas percobaan | Percobaan kode gagal dibatasi per pengguna | Anti tebak kode | Penerapan | Rate limiter | 429 | Voucher | VCH-008 |

## 11. Denda (180–199)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-180 | Nonaktif default | Denda tidak dibentuk sampai `finance.late_fee.enabled = true` dan ada aturan aktif | Kebijakan belum ditetapkan | Job | JOB | — | Denda | LTF-011 |
| KEU-BR-181 | Hari terlambat | Terlambat bila tanggal (zona tenant) > `due_date`; jatuh tempo berlaku s.d. 23:59:59 | Keadilan | Job | JOB `TenantClock` | — | Denda | LTF-001, LTF-002 |
| KEU-BR-182 | Masa tenggang | Denda mulai `due_date + grace_days + 1` | Toleransi | Job | JOB | — | Denda | LTF-001 |
| KEU-BR-183 | Dasar denda | Sisa pokok komponen dasar denda (waterfall) posisi akhir hari sebelumnya, atau pokok awal; per termin bila dikonfigurasi | Proporsional | Job | JOB | — | Denda | LTF-004, LTF-010 |
| KEU-BR-184 | Plafon | Total denda per tagihan dasar/termin ≤ plafon nominal/persen | Batas wajar | Job | JOB | Bucket dibatasi/tidak dibentuk | Denda | LTF-005 |
| KEU-BR-185 | Idempoten per bucket | Satu denda per tagihan/termin × aturan × bucket | Job aman diulang | Job | DB unique `scope_key` | Dilewati | Denda | LTF-003 |
| KEU-BR-186 | Berhenti setelah lunas | Tidak ada bucket baru bila dasar = 0 | Keadilan | Job | JOB | — | Denda | LTF-006 |
| KEU-BR-187 | Tanpa denda atas denda | Tagihan `late_fee` bukan tagihan dasar | Menghindari bunga berbunga | Job | JOB | — | Denda | LTF-001 |
| KEU-BR-188 | Pembebasan berizin | Pembebasan lewat `late_fee_waiver` dengan persetujuan | Kontrol diskresi | Pengajuan | SV + SoD | 403 | Denda | LTF-007 |
| KEU-BR-189 | Koreksi salah hitung | Kesalahan dikoreksi dengan pembalikan (`LATE_FEE_ERROR`), bukan pembebasan | Pelaporan jujur | Pengajuan | SV | — | Denda | LTF-008 |
| KEU-BR-190 | Tagihan denda terbuka | Bucket masuk tagihan denda terbuka per tagihan dasar; baru bila yang lama lunas/batal | Dokumen rapi | Job | SV + `billing_key` | — | Denda | LTF-001 |
| KEU-BR-191 | Tanggal efektif bayar | Denda memakai `paid_at`, bukan tanggal verifikasi; pembentukan ditunda bila ada pembayaran pending tepat waktu; bucket keliru dibalik otomatis | Mahasiswa tidak dirugikan verifikasi lambat | Job, verifikasi | JOB + SV | — | Denda | LTF-008 |
| KEU-BR-192 | Mengejar bucket | Job membentuk bucket yang terlewat dengan tanggal dan dasar masing-masing | Ketahanan jadwal | Job | JOB | — | Denda | LTF-009 |
| KEU-BR-193 | Aturan ke depan | Perubahan aturan hanya untuk bucket berikutnya | Kepastian | Ubah aturan | SV | — | Denda | LTF-012 |

## 12. Deposit (200–219)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-200 | Saldo = mutasi | `balance = Σ kredit − Σ debit`; `balance_after` berurutan | Dapat ditelusuri | Setiap mutasi; job harian | SV + `INV-07/08` | Alert integritas | Deposit | DEP-005 |
| KEU-BR-201 | Saldo tersedia | Tersedia = `balance − held_amount` | Dana ditahan tidak dipakai | Pemakaian/refund | SV + DB CHECK | 422 | Deposit | DEP-006 |
| KEU-BR-202 | Sumber unik | Setiap mutasi bersumber; satu sumber satu mutasi per jenis | Mencegah dobel | Mutasi | DB unique sumber | 409 | Deposit | DEP-009 |
| KEU-BR-203 | Sumber kredit | Kredit hanya dari konversi kelebihan bayar, top-up terverifikasi, pembalikan, penyesuaian berizin | Asal dana jelas | Kredit | SV | 422 | Deposit | DEP-010 |
| KEU-BR-204 | Konversi kelebihan bayar | Konversi ≤ `unapplied_amount` (dikurangi refund aktif) | Dana tidak dobel | Konversi | SV kunci | 422 `ALLOCATION_EXCEEDS_AVAILABLE` | Deposit | DEP-001 |
| KEU-BR-205 | Pemakaian | Hanya untuk tagihan mahasiswa yang sama, ≤ tersedia dan ≤ sisa | Konsistensi | Pemakaian | SV kunci | 422 | Deposit | DEP-002 |
| KEU-BR-206 | Pemakaian mandiri [K] | Mahasiswa memakai sendiri hanya bila diizinkan | Kebijakan | Pemakaian portal | PL + pengaturan | 403 | Deposit | DEP-002 |
| KEU-BR-207 | Pembatalan pemakaian | Lewat `allocation_reversal`: alokasi negatif + kredit `invoice_payment_reversal` | Histori utuh | Penerapan | SV | `failed` | Deposit | DEP-008 |
| KEU-BR-208 | Refund deposit | Diajukan → ditahan → disetujui → selesai (debit) | Kontrol keluar dana | Refund | SV + SoD | 422 / 403 | Deposit, Refund | DEP-006 |
| KEU-BR-209 | Akun dibekukan | Akun `frozen` menolak pemakaian & refund | Investigasi | Debit | SV | 409 `DEPOSIT_FROZEN` | Deposit | DEP-007 |
| KEU-BR-210 | Satu akun | Satu akun per mahasiswa, dibuat otomatis | Sederhana | Kredit pertama | DB unique | — | Deposit | DEP-001 |
| KEU-BR-211 | Penutupan akun | Hanya bila saldo dan penahanan nol | Tidak ada dana tertinggal | Tutup | SV | 409 | Deposit | DEP-007 |
| KEU-BR-212 | Penyesuaian berizin | Penyesuaian saldo lewat `deposit_adjustment` dengan persetujuan | Kontrol | Pengajuan | SV + SoD | 403 | Deposit | DEP-005 |
| KEU-BR-213 | Deposit bukan pelunasan | Saldo deposit tidak mengurangi piutang sampai dipakai | Piutang jujur | Laporan | SV laporan | — | Deposit, Laporan | AR-001 |

## 13. UKT (220–239)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-220 | Golongan = klasifikasi | Golongan tidak menyimpan nominal; nominal di aturan tarif | Pemisahan konsep | Master | Skema | — | UKT | UKT-001 |
| KEU-BR-221 | Tidak tumpang tindih | Rentang penetapan aktif satu mahasiswa tidak beririsan; penetapan baru membatasi yang lama | Satu golongan per term | Aktivasi | SV + DB unique | 409 | UKT | UKT-004 |
| KEU-BR-222 | Penetapan wajib | Komponen UKT memerlukan penetapan aktif yang mencakup term | Tidak menagih golongan keliru | Resolusi tarif | SV | 422 `UKT_GROUP_MISSING` | UKT, Tagihan | UKT-001, UKT-002 |
| KEU-BR-223 | Persetujuan penetapan | Penetapan diaktifkan oleh pejabat berwenang; SoD | Kontrol | Approve | SV + SoD | 403 | UKT | UKT-005 |
| KEU-BR-224 | Perubahan tidak surut | Perubahan golongan = penetapan baru; tagihan terbit berubah hanya lewat koreksi (`UKT_CHANGED`) | Kepastian | Aktivasi | SV | — | UKT | UKT-003 |
| KEU-BR-225 | Keberatan | Dalam batas waktu; satu keberatan aktif per penetapan; alasan wajib | Hak mahasiswa terkendali | Pengajuan | FR + DB unique | 422 / 409 | UKT | UKT-005, UKT-006 |
| KEU-BR-226 | Hasil keberatan | Keberatan diterima menghasilkan penetapan baru (`basis = appeal`) yang disetujui pejabat berbeda | Kontrol | Putusan | SV + SoD | 403 | UKT | UKT-005 |
| KEU-BR-227 | Kelengkapan tarif | Setiap golongan aktif yang dipakai mahasiswa memiliki tarif UKT pada jadwal berlaku | Penagihan tidak gagal | Validasi | SV laporan | Temuan kritis | UKT, Tarif | UKT-007 |
| KEU-BR-228 | Dokumen rahasia | Dokumen & ringkasan penilaian hanya untuk staf keuangan berwenang dan pemilik; akses diaudit | Privasi | Akses | PL + audit | 403 | UKT | UKT-008 |
| KEU-BR-229 | Impor massal | Impor memvalidasi per baris; baris valid menjadi draf; persetujuan tetap per baris | Efisiensi tanpa melepas kontrol | Impor | SV | Laporan galat | UKT | UKT-009 |
| KEU-BR-230 | UKT bukan beasiswa | Beasiswa mengurangi tagihan, tidak mengubah golongan | Pemisahan konsep | Perhitungan | SV | — | UKT, Beasiswa | UKT-001 |

## 14. Piutang dan Laporan (240–259)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-240 | Piutang aktif | Piutang = Σ sisa tagihan `unpaid/partial`; draf & batal tidak dihitung | Definisi tunggal | Laporan | SV laporan | — | Laporan | AR-001, AR-004 |
| KEU-BR-241 | Pending tidak mengurangi | Pembayaran `pending_verification` tidak mengurangi piutang; ditampilkan terpisah | Konservatif | Laporan | SV laporan | — | Laporan | AR-002 |
| KEU-BR-242 | Umur piutang | Dihitung dari jatuh tempo efektif per bagian; kategori dapat dikonfigurasi, berurutan, menyeluruh | Analisis tunggakan | Laporan | SV + validasi pengaturan | 422 pengaturan | Laporan | AR-003 |
| KEU-BR-243 | Ketertelusuran | Setiap angka dapat dibuka ke daftar sumber dengan total sama | Dapat diaudit | Laporan | Uji otomatis | — | Laporan | AR-001 |
| KEU-BR-244 | Arus vs posisi | Laporan arus berbasis `posting_date`/`issued_at`; posisi berbasis tanggal acuan | Konsistensi periodik | Laporan | SV | — | Laporan | RPT-001, RPT-002 |
| KEU-BR-245 | Cakupan unit | Dekan/kaprodi hanya melihat unitnya | Kerahasiaan | Laporan | `FinanceScope` | 404 / data tersaring | Laporan | AR-005 |
| KEU-BR-246 | Ekspor laporan | Ekspor memerlukan izin ekspor, dicatat, dibatasi | Kebocoran data | Ekspor | PL + audit | 403 | Laporan | RPT-003 |
| KEU-BR-247 | Snapshot | Snapshot harian diganti saat diulang; dibekukan untuk periode tertutup | Historis stabil | Job | JOB | — | Laporan | AR-006 |
| KEU-BR-248 | Tingkat pelunasan | `collection_rate` per kohort = Σ dibayar ÷ Σ nominal tagihan efektif | Definisi baku | Laporan | SV | — | Laporan | AR-007 |

## 15. Cekal (260–279)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-260 | Nonaktif tanpa kebijakan | Tanpa `finance.hold.enabled` dan kebijakan aktif, tidak ada cekal otomatis | Kebijakan belum ditetapkan | Evaluasi | SV | — | Cekal | HLD-003 |
| KEU-BR-261 | Syarat cekal | Cekal bila tunggakan jatuh tempo (setelah tenggang, komponen & tipe kebijakan) > 0 dan ≥ ambang | Kebijakan kampus | Evaluasi | SV | — | Cekal | HLD-001, HLD-003 |
| KEU-BR-262 | Satu cekal aktif | Satu cekal aktif per mahasiswa × kebijakan × term | Tidak dobel | Evaluasi | DB unique `active_key` | Diperbarui | Cekal | HLD-001 |
| KEU-BR-263 | Buka otomatis | Cekal dibuka saat syarat tidak terpenuhi, target ≤ 60 detik setelah pembayaran terverifikasi | Layanan segera pulih | Event pembayaran | SV + antrean prioritas | Alert bila melewati SLA | Cekal | HLD-002 |
| KEU-BR-264 | Pending tidak membuka | Pembayaran belum terverifikasi tidak membuka cekal | Konservatif | Evaluasi | SV | — | Cekal | HLD-011 |
| KEU-BR-265 | Pengecualian | Per layanan, berbatas waktu ≤ maksimum, beralasan, berizin | Keringanan terkendali | Pengajuan | FR + SV + SoD | 422 / 403 | Cekal | HLD-004 |
| KEU-BR-266 | Manual | Cekal manual beralasan; pembukaan manual hanya untuk cekal manual dan berizin; cekal otomatis dibuka lewat pengecualian | Tidak terjadi bolak-balik status | Aksi manual | SV + SoD | 403 / 409 | Cekal | HLD-007 |
| KEU-BR-267 | Sinkronisasi | Perubahan dikirim lewat outbox idempoten, di-retry dengan *backoff*, gagal → alert | Konsistensi KEU–SIAKAD | Perubahan cekal | Worker | `sync_status = failed` | Cekal, SIAKAD | HLD-005 |
| KEU-BR-268 | Penegakan backend | Layanan akademik memeriksa cekal di service backend | Tidak dapat dilewati | KRS, ujian, dll. | Guard | 409 `ACADEMIC_BLOCKED` | SIAKAD | HLD-006 |
| KEU-BR-269 | Rekonsiliasi status | Status KEU dan proyeksi SIAKAD dibandingkan harian | Deteksi selisih | Job | JOB | Sinkron ulang | Cekal | HLD-010 |
| KEU-BR-270 | Nominal rahasia | Pihak non-keuangan tidak melihat nominal tunggakan | Privasi | Tampilan, notifikasi | PL + Resource | Kolom disaring | Cekal | HLD-008, NTF-005 |
| KEU-BR-271 | Evaluasi terjadwal | Evaluasi harian sebagai jaring pengaman event | Ketahanan | Job | JOB | — | Cekal | HLD-010 |
| KEU-BR-272 | Per termin | Mahasiswa bercicilan dinilai per termin jatuh tempo bila dikonfigurasi | Adil bagi peserta cicilan | Evaluasi | SV | — | Cekal, Cicilan | HLD-009 |
| KEU-BR-273 | Gagal sinkron bukan pembukaan | Kegagalan sinkronisasi tidak dianggap pembukaan; SIAKAD memakai proyeksi terakhir | Konservatif & aman | Sinkronisasi | Worker | Alert prioritas untuk pembukaan yang gagal | Cekal | HLD-005 |

## 16. Rekonsiliasi, Refund, Koreksi, Periode (280–299)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-280 | Unsur koreksi | Setiap koreksi memiliki alasan, pelaku, waktu, referensi asal, persetujuan | Akuntabilitas | Pengajuan | FR + SV | 422 | Koreksi | REC-001, SEC-010 |
| KEU-BR-281 | Reversal satu-satunya | Efek terverifikasi dibatalkan dengan baris pembalik; satu baris dibalik sekali | Histori utuh | Penerapan | SV + DB unique `reversal_of_id` | 409 | Koreksi | REC-001, REC-003, REC-006 |
| KEU-BR-282 | Batas refund & penahanan | Refund ≤ dana tersedia sumber; dana ditahan saat diajukan | Dana tidak dipakai ganda | Pengajuan | SV kunci | 422 | Refund | REC-004, REC-005 |
| KEU-BR-283 | Persetujuan refund | Bendahara; tingkat kedua di atas ambang; SoD | Kontrol keluar dana | Approve | SV + workflow | 403 | Refund | REC-004 |
| KEU-BR-284 | Bukti penyelesaian | `completed` wajib referensi/bukti transfer | Bukti audit | Complete | FR | 422 | Refund | REC-004 |
| KEU-BR-285 | Refund gagal | Gagal/ditolak/batal melepas penahanan tanpa mengubah ledger | Dana kembali tersedia | Transisi | SV | — | Refund | REC-014 |
| KEU-BR-286 | Impor rekonsiliasi | Berkas unik per jenis (checksum); baris ganda ditandai | Tidak dobel | Impor | DB unique | 409 | Rekonsiliasi | REC-009 |
| KEU-BR-287 | Pencocokan | Satu baris eksternal ↔ satu transaksi; batch ditutup hanya tanpa item terbuka | Kelengkapan | Pencocokan, penutupan | SV | 409 | Rekonsiliasi | REC-007 |
| KEU-BR-288 | Lewat jalur verifikasi | Transaksi provider yang lunas tetapi belum tercatat diproses lewat cek status, bukan input manual | Satu jalur kebenaran | Penyelesaian item | SV | — | Rekonsiliasi | REC-008 |
| KEU-BR-289 | Dana tak teridentifikasi | Kredit bank tanpa mahasiswa dicatat `pending_verification` tanpa `student_id` sampai diidentifikasi dan diverifikasi | Dana tidak hilang | Rekonsiliasi | SV | — | Rekonsiliasi | REC-012 |
| KEU-BR-290 | Syarat penutupan periode | Tidak ada penyesuaian tertunda, sesi kas terbuka, pelanggaran invarian; rekonsiliasi selesai/diakui | Periode bersih | Penutupan | SV | Daftar pemblokir | Periode | REC-010 |
| KEU-BR-291 | Buka kembali periode | Dua tingkat persetujuan + alasan; ringkasan baru saat ditutup ulang | Kontrol | Reopen | SV + workflow | 403 | Periode | REC-011 |
| KEU-BR-292 | Write-off nonaktif | Penghapusan piutang tidak tersedia sampai diputuskan [K] | Keputusan akuntansi | Pengajuan | SV | 422 | Koreksi | REC-015 |
| KEU-BR-293 | Jurnal seimbang | Ekspor akuntansi Σ debit = Σ kredit; semua komponen berkode akun | Data akuntansi valid | Ekspor | SV | Ekspor gagal | Akuntansi | REC-013 |
| KEU-BR-294 | Metode refund | Gateway hanya bila didukung & pembayaran asal dari provider itu; tunai dalam sesi kas; transfer ke rekening atas nama mahasiswa | Dana ke pihak yang benar | Process | SV | 422 | Refund | REC-004 |
| KEU-BR-295 | Pembalikan berantai | Reversal ditolak bila efek turunan (pemakaian deposit dsb.) belum dibalik | Saldo tidak negatif | Penerapan | SV | `failed` + langkah | Koreksi | REC-002 |
| KEU-BR-296 | Selisih diselesaikan | Setiap selisih rekonsiliasi diselesaikan atau diabaikan dengan alasan tertulis | Tidak ada selisih tak terjelaskan | Penyelesaian | FR | 422 | Rekonsiliasi | REC-007 |

## 17. Keamanan dan Audit (300–319)

| ID | Nama | Deskripsi | Alasan bisnis | Pemicu | Validasi | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| KEU-BR-300 | Pemisahan tugas | Pembuat ≠ penyetuju/verifikator untuk pasangan di [03 §7](03-role-dan-hak-akses.md#7-pemisahan-tugas-sod) bila SoD aktif | Mencegah kecurangan | Aksi setuju/verifikasi | SV guard + listener workflow | 403 `SELF_APPROVAL_FORBIDDEN` | Semua | MAN-009, TAR-005, SCH-009, VCH-011 |
| KEU-BR-301 | Otorisasi | Setiap endpoint memeriksa permission dan scope | Akses tepat | Setiap permintaan | PL + middleware | 403 / 404 | Semua | RBAC-001, RBAC-002, RBAC-003, UI-001 |
| KEU-BR-302 | Tanda tangan webhook | Webhook diverifikasi dengan secret akun, perbandingan waktu-konstan | Mencegah pemalsuan | Webhook | Adapter | 401, `invalid_signature` | Online | PG-005 |
| KEU-BR-303 | Anti-replay | Stempel waktu ≤ toleransi bila tersedia; deduplikasi | Mencegah pemutaran ulang | Webhook | Adapter + DB | 401 / `duplicate` | Online | PG-013 |
| KEU-BR-304 | Perlindungan berkas | Berkas privat, tipe/ukuran dibatasi, akses berizin dan diaudit | Privasi | Unggah/unduh | FileManagement + PL | 403 / 422 | Manual, UKT | SEC-002, MAN-008 |
| KEU-BR-305 | Kerahasiaan kredensial | Kredensial tidak muncul di respons, log, audit | Keamanan | Semua | `$hidden`, review log | — | Online | PG-016 |
| KEU-BR-306 | Masking | Nomor rekening termasking; nomor penuh hanya lewat aksi yang diaudit | Privasi | Tampilan/ekspor | Resource | — | Semua | SEC-003, MST-004, SEC-009 |
| KEU-BR-307 | Cakupan audit | Tindakan dalam katalog [24 §8](24-keamanan-audit-dan-kontrol-internal.md#8-katalog-audit) tercatat lengkap | Akuntabilitas | Tindakan | SV | Transaksi gagal bila audit gagal | Semua | SEC-004, SEC-009 |
| KEU-BR-308 | Ekspor sensitif | Ekspor data sensitif dibatasi peran, *watermark*, kedaluwarsa | Kebocoran | Ekspor | PL | 403 | Laporan | RPT-003 |
| KEU-BR-309 | Retensi | Data disimpan sesuai kebijakan; pembersihan hanya data yang ditetapkan | Kepatuhan | Job retensi | JOB | — | Semua | SEC-011 |
| KEU-BR-310 | Deteksi anomali | Aturan ANM dievaluasi dan diberitahukan | Deteksi dini | Job | JOB | Alert | Semua | SEC-006 |
| KEU-BR-311 | Pembatasan laju | Endpoint sensitif dibatasi laju | Penyalahgunaan | Permintaan | Rate limiter | 429 | API | SEC-005 |
| KEU-BR-312 | Milik sendiri | Mahasiswa hanya mengakses data miliknya | Privasi | Endpoint mahasiswa | PL | 404 | Portal | RBAC-005 |
| KEU-BR-313 | Super admin | Tindakan tulis keuangan oleh super admin hanya *break-glass* beralasan, diaudit, tunduk SoD | Batas peran platform | Aksi tulis | Guard | 403 | Semua | RBAC-004 |
| KEU-BR-314 | Audit immutable | Tidak ada endpoint ubah/hapus audit; histori status append-only | Integritas bukti | — | Tidak ada route | — | Audit | SEC-008 |
