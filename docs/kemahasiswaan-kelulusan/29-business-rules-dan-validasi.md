# 29 — Business Rules dan Validasi

> **Status:** rancangan · Disusun 2026-10-13 (commit `47043ca`) · Kembali ke [README](README.md)
> **Katalog acuan aturan bisnis.** Dokumen fitur memakai ID yang sama. Bila teks berbeda, dokumen ini yang berlaku. Test case di [30](30-skenario-pengujian.md).

## 1. Format

Setiap aturan memiliki: **ID** · **Nama** · **Deskripsi** · **Pemicu** · **Validasi** · **Hasil yang diharapkan** · **Penanganan kegagalan** · **Modul** · **Test case**. Angka ambang di aturan merujuk ke **konfigurasi**, bukan nilai tetap.

Rentang ID: `KEM-BR` 001–019 poin, 020–029 aktivitas/anti-manipulasi, 030–039 disiplin, 050–059 beasiswa, 070–079 surat · `KEL-BR` 001–004 inti, 005–019 periode/aturan, 020–039 eligibility, 040–059 peserta/keputusan, 060–079 penomoran/dokumen · `WIS-BR` · `SKPI-BR` · `TRACER-BR` · `KARIR-BR` · `UMUM-BR` lintas modul.

ID yang ditetapkan kebutuhan bisnis dan dipakai persis: `KEM-BR-001`, `KEM-BR-002`, `KEL-BR-001`…`KEL-BR-004`, `SKPI-BR-001`, `TRACER-BR-001`…`TRACER-BR-003`, `KARIR-BR-001`, `KARIR-BR-002`.

## 2. Kemahasiswaan (KEM)

