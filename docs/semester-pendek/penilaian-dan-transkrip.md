# Semester Pendek — Pembelajaran, Ujian, Penilaian & Transkrip

> Bagian dari [paket rancangan Semester Pendek](./README.md).

## 1. Pembelajaran (LMS) & Ujian

### 1.1 LMS — tidak dibangun untuk SP

Materi, tugas, kuis, diskusi, dan pengumpulan tugas **belum ada di backend** (tidak ada model/tabel/endpoint; `PortalAssignmentsPage`/`PortalQuizzesPage` berisi data statis — RANCANGAN-AKUN-AKADEMIK §4.9). SP **tidak** membangun LMS sendiri.

Aturan untuk modul LMS di masa depan (agar SP otomatis tercakup): setiap entitas pembelajaran **wajib** terikat ke `class_section_id` (bukan ke `academic_term_id` langsung, bukan ke `course_id`). Periode — reguler atau SP — selalu diturunkan dari `class_sections.academic_term_id`. Dengan begitu tidak ada percabangan "regular vs short" di LMS.

### 1.2 Ujian — reuse 100%

```
exams ✅
  ├── class_section_id  → class_sections.academic_term_id → term SP
  ├── created_by (dosen)
  ├── starts_at / ends_at (jadwal ujian)
  ├── question_selection_mode, randomize_questions, randomize_options  (EXAM_RANDOMIZATION.md)
  ├── weight_percentage, exam_grade_ranges
  └── peserta = krs_items enrolled di kelas itu (ExamService::participantsQuery / resolveEligibleKrsItem)
```

