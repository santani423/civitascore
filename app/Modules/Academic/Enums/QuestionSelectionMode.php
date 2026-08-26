<?php

namespace Modules\Academic\Enums;

enum QuestionSelectionMode: string
{
    case All = 'all';
    case Random = 'random';
    case Manual = 'manual';
}
