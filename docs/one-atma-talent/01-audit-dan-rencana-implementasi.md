# One Atma Talent — Audit Project dan Rencana Implementasi

> **Per:** 5 November 2026, branch `feat/attendance-qr-checkin`.
> **Lingkup dokumen ini:** hasil Phase 1 (audit) dan rencana fase untuk fitur asesmen minat & bakat yang terintegrasi dengan PMB. Dokumen ini ditulis sebelum perubahan kode dan diperbarui setiap fase selesai.

## 1. Ringkasan Audit

### 1.1 Stack dan pola yang sudah dipakai

| Aspek | Kondisi di repository |
|---|---|
| Backend | Laravel 13 (`composer.json`: PHP `^8.3`), Sanctum, Pest 4. Modul per domain di `app/Modules/*`. |
| Multi-tenant | Kolom `university_id` di setiap tabel tenant; trait `App\Support\Tenancy\TenantScoped` memasang global scope dan mengisi `university_id` dari `TenantContext`. |
| Modul PMB | `app/Modules/Admission` — tabel `pmb_*`, primary key ULID, basis model `PmbModel` (ULID + tenant scope). |
| Autentikasi pendaftar | Guard token `ApplicantAccount` (ability `applicant`), middleware `pmb.applicant[:verified]`, `pmb.public`, `pmb.idempotent`, rate limit `pmb-applicant` (120/menit). |
| Autentikasi staf | Middleware `pmb.staff`, `tenant.access`, `permission:<slug>` per rute. |
| Rute API | `app/Modules/Admission/Routes/api.php`: `/pmb/public`, `/pmb/applicant`, `/pmb/admin`. Pola controller: `Controllers/Applicant/*` (ownership lewat relasi akun, 404 untuk milik orang lain) dan `Controllers/Admin/*`. |
| Respons & error | `App\Support\Http\ApiResponse::success/error`, `DomainRuleException` (kode error konsisten). |
| Frontend | React 19, Vite 8, Tailwind 3, react-router-dom 7, recharts 3 (sudah ada), react-hook-form + zod. Komponen UI di `frontend/src/components/ui/*`. |
| Frontend PMB | `frontend/src/pmb/`: rute di `PmbRoutes.tsx` dan `routes.ts`, klien API `api/applicant.ts` dan `api/admin.ts`, hook `useLoad` dan `useAction`, layout `PortalLayout`. CBT memakai halaman layar penuh di luar layout (`pages/portal/StagePages.tsx`). |
| Permission | Katalog `Modules\Admission\Support\PmbPermission::MAP` dan `Modules\UserManagement\Database\Seeders\RolePermissionSeeder` (`PMB_ADMIN` dan daftar per role `admission_*`). |
| Persetujuan | `ConsentType` (enum) dan `pmb_account_consents` (kolom `consent_type` string 40) lewat `AccountService::recordConsent()`. |
| Audit | `Modules\AuditLog\Services\AuditLogService`. Akses data sensitif PMB lewat `SensitiveAccessLogger`. |

### 1.2 Entitas yang sudah ada dan akan dipakai ulang

| Kebutuhan One Atma Talent | Sudah ada | Keputusan |
|---|---|---|
| Identitas calon mahasiswa | `pmb_applicant_accounts` (akun), `pmb_applicant_profiles` (profil), `pmb_applications` (pendaftaran per akun per gelombang-jalur) | **Dipakai ulang.** Sesi asesmen menunjuk `pmb_applications.id`. Tidak ada tabel identitas peserta baru. |
| Periode PMB | `pmb_periods` | Dipakai sebagai batas berlaku versi asesmen (opsional). |
| Program studi | `Modules\Academic\Models\StudyProgram`; profil publik `pmb_program_profiles` | Belum dipakai di fase ini. Dibutuhkan untuk matching engine (fase 5). |
| Fakultas | `Modules\Academic\Models\Faculty` | Belum dipakai di fase ini. |
| Persetujuan pemrosesan data | `pmb_account_consents` + `ConsentType` | **Dipakai.** Tambah satu nilai enum `assessment_talent`. |
| Ujian dengan timer, soal dibekukan, kunci tidak dikirim ke klien | `Modules\Admission\Services\Exam\CbtService` (`pmb_cbt_questions`, `pmb_cbt_attempts`) | **Pola diacu**, tidak dipakai langsung. CBT PMB terikat jadwal ujian (`pmb_exam_schedules`), sedangkan asesmen talent berjalan mandiri secara online. |
| Ujian mata kuliah | `Modules\Academic` (`exams`, `exam_questions`, `question_bank_items`) | **Tidak dipakai.** Terikat `class_section` dan KRS, bukan calon mahasiswa. |
| Skema seleksi & komponen skor | `pmb_selection_schemes`, `pmb_selection_components` (`ComponentSource::SpecialTest`, `Cbt`) | Titik integrasi untuk admissions scoring di masa depan. Belum dihubungkan. |
| Log audit | `AuditLogService`, `pmb_*_logs` | Dipakai untuk perubahan konfigurasi versi. Di fase ini riwayat sesi dicatat di `pmb_assessment_session_events`. |

