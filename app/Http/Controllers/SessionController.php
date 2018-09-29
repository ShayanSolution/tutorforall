<?php

namespace App\Http\Controllers;


use App\Wallet;
use Illuminate\Http\Request;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Support\Facades\URL;
use Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use App\Jobs\BookLaterTutorNotification;
use App\Jobs\BookLaterStudentNotification;

//Models
use App\Models\Profile;
use App\Models\Session;
use App\Models\User;
use App\Package;
use phpDocumentor\Reflection\Types\Null_;

/**
 * Class SessionController
 * @package App\Http\Controllers
 * Api will list all booked and ended sessions
 */
class SessionController extends Controller
{
    public function mySessions(Request $request){
        $data = $request->all();
        $session = new Session();
        //tutor session list
        if(isset($data['tutor_id']) && !empty($data['tutor_id'])){
            $tutor_id = $data['tutor_id'];
            $user_session = $session->getTutorSessionDetail($tutor_id);
            return response()->json(
                [
                    'data' => $user_session
                ]
            );
        }
        //student session list
        else{
            $student_id = $data['student_id'];
            $user_session = $session->getStudentSessionDetail($student_id);

        }
        if($user_session){
            $tutor_sessions = [];
            foreach ($user_session as $user){
                $user_details = User::where('id',$user->session_user_id)->first();
                $wallet = Wallet::where(['session_id'=>$user->session_id, 'type'=>'credit'])->first();
                if($wallet){
                    $paidAmount =  $wallet->amount;
                }
                $tutor_sessions[] = [
                    'FullName' => $user_details->firstName.' '.$user_details->lastName,
                    'FirstName' => $user_details->firstName,
                    'LastName' => $user_details->lastName,
                    'Experience' => (int)$user_details->experience,
                    'Date' => $user->Session_created_date,
                    'Lat' => $user->latitude,
                    'Long' => $user->longitude,
                    'User_Lat' => $user_details->latitude,
                    'User_Long' => $user_details->longitude,
                    'Status' => $user->session_status,
                    'Subject' => $user->s_name,
                    'Program' => $user->p_name,
                    'Student_Longitude' => $user->longitude,
                    'Student_Latitude' => $user->latitude,
                    'Session_Location' => is_null($user->session_location)?'':$user->session_location,
                    'Session_Duration' => $user->duration,
                    'Hour' => $user->duration,
                    'Price' => $user->rate,
                    'Session_id' => $user->session_id,
                    'session_status' => $user->session_status,
                    'paid_amount' => isset($paidAmount) ? $paidAmount : 0,
                    'Age' => Carbon::parse($user->dob)->age,
                    'Profile_image'=>!empty($user_details->profileImage)?URL::to('/images').'/'.$user_details->profileImage:''
                ];

            }

            return response()->json(
                [
                    'data' => $tutor_sessions
                ]
            );

        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find user session'
                ], 422
            );

        }
    }

    /**
     * Class SessionController
     * @package App\Http\Controllers
     * Api will list all reject and pending sessions
     */
    public function requestSessions(Request $request){
        $this->validate($request,[
            'tutor_id' => 'required',
        ]);
        $data = $request->all();
        //tutor session list
        $tutor_id = $data['tutor_id'];
        $session =  new Session();
        $user_session = $session->findRequestSession($tutor_id);
        if($user_session){
            $tutor_sessions = [];
            foreach ($user_session as $tutor){
                $student = User::where('id',$tutor->session_user_id)->first();
                $tutor_sessions[] = [
                    'Session_id' => $tutor->session_id,
                    'TutorName' => $tutor->firstName.' '.$tutor->lastName,
                    'StudentName' => $student->firstName.' '.$student->lastName,
                    'StudentFirstName' => $student->firstName,
                    'StudentLastName' => $student->lastName,
                    'TutorFirstName' => $tutor->firstName,
                    'TutorLastName' => $tutor->lastName,
                    'TutorAge' => Carbon::parse($tutor->dob)->age,
                    'StudentAge' => Carbon::parse($student->dob)->age,
                    'Price' => $tutor->rate,
                    'TutorID'=>$tutor->id,
                    'StudentID'=>$tutor->session_user_id,
                    'Date' => $tutor->Session_created_date,
                    'TutorLat' => $tutor->latitude,
                    'TutorLong' => $tutor->longitude,
                    'StudentLat' => $student->latitude,
                    'StudentLong' => $student->longitude,
                    'Status' => $tutor->session_status,
                    'Subject' => $tutor->s_name,
                    'Class' => $tutor->p_name,
                    'Subject_id' => $tutor->subject_id,
                    'Class_id' => $tutor->programme_id,
                    'IsGroup' => $tutor->is_group,
                    'Datetime' => Carbon::now()->toDateTimeString(),
                    'Latitude' => $tutor->latitude,
                    'Longitude' => $tutor->longitude,
                    'SessionLocation' => is_null($tutor->session_location)?'':$tutor->session_location,
                    'Hour' => $tutor->duration,
                    'Profile_image'=>!empty($student->profileImage)?URL::to('/images').'/'.$student->profileImage:''
                ];
            }

            return response()->json(
                [
                    'data' => $tutor_sessions
                ]
            );

        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find user session'
                ], 422
            );

        }
    }

    /**
     * Class SessionController
     * @package App\Http\Controllers
     * Api will create student session with tutor and notification to student
     */
    public function bookedTutor(Request $request){
        $data = $request->all();
        $this->validate($request,[
            'session_id' => 'required', //TODO: this field will be required when mobile developer work on it.
//            'student_id' => 'required',
//            'tutor_id' => 'required',
//            'subject_id' => 'required',
//            'class_id' => 'required',
//            'longitude' => 'required',
//            'latitude' => 'required',
            'rate' => 'required'
        ]);
        $sessionId = $data['session_id'];
        //get session by id
        $session = Session::find($sessionId);

        if(!$session){
            return [
                'status' => 'fail',
                'messages' => 'Session id does not exist'
            ];
        }

        //if student session already exists.
        if($session->status == 'booked'){
            return [
                'status' => 'fail',
                'messages' => 'Session already booked!'
            ];
        }else{
            $tutorId = $session->tutor_id;
            $studentId = $session->student_id;

            //get tutor profile
            $user = new User();
            $users = $user->findBookedUser($tutorId);
            //get student profile
            $student = User::where('id','=',$studentId)->first();

            //get package rate
            $package_id = $data['rate'];
            $package = new Package();
            $package_rate = $package->getPackageRate($package_id, $session->is_group, $session->group_members);

            $updated_session = $session->updateSession(['id'=>$sessionId], ['status'=>'booked', 'rate'=> $package_rate]);

            if($updated_session){

                //get tutor device token
                $device = User::where('id','=',$studentId)->select('device_type', 'device_token as token')->first();
                $message = PushNotification::Message(
                    $users->firstName.' '.$users->lastName.' accepted your request',
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
                            'notification_type' => 'session_booked',
                            'session_id' => $sessionId,
                            'Tutor_Name' => $users->firstName." ".$users->lastName,
                            'Class_Name' => $users->p_name,
                            'Subject_Name' => $users->s_name,
                            'Class_id' => $users->p_id,
                            'Subject_id' => $users->s_id,
                            'is_group' => $users->is_group,
                            'tutor_is_home' => $users->t_is_home,
                            'tutor_lat' => $users->latitude,
                            'tutor_long' => $users->longitude,
                            'student_lat' => $student->latitude,
                            'student_long' => $student->longitude,
                            'session_lat' => $session->latitude,
                            'session_long' => $session->longitude,
                            'session_location' => $session->session_location,
                            'Profile_Image' => !empty($users->profileImage)?URL::to('/images').'/'.$users->profileImage:'',
                        ))
                    ));
                //send student info
    //            Queue::push(PushNotification::app('appStudentIOS')
    //                ->to($device->token)
    //                ->send($message));
                if($device->device_type == 'android') {
                    PushNotification::app('appNameAndroid')->to($device->token)->send($message);
                }else{
                    PushNotification::app('appStudentIOS')->to($device->token)->send($message);
                }

                if($session->book_later_at != null || $session->book_later_at != ''){
                    $bookLaterAt = Carbon::parse($session->book_later_at);
                    $now = Carbon::now();
                    $delay = $bookLaterAt->diffInMinutes($now) - 60; //Subtract 1 hour

                    $tutorNotificationJob = (new BookLaterTutorNotification($session->id))->delay($delay*60);
                    dispatch($tutorNotificationJob);

                    $studentNotificationJob = (new BookLaterStudentNotification($session->id))->delay($delay*60);
                    dispatch($studentNotificationJob);

                }


                
                return [
                    'status' => 'success',
                    'messages' => 'Session booked successfully'
                ];
            }else{
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Unable to update session status'
                    ], 422
                );
            }

        }



    }

    /**
     * @param Request $request
     * @return insert session with status rejected.
     * 
     */
    public function sessionRejected(Request $request){
        $this->validate($request,[
            'session_id' => 'required', //TODO: this field will be required when mobile developer work on it.
//            'tutor_id' => 'required',
//            'student_id' => 'required',
//            'class_id' => 'required',
//            'subject_id' => 'required',
        ]);
        $data = $request->all();
        $data['status'] = 'reject';
        
        $session = new Session();
        $session = $session->updateSession(['id'=>$data['session_id']], ['status'=> $data['status']]);
        if($session){
            return [
                'status' => 'success',
                'messages' => 'Session status updated successfully'
            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find tutor'
                ], 422
            );
        }

    }

    public function getUserSession($user_id){
        $session = new Session();
        $user_session = $session->getTutorSessionDetail($user_id);
        return $user_session;
    }
    
    public function updateDeserveStudentStatus($student_id){
        //update student deserving status
        Profile::updateDerserveStatus($student_id);
        $students =  User::getStudents();
        return $students;
    }


    /**
     * @param Request $request
     * @return insert session with status rejected.
     *
     */
    public function updateSessionStatus(Request $request){
        $this->validate($request,[
//            'session_id' => 'required', //TODO: this field will be required when mobile developer work on it.
            'tutor_id' => 'required',
            'student_id' => 'required',
            'class_id' => 'required',
            'subject_id' => 'required',
            'status' => 'required'
        ]);
        $data = $request->all();
        
        $session = new Session();
        $session = $session->updateSession(['id'=>$data['session_id']], ['status'=> $data['status']]);
        if($session){
            return [
                'status' => 'success',
                'messages' => 'Session status updated successfully'
            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find tutor'
                ], 422
            );
        }

    }

    public function sessionStart(Request $request)
    {
        $this->validate($request, [
            'session_id' => 'required'
        ]);

        $updateSession = new Session();
        $updateSession->updateSession(['id'=>$request->session_id], ['started_at'=>Carbon::now()]);
        return [
            'status' => 'success',
            'messages' => 'Session updated successfully'
        ];
    }
    
    public function sessionCalculationCost(Request $request){
        $this->validate($request,[
            'session_id' => 'required',
            'duration' => 'required'
        ]);
        $findSession = Session::find($request->session_id);
        $student_id  = $findSession->student_id;
        $user = User::find($student_id);
        $duration = $request->duration;
        $originalDuration = $request->duration;
        $group_members = $findSession->group_members;

        $twentyPercent = 20/100;
        $thirtyPercent = 30/100;
        $fortyPercent  = 40/100;
        $fiftyPercent  = 50/100;

        $duration = Carbon::parse($duration);
        $durationMinutes=$duration->format('i');
        $durationInHour = $durationMinutes > 0 ? $duration->addHour(1)->format('h') : $duration->format('h');
        $costPerHour = 400;
        $totalCostAccordingToHours = $costPerHour * $durationInHour;
        if ($group_members != 0){
            switch ($group_members){
                case '2':
                    $percentage = $totalCostAccordingToHours * $twentyPercent;
                    $totalCostAccordingToHours += $percentage;
                    break;
                case '3':
                    $percentage = $totalCostAccordingToHours * $thirtyPercent;
                    $totalCostAccordingToHours += $percentage;
                    break;
                case '4':
                    $percentage = $totalCostAccordingToHours * $fortyPercent;
                    $totalCostAccordingToHours += $percentage;
                    break;
                case '5':
                    $percentage = $totalCostAccordingToHours * $fiftyPercent;
                    $totalCostAccordingToHours += $percentage;
                    break;
            }
        }
        $findSession->rate = $totalCostAccordingToHours;
        $findSession->status = 'ended';
        $findSession->duration = $originalDuration;
        $findSession->save();
        $wallet                   =   new Wallet();
        $wallet->session_id       =   $findSession->id;
        $wallet->amount           =   $totalCostAccordingToHours;
        $wallet->type             =   'debit';
        $wallet->from_user_id     =   $findSession->student_id;
        $wallet->to_user_id       =   $findSession->tutor_id;
        $wallet->save();
        $message = PushNotification::Message(
            'Your total cost is '. $totalCostAccordingToHours,
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
                    'notification_type' => 'session_ended',
                    'session_id' => $request->session_id
                ))
            ));
            if($user->device_type == 'android') {
                PushNotification::app('appNameAndroid')->to($user->device_token)->send($message);
            }else{
                PushNotification::app('appStudentIOS')->to($user->device_token)->send($message);
            }
            return response()->json(
                [
                    'status'   => 'success',
                    'totalCost' => $totalCostAccordingToHours,
                    'hourly_rate' => $costPerHour
                ]
            );
    }
}
