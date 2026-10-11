# 04 — Spesifikasi Modul C (Akun), D (Formulir), dan E (Dokumen)

[← Kembali ke dokumen utama](../RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md)

Label: **[E]** eksplisit · **[I]** inferensi · **[R]** rekomendasi · **[K]** perlu konfirmasi.

---

# C. Registrasi dan Akun Calon Mahasiswa

## C.1 Model akun

* Tabel `pmb_applicant_accounts` (tenant-scoped, KD-01). Model `PmbApplicantAccount` memakai `HasApiTokens` (Sanctum); token ber-*ability* `applicant`. Tabel `personal_access_tokens` yang ada sudah polymorphic sehingga tidak perlu tabel token baru.
* Satu akun = satu orang. Satu akun dapat memiliki beberapa aplikasi (lintas gelombang/jalur/periode) sesuai BR-04.
* Profil identitas (`pmb_applicant_profiles`) melekat ke **akun**, bukan aplikasi, sehingga tidak diisi ulang di aplikasi berikutnya. Saat aplikasi dikirim, data profil disalin sebagai snapshot ke aplikasi (`pmb_applications.profile_snapshot`, json terenkripsi untuk bagian sensitif) agar data yang diverifikasi tidak berubah ketika profil diedit kemudian.

## C.2 Kebutuhan fungsional

| ID | Fitur | Spesifikasi | Label |
|---|---|---|---|
| FR-ACC-01 | Registrasi | Field: nama lengkap (sesuai ijazah), email, nomor HP (format E.164, default +62), kata sandi + konfirmasi, centang Kebijakan Privasi & Ketentuan (versi dicatat, BR-09), CAPTCHA. Sumber informasi opsional (dropdown + "lainnya"). Mode **assisted**: petugas loket membuat akun atas nama pendaftar (`created_by_user_id` diisi, email verifikasi dikirim ke pendaftar). | I |
| FR-ACC-02 | Verifikasi | Default: tautan verifikasi email bertanda tangan (berlaku 24 jam, sekali pakai). Opsi tenant: OTP 6 digit via SMS/WA (berlaku 5 menit, maks 5 percobaan, kirim ulang setelah 60 detik, maks 5 kirim/jam/nomor). Akun `unverified` dapat login tetapi hanya melihat halaman "verifikasi akun" (BR-08). | R |
| FR-ACC-03 | Login, logout, sesi | Login email/HP + kata sandi. Token Sanctum berumur 12 jam *sliding*, maksimal 5 token aktif per akun (token tertua dicabut). Logout mencabut token saat ini; "keluar dari semua perangkat" mencabut semua. Daftar sesi aktif (perangkat, IP kasar, waktu). | R |
| FR-ACC-04 | Lupa & reset kata sandi | Form email/HP → tautan/OTP reset (berlaku 60 menit). Respons selalu sama ("Jika akun terdaftar, instruksi telah dikirim") untuk mencegah enumerasi. Reset mencabut semua token. | R |
| FR-ACC-05 | Profil akun | Ubah nama tampilan, HP, kata sandi (perlu kata sandi lama). Ubah email: verifikasi ke email baru, notifikasi ke email lama. Perubahan nama lengkap & identitas setelah ada aplikasi `submitted` hanya melalui permintaan koreksi ke petugas. | R |
| FR-ACC-06 | Pencegahan duplikat | Lihat C.3. | R |
| FR-ACC-07 | Persetujuan | Tabel `pmb_account_consents`: `consent_type` (`privacy_policy`, `terms`, `data_processing_pmb`, `marketing_optional`, `guardian_consent`), `document_version`, `granted_at`, `ip`, `user_agent`, `revoked_at`. Persetujuan pemasaran **opsional & terpisah** (tidak dicentang default). | R |
| FR-ACC-08 | Perlindungan penyalahgunaan & pemulihan | Rate limit: registrasi 5/jam/IP, login 5 gagal/15 menit/akun → `locked` 15 menit (eksponensial), OTP sesuai FR-ACC-02. Deteksi kata sandi bocor (cek k-anonymity HIBP, opsional [R]). Pemulihan akun bila email/HP hilang: permintaan ke panitia dengan unggah identitas → petugas memverifikasi → ganti email (tercatat audit). | R |

## C.3 Pencegahan pendaftaran ganda tanpa menghalangi pendaftaran sah

**Masalah:** satu orang dapat membuat dua akun (email berbeda) dan mendaftar dua kali, atau satu akun membuat dua aplikasi ke jalur yang sama.

