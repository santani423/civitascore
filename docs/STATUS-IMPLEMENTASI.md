# Status Implementasi Civitas One — Yang Sudah dan Belum Dibuat

> **Per:** 17 Oktober 2026, kode `main` @ `70e2f52`.
> **Cara menyusun:** seluruh dokumen di `docs/` (cetak biru, rancangan per modul, catatan implementasi, manual book) dicocokkan dengan kode: rute API di `app/Modules/*/Routes`, model & migrasi, halaman di `frontend/src` (admin, `pmb`, `keu`, portal, `landing`, `one`), dan `mobile/lib`. Bila dokumen dan kode berbeda, **kode yang dipakai**. Beberapa dokumen lama (mis. `FRONTEND-IMPLEMENTASI.md`, `BLACK-BOX-TESTING.md`, `RANCANGAN-AKUN-*.md` bertanggal Agustus–September) sudah tertinggal dari kode.

## Legenda

| Tanda | Arti |
|---|---|
| ✅ | **Sudah** — API + halaman web berjalan |
| 🟡 | **Sebagian** — ada, tetapi ada bagian yang belum (dijelaskan di baris) |
| ⚙️ | **Backend saja** — API/aturan ada, halaman web/mobile belum ada |
| 🧩 | **UI tanpa backend** — halaman berisi data statis/contoh, tidak memanggil API |
| ❌ | **Belum dibuat** — sumber rancangannya disebut di kolom terakhir |

## Ringkasan

| Area | Kondisi |
|---|---|
| Modul backend | 20 modul di `app/Modules` |
| Modul paling lengkap | PMB (`Admission`, 331 rute, 74 tabel), Keuangan (`Finance`/KEU, 157 rute), Akademik (143 rute), SDM (`HumanResource`, 108 rute) |
| Modul yang masih baca-saja | Beasiswa, Skripsi, Magang/MBKM, Perpustakaan, Alumni (masing-masing 2–3 rute `GET`) |
| Modul yang belum ada sama sekali | OBE, EDOM, Semester Pendek, Kemahasiswaan & Kelulusan (poin, yudisium, wisuda, SKPI, tracer, Karirlink), Akreditasi, PDDikti, Penelitian/PkM, Sarpras, dan modul jangka panjang lain |
| Mobile (Flutter) | Hanya login, sesi keamanan, dashboard, dan konfigurasi ujian dosen |
| Tes otomatis | 415 tes; 398 lulus, **17 gagal** (sudah gagal sebelum KEU — `makeParticipationClassSectionFixture()` tidak terdefinisi di `ExamViolationAndScoringTest` & `PublicExamAccessTest`) |

---

## 1. Fondasi Platform

### Sudah dibuat

| Fitur | Status | Catatan |
|---|---|---|
| Login, logout, logout semua perangkat, `/me` | ✅ | `Auth` |
| Lupa & atur ulang kata sandi, ubah kata sandi | ✅ | |
| Daftar sesi & perangkat aktif, cabut sesi | ✅ | Web `SecuritySessionsPage`, mobile `security_sessions_screen` |
| Pembatasan percobaan login | ✅ | |
| Peran (CRUD), izin (CRUD), sinkron izin ke peran | ✅ | `RolesPage`, `PermissionsPage` |
| Tetapkan/cabut peran pengguna | ✅ | `UserRolesPage` |
| Gating menu & tombol berdasarkan izin | ✅ | `usePermission` |
| Multi-tenant (isolasi `university_id`, `TenantScoped`, resolusi tenant) | ✅ | Ada tes isolasi |
| Super Admin: CRUD universitas, aktivasi/suspend, statistik platform | ✅ | `UniversitiesPage`, `PlatformDashboardPage` |
| Tenant Switcher + banner mode tenant | ✅ | |
| Support Session (mulai/akhiri/riwayat, wajib alasan) | ✅ | `SupportSessionsPage` |
| Profil & pengaturan universitas (`/tenant/*`) | ⚙️ | Hanya API |
| Alur persetujuan: workflow (buat/hapus), setujui/tolak/delegasi/kembalikan, ajukan ulang | ✅ | `ApprovalWorkflowsPage`, `ApprovalRequestsPage` |
| Larangan menyetujui pengajuan sendiri (kontrak `RequiresDistinctApprover`) | ✅ | Dipakai PMB |
| Log audit (baca, filter) | ✅ | `AuditLogPage` |
| Unggah/unduh/pratinjau berkas, checksum, kontrol akses | ✅ | `FileManagement` |
| Templat notifikasi, kanal, preferensi, notifikasi dalam aplikasi, hitung belum dibaca | ✅ | `NotificationsPage` |
| Kirim notifikasi via **database & email** | ✅ | |
| Pengaturan sistem, feature flag global + override per universitas | ✅ | `SystemSettingsPage`, `FeatureFlagsPage` |
| Dashboard umum: 7 kartu + 8 grafik + filter semester/fakultas/prodi | ✅ | `DashboardStatsService` |
| Laporan umum (mahasiswa, akademik, keuangan, SDM) + ekspor CSV | ✅ | `ReportService` |
| Shell aplikasi: responsif, mode terang/gelap | ✅ | |

