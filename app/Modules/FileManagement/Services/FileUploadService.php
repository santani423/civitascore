<?php

namespace Modules\FileManagement\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\FileManagement\Contracts\VirusScanner;
use Modules\FileManagement\Enums\FileUploadStatus;
use Modules\FileManagement\Models\FileUpload;
use Modules\SystemSetting\Services\FeatureFlagService;

class FileUploadService
{
    public function __construct(
        private readonly VirusScanner $scanner,
        private readonly FeatureFlagService $featureFlags,
    ) {}

    public function store(UploadedFile $file, User $uploader, bool $isPublic = false): FileUpload
    {
        $disk = (string) config('filesystems.default');
        $path = $file->store('uploads/'.$uploader->id, $disk);

        $upload = DB::transaction(fn (): FileUpload => FileUpload::create([
            'uploaded_by' => $uploader->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size_bytes' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'status' => FileUploadStatus::Pending,
            'is_public' => $isPublic,
        ]));

        return $this->scanIfEnabled($upload);
    }

    private function scanIfEnabled(FileUpload $upload): FileUpload
    {
        if (! $this->featureFlags->isEnabled('file_upload.virus_scan_enabled')) {
            $upload->update(['status' => FileUploadStatus::Clean]);

            return $upload;
        }

        $upload->update(['status' => FileUploadStatus::Scanning]);

        $isClean = $this->scanner->scan($upload);

        $upload->update(['status' => $isClean ? FileUploadStatus::Clean : FileUploadStatus::Rejected]);

        return $upload;
    }
}
