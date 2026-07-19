<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Global catalog of enable-able business modules (table `modules`). Named
 * PlatformModule (not Module) to avoid confusion with the app/Modules/*
 * codebase-organization concept.
 */
class PlatformModule extends Model
{
    use HasUlids;

    protected $table = 'modules';

    protected $fillable = ['key', 'name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
