<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit', '../app/Modules/*/Tests/Feature', '../app/Modules/*/Tests/Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| API Response Envelope Assertions
|--------------------------------------------------------------------------
|
| Every controller in this app returns the same {success, message, data,
| meta} / {success, message, errors} JSON envelope (App\Support\Http\
| ApiResponse) — these helpers assert that shape consistently across every
| Feature test instead of re-checking raw JSON keys in each one.
|
*/

TestResponse::macro('assertApiSuccess', function (int $status = 200) {
    /** @var TestResponse $this */
    return $this->assertStatus($status)->assertJson(['success' => true]);
});

TestResponse::macro('assertApiError', function (int $status) {
    /** @var TestResponse $this */
    return $this->assertStatus($status)->assertJson(['success' => false]);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create and authenticate a user holding exactly the given permission
 * slugs (e.g. "roles.read"), via a throwaway role — used across Feature
 * tests to assert RBAC enforcement without repeating the role/permission
 * wiring in every test file.
 *
 * @param  array<int, string>  $permissionSlugs
 */
function actingAsUserWithPermissions(array $permissionSlugs = []): User
{
    $user = User::factory()->create();

    if ($permissionSlugs !== []) {
        $role = Role::factory()->create();

        $permissions = collect($permissionSlugs)->map(function (string $slug): Permission {
            [$resource, $action] = explode('.', $slug, 2);

            return Permission::factory()->create([
                'slug' => $slug,
                'resource' => $resource,
                'action' => PermissionAction::from($action),
            ]);
        });

        $role->permissions()->sync($permissions->pluck('id')->all());
        $user->roles()->attach($role->id, ['assigned_at' => now()]);
    }

    test()->actingAs($user);

    return $user;
}
