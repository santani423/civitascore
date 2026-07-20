<?php

namespace Modules\Internship\Enums;

enum InternshipStatus: string
{
    case Terdaftar = 'terdaftar';
    case Berlangsung = 'berlangsung';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';
}
