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
}
