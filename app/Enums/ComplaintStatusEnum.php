<?php

namespace App\Enums;

enum ComplaintStatusEnum: string
{
    case CANCELED = 'Canceled';
    case PENDING = 'Pending';
    case READ = 'Read';
    case DISMISS = 'dismiss';
}