| Kebutuhan | Status |
|---|---|
| UTS (opsional di SP) / UAS | ✅ — keduanya sekadar `exams` dengan judul berbeda; banyak SP hanya punya UAS |
| Jadwal ujian | ✅ `starts_at`/`ends_at`, ditegakkan `assertWithinSchedule()` |
| Kelayakan peserta | ✅ hanya `krs_items` `enrolled` — item `pending` otomatis tidak bisa ikut. 🔧 opsional: syarat kehadiran ([aturan-bisnis.md §9.3](./aturan-bisnis.md#93-minimum-kehadiran)) |
| Bank soal, randomisasi, submit, skor, hasil | ✅ tanpa perubahan |
| Akses publik via link/QR + NIM | ✅ — `resolveStudentByNim()` + `resolveEligibleKrsItem()` sudah memastikan peserta terdaftar di kelas |
| Kepemilikan dosen | ⛔ `ExamPolicy` saat ini permission-only; ikut perbaikan umum ([peran-dan-izin.md §5.1](./peran-dan-izin.md#51-dosen--kelas--prasyarat-umum)) |
| Term ARCHIVED | 🔧 `ExamService::createExam/updateExam/publish` menolak 409 bila term kelasnya `archived` |

Tidak ada `ShortExam`, tidak ada kolom `is_short_semester` di `exams`.

---

## 2. Penilaian

### 2.1 Komposisi nilai

```
Kehadiran ─┐
Tugas      ├─► Nilai akhir (0–100) ─► LetterGrade::fromScore() ─► bobot (weight())
Kuis       │      grades.score            grades.letter_grade
UTS        │
UAS ───────┘
```

**Kondisi saat ini:** dosen memasukkan **nilai akhir** langsung (`PUT /krs-items/{id}/grade {score, letter_grade?}`). Komponen berbobot hanya ada parsial di modul Ujian (`exams.weight_percentage` → `weighted_score` per attempt) dan tidak dijumlahkan otomatis ke `grades`.

**Keputusan:** SP memakai mekanisme yang sama. Konfigurasi komponen nilai berbobot (Kehadiran 10% / Tugas 20% / …) adalah fitur **umum** blueprint §4.16 dan tidak dibangun sebagai bagian SP. Bila nanti dibangun, harus per `class_section` (bobot bisa beda antar kelas) dengan validasi total = 100%, dan SP otomatis ikut.

Skala huruf tetap `LetterGrade` (7 tingkat, hardcoded). Kustomisasi skala per tenant juga gap umum (AKADEMIK §6.2) — tidak dikerjakan di sini.

### 2.2 Status nilai

`grades.status` 🆕: `draft` → `submitted` → `finalized`.

| Status | Arti | Siapa yang mengubah | Dihitung ke IP/IPK? | Terlihat mahasiswa? |
|---|---|---|---|---|
| `draft` | Disimpan dosen, belum diserahkan | Dosen pengampu (`PUT`) | ❌ | ❌ |
| `submitted` | Diserahkan dosen untuk ditinjau | Dosen (submit per kelas) | ✅ | Reguler: ✅ (perilaku sekarang) · SP: ❌ sampai `finalized` |
| `finalized` | Dikunci oleh Bagian Akademik | Admin (`grades.finalize`) | ✅ | ✅ |

"Reviewed" dari prompt bukan status tersendiri — peninjauan adalah tindakan admin di antara `submitted` dan `finalized` (admin bisa mengembalikan ke `draft` dengan `reason`).

**Penegakan bertahap (menghindari regresi):**

| | Term `pendek` | Term reguler (fase 1) |
|---|---|---|
| `PUT` nilai baru membuat status | `draft` | `submitted` (sama dengan sekarang: langsung dihitung) |
| Wajib submit & finalize sebelum COMPLETED | ✅ | ❌ (endpoint tersedia, opsional dipakai) |
| `PUT` ditolak bila `finalized` | ✅ | ✅ (tidak ada nilai reguler yang `finalized` sampai admin memfinalisasi, jadi tidak ada perubahan perilaku) |

### 2.3 Alur finalisasi

```
Dosen pengampu                     Bagian Akademik
──────────────                     ───────────────
PUT  /krs-items/{id}/grade   (berulang, status draft)
         │
PATCH /class-sections/{id}/grades/submit
   • semua peserta enrolled harus punya nilai — 409 bila ada yang kosong
   • draft → submitted (dalam satu transaksi)
   • notifikasi ke admin: short_term.grades_submitted
                                   │
                                   ├── kembalikan: PATCH /class-sections/{id}/grades/return {reason}
                                   │      submitted → draft, notifikasi ke dosen
                                   │
                                   └── PATCH /class-sections/{id}/grades/finalize
                                          • semua harus submitted — 409 bila ada draft
                                          • submitted → finalized, finalized_at, finalized_by
                                          • term harus GRADING (untuk SP)
```

Setelah `finalized`, `PUT /krs-items/{id}/grade` → 409 "Nilai sudah difinalisasi; ajukan revisi nilai." Tidak ada jalur yang mengubah nilai final secara diam-diam — termasuk untuk admin.

Finalisasi per kelas (bukan per baris) karena itulah unit kerja nyata dosen/akademik, dan menghindari kelas setengah final.

---

## 3. Riwayat Nilai

Riwayat perubahan berasal dari `audit_logs` (trait `Auditable` dipasang pada `Grade`): setiap `updated` mencatat `old_values`/`new_values` (score, letter_grade, status), `user_id`, `reason` (dari input `reason` atau header `X-Change-Reason` — mekanisme ✅ sudah ada di `AuditLogService::record()`), IP, user agent, waktu. Endpoint baca: `GET /audit-logs?filter[auditable_type]=...` (✅ sudah ada, permission `audit_logs.read`) — 🔧 tambahkan `auditable_id` ke `filterable` di `AuditLogController::index()` (saat ini hanya `action`, `user_id`, `auditable_type`). Untuk tampilan riwayat di halaman nilai (yang dibuka role tanpa `audit_logs.read`), sediakan `GET /grades/{grade}/history` dengan otorisasi `GradePolicy` yang membaca `audit_logs` untuk grade itu saja. Tidak dibutuhkan tabel `grade_histories`.

## 4. Revisi Nilai

Mengikuti blueprint §6 "Pengajuan Perubahan Nilai" dan modul `ApprovalWorkflow` yang ada.

```
Dosen pengampu / Bagian Akademik
    │  POST /grades/{grade}/revision-requests {score, letter_grade?, reason}
    │     • grade harus finalized (untuk non-final cukup PUT biasa)
    │     • term tidak boleh ARCHIVED
    │     • maks. 1 permintaan pending per grade
    ▼
grade_revision_requests (pending, snapshot old_*)
    │
    ├── workflow 'grade_revision' aktif → ApprovalRequestService::submit()
    │       (mis. langkah 1 Kaprodi, langkah 2 Bagian Akademik — dikonfigurasi tenant)
    │
    └── tidak ada workflow → menunggu keputusan pemegang grades.approve
              PATCH /grade-revision-requests/{id}/approve|reject {reason?}
    ▼
Disetujui (event ApprovalRequestApproved ✅ atau endpoint fallback)
    • DB::transaction + lockForUpdate(grade):
        - cek grade masih sama dengan snapshot old_* (kalau tidak → 409, permintaan basi)
        - grade.update(score, letter_grade)  → Auditable mencatat old/new + reason
        - request.status = approved, decided_at, applied_at
    • activity_logs GRADE_CHANGED (properti: revision_request_id, old, new, approver)
    • notifikasi ke mahasiswa & pengaju: short_term.grade_revised
Ditolak → request.status = rejected, nilai tidak berubah, notifikasi ke pengaju
```

**Field audit wajib** untuk setiap perubahan nilai: `actor` (user_id), `action`, `entity` (`grades`), `entity_id`, `before` (score, letter_grade, status), `after`, `reason`, `timestamp`, `ip_address`, `user_agent`, plus `revision_request_id` & `approval_request_id` bila lewat revisi.

---

## 5. IPS, IPK & Mata Kuliah Ulang

### 5.1 Definisi

| Istilah | Perhitungan |
|---|---|
| **IPS SP** | Σ(SKS × bobot) ÷ Σ SKS dari **semua** nilai terhitung di term SP itu — termasuk mata kuliah ulang. IPS menggambarkan prestasi di periode itu, jadi percobaan ulang **selalu** masuk IPS periodenya. |
| **IPK** | Σ(SKS × bobot) ÷ Σ SKS dari **satu percobaan efektif per mata kuliah** (`course_id`) menurut `academic.repeat_grade_policy`. |
| **Total SKS lulus** | Σ SKS percobaan efektif (bukan jumlah semua percobaan). |
| **Nilai terhitung** | `letter_grade IS NOT NULL` **dan** `grades.status ≠ draft` **dan** `krs_items.status = enrolled` (filter `enrolled` sudah ada di `gradedCredits()`). |

### 5.2 Contoh

| Term | MK | SKS | Nilai | Bobot |
|---|---|---|---|---|
| 2026/2027 Ganjil | Basis Data | 3 | D | 1,00 |
| 2026/2027 Ganjil | Algoritma | 3 | B | 3,00 |
| 2026/2027 Pendek | Basis Data | 3 | B | 3,00 |

| | Hari ini (bug) | `highest` | `latest` |
|---|---|---|---|
| IPS Ganjil | 2,00 | 2,00 | 2,00 |
| IPS Pendek | 3,00 | 3,00 | 3,00 |
| IPK | (3+9+9)/9 = **2,33** | (9+9)/6 = **3,00** | (9+9)/6 = **3,00** |
| Total SKS | **9** | **6** | **6** |

Kasus di mana kebijakan berbeda: SP Basis Data mendapat **E** → `highest` memakai D (Ganjil), `latest` memakai E (SP).

### 5.3 Perubahan pada `AcademicRecordService::transcript()`

```
grades = gradedCredits(student)                     # sudah ada; + filter status ≠ draft
terms  = grades.groupBy(academic_term_id) → IPS     # tidak berubah
effective = grades.groupBy(classSection.course_id)
                  .map(policy.pick)                 # highest | latest
ipk, total_sks = sum(effective)                     # berubah: dulu sum(grades)
```

`termGpa()` (dipakai `maxSks()`) tetap IPS per term — tidak terpengaruh kebijakan ulang.

**Ini perubahan perilaku untuk semester reguler juga** (mahasiswa yang pernah mengulang MK di reguler akan melihat IPK berubah). Perubahan ini adalah koreksi, tetapi harus disetujui akademik dan diumumkan sebelum rilis — lihat [rencana-implementasi.md](./rencana-implementasi.md#fase-2--penilaian--transkrip).

### 5.4 Tidak ada kode kebijakan per universitas

Kebijakan adalah strategi kecil (`HighestGradePolicy`, `LatestGradePolicy` mengimplementasikan satu interface `RepeatGradePolicy::pick(Collection $attempts): Grade`), dipilih dari setting tenant. Kebijakan baru = kelas baru + nilai enum baru, bukan `if ($university->code === ...)`.

---

## 6. Transkrip

### 6.1 Respons (kompatibel mundur)

Kunci lama (`terms[].academic_term_id/label/sks/ip`, `ipk`, `total_sks`) **tidak berubah bentuk**. Ditambah:

```json
{
  "terms": [
    {
      "academic_term_id": "01J...",
      "label": "2026/2027 Pendek",
      "semester": "pendek",
      "sks": 3,
      "ip": 3.0,
      "items": [
        {
          "krs_item_id": "01J...",
          "course_id": "01J...",
          "course_code": "IF302",
          "course_name": "Basis Data",
          "credits": 3,
          "letter_grade": "B",
          "score": "78.00",
          "grade_status": "finalized",
          "attempt_number": 2,
          "is_repeat": true,
          "counts_toward_ipk": true
        }
      ]
    }
  ],
  "ipk": 3.0,
  "total_sks": 6,
  "repeat_grade_policy": "highest"
}
```

Untuk mahasiswa (portal), item term SP dengan `grade_status ≠ finalized` disembunyikan (atau `letter_grade: null`), dan IPK dihitung tanpa item itu.

### 6.2 Tampilan

`academic.transcript_repeat_display`:

| Nilai | Tampilan |
|---|---|
| `all_attempts` (default) | Semua percobaan ditampilkan di term masing-masing. Percobaan yang tidak dihitung ke IPK diberi penanda (mis. tanda kurung / teks abu-abu + keterangan "diulang di 2026/2027 Pendek"). Percobaan ulang diberi penanda "U" (ulang). |
| `effective_only` | Hanya percobaan efektif yang ditampilkan per MK, di term tempat percobaan itu terjadi. |

Contoh `all_attempts`:

```
2026/2027 Ganjil
  IF302  Basis Data        3   (D)   ← tidak dihitung, digantikan 2026/2027 Pendek
  IF305  Algoritma         3    B
2026/2027 Pendek
  IF302  Basis Data  [U]   3    B
─────────────────────────────────
Total SKS 6     IPK 3,00     (kebijakan: nilai tertinggi)
```

Transkrip **tidak pernah** dimodifikasi langsung — selalu hasil hitung dari `grades`. Perubahan transkrip hanya terjadi lewat revisi nilai (§4).

### 6.3 KHS SP

KHS = satu elemen `terms[]` untuk term SP. Tidak perlu endpoint terpisah: `GET student/transcript?filter[academic_term_id]=...` atau frontend memilih elemen yang sesuai.
