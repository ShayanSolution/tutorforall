<?php

namespace App\Models;
use Illuminate\Support\Carbon;

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

    public function getCreatedAtAttribute($value)
    {
        return Carbon::createFromTimestamp(strtotime($value))
            ->timezone(env('APP_TIMEZONE'))
            ->toDateTimeString()
            ;
    }

    public function notification(){
        return $this->belongsTo(Notification::class);
    }
}
