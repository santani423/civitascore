# One Atma Talent — Fase 2: Admin Management

> **Per:** 5 November 2026. Ditulis untuk pengembang CivitasOne yang melanjutkan fitur ini.
> **Sebelumnya:** [02-slice-1-riasec.md](02-slice-1-riasec.md). **Terkait:** [04](04-session-expiration.md), [05](05-versioning-and-approval.md), [06](06-result-review-publication.md), [07](07-testing-and-verification.md).

## 1. Ringkasan

Fase 2 membuat admin dapat mengelola asesmen RIASEC lewat UI, bukan hanya API. Halaman berada di bawah menu **PMB → One Atma Talent** (`/admin/pmb/talenta`).

| Halaman | Rute | Isi |
|---|---|---|
| Dashboard | `/admin/pmb/talenta` | Ringkasan dan grafik dari API, filter periode, jenis, versi, status, prodi, mode. |
| Asesmen & Versi | `/admin/pmb/talenta/asesmen` | Daftar jenis asesmen, buat, aktifkan atau nonaktifkan. |
| Detail jenis | `/admin/pmb/talenta/asesmen/:id` | Riwayat versi, buat draft, salin versi. |
| Versi | `/admin/pmb/talenta/versi/:id` | Konfigurasi, dimensi, pertanyaan, persetujuan, perbandingan, riwayat. |
| Bank soal | `/admin/pmb/talenta/bank-soal` | Telusuri pertanyaan lintas versi, pratinjau, salin ke draft. |
| Peserta & Sesi | `/admin/pmb/talenta/peserta` | Daftar peserta dengan filter. |
| Detail sesi | `/admin/pmb/talenta/sesi/:id` | Ringkasan, jawaban (bila berizin), riwayat, review, publikasi, invalidasi. |
| Audit | `/admin/pmb/talenta/audit` | Riwayat perubahan One Atma Talent. |

Pola UI mengikuti modul PMB yang sudah ada: `AdminPage`, `AdminList`, `FormDialog`, `ReasonDialog`, `Tabs`, `KeyValue`, dan `useLoad`/`useAction`.

## 2. Keputusan Penting

- **Izin memakai aksi standar.** `PermissionAction` ada di enum yang dipakai untuk membentuk kolom `permissions.action` (tipe enum di MySQL). Menambah aksi baru berarti mengubah skema tabel yang dipakai semua modul. Karena itu nama dari prompt dipetakan ke aksi yang sudah ada (lihat §5).
- **Halaman versi memuat bank soal per versi.** Pertanyaan dibuat dan diubah di tab *Pertanyaan* pada halaman versi, sehingga selalu terikat ke draft yang tepat. Halaman bank soal lintas versi dipakai untuk menelusuri dan menyalin butir ke draft jenis yang sama.
- **Pemisahan tugas dijaga di backend.** Tombol UI disembunyikan sesuai izin, tetapi setiap rute diberi `permission:` dan setiap keputusan approval memakai policy `act` dari engine ApprovalWorkflow.
- **Pratinjau butir hanya menampilkan teks dan skala.** Dimensi dan arah butir tidak dikirim ke pratinjau, sama seperti respons untuk peserta.

## 3. Struktur Data Tambahan

Migrasi `2026_11_06_100000_extend_pmb_assessment_admin.php` hanya menambah kolom dan satu tabel. Tidak ada kolom yang dihapus.

| Tabel | Kolom baru | Fungsi |
|---|---|---|
| `pmb_assessment_items` | `code`, `question_type`, `status`, `source_item_id` | Kode unik per versi, jenis (`likert`), status (`draft`/`approved`), asal butir hasil salin. |
| `pmb_assessment_versions` | `parent_version_id`, `approval_request_id`, `submitted_at`, `submitted_by`, `approved_at`, `archived_at`, `change_note` | Lineage versi, pengajuan approval, jejak waktu, catatan perubahan. |
| `pmb_assessment_sessions` | `review_status`, `mode`, `invalidated_at`, `invalidated_by`, `invalidation_reason` | Status review, mode (saat ini hanya `online`), jejak invalidasi. |
| `pmb_assessment_review_notes` (baru) | seluruh tabel | Riwayat review sesi: catatan, penandaan, penyelesaian. Append-only. |

Data existing: status versi `retired` dari Slice 1 diubah menjadi `archived` lewat pembaruan data di migrasi yang sama.

## 4. Endpoint Admin

Semua di bawah `/api/v1/pmb/admin`, dengan `pmb.staff`, `tenant.access`, dan `permission:` per rute.

