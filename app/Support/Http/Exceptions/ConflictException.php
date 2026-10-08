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
 *
 * An optional machine-readable $errorCode (e.g. SCHEDULE_CONFLICT) is
 * rendered as `errors.code`, alongside any $context keys, so clients can
 * branch on the code instead of parsing the human message. Callers without
 * a code keep rendering `errors: []` exactly as before.
 */
class ConflictException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = 'Permintaan bertentangan dengan kondisi saat ini.',
        public readonly ?string $errorCode = null,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        $errors = $this->errorCode === null ? [] : ['code' => $this->errorCode, ...$this->context];

        return ApiResponse::error($this->getMessage(), $errors, status: 409);
    }
}
