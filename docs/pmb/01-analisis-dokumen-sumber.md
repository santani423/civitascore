# 01 — Analisis Dokumen Sumber

[← Kembali ke dokumen utama](../RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md)

Label: **[E]** eksplisit di proposal SEVIMA · **[I]** inferensi · **[R]** rekomendasi · **[K]** perlu konfirmasi.

---

## 1. Metode Analisis

1. Membaca `SEVIMA_Platform_Proposal.md` seluruhnya (matriks fitur per paket, 16 kelompok modul, add-on, ketentuan).
2. Membaca `SEVIMA_Penjelasan_Modul.md` seluruhnya (uraian fungsional 28 bagian + glosarium). Dokumen ini menyatakan: *"Penjelasan fitur di dokumen ini adalah uraian fungsional berdasarkan nama fitur pada penawaran. Detail teknis dan alur kerja aktual sebaiknya dikonfirmasi saat demo dengan pihak SEVIMA."* Karena itu isinya diperlakukan sebagai **[I]**.
3. Mengekstrak teks PDF asli `[14092026] WEBSITE Proposal Penawaran SEVIMA Platform.pdf` (13 halaman, Microsoft Word 2019, 14 Sep 2026) dan membandingkan bagian PMB, RPL, CRM, KEU, Maukuliah, serta keterangan. **Hasil: identik dengan Markdown.** PDF tidak berisi diagram alur, spesifikasi teknis, API, maupun struktur data — hanya matriks centang dan harga.
4. Membaca kode CivitasOne (`app/Modules/*`, migrasi, `routes/api.php`) dan dokumen internal (`RANCANGAN-APLIKASI.md`, `MULTI-TENANT-ARCHITECTURE.md`) untuk memetakan entitas yang dapat dipakai ulang dan batasan teknis.

**Kesimpulan metodologis:** sumber SEVIMA memberikan **daftar kemampuan** (apa), tidak memberikan **cara kerja** (bagaimana). Semua aturan bisnis, status, struktur data, dan API dalam rancangan ini adalah rancangan baru yang diberi label **[R]**, kecuali keberadaan fiturnya sendiri yang diberi **[E]**.

---

## 2. Inventaris Modul dan Fitur Sumber

### 2.1 Modul PMB (Proposal §3.3) — semua paket kecuali disebut

| ID | Fitur (nama sesuai sumber) | Paket | Uraian Penjelasan_Modul [I] | Relevansi PMB |
|---|---|---|---|---|
| SRC-PMB-01 | Website PMB sesuai branding kampus (ac.id) | Semua | Logo, warna, domain resmi kampus | Langsung |
| SRC-PMB-02 | ↳ Portal pendaftaran sesuai jalur & gelombang | Semua | Calon memilih jalur & gelombang | Langsung |
| SRC-PMB-03 | ↳ Manajemen detail informasi program studi | Semua | Deskripsi, akreditasi, prospek prodi | Langsung |
| SRC-PMB-04 | ↳ Manajemen informasi biaya kuliah & brosur | Semua | Rincian biaya & brosur unduh | Langsung |
| SRC-PMB-05 | Manajemen Siklus PMB ↳ Penerbitan LoA | Semua | Surat penerimaan untuk yang lulus | Langsung |
| SRC-PMB-06 | ↳ Manajemen jalur, gelombang, prasyarat & sistem kuliah | Semua | Jalur, jadwal gelombang, syarat dokumen, sistem kuliah (reguler/karyawan/daring) | Langsung |
| SRC-PMB-07 | ↳ Manajemen ujian seleksi, ruang ujian & rekomendasi kelulusan | Semua | Jadwal, ruang, rekomendasi lulus dari nilai | Langsung |
| SRC-PMB-08 | ↳ Pengumuman jadwal ujian | Semua | Jadwal tampil di portal | Langsung |
| SRC-PMB-09 | ↳ Manajemen formulir & tagihan pendaftaran | Semua, "Lebih Lengkap" | Formulir & tagihan biaya formulir | Langsung |
| SRC-PMB-10 | ↳ Setting NIM & ekspor menjadi mahasiswa | Semua | NIM otomatis & pindah ke SIAKAD | Langsung |
| SRC-PMB-11 | ↳ Laporan rekap pendaftar, pembayaran & sumber informasi | Semua | Rekap untuk evaluasi promosi | Langsung |
| SRC-PMB-12 | Pembayaran tagihan pendaftaran online | Semua | VA/e-commerce, "terverifikasi otomatis" | Langsung |
| SRC-PMB-13 | Form pendaftaran online & upload berkas persyaratan administrasi | Semua | Isi data & unggah dokumen | Langsung |
| SRC-PMB-14 | Integrasi SEVIMA CRM (MauKuliah) | Semua | Peminat Maukuliah masuk ke data PMB | Integrasi |
| SRC-PMB-15 | Dukungan jalur pindahan, pascasarjana, RPL | Semua | S2/S3, pindahan, RPL | Jalur khusus |
| SRC-PMB-16 | Pembagian ruangan, penguji & peserta berdasarkan jadwal ujian | Semua | Bagi peserta & penguji "secara otomatis" | Langsung |
| SRC-PMB-17 | Manajemen pendaftaran & pengelolaan KIP Kuliah | Semua | Pendaftar penerima KIP-K | Jalur khusus |
| SRC-PMB-18 | One Day Service (ODS); ↳ seleksi terintegrasi CBT | Semua; CBT Business+ | Daftar–tes–pengumuman sehari; tes di CBT dinilai otomatis | Jalur khusus |
| SRC-PMB-19 | Fasilitas penyebaran informasi: quick chat ke panitia, notifikasi email pengumuman pendaftaran, kirim info via email & SMS | Semua | Chat; email tiap tahap; broadcast | Komunikasi |

