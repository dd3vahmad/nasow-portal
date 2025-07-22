<?php

namespace App\Enums;

enum CpdActivityType: string
{
    case SEMINAR = 'seminar';
    case COURSE = 'course';
    case WORKSHOP = 'workshop';
    case CONFERENCE = 'conference';
    case WEBINAR = 'webinar';
    case OTHER = 'other';
}
