<?php

namespace App\Jobs;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Log;
//helpers
use TimeZoneHelper;
//Models
use App\Models\User;
use App\Models\Session;
use App\Models\Subject;
use App\Models\Programme;

class BookLaterStudentNotification extends Job
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
        $session = Session::find($this->sessionId);

        $tutor = User::find($session->tutor_id);
        $student = User::find($session->student_id);

        $program = Programme::find($session->programme_id);
        $subject = Subject::find($session->subject_id);

        if(!empty($student->device_token)){
            //get tutor device token
            $message = PushNotification::Message(
                'Your session will start with '.$tutor->firstName.' '.$tutor->lastName.' in an hour.',
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
                        'session_id' => $this->sessionId,
                        'tutor_name' => $tutor->firstName." ".$tutor->lastName,
                        'class_name' => $program->name,
                        'subject_name' => $subject->name,
                        'class_id' => $program->id,
                        'subject_id' => $subject->id,
                        'is_group' => $session->is_group,
                        'session_lat' => $session->latitude,
                        'session_long' => $session->longitude,
                        'session_location' => $session->session_location,
                        'Profile_Image' => !empty($tutor->profileImage)?URL::to('/images').'/'.$tutor->profileImage:'',
                    ))
                ));
            if($student->device_type == 'android') {
                PushNotification::app('appNameAndroid')->to($student->token)->send($message);
            }else{
                PushNotification::app('appStudentIOS')->to($student->token)->send($message);
            }
        }
    }
}
