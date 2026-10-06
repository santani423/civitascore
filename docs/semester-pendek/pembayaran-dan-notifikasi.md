# Semester Pendek — Pembayaran & Notifikasi

> Bagian dari [paket rancangan Semester Pendek](./README.md).

## 1. Kondisi Modul Keuangan Saat Ini

| Komponen | Kondisi |
|---|---|
| `invoices` | `student_id`, `period` (string bebas, mis. `2026/2027 Ganjil`), `amount`, `paid_amount`, `status` (`unpaid`/`partial`/`paid`), `due_date` |
| `payments` | `invoice_id`, `amount`, `paid_at`, `method` |
| Endpoint | **Hanya baca**: `GET /invoices`, `GET /invoices/{id}`, `GET /payments` |
| Payment gateway / VA / QRIS / webhook | **Tidak ada** |
| Role | `finance_administrator` hanya `invoices.read` |

Konsekuensi: SP memakai `invoices`/`payments` yang ada, menambah **jalur tulis minimum** (catat pembayaran manual, batalkan tagihan), dan **tidak** membangun payment gateway. Gateway adalah fitur umum modul Keuangan (blueprint §4.23) — rancangan kontraknya dicatat di §2.7 agar SP kompatibel saat gateway dibuat.

## 2. Alur Pembayaran SP

### 2.1 Perhitungan biaya

```
biaya item   = courses.credits × setting('academic.short_term.fee_per_credit')
biaya tagihan = Σ biaya item yang masuk tagihan itu

Contoh: Basis Data 3 SKS × Rp150.000 = Rp450.000
```

`fee_per_credit = 0` → tidak ada tagihan; item langsung `enrolled` setelah persetujuan (bila ada).

Biaya per kelas/praktikum berbeda **tidak** didukung di fase ini; bila dibutuhkan, tambahkan `class_sections.fee_override` (nullable) — perubahan kecil yang tidak memengaruhi alur.

### 2.2 Kapan tagihan dibuat

```
Submit KRS SP
   │
   ├── approval dibutuhkan? ── ya ──► tunggu keputusan per item
   │                                     └─ item disetujui ──┐
   └── tidak ────────────────────────────────────────────────┤
                                                             ▼
                                  Tagihan dibuat untuk item yang siap bayar:
                                  invoices { student_id, academic_term_id = SP,
                                             period = term.label(),
                                             amount, status = unpaid,
                                             due_date = today + payment_due_days }
                                  krs_items.invoice_id = invoice.id
```

- Tagihan dibuat **setelah** persetujuan supaya mahasiswa tidak pernah ditagih untuk MK yang ditolak.
- Satu tagihan per "gelombang" item siap bayar (satu submit tanpa approval = satu tagihan; dengan approval, item yang disetujui dalam satu keputusan/hari yang sama digabung ke tagihan `unpaid` SP yang sudah ada **bila belum ada pembayaran apa pun** pada tagihan itu, kalau tidak dibuat tagihan baru). Tagihan yang sudah menerima pembayaran tidak pernah diubah jumlahnya.

### 2.3 Status pembayaran

Pemetaan status konseptual ke `InvoiceStatus`:

| Konsep | `InvoiceStatus` | Keterangan |
|---|---|---|
| UNPAID | `unpaid` ✅ | |
| PENDING (menunggu verifikasi) | — | Tidak ada di fase manual: pembayaran **dicatat oleh Keuangan setelah diverifikasi**, sehingga tidak ada keadaan "terkirim tapi belum diverifikasi" di sistem. Muncul bersama gateway (di tabel transaksi, bukan di invoice — §2.7). |
| (sebagian) | `partial` ✅ | Untuk SP: item tetap `pending` sampai `paid` |
| PAID | `paid` ✅ | **Hanya** dicapai lewat `payments` yang menjumlah ≥ `amount` |
| FAILED | — | Tidak relevan untuk pencatatan manual; untuk gateway, kegagalan dicatat di transaksi dan invoice tetap `unpaid` |
| CANCELLED | `cancelled` 🆕 | Kedaluwarsa, semua item batal, kelas dibatalkan sebelum bayar |
| REFUNDED | — | Fase ini: proses manual di luar sistem (§2.6). Tidak ditambahkan ke enum sampai alur refund dibangun. |

Prinsip: **status `paid` tidak pernah di-set langsung.** Tidak ada endpoint `PATCH invoices/{id} {status: paid}`. Status adalah turunan dari `paid_amount` vs `amount` yang dihitung di dalam transaksi pencatatan pembayaran.

