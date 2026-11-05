# One Atma Talent — Slice 1: Minat (RIASEC)

> **Per:** 5 November 2026. Rencana lengkap ada di [01-audit-dan-rencana-implementasi.md](01-audit-dan-rencana-implementasi.md).
> **Catatan:** dokumen ini menjelaskan Slice 1. Admin UI, penjadwal, approval, dan review ada di Fase 2 ([03](03-admin-management.md)–[07](07-testing-and-verification.md)).
> **Lingkup:** asesmen minat RIASEC dengan skala Likert 1–5, dari konfigurasi admin sampai hasil yang dipublikasikan ke peserta.

## 1. Yang Sudah Ada

| Lapisan | Isi |
|---|---|
| Migrasi | `2026_11_05_100000_create_pmb_assessment_tables.php` — 8 tabel `pmb_assessment_*` |
| Enum | `AssessmentDomain`, `AssessmentPurpose`, `AssessmentVersionStatus`, `AssessmentSessionStatus`; `ConsentType::AssessmentTalent` |
| Model | `AssessmentType`, `AssessmentVersion`, `AssessmentDimension`, `AssessmentItem`, `AssessmentSession`, `AssessmentResponse`, `AssessmentScore`, `AssessmentSessionEvent` (semua `PmbModel`) |
| Scoring | `Services/Assessment/Scoring/RiasecScorer.php` (`riasec-mean-v1`), tanpa akses database |
| Service | `AssessmentConfigurationService` (admin), `AssessmentSessionService` (peserta, penilaian, publikasi, hasil) |
| API peserta | 7 rute di `/pmb/applicant` (lihat bagian 4) |
| API admin | 17 rute di `/pmb/admin` (lihat bagian 4) |
| Izin | `pmb_assessments.{read,update,publish}`, `pmb_assessment_results.{read,publish}` |
| Frontend peserta | `pages/portal/AssessmentPages.tsx`: daftar, mulai, pengerjaan (layar penuh), hasil |
| Tes | `Tests/Unit/RiasecScorerTest.php` (6), `Tests/Feature/AssessmentTalentTest.php` (6) |

**Yang belum ada di slice ini (sebagian sudah dikerjakan di Fase 2):** halaman admin, penjadwal penutupan sesi, dan persetujuan dua langkah sudah ada di Fase 2. Domain selain minat dan matching program studi belum. Lihat bagian 11.

## 2. Model Data

```
pmb_assessment_types ──< pmb_assessment_versions ──< pmb_assessment_dimensions
                                 │                         │
                                 │ 1                       │ 1
                                 └──< pmb_assessment_items >┘ (dimension_id)
                                 │
pmb_applications ──< pmb_assessment_sessions ──< pmb_assessment_responses (session_id, item_id)
                          │       │
                          │       └──< pmb_assessment_scores (per dimensi)
                          └──< pmb_assessment_session_events (append-only)
```

| Tabel | Kunci unik | Catatan |
|---|---|---|
| `pmb_assessment_types` | `(university_id, code)` | `domain` menentukan dimensi yang wajib dan apakah scoring tersedia. |
| `pmb_assessment_versions` | `(assessment_type_id, version_no)` | `purpose` (`exploratory` / `admissions`), `status`, periode berlaku, timer opsional, `scoring_config` (JSON: `engine`, `scale_min`, `scale_max`). |
| `pmb_assessment_dimensions` | `(version_id, code)` | `interpretation` diisi tim asesmen. Tidak ada teks bawaan. |
| `pmb_assessment_items` | — | `is_reversed` dan `dimension_id` tidak pernah dikirim ke peserta. |
| `pmb_assessment_sessions` | `(application_id, version_id)` | Satu sesi per pendaftaran per versi. `item_order` dibekukan saat mulai. Menyimpan hasil ringkas: `holland_code`, `dominant_dimensions`, `scoring_engine`. |
| `pmb_assessment_responses` | `(session_id, item_id)` | Upsert per batch. |
| `pmb_assessment_scores` | `(session_id, dimension_id)` | Nilai mentah, rata-rata, persentase, peringkat. |
| `pmb_assessment_session_events` | — | Jejak `started`, `submitted`, `expired`, `published`. |

Respons selalu menunjuk butir dari versi tempat sesi dimulai. Versi yang sudah `published` tidak dapat diubah (`VERSION_LOCKED`), sehingga histori respons tidak pernah kehilangan konteksnya. Aturan ini berlaku di service, tidak hanya di UI.

## 3. Siklus Status

**Versi:** `draft` → `published` → `retired`. Hanya `draft` yang dapat diubah. Publikasi versi baru memensiunkan versi `published` sebelumnya untuk jenis yang sama. Sesi lama tetap dapat dibaca.

**Sesi:**