### Belum dibuat

| Fitur | Status | Sumber rancangan |
|---|---|---|
| Autentikasi dua faktor (2FA/MFA) | ❌ | `RANCANGAN-APLIKASI.md` §10, `RANCANGAN-SUPER-ADMIN.md` §6.3 |
| Verifikasi email akun staf | ❌ | `RANCANGAN-APLIKASI.md` §10 |
| **Buat/ubah/nonaktifkan akun pengguna umum** (hanya ada `GET /users`; akun dibuat lewat dosen, konversi PMB, seeder) | ❌ | Implisit di §3 cetak biru |
| Kirim notifikasi **push, WhatsApp, SMS** dari modul Notification inti (enum ada, `isSupported = false`) | ❌ | §9 cetak biru |
| Pencarian global (kotak cari di header hanya menyimpan teks, tidak mencari apa pun) | 🧩 | §15 cetak biru |
| Cakupan data Dekan/Kaprodi per fakultas/prodi (kolom `scope_type/scope_id` ada, belum dipakai di query Akademik & Keuangan; PMB sudah) | 🟡 | `academic-learning/11` |
| Penegakan hak paket langganan per fitur (`EnsureFeatureEntitled`, `plan_feature_entitlements`) | ❌ | `academic-learning/11`, `13` |
| Super Admin: manajemen domain, katalog modul & toggle per universitas, paket & status langganan (model ada, tanpa API/UI) | ⚙️/❌ | `RANCANGAN-SUPER-ADMIN.md` §3, §5 |
| Invoice & pembayaran langganan, notifikasi billing, suspend otomatis saat overdue | ❌ | `RANCANGAN-SUPER-ADMIN.md` §5.3 |
| Pencatatan otomatis `actions_performed` dan auto-expire Support Session | ❌ | `RANCANGAN-SUPER-ADMIN.md` §10 |
| Sesi/perangkat lintas platform, IP allowlist per universitas, deteksi anomali | ❌ | `RANCANGAN-SUPER-ADMIN.md` §6 |
| Master data referensi (jenis institusi, akreditasi), mode maintenance via UI, ekspor laporan agregat platform | ❌ | `RANCANGAN-SUPER-ADMIN.md` §7 |
| Fallback templat notifikasi global lintas tenant | ❌ | `MULTI-TENANT-ARCHITECTURE.md` §9 |
| Penyimpanan berkas per folder tenant (`universities/{id}/…`) | ❌ | `MULTI-TENANT-ARCHITECTURE.md` §9 |
| Pemindai antivirus nyata (hanya `NullVirusScanner`) | ⚙️ | PMB/KK |
| Ekspor laporan XLSX/PDF, drill-down, perbandingan antartahun | ❌ | §4.38 cetak biru |
| Impor data umum (Excel/CSV dengan pratinjau & laporan galat) | ❌ | §16 cetak biru |
| Approver berbasis jabatan/atasan langsung, bulk approve | ❌ | `RANCANGAN-AKUN-SDM.md` Fase 3 |

---

## 2. Penerimaan Mahasiswa Baru (PMB)

Rancangan: `RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md`, `docs/pmb/01–16`. Kode: `app/Modules/Admission`, `frontend/src/pmb`.

### Sudah dibuat