| ID | Nama | Deskripsi | Pemicu | Validasi | Hasil diharapkan | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| <a id="kem-br-001"></a>KEM-BR-001 | Poin setelah verifikasi | Poin hanya diberikan setelah proses verifikasi sesuai kebijakan kampus | Pengajuan disetujui | Status pengajuan `approved` dan aksi oleh verifikator berwenang | Entri ledger `award` dibuat dalam transaksi yang sama | Tanpa persetujuan tidak ada entri; upaya tulis langsung ditolak (tidak ada endpoint) | Poin | KEM-TC-001, 002 |
| <a id="kem-br-002"></a>KEM-BR-002 | Riwayat poin dipertahankan | Perubahan poin harus mempertahankan riwayat sebelumnya | Koreksi, cabut, banding | Tidak ada UPDATE/DELETE pada `student_point_ledgers` | Entri baru bertanda ± menunjuk entri asal | Upaya ubah entri → 405/tidak tersedia; uji checksum tabel | Poin | KEM-TC-008 |
| <a id="kem-br-003"></a>KEM-BR-003 | Aturan menurut tanggal kegiatan | Poin dihitung dari aturan yang berlaku pada tanggal kegiatan | Persetujuan | `effective_from ≤ activity_date < effective_until` | `activity_point_rule_id` = aturan tanggal kegiatan | Tanpa aturan → poin manual + alasan (AF-PNT-01) | Poin | KEM-TC-004 |
| <a id="kem-br-004"></a>KEM-BR-004 | Override poin terkendali | Ubah poin dalam rentang dengan alasan; di luar rentang perlu persetujuan manager | Verifikator mengubah poin | `min ≤ poin ≤ max` atau ada approval disetujui | Entri `is_override` + alasan (+ `approval_request_id`) | 409 `OVERRIDE_REQUIRES_APPROVAL` + approval dibuat | Poin | KEM-TC-005, 006 |
| <a id="kem-br-005"></a>KEM-BR-005 | Batas poin periode | Kelebihan dari batas periode tercatat, tidak dihitung | Penulisan entri | Ringkasan dikunci `FOR UPDATE` | `counted_points` = sisa kuota; kelebihan di `awarded_points` | Konflik kunci → retry otomatis | Poin | KEM-TC-007, 014 |
| <a id="kem-br-006"></a>KEM-BR-006 | Saldo turunan ledger | Ringkasan poin dapat dibangun ulang dan tidak diedit manual | Setiap entri; command rebuild | Tidak ada endpoint ubah ringkasan | Rebuild = nilai sama | Selisih saat rebuild dicatat audit dan dilaporkan | Poin | KEM-TC-015 |
| <a id="kem-br-007"></a>KEM-BR-007 | Master terpakai tidak dihapus | Kategori/aturan terpakai hanya dinonaktifkan | Hapus | Tidak ada pengajuan/ledger terkait | Hapus draf berhasil | 409 `CATEGORY_IN_USE` | Poin | KEM-TC-012 |
| <a id="kem-br-008"></a>KEM-BR-008 | Poin untuk yudisium | Hanya kategori `counts_for_graduation` dan kegiatan dalam masa studi | Perhitungan `graduation_counted_points` | Kategori & tanggal | Total yudisium ≤ total keseluruhan | — | Poin, Yudisium | KEM-TC-016 |
| <a id="kem-br-009"></a>KEM-BR-009 | Koreksi beralasan | Koreksi wajib alasan, menunjuk entri asal, dan persetujuan bila menambah di atas rentang | Koreksi | `reason` ada; `reverses_entry_id` valid | Entri `correction` | 422 tanpa alasan | Poin | KEM-TC-008 |
| <a id="kem-br-010"></a>KEM-BR-010 | Pencabutan poin | Bukti tidak sah membatalkan seluruh poin aktif pengajuan | Cabut | Pengajuan `approved` | Entri `revocation`; pengajuan `revoked`; eligibility stale; SKPI `source_revoked` | Transisi tidak sah → 409 | Poin, Aktivitas | KEM-TC-009, 029 |
| <a id="kem-br-011"></a>KEM-BR-011 | Banding poin | Dalam batas waktu, satu banding aktif per pengajuan | Mahasiswa mengajukan | `appeal_window_days`; `active_key` unik | Banding `submitted` | 422 `APPEAL_WINDOW_CLOSED` / 409 duplikat | Poin | KEM-TC-010, 011 |
| <a id="kem-br-012"></a>KEM-BR-012 | Aturan tidak surut | Perubahan aturan tidak mengubah poin yang sudah diberikan kecuali rekalkulasi disetujui | Publikasi aturan | — | Entri lama tetap | Rekalkulasi tanpa approval ditolak | Poin | KEM-TC-017 |
| <a id="kem-br-013"></a>KEM-BR-013 | Status yang boleh mengajukan | Hanya status di `allowed_student_statuses` | Buat/kirim pengajuan | Status mahasiswa | Pengajuan tersimpan | 409 `STUDENT_STATUS_NOT_ALLOWED` | Aktivitas | KEM-TC-018 |
| <a id="kem-br-020"></a>KEM-BR-020 | Bukti wajib lengkap | Bukti wajib per kategori terunggah & `clean` | Kirim | Jenis bukti & status berkas | `submitted` | 422 | Aktivitas | KEM-TC-020 |
| <a id="kem-br-021"></a>KEM-BR-021 | Tipe & ukuran bukti | pdf/jpg/jpeg/png, ≤ batas | Unggah/kirim | Deteksi MIME konten | Diterima | 422 | Aktivitas | KEM-TC-021 |
| <a id="kem-br-022"></a>KEM-BR-022 | Duplikasi bukti | Checksum sama: mahasiswa sama ditolak, mahasiswa lain diberi flag | Kirim | `activity_evidences.checksum` | Flag `shared_evidence` | 409 `DUPLICATE_EVIDENCE` | Aktivitas | KEM-TC-019, 023 |
| <a id="kem-br-023"></a>KEM-BR-023 | Kegiatan ganda | `dedupe_key` aktif unik | Kirim | Unique `active_dedupe_key` | — | 409 `DUPLICATE_SUBMISSION` | Aktivitas | KEM-TC-022 |
| <a id="kem-br-024"></a>KEM-BR-024 | Tanggal & batas pengajuan | Tidak di masa depan, tidak sebelum masa studi, dalam window | Kirim | Tanggal (zona tenant) | — | 409 `SUBMISSION_WINDOW_CLOSED` / 422 | Aktivitas | KEM-TC-027 |
| <a id="kem-br-025"></a>KEM-BR-025 | Satu klaim per peran event | Satu pengajuan aktif per event × mahasiswa × peran | Klaim | `active_event_key` | — | 409 | Aktivitas | KEM-TC-026 |
| <a id="kem-br-026"></a>KEM-BR-026 | Tidak memverifikasi milik sendiri | Verifikator ≠ pengaju/pembuat event | Verifikasi | SoD guard | — | 403 `SELF_APPROVAL_FORBIDDEN` | Aktivitas | KEM-TC-028 |
| <a id="kem-br-027"></a>KEM-BR-027 | Alasan & batas revisi | Tolak/kembalikan beralasan; revisi ≤ `max_revisions` | Tolak/kembalikan/kirim ulang | `note` wajib; `revision_count` | — | 422; melewati batas → `rejected` otomatis | Aktivitas | KEM-TC-024, 025 |
| <a id="kem-br-028"></a>KEM-BR-028 | Verifikasi penyelenggara | Kategori bertanda wajib menunjuk event `verified` | Kirim | `activity_event_id` & status | — | 422 | Aktivitas | KEM-TC-039 |
| <a id="kem-br-029"></a>KEM-BR-029 | Penarikan terbatas | Tarik hanya sebelum diputus | Tarik | Status ∈ {submitted, revision_requested} | `withdrawn` | 409 `INVALID_STATUS_TRANSITION` | Aktivitas | KEM-TC-040 |
| <a id="kem-br-030"></a>KEM-BR-030 | Laporan bukan sanksi | Laporan/kasus belum diputus tidak berdampak | Pembacaan oleh modul lain | Reader hanya mengembalikan keputusan `final` | Tidak ada efek layanan | — | Disiplin | KEM-TC-030 |
| <a id="kem-br-031"></a>KEM-BR-031 | Sanksi dari keputusan final | Hanya keputusan final hasil workflow berwenang | Finalisasi keputusan | Approval `approved` | `final`; cekal (bila ada) diproyeksikan | Tanpa approval → tetap `pending_approval` | Disiplin | KEM-TC-033 |
| <a id="kem-br-032"></a>KEM-BR-032 | Hak didengar | Draf keputusan butuh catatan pemeriksaan/pemanggilan patut | Ajukan draf | `violation_case_hearings` | — | 409 `HEARING_REQUIRED` | Disiplin | KEM-TC-032 |
| <a id="kem-br-033"></a>KEM-BR-033 | Sanksi aktif turunan | Aktif = final ∧ dalam masa berlaku | Pembacaan | Tanggal (zona tenant) | Berakhir otomatis setelah `effective_until` | — | Disiplin | KEM-TC-041 |
| <a id="kem-br-034"></a>KEM-BR-034 | Keberatan | Dalam batas waktu; sanksi tetap berlaku kecuali ditangguhkan | Ajukan keberatan | `appeal_window_days`, satu aktif | Keberatan `submitted` | 422 lewat batas | Disiplin | KEM-TC-034, 042 |
| <a id="kem-br-035"></a>KEM-BR-035 | Keputusan tidak diedit | Perubahan = keputusan baru/annul | Koreksi/keberatan | Tidak ada PUT pada `final` | `superseded`/`annulled` + baru | 409 | Disiplin | KEM-TC-034, 035 |
| <a id="kem-br-036"></a>KEM-BR-036 | Akses terbatas | Permission eksplisit; identitas pelapor rahasia | Akses | Policy | — | 403; identitas disembunyikan | Disiplin | KEM-TC-036, 037 |
| <a id="kem-br-037"></a>KEM-BR-037 | Poin pelanggaran terpisah | Tidak mengurangi poin aktivitas kecuali kebijakan | Keputusan dengan poin | Setting tenant | Ledger aktivitas tidak berubah | — | Disiplin, Poin | KEM-TC-043 |
| <a id="kem-br-038"></a>KEM-BR-038 | Dampak yudisium terkendali | Hanya via syarat terkonfigurasi atau `restricted_services` keputusan final | Eligibility | Checker/cekal | — | — | Disiplin, Yudisium | KEM-TC-030, 033 |
| <a id="kem-br-039"></a>KEM-BR-039 | Keputusan dibatalkan tidak tampil | Tidak di surat/SKPI/laporan sanksi aktif | Penerbitan/laporan | Status `annulled` dikecualikan | — | — | Disiplin | KEM-TC-044 |
| <a id="kem-br-050"></a>KEM-BR-050 | Syarat buka program | Sumber dana, periode, kuota, ≥1 persyaratan | Buka program | Kelengkapan | `open` | 422 | Beasiswa | KEM-TC-057 |
| <a id="kem-br-051"></a>KEM-BR-051 | Satu pendaftaran per program | Unique (`scholarship_id`, `student_id`) (Ada) | Daftar | Unique | — | 409 | Beasiswa | KEM-TC-058 |
| <a id="kem-br-052"></a>KEM-BR-052 | Kuota penerima | Award aktif ≤ kuota, dengan kunci | Persetujuan batch | `FOR UPDATE` program | — | 409 `QUOTA_EXCEEDED` tanpa perubahan | Beasiswa | KEM-TC-051 |
| <a id="kem-br-053"></a>KEM-BR-053 | Penetapan berwenang | Penetapan/perpanjangan/penghentian disetujui pejabat; pengusul ≠ penyetuju | Batch/perubahan | Workflow + SoD | — | 403 `SELF_APPROVAL_FORBIDDEN` | Beasiswa | KEM-TC-052 |
| <a id="kem-br-054"></a>KEM-BR-054 | Penerimaan ganda | Dibatasi `stacking_policy` | Penetapan | Award aktif lain | — | Ditolak kecuali izin beralasan | Beasiswa | KEM-TC-055 |
| <a id="kem-br-055"></a>KEM-BR-055 | Sumber kebenaran | Scholarship = penerima/periode; KEU = potongan; sinkron via event | Perubahan award | Tidak ada tulis lintas modul | Event idempoten | Kegagalan event → retry + rekonsiliasi | Beasiswa, KEU | KEM-TC-053 |
| <a id="kem-br-056"></a>KEM-BR-056 | Evaluasi tidak otomatis menghentikan | Gagal evaluasi → usulan keputusan | Job evaluasi | — | `scholarship_award_evaluations.failed` | — | Beasiswa | KEM-TC-059 |
| <a id="kem-br-057"></a>KEM-BR-057 | Penghentian tidak surut | Berlaku mulai term efektif | Terminate | Term efektif ≥ term berjalan | Tagihan lunas tidak berubah | — | Beasiswa, KEU | KEM-TC-060 |
| <a id="kem-br-058"></a>KEM-BR-058 | Perubahan status memicu evaluasi | Cuti/DO/lulus/mundur | `student_status_histories` | — | Evaluasi luar jadwal | — | Beasiswa | KEM-TC-054 |
| <a id="kem-br-059"></a>KEM-BR-059 | Data ekonomi terlindungi | Terenkripsi, akses terbatas | Simpan/baca | Cast `encrypted`; policy | `viewed_sensitive` | 403 | Beasiswa | KEM-TC-056 |
| <a id="kem-br-070"></a>KEM-BR-070 | Surat aktif untuk status berhak | Status di setting pada term berjalan | Ajukan/terbit | Status & term | — | 409 | Surat | KEM-TC-070, 071 |
| <a id="kem-br-071"></a>KEM-BR-071 | Nomor sekali | Dialokasikan saat terbit, tidak dipakai ulang | Terbit | Skema & sequence | Nomor unik | 409 | Surat, Dokumen | KEM-TC-070, KEL-TC-061 |
| <a id="kem-br-072"></a>KEM-BR-072 | Isi dibekukan | Snapshot + PDF + hash | Unduh ulang | — | Berkas & hash sama | — | Surat, Dokumen | KEM-TC-072 |
| <a id="kem-br-073"></a>KEM-BR-073 | Batal & terbit ulang terlacak | Tidak hapus; dokumen baru menunjuk lama | Batal/terbit ulang | — | `revoked`/`superseded` | — | Surat, Dokumen | KEM-TC-073, 074 |
| <a id="kem-br-074"></a>KEM-BR-074 | Klaim TTE terkendali | Frasa keabsahan elektronik hanya mode `electronic_certified` berhasil | Render | `signature_mode` | — | — | Surat, Dokumen | KEM-TC-077 |
| <a id="kem-br-075"></a>KEM-BR-075 | Verifikasi publik minimal | Data minimal, rate limit | Akses publik | Resource publik | — | 429 | Dokumen | KEM-TC-073, KEL-TC-068 |
| <a id="kem-br-076"></a>KEM-BR-076 | Riwayat unduhan | Setiap unduhan dicatat | Unduh | — | `document_access_logs` | — | Dokumen | KEM-TC-072 |
| <a id="kem-br-077"></a>KEM-BR-077 | Batas pengajuan surat | ≤ `max_open_requests` | Ajukan | Hitung pengajuan belum terbit | — | 409 `TOO_MANY_ACTIVE_REQUESTS` | Surat | KEM-TC-076 |

