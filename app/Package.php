<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'packages';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'status',
        'deleted_at',
    ];

    public function getPackageRate($id, $is_group, $members)
    {
        $package = Package::where(['id'=>$id, 'is_active'=> 1])->first();
        if($is_group == 0){
            return $package->hourly_rate;
        }
        else{
            if($members == 2){
                $division = $package->extra_percentage_for_group_of_two/$package->hourly_rate;
                $percentage = number_format( $division * 100, 0 );
                return $percentage+$package->hourly_rate;
            }elseif ($members == 3){

                $division = $package->extra_percentage_for_group_of_three/$package->hourly_rate;
                $percentage = number_format( $division * 100, 0 );
                return $percentage+$package->hourly_rate;
            }elseif ($members == 4){

                $division = $package->extra_percentage_for_group_of_four/$package->hourly_rate;
                $percentage = number_format( $division * 100, 0 );
                return $percentage+$package->hourly_rate;
            }else{
                return $package->hourly_rate;
            }
        }
    }
}
