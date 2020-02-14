<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationStatus extends Model
{
    protected $fillable = [
        'notification_id',
        'receiver_id',
        'notification_type',
        'read_status'
    ];
    protected $table = 'notifications_status';

    public function notification(){
        return $this->belongsTo(Notification::class);
    }
}