| Fitur | Status | Catatan |
|---|---|---|
| Website PMB publik: beranda, prodi, jalur, biaya, jadwal, panduan, FAQ, berita, cek status, info daftar ulang, kontak, kebijakan privasi, verifikasi LoA | ✅ | `pmb/pages/public` |
| CMS konten & profil prodi (editor teks kaya) | ✅ | |
| Akun pendaftar terpisah: daftar, verifikasi email/OTP, masuk, lupa sandi, pemulihan akun, CAPTCHA Turnstile | ✅ | CAPTCHA aktif setelah kunci dipasang |
| Konfigurasi: periode, gelombang, gelombang-jalur, jalur, sistem kuliah, jenis dokumen, kuota (+salin periode), persyaratan, komponen biaya, publikasi/tutup/arsip | ✅ | |
| Jalur: reguler, prestasi, KIP/afirmasi (field KIP), pindahan (data PT asal), pascasarjana, RPL, beasiswa, kerja sama, internasional, ODS (`is_ods`) | ✅ | Pendaftaran & data; lihat "belum" untuk RPL |
| Template formulir berversi, field tambahan | ✅ | Editor berbasis JSON |
| Skema seleksi berversi, simulasi, kunci | ✅ | |
| Formulir pendaftaran per jalur, pilihan prodi, nilai rapor, prestasi, data wali, pernyataan | ✅ | Simpan per langkah |
| Unggah dokumen, verifikasi, perbaikan berbatas waktu | ✅ | |
| Tagihan, voucher, loket kasir, transfer manual + verifikasi bukti, rekonsiliasi, refund berpersetujuan | ✅ | |
| Pembayaran online Xendit + webhook + cek ulang | 🟡 | Diuji dengan respons tiruan; belum diuji dengan akun nyata |
| Verifikasi administrasi: antrean, klaim, ambil berikutnya, bulk, galeri pas foto | ✅ | |
| Ujian seleksi: lokasi/ruang, jadwal, sesi, penempatan, petugas, kartu peserta, check-in QR, jadwal ulang; mode luring/daring/CBT/wawancara/portofolio | ✅ | |
| CBT PMB (bank soal, pengerjaan di portal) | ✅ | |
| Penilaian (numerik, lulus/tidak, rubrik), impor nilai, kunci nilai | ✅ | |
| Run seleksi snapshot + hash, ranking, tie-break, alokasi kuota, cadangan | ✅ | |
| Pengesahan dua langkah via ApprovalWorkflow, publikasi terjadwal, LoA PDF + QR | ✅ | |
| Koreksi hasil & pengajuan perubahan | ✅ | |
| Daftar ulang: tawaran, data lanjutan, dokumen final, biaya awal + cicilan, verifikasi, NIM otomatis, konversi ke mahasiswa + akun, posting ke Keuangan | ✅ | |
| Komunikasi: pesan pendaftar, broadcast, templat, log | ✅ | |
| Dashboard PMB, laporan & ekspor beralasan, audit, log akses data sensitif | ✅ | |
| Enkripsi data sensitif, consent, unduh data pribadi, nonaktif akun, retensi | ✅ | |
| Pendaftaran loket, deteksi akun ganda | ✅ | |
| Endpoint penerima lead CRM, webhook CBT eksternal (HMAC) | ⚙️ | Menunggu spesifikasi penyedia |
| Penjadwal `pmb:tick` & `pmb:daily`, `pmb:install` | ✅ | |
| Manual book (penyelenggara & calon mahasiswa) + 2 video tutorial | ✅ | |

### Belum dibuat / bug diketahui

| Item | Status | Sumber |
|---|---|---|
| Simpan otomatis formulir & mode luring | ❌ | `pmb/12` POR-04 |
| Bukti pendaftaran PDF setelah kirim formulir | ❌ | `manual-book/pmb/60` #2 |
| Unggah drag & drop, kamera, kompresi sisi klien | ❌ | #3 |
| Polling status pembayaran otomatis | ❌ | #4 |
| Formulir "beri tahu saya" saat belum ada periode | ❌ | #7 |
| Aturan tampil bersyarat di portal (server sudah, UI menampilkan semua); field tahap daftar ulang di halaman daftar ulang | 🟡 | #8 |
| Pintasan keyboard verifikator, penampil dokumen dalam halaman | ❌ | #10–11 |
| Filter tersimpan & pilih kolom di tabel | ❌ | #12 |
| Dashboard terpisah per peran; **perbandingan antarperiode di layar** (API sudah ada) | 🟡 | #13–14 |
| Kuota matriks + impor/ekspor XLSX; mode "batas per gelombang" tanpa isian | 🟡 | #15 |
| Editor template & skema visual (drag, pembuat kondisi, pratinjau mobile/desktop) | ❌ | #16–17 |
| Check-in dengan kamera tablet & foto peserta | ❌ | #18 |
| Strategi penempatan per prodi/aksesibilitas di layar (API sudah) | ⚙️ | #20 |
| Tombol "kembalikan" di persetujuan PMB | ❌ | #21 |
| Brosur & templat hasil belum dipakai di website/portal | 🟡 | #24 |
| Alur persetujuan wali untuk pendaftar < 18 tahun | ❌ | `manual-book/pmb/61` K13 |
| Modul RPL (asesor, rekognisi MK); PMB hanya meneruskan data | ❌ | FR-SPC-05 |
| Sinkron dua arah CRM/Maukuliah; TTE LoA tersertifikasi; SMS/WA nyata | ❌ | FR-INT-03, FR-INT-06 |
| Pengiriman ke PDDikti/Neo Feeder (PMB hanya laporan kelengkapan) | ❌ | FR-INT-06 |
| **Bug B1**: notifikasi dalam aplikasi pendaftar gagal disimpan (`PmbMessageNotification` belum memakai ULID) | ❌ masih ada | `manual-book/pmb/61` §2 |
| **Bug B9**: placeholder cek status `PMB27-G1-REG-00001` ≠ format asli `PMB2027-REG-000001` | ❌ masih ada | §2 |
| Bug B2–B8, B10–B11 (tanggal lahir kosong, kunci CBT tidak tampil di bank soal, ID refund, label kode mentah, dialog minta ID teknis, dll.) | belum diverifikasi ulang | §2 |
| `composer.lock` memuat `openspout` yang butuh PHP ≥ 8.4, server 8.3 | ❌ | `pmb/16` §5 |

---

## 3. Akademik (SIAKAD)

