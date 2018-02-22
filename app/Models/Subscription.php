<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'name',
        'cost_hourly',
        'group_costing',
        'status',
        'meeting_type_id',
    ];

    protected $casts = [
      'group_costing' => 'array'
    ];
}
