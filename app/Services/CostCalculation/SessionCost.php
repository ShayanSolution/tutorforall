<?php

namespace App\Services\CostCalculation;


class SessionCost{

    public function execute($durationInHour, $durationInMinute, $findSession){

        if ($durationInHour == 1 || $durationInHour > 1){
            $costFirstHour = $findSession->hourly_rate;
            $hourlyRatePastFirstHour = $findSession->hourly_rate_past_first_hour;
            // Calculations
            $hoursCost = $costFirstHour * $durationInHour;
            $minutesCost = $hourlyRatePastFirstHour * $durationInMinute;
            $totalCostAccordingToHours = round($hoursCost + $minutesCost);
        } else {
            // for 1st hour
            $durationInHour = 1;
            $costPerHour = $findSession->hourly_rate;
            $totalCostAccordingToHours = $costPerHour * $durationInHour;
        }

        return $totalCostAccordingToHours;
    }


}
