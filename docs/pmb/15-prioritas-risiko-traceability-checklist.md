# 15 — Prioritas Implementasi, Dependensi & Risiko, Matriks Keterlacakan, dan Checklist

[← Kembali ke dokumen utama](../RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md)

---

# A. Prioritas Implementasi

## A.1 Dasar penentuan prioritas

1. **MVP** = himpunan minimum agar satu siklus PMB jalur reguler & prestasi dapat berjalan end-to-end secara aman: konfigurasi → daftar → bayar → verifikasi → nilai → sahkan → umumkan → daftar ulang → mahasiswa.
2. Fitur yang menjadi **prasyarat integritas** (idempotensi pembayaran, snapshot seleksi, SoD pengesahan, konversi idempoten, audit) masuk MVP walau tidak disebut sumber — tanpa itu sistem tidak aman dipakai.
3. Fitur yang di sumber bergantung paket lebih tinggi (CBT: Business+; RPL: Professional+) atau membutuhkan integrasi pihak ketiga yang belum dikontrak (SMS, CRM, TTE) → Phase 2/3.
4. Fitur efisiensi (verifikasi massal, promosi otomatis, voucher) → Phase 2.

## A.2 Prioritas per fitur

| Fitur | FR | Fase | Alasan prioritas | Dependensi | Risiko | Indikator keberhasilan |
|---|---|---|---|---|---|---|
| Periode, gelombang, jalur, sistem kuliah | CFG-01..03, 06 | MVP | Fondasi semua proses | `academic_terms`, `study_programs` | Salah urutan tanggal | Periode dapat dipublikasi tanpa developer |
| Gelombang-jalur, kuota, persyaratan, biaya | CFG-04, 07..09 | MVP | Menentukan apa yang dapat didaftar & ditagih | Item di atas | Kuota/tarif salah input | 0 tagihan dengan nominal di luar konfigurasi |
| Template formulir berversi | CFG-10 | MVP | Formulir per jalur | Mesin formulir | Editor kompleks → mulai dengan field tambahan sederhana | Template baru tanpa deploy |
| Skema seleksi berversi | CFG-11, SEL-01 | MVP | Prasyarat perhitungan yang dapat diaudit | — | Kebijakan belum diputuskan (RSK-01) | Skema terkunci sebelum run |
| Template notifikasi & pengaturan | CFG-12, NTF-02 | MVP | Komunikasi wajib | Modul Notification | Template belum diisi tenant | Semua event MVP punya template aktif |
| Profil prodi & website publik dasar | CFG-05, WEB-01..09 | MVP | Pintu masuk pendaftar [E] | Tenancy branding, domain | SEO/performa | LCP ≤ 2,5 s; info sinkron konfigurasi |
| CMS penuh | WEB-10 | Phase 2 | MVP cukup konten sederhana (FAQ/berita/legal via `pmb_contents` form dasar) | — | — | Panitia mengelola konten mandiri |
| Akun, verifikasi email, login, reset, anti-duplikat, consent | ACC-01..08 | MVP | Prasyarat portal & UU PDP | Guard Sanctum baru | Enumerasi/brute force | 0 akun ganda per identitas |
| OTP SMS/WA | ACC-02 (opsi) | Phase 2 | Butuh kontrak penyedia | Penyedia SMS/WA | Biaya, deliverability | Tingkat sukses OTP ≥ 95% |
| Formulir & data pendaftar | FRM-01..08 | MVP | Inti pendaftaran [E] | Template, profil | Field sensitif berlebihan | ≥ 90% pendaftar kirim tanpa bantuan |
| Dokumen + pemindaian + versi | DOC-01..07 | MVP | Persyaratan administrasi [E] | Storage privat, antivirus | Malware, kapasitas storage | 0 file tak bersih diunduh petugas |
| Tagihan & pembayaran gateway + manual | PAY-01..04, 06..08 | MVP | Pendapatan & syarat lanjut [E] | Kontrak gateway (RSK-02) | Callback ganda/hilang | 0 efek ganda; rekonsiliasi selisih ≤ H+1 |
| Rekonsiliasi dasar | PAY-09 | MVP | Integritas keuangan | Akses settlement | Format settlement berbeda | Batch harian tertutup ≤ H+2 |
| Voucher | PAY-05 | Phase 2 | Promosi, bukan inti | — | Penyalahgunaan kode | Pemakaian sesuai kuota |
| Refund via approval | PAY-10 | Phase 2 | MVP: pencatatan manual oleh keuangan | Approval engine | Proses manual rawan | Semua refund punya approval |
| Verifikasi: antrean, pemeriksaan, keputusan, penugasan, catatan | VER-01..04, 06 | MVP | Gerbang seleksi | Dokumen, pembayaran | Bottleneck petugas | Median ≤ 3 menit/aplikasi |
| Verifikasi massal | VER-05 | Phase 2 | Efisiensi | VER-03 | Verifikasi tanpa pemeriksaan | Hanya aksi aman tersedia |
| Ujian luring: jadwal, sesi, ruang, penguji, kartu, hadir, nilai, reschedule | EXM-01..08 | MVP | Jalur reguler berujian [E] | Master ruang PMB | Bentrok jadwal | 0 sesi melebihi kapasitas |
| CBT terintegrasi | EXM-09, INT-05 | Phase 2 | Business+ di sumber; GAP-03 | Refactor ujian / vendor | Regresi ujian akademik | Nilai CBT tersinkron otomatis |
| Perhitungan, rekomendasi, alokasi, ranking, pengesahan, audit nilai | SEL-02..08 | MVP | Inti seleksi [E rekomendasi] | Skema, nilai | Kebijakan multi-pilihan | Golden test lulus; run reproducible |
| Pengesahan, publikasi terjadwal, hasil individual, LoA, koreksi | ANN-01..06 | MVP | Keputusan sah [E LoA] | Approval engine, PDF | Kebocoran hasil | 0 hasil terlihat sebelum waktunya |
| Promosi cadangan manual | BR-32 (REG-12) | MVP | Mengisi kursi kosong | Keputusan | Race condition | 0 over-capacity |
| Promosi cadangan otomatis | BR-32 | Phase 2 | Otomasi | — | Keputusan tanpa pengawasan | Aturan dikonfigurasi & dicatat |
| Daftar ulang: konfirmasi, data lanjutan, dokumen final, tagihan awal, cicilan pertama, validasi, persetujuan | REG-01..07 | MVP | Syarat menjadi mahasiswa | PAY, DOC | Data PDDikti tak lengkap | ≥ 95% checklist otomatis akurat |
| NIM & konversi mahasiswa idempoten + integrasi Akademik/Finance | REG-08..10, INT-01..02 | MVP | Tujuan akhir PMB [E] | Modul Academic & Finance, migrasi additive | Duplikasi NIM | 0 duplikat; ≥ 99% konversi otomatis |
| Bukti registrasi, orientasi, batas & kedaluwarsa | REG-11..12 | MVP | Penutupan proses | — | — | Notifikasi enrolled terkirim |
| Jalur prestasi | SPC-01 | MVP | Disebut target MVP | SEL (komponen prestasi) | Verifikasi sertifikat | Poin prestasi terhitung otomatis |
| KIP Kuliah, pindahan, pascasarjana, beasiswa/afirmasi, ODS | SPC-02..04, 06..07 | Phase 2 | Variasi jalur [E sebagian] | MVP lengkap | Aturan khusus per jalur | Jalur dapat dibuka via konfigurasi |
| RPL & kerja sama | SPC-05, 08 | Phase 3 | Modul RPL terpisah (Professional+) | Modul RPL | Ketergantungan modul lain | Data RPL mengalir ke modul RPL |
| Dashboard & laporan rekap (pendaftar, pembayaran, sumber info, seleksi, daftar ulang) | RPT-01..06 | MVP | [E] rekap & monitoring | Data operasional | Query berat | Dashboard ≤ 5 menit tertunda |
| Laporan keuangan PMB lanjut | RPT-07 | Phase 2 | Rekonsiliasi PMB vs Finance | INT-02 | — | Selisih teridentifikasi |
| Perbandingan antarperiode & analitik | RPT-08 | Phase 3 | Butuh ≥ 2 periode data | `pmb_daily_stats` | Data historis tidak tersedia | KPI-16 tampil |
| Notifikasi email & in-app | NTF-01, 03, 04 | MVP | [E] notifikasi email | Penyedia email | Masuk spam | Deliverability ≥ 98% |
| Quick chat & broadcast email/SMS | COM-01..02 | Phase 2 | [E] tetapi bukan prasyarat alur | Notification, SMS | Beban petugas | SLA balasan ≤ 1 hari kerja |
| Helpdesk/tiket | COM-03 | Phase 3 | Dapat memakai Tata Usaha | Modul TU (belum ada) | — | — |
| Integrasi payment gateway | INT-04 | MVP | Pembayaran online [E] | Kontrak | — | Lihat PAY |
| CRM/Maukuliah, PDDikti readiness, TTE, SIMPONI/EMIS | INT-03, 06 | Phase 3 | Butuh akses/kontrak pihak ketiga | Dokumentasi pihak ketiga | Spesifikasi belum tersedia | Integrasi terverifikasi di sandbox |

