<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Profile;
use App\Models\Session;
use App\Models\User;
use App\Models\Programme;
use App\Models\Subject;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\Request;
use App\Transformers\UserTransformer;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Queue\Queue;
use Carbon\Carbon;
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
        //tutor session list
        if(isset($data['tutor_id'])){
            $tutor_id = $data['tutor_id'];
            $user_session = User::select('users.*')
                ->select('users.*','sessions.created_at as Session_created_date')
                ->join('sessions','sessions.tutor_id','=','users.id')
                ->where('users.role_id','=',2)
                ->where('users.id','=',$tutor_id)
                ->where('sessions.status','=','booked')
                ->orWhere('sessions.status','=','end')
                ->get();
        }
        //student session list
        else{
            $student_id = $data['student_id'];
            $user_session = User::select('users.*')
                ->select('users.*','sessions.created_at as Session_created_date')
                ->join('sessions','sessions.student_id','=','users.id')
                ->where('users.role_id','=',3)
                ->where('users.id','=',$student_id)
                ->where('sessions.status','=','booked')
                ->orWhere('sessions.status','=','end')
                ->get();
        }
        if($user_session){
            $tutor_sessions = [];
            foreach ($user_session as $user){
                $tutor_sessions[] = [
                    'FullName' => $user->firstName.' '.$user->lastName,
                    'Date' => $user->Session_created_date,
                    'Lat' => $user->latitude,
                    'Long' => $user->longitude,
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
        $programme_id = $data['class_id'];
        $subject_id = $data['subject_id'];
        //get tutor profile
        $users = User::select('users.*')
            ->select('users.*','programmes.name as p_name','subjects.name as s_name'
                ,'programmes.id as p_id','subjects.id as s_id','profiles.is_group',
                'profiles.is_home as t_is_home')
            ->leftjoin('profiles','profiles.user_id','=','users.id')
            ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
            ->leftjoin('subjects','subjects.id','=','profiles.subject_id')
            ->where('users.role_id','=',2)
            ->where('users.id','=',$tutor_id)
            ->first();
        $student = User::where('id','=',$student_id)->first();
        $session = Session::
        where('student_id','=',$student_id)
            ->where('programme_id','=',$programme_id)
            ->where('subject_id','=',$subject_id)
            ->where('status','=','booked')
            ->first();

        //if student session already exists.
        if($session){
            return [
                'status' => 'fail',
                'messages' => 'Session already booked!'
            ];
        }else{
            $session = new Session;
            $session->tutor_id = $tutor_id;
            $session->student_id = $student_id;
            $session->programme_id = $programme_id;
            $session->subject_id = $subject_id;
            $session->status = 'booked';
            $session->subscription_id = 3;
            $session->meeting_type_id = 1;
            $session->save();

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
            //send student info to student
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
                'messages' => 'Session Created successfully'
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