Rancangan: `RANCANGAN-APLIKASI.md` §4.1–4.22, `RANCANGAN-AKUN-AKADEMIK.md`, `academic-learning/01-siakad.md`. Kode: `app/Modules/Academic`.

### Sudah dibuat

| Fitur | Status | Catatan |
|---|---|---|
| Daftar/detail mahasiswa + filter; **tambah mahasiswa** | ✅ | Belum ada ubah/hapus |
| Riwayat status mahasiswa otomatis | ✅ | `StudentObserver` |
| Akun mahasiswa terhubung (`students.user_id`) | ✅ | |
| Dosen: tambah/ubah/hapus, buat akun login, reset sandi, aktif/nonaktif; profil dosen mandiri | ✅ | |
| Pegawai (data induk `employees`) daftar/detail | ✅ | Pengelolaan lewat SDM |
| Fakultas, prodi, kurikulum, mata kuliah: daftar/detail | ✅ | Baca saja |
| Kelas: daftar/detail; **tetapkan dosen pengampu & jadwal** (hari, jam, ruang teks) dengan cek bentrok dosen/ruang | ✅ | |
| Prasyarat mata kuliah (+nilai minimum): lihat & ubah | ⚙️ | Tidak ada halaman web |
| Periode KRS per semester | ⚙️ | Tidak ada halaman web |
| Penetapan dosen wali | ⚙️ | Tidak ada halaman web |
| Kalender akademik: CRUD | ⚙️ | Admin tanpa halaman; tampil di portal ✅ |
| KRS mandiri mahasiswa: penawaran, cek status aktif, prasyarat, batas SKS (IPS), bentrok jadwal, kapasitas (lock), ajukan, batal, riwayat, cetak PDF | ✅ | Portal |
| KRS oleh admin: daftarkan & drop | ✅ | `KrsPage` |
| Persetujuan/penolakan KRS oleh dosen wali + notifikasi | ⚙️ | `krsApprovalService` ada; rute `/akademik/persetujuan-krs` tanpa halaman |
| Presensi batch per pertemuan (4 status) | ✅ | `AttendancePage` |
| Input nilai, konversi huruf otomatis/override | ✅ | `GradesPage` |
| IPS/IPK, nilai terbaik MK ulang, transkrip, KHS PDF | ✅ | |
| Pengajuan mahasiswa: cuti, aktif kembali, perubahan data, surat keterangan (PDF), lainnya — via ApprovalWorkflow | ✅ | Ditinjau lewat menu Persetujuan; halaman `/akademik/pengajuan-mahasiswa` belum ada |
| Cekal akademik (`academic_blocks`): ditegakkan pada KRS & ujian (sumber keuangan), dispensasi, riwayat | ✅ | UI di menu Keuangan → Cekal |
| Kepemilikan kelas oleh dosen (flag `academic.lecturer_ownership`) | 🟡 | Ada, aktif per tenant via flag |
| Flag `academic.short_term` (persiapan semester pendek) | 🟡 | Flag saja |

### Belum dibuat

| Fitur | Status | Sumber |
|---|---|---|
| Ubah/hapus mahasiswa; data orang tua/wali, NIK, jalur masuk lengkap | ❌ | `01-siakad` A.1 |
| Impor massal mahasiswa/dosen/MK; deduplikasi | ❌ | A.1 |
| CRUD fakultas, prodi, kurikulum, mata kuliah, **buka kelas baru** (sekarang lewat seeder/migrasi data) | ❌ | A.1–A.3 |
| Kurikulum berversi per angkatan (`curriculum_courses`, `curriculum_cohorts`), paket MK, ekuivalensi, konversi kurikulum | ❌ | A.2 |
| Periode akademik yang mengikat (`academic_term_periods`: add/drop, UTS, UAS, input nilai, EDOM) | ❌ | A.1 |
| Tabel ruang (`rooms`), utilisasi ruang, team teaching (`class_section_lecturers`), jenis jadwal, kelas kelompok, publikasi jadwal, notifikasi perubahan, saran slot, salin dari semester lalu, drag-and-drop jadwal | ❌ | A.3, §4.9 |
| KRS: status `revision_requested`/`locked`, periode add/drop, paket KRS semester awal, riwayat persetujuan terstruktur | ❌ | A.4 |
| Batas SKS & skala nilai sebagai pengaturan tenant (sekarang hardcoded `SKS_LIMIT_TIERS`, `LetterGrade`) | ❌ | A.4, A.6 |
| Jurnal mengajar/BAP (`lecture_sessions`), koreksi presensi berotorisasi, batas minimal kehadiran ujian | ❌ | A.5 |
| Presensi QR, PIN, GPS/geofencing, NFC, wajah, mesin absensi | ❌ | §4.14, `02-edlink` B.3 |
| Komponen & bobot nilai (`assessment_components`), publikasi nilai, revisi nilai berjejak, pemutihan, nilai tertunda | ❌ | A.6 |
| Perwalian: catatan konsultasi, daftar bimbingan, mahasiswa berisiko, janji temu | ❌ | A.7 |
| Mesin cekal generik (sumber akademik, kehadiran, perpustakaan, kemahasiswaan), simulasi aturan | ❌ | A.8 |
| Transfer mahasiswa & konversi nilai, double degree | ❌ | A.9 |
| Tugas akhir lengkap (proposal, multi-pembimbing, seminar, sidang, penguji) | ❌ | A.9.3, §4.20 |
| Pengumuman massal email/SMS, akses orang tua/wali | ❌ | A.9 |
| MBKM terstruktur (8 aktivitas, mitra, seleksi, logbook, konversi per MK, IKU) | ❌ | A.10 |
| **Semester Pendek** (seluruh paket) | ❌ | `docs/semester-pendek/` |
| Mutasi mahasiswa, KRS otomatis program tertentu | ❌ | §4.3, §4.10 |

