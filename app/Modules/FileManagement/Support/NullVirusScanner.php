<?php

namespace Modules\FileManagement\Support;

use Modules\FileManagement\Contracts\VirusScanner;
use Modules\FileManagement\Models\FileUpload;

/**
 * Phase 1 has no real antivirus engine wired up. This proves the
 * VirusScanner contract end-to-end and always reports clean — swap the
 * container binding for a real scanner (ClamAV, cloud API, ...) later
 * without touching FileUploadService.
 */
class NullVirusScanner implements VirusScanner
{
    public function scan(FileUpload $upload): bool
    {
        return true;
    }
}
