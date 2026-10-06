# Semester Pendek — Peran & Izin

> Bagian dari [paket rancangan Semester Pendek](./README.md). Memakai arsitektur otorisasi yang **sudah ada**: permission slug `{resource}.{action}` (enum `PermissionAction`: `create, read, update, delete, approve, reject, export, import, publish, finalize`), middleware rute `permission:`, Policy per model, `PermissionRegistry` (cache per user per tenant, bypass `super_admin`), dan pola policy kepemilikan seperti `ExamParticipationPolicy`.

## 1. Prinsip

1. **Bagian Akademik mengonfigurasi; Dosen mengajar; Mahasiswa mendaftar.** Dosen **tidak** membuat periode SP, kelas, atau jadwal.
2. **Permission slug = gerbang kasar; Policy = kepemilikan objek.** Setiap aksi dosen/mahasiswa di SP wajib dicek di level objek (kelas yang diampu / KRS milik sendiri). Permission saja tidak cukup — itulah celah yang sudah ada hari ini (RANCANGAN-AKUN-DOSEN §8.1).
3. **Tidak ada permission khusus SP** kecuali benar-benar tidak bisa dinyatakan dengan resource yang ada. SP adalah term; izin atas term berlaku untuk semua jenis term.

## 2. Permission Baru / Diperluas

Ditambahkan ke `RolePermissionSeeder::PERMISSION_MAP`:

| Resource | Aksi saat ini | Aksi baru | Dipakai untuk |
|---|---|---|---|
| `academic_terms` 🆕 | — | `read`, `create`, `update`, `delete` | CRUD periode + transisi status (`update`) |
| `classes` 🔧 | `read` | `create`, `update` | Buka kelas, ubah kapasitas, jadwal, dosen, pembatalan |
| `courses` | `read` | — | (tidak berubah) |
| `course_prerequisites` 🆕 | — | `read`, `update` | Kelola prasyarat (opsional, fase prasyarat) |
| `krs` 🔧 | `read`, `create`, `update` | `approve` | Override aturan saat admin mendaftarkan (§E15 aturan-bisnis) |
| `krs_participation` 🆕 | — | `read`, `create`, `update` | KRS mandiri mahasiswa — pola sama dengan `exam_participation` |
| `grades` 🔧 | `read`, `create`, `update` | `finalize`, `approve` | Finalisasi nilai per kelas; memutuskan revisi nilai (bila tanpa workflow) |
| `invoices` 🔧 | `read` | `update` | Mencatat pembayaran, membatalkan tagihan |

Persetujuan KRS dan revisi nilai yang melalui `ApprovalWorkflow` **tidak** butuh permission baru: endpoint `approval-request-steps/{id}/approve|reject` yang ada sudah mengotorisasi berdasarkan approver yang ditugaskan (`ApprovalRequestStepPolicy`).

## 3. Pemberian ke Role (`OrganizationalRoleSeeder::ROLE_PERMISSIONS`)

| Role | Tambahan |
|---|---|
| `academic_administrator` | `academic_terms.read/create/update/delete`, `classes.create/update`, `course_prerequisites.read/update`, `krs.approve`, `grades.finalize`, `grades.approve` |
| `head_of_study_program` | `academic_terms.read` (sudah punya `krs.read`, `grades.read`) |
| `dean`, `rector`, `vice_rector`, `university_owner`, `university_administrator`, `auditor` | `academic_terms.read` |
| `lecturer` | `academic_terms.read` (sudah punya `grades.create/update`, `attendance.*`, `exams.*`) |
| `student` | `krs_participation.read/create/update` (sudah punya `exam_participation.*`) |
| `finance_administrator` | `invoices.update` (sudah punya `invoices.read`) |
| `academic_advisor` | **Tetap kosong** sampai relasi dosen-wali ada |

## 4. Matriks Role × Aksi

Legenda: ✅ boleh · 🔒 boleh hanya untuk objek miliknya · ⚙️ hanya bila ditugaskan sebagai approver di workflow · ❌ tidak boleh · 📝 wajib tercatat di audit log · ✍️ wajib `reason`

### 4.1 Periode & penawaran

