# 13 — Rancangan Denda Keterlambatan

> **Status:** rancangan. Disusun 2026-10-13. Fitur di balik saklar `finance.late_fee.enabled` (default `false`) sampai kebijakan ditetapkan ([KEU-Q-06](29-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-keu-q)).
> Kembali ke [README](README.md) · Skema: [19 §3.10](19-rancangan-database-dan-relasi.md#310-denda) · Aturan: `KEU-BR-180`–`199` ([25](25-business-rules-dan-validasi.md)) · Keputusan: [KEU-KD-03](29-glossary-dan-keputusan-bisnis.md#2-keputusan-desain-keu-kd), [KEU-KD-28](29-glossary-dan-keputusan-bisnis.md#2-keputusan-desain-keu-kd), [KEU-KD-29](29-glossary-dan-keputusan-bisnis.md#2-keputusan-desain-keu-kd)

Denda adalah kewajiban tambahan karena pembayaran melewati jatuh tempo dan masa tenggang. Denda dibentuk oleh job terjadwal yang idempoten, per *bucket* waktu, dan dicatat sebagai **tagihan terpisah** bertipe `late_fee` yang menunjuk tagihan dasarnya.

**Peta format baku:** tujuan/aktor/prasyarat §1 · data masukan §3 · proses & status §4–§7 · aturan & validasi §3–§8 · hak akses §9 · data & relasi §10 · API §11 · UI §12 · notifikasi §13 · laporan §14 · kegagalan §15 · audit §16 · AC & TC §17.

## Daftar Isi

1. [Tujuan, Aktor, dan Prasyarat](#1-tujuan-aktor-dan-prasyarat)
2. [Konsep dan Pilihan Desain](#2-konsep-dan-pilihan-desain)
3. [Aturan Denda](#3-aturan-denda)
4. [Frekuensi, Bucket, dan Perhitungan](#4-frekuensi-bucket-dan-perhitungan)
5. [Job Terjadwal dan Idempotensi](#5-job-terjadwal-dan-idempotensi)
6. [Tagihan Denda](#6-tagihan-denda)
7. [Pembebasan dan Koreksi Denda](#7-pembebasan-dan-koreksi-denda)
8. [Interaksi dengan Fitur Lain](#8-interaksi-dengan-fitur-lain)
9. [Hak Akses](#9-hak-akses)
10. [Struktur Data dan Relasi](#10-struktur-data-dan-relasi)
11. [API dan Event](#11-api-dan-event)
12. [UI/UX](#12-uiux)
13. [Notifikasi](#13-notifikasi)
14. [Laporan Terkait](#14-laporan-terkait)
15. [Skenario Kegagalan](#15-skenario-kegagalan)
16. [Audit Trail](#16-audit-trail)
17. [Acceptance Criteria dan Test Case](#17-acceptance-criteria-dan-test-case)

---

## 1. Tujuan, Aktor, dan Prasyarat

| Aspek | Isi |
|---|---|
| **Tujuan** | Mengenakan denda keterlambatan sesuai kebijakan secara konsisten, dapat dijelaskan, dan tidak ganda |
| **Manfaat** | Mendorong ketepatan bayar; perhitungan otomatis menghindari kesalahan manual; pembebasan tercatat dan berizin |
| **Aktor** | Sistem (job), administrator keuangan (aturan), staf keuangan (pengajuan pembebasan), bendahara (persetujuan), mahasiswa (menerima dan membayar) |
| **Prasyarat** | Kebijakan denda disetujui ([KEU-Q-06](29-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-keu-q)); komponen kategori `late_fee` (`DENDA`); aturan aktif; `finance.late_fee.enabled = true`; scheduler berjalan ([KEU-A-13](29-glossary-dan-keputusan-bisnis.md#4-asumsi-keu-a)) |

## 2. Konsep dan Pilihan Desain

Permintaan bisnis meminta memilih antara denda sebagai **komponen tagihan tambahan** atau **transaksi penyesuaian**. Rancangan memilih **tagihan terpisah berisi baris denda** ([KEU-KD-03](29-glossary-dan-keputusan-bisnis.md#2-keputusan-desain-keu-kd)):

| Opsi | Kelebihan | Kekurangan | Keputusan |
|---|---|---|---|
| Baris denda di tagihan dasar | Satu dokumen | Nominal tagihan dasar berubah setelah dibayar sebagian; snapshot dan cicilan terganggu; denda ikut dipotong beasiswa bila tidak hati-hati | Ditolak |
| Transaksi penyesuaian di luar tagihan | Tidak menyentuh tagihan | Denda tidak dapat dibayar/dialokasikan/dikuitansikan dengan mekanisme yang sama; tidak terlihat di piutang | Ditolak |
| **Tagihan `late_fee` terpisah (`parent_invoice_id`)** | Tagihan dasar tetap utuh; denda dapat dibayar, dicicil tidak, dikecualikan dari beasiswa/cekal secara terpisah; pembebasan berupa baris `late_fee_waiver` yang jelas | Mahasiswa melihat dua dokumen | **Dipilih**; UI menampilkan denda sebagai "Denda terkait" pada tagihan dasar |

Setiap denda per periode dicatat di `late_fee_assessments` (satu baris per tagihan dasar/termin × aturan × bucket) yang menjadi dasar idempotensi dan audit.

## 3. Aturan Denda

Kolom lengkap: [19 §3.10.1](19-rancangan-database-dan-relasi.md#3101-late_fee_rules--baru).

| Kebutuhan bisnis | Kolom | Contoh |
|---|---|---|
| Nominal tetap | `calc_type = fixed`, `value` | Rp100.000 per bucket |
| Persentase | `calc_type = percentage`, `value` | 2% per bulan |
| Per hari keterlambatan | `frequency = daily` | Rp5.000 per hari |
| Per periode tertentu | `frequency = weekly` / `monthly` | 2% per bulan |
| Sekali | `frequency = once` | Rp250.000 sekali |
| Maksimum denda | `max_amount`, `max_percentage` | Maks 10% dari pokok awal |
| Masa tenggang | `grace_days` | 7 hari |
| Jenis tagihan | `invoice_type` (`null` = semua kecuali `late_fee`) | `semester` |
| Periode akademik | `academic_term_id` (`null` = semua) | 2026/2027 Ganjil |
| Dasar perhitungan | `base_type` | `outstanding_principal` (sisa pokok) atau `original_principal` |
| Per termin cicilan | `apply_per_installment_term` | `true` |
| Masa berlaku aturan | `valid_from`, `valid_until` | |

Aturan validasi:

- Pada satu kombinasi (`invoice_type`, `academic_term_id`) hanya satu aturan aktif dengan masa berlaku beririsan. Aturan dengan `academic_term_id` spesifik mengalahkan aturan umum (`null`).
- `fee_component_id` wajib komponen kategori `late_fee` ([KEU-BR-021](25-business-rules-dan-validasi.md)).
- Perubahan aturan hanya berlaku untuk bucket **berikutnya**; bucket yang sudah dibentuk tidak dihitung ulang ([KEU-BR-193](25-business-rules-dan-validasi.md)).

## 4. Frekuensi, Bucket, dan Perhitungan

### 4.1 Tanggal dan zona waktu

| Ketentuan | Isi ([KEU-BR-181](25-business-rules-dan-validasi.md), [KEU-BR-182](25-business-rules-dan-validasi.md), [KEU-BR-012](25-business-rules-dan-validasi.md)) |
|---|---|
| Batas jatuh tempo | Tanggal jatuh tempo berlaku sampai **23:59:59 zona tenant** (`universities.timezone`). Pembayaran pada tanggal itu tepat waktu |
| Jatuh tempo efektif | `invoices.due_date`; untuk tagihan bercicilan dengan `apply_per_installment_term`, jatuh tempo masing-masing termin ([10 §8](10-rancangan-cicilan-tagihan.md#8-denda-dan-cekal-per-termin)) |
| Hari terlambat | `days_late(d) = d − due_date` (hari kalender) |
| Tanggal pertama dapat didenda | `first_date = due_date + grace_days + 1` |
| Tanggal efektif pembayaran | `payments.paid_at` (tanggal bayar), **bukan** tanggal verifikasi ([KEU-BR-191](25-business-rules-dan-validasi.md), [KEU-KD-28](29-glossary-dan-keputusan-bisnis.md#2-keputusan-desain-keu-kd)) |

### 4.2 Bucket

| `frequency` | Tanggal bucket ke-*k* | `bucket_key` | Maks bucket |
|---|---|---|---|
| `once` | `first_date` | `once` | 1 |
| `daily` | `first_date + k hari` | `YYYY-MM-DD` | tak terbatas (dibatasi plafon) |
| `weekly` | `first_date + 7k hari` | `YYYY-Www` (ISO week tanggal bucket) | tak terbatas |
| `monthly` | `first_date + k bulan` (tanggal ulang; bila tanggal tidak ada, hari terakhir bulan) | `YYYY-MM` | tak terbatas |

**Rekomendasi [R]:** gunakan `monthly` atau `once` sebagai default kebijakan. `daily` didukung, tetapi menghasilkan banyak baris (lebih dari 300.000 baris per tahun pada tenant 10.000 mahasiswa, [19 §9](19-rancangan-database-dan-relasi.md#9-ringkasan-indeks-dan-volume)), banyak notifikasi, dan sulit dijelaskan kepada mahasiswa.

### 4.3 Dasar dan nominal

```
pokok_terutang(d) = Σ sisa komponen is_late_fee_base pada tagihan dasar menurut waterfall (07 §4.5),
                    menghitung hanya alokasi dari pembayaran dengan paid_at ≤ d − 1 dan pemakaian deposit
                    yang dibukukan ≤ d − 1                                            (posisi akhir hari sebelumnya)
pokok_awal        = Σ neto komponen is_late_fee_base saat tagihan dasar terbit
dasar(d)          = base_type = outstanding_principal ? pokok_terutang(d) : (pokok_terutang(d) > 0 ? pokok_awal : 0)
denda(d)          = fixed ? value : round(dasar(d) × value%)
plafon            = min(COALESCE(max_amount, ∞), COALESCE(max_percentage × pokok_awal, ∞))
denda(d)          = min(denda(d), plafon − Σ amount bucket sebelumnya yang tidak reversed)
bila dasar(d) = 0 atau denda(d) ≤ 0 → bucket tidak dibentuk
```

- **Pembayaran sebagian** menurunkan `pokok_terutang` sehingga denda bucket berikutnya lebih kecil ([KEU-BR-183](25-business-rules-dan-validasi.md)).
- **Berhenti setelah lunas**: bila pokok yang menjadi dasar denda sudah nol, tidak ada bucket baru ([KEU-BR-186](25-business-rules-dan-validasi.md)). Denda yang sudah dibentuk tetap terutang.
- **Plafon** dihitung kumulatif per tagihan dasar (atau per termin bila per termin). Pembebasan (`waived`) tetap dihitung terhadap plafon; pembalikan karena kesalahan (`reversed`) tidak ([KEU-BR-184](25-business-rules-dan-validasi.md)).
- **Per termin**: `pokok_terutang` = sisa termin × (Σ neto komponen dasar-denda yang dapat dicicil ÷ `installable_amount`), yaitu porsi termin yang berasal dari komponen dasar denda.
- **Tidak ada denda atas denda**: tagihan `late_fee` tidak pernah menjadi tagihan dasar ([KEU-BR-187](25-business-rules-dan-validasi.md)).

### 4.4 Contoh

Aturan `DENDA-SMT`: `monthly`, 2% dari `outstanding_principal`, `grace_days` 7, `max_percentage` 10%. Tagihan dasar Citra `TGH-2026-0002001` (Rp8.650.000: SPP 6.500.000, SKS 1.800.000, REG 250.000, ADM 100.000), jatuh tempo **15 September 2026**. Komponen dasar denda: SPP dan SKS → `pokok_awal` = **8.300.000**; plafon = 830.000.

| Tanggal | Peristiwa | Pokok terutang | Denda bucket | Σ denda |
|---|---|---:|---:|---:|
| 15 Sep | Jatuh tempo (tepat waktu sampai 23:59 WIB) | 8.300.000 | — | 0 |
| 16–22 Sep | Masa tenggang | 8.300.000 | — | 0 |
| **23 Sep** | Bucket `2026-09` (`first_date`) | 8.300.000 | 2% = **166.000** | 166.000 |
| 10 Okt | Bayar Rp4.000.000 → waterfall: REG 250.000, SPP 3.750.000 | 4.550.000 (SPP 2.750.000 + SKS 1.800.000) | — | 166.000 |
| **23 Okt** | Bucket `2026-10` | 4.550.000 | 2% = **91.000** | 257.000 |
| 20 Nov | Lunas sisa tagihan dasar | 0 | — | 257.000 |
| 23 Nov | Bucket `2026-11` | 0 | tidak dibentuk | 257.000 |

Kedua bucket masuk ke satu tagihan denda terbuka `late_fee` (§6). Total denda Rp257.000 masih di bawah plafon Rp830.000.

## 5. Job Terjadwal dan Idempotensi

`finance:assess-late-fees`, dijadwalkan per tenant pada `finance.late_fee.run_at` (00:30 zona tenant):

```
untuk setiap tagihan dasar kandidat (status unpaid/partial, bukan late_fee, aturan cocok, first_date ≤ hari ini):
    BEGIN
      lock finance_sequences (bila perlu nomor) → lock invoices dasar & tagihan denda terbuka (urut id)
      untuk setiap bucket b dengan tanggal ≤ hari ini yang belum ada (mengejar ketinggalan, KEU-BR-192):
          tunda bila: ada pembayaran pending_verification milik mahasiswa dengan paid_at ≤ tanggal b − 1
                      atau sengketa aktif dan finance.dispute.suspend_late_fee
          hitung §4.3
          INSERT late_fee_assessments (scope_key unik)  → bila bentrok: lewati (sudah ada)
          tambah baris late_fee ke tagihan denda terbuka (§6)
      histori, outbox InvoiceIssued/InvoiceStatusChanged untuk tagihan denda, notifikasi
    COMMIT
```

| Jaminan | Mekanisme |
|---|---|
| Tidak ganda bila job dijalankan dua kali atau paralel ([KEU-BR-185](25-business-rules-dan-validasi.md)) | Unique `(university_id, scope_key)` pada `late_fee_assessments` (`invoice:termin:aturan:bucket`) + `withoutOverlapping()` per tenant |
| Mengejar bucket yang terlewat | Job menghitung semua bucket sampai hari ini; tanggal bucket dan dasarnya tetap mengikuti tanggal bucket, bukan tanggal job berjalan |
| Satu transaksi per tagihan dasar | Kegagalan satu tagihan tidak menggagalkan yang lain |
| `run_id` | Setiap eksekusi tercatat; hasil (jumlah bucket dibentuk, ditunda, dilewati) tersedia di halaman pemantauan |

## 6. Tagihan Denda

| Aspek | Ketentuan ([KEU-BR-190](25-business-rules-dan-validasi.md), [KEU-KD-29](29-glossary-dan-keputusan-bisnis.md#2-keputusan-desain-keu-kd)) |
|---|---|
| Kunci | `billing_key = late_fee:{parent_invoice_id}:{seq}`. Paling banyak **satu** tagihan denda terbuka (`unpaid`/`partial`) per tagihan dasar |
| Penambahan bucket | Ditambahkan sebagai baris `late_fee` (`source_type = late_fee_assessment`) ke tagihan denda terbuka. Bila tidak ada (belum ada atau sudah `paid`/`cancelled`), dibuat tagihan denda baru dengan `seq + 1` |
| Jatuh tempo | Saat dibuat: tanggal bucket + `finance.late_fee.due_days` (7 [K], [KEU-Q-30](29-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-keu-q)). Saat bucket baru ditambahkan: `due_date = max(due_date, tanggal bucket + due_days)` (perubahan otomatis yang menguntungkan mahasiswa, tercatat `late_fee_due_date_extended`) |
| Sifat | Tidak dapat dipotong beasiswa/voucher (komponen `DENDA` tidak `is_discountable`), tidak dapat dicicil, tidak menjadi dasar denda, ikut cekal hanya bila `finance_hold_policies.include_late_fee = true` |
| Alokasi | Mengikuti FIFO; posisi dalam `finance.allocation.invoice_type_priority` (default denda paling akhir pada jatuh tempo yang sama) |
| Pembatalan tagihan dasar | Tagihan denda terbuka tanpa alokasi ikut dibatalkan ([06 §8.3](06-rancangan-tagihan-mahasiswa.md#83-pembatalan)); yang sudah dibayar sebagian tetap dan staf memutuskan pembebasan |

## 7. Pembebasan dan Koreksi Denda

| Jenis | Kapan | Mekanisme | Persetujuan |
|---|---|---|---|
| **Pembebasan** (kebijakan) | Keringanan, kondisi khusus | `finance_adjustments` `late_fee_waiver` atas satu bucket (`target_type = late_fee_assessment`) atau seluruh tagihan denda; menambah baris `late_fee_waiver` negatif yang menunjuk baris denda; `late_fee_assessments.waived_amount` bertambah, status `waived` bila penuh ([KEU-BR-188](25-business-rules-dan-validasi.md)) | Selalu, oleh bendahara (`finance.approval.thresholds.late_fee_waiver.max_amount_officer = 0`) |
| **Koreksi salah hitung** (kesalahan sistem/aturan) | Bug, aturan salah diterapkan | `invoice_correction` alasan `LATE_FEE_ERROR`: baris `adjustment` negatif, `late_fee_assessments.status = reversed` ([KEU-BR-189](25-business-rules-dan-validasi.md)) | Bendahara |
| **Koreksi otomatis** | Pembayaran dengan `paid_at` ≤ tanggal bucket − 1 baru diverifikasi setelah bucket dibentuk | Sistem membalik bucket yang seharusnya tidak terbentuk atau lebih kecil (alasan `PAID_ON_TIME_VERIFIED_LATE`) dan menghitung ulang bucket itu | Otomatis (sistem), diaudit |

Pembebasan/koreksi tidak boleh membuat nominal tagihan denda < `paid_amount`; bila denda sudah dibayar, alokasinya dibalik lebih dulu dan dananya menjadi dana belum diterapkan ([KEU-BR-056](25-business-rules-dan-validasi.md)).

## 8. Interaksi dengan Fitur Lain

| Fitur | Interaksi |
|---|---|
| Cicilan | Per termin bila `apply_per_installment_term`; termin yang belum jatuh tempo tidak didenda |
| Sengketa | Denda ditunda selama sengketa aktif hanya bila `finance.dispute.suspend_late_fee = true` [K] |
| Deposit / dana belum diterapkan | Tidak mengurangi dasar denda sampai benar-benar dialokasikan |
| Pembayaran direversal | Pokok terutang naik untuk bucket berikutnya; bucket lalu tidak berubah |
| Cekal | Tagihan denda dihitung bila `include_late_fee` |
| Perpanjangan jatuh tempo | `first_date` dihitung dari `due_date` baru untuk bucket yang belum dibentuk |
| Mahasiswa nonaktif/lulus | Tidak ada pengecualian otomatis; kampus dapat membuat aturan khusus atau membebaskan [K] |

## 9. Hak Akses

| Aksi | Permission | Role default |
|---|---|---|
| Kelola & simulasi aturan, lihat denda terbentuk | `late_fee_rules.read/create/update` | FA (FO, FM, AU baca) |
| Ajukan pembebasan/koreksi | `finance_adjustments.create` | FA, FO |
| Setujui | `finance_adjustments.approve` | FM |
| Jalankan job manual (tenant) | `late_fee_rules.update` | FA |
| Mahasiswa melihat denda | `finance_self_service.read` | Milik sendiri |

## 10. Struktur Data dan Relasi

| Tabel | Rincian |
|---|---|
| `late_fee_rules` | [19 §3.10.1](19-rancangan-database-dan-relasi.md#3101-late_fee_rules--baru) |
| `late_fee_assessments` | [19 §3.10.2](19-rancangan-database-dan-relasi.md#3102-late_fee_assessments--baru) |
| `invoices` (`invoice_type = late_fee`, `parent_invoice_id`) | [19 §3.4.1](19-rancangan-database-dan-relasi.md#341-invoices--ubah) |
| `invoice_items` (`late_fee`, `late_fee_waiver`) | [19 §3.4.2](19-rancangan-database-dan-relasi.md#342-invoice_items--baru) |

## 11. API dan Event

| Method | URL | Permission | Keterangan |
|---|---|---|---|
| GET/POST | `/late-fee-rules` | `late_fee_rules.read` / `.create` | |
| GET/PUT | `/late-fee-rules/{rule}` | `.read` / `.update` | |
| POST | `/late-fee-rules/{rule}/simulate` | `late_fee_rules.read` | Input tagihan + tanggal → bucket yang akan terbentuk, tanpa menyimpan |
| GET | `/late-fee-assessments` | `late_fee_rules.read` | Filter tagihan, status, bucket, term |
| POST | `/late-fee-assessments/run` | `late_fee_rules.update` | Jalankan job untuk tenant (idempoten) |
| POST | `/late-fee-assessments/{assessment}/waive` | `finance_adjustments.create` | `late_fee_waiver` |
| POST | `/late-fee-assessments/{assessment}/reverse` | `finance_adjustments.create` | `LATE_FEE_ERROR` |

Event: `InvoiceIssued` (tagihan denda baru), `InvoiceStatusChanged`.

## 12. UI/UX

| Halaman | Rute | Inti |
|---|---|---|
| Aturan Denda | `/keuangan/master/aturan-denda` | Daftar aturan, form, simulator (pilih tagihan atau isi nominal & tanggal → tabel bucket seperti §4.4) |
| Denda Terbentuk | `/keuangan/tagihan/denda` | Tabel bucket (mahasiswa, tagihan dasar, bucket, dasar, nominal, dibebaskan, status), aksi Bebaskan/Koreksi, hasil eksekusi job terakhir |
| Detail tagihan dasar | Tab "Denda terkait" | Daftar bucket dan tagihan denda |
| Portal | Detail tagihan | Kotak "Denda keterlambatan" dengan penjelasan perhitungan per bucket |

## 13. Notifikasi

| Event | Penerima | Kanal |
|---|---|---|
| `finance.late_fee_assessed` (dikelompokkan per hari) | Mahasiswa | database, mail |
| `finance.late_fee_waived` | Mahasiswa | database, mail |
| Ringkasan job harian (jumlah dibentuk/ditunda/galat) | Administrator keuangan | database |

## 14. Laporan Terkait

[KEU-RPT-12](22-rancangan-laporan-dan-dashboard.md) denda (dibentuk, dibayar, dibebaskan, dibalik; per term, prodi, aturan).

## 15. Skenario Kegagalan

| Skenario | Penanganan |
|---|---|
| Job tidak berjalan beberapa hari | Saat berjalan lagi, bucket terlewat dibentuk dengan tanggal dan dasar masing-masing ([KEU-BR-192](25-business-rules-dan-validasi.md)) |
| Job berjalan dua kali / paralel | Unique `scope_key`; `withoutOverlapping` |
| Pembayaran tepat waktu baru diverifikasi setelah bucket | Pembentukan ditunda bila sudah diketahui pending; bila sudah terbentuk, dibalik otomatis (§7) |
| Aturan diubah di tengah periode | Bucket berikutnya memakai aturan baru; bucket lama tetap ([KEU-BR-193](25-business-rules-dan-validasi.md)) |
| Zona waktu tenant diubah | Berlaku untuk perhitungan berikutnya; tidak ada perhitungan ulang |
| Plafon tercapai | Bucket berikutnya tidak dibentuk |

## 16. Audit Trail

| Aksi | Dicatat |
|---|---|
| Ubah aturan | `audit_logs` (nilai lama/baru) |
| Setiap bucket | `late_fee_assessments` (dasar, hari terlambat, aturan, `run_id`) + baris tagihan + histori |
| Pembebasan/koreksi | `finance_adjustments` + `finance_status_histories` (`late_fee_assessment`) + `audit_logs` (`approved`) |
| Eksekusi job | Ringkasan per `run_id` di log terstruktur |

## 17. Acceptance Criteria dan Test Case

| Acceptance criteria | Test case |
|---|---|
| [KEU-AC-LTF-01](27-acceptance-criteria.md) Denda sesuai aturan (tenggang, dasar, plafon, per termin) | [KEU-TC-LTF-001](26-skenario-pengujian.md), [KEU-TC-LTF-004](26-skenario-pengujian.md), [KEU-TC-LTF-005](26-skenario-pengujian.md), [KEU-TC-LTF-010](26-skenario-pengujian.md) |
| [KEU-AC-LTF-02](27-acceptance-criteria.md) Idempoten dan mengejar bucket | [KEU-TC-LTF-003](26-skenario-pengujian.md), [KEU-TC-LTF-009](26-skenario-pengujian.md) |
| [KEU-AC-LTF-03](27-acceptance-criteria.md) Batas hari mengikuti zona waktu tenant | [KEU-TC-LTF-002](26-skenario-pengujian.md) |
| [KEU-AC-LTF-04](27-acceptance-criteria.md) Berhenti saat lunas; verifikasi terlambat dikoreksi | [KEU-TC-LTF-006](26-skenario-pengujian.md), [KEU-TC-LTF-008](26-skenario-pengujian.md) |
| [KEU-AC-LTF-05](27-acceptance-criteria.md) Pembebasan berizin | [KEU-TC-LTF-007](26-skenario-pengujian.md) |
