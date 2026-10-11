# 02 — Asumsi, Batasan, Keputusan Desain, dan Pertanyaan Stakeholder

[← Kembali ke dokumen utama](../RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md)

Setiap asumsi berlaku sampai dikonfirmasi atau dibatalkan stakeholder. Kolom **Dampak jika salah** menunjukkan bagian rancangan yang harus diubah.

---

## 1. Asumsi

| ID | Asumsi | Dasar | Dampak jika salah |
|---|---|---|---|
| A-01 | PMB dibangun sebagai modul `Admission` di CivitasOne. Dokumen SEVIMA hanya acuan cakupan. | Repo & dokumen CivitasOne; brief meminta rancangan siap kembangkan | Bagian DB/API tidak berlaku; dokumen menjadi kebutuhan vendor |
| A-02 | Satu tenant = satu PT; tidak ada PMB bersama lintas tenant. | MULTI-TENANT-ARCHITECTURE | Perlu konsep konsorsium & berbagi kuota |
| A-03 | Identitas WNI = NIK 16 digit; WNA = paspor + negara penerbit. | Praktik nasional | BR-04, validasi FRM-02 |
| A-04 | Pendaftar ≥ 17 tahun atau didampingi wali; tidak ada perlakuan khusus anak di bawah umur pada MVP. | Mayoritas lulusan SMA ≥ 17 | Bila ada pendaftar < 18 tahun (anak menurut UU PDP), perlu persetujuan wali — lihat Q-PDP-02 |
| A-05 | MVP memakai **satu** payment gateway dengan VA bank & QRIS, callback bertanda tangan, plus transfer manual. | INK-06; kanal "Bank atau e-Commerce" | Adapter tambahan; jadwal MVP |
| A-06 | Biaya dalam Rupiah, tanpa desimal sen pada praktik, disimpan `decimal(14,2)` (konsisten `invoices`). | Kode `invoices` | — |
| A-07 | Zona waktu mengikuti `universities.timezone`; semua timestamp disimpan UTC (`timestampTz`). | Kode Tenancy | Tampilan jadwal |
| A-08 | Mahasiswa baru dibuat dengan `students.status = active`, `enrolled_at = tanggal persetujuan final`, `admission_year = tahun dari periode`. | Enum `StudentStatus` yang ada | Bila perlu status `pending_activation`, enum Akademik harus ditambah (Q-REG-03) |
| A-09 | Prodi pilihan berasal dari `study_programs` aktif; jenjang dari `study_programs.degree_level`. | Kode Academic | — |
| A-10 | Satu aplikasi memiliki 1..N pilihan prodi (N dikonfigurasi per gelombang-jalur, default 2). | Praktik PMB | BR-28, `pmb_application_choices` |
| A-11 | Ujian pada MVP: tes tertulis luring, wawancara, portofolio, tes khusus — nilai diinput/diimpor. CBT Phase 2. | INK-03, GAP-03 | Bila CBT wajib di MVP, Phase 2 ditarik maju |
| A-12 | Email adalah kanal notifikasi wajib; SMS/WA opsional per tenant. | SRC-PMB-19 | — |
| A-13 | LoA berupa PDF yang dibangkitkan sistem, diunduh dari portal; tanpa TTE di MVP. | SRC-PMB-05; TTE berbiaya terpisah | Integrasi TTE (Phase 3) |
| A-14 | Biaya pendaftaran default tidak dapat dikembalikan, kecuali periode/gelombang dibatalkan kampus. | Praktik umum | BR-19, alur refund |
| A-15 | Biaya pendidikan awal dibayar penuh atau minimal cicilan pertama sebelum persetujuan final. | SRC-KEU-05 cicilan | BR-33 |
| A-16 | Satu aplikasi menghasilkan maksimal satu mahasiswa. Pendaftar diterima di satu prodi saja per aplikasi. | Praktik | BR-28, BR-37 |
| A-17 | Pendaftar yang diterima di dua aplikasi berbeda (mis. dua jalur) hanya dapat menyelesaikan daftar ulang satu; menerima satu tawaran otomatis menolak tawaran lain dalam periode yang sama. | Praktik, mencegah kursi hilang | FR-REG-01 |
| A-18 | Volume awal per tenant: ≤ 20.000 akun, ≤ 10.000 aplikasi per periode, puncak ≤ 2.000 pengguna bersamaan saat pengumuman. | Kapasitas paket SEVIMA ≤ 30.000 mhs (benchmark) | Target NFR [13 §C](13-notifikasi-keamanan-nonfungsional.md#c-kebutuhan-nonfungsional) |
| A-19 | Website PMB memakai domain/subdomain tenant (mis. `pmb.kampus.ac.id`) yang terdaftar di `university_domains`. | SRC-PMB-01, Tenancy | Resolusi tenant di endpoint publik |
| A-20 | Konten publik dikelola panitia (Humas) melalui CMS sederhana di PMB, bukan CMS website utama kampus. | SRC-PMB-03/04 | Bila memakai CMS kampus, FR-WEB-10 diganti integrasi |
| A-21 | Tenant memakai Bahasa Indonesia; dukungan Bahasa Inggris (pendaftar internasional) Phase 3. | — | i18n |
| A-22 | Penguji/pewawancara adalah pengguna `users` (dosen/staf) yang sudah ada. | Kode `lecturers`, `employees` | — |
| A-23 | Semua pendaftar menyelesaikan proses secara online; loket hanya membantu. Petugas dapat membuat aplikasi atas nama pendaftar (assisted registration) dengan jejak audit. | SRC-PMB-13, ODS | FR-ACC-01 |
| A-24 | Aturan seleksi jalur yang diatur pemerintah (mis. KIP Kuliah) mengikuti ketentuan program; PMB hanya mencatat status & dokumen. | SRC-PMB-17 | FR-SPC-02 |

---

## 2. Batasan

| ID | Batasan |
|---|---|
| B-01 | Sumber SEVIMA tidak memuat spesifikasi teknis, aturan bisnis, atau API — tidak ada yang dapat diklaim sebagai "perilaku SEVIMA". |
| B-02 | Tabel `users` global dan unique email (GAP-01) membatasi cara akun pendaftar dikelola. |
| B-03 | Mesin ujian CivitasOne terikat kelas (GAP-03). |
| B-04 | Tagihan Finance terikat mahasiswa (GAP-02). |
| B-05 | Belum ada master ruang (GAP-08). |
| B-06 | Penyimpanan file belum per tenant (GAP-05); dokumen PMB harus memakai path builder sendiri. |
| B-07 | Jumlah kanal pembayaran bergantung paket langganan dan kontrak gateway. |
| B-08 | Integrasi dengan sistem pemerintah (PDDikti, SIM KIP Kuliah, SIMPONI, EMIS) bergantung pada akses resmi tenant; PMB tidak boleh mengandalkan endpoint yang belum terverifikasi. |
| B-09 | Kebijakan seleksi, biaya, refund, dan retensi adalah kewenangan tiap PT; sistem hanya menyediakan konfigurasi. |

---

## 3. Keputusan Desain

| ID | Keputusan | Alternatif yang ditolak | Alasan |
|---|---|---|---|
| KD-01 | Akun pendaftar di `pmb_applicant_accounts` (tenant-scoped, model `PmbApplicantAccount` dengan `HasApiTokens`, guard `applicant`). `users` dibuat saat konversi. | (a) Pakai `users` + `user_universities`; (b) ubah `users.email` menjadi unik per tenant | (a) bocor/bentrok lintas tenant, pendaftar gagal bercampur dengan staf; (b) mengubah tabel global inti, berisiko tinggi untuk modul lain |
| KD-02 | Status per siklus hidup di kolom/tabel terpisah (11 SM). Status ringkas diturunkan. | Satu kolom `status` | Brief; aktor & transisi berbeda; menghindari kombinasi status ilegal |
| KD-03 | Tagihan PMB di `pmb_invoices`; posting ke `invoices` saat konversi dengan `source_type='pmb_invoice'`. | Membuat `invoices.student_id` nullable dan menambah `applicant_id` | Tidak mengganggu laporan piutang mahasiswa yang ada; batas domain jelas |
| KD-04 | Selection run = snapshot immutable atas skema `locked`. | Hitung on-the-fly saat dibuka | Hasil berubah diam-diam saat bobot/nilai diedit |
| KD-05 | Pengesahan memakai approval engine; publikasi oleh job terjadwal. | Tombol "Publikasikan" langsung | SoD & anti-bocor |
| KD-06 | Konversi via outbox + idempotency + row lock sequence NIM. | Konversi sinkron di request persetujuan | Timeout & retry menghasilkan duplikasi |
| KD-07 | CBT tidak di MVP; Phase 2 generalisasi mesin ujian (examinee polymorphic) **atau** integrasi CBT eksternal [K]. | Refactor `exams` di MVP | Risiko regresi modul ujian akademik yang sedang dipakai |
| KD-08 | Formulir hibrida: field inti (identitas, pendidikan, pilihan) di kolom bertipe; field tambahan per jalur di `pmb_application_answers` (EAV terbatas) berdasarkan `pmb_form_fields`. | (a) Semua di JSON; (b) semua kolom tetap | (a) sulit dilaporkan/divalidasi; (b) tidak fleksibel per jalur |
| KD-09 | Prefiks tabel `pmb_`. | Nama tanpa prefiks seperti kandidat brief | Bentrok dengan `exams`, `payments`, `invoices`, `announcements` yang sudah ada |
| KD-10 | Enum disimpan sebagai `string` + PHP backed enum. | Enum MySQL | Pola terbaru CivitasOne (migrasi mengubah enum → string) memudahkan penambahan status |
| KD-11 | Kuota dikunci dengan `SELECT ... FOR UPDATE` pada baris `pmb_quotas` saat publikasi/promosi; counter `accepted_count` denormalisasi divalidasi ulang dari data keputusan saat rekonsiliasi. | Hitung `COUNT(*)` tanpa lock | Race condition saat promosi cadangan paralel |
| KD-12 | Pengecekan status publik (tanpa login) hanya menampilkan status ringkas dengan nomor pendaftaran + tanggal lahir + CAPTCHA; detail hanya di portal. | Cek dengan nomor pendaftaran saja | Enumerasi nomor pendaftaran membocorkan hasil |

---

## 4. Pertanyaan untuk Stakeholder

Prioritas: **P1** = memblokir desain/implementasi MVP; **P2** = dibutuhkan sebelum fitur terkait; **P3** = dapat diputuskan kemudian.

### 4.1 Konfigurasi & jalur

| ID | Pertanyaan | Prioritas | Default rancangan |
|---|---|---|---|
| Q-CFG-01 | Jalur apa saja yang dibuka tahun pertama, dan jalur mana yang memakai ujian? | P1 | Reguler (ujian), Prestasi (rapor+prestasi, tanpa ujian) |
| Q-CFG-02 | Apakah kuota per gelombang atau per periode? | P1 | Per periode × jalur × prodi × sistem kuliah, dapat dibatasi per gelombang via `wave_cap` opsional |
| Q-CFG-03 | Apakah kuota dipublikasikan di website? | P3 | Tidak; hanya "kuota tersedia/terbatas" |
| Q-CFG-04 | Sistem kuliah apa yang ada (reguler, karyawan, daring)? Apakah tarif berbeda? | P1 | Reguler & Karyawan, tarif berbeda |
| Q-CFG-05 | Apakah boleh mendaftar lebih dari satu jalur dalam satu periode? | P1 | Ya, maks 2 aplikasi aktif per periode |

### 4.2 Akun & data pribadi

| ID | Pertanyaan | Prioritas | Default |
|---|---|---|---|
| Q-ACC-01 | Login dengan email saja, atau juga nomor HP? Verifikasi via email link atau OTP? | P1 | Email + link verifikasi; OTP HP Phase 2 |
| Q-PDP-01 | Siapa DPO/penanggung jawab data pribadi tenant? Apakah sudah ada kebijakan privasi PMB? | P1 | Harus disediakan tenant sebelum go-live |
| Q-PDP-02 | Bagaimana perlakuan pendaftar di bawah 18 tahun (persetujuan wali)? | P2 | Centang persetujuan wali + data wali wajib bila usia < 18 |
| Q-PDP-03 | Masa retensi data pendaftar yang tidak diterima/tidak daftar ulang? | P2 | 2 tahun setelah periode ditutup |
| Q-PDP-04 | Data sensitif apa yang benar-benar dibutuhkan (agama, disabilitas, penghasilan orang tua, kesehatan)? Untuk tujuan apa? | P1 | Agama & nama ibu kandung dikumpulkan di daftar ulang untuk PDDikti; disabilitas opsional untuk akomodasi; penghasilan hanya jalur KIP/afirmasi/beasiswa |

### 4.3 Formulir & dokumen

| ID | Pertanyaan | Prioritas | Default |
|---|---|---|---|
| Q-FRM-01 | Daftar dokumen wajib per jalur? | P1 | Lihat [04 §E.2](04-spesifikasi-akun-formulir-dokumen.md#e2-katalog-dokumen-default) |
| Q-FRM-02 | Apakah NISN wajib untuk lulusan SMA/SMK/MA? | P2 | Wajib untuk lulusan ≥ 2 tahun terakhir, opsional selebihnya |
| Q-FRM-03 | Mata pelajaran rapor yang dinilai untuk jalur prestasi? Semester berapa? | P1 | Semester 1–5, semua mapel; rata-rata |
| Q-FRM-04 | Berapa lama waktu perbaikan dokumen? | P2 | 3 × 24 jam |

### 4.4 Pembayaran

| ID | Pertanyaan | Prioritas | Default |
|---|---|---|---|
| Q-PAY-01 | Payment gateway/bank mana? Sudah ada kontrak? | P1 | Satu gateway agregator (VA + QRIS) |
| Q-PAY-02 | Apakah transfer manual + unggah bukti diizinkan? | P1 | Ya, dengan verifikasi `finance_officer` |
| Q-PAY-03 | Berapa lama masa berlaku VA/tagihan pendaftaran? | P2 | 24 jam per transaksi; tagihan sampai batas bayar gelombang |
| Q-PAY-04 | Kebijakan refund biaya pendaftaran & biaya awal (pengunduran diri, tidak lulus ijazah, dll.)? | P1 | Pendaftaran: tidak dapat dikembalikan; biaya awal: sesuai SK rektor, via approval |
| Q-PAY-05 | Apakah biaya awal boleh dicicil? Minimum pembayaran untuk menjadi mahasiswa? | P1 | Boleh; minimum cicilan pertama |
| Q-PAY-06 | Apakah voucher/potongan biaya pendaftaran dipakai (mis. promosi sekolah mitra)? | P3 | Phase 2 |

### 4.5 Seleksi & ujian

| ID | Pertanyaan | Prioritas | Default |
|---|---|---|---|
| Q-SEL-01 | Berapa jumlah pilihan prodi per aplikasi? | P1 | 2 |
| Q-SEL-02 | Metode alokasi multi-pilihan: berbasis skor dengan prioritas pilihan (BR-28) atau pilihan pertama penuh dulu? | P1 | BR-28 (skor desc, pilihan prioritas tertinggi yang tersedia) |
| Q-SEL-03 | Komponen & bobot per jalur, ambang minimal per komponen/prodi? | P1 | Lihat contoh [06 §H.4](06-spesifikasi-seleksi-ujian-pengumuman.md#h4-rumus-penilaian) |
| Q-SEL-04 | Aturan tie-break? | P1 | BR-29 |
| Q-SEL-05 | Apakah status "cadangan" dipakai dan bagaimana promosi cadangan? | P1 | Dipakai; promosi manual oleh Admin PMB |
| Q-EXM-01 | Ujian luring di kampus atau juga di lokasi lain? Kapasitas ruang? | P2 | Kampus; kapasitas per ruang |
| Q-EXM-02 | CBT internal atau sistem eksternal? | P2 | Phase 2, diputuskan kemudian |
| Q-EXM-03 | Kebijakan ujian susulan (sakit/bentrok)? | P2 | Satu kali reschedule dengan persetujuan panitia |

### 4.6 Pengumuman & daftar ulang

| ID | Pertanyaan | Prioritas | Default |
|---|---|---|---|
| Q-ANN-01 | Siapa pejabat pengesah hasil (Rektor, WR I, Ketua Panitia)? Satu atau berjenjang? | P1 | Dua langkah: Ketua Panitia → WR I |
| Q-ANN-02 | Apakah pengumuman serentak per gelombang, atau per jalur? | P2 | Per gelombang-jalur |
| Q-REG-01 | Format NIM per tenant (kode prodi, tahun, jalur, urutan)? | P1 | `{YY}{KODE_PRODI}{SEQ:4}` |
| Q-REG-02 | Dokumen final daftar ulang (ijazah asli/legalisir, SKL)? Bagaimana jika ijazah belum terbit? | P1 | SKL diterima; ijazah dikejar oleh Akademik |
| Q-REG-03 | Status awal mahasiswa baru di Akademik? | P2 | `active` |
| Q-REG-04 | Berapa lama batas daftar ulang dan perpanjangan maksimal? | P1 | 14 hari; perpanjangan maks 7 hari |
| Q-REG-05 | Field PDDikti wajib yang dikumpulkan (NIK, nama ibu kandung, agama, kewarganegaraan, alamat lengkap, jenis tinggal, alat transportasi, dll.)? | P1 | Field wajib PDDikti dikumpulkan di daftar ulang |
| Q-REG-06 | Email akun mahasiswa: email pribadi pendaftar atau email institusi yang dibangkitkan? | P1 | Email institusi bila tenant punya domain email; fallback email pribadi (BR-38) |
| Q-REG-07 | Apakah tes kesehatan disyaratkan (mis. prodi kesehatan)? | P3 | Tidak, kecuali dikonfigurasi per prodi |

---

## 5. Ringkasan Jumlah

| Jenis | Jumlah |
|---|---:|
| Asumsi | 24 |
| Batasan | 9 |
| Keputusan desain | 12 |
| Pertanyaan stakeholder | 37 (P1: 24, P2: 10, P3: 3) |
