<?php

namespace App\Jobs;
use App\Helpers\Push;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
//helpers
use TimeZoneHelper;
//Models
use App\Models\Session;
use App\Models\User;
use App\Models\Programme;
use App\Models\Subject;
use Illuminate\Support\Facades\Config;

class SendPushNotification extends Job implements ShouldQueue
{
    use Queueable;
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
        Log::info('Tutor request push notification constructor called at '.Carbon::now());
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
        Log::info('Tutor request push notification called at '.Carbon::now());
        $studentId = $this->data['student_id'];
        $programmeId = $this->data['class_id'];
        $subjectId = $this->data['subject_id'];

        $class = Programme::find($programmeId);
        $subject = Subject::find($subjectId);


        $userAge = Carbon::parse($this->student->dob)->age;


        for($j=0;$j<count($this->tutorsIds);$j++){
            Log::info('Sending to tutor_id => '. $this->tutorsIds[$j]);
            //get tutor device token to send notification
            $user = User::where('id','=',$this->tutorsIds[$j])->select('users.*','device_token as token')->first();
            if(!empty($user->token)){
                //save session record
                $sessionData['tutor_id'] =  $this->tutorsIds[$j];
                $sessionData['student_id'] =  $studentId;
                $sessionData['programme_id'] =  $programmeId;
                $sessionData['subject_id'] =  $subjectId;
                $sessionData['session_sent_group'] =  $this->data['session_sent_group'];
                $sessionData['status'] =  'pending';

                if($this->data['is_home'] == 0){
                    $sessionData['latitude'] =  $user->latitude;
                    $sessionData['longitude'] =  $user->longitude;
                    $sessionData['session_location'] = $user->address;
                }
                else{
                    $sessionData['latitude'] =  $this->data['latitude'];
                    $sessionData['longitude'] =  $this->data['longitude'];
                    if(isset($this->data['session_location'])) {
                        $sessionData['session_location'] = $this->data['session_location'];
                    }
                    else{
                        $sessionData['session_location'] = '';
                    }
                }

                $sessionData['is_group'] = $this->data['is_group'];
                $sessionData['hourly_rate'] = $this->data['hourly_rate'];
                $sessionData['is_home'] = $this->data['is_home'];
                $sessionDateTime = Carbon::now()->toDateTimeString();
                $dateTime = explode(" ",$sessionDateTime);
                $sessionType = 'now';
                if(isset($this->data['group_members'])){
                    $sessionData['group_members'] = $this->data['group_members'];
                }else{
                    $sessionData['group_members'] = 0;
                }
                $sessionData['started_at'] = TimeZoneHelper::timeConversion(Carbon::now(), 0);


                if(isset($this->data['book_later_time']) && isset($this->data['book_later_date'])){
                    $sessionData['book_later_at'] = $this->data['book_later_date'].' '.date("H:i:s", strtotime($this->data['book_later_time']));
                    $sessionDateTime = $sessionData['book_later_at'];
                    $dateTime = explode(" ",$sessionDateTime);
                    $sessionType = 'later';
                }

                //From Android
                if(isset($this->data['book_type']) && $this->data['book_type'] == 'later'){
                    $sessionData['book_later_at'] = $this->data['session_time'];
                    $dateTime = explode(" ",$sessionData['book_later_at']);
                    $sessionType = 'later';
                }



                //Create new entry if session is not exist with same student_id, subject_id, class_id and date. else update the already existed entry.
                //Reject session request status is also updated. Means we will not have reject sessions history.
                $sessionData['subscription_id']= 3;
                $sessionData['meeting_type_id']= 1;
                $sessionRequest = Session::create($sessionData);
                //$session = new Session();
                //$sessionRequest = $session->createOrUpdateSession($sessionData);


                $title = Config::get('user-constants.APP_NAME');
                $isLocal = '';
                $body = $this->student->firstName.' '.$this->student->lastName.' wants a session with you'.$isLocal;
                $customData = array(
                    'notification_type' => 'session_request',
                    'session_id' => (string)$sessionRequest->id,
                    'Student_Name' => $this->student->firstName." ".$this->student->lastName,
                    'student_latitude' => $this->student->latitude,
                    'student_longitude' => $this->student->longitude,
                    'student_phone' => $this->student->phone,
                    'student_device_token' => $this->student->device_token,
                    'Student_id' => $this->student->id,
                    'Class_Name' => isset($class->name)?$class->name:'',
                    'Subject_Name' => isset($subject->name)?$subject->name:'',
                    'Class_id' => $programmeId,
                    'Subject_id' => $subjectId,
                    'IS_Group' => (int)$sessionData['is_group'],
                    'Group_Members' => (int)$sessionData['group_members'],
                    'IS_Home' => (int)$this->data['is_home'],
                    'Hourly_Rate' => (string)round($this->data['hourly_rate'], 0),
                    'Longitude' =>  (string)$sessionData['longitude'],
                    'Latitude' => (string)$sessionData['latitude'],
                    'Session_Location' => $sessionData['session_location'],
                    'Datetime' => $sessionDateTime,
                    'date' => $dateTime[0],
                    'time' => date("g:i a", strtotime($dateTime[1])),
                    'Age' => $userAge>0?$userAge:'',
                    'Profile_Image' => !empty($this->student->profileImage)?env('ASSET_BASE_URL').'/images/'.$this->student->profileImage:'',
                    'session_sent_group' => $sessionData['session_sent_group'],
                    'approaching_time' => $this->data['approaching_time'],
                    'distance' => $this->data['distance'],
                    'session_type' => $sessionType
                );
                $this->slackLog($user, env('TOOTAR_LOGGER_WEBHOOK_SLACK'));
                Push::handle($title, $body, $customData, $user);
            }
        }

    }
    private function slackLog($user, $url){
        $ch = curl_init($url);
        $data = array(
            'text' => 'Notification sent to '.$user->fullName.'. at '.Carbon::now()
        );
        $payload = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}