## 3. Yudisium dan Dokumen (KEL)

| ID | Nama | Deskripsi | Pemicu | Validasi | Hasil diharapkan | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| <a id="kel-br-001"></a>KEL-BR-001 | Versi aturan saat pemeriksaan | Eligibility dihitung berdasarkan versi aturan yang berlaku pada pemeriksaan | Pemeriksaan | Resolusi versi | `requirement_set_version_id` tersimpan | Tanpa versi → `manual_review` `NO_RULE_SET` | Eligibility | KEL-TC-020, 026 |
| <a id="kel-br-002"></a>KEL-BR-002 | Eligibility ≠ keputusan | Hasil eligibility tidak sama dengan keputusan kelulusan final | Hasil `eligible` | — | Status mahasiswa tidak berubah | — | Eligibility, Keputusan | KEL-TC-052 |
| <a id="kel-br-003"></a>KEL-BR-003 | Otorisasi penetapan | Penetapan kelulusan membutuhkan otorisasi yang ditentukan kampus | Finalisasi | Approval batch `approved` + `graduation_decisions.finalize` | `final` | 409/403 | Keputusan | KEL-TC-040, 053 |
| <a id="kel-br-004"></a>KEL-BR-004 | Nomor unik | Nomor dokumen resmi unik dalam ruang lingkup penomoran | Alokasi | Unique `official_number_normalized` | Unik | Retry, lalu `NUMBER_ALLOCATION_CONFLICT` | Dokumen | KEL-TC-061 |
| <a id="kel-br-005"></a>KEL-BR-005 | Syarat buka periode | ≥1 unit; tiap prodi punya set dapat diresolusi | Buka | Resolusi per prodi | `open` | 422 dengan daftar prodi | Periode | KEL-TC-001 |
| <a id="kel-br-006"></a>KEL-BR-006 | Versi immutable | Versi terpublikasi tidak diubah | Ubah versi | Status | — | 409 | Aturan | KEL-TC-002 |
| <a id="kel-br-007"></a>KEL-BR-007 | Paling spesifik & tanpa konflik | Resolusi set paling spesifik; konflik setara ditolak | Publikasi/resolusi | Skor spesifisitas | Satu set | 409 `RULE_CONFLICT` | Aturan | KEL-TC-003, 004 |
| <a id="kel-br-008"></a>KEL-BR-008 | Tanpa ambang bawaan | Ambang dari parameter kampus | Simpan versi | JSON Schema wajib | — | 422 | Aturan | KEL-TC-005 |
| <a id="kel-br-009"></a>KEL-BR-009 | Tidak tumpang tindih | Prodi tidak di dua periode `open` overlapping | Buka | Rentang tanggal | — | 409 | Periode | KEL-TC-006 |
| <a id="kel-br-010"></a>KEL-BR-010 | Finalisasi periode | Semua pendaftaran terminal | Finalisasi | Status pendaftaran | `finalized` | 409 dengan daftar | Periode | KEL-TC-007 |
| <a id="kel-br-020"></a>KEL-BR-020 | Item berbukti | Item menyimpan aktual, ambang, sumber, `as_of` | Pemeriksaan | — | Item lengkap | — | Eligibility | KEL-TC-020 |
| <a id="kel-br-021"></a>KEL-BR-021 | Sumber tidak tersedia ≠ lolos | `source_unavailable` → `manual_review` | Checker gagal | — | Bukan `eligible` | — | Eligibility | KEL-TC-027 |
| <a id="kel-br-022"></a>KEL-BR-022 | Stale | Data sumber berubah, versi berganti, umur lewat | Event/umur | — | `stale_at` diisi; recheck dijadwalkan | — | Eligibility | KEL-TC-026 |
| <a id="kel-br-023"></a>KEL-BR-023 | Stale tak dipakai | Verifikasi/penetapan memaksa recheck | Verifikasi/penetapan | `is_stale` | Hasil baru dipakai | 409 `ELIGIBILITY_STALE` bila recheck gagal | Eligibility | KEL-TC-032 |
| <a id="kel-br-024"></a>KEL-BR-024 | Pengecualian berwenang | Hanya `waivable`, per mahasiswa, beralasan, disetujui | Pengecualian | `waivable`, approval | Item `exempted` | 422 untuk non-waivable | Eligibility | KEL-TC-028, 029 |
| <a id="kel-br-025"></a>KEL-BR-025 | Urutan agregasi | failed > pending > manual/unavailable > exempted > eligible; hanya `mandatory` | Agregasi | — | Hasil deterministik | — | Eligibility | KEL-TC-033 |
| <a id="kel-br-026"></a>KEL-BR-026 | Tidak hitung ulang data pemilik | IPK/SKS/saldo dari kontrak | Checker | Kontrak | Nilai = sumber | — | Eligibility | KEL-TC-023 |
| <a id="kel-br-027"></a>KEL-BR-027 | Satu pemeriksaan berjalan | Lock per mahasiswa+periode | Pemeriksaan bersamaan | `Cache::lock` | Satu hasil baru | Menunggu & memakai hasil terbaru | Eligibility | KEL-TC-030 |
| <a id="kel-br-040"></a>KEL-BR-040 | Syarat kirim pendaftaran | Eligibility terkini eligible/exempted/pending berkas | Kirim | Hasil terkini | `submitted` | 409 `NOT_ELIGIBLE` | Pendaftaran | KEL-TC-041 |
| <a id="kel-br-041"></a>KEL-BR-041 | Verifikasi berjenjang & SoD | Tahap sesuai workflow; satu orang tidak memverifikasi dua tahap | Verifikasi | Riwayat verifikasi | — | 403 | Pendaftaran | KEL-TC-044 |
| <a id="kel-br-042"></a>KEL-BR-042 | Alasan penolakan | Alasan + daftar syarat/berkas | Tolak/kembalikan | `issues[]`, `note` | — | 422 | Pendaftaran | KEL-TC-042, 043 |
| <a id="kel-br-043"></a>KEL-BR-043 | Data induk via Akademik | Perbaikan via `student_requests` `data_change` | Konfirmasi data diri berbeda | — | Permintaan dibuat | — | Pendaftaran, Akademik | KEL-TC-054 |
| <a id="kel-br-044"></a>KEL-BR-044 | Keputusan dalam rapat | Rapat `held`; snapshot akademik | Input keputusan | Status rapat | Snapshot tersimpan | 409 | Keputusan | KEL-TC-055 |
| <a id="kel-br-045"></a>KEL-BR-045 | Pra-penetapan segar | `graduated` butuh eligibility segar eligible/exempted | Keputusan `graduated` | Umur ≤ batas | — | 409 dengan item berubah | Keputusan | KEL-TC-045 |
| <a id="kel-br-046"></a>KEL-BR-046 | Predikat terkonfigurasi | Dari aturan predikat versi berlaku tanggal lulus | Keputusan | Aturan published | `honor_*` tersimpan | Override tanpa alasan 422 | Keputusan | KEL-TC-046, 047 |
| <a id="kel-br-047"></a>KEL-BR-047 | Tanggal lulus menurut kebijakan | Bukan waktu input | Keputusan | `graduation_date_policy` | Tanggal benar | — | Keputusan | KEL-TC-056 |
| <a id="kel-br-048"></a>KEL-BR-048 | Status via kontrak | Finalisasi memanggil Akademik dalam transaksi | Finalisasi | — | `students.status = graduated`, histori | Rollback per mahasiswa | Keputusan, Akademik | KEL-TC-048 |
| <a id="kel-br-049"></a>KEL-BR-049 | Koreksi via pengajuan | Keputusan pengganti | Koreksi | Approval | `superseded` + baru + antrean terbit ulang | — | Keputusan | KEL-TC-049 |
| <a id="kel-br-050"></a>KEL-BR-050 | Pembatalan kelulusan | Cabut dokumen, kembalikan status, jejak lengkap | Annul | Approval tertinggi | `annulled`; dokumen revocation; alumni nonaktif | — | Keputusan | KEL-TC-050 |
| <a id="kel-br-051"></a>KEL-BR-051 | Satu kelulusan aktif | Satu `final graduated` per mahasiswa | Finalisasi/daftar | `active_graduated_key` | — | 409 `ALREADY_GRADUATED` | Keputusan | KEL-TC-051 |
| <a id="kel-br-060"></a>KEL-BR-060 | Format nomor valid | Token urut + token periode sesuai reset | Simpan skema | Parser format | — | 422 | Dokumen | KEL-TC-060 |
| <a id="kel-br-061"></a>KEL-BR-061 | Alokasi aman konkurensi | Kunci baris + unique | Issue | — | Unik | Retry/insiden | Dokumen | KEL-TC-061 |
| <a id="kel-br-062"></a>KEL-BR-062 | Nomor tidak dipakai ulang | Celah hanya dari pembatalan tercatat | Render gagal/batal | — | Draf bernomor diretry | — | Dokumen | KEL-TC-063 |
| <a id="kel-br-063"></a>KEL-BR-063 | Dokumen immutable | Snapshot & berkas tetap | Data sumber berubah | — | Hash tetap | — | Dokumen | KEL-TC-066 |
| <a id="kel-br-064"></a>KEL-BR-064 | Terbit ulang terhubung | Sesuai `reissue_number_policy` | Terbit ulang | — | Dokumen baru `supersedes` | — | Dokumen | KEL-TC-064 |
| <a id="kel-br-065"></a>KEL-BR-065 | Batal tetap terlacak | Tetap tersimpan & terverifikasi | Batal | — | Verifikasi: DIBATALKAN/DIGANTIKAN | — | Dokumen | KEL-TC-065 |
| <a id="kel-br-066"></a>KEL-BR-066 | Nomor eksternal | PIN diinput/diimpor, divalidasi | Impor | Regex, unik, keputusan final | — | Baris ditolak | Dokumen | KEL-TC-067 |
| <a id="kel-br-067"></a>KEL-BR-067 | Identitas dokumen berbeda | ID internal, nomor resmi, nomor eksternal, kode verifikasi berbeda | Issue | — | Kode verifikasi ≠ nomor | — | Dokumen | KEL-TC-070 |