### 1.3 Temuan yang perlu diperhatikan

1. **Konflik dengan rancangan PMB yang ada.** `docs/RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md` (baris 161) menyebut *"Tes potensi & try out (Maukuliah) — Layanan eksternal SEVIMA; tidak dibangun."* Permintaan ini membangun tes minat & bakat di dalam One Atma. Dokumen rancangan PMB perlu direvisi setelah keputusan ini disahkan. Implementasi di bawah mengikuti permintaan terbaru.
2. **Tidak ada konten instrumen.** Tidak ada bank soal RIASEC, aptitude, atau SJT di repository. Sistem tidak menyertakan butir soal buatan sendiri sebagai instrumen produksi. Admin atau tim ahli harus menyusun butir, melakukan expert review, pilot testing, dan item analysis sebelum versi dipublikasikan.
3. **Aturan admissions belum boleh aktif.** Sesuai bagian 17 prompt, scoring untuk admissions tidak diaktifkan sebelum validasi dan approval. Versi dengan `purpose = admissions` ditolak saat publikasi.
4. **Pemetaan asesmen per prodi** (bagian 4 prompt) membutuhkan approval fakultas. Belum dikerjakan.
5. **Tes berbatas waktu dan pengawasan.** Timer server-side sudah ada di pola CBT. Pengawasan (kamera, pelanggaran, jeda) belum diputuskan.

## 2. Keputusan Desain

| # | Keputusan | Alasan |
|---|---|---|
| D1 | Tabel memakai prefiks `pmb_assessment_*` dan kode berada di `app/Modules/Admission`. | Konvensi modul PMB. Tidak membuat modul baru yang memisahkan tabel dari identitas pendaftar. |
| D2 | Sesi menunjuk `application_id` ke `pmb_applications`. | Prompt meminta hubungan ke data pendaftar yang sudah ada. |
| D3 | Versi instrumen tidak dapat diubah setelah dipublikasikan. Perubahan dibuat sebagai versi baru. | Respons lama tetap menunjuk butir versi lama. Histori respons tidak hilang. |
| D4 | `purpose` (`exploratory` / `admissions`) adalah kolom pada versi. Versi `admissions` tidak bisa dipublikasikan di fase ini. | Pemisahan konfigurasi dan hak akses. Mencegah scoring admissions aktif tanpa validasi. |
| D5 | Dimensi dan arah skor setiap butir tidak dikirim ke frontend. | Mencegah pengaturan jawaban berdasarkan bobot. |
| D6 | Scoring RIASEC ditulis sebagai kelas PHP murni (`RiasecScorer`) tanpa akses database. Dasar peringkat dimensi adalah **rata-rata** respons, bukan jumlah. Seri diurutkan dengan urutan tetap R-I-A-S-E-C. Holland Code diambil dari tiga dimensi teratas. | Scoring terpisah dari controller dan frontend, dan bisa diuji dengan dataset. Rata-rata tidak bias oleh jumlah butir per dimensi. Tidak ada norma atau transformasi yang diterapkan karena belum disahkan. |
| D7 | Hasil hanya tampil ke peserta setelah sesi berstatus `published`. Publikasi dilakukan admin per sesi. | Prompt: hasil hanya dapat dilihat setelah status publikasi terpenuhi. |
| D8 | Satu sesi per pasangan (pendaftaran, versi). Timer bersifat opsional (`duration_minutes` nullable). | Mencegah pengulangan tes. Timer hanya dipasang bila versi memerlukannya. |
| D9 | Syarat peserta: pendaftaran sudah terkirim (`submitted_at`), tidak ditarik, akun aktif dan terverifikasi, versi berstatus `published`, dan berada dalam `available_from` / `available_until` serta `period_id` bila diisi. | Mengikuti kebijakan periode dan syarat peserta dari bagian 4 dan 5 prompt. |
| D10 | Persetujuan pemrosesan data memakai `ConsentType::AssessmentTalent` lewat `AccountService::recordConsent()`. | Konsisten dengan persetujuan PMB yang sudah ada. |
| D11 | Hak akses: `pmb_assessments.{read,update,publish}` untuk konfigurasi versi, dan `pmb_assessment_results.{read,publish}` untuk hasil peserta. | Pemisahan konfigurasi dan hasil sesuai prompt bagian 6 (Governance). |
| D12 | Tidak ada seeder berisi butir soal. | Mencegah dummy data menjadi instrumen produksi (bagian 17 prompt). |

