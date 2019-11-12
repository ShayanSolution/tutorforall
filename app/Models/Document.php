<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    public $timestamps = false;

    protected $hidden = ['verified_at', 'verified_by', 'storage_path', 'created_at', 'updated_at'];

    protected $fillable = [
        'tutor_id', 'title', 'path', 'status', 'document_type',
        'rejection_reason', 'verified_by', 'verified_at',
        'storage_path'
    ];

    public function getStatusAttribute($value){

        if($value == 0)
            $status = 'Rejected';
        else if($value == 1)
            $status = 'Accepted';
        else
            $status = 'Pending';

        return $status;
    }

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
