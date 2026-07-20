<?php

namespace Modules\Scholarship\Database\Seeders;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Student;
use Modules\Scholarship\Models\Scholarship;
use Modules\Tenancy\Models\University;

/**
 * 5-8 scholarship programs per university, each with 20-40 applications
 * drawn from distinct active students (one application per student per
 * scholarship, matching the scholarship_applications unique constraint).
 * Status is weighted toward "approved" (~50%) with a meaningful slice
 * under_review (~30%) and smaller shares rejected/submitted so the
 * applications list isn't degenerate.
 *
 * Bulk-inserted via DB::table() like FinanceSeeder/AcademicSeeder — see
 * their docblocks for why. Idempotent: skips entirely if this university
 * already has scholarships.
 */
class ScholarshipSeeder extends Seeder
{
    private const NAME_POOL = [
        'Prestasi Akademik', 'Bidikmisi', 'KIP Kuliah', 'Yayasan Peduli Pendidikan',
        'Beasiswa Unggulan', 'Bantuan Ekonomi', 'Beasiswa Riset', 'Alumni Peduli',
    ];

    private const AMOUNT_POOL = [1_000_000, 2_000_000, 3_000_000, 5_000_000];

    /** @var array<string, int> */
    private const STATUS_WEIGHTS = ['approved' => 50, 'under_review' => 30, 'rejected' => 15, 'submitted' => 5];

    /**
     * @param  Collection<int, Student>  $students
     */
    public function run(University $university, Collection $students): void
    {
        app(TenantContext::class)->setUniversityId($university->id);

        if (Scholarship::query()->count() > 0) {
            return;
        }

        $activeStudents = $students->filter(fn (Student $student) => $student->status === StudentStatus::Active);

        if ($activeStudents->isEmpty()) {
            return;
        }

        $now = now();
        $scholarshipCount = random_int(5, 8);
        $scholarshipRows = [];
        $scholarshipIds = [];

        for ($i = 0; $i < $scholarshipCount; $i++) {
            $scholarshipId = (string) Str::ulid();
            $scholarshipIds[] = $scholarshipId;

            $scholarshipRows[] = [
                'id' => $scholarshipId,
                'university_id' => $university->id,
                'name' => 'Beasiswa '.self::NAME_POOL[array_rand(self::NAME_POOL)],
                'provider' => fake()->company(),
                'quota' => fake()->numberBetween(10, 100),
                'amount' => self::AMOUNT_POOL[array_rand(self::AMOUNT_POOL)],
                'academic_year' => '2026/2027',
                'registration_start' => now()->subMonths(2)->toDateString(),
                'registration_end' => now()->addMonth()->toDateString(),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($scholarshipRows, 500) as $chunk) {
            DB::table('scholarships')->insert($chunk);
        }

        $applicationRows = [];

        foreach ($scholarshipIds as $scholarshipId) {
            $n = min($activeStudents->count(), random_int(20, 40));
            $pickedStudents = $activeStudents->shuffle()->take($n);

            foreach ($pickedStudents as $student) {
                $status = $this->weightedPick(self::STATUS_WEIGHTS);

                $applicationRows[] = [
                    'id' => (string) Str::ulid(),
                    'university_id' => $university->id,
                    'scholarship_id' => $scholarshipId,
                    'student_id' => $student->id,
                    'status' => $status,
                    'submitted_at' => $now,
                    'reviewed_at' => $status === 'submitted' ? null : $now,
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($applicationRows, 500) as $chunk) {
            DB::table('scholarship_applications')->insert($chunk);
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
