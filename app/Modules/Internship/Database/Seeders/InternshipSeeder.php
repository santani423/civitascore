<?php

namespace Modules\Internship\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Lecturer;
use Modules\Internship\Models\Internship;
use Modules\Tenancy\Models\University;

/**
 * Seeds a realistic slice of internship/KKN/MBKM placements for one
 * university: ~15% of active students get a single internship record, with
 * program type and status drawn from weighted distributions so dashboards
 * have a believable mix rather than a uniform spread.
 *
 * Idempotent: if this university already has internships seeded, skips
 * regeneration entirely — safe to re-run `php artisan db:seed`.
 */
class InternshipSeeder extends Seeder
{
    private const PROGRAM_TYPE_WEIGHTS = ['magang' => 50, 'kkn' => 20, 'mbkm' => 30];

    private const STATUS_WEIGHTS = ['selesai' => 40, 'berlangsung' => 40, 'terdaftar' => 15, 'dibatalkan' => 5];

    private const ACTIVE_STUDENT_SHARE = 0.15;

    /**
     * @param  Collection<int, \Modules\Academic\Models\Student>  $students
     */
    public function run(University $university, Collection $students): void
    {
        if (Internship::query()->count() > 0) {
            return;
        }

        $lecturers = Lecturer::query()->where('university_id', $university->id)->get();

        if ($lecturers->isEmpty()) {
            return;
        }

        $activeStudents = $students->filter(fn ($student) => $student->status === StudentStatus::Active);

        $selectedStudents = $activeStudents->shuffle()->take((int) ceil($activeStudents->count() * self::ACTIVE_STUDENT_SHARE));

        if ($selectedStudents->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($selectedStudents as $student) {
            $programType = $this->weightedPick(self::PROGRAM_TYPE_WEIGHTS);
            $status = $this->weightedPick(self::STATUS_WEIGHTS);

            $rows[] = [
                'id' => (string) Str::ulid(),
                'university_id' => $university->id,
                'student_id' => $student->id,
                'program_type' => $programType,
                'institution_name' => fake()->company(),
                'position' => fake()->jobTitle(),
                'supervisor_lecturer_id' => $lecturers->random()->id,
                'start_date' => now()->subMonths(random_int(1, 6)),
                'end_date' => in_array($status, ['selesai', 'dibatalkan'], true) ? $now : null,
                'status' => $status,
                'sks_converted' => $programType === 'mbkm' ? random_int(2, 6) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('internships')->insert($chunk);
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
