<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $fillable = [
        'name',
        'student_id',
        'tutor_id',
        'programme_id',
        'subject_id',
        'subscription_id',
        'meeting_type_id',
        'is_group',
        'group_members',
        'status',
        'started_at',
        'ended_at',
        'duration',
    ];

    public function student()
    {
        return $this->belongsTo('App\Models\User', 'student_id');
    }

    public function tutor()
    {
        return $this->belongsTo('App\Models\User', 'tutor_id');
    }

    public function programme()
    {
        return $this->belongsTo('App\Models\Programme');
    }

    public function meetingType()
    {
        return $this->belongsTo('App\Models\MeetingType');
    }

    public function rating()
    {
        return $this->hasOne('App\Models\Rating');
    }

    public function invoice()
    {
        return $this->hasOne('App\Models\Invoice');
    }
}
