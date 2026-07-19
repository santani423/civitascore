<?php

namespace Modules\Tenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Tenancy\Enums\UniversityStatus;
use Modules\Tenancy\Models\University;

/**
 * @extends Factory<University>
 */
class UniversityFactory extends Factory
{
    protected $model = University::class;

    public function definition(): array
    {
        $name = fake()->unique()->company().' University';

        return [
            'code' => Str::upper(fake()->unique()->lexify('UNIV-????')),
            'slug' => Str::slug($name),
            'name' => $name,
            'short_name' => fake()->lexify('???'),
            'status' => UniversityStatus::Active,
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'currency' => 'IDR',
            'date_format' => 'd/m/Y',
            'is_active' => true,
            'activated_at' => now(),
        ];
    }
}
