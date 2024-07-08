<?php

namespace App\Enums;

enum NotificationTypeEnum: string
{
    case INFO = 'info';
    case DANGER = 'danger';
    case SUCCESS = 'success';
    case ERROR = 'error';
}
