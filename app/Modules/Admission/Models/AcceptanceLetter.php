<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\LetterStatus;
use Modules\FileManagement\Models\FileUpload;

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
