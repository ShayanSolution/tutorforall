<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'name',
        'total_cost',
        'session_id',
        'subscription_id',
        'transaction_id',
    ];
}
