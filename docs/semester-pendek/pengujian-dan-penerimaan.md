# Semester Pendek — Strategi Pengujian & Kriteria Penerimaan

> Bagian dari [paket rancangan Semester Pendek](./README.md). Pest, tes diletakkan per modul (`app/Modules/Academic/Tests/{Feature,Unit}`, `app/Modules/Finance/Tests/Feature`). Pola fixture mengikuti `makeEnrollmentFixture()` di `KrsEnrollmentTest.php` (set `TenantContext`, factory per tenant).
>
> ⚠️ `phpunit.xml` saat ini **tidak** mendaftarkan `app/Modules/Academic/Tests/Unit` — tambahkan ke testsuite `Unit` sebelum menulis tes unit di bawah.

## 1. Strategi Pengujian

### 1.1 Unit

| Subjek | Kasus kunci |
|---|---|
| `AcademicTermStatus` / mesin transisi | Setiap transisi sah lolos; setiap transisi lain ditolak; `registration_closed → registration_open` hanya sebelum `start_date`; `planned → draft` hanya tanpa KRS |
| `EnrollmentEligibility` (per aturan, dengan data in-memory/factory) | Status mahasiswa (5 nilai `StudentStatus`); jendela pendaftaran (sebelum, di dalam, sesudah, null); duplikat MK lintas kelas; baru vs ulang × `allow_new_courses` × `retake_max_letter_grade`; MK masih berjalan di term lain; prasyarat (tanpa data → lolos, lulus, tidak lulus, `min_letter_grade` custom) |
| `maxSks()` | SP memakai `max_credits` term → setting → 9; reguler mengabaikan SP sebagai "term sebelumnya" (kasus mahasiswa A/B/C di [aturan-bisnis.md §4.5](./aturan-bisnis.md#45-batas-sks)); setting `counts_toward_next_term_sks_quota = true` |
| Penghitung SKS | `pending` + `enrolled` dihitung; `dropped`/`rejected` tidak; hold kedaluwarsa tidak dihitung kapasitas |
| Deteksi bentrok | Irisan penuh, sebagian, bersinggungan tepat (11:00/11:00 → tidak bentrok), hari berbeda, format TIME MySQL (`08:00:00`), normalisasi ruang (`"R.301 "` vs `"r.301"`), ruang kosong/`online`/`daring` dilewati (✅ `ClassScheduleTest`). Kelas nonaktif dan term lain tidak dibandingkan (feature) |
| `RepeatGradePolicy` | `highest`: pilih bobot tertinggi, seri → terbaru; `latest`: terbaru non-draft; satu percobaan; percobaan dengan nilai null diabaikan |
| `transcript()` | IPS SP termasuk MK ulang; IPK & `total_sks` hanya percobaan efektif; contoh tabel [penilaian-dan-transkrip.md §5.2](./penilaian-dan-transkrip.md#52-contoh); `dropped` tidak pernah dihitung; `draft` tidak dihitung |
| Konversi skor | `LetterGrade::fromScore` (✅ sudah dites) — tidak berubah |
| Biaya | SKS × tarif; tarif 0 → tanpa invoice |
| `attendanceRate()` | Pertemuan yang tercatat saja; `permitted`/`sick` sesuai setting; 0 pertemuan → null (bukan 0%) |

### 1.2 Feature / API

| Alur | Kasus |
|---|---|
| CRUD periode | Buat SP (201), SP kedua tahun sama (409), tanggal beririsan (409), validasi 422, `PUT` tidak bisa mengubah `status`, hapus DRAFT tanpa kelas (200) / dengan kelas (409) |
| Transisi status | Setiap transisi lewat endpoint; `→ registration_open` tanpa kelas (409); `→ completed` dengan nilai belum final (409) / dipaksa dengan reason (200 + audit) |
| Buka kelas, jadwal, dosen | 201; bentrok ruang 409; bentrok dosen 409 / `force` 200; dosen tidak aktif 409; ubah jadwal → `warnings.student_conflicts` |
| Pendaftaran mandiri | Lolos semua → `pending`; tanpa workflow & tarif 0 → `enrolled`; batch atomik (satu gagal → nol item tersimpan); setiap `errors.code` pada pipeline |
| Persetujuan | Workflow aktif → `ApprovalRequest` per item; approve → invoice dibuat; reject → `rejected` & kursi dilepas |
| Pembayaran | Catat sebagian → `partial`, item tetap `pending`; lunas → `paid` & item `enrolled`; lebih dari sisa 422; invoice `cancelled` 409; kedaluwarsa (lazy & command) |
| Pembatalan | Mahasiswa batal `pending` → invoice disesuaikan; batal item berbayar → 409; admin drop item berbayar → `REFUND_REQUIRED` |
| Pembatalan kelas | Item → `dropped`, invoice `cancelled`/disesuaikan/refund; kelas dengan nilai 409 |
| Nilai | Input draft (SP) vs submitted (reguler); submit kelas tidak lengkap 409; return; finalize; `PUT` setelah final 409 |
| Revisi nilai | Ajukan → approve → nilai berubah + audit old/new + `GRADE_CHANGED`; reject → tidak berubah; snapshot basi → 409; dua pending 409; term archived 409 |
| Transkrip | Respons memuat kunci lama + `items[]`; penanda ulang; mahasiswa tidak melihat nilai SP non-final |
| Ujian | Item `pending` tidak bisa mulai ujian kelas SP; `enrolled` bisa (reuse); `attendance_blocks_exam` |
| Laporan | Lima tipe, filter term, CSV/PDF, audit `exported` |

### 1.3 Otorisasi

| Skenario | Hasil |
|---|---|
| Mahasiswa `POST /academic-terms` | 403 |
| Dosen `POST /academic-terms` | 403 |
| Dosen `POST /class-sections` | 403 |
| Mahasiswa `PUT /krs-items/{id}/grade` | 403 |
| Dosen menilai KRS di kelas yang **tidak** diampunya | 403 |
| Dosen `GET /grades` → hanya kelasnya | daftar terfilter |
| Dosen `PATCH /class-sections/{id}/grades/finalize` | 403 |
| Mahasiswa `PATCH /student/krs-items/{milik orang lain}/cancel` | 403 |
| Mahasiswa mengirim `student_id` di `POST /student/krs-items` | diabaikan (item atas namanya sendiri) |
| Mahasiswa `GET /student/academic-terms/{draft}/offering` | 404 |
| Admin `POST /krs-items` dengan `override` tanpa `krs.approve` | 403 |
| Keuangan `PATCH /class-sections/...` | 403 |
| Pengguna tenant A memakai ID kelas/term tenant B di setiap endpoint baru | 404 |
| `super_admin` di semua endpoint | lolos (bypass `PermissionRegistry`) |

### 1.4 Integrasi end-to-end

Satu skenario Pest panjang per kombinasi konfigurasi (tanpa/dengan approval × tarif 0/berbayar):

```
Admin buat SP → buka kelas + dosen + jadwal → REGISTRATION_OPEN
→ mahasiswa (nilai D di Ganjil) daftar Basis Data
→ [approve] → invoice → Keuangan catat lunas → enrolled
→ REGISTRATION_CLOSED → ONGOING
→ dosen absen 8 pertemuan → dosen buat & publish ujian → mahasiswa kerjakan
→ GRADING → dosen input B → submit → admin finalize → COMPLETED
→ transkrip: dua percobaan, IPK memakai B, total SKS tidak ganda
→ audit_logs & activity_logs memuat seluruh event yang diharapkan
→ notifikasi tercatat (Notification::fake) untuk setiap event key
```

### 1.5 Uji konkurensi

Tidak bisa dibuktikan di SQLite (penulisan terserialisasi, `lockForUpdate` no-op). Dua lapis:

1. **Tes Pest bertanda `@group mysql`** (dilewati bila `DB_CONNECTION ≠ mysql`), dijalankan di CI dengan service MySQL:
   - Kapasitas 30, terisi 29 → 10 proses paralel (`pcntl_fork` atau `Process::pool` memanggil `artisan` command uji) mendaftarkan 10 mahasiswa berbeda → tepat 1 sukses, 9 `CLASS_FULL`, `COUNT(*) = 30`.
   - Satu mahasiswa, sisa 3 SKS, 2 permintaan paralel ke 2 kelas 3 SKS → tepat 1 sukses.
   - Dua mahasiswa memilih {X,Y} dan {Y,X} serentak → tidak ada deadlock (keduanya selesai, sukses/gagal sesuai kapasitas).
   - Dua pencatatan pembayaran paralel melebihi sisa → hanya yang muat yang sukses.
2. **Uji beban manual** sebelum rilis (mis. k6/JMeter 200 pengguna virtual ke satu kelas berkapasitas 30) di staging MySQL.

### 1.6 Regresi

Seluruh test suite yang ada harus tetap hijau **tanpa diubah**, kecuali tes yang memang menguji perilaku yang sengaja diubah (didaftar eksplisit di PR):

| Area | Yang harus tetap | Perubahan sengaja |
|---|---|---|
| KRS reguler (`KrsEnrollmentTest`) | Kapasitas, duplikat, drop, batas SKS tier | Mahasiswa non-aktif kini ditolak; MK sama kelas lain ditolak |
| Batas SKS reguler | Tier 18/20/22/24 | Term SP diabaikan sebagai "term sebelumnya" |
| Nilai reguler (`GradeRecordingTest`) | `PUT` membuat/mengubah, konversi huruf, override huruf | Baris baru berstatus `submitted`; `PUT` ke nilai `finalized` 409 |
| Absensi (`AttendanceRecordingTest`) | Batch, transaksional, update per pertemuan | Hanya pengampu (setelah Fase 0) |
| Ujian (`Exam*Test`, `StudentExamParticipationTest`, `PublicExamAccessTest`) | Semua | Term archived menolak tulis |
| Transkrip (`TranscriptTest`) | Bentuk respons, IP per term | IPK/total SKS untuk MK yang diulang |
| Dashboard (`DashboardStatsTest`) | Default `is_current` | — |
| Isolasi tenant (`TenantIsolationTest`) | Semua | + tabel baru |
| Label term | `2026/2027 Ganjil`, `… Genap` | + `… Pendek` |
| Frontend | `npm run build` & lint hijau; halaman KRS/Nilai/Absensi admin tetap berfungsi tanpa memilih SP | — |

---

## 2. Kriteria Penerimaan

### AC-01 Membuat Semester Pendek

```
Given  Bagian Akademik login di tenant U
And    belum ada Semester Pendek untuk 2026/2027 di U
When   ia mengirim POST /academic-terms {semester: pendek, academic_year: 2026/2027, tanggal valid}
Then   term dibuat dengan status draft
And    label-nya "2026/2027 Pendek"
And    audit_logs mencatat created dengan user_id-nya
And    term tidak terlihat oleh mahasiswa
```

### AC-02 Menolak SP ganda & tanggal beririsan

```
Given  SP 2026/2027 sudah ada
When   Bagian Akademik membuat SP 2026/2027 lagi
Then   409 dan tidak ada baris baru

Given  Genap 2026/2027 berakhir 2027-06-30
When   SP dibuat dengan start_date 2027-06-25
Then   409 "Tanggal Semester Pendek beririsan dengan 2026/2027 Genap."
```

### AC-03 Transisi status

```
Given  SP berstatus planned dan punya minimal satu kelas aktif
When   Bagian Akademik mengubah status ke registration_open
Then   status berubah, audit mencatat from/to
And    mahasiswa aktif di prodi terkait menerima notifikasi short_term.registration_opened

Given  SP berstatus draft
When   status diubah langsung ke ongoing
Then   409 dan status tetap draft
```

### AC-04 Hanya akademik yang mengonfigurasi

```
Given  pengguna berperan Dosen atau Mahasiswa
When   ia memanggil POST /academic-terms, POST /class-sections, atau PUT /class-sections/{id}/teaching
Then   403
```

### AC-05 Membuka kelas SP dengan dosen & jadwal

```
Given  SP berstatus planned
When   Bagian Akademik membuka kelas Basis Data SP-A kapasitas 30, menugaskan Dosen X, jadwal Senin 08:00–10:30 R.301
Then   kelas, penugasan, dan jadwal tersimpan
And    bila R.301 Senin 09:00–11:00 sudah dipakai kelas aktif lain di term yang sama → 409 SCHEDULE_CONFLICT bentrok ruangan (tidak bisa dipaksa)
And    bila Dosen X mengajar kelas aktif lain Senin 09:00 di term yang sama → 409 SCHEDULE_CONFLICT kecuali force + reason
```

### AC-06 Pendaftaran mandiri berhasil

```
Given  SP berstatus registration_open dan sekarang di dalam jendela pendaftaran
And    mahasiswa berstatus active, tidak punya tagihan lewat jatuh tempo
And    mahasiswa memenuhi prasyarat dan kelas masih punya kursi
And    total SKS setelah ditambah ≤ batas SKS SP
When   mahasiswa mengirim POST /student/krs-items {class_section_ids: [SP-A]}
Then   krs_item dibuat untuk mahasiswa itu sendiri dengan status pending
And    alur persetujuan sesuai konfigurasi tenant dijalankan
And    audit_logs dan activity_logs ENROLLMENT_CREATED tercatat
```

### AC-07 Penolakan kelayakan

```
Given  mahasiswa berstatus leave            When mendaftar   Then 409 STUDENT_NOT_ACTIVE
Given  kelas penuh                          When mendaftar   Then 409 CLASS_FULL
Given  total SKS akan menjadi 12 (batas 9)  When mendaftar   Then 409 SKS_LIMIT_EXCEEDED
Given  sudah terdaftar Basis Data SP-A      When daftar SP-B Then 409 COURSE_ALREADY_TAKEN
Given  jadwal SP-A bentrok kelas lain miliknya When mendaftar Then 409 SCHEDULE_CONFLICT
Given  nilai Basis Data sebelumnya B, retake_max_letter_grade = C
                                            When mendaftar   Then 409 COURSE_NOT_RETAKABLE
Given  pendaftaran sudah ditutup            When mendaftar   Then 409 TERM_NOT_OPEN
And    pada setiap kasus tidak ada krs_item yang tersimpan
```

### AC-08 Batch atomik

```
Given  mahasiswa memilih SP-A (layak) dan SP-B (penuh)
When   ia mengirim keduanya dalam satu permintaan
Then   409 menyebut SP-B
And    SP-A juga tidak tersimpan
```

### AC-09 Kapasitas di bawah konkurensi

```
Given  kapasitas 30 dan 29 peserta aktif
When   dua mahasiswa berbeda mendaftar pada saat bersamaan
Then   tepat satu berhasil dan satu menerima 409 CLASS_FULL
And    jumlah peserta aktif adalah 30
```

### AC-10 Persetujuan dikonfigurasi

```
Given  tidak ada approval workflow aktif untuk krs_item.short_term
When   mahasiswa mendaftar
Then   item langsung masuk tahap pembayaran tanpa approval request

Given  workflow aktif dengan approver Kaprodi
When   mahasiswa mendaftar
Then   approval request dibuat dan Kaprodi menerima notifikasi
When   Kaprodi menolak dengan alasan
Then   item berstatus rejected, kursi dilepas, mahasiswa dinotifikasi beserta alasan
```

### AC-11 Pembayaran tidak lunas karena dibuat

```
Given  item disetujui dengan biaya Rp450.000
Then   invoice unpaid Rp450.000 dibuat dengan due_date = hari ini + payment_due_days
And    item tetap pending
When   Keuangan mencatat pembayaran Rp450.000
Then   invoice paid, item enrolled, mahasiswa menerima short_term.payment_confirmed
And    tidak ada endpoint yang dapat mengubah invoice menjadi paid tanpa pembayaran tercatat
```

### AC-12 Kedaluwarsa

```
Given  item pending dengan invoice unpaid yang due_date-nya kemarin
When   mahasiswa lain mendaftar ke kelas yang sama (atau command terjadwal berjalan)
Then   item lama menjadi dropped, invoice cancelled, kursinya tersedia
And    mahasiswa lama menerima short_term.payment_expired
```

### AC-13 Pembatalan oleh mahasiswa

```
Given  pendaftaran masih dibuka dan item pending belum dibayar
When   mahasiswa membatalkannya
Then   item dropped dan tagihan disesuaikan/dibatalkan

Given  item sudah enrolled dan dibayar
When   mahasiswa mencoba membatalkannya
Then   409 dan item tidak berubah
```

### AC-14 Hanya peserta enrolled yang ikut kuliah

```
Given  item pending
Then   mahasiswa tidak muncul di daftar absensi dosen, tidak bisa mulai ujian, tidak bisa dinilai
```

### AC-15 Dosen hanya di kelasnya

```
Given  Dosen X mengampu SP-A tetapi tidak SP-B
When   ia merekam absensi atau nilai untuk SP-B
Then   403
And    GET /grades dan GET /class-sections untuknya tidak memuat SP-B
```

### AC-16 Finalisasi nilai

```
Given  SP berstatus grading dan semua peserta SP-A punya nilai draft
When   Dosen X submit nilai SP-A
Then   semua nilai submitted dan Bagian Akademik dinotifikasi
When   Bagian Akademik memfinalisasi SP-A
Then   semua nilai finalized dengan finalized_by dan finalized_at
When   Dosen X mengirim PUT nilai untuk peserta SP-A
Then   409 "Nilai sudah difinalisasi; ajukan revisi nilai."
```

### AC-17 Revisi nilai

```
Given  nilai Basis Data mahasiswa M sudah finalized = C
When   Dosen X mengajukan revisi ke B dengan alasan
And    approver menyetujui
Then   nilai menjadi B
And    audit_logs mencatat old C → new B beserta alasan dan pelaku
And    activity_logs GRADE_CHANGED merujuk revision request
And    mahasiswa M dinotifikasi
```

### AC-18 Mata kuliah ulang tidak menghapus riwayat

```
Given  mahasiswa M mendapat D di Basis Data 2026/2027 Ganjil
And    B di Basis Data 2026/2027 Pendek (final)
When   transkrip M diminta
Then   kedua percobaan ada di terms masing-masing
And    percobaan SP bertanda is_repeat = true, attempt_number = 2
And    IPK dihitung dari satu percobaan sesuai repeat_grade_policy
And    total_sks menghitung Basis Data satu kali
And    tidak ada baris grades yang dihapus atau diubah
```

### AC-19 SP tidak merusak batas SKS semester berikutnya

```
Given  mahasiswa A ber-IP Genap 1,80 dan tidak ikut SP
And    SP 2026/2027 ada di antara Genap 2026/2027 dan Ganjil 2027/2028
When   A didaftarkan KRS Ganjil 2027/2028
Then   batas SKS-nya 18, bukan 24
```

### AC-20 Penyelesaian & arsip

```
Given  semua nilai SP sudah finalized
When   Bagian Akademik mengubah status ke completed
Then   mahasiswa dapat melihat nilai SP & IPS-nya dan menerima short_term.grades_released
When   status diubah ke archived
Then   setiap operasi tulis (kelas, KRS, absensi, ujian, nilai, revisi) pada term itu ditolak 409
And    transkrip tetap menampilkan data term itu
```

### AC-21 Isolasi tenant

```
Given  pengguna tenant A
When   ia memanggil endpoint SP mana pun dengan ID term/kelas/KRS/invoice milik tenant B
Then   404
```

### AC-22 Regresi semester reguler

```
Given  seluruh test suite yang ada sebelum fitur SP
Then   semuanya lulus, kecuali tes yang tercantum di §1.6 sebagai perubahan sengaja, yang diperbarui dalam PR yang sama dengan penjelasan
```
