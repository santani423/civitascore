# Semester Pendek — Frontend, Dashboard, Laporan & Mobile

> Bagian dari [paket rancangan Semester Pendek](./README.md).

## 1. Pola Frontend yang Dipakai Ulang

Stack web: React 19 + Vite + TypeScript (`frontend/src`), `react-router-dom` v7, `axios` (`services/api.ts`), `zustand` (`stores/authStore`, `tenantStore`), `react-hook-form` + `zod`.

| Kebutuhan | Pakai yang ada |
|---|---|
| Ambil data | `hooks/useFetch.ts`, `hooks/usePaginatedList.ts` |
| Service API | Tambah fungsi ke `services/academicService.ts` & `services/financeService.ts` (bukan file service SP baru) |
| Tipe | `types/academic.ts` (tambah `AcademicTerm`, `AcademicTermStatus`, `ClassSectionSchedule`, dst.) |
| Komponen | `components/ui/*`: `PageHeader`, `Card`, `DataTable`, `ListPagination`, `Modal`, `Badge`, `StatCard`, `EmptyState`, `Skeleton`, `Alert`, `Select`, `Input`, `Textarea`, `Checkbox`, `Tooltip` |
| Gating aksi | `hooks/usePermission.ts`; `hooks/useIsStudent.ts` untuk portal |
| Form | Pola `EnrollKrsModal`/`GradeFormModal` (`Modal` + `react-hook-form` + `zod`) |
| Navigasi | `constants/nav.ts` (`NAV_ITEMS` digerbangi permission), `constants/routes.ts` (`ROUTES`) |

