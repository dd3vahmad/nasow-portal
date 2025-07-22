<?php

namespace App\Enums;

enum CPDActivityType: string
{
    case SEMINAR = 'seminar';
    case COURSE = 'course';
    case WORKSHOP = 'workshop';
    case CONFERENCE = 'conference';
    case WEBINAR = 'webinar';
    case OTHER = 'other';
}
