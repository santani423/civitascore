<?php

use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\HumanResource\Models\WorkUnit;
use Modules\HumanResource\Services\HrReportService;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;

require_once __DIR__.'/../Support/hr_helpers.php';

/*
| Modul SDM #4 — Data Tenaga Kependidikan: profil khusus tendik (kategori,
| penugasan lab/fasilitas, kompetensi), daftar tendik, dan rekap komposisi
| serta rasio tendik terhadap mahasiswa/dosen per fakultas.
*/

beforeEach(function () {
    hrSeedRoles();
    $this->university = University::factory()->create();
    $this->sdm = hrUserWithRole($this->university, 'hr_administrator');
});

test('Bagian SDM adds an education staff member with the tendik profile', function () {
    $response = $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->postJson('/api/v1/hr/employees', [
            'employee_type' => 'staff',
            'name' => 'Rina Laboran',
            'nip' => 'T-0001',
            'employment_status' => 'contract',
            'staff_category' => 'laboratory',
            'assigned_facility' => 'Laboratorium Komputer Dasar',
            'competency_summary' => 'Sertifikasi K3 Laboratorium; CCNA',
        ])
        ->assertApiSuccess(201);

    expect($response->json('data'))
        ->employee_type->toBe('staff')
        ->staff_category->toBe('laboratory')
        ->staff_category_label->toBe('Laboran')
        ->assigned_facility->toBe('Laboratorium Komputer Dasar')
        ->competency_summary->toBe('Sertifikasi K3 Laboratorium; CCNA');
});

test('staff category is required for tendik and supports the extended categories', function () {
    $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->postJson('/api/v1/hr/employees', ['employee_type' => 'staff', 'name' => 'Tanpa Kategori', 'employment_status' => 'permanent'])
        ->assertApiError(422)
        ->assertJsonValidationErrors(['staff_category']);

    foreach (['finance', 'security', 'driver'] as $index => $category) {
        $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
            ->postJson('/api/v1/hr/employees', ['employee_type' => 'staff', 'name' => "Staf {$index}", 'employment_status' => 'permanent', 'staff_category' => $category])
            ->assertApiSuccess(201);
    }
});

test('tendik-only fields are rejected for lecturers', function () {
    $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->postJson('/api/v1/hr/employees', [
            'employee_type' => 'lecturer',
            'name' => 'Dr. Dosen',
            'nidn' => '0011223344',
            'employment_status' => 'permanent',
            'create_account' => false,
            'staff_category' => 'administration',
            'assigned_facility' => 'Lab A',
        ])
        ->assertApiError(422)
        ->assertJsonValidationErrors(['staff_category', 'assigned_facility']);

    $lecturer = hrLecturer($this->university);

    $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->putJson("/api/v1/hr/employees/{$lecturer->id}", ['name' => $lecturer->name, 'employment_status' => 'permanent', 'competency_summary' => 'x'])
        ->assertApiError(422)
        ->assertJsonValidationErrors(['competency_summary']);
});

test('updating a tendik keeps the category unless changed and cannot clear it', function () {
    $staff = hrStaff($this->university, ['staff_category' => 'librarian']);

    $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->putJson("/api/v1/hr/employees/{$staff->id}", ['name' => 'Nama Baru', 'employment_status' => 'permanent', 'assigned_facility' => 'Perpustakaan Pusat'])
        ->assertApiSuccess()
        ->assertJsonPath('data.staff_category', 'librarian')
        ->assertJsonPath('data.assigned_facility', 'Perpustakaan Pusat');

    $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->putJson("/api/v1/hr/employees/{$staff->id}", ['name' => 'Nama Baru', 'employment_status' => 'permanent', 'staff_category' => null])
        ->assertApiError(422)
        ->assertJsonValidationErrors(['staff_category']);
});

test('staff list only shows tendik and filters by category', function () {
    hrStaff($this->university, ['name' => 'Ani Admin', 'staff_category' => 'administration']);
    $laboran = hrStaff($this->university, ['name' => 'Budi Laboran', 'staff_category' => 'laboratory']);
    $lecturer = hrLecturer($this->university);

    $ids = $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->getJson('/api/v1/hr/staff')
        ->assertApiSuccess()
        ->json('data.*.id');
    expect($ids)->toHaveCount(2)->not->toContain($lecturer->id);

    $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->getJson('/api/v1/hr/staff?filter[staff_category]=laboratory')
        ->assertApiSuccess()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $laboran->id);
});

