<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case SAVINGS = 'savings';
    case WITHDRAWAL = 'withdrawal';
    case TRANSFER = 'transfer';
    case LOAN = 'loan';
    case DEPOSIT = 'deposit';
    case FINANCING = 'financing';
}
