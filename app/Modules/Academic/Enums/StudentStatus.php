<?php

namespace Modules\Academic\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Leave = 'leave';
    case Graduated = 'graduated';
    case Inactive = 'inactive';
    case DroppedOut = 'dropped_out';
    case Resigned = 'resigned';

    /**
     * Status yang tidak lagi boleh login sama sekali (lihat
     * AuthenticateUserAction & StudentUserAccountSeeder).
     *
     * @return array<int, self>
     */
    public static function loginBlocked(): array
    {
        return [self::Inactive, self::DroppedOut, self::Resigned];
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Leave => 'Cuti',
            self::Graduated => 'Lulus',
            self::Inactive => 'Nonaktif',
            self::DroppedOut => 'Drop Out',
            self::Resigned => 'Mengundurkan Diri',
        };
    }
}
