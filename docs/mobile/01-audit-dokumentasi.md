# Audit Dokumentasi & Kode untuk Aplikasi Mobile Civitas One

> **Per:** 18 Oktober 2026 (kode `main` @ `70e2f52` + perubahan kerja). **Tujuan:** memastikan seluruh isi `docs/` dan implementasi yang ada sudah ditelaah sebelum aplikasi Android mahasiswa & dosen dibangun.

## 1. Metode

- `docs/` dibagi menjadi 32 partisi dokumen (±2.000–3.500 baris per partisi) dan 9 area kode; tiap partisi dibaca **utuh** oleh satu pembaca (agen) yang menulis catatan rinci beserta rujukan `path#bagian` dan hasil terstruktur (fitur, aturan bisnis, kriteria uji, endpoint, status).
- Video (`.mp4`) tidak dapat dibaca langsung; isinya dicakup lewat sumber narasi di repo (`script.json`, `timeline.js`, `presentasi.html`, naskah `.md`).
- PDF dibaca per halaman; gambar dilihat langsung. Screenshot manual book PMB (`docs/manual-book/pmb/assets/`, ±150 berkas) diinventarisasi berdasarkan nama berkas; yang relevan dengan portal/tampilan ponsel dilihat langsung.
- Hasil pembaca disintesis per domain menjadi matriks fitur (`02-matriks-fitur.md`), dengan status backend **diverifikasi ulang terhadap rute di kode**. Aturan: bila dokumen dan kode berbeda, kode yang dipakai (sama dengan `STATUS-IMPLEMENTASI.md`).
- Keamanan: `.env` lokal mengarah ke basis data produksi, sehingga audit hanya membaca kode — tidak ada `artisan migrate/seed/serve`.

## 2. Cakupan

| Kategori | Jumlah |
|---|---|
| Berkas di `docs/` | 386 |
| Dibaca/dilihat utuh | 225 |
| Tidak dibaca utuh (alasan dicatat) | 10 |
| Tidak dibaca (aset/tooling, lihat §4) | 151 |

## 3. Daftar dokumen yang diaudit

