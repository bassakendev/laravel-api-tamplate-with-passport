<?php

namespace App\Enums;

enum SavingGroupMemberSatusEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case REFUSED = 'refused';
    case LEFT = 'left';
}
