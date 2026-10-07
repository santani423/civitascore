<?php

use App\Models\User;
use Modules\Announcement\Enums\AnnouncementTargetScope;
use Modules\Announcement\Models\Announcement;
use Modules\Notification\Services\NotificationDispatcher;
use Modules\Notification\Enums\NotificationChannel;

/*
| Feed pengumuman Portal Mahasiswa (sasaran prodi/fakultas/angkatan/semester/
| peran, status sudah dibaca) dan pusat notifikasi milik user sendiri.
*/

/**
 * @param  array<string, mixed>  $world
 * @param  array<string, mixed>  $attributes
 */
function feedAnnouncement(array $world, string $title, array $attributes = []): Announcement
{
    return portalAsTenant($world['university'], fn () => Announcement::factory()->create([
        'university_id' => $world['university']->id,
        'title' => $title,
        'published_at' => now()->subHour(),
        ...$attributes,
    ]));
}

test('the student feed only lists announcements targeted at this student', function () {
    // Angkatan 2024 pada 2026/2027 Ganjil = semester 5.
    $world = portalWorld(['admission_year' => 2024]);
    $otherProgram = portalAsTenant($world['university'], fn () => \Modules\Academic\Models\StudyProgram::factory()->create(['university_id' => $world['university']->id]));

    feedAnnouncement($world, 'Untuk semua');
    feedAnnouncement($world, 'Prodi saya', ['target_scope' => AnnouncementTargetScope::ProgramStudi, 'target_id' => $world['program']->id]);
    feedAnnouncement($world, 'Fakultas saya', ['target_scope' => AnnouncementTargetScope::Fakultas, 'target_id' => $world['program']->faculty_id]);
    feedAnnouncement($world, 'Angkatan 2024', ['target_admission_year' => 2024]);
    feedAnnouncement($world, 'Semester 5', ['target_semester' => 5, 'audience' => 'students']);
    feedAnnouncement($world, 'Prodi lain', ['target_scope' => AnnouncementTargetScope::ProgramStudi, 'target_id' => $otherProgram->id]);
    feedAnnouncement($world, 'Angkatan 2023', ['target_admission_year' => 2023]);
    feedAnnouncement($world, 'Semester 3', ['target_semester' => 3]);
    feedAnnouncement($world, 'Khusus dosen', ['audience' => 'lecturers']);
    feedAnnouncement($world, 'Belum terbit', ['published_at' => now()->addDay()]);

    $response = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/announcements?per_page=50')->assertApiSuccess();

    expect(collect($response->json('data'))->pluck('title')->sort()->values()->all())
        ->toBe(['Angkatan 2024', 'Fakultas saya', 'Prodi saya', 'Semester 5', 'Untuk semua'])
        ->and($response->json('meta.unread_count'))->toBe(5);

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/announcements?filter=study_program')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Prodi saya');
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/announcements?filter=students')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Semester 5');
});

test('reading an announcement marks it read and announcements for others are not accessible', function () {
    $world = portalWorld();
    $mine = feedAnnouncement($world, 'Jadwal UTS');
    $otherProgram = portalAsTenant($world['university'], fn () => \Modules\Academic\Models\StudyProgram::factory()->create(['university_id' => $world['university']->id]));
    $notMine = feedAnnouncement($world, 'Prodi lain', ['target_scope' => AnnouncementTargetScope::ProgramStudi, 'target_id' => $otherProgram->id]);

    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/announcements/{$mine->id}")
        ->assertApiSuccess()
        ->assertJsonPath('data.is_read', false);

    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/announcements/{$mine->id}/read")
        ->assertApiSuccess()
        ->assertJsonPath('data.unread_count', 0);

    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/announcements?filter=unread')->assertJsonCount(0, 'data');
    $this->withHeaders(portalHeaders($world))->getJson("/api/v1/student/announcements/{$notMine->id}")->assertApiError(404);
    $this->withHeaders(portalHeaders($world))->postJson("/api/v1/student/announcements/{$notMine->id}/read")->assertApiError(404);

    // Feed admin tetap butuh announcements.read — mahasiswa tidak punya.
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/announcements')->assertApiError(403);
});

test('the academic office publishes a targeted announcement', function () {
    $world = portalWorld();
    actingAsUserWithUniversityPermissions($world['university'], ['announcements.create', 'announcements.read']);

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/announcements', [
        'title' => 'Pembayaran UKT', 'body' => 'Batas pembayaran 30 Oktober.',
        'target_scope' => 'program_studi', 'target_id' => $world['program']->id, 'audience' => 'students', 'target_admission_year' => 2024,
    ])->assertApiSuccess(201)
        ->assertJsonPath('data.audience', 'students')
        ->assertJsonPath('data.target_admission_year', 2024);

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/announcements', [
        'title' => 'Target asing', 'body' => 'x', 'target_scope' => 'program_studi', 'target_id' => '01JXXXXXXXXXXXXXXXXXXXXXXX',
    ])->assertApiError(422);

    $this->actingAs($world['user']);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/student/announcements')->assertJsonPath('data.0.title', 'Pembayaran UKT');
});

test('the notification center lists, counts and marks only the user\'s own notifications', function () {
    $world = portalWorld();
    $other = User::factory()->create();
    $dispatcher = app(NotificationDispatcher::class);

    portalAsTenant($world['university'], function () use ($dispatcher, $world, $other): void {
        app(\Modules\Academic\Services\StudentNotificationService::class)->ensureTemplates();
        $dispatcher->dispatch($world['user'], 'student.krs_approved', [NotificationChannel::Database], ['term' => '2026/2027 Ganjil'], ['link' => '/portal/jadwal-kuliah']);
        $dispatcher->dispatch($world['user'], 'student.grade_published', [NotificationChannel::Database], ['course' => 'Basis Data', 'term' => '2026/2027 Ganjil', 'grade' => 'A']);
        $dispatcher->dispatch($other, 'student.krs_approved', [NotificationChannel::Database], ['term' => '2026/2027 Ganjil']);
    });

    $list = $this->withHeaders(portalHeaders($world))->getJson('/api/v1/notifications')->assertApiSuccess();
    expect($list->json('data'))->toHaveCount(2)
        ->and($list->json('meta.unread_count'))->toBe(2);

    $approved = collect($list->json('data'))->firstWhere('event_key', 'student.krs_approved');
    expect($approved['message'])->toBe('KRS 2026/2027 Ganjil Anda telah disetujui. Jadwal kuliah sudah dapat dilihat.')
        ->and($approved['link'])->toBe('/portal/jadwal-kuliah');

    $this->withHeaders(portalHeaders($world))->patchJson("/api/v1/notifications/{$approved['id']}/read")
        ->assertApiSuccess()
        ->assertJsonPath('data.unread_count', 1);

    $othersId = $other->notifications()->value('id');
    $this->withHeaders(portalHeaders($world))->patchJson("/api/v1/notifications/{$othersId}/read")->assertApiError(404);
    expect($other->unreadNotifications()->count())->toBe(1);

    $this->withHeaders(portalHeaders($world))->postJson('/api/v1/notifications/read-all')->assertJsonPath('data.unread_count', 0);
    $this->withHeaders(portalHeaders($world))->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.unread_count', 0);
});
