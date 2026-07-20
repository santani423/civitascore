<?php

namespace Modules\Library\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Student;
use Modules\Library\Enums\BookLoanStatus;
use Modules\Library\Models\Book;
use Modules\Library\Models\BookLoan;

/**
 * @extends Factory<BookLoan>
 */
class BookLoanFactory extends Factory
{
    protected $model = BookLoan::class;

    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'student_id' => Student::factory(),
            'borrowed_at' => now()->subDays(10),
            'due_at' => now()->addDays(4),
            'returned_at' => null,
            'status' => BookLoanStatus::Dipinjam,
            'fine_amount' => null,
        ];
    }
}
