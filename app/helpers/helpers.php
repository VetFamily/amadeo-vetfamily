<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

function getNbDaysOfPeriod($year, $month1, $month2)
{
  $result = 0;
  for ($i = $month1; $i <= $month2; $i++) {
    $result += cal_days_in_month(CAL_GREGORIAN, $i, $year);
  }
  return $result;
}

function getDaysObjectif($dateStart, $dateEnd, $maxDatePurchases)
{
  $nbOfDays = 0;
  $dateStart = new Carbon($dateStart);
  $dateEnd = new Carbon($dateEnd);
  $maxDatePurchases = new Carbon($maxDatePurchases);
  if (($maxDatePurchases->year == date('Y')) & ($maxDatePurchases->month == date('m')))
  {
    $today = $maxDatePurchases->firstOfMonth()->addDays(14);
  } else 
  {
    $today = $maxDatePurchases->firstOfMonth()->addMonths(1);
  }

  if ($today->greaterThan($dateEnd)) {
    $dateEnd = $dateEnd->endOfMonth();
    $nbOfDays = ($dateEnd->diffInDays($dateStart)) + 1;
  } else {
    $nbOfDays = $today->diffInDays($dateStart);
  }
  return $nbOfDays;
}

/**
 * Gets the number of days (the last day in the month)
 *
 * @param int $year The year
 * @param int $month The month
 * @return void
 */
function getLastDay($year, $month) {
  return (new Carbon("{$year}-{$month}-1"))->daysInMonth;
}