| Aksi | Super Admin | Bagian Akademik | Kaprodi | Dosen PA | Dosen | Mahasiswa | Keuangan |
|---|---|---|---|---|---|---|---|
| Lihat daftar term SP | ✅ | ✅ | ✅ | ✅* | ✅ | 🔒 (status ≥ PLANNED) | ❌ |
| Buat term SP 📝 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Ubah term SP 📝 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Ubah status term 📝 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Paksa COMPLETED tanpa semua nilai final 📝✍️ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Hapus term (DRAFT, tanpa kelas) 📝 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Buka/ubah kelas SP 📝 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Batalkan kelas 📝✍️ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Tetapkan dosen pengampu 📝 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Atur jadwal/ruang 📝 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Paksa jadwal bentrok dosen 📝✍️ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Kelola prasyarat MK 📝 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

\* Dosen PA: setelah identity link & relasi dosen-wali ada.

### 4.2 KRS

| Aksi | Super Admin | Bagian Akademik | Kaprodi | Dosen PA | Dosen | Mahasiswa | Keuangan |
|---|---|---|---|---|---|---|---|
| Lihat penawaran + kelayakan | ✅ | ✅ | ✅ | — | ✅ | 🔒 | ❌ |
| Daftar KRS sendiri 📝 | — | — | — | — | — | 🔒 | — |
| Batalkan KRS sendiri 📝 | — | — | — | — | — | 🔒 (§7.4) | — |
| Daftarkan mahasiswa (atas nama) 📝 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Override prasyarat/SKS/bentrok/kapasitas 📝✍️ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Setujui/tolak KRS 📝 | ✅ | ⚙️ | ⚙️ | ⚙️* | ❌ | ❌ | ❌ |
| Batalkan KRS mahasiswa (admin drop) 📝✍️ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Lihat seluruh KRS SP | ✅ | ✅ | ✅ | 🔒* | 🔒 (peserta kelasnya) | 🔒 | ❌ |

### 4.3 Pembelajaran & penilaian

| Aksi | Super Admin | Bagian Akademik | Kaprodi | Dosen PA | Dosen | Mahasiswa | Keuangan |
|---|---|---|---|---|---|---|---|
| Lihat peserta kelas | ✅ | ✅ | ✅ | ❌ | 🔒 | ❌ | ❌ |
| Rekam absensi | ✅ | ❌ | ❌ | ❌ | 🔒 | ❌ | ❌ |
| Lihat absensi | ✅ | ✅ | ✅ | 🔒* | 🔒 | 🔒 | ❌ |
| Kelola ujian | ✅ | ❌ | ❌ | ❌ | 🔒 | ❌ | ❌ |
| Mengerjakan ujian | — | — | — | — | — | 🔒 (✅ sudah ada) | — |
| Input/ubah nilai draft 📝 | ✅ | ❌ | ❌ | ❌ | 🔒 | ❌ | ❌ |
| Submit nilai kelas 📝 | ✅ | ❌ | ❌ | ❌ | 🔒 | ❌ | ❌ |
| Kembalikan nilai ke dosen (un-submit) 📝✍️ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Finalisasi nilai 📝 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Ajukan revisi nilai final 📝✍️ | ✅ | ✅ | ❌ | ❌ | 🔒 | ❌ | ❌ |
| Setujui revisi nilai 📝 | ✅ | ⚙️ | ⚙️ | ❌ | ❌ | ❌ | ❌ |
| Lihat nilai sendiri (setelah COMPLETED / nilai final) | — | — | — | — | — | 🔒 | — |
| Lihat transkrip | ✅ | ✅ | ✅ | 🔒* | ❌ | 🔒 | ❌ |

### 4.4 Keuangan

| Aksi | Super Admin | Bagian Akademik | Kaprodi | Dosen PA | Dosen | Mahasiswa | Keuangan |
|---|---|---|---|---|---|---|---|
| Lihat tagihan SP | ✅ | ✅ (status saja, via KRS) | ❌ | ❌ | ❌ | 🔒 | ✅ |
| Catat pembayaran 📝 | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Batalkan tagihan 📝✍️ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Ubah status pembayaran langsung | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ — status invoice **hanya** berubah sebagai akibat pencatatan pembayaran/pembatalan |

