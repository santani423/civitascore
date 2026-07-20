<?php

namespace Modules\Announcement\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\StudyProgram;
use Modules\Announcement\Models\Announcement;
use Modules\Tenancy\Models\University;

/**
 * Seeds 15-20 announcements for one university, weighted mostly toward
 * university-wide notices with smaller shares scoped to a specific faculty
 * or study program (target_id interpreted per target_scope — see the
 * announcements migration for why this isn't a formal FK). ~20% are pinned.
 *
 * Idempotent: if this university already has announcements seeded, skips
 * regeneration entirely — safe to re-run `php artisan db:seed`.
 */
class AnnouncementSeeder extends Seeder
{
    /** @var array<string, int> */
    private const TARGET_SCOPE_WEIGHTS = ['universitas' => 60, 'fakultas' => 25, 'program_studi' => 15];

    private const PINNED_CHANCE = 20;

    public function run(University $university, ?User $actor): void
    {
        if (Announcement::query()->count() > 0) {
            return;
        }

        $facultyIds = Faculty::query()->where('university_id', $university->id)->pluck('id')->all();
        $studyProgramIds = StudyProgram::query()->where('university_id', $university->id)->pluck('id')->all();

        $now = now();
        $rows = [];
        $count = random_int(15, 20);

        for ($i = 0; $i < $count; $i++) {
            $targetScope = $this->weightedPick(self::TARGET_SCOPE_WEIGHTS);

            $targetId = match ($targetScope) {
                'fakultas' => $facultyIds !== [] ? $facultyIds[array_rand($facultyIds)] : null,
                'program_studi' => $studyProgramIds !== [] ? $studyProgramIds[array_rand($studyProgramIds)] : null,
                default => null,
            };

            $rows[] = [
                'id' => (string) Str::ulid(),
                'university_id' => $university->id,
                'title' => fake()->sentence(5),
                'body' => fake()->paragraphs(3, true),
                'target_scope' => $targetScope,
                'target_id' => $targetId,
                'is_pinned' => random_int(1, 100) <= self::PINNED_CHANCE,
                'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
                'created_by' => $actor?->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('announcements')->insert($rows);
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
