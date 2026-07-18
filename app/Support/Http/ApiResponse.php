<?php

namespace App\Support\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data = null, string $message = 'Berhasil.', array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $status);
    }

    public static function paginated(ResourceCollection $resource, string $message = 'Berhasil.'): JsonResponse
    {
        $paginator = $resource->resource instanceof LengthAwarePaginator
            ? $resource->resource
            : null;

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource->collection,
            'meta' => $paginator ? [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ] : [],
        ], 200);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