### 4.5 Yang tidak boleh diakses sama sekali

| Role | Tidak boleh |
|---|---|
| Dosen | Kelas yang tidak diampu (404 — tidak membocorkan keberadaan), konfigurasi term/kelas/jadwal, tagihan, finalisasi nilai, data KRS mahasiswa lain di luar kelasnya |
| Mahasiswa | KRS/nilai/absensi/tagihan mahasiswa lain, term berstatus DRAFT, nilai yang belum final, endpoint admin `krs-items` (tidak punya `krs.create`) |
| Keuangan | Nilai, absensi, konfigurasi akademik |
| Kaprodi | Menulis apa pun di luar langkah approval yang ditugaskan kepadanya |

## 5. Policy Kepemilikan Objek

### 5.1 Dosen → kelas (⛔ prasyarat umum)

```php
// Konsep — ClassSectionPolicy / GradePolicy / AttendancePolicy / ExamPolicy
private function teaches(User $user, ClassSection $classSection): bool
{
    $lecturer = $user->lecturer;            // 🆕 HasOne via lecturers.user_id
    return $lecturer !== null
        && $classSection->lecturers()->whereKey($lecturer->id)->exists();
}

public function manage(User $user, KrsItem $krsItem): bool    // GradePolicy
{
    if (! ($user->hasPermissionTo('grades.create') || $user->hasPermissionTo('grades.update'))) {
        return false;
    }
    // Admin akademik/super admin tidak terikat kepemilikan
    if ($user->hasPermissionTo('grades.finalize')) {
        return true;
    }
    return $this->teaches($user, $krsItem->classSection);
}
```

Daftar untuk dosen (`GET /grades`, `/attendances`, `/class-sections`, `/exams`) wajib difilter ke kelas yang diampu bila pengguna tidak punya permission admin — bukan hanya disembunyikan di UI.

**Catatan transisi:** menegakkan ini memengaruhi semester reguler juga (memperbaiki celah DOSEN §8.1). Untuk tenant yang belum mengisi `class_section_lecturers`, dosen akan kehilangan akses ke kelas reguler. Rencana migrasi data ada di [rencana-implementasi.md §2 Fase 0](./rencana-implementasi.md#fase-0--prasyarat-umum).

### 5.2 Mahasiswa → KRS sendiri

Policy baru `KrsParticipationPolicy` (pola identik `ExamParticipationPolicy`, dipanggil via `app()` dari controller karena lintas model):

```php
public function viewOwn(User $user): bool
{
    return $user->hasPermissionTo('krs_participation.read') && $user->student !== null;
}

public function enrollOwn(User $user): bool
{
    return $user->hasPermissionTo('krs_participation.create') && $user->student !== null;
}

public function cancelOwn(User $user, KrsItem $krsItem): bool
{
    return $user->hasPermissionTo('krs_participation.update')
        && $user->student !== null
        && $user->student->id === $krsItem->student_id;
}
```

`student_id` **tidak pernah** diterima dari body request mahasiswa.

### 5.3 Visibilitas term untuk mahasiswa

Scope query `AcademicTerm::visibleToStudents()` = `status NOT IN (draft)`. Term DRAFT diperlakukan tidak ada (404).

## 6. Aksi yang Wajib Melalui Persetujuan

| Aksi | Mekanisme | Default |
|---|---|---|
| KRS SP mahasiswa | `ApprovalWorkflow` `krs_item.short_term` | Otomatis (tanpa workflow) |
| Revisi nilai final | `ApprovalWorkflow` `grade_revision` | **Wajib**: bila tidak ada workflow aktif, fallback ke persetujuan langsung oleh pemegang `grades.approve` (tidak pernah langsung diterapkan) |
| Override aturan KRS oleh admin | Tidak melalui approval — dibatasi permission `krs.approve` + `reason` + audit | — |
| Paksa COMPLETED | Tidak melalui approval — `reason` + audit | — |

## 7. Aksi yang Wajib Diaudit

Seluruh aksi bertanda 📝 di §4. Mekanisme & daftar event: [keamanan-audit-integritas.md §4](./keamanan-audit-integritas.md#4-audit-log).
