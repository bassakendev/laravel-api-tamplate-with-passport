<?php

namespace App\Enums;

enum UserStatusEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
}
