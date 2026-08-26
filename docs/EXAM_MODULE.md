# Modul Ujian (Exam)

Ringkasan teknis modul Ujian — model data, endpoint API, dan permission.
Untuk penjelasan perilaku randomisasi dan aturan validasi, lihat
[EXAM_RANDOMIZATION.md](EXAM_RANDOMIZATION.md). Konteks bisnis modul secara
umum ada di `RANCANGAN-APLIKASI.md` §4.18 "Modul Ujian".

Modul ini hidup di dalam `app/Modules/Academic` (backend Laravel) dan
`mobile/lib/features/exam` (Flutter, sisi konfigurasi dosen/admin).

## Model Data

| Tabel | Deskripsi |
| --- | --- |
| `exams` | Konfigurasi satu ujian — terikat ke `class_sections`, menyimpan `questions_per_participant`, `question_selection_mode`, flag pengacakan, `max_attempts`, status publikasi. |
| `exam_questions` | Satu soal dalam pool sebuah ujian — teks, poin, `order_index`, `is_selected` (dipakai mode manual). |
| `exam_question_options` | Opsi jawaban satu soal — ULID-nya adalah identitas stabil untuk pemetaan jawaban benar; `is_correct` hanya terlihat di resource admin. |
| `exam_attempts` | Satu percobaan seorang peserta (`krs_item_id`) atas sebuah ujian — snapshot `question_order`/`option_order`, status, skor. |
| `exam_attempt_answers` | Jawaban peserta per soal dalam satu percobaan. |

**Catatan desain:** `ExamAttempt` terikat ke `KrsItem` (baris pendaftaran
mahasiswa pada kelas), bukan langsung ke `auth()->user()` — mengikuti pola
yang sudah dipakai `Grade` dan `Attendance`, karena aplikasi ini belum
memiliki identity link Student/Lecturer → User untuk self-service murni.
Percobaan direkam oleh pengguna berizin (dosen/pengawas) atas nama peserta.

## Endpoint API

Semua endpoint di bawah `auth:sanctum` + resolusi tenant (`X-University-ID`),
mengikuti konvensi `Modules/Academic/Routes/api.php`.

| Method | Path | Permission |
| --- | --- | --- |
| GET | `/exams` | `exams.read` |
| POST | `/exams` | `exams.create` |
| GET | `/exams/{exam}` | `exams.read` |
| PUT | `/exams/{exam}` | `exams.update` |
| DELETE | `/exams/{exam}` | `exams.delete` |
| PATCH | `/exams/{exam}/publish` | `exams.publish` |
| GET | `/exams/{exam}/questions` | `exams.read` |
| POST | `/exams/{exam}/questions` | `exams.create`, `exams.update` |
| PUT | `/exam-questions/{examQuestion}` | `exams.update` |
| DELETE | `/exam-questions/{examQuestion}` | `exams.update`, `exams.delete` |
| POST | `/krs-items/{krsItem}/exams/{exam}/attempt` | `exam_attempts.create` |
| PUT | `/exam-attempts/{examAttempt}/answer` | `exam_attempts.update` |
| PATCH | `/exam-attempts/{examAttempt}/submit` | `exam_attempts.update` |

## Permission

Ditambahkan ke katalog di `RolePermissionSeeder` (resource `exams` dan
`exam_attempts`) dan diberikan ke role `lecturer` penuh (`exams.*`,
`exam_attempts.*`), serta `exams.read` ke seluruh role admin/read-only yang
sudah punya akses baca modul Akademik lainnya (`university_owner`,
`university_administrator`, `rector`, `vice_rector`, `dean`,
`head_of_study_program`, `academic_administrator`, `auditor`).

## Backend

- Model: `app/Modules/Academic/Models/{Exam,ExamQuestion,ExamQuestionOption,ExamAttempt,ExamAttemptAnswer}.php`
- Service (aturan bisnis): `app/Modules/Academic/Services/ExamService.php`
- Controller: `app/Modules/Academic/Controllers/{Exam,ExamQuestion,ExamAttempt}Controller.php`
- Policy: `app/Modules/Academic/Policies/{Exam,ExamAttempt}Policy.php`
- Test: `app/Modules/Academic/Tests/Feature/{ExamConfigurationTest,ExamAttemptTest}.php`

## Flutter

- Domain: `mobile/lib/features/exam/domain/` — entities, `ExamRepository`,
  `ExamValidation` (aturan validasi client-side, dites di
  `mobile/test/features/exam/exam_form_validation_test.dart`).
- Data: `mobile/lib/features/exam/data/` — `ExamRemoteDataSource`,
  `ExamRepositoryImpl`.
- Presentation: `mobile/lib/features/exam/presentation/` — wizard
  (`ExamFormScreen`, 3 langkah: Informasi, Soal, Preview & Publish), bank
  soal (`QuestionBankScreen`), pratinjau sebagai mahasiswa
  (`ExamPreviewScreen`), daftar ujian (`ExamListScreen`).
- Route: `/akademik/ujian`, `/akademik/ujian/baru`, `/akademik/ujian/:id/edit`,
  `/akademik/ujian/:id/soal` (`core/router/route_paths.dart`,
  `core/router/app_router.dart`), digerbangi `PermissionGate`.

**Belum dibangun:** layar pengerjaan ujian sungguhan untuk mahasiswa. Lihat
[EXAM_RANDOMIZATION.md](EXAM_RANDOMIZATION.md) §10.
