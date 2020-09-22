<?php

namespace App\Helpers;
use App\Models\User;
use Jcf\Geocode\Geocode;
class ReverseGeocode{
    protected static $ass = [
        'route' => 'area',
        'sublocality_level_1' => 'area',
        'locality' => 'city',
        'administrative_area_level_1' => 'province',
        'country' => 'country',
    ];
    public static function reverseGeoCoding($latitude, $longitude, $id){
        $user = User::find($id);
        if (!$user){
            return;
        }
        $getResponse = Geocode::make()->latLng($latitude,$longitude);
//        $getResponse = Geocode::make()->latLng(32.493748,74.534727);
//dd($getResponse);
        if ($getResponse){
            foreach ($getResponse->response->address_components as $response){
                $type = $response->types[0];
                if (key_exists($type, self::$ass)) {
                    $user->{self::$ass[$type]} = $response->long_name;
                }
            }
            if ($getResponse->formattedAddress()){
                $user->reverse_geocode_address = $getResponse->formattedAddress();
            }
            $user->save();
        }
    }
}