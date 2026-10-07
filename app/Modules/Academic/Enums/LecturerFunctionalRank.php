<?php

namespace Modules\Academic\Enums;

/**
 * Jabatan fungsional akademik dosen (jenjang Kemendikbudristek).
 */
enum LecturerFunctionalRank: string
{
    case None = 'none';
    case AsistenAhli = 'asisten_ahli';
    case Lektor = 'lektor';
    case LektorKepala = 'lektor_kepala';
    case GuruBesar = 'guru_besar';
}
