<?php

namespace App\Consts;

abstract class ErrorMessages
{
    static string $SAVING_GOAL_MODEL_ERROR = 'Saving goal not found, already completed or canceled.';

    static string $LOAN_VALUE_ERROR = 'The amount to be loaned either is greater or less than the fixed amounts.';

    static string $BALANCE_ERROR = 'Your balance is insufficient to complete this transaction.';

    static string $LOAN_INPUT_REQUEST_ERROR = 'The amount entered is greater than or aqual to the expected amount.';

    static string $LOAN_FAILLED_ERROR = 'We encountered a problem while carrying out your operation, please try again later.';

    static string $INSUFFICIENT_PLEDGE = 'The saving selected will not be enough to repay your loan.';

    static string $PLEDGE_ALREADY_USED = 'The saving goal has already been pledged for another loan.';

    static string $LOAN_MODEL_ERROR = 'Loan goal not found or refunded.';

    static string $USER_OR_GROUP_MODEL_ERROR = 'User or  Group not found.';

    static string $MEMBER_BALANCE_ERROR = 'Your balance or the balance of the user you want to add is insufficient.';

    static string $BAD_DATE = 'Deadline too close or already passed, please enter a date at least three days in advance.';

    static string $ADD_MEMBER_CONFLICT = 'You cannot add yourself..';

    static string $USER_MODEL_ERROR = 'User model not found.';

    static string $TRANSFER_CONFLICT = 'You cannot send money to yourself..';

    static string $ERROR_MEMBER = 'This member already exist in this group..';

    static string $GROUP_INSERTION_ERROR = 'You cannot enter a group in normal mode on your own.';



    static string $PAYMENT_ER101 = 'Invalid phone number. Ensure the number starts with the country code. e.g +237xxxxxxxxx.';

    static string $PAYMENT_ER102 = 'Unsupported Carrier phone number. Currently, only MTN and Orange phone numbers are accepted for mobile money.';

    static string $PAYMENT_ER201 = 'Invalid amount. Decimal numbers are NOT allowed. The Amount can be sent as integer or string.';

    static string $PAYMENT_ER301 = 'Insufficient balance. Trying to withdraw an amount which is above your current balance for the specific carrier.';

    static string $TRANSACTION_STATUS_UPDATER_ERROR = 'The reference or status or external reference parameters cannot be found in your query..';

}
