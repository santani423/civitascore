<?php

namespace Modules\Library\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Library\Models\Book;
use Modules\Tenancy\Models\University;

/**
 * Seeds a library catalog (150-300 books) plus a circulation history
 * (500-1000 loans) for one university, weighted toward "dikembalikan" (60%)
 * with a meaningful slice still "dipinjam" (30%) and "terlambat" (10%) so
 * the loan-status chart and "denda" stat aren't degenerate.
 *
 * Bulk-inserted via DB::table() like AcademicSeeder — see its docblock for
 * why. Idempotent: skips entirely if this university already has books.
 *
 * @param  Collection<int, \Modules\Academic\Models\Student>  $students
 */
class LibrarySeeder extends Seeder
{
    private const CATEGORY_POOL = ['Fiksi', 'Non-Fiksi', 'Sains', 'Teknologi', 'Sejarah', 'Ekonomi'];

    /** @var array<string, int> */
    private const STATUS_WEIGHTS = ['dikembalikan' => 60, 'dipinjam' => 30, 'terlambat' => 10];

    public function run(University $university, Collection $students): void
    {
        if (Book::query()->count() > 0) {
            return;
        }

        if ($students->isEmpty()) {
            return;
        }

        $bookIds = $this->seedBooks($university);
        $this->seedLoans($university, $bookIds, $students);
    }

    /**
     * @return array<int, string>
     */
    private function seedBooks(University $university): array
    {
        $now = now();
        $rows = [];
        $bookCount = random_int(150, 300);

        for ($i = 0; $i < $bookCount; $i++) {
            $rows[] = [
                'id' => (string) Str::ulid(),
                'university_id' => $university->id,
                'title' => fake()->sentence(3),
                'author' => fake()->name(),
                'publisher' => fake()->company(),
                // Faker's isbn13() may not exist in this Faker version —
                // fake an ISBN-shaped string instead.
                'isbn' => fake()->numerify('978-##-####-###-#'),
                'category' => self::CATEGORY_POOL[array_rand(self::CATEGORY_POOL)],
                'stock' => fake()->numberBetween(1, 10),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('books')->insert($chunk);
        }

        return Book::query()->where('university_id', $university->id)->pluck('id')->all();
    }

    /**
     * @param  array<int, string>  $bookIds
     * @param  Collection<int, \Modules\Academic\Models\Student>  $students
     */
    private function seedLoans(University $university, array $bookIds, Collection $students): void
    {
        $studentIds = $students->pluck('id')->all();
        $now = now();
        $rows = [];
        $loanCount = random_int(500, 1000);

        for ($i = 0; $i < $loanCount; $i++) {
            $status = $this->weightedPick(self::STATUS_WEIGHTS);
            $borrowedAt = now()->subDays(random_int(5, 120));
            $dueAt = $borrowedAt->addDays(14);

            $rows[] = [
                'id' => (string) Str::ulid(),
                'university_id' => $university->id,
                'book_id' => $bookIds[array_rand($bookIds)],
                'student_id' => $studentIds[array_rand($studentIds)],
                'borrowed_at' => $borrowedAt->toDateString(),
                'due_at' => $dueAt->toDateString(),
                'returned_at' => $status === 'dikembalikan' ? fake()->dateTimeBetween($borrowedAt, 'now')->format('Y-m-d') : null,
                'status' => $status,
                'fine_amount' => $status === 'terlambat' ? random_int(5000, 50000) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('book_loans')->insert($chunk);
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
