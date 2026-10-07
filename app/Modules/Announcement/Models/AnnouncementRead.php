<?php

namespace Modules\Announcement\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Penanda "sudah dibaca" — tidak perlu TenantScoped: selalu diakses lewat
 * Announcement (yang sudah ter-scope tenant) dan user yang login.
 *
 * @property string $id
 * @property string $announcement_id
 * @property string $user_id
 * @property CarbonImmutable $read_at
 */
class AnnouncementRead extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['announcement_id', 'user_id', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Announcement, $this>
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
