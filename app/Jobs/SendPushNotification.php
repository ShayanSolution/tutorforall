<?php

namespace App\Jobs;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Log;
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
        $student_id = $this->data['student_id'];
        $programme_id = $this->data['class_id'];
        $subject_id = $this->data['subject_id'];

        $deviceTokenArray = array();
        $class = Programme::find($programme_id);
        $subject = Subject::find($subject_id);


        $userAge = Carbon::parse($this->student->dob)->age;


        for($j=0;$j<count($this->tutorsIds);$j++){
            //get tutor device token to send notification
            $user = User::where('id','=',$this->tutorsIds[$j])->select('users.*','device_token as token')->first();
            if(!empty($user->token)){
                //save session record
                $session_data['tutor_id'] =  $this->tutorsIds[$j];
                $session_data['student_id'] =  $student_id;
                $session_data['programme_id'] =  $programme_id;
                $session_data['subject_id'] =  $subject_id;
                $session_data['status'] =  'pending';
                $session_data['latitude'] =  $this->data['latitude'];
                $session_data['longitude'] =  $this->data['longitude'];
                $session_data['is_group'] = $this->data['is_group'];
                if(isset($this->data['group_members'])){
                    $session_data['group_members'] = $this->data['group_members'];
                }else{
                    $session_data['group_members'] = 0;
                }
                $session_data['started_at'] = TimeZoneHelper::timeConversion(Carbon::now(), 0);


                $session = new Session();
                $session_request = $session->addSession($session_data);

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
                            'session_id' => $session_request->id,
                            'Student_Name' => $this->student->firstName." ".$this->student->lastName,
                            'Student_id' => $this->student->id,
                            'Class_Name' => isset($class->name)?$class->name:'',
                            'Subject_Name' => isset($subject->name)?$subject->name:'',
                            'Class_id' => $programme_id,
                            'Subject_id' => $subject_id,
                            'IS_Group' => $this->student->is_group,
                            'Longitude' => $this->student->longitude,
                            'Latitude' => $this->student->latitude,
                            'Datetime' => Carbon::now()->toDateTimeString(),
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
