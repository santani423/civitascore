<?php

namespace Modules\Admission\Exceptions;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Transisi status yang tidak ada di tabel state machine (docs/pmb/09) →
 * HTTP 422 `INVALID_TRANSITION`, opsional dengan daftar syarat yang belum
 * terpenuhi (`unmet`).
 */
class InvalidTransitionException extends RuntimeException
{
    /**
     * @param  array<int, string>  $unmet
     */
    public function __construct(string $message = 'Perubahan status tidak diizinkan.', public readonly array $unmet = [])
    {
        parent::__construct($message);
    }

    public static function between(string $machine, string $from, string $to): self
    {
        return new self("Transisi {$machine} dari '{$from}' ke '{$to}' tidak diizinkan.");
    }

    public function render(): JsonResponse
    {
        $errors = ['code' => 'INVALID_TRANSITION'];

        if ($this->unmet !== []) {
            $errors['unmet'] = $this->unmet;
        }

        return ApiResponse::error($this->getMessage(), $errors, 422);
    }
}
