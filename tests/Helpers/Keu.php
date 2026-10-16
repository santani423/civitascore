<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Testing\TestResponse;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Curriculum;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\InvoiceLineType;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Services\InvoiceWriter;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;

/*
|--------------------------------------------------------------------------
| Fixture Modul KEU (app/Modules/Finance/Tests)
|--------------------------------------------------------------------------
|
| Dibangun di atas portalWorld(): satu universitas + prodi + semester aktif
| + mahasiswa yang login. Petugas keuangan dibuat sebagai user terpisah
| dengan permission tertentu supaya pemisahan tugas (maker-checker) dapat
| diuji. Nama fungsi berawalan `keu`.
|
*/

/**
 * Permission role `student` untuk KEU (KeuPermission::roles()['student']).
 *
 * @return array<int, string>
 */
function keuStudentPermissions(): array
{
    return ['student_finance.read', 'student_finance.create'];
}

/**
 * Dunia portal + izin keuangan mahasiswa untuk user mahasiswa yang login.
 *
 * @return array{university: University, program: StudyProgram, curriculum: Curriculum, term: AcademicTerm, student: Student, user: User}
 */
function keuWorld(array $studentAttributes = []): array
{
    $world = portalWorld($studentAttributes);
    portalGrant($world, $world['user'], keuStudentPermissions(), MembershipType::Student);

    return $world;
}

/**
 * Petugas keuangan (tidak login-kan) dengan permission tertentu.
 *
 * @param  array<string, mixed>  $world
 * @param  array<int, string>  $permissions
 */
function keuStaff(array $world, array $permissions): User
{
    $user = User::factory()->create();
    portalGrant($world, $user, $permissions, MembershipType::Staff);

    return $user;
}

/**
 * Tagihan terbit (lewat InvoiceWriter — jalur yang sama dengan billing run).
 *
 * @param  array<string, mixed>  $world
 */
function keuInvoice(array $world, string $amount = '1000000', ?Student $student = null, ?CarbonImmutable $dueDate = null, BillGroup $group = BillGroup::Registration): Invoice
{
    return portalAsTenant($world['university'], fn () => app(InvoiceWriter::class)->create(
        $student ?? $world['student'],
        $world['term'],
        $group,
        [['line_type' => InvoiceLineType::Charge, 'description' => 'Biaya Pendidikan', 'amount_cents' => (int) round(((float) $amount) * 100)]],
        $dueDate ?? CarbonImmutable::today()->addDays(14),
        null,
    ));
}

/** Kasir: buka sesi kas & catat tunai. */
function keuCashier(array $world): User
{
    return keuStaff($world, ['cashier_sessions.create', 'cashier_sessions.read', 'payment_transactions.create', 'payment_transactions.read']);
}

function keuOpenSession(array $world, User $cashier): string
{
    return test()->actingAs($cashier)->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/finance/cashier-sessions', ['opening_float' => 0])
        ->assertApiSuccess(201)->json('data.id');
}

function keuPayCash(array $world, User $cashier, string $sessionId, string $targetId, int $amount, string $targetType = 'invoice', ?Student $student = null): TestResponse
{
    return test()->actingAs($cashier)->withHeaders(portalHeaders($world))
        ->postJson("/api/v1/finance/cashier-sessions/{$sessionId}/payments", [
            'student_id' => ($student ?? $world['student'])->id, 'target_type' => $targetType, 'target_id' => $targetId, 'amount' => $amount,
        ]);
}

/**
 * Mahasiswa lain yang dapat login ke portal keuangan.
 *
 * @param  array<string, mixed>  $world
 * @return array{student: Student, user: User}
 */
function keuOtherStudentUser(array $world): array
{
    $user = User::factory()->create();
    portalGrant($world, $user, [...portalStudentPermissions(), ...keuStudentPermissions()], MembershipType::Student);

    return ['student' => portalOtherStudent($world, ['user_id' => $user->id]), 'user' => $user];
}