---

## 4. Pembelajaran Daring (LMS)

| Fitur | Status | Catatan / sumber |
|---|---|---|
| Materi per pertemuan (berkas/tautan/video/teks, publikasi) | ⚙️ dosen / ✅ mahasiswa | API dosen ada; halaman `/akademik/kelas-saya` belum ada; portal mahasiswa sudah |
| Tugas: buat, batas waktu, pengumpulan, terlambat, nilai & umpan balik | ⚙️ dosen / ✅ mahasiswa | sama |
| Kelas LMS akademik & umum, keanggotaan, duplikasi bahan ajar | ❌ | `02-edlink-lms` B.1 |
| Versi materi, progres belajar, kuis LMS, forum, pengumuman kelas, riwayat aktivitas | ❌ | B.2 |
| Perkuliahan hybrid, presensi QR, dashboard keterlibatan, engagement score, lecture performance score, gamifikasi | ❌ | B.3–B.5 |
| Anotasi PDF, video interaktif, presensi AI, presensi pegawai, SPADA/MOOC, kuota storage, whitelabel & APK | ❌ | B.5–B.6 |
| Pemeriksaan plagiarisme, tugas kelompok | ❌ | §4.12 cetak biru |

---

## 5. Ujian Daring (CBT)

| Fitur | Status | Catatan / sumber |
|---|---|---|
| Bank soal per MK (CRUD), terapkan ke ujian | ✅ | `QuestionBankPage` |
| Ujian: CRUD, soal, publikasi + validasi, mode semua/acak/manual, acak soal & opsi, durasi, jadwal, navigasi mundur, percobaan maks, bobot | ✅ | `ExamsPage`, `ExamDetailPage` |
| Rentang nilai per ujian | ✅ | |
| Pengerjaan mahasiswa di portal: timer server, simpan per soal, submit, hasil PDF | ✅ | `PortalExamTakingPage` |
| Ujian publik via tautan + NIM | ✅ | `/exam/:accessToken` |
| Deteksi pelanggaran (pindah tab, blur, keluar fullscreen, salin/tempel, klik kanan) + pemantauan | ✅ | Penalti 1 poin otomatis per pelanggaran |
| Rekap, daftar peserta, ekspor CSV/PDF, unduh soal PDF | ✅ | `ExamRecapPage` |
| Konfigurasi ujian di Flutter (wizard, bank soal, pratinjau) | ✅ | |
| Layar pengerjaan ujian di Flutter | ❌ | `EXAM_RANDOMIZATION.md` §10 |
| Wadah bank soal, status review, tingkat kesulitan, topik | ❌ | `03-cbt` C.1 |
| Jenis soal selain pilihan ganda (esai dll.), penilaian manual, rilis hasil | ❌ | C.1–C.2 |
| Peserta eksplisit (`exam_participants`), panitia & pengawas ujian, 8 status ujian, job penutup attempt kedaluwarsa | ❌ | C.2 |
| Penalti pelanggaran sebagai pengaturan | ❌ | C.4 |
| CBT AI (draf soal), proctoring kamera | ❌ | C.3–C.4 |
| Kirim nilai ujian ke komponen nilai SIAKAD | ❌ | C.5 |
| Jadwal UTS/UAS terpusat, kartu ujian, ruang & pengawas, berita acara, ujian susulan (kelas reguler) | ❌ | §4.18 cetak biru |

---

## 6. Portal Mahasiswa

| Halaman | Status | Catatan |
|---|---|---|
| Dashboard, profil (ubah sebagian), notifikasi, pengumuman (tanda dibaca) | ✅ | |
| KRS, jadwal, KHS, nilai, transkrip, presensi, kalender, ringkasan & riwayat akademik | ✅ | |
| Mata kuliah, materi, tugas & pengumpulan | ✅ | |
| Ujian, pengerjaan, hasil | ✅ | |
| Dokumen PDF (KRS, KHS, transkrip) | ✅ | |
| Pengajuan (cuti, aktif kembali, perubahan data, surat) | ✅ | `PortalRequestsPage` |
| Keuangan (tagihan, bayar, bukti transfer, kuitansi, deposit, cicilan, cekal) | ✅ | `keu/pages/portal` |
| Pengaturan portal | 🧩 | Dirutekan, data statis |
| Bimbingan akademik, bimbingan skripsi, beasiswa, evaluasi dosen (EDOM), wisuda, kuis, form cuti lama, form surat lama | 🧩 | File halaman ada (`pages/portal/*`) tetapi **tidak dirutekan** dan berisi data contoh |
| Portal orang tua/wali | ❌ | §3.13 cetak biru, KEU F-16 |

