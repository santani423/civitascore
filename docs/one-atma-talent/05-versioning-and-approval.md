# One Atma Talent — Versioning dan Persetujuan Instrumen

> **Per:** 5 November 2026. **Terkait:** [03](03-admin-management.md), [06](06-result-review-publication.md).

## 1. Status Versi

```
                 ┌── kembalikan ──┐
                 ▼                │
draft ──ajukan──► in_review ──────┘
                   │        │
                   │        └──tolak──► rejected  (final; revisi dibuat sebagai salinan)
                   └──setujui (langkah terakhir)──► approved ──terbitkan──► published ──arsipkan──► archived
```

State machine juga mengizinkan `approved` → `draft` (membatalkan persetujuan sebelum terbit), tetapi endpoint untuk itu belum dibuat.

| Status | Dapat diedit | Keterangan |
|---|---|---|
| `draft` | Ya | Dimensi, pertanyaan, dan konfigurasi bisa diubah. Dapat dihapus bila belum pernah diajukan. |
| `in_review` | Tidak | Sedang ditelaah. Keputusan dibuat dari halaman versi. |
| `approved` | Tidak | Sudah disetujui semua langkah. Pertanyaan berstatus `approved`. Menunggu penerbitan. |
| `rejected` | Tidak | Ditolak. Revisi dibuat dengan **Salin menjadi versi baru**. |
| `published` | Tidak | Dipakai peserta. Hanya satu versi terbit per jenis. |
| `archived` | Tidak | Tidak lagi dipakai peserta baru. Sesi dan hasil yang sudah ada tetap dapat dibaca. |

Transisi diperiksa oleh `StateTransition::assert()` dengan tabel di `AssessmentVersionStatus::allowedTransitions()`. Transisi di luar tabel menghasilkan `INVALID_TRANSITION` (422).

## 2. Aturan Versioning

1. **Versi yang sudah diajukan atau terbit tidak diubah secara destruktif.** Setiap perubahan substansial dibuat sebagai versi baru lewat salinan (`POST assessment-versions/{id}/clone`). Salinan menyimpan `parent_version_id` dan `change_note`.
2. **Sesi mengacu ke versi saat dimulai.** `version_id` dan `item_order` dibekukan di sesi. Respons menunjuk butir dari versi itu. Penerbitan versi baru tidak mengubah sesi yang sedang berjalan; sesi itu diselesaikan pada versi asalnya, walaupun versi tersebut sudah diarsipkan.
3. **Satu versi terbuka per jenis.** `draft`, `in_review`, dan `approved` dihitung sebagai terbuka. Pembuatan atau salinan kedua ditolak dengan `VERSION_OPEN`.
4. **Tujuan penggunaan dikunci setelah dibuat.** `purpose` tidak ikut dalam PATCH (`prohibited` di validasi). Versi `admissions` dapat dibuat sebagai draft tetapi tidak dapat diajukan (`ADMISSIONS_NOT_APPROVED`) sampai ada validasi psikometrik dan persetujuan fakultas.
5. **Scoring berversi.** Aturan scoring ada di kode (`RiasecScorer::ENGINE`, saat ini `riasec-mean-v1`) dan disimpan di `scoring_config` versi serta di `scoring_engine` setiap sesi yang sudah dinilai. Mengubah rumus berarti membuat engine baru (mis. `riasec-mean-v2`) dan versi instrumen baru. Sesi lama tetap menyimpan engine yang dipakai saat dinilai. Tidak ada UI untuk mengubah rumus.

## 3. Approval Workflow

Memakai engine **ApprovalWorkflow** yang sudah ada, bukan tabel persetujuan baru.

| Item | Nilai |
|---|---|
| Jenis workflow | `pmb_assessment_version` (ditambahkan ke `PmbApprovalService::DEFAULTS`) |
| Nama alur default | Pengesahan Instrumen Asesmen |
| Langkah 1 | Penelaah Asesmen — role `admission_administrator` |
| Langkah 2 | Pejabat Pengesah — role `admission_approver` |
| Requestable | `AssessmentVersion` (implementasi `RequiresDistinctApprover`) |

