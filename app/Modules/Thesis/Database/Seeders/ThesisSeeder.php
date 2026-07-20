<?php

namespace Modules\Thesis\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Thesis\Enums\ThesisStatus;
use Modules\Thesis\Enums\ThesisType;
use Modules\Thesis\Models\Thesis;
use Modules\Tenancy\Models\University;

/**
 * Gives ~20% of a university's eligible students (Active or Graduated) a
 * thesis record, always Skripsi (S1 is this app's dominant population).
 * Graduated students weight heavily toward "selesai"; active students are
 * spread across the earlier stages — see weightedPick(), copied from
 * AcademicSeeder's pattern.
 *
 * Idempotent: if this university already has theses seeded, skips
 * regeneration entirely.
 */
class ThesisSeeder extends Seeder
{
    /** @var array<string, int> status-slug => weight out of 100, for graduated students */
    private const GRADUATED_STATUS_WEIGHTS = ['selesai' => 80, 'sidang' => 10, 'penelitian' => 10];

    /** @var array<string, int> status-slug => weight out of 100, for active students */
    private const ACTIVE_STATUS_WEIGHTS = [
        'proposal' => 15, 'bimbingan' => 25, 'seminar_proposal' => 20,
        'penelitian' => 25, 'sidang' => 10, 'selesai' => 5,
    ];

    /** Share of eligible students who get a thesis record. */
    private const ELIGIBLE_SHARE = 0.2;

    /**
     * @param  Collection<int, Student>  $students
     */
    public function run(University $university, Collection $students): void
    {
        if (Thesis::query()->count() > 0) {
            return;
        }

        $lecturers = Lecturer::query()->where('university_id', $university->id)->get();

        if ($lecturers->isEmpty()) {
            return;
        }

        $eligible = $students->filter(
            fn (Student $student) => in_array($student->status, [StudentStatus::Active, StudentStatus::Graduated], true),
        );

        $selected = $eligible->shuffle()->take((int) ceil($eligible->count() * self::ELIGIBLE_SHARE));

        if ($selected->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($selected as $student) {
            $weights = $student->status === StudentStatus::Graduated
                ? self::GRADUATED_STATUS_WEIGHTS
                : self::ACTIVE_STATUS_WEIGHTS;

            $status = $this->weightedPick($weights);

            $rows[] = [
                'id' => (string) Str::ulid(),
                'university_id' => $university->id,
                'student_id' => $student->id,
                'supervisor_lecturer_id' => $lecturers->random()->id,
                'title' => fake()->sentence(6),
                'thesis_type' => ThesisType::Skripsi->value,
                'status' => $status,
                'submitted_at' => now()->subMonths(random_int(1, 12)),
                'completed_at' => $status === ThesisStatus::Selesai->value ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('theses')->insert($chunk);
        }
    }

    /**
     * @param  array<string, int>  $weights
     */
    private function weightedPick(array $weights): string
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