## 4. Wisuda (WIS)

| ID | Nama | Deskripsi | Pemicu | Validasi | Hasil diharapkan | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| <a id="wis-br-001"></a>WIS-BR-001 | Dasar pendaftaran | Sesuai `registration_basis` (default keputusan final) | Daftar | Keputusan & cut-off | Pendaftaran tersimpan | 409 `NOT_ELIGIBLE_FOR_CEREMONY` | Wisuda | WIS-TC-002 |
| <a id="wis-br-002"></a>WIS-BR-002 | Satu wisuda | Tidak ada pendaftaran aktif ganda | Daftar | `active_graduate_key` | — | 409 | Wisuda | WIS-TC-010 |
| <a id="wis-br-003"></a>WIS-BR-003 | Lunas dari KEU | Tidak menghitung nominal | Validasi | `FinanceObligationReader` | Status sesuai KEU | KEU tak tersedia → tetap `payment_pending` | Wisuda | WIS-TC-007 |
| <a id="wis-br-004"></a>WIS-BR-004 | Kapasitas sesi | Tidak melebihi | Penempatan | Kunci sesi | — | 409 `SESSION_FULL` | Wisuda | WIS-TC-003 |
| <a id="wis-br-005"></a>WIS-BR-005 | Batas pendamping | ≤ batas | Simpan | Hitung | — | 422 | Wisuda | WIS-TC-009 |
| <a id="wis-br-006"></a>WIS-BR-006 | Perubahan jadwal terkabar | Dicatat & dinotifikasi | Ubah jadwal | — | Histori + notifikasi + kartu baru | — | Wisuda | WIS-TC-004 |
| <a id="wis-br-007"></a>WIS-BR-007 | Check-in idempoten | Satu kehadiran | Pindai | `checked_in_key` | `duplicate_scan` tercatat | — | Wisuda | WIS-TC-005 |
| <a id="wis-br-008"></a>WIS-BR-008 | Check-in sah | `confirmed` & sesi sesuai | Pindai | Status/sesi | — | 409 `WRONG_SESSION`/`NOT_CONFIRMED` | Wisuda | WIS-TC-011 |
| <a id="wis-br-009"></a>WIS-BR-009 | Buku dari sumber resmi | Data terverifikasi; lainnya `held` | Bangun edisi | Sumber per kolom | Entri `held` dilaporkan | — | Wisuda | WIS-TC-006 |
| <a id="wis-br-010"></a>WIS-BR-010 | Edisi immutable | Revisi = edisi baru | Ubah published | Status | — | 409 | Wisuda | WIS-TC-012 |
| <a id="wis-br-011"></a>WIS-BR-011 | Minimisasi data buku | Foto/publikasi dengan consent | Bangun/publikasi | Consent | Tanpa foto bila tidak setuju | — | Wisuda | WIS-TC-013 |
| <a id="wis-br-012"></a>WIS-BR-012 | Refund oleh KEU | Pembatalan diteruskan ke KEU | Batal setelah bayar | — | Event `CeremonyRegistrationCancelled` | Retry event | Wisuda, KEU | WIS-TC-014 |
| <a id="wis-br-013"></a>WIS-BR-013 | Alokasi deterministik | Hasil sama untuk input sama; koreksi manual beralasan sebelum `finalized` | Alokasi | — | Urutan tetap | 409 setelah finalized | Wisuda | WIS-TC-015 |

