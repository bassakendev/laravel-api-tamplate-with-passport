<?php

namespace App\Utils;

use App\Enums\SavingGroupContributionFrequencyEnum;
use Carbon\Carbon;

abstract class DateUtils
{

    /**
     * This function takes as a parameter a string date in the format (Y-m-d) and returns the associated dateTime.
     *
     * @param string $date
     * @return Carbon
     */
    static public function stringToDateTime(string $date): Carbon
    {
        $timestamp = strtotime($date);
        $formattedDate = date('Y-m-d', $timestamp);
        $dateTime = Carbon::createFromFormat('Y-m-d', $formattedDate);

        return $dateTime;
    }


    /**
     * This function takes parameters of a frequency (day, month, week) and a number of periods then returns an associated end date.
     *
     * @param string $periode
     * @param string $frequence
     * @return Carbon
     */
    static public function sortFutureDate(int $periode, string $frequence): Carbon
    {
        $dateTime = now();

        switch ($frequence) {
            case SavingGroupContributionFrequencyEnum::DAILY->value:
                $dateTime->addDays($periode);
                break;
            case SavingGroupContributionFrequencyEnum::WEEKLY->value:
                $dateTime->addWeeks($periode);
                break;
            case SavingGroupContributionFrequencyEnum::MONTHLY->value:
                $dateTime->addMonths($periode);
                break;

            default:
                $dateTime->addDays($periode);
                break;
        }

        return $dateTime;
    }


    /**
     * This function takes parameters frequency (day, month, week), a number of periods adn date then returns an associated end date.
     *
     * @param string $periode
     * @param string $frequence
     * @param string $date
     * @return Carbon
     */
    static public function addPeriodes(string $frequence, string $date, int $periode = 1): Carbon
    {
        $dateTime = self::stringToDateTime($date);

        switch ($frequence) {
            case SavingGroupContributionFrequencyEnum::DAILY->value:
                $dateTime->addDays($periode);
                break;
            case SavingGroupContributionFrequencyEnum::WEEKLY->value:
                $dateTime->addWeeks($periode);
                break;
            case SavingGroupContributionFrequencyEnum::MONTHLY->value:
                $dateTime->addMonths($periode);
                break;

            default:
                $dateTime->addDays($periode);
                break;
        }

        return $dateTime;
    }
}
