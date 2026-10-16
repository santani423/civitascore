<?php

namespace Modules\Finance\Controllers;

/** Netralkan sel CSV yang diawali = + - @ (injeksi formula spreadsheet), pola ekspor PMB. */
final class CsvSafe
{
    public static function cell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true) && ! is_numeric($value) ? "'".$value : $value;
    }
}
