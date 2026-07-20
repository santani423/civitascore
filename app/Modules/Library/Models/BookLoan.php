<?php

namespace Modules\Library\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Student;
use Modules\Library\Database\Factories\BookLoanFactory;
use Modules\Library\Enums\BookLoanStatus;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $book_id
 * @property string $student_id
 * @property CarbonImmutable $borrowed_at
 * @property CarbonImmutable $due_at
 * @property CarbonImmutable|null $returned_at
 * @property BookLoanStatus $status
 * @property string|null $fine_amount
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Book $book
 * @property-read Student $student
 */
class BookLoan extends Model implements ScopesToInstitution
{
    /** @use HasFactory<BookLoanFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'book_id', 'student_id', 'borrowed_at', 'due_at', 'returned_at', 'status', 'fine_amount'];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'date',
            'due_at' => 'date',
            'returned_at' => 'date',
            'status' => BookLoanStatus::class,
            'fine_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    protected static function newFactory(): BookLoanFactory
    {
        return BookLoanFactory::new();
    }
}
