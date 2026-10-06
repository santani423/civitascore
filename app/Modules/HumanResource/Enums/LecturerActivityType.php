<?php

namespace Modules\HumanResource\Enums;

enum LecturerActivityType: string
{
    case Research = 'research';
    case CommunityService = 'community_service';

    public function label(): string
    {
        return match ($this) {
            self::Research => 'Penelitian',
            self::CommunityService => 'Pengabdian kepada Masyarakat',
        };
    }
}
