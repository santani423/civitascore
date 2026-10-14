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
 * Each role only gets the subset of the existing permission catalog that
 * actually matches its real responsibility today — e.g. `auditor` gets
 * audit_logs.read. `lecturer` now gets grading/attendance write access since
 * those are real, working endpoints (AcademicRecordService). `student` now
 * has the Student→User identity link (`students.user_id`,
 * `StudentUserAccountSeeder`) and gets `exam_participation.*` — a resource
 * deliberately separate from `exam_attempts.*` (dosen/pengawas recording on
 * behalf of *any* KrsItem) so granting it can never let a student touch
 * another student's attempt; ownership is still re-checked at the object
 * level by `ExamParticipationPolicy`/`StudentExamController`. The rest of
 * the Portal Mahasiswa follows the same pattern with its own self-service
 * resources (`student_portal.*`, `krs_self_service.*`, `student_requests.*`,
 * `assignment_submissions.create`) — every one of them is resolved from
 * `$user->student` server-side, so `student` still never receives an admin
 * slug like krs.create/grades.read that would open other students' data.
 * Dosen wali approve KRS through `krs_advising.*`, re-checked against
 * students.academic_advisor_id. `employee`/`lecturer`/`academic_advisor` get only
 * `hr_self_service.*` (own leave & HR requests, scoped by
 * employees.user_id — see Modules\HumanResource SelfServiceController).
 * These demo accounts remain useful to prove RBAC denies admin endpoints to
 * non-admin roles.
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
        'admission_administrator' => 'Administrator PMB',
        'admission_approver' => 'Pejabat Pengesah PMB',
        'admission_verifier' => 'Petugas Verifikasi PMB',
        'admission_selection_committee' => 'Panitia Seleksi PMB',
        'admission_registrar' => 'Petugas Daftar Ulang PMB',
    ];

    /**
     * Matriks role × permission PMB (docs/pmb/08 §2). Kolom "S" (dean/kaprodi)
     * dibatasi lewat user_roles.scope_type/scope_id saat penugasan; kolom "A"
     * (penguji) hanya pmb_scores.create + presensi, dibatasi keanggotaan sesi
     * di ExamService — penguji sengaja TIDAK diberi pmb_applications.read.
     *
     * @var array<int, string>
     */
    private const PMB_ADMIN = [
        'pmb_config.read', 'pmb_config.update', 'pmb_config.publish', 'pmb_fees.update', 'pmb_content.update', 'pmb_program_profiles.update',
        'pmb_applications.read', 'pmb_applications.update', 'pmb_applications.create', 'pmb_sensitive.read', 'pmb_documents.read',
        'pmb_verification.approve', 'pmb_verification.update', 'pmb_verification.finalize', 'pmb_payments.read',
        'pmb_exams.update', 'pmb_exam_attendance.update', 'pmb_selection.finalize', 'pmb_results.read', 'pmb_results.update',
        'pmb_waitlist.update', 'pmb_reregistration.read', 'pmb_reregistration.update', 'pmb_conversion.read', 'pmb_conversion.update',
        'pmb_nim.update', 'pmb_reports.read', 'pmb_reports.export', 'pmb_communication.publish', 'pmb_communication.update', 'pmb_audit.read',
        'approval_requests.read',
    ];

    /** @var array<int, string> */
    private const PMB_SCOPED_ADMIN = ['pmb_config.read', 'pmb_program_profiles.update', 'pmb_applications.read', 'pmb_documents.read', 'pmb_reregistration.read', 'pmb_reports.read', 'pmb_reports.export'];

    /** @var array<int, string> */
    private const PMB_FINANCE = [
        'pmb_config.read', 'pmb_applications.read', 'pmb_payments.read', 'pmb_payments.approve', 'pmb_payments.finalize',
        'pmb_refunds.create', 'pmb_refunds.approve', 'pmb_reregistration.read', 'pmb_reports.read', 'pmb_reports.export', 'pmb_communication.update',
    ];

    /**
     * Baca seluruh Modul SDM (tanpa hak tulis) — untuk pimpinan & auditor.
     *
     * @var array<int, string>
     */
    private const HR_READ = [
        'hr_dashboard.read', 'hr_employees.read', 'hr_lecturers.read', 'hr_staff.read', 'hr_positions.read',
        'hr_transfers.read', 'hr_contracts.read', 'hr_documents.read', 'hr_leave.read', 'hr_requests.read',
        'hr_training.read', 'hr_performance.read', 'hr_reports.read', 'hr_audit.read',
    ];

    /**
     * Layanan mandiri SDM (Pengajuan Saya: cuti, perubahan data, dsb.) —
     * selalu dibatasi ke data pegawai milik akun sendiri.
     *
     * @var array<int, string>
     */
    private const HR_SELF_SERVICE = ['hr_self_service.read', 'hr_self_service.create', 'hr_self_service.update'];

    /**
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        'university_owner' => [
            ...self::HR_READ, 'hr_reports.export', 'hr_employees.export',
            'tenant_profile.read', 'tenant_profile.update',
            'roles.read', 'user_roles.read', 'user_roles.create',
            'audit_logs.read',
            'system_settings.read', 'system_settings.update',
            'feature_flags.read', 'feature_flags.update',
            'approval_workflows.create', 'approval_workflows.read', 'approval_workflows.delete',
            'users.read',
            'students.read', 'lecturers.read', 'employees.read', 'study_programs.read', 'classes.read', 'invoices.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read', 'exams.read', 'question_bank.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
        ],
        'university_administrator' => [
            ...self::HR_READ, 'hr_reports.export', 'hr_employees.export',
            'tenant_profile.read', 'tenant_profile.update',
            'roles.read', 'user_roles.read', 'user_roles.create',
            'audit_logs.read',
            'approval_workflows.read',
            'users.read',
            'students.read', 'lecturers.read', 'employees.read', 'study_programs.read', 'classes.read', 'invoices.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read', 'exams.read', 'question_bank.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
        ],
        'rector' => [
            'hr_dashboard.read', 'hr_reports.read', 'hr_reports.export',
            'audit_logs.read', 'approval_requests.read', 'users.read',
            'students.read', 'lecturers.read', 'employees.read', 'study_programs.read', 'classes.read', 'invoices.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read', 'exams.read', 'question_bank.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
            'pmb_config.read', 'pmb_reports.read',
        ],
        'vice_rector' => [
            'hr_dashboard.read', 'hr_reports.read',
            'approval_requests.read', 'users.read', 'students.read', 'lecturers.read', 'study_programs.read', 'classes.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read', 'exams.read', 'question_bank.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
            'pmb_config.read', 'pmb_reports.read',
        ],
        'dean' => [
            'approval_requests.read', 'users.read', 'students.read', 'lecturers.read', 'study_programs.read', 'classes.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read', 'exams.read', 'question_bank.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
            ...self::PMB_SCOPED_ADMIN,
        ],
        'head_of_study_program' => [
            'approval_requests.read', 'users.read', 'students.read', 'lecturers.read', 'study_programs.read', 'classes.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read', 'exams.read', 'question_bank.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
            ...self::PMB_SCOPED_ADMIN,
        ],
        'academic_administrator' => [
            'approval_requests.read',
            'notification_templates.create', 'notification_templates.read', 'notification_templates.update',
            'file_uploads.read',
            'users.read',
            // lecturers.read: memilih dosen pengampu kelas (Dosen & Jadwal).
            'students.read', 'students.create', 'students.update', 'lecturers.read', 'study_programs.read', 'classes.read', 'classes.update',
            'curriculums.read', 'courses.read', 'courses.update', 'krs.read', 'krs.create', 'krs.update', 'krs.approve',
            'grades.read', 'attendance.read', 'exams.read', 'question_bank.read',
            'course_materials.read', 'course_materials.create', 'course_materials.update', 'course_materials.delete',
            'assignments.read', 'assignments.create', 'assignments.update', 'assignments.delete',
            'assignment_submissions.read', 'assignment_submissions.update',
            'academic_calendar.read', 'academic_calendar.create', 'academic_calendar.update', 'academic_calendar.delete',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read',
            'announcements.read', 'announcements.create', 'announcements.update', 'announcements.delete', 'reports.read',
        ],
        'lecturer' => [
            ...self::HR_SELF_SERVICE,
            'students.read', 'study_programs.read', 'classes.read', 'courses.read',
            'krs.read', 'grades.read', 'grades.create', 'grades.update', 'attendance.read', 'attendance.create', 'attendance.update',
            'exams.read', 'exams.create', 'exams.update', 'exams.delete', 'exams.publish', 'exam_attempts.read', 'exam_attempts.create', 'exam_attempts.update',
            'question_bank.read', 'question_bank.create', 'question_bank.update', 'question_bank.delete',
            'lecturer_profile.read', 'lecturer_profile.update',
            // Perkuliahan & perwalian — object-level: hanya kelas yang ia ampu
            // dan mahasiswa yang ia walikan (ClassSectionAccess / KrsApprovalPolicy).
            'course_materials.read', 'course_materials.create', 'course_materials.update', 'course_materials.delete',
            'assignments.read', 'assignments.create', 'assignments.update', 'assignments.delete',
            'assignment_submissions.read', 'assignment_submissions.update',
            'krs_advising.read', 'krs_advising.approve',
            'academic_calendar.read',
            // Penguji/pengawas PMB: dibatasi sesi yang ditugaskan (ExamService::assertSessionStaff).
            'pmb_scores.create', 'pmb_exam_attendance.update',
        ],
        'finance_administrator' => ['approval_requests.read', 'file_uploads.read', 'invoices.read', 'scholarships.read', ...self::PMB_FINANCE],
        // Bagian SDM: seluruh Modul SDM, plus CRUD menu Dosen (lecturers.*)
        // — LecturerObserver menyinkronkan setiap perubahan dosen ke data
        // pegawainya di Modul SDM, jadi tetap satu identitas.
        'hr_administrator' => [
            'user_roles.read', 'users.read',
            'lecturers.read', 'lecturers.create', 'lecturers.update', 'lecturers.delete',
            ...self::HR_READ,
            'hr_employees.create', 'hr_employees.update', 'hr_employees.delete', 'hr_employees.export',
            'hr_lecturers.create', 'hr_lecturers.update', 'hr_lecturers.delete',
            'hr_staff.create', 'hr_staff.update', 'hr_staff.delete',
            'hr_positions.create', 'hr_positions.update', 'hr_positions.delete',
            'hr_transfers.create', 'hr_transfers.update',
            'hr_contracts.create', 'hr_contracts.update', 'hr_contracts.delete',
            'hr_documents.create', 'hr_documents.update', 'hr_documents.delete',
            'hr_leave.create', 'hr_leave.approve', 'hr_leave.reject',
            'hr_requests.create', 'hr_requests.update', 'hr_requests.approve', 'hr_requests.reject',
            'hr_training.create', 'hr_training.update', 'hr_training.delete',
            'hr_performance.create', 'hr_performance.update', 'hr_performance.delete',
            'hr_reports.export',
        ],
        'library_administrator' => ['file_uploads.read', 'file_uploads.delete', 'books.read'],
        'auditor' => [
            ...self::HR_READ, 'hr_reports.export',
            'audit_logs.read', 'approval_requests.read',
            'students.read', 'lecturers.read', 'employees.read', 'study_programs.read', 'classes.read', 'invoices.read',
            'curriculums.read', 'courses.read', 'krs.read', 'grades.read', 'attendance.read', 'exams.read', 'question_bank.read',
            'scholarships.read', 'theses.read', 'internships.read', 'books.read', 'alumni.read', 'announcements.read', 'reports.read',
            'pmb_config.read', 'pmb_applications.read', 'pmb_sensitive.read', 'pmb_documents.read', 'pmb_payments.read', 'pmb_results.read',
            'pmb_reregistration.read', 'pmb_conversion.read', 'pmb_reports.read', 'pmb_reports.export', 'pmb_audit.read',
        ],
        'student' => [
            'exam_participation.read', 'exam_participation.create', 'exam_participation.update',
            'student_portal.read', 'student_portal.update',
            'krs_self_service.read', 'krs_self_service.create', 'krs_self_service.update',
            'student_requests.read', 'student_requests.create', 'student_requests.update',
            'assignment_submissions.create',
        ],
        // academic_advisor & employee: tanpa permission admin. Keduanya
        // mendapat layanan mandiri SDM (cuti/pengajuan milik sendiri,
        // dibatasi lewat employees.user_id); dosen PA juga menyetujui KRS
        // mahasiswa perwaliannya sendiri (krs_advising.*, dicek terhadap
        // students.academic_advisor_id lewat identity link dosen).
        'academic_advisor' => [...self::HR_SELF_SERVICE, 'krs_advising.read', 'krs_advising.approve'],
        'employee' => self::HR_SELF_SERVICE,
        'admission_administrator' => self::PMB_ADMIN,
        'admission_approver' => ['pmb_config.read', 'pmb_applications.read', 'pmb_results.approve', 'pmb_results.read', 'pmb_reports.read', 'approval_requests.read'],
        'admission_verifier' => [
            'pmb_config.read', 'pmb_applications.read', 'pmb_applications.create', 'pmb_sensitive.read', 'pmb_documents.read',
            'pmb_verification.approve', 'pmb_payments.read', 'pmb_reports.read', 'pmb_communication.update',
        ],
        'admission_selection_committee' => [
            'pmb_config.read', 'pmb_applications.read', 'pmb_documents.read', 'pmb_exams.update', 'pmb_exam_attendance.update',
            'pmb_scores.import', 'pmb_scores.finalize', 'pmb_selection.update', 'pmb_selection.finalize', 'pmb_selection.publish',
            'pmb_results.read', 'pmb_results.update', 'pmb_reports.read', 'pmb_reports.export', 'approval_requests.read',
        ],
        'admission_registrar' => [
            'pmb_config.read', 'pmb_applications.read', 'pmb_sensitive.read', 'pmb_documents.read', 'pmb_payments.read',
            'pmb_reregistration.read', 'pmb_reregistration.approve', 'pmb_conversion.read', 'pmb_reports.read', 'pmb_communication.update',
        ],
    ];

    public function run(): void
    {
        foreach (self::ROLE_LABELS as $slug => $name) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug, 'university_id' => null],
                ['name' => $name, 'description' => "Role {$name}.", 'is_system' => true],
            );

            $permissionIds = Permission::query()
                ->whereIn('slug', self::ROLE_PERMISSIONS[$slug])
                ->pluck('id');

            $role->permissions()->sync($permissionIds);
        }

        // sync() writes pivot rows directly and this seeder runs under
        // DatabaseSeeder's WithoutModelEvents, so RolePermission's model
        // events never fire — flush explicitly (same as RolePermissionSeeder).
        $this->flushPermissionCache();
    }

    /**
     * Membersihkan cache permission hanya ketika cache store
     * yang digunakan mendukung cache tags (file/database tidak mendukung).
     */
    private function flushPermissionCache(): void
    {
        $cache = Cache::store();

        if (! $cache->supportsTags()) {
            return;
        }

        $cache->tags(['permissions'])->flush();
    }
}
