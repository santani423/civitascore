# 13 — Notifikasi, Keamanan & Audit, dan Kebutuhan Nonfungsional

[← Kembali ke dokumen utama](../RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md)

---

# A. Notifikasi dan Komunikasi

## A.1 Kebutuhan fungsional

| ID | Fitur | Label |
|---|---|---|
| FR-NTF-01 | Notifikasi otomatis per tahap via email (dan in-app) | E |
| FR-NTF-02 | Template per tenant/event/kanal, placeholder terbatas, pratinjau & kirim uji | R |
| FR-NTF-03 | Antrean, retry, dan riwayat pengiriman (`notification_logs`) | R |
| FR-NTF-04 | Preferensi kanal & aturan keamanan konten | R |
| FR-COM-01 | Quick chat pendaftar ↔ panitia | E |
| FR-COM-02 | Broadcast email & SMS ke segmen pendaftar | E |
| FR-COM-03 | Helpdesk/tiket untuk pemulihan akun & keluhan (integrasi Tata Usaha bila ada) | I |

## A.2 Katalog event notifikasi

Kanal: **E** email · **S** SMS/WA · **I** in-app (portal). Prioritas: **T** tinggi (dikirim segera, antrean prioritas) · **N** normal · **R** rendah (boleh ditunda/digabung).

| Event key | Pemicu | Penerima | Kanal | Prioritas | Isi pokok (tanpa data sensitif) |
|---|---|---|---|---|---|
| `pmb.account.registered` | Registrasi berhasil | Pendaftar | E | N | Selamat datang, langkah berikut |
| `pmb.account.verification_requested` | Registrasi / kirim ulang | Pendaftar | E (S bila OTP) | T | Tautan/OTP verifikasi (berlaku 24 jam / 5 menit) |
| `pmb.account.verified` | Verifikasi sukses | Pendaftar | I | R | Akun aktif |
| `pmb.account.registration_attempt` | Registrasi dengan email yang sudah terdaftar | Pemilik email | E | N | "Ada upaya pendaftaran dengan email Anda; abaikan bila bukan Anda" |
| `pmb.account.password_reset` | Lupa sandi | Pendaftar | E/S | T | Tautan/OTP reset |
| `pmb.account.security_alert` | Sandi/email diubah, akun terkunci | Pendaftar (email lama untuk ganti email) | E | T | Perubahan keamanan + cara mengamankan |
| `pmb.application.created` | Draft dibuat | Pendaftar | I | R | Jalur & tenggat |
| `pmb.application.incomplete_reminder` | Job harian: draft belum dikirim H-3, H-1 tutup | Pendaftar | E, I | N | Pengingat melengkapi + tenggat |
| `pmb.application.submitted` | Formulir terkirim | Pendaftar | E, I | N | Nomor pendaftaran, langkah berikut |
| `pmb.invoice.created` | Tagihan dibuat | Pendaftar | I | R | Nominal & tenggat |
| `pmb.payment.instructions` | Transaksi pending dibuat | Pendaftar | E | N | Kanal, nomor VA, nominal, kedaluwarsa |
| `pmb.payment.succeeded` | Tagihan paid / cicilan diterima | Pendaftar | E, I | T | Nominal, nomor kuitansi, langkah berikut |
| `pmb.payment.failed` / `pmb.payment.expired` | Transaksi gagal/kedaluwarsa, bukti ditolak | Pendaftar | E, I | N | Alasan umum, ajakan bayar ulang |
| `pmb.invoice.expired` | Tagihan lewat tenggat | Pendaftar | E | N | Konsekuensi & kontak |
| `pmb.document.rejected` / `pmb.application.revision_requested` | Petugas meminta perbaikan (digabung per aplikasi) | Pendaftar | E, S (opsional), I | T | "Ada dokumen/data yang perlu diperbaiki sebelum {tenggat}" — rincian alasan **hanya di portal** |
| `pmb.application.verified` | Verifikasi lolos | Pendaftar | E, I | N | Lolos administrasi, tahap seleksi |
| `pmb.application.rejected` | Ditolak administrasi | Pendaftar | E, I | N | Ajakan login untuk melihat alasan |
| `pmb.exam.scheduled` | Jadwal & sesi diterbitkan | Peserta | E, S, I | T | Tanggal, jam, lokasi, unduh kartu |
| `pmb.exam.rescheduled` | Perubahan sesi/jadwal | Peserta terdampak | E, S, I | T | Jadwal baru |
| `pmb.exam.reminder` | H-1 ujian | Peserta | E, S | N | Pengingat & tata tertib |
| `pmb.approval.requested` | Run diajukan pengesahan | Pengesah | E, I (staf) | T | Ringkasan & tautan admin |
| `pmb.result.published` | Batch publikasi | Pendaftar | E, S, I | T | "Hasil seleksi Anda telah tersedia, silakan login" (tanpa hasil di subjek/SMS) |
| `pmb.result.corrected` | Koreksi keputusan | Pendaftar terdampak | E, I | T | Hasil diperbarui, login |
| `pmb.waitlist.promoted` | Promosi cadangan | Pendaftar | E, S, I | T | Ada tawaran baru, batas daftar ulang |
| `pmb.re_registration.confirmed` | Terima tawaran | Pendaftar | E, I | N | Langkah daftar ulang, tagihan |
| `pmb.re_registration.deadline_reminder` | H-3, H-1, 6 jam | Pendaftar belum submitted | E, S, I | T | Sisa langkah & batas |
| `pmb.re_registration.revision_requested` | Petugas meminta perbaikan | Pendaftar | E, I | T | Login untuk detail |
| `pmb.re_registration.expired` / `.declined` / `.cancelled` | Status terminal | Pendaftar | E, I | N | Konsekuensi, info refund bila relevan |
| `pmb.student.enrolled` | Konversi sukses | Mahasiswa baru | E, S, I | T | NIM, aktivasi akun mahasiswa, info orientasi |
| `pmb.conversion.failed` | Konversi gagal/manual intervention | `pmb_admin` | E, I | T | Aplikasi & kode error |
| `pmb.refund.completed` | Refund selesai | Pendaftar | E | N | Nominal & tanggal |
| `pmb.chat.reply` | Panitia membalas | Pendaftar | I (E bila tidak aktif 1 jam) | N | "Ada balasan dari panitia" |
| `pmb.ops.alert` | Webhook signature gagal berulang, rekonsiliasi mismatch, antrean > SLA | `pmb_admin`, `finance_officer`, tim IT | E, I | T | Ringkasan teknis |

