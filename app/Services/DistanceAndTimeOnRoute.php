<?php

namespace App\Services;
use Log;

class DistanceAndTimeOnRoute{

    public function execute($student, $tutor, $sessionData, $isHome = 0){

        if($isHome == 0)
        {
            $origin = [
                'latitude'  =>  (string)$student->latitude,
                'longitude' =>  (string)$student->longitude
            ];
        }else{
            $origin = [
                'latitude'  =>  (string)$tutor->latitude,
                'longitude' =>  (string)$tutor->longitude
            ];
        }

        $destination = [
            'latitude'  =>  (string)$sessionData['latitude'],
            'longitude' =>  (string)$sessionData['longitude']
        ];


        return $this->getDistanceAndTime(
            'metric',
            $origin,
            $destination
        );
    }

    /**
     * @param $unit
     * @param $origin
     * @param $destination
     * @return array
     */
    private function getDistanceAndTime($unit, $origin, $destination){

        $url = 'https://maps.googleapis.com/maps/api/directions/json?';

        $url .= 'units='.$unit.'&';
        $url .= 'origin='.$destination['latitude'].','.$destination['longitude'].'&';
        $url .= 'destination='.$origin['latitude'].','.$origin['longitude'].'&';
        $url .= 'key='.env('GOOGLE_API_KEY');

        $ch = curl_init();

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
        );

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);

        $legOfGoogleRoutes = json_decode($result)->routes[0]->legs[0];

        Log::info("Complete Object:",$result);
        Log::info("calculated distance:",$legOfGoogleRoutes->distance->text);
        Log::info("calculated time:",$legOfGoogleRoutes->duration->text);

        return [
            'duration'  =>  $legOfGoogleRoutes->duration->text,
            'distance'  =>  $legOfGoogleRoutes->distance->text,
        ];
    }
}
