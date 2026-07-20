<?php

namespace Modules\Library\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Library\Database\Factories\BookFactory;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $title
 * @property string $author
 * @property string|null $publisher
 * @property string|null $isbn
 * @property string|null $category
 * @property int $stock
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Book extends Model implements ScopesToInstitution
{
    /** @use HasFactory<BookFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'title', 'author', 'publisher', 'isbn', 'category', 'stock', 'is_active'];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'is_active' => 'boolean',
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
     * @return HasMany<BookLoan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(BookLoan::class);
    }

    protected static function newFactory(): BookFactory
    {
        return BookFactory::new();
    }
}
