<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeakFactor extends Model
{
    protected $table = 'peak_factors';

    protected $fillable = ['class_id', 'subject_id', 'is_group', 'category_id', 'experience'];
}
