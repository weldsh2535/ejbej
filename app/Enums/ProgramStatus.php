<?php

namespace App\Enums;

enum ProgramStatus: string
{

    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case ARCHIVED = 'archived';
    case UPCOMING = 'upcoming';
}