| Lapisan | Mekanisme |
|---|---|
| Akun | Email unik per tenant (BR-07). HP unik per tenant bila dipakai login. |
| Identitas | NIK (WNI) atau paspor+negara (WNA) diisi di profil; disimpan terenkripsi + kolom `identity_hash` = HMAC-SHA256(kunci tenant, nilai ternormalisasi). **Unique index** `(university_id, identity_hash)` pada `pmb_applicant_profiles` → satu identitas = satu akun. |
| Saat bentrok identitas | Pendaftar melihat pesan netral: "Identitas ini sudah terdaftar pada akun lain. Silakan masuk dengan akun tersebut atau hubungi panitia." **Tidak** menampilkan email akun lain. Opsi "Klaim identitas" → membuat tiket `pmb_duplicate_flags` untuk diverifikasi petugas (mis. pendaftar lupa akun lama). |
| Aplikasi | Unique `(applicant_account_id, wave_path_id)` untuk aplikasi non-`void`/`withdrawn` (diterapkan dengan kolom generated `active_key` — lihat [10 §5](10-database-erd-data-dictionary.md#5-strategi-penanganan-data-duplikat)). Batas aplikasi aktif per periode (BR-04) diperiksa di service dengan lock pada baris akun. |
| Heuristik | Job harian mendeteksi kemiripan nama + tanggal lahir + nama sekolah lintas akun (tanpa NIK, mis. WNA) → `pmb_duplicate_flags` status `open` untuk ditinjau; flag terbuka memblokir `verified` (BR-22). |
| Sah | Pendaftaran di jalur berbeda pada periode yang sama tetap diizinkan bila `pmb.allow_multi_path_per_period = true`; pendaftaran ulang di periode berikutnya selalu diizinkan dengan akun yang sama. |

## C.4 Hasil yang diharapkan

Pendaftar dapat membuat akun, memverifikasi, masuk, dan memulihkan akses secara mandiri; satu identitas tidak dapat memiliki dua akun; upaya enumerasi & brute force dibatasi.

---

# D. Formulir Pendaftaran

## D.0 Kebutuhan fungsional

| ID | Fitur | Rincian | Label |
|---|---|---|---|
| FR-FRM-01 | Formulir pendaftaran online per jalur & gelombang | Wizard bertahap, template berversi per wave path (D.1, D.3) | E |
| FR-FRM-02 | Data identitas | D.2.1 | R |
| FR-FRM-03 | Data pendidikan & nilai rapor | D.2.2 | R |
| FR-FRM-04 | Data pilihan akademik | D.2.3 | R |
| FR-FRM-05 | Data pendukung (orang tua/wali, kebutuhan khusus, prestasi, beasiswa/KIP) | D.2.4 | R |
| FR-FRM-06 | Pernyataan & persetujuan | D.2.5 | R |
| FR-FRM-07 | Draft, autosave, penguncian, koreksi, riwayat | D.4 | R |
| FR-FRM-08 | Sumber informasi pendaftar | D.2.4 (`info_source`) — dasar laporan sumber informasi | E |

## D.1 Alur formulir

1. Pendaftar memilih gelombang & jalur yang `open` → sistem memeriksa BR-04 → membuat `pmb_applications` (`form_status = draft`), mengikat `form_template_id` versi aktif dan menyalin `config_snapshot` (biaya, jadwal, `max_choices`, `payment_timing`).
2. Formulir bertahap (wizard): **Identitas → Pendidikan → Pilihan Prodi → Data Pendukung → Dokumen → Pembayaran → Pernyataan & Kirim**. Urutan langkah Pembayaran dan Pernyataan mengikuti `payment_timing`.
3. Setiap langkah dapat disimpan; tidak ada field wajib untuk menyimpan draft (BR-10). Indikator kelengkapan per langkah.
4. Kirim → validasi server penuh (BR-11) → transaksi DB: kunci data, terbitkan `registration_number`, snapshot profil, catat consent `data_processing_pmb`, history status, event `pmb.application.submitted`.

**Nomor pendaftaran:** `{KODE_PERIODE}-{KODE_JALUR}-{SEQ:6}` (mis. `PMB2027-REG-000123`), dibangkitkan dari sequence per (periode, jalur) dengan lock, unik per tenant. Diterbitkan saat **submit**, bukan saat draft — agar nomor tidak terbuang dan tidak dapat ditebak sebelum ada pendaftaran. Draft memakai ULID internal.

## D.2 Spesifikasi field inti

Legenda kolom **Wajib**: Y = wajib saat submit; T = opsional; K = kondisional (lihat kolom Kondisi). Semua validasi dijalankan di server (FormRequest/Rule objects); validasi di klien hanya untuk UX.

### D.2.1 Data identitas (di `pmb_applicant_profiles`)

| Field | Tipe | Wajib | Validasi | Kondisi / catatan |
|---|---|---|---|---|
| `full_name` | string(150) | Y | huruf, spasi, `'.-,`; tidak boleh semua kapital/angka; trim spasi ganda | Sesuai ijazah. Terkunci setelah `submitted` (BR-12) |
| `nationality` | string(2) ISO 3166 | Y | kode valid | Menentukan jenis identitas |
| `nik` | string(16), terenkripsi | K | 16 digit numerik; digit 7–12 konsisten dengan tanggal lahir (perempuan: tanggal + 40) — peringatan, bukan blokir [R]; unik via `identity_hash` | Wajib bila `nationality = ID` |
| `passport_number`, `passport_country` | string(20), string(2) | K | alfanumerik | Wajib bila bukan WNI |
| `birth_place` | string(100) | Y | — | — |
| `birth_date` | date | Y | usia 14–70 tahun pada `period.starts_on` (batas dapat dikonfigurasi) | Usia < 18 → seksi wali wajib & consent wali (Q-PDP-02) |
| `gender` | string enum `male`/`female` | K | — | Wajib hanya bila tenant mengaktifkan (`pmb.collect_gender`), mis. untuk asrama/PDDikti. PDDikti mensyaratkan jenis kelamin; dapat dipindah ke daftar ulang [K] |
| `email` | string | Y | dari akun, readonly | — |
| `phone` | string(20) | Y | E.164 | dari akun, dapat diubah |
| `address_line`, `rt`, `rw`, `village`, `district`, `city`, `province`, `postal_code` | string | Y (alamat, kota, provinsi); T (RT/RW/kode pos) | kota/provinsi dari master wilayah [R] | Data wilayah: referensi kode wilayah Kemendagri (perlu master `regions`, Phase 2; MVP teks bebas + dropdown provinsi) |
| `photo_file_id` | FK file | — | via dokumen `PHOTO` | Tidak di profil; lihat E |

### D.2.2 Data pendidikan (`pmb_applicant_educations`, 1..n per akun; aplikasi mereferensikan satu sebagai `primary_education_id`)

| Field | Tipe | Wajib | Validasi | Kondisi |
|---|---|---|---|---|
| `level` | enum `secondary`, `diploma`, `bachelor`, `master` | Y | — | Jalur S2 wajib `bachelor`; pindahan/RPL wajib riwayat PT |
| `institution_name` | string(200) | Y | — | Autocomplete dari referensi sekolah (Phase 2) |
| `institution_npsn` | string(8) | T | 8 digit | Untuk sekolah menengah |
| `school_type` | enum `sma`, `smk`, `ma`, `paket_c`, `luar_negeri`, `lainnya` | K | — | Wajib bila `level = secondary` |
| `major` | string(100) | Y | — | IPA/IPS/jurusan SMK/prodi asal |
| `graduation_year` | year | Y | 1960..(tahun periode) | Bila = tahun periode dan belum lulus → `is_graduating = true` (boleh, ijazah/SKL di daftar ulang) |
| `nisn` | string(10) | K | 10 digit | Q-FRM-02 |
| `student_id_previous`, `gpa`, `credits_earned`, `accreditation_previous` | string, decimal(3,2), smallint, string | K | IPK 0–4 | Wajib untuk `transfer`, `rpl`, `postgraduate` |
| `report_scores` | json `{semester: {subject: score}}` atau tabel `pmb_report_scores` [R] | K | 0–100 per nilai; semester sesuai skema | Wajib bila skema memakai komponen `report_card`; lihat [06 §H.4](06-spesifikasi-seleksi-ujian-pengumuman.md#h4-rumus-penilaian) |

### D.2.3 Data pilihan akademik (`pmb_application_choices`)

| Field | Tipe | Wajib | Validasi |
|---|---|---|---|
| `priority` | tinyint 1..`max_choices` | Y (≥ 1 pilihan) | unik per aplikasi; tidak boleh lompat (1,2 bukan 1,3) |
| `study_program_id` | FK | Y | ada baris `pmb_quotas` `is_offered` untuk (periode, jalur, prodi, sistem); jenjang sesuai `eligible_degree_levels` jalur |
| `study_system_id` | FK | Y | sama seperti di atas; bila `allow_cross_system_choice = false`, semua pilihan harus sistem yang sama |
| — | — | — | prodi yang sama dengan sistem sama tidak boleh dipilih dua kali |
| `quota_id` | FK | otomatis | diisi server dari kombinasi |

Jenjang, fakultas, jalur, dan gelombang **tidak** diisi ulang; jalur & gelombang sudah ditentukan saat aplikasi dibuat, jenjang & fakultas diturunkan dari prodi.

### D.2.4 Data pendukung

| Kelompok | Tabel/field | Wajib | Catatan |
|---|---|---|---|
| Orang tua/wali | `pmb_applicant_guardians`: `relationship` (`father`, `mother`, `guardian`), `full_name`, `phone`, `email`, `occupation`, `education_level`, `income_range` (rentang, bukan angka pasti), `is_deceased` | **Pendaftaran:** minimal satu kontak darurat (nama + HP) **[R]**. **Daftar ulang:** data ayah/ibu sesuai kebutuhan PDDikti (nama ibu kandung) | `income_range` hanya dikumpulkan bila jalur `kip_affirmation`/`scholarship` atau tenant memerlukannya (Q-PDP-04). Wajib bila pendaftar < 18 tahun |
| Kebutuhan khusus | `pmb_applicant_profiles.special_needs` (json kategori), `special_needs_note` | **Opsional** | Tujuan dijelaskan di form: "untuk menyiapkan akomodasi ujian". Data sensitif (BR-40), hanya terlihat panitia akomodasi & `pmb_admin` |
| Prestasi | `pmb_applicant_achievements`: `name`, `level` (`school`, `district`, `province`, `national`, `international`), `rank` (`1`,`2`,`3`,`finalist`,`participant`), `year`, `organizer`, `document_id` | K: wajib ≥ 1 untuk jalur `achievement` | Dokumen sertifikat wajib per prestasi |
| Beasiswa/KIP | `pmb_applications.kip_number`, `kip_status`, `scholarship_interest` (bool), `scholarship_program_code` | K: jalur `kip_affirmation` | FR-SPC-02 |
| Sumber informasi | `pmb_applications.info_source` (enum: `website`, `social_media`, `school_visit`, `expo`, `friend_family`, `alumni`, `maukuliah`, `search_engine`, `other`), `info_source_detail`, `referral_code` | Y [E] | Untuk laporan sumber informasi (FR-RPT-04) |
| Field tambahan jalur | `pmb_application_answers` | sesuai template | D.3 |

### D.2.5 Pernyataan dan persetujuan (dicatat di `pmb_account_consents` + `pmb_applications.declarations`)

| Pernyataan | Wajib | Teks dikelola di |
|---|---|---|
| Kebenaran data & kesediaan dibatalkan bila data palsu | Y | `pmb_contents` tipe `declaration`, berversi |
| Persetujuan pemrosesan data pribadi untuk PMB (tujuan, jangka waktu retensi, pihak penerima — mis. payment gateway) | Y | idem |
| Kesediaan mengikuti ketentuan seleksi | Y | idem |
| Persetujuan wali (bila < 18 tahun) | K | idem |
| Persetujuan lain dari PT | Sesuai template | idem |

## D.3 Mesin formulir dinamis

`pmb_form_fields`:

| Kolom | Keterangan |
|---|---|
| `section` | `identity`, `education`, `choices`, `supporting`, `custom_<n>` |
| `key` | snake_case unik per template; tidak boleh sama dengan field inti |
| `label`, `help_text`, `placeholder` | Teks |
| `type` | `text`, `textarea`, `number`, `date`, `select`, `multiselect`, `radio`, `checkbox`, `email`, `phone`, `url`, `file_ref` (merujuk jenis dokumen) |
| `options` | json `[{value,label}]` untuk tipe pilihan |
| `is_required` | bool |
| `validation` | json terbatas (allowlist): `min`, `max`, `min_length`, `max_length`, `regex` (diuji ReDoS-safe, panjang ≤ 200), `before`, `after` |
| `visibility_rule` | json: `{"all":[{"field":"nationality","op":"eq","value":"ID"}]}`; operator `eq`, `neq`, `in`, `not_in`, `gt`, `lt`, `filled`; dapat merujuk field inti, field tambahan, atau `path.category` |
| `is_sensitive` | bool → nilai dienkripsi & dimasking |
| `is_reportable` | bool → tersedia sebagai kolom laporan/ekspor |
| `sort_order` | int |

Validasi server membangun aturan Laravel dari definisi field; field tersembunyi (rule false) **dibuang** dari input dan tidak divalidasi. Nilai disimpan di `pmb_application_answers(application_id, form_field_id, value_text, value_json)`.

## D.4 Draft, autosave, perubahan setelah kirim, riwayat

| Aspek | Aturan |
|---|---|
| Draft | Aplikasi `draft` tersimpan tanpa batas waktu selama gelombang terbuka; draft yang tidak dikirim saat `registration_closes_at` → `void` oleh job (alasan `WAVE_CLOSED`), data tetap ada untuk laporan "formulir belum lengkap". |
| Autosave | Klien menyimpan per langkah setiap 20 detik bila ada perubahan dan saat pindah langkah (PATCH parsial, idempotent). Konflik multi-tab: kolom `lock_version` (optimistic locking) → respons 409 dengan versi terbaru; UI menawarkan muat ulang. |
| Setelah kirim | Terkunci (BR-12). Status `revision_requested` membuka **hanya** field/dokumen yang ditandai petugas (`pmb_review_notes.target_fields`). Pendaftar mengirim ulang → `submitted` (history mencatat `resubmission`). |
| Koreksi oleh petugas | `pmb_admin` dapat mengoreksi field dengan alasan wajib (mis. salah ketik nama) — audit mencatat nilai lama & baru. Koreksi NIK/nama setelah `verified` memerlukan verifikasi ulang dokumen identitas. |
| Pilihan prodi | BR-13. |
| Penarikan | Pendaftar dapat menarik aplikasi (`withdrawn`) sebelum keputusan dipublikasikan; biaya mengikuti BR-19. |
| Riwayat | `pmb_application_status_histories` untuk status; `audit_logs` untuk perubahan field (old/new, dengan nilai sensitif dimasking di log). |

## D.5 Hasil yang diharapkan

Formulir menyesuaikan jalur; draft tidak hilang; pengiriman hanya berhasil bila data valid di server; data yang sudah diverifikasi tidak berubah tanpa jejak.

---

# E. Pengelolaan Dokumen Persyaratan

## E.1 Kebutuhan fungsional

| ID | Fitur | Spesifikasi | Label |
|---|---|---|---|
| FR-DOC-01 | Unggah dokumen | Per persyaratan: unggah satu file (atau beberapa untuk tipe `multi`, mis. sertifikat prestasi). Progress bar, pratinjau (gambar/PDF), ganti file selama `draft`. | E |
| FR-DOC-02 | Validasi file | Server memeriksa ekstensi **dan** MIME hasil deteksi isi (magic bytes, `finfo`), ukuran ≤ `max_size_kb`, dimensi minimal untuk foto (mis. ≥ 400×600 px, rasio 3:4 ± 10%), PDF tidak terenkripsi & ≤ 20 halaman, nama file dinormalisasi (tidak dipakai sebagai path). | R |
| FR-DOC-03 | Penyimpanan aman | Disk privat (S3-compatible/lokal privat), path `universities/{university_id}/pmb/{period_code}/{application_id}/{document_type}/{ulid}.{ext}`; record `file_uploads` (`uploaded_by = null`, `fileable` → `pmb_document_versions`, `checksum` SHA-256, `status = pending`). Pemindaian antivirus (ClamAV) via job → `clean`/`rejected`. File `rejected` tidak dapat diunduh. Unduhan via URL bertanda tangan 5 menit setelah policy check. EXIF lokasi dihapus dari foto. | R |
| FR-DOC-04 | Verifikasi per dokumen | Petugas melihat dokumen berdampingan dengan data formulir; keputusan `approved` / `rejected` (kode alasan wajib + catatan) / `revision_requested`. Tercatat di `pmb_document_reviews`. | R |
| FR-DOC-05 | Unggah ulang & versi | Unggah ulang membuat `pmb_document_versions` baru (`version_no + 1`); `current_version_id` dipindah; versi lama tetap (BR-21). Pendaftar melihat alasan penolakan pada versi sebelumnya. | R |
| FR-DOC-06 | Penguncian | Dokumen terkunci saat `approved`, dan seluruh dokumen tahap `application` terkunci saat `verification_status = verified`. Dokumen tahap `re_registration` dibuka saat daftar ulang `in_progress` dan terkunci saat `approved`. | R |
| FR-DOC-07 | Akses & retensi | Akses: pemilik akun, petugas dengan permission & scope; dokumen `is_sensitive` hanya role dengan `pmb_sensitive.read`. Retensi: dokumen pendaftar yang tidak menjadi mahasiswa dihapus permanen (file + baris di-anonimkan) setelah `pmb.retention_years_applicant`; dokumen mahasiswa (`retention_class = student_record`) dipindahkan kepemilikannya ke record mahasiswa saat konversi. | R |

## E.2 Katalog dokumen default

Usulan awal untuk dikonfirmasi (Q-FRM-01).

| Kode | Dokumen | Format | Maks | Tahap | Jalur | Sensitif |
|---|---|---|---:|---|---|:-:|
| `PHOTO` | Pas foto latar polos | JPG, PNG | 1 MB | application | Semua | — |
| `ID_CARD` | KTP / Kartu Pelajar / Paspor | JPG, PNG, PDF | 2 MB | application | Semua | Ya |
| `FAMILY_CARD` | Kartu Keluarga | JPG, PNG, PDF | 2 MB | re_registration (application untuk KIP) | Semua | Ya |
| `REPORT_CARD` | Rapor semester 1–5 (gabung 1 PDF) | PDF | 5 MB | application | Prestasi, Reguler bila skema memakai rapor | — |
| `DIPLOMA_OR_SKL` | Ijazah / Surat Keterangan Lulus | PDF, JPG | 2 MB | re_registration (application untuk yang sudah lulus, opsional) | Semua | — |
| `TRANSCRIPT` | Transkrip nilai PT asal | PDF | 5 MB | application | Pindahan, Pascasarjana, RPL | — |
| `TRANSFER_LETTER` | Surat keterangan pindah / bebas tanggungan | PDF | 2 MB | application | Pindahan | — |
| `ACHIEVEMENT_CERT` | Sertifikat prestasi (multi) | PDF, JPG | 2 MB/file | application | Prestasi | — |
| `KIP_CARD` | Kartu KIP / bukti terdaftar KIP Kuliah | PDF, JPG | 2 MB | application | KIP/Afirmasi | Ya |
| `INCOME_STATEMENT` | Surat keterangan penghasilan / SKTM | PDF | 2 MB | application | KIP/Afirmasi/Beasiswa | Ya |
| `RECOMMENDATION` | Surat rekomendasi | PDF | 2 MB | application | Pascasarjana, Kerja sama | — |
| `WORK_EXPERIENCE` | Surat keterangan kerja / portofolio | PDF | 10 MB | application | RPL, Kelas karyawan (opsional) | — |
| `PARTNERSHIP_LETTER` | Surat penugasan instansi mitra | PDF | 2 MB | application | Kerja sama | — |
| `HEALTH_CERT` | Surat keterangan sehat | PDF | 2 MB | re_registration | Hanya bila dikonfigurasi per prodi (Q-REG-07) | Ya |
| `STATEMENT_LETTER` | Surat pernyataan bermeterai | PDF | 2 MB | re_registration | Sesuai kebijakan | — |

## E.3 Status dokumen

`missing` → `uploaded` → `in_review` → `approved` | `rejected` | `revision_requested` → (unggah ulang) `uploaded` … Definisi lengkap di [09 SM-03](09-workflow-dan-state-machine.md#sm-03-dokumen).

Kode alasan penolakan (dapat ditambah tenant): `UNREADABLE`, `WRONG_DOCUMENT`, `INCOMPLETE_PAGES`, `EXPIRED`, `NAME_MISMATCH`, `DATA_MISMATCH`, `NOT_LEGALIZED`, `SUSPECTED_FORGERY`, `PHOTO_NOT_COMPLIANT`, `OTHER`. Alasan `SUSPECTED_FORGERY` tidak ditampilkan verbatim ke pendaftar (ditampilkan "Dokumen tidak dapat diterima, hubungi panitia") dan memicu eskalasi ke `pmb_admin`.

## E.4 Hasil yang diharapkan

Hanya file valid dan bersih yang tersimpan; setiap keputusan verifikasi dokumen beralasan dan dapat ditelusuri versi demi versi; dokumen tidak dapat diganti diam-diam setelah disetujui.