```
in_progress ──(kirim, skor dihitung)──► submitted ──(admin publish)──► published
     │                                                                    ▲
     └──(batas waktu lewat)──► expired ──(admin publish, bila ada skor)──┘
```

- `in_progress` → `expired` dengan jawaban lengkap: dinilai, status `expired`, tetap bisa dipublikasikan.
- `in_progress` → `expired` dengan jawaban belum lengkap: ditutup **tanpa skor**. Hasil tidak dibuat dari data parsial.
- Transisi diperiksa `StateTransition::assert()` lewat `TransitionsState`.

## 4. Endpoint

### Peserta (`/api/v1/pmb/applicant`, token `applicant`, `throttle:pmb-applicant`)

| Method | Path | Catatan |
|---|---|---|
| GET | `applications/{id}/assessments` | Daftar asesmen beserta `can_start`, `blocked_reason`, dan status sesi. |
| POST | `applications/{id}/assessments/consent` | Mencatat `assessment_talent` di `pmb_account_consents`. Idempoten. Hanya akun terverifikasi. |
| POST | `applications/{id}/assessments/{versionId}/sessions` | Mulai sesi, atau mengembalikan sesi yang sudah ada. Hanya akun terverifikasi. |
| GET | `assessment-sessions/{sessionId}` | Status, butir (`id`, `stem`, `value`), batas waktu, `server_time`. |
| PUT | `assessment-sessions/{sessionId}/responses` | Body `responses: [{item_id, value}]`. Upsert, aman dikirim ulang. |
| POST | `assessment-sessions/{sessionId}/submit` | Hanya bila semua butir sudah dijawab. Idempoten. |
| GET | `assessment-sessions/{sessionId}/result` | `409 RESULT_NOT_PUBLISHED` sebelum dipublikasikan. |

Akses sesi selalu lewat relasi akun: milik orang lain menghasilkan 404.

### Admin (`/api/v1/pmb/admin`, `pmb.staff`, `tenant.access`, permission per rute)

| Method | Path | Permission |
|---|---|---|
| GET | `assessment-types` | `pmb_assessments.read` |
| POST | `assessment-types` | `pmb_assessments.update` |
| PATCH | `assessment-types/{type}` | `pmb_assessments.update` |
| GET | `assessment-types/{type}/versions` | `pmb_assessments.read` |
| POST | `assessment-types/{type}/versions` | `pmb_assessments.update` |
| GET | `assessment-versions/{version}` | `pmb_assessments.read` |
| PATCH | `assessment-versions/{version}` | `pmb_assessments.update` (draft) |
| POST | `assessment-versions/{version}/publish` | `pmb_assessments.publish` |
| POST | `assessment-versions/{version}/dimensions` | `pmb_assessments.update` (draft) |
| PATCH / DELETE | `assessment-dimensions/{dimension}` | `pmb_assessments.update` (draft) |
| POST | `assessment-versions/{version}/items` | `pmb_assessments.update` (draft) |
| PATCH / DELETE | `assessment-items/{item}` | `pmb_assessments.update` (draft) |
| GET | `assessment-versions/{version}/sessions` | `pmb_assessment_results.read` |
| GET | `assessment-sessions/{session}` | `pmb_assessment_results.read` |
| POST | `assessment-sessions/{session}/publish` | `pmb_assessment_results.publish` |

Peran yang mendapat izin ini: `admission_administrator` (lewat `PMB_ADMIN` di `OrganizationalRoleSeeder`). Peran lain tidak berubah.

## 5. Aturan Publikasi Versi

Versi hanya dapat dipublikasikan bila:

1. Status `draft`.
2. Tujuan `exploratory`. Tujuan `admissions` ditolak dengan `ADMISSIONS_NOT_APPROVED` (prompt bagian 17: jangan aktifkan scoring admissions sebelum validasi dan persetujuan).
3. Domain sudah punya scoring engine (`DOMAIN_NOT_SUPPORTED` bila belum).
4. Dimensi lengkap sesuai domain. Untuk minat: tepat R, I, A, S, E, C (`DIMENSIONS_INCOMPLETE`).
5. Setiap dimensi memiliki minimal satu butir (`ITEMS_INCOMPLETE`).

Publikasi dicatat di audit log lewat `HistoryRecorder::change()`.

## 6. Scoring `riasec-mean-v1`

Implementasi: `app/Modules/Admission/Services/Assessment/Scoring/RiasecScorer.php`.

1. Butir dengan `is_reversed` dihitung sebagai `min + max − nilai`. Pada skala 1–5: `6 − nilai`.
2. Skor mentah dimensi = jumlah nilai yang sudah disesuaikan.
3. Rata-rata = skor mentah ÷ jumlah butir dimensi. **Rata-rata yang dipakai untuk peringkat**, sehingga dimensi dengan lebih banyak butir tidak otomatis unggul.
4. Persentase = `(rata-rata − min) ÷ (max − min) × 100`. Deskriptif, **bukan norma**.
5. Peringkat: rata-rata menurun. Seri diurutkan dengan urutan tetap R, I, A, S, E, C.
6. Holland Code = tiga huruf teratas. Dimensi dominan = tiga teratas.