## 5. SKPI

| ID | Nama | Deskripsi | Pemicu | Validasi | Hasil diharapkan | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| <a id="skpi-br-001"></a>SKPI-BR-001 | Hanya capaian terverifikasi | Capaian yang belum diverifikasi tidak boleh otomatis dipublikasikan dalam SKPI final | Ajukan draf / terbit | Semua item `verified` + `eligible` | — | 409 `UNVERIFIED_ACHIEVEMENT` | SKPI | SKPI-TC-003 |
| <a id="skpi-br-002"></a>SKPI-BR-002 | Reuse verifikasi sumber | Tanpa verifikasi ganda; perubahan sumber menandai | Event sumber | `source_key` | Kandidat/`source_revoked` | — | SKPI | SKPI-TC-001, 009 |
| <a id="skpi-br-003"></a>SKPI-BR-003 | Terbit untuk lulusan | Keputusan final `graduated` | Terbit | `GraduationRecordReader` | — | 409 | SKPI | SKPI-TC-011 |
| <a id="skpi-br-004"></a>SKPI-BR-004 | Dua bahasa lengkap | Teks ID & EN kecuali `no_translate` | Ajukan | Field | — | 409 | SKPI | SKPI-TC-004 |
| <a id="skpi-br-005"></a>SKPI-BR-005 | Terjemahan ditinjau & glosarium | `reviewed`; istilah baku dari glosarium | Ajukan | Status & glosarium | — | 409 `TRANSLATION_NOT_REVIEWED` | SKPI | SKPI-TC-004, 012 |
| <a id="skpi-br-006"></a>SKPI-BR-006 | Dibekukan & revisi berhistori | Terbit ulang menggantikan | Revisi | — | `superseded` + baru | — | SKPI | SKPI-TC-006 |
| <a id="skpi-br-007"></a>SKPI-BR-007 | Nomor via OfficialDocument | — | Terbit | Skema `skpi` | Nomor unik | 409 `NUMBERING_SCHEME_MISSING` | SKPI | SKPI-TC-005, 007 |
| <a id="skpi-br-008"></a>SKPI-BR-008 | Batas tampil | ≤ `max_items_displayed` | Ajukan | Hitung per kategori | — | 409 `DISPLAY_LIMIT_EXCEEDED` | SKPI | SKPI-TC-013 |
| <a id="skpi-br-009"></a>SKPI-BR-009 | Akses terbatas | Pemilik, scope, pejabat; publik minimal | Akses | Policy | — | 403/404 | SKPI | SKPI-TC-008 |
| <a id="skpi-br-010"></a>SKPI-BR-010 | Versi tercatat | Template & profil prodi | Terbit | — | ID versi pada dokumen | — | SKPI | SKPI-TC-014 |
| <a id="skpi-br-011"></a>SKPI-BR-011 | Data akademik dari keputusan | Tidak diinput manual | Susun draf | Snapshot | Nilai = keputusan | — | SKPI | SKPI-TC-010 |

