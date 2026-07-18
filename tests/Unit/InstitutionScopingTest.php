<?php

use App\Support\Scoping\BelongsToInstitutionScope;
use App\Support\Scoping\InstitutionContextResolver;
use App\Support\Scoping\ScopesToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proves the institution-scoping hook (built for Phase 2+, unused by any
 * real Phase 1 model) actually works, without polluting production schema
 * with a throwaway model/table — both are defined entirely inside this
 * test file.
 */
beforeEach(function () {
    Schema::create('scoping_demo_items', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('faculty_id')->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('scoping_demo_items');
});

test('BelongsToInstitutionScope restricts queries based on the bound resolver context', function () {
    $model = new class extends Model implements ScopesToInstitution
    {
        protected $table = 'scoping_demo_items';

        protected $fillable = ['name', 'faculty_id'];

        public $timestamps = false;

        public function institutionScopeColumns(): array
        {
            return ['faculty_id'];
        }

        protected static function booted(): void
        {
            self::addGlobalScope(new BelongsToInstitutionScope);
        }
    };

    $model::create(['name' => 'A', 'faculty_id' => 'fk-1']);
    $model::create(['name' => 'B', 'faculty_id' => 'fk-2']);

    app()->bind(InstitutionContextResolver::class, fn () => new class implements InstitutionContextResolver
    {
        public function resolve(): array
        {
            return ['faculty_id' => 'fk-1'];
        }
    });

    $results = $model::query()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('A');
});

test('with the default NullInstitutionContextResolver, nothing is restricted', function () {
    $model = new class extends Model implements ScopesToInstitution
    {
        protected $table = 'scoping_demo_items';

        protected $fillable = ['name', 'faculty_id'];

        public $timestamps = false;

        public function institutionScopeColumns(): array
        {
            return ['faculty_id'];
        }

        protected static function booted(): void
        {
            self::addGlobalScope(new BelongsToInstitutionScope);
        }
    };

    $model::create(['name' => 'A', 'faculty_id' => 'fk-1']);
    $model::create(['name' => 'B', 'faculty_id' => 'fk-2']);

    expect($model::query()->count())->toBe(2);
});
