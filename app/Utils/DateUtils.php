<?php

namespace App\Utils;

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

}
