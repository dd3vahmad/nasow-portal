<?php

namespace App\Enums;

enum ActivityType: string
{
    case SUPPORT = 'support';
    case MEMBERSHIP = 'membership';
    case CPD = 'cpd';
    case PAYMENT = 'payment';
    case ACCOUNT = 'account';
}
