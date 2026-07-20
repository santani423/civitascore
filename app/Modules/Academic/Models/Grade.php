<?php

namespace Modules\Academic\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\Factories\GradeFactory;
use Modules\Academic\Enums\LetterGrade;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $krs_item_id
 * @property LetterGrade|null $letter_grade
 * @property string|null $score
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read KrsItem $krsItem
 */
class Grade extends Model implements ScopesToInstitution
{
    /** @use HasFactory<GradeFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'krs_item_id', 'letter_grade', 'score', 'submitted_at'];

    protected function casts(): array
    {
        return [
            'letter_grade' => LetterGrade::class,
            'score' => 'decimal:2',
            'submitted_at' => 'datetime',
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
     * @return BelongsTo<KrsItem, $this>
     */
    public function krsItem(): BelongsTo
    {
        return $this->belongsTo(KrsItem::class);
    }

    protected static function newFactory(): GradeFactory
    {
        return GradeFactory::new();
    }
}
