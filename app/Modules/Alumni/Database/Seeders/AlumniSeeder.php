<?php

namespace Modules\Alumni\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Student;
use Modules\Alumni\Models\Alumni;
use Modules\Tenancy\Models\University;

/**
 * Derives one alumni row for every already-seeded Student whose status is
 * Graduated — a direct 1:1 derivation of existing data, not a random
 * sample, so every graduated student gets exactly one alumni profile.
 *
 * Idempotent: if this university already has alumni rows, skips
 * regeneration entirely — safe to re-run `php artisan db:seed`.
 */
class AlumniSeeder extends Seeder
{
    /** @var array<string, int> employment-status-slug => weight out of 100 */
    private const EMPLOYMENT_STATUS_WEIGHTS = [
        'bekerja' => 60,
        'wirausaha' => 10,
        'melanjutkan_studi' => 10,
        'mencari_kerja' => 10,
        'belum_bekerja' => 10,
    ];

    /**
     * @param  Collection<int, Student>  $students
     */
    public function run(University $university, Collection $students): void
    {
        if (Alumni::query()->count() > 0) {
            return;
        }

        $graduates = $students->filter(fn (Student $student) => $student->status === StudentStatus::Graduated);

        if ($graduates->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($graduates as $student) {
            $employmentStatus = $this->weightedPick(self::EMPLOYMENT_STATUS_WEIGHTS);
            $isEmployedOrEntrepreneur = in_array($employmentStatus, ['bekerja', 'wirausaha'], true);

            $rows[] = [
                'id' => (string) Str::ulid(),
                'university_id' => $university->id,
                'student_id' => $student->id,
                'graduation_year' => $student->graduated_at?->year ?? ($student->admission_year + 4),
                'employment_status' => $employmentStatus,
                'company_name' => $isEmployedOrEntrepreneur ? fake()->company() : null,
                'job_title' => $isEmployedOrEntrepreneur ? fake()->jobTitle() : null,
                'waiting_period_months' => $employmentStatus === 'bekerja' ? random_int(1, 12) : null,
                'is_verified' => random_int(1, 100) <= 70,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('alumni')->insert($chunk);
        }
    }

    /**
     * @param  array<int|string, int>  $weights
     */
    private function weightedPick(array $weights): int|string
    {
        $total = array_sum($weights);
        $random = random_int(1, $total);
        $cumulative = 0;

        foreach ($weights as $key => $weight) {
            $cumulative += $weight;

            if ($random <= $cumulative) {
                return $key;
            }
        }

        return array_key_last($weights);
    }
}
