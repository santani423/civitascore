# Randomisasi Ujian Pilihan Ganda

Dokumen ini menjelaskan bagaimana ujian pilihan ganda tingkat lanjut bekerja
di CivitasOne: question pool, jumlah soal per peserta, pemilihan soal,
pengacakan urutan soal, pengacakan opsi jawaban, pemilihan soal manual,
stabilitas percobaan, dan tanggung jawab keamanan. Untuk ringkasan model
data, endpoint, dan permission, lihat [EXAM_MODULE.md](EXAM_MODULE.md).

## 1. Question Pool

Question pool adalah seluruh soal yang disiapkan dosen untuk satu ujian
(`exams.question_pool_size`, dihitung dari jumlah baris `exam_questions`
milik ujian tersebut). Contoh: dosen menyiapkan 100 soal untuk UTS
Pemrograman Web — pool-nya adalah 100.

## 2. Jumlah Soal per Peserta

`exams.questions_per_participant` menentukan berapa soal yang benar-benar
dikerjakan setiap peserta, terpisah dari ukuran pool. Contoh: pool 100 soal,
tetapi setiap peserta hanya mengerjakan 50 soal.

Validasi wajib: `questions_per_participant <= question_pool_size`. Jika
dilanggar, publikasi ujian ditolak dengan pesan:

> Jumlah soal per peserta tidak boleh melebihi jumlah soal dalam question pool.

## 3. Pemilihan Soal (`question_selection_mode`)

Tiga mode, disimpan sebagai enum `QuestionSelectionMode` (backend:
`Modules\Academic\Enums\QuestionSelectionMode`):

- **`all`** — seluruh soal di pool dikirim ke setiap peserta. Biasanya
  `questions_per_participant == question_pool_size`.
- **`random`** — sistem memilih `questions_per_participant` soal secara acak
  dari pool, independen untuk tiap percobaan peserta.
- **`manual`** — dosen menandai soal mana dari pool yang termasuk dalam set
  tetap (`exam_questions.is_selected`). Set yang sama (dalam urutan
  `order_index`) diberikan ke setiap peserta. Publikasi ditolak bila jumlah
  soal yang ditandai `is_selected` kurang dari `questions_per_participant`:
  > Soal belum mencukupi. Anda membutuhkan {N} soal, tetapi baru memilih {M} soal.

## 4. Pengacakan Urutan Soal (`randomize_questions`)

Independen dari mode pemilihan soal. Bila aktif, urutan tampil soal yang
sudah terpilih diacak ulang per percobaan; bila nonaktif, urutan mengikuti
`order_index` yang dikonfigurasi dosen.

## 5. Pengacakan Opsi Jawaban (`randomize_options`)

Independen dari dua pengaturan di atas. Bila aktif, urutan tampil opsi A/B/C/D
setiap soal diacak per percobaan. **Identitas opsi tetap stabil** — setiap
`ExamQuestionOption` punya ULID sendiri, dan jawaban benar dirujuk lewat ID
tersebut (bukan posisi "A"/"B"), sehingga pengacakan tampilan tidak pernah
merusak pemetaan jawaban benar (lihat `ExamQuestionOption.is_correct`,
`app/Modules/Academic/Models/ExamQuestionOption.php`).

Ketiga pengaturan (pemilihan soal, urutan soal, urutan opsi) berlaku
independen satu sama lain — kombinasi apa pun sah.

## 6. Stabilitas Percobaan (Attempt Stability)

Begitu `ExamService::startAttempt()` dipanggil pertama kali untuk pasangan
(ujian, peserta, nomor percobaan), backend menyimpan snapshot penugasan pada
`exam_attempts.question_order` (array ID soal terurut) dan
`exam_attempts.option_order` (map ID soal → array ID opsi terurut). Panggilan
`startAttempt()` berikutnya untuk pasangan yang sama mengembalikan attempt
yang sudah ada, bukan membuat penugasan baru — refresh halaman, keluar-masuk
aplikasi, atau kehilangan koneksi tidak pernah mengubah soal/urutan yang
sudah ditugaskan.

## 7. Tanggung Jawab Backend & Keamanan

Backend adalah satu-satunya sumber kebenaran untuk: penugasan soal per
peserta, jawaban benar, durasi ujian, waktu mulai/selesai percobaan, skor,
dan urutan tampil. Flutter (`mobile/lib/features/exam`) hanya menampilkan
state yang dikembalikan API — tidak pernah menghitung ulang randomisasi di
sisi klien. Resource admin (`ExamQuestionResource`, menyertakan
`is_correct`) dan resource peserta (`ExamAttemptResource`, **tidak pernah**
menyertakan `is_correct`) sengaja dipisahkan agar jawaban benar tidak pernah
terkirim ke tampilan peserta.

## 8. Aturan Validasi (ringkasan)

| Aturan | Pesan |
| --- | --- |
| `question_pool_size == 0` | Question pool masih kosong. Tambahkan soal terlebih dahulu. |
| `questions_per_participant <= 0` | Jumlah soal harus lebih dari 0. (divalidasi saat input, `StoreExamRequest`) |
| `questions_per_participant > question_pool_size` | Jumlah soal per peserta tidak boleh melebihi jumlah soal dalam question pool. |
| Mode manual, `selected_count < questions_per_participant` | Soal belum mencukupi. Anda membutuhkan {N} soal, tetapi baru memilih {M} soal. |
| Ada soal tanpa opsi jawaban benar | Setiap soal harus memiliki tepat satu jawaban benar. |

Tombol Publish di wizard Flutter mengulang validasi yang sama secara lokal
(`lib/features/exam/domain/exam_validation.dart`) untuk umpan balik instan,
tetapi backend (`ExamService::publish()`) tetap menjadi penegak akhir.

## 9. Contoh Konfigurasi

**UTS Pemrograman Web — 100 soal, 50 per peserta, acak penuh**

```json
{
  "title": "UTS Pemrograman Web",
  "questions_per_participant": 50,
  "question_selection_mode": "random",
  "randomize_questions": true,
  "randomize_options": true
}
```
dengan 100 baris `exam_questions` terpasang pada ujian ini.

**Kuis singkat — semua soal, urutan tetap**

```json
{
  "title": "Kuis Mingguan",
  "questions_per_participant": 10,
  "question_selection_mode": "all",
  "randomize_questions": false,
  "randomize_options": false
}
```
dengan tepat 10 baris `exam_questions`.

## 10. Cakupan yang Belum Diimplementasikan

Layar pengerjaan ujian sungguhan untuk mahasiswa (timer berjalan, navigasi
antar-soal, submit) belum dibangun di Flutter — sejalan dengan catatan yang
sudah ada di `RANCANGAN_FLUTTER_CIVITAS_ONE.md` §6.15.10 bahwa layar ini
"di luar cakupan dokumen ini, perlu perancangan lanjutan". Endpoint backend
untuk memulai/menjawab/mengumpulkan percobaan (`ExamAttemptController`)
sudah tersedia dan teruji, sehingga implementasi layar tersebut tinggal
mengonsumsi API yang ada.