## A.3 Urutan pengerjaan MVP (usulan sprint 2 minggu)

| Sprint | Fokus | Keluaran |
|---|---|---|
| S1 | Fondasi modul, migrasi konfigurasi, RBAC & seeder, guard pendaftar | CRUD konfigurasi (API), role & permission |
| S2 | Akun pendaftar, profil, website publik dasar | Registrasi→login, halaman publik |
| S3 | Formulir dinamis, aplikasi, dokumen & pemindaian | Draft→submit (tanpa bayar) |
| S4 | Tagihan, gateway (sandbox), manual, webhook, rekonsiliasi dasar | Bayar→paid idempoten |
| S5 | Verifikasi (antrean, pemeriksaan, keputusan), notifikasi email | Submitted→verified |
| S6 | Ujian luring, nilai, skema & run seleksi | Run + golden test |
| S7 | Approval, publikasi terjadwal, LoA, promosi cadangan, koreksi | Hasil terbit aman |
| S8 | Daftar ulang, tagihan awal, NIM, konversi, posting Finance | Enrolled end-to-end |
| S9 | Dashboard & laporan, audit, hardening keamanan, performa | KPI, laporan, NFR |
| S10 | UAT siklus penuh, pentest, perbaikan, go-live readiness | Rilis MVP |

