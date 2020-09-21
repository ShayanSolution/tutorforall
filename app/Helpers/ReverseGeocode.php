<?php

namespace App\Helpers;
use App\Models\User;
use Jcf\Geocode\Geocode;
class ReverseGeocode{
    protected static $ass = [
        'route' => 'city',
        'administrative_area_level_1' => 'state',
        'country' => 'country'
    ];
    public static function reverseGeoCoding($latitude, $longitude, $id=247){
        $user = User::find($id);
        if (!$user){
            return;
        }
        $getResponse = Geocode::make()->latLng(32.493748,74.534727);

        if ($getResponse){
            foreach ($getResponse->response->address_components as $response){
                $type = $response->types[0];
                if (key_exists($type, self::$ass)) {
                    $user->{self::$ass[$type]} = $response->long_name;
                }
            }
            dd($user);
        }
        dd($response->raw()->address_components[1]->types[1]);
//        dd($response->raw()->address_components[0]->long_name);
//        dd($response->raw()->address_components[1]);
        dd($response->response, $response->formattedAddress(), $response->locationType(), $response);
    }
}