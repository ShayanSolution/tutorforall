<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationStatus extends Model
{
    protected $table = 'notifications_status';

    public function notification(){
        return $this->belongsTo(Notification::class);
    }
}
