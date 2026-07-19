<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

const DASHBOARD_ALL_PERMISSIONS = [
    'students.read', 'lecturers.read', 'employees.read',
    'study_programs.read', 'classes.read', 'invoices.read', 'approval_requests.read',
];

/**
 * Grants $user a role (scoped to $university) carrying exactly the given
 * permission slugs, and an active default membership — same pattern as
 * TenantIsolationTest's grantTenantScopedPermissions(), duplicated locally
 * rather than shared across Feature test files (Pest doesn't scope
 * top-level `function` declarations per file).
 */
function grantDashboardPermissions(User $user, University $university, array $permissionSlugs): void
{
    UserUniversity::query()->create([
        'user_id' => $user->id,
        'university_id' => $university->id,
        'membership_type' => MembershipType::Admin,
        'status' => MembershipStatus::Active,
        'joined_at' => now(),
        'is_default' => true,
    ]);

    $role = Role::factory()->create(['university_id' => $university->id]);

    $permissionIds = collect($permissionSlugs)->map(function (string $slug) {
        [$resource, $action] = explode('.', $slug, 2);

        return Permission::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'resource' => $resource, 'action' => PermissionAction::from($action), 'scope' => PermissionScope::Data],
        )->id;
    });

    $role->permissions()->sync($permissionIds);

    UserRole::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'university_id' => $university->id,
        'assigned_at' => now(),
    ]);
}

/**
 * @return array{faculty: Faculty, program: StudyProgram, term: AcademicTerm}
 */
function seedAcademicFixture(University $university): array
{
    app(TenantContext::class)->setUniversityId($university->id);

    $faculty = Faculty::factory()->create(['university_id' => $university->id]);
    $program = StudyProgram::factory()->create(['university_id' => $university->id, 'faculty_id' => $faculty->id]);
    $term = AcademicTerm::factory()->create([
        'university_id' => $university->id,
        'semester' => AcademicSemester::Ganjil,
        'is_current' => true,
    ]);

    Student::factory()->count(3)->create([
        'university_id' => $university->id, 'study_program_id' => $program->id, 'status' => StudentStatus::Active,
    ]);
    Student::factory()->create([
        'university_id' => $university->id, 'study_program_id' => $program->id, 'status' => StudentStatus::Graduated,
    ]);

    Lecturer::factory()->count(2)->create(['university_id' => $university->id, 'faculty_id' => $faculty->id]);
    Employee::factory()->create(['university_id' => $university->id]);

    ClassSection::factory()->count(2)->create([
        'university_id' => $university->id, 'study_program_id' => $program->id, 'academic_term_id' => $term->id, 'is_active' => true,
    ]);

    $students = Student::query()->where('status', StudentStatus::Active)->get();

    Invoice::factory()->create([
        'university_id' => $university->id, 'student_id' => $students[0]->id,
        'amount' => 5_000_000, 'paid_amount' => 5_000_000, 'status' => InvoiceStatus::Paid,
    ]);
    Invoice::factory()->create([
        'university_id' => $university->id, 'student_id' => $students[1]->id,
        'amount' => 5_000_000, 'paid_amount' => 2_000_000, 'status' => InvoiceStatus::Partial,
    ]);
    Invoice::factory()->create([
        'university_id' => $university->id, 'student_id' => $students[2]->id,
        'amount' => 5_000_000, 'paid_amount' => 0, 'status' => InvoiceStatus::Unpaid,
    ]);

    app(TenantContext::class)->setUniversityId(null);

    return ['faculty' => $faculty, 'program' => $program, 'term' => $term];
}

test('dashboard summary and charts match seeded data for the resolved university', function () {
    $university = University::factory()->create();
    seedAcademicFixture($university);

    $user = User::factory()->create();
    grantDashboardPermissions($user, $university, DASHBOARD_ALL_PERMISSIONS);

    $response = $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/dashboard')
        ->assertApiSuccess();

    $response->assertJsonPath('data.summary.total_students', 4)
        ->assertJsonPath('data.summary.active_students', 3)
        ->assertJsonPath('data.summary.total_lecturers', 2)
        ->assertJsonPath('data.summary.total_employees', 1)
        ->assertJsonPath('data.summary.total_study_programs', 1)
        ->assertJsonPath('data.summary.active_classes', 2)
        ->assertJsonPath('data.summary.unpaid_invoices', 2);

    $studentStatus = collect($response->json('data.charts.student_status'))->keyBy('status');
    expect($studentStatus['Aktif']['total'])->toBe(3);
    expect($studentStatus['Lulus']['total'])->toBe(1);
});

test('unpaid_invoices counts partially-paid invoices as not yet lunas', function () {
    $university = University::factory()->create();
    seedAcademicFixture($university);

    $user = User::factory()->create();
    grantDashboardPermissions($user, $university, ['invoices.read']);

    $response = $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/dashboard')
        ->assertApiSuccess();

    // Fixture has 1 paid + 1 partial + 1 unpaid invoice — "belum dibayar" must be 2 (partial + unpaid), not 1.
    expect($response->json('data.summary.unpaid_invoices'))->toBe(2);

    $invoiceStatus = collect($response->json('data.charts.invoice_status'))->keyBy('status');
    expect($invoiceStatus['Dibayar Sebagian']['total'])->toBe(1);
    expect((float) $invoiceStatus['Dibayar Sebagian']['amount'])->toBe(5_000_000.0);
});