### 2.2 Modul lain yang terkait dengan PMB

| ID | Modul / fitur sumber | Paket | Keterkaitan dengan PMB | Jenis |
|---|---|---|---|---|
| SRC-DSH-01 | Smart Dashboard ↳ Monitoring Aktivitas PMB | Semua (Report); Professional+ (Dashboard) | KPI PMB untuk pimpinan. Uraian [I]: pendaftar, bayar formulir, lulus, daftar ulang per jalur/gelombang | Langsung |
| SRC-DSH-02 | Smart Dashboard ↳ Monitoring Keuangan Akademik | Semua | Pendapatan biaya pendaftaran & biaya awal | Tidak langsung |
| SRC-DSH-03 | Generator soal CBT; SEVIMA AI Assistant | Business+ | Soal ujian seleksi; chatbot informasi PMB | Tidak langsung (di luar lingkup) |
| SRC-NEO-01 | Neo Feeder ↳ Integrasi data akademik, **biaya masuk** & SPP | Semua | Data mahasiswa baru & biaya masuk dilaporkan ke PDDikti → PMB harus mengumpulkan data yang dibutuhkan | Tidak langsung |
| SRC-NEO-02 | Neo Feeder ↳ Daftar Perubahan Data Mahasiswa (PDM) | Semua | Kesalahan data identitas di PMB berbiaya tinggi untuk dikoreksi setelah lapor → validasi identitas ketat di PMB | Tidak langsung |
| SRC-KEU-01 | Pembayaran tagihan online (3–6 channel) | Per paket | Kanal pembayaran PMB dikonfigurasi, jumlah terbatas paket | Langsung |
| SRC-KEU-02 | Manajemen tarif uang kuliah (per angkatan, prodi, jalur, kelas [I]) | Semua | Tarif biaya pendidikan awal per prodi/jalur/sistem kuliah | Langsung |
| SRC-KEU-03 | Pembayaran tunai & upload bukti bayar | Semua | Pembayaran manual biaya pendaftaran & daftar ulang | Langsung |
| SRC-KEU-04 | Beasiswa/potongan; Voucher tagihan ("misalnya untuk promosi PMB" [I]) | Semua | Potongan biaya pendaftaran/biaya awal | Langsung |
| SRC-KEU-05 | Cicilan tagihan | Semua | Cicilan biaya pendidikan awal | Langsung |
| SRC-KEU-06 | Pengaturan & validasi penerima UKT | Semua | PTN: penetapan golongan UKT mahasiswa baru saat daftar ulang | Kondisional |
| SRC-KEU-07 | Deposit, denda | Semua | Tidak dipakai di PMB (denda tidak relevan); deposit opsional | Tidak dipakai |
| SRC-SIA-01 | SIAKAD ↳ Manajemen data & aktivitas mahasiswa | Semua | Target konversi pendaftar | Langsung |
| SRC-SIA-02 | SIAKAD ↳ Transfer mahasiswa & double degree | Semua | Konversi nilai jalur pindahan dilakukan di SIAKAD | Tidak langsung |
| SRC-SIA-03 | SIAKAD ↳ Paket mata kuliah | Semua | KRS paket semester 1 mahasiswa baru (pasca-konversi) | Tidak langsung |
| SRC-SIA-04 | SIAKAD ↳ Login sebagai orang tua; Info via email & SMS | Semua | Data orang tua/wali dari PMB dipakai akun orang tua | Tidak langsung |
| SRC-SIA-05 | SIAKAD ↳ Utilitas ruang kuliah | Semua | Ruang ujian PMB dapat memakai master ruang (belum ada di CivitasOne) | Tidak langsung |
| SRC-CBT-01 | CBT — Modul Test Online; CBT AI | Business+ | Ujian seleksi berbasis komputer | Langsung (opsional) |
| SRC-EDL-01 | EDLINK ↳ Proctoring | Professional+ | Pengawasan ujian seleksi online | Opsional |
| SRC-CRM-01 | CRM CAMABA: kontak peminat, penilaian interaksi, alur prospek, follow up, dasbor, custom landing page | Business+ (landing: Professional+) | Hulu PMB: peminat → pendaftar. Pipeline [I]: kenal → tertarik → daftar → bayar → daftar ulang | Integrasi |
| SRC-MKL-01 | Maukuliah: profil kampus, tes potensi, try out PTN | Semua / Business+ | Sumber lead & sumber informasi pendaftar | Integrasi |
| SRC-RPL-01 | RPL: syarat dokumen, jenis program & jalur, tarif formulir, mata kuliah RPL, jadwal & komposisi seleksi, verifikasi berkas & distribusi asesor, nilai & kelulusan, rekap | Professional+ / add-on Rp 4 jt | Jalur RPL: pendaftaran di PMB, asesmen di modul RPL | Jalur khusus |
| SRC-POI-01 | Poin Kemahasiswaan ↳ Beasiswa mahasiswa | Semua | Beasiswa jalur masuk diteruskan sebagai data beasiswa mahasiswa | Tidak langsung |
| SRC-TU-01 | Tata Usaha ↳ Tiket pertanyaan & keluhan; custom KTM | Semua | Helpdesk pendaftar; KTM mahasiswa baru | Tidak langsung |
| SRC-API-01 | Open API | Professional+ | Integrasi website/aplikasi kampus dengan PMB | Integrasi |
| SRC-TTE-01 | Dukungan TTE (biaya aktivasi terpisah) | Business+ | Tanda tangan elektronik LoA & bukti registrasi [I] | Opsional |
| SRC-SIM-01 | Integrasi SIMPONI KEMENKEU | Business+, PTN/kedinasan | Pembayaran PMB tercatat sebagai PNBP | Kondisional |
| SRC-EMIS-01 | Pelaporan EMIS KEMENAG ↳ master mahasiswa | Semua, PTKI | Data mahasiswa baru PTKI | Kondisional |
| SRC-BIOS-01 | Integrasi BIOS ↳ data layanan/keuangan | Professional+, PTN BLU | Pelaporan penerimaan | Kondisional |
| SRC-KES-01 | Modul Kampus Kesehatan | Semua, kampus kesehatan | Persyaratan kesehatan khusus jalur kesehatan (inferensi lemah) | Kondisional |
| SRC-PPG-01 | Manajemen PPG | Business+, LPTK | PPG punya mekanisme seleksi nasional sendiri; PMB dapat menjadi pintu registrasi [I] | Kondisional, [K] |
| SRC-IMP-01 | Migrasi data awal (Neofeeder + Excel; sistem lama di Enterprise) | Semua | Impor data PMB historis untuk perbandingan antarperiode | Tidak langsung |

