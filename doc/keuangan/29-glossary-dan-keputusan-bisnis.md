# 29 — Glosarium dan Keputusan Bisnis

> **Status:** rancangan. Disusun 2026-10-12.
> Kembali ke [README](README.md) · Skema acuan: [19](19-rancangan-database-dan-relasi.md)

Dokumen ini menjadi rujukan tunggal untuk:

- **istilah**: makna setiap istilah keuangan yang dipakai di seluruh dokumen KEU;
- **keputusan desain** (`KEU-KD-NN`): beserta alasan dan alternatif yang ditolak;
- **pertanyaan terbuka** (`KEU-Q-NN`): hal yang harus dikonfirmasi pemilik kebijakan kampus/produk sebelum atau selama implementasi;
- **asumsi kerja** (`KEU-A-NN`): anggapan yang dipakai rancangan selama belum ada konfirmasi.

## Daftar Isi

1. [Glosarium](#1-glosarium)
2. [Keputusan Desain (KEU-KD)](#2-keputusan-desain-keu-kd)
3. [Pertanyaan Terbuka (KEU-Q)](#3-pertanyaan-terbuka-keu-q)
4. [Asumsi (KEU-A)](#4-asumsi-keu-a)
5. [Pemetaan Istilah Indonesia–Kode](#5-pemetaan-istilah-indonesiakode)

---

## 1. Glosarium

| Istilah | Definisi dalam rancangan KEU | Entitas/kolom |
|---|---|---|
| **Alokasi pembayaran** | Penerapan sejumlah dana (dari pembayaran atau deposit) untuk mengurangi saldo terutang satu tagihan. Bersifat append-only dan bertanda; pembatalan dicatat sebagai alokasi negatif | `payment_allocations` |
| **Angkatan** | Tahun masuk mahasiswa | `students.admission_year` |
| **Bucket denda** | Satuan waktu pembentukan denda (sekali, harian, mingguan, bulanan). Satu bucket menghasilkan paling banyak satu denda per tagihan dasar/termin | `late_fee_assessments.bucket_key` |
| **Beasiswa** | Pengurang kewajiban yang diberikan karena program beasiswa (penuh/parsial). Berbeda dari penetapan UKT | `discount_schemes.scheme_type = scholarship`, `student_discounts` |
| **Cekal keuangan** | Pembatasan layanan akademik tertentu (KRS, ujian, dll.) karena tunggakan yang memenuhi kebijakan. Keputusannya dibuat KEU, penegakannya oleh modul akademik | `finance_holds` → `academic_blocks` |
| **Cicilan** | Pembagian saldo terutang satu tagihan ke beberapa termin dengan jatuh tempo berbeda | `installment_plans`, `installment_terms` |
| **Dana belum diterapkan** | Bagian pembayaran terverifikasi yang belum dialokasikan, belum dikonversi ke deposit, dan belum dikembalikan. Berasal dari kelebihan bayar atau pembayaran tanpa tagihan | `payments.unapplied_amount` |
| **Denda** | Kewajiban tambahan karena keterlambatan pembayaran setelah masa tenggang. Dicatat sebagai tagihan terpisah | `invoices.invoice_type = late_fee` |
| **Deposit** | Saldo titipan mahasiswa yang dapat dipakai untuk membayar tagihan berikutnya atau dikembalikan. Dicatat sebagai ledger | `deposit_accounts`, `deposit_transactions` |
| **Dispensasi / pengecualian cekal** | Izin sementara untuk menggunakan layanan meskipun cekal aktif. Berbatas waktu dan per layanan | `finance_hold_exemptions` |
| **Golongan UKT** | Klasifikasi kemampuan ekonomi (mis. Golongan I–VIII). Bukan nominal | `ukt_groups` |
| **Jatuh tempo** | Tanggal terakhir pembayaran tanpa dianggap terlambat (akhir hari, zona waktu tenant) | `invoices.due_date`, `installment_terms.due_date` |
| **Jalur masuk** | Jalur penerimaan (mis. SNBP, SNBT, Mandiri, Beasiswa) | `students.admission_path_code` (diusulkan) |
| **Kanal pembayaran** | Cara membayar yang ditawarkan (VA bank tertentu, QRIS, e-wallet, transfer manual, loket). Kanal online dihitung terhadap kuota paket | `payment_channels` |
| **Kelas / sistem kuliah** | Jenis program (reguler pagi, reguler sore/karyawan, internasional, dll.) | `students.study_system_code` (diusulkan) |
| **Kelebihan bayar** | Pembayaran terverifikasi yang melebihi saldo terutang tagihan sasaran | Sisa `payments.unapplied_amount` |
| **Komponen biaya** | Jenis biaya yang ditagihkan (UKT, SPP, per SKS, praktikum, registrasi, pengembangan, administrasi, denda) | `fee_components` |
| **Kuitansi** | Bukti penerimaan pembayaran yang diterbitkan sistem untuk pembayaran terverifikasi | `receipts` |
| **Nominal bruto** | Jumlah seluruh komponen biaya sebelum potongan | `invoices.gross_amount` |
| **Nominal pembayaran** | Uang yang diterima dalam satu penerimaan | `payments.amount` |
| **Nominal tagihan (neto)** | Total kewajiban satu tagihan setelah potongan dan penyesuaian | `invoices.amount` |
| **Outbox** | Tabel antrean event yang ditulis dalam transaksi DB yang sama dengan perubahan data, lalu dikirim oleh worker | `finance_outbox_events` |
| **Pembayaran terverifikasi** | Penerimaan yang sudah dipastikan valid: lewat callback provider yang tervalidasi + cek status, atau lewat verifikasi petugas berwenang | `payments.status = verified` |
| **Penetapan UKT** | Keputusan yang menempatkan seorang mahasiswa pada golongan UKT tertentu untuk rentang term | `ukt_determinations` |
| **Periode akademik / term** | Semester (ganjil/genap/pendek) dalam tahun akademik | `academic_terms` |
| **Periode keuangan** | Bulan pembukuan untuk penguncian transaksi | `finance_periods` |
| **Piutang (saldo piutang aktif)** | Σ saldo terutang tagihan berstatus `unpaid` atau `partial` | Turunan |
| **Piutang bersih mahasiswa** | Piutang dikurangi saldo deposit tersedia dan dana belum diterapkan milik mahasiswa yang sama; tidak pernah negatif. Hanya indikator, bukan saldo pembukuan | Turunan |
| **Posting date** | Tanggal pembukuan suatu efek finansial, dasar laporan periodik dan penutupan periode | `*.posting_date` |
| **Potongan** | Pengurang kewajiban non-beasiswa: potongan program (skema), voucher, atau potongan manual (koreksi berizin) | `invoice_items` (`discount`, `voucher`, `manual_discount`) |
| **Reversal / pembalikan** | Baris baru yang meniadakan efek baris lama tanpa menghapusnya | `payment_allocations.reversal_of_id`, `deposit_transactions.reversal_of_id` |
| **Rekonsiliasi** | Pencocokan transaksi sistem dengan laporan provider (settlement) atau mutasi bank | `reconciliation_batches/items` |
| **Saldo terutang** | Nominal tagihan dikurangi total alokasi | `invoices.outstanding_amount` |
| **Sengketa tagihan** | Keberatan atas kebenaran tagihan yang diajukan mahasiswa atau staf | `invoice_disputes` |
| **Settlement** | Laporan provider tentang dana yang sudah dicairkan ke rekening kampus, termasuk potongan biaya | Sumber `reconciliation_batches` |
| **Snapshot tarif** | Salinan nilai dan referensi tarif pada saat tagihan diterbitkan | `invoice_items.unit_amount`, `snapshot` |
| **SoD (pemisahan tugas)** | Prinsip bahwa pembuat dan penyetuju transaksi sensitif adalah orang berbeda | `finance.sod.enabled` |
| **Tagihan** | Dokumen kewajiban mahasiswa untuk satu periode/keperluan, terdiri atas beberapa baris | `invoices` |
| **Tagihan massal** | Penerbitan tagihan untuk banyak mahasiswa berdasarkan kriteria | `billing_runs` |
| **Tagihan susulan** | Tagihan tambahan pada term yang sama karena perubahan dasar penagihan (mis. tambah SKS setelah tagihan terbit) | `invoice_type = supplementary` |
| **Tagihan tambahan** | Tagihan di luar tagihan semester rutin (mis. biaya wisuda, cuti) | `invoice_type = additional` |
| **Tarif** | Nominal per komponen biaya untuk kombinasi kriteria tertentu dalam satu versi jadwal tarif | `tariff_schedules`, `tariff_rules` |
| **Termin** | Satu bagian jadwal cicilan dengan nominal dan jatuh tempo sendiri | `installment_terms` |
| **Transaksi online** | Satu upaya pembayaran lewat provider (VA, QRIS, dll.). Statusnya tidak sama dengan status pembayaran ataupun tagihan | `payment_transactions` |
| **UKT** | Uang Kuliah Tunggal: biaya kuliah per semester yang besarannya ditentukan oleh golongan | Komponen `category = ukt` |
| **Uang muka (cicilan)** | Termin pertama cicilan yang ditandai sebagai DP | `installment_terms.is_down_payment` |
| **Voucher** | Kode potongan yang dimasukkan pengguna, dengan kuota dan masa berlaku | `vouchers`, `voucher_redemptions` |
| **Waterfall alokasi** | Aturan deterministik untuk menurunkan alokasi tingkat komponen dari alokasi tingkat tagihan, berdasarkan `allocation_priority` | [KEU-KD-19](#2-keputusan-desain-keu-kd) |
| **Webhook / callback** | Pemberitahuan HTTP dari provider tentang perubahan status transaksi | `payment_webhook_events` |

---

## 2. Keputusan Desain (KEU-KD)

| ID | Keputusan | Alasan | Alternatif yang ditolak |
|---|---|---|---|
| **KEU-KD-01** | Memperluas tabel `invoices` dan `payments` yang ada (+ `invoice_items`, `payment_allocations`), tidak membuat `student_bills`/`student_bill_details` seperti `RANCANGAN-APLIKASI.md §7` | `invoices` sudah dipakai Dashboard, Report, statistik platform, frontend, dan dirujuk rancangan PMB, Semester Pendek, serta `academic-learning`. Mengganti nama memecah semua konsumen | Tabel baru `student_bills` dengan migrasi data: biaya tinggi tanpa nilai tambah |
| **KEU-KD-02** | Termin cicilan adalah **jadwal** (`installment_terms`) di atas satu tagihan. Piutang tetap dihitung dari tagihan | Mencegah kewajiban terhitung dua kali di laporan. Satu nomor tagihan tetap menjadi rujukan bagi mahasiswa. Denda dan cekal dapat memakai jatuh tempo termin | Memecah tagihan menjadi beberapa tagihan anak (usulan `pmb/11` §B.2): pembatalan cicilan menjadi rumit dan rawan dihitung ganda dengan tagihan induk. Perbedaan dengan PMB dicatat di [KEU-Q-11](#3-pertanyaan-terbuka-keu-q) |
| **KEU-KD-03** | Denda dicatat sebagai **tagihan terpisah** (`invoice_type = late_fee`, `parent_invoice_id`) yang berisi satu baris per bucket (`late_fee_assessments`) | Tagihan dasar tetap utuh (snapshot tidak berubah setelah dibayar sebagian). Denda dapat dikecualikan dari cekal, laporan, maupun beasiswa secara terpisah. Pembebasan denda menjadi baris `late_fee_waiver` yang jelas | (a) Denda sebagai baris di tagihan dasar: nominal tagihan berubah setelah ada pembayaran. (b) Denda sebagai "transaksi penyesuaian" di luar tagihan: denda tidak bisa dibayar/dialokasikan dengan mekanisme yang sama |
| **KEU-KD-04** | Penerimaan dana (`payments`) dipisahkan dari penerapan dana (`payment_allocations`) | Satu pembayaran dapat melunasi beberapa tagihan; kelebihan bayar dan deposit dapat ditelusuri; pembayaran tanpa referensi dapat diterima lebih dulu | `payments.invoice_id` tunggal (kondisi sekarang): tidak mendukung pembayaran gabungan, kelebihan bayar, maupun reversal sebagian |
| **KEU-KD-05** | Koreksi selalu melalui reversal atau `finance_adjustments`. Data finansial terverifikasi tidak di-`UPDATE`/`DELETE` | Jejak audit utuh dan laporan periode tertutup tidak berubah | Edit langsung dengan audit log: nilai historis laporan bisa berubah diam-diam |
| **KEU-KD-06** | Deposit = ledger append-only (`deposit_transactions`) + saldo cache (`deposit_accounts.balance`) yang dikunci baris | Saldo dapat ditelusuri ke setiap mutasi; pemakaian bersamaan dicegah dengan `FOR UPDATE` | Hanya kolom saldo: tidak dapat ditelusuri. Hanya ledger tanpa cache: pemeriksaan saldo mahal dan sulit dikunci |
| **KEU-KD-07** | Snapshot tarif dan atribut perilaku komponen disalin ke `invoice_items` saat tagihan terbit | Perubahan master tarif tidak berlaku surut | Join ke master saat menampilkan: tagihan lama ikut berubah |
| **KEU-KD-08** | Tarif dipilih berdasarkan **skor spesifisitas** kriteria. Dua aturan dengan skor sama yang cocok untuk mahasiswa yang sama ditolak saat publikasi (`TARIFF_AMBIGUOUS`) | Deterministik dan dapat dijelaskan ke auditor | Urutan baris (*first match*): rapuh dan bergantung urutan input |
| **KEU-KD-09** | KEU memiliki **keputusan** cekal keuangan (`finance_holds`, kebijakan, pengecualian). SIAKAD memiliki **penegakan** (`academic_blocks`, `AcademicBlockGuard`) sebagai proyeksi yang disinkronkan lewat outbox | Selaras dengan `academic-learning` (Finance = sumber data, SIAKAD = penegak). Kebijakan keuangan (ambang, komponen, tenggang) tetap di tangan bagian keuangan. Status sinkronisasi dapat dipantau | (a) Seluruh aturan di `academic_block_rules`: SIAKAD harus memahami detail tagihan/cicilan. (b) KRS memanggil KEU langsung tanpa proyeksi: setiap layanan akademik harus mengenal KEU, dan cekal non-keuangan memakai jalur yang berbeda |
| **KEU-KD-10** | Status transaksi provider (`payment_transactions.status`), status penerimaan (`payments.status`), dan status tagihan (`invoices.status`) adalah tiga status berbeda | Transaksi gagal/kedaluwarsa tidak mengubah tagihan; satu tagihan dapat punya banyak upaya bayar | Satu status gabungan di tagihan: tidak dapat membedakan "menunggu bayar di VA" dari "belum dibayar" |
| **KEU-KD-11** | "Terlambat" dan "disengketakan" bukan nilai `invoices.status`. Terlambat diturunkan dari `due_date`; sengketa memakai `has_open_dispute` | Tidak perlu job yang mengubah status harian; kombinasi (mis. sebagian dibayar + terlambat + disengketakan) dapat diwakili | Menambah `overdue`/`disputed` ke enum: status saling bertabrakan |
| **KEU-KD-12** | `invoices.status` dikonversi dari MySQL `enum` menjadi `string(20)` + PHP enum | Konvensi proyek terbaru ([academic-learning/07 §1.4](../../docs/academic-learning/07-database-erd.md)) | Menambah nilai pada enum MySQL: `ALTER TABLE` penuh setiap perubahan |
| **KEU-KD-13** | Waktu disimpan UTC; batas hari dihitung dalam `universities.timezone` | Aplikasi multi-tenant dengan `app.timezone = UTC`; jatuh tempo harus mengikuti jam lokal kampus | Memakai zona aplikasi: jatuh tempo bergeser 7 jam untuk WIB |
| **KEU-KD-14** | Lapisan gateway (`PaymentGatewayContract`, `payment_gateway_accounts`, `payment_channels`, `payment_webhook_events`) dipakai bersama oleh KEU dan PMB. Kuota kanal dihitung per tenant | Satu kontrak provider per kampus; kuota paket berlaku untuk modul KEU yang mencakup pembayaran PMB | Lapisan terpisah per modul (tabel `pmb_payment_*` dengan kredensial sendiri): kredensial ganda, kuota ambigu. Tabel domain PMB (`pmb_invoices`) tetap milik PMB |
| **KEU-KD-15** | KEU **bukan** buku besar. Integrasi akuntansi berupa ekspor jurnal ringkas per periode berdasarkan `fee_components.accounting_code` | Ruang lingkup KEU = administrasi keuangan mahasiswa (`SEVIMA §27.2`, `RANCANGAN-APLIKASI §21.4`) | Membangun GL di KEU: di luar lingkup dan menduplikasi Finance Cloud |
| **KEU-KD-16** | Penutupan periode keuangan per **bulan kalender** berdasarkan `posting_date` | Mengikuti siklus pelaporan keuangan kampus; term akademik tidak sejajar dengan bulan buku | Penutupan per term akademik: tidak cocok dengan siklus akuntansi bulanan [K] |
| **KEU-KD-17** | Pemisahan tugas (pembuat ≠ penyetuju) ditegakkan di service KEU, terlepas dari konfigurasi ApprovalWorkflow | ApprovalWorkflow saat ini tidak mencegah *self-approval* pada langkah bertipe `role`/`user` (temuan GAP-05) | Hanya mengandalkan konfigurasi workflow: celah kontrol |
| **KEU-KD-18** | Nomor dokumen per tenant per tahun memakai `finance_sequences` yang dikunci baris | Nomor manusiawi, unik, dan berurutan | `AUTO_INCREMENT` global: bocor lintas tenant. ULID sebagai nomor: tidak manusiawi |
| **KEU-KD-19** | Alokasi dicatat per tagihan (dan per termin). Alokasi per komponen **diturunkan** dengan *waterfall* berdasarkan `invoice_items.allocation_priority` | Satu sumber kebenaran untuk saldo tagihan; laporan per komponen dan dasar denda tetap dapat dihitung secara deterministik | Menyimpan alokasi per komponen: baris berlipat dan rawan selisih pembulatan |
| **KEU-KD-20** | Setiap potongan adalah baris negatif yang menunjuk baris komponen sasaran (`applies_to_item_id`) | Batas potongan per komponen dapat ditegakkan dan ditelusuri | Potongan di tingkat total tagihan: tidak dapat membatasi per komponen |
| **KEU-KD-21** | Pembayaran yang dikonfirmasi provider setelah transaksi kedaluwarsa/dibatalkan tetap dicatat sebagai penerimaan (`payments`). Dana dialokasikan bila tagihan masih terutang; bila tidak, menjadi dana belum diterapkan | Uang sudah benar-benar diterima kampus; menolaknya membuat selisih rekonsiliasi | Mengabaikan callback setelah kedaluwarsa: kehilangan dana tercatat |
| **KEU-KD-22** | Integrasi lintas modul memakai outbox transaksional (`finance_outbox_events`) + worker idempoten | Konsisten dengan prinsip "event setelah commit" (`academic-learning` BR-SIA-INT-03) dan dapat di-retry | Event langsung dari listener sinkron: hilang bila worker gagal |

---

## 3. Pertanyaan Terbuka (KEU-Q)

Prioritas: **P1** = harus dijawab sebelum fase terkait dimulai; **P2** = sebelum UAT; **P3** = dapat menyusul. Kolom *Default rancangan* adalah nilai yang dipakai dokumen selama belum ada jawaban.

| ID | Pertanyaan | Prioritas | Default rancangan | Dokumen terdampak |
|---|---|---|---|---|
| KEU-Q-01 | Paket di repository (`trial/starter/professional/enterprise`) berbeda dengan matriks referensi (Growth/Business/Professional/Premium/Enterprise). Bagaimana pemetaannya, dan berapa kuota kanal tiap paket di kode? | P1 | Entitlement `finance.payment_channels` dengan nilai per paket dikonfigurasi Super Admin; default referensi Growth 3, Business 3, Professional 4, Premium 5, Enterprise 6 | [08](08-rancangan-pembayaran-online-dan-gateway.md), [04](04-master-data-dan-konfigurasi.md) |
| KEU-Q-02 | Apakah loket dan transfer manual dihitung sebagai "kanal pembayaran" dalam kuota paket? | P1 | Tidak dihitung; kuota hanya untuk kanal online | [08](08-rancangan-pembayaran-online-dan-gateway.md) |
| KEU-Q-03 | Provider gateway mana yang dikontrak, dan kanal apa saja yang benar-benar didukungnya? | P1 | Adapter generik; kanal diambil dari kemampuan adapter | [08](08-rancangan-pembayaran-online-dan-gateway.md) |
| KEU-Q-04 | Urutan perhitungan tarif → beasiswa → potongan → voucher → potongan manual. Apakah persentase dihitung dari nominal bruto komponen atau dari sisa setelah potongan sebelumnya? | P1 | Urutan tersebut; persentase dari **sisa** komponen (`remaining_component`) | [11](11-rancangan-beasiswa-dan-potongan.md) |
| KEU-Q-05 | Apakah beasiswa boleh digabung dengan voucher atau potongan lain? | P1 | Boleh, kecuali skema berada dalam `stacking_group` yang sama; voucher tidak digabung dengan voucher lain | [11](11-rancangan-beasiswa-dan-potongan.md), [12](12-rancangan-voucher-tagihan.md) |
| KEU-Q-06 | Kebijakan denda: nominal/persen, frekuensi, masa tenggang, plafon, dan komponen yang dikenai | P1 | Fitur denda nonaktif (`finance.late_fee.enabled = false`) sampai kebijakan ditetapkan | [13](13-rancangan-denda.md) |
| KEU-Q-07 | Layanan apa saja yang dicekal karena tunggakan, berapa ambang minimum, dan berapa hari tenggang? | P1 | Tidak ada kebijakan aktif; contoh: `krs` + `exam`, ambang Rp0, tenggang 0 hari | [17](17-rancangan-cekal-keuangan.md) |
| KEU-Q-08 | Siapa yang berwenang menyetujui tarif, pembatalan tagihan, koreksi, refund, dan pembebasan denda? Apakah ada ambang nominal untuk persetujuan bertingkat? | P1 | `finance_manager` untuk semua; tingkat kedua (`vice_rector`) untuk refund > Rp10.000.000 (*contoh*) | [03](03-role-dan-hak-akses.md), [24](24-keamanan-audit-dan-kontrol-internal.md) |
| KEU-Q-09 | Kelebihan bayar default: dikonversi otomatis ke deposit atau menunggu keputusan staf? | P2 | Menunggu keputusan staf (`finance.overpayment.default_action = hold`) | [07](07-rancangan-pembayaran.md), [14](14-rancangan-deposit-mahasiswa.md) |
| KEU-Q-10 | Syarat cicilan: maksimal termin, minimal uang muka, siapa yang boleh mengajukan, apakah ada biaya administrasi cicilan? | P1 | Maks 4 termin, DP minimal 25%, pengajuan oleh mahasiswa atau staf, tanpa biaya | [10](10-rancangan-cicilan-tagihan.md) |
| KEU-Q-11 | PMB merancang termin cicilan biaya awal sebagai invoice terpisah. Apakah PMB menyesuaikan diri dengan model termin KEU? | P1 | PMB memposting satu tagihan `admission` + rencana cicilan KEU | [10](10-rancangan-cicilan-tagihan.md), [20](20-rancangan-api-dan-integrasi.md) |
| KEU-Q-12 | Dasar tagihan per SKS: SKS yang diajukan, disetujui dosen wali, atau terkunci setelah masa ubah KRS? | P1 | SKS dari `krs_items` berstatus `enrolled` pada saat penagihan; perubahan sesudahnya menghasilkan tagihan susulan atau koreksi | [06](06-rancangan-tagihan-mahasiswa.md) |
| KEU-Q-13 | Apakah kampus memakai UKT (PTN/PTN-BH) atau SPP tetap (PTS)? Apakah perlu keduanya? | P1 | Keduanya didukung lewat kategori komponen; UKT aktif hanya bila ada `ukt_groups` | [15](15-rancangan-ukt-dan-validasi-penerima.md) |
| KEU-Q-14 | Apakah pembayaran parsial diizinkan untuk tagihan tanpa cicilan? Berapa minimum per pembayaran? | P2 | Diizinkan; minimum Rp10.000 untuk kanal online (*contoh*), mengikuti batas provider | [07](07-rancangan-pembayaran.md) |
| KEU-Q-15 | Apakah nomor kuitansi wajib tanpa celah (*gapless*)? | P2 | Tidak wajib; nomor tidak pernah dipakai ulang | [09](09-rancangan-pembayaran-tunai-dan-manual.md), [19](19-rancangan-database-dan-relasi.md) |
| KEU-Q-16 | Siklus penutupan periode: bulanan, dan siapa yang dapat membuka kembali? | P2 | Bulanan; pembukaan kembali oleh `finance_manager` dengan persetujuan `vice_rector` | [18](18-rancangan-rekonsiliasi-dan-penyesuaian.md) |
| KEU-Q-17 | Berapa lama retensi data keuangan dan audit? | P2 | 10 tahun | [24](24-keamanan-audit-dan-kontrol-internal.md) |
| KEU-Q-18 | Apakah ada sistem akuntansi yang harus diintegrasikan (format/standar akun)? | P3 | Ekspor CSV/XLSX jurnal ringkas | [20](20-rancangan-api-dan-integrasi.md) |
| KEU-Q-19 | Apakah dosen wali boleh melihat nominal tunggakan mahasiswa bimbingannya? | P2 | Tidak; hanya status cekal dan alasan umum | [03](03-role-dan-hak-akses.md), [17](17-rancangan-cekal-keuangan.md) |
| KEU-Q-20 | Apakah folder dokumen dipindahkan ke `docs/keuangan/` agar seragam dengan dokumen lain? | P3 | Tetap di `doc/keuangan/` sesuai permintaan | [README](README.md) |
| KEU-Q-21 | Apakah mahasiswa boleh melakukan top-up deposit secara mandiri? | P2 | Ya, lewat kanal yang `allow_deposit_topup = true`; nonaktif secara default | [14](14-rancangan-deposit-mahasiswa.md) |
| KEU-Q-22 | Apakah pencabutan beasiswa dapat berlaku untuk tagihan yang sudah terbit pada term berjalan? | P1 | Tidak otomatis; berlaku mulai term berikutnya. Koreksi term berjalan memakai `finance_adjustments` dengan persetujuan | [11](11-rancangan-beasiswa-dan-potongan.md) |
| KEU-Q-23 | Apakah voucher PMB dikelola di KEU (`vouchers`, `context = admission`) atau tetap di `pmb_vouchers`? | P2 | Dikelola di KEU, dipakai PMB lewat layanan voucher | [12](12-rancangan-voucher-tagihan.md) |
| KEU-Q-24 | Apakah sengketa tagihan menangguhkan denda dan/atau cekal? | P2 | Tidak (`finance.dispute.suspend_late_fee = false`, `finance.dispute.suspend_hold = false`) | [06](06-rancangan-tagihan-mahasiswa.md) |
| KEU-Q-25 | Apakah biaya administrasi kanal ditanggung mahasiswa atau kampus, dan apakah berbeda per kanal? | P1 | Dikonfigurasi per kanal (`fee_bearer`); default kampus | [08](08-rancangan-pembayaran-online-dan-gateway.md) |
| KEU-Q-26 | Apakah *write-off* (penghapusan piutang tak tertagih) termasuk lingkup KEU? | P3 | Tipe `write_off` disiapkan, tetapi nonaktif; keputusan akuntansi | [18](18-rancangan-rekonsiliasi-dan-penyesuaian.md) |
| KEU-Q-27 | Kapan `posting_date` untuk pembayaran gateway: tanggal bayar menurut provider atau tanggal settlement? | P2 | Tanggal bayar menurut provider (`finance.posting_date_basis = paid_at`) | [07](07-rancangan-pembayaran.md), [18](18-rancangan-rekonsiliasi-dan-penyesuaian.md) |

---

## 4. Asumsi (KEU-A)

| ID | Asumsi | Dasar | Dampak jika salah |
|---|---|---|---|
| KEU-A-01 | Mata uang tunggal **IDR**, tanpa konversi kurs | `universities.currency` default `IDR`; tidak ada kebutuhan multi-mata uang di referensi | Perlu kolom kurs dan nilai ekuivalen |
| KEU-A-02 | Nominal tagihan dan pembayaran berupa rupiah penuh (sen = 00) | Praktik pembayaran kampus di Indonesia | Pembulatan perlu ditinjau ulang |
| KEU-A-03 | Satu provider gateway aktif per tenant pada satu waktu (beberapa kanal) | Kontrak umum kampus; `pmb/02` A-05 | Perlu pemilihan provider per kanal (sudah didukung `payment_channels.payment_gateway_account_id`) |
| KEU-A-04 | Kolom `students.admission_path_code` dan `students.study_system_code` akan tersedia, ditambahkan oleh Akademik/PMB | Diusulkan `pmb/10`; dibutuhkan referensi "tarif per jalur dan kelas" | Kriteria tarif jalur/kelas tidak dapat dipakai sampai kolom ada; tarif tetap berjalan dengan kriteria lain |
| KEU-A-05 | Mahasiswa yang ditagih sudah ada di `students`. Calon mahasiswa ditangani PMB sampai konversi | Modul PMB terpisah | Tagihan pendaftar perlu `student_id` nullable — ditolak rancangan ini |
| KEU-A-06 | Data SKS diambil dari `krs_items` + `courses.credits` | Struktur Academic saat ini | Bila KRS berpindah modul, adaptor perlu disesuaikan |
| KEU-A-07 | Zona waktu tenant default `Asia/Jakarta` (WIB) | `universities.timezone` | — (sudah dikonfigurasi per tenant) |
| KEU-A-08 | Kanal notifikasi yang tersedia hanya `database` dan `mail` | `NotificationChannel::isImplemented()` | WhatsApp/SMS memerlukan implementasi kanal di modul Notification |
| KEU-A-09 | Pembayaran kartu kredit tidak diproses langsung oleh Civitas One; bila ada, sepenuhnya melalui halaman provider | Menghindari ruang lingkup PCI DSS | Bila diperlukan, wajib memakai tokenisasi/hosted page provider |
| KEU-A-10 | Scope fakultas/prodi akan ditegakkan memakai `user_roles.scope_type/scope_id` sesuai rancangan `AcademicScope` | `academic-learning/11` | Dekan/Kaprodi sementara melihat seluruh tenant atau tidak diberi akses ([03](03-role-dan-hak-akses.md)) |
| KEU-A-11 | Event Akademik (`KrsSubmissionApproved`, dll.) belum ada dan akan ditambahkan oleh tim Akademik | `academic-learning/06` | KEU memakai penagihan berbasis jadwal/manual sampai event tersedia |
| KEU-A-12 | `academic_blocks` (SIAKAD) mungkin belum tersedia saat KEU fase cekal dimulai | `academic-learning` Tahap 2a | Disediakan jalur sementara `FinanceClearanceGuard` di KRS/Ujian ([17 §9](17-rancangan-cekal-keuangan.md)) |
| KEU-A-13 | Server produksi menjalankan `schedule:run` dan worker antrean | Diperlukan job denda, kedaluwarsa, polling, outbox | Bila tidak, mekanisme *lazy* (cek saat diakses) menjadi pengaman, tetapi notifikasi dan denda tertunda |
| KEU-A-14 | Mahasiswa memiliki akun `users` yang tertaut (`students.user_id`) untuk layanan mandiri | Portal mahasiswa yang ada | Mahasiswa tanpa akun hanya dapat dilayani lewat loket |
| KEU-A-15 | Biaya pendaftaran PMB ditagih dan dikelola di modul PMB; KEU hanya menerima posting biaya awal setelah konversi | `pmb/02` KD-03 | Bila PMB memakai KEU langsung, perlu `student_id` nullable atau entitas pendaftar |

---

## 5. Pemetaan Istilah Indonesia–Kode

| Istilah UI (Bahasa Indonesia) | Nilai kode |
|---|---|
| Draf | `draft` |
| Belum Dibayar | `unpaid` |
| Dibayar Sebagian | `partial` |
| Lunas | `paid` |
| Dibatalkan | `cancelled` |
| Terlambat (label turunan) | `is_overdue = true` |
| Disengketakan (label turunan) | `has_open_dispute = true` |
| Menunggu Verifikasi | `payments.pending_verification` |
| Terverifikasi | `payments.verified` |
| Ditolak | `rejected` |
| Dibalik (reversal) | `payments.reversed` |
| Menunggu Pembayaran (transaksi online) | `payment_transactions.pending` |
| Berhasil (transaksi online) | `payment_transactions.paid` |
| Gagal | `failed` |
| Kedaluwarsa | `expired` |
| Menunggu Persetujuan | `pending_approval` |
| Aktif | `active` |
| Dicabut | `revoked` |
| Digantikan | `superseded` |
| Tidak Dicekal / Dicekal / Dalam Pengecualian / Menunggu Sinkronisasi / Gagal Sinkronisasi | Turunan `finance_holds` ([README §5.4](README.md#54-kanon-status)) |
| Tagihan Semester / Tambahan / Susulan / Semester Pendek / PMB / Denda | `semester` / `additional` / `supplementary` / `short_term` / `admission` / `late_fee` |
