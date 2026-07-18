<?php

namespace App\Support\Http\Exceptions;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Throw when a request is individually valid but conflicts with the
 * resource's current state (duplicate assignment, already-decided approval
 * step, etc.) — renders itself as a 409 via Laravel's exception-render hook.
 */
class ConflictException extends RuntimeException
{
    public function __construct(string $message = 'Permintaan bertentangan dengan kondisi saat ini.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), status: 409);
    }
}