### 2.4 Kedaluwarsa

Item `pending` dengan invoice `unpaid`/`partial` yang `due_date < today`:

1. Invoice → `cancelled` (hanya bila `paid_amount = 0`; bila `partial`, ditandai untuk ditindaklanjuti Keuangan, item tidak dibatalkan otomatis).
2. Item → `dropped`.
3. Notifikasi `short_term.payment_expired` ke mahasiswa.
4. `activity_logs` `ENROLLMENT_EXPIRED`.

Dijalankan oleh dua mekanisme (pola *lazy* sudah dipakai `ExamService::finalizeIfExpired()`):

| Mekanisme | Kapan | Tujuan |
|---|---|---|
| Lazy | (a) di dalam `enroll()` untuk kelas yang sedang dikunci, sebelum menghitung kapasitas; (b) saat mahasiswa membuka daftar KRS-nya | Kebenaran kapasitas tidak bergantung pada cron |
| Terjadwal | Command baru `academic:expire-short-term-holds`, dijadwalkan harian di `routes/console.php` | Membersihkan sisa, mengirim notifikasi tepat waktu |

Catatan: proyek belum punya jadwal (`routes/console.php` hanya contoh `inspire`). Bila server produksi (shared hosting, lihat komentar `PermissionRegistry`) tidak menjalankan `schedule:run`, mekanisme lazy tetap menjaga kapasitas benar; hanya notifikasi kedaluwarsa yang tertunda.

