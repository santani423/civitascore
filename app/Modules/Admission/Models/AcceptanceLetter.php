<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\LetterStatus;
use Modules\FileManagement\Models\FileUpload;

/**
 * @property string $id
 * @property string $university_id
 * @property string $decision_id
 * @property string $letter_number
 * @property string $verification_code
 * @property string|null $file_upload_id
 * @property LetterStatus $status
 * @property CarbonImmutable|null $issued_at
 * @property CarbonImmutable|null $revoked_at
 * @property string|null $revoke_reason
 * @property string|null $signature_reference
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read AdmissionDecision $decision
 * @property-read FileUpload|null $fileUpload
 */
class AcceptanceLetter extends PmbModel
{
    protected $table = 'pmb_acceptance_letters';

    protected function casts(): array
    {
        return [
            'status' => LetterStatus::class,
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AdmissionDecision, $this>
     */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(AdmissionDecision::class, 'decision_id');
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function fileUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'file_upload_id');
    }
}
