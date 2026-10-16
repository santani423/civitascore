# 03 — Aktor, Role, dan Hak Akses

> **Status:** rancangan · Disusun 2026-10-13 (commit `47043ca`) · Kembali ke [README](README.md)
> **Dokumen acuan role & permission.** Mengikuti RBAC yang ada (`Modules\UserManagement`): `roles`, `permissions`, `role_permissions`, `user_roles` (`scope_type`, `scope_id`, `expires_at`, `university_id`), slug `resource.action`, action dari enum `PermissionAction` (`create, read, update, delete, approve, reject, export, import, publish, finalize`), middleware `permission:a,b` (OR), dan bypass `super_admin`.

## 1. Konsep yang Dibedakan

| Konsep | Arti | Sumber | Contoh |
|---|---|---|---|
| **Paket** | Universitas boleh memakai fitur | `plan_feature_entitlements` ([34](34-matriks-paket-dan-entitlement.md)) | `career.articles` mulai Professional |
| **Tenant** | Universitas tempat data berada | `university_id`, `TenantContext` (Ada) | — |
| **Role** | Kumpulan permission sesuai jabatan | `roles` (template global `university_id = null`, Ada) | `graduation_officer` |
| **Permission** | Hak atas resource + aksi | `permissions` | `yudisium_registrations.approve` |
| **Data scope** | Batas data yang dapat disentuh grant role | `user_roles.scope_type/scope_id` | Admin prodi Informatika (`study_program`, id) |
| **Fakultas / prodi** | Unit organisasi akademik | `faculties`, `study_programs` (Ada) | — |
| **Kepemilikan** | Data milik pengguna sendiri | `$user->student` (Ada) | Pengajuan aktivitas saya |
| **Persetujuan tindakan** | Langkah ApprovalWorkflow untuk aksi tertentu | `approval_workflows` (Ada) | Pengesahan keputusan kelulusan |
| **Konteks perusahaan** | Hak anggota perusahaan | `career_company_members.role` (Baru) | `company_recruiter` |

