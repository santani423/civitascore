<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\Student;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Notification\Services\NotificationDispatcher;
use Throwable;

/**
 * Notifikasi KEU lewat Modul Notification (in-app; template dapat disunting
 * per universitas di Pengaturan → Notifikasi). Kegagalan kirim tidak pernah
 * membatalkan transaksi keuangan.
 */
class FinanceNotifier
{
    /** @var array<string, array{name: string, body: string}> */
    public const TEMPLATES = [
        'keu.invoice_issued' => ['name' => 'Keuangan: Tagihan baru', 'body' => 'Tagihan {{number}} ({{period}}) sebesar {{amount}} telah terbit, jatuh tempo {{due_date}}.'],
        'keu.payment_succeeded' => ['name' => 'Keuangan: Pembayaran diterima', 'body' => 'Pembayaran {{amount}} ({{receipt}}) telah diterima dan dicatat.'],
        'keu.proof_rejected' => ['name' => 'Keuangan: Bukti transfer ditolak', 'body' => 'Bukti transfer {{amount}} ditolak: {{reason}}. Silakan unggah ulang.'],
        'keu.payment_reversed' => ['name' => 'Keuangan: Pembayaran dibatalkan', 'body' => 'Pembayaran {{receipt}} sebesar {{amount}} dibatalkan: {{reason}}.'],
        'keu.deposit_credited' => ['name' => 'Keuangan: Saldo deposit bertambah', 'body' => 'Saldo deposit Anda bertambah {{amount}} ({{reason}}).'],
        'keu.installment_decided' => ['name' => 'Keuangan: Keputusan cicilan', 'body' => 'Pengajuan cicilan tagihan {{number}} {{status}}.'],
        'keu.penalty_issued' => ['name' => 'Keuangan: Tagihan denda', 'body' => 'Denda keterlambatan {{amount}} untuk tagihan {{number}} telah ditagihkan.'],
        'keu.aid_decided' => ['name' => 'Keuangan: Beasiswa/potongan', 'body' => '{{policy}} untuk Anda {{status}}.'],
        'keu.ukt_decided' => ['name' => 'Keuangan: Golongan UKT', 'body' => 'Pengajuan perubahan golongan UKT {{status}}. {{note}}'],
        'keu.refund_status' => ['name' => 'Keuangan: Pengembalian dana', 'body' => 'Pengembalian dana {{number}} sebesar {{amount}}: {{status}}.'],
        'keu.block_imposed' => ['name' => 'Keuangan: Cekal keuangan', 'body' => 'Layanan {{services}} dibatasi: {{reason}}'],
        'keu.block_revoked' => ['name' => 'Keuangan: Cekal dicabut', 'body' => 'Cekal keuangan ({{rule}}) telah dicabut.'],
        'keu.exemption_decided' => ['name' => 'Keuangan: Dispensasi', 'body' => 'Dispensasi {{service}} {{status}}{{until}}.'],
        'keu.due_reminder' => ['name' => 'Keuangan: Pengingat jatuh tempo', 'body' => 'Tagihan {{number}} sisa {{amount}} jatuh tempo {{due_date}}.'],
    ];

    /** @var array<string, true> */
    private array $ensured = [];

    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /** @param  array<string, string>  $placeholders */
    public function student(Student $student, string $eventKey, array $placeholders, string $link = '/portal/keuangan'): void
    {
        $student->loadMissing('user');

        if ($student->user !== null) {
            $this->user($student->user, $eventKey, $placeholders, $link);
        }
    }

    /** @param  array<string, string>  $placeholders */
    public function user(User $user, string $eventKey, array $placeholders, ?string $link = null): void
    {
        try {
            $this->ensureTemplates();
            $this->dispatcher->dispatch($user, $eventKey, [NotificationChannel::Database], $placeholders, ['link' => $link]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function ensureTemplates(): void
    {
        $universityId = app(TenantContext::class)->universityId();

        if ($universityId === null || isset($this->ensured[$universityId])) {
            return;
        }

        foreach (self::TEMPLATES as $eventKey => $template) {
            NotificationTemplate::query()->firstOrCreate(
                ['university_id' => $universityId, 'event_key' => $eventKey, 'channel' => NotificationChannel::Database->value],
                ['name' => $template['name'], 'subject' => $template['name'], 'body_template' => $template['body'], 'is_active' => true],
            );
        }

        $this->ensured[$universityId] = true;
    }
}
