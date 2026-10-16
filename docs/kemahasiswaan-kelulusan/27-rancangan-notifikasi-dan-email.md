# 27 — Rancangan Notifikasi dan Email

> **Status:** rancangan · Disusun 2026-10-13 (commit `47043ca`) · Kembali ke [README](README.md)
> Aturan: [UMUM-BR-011](29-business-rules-dan-validasi.md#umum-br-011)

## 1. Kondisi Modul Notification (terverifikasi)

| Komponen | Kondisi | Dampak |
|---|---|---|
| `NotificationDispatcher::dispatch(User $user, string $eventKey, array $channels, array $placeholders, array $data)` | Ada | Penerima harus `User`. Responden tracer, kontak pengguna lulusan, dan undangan perusahaan **tidak** punya akun |
| `TemplatedNotification` (`ShouldQueue`) | Ada. Mengimplementasikan `toDatabase` dan `toMail` saja | Kanal efektif: `database`, `mail`. Enum `NotificationChannel` memuat `push`, `whatsapp`, `sms` tetapi tanpa implementasi ([KK-A-04](33-glossary-dan-keputusan-bisnis.md#4-asumsi-kk-a)) |
| `notification_templates` | Ada. Unik (`event_key`, `channel`), + `university_id` (override tenant) | **Tidak ada kolom bahasa**, padahal SKPI/survei/portal karier memerlukan ID/EN |
| `notification_logs` | Ada (`event_key`, `status`, `sent_at`, `error_message`) | **Tidak ada kunci dedupe**, sehingga retry dapat mengirim ganda |
| `user_notification_preferences` | Ada (`channel`, `notification_type` dengan sentinel `*`) | Dipakai apa adanya |
| `ApprovalStepAssigned`, `ApprovalRequestDecided` | Ada | Dipakai untuk notifikasi persetujuan |

### 1.1 Perluasan yang diusulkan

| Perluasan | Rancangan |
|---|---|
| Penerima non-user | `NotificationDispatcher::dispatchToAddress(string $email, string $locale, string $eventKey, array $placeholders, string $dedupeKey, ?string $universityId)` memakai `Notification::route('mail', $email)` (notifiable on-demand Laravel). Log memakai `notifiable_type = 'anonymous_mail'` + `email_hash` |
| Bahasa | `notification_templates.locale` (default `id`). Pemilihan: preferensi pengguna → `universities.locale` (Ada) → `id`. Fallback ke `id` bila template bahasa lain tidak ada |
| Idempotensi | `notification_logs.dedupe_key` unik parsial. Dispatcher menolak (no-op) bila `dedupe_key` sudah `sent`/`queued` |
| Ringkasan (digest) | Job `notifications:send-digests` untuk event bertipe ringkasan (harian/mingguan) |
| Email massal tracer | Tidak melalui `TemplatedNotification` per pengguna, tetapi memakai `SendTracerInvitationJob` dengan template dari tabel yang sama dan throttle khusus ([18](18-rancangan-distribusi-dan-reminder-tracer.md)) |

## 2. Prinsip

1. **Pemicu = transisi status yang sudah di-commit.** Notifikasi dikirim dari listener `afterCommit`, tidak pernah sebelum transaksi selesai.
2. **Satu kunci dedupe per kejadian bisnis**: `{event_key}:{subject_id}:{versi/urutan}`, mis. `career.application.status_changed:01JAPP:01JHIST`. Retry job dan event ganda menghasilkan satu notifikasi.
3. **Data minimal di isi pesan.** Email tidak memuat nilai, IPK, nominal, isi kasus disiplin, atau token selain tautan yang memang diperlukan. Detail dibaca setelah login.
4. **Preferensi dihormati** kecuali notifikasi **wajib** (transaksional/hukum: keputusan kelulusan, pemanggilan disiplin, keputusan sanksi, dokumen dibatalkan, undangan survei pertama, consent rekomendasi). Notifikasi wajib tidak dapat dimatikan di kanal `database`, sedangkan `mail` dapat dimatikan kecuali bertanda `mandatory_mail`.
5. **Kanal default**: `database` + `mail`. Kanal lain menyusul saat diimplementasikan.

## 3. Katalog Notifikasi

Kolom: Event key · Pemicu · Penerima · Kanal · Waktu · Wajib · Dedupe key.

### 3.1 Kemahasiswaan

| Event key | Pemicu | Penerima | Kanal | Waktu | Wajib | Dedupe |
|---|---|---|---|---|---|---|
| `student_affairs.activity.submitted` | Pengajuan `submitted` | Mahasiswa | db | Segera | — | `…:{submission}:{revision_count}` |
| `student_affairs.activity.revision_requested` | `revision_requested` | Mahasiswa | db, mail | Segera | — | `…:{verification_id}` |
| `student_affairs.activity.approved` | `approved` (memuat poin) | Mahasiswa | db, mail | Segera | — | `…:{submission}` |
| `student_affairs.activity.rejected` | `rejected` | Mahasiswa | db, mail | Segera | — | `…:{submission}:{verification_id}` |
| `student_affairs.points.corrected` / `revoked` | Entri ledger koreksi/cabut | Mahasiswa (+ dosen wali untuk cabut) | db, mail | Segera | ✓ (cabut) | `…:{ledger_id}` |
| `student_affairs.point_appeal.decided` | Banding diputus | Mahasiswa | db, mail | Segera | — | `…:{appeal}` |
| `student_affairs.activity.sla_overdue` | Job harian | Manager | db (digest) | 07.00 tenant | — | `…:{date}:{user}` |
| `student_affairs.violation.hearing_scheduled` | Jadwal pemeriksaan dibuat/diubah | Terlapor | db, mail | Segera | ✓ `mandatory_mail` | `…:{hearing}:{version}` |
| `student_affairs.violation.decision_final` | Keputusan `final` | Terlapor, dosen wali (opsional, ringkasan) | db, mail | Segera | ✓ | `…:{decision}` |
| `student_affairs.violation.appeal_decided` | Putusan keberatan | Mahasiswa | db, mail | Segera | ✓ | `…:{appeal}` |
| `scholarship.application.result` | Pendaftaran `approved`/`rejected`/`waitlisted` | Mahasiswa | db, mail | Setelah batch disetujui | — | `…:{application}:{status}` |
| `scholarship.award.offered` / `activated` / `status_changed` | Award berubah | Mahasiswa | db, mail | Segera | ✓ (terminated) | `…:{award}:{status}:{term}` |
| `documents.issued` | Surat aktif/dokumen terbit | Pemilik | db, mail | Segera | — | `…:{document}` |
| `documents.revoked` | Dokumen dibatalkan | Pemilik | db, mail | Segera | ✓ | `…:{document}` |

### 3.2 Yudisium & wisuda

| Event key | Pemicu | Penerima | Kanal | Waktu | Wajib | Dedupe |
|---|---|---|---|---|---|---|
| `graduation.period.opened` | Periode `open` | Calon peserta unit | db, mail | Segera (batch, throttle) | — | `…:{period}:{student}` |
| `graduation.period.schedule_changed` | Perubahan jadwal | Pendaftar periode | db, mail | Segera | ✓ | `…:{change_id}:{student}` |
| `graduation.eligibility.incomplete` | Hasil pertama `not_eligible`, lalu ringkasan mingguan | Mahasiswa | db, mail | Pertama: segera; ringkasan: Senin 07.00 | — | `…:{period}:{student}:{iso_week}` |
| `graduation.eligibility.now_eligible` | Hasil berubah ke eligible | Mahasiswa | db, mail | Segera | — | `…:{period}:{student}` (sekali) |
| `graduation.registration.returned` / `rejected` | Verifikasi | Mahasiswa | db, mail | Segera | ✓ (rejected) | `…:{verification_id}` |
| `graduation.meeting.scheduled` | Rapat dijadwalkan | Peserta rapat | db, mail | Segera | — | `…:{meeting}:{student}` |
| `graduation.decision.final` | Finalisasi | Mahasiswa | db, mail | Segera | ✓ `mandatory_mail` | `…:{decision}` |
| `graduation.decision.corrected` / `annulled` | Koreksi/pembatalan | Mahasiswa | db, mail | Segera | ✓ `mandatory_mail` | `…:{correction}` |
| `graduation.documents.available` | Transkrip akhir/ijazah/SKL terbit | Lulusan | db, mail | Segera (digest bila > 1 dokumen dalam 10 menit) | — | `…:{student}:{batch}` |
| `graduation.ceremony.registration_opened` | Wisuda dibuka | Lulusan eligible | db, mail | Segera (batch) | — | `…:{ceremony}:{student}` |
| `graduation.ceremony.payment_pending` | `payment_pending` | Lulusan | db, mail | Segera + H-3 batas | — | `…:{registration}:{seq}` |
| `graduation.ceremony.registration_verified` | Validasi | Lulusan | db, mail | Segera | — | `…:{registration}` |
| `graduation.ceremony.confirmation_reminder` | Belum konfirmasi | Lulusan | db, mail | H-7, H-2 batas | — | `…:{registration}:{offset}` |
| `graduation.ceremony.session_assigned` | Sesi final | Lulusan | db, mail (+ tautan kartu) | Saat `finalized` | ✓ | `…:{registration}:{session}` |
| `graduation.ceremony.schedule_changed` | Perubahan jadwal/sesi | Peserta terdampak | db, mail | Segera | ✓ `mandatory_mail` | `…:{change_id}:{registration}` |
| `graduation.ceremony.book_review_open` | Masa koreksi buku | Lulusan, admin prodi | db, mail | Segera | — | `…:{edition}:{recipient}` |
| `skpi.document.ready_for_review` | Draf siap pernyataan / diajukan | Lulusan / petugas | db, mail | Segera | — | `…:{document}:{status}` |
| `skpi.document.issued` / `revoked` | SKPI terbit/dicabut | Lulusan | db, mail | Segera | ✓ (revoked) | `…:{document}` |

### 3.3 Tracer

| Event key | Pemicu | Penerima | Kanal | Waktu | Wajib | Dedupe |
|---|---|---|---|---|---|---|
| `tracer.invitation` | Kampanye `initial` | Responden (alamat) | mail (+ db bila alumni punya akun) | Sesuai jadwal kampanye, throttle | ✓ | `tracer_invitations.dedupe_key` |
| `tracer.reminder` | Jadwal pengingat | Responden belum mengisi | mail | Jam terjadwal | — (responden dapat berhenti berlangganan) | idem |
| `tracer.response.submitted` | Kirim survei | Responden | mail (opsional) | Segera | — | `…:{response}` |
| `tracer.mail.failed` / `bounce_rate_high` | Bounce/gagal > ambang | Petugas tracer | db, mail (digest) | Harian / segera bila > 10% | — | `…:{campaign}:{date}` |
| `tracer.quota.threshold_reached` / `exhausted` | Pemakaian 80/95/100% | Admin universitas, petugas | db, mail | Segera | ✓ | `…:{window}:{threshold}` |

Setiap email tracer memuat tautan **berhenti berlangganan** khusus survei (one-click, menambah `tracer_suppressions` `opt_out`) dan identitas pengirim (kampus).

### 3.4 Career Center

| Event key | Pemicu | Penerima | Kanal | Waktu | Wajib | Dedupe |
|---|---|---|---|---|---|---|
| `career.job.new_matching` | Lowongan baru cocok preferensi | Mahasiswa/alumni yang mengaktifkan | db, mail (digest) | Harian 08.00 | — | `…:{user}:{date}` |
| `career.application.submitted` | Lamaran terkirim | Pelamar; perekrut (digest) | db, mail | Segera / harian | — | `…:{application}` |
| `career.application.status_changed` | Transisi status | Pelamar | db, mail | Segera | — | `…:{history_id}` |
| `career.cv.downloaded` | Perusahaan mengunduh CV | Pemilik | db | Segera (digest bila > 3/jam) | — | `…:{access_log_id}` |
| `career.company.invited` | Undangan perusahaan | Alamat PIC | mail | Segera | ✓ | `…:{invitation}:{seq}` |
| `career.company.verification_decided` | Verifikasi | Pemilik perusahaan | mail, db | Segera | ✓ | `…:{verification}` |
| `career.posting.review_decided` / `expiring` / `taken_down` | Moderasi/kedaluwarsa | Perekrut | mail, db | Segera / H-3 | — | `…:{posting}:{event}` |
| `career.recommendation.approval_requested` | Usulan diajukan | Penyetuju | via `ApprovalStepAssigned` (Ada) | Segera | — | Ada |
| `career.recommendation.consent_requested` | Persetujuan kampus | Alumni | db, mail | Segera + 1 pengingat H+3 | ✓ | `…:{candidate}:{seq}` |
| `career.recommendation.shared` / `consent_revoked` | Berbagi/cabut | Perusahaan | mail, db | Segera | — | `…:{candidate}:{event}` |

## 4. Template

- Template per `event_key` × `channel` × `locale`, dengan override tenant (`university_id`, Ada). Placeholder dibatasi daftar yang dideklarasikan per event (`{{student_name}}`, `{{period_name}}`, `{{link}}`, …). `TemplateRenderer` (Ada) menolak placeholder tak dikenal.
- Template email memakai branding tenant (logo, warna) dari `universities` dan, untuk email portal karier pada paket whitelabel, `career_portal_settings`.
- Contoh `graduation.decision.final` (`mail`, `id`):

```text
Subjek: Keputusan Yudisium {{period_name}}

Yth. {{student_name}},

Keputusan yudisium Anda pada {{period_name}} telah ditetapkan.
Silakan masuk ke portal untuk melihat rincian keputusan dan dokumen yang tersedia:
{{link}}

Pesan ini dikirim otomatis oleh {{university_name}}.
```

Isi tidak menyebut hasil (lulus/ditunda) di email untuk mengurangi risiko salah kirim. Hasil dibaca setelah login [K: kampus dapat mengubah template].

## 5. Retry, Kegagalan, dan Audit

| Aspek | Ketentuan |
|---|---|
| Retry | Job notifikasi: `tries = 3`, backoff 60/300/900 detik. Kegagalan permanen → `notification_logs.status = failed` + `error_message` (Ada) |
| Idempotensi | `dedupe_key` diperiksa sebelum mengantre dan sebelum mengirim (job memeriksa ulang) |
| Preferensi | `user_notification_preferences` (Ada) dievaluasi saat kirim |
| Audit pengiriman | `notification_logs` (Ada) untuk setiap kanal. Event tracer juga di `tracer_email_events` |
| Pemantauan | Laporan harian: gagal per event, bounce rate, antrean tertunda > 15 menit |

## 6. Acceptance Criteria dan Test Case

| ID | Kriteria / skenario | Prioritas |
|---|---|---|
| UMUM-AC-05 | Event yang sama dipancarkan 3× → 1 notifikasi per kanal (diuji dengan `Notification::fake` + dedupe nyata di DB) | P1 |
| UMUM-AC-06 | Email undangan tracer ke alamat tanpa akun terkirim dan tercatat dengan `email_hash` | P1 |
| UMUM-AC-07 | Template `en` dipakai untuk pengguna berpreferensi `en`. Bila tidak ada, `id` dipakai | P2 |
| UMUM-TC-010 | Retry job notifikasi tidak mengirim ganda | P1 |
| UMUM-TC-011 | Notifikasi wajib tidak dapat dimatikan di kanal `database` | P2 |
| UMUM-TC-012 | Isi email keputusan tidak memuat IPK/hasil | P2 |
