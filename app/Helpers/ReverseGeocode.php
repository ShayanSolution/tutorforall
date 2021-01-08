<?php

namespace App\Helpers;
use App\Models\User;
use Illuminate\Support\Facades\Log;
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

    public static function calculateDistanceInKM($tutorLat, $tutorLong, $sessionLat, $sessionLong) {
        if (($tutorLat == $sessionLat) && ($tutorLong == $sessionLong)) {
            return 0;
        }
        else {
            $theta = $tutorLong - $sessionLong;
            $dist = sin(deg2rad($tutorLat)) * sin(deg2rad($sessionLat)) +  cos(deg2rad($tutorLat)) * cos(deg2rad($sessionLat)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $milesInToKM = $miles * 1.609344;
            return $milesInToKM;
        }
    }

    public static function distanceByGoogleApi($tutorLat, $tutorLong, $sessionLat, $sessionLong){
        $unit = 'metric';
        $origin = [
            'latitude'  =>  (string)$tutorLat,
            'longitude' =>  (string)$tutorLong
        ];
        $destination = [
            'latitude'  =>  (string)$sessionLat,
            'longitude' =>  (string)$sessionLong
        ];
        $url = 'https://maps.googleapis.com/maps/api/directions/json?';

        $url .= 'units='.$unit.'&';
        $url .= 'destination='.$destination['latitude'].','.$destination['longitude'].'&';
        $url .= 'origin='.$origin['latitude'].','.$origin['longitude'].'&';
        $url .= 'key='.env('GOOGLE_API_KEY');

        $ch = curl_init();

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
        );

        Log::info("Requested Direction URL:".$url);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);

        $legOfGoogleRoutes = json_decode($result)->routes[0]->legs[0];

        Log::info("Complete Object:",(array)$result);
        Log::info("calculated distance:".$legOfGoogleRoutes->distance->value);
        Log::info("calculated time:".$legOfGoogleRoutes->duration->text);

        $duration = $legOfGoogleRoutes->distance->value;

        return $duration;

    }
}