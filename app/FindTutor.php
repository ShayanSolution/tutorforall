<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FindTutor extends Model
{
    protected $fillable = [
        'student_id', 'class_id', 'subject_id', 'is_group', 'longitude', 'latitude', 'status'
    ];
}