test('dashboard data from one university never leaks into another university\'s response', function () {
    $universityA = University::factory()->create();
    $universityB = University::factory()->create();
    seedAcademicFixture($universityA);
    seedAcademicFixture($universityB);

    // Add one more student to B only, so the two universities are distinguishable.
    app(TenantContext::class)->setUniversityId($universityB->id);
    Student::factory()->create([
        'university_id' => $universityB->id,
        'study_program_id' => StudyProgram::query()->first()->id,
        'status' => StudentStatus::Active,
    ]);
    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantDashboardPermissions($user, $universityA, ['students.read']);
    grantDashboardPermissions($user, $universityB, ['students.read']);

    $responseA = $this->actingAs($user)->withHeader('X-University-ID', $universityA->id)->getJson('/api/v1/dashboard');
    $responseB = $this->actingAs($user)->withHeader('X-University-ID', $universityB->id)->getJson('/api/v1/dashboard');

    expect($responseA->json('data.summary.total_students'))->toBe(4);
    expect($responseB->json('data.summary.total_students'))->toBe(5);
});

test('the faculty filter narrows student counts to that faculty only', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $facultyA = Faculty::factory()->create(['university_id' => $university->id]);
    $facultyB = Faculty::factory()->create(['university_id' => $university->id]);
    $programA = StudyProgram::factory()->create(['university_id' => $university->id, 'faculty_id' => $facultyA->id]);
    $programB = StudyProgram::factory()->create(['university_id' => $university->id, 'faculty_id' => $facultyB->id]);

    Student::factory()->count(2)->create(['university_id' => $university->id, 'study_program_id' => $programA->id, 'status' => StudentStatus::Active]);
    Student::factory()->count(5)->create(['university_id' => $university->id, 'study_program_id' => $programB->id, 'status' => StudentStatus::Active]);
    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantDashboardPermissions($user, $university, ['students.read']);

    $response = $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/dashboard?faculty_id='.$facultyA->id)
        ->assertApiSuccess();

    expect($response->json('data.summary.total_students'))->toBe(2);
});

test('a role without a permission never receives that field, even omitted rather than zero', function () {
    $university = University::factory()->create();
    seedAcademicFixture($university);

    $user = User::factory()->create();
    grantDashboardPermissions($user, $university, ['invoices.read']);

    $response = $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/dashboard')
        ->assertApiSuccess();

    $response->assertJsonMissingPath('data.summary.total_students')
        ->assertJsonMissingPath('data.charts.student_growth')
        ->assertJsonPath('data.summary.unpaid_invoices', 2);
});

test('pending_approvals is tenant-wide for a user with approval_requests.read, but only their own without it', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);
    $workflow = ApprovalWorkflow::factory()->create(['university_id' => $university->id]);

    $requester = User::factory()->create();
    $someoneElse = User::factory()->create();

    ApprovalRequest::factory()->create([
        'university_id' => $university->id, 'approval_workflow_id' => $workflow->id,
        'requested_by' => $requester->id, 'status' => ApprovalRequestStatus::Submitted,
    ]);
    ApprovalRequest::factory()->create([
        'university_id' => $university->id, 'approval_workflow_id' => $workflow->id,
        'requested_by' => $someoneElse->id, 'status' => ApprovalRequestStatus::Submitted,
    ]);
    app(TenantContext::class)->setUniversityId(null);

    grantDashboardPermissions($requester, $university, []);
    $ownOnly = $this->actingAs($requester)->withHeader('X-University-ID', $university->id)->getJson('/api/v1/dashboard');
    expect($ownOnly->json('data.summary.pending_approvals'))->toBe(1);

    $overseer = User::factory()->create();
    grantDashboardPermissions($overseer, $university, ['approval_requests.read']);
    $tenantWide = $this->actingAs($overseer)->withHeader('X-University-ID', $university->id)->getJson('/api/v1/dashboard');
    expect($tenantWide->json('data.summary.pending_approvals'))->toBe(2);
});

test('a university with no academic or finance data returns valid zeros, not an error', function () {
    $university = University::factory()->create();

    $user = User::factory()->create();
    grantDashboardPermissions($user, $university, DASHBOARD_ALL_PERMISSIONS);

    $response = $this->actingAs($user)
        ->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/dashboard')
        ->assertApiSuccess();

    $response->assertJsonPath('data.summary.total_students', 0)
        ->assertJsonPath('data.summary.unpaid_invoices', 0)
        ->assertJsonPath('data.charts.student_growth', [])
        ->assertJsonPath('data.charts.student_status', []);
});

test('the dashboard endpoint rejects requests with no resolved university', function () {
    $superAdmin = User::factory()->create();
    $superAdminRole = Role::query()->firstOrCreate(['slug' => 'super_admin', 'university_id' => null], ['name' => 'Super Admin', 'is_system' => true]);
    UserRole::query()->create(['user_id' => $superAdmin->id, 'role_id' => $superAdminRole->id, 'assigned_at' => now()]);

    $this->actingAs($superAdmin)
        ->getJson('/api/v1/dashboard')
        ->assertApiError(422);
});