### 2.3 Modul yang tidak relevan untuk PMB

EDLINK (selain proctoring), OBE, EDOM, Yudisium & Wisuda, SKPI, Tracer Study, Karirlink, SDM, Litabmas, SPME, SPMI, Dashboard IKU, Manajemen Kerjasama (kecuali sebagai sumber data mitra untuk jalur kerja sama **[I]**), SLiMS, Modul Fakultas Kedokteran.

---

## 3. Klasifikasi: Eksplisit vs Inferensi vs Rekomendasi

| Area rancangan | Eksplisit [E] | Inferensi [I] | Rekomendasi [R] |
|---|---|---|---|
| Konfigurasi | Jalur, gelombang, prasyarat, sistem kuliah, info prodi, biaya & brosur | Kuota (tersirat dari "rekomendasi kelulusan" + pengumuman), tarif per jalur/prodi (KEU) | Versi konfigurasi, penguncian, aturan dampak perubahan, skema seleksi berversi, template formulir dinamis |
| Website | Branding ac.id, portal per jalur & gelombang, info prodi, biaya, brosur | Pengumuman jadwal di portal | FAQ, berita, cek status/hasil tanpa login, CMS, SEO, aksesibilitas |
| Akun | — | Akun diperlukan untuk form online & pembayaran | Seluruh mekanisme akun, verifikasi, anti-duplikat, sesi |
| Formulir & dokumen | Form online, upload berkas, sumber informasi (dari laporan) | — | Field per kelompok, validasi, draft, autosave, versi dokumen, retensi |
| Pembayaran | Pembayaran online tagihan pendaftaran | Kanal per paket, tunai & bukti bayar, voucher, cicilan (KEU) | Idempotensi webhook, double-check, rekonsiliasi, refund, SoD |
| Verifikasi | — | Panitia memeriksa berkas (tersirat dari "prasyarat" & "upload berkas") | Dashboard verifikasi, penugasan, status, kode alasan |
| Seleksi & ujian | Ujian seleksi, ruang, rekomendasi kelulusan, pembagian ruang/penguji/peserta, jadwal ujian, CBT (Business+), ODS | Penguji menginput nilai | Rumus skor, normalisasi, alokasi multi-pilihan, tie-break, snapshot, approval |
| Pengumuman | LoA, notifikasi email pengumuman | Pengumuman hasil di portal | Approval pejabat, publikasi terjadwal, koreksi berversi |
| Daftar ulang | Setting NIM & ekspor menjadi mahasiswa | Biaya awal via KEU, cicilan, UKT (PTN) | Seluruh tahapan, status, batas waktu, perpanjangan, outbox, idempotensi |
| Jalur khusus | Pindahan, pascasarjana, RPL, KIP Kuliah, ODS | Prestasi, beasiswa (uraian [I] "reguler, prestasi, beasiswa") | Afirmasi, kerja sama, aturan khusus per jalur |
| Pelaporan | Rekap pendaftar, pembayaran, sumber informasi; monitoring PMB pimpinan | Indikator funnel per jalur/gelombang | Definisi KPI, rumus, perbandingan antarperiode, hak ekspor |
| Komunikasi | Quick chat, email pengumuman, email & SMS massal | — | Template, retry, riwayat, pembatasan konten sensitif |

