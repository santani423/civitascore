<?php

namespace Modules\Academic\Services;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\Academic\Models\Student;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Notification\Services\NotificationDispatcher;
use Modules\UserManagement\Models\UserRole;

/**
 * Notifikasi Portal Mahasiswa lewat infrastruktur Modul Notification yang
 * sudah ada (NotificationDispatcher + notification_templates) — pola sama
 * dengan HrNotificationService. Kanal in-app (database); teks template bisa
 * disunting per universitas di Pengaturan → Notifikasi. `link` disimpan di
 * payload notifikasi supaya pusat notifikasi bisa membuka halaman terkait.
 */
class StudentNotificationService
{
    /** @var array<string, array{name: string, body: string}> */
    public const TEMPLATES = [
        'student.krs_submitted' => [
            'name' => 'KRS: Berhasil diajukan',
            'body' => 'KRS {{term}} berhasil diajukan ({{credits}} SKS) dan menunggu persetujuan dosen wali.',
        ],
        'student.krs_review_requested' => [
            'name' => 'KRS: Menunggu persetujuan',
            'body' => 'KRS {{student}} ({{nim}}) untuk {{term}} menunggu persetujuan Anda.',
        ],
        'student.krs_approved' => [
            'name' => 'KRS: Disetujui',
            'body' => 'KRS {{term}} Anda telah disetujui. Jadwal kuliah sudah dapat dilihat.',
        ],
        'student.krs_rejected' => [
            'name' => 'KRS: Ditolak',
            'body' => 'KRS {{term}} Anda ditolak: {{note}}. Silakan perbaiki lalu ajukan kembali.',
        ],
        'student.grade_published' => [
            'name' => 'Nilai: Tersedia',
            'body' => 'Nilai {{course}} ({{term}}) telah tersedia: {{grade}}.',
        ],
        'student.assignment_published' => [
            'name' => 'Tugas: Baru',
            'body' => 'Tugas baru "{{title}}" untuk {{course}}, batas pengumpulan {{due_at}}.',
        ],
        'student.assignment_graded' => [
            'name' => 'Tugas: Dinilai',
            'body' => 'Tugas "{{title}}" ({{course}}) telah dinilai: {{score}}.',
        ],
        'student.request_submitted' => [
            'name' => 'Pengajuan mahasiswa: Baru',
            'body' => '{{type}} dari {{student}} ({{nim}}): {{title}}.',
        ],
        'student.request_decided' => [
            'name' => 'Pengajuan mahasiswa: Keputusan',
            'body' => 'Pengajuan "{{title}}" telah {{status}}.',
        ],
    ];

    /** @var array<string, true> universitas yang templatenya sudah dipastikan ada pada instance ini */
    private array $ensuredFor = [];

    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /**
     * @param  array<string, string>  $placeholders
     */
    public function notifyStudent(Student $student, string $eventKey, array $placeholders, ?string $link = null): void
    {
        $user = $student->user;

        if ($user !== null) {
            $this->notifyUser($user, $eventKey, $placeholders, $link);
        }
    }

    /**
     * @param  array<string, string>  $placeholders
     */
    public function notifyUser(User $user, string $eventKey, array $placeholders, ?string $link = null): void
    {
        $this->ensureTemplates();
        $this->dispatcher->dispatch($user, $eventKey, [NotificationChannel::Database], $placeholders, ['link' => $link]);
    }

    /**
     * Seluruh pengguna aktif ber-role tertentu di universitas yang sedang aktif.
     *
     * @param  array<string, string>  $placeholders
     */
    public function notifyRole(string $roleSlug, string $eventKey, array $placeholders, ?string $link = null): void
    {
        foreach ($this->usersWithRole($roleSlug) as $user) {
            $this->notifyUser($user, $eventKey, $placeholders, $link);
        }
    }

    /**
     * @return Collection<int, User>
     */
    public function usersWithRole(string $roleSlug): Collection
    {
        $userIds = UserRole::query()
            ->where('university_id', app(TenantContext::class)->universityId())
            ->whereHas('role', fn ($query) => $query->where('slug', $roleSlug))
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

        if ($universityId === null || isset($this->ensuredFor[$universityId])) {
            return;
        }

        foreach (self::TEMPLATES as $eventKey => $template) {
            NotificationTemplate::query()->firstOrCreate(
                ['university_id' => $universityId, 'event_key' => $eventKey, 'channel' => NotificationChannel::Database->value],
                ['name' => $template['name'], 'subject' => $template['name'], 'body_template' => $template['body'], 'is_active' => true],
            );
        }

        $this->ensuredFor[$universityId] = true;
    }
}
