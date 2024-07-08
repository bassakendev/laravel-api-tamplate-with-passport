<?php

namespace App\Enums;

enum SavingGroupContributionFrequencyEnum: string
{
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case DAILY = 'daily';
}
