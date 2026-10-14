<?php

namespace Modules\Admission\Exceptions;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Pelanggaran aturan bisnis PMB dengan kode domain (docs/pmb/11 §A.1),
 * mis. WAVE_CLOSED (422), APPLICATION_LIMIT_REACHED (409), DOCUMENT_LOCKED (423).
 */
class DomainRuleException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status = 422,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), ['code' => $this->errorCode, ...$this->context], $this->status);
    }
}
