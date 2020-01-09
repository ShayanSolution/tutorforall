<?php
/**
 * Created by PhpStorm.
 * User: shayansolutions
 * Date: 24-Sep-18
 * Time: 6:00 PM
 */

namespace App\Helpers;
use Twilio\Rest\Client;
use Twilio\Jwt\ClientToken;
use Twilio\Exceptions\TwilioException;


class TwilioHelper
{
    public static function sendCodeSms($toNumber, $code, $roleId){
        $isEnvProd = app()->environment() == 'production';
        $accountSid = config('twilio.accountId');
        $authToken  = config('twilio.authKey');
        $twilioNumber = config('twilio.twilioNumber');
        $twilioTestNumber = config('twilio.twilioTestNumber');
//        Log::info("sending code to: ".$toNumber);
        $client = new Client($accountSid, $authToken);
        if ($roleId == 2){
            $appNameForCode = 'Tootar Teacher';
        } else {
            $appNameForCode = 'Tootar';
        }
        try {

            // Use the client to do fun stuff like send text messages!
            $response = $client->messages->create(
            // the number you'd like to send the message to
                $isEnvProd ? $toNumber : config('twilio.testReceivingNumber'),
                array(
                    // A Twilio phone number you purchased at twilio.com/console
                    'from' => $isEnvProd ? $twilioNumber : $twilioTestNumber,
                    // the body of the text message you'd like to send
                    'body' => "Welcome to $appNameForCode. Your verification code is $code"
                )
            );
            if($response->sid){
//                Log::info("code sent to: ".$toNumber);
                return $response->sid;
            }else{
//                Log::info("code sending failed to: ".$toNumber);
                return FALSE;
            }

        }
        catch (TwilioException $e)
        {
//            Log::info("code sending failed to: ".$toNumber);
            return FALSE;
        }
    }
}