<?php

namespace App;
use \GuzzleHttp\Exception\RequestException;

class Notify
{
    public function sendNotification($userId, $title, $message, $data=[]) {
        
        $url = "https://fcm.googleapis.com/fcm/send";
        $serverKey = "";        
        $headers = ["headers" => [
                'Authorization' => "key=" .$serverKey,
                'Content-Type' => 'application/json'
                ],];
        $client = new \GuzzleHttp\Client($headers);
        
        if($userId != NULL) {
            $userDevice = \DB::table('users')->where('id', $userId)->first();
            $sendToDevice = $userDevice->device_token;
        }
        
        $notificationData = array('title'=>$title,'body'=>$message);
        $payloadData = array_merge($notificationData, $data);
        
        $fields = array('to'=>$sendToDevice,
//            'notification'=> $payloadData, // Please donot enable
            'data'=> $payloadData
        );

        try{
            $response = $client->post($url, ["body"=> json_encode($fields)]);

        } catch (RequestException $e) {
            // To catch exactly error 400 use 
            if ($e->getResponse()->getStatusCode() == '400') {
                    echo "Got response 400";
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
