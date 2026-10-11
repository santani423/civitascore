# 14 — Skenario Uji & Kriteria Penerimaan Modul Akademik & Pembelajaran

> **Status:** rancangan. **Disusun:** 2026-10-11, berdasarkan kondisi repository pada commit `ab81cc7`.
> Kembali ke [README](README.md) · Aturan bisnis: [10-business-rules.md](10-business-rules.md) · Hak akses: [11-roles-permissions.md](11-roles-permissions.md) · Keamanan: [13-security-audit.md](13-security-audit.md) · Tahapan: [15-implementation-roadmap.md](15-implementation-roadmap.md)
>
> Dokumen ini adalah acuan QA untuk menguji rancangan modul SIAKAD, EDLINK/LMS, CBT & CBT AI, OBE, EDOM, dan MBKM, termasuk lapisan entitlement paket dan keamanan. Skenario ditulis **black box** (melalui REST `/api/v1/...`, UI web `frontend/`, dan aplikasi Flutter `mobile/`). Skenario untuk fitur berlabel **(Baru)** menguji rancangan target dan **belum dapat dijalankan** sebelum fitur tersebut dibangun.

## Daftar Isi

1. [Tujuan, Ruang Lingkup, dan Hubungan dengan Dokumen Uji yang Ada](#1-tujuan-ruang-lingkup-dan-hubungan-dengan-dokumen-uji-yang-ada)
2. [Konvensi Penulisan Skenario](#2-konvensi-penulisan-skenario)
3. [Lingkungan, Data Uji, dan Akun](#3-lingkungan-data-uji-dan-akun)
4. [SIAKAD (TS-SIA)](#4-siakad-ts-sia)
5. [EDLINK/LMS (TS-LMS)](#5-edlinklms-ts-lms)
6. [CBT & CBT AI (TS-CBT)](#6-cbt--cbt-ai-ts-cbt)
7. [OBE (TS-OBE)](#7-obe-ts-obe)
8. [EDOM (TS-EDM)](#8-edom-ts-edm)
9. [MBKM (TS-MBK)](#9-mbkm-ts-mbk)
10. [Entitlement Paket (TS-ENT)](#10-entitlement-paket-ts-ent)
11. [Keamanan, Isolasi, dan Privasi (TS-SEC)](#11-keamanan-isolasi-dan-privasi-ts-sec)
12. [Uji Non-Fungsional: Konkurensi, Kinerja, Ketahanan](#12-uji-non-fungsional-konkurensi-kinerja-ketahanan)
13. [Matriks Keterlacakan Brief → Skenario](#13-matriks-keterlacakan-brief--skenario)
14. [Penempatan Uji Otomatis](#14-penempatan-uji-otomatis)
15. [Kriteria Masuk, Kriteria Keluar, dan Regresi](#15-kriteria-masuk-kriteria-keluar-dan-regresi)

---

## 1. Tujuan, Ruang Lingkup, dan Hubungan dengan Dokumen Uji yang Ada

### 1.1 Tujuan

1. Menyediakan skenario uji yang dapat ditelusuri ke setiap butir brief §13 dan ke aturan bisnis `BR-*`.
2. Menetapkan **kriteria lulus** yang objektif: kode HTTP, isi envelope `ApiResponse`, perubahan baris database, entri `audit_logs`, dan notifikasi.
3. Menunjukkan **di mana** setiap skenario diotomasi (Pest Feature/Unit, Flutter, e2e) dan mana yang tetap manual.

### 1.2 Ruang lingkup

| Termasuk | Tidak termasuk |
|---|---|
| Alur fungsional SIAKAD A.1–A.10, LMS B.1–B.6, CBT C.1–C.5, OBE D.1–D.7, EDOM E.1–E.3 | Uji fungsional PMB, Keuangan, Perpustakaan, Kemahasiswaan (hanya titik integrasinya: status tagihan → cekal, peserta seleksi → CBT) |
| Empat lapis izin (paket → tenant → permission & scope → syarat data) | Uji penetrasi menyeluruh (dirujuk ke [13-security-audit.md](13-security-audit.md)) |
| Isolasi antar-PT dan privasi (EDOM anonim, orang tua, proctoring) | Validasi format laporan akreditasi yang belum diputuskan ([OD-19](16-open-decisions.md#od-19)) |
| Konkurensi kapasitas kelas, token QR, ujian saat jaringan putus | Uji beban skala produksi (hanya rencana di §12) |

### 1.3 Hubungan dengan dokumen uji yang ada

| Dokumen | Isi | Hubungan |
|---|---|---|
| [../BLACK-BOX-TESTING.md](../BLACK-BOX-TESTING.md) | Checklist black box kondisi kode per 2026-08-09 (ID `AKM-`, `KRS-`, `NIL-`, `ABS-`, `TRX-`, dst.) | Tetap berlaku sebagai **uji regresi** fitur (Ada). Beberapa catatannya sudah tertinggal: `POST /students` dan CRUD `lecturers` kini ada (AKM-05), dan kepemilikan kelas dosen sudah dapat ditegakkan melalui flag `academic.lecturer_ownership` (NIL-09). Pemetaan regresi ada di §15.3 |
| [../semester-pendek/pengujian-dan-penerimaan.md](../semester-pendek/pengujian-dan-penerimaan.md) | Strategi uji Semester Pendek (`AC-xx`) | Skenario SP tidak diulang. Dokumen ini hanya menguji titik sambung `academic_terms.term_type` (TS-SIA-38) |
| [../EXAM_MODULE.md](../EXAM_MODULE.md), [../EXAM_RANDOMIZATION.md](../EXAM_RANDOMIZATION.md) | Perilaku mesin ujian yang sudah ada | Dasar skenario CBT berlabel (Ada) |

---

## 2. Konvensi Penulisan Skenario

### 2.1 Format ID

`TS-<MOD>-NN`, dengan `<MOD>` sama dengan kode modul pada ID aturan bisnis:

| Kode | Modul | Rentang ID |
|---|---|---|
| `SIA` | SIAKAD (A.1–A.9) | TS-SIA-01 … TS-SIA-40 |
| `LMS` | EDLINK/LMS | TS-LMS-01 … TS-LMS-22 |
| `CBT` | CBT & CBT AI | TS-CBT-01 … TS-CBT-24 |
| `OBE` | OBE & OBE AI | TS-OBE-01 … TS-OBE-15 |
| `EDM` | EDOM | TS-EDM-01 … TS-EDM-14 |
| `MBK` | MBKM (A.10) | TS-MBK-01 … TS-MBK-10 |
| `ENT` | Entitlement paket | TS-ENT-01 … TS-ENT-12 |
| `SEC` | Keamanan, isolasi, privasi, audit | TS-SEC-01 … TS-SEC-20 |

ID tidak boleh dipakai ulang. Skenario yang dihapus ditandai *ditarik*, bukan dinomori ulang.

### 2.2 Referensi aturan bisnis

Kolom **Referensi aturan** memakai ID tingkat area `BR-<MODUL>-<AREA>` sesuai skema di [10-business-rules.md](10-business-rules.md). Nomor urut aturan (`-NN`) mengikuti daftar resmi di dokumen tersebut. Jika satu skenario menguji aturan tertentu yang sudah bernomor, nomor itu ditambahkan saat dokumen 10 difinalkan.

| Area | Arti | Area | Arti |
|---|---|---|---|
| `BR-SIA-MST` | Data master & periode | `BR-LMS-KLS` | Kelas LMS akademik/umum |
| `BR-SIA-KUR` | Kurikulum, prasyarat, ekuivalensi | `BR-LMS-MAT` | Materi & versi |
| `BR-SIA-KLS` | Kelas perkuliahan | `BR-LMS-TGS` | Tugas & pengumpulan |
| `BR-SIA-JDW` | Jadwal & bentrok | `BR-LMS-FRM` | Forum |
| `BR-SIA-KRS` | Siklus KRS | `BR-LMS-QR` | Sesi hybrid & presensi QR |
| `BR-SIA-PRS` | Sesi, presensi, jurnal | `BR-LMS-ANL` | Dashboard, engagement, gamification |
| `BR-SIA-NIL` | Komponen, nilai, publikasi, revisi | `BR-LMS-STO` | Storage & kuota |
| `BR-SIA-IPK` | IPS/IPK, transkrip | `BR-CBT-BNK` | Bank soal |
| `BR-SIA-WAL` | Perwalian | `BR-CBT-UJN` | Pengaturan ujian & peserta |
| `BR-SIA-CKL` | Cekal & dispensasi | `BR-CBT-ATT` | Pengerjaan (timer, jawaban, sesi) |
| `BR-SIA-CUT` | Cuti & status mahasiswa | `BR-CBT-NIL` | Penilaian & pengiriman nilai |
| `BR-SIA-TRF` | Transfer, konversi, double degree | `BR-CBT-AI` | CBT AI |
| `BR-SIA-TA` | Tugas akhir | `BR-CBT-PRC` | Proctoring |
| `BR-SIA-ORT` | Akses orang tua/wali | `BR-OBE-ART` | Artefak OBE & pengesahan |
| `BR-MBK-PRG` | Program & mitra MBKM | `BR-OBE-MAP` | Pemetaan |
| `BR-MBK-PAR` | Keikutsertaan, logbook, evaluasi | `BR-OBE-UKR` | Pengukuran CPMK/CPL |
| `BR-MBK-KNV` | Konversi SKS/MK | `BR-OBE-AI` | OBE AI |
| `BR-MBK-IKU` | Rekap IKU | `BR-ENT-PKG` | Fitur per paket, aktivasi tenant, downgrade |
| `BR-EDM-PRD` | Periode & formulir EDOM | `BR-ENT-LIM` | Batas kuantitatif (storage, kelas umum) |
| `BR-EDM-ISI` | Pengisian | `BR-SEC-STATE` | Transisi status hanya lewat service |
| `BR-EDM-PRV` | Privasi & anonimitas | `BR-SEC-AUD` | Audit |
| `BR-EDM-REK` | Rekap & pelaporan | `BR-SEC-APV` | Persetujuan & prinsip empat mata |

Area keamanan tambahan berikut **diusulkan** dokumen ini untuk aturan yang pemiliknya [13-security-audit.md](13-security-audit.md) dan belum tercantum di katalog 10: `BR-SEC-TEN` (isolasi tenant), `BR-SEC-SCP` (scope data per role), `BR-SEC-PRV` (privasi data pribadi), `BR-SEC-RTL` (rate limit), `BR-SEC-FIL` (akses berkas). `BR-SEC-*` dipakai bila skenario menguji aturan keamanan umum lintas area.

### 2.3 Atribut skenario

Skenario **wajib brief §13** dan kasus tepi kritis ditulis sebagai blok rinci:

| Atribut | Isi |
|---|---|
| Referensi aturan | ID `BR-*` |
| Prioritas · Jenis | **Tinggi / Sedang / Rendah** (sama dengan [../BLACK-BOX-TESTING.md](../BLACK-BOX-TESTING.md)) · Positif / Negatif / Tepi / Konkurensi / Privasi |
| Status fitur | (Ada) / (Parsial) / (Baru) sesuai README §3.2 |
| Kondisi awal | Data dan konfigurasi yang harus ada |
| Langkah | Urutan aksi pengguna/API |
| Hasil diharapkan | Perilaku sistem yang teramati |
| Kriteria lulus | Pernyataan yang dapat diverifikasi otomatis atau oleh penguji (kode HTTP, `errors.code`, baris DB, audit, notifikasi) |
| Otomasi | Lokasi uji otomatis yang direncanakan |

Skenario pelengkap ditulis dalam tabel ringkas dengan kolom yang sama.

### 2.4 Konvensi hasil API

- Sukses: `{success: true, message, data, meta}`. Daftar terpaginasi: `meta: {current_page, per_page, total, last_page}`.
- Gagal validasi: `422 {success: false, message, errors: {field: [...]}}`.
- Konflik aturan bisnis: `409` melalui `ConflictException`. Bila ada kode mesin, kode itu muncul di `errors.code` (pola yang sudah ada: `SCHEDULE_CONFLICT`). Kode yang dipakai dokumen ini mengikuti katalog [08-api-specification.md §1.7](08-api-specification.md#17-katalog-kode-error-domain), antara lain `PERIOD_CLOSED`, `ACADEMIC_BLOCKED`, `PREREQUISITE_NOT_MET`, `CREDIT_LIMIT_EXCEEDED`, `CLASS_FULL`, `KRS_LOCKED`, `ROOM_CONFLICT`, `LECTURER_CONFLICT`, `QR_TOKEN_EXPIRED`, `ATTENDANCE_BELOW_MINIMUM`, `GRADE_INCOMPLETE`, `GRADE_PUBLISHED`, `ATTEMPT_CLOSED`, `ANSWER_OUT_OF_ORDER`, `EXAM_DUPLICATE`, `EDOM_ALREADY_SUBMITTED`, `EDOM_PERIOD_CLOSED`, `EDOM_REQUIRED`, `STORAGE_QUOTA_EXCEEDED`, `GENERAL_CLASS_QUOTA_EXCEEDED`, dan `INVALID_STATE_TRANSITION` untuk transisi status yang tidak sah.
- Tanpa izin: `403`. Fitur tidak tersedia di paket atau tidak aktif di tenant: `403` dengan `errors.code` `FEATURE_NOT_ENTITLED` atau `FEATURE_DISABLED`.
- Sumber daya milik tenant lain atau di luar scope data: **`404`**, bukan `403` dan bukan `500`. Pola ini sudah dipakai (`findOrFail` ter-scope tenant, lihat KRS-13 di dokumen black box).

### 2.5 Angka kebijakan

Semua angka kebijakan dalam skenario (batas SKS, kehadiran minimum, bobot, skala nilai, masa berlaku QR, kuota) adalah **contoh** dan dibaca dari `university_settings` grup `academic.*` atau tabel konfigurasi (`grade_scales`, `academic_block_rules`, `plan_feature_entitlements`). Kriteria lulus selalu dirumuskan relatif terhadap konfigurasi, misalnya "SKS = batas terkonfigurasi" dan bukan "SKS = 24".

---

## 3. Lingkungan, Data Uji, dan Akun

### 3.1 Lingkungan

| Lingkungan | Basis data | Kegunaan |
|---|---|---|
| CI default | SQLite `:memory:` (sesuai `phpunit.xml`) | Unit & Feature Pest. **Tidak** membuktikan penguncian baris (`lockForUpdate` tidak berefek) |
| CI grup `mysql` | MySQL/PostgreSQL sesuai produksi | Uji konkurensi (§12.1), indeks unik, dan kunci baris |
| Staging | Salinan skema produksi, data sintetis | Uji e2e web & mobile, uji beban, uji pemulihan |

### 3.2 Fixture tenant

| Fixture | Isi |
|---|---|
| **U-A** (paket `enterprise`) | 2 fakultas (F1, F2), 3 prodi (P1, P2 di F1; P3 di F2), term berjalan + 2 term lampau, kurikulum P1 versi 2020 dan 2024, 30 mata kuliah, 12 kelas dengan jadwal, 60 mahasiswa angkatan 2020/2024, 8 dosen dengan akun (`lecturers.user_id`), 2 orang tua tertaut |
| **U-B** (paket `starter`) | Struktur minimal (1 fakultas, 1 prodi, 2 kelas). Dipakai untuk uji isolasi dan entitlement |
| **U-C** (paket `professional`, add-on OBE nonaktif) | Untuk uji add-on dan downgrade |

Fixture memakai pola yang sudah ada: `TenantContext::setUniversityId()` sebelum factory, lalu `null` kembali (lihat `makeEnrollmentFixture()` di `app/Modules/Academic/Tests/Feature/KrsEnrollmentTest.php` dan helper `portalWorld()` di `tests/Helpers/StudentPortal.php`). Pemberian izin memakai `actingAsUserWithUniversityPermissions()` (`tests/Pest.php`).

### 3.3 Akun uji per role

| Role (slug kanon) | Akun fixture | Scope |
|---|---|---|
| `super_admin` | `superadmin@` | Platform |
| `university_administrator` | `admin.ua@` | U-A |
| `academic_administrator` (BAAK) | `baak.ua@` | U-A |
| `faculty_administrator` | `adminf1.ua@` | `faculty` = F1 |
| `study_program_administrator` | `adminp1.ua@` | `study_program` = P1 |
| `head_of_study_program` | `kaprodi.p1@` | `study_program` = P1 |
| `dean` | `dekan.f1@` | `faculty` = F1 |
| `lecturer` | `dosen1@` (mengampu K1, K2), `dosen2@` (mengampu K3) | Kelas yang diampu |
| `academic_advisor` | `wali1@` (wali 15 mahasiswa P1) | `students.academic_advisor_id` |
| `student` | `mhs.p1.01@` … | Diri sendiri |
| `quality_assurance` (LPM) | `lpm.ua@` | U-A |
| `exam_committee`, `exam_proctor` | `panitia.ua@`, `pengawas.ua@` | Ujian yang ditugaskan |
| `guardian` | `ortu.01@` (tertaut ke `mhs.p1.01`) | Mahasiswa tertaut |
| `lms_administrator` | `lmsadmin.ua@` | U-A |
| Tenant lain | `admin.ub@` | U-B |

---

## 4. SIAKAD (TS-SIA)

### 4.1 KRS

#### TS-SIA-01 — KRS: prasyarat terpenuhi

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-KRS`, `BR-SIA-KUR` |
| Prioritas · Jenis · Status | Tinggi · Positif · (Parsial: cek prasyarat sudah ada di `KrsPlanService`) |
| Kondisi awal | Periode KRS term berjalan terbuka. `mhs.p1.01` berstatus `active`, tidak ada cekal aktif. Mata kuliah *Basis Data Lanjut* memiliki prasyarat *Basis Data* dengan `min_letter_grade` = C (contoh). Mahasiswa memiliki nilai B untuk *Basis Data* di term lampau. Kelas BDL-A berkapasitas 40, terisi 10 |
| Langkah | 1. Login sebagai mahasiswa. 2. `GET /student/krs/offerings`. 3. `POST /student/krs/items {class_section_id: BDL-A}` |
| Hasil diharapkan | Penawaran menandai BDL-A dapat dipilih. Item tersimpan dengan status `draft` pada `krs_submissions` berstatus `draft` |
| Kriteria lulus | Respons 201. `krs_items` bertambah satu baris (`student_id`, `class_section_id` = BDL-A, `status = draft`). `total_credits` header bertambah sebesar SKS mata kuliah |
| Otomasi | Pest Feature `app/Modules/Academic/Tests/Feature/StudentKrsSelfServiceTest.php` (diperluas) |

#### TS-SIA-02 — KRS: prasyarat tidak terpenuhi

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-KRS`, `BR-SIA-KUR` |
| Prioritas · Jenis · Status | Tinggi · Negatif · (Parsial) |
| Kondisi awal | Seperti TS-SIA-01, tetapi nilai *Basis Data* = D (di bawah `min_letter_grade`). Varian B: mahasiswa belum pernah mengambil *Basis Data*. Varian C: *Basis Data* sedang diambil di term berjalan (belum bernilai) |
| Langkah | `POST /student/krs/items {class_section_id: BDL-A}` untuk setiap varian |
| Hasil diharapkan | Ditolak dengan pesan yang menyebut prasyarat yang belum terpenuhi. Penawaran (`GET /student/krs/offerings`) menampilkan alasan tidak dapat dipilih |
| Kriteria lulus | 409 dengan `errors.code = PREREQUISITE_NOT_MET` dan daftar mata kuliah prasyarat di konteks error. Tidak ada baris `krs_items` baru. Varian C mengikuti kebijakan [10-business-rules.md](10-business-rules.md) (default: ditolak) |
| Otomasi | Pest Feature (sama dengan TS-SIA-01) + Unit evaluator prasyarat |

#### TS-SIA-03 — KRS: batas SKS tepat di batas dan satu SKS di atasnya

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-KRS` (batas SKS dari `academic.krs.max_sks_tiers`, [OD-05](16-open-decisions.md#od-05)) |
| Prioritas · Jenis · Status | Tinggi · Tepi · (Parsial: tier masih `SKS_LIMIT_TIERS` hardcoded, dipindah ke konfigurasi di Tahap 0) |
| Kondisi awal | Konfigurasi tenant: IP 2,50–2,99 → batas 22 SKS (contoh). Mahasiswa IP dasar = 2,50 tepat di batas bawah tier. Draf KRS sudah 19 SKS |
| Langkah | 1. Tambah kelas 3 SKS (total 22). 2. Tambah kelas 1 SKS (total 23). 3. Ulangi dengan IP dasar 2,49 (tier bawahnya, batas 20 SKS contoh) |
| Hasil diharapkan | Langkah 1 berhasil (sama dengan batas diizinkan). Langkah 2 ditolak. Langkah 3: penambahan yang membuat total > 20 ditolak |
| Kriteria lulus | Langkah 1: 201, `total_credits` = batas. Langkah 2: 409 `CREDIT_LIMIT_EXCEEDED`, pesan memuat batas dan SKS saat ini. Batas tier dibaca dari konfigurasi: mengubah setting tenant mengubah hasil tanpa deploy. `POST /student/krs/submit` dengan total > batas (mis. setelah tier diturunkan admin) ditolak 409 |
| Otomasi | Pest Unit (penghitung batas, nilai batas tier) + Feature |

#### TS-SIA-04 — KRS: bentrok jadwal

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-KRS`, `BR-SIA-JDW` |
| Prioritas · Jenis · Status | Tinggi · Negatif · (Parsial: `SCHEDULE_CONFLICT` sudah ada) |
| Kondisi awal | Draf KRS memuat kelas X (Senin 08:00–10:30). Kelas Y: Senin 10:00–11:40. Kelas Z: Senin 10:30–12:10 (bersinggungan tepat) |
| Langkah | 1. Tambah Y. 2. Tambah Z |
| Hasil diharapkan | Y ditolak dengan rincian kelas yang bentrok. Z diterima (batas yang bersinggungan tepat tidak dianggap bentrok) |
| Kriteria lulus | Langkah 1: 409 `errors.code = SCHEDULE_CONFLICT`, `errors.conflicts[0]` memuat nama mata kuliah dan jadwal. Langkah 2: 201. Jadwal berstatus `cancelled` tidak ikut dibandingkan |
| Otomasi | Pest Feature + Unit deteksi irisan (melanjutkan `app/Modules/Academic/Tests/Unit/ClassScheduleTest.php`) |

#### TS-SIA-05 — KRS: kelas penuh

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-KRS` |
| Prioritas · Jenis · Status | Tinggi · Negatif · (Ada) |
| Kondisi awal | Kelas K-A kapasitas 30, terisi 30 (item `pending` + `enrolled`). Kelas K-B mata kuliah sama, terisi 12 |
| Langkah | 1. Tambah K-A. 2. Tambah K-B |
| Hasil diharapkan | K-A ditolak dengan saran memilih kelas lain. K-B diterima. Penawaran menandai K-A "Penuh" |
| Kriteria lulus | Langkah 1: 409 `CLASS_FULL`. Langkah 2: 201. Item `dropped` tidak dihitung terhadap kapasitas |
| Otomasi | Pest Feature (`KrsEnrollmentTest`, `StudentKrsSelfServiceTest`) |

#### TS-SIA-06 — KRS: perebutan kursi terakhir secara bersamaan

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-KRS` (kapasitas tidak boleh terlampaui) |
| Prioritas · Jenis · Status | Tinggi · Konkurensi · (Parsial: `lockForUpdate` sudah dipakai di `KrsPlanService`, belum dibuktikan di DB nyata) |
| Kondisi awal | Basis data MySQL/PostgreSQL. Kelas kapasitas 30, terisi 29. Sepuluh mahasiswa berbeda memenuhi semua syarat |
| Langkah | Sepuluh proses paralel memanggil `POST /student/krs/items` ke kelas yang sama dalam jendela < 100 ms |
| Hasil diharapkan | Tepat satu permintaan berhasil. Sembilan ditolak sebagai kelas penuh. Tidak ada deadlock yang tidak tertangani |
| Kriteria lulus | Jumlah respons 201 = 1, jumlah 409 `CLASS_FULL` = 9, `COUNT(krs_items aktif)` kelas = 30, tidak ada 500. Varian: satu mahasiswa dengan sisa kuota 3 SKS mengirim dua permintaan paralel ke dua kelas 3 SKS → tepat satu berhasil. Varian urutan terbalik ({X,Y} dan {Y,X}) → tidak ada deadlock |
| Otomasi | Pest Feature `@group mysql` (dilewati di SQLite) + uji beban staging (§12.1) |

#### TS-SIA-07 — KRS: cekal keuangan memblokir pengisian

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-CKL`, `BR-SIA-KRS` |
| Prioritas · Jenis · Status | Tinggi · Negatif · (Baru: `KrsPlanService` belum memeriksa cekal) |
| Kondisi awal | `academic_block_rules`: tagihan jatuh tempo belum lunas → cekal layanan `krs` dan `exam` (contoh). Mahasiswa memiliki tagihan semester berjalan berstatus belum lunas, sehingga terbentuk `academic_blocks` `active` kategori keuangan |
| Langkah | 1. `GET /student/krs`. 2. `POST /student/krs/items`. 3. `POST /student/krs/submit` (jika draf sudah ada sebelum cekal terbit) |
| Hasil diharapkan | Halaman KRS menampilkan banner cekal beserta alasan dan unit yang dapat dihubungi. Penambahan dan pengajuan ditolak |
| Kriteria lulus | Langkah 2 dan 3: 409 `ACADEMIC_BLOCKED` dengan `errors.blocks[]` berisi kategori, alasan, dan layanan terdampak. Detail nominal tagihan **tidak** ditampilkan di respons KRS. Setelah tagihan lunas (event dari Keuangan), cekal berubah `revoked` atau `expired` dan langkah 2 berhasil tanpa campur tangan manual |
| Otomasi | Pest Feature `app/Modules/Academic/Tests/Feature/AcademicBlockTest.php` (event Keuangan di-*fake*) |

#### TS-SIA-08 — Dispensasi cekal

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-CKL`, `BR-SEC-AUD` |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru) |
| Kondisi awal | Cekal keuangan aktif seperti TS-SIA-07. BAAK memiliki `academic_block_exemptions.create` |
| Langkah | 1. BAAK mengajukan dispensasi: layanan `krs`, berlaku sampai tanggal T, alasan wajib, dokumen pendukung opsional. 2. Mahasiswa mengisi KRS. 3. Waktu dimajukan melewati T. 4. Mahasiswa menambah item lagi |
| Hasil diharapkan | Selama dispensasi berlaku, KRS dapat diisi. Cekal tetap tercatat (status `exempted`), bukan dihapus. Setelah T, cekal kembali `active` jika penyebab belum hilang |
| Kriteria lulus | Langkah 1: 201, `academic_block_histories` mencatat `active → exempted` dengan `user_id` penetap dan alasan. `audit_logs` memuat aksi dispensasi. Notifikasi ke mahasiswa terkirim. Langkah 2: 201. Langkah 4: 409 `ACADEMIC_BLOCKED`. Dispensasi tanpa alasan → 422. Pengguna tanpa permission → 403 |
| Otomasi | Pest Feature (`travel()` untuk masa berlaku) |

#### TS-SIA-09 — Beberapa cekal bersamaan

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-CKL` |
| Prioritas · Jenis · Status | Tinggi · Tepi · (Baru) |
| Kondisi awal | Mahasiswa memiliki dua cekal aktif: keuangan (layanan `krs`, `exam`) dan akademik (layanan `krs`). Dispensasi diberikan hanya untuk cekal keuangan |
| Langkah | 1. Isi KRS. 2. Mulai ujian |
| Hasil diharapkan | KRS tetap ditolak karena cekal akademik masih aktif. Ujian dapat dimulai karena satu-satunya cekal ujian sudah didispensasi |
| Kriteria lulus | Langkah 1: 409, `errors.blocks[]` hanya memuat cekal akademik. Langkah 2: 200/201. Respons tidak pernah memuat cekal yang tidak relevan dengan layanan yang diminta |
| Otomasi | Pest Unit (resolver layanan terblokir) + Feature |

#### TS-SIA-10 — KRS di luar periode

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-KRS`, `BR-SIA-MST` |
| Prioritas · Jenis · Status | Tinggi · Negatif · (Parsial: periode dari `krs_start_date`/`krs_end_date`, target `academic_term_periods`) |
| Kondisi awal | Periode KRS berakhir kemarin |
| Langkah | `POST /student/krs/items`, `POST /student/krs/submit`, `POST /student/krs/cancel` |
| Hasil diharapkan | Semua aksi tulis ditolak. Halaman KRS hanya baca dengan informasi periode |
| Kriteria lulus | 409 `PERIOD_CLOSED`. Batas waktu dievaluasi di zona waktu tenant: aksi pada detik terakhir hari penutupan diterima, aksi satu detik setelahnya ditolak |
| Otomasi | Pest Feature (`travelTo`) |

#### TS-SIA-11 — Revisi KRS oleh dosen wali dan pengajuan ulang

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-KRS`, `BR-SIA-WAL` |
| Prioritas · Jenis · Status | Tinggi · Positif · (Parsial: approve/reject ada, status `revision_requested` baru) |
| Kondisi awal | KRS `submitted` milik mahasiswa perwalian `wali1` |
| Langkah | 1. `wali1` meminta revisi dengan catatan (`POST /krs-submissions/{id}/reject` saat ini; target `/advising/krs-submissions/{id}/...`). 2. Mahasiswa mengubah item dan mengajukan ulang. 3. `wali1` menyetujui. 4. Dosen wali lain mencoba menyetujui KRS yang sama |
| Hasil diharapkan | Status `submitted → revision_requested → submitted → approved`. Catatan revisi terlihat oleh mahasiswa. Item berubah menjadi `enrolled` setelah disetujui |
| Kriteria lulus | Setiap transisi menambah baris `krs_submission_events` (aktor, waktu, catatan). Revisi tanpa catatan → 422. Langkah 4 → 404 (mahasiswa di luar scope perwalian). Menyetujui KRS yang sudah diputuskan → 409. Data lama berstatus `rejected` tampil sebagai `revision_requested` setelah migrasi |
| Otomasi | Pest Feature |

#### TS-SIA-12 — Penguncian KRS dan perubahan sesudahnya

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-KRS` ([OD-09](16-open-decisions.md#od-09)) |
| Prioritas · Jenis · Status | Tinggi · Tepi · (Baru) |
| Kondisi awal | KRS `approved`. Periode KRS berakhir, KRS dikunci (`locked`) oleh job atau BAAK. Periode perubahan (add/drop) dikonfigurasi atau tidak |
| Langkah | 1. Mahasiswa menambah item setelah `locked` tanpa periode perubahan. 2. Dengan periode perubahan terbuka, mahasiswa mengajukan perubahan. 3. Jumlah perubahan melewati batas terkonfigurasi |
| Hasil diharapkan | Langkah 1 ditolak. Langkah 2 membuat permintaan perubahan yang memerlukan persetujuan dosen wali dan tidak mengubah item sebelum disetujui. Langkah 3 ditolak |
| Kriteria lulus | Langkah 1: 409 `KRS_LOCKED`. Langkah 2: event `change_requested` di `krs_submission_events`, item lama tetap `enrolled` sampai disetujui. Riwayat perubahan dapat ditampilkan kronologis |
| Otomasi | Pest Feature |

#### TS-SIA-13 — Paket KRS otomatis mahasiswa semester awal

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-KRS`, `BR-SIA-KUR` |
| Prioritas · Jenis · Status | Sedang · Positif · (Baru) |
| Kondisi awal | `course_packages` untuk P1 angkatan 2026 semester 1 memuat 8 mata kuliah. 40 mahasiswa baru aktif |
| Langkah | BAAK menjalankan penerapan paket untuk angkatan. Ulangi penerapan |
| Hasil diharapkan | Setiap mahasiswa mendapat item sesuai paket ke kelas yang ditentukan. Penerapan kedua tidak menggandakan item |
| Kriteria lulus | Jumlah item = 40 × 8. Penerapan ulang idempoten (jumlah tetap). Mahasiswa bercekal dilewati dan dilaporkan di ringkasan hasil, tidak menggagalkan seluruh batch |
| Otomasi | Pest Feature |

### 4.2 Kelas, jadwal, dan kurikulum

#### TS-SIA-14 — Validasi bentrok dosen dan ruang saat penjadwalan

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-JDW` |
| Prioritas · Jenis · Status | Tinggi · Negatif · (Parsial: bentrok dosen/ruang berbasis teks ruang sudah ada; `rooms` baru) |
| Kondisi awal | `dosen1` mengajar Senin 08:00–10:30 di ruang R-301. Ruang R-301 berkapasitas 40 |
| Langkah | 1. Jadwalkan kelas lain untuk `dosen1` Senin 09:00. 2. Jadwalkan kelas lain di R-301 Senin 10:00. 3. Jadwalkan kelas berkapasitas 60 di R-301. 4. Jadwalkan praktikum kelompok A dan B dari kelas yang sama pada jam yang sama di dua ruang berbeda |
| Hasil diharapkan | Langkah 1 dan 2 ditolak dengan rincian bentrok. Langkah 3 memberi peringatan kapasitas ruang (atau ditolak sesuai konfigurasi). Langkah 4 diterima karena `group_label` berbeda dan ruang berbeda |
| Kriteria lulus | Langkah 1: 409 `LECTURER_CONFLICT`, langkah 2: 409 `ROOM_CONFLICT`, keduanya dengan `errors.conflicts[]`. Team teaching: bentrok diperiksa untuk **setiap** dosen di `class_section_lecturers` |
| Otomasi | Pest Feature + Unit |

#### TS-SIA-15 — Publikasi dan perubahan jadwal memicu notifikasi

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-JDW` |
| Prioritas · Jenis · Status | Sedang · Positif · (Baru) |
| Kondisi awal | Jadwal `draft`, kelas sudah memiliki 25 peserta `enrolled` |
| Langkah | 1. Publikasikan jadwal. 2. Ubah ruang dan jam jadwal yang `published` |
| Hasil diharapkan | Mahasiswa hanya melihat jadwal `published`. Perubahan dicatat sebagai riwayat dan memicu notifikasi ke peserta dan dosen |
| Kriteria lulus | Sebelum publikasi, `GET /student/schedule` tidak memuat jadwal tersebut. Setelah perubahan, 25 notifikasi + dosen tercatat (`Notification::fake`). Riwayat menyimpan nilai lama dan baru. Peserta yang menjadi bentrok akibat perubahan dilaporkan di `warnings.student_conflicts` |
| Otomasi | Pest Feature |

#### TS-SIA-16 — Angkatan berbeda memakai versi kurikulum berbeda

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-KUR` ([OD-03](16-open-decisions.md#od-03)) |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru: `curriculum_courses`, `curriculum_cohorts`) |
| Kondisi awal | P1 angkatan 2020 → kurikulum 2020, angkatan 2024 → kurikulum 2024. *Algoritma* wajib di 2020 dan pilihan di 2024. Ekuivalensi: *Pemrograman Dasar* (2020) ≡ *Dasar Pemrograman* (2024) |
| Langkah | 1. Tampilkan struktur kurikulum untuk mahasiswa 2020 dan 2024. 2. Mahasiswa 2020 yang mengulang mengambil *Dasar Pemrograman*. 3. Kurikulum 2024 direvisi (menambah MK) |
| Hasil diharapkan | Masing-masing angkatan melihat sifat wajib/pilihan sesuai versinya. Nilai *Dasar Pemrograman* diakui sebagai pemenuhan *Pemrograman Dasar* untuk mahasiswa 2020. Revisi 2024 tidak mengubah transkrip dan struktur mahasiswa 2020 |
| Kriteria lulus | Transkrip dan perhitungan kelulusan mahasiswa 2020 tidak berubah setelah revisi. Riwayat perubahan kurikulum tercatat di `audit_logs`. Mengubah kurikulum berstatus terpakai tanpa permission `curriculums.update` → 403 |
| Otomasi | Pest Feature |

### 4.3 Presensi dan jurnal mengajar

#### TS-SIA-17 — Mengisi presensi per sesi pertemuan

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-PRS` |
| Prioritas · Jenis · Status | Tinggi · Positif · (Parsial: `POST /class-sections/{id}/attendances` ada, `lecture_sessions` baru) |
| Kondisi awal | Kelas K1 diampu `dosen1`, 30 peserta `enrolled`, sesi pertemuan ke-5 `scheduled` |
| Langkah | 1. Dosen membuka sesi (status `ongoing`), mengisi jurnal (topik, materi, metode). 2. Mengisi presensi 30 peserta (hadir/izin/sakit/alpa). 3. Menutup sesi (`held`). 4. Mahasiswa melihat rekap presensi |
| Hasil diharapkan | Presensi tersimpan atomik per sesi. Rekap per mahasiswa dan per kelas memperbarui persentase |
| Kriteria lulus | 30 baris `attendances` dengan `lecture_session_id` dan `method = manual`, `recorded_by` = user dosen. Pengiriman ulang memperbarui baris yang sama (unik per `krs_item` × sesi). Peserta `dropped` ditolak (422/409). Dosen yang tidak mengampu K1 → 403/404 saat `academic.lecturer_ownership` aktif |
| Otomasi | Pest Feature (`AttendanceRecordingTest` diperluas) |

#### TS-SIA-18 — Koreksi presensi berotorisasi

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-PRS`, `BR-SEC-AUD`, `BR-SEC-APV` |
| Prioritas · Jenis · Status | Tinggi · Positif/Negatif · (Baru) |
| Kondisi awal | Sesi ke-5 `held` lebih dari batas waktu koreksi langsung (contoh 2×24 jam, terkonfigurasi). Mahasiswa tercatat `absent` |
| Langkah | 1. Dosen mengubah langsung status presensi. 2. Dosen mengajukan koreksi dengan alasan dan bukti. 3. Kaprodi/BAAK menyetujui. 4. Mahasiswa melihat riwayat |
| Hasil diharapkan | Langkah 1 ditolak setelah batas waktu. Koreksi diproses melalui persetujuan. Setelah disetujui, status presensi berubah dan persentase dihitung ulang |
| Kriteria lulus | Langkah 1: 409. `attendance_corrections` berstatus disetujui dengan `old_status`, `new_status`, alasan, penyetuju. `audit_logs` mencatat perubahan. Koreksi tanpa alasan → 422. Penyetuju = pengaju → ditolak |
| Otomasi | Pest Feature |

#### TS-SIA-19 — Batas kehadiran minimum untuk ujian

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-PRS`, `BR-SIA-CKL` ([OD-06](16-open-decisions.md#od-06)) |
| Prioritas · Jenis · Status | Tinggi · Tepi · (Baru) |
| Kondisi awal | Kehadiran minimum ujian 75% (contoh, `university_settings` grup `academic`). 16 sesi `held`. Mahasiswa A hadir 12 (75,00%), mahasiswa B hadir 11 (68,75%). Konfigurasi izin/sakit dihitung hadir = tidak |
| Langkah | 1. A dan B memulai UAS. 2. Ubah konfigurasi menjadi izin/sakit dihitung hadir; B memiliki 1 izin. 3. B memulai UAS lagi |
| Hasil diharapkan | A boleh (tepat di batas). B ditolak dengan informasi persentase. Setelah konfigurasi diubah, B (12/16) boleh |
| Kriteria lulus | A: 201. B langkah 1: 409 `ATTENDANCE_BELOW_MINIMUM` (atau `ACADEMIC_BLOCKED` kategori kehadiran sesuai desain 10). Sesi `cancelled` tidak masuk penyebut. Kelas tanpa sesi `held` → persentase `null`, tidak memblokir |
| Otomasi | Pest Unit (kalkulator persentase) + Feature |

#### TS-SIA-20 — Jurnal mengajar terhadap RPS (rencana vs realisasi)

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-PRS`, `BR-OBE-ART` |
| Prioritas · Jenis · Status | Sedang · Positif · (Baru) |
| Kondisi awal | RPS mata kuliah K1 `ratified` dengan 16 `lesson_plan_meetings`. 10 sesi `held` dengan jurnal |
| Langkah | Kaprodi membuka laporan realisasi perkuliahan K1 |
| Hasil diharapkan | Laporan menampilkan per pertemuan: topik rencana, topik realisasi, status (sesuai, berbeda, belum terlaksana) |
| Kriteria lulus | Persentase realisasi = sesi `held` yang tertaut pertemuan RPS ÷ pertemuan RPS. Kelas tanpa RPS menampilkan status "RPS belum disahkan", bukan error |
| Otomasi | Pest Feature |

### 4.4 Penilaian dan hasil studi

#### TS-SIA-21 — Menghitung nilai akhir dari komponen

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-NIL` ([OD-04](16-open-decisions.md#od-04), [OD-29](16-open-decisions.md#od-29)) |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru: `assessment_components`, `assessment_scores`, `grade_scales`) |
| Kondisi awal | Komponen K1 (contoh): Tugas 20%, Kuis 10%, UTS 30%, UAS 40% (total 100%). Skala nilai tenant: A ≥ 85, AB 75–84,99, B 65–74,99, … (contoh). Pembulatan: 2 desimal, *half up* sebelum konversi (contoh) |
| Langkah | Dosen menginput skor mahasiswa: Tugas 80, Kuis 90, UTS 70, UAS 84,99. Sistem menghitung nilai akhir |
| Hasil diharapkan | Nilai akhir = 0,2×80 + 0,1×90 + 0,3×70 + 0,4×84,99 = 79,996 → 80,00 → AB |
| Kriteria lulus | `grades.final_score = 80.00`, `grade_scale_item_id` = AB, `grade_point` sesuai skala. Mengganti aturan pembulatan menjadi *truncate* menghasilkan 79,99 → AB (tidak berubah) dan nilai uji lain di batas 84,995 menghasilkan huruf berbeda sesuai aturan. Total bobot ≠ 100% → simpan komponen ditolak 422 `WEIGHT_TOTAL_INVALID` |
| Otomasi | Pest Unit (kalkulator, kasus batas setiap ambang) + Feature |

#### TS-SIA-22 — Nilai kosong dan kelengkapan sebelum pengajuan

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-NIL` |
| Prioritas · Jenis · Status | Tinggi · Negatif · (Baru) |
| Kondisi awal | Dari 30 peserta, 2 belum memiliki skor UAS |
| Langkah | Dosen mengajukan lembar nilai kelas (`grade_submissions` → `submitted`) |
| Hasil diharapkan | Ditolak dengan daftar mahasiswa yang belum lengkap, kecuali komponen ditandai boleh kosong atau mahasiswa diberi status khusus sesuai konfigurasi (mis. nilai tunda) |
| Kriteria lulus | 409 `GRADE_INCOMPLETE` dengan `errors.missing_count = 2` dan `errors.missing[]` berisi 2 `krs_item_id`. Skor kosong **tidak** diperlakukan sebagai 0 secara diam-diam. Skor di luar 0–100 → 422 |
| Otomasi | Pest Feature |

#### TS-SIA-23 — Mengubah nilai setelah publikasi

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-NIL`, `BR-SEC-AUD` ([OD-07](16-open-decisions.md#od-07), [OD-08](16-open-decisions.md#od-08)) |
| Prioritas · Jenis · Status | Tinggi · Negatif/Positif · (Baru) |
| Kondisi awal | Lembar nilai K1 `published`. Mahasiswa X bernilai B |
| Langkah | 1. Dosen mengirim `PUT /krs-items/{id}/grade` langsung. 2. Dosen mengajukan `grade_revisions` (`revision_type = correction`) dengan alasan. 3. Kaprodi menyetujui. 4. Mahasiswa melihat KHS dan transkrip |
| Hasil diharapkan | Perubahan langsung ditolak. Revisi melalui persetujuan mengubah nilai, menghitung ulang IPS/IPK, dan memberi notifikasi |
| Kriteria lulus | Langkah 1: 409 `GRADE_PUBLISHED`. Langkah 3: `grade_revisions` `requested → approved → applied`; `audit_logs` memuat nilai lama, nilai baru, alasan, penyetuju. IPS/IPK baru tercermin di `GET /student/transcript`. Revisi ganda yang masih `requested` untuk nilai yang sama → 409 |
| Otomasi | Pest Feature (`GradeRecordingTest` diperluas, regresi NIL-04 diubah dengan sengaja) |

#### TS-SIA-24 — Publikasi nilai dengan persetujuan Kaprodi

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-NIL` ([OD-07](16-open-decisions.md#od-07)) |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru) |
| Kondisi awal | Tenant mewajibkan persetujuan Kaprodi sebelum publikasi (konfigurasi) |
| Langkah | Dosen `submitted` → Kaprodi `in_review` → Kaprodi `published`. Varian: Kaprodi mengembalikan (`reopened`) |
| Hasil diharapkan | Mahasiswa hanya melihat nilai setelah `published`. Saat `reopened`, dosen dapat mengubah dan mengajukan ulang |
| Kriteria lulus | `GET /student/grades` sebelum publikasi tidak memuat nilai kelas. Setelah publikasi, `grades.is_published = true` dan notifikasi terkirim. Kaprodi prodi lain → 404. Tenant tanpa persetujuan: dosen dapat langsung `published` |
| Otomasi | Pest Feature |

#### TS-SIA-25 — Pemutihan (perbaikan) nilai dan IPK

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SIA-NIL`, `BR-SIA-IPK` ([OD-08](16-open-decisions.md#od-08)) |
| Prioritas · Jenis · Status | Sedang · Positif · (Baru; logika nilai terbaik untuk MK diulang sudah ada di `StudentGradeService`) |
| Kondisi awal | Mahasiswa bernilai D pada *Statistika* (3 SKS). Program pemutihan dibuka |
| Langkah | Ajukan `grade_revisions` `revision_type = remediation`, disetujui, nilai baru C |
| Hasil diharapkan | Transkrip memakai nilai sesuai kebijakan (terbaik/terakhir). Nilai lama tetap tersimpan di riwayat |
| Kriteria lulus | IPK dihitung ulang dengan satu kali hitung SKS (tidak ganda). Riwayat dapat ditampilkan. Kebijakan terbaik vs terakhir diambil dari konfigurasi |
| Otomasi | Pest Feature + Unit (`TranscriptTest` regresi) |

### 4.5 Perwalian, layanan akademik, dan data master — skenario pelengkap

| ID | Judul | Ref. aturan | Kondisi awal | Langkah | Hasil diharapkan | Kriteria lulus | Prio · Status |
|---|---|---|---|---|---|---|---|
| TS-SIA-26 | Dosen wali hanya melihat mahasiswa perwaliannya | `BR-SIA-WAL`, `BR-SEC-SCP` | `wali1` wali 15 mahasiswa | `GET /advising/students`; `GET /advising/students/{id_bukan_perwalian}` | Daftar berisi 15 mahasiswa | Jumlah = 15; akses mahasiswa lain → 404 | Tinggi · Baru |
| TS-SIA-27 | Catatan konsultasi perwalian | `BR-SIA-WAL` | Mahasiswa perwalian ada | Wali membuat `advising_records` (topik, catatan, tindak lanjut, visibilitas) | Catatan tersimpan; mahasiswa melihat catatan yang ditandai terlihat | Catatan privat tidak muncul di `GET /student/...`; BAAK dapat membaca sesuai matriks 11 | Sedang · Baru |
| TS-SIA-28 | Penanda mahasiswa butuh pendampingan | `BR-SIA-WAL` | Ambang (contoh): IPS < 2,00 atau kehadiran < 75% di ≥ 2 MK | Hitung indikator setelah nilai dipublikasikan | Mahasiswa yang memenuhi ambang ditandai beserta alasannya | Penanda dapat dijelaskan (kriteria mana yang terpenuhi); ambang dari konfigurasi; bukan label final tanpa tinjauan wali | Sedang · Baru |
| TS-SIA-29 | Mengganti dosen wali | `BR-SIA-WAL` | Mahasiswa dengan wali lama dan KRS `submitted` | `PATCH /students/{id}/academic-advisor` (Ada) | Antrian persetujuan pindah ke wali baru | Wali lama → 404 untuk KRS tersebut; riwayat penggantian tercatat di audit | Sedang · Ada/Parsial |
| TS-SIA-30 | Cuti studi melalui ApprovalWorkflow | `BR-SIA-CUT` | Mahasiswa `active`, workflow cuti aktif | `POST /student/requests {type: leave}` → submit → disetujui | Status mahasiswa `leave`, riwayat status tercatat | `student_status_histories` bertambah; KRS term cuti ditolak | Tinggi · Ada |
| TS-SIA-31 | Transfer masuk dan konversi nilai | `BR-SIA-TRF` | Mahasiswa pindahan dengan 40 SKS dari PT asal | Buat `student_transfers` + `credit_conversions` (`source_type = transfer`) per MK | MK yang diakui masuk transkrip dengan penanda konversi | Total SKS diakui ≤ batas terkonfigurasi; konversi disetujui Kaprodi; konversi `applied` tidak dapat diubah tanpa revisi | Sedang · Baru |
| TS-SIA-32 | Double degree internal | `BR-SIA-TRF` ([OD-23](16-open-decisions.md#od-23)) | Mahasiswa P1 terdaftar program kedua P3 | Buat `double_degree_enrollments`, isi KRS di dua prodi | KRS terpisah per prodi; transkrip sesuai keputusan OD-23 | Batas SKS dihitung sesuai konfigurasi; tidak ada duplikasi `students` | Rendah · Baru |
| TS-SIA-33 | Alur tugas akhir dari judul sampai sidang | `BR-SIA-TA` | Mahasiswa memenuhi syarat SKS minimum (contoh) | Ajukan judul → penetapan 2 pembimbing → 8 bimbingan → seminar proposal → sidang → `completed` | Status `theses` mengikuti state machine kanon | Transisi ilegal (mis. `submitted → ready_for_defense`) → 409; jumlah bimbingan minimum (konfigurasi) dicek sebelum `ready_for_defense`; penguji ≠ pembimbing | Sedang · Parsial |
| TS-SIA-34 | Cekal yudisium dari perpustakaan/kemahasiswaan | `BR-SIA-CKL` ([OD-22](16-open-decisions.md#od-22)) | Tanggungan perpustakaan tercatat | Mahasiswa mengajukan yudisium | Ditolak dengan alasan cekal perpustakaan | 409 `ACADEMIC_BLOCKED`; layanan KRS tidak terpengaruh | Sedang · Baru |
| TS-SIA-35 | Pengumuman massal email & SMS | [01-siakad.md](01-siakad.md) A.9 (belum ber-ID) ([OD-24](16-open-decisions.md#od-24)) | 500 mahasiswa P1 aktif | BAAK mengirim pengumuman ke filter prodi P1 kanal email + SMS | Pengiriman diantrikan; status per penerima dapat dilihat | Jumlah notifikasi = 500 per kanal; mahasiswa P2 tidak menerima; tenant tanpa kredensial SMS → kanal SMS dilewati dengan peringatan | Sedang · Parsial |
| TS-SIA-36 | Membuat mahasiswa dengan NIM duplikat | `BR-SIA-MST` | NIM 2401001 sudah ada di U-A | `POST /students` NIM sama; NIM sama di U-B | Ditolak di U-A; diterima di U-B | U-A: 422; U-B: 201 (unik per tenant) | Tinggi · Ada |
| TS-SIA-37 | Homebase dan riwayat mengajar dosen | `BR-SIA-MST` | Dosen mengajar 3 term | Tampilkan riwayat mengajar | Riwayat diturunkan dari `class_section_lecturers` (fallback `class_sections.lecturer_id`) | Kelas team teaching muncul untuk setiap dosen; homebase dari `lecturers.study_program_id` | Rendah · Parsial |
| TS-SIA-38 | Titik sambung semester pendek | `BR-SIA-MST` | `academic_terms.term_type = short` | Batas SKS dan KRS mengikuti rancangan [semester-pendek](../semester-pendek/README.md) | Term reguler tidak terpengaruh | Regresi AC-xx semester pendek hijau | Sedang · Baru |
| TS-SIA-39 | Periode akademik mengikat proses | `BR-SIA-MST` | `academic_term_periods` dengan `period_type = grade_entry` berakhir kemarin | Dosen mengajukan nilai | Ditolak di luar periode (kecuali melalui revisi) | 409; periode diambil dari `academic_term_periods`, bukan `academic_calendar_events` | Sedang · Baru |
| TS-SIA-40 | Transkrip dan KHS | `BR-SIA-IPK` | Mahasiswa dengan 4 term bernilai, 1 MK diulang | `GET /student/transcript`, `GET /student/khs` | IPS per term dan IPK benar | Nilai belum `published` tidak dihitung; regresi `TranscriptTest` hijau | Tinggi · Ada |

---

## 5. EDLINK/LMS (TS-LMS)

#### TS-LMS-01 — Sinkronisasi kelas SIAKAD ke kelas akademik LMS

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-LMS-KLS` |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru: `lms_classes`) |
| Kondisi awal | Term berjalan memiliki 12 `class_sections` `open`. Kelas K1 memiliki 2 dosen (`class_section_lecturers`) dan 30 peserta `enrolled` |
| Langkah | 1. Event kelas dibuka/dibuat memicu pembentukan `lms_classes` (`class_type = academic`). 2. Mahasiswa baru di-`enrolled` ke K1. 3. Satu peserta `dropped`. 4. Event dikirim ulang (retry) |
| Hasil diharapkan | Satu `lms_classes` per `class_section`. Peserta dan pengajar **dibaca** dari `krs_items` dan `class_section_lecturers`, tidak disalin. Peserta baru langsung melihat kelas; peserta `dropped` kehilangan akses tulis tetapi riwayat kirimannya tetap |
| Kriteria lulus | `COUNT(lms_classes WHERE class_section_id = K1) = 1` setelah event ganda (idempoten). Tidak ada baris `lms_class_members` untuk kelas akademik. Peserta `dropped` → 403 saat mengumpulkan tugas; kiriman lama masih terlihat oleh dosen. Kegagalan pembentukan dicatat dan dapat dicoba ulang tanpa duplikasi |
| Otomasi | Pest Feature `app/Modules/Lms/Tests/Feature/AcademicClassSyncTest.php` |

#### TS-LMS-02 — Mengumpulkan tugas tepat waktu dan terlambat

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-LMS-TGS` |
| Prioritas · Jenis · Status | Tinggi · Positif/Tepi · (Ada untuk `assignments` berbasis `class_section_id`) |
| Kondisi awal | Tugas `is_published`, `due_at` = T, `allow_late_submission = false`, `allow_resubmission = true`, ekstensi `pdf`, ukuran maksimum 10 MB |
| Langkah | 1. Unggah PDF 9 MB pada T − 1 menit (`POST /file-uploads` lalu `POST /student/assignments/{id}/submission`). 2. Kirim ulang pada T − 10 detik. 3. Kirim pada T + 1 detik. 4. Unggah `.exe` dan PDF 11 MB. 5. Ulangi langkah 3 dengan `allow_late_submission = true` |
| Hasil diharapkan | Langkah 1–2 diterima, `submission_count` bertambah. Langkah 3 ditolak. Langkah 4 ditolak validasi. Langkah 5 diterima dengan `is_late = true` |
| Kriteria lulus | Langkah 1: 201, `assignment_submissions.submitted_at` ≤ T. Langkah 2: baris yang sama, `submission_count = 2`. Langkah 3: 409. Langkah 4: 422. Langkah 5: 201, `is_late = true`. Waktu pembanding adalah waktu server, bukan waktu klien. Mahasiswa lain tidak dapat melihat berkas kiriman (404) |
| Otomasi | Pest Feature (`StudentLearningTest` diperluas) |

#### TS-LMS-03 — Penilaian tugas dan pengiriman ke komponen nilai SIAKAD

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-LMS-TGS`, `BR-SIA-NIL` |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru: pemetaan ke `assessment_components`) |
| Kondisi awal | Komponen *Tugas* (20%) K1 dipetakan ke 3 tugas LMS (agregasi rata-rata, contoh). Dosen menilai seluruh kiriman |
| Langkah | 1. Dosen menekan "Kirim ke nilai SIAKAD". 2. Dosen mengubah nilai satu tugas setelah dikirim. 3. Lembar nilai sudah `published` lalu dosen mengirim ulang |
| Hasil diharapkan | Nilai masuk sebagai `assessment_scores` berstatus perlu validasi dosen sebelum dihitung. Perubahan di LMS tidak menimpa otomatis nilai yang sudah divalidasi |
| Kriteria lulus | `assessment_scores` per mahasiswa = rata-rata 3 tugas. Kiriman ulang memperbarui baris yang sama (idempoten). Langkah 3: 409 `GRADE_PUBLISHED`, harus lewat `grade_revisions`. Mahasiswa yang belum mengumpulkan diperlakukan sesuai konfigurasi (kosong, bukan 0, kecuali dikonfigurasi) |
| Otomasi | Pest Feature |

#### TS-LMS-04 — Presensi QR: token valid

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-LMS-QR`, `BR-SIA-PRS` |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru: `attendance_qr_tokens`) |
| Kondisi awal | Sesi K1 `ongoing`, mode hybrid. Masa berlaku token 30 detik dengan rotasi (contoh, terkonfigurasi). Mahasiswa peserta K1 memakai aplikasi Flutter |
| Langkah | 1. Dosen menampilkan QR. 2. Mahasiswa memindai dalam masa berlaku |
| Hasil diharapkan | Presensi `present` tercatat dengan `method = qr`. Layar dosen memperbarui jumlah hadir |
| Kriteria lulus | 201. `attendances.lecture_session_id` = sesi, `method = qr`, `recorded_by` = user mahasiswa. Token yang disimpan hanya hash. Peserta bukan anggota kelas → 404 |
| Otomasi | Pest Feature `app/Modules/Lms/Tests/Feature/QrAttendanceTest.php` + Flutter widget test pemindai |

#### TS-LMS-05 — Presensi QR: token kedaluwarsa

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-LMS-QR` |
| Prioritas · Jenis · Status | Tinggi · Negatif/Tepi · (Baru) |
| Kondisi awal | Token dibuat pada t0, masa berlaku 30 detik (contoh) |
| Langkah | Kirim token pada t0 + 29 detik, t0 + 30 detik, dan t0 + 31 detik (waktu server). Kirim token saat sesi sudah `held` |
| Hasil diharapkan | Diterima sebelum batas, ditolak pada dan setelah batas sesuai definisi inklusif/eksklusif di 10. Token sesi yang sudah ditutup selalu ditolak |
| Kriteria lulus | Ditolak dengan 409 `QR_TOKEN_EXPIRED`. Perbedaan jam perangkat tidak memengaruhi hasil (stempel waktu klien diabaikan). Toleransi latensi jaringan, bila ada, berasal dari konfigurasi dan diuji di batasnya |
| Otomasi | Pest Feature (`travelTo`) |

#### TS-LMS-06 — Presensi QR: token diputar ulang atau dibagikan

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-LMS-QR`, `BR-SEC-RTL` |
| Prioritas · Jenis · Status | Tinggi · Negatif · (Baru) |
| Kondisi awal | Token valid. Mahasiswa A sudah memindai |
| Langkah | 1. A mengirim token yang sama lagi. 2. Mahasiswa B (peserta kelas, tidak hadir) memakai foto QR yang dikirim A setelah token berotasi. 3. B mengirim token valid dari perangkat yang sama dengan A dalam rentang detik yang sama. 4. Pengirim mencoba 50 token acak dalam satu menit |
| Hasil diharapkan | Langkah 1 idempoten (tidak menambah baris, status tetap). Langkah 2 ditolak. Langkah 3 diterima tetapi ditandai untuk ditinjau dosen sesuai aturan anti-penyalahgunaan. Langkah 4 dibatasi laju |
| Kriteria lulus | Langkah 1: 200 dengan kode `ALREADY_RECORDED` (respons idempoten), jumlah baris tetap 1. Langkah 2: 409 `QR_TOKEN_EXPIRED`. Langkah 3: presensi tercatat dengan penanda tinjauan, dosen dapat mengoreksi melalui TS-SIA-18. Langkah 4: 429 setelah ambang terkonfigurasi |
| Otomasi | Pest Feature |

#### TS-LMS-07 — Kuota storage tenant

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-LMS-STO`, `BR-ENT-LIM` ([OD-20](16-open-decisions.md#od-20)) |
| Prioritas · Jenis · Status | Tinggi · Tepi · (Baru) |
| Kondisi awal | Limit `lms.storage_gb` tenant = 50 (contoh). Pemakaian (`SUM(file_uploads.size_bytes)` lingkup LMS) = 49,9 GB. Ambang peringatan 80% dan 95% (contoh) |
| Langkah | 1. Unggah 50 MB. 2. Unggah 200 MB. 3. Hapus materi 1 GB (soft delete) lalu unggah lagi |
| Hasil diharapkan | Peringatan dikirim saat melewati ambang. Unggahan yang membuat pemakaian > kuota ditolak sebelum berkas disimpan permanen |
| Kriteria lulus | Langkah 1: 201 dan notifikasi ambang ke `lms_administrator`. Langkah 2: 409 `STORAGE_QUOTA_EXCEEDED`, tidak ada berkas yatim di disk. Langkah 3 mengikuti kebijakan OD-20 (berkas terhapus lunak dihitung atau tidak). Pemakaian U-A tidak dipengaruhi berkas U-B |
| Otomasi | Pest Feature (`Storage::fake`) |

#### TS-LMS-08 — Kuota kelas umum per semester

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-LMS-KLS`, `BR-ENT-LIM` ([OD-27](16-open-decisions.md#od-27)) |
| Prioritas · Jenis · Status | Sedang · Tepi · (Baru) |
| Kondisi awal | Limit `lms.general_classes_per_term` = 10 (contoh). Sudah ada 9 kelas umum aktif term ini |
| Langkah | 1. Buat kelas umum ke-10. 2. Buat ke-11. 3. Arsipkan satu kelas, buat lagi. 4. Buat kelas akademik baru |
| Hasil diharapkan | Ke-10 diterima, ke-11 ditolak. Perilaku setelah arsip sesuai OD-27. Kelas akademik tidak terkena batas |
| Kriteria lulus | Langkah 2: 409 `GENERAL_CLASS_QUOTA_EXCEEDED`. Langkah 4: 201. Dua permintaan paralel pada posisi 9 → tepat satu berhasil (grup `mysql`) |
| Otomasi | Pest Feature |

### 5.1 Skenario pelengkap LMS

| ID | Judul | Ref. aturan | Kondisi awal | Langkah | Hasil diharapkan | Kriteria lulus | Prio · Status |
|---|---|---|---|---|---|---|---|
| TS-LMS-09 | Kelas umum dengan anggota non-KRS | `BR-LMS-KLS` | `lms_administrator` | Buat kelas umum, undang 20 anggota (`lms_class_members`) | Anggota dapat mengakses materi | Kelas umum tidak muncul di KRS/KHS; anggota bukan mahasiswa tidak mendapat akses SIAKAD | Sedang · Baru |
| TS-LMS-10 | Materi per pertemuan dan versi | `BR-LMS-MAT` | Materi pertemuan 3 sudah ada | Unggah versi baru | `course_material_versions` bertambah; `current_version_id` berganti | Versi lama dapat diunduh dosen; mahasiswa melihat versi terbaru; riwayat tidak hilang | Sedang · Baru |
| TS-LMS-11 | Duplikasi kelas dan bahan ajar ke term berikut | `BR-LMS-MAT` | K1 term lalu memiliki 14 materi, 3 tugas | Duplikasi ke K1 term ini | Materi & tugas tersalin sebagai draf, tanpa kiriman mahasiswa | Berkas tidak diduplikasi fisik (referensi `file_uploads` yang sama atau salinan sesuai desain 02); tanggal tenggat dikosongkan/disesuaikan | Sedang · Baru |
| TS-LMS-12 | Forum diskusi dan moderasi | `BR-LMS-FRM` | Kelas aktif | Mahasiswa membuat thread; dosen menyembunyikan post | Post tersembunyi tidak tampil bagi mahasiswa | Peserta kelas lain → 404; penyembunyian tercatat audit | Sedang · Baru |
| TS-LMS-13 | Pengumuman kelas | `BR-LMS-KLS` | Kelas 30 peserta | Dosen menerbitkan pengumuman | 30 notifikasi | Mahasiswa `dropped` tidak menerima | Rendah · Baru |
| TS-LMS-14 | Progres penyelesaian materi | `BR-LMS-ANL` | 10 materi | Mahasiswa membuka 6 dan menandai selesai 4 | Progres = sesuai definisi 02 (selesai, bukan sekadar dibuka) | `lms_material_progress` tercatat; membuka berkas tidak sama dengan menyelesaikan | Sedang · Baru |
| TS-LMS-15 | Dashboard tidak menyamakan login dengan pemahaman | `BR-LMS-ANL` | Mahasiswa sering login, tidak mengumpulkan tugas | Lihat dashboard dosen | Metrik keaktifan dan penyelesaian ditampilkan terpisah | Tidak ada metrik "pemahaman" yang dihitung dari jumlah login | Sedang · Baru |
| TS-LMS-16 | Student Engagement Score sesuai paket | `BR-LMS-ANL`, `BR-ENT-PKG` | Tenant tanpa `lms.engagement_score` | Buka dashboard | Kartu skor tidak tampil, API 403 | `FEATURE_NOT_ENTITLED`; tenant berhak melihat skor dengan rincian komponen | Sedang · Baru |
| TS-LMS-17 | Gamification: poin dan lencana | `BR-LMS-ANL` | `lms.gamification` aktif | Kumpulkan tugas tepat waktu | Poin masuk `lms_point_ledgers` (append-only) | Kiriman ulang tidak menggandakan poin; leaderboard hanya kelas sendiri | Rendah · Baru |
| TS-LMS-18 | Log aktivitas belajar append-only | `BR-LMS-ANL` | Aktivitas tercatat | Coba ubah/hapus `lms_activity_events` via API | Tidak ada endpoint ubah/hapus | 404/405 | Sedang · Baru |
| TS-LMS-19 | Sesi daring: tautan pertemuan dan materi pra/pasca kelas | `BR-LMS-QR` | Sesi mode daring | Dosen mengisi tautan dan materi pra-kelas | Peserta melihat tautan hanya selama jendela sesi (konfigurasi) | Non-peserta → 404 | Sedang · Baru |
| TS-LMS-20 | Arsip kelas | `BR-LMS-KLS` | Term selesai | Arsipkan kelas (`active → archived`) | Kelas baca-saja | Tulis materi/tugas/forum → 409; data tetap dapat diekspor | Sedang · Baru |
| TS-LMS-21 | Whitelabel tenant Premium/Enterprise | `BR-ENT-PKG` ([OD-25](16-open-decisions.md#od-25)) | Tenant berhak `lms.whitelabel` | Ubah nama, logo, warna | Tampil di web dan konfigurasi build APK | Tenant tanpa hak → 403; aset logo melalui `file_uploads` dengan validasi tipe | Rendah · Baru |
| TS-LMS-22 | Ekspor/impor kursus SPADA | `BR-LMS-KLS` ([OD-17](16-open-decisions.md#od-17)) | Format disepakati | Ekspor kelas | Berkas sesuai format | Kegagalan impor tidak membuat data setengah jadi (transaksional) | Rendah · Baru |

---

## 6. CBT & CBT AI (TS-CBT)

#### TS-CBT-01 — Ujian dengan timer dan auto-submit

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-CBT-ATT` |
| Prioritas · Jenis · Status | Tinggi · Positif/Tepi · (Ada: `ExamService::attemptDeadline()` dan `finalizeIfExpired()`; status target `auto_submitted` baru) |
| Kondisi awal | Ujian `duration_minutes = 60`, `ends_at` = 10:30. Mahasiswa memulai pukul 10:00 |
| Langkah | 1. Mulai (`POST /student/exams/{id}/start`). 2. Jawab beberapa soal. 3. Simpan jawaban pukul 10:29:59. 4. Simpan jawaban pukul 10:30:01. 5. Buka percobaan setelah 10:30 tanpa menekan submit |
| Hasil diharapkan | Batas efektif = yang lebih awal antara `started_at + durasi` (11:00) dan `ends_at` (10:30), yaitu 10:30. Jawaban sebelum batas tersimpan. Setelah batas, percobaan difinalkan dan dinilai |
| Kriteria lulus | Langkah 3: 200. Langkah 4: 409 `ATTEMPT_CLOSED`, jawaban tidak tersimpan. Langkah 5: status `submitted` (saat ini) atau `auto_submitted` (target), `submitted_at` = batas efektif, skor terhitung dari jawaban yang tersimpan. Sisa waktu yang dikirim server ke klien dihitung dari waktu server |
| Otomasi | Pest Feature (`ExamAttemptTest`, `StudentExamParticipationTest`) |

#### TS-CBT-02 — Koneksi putus saat ujian lalu tersambung kembali

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-CBT-ATT` |
| Prioritas · Jenis · Status | Tinggi · Ketahanan · (Parsial: jawaban disimpan per soal; antrean luring di Flutter baru) |
| Kondisi awal | Percobaan `in_progress`, 20 soal, 8 terjawab dan tersimpan di server. Aplikasi Flutter |
| Langkah | 1. Matikan jaringan. 2. Jawab soal 9–12 (tersimpan lokal). 3. Pulihkan jaringan sebelum batas waktu. 4. Tutup aplikasi lalu buka lagi dari perangkat yang sama |
| Hasil diharapkan | Klien menampilkan status "tidak tersambung" dan menyimpan jawaban di antrean lokal. Setelah tersambung, antrean dikirim berurutan dengan stempel urutan. Percobaan dilanjutkan dengan sisa waktu dari server |
| Kriteria lulus | Setelah langkah 3, server memiliki 12 jawaban. Pengiriman ganda jawaban yang sama idempoten. Urutan ditentukan `client_seq`, bukan waktu tiba: jawaban dengan `client_seq` lebih lama dari yang tersimpan ditolak 409 `ANSWER_OUT_OF_ORDER`. Timer **tidak berhenti** selama putus koneksi. Langkah 4: `GET /student/exam-attempts/{id}` mengembalikan jawaban tersimpan dan sisa waktu yang benar |
| Otomasi | Flutter integration test `mobile/integration_test/exam_offline_test.dart` (jaringan disimulasikan) + Pest Feature idempotensi jawaban |

#### TS-CBT-03 — Waktu habis saat peserta luring

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-CBT-ATT` |
| Prioritas · Jenis · Status | Tinggi · Tepi · (Parsial) |
| Kondisi awal | Seperti TS-CBT-02, tetapi jaringan baru pulih 5 menit setelah batas efektif. Jawaban 9–12 dibuat lokal sebelum batas |
| Langkah | 1. Jaringan pulih. 2. Klien mengirim antrean jawaban. 3. Klien mengirim submit |
| Hasil diharapkan | Server sudah memfinalkan percobaan saat batas tercapai (lazy saat akses berikutnya). Jawaban yang tiba setelah batas **ditolak** walaupun stempel lokal sebelum batas, karena waktu klien tidak dipercaya. Peserta melihat pesan bahwa waktu habis dan jawaban yang dinilai adalah yang sudah diterima server |
| Kriteria lulus | Langkah 2: 409 `ATTEMPT_CLOSED` untuk setiap jawaban, tidak ada perubahan `exam_attempt_answers`. Langkah 3: 200 idempoten atau 409 "sudah dikumpulkan", status tetap final. Skor = berdasarkan 8 jawaban. Pengecualian (perpanjangan waktu karena gangguan massal) hanya melalui aksi pengawas/panitia yang tercatat audit — lihat TS-CBT-12 |
| Otomasi | Flutter integration test + Pest Feature (`travel`) |

#### TS-CBT-04 — Membuat draf soal dengan CBT AI

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-CBT-AI` ([OD-11](16-open-decisions.md#od-11)) |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru: `ai_generation_jobs`, `ai_question_drafts`) |
| Kondisi awal | Tenant berhak `cbt.ai`. Dosen memiliki `ai_question_generation.create`. Materi pertemuan 1–7 tersedia. Penyedia AI di-*mock* |
| Langkah | 1. Kirim permintaan: topik, materi sumber, tingkat kesulitan sedang, 10 soal pilihan ganda, dengan kunci & pembahasan. 2. Pantau status job. 3. Penyedia AI gagal/timeout pada percobaan pertama |
| Hasil diharapkan | Job diantrikan dan tidak memblokir UI. Hasil berupa 10 `ai_question_drafts` berstatus draf yang tertaut ke job. Kegagalan sementara dicoba ulang sesuai kebijakan; kegagalan permanen ditandai gagal dengan pesan yang dapat dipahami |
| Kriteria lulus | 202 saat pengajuan dengan `data.job_id`. Setelah job selesai: 10 draf, **0** baris baru di `question_bank_items` berstatus `published` dan **0** baris di `exam_questions`. Permintaan ganda dengan `Idempotency-Key` yang sama tidak membuat job kedua. Isi materi yang dikirim ke penyedia tercatat ringkas di log job tanpa data pribadi mahasiswa |
| Otomasi | Pest Feature `app/Modules/Cbt/Tests/Feature/AiQuestionGenerationTest.php` (`Http::fake`, `Queue::fake`) |

#### TS-CBT-05 — Draf AI tidak dapat dipublikasikan tanpa peninjauan

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-CBT-AI`, `BR-CBT-BNK` |
| Prioritas · Jenis · Status | Tinggi · Negatif · (Baru) |
| Kondisi awal | 10 draf hasil TS-CBT-04. Ujian `active` sedang berlangsung |
| Langkah | 1. Panggil `POST /exams/{id}/questions/apply-bank` dengan ID `ai_question_drafts`. 2. Dosen menyunting draf (`PUT /ai-question-drafts/{id}`) lalu menyetujui (`POST /ai-question-drafts/{id}/approve`). 3. Panggil `apply-bank` dengan butir bank hasil langkah 2 yang masih `draft`/`in_review`. 4. Penyusun butir menyetujui tinjauan butirnya sendiri. 5. Pemeriksa lain menyetujui → `published`. 6. Tambahkan butir hasil langkah 5 ke ujian yang sudah `active` |
| Hasil diharapkan | Draf AI hanya dapat menjadi soal bank melalui tinjauan manusia. Penyusun tidak menyetujui hasilnya sendiri (bila pemisahan peran dikonfigurasi). Soal tidak pernah masuk langsung ke ujian aktif |
| Kriteria lulus | Tidak ada endpoint yang menerbitkan draf AI langsung sebagai `published` atau memasukkannya ke ujian. Langkah 1: 404/422 (ID draf bukan `question_bank_items`). Langkah 2: butir baru di `question_bank_items` berstatus `draft`/`in_review` (tidak pernah `published`), penanda asal AI dan penyetuju tersimpan, audit tercatat. Langkah 3: 409 `INVALID_STATE_TRANSITION`. Langkah 4: 403/409 bila pemisahan peran dikonfigurasi. Langkah 5: `status = published`. Langkah 6: 409 (soal ujian `active` tidak dapat diubah) |
| Otomasi | Pest Feature |

#### TS-CBT-06 — Pengiriman nilai ujian ke komponen nilai SIAKAD

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-CBT-NIL`, `BR-SIA-NIL` |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru: `assessment_scores`; `weighted_score` sudah ada) |
| Kondisi awal | UTS K1 `graded`, terpetakan ke komponen *UTS* (30%). 30 percobaan dinilai, 1 percobaan `voided` karena pelanggaran |
| Langkah | 1. Panitia/dosen mempublikasikan hasil dan mengirim nilai. 2. Kirim ulang. 3. Satu esai dinilai ulang setelah dikirim |
| Hasil diharapkan | Nilai masuk ke `assessment_scores` komponen UTS untuk 29 mahasiswa. Percobaan `voided` tidak mengirim nilai; mahasiswa tersebut ditandai perlu keputusan dosen |
| Kriteria lulus | 29 baris; kiriman ulang tidak menambah baris. Langkah 3 memperbarui skor hanya jika lembar nilai belum `published`, bila sudah → melalui `grade_revisions`. Ujian dengan beberapa percobaan memakai kebijakan percobaan (terbaik/terakhir) dari konfigurasi ujian |
| Otomasi | Pest Feature |

#### TS-CBT-07 — Mencegah duplikasi ujian untuk konteks yang sama

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-CBT-UJN` |
| Prioritas · Jenis · Status | Sedang · Negatif · (Baru) |
| Kondisi awal | UAS K1 sudah ada (`exam_type = final`, `context_type = class_section`) |
| Langkah | 1. Buat UAS kedua untuk K1. 2. Kuis LMS membuat ujian CBT dua kali karena retry |
| Hasil diharapkan | UAS kedua ditolak kecuali ditandai ujian susulan/remedial. Retry dari LMS idempoten |
| Kriteria lulus | Langkah 1: 409 `EXAM_DUPLICATE`. Langkah 2: satu `exams` saja (kunci idempotensi dari referensi kuis LMS) |
| Otomasi | Pest Feature |

### 6.1 Skenario pelengkap CBT

| ID | Judul | Ref. aturan | Kondisi awal | Langkah | Hasil diharapkan | Kriteria lulus | Prio · Status |
|---|---|---|---|---|---|---|---|
| TS-CBT-08 | Status bank soal draf → ditinjau → dipublikasikan | `BR-CBT-BNK` | Penyusun & pemeriksa berbeda | Buat, kirim tinjau, setujui | `question_bank_items.status` mengikuti state machine | Soal `draft`/`in_review` tidak dapat dipakai `apply-bank` (409); soal `retired` tidak muncul di pilihan baru tetapi tetap di ujian lama | Tinggi · Baru |
| TS-CBT-09 | Acak soal & opsi per peserta | `BR-CBT-UJN` | `randomize_questions`, `randomize_options` aktif, 2 peserta | Mulai ujian | Urutan berbeda per peserta, stabil saat dimuat ulang | `question_order`/`option_order` tersimpan di percobaan; regresi [../EXAM_RANDOMIZATION.md](../EXAM_RANDOMIZATION.md) hijau | Tinggi · Ada |
| TS-CBT-10 | Batas jumlah percobaan | `BR-CBT-ATT` | `max_attempts = 1` | Mulai percobaan kedua | Ditolak | 409; percobaan `in_progress` yang ada dilanjutkan, bukan dibuat baru | Tinggi · Ada |
| TS-CBT-11 | Peserta di luar daftar | `BR-CBT-UJN` | `exam_participants` ditetapkan eksplisit | Mahasiswa KRS yang tidak terdaftar mulai ujian | Ditolak | 404/403; peserta dengan cekal ujian aktif → 409 `ACADEMIC_BLOCKED` | Tinggi · Baru |
| TS-CBT-12 | Perpanjangan waktu oleh pengawas | `BR-CBT-UJN`, `BR-SEC-AUD` | Gangguan listrik ruang ujian | Pengawas memperpanjang 15 menit untuk 20 peserta dengan alasan | Batas efektif peserta tersebut bertambah | Audit `forced_override` dengan alasan; peserta lain tidak berubah; tanpa `exam_proctoring` → 403 | Sedang · Baru |
| TS-CBT-13 | Soal esai dinilai manual | `BR-CBT-NIL` ([OD-26](16-open-decisions.md#od-26)) | Ujian campuran PG + esai | Peserta kirim; dosen menilai esai | Status `submitted → graded` setelah semua esai dinilai | Skor PG otomatis, skor esai manual, skor total benar; nilai belum dikirim ke SIAKAD sebelum `graded` | Sedang · Baru |
| TS-CBT-14 | Pelanggaran pindah tab tercatat sebagai indikasi | `BR-CBT-PRC` | `tab_switch` aktif | Peserta pindah tab 3× | `exam_violations` 3 baris berurutan | `sequence_number` 1–3; penalti sesuai konfigurasi; tidak otomatis `voided` tanpa keputusan pengawas | Tinggi · Ada |
| TS-CBT-15 | Proctoring kamera: izin ditolak | `BR-CBT-PRC` ([OD-12](16-open-decisions.md#od-12)) | Ujian mewajibkan kamera, tenant berhak `lms.proctoring` | Peserta menolak izin kamera | Peserta tidak dapat mulai; instruksi ditampilkan | Tidak ada percobaan dibuat; kejadian dicatat; ujian tanpa wajib kamera tidak terpengaruh | Tinggi · Baru |
| TS-CBT-16 | Deteksi otomatis bukan bukti pasti | `BR-CBT-PRC` | Snapshot ditandai "wajah ganda" | Pengawas meninjau | Status kejadian menunggu tinjauan; keputusan manusia dicatat | Tidak ada perubahan skor/status percobaan tanpa keputusan pengawas; keputusan memuat alasan | Tinggi · Baru |
| TS-CBT-17 | Akses bukti proctoring | `BR-CBT-PRC`, `BR-SEC-PRV` | Snapshot tersimpan | Dosen non-pengawas, mahasiswa lain, pengawas ujian lain membuka | Hanya pengawas/panitia ujian itu yang dapat | Lainnya → 404; setiap akses tercatat `viewed_sensitive`; URL berkas bertanda tangan berumur pendek | Tinggi · Baru |
| TS-CBT-18 | Retensi bukti proctoring | `BR-CBT-PRC` ([OD-12](16-open-decisions.md#od-12)) | Masa retensi terkonfigurasi | Jalankan job retensi setelah masa lewat | Berkas bukti dihapus permanen; ringkasan event tetap | Berkas tidak ada di disk; kasus yang sedang disengketakan dikecualikan | Sedang · Baru |
| TS-CBT-19 | Ujian publik PMB dengan token akses | `BR-CBT-UJN` | Ujian publik dengan `access_token` | `POST /public/exams/{accessToken}/access` dengan NIM/nomor peserta | Sesi dibuat | Throttle 10/menit (Ada); token salah → 404; token dibuat ulang → token lama 404 | Tinggi · Ada |
| TS-CBT-20 | Peserta PMB melalui `exam_participants` | `BR-CBT-UJN` | Daftar peserta seleksi dari PMB | Sinkron peserta dua kali | Tidak ada duplikasi peserta | Unik per ujian × identitas peserta | Sedang · Baru |
| TS-CBT-21 | Ujian aktif tidak dapat diubah soalnya | `BR-CBT-UJN` | `exams.status = active` | Ubah soal/opsi/kunci | Ditolak | 409; perubahan kunci setelah `finished` hanya melalui penilaian ulang tercatat audit | Tinggi · Parsial |
| TS-CBT-22 | Ujian dari kuis LMS | `BR-CBT-UJN`, `BR-ENT-PKG` | Tenant berhak `lms.cbt_integration` | Dosen membuat kuis LMS berbasis CBT | Kuis tampil di kelas LMS | Tenant tanpa hak → kuis LMS sederhana saja, opsi CBT tersembunyi, API 403 | Sedang · Baru |
| TS-CBT-23 | Ujian di kalender akademik | `BR-CBT-UJN` | Periode UTS di `academic_term_periods` | Jadwalkan ujian di luar periode | Peringatan atau tolak sesuai konfigurasi | Ujian tampil di `GET /student/calendar` | Rendah · Baru |
| TS-CBT-24 | Sesi ujian diambil alih perangkat lain | `BR-CBT-ATT`, `BR-SEC-*` | Percobaan aktif di perangkat A | Login perangkat B dan buka percobaan | Sesuai kebijakan: B ditolak atau A diputus dan kejadian dicatat | `session_token_hash` hanya valid untuk satu sesi; kejadian masuk `exam_violations` | Tinggi · Parsial |

---

## 7. OBE (TS-OBE)

#### TS-OBE-01 — Mengukur ketercapaian CPMK dari komponen nilai

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-OBE-UKR`, `BR-OBE-MAP` ([OD-16](16-open-decisions.md#od-16)) |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru) |
| Kondisi awal | MK *Basis Data* memiliki CPMK-1 dan CPMK-2. `assessment_component_outcomes`: UTS → CPMK-1 (bobot 100%), Tugas → CPMK-1 (40%) dan CPMK-2 (60%), UAS → CPMK-2 (100%) (contoh). Ambang tercapai individu 60 (contoh). Lembar nilai `published` |
| Langkah | Kaprodi menjalankan perhitungan pengukuran untuk kelas tersebut |
| Hasil diharapkan | Skor CPMK per mahasiswa = rata-rata tertimbang skor komponen yang dipetakan. Persentase mahasiswa yang mencapai ambang per CPMK dihitung |
| Kriteria lulus | Hasil `outcome_measurements` sama dengan perhitungan manual pada spreadsheet uji (toleransi 0,01). Komponen tanpa pemetaan tidak ikut. Mahasiswa dengan skor komponen kosong dikecualikan dari penyebut dan dilaporkan jumlahnya. Snapshot menyimpan versi metode dan parameter yang dipakai |
| Otomasi | Pest Unit (kalkulator, dataset fixture) + Feature |

#### TS-OBE-02 — Agregasi CPMK ke CPL dengan beberapa metode

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-OBE-UKR` ([OD-16](16-open-decisions.md#od-16)) |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru) |
| Kondisi awal | CPL-3 didukung CPMK dari 3 MK (bobot SKS 2, 3, 4). Target CPL-3 = 70% mahasiswa mencapai ≥ 60 (contoh) |
| Langkah | Hitung dengan metode (a) rata-rata tertimbang SKS, (b) rata-rata sederhana, (c) nilai minimum. Bandingkan dengan target |
| Hasil diharapkan | Tiga hasil berbeda sesuai rumus terdokumentasi di [04-obe.md](04-obe.md). Status tercapai/belum ditentukan terhadap target |
| Kriteria lulus | Tiap metode cocok dengan perhitungan manual. Mengganti metode tidak menimpa snapshot lama (snapshot baru dibuat). Laporan menampilkan metode yang dipakai |
| Otomasi | Pest Unit |

### 7.1 Skenario pelengkap OBE

| ID | Judul | Ref. aturan | Kondisi awal | Langkah | Hasil diharapkan | Kriteria lulus | Prio · Status |
|---|---|---|---|---|---|---|---|
| TS-OBE-03 | CPL tanpa MK pendukung | `BR-OBE-MAP` | CPL-7 tanpa pemetaan MK | Buka matriks CPL–MK | CPL-7 ditandai "tidak didukung" | Muncul di daftar peringatan; pengesahan kurikulum dapat diblokir sesuai konfigurasi | Tinggi · Baru |
| TS-OBE-04 | Pengesahan CPL membekukan versi | `BR-OBE-ART`, `BR-SEC-AUD` | CPL `in_review` | Kaprodi mengesahkan (`ratified`) lalu mencoba mengubah teks | Perubahan ditolak; revisi membuat versi baru | 409 untuk ubah langsung; revisi → versi lama `superseded`; audit mencatat pengesah | Tinggi · Baru |
| TS-OBE-05 | Profil lulusan ↔ CPL | `BR-OBE-MAP` | 3 profil, 9 CPL | Petakan; hapus profil yang terpetakan | Penghapusan ditolak bila sudah `ratified` | 409; riwayat pemetaan tersimpan | Sedang · Baru |
| TS-OBE-06 | RPS: penyusunan dan pengesahan | `BR-OBE-ART` | MK dengan 3 CPMK | Susun 16 pertemuan, metode asesmen, tautkan asesmen ke CPMK, ajukan, sahkan | `lesson_plans` `ratified` | Pertemuan tanpa CPMK → peringatan; total bobot asesmen = 100% atau ditolak 422 | Tinggi · Baru |
| TS-OBE-07 | Sub-CPMK opsional | `BR-OBE-MAP` | CPMK-1 dengan 2 Sub-CPMK | Petakan komponen ke Sub-CPMK | Skor CPMK = agregasi Sub-CPMK | `parent_id` benar; tanpa Sub-CPMK tetap berfungsi | Sedang · Baru |
| TS-OBE-08 | Rekap ketercapaian per mahasiswa, MK, prodi | `BR-OBE-UKR` | Snapshot tersedia | Buka tiga rekap | Angka konsisten antartingkat | Jumlah mahasiswa per MK = peserta dengan nilai `published` | Sedang · Baru |
| TS-OBE-09 | Analisis area belum tercapai & tindak lanjut | `BR-OBE-UKR` | CPL-3 di bawah target | Buat `outcome_evaluation_reports` → `evaluated` → `outcome_followups` → `followed_up` | Riwayat evaluasi tercatat | Transisi mengikuti state machine; tindak lanjut wajib penanggung jawab dan tenggat | Sedang · Baru |
| TS-OBE-10 | Ekspor laporan akreditasi inti | `BR-OBE-UKR` ([OD-19](16-open-decisions.md#od-19)) | Data lengkap | Ekspor matriks CPL–MK, CPMK, ketercapaian | Berkas XLSX/PDF | Audit `exported`; format lembaga akreditasi khusus ditandai menunggu OD-19 | Sedang · Baru |
| TS-OBE-11 | Draf OBE AI hanya rekomendasi | `BR-OBE-AI` ([OD-11](16-open-decisions.md#od-11)) | Tenant berhak `obe.ai` | Minta draf CPMK & RPS | Draf tersimpan sebagai saran | Tidak ada artefak `ratified`/`in_review` dibuat otomatis; adopsi saran membuat artefak `draft` atas nama pengguna | Tinggi · Baru |
| TS-OBE-12 | OBE AI tanpa hak | `BR-ENT-PKG` | Tenant `obe` tanpa `obe.ai` | Panggil endpoint AI | Ditolak | 403 `FEATURE_NOT_ENTITLED` | Sedang · Baru |
| TS-OBE-13 | Nilai direvisi setelah pengukuran | `BR-OBE-UKR` | Snapshot ada, lalu `grade_revisions` `applied` | Jalankan ulang | Snapshot baru; snapshot lama tetap untuk jejak | Laporan menandai snapshot usang | Sedang · Baru |
| TS-OBE-14 | Jurnal mengajar vs RPS dalam laporan OBE | `BR-OBE-ART` | Lihat TS-SIA-20 | Rekap realisasi RPS per prodi | Persentase realisasi per MK | Konsisten dengan laporan SIAKAD | Rendah · Baru |
| TS-OBE-15 | Kaprodi hanya mengelola OBE prodinya | `BR-SEC-SCP` | `kaprodi.p1` | Ubah CPL P2 | Ditolak | 404 | Tinggi · Baru |

---

## 8. EDOM (TS-EDM)

#### TS-EDM-01 — Mengisi EDOM

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-EDM-ISI`, `BR-EDM-PRD` |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru; halaman `PortalLecturerEvaluationPage` saat ini mock) |
| Kondisi awal | `edom_periods` `open`. Formulir 12 pertanyaan (10 wajib skala 1–5 contoh, 2 opsional teks). Mahasiswa `enrolled` di 6 kelas; K1 diampu 2 dosen sehingga ada 7 `edom_targets` untuk mahasiswa ini. Aturan kelayakan: kehadiran ≥ 1 sesi (contoh) |
| Langkah | 1. `GET /student/edom` (daftar evaluasi wajib). 2. Simpan sebagian jawaban untuk target 1. 3. Kirim target 1 tanpa satu pertanyaan wajib. 4. Lengkapi lalu kirim. 5. Kirim lagi target 1 |
| Hasil diharapkan | Daftar menampilkan 7 target dengan status belum/sudah. Jawaban sementara dapat dilanjutkan. Kiriman tidak lengkap ditolak. Kiriman lengkap tercatat sekali |
| Kriteria lulus | Langkah 1: 7 item. Langkah 3: 422 dengan daftar pertanyaan wajib yang kosong. Langkah 4: 201, `edom_participations.status` sudah mengisi, satu `edom_responses` + 10–12 `edom_answers`. Langkah 5: 409 `EDOM_ALREADY_SUBMITTED`. Kelas yang tidak memenuhi kelayakan tidak muncul |
| Otomasi | Pest Feature `app/Modules/Edom/Tests/Feature/EdomSubmissionTest.php` |

#### TS-EDM-02 — EDOM anonim: tidak ada jalur penghubung identitas

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-EDM-PRV` ([OD-10](16-open-decisions.md#od-10)) |
| Prioritas · Jenis · Status | Tinggi · Privasi · (Baru) |
| Kondisi awal | Periode bermode anonim. 25 mahasiswa mengisi untuk `dosen1` K1 |
| Langkah | 1. **Inspeksi skema**: periksa kolom `edom_responses` dan `edom_answers`. 2. **Inspeksi korelasi**: bandingkan urutan dan stempel waktu `edom_participations` dengan `edom_responses`. 3. **Inspeksi API**: panggil semua endpoint laporan EDOM sebagai dosen, Kaprodi, LPM, admin tenant, dan `super_admin`. 4. **Inspeksi log**: cari ID respons di `audit_logs`, log aplikasi, dan payload job/queue. 5. **Inspeksi ekspor**: unduh ekspor mentah LPM |
| Hasil diharapkan | Tidak ada cara, dengan permission apa pun, untuk menautkan satu respons ke satu mahasiswa |
| Kriteria lulus | (1) `edom_responses` dan `edom_answers` **tidak** memiliki kolom `student_id`, `user_id`, `krs_item_id`, `edom_participation_id`, IP, atau user agent. (2) `edom_responses` tidak menyimpan stempel waktu presisi (dibulatkan, mis. ke tanggal) dan ID tidak berurutan terhadap partisipasi (ULID dibuat dengan waktu terbulat atau acak sesuai desain 05), sehingga urutan tidak dapat dikorelasikan. (3) Tidak ada endpoint yang mengembalikan respons individu bersama identitas; respons teks bebas hanya tampil teragregasi. (4) `audit_logs` untuk pengisian EDOM hanya mencatat partisipasi, tanpa ID respons; payload job tidak memuat keduanya bersamaan. (5) Ekspor mentah tanpa kolom identitas. Varian: periode **non-anonim** (bila dikonfigurasi OD-10) menampilkan pemberitahuan sebelum mengisi |
| Otomasi | Pest Feature: uji skema (`Schema::getColumnListing`) + uji API per role + uji log (`Log::spy`). Peninjauan kode wajib untuk setiap perubahan tabel `edom_*` |

#### TS-EDM-03 — EDOM sebagai syarat melihat nilai

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-EDM-ISI` ([OD-10](16-open-decisions.md#od-10)) |
| Prioritas · Jenis · Status | Tinggi · Tepi · (Baru) |
| Kondisi awal | Tenant mengaktifkan syarat EDOM sebelum melihat nilai. Mahasiswa sudah mengisi 6 dari 7 target. Nilai K1–K6 `published` |
| Langkah | 1. `GET /student/grades`. 2. Isi target terakhir. 3. Ulangi |
| Hasil diharapkan | Sebelum lengkap: nilai kelas yang EDOM-nya belum diisi disembunyikan (atau semua nilai, sesuai konfigurasi) dengan pesan arahan. Setelah lengkap: nilai tampil |
| Kriteria lulus | Langkah 1: respons memuat penanda `EDOM_REQUIRED` untuk kelas terkait tanpa nilai. Syarat ini **tidak** memengaruhi perhitungan IPK, transkrip resmi yang dicetak BAAK, atau akses orang tua yang diizinkan. Setelah periode `closed`, syarat berhenti berlaku sesuai konfigurasi |
| Otomasi | Pest Feature |

### 8.1 Skenario pelengkap EDOM

| ID | Judul | Ref. aturan | Kondisi awal | Langkah | Hasil diharapkan | Kriteria lulus | Prio · Status |
|---|---|---|---|---|---|---|---|
| TS-EDM-04 | Pembentukan target dari SIAKAD | `BR-EDM-PRD` | 12 kelas, 3 team teaching | Buka periode | Target dibentuk per kelas × dosen | Jumlah target = Σ dosen per kelas; dosen/kelas dibaca dari SIAKAD, tidak disalin | Tinggi · Baru |
| TS-EDM-05 | Formulir kustom & skala | `BR-EDM-PRD` | Formulir draf | Tambah kategori, pertanyaan wajib/opsional, skala 1–4 | Formulir tersimpan | Formulir yang sudah dipakai periode `open` tidak dapat diubah (409); salin untuk versi baru | Sedang · Baru |
| TS-EDM-06 | Periode ditutup | `BR-EDM-PRD` | Periode `closed` | Mahasiswa mengirim | Ditolak | 409 `EDOM_PERIOD_CLOSED`; draf jawaban tidak dihitung | Sedang · Baru |
| TS-EDM-07 | Dosen melihat rekap tanpa respons individu | `BR-EDM-REK`, `BR-EDM-PRV` | Periode `published` | Dosen membuka laporannya | Rata-rata per aspek, distribusi, komentar teragregasi | Tidak ada endpoint respons per baris untuk dosen; laporan dosen lain → 404 | Tinggi · Baru |
| TS-EDM-08 | Ambang minimum responden | `BR-EDM-PRV` | Ambang (contoh) 5 responden; kelas kecil 3 responden | Buka laporan kelas | Angka disembunyikan dengan pesan | Tidak dapat ditebak dari selisih laporan dosen − laporan kelas (agregasi tingkat atas juga menyembunyikan bila memungkinkan dedukasi) | Tinggi · Baru |
| TS-EDM-09 | Rekap LPM & prodi, antarsemester | `BR-EDM-REK` | 3 periode `published` | LPM membuka tren | Tren per dosen dan aspek | Kaprodi hanya prodinya (404 lainnya); ekspor tercatat audit | Sedang · Baru |
| TS-EDM-10 | Partisipasi | `BR-EDM-REK` | 60% mahasiswa mengisi | Lihat partisipasi per kelas | Persentase partisipasi | Partisipasi berasal dari `edom_participations`, bukan dari `edom_responses` | Sedang · Baru |
| TS-EDM-11 | Aspek butuh pembinaan | `BR-EDM-REK` | Ambang (contoh) rata-rata < 3,0 | Analisis periode | Daftar aspek ditandai | Ditampilkan sebagai masukan; laporan memuat catatan bahwa EDOM bukan satu-satunya ukuran kinerja | Sedang · Baru |
| TS-EDM-12 | Status periode | `BR-EDM-PRD` | `prepared` | `open → closed → analyzed → published` | Transisi berurutan | Transisi lompat → 409; laporan dosen hanya setelah `published` | Sedang · Baru |
| TS-EDM-13 | Mahasiswa dropped / cuti | `BR-EDM-PRD` | Mahasiswa `dropped` dari K2 | Buka daftar | K2 tidak muncul | Target tidak dibuat untuk item `dropped` | Rendah · Baru |
| TS-EDM-14 | Paket: EDOM tersedia semua paket | `BR-ENT-PKG` | Tenant `starter` | Buka modul EDOM | Tersedia | Tidak ada 403 entitlement (tetap tunduk aktivasi tenant & permission) | Sedang · Baru |

---

## 9. MBKM (TS-MBK)

#### TS-MBK-01 — Pengajuan, seleksi, dan keikutsertaan

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-MBK-PRG`, `BR-MBK-PAR` |
| Prioritas · Jenis · Status | Tinggi · Positif · (Baru; `internships` `program_type = mbkm` hanya parsial, [OD-14](16-open-decisions.md#od-14)) |
| Kondisi awal | `mbkm_programs` magang dengan mitra `mbkm_partners`, status `registration_open`, kuota 5, syarat semester ≥ 5 dan IPK ≥ 2,75 (contoh) |
| Langkah | 1. Mahasiswa semester 3 mendaftar. 2. 8 mahasiswa memenuhi syarat mendaftar. 3. Program masuk `selection`, admin memilih 5. 4. Program `running`, pembimbing ditetapkan |
| Hasil diharapkan | Langkah 1 ditolak. Status partisipasi: `applied → selected` (5) / `rejected` (3). Pembimbing tertaut |
| Kriteria lulus | Langkah 1: 409 dengan alasan syarat. Seleksi melebihi kuota → 409. Notifikasi hasil seleksi ke 8 pendaftar. Mahasiswa bercekal akademik ditolak sesuai `academic_block_rules` |
| Otomasi | Pest Feature `app/Modules/Mbkm/Tests/Feature/MbkmParticipationTest.php` |

#### TS-MBK-02 — Konversi kegiatan ke SKS/MK

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-MBK-KNV` |
| Prioritas · Jenis · Status | Tinggi · Positif/Tepi · (Baru: `credit_conversions` `source_type = mbkm`) |
| Kondisi awal | Partisipasi `evaluated` dengan nilai pembimbing lapangan dan dosen. Batas konversi 20 SKS per semester (contoh) |
| Langkah | 1. Kaprodi mengusulkan konversi ke 4 MK (total 18 SKS). 2. Usulan kedua menambah 3 SKS. 3. Persetujuan → `applied` |
| Hasil diharapkan | Konversi 18 SKS disetujui; tambahan yang membuat total 21 ditolak. Nilai muncul di KHS/transkrip dengan penanda MBKM |
| Kriteria lulus | Langkah 2: 409. Setelah `applied`: `mbkm_participations.status = converted`, `grades` terbentuk melalui konversi (bukan input manual), IPS/IPK dihitung. MK yang sudah lulus dengan nilai lebih tinggi diperlakukan sesuai kebijakan nilai ulang |
| Otomasi | Pest Feature |

### 9.1 Skenario pelengkap MBKM

| ID | Judul | Ref. aturan | Kondisi awal | Langkah | Hasil diharapkan | Kriteria lulus | Prio · Status |
|---|---|---|---|---|---|---|---|
| TS-MBK-03 | Delapan jenis kegiatan | `BR-MBK-PRG` | — | Buat program untuk setiap jenis: pertukaran antarprodi, pertukaran PT eksternal, magang, asistensi mengajar, penelitian, proyek kemanusiaan, wirausaha, studi independen, KKN tematik | Semua tersimpan | Jenis di luar daftar terkonfigurasi → 422 | Sedang · Baru |
| TS-MBK-04 | Logbook mingguan | `BR-MBK-PAR` | Partisipasi `active` | Isi logbook; pembimbing memberi umpan balik | `mbkm_logbooks` tersimpan | Logbook setelah partisipasi `evaluated` → 409; pembimbing lain → 404 | Sedang · Baru |
| TS-MBK-05 | Evaluasi oleh pembimbing | `BR-MBK-PAR` | Kegiatan selesai | Isi `mbkm_assessments` | Partisipasi `evaluated` | Evaluasi wajib lengkap sebelum konversi | Sedang · Baru |
| TS-MBK-06 | Pengunduran diri | `BR-MBK-PAR` | Partisipasi `active` | Mahasiswa mengundurkan diri dengan persetujuan | `withdrawn` | Tidak ada konversi; kuota tidak dibuka kembali setelah `running` (kecuali konfigurasi) | Rendah · Baru |
| TS-MBK-07 | Pertukaran ke PT eksternal | `BR-MBK-KNV` | Mahasiswa mengambil MK di PT mitra | Input nilai PT mitra lalu konversi | Nilai dikonversi ke skala tenant | Konversi skala tercatat (nilai asal + hasil) | Sedang · Baru |
| TS-MBK-08 | Rekap IKU otomatis hanya Enterprise | `BR-MBK-IKU`, `BR-ENT-PKG` ([OD-13](16-open-decisions.md#od-13)) | U-A (`siakad.mbkm.iku`) dan U-B (`siakad.mbkm` saja) | Buka rekap IKU | U-A: `iku_snapshots` terhitung; U-B: ditolak | U-B: 403 `FEATURE_NOT_ENTITLED`, rekap kegiatan MBKM standar tetap tersedia | Tinggi · Baru |
| TS-MBK-09 | Data `internships` lama | `BR-MBK-PRG` ([OD-14](16-open-decisions.md#od-14)) | Baris `internships.program_type = mbkm` | Jalankan strategi migrasi terpilih | Data lama tetap terbaca | Jumlah baris sebelum = sesudah; rollback mengembalikan kondisi | Sedang · Baru |
| TS-MBK-10 | Mitra MBKM | `BR-MBK-PRG` | — | CRUD mitra; hapus mitra yang dipakai program | Hapus ditolak | 409; mitra diarsipkan, bukan dihapus | Rendah · Baru |

---

## 10. Entitlement Paket (TS-ENT)

Lapisan izin dievaluasi berurutan: (1) fitur tersedia di paket (`plan_feature_entitlements`), (2) fitur aktif untuk tenant (`university_feature_flags` / `university_modules`), (3) permission & scope pengguna, (4) data memenuhi syarat proses. Saat ini lapis (1) dan (2) **belum ditegakkan** (README §3.2).

#### TS-ENT-01 — Batas fitur per paket

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-ENT-PKG` ([OD-01](16-open-decisions.md#od-01)) |
| Prioritas · Jenis · Status | Tinggi · Negatif · (Baru: `EnsureFeatureEntitled`, `plan_feature_entitlements`) |
| Kondisi awal | U-B berpaket `starter`, pemetaan (contoh) `starter` → `siakad`, `siakad.mbkm`, `lms`, `edom`; tanpa `cbt`, `obe`. Pengguna U-B memiliki permission `exams.create` dan `obe_outcomes.create` (sengaja diberikan) |
| Langkah | 1. `POST /exams`. 2. `GET /obe/program-learning-outcomes`. 3. `GET /lms/classes`. 4. Buka menu web |
| Hasil diharapkan | Fitur di luar paket ditolak walaupun pengguna memiliki permission. Menu fitur di luar paket tidak tampil atau tampil sebagai "tidak tersedia di paket" |
| Kriteria lulus | Langkah 1–2: 403 `errors.code = FEATURE_NOT_ENTITLED` dengan `errors.feature` (`cbt`, `obe`). Langkah 3: 200. Pemeriksaan entitlement terjadi **sebelum** pemeriksaan permission (urutan dapat dibuktikan: pengguna tanpa permission di tenant tanpa paket juga mendapat `FEATURE_NOT_ENTITLED`). Ujian publik PMB U-B juga ditolak |
| Otomasi | Pest Feature `tests/Feature/Entitlement/PlanEntitlementTest.php` atau modul pemilik middleware; Vitest/e2e untuk menu |

#### TS-ENT-02 — Penurunan paket (downgrade)

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-ENT-PKG` |
| Prioritas · Jenis · Status | Tinggi · Tepi · (Baru) |
| Kondisi awal | U-C `professional` memiliki 3 ujian CBT `published`, 1 ujian `active`, bank soal 200 butir, data OBE, pemakaian storage 150 GB. Paket diturunkan ke `starter` (limit storage 50 GB contoh, tanpa `cbt`, `obe`) |
| Langkah | 1. Ubah paket tenant. 2. Peserta melanjutkan ujian yang `active`. 3. Dosen membuat ujian baru. 4. Admin membuka data CBT/OBE lama. 5. Unggah berkas LMS baru. 6. Ekspor data lama |
| Hasil diharapkan | **Tidak ada data yang dihapus.** Fitur yang hilang menjadi baca-saja/ekspor atau tersembunyi sesuai kebijakan downgrade di [11-roles-permissions.md](11-roles-permissions.md). Proses yang sedang berjalan diselesaikan (masa tenggang) |
| Kriteria lulus | Langkah 2: ujian aktif dapat diselesaikan (masa tenggang terkonfigurasi). Langkah 3: 403 `FEATURE_NOT_ENTITLED`. Langkah 4: sesuai kebijakan (baca-saja 200 atau 403) — tidak ada 500. Langkah 5: 409 `STORAGE_QUOTA_EXCEEDED` karena 150 GB > 50 GB; berkas lama tetap dapat diunduh. Langkah 6: 200. Nilai CBT yang sudah dikirim ke SIAKAD tetap utuh. Upgrade kembali memulihkan akses tanpa migrasi data. Perubahan paket tercatat audit |
| Otomasi | Pest Feature |

### 10.1 Skenario pelengkap entitlement

| ID | Judul | Ref. aturan | Kondisi awal | Langkah | Hasil diharapkan | Kriteria lulus | Prio · Status |
|---|---|---|---|---|---|---|---|
| TS-ENT-03 | Fitur di paket tetapi dinonaktifkan tenant | `BR-ENT-PKG` | U-A berhak `edom`, flag tenant off | Akses EDOM | Ditolak | 403 `FEATURE_DISABLED` (berbeda dari `FEATURE_NOT_ENTITLED`) | Tinggi · Baru |
| TS-ENT-04 | Add-on OBE pada paket di bawah minimum | `BR-ENT-PKG` ([OD-01](16-open-decisions.md#od-01), [OD-02](16-open-decisions.md#od-02)) | U-B `starter` + add-on `obe` aktif | Akses OBE; akses OBE AI | OBE tersedia; OBE AI tidak | `obe` 200; `obe.ai` 403 | Tinggi · Baru |
| TS-ENT-05 | Limit storage per paket | `BR-ENT-LIM` | Lihat TS-LMS-07 | Ubah paket → limit berubah | Limit baru berlaku segera | Cache entitlement diinvalidasi saat paket berubah | Tinggi · Baru |
| TS-ENT-06 | Limit kelas umum per paket | `BR-ENT-LIM` | Lihat TS-LMS-08 | — | — | Limit dibaca dari entitlement, bukan konstanta | Sedang · Baru |
| TS-ENT-07 | Permission ada, scope tidak | `BR-ENT-*`, `BR-SEC-SCP` | Fitur berhak, `kaprodi.p1` | Akses data P2 | Ditolak di lapis 3 | 404 | Tinggi · Baru |
| TS-ENT-08 | Semua lapis lolos, data tidak memenuhi | `BR-ENT-*` | Semua lapis lolos, KRS periode tutup | KRS | Ditolak di lapis 4 | 409 `PERIOD_CLOSED` | Sedang · Baru |
| TS-ENT-09 | `super_admin` platform tanpa konteks tenant | `BR-ENT-*`, `BR-SEC-TEN` | Tanpa `X-University-ID` | `GET /lms/classes` | Tidak melihat data bisnis | Ditolak/kosong (regresi PLAT-14) | Tinggi · Parsial |
| TS-ENT-10 | Menu & route web mengikuti entitlement | `BR-ENT-PKG` | U-B | Buka `/akademik/...` CBT via URL langsung | Halaman "tidak tersedia di paket" | Guard route (pola `RequirePermission`) menolak tanpa memanggil API berulang | Sedang · Baru |
| TS-ENT-11 | Aplikasi mobile mengikuti entitlement | `BR-ENT-PKG` | U-B tanpa `cbt` | Buka tab ujian di Flutter | Tab tersembunyi | Respons `GET /me` (atau endpoint fitur) memuat daftar feature key aktif | Sedang · Baru |
| TS-ENT-12 | Pemetaan paket dapat diubah tanpa deploy | `BR-ENT-PKG` | `super_admin` | Tambah `cbt` ke `starter` di `plan_feature_entitlements` | U-B langsung berhak | Perubahan tercatat audit platform | Sedang · Baru |

---

## 11. Keamanan, Isolasi, dan Privasi (TS-SEC)

#### TS-SEC-01 — Akses ID milik tenant lain mengembalikan 404

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SEC-TEN` |
| Prioritas · Jenis · Status | Tinggi · Negatif · (Ada untuk tabel lama; wajib untuk setiap tabel baru) |
| Kondisi awal | Pengguna U-A dengan permission lengkap. Daftar ID sumber daya U-B dari seluruh tabel tenant (KRS, nilai, sesi, presensi, kelas LMS, materi, ujian, soal, draf AI, CPL, RPS, periode EDOM, program MBKM, cekal, berkas) |
| Langkah | Untuk setiap endpoint `GET/PUT/PATCH/DELETE` yang menerima ID dan setiap payload yang mereferensikan ID (mis. `class_section_id`, `exam_id`, `lms_class_id`), kirim ID U-B dengan header `X-University-ID` U-A |
| Hasil diharapkan | Sumber daya tenant lain dianggap tidak ada |
| Kriteria lulus | **Semua** respons 404 (path) atau 422 `exists` ter-scope (payload). Tidak ada 403 yang membocorkan keberadaan, tidak ada 500, tidak ada perubahan data U-B (hitung baris dan `updated_at` U-B sebelum/sesudah). Uji dibangkitkan dari daftar route (`Route::getRoutes()`) agar route baru otomatis tercakup |
| Otomasi | Pest Feature generatif `tests/Feature/TenantIsolationAcademicTest.php` (melanjutkan `app/Modules/Tenancy/Tests/Feature/TenantIsolationTest.php` dan `tests/Unit/InstitutionScopingTest.php` yang sudah ada; belum ada uji isolasi generatif per route akademik) |

#### TS-SEC-02 — Orang tua hanya melihat mahasiswa tertaut

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SEC-PRV`, `BR-SIA-ORT` ([OD-21](16-open-decisions.md#od-21)) |
| Prioritas · Jenis · Status | Tinggi · Privasi · (Baru: `student_guardians`, `guardian_portal`) |
| Kondisi awal | `ortu.01` tertaut ke `mhs.p1.01` (tautan terverifikasi dan disetujui mahasiswa sesuai OD-21). `mhs.p1.02` tidak tertaut. Tautan ke `mhs.p1.03` berstatus menunggu verifikasi |
| Langkah | 1. `GET /guardian/students`. 2. `GET /guardian/students/{mhs.p1.01}/grades`. 3. Sama untuk `mhs.p1.02` dan `mhs.p1.03`. 4. Mencoba endpoint non-guardian (`GET /students/{id}`, `GET /student/krs`) |
| Hasil diharapkan | Hanya `mhs.p1.01` terlihat. Mahasiswa lain dan tautan yang belum diverifikasi tidak dapat diakses |
| Kriteria lulus | Langkah 1: tepat 1 mahasiswa. Langkah 3: 404. Langkah 4: 403. Tautan yang dicabut berlaku segera (permintaan berikutnya 404). Akses orang tua tercatat audit |
| Otomasi | Pest Feature `app/Modules/Academic/Tests/Feature/GuardianAccessTest.php` |

#### TS-SEC-03 — Orang tua hanya melihat data yang diizinkan

| Atribut | Isi |
|---|---|
| Referensi aturan | `BR-SEC-PRV` |
| Prioritas · Jenis · Status | Tinggi · Privasi · (Baru) |
| Kondisi awal | Data yang diizinkan (contoh, terkonfigurasi): nilai `published`, IPS/IPK, rekap kehadiran, status tagihan. Tidak diizinkan: catatan perwalian, jawaban EDOM, detail pelanggaran ujian, NIK/dokumen identitas, nilai komponen yang belum dipublikasi |
| Langkah | Panggil setiap endpoint `/guardian/...`; periksa seluruh kunci JSON. Ubah konfigurasi (matikan rekap tagihan) |
| Hasil diharapkan | Respons hanya memuat atribut yang diizinkan |
| Kriteria lulus | Uji *snapshot* kunci respons: tidak ada kunci di luar daftar izin (allowlist). Nilai belum `published` tidak muncul. Status tagihan tanpa rincian transaksi. Setelah konfigurasi diubah, endpoint tagihan 403 dan kartu web tersembunyi |
| Otomasi | Pest Feature (assert struktur JSON eksak) |

### 11.1 Skenario pelengkap keamanan

| ID | Judul | Ref. aturan | Kondisi awal | Langkah | Hasil diharapkan | Kriteria lulus | Prio · Status |
|---|---|---|---|---|---|---|---|
| TS-SEC-04 | Header `X-University-ID` tenant tanpa keanggotaan | `BR-SEC-TEN` | Pengguna U-A tanpa keanggotaan U-B | Kirim header U-B ke endpoint akademik | Ditolak | 403 (regresi TEN-04, diperluas ke endpoint akademik) | Tinggi · Parsial |
| TS-SEC-05 | `university_id` di payload diabaikan | `BR-SEC-TEN` | — | `POST` dengan `university_id` U-B | Data tersimpan di U-A | Kolom `university_id` = konteks tenant; bukan dari payload | Tinggi · Ada |
| TS-SEC-06 | Dekan hanya fakultasnya | `BR-SEC-SCP` | `dekan.f1` | Daftar mahasiswa, nilai, laporan | Hanya F1 | `total` = jumlah F1; detail F2 → 404 | Tinggi · Baru |
| TS-SEC-07 | Admin prodi hanya prodinya | `BR-SEC-SCP` | `adminp1.ua` | CRUD kelas P2 | Ditolak | 404 | Tinggi · Baru |
| TS-SEC-08 | Dosen hanya kelas yang diampu | `BR-SEC-SCP` | Flag `academic.lecturer_ownership` aktif | `dosen2` input nilai/presensi K1 | Ditolak | 403/404 sesuai `ClassSectionAccess` (regresi `LecturerOwnershipTest`); dosen team teaching diizinkan | Tinggi · Parsial |
| TS-SEC-09 | Mahasiswa hanya datanya sendiri | `BR-SEC-SCP` | Dua mahasiswa | Akses `student/attendance/{krsItem}` milik orang lain | Ditolak | 404 | Tinggi · Ada |
| TS-SEC-10 | Audit aksi sensitif | `BR-SEC-AUD` | — | Ubah nilai, dispensasi cekal, setujui KRS, ubah kurikulum, sahkan CPL | Setiap aksi tercatat | `audit_logs` memuat user, aksi, old/new, alasan, IP; tidak ada password/token/hash sesi di `old_values`/`new_values` (cari pola `password`, `token`, `session_token_hash`) | Tinggi · Parsial |
| TS-SEC-11 | Audit log tidak dapat diubah | `BR-SEC-AUD` | — | `PUT/DELETE /audit-logs/{id}` | Tidak ada | 404/405 (regresi AUDIT-01 … AUDIT-05) | Tinggi · Ada |
| TS-SEC-12 | Kontrol akses berkas | `BR-SEC-FIL` | Berkas tugas mahasiswa A | Mahasiswa B / dosen kelas lain mengunduh | Ditolak | 404; URL bertanda tangan kedaluwarsa → 403; berkas tenant lain tidak dapat diakses meskipun path diketahui | Tinggi · Parsial |
| TS-SEC-13 | Validasi tipe dan pemindaian berkas | `BR-SEC-FIL` | — | Unggah berkas berekstensi `.pdf` berisi executable | Ditolak atau dikarantina | `file_uploads.status` scan sesuai; berkas tidak dapat diunduh sebelum lolos | Sedang · Parsial |
| TS-SEC-14 | Rate limit endpoint sensitif | `BR-SEC-RTL` | — | Ulangi permintaan cepat ke QR, akses ujian publik, permintaan AI, ekspor | Dibatasi | 429 setelah ambang terkonfigurasi | Sedang · Parsial |
| TS-SEC-15 | Idempotensi aksi berulang | `BR-SEC-*` | — | Kirim `POST` persetujuan/publikasi dua kali dengan `Idempotency-Key` sama | Satu efek | Respons kedua = respons pertama; tidak ada baris ganda | Sedang · Baru |
| TS-SEC-16 | Mass assignment status | `BR-SEC-STATE` | — | `PUT` mengirim `status: published` pada endpoint ubah biasa | Diabaikan | Status hanya berubah via endpoint transisi | Tinggi · Parsial |
| TS-SEC-17 | Privasi proctoring | `BR-SEC-PRV`, `BR-CBT-PRC` | Lihat TS-CBT-15–18 | Pemberitahuan dan persetujuan kamera sebelum ujian | Teks pemberitahuan tampil dan dicatat | Persetujuan tercatat per percobaan | Tinggi · Baru |
| TS-SEC-18 | Ekspor data pribadi | `BR-SEC-PRV` | Laporan mahasiswa | Ekspor oleh role tanpa `academic_reports.export` | Ditolak | 403; ekspor sah tercatat `exported`; kolom sensitif dimasking sesuai matriks 12 | Tinggi · Parsial |
| TS-SEC-19 | Pencadangan & pemulihan | `BR-SEC-*` | Staging | Pulihkan cadangan ke lingkungan terpisah | Data akademik utuh | Hitung baris tabel kritis sama; berkas sesuai checksum (lihat [13-security-audit.md](13-security-audit.md)) | Sedang · Parsial |
| TS-SEC-20 | Kegagalan integrasi tidak membocorkan detail | `BR-SEC-*` | Penyedia AI / SMS error | Picu kegagalan | Pesan generik ke pengguna | Respons tanpa stack trace/kredensial; detail di log server; monitor sinkronisasi menandai gagal | Sedang · Baru |

---

## 12. Uji Non-Fungsional: Konkurensi, Kinerja, Ketahanan

### 12.1 Konkurensi

| Skenario | Cara | Kriteria lulus |
|---|---|---|
| Kursi terakhir (TS-SIA-06) | Pest `@group mysql` dengan proses paralel + k6 di staging (contoh: 200 pengguna virtual ke kelas kapasitas 30) | Tidak ada kelebihan kapasitas; tidak ada 500; p95 latensi tambah item KRS di bawah target yang ditetapkan tim |
| Sisa kuota SKS paralel | Pest `@group mysql` | Tepat satu berhasil |
| Dua dosen team teaching mengisi presensi sesi yang sama | Pest `@group mysql` | Hasil akhir deterministik (pengiriman terakhir per mahasiswa), tidak ada baris ganda |
| Kuota kelas umum dan storage di batas | Pest `@group mysql` | Tidak terlampaui |
| Pemindaian QR massal | k6: 300 pemindaian dalam 30 detik untuk satu sesi | Semua tercatat sekali; tidak ada 500 |
| Mulai ujian serentak | k6: 500 peserta memulai dalam 1 menit | Semua percobaan dibuat sekali; urutan acak tersimpan |

### 12.2 Kinerja (target ditetapkan tim, angka contoh)

| Operasi | Volume uji | Target contoh |
|---|---|---|
| `GET /student/krs/offerings` | 300 kelas di term | p95 < 800 ms |
| Simpan jawaban ujian | 500 peserta × 1 jawaban / 10 detik | p95 < 300 ms |
| Hitung nilai kelas | 60 peserta × 6 komponen | < 2 detik |
| Pengukuran CPL prodi | 1.000 mahasiswa × 50 MK | Job antrean selesai < 10 menit; UI tidak terblokir |
| Rekap EDOM tenant | 20.000 respons | < 30 detik (job) |

### 12.3 Ketahanan

| Skenario | Kriteria lulus |
|---|---|
| Worker antrean mati saat job AI berjalan | Job dilanjutkan/diulang setelah worker hidup; status tidak tertahan `processing` selamanya (batas waktu) |
| Event SIAKAD → LMS gagal | Tercatat, dapat diulang manual/otomatis; tidak ada kelas LMS ganda |
| Notifikasi gagal terkirim | Transaksi utama (mis. publikasi nilai) tetap berhasil; notifikasi dicoba ulang |
| Scheduler tidak berjalan | Finalisasi ujian tetap terjadi secara lazy (perilaku yang ada); penguncian KRS dan kedaluwarsa dispensasi dievaluasi saat akses |

---

## 13. Matriks Keterlacakan Brief → Skenario

### 13.1 Butir wajib brief §13

| No | Butir brief §13 | Skenario |
|---|---|---|
| 1 | KRS prasyarat terpenuhi | TS-SIA-01 |
| 2 | KRS prasyarat tidak terpenuhi | TS-SIA-02 |
| 3 | Bentrok jadwal | TS-SIA-04, TS-SIA-14 |
| 4 | Kelas penuh | TS-SIA-05, TS-SIA-06 |
| 5 | Cekal keuangan | TS-SIA-07, TS-SIA-09 |
| 6 | Dispensasi cekal | TS-SIA-08, TS-SIA-09 |
| 7 | Isi presensi | TS-SIA-17, TS-LMS-04 |
| 8 | Koreksi presensi | TS-SIA-18 |
| 9 | Hitung nilai | TS-SIA-21, TS-SIA-22 |
| 10 | Ubah nilai setelah publikasi | TS-SIA-23, TS-SIA-25 |
| 11 | Sinkron kelas ke LMS | TS-LMS-01 |
| 12 | Kumpul tugas | TS-LMS-02, TS-LMS-03 |
| 13 | Ujian dengan timer | TS-CBT-01 |
| 14 | Koneksi putus saat ujian | TS-CBT-02, TS-CBT-03 |
| 15 | Buat soal AI | TS-CBT-04, TS-CBT-05 |
| 16 | Ukur CPMK & CPL | TS-OBE-01, TS-OBE-02 |
| 17 | Isi EDOM | TS-EDM-01 |
| 18 | EDOM anonim | TS-EDM-02, TS-EDM-07, TS-EDM-08 |
| 19 | Batas akses orang tua | TS-SEC-02, TS-SEC-03 |
| 20 | Batas fitur per paket | TS-ENT-01 … TS-ENT-12 |
| 21 | Isolasi antar-PT | TS-SEC-01, TS-SEC-04, TS-SEC-05, TS-ENT-09 |

### 13.2 Kasus tepi yang diminta

| Kasus tepi | Skenario |
|---|---|
| Batas SKS (tepat di batas dan +1) | TS-SIA-03 |
| Kursi terakhir dengan permintaan bersamaan | TS-SIA-06, §12.1 |
| Token QR kedaluwarsa / diputar ulang | TS-LMS-05, TS-LMS-06 |
| Timer habis saat luring | TS-CBT-03 |
| Draf AI tidak dapat dipublikasikan tanpa tinjauan | TS-CBT-05, TS-OBE-11 |
| Anonimitas EDOM tanpa jalur join | TS-EDM-02 |
| Orang tua hanya mahasiswa tertaut & data diizinkan | TS-SEC-02, TS-SEC-03 |
| Perilaku downgrade paket | TS-ENT-02 |
| ID lintas-tenant → 404 | TS-SEC-01 |

### 13.3 Cakupan per sub-bagian brief

| Bagian brief | Skenario |
|---|---|
| A.1 Data master | TS-SIA-36, TS-SIA-37, TS-SIA-39 |
| A.2 Kurikulum & MK | TS-SIA-01, TS-SIA-02, TS-SIA-13, TS-SIA-16, TS-SIA-31 |
| A.3 Kelas, jadwal, dosen | TS-SIA-04, TS-SIA-14, TS-SIA-15 |
| A.4 KRS (13 langkah + alternatif) | TS-SIA-01 … TS-SIA-13 |
| A.5 Presensi & jurnal | TS-SIA-17 … TS-SIA-20 |
| A.6 Penilaian & hasil studi | TS-SIA-21 … TS-SIA-25, TS-SIA-40 |
| A.7 Perwalian | TS-SIA-11, TS-SIA-26 … TS-SIA-29 |
| A.8 Pencekalan | TS-SIA-07 … TS-SIA-09, TS-SIA-19, TS-SIA-34 |
| A.9 Layanan lain | TS-SIA-30 … TS-SIA-35, TS-SEC-02, TS-SEC-03 |
| A.10 MBKM | TS-MBK-01 … TS-MBK-10 |
| B.1 Manajemen kelas | TS-LMS-01, TS-LMS-08, TS-LMS-09, TS-LMS-11, TS-LMS-20 |
| B.2 Materi & aktivitas | TS-LMS-02, TS-LMS-03, TS-LMS-10 … TS-LMS-14, TS-LMS-18 |
| B.3 Hybrid & QR | TS-LMS-04 … TS-LMS-06, TS-LMS-19 |
| B.4 Dashboard | TS-LMS-14 … TS-LMS-16 |
| B.5 Lanjutan | TS-LMS-16, TS-LMS-17, TS-LMS-22, TS-CBT-15, TS-CBT-22 |
| B.6 Storage & whitelabel | TS-LMS-07, TS-LMS-21, TS-ENT-05 |
| C.1 Bank soal | TS-CBT-08 |
| C.2 Pengaturan ujian | TS-CBT-01 … TS-CBT-03, TS-CBT-09 … TS-CBT-13, TS-CBT-21, TS-CBT-24 |
| C.3 CBT AI | TS-CBT-04, TS-CBT-05 |
| C.4 Proctoring | TS-CBT-14 … TS-CBT-18, TS-SEC-17 |
| C.5 Integrasi | TS-CBT-06, TS-CBT-07, TS-CBT-19, TS-CBT-20, TS-CBT-22, TS-CBT-23 |
| D.1–D.3 Profil, CPL, pemetaan | TS-OBE-03 … TS-OBE-05, TS-OBE-15 |
| D.4 RPS | TS-OBE-06, TS-OBE-07, TS-SIA-20, TS-OBE-14 |
| D.5 Pengukuran | TS-OBE-01, TS-OBE-02, TS-OBE-08, TS-OBE-09, TS-OBE-13 |
| D.6 Pelaporan | TS-OBE-10 |
| D.7 OBE AI | TS-OBE-11, TS-OBE-12 |
| E.1 Pengaturan EDOM | TS-EDM-04, TS-EDM-05, TS-EDM-06, TS-EDM-12, TS-EDM-13 |
| E.2 Pengisian & privasi | TS-EDM-01 … TS-EDM-03, TS-EDM-08 |
| E.3 Rekap | TS-EDM-07, TS-EDM-09 … TS-EDM-11 |
| §4 Integrasi antarmodul (10 titik) | 1: TS-LMS-01 · 2: TS-LMS-01 · 3: TS-OBE-06 · 4: TS-SIA-20 · 5: TS-LMS-03, TS-CBT-06 · 6: TS-OBE-01 · 7: TS-EDM-04 · 8: TS-SIA-39, TS-CBT-23 · 9: TS-SIA-07, TS-SIA-19, TS-CBT-11 · 10: TS-MBK-02 |
| §5 Role & scope | TS-SIA-26, TS-SEC-06 … TS-SEC-09, TS-OBE-15 |
| §11 Keamanan | TS-SEC-01 … TS-SEC-20 |
| §12 Entitlement | TS-ENT-01 … TS-ENT-12, TS-LMS-16, TS-MBK-08, TS-EDM-14 |

---

## 14. Penempatan Uji Otomatis

### 14.1 Backend (Pest)

| Jenis | Lokasi | Catatan |
|---|---|---|
| Feature SIAKAD | `app/Modules/Academic/Tests/Feature/*Test.php` | Memperluas uji yang ada (`KrsEnrollmentTest`, `StudentKrsSelfServiceTest`, `AttendanceRecordingTest`, `GradeRecordingTest`, `TranscriptTest`, `LecturerOwnershipTest`). Uji baru: `AcademicBlockTest`, `GradeSubmissionTest`, `GradeRevisionTest`, `LectureSessionTest`, `AttendanceCorrectionTest`, `CurriculumVersionTest`, `AdvisingTest`, `GuardianAccessTest` |
| Unit SIAKAD | `app/Modules/Academic/Tests/Unit` | Sudah terdaftar di `phpunit.xml`. Kalkulator nilai, batas SKS, deteksi bentrok, persentase kehadiran, resolver cekal |
| Modul baru | `app/Modules/{Lms,Cbt,Obe,Edom,Mbkm}/Tests/{Feature,Unit}` | `tests/Pest.php` sudah memakai pola `../app/Modules/*/Tests/Feature`, tetapi **`phpunit.xml` mendaftarkan direktori per modul secara eksplisit**. Setiap modul baru wajib menambahkan direktorinya ke testsuite `Unit` dan `Feature` (bagian dari DoD di [15-implementation-roadmap.md](15-implementation-roadmap.md)) |
| Entitlement | Modul pemilik middleware `EnsureFeatureEntitled` (mis. `app/Modules/Tenancy/Tests/Feature`) | TS-ENT-* |
| Isolasi tenant generatif | `tests/Feature` atau `app/Modules/Tenancy/Tests/Feature` | TS-SEC-01, dibangkitkan dari daftar route |
| Konkurensi | Uji bertanda `->group('mysql')` | Dilewati bila `DB_CONNECTION` ≠ mysql/pgsql. CI menjalankan job terpisah dengan layanan DB |
| Helper | `tests/Helpers/*` | Tambahkan fixture `academicWorld()` (tenant, prodi, term, kelas, peserta, dosen) mengikuti pola `portalWorld()` |

Pola wajib: `TenantContext` diset saat membuat fixture lalu dikembalikan ke `null`; izin melalui `actingAsUserWithUniversityPermissions()`; layanan eksternal di-*fake* (`Http::fake`, `Queue::fake`, `Notification::fake`, `Storage::fake`, `Event::fake`).

### 14.2 Frontend web

| Jenis | Lokasi | Status |
|---|---|---|
| Lint | `npm run lint` (oxlint) | Ada |
| Uji komponen | `frontend/src/**/__tests__` dengan Vitest + Testing Library | **[Rekomendasi]** — belum ada runner uji di `frontend/package.json` |
| E2E | `frontend/e2e/*.spec.ts` dengan Playwright terhadap staging/seed | **[Rekomendasi]**. Prioritas: alur KRS mahasiswa, input & publikasi nilai, pengisian EDOM, guard menu entitlement (TS-ENT-10) |

### 14.3 Mobile (Flutter)

| Jenis | Lokasi | Cakupan |
|---|---|---|
| Unit/widget | `mobile/test/features/<fitur>/` (pola yang ada: `mobile/test/features/exam/exam_form_validation_test.dart`) | Timer ujian berbasis waktu server, antrean jawaban luring, pemindai QR, tampilan berbasis feature key |
| Integrasi | `mobile/integration_test/` **[Rekomendasi]** | TS-CBT-02, TS-CBT-03, TS-LMS-04 dengan simulasi jaringan |

### 14.4 Uji manual

| Skenario | Alasan |
|---|---|
| TS-CBT-15, TS-CBT-16 (kamera) | Perilaku izin perangkat dan peramban nyata |
| TS-LMS-21 (APK whitelabel) | Proses build dan publikasi ([OD-25](16-open-decisions.md#od-25)) |
| TS-SEC-19 (pemulihan) | Prosedur operasional |
| Aksesibilitas halaman utama | Pemeriksaan kontras dan navigasi papan ketik (lihat [09-ui-ux-specification.md](09-ui-ux-specification.md)) |

---

## 15. Kriteria Masuk, Kriteria Keluar, dan Regresi

### 15.1 Kriteria masuk (per fitur)

1. Aturan bisnis fitur sudah bernomor di [10-business-rules.md](10-business-rules.md) dan keputusan OD terkait sudah diputuskan atau diberi nilai default terdokumentasi.
2. Migrasi dapat dijalankan dan di-*rollback* di basis data uji.
3. Permission baru sudah ada di `RolePermissionSeeder` dan peta role di `OrganizationalRoleSeeder`.
4. Fixture data uji tersedia.

### 15.2 Kriteria keluar (per tahap roadmap)

| Kriteria | Ambang |
|---|---|
| Skenario prioritas Tinggi tahap tersebut | 100% lulus |
| Skenario prioritas Sedang | ≥ 95% lulus; sisanya memiliki tiket dan bukan masalah keamanan/integritas data |
| TS-SEC-01 (isolasi) untuk tabel baru tahap tersebut | 100% lulus |
| Uji konkurensi relevan (grup `mysql`) | Lulus di CI |
| Regresi suite lama | Hijau; perubahan perilaku yang disengaja didaftar di PR |
| Cacat terbuka kritis/tinggi | 0 |

### 15.3 Pemetaan regresi terhadap [../BLACK-BOX-TESTING.md](../BLACK-BOX-TESTING.md)

| ID lama | Status setelah rancangan | Skenario baru terkait |
|---|---|---|
| AKM-05 | **Harus diperbarui**: `POST /students` sekarang ada | TS-SIA-36 |
| KRS-01 … KRS-13 | Tetap berlaku untuk endpoint admin `/krs-items` | TS-SIA-03 … TS-SIA-06 |
| KRS-05, KRS-06 | Angka tier menjadi konfigurasi; uji membaca nilai dari setting | TS-SIA-03 |
| NIL-01 … NIL-03 | Diganti perhitungan berbasis `grade_scales` (perubahan sengaja) | TS-SIA-21 |
| NIL-04 | Berubah: setelah `published`, `PUT` ditolak 409 | TS-SIA-23 |
| NIL-09 | Celah tertutup bila `academic.lecturer_ownership` aktif | TS-SEC-08 |
| ABS-01 … ABS-08 | Tetap; ditambah `lecture_session_id` | TS-SIA-17, TS-SIA-18 |
| TRX-01 … TRX-05 (transkrip) | Tetap; ditambah nilai belum `published` tidak dihitung | TS-SIA-40 |
| PLAT-14, TEN-04 | Tetap; diperluas ke endpoint baru | TS-ENT-09, TS-SEC-04 |
| Seksi 24 (portal mock) | Halaman EDOM, perwalian, cuti, kuis berubah dari mock ke API | TS-EDM-01, TS-SIA-27, TS-SIA-30 |
