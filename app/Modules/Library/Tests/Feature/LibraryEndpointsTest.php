<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\Student;
use Modules\Library\Enums\BookLoanStatus;
use Modules\Library\Models\Book;
use Modules\Library\Models\BookLoan;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

function grantLibraryPermission(User $user, University $university, array $permissionSlugs): void
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

test('books require books.read permission', function () {
    $university = University::factory()->create();
    $user = User::factory()->create();
    grantLibraryPermission($user, $university, []);

    $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/books')->assertApiError(403);
});

test('a permitted user can list and view books with embedded loans', function () {
    $university = University::factory()->create();
    app(TenantContext::class)->setUniversityId($university->id);

    $book = Book::factory()->create(['university_id' => $university->id]);
    $student = Student::factory()->create(['university_id' => $university->id]);
    BookLoan::factory()->create([
        'university_id' => $university->id,
        'book_id' => $book->id,
        'student_id' => $student->id,
        'status' => BookLoanStatus::Terlambat,
        'fine_amount' => 15000,
    ]);

    app(TenantContext::class)->setUniversityId(null);

    $user = User::factory()->create();
    grantLibraryPermission($user, $university, ['books.read']);

    $list = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/books')
        ->assertApiSuccess();
    expect($list->json('data.0.loans_count'))->toBe(1);

    $detail = $this->actingAs($user)->withHeader('X-University-ID', $university->id)
        ->getJson('/api/v1/books/'.$book->id)
        ->assertApiSuccess();
    expect($detail->json('data.loans.0.status'))->toBe('terlambat');
    expect($detail->json('data.loans.0.student_name'))->toBe($student->name);
});
