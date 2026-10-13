<?php

namespace Modules\Admission\Support;

/**
 * Evaluator aturan tampil field dinamis (04 §D.3 `visibility_rule`) dan
 * kondisi persyaratan dokumen (03 FR-CFG-08 `condition`).
 *
 * Bentuk: {"all":[cond...]} | {"any":[cond...]} | cond, dengan
 * cond = {"field": "...", "op": "eq|neq|in|not_in|gt|lt|filled", "value": ...}.
 */
final class FormRuleEvaluator
{
    public const OPERATORS = ['eq', 'neq', 'in', 'not_in', 'gt', 'lt', 'filled'];

    /**
     * @param  array<string, mixed>|null  $rule
     * @param  array<string, mixed>  $context
     */
    public static function passes(?array $rule, array $context): bool
    {
        if ($rule === null || $rule === []) {
            return true;
        }

        if (isset($rule['all'])) {
            foreach ((array) $rule['all'] as $condition) {
                if (! self::passes($condition, $context)) {
                    return false;
                }
            }

            return true;
        }

        if (isset($rule['any'])) {
            foreach ((array) $rule['any'] as $condition) {
                if (self::passes($condition, $context)) {
                    return true;
                }
            }

            return false;
        }

        $field = $rule['field'] ?? null;

        if (! is_string($field)) {
            return true;
        }

        $actual = data_get($context, $field);
        $expected = $rule['value'] ?? null;

        return match ($rule['op'] ?? 'eq') {
            'eq' => (string) $actual === (string) $expected,
            'neq' => (string) $actual !== (string) $expected,
            'in' => in_array((string) $actual, array_map('strval', (array) $expected), true),
            'not_in' => ! in_array((string) $actual, array_map('strval', (array) $expected), true),
            'gt' => is_numeric($actual) && (float) $actual > (float) $expected,
            'lt' => is_numeric($actual) && (float) $actual < (float) $expected,
            'filled' => $actual !== null && $actual !== '' && $actual !== [] && $actual !== false,
            default => true,
        };
    }

    /**
     * Validasi struktur aturan saat template disimpan.
     *
     * @param  array<string, mixed>|null  $rule
     */
    public static function isValid(?array $rule, int $depth = 0): bool
    {
        if ($rule === null || $rule === []) {
            return true;
        }

        if ($depth > 3) {
            return false;
        }

        foreach (['all', 'any'] as $group) {
            if (isset($rule[$group])) {
                if (! is_array($rule[$group]) || count($rule[$group]) > 20) {
                    return false;
                }

                foreach ($rule[$group] as $condition) {
                    if (! is_array($condition) || ! self::isValid($condition, $depth + 1)) {
                        return false;
                    }
                }

                return true;
            }
        }

        return is_string($rule['field'] ?? null) && in_array($rule['op'] ?? 'eq', self::OPERATORS, true);
    }
}