## 6. Tracer Study (TRACER)

| ID | Nama | Deskripsi | Pemicu | Validasi | Hasil diharapkan | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| <a id="tracer-br-001"></a>TRACER-BR-001 | Jawaban terikat versi | Jawaban responden harus terhubung dengan versi kuesioner yang digunakan | Simpan respons | FK versi & pertanyaan versi | Tersimpan dengan versi | 422 bila pertanyaan bukan versi target | Tracer | TRACER-TC-010 |
| <a id="tracer-br-002"></a>TRACER-BR-002 | Terkirim ≠ mengisi | Status email terkirim tidak berarti responden sudah mengisi | Tampilan/laporan | Dimensi terpisah | Dihitung terpisah | — | Tracer | TRACER-TC-031 |
| <a id="tracer-br-003"></a>TRACER-BR-003 | Kuota sesuai definisi | Pemakaian kuota mengikuti definisi perhitungan paket | Kampanye/kirim/bounce | Ledger atomik | Sisa benar | 409 `QUOTA_EXCEEDED` | Tracer, Entitlement | TRACER-TC-026, 027 |
| <a id="tracer-br-004"></a>TRACER-BR-004 | Versi immutable | Published tak diubah; berjawaban tak dihapus | Ubah/hapus | Status | — | 409 | Tracer | TRACER-TC-017 |
| <a id="tracer-br-005"></a>TRACER-BR-005 | Target unik | Per periode × jenis × subjek | Bangun target | Unique | — | Duplikat diabaikan | Tracer | TRACER-TC-001 |
| <a id="tracer-br-006"></a>TRACER-BR-006 | Token aman | ≥256-bit, hash, kedaluwarsa, dapat dicabut | Undangan | — | Satu token per target | — | Tracer | TRACER-TC-025, 032 |
| <a id="tracer-br-007"></a>TRACER-BR-007 | Satu respons | Idempoten; revisi bila diizinkan | Kirim | Unique target | — | 409 `ALREADY_SUBMITTED` | Tracer | TRACER-TC-013 |
| <a id="tracer-br-008"></a>TRACER-BR-008 | Pengingat terbatas | Belum kirim, tanpa bounce permanen, batas & jeda | Job pengingat | Kriteria | — | Dilewati | Tracer | TRACER-TC-023, 024 |
| <a id="tracer-br-009"></a>TRACER-BR-009 | Supresi | Hard bounce/keluhan menyupresi | Webhook | — | `tracer_suppressions` | — | Tracer | TRACER-TC-022 |
| <a id="tracer-br-010"></a>TRACER-BR-010 | Kirim idempoten | `dedupe_key` | Retry job | Unique | Satu email | — | Tracer | TRACER-TC-028 |
| <a id="tracer-br-011"></a>TRACER-BR-011 | Denominator eksplisit | RR dengan label `P_ELG`/`P_INV` | Laporan | — | Dua angka berlabel | — | Tracer | TRACER-TC-040 |
| <a id="tracer-br-012"></a>TRACER-BR-012 | Non-respons ≠ belum bekerja | Tidak digabung; tanpa sumber = belum diketahui | Laporan/profil | Kategori | — | — | Tracer, Alumni | TRACER-TC-006, 041 |
| <a id="tracer-br-013"></a>TRACER-BR-013 | Jawaban terpisah dari profil | Profil hanya atas consent sebagai catatan bersumber | Kirim survei | Consent | `alumni_career_records` | — | Tracer, Alumni | TRACER-TC-005 |
| <a id="tracer-br-014"></a>TRACER-BR-014 | Ekspor terlindungi | Default anonim; identitas berizin; sel minimum | Ekspor | Permission, `purpose` | — | 403/422 | Tracer | TRACER-TC-042, 043, 044 |
| <a id="tracer-br-015"></a>TRACER-BR-015 | Publikasi agregat | Disetujui, sel minimum | Publikasi | n ≥ batas | — | 409 | Tracer, Karier | KARIR-TC-007 |
| <a id="tracer-br-016"></a>TRACER-BR-016 | Pendapatan terbatas | Opsional, berdasar, terenkripsi, agregat | Konfigurasi/isi | `sensitivity` | — | — | Tracer | TRACER-TC-033 |
| <a id="tracer-br-017"></a>TRACER-BR-017 | Template standar bermetadata | Sumber, versi, verifikasi kurator | Publikasi template | Metadata | — | 422 | Tracer | TRACER-TC-015 |
| <a id="tracer-br-018"></a>TRACER-BR-018 | Kuota habis | Blok undangan awal saja | Kampanye | Kuota | Pengingat tetap | 409 | Tracer | TRACER-TC-030 |
| <a id="tracer-br-019"></a>TRACER-BR-019 | Logika bersyarat di server | Wajib hanya bila tampil; jawaban tersembunyi dibuang | Kirim | Evaluasi server | — | 422 untuk yang tampil | Tracer | TRACER-TC-011, 012 |
| <a id="tracer-br-020"></a>TRACER-BR-020 | Tutup periode | Token kedaluwarsa, pengingat berhenti | Tutup | — | — | — | Tracer | TRACER-TC-025 |

