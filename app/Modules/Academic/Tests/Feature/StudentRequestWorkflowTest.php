<?php

use App\Models\User;
use Modules\Academic\Enums\StudentRequestStatus;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\StudentRequest;
use Modules\Academic\Models\StudentStatusHistory;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\Tenancy\Enums\MembershipType;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

/*
| Pengajuan akademik mahasiswa di atas Modul ApprovalWorkflow (pola yang sama
| dengan pengajuan SDM): draft → submitted → approved/rejected. Keputusan bisa
| diambil dari endpoint peninjauan Akademik maupun halaman Persetujuan umum —
| hasilnya identik karena disinkronkan listener SyncStudentRequestDecision.
*/

/**
 * Akun Bagian Akademik: role global `academic_administrator` (penyetuju
 * default alur pengajuan mahasiswa) + approval_requests.read.
 *
 * @param  array<string, mixed>  $world
 */
function requestReviewer(array $world): User
{
    $user = User::factory()->create();
    portalGrant($world, $user, ['approval_requests.read'], MembershipType::Staff);

    $role = Role::query()->firstOrCreate(
        ['slug' => 'academic_administrator', 'university_id' => null],
        ['name' => 'Bagian Akademik', 'description' => 'Role Bagian Akademik.', 'is_system' => true],
    );
    UserRole::query()->create(['user_id' => $user->id, 'role_id' => $role->id, 'university_id' => $world['university']->id, 'assigned_at' => now()]);

    return $user;
}

test('an approved leave request puts the student on leave and records it in the status history', function () {
    $world = portalWorld();
    $reviewer = requestReviewer($world);

    $created = $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/requests', [
        'type' => 'leave', 'title' => 'Cuti semester ganjil', 'description' => 'Alasan kesehatan', 'submit' => true,
    ])->assertApiSuccess(201)
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('data.status_label', 'Sedang Ditinjau');

    $requestId = $created->json('data.id');
    expect(ApprovalRequest::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and($reviewer->notifications()->where('data->event_key', 'student.request_submitted')->exists())->toBeTrue();

    // Mahasiswa tidak bisa memutuskan pengajuannya sendiri.
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student-requests/{$requestId}/approve")->assertApiError(403);

    $this->actingAs($reviewer);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student-requests')
        ->assertApiSuccess()
        ->assertJsonPath('data.0.student.nim', $world['student']->nim)
        ->assertJsonPath('data.0.can_act', true);

    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student-requests/{$requestId}/approve", ['note' => 'Disetujui'])
        ->assertApiSuccess()
        ->assertJsonPath('data.status', 'approved');

    expect($world['student']->fresh()->status)->toBe(StudentStatus::Leave)
        ->and(StudentStatusHistory::query()->withoutGlobalScopes()->where('student_id', $world['student']->id)->sole()->reason)->toBe('Cuti akademik disetujui')
        ->and($world['user']->notifications()->where('data->event_key', 'student.request_decided')->exists())->toBeTrue();

    $this->actingAs($world['user']);
    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/requests/{$requestId}")
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('data.decision_note', 'Disetujui')
        ->assertJsonPath('data.reviewer', $reviewer->name);
});

test('a decision taken from the generic approvals inbox is synced back to the student request', function () {
    $world = portalWorld();
    $reviewer = requestReviewer($world);

    $requestId = $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/requests', [
        'type' => 'letter', 'title' => 'Surat aktif kuliah', 'payload' => ['letter_type' => 'active_student', 'purpose' => 'Beasiswa'], 'submit' => true,
    ])->json('data.id');

    $approval = ApprovalRequest::query()->withoutGlobalScopes()->with('currentStep')->sole();

    $this->actingAs($reviewer);
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/approval-request-steps/{$approval->current_step_id}/reject", ['comment' => 'Lengkapi KTM'])
        ->assertApiSuccess();

    expect(StudentRequest::query()->withoutGlobalScopes()->find($requestId)->status)->toBe(StudentRequestStatus::Rejected);
});

