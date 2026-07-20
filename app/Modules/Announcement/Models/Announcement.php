<?php

namespace Modules\Announcement\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Announcement\Database\Factories\AnnouncementFactory;
use Modules\Announcement\Enums\AnnouncementTargetScope;
use Modules\Tenancy\Models\University;

/**
 * @property string $id
 * @property string $university_id
 * @property string $title
 * @property string $body
 * @property AnnouncementTargetScope $target_scope
 * @property string|null $target_id
 * @property bool $is_pinned
 * @property CarbonImmutable $published_at
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read University $university
 * @property-read User|null $creator
 */
class Announcement extends Model implements ScopesToInstitution
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'title', 'body', 'target_scope', 'target_id', 'is_pinned', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'target_scope' => AnnouncementTargetScope::class,
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    protected static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }
}
