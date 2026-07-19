<?php

namespace Modules\Tenancy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Enums\SubscriptionStatus;

/**
 * Three demo universities with deliberately different characteristics
 * (size, active modules, subscription tier, branding) so the platform can
 * be exercised under varied tenant conditions. See
 * docs/MULTI-TENANT-ARCHITECTURE.md for what "kondisi data" from each
 * university's original spec is and isn't represented here — academic
 * transaction data (mahasiswa/dosen/KRS/nilai/tagihan row-level detail)
 * is out of scope until those business modules exist; what's seeded here
 * covers every module that actually exists today (RBAC, approvals, files,
 * audit log, notifications, tenant settings).
 *
 * Every university gets one demo account per organizational role (see
 * OrganizationalRoleSeeder) via demoAccounts(), so every role in the system
 * can actually be logged into — not just the four "headline" roles.
 */
class DemoUniversitiesSeeder extends Seeder
{
    /**
     * One demo account per organizational role.
     * [role slug, email local-part, name label, membership type]
     *
     * @var array<int, array{0: string, 1: string, 2: string, 3: MembershipType}>
     */
    private const ROLE_ACCOUNT_TEMPLATES = [
        ['university_owner', 'owner', 'Owner', MembershipType::Owner],
        ['university_administrator', 'admin', 'Admin', MembershipType::Admin],
        ['rector', 'rektor', 'Rektor', MembershipType::Staff],
        ['vice_rector', 'wakilrektor', 'Wakil Rektor', MembershipType::Staff],
        ['dean', 'dekan', 'Dekan', MembershipType::Staff],
        ['head_of_study_program', 'kaprodi', 'Ketua Program Studi', MembershipType::Staff],
        ['academic_administrator', 'akademik', 'Bagian Akademik', MembershipType::Staff],
        ['lecturer', 'dosen', 'Dosen', MembershipType::Lecturer],
        ['academic_advisor', 'dosenpa', 'Dosen Pembimbing Akademik', MembershipType::Lecturer],
        ['student', 'mahasiswa', 'Mahasiswa', MembershipType::Student],
        ['employee', 'pegawai', 'Pegawai', MembershipType::Staff],
        ['finance_administrator', 'keuangan', 'Bagian Keuangan', MembershipType::Staff],
        ['hr_administrator', 'sdm', 'Bagian SDM', MembershipType::Staff],
        ['library_administrator', 'pustakawan', 'Pustakawan', MembershipType::Staff],
        ['auditor', 'auditor', 'Auditor', MembershipType::Auditor],
    ];

    /**
     * @param array<string, string> $localPartOverrides role slug => email local-part override (mis. STIKes pakai "ketua" bukan "rektor")
     * @param array<string, string> $labelOverrides role slug => name label override
     * @return array<int, array{email: string, name: string, role: string, membership: MembershipType}>
     */
    private static function demoAccounts(
        string $domain,
        string $shortName,
        array $localPartOverrides = [],
        array $labelOverrides = [],
    ): array {
        return array_map(
            static function (array $template) use ($domain, $shortName, $localPartOverrides, $labelOverrides) {
                [$role, $localPart, $label] = $template;
                $membership = $template[3];
                $localPart = $localPartOverrides[$role] ?? $localPart;
                $label = $labelOverrides[$role] ?? $label;

                return [
                    'email' => "{$localPart}@{$domain}",
                    'name' => "{$label} {$shortName}",
                    'role' => $role,
                    'membership' => $membership,
                ];
            },
            self::ROLE_ACCOUNT_TEMPLATES,
        );
    }

    public function run(): void
    {
        (new UniversitySeeder([
            'code' => 'UND',
            'name' => 'Universitas Nusantara Digital',
            'short_name' => 'UND',
            'domain' => 'undigital.test',
            'education_institution_type' => 'Universitas',
            'accreditation' => 'A',
            'primary_color' => '#1D4ED8',
            'secondary_color' => '#F59E0B',
            'plan_code' => 'enterprise',
            'subscription_status' => SubscriptionStatus::Active,
            'modules' => [
                'academic', 'admission', 'finance', 'hr', 'payroll', 'library',
                'asset', 'research', 'community_service', 'alumni', 'graduation',
                'mbkm', 'complaint', 'document', 'mobile_app',
            ],
            'feature_flags' => ['file_upload.virus_scan_enabled' => true, 'approval_workflow.delegation_enabled' => true],
            'demo_accounts' => self::demoAccounts('undigital.test', 'UND'),
            'academic_scale' => ['faculties' => 6, 'study_programs' => 18, 'students' => 900, 'lecturers' => 70, 'employees' => 40, 'classes' => 120],
        ]))->run();

        (new UniversitySeeder([
            'code' => 'ITM',
            'name' => 'Institut Teknologi Mandala',
            'short_name' => 'ITM',
            'domain' => 'itmandala.test',
            'education_institution_type' => 'Institut',
            'accreditation' => 'B',
            'primary_color' => '#15803D',
            'secondary_color' => '#71717A',
            'plan_code' => 'professional',
            'subscription_status' => SubscriptionStatus::Active,
            'modules' => ['academic', 'finance', 'library', 'research', 'mbkm', 'document'],
            'feature_flags' => ['file_upload.virus_scan_enabled' => true, 'approval_workflow.delegation_enabled' => false],
            'demo_accounts' => self::demoAccounts('itmandala.test', 'ITM'),
            'academic_scale' => ['faculties' => 3, 'study_programs' => 8, 'students' => 350, 'lecturers' => 30, 'employees' => 18, 'classes' => 50],
        ]))->run();

        (new UniversitySeeder([
            'code' => 'STIKES',
            'name' => 'Sekolah Tinggi Ilmu Kesehatan Sejahtera',
            'short_name' => 'STIKes Sejahtera',
            'domain' => 'stikessejahtera.test',
            'education_institution_type' => 'Sekolah Tinggi',
            'accreditation' => 'B',
            'primary_color' => '#991B1B',
            'secondary_color' => '#FFFFFF',
            'plan_code' => 'trial',
            'subscription_status' => SubscriptionStatus::Trial,
            'modules' => ['academic', 'finance', 'document'],
            'feature_flags' => ['file_upload.virus_scan_enabled' => false, 'approval_workflow.delegation_enabled' => false],
            'demo_accounts' => self::demoAccounts(
                'stikessejahtera.test',
                'STIKes Sejahtera',
                ['rector' => 'ketua'],
                ['rector' => 'Ketua'],
            ),
            'academic_scale' => ['faculties' => 2, 'study_programs' => 3, 'students' => 60, 'lecturers' => 10, 'employees' => 6, 'classes' => 10],
        ]))->run();
    }
}
