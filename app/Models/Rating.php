<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'rating',
        'review',
        'session_id',
        'user_id'
    ];

    public function session()
    {
        return $this->belongsTo('App\Models\Session');
    }

    public static function generateErrorResponse($validator){
        $response = null;
        if ($validator->fails()) {
            $response = $validator->errors()->toArray();
            $response['error'] = $validator->errors()->toArray();
            $response['code'] = 500;
            $response['message'] = 'Error occured';
        }
        else{
            $response['code'] = 200;
            $response['message'] = 'operation completed successfully';
        }
        return $response;
    }
}
