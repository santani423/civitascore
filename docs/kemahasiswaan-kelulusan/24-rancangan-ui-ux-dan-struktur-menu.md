# 24 — Rancangan UI/UX dan Struktur Menu

> **Status:** rancangan · Disusun 2026-10-13 (commit `47043ca`) · Kembali ke [README](README.md)
> Hak akses: [03](03-aktor-role-dan-hak-akses.md) · Paket: [34](34-matriks-paket-dan-entitlement.md)

## 1. Kondisi Frontend (terverifikasi)

| Aplikasi | Lokasi | Ciri | Pemakaian untuk modul ini |
|---|---|---|---|
| Admin & portal klasik | `frontend/src/pages/*`, menu `constants/nav.ts`, rute `constants/routes.ts`, `RequirePermission`, `usePermission` | React 19 + Vite + Tailwind, label Bahasa Indonesia hardcoded, komponen `components/ui/`: `PageHeader`, `Card`, `StatCard`, `DataTable`, `ListPagination`, `Badge`, `Button`, `Input`, `Select`, `Textarea`, `Checkbox`, `Modal`, `Dropdown`, `Tooltip`, `Alert`, `EmptyState`, `Skeleton`, `Breadcrumb`, `Avatar` | **Dashboard internal kampus** (staf) dan portal mahasiswa klasik (`pages/portal/*`) |
| Civitas One | `frontend/src/one/*`, menu `one/components/layout/nav.ts`, i18n `one/i18n` | UI baru berorientasi mahasiswa, i18n, data sebagian tiruan (`useMockData`) | **Portal mahasiswa/alumni** baru: `/one/career` (sudah ada di menu), ditambah kelulusan & kemahasiswaan |
| Layout publik | `layouts/PublicExamLayout.tsx` | Halaman publik tanpa menu internal | Pola untuk survei, verifikasi dokumen, portal karier publik |
| Halaman statis terkait | `PortalGraduationPage`, `PortalScholarshipPage`, `PortalLetterRequestPage` (tanpa panggilan API), `one/pages/LibraryCareer.tsx` (data tiruan) | | Diganti/dihubungkan ke API |

Keputusan: halaman staf dibangun di aplikasi admin (`pages/`), dan halaman mahasiswa/alumni dibangun di Civitas One (`one/`) dengan tautan dari portal klasik selama masa transisi ([KK-Q-34](33-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-kk-q)).

## 2. Lima Permukaan yang Dipisahkan

| Permukaan | Pengguna | Layout | Data |
|---|---|---|---|
| Dashboard internal kampus | Staf, pimpinan, auditor | `DashboardLayout` | Data administratif sesuai permission & scope |
| Portal mahasiswa | Mahasiswa aktif/tingkat akhir | Civitas One | Data milik sendiri |
| Portal alumni | Alumni (akun yang sama, role `alumni`) | Civitas One (menu menyesuaikan role) | Data milik sendiri, survei, karier |
| Portal perusahaan | Perekrut/pemilik akun | Layout perusahaan (Baru), branding Karirlink | Data perusahaan, lowongan, pelamar lowongannya |
| Halaman publik | Siapa pun | Layout publik (pola `PublicExamLayout`) | Data minimal: verifikasi dokumen, survei bertoken, lowongan & artikel publik, publikasi tracer agregat |

Resource API untuk halaman publik terpisah dari resource internal (pola `PublicExamResource`, Ada). Data administratif tidak pernah dirender di permukaan publik.

## 3. Struktur Menu Dashboard Internal

