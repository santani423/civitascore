<?php

use Modules\SystemSetting\Enums\SettingValueType;

test('each setting value type casts its raw stored string correctly', function () {
    expect(SettingValueType::String->cast('hello'))->toBe('hello');
    expect(SettingValueType::Integer->cast('42'))->toBe(42);
    expect(SettingValueType::Boolean->cast('1'))->toBeTrue();
    expect(SettingValueType::Boolean->cast('0'))->toBeFalse();
    expect(SettingValueType::Json->cast('{"a":1}'))->toBe(['a' => 1]);
});

test('casting a null value always returns null regardless of type', function () {
    expect(SettingValueType::Integer->cast(null))->toBeNull();
});
