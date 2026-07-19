<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

function grantFinancePermission(User $user, University $university, array $permissionSlugs): void
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

test('invoices and payments require the invoices.read permission', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantFinancePermission($user, $university, []);

    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/invoices')->assertApiError(403);
    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/payments')->assertApiError(403);
});

test('the unpaid_invoices dashboard card total matches the invoice list total for the same combined filter', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $program = StudyProgram::factory()->create(['university_id' => $university->id]);
    $students = Student::factory()->count(3)->create(['university_id' => $university->id, 'study_program_id' => $program->id]);

    $paidInvoice = Invoice::factory()->create(['university_id' => $university->id, 'student_id' => $students[0]->id, 'status' => InvoiceStatus::Paid]);
    Invoice::factory()->create(['university_id' => $university->id, 'student_id' => $students[1]->id, 'status' => InvoiceStatus::Partial]);
    Invoice::factory()->create(['university_id' => $university->id, 'student_id' => $students[2]->id, 'status' => InvoiceStatus::Unpaid]);

    Payment::factory()->create(['university_id' => $university->id, 'invoice_id' => $paidInvoice->id]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantFinancePermission($user, $university, ['invoices.read']);

    $dashboard = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/dashboard')
        ->assertApiSuccess();
    $unpaidFromDashboard = $dashboard->json('data.summary.unpaid_invoices');

    $invoiceList = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/invoices?filter[status]=unpaid,partial')
        ->assertApiSuccess();

    expect($unpaidFromDashboard)->toBe(2);
    expect($invoiceList->json('meta.total'))->toBe($unpaidFromDashboard);

    $invoiceDetail = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson("/api/v1/invoices/{$paidInvoice->id}")
        ->assertApiSuccess();
    expect($invoiceDetail->json('data.payments'))->toHaveCount(1);

    $payments = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/payments')
        ->assertApiSuccess();
    expect($payments->json('meta.total'))->toBe(1);
});
