<?php

namespace App\Jobs;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Log;
//helpers
use TimeZoneHelper;
//Models
use App\Models\Session;
use App\Models\User;
use App\Models\Programme;
use App\Models\Subject;

class SendPushNotification extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $tutorsIds;
    protected $data;
    protected $student;

    public function __construct($data, $tutorsIds, $student)
    {
        $this->tutorsIds = $tutorsIds;
        $this->data = $data;
        $this->student = $student;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $studentId = $this->data['student_id'];
        $programmeId = $this->data['class_id'];
        $subjectId = $this->data['subject_id'];

        $deviceTokenArray = array();
        $class = Programme::find($programmeId);
        $subject = Subject::find($subjectId);


        $userAge = Carbon::parse($this->student->dob)->age;


        for($j=0;$j<count($this->tutorsIds);$j++){
            //get tutor device token to send notification
            $user = User::where('id','=',$this->tutorsIds[$j])->select('users.*','device_token as token')->first();
            if(!empty($user->token)){
                //save session record
                $sessionData['tutor_id'] =  $this->tutorsIds[$j];
                $sessionData['student_id'] =  $studentId;
                $sessionData['programme_id'] =  $programmeId;
                $sessionData['subject_id'] =  $subjectId;
                $sessionData['status'] =  'pending';
                $sessionData['latitude'] =  $this->data['latitude'];
                $sessionData['longitude'] =  $this->data['longitude'];
                $sessionData['is_group'] = $this->data['is_group'];
                $sessionDateTime = Carbon::now()->toDateTimeString();
                $dateTime = explode(" ",$sessionDateTime);
                if(isset($this->data['group_members'])){
                    $sessionData['group_members'] = $this->data['group_members'];
                }else{
                    $sessionData['group_members'] = 0;
                }
                $sessionData['started_at'] = TimeZoneHelper::timeConversion(Carbon::now(), 0);

                if(isset($this->data['session_location'])){

                    $sessionData['session_location'] = $this->data['session_location'];
                }else{
                    $sessionData['session_location'] = '';
                }

                if(isset($this->data['book_later_time']) && isset($this->data['book_later_date'])){
                    $sessionData['book_later_at'] = $this->data['book_later_date'].' '.date("H:i", strtotime($this->data['book_later_time']));
                    $sessionDateTime = $sessionData['book_later_at'];
                    $dateTime = explode(" ",$sessionDateTime);
                }

                $session = new Session();
                $sessionRequest = $session->addSession($sessionData);

                $deviceTokenArray[] = $user->token;
                //notification message
                $message = PushNotification::Message(
                    $this->student->firstName.' '.$this->student->lastName.' wants a session with you',
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
                            'notification_type' => 'session_request',
                            'session_id' => (string)$sessionRequest->id,
                            'Student_Name' => $this->student->firstName." ".$this->student->lastName,
                            'Student_id' => $this->student->id,
                            'Class_Name' => isset($class->name)?$class->name:'',
                            'Subject_Name' => isset($subject->name)?$subject->name:'',
                            'Class_id' => $programmeId,
                            'Subject_id' => $subjectId,
                            'IS_Group' => (int)$sessionData['is_group'],
                            'Longitude' =>  $sessionData['longitude'],
                            'Latitude' => $sessionData['latitude'],
                            'Session_Location' => $sessionData['session_location'],
                            'Datetime' => $sessionDateTime,
                            'date' => $dateTime[0],
                            'time' => $dateTime[1],
                            'Age' => $userAge>0?$userAge:'',
                            'Profile_Image' => !empty($this->student->profileImage)?URL::to('/images').'/'.$this->student->profileImage:'',
                        ))
                    ));

                if($user->device_type == 'android') {
                    PushNotification::app('appNameAndroid')->to($user->token)->send($message);
                }else{
                    PushNotification::app('appNameIOS')->to($user->token)->send($message);
                }
            }
        }

    }
}
