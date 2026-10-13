<?php

namespace Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\FileManagement\Enums\FileUploadStatus;
use Modules\FileManagement\Models\FileUpload;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Unduhan berkas PMB lewat URL bertanda tangan berumur pendek (FR-DOC-03,
 * 13 §B.4). Hak akses sudah diperiksa saat URL dibangkitkan; berkas yang
 * belum `clean` tidak pernah dilayani.
 */
class FileDownloadController extends Controller
{
    public function __invoke(string $file): StreamedResponse
    {
        $upload = FileUpload::query()->withoutGlobalScopes()->findOrFail($file);

        abort_unless($upload->status === FileUploadStatus::Clean, 404);

        $inline = str_starts_with($upload->mime_type, 'image/') || $upload->mime_type === 'application/pdf';

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name, [
            'Content-Type' => $upload->mime_type,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], $inline ? 'inline' : 'attachment');
    }
}
