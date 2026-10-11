# 14 — Error Handling, Edge Cases, Strategi Testing, dan Acceptance Criteria

[← Kembali ke dokumen utama](../RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md)

---

# A. Error Handling dan Edge Cases

| ID | Skenario | Penyebab | Dampak | Validasi pencegahan | Mekanisme pemulihan | Hasil yang diharapkan |
|---|---|---|---|---|---|---|
| EC-01 | Calon mahasiswa membuat akun ganda | Lupa akun lama, email berbeda | Pendaftaran ganda, kuota & statistik terdistorsi | Email unik per tenant; `identity_hash` unik; heuristik harian | Flag duplikat → petugas menonaktifkan akun sekunder & menarik aplikasinya; klaim identitas | Satu identitas = satu akun aktif; aplikasi duplikat tidak masuk seleksi |
| EC-02 | Pendaftaran dikirim dua kali | Double click, retry jaringan | Nomor pendaftaran ganda | `Idempotency-Key`; `lock_version`; transisi `draft→submitted` sekali; nomor diterbitkan dalam transaksi | Request kedua mengembalikan respons pertama | Satu nomor pendaftaran, satu history `submitted` |
| EC-03 | Pembayaran berhasil, callback terlambat | Gangguan gateway/jaringan | Pendaftar tidak dapat lanjut, tagihan tampak belum bayar | Polling cadangan 10 menit; tombol "Cek status" memicu double-check | Rekonsiliasi harian memperbaiki otomatis; `paid_at` memakai waktu gateway (relevan untuk tenggat) | Status menjadi `paid` ≤ 15 menit tanpa intervensi; tenggat dinilai dari `paid_at` |
| EC-04 | Callback diterima berulang | Retry gateway | Efek ganda (notifikasi, status) | Unique (`provider`, `provider_event_id`); transisi hanya maju; dedupe notifikasi | No-op | Satu perubahan status, satu notifikasi |
| EC-05 | Pembayaran berhasil, status aplikasi belum diperbarui | Worker gagal setelah transaksi `success` | Inkonsistensi tagihan/aplikasi | Perubahan transaksi, tagihan, `registration_payment_status` dalam **satu transaksi DB**; efek lanjut via outbox | Outbox di-retry; job konsistensi harian membandingkan tagihan vs status cermin | Konsisten; tidak ada `paid` yang tidak tercermin |
| EC-06 | Dokumen diganti setelah diverifikasi | Pendaftar mencoba mengganti | Data terverifikasi berubah | Dokumen `approved` terkunci (423); unggah hanya bila `revision_requested` | — | Ditolak dengan pesan; versi terverifikasi tetap |
| EC-07 | Pendaftar ubah prodi setelah batas | Berubah pikiran | Ketidakadilan, kuota | Pilihan terkunci setelah submit (BR-13) | Hanya Admin PMB dengan alasan sebelum `ranked` | Perubahan tercatat; setelah ranked ditolak |
| EC-08 | Kuota habis saat beberapa petugas memproses bersamaan (promosi cadangan/override) | Race condition | Penerimaan melebihi kapasitas | `SELECT ... FOR UPDATE` pada `pmb_quotas`; `accepted_count` divalidasi dalam transaksi; alokasi run bersifat batch tunggal | Job rekonsiliasi kuota harian memberi alert bila selisih | 0 penerimaan melebihi kapasitas; request kedua 409 `QUOTA_FULL` |
| EC-09 | Nilai diubah setelah pengesahan | Kesalahan input ditemukan / upaya manipulasi | Hasil tidak sah | Nilai terkunci saat run approved (423); run memakai salinan | Koreksi via approval + run koreksi + keputusan versi baru | Perubahan hanya via proses koreksi tercatat |
| EC-10 | Hasil dipublikasikan sebelum disetujui | Kesalahan operasional / bug | Kebocoran, hasil tidak sah | Publikasi hanya oleh job untuk run `approved`; scope `published()` di semua endpoint pendaftar; test | Bila bug terjadi: retract (status kembali `unpublished`), notifikasi koreksi, laporan insiden | Tidak mungkin melalui UI; test regresi menjaga |
| EC-11 | Calon mahasiswa tidak menyelesaikan daftar ulang | Lupa, mundur | Kursi kosong | Pengingat H-3/H-1/6 jam | Job `expired`; promosi cadangan | Kursi dapat diisi cadangan |
| EC-12 | Nomor mahasiswa gagal dibuat | Format salah, bentrok NIM manual, sequence error | Konversi tertahan | Pratinjau format; sequence ber-lock; unique NIM | Retry nomor berikutnya (≤ 5), lalu `manual_intervention` + alert | NIM unik atau konversi tertahan dengan jelas |
| EC-13 | Integrasi akademik timeout | Beban DB, deadlock | Mahasiswa belum terbentuk | Transaksi pendek; lease worker | Retry eksponensial; langkah idempoten | Konversi selesai tanpa duplikasi |
| EC-14 | Dua proses membuat mahasiswa untuk calon yang sama | Retry paralel, dua worker | Mahasiswa/NIM ganda | Unique `application_id` di konversi, unique `students.admission_application_id`, `FOR UPDATE SKIP LOCKED` | Proses kedua mendapat konflik unique → membaca hasil yang ada | Tepat satu mahasiswa |
| EC-15 | Pengguna tanpa hak mengakses data | URL ditebak, scope salah | Kebocoran data | Policy + query scope; 404 untuk di luar scope | Audit; alert pola akses mencurigakan | 404/403; tercatat |
| EC-16 | Jadwal/konfigurasi berubah saat proses berjalan | Admin mengubah gelombang/skema | Pendaftar berbeda aturan | BR-03; snapshot konfigurasi pada aplikasi; versi template & skema | Perpanjangan saja yang diizinkan, dengan notifikasi | Pendaftar yang ada tidak terdampak perubahan berbahaya |
| EC-17 | Pembayaran masuk untuk tagihan kedaluwarsa | VA dibayar mepet waktu kedaluwarsa | Dana masuk, aplikasi tertutup | Tenggat dinilai dari `paid_at` gateway | `needs_resolution` → terima & buka kembali atau refund (BR-18) | Tidak ada dana "hilang"; keputusan tercatat |
| EC-18 | Pembayaran ganda (dua kanal) | Pendaftar membayar VA dan QRIS | Kelebihan bayar | Batalkan pending lain saat membuat transaksi baru (bila didukung) | Rekonsiliasi `duplicate_payment` → refund otomatis diajukan | Kelebihan dikembalikan sesuai prosedur |
| EC-19 | Nominal dibayar ≠ tagihan | Transfer manual salah nominal | Status tidak jelas | VA nominal tetap (closed amount) bila didukung; manual: kode unik | Item `amount_mismatch`; petugas memutuskan (kurang → tagih selisih; lebih → refund selisih) | Tidak otomatis `paid` |
| EC-20 | File berisi malware | Unggahan berbahaya | Kompromi petugas | Pemindaian; file belum `clean` tidak dapat diunduh | Karantina, notifikasi pendaftar untuk unggah ulang, alert bila berulang | Petugas tidak pernah membuka file tak bersih |
| EC-21 | Petugas memverifikasi aplikasi yang sama bersamaan | Klaim kedaluwarsa, push + pull | Keputusan bertentangan | Klaim unik aktif; `lock_version` | Request kedua 409 | Satu keputusan |
| EC-22 | Pendaftar diterima di dua aplikasi | Dua jalur lolos | Dua kursi terpakai | Terima satu → lainnya otomatis `withdrawn` (A-17) | Kursi lain dibebaskan | Satu daftar ulang aktif |
| EC-23 | Gateway tidak tersedia saat hari terakhir | Gangguan penyedia | Pendaftar tidak dapat bayar | Circuit breaker; kanal alternatif; transfer manual | Perpanjangan batas bayar massal (approval) | Pendaftar punya jalur alternatif |
| EC-24 | Penguji tidak hadir / sesi batal | Force majeure | Peserta tanpa nilai | Penugasan cadangan | Reschedule massal + notifikasi; `excused` | Peserta dijadwalkan ulang, tidak dinilai gugur |
| EC-25 | Data identitas salah setelah verifikasi (mis. nama di ijazah berbeda) | Salah ketik | Data PDDikti salah (biaya PDM tinggi) | Validasi format & konsistensi dokumen saat verifikasi | Koreksi petugas dengan alasan + verifikasi ulang dokumen identitas | Data mahasiswa benar sebelum konversi |
| EC-26 | Email akun sudah ada di `users` saat konversi | Pendaftar adalah alumni/staf | Konflik akun | BR-38 | `failed` dengan kode `USER_EMAIL_CONFLICT` → admin memilih email institusi atau penautan terverifikasi | Akun mahasiswa terbentuk tanpa mengambil alih akun lain |
| EC-27 | Lonjakan trafik saat pengumuman | Ribuan pendaftar serentak | Lambat/timeout | Keputusan terbit berbasis snapshot; cache singkat cek hasil; autoscale; antrean notifikasi bertahap | — | NFR-04 terpenuhi |
| EC-28 | Jam server tidak sinkron | NTP gagal | Publikasi/batas waktu salah | NTP & alert drift > 1 detik | Koreksi jam; audit keputusan waktu | Batas waktu konsisten |

