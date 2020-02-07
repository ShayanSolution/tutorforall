<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public function notification_status(){
        return $this->hasMany(NotificationStatus::class);
    }
}
