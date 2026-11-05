<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\AssessmentDomain;

/**
 * Jenis asesmen One Atma Talent (mis. "Minat RIASEC"). Satu jenis memiliki
 * banyak versi instrumen; hanya satu versi `published` yang aktif per jenis.
 *
 * @property string $id
 * @property string $university_id
 * @property string $code
 * @property string $name
 * @property AssessmentDomain $domain
 * @property string|null $description
 * @property bool $is_active
 * @property-read Collection<int, AssessmentVersion> $versions
 */
class AssessmentType extends PmbModel
{
    protected $table = 'pmb_assessment_types';

    protected function casts(): array
    {
        return [
            'domain' => AssessmentDomain::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<AssessmentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(AssessmentVersion::class, 'assessment_type_id');
    }
}