Alur default dibuat otomatis saat pengajuan pertama bila tenant belum punya alur. Alur itu dapat diubah di halaman *Alur Persetujuan* yang sudah ada.

### 3.1 Pemisahan tugas

- Pemohon tidak boleh memutuskan pengajuannya sendiri. Engine memeriksa `requested_by`.
- Penyusun (`created_by`) dan pengaju (`submitted_by`) dikecualikan dari keputusan lewat `excludedApproverIds()`.
- Setiap keputusan memanggil `Gate::authorize('act', $step)`, policy yang sama dengan halaman persetujuan umum. Role langkah dan giliran diperiksa di sini.

Tes `menjalankan persetujuan dua pihak dengan pemisahan tugas`: penyusun mencoba menyetujui pengajuannya sendiri dan menerima 409. Keputusan kemudian dilakukan oleh penelaah (pengguna lain dengan role yang sama), lalu pejabat pengesah.

### 3.2 Keputusan

| Keputusan | Akibat pada versi | Syarat |
|---|---|---|
| Setujui (langkah bukan terakhir) | Tetap `in_review`, langkah berikutnya aktif | Permission `pmb_assessments.approve` |
| Setujui (langkah terakhir) | `approved`, `approved_at` diisi, semua butir `approved` | Sama |
| Kembalikan | `draft`, dapat diedit lagi | Komentar wajib (min. 5 karakter) |
| Tolak | `rejected`, final | Komentar wajib |

Status versi diperbarui oleh listener `SyncApprovalDecisions` ketika engine menutup pengajuan (`approved`, `rejected`, `returned`). Pemanggilan ganda tidak berefek karena listener hanya memproses versi yang masih `in_review`.

### 3.3 Pengajuan

Pengajuan memeriksa kelengkapan lebih dulu (`assertReadyForReview`):

- Tujuan `exploratory`.
- Domain punya scoring engine.
- Dimensi lengkap sesuai domain (untuk minat: R, I, A, S, E, C).
- Setiap dimensi punya minimal satu butir.

Bila salah satu tidak terpenuhi, tombol *Ajukan* tetap aktif tetapi panel peringatan menunjukkan alasannya (`readiness`), dan API menolak dengan kode yang sesuai.

## 4. Penerbitan dan Arsip

- **Terbitkan** hanya dari `approved` (`VERSION_NOT_APPROVED` bila tidak). Di dalam transaksi, versi `published` sebelumnya untuk jenis yang sama diarsipkan dengan `superseded_by` sebagai alasan di audit. Jenis dikunci dengan `lockForUpdate()`, dan status diperiksa ulang setelah kunci, sehingga dua penerbitan bersamaan tidak lolos.
- **Arsipkan** hanya dari `published`, dengan alasan wajib. Tidak menghapus sesi maupun respons.
- **Hapus** hanya untuk draft yang belum pernah diajukan dan tidak punya sesi. Versi yang pernah diajukan tidak dapat dihapus (`VERSION_IN_USE`).

## 5. Perbandingan dan Riwayat

- **Perbandingan** (`GET assessment-versions/{v}/diff/{other}`) membandingkan dua versi dari jenis yang sama. Dimensi dicocokkan per kode, butir per kode. Butir tanpa kode dicocokkan per id. Hasilnya: dimensi ditambahkan, dihapus, berubah (nama, interpretasi, deskripsi); butir ditambahkan, dihapus, berubah (teks, arah, dimensi).
- **Riwayat** versi berasal dari audit log (`AuditLogService` via `HistoryRecorder`). Setiap perubahan status dan konfigurasi tercatat dengan pengguna, nilai lama dan baru, serta alasan bila ada.

## 6. Belum Selesai

- Penolakan dengan alasan yang terstruktur (saat ini teks bebas).
- Batas waktu pada langkah approval (eskalasi).
- Delegasi langkah approval khusus untuk versi asesmen. Tersedia di engine, belum diekspos di halaman versi.