| Berkas | Tujuan | Relevansi mahasiswa | Relevansi dosen |
|---|---|---|---|
| `docs/BLACK-BOX-TESTING.md` | Living black-box test checklist across 18 backend modules plus Super Admin, with test IDs AUTH, RBAC, PLAT, TEN, SET, NOTIF, FILE, APPR, AUDIT, AKM, KRS, NIL, ABS, TRX, FIN, BEA, PUS, MBKM, ALM, SKR, ANN, LAP, DASH, PORT | sedang | tinggi |
| `docs/EXAM_MODULE.md` | Technical summary of the Exam module. Covers 5 tables (exams, exam_questions, exam_question_options, exam_attempts, exam_attempt_answers), 13 API endpoints with their permissions, and permission grants (role lecturer get | sedang | tinggi |
| `docs/EXAM_RANDOMIZATION.md` | Behaviour and validation rules for multiple-choice exams: question_pool_size, questions_per_participant, selection modes all/random/manual, randomize_questions/randomize_options, attempt snapshot stability (question_orde | tinggi | tinggi |
| `docs/FRONTEND-IMPLEMENTASI.md` | Living checklist for the React web frontend. Auth phase done (login/me/logout, forgot/reset, sessions & devices). Settings phase done (roles, flags, notifications, audit, file upload, approvals). Per-module write-operati | rendah | rendah |
| `docs/Logo_unika-atmajaya.gif` | Atma Jaya crest; colors deep green #056525, orange #F0901B, red #E53025 | sedang | sedang |
| `docs/MULTI-TENANT-ARCHITECTURE.md` | Shared-DB multi-tenancy using university_id. Covers: table classification; universities fields (incl. branding, locale, timezone, currency, date_format); TenantScoped; tenant.resolve order (X-University-ID header → custo | sedang | sedang |
| `docs/RANCANGAN-AKUN-AKADEMIK.md` | Whole Academic module across roles (centred on academic_administrator). Covers: role map; full data model with fields; master-data filters; KRS, grades, attendance and transcript rules and endpoints; transcript/IP/IPK fo | tinggi | tinggi |
| `docs/RANCANGAN-AKUN-DOSEN.md` | Lecturer (role lecturer) account design from the Sep-2026 code. Covers: permission list; read-only master data; grades, attendance, exam and question-bank endpoints and rules; permission-trimmed dashboard; EDOM and the D | rendah | tinggi |
| `docs/RANCANGAN-AKUN-MAHASISWA.md` | Student account design written from the Sep-2026 code. Covers: role student with only exam_participation.* permissions; students.user_id link with default password = NIM; the 21 portal pages (2 live then); the full exam  | tinggi | rendah |
| `docs/RANCANGAN-AKUN-SDM.md` | Design for the HR account (role hr_administrator, label 'Bagian SDM') and the 22-module SDM/HumanResource domain. Built from the actual code state plus designs for unbuilt parts. Defines lecturer/employee self-service: P | — | tinggi |
| `docs/RANCANGAN-APLIKASI.md` | Master blueprint ('Dokumen landasan (blueprint) pengembangan') for the whole university management system. It declares itself the official source of truth. It covers the overview, goals, 13 roles (incl. Dosen, Dosen Pemb | tinggi | tinggi |
| `docs/RANCANGAN-SUPER-ADMIN.md` | Design for Super Admin platform management: tenant switcher and support sessions, university lifecycle, domains, module catalog and feature flags, subscriptions/billing/invoices, global RBAC UI, security (cross-tenant au | rendah | rendah |
| `docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md` | Android-only Flutter technical design for Civitas One. It is derived 1:1 from the React web app in frontend/ and plans to replicate all 71 pages: auth, tenant admin, super admin and the 19-screen Portal Mahasiswa. It cov | tinggi | sedang |
| `docs/RANCANGAN_SISTEM_ADMINISTRASI_KEUANGAN_MAHASISWA_KEU.md` | Master design of the KEU student-finance module (student receivables subledger), extending app/Modules/Finance. It covers fee components and versioned tariffs; invoices (registration, per-SKS, incidental); UKT; scholarsh | tinggi | rendah |
| `docs/RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md` | Master design for the new CivitasOne PMB/Admission module (app/Modules/Admission, code 'pmb'). It covers scope, roles, architecture, the end-to-end flow, 42 business rules, 11 state machines, 66 pmb_ tables, API prefixes | rendah | rendah |
| `docs/SEVIMA_Penjelasan_Modul.md` | Functional explanation of every module and feature of the reference product SEVIMA Platform: users, lowest package tier (Growth -> Business -> Professional -> Premium -> Enterprise), and (*) for features in development.  | tinggi | tinggi |
| `docs/SEVIMA_Platform_Proposal.md` | SEVIMA vendor feature-and-price matrix for five packages (Growth, Business, Professional, Premium, Enterprise), covering modules SIAKAD, KEU, EDLINK (LMS + Android/iOS apps), CBT, EDOM, Yudisium/Wisuda, Kemahasiswaan, SK | sedang | sedang |
| `docs/STATUS-IMPLEMENTASI.md` | The most recent, code-verified inventory of what is built and what is not, across all modules. Uses the legend ✅ 🟡 ⚙️ 🧩 ❌. Where docs and code differ, code wins. It states that FRONTEND-IMPLEMENTASI.md, BLACK-BOX-TESTING | tinggi | tinggi |
| `docs/academic-learning/01-siakad.md` | Functional design of SIAKAD A.1-A.10 (master data, curriculum, schedule, KRS, attendance, grading, perwalian, cekal, services, MBKM) | tinggi | tinggi |
| `docs/academic-learning/02-edlink-lms.md` | EDLINK/LMS functional design: classes, materials, assignments, quizzes, forum, QR attendance, dashboards, plan features, storage/whitelabel, endpoints, permissions, BR-LMS rules | tinggi | tinggi |
| `docs/academic-learning/03-cbt-cbt-ai.md` | CBT & CBT-AI functional design: question bank, exam settings, taking/timer/autosave, grading, AI drafts, proctoring, integration, state machines, endpoints | tinggi | tinggi |
| `docs/academic-learning/04-obe.md` | Design of OBE module: profiles, CPL, CPMK, RPS, mappings, attainment measurement, evaluation | rendah | sedang |
| `docs/academic-learning/05-edom.md` | Design of EDOM (student evaluation of lecturers) incl. anonymity, student fill flow, grade gate, reports | tinggi | sedang |
| `docs/academic-learning/06-integrasi-arsitektur.md` | Cross-module integration architecture, data ownership, events, flows, error/idempotency conventions | sedang | sedang |
| `docs/academic-learning/07-database-erd.md` | Reference DB schema/ERD design (tables, columns, enums, relations, mermaid ERDs, migration/backfill plan M01-M45) for the academic-learning doc set; marks each table Ada/Diperluas/Baru | tinggi | tinggi |
| `docs/academic-learning/08-api-specification.md` | Design API spec for Academic & Learning: conventions, error catalog, idempotency, rate limits, endpoints per group with Ada/Baru status, examples | tinggi | tinggi |
| `docs/academic-learning/09-ui-ux-specification.md` | Design-stage UI/UX spec for academic & learning modules: design system reuse, status registry, dashboards per role, navigation, per-page specs, wireframes, accessibility, mobile Flutter patterns | tinggi | tinggi |
| `docs/academic-learning/10-business-rules.md` | Owner of BR-* business rules catalog, state machines for canonical statuses, config key index with example defaults, domain events and notification keys | tinggi | tinggi |
| `docs/academic-learning/11-roles-permissions.md` | RBAC, scope, four authorization layers, permission matrix and slug catalog, package entitlement, audit actions | tinggi | tinggi |
| `docs/academic-learning/12-reports-analytics.md` | Reports/analytics/export design for academic modules; scope, masking, async export | sedang | tinggi |
| `docs/academic-learning/13-security-audit.md` | Security, audit, privacy, retention, exam security, parent limits | sedang | sedang |
| `docs/academic-learning/14-test-scenarios.md` | QA scenarios TS-SIA/LMS/CBT/OBE/EDM/MBK/ENT/SEC with pass criteria | tinggi | tinggi |
| `docs/academic-learning/15-implementation-roadmap.md` | Stages 0-7b, priorities, risks, DoD, sprint backlog | rendah | rendah |
| `docs/academic-learning/README.md` | Index, scope, repo findings, architecture, naming canon for Academic & Learning design | sedang | sedang |
| `docs/kemahasiswaan-kelulusan/01-overview-dan-ruang-lingkup.md` | Background, goals, scope and module boundaries, design principles | rendah | rendah |
| `docs/kemahasiswaan-kelulusan/02-analisis-kebutuhan-bisnis.md` | Business processes, functional and non-functional requirements, gaps, risks | sedang | rendah |
| `docs/kemahasiswaan-kelulusan/03-aktor-role-dan-hak-akses.md` | Canonical role, permission, scope catalogue and access matrices | sedang | sedang |
| `docs/kemahasiswaan-kelulusan/04-arsitektur-dan-integrasi-antarmodul.md` | Target architecture, data ownership, integration patterns, reconciliation | rendah | rendah |
| `docs/kemahasiswaan-kelulusan/05-rancangan-poin-kemahasiswaan.md` | Activity categories, point rules, append-only point ledger, corrections, revocation, appeals | tinggi | sedang |
| `docs/kemahasiswaan-kelulusan/06-rancangan-aktivitas-dan-prestasi.md` | Student activity/achievement submission, evidence, verification, collective events, anti-duplication | tinggi | sedang |
| `docs/kemahasiswaan-kelulusan/07-rancangan-pelanggaran-dan-sanksi.md` | Violation report -> case -> sanction decision -> objection, with student hearing/defense and advisor summary | sedang | sedang |
| `docs/kemahasiswaan-kelulusan/08-rancangan-beasiswa-mahasiswa.md` | Scholarship programs, applications, selection, awards, evaluation and KEU sync | tinggi | — |
| `docs/kemahasiswaan-kelulusan/09-rancangan-surat-keterangan-aktif.md` | Extend existing active-student letter flow with official numbering, frozen snapshot, signature modes, public verification, revocation | tinggi | — |
| `docs/kemahasiswaan-kelulusan/10-rancangan-periode-dan-syarat-yudisium.md` | Yudisium period lifecycle and versioned graduation requirement rule engine (checker catalogue, resolution by specificity) | sedang | rendah |
| `docs/kemahasiswaan-kelulusan/11-rancangan-eligibilitas-yudisium.md` | Eligibility engine: per-requirement results, aggregation, staleness, exemptions, student self-view and recheck | tinggi | sedang |
| `docs/kemahasiswaan-kelulusan/12-rancangan-peserta-dan-penetapan-kelulusan.md` | Yudisium registration, tiered verification, meeting, decision, honors, finalization, corrections | tinggi | sedang |
| `docs/kemahasiswaan-kelulusan/13-rancangan-penomoran-dokumen-kelulusan.md` | Shared OfficialDocument service: templates, numbering, issuance, revocation, reissue, public authenticity verification | sedang | — |
| `docs/kemahasiswaan-kelulusan/14-rancangan-wisuda-dan-buku-wisuda.md` | Graduation ceremony registration, fee validation via KEU, sessions, QR check-in, graduation book | tinggi | — |
| `docs/kemahasiswaan-kelulusan/15-rancangan-skpi.md` | SKPI (diploma supplement) bilingual achievement collection, verification, drafting, approval, issuance | tinggi | rendah |
| `docs/kemahasiswaan-kelulusan/16-rancangan-tracer-study.md` | Tracer study periods, target groups, employer contacts, alumni career status and records | rendah | — |
| `docs/kemahasiswaan-kelulusan/17-rancangan-kuesioner-dan-survei.md` | Questionnaire builder, versioning, standard templates, indicator mapping, survey response flow | rendah | — |
| `docs/kemahasiswaan-kelulusan/18-rancangan-distribusi-dan-reminder-tracer.md` | Survey invitation tokens, email distribution, reminders, bounce handling, package quota ledger | rendah | — |
| `docs/kemahasiswaan-kelulusan/19-rancangan-dashboard-dan-laporan-tracer.md` | Tracer indicator formulas, dashboards, accreditation recap, exports (staff) | — | — |
| `docs/kemahasiswaan-kelulusan/20-rancangan-career-center-karirlink.md` | Karirlink portal models, branding, career profile, CV, articles, tracer publications | tinggi | — |
| `docs/kemahasiswaan-kelulusan/21-rancangan-lowongan-dan-lamaran-kerja.md` | Job postings, moderation, applications, status machine | tinggi | — |
| `docs/kemahasiswaan-kelulusan/22-rancangan-mitra-perusahaan-dan-crm.md` | Company partners, verification, CRM (staff/company) | — | — |
| `docs/kemahasiswaan-kelulusan/23-rancangan-rekomendasi-lulusan.md` | Graduate recommendation with alumni consent (campus staff driven) | sedang | — |
| `docs/kemahasiswaan-kelulusan/24-rancangan-ui-ux-dan-struktur-menu.md` | UI/UX and menu structure incl. student/alumni Civitas One menu | tinggi | rendah |
| `docs/kemahasiswaan-kelulusan/25-rancangan-database-dan-erd.md` | Database schema and ERD for graduation/affairs/career modules | sedang | rendah |
| `docs/kemahasiswaan-kelulusan/26-rancangan-api-dan-integrasi.md` | API conventions, endpoints, error codes, integration | tinggi | rendah |
| `docs/kemahasiswaan-kelulusan/27-rancangan-notifikasi-dan-email.md` | Notification/email design: channels, catalog of event keys, templates, retry | tinggi | rendah |
| `docs/kemahasiswaan-kelulusan/28-keamanan-privasi-dan-audit.md` | Security, privacy, consent, retention, tokens, upload validation, rate limits | sedang | rendah |
| `docs/kemahasiswaan-kelulusan/29-business-rules-dan-validasi.md` | Catalog of business rules BR with validations and error codes | tinggi | rendah |
| `docs/kemahasiswaan-kelulusan/30-skenario-pengujian.md` | Test case catalog and UAT scenarios | tinggi | rendah |
| `docs/kemahasiswaan-kelulusan/31-acceptance-criteria.md` | Acceptance criteria with traceability and DoD | tinggi | rendah |
| `docs/kemahasiswaan-kelulusan/32-roadmap-implementasi.md` | Phased implementation roadmap and dependencies | sedang | — |
| `docs/kemahasiswaan-kelulusan/33-glossary-dan-keputusan-bisnis.md` | Glossary, design decisions KK-KD, open questions KK-Q, assumptions KK-A | sedang | rendah |
| `docs/kemahasiswaan-kelulusan/34-matriks-paket-dan-entitlement.md` | Package/entitlement matrix and feature keys | sedang | — |
| `docs/kemahasiswaan-kelulusan/README.md` | Index, repo findings, canonical statuses/IDs/feature keys, dependencies and open decisions for group E (student affairs and graduation) design | sedang | rendah |
| `docs/keu/catatan-implementasi.md` | KEU implementation notes: built scope, status, config, decisions | tinggi | rendah |
| `docs/keuangan/01-overview-dan-ruang-lingkup.md` | Goals, users, scope/out-of-scope, boundaries with other modules, four layers, package availability | sedang | rendah |
| `docs/keuangan/02-analisis-kebutuhan-bisnis.md` | Business processes, functional (KEU-FR-*) and non-functional (KEU-NFR-*) requirements, risks | tinggi | rendah |
| `docs/keuangan/03-role-dan-hak-akses.md` | Role, permission catalogue, matrix, FinanceScope, approvals, SoD, column visibility | tinggi | sedang |
| `docs/keuangan/04-master-data-dan-konfigurasi.md` | Fee components, bank accounts, numbering, finance.* tenant settings, finance periods (staff config) | rendah | — |
| `docs/keuangan/05-rancangan-tarif-uang-kuliah.md` | Versioned tariff design, matching/selection, snapshot and worked examples determining student invoice amounts | rendah | — |
| `docs/keuangan/06-rancangan-tagihan-mahasiswa.md` | Invoice (tagihan) types, nominal definitions, status machine, per-SKS billing, bulk billing, corrections, disputes, history, student invoice API | tinggi | — |
| `docs/keuangan/07-rancangan-pembayaran.md` | Core payment model: payments vs allocations, payment status, FIFO allocation, partial/min payment, overpayment, receipts, reversal | tinggi | — |
| `docs/keuangan/08-rancangan-pembayaran-online-dan-gateway.md` | Online gateway payments (VA/QRIS/e-wallet), adapter, transaction status machine, webhooks, student pay flow and APIs | tinggi | — |
| `docs/keuangan/09-rancangan-pembayaran-tunai-dan-manual.md` | Cash counter, manual transfer with proof upload, verification/rejection codes, duplicate prevention, public receipt verification | tinggi | — |
| `docs/keuangan/10-rancangan-cicilan-tagihan.md` | Installment plans over an invoice: schedule formation, request/approval, allocation to terms, rebalance, student request API | sedang | — |
| `docs/keuangan/11-rancangan-beasiswa-dan-potongan.md` | Design of scholarships/discounts as negative invoice lines, award lifecycle, calculation order, late apply/revocation | sedang | — |
| `docs/keuangan/12-rancangan-voucher-tagihan.md` | Voucher code design: validation, quota, reservation, release, student apply/remove on invoice | sedang | — |
| `docs/keuangan/13-rancangan-denda.md` | Late fee rules, buckets, separate late_fee invoice, waiver | sedang | — |
| `docs/keuangan/14-rancangan-deposit-mahasiswa.md` | Append-only deposit ledger, use, top-up, refund, freeze | sedang | — |
| `docs/keuangan/15-rancangan-ukt-dan-validasi-penerima.md` | UKT groups, per-student determination, appeals, document confidentiality, validation | sedang | rendah |
| `docs/keuangan/16-rancangan-monitoring-piutang.md` | Receivables indicators, aging, snapshots, dashboards (staff/management) | — | rendah |
| `docs/keuangan/17-rancangan-cekal-keuangan.md` | Financial hold (cekal) policy, evaluation, exemptions, sync to SIAKAD academic_blocks, enforcement points | tinggi | sedang |
| `docs/keuangan/18-rancangan-rekonsiliasi-dan-penyesuaian.md` | Reconciliation, refund, reversal/adjustment, period close, accounting export (staff) | rendah | — |
| `docs/keuangan/19-rancangan-database-dan-relasi.md` | Reference DB schema/ERD for finance (KEU) module: 50 entities, data dictionary, migration, integrity | sedang | — |
| `docs/keuangan/20-rancangan-api-dan-integrasi.md` | Official API and integration contract for finance; student endpoints under /api/v1/student/finance | tinggi | — |
| `docs/keuangan/21-rancangan-ui-ux-dan-menu.md` | KEU UI/UX and menu design: staff menu A-K and student portal Keuangan pages | tinggi | rendah |
| `docs/keuangan/22-rancangan-laporan-dan-dashboard.md` | Dashboards per audience and 18 staff reports KEU-RPT-01..18 | rendah | rendah |
| `docs/keuangan/23-rancangan-notifikasi.md` | Finance notification catalogue (student, staff, advisor), reminders, privacy | tinggi | rendah |
| `docs/keuangan/24-keamanan-audit-dan-kontrol-internal.md` | Security, SoD, audit, retention, anomaly rules | rendah | — |
| `docs/keuangan/25-business-rules-dan-validasi.md` | Official KEU-BR-001..314 business rule catalogue | tinggi | rendah |
| `docs/keuangan/26-skenario-pengujian.md` | KEU-TC test case catalogue by area | sedang | rendah |
| `docs/keuangan/27-acceptance-criteria.md` | KEU-AC acceptance criteria and evidence codes | sedang | rendah |
| `docs/keuangan/28-roadmap-implementasi.md` | Phased implementation roadmap, dependencies, risks, DoD | rendah | — |
| `docs/keuangan/29-glossary-dan-keputusan-bisnis.md` | Glossary, KEU-KD decisions, KEU-Q open questions with defaults, KEU-A assumptions, UI term mapping | sedang | rendah |
| `docs/keuangan/README.md` | Index, repository findings, architecture and canon (statuses, roles, permissions, error codes, events, settings) for the KEU student finance module design | sedang | rendah |
| `docs/manual-book/keuangan/01-pendahuluan.md` | Goals, key concepts, three status layers, 23-feature list, semester flow | tinggi | rendah |
| `docs/manual-book/keuangan/02-akses-dan-peran.md` | Access prerequisites, permissions (incl. student_finance), roles, maker-checker, sensitive data | sedang | rendah |
| `docs/manual-book/keuangan/03-dashboard-dan-menu.md` | Staff menu/dashboard and finance settings (bank accounts, grace, reminders) | rendah | — |
| `docs/manual-book/keuangan/10-tarif-dan-komponen-biaya.md` | Fee components and tariff tables (staff) | rendah | — |
| `docs/manual-book/keuangan/11-tagihan.md` | Invoice groups, mass billing, manual invoice, SKS invoice from KRS approval, adjustments | sedang | rendah |
| `docs/manual-book/keuangan/12-pembayaran.md` | Cash, manual transfer with proof upload, online VA/QRIS, deposit payment, cancel | tinggi | — |
| `docs/manual-book/keuangan/13-verifikasi-dan-kuitansi.md` | Proof verification, receipts (student view), reversal | tinggi | — |
| `docs/manual-book/keuangan/14-beasiswa-potongan-voucher.md` | Scholarship/discount types, assignment, voucher redemption rules | sedang | — |
| `docs/manual-book/keuangan/15-denda-dan-deposit.md` | Late penalty rules/calculation and deposit mutations | tinggi | — |
| `docs/manual-book/keuangan/16-ukt.md` | UKT groups, assignment, student review request | sedang | — |
| `docs/manual-book/keuangan/17-cicilan.md` | Installment schemes, student request, termin payment | tinggi | — |
| `docs/manual-book/keuangan/18-piutang-dan-tunggakan.md` | Receivable/arrears definitions, ledger, reminders | sedang | — |
| `docs/manual-book/keuangan/19-cekal-keuangan.md` | Finance clearance (cekal) rules, enforcement on KRS/ujian, dispensasi, bebas tanggungan | tinggi | sedang |
| `docs/manual-book/keuangan/20-rekonsiliasi-dan-refund.md` | Bank reconciliation (staff) and student refund flow | sedang | — |
| `docs/manual-book/keuangan/21-laporan-dan-ekspor.md` | 13 finance reports and CSV export (staff) | — | — |
| `docs/manual-book/keuangan/30-panduan-per-peran.md` | Per-role guide; section 1 is the full student portal guide | tinggi | rendah |
| `docs/manual-book/keuangan/40-masalah-dan-pesan-kesalahan.md` | Error messages and troubleshooting incl. student-visible strings | tinggi | rendah |
| `docs/manual-book/keuangan/41-faq-dan-prosedur.md` | FAQ and staff SOP | sedang | — |
| `docs/manual-book/keuangan/42-batasan-dan-integrasi.md` | Limitations, unbuilt integrations, ops dependencies | sedang | rendah |
| `docs/manual-book/keuangan/README.md` | Index, reader map and conventions of KEU manual; notes no screenshots, online payment conditional | sedang | — |
| `docs/manual-book/pmb/01-pengenalan.md` | PMB module intro: three faces, concepts, applicant stage labels | rendah | — |
| `docs/manual-book/pmb/02-akses-dan-peran.md` | Access URLs, staff roles/permissions, separation of duties | — | rendah |
| `docs/manual-book/pmb/03-alur-kerja-panitia.md` | Committee work calendar phases 0-6 | — | — |
| `docs/manual-book/pmb/10-website-pmb.md` | Public PMB website incl. mobile layout, cek status, LoA verify | rendah | — |
| `docs/manual-book/pmb/11-akun-pendaftar.md` | Applicant account, verification, login, forgot password/OTP, recovery | sedang | — |
| `docs/manual-book/pmb/12-formulir-pendaftaran.md` | Applicant portal and registration form | rendah | — |
| `docs/manual-book/pmb/13-dokumen-dan-pembayaran.md` | Applicant document upload and payment | rendah | — |
| `docs/manual-book/pmb/14-ujian-dan-cbt.md` | Applicant exam page, participant card, CBT UX | sedang | — |
| `docs/manual-book/pmb/15-hasil-dan-daftar-ulang.md` | Results, offer accept/reject, re-registration, becoming student/NIM | sedang | — |
| `docs/manual-book/pmb/16-pesan-notifikasi-akun.md` | Applicant messages, notifications, account security | sedang | — |
| `docs/manual-book/pmb/20-periode-gelombang-kuota-biaya.md` | Admin config of periods, waves, quotas, fees | — | — |
| `docs/manual-book/pmb/21-master-data.md` | Admin master data: paths, study systems, doc types | — | — |
| `docs/manual-book/pmb/22-template-formulir.md` | Admin form templates | — | — |
| `docs/manual-book/pmb/23-skema-seleksi.md` | Admin selection schemes | — | — |
| `docs/manual-book/pmb/24-konten-website-profil-prodi.md` | Admin website content and prodi profile | — | — |
| `docs/manual-book/pmb/25-pengaturan-pmb.md` | Admin settings, NIM format, vouchers | — | — |
| `docs/manual-book/pmb/30-dashboard-pmb.md` | PMB dashboard KPIs, funnel, quota, revenue, SLA for staff | — | — |
| `docs/manual-book/pmb/31-data-pendaftar.md` | Staff applicant list/detail, counter registration, sensitive data reveal, corrections | — | — |
| `docs/manual-book/pmb/32-verifikasi-administrasi.md` | Administrative verification queue/claim/decisions, duplicates, account recovery | — | — |
| `docs/manual-book/pmb/33-keuangan-pmb.md` | PMB finance: invoices, manual proof, cashier, reconciliation, refunds | — | — |
| `docs/manual-book/pmb/34-ujian-seleksi.md` | Exam schedule/sessions/placement/CBT bank/check-in; lists proctor assignment | — | rendah |
| `docs/manual-book/pmb/35-penilaian-ujian.md` | Examiner/proctor (lecturer) score entry and score locking | — | tinggi |
| `docs/manual-book/pmb/36-proses-seleksi-dan-pengesahan.md` | Selection runs, score matrix/import, override, approval flows | — | — |
| `docs/manual-book/pmb/37-pengumuman-dan-keputusan.md` | Result decisions, publication batches, waitlist, result corrections | — | — |
| `docs/manual-book/pmb/38-daftar-ulang-dan-konversi.md` | Re-registration validation and conversion applicant->student (NIM, account, billing, scholarship) | rendah | — |
| `docs/manual-book/pmb/39-komunikasi.md` | Applicant chat, broadcast, notification templates and logs | — | — |
| `docs/manual-book/pmb/40-laporan.md` | PMB reports and exports with PII controls | — | — |
| `docs/manual-book/pmb/41-audit.md` | Read-only PMB audit trails | — | — |
| `docs/manual-book/pmb/50-administrasi-sistem.md` | Install, cron, env vars, integration endpoints, security/rate limits | — | — |
| `docs/manual-book/pmb/60-perbedaan-rancangan-implementasi.md` | Design-vs-implementation differences (27 items), state 14 Oct 2026 | — | rendah |
| `docs/manual-book/pmb/61-hal-perlu-konfirmasi.md` | Open confirmations (E1-E10, K1-K13, P1-P6) and known defects B1-B11 | — | rendah |
| `docs/manual-book/pmb/62-faq-dan-pemecahan-masalah.md` | Applicant and staff FAQ/troubleshooting with real messages | — | — |
| `docs/manual-book/pmb/63-glosarium-status.md` | Glossary and label-to-code status maps | rendah | rendah |
| `docs/manual-book/pmb/MANUAL-BOOK-PMB-CALON-MAHASISWA.md` | Single-file applicant manual end to end | sedang | — |
| `docs/manual-book/pmb/MANUAL-BOOK-PMB-PENYELENGGARA.md` | Consolidated PMB organizer manual (roles, menu matrix, permissions, SoD, config, ops, appendices) | rendah | sedang |
| `docs/manual-book/pmb/README.md` | Index, reader map, conventions of PMB manual | rendah | rendah |
| `docs/manual-book/pmb/assets/10-02-beranda-ponsel.png` | (aset/media yang dilihat) | | |
| `docs/manual-book/pmb/assets/10-03-menu-ponsel.png` | (aset/media yang dilihat) | | |
| `docs/manual-book/pmb/assets/11-03-masuk.png` | (aset/media yang dilihat) | | |
| `docs/manual-book/pmb/assets/11-05-atur-sandi-otp.png` | (aset/media yang dilihat) | | |
| `docs/manual-book/pmb/assets/14-03-cbt-mengerjakan.png` | (aset/media yang dilihat) | | |
| `docs/manual-book/pmb/assets/15-01-portal-mahasiswa.png` | (aset/media yang dilihat) | | |
| `docs/manual-book/pmb/assets/15-06-daftar-ulang-berjalan.png` | (aset/media yang dilihat) | | |
| `docs/manual-book/pmb/assets/16-01-pesan.png` | (aset/media yang dilihat) | | |
| `docs/manual-book/pmb/assets/16-03-notifikasi.png` | (aset/media yang dilihat) | | |
| `docs/pmb/01-analisis-dokumen-sumber.md` | Analysis of SEVIMA PMB scope vs CivitasOne code, inconsistencies and gaps | rendah | — |
| `docs/pmb/02-asumsi-keputusan-pertanyaan.md` | Assumptions, constraints, design decisions, stakeholder questions for PMB | rendah | rendah |
| `docs/pmb/03-spesifikasi-konfigurasi-dan-website.md` | PMB configuration and public website spec | — | — |
| `docs/pmb/04-spesifikasi-akun-formulir-dokumen.md` | Applicant account, form and document spec (security conventions) | rendah | — |
| `docs/pmb/05-spesifikasi-pembayaran-dan-verifikasi.md` | Applicant payment (gateway patterns) and admin verification spec | rendah | — |
| `docs/pmb/06-spesifikasi-seleksi-ujian-pengumuman.md` | Selection, exam scheduling/scoring and result announcement incl. examiner duties | — | sedang |
| `docs/pmb/07-spesifikasi-daftar-ulang-jalur-khusus-pelaporan.md` | Re-registration, student conversion (NIM), special paths, reporting | sedang | — |
| `docs/pmb/08-rbac.md` | PMB roles/permissions incl. examiner = lecturer | — | sedang |
| `docs/pmb/09-workflow-dan-state-machine.md` | PMB workflows and 11 state machines | rendah | rendah |
| `docs/pmb/10-database-erd-data-dictionary.md` | PMB DB design: ERD, data dictionary (66 pmb_* tables), student conversion tables, changes to students/invoices, backup | rendah | rendah |
| `docs/pmb/11-api-dan-integrasi.md` | PMB API design, conventions, auth/rate limits, exam/examiner endpoints, conversion, integration adapters | rendah | rendah |
| `docs/pmb/12-ui-ux-sitemap.md` | PMB sitemap and page specs (public, applicant portal, admin); examiner score-entry page | rendah | rendah |
| `docs/pmb/13-notifikasi-keamanan-nonfungsional.md` | PMB notification catalogue, security/privacy (UU PDP), audit, NFRs | sedang | rendah |
| `docs/pmb/14-edge-cases-testing-acceptance.md` | PMB edge cases EC-01..28, test strategy, AC-01..21 | rendah | rendah |
| `docs/pmb/15-prioritas-risiko-traceability-checklist.md` | PMB phasing, dependencies, risks, FR traceability (125 FRs), checklist | rendah | rendah |
| `docs/pmb/16-catatan-implementasi.md` | What was actually implemented for PMB module (code map, decisions, security, ops, external deps) | rendah | rendah |
| `docs/proposal/Proposal-Civitas-One-Atma-Jaya.pdf` | Sales/solution proposal for Atma Jaya with module status labels (TERSEDIA/SEBAGIAN/RANCANGAN/RENCANA), mobile plan, roadmap, security | tinggi | tinggi |
| `docs/semester-pendek/README.md` | Exec summary, architecture decision (SP = academic_terms row semester=pendek), current-vs-needed table, lifecycle, prerequisites | tinggi | sedang |
| `docs/semester-pendek/aturan-bisnis.md` | SP business rules: term model, lifecycle, eligibility pipeline w/ error codes, retake, KRS flow, conflicts, attendance, edge cases | tinggi | sedang |
| `docs/semester-pendek/black-box-testing.md` | Frontend black-box test catalogue (IDs NAV/FLG/SET/TRM/STS/KLS/JDW/KRS/ELG/APV/ADM/PAY/OWN/LRN/PRT/GRD/TRN/RPT/NTF/AUD/SEC/UX/REG/E2E) for Short Semester | tinggi | tinggi |
| `docs/semester-pendek/desain-api.md` | API contracts /api/v1 incl. student/* self-service, lecturer/class-sections, grades, finance | tinggi | sedang |
| `docs/semester-pendek/desain-database.md` | Schema changes, new tables, per-tenant settings keys and defaults | rendah | rendah |
| `docs/semester-pendek/frontend-dan-mobile.md` | Web pages admin/student, report types, Flutter scope decisions and proposed structure | tinggi | sedang |
| `docs/semester-pendek/keamanan-audit-integritas.md` | SP security, tenant isolation, locking and audit design | sedang | sedang |
| `docs/semester-pendek/pembayaran-dan-notifikasi.md` | SP billing, payment recording, expiry, notification event catalog | tinggi | sedang |
| `docs/semester-pendek/pengujian-dan-penerimaan.md` | Pest test strategy and acceptance criteria AC-01..AC-22 | tinggi | sedang |
| `docs/semester-pendek/penilaian-dan-transkrip.md` | Exams, grade statuses/finalization, revision, IPS/IPK, transcript format | tinggi | tinggi |
| `docs/semester-pendek/peran-dan-izin.md` | Permissions, role x action matrix, ownership policies | tinggi | tinggi |
| `docs/semester-pendek/rencana-implementasi.md` | Original phase plan (Fase 0-7), readiness checklist, not-built list, risks | sedang | sedang |
| `docs/semester-pendek/tahapan-pengembangan.md` | Executable stage plan reconciling design with code (R-01..R-14), gates G1-G6, estimates | sedang | sedang |
| `docs/sempel/Gemini_Generated_Image_37brqe37brqe37br.jpg` | AI mock KTP test fixture | rendah | — |
| `docs/sempel/Gemini_Generated_Image_sz8ebosz8ebosz8e.jpg` | AI pas foto 3:4 test fixture | rendah | — |
| `docs/sempel/Sampel Rapor Semester 1 - 5.pdf` | Sample SMA rapor sem 1-5 (PMB applicant fixture) | rendah | — |
| `docs/sempel/Sampel SKL dan Ijazah.pdf` | Sample SMA SKL + Ijazah (PMB applicant test fixture) | rendah | — |
| `docs/sempel/c2cce0b1-kwitansi-3.jpg` | Photo of ATM transfer slip, Rp200.000; test input for transfer proof upload | sedang | — |
| `docs/video-semester-pendek/source/build_audio.mjs` | Tooling: msedge-tts narration (id-ID-GadisNeural) -> narration.wav + timeline.js | — | — |
| `docs/video-semester-pendek/source/package.json` | Tooling manifest (msedge-tts, playwright-core 1.63.0, ffmpeg-installer) | — | — |
| `docs/video-semester-pendek/source/presentasi.html` | Animated 1280x720 slides with on-screen text, mock student portal, status/rules diagrams | sedang | rendah |
| `docs/video-semester-pendek/source/render.mjs` | Tooling: Playwright+ffmpeg video render (1920x1080, 25fps) | — | — |
| `docs/video-semester-pendek/source/script.json` | Narration script for explainer video, 25 scenes (cap + tts) | sedang | rendah |
| `docs/video-semester-pendek/source/timeline.js` | Generated scene/line timings (total 1185.102 s) | — | — |
| `docs/video-tutorial-pmb/README.md` | Overview of two PMB tutorial videos and their source/build pipeline | — | — |
| `docs/video-tutorial-pmb/naskah-video-pmb-calon-mahasiswa.md` | Generated production script for applicant video (24 scenes, 17:28) | sedang | — |
| `docs/video-tutorial-pmb/naskah-video-pmb-penyelenggara.md` | Generated production script for PMB staff video (26 scenes, 18:37) | — | rendah |
| `docs/video-tutorial-pmb/source/build_audio.mjs` | Edge TTS narration builder, writes timeline.js | — | — |
| `docs/video-tutorial-pmb/source/build_naskah.mjs` | Generator: reads <name>.timeline.js, writes naskah md | — | — |
| `docs/video-tutorial-pmb/source/common.mjs` | Camera helpers and TTS pronunciation map | — | — |
| `docs/video-tutorial-pmb/source/package.json` | npm scripts/deps for video build | — | — |
| `docs/video-tutorial-pmb/source/render.mjs` | Playwright+ffmpeg frame renderer to mp4 | — | — |
| `docs/video-tutorial-ujian/source-dosen/build_audio.mjs` | Tooling only: builds the narration with msedge-tts (default voice id-ID-GadisNeural, rate +4%), trims silence, caches clips by sha1, and writes narration.wav and timeline.js. | — | — |
| `docs/video-tutorial-ujian/source-dosen/package.json` | Tooling manifest 'tutorial-ujian-dosen' (private, ESM). Scripts: audio, snap, render (outputs ../tutorial-ujian-dosen.mp4). Dependencies: @ffmpeg-installer/ffmpeg ^1.1.0, msedge-tts ^2.0.9, playwright-core 1.63.0. | — | — |
| `docs/video-tutorial-ujian/source-dosen/render.mjs` | Tooling only: renders tutorial.html at 1920x1080 and 25 fps with Playwright (Microsoft Edge or Chrome channel) and @ffmpeg-installer, using parallel worker segments, concat, and loudnorm audio. Also has a snap mode. | — | — |
| `docs/video-tutorial-ujian/source-dosen/script.json` | Narration script for the lecturer exam-management tutorial: 16 scenes. Covers the 8-step flow (Bank Soal, Buat Ujian, Masukkan Soal, Rentang Nilai & Bobot, Periksa & Publish, Link & QR Code, Pantau Live, Rekap Nilai), se | rendah | tinggi |
| `docs/video-tutorial-ujian/source-dosen/timeline.js` | Generated timeline for the lecturer tutorial. Total 558.065 s across 16 scenes. Every caption is identical to script.json (verified). | — | sedang |
| `docs/video-tutorial-ujian/source-dosen/tutorial.html` | HTML/JS animation that renders the lecturer tutorial. Mock UI covers: login 'Masuk ke OneAtma'; the lecturer dashboard (Kelas Diampu, Ujian Aktif, Soal di Bank Soal, Jadwal Mengajar Hari Ini) and the Akademik sidebar (in | rendah | tinggi |
| `docs/video-tutorial-ujian/source/build_audio.py` | Tooling only: builds the narration with macOS 'say' (voice Damayanti, rate 168) and imageio_ffmpeg, then writes narration.wav and timeline.js from script.json, using 0.7/0.45/1.1 s lead, gap and tail silences. | — | — |
| `docs/video-tutorial-ujian/source/render.js` | Tooling only: a Playwright (Chromium) plus ffmpeg script that renders tutorial.html at 1280x720 and 25 fps into an MP4 with narration.wav. It also has a snap mode for screenshots. | — | — |
| `docs/video-tutorial-ujian/source/script.json` | Narration script for the student exam tutorial: 16 scenes, each with caption and TTS text. Covers preparation, opening the exam via link or QR, NIM-only access, error meanings, the exam page tour, autosave answering and  | tinggi | rendah |
| `docs/video-tutorial-ujian/source/timeline.js` | Generated timeline (window.TL) with scene and caption timings for the student tutorial. Total 482.68 s across 16 scenes. Every caption is identical to script.json (verified). | sedang | rendah |
| `docs/video-tutorial-ujian/source/tutorial.html` | HTML/JS animation that renders the student tutorial frames. It contains the exact mock UI strings for: the 'Akses Ujian' page (NIM field, Mulai Ujian); the exam-taking page (header with NIM and name, countdown with yello | tinggi | rendah |
| `docs/video-semester-pendek/mekanisme-semester-pendek.mp4` | Rendered explainer video (~19m45s) of mechanism, marked 'RANCANGAN, belum diterapkan' | sedang | rendah |
| `docs/video-tutorial-pmb/source/calon-mahasiswa.script.mjs` | Source data of applicant script (scenes, narration, cards) | — | — |
| `docs/video-tutorial-pmb/source/calon-mahasiswa.timeline.js` | Generated timeline JSON | — | — |
| `docs/video-tutorial-pmb/source/penyelenggara.script.mjs` | Source data of staff script | — | — |
| `docs/video-tutorial-pmb/source/penyelenggara.timeline.js` | Generated timeline JSON | — | — |
| `docs/video-tutorial-pmb/source/player.html` | Scene player used for rendering | — | — |
| `docs/video-tutorial-pmb/tutorial-pmb-calon-mahasiswa.mp4` | Applicant video 17:28 | — | — |
| `docs/video-tutorial-pmb/tutorial-pmb-penyelenggara.mp4` | Staff video 18:37 | — | — |
| `docs/video-tutorial-ujian/tutorial-ujian-dosen.mp4` | Rendered lecturer exam-management tutorial video (binary, not readable). Per metadata it is 1920x1080 and 558.08 s, which matches the source-dosen timeline; its content corresponds to source-dosen/script.json and tutoria | rendah | tinggi |
| `docs/video-tutorial-ujian/tutorial-ujian-mahasiswa.mp4` | Rendered student exam tutorial video (binary, not readable). Per metadata it is 1280x720 and 482.65 s, which matches the source/ timeline; its content corresponds to source/script.json and tutorial.html. The lecturer tut | tinggi | rendah |

## 4. Berkas tidak dibaca utuh / tidak dibaca

**Tidak dibaca utuh:**

- `docs/video-semester-pendek/mekanisme-semester-pendek.mp4` — D27-sp-testing-plan-video: binary video (60.6 MB); content equals script.json narration + presentasi.html slides, which were read
- `docs/video-tutorial-pmb/source/calon-mahasiswa.script.mjs` — D30-manual-keu-video-pmb: generated narration source; content read via naskah md (all 105 captions verified verbatim present in script via script check; only first 60 lines read directly)
- `docs/video-tutorial-pmb/source/calon-mahasiswa.timeline.js` — D30-manual-keu-video-pmb: generated narration source; content read via naskah md (single-line 68KB generated JSON; captions and total duration 1048.589s verified programmatically)
- `docs/video-tutorial-pmb/source/penyelenggara.script.mjs` — D30-manual-keu-video-pmb: generated narration source; content read via naskah md (all 98 captions verified verbatim present in script via script check)
- `docs/video-tutorial-pmb/source/penyelenggara.timeline.js` — D30-manual-keu-video-pmb: generated narration source; content read via naskah md (single-line 71KB generated JSON; captions and total duration 1117.802s verified programmatically)
- `docs/video-tutorial-pmb/source/player.html` — D30-manual-keu-video-pmb: rendering template (CSS/JS for video frames); only first 40 lines read, no product requirements
- `docs/video-tutorial-pmb/tutorial-pmb-calon-mahasiswa.mp4` — D30-manual-keu-video-pmb: binary video
- `docs/video-tutorial-pmb/tutorial-pmb-penyelenggara.mp4` — D30-manual-keu-video-pmb: binary video
- `docs/video-tutorial-ujian/tutorial-ujian-dosen.mp4` — D06-exam-docs-videos: binary video. Only metadata was read (1920x1080, 558.08 s, matching the source-dosen/timeline.js total of 558.065 s); the content is covered through the source-dosen narration/HTML sources
- `docs/video-tutorial-ujian/tutorial-ujian-mahasiswa.mp4` — D06-exam-docs-videos: binary video. Only metadata was read (1280x720, 482.65 s, matching the source/timeline.js total of 482.68 s); the content is covered through the source/ narration/HTML sources

**Screenshot manual book PMB yang diinventarisasi (nama berkas saja, 143 berkas):** materi panitia/pendaftar PMB, di luar cakupan aplikasi mahasiswa/dosen; berkas yang relevan (tampilan ponsel, portal, CBT, notifikasi) sudah dilihat dan tercantum di §3.

**Berkas tooling/duplikat yang tidak dibaca:** `docs/video-semester-pendek/source/.gitignore`, `docs/video-semester-pendek/source/logo-atmajaya.gif`, `docs/video-semester-pendek/source/package-lock.json`, `docs/video-tutorial-pmb/source/.gitignore`, `docs/video-tutorial-pmb/source/package-lock.json`, `docs/video-tutorial-ujian/source-dosen/.gitignore`, `docs/video-tutorial-ujian/source-dosen/logo-atmajaya.gif`, `docs/video-tutorial-ujian/source/logo-atmajaya.gif` (konfigurasi build video, lockfile, salinan logo).

## 5. Area kode yang diaudit

| Area | Cakupan | Endpoint dicatat | Celah untuk mobile |
|---|---|---|---|
| C1-be-conventions | Auth, Tenancy, UserManagement, middleware, seeder peran/izin | 15 | 8 |
| C2-be-academic-student | API Akademik untuk mahasiswa (`student/*`, ujian publik, pengumuman mahasiswa) | 60 | 9 |
| C3-be-academic-lecturer | API Akademik untuk dosen & dosen PA | 63 | 8 |
| C4-be-finance | API Keuangan mahasiswa (`student/finance/*`), webhook Xendit | 24 | 9 |
| C5-be-other-modules | Pengumuman, Notifikasi, FileManagement, ApprovalWorkflow, Dashboard, modul data pendukung | 31 | 10 |
| C6-be-hr | SDM: layanan mandiri dosen (`me/hr/*`), profil dosen | 12 | 5 |
| C7-fe-portals | Portal web mahasiswa/dosen (`frontend/src/pages/portal`, `keu/pages/portal`) | 148 | 11 |
| C8-fe-one-app | Prototipe web Civitas One (`frontend/src/one`, data statis, i18n ID/EN/ZH) | 0 | 3 |
| C9-mobile-scaffold | Aplikasi Flutter yang ada (`mobile/`) | 15 | 9 |

## 6. Kontradiksi antardokumen (dicatat pembaca)

- (D01-flutter-design) Android-only scope: the design doc title is 'Rancangan Aplikasi Android', only Android SDK levels are given, BAB 8 lists Android permissions/API levels only, release is APK/AAB plus Play Store only, flavors are Gradle-only, the env default 10.0.2.2 is an Android emulator alias, and a11y testing is TalkBack-only. This conflicts with an Android + iOS target: no iOS deployment target, Info.plist usag
- (D01-flutter-design) Existing mobile/ Flutter project has no ios/ directory (directory listing cross-check, not an assigned file), so the current codebase is Android-only.
- (D01-flutter-design) Android-only mechanisms need iOS alternatives: Dynamic Color, FLAG_SECURE via flutter_windowmanager (iOS cannot block screenshots), App Links assetlinks.json (iOS needs apple-app-site-association), Glance home widget (iOS WidgetKit), Android 12 Splash API config (android: true only), predictive back, Android notification channels, getExternalStorageDirectory (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#
- (D01-flutter-design) i18n supports only ID and EN (supportedLocales [id, en], app_id.arb/app_en.arb), with Indonesian as fixed default; Chinese (ZH) is absent. Formatters mirror id-ID number/date formatting. Inter font has no CJK glyphs (auditor note). Backend enum slugs are Indonesian (bekerja, terdaftar, dipinjam, universitas) and need per-locale labels (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.12, #5.3, #9.1, #10.3)
- (D01-flutter-design) Scope conflict: the doc plans to replicate all 71 pages including admin and super-admin modules, while the target app is students + lecturers only (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#1.3).
- (D01-flutter-design) No lecturer role or portal: the UI reads only the 'super_admin' and 'student' roles. Lecturers fall into the admin-oriented tenant dashboard and permission-filtered admin menus. There are no lecturer-specific screens (my classes, teaching schedule, advisees, HR self-service, EDOM results, grade finalization) and Fase 7 golden-path testing lists Super Admin, Admin Tenant and Mahasiswa only (docs/RA
- (D01-flutter-design) Dosen PA duties are missing: the student portal shows dosen PA info and advising sessions, but there is no lecturer-side KRS approval, advisee list or advising-notes entry. KrsItemStatus has only {enrolled, dropped}, yet PortalDashboard shows 'Status KRS: Disetujui' and PortalKrs shows a 'Badge Status KRS', implying unmodelled approval states (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.15.15, #6.2.3,
- (D01-flutter-design) Login wireframe label says 'Email atau Username' but validation requires a valid email format and the body is {email, password} (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.1.1, #2.2).
- (D01-flutter-design) Password minimum length is inconsistent: 6 characters at login vs 8 characters at reset (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.1.1, #6.1.3).
- (D01-flutter-design) BAB 1.1 says Portal pages other than Profil ('selain Profil') use static data, but 6.15.2 says PortalProfileScreen is also static with no backend (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#1.1, #6.15.2).
- (D01-flutter-design) Avatar dropdown 'Profil Saya' opens SecuritySessionsScreen, while the Portal menu 'Profil Saya' opens PortalProfileScreen (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#7.2, #6.15.2).
- (D01-flutter-design) Phase naming is ambiguous: 'Fase 1' is used to mean the initial release (1.3, 8.6, 8.12, Fase 5 note), while BAB 12 defines Fase 1 as 'Dashboard & Navigasi Inti' (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#1.3, #8.6, #8.12, #BAB 12).
- (D01-flutter-design) Atma Jaya branding (palette from the emblem, logo_atmajaya splash) is hardcoded, but the platform is multi-tenant and UniversityModel carries primaryColor, secondaryColor, locale, timezone, currency and dateFormat (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#2.3, #8.13, #10.3).
- (D01-flutter-design) Version baseline mismatch: README says Flutter SDK '^3.12.2'; the design doc says Flutter >= 3.35 / Dart >= 3.9.0 (pubspec sdk '>=3.9.0 <4.0.0'). Cross-check: actual mobile/pubspec.yaml has sdk ^3.12.2, go_router ^18.0.0 vs doc ^14.8.1, flutter_secure_storage ^10.3.1 vs ^9.2.2, version 1.0.0+1 vs 0.1.0+1 (mobile/README.md#Tech stack, docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#1.4, #9.2).
- (D01-flutter-design) README says core/network has Dio + an auth interceptor only; the design doc requires auth, tenant and error interceptors (mobile/README.md#Status, docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#3.4).
- (D01-flutter-design) flutter_windowmanager is referenced for FLAG_SECURE but is missing from the package table and pubspec (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#11.5, #9.1).
- (D01-flutter-design) Student check-in ('Lakukan Presensi') has no mechanism defined, and the Play Store Data Safety checklist states location is not collected, which rules out geo-attendance as designed (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.15.8, #13.4).
- (D01-flutter-design) Students cannot reach personal notification preferences or approval requests from the Portal nav (no Pengaturan/Persetujuan items), although those routes are not blocked (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.14.6, #7.1, #7.3).
- (D01-flutter-design) CBT/exam is not covered by the design doc or README, but mobile/lib/features/exam/ exists in code (exam list/form/preview, question bank screens). The doc's last commit message is 'bank soal' (cross-check via directory listing).
- (D01-flutter-design) android/app/RANCANGAN_APP_FLUTTER.md is empty (0 bytes), a dead placeholder outside mobile/.
- (D02-app-design) Grade scale: docs/RANCANGAN-APLIKASI.md#4.16 gives a 9-tier scale (85-100 A 4,00; 80-84 A- 3,75; 75-79 B+ 3,50; 70-74 B 3,00; 65-69 B- 2,75; 60-64 C+ 2,50; 55-59 C 2,00; 40-54 D 1,00; 0-39 E 0,00) that administrators must be able to adjust. docs/RANCANGAN-AKUN-DOSEN.md#6.1 and docs/RANCANGAN-AKUN-AKADEMIK.md#6.2 describe the implemented 7-tier LetterGrade::fromScore() (>=85 A 4,00; 75-84 AB 3,50; 
- (D02-app-design) SKS cap: the blueprint tiers (#4.10: <2,00 -> 18; 2,00-2,49 -> 20; 2,50-2,99 -> 22; >=3,00 -> 24) match the implementation values. However, docs/RANCANGAN-AKUN-AKADEMIK.md#6.1 says the implementation uses the previous semester's IP, defaults to 24 for students with no grade history (not in the blueprint), and hardcodes AcademicRecordService::SKS_LIMIT_TIERS. The blueprint requires the cap to be co
- (D02-app-design) Attendance: the blueprint (#4.14) defines 6 statuses (Hadir, Terlambat, Izin, Sakit, Alpa, Dispensasi) and 8 methods (manual, QR, PIN, GPS, geofencing, NFC, face, machine). docs/RANCANGAN-AKUN-DOSEN.md#6.2 describes the implementation as 4 statuses (present/permitted/sick/absent), manual input only. docs/STATUS-IMPLEMENTASI.md marks QR/PIN/GPS/NFC/face attendance as not implemented.
- (D02-app-design) Advisor KRS approval and perwalian: required by the blueprint (#3.7, #4.10, #4.19). docs/STATUS-IMPLEMENTASI.md (17 Oct 2026) says the KRS approval API (krsApprovalService) exists with no page, and that perwalian notes, advisee list, at-risk students and appointments are not implemented.
- (D02-app-design) EDOM: the blueprint #4.33 specifies the module, but docs/STATUS-IMPLEMENTASI.md lists EDOM among modules that do not exist yet (portal pages are unrouted mock data).
- (D02-app-design) Mobile platform: the blueprint #12 leaves Flutter vs React Native open. The repo already has a Flutter app (mobile/), and docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md designs an Android-only Flutter app, while this mobile project targets Android + iOS. docs/STATUS-IMPLEMENTASI.md lists push, biometrics, offline, deep link and multi-language as not implemented; the blueprint does not mention offline, biom
- (D02-app-design) Internal (blueprint): the §13 module list (Authentication, Student, StudyPlan, Examination, Grading, ...) does not match the real module names cited in its own status notes (app/Modules/Academic for exams).
- (D02-app-design) Internal terminology: 'dosen wali' and 'Dosen Pembimbing Akademik' are used interchangeably (#3.7, #4.4, #4.10, #4.19, #6).
- (D02-app-design) Internal overlaps: alumni job vacancies (#4.35) vs the active-student career center (#21.6), which the doc says to keep separate; EDOM (#4.33) should be built on the generic survey engine (#21.10); 'Ujian' appears both inside Perkuliahan (#4.11) and as its own module (#4.18).
- (D02-app-design) Internal sequencing: Ujian is in Tahap 3 (#18) but missing from the MVP (#19); bill payment is a core student feature (#3.8) but finance is deferred to Tahap 5 and after the MVP.
- (D02-app-design) Consistent, not a conflict: the student CBT exam-taking screen is 'not built' in the blueprint #4.18 (note dated 2026-08-27), EXAM_MODULE.md, EXAM_RANDOMIZATION.md and STATUS-IMPLEMENTASI.md.
- (D03-role-accounts-status) Student permissions & API: RANCANGAN-AKUN-MAHASISWA §2/§5 (2026-09-11) says the student role has only exam_participation.read/create/update and 7 endpoints. STATUS-IMPLEMENTASI §3/§6 (17 Oct 2026) says KRS self-service, KHS, grades, transcript, attendance, schedule, calendar, materials, assignments, requests and finance are ✅ in the portal. The current student permissions and endpoint paths are no
- (D03-role-accounts-status) Portal page count/status: MAHASISWA §4.1 says 21 pages with 2 live; AKADEMIK §4.7 and FRONTEND §1/§5.2 say 18 pages, all static; STATUS §6 says most are ✅, with settings 🧩 (routed) and 8 sample pages (academic advising, thesis advising, scholarship, EDOM, wisuda, quiz, old leave/letter forms) 🧩 and NOT routed.
- (D03-role-accounts-status) Student dashboard: MAHASISWA §4.4 says it is empty (EmptyState 'Belum ada statistik untuk ditampilkan.'); STATUS §6 says Dashboard ✅.
- (D03-role-accounts-status) Assignments: MAHASISWA §4.1/§4.5 and AKADEMIK §4.9 say there is no Assignment backend module; STATUS §4 says assignments ✅ for students and ⚙️ for lecturers.
- (D03-role-accounts-status) Leave/letter requests: MAHASISWA §4.5/§10 say there is no backend; STATUS §3/§6 say requests via ApprovalWorkflow are ✅ (PortalRequestsPage).
- (D03-role-accounts-status) Student finance: MAHASISWA §4.1 says there is no self-service finance endpoint, and FRONTEND §5.1 (Aug) says Finance is read-only; STATUS §8 says the student finance portal is ✅.
- (D03-role-accounts-status) Announcements: MAHASISWA §10 says there is no backend and FRONTEND §5.1 says read-only, seeder only; STATUS §10 says CRUD + portal reading ✅.
- (D03-role-accounts-status) Lecturer identity link: DOSEN §1/§3/§8.2 and AKADEMIK §3/§8 say Lecturer has no user_id; STATUS §3 says lecturers get login accounts, and §15 records two identity paths (employees.user_id → lecturers.employee_id vs lecturers.user_id).
- (D03-role-accounts-status) Class lecturer & schedule: DOSEN §3/§10 and AKADEMIK §4.9 say class_sections has no lecturer_id and no day/time/room; STATUS §3 says lecturer & schedule assignment (day, time, room as text) with clash check is ✅ (one lecturer per class, room free text per §15).
- (D03-role-accounts-status) Class ownership validation: the Sep docs say lecturers can act on any class (NIL-09/ABS-08) with no ownership data; STATUS §3 says flag academic.lecturer_ownership 🟡, enabled per tenant.
- (D03-role-accounts-status) Dosen PA: DOSEN §4.7 and AKADEMIK §4.8 say there is no advisor relation table or KRS approval endpoint (role academic_advisor, zero permissions); STATUS §3/§7 says dosen wali assignment ⚙️ and KRS approval by dosen wali + notification ⚙️ (krsApprovalService, route /akademik/persetujuan-krs without page). Naming also differs: 'dosen wali' vs academic_advisor.
- (D03-role-accounts-status) Lecturer profile: DOSEN §4.8/§10 says there is no self-profile or write endpoint; STATUS §3/§7 says 'profil dosen mandiri' ✅.
- (D03-role-accounts-status) Master data writes: AKADEMIK §4.1/§10 says no create/update for any master data; STATUS §3 says add student ✅ and lecturer CRUD ✅, while faculties/prodi/curricula/courses remain read-only.
- (D03-role-accounts-status) Exam recap: DOSEN §10 says there is no aggregate recap screen; STATUS §5 says ExamRecapPage ✅ (recap, participants, CSV/PDF export, question PDF).
- (D03-role-accounts-status) Exam scoring: MAHASISWA §4.2 says score = proportion of correct MC points; STATUS §5 adds an automatic 1-point penalty per violation, weights ('bobot') and grade ranges per exam, without giving the combined formula.
- (D03-role-accounts-status) Mobile dashboard: DOSEN §9 calls dashboard_screen.dart a Phase 0 placeholder; STATUS §14 says Dashboard ✅ (dasar).
- (D03-role-accounts-status) Student exam taking: AKADEMIK §4.6/§10 says the endpoint exists but the screen does not (🧩); MAHASISWA §4.1 and STATUS §5 say web PortalExamTakingPage is ✅. Consistent only if AKADEMIK means Flutter, which STATUS also marks ❌.
- (D03-role-accounts-status) Connecting the portal: AKADEMIK §4.7/§11.3 says KRS/schedule/KHS/grades/attendance/transcript can be connected now with existing endpoints; MAHASISWA §8.1 says granting admin permissions would leak whole-tenant data and new self-service endpoints are required; FRONTEND §5.2 says the identity link is needed first.
- (D03-role-accounts-status) Lecturer reach over students: AKADEMIK §2 says lecturers read limited master data, 'not students outside that'; DOSEN §4.1 says students.read lets lecturers see any student in the tenant.
- (D03-role-accounts-status) AKADEMIK §9 says the Akademik menu has '8 halaman' but lists 9 items.
- (D03-role-accounts-status) Login route: the Sep docs (MAHASISWA §4.6, DOSEN §4.8) warn about an uncommitted change of login from POST to GET; STATUS §1 (Oct) lists login ✅ with no mention of it.
- (D03-role-accounts-status) MULTI-TENANT-ARCHITECTURE §9 (Jul 2026) says academic/finance modules do not exist and support session is table-only; STATUS (Oct) shows both built (Support Session ✅). Per-tenant file folders and global notification-template fallback remain ❌ in both.
- (D04-keu-master) Reminder schedules disagree inside the doc. FR-CCL-07 says H-7, H-3, H-1, hari H, H+1. F-15 step 3, FR-PTG-03 and keu.reminders.offsets_days [-7,-3,-1,1,7,30] have no day-0 reminder and add H+7 and H+30. The §10.7 keys due_reminder (H-7/H-3/H-1) and overdue (H+1/H+7/H+30) match the config, not FR-CCL-07 (§6/F-07 vs §6/F-15, §9.5, §10.7).
- (D04-keu-master) Installment limit scope differs: F-07 step 1 says 'maksimal satu cicilan aktif per kelompok' (per bill group), while BR-KEU-30 says 'maksimal satu rencana aktif per tagihan' (per invoice) (§6/F-07 vs §8.1.6).
- (D04-keu-master) BR-KEU-32 says term 1 is due no later than the registration payment deadline. In the worked example (§8.3.9) the SKS invoice's term 1 is due 30 Sep, after the 31 Aug registration due date. Either the rule only applies to the registration group, or the example contradicts it.
- (D04-keu-master) Clearance and running penalties: BR-KEU-70, the F-14 yudisium row and AC-CKL-06 require running penalty estimates to block clearance. The FinanceBlockSource condition parameter include_penalties only adds crystallized penalty invoices (§10.3), so the KEU-YUDISIUM rule cannot express the running-estimate condition.
- (D04-keu-master) Voucher lockout threshold is ambiguous. FR-VCR-05 says 'maks. 5 kode gagal per 10 menit'. BR-KEU-29 says 'lebih dari 5 kode gagal → dikunci 30 menit'. AC-VCR-03 says 5 wrong codes → the 6th attempt gets 429 (§6/F-06, §8.1.5, §15.2).
- (D04-keu-master) Dispensation permission gap: §4.6 and F-14 let the student (MHS) and dosen wali (DW) submit financial dispensations. The §4.4 matrix grants academic_block_exemptions.create only to FM and FA, and DW only has finance_clearances.read (M). No DW path or permission exists.
- (D04-keu-master) Voucher phase mismatch: FR-PRT-02 (MVP) and the portal tree include 'Masukkan Voucher', but all of F-06 (vouchers) is Phase 2 (§6/F-16 vs §6/F-06, §16.5).
- (D04-keu-master) Two keys limit dispensation length: academic.blocks.exemption_max_days (SIAKAD; no value given) and keu.dispensation.staff_max_days = 7 (BR-KEU-67).
- (D04-keu-master) The §10.5 API summary is missing student endpoints that FRs require: cancel a pending transaction (F-13 step 2), transaction history, kartu piutang PDF (FR-PRT-04/FR-RFD-01), deposit ledger, refund/dispensation/installment status, clearance status and letter download, and claiming unidentified funds (FR-REK-04, AC-REK-04).
- (D04-keu-master) Implementation status vs other doc: the design doc (15 Oct 2026, branch feature/pmb-admission @24f5684) says no code exists and the cekal engine is not built. docs/STATUS-IMPLEMENTASI.md (17 Oct 2026, main @70e2f52, §8) says KEU is built (157 routes) with 'Portal keuangan mahasiswa ✅', cekal for KRS & exams with dispensation and auto-lift ✅, vouchers, penalties and installments ✅. It still lists o
- (D04-keu-master) Payment provider: the design doc refers to the PMB Midtrans adapter (§1.4, §6/F-09); docs/STATUS-IMPLEMENTASI.md §8 refers to Xendit for KEU online payment.
- (D04-keu-master) Implemented student routes in app/Modules/Finance/Routes/api.php (prefix student/finance, auth:sanctum + tenant.access) differ from the §10.5 design: - design POST /student/finance/invoices/{id}/payments → code POST payments/online + POST payments/manual - design POST /student/finance/installment-requests → code POST invoices/{invoice}/installments - design POST /student/finance/deposit/apply → co
- (D04-keu-master) Permission naming: the design uses finance_clearances.read and GET /finance-clearances/{studentId}; the code uses finance_clearance.read on GET /finance/students/{student}/clearance, with admin routes under a /finance prefix.
- (D04-keu-master) Design doc vs older docs (recorded in §3.3): SEVIMA 'mahasiswa yang belum lunas otomatis tidak bisa KRS/ujian' is reinterpreted as configurable rules (INK-KEU-05). RANCANGAN-APLIKASI §4.23 mixes invoice and transaction statuses (INK-KEU-03). PMB partially_paid vs Finance partial is a display label only (INK-KEU-16). RANCANGAN-AKUN-MAHASISWA describes a static portal invoices page (superseded). aca
- (D05-sdm-superadmin-sevima) RANCANGAN-AKUN-SDM contradicts itself on whether the HR module exists. §1 says the domain has no module of its own and only 1 of 22 modules is fully running; §4.2 says none of the designed tables exist. But §3 row 4 and §5.4 (dated 2026-10-08) say Data Tendik is ✅, built on employees with staff_category, a position_id -> positions master, work_unit_id, /hr/staff and /hr/employees endpoints, and a 
- (D05-sdm-superadmin-sevima) RANCANGAN-AKUN-SDM §2/§7.2/§8.2 vs the repo (REPO OBSERVATION). The doc gives lecturer and employee zero HR permissions and plans self-service as me/... with self_service_hr.*. The repo's OrganizationalRoleSeeder grants hr_self_service.read/create/update to lecturer, academic_advisor and employee. The actual routes are /me/hr/profile, /me/hr/leave-requests (+ /{id}/submit, /{id}/cancel) and /me/hr
- (D05-sdm-superadmin-sevima) Permission names differ between the SDM design and the repo HR routes (REPO OBSERVATION). Design: leave_requests.*, personnel_documents.*, employment_contracts.*, hr_audit_logs.read, hr_exports.create. Repo: hr_leave.(read\|create\|approve\|reject), hr_documents.read, hr_contracts.read, hr_audit.read, hr_requests.(approve\|reject), hr_reports.read.
- (D05-sdm-superadmin-sevima) docs/STATUS-IMPLEMENTASI.md §9 (excerpt only) contradicts the status in RANCANGAN-AKUN-SDM (2026-10-06). STATUS marks most SDM modules ⚙️ (backend only), including employee self-service (profil, cuti, pengajuan); Kehadiran pegawai ❌; BKD ❌; and 'Aktivitas penelitian & pengabdian dosen (catatan per dosen)' ⚙️. The SDM design marks most modules ❌ and has no research/PkM module at all. STATUS also na
- (D05-sdm-superadmin-sevima) The academic rank ladder differs across documents. RANCANGAN-AKUN-SDM §3: 'Asisten Ahli -> Lektor -> Lektor Kepala -> Profesor'. §5.14 adds 'Tenaga Pengajar' as the first level. The §4.2 academic_ranks names omit Tenaga Pengajar. SEVIMA §18 (PAK) ends with 'Guru Besar' instead of Profesor.
- (D05-sdm-superadmin-sevima) Academic-rank promotion status and approval chain are inconsistent in RANCANGAN-AKUN-SDM. §5.14 has 4 statuses (diusulkan -> disetujui internal -> SK terbit -> aktif); §4.2 academic_rank_histories.status has 3 (proposed\|approved\|active). The §5.14 chain ends with an optional Rektor; the §10.4 workflow stops at hr_administrator.
- (D05-sdm-superadmin-sevima) RANCANGAN-AKUN-SDM §5.17 and §4.2 (attendance_corrections.hr_request_id) use request type attendance_correction, but the hr_requests.request_type enum in §4.2 does not include it.
- (D05-sdm-superadmin-sevima) Status vocabularies don't line up in RANCANGAN-AKUN-SDM. leave_requests uses draft\|pending\|returned\|approved\|rejected\|cancelled. hr_requests uses draft\|submitted\|returned\|approved\|rejected\|cancelled\|applied. Actual approval requests use submitted\|in_progress\|approved\|rejected, with 'returned' only designed.
- (D05-sdm-superadmin-sevima) Contract-expiry notification recipients differ in RANCANGAN-AKUN-SDM. §5.9 says H-90/H-60/H-30 to Bagian SDM and the direct supervisor; §5.21 hr.contract_expiring also includes the employee (pegawai).
- (D05-sdm-superadmin-sevima) RANCANGAN-AKUN-SDM §13 says '20 event SDM', but the §5.21 table has 18 rows and 21 template keys.
- (D05-sdm-superadmin-sevima) Role slugs differ. RANCANGAN-SUPER-ADMIN §4 lists seeded global roles 'dosen' and 'mahasiswa'. RANCANGAN-AKUN-SDM §2 and the repo seeder use 'lecturer' and 'student'. The repo also has 'academic_advisor' (label 'Dosen Pembimbing Akademik'), which none of the three docs mention.
- (D05-sdm-superadmin-sevima) RANCANGAN-SUPER-ADMIN §8 (2026-07-19) says Mahasiswa, Akademik, Dosen, Pegawai, Keuangan, Skripsi, Magang/MBKM, Perpustakaan and Alumni have no tables or modules. That is outdated: RANCANGAN-AKUN-SDM (2026-10-06) documents the lecturers, employees, theses and internships tables, and the repo has Academic, Finance, Thesis, Internship, Library and Alumni modules.
- (D05-sdm-superadmin-sevima) RANCANGAN-AKUN-SDM §12.1 cites RANCANGAN_FLUTTER_CIVITAS_ONE.md §6.5.5-6.5.6, which plans read-only EmployeesScreen/EmployeeDetailScreen for mobile. But per §2 the lecturer role has neither employees.read nor lecturers.read, so these directory screens don't fit a student/lecturer-only app.
- (D05-sdm-superadmin-sevima) BKD naming and SDM scope differ. Naming: 'Beban Kerja Dosen' (RANCANGAN-AKUN-SDM §6), 'Beban Kinerja Dosen' (SEVIMA §18), 'Beban Kerja/Kinerja Dosen' (SEVIMA glossary). Scope: SEVIMA's base SDM module (Business tier) includes BKD, research/PkM data, SISTER sync, remuneration and PAK. The CivitasOne SDM design defers BKD to Fase 5 and has no research/PkM, SISTER, remuneration or PAK features.
- (D06-exam-docs-videos) Student exam access model. docs/EXAM_MODULE.md#Model Data (Catatan desain) says ExamAttempt is bound to KrsItem and attempts are 'recorded by authorised users (dosen/pengawas) on behalf of the participant' through permission-gated endpoints (POST /krs-items/{krsItem}/exams/{exam}/attempt needs exam_attempts.create, held by role lecturer). The student tutorial (docs/video-tutorial-ujian/source/scri
- (D06-exam-docs-videos) Implementation status. docs/EXAM_MODULE.md#Flutter and docs/EXAM_RANDOMIZATION.md#10 (git 2026-08-27) say the real student exam-taking screen (timer, navigation, submit) is not built. The student tutorial (sources dated 2026-10-06) shows a complete web exam flow: timer, autosave, navigation, submit, result and PDF. The web flow was likely built later, but its Flutter status is not stated.
- (D06-exam-docs-videos) Data model and API gap. docs/EXAM_MODULE.md#Model Data and #Endpoint API list only exams, exam_questions, exam_question_options, exam_attempts and exam_attempt_answers, with 13 endpoints. The lecturer tutorial (docs/video-tutorial-ujian/source-dosen/script.json#bank, #grade, #access, #monitor, #violations, #recap) uses features with no documented table or endpoint: a reusable Bank Soal with a 'Dip
- (D06-exam-docs-videos) Lecturer client. docs/EXAM_MODULE.md#Flutter describes Flutter (mobile/lib/features/exam) as the lecturer/admin configuration client, with a 3-step ExamFormScreen wizard (Informasi, Soal, Preview & Publish). The lecturer tutorial (docs/video-tutorial-ujian/source-dosen/script.json#intro) says 'Semua langkah dilakukan lewat browser, di menu Akademik. Tidak perlu aplikasi tambahan.' and shows a sing
- (D06-exam-docs-videos) Role naming. docs/EXAM_MODULE.md#Permission grants exams.* and exam_attempts.* to role 'lecturer'. docs/BLACK-BOX-TESTING.md#Akun Uji uses the role names 'dosen', 'akademik' (academic_administrator), 'mahasiswa' and 'dosenpa', while RBAC-05 refers to a system role with slug 'student'. BLACK-BOX's dosen permission list (2026-08-09) contains no exams.* permissions because it predates the exam module
- (D06-exam-docs-videos) Dosen PA. docs/BLACK-BOX-TESTING.md#Akun Uji (2026-08-09) says role 'dosenpa' has no permissions and self-service does not exist. The lecturer tutorial UI (docs/video-tutorial-ujian/source-dosen/tutorial.html#sidebar, 2026-10-07) shows 'Persetujuan KRS' and 'Kelas Saya' submenus for a Dosen account, implying a KRS-approval screen exists. It is not described in any assigned file.
- (D06-exam-docs-videos) docs/BLACK-BOX-TESTING.md#24 says the student portal has '18 halaman' but lists only 17 page names (KRS, Jadwal, KHS, Transkrip, Nilai, Absensi, Tugas, Kuis, Cuti, Surat, Beasiswa, Tagihan, Bimbingan Akademik, Bimbingan Skripsi, Pengumuman, Evaluasi Dosen, Wisuda).
- (D06-exam-docs-videos) Participant status labels differ within the lecturer tutorial. The exam detail table uses 'Sudah Mengerjakan' for completed (docs/video-tutorial-ujian/source-dosen/tutorial.html#ST_LBL), while the Rekap Nilai page uses 'Selesai', including in its status filter 'Belum Mengerjakan / Sedang Mengerjakan / Selesai' (docs/video-tutorial-ujian/source-dosen/tutorial.html#recapBody).
- (D06-exam-docs-videos) The student tutorial refers to lecturer-controlled settings that do not appear in the lecturer tutorial's 'Buat Ujian'/'Ubah Ujian' forms or question form (docs/video-tutorial-ujian/source-dosen/tutorial.html#createModal, #editModal, #bankModal): disabling backward navigation ('navigasi mundur', source/script.json#answer), showing results immediately 'jika dosen mengizinkan' (source/script.json#re
- (D06-exam-docs-videos) Error wording. The student narration caption says '“Link sudah tidak berlaku”' (docs/video-tutorial-ujian/source/script.json#errors), but the on-screen message is 'Ujian tidak ditemukan atau link sudah tidak berlaku.' (docs/video-tutorial-ujian/source/tutorial.html#S.errors).
- (D07-sevima-pmb-design) INK-05: SEVIMA_Penjelasan_Modul.md says an applicant 'otomatis mendapat NIM dan dipindahkan ke SIAKAD sebagai mahasiswa aktif' and that payment is 'terverifikasi otomatis'. The PMB design rejects this: active student status only after final re-registration approval (BR-33). (docs/RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md#5.3 Inkonsistensi penting yang memengaruhi rancangan)
- (D07-sevima-pmb-design) GAP-09: RANCANGAN-APLIKASI.md §4.2 uses one combined PMB status list (Draft...Menjadi mahasiswa). The PMB design replaces it with 11 separate state machines. (docs/RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md#5.3 Inkonsistensi penting yang memengaruhi rancangan)
- (D07-sevima-pmb-design) SEVIMA Karirlink module is listed as Professional+, but several sub-features are marked available from Growth (the doc's own note). (docs/SEVIMA_Platform_Proposal.md#3.9 Tracer Study & Karirlink (Career Center))
- (D07-sevima-pmb-design) INK-01..04, INK-06: CRM integration in PMB at all tiers vs CRM module Business+; RPL path at all tiers vs RPL module Professional+; ODS at all tiers vs CBT Business+; 'Lebih Lengkap' undefined (also used for KRS online and tuition tariff management in SEVIMA); payment channel count tied to package. (docs/RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md#5.3 Inkonsistensi penting yang memengaruhi r
- (D07-sevima-pmb-design) The PMB design (10 Oct 2026) says 'Belum ada modul PMB', but recent repo commits ('pmb', 'Merge origin/main: landing page & PMB Atma Jaya ke dalam modul KEU') suggest PMB work has since landed inside the KEU module. The implementation status in the doc may be outdated. This is an observation from the git log, not from the document.
- (D07-sevima-pmb-design) SEVIMA offers iOS only from the Professional tier and Android in all tiers. The CivitasOne mobile plan targets Android and iOS equally, a scope difference from the benchmark.
- (D07-sevima-pmb-design) KD-07: CivitasOne's academic exam table `exams` is tied to class_section_id/krs_item_id. Any mobile CBT for non-class exams depends on the Phase 2 generalised exam engine. (docs/RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md#1 Ringkasan Eksekutif)
- (D08-proposal-pdf) Proposal says Flutter (Android) only; target includes iOS
- (D08-proposal-pdf) Proposal says Flutter has login, dashboard, lecturer exam module; mobile/README reportedly claims auth bootstrap only
- (D08-proposal-pdf) Proposal places full mobile app in Tahap 4 (last)
- (D08-proposal-pdf) Languages ID/EN only
- (D08-proposal-pdf) Grade scale and SKS caps are defaults to be agreed with Atma Jaya
- (D09-samples-notes) catatan says mobile app does not exist, while mobile/ has a minimal Flutter app
- (D09-samples-notes) Webhook paths differ (/finance/webhooks/payments/{provider} vs /webhooks/xendit); explained, mobile never calls
- (D09-samples-notes) IMP-04: transcript/grades not blocked by finance although catalogue lists them
- (D09-samples-notes) Samples inconsistent: SKL avg 88,42 vs Ijazah 89.9 (computed 90.06); rapor sem1 total 1.003 vs sum 1,007; KTP persona differs
- (D09-samples-notes) Filename 'kwitansi' is actually an ATM transfer slip
- (D10-al-siakad) 16-open-decisions.md referenced by README and 01 does not exist; all OD-xx unresolved
- (D10-al-siakad) KRS status rejected (code) vs revision_requested (canon); student.krs_rejected key kept
- (D10-al-siakad) student_requests types data_change/other used in 01 but README lists only leave/reactivation
- (D10-al-siakad) Grade publisher: BAAK (A.6.3) vs BAAK or Kaprodi (A.6.12)
- (D10-al-siakad) README says mobile reads docs 02/03/08/09 but not 01 though KRS/attendance/grades are core mobile flows
- (D10-al-siakad) Web portal pages for EDOM, perwalian, cuti, kuis are mock per README 3.3 while 01 marks backend (Ada)
- (D10-al-siakad) Attendance status enum not specified (4 statuses unnamed)
- (D11-al-lms-cbt) Flutter status: 02#6.1 says Android app "(Parsial: Flutter baru auth/dashboard/exam)"; 03#2.1 clarifies Flutter only has exam **configuration** (wizard, question bank, preview — lecturer side) and **no exam-taking screen**; 03#14 "Mahasiswa mobile belum bisa ujian di aplikasi". So "exam" in Flutter = lecturer config only.
- (D11-al-lms-cbt) Violation penalty: existing code auto-deducts 1 point per violation (03#2.1, 03#8.5) vs design default 0 for new exams (old exams migrated to 1) — mobile must not assume either; display per-exam config.
- (D11-al-lms-cbt) Test scenario 13 (03#13) cites BR-CBT-INT-10 for lockout after 5 wrong codes, but INT-10 is about hashed access codes; §9.5 says lockout "setelah n kegagalan" (no fixed 5).
- (D11-al-lms-cbt) QR rate-limit example 10 attempts/min (02#4.3.4) vs join-code attempts also 10/min (02#2.5) — consistent but distinct.
- (D11-al-lms-cbt) QR endpoints differ: 02#4.3.2/02#9 use `POST /teaching/lecture-sessions/{id}/qr/open`, `POST .../qr/close`, `GET .../qr-token` (perm `lecture_sessions.update`) and student `POST /lms/attendance/qr-check-in` (perm `attendance.create` granted to student). 08 §2.6 uses `POST /teaching/lecture-sessions/{lectureSession}/qr-tokens` (issue rotating QR, `attendance.create` + pengampu, validity example 30 
- (D11-al-lms-cbt) QR response shape differs: 02#9 -> 200 with `checked_in_at`, `flags: ["late"]`, error `errors.code: ["qr_token_expired"]` (array, lowercase). 08 §3.3 -> 201 with `attendance_id`, `class_section{id, course_name, class_code}`, `recorded_at`; repeat scan -> 200 with `meta.code = ALREADY_RECORDED`; error `errors.code: "QR_TOKEN_EXPIRED"` (string, uppercase) + `lecture_session_id`. 08 request body has 
- (D11-al-lms-cbt) QR rate limit: 02#4.3.4 example 10 attempts/min/user vs 08 rate-limit table `qr-scan` 6/min; 30/hour per user and 60/min per IP.
- (D11-al-lms-cbt) QR idempotency/upsert key: 02 BR-LMS-QR-01 `(krs_item_id, meeting_number)` vs 08 natural idempotency `krs_item_id + lecture_session_id`.
- (D11-al-lms-cbt) Attendance `method` enum: 02#4.3.3 `manual / qr / ai_face / import` vs 01-siakad (A.5) `manual, qr, import, ai`.
- (D11-al-lms-cbt) Material progress endpoint: 02 `POST /lms/materials/{id}/progress` vs 08 `POST /student/lms/materials/{courseMaterial}/progress`.
- (D11-al-lms-cbt) Forum endpoints: 02 `/lms/classes/{id}/threads`, `/lms/threads/{id}/posts`, `PATCH /lms/posts/{id}/hide` vs 08 `/lms/classes/{lmsClass}/discussions`, `/lms/discussions/{thread}/posts`, `PATCH /lms/discussion-posts/{post}`.
- (D11-al-lms-cbt) Duplication: 02 `POST /lms/classes/{id}/duplicate-from` (perm `lms_classes.update`) vs 08 `POST /lms/classes/{lmsClass}/duplicate` (perm `lms_classes.create`) and `POST /lms/classes/{lmsClass}/materials/copy`.
- (D11-al-lms-cbt) Archive verb: 02 `PATCH /lms/classes/{id}/archive` vs 08 `POST /lms/classes/{lmsClass}/archive`.
- (D11-al-lms-cbt) Dashboard: 02 `GET /lms/analytics/dashboard` vs 08 `GET /lms/dashboard/{role}` (`lecturer`, `student`, ...; student perm `student_portal.read`). 08 also lists `/lms/engagement-scores`, `/lms/lecture-performance-scores`, `/lms/gamification/leaderboard`, `/lms/classes/{lmsClass}/participants`, `/lms/classes/{lmsClass}/activity` not in 02.
- (D11-al-lms-cbt) Snapshot upload: 03 `POST /student/exam-attempts/{id}/snapshots` vs 08 `POST /student/exam-attempts/{examAttempt}/proctoring-snapshots`.
- (D11-al-lms-cbt) Exam start/answer error codes: 03 `participant_blocked`, `attempt_time_over`, stale handling `200 stale:true` vs 08 `ACADEMIC_BLOCKED`, `ATTENDANCE_BELOW_MINIMUM`, `ATTEMPT_CLOSED`, `ANSWER_OUT_OF_ORDER`; 08 adds `essay_text` field to answer payload (03 uses `answer`/`answer_text`).
- (D11-al-lms-cbt) Voiding: 03 via `POST /exam-attempts/{id}/integrity-decision` + ApprovalWorkflow (committee `exam_proctoring.approve`) vs 08 `POST /exam-attempts/{examAttempt}/void` (perm `exams.update`).
- (D11-al-lms-cbt) Roadmap: 03#14 says Flutter exam-taking is roadmap "Tahap 5"; 15-implementation-roadmap names it "Tahap 5b — CBT dan Integrasi Nilai" (and LMS = Tahap 4). Consistent but more precise.
- (D12-al-obe-edom-arch) EDOM participations created at open (05) vs when student opens form (06 R7)
- (D12-al-obe-edom-arch) Participation status submitted (05) vs completed (06 7.4)
- (D12-al-obe-edom-arch) edom_reports at analyzed (05) vs at close (06 7.4)
- (D12-al-obe-edom-arch) Gate class EdomGradeGate (05) vs GradeVisibilityGate (06); grade_view as block service point vs rejected option
- (D12-al-obe-edom-arch) Grade endpoint GET /student/...grades vs GET student/khs
- (D12-al-obe-edom-arch) RPS->components auto copy (04 7.4) vs template, not overwritten (06 R3)
- (D12-al-obe-edom-arch) Session-RPS linking at generation (04) vs LessonPlanRatified listener (06)
- (D12-al-obe-edom-arch) Snapshot is_current=false (04) vs superseded (06); idempotency keys differ
- (D12-al-obe-edom-arch) Component-CPMK weights sum 100% (06 R6) vs sum = assessment weight_percent (04)
- (D12-al-obe-edom-arch) Team-teaching journal conflict code SESSION_CONFLICT vs STALE_VERSION
- (D13-al-erd) academic_terms.status 8 values not yet in README section 5.4 canon (ERD#6.6)
- (D13-al-erd) Short-semester design differs: krs_items.status += rejected not adopted (use dropped + drop_reason); krs_submissions legacy rejected -> revision_requested; grades.status draft/submitted/finalized replaced by grade_submissions + is_published; grade_revision_requests replaced by grade_revisions (ERD#6.6)
- (D13-al-erd) Thesis legacy status mapping (B6) is a proposal; must align with 01-siakad.md A.9 (ERD#6.3)
- (D13-al-erd) Doc states its own column names override other docs 01-06,08-16 where they differ (ERD header)
- (D13-al-erd) Doc is a design: most tables are planned, so real backend columns may differ; verify against code
- (D14-al-api-roles) 11#2 L1/L2 failures described as errors.feature [not_in_plan\|disabled] while 08#1.6/1.7 define errors.code FEATURE_NOT_ENTITLED / FEATURE_DISABLED.
- (D14-al-api-roles) 08#2.5 /advising/krs-submissions/{id}/approve duplicates existing POST /krs-submissions/{id}/approve (same service); mobile must choose, advising route is Baru.
- (D14-al-api-roles) 08#4 notes POST /student-requests/{id}/approve\|reject uses approval_requests.read (inconsistent, flagged in 11).
- (D14-al-api-roles) Status krs_submissions.rejected vs revision_requested: reject endpoint kept as alias, old clients show wrong label (11#9 row 7).
- (D14-al-api-roles) Lecturer ownership flag academic.lecturer_ownership default OFF today (11#9 row 4) so lecturers may currently write other classes' grades.
- (D15-al-uiux-rules) Notification key naming: 09 uses krs.approved etc.; 10 uses student.krs_approved (09 says final per notification_templates).
- (D15-al-uiux-rules) krs_submissions enum currently draft\|submitted\|approved\|rejected; target adds revision_requested/locked/cancelled (rejected migrated) - mobile must handle both during transition.
- (D15-al-uiux-rules) If-Match required for UI transitions but optional for legacy mobile during transition (BR-SEC-STATE-04).
- (D15-al-uiux-rules) All policy numbers (SKS tiers, 75%, 24 SKS etc.) are examples; KRS SKS tiers currently hardcoded SKS_LIMIT_TIERS; must be fetched from config.
- (D15-al-uiux-rules) Web menu items Kelas Saya, Persetujuan KRS, Pengajuan Mahasiswa exist but routes unregistered (404).
- (D16-al-reports-sec-tests-roadmap) 14#TS-SIA-19: failure code ATTENDANCE_BELOW_MINIMUM vs ACADEMIC_BLOCKED (attendance category) left undecided pending doc 10.
- (D16-al-reports-sec-tests-roadmap) 14#TS-CBT-01: attempt final status 'submitted' (current) vs 'auto_submitted' (target).
- (D16-al-reports-sec-tests-roadmap) 12#4.9: students only get provisional transcript while existing GET /student/transcript is used in 14 as the student transcript endpoint; mobile must label provisional vs official.
- (D16-al-reports-sec-tests-roadmap) 13#2: Sanctum tokens never expire today; proposed 30 days mobile - not yet decided.
- (D16-al-reports-sec-tests-roadmap) 14#2.2: BR-SEC-TEN/SCP/PRV/RTL/FIL are proposed codes not yet in doc 10 catalog.
- (D16-al-reports-sec-tests-roadmap) 12#5 gives lecturers no IPS/IPK access while dosen wali has it; confirm lecturer UI must not show IPK.
- (D17-keu-01-05) README says folder created as doc/keuangan/ with links ../../docs/..., but files actually live in docs/keuangan/ (README#Header note, KEU-Q-20).
- (D17-keu-01-05) Doc 03 gives student only finance_self_service.*, whereas existing Finance endpoints (GET /invoices, /payments) use invoices.read and have no student scoping; migration path for student access unclear (README#3.2 vs 03#4).
- (D17-keu-01-05) Cekal service keys differ between KEU (grade_view; 6 services) and academic-learning (view_grades; 12 services) (README#3.4 INK-06).
- (D17-keu-01-05) Existing PortalInvoicesPage is static and not routed; PortalScholarshipPage also static (README#3.4 INK-09); other docs (RANCANGAN-SUPER-ADMIN, MULTI-TENANT) wrongly say student bills do not exist (INK-04).
- (D17-keu-01-05) Package names in SEVIMA (Growth/Business/Professional/Premium/Enterprise, 3-6 channels) differ from repo plans trial/starter/professional/enterprise (KEU-Q-01).
- (D18-keu-06-10) 08#4 channel quota plan names (Growth/Business/Professional/Premium/Enterprise) differ from repo subscription_plans codes trial/starter/professional/enterprise.
- (D18-keu-06-10) 06#7.4 KEU installment plan on one admission invoice vs PMB design separate installment terms (INK-03, KEU-Q-11).
- (D18-keu-06-10) Student payment history is split across payments (manual/cashier) and payment_transactions (online attempts) with separate student endpoints; mobile must reconcile both views.
- (D18-keu-06-10) All /student/finance/* endpoints are new design; only staff GET /invoices and /payments are marked 'Ada' (existing); portal invoice page is currently static.
- (D19-keu-11-18) Cekal service keys differ between academic-learning/07 (grade_view, krs, exam, yudisium, graduation, transcript) and academic-learning/01 A.8 (view_grades, krs_change, exam_midterm, exam_final, thesis_defense, mbkm_registration, academic_letter) - INK-06 (17#2).
- (D19-keu-11-18) Doc 17 says cekal not in code today (KrsPlanService/ExamService no finance checks) while the web already has student KRS/exam; mobile must not assume enforcement exists yet.
- (D19-keu-11-18) Doc 11 says Scholarship module is read-only (no write endpoint) so applying/selection of scholarships is not available via API yet; student_affairs scholarship application flows are documented elsewhere.
- (D20-keu-19-20) Student manual-payments POST request body is not specified (staff POST /payments for manual_transfer uses bank_account_id, external_reference, multipart proof) - 20#5 vs 20#7.5
- (D20-keu-19-20) Cekal service keys in 19#3.16.1 (krs, exam, grade_view, transcript, yudisium, graduation) differ from academic-learning/01 (krs_change, exam_midterm, exam_final); INK-06 open
- (D20-keu-19-20) INK-08: Semester Pendek design proposes POST /invoices/{invoice}/payments and InvoicePaid; KEU replaces with POST /payments + PaymentVerified/InvoiceStatusChanged (20#9)
- (D20-keu-19-20) Sample dates in 20#7.2/7.5 are mutually inconsistent (due 2026-09-15 vs trx expiry 2026-09-14) - illustrative only
- (D21-keu-21-29) Docs path inconsistency: KEU-Q-20 says folder stays doc/keuangan/ while files are in docs/keuangan/ (29#3).
- (D21-keu-21-29) Student endpoint prefix /student/finance appears in BR-016/TC RBAC-005 while UI uses finance_self_service.* permission; portal API catalogue lives in doc 20 section 5 (not in this batch).
- (D21-keu-21-29) Doc 25 header says rules 300-319 but catalogue ends at KEU-BR-314.
- (D21-keu-21-29) Deposit: KEU-Q-31 default says students do NOT use deposit themselves, yet portal page offers Pakai/Top-up 'bila diizinkan' (flag-dependent).
- (D21-keu-21-29) README/KEU docs claim Flutter app can reuse portal API but no mobile-specific push requirements exist and push channel unimplemented.
- (D22-kk-01-09) Portal route naming /portal/kemahasiswaan/* in design vs existing student portal under frontend/src/one; KK-Q-34 (which student portal is used) is unresolved (README#6).
- (D22-kk-01-09) Two near-identical API prefixes: student self-service /student/affairs/... vs staff /student-affairs/...; the repo's existing student endpoints use /student/requests (09).
- (D22-kk-01-09) Submission allowed statuses default active+leave (05, student_affairs.submission.allowed_student_statuses) vs active-letter allowed statuses default only active (09).
- (D22-kk-01-09) Activity evidence MIME narrowed to pdf/jpg/jpeg/png vs file module default list; discipline evidence adds mp4.
- (D22-kk-01-09) INK-04: keuangan/11 triggers discounts from scholarship_applications.approved while 08 introduces scholarship_awards as trigger.
- (D22-kk-01-09) INK-03: RANCANGAN-APLIKASI places points under Organisasi & Kegiatan; SEVIMA and this design group under Poin Kemahasiswaan.
- (D22-kk-01-09) README lists existing PortalScholarshipPage/PortalLetterRequestPage as static (not calling API) while 09 says working letter submission goes through general student requests page.
- (D23-kk-10-18) Doc 11 gives dosen wali scope 'w' read of eligibility, while doc 12 gives only dosen pembimbing (thesis supervisor) read of registration status; no unified advisor permission for graduation.
- (D23-kk-10-18) Doc 13 cites rule id KEM-BR-075 (prefix inconsistent with KEL-BR/other prefixes) for verification rate-limit.
- (D23-kk-10-18) Doc 16 says existing alumni.employment_status is mandatory enum while design makes it nullable string (planned schema change; existing frontend/API are read-only).
- (D23-kk-10-18) Doc 11 portal page /portal/kelulusan replaces existing static PortalGraduationPage (existing web), implying current graduation portal is static/not data-driven.
- (D23-kk-10-18) Doc 17 describes questionnaire engine as generic (could serve other surveys) but no lecturer-evaluation (EDOM) questionnaire is defined here.
- (D24-kk-19-26) Student career route /one/career vs /portal/karir both listed (20#14)
- (D24-kk-19-26) Karirlink plan matrix inconsistency KK-Q-04 (tracer publication Growth vs sources)
- (D24-kk-19-26) Career UI exists with mock data while backend Career module absent (20)
- (D24-kk-19-26) Lecturer role absent from career/recommendation design; recommendation is by career-center staff not lecturers
- (D25-kk-27-34) Package naming: Growth/Business/Professional/Premium/Enterprise vs repo trial/starter/professional/enterprise (KK-Q-01).
- (D25-kk-27-34) KK-Q-04: 'full module from Professional' vs table showing most career features at Growth.
- (D25-kk-27-34) Traceability slips in doc 31: KEM-AC-10 (import 2,000 participants) links KEM-TC-026 which tests event claim; UMUM-AC-06 maps TRACER-TC-020 while doc 27 says email_hash test.
- (D25-kk-27-34) KEL-TC-068 cites KEM-BR-075 for graduation doc verification (cross-module BR).
- (D25-kk-27-34) Doc 27 limits channels to database+mail; a mobile app needs push, which is absent.
- (D26-sp-core) Lecturer model: desain-database#3.4 designs class_section_lecturers (multiple, coordinator/member) and offering response has lecturers[]; but desain-api#2.2 and aturan-bisnis#8.3 (R-01) say single lecturer via class_sections.lecturer_id and PUT .../teaching already exists; desain-api#5 audit table still lists PUT .../lecturers and .../schedules.
- (D26-sp-core) README header says nothing implemented (2026-10-06) while R-01/R-02 notes (2026-10-08) mark class_schedules, PUT teaching and ClassTeachingModal as existing.
- (D26-sp-core) Grade visibility: peran-dan-izin says student sees own grades after COMPLETED/final; penilaian says regular terms show submitted grades while SP only finalized.
- (D26-sp-core) desain-api uses GET /student/transcript but README/penilaian cite existing GET /students/{id}/transcript.
- (D26-sp-core) Notification doc says D/M/P default channels, but no WebSocket; 'real-time' only via database channel.
- (D27-sp-testing-plan-video) Approval model: AC-10 and video describe per-course approval workflow (Kaprodi) with item status 'rejected'; tahapan R-04/black-box use submission-level approval by dosen wali or auto, items return to 'draft' (tahapan §6 adjusts AC; video not updated; R-04 awaits tech-lead confirmation).
- (D27-sp-testing-plan-video) Lecturer model: rencana-implementasi uses class_section_lecturers pivot; tahapan R-01 keeps single class_sections.lecturer_id.
- (D27-sp-testing-plan-video) LMS: rencana checklist says 'LMS belum ada'; tahapan R-09 says LMS tables exist.
- (D27-sp-testing-plan-video) Registration window example: video 21-30 Jun 2027 vs black-box KRS period 21 Jun-2 Jul 2027.
- (D27-sp-testing-plan-video) Video clash formula mentions effective date ranges; tahapan R-02 says effective dates not built.
- (D27-sp-testing-plan-video) Foreign-class access by lecturer: 403 (AC-15, keamanan) vs 'tidak ditemukan' 404 (video slide) / alert (OWN-02).
- (D27-sp-testing-plan-video) Self-cancel: video allows enrolled-without-fee cancel; AC-13 covers only pending-unpaid vs enrolled-paid.
- (D28-pmb-01-09) Permission names: brief says lecturers have pmb_scores.create and pmb_exam_attendance.update; docs define pmb_scores.input and pmb_exams.attendance (08-rbac.md#1). Verify in code.
- (D28-pmb-01-09) SEVIMA text says auto NIM/move to SIAKAD after daftar ulang; design reinterprets to after final approval (01#INK-05).
- (D28-pmb-01-09) RANCANGAN-APLIKASI.md section 4.2 single combined PMB status list is superseded by 11 state machines (01#GAP-09).
- (D29-pmb-10-16) Doc 11 specifies generic HMAC webhook signature; doc 16 implemented Xendit x-callback-token + status re-verify and a shared /api/v1/webhooks/xendit URL (docs/pmb/16#3 vs 11#A.18.3).
- (D29-pmb-10-16) Table count: doc 10 says 66 new tables; doc 16 says 74 implemented.
- (D29-pmb-10-16) Role names differ: docs 11/12 use pmb_admin, pmb_verifier, finance_officer, selection_committee, re_registration_officer; doc 16 creates admission_administrator, admission_approver, admission_verifier, admission_selection_committee, admission_registrar; module key `admission` not `pmb`.
- (D29-pmb-10-16) Doc 11 A.1 says one pending transaction per channel (BR-16); doc 16 F4 says one pending per invoice across channels.
- (D29-pmb-10-16) Doc 11 B.1 says parent login data 'for parent login feature if any' - no parent-login feature is specified elsewhere in these docs.
- (D29-pmb-10-16) Doc 13 B.1 recommends staff MFA (TOTP) dependent on Auth module; doc 16 does not mention MFA being implemented.
- (D30-manual-keu-video-pmb) Finance manual 42 states 'aplikasi mobile belum ada' while repo contains minimal mobile/ Flutter app (verify with other audits).
- (D30-manual-keu-video-pmb) Transfer proof deadline: KEU manual 3 days vs PMB registration instruction 24 hours (different modules).
- (D30-manual-keu-video-pmb) Manual says UI strings taken directly from app but also states no UAT and no screenshots.
- (D30-manual-keu-video-pmb) PMB naskah notes applicant Notifikasi page can appear empty/known display bug, whereas finance manual relies on portal Notifikasi menu.
- (D31-manual-pmb-applicant) Design (Q-CFG-05) suggests max 2 active applications; implementation default 1 [12-formulir-pendaftaran.md#3]
- (D31-manual-pmb-applicant) Design suggests WR I as step-2 approver; app uses admission_approver [02-akses-dan-peran.md#3]
- (D31-manual-pmb-applicant) Screenshot named 15-01-portal-mahasiswa is the PMB applicant portal home after conversion, not a student portal
- (D31-manual-pmb-applicant) Applicant in-app notifications documented as working feature but known bug leaves page empty [16-pesan-notifikasi-akun.md#2]
- (D32-manual-pmb-committee) 33-keuangan-pmb.md#5 says Xendit settlement is pulled automatically; MANUAL §15.4 'kode terkini' says Xendit settlement is NOT auto-pulled.
- (D32-manual-pmb-committee) Notification log status label 'Terkirim' maps to code dispatched (63) - minor label/code mismatch with other docs using sent.
- (D32-manual-pmb-committee) Lecturer screenshot shows Akademik and Mahasiswa menus for lecturer role while these docs describe only Penilaian Ujian.

## 7. Pertanyaan terbuka dari dokumen

- (D01-flutter-design) When will Portal endpoints be available (profile, self-KRS, schedule, KHS, transcript PDF, attendance check-in, assignments, quizzes, leave, letters, EDOM, advising, graduation)? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.15)
- (D01-flutter-design) Can POST /krs-items accept the student's own student_id for self-service KRS? What are the KRS approval states and the dosen PA approval flow? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.15.3, #10.3)
- (D01-flutter-design) How should lecturers be detected (dedicated role flag?), and what should the lecturer home and menu contain? The doc defines none. (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#2.4, #7.3)
- (D01-flutter-design) Which payment gateway provider (VA / e-wallet) will be used, and what is the student payment-initiation endpoint? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.15.14)
- (D01-flutter-design) Is there a student-side POST /scholarship-applications? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.15.13)
- (D01-flutter-design) What is the FCM device-token registration endpoint (e.g. POST /devices/fcm-token), and how will iOS APNs be configured? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.3)
- (D01-flutter-design) What is the official domain for App Links / Universal Links (placeholder app.civitasone.example)? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.8)
- (D01-flutter-design) i18n launch scope: ID-only first or full EN? Chinese (ZH) is now required but absent from the design. (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.12)
- (D01-flutter-design) Will the backend add refresh tokens, and what is the token lifetime? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#11.2)
- (D01-flutter-design) What is the production certificate pin (SHA-256) and the rotation plan? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#11.4)
- (D01-flutter-design) Firebase Crashlytics/Analytics or Sentry, and is institutional privacy approval in place? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.14)
- (D01-flutter-design) Should splash and branding be per tenant? The current logo is an Atma Jaya GIF that needs a PNG export. (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.13, #2.3)
- (D01-flutter-design) What is the quiz/CBT-taking screen design (timers, randomization, violation detection)? It is explicitly outside this doc, yet mobile/lib/features/exam exists. (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.15.10)
- (D01-flutter-design) What attendance check-in mechanism should students use (QR, geolocation, time window)? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.15.8)
- (D01-flutter-design) Which Flutter/Dart version baseline applies: the doc's >= 3.35 / >= 3.9, or the README's SDK ^3.12.2? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#1.4, mobile/README.md#Tech stack)
- (D01-flutter-design) What are the numeric business rules missing from the doc: Maksimal SKS value, KRS windows/deadlines, graduation IPK minimum, and payment/cekal effects on KRS or exams? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.15.3, #6.15.19)
- (D01-flutter-design) How should the reset-password success flag pass to Login: GoRouter 'extra' or a query flag? (docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#6.1.3)
- (D02-app-design) SKS cap: is it based on IPS of the previous semester or on IPK (the blueprint just says 'IP')? Is it configurable per tenant, and what is the cap for first-semester students?
- (D02-app-design) Which grade letter set and weights will the API return (blueprint 9-tier vs implemented 7-tier)? The mobile app should render the scale dynamically.
- (D02-app-design) KRS state machine: the blueprint names no statuses (draft/submitted/approved/rejected/locked?) and gives no rule for what happens after rejection (resubmission, revision window), nor the add/drop window after locking.
- (D02-app-design) Who approves student absence permission requests (course lecturer, dosen PA or academic staff), and who may correct attendance?
- (D02-app-design) Who configures grade components and weights (the lecturer per class, or kaprodi/admin)? Must the weights total 100%? Who approves grade finalization ('Persetujuan nilai')?
- (D02-app-design) What is the minimum attendance threshold and its effect (exam eligibility)? What are the exam-card eligibility rules ('Validasi syarat mengikuti ujian')?
- (D02-app-design) What are the late-submission deduction values and grace periods for assignments?
- (D02-app-design) Do financial blocks (cekal) apply to KRS, exams, grades or transcripts? The blueprint only has finance checking obligations during the cuti workflow.
- (D02-app-design) Do lecturers see their own EDOM results, when (e.g., after grade publication), and with what restrictions ('Pembatasan akses hasil evaluasi')?
- (D02-app-design) Lecturer role in exams: proctor (pengawas), berita acara ujian and ujian susulan flow. Actors are unspecified.
- (D02-app-design) Is in-app messaging/chat in scope? A 'messages' table is listed in §7 but no messaging feature is described.
- (D02-app-design) How do students and lecturers select or resolve their tenant (university) at login? The doc only describes the Super Admin Tenant Switcher.
- (D02-app-design) Is the optional parent/guardian role excluded from the mobile app?
- (D02-app-design) Do lecturers get employee self-service features (payslip, reimbursement, meeting invitations), which are listed only for Pegawai (#3.9, #4.37)?
- (D02-app-design) Can students or lecturers borrow rooms/equipment or report damage via the app (#4.27 actors unspecified)?
- (D02-app-design) Should students view the curriculum structure, prerequisites and RPS? The modules exist but no student-facing view is listed.
- (D02-app-design) MFA method is unspecified. Offline mode, biometric unlock, deep links, screenshot protection and the supported languages list are not addressed in the blueprint.
- (D02-app-design) Short semester (semester pendek) is not mentioned anywhere in the blueprint. Its rules come only from docs/semester-pendek.
- (D02-app-design) Who can see advising notes (catatan bimbingan): only advisors, or students too?
- (D02-app-design) Notification preference granularity (per event type and/or per channel) and the event-to-role mapping are not specified.
- (D03-role-accounts-status) What are the current API paths and permission slugs for student self-service: KRS offering/submit/cancel/history/PDF, KHS, grades, transcript, attendance, schedule, calendar, academic summary, materials, assignments, requests, finance? Only the exam endpoints are documented here.
- (D03-role-accounts-status) Which KRS statuses exist now for student-submitted KRS, and what is the approval state machine? The Sep model has enrolled\|dropped only; STATUS says revision_requested/locked are ❌.
- (D03-role-accounts-status) Who approves KRS: the academic_advisor role or a lecturer assigned as dosen wali? What are the API path/payload behind krsApprovalService, and the notification channel and content?
- (D03-role-accounts-status) Can students see grades as soon as lecturers enter them, given grade publication is ❌? Will EDOM completion ('syarat lihat nilai') gate grade viewing?
- (D03-role-accounts-status) Which student profile fields can be edited ('profil (ubah sebagian)')?
- (D03-role-accounts-status) Is a forced password change on first login implemented, given seeded passwords = NIM?
- (D03-role-accounts-status) Which lecturer identity path should mobile use for 'my classes' (lecturers.user_id vs employees.user_id → lecturers.employee_id)? Which tenants enable academic.lecturer_ownership, and how should the app behave when it is off?
- (D03-role-accounts-status) How should mobile reproduce web violation detection (tab switch, blur, fullscreen exit, copy/paste, right-click), e.g. app backgrounding or screenshots? Does the 1-point penalty apply on mobile?
- (D03-role-accounts-status) What is the endpoint for the exam result PDF, and how do exam weights ('bobot') and grade ranges ('rentang nilai') combine with the violation penalty in scoring?
- (D03-role-accounts-status) How does a mobile user pick their university before login? Custom-domain resolution does not apply to apps, and X-University-ID is 'claimed, not validated'. Can multi-membership users switch tenant in-app?
- (D03-role-accounts-status) Will push be enabled in the core Notification module (currently isSupported = false), and with which provider? None is named in these docs.
- (D03-role-accounts-status) Should lecturers get lecturers.read (lecturer directory)? DOSEN §8.3/§11.6 leaves this open.
- (D03-role-accounts-status) Can lecturers read announcements and the academic calendar? STATUS only mentions the student portal.
- (D03-role-accounts-status) Has the GET-vs-POST login route change been resolved?
- (D03-role-accounts-status) Lecturer teaching schedule: is there a lecturer-facing endpoint/view for 'my teaching schedule' now that schedule columns and lecturer assignment exist?
- (D04-keu-master) Q-KEU-01 (P1): fee scheme, UKT vs SPP/BPP + SKS + DPP. Default PTS: BPP + SKS + practicum + DPP, UKT off. This decides whether the UKT screens ship.
- (D04-keu-master) Q-KEU-04 (P1): which payment provider, dynamic or static VA, H2H. Default: one aggregator (VA + QRIS), dynamic VA. Code mentions Xendit.
- (D04-keu-master) Q-KEU-05 (P1): cekal policy per service, reference dates, exempted prodi/groups. Default: the F-14 table.
- (D04-keu-master) Q-KEU-06 (P1): installment scheme details. Default: 3 terms 40/30/30, auto-approved, no admin fee.
- (D04-keu-master) Q-KEU-07 (P1): whether penalties exist at all. Default: none active, so the penalty transparency screen may be empty.
- (D04-keu-master) Q-KEU-08 (P1): refund policy per reason. Default: double/overpayment 100%, others per SK, DPP not refundable.
- (D04-keu-master) Q-KEU-11 (P1): SKS invoice trigger (approved vs locked) and drop deadline. Default: KRS approved by dosen wali; deadline = end of add/drop.
- (D04-keu-master) Q-KEU-17 (P2): guardian rights (summary vs detail, can pay, reminders). Affects the student consent UI.
- (D04-keu-master) Q-KEU-18 (P2): does a pending transfer proof satisfy cekal, and for how many hours? Default: no (0 h).
- (D04-keu-master) Q-KEU-19 (P2): deposit auto-use at issue, top-up, transfers between students. Default: manual, top-up yes, no transfers.
- (D04-keu-master) Q-KEU-27 (P3): do receipts need TTE/e-meterai? Default: no, QR verification.
- (D04-keu-master) Mobile: how should the app display VA/QR/redirect instructions and handle the gateway return? The doc only says client-side results trigger a server re-check. Also, polling vs push for 'status real-time' (FR-PRT-02)?
- (D04-keu-master) Mobile: push is listed in FR-PRT-07, but only due_reminder defaults to push. Should payment success, proof rejection, cekal and dispensation decisions also push?
- (D04-keu-master) Lecturer: can a dosen PA submit or track advisee dispensations in the mobile app? There is no permission or endpoint for DW (§4.4 vs §4.6).
- (D04-keu-master) Lecturer: BR-KEU-69 mentions ordinary lecturers ('dosen') receiving cekal messages without amounts. Should exam or attendance rosters show 'blocked' flags in the lecturer app?
- (D04-keu-master) Who can cancel a voucher redemption (student or staff)? Not stated (§6/F-06 step 4).
- (D04-keu-master) How does a student claim unidentified funds (FR-REK-04)? There is no student endpoint or screen.
- (D04-keu-master) What is the structure of the KRS blockers[] payload? Referenced in §5.4 but defined in the SIAKAD design A.8, not here.
- (D05-sdm-superadmin-sevima) Identity link for lecturer self-service: the design uses personnel.user_id (RANCANGAN-AKUN-SDM §5.2, §11 item 7); the repo seeder comment says self-service is scoped by employees.user_id, with LecturerObserver syncing lecturers to employees. Which mapping should the mobile app rely on?
- (D05-sdm-superadmin-sevima) Who is the 'atasan langsung' (first approver) for a lecturer's leave and HR requests: Kaprodi or Dekan? The direct_supervisor and unit_head approver types are only designed, and the position approver throws LogicException.
- (D05-sdm-superadmin-sevima) Is the mobile approval inbox (features/hr_approval/) in scope for lecturers who are approvers? Kaprodi and Dekan are separate role slugs (head_of_study_program, dean), outside the two target roles.
- (D05-sdm-superadmin-sevima) Several limits are left as N: doctor's-note threshold for sick leave, how far back sick leave can be dated, the maximum number of resubmissions (R15), and the large-export threshold. Carry-over is 'maks. N' in §5.10 but defaults to 6 in R4. What are the default values?
- (D05-sdm-superadmin-sevima) Will employee self check-in (GPS/geofence/photo) be built for mobile? It is conditional on the campus having no attendance machine, and STATUS-IMPLEMENTASI marks employee attendance ❌.
- (D05-sdm-superadmin-sevima) Can a lecturer see their own unmasked NIK, KK, bank account and NPWP in Profil Saya? The design hides them without personnel_sensitive.read, which self-service roles don't have.
- (D05-sdm-superadmin-sevima) Which request type covers employee-submitted external work experience (§5.7) and external training records (§5.13)? No dedicated request_type exists; maybe 'other' or 'data_change'.
- (D05-sdm-superadmin-sevima) There is no self-service endpoint for performance self-assessment (penilaian diri); §8.2 defines only admin performance-reviews endpoints. How will lecturers submit self-assessments?
- (D05-sdm-superadmin-sevima) Can lecturers self-register for internal trainings that have a quota and registration (§5.13)? No me/ registration endpoint exists.
- (D05-sdm-superadmin-sevima) Will the certificate_letter PDF (surat keterangan kerja/aktif mengajar) be downloadable in the app, and is TTE e-signature (SEVIMA §25) planned for it?
- (D05-sdm-superadmin-sevima) Does CivitasOne adopt these SEVIMA rules: EDOM completion before viewing grades (§11); exam block at 75% minimum attendance (§7.4); cekal dispensations?
- (D05-sdm-superadmin-sevima) Which CivitasOne document specifies lecturer research/PkM records? SEVIMA §18/§19 describe them, and STATUS-IMPLEMENTASI lists 'catatan per dosen' as ⚙️, but RANCANGAN-AKUN-SDM has none.
- (D05-sdm-superadmin-sevima) Will the self-service payslip (Fase 5, owner only) be in mobile scope, and what extra security does it need?
- (D05-sdm-superadmin-sevima) Push delivery: which provider (FCM/APNs), and what is the device-token registration endpoint? These docs only list 'push' as a channel.
- (D05-sdm-superadmin-sevima) How should the app handle Super Admin forced logout (RANCANGAN-SUPER-ADMIN §6.2, planned) and maintenance mode (§7, planned)? The error contracts are not specified.
- (D05-sdm-superadmin-sevima) 2FA is not implemented in the backend, and biometric unlock is not mentioned in any of the three docs. Is either expected for the mobile app?
- (D05-sdm-superadmin-sevima) Notification templates have no global fallback (RANCANGAN-SUPER-ADMIN §7). Are hr.* templates seeded for every tenant? Otherwise lecturers in some tenants may receive no HR notifications.
- (D06-exam-docs-videos) Should the mobile app let a logged-in student take the exam with their authenticated identity, or replicate the public link/QR plus NIM flow (open it in a browser or webview)? Which API does the public flow use? It is not documented in docs/EXAM_MODULE.md.
- (D06-exam-docs-videos) Security: with NIM-only access (docs/video-tutorial-ujian/source/script.json#prep), anyone holding the link and another student's NIM could start that student's attempt. Is there any additional check (device, session, proctor)?
- (D06-exam-docs-videos) How do the six browser-centric violation types (tab switch, focus loss, fullscreen exit, copy, paste, right-click) map to a native Flutter app: app lifecycle paused/inactive, notification shade, split screen, screenshots? Is there a violation-logging API?
- (D06-exam-docs-videos) Where does the lecturer configure the backward-navigation lock, immediate result display, result publication, and per-question explanation (Pembahasan)? None of these appear in the lecturer forms shown.
- (D06-exam-docs-videos) Is 'Persentase' on the student result page based on the raw or the final score? The example shows 78% = Final 78 with Raw 80 (docs/video-tutorial-ujian/source/tutorial.html#resultPage).
- (D06-exam-docs-videos) Does the ~24-hour result-page limit apply in a logged-in mobile app? Does it start at the exam deadline ('batas waktu ujian', on-screen) or when the exam ends ('setelah ujian berakhir', narration)? Will students see exam results anywhere afterwards?
- (D06-exam-docs-videos) How do exam Weighted Scores feed into course grades (PUT /krs-items/{id}/grade)? The recap says the CSV is for 'impor ke sistem nilai', which suggests a manual import.
- (D06-exam-docs-videos) With max_attempts > 1, does each new attempt number get a fresh random assignment (the snapshot is per exam, participant and attempt number), and which attempt's score counts?
- (D06-exam-docs-videos) Given the web-only statement in the lecturer tutorial, is the Flutter lecturer exam module (wizard, QuestionBankScreen, ExamPreviewScreen) still the intended mobile lecturer flow? Is parity needed for Akses Ujian (link/QR), live monitoring, the violation timeline and Rekap Nilai export?
- (D06-exam-docs-videos) Live monitoring polls every 5 s on the web. Should mobile poll too, or will push/websocket be provided? The push channel is not implemented per NOTIF-05.
- (D06-exam-docs-videos) NIL-09/ABS-08 say lecturers can grade and record attendance for any class in the tenant. Should the mobile app restrict lecturers to the classes they teach ('Kelas Saya'), and will the backend enforce it?
- (D06-exam-docs-videos) What exactly triggers 'Ujian belum tersedia.': exam not published, before Waktu Mulai, or link not generated yet?
- (D06-exam-docs-videos) Are 'dosen'/'lecturer' and 'mahasiswa'/'student' the same roles? What permissions will 'dosenpa' receive for KRS approval ('Persetujuan KRS')?
- (D06-exam-docs-videos) BLACK-BOX (2026-08-09) says mahasiswa has no permissions and the student portal is static. Which read endpoints (grades/KHS, attendance, transcript, invoices, announcements, schedules) will be opened to students for the mobile app?
- (D06-exam-docs-videos) What does the 'Detail Mahasiswa' action in the exam participant table show (a per-student answer sheet?)? It is not described.
- (D06-exam-docs-videos) Is there a student exam schedule or list of upcoming exams in-app, or are exams discovered only through lecturer-shared links/QR codes?
- (D07-sevima-pmb-design) BR-38 / KD-01: what login identity will a converted student use in the mobile app (institutional email, alternative email, NIM)? users.email is globally unique across tenants and is not auto-linked to the applicant's email.
- (D07-sevima-pmb-design) Q-REG-03 / A-08: is a newly converted student's initial status 'active' or something like 'pending_activation'? This decides what the student app shows before the entry semester starts.
- (D07-sevima-pmb-design) Q-REG-01: NIM format per tenant (default {YY}{PRODI}{SEQ:4}). The mobile app should not assume a format.
- (D07-sevima-pmb-design) Q-PAY-01: which payment gateway (Midtrans / Xendit / bank H2H) for VA and QRIS? The PMB and student finance flows may share it.
- (D07-sevima-pmb-design) Should PMB examiner score entry (a lecturer duty) be offered on mobile, or stay web-admin only? The doc defines only web admin pages.
- (D07-sevima-pmb-design) Who can view SEVIMA's 'Rekap hasil kuesioner per dosen & per kelas' (EDOM)? Can lecturers see their own results?
- (D07-sevima-pmb-design) The SEVIMA features for perwalian/consultation, thesis supervision, cuti studi, MBKM logbook, and cekal/dispensation have names only, with no workflows, statuses or limits. Their concrete rules must come from other CivitasOne design docs.
- (D07-sevima-pmb-design) Minimum attendance threshold for the exam block (cekal minimal kehadiran) is not specified.
- (D07-sevima-pmb-design) Should the mobile app include parent access ('Login sebagai orang tua' is a SEVIMA all-tier feature)? It is outside the two primary roles.
- (D07-sevima-pmb-design) After conversion, should the student app show onboarding content (bukti registrasi, orientation info, first invoices)? The PMB doc lists these outputs but names no channel beyond notification/link.
- (D08-proposal-pdf) Push provider (FCM/APNs) and device token endpoint unknown
- (D08-proposal-pdf) Offline scope undefined
- (D08-proposal-pdf) Which pages restrict screenshots
- (D08-proposal-pdf) Mobile equivalents of exam violations undefined
- (D08-proposal-pdf) Whether students can view scholarship/thesis/library data (5.11)
- (D08-proposal-pdf) No API paths given beyond /api/v1
- (D08-proposal-pdf) EDOM-before-grades policy undecided
- (D09-samples-notes) Exact /api/v1/student/finance/* endpoints and payloads
- (D09-samples-notes) Can students self-submit refund/special-instalment/UKT change requests?
- (D09-samples-notes) Notification channels (push?) for 15 keu.* templates
- (D09-samples-notes) Proof form fields besides transfer date
- (D09-samples-notes) Return flow from Xendit checkout (deep link?)
- (D09-samples-notes) Per-tenant branding vs Atma Jaya crest
- (D09-samples-notes) Are sempel files only PMB fixtures?
- (D10-al-siakad) Exact attendance status enum and counted statuses (OD-06)
- (D10-al-siakad) SKS tiers (OD-05)
- (D10-al-siakad) add/drop mode (OD-09)
- (D10-al-siakad) EDOM gating of grade view (OD-10)
- (D10-al-siakad) advisor access to component scores (OD-28)
- (D10-al-siakad) whether Kaprodi functions are in mobile scope
- (D10-al-siakad) student-facing dispensation request flow/endpoint unspecified
- (D10-al-siakad) student add-class endpoint name not given (only admin POST /krs-items)
- (D11-al-lms-cbt) OD-NEW-lms-pending-krs-access: can students with `pending` KRS see class materials during add/drop? (default no) (02#2.4)
- (D11-al-lms-cbt) OD-NEW-lms-quiz-entitlement: are simple quizzes in Growth `lms`? Affects whether quiz UI shows. (02#3.5)
- (D11-al-lms-cbt) OD-06: does late QR check-in count as present; min attendance for exam eligibility (75% example only). (02#4.3.3, 03#4.2)
- (D11-al-lms-cbt) Which QR/LMS endpoint naming is canonical (02 vs 08)? (see §3)
- (D11-al-lms-cbt) Will mobile app host exam proctoring with camera (Flutter background detection event types unspecified)? Which violation types does Flutter emit (app backgrounded mapped to `window_blur`?). (03#8.2)
- (D11-al-lms-cbt) Is screenshot/screen-recording protection required for the exam screen? Not stated in either doc.
- (D11-al-lms-cbt) Can lecturers holding `exam_proctor`/`exam_committee` roles use mobile for monitoring/extensions? Not stated.
- (D11-al-lms-cbt) Dosen wali (advisor) summary of student LMS data is permitted (BR-LMS-DSH-01) but no endpoint/screen defined.
- (D11-al-lms-cbt) Device binding change limit (1 per 7 days) — what UI flow for device re-registration?
- (D11-al-lms-cbt) PDF annotation: web viewer only (02#6.8) — student mobile view of annotated PDF via `feedback_file_id` after `returned_at`.
- (D11-al-lms-cbt) Interactive video and AI face attendance — provider and mobile support (OD-18).
- (D11-al-lms-cbt) iOS entitlement: `X-Client-Platform` header values not specified.
- (D11-al-lms-cbt) Offline lecturer token generation requires shipping `K_session` to lecturer device — security review needed (02#4.3.5).
- (D11-al-lms-cbt) Gamification leaderboard opt-out: where the setting lives (not specified).
- (D12-al-obe-edom-arch) Is student CPL attainment view in mobile v1 (default flag false)?
- (D12-al-obe-edom-arch) No student endpoint for RPS
- (D12-al-obe-edom-arch) Dosen wali access to attainment (OD-28)
- (D12-al-obe-edom-arch) Which lecturer OBE functions on mobile vs web only
- (D12-al-obe-edom-arch) EDOM response schemas, anonymity_mode/n_min and period closes_at not in API responses
- (D12-al-obe-edom-arch) Unified Status Layanan endpoint undefined
- (D12-al-obe-edom-arch) Push templates/keys beyond EDOM events undefined
- (D12-al-obe-edom-arch) Late-close limit for lecturer session
- (D13-al-erd) OD-05 SKS limit tiers values, OD-06 minimum attendance % (75 is example), OD-09 add/drop max items (2 example), OD-07 Kaprodi grade approval, OD-08 repeat grade policy, OD-10 EDOM anonymity default, OD-14 internships fate, OD-26 question types, OD-27 general class quota, OD-29 rounding (all in 16-open-decisions.md, not read here)
- (D13-al-erd) No endpoint payload definitions or acceptance-criteria IDs in this doc; see API/test docs
- (D13-al-erd) Whether EDOM completion gates grade view (edom_periods.gates_grade_view default false) is per-period config; mobile must handle gated grades
- (D13-al-erd) Which mobile-visible OBE data (CPMK/CPL achievement) students should see is unspecified in the schema
- (D14-al-api-roles) OD-06 attendance threshold for exam; OD-07 Kaprodi grade approval mandatory?; OD-08 revision deadline; OD-09 KRS change limits; OD-10 EDOM required to view grades / free-text comments; OD-12 proctoring camera consent; OD-21 guardian consent; OD-28 can advisors see component grades; OD-29 rounding (details in 16-open-decisions.md, not assigned).
- (D14-al-api-roles) Exact value of attendance_minimum, edit window for attendance, QR TTL (30 s only example), advising note edit limit (7 days example) are all examples, not decided.
- (D14-al-api-roles) Student QR scan uses student_portal.read (no dedicated permission).
- (D15-al-uiux-rules) OD-05 SKS basis (ips_previous vs ipk); OD-06 excused attendance counts; OD-07 kaprodi grade approval; OD-08 transcript best/latest and revision window; OD-09 post-lock KRS changes; OD-10 EDOM anonymity/gate; OD-21 guardian consent; OD-28 advisor sees component scores; OD-29 rounding; OD-20/27 storage policy.
- (D15-al-uiux-rules) Exact API for QR attendance scan and student-facing endpoints for dashboard widgets/features payload are not specified here (see 08-api-specification.md).
- (D15-al-uiux-rules) Push notification (FCM) mechanism, biometrics, deep links not covered.
- (D16-al-reports-sec-tests-roadmap) OD-06 whether izin/sakit count as present; OD-28 advisor access to component scores; OD-10 EDOM anonymity default and EDOM-required-for-grades; OD-21 guardian verification; OD-NEW-student-cpl-visibility; OD-NEW-report-thresholds; OD-29 rounding; OD-08 repeat-course IPK; OD-05 SKS tiers; OD-12 proctoring evidence; OD-11 AI data policy.
- (D16-al-reports-sec-tests-roadmap) Which student reports (catalog) are exposed through mobile endpoints /academic-reports vs dedicated /student/* endpoints is not specified.
- (D17-keu-01-05) Do lecturers/advisors see arrears amounts or only hold status (KEU-Q-19)?
- (D17-keu-01-05) Which payment gateway provider and which channels are offered to students (KEU-Q-03, KEU-Q-01)?
- (D17-keu-01-05) Which services are blocked by cekal and thresholds (KEU-Q-07); is KEU or SIAKAD enforcement (direct FinanceClearanceGuard vs projection) used first?
- (D17-keu-01-05) Whether partial payment, deposit self-use/top-up, installment limits are enabled per tenant (all [K])
- (D17-keu-01-05) Late fee policy (KEU-Q-06) and SKS basis for per-SKS billing (KEU-Q-12, default enrolled)
- (D17-keu-01-05) UKT vs SPP model per tenant (KEU-Q-13)
- (D17-keu-01-05) What exactly will mobile consume: docs 20 (API) and 21 (UI) were not in this part and must be read for student endpoints and payloads
- (D18-keu-06-10) Which gateway provider(s) and channels will be offered (KEU-Q-03), and channel quota entitlement (KEU-Q-01)?
- (D18-keu-06-10) Are partial payments allowed (KEU-Q-14), min amount Rp10,000?
- (D18-keu-06-10) Is installment policy approved and enabled (KEU-Q-10)?
- (D18-keu-06-10) SKS billing basis single vs two-stage (KEU-Q-12)?
- (D18-keu-06-10) Does dispute suspend late fee/hold (KEU-Q-24)? Who bears gateway fee (KEU-Q-25)?
- (D18-keu-06-10) No push notification channel specified; is FCM planned? Is there a student-specific invoice history endpoint?
- (D18-keu-06-10) How will e-wallet redirect/deep-link return be handled in the mobile app?
- (D19-keu-11-18) KEU-Q-19: exact visibility of cekal for dosen wali/dekan/kaprodi.
- (D19-keu-11-18) KEU-Q-31: may students use deposit themselves (finance.deposit.self_use_enabled)?
- (D19-keu-11-18) KEU-Q-21: is online deposit top-up enabled?
- (D19-keu-11-18) KEU-Q-06 / KEU-Q-07: late-fee and cekal policies not yet decided (features default off).
- (D19-keu-11-18) KEU-Q-13: whether campus uses UKT at all (vs fixed SPP).
- (D19-keu-11-18) KEU-Q-04/05: calculation order and percentage base for scholarship/discount.
- (D19-keu-11-18) Which cekal service list applies (finance.hold.available_services) for mobile UI messaging.
- (D19-keu-11-18) Mobile push notifications not specified (only database + mail).
- (D20-keu-19-20) Request/response schemas absent for most student endpoints: summary, installment-requests, disputes, ukt-appeals, manual-payments, deposit, scholarships, ukt, clearance, payment-channels, payments history
- (D20-keu-19-20) Mobile push notification channel not defined (only database, mail)
- (D20-keu-19-20) Which payment gateway providers will actually be implemented (midtrans, xendit, bni_direct are examples)
- (D20-keu-19-20) finance.deposit.self_use_enabled and posting_date_basis [K] decisions pending; KEU-Q-02, KEU-Q-15 open
- (D20-keu-19-20) Entire finance redesign is unimplemented; existing API is only staff GET /invoices, /invoices/{id}, /payments; no student finance endpoints exist yet
- (D21-keu-21-29) KEU-Q-09 overpayment default (hold vs auto-deposit)
- (D21-keu-21-29) KEU-Q-10 installment terms/DP/fees
- (D21-keu-21-29) KEU-Q-19 may dosen wali see nominal (default no)
- (D21-keu-21-29) KEU-Q-21/31 student top-up and self-use of deposit
- (D21-keu-21-29) KEU-Q-24 do disputes suspend late fee/cekal (default no)
- (D21-keu-21-29) KEU-Q-33 behaviour on cekal sync failure (default keep last status)
- (D21-keu-21-29) KEU-Q-36 per-SKS billing one stage or two (default two)
- (D21-keu-21-29) KEU-Q-07 which services are cekal'd, threshold, grace (no active policy by default)
- (D21-keu-21-29) KEU-Q-06 late fee policy (disabled until set)
- (D21-keu-21-29) Mandatory-notification flag in NotificationDispatcher not yet available (KEU-A-17)
- (D22-kk-01-09) KK-Q-05: are student-affairs points mandatory for yudisium and at what threshold per prodi/angkatan (progress UI depends on it)?
- (D22-kk-01-09) KK-Q-31: who verifies per category (prodi/faculty/university) and SLA days?
- (D22-kk-01-09) KK-Q-08: discipline committee procedure, authority per severity, authority over alumni; KK-Q-32 whether sanctions block yudisium; KK-Q-07 whether violation points reduce activity points.
- (D22-kk-01-09) KK-Q-23: scholarship approving official, stacking policy, cash disbursement, donor reporting.
- (D22-kk-01-09) KK-Q-09/KK-Q-12/KK-Q-24: letter signature mode, number format, allowed statuses/validity of letters.
- (D22-kk-01-09) Is advisor (dosen wali) visibility of sanctions enabled by default (default false)? No dedicated advisor/lecturer endpoints defined here: which endpoints a mobile advisor would call is unspecified.
- (D22-kk-01-09) Lecturer as violation reporter: exact endpoint/permission for non-student reporters not clearly specified.
- (D22-kk-01-09) Push notifications, offline, deep links not addressed; docs only plan database+mail notifications.
- (D23-kk-10-18) KK-Q-10: official verification order and approvers for yudisium; registration mode (student self vs auto_enroll)
- (D23-kk-10-18) KK-Q-11: honor predicate rules per degree level
- (D23-kk-10-18) KK-Q-13: PDDikti reporting and national diploma PIN
- (D23-kk-10-18) KK-Q-14/15: wisuda registration basis, fees, graduate book columns
- (D23-kk-10-18) KK-Q-16: official SKPI structure and machine translation use
- (D23-kk-10-18) KK-Q-17/19/25: tracer mandatory cohort/instrument, income collection, alumni account & email
- (D23-kk-10-18) KK-Q-02/20: quota window, bounce refund policy, email open tracking
- (D23-kk-10-18) KK-Q-28/33: library fines clearance; no final-defense (sidang) entity
- (D24-kk-19-26) Marketplace internal vs external karirlink.id (KK-Q-03)
- (D24-kk-19-26) Retention of CV/applications (KK-Q-22)
- (D24-kk-19-26) Final-year students recommended for internship? (KK-Q-26)
- (D24-kk-19-26) Transition between classic portal and Civitas One (KK-Q-34)
- (D24-kk-19-26) Is there any lecturer (dosen PA) UI for points/eligibility of advisees?
- (D25-kk-27-34) KK-Q-25 alumni account (same account vs new).
- (D25-kk-27-34) KK-Q-34 which student portal (Civitas One vs pages/portal) for new features.
- (D25-kk-27-34) KK-Q-24 statuses allowed for surat aktif and validity period.
- (D25-kk-27-34) KK-Q-05 whether points mandatory for yudisium.
- (D25-kk-27-34) KK-Q-14 ceremony registration basis and fee.
- (D25-kk-27-34) KK-Q-31 verification SLA (default 7 days).
- (D25-kk-27-34) No push/device-token design anywhere in docs 27-34.
- (D26-sp-core) How lecturers will be linked to users (lecturers.user_id) and how existing tenants backfill class_section_lecturers; until then lecturer mobile features blocked.
- (D26-sp-core) Dosen PA relation not designed; advisor approval unavailable.
- (D26-sp-core) Push token registration endpoint for mobile not specified in these docs.
- (D26-sp-core) No offline/biometric/i18n/dark mode requirements stated for mobile.
- (D26-sp-core) Final location of student attendance/schedule endpoints for non-SP terms (shared student/* endpoints planned).
- (D27-sp-testing-plan-video) Is SP in scope for first mobile release? Docs say mobile optional after G6 and nothing is implemented yet.
- (D27-sp-testing-plan-video) Dosen PA workflow: approval page missing (T-1), advisor assignment has no UI (T-4); video says Dosen PA 'menyusul'. Mobile advisor approval depends on existing endpoints krs-submissions (exact paths not given here).
- (D27-sp-testing-plan-video) Exact REST paths for student KRS (student/krs/*, student/krs/items, student/krs/submit, student/krs/terms, student/invoices) need confirmation against code.
- (D27-sp-testing-plan-video) Default repeat_grade_policy and SP approval_mode per tenant remain configuration decisions.
- (D28-pmb-01-09) How does a converted student activate the account (institutional email vs personal; password setup)? (Q-REG-06, 07#K.2 step 17)
- (D28-pmb-01-09) Initial student status: active vs pending_activation (Q-REG-03)
- (D28-pmb-01-09) Where to store NIK, mother name, religion etc.: students columns vs student_profiles (01#GAP-04)
- (D28-pmb-01-09) Is there a mobile examiner experience (QR attendance, score input) for lecturers? Docs imply web only.
- (D28-pmb-01-09) Whether NIM format/PMB conversion is implemented in code (docs are design only; check app/Modules).
- (D29-pmb-10-16) Should the lecturer mobile app expose PMB examiner functions (my sessions, score entry, QR check-in)? ADM-53 says mobile/tablet fully supported but endpoints are under /pmb/admin with staff permissions.
- (D29-pmb-10-16) No notification is defined when a lecturer is assigned as examiner/proctor (pmb.exam.* events target applicants only).
- (D29-pmb-10-16) How does a newly converted student first log in on mobile (activation link flow, deep link, must_change_password handling)? Activation link mechanics not specified.
- (D29-pmb-10-16) NIM format (Q-REG-01) and PDDikti field list (Q-REG-05) remain open stakeholder decisions (DEP-09).
- (D29-pmb-10-16) Push notifications (FCM) are not mentioned for any channel; only email/SMS/WA/in-app.
- (D29-pmb-10-16) Parent login mentioned conditionally in doc 11 B.1; unspecified.
- (D30-manual-keu-video-pmb) No API endpoints documented for student finance; need backend routes under app/Modules/Finance (student_finance permission) or docs/keu/catatan-implementasi.md.
- (D30-manual-keu-video-pmb) Exact text shown to dosen PA when KRS approval blocked by cekal not given.
- (D30-manual-keu-video-pmb) Will cekal extend beyond KRS and Ujian (nilai/transkrip/yudisium)?
- (D30-manual-keu-video-pmb) Is online VA/QRIS (Xendit) enabled for target campuses; untested.
- (D30-manual-keu-video-pmb) Is PMB applicant portal in scope for mobile or only student/lecturer roles?
- (D31-manual-pmb-applicant) How are SIAKAD/student credentials delivered and is a first-login password change forced? (only 'dikirim ke email')
- (D31-manual-pmb-applicant) Is the student mobile app mentioned to new students after conversion? Not stated
- (D31-manual-pmb-applicant) No API endpoints documented in these files
- (D31-manual-pmb-applicant) Chapters 30-41, 50, 60-63 and organizer manual referenced but not part of this audit unit
- (D32-manual-pmb-committee) No API endpoints for lecturer exam scoring/check-in are documented in these files.
- (D32-manual-pmb-committee) Whether examiner lecturers get a notification when assigned to a session is not described.
- (D32-manual-pmb-committee) Whether lecturer exam proctor/scoring should be in the mobile app (and camera QR check-in) is undecided; design ADM-52/53 intended tablet camera and autosave.
- (D32-manual-pmb-committee) Guardian consent for applicants under 18 not implemented (K13).
- (D32-manual-pmb-committee) Which APIs expose student record created by conversion (NIM, institutional email domain pmb.institution_email_domain default empty).

## 8. Risiko yang ditemukan di kode

- (C1-be-conventions) Login failures return 422 (not 401); lockout returns 429 with message 'Validasi data gagal.' and the real text under errors.email\|nim[0].
- (C1-be-conventions) X-University-ID is trusted on routes lacking tenant.access; tenant isolation of permissions relies on user_roles.university_id.
- (C1-be-conventions) Empty meta serialises as [] (JSON array) not {} - parse defensively in Dart.
- (C1-be-conventions) Student default password is birth date DDMMYYYY (weak) with must_change_password=true; mobile must force change.
- (C1-be-conventions) Email login global lookup: same email across tenants maps to one user; tenant picked from membership default.
- (C1-be-conventions) Suspended/inactive university not checked at login.
- (C1-be-conventions) Permission cache 1h per user+tenant (version bump on grant change).
- (C2-be-academic-student) Public exam access trusts NIM only; anyone with link+NIM can take another student's exam. Prefer authenticated /student/exams flow.
- (C2-be-academic-student) Exam attempt payload lacks deadline; server finalizes lazily only on access.
- (C2-be-academic-student) PHP empty arrays encode as [] so meta/errors may be [] instead of {}; Flutter models must tolerate list-or-map.
- (C2-be-academic-student) Decimal fields (score etc.) are strings, others numbers; parse defensively.
- (C2-be-academic-student) KRS window uses university timezone date comparison; device timezone must not be used.
- (C2-be-academic-student) Student routes lack tenant.access; tenant from header/domain/default membership.
- (C2-be-academic-student) File upload extension validated from client filename; Dart multipart must keep extension.
- (C2-be-academic-student) abort_unless 403 in StudentExamController returns generic 403 (not 404) for foreign attempts.
- (C2-be-academic-student) KRS add/remove use row locks; rapid double-tap yields 409 not duplicates.
- (C3-be-academic-lecturer) Ownership flag OFF: lecturers can read/write grades, attendance, exams of any class
- (C3-be-academic-lecturer) GET /students, /students/{id}, /students/{id}/transcript open to every lecturer for all students
- (C3-be-academic-lecturer) Question bank shared across all lecturers
- (C3-be-academic-lecturer) Exam access_token exposed to all exams.read holders
- (C3-be-academic-lecturer) academic_advisor role alone cannot see classes/students/krs-items
- (C3-be-academic-lecturer) Grade letter override can contradict score
- (C4-be-finance) Proof upload is two-step (file-uploads then JSON); file-uploads accepts 10MB but proof rejects >2MB with 422 on file_upload_id after the upload already consumed.
- (C4-be-finance) Student without students.user_id link gets 403 on every finance endpoint.
- (C4-be-finance) Unique code adds code*100 cents (Rp code) to amount - app must display amount from API, not compute.
- (C4-be-finance) Xendit external_id/webhook shared with PMB; channel list empty if credentials absent in tenant environment.
- (C4-be-finance) Idempotency-Key must be generated per attempt and reused on retry; reuse with different target gives 409.
- (C4-be-finance) status query param on /invoices is unvalidated comma list.
- (C5-be-other-modules) Notification preference matcher compares notification_type to notification class (TemplatedNotification), so event-key preferences silently do nothing.
- (C5-be-other-modules) GET /books/{id} embeds loans of all students (names/NIM) for any books.read holder; GET /scholarships/{id} embeds all applications - privacy if permissions are ever broadened to students/lecturers.
- (C5-be-other-modules) Dashboard cached 300s per tenant+filter; filters list (faculties, programs, terms) returned to any authenticated user.
- (C5-be-other-modules) PATCH used for single notification read (not POST); mobile HTTP client must support PATCH.
- (C5-be-other-modules) File downloads gated by Bearer; preview CSP is for browsers - mobile WebView should not rely on it.
- (C5-be-other-modules) Virus scanner is NullVirusScanner (always clean); declared mime is client-supplied (getClientMimeType) though extension validated via mimes.
- (C5-be-other-modules) Announcement body not sanitized (plain text assumed); published_at in future hides announcement (scheduled).
- (C6-be-hr) Two identity paths: /me/hr 404 if employees.user_id null though /lecturer-profile works
- (C6-be-hr) Two academic rank enums (functional_rank vs academic_rank incl. guru_besar vs profesor)
- (C6-be-hr) lecturer-profile phone not synced to employees.phone
- (C6-be-hr) owner receives unmasked NIK/NPWP/bank numbers
- (C6-be-hr) hr.request_returned template may be missing from HrNotificationService::TEMPLATES
- (C6-be-hr) no tenant.access middleware on /lecturer-profile unlike /me/hr
- (C6-be-hr) leave days ignore public holidays
- (C7-fe-portals) Client permission gating only; backend authoritative.
- (C7-fe-portals) Public exam endpoints send no tenant header: backend must resolve tenant from token.
- (C7-fe-portals) No token refresh; web stores token in localStorage; 401 = logout.
- (C7-fe-portals) KEU amounts are strings.
- (C7-fe-portals) Exam deadline computed client-side for UX only; server finalises.
- (C7-fe-portals) StudentDocument.url is relative and needs Bearer to download.
- (C7-fe-portals) Unrouted mock portal pages contain fake data.
- (C7-fe-portals) academic_advisor role has no web UI.
- (C8-fe-one-app) Prototype data/labels (e.g., grade scale, 75% attendance rule, service catalog, tuition values) are invented mock values, not business rules
- (C8-fe-one-app) Prototype brand is navy blue; main web app/landing uses Atma Jaya green/gold palette (tailwind brand scale) - confirm which brand the Flutter app follows
- (C8-fe-one-app) zh strings use Simplified Chinese; confirm ZH is wanted for mobile
- (C9-mobile-scaffold) CI analyze step will fail on the 2 deprecated_member_use infos (flutter analyze is fatal on infos by default).
- (C9-mobile-scaffold) Release build is signed with debug key; applicationId dev.civitasone.civitasone is permanent once published.
- (C9-mobile-scaffold) Offline cold start logs the user out (token deleted on any ApiFailure).
- (C9-mobile-scaffold) X-University-ID never sent: multi-university users get their default membership only; guest endpoints are tenant-less.
- (C9-mobile-scaffold) Exam form recreates TextEditingControllers every build (caret jump/leak).
- (C9-mobile-scaffold) Cleartext HTTP to 10.0.2.2/localhost permitted in all flavors including prod (limited to emulator aliases).
- (C9-mobile-scaffold) Raw exception text ($error) and 'Gagal memuat ujian: $error' shown to users.
- (C9-mobile-scaffold) Google Fonts fetched at runtime (no bundled font) can fail offline.
- (C9-mobile-scaffold) Riverpod 2 APIs used (FamilyAsyncNotifier) block upgrade to v3.
