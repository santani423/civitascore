# 63. Glosarium & Daftar Status

[← FAQ & Pemecahan Masalah](62-faq-dan-pemecahan-masalah.md) · [Daftar isi](README.md)

Daftar ini memetakan **label yang tampil di layar** ke **kode sistem** (dipakai pada filter, laporan, log, dan [rancangan 09](../../pmb/09-workflow-dan-state-machine.md)).

## 1. Glosarium

| Istilah | Arti |
|---|---|
| Akun pendaftar | Akun portal PMB calon mahasiswa, terpisah dari akun staf/mahasiswa. |
| Periode | Siklus PMB untuk satu tahun akademik masuk. |
| Gelombang | Rentang jadwal dalam periode (pendaftaran s.d. daftar ulang). |
| Gelombang ODS | Gelombang dengan jadwal ujian fleksibel (*On Demand Schedule*). |
| Jalur masuk | Kategori penerimaan (Reguler, Prestasi, KIP, dsb.). |
| Gelombang-jalur | Jalur yang dibuka pada satu gelombang beserta biaya, template, dan skemanya. |
| Sistem kuliah | Kelas perkuliahan (Reguler, Karyawan, dsb.). |
| Kuota | Kapasitas per periode × jalur × prodi × sistem kuliah. |
| Template formulir | Aturan isian formulir pendaftaran, berversi. |
| Skema seleksi | Komponen penilaian, bobot, ambang, dan metode alokasi, berversi. |
| Run seleksi | Satu kali perhitungan peringkat & penempatan untuk satu gelombang-jalur. |
| Override | Perubahan rekomendasi hasil run oleh panitia, wajib alasan. |
| Hash hasil | Sidik jari SHA-256 hasil run untuk memastikan hasil yang disahkan sama dengan yang dihitung. |
| Batch pengumuman | Jadwal publikasi keputusan suatu run. |
| LoA | Surat penerimaan (*Letter of Acceptance*) ber-QR verifikasi. |
| Kode unik | Tiga digit tambahan pada nominal transfer manual untuk memudahkan pencocokan. |
| Klaim | Penguncian sementara satu pendaftaran oleh verifikator (default 30 menit). |
| Konversi | Pembentukan data mahasiswa, NIM, akun, tagihan, dokumen, dan beasiswa setelah daftar ulang disetujui. |
| Outbox | Antrean efek samping (notifikasi, kartu ujian, LoA, konversi, ekspor) yang diproses penjadwal. |
| Data sensitif | Data pribadi yang dienkripsi & tampil tersamar; membukanya wajib menyebut tujuan dan tercatat. |
| BR-xx, FR-xx, ADM-xx, POR-xx, PUB-xx | Kode aturan bisnis, kebutuhan fungsional, dan halaman pada dokumen rancangan `docs/pmb`. |

## 2. Status pendaftaran

### 2.1 Tahap pendaftar (`current_stage`)

| Label | Kode |
|---|---|
| Melengkapi formulir | `draft` |
| Menunggu pembayaran | `awaiting_payment` |
| Sedang diverifikasi | `under_verification` |
| Perlu perbaikan | `revision_needed` |
| Tidak lolos administrasi | `admin_rejected` |
| Proses seleksi | `in_selection` |
| Hasil seleksi tersedia | `result_available` |
| Daftar ulang | `re_registration` |
| Daftar ulang tidak diselesaikan | `re_registration_closed` |
| Selamat, Anda resmi menjadi mahasiswa | `enrolled` |
| Ditarik | `withdrawn` |
| Tidak dikirim | `void` |

### 2.2 Formulir, verifikasi, seleksi, pembayaran pendaftaran

| Status formulir | Kode | | Status verifikasi | Kode |
|---|---|---|---|---|
| Draf | `draft` | | Belum diperiksa | `not_started` |
| Terkirim | `submitted` | | Diperiksa | `in_review` |
| Perlu perbaikan | `revision_requested` | | Perlu perbaikan | `revision_requested` |
| Terkunci | `locked` | | Terverifikasi | `verified` |
| Ditarik | `withdrawn` | | Ditolak | `rejected` |
| Batal | `void` | | | |