---

## 7. Layanan Dosen

| Fitur | Status |
|---|---|
| Nilai, presensi, ujian & bank soal, rekap ujian, profil dosen | ✅ |
| Kelas Saya (materi, tugas, nilai tugas) | ⚙️ API ada, halaman belum |
| Persetujuan KRS (dosen wali) | ⚙️ API ada, halaman belum |
| Jurnal mengajar, catatan bimbingan, lihat hasil EDOM, BKD, riwayat mengajar lengkap | ❌ |
| Bimbingan skripsi, jadwal sidang | ❌ |

---

## 8. Keuangan Mahasiswa (KEU)

Rancangan: `RANCANGAN_SISTEM_ADMINISTRASI_KEUANGAN_MAHASISWA_KEU.md`, `docs/keuangan/`. Catatan: `docs/keu/catatan-implementasi.md`. Kode: `app/Modules/Finance`, `frontend/src/keu` (24 halaman admin + portal).

### Sudah dibuat

| Fitur | Status |
|---|---|
| Komponen biaya, tabel tarif berversi (draf→persetujuan→terbit, imutabel), resolusi paling spesifik, konflik tarif, pratinjau | ✅ |
| Penerbitan massal (simulasi → persetujuan → posting), tagihan manual & draf, tagihan SKS otomatis dari KRS | ✅ |
| Penyesuaian tagihan (naik/turun/bebas/bebas denda/void) dengan maker-checker | ✅ |
| Loket kasir & sesi kas, setoran & verifikasi, kuitansi PDF | ✅ |
| Transfer manual + kode unik + verifikasi bukti | ✅ |
| Pembayaran sebagian, alokasi termin, kelebihan → deposit, pembalikan | ✅ |
| Deposit & refund (hold/approve/complete, rekening terenkripsi) | ✅ |
| Rekonsiliasi (mutasi CSV, settlement, setoran kas, tutup batch) | ✅ |
| Beasiswa/potongan, voucher | ✅ |
| Cicilan standar & khusus | ✅ |
| Denda (aturan berversi, harian, plafon, kristalisasi) | ✅ |
| Piutang, aging, tunggakan, catatan penagihan, pengingat | ✅ |
| Cekal keuangan ↔ SIAKAD (KRS & ujian), dispensasi, cabut otomatis | ✅ |
| Dashboard + 13 laporan + ekspor CSV beralasan | ✅ |
| Portal keuangan mahasiswa | ✅ |
| Pengaturan `keu.*`, peran Kepala Keuangan & Kasir, `keu:install/daily/recheck-payments` | ✅ |

### Belum dibuat

| Fitur | Status | Sumber |
|---|---|---|
| Pembayaran online aktif nyata (Xendit belum diuji dengan akun; tanpa kredensial = nonaktif) | 🟡 | `keu/catatan-implementasi` §9 |
| Bank host-to-host / VA langsung, biller, bayar beberapa tagihan sekaligus | ❌ | F-09 |
| Refund otomatis via API penyedia | ❌ | FR-RFD-06 |
| Format mutasi per bank, tarik settlement otomatis | ❌ | F-12 |
| Tarif per mata kuliah; tarif individual; salin & naikkan; impor/ekspor Excel tarif; tingkat semester memperhitungkan cuti | ❌ | F-02 |
| UKT: instrumen skoring, pengajuan data ekonomi, SK penetapan, deteksi anomali | 🟡 | F-04 |
| Klaim ke penyandang dana beasiswa, write-off piutang, evaluasi syarat beasiswa per term | ❌ | F-05 |
| Cekal untuk nilai, transkrip, yudisium, wisuda; surat bebas tanggungan PDF + QR; janji bayar | ❌ | F-14 |
| Penjadwalan ulang cicilan, gagal bayar cicilan | ❌ | F-07 |
| Persetujuan bertingkat (multi-level) | ❌ | IMP-01 |
| Tutup periode harian/bulanan, ekspor siap-jurnal, data biaya untuk PDDikti | ❌ | F-17 |
| Ekspor XLSX/PDF laporan (sekarang CSV) | ❌ | §11 |
| Pembatasan data per fakultas/prodi | ❌ | §4.2 |
| Portal orang tua, keuangan di aplikasi mobile | ❌ | F-16 |

---

## 9. SDM (Kepegawaian)