---

## 4. Inkonsistensi dan Ambiguitas Sumber

| ID | Temuan | Lokasi | Dampak & keputusan rancangan |
|---|---|---|---|
| INK-01 | Integrasi CRM (MauKuliah) ada di PMB **semua paket**, sedangkan modul CRM CAMABA hanya **Business+**. | Proposal §3.3 vs §3.15 | Diartikan sebagai integrasi data peminat dari Maukuliah (tersedia untuk semua), bukan CRM penuh. PMB menyediakan endpoint penerimaan lead + atribusi sumber (FR-INT-03); CRM penuh di luar lingkup. |
| INK-02 | Dukungan jalur RPL ada di PMB **semua paket**, modul RPL hanya **Professional+** atau add-on. | §3.3 vs §3.4, §6.1 | PMB menyediakan jalur kategori `rpl` (pendaftaran, dokumen, biaya formulir). Asesmen/rekognisi mata kuliah menjadi tanggung jawab modul RPL (Phase 3, FR-SPC-05). |
| INK-03 | Manajemen ujian seleksi **semua paket**, CBT hanya **Business+**; ODS semua paket, ODS-CBT Business+. | §3.3, §3.8 | Mesin seleksi harus bekerja dengan sumber nilai non-CBT (input manual/impor). CBT = salah satu `source` komponen seleksi (FR-EXM-09). |
| INK-04 | "Manajemen Formulir & Tagihan Pendaftaran: **Lebih Lengkap**" di semua paket — tidak dijelaskan "lebih lengkap" dibanding apa. | §3.3, PDF hal. 2 | Tidak dapat dijadikan spesifikasi. Formulir dinamis berversi dirancang sebagai [R]. |
| INK-05 | Penjelasan_Modul: "Pendaftar yang sudah daftar ulang **otomatis** mendapat NIM dan dipindahkan ke SIAKAD sebagai **mahasiswa aktif**"; pembayaran "**terverifikasi otomatis**". | Penjelasan §3.2, §3.3 | Ditafsirkan ulang: NIM & konversi otomatis **setelah** persetujuan final daftar ulang (BR-33); "otomatis" untuk pembayaran hanya bila callback bertanda tangan & double-check (BR-15). |
| INK-06 | Jumlah channel pembayaran dibatasi paket (3/3/4/5/6); kanal "Bank atau e-Commerce". | §1, §3.6, §7 | Kanal = konfigurasi tenant, bukan hardcode (BR-17). Rancangan mendukung VA bank, QRIS, e-wallet/e-commerce melalui adapter gateway. |
| INK-07 | Karirlink tertulis modul Professional+, tetapi subfitur centang sejak Growth. | §3.9 catatan | Tidak berdampak pada PMB. Dicatat sebagai bukti bahwa matriks sumber tidak selalu konsisten → klaim [E] hanya untuk baris yang jelas. |
| INK-08 | Pelaporan "PPSDM PU" ditulis "(PT di bawah Kementerian Perhubungan)" — kementerian tidak cocok dengan nama. | §5 | Tidak berdampak pada PMB. |
| INK-09 | Pipeline CRM "kenal → tertarik → daftar → bayar → daftar ulang" hanya ada di Penjelasan_Modul. | Penjelasan §4 | Dipakai sebagai [I] untuk definisi funnel di dashboard (RPT-01). |
| INK-10 | Sumber tidak menyebut tahap **verifikasi administrasi** maupun **pengesahan hasil**, tetapi menyebut "rekomendasi kelulusan". | §3.3 | "Rekomendasi" ditafsirkan sebagai keluaran sistem yang **belum** final → perlu tahap pengesahan (FR-ANN-01..02, [R]). |

