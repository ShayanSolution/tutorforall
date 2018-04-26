<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Support\Facades\URL;
use Log;


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
        if(isset($data['tutor_id'])){
            $tutor_id = $data['tutor_id'];
            $user_session = $session->getTutorSessionDetail($tutor_id);
        }
        //student session list
        else{
            $student_id = $data['student_id'];
            $user_session = $session->getStudentSessionDetail($student_id);
            
        }
        if($user_session){
            $tutor_sessions = [];
            foreach ($user_session as $user){
                $user_name = User::where('id',$user->session_user_id)->first();
                $tutor_sessions[] = [
                    'FullName' => $user->firstName.' '.$user->lastName,
                    'UserName' => $user_name->firstName.' '.$user_name->lastName,
                    'Date' => $user->Session_created_date,
                    'Lat' => $user->latitude,
                    'Long' => $user->longitude,
                    'User_Lat' => $user_name->latitude,
                    'User_Long' => $user_name->longitude,
                    'Status' => $user->session_status,
                    'Subject' => $user->s_name,
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
                    'TutorName' => $tutor->firstName.' '.$tutor->lastName,
                    'StudentName' => $student->firstName.' '.$student->lastName,
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
                    'IsGroup' => $tutor->is_group,
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
            'student_id' => 'required',
            'tutor_id' => 'required',
            'subject_id' => 'required',
            'class_id' => 'required',
        ]);
        $tutor_id = $data['tutor_id'];
        $student_id = $data['student_id'];
        //get tutor profile
        $user = new User();
        $users = $user->findBookedUser($tutor_id);
        $student = User::where('id','=',$student_id)->first();
        $session = new Session();
        $session = $session->findStudentSession($data);
        //if student session already exists.
        if($session){
            return [
                'status' => 'fail',
                'messages' => 'Session already booked!'
            ];
        }else{
            $session = new Session();
            $session->saveSession($data);
            //get tutor device token
            $device = User::where('id','=',$student_id)->select('device_token as token')->first();
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
                        'Profile_Image' => !empty($users->profileImage)?URL::to('/images').'/'.$users->profileImage:'',
                    ))
                ));
            //send student info
//            Queue::push(PushNotification::app('appStudentIOS')
//                ->to($device->token)
//                ->send($message));
            PushNotification::app('appStudentIOS')
                ->to($device->token)
                ->send($message);

        }

        if($session){
            return [
                'status' => 'success',
                'messages' => 'Session booked successfully'
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

    /**
     * @param Request $request
     * @return insert session with status rejected.
     * 
     */
    public function sessionRejected(Request $request){
        $this->validate($request,[
            'tutor_id' => 'required',
            'student_id' => 'required',
            'class_id' => 'required',
            'subject_id' => 'required',
        ]);
        $data = $request->all();
        $data['status'] = 'reject';
        $session = new Session();
        $session = $session->saveSession($data);
        if($session){
            return [
                'status' => 'success',
                'messages' => 'Session with reject status created successfully'
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
}
