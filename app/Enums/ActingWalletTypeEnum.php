<?php

namespace App\Enums;

enum ActingWalletTypeEnum: string
{
    case WITHDRAWAL = 'withdrawal_wallet';
    case MAIN = 'main_wallet';
}
