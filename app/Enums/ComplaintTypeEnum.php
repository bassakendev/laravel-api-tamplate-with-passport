<?php

namespace App\Enums;

enum ComplaintTypeEnum: string
{
    case PHYSICAL_VIOLENCE = 'Physical violence';
    case PSYCHOLOGICAL_VIOLENCE = 'Psychological Violence';
    case SEXUAL_VIOLENCE = 'Sexual Violence';
    case ECONOMIC_VIOLENCE = 'Economic Violence';
    case VERBAL_VIOLENCE = 'Verbal violence';
    case HARASSMENT = 'Harassment';
    case ABUS_OF_POWER_AND_CONTROL = 'Abuse of Power and Control';
    case SPIRITUAL_OR_RELIGIOUS_VIOLENCE = 'Spiritual or Religious Violence';
}
