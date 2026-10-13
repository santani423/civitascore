# 19 — Rancangan Dashboard dan Laporan Tracer

> **Status:** rancangan · Disusun 2026-10-13 (commit `47043ca`) · Kembali ke [README](README.md)
> Aturan: `TRACER-BR-011`, `TRACER-BR-012`, `TRACER-BR-014`, `TRACER-BR-015` · Indikator & pemetaan: [17 §5.6](17-rancangan-kuesioner-dan-survei.md#5-alur-bisnis-utama)

Dokumen ini mendefinisikan **indikator tracer beserta formula, denominator, periode, aturan data kosong, dan sumber data**, dashboard operasional dan outcome, tren antartahun, rekapitulasi untuk pelaporan akreditasi (APT/APS) **sesuai instrumen yang dikonfirmasi kampus**, serta ekspor Excel/CSV dengan pembatasan privasi.

## Daftar Isi

1. [Tujuan](#1-tujuan)
2. [Ruang Lingkup](#2-ruang-lingkup)
3. [Aktor dan Hak Akses](#3-aktor-dan-hak-akses)
4. [Prasyarat](#4-prasyarat)
5. [Alur Bisnis Utama](#5-alur-bisnis-utama)
6. [Alur Alternatif dan Kegagalan](#6-alur-alternatif-dan-kegagalan)
7. [Status dan Transisi Status](#7-status-dan-transisi-status)
8. [Data Input dan Output](#8-data-input-dan-output)
9. [Aturan Bisnis](#9-aturan-bisnis)
10. [Validasi](#10-validasi)
11. [Rancangan Database](#11-rancangan-database)
12. [Relasi Antarmodul](#12-relasi-antarmodul)
13. [Kontrak API Konseptual](#13-kontrak-api-konseptual)
14. [Rancangan Halaman UI/UX](#14-rancangan-halaman-uiux)
15. [Notifikasi](#15-notifikasi)
16. [Laporan](#16-laporan)
17. [Keamanan dan Audit](#17-keamanan-dan-audit)
18. [Acceptance Criteria](#18-acceptance-criteria)
19. [Test Case](#19-test-case)
20. [Asumsi dan Keputusan Terbuka](#20-asumsi-dan-keputusan-terbuka)

---

## 1. Tujuan

Menyajikan angka outcome lulusan yang dapat direproduksi: siapa pun yang menghitung ulang dari data mentah dengan definisi yang sama harus mendapat angka yang sama.

## 2. Ruang Lingkup

**Termasuk:** katalog indikator, dashboard operasional (progres pengumpulan), dashboard outcome, potongan per prodi/angkatan/cohort/periode, tren, rekap akreditasi, ekspor, agregat untuk pimpinan dan publikasi Career Center.

**Tidak termasuk:** penulisan narasi laporan akreditasi dan pengiriman data ke sistem pelaporan eksternal.

## 3. Aktor dan Hak Akses

| Aktor | Data yang dapat diakses | Permission |
|---|---|---|
| Petugas tracer / analis | Dashboard lengkap, jawaban tanpa identitas, ekspor tanpa identitas | `tracer_reports.read/export`, `tracer_responses.read/export` |
| Pemegang izin identitas | Ekspor beridentitas (untuk tindak lanjut) | + `tracer_respondent_identity.read/export` |
| Admin prodi / Kaprodi | Agregat prodinya, dan jawaban terbuka tanpa identitas untuk prodinya | `tracer_reports.read` scope `p` |
| Pimpinan (rektor, WR, dekan) | Agregat dengan sel minimum | `tracer_reports.read` |
| Pusat karier (publikasi) | Agregat yang disetujui untuk publikasi | `career_tracer_publications.*` ([20](20-rancangan-career-center-karirlink.md)) |
| Auditor | Agregat + log ekspor | `tracer_reports.read`, `audit_logs.read` |

## 4. Prasyarat

Kuesioner dengan pemetaan indikator ([17](17-rancangan-kuesioner-dan-survei.md)). Untuk indikator waktu tunggu: tanggal lulus resmi (`graduation_decisions.graduation_date` atau `students.graduated_at`).

## 5. Alur Bisnis Utama

### 5.1 Definisi populasi (denominator)

| Kode populasi | Definisi | Rumus |
|---|---|---|
| `P_DEF` | Target terdefinisi | Seluruh `tracer_targets` periode × kelompok (eligible + excluded) |
| `P_ELG` | Target eligible | `status = eligible` |
| `P_INV` | Terundang | Target eligible dengan undangan `initial` `sent`/`delivered` dan tanpa `hard_bounced`/`failed` |
| `P_RSP` | Responden | Target dengan `tracer_responses.status = submitted` |
| `P_Q(q)` | Responden yang ditanya q | Responden di mana pertanyaan q tampil menurut logika |
| `P_A(q)` | Responden yang menjawab q | `P_Q(q)` dengan jawaban tidak kosong |

### 5.2 Katalog indikator

Setiap indikator memiliki: `code`, `name`, formula, numerator, denominator, periode, aturan data kosong, sumber, dan unit potong. Ambang (mis. "≤ 6 bulan") adalah **parameter indikator yang dapat dikonfigurasi** sesuai instrumen yang dipakai.

| Kode | Indikator | Numerator | Denominator | Aturan data kosong | Sumber |
|---|---|---|---|---|---|
| `RR_ELG` | Response rate (terhadap eligible) | `|P_RSP|` | `|P_ELG|` | Denominator 0 → "tidak tersedia" | `tracer_targets`, `tracer_responses` |
| `RR_INV` | Response rate (terhadap terundang) | `|P_RSP ∩ P_INV|` | `|P_INV|` | idem | + `tracer_invitations` |
| `DLV` | Tingkat keterkiriman | `|P_INV|` | `|P_ELG|` yang diundang | | `tracer_invitations` |
| `CS_DIST` | Distribusi status karier | Jumlah responden per kategori status (dari `option_map`) | `|P_A(q_status)|` | Tidak menjawab → kategori "Tidak menjawab", **tidak** digabung ke "Belum bekerja" ([TRACER-BR-012](29-business-rules-dan-validasi.md#tracer-br-012)) | `tracer_answers` |
| `EMP_RATE` | Proporsi bekerja/wirausaha/studi lanjut | Responden berkategori `employed`, `self_employed`, `further_study` (himpunan dapat dikonfigurasi) | `|P_A(q_status)|` | | |
| `WT_MEAN`, `WT_MEDIAN` | Waktu tunggu kerja pertama (bulan) | Nilai dari pertanyaan waktu tunggu, atau selisih `tanggal mulai kerja − tanggal lulus` (pilih sumber per instrumen, `value_source`) | Responden bekerja/wirausaha yang menjawab | Nilai negatif (bekerja sebelum lulus) dihitung 0 bila instrumen menyatakan demikian, selain itu dikeluarkan dan dilaporkan | |
| `WT_LE_X` | Proporsi waktu tunggu ≤ X bulan | Responden dengan waktu tunggu ≤ X | Responden bekerja/wirausaha yang menjawab waktu tunggu | X = parameter | |
| `FIT_DIST` | Kesesuaian bidang kerja dengan bidang studi | Per kategori jawaban kesesuaian | Responden bekerja yang menjawab | | |
| `FIT_HIGH` | Proporsi kesesuaian tinggi | Kategori yang ditetapkan "sesuai" pada `option_map` | idem | | |
| `WP_TYPE` | Jenis tempat kerja (lokal/nasional/multinasional, sektor) | Per kategori | Responden bekerja yang menjawab | | |
| `INC_MEDIAN` | Median pendapatan (opsional) | Nilai pendapatan | Responden yang menjawab pertanyaan pendapatan | Hanya ditampilkan bila `n ≥ min_cell_size` dan pertanyaan diaktifkan ([TRACER-BR-016](29-business-rules-dan-validasi.md#tracer-br-016)) | Terenkripsi |
| `EMPSAT` | Kepuasan pengguna lulusan per aspek | Rata-rata skala per aspek, dan distribusi kategori | Respons survei pengguna lulusan yang menjawab aspek | | Survei `employer` |
| `EMPSAT_GOOD` | Proporsi penilaian ≥ "baik" per aspek | Respons dengan skor ≥ ambang | idem | Ambang = parameter | |

Formula disimpan di `tracer_indicators.definition` (json terstruktur, bukan kode bebas), sehingga dapat diaudit dan ditampilkan di tooltip dashboard.

### 5.3 Potongan dan periode

- Potongan: prodi, fakultas, jenjang, cohort (tahun lulus), periode tracer, jenis kelamin (opsional), jalur masuk (bila tersedia).
- **Cohort** ditentukan tanggal lulus resmi, bukan tahun undangan.
- **Tren antartahun** hanya menyambungkan indikator yang berasal dari pertanyaan dengan `code` sama dan tanpa tanda `semantic_change_review_required` yang belum diselesaikan ([17 §5.3](17-rancangan-kuesioner-dan-survei.md#5-alur-bisnis-utama)). Bila tidak sebanding, grafik menampilkan putus garis dan catatan.

### 5.4 Dashboard

**Operasional** (periode aktif): kartu target terdefinisi/eligible/dikecualikan, undangan terkirim/gagal/bounce, dibuka, mulai, selesai, belum mengisi, response rate (`RR_ELG` dan `RR_INV` berdampingan dengan label denominator), kuota, dan grafik respons harian.

**Outcome** (periode tertutup atau berjalan dengan label "sementara"): distribusi status karier, waktu tunggu (median + proporsi ≤ X), kesesuaian bidang, jenis tempat kerja, kepuasan pengguna lulusan per aspek, per prodi/cohort, dan tren.

Setiap kartu menampilkan `n` (denominator) dan periode data. Agregat dihitung ulang terjadwal (`tracer_result_aggregates`, setiap jam saat periode aktif) dengan `computed_at`, dan dapat dihitung ulang manual.

### 5.5 Rekap akreditasi (APT/APS)

- Rekap disusun melalui **profil pelaporan** (`tracer_reporting_profiles`) yang memetakan indikator ke tabel/butir instrumen akreditasi: `profile_code`, `instrument_name`, `instrument_version`, `effective_from`, `tables[]` (kolom → indikator + filter cohort), `verified_by`.
- Rancangan **tidak** menetapkan tabel atau butir instrumen akreditasi tertentu. Profil dibuat/diverifikasi berdasarkan instrumen dan versi regulasi yang benar-benar digunakan kampus/lembaga akreditasi terkait ([KK-Q-18](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q)).
- Profil yang belum diverifikasi ditampilkan dengan label "Draf — belum diverifikasi terhadap instrumen resmi" dan tidak dapat diekspor sebagai "final".
- Rekap institusi (APT) mengagregasi seluruh prodi. Rekap prodi (APS) memakai scope prodi.

### 5.6 Ekspor

| Opsi | Keterangan |
|---|---|
| Format | XLSX, CSV (openspout, Ada) |
| Filter | Periode, cohort, prodi, jenis responden, status responden (selesai/sedang/belum), versi kuesioner |
| Kolom | Pilih pertanyaan (kode + label), atribut target |
| Identitas | Default **tanpa identitas**: `respondent_ref` = hash stabil per ekspor (salt per ekspor). Identitas (NIM, nama, email) hanya dengan `tracer_respondent_identity.export` dan alasan tujuan ([TRACER-BR-014](29-business-rules-dan-validasi.md#tracer-br-014)) |
| Data sensitif | Pendapatan dan jawaban `sensitive` hanya bila diminta eksplisit, dan memerlukan izin identitas atau dilaporkan dalam rentang (binning) |
| Agregat pimpinan | Ekspor tabel agregat dengan supresi sel `n < min_cell_size` (default 5) |
| Pemrosesan | Asinkron (`data_exports`, [25 §8](25-rancangan-database-dan-erd.md#8-kelompok-lintas-modul)). Berkas kedaluwarsa 7 hari. Unduhan memerlukan login pembuat |

## 6. Alur Alternatif dan Kegagalan

| Kode | Kondisi | Penanganan |
|---|---|---|
| AF-RPT-01 | Pemetaan indikator belum diisi | Indikator "tidak tersedia" + tautan ke pemetaan |
| AF-RPT-02 | Sel kecil | Disupresi dengan tanda "< 5" |
| AF-RPT-03 | Ekspor besar (> 50.000 baris) | Dibagi berkas / dikompres. Antrean prioritas rendah |
| AF-RPT-04 | Jawaban di-*invalidate* setelah agregat dihitung | Agregat dihitung ulang dan versi agregat sebelumnya disimpan untuk jejak |

## 7. Status dan Transisi Status

`tracer_reporting_profiles.status`: `draft`, `verified`, `retired`. `data_exports.status`: `queued`, `processing`, `ready`, `failed`, `expired`. `tracer_result_aggregates` tanpa status (berversi `computed_at`).

## 8. Data Input dan Output

Contoh keluaran API indikator:

```json
{
  "indicator": "WT_LE_X",
  "parameters": { "x_months": 6 },
  "filters": { "tracer_period_id": "01J...", "study_program_id": "01J..." },
  "numerator": 48, "denominator": 61, "value": "0.7869",
  "suppressed": false, "computed_at": "2026-10-13T02:00:00Z",
  "definition_ref": "tracer_indicators/WT_LE_X@v1"
}
```

## 9. Aturan Bisnis

| ID | Aturan |
|---|---|
| [TRACER-BR-011](29-business-rules-dan-validasi.md#tracer-br-011) | Response rate selalu disajikan dengan denominator yang disebutkan (`P_ELG` atau `P_INV`) dan tidak dicampur |
| [TRACER-BR-012](29-business-rules-dan-validasi.md#tracer-br-012) | Non-responden dan "tidak menjawab" tidak digabung ke kategori "belum bekerja" |
| [TRACER-BR-014](29-business-rules-dan-validasi.md#tracer-br-014) | Ekspor default tanpa identitas. Identitas & data sensitif memerlukan izin khusus, alasan, dan audit. Agregat pimpinan menyupresi sel kecil |
| [TRACER-BR-015](29-business-rules-dan-validasi.md#tracer-br-015) | Publikasi hasil ke portal karier hanya berupa agregat yang disetujui dan memenuhi ukuran sel minimum |

## 10. Validasi

V-RPT-01 parameter indikator dalam rentang wajar (X bulan 1–60). V-RPT-02 ekspor beridentitas wajib `purpose` ≥ 20 karakter. V-RPT-03 filter prodi harus dalam scope pengguna.

## 11. Rancangan Database

`tracer_indicators`, `tracer_indicator_mappings`, `tracer_result_aggregates`, `tracer_reporting_profiles`, `data_exports` (lintas). Lihat [25](25-rancangan-database-dan-erd.md).

## 12. Relasi Antarmodul

Career Center: publikasi agregat (`career_tracer_publications`, [20](20-rancangan-career-center-karirlink.md)). OBE (`academic-learning/04`): `needs_rationale` profil prodi dapat merujuk laporan tracer (tautan, bukan salinan data).

## 13. Kontrak API Konseptual

| Method | URL | Permission |
|---|---|---|
| GET | `/tracer/dashboard/operational?period_id=` | `tracer_reports.read` |
| GET | `/tracer/dashboard/outcome?period_id=&group_by=study_program` | `tracer_reports.read` |
| GET | `/tracer/indicators`, `/tracer/indicators/{code}/values` | `tracer_reports.read` |
| POST | `/tracer/aggregates/recompute` | `tracer_periods.update` |
| GET/POST | `/tracer/reporting-profiles`, POST `/{profile}/verify` | `tracer_reports.read` / `tracer_periods.publish` |
| GET | `/tracer/reporting-profiles/{profile}/render?cohort=` | `tracer_reports.read` |
| POST | `/data-exports` (`type: tracer_responses`, filter, kolom, `include_identity`, `purpose`) | `tracer_responses.export` (+ identitas) |
| GET | `/data-exports/{export}`, `/download` | Pembuat |

## 14. Rancangan Halaman UI/UX

| Halaman | Rute | Inti |
|---|---|---|
| Dashboard Tracer | `/tracer` | Operasional (§5.4) |
| Hasil Survei | `/tracer/hasil` | Per pertanyaan: distribusi, n ditanya, n menjawab, jawaban terbuka (tanpa identitas, dapat dicari) |
| Dashboard Outcome | `/tracer/outcome` | Indikator dengan tooltip definisi, filter prodi/cohort/periode, tren |
| Ekspor dan Laporan | `/tracer/laporan` | Wizard ekspor (filter → kolom → identitas & alasan → antre), daftar ekspor saya, profil pelaporan akreditasi |

Visual mengikuti pedoman: setiap persentase disertai "n = …", dan warna kategori "Tidak menjawab" netral abu-abu.

## 15. Notifikasi

`data_exports.ready` (pembuat), `tracer.reporting_profile.verification_needed` (petugas).

## 16. Laporan

| ID | Laporan |
|---|---|
| TRACER-RPT-05 | Outcome per prodi/cohort (status karier, waktu tunggu, kesesuaian) |
| TRACER-RPT-06 | Kepuasan pengguna lulusan per aspek |
| TRACER-RPT-07 | Tren antartahun |
| TRACER-RPT-08 | Rekap akreditasi per profil terverifikasi |
| TRACER-RPT-09 | Log ekspor (siapa, kapan, filter, identitas, alasan) |

## 17. Keamanan dan Audit

Setiap ekspor menulis `audit_logs` `exported` + baris `data_exports` (filter, kolom, identitas ya/tidak, `purpose`). Akses jawaban terbuka tercatat `viewed_sensitive` bila memuat pertanyaan `personal`/`sensitive`.

## 18. Acceptance Criteria

| ID | Kriteria |
|---|---|
| TRACER-AC-14 | Data uji: 100 eligible, 80 terundang, 40 responden → `RR_ELG = 40%`, `RR_INV = 50%`, keduanya berlabel denominator |
| TRACER-AC-15 | Data uji dengan 10 "Belum bekerja" dan 15 tidak menjawab status → kategori "Belum bekerja" = 10, "Tidak menjawab" = 15 |
| TRACER-AC-16 | Ekspor tanpa izin identitas tidak memuat NIM/nama/email (diverifikasi pada berkas) |
| TRACER-AC-17 | Agregat pimpinan menyupresi sel n < 5 |

## 19. Test Case

| ID | Judul | Prioritas |
|---|---|---|
| TRACER-TC-040 | Denominator response rate | P1 |
| TRACER-TC-041 | Non-responden tidak digabung belum bekerja | P1 |
| TRACER-TC-042 | Ekspor hasil tanpa identitas | P1 |
| TRACER-TC-043 | Ekspor beridentitas memerlukan izin & alasan | P1 |
| TRACER-TC-044 | Supresi sel kecil | P2 |
| TRACER-TC-045 | Tren putus saat butir tidak sebanding | P3 |
| TRACER-TC-046 | Isolasi data antartenant pada dashboard & ekspor | P1 |

## 20. Asumsi dan Keputusan Terbuka

| ID | Hal |
|---|---|
| [KK-Q-18](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q) | Instrumen akreditasi & versi yang dipakai |
| [KK-Q-17](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q) | Cohort wajib & parameter indikator (mis. X bulan) |
| [KK-A-13](33-glossary-dan-keputusan-bisnis.md#4-asumsi-kk-a) | `min_cell_size` default 5 |
