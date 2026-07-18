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
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileUploadController extends Controller
{
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

    public function destroy(FileUpload $fileUpload): JsonResponse
    {
        $this->authorize('delete', $fileUpload);

        $fileUpload->delete();

        return ApiResponse::success(message: 'File berhasil dihapus.');
    }
}
