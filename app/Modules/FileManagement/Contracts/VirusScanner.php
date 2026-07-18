<?php

namespace Modules\FileManagement\Contracts;

use Modules\FileManagement\Models\FileUpload;

interface VirusScanner
{
    /**
     * @return bool true when the file is clean, false when it should be rejected
     */
    public function scan(FileUpload $upload): bool;
}