---

# B. Dependensi dan Risiko

## B.1 Dependensi

| ID | Dependensi | Jenis | Dibutuhkan untuk | Status |
|---|---|---|---|---|
| DEP-01 | Keputusan kebijakan seleksi (Q-SEL-01..05) | Stakeholder | S6 | Belum |
| DEP-02 | Kontrak & sandbox payment gateway (Q-PAY-01) | Pihak ketiga | S4 | Belum |
| DEP-03 | Penyedia email transaksional & domain pengirim (SPF/DKIM) | Pihak ketiga/IT | S2 | Belum |
| DEP-04 | Object storage privat & antivirus | Infrastruktur | S3 | Belum |
| DEP-05 | Kontrak `AdmissionEnrollmentService` di modul Academic + migrasi `students` (GAP-04) | Internal | S8 | Belum |
| DEP-06 | Migrasi `invoices.source_type/source_id` di modul Finance | Internal | S8 | Belum |
| DEP-07 | Workflow approval PMB diseed & approver ditetapkan (Q-ANN-01) | Stakeholder/internal | S7 | Belum |
| DEP-08 | Kebijakan privasi, ketentuan, pernyataan (teks hukum) & DPO (Q-PDP-01) | Stakeholder/legal | S2 (wajib sebelum publish) | Belum |
| DEP-09 | Format NIM (Q-REG-01) & field PDDikti (Q-REG-05) | Stakeholder | S8 | Belum |
| DEP-10 | Domain/subdomain PMB per tenant (`university_domains`) | IT tenant | S2 | Belum |
| DEP-11 | Penyedia SMS/WA | Pihak ketiga | Phase 2 | — |
| DEP-12 | Keputusan CBT internal vs eksternal (Q-EXM-02) | Stakeholder | Phase 2 | — |
| DEP-13 | Dokumentasi API CRM/Maukuliah, TTE, SIMPONI | Pihak ketiga | Phase 3 | — |

## B.2 Risiko

Skala: Kemungkinan (K) & Dampak (D) 1–3; Skor = K × D.

| ID | Risiko | K | D | Skor | Mitigasi | Pemilik |
|---|---|:-:|:-:|:-:|---|---|
| RSK-01 | Kebijakan seleksi berubah/terlambat → rework | 3 | 3 | 9 | Mesin berbasis konfigurasi; keputusan P1 sebelum S6; simulasi skema | Ketua panitia |
| RSK-02 | Kontrak gateway terlambat | 2 | 3 | 6 | Transfer manual + verifikasi petugas sebagai fallback; adapter terisolasi | Keuangan |
| RSK-03 | Lonjakan trafik hari terakhir/pengumuman | 3 | 2 | 6 | Uji beban, cache publik, snapshot keputusan, autoscale, publikasi bertahap notifikasi | IT |
| RSK-04 | Data PDDikti tidak lengkap | 2 | 3 | 6 | Field wajib di daftar ulang + KPI-19 | BAAK |
| RSK-05 | Kebocoran data pribadi | 2 | 3 | 6 | Enkripsi, masking, RBAC scope, log akses, DPIA, pentest | DPO/IT |
| RSK-06 | Perubahan pada `students`/`invoices` meregresi modul lain | 2 | 2 | 4 | Migrasi additive, test regresi, review modul pemilik | Tech lead |
| RSK-07 | Refactor ujian untuk CBT meregresi ujian akademik | 2 | 3 | 6 | Ditunda ke Phase 2 (KD-07); opsi vendor | Tech lead |
| RSK-08 | Petugas tidak mengikuti SoD (berbagi akun) | 2 | 3 | 6 | Akun individual, MFA, audit, pelatihan | Pimpinan |
| RSK-09 | Email masuk spam → pendaftar tidak menerima verifikasi/hasil | 2 | 2 | 4 | SPF/DKIM/DMARC, domain tenant, notifikasi in-app sebagai cadangan | IT |
| RSK-10 | Kesalahan konfigurasi admin (kuota/biaya/tanggal) | 3 | 2 | 6 | Validasi, pratinjau, daftar periksa publish, BR-03, audit | Admin PMB |
| RSK-11 | Ketergantungan pada uraian SEVIMA yang bersifat tafsiran (INK-05) | 2 | 2 | 4 | Label [I]; konfirmasi stakeholder | BA |
| RSK-12 | Ruang lingkup membengkak (jalur khusus di MVP) | 2 | 2 | 4 | Batas MVP tegas; change control | PM |

---

# C. Matriks Keterlacakan Kebutuhan

