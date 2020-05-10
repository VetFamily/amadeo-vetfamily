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

function getDaysObjectif($dateStart, $dateEnd)
{
  $nbOfDays = 0;
  $dateMAJ = explode("/", Session::get('date_maj'));
  $year = (date('Y') != $dateMAJ[2]) || ((new DateTime($dateMAJ[2] . '-' . $dateMAJ[1] . '-' . $dateMAJ[0])) < (new DateTime(date('Y') . '-02-05'))) ?  date('Y') - 1 : date('Y');
  $today = new Carbon("{$year}-$dateMAJ[1]-" . ($dateMAJ[0] > 15 ? 15 : 1));
  $dateStart = new Carbon($dateStart);
  $dateEnd = new Carbon($dateEnd);
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
