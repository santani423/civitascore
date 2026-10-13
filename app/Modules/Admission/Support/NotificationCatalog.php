<?php

namespace Modules\Admission\Support;

/**
 * Katalog event notifikasi PMB (docs/pmb/13 §A.2) beserta template default
 * berbahasa Indonesia. Template tenant di `notification_templates` (event_key
 * + channel) menimpa default ini; default memastikan tidak ada notifikasi
 * yang hilang diam-diam bila tenant belum mengisi template (GAP-06).
 *
 * Isi TIDAK memuat data sensitif (13 §A.5): rincian hanya di portal.
 */
final class NotificationCatalog
{
    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_LOW = 'low';

    /** Placeholder umum yang selalu diizinkan. */
    public const COMMON_PLACEHOLDERS = ['applicant_name', 'university_name', 'portal_url', 'contact'];

    /**
     * event_key => [channels(mail|database|sms), priority, subject, body, placeholders tambahan]
     *
     * @var array<string, array{0: array<int, string>, 1: string, 2: string, 3: string, 4: array<int, string>}>
     */
    public const EVENTS = [
        'pmb.account.registered' => [['mail'], self::PRIORITY_NORMAL, 'Selamat datang di PMB {{university_name}}', 'Halo {{applicant_name}}, akun PMB Anda telah dibuat. Langkah berikutnya: verifikasi email lalu lengkapi profil di {{portal_url}}.', []],
        'pmb.account.verification_requested' => [['mail'], self::PRIORITY_HIGH, 'Verifikasi akun PMB {{university_name}}', 'Halo {{applicant_name}}, gunakan tautan berikut untuk memverifikasi akun Anda (berlaku 24 jam): {{verification_url}}', ['verification_url', 'otp_code']],
        'pmb.account.verified' => [['database'], self::PRIORITY_LOW, 'Akun terverifikasi', 'Akun Anda telah aktif. Silakan pilih gelombang dan jalur pendaftaran.', []],
        'pmb.account.registration_attempt' => [['mail'], self::PRIORITY_NORMAL, 'Upaya pendaftaran dengan email Anda', 'Ada upaya pendaftaran akun PMB {{university_name}} dengan email Anda. Abaikan pesan ini bila bukan Anda; bila Anda lupa kata sandi, gunakan fitur Lupa Kata Sandi.', []],
        'pmb.account.password_reset' => [['mail'], self::PRIORITY_HIGH, 'Atur ulang kata sandi PMB', 'Gunakan tautan berikut untuk mengatur ulang kata sandi Anda (berlaku 60 menit): {{reset_url}}', ['reset_url', 'otp_code']],
        'pmb.account.security_alert' => [['mail'], self::PRIORITY_HIGH, 'Pemberitahuan keamanan akun PMB', 'Ada perubahan keamanan pada akun Anda: {{event_description}}. Bila bukan Anda, segera atur ulang kata sandi dan hubungi panitia.', ['event_description']],
        'pmb.account.locked' => [['mail'], self::PRIORITY_HIGH, 'Akun PMB terkunci sementara', 'Akun Anda terkunci sementara karena beberapa kali gagal masuk. Coba lagi setelah {{locked_until}} atau atur ulang kata sandi.', ['locked_until']],
        'pmb.account.suspended' => [['mail'], self::PRIORITY_HIGH, 'Akun PMB dibekukan', 'Akun PMB Anda dibekukan oleh panitia. Hubungi panitia untuk informasi lebih lanjut.', []],
        'pmb.account.deactivated' => [['mail'], self::PRIORITY_NORMAL, 'Akun PMB dinonaktifkan', 'Akun PMB Anda telah dinonaktifkan.', []],
        'pmb.application.created' => [['database'], self::PRIORITY_LOW, 'Pendaftaran dibuat', 'Draft pendaftaran {{path_name}} ({{wave_name}}) telah dibuat. Tenggat pengiriman: {{deadline}}.', ['path_name', 'wave_name', 'deadline']],
        'pmb.application.incomplete_reminder' => [['mail', 'database'], self::PRIORITY_NORMAL, 'Lengkapi formulir pendaftaran Anda', 'Formulir {{path_name}} Anda belum dikirim. Lengkapi sebelum {{deadline}} melalui {{portal_url}}.', ['path_name', 'deadline']],
        'pmb.application.submitted' => [['mail', 'database'], self::PRIORITY_NORMAL, 'Formulir pendaftaran diterima', 'Formulir Anda telah dikirim dengan nomor pendaftaran {{registration_number}}. Pantau status di {{portal_url}}.', ['registration_number']],
        'pmb.application.void' => [['database'], self::PRIORITY_LOW, 'Pendaftaran tidak dikirim', 'Draft pendaftaran {{path_name}} tidak dikirim hingga gelombang ditutup.', ['path_name']],
        'pmb.application.withdrawn' => [['mail', 'database'], self::PRIORITY_NORMAL, 'Pendaftaran ditarik', 'Pendaftaran {{registration_number}} telah ditarik.', ['registration_number']],
        'pmb.invoice.created' => [['database'], self::PRIORITY_LOW, 'Tagihan dibuat', 'Tagihan {{invoice_number}} sebesar {{amount}} dibuat, jatuh tempo {{deadline}}.', ['invoice_number', 'amount', 'deadline']],
        'pmb.payment.instructions' => [['mail'], self::PRIORITY_NORMAL, 'Instruksi pembayaran', 'Selesaikan pembayaran {{amount}} melalui {{channel}} sebelum {{deadline}}. Detail instruksi tersedia di {{portal_url}}.', ['amount', 'channel', 'deadline']],
        'pmb.payment.succeeded' => [['mail', 'database'], self::PRIORITY_HIGH, 'Pembayaran diterima', 'Pembayaran {{amount}} untuk tagihan {{invoice_number}} telah diterima (kuitansi {{receipt_number}}).', ['amount', 'invoice_number', 'receipt_number']],
        'pmb.payment.failed' => [['mail', 'database'], self::PRIORITY_NORMAL, 'Pembayaran tidak berhasil', 'Pembayaran untuk tagihan {{invoice_number}} tidak berhasil. Silakan lakukan pembayaran ulang melalui {{portal_url}}.', ['invoice_number']],
        'pmb.payment.expired' => [['mail', 'database'], self::PRIORITY_NORMAL, 'Instruksi pembayaran kedaluwarsa', 'Instruksi pembayaran untuk tagihan {{invoice_number}} telah kedaluwarsa. Buat instruksi baru melalui {{portal_url}}.', ['invoice_number']],
        'pmb.invoice.expired' => [['mail'], self::PRIORITY_NORMAL, 'Tagihan kedaluwarsa', 'Tagihan {{invoice_number}} telah melewati tenggat. Hubungi panitia bila Anda sudah membayar.', ['invoice_number']],
        'pmb.application.revision_requested' => [['mail', 'database', 'sms'], self::PRIORITY_HIGH, 'Perbaikan data/dokumen diperlukan', 'Ada data atau dokumen yang perlu diperbaiki sebelum {{deadline}}. Silakan masuk ke portal untuk melihat rinciannya.', ['deadline']],
        'pmb.application.verified' => [['mail', 'database'], self::PRIORITY_NORMAL, 'Lolos verifikasi administrasi', 'Pendaftaran {{registration_number}} lolos verifikasi administrasi dan masuk tahap seleksi.', ['registration_number']],
        'pmb.application.rejected' => [['mail', 'database'], self::PRIORITY_NORMAL, 'Hasil verifikasi administrasi', 'Pendaftaran {{registration_number}} tidak lolos verifikasi administrasi. Silakan masuk ke portal untuk melihat alasannya.', ['registration_number']],
        'pmb.exam.scheduled' => [['mail', 'database', 'sms'], self::PRIORITY_HIGH, 'Jadwal ujian seleksi', 'Jadwal {{exam_title}}: {{exam_date}} di {{exam_location}}. Unduh kartu peserta di portal.', ['exam_title', 'exam_date', 'exam_location']],
        'pmb.exam.rescheduled' => [['mail', 'database', 'sms'], self::PRIORITY_HIGH, 'Perubahan jadwal ujian', 'Jadwal {{exam_title}} Anda berubah menjadi {{exam_date}} di {{exam_location}}.', ['exam_title', 'exam_date', 'exam_location']],
        'pmb.exam.reminder' => [['mail', 'sms'], self::PRIORITY_NORMAL, 'Pengingat ujian besok', 'Pengingat: {{exam_title}} dilaksanakan {{exam_date}} di {{exam_location}}. Bawa kartu peserta.', ['exam_title', 'exam_date', 'exam_location']],
        'pmb.exam.absent' => [['database'], self::PRIORITY_LOW, 'Ketidakhadiran ujian tercatat', 'Anda tercatat tidak hadir pada {{exam_title}}. Hubungi panitia bila ada kendala.', ['exam_title']],
        'pmb.selection.disqualified' => [['mail', 'database'], self::PRIORITY_NORMAL, 'Status seleksi', 'Terdapat pembaruan status seleksi Anda. Silakan masuk ke portal.', []],
        'pmb.approval.requested' => [['mail', 'database'], self::PRIORITY_HIGH, 'Persetujuan hasil seleksi menunggu', 'Hasil seleksi {{path_name}} {{wave_name}} menunggu pengesahan Anda.', ['path_name', 'wave_name']],
        'pmb.result.published' => [['mail', 'database', 'sms'], self::PRIORITY_HIGH, 'Hasil seleksi Anda telah tersedia', 'Hasil seleksi Anda telah tersedia. Silakan masuk ke {{portal_url}} untuk melihatnya.', []],
        'pmb.result.corrected' => [['mail', 'database'], self::PRIORITY_HIGH, 'Hasil seleksi diperbarui', 'Hasil seleksi Anda telah diperbarui. Silakan masuk ke portal untuk melihat rinciannya.', []],
        'pmb.waitlist.promoted' => [['mail', 'database', 'sms'], self::PRIORITY_HIGH, 'Ada tawaran baru untuk Anda', 'Anda mendapat tawaran penerimaan. Selesaikan daftar ulang sebelum {{deadline}} melalui portal.', ['deadline']],
        'pmb.re_registration.confirmed' => [['mail', 'database'], self::PRIORITY_NORMAL, 'Tawaran diterima — lanjutkan daftar ulang', 'Terima kasih telah menerima tawaran. Lengkapi daftar ulang sebelum {{deadline}}.', ['deadline']],
        'pmb.re_registration.deadline_reminder' => [['mail', 'database', 'sms'], self::PRIORITY_HIGH, 'Batas daftar ulang segera berakhir', 'Daftar ulang Anda belum selesai. Batas: {{deadline}}.', ['deadline']],
        'pmb.re_registration.submitted' => [['database'], self::PRIORITY_LOW, 'Daftar ulang diajukan', 'Daftar ulang Anda sedang divalidasi petugas.', []],
        'pmb.re_registration.revision_requested' => [['mail', 'database'], self::PRIORITY_HIGH, 'Perbaikan daftar ulang diperlukan', 'Ada bagian daftar ulang yang perlu diperbaiki. Silakan masuk ke portal.', []],
        'pmb.re_registration.expired' => [['mail', 'database'], self::PRIORITY_NORMAL, 'Batas daftar ulang terlewati', 'Batas daftar ulang telah lewat sehingga tawaran tidak dapat dilanjutkan. Hubungi panitia untuk informasi pengembalian dana bila ada.', []],
        'pmb.re_registration.declined' => [['mail', 'database'], self::PRIORITY_NORMAL, 'Tawaran ditolak', 'Anda telah menolak tawaran penerimaan.', []],
        'pmb.re_registration.cancelled' => [['mail', 'database'], self::PRIORITY_NORMAL, 'Penerimaan dibatalkan', 'Penerimaan Anda dibatalkan oleh kampus. Silakan masuk ke portal atau hubungi panitia.', []],
        'pmb.student.enrolled' => [['mail', 'database', 'sms'], self::PRIORITY_HIGH, 'Selamat, Anda resmi menjadi mahasiswa', 'Selamat! NIM Anda {{nim}}. Aktivasi akun mahasiswa melalui email terpisah dan unduh bukti registrasi di portal.', ['nim']],
        'pmb.conversion.failed' => [['mail', 'database'], self::PRIORITY_HIGH, 'Konversi mahasiswa gagal', 'Konversi aplikasi {{registration_number}} gagal: {{error_code}}.', ['registration_number', 'error_code']],
        'pmb.refund.completed' => [['mail'], self::PRIORITY_NORMAL, 'Pengembalian dana selesai', 'Pengembalian dana sebesar {{amount}} telah diproses.', ['amount']],
        'pmb.chat.reply' => [['database'], self::PRIORITY_NORMAL, 'Balasan dari panitia', 'Ada balasan dari panitia untuk pertanyaan Anda.', []],
        'pmb.broadcast' => [['mail', 'sms'], self::PRIORITY_NORMAL, '{{subject}}', '{{body}}', ['subject', 'body']],
        'pmb.ops.alert' => [['mail', 'database'], self::PRIORITY_HIGH, 'Peringatan operasional PMB', '{{message}}', ['message']],
    ];

    /** @return array<int, string> */
    public static function allowedPlaceholders(string $eventKey): array
    {
        return [...self::COMMON_PLACEHOLDERS, ...(self::EVENTS[$eventKey][4] ?? [])];
    }

    public static function exists(string $eventKey): bool
    {
        return array_key_exists($eventKey, self::EVENTS);
    }
}