Gating UI **hanya kenyamanan** — setiap aksi juga diotorisasi server ([keamanan-audit-integritas.md §1](./keamanan-audit-integritas.md#1-otorisasi)).

**Prinsip:** tidak ada halaman "KRS SP" terpisah dari halaman KRS yang ada bila cukup dengan filter periode. Halaman baru hanya untuk yang memang baru: manajemen periode, dashboard SP, pendaftaran mandiri mahasiswa.

### 1.1 Keadaan standar setiap halaman

| Keadaan | Perilaku |
|---|---|
| Memuat | `Skeleton` sesuai bentuk konten (tabel/kartu), bukan spinner layar penuh |
| Kosong | `EmptyState` dengan kalimat spesifik + aksi utama bila pengguna berhak (mis. "Belum ada kelas di Semester Pendek ini. [Buka Kelas]") |
| Error jaringan/5xx | `Alert` variant error + tombol "Coba lagi" |
| 403 | Halaman `errors` yang ada |
| 404 (term draft / tenant lain) | Halaman `errors` 404 |
| 409 | Pesan server ditampilkan apa adanya (sudah bahasa Indonesia); bila ada `errors.code`, sorot baris/kelas terkait |
| 422 | Error per field di form (pemetaan `errors` Laravel ke `react-hook-form` `setError`) |
| Responsif | `DataTable` bergulir horizontal di < 768px; panel ringkasan menumpuk vertikal; aksi utama tetap terlihat |

---

## 2. Halaman Admin / Akademik

Menu **Akademik** di `NAV_ITEMS` ditambah item **"Periode Akademik"** (`academic_terms.read`). SP muncul sebagai periode dengan badge "Pendek"; tidak ada menu top-level "Semester Pendek" terpisah.

| # | Halaman | Rute (usulan) | Permission | Baru/Ubah |
|---|---|---|---|---|
| 2.1 | Dashboard SP (tab di detail periode) | `/akademik/periode/:id` | `academic_terms.read` | 🆕 |
| 2.2 | Daftar Periode | `/akademik/periode` | `academic_terms.read` | 🆕 |
| 2.3 | Buat/Ubah Periode | modal di 2.2 / 2.1 | `academic_terms.create/update` | 🆕 |
| 2.4 | Kelas SP (penawaran) | tab "Kelas" di 2.1 → `ClassSectionsPage` dengan `academic_term_id` terkunci | `classes.read/create` | 🔧 |
| 2.5 | Detail Kelas: dosen & jadwal | `ClassSectionDetailPage` | `classes.update` | 🔧 |
| 2.6 | Pendaftaran (KRS) | tab "Pendaftaran" di 2.1 → `KrsPage` difilter term | `krs.read` | 🔧 |
| 2.7 | Pemantauan Pembayaran | tab "Pembayaran" di 2.1 | `invoices.read` atau `krs.read` | 🆕 (komponen) |
| 2.8 | Pemantauan Nilai | tab "Nilai" di 2.1 → `GradesPage` difilter term | `grades.read` | 🔧 |
| 2.9 | Laporan | `/laporan` (✅ `pages/report`) | `reports.read` | 🔧 |
| 2.10 | Pengaturan Kebijakan SP | `/pengaturan/akademik` (`pages/settings`) | `tenant_profile.update` | 🆕 |

### 2.1 Dashboard SP

Sumber data: `GET /academic-terms/{id}/summary` (satu panggilan, agregat di server).

```
┌ 2026/2027 Pendek ───────────── [REGISTRATION_OPEN ▾ Ubah Status] ┐
│ Pendaftaran: 21–30 Jun 2027 · Kuliah: 5 Jul – 20 Ags 2027 · Maks 9 SKS │
├──────────────┬──────────────┬──────────────┬──────────────┬───────────┤
│ Mahasiswa    │ Disetujui/   │ Menunggu     │ Lunas /      │ Kelas /   │
│ terdaftar    │ Terdaftar    │ (approval/   │ Belum lunas  │ Dosen     │
│ 142          │ 118          │ bayar) 24    │ 101 / 17     │ 18 / 12   │
├──────────────┴──────────────┴──────────────┴──────────────┴───────────┤
│ ⚠ Perlu tindakan                                                      │
│  • 3 kelas di bawah minimum peserta        [lihat]                    │
│  • 2 kelas hampir penuh (≥ 90%)            [lihat]                    │
│  • 4 kelas belum punya dosen / jadwal      [lihat]                    │
│  • 7 mahasiswa kehadiran < 75%             [lihat]   (ONGOING)        │
│  • 5 kelas nilai belum disubmit · 2 belum final      (GRADING)        │
└───────────────────────────────────────────────────────────────────────┘
[Kelas] [Pendaftaran] [Pembayaran] [Nilai] [Riwayat Status]
```

Metrik di `summary`: `students_registered` (distinct mahasiswa dengan item pending/enrolled), `enrollments_enrolled`, `enrollments_pending_approval`, `enrollments_pending_payment`, `enrollments_rejected`, `enrollments_dropped`, `classes_total`, `classes_cancelled`, `classes_below_minimum`, `classes_near_capacity`, `classes_without_lecturer`, `classes_without_schedule`, `lecturers_total`, `invoices_paid`, `invoices_unpaid`, `amount_billed`, `amount_paid`, `students_low_attendance`, `grades_draft`, `grades_submitted`, `grades_finalized`.

Kartu "Perlu tindakan" muncul sesuai status term (mis. nilai hanya relevan di GRADING). Dropdown "Ubah Status" hanya menampilkan transisi yang sah dari status saat ini; aksi destruktif (→ COMPLETED paksa, → ARCHIVED) memakai modal konfirmasi dengan kolom `reason`.

### 2.2–2.3 Daftar & form periode

`DataTable` kolom: Tahun Akademik, Semester (badge), Status (badge berwarna per status), Tanggal Kuliah, Jendela Pendaftaran, Jumlah Kelas. Filter: semester, status, tahun. Form (zod): validasi tanggal berurutan di klien (cerminan aturan server); `semester` dan `academic_year` dinonaktifkan saat edit bila sudah ada kelas.

### 2.4–2.5 Kelas, dosen, jadwal

- `ClassSectionsPage` mendapat tombol "Buka Kelas" (modal: pilih MK — difilter prodi —, kode kelas, kapasitas, minimum peserta).
- Kolom tambahan saat melihat term SP: Peserta (enrolled/pending/kapasitas, bar), Dosen, Jadwal ringkas, Status (Aktif/Batal/Di bawah minimum).
- `ClassSectionDetailPage`: panel **Dosen Pengampu** (pilih dari `LecturersPage` data, peran koordinator/anggota) dan panel **Jadwal** (baris hari + jam + ruang, tambah/hapus). Simpan menampilkan konflik dari 409 secara inline per baris; checkbox "Paksa" + alasan hanya untuk bentrok dosen. Peringatan `student_conflicts` tampil sebagai `Alert` warning setelah simpan.
- Aksi "Batalkan Kelas" (modal, alasan wajib, menampilkan jumlah peserta & tagihan terdampak sebelum konfirmasi).

### 2.6 Pendaftaran

`KrsPage` yang ada + filter status baru (`pending`, `rejected`) + kolom "Persetujuan" & "Pembayaran" (turunan). `EnrollKrsModal` mendapat bagian "Override aturan" (checkbox per aturan + alasan) yang hanya muncul bila `usePermission('krs.approve')`. Persetujuan dilakukan di halaman approval yang ✅ sudah ada (`pages/approvals`) — dari sini cukup tautan.

### 2.7 Pemantauan pembayaran

Tabel: NIM, Nama, Item (MK), Jumlah, Status Invoice, Jatuh Tempo (merah bila lewat). Untuk `finance_administrator`, tombol "Catat Pembayaran" (modal `RecordPaymentRequest`) — atau dikerjakan dari `pages/finance/InvoicesPage` yang ✅ ada dengan filter periode.

### 2.8 Pemantauan nilai

Per kelas: Dosen, Peserta, Draft/Submit/Final, aksi "Kembalikan" (alasan) dan "Finalisasi Kelas" (konfirmasi). Tab "Revisi Nilai" daftar `grade_revision_requests`.

### 2.10 Pengaturan kebijakan SP

Form atas kunci `academic.short_term.*` dan `academic.*` ([desain-database.md §5](./desain-database.md#5-konfigurasi-per-tenant-university_settings)) via `PUT /tenant/settings` ✅. Setiap field menjelaskan dampaknya dalam satu kalimat (mis. "Bila aktif, IP Semester Pendek ikut menentukan batas SKS semester berikutnya"). Perubahan `repeat_grade_policy` menampilkan peringatan bahwa IPK seluruh mahasiswa akan dihitung ulang.

---

## 3. Halaman Mahasiswa (Portal)

**Prasyarat:** portal saat ini statis (RANCANGAN-AKUN-MAHASISWA §4.3). Halaman SP dibangun langsung terhubung ke API; jangan menambah halaman statis baru.

Struktur menu portal "Akademik" (✅ ada) ditambah satu entri **"Semester Pendek"** yang hanya muncul bila ada term SP berstatus ≥ PLANNED (`GET /student/academic-terms?filter[semester]=pendek`):

```
Semester Pendek
├── Ringkasan          ← status term, aturan (maks SKS, biaya/SKS), status saya
├── Pilih Mata Kuliah  ← offering + eligibility; keranjang; submit
├── KRS Saya           ← item + status persetujuan/pembayaran; batalkan
├── Jadwal             ← PortalSchedulePage difilter term SP
├── Absensi            ← PortalAttendancePage difilter term SP
├── Ujian              ← PortalExamsPage (✅ sudah tersambung API) — otomatis memuat ujian kelas SP
├── Nilai              ← PortalGradesPage difilter term SP (hanya nilai final)
├── Tagihan            ← PortalInvoicesPage difilter term SP
└── Transkrip          ← PortalTranscriptPage (menampilkan penanda ulang)
```

Materi/Tugas/Kuis **tidak** dimasukkan karena backend LMS belum ada.

### 3.1 Halaman "Pilih Mata Kuliah"

- Kartu per kelas: kode & nama MK, SKS, kelas, dosen, jadwal, sisa kursi, biaya, badge "Ulang (nilai sebelumnya D)" / "Baru".
- Kelas tidak layak ditampilkan **tetap** (abu-abu) dengan alasan dari `eligibility.reasons` — mahasiswa perlu tahu kenapa, bukan hanya tidak melihatnya.
- Keranjang samping (bawah pada mobile): total SKS vs maksimum (bar), total biaya, bentrok antar-pilihan dideteksi di klien dari `schedules` (cerminan logika server, hanya untuk umpan balik cepat).
- Submit → modal konfirmasi (daftar, total SKS, total biaya, "Tagihan akan dibuat setelah disetujui" bila approval aktif) → `POST /student/krs-items`. Bila 409, kelas bermasalah disorot dan pesan server ditampilkan.

### 3.2 Keadaan UI per status

| Keadaan | Ringkasan | Pilih MK | KRS Saya |
|---|---|---|---|
| PLANNED | "Pendaftaran dibuka {tgl}." | Daftar kelas terlihat, tombol nonaktif | — |
| REGISTRATION_OPEN, belum daftar | Hitung mundur penutupan | Aktif | Kosong + ajakan memilih |
| Menunggu persetujuan | Badge kuning "Menunggu persetujuan" | Aktif (tambah MK) | Item dengan status approval |
| Menunggu pembayaran | Badge oranye + jumlah + jatuh tempo + tautan Tagihan | Aktif | Item + tautan tagihan |
| Terdaftar | Badge hijau "Terdaftar" + jadwal minggu ini | Aktif selama masih dibuka | Item terkunci (batal hanya bila bebas biaya) |
| REGISTRATION_CLOSED | "Pendaftaran ditutup" | Nonaktif | Baca saja |
| ONGOING | Jadwal hari ini, kehadiran %, ujian mendatang | Tersembunyi | Baca saja |
| GRADING | "Nilai sedang diproses" | Tersembunyi | Baca saja |
| COMPLETED | IPS SP, nilai per MK, dampak ke IPK | Tersembunyi | Baca saja |
| Ditolak / kedaluwarsa | Badge merah + alasan, bisa daftar ulang bila masih dibuka | Aktif | Item dengan status |

---

## 4. Laporan

Tipe laporan baru di `ReportService::build()`, semua ber-filter `academic_term_id` (wajib) dan opsional `study_program_id`. Ekspor CSV & PDF mengikuti pola `ExamController::exportRecap()` (proyek **tidak** punya pustaka Excel; CSV UTF-8 bisa dibuka Excel).

| Tipe | Kolom |
|---|---|
| `sp-enrollment` | NIM, Nama, Prodi, Kode MK, Mata Kuliah, Kelas, SKS, Ulang (Y/T), Status KRS, Status Pembayaran |
| `sp-courses` | Kode MK, Mata Kuliah, Kelas, Kapasitas, Terdaftar, Menunggu, Sisa, Min. Peserta, Dosen, Jadwal, Status |
| `sp-grades` | NIM, Nama, Kode MK, Mata Kuliah, Kelas, Nilai Angka, Huruf, Bobot, Status Nilai, Ulang (Y/T), Nilai Sebelumnya |
| `sp-finance` | NIM, Nama, No. Invoice, Item, Jumlah, Dibayar, Status, Jatuh Tempo, Tgl Bayar, Metode, Referensi |
| `sp-summary` | Total terdaftar, total selesai (nilai final), total tidak lulus (E / di bawah batas lulus), rata-rata bobot nilai, rata-rata IPS SP, jumlah MK ulang vs baru, jumlah mahasiswa yang IPK-nya naik |

Ekspor tercatat di `audit_logs` (`exported`).

---

## 5. Mobile (Flutter)

### 5.1 Kondisi saat ini

`mobile/lib/features/` hanya berisi `auth`, `dashboard`, dan `exam` (konfigurasi ujian sisi dosen/admin: `ExamListScreen`, `ExamFormScreen`, `QuestionBankScreen`, `ExamPreviewScreen`). Stack: Riverpod (+ generator), go_router, Dio, freezed; gating `PermissionGate` (`core/permission`). **Tidak ada satu pun fitur mahasiswa**, termasuk layar mengerjakan ujian.

### 5.2 Keputusan

| Fungsi | Mobile? | Alasan |
|---|---|---|
| Konfigurasi periode, kelas, dosen, jadwal, override, finalisasi nilai, laporan | ❌ **web saja** | Tugas administratif padat tabel, dilakukan beberapa kali per periode, butuh layar lebar & audit alasan |
| Persetujuan KRS (approver) | ⏳ nanti | Bernilai di mobile, tetapi bagian dari fitur approval umum, bukan SP |
| Dosen: daftar kelas SP, absensi | ⏳ setelah identity link dosen | Absensi di kelas adalah use case mobile terkuat; dibangun sebagai fitur absensi umum (`features/attendance`), SP ikut lewat filter term |
| Dosen: ujian kelas SP | ✅ otomatis | `features/exam` sudah memilih `class_section`; kelas SP muncul tanpa perubahan setelah filter "kelas saya" ada |
| Mahasiswa: ringkasan SP, KRS Saya, status pembayaran, jadwal, nilai, notifikasi | ⏳ fase mobile mahasiswa | Berguna, tetapi mobile belum punya fondasi fitur mahasiswa sama sekali. Membangunnya **khusus untuk SP lebih dulu** akan membalik urutan yang benar |
| Mahasiswa: memilih MK SP (submit KRS) | ⏳ opsional, setelah butir di atas | Paling kompleks (keranjang, bentrok, biaya); web cukup untuk periode pendaftaran ~10 hari |
| Notifikasi push SP | ✅ via kanal `push` bila tenant mengaktifkan & app mendaftarkan token | Tidak perlu layar baru |

### 5.3 Bila fase mobile mahasiswa dikerjakan

Ikuti struktur fitur yang ada (data / domain / presentation, `freezed` entities, `Repository` abstrak, `RemoteDataSource` Dio, controller Riverpod):

```
mobile/lib/features/academic_student/
├── domain/entities/  academic_term.dart, krs_item.dart, offering_class.dart, transcript.dart
├── domain/repositories/student_academic_repository.dart
├── data/  student_academic_remote_data_source.dart, student_academic_repository_impl.dart
└── presentation/screens/
      short_term_overview_screen.dart   ← ringkasan + status (tabel §3.2)
      my_krs_screen.dart
      schedule_screen.dart
      grades_screen.dart
```

Endpoint sama dengan web (`/student/*`) — tidak ada endpoint khusus mobile. Nama fitur `academic_student`, bukan `short_semester`: SP hanya filter periode.
