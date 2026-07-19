<?php

namespace Modules\Tenancy\Database\Seeders;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Enums\ApprovalHistoryEvent;
use Modules\ApprovalWorkflow\Enums\ApprovalRejectAction;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStepStatus;
use Modules\ApprovalWorkflow\Models\ApprovalHistory;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflowStep;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Models\AuditLog;
use Modules\FileManagement\Enums\FileUploadStatus;
use Modules\FileManagement\Models\FileUpload;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\NotificationTemplate;
use Modules\SystemSetting\Enums\SettingValueType;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\SubscriptionStatus;
use Modules\Tenancy\Enums\UniversityStatus;
use Modules\Tenancy\Models\PlatformModule;
use Modules\Tenancy\Models\SubscriptionPlan;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UniversityDomain;
use Modules\Tenancy\Models\UniversityFeatureFlag;
use Modules\Tenancy\Models\UniversityModule;
use Modules\Tenancy\Models\UniversitySetting;
use Modules\Tenancy\Models\UniversitySubscription;
use Modules\Tenancy\Models\UniversitySubscriptionHistory;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

/**
 * Builds one fully wired demo university: identity, domain, subscription,
 * enabled modules, feature flag overrides, branding/security settings,
 * demo login accounts (with real role + membership grants), and a sample
 * of records for every business module that actually exists today
 * (approval workflow, file upload, notification template, audit log).
 *
 * Deliberately does NOT seed academic/financial transaction records
 * (mahasiswa, dosen, KRS, nilai, tagihan, ...) — those tables don't exist
 * in this codebase yet (see docs/FRONTEND-IMPLEMENTASI.md Fase 3). Demo
 * accounts use organizational role labels (dosen, mahasiswa, ...) from
 * OrganizationalRoleSeeder, which only requires Role+User+membership rows,
 * not the underlying business tables.
 *
 * Takes its config as a constructor array rather than being called via
 * $this->call() (which doesn't support per-call arguments) — instantiate
 * directly: (new UniversitySeeder($config))->run().
 *
 * university_id is always set explicitly on every tenant-scoped create()
 * call here, never left to TenantScoped's creating-event auto-fill —
 * DatabaseSeeder uses WithoutModelEvents (to avoid flooding audit_logs
 * with seed noise via the Auditable trait), which also silently disables
 * that auto-fill for the whole seeding run.
 *
 * @param array{
 *     code: string, name: string, short_name: string, domain: string,
 *     education_institution_type: string, accreditation: string,
 *     primary_color: string, secondary_color: string,
 *     plan_code: string, subscription_status: SubscriptionStatus,
 *     modules: array<int, string>, feature_flags: array<string, bool>,
 *     demo_accounts: array<int, array{email: string, name: string, role: string, membership: \Modules\Tenancy\Enums\MembershipType}>,
 * } $config
 */
class UniversitySeeder extends Seeder
{
    public function __construct(private readonly array $config) {}

    public function run(): void
    {
        $university = $this->createUniversity();

        app(TenantContext::class)->setUniversityId($university->id);

        $this->createSubscription($university);
        $this->enableModules($university);
        $this->applyFeatureFlags($university);
        $this->seedSettings($university);
        $admins = $this->createDemoAccounts($university);
        $this->seedApprovalDemo($university, $admins[0] ?? null);
        $this->seedNotificationTemplates($university);
        $this->seedFileUploads($university, $admins[0] ?? null);
        $this->seedAuditLogEntries($university, $admins[0] ?? null);

        app(TenantContext::class)->setUniversityId(null);
    }

    private function createUniversity(): University
    {
        $university = University::query()->updateOrCreate(
            ['code' => $this->config['code']],
            [
                'slug' => \Illuminate\Support\Str::slug($this->config['name']),
                'name' => $this->config['name'],
                'short_name' => $this->config['short_name'],
                'education_institution_type' => $this->config['education_institution_type'],
                'accreditation' => $this->config['accreditation'],
                'email' => 'info@'.$this->config['domain'],
                'primary_color' => $this->config['primary_color'],
                'secondary_color' => $this->config['secondary_color'],
                'status' => UniversityStatus::Active,
                'is_active' => true,
                'activated_at' => now(),
            ],
        );

        UniversityDomain::query()->updateOrCreate(
            ['domain' => $this->config['domain']],
            ['university_id' => $university->id, 'is_primary' => true, 'verified_at' => now()],
        );

        return $university;
    }

