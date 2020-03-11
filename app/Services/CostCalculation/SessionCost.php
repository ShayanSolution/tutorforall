<?php

namespace App\Services\CostCalculation;


class SessionCost{

    public function execute($durationInHour, $findSession){

        $hourlyRatePastFirstHour = (int)$findSession->hourly_rate_past_first_hour;

        if ($durationInHour > 1){
            $excludeFirstHour = $durationInHour - 1;
            $costNextHours = $hourlyRatePastFirstHour * $excludeFirstHour;
            $costFirstHour = $findSession->hourly_rate;
            $totalCostAccordingToHours = $costFirstHour + $costNextHours;
        } else {
            $costPerHour = $findSession->hourly_rate;
            $totalCostAccordingToHours = $costPerHour * $durationInHour;
        }

        return $totalCostAccordingToHours;
    }


}
