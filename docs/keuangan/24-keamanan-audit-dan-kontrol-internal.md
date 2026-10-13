# 24 — Keamanan, Audit, dan Kontrol Internal

> **Status:** rancangan. Disusun 2026-10-13.
> Kembali ke [README](README.md) · Hak akses: [03](03-role-dan-hak-akses.md) · Aturan: `KEU-BR-300`–`319` ([25](25-business-rules-dan-validasi.md))

Dokumen ini mengumpulkan kontrol keamanan dan pengendalian internal modul KEU: siapa boleh melakukan apa, bagaimana integritas transaksi dijaga, bagaimana data sensitif dilindungi, apa yang diaudit, berapa lama data disimpan, dan bagaimana aktivitas mencurigakan dideteksi. Mekanisme yang sudah ada di repository (RBAC kustom, `TenantScoped`, `audit_logs`, `file_uploads` privat, cast `encrypted`) dipakai dan diperketat, bukan diganti.

## Daftar Isi

1. [Ancaman Utama dan Kontrol](#1-ancaman-utama-dan-kontrol)
2. [Kontrol Akses](#2-kontrol-akses)
3. [Pemisahan Tugas](#3-pemisahan-tugas)
4. [Integritas Transaksi](#4-integritas-transaksi)
5. [Keamanan Pembayaran Online](#5-keamanan-pembayaran-online)
6. [Perlindungan Berkas](#6-perlindungan-berkas)
7. [Kriptografi, Masking, dan Log](#7-kriptografi-masking-dan-log)
8. [Katalog Audit](#8-katalog-audit)
9. [Pembatasan Akses Laporan dan Ekspor](#9-pembatasan-akses-laporan-dan-ekspor)
10. [Keamanan API dan Aplikasi](#10-keamanan-api-dan-aplikasi)
11. [Retensi Data](#11-retensi-data)
12. [Pemantauan Aktivitas Mencurigakan](#12-pemantauan-aktivitas-mencurigakan)
13. [Ringkasan Kontrol Internal](#13-ringkasan-kontrol-internal)
14. [Acceptance Criteria dan Test Case](#14-acceptance-criteria-dan-test-case)

---

## 1. Ancaman Utama dan Kontrol

| Ancaman | Contoh | Kontrol utama | Rujukan |
|---|---|---|---|
| Status lunas palsu | Memalsukan redirect "sukses", mengirim webhook palsu | Status hanya dari webhook bertanda tangan + konfirmasi provider | §5, [KEU-BR-091](25-business-rules-dan-validasi.md) |
| Pembayaran ganda tercatat | Webhook diulang/replay | Deduplikasi berlapis, unique constraint | [08 §8](08-rancangan-pembayaran-online-dan-gateway.md#8-idempotensi-duplikasi-dan-urutan-callback) |
| Bukti transfer palsu atau dipakai ulang | Unggah ulang bukti orang lain | Verifikasi terhadap mutasi, unique referensi, checksum | [09 §6](09-rancangan-pembayaran-tunai-dan-manual.md#6-pencegahan-duplikasi) |
| Kecurangan internal | Petugas memverifikasi/menyetujui transaksinya sendiri, refund ke rekening sendiri | SoD, persetujuan bertingkat, deteksi anomali | §3, §12 |
| Akses data mahasiswa lain (IDOR) | Mengganti id tagihan di URL | Kepemilikan dari sesi, 404 untuk data bukan milik | [KEU-BR-312](25-business-rules-dan-validasi.md) |
| Kebocoran lintas tenant | Id milik universitas lain | Global scope `TenantScoped`, `Rule::exists` bertenant | [KEU-BR-015](25-business-rules-dan-validasi.md) |
| Manipulasi nominal | Mengirim nominal tagihan dari klien | Nominal dihitung server dari tarif; validasi desimal; CHECK DB | §4 |
| Perubahan historis diam-diam | Edit nominal tagihan terbayar | Append-only, reversal, periode tertutup | §4 |
| Kebocoran kredensial/data sensitif | Log, respons API, ekspor | Enkripsi, `$hidden`, masking, audit ekspor | §7, §9 |
| Penegakan cekal dilewati | Memanggil API KRS langsung | Guard di backend | [17 §8](17-rancangan-cekal-keuangan.md#8-penegakan-di-backend) |
| Penyalahgunaan hak super admin | Tindakan finansial lintas tenant | Guard khusus + alasan + audit `forced_override` | [KEU-BR-313](25-business-rules-dan-validasi.md) |

## 2. Kontrol Akses

| Kontrol | Ketentuan |
|---|---|
| RBAC | Permission `resource.action` per endpoint ([03 §3](03-role-dan-hak-akses.md#3-katalog-permission)); middleware `permission:` + Policy per model ([KEU-BR-301](25-business-rules-dan-validasi.md)) |
| Cakupan data | `FinanceScope` (tenant, fakultas, prodi, bimbingan, kelas, diri sendiri) diterapkan di query ([03 §5](03-role-dan-hak-akses.md#5-cakupan-data-financescope)); data di luar cakupan → 404 |
| Isolasi tenant | `TenantScoped` + `tenant.access`; validasi `exists` selalu dengan `university_id`; berkas dan antrean membawa `university_id` |
| Mahasiswa | Hanya data miliknya; `student_id` tidak diterima dari klien ([KEU-BR-016](25-business-rules-dan-validasi.md)) |
| Visibilitas kolom | Nominal, rekening, dokumen UKT disaring di `JsonResource` ([03 §8](03-role-dan-hak-akses.md#8-visibilitas-tingkat-kolom)) |
| Super admin | Tindakan tulis keuangan oleh `super_admin` ditolak kecuali dengan header alasan eksplisit (*break-glass*), dicatat `forced_override`, dan tetap tunduk pada SoD ([KEU-BR-313](25-business-rules-dan-validasi.md), [KEU-Q-34](29-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-keu-q)) |
| Kedaluwarsa role | `user_roles.expires_at` (Ada) dipakai untuk penugasan sementara, mis. petugas loket musim registrasi |

## 3. Pemisahan Tugas

| Kontrol | Ketentuan |
|---|---|
| Guard | `SegregationOfDutiesGuard` di setiap aksi penyetujuan/verifikasi; 403 `SELF_APPROVAL_FORBIDDEN` ([KEU-BR-300](25-business-rules-dan-validasi.md)) |
| Mode workflow | Listener `ApprovalRequestApproved` memeriksa ulang bahwa tidak ada approver langkah yang sama dengan pembuat sebelum menerapkan efek ([03 §6](03-role-dan-hak-akses.md#6-persetujuan)) |
| Daftar pasangan | [03 §7](03-role-dan-hak-akses.md#7-pemisahan-tugas-sod) |
| Persetujuan bertingkat | Ambang nominal untuk refund, koreksi, pembukaan periode ([04 §5.4](04-master-data-dan-konfigurasi.md#54-default-financeapprovalthresholds)) |
| Penonaktifan SoD | Hanya bendahara, alasan wajib, `forced_override`, muncul di laporan audit dan memicu anomali ANM-09 |

## 4. Integritas Transaksi

| Kontrol | Ketentuan |
|---|---|
| Nominal dihitung server | Nominal tagihan dari tarif/skema; klien tidak mengirim nominal komponen bertarif. Nominal pembayaran manual diverifikasi terhadap mutasi |
| Aritmetika | Desimal (BCMath/`brick/money`), tidak pernah float; uang di API sebagai string ([KEU-BR-011](25-business-rules-dan-validasi.md)) |
| Validasi nominal | `> 0` untuk pembayaran/refund/termin; alokasi ≤ sisa dan ≤ dana tersedia; potongan ≤ komponen; CHECK DB sebagai lapis kedua ([19 §1.5](19-rancangan-database-dan-relasi.md#15-check-constraint)) |
| Idempotensi | `Idempotency-Key` pada operasi finansial ([20 §3.2](20-rancangan-api-dan-integrasi.md#32-idempotensi)), unique key domain ([19 §7.3](19-rancangan-database-dan-relasi.md#73-pencegahan-duplikasi-ringkas)) |
| Konkurensi | Kunci baris dengan urutan baku; optimistic locking untuk penyuntingan ([KEU-BR-010](25-business-rules-dan-validasi.md), [KEU-BR-013](25-business-rules-dan-validasi.md)) |
| Append-only | Alokasi, mutasi deposit, histori status, baris tagihan pasca-terbit; tidak ada endpoint `DELETE`/`UPDATE` nominal ([KEU-BR-007](25-business-rules-dan-validasi.md)) |
| Transisi status | Hanya lewat service domain; histori + audit dalam transaksi yang sama ([KEU-BR-013](25-business-rules-dan-validasi.md)) |
| Periode tertutup | `PERIOD_CLOSED` ([KEU-BR-014](25-business-rules-dan-validasi.md)) |
| Pemeriksaan harian | `finance:verify-integrity` (`INV-01`…`INV-14`) tanpa perbaikan otomatis ([19 §8](19-rancangan-database-dan-relasi.md#8-invarian-dan-query-pemeriksaan)) |

## 5. Keamanan Pembayaran Online

| Kontrol | Ketentuan |
|---|---|
| Verifikasi webhook | Tanda tangan/token sesuai provider dengan perbandingan waktu-konstan; secret per akun terenkripsi ([KEU-BR-302](25-business-rules-dan-validasi.md)) |
| Konfirmasi | Status `paid` dikonfirmasi `inquireStatus` bila didukung |
| Anti-replay | Stempel waktu ≤ 300 detik bila tersedia; deduplikasi `dedupe_key` ([KEU-BR-303](25-business-rules-dan-validasi.md)) |
| Tenant | Dari id akun pada URL, bukan payload ([KEU-BR-101](25-business-rules-dan-validasi.md)) |
| Lapisan jaringan opsional | Daftar IP provider; HTTPS wajib; ukuran body dibatasi |
| Redirect | Tidak pernah mengubah status ([KEU-BR-091](25-business-rules-dan-validasi.md)) |
| Nominal | VA nominal tertutup bila didukung; selisih → `pending_verification` ([KEU-BR-095](25-business-rules-dan-validasi.md)) |
| Data kartu | Tidak diproses atau disimpan; bila ada kanal kartu, hanya halaman hosted/tokenisasi provider ([KEU-A-09](29-glossary-dan-keputusan-bisnis.md#4-asumsi-keu-a)) |

## 6. Perlindungan Berkas

| Kontrol | Ketentuan ([KEU-BR-304](25-business-rules-dan-validasi.md)) |
|---|---|
| Penyimpanan | Disk `local` privat (FileManagement), tanpa URL publik; unduhan melalui stream berizin (pola `FileUploadController` yang ada) |
| Jenis & ukuran | Bukti bayar: `pdf/jpg/jpeg/png`, ≤ `finance.proof.max_size_mb` (5) dan ≤ `file.max_upload_size_mb` (10); dokumen UKT: `pdf/jpg/jpeg/png`; impor: `csv/xlsx` |
| Validasi isi | MIME diperiksa dari isi berkas (bukan hanya ekstensi); gambar diproses ulang (strip metadata EXIF) sebelum disimpan [R] |
| Integritas | Checksum SHA-256; deteksi duplikat |
| Malware | `VirusScanner` bila `file_upload.virus_scan_enabled`; berkas terdeteksi ditolak |
| Akses | Policy: pemilik (mahasiswa), staf dengan permission terkait; setiap unduhan `downloaded` |
| Penghapusan | Berkas bukti/dokumen tidak dihapus selama data induk dalam masa retensi; soft delete `file_uploads` hanya untuk unggahan yatim |

## 7. Kriptografi, Masking, dan Log

| Data | Perlakuan ([KEU-BR-305](25-business-rules-dan-validasi.md), [KEU-BR-306](25-business-rules-dan-validasi.md)) |
|---|---|
| Kredensial & secret webhook | Cast `encrypted` (kunci aplikasi); `$hidden`; tidak pernah di log/audit; rotasi didukung masa transisi secret lama |
| Nomor rekening kampus & penerima refund | `encrypted` + `*_hash` (HMAC-SHA256 dengan kunci terpisah dari `APP_KEY`, untuk unik/pencarian) + `*_last4` |
| Nomor rekening pengirim transfer | Hanya 4 digit akhir disimpan |
| Rotasi kunci aplikasi | Mengikuti mekanisme *previous keys* Laravel; uji dekripsi data lama sebelum rotasi |
| Masking tampilan | `•••• 1234`; nomor penuh hanya lewat aksi "lihat" yang diaudit `viewed_sensitive` |
| Audit | Kolom sensitif dimasking lewat `$auditMasked` (mekanisme yang ada) |
| Log aplikasi | Payload webhook tidak ditulis ke log aplikasi (sudah tersimpan di tabel); header `Authorization` disaring |
| Respons galat | Tidak membocorkan keberadaan data lintas tenant/mahasiswa (404 seragam), tidak menampilkan *stack trace* |

## 8. Katalog Audit

Tiga lapisan catatan ([KEU-BR-307](25-business-rules-dan-validasi.md)):

| Lapisan | Isi | Siapa melihat |
|---|---|---|
| Baris domain append-only | Alokasi, mutasi deposit, baris tagihan, denda, item rekonsiliasi | Staf sesuai izin |
| `finance_status_histories` | Setiap transisi status: dari/ke, pelaku, alasan, payload | Staf; versi tanpa nama staf untuk mahasiswa |
| `audit_logs` | Nilai lama/baru, pelaku, IP, perangkat, alasan, aksi (`AuditAction`) | Auditor, bendahara |

| Domain | Tindakan yang **wajib** diaudit | Alasan wajib |
|---|---|---|
| Tarif | Buat/ubah/hapus aturan draf, impor, ajukan, setujui/tolak, publikasi, arsip, revisi | Tolak |
| Tagihan | Buat, terbit, buang draf, koreksi, pembatalan, perpanjangan, voucher oleh staf, ekspor | Koreksi, pembatalan, perpanjangan, lepas voucher |
| Pembayaran | Catat, verifikasi (termasuk nominal berbeda & *override* duplikat), tolak, batal, reversal, realokasi, alokasi manual, konversi deposit, unduh bukti, cetak/ganti kuitansi | Tolak, reversal, realokasi, *override*, ganti kuitansi |
| Sesi kas | Buka, tutup (selisih), verifikasi, buka ulang | Selisih ≠ 0, buka ulang |
| Gateway | Akun & kredensial (tanpa nilai), kanal, kesehatan manual, proses ulang webhook | Proses ulang paksa |
| UKT | Penetapan (semua transisi), keberatan, akses dokumen & ringkasan penilaian | Cabut, tolak, koreksi |
| Beasiswa & voucher | Skema, penetapan, persetujuan, penangguhan, pencabutan, penerapan susulan, voucher | Cabut, tangguhkan |
| Denda | Aturan, pembebasan, koreksi, eksekusi job | Pembebasan, koreksi |
| Deposit & refund | Setiap mutasi, pembekuan, penutupan, penyesuaian, refund (semua transisi) | Bekukan, penyesuaian, refund |
| Cekal | Kebijakan, cekal manual, pengecualian, pembukaan manual, sinkronisasi ulang, *override* BAAK | Cekal manual, pengecualian, pembukaan, *override* |
| Periode | Mulai tutup, tutup, buka kembali | Buka kembali |
| Pengaturan | Setiap perubahan `finance.*` | Kunci sensitif |
| Akses sensitif | Lihat nomor rekening penuh, dokumen UKT, ekspor laporan | — |

Audit log tidak dapat diubah atau dihapus melalui aplikasi; tidak ada endpoint tulis untuk `audit_logs` ([KEU-BR-314](25-business-rules-dan-validasi.md)).

## 9. Pembatasan Akses Laporan dan Ekspor

Ringkasan dari [22 §5](22-rancangan-laporan-dan-dashboard.md#5-ekspor): permission `finance_reports.export` terpisah dari `.read`; scope diterapkan; laporan berisi data sensitif hanya untuk bendahara/auditor; setiap ekspor diaudit dengan filter dan jumlah baris; berkas ekspor privat dan kedaluwarsa 24 jam; *watermark*; batas jumlah ekspor bersamaan ([KEU-BR-308](25-business-rules-dan-validasi.md)).

## 10. Keamanan API dan Aplikasi

| Kontrol | Ketentuan |
|---|---|
| Autentikasi | Sanctum (Ada); sesi SPA dengan CSRF sesuai konfigurasi yang ada |
| Validasi masukan | Form Request untuk setiap endpoint tulis; `Rule::exists` bertenant; enum untuk status/tipe; tidak ada *mass assignment* kolom status/nominal |
| Pembatasan laju | [20 §11](20-rancangan-api-dan-integrasi.md#11-pembatasan-laju) ([KEU-BR-311](25-business-rules-dan-validasi.md)) |
| Kode publik | Kode verifikasi kuitansi 32 karakter acak kriptografis; tidak dapat ditebak; hasil termasking |
| Kesalahan | Pesan umum untuk pengguna; detail teknis hanya di log internal |
| Dependensi | Pustaka PDF/XLSX (`dompdf`, `openspout`) diperbarui berkala; input pengguna di PDF di-*escape* |
| Pengujian keamanan | Area `SEC` dan `RBAC` di [26](26-skenario-pengujian.md); uji IDOR per endpoint mahasiswa; uji isolasi tenant per endpoint staf |

## 11. Retensi Data

Default rancangan [K] ([KEU-Q-17](29-glossary-dan-keputusan-bisnis.md#3-pertanyaan-terbuka-keu-q), [KEU-BR-309](25-business-rules-dan-validasi.md)):

| Data | Retensi | Setelah retensi |
|---|---|---|
| Tagihan, baris, pembayaran, alokasi, kuitansi, deposit, refund, koreksi, denda, rekonsiliasi | Minimal 10 tahun setelah tahun buku (`finance.retention.years`), mengacu praktik kearsipan dokumen keuangan (UU 8/1997 tentang Dokumen Perusahaan), dikonfirmasi bagian hukum | Arsip/anonimisasi sesuai keputusan kampus; tidak dihapus otomatis tanpa persetujuan |
| `audit_logs` dan `finance_status_histories` KEU | Sama dengan data keuangan | Sama |
| Berkas bukti bayar, dokumen UKT | Mengikuti data induk | Sama |
| `payment_webhook_events.payload` | 400 hari (`finance.retention.webhook_payload_days`) | Payload diganti `{"purged": true}`; metadata tetap |
| `idempotency_keys` | 24 jam setelah kedaluwarsa | Dihapus job |
| `finance_outbox_events` `dispatched` | 90 hari | Dihapus job; `dead` tidak dihapus otomatis |
| Berkas ekspor laporan | 24 jam | Dihapus job |
| `receivable_snapshots` | Mengikuti data keuangan | — |

Penahanan hukum (*legal hold*): bendahara/auditor dapat menandai mahasiswa atau periode agar dikecualikan dari pembersihan [R].

## 12. Pemantauan Aktivitas Mencurigakan

Job `finance:detect-anomalies` (harian, dan *near real-time* untuk aturan kritis) mengevaluasi aturan berikut ([KEU-BR-310](25-business-rules-dan-validasi.md)). Hasil dikirim sebagai `finance.anomaly_detected` ke bendahara dan auditor, dicatat, dan muncul di laporan audit. Ambang adalah *contoh* [R].

| ID | Aturan | Tingkat |
|---|---|---|
| ANM-01 | Satu pengguna melakukan > 5 reversal/pembatalan dalam 7 hari | Tinggi |
| ANM-02 | Refund ke rekening penerima yang sama (`beneficiary_account_hash`) untuk ≥ 2 mahasiswa berbeda | Kritis |
| ANM-03 | Verifikasi dengan *override* peringatan duplikat > 3 kali/minggu oleh pengguna yang sama | Tinggi |
| ANM-04 | Verifikasi manual dengan nominal sebenarnya ≠ klaim pada > 20% verifikasi seorang staf | Sedang |
| ANM-05 | Percobaan *self-approval* yang ditolak guard | Sedang (kritis bila berulang) |
| ANM-06 | Lonjakan webhook `invalid_signature` (> 20/jam per akun) | Tinggi |
| ANM-07 | Lonjakan percobaan kode voucher gagal dari banyak akun | Sedang |
| ANM-08 | Tindakan sensitif (refund, reversal, pengaturan) di luar jam kerja tenant | Sedang |
| ANM-09 | `finance.sod.enabled` dimatikan atau ambang persetujuan dinaikkan | Tinggi |
| ANM-10 | Periode keuangan dibuka kembali | Tinggi |
| ANM-11 | Ekspor laporan berisi > 10.000 baris data mahasiswa oleh satu pengguna dalam sehari | Sedang |
| ANM-12 | Selisih kas berulang pada petugas yang sama (≥ 3 sesi dalam 30 hari) | Sedang |
| ANM-13 | Pembayaran tunai bernilai besar (≥ Rp25.000.000 *contoh*) dicatat menjelang tutup sesi | Sedang |
| ANM-14 | Pelanggaran invarian `INV-*` | Kritis |

Kebutuhan data: ANM-02 memerlukan kolom `refunds.beneficiary_account_hash` ([19 §3.13.2](19-rancangan-database-dan-relasi.md#3132-refunds--baru)).

## 13. Ringkasan Kontrol Internal

| Tujuan kontrol | Preventif | Detektif | Korektif |
|---|---|---|---|
| Hanya uang nyata yang tercatat | Webhook bertanda tangan, verifikasi mutasi, SoD | Rekonsiliasi settlement & bank | Reversal berizin |
| Tagihan benar | Tarif disetujui, pratinjau massal, snapshot | Validasi tarif & UKT, sengketa | Koreksi berizin |
| Tidak ada duplikasi | Unique constraint, idempotensi | `INV-*`, rekonsiliasi | Reversal, refund |
| Kas loket aman | Sesi per petugas | Penutupan & verifikasi sesi, ANM-12 | Catatan selisih, tindak lanjut |
| Potongan terkendali | Persetujuan, kuota, batas | Laporan RPT-10, evaluasi kelayakan | Pencabutan/penangguhan |
| Laporan dapat dipercaya | Definisi tunggal, periode tertutup | Uji konsistensi, `closing_summary` | Koreksi di periode terbuka |
| Akses tepat | RBAC, scope, SoD | Audit, anomali | Pencabutan role |

## 14. Acceptance Criteria dan Test Case

| Acceptance criteria | Test case |
|---|---|
| [KEU-AC-SEC-01](27-acceptance-criteria.md) Seluruh tindakan sensitif tercatat | [KEU-TC-SEC-004](26-skenario-pengujian.md), [KEU-TC-SEC-009](26-skenario-pengujian.md), [KEU-TC-SEC-010](26-skenario-pengujian.md) |
| [KEU-AC-SEC-02](27-acceptance-criteria.md) Isolasi tenant & data milik sendiri | [KEU-TC-SEC-001](26-skenario-pengujian.md), [KEU-TC-RBAC-005](26-skenario-pengujian.md) |
| [KEU-AC-SEC-03](27-acceptance-criteria.md) Webhook aman | [KEU-TC-PG-005](26-skenario-pengujian.md), [KEU-TC-PG-013](26-skenario-pengujian.md) |
| [KEU-AC-SEC-04](27-acceptance-criteria.md) Berkas terlindungi | [KEU-TC-SEC-002](26-skenario-pengujian.md), [KEU-TC-MAN-008](26-skenario-pengujian.md) |
| [KEU-AC-SEC-05](27-acceptance-criteria.md) Enkripsi & masking | [KEU-TC-SEC-003](26-skenario-pengujian.md), [KEU-TC-PG-016](26-skenario-pengujian.md) |
| [KEU-AC-SEC-06](27-acceptance-criteria.md) Anomali terdeteksi | [KEU-TC-SEC-006](26-skenario-pengujian.md) |
| [KEU-AC-SEC-07](27-acceptance-criteria.md) Retensi & imutabilitas audit | [KEU-TC-SEC-008](26-skenario-pengujian.md), [KEU-TC-SEC-007](26-skenario-pengujian.md) |