test('an approved letter request can be downloaded as a PDF by its owner only', function () {
    $world = portalWorld();
    $reviewer = requestReviewer($world);

    $requestId = $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/requests', [
        'type' => 'letter', 'title' => 'Surat aktif kuliah', 'payload' => ['letter_type' => 'active_student', 'purpose' => 'Pengajuan beasiswa'], 'submit' => true,
    ])->json('data.id');

    $this->withHeaders(portalHeaders($world))->get("/api/v1/student/requests/{$requestId}/letter", ['Accept' => 'application/json'])->assertApiError(409);

    $this->actingAs($reviewer);
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student-requests/{$requestId}/approve")->assertApiSuccess();

    $this->actingAs($world['user']);
    $pdf = $this->withHeaders(portalHeaders($world))->get("/api/v1/student/requests/{$requestId}/letter");
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/documents')
        ->assertApiSuccess()
        ->assertJsonPath('data.0.type', 'letter');

    // Mahasiswa lain → 404.
    $other = portalOtherStudent($world);
    $otherUser = actingAsUserWithUniversityPermissions($world['university'], portalStudentPermissions());
    portalAsTenant($world['university'], fn () => $other->update(['user_id' => $otherUser->id]));
    $this->withHeaders(portalHeaders($world))->get("/api/v1/student/requests/{$requestId}/letter", ['Accept' => 'application/json'])->assertApiError(404);
    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/requests/{$requestId}")->assertApiError(404);
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/requests/{$requestId}/cancel")->assertApiError(404);
});

test('an approved data change updates the student and the login email', function () {
    $world = portalWorld(['name' => 'Budi', 'email' => 'budi.lama@example.test']);
    $world['user']->update(['email' => 'budi.lama@example.test']);
    $reviewer = requestReviewer($world);

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/requests', [
        'type' => 'data_change', 'title' => 'Perbaikan data', 'payload' => ['changes' => ['nim' => '123']], 'submit' => true,
    ])->assertApiError(422);

    $requestId = $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/requests', [
        'type' => 'data_change', 'title' => 'Perbaikan nama & email', 'submit' => true,
        'payload' => ['changes' => ['name' => 'Budi Santoso', 'email' => 'budi.baru@example.test', 'birth_place' => 'Bandung']],
    ])->assertApiSuccess(201)->json('data.id');

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/profile')
        ->assertJsonPath('data.pending_data_change.id', $requestId)
        ->assertJsonPath('data.personal.name', 'Budi');

    $this->actingAs($reviewer);
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student-requests/{$requestId}/approve")->assertApiSuccess();

    $student = $world['student']->fresh();
    expect($student->name)->toBe('Budi Santoso')
        ->and($student->email)->toBe('budi.baru@example.test')
        ->and($student->birth_place)->toBe('Bandung')
        ->and($world['user']->fresh()->email)->toBe('budi.baru@example.test');
});

test('request rules: reactivation only while on leave, no duplicate request in review, drafts are editable and cancellable', function () {
    $world = portalWorld();
    requestReviewer($world);

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/requests', [
        'type' => 'reactivation', 'title' => 'Aktif kembali', 'submit' => true,
    ])->assertApiError(409);

    $draft = $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/requests', [
        'type' => 'other', 'title' => 'Permohonan dispensasi', 'description' => 'Draft awal',
    ])->assertApiSuccess(201)->assertJsonPath('data.status', 'draft');

    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/student/requests/{$draft->json('data.id')}", ['description' => 'Sudah diperbaiki'])
        ->assertJsonPath('data.description', 'Sudah diperbaiki');

    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/requests/{$draft->json('data.id')}/submit")->assertJsonPath('data.status', 'submitted');
    $this->withHeaders(portalHeaders($world))->putJson("/api/v1/student/requests/{$draft->json('data.id')}", ['description' => 'Ubah setelah kirim'])->assertApiError(409);

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/student/requests', [
        'type' => 'other', 'title' => 'Permohonan kedua', 'submit' => true,
    ])->assertApiError(409);

    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/requests/{$draft->json('data.id')}/cancel")
        ->assertApiSuccess()
        ->assertJsonPath('data.status', 'cancelled');

    // ApprovalRequest-nya di-soft-delete → hilang dari kotak persetujuan mana pun.
    expect(ApprovalRequest::query()->withoutGlobalScopes()->whereNull('deleted_at')->count())->toBe(0);
});
