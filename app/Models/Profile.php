<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'name',
        'is_mentor',
        'is_deserving',
        'meeting_type_id',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function meetingType()
    {
        return $this->belongsTo('App\Models\MeetingType');
    }
}
