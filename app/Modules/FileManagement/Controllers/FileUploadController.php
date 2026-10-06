<?php

namespace Modules\FileManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\FileManagement\Models\FileUpload;
use Modules\FileManagement\Requests\StoreFileUploadRequest;
use Modules\FileManagement\Resources\FileUploadResource;
use Modules\FileManagement\Services\FileUploadService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileUploadController extends Controller
{
    /**
     * Hanya tipe yang aman dirender browser secara inline. Content-Type
     * diambil dari ekstensi (sudah divalidasi `mimes:` saat upload), bukan
     * dari mime_type kiriman klien.
     *
     * @var array<string, string>
     */
    private const PREVIEWABLE = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
    ];

    public function __construct(private readonly FileUploadService $uploads) {}

    public function store(StoreFileUploadRequest $request): JsonResponse
    {
        $upload = $this->uploads->store(
            $request->file('file'),
            $request->user(),
            $request->boolean('is_public'),
        );

        return ApiResponse::success(new FileUploadResource($upload), 'File berhasil diunggah.', status: 201);
    }

    public function show(FileUpload $fileUpload): JsonResponse
    {
        $this->authorize('view', $fileUpload);

        return ApiResponse::success(new FileUploadResource($fileUpload));
    }

    public function download(FileUpload $fileUpload): StreamedResponse
    {
        $this->authorize('download', $fileUpload);

        return Storage::disk($fileUpload->disk)->download($fileUpload->path, $fileUpload->original_name);
    }

    public function preview(FileUpload $fileUpload): Response
    {
        $this->authorize('download', $fileUpload);

        $contentType = self::PREVIEWABLE[strtolower($fileUpload->extension)] ?? null;

        if ($contentType === null) {
            return ApiResponse::error('Pratinjau hanya tersedia untuk PDF dan gambar.', status: 415);
        }

        return Storage::disk($fileUpload->disk)->response($fileUpload->path, $fileUpload->original_name, [
            'Content-Type' => $contentType,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox",
        ], 'inline');
    }

    public function destroy(FileUpload $fileUpload): JsonResponse
    {
        $this->authorize('delete', $fileUpload);

        $fileUpload->delete();

        return ApiResponse::success(message: 'File berhasil dihapus.');
    }
}
