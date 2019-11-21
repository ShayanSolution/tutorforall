<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 11/21/2019
 * Time: 5:01 PM
 */

namespace App\Services\CostCalculation;

use App\Models\Category;


class CategoryCost
{
    public $categoryCost;
    public function __construct()
    {
        $this->categoryCost = 0;
    }

    public function execute($categoryId, $hourlyRate){
        $category = Category::where('id', $categoryId)->first();
        $this->categoryCost = ($category->percentage/100) * $hourlyRate;
        return $this->categoryCost;
    }

}
