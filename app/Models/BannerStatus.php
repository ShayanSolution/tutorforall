<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannerStatus extends Model
{
    protected $fillable = [
        'banner_id',
        'receiver_id',
        'is_read'
    ];
    protected $table = 'banners_status';

    public function banner(){
        return $this->belongsTo(Banner::class);
    }
}
