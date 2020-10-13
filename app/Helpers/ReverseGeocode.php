<?php

namespace App\Helpers;
use App\Models\User;
use Jcf\Geocode\Geocode;
class ReverseGeocode{
    protected static $ass = [
        'route' => 'area',
        'political' => 'area1',
        'locality' => 'city',
        'administrative_area_level_1' => 'province',
        'country' => 'country',
    ];
//    public static function reverseGeoCoding($latitude, $longitude, $id){
//        $user = User::find($id);
//        if (!$user){
//            return;
//        }
//        $getResponse = Geocode::make()->latLng($latitude,$longitude);
////        $getResponse = Geocode::make()->latLng(32.493748,74.534727);
//        self::reverseGeoCodingSession($latitude,$longitude);
//dd($getResponse);
//        if ($getResponse){
//            foreach ($getResponse->response->address_components as $response){
//                $type = $response->types[0];
//                if (key_exists($type, self::$ass)) {
//                    $user->{self::$ass[$type]} = $response->long_name;
//                }
//            }
//            if ($getResponse->formattedAddress()){
//                $user->reverse_geocode_address = $getResponse->formattedAddress();
//            }
//            $user->save();
//        }
//    }

    public static function reverseGeoCoding($latitude, $longitude){
        $addressArray = [];
        $areaArray = [];
        $getResponse = Geocode::make()->latLng($latitude,$longitude);
//        $getResponse = Geocode::make()->latLng(32.493748,74.534727);

        if ($getResponse){
            foreach ($getResponse->response->address_components as $response){
                $type = $response->types[0];
                if (key_exists($type, self::$ass)) {
                    if ($type == 'country') {
                        $addressArray['country'] = $response->long_name;
                    }
                    if ($type == 'administrative_area_level_1') {
                        $addressArray['province'] = $response->long_name;
                    }
                    if ($type == 'locality') {
                        $addressArray['city'] = $response->long_name;
                    }
                    if ($type == 'route') {
                        $addressArray['area']= $areaArray['area'] = $response->long_name;
                    }
                    if ($type == 'political' && $response->types[2] == 'sublocality_level_2') {
                        $addressArray['area1'] = $areaArray['area1'] = $response->long_name;
                    }
                    if ($type == 'political' && $response->types[2] == 'sublocality_level_1') {
                        $addressArray['area2'] = $areaArray['area2'] = $response->long_name;
                    }
                }
            }
            $addressArray['full_area'] = implode(' ', $areaArray);
            if ($getResponse->formattedAddress()){
                $addressArray['reverse_geocode_address'] = $getResponse->formattedAddress();
            }
            return $addressArray;

        }
    }
}