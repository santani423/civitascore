# Semester Pendek — Rencana Implementasi

> Bagian dari [paket rancangan Semester Pendek](./README.md).
>
> **Catatan 2026-10-08:** sebagian prasyarat di bawah sudah dibangun dalam bentuk lain sejak dokumen ini ditulis. Urutan kerja yang berlaku, berikut penyesuaiannya dengan kode terbaru, ada di [tahapan-pengembangan.md](./tahapan-pengembangan.md).

## 1. Urutan Fase

Setiap fase dapat dirilis sendiri dan tidak merusak semester reguler. Fase 0 dan sebagian fase lain adalah **gap umum** yang memang tercatat di RANCANGAN-AKUN-AKADEMIK §10–11 — mengerjakannya memperbaiki sistem untuk semua periode, bukan hanya SP.

```
Fase 0  Prasyarat umum        ─┐
Fase 1  Periode & penawaran    ├─► Fase 3 KRS mandiri & persetujuan ─► Fase 4 Pembayaran ─► Fase 6 Dashboard, laporan, notifikasi
Fase 2  Penilaian & transkrip ─┘                                                         └─► Fase 7 Mobile (opsional)
Fase 5  Prasyarat MK (opsional, bisa kapan saja setelah Fase 1)
```

## 2. Detail Fase

### Fase 0 — Prasyarat umum

| Pekerjaan | Catatan |
|---|---|
| `lecturers.user_id` + `User::lecturer()` + seeder/backfill akun dosen | Pola `Student.user_id` / `StudentUserAccountSeeder` |
| `class_section_lecturers` + endpoint `PUT /class-sections/{id}/lecturers` | |
| Policy kepemilikan di `GradePolicy`, `AttendancePolicy`, `ExamPolicy`, `ClassSectionPolicy` + filter daftar | **Berdampak pada semester reguler.** Rilis bertahap: (1) rilis tabel & UI penugasan, (2) admin mengisi penugasan untuk term berjalan, (3) aktifkan penegakan lewat feature flag tenant (`university_feature_flags` ✅ ada), (4) jadikan default |
| `GET /lecturer/class-sections` | |
| Perbaiki `AcademicTerm::label()` → `match` | Wajib sebelum nilai enum `pendek` ada |

### Fase 1 — Periode & penawaran

| Pekerjaan |
|---|
| Enum `pendek`; kolom `status`, `registration_*`, `max_credits` + backfill |
| `AcademicTermStatus` + `AcademicTermService::transition()` |
| Endpoint `academic-terms` (CRUD + status + summary dasar) + permission `academic_terms.*` |
| Endpoint tulis `class-sections` (+ `min_participants`, `cancel`) + permission `classes.create/update` |
| `class_section_schedules` + deteksi bentrok dosen/ruang |
| `university_settings` kunci `academic.*` + helper `AcademicPolicySettings` |
| Frontend: Daftar/Form Periode, tab Kelas, panel Dosen & Jadwal, halaman Pengaturan Akademik |

### Fase 2 — Penilaian & transkrip

| Pekerjaan |
|---|
| `maxSks()` mengecualikan SP + cabang SP | 
| `grades.status/finalized_*` + backfill `submitted` + endpoint submit/return/finalize |
| `Auditable` pada `Grade` + `GET /grades/{grade}/history` |
| `grade_revision_requests` + listener approval + fallback `grades.approve` |
| `RepeatGradePolicy` + `transcript()` (IPK efektif, `items[]`) |
| **Keputusan akademik tertulis** sebelum rilis: perubahan IPK untuk mahasiswa yang pernah mengulang di semester reguler, dan kebijakan default tenant |

### Fase 3 — KRS mandiri & persetujuan

| Pekerjaan |
|---|
| `krs_items.status` += `pending`, `rejected` |
| `EnrollmentEligibility` (pipeline lengkap kecuali prasyarat) + mode evaluasi |
| Kunci mahasiswa → kelas terurut di `enroll()` |
| `ConflictException` dengan `code` |
| `KrsParticipationPolicy` + permission `krs_participation.*` + endpoint `student/*` (termasuk schedule/attendances/transcript yang umum) |
| Integrasi `ApprovalWorkflow` (`krs_item.short_term`) + listener |
| Override admin + permission `krs.approve` |
| Portal: halaman Semester Pendek (Ringkasan, Pilih MK, KRS Saya) + filter periode di Jadwal/Absensi/Nilai/Transkrip |

### Fase 4 — Pembayaran

| Pekerjaan |
|---|
| `invoices.academic_term_id`, status `cancelled`; `krs_items.invoice_id`; `payments.reference/recorded_by` |
| Pembuatan invoice setelah persetujuan; penyesuaian saat batal |
| `POST /invoices/{id}/payments`, `PATCH /invoices/{id}/cancel` + permission `invoices.update` |
| Event `InvoicePaid` → promosi item |
| Lazy expiry + command `academic:expire-short-term-holds` + jadwal |
| Portal Tagihan SP; tab Pembayaran admin |

### Fase 5 — Prasyarat MK (opsional)

`course_prerequisites` + endpoint + pemeriksaan di pipeline + override.

### Fase 6 — Dashboard, laporan, notifikasi

Summary lengkap, kartu "Perlu tindakan", 5 laporan + ekspor, seluruh event notifikasi + template default, command pengingat.

### Fase 7 — Mobile (opsional)