    private function createSubscription(University $university): void
    {
        $plan = SubscriptionPlan::query()->where('code', $this->config['plan_code'])->firstOrFail();

        $subscription = UniversitySubscription::query()->updateOrCreate(
            ['university_id' => $university->id],
            [
                'subscription_plan_id' => $plan->id,
                'status' => $this->config['subscription_status'],
                'current_period_starts_at' => now()->subMonths(2),
                'current_period_ends_at' => now()->addYear(),
            ],
        );

        UniversitySubscriptionHistory::query()->firstOrCreate([
            'university_id' => $university->id,
            'subscription_plan_id' => $plan->id,
            'event' => 'subscription_seeded',
        ], [
            'note' => "Berlangganan paket {$plan->name} saat seeding demo.",
        ]);
    }

    private function enableModules(University $university): void
    {
        foreach ($this->config['modules'] as $moduleKey) {
            $module = PlatformModule::query()->where('key', $moduleKey)->first();

            if (! $module) {
                continue;
            }

            UniversityModule::query()->updateOrCreate(
                ['university_id' => $university->id, 'module_id' => $module->id],
                ['is_enabled' => true, 'enabled_at' => now()],
            );
        }
    }

    private function applyFeatureFlags(University $university): void
    {
        foreach ($this->config['feature_flags'] as $key => $isEnabled) {
            UniversityFeatureFlag::query()->updateOrCreate(
                ['university_id' => $university->id, 'key' => $key],
                ['is_enabled' => $isEnabled],
            );
        }
    }

