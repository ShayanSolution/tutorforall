<?php

namespace App\Jobs;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
//Models
use App\Models\Session;
use App\Models\User;

class StartSessionNotification extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $sessionId;

    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $sessionId = $this->sessionId;

        $session = Session::find($this->sessionId);

        //get student device token to send notification
        $user = User::where('id','=',$session->student_id)->select('users.*','device_token as token')->first();
        if(!empty($user->token)){

            //notification message
            $message = PushNotification::Message(
                'Your session started.',
                array(
                    'badge' => 1,
                    'sound' => 'example.aiff',
                    'actionLocKey' => 'Action button title!',
                    'locKey' => 'localized key',
                    'locArgs' => array(
                        'localized args',
                        'localized args',
                    ),
                    'launchImage' => 'image.jpg',
                    'custom' => array('custom_data' => array(
                        'notification_type' => 'session_started',
                        'session_id' => (string)$sessionId,
                    ))
                ));

            if($user->device_type == 'android') {
                PushNotification::app('appNameAndroid')->to($user->token)->send($message);
            }else{
                //TODO: Implement from IOS side
                //PushNotification::app('appNameIOS')->to($user->token)->send($message);
            }
        }

    }
}
