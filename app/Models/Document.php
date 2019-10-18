<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'tutor_id', 'title', 'path', 'status',
        'rejection_reason', 'verified_by', 'verified_at'
    ];

    public function getPathAttribute($path){
        if(str_contains(app('url')->full(),'delete-tutors-document'))
            return $path;
        else
            return env('ASSET_BASE_URL').$path;
    }

    public function tutor(){
        return $this->belongsTo(User::class, 'tutor_id', 'id');
    }

}
