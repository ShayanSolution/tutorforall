<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingType extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];
}
