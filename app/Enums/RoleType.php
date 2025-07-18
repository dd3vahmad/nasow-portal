<?php

namespace App\Enums;

enum RoleType: string
{
    case NationalAdmin = 'national-admin';
    case StateAdmin = 'state-admin';
    case SupportStaff = 'support-staff';
    case Member = 'member';
    case Guest = 'guest';
}
