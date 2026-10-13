# 30 — Skenario Pengujian

> **Status:** rancangan · Disusun 2026-10-13 (commit `47043ca`) · Kembali ke [README](README.md)
> **Katalog acuan test case.** Aturan bisnis: [29](29-business-rules-dan-validasi.md). Acceptance: [31](31-acceptance-criteria.md).

## 1. Strategi dan Data Uji

### 1.1 Jenis pengujian

| Kode | Jenis | Alat (sesuai repo) | Cakupan |
|---|---|---|---|
| U | Unit | Pest 4 (Ada) | Service domain: resolusi aturan, agregasi eligibility, predikat, format nomor, logika kuesioner, kuota |
| I | Integrasi / feature | Pest feature test + SQLite in-memory (Ada, `phpunit.xml`) dan MySQL untuk uji konkurensi | Endpoint, policy, transaksi, event & listener, kontrak antarmodul |
| E | End-to-end | Uji browser pada frontend (Vite/React) | Alur pengguna lintas halaman |
| S | Keamanan | Feature test negatif + uji statis | Isolasi tenant, permission, scope, token, ekspor |
| A | Penerimaan pengguna (UAT) | Skenario manual oleh pengguna kampus | Alur bisnis nyata dengan data kampus |
| K | Kinerja/konkurensi | Uji paralel pada MySQL staging | Penomoran, kuota, kapasitas sesi, eligibility massal |

Uji modul ditempatkan di `app/Modules/<Modul>/Tests/{Feature,Unit}` (pola Ada). Uji isolasi tenant mengikuti `Tenancy/Tests/Feature/TenantIsolationTest` (Ada).

### 1.2 Kriteria lulus/gagal umum

**Lulus** bila seluruh butir "Hasil diharapkan" terpenuhi **dan** tidak ada perubahan data di luar yang diharapkan (diperiksa dengan pembandingan jumlah baris/nilai tabel terkait sebelum-sesudah). **Gagal** bila salah satu butir tidak terpenuhi, respons HTTP/kode error berbeda, atau terjadi efek samping (baris tambahan, notifikasi ganda, audit hilang).

### 1.3 Data uji kanon

| Kode | Data |
|---|---|
| U-A, U-B | Dua tenant. U-A paket setara Professional, U-B setara Business (pemetaan uji via `plan_feature_entitlements`) |
| FTI / P-IF, P-SI | Fakultas & dua prodi di U-A |
| M1 | Mahasiswa aktif P-IF angkatan 2022, memenuhi semua syarat yudisium uji |
| M2 | Mahasiswa P-IF kurang SKS (ambang uji 144, aktual 138) |
| M3 | Mahasiswa P-IF IPK di bawah ambang uji |
| M4 | Mahasiswa P-IF bertunggakan (cekal yudisium dari KEU / stub kontrak) |
| M5 | Mahasiswa P-SI poin kemahasiswaan 88 (ambang uji 100) |
| A1 | Alumni U-A lulus 2025, email valid |
| A2 | Alumni U-A tanpa email valid |
| MB1 | Mahasiswa U-B |
| Staf | `sa_mgr` (student_affairs_manager), `sa_off` (officer), `dis_off`, `grad_off`, `adm_if` (study_program_administrator P-IF), `adm_fti` (faculty_administrator), `komite`, `tracer_off`, `cc_admin`, `auditor`, `univ_admin` |
| Perusahaan | CO1 terverifikasi (pemilik `co1_owner`, perekrut `co1_rec`), CO2 terverifikasi, CO3 `pending_verification` |
| Angka | Seluruh ambang adalah **nilai uji** yang dimasukkan ke konfigurasi, bukan nilai bawaan sistem |

Kolom tabel: **ID** · **Jenis** · **Tujuan** · **Prasyarat & data** · **Langkah** · **Hasil diharapkan (kriteria lulus)** · **Prioritas** · **BR**.

## 2. Kemahasiswaan

