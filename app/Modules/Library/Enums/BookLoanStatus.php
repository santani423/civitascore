<?php

namespace Modules\Library\Enums;

enum BookLoanStatus: string
{
    case Dipinjam = 'dipinjam';
    case Dikembalikan = 'dikembalikan';
    case Terlambat = 'terlambat';
}
