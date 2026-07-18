<?php

use App\Support\Http\ListQuery;
use Illuminate\Http\Request;
use Modules\UserManagement\Models\Role;

test('an oversized per_page request is clamped to the maximum, not rejected', function () {
    Role::factory()->count(5)->create();

    $request = Request::create('/roles', 'GET', ['per_page' => 500]);

    $paginator = ListQuery::paginate(Role::query(), $request);

    expect($paginator->perPage())->toBe(100);
});

test('search only matches against explicitly allowed columns', function () {
    Role::factory()->create(['name' => 'Findable Role']);
    Role::factory()->create(['name' => 'Other Role']);

    $request = Request::create('/roles', 'GET', ['search' => 'Findable']);

    $paginator = ListQuery::paginate(Role::query(), $request, searchable: ['name']);

    expect($paginator->total())->toBe(1);
});

test('sort only applies to explicitly allowed columns', function () {
    Role::factory()->create(['name' => 'Z Role']);
    Role::factory()->create(['name' => 'A Role']);

    $request = Request::create('/roles', 'GET', ['sort' => 'name']);

    $paginator = ListQuery::paginate(Role::query(), $request, sortable: ['name']);

    expect($paginator->first()->name)->toBe('A Role');
});
