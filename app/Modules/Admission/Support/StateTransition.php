<?php

namespace Modules\Admission\Support;

use BackedEnum;
use Modules\Admission\Exceptions\InvalidTransitionException;

final class StateTransition
{
    /**
     * Menolak transisi yang tidak ada di tabel state machine (09 §2).
     */
    public static function assert(BackedEnum $from, BackedEnum $to, string $machine): void
    {
        if (! method_exists($from, 'canTransitionTo') || ! $from->canTransitionTo($to)) {
            throw InvalidTransitionException::between($machine, (string) $from->value, (string) $to->value);
        }
    }
}