## A.3 Template

* Disimpan di `notification_templates` (unik per tenant × `event_key` × `channel`), diseed saat aktivasi modul (GAP-06).
* Placeholder yang diizinkan per event (allowlist), mis. `{{applicant_name}}`, `{{registration_number}}`, `{{wave_name}}`, `{{path_name}}`, `{{deadline}}`, `{{portal_url}}`, `{{amount}}`, `{{exam_date}}`, `{{exam_location}}`, `{{program_name}}`, `{{nim}}`, `{{university_name}}`, `{{contact}}`. Placeholder di luar allowlist ditolak saat simpan.
* Badan email di-escape (Blade `{{ }}`), tidak boleh berisi HTML dari input pendaftar.
* SMS ≤ 160 karakter, tanpa tautan pendek pihak ketiga (risiko phishing) — gunakan domain tenant.

## A.4 Pengiriman, retry, dan riwayat

| Aspek | Ketentuan |
|---|---|
| Pemicu | Listener domain event (dari outbox) → `NotificationDispatcher` → job per kanal |
| Antrean | Redis queue `notifications-high` (prioritas T) dan `notifications` (N/R) |
| Retry | 3 kali dengan backoff 1, 5, 30 menit untuk error sementara (timeout, 5xx); error permanen (alamat tidak valid, bounce keras) tidak di-retry |
| Deduplikasi | Kunci `event_key + notifiable_id + aggregate_id + version` disimpan 24 jam → mencegah notifikasi ganda saat event diproses ulang |
| Riwayat | `notification_logs` (status `queued/sent/failed/bounced`, `error_message`, `sent_at`); ditampilkan di timeline aplikasi (admin) dan riwayat notifikasi (portal, in-app) |
| Jam tenang | SMS/WA prioritas N/R tidak dikirim 21.00–06.00 waktu tenant (ditunda); prioritas T tetap dikirim |
| Batas broadcast | Throttle sesuai kuota penyedia; broadcast > 1.000 penerima dipecah batch |
| Preferensi | Email transaksional tidak dapat dimatikan; SMS/WA dapat dimatikan pendaftar kecuali OTP; pemasaran hanya bila consent `marketing_optional` |

## A.5 Prinsip keamanan konten

