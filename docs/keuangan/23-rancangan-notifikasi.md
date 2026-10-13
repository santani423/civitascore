# 23 — Rancangan Notifikasi

> **Status:** rancangan. Disusun 2026-10-13.
> Kembali ke [README](README.md) · Modul Notification (Ada): `NotificationDispatcher`, `notification_templates`, `user_notification_preferences` · Asumsi kanal: [KEU-A-08](29-glossary-dan-keputusan-bisnis.md#4-asumsi-keu-a)

Dokumen ini mendefinisikan seluruh notifikasi modul KEU: pemicu, penerima, kanal, isi, dan aturan pengirimannya. Notifikasi memakai modul Notification yang sudah ada; KEU tidak membuat mekanisme pengiriman sendiri.

## Daftar Isi

1. [Mekanisme yang Ada](#1-mekanisme-yang-ada)
2. [Prinsip](#2-prinsip)
3. [Katalog Notifikasi Mahasiswa](#3-katalog-notifikasi-mahasiswa)
4. [Katalog Notifikasi Staf, Pimpinan, dan Akademik](#4-katalog-notifikasi-staf-pimpinan-dan-akademik)
5. [Pengingat Terjadwal](#5-pengingat-terjadwal)
6. [Isi, Privasi, dan Templat](#6-isi-privasi-dan-templat)
7. [Kanal Masa Depan](#7-kanal-masa-depan)
8. [Kegagalan dan Audit](#8-kegagalan-dan-audit)
9. [Acceptance Criteria dan Test Case](#9-acceptance-criteria-dan-test-case)

---

## 1. Mekanisme yang Ada

| Komponen | Perilaku | Pemakaian KEU |
|---|---|---|
| `NotificationDispatcher::dispatch(User $user, string $eventKey, array $channels, array $placeholders = [], array $data = [])` | Mengirim berdasarkan templat per tenant dan kunci event; tercatat oleh listener `LogNotificationDispatch` | Satu-satunya jalur pengiriman KEU |
| Kunci event | Pola `domain.event` (mis. `student.krs_approved`) | KEU memakai awalan `finance.` |
| Kanal | `database` dan `mail` terimplementasi; `push`, `whatsapp`, `sms` belum (`NotificationChannel::isImplemented()`) | Default KEU: `database` + `mail` |
| Preferensi pengguna | `user_notification_preferences` (`channel`, `notification_type`, `is_enabled`) | Dihormati, kecuali notifikasi **wajib** (§2) |
| Templat | Per tenant, dapat disunting bagian akademik/admin (`notification_templates.*`) | KEU menyediakan templat bawaan (seeder) untuk setiap kunci |

**Ketergantungan [R]:** dispatcher belum mengenal notifikasi wajib. KEU membutuhkan penanda wajib (daftar kunci wajib atau parameter) agar notifikasi transaksional tetap terkirim melalui surel walaupun pengguna mematikan surel untuk kunci tersebut. Sampai tersedia, notifikasi wajib tetap dikirim ke kanal `database` (kotak masuk aplikasi) yang menjadi catatan resmi.

## 2. Prinsip

| Prinsip | Penerapan |
|---|---|
| Kirim setelah commit | Notifikasi dipicu dari event outbox atau listener `afterCommit`; transaksi yang dibatalkan (*rollback*) tidak pernah menghasilkan notifikasi ([KEU-BR-018](25-business-rules-dan-validasi.md)) |
| Idempoten | Kunci pengiriman = `{eventKey}:{entity_id}:{versi/tanggal}`; pemrosesan ulang event tidak mengirim dua kali |
| Wajib vs informatif | **Wajib**: tagihan terbit, pembayaran terverifikasi/ditolak/dibalik, refund selesai, cekal terbit/dibuka, keputusan UKT/keberatan. **Informatif**: pengingat, ringkasan, notifikasi staf |
| Tautan, bukan data penuh | Isi memuat ringkasan dan tautan ke portal; rincian dibuka setelah login |
| Privasi | Tidak ada data mahasiswa lain, kredensial, nomor rekening penuh, dokumen UKT; nominal tidak dikirim ke peran akademik ([KEU-BR-270](25-business-rules-dan-validasi.md)) |
| Ringkas | Notifikasi berfrekuensi tinggi digabung (*digest*): pengingat per mahasiswa per hari, antrean bukti per jam, denda per hari |
| Bahasa | Bahasa Indonesia baku; tanggal dan jam dalam zona waktu tenant dengan akhiran WIB/WITA/WIT |

## 3. Katalog Notifikasi Mahasiswa

| Kunci | Pemicu | Kanal | Wajib | Placeholder utama |
|---|---|---|:-:|---|
| `finance.invoice_issued` | Tagihan terbit (`InvoiceIssued`, bukan draf) | database, mail | ✓ | nama, nomor tagihan, periode, nominal, jatuh tempo, tautan |
| `finance.invoice_due_reminder` | H-7 dan H-1 jatuh tempo (§5) | database, mail | | daftar tagihan/termin, sisa, jatuh tempo |
| `finance.invoice_overdue` | H+1 setelah jatuh tempo | database, mail | | nomor, sisa, hari terlambat, info denda/cekal bila ada |
| `finance.invoice_corrected` | Penyesuaian tagihan diterapkan (koreksi, potongan manual, beasiswa susulan, voucher oleh staf) | database, mail | ✓ | nomor, nominal lama → baru, alasan umum |
| `finance.invoice_cancelled` | Tagihan dibatalkan | database, mail | ✓ | nomor, alasan umum |
| `finance.payment_instruction` | Transaksi online `pending` | database (mail opsional) | | kanal, nomor VA/tautan QR, total, batas waktu |
| `finance.payment_transaction_expired` | Transaksi kedaluwarsa tanpa bayar | database | | kanal, tautan buat transaksi baru |
| `finance.payment_verified` | Pembayaran `verified` | database, mail | ✓ | nominal, tanggal, kanal, tagihan yang dilunasi, sisa, tautan kuitansi |
| `finance.payment_rejected` | Bukti ditolak | database, mail | ✓ | kode alasan (bahasa sederhana), catatan, langkah berikut |
| `finance.payment_reversed` | Pembayaran dibalik | database, mail | ✓ | nomor pembayaran, alasan umum, kontak bagian keuangan |
| `finance.installment_decided` | Cicilan disetujui/ditolak | database, mail | ✓ | jadwal termin / alasan |
| `finance.installment_term_reminder` | H-7, H-1 termin | database, mail | | termin ke-, nominal, jatuh tempo |
| `finance.discount_awarded` / `finance.discount_revoked` | Beasiswa/potongan aktif / ditangguhkan / dicabut | database, mail | ✓ | nama skema, term berlaku, efek |
| `finance.late_fee_assessed` | Denda terbentuk (digest harian) | database, mail | | tagihan dasar, bucket, nominal, cara menghindari denda berikutnya |
| `finance.late_fee_waived` | Pembebasan denda | database, mail | | nominal dibebaskan |
| `finance.deposit_changed` | Mutasi deposit (selain yang sudah tercakup notifikasi lain) | database | | jenis mutasi, nominal, saldo tersedia |
| `finance.refund_status_changed` | Refund disetujui/ditolak/selesai/gagal | database, mail | ✓ (selesai) | nominal, status, rekening tujuan termasking |
| `finance.ukt_determined` | Penetapan UKT aktif | database, mail | ✓ | golongan, term berlaku, batas waktu keberatan |
| `finance.ukt_appeal_decided` | Keberatan diputus | database, mail | ✓ | keputusan, golongan baru/alasan |
| `finance.hold_imposed` | Cekal terbit | database, mail | ✓ | layanan dibatasi, tagihan penyebab, cara menyelesaikan |
| `finance.hold_released` | Cekal dibuka | database, mail | ✓ | layanan yang kembali terbuka |
| `finance.hold_exemption_decided` | Pengecualian disetujui/ditolak | database, mail | ✓ | layanan, berlaku sampai |
| `finance.hold_exemption_expiring` | H-2 pengecualian berakhir | database, mail | | tanggal berakhir, sisa tunggakan |
| `finance.dispute_resolved` | Sengketa diputus | database, mail | ✓ | keputusan, tindak lanjut |

## 4. Katalog Notifikasi Staf, Pimpinan, dan Akademik

| Kunci | Pemicu | Penerima | Kanal |
|---|---|---|---|
| `finance.approval_requested` | Entitas masuk `pending_approval` pada mode persetujuan langsung (mode workflow memakai notifikasi ApprovalWorkflow yang ada) | Pemegang `*.approve` terkait (bukan pengaju) | database, mail |
| `finance.proof_submitted` | Bukti transfer baru (digest per jam) | Staf keuangan (`payments.approve`) | database |
| `finance.installment_requested` | Pengajuan cicilan mahasiswa | Staf keuangan | database |
| `finance.dispute_submitted` | Sengketa baru | Staf keuangan | database |
| `finance.ukt_appeal_submitted` | Keberatan UKT baru | Staf keuangan | database |
| `finance.cashier_difference` | Sesi kas ditutup dengan selisih | Bendahara | database, mail |
| `finance.reconciliation_exceptions` | Ringkasan harian item rekonsiliasi terbuka | Staf keuangan | database |
| `finance.period_closing_reminder` | H-3 akhir bulan + pemblokir penutupan | Bendahara | database, mail |
| `finance.integrity_violation` | `finance:verify-integrity` menemukan pelanggaran | Bendahara, auditor | database, mail |
| `finance.anomaly_detected` | Aturan anomali terpicu ([24 §12](24-keamanan-audit-dan-kontrol-internal.md)) | Bendahara, auditor | database, mail |
| `finance.integration_alert` | Kanal `down`, webhook gagal menumpuk, sinkronisasi cekal `failed`, outbox `dead` | Administrator integrasi (+ bendahara untuk dampak pembayaran/cekal) | database, mail |
| `finance.hold_advisee_summary` | Ringkasan harian mahasiswa bimbingan yang tercekal (tanpa nominal) | Dosen wali | database |
| Ringkasan piutang mingguan [R] | Senin 07:00 | Bendahara, pimpinan | mail |

## 5. Pengingat Terjadwal

Job `finance:send-due-reminders`, harian 07:00 zona tenant:

| Langkah | Ketentuan |
|---|---|
| Kandidat | Tagihan `unpaid/partial` (bukan draf/batal) dan termin cicilan aktif yang belum lunas dengan jatuh tempo = hari ini + *n* untuk *n* ∈ `finance.notification.due_reminder_days` (default 7 dan 1), serta jatuh tempo kemarin (H+1, `invoice_overdue`) |
| Pengecualian | Tagihan dengan transaksi online `pending` yang belum kedaluwarsa (mahasiswa sedang membayar) hanya mendapat pengingat H-1; tagihan disengketakan tetap diingatkan dengan catatan |
| Penggabungan | Satu notifikasi per mahasiswa per hari berisi semua tagihan/termin yang relevan |
| Idempotensi | Kunci `reminder:{student}:{tanggal}:{jenis}` |
| Penghentian | Tagihan yang lunas atau dibatalkan sebelum job berjalan tidak dikirimi pengingat ([KEU-TC-NTF-004](26-skenario-pengujian.md)) |

## 6. Isi, Privasi, dan Templat

Contoh templat bawaan (placeholder ditulis sesuai sintaks templat modul Notification):

**`finance.invoice_issued`**

> **Subjek:** Tagihan {nomor_tagihan} — {periode}
> Yth. {nama}, tagihan {jenis_tagihan} periode {periode} sebesar **{nominal}** telah terbit dengan jatuh tempo **{jatuh_tempo}**. Rincian dan pilihan pembayaran tersedia di {tautan_portal}.

**`finance.payment_verified`**

> **Subjek:** Pembayaran {nomor_pembayaran} diterima
> Yth. {nama}, pembayaran sebesar **{nominal}** melalui {kanal} pada {tanggal_bayar} telah kami terima dan diterapkan ke: {daftar_tagihan}. Sisa kewajiban Anda saat ini {sisa_total}. Kuitansi: {tautan_kuitansi}.

**`finance.hold_imposed`**

> **Subjek:** Layanan akademik dibatasi sementara
> Yth. {nama}, layanan **{daftar_layanan}** dibatasi karena terdapat kewajiban yang telah melewati jatuh tempo ({daftar_tagihan_singkat}). Pembatasan dibuka otomatis setelah pembayaran terverifikasi. Bayar atau hubungi Bagian Keuangan: {tautan_portal}.

**`finance.hold_advisee_summary`** (dosen wali, tanpa nominal)

> Mahasiswa bimbingan Anda dengan pembatasan layanan karena administrasi keuangan: {daftar_nim_nama_layanan}. Rincian kewajiban hanya dapat dilihat oleh mahasiswa dan Bagian Keuangan.

| Data | Boleh dalam notifikasi? |
|---|---|
| Nominal tagihan/pembayaran milik penerima | Ya (mahasiswa sendiri, staf keuangan) |
| Nomor VA milik transaksi penerima | Ya (hanya `payment_instruction`) |
| Nomor rekening kampus | Ya (rekening penerima pembayaran, bersifat publik bagi pembayar) |
| Nomor rekening mahasiswa (refund) | Hanya 4 digit akhir |
| Nominal tunggakan untuk dosen/BAAK | Tidak |
| Alasan penolakan internal (catatan staf) | Hanya versi untuk mahasiswa (kode alasan yang diterjemahkan + catatan yang ditandai untuk mahasiswa) |

## 7. Kanal Masa Depan

Bila kanal `whatsapp`, `sms`, atau `push` diimplementasikan di modul Notification, KEU cukup menambahkan kanal pada daftar per kunci (tanpa perubahan logika). Kandidat prioritas: `payment_instruction`, `invoice_due_reminder`, `payment_verified`, `hold_imposed`. Pesan WhatsApp/SMS memakai versi ringkas tanpa nominal lengkap untuk notifikasi selain milik penerima.

## 8. Kegagalan dan Audit

| Skenario | Penanganan |
|---|---|
| Pengiriman surel gagal | Ditangani antrean (retry); kanal `database` tetap tercatat |
| Pengguna tidak memiliki akun `users` (mahasiswa tanpa tautan) | Notifikasi tidak terkirim; tercatat di log dispatch; muncul di laporan "mahasiswa tanpa akun" [R] ([KEU-A-14](29-glossary-dan-keputusan-bisnis.md#4-asumsi-keu-a)) |
| Event diproses ulang | Kunci idempoten mencegah duplikasi |
| Templat tenant dihapus | Dispatcher memakai templat bawaan KEU |
| Audit | Setiap pengiriman dicatat oleh `LogNotificationDispatch` (yang sudah ada); KEU tidak menulis `audit_logs` untuk notifikasi |

## 9. Acceptance Criteria dan Test Case

| Acceptance criteria | Test case |
|---|---|
| [KEU-AC-NTF-01](27-acceptance-criteria.md) Notifikasi terkirim untuk event yang tepat ke penerima yang tepat | [KEU-TC-NTF-001](26-skenario-pengujian.md), [KEU-TC-NTF-002](26-skenario-pengujian.md), [KEU-TC-NTF-004](26-skenario-pengujian.md) |
| [KEU-AC-NTF-02](27-acceptance-criteria.md) Hanya setelah commit, tanpa duplikasi | [KEU-TC-NTF-003](26-skenario-pengujian.md) |
| [KEU-AC-NTF-03](27-acceptance-criteria.md) Tidak membocorkan data sensitif | [KEU-TC-NTF-005](26-skenario-pengujian.md) |
