<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Support\KeuPermission;
use Modules\UserManagement\Database\Seeders\OrganizationalRoleSeeder;
use Modules\UserManagement\Database\Seeders\RolePermissionSeeder;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

/*
| Katalog permission KEU, middleware route, dan template peran harus
| konsisten — slug yang salah ketik berarti fitur tidak bisa dipakai siapa pun.
*/

function keuRolePermissions(string $slug): array
{
    return Role::query()->where('slug', $slug)->whereNull('university_id')->firstOrFail()->permissions()->pluck('slug')->all();
}

test('every KEU permission and role template is seeded', function () {
    $this->seed([RolePermissionSeeder::class, OrganizationalRoleSeeder::class]);

    $seeded = Permission::query()->pluck('slug')->all();
    expect(array_diff(KeuPermission::all(), $seeded))->toBe([]);

    foreach (KeuPermission::roles() as $role => $slugs) {
        expect(array_diff($slugs, keuRolePermissions($role)))->toBe([], "Peran {$role} kehilangan izin KEU");
    }

    expect(keuRolePermissions('finance_cashier'))->toContain('payment_transactions.create', 'cashier_sessions.create')
        ->not->toContain('payment_transactions.approve', 'refunds.approve', 'cashier_sessions.approve')
        ->and(keuRolePermissions('student'))->toContain('student_finance.read', 'student_finance.create')
        ->not->toContain('invoices.read', 'payment_transactions.read')
        ->and(keuRolePermissions('finance_manager'))->toContain('refunds.approve', 'billing_runs.approve', 'finance_settings.update')
        // Peran lama tetap memiliki izin pra-KEU-nya.
        ->and(keuRolePermissions('finance_administrator'))->toContain('invoices.read', 'scholarships.read');
});

test('every permission referenced by finance routes exists in the KEU catalog', function () {
    $slugs = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->getActionName(), 'Modules\\Finance\\'))
        ->flatMap(fn ($route) => collect($route->gatherMiddleware())
            ->filter(fn ($m) => is_string($m) && str_starts_with($m, 'permission:'))
            ->flatMap(fn ($m) => explode(',', substr($m, strlen('permission:')))))
        ->unique()->values()->all();

    expect($slugs)->not->toBeEmpty()
        ->and(array_diff($slugs, KeuPermission::all()))->toBe([]);
});