Yang **tidak** dilakukan (menunggu pengesahan fakultas dan validasi psikometrik): bobot, norma, transformasi skor terstandar, dan interpretasi otomatis. Interpretasi yang tampil adalah teks yang ditulis admin di `interpretation`; bila kosong, peserta melihat `description`.

Scorer menolak input yang tidak lengkap (`INCOMPLETE_INSTRUMENT`), nilai di luar skala (`INVALID_VALUE`), dan kode dimensi yang tidak dikenal (`INVALID_DIMENSION`).

Saat sesi dikirim, skor dihitung dan disimpan di transaksi yang sama dengan perubahan status, sehingga tidak ada keadaan setengah jadi.

## 7. Keamanan dan Integritas

| Risiko | Mitigasi |
|---|---|
| Kunci atau dimensi butir bocor ke peserta | `AssessmentSessionService::payload()` hanya mengirim `id`, `stem`, `value`. Dicek di tes (`not->toHaveKeys(['dimension','is_reversed'])`). |
| Mulai dua sesi bersamaan | `lockForUpdate()` pada baris pendaftaran, plus unique `(application_id, version_id)`. |
| Jawaban diubah setelah dikirim atau waktu habis | `assertOpen()` di transaksi yang mengunci baris sesi. |
| Waktu habis hanya dicek di klien | Batas waktu dicek di server (`expireIfDue`) pada setiap akses dan setiap simpan atau kirim. |
| Jawaban diubah setelah publikasi instrumen | `assertDraft()` pada setiap perubahan konfigurasi. |
| Hasil dibaca sebelum dipublikasikan | `result()` hanya untuk `published`. |
| Pendaftaran orang lain | Semua akses lewat relasi akun (`applications()`), 404 untuk milik orang lain. |
| Pendaftaran belum dikirim atau ditarik | `problem()`: `APPLICATION_NOT_SUBMITTED`, `APPLICATION_WITHDRAWN`. |
| Versi di luar periode atau dipensiunkan | `NOT_OPEN`, `CLOSED`, `NOT_AVAILABLE`, `PERIOD_MISMATCH`. |
| Persetujuan belum ada | `CONSENT_REQUIRED`. Persetujuan dicatat dengan IP dan user agent. |
| Jawaban terputus | Simpan per batch tiap 2 detik di klien, dicoba ulang. Upsert membuat pengiriman ulang aman. |

## 8. Frontend (Peserta)

Rute di `frontend/src/pmb/routes.ts`:

| Rute | Halaman | Layout |
|---|---|---|
| `/pmb/portal/pendaftaran/:id/asesmen` | `AssessmentListPage` | Portal |
| `/pmb/portal/pendaftaran/:id/asesmen/:versionId` | `AssessmentStartPage` (instruksi, persetujuan, mulai) | Portal |
| `/pmb/portal/asesmen/sesi/:sessionId` | `AssessmentSessionPage` (pengerjaan, layar penuh seperti CBT) | Layar penuh |
| `/pmb/portal/asesmen/sesi/:sessionId/hasil` | `AssessmentResultPage` (Holland Code, radar, interpretasi) | Portal |

Tautan "Asesmen minat & bakat" ditambahkan di kartu pendaftaran pada beranda portal.

Perilaku pengerjaan:
- Pilihan Likert 1–5 dengan label, bisa dengan mouse, keyboard (Tab dan radio), atau tombol angka 1–5 di keyboard.
- Navigasi nomor, tombol sebelumnya dan berikutnya, penanda belum dijawab.
- Progress bar dengan `role="progressbar"`.
- Timer dari `server_time` (bukan jam perangkat) bila `deadline_at` ada. Saat waktu habis, halaman langsung mengirim.
- Konfirmasi sebelum kirim. Tombol kirim nonaktif bila masih ada butir kosong.
- Banner saat `navigator.onLine` false. Galat simpan ditampilkan dan dicoba ulang.
- 409 saat menyimpan memuat ulang status sesi dari server.
- Label state: loading, empty (`Belum ada asesmen`), error, dan success (`Tersimpan`, `Asesmen sudah dikirim`).

## 9. Cara Menjalankan dan Menguji

Tidak ada konfigurasi environment baru. Migrasi wajib dijalankan di lingkungan tujuan:

```bash
php artisan migrate
```

> Jangan menjalankan migrasi dari `.env` lokal, karena file itu mengarah ke MySQL produksi (lihat catatan memori proyek). Gunakan lingkungan yang sudah disetujui atau SQLite terisolasi.