Lihat [frontend-dan-mobile.md §5](./frontend-dan-mobile.md#5-mobile-flutter). Bukan bagian dari definisi "SP selesai".

## 3. Checklist Kesiapan

```
Prasyarat
[ ] Identity link lecturers.user_id
[ ] class_section_lecturers + kepemilikan dosen ditegakkan
[ ] AcademicTerm::label() mendukung pendek

Periode & penawaran
[ ] Dukungan Academic Period (enum pendek, status, jendela, max_credits)
[ ] Konfigurasi SP per tenant (university_settings academic.*)
[ ] Penawaran mata kuliah (class_sections tulis, min_participants, cancel)
[ ] Manajemen kelas
[ ] Penugasan dosen (+ bentrok dosen)
[ ] Jadwal (+ bentrok ruang)

Pendaftaran
[ ] Kelayakan mahasiswa (pipeline + mode evaluasi)
[ ] KRS mandiri (student/krs-items)
[ ] Alur persetujuan (ApprovalWorkflow krs_item.short_term)
[ ] Pembayaran (invoice, catat pembayaran, kedaluwarsa)
[ ] Enrollment (promosi pending → enrolled)
[ ] Konkurensi kapasitas & SKS (kunci mahasiswa → kelas)

Perkuliahan
[ ] Absensi (reuse + persentase + minimum)
[ ] Integrasi LMS — N/A: LMS belum ada; aturan keterikatan class_section_id dicatat
[ ] Integrasi ujian (reuse; opsional syarat kehadiran; term archived)

Nilai
[ ] Penilaian (status draft/submitted/finalized)
[ ] Finalisasi nilai per kelas
[ ] Revisi nilai via approval + audit
[ ] Transkrip (items[], penanda ulang, tampilan)
[ ] IPS/IPK (kebijakan ulang; maxSks mengabaikan SP)

Lintas fungsi
[ ] Notifikasi (event key + template default)
[ ] Laporan (5 tipe, CSV/PDF)
[ ] Audit log (Auditable + event domain)
[ ] Keamanan (permission, policy, tenant, mass assignment)
[ ] API (sesuai desain-api.md)
[ ] Frontend admin
[ ] Frontend portal mahasiswa
[ ] Flutter (opsional)

Pengujian
[ ] Unit
[ ] Feature/API
[ ] Otorisasi
[ ] Integrasi end-to-end
[ ] Konkurensi (MySQL)
[ ] Regresi (suite lama hijau; perubahan sengaja terdokumentasi)
[ ] phpunit.xml mendaftarkan app/Modules/Academic/Tests/Unit
```

## 4. Yang Sengaja Tidak Dibangun

Membedakan **reuse** dari **benar-benar baru** juga berarti menolak hal-hal yang terdengar wajar tetapi tidak dibutuhkan:

| Tidak dibangun | Alasan |
|---|---|
| Tabel `short_semesters`, `short_krs`, `short_grades`, `short_exams`, `short_semester_*` | SP = term; seluruh rantai sudah periode-agnostik (README §2) |
| Tabel `academic_years` | Tahun akademik tidak punya atribut sendiri; string sudah dipakai konsisten |
| Tabel `course_offerings` | `class_sections` sudah = penawaran (MK × periode × kelas) |
| Tabel header `krs_submissions` | Approval per item cukup untuk 1–3 MK; invoice dihubungkan via `krs_items.invoice_id` |
| Kolom `attempt_number` / `replaced_by` tersimpan | Diturunkan saat dibaca → tidak bisa tidak sinkron |
| Tabel `grade_histories` | `audit_logs` via `Auditable` sudah menyimpan old/new + reason |
| Nilai enum baru di `AuditAction` | Event domain → `activity_logs` |
| Status `krs_items` `pending_approval` / `pending_payment` terpisah | Diturunkan dari approval request & invoice — satu sumber kebenaran |
| Kolom `requires_advisor_approval` di term | Ada/tidaknya workflow aktif adalah konfigurasinya |
| LMS khusus SP | LMS belum ada sama sekali; dibangun umum nanti |
| Komponen nilai berbobot khusus SP | Fitur umum blueprint §4.16 |
| Skala huruf per tenant | Gap umum (AKADEMIK §6.2) |
| Payment gateway, VA, QRIS, refund otomatis | Fitur umum modul Keuangan; kontrak kompatibel dicatat |
| Tabel ruangan | Modul Sarana & Prasarana belum ada; string ruang cukup untuk deteksi bentrok |
| Daftar tunggu (waitlist) kelas penuh | Tidak diminta; bisa ditambah kemudian di atas status `pending` |
| Lebih dari satu SP per tahun akademik | Jarang; butuh perubahan unique index + kolom urutan |
| Dashboard/administrasi SP di mobile | Web-only (frontend-dan-mobile.md §5.2) |
| WebSocket untuk notifikasi real-time | Kanal `database` sudah dipakai sebagai in-app |
| Override per universitas di kode | Semua variasi lewat `university_settings` & `approval_workflows` |
| Penegakan status/jendela pendaftaran untuk term reguler (fase ini) | Perubahan perilaku yang butuh keputusan sendiri; desain sudah siap |

## 5. Risiko

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Penegakan kepemilikan dosen memutus akses dosen di kelas reguler yang belum dipetakan | Dosen tidak bisa input nilai/absensi | Feature flag per tenant + periode pengisian penugasan (Fase 0) |
| IPK berubah untuk mahasiswa yang pernah mengulang | Keluhan / dokumen lama tidak cocok | Keputusan akademik tertulis, pengumuman, laporan "mahasiswa yang IPK-nya berubah" sebelum rilis |
| Server produksi tidak menjalankan scheduler | Notifikasi kedaluwarsa/pengingat tertunda | Lazy expiry menjaga kebenaran kapasitas; dokumentasikan kebutuhan cron |
| Portal mahasiswa masih statis di halaman lain | Mahasiswa bingung data SP asli vs halaman lain palsu | Sambungkan Jadwal/Nilai/Absensi/Transkrip di Fase 3 (endpoint umum), bukan hanya halaman SP |
| Uji konkurensi hanya di SQLite | Race condition lolos ke produksi | Grup tes MySQL di CI + uji beban staging |
| Perubahan enum MySQL di tabel besar (`krs_items`) | Lock tabel saat migrasi | Jalankan di luar jam sibuk; ukuran saat ini kecil |