1. Tidak memuat NIK, tanggal lahir lengkap, nilai, alasan penolakan rinci, atau nominal refund ke rekening tertentu di SMS/WA.
2. Tidak melampirkan dokumen (LoA/kuitansi) di email; email berisi tautan ke portal (perlu login). Pengecualian [K]: kuitansi PDF tanpa data sensitif dapat dilampirkan.
3. Hasil seleksi tidak disebut di subjek email/SMS.
4. Tautan memakai domain tenant; tidak ada pengalihan terbuka (*open redirect*).
5. Quick chat: lampiran dipindai; petugas tidak boleh meminta kata sandi/OTP (pesan otomatis peringatan di UI chat).

---

# B. Keamanan, Privasi, dan Audit

## B.1 Autentikasi dan otorisasi

| Kontrol | Rancangan |
|---|---|
| Guard terpisah | Staf: `users` (Sanctum, yang ada). Pendaftar: `pmb_applicant_accounts` (Sanctum ability `applicant`). Middleware memastikan token pendaftar tidak dapat memanggil `/pmb/admin/*` dan sebaliknya |
| Kata sandi | Hash bawaan Laravel (bcrypt cost ≥ 12 atau Argon2id); kebijakan minimal 8 karakter + tolak sandi umum/bocor |
| MFA staf | Direkomendasikan wajib (TOTP) untuk `pmb_admin`, `finance_officer`, `pmb_approver`, `selection_committee` [R] — bergantung kemampuan modul Auth (follow-up) |
| Sesi | Token berumur terbatas; pencabutan saat reset sandi; daftar sesi |
| Otorisasi | RBAC + scope + kepemilikan ([08](08-rbac.md)); default deny; 404 untuk objek di luar scope (tidak membocorkan keberadaan) |
| Brute force | Rate limit & lockout ([11 §A.2](11-api-dan-integrasi.md#a2-grup-autentikasi-dan-rate-limit)); CAPTCHA |

## B.2 Proteksi aplikasi web

| Ancaman | Kontrol |
|---|---|
| SQL injection | Eloquent/Query Builder dengan binding; tidak ada raw SQL dengan input; kolom sort/filter dibatasi allowlist (`ListQuery` yang ada) |
| XSS | React escaping default; konten CMS & rich text disanitasi server (allowlist HTML, mis. HTMLPurifier) dan dirender dengan sanitasi ulang; CSP `default-src 'self'`, tanpa `unsafe-inline` untuk script |
| CSRF | API berbasis token Bearer (tanpa cookie sesi) untuk portal; bila SPA memakai cookie Sanctum → CSRF token wajib; `SameSite=Lax` |
| IDOR | Policy per model + query scope; ULID tidak dianggap rahasia |
| Mass assignment | FormRequest + `$fillable` eksplisit; status tidak pernah mass-assignable |
| SSRF | Tidak ada fitur fetch URL dari input; URL webhook keluar (CRM) di-allowlist |
| Open redirect | Parameter redirect hanya path relatif |
| Clickjacking | `X-Frame-Options: DENY` / CSP `frame-ancestors 'none'` |
| Header keamanan | HSTS, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` minimal (kamera hanya untuk halaman unggah/check-in) |
| Dependensi | `composer audit` & `npm audit` di CI; pembaruan keamanan bulanan |
| ReDoS | Regex validasi field dinamis dibatasi panjang & diuji (FR-CFG-10) |

## B.3 Validasi input di server

Semua input divalidasi oleh FormRequest/Rule di server — termasuk field dinamis (dibangun dari definisi template), unggahan (MIME berdasarkan isi), nominal (dari server, bukan klien), transisi status (state machine), dan tanggal (urutan). Validasi klien hanya untuk kenyamanan.

## B.4 Perlindungan file unggahan

| Kontrol | Rincian |
|---|---|
| Tipe | Allowlist per jenis dokumen; deteksi MIME dari isi; tolak file poliglot (ekstensi ≠ isi) |
| Ukuran | Batas per jenis + batas global request (mis. 20 MB) di web server |
| Nama | Nama asli hanya metadata; path = ULID |
| Penyimpanan | Bucket/disk privat, di luar web root, tanpa listing; enkripsi at-rest (SSE) |
| Pemindaian | ClamAV via job; file belum `clean` tidak dapat diunduh siapa pun; `rejected` dikarantina |
| Pemrosesan | Gambar di-*re-encode* (menghapus metadata EXIF & payload tersembunyi); PDF tidak dirender di server kecuali di sandbox |
| Unduhan | URL bertanda tangan 5 menit, `Content-Disposition: attachment` untuk non-gambar, `Content-Type` dari DB |

## B.5 Enkripsi, secret, dan kredensial

| Aspek | Rancangan |
|---|---|
| Transport | TLS 1.2+ (disarankan 1.3) untuk semua endpoint & koneksi ke penyedia; HSTS |
| At rest | Kolom sensitif `encrypted` cast (BR-40); disk/bucket terenkripsi; backup terenkripsi |
| Hash pencarian | `identity_hash` = HMAC-SHA256 dengan kunci per tenant (bukan hash polos — NIK berentropi rendah dan mudah di-*brute force*) |
| Secret | `.env`/secret manager; secret gateway per tenant disimpan terenkripsi; tidak pernah dikirim ke front-end atau log |
| Rotasi | `APP_KEY` dengan `APP_PREVIOUS_KEYS`; job re-enkripsi bertahap; kunci HMAC berversi (`identity_hash_version`) [R] |
| Log | Masking otomatis NIK, nomor HP, email (sebagian), token, header `Authorization`, payload kartu |

## B.6 Pembatasan akses data pribadi

* Data sensitif dimasking di daftar & detail; pembukaan memerlukan `pmb_sensitive.read` + tujuan; dicatat di `pmb_sensitive_access_logs`.
* Penguji hanya melihat data minimum (nama, foto, no. peserta).
* Fakultas/prodi hanya melihat pendaftar yang memilih prodinya; tidak melihat pilihan prodi lain pendaftar [K] (default: hanya melihat bahwa ada pilihan lain, tidak nama prodinya).
* Ekspor data pribadi dibatasi & dicatat (BR-42); file ekspor berlaku 24 jam.
* Lingkungan non-produksi memakai data sintetis/teranonimkan — dilarang menyalin data produksi pendaftar ke dev/staging.

## B.7 Kepatuhan UU No. 27 Tahun 2022 (Pelindungan Data Pribadi)

| Kewajiban | Penerapan dalam rancangan | Penanggung jawab |
|---|---|---|
| Dasar pemrosesan yang sah | Pemrosesan PMB: persetujuan eksplisit + pelaksanaan perjanjian/permintaan pendaftar; data untuk PDDikti: kewajiban hukum PT. Dicatat di `pmb_account_consents` | Tenant (pengendali), CivitasOne (prosesor) |
| Data spesifik (kesehatan, disabilitas, data anak, data keuangan pribadi) | Hanya dikumpulkan bila perlu (Q-PDP-04), opsional bila memungkinkan, terenkripsi, akses terbatas | Tenant |
| Data anak (< 18 tahun) | Persetujuan wali (A-04, Q-PDP-02) | Tenant |
| Transparansi | Pemberitahuan privasi PMB (tujuan, jenis data, retensi, penerima — mis. payment gateway, penyedia email/SMS, PDDikti) dikelola di `pmb_contents` berversi | Tenant |
| Minimisasi | Field sensitif tidak wajib tanpa dasar (brief & BR-40); pengumpulan bertahap (data PDDikti di daftar ulang, bukan di pendaftaran) | Desain |
| Hak subjek data | Akses & unduh data saya (POR-12), perbaikan (koreksi via petugas), penghapusan (hapus akun bila tidak ada kewajiban retensi), penarikan consent pemasaran; SLA respons sesuai ketentuan yang berlaku | Tenant + fitur |
| Retensi & pemusnahan | BR-41; job retensi bulanan menganonimkan akun/aplikasi yang lewat masa retensi dan menghapus file; laporan pemusnahan | Sistem + tenant |
| Keamanan | Seluruh B.1–B.6 | CivitasOne + tenant |
| Pemberitahuan kegagalan pelindungan | Prosedur insiden B.10 — pemberitahuan tertulis kepada subjek data dan lembaga paling lambat 3 × 24 jam sejak diketahui | Tenant (dibantu CivitasOne) |
| Perjanjian pemrosesan | Perjanjian pengendali–prosesor antara tenant & CivitasOne, serta dengan subprosesor (gateway, email/SMS, storage) | Manajemen |
| DPIA | Penilaian dampak sebelum go-live karena pemrosesan data dalam skala besar & data anak | Tenant + CivitasOne |
| Pejabat/petugas PDP | Ditunjuk tenant (Q-PDP-01) | Tenant |

## B.8 Audit log

| Yang dicatat | Tempat |
|---|---|
| Setiap transisi status (semua SM) | `pmb_application_status_histories` + `audit_logs` |
| Perubahan konfigurasi (old/new, alasan) | `audit_logs` |
| Perubahan nilai & override hasil | `pmb_score_revisions`, `pmb_selection_run_results.override_*`, `audit_logs` |
| Keputusan verifikasi dokumen/pembayaran | `pmb_document_reviews`, `pmb_manual_payment_proofs`, `audit_logs` |
| Approval | `approval_actions`, `approval_histories` (yang ada) |
| Akses data sensitif | `pmb_sensitive_access_logs` |
| Ekspor | `pmb_export_logs` |
| Login & sesi staf | `login_histories`, `user_sessions` (yang ada) |
| Login pendaftar | `audit_logs` (event login sukses/gagal, tanpa sandi) |
| Callback pembayaran | `pmb_payment_callbacks` |

Sifat: insert-only bagi aplikasi (tidak ada endpoint ubah/hapus); retensi audit ≥ retensi data terkait + 1 tahun [K]; dapat diekspor auditor; jam server NTP.

## B.9 Pencegahan manipulasi nilai/hasil dan rekonsiliasi pembayaran

| Risiko | Kontrol |
|---|---|
| Penguji mengubah nilai peserta lain | Policy keanggotaan sesi; nilai terkunci saat sesi dikunci |
| Nilai diubah setelah perhitungan | Run memakai salinan nilai; nilai terkunci saat run approved; koreksi via approval |
| Bobot diubah diam-diam | Skema terkunci & berversi; run menyimpan `selection_scheme_id` versi |
| Hasil diubah setelah pengesahan | Run results insert-only; `result_hash` diverifikasi saat publikasi; keputusan berversi |
| Satu orang menyusun & mengesahkan | SoD approval engine |
| Publikasi dini | Job terjadwal saja; percepatan butuh approval |
| Pembayaran palsu | Tanda tangan + double-check + rekonsiliasi harian; status klien diabaikan |
| Bukti transfer palsu/duplikat | Pencocokan mutasi, deteksi checksum duplikat, SoD |
| Penyalahgunaan hak admin | Audit, dashboard auditor, alert aksi kritis di luar jam kerja [R] |

## B.10 Penanganan insiden keamanan

1. **Deteksi:** alert (lonjakan login gagal, signature webhook gagal berulang, akses sensitif massal, ekspor besar, error 5xx), laporan pengguna.
2. **Triase (≤ 1 jam):** tentukan cakupan, data terdampak, tenant terdampak.
3. **Penahanan:** cabut token/kredensial, nonaktifkan akun/integrasi terdampak, tutup sementara wave path (saklar `is_open`) bila perlu.
4. **Pemberantasan & pemulihan:** perbaikan, rotasi secret, restore bila perlu (point-in-time).
5. **Notifikasi:** sesuai UU PDP (≤ 3 × 24 jam) oleh tenant sebagai pengendali, dibantu CivitasOne; komunikasi ke pendaftar terdampak.
6. **Pascainsiden:** laporan akar masalah, tindakan pencegahan, pembaruan runbook.

## B.11 Pengamanan integrasi API

Tanda tangan HMAC + timestamp (anti-replay, toleransi 5 menit) untuk webhook; double-check status; token klien berscope minimal untuk endpoint integrasi; allowlist IP bila tersedia; TLS; rate limit; logging permintaan integrasi (tanpa secret); rotasi secret per tenant.

---

# C. Kebutuhan Nonfungsional

> Semua target berikut adalah **usulan awal** berdasarkan asumsi volume A-18 dan **wajib divalidasi** bersama stakeholder & tim infrastruktur dengan data kapasitas aktual. Target bukan jaminan sistem.

| ID | Kategori | Kebutuhan | Indikator terukur (target usulan) | Cara uji |
|---|---|---|---|---|
| NFR-01 | Performa | Respons API portal & admin | P95 ≤ 500 ms untuk GET, ≤ 1.000 ms untuk POST non-unggah pada beban puncak; P99 ≤ 2 s | Uji beban k6/JMeter, APM |
| NFR-02 | Performa | Halaman publik | LCP ≤ 2,5 s (4G, perangkat menengah), TTFB ≤ 600 ms dengan cache | Lighthouse CI, RUM |
| NFR-03 | Performa | Perhitungan seleksi | Run untuk 10.000 aplikasi selesai ≤ 2 menit | Benchmark dengan data sintetis |
| NFR-04 | Performa | Publikasi hasil | 10.000 keputusan terbit ≤ 5 menit sejak jadwal; cek hasil menahan 2.000 pengguna bersamaan dengan P95 ≤ 1 s | Uji beban skenario pengumuman |
| NFR-05 | Skalabilitas | Horizontal | Web/API stateless (sesi & cache di Redis); worker queue dapat ditambah; tidak ada state lokal | Uji penambahan instance |
| NFR-06 | Skalabilitas | Volume | Mendukung ≥ 20.000 akun & ≥ 10.000 aplikasi per tenant per periode tanpa degradasi di atas NFR-01 | Uji beban dengan dataset 2× target |
| NFR-07 | Ketersediaan | Uptime | ≥ 99,5% bulanan; ≥ 99,9% selama jendela kritis (H-3 s.d. H+1 tutup pendaftaran, pengumuman, batas daftar ulang) | Monitoring uptime |
| NFR-08 | Ketersediaan | Pemeliharaan | Tanpa downtime terencana di jendela kritis (freeze deploy); deploy zero-downtime | Kalender operasional |
| NFR-09 | Keamanan | Kerentanan | 0 temuan kritis/tinggi terbuka saat go-live (OWASP ASVS L2 sebagai acuan) | Pentest & SAST/DAST |
| NFR-10 | Integritas transaksi | Pembayaran | 0 tagihan `paid` tanpa transaksi tervalidasi; 0 efek ganda dari callback berulang; selisih rekonsiliasi terdeteksi ≤ H+1 | Test otomatis idempotensi, laporan rekonsiliasi |
| NFR-11 | Konsistensi data | Kuota & NIM | 0 penerimaan melebihi kapasitas; 0 NIM/mahasiswa ganda pada uji konkurensi 50 proses paralel | Test konkurensi |
| NFR-12 | Kemudahan penggunaan | Pendaftar | ≥ 90% peserta uji menyelesaikan registrasi → kirim formulir tanpa bantuan; SUS ≥ 75 | Uji usabilitas 10–15 peserta |
| NFR-13 | Kemudahan penggunaan | Petugas | Median waktu verifikasi satu aplikasi lengkap ≤ 3 menit | Observasi UAT |
| NFR-14 | Aksesibilitas | WCAG | WCAG 2.1 AA pada halaman publik & portal; 0 pelanggaran kritis axe | axe-core CI + audit manual |
| NFR-15 | Kompatibilitas | Browser | 2 versi terakhir Chrome, Edge, Firefox, Safari; Chrome Android & Safari iOS 2 versi terakhir | Matriks uji |
| NFR-16 | Responsivitas | Layar | Publik & portal berfungsi penuh pada 360–1920 px tanpa scroll horizontal | Uji visual |
| NFR-17 | Observabilitas | Logging & metrik | Log terstruktur JSON dengan `request_id`, `university_id`; metrik (latensi, error rate, antrean, callback, konversi); alert ke on-call; tracing untuk alur pembayaran & konversi | Dashboard observabilitas |
| NFR-18 | Pemeliharaan | Kualitas kode | Mengikuti konvensi modul, Pint, PHPStan (`phpstan.neon` yang ada) level proyek tanpa error baru; ESLint/TS strict front-end | CI |
| NFR-19 | Backup & pemulihan | RPO/RTO | RPO ≤ 15 menit, RTO ≤ 4 jam; restore drill per kuartal lulus | Drill terdokumentasi |
| NFR-20 | Pengujian otomatis | Cakupan | Cakupan baris ≥ 80% untuk `Services` & state machine modul Admission; 100% endpoint memiliki test otorisasi; E2E alur utama hijau sebelum rilis | CI coverage |
| NFR-21 | Kemampuan audit | Jejak | 100% transisi status & perubahan nilai/konfigurasi memiliki entri audit dengan aktor, waktu, alasan (bila wajib) | Test + sampling auditor |
| NFR-22 | Privasi | Data sensitif | 100% kolom sensitif terenkripsi; 100% pembukaan tercatat | Test + review skema |
| NFR-23 | Lokalisasi | Bahasa & waktu | Bahasa Indonesia; waktu sesuai zona tenant; format Rupiah | Review UI |