Satu request harus lolos **semua** lapis yang relevan ([28 §3](28-keamanan-privasi-dan-audit.md#3-isolasi-tenant-dan-lapis-otorisasi)).

### 1.1 Jenis scope

| Kode | Scope | Penentu |
|---|---|---|
| `u` | Seluruh universitas | Grant tanpa scope |
| `f` | Fakultas | `scope_type = faculty` |
| `p` | Program studi | `scope_type = study_program` |
| `w` | Mahasiswa perwalian | `students.academic_advisor_id` (Ada) |
| `b` | Mahasiswa bimbingan tugas akhir | `theses.supervisor_lecturer_id` (Ada) |
| `s` | Milik sendiri | `students.user_id` (Ada) |
| `t` | Ditugaskan | Mis. panitia seleksi beasiswa, petugas check-in sesi |
| `c` | Perusahaan | `career_company_members` |

Scope `f`/`p` bergantung pada `AcademicScope` (rancangan `academic-learning`, belum ada). Sampai tersedia, role dengan scope `f`/`p` **tidak diberikan** di produksi ([KK-A-08](33-glossary-dan-keputusan-bisnis.md#4-asumsi-kk-a)).

## 2. Katalog Role

| Aktor (kebutuhan) | Role slug | Status | Scope | Keterangan |
|---|---|---|---|---|
| Super Admin | `super_admin` | Ada | Platform | Konfigurasi platform, entitlement, template standar. Tidak menjalankan proses tenant sehari-hari |
| Admin universitas | `university_administrator` (+ `university_owner`) | Ada | `u` | Pengaturan tenant, melihat ringkasan. **Tidak** otomatis mendapat akses data disiplin, identitas tracer, atau CV |
| Admin fakultas | `faculty_administrator` | Baru (dirancang `academic-learning`) | `f` | Verifikasi yudisium fakultas, buku wisuda unit, laporan unit |
| Admin program studi | `study_program_administrator` | Baru (dirancang `academic-learning`) | `p` | Verifikasi aktivitas tingkat prodi, verifikasi yudisium prodi, SKPI prodi, agregat tracer prodi |
| Kepala bagian kemahasiswaan | `student_affairs_manager` | Baru | `u` | Master poin, override, banding, auto-award, beasiswa |
| Petugas kemahasiswaan | `student_affairs_officer` | Baru | `u`/`f` | Verifikasi aktivitas, koreksi poin, beasiswa operasional |
| Petugas disiplin | `discipline_officer` | Baru | `u`/`f` | Laporan, kasus, draf keputusan |
| Anggota komisi disiplin | `discipline_committee_member` | Baru | `t` | Kasus yang ditugaskan |
| Panitia seleksi beasiswa | `scholarship_reviewer` | Baru | `t` | Menilai pendaftar yang ditugaskan |
| Petugas BAAK | `academic_administrator` | Ada | `u` | Surat aktif (Ada via approval), verifikasi akhir yudisium |
| Petugas kelulusan | `graduation_officer` | Baru | `u` | Periode, aturan, eligibility, rapat, finalisasi, dokumen kelulusan |
| Panitia wisuda | `graduation_committee` | Baru | `u` | Wisuda & buku |
| Petugas check-in | `ceremony_checkin_officer` | Baru | `t` (sesi) | Check-in |
| Petugas SKPI | `skpi_officer` | Baru | `p`/`u` | Verifikasi capaian & terjemahan, penyusunan, penerbitan |
| Admin dokumen | `document_administrator` | Baru | `u` | Template, penomoran, penandatangan |
| Pusat karier | `career_center_administrator`, `career_center_officer` | Baru | `u` | Karirlink |
| Petugas tracer study | `tracer_officer` | Baru | `u` | Tracer |
| Dosen | `lecturer` | Ada | `b` | Melihat status yudisium mahasiswa bimbingan |
| Dosen wali | `academic_advisor` | Ada | `w` | Melihat poin, ringkasan sanksi final (bila diizinkan), status eligibility |
| Mahasiswa aktif | `student` | Ada | `s` | Layanan mandiri |
| Mahasiswa tingkat akhir | `student` + **kondisi data** | — | `s` | Bukan role. Fitur yudisium/SKPI tampil bila ada periode terbuka atau hasil discovery calon |
| Alumni | `alumni` | Baru | `s` | Diberikan saat finalisasi kelulusan. `student` tetap (status `graduated` tidak diblokir login, Ada) |
| Perusahaan (pemilik akun) | `company_owner` | Baru (keanggotaan perusahaan, bukan `roles`) | `c` | |
| Perekrut | `company_recruiter` | Baru (keanggotaan perusahaan) | `c` | |
| Pimpinan | `rector`, `vice_rector`, `dean`, `head_of_study_program` | Ada | `u`/`f`/`p` | Dashboard agregat, penyetuju sesuai workflow |
| Auditor | `auditor` | Ada | `u` | Baca + audit, tanpa ubah |
| Moderator karier platform | `career_platform_moderator` | Baru (platform) | Platform | Verifikasi perusahaan, moderasi marketplace |
| Kurator template survei | `survey_template_curator` | Baru (platform) | Platform | Template tracer standar |

## 3. Katalog Permission

Resource baru (semua action dari enum `PermissionAction`). Makna action non-CRUD: `approve` = menyetujui/memverifikasi, `reject` = menolak, `publish` = memberlakukan/menerbitkan/membuka, `finalize` = mengunci proses, `import`/`export` = berkas.

| Kelompok | Resource → action |
|---|---|
| Kemahasiswaan | `activity_categories` (read, create, update, delete) · `activity_events` (read, create, update, approve) · `activity_submissions` (read, update, approve, reject, export) · `student_points` (read, update, approve, export) · `point_appeals` (read, approve, reject) · `violation_types` (read, create, update) — juga mengatur `sanction_types` · `violation_reports` (read, create, update) · `violation_cases` (read, create, update, export) · `disciplinary_decisions` (read, create, approve, reject) · `sanction_appeals` (read, approve, reject) · `student_affairs_reports` (read, export) · `student_affairs_self_service` (read, create, update) |
| Beasiswa | `scholarships` (**Ada: read**; + create, update, publish) · `scholarship_applications` (read, update, approve, reject, export) · `scholarship_awards` (read, create, update, approve, reject, export) |
| Dokumen | `document_templates` (read, create, update, publish) · `document_numbering_schemes` (read, create, update) · `document_signatories` (read, create, update) · `issued_documents` (read, publish, import, export) · `document_revocations` (read, create, approve, reject) |
| Yudisium | `yudisium_periods` (read, create, update, publish, finalize) · `graduation_requirement_sets` (read, create, update, publish) · `graduation_eligibility` (read, create, export) · `graduation_requirement_exemptions` (read, create, approve, reject) · `yudisium_registrations` (read, create, update, approve, reject, export) · `yudisium_meetings` (read, create, update, finalize) · `graduation_decisions` (read, approve, finalize, export) · `graduation_decision_corrections` (read, create, approve, reject) · `graduation_honor_rules` (read, create, update, publish) · `graduation_self_service` (read, create, update) |
| Wisuda | `graduation_ceremonies` (read, create, update, publish, finalize) · `ceremony_sessions` (read, create, update) · `ceremony_registrations` (read, update, approve, reject, export) · `ceremony_attendance` (read, create) · `graduation_books` (read, create, update, publish, export) · `graduation_book_corrections` (read, create) |
| SKPI | `skpi_categories` (read, create, update) · `skpi_glossary` (read, create, update, approve) · `skpi_templates` (read, create, update, publish) · `skpi_achievements` (read, approve, reject) · `skpi_documents` (read, create, update, approve, reject, publish, export) · `skpi_self_service` (read, create, update) |
| Tracer | `tracer_periods` (read, create, update, publish, finalize) · `tracer_targets` (read, create, update, import, export) · `tracer_questionnaires` (read, create, update, publish) · `tracer_invitations` (read, create) · `tracer_responses` (read, export) · `tracer_respondent_identity` (read, export) · `tracer_reports` (read, export) · `tracer_quota` (read) · `alumni` (**Ada: read**) · `alumni_career_records` (read, create) · `alumni_self_service` (read, update) |
| Karier | `career_portal_settings` (read, update) · `career_articles` (read, create, update, publish) · `career_tracer_publications` (read, create, approve) · `career_dashboard` (read) · `career_profiles` (read) · `career_company_invitations` (read, create) · `career_partnerships` (read, create, update) · `career_crm` (read, create, update) · `career_job_postings` (read, create, update, approve, reject, publish) · `career_applications` (read, update, export) · `career_recommendations` (read, create, update, approve, reject) · `career_self_service` (read, create, update) |
| Platform | `platform_billing` (Ada) · `platform_survey_templates` (read, create, update, publish) · `platform_career` (read, update, approve, reject) |

Pembatasan per jenis dokumen untuk `issued_documents.publish` memakai `document_issuer_assignments` ([13 §17](13-rancangan-penomoran-dokumen-kelulusan.md#17-keamanan-dan-audit)).

## 4. Matriks Hak Akses

Legenda: **L** lihat · **B** buat · **U** ubah · **V** verifikasi · **S** setujui · **T** terbitkan/publikasikan · **X** batalkan/cabut · **E** ekspor · **H** hapus. Huruf kecil di belakang = scope (`u`,`f`,`p`,`w`,`b`,`s`,`t`,`c`). "—" = tidak ada akses. "Wf" = hanya bila ditetapkan sebagai langkah ApprovalWorkflow.

### 4.1 Kemahasiswaan

| Role | Kategori & aturan poin | Pengajuan aktivitas | Poin (ledger) | Pelanggaran (laporan/kasus) | Keputusan sanksi | Beasiswa program | Pendaftar & penerima beasiswa | Surat aktif |
|---|---|---|---|---|---|---|---|---|
| `super_admin` | L | — (konteks dukungan saja) | — | — | — | — | — | — |
| `university_administrator` | L | L (agregat) | L (agregat) | — | — | L | L agregat | — |
| `student_affairs_manager` | L B U H(draf) | L V X | L U S E | L | — | L B U T | L S X E | — |
| `student_affairs_officer` | L | L V (u/f) | L U(koreksi) | — | — | L B U | L V U | — |
| `discipline_officer` | — | — | — | L B U E | B (draf) | — | — | — |
| `discipline_committee_member` | — | — | — | L U (t) | — | — | — | — |
| Pejabat disiplin (Wf) | — | — | — | L | S X | — | — | — |
| `scholarship_reviewer` | — | — | — | — | — | L | L U (t) | — |
| Pejabat penetap beasiswa (Wf) | — | — | — | — | — | L | S X | — |
| `academic_administrator` | — | — | — | — | — | — | — | L S(Wf) T |
| `faculty_administrator` / `study_program_administrator` | L | L V (f/p) | L (f/p) | — | — | L | L (f/p) | — |
| `academic_advisor` | — | L (w) | L (w) | — | L ringkasan final (w, bila diizinkan) | — | — | — |
| `student` / `alumni` | L | L B U (s) | L (s) | B (laporan) | L (s) + keberatan | L | B (s) | B L (s) |
| `rector`/`vice_rector`/`dean` | L | L agregat | L agregat | L agregat | — (kecuali Wf) | L | L E agregat | — |
| `auditor` | L | L | L | L | L | L | L | L |

### 4.2 Yudisium, dokumen, wisuda

| Role | Periode & aturan | Eligibility | Pendaftaran & verifikasi | Rapat & keputusan | Koreksi/pembatalan keputusan | Predikat | Dokumen kelulusan | Wisuda | Buku wisuda |
|---|---|---|---|---|---|---|---|---|---|
| `graduation_officer` | L B U T | L B(periksa) E | L B V E | L B U, finalisasi | B (ajukan) | L B U | L T E | L | L |
| Pejabat akademik (Wf) | T (publikasi aturan) | L | — | S (pengesahan) | S X | T | X (S pembatalan) | — | T (S) |
| `academic_administrator` (BAAK) | L | L | V (tahap BAAK) | L | — | L | L | — | — |
| `faculty_administrator` | L B(draf aturan fakultas) | L B (f) | V (f) | L (f) | — | L | L (f) | L (f) | L U koreksi (f) |
| `study_program_administrator` | L B(draf aturan prodi) | L B (p) | V (p) | L (p) | — | L | L (p) | L (p) | L U koreksi (p) |
| `head_of_study_program` | L | L (p) | V (Wf, p) | L (p) | — | L | L (p) | L | L |
| `graduation_committee` | L | — | — | L | — | — | — | L B U V T E | L B U T(ajukan) E |
| `ceremony_checkin_officer` | — | — | — | — | — | — | — | Check-in (t) | — |
| `document_administrator` | — | — | — | — | — | — | Template, nomor, penandatangan | — | — |
| `lecturer` | — | L (b) | L (b) | — | — | — | — | — | — |
| `academic_advisor` | — | L (w) | L (w) | — | — | — | — | — | — |
| `student` (tingkat akhir) / `alumni` | L | L (s) | B U (s) | L (s) | — | L (s) | L unduh (s) | B U (s) | L koreksi (s) |
| Pimpinan | L | L agregat | L | L E | S (Wf) | L | L | L | L |
| `auditor` | L | L | L | L | L | L | L | L | L |

### 4.3 SKPI dan Tracer

| Role | Kategori/glosarium/profil prodi | Capaian | Dokumen SKPI | Tracer: periode, target, kuesioner, distribusi | Tracer: jawaban (tanpa identitas) | Tracer: identitas responden | Tracer: laporan/agregat | Kuota |
|---|---|---|---|---|---|---|---|---|
| `skpi_officer` | L U (profil p) | L V (p) | L B U T(ajukan) | — | — | — | — | — |
| Admin SKPI (`skpi_officer` u) | L B U S(glosarium) | L V | L B U T | — | — | — | — | — |
| Kaprodi / Dekan (Wf) | L S(profil) | L | L S | — | — | — | L (p/f) | — |
| `tracer_officer` | — | — | — | L B U T E | L E | — (diberi terpisah bila perlu) | L E | L |
| `study_program_administrator` | L (profil p) | L (p) | L (p) | L, B kontak pengguna lulusan (p) | L jawaban terbuka (p) | — | L (p) | — |
| `university_administrator` | L | — | L agregat | L | — | — | L | L |
| Pimpinan | — | — | L agregat | L | — | — | L agregat (sel minimum) | — |
| `student` / `alumni` | L | L B U (s) | L, pernyataan (s) | Mengisi survei (s / token) | L jawaban sendiri | — | L publikasi | — |
| `auditor` | L | L | L | L | L | L (log saja) | L | L |
| `survey_template_curator` | — | — | — | Template standar (platform) | — | — | — | — |
| `super_admin` | — | — | — | — | — | — | — | Atur & sesuaikan |

### 4.4 Career Center

| Role | Pengaturan portal | Artikel | Profil karier pengguna | Perusahaan & kemitraan | CRM | Lowongan (tenant) | Moderasi | Lamaran | Rekomendasi | Publikasi tracer |
|---|---|---|---|---|---|---|---|---|---|---|
| `career_center_administrator` | L U | L B U T | L (layanan) | L B U, undang | L B U | L B U T | S X (tenant) | L U E | L B U S | L B S |
| `career_center_officer` | L | L B U | L (layanan) | L, undang | L B U | L B U | V | L U | L B U | L B |
| `student` / `alumni` | — | L | L B U H (s) | L publik | — | L | — | B L X(tarik) (s) | Consent (s) | L |
| `company_owner` | — | L | L hanya dengan dasar akses | Profil sendiri, anggota | — | B U (c) | — | L U (c) | Ajukan, L shared (c) | L |
| `company_recruiter` | — | L | idem | L | — | B U (c) | — | L U (c) | Ajukan, L shared (c) | L |
| `career_platform_moderator` | — | Artikel platform | — | Verifikasi, suspensi | — | — | S X (marketplace) | — | — | — |
| Pimpinan | L | L | — | L | — | L | — | L agregat | L agregat | L |
| `auditor` | L | L | Log akses | L | L | L | L | L log | L | L |

## 5. Aksi Sensitif yang Memerlukan Otorisasi Eksplisit dan Audit

| Aksi | Permission | Persetujuan | Audit |
|---|---|---|---|
| Penetapan kelulusan | `graduation_decisions.finalize` | Workflow pengesahan | `approved` + histori |
| Koreksi/pembatalan kelulusan | `graduation_decision_corrections.create/approve` | Workflow | `forced_override` |
| Penerbitan dokumen resmi | `issued_documents.publish` + penugasan jenis dokumen | Sesuai jenis | `issued_document_events` + `created` |
| Pembatalan dokumen | `document_revocations.approve` | Workflow | `approved` |
| Perubahan poin (override/koreksi/cabut) | `student_points.update` / `.approve` / `activity_submissions.update` | Di luar rentang: workflow | `forced_override` |
| Pemberian sanksi | `disciplinary_decisions.create` + workflow | Workflow per tingkat | `approved` |
| Ekspor CV / lamaran | `career_applications.export` (staf) / dasar akses (perusahaan) | — | `exported`/`downloaded` + log akses |
| Ekspor identitas tracer | `tracer_respondent_identity.export` | Alasan wajib | `exported` |
| Penyesuaian kuota | Platform | — | Audit platform |

## 6. Penugasan Role ke Pengguna

- Role baru dibuat sebagai template global (`university_id = null`, `is_system = true`) melalui seeder, mengikuti pola `OrganizationalRoleSeeder` (Ada), dan diberikan per universitas melalui `user_roles.university_id`.
- Role `alumni` diberikan otomatis oleh listener `GraduationDecisionFinalized` (dan dicabut saat `Annulled`) dengan `user_roles.university_id` tenant terkait.
- Role berscope (`f`/`p`/`t`) diberikan dengan `scope_type`/`scope_id`, dan `expires_at` untuk penugasan sementara (panitia seleksi, petugas check-in).
- Pemberian role sensitif (`discipline_*`, `graduation_officer`, `document_administrator`, izin identitas tracer) dicatat audit (Ada: `UserRole` events → `PermissionRegistry::flushAll`).

## 7. Prinsip Least Privilege yang Diterapkan

1. Tidak ada role yang mendapat `*.read` lintas seluruh modul selain `auditor`. Bahkan auditor tidak mendapat izin identitas tracer di luar log.
2. `university_administrator`/`university_owner` mendapat agregat dan konfigurasi, bukan data pribadi disiplin/CV/tracer.
3. Pimpinan mendapat agregat dengan supresi sel kecil.
4. Self-service selalu scope `s`, dengan identitas dari sesi.
5. Permission yang ada dan terlalu lebar dipersempit bertahap (`alumni.read` → data sensitif dipisah; `scholarships.read` → `scholarship_applications.read`) dengan masa transisi satu rilis ([32](32-roadmap-implementasi.md)).
