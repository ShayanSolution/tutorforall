<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LastLogin extends Model
{
    protected $fillable = [
        'user_id',
        'role_id'
    ];
}