---

## 5. Analisis Kode CivitasOne yang Sudah Ada

### 5.1 Komponen yang dipakai ulang

| Komponen | Lokasi | Dipakai untuk |
|---|---|---|
| Tenancy (`universities`, `university_settings`, `university_modules`, `TenantScoped`, `TenantContext`, `ResolveUniversityMiddleware`) | `app/Modules/Tenancy`, `app/Support/Tenancy` | Isolasi data PMB, resolusi tenant dari domain website PMB, branding (logo, warna), pengaturan `pmb.*` |
| RBAC (`roles`, `permissions`, `user_roles` dengan `scope_type`/`scope_id`, `PermissionRegistry`, middleware `permission:`) | `app/Modules/UserManagement` | Role staf PMB & scope fakultas/prodi |
| Approval engine (workflow multi-step, `return`, contextual approver, `resubmission_count`) | `app/Modules/ApprovalWorkflow` | Pengesahan hasil seleksi, koreksi hasil, refund, perpanjangan massal |
| File upload (`file_uploads`: disk, path, mime, size, checksum, status `pending/scanning/clean/rejected`, soft delete, `university_id`) | `app/Modules/FileManagement` | Dokumen pendaftar, bukti bayar, LoA PDF, brosur |
| Notifikasi (`notification_templates` per tenant/event/channel, `notification_logs`, `user_notification_preferences`) | `app/Modules/Notification` | Semua notifikasi PMB |
| Audit (`audit_logs` dengan morph, old/new values, reason, IP, UA; `activity_logs`) | `app/Modules/AuditLog` | Audit transisi status, perubahan nilai, konfigurasi |
| Akademik (`faculties`, `study_programs` dengan `degree_level`, `academic_terms`, `students`, `student_status_histories`, `lecturers`) | `app/Modules/Academic` | Master prodi, tahun masuk, target konversi, penguji dari dosen |
| Finance (`invoices`, `payments`) | `app/Modules/Finance` | Posting tagihan biaya awal setelah konversi |
| Scholarship (`scholarships`, `scholarship_applications`) | `app/Modules/Scholarship` | Beasiswa jalur masuk diteruskan pasca-konversi |
| Announcement | `app/Modules/Announcement` | **Tidak** dipakai untuk hasil seleksi (audiens internal, tanpa approval/scheduling seleksi) |