Rancangan: `RANCANGAN-AKUN-SDM.md` (22 modul). Kode: `app/Modules/HumanResource` (API `/hr/*`, `/me/hr/*`). **Web hanya punya halaman Data Tenaga Kependidikan** (`/sdm/tenaga-kependidikan`).

| # | Modul SDM | Status |
|---|---|---|
| 1 | Dashboard SDM | ⚙️ |
| 2 | Data pegawai (CRUD, aktif/nonaktif, ekspor) | ⚙️ (UI hanya untuk tendik ✅) |
| 3 | Data dosen | ✅ (lewat modul Akademik) |
| 4 | Data tenaga kependidikan (kategori, penugasan, rekap & rasio) | ✅ |
| 5 | Riwayat pendidikan | ⚙️ |
| 6 | Riwayat jabatan + master jabatan | ⚙️ |
| 7 | Riwayat pekerjaan / penempatan | ⚙️ (melalui mutasi & riwayat jabatan) |
| 8 | Dokumen kepegawaian (versi, verifikasi) | ⚙️ |
| 9 | Kontrak & status kepegawaian (terminasi) | ⚙️ |
| 10 | Cuti & izin (kuota, ajukan, setujui, batal) | ⚙️ |
| 11 | **Kehadiran pegawai** | ❌ |
| 12 | Kinerja pegawai | ⚙️ |
| 13 | Pelatihan & sertifikasi | ⚙️ |
| 14 | Jabatan akademik dosen | ⚙️ |
| 15 | Kepangkatan + master pangkat | ⚙️ |
| 16 | Penempatan & mutasi, unit kerja | ⚙️ |
| 17 | Pengajuan SDM (proses, setujui, tolak) | ⚙️ |
| 18 | Persetujuan SDM | ⚙️ |
| 19 | Laporan SDM (11 jenis) | ⚙️ |
| 20 | Ekspor data | 🟡 (ekspor pegawai/tendik ada) |
| 21 | Notifikasi SDM + job harian `hr:daily-maintenance` | ⚙️ |
| 22 | Audit log SDM | ⚙️ |
| — | Layanan mandiri pegawai (profil, cuti, pengajuan) | ⚙️ |
| — | Aktivitas penelitian & pengabdian dosen (catatan per dosen) | ⚙️ |
| — | Payroll & slip gaji, lembur & perjalanan dinas, surat peringatan, tugas pegawai, BKD | ❌ |

---

## 10. Modul Data Pendukung

| Modul | Sudah | Belum |
|---|---|---|
| Beasiswa | ✅ daftar/detail program & pendaftar (baca); potongan beasiswa di KEU | Pendaftaran mahasiswa, persyaratan, seleksi, penetapan, evaluasi (`kemahasiswaan-kelulusan/08`) |
| Skripsi/Tesis | ✅ daftar/detail (baca) | Seluruh alur (§4.20, A.9.3) |
| Magang/KKN/MBKM | ✅ daftar/detail (baca) | Pendaftaran, penempatan, logbook, penilaian, konversi (§4.21–4.22, A.10) |
| Perpustakaan | ✅ katalog & data peminjaman (baca) | Sirkulasi, denda, reservasi, repositori, kartu anggota, bebas pustaka (§4.26) |
| Alumni | ✅ daftar/detail (baca) | Konversi lulusan → alumni, tracer, lowongan, event, donasi (§4.35) |
| Pengumuman | ✅ CRUD + baca di portal | Target per peran/angkatan/kelas, jadwal publikasi, kanal WA/SMS/push (§4.36) |

---

## 11. Modul yang Belum Ada Sama Sekali (Sudah Ada Rancangan Rinci)

| Modul | Rancangan |
|---|---|
| **OBE** — profil lulusan, CPL, pemetaan, CPMK, RPS, pengukuran ketercapaian, evaluasi kurikulum, laporan akreditasi, OBE AI | `academic-learning/04-obe.md` |
| **EDOM** — periode, formulir, pengisian anonim, ambang responden, syarat lihat nilai, rekap | `academic-learning/05-edom.md` (halaman portal masih 🧩) |
| **Semester Pendek** | `docs/semester-pendek/` (14 dokumen) |
| **Poin Kemahasiswaan** — kategori & aturan poin, aktivitas & prestasi, pelanggaran & sanksi, banding | `kemahasiswaan-kelulusan/05–07` |
| **Beasiswa mahasiswa (alur lengkap)** | `kemahasiswaan-kelulusan/08` |
| **Surat keterangan aktif resmi** (penomoran tercatat, verifikasi; PDF dasar sudah ada) | `kemahasiswaan-kelulusan/09` |
| **Yudisium** — periode, syarat berversi, eligibilitas, verifikasi, keputusan, predikat | `kemahasiswaan-kelulusan/10–12` |
| **Dokumen resmi kelulusan** — templat, penomoran, hash, verifikasi | `kemahasiswaan-kelulusan/13` |
| **Wisuda & buku wisuda** | `kemahasiswaan-kelulusan/14` |
| **SKPI** dua bahasa | `kemahasiswaan-kelulusan/15` |
| **Tracer study** — periode, kuesioner, distribusi, reminder, dashboard | `kemahasiswaan-kelulusan/16–19` |
| **Karirlink** — portal karier, lowongan, lamaran, mitra & CRM, rekomendasi lulusan | `kemahasiswaan-kelulusan/20–23` (halaman `/one/career` hanya 🧩) |
| **Aplikasi Android lengkap** (71 layar, biometrik, push, offline, dll.) | `RANCANGAN_FLUTTER_CIVITAS_ONE.md` |

