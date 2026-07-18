<?php

namespace Modules\AuditLog\Enums;

enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Exported = 'exported';
    case Imported = 'imported';
}
