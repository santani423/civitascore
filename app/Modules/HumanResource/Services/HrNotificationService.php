<?php

namespace Modules\HumanResource\Services;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\Academic\Models\Employee;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Notification\Services\NotificationDispatcher;
use Modules\UserManagement\Models\UserRole;

/**
 * Notifikasi SDM lewat infrastruktur Modul Notification yang sudah ada
 * (NotificationDispatcher + notification_templates). Kanal default
 * in-app (database); template bisa diubah per universitas di halaman
 * Pengaturan → Notifikasi.
 */
class HrNotificationService
{
    /** @var array<string, array{name: string, body: string}> */
    public const TEMPLATES = [
        'hr.contract_expiring' => [
            'name' => 'SDM: Kontrak akan berakhir',
            'body' => 'Kontrak {{contract_number}} milik {{employee}} berakhir dalam {{days}} hari ({{end_date}}).',
        ],
        'hr.request_submitted' => [
            'name' => 'SDM: Pengajuan baru',
            'body' => '{{type}} dari {{employee}}: {{title}}.',
        ],
        'hr.request_decided' => [
            'name' => 'SDM: Keputusan pengajuan',
            'body' => 'Pengajuan "{{title}}" telah {{status}}.',
        ],
        'hr.document_expiring' => [
            'name' => 'SDM: Dokumen akan kedaluwarsa',
            'body' => 'Dokumen {{document}} milik {{employee}} kedaluwarsa pada {{expires_at}}.',
        ],
    ];

    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /**
     * @param  array<string, string>  $placeholders
     */
    public function notifyHrAdministrators(string $eventKey, array $placeholders): void
    {
        $this->ensureTemplates();

        foreach ($this->hrAdministrators() as $user) {
            $this->dispatcher->dispatch($user, $eventKey, [NotificationChannel::Database], $placeholders);
        }
    }

    /**
     * @param  array<string, string>  $placeholders
     */
    public function notifyEmployee(Employee $employee, string $eventKey, array $placeholders): void
    {
        $user = $employee->user;

        if ($user === null) {
            return;
        }

        $this->ensureTemplates();
        $this->dispatcher->dispatch($user, $eventKey, [NotificationChannel::Database], $placeholders);
    }

    /**
     * Pengguna ber-role Bagian SDM di universitas yang sedang aktif.
     *
     * @return Collection<int, User>
     */
    public function hrAdministrators(): Collection
    {
        $userIds = UserRole::query()
            ->where('university_id', app(TenantContext::class)->universityId())
            ->whereHas('role', fn ($query) => $query->where('slug', 'hr_administrator'))
            ->pluck('user_id');

        return User::query()->whereIn('id', $userIds)->where('is_active', true)->get();
    }

    /**
     * Idempotent — template yang sudah ada (mungkin sudah disunting admin)
     * tidak ditimpa.
     */
    public function ensureTemplates(): void
    {
        $universityId = app(TenantContext::class)->universityId();

        if ($universityId === null) {
            return;
        }

        foreach (self::TEMPLATES as $eventKey => $template) {
            NotificationTemplate::query()->firstOrCreate(
                ['university_id' => $universityId, 'event_key' => $eventKey, 'channel' => NotificationChannel::Database->value],
                ['name' => $template['name'], 'subject' => $template['name'], 'body_template' => $template['body'], 'is_active' => true],
            );
        }
    }
}