Ditambahkan ke `TENANT_BUSINESS_NAV_ITEMS` (`constants/nav.ts`). Setiap item memiliki `permission` (Ada) dan atribut baru `feature` (feature key). Item tersembunyi bila permission tidak ada, dan tampil terkunci hanya untuk admin universitas bila fitur di luar paket ([34 §5](34-matriks-paket-dan-entitlement.md#5-perilaku-saat-tidak-tersedia-ux)).

```text
Kemahasiswaan (/kemahasiswaan)                         feature: student_affairs.core
├─ Dashboard Kemahasiswaan        /kemahasiswaan                     student_affairs_reports.read
├─ Master Kategori Kegiatan       /kemahasiswaan/kategori            activity_categories.read
├─ Pengajuan Aktivitas            /kemahasiswaan/pengajuan           activity_submissions.read
├─ Verifikasi Aktivitas           /kemahasiswaan/verifikasi          activity_submissions.approve
├─ Kegiatan Terdaftar             /kemahasiswaan/kegiatan            activity_events.read
├─ Poin Mahasiswa                 /kemahasiswaan/poin                student_points.read
├─ Banding Poin                   /kemahasiswaan/banding             point_appeals.read
├─ Pelanggaran dan Sanksi         /kemahasiswaan/pelanggaran         violation_reports.read | violation_cases.read
├─ Program Beasiswa               /kemahasiswaan/beasiswa            scholarships.read
├─ Penerima Beasiswa              /kemahasiswaan/beasiswa/penerima   scholarship_awards.read
├─ Surat Keterangan Aktif         /kemahasiswaan/surat-aktif         approval_requests.read | issued_documents.read
└─ Laporan Kemahasiswaan          /kemahasiswaan/laporan             student_affairs_reports.read

Yudisium (/yudisium)                                   feature: graduation.core
├─ Dashboard Yudisium             /yudisium
├─ Periode Yudisium               /yudisium/periode                  yudisium_periods.read
├─ Aturan Persyaratan             /yudisium/aturan                   graduation_requirement_sets.read
├─ Pemeriksaan Eligibility        /yudisium/eligibility              graduation_eligibility.read
├─ Calon Peserta                  /yudisium/calon-peserta            graduation_eligibility.read
├─ Peserta                        /yudisium/peserta                  yudisium_registrations.read
├─ Verifikasi Prodi               /yudisium/verifikasi-prodi         yudisium_registrations.approve (scope p)
├─ Verifikasi Fakultas            /yudisium/verifikasi-fakultas      yudisium_registrations.approve (scope f/u)
├─ Penetapan Kelulusan            /yudisium/rapat                    yudisium_meetings.read
├─ Predikat Kelulusan             /yudisium/predikat                 graduation_honor_rules.read
├─ SK Yudisium                    /yudisium/sk                       issued_documents.read
├─ Dokumen Kelulusan              /yudisium/dokumen                  issued_documents.read
└─ Riwayat Penerbitan             /yudisium/riwayat-penerbitan       issued_documents.read

Wisuda (/wisuda)                                       feature: graduation.core
├─ Periode Wisuda                 /wisuda/periode                    graduation_ceremonies.read
├─ Pendaftaran Peserta            /wisuda/pendaftaran                ceremony_registrations.read
├─ Validasi Peserta               /wisuda/validasi                   ceremony_registrations.approve
├─ Pengelompokan Sesi             /wisuda/sesi                       ceremony_sessions.read
├─ Konfirmasi Kehadiran           /wisuda/konfirmasi                 ceremony_registrations.read
├─ Check-in                       /wisuda/check-in                   ceremony_attendance.create
├─ Buku Wisuda                    /wisuda/buku                       graduation_books.read
└─ Laporan Wisuda                 /wisuda/laporan                    ceremony_registrations.export

SKPI (/skpi)                                           feature: skpi.core
├─ Dashboard SKPI                 /skpi
├─ Kategori Capaian               /skpi/kategori                     skpi_categories.read
├─ Capaian Mahasiswa              /skpi/capaian                      skpi_achievements.read
├─ Verifikasi Capaian             /skpi/verifikasi                   skpi_achievements.approve
├─ Template SKPI                  /skpi/template                     skpi_templates.read
├─ Penyusunan Dokumen             /skpi/dokumen                      skpi_documents.read
├─ Persetujuan                    /skpi/persetujuan                  skpi_documents.approve
├─ Penerbitan                     /skpi/penerbitan                   skpi_documents.publish
└─ Verifikasi Dokumen             /skpi/verifikasi-dokumen           issued_documents.read  (alat cek internal; halaman publik terpisah)

Tracer Study (/tracer)                                 feature: tracer.core
├─ Dashboard Tracer               /tracer                            tracer_reports.read
├─ Periode Tracer                 /tracer/periode                    tracer_periods.read
├─ Target Responden               /tracer/target                     tracer_targets.read
├─ Kuesioner                      /tracer/kuesioner                  tracer_questionnaires.read
├─ Template Kuesioner             /tracer/template                   tracer_questionnaires.read   feature: tracer.standard_template
├─ Distribusi                     /tracer/distribusi                 tracer_invitations.create
├─ Status Pengiriman              /tracer/pengiriman                 tracer_invitations.read
├─ Reminder                       /tracer/reminder                   tracer_invitations.create
├─ Status Responden               /tracer/responden                  tracer_invitations.read
├─ Hasil Survei                   /tracer/hasil                      tracer_responses.read
├─ Dashboard Outcome              /tracer/outcome                    tracer_reports.read
├─ Ekspor dan Laporan             /tracer/laporan                    tracer_reports.export
└─ Pengaturan Kuota               /tracer/kuota                      tracer_quota.read

Career Center (/karir)                                 feature: career.portal
├─ Dashboard Karier               /karir                             career_dashboard.read
├─ Profil Karier                  /karir/profil                      career_profiles.read
├─ Perusahaan Mitra               /karir/mitra                       career_partnerships.read
├─ Undangan Perusahaan            /karir/undangan                    career_company_invitations.read   feature: career.company_invitations
├─ Lowongan                       /karir/lowongan                    career_job_postings.read          feature: career.job_posting
├─ Moderasi Lowongan              /karir/moderasi                    career_job_postings.approve
├─ Lamaran                        /karir/lamaran                     career_applications.read
├─ Monitoring Rekrutmen           /karir/monitoring                  career_applications.read
├─ CRM Perusahaan                 /karir/crm                         career_crm.read                   feature: career.crm
├─ Rekomendasi Lulusan            /karir/rekomendasi                 career_recommendations.read       feature: career.graduate_recommendation
├─ Artikel dan Tips Karier        /karir/artikel                     career_articles.read              feature: career.articles
├─ Publikasi Outcome Tracer       /karir/publikasi-tracer            career_tracer_publications.read   feature: career.tracer_publication
└─ Pengaturan Portal              /karir/pengaturan                  career_portal_settings.read

Pengaturan (Ada) → + Dokumen Resmi   /pengaturan/dokumen             document_templates.read
```

Menu lama: `Keuangan › Beasiswa` (`/keuangan/beasiswa`, `scholarships.read`) tetap untuk skema potongan KEU. Program/penerima pindah ke Kemahasiswaan. `Alumni` (`/alumni`, Ada) tetap dan menampilkan status karier bersumber.

### 3.1 Menu portal mahasiswa/alumni (Civitas One)

Grup `campus` dan grup baru `graduation` di `one/components/layout/nav.ts`:

| Item | Rute | Tampil untuk |
|---|---|---|
| Aktivitas & Prestasi | `/one/affairs/activities` | Mahasiswa |
| Poin Saya | `/one/affairs/points` | Mahasiswa |
| Kedisiplinan | `/one/affairs/discipline` | Mahasiswa (hanya bila ada kasus/keputusan) |
| Beasiswa | `/one/scholarships` (Ada di menu, dihubungkan API) | Mahasiswa |
| Surat Keterangan | `/one/services/letters` | Mahasiswa |
| Kelulusan (status, persyaratan, pendaftaran yudisium) | `/one/graduation` | Mahasiswa dengan periode terbuka/calon; alumni (riwayat) |
| Wisuda | `/one/graduation/ceremony` | Lulusan eligible |
| SKPI | `/one/graduation/skpi` | Tingkat akhir & alumni |
| Dokumen Kelulusan | `/one/graduation/documents` | Alumni |
| Karier (lowongan, lamaran, profil, CV, consent) | `/one/career` (Ada di menu) | Mahasiswa & alumni |
| Survei | `/one/surveys` | Alumni dengan survei aktif |

### 3.2 Portal perusahaan

`/perusahaan`: Dashboard · Profil Perusahaan · Anggota · Lowongan · Pelamar · Kandidat Rekomendasi · Verifikasi · Syarat Penggunaan.

### 3.3 Halaman publik

`/verifikasi/:code` · `/survei/:token` · `/undangan-perusahaan/:token` · `{portal}/lowongan[/:id]` · `{portal}/artikel[/:slug]` · `{portal}/statistik-karier`.

## 4. Pola Halaman Standar

Seluruh halaman daftar staf mengikuti pola berikut kecuali dinyatakan lain.

| Aspek | Ketentuan |
|---|---|
| Header | `PageHeader` dengan judul, breadcrumb, dan tombol aksi utama (tampil hanya bila `usePermission` & `useFeature` (Baru) benar) |
| Ringkasan | Baris `StatCard` (maks. 4) dengan angka yang dapat diklik sebagai filter (pola `?filter[col]=a,b`, Ada) |
| Filter | Bar filter di atas tabel: pencarian (`search`), select status, unit (fakultas/prodi sesuai scope), periode. Filter tersimpan di URL query |
| Tabel | `DataTable` + `ListPagination` (server-side, default 20, maks. 100). Kolom dapat diurutkan sesuai `sortable` endpoint |
| Detail | Drawer kanan untuk detail ringan. Halaman detail untuk entitas dengan timeline (kasus, pendaftaran yudisium, dokumen) |
| Form | `react-hook-form` + `zod` (Ada di stack). Validasi klien mencerminkan validasi server, tetapi server adalah sumber kebenaran. Galat 422 dipetakan ke field |
| Bulk action | Checkbox per baris + aksi massal (verifikasi, ekspor, kirim undangan). Aksi massal menampilkan ringkasan sebelum eksekusi dan hasil per baris setelahnya |
| Konfirmasi sensitif | `Modal` konfirmasi berisi ringkasan dampak + input alasan (wajib bila server mewajibkan) + ketik ulang kata kunci untuk aksi tak terbalik (finalisasi kelulusan, pembatalan dokumen, publikasi buku) |
| Loading | `Skeleton` sesuai bentuk konten. Tombol aksi menampilkan spinner dan dinonaktifkan (cegah klik ganda; server juga idempoten) |
| Empty state | `EmptyState` dengan kalimat spesifik + aksi berikutnya (tabel §5) |
| Error state | `Alert` merah dengan pesan server + tombol coba lagi. 403 → "Anda tidak memiliki akses". 404 → "Data tidak ditemukan atau di luar unit Anda". 409 → pesan bisnis (mis. kuota) dengan aksi yang relevan |
| Status | `Badge` dengan varian konsisten: abu-abu (draf/belum), biru (diajukan/proses), kuning (perlu tindakan/kembali), hijau (disetujui/terbit/lulus), merah (ditolak/dibatalkan/gagal), ungu (dikecualikan/khusus) |
| Responsif | ≥ 1024 px: tabel penuh + drawer. 640–1023 px: kolom sekunder disembunyikan. < 640 px: kartu per baris, filter dalam sheet. Halaman check-in & survei dirancang mobile-first |
| Aksesibilitas | Label form eksplisit, fokus terlihat, kontras ≥ 4,5:1, status tidak hanya dengan warna (ikon + teks), navigasi papan ketik pada tabel |
| Zona waktu | Tanggal ditampilkan dalam zona tenant (`universities.timezone`) |

## 5. Spesifikasi Halaman

Kolom: **Halaman** · **Tujuan** · **Komponen utama** · **Filter** · **Kolom tabel** · **Aksi (permission)** · **Empty state** · **Batas paket**.

### 5.1 Kemahasiswaan

| Halaman | Tujuan | Komponen | Filter | Kolom tabel | Aksi | Empty state | Paket |
|---|---|---|---|---|---|---|---|
| Dashboard Kemahasiswaan | Ringkasan | StatCard (antrean verifikasi, melewati SLA, kasus aktif, beasiswa aktif), grafik pengajuan per kategori | Periode, fakultas | — | — | "Belum ada aktivitas pada periode ini." | Semua |
| Master Kategori | Kelola kategori & aturan | Tabel + drawer matriks aturan (peran × tingkat) | Status, jenis | Kode, nama, jenis, dipakai yudisium, aturan aktif, status | Buat/ubah/publikasi/nonaktif/hapus draf | "Buat kategori pertama untuk mulai menerima pengajuan." | Semua |
| Pengajuan Aktivitas | Semua pengajuan | Tabel | Status, kategori, prodi, flag, rentang tanggal | NIM, nama, kegiatan, kategori, tanggal, status, flag | Ekspor (`activity_submissions.export`) | "Belum ada pengajuan." | |
| Verifikasi Aktivitas | Memutus pengajuan | Split view: antrean kiri, pratinjau bukti + form keputusan kanan; tautan ke pengajuan terkait bila flag duplikat | Kategori, flag, lewat SLA | NIM, kegiatan, kategori, diajukan, umur | Setujui (dengan poin saran & alasan bila berubah), kembalikan, tolak; bulk setujui hanya untuk klaim kegiatan terdaftar tanpa flag | "Tidak ada pengajuan yang menunggu verifikasi." | |
| Kegiatan Terdaftar | Event & peserta | Tabel + wizard impor | Status, kategori | Nama, tanggal, peserta, mode, status | Buat, impor peserta, verifikasi (bukan oleh pembuat) | | |
| Poin Mahasiswa | Rekap poin | Tabel + detail (grafik per kategori, timeline ledger) | Prodi, angkatan, rentang poin, di bawah ambang yudisium | NIM, nama, prodi, total, dihitung yudisium | Koreksi (modal: selisih, alasan, lampiran) | | |
| Pelanggaran dan Sanksi | Tab Laporan/Kasus/Keputusan/Keberatan | Badge status berbeda tegas (laporan ≠ sanksi) | Status, tingkat, fakultas | (per tab) | Saring, buka kasus, jadwal pemeriksaan, temuan, draf keputusan | "Tidak ada laporan." | |
| Program Beasiswa | Program | Tabel + form bertahap (info, syarat, seleksi, keberlanjutan) | Status, sumber dana, TA | Kode, nama, sumber, kuota, terisi, periode, status | Buka/tutup | | |
| Penerima Beasiswa | Penerima | Tabel + timeline evaluasi | Program, status, term | NIM, nama, program, periode bantuan, status, evaluasi terakhir | Usul tangguh/henti/perpanjang | | |
| Surat Keterangan Aktif | Penerbitan | Tab status gabungan | Status, tanggal | No. surat, NIM, nama, keperluan, status, terbit | Terbitkan, unggah pindaian bertanda tangan, terbitkan ulang, ajukan pembatalan | | |
| Laporan Kemahasiswaan | Laporan KEM-RPT-* | Daftar laporan + filter + ekspor | | | Ekspor | | |

### 5.2 Yudisium & dokumen

| Halaman | Tujuan | Komponen | Filter | Kolom tabel | Aksi | Empty state |
|---|---|---|---|---|---|---|
| Dashboard Yudisium | Progres periode berjalan | Corong: calon → mendaftar → prodi → fakultas → siap → final; daftar tenggat | Periode, fakultas | — | — | "Belum ada periode aktif." |
| Periode Yudisium | Kelola periode | Form dengan pemilih unit berpohon & lini masa jadwal | Status, TA | Kode, nama, term, unit, jadwal, pendaftar, status | Buka/tutup/buka kembali/finalisasi (konfirmasi) | |
| Aturan Persyaratan | Set & versi | Daftar set (selector terbaca), editor versi (form parameter dari JSON Schema), diff, simulasi | Jenjang, fakultas, prodi, status | Set, cakupan, versi aktif, berlaku sejak | Buat versi, simulasi, publikasi (pemisahan peran) | "Belum ada aturan. Periode tidak dapat dibuka tanpa aturan." |
| Pemeriksaan Eligibility | Hasil & pemeriksaan | StatCard per hasil, tombol periksa massal (progres), badge stale | Hasil, prodi, stale, item gagal | NIM, nama, prodi, hasil, item gagal, umur hasil | Periksa ulang (satu/massal), selesaikan item manual, ajukan pengecualian | |
| Calon Peserta | Discovery | Tabel | Hasil, sudah/belum mendaftar | | Daftarkan (mode `staff_enroll`) | |
| Verifikasi Prodi/Fakultas | Antrean tahap | Split view: data diri terkonfirmasi, berkas, eligibility terkini (dipaksa segar), form keputusan dengan daftar isu | Status, prodi | NIM, nama, diajukan, eligibility, berkas | Setujui/kembalikan/tolak; bulk setujui untuk yang eligible & berkas lengkap | "Tidak ada pendaftaran menunggu verifikasi unit Anda." |
| Penetapan Kelulusan | Rapat & keputusan | Daftar peserta rapat dengan predikat terhitung, IPK, tanggal lulus, kolom keputusan; ringkasan pra-pengesahan | Unit | NIM, nama, IPK, predikat, keputusan | Ajukan pengesahan, finalisasi (ketik "FINALISASI") | |
| Predikat Kelulusan | Aturan predikat | Editor tier + uji coba | Jenjang | | Publikasi | |
| SK Yudisium / Dokumen Kelulusan | Penerbitan | Tabel dokumen per jenis, status tanda tangan, antrean terbit ulang, impor PIN | Jenis, status, periode | Nomor, pemegang, jenis, status, terbit | Terbitkan (massal), impor PIN, terbitkan ulang, ajukan pembatalan | |
| Riwayat Penerbitan | Register | Timeline event | Jenis, tanggal, aktor | | Ekspor register | |

### 5.3 Wisuda, SKPI, Tracer, Karier

Spesifikasi inti halaman ada di dokumen fitur ([14 §14](14-rancangan-wisuda-dan-buku-wisuda.md#14-rancangan-halaman-uiux), [15 §14](15-rancangan-skpi.md#14-rancangan-halaman-uiux), [16 §14](16-rancangan-tracer-study.md#14-rancangan-halaman-uiux), [17 §14](17-rancangan-kuesioner-dan-survei.md#14-rancangan-halaman-uiux), [18 §14](18-rancangan-distribusi-dan-reminder-tracer.md#14-rancangan-halaman-uiux), [19 §14](19-rancangan-dashboard-dan-laporan-tracer.md#14-rancangan-halaman-uiux), [20 §14](20-rancangan-career-center-karirlink.md#14-rancangan-halaman-uiux)–[23 §14](23-rancangan-rekomendasi-lulusan.md#14-rancangan-halaman-uiux)) dan mengikuti pola §4. Ketentuan khusus:

| Halaman | Ketentuan khusus |
|---|---|
| Check-in | Layar penuh mobile, kamera QR, umpan balik besar (hijau "Selamat datang", kuning "Sudah check-in 09:12", merah "Sesi berbeda: Sesi 2"), penghitung hadir/kapasitas, mode luring dengan indikator antrean sinkron |
| Pengelompokan Sesi | Papan per sesi dengan bar kapasitas. Seret-lepas menampilkan konfirmasi dan menolak bila penuh |
| Buku Wisuda | Pratinjau halaman, panel diff antaredisi, daftar entri `held` dengan alasan |
| Verifikasi Capaian SKPI | Teks ID dan EN berdampingan, saran glosarium inline, tanda konsistensi istilah |
| Builder Kuesioner | Tiga panel. Versi terpublikasi baca-saja dengan banner |
| Distribusi Tracer | Kartu kuota selalu terlihat. Kampanye melebihi kuota menampilkan pilihan kirim sebagian |
| Status Responden | Corong + tabel. Kolom identitas hanya bila izin identitas, selain itu ID samaran |
| Dashboard Outcome | Setiap angka berlabel `n` dan definisi (tooltip). "Tidak menjawab" berwarna netral |
| Pengaturan Portal | Pratinjau langsung tema. Pada paket tanpa whitelabel, form tampil dengan banner terkunci |
| Moderasi Lowongan | Daftar flag otomatis disorot, pratinjau sebagaimana dilihat pelamar |
| Rekomendasi Lulusan | Hasil pencarian tanpa kontak. Status consent per kandidat terlihat jelas |

### 5.4 Portal mahasiswa/alumni

| Halaman | Inti | Empty state |
|---|---|---|
| Aktivitas & Prestasi | Daftar pengajuan + wizard 3 langkah. Catatan petugas tampil pada status kembali/tolak | "Belum ada aktivitas. Ajukan kegiatan pertama Anda." |
| Poin Saya | Total, per kategori, progres terhadap syarat yudisium prodi (bila ada aturan), riwayat | |
| Kelulusan | Checklist persyaratan dengan unit penanggung jawab & langkah penyelesaian, tombol periksa ulang (dibatasi), pendaftaran yudisium dengan isian per tahap | "Belum ada periode yudisium terbuka untuk program studi Anda." |
| Wisuda | Pendaftaran, status biaya (dari KEU, tanpa nominal di halaman ini; tautan ke tagihan), konfirmasi, kartu peserta | |
| SKPI Saya | Capaian per kategori (sumber & status), pilih tampil, isian terjemahan, pratinjau, pernyataan, unduh | |
| Dokumen Kelulusan | Daftar dokumen terbit + kode verifikasi + status | |
| Karier | Lowongan (filter), detail & lamar, lamaran saya (timeline status), profil & CV, permintaan berbagi data, log akses CV | |
| Survei | Survei aktif (tanpa token, identitas dari sesi) | "Tidak ada survei yang perlu diisi." |

### 5.5 Halaman publik

| Halaman | Isi | Larangan |
|---|---|---|
| Verifikasi dokumen | Status besar, data minimal, cocokkan berkas | Tanpa NIM/nilai/berkas, `noindex` |
| Survei | Branding kampus, satu bagian per layar, kemajuan, autosave, pernyataan privasi singkat, tautan berhenti berlangganan | Tanpa menu internal, tanpa pelacak pihak ketiga |
| Portal karier publik | Lowongan, artikel publik, statistik terpublikasi | Tanpa data pelamar |
| Undangan perusahaan | Info kampus pengundang + registrasi/login | — |

## 6. Hak Akses Tombol

Tombol aksi dirender berdasarkan `usePermission` (Ada) + `useFeature` (Baru) + **kemampuan dari server**: setiap resource detail mengembalikan `can: { approve: bool, reject: bool, … }` yang dihitung policy backend (mencakup scope, status, dan pemisahan tugas). Frontend tidak menebak aturan bisnis. Pola serupa sudah dipakai ApprovalWorkflow: `ApprovalRequestResource` mengembalikan `can_act` dan `can_resubmit` (Ada, diuji `ApprovalRequestCanActTest`).

## 7. Acceptance Criteria UI

| ID | Kriteria |
|---|---|
| UMUM-AC-08 | Setiap item menu baru memiliki `permission`, dan item berfitur memiliki `feature`. Pengguna tanpa izin tidak melihat item (uji snapshot menu per role) |
| UMUM-AC-09 | Halaman daftar memiliki state loading, empty, dan error yang teruji (uji komponen) |
| UMUM-AC-10 | Halaman publik tidak memuat bundle menu internal (uji ukuran/route split) |
| UMUM-AC-11 | Halaman check-in dan survei lolos audit aksesibilitas otomatis tanpa pelanggaran kritis pada lebar 375 px |
