<?php

namespace Modules\Finance\Payments;

final class ChargeResult
{
    /** @param  array<int, array{title: string, steps: array<int, string>}>  $instructions */
    public function __construct(
        public readonly string $providerReference,
        public readonly ?string $vaNumber = null,
        public readonly ?string $qrString = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $expiresAt = null,
        public readonly array $instructions = [],
    ) {}
}
