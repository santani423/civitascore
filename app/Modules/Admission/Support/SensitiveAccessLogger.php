<?php

namespace Modules\Admission\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Admission\Models\SensitiveAccessLog;

/** Setiap pembukaan data sensitif dicatat (BR-40, 08 §4, AC-18). */
class SensitiveAccessLogger
{
    public function log(User $user, Model $subject, string $fieldOrDocument, string $purpose, ?string $ip): void
    {
        SensitiveAccessLog::query()->create([
            'university_id' => $subject->getAttribute('university_id'),
            'user_id' => $user->getKey(),
            'subject_type' => class_basename($subject),
            'subject_id' => (string) $subject->getKey(),
            'field_or_document' => mb_substr($fieldOrDocument, 0, 100),
            'purpose' => mb_substr($purpose, 0, 500),
            'ip_address' => $ip,
        ]);
    }

    public static function mask(?string $value, int $visibleEnd = 4, int $visibleStart = 0): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $length = mb_strlen($value);

        if ($length <= $visibleEnd + $visibleStart) {
            return str_repeat('*', $length);
        }

        return mb_substr($value, 0, $visibleStart).str_repeat('*', $length - $visibleEnd - $visibleStart).mb_substr($value, -$visibleEnd);
    }
}