## 3. Cakupan Fase Ini (Slice 1)

Slice 1 mengerjakan satu domain end-to-end: **Interest Assessment (RIASEC)** dengan skala Likert 1–5.

| Bagian | Dikerjakan di slice 1 | Ditunda |
|---|---|---|
| Konfigurasi | Tipe asesmen, versi (draft → published), dimensi, butir Likert, periode berlaku, timer opsional | Blueprint lintas domain, scoring rule versioning, norma |
| Peserta | Daftar asesmen untuk pendaftaran, persetujuan, mulai sesi, simpan respons per batch, kirim, lihat hasil setelah publikasi | Pemulihan koneksi lanjutan (offline queue), aksomodasi waktu, jadwal on-site |
| Scoring | `RiasecScorer`: skor mentah dan rata-rata per dimensi, tiga dimensi dominan, Holland Code | Transformasi yang disahkan, norma, interpretasi berbasis aturan |
| Governance | Permission katalog, publikasi versi dan sesi, event sesi | Persetujuan dua langkah lewat `ApprovalWorkflow`, log akses khusus, retensi |
| Frontend | (Lihat status di bagian 5) | Dashboard admin, monitoring, analitik |

## 4. Rencana Fase dan Dependensi

| Fase | Isi | Dependensi | Status |
|---|---|---|---|
| 1. Audit | Dokumen ini | — | Selesai |
| 2. Database & backend | Migrasi `pmb_assessment_*`, model, enum, service, API, permission | Fase 1 | Slice 1 dan Fase 2 selesai untuk RIASEC |
| 3. Admin | Konfigurasi versi, dimensi, butir, publikasi, monitoring sesi | Fase 2 | Selesai untuk RIASEC. Lihat [03](03-admin-management.md) |
| 4. Peserta | Dashboard asesmen, persetujuan, halaman pengerjaan, halaman hasil | Fase 1 | Selesai untuk RIASEC (slice 1) |
| 5. Scoring & matching | Scoring RIASEC (slice 1), lalu aptitude, academic readiness, SJT, aspirasi, matching engine | Fase 2, data program studi | RIASEC selesai; sisanya belum |
| 6. Hybrid & reporting | On-site, konselor, analitik | Fase 3–5 | Belum |
| 7. QA | Uji otomatis, keamanan, aksesibilitas | Semua fase | Uji otomatis dan verifikasi statis selesai untuk Fase 2. MySQL dan aksesibilitas belum. Lihat [07](07-testing-and-verification.md) |

## 5. Status Implementasi

Diperbarui setelah setiap fase. Lihat `02-slice-1-riasec.md` untuk Slice 1 dan `03`–`07` untuk Fase 2.

| Area | Status |
|---|---|
| Migrasi & model `pmb_assessment_*` | Selesai (lihat 02) |
| Scoring RIASEC | Selesai, diuji unit (6 tes) |
| API admin konfigurasi & publikasi | Selesai, diuji feature |
| API peserta | Selesai, diuji feature |
| Halaman peserta (daftar, mulai, pengerjaan, hasil) | Selesai, tipe dan build tervalidasi; belum diuji di browser |
| Halaman admin (dashboard, asesmen, versi, bank soal, peserta, sesi, review, audit) | Selesai untuk RIASEC. Lihat [03](03-admin-management.md) |
| Persetujuan versi dua pihak | Selesai lewat ApprovalWorkflow. Lihat [05](05-versioning-and-approval.md) |
| Review dan publikasi hasil | Selesai. Lihat [06](06-result-review-publication.md) |
| Penjadwal penutupan sesi kedaluwarsa | Selesai lewat `pmb:tick`. Lihat [04](04-session-expiration.md) |

## 6. Baseline Pengujian

Sebelum perubahan dijalankan `php artisan test` tanpa paralel pada tree bersih (SQLite in-memory dari `phpunit.xml`, tidak menyentuh `.env`).

- Hasil baseline: 440 tes lulus, 3567 assertion, 0 gagal (sekitar 82 detik).
