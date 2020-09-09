<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLocation extends Model
{
    protected $fillable = [
        'user_id',
        'place_id',
        'place_name',
        'place_address',
        'place_detail',
        'saved_name',
        'latitude',
        'longitude',
        'type'
    ];
}
