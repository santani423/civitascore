<?php

use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;

require_once __DIR__.'/../Support/hr_helpers.php';

/*
| Matriks otorisasi Modul SDM memakai role organisasi sungguhan dari
| OrganizationalRoleSeeder: SDM penuh, Admin baca saja, Dosen & Mahasiswa
| tidak bisa membuka halaman SDM sama sekali.
*/

beforeEach(function () {
    hrSeedRoles();
    $this->university = University::factory()->create();
});

test('SDM endpoints require authentication', function () {
    $this->getJson('/api/v1/hr/employees')->assertApiError(401);
    $this->getJson('/api/v1/me/hr/profile')->assertApiError(401);
});

test('Bagian SDM can open every SDM module', function () {
    $sdm = hrUserWithRole($this->university, 'hr_administrator');

    foreach ([
        'hr/dashboard', 'hr/notifications', 'hr/options', 'hr/employees', 'hr/lecturers', 'hr/staff',
        'hr/employment-statuses', 'hr/work-units', 'hr/positions', 'hr/ranks', 'hr/position-histories',
        'hr/rank-histories', 'hr/academic-ranks', 'hr/educations', 'hr/contracts', 'hr/documents',
        'hr/trainings', 'hr/certifications', 'hr/performance-reviews', 'hr/transfers', 'hr/leave-requests',
        'hr/requests', 'hr/approvals', 'hr/reports', 'hr/reports/employees', 'hr/audit-logs',
    ] as $path) {
        $this->actingAs($sdm)->withHeaders(hrHeaders($this->university))
            ->getJson("/api/v1/{$path}")
            ->assertStatus(200);
    }
});

test('Bagian SDM is limited to SDM — academic admin data is not exposed', function () {
    $sdm = hrUserWithRole($this->university, 'hr_administrator');

    foreach (['students', 'invoices', 'krs-items', 'grades'] as $path) {
        $this->actingAs($sdm)->withHeaders(hrHeaders($this->university))
            ->getJson("/api/v1/{$path}")
            ->assertApiError(403);
    }
});

test('university administrator can read SDM data but cannot change it', function () {
    $admin = hrUserWithRole($this->university, 'university_administrator', MembershipType::Admin);
    $employee = hrStaff($this->university);

    $this->actingAs($admin)->withHeaders(hrHeaders($this->university))
        ->getJson('/api/v1/hr/employees')->assertApiSuccess();
    $this->actingAs($admin)->withHeaders(hrHeaders($this->university))
        ->getJson("/api/v1/hr/employees/{$employee->id}")->assertApiSuccess();

    $this->actingAs($admin)->withHeaders(hrHeaders($this->university))
        ->postJson('/api/v1/hr/employees', ['employee_type' => 'staff', 'name' => 'X', 'employment_status' => 'permanent'])
        ->assertApiError(403);
    $this->actingAs($admin)->withHeaders(hrHeaders($this->university))
        ->deleteJson("/api/v1/hr/employees/{$employee->id}")
        ->assertApiError(403);
});

test('lecturer (Dosen) cannot open SDM pages', function () {
    $lecturer = hrUserWithRole($this->university, 'lecturer', MembershipType::Lecturer);

    foreach (['hr/dashboard', 'hr/employees', 'hr/lecturers', 'hr/leave-requests', 'hr/reports', 'hr/audit-logs'] as $path) {
        $this->actingAs($lecturer)->withHeaders(hrHeaders($this->university))
            ->getJson("/api/v1/{$path}")
            ->assertApiError(403);
    }
});

test('student (Mahasiswa) cannot open SDM pages nor HR self-service', function () {
    $student = hrUserWithRole($this->university, 'student', MembershipType::Student);

    foreach (['hr/dashboard', 'hr/employees', 'me/hr/profile', 'me/hr/leave-requests'] as $path) {
        $this->actingAs($student)->withHeaders(hrHeaders($this->university))
            ->getJson("/api/v1/{$path}")
            ->assertApiError(403);
    }
});

test('a lecturer account not yet linked to an employee record gets 404 on self-service', function () {
    $lecturer = hrUserWithRole($this->university, 'lecturer', MembershipType::Lecturer);

    $this->actingAs($lecturer)->withHeaders(hrHeaders($this->university))
        ->getJson('/api/v1/me/hr/profile')
        ->assertApiError(404);
});

test('SDM routes require an active membership in the requested university', function () {
    $sdm = hrUserWithRole($this->university, 'hr_administrator');
    $otherUniversity = University::factory()->create();

    $this->actingAs($sdm)->withHeaders(hrHeaders($otherUniversity))
        ->getJson('/api/v1/hr/employees')
        ->assertApiError(403);
});

test('an employee from another university reads as not found', function () {
    $sdm = hrUserWithRole($this->university, 'hr_administrator');
    $otherUniversity = University::factory()->create();
    $foreign = hrStaff($otherUniversity);

    $this->actingAs($sdm)->withHeaders(hrHeaders($this->university))
        ->getJson("/api/v1/hr/employees/{$foreign->id}")
        ->assertApiError(404);

    $listed = $this->actingAs($sdm)->withHeaders(hrHeaders($this->university))
        ->getJson('/api/v1/hr/employees')
        ->json('data.*.id');
    expect($listed)->not->toContain($foreign->id);
});

test('a custom role with staff-only rights cannot create a lecturer', function () {
    $staffAdmin = hrUserWithPermissions($this->university, ['hr_employees.read', 'hr_employees.create', 'hr_staff.read', 'hr_staff.create']);

    $this->actingAs($staffAdmin)->withHeaders(hrHeaders($this->university))
        ->postJson('/api/v1/hr/employees', ['employee_type' => 'lecturer', 'name' => 'Dr. X', 'nidn' => '998877', 'employment_status' => 'permanent'])
        ->assertApiError(403);

    $this->actingAs($staffAdmin)->withHeaders(hrHeaders($this->university))
        ->postJson('/api/v1/hr/employees', ['employee_type' => 'staff', 'name' => 'Staf Baru', 'employment_status' => 'permanent', 'staff_category' => 'administration'])
        ->assertApiSuccess(201);
});
