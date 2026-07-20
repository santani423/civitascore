<?php

namespace Modules\Announcement\Enums;

enum AnnouncementTargetScope: string
{
    case Universitas = 'universitas';
    case Fakultas = 'fakultas';
    case ProgramStudi = 'program_studi';
}