Bahkan sebelum dibersihkan, item dengan invoice kedaluwarsa **tidak** menahan kursi (lihat rumus kapasitas di [desain-database.md §3.7](./desain-database.md#37-krs_items-)).

### 2.5 Pencatatan pembayaran (manual)

```
Keuangan: POST /invoices/{invoice}/payments {amount, paid_at, method, reference}
   │  DB::transaction
   │    invoice = Invoice::lockForUpdate()->findOrFail()
   │    tolak bila cancelled / paid / amount > sisa
   │    Payment::create(..., recorded_by = auth user)
   │    invoice.paid_amount += amount
   │    invoice.status = paid_amount >= amount ? paid : partial
   │  commit
   ▼
event InvoicePaid (hanya saat transisi ke paid, setelah commit)
   └── listener PromotePaidShortTermEnrollments
         untuk setiap krs_items.invoice_id = invoice, status pending:
           bila tidak ada approval_request yang masih berjalan → enrolled
         notifikasi short_term.payment_confirmed + short_term.enrollment_confirmed
```

**Pembayaran setelah kedaluwarsa** (mahasiswa transfer di hari terakhir, Keuangan mencatat besoknya): invoice sudah `cancelled` → 409. Keuangan harus memutuskan: daftarkan ulang lewat admin akademik (kursi mungkin sudah diambil orang lain) atau refund. Ini disengaja — sistem tidak boleh menimpa kursi yang sudah dilepas.

### 2.6 Refund

Dibutuhkan bila kelas dibatalkan atau admin membatalkan item yang sudah dibayar. Fase ini:
- Item → `dropped`; invoice tetap `paid` (uang memang sudah diterima).
- `activity_logs` `REFUND_REQUIRED` dengan `{invoice_id, krs_item_ids, amount}`; muncul di dashboard Keuangan sebagai daftar "perlu refund".
- Pengembalian dana diproses di luar sistem.

Alur refund penuh (status `refunded`, tabel `refunds`, persetujuan) adalah fitur umum Keuangan — di luar cakupan.

### 2.7 Payment gateway (masa depan)

Kontrak yang harus dipenuhi modul gateway agar SP kompatibel tanpa perubahan:

```
Create payment intent ─► payment_transactions { invoice_id, provider, external_id,
                                                 amount, status: pending, payload }
         │
         ▼
Gateway callback/webhook ─► verifikasi tanda tangan (HMAC/kunci provider) — tolak 401 bila gagal
         │                ─► idempoten: unique (provider, external_id); callback ulang
         │                   untuk transaksi yang sudah settled = 200 tanpa efek
         │                ─► verifikasi ulang status ke API provider (jangan percaya body saja)
         ▼
status settled ─► panggil jalur yang SAMA dengan §2.5 (buat Payment, update invoice, event InvoicePaid)
status failed/expired ─► payment_transactions.status = failed; invoice tidak berubah
```

Membuat transaksi **tidak pernah** mengubah invoice menjadi `paid`.

---

## 3. Notifikasi

### 3.1 Mekanisme yang dipakai ulang

`NotificationDispatcher::dispatch(User $user, string $eventKey, array $channels, array $placeholders)` → `TemplatedNotification` → template per tenant (`notification_templates`, dikelola `academic_administrator` yang sudah punya `notification_templates.*`) → kanal (`database`, `mail`, `push`, `whatsapp`, `sms`) sesuai `notification_channels` tenant dan `user_notification_preferences` pengguna. Dicatat di `notification_logs`.

Pengiriman dilakukan **setelah commit** (event + listener `ShouldQueue` / `afterCommit`), tidak di dalam transaksi pendaftaran.

**Kendala:** dosen hanya bisa dinotifikasi bila `lecturers.user_id` terisi (⛔ prasyarat).

### 3.2 Daftar event

Kanal default: **D** = `database` (in-app) · **M** = `mail` · **P** = `push` (bila kanal aktif di tenant & app mobile terpasang). Semua dapat diubah per tenant lewat template/kanal, dan per pengguna lewat preferensi — kecuali yang ditandai **wajib** (tidak bisa dimatikan pengguna).

| Event key | Penerima | Pemicu | Kanal default |
|---|---|---|---|
| `short_term.registration_opened` | Mahasiswa aktif di prodi yang punya kelas SP | Term → REGISTRATION_OPEN | D, M |
| `short_term.registration_closing` | Mahasiswa layak yang belum mendaftar | H-1 `registration_ends_at` (command terjadwal) | D |
| `short_term.krs_submitted` | Mahasiswa | Submit berhasil | D |
| `short_term.krs_approved` / `short_term.krs_rejected` | Mahasiswa | Keputusan approval | D, M, P |
| `short_term.invoice_issued` | Mahasiswa | Tagihan dibuat | D, M (**wajib**) |
| `short_term.payment_confirmed` | Mahasiswa | Invoice → paid | D, M (**wajib**) |
| `short_term.enrollment_confirmed` | Mahasiswa | Item → enrolled | D, P |
| `short_term.payment_expired` | Mahasiswa | Kedaluwarsa | D, M (**wajib**) |
| `short_term.schedule_changed` | Peserta + dosen kelas | Jadwal diubah | D, M, P |
| `short_term.class_cancelled` | Peserta + dosen | Kelas dibatalkan | D, M, P (**wajib**) |
| `short_term.exam_published` | Peserta | Ujian kelas SP dipublish (✅ titik kait `ExamService::publish`) | D, P |
| `short_term.grades_released` | Mahasiswa | Term → COMPLETED | D, M, P |
| `short_term.grade_revised` | Mahasiswa, pengaju | Revisi nilai diterapkan | D, M |
| `short_term.teaching_assigned` | Dosen | Ditugaskan ke kelas SP | D, M |
| `short_term.new_enrollment` | Dosen | Ringkasan harian peserta baru (bukan per item) | D |
| `short_term.attendance_reminder` | Dosen | Kelas berjalan tanpa absensi ≥ 3 hari (terjadwal) | D |
| `short_term.grading_deadline` | Dosen yang nilai kelasnya belum submit | Term → GRADING, dan H-2 akhir periode nilai | D, M |
| `short_term.grades_submitted` | Bagian Akademik | Dosen submit nilai kelas | D |
| `short_term.pending_approvals` | Approver | ✅ sudah ditangani `SendApprovalNotification` (ApprovalWorkflow) — tidak perlu event baru | — |
| `short_term.registration_summary` | Bagian Akademik | Harian selama REGISTRATION_OPEN | D |
| `short_term.unfinalized_grades` | Bagian Akademik | Term GRADING dengan kelas belum final (terjadwal) | D |
| `short_term.refund_required` | Keuangan | §2.6 | D, M |

"Real-time" di sistem ini = kanal `database` yang dibaca frontend; tidak ada WebSocket. Tidak ditambahkan untuk SP.

### 3.3 Template default

Seeder `ShortTermNotificationTemplateSeeder` menambahkan template default (bahasa Indonesia) per event key untuk setiap tenant, dengan placeholder: `{student_name}`, `{nim}`, `{term_label}`, `{course_code}`, `{course_name}`, `{class_code}`, `{amount}`, `{due_date}`, `{reason}`. Tenant dapat mengubahnya lewat `PUT /notification-templates/{id}` (✅ ada).