test('staff summary recaps composition per category, unit and employment status', function () {
    $unit = hrInTenant($this->university, fn () => WorkUnit::factory()->create(['university_id' => $this->university->id, 'name' => 'Biro Keuangan']));

    hrStaff($this->university, ['staff_category' => 'finance', 'work_unit_id' => $unit->id, 'employment_status' => 'contract']);
    hrStaff($this->university, ['staff_category' => 'finance', 'work_unit_id' => $unit->id]);
    hrStaff($this->university, ['staff_category' => 'administration']);
    hrStaff($this->university, ['staff_category' => null]);
    hrStaff($this->university, ['staff_category' => 'finance', 'is_active' => false]);
    hrLecturer($this->university);

    $data = $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->getJson('/api/v1/hr/staff/summary')
        ->assertApiSuccess()
        ->json('data');

    expect($data['totals'])->toBe(['total' => 5, 'active' => 4, 'inactive' => 1, 'uncategorized' => 1]);

    $byCategory = collect($data['by_category'])->pluck('total', 'value');
    expect($byCategory['finance'])->toBe(2)
        ->and($byCategory['administration'])->toBe(1)
        ->and($byCategory['driver'])->toBe(0);

    expect(collect($data['by_employment_status'])->pluck('total', 'value')['contract'])->toBe(1);

    expect(collect($data['by_work_unit'])->firstWhere('work_unit_id', $unit->id))->toMatchArray([
        'name' => 'Biro Keuangan',
        'total' => 2,
        'categories' => ['finance' => 2],
    ]);
    expect(collect($data['by_work_unit'])->firstWhere('work_unit_id', null))
        ->total->toBe(2)
        ->name->toBe('Belum ditempatkan');
});

test('staff summary computes tendik ratios per faculty', function () {
    [$faculty, $facultyUnit] = hrInTenant($this->university, function () {
        $faculty = Faculty::factory()->create(['university_id' => $this->university->id, 'name' => 'Fakultas Teknik']);
        $program = StudyProgram::factory()->create(['university_id' => $this->university->id, 'faculty_id' => $faculty->id]);
        $unit = WorkUnit::factory()->create(['university_id' => $this->university->id, 'faculty_id' => $faculty->id]);

        Student::factory()->count(30)->create(['university_id' => $this->university->id, 'study_program_id' => $program->id, 'status' => 'active']);
        Student::factory()->count(5)->create(['university_id' => $this->university->id, 'study_program_id' => $program->id, 'status' => 'graduated']);

        return [$faculty, $unit];
    });

    // Satu tendik lewat faculty_id langsung, satu lewat fakultas unit kerjanya,
    // satu di unit non-fakultas (hanya masuk rasio universitas).
    hrStaff($this->university, ['staff_category' => 'administration', 'faculty_id' => $faculty->id]);
    hrStaff($this->university, ['staff_category' => 'laboratory', 'work_unit_id' => $facultyUnit->id]);
    hrStaff($this->university, ['staff_category' => 'librarian']);
    foreach (range(1, 4) as $_) {
        hrLecturer($this->university, ['faculty_id' => $faculty->id]);
    }

    $ratios = $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->getJson('/api/v1/hr/staff/summary')
        ->assertApiSuccess()
        ->json('data.ratios');

    expect(collect($ratios['by_faculty'])->firstWhere('faculty_id', $faculty->id))->toMatchArray([
        'faculty_name' => 'Fakultas Teknik',
        'staff' => 2,
        'lecturers' => 4,
        'students' => 30,
        'students_per_staff' => 15,
        'lecturers_per_staff' => 2,
    ]);
    expect($ratios['overall'])->toMatchArray([
        'staff' => 3,
        'lecturers' => 4,
        'students' => 30,
        'students_per_staff' => 10,
        'staff_outside_faculty' => 1,
    ]);
});

test('staff summary is tenant-isolated and permission-gated', function () {
    hrStaff($this->university, ['staff_category' => 'administration']);
    hrStaff(University::factory()->create(), ['staff_category' => 'administration']);

    $this->actingAs($this->sdm)->withHeaders(hrHeaders($this->university))
        ->getJson('/api/v1/hr/staff/summary')
        ->assertJsonPath('data.totals.total', 1);

    $staffReader = hrUserWithPermissions($this->university, ['hr_staff.read']);
    $this->actingAs($staffReader)->withHeaders(hrHeaders($this->university))
        ->getJson('/api/v1/hr/staff/summary')
        ->assertApiSuccess();

    $lecturer = hrUserWithRole($this->university, 'lecturer', MembershipType::Lecturer);
    $this->actingAs($lecturer)->withHeaders(hrHeaders($this->university))
        ->getJson('/api/v1/hr/staff/summary')
        ->assertApiError(403);
});

test('staff export honours the category filter', function () {
    hrStaff($this->university, ['name' => 'Ani Admin', 'staff_category' => 'administration']);
    hrStaff($this->university, ['name' => 'Budi Satpam', 'staff_category' => 'security']);

    $report = app(HrReportService::class);
    $rows = hrInTenant($this->university, fn () => $report->build('staff', ['staff_category' => 'security'])['rows']);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['nama'])->toBe('Budi Satpam')
        ->and($rows[0]['kategori'])->toBe('Keamanan');
});
