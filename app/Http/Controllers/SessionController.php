<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Support\Facades\URL;
use Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

//Models
use App\Models\Profile;
use App\Models\Session;
use App\Models\User;
use App\Package;

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
                $tutor_sessions[] = [
                    'FullName' => $user->firstName.' '.$user->lastName,
                    'FirstName' => $user->firstName,
                    'LastName' => $user->lastName,
                    'Date' => $user->Session_created_date,
                    'Lat' => $user->latitude,
                    'Long' => $user->longitude,
                    'User_Lat' => $user_details->latitude,
                    'User_Long' => $user_details->longitude,
                    'Status' => $user->session_status,
                    'Subject' => $user->s_name,
                    'Student_Longitude' => $user->longitude,
                    'Student_Latitude' => $user->latitude,
                    'Hour' => $user->duration,
                    'Price' => $user->rate,
                    'Session_id' => $user->session_id,
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
                    'Hour' => $tutor->duration,
                    'Profile_image'=>!empty($tutor->profileImage)?URL::to('/images').'/'.$tutor->profileImage:''
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
            'rate' => 'required'
        ]);
        $session_id = $data['session_id'];
        //get session by id
        $session = new Session();
        $session = $session->findSessionById($session_id);

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
            $tutor_id = $session->tutor_id;
            $student_id = $session->student_id;

            //get tutor profile
            $user = new User();
            $users = $user->findBookedUser($tutor_id);
            //get student profile
            $student = User::where('id','=',$student_id)->first();

            //get package rate
            $package_id = $data['rate'];
            $package = new Package();
            $package_rate = $package->getPackageRate($package_id, $session->is_group, $session->group_members);

            $updated_session = $session->updateSession(['id'=>$session_id], ['status'=>'booked', 'rate'=> $package_rate]);

            if($updated_session){

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
                            'session_id' => $session_id,
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
            'session_id' => 'required'
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
            'session_id' => 'required',
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

}
