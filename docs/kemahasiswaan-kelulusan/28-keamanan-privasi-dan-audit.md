# 28 — Keamanan, Privasi, dan Audit

> **Status:** rancangan · Disusun 2026-10-13 (commit `47043ca`) · Kembali ke [README](README.md)
> Aturan: `UMUM-BR-001`–`UMUM-BR-012` ([29](29-business-rules-dan-validasi.md)) · Hak akses: [03](03-aktor-role-dan-hak-akses.md)

Prinsip yang dipegang: **least privilege**, **minimisasi data**, **pembatasan tujuan**, **penegakan di backend**, dan **jejak yang tidak dapat diubah** untuk keputusan dan dokumen resmi. Kepatuhan terhadap regulasi perlindungan data pribadi yang berlaku di Indonesia perlu dikonfirmasi oleh fungsi hukum/DPO kampus dan platform ([KK-Q-27](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q)). Dokumen ini menyediakan kendali teknis yang mendukungnya, bukan opini hukum.

## Daftar Isi

1. [Temuan Keamanan Repository yang Relevan](#1-temuan-keamanan-repository-yang-relevan)
2. [Model Ancaman Ringkas](#2-model-ancaman-ringkas)
3. [Isolasi Tenant dan Lapis Otorisasi](#3-isolasi-tenant-dan-lapis-otorisasi)
4. [Klasifikasi dan Perlindungan Data](#4-klasifikasi-dan-perlindungan-data)
5. [Persetujuan dan Pembatasan Tujuan](#5-persetujuan-dan-pembatasan-tujuan)
6. [Pemisahan Tugas](#6-pemisahan-tugas)
7. [Audit Trail](#7-audit-trail)
8. [Hak Subjek Data](#8-hak-subjek-data)
9. [Retensi Data](#9-retensi-data)
10. [Tautan Publik dan Token](#10-tautan-publik-dan-token)
11. [Validasi Berkas](#11-validasi-berkas)
12. [Rate Limiting](#12-rate-limiting)
13. [Ekspor Data](#13-ekspor-data)
14. [Penanganan Akses Tidak Sah](#14-penanganan-akses-tidak-sah)
15. [Acceptance Criteria dan Test Case](#15-acceptance-criteria-dan-test-case)

---

## 1. Temuan Keamanan Repository yang Relevan

| ID | Temuan (terverifikasi) | Penanganan rancangan |
|---|---|---|
| SEC-F01 | Sebagian besar grup rute modul (mis. `Alumni`, `Scholarship`, `Academic`) memakai `auth:sanctum` + `permission:` **tanpa** `tenant.access`. Hanya `/tenant` dan `/hr` memakai `tenant.access` | Seluruh rute modul baru wajib `tenant.access` (§3) |
| SEC-F02 | Scope fakultas/prodi belum ditegakkan. `user_roles.scope_type/scope_id` ada tetapi tidak dipakai seeder/policy | Bergantung `AcademicScope` (rancangan `academic-learning`). Modul ini memakai policy scope pada setiap query staf |
| SEC-F03 | `alumni.read` diberikan ke 8 role tanpa scope dan memuat perusahaan & jabatan alumni | Data karier sensitif dipindah ke permission terpisah (§4) |
| SEC-F04 | ApprovalWorkflow hanya mengecualikan pemohon pada approver **kontekstual** (`ApproverResolver::contextualUserIds`). Langkah `role`/`user` tidak mencegah self-approval | Guard pemisahan tugas di service modul (§6) |
| SEC-F05 | Unduhan berkas melalui stream berpolicy (`FileUploadController::download`), tanpa signed URL; `RestrictsFileAccess` tersedia | Model sensitif modul ini mengimplementasikan `RestrictsFileAccess` |
| SEC-F06 | `VirusScanner` hanya `NullVirusScanner` di belakang flag `file_upload.virus_scan_enabled` | Rekomendasi: aktifkan pemindai nyata sebelum menerima bukti publik (sertifikat, CV) |
| SEC-F07 | Surat aktif dirender ulang setiap unduhan dengan nomor turunan ULID | Snapshot + nomor resmi ([09](09-rancangan-surat-keterangan-aktif.md)) |
| SEC-F08 | `audit_logs` tanpa kebijakan retensi | §9 |
| SEC-F09 | Precedent token publik: `exams.access_token` disimpan **plaintext** (pengenal ujian), sedangkan `session_token` disimpan sebagai hash | Token modul ini (survei, undangan, verifikasi, check-in) disimpan sebagai hash |

## 2. Model Ancaman Ringkas

| Aset | Ancaman utama | Kendali |
|---|---|---|
| Keputusan kelulusan & dokumen resmi | Pemalsuan, perubahan diam-diam, penerbitan tanpa wewenang | Workflow, SoD, snapshot + hash, verifikasi publik, audit |
| Poin & sanksi | Manipulasi poin, sanksi tanpa proses | Ledger append-only, hak didengar, approval |
| Jawaban tracer | Re-identifikasi, kebocoran ekspor | Pemisahan identitas, sel minimum, ekspor berizin |
| CV & profil karier | Scraping, akses perusahaan tanpa izin | Default privat, dasar akses, log akses terlihat pemilik |
| Data lintas tenant | Kebocoran antaruniversitas | `TenantScoped`, `tenant.access`, uji isolasi |
| Token publik | Penebakan, kebocoran via log | Entropi tinggi, hash, rate limit, redaksi log |
| Akun perusahaan | Lowongan palsu, penipuan | Verifikasi, moderasi, suspensi |

## 3. Isolasi Tenant dan Lapis Otorisasi

1. **Tenant**: semua tabel data pengguna ber-`university_id` + `TenantScoped` (Ada). Job antrean menyetel `TenantContext` eksplisit dari payload, dan uji memastikan job tanpa konteks gagal tertutup.
2. **Paket**: `feature:` sebelum `permission:` ([34](34-matriks-paket-dan-entitlement.md)).
3. **Permission**: slug `resource.action` ([03](03-aktor-role-dan-hak-akses.md)).
4. **Scope data**: `u`/`f`/`p`/`w`/pembimbing/milik sendiri. Objek di luar scope → 404 (bukan 403) agar keberadaan data tidak terungkap.
5. **Kepemilikan**: layanan mandiri selalu menyelesaikan identitas dari `$user->student` server-side (pola `ResolvesCurrentStudent`, Ada). ID mahasiswa dari klien diabaikan.
6. **Konteks perusahaan**: `X-Company-ID` divalidasi ke keanggotaan aktif. Query perusahaan atas data tenant hanya melalui service khusus ([25 §7](25-rancangan-database-dan-erd.md#7-kelompok-career-center)).
7. **Platform**: Super Admin bypass permission (Ada, `PermissionRegistry`), tetapi akses ke data tenant oleh Super Admin dalam konteks dukungan memakai `support_sessions` (Ada) dan tercatat.

## 4. Klasifikasi dan Perlindungan Data

| Kelas | Contoh | Penyimpanan | Akses |
|---|---|---|---|
| Publik | Lowongan terpublikasi, artikel publik, profil perusahaan publik, publikasi tracer agregat | Biasa | Semua |
| Internal | Daftar kategori, periode, statistik agregat | Biasa | Staf berizin |
| Pribadi | Nama, NIM, kontak, profil karier, CV, pengajuan aktivitas, pendaftaran yudisium/wisuda | Biasa; kontak alumni **[enc]** | Pemilik + staf dalam scope + pihak dengan dasar akses |
| Sensitif | NIK, data ekonomi pendaftar beasiswa, kasus disiplin, pendapatan, jawaban tracer `sensitive`, kebutuhan khusus wisuda, NPWP perusahaan | **[enc]**, tidak diindeks, tidak di log | Permission eksplisit + `viewed_sensitive` audit |
| Resmi | Keputusan kelulusan, dokumen terbit | Immutable + hash | Pemilik + staf penerbit + auditor |

**Perlindungan per area:**

| Area | Kendali khusus |
|---|---|
| Dokumen akademik | Berkas privat. Unduhan berpolicy + `document_access_logs`. Pratinjau berwatermark tanpa kode verifikasi |
| CV & data karier | Default privat. Akses perusahaan hanya berdasar lamaran/consent. Snapshot per lamaran. Log akses terlihat pemilik. Tidak ada endpoint "unduh semua CV" |
| Jawaban tracer | `tracer_responses.read` tidak memuat identitas. Identitas memerlukan `tracer_respondent_identity.*`. Admin prodi hanya agregat & jawaban terbuka tanpa identitas di prodinya |
| Data perusahaan | Dokumen legal hanya moderator platform. CRM hanya tenant pemilik |
| Pelanggaran | Permission eksplisit. Identitas pelapor rahasia. Dosen wali hanya ringkasan keputusan final |

## 5. Persetujuan dan Pembatasan Tujuan

`data_sharing_consents` ([25 §7](25-rancangan-database-dan-erd.md#7-kelompok-career-center)) menyimpan **tujuan**, penerima, data, versi teks, masa berlaku, dan status.

| Tujuan | Diperlukan untuk | Default |
|---|---|---|
| `profile_discovery` | Profil dapat ditemukan mitra/marketplace | Tidak |
| `recommendation_pool` | Dipertimbangkan untuk rekomendasi | Tidak |
| `recommendation` | Berbagi data ke perusahaan tertentu | Per permintaan |
| `job_application` | Berbagi data lamaran ke perusahaan pemilik lowongan | Melekat pada aksi melamar (teks jelas) |
| `tracer_profile_update` | Jawaban tracer memperbarui profil karier | Tidak |
| Publikasi foto/data buku wisuda | Buku wisuda | Tidak (pilihan di pendaftaran) |

Data yang dikumpulkan untuk satu tujuan tidak dipakai untuk tujuan lain tanpa dasar baru: jawaban tracer tidak dipakai untuk menyaring kandidat rekomendasi, data ekonomi beasiswa tidak dipakai di luar seleksi program, dan data disiplin tidak dipakai untuk rekomendasi karier.

## 6. Pemisahan Tugas

Guard `SegregationOfDutiesGuard` (Baru, dipakai seluruh modul) dijalankan pada setiap aksi persetujuan, **selain** pemeriksaan ApprovalWorkflow ([UMUM-BR-005](29-business-rules-dan-validasi.md#umum-br-005)):

| Aksi | Tidak boleh dilakukan oleh |
|---|---|
| Verifikasi aktivitas / event | Pengaju, pembuat event |
| Persetujuan override/koreksi poin | Pengusul koreksi, verifikator pengajuan yang sama |
| Persetujuan keputusan sanksi | Pelapor, petugas yang menyusun draf |
| Penetapan penerima beasiswa | Pengusul batch |
| Verifikasi yudisium tahap N+1 | Verifikator tahap N pendaftaran sama |
| Pengesahan keputusan kelulusan | Penyusun rapat (sebagai pengesah tunggal) |
| Publikasi versi aturan yudisium | Penyusun draf (bila `require_separate_publisher`) |
| Pembatalan dokumen | Pengaju pembatalan |
| Persetujuan rekomendasi lulusan | Petugas pengusul |
| Penyesuaian kuota | Pengguna tenant (hanya platform) |

Pelanggaran → 403 `SELF_APPROVAL_FORBIDDEN` / `CONFLICT_OF_INTEREST`. Rekomendasi perbaikan ApprovalWorkflow agar mengecualikan pemohon pada semua tipe approver dicatat sebagai temuan SEC-F04 (sama dengan KEU GAP-05).

## 7. Audit Trail

| Aksi sensitif | Mekanisme | Aksi `AuditAction` (Ada) |
|---|---|---|
| Perubahan master (kategori, aturan, template, skema nomor) | `Auditable` | `created`/`updated`/`deleted` |
| Pemberian, override, koreksi, pencabutan poin | Ledger + `recordAction` | `approved`, `forced_override`, `updated` |
| Laporan, kasus, keputusan & keberatan disiplin | `Auditable` + histori | `approved`, `rejected`, `viewed_sensitive` |
| Penetapan/penghentian beasiswa | Histori + audit | `approved`, `rejected` |
| Verifikasi, keputusan, koreksi, pembatalan kelulusan | Histori + audit | `verified`, `approved`, `forced_override` |
| Penerbitan, pembatalan, penerbitan ulang dokumen | `issued_document_events` + audit | `created`, `approved` |
| Unduhan dokumen/CV oleh staf/perusahaan | `document_access_logs` / `career_document_access_logs` + audit | `downloaded` |
| Ekspor | `data_exports` + audit | `exported` |
| Impor (peserta event, PIN, target tracer) | Audit | `imported` |
| Pengecualian/override aturan | Audit dengan alasan wajib | `forced_override` |
| Akses data sensitif | Audit | `viewed_sensitive` |

Kolom `reason` (Ada) wajib untuk `forced_override`, `rejected`, pembatalan, dan koreksi. Masking nilai sensitif di `old_values/new_values` memakai properti `$auditMasked` (Ada, dibaca `AuditLogService`, contoh pemakaian di model `Employee`) untuk kolom **[enc]**.

## 8. Hak Subjek Data

| Permintaan | Penanganan |
|---|---|
| Akses (melihat data sendiri) | Portal menampilkan profil, pengajuan, poin, dokumen, jawaban tracer sendiri, consent, dan log akses CV |
| Koreksi | Data induk melalui `student_requests` `data_change` (Ada). Data modul melalui alur koreksi masing-masing |
| Penarikan persetujuan | Cabut consent (berlaku ke depan) |
| Penghapusan | Profil karier & CV: dihapus/dianonimkan. Jawaban tracer: dianonimkan (identitas dilepas, `tracer_targets` dihapus tautan) bila dasar penyimpanan tidak lagi ada, dan agregat tetap. **Tidak dihapus**: keputusan kelulusan, dokumen resmi, keputusan disiplin final, ledger poin (kewajiban arsip institusi), cukup ditandai |
| Keberatan pemrosesan (mis. tidak ingin disurvei) | Supresi tracer `opt_out` |

Alur permintaan dicatat (`data_subject_requests`, opsional, bila platform menyediakan) dan diproses petugas berwenang ([KK-Q-27](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q)).

## 9. Retensi Data

Nilai default rancangan. Kampus menetapkan nilai final sesuai kebijakan arsip ([KK-Q-22](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q), [KK-Q-27](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q)).

| Data | Retensi default | Setelah retensi |
|---|---|---|
| Keputusan kelulusan, dokumen resmi terbit/dibatalkan, register nomor | Permanen | — |
| Ledger poin, keputusan disiplin final | Permanen (arsip mahasiswa) | — |
| Laporan & kasus disiplin yang ditutup tanpa pelanggaran | 5 tahun | Dianonimkan |
| Bukti aktivitas (berkas) | 2 tahun setelah lulus | Berkas dihapus, metadata tetap |
| Data ekonomi pendaftar beasiswa (ditolak) | 1 tahun setelah program ditutup | Dihapus |
| Jawaban tracer beridentitas | 5 tahun | Identitas dilepas, jawaban tetap untuk agregat |
| Token survei, undangan perusahaan | Kedaluwarsa + 90 hari | Dihapus |
| CV profil tidak aktif | 24 bulan tidak aktif | Dihapus setelah pemberitahuan |
| Snapshot lamaran | 24 bulan setelah final | Dianonimkan |
| `document_access_logs`, `career_document_access_logs` | 5 tahun | Dihapus |
| `audit_logs` (modul ini) | Mengikuti kebijakan platform. Rancangan menyarankan ≥ 5 tahun untuk aksi keputusan | — |
| Berkas ekspor (`data_exports`) | 7 hari | Dihapus otomatis |

Job `retention:apply` (harian) menjalankan kebijakan dan mencatat ringkasan di audit.

## 10. Tautan Publik dan Token

| Token | Entropi | Penyimpanan | Masa berlaku | Rate limit |
|---|---|---|---|---|
| Survei tracer | 256-bit | sha256 | Sampai periode tutup | 30/menit/IP |
| Kode verifikasi dokumen | 128-bit (base32 26 char) | sha256 | Permanen (status berubah) | 20/menit/IP |
| Undangan perusahaan | 256-bit | sha256 | 14 hari, sekali pakai | 10/menit/IP |
| Check-in wisuda | 128-bit | sha256 | Akhir hari upacara | Per perangkat petugas |

Halaman publik: `Referrer-Policy: no-referrer`, `X-Robots-Tag: noindex` (survei, verifikasi), tanpa skrip pihak ketiga, dan pesan galat seragam. Middleware redaksi menghapus token dari log request.

## 11. Validasi Berkas

| Konteks | MIME | Maks | Catatan |
|---|---|---|---|
| Bukti aktivitas / SKPI | pdf, jpg, jpeg, png | 5 MB (≤ `file.max_upload_size_mb`) | Deteksi MIME dari konten |
| Bukti disiplin | pdf, jpg, jpeg, png, mp4 | Setting | Akses terbatas |
| Berkas yudisium/wisuda (foto) | jpg, png | 2 MB | Validasi dimensi minimum |
| CV / dokumen lamaran | pdf | 5 MB | Metadata PDF tidak dibaca ke DB |
| Impor | xlsx, csv | 10 MB | Baris dibatasi |

Semua unggahan memakai `FileUploadService` (Ada): sha256, status pemindaian, disk privat. Berkas `rejected` tidak dapat dilampirkan.

## 12. Rate Limiting

| Endpoint | Batas default |
|---|---|
| Publik verifikasi / survei / undangan | §10 |
| Registrasi perusahaan | 5/jam/IP |
| Lamaran | 20/hari/pengguna |
| Posting lowongan | 20/hari/perusahaan |
| Periksa ulang eligibility mandiri | 3/jam/mahasiswa |
| API perusahaan | 120/menit/pengguna |
| Ekspor | 10/jam/pengguna |

## 13. Ekspor Data

Ekspor asinkron (`data_exports`), default tanpa identitas, kolom dipilih, identitas memerlukan permission khusus + `purpose`, dan berkas privat kedaluwarsa 7 hari serta hanya dapat diunduh pembuat. Agregat pimpinan menyupresi sel `< min_cell_size` ([UMUM-BR-010](29-business-rules-dan-validasi.md#umum-br-010)).

## 14. Penanganan Akses Tidak Sah

| Kejadian | Respons sistem |
|---|---|
| Permission tidak ada | 403, tanpa detail data |
| Objek di luar scope/tenant | 404 |
| Token tidak valid/kedaluwarsa | Pesan generik + rate limit |
| Lonjakan kegagalan (≥ 20 403/404 per pengguna per 5 menit, atau penebakan token) | Log keamanan + notifikasi admin platform, dan opsi blokir sementara |
| Perusahaan mengakses CV di luar dasar akses | 403 + catatan percobaan di log akses (tanpa berkas) |
| Dugaan kebocoran | Prosedur insiden platform (di luar rancangan modul) |

## 15. Acceptance Criteria dan Test Case

| ID | Kriteria / skenario | Prioritas |
|---|---|---|
| UMUM-AC-01 | Uji isolasi otomatis untuk setiap endpoint daftar baru: data tenant B tidak pernah muncul di konteks tenant A | P1 |
| UMUM-AC-02 | Setiap aksi pada tabel §7 menghasilkan baris audit dengan aktor, waktu, dan alasan bila wajib | P1 |
| UMUM-AC-03 | Seluruh token publik tersimpan sebagai hash (uji skema: tidak ada kolom token plaintext) | P1 |
| UMUM-AC-04 | Admin prodi A mendapat 404 untuk pendaftaran yudisium mahasiswa prodi B | P1 |
| UMUM-TC-001 | Isolasi data antartenant (semua modul) | P1 |
| UMUM-TC-002 | Self-approval ditolak pada langkah ApprovalWorkflow bertipe role | P1 |
| UMUM-TC-003 | Unduhan CV tercatat dan terlihat pemilik | P1 |
| UMUM-TC-004 | Ekspor tanpa izin identitas tidak memuat identitas | P1 |
| UMUM-TC-005 | Job retensi menganonimkan jawaban tracer kedaluwarsa tanpa mengubah agregat | P2 |
| UMUM-TC-006 | Token tidak tercatat di log request | P2 |
