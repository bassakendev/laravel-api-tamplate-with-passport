<?php

namespace App\Consts;

abstract class AdminNotificationMessage
{
    static string $LOAN_FAILURE = 'A loan has just been refused due to the insufficiency of your balance, remember to recharge it to make the loans work again in the application.';

    static string $NEW_LOAN = 'A new loan has just arrived.';
}