| Status seleksi | Kode | | Pembayaran pendaftaran | Kode |
|---|---|---|---|---|
| Belum memenuhi syarat | `not_eligible` | | Tidak perlu | `not_required` |
| Siap seleksi | `eligible` | | Belum bayar | `unpaid` |
| Terjadwal | `scheduled` | | Menunggu | `pending` |
| Dinilai | `assessed` | | Lunas | `paid` |
| Tidak hadir | `absent` | | Kedaluwarsa | `expired` |
| Didiskualifikasi | `disqualified` | | | |
| Diperingkat | `ranked` | | | |

### 2.3 Dokumen

| Label | Kode | | Keputusan tinjauan | Kode |
|---|---|---|---|---|
| Belum diunggah | `missing` | | Setujui | `approved` |
| Diunggah | `uploaded` | | Minta perbaikan | `revision_requested` |
| Diperiksa | `in_review` | | Tolak | `rejected` |
| Disetujui | `approved` | | Buka kembali | `reopened` |
| Perlu perbaikan | `revision_requested` | | | |
| Ditolak | `rejected` | | | |

## 3. Keuangan

| Tagihan | Kode | | Transaksi | Kode |
|---|---|---|---|---|
| Belum bayar | `unpaid` | | Dibuat | `created` |
| Menunggu | `pending` | | Menunggu | `pending` |
| Sebagian | `partially_paid` | | Berhasil | `success` |
| Lunas | `paid` | | Gagal | `failed` |
| Kedaluwarsa | `expired` | | Kedaluwarsa | `expired` |
| Dibatalkan | `cancelled` | | Dibatalkan | `cancelled` |
| Refund diproses | `refund_pending` | | Dikembalikan | `refunded` |
| Dikembalikan | `refunded` | | | |

| Bukti transfer | Kode | | Refund | Kode |
|---|---|---|---|---|
| Menunggu | `submitted` | | Draf | `draft` |
| Disetujui | `approved` | | Menunggu pengesahan | `pending_approval` |
| Ditolak | `rejected` | | Disetujui | `approved` |
| | | | Diproses | `processing` |
| | | | Selesai | `completed` |
| | | | Ditolak | `rejected` |
| | | | Gagal | `failed` |

Hasil rekonsiliasi: `matched` (cocok), `missing_in_system` (ada di mutasi, tidak ada di sistem), `missing_in_provider` (ada di sistem, tidak ada di mutasi), `amount_mismatch` (nominal berbeda), `duplicate_payment` (pembayaran ganda).

Kanal/penyedia: `manual_transfer`/`manual` (transfer + bukti), `cashier` (loket), `midtrans` (gateway).

## 4. Ujian & seleksi

| Jadwal ujian | Kode | | Kehadiran | Kode |
|---|---|---|---|---|
| Draf | `draft` | | Terdaftar | `registered` |
| Terbit | `published` | | Hadir | `present` |
| Selesai | `completed` | | Tidak hadir | `absent` |
| Dibatalkan | `cancelled` | | Izin | `excused` |
| | | | Didiskualifikasi | `disqualified` |

| Run seleksi | Kode | | Rekomendasi | Kode |
|---|---|---|---|---|
| Draf | `draft` | | Lulus | `pass` |
| Dihitung | `computed` | | Cadangan | `waitlist` |
| Ditinjau | `under_review` | | Tidak lulus | `fail` |
| Menunggu pengesahan | `pending_approval` | | | |
| Disetujui | `approved` | | | |
| Ditolak | `rejected` | | | |
| Terbit | `published` | | | |
| Digantikan | `superseded` | | | |
| Gagal | `failed` | | | |

Alasan tidak lulus (`fail_reason`): `below_threshold` (skor di bawah ambang), `component_minimum` (nilai komponen di bawah minimum), `absent` (tidak hadir ujian), `quota_full_no_waitlist` (kuota & cadangan penuh), `disqualified` (didiskualifikasi), `incomplete_scores` (nilai komponen wajib belum lengkap).

## 5. Keputusan, pengumuman, daftar ulang

