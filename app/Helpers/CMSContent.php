<?php
/**
 * Created by PhpStorm.
 * User: shayansolutions
 * Date: 24-Sep-18
 * Time: 6:00 PM
 */

namespace App\Helpers;
use App\Models\CMS;
use Twilio\Rest\Client;
use Twilio\Jwt\ClientToken;
use Twilio\Exceptions\TwilioException;


class CMSContent
{
    public static function getWhatsAppSMS($user_role_id){
        $content = CMS::where('type', 'whatsapp_sms')->where('user_role_id', $user_role_id)->first();
        return strip_tags($content->content);
    }

    public static function getHomePageNote($user_role_id){
        $content = CMS::where('type', 'home_page_note')->where('user_role_id', $user_role_id)->first();
        return $content->content;
    }

    public static function getSessionInstructions($user_role_id){
        $content = CMS::where('type', 'session_instructions')->where('user_role_id', $user_role_id)->first();
        return $content->content;
    }
}