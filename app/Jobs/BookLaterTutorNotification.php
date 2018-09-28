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

class BookLaterTutorNotification extends Job
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
        $studentAge = Carbon::parse($student->dob)->age;

        $program = Programme::find($session->programme_id);
        $subject = Subject::find($session->subject_id);

        if(!empty($tutor->device_token)){
            //notification message
            $message = PushNotification::Message(
                'Your session will start with '.$student->firstName.' '.$student->lastName.' in an hour.',
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
                        'student_name' => $student->firstName." ".$student->lastName,
                        'student_id' => $student->id,
                        'class_name' => isset($program->name)?$program->name:'',
                        'subject_name' => isset($subject->name)?$subject->name:'',
                        'class_id' => $program->id,
                        'subject_id' => $subject->id,
                        'is_group' => $session->is_group,
                        'longitude' =>  $session->longitude,
                        'latitude' => $session->latitude,
                        'session_location' => $session->session_location,
                        'Datetime' => $session->book_later_at,
                        'Age' => $studentAge>0?$studentAge:'',
                        'Profile_Image' => !empty($student->profileImage)?URL::to('/images').'/'.$student->profileImage:'',
                    ))
                ));

            if($tutor->device_type == 'android') {
                PushNotification::app('appNameAndroid')->to($tutor->device_token)->send($message);
            }else{
                PushNotification::app('appNameIOS')->to($tutor->device_token)->send($message);
            }
        }

    }
}