---

# B. Strategi Testing

Mengikuti pola yang ada: test modul di `app/Modules/Admission/Tests` (PHPUnit, `phpunit.xml`), database test terisolasi, factory per model.

| Jenis | Cakupan | Alat | Kriteria lulus |
|---|---|---|---|
| Unit | State machine (setiap transisi sah & ilegal), kalkulator skor (normalisasi, bobot, pembulatan, eligibility), alokasi BR-28 & tie-break (kasus seri, kuota 0, multi-pilihan), formatter NIM & nomor, evaluator aturan tampil field, kalkulator tagihan & cicilan, `ApplicationStageResolver` | PHPUnit | ≥ 80% baris pada Services/Domain; kasus contoh 06 §H.4.5 sebagai *golden test* |
| Integration | Service + DB: submit aplikasi (transaksi & snapshot), webhook → tagihan, verifikasi → seleksi, run → approval → publikasi, daftar ulang → konversi → Finance; isolasi tenant (`TenantScoped`) | PHPUnit + MySQL test | Semua alur utama hijau |
| Konkurensi | Promosi cadangan paralel, alokasi nomor, konversi paralel, klaim verifikasi, callback paralel | PHPUnit dengan proses paralel / test khusus | 0 duplikasi, 0 over-capacity (NFR-11) |
| API | Setiap endpoint: sukses, validasi, 401, 403 (permission), 404 (scope/kepemilikan), 409/422/423 domain, idempotensi, format `ApiResponse` | PHPUnit HTTP tests | 100% endpoint punya test otorisasi (NFR-20) |
| Kontrak integrasi | Adapter gateway dengan sandbox & fixture webhook (tanda tangan valid/invalid, duplikat, urutan terbalik); adapter email/SMS dengan fake | PHPUnit + sandbox | Semua skenario F.4 lulus |
| UI | Komponen (`FormRenderer`, `StatusBadge`, wizard, uploader), validasi klien, state empty/loading/error | Vitest + Testing Library | Komponen kritis tercakup |
| End-to-end | Pendaftar: daftar → verifikasi → isi → unggah → bayar (sandbox) → kirim; petugas verifikasi; panitia nilai → run → approval → publikasi; pendaftar daftar ulang → enrolled | Playwright | Skenario utama & gagal utama hijau di staging |
| Security | SAST (PHPStan + aturan keamanan, `composer audit`, `npm audit`), DAST (OWASP ZAP), pentest pihak ketiga sebelum go-live; uji IDOR lintas tenant & lintas akun; uji unggahan berbahaya | — | NFR-09 |
| Performance | Skenario: hari terakhir pendaftaran (submit + bayar), pengumuman (cek hasil), run seleksi 10.000 aplikasi, ekspor besar | k6 | NFR-01..04 |
| Aksesibilitas | axe-core otomatis + audit manual pembaca layar (NVDA/VoiceOver) halaman publik & portal | — | NFR-14 |
| UAT | Skenario per role oleh panitia nyata, data realistis (sintetis), satu siklus PMB simulasi penuh termasuk koreksi & promosi cadangan | Checklist UAT | Seluruh AC MVP diterima; tidak ada defek kritis/tinggi |
| Regression | Suite otomatis di CI pada setiap PR; regresi modul Academic/Finance/Approval yang disentuh (perubahan additive `students`, `invoices`) | CI | Hijau sebelum merge |

