<?php

namespace Modules\SystemSetting\Enums;

enum SettingValueType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Json = 'json';

    /**
     * Cast a raw stored string value to its declared PHP type.
     */
    public function cast(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::String => $value,
            self::Integer => (int) $value,
            self::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::Json => json_decode($value, true),
        };
    }
}
