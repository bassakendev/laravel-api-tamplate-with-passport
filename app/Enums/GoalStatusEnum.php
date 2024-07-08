<?php

namespace App\Enums;

enum GoalStatusEnum: string
{
    case INPROGRESS = 'in progress';
    case REACHED = 'reached';
    case NO_REACHED = 'not reached';
    case CANCELLED = 'cancelled';
}
