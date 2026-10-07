<?php

namespace Modules\Academic\Enums;

/**
 * Status kepegawaian dosen — dosen tetap, dosen kontrak, atau dosen tidak
 * tetap/luar biasa (honorer).
 */
enum LecturerEmploymentStatus: string
{
    case Permanent = 'permanent';
    case Contract = 'contract';
    case Honorary = 'honorary';
}
