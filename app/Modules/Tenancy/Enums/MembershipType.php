<?php

namespace Modules\Tenancy\Enums;

enum MembershipType: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Staff = 'staff';
    case Lecturer = 'lecturer';
    case Student = 'student';
    case Auditor = 'auditor';
    case Guest = 'guest';
}