## 7. Career Center (KARIR)

| ID | Nama | Deskripsi | Pemicu | Validasi | Hasil diharapkan | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| <a id="karir-br-001"></a>KARIR-BR-001 | Berbagi sesuai izin | Data alumni hanya dapat dibagikan sesuai izin dan hak akses | Setiap pembacaan data alumni oleh pihak luar | Consent + dasar akses | — | 403 `CONSENT_REQUIRED` | Karier | KARIR-TC-031 |
| <a id="karir-br-002"></a>KARIR-BR-002 | Moderasi sebelum publikasi | Lowongan harus melewati pemeriksaan yang ditentukan sebelum dipublikasikan | Ajukan lowongan | Moderasi | `published` setelah disetujui | Tetap `pending_review` | Karier | KARIR-TC-010, 011 |
| <a id="karir-br-003"></a>KARIR-BR-003 | Perusahaan terverifikasi | Untuk posting publik & akses kandidat | Posting | `verified` | — | 403 `COMPANY_NOT_VERIFIED` | Karier | KARIR-TC-026 |
| <a id="karir-br-004"></a>KARIR-BR-004 | Tiga lapis status | Perusahaan, anggota, kemitraan dievaluasi terpisah | Aksi perusahaan | Ketiganya | — | 403 | Karier | KARIR-TC-017, 023 |
| <a id="karir-br-005"></a>KARIR-BR-005 | Satu lamaran aktif | Per pelamar per lowongan | Lamar | `active_key` | — | 409 `DUPLICATE_APPLICATION` | Karier | KARIR-TC-012 |
| <a id="karir-br-006"></a>KARIR-BR-006 | State machine lamaran | Transisi sah; final tetap | Ubah status | Matriks transisi | Riwayat tahap | 409 | Karier | KARIR-TC-013, 014 |
| <a id="karir-br-007"></a>KARIR-BR-007 | Dasar akses CV | Lamaran/rekomendasi; dicatat | Unduh | Dasar akses | Log akses | 403 + catatan percobaan | Karier | KARIR-TC-015 |
| <a id="karir-br-008"></a>KARIR-BR-008 | Profil privat default | Buka dengan consent | Buat profil | — | `private` | — | Karier | KARIR-TC-004 |
| <a id="karir-br-009"></a>KARIR-BR-009 | Syarat rekomendasi | Entitlement, kemitraan, persetujuan kampus, consent | Rekomendasi | Semua syarat | — | 403/409 | Karier | KARIR-TC-030, 034 |
| <a id="karir-br-010"></a>KARIR-BR-010 | Consent spesifik | Penerima, tujuan, data, masa berlaku; dapat dicabut | Consent | — | Akses berakhir saat cabut/kedaluwarsa | — | Karier | KARIR-TC-033 |
| <a id="karir-br-011"></a>KARIR-BR-011 | Isolasi tenant | Data pengguna per tenant | Query | Scope | — | Kosong/404 | Karier | KARIR-TC-005 |
| <a id="karir-br-012"></a>KARIR-BR-012 | Moderator sesuai visibilitas | Marketplace: platform; tenant: tenant | Moderasi | `visibility` | — | 403 | Karier | KARIR-TC-010, 019 |
| <a id="karir-br-013"></a>KARIR-BR-013 | Tutup otomatis | Lewat deadline | Job harian | Tanggal | `closed` | — | Karier | KARIR-TC-027 |
| <a id="karir-br-014"></a>KARIR-BR-014 | Anti-spam | Rate limit & deteksi duplikat | Posting/lamar | Batas | — | 429/flag | Karier | KARIR-TC-018 |
| <a id="karir-br-015"></a>KARIR-BR-015 | Fitur Professional+ | Artikel tenant & rekomendasi | Akses | Entitlement | — | 403 `FEATURE_NOT_IN_PLAN` | Karier | KARIR-TC-003 |
| <a id="karir-br-016"></a>KARIR-BR-016 | Branding berhak | Whitelabel dengan fallback | Render portal | Entitlement | Tema benar | Fallback default | Karier | KARIR-TC-001, 002 |
| <a id="karir-br-017"></a>KARIR-BR-017 | Publikasi tracer agregat | Snapshot disetujui, sel minimum | Publikasi | n | — | 409 | Karier | KARIR-TC-007 |
| <a id="karir-br-018"></a>KARIR-BR-018 | CRM internal | Tidak terlihat perusahaan/tenant lain | Akses | Scope | — | 404 | Karier | KARIR-TC-024 |
| <a id="karir-br-019"></a>KARIR-BR-019 | Token undangan | Sekali pakai, hash, kedaluwarsa | Terima undangan | — | — | 410 | Karier | KARIR-TC-022 |
| <a id="karir-br-020"></a>KARIR-BR-020 | Retensi CV & snapshot | Sesuai kebijakan | Job retensi | — | Dihapus/dianonimkan | — | Karier | KARIR-TC-006, 016 |

