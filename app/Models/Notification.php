<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Notification extends Model
{
    public function getCreatedAtAttribute($value)
    {
        return Carbon::createFromTimestamp(strtotime($value))
            ->timezone(env('APP_TIMEZONE'))
            ->toDateTimeString()
            ;
    }
    public function notification_status(){
        return $this->hasMany(NotificationStatus::class);
    }
}
