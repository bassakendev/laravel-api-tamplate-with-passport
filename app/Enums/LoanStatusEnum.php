<?php

namespace App\Enums;

enum LoanStatusEnum: string
{
    case REFUNDED = 'refunded';
    case NOT_REFUNDED = 'not refunded';
}