    private function seedSettings(University $university): void
    {
        $settings = [
            ['key' => 'branding.primary_color', 'value' => $this->config['primary_color'], 'type' => SettingValueType::String, 'group' => 'branding'],
            ['key' => 'branding.secondary_color', 'value' => $this->config['secondary_color'], 'type' => SettingValueType::String, 'group' => 'branding'],
            ['key' => 'academic.current_academic_year', 'value' => (string) now()->year, 'type' => SettingValueType::String, 'group' => 'academic'],
        ];

        foreach ($settings as $setting) {
            UniversitySetting::query()->updateOrCreate(
                ['university_id' => $university->id, 'key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type'], 'group' => $setting['group'], 'is_public' => true],
            );
        }
    }

    /**
     * @return array<int, User>
     */
    private function createDemoAccounts(University $university): array
    {
        $created = [];

        foreach ($this->config['demo_accounts'] as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'password' => 'password', 'email_verified_at' => now(), 'is_active' => true],
            );

            UserUniversity::query()->updateOrCreate(
                ['user_id' => $user->id, 'university_id' => $university->id],
                [
                    'membership_type' => $account['membership'],
                    'status' => MembershipStatus::Active,
                    'joined_at' => now(),
                    'is_default' => true,
                ],
            );

            $role = Role::query()->where('slug', $account['role'])->whereNull('university_id')->first();

            if ($role) {
                UserRole::query()->firstOrCreate(
                    ['user_id' => $user->id, 'role_id' => $role->id, 'university_id' => $university->id, 'scope_type' => null, 'scope_id' => null],
                    ['assigned_at' => now()],
                );
            }

            $created[] = $user;
        }

        return $created;
    }

    private function seedApprovalDemo(University $university, ?User $approver): void
    {
        if (! $approver) {
            return;
        }

        $workflow = ApprovalWorkflow::query()->updateOrCreate(
            ['university_id' => $university->id, 'workflowable_type' => 'demo.example', 'name' => 'Contoh Alur Persetujuan'],
            ['is_active' => true, 'description' => 'Alur persetujuan contoh untuk demo platform.'],
        );

        $step = ApprovalWorkflowStep::query()->updateOrCreate(
            ['approval_workflow_id' => $workflow->id, 'sequence' => 1],
            [
                'name' => 'Persetujuan Admin',
                'approver_type' => ApprovalApproverType::User,
                'approver_user_id' => $approver->id,
                'action_on_reject' => ApprovalRejectAction::StopWorkflow,
            ],
        );

        $this->seedApprovalRequest($university, $workflow, $step, $approver, ApprovalRequestStatus::Submitted);
        $this->seedApprovalRequest($university, $workflow, $step, $approver, ApprovalRequestStatus::Approved);
        $this->seedApprovalRequest($university, $workflow, $step, $approver, ApprovalRequestStatus::Rejected);
    }

    private function seedApprovalRequest(
        University $university,
        ApprovalWorkflow $workflow,
        ApprovalWorkflowStep $workflowStep,
        User $requester,
        ApprovalRequestStatus $status,
    ): void {
        // requestable_id is deterministic (not a fresh ULID per run) so this
        // whole method is safe to re-run — firstOrCreate matches the same
        // demo row instead of inserting a duplicate.
        $requestableId = 'demo-'.$university->code.'-'.$status->value;

        $request = ApprovalRequest::query()->firstOrCreate(
            ['university_id' => $university->id, 'requestable_type' => 'demo.example', 'requestable_id' => $requestableId],
            [
                'approval_workflow_id' => $workflow->id,
                'requested_by' => $requester->id,
                'status' => $status,
                'submitted_at' => now()->subDays(3),
                'completed_at' => $status === ApprovalRequestStatus::Submitted ? null : now()->subDay(),
                'notes' => 'Pengajuan contoh untuk demo platform.',
            ],
        );

        if (! $request->wasRecentlyCreated) {
            return;
        }

        $stepStatus = match ($status) {
            ApprovalRequestStatus::Submitted => ApprovalRequestStepStatus::Pending,
            ApprovalRequestStatus::Approved => ApprovalRequestStepStatus::Approved,
            ApprovalRequestStatus::Rejected => ApprovalRequestStepStatus::Rejected,
            default => ApprovalRequestStepStatus::Pending,
        };

        $requestStep = ApprovalRequestStep::query()->create([
            'university_id' => $university->id,
            'approval_request_id' => $request->id,
            'approval_workflow_step_id' => $workflowStep->id,
            'sequence' => 1,
            'assigned_approver_user_id' => $requester->id,
            'status' => $stepStatus,
            'acted_at' => $status === ApprovalRequestStatus::Submitted ? null : now()->subDay(),
        ]);

        if ($status === ApprovalRequestStatus::Submitted) {
            $request->update(['current_step_id' => $requestStep->id]);
        }

        ApprovalHistory::query()->create([
            'approval_request_id' => $request->id,
            'approval_request_step_id' => $requestStep->id,
            'event' => match ($status) {
                ApprovalRequestStatus::Approved => ApprovalHistoryEvent::Approved,
                ApprovalRequestStatus::Rejected => ApprovalHistoryEvent::Rejected,
                default => ApprovalHistoryEvent::Submitted,
            },
            'actor_id' => $requester->id,
            'description' => 'Pengajuan contoh dibuat saat seeding demo.',
        ]);
    }

    private function seedNotificationTemplates(University $university): void
    {
        NotificationTemplate::query()->updateOrCreate(
            ['university_id' => $university->id, 'event_key' => 'demo.welcome', 'channel' => NotificationChannel::Mail],
            [
                'name' => 'Selamat Datang',
                'subject' => 'Selamat datang di {{university_name}}',
                'body_template' => 'Halo {{name}}, akun Anda di {{university_name}} sudah aktif.',
                'is_active' => true,
            ],
        );
    }

    private function seedFileUploads(University $university, ?User $uploader): void
    {
        if (! $uploader) {
            return;
        }

        $samples = [
            ['name' => 'panduan-akademik.pdf', 'status' => FileUploadStatus::Clean],
            ['name' => 'draft-dokumen.docx', 'status' => FileUploadStatus::Pending],
        ];

        foreach ($samples as $sample) {
            FileUpload::query()->firstOrCreate(
                ['university_id' => $university->id, 'uploaded_by' => $uploader->id, 'original_name' => $sample['name']],
                [
                    'disk' => 'local',
                    'path' => 'uploads/demo/'.\Illuminate\Support\Str::random(20).'-'.$sample['name'],
                    'mime_type' => str_ends_with($sample['name'], '.pdf') ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'extension' => pathinfo($sample['name'], PATHINFO_EXTENSION),
                    'size_bytes' => random_int(50_000, 2_000_000),
                    'status' => $sample['status'],
                    'is_public' => false,
                ],
            );
        }
    }

    private function seedAuditLogEntries(University $university, ?User $actor): void
    {
        AuditLog::query()->firstOrCreate(
            [
                'university_id' => $university->id,
                'auditable_type' => University::class,
                'auditable_id' => $university->id,
                'action' => AuditAction::Updated,
            ],
            [
                'user_id' => $actor?->id,
                'old_values' => ['status' => UniversityStatus::Trial->value],
                'new_values' => ['status' => UniversityStatus::Active->value],
                'reason' => 'Aktivasi awal saat seeding demo.',
            ],
        );
    }
}
