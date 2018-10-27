<?php

namespace App\Http\Controllers;


use App\Wallet;
use Illuminate\Http\Request;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Support\Facades\URL;
use Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Jobs\BookLaterTutorNotification;
use App\Jobs\BookLaterStudentNotification;
use App\Jobs\StartSessionNotification;

//Models
use App\Models\Profile;
use App\Models\Session;
use App\Models\User;
use App\Package;
use App\Models\Rating;
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
                $wallet = Wallet::where(['session_id'=>$user->session_id, 'type'=>'debit'])->first();
                if($wallet){
                    $paidAmount =  $wallet->amount;
                }
                if($user->book_later_at != null || $user->book_later_at != ''){
                    $sessionDate = $user->book_later_at;
                }else{
                    $sessionDate = $user->Session_created_date;
                }
                $tutor_sessions[] = [
                    'FullName' => $user_details->firstName.' '.$user_details->lastName,
                    'FirstName' => $user_details->firstName,
                    'LastName' => $user_details->lastName,
                    'Experience' => (int)$user_details->experience,
                    'Date' => $sessionDate,
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
                    'is_home' => $user->session_is_home,
                    'Session_id' => $user->session_id,
                    'session_status' => $user->session_status,
                    'is_group' => $user->session_is_group,
                    'group_members' => $user->session_group_members,
                    'session_rating' => is_null($user->session_rating)?'':number_format((float)$user->session_rating, 1, '.', ''),
                    'session_review' => is_null($user->session_review)?'':(string)$user->session_review,
                    'paid_amount' => isset($paidAmount) ? $paidAmount : 0,
                    'Age' => Carbon::parse($user->dob)->age,
                    'Profile_image'=>!empty($user_details->profileImage)?URL::to('/images').'/'.$user_details->profileImage:'',
                    'hourly_rate' => $user->hourly_rate
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
                    'HourlyRate' => $tutor->hourly_rate,
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
            'session_id' => 'required',
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
                'message' => 'Session id does not exist'
            ];
        }

        //if student session already exists.
        if($session->status == 'booked' || $session->status == 'started' || $session->status == 'ended'){
            return [
                'status' => 'fail',
                'message' => 'Session already booked!'
            ];
        }else{
//            $tutorId = $session->tutor_id;
            $tutorId = Auth::user()->id;//Get login tutor id
            $studentId = $session->student_id;

            //Update tutor id who booked the session.
            $updated_session_tutor = $session->updateSession(['id'=>$sessionId], ['tutor_id'=>Auth::user()->id]);

            //get tutor profile
            $user = new User();
            $users = $user->findBookedUser($tutorId, $sessionId);
            //get student profile
            $student = User::where('id','=',$studentId)->first();

            //get package rate
            $package_id = $data['rate'];
            $package = new Package();
            $package_rate = $package->getPackageRate($package_id, $session->is_group, $session->group_members);

            $updated_session = $session->updateSession(['id'=>$sessionId], ['status'=>'booked', 'rate'=> $package_rate]);

            //get session rating
            $rating_sessions = Session::where('tutor_id', $tutorId)->where('hourly_rate', '!=', 0)->pluck('id');
            $rating = Rating::whereIn('session_id', $rating_sessions)->get();

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
                            'group_members' => $users->s_group_members,
                            'is_home' => $users->s_is_home,
                            'hourly_rate' => $users->hourly_rate,
                            'tutor_is_home' => $users->t_is_home,
                            'tutor_lat' => (string)$users->latitude,
                            'tutor_long' => (string)$users->longitude,
                            'student_lat' => $student->latitude,
                            'student_long' => $student->longitude,
                            'session_lat' => (string)$session->latitude,
                            'session_long' => (string)$session->longitude,
                            'session_location' => $session->session_location,
                            'session_rating' => number_format((float)$rating->avg('rating'), 1, '.', ''),
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

                //Book later notifications.
//                if($session->book_later_at != null || $session->book_later_at != ''){
//                    $bookLaterAt = Carbon::parse($session->book_later_at);
//                    $now = Carbon::now();
//                    $delay = $bookLaterAt->diffInMinutes($now) - 60; //Subtract 1 hour
//
//                    $tutorNotificationJob = (new BookLaterTutorNotification($session->id))->delay($delay*60);
//                    dispatch($tutorNotificationJob);
//
//                    $studentNotificationJob = (new BookLaterStudentNotification($session->id))->delay($delay*60);
//                    dispatch($studentNotificationJob);
//
//                }


                
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
        //TODO: work on reject status

        $session = Session::find($data['session_id']);
//        Log::info('Reject Session API, Session status: '.$session->status);
//        if($session->status != 'booked' || $session->status != 'ended' || $session->status != 'started'){
//            $session = $session->update(['status'=> $data['status'], 'tutor_id'=> Auth::user()->id]);
//        }else{
//            //TODO: insert new entry as rejected status.
//        }

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
        $updateSession->updateSession(['id'=>$request->session_id], ['status'=>'started','started_at'=>Carbon::now()]);

        //send student info to tutor
        $job = new StartSessionNotification($request->session_id);
        dispatch($job);
        
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

        $date = Carbon::parse($findSession->started_at);
        $now = Carbon::now();

        $duration = $date->diffInHours($now);

        $durationInHour = $duration > 0 ? $duration : $duration+1;

        $costPerHour = $findSession->hourly_rate;
        $totalCostAccordingToHours = $costPerHour * $durationInHour;

        if($findSession->student->profile->is_deserving == 0) {
            $findSession->ended_at = $now;
            $findSession->rate = $totalCostAccordingToHours;
            $findSession->status = 'ended';
            $findSession->duration = $originalDuration;
            $findSession->save();
            $wallet = new Wallet();
            $wallet->session_id = $findSession->id;
            $wallet->amount = $totalCostAccordingToHours;
            $wallet->type = 'debit';
            $wallet->from_user_id = $findSession->student_id;
            $wallet->to_user_id = $findSession->tutor_id;
            $wallet->save();
            //TODO: Add in job
            $message = PushNotification::Message(
                'Your total cost is Rs ' . $totalCostAccordingToHours,
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
                        'session_id' => $request->session_id,
                        'session_cost' => $totalCostAccordingToHours
                    ))
                ));
            if ($user->device_type == 'android') {
                PushNotification::app('appNameAndroid')->to($user->device_token)->send($message);
            } else {
                PushNotification::app('appStudentIOS')->to($user->device_token)->send($message);
            }
            return response()->json(
                [
                    'status' => 'success',
                    'totalCost' => $totalCostAccordingToHours,
                    'hourly_rate' => $costPerHour
                ]
            );
        }
        else{
            $findSession->ended_at = $now;
            $findSession->rate = 0;
            $findSession->status = 'ended';
            $findSession->duration = $originalDuration;
            $findSession->save();
            //TODO: Add in job
            $message = PushNotification::Message(
                'Your total cost is Rs ' . $totalCostAccordingToHours,
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
            if ($user->device_type == 'android') {
                PushNotification::app('appNameAndroid')->to($user->device_token)->send($message);
            } else {
                PushNotification::app('appStudentIOS')->to($user->device_token)->send($message);
            }
            return response()->json(
                [
                    'status' => 'success',
                    'totalCost' => 0,
                    'hourly_rate' => $costPerHour
                ]
            );
        }
    }
    
    public function getLatestSession(){
        $userId = Auth::user()->id;
        $roleId = Auth::user()->role_id;
        Log::info('Get latest session of user ID: '.$userId);
        Log::info('Get latest session of user role: '.$roleId);
        $session = '';
        $rating = '';
        $data = [];
        if($roleId == 2){
            $session = Session::where('tutor_id', $userId)->with('tutor','student')->orderBy('updated_at', 'desc')->first();
            if($session){
                $rating = Rating::where('session_id', $session->id)->first();
            }else{
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Session not found.'
                    ]
                );
            }
            
        }
        else{
            $session = Session::where('student_id', $userId)->with('tutor','student')->orderBy('updated_at', 'desc')->first();
            if($session) {
                $rating = Rating::where('session_id', $session->id)->first();
                //get tutor avg rating
                $rating_sessions = Session::where('tutor_id', $session->tutor_id)->where('hourly_rate', '!=', 0)->pluck('id');
                $tutor_rating = Rating::whereIn('session_id', $rating_sessions)->get();
            }else{
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Session not found.'
                    ]
                );
            }
        }

        Log::info('Get latest session ID: '.$session->id);

        $data['program_name'] = $session->programme->name;
        $data['subject_name'] = $session->subject->name;
        $data['tutor_name']   = $session->tutor->firstName." ".$session->tutor->lastName;
        $data['latitude']     = $session->tutor->latitude;
        $data['longitude']    = $session->tutor->longitude;
        $data['tutor_profile_img']  = \url("images/".$session->tutor->profileImage);
        if(isset($session->student->firstName)){
            $data['student_name'] = $session->student->firstName." ".$session->student->lastName;
            $data['student_profile_img']  = \url("images/".$session->student->profileImage);
        }else{
            $data['student_name'] = "";
            $data['student_profile_img']  = '';
        }


        if($roleId == 3) {
            $data['tutor_rating'] = number_format((float)$tutor_rating->avg('rating'), 1, '.', '');
        }
        if($session){
            return response()->json(
                [
                    'status' => 'success',
                    'session' => $session,
                    'rating' => $rating,
                    'data'   => $data
                ]
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find session.'
                ]
            );
        }
    }
    
}
