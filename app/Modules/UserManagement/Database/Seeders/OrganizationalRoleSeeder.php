<?php

namespace Modules\UserManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

/**
 * Global role templates (university_id null) matching the organizational
 * roles every tenant needs — assigned to users per-university via
 * user_roles.university_id, not duplicated per tenant.
 *
 * Business-domain permissions (grading, billing, ...) don't exist yet (no
 * academic modules built), so each role only gets the subset of the
 * existing admin-panel permission catalog that actually matches its real
 * responsibility today — e.g. `auditor` gets audit_logs.read, but `lecturer`
 * and `student` intentionally get none, since the panel has nothing in
 * their domain yet. That's a correct reflection of current access, not a
 * placeholder: their demo accounts exist to prove RBAC denies admin
 * endpoints to non-admin roles, not to grant them premature access.
 */
class OrganizationalRoleSeeder extends Seeder
{
    private const ROLE_LABELS = [
        'university_owner' => 'University Owner',
        'university_administrator' => 'University Administrator',
        'rector' => 'Rektor',
        'vice_rector' => 'Wakil Rektor',
        'dean' => 'Dekan',
        'head_of_study_program' => 'Ketua Program Studi',
        'academic_administrator' => 'Bagian Akademik',
        'lecturer' => 'Dosen',
        'academic_advisor' => 'Dosen Pembimbing Akademik',
        'student' => 'Mahasiswa',
        'employee' => 'Pegawai',
        'finance_administrator' => 'Bagian Keuangan',
        'hr_administrator' => 'Bagian SDM',
        'library_administrator' => 'Pustakawan',
        'auditor' => 'Auditor',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        'university_owner' => [
            'tenant_profile.read', 'tenant_profile.update',
            'roles.read', 'user_roles.read', 'user_roles.create',
            'audit_logs.read',
            'system_settings.read', 'system_settings.update',
            'feature_flags.read', 'feature_flags.update',
            'approval_workflows.create', 'approval_workflows.read', 'approval_workflows.delete',
            'users.read',
            'students.read', 'lecturers.read', 'employees.read', 'study_programs.read', 'classes.read', 'invoices.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
        ],
        'university_administrator' => [
            'tenant_profile.read', 'tenant_profile.update',
            'roles.read', 'user_roles.read', 'user_roles.create',
            'audit_logs.read',
            'approval_workflows.read',
            'users.read',
            'students.read', 'lecturers.read', 'employees.read', 'study_programs.read', 'classes.read', 'invoices.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
        ],
        'rector' => [
            'audit_logs.read', 'approval_requests.read', 'users.read',
            'students.read', 'lecturers.read', 'employees.read', 'study_programs.read', 'classes.read', 'invoices.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
        ],
        'vice_rector' => [
            'approval_requests.read', 'users.read', 'students.read', 'lecturers.read', 'study_programs.read', 'classes.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
        ],
        'dean' => [
            'approval_requests.read', 'users.read', 'students.read', 'lecturers.read', 'study_programs.read', 'classes.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
        ],
        'head_of_study_program' => [
            'approval_requests.read', 'users.read', 'students.read', 'lecturers.read', 'study_programs.read', 'classes.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
        ],
        'academic_administrator' => [
            'approval_requests.read',
            'notification_templates.create', 'notification_templates.read', 'notification_templates.update',
            'file_uploads.read',
            'users.read',
            'students.read', 'study_programs.read', 'classes.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
        ],
        'finance_administrator' => ['approval_requests.read', 'file_uploads.read', 'invoices.read', 'scholarships.read'],
        'hr_administrator' => ['user_roles.read', 'users.read', 'employees.read', 'lecturers.read'],
        'library_administrator' => ['file_uploads.read', 'file_uploads.delete', 'books.read'],
        'auditor' => [
            'audit_logs.read', 'approval_requests.read',
            'students.read', 'lecturers.read', 'employees.read', 'study_programs.read', 'classes.read', 'invoices.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
        ],
        // lecturer, academic_advisor, student, employee: sengaja tanpa
        // permission admin — belum ada modul akademik/kepegawaian yang jadi
        // domain izin mereka.
    ];

    public function run(): void
    {
        foreach (self::ROLE_LABELS as $slug => $name) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug, 'university_id' => null],
                ['name' => $name, 'description' => "Role {$name}.", 'is_system' => true],
            );

            $permissionIds = Permission::query()
                ->whereIn('slug', self::ROLE_PERMISSIONS[$slug] ?? [])
                ->pluck('id');

            $role->permissions()->sync($permissionIds);
        }

        // sync() writes pivot rows directly and this seeder runs under
        // DatabaseSeeder's WithoutModelEvents, so RolePermission's model
        // events never fire — flush explicitly (same as RolePermissionSeeder).
        Cache::tags(['permissions'])->flush();
    }
}