| Keputusan | Kode | | Publikasi / batch | Kode |
|---|---|---|---|---|
| Diterima | `accepted` | | Belum terbit | `unpublished` |
| Cadangan | `waitlisted` | | Terbit | `published` |
| Tidak diterima | `rejected` | | Terjadwal (batch) | `scheduled` |
| Dibatalkan | `withdrawn` | | Menerbitkan (batch) | `publishing` |
| | | | Gagal (batch) | `failed` |
| | | | Dibatalkan (batch) | `cancelled` |

Sumber keputusan: `selection_run` (hasil run), `waitlist_promotion` (promosi cadangan), `correction` (koreksi), `offer_withdrawal` (pembatalan tawaran). Status LoA: `pending`, `issued` (terbit), `revoked` (dicabut).

| Daftar ulang | Kode | | Pemeriksaan daftar ulang | Kode |
|---|---|---|---|---|
| Menunggu konfirmasi | `awaiting_confirmation` | | Menunggu | `pending` |
| Dikonfirmasi | `confirmed` | | Lolos | `passed` |
| Berlangsung | `in_progress` | | Tidak lolos | `failed` |
| Terkirim | `submitted` | | Tidak berlaku | `not_applicable` |
| Perlu perbaikan | `revision_requested` | | | |
| Disetujui | `approved` | | | |
| Resmi mahasiswa | `enrolled` | | | |
| Menolak tawaran | `declined` | | | |
| Kedaluwarsa | `expired` | | | |
| Dibatalkan | `cancelled` | | | |

Kode pemeriksaan: `OFFER_ACCEPTED`, `PDDIKTI_DATA_COMPLETE`, `GUARDIAN_DATA_COMPLETE`, `FINAL_DOCUMENTS_APPROVED`, `HEALTH_CLEARED`, `PAYMENT_MINIMUM_MET`, `ORIGINAL_DOCUMENTS_CHECKED`, `NO_OPEN_DUPLICATE_FLAG`.

Konversi: `pending` (Menunggu), `processing` (Diproses), `succeeded` (Berhasil), `failed` (Gagal), `manual_intervention` (Perlu tindakan).

## 6. Konfigurasi, komunikasi, dan lain-lain

| Objek | Status (label → kode) |
|---|---|
| Periode | Draf `draft` · Terbit `published` · Ditutup `closed` · Diarsipkan `archived` |
| Gelombang | Draf `draft` · Terjadwal `scheduled` · Dibuka `open` · Ditutup `closed` · Diumumkan `announced` · Selesai `completed` |
| Template formulir | Draf `draft` · Terbit `published` · Pensiun `retired` |
| Skema seleksi | Draf `draft` · Terkunci `locked` |
| Konten | Draf `draft` · Terbit `published` · Arsip `archived` |
| Akun pendaftar | Belum verifikasi `unverified` · Aktif `active` · Terkunci `locked` · Dibekukan `suspended` · Nonaktif `deactivated` |
| Pesan (chat) | Dibuka `open` · Menunggu pendaftar `pending_applicant` · Ditutup `closed` |
| Broadcast | Draf `draft` · Terjadwal `scheduled` · Mengirim `sending` · Terkirim `sent` · Dibatalkan `cancelled` |
| Log notifikasi | Menunggu `pending` · Terkirim `dispatched` · Gagal `failed` |
| Ekspor | Menunggu `pending` · Diproses `processing` · Siap `ready` · Gagal `failed` · Kedaluwarsa `expired` |
| Indikasi duplikat | Terbuka `open` · Duplikat `confirmed_duplicate` · Bukan duplikat `not_duplicate` · Digabung `merged` |
| Pemulihan akun / jadwal ulang | Menunggu `pending` · Disetujui `approved` · Ditolak `rejected` |
| Pengajuan perubahan | Menunggu pengesahan `pending_approval` · Disetujui `approved` · Ditolak `rejected` · Dikembalikan `returned` · Diterapkan `applied` · Gagal `failed` |

---

[← FAQ & Pemecahan Masalah](62-faq-dan-pemecahan-masalah.md) · [Daftar isi](README.md)