### 5.2 Kesenjangan dan batasan teknis

| ID | Temuan di kode | Dampak | Keputusan |
|---|---|---|---|
| GAP-01 | `users.email` **unique global**, tabel `users` diklasifikasikan GLOBAL (tidak ber-`university_id`). | Calon mahasiswa yang mendaftar di dua kampus akan bentrok; mengecek keberadaan email membocorkan informasi lintas tenant. | KD-01: tabel `pmb_applicant_accounts` tenant-scoped dengan guard Sanctum sendiri (tabel `personal_access_tokens` sudah polymorphic `tokenable`). |
| GAP-02 | `invoices.student_id` **NOT NULL** FK `students`; `InvoiceStatus` hanya `unpaid/partial/paid`; `payments` tanpa referensi gateway. | Tidak dapat menagih pendaftar sebelum menjadi mahasiswa; tidak ada idempotensi gateway. | KD-03: tabel tagihan PMB sendiri; posting ke Finance saat konversi; tambah `source_type/source_id` di `invoices`. |
| GAP-03 | `exams` wajib `class_section_id`; `exam_attempts` wajib `krs_item_id`. | Mesin ujian tidak dapat dipakai pendaftar tanpa refactor. | KD-07: MVP tanpa CBT internal; Phase 2 generalisasi (`examinee` polymorphic) atau integrasi eksternal. |
| GAP-04 | `students` tidak memiliki NIK, kewarganegaraan, nama ibu, agama, jalur masuk, asal sekolah, `admission_application_id`. | Data PDDikti mahasiswa baru tidak lengkap; tidak ada jejak asal PMB. | Migrasi additive pada `students` atau tabel `student_profiles` [K] (lihat [10 §6](10-database-erd-data-dictionary.md#6-perubahan-pada-tabel-yang-sudah-ada)). |
| GAP-05 | `FileUploadService::store()` menyimpan di `uploads/{uploader_id}/...`, belum per tenant (MULTI-TENANT §9.7). Uploader pendaftar bukan `users`. | `uploaded_by` FK ke `users` tidak bisa diisi untuk pendaftar. | Untuk dokumen pendaftar: `uploaded_by = null`, kepemilikan via `fileable` (morph ke `pmb_document_versions`) dan path `universities/{tenant}/pmb/{application}/...`. |
| GAP-06 | `notification_templates` tidak punya fallback global (MULTI-TENANT §9.8). | Setiap tenant harus diseed template PMB. | Seeder template PMB per tenant saat modul `pmb` diaktifkan. |
| GAP-07 | `audit_logs.action` enum (sudah dilebarkan migrasi 2026_10_07). | Aksi PMB (mis. `verify`, `publish`) perlu ditambahkan. | Tambah nilai `AuditAction` PMB atau pakai `updated` + `reason` + metadata. |
| GAP-08 | Tidak ada master ruang (rooms) di CivitasOne. | Ruang ujian PMB perlu master sendiri. | `pmb_exam_venues` + `pmb_exam_rooms`; dapat dimigrasi ke master ruang global bila kelak dibuat. |
| GAP-09 | `RANCANGAN-APLIKASI.md §4.2` memakai satu daftar status PMB gabungan. | Bertentangan dengan kebutuhan status per siklus. | Dokumen ini menggantikan bagian tersebut; perlu catatan di `RANCANGAN-APLIKASI.md`. |
| GAP-10 | `ApprovalWorkflow` approver type `position` belum didukung penuh (MULTI-TENANT §9.2), sudah ada `approver_position_id` (migrasi 2026_10_07). | Pengesah "Rektor/WR I" lebih tepat via role. | Pakai approver type `role` (`pmb_approver`) pada MVP. |

---

## 6. Daftar Kebutuhan Awal dan Pertanyaan Hasil Analisis

Kebutuhan awal yang muncul dari analisis dikonsolidasikan menjadi FR di dokumen 03–07 dan dipetakan di [matriks keterlacakan](15-prioritas-risiko-traceability-checklist.md#c-matriks-keterlacakan-kebutuhan). Pertanyaan yang tidak dapat dijawab dari sumber dikumpulkan di [02 §4](02-asumsi-keputusan-pertanyaan.md#4-pertanyaan-untuk-stakeholder).