| ID | Jenis | Tujuan | Prasyarat & data | Langkah | Hasil diharapkan | Prio | BR |
|---|---|---|---|---|---|---|---|
| KEM-TC-001 | I | Persetujuan menghasilkan poin | Kategori KMP aktif, aturan juara1×nasional 40 (35–45); pengajuan M1 `submitted` | `sa_off` approve tanpa ubah poin | 200; status `approved`; 1 entri `award` 40/40; ringkasan +40 | P1 | KEM-BR-001 |
| KEM-TC-002 | I | Pengajuan belum disetujui tidak menambah poin | Pengajuan M1 `submitted` | GET poin M1 | Saldo tidak berubah; 0 entri ledger untuk pengajuan | P1 | KEM-BR-001 |
| KEM-TC-003 | U | Aturan paling spesifik | Aturan default 10, kategori×peran 20, kategori×peran×tingkat 40 | Resolve untuk juara1 nasional | Aturan 40 dipilih | P1 | KEM-BR-003 |
| KEM-TC-004 | U | Aturan menurut tanggal kegiatan | Aturan v1 s.d. 2026-06-30 (30), v2 sejak 2026-07-01 (40); kegiatan 2026-06-15, diverifikasi 2026-08-01 | Resolve | v1 (30) dipakai | P1 | KEM-BR-003 |
| KEM-TC-005 | I | Override dalam rentang | Aturan 40 (35–45) | Approve 42 dengan alasan | `is_override = true`, alasan tersimpan, tanpa approval | P2 | KEM-BR-004 |
| KEM-TC-006 | I | Override di luar rentang | idem | Approve 60 | 409 `OVERRIDE_REQUIRES_APPROVAL`; approval dibuat; setelah `sa_mgr` setuju → entri 60 dengan `approval_request_id` | P1 | KEM-BR-004 |
| KEM-TC-007 | U/I | Batas periode | Cap KMP 100/TA; akumulasi 92 | Approve peserta 10 | `awarded` 10, `counted` 8 | P1 | KEM-BR-005 |
| KEM-TC-008 | I | Koreksi poin | Entri award 40 | `sa_off` koreksi −10 alasan "salah tingkat" | Entri `correction` −10 menunjuk entri asal; entri asal tidak berubah (checksum) | P1 | KEM-BR-002, 009 |
| KEM-TC-009 | I | Cabut karena bukti palsu | Pengajuan approved 40 | `sa_mgr` revoke alasan | Entri −40; status `revoked`; event stale; capaian SKPI turunan `source_revoked` | P1 | KEM-BR-010 |
| KEM-TC-010 | I | Banding diterima | Pengajuan ditolak 3 hari lalu (window 14) | M1 banding → `sa_mgr` terima | Banding `accepted`; pengajuan kembali `under_review` | P2 | KEM-BR-011 |
| KEM-TC-011 | I | Banding lewat waktu | Keputusan 20 hari lalu | M1 banding | 422 `APPEAL_WINDOW_CLOSED` | P2 | KEM-BR-011 |
| KEM-TC-012 | I | Kategori terpakai tidak dihapus | Kategori dengan 1 pengajuan | DELETE | 409 `CATEGORY_IN_USE` | P2 | KEM-BR-007 |
| KEM-TC-013 | I | Aturan tumpang tindih | Aturan aktif 2026-01-01..null | Publish aturan kunci sama mulai 2026-06-01 tanpa menutup lama | 409 `RULE_OVERLAP` | P2 | V-PNT-02 ([05](05-rancangan-poin-kemahasiswaan.md#10-validasi)) |
| KEM-TC-014 | K | Persetujuan bersamaan & cap | Akumulasi 95, cap 100; dua pengajuan 10 | Approve paralel (MySQL) | Total `counted` tambahan = 5 | P1 | KEM-BR-005 |
| KEM-TC-015 | I | Rebuild ringkasan | 1.000 entri acak | Jalankan rebuild | Nilai identik; tidak ada audit selisih | P2 | KEM-BR-006 |
| KEM-TC-016 | U | Poin untuk yudisium | Entri kategori `counts_for_graduation=false` 20 dan kegiatan sebelum `enrolled_at` 10 | Hitung | Tidak termasuk dalam `graduation_counted_points` | P1 | KEM-BR-008 |
| KEM-TC-017 | I | Aturan tidak surut | Entri award 40 | Publikasi aturan baru 50 | Entri tetap 40 | P2 | KEM-BR-012 |
| KEM-TC-018 | I | Status tidak berhak | Mahasiswa `dropped_out` | Buat pengajuan | 409 `STUDENT_STATUS_NOT_ALLOWED` | P2 | KEM-BR-013 |
| KEM-TC-019 | I | Bukti sama oleh mahasiswa sama | Pengajuan aktif dengan checksum X | Kirim pengajuan lain dengan berkas X | 409 `DUPLICATE_EVIDENCE` | P1 | KEM-BR-022 |
| KEM-TC-020 | E | Pengajuan lengkap | M1, kategori SEM | Wizard → kirim → `sa_off` setujui | Status `approved`; notifikasi `approved` 1×; poin tampil di portal | P1 | KEM-BR-020 |
| KEM-TC-021 | I/S | Bukti tidak valid | Berkas `.exe` diganti nama `.pdf` | Unggah & kirim | 422; tidak ada pengajuan `submitted` | P1 | KEM-BR-021 |
| KEM-TC-022 | I | Pengajuan duplikat | Pengajuan aktif "Seminar AI" 2026-05-10 peserta | Ajukan lagi (judul beda kapital/spasi) | 409 `DUPLICATE_SUBMISSION` | P1 | KEM-BR-023 |
| KEM-TC-023 | I | Bukti sama mahasiswa lain | M1 memakai sertifikat kolektif X | M2 memakai X | 201; flag `shared_evidence` | P2 | KEM-BR-022 |
| KEM-TC-024 | I | Tolak wajib alasan | Pengajuan `under_review` | Reject tanpa `note` | 422 | P2 | KEM-BR-027 |
| KEM-TC-025 | I | Batas revisi | `max_revisions` = 2, sudah 2× | Kembalikan ke-3 / kirim ulang | Status `rejected` alasan sistem | P2 | KEM-BR-027 |
| KEM-TC-026 | I | Klaim event tidak di daftar | Event verified, M3 tidak di daftar | M3 klaim | 201 dengan flag `not_in_participant_list`; klaim kedua peran sama → 409 | P2 | KEM-BR-025 |
| KEM-TC-027 | I | Lewat batas hari | Window 90, kegiatan 120 hari lalu | Kirim | 409 `SUBMISSION_WINDOW_CLOSED` | P2 | KEM-BR-024 |
| KEM-TC-028 | S | Self-verification | `sa_off` juga pengaju (mahasiswa-staf) | Approve sendiri | 403 `SELF_APPROVAL_FORBIDDEN` | P1 | KEM-BR-026 |
| KEM-TC-029 | I | Cabut → stale | M1 punya eligibility `eligible` | Revoke pengajuan | Eligibility `is_stale = true` ≤ 1 menit | P1 | KEM-BR-010 |
| KEM-TC-030 | I | Laporan belum diputus | Laporan & kasus `investigating` untuk M1 | Jalankan eligibility & `DisciplinaryRecordReader` | Reader kosong; eligibility tidak memuat item gagal disiplin | P1 | KEM-BR-030, 038 |
| KEM-TC-031 | I | Penyaringan → dismissed | Laporan baru | `dis_off` dismiss + alasan | `dismissed`; tidak ada kasus | P3 | KEM-BR-030 |
| KEM-TC-032 | I | Hak didengar | Kasus tanpa hearing; `require_hearing` | Buat & ajukan draf | 409 `HEARING_REQUIRED` | P1 | KEM-BR-032 |
| KEM-TC-033 | I | Keputusan final dengan restricted services | Draf skorsing `restricted_services=[yudisium]` | Approval selesai | `final`; `academic_blocks` (stub/projector) aktif; eligibility M1 `not_eligible` item disiplin | P1 | KEM-BR-031, 038 |
| KEM-TC-034 | I | Perubahan sanksi via keberatan | Keputusan final, keberatan | Putus `modified` | Keputusan lama `superseded`; baru `final`; histori lengkap | P1 | KEM-BR-034, 035 |
| KEM-TC-035 | I | Pembatalan keputusan | Keputusan final + cekal | Putus `annulled` | `annulled`; cekal dicabut | P1 | KEM-BR-035 |
| KEM-TC-036 | S | Pelapor rahasia | Laporan `reporter_confidential` | M1 GET kasus | Respons tanpa identitas pelapor | P1 | KEM-BR-036 |
| KEM-TC-037 | S | Akses tanpa izin eksplisit | `univ_admin` (punya `students.read`) | GET kasus | 403 | P1 | KEM-BR-036 |
| KEM-TC-038 | S | Penyetuju = pelapor | Pelapor = pejabat langkah 1 | Approve | 403 `CONFLICT_OF_INTEREST` | P2 | KEM-BR-036 |
| KEM-TC-039 | I | Wajib event terverifikasi | Kategori `requires_organizer_verification` | Kirim tanpa event / event `pending_verification` | 422 | P2 | KEM-BR-028 |
| KEM-TC-040 | I | Tarik setelah disetujui | Pengajuan `approved` | Withdraw | 409 `INVALID_STATUS_TRANSITION` | P3 | KEM-BR-029 |
| KEM-TC-041 | U | Sanksi berakhir | Keputusan `effective_until` kemarin (zona tenant) | Reader hari ini | Tidak aktif | P2 | KEM-BR-033 |
| KEM-TC-042 | I | Keberatan lewat batas | Keputusan 20 hari lalu, window 14 | Ajukan keberatan | 422 | P2 | KEM-BR-034 |
| KEM-TC-043 | U | Poin pelanggaran terpisah | `violation_points` 10, setting default | Hitung poin aktivitas | Tidak berkurang | P3 | KEM-BR-037 |
| KEM-TC-044 | I | Keputusan dibatalkan tidak tampil | Keputusan `annulled` | Terbitkan surat berkelakuan baik / laporan sanksi aktif | Tidak memuat keputusan itu | P2 | KEM-BR-039 |
| KEM-TC-050 | I | Daftar di luar periode | Program tutup kemarin | Daftar | 409/422 | P2 | KEM-BR-050 |
| KEM-TC-051 | K | Kuota penerima | Kuota 10, aktif 8; batch 3 | Approve batch | 409 `QUOTA_EXCEEDED`; tidak ada award baru | P1 | KEM-BR-052 |
| KEM-TC-052 | S | Penetapan oleh pengusul | Pengusul = penyetuju | Approve | 403 | P1 | KEM-BR-053 |
| KEM-TC-053 | I | Event ke KEU idempoten | Award aktif | Pancarkan `ScholarshipAwardActivated` 3× (stub KEU) | 1 `student_discounts` | P1 | KEM-BR-055 |
| KEM-TC-054 | I | Status DO memicu evaluasi | Award aktif, M ubah status DO | Observer | Evaluasi luar jadwal tercatat | P2 | KEM-BR-058 |
| KEM-TC-055 | I | Exclusive | M penerima program exclusive A | Penetapan program B | Ditolak tanpa izin beralasan | P2 | KEM-BR-054 |
| KEM-TC-056 | S | Data ekonomi terenkripsi | Pendaftar isi penghasilan | Baca DB & API tanpa izin | Kolom terenkripsi; API 403/field tidak ada | P1 | KEM-BR-059 |
| KEM-TC-057 | I | Buka program tidak lengkap | Program tanpa persyaratan | Open | 422 | P3 | KEM-BR-050 |
| KEM-TC-058 | I | Daftar ganda | Pendaftaran ada | Daftar lagi | 409 | P3 | KEM-BR-051 |
| KEM-TC-059 | I | Evaluasi gagal | IPS di bawah syarat | Job evaluasi | `failed`, award tetap `active` | P2 | KEM-BR-056 |
| KEM-TC-060 | I | Henti tidak surut | Tagihan term T-1 lunas | Terminate efektif T | Tagihan T-1 tidak berubah (stub KEU) | P2 | KEM-BR-057 |
| KEM-TC-070 | E | Surat aktif sampai terbit | M1 aktif, term berjalan | Ajukan → setuju → terbit | Nomor resmi sesuai format; PDF; status `issued` | P1 | KEM-BR-070, 071 |
| KEM-TC-071 | I | Mahasiswa cuti | Status `leave` | Ajukan | Ditolak dengan pesan | P2 | KEM-BR-070 |
| KEM-TC-072 | I | Unduh ulang | Surat issued | Ubah nama M1; unduh 2× | Hash sama dengan saat terbit; 2 baris `document_access_logs` | P1 | KEM-BR-072, 076 |
| KEM-TC-073 | E | Pembatalan & verifikasi publik | Surat issued | Batal disetujui; buka `/verifikasi/{code}` | "DIBATALKAN"; tanpa NIM | P1 | KEM-BR-073, 075 |
| KEM-TC-074 | I | Terbit ulang | Surat issued | Reissue | Dokumen baru `supersedes`; lama `superseded` | P2 | KEM-BR-073 |
| KEM-TC-075 | I | Listener idempoten | Approval approved | Jalankan listener 3× | 1 dokumen | P1 | KEM-BR-071 |
| KEM-TC-076 | I | Batas pengajuan | 3 surat belum terbit | Ajukan ke-4 | 409 `TOO_MANY_ACTIVE_REQUESTS` | P3 | KEM-BR-077 |
| KEM-TC-077 | U | Mode wet_signature | Mode default | Render | PDF tanpa frasa keabsahan elektronik | P2 | KEM-BR-074 |

## 3. Yudisium dan Dokumen

| ID | Jenis | Tujuan | Prasyarat & data | Langkah | Hasil diharapkan | Prio | BR |
|---|---|---|---|---|---|---|---|
| KEL-TC-001 | I | Buka periode tanpa aturan | P-SI tanpa set | Open periode berunit FTI | 422 menyebut P-SI | P1 | KEL-BR-005 |
| KEL-TC-002 | I | Versi immutable | Versi published | PUT | 409 | P1 | KEL-BR-006 |
| KEL-TC-003 | U | Resolusi spesifik | Set FTI-S1 dan set P-IF-kurikulum2024 | Resolve M1 | Set P-IF | P1 | KEL-BR-007 |
| KEL-TC-004 | I | Konflik setara | Dua set P-IF tanpa kurikulum | Publish kedua | 409 `RULE_CONFLICT` | P2 | KEL-BR-007 |
| KEL-TC-005 | I | Parameter kosong | Checker `academic.gpa` tanpa `min_gpa` | Simpan | 422 | P1 | KEL-BR-008 |
| KEL-TC-006 | I | Periode tumpang tindih | Periode open P-IF Okt–Nov | Open periode kedua Nov–Des | 409 | P2 | KEL-BR-009 |
| KEL-TC-007 | I | Finalisasi periode | 1 pendaftaran `submitted` | Finalize | 409 dengan daftar | P2 | KEL-BR-010 |
| KEL-TC-008 | I | Simulasi | Versi draf | Simulate | Laporan perbandingan; 0 baris `graduation_eligibility_checks` baru | P2 | KEL-BR-006 |
| KEL-TC-020 | I | Semua syarat terpenuhi | M1 | Periksa | `eligible`; semua item `passed` dengan `source.as_of`; versi tersimpan | P1 | KEL-BR-001, 020 |
| KEL-TC-021 | I | Kurang SKS | M2 | Periksa | `not_eligible`; item SKS failed aktual 138 ambang 144 | P1 | KEL-BR-020 |
| KEL-TC-022 | I | IPK belum memenuhi | M3 | Periksa | item IPK failed | P1 | KEL-BR-020 |
| KEL-TC-023 | I/S | Tunggakan | M4 (stub KEU tercekal) | Periksa; GET hasil sebagai M4 | item keuangan failed; respons tanpa nominal | P1 | KEL-BR-026 |
| KEL-TC-024 | I | Data akademik belum sinkron | M1 punya KRS term berjalan tanpa nilai final, `require_final_grades` | Periksa | item `pending_verification`; hasil `pending_verification` | P1 | KEL-BR-025 |
| KEL-TC-025 | I | Poin belum terpenuhi | M5 | Periksa | item poin failed 88/100 | P1 | KEL-BR-020 |
| KEL-TC-026 | I | Recheck setelah perubahan | M2 `not_eligible`; nilai perbaikan masuk (SKS 146) | Event nilai; tunggu debounce | Hasil lama stale; hasil baru `eligible` | P1 | KEL-BR-022 |
| KEL-TC-027 | I | Sumber tidak tersedia | Stub KEU melempar exception | Periksa | item `source_unavailable`; hasil `manual_review` | P1 | KEL-BR-021 |
| KEL-TC-028 | I | Pengecualian waivable | Syarat TOEFL waivable | Ajukan & setujui | item `exempted`; hasil `exempted` | P2 | KEL-BR-024 |
| KEL-TC-029 | I | Pengecualian non-waivable | Syarat SKS | Ajukan | 422 | P2 | KEL-BR-024 |
| KEL-TC-030 | K | Pemeriksaan bersamaan | M1 | 5 permintaan paralel | 1 hasil baru `is_current` | P2 | KEL-BR-027 |
| KEL-TC-031 | S | Isolasi job massal | Batch U-A | Jalankan | Tidak ada baris untuk mahasiswa U-B | P1 | UMUM-BR-001 |
| KEL-TC-032 | I | Stale saat verifikasi | Pendaftaran M1, hasil stale | `adm_if` verify | Recheck sinkron; verifikasi memakai hasil baru | P1 | KEL-BR-023 |
| KEL-TC-033 | U | Urutan agregasi | Kombinasi item | Agregasi | Sesuai tabel urutan | P2 | KEL-BR-025 |
| KEL-TC-040 | E | Alur penuh | M1, workflow Prodi→Fakultas→BAAK, pengesah | Daftar → 3 verifikasi → rapat → pengesahan → finalisasi | `final graduated`; `students.status = graduated`, `graduated_at`; alumni dibuat; event | P1 | KEL-BR-003 |
| KEL-TC-041 | I | Daftar dengan not_eligible | M2 | Submit | 409 `NOT_ELIGIBLE` | P1 | KEL-BR-040 |
| KEL-TC-042 | I | Tolak wajib alasan | Pendaftaran `submitted` | Reject tanpa issues | 422 | P2 | KEL-BR-042 |
| KEL-TC-043 | E | Kembalikan & perbaiki | Berkas foto ditolak | Return → M1 unggah ulang → submit | Kembali ke tahap prodi | P2 | KEL-BR-042 |
| KEL-TC-044 | S | Verifikator dua tahap | `adm_if` juga ditugaskan tahap fakultas | Verify tahap 2 | 403 | P2 | KEL-BR-041 |
| KEL-TC-045 | I | Pra-penetapan menemukan perubahan | M1 hasil > 60 menit; tunggakan muncul | Input `graduated` | 409 dengan item keuangan | P1 | KEL-BR-045 |
| KEL-TC-046 | U | Predikat | Aturan uji: tier A IPK ≥ X & studi ≤ Y | Hitung untuk tiga profil | Predikat sesuai tabel uji | P1 | KEL-BR-046 |
| KEL-TC-047 | I | Override predikat tanpa alasan | — | Ubah predikat | 422 | P2 | KEL-BR-046 |
| KEL-TC-048 | I | Finalisasi & status | Batch disahkan | Finalize | Status mahasiswa via kontrak; histori 1 baris | P1 | KEL-BR-048 |
| KEL-TC-049 | I | Koreksi keputusan | Final M1 nama salah | Koreksi disetujui | Keputusan baru final, lama superseded, antrean terbit ulang | P1 | KEL-BR-049 |
| KEL-TC-050 | I | Pembatalan kelulusan | Final + ijazah terbit | Annul disetujui | `annulled`; revocation ijazah dibuat; status dikembalikan; role alumni dicabut | P1 | KEL-BR-050 |
| KEL-TC-051 | I | Daftar ulang setelah lulus | M1 final graduated | Daftar periode lain | 409 `ALREADY_GRADUATED` | P2 | KEL-BR-051 |
| KEL-TC-052 | I | Eligible ≠ lulus | M1 eligible | Cek status mahasiswa | Tetap `active` | P1 | KEL-BR-002 |
| KEL-TC-053 | S | Finalisasi tanpa pengesahan | Batch `pending_approval` | Finalize | 409; tidak ada perubahan status | P1 | KEL-BR-003 |
| KEL-TC-054 | I | Data diri berbeda | M1 ubah nama saat konfirmasi | Submit | `student_requests` `data_change` dibuat; `students.name` tidak berubah | P2 | KEL-BR-043 |
| KEL-TC-055 | I | Keputusan di rapat belum held | Rapat `scheduled` | Input keputusan | 409 | P2 | KEL-BR-044 |
| KEL-TC-056 | U | Tanggal lulus | Kebijakan `meeting_date`, rapat 2026-11-20 23:30 WIB, finalisasi 2026-11-22 | Hitung | 2026-11-20 | P2 | KEL-BR-047 |
| KEL-TC-060 | U | Format nomor | `{seq:3}/{unit_code}/SK-YUD/{roman_month}/{year}`, seq 45, Nov 2026 | Format | `045/FTI/SK-YUD/XI/2026`; format tanpa `{year}` reset yearly → 422 | P1 | KEL-BR-060 |
| KEL-TC-061 | K | Nomor ganda dicegah | Skema sama | 1.000 issue paralel (MySQL) | 1.000 nomor unik | P1 | KEL-BR-004, 061 |
| KEL-TC-062 | I | Issue idempoten | — | Issue dengan `idempotency_key` sama 2× | 1 dokumen | P1 | UMUM-BR-006 |
| KEL-TC-063 | I | Render gagal | Renderer dipaksa gagal | Issue; perbaiki; retry | Draf bernomor; setelah retry `issued` dengan nomor sama; tidak ada nomor baru | P2 | KEL-BR-062 |
| KEL-TC-064 | I | Terbit ulang dua kebijakan | Skema `new_number` & `same_number_with_suffix` | Reissue | Nomor baru / nomor `-R1` | P2 | KEL-BR-064 |
| KEL-TC-065 | I | Pembatalan & status verifikasi | Dokumen issued | Revoke | Verifikasi "DIBATALKAN"; tetap di register | P1 | KEL-BR-065 |
| KEL-TC-066 | I | Snapshot tetap | Ijazah issued | Ubah nama & prodi mahasiswa | Snapshot & hash tetap | P1 | KEL-BR-063 |
| KEL-TC-067 | I | Impor PIN | 5 baris: 1 duplikat, 1 format salah, 1 NIM tanpa kelulusan | Impor | 2 berhasil, 3 gagal dengan alasan | P2 | KEL-BR-066 |
| KEL-TC-068 | S | Verifikasi publik minimal | Transkrip issued | GET publik | Tanpa NIM, IPK, berkas | P1 | KEM-BR-075 |
| KEL-TC-069 | S | Rate limit verifikasi | — | 30 permintaan/menit | 429 setelah batas | P2 | UMUM-BR-009 |
| KEL-TC-070 | U | Identitas berbeda | Dokumen issued | Bandingkan | `verification_code` ≠ `official_number` ≠ `id` | P3 | KEL-BR-067 |

## 4. Wisuda

| ID | Jenis | Tujuan | Prasyarat & data | Langkah | Hasil diharapkan | Prio | BR |
|---|---|---|---|---|---|---|---|
| WIS-TC-001 | E | Pendaftaran sampai confirmed | M1 final graduated, biaya berlaku | Daftar → KEU lunas (stub) → validasi → konfirmasi | `confirmed`; kartu tersedia | P1 | WIS-BR-001 |
| WIS-TC-002 | I | Tidak eligible | M2 belum lulus | Daftar | 409 `NOT_ELIGIBLE_FOR_CEREMONY` | P1 | WIS-BR-001 |
| WIS-TC-003 | K | Kuota sesi penuh | Sesi kapasitas 150 | 200 penempatan paralel | 150 sukses, 50 `SESSION_FULL` | P1 | WIS-BR-004 |
| WIS-TC-004 | I | Perubahan jadwal | Sesi berisi 100 | Ubah jam | 100 notifikasi (dedupe); histori; kartu baru | P2 | WIS-BR-006 |
| WIS-TC-005 | I | Check-in ganda | Peserta confirmed | Pindai 3× | 1 `checked_in`, 2 `duplicate_scan` | P1 | WIS-BR-007 |
| WIS-TC-006 | I | Buku dengan data belum valid | Foto belum verified untuk 3 peserta | Bangun edisi | 3 entri `held` + laporan | P1 | WIS-BR-009 |
| WIS-TC-007 | I | Lunas dari KEU | Stub KEU `isSettled=false` lalu `true` | Event `InvoiceStatusChanged` | `payment_pending` → `submitted` | P1 | WIS-BR-003 |
| WIS-TC-008 | I | Keputusan dibatalkan | Pendaftaran confirmed | Event annul | `cancelled`; event ke KEU | P2 | WIS-BR-012 |
| WIS-TC-009 | I | Pendamping melebihi | Maks 2 | Tambah 3 | 422 | P3 | WIS-BR-005 |
| WIS-TC-010 | I | Daftar ganda lintas periode | Pendaftaran aktif periode I | Daftar periode II | 409 | P2 | WIS-BR-002 |
| WIS-TC-011 | I | Check-in sesi salah / belum konfirmasi | Peserta sesi 2; peserta `verified` | Pindai di sesi 1 | 409 `WRONG_SESSION` / `NOT_CONFIRMED` | P2 | WIS-BR-008 |
| WIS-TC-012 | I | Edisi immutable | Edisi published | Ubah entri | 409; revisi → edisi 2 dengan diff | P2 | WIS-BR-010 |
| WIS-TC-013 | I | Foto tanpa consent | `photo_publication_consent=false` | Bangun & render | Tanpa foto | P2 | WIS-BR-011 |
| WIS-TC-014 | I | Batal setelah bayar | Lunas | Batalkan | Event `CeremonyRegistrationCancelled` 1× | P2 | WIS-BR-012 |
| WIS-TC-015 | U | Alokasi deterministik | Data sama | Alokasi 2× | Hasil identik; urut fakultas→prodi→NIM | P3 | WIS-BR-013 |

## 5. SKPI

| ID | Jenis | Tujuan | Prasyarat & data | Langkah | Hasil diharapkan | Prio | BR |
|---|---|---|---|---|---|---|---|
| SKPI-TC-001 | I | Capaian valid masuk | Aktivitas M1 approved, kategori terpetakan | Event | Capaian `verified` kandidat; 1 baris (event 2× tetap 1) | P1 | SKPI-BR-002 |
| SKPI-TC-002 | I | Capaian ditolak tidak masuk | Aktivitas rejected | — | Tidak ada capaian | P1 | SKPI-BR-001 |
| SKPI-TC-003 | I | Capaian belum diverifikasi | Capaian manual `submitted` dipilih | Submit draf | 409 `UNVERIFIED_ACHIEVEMENT` | P1 | SKPI-BR-001 |
| SKPI-TC-004 | I | Dua bahasa | 1 item `translation_status=draft` | Submit draf | 409 `TRANSLATION_NOT_REVIEWED` | P1 | SKPI-BR-004, 005 |
| SKPI-TC-005 | I | Generate PDF | Draf approved, template bilingual | Issue | PDF dua bahasa, nomor, QR, hash | P1 | SKPI-BR-007 |
| SKPI-TC-006 | I | Revisi | SKPI issued | Revisi → approve → issue | Baru `issued`, lama `superseded` | P1 | SKPI-BR-006 |
| SKPI-TC-007 | E | Verifikasi nomor | SKPI issued | Buka kode publik | Status SAH, nomor sesuai, tanpa capaian | P1 | SKPI-BR-007, 009 |
| SKPI-TC-008 | S | Pembatasan akses | M2 & `adm_si` | GET SKPI/bukti M1 | 404/403 | P1 | SKPI-BR-009 |
| SKPI-TC-009 | I | Sumber dicabut | Draf memuat capaian dari aktivitas | Revoke aktivitas | Capaian `source_revoked`; draf `source_changed` | P2 | SKPI-BR-002 |
| SKPI-TC-010 | I | Data akademik dari keputusan | Keputusan IPK 3,81 | Susun draf | IPK 3,81 dari snapshot | P2 | SKPI-BR-011 |
| SKPI-TC-011 | I | Terbit tanpa kelulusan | M1 belum final | Issue | 409 | P1 | SKPI-BR-003 |
| SKPI-TC-012 | U | Glosarium | Istilah predikat di glosarium | Susun item | Teks EN memakai istilah glosarium; penyimpangan ditandai | P2 | SKPI-BR-005 |
| SKPI-TC-013 | I | Batas tampil | Batas 5, dipilih 6 | Submit | 409 `DISPLAY_LIMIT_EXCEEDED` | P2 | SKPI-BR-008 |
| SKPI-TC-014 | I | Versi tercatat | Issue | Baca dokumen | `document_template_version_id` & profil prodi versi terisi | P3 | SKPI-BR-010 |

## 6. Tracer

| ID | Jenis | Tujuan | Prasyarat & data | Langkah | Hasil diharapkan | Prio | BR |
|---|---|---|---|---|---|---|---|
| TRACER-TC-001 | I | Bangun target & dedupe | Cohort 2025 (100 alumni) | Build 2× | 100 target | P1 | TRACER-BR-005 |
| TRACER-TC-002 | I | Email tidak valid | A2 | Build | `excluded no_valid_email` | P2 | TRACER-BR-005 |
| TRACER-TC-003 | I | Email bersama | Dua alumni email sama | Build | Flag `shared_email` | P3 | TRACER-BR-005 |
| TRACER-TC-004 | I | Kelulusan dibatalkan | Target A1 dengan respons | Annul | Target excluded; token dicabut; respons invalidated | P2 | TRACER-BR-012 |
| TRACER-TC-005 | I | Pembaruan profil atas consent | A1 menjawab bekerja + consent | Submit | `alumni_career_records` source `tracer_response`; tanpa consent → tidak ada | P1 | TRACER-BR-013 |
| TRACER-TC-006 | I | Belum diketahui | A1 tanpa catatan | Laporan & profil | "Belum diketahui"; tidak di "Belum bekerja" | P1 | TRACER-BR-012 |
| TRACER-TC-010 | I | Versi kuesioner | 10 respons v1; publish v2 (label F8 berubah) | Tampilkan respons v1 | Label v1; v2 untuk respons baru | P1 | TRACER-BR-001 |
| TRACER-TC-011 | I | Wajib bersyarat | F5 wajib bila F8=bekerja | Kirim F8=belum bekerja tanpa F5; F8=bekerja tanpa F5 | Pertama 201; kedua 422 | P1 | TRACER-BR-019 |
| TRACER-TC-012 | I | Jawaban tersembunyi dibuang | Klien mengirim F5 saat F8=studi lanjut | Submit | F5 tidak tersimpan | P1 | TRACER-BR-019 |
| TRACER-TC-013 | I | Mengisi dua kali | Respons submitted | Submit lagi kunci beda | 409 `ALREADY_SUBMITTED`; 1 respons | P1 | TRACER-BR-007 |
| TRACER-TC-014 | S | Pesan token seragam | Token acak & token kedaluwarsa | GET | Respons identik 410 | P2 | UMUM-BR-009 |
| TRACER-TC-015 | I | Template tanpa metadata | Template tanpa `source_version` | Publish | 422 | P2 | TRACER-BR-017 |
| TRACER-TC-016 | U | Siklus logika | F3 merujuk F4 setelahnya | Publish | 422 | P3 | TRACER-BR-019 |
| TRACER-TC-017 | I | Versi published immutable | v1 published | PUT | 409 | P1 | TRACER-BR-004 |
| TRACER-TC-020 | E | Distribusi | 50 target, kuota 200 | Kampanye | 50 `sent`; 50 consume | P1 | TRACER-BR-003 |
| TRACER-TC-021 | I | Gagal sebelum diterima | Mailer stub menolak 5 | Kampanye | 5 `failed`; 5 release; pemakaian 45 | P1 | TRACER-BR-003 |
| TRACER-TC-022 | I | Hard bounce | Undangan sent | Webhook hard bounce | Supresi; refund (kebijakan uji `true`); tidak ada pengingat | P1 | TRACER-BR-009 |
| TRACER-TC-023 | I | Reminder berulang | Jadwal 7/14/21, maks 3, jeda 5 | Majukan waktu 30 hari | Tepat 3 pengingat, jeda terpenuhi | P1 | TRACER-BR-008 |
| TRACER-TC-024 | I | Tidak mengingatkan yang selesai | Target submitted | Job | 0 pengingat | P1 | TRACER-BR-008 |
| TRACER-TC-025 | I | Tautan kedaluwarsa | Periode ditutup | Buka tautan | 410; pengingat berhenti | P1 | TRACER-BR-006, 020 |
| TRACER-TC-026 | I | Batas kuota | Sisa 15 | Kampanye 40 (`reject`), lalu `send_within_quota` | 409 detail kuota; lalu 15 terkirim | P1 | TRACER-BR-003 |
| TRACER-TC-027 | K | Reservasi paralel | Batas 50 | Dua kampanye 40 paralel | Total reservasi ≤ 50 | P1 | TRACER-BR-003 |
| TRACER-TC-028 | I | Retry job | Job dijalankan 3× | — | 1 email, 1 consume | P1 | TRACER-BR-010 |
| TRACER-TC-029 | I | Webhook duplikat | Event sama 2× | — | 1 `tracer_email_events` | P2 | TRACER-BR-009 |
| TRACER-TC-030 | I | Kuota habis, reminder jalan | Sisa 0 | Kampanye & job reminder | Kampanye 409; reminder terkirim | P2 | TRACER-BR-018 |
| TRACER-TC-031 | I | Terkirim ≠ selesai | 10 sent, 4 submitted | Status responden | Terkirim 10, Selesai 4 | P1 | TRACER-BR-002 |
| TRACER-TC-032 | S | Token hash & cabut | Token dibuat | Periksa DB; cabut; buka | Hanya hash di DB; 410 setelah cabut | P1 | TRACER-BR-006 |
| TRACER-TC-033 | S | Pendapatan | Pertanyaan `sensitive` aktif | Submit; baca tanpa izin | Terenkripsi; tidak tampil di jawaban anonim; agregat bila n ≥ 5 | P2 | TRACER-BR-016 |
| TRACER-TC-040 | U | Denominator | 100 eligible, 80 terundang, 40 responden | Hitung | `RR_ELG` 40%, `RR_INV` 50% | P1 | TRACER-BR-011 |
| TRACER-TC-041 | U | Non-respons | 10 belum bekerja, 15 tidak menjawab | Distribusi status | Kategori terpisah | P1 | TRACER-BR-012 |
| TRACER-TC-042 | S | Ekspor tanpa identitas | `tracer_off` tanpa izin identitas | Ekspor | Berkas tanpa NIM/nama/email; `respondent_ref` hash | P1 | TRACER-BR-014 |
| TRACER-TC-043 | S | Ekspor beridentitas | Izin identitas | Ekspor tanpa `purpose` / dengan | 422 / berhasil + audit | P1 | TRACER-BR-014 |
| TRACER-TC-044 | U | Sel kecil | Prodi n=3 | Agregat pimpinan | Disupresi "< 5" | P2 | TRACER-BR-014 |
| TRACER-TC-045 | U | Tren putus | Butir `semantic_change_review_required` | Tren | Garis putus + catatan | P3 | TRACER-BR-011 |
| TRACER-TC-046 | S | Isolasi tenant | Data U-A & U-B | Dashboard & ekspor U-A | Tanpa data U-B | P1 | UMUM-BR-001 |

## 7. Career Center

| ID | Jenis | Tujuan | Prasyarat & data | Langkah | Hasil diharapkan | Prio | BR |
|---|---|---|---|---|---|---|---|
| KARIR-TC-001 | E | Whitelabel | U-A berhak, branding diisi | Buka portal | Logo/warna/nama U-A | P1 | KARIR-BR-016 |
| KARIR-TC-002 | E | Fallback | U-B, branding diisi | Buka portal | Tema default; pengaturan tersimpan | P1 | KARIR-BR-016 |
| KARIR-TC-003 | S | Fitur tidak dalam paket | U-B | POST artikel | 403 `FEATURE_NOT_IN_PLAN` | P1 | KARIR-BR-015 |
| KARIR-TC-004 | I | Profil default private | Profil baru | Pencarian kandidat CO1 | Tidak ditemukan | P1 | KARIR-BR-008 |
| KARIR-TC-005 | S | Isolasi antaruniversitas | A1 (U-A) & MB1 (U-B) melamar lowongan marketplace CO1 | Staf U-A GET lamaran | Hanya A1; CO1 melihat keduanya | P1 | KARIR-BR-011 |
| KARIR-TC-006 | I | Retensi CV | CV tidak aktif 24 bln | Job retensi | Pemberitahuan; setelah 30 hari dihapus | P3 | KARIR-BR-020 |
| KARIR-TC-007 | I | Publikasi tracer agregat | Sel n=3 | Ajukan | 409; n ≥ 5 berhasil | P2 | KARIR-BR-017, TRACER-BR-015 |
| KARIR-TC-010 | E | Publikasi lowongan | CO1 | Buat → submit → moderator setuju | Tampil di portal U-A & U-B | P1 | KARIR-BR-002 |
| KARIR-TC-011 | I | Lowongan ditolak | — | Moderator tolak + alasan | `rejected`; notifikasi; tidak tampil | P2 | KARIR-BR-002 |
| KARIR-TC-012 | I | Lamaran ganda | A1 sudah melamar | Lamar lagi | 409 `DUPLICATE_APPLICATION` | P1 | KARIR-BR-005 |
| KARIR-TC-013 | E | Status lamaran | Lamaran A1 | CO1 reviewed → shortlisted → interview → offered → accepted | Notifikasi tiap transisi (5); riwayat 5 | P1 | KARIR-BR-006 |
| KARIR-TC-014 | I | Transisi tidak sah | Lamaran `submitted` | Ubah ke `accepted` | 409 | P2 | KARIR-BR-006 |
| KARIR-TC-015 | S | Akses CV tanpa izin | Lamaran A1 ke CO1 | `co2_rec` unduh CV | 403; percobaan tercatat | P1 | KARIR-BR-007 |
| KARIR-TC-016 | I | Snapshot dokumen | Lamaran terkirim | A1 hapus CV profil | CO1 tetap mengunduh snapshot | P2 | KARIR-BR-020 |
| KARIR-TC-017 | I | Suspensi perusahaan | CO1 2 lowongan published | Suspend | `taken_down`; pelamar diberi tahu | P2 | KARIR-BR-004 |
| KARIR-TC-018 | I | Rate limit | Batas 20/hari | Lamaran ke-21 | 429 | P3 | KARIR-BR-014 |
| KARIR-TC-019 | I | Tenant menyembunyikan | Lowongan marketplace | `cc_admin` U-A hide | Tidak tampil di U-A, tetap di U-B | P3 | KARIR-BR-012 |
| KARIR-TC-020 | E | Registrasi perusahaan | — | Registrasi + terima syarat | `unverified`/`pending_verification`; pemilik aktif | P1 | KARIR-BR-003 |
| KARIR-TC-021 | I | Verifikasi perusahaan | CO3 | Moderator setujui / tolak | `verified` / `rejected` + alasan | P1 | KARIR-BR-003 |
| KARIR-TC-022 | I | Undangan sekali pakai | Undangan diterima | Pakai token lagi | 410 | P2 | KARIR-BR-019 |
| KARIR-TC-023 | S | Status perusahaan vs perekrut | CO1 verified, `co1_rec` suspended | `co1_rec` ubah status lamaran | 403 `MEMBER_NOT_ACTIVE` | P1 | KARIR-BR-004 |
| KARIR-TC-024 | S | Isolasi CRM | Catatan U-A | U-B & CO1 akses | 404 | P1 | KARIR-BR-018 |
| KARIR-TC-025 | I | Registrasi duplikat | CO1 ada | Registrasi nomor registrasi sama | Permintaan bergabung, bukan perusahaan baru | P3 | KARIR-BR-003 |
| KARIR-TC-026 | S | Belum terverifikasi | CO3 | Posting lowongan publik | 403 `COMPANY_NOT_VERIFIED` | P1 | KARIR-BR-003 |
| KARIR-TC-027 | I | Tutup otomatis | Deadline kemarin | Job | `closed` | P2 | KARIR-BR-013 |
| KARIR-TC-030 | E | Rekomendasi lengkap | U-A, CO1 mitra aktif, A1 opt-in | Permintaan → cari → usul → setuju → consent → shared | CO1 melihat snapshot sesuai scope; keterlacakan lengkap | P1 | KARIR-BR-009 |
| KARIR-TC-031 | S | Tanpa persetujuan alumni | Kandidat `approved_by_campus`, consent belum | CO1 GET kandidat | Tidak termuat | P1 | KARIR-BR-001 |
| KARIR-TC-032 | I | Atribut terlindungi | Kriteria `gender` | Simpan | 422 `PROTECTED_ATTRIBUTE_CRITERIA` | P2 | KARIR-BR-009 |
| KARIR-TC-033 | I | Cabut consent | Shared | A1 cabut | CO1 tidak dapat membuka snapshot; notifikasi | P1 | KARIR-BR-010 |
| KARIR-TC-034 | S | Rekomendasi di luar paket | U-B | POST permintaan | 403 | P1 | KARIR-BR-009 |

## 8. Lintas Modul

| ID | Jenis | Tujuan | Prasyarat & data | Langkah | Hasil diharapkan | Prio | BR |
|---|---|---|---|---|---|---|---|
| UMUM-TC-001 | S | Isolasi tenant semua endpoint daftar | Data U-A & U-B per tabel | Iterasi seluruh endpoint GET daftar dengan konteks U-A | Tidak ada ID U-B; `X-University-ID` palsu tanpa keanggotaan → 403 | P1 | UMUM-BR-001 |
| UMUM-TC-002 | S | Self-approval tipe role | Workflow langkah `role` | Pemohon memegang role itu & approve | 403 | P1 | UMUM-BR-005 |
| UMUM-TC-003 | S | Log akses CV terlihat pemilik | Unduhan oleh CO1 | A1 GET access-log | Entri tercatat | P1 | KARIR-BR-007 |
| UMUM-TC-004 | S | Ekspor tanpa identitas | Lihat TRACER-TC-042 | | | P1 | UMUM-BR-010 |
| UMUM-TC-005 | I | Retensi tracer | Jawaban > retensi | Job | Identitas dilepas; agregat sama | P2 | UMUM-BR-010 |
| UMUM-TC-006 | S | Token tidak di log | Akses survei | Periksa log | Token disensor | P2 | UMUM-BR-009 |
| UMUM-TC-010 | I | Notifikasi tidak ganda | Event 3× / retry job | — | 1 notifikasi per kanal | P1 | UMUM-BR-011 |
| UMUM-TC-011 | I | Notifikasi wajib | Preferensi db off | Keputusan final | Tetap masuk `database` | P2 | UMUM-BR-011 |
| UMUM-TC-012 | U | Isi email keputusan | — | Render | Tanpa IPK/hasil | P2 | UMUM-BR-011 |
| UMUM-TC-020 | S | Fitur di luar paket | Lihat [34 §6](34-matriks-paket-dan-entitlement.md#6-cara-pengujian-pembatasan-paket) | | | P1 | UMUM-BR-002 |
| UMUM-TC-021 | I | Ubah batas tanpa deploy | idem | | | P1 | UMUM-BR-003 |
| UMUM-TC-022 | I | Fitur dimatikan tenant | idem | | | P2 | UMUM-BR-002 |
| UMUM-TC-023 | S | Menyalakan fitur di luar paket | idem | | | P2 | UMUM-BR-002 |
| UMUM-TC-024 | U | Uji statis literal kuota | idem | | | P1 | UMUM-BR-003 |
| UMUM-TC-025 | I | Turun paket | idem | | | P2 | UMUM-BR-002 |
| UMUM-TC-030 | S | Aksi sensitif tanpa alasan | Pembatalan dokumen tanpa `reason` | POST | 422; tidak ada audit/aksi | P1 | UMUM-BR-004 |
| UMUM-TC-031 | I | Idempotency key dipakai ulang | Kunci K dipakai payload A | Kirim kunci K payload B | 409 `IDEMPOTENCY_KEY_REUSED` | P1 | UMUM-BR-006 |
| UMUM-TC-032 | S | MIME palsu | PNG berisi skrip | Unggah | 422 / `rejected` | P1 | UMUM-BR-007 |
| UMUM-TC-033 | S | Tidak ada PATCH status | — | PATCH `{status}` ke resource | 404/405; uji route list | P2 | UMUM-BR-008 |
| UMUM-TC-034 | S | Rate limit publik | — | Lewati batas | 429 | P2 | UMUM-BR-009 |
| UMUM-TC-035 | U | Batas hari zona tenant | Tenant WIB; tenggat 2026-10-31 | Aksi 2026-10-31 23:30 WIB (16:30Z) dan 2026-11-01 00:10 WIB | Pertama diterima, kedua ditolak | P2 | UMUM-BR-012 |

## 9. Skenario UAT (contoh)

| ID | Pengguna | Skenario | Bukti penerimaan |
|---|---|---|---|
| UAT-01 | Petugas kemahasiswaan | Memverifikasi 20 pengajuan nyata termasuk 2 duplikat | Waktu rata-rata < 2 menit/pengajuan; duplikat tertangkap |
| UAT-02 | BAAK & prodi | Menjalankan satu periode yudisium percontohan satu prodi dari pembukaan sampai SK | Daftar lulusan & SK sesuai rapat manual paralel |
| UAT-03 | Panitia wisuda | Simulasi check-in 200 peserta dengan 4 perangkat | Tidak ada antrean > 10 detik/peserta; rekap cocok |
| UAT-04 | Petugas SKPI | Menerbitkan 10 SKPI dua bahasa | Pemeriksaan bahasa oleh penerjemah kampus tanpa koreksi istilah baku |
| UAT-05 | Pusat karier / LPM | Menjalankan tracer cohort nyata | Angka dashboard dapat direproduksi dari ekspor |
| UAT-06 | Pusat karier & mitra | Satu lowongan nyata sampai penerimaan | Pelamar melihat setiap perubahan status |
