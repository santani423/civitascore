<?php

use Carbon\CarbonImmutable;
use Modules\Academic\Enums\AttendanceStatus;
use Modules\Academic\Models\Attendance;
use Modules\Academic\Models\AttendanceSession;

afterEach(fn () => CarbonImmutable::setTestNow());

/** Dosen membuka sesi untuk pertemuan 1 hari ini, lalu mengembalikan id sesi dan payload QR-nya. */
function openCheckInAsLecturer(array $world, $classSection): array
{
    actingAsUserWithUniversityPermissions($world['university'], ['attendance.create', 'attendance.update']);

    $session = test()->withHeaders(portalHeaders($world))
        ->postJson("/api/v1/class-sections/{$classSection->id}/attendance-sessions", [
            'meeting_number' => 1,
            'meeting_date' => now()->toDateString(),
        ])
        ->assertApiSuccess(201)
        ->json('data');

    $payload = test()->withHeaders(portalHeaders($world))
        ->getJson("/api/v1/attendance-sessions/{$session['id']}/qr")
        ->assertApiSuccess()
        ->json('data.payload');

    return [$session, $payload];
}

test('an enrolled student records their own presence by scanning the lecturer QR', function () {
    $world = portalWorld();
    $classSection = portalClass($world, portalCourse($world), [[1, '08:00', '10:00']]);
    portalEnroll($world, $world['student'], $classSection);
    [$session, $payload] = openCheckInAsLecturer($world, $classSection);

    expect($payload)->toStartWith($session['id'].'.');

    $this->actingAs($world['user']);

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/attendance/qr-scan', ['payload' => $payload])
        ->assertApiSuccess(201)
        ->assertJsonPath('data.status', 'present')
        ->assertJsonPath('data.meeting_number', 1)
        ->assertJsonPath('data.already_recorded', false);

    $attendance = Attendance::query()->sole();

    expect($attendance->status)->toBe(AttendanceStatus::Present)
        ->and($attendance->method)->toBe('qr');

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/attendance/qr-scan', ['payload' => $payload])
        ->assertApiSuccess()
        ->assertJsonPath('meta.code', 'ALREADY_RECORDED')
        ->assertJsonPath('data.already_recorded', true);

    expect(Attendance::query()->count())->toBe(1);
});

test('a QR code from a slot two intervals ago is rejected while the previous slot is still accepted', function () {
    $base = CarbonImmutable::now()->startOfMinute();
    CarbonImmutable::setTestNow($base->addSeconds(5));

    $world = portalWorld();
    $classSection = portalClass($world, portalCourse($world), [[1, '08:00', '10:00']]);
    portalEnroll($world, $world['student'], $classSection);
    [, $payload] = openCheckInAsLecturer($world, $classSection);

    $this->actingAs($world['user']);

    CarbonImmutable::setTestNow($base->addSeconds(35));
    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/attendance/qr-scan', ['payload' => $payload])
        ->assertApiSuccess(201);

    CarbonImmutable::setTestNow($base->addSeconds(65));
    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/attendance/qr-scan', ['payload' => $payload])
        ->assertApiError(422)
        ->assertJsonValidationErrors('payload');
});

test('a tampered or malformed QR payload is rejected', function () {
    $world = portalWorld();
    $classSection = portalClass($world, portalCourse($world), [[1, '08:00', '10:00']]);
    portalEnroll($world, $world['student'], $classSection);
    [$session, $payload] = openCheckInAsLecturer($world, $classSection);

    $this->actingAs($world['user']);

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/attendance/qr-scan', ['payload' => $session['id'].'.'.str_repeat('0', 32)])
        ->assertApiError(422)
        ->assertJsonValidationErrors('payload');

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/attendance/qr-scan', ['payload' => 'bukan-qr'])
        ->assertApiError(422)
        ->assertJsonValidationErrors('payload');

    expect(Attendance::query()->count())->toBe(0);
});

test('a closed or expired session refuses check-ins', function () {
    $world = portalWorld();
    $classSection = portalClass($world, portalCourse($world), [[1, '08:00', '10:00']]);
    portalEnroll($world, $world['student'], $classSection);
    [$session, $payload] = openCheckInAsLecturer($world, $classSection);

    actingAsUserWithUniversityPermissions($world['university'], ['attendance.create', 'attendance.update']);
    $this->withHeaders(portalHeaders($world))
        ->postJson("/api/v1/attendance-sessions/{$session['id']}/close")
        ->assertApiSuccess();

    $this->actingAs($world['user']);
    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/attendance/qr-scan', ['payload' => $payload])
        ->assertApiError(409);

    AttendanceSession::query()->update(['closed_at' => null, 'expires_at' => now()->subMinute()]);
    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/attendance/qr-scan', ['payload' => $payload])
        ->assertApiError(409);

    expect(Attendance::query()->count())->toBe(0);
});

test('a student who is not enrolled in the class cannot record presence', function () {
    $world = portalWorld();
    $classSection = portalClass($world, portalCourse($world), [[1, '08:00', '10:00']]);
    [, $payload] = openCheckInAsLecturer($world, $classSection);

    $peerUser = actingAsUserWithUniversityPermissions($world['university'], ['student_portal.update']);
    portalAsTenant($world['university'], fn () => portalOtherStudent($world, ['user_id' => $peerUser->id]));

    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/attendance/qr-scan', ['payload' => $payload])
        ->assertApiError(403);

    expect(Attendance::query()->count())->toBe(0);
});

test('a check-in does not overwrite a status the lecturer already recorded', function () {
    $world = portalWorld();
    $classSection = portalClass($world, portalCourse($world), [[1, '08:00', '10:00']]);
    $krsItem = portalEnroll($world, $world['student'], $classSection);
    [, $payload] = openCheckInAsLecturer($world, $classSection);

    portalAsTenant($world['university'], fn () => Attendance::query()->create([
        'university_id' => $world['university']->id,
        'krs_item_id' => $krsItem->id,
        'meeting_number' => 1,
        'meeting_date' => now()->toDateString(),
        'status' => AttendanceStatus::Sick,
    ]));

    $this->actingAs($world['user']);
    $this->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/attendance/qr-scan', ['payload' => $payload])
        ->assertApiError(409);

    expect(Attendance::query()->sole()->status)->toBe(AttendanceStatus::Sick);
});

test('a lecturer cannot open a second running session for the same meeting', function () {
    $world = portalWorld();
    $classSection = portalClass($world, portalCourse($world), [[1, '08:00', '10:00']]);
    openCheckInAsLecturer($world, $classSection);

    $this->withHeaders(portalHeaders($world))
        ->postJson("/api/v1/class-sections/{$classSection->id}/attendance-sessions", [
            'meeting_number' => 1,
            'meeting_date' => now()->toDateString(),
        ])
        ->assertApiError(409);
});
