<?php

namespace Modules\Alumni\Enums;

enum AlumniEmploymentStatus: string
{
    case Bekerja = 'bekerja';
    case Wirausaha = 'wirausaha';
    case MelanjutkanStudi = 'melanjutkan_studi';
    case MencariKerja = 'mencari_kerja';
    case BelumBekerja = 'belum_bekerja';
}
