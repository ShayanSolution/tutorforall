<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Push{

    public static function handle($title, $body, $customData, $user){
        $data  = array(
            'title' => $title,
            'body'  => $body,
            'badge' => 1,
            'sound' => 'example.aiff',
            'actionLocKey' => 'Action button title!',
            'locKey' => 'localized key',
            'locArgs' => array(
                'localized args',
                'localized args',
            ),
            'launchImage' => 'image.jpg'
        );

        if($user->device_type == 'android')
            $data['custom'] = array('custom_data' => $customData);
        else
            $data['custom_data'] = json_encode($customData);


        $optionBuilder = new \LaravelFCM\Message\OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);

        $notificationBuilder = new \LaravelFCM\Message\PayloadNotificationBuilder($title);
        $notificationBuilder->setBody($body)
            ->setChannelId('FirebaseNotifications')
            ->setSound('default');

        $dataBuilder = new \LaravelFCM\Message\PayloadDataBuilder();
        $dataBuilder->setData($data);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();


        Log::info('Tutor request push notification sent at '.Carbon::now(). ' to all device');

        Log::info("Send push notification on device : ".$user->device_token);
        $notification = $user->device_type == 'android' ? null : $notification;
        $downstreamResponse = \LaravelFCM\Facades\FCM::sendTo($user->device_token, $option, $notification , $data);

        $failedTokens = $downstreamResponse->tokensWithError();
        if(!empty($failedTokens)){
            foreach ($failedTokens as $failedToken){
                Log::info('Failed token : '.$failedToken);
            }
        }
    }

}