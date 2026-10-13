# 21 — Rancangan UI/UX dan Struktur Menu

> **Status:** rancangan. Disusun 2026-10-13. Halaman keuangan yang **sudah ada** hanya `InvoicesPage`, `InvoiceDetailPage`, `PaymentsPage` (read-only) dan `PortalInvoicesPage` (data statis, belum terdaftar di `ROUTES.portal`).
> Kembali ke [README](README.md) · Hak akses: [03](03-role-dan-hak-akses.md) · API: [20](20-rancangan-api-dan-integrasi.md) · Istilah UI: [29 §5](29-glossary-dan-keputusan-bisnis.md#5-pemetaan-istilah-indonesiakode)

Dokumen ini mengusulkan struktur menu dan spesifikasi halaman untuk staf dan mahasiswa. Struktur menu adalah **usulan** yang mengikuti pola frontend yang ada dan dapat disesuaikan setelah uji pengguna.

## Daftar Isi

1. [Kondisi Frontend dan Prinsip](#1-kondisi-frontend-dan-prinsip)
2. [Pola Halaman Baku](#2-pola-halaman-baku)
3. [Struktur Menu Staf](#3-struktur-menu-staf)
4. [Spesifikasi Halaman Staf](#4-spesifikasi-halaman-staf)
5. [Struktur Menu dan Halaman Portal Mahasiswa](#5-struktur-menu-dan-halaman-portal-mahasiswa)
6. [Tampilan bagi Peran Akademik dan Publik](#6-tampilan-bagi-peran-akademik-dan-publik)
7. [Label Status dan Istilah](#7-label-status-dan-istilah)
8. [Responsif, Aksesibilitas, dan Bahasa](#8-responsif-aksesibilitas-dan-bahasa)

---

## 1. Kondisi Frontend dan Prinsip

| Aspek | Kondisi (`frontend/`) | Pemakaian di KEU |
|---|---|---|
| Stack | React 19, Vite, Tailwind 3, `react-router-dom` 7, `react-hook-form` + `zod`, `zustand`, `recharts`, `lucide-react` | Sama |
| Komponen | `components/ui`: `DataTable`, `ListPagination`, `PageHeader`, `StatCard`, `Badge`, `Modal`, `Dropdown`, `EmptyState`, `Skeleton`, `Alert`, `Tooltip`, `Input`, `Select`, `Textarea`, `Checkbox`, `Button` | Dipakai ulang; komponen baru: `MoneyText`, `MoneyInput`, `StatusBadge`, `Timeline`, `Drawer`, `FileDropzone`, `ConfirmDialog` dengan ringkasan dampak |
| Data | Hook kustom `usePaginatedList` / `useFetch` | Sama |
| Menu | `constants/nav.ts` dua tingkat, `permission` per item | Grup "Keuangan" (§3). Usulan: `NavItem.permission` menerima `string \| string[]` (OR), sejalan dengan middleware backend |
| Bahasa | Bahasa Indonesia langsung di kode (tanpa i18n) | Label dipusatkan di `constants/finance.ts` (§8) |
| Format uang | `formatCurrencyIDR` (`utils/formatters`) | Uang dari API berupa string desimal; diformat tanpa konversi ke float untuk perhitungan |

Prinsip:

1. **Backend adalah penentu.** Tombol disembunyikan bila pengguna tidak berizin, tetapi API tetap menolak ([KEU-BR-301](25-business-rules-dan-validasi.md)); UI tidak menghitung nominal yang mengikat.
2. **Angka selalu dapat diklik** ke daftar sumbernya ([16 §8](16-rancangan-monitoring-piutang.md#8-ketertelusuran-drill-down)).
3. **Tindakan kritis selalu dikonfirmasi** dengan ringkasan dampak (nominal sebelum/sesudah, entitas terdampak, persetujuan yang dibutuhkan).
4. **Tidak ada status palsu.** Setelah kembali dari halaman provider, UI menampilkan "Menunggu konfirmasi pembayaran", bukan "Lunas" ([KEU-BR-091](25-business-rules-dan-validasi.md)).
5. Mengganti halaman statis (`PortalInvoicesPage`, bagian keuangan `PortalScholarshipPage`) dengan data nyata.

## 2. Pola Halaman Baku

Setiap halaman di §4 dan §5 mengikuti pola ini kecuali dinyatakan lain.

| Aspek | Pola |
|---|---|
| **Kepala halaman** | `PageHeader` (judul, *breadcrumb*, tombol aksi utama sesuai izin) |
| **Filter** | Baris filter di atas tabel; filter aktif tampil sebagai *chip*; tersimpan di URL query (dapat dibagikan) dan `localStorage` per pengguna |
| **Tabel** | `DataTable`; kolom uang rata kanan dengan `MoneyText`; status dengan `StatusBadge`; kolom aksi di ujung (menu kebab) |
| **Sorting** | Klik header kolom yang diizinkan API (`sort=-kolom`); default dinyatakan per halaman |
| **Paginasi** | `ListPagination`, 25 baris default (pilihan 10/25/50/100); jumlah total tampil |
| **Detail** | Halaman detail untuk entitas utama (tagihan, pembayaran); `Drawer` kanan untuk entitas pendukung (bukti, sesi, item rekonsiliasi) |
| **Form** | `react-hook-form` + `zod`; validasi klien hanya untuk kenyamanan, pesan galat server (`errors.*`) dipetakan ke field; tombol simpan terkunci saat mengirim; `Idempotency-Key` dibuat sekali per pembukaan form |
| **Loading** | `Skeleton` sesuai bentuk konten; tombol menampilkan *spinner* |
| **Empty state** | `EmptyState` dengan kalimat penjelas dan aksi berikut (mis. "Belum ada tagihan untuk term ini. Buat tagihan massal") |
| **Error** | `Alert` dengan pesan dari `message`; kode khusus diterjemahkan (mis. `STALE_VERSION` → "Data telah diubah pengguna lain. Muat ulang"); tombol "Coba lagi" |
| **Konfirmasi kritis** | `ConfirmDialog`: judul aksi, ringkasan dampak, kolom alasan (bila wajib), centang "Saya memahami" untuk aksi tak dapat dibatalkan |
| **Hak akses tombol** | Tiap tombol memeriksa permission spesifik (mis. "Setujui" → `*.approve`) dan, untuk SoD, disembunyikan bila pengguna adalah pembuat (API tetap menegakkan) |
| **Responsif** | Desktop ≥ 1024 px tabel penuh; tablet 768–1023 px kolom sekunder disembunyikan, detail sebagai halaman penuh; ponsel < 768 px tabel menjadi daftar kartu, filter dalam *bottom sheet*, aksi utama sebagai tombol melayang |

## 3. Struktur Menu Staf

Sidebar memiliki satu grup **Keuangan** (ikon `Wallet`, sudah ada) dengan anak berikut. Setiap anak membuka halaman yang memiliki **tab sekunder** untuk sub-halaman, sehingga struktur dua tingkat `nav.ts` tetap berlaku. Item tampil bila pengguna memiliki **salah satu** permission di kolom Izin.

| Grup (anak menu) | Rute | Tab / sub-halaman | Izin tampil |
|---|---|---|---|
| **A. Dashboard Keuangan** | `/keuangan/dashboard` | Ringkasan, Piutang, Pembayaran hari ini, Transaksi pending, Tagihan jatuh tempo (widget) | `finance_dashboard.read` |
| **B. Master Keuangan** | `/keuangan/master` | Komponen Biaya, Tarif Uang Kuliah, Aturan Denda, Golongan UKT, Kanal Pembayaran, Rekening Penerima, Periode Keuangan, Pengaturan | `fee_components.read`, `tariffs.read`, `late_fee_rules.read`, `ukt_groups.read`, `payment_channels.read`, `finance_periods.read`, `finance_settings.read` |
| **C. Tagihan** | `/keuangan/tagihan` (Ada) | Daftar Tagihan, Buat Tagihan, Tagihan Massal, Cicilan, Sengketa, Denda | `invoices.read`, `billing_runs.read`, `installment_plans.read`, `invoice_disputes.read` |
| **D. Pembayaran** | `/keuangan/pembayaran` (Ada) | Daftar Pembayaran, Transaksi Online, Loket, Sesi Kas, Verifikasi Bukti Bayar, Kuitansi, Pengembalian Dana, Koreksi & Reversal | `payments.read`, `receipts.read`, `cashier_sessions.read`, `refunds.read`, `finance_adjustments.read` |
| **E. Beasiswa & Potongan** | `/keuangan/beasiswa` (Ada, diperluas) | Program Beasiswa (modul Scholarship), Skema Potongan, Penerima, Evaluasi, Voucher | `scholarships.read`, `discount_schemes.read`, `student_discounts.read`, `vouchers.read` |
| **F. Deposit** | `/keuangan/deposit` | Saldo Mahasiswa, Mutasi (detail) | `deposits.read` |
| **G. UKT** | `/keuangan/ukt` | Penetapan Mahasiswa, Validasi Penerima, Keberatan, Golongan (tautan ke B) | `ukt_determinations.read`, `ukt_appeals.read` |
| **H. Monitoring** | `/keuangan/monitoring` | Piutang, Umur Piutang, Mahasiswa Belum Lunas, Status Cekal, Kebijakan Cekal, Rekonsiliasi | `finance_reports.read`, `finance_holds.read`, `finance_hold_policies.read`, `reconciliations.read` |
| **I. Laporan** | `/keuangan/laporan` | Katalog 18 laporan, Ekspor Saya, Ekspor Akuntansi | `finance_reports.read` |
| **J. Persetujuan Keuangan** | `/keuangan/persetujuan` | Kotak masuk penyetuju lintas entitas | Salah satu `*.approve` keuangan |
| **K. Integrasi** | `/keuangan/integrasi` | Gateway & Webhook, Outbox, Sinkronisasi Cekal | `finance_integrations.read`, `payment_gateway_accounts.read` |

Perubahan terhadap menu lama: anak "Tagihan" dan "Pembayaran" tetap (rute sama), "Beasiswa" diperluas menjadi grup E. Menu grup lain milik peran non-keuangan tidak berubah.

## 4. Spesifikasi Halaman Staf

Format setiap kartu: **Tujuan · Komponen · Filter · Kolom (sort default) · Detail/Form · Empty state · Konfirmasi kritis · Tombol (izin)**. Pola loading, error, paginasi, dan responsif mengikuti §2.

### 4.1 A — Dashboard Keuangan (`/keuangan/dashboard`)

| Aspek | Isi |
|---|---|
| Tujuan | Gambaran harian bagi staf dan pimpinan |
| Komponen | Pemilih tanggal acuan; `StatCard`: Piutang, Jatuh tempo, Pembayaran terverifikasi hari ini, Pembayaran pending, Dana belum diterapkan, Deposit tersedia, Cekal aktif; grafik tren 12 bulan (piutang & pembayaran, dari snapshot); umur piutang bertumpuk; 10 prodi dengan piutang terbesar; widget "Tagihan jatuh tempo 7 hari ke depan"; widget "Pekerjaan saya" (antrean verifikasi, persetujuan, sengketa, item rekonsiliasi terbuka) |
| Filter | Term, fakultas/prodi (sesuai scope), jenis tagihan |
| Empty state | "Belum ada tagihan terbit untuk filter ini" |
| Tombol | Tidak ada aksi tulis; setiap kartu adalah tautan *drill-down* |
| Responsif | Kartu 2 kolom di ponsel; grafik dapat digeser |

### 4.2 B — Master Keuangan

**B1 Komponen Biaya** — rincian di [04 §10](04-master-data-dan-konfigurasi.md#10-uiux).

| Aspek | Isi |
|---|---|
| Kolom (sort `display_order`) | Kode, Nama, Kategori, Basis, Prioritas alokasi, ikon Dapat dipotong/dicicil/dasar denda/cekal, Kode akun, Status |
| Form | Kode (huruf besar otomatis), nama, kategori, basis, prioritas (1–999), atribut perilaku (sakelar + penjelasan efek), kode akun; tab "Mata kuliah" untuk `per_course` |
| Konfirmasi | Nonaktifkan komponen yang dipakai jadwal tarif aktif (tampilkan jumlah aturan terdampak) |
| Tombol | Tambah/Ubah/Nonaktifkan (`fee_components.create/update`), Hapus (`.delete`, hanya bila belum dirujuk) |

**B2 Tarif Uang Kuliah** — [05 §13](05-rancangan-tarif-uang-kuliah.md#13-uiux).

| Aspek | Isi |
|---|---|
| Daftar | Kolom: Kode, Versi, Berlaku dari–sampai, Status, Jumlah aturan, Diajukan/Disetujui oleh; filter status, term |
| Detail | Tab Aturan (tabel + matriks prodi × angkatan per komponen, sel dapat disunting pada draf), Validasi (konflik memblokir berwarna merah, peringatan kuning), Pratinjau (pilih mahasiswa & term → resolusi dengan penjelasan skor), Riwayat |
| Konfirmasi | Publikasi: ringkasan versi lama yang akan dibatasi dan term berlaku; Tolak: alasan wajib |
| Tombol | Buat/Impor/Ajukan/Revisi (`tariffs.create/update`), Setujui/Tolak (`.approve/.reject`, disembunyikan bagi pengaju), Publikasikan (`.publish`) |
| Empty state | "Belum ada jadwal tarif. Buat jadwal pertama atau impor dari berkas" |

**B3 Aturan Denda** — [13 §12](13-rancangan-denda.md#12-uiux). Simulator menampilkan tabel bucket seperti [13 §4.4](13-rancangan-denda.md#44-contoh). Banner bila `finance.late_fee.enabled = false`: "Denda nonaktif sampai kebijakan ditetapkan".

**B4 Golongan UKT** — [15 §12](15-rancangan-ukt-dan-validasi-penerima.md#12-uiux). Kolom: kode, nama, level, kriteria, jumlah mahasiswa aktif.

**B5 Kanal Pembayaran** — [08 §17](08-rancangan-pembayaran-online-dan-gateway.md#17-uiux).

| Aspek | Isi |
|---|---|
| Komponen | Indikator kuota "3 dari 4 kanal online aktif"; kartu kanal (nama, jenis, provider, penanggung biaya, masa berlaku, kesehatan, status) |
| Konfirmasi | Aktivasi yang melampaui kuota diblokir dengan pesan `CHANNEL_LIMIT_EXCEEDED`; nonaktivasi kanal dengan transaksi `pending` menampilkan jumlah transaksi berjalan |
| Tombol | Tambah/Ubah/Aktif/Nonaktif (`payment_channels.create/update`) |

**B6 Rekening Penerima** — [04 §10](04-master-data-dan-konfigurasi.md#10-uiux). Nomor termasking; "Lihat nomor lengkap" dengan konfirmasi "Tindakan ini dicatat".

**B7 Periode Keuangan** — wizard penutupan [18 §8](18-rancangan-rekonsiliasi-dan-penyesuaian.md#8-penutupan-periode-dan-ekspor-akuntansi): langkah Pemeriksaan (daftar pemblokir dengan tautan) → Ringkasan → Konfirmasi (centang). Tombol Tutup (`finance_periods.finalize`), Ajukan Buka Kembali (`.update`).

**B8 Pengaturan Keuangan** — form berkelompok ([04 §5](04-master-data-dan-konfigurasi.md#5-pengaturan-tenant-finance)); lencana [K] "Perlu konfirmasi kebijakan"; kunci sensitif terkunci untuk non-bendahara; setiap simpan meminta alasan bila kunci sensitif.

### 4.3 C — Tagihan

**C1 Daftar Tagihan** (`/keuangan/tagihan`, Ada, diperluas)

| Aspek | Isi |
|---|---|
| Filter | Term, jenis, status (multi), terlambat, disengketakan, bercicilan, fakultas/prodi, angkatan, jatuh tempo (rentang), pencarian nomor/NIM/nama |
| Kolom (sort `-issued_at`) | Nomor, NIM, Nama, Prodi, Periode, Jenis, Nominal, Dibayar, Sisa, Jatuh tempo, Status + lencana |
| Tombol | Buat Tagihan (`invoices.create`), Tagihan Massal (`billing_runs.create`), Ekspor (`invoices.export`) |
| Empty state | "Tidak ada tagihan sesuai filter" + tombol hapus filter |

**C2 Buat Tagihan** (`/keuangan/tagihan/baru`)

| Aspek | Isi |
|---|---|
| Form | Langkah 1 mahasiswa & term & jenis → langkah 2 pratinjau resolusi tarif (komponen, aturan, nominal; galat tarif ditampilkan per komponen) + baris manual (komponen `manual`) → langkah 3 jatuh tempo, deskripsi → Simpan draf / Terbitkan |
| Validasi | Pesan `TARIFF_NOT_FOUND`, `ALREADY_BILLED` ditampilkan di langkah terkait |
| Konfirmasi | Terbitkan: "Tagihan Rp… akan terlihat oleh mahasiswa dan dihitung sebagai piutang" |

**C3 Tagihan Massal** (`/keuangan/tagihan/massal`)

| Aspek | Isi |
|---|---|
| Komponen | Wizard kriteria → komponen & jatuh tempo & mode (draf/terbit) → pratinjau (ringkasan jumlah & total, distribusi alasan gagal, tabel item dengan filter hasil) → eksekusi dengan bilah progres → hasil |
| Konfirmasi | Eksekusi: jumlah mahasiswa, total nominal, mode; peringatan bila pratinjau > 24 jam |
| Tombol | Pratinjau (`billing_runs.create`), Eksekusi (`billing_runs.finalize`), Terbitkan draf (`invoices.create`), Salin ke term berikut |

**C4 Detail Tagihan** (`/keuangan/tagihan/:id`, Ada, diperluas)

| Aspek | Isi |
|---|---|
| Komponen | Kartu ringkasan nominal (bruto, potongan, penyesuaian, neto, dibayar, sisa), status & lencana; tabel baris dengan potongan menjorok di bawah komponen sasaran; tab Pembayaran & Alokasi, Cicilan, Denda Terkait, Sengketa, Voucher, **Riwayat** (linimasa [06 §10](06-rancangan-tagihan-mahasiswa.md#10-riwayat-tagihan)) |
| Tombol | Terbitkan/Hitung ulang (draf), Koreksi, Perpanjang Jatuh Tempo, Batalkan (`finance_adjustments.create`), Terapkan Voucher (`invoices.update`), Susun Cicilan (`installment_plans.create`), Cetak |
| Konfirmasi | Batalkan: menampilkan syarat (alokasi aktif harus dibalik, transaksi online aktif) dan efek berantai |

**C5 Cicilan** (`/keuangan/tagihan/cicilan`) — [10 §13](10-rancangan-cicilan-tagihan.md#13-uiux). Penyusun jadwal menampilkan indikator "Σ termin = nominal dicicil" (hijau/merah) dan baris bagian non-cicilan.

**C6 Sengketa** (`/keuangan/tagihan/sengketa`) — antrean dengan umur, kategori, status; drawer detail dengan tombol Tinjau/Terima (wajib menautkan koreksi atau catatan)/Tolak.

**C7 Denda** (`/keuangan/tagihan/denda`) — [13 §12](13-rancangan-denda.md#12-uiux).

### 4.4 D — Pembayaran

**D1 Daftar Pembayaran** (`/keuangan/pembayaran`, Ada, diperluas) — kolom: Nomor, Diterima, NIM/Nama, Sumber, Kanal, Nominal, Teralokasi, Belum diterapkan, Status; tab cepat "Menunggu verifikasi", "Dana belum diterapkan". Detail di `/keuangan/pembayaran/:id` ([07 §13](07-rancangan-pembayaran.md#13-uiux)).

**D2 Transaksi Online** (`/keuangan/pembayaran/online`) — [08 §17](08-rancangan-pembayaran-online-dan-gateway.md#17-uiux); kolom: Nomor transaksi, Mahasiswa, Kanal, Total, Status, Kedaluwarsa, Pembayaran terkait; drawer linimasa webhook.

**D3 Loket** (`/keuangan/pembayaran/loket`) — [09 §13](09-rancangan-pembayaran-tunai-dan-manual.md#13-uiux). Bila tidak ada sesi terbuka, halaman menampilkan form "Buka sesi kas" sebagai satu-satunya aksi. Konfirmasi bayar: ringkasan alokasi + kembalian; setelah sukses, kuitansi otomatis dicetak/diunduh.

**D4 Sesi Kas** (`/keuangan/pembayaran/sesi-kas`) — tutup sesi (input hitung fisik, selisih otomatis, catatan wajib bila ≠ 0); antrean verifikasi untuk FO/FM.

**D5 Verifikasi Bukti Bayar** (`/keuangan/pembayaran/verifikasi`)

| Aspek | Isi |
|---|---|
| Komponen | Tata letak dua panel: antrean (kiri) dan detail (kanan) dengan penampil berkas (zoom, putar), data klaim, saldo tagihan, saran baris mutasi, peringatan duplikat |
| Form | Verifikasi: nominal sebenarnya (default klaim), referensi bank, alokasi (default pilihan mahasiswa/FIFO), alasan bila nominal berbeda / *override*. Tolak: kode alasan (pilihan) + catatan |
| Konfirmasi | Verifikasi dengan peringatan duplikat memerlukan alasan *override* |
| Tombol | Verifikasi/Tolak (`payments.approve/.reject`), disembunyikan untuk pengajuan yang dicatat pengguna sendiri |
| Responsif | Tablet: panel bertumpuk; ponsel: tidak direkomendasikan untuk verifikasi (peringatan), tetapi tetap berfungsi |

**D6 Kuitansi** (`/keuangan/pembayaran/kuitansi`) — pencarian nomor/NIM; aksi Cetak Salinan, Ganti (alasan).

**D7 Pengembalian Dana** (`/keuangan/pembayaran/refund`) — [18 §12](18-rancangan-rekonsiliasi-dan-penyesuaian.md#12-uiux); form menampilkan dana tersedia sumber; langkah proses dengan unggah bukti; tombol sesuai status dan izin.

**D8 Koreksi & Reversal** (`/keuangan/pembayaran/koreksi`) — daftar penyesuaian; form dengan **pratinjau efek** (baris yang akan dibuat, saldo sebelum/sesudah, langkah berantai yang diperlukan).

### 4.5 E — Beasiswa & Potongan

| Halaman | Rute | Inti |
|---|---|---|
| Program Beasiswa | `/keuangan/beasiswa` (Ada) | Daftar program modul Scholarship + jumlah penerima KEU per program; tombol "Buat skema potongan" untuk program tanpa skema |
| Skema Potongan | `/keuangan/beasiswa/skema` | [11 §12](11-rancangan-beasiswa-dan-potongan.md#12-uiux) |
| Penerima | `/keuangan/beasiswa/penerima` | Tabel penerima, impor, aksi massal persetujuan |
| Evaluasi | `/keuangan/beasiswa/evaluasi` | Antrean hasil evaluasi kelayakan |
| Voucher | `/keuangan/beasiswa/voucher` | [12 §12](12-rancangan-voucher-tagihan.md#12-uiux) |

### 4.6 F — Deposit, G — UKT

| Halaman | Rute | Inti |
|---|---|---|
| Saldo Deposit | `/keuangan/deposit` | [14 §13](14-rancangan-deposit-mahasiswa.md#13-uiux) |
| Mutasi Deposit | `/keuangan/deposit/:id` | Ledger dengan saldo berjalan; aksi Pakai, Ajukan Refund, Bekukan (konfirmasi alasan) |
| Penetapan UKT | `/keuangan/ukt/penetapan` | [15 §12](15-rancangan-ukt-dan-validasi-penerima.md#12-uiux) |
| Validasi Penerima | `/keuangan/ukt/validasi` | Tab Validasi Tarif & Validasi Penerima |
| Keberatan | `/keuangan/ukt/keberatan` | Antrean keberatan dengan dokumen |

### 4.7 H — Monitoring, I — Laporan, J — Persetujuan, K — Integrasi

| Halaman | Rute | Inti |
|---|---|---|
| Monitoring Piutang | `/keuangan/monitoring/piutang` | [16 §14](16-rancangan-monitoring-piutang.md#14-uiux) |
| Umur Piutang | `/keuangan/monitoring/umur-piutang` | Matriks bucket × dimensi |
| Mahasiswa Belum Lunas | `/keuangan/monitoring/belum-lunas` | Daftar + status cekal |
| Status Cekal | `/keuangan/monitoring/cekal` | [17 §14](17-rancangan-cekal-keuangan.md#14-uiux); lencana 5 status tampilan |
| Kebijakan Cekal | `/keuangan/monitoring/cekal/kebijakan` | Form + simulasi wajib sebelum aktivasi |
| Rekonsiliasi | `/keuangan/monitoring/rekonsiliasi` | [18 §12](18-rancangan-rekonsiliasi-dan-penyesuaian.md#12-uiux) |
| Katalog Laporan | `/keuangan/laporan` | Kartu 18 laporan (judul, deskripsi, audiens) sesuai izin |
| Penampil Laporan | `/keuangan/laporan/:code` | Filter laporan, tabel hasil (paginasi), total, tombol Ekspor (CSV/XLSX/PDF sesuai laporan, `finance_reports.export`) |
| Ekspor Saya | `/keuangan/laporan/ekspor` | Status ekspor asinkron, unduh (kedaluwarsa 24 jam) |
| Ekspor Akuntansi | `/keuangan/laporan/ekspor-akuntansi` | Batch per periode tertutup |
| Persetujuan Keuangan | `/keuangan/persetujuan` | Kotak masuk penyetuju: tab per jenis (Tarif, Koreksi, Refund, Cicilan, Beasiswa, Voucher, UKT, Pengecualian Cekal, Pembukaan Cekal, Periode). Setiap item menampilkan pengaju, nominal, ringkasan dampak, umur; tombol Setujui/Tolak. Item yang diajukan pengguna sendiri tampil tanpa tombol, dengan keterangan "Tidak dapat menyetujui pengajuan sendiri" |
| Gateway & Webhook | `/keuangan/integrasi/gateway` | [08 §17](08-rancangan-pembayaran-online-dan-gateway.md#17-uiux) |
| Outbox & Sinkronisasi Cekal | `/keuangan/integrasi/sinkronisasi-cekal` | Antrean `pending/failed/dead`, tombol Kirim Ulang (`finance_integrations.update`) |

## 5. Struktur Menu dan Halaman Portal Mahasiswa

Grup baru **Keuangan** pada `PORTAL_NAV` (`permission: 'finance_self_service.read'`), rute di bawah `/portal/keuangan` (ditambahkan ke `ROUTES.portal`).

| Halaman | Rute | Inti | Aksi (izin `finance_self_service.*`) |
|---|---|---|---|
| Ringkasan Keuangan | `/portal/keuangan` | Kartu: Total terutang, Jatuh tempo terdekat (hitung mundur), Deposit tersedia, Status layanan (cekal); daftar tagihan terbuka; banner cekal bila ada | Bayar sekarang |
| Tagihan Saya | `/portal/keuangan/tagihan` | Mengganti `PortalInvoicesPage` statis. Kartu per tagihan: nomor, periode, sisa, jatuh tempo, status, lencana Terlambat/Cicilan | Bayar, Detail |
| Detail Tagihan | `/portal/keuangan/tagihan/:id` | Rincian komponen & potongan (bahasa sederhana), pembayaran yang sudah diterapkan, termin cicilan, denda terkait, kolom voucher, tombol Ajukan Cicilan / Ajukan Sengketa, Unduh PDF | `.update` (voucher), `.create` (cicilan, sengketa) |
| Bayar | `/portal/keuangan/bayar` | Pilih tagihan/termin & nominal → pilih kanal (kartu dengan biaya admin & total) → konfirmasi | `.create` |
| Instruksi Pembayaran | `/portal/keuangan/transaksi/:id` | VA/QR, total, hitung mundur, instruksi, status "Menunggu pembayaran" / "Menunggu konfirmasi" / "Berhasil" (hanya dari status server) | Batalkan transaksi |
| Konfirmasi Transfer | `/portal/keuangan/konfirmasi-transfer` | Form unggah bukti; daftar pengajuan & alasan penolakan | `.create`, `.update` |
| Riwayat Pembayaran | `/portal/keuangan/pembayaran` | Daftar pembayaran & kuitansi | Unduh kuitansi |
| Deposit Saya | `/portal/keuangan/deposit` | Saldo, mutasi; Pakai/Top-up bila diizinkan | `.update`, `.create` |
| Beasiswa Saya | `/portal/keuangan/beasiswa` | Penetapan aktif & efeknya | — |
| UKT Saya | `/portal/keuangan/ukt` | Golongan, riwayat, Ajukan Keberatan (dalam batas waktu) | `.create` |
| Status Layanan | `/portal/keuangan/status-layanan` | Status cekal per layanan, tagihan penyebab, cara menyelesaikan, pengecualian aktif | — |

Prioritas desain portal: **ponsel lebih dulu** (mayoritas mahasiswa membayar dari ponsel), tombol "Salin nomor VA", tampilan QR besar, nominal besar dan jelas, bahasa non-teknis (mis. "Sisa yang harus dibayar", bukan "outstanding").

Empty state portal: "Tidak ada tagihan yang perlu dibayar saat ini." Untuk riwayat kosong: "Belum ada pembayaran tercatat."

## 6. Tampilan bagi Peran Akademik dan Publik

| Peran | Tempat | Isi |
|---|---|---|
| BAAK, dosen wali, dekan/kaprodi | Profil mahasiswa SIAKAD, daftar bimbingan, persetujuan KRS | Lencana "Cekal keuangan: KRS, Ujian" + alasan umum + "Hubungi Bagian Keuangan". Tanpa nominal ([KEU-BR-270](25-business-rules-dan-validasi.md)) |
| Dosen wali saat menyetujui KRS | Dialog persetujuan | Bila mahasiswa tercekal `krs`, tombol Setujui menampilkan galat `ACADEMIC_BLOCKED` dari server |
| Publik (pemegang kuitansi) | `/kuitansi/verifikasi/:code` | Kartu Sah/Dibatalkan dengan data termasking ([09 §8](09-rancangan-pembayaran-tunai-dan-manual.md#8-kuitansi)) |

## 7. Label Status dan Istilah

Label mengikuti [29 §5](29-glossary-dan-keputusan-bisnis.md#5-pemetaan-istilah-indonesiakode). Varian `Badge` yang ada (`success`, `warning`, `danger`, dll.) dipetakan:

| Entitas | Nilai → label (varian) |
|---|---|
| Tagihan | `draft` Draf (netral) · `unpaid` Belum Dibayar (danger) · `partial` Dibayar Sebagian (warning) · `paid` Lunas (success) · `cancelled` Dibatalkan (netral, teks dicoret) · turunan: Terlambat (danger, garis tepi) · Disengketakan (info) · Cicilan (info) |
| Pembayaran | `pending_verification` Menunggu Verifikasi (warning) · `verified` Terverifikasi (success) · `rejected` Ditolak (danger) · `cancelled` Dibatalkan (netral) · `reversed` Dibalik (netral) |
| Transaksi online | `created`/`pending` Menunggu Pembayaran (warning) · `paid` Berhasil (success) · `failed` Gagal (danger) · `expired` Kedaluwarsa (netral) · `cancelled` Dibatalkan (netral) |
| Persetujuan umum | `pending_approval` Menunggu Persetujuan (warning) · `approved`/`active` Disetujui/Aktif (success) · `rejected` Ditolak (danger) · `revoked` Dicabut · `superseded` Digantikan · `expired` Kedaluwarsa (netral) |
| Cekal (tampilan) | Tidak Dicekal (success) · Dicekal (danger) · Dalam Pengecualian (info) · Menunggu Sinkronisasi (warning) · Gagal Sinkronisasi (danger, ikon peringatan) |

## 8. Responsif, Aksesibilitas, dan Bahasa

| Aspek | Ketentuan |
|---|---|
| Titik henti | Mengikuti Tailwind: `sm` 640, `md` 768, `lg` 1024, `xl` 1280 |
| Tabel di ponsel | Menjadi kartu: baris 1 identitas (nomor, nama), baris 2 nominal utama & status, aksi di menu |
| Ringkasan lengket | Pada form alokasi, cicilan, dan bayar: total dan validasi "cocok/tidak cocok" menempel di bawah layar |
| Aksesibilitas | Kontras warna status tidak bergantung warna saja (ikon + teks); semua aksi dapat dijangkau papan ketik; label `aria` pada tombol ikon; urutan fokus logis; pesan galat terhubung ke field |
| Angka | Format `Rp7.500.000` (pemisah ribuan titik), tanpa desimal bila ,00; nilai negatif dengan tanda minus dan warna netral (bukan merah untuk potongan) |
| Waktu | Ditampilkan dalam zona tenant dengan akhiran zona (WIB/WITA/WIT) pada jatuh tempo dan batas transaksi |
| Bahasa | Bahasa Indonesia baku sesuai istilah akademik. Admin app belum memakai i18n; seluruh label KEU dipusatkan di `frontend/src/constants/finance.ts` (status, kategori, pesan galat per `errors.code`) agar kelak dapat dipindahkan ke berkas terjemahan tanpa menyisir komponen |
| Mobile app | Portal API ([20 §5](20-rancangan-api-dan-integrasi.md#5-katalog-endpoint-mahasiswa)) dapat dipakai aplikasi Flutter (`docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md`) tanpa perubahan |
| Uji UI | [KEU-TC-UI-001](26-skenario-pengujian.md)…[KEU-TC-UI-004](26-skenario-pengujian.md) |