## 12. Modul Jangka Panjang (Baru Konsep di Cetak Biru)

Semua ❌, sumber `RANCANGAN-APLIKASI.md`:

- Akreditasi BAN-PT/LAM (§21.1) — *Kritis*
- Pelaporan PDDikti/Feeder (§21.2) — *Kritis*
- Kepatuhan UU PDP lintas modul (§21.3) — *Kritis* (sebagian prinsip sudah di PMB & KEU)
- Keuangan institusi: RKAT, anggaran, pengadaan, buku besar (§21.4)
- Kerja sama & MoU (§21.5)
- Career center mahasiswa aktif (§21.6)
- Urusan internasional (§21.8), fasilitas pendukung (§21.9), survei umum (§21.10), API & developer portal (§21.11)
- Penelitian (§4.28), pengabdian masyarakat (§4.29)
- Sarana & prasarana (§4.27)
- Organisasi & kegiatan mahasiswa (§4.30), pelanggaran & disiplin di luar poin (§4.31)
- Pengaduan & layanan mahasiswa (§4.32)
- Rapat & agenda (§4.37), tugas pegawai (§4.13)
- Absensi dosen & pegawai (§4.15), payroll (§3.11)
- Integrasi: SSO, Google Workspace, Microsoft 365, Zoom/Meet/Teams, Google Calendar, fingerprint, face recognition, plagiarism checker, TTE (§11)

---

## 13. Frontend Lain

| Bagian | Status | Catatan |
|---|---|---|
| Landing page publik "Civitas One Atma" (`frontend/src/landing`) | ✅ | Tabel `CAPABILITY_STATUS` di `landing/data/catalog.ts` **sudah tertinggal**: PMB masih "planned" dan keuangan masih "phased" padahal sudah dibangun |
| Aplikasi `/one/*` (`frontend/src/one`, ±30 rute) | 🧩 | Seluruhnya data statis dari `one/data/*`, nol pemanggilan API |
| Ujian publik `/exam/*` | ✅ | |

## 14. Aplikasi Mobile (Flutter)

| Fitur | Status |
|---|---|
| Login, lupa/atur ulang sandi, sesi & perangkat | ✅ |
| Dashboard | ✅ (dasar) |
| Ujian: daftar, wizard buat/ubah, bank soal, pratinjau | ✅ |
| Fondasi: tema terang/gelap, router + guard izin, Dio + interceptor tenant, secure storage, 3 flavor | ✅ |
| Semua modul lain (akademik, keuangan, PMB, portal mahasiswa, SDM, persetujuan, platform) | ❌ |
| Pengerjaan ujian oleh mahasiswa | ❌ |
| Push notification, biometrik, mode offline, deep link, multi-bahasa | ❌ |

## 15. Utang Teknis & Celah yang Tercatat

| Item | Sumber |
|---|---|
| 17 tes gagal (`makeParticipationClassSectionFixture()` tidak terdefinisi) | `keu/catatan-implementasi` §11 |
| Batas SKS & skala nilai hardcoded | `academic-learning/README` §3.3 |
| Satu kurikulum per MK (`courses.curriculum_id`), satu dosen per kelas, ruang berupa teks bebas | §3.3 |
| Cakupan Dekan/Kaprodi belum ditegakkan (Akademik, KEU) | §3.3 |
| Entitlement paket belum ditegakkan; nama paket repo (trial/starter/professional/enterprise) ≠ acuan kuota KEU | §3.3, KEU IMP-05 |
| Dua jalur identitas dosen (`employees.user_id → lecturers.employee_id` vs `lecturers.user_id`) | `semester-pendek/tahapan-pengembangan` R-03 |
| Modul `Internship` tumpang tindih dengan MBKM | `academic-learning` OD-14 |
| FK lama `cascadeOnDelete` ke `students` dapat menghapus arsip | `kemahasiswaan-kelulusan/README` GAP-11 |
| `ApprovalWorkflow` belum mencegah self-approval pada approver bertipe role/user (hanya kontrak opsional) | GAP-07 |
| Belum ada React Query/SWR (fetch manual per halaman) | `FRONTEND-IMPLEMENTASI.md` §6 |
| Dokumen status lama (`FRONTEND-IMPLEMENTASI.md`, `BLACK-BOX-TESTING.md`, `RANCANGAN-AKUN-*.md`, `landing/data/catalog.ts`) tidak lagi mencerminkan kode | — |
