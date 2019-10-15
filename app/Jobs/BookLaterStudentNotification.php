<?php

namespace App\Jobs;
use App\Helpers\Push;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
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

            $title = Config::get('user-constants.APP_NAME');
            $body = 'Your session will start with '.$tutor->firstName.' '.$tutor->lastName.' in an hour.';
            //get tutor device token
            $customData = array(
                'notification_type' => 'student_session_start',
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
            );

            Push::handle($title, $body, $customData, $student);
        }
    }
}