Uji backend (SQLite in-memory dari `phpunit.xml`, tidak menyentuh `.env`):

```bash
php artisan test --filter=Assessment
php artisan test                      # suite penuh: 452 tes lulus setelah perubahan ini
```

Jika output `php artisan test` kosong dan keluar dengan kode 1, jalankan dengan `PAO_DISABLE=1` agar error fatal terlihat.

Uji frontend:

```bash
cd frontend
npx tsc -p tsconfig.app.json --noEmit    # type-check tanpa menulis dist
npx vite build --outDir <folder-sementara> --emptyOutDir   # build tanpa menyentuh frontend/dist
```

`npm run build` menulis ulang `frontend/dist` yang di-track git. Hindari bila perubahan itu tidak diinginkan.

## 10. Daftar File

**Dibuat**

- `app/Modules/Admission/Database/Migrations/2026_11_05_100000_create_pmb_assessment_tables.php`
- `app/Modules/Admission/Enums/AssessmentDomain.php`, `AssessmentPurpose.php`, `AssessmentVersionStatus.php`, `AssessmentSessionStatus.php`
- `app/Modules/Admission/Models/Assessment{Type,Version,Dimension,Item,Session,Response,Score,SessionEvent}.php`
- `app/Modules/Admission/Services/Assessment/AssessmentConfigurationService.php`
- `app/Modules/Admission/Services/Assessment/AssessmentSessionService.php`
- `app/Modules/Admission/Services/Assessment/Scoring/RiasecScorer.php`
- `app/Modules/Admission/Controllers/Applicant/AssessmentController.php`
- `app/Modules/Admission/Controllers/Admin/AssessmentController.php`
- `app/Modules/Admission/Tests/Unit/RiasecScorerTest.php`
- `app/Modules/Admission/Tests/Feature/AssessmentTalentTest.php`
- `frontend/src/pmb/pages/portal/AssessmentPages.tsx`
- `docs/one-atma-talent/01-audit-dan-rencana-implementasi.md`, `02-slice-1-riasec.md`

**Diubah**

- `app/Modules/Admission/Enums/ConsentType.php`: nilai `assessment_talent`.
- `app/Modules/Admission/Routes/api.php`: rute peserta dan admin.
- `app/Modules/Admission/Support/PmbPermission.php`: konstanta dan `MAP`.
- `app/Modules/UserManagement/Database/Seeders/RolePermissionSeeder.php`: katalog izin.
- `app/Modules/UserManagement/Database/Seeders/OrganizationalRoleSeeder.php`: `PMB_ADMIN`.
- `frontend/src/pmb/api/applicant.ts`: tipe dan method API.
- `frontend/src/pmb/routes.ts`: konstanta rute.
- `frontend/src/pmb/PmbRoutes.tsx`: registrasi rute.
- `frontend/src/pmb/pages/portal/PortalPages.tsx`: tautan di kartu pendaftaran.

## 11. Belum Selesai dan Alasannya

| Item | Alasan / prasyarat |
|---|---|
| Halaman admin (CRUD jenis, versi, dimensi, butir; monitoring; publikasi hasil) | Dikerjakan setelah API stabil. Sementara admin memakai API. |
| Penjadwal `expireDue()` | Metode sudah ada, tetapi belum dijadwalkan di `routes/console.php` atau `pmb:tick`. Sesi yang kedaluwarsa tetap ditutup saat diakses. |
| Persetujuan dua langkah untuk publikasi versi | Perlu keputusan kebijakan (siapa yang menyetujui) dan integrasi `ApprovalWorkflow`. |
| Domain aptitude, academic readiness, SJT, aspirasi | Memerlukan kunci jawaban atau konten dari ahli. Tidak dibuat dengan data karangan. |
| Norma, bobot, transformasi skor | Menunggu expert review, pilot testing, item analysis, dan validasi (prompt bagian 7). |
| Matching program studi | Fase 5. Butuh profil prodi dan aturan matching yang disahkan fakultas. |
| Penggunaan untuk seleksi (admissions) | Dilarang sampai validasi dan approval. Versi `admissions` ditolak saat publikasi. |
| Pengawasan ujian (kamera, pelanggaran, jeda) | Belum ada keputusan kebijakan. |
| Pengujian aksesibilitas (WCAG 2.2 AA) dan uji browser end-to-end | Belum dijalankan. Tes UI di repo belum mencakup halaman ini. |
| Tes SQL MySQL | Semua tes berjalan di SQLite. Migrasi memakai pola `MigrationRecovery` seperti migrasi PMB lain, tetapi belum diuji di MySQL. |
| Revisi rancangan PMB | `docs/RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md` (baris 161) menyebut tes potensi sebagai layanan eksternal. Perlu diperbarui setelah keputusan ini disahkan. |
