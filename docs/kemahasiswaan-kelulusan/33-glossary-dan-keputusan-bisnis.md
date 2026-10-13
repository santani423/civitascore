# 33 — Glosarium dan Keputusan Bisnis

> **Status:** rancangan · Disusun 2026-10-13 (commit `47043ca`) · Kembali ke [README](README.md)

## 1. Glosarium

| Istilah | Definisi dalam rancangan ini | Representasi |
|---|---|---|
| Aktivitas | Kegiatan non-akademik/prestasi yang diajukan mahasiswa untuk diakui | `activity_submissions` |
| Kegiatan terdaftar | Kegiatan yang didaftarkan unit/penyelenggara beserta daftar peserta | `activity_events` |
| Poin diberikan / poin dihitung | Poin yang dicatat vs poin yang masuk saldo setelah batas periode | `awarded_points` / `counted_points` |
| Poin untuk yudisium | Poin dihitung dari kategori yang diakui yudisium dalam masa studi | `graduation_counted_points` |
| Ledger poin | Buku besar mutasi poin yang tidak pernah diubah | `student_point_ledgers` |
| Laporan pelanggaran | Klaim awal, belum terbukti | `violation_reports` |
| Kasus | Proses pemeriksaan per mahasiswa terlapor | `violation_cases` |
| Keputusan sanksi | Keputusan final pejabat berwenang | `disciplinary_decisions` (`final`) |
| Sanksi aktif | Keputusan final dalam masa berlakunya (turunan) | — |
| Program beasiswa | Skema beasiswa dengan sumber dana, kuota, syarat | `scholarships` (Ada) |
| Penerima beasiswa (award) | Penetapan seorang mahasiswa sebagai penerima untuk periode tertentu | `scholarship_awards` |
| Potongan beasiswa | Pengurang tagihan yang dikelola KEU | `student_discounts` (rancangan KEU) |
| Periode yudisium | Jendela pendaftaran dan penetapan kelulusan | `yudisium_periods` |
| Set persyaratan / versi | Lingkup penerapan aturan / isi aturan yang immutable setelah dipublikasikan | `graduation_requirement_sets` / `_versions` |
| Checker | Komponen kode yang menilai satu jenis syarat dari sumber data pemilik | `graduation_checker_definitions` |
| Eligibility | Hasil pemeriksaan otomatis kelayakan yudisium (bukan keputusan) | `graduation_eligibility_checks` |
| Stale | Hasil eligibility yang tidak berlaku karena data/aturan/umur berubah | `stale_at` |
| Pengecualian (exemption) | Pembebasan syarat tertentu oleh pejabat berwenang | `graduation_requirement_exemptions` |
| Calon peserta | Mahasiswa yang lolos pra-saring otomatis | Hasil discovery |
| Keputusan kelulusan | Penetapan resmi lulus/ditunda/tidak lulus dalam rapat yang disahkan | `graduation_decisions` |
| Predikat | Sebutan kelulusan menurut aturan kampus (mis. dengan pujian) | `honor_*` |
| Tanggal lulus | Tanggal kelulusan resmi menurut kebijakan periode | `graduation_date` |
| Lulusan / alumni | Mahasiswa dengan keputusan final `graduated` | `students.status = graduated`, `alumni` |
| Dokumen resmi | Dokumen bernomor yang diterbitkan institusi | `issued_documents` |
| Nomor resmi | Nomor yang tercetak menurut skema institusi | `official_number` |
| Nomor eksternal | Nomor dari otoritas luar (mis. PIN ijazah) | `external_number` |
| Kode verifikasi | Kode acak di QR/URL untuk memeriksa keaslian; bukan nomor dokumen | `verification_code_hash` |
| Snapshot | Salinan data yang dibekukan saat dokumen/keputusan dibuat | json `snapshot` |
| Penerbitan ulang | Dokumen baru yang menggantikan dokumen terbit | `supersedes_document_id` |
| Periode wisuda | Penyelenggaraan upacara wisuda | `graduation_ceremonies` |
| Buku wisuda (edisi) | Daftar wisudawan berversi yang dibangun dari sumber resmi | `graduation_book_editions` |
| SKPI | Surat Keterangan Pendamping Ijazah, dua bahasa | `skpi_documents` + `issued_documents` |
| Capaian SKPI | Klaim/rekaman prestasi untuk SKPI dengan empat tahap (diajukan, diverifikasi, disetujui untuk SKPI, dipilih) | `skpi_achievements` |
| Glosarium SKPI | Padanan baku istilah ID–EN | `skpi_glossary_terms` |
| Periode tracer | Penyelenggaraan survei untuk cohort tertentu | `tracer_periods` |
| Target responden | Alumni/pengguna lulusan yang dituju dalam periode | `tracer_targets` |
| Pengguna lulusan | Atasan/instansi yang menilai alumni | `tracer_employer_contacts` |
| Undangan / pengingat / kirim ulang | Jenis pengiriman email survei | `tracer_invitations.invitation_type` |
| Status pengiriman vs status pengisian | Fakta email vs fakta survei diisi; dua dimensi terpisah | `delivery_status` vs `tracer_responses.status` |
| Kuesioner terkirim (satuan kuota) | Target unik yang undangan awalnya diterima penyedia email dalam jendela kuota | `feature_usage_ledgers` |
| Response rate (RR) | Responden ÷ denominator (eligible atau terundang) yang disebutkan | `RR_ELG`, `RR_INV` |
| Waktu tunggu | Selang dari tanggal lulus ke pekerjaan pertama | Indikator `WT_*` |
| Profil pelaporan akreditasi | Pemetaan indikator ke tabel instrumen akreditasi berversi | `tracer_reporting_profiles` |
| APT / APS | Akreditasi institusi / program studi; formatnya mengikuti instrumen yang berlaku | — |
| Marketplace | Lowongan lintas kampus dari perusahaan terverifikasi platform | `career_job_postings.visibility = marketplace` |
| Whitelabel | Portal karier bermerek kampus | `career.whitelabel` |
| Kemitraan | Hubungan perusahaan dengan satu kampus | `career_partnerships` |
| Dasar akses | Alasan sah perusahaan melihat data kandidat (lamaran/consent) | `access_basis` |
| Consent | Persetujuan spesifik tujuan, penerima, data, masa berlaku | `data_sharing_consents` |
| Entitlement | Hak tenant atas fitur menurut paket | `plan_feature_entitlements` |
| Feature key | Kunci fitur paket, mis. `career.articles` | — |
| Scope | Batas data grant role (`u`,`f`,`p`,`w`,`b`,`s`,`t`,`c`) | [03 §1.1](03-aktor-role-dan-hak-akses.md#11-jenis-scope) |
| Ada / Ubah / Baru | Status komponen terhadap repository saat ini | — |

## 2. Keputusan Desain (KK-KD)

| ID | Keputusan | Alasan | Alternatif yang ditolak |
|---|---|---|---|
| <a id="kk-kd-01"></a>KK-KD-01 | Modul baru `StudentAffairs`, `Graduation`, `OfficialDocument`, `Skpi`, `TracerStudy`, `Career`. Memperluas `Scholarship` dan `Alumni` yang ada | Mengikuti pola `app/Modules/*`; batas tanggung jawab jelas | Satu modul besar "Kemahasiswaan" (sulit dirawat) |
| KK-KD-02 | Manajemen organisasi mahasiswa (pengurus, proposal dana, LPJ) di luar ruang lingkup. Kegiatan organisasi diakui melalui pengajuan aktivitas | Ruang lingkup kebutuhan E fokus pada poin | Membangun modul organisasi penuh sekarang |
| KK-KD-03 | Scholarship = penerima & periode; KEU = potongan; sinkron via event | Mencegah keputusan ganda; selaras rancangan KEU 11 | Penerima disimpan di KEU |
| KK-KD-04 | Poin ditentukan aturan; verifikator dapat menyesuaikan dalam rentang; di luar rentang perlu persetujuan | Konsistensi & anti-manipulasi | Mahasiswa mengusulkan poin |
| KK-KD-05 | Laporan, kasus, dan keputusan sanksi adalah entitas terpisah | Laporan belum terbukti tidak boleh berdampak | Satu tabel "pelanggaran" berstatus |
| KK-KD-06 | Surat aktif memakai `student_requests` + ApprovalWorkflow yang ada; penerbitan via OfficialDocument | Tidak menduplikasi alur yang sudah berjalan | Tabel pengajuan surat baru |
| KK-KD-07 | Rule engine = katalog checker (kode) + parameter (data) + versi immutable + resolusi spesifisitas | Fleksibel tanpa kode tenant, dapat diaudit | DSL bebas / ekspresi tenant (risiko keamanan & kesalahan) |
| KK-KD-08 | Eligibility terpisah dari keputusan; keputusan hanya melalui rapat + workflow pengesahan | Kebutuhan: tidak ada kelulusan final otomatis | Auto-lulus bila eligible |
| KK-KD-09 | Perubahan `students.status` menjadi `graduated` hanya melalui kontrak Akademik | Akademik pemilik data status; `StudentObserver` mencatat histori | Modul kelulusan menulis `students` langsung |
| KK-KD-10 | Layanan penomoran terpusat; ID internal ≠ nomor resmi ≠ nomor eksternal ≠ kode verifikasi | Mencegah duplikasi & kebingungan identitas | Penomoran per modul |
| KK-KD-11 | Modul kelulusan menyiapkan data, nomor, snapshot, PDF ijazah/transkrip; cetak blanko & pelaporan nasional di luar | Batas tanggung jawab realistis | Mengelola blanko fisik |
| KK-KD-12 | FK ke `students` untuk data keputusan/riwayat/dokumen memakai `restrict` | Mencegah hilangnya arsip resmi | Mengikuti pola `cascade` tabel lama |
| KK-KD-13 | Wisuda berbasis keputusan kelulusan final (dapat dikonfigurasi); biaya dikelola KEU | Wisuda adalah upacara; KEU sumber tagihan | Menghitung biaya di modul wisuda |
| KK-KD-14 | Buku wisuda sebagai snapshot berversi dengan masa koreksi | Konsistensi publikasi | Laporan dinamis |
| KK-KD-15 | SKPI memakai capaian dari sumber terverifikasi tanpa verifikasi ganda (kecuali kategori mewajibkan) | Efisiensi & konsistensi | Semua capaian diverifikasi ulang |
| KK-KD-16 | Dua bahasa per field + glosarium + peninjauan terjemahan wajib | Kualitas terjemahan isi, bukan hanya label | Hanya menerjemahkan label template |
| KK-KD-17 | Verifikasi keaslian: kode acak 128-bit (hash), hash berkas, halaman publik minimal | Keamanan & privasi | URL berisi NIM/nomor |
| KK-KD-18 | Versi kuesioner immutable; jawaban terikat versi | Konsistensi data historis | Edit kuesioner langsung |
| KK-KD-19 | Satuan kuota tracer = target unik dengan undangan awal diterima penyedia dalam jendela; kegagalan sebelum diterima dikembalikan | Adil & dapat diaudit (usulan, perlu konfirmasi) | Menghitung setiap email (termasuk pengingat) |
| KK-KD-20 | Status pengiriman terpisah dari status pengisian | Mencegah salah tafsir respons | Satu kolom status |
| KK-KD-21 | Jawaban tracer terpisah dari profil alumni; profil hanya diperbarui atas consent sebagai catatan bersumber; status tanpa sumber = belum diketahui | Integritas data & privasi | Menulis jawaban langsung ke `alumni` |
| KK-KD-22 | Perusahaan & lowongan marketplace = data platform; profil/CV/lamaran/consent = data tenant | Marketplace bersama tanpa kebocoran data mahasiswa | Perusahaan per tenant (duplikasi), atau data pengguna platform-wide |
| KK-KD-23 | Akun perekrut = `users` global + `career_company_members`; bukan anggota tenant | Perusahaan bukan bagian universitas | `user_universities` dengan tipe `guest` |
| KK-KD-24 | Akses CV hanya dengan dasar akses (lamaran/consent rekomendasi), dicatat dan terlihat pemilik | Least privilege | Perusahaan mitra bebas menelusuri CV |
| KK-KD-25 | Entitlement terpusat (`plan_feature_entitlements` + `FeatureUsageService`); interim `university_settings` hanya-platform | Tidak ada hardcode batas paket | Konstanta per modul |
| KK-KD-26 | Integrasi in-process: kontrak PHP sinkron + event Laravel `afterCommit`/queued + rekonsiliasi; tanpa outbox per modul | Monolit modular, preseden `SyncStudentRequestDecision` | Outbox per modul (KEU memakainya untuk integrasi eksternal) |

## 3. Pertanyaan Terbuka (KK-Q)

| ID | Pertanyaan | Dampak | Usulan sementara | Pemilik keputusan |
|---|---|---|---|---|
| <a id="kk-q-01"></a>KK-Q-01 | Pemetaan paket Growth/Business/Professional/Premium/Enterprise ke kode `subscription_plans` (trial/starter/professional/enterprise)? | Semua batas paket | Data-driven, tunda pemetaan (sama dengan KEU-Q-01, OD-01) | Manajemen produk |
| KK-Q-02 | Satuan & jendela kuota tracer (per periode/tahun akademik/tahun langganan/tenant); refund hard bounce? | Kuota | Per tahun langganan; refund hard bounce ≤ 7 hari | Produk & bisnis |
| KK-Q-03 | Marketplace Karirlink dibangun internal atau integrasi platform eksternal (karirlink.id)? | Arsitektur Career | Internal dengan adaptor | Produk |
| KK-Q-04 | Sumber referensi menyebut "modul lengkap mulai Professional" tetapi tabel memuat fitur Growth; mana yang berlaku? | Matriks paket | Tabel apa adanya, ditandai T | Produk |
| KK-Q-05 | Apakah poin kemahasiswaan wajib untuk yudisium? Ambang per prodi/angkatan? | Aturan yudisium | Tidak wajib sampai diatur | Kampus |
| KK-Q-06 | Basis batas periode poin? | Perhitungan poin | `academic_year` | Kampus |
| KK-Q-07 | Apakah poin pelanggaran mengurangi poin aktivitas? | Saldo poin | Tidak | Kampus |
| KK-Q-08 | Prosedur komisi disiplin, pejabat berwenang per tingkat, kewenangan terhadap alumni, hak didengar | Workflow disiplin | Hak didengar wajib; alumni hanya manager | Kampus (hukum/kemahasiswaan) |
| KK-Q-09 | Mekanisme tanda tangan dokumen (basah, gambar, elektronik tersertifikasi) & penyedia | Keabsahan dokumen | `wet_signature` | Kampus & hukum |
| KK-Q-10 | Urutan verifikasi yudisium, pengesah, mode pendaftaran (mandiri/oleh petugas/otomatis) | Workflow | Prodi → Fakultas → BAAK; mandiri | Kampus (akademik) |
| KK-Q-11 | Aturan predikat per jenjang (IPK, masa studi, nilai, syarat lain) | Predikat | Tidak ada default | Kampus (peraturan akademik) |
| KK-Q-12 | Format nomor per jenis dokumen & unit penerbit | Penomoran | Diisi per kampus | Kampus |
| KK-Q-13 | Proses nomor ijazah nasional (PIN) dan pelaporan kelulusan ke sistem nasional | Ijazah | Impor/ekspor berkas | Kampus & produk |
| KK-Q-14 | Dasar pendaftaran wisuda & biaya wisuda | Wisuda | Keputusan final; biaya opsional via KEU | Kampus |
| KK-Q-15 | Kolom buku wisuda (IPK, foto, judul TA, publikasi publik) | Privasi | Minimal; foto dengan consent | Kampus |
| KK-Q-16 | Struktur SKPI resmi, regulasi acuan yang berlaku, pemakaian terjemahan mesin | SKPI | Struktur dapat dikonfigurasi; tanpa mesin | Kampus & produk |
| KK-Q-17 | Instrumen tracer standar & versi resmi yang dipakai, cohort wajib, parameter indikator (X bulan) | Tracer | Template dimuat kurator dari dokumen resmi | Kampus & produk |
| KK-Q-18 | Instrumen akreditasi (APT/APS) & versinya | Rekap akreditasi | Profil pelaporan berversi | Kampus (penjaminan mutu) |
| KK-Q-19 | Dasar & persetujuan pengumpulan pendapatan | Privasi | Opsional, terenkripsi, agregat | Kampus & hukum |
| KK-Q-20 | Penyedia email & dukungan webhook; pelacakan buka email diizinkan? | Distribusi | Nonaktif | Produk & kampus |
| KK-Q-21 | Moderasi lowongan marketplace & verifikasi perusahaan oleh platform atau tenant? | Career | Platform | Produk |
| KK-Q-22 | Retensi CV, lamaran, dan data karier | Privasi | 24 bulan | Kampus & hukum |
| KK-Q-23 | Pejabat penetap beasiswa, kebijakan stacking, penyaluran beasiswa tunai, pelaporan ke pemberi dana | Beasiswa | Workflow per program | Kampus |
| KK-Q-24 | Status yang boleh mendapat surat aktif, masa berlaku surat, perlakuan surat lama bernomor turunan ULID | Surat | `active`; tanpa masa berlaku; label legacy | Kampus |
| KK-Q-25 | Akun alumni: akun mahasiswa tetap dipakai atau akun baru; email pribadi | Akses alumni & tracer | Akun sama + email kontak alumni | Kampus & produk |
| KK-Q-26 | Mahasiswa tingkat akhir boleh direkomendasikan (magang)? | Rekomendasi | Konfigurasi tenant, default tidak | Kampus |
| KK-Q-27 | Kepatuhan perlindungan data pribadi; peran pengendali/prosesor antara kampus, platform, perusahaan; prosedur hak subjek data | Seluruh modul | Kendali teknis di [28](28-keamanan-privasi-dan-audit.md) | Hukum/DPO |
| KK-Q-28 | Status bebas perpustakaan dan pelunasan denda | Eligibility | Denda → item manual | Kampus & Library |
| KK-Q-29 | Sumber data pejabat penandatangan (modul SDM) | Dokumen | Tautan opsional ke `employees`/`positions` | Kampus |
| KK-Q-30 | Domain kustom whitelabel & pengelolaan TLS | Career | Subdomain platform | Produk & infrastruktur |
| KK-Q-31 | Unit verifikator per kategori aktivitas & SLA verifikasi | Aktivitas | Per kategori; SLA 7 hari | Kampus |
| KK-Q-32 | Sanksi apa yang menghalangi yudisium menurut peraturan universitas | Eligibility | Hanya via `restricted_services` eksplisit | Kampus |
| KK-Q-33 | Sumber data ujian akhir/sidang (belum ada entitas terpisah) | Checker | Item manual | Kampus & Akademik |
| KK-Q-34 | Portal mahasiswa: Civitas One (`one/`) atau portal klasik (`pages/portal`)? | Frontend | Civitas One untuk fitur baru | Produk |

## 4. Asumsi (KK-A)

| ID | Asumsi | Dasar | Bila salah |
|---|---|---|---|
| <a id="kk-a-01"></a>KK-A-01 | Seluruh modul berjalan dalam satu aplikasi Laravel dan satu basis data | Struktur repository | Kontrak in-process perlu diganti API/outbox |
| KK-A-02 | Angka paket & fitur Karirlink/tracer berasal dari kebutuhan bisnis (referensi SEVIMA) | Prompt kebutuhan, `SEVIMA_Penjelasan_Modul.md` | Matriks [34](34-matriks-paket-dan-entitlement.md) disesuaikan (data saja) |
| KK-A-03 | Angka default di pengaturan (hari, batas, ukuran) hanya default rancangan | — | Diubah per tenant |
| KK-A-04 | Kanal notifikasi efektif hanya `database` & `mail` | `TemplatedNotification` hanya `toDatabase`/`toMail` | Tambah kanal tanpa ubah katalog |
| KK-A-05 | Lulusan tetap memakai akun yang sama karena `graduated` tidak diblokir login | `StudentStatus::loginBlocked()` | Perlu alur aktivasi akun alumni |
| KK-A-06 | `students.email` dapat berupa email kampus yang nonaktif setelah lulus | Praktik umum | Prioritas email disesuaikan |
| KK-A-07 | Blanko ijazah dicetak di luar sistem atau dari template PDF | — | Integrasi pencetakan |
| KK-A-08 | `AcademicScope` & role scope dibangun sesuai rancangan `academic-learning` | `academic-learning/11` | Role scope tidak dapat dipakai |
| KK-A-09 | `plan_feature_entitlements` dibangun sesuai `academic-learning/11 §2.2` | idem | Interim `university_settings` diperpanjang |
| KK-A-10 | `FinanceClearanceService` (KEU) tersedia, atau checker keuangan berjalan manual | KEU 17 §9 | Checker manual |
| KK-A-11 | Bahasa kedua SKPI hanya Bahasa Inggris | Kebutuhan | Kolom bahasa digeneralisasi |
| KK-A-12 | Sertifikat kolektif (satu berkas untuk banyak nama) sah dan tidak dianggap duplikat lintas mahasiswa | Praktik umum | Aturan duplikat diperketat |
| KK-A-13 | Ukuran sel minimum agregat = 5 | Praktik anonimisasi umum | Nilai diubah per tenant |
| KK-A-14 | Lingkungan staging acuan untuk uji kinerja tersedia | — | Target kinerja ditinjau ulang |
