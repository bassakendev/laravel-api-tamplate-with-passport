<?php

namespace App\Enums;

enum MessageStatusEnum: string
{
    case PENDING = 'pending';
    case SEND = 'send';
    case READ = 'read';
}
