<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PercentageCostForMultistudentGroup extends Model
{
    protected $table = 'percentage_cost_for_multistudent_group';

    protected $fillable = ['number_of_students', 'percentage', 'is_active', 'deleted_at'];
}
