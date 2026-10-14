<?php

namespace Modules\Admission\Support;

/**
 * Perilaku bersama enum state machine PMB (docs/pmb/09 §2): setiap enum
 * mendefinisikan allowedTransitions(); service domain wajib memeriksa
 * transisi lewat StateTransition::assert() sebelum menulis status.
 */
trait TransitionsState
{
    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
