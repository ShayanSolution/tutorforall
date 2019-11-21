<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 11/21/2019
 * Time: 5:02 PM
 */

namespace App\Services\CostCalculation;

use App\Models\PercentageCostForMultistudentGroup;

class GroupCost
{

    public function __construct()
    {
    }

    public function execute($groupCount, $hourlyRate, CategoryCost $categoryCost){
        $PercentageCostForMultistudentGroup = PercentageCostForMultistudentGroup::where('number_of_students', $groupCount)->first();
        $calculationsForGroup = ($PercentageCostForMultistudentGroup->percentage/100) * $hourlyRate;
        return $calculationsForGroup + $categoryCost->categoryCost + $hourlyRate;
    }

}