Kolom: **FR** · **Kebutuhan** · **Sumber** (ID dari [01 §2](01-analisis-dokumen-sumber.md#2-inventaris-modul-dan-fitur-sumber), "—" bila tidak ada) · **Label** · **Spesifikasi** · **Fase** · **AC** ([14 §C](14-edge-cases-testing-acceptance.md#c-acceptance-criteria)).

## C.1 Konfigurasi, website, akun

| FR | Kebutuhan | Sumber | Label | Spesifikasi | Fase | AC |
|---|---|---|---|---|---|---|
| FR-CFG-01 | Periode PMB & tahun akademik | — | R | 03 §A.3 | MVP | AC-20 |
| FR-CFG-02 | Gelombang & jadwal tahapan | SRC-PMB-06 | E | 03 §A.3 | MVP | AC-20 |
| FR-CFG-03 | Jalur masuk | SRC-PMB-06 | E | 03 §A.3 | MVP | — |
| FR-CFG-04 | Gelombang-jalur (portal per jalur & gelombang) | SRC-PMB-02 | I | 03 §A.3 | MVP | AC-20 |
| FR-CFG-05 | Profil prodi publik | SRC-PMB-03 | E | 03 §A.3 | MVP | — |
| FR-CFG-06 | Sistem kuliah | SRC-PMB-06 | E | 03 §A.3 | MVP | — |
| FR-CFG-07 | Kuota | SRC-PMB-07 (tersirat) | I | 03 §A.3 | MVP | AC-14 |
| FR-CFG-08 | Persyaratan dokumen (prasyarat) | SRC-PMB-06 | E | 03 §A.3 | MVP | AC-05 |
| FR-CFG-09 | Biaya pendaftaran, biaya awal, brosur | SRC-PMB-04, SRC-KEU-02 | E | 03 §A.3 | MVP | — |
| FR-CFG-10 | Template formulir berversi | SRC-PMB-09 (INK-04) | R | 03 §A.3, 04 §D.3 | MVP | — |
| FR-CFG-11 | Skema seleksi berversi | — | R | 06 §H.3 | MVP | AC-11 |
| FR-CFG-12 | Template notifikasi/pengumuman & pengaturan tahapan | — | R | 03 §A.3 | MVP | — |
| FR-WEB-01 | Branding tenant & domain | SRC-PMB-01 | E | 03 §B.3 | MVP | — |
| FR-WEB-02 | Beranda & info PT | SRC-PMB-01 | E | 03 §B.3 | MVP | — |
| FR-WEB-03 | Info fakultas & prodi | SRC-PMB-03 | E | 03 §B.3 | MVP | — |
| FR-WEB-04 | Info jalur & persyaratan | SRC-PMB-02, SRC-PMB-06 | E | 03 §B.3 | MVP | — |
| FR-WEB-05 | Info biaya & brosur | SRC-PMB-04 | E | 03 §B.3 | MVP | — |
| FR-WEB-06 | Jadwal penerimaan | SRC-PMB-08 | I | 03 §B.3 | MVP | — |
| FR-WEB-07 | Panduan, FAQ, berita | — | R | 03 §B.3 | MVP | — |
| FR-WEB-08 | Cek status & hasil publik | — | R | 03 §B.3 | MVP | AC-21 |
| FR-WEB-09 | Kontak & tombol chat | — | R | 03 §B.3 | MVP | — |
| FR-WEB-10 | CMS konten | — | R | 03 §B.3 | Phase 2 | — |
| FR-ACC-01 | Registrasi (termasuk assisted) | SRC-PMB-13 (tersirat) | I | 04 §C.2 | MVP | AC-01 |
| FR-ACC-02 | Verifikasi email/OTP | — | R | 04 §C.2 | MVP (OTP: Phase 2) | AC-01 |
| FR-ACC-03 | Login, logout, sesi | — | R | 04 §C.2 | MVP | — |
| FR-ACC-04 | Lupa & reset sandi | — | R | 04 §C.2 | MVP | — |
| FR-ACC-05 | Profil akun | — | R | 04 §C.2 | MVP | — |
| FR-ACC-06 | Pencegahan duplikat | — | R | 04 §C.3 | MVP | AC-02 |
| FR-ACC-07 | Persetujuan (consent) | — | R | 04 §C.2 | MVP | AC-01 |
| FR-ACC-08 | Anti-penyalahgunaan & pemulihan akun | — | R | 04 §C.2 | MVP | — |

## C.2 Formulir, dokumen, pembayaran, verifikasi

| FR | Kebutuhan | Sumber | Label | Spesifikasi | Fase | AC |
|---|---|---|---|---|---|---|
| FR-FRM-01 | Formulir online per jalur | SRC-PMB-13, SRC-PMB-09 | E | 04 §D | MVP | AC-03 |
| FR-FRM-02 | Data identitas | — | R | 04 §D.2.1 | MVP | AC-02 |
| FR-FRM-03 | Data pendidikan & rapor | — | R | 04 §D.2.2 | MVP | — |
| FR-FRM-04 | Pilihan akademik | — | R | 04 §D.2.3 | MVP | — |
| FR-FRM-05 | Data pendukung | — | R | 04 §D.2.4 | MVP | — |
| FR-FRM-06 | Pernyataan & persetujuan | — | R | 04 §D.2.5 | MVP | AC-03 |
| FR-FRM-07 | Draft, autosave, kunci, riwayat | — | R | 04 §D.4 | MVP | AC-04 |
| FR-FRM-08 | Sumber informasi | SRC-PMB-11 | E | 04 §D.2.4 | MVP | AC-19 |
| FR-DOC-01 | Unggah dokumen | SRC-PMB-13 | E | 04 §E.1 | MVP | AC-05 |
| FR-DOC-02 | Validasi file | — | R | 04 §E.1 | MVP | AC-05 |
| FR-DOC-03 | Penyimpanan aman & pemindaian | — | R | 04 §E.1 | MVP | AC-05 |
| FR-DOC-04 | Verifikasi per dokumen | — | R | 04 §E.1 | MVP | AC-08 |
| FR-DOC-05 | Unggah ulang & versi | — | R | 04 §E.1 | MVP | AC-08 |
| FR-DOC-06 | Penguncian | — | R | 04 §E.1 | MVP | AC-05 |
| FR-DOC-07 | Akses & retensi | — | R | 04 §E.1 | MVP | AC-18 |
| FR-PAY-01 | Tarif & tagihan pendaftaran | SRC-PMB-09 | E | 05 §F.2 | MVP | AC-06 |
| FR-PAY-02 | Pembayaran online gateway | SRC-PMB-12 | E | 05 §F.2 | MVP | AC-06 |
| FR-PAY-03 | Kanal terkonfigurasi | SRC-KEU-01 | I | 05 §F.2 | MVP | — |
| FR-PAY-04 | Transfer manual/loket & bukti | SRC-KEU-03 | I | 05 §F.2, F.5 | MVP | AC-07 |
| FR-PAY-05 | Voucher | SRC-KEU-04 | I | 05 §F.2 | Phase 2 | — |
| FR-PAY-06 | Callback idempoten & double-check | — | R | 05 §F.4 | MVP | AC-06 |
| FR-PAY-07 | Status, kedaluwarsa, pembatalan | — | R | 05 §F.2 | MVP | AC-06 |
| FR-PAY-08 | Bukti pembayaran & riwayat | — | R | 05 §F.2 | MVP | — |
| FR-PAY-09 | Rekonsiliasi | — | R | 05 §F.6 | MVP (dasar) | — |
| FR-PAY-10 | Pembatalan & refund | — | R | 05 §F.7 | Phase 2 | — |
| FR-VER-01 | Daftar & antrean pendaftar | — | R | 05 §G.2 | MVP | AC-17 |
| FR-VER-02 | Halaman pemeriksaan | — | R | 05 §G.2 | MVP | AC-18 |
| FR-VER-03 | Keputusan verifikasi | — | R | 05 §G.2–G.3 | MVP | AC-08 |
| FR-VER-04 | Penugasan pemeriksa | — | R | 05 §G.2 | MVP | AC-08 |
| FR-VER-05 | Verifikasi massal aman | — | R | 05 §G.2 | Phase 2 | — |
| FR-VER-06 | Catatan, riwayat, ekspor | — | R | 05 §G.2 | MVP | AC-19 |

## C.3 Seleksi, ujian, pengumuman

| FR | Kebutuhan | Sumber | Label | Spesifikasi | Fase | AC |
|---|---|---|---|---|---|---|
| FR-SEL-01 | Skema & komponen | — | R | 06 §H.3 | MVP | AC-11 |
| FR-SEL-02 | Input nilai non-ujian | — | R | 06 §H.6 | MVP | — |
| FR-SEL-03 | Perhitungan skor | — | R | 06 §H.4 | MVP | AC-11 |
| FR-SEL-04 | Rekomendasi kelulusan | SRC-PMB-07 | E | 06 §H.6 | MVP | AC-11 |
| FR-SEL-05 | Alokasi pilihan & kuota | — | R | 06 §H.4.6 | MVP | AC-11 |
| FR-SEL-06 | Ranking & tie-break | — | R | 06 §H.5 | MVP | AC-11 |
| FR-SEL-07 | Peninjauan & pengesahan | SRC-PMB-07 (rekomendasi → keputusan) | I | 06 §H.6, J | MVP | AC-12 |
| FR-SEL-08 | Riwayat & audit nilai | — | R | 06 §H.8 | MVP | AC-10 |
| FR-EXM-01 | Jadwal ujian | SRC-PMB-07 | E | 06 §I.1 | MVP | AC-09 |
| FR-EXM-02 | Sesi & ruang | SRC-PMB-07 | E | 06 §I.1 | MVP | AC-09 |
| FR-EXM-03 | Pembagian peserta | SRC-PMB-16 | E | 06 §I.1 | MVP | AC-09 |
| FR-EXM-04 | Penugasan penguji | SRC-PMB-16 | E | 06 §I.1 | MVP | AC-10 |
| FR-EXM-05 | Pengumuman jadwal & kartu peserta | SRC-PMB-08 | E | 06 §I.1 | MVP | AC-09 |
| FR-EXM-06 | Kehadiran | — | R | 06 §I.1 | MVP | — |
| FR-EXM-07 | Input & impor nilai | — | R | 06 §I.1 | MVP | AC-10 |
| FR-EXM-08 | Penjadwalan ulang | — | R | 06 §I.1 | MVP | — |
| FR-EXM-09 | Integrasi CBT | SRC-PMB-18, SRC-CBT-01 | E | 06 §I.1, 11 §B.5 | Phase 2 | — |
| FR-ANN-01 | Penetapan hasil akhir & override | — | R | 06 §J.1 | MVP | AC-12 |
| FR-ANN-02 | Pengesahan pejabat | — | R | 06 §J.1 | MVP | AC-12 |
| FR-ANN-03 | Publikasi terjadwal | — | R | 06 §J.1, J.3 | MVP | AC-12 |
| FR-ANN-04 | Pengumuman individual & cek hasil | SRC-PMB-19 (notifikasi pengumuman) | I | 06 §J.1 | MVP | AC-12, AC-21 |
| FR-ANN-05 | Letter of Acceptance | SRC-PMB-05 | E | 06 §J.1 | MVP | AC-12 |
| FR-ANN-06 | Koreksi & riwayat publikasi | — | R | 06 §J.5 | MVP | AC-13 |

## C.4 Daftar ulang, jalur khusus, pelaporan, komunikasi, integrasi

| FR | Kebutuhan | Sumber | Label | Spesifikasi | Fase | AC |
|---|---|---|---|---|---|---|
| FR-REG-01 | Konfirmasi/penolakan tawaran | — | R | 07 §K.2 | MVP | AC-15 |
| FR-REG-02 | Data lanjutan & orang tua/wali | — | R | 07 §K.2 | MVP | AC-15 |
| FR-REG-03 | Dokumen final & kesehatan opsional | — | R | 07 §K.2 | MVP | AC-15 |
| FR-REG-04 | Tagihan biaya awal | SRC-KEU-02 | I | 07 §K.2 | MVP | AC-15 |
| FR-REG-05 | Cicilan, potongan, beasiswa | SRC-KEU-04, SRC-KEU-05 | I | 07 §K.2 | MVP (cicilan pertama), Phase 2 (voucher) | — |
| FR-REG-06 | Verifikasi pembayaran biaya awal | — | R | 07 §K.2, 05 §F | MVP | AC-06 |
| FR-REG-07 | Validasi kelengkapan & persetujuan final | — | R | 07 §K.2 | MVP | AC-15 |
| FR-REG-08 | Setting & penerbitan NIM | SRC-PMB-10 | E | 07 §K.6 | MVP | AC-16 |
| FR-REG-09 | Pembentukan mahasiswa (ekspor) | SRC-PMB-10 | E | 07 §K.6 | MVP | AC-16 |
| FR-REG-10 | Integrasi & pemulihan kegagalan | — | R | 07 §K.7 | MVP | AC-16 |
| FR-REG-11 | Bukti registrasi & orientasi | — | R | 07 §K.2 | MVP | AC-16 |
| FR-REG-12 | Batas waktu, perpanjangan, kedaluwarsa, pembatalan, promosi cadangan | — | R | 07 §K.4–K.5, 06 §J.4 | MVP | AC-14, AC-15 |
| FR-SPC-01 | Jalur prestasi | (uraian [I] Penjelasan §3.1) | I | 07 §L | MVP | AC-11 |
| FR-SPC-02 | KIP Kuliah | SRC-PMB-17 | E | 07 §L | Phase 2 | — |
| FR-SPC-03 | Jalur pindahan | SRC-PMB-15 | E | 07 §L | Phase 2 | — |
| FR-SPC-04 | Pascasarjana | SRC-PMB-15 | E | 07 §L | Phase 2 | — |
| FR-SPC-05 | RPL | SRC-PMB-15, SRC-RPL-01 | E | 07 §L | Phase 3 | — |
| FR-SPC-06 | Beasiswa & afirmasi | SRC-KEU-04, SRC-POI-01 | I | 07 §L | Phase 2 | — |
| FR-SPC-07 | One Day Service | SRC-PMB-18 | E | 07 §L | Phase 2 | — |
| FR-SPC-08 | Kerja sama & jalur lain | — | R | 07 §L | Phase 3 | — |
| FR-RPT-01 | Dashboard monitoring PMB | SRC-DSH-01 | E | 07 §M | MVP | AC-19 |
| FR-RPT-02 | Rekap pendaftar | SRC-PMB-11 | E | 07 §M | MVP | AC-19 |
| FR-RPT-03 | Rekap pembayaran | SRC-PMB-11 | E | 07 §M | MVP | AC-19 |
| FR-RPT-04 | Laporan sumber informasi | SRC-PMB-11 | E | 07 §M | MVP | AC-19 |
| FR-RPT-05 | Laporan seleksi | — | R | 07 §M | MVP | — |
| FR-RPT-06 | Laporan daftar ulang & konversi | — | R | 07 §M | MVP | — |
| FR-RPT-07 | Laporan keuangan PMB | SRC-DSH-02 | I | 07 §M | Phase 2 | — |
| FR-RPT-08 | Ekspor & perbandingan antarperiode | — | R | 07 §M | Phase 3 | AC-19 |
| FR-COM-01 | Quick chat | SRC-PMB-19 | E | 13 §A.1 | Phase 2 | — |
| FR-COM-02 | Broadcast email & SMS | SRC-PMB-19 | E | 13 §A.1 | Phase 2 | — |
| FR-COM-03 | Helpdesk/tiket | SRC-TU-01 | I | 13 §A.1 | Phase 3 | — |
| FR-NTF-01 | Notifikasi email per tahap | SRC-PMB-19 | E | 13 §A.2 | MVP | AC-03, AC-06, AC-12 |
| FR-NTF-02 | Template per tenant | — | R | 13 §A.3 | MVP | — |
| FR-NTF-03 | Retry & riwayat | — | R | 13 §A.4 | MVP | AC-06 |
| FR-NTF-04 | Preferensi & keamanan konten | — | R | 13 §A.4–A.5 | MVP | — |
| FR-INT-01 | Integrasi Akademik | SRC-PMB-10, SRC-SIA-01 | E | 11 §B.1 | MVP | AC-16 |
| FR-INT-02 | Integrasi Keuangan | SRC-KEU-* | I | 11 §B.2 | MVP | AC-16 |
| FR-INT-03 | CRM/Maukuliah | SRC-PMB-14, SRC-CRM-01, SRC-MKL-01 | E | 11 §B.7 | Phase 3 | — |
| FR-INT-04 | Payment gateway | SRC-PMB-12, SRC-KEU-01 | E | 11 §B.3 | MVP | AC-06 |
| FR-INT-05 | CBT | SRC-PMB-18, SRC-CBT-01 | E | 11 §B.5 | Phase 2 | — |
| FR-INT-06 | PDDikti readiness, TTE, SIMPONI/EMIS | SRC-NEO-01, SRC-TTE-01, SRC-SIM-01, SRC-EMIS-01 | I | 11 §B.6, B.8 | Phase 3 | — |

## C.5 Rekap cakupan

| Kategori | FR | E | I | R | MVP | Phase 2 | Phase 3 |
|---|---:|---:|---:|---:|---:|---:|---:|
| CFG | 12 | 6 | 2 | 4 | 12 | 0 | 0 |
| WEB | 10 | 5 | 1 | 4 | 9 | 1 | 0 |
| ACC | 8 | 0 | 1 | 7 | 8 | 0 | 0 |
| FRM | 8 | 2 | 0 | 6 | 8 | 0 | 0 |
| DOC | 7 | 1 | 0 | 6 | 7 | 0 | 0 |
| PAY | 10 | 2 | 3 | 5 | 8 | 2 | 0 |
| VER | 6 | 0 | 0 | 6 | 5 | 1 | 0 |
| SEL | 8 | 1 | 1 | 6 | 8 | 0 | 0 |
| EXM | 9 | 6 | 0 | 3 | 8 | 1 | 0 |
| ANN | 6 | 1 | 1 | 4 | 6 | 0 | 0 |
| REG | 12 | 2 | 2 | 8 | 12 | 0 | 0 |
| SPC | 8 | 5 | 2 | 1 | 1 | 5 | 2 |
| RPT | 8 | 4 | 1 | 3 | 6 | 1 | 1 |
| COM/NTF | 7 | 3 | 1 | 3 | 4 | 2 | 1 |
| INT | 6 | 4 | 2 | 0 | 3 | 1 | 2 |
| **Total** | **125** | **42** | **17** | **66** | **105** | **14** | **6** |

Catatan: fitur dengan sebagian MVP & sebagian Phase 2 (mis. FR-ACC-02 OTP, FR-REG-05 voucher, FR-PAY-09 rekonsiliasi lanjut) dihitung pada fase **pertama** kemunculannya.

## C.6 Fitur sumber yang sengaja tidak diturunkan menjadi FR PMB

| Sumber | Alasan |
|---|---|
| SRC-DSH-03 (Generator soal CBT AI, AI Assistant) | Di luar lingkup PMB; dapat menjadi bagian modul CBT |
| SRC-EDL-01 (Proctoring) | Bergantung CBT (Phase 2), dicatat di 11 §B.5 |
| SRC-SIA-02/03/04/05 | Dijalankan modul Akademik setelah konversi; PMB hanya menyediakan data |
| SRC-KEU-06 (UKT) | Kondisional PTN; dicatat sebagai pertanyaan bila tenant PTN [K] |
| SRC-KEU-07 (deposit, denda) | Tidak relevan PMB |
| SRC-BIOS-01, SRC-KES-01, SRC-PPG-01 | Kondisional jenis PT; tidak dirancang tanpa kebutuhan tenant |
| SRC-IMP-01 (migrasi data awal) | Hanya data agregat historis (10 §9) |

---

# D. Checklist Implementasi

## D.1 Prasyarat (sebelum S1)

- [ ] Konfirmasi A-01 dan KD-01..KD-12 oleh tech lead & stakeholder.
- [ ] Jawaban seluruh pertanyaan **P1** di [02 §4](02-asumsi-keputusan-pertanyaan.md#4-pertanyaan-untuk-stakeholder).
- [ ] DPO tenant ditetapkan; teks kebijakan privasi, ketentuan, & pernyataan disiapkan (DEP-08).
- [ ] Gateway & penyedia email dipilih; akun sandbox tersedia (DEP-02, DEP-03).
- [ ] Storage privat & antivirus tersedia (DEP-04).
- [ ] DPIA dimulai.
- [ ] Catatan di `RANCANGAN-APLIKASI.md §4.2` bahwa bagian PMB digantikan dokumen ini (GAP-09).

## D.2 MVP

**Database & fondasi**
- [ ] Modul `app/Modules/Admission` (struktur folder sesuai konvensi), registrasi provider & rute.
- [ ] Migrasi 66 tabel `pmb_*` sesuai urutan [10 §9](10-database-erd-data-dictionary.md#9-strategi-migrasi-database) dengan `down()` teruji.
- [ ] Migrasi additive `students` (+ `student_profiles` bila disetujui) & `invoices`/`payments`.
- [ ] Enum PHP untuk seluruh SM-01..SM-11 dengan `allowedTransitions()` + unit test.
- [ ] `TenantScoped` di semua model; test isolasi tenant.
- [ ] `AdmissionModuleSeeder`: role/permission (pemetaan [08](08-rbac.md)), template notifikasi, approval workflows, katalog dokumen, sistem kuliah.

**Konfigurasi & website**
- [ ] CRUD + aturan BR-01..BR-06 & dampak perubahan (03 §A.4) — AC-20.
- [ ] Mesin formulir (template, field, aturan tampil, validasi server) — AC-03, AC-04.
- [ ] Skema seleksi + lock + simulasi.
- [ ] Endpoint publik + halaman publik PUB-01..14 (SEO, aksesibilitas) — AC-21.

**Pendaftar**
- [ ] Guard `applicant`, registrasi, verifikasi email, login, reset, sesi, consent — AC-01.
- [ ] Profil + `identity_hash` + flag duplikat — AC-02.
- [ ] Aplikasi, wizard, autosave, submit idempoten, nomor pendaftaran — AC-03.
- [ ] Dokumen: unggah, validasi isi, pemindaian, versi, kunci, URL bertanda tangan — AC-05.

**Pembayaran**
- [ ] Tagihan & item, nomor tagihan, adapter gateway, webhook (signature, dedupe, double-check), polling cadangan — AC-06.
- [ ] Transfer manual & pembayaran loket, SoD — AC-07.
- [ ] Rekonsiliasi harian dasar; penanganan `needs_resolution`.

**Verifikasi & seleksi**
- [ ] Antrean, klaim, pemeriksaan, keputusan, perbaikan + job kedaluwarsa revisi — AC-08.
- [ ] Ujian: ruang, jadwal, sesi, bagi otomatis, penguji, kartu QR, kehadiran, input/impor nilai, kunci — AC-09, AC-10.
- [ ] Kalkulator skor, alokasi BR-28, tie-break, snapshot + hash, override, golden test — AC-11.

**Pengumuman & daftar ulang**
- [ ] Integrasi approval engine, job publikasi terjadwal, verifikasi hash, LoA + verifikasi publik — AC-12.
- [ ] Koreksi berversi & promosi cadangan ber-lock — AC-13, AC-14.
- [ ] Daftar ulang: terima/tolak, data lanjutan (PDDikti), dokumen final, tagihan awal + cicilan pertama, checklist, persetujuan (SoD), job kedaluwarsa, perpanjangan — AC-15.
- [ ] `AdmissionEnrollmentService` di Academic, sequence NIM, worker konversi (outbox, lease, retry, langkah), posting Finance idempoten — AC-16.

**Lintas**
- [ ] Notifikasi email & in-app untuk event MVP (13 §A.2) + dedupe + retry.
- [ ] Dashboard ADM-01..07 & laporan RPT-01..06, ekspor tercatat — AC-19.
- [ ] Audit: history status, audit log, log akses sensitif, log ekspor — AC-18.
- [ ] Keamanan: rate limit, CAPTCHA, header, CSP, enkripsi kolom, masking log — AC-17.
- [ ] Observabilitas: log terstruktur, metrik, alert (`pmb.ops.alert`).
- [ ] Testing: unit, integrasi, konkurensi, API (otorisasi 100%), E2E Playwright, uji beban, axe, pentest.
- [ ] UAT satu siklus penuh; runbook operasional (publikasi, insiden, retry konversi); restore drill.

## D.3 Phase 2

- [ ] OTP SMS/WA (ACC-02), quick chat (COM-01), broadcast (COM-02).
- [ ] CMS penuh (WEB-10).
- [ ] Voucher (PAY-05), refund via approval (PAY-10), laporan keuangan PMB (RPT-07).
- [ ] Verifikasi massal aman (VER-05).
- [ ] CBT: keputusan internal/eksternal, generalisasi mesin ujian atau adapter vendor, sinkron nilai (EXM-09, INT-05).
- [ ] Jalur KIP Kuliah, pindahan, pascasarjana, beasiswa/afirmasi, ODS (SPC-02..04, 06..07).
- [ ] Promosi cadangan otomatis (opsional, terkonfigurasi).
- [ ] Multi-gateway & circuit breaker per kanal.
- [ ] Master kode wilayah & referensi sekolah (autocomplete).

## D.4 Phase 3

- [ ] Integrasi CRM/Maukuliah setelah dokumentasi tersedia (INT-03).
- [ ] RPL: pendaftaran → modul RPL (SPC-05); jalur kerja sama (SPC-08).
- [ ] TTE LoA & bukti registrasi; SIMPONI/EMIS kondisional; pemeriksaan kesiapan PDDikti (INT-06).
- [ ] Perbandingan antarperiode & analitik lanjutan (RPT-08), helpdesk (COM-03).
- [ ] Bahasa Inggris untuk pendaftar internasional.