**Konfigurasi**

| Method | Path | Permission |
|---|---|---|
| GET | `assessment-dashboard` | `pmb_assessment_sessions.read` (distribusi hasil juga butuh `pmb_assessment_results.read`) |
| GET | `assessment-types` | `pmb_assessments.read` |
| POST | `assessment-types` | `pmb_assessments.create` |
| PATCH | `assessment-types/{type}` | `pmb_assessments.update` |
| GET | `assessment-types/{type}/versions` | `pmb_assessments.read` |
| POST | `assessment-types/{type}/versions` | `pmb_assessments.create` |
| GET | `assessment-versions/{version}` | `pmb_assessments.read` |
| GET | `assessment-versions/{version}/diff/{other}` | `pmb_assessments.read` |
| PATCH | `assessment-versions/{version}` | `pmb_assessments.update` (draft) |
| POST | `assessment-versions/{version}/clone` | `pmb_assessments.create` |
| POST | `assessment-versions/{version}/dimensions` | `pmb_assessments.update` (draft) |
| PATCH / DELETE | `assessment-dimensions/{dimension}` | `pmb_assessments.update` (draft) |
| POST | `assessment-versions/{version}/submit` | `pmb_assessments.update` |
| POST | `assessment-versions/{version}/decision` | `pmb_assessments.approve` |
| POST | `assessment-versions/{version}/publish` | `pmb_assessments.publish` |
| POST | `assessment-versions/{version}/archive` | `pmb_assessments.delete` |
| DELETE | `assessment-versions/{version}` | `pmb_assessments.delete` (draft yang belum diajukan) |

**Bank soal**

| Method | Path | Permission |
|---|---|---|
| GET | `assessment-questions` | `pmb_assessment_questions.read` |
| GET | `assessment-items/{item}` | `pmb_assessment_questions.read` |
| GET | `assessment-items/{item}/preview` | `pmb_assessment_questions.read` |
| POST | `assessment-versions/{version}/items` | `pmb_assessment_questions.create` |
| POST | `assessment-items/{item}/copy` | `pmb_assessment_questions.create` |
| PATCH | `assessment-items/{item}` | `pmb_assessment_questions.update` |
| DELETE | `assessment-items/{item}` | `pmb_assessment_questions.delete` |

**Monitoring, review, dan audit**

| Method | Path | Permission |
|---|---|---|
| GET | `assessment-sessions` | `pmb_assessment_sessions.read` |
| GET | `assessment-sessions/{session}` | `pmb_assessment_sessions.read` |
| GET | `assessment-sessions/{session}/review` | `pmb_assessment_results.read` |
| POST | `assessment-sessions/{session}/notes` | `pmb_assessment_results.update` |
| POST | `assessment-sessions/{session}/flag` | `pmb_assessment_results.update` |
| POST | `assessment-sessions/{session}/unflag` | `pmb_assessment_results.update` |
| POST | `assessment-sessions/{session}/review-complete` | `pmb_assessment_results.update` |
| POST | `assessment-sessions/{session}/invalidate` | `pmb_assessment_sessions.reject` |
| POST | `assessment-sessions/{session}/publish` | `pmb_assessment_results.publish` |
| GET | `assessment-audit-logs` | `pmb_assessment_audit_logs.read` |

Respons galat memakai bentuk standar: `{ success: false, message, errors: { code } }`. Kode yang dipakai: `VERSION_LOCKED`, `VERSION_OPEN`, `VERSION_NOT_APPROVED`, `ADMISSIONS_NOT_APPROVED`, `CODE_DUPLICATE`, `INVALID_CODE`, `ITEM_IN_USE`, `TYPE_MISMATCH`, `REVIEW_REQUIRED`, `RESULT_NOT_PUBLISHED`, dan `INVALID_TRANSITION` (422, mengikuti konvensi state machine PMB).

## 5. Permission

Pemetaan dari nama di prompt ke aksi yang sudah ada:

