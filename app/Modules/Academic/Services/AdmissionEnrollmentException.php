<?php

namespace Modules\Academic\Services;

use RuntimeException;

/** Kesalahan data saat pembentukan mahasiswa — tidak di-retry otomatis (K.7). */
class AdmissionEnrollmentException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
