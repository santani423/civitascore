<?php

namespace Modules\Academic\Enums;

enum CourseMaterialType: string
{
    case File = 'file';
    case Link = 'link';
    case Video = 'video';
    case Text = 'text';

    public function label(): string
    {
        return match ($this) {
            self::File => 'Berkas',
            self::Link => 'Tautan',
            self::Video => 'Video',
            self::Text => 'Teks',
        };
    }
}