| Nama di prompt | Permission di sistem | Alasan |
|---|---|---|
| `pmb_assessments.view` | `pmb_assessments.read` | Aksi `read`. |
| `pmb_assessments.create` | `pmb_assessments.create` | Sudah ada. |
| `pmb_assessments.update` | `pmb_assessments.update` | Sudah ada. |
| `pmb_assessments.publish` | `pmb_assessments.publish` | Sudah ada. |
| `pmb_assessments.archive` | `pmb_assessments.delete` | Arsip menonaktifkan versi dari penggunaan aktif, setara dengan hapus. |
| `pmb_assessments.approve` | `pmb_assessments.approve` | Sudah ada. |
| `pmb_assessment_questions.*` | `pmb_assessment_questions.{read,create,update,delete}` | Sudah ada. |
| `pmb_assessment_results.view` | `pmb_assessment_results.read` | Aksi `read`. |
| `pmb_assessment_results.review` | `pmb_assessment_results.update` | Review mengubah catatan dan status review. |
| `pmb_assessment_results.publish` | `pmb_assessment_results.publish` | Sudah ada. |
| `pmb_assessment_sessions.view` / `monitor` | `pmb_assessment_sessions.read` | Monitoring adalah membaca. |
| `pmb_assessment_sessions.invalidate` | `pmb_assessment_sessions.reject` | Invalidasi menolak sesi. |
| `pmb_assessment_audit_logs.view` | `pmb_assessment_audit_logs.read` | Aksi `read`. |

Pembagian per role (`OrganizationalRoleSeeder`):

| Role | Izin |
|---|---|
| `admission_administrator` (Administrator PMB, penyusun dan penelaah) | Assessment read, create, update, delete, approve. Pertanyaan CRUD. Hasil read, update (review). Sesi read, reject (invalidasi). Audit read. **Tidak** publish. |
| `admission_approver` (Pejabat Pengesah) | Assessment read, approve, publish. Pertanyaan read. Hasil read, publish. Sesi read. Audit read. **Tidak** menyusun pertanyaan. |
| Role lain | Tidak ada izin baru. |

Pemisahan tugas: penyusun tidak boleh menyetujui versinya sendiri (`RequiresDistinctApprover`), dan langkah approval hanya dapat diputuskan oleh role yang tercantum di langkah tersebut.

Catatan privasi: nama peserta di daftar dan detail hanya muncul bila pengguna punya `pmb_applications.read`. Jawaban mentah hanya muncul bila punya `pmb_sensitive.read`. Keduanya diputuskan di service, bukan di frontend.

## 6. Workflow Admin

```
Buat jenis ──► Buat versi draft ──► Susun dimensi & pertanyaan
                                        │
                  ┌─────────────────────┘
                  ▼
           Ajukan (cek kelengkapan) ──► Telaah (administrator) ──► Setujui (pejabat pengesah)
                                                     │                        │
                                              Kembalikan ─► draft      Terbitkan ─► versi lama diarsipkan
                                              Tolak ─► ditolak ─► salin jadi revisi
```

Versi yang sudah diajukan, disetujui, atau terbit dikunci. Revisi selalu dibuat sebagai salinan (`clone`), dengan dimensi dan butir yang disalin dan kode yang sama, sehingga `diff` dapat membandingkan dua versi per kode butir.

Satu jenis hanya boleh punya satu versi terbuka (`draft`, `in_review`, atau `approved`). Pembuatan atau salinan kedua ditolak dengan `VERSION_OPEN`.

## 7. Dashboard

Angka dihitung dari data aktual dengan filter yang sama untuk semua kartu dan grafik:

- **Total peserta:** jumlah pendaftaran yang punya sesi.
- **Belum dimulai:** pendaftaran terkirim, tidak ditarik, yang belum punya sesi untuk setiap versi terbit yang berlaku dalam cakupan filter.
- **Menunggu scoring:** sesi `submitted`/`expired` tanpa `scored_at`. Karena scoring berjalan sinkron, nilainya seharusnya nol. Angka di atas nol menunjukkan anomali.
- **Tren 30 hari:** sesi dimulai dan dikirim per hari.
- **Distribusi hasil:** Holland Code teratas dari sesi terbit. Disembunyikan sampai minimal 10 hasil terbit, agar peserta tidak dapat dikenali dari angka kecil.

Bila belum ada data, dashboard menampilkan empty state dengan tautan ke menu Asesmen & Versi. Tidak ada data dummy.

## 8. Belum Selesai

- Penugasan review ke staf tertentu (saat ini semua staf dengan izin dapat mereview).
- Pembuatan pertanyaan dengan jenis selain Likert. Kolom `question_type` sudah ada, tetapi scoring belum mendukungnya.
- Mode pelaksanaan selain daring (on-site). Filter mode sudah ada, tetapi hanya `online` yang tersedia.
- Ekspor hasil dan laporan agregat. Belum ada.
- Pengujian browser penuh dengan data nyata. Lihat [07](07-testing-and-verification.md).
