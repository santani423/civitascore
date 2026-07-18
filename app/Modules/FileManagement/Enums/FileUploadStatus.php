<?php

namespace Modules\FileManagement\Enums;

enum FileUploadStatus: string
{
    case Pending = 'pending';
    case Scanning = 'scanning';
    case Clean = 'clean';
    case Rejected = 'rejected';
}