## 8. Lintas Modul (UMUM)

| ID | Nama | Deskripsi | Pemicu | Validasi | Hasil diharapkan | Kegagalan | Modul | TC |
|---|---|---|---|---|---|---|---|---|
| <a id="umum-br-001"></a>UMUM-BR-001 | Isolasi tenant | Data tenant ber-`university_id`, `TenantScoped`, `tenant.access` | Setiap request/job | Scope & konteks | Hanya data tenant | 403/404 | Semua | UMUM-TC-001 |
| <a id="umum-br-002"></a>UMUM-BR-002 | Paket sebelum izin | `feature:` sebelum `permission:` | Request | Entitlement | — | 403 `FEATURE_NOT_IN_PLAN`/`FEATURE_DISABLED` | Semua | UMUM-TC-020, 022 |
| <a id="umum-br-003"></a>UMUM-BR-003 | Batas paket terpusat | Dibaca `FeatureEntitlementService`, tidak di-hardcode | Pengembangan | Uji statis | — | Gagal CI | Semua | UMUM-TC-021, 024 |
| <a id="umum-br-004"></a>UMUM-BR-004 | Aksi sensitif eksplisit | Permission eksplisit + alasan + audit | Aksi tabel [03 §5](03-aktor-role-dan-hak-akses.md#5-aksi-sensitif-yang-memerlukan-otorisasi-eksplisit-dan-audit) | Permission, `reason` | Audit tercatat | 403/422 | Semua | UMUM-TC-030 |
| <a id="umum-br-005"></a>UMUM-BR-005 | Pemisahan tugas | Pembuat ≠ penyetuju | Persetujuan | `SegregationOfDutiesGuard` | — | 403 `SELF_APPROVAL_FORBIDDEN` | Semua | UMUM-TC-002 |
| <a id="umum-br-006"></a>UMUM-BR-006 | Idempotensi | `Idempotency-Key` pada endpoint [I] | POST sensitif | `idempotency_keys` | Respons sama | 409 `IDEMPOTENCY_KEY_REUSED` | Semua | UMUM-TC-031 |
| <a id="umum-br-007"></a>UMUM-BR-007 | Validasi berkas | MIME konten, ukuran, pemindaian | Unggah | `FileUploadService` | `clean` | 422 / `rejected` | Semua | UMUM-TC-032 |
| <a id="umum-br-008"></a>UMUM-BR-008 | Status via service | Tidak ada PATCH status generik; histori ditulis | Transisi | Matriks transisi | Histori + audit | 409 | Semua | UMUM-TC-033 |
| <a id="umum-br-009"></a>UMUM-BR-009 | Tautan publik aman | Token acak, hash, kedaluwarsa, rate limit, data minimal | Akses publik | — | — | 410/429 | Semua | UMUM-TC-006, 034 |
| <a id="umum-br-010"></a>UMUM-BR-010 | Ekspor terkendali | Permission export, audit, masking default, berkas kedaluwarsa | Ekspor | — | `data_exports` | 403 | Semua | UMUM-TC-004 |
| <a id="umum-br-011"></a>UMUM-BR-011 | Notifikasi idempoten | `dedupe_key` | Event/retry | — | Satu notifikasi | — | Notifikasi | UMUM-TC-010 |
| <a id="umum-br-012"></a>UMUM-BR-012 | Waktu tenant | Simpan UTC; batas hari zona tenant | Tenggat/periode | `universities.timezone` | Batas benar | — | Semua | UMUM-TC-035 |

## 9. Validasi Umum Input

| Aspek | Ketentuan |
|---|---|
| String | Trim, tolak karakter kontrol, panjang maksimum sesuai skema |
| Teks kaya | Markdown/HTML disanitasi allowlist (artikel, about portal, template dokumen hanya admin) |
| Tanggal | ISO 8601; perbandingan dalam zona tenant |
| ID | ULID valid dan berada di tenant aktif (404 bila tidak) |
| Enum | Nilai dari enum PHP; status tidak diterima dari klien kecuali sebagai aksi |
| Angka ambang | Wajib eksplisit, dalam rentang wajar (mis. IPK 0–skala maks) |
| JSON konfigurasi | JSON Schema per jenis (checker, kriteria rekomendasi, logika kuesioner) |
| Berkas | [28 §11](28-keamanan-privasi-dan-audit.md#11-validasi-berkas) |