**Data uji:** factory untuk periode lengkap (gelombang, jalur, kuota, skema), generator 10.000 pendaftar sintetis (nama, NIK palsu valid format, nilai) untuk performa; **tidak** memakai data pribadi nyata.

---

# C. Acceptance Criteria

Format Given–When–Then. Kode AC dirujuk dari checklist ([15 §D](15-prioritas-risiko-traceability-checklist.md#d-checklist-implementasi)).

### AC-01 Registrasi akun

* **Sukses** — *Given* pengunjung di halaman daftar dengan email yang belum terdaftar · *When* mengisi data valid, mencentang kebijakan, dan lolos CAPTCHA · *Then* akun `unverified` dibuat, consent tercatat dengan versi dokumen, email verifikasi terkirim, dan halaman menampilkan instruksi cek email.
* **Email sudah ada** — *Given* email sudah terdaftar · *When* registrasi dikirim · *Then* respons tampil identik dengan sukses, tidak ada akun baru, dan pemilik email menerima email `pmb.account.registration_attempt`.
* **Belum verifikasi** — *Given* akun `unverified` · *When* mencoba membuat aplikasi · *Then* ditolak 403 dengan arahan verifikasi.

### AC-02 Pencegahan identitas ganda

* *Given* NIK X sudah terdaftar pada akun A · *When* akun B menyimpan profil dengan NIK X · *Then* sistem menolak dengan pesan netral tanpa menampilkan data akun A, dan menawarkan klaim identitas.

### AC-03 Pengiriman formulir pendaftaran

* **Sukses** — *Given* pendaftar login, gelombang terbuka, seluruh field & dokumen wajib lengkap, dan (bila disyaratkan) tagihan lunas · *When* mengirim formulir · *Then* server memvalidasi ulang seluruh data, menyimpan secara atomik, menerbitkan nomor pendaftaran unik, mengubah `form_status` menjadi `submitted`, menyimpan snapshot profil & consent, mencatat history, dan mengirim notifikasi.
* **Belum lengkap** — *Given* ada field wajib kosong atau dokumen wajib belum diunggah · *When* mengirim · *Then* respons 422 `INCOMPLETE_APPLICATION` berisi daftar kekurangan per langkah, status tetap `draft`, tidak ada nomor terbit.
* **Pengiriman berulang** — *Given* formulir sudah terkirim dengan `Idempotency-Key` K · *When* request yang sama dikirim lagi · *Then* respons identik dengan pertama, tidak ada nomor/history kedua.
* **Penyimpanan gagal** — *Given* terjadi kegagalan DB di tengah proses · *When* mengirim · *Then* seluruh perubahan dibatalkan (rollback), status tetap `draft`, nomor tidak terpakai, dan pendaftar melihat pesan untuk mencoba lagi.
* **Gelombang tutup** — *Given* `registration_closes_at` telah lewat · *When* mengirim · *Then* 422 `WAVE_CLOSED`.

### AC-04 Autosave & konflik

* *Given* formulir terbuka di dua tab · *When* tab kedua menyimpan dengan `lock_version` lama · *Then* 409 `VERSION_CONFLICT` dan UI menawarkan muat ulang tanpa menimpa data.

### AC-05 Unggah dokumen

* **Sukses** — *Given* persyaratan `REPORT_CARD` (PDF ≤ 5 MB) · *When* mengunggah PDF 3 MB yang bersih · *Then* versi baru tersimpan privat, dipindai, status `uploaded`, dan dapat dipratinjau setelah `clean`.
* **Tipe palsu** — *When* mengunggah file `.pdf` yang isinya executable · *Then* ditolak 422 tipe file.
* **Terkunci** — *Given* dokumen `approved` · *When* mengunggah ulang · *Then* 423 `DOCUMENT_LOCKED`.

### AC-06 Pembayaran via gateway

* **Sukses** — *Given* tagihan `unpaid` · *When* pendaftar memilih VA dan membayar, lalu gateway mengirim callback bertanda tangan valid yang dikonfirmasi API status · *Then* transaksi `success`, tagihan `paid`, kuitansi tersedia, notifikasi terkirim.
* **Callback ganda** — *When* callback yang sama diterima 3 kali · *Then* hanya satu perubahan status & satu notifikasi.
* **Tanda tangan salah** — *When* callback dengan tanda tangan tidak valid diterima · *Then* 401, status tidak berubah, callback tercatat `invalid_signature`.
* **Status dari klien** — *When* klien mengirim klaim "sudah bayar" · *Then* status tidak berubah kecuali double-check gateway mengonfirmasi.
* **Callback terlambat** — *Given* callback tidak pernah datang · *When* job polling berjalan · *Then* status diperbarui berdasarkan API gateway.

### AC-07 Pembayaran manual

* *Given* bukti transfer diunggah · *When* petugas keuangan menyetujui setelah mencocokkan mutasi · *Then* transaksi `success`, tagihan `paid`; *When* menolak dengan alasan · *Then* transaksi `failed` dan pendaftar dapat mencoba lagi.

### AC-08 Verifikasi administrasi

* **Verifikasi** — *Given* petugas mengklaim aplikasi dengan semua dokumen `approved`, pembayaran terpenuhi, dan tanpa flag · *When* menekan Verifikasi · *Then* `verification_status = verified`, `selection_status = eligible`, dokumen terkunci, history & audit tercatat, notifikasi terkirim.
* **Syarat belum terpenuhi** — *Given* ada dokumen belum disetujui · *When* Verifikasi · *Then* 422 dengan daftar `unmet`.
* **Perbaikan** — *When* petugas meminta perbaikan pada dokumen X · *Then* hanya dokumen X yang dapat diunggah ulang, tenggat tampil di portal; *Given* tenggat lewat · *Then* job mengubah menjadi `rejected` alasan `REVISION_EXPIRED`.
* **Tanpa hak** — *Given* pengguna tanpa `pmb_verification.decide` atau bukan pemilik klaim · *When* memutuskan · *Then* 403/409 dan tidak ada perubahan.

### AC-09 Penjadwalan ujian

* *Given* sesi berkapasitas 30 berisi 30 peserta · *When* petugas memindahkan peserta ke sesi itu · *Then* 409 `SESSION_FULL`.
* *Given* ruang R dipakai 08.00–10.00 · *When* membuat sesi di R 09.00–11.00 · *Then* 409 `SCHEDULE_CONFLICT`.
* *Given* jadwal dipublikasikan · *Then* peserta menerima notifikasi dan dapat mengunduh kartu dengan QR.

### AC-10 Input nilai

* *Given* penguji ditugaskan di sesi S · *When* menginput nilai peserta di sesi S dalam rentang · *Then* tersimpan dengan `entered_by`; *When* menginput untuk peserta sesi lain · *Then* 403; *Given* sesi dikunci · *Then* 423 `SCORES_LOCKED`.

### AC-11 Perhitungan seleksi

* **Golden test** — *Given* data contoh 06 §H.4.5 · *When* run dijalankan · *Then* skor A=80,20, B=81,00, C=76,20, E=77,00, D gugur; B & A diterima Informatika, E diterima SI, C cadangan di Informatika #1 dan SI #1.
* **Skema belum terkunci** — *When* run diminta · *Then* 422 `SCHEME_NOT_LOCKED`.
* **Perubahan bobot setelah run** — *Given* run `computed` · *When* admin membuat versi skema baru · *Then* hasil run lama tidak berubah; hasil baru hanya muncul pada run baru.
* **Seri tak terselesaikan** — *Given* dua pendaftar seri pada kursi terakhir · *Then* run tidak dapat diajukan sampai panitia menetapkan urutan dengan alasan.

### AC-12 Pengesahan & publikasi

* *Given* run diajukan oleh pengguna P · *When* P mencoba menyetujui · *Then* ditolak `SELF_APPROVAL_FORBIDDEN`.
* *Given* run disetujui dengan jadwal publikasi T · *When* pendaftar membuka halaman hasil sebelum T · *Then* tidak ada informasi keputusan; *When* T tercapai · *Then* job memverifikasi hash, keputusan `published`, kuota diperbarui, daftar ulang dibuat untuk yang diterima, LoA diterbitkan, notifikasi terkirim.
* *Given* hash tidak cocok · *Then* publikasi dibatalkan dan alert terkirim.

### AC-13 Koreksi hasil

* *Given* keputusan terbit · *When* koreksi disetujui · *Then* versi keputusan baru dibuat, versi lama tetap tersimpan (`superseded`), pendaftar terdampak menerima notifikasi `pmb.result.corrected`.

### AC-14 Promosi cadangan

* *Given* kuota Informatika penuh lalu satu penerima menolak tawaran · *When* admin mempromosikan cadangan · *Then* cadangan rank terendah menjadi `accepted` (versi baru), kuota terisi kembali, daftar ulang dibuat dengan tenggat individual; *When* dua admin mempromosikan bersamaan untuk satu kursi · *Then* hanya satu berhasil, lainnya 409.

### AC-15 Daftar ulang

* **Terima tawaran** — *Given* keputusan `accepted` dan dalam periode daftar ulang · *When* menerima tawaran · *Then* status `confirmed`, tagihan biaya awal dibuat, tawaran lain dalam periode ditarik.
* **Tidak lengkap** — *Given* checklist belum hijau · *When* mengirim · *Then* 422 dengan daftar `unmet_checks`.
* **Kedaluwarsa** — *Given* batas lewat dan belum submitted · *When* job harian berjalan · *Then* status `expired`, kursi dibebaskan, notifikasi terkirim.
* **Persetujuan final** — *Given* checklist lengkap dan pembayaran minimum terpenuhi · *When* petugas (bukan verifikator bukti bayar aplikasi itu) menyetujui · *Then* status `approved` dan konversi `pending` dibuat; *Given* petugas adalah verifikator bukti bayar · *Then* 403 SoD.

### AC-16 Pembentukan mahasiswa

* **Sukses** — *Given* daftar ulang `approved` · *When* worker memproses konversi · *Then* NIM unik terbit sesuai format, `students` & `users` terbentuk dengan prodi & tahun masuk benar, `student_status_histories` mencatat `active`, tagihan terposting ke Finance, daftar ulang `enrolled`, bukti registrasi & notifikasi terkirim.
* **Retry** — *Given* konversi gagal setelah `students` dibuat · *When* retry · *Then* tidak ada mahasiswa/NIM baru; hanya langkah yang belum selesai dijalankan.
* **Paralel** — *Given* dua worker memproses konversi yang sama · *Then* tepat satu mahasiswa terbentuk.
* **Gagal data** — *Given* email bentrok dengan `users` · *Then* status `failed` kode `USER_EMAIL_CONFLICT`, admin dapat memperbaiki dan mencoba lagi.

### AC-17 Isolasi tenant & scope

* *Given* admin tenant A · *When* mengakses aplikasi tenant B dengan ID valid · *Then* 404.
* *Given* admin prodi Informatika · *When* membuka daftar pendaftar · *Then* hanya aplikasi yang memilih Informatika yang tampil.

### AC-18 Data sensitif

* *Given* petugas dengan `pmb_sensitive.read` · *When* membuka NIK dengan tujuan · *Then* nilai tampil dan entri `pmb_sensitive_access_logs` tercatat; *Given* tanpa permission · *Then* hanya nilai tersamar.

### AC-19 Dashboard & laporan

* *Given* data uji dengan 100 submitted, 60 paid, 40 verified, 20 accepted, 15 enrolled · *When* membuka dashboard · *Then* KPI sesuai definisi M.2 (CR total = 15%, yield = 75%).
* *When* mengekspor daftar pendaftar · *Then* alasan wajib, kolom sesuai hak, entri log ekspor tercatat.

### AC-20 Konfigurasi setelah pendaftaran berjalan

* *Given* gelombang memiliki aplikasi `submitted` · *When* admin mengubah biaya pendaftaran · *Then* 409 `CONFIG_IN_USE`; *When* memperpanjang tanggal tutup dengan alasan · *Then* berhasil, tercatat, dan pendaftar draft dinotifikasi.

### AC-21 Cek status publik

* *Given* nomor pendaftaran & tanggal lahir benar · *When* cek status · *Then* status ringkas tampil (keputusan hanya bila sudah terbit); *Given* kombinasi salah · *Then* pesan generik "Data tidak ditemukan"; *Given* > 5 percobaan/menit · *Then* 429.
