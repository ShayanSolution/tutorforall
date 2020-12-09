<?php

namespace App\Http\Controllers;


use App\Exceptions\CouldNotMarkSessionAsBooked;
use App\Exceptions\SessionBookedStartedOrEnded;
use App\Exceptions\SessionExpired;
use App\Jobs\BookNotification;
use App\Jobs\CancelledSessionNotification;
use App\Jobs\ReceivedPaymentNotification;
use App\Jobs\SendNotificationOfCalculationCost;
use App\Jobs\SessionPaidNotificationToTutor;
use App\Jobs\SessionPaymentEmail;
use App\Models\Disbursement;
use App\Models\SessionPayment;
use App\Models\Setting;
use App\Services\CostCalculation\SessionCost;
use App\Wallet;
use Illuminate\Http\Request;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Support\Facades\DB;
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
            $studentObj = User::where('id', $student_id)->first();
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
                if ($user_details){
//                    $trackingON = 0;
                    $sessionType = 'now';
                    $checkTrackingOn = $user->book_later_at;
                    if ($checkTrackingOn) {
                        $sessionType = 'later';
//                        $bookLaterTime = Carbon::parse($checkTrackingOn);
//                        $currentTime = Carbon::parse(Carbon::now());
//                        $hours = $currentTime->diffInHours($bookLaterTime);
//                        if ($hours <= 1) {
//                            $trackingON = 1;
//                        }
                    }
                    $tutor_sessions[] = [
                    'FullName' => $user_details->firstName.' '.$user_details->lastName,
                    'FirstName' => $user_details->firstName,
                    'LastName' => $user_details->lastName,
                    'tutor_phone' => $user_details->phone,
                    'Experience' => (int)$user_details->experience,
                    'Date' => $sessionDate,
                    'Lat' => $user->latitude,
                    'Long' => $user->longitude,
                    'User_Lat' => $user_details->latitude,
                    'User_Long' => $user_details->longitude,
                    'Status' => $user->session_status,
                    'Subject' => $user->s_name,
                    'Program' => $user->p_name,
                    'Student_Longitude' => $studentObj->longitude,
                    'Student_Latitude' => $studentObj->latitude,
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
                    'hourly_rate' => $user->hourly_rate,
                    'hourly_rate_past_first_hour' => $user->hourly_rate_past_first_hour,
                    'book_later_at' => $user->book_later_at,
                    'session_type' => $sessionType,
                    'is_hourly' => $user->is_hourly,
                    'tracking_on' => $user->tracking_on,
                    ];
                }

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
                    'Profile_image'=>!empty($student->profileImage)?URL::to('/images').'/'.$student->profileImage:'',
                    'is_hourly' => $tutor->is_hourly
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
    /**
     * New Implementation is written below
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

        $sessionBookedOrStartedOrEnded = Session::where('session_sent_group', $session->session_sent_group)
                            ->whereIn('status', [
                                'booked', 'started', 'ended'
                            ])
                            ->count();

        //if student session already exists.
        if($sessionBookedOrStartedOrEnded > 0){
            return [
                'status' => 'fail',
                'message' => 'Session already booked!'
            ];
        }else if($session->status == 'expired'){
            return [
                'status' => 'fail',
                'message' => 'Session expired!'
            ];
        }
        else{
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

                $bookNotification = (new BookNotification(json_encode($student), json_encode($users), $session, $rating, $sessionId));
                dispatch($bookNotification);

                //Book later notifications.
                if($session->book_later_at != null || $session->book_later_at != ''){

                    $bookLaterAt = Carbon::parse($session->book_later_at);//Carbon::createFromFormat('Y-m-d H:i:s', $session->book_later_at, env('APP_SERVER_TIMEZONE'));
//                    $bookLaterAt->setTimezone(env('APP_TIMEZONE'));
                    $now = Carbon::now();
                    $delay = $bookLaterAt->diffInMinutes($now) - 60; //Subtract 1 hour

                    $tutorNotificationJob = (new BookLaterTutorNotification($sessionId))->delay(Carbon::now()->addMinutes($delay));
                    dispatch($tutorNotificationJob);

                    $studentNotificationJob = (new BookLaterStudentNotification($sessionId))->delay(Carbon::now()->addMinutes($delay));
                    dispatch($studentNotificationJob);
                }

                return [
                    'status'        => 'success',
                    'message'       => 'Session booked successfully',
                    'session_id'    =>  $sessionId
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
    */



    /**
     * Class SessionController
     * @package App\Http\Controllers
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     * @throws \Exception
     *
     * Api will book student session with tutor and notify to student
     */
    public function bookedTutor(Request $request){
        $this->validate($request,[
            'session_id'            =>  'required',
            'rate'                  =>  'required',
            'session_sent_group'    =>  'required'
        ]);

        $savedThisSession = 0;
        DB::beginTransaction();
        try {
            $tutorId        = Auth::user()->id;
            $sessionId      = $request->session_id;
            $packageId      = $request->rate;

            $session = Session::find($sessionId);

            if($session->status == 'expired'){
                throw new SessionExpired();
            }

            $sessionBookedOrStartedOrEnded = Session::where('session_sent_group', $request->session_sent_group)
                ->whereIn('status', [
                    'booked', 'started', 'ended'
                ])
                ->count();

            if($sessionBookedOrStartedOrEnded > 0){
                throw new SessionBookedStartedOrEnded();
            }

            $user = new User();
            $users = $user->findBookedUser($tutorId, $sessionId);


            $student = $session->student;

            $package = new Package();

            $package_rate = $package->getPackageRate($packageId, $session->is_group, $session->group_members);


            $siblingSessions           = Session::where('session_sent_group', $request->session_sent_group)
                ->lockForUpdate()->get();

            foreach ($siblingSessions as $session){

                if($session->id == $sessionId){
                    $session->status    = 'booked';
                    $session->rate      = $package_rate;
                    if ($session->book_later_at == null) {
                        $session->tracking_on = 1;
                    }
                }else
                    $session->status   =  'expired';

                $savedThisSession  =  $session->save();

                if(!$savedThisSession)
                    throw new CouldNotMarkSessionAsBooked();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        if($savedThisSession){
            //get session rating
            $rating_sessions = Session::where('tutor_id', $tutorId)->where('hourly_rate', '!=', 0)->pluck('id');
            $rating = Rating::whereIn('session_id', $rating_sessions)->get();

            return $this->sendBookingNotifications($student, $users, $session, $rating, $sessionId);
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update session status'
                ], 422
            );
        }

    }

    private function sendBookingNotifications($student, $users, $session, $rating, $sessionId){

        $bookNotification = (new BookNotification(json_encode($student), json_encode($users), $session, $rating, $sessionId));
        dispatch($bookNotification);

        //Book later notifications.
        if($session->book_later_at != null || $session->book_later_at != ''){
            // tracking on for session
//            $session = Session::where('id', $sessionId)->first();
//            if ($session) {
//                $session->update([
//                    'tracking_on' => 1
//                ]);
//            }

            $bookLaterAt = Carbon::parse($session->book_later_at);//Carbon::createFromFormat('Y-m-d H:i:s', $session->book_later_at, env('APP_SERVER_TIMEZONE'));
//                    $bookLaterAt->setTimezone(env('APP_TIMEZONE'));
            $now = Carbon::now();
            $delay = $bookLaterAt->diffInMinutes($now) - 60; //Subtract 2 hour

            $tutorNotificationJob = (new BookLaterTutorNotification($sessionId))->delay(Carbon::now()->addMinutes($delay));
            dispatch($tutorNotificationJob);

            $studentNotificationJob = (new BookLaterStudentNotification($sessionId))->delay(Carbon::now()->addMinutes($delay));
            dispatch($studentNotificationJob);
        }

        return response()->json([
            'status'        => 'success',
            'message'       => 'Session booked successfully',
            'session_id'    =>  $sessionId
        ]);
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

        $session = Session::find($data['session_id']);

        if($session->status == 'pending'){

            $updatedSession = Session::where('id', $data['session_id'])->update(['status'=>$data['status']]);

            if($updatedSession){
                return response()->json([
                    'status' => 'success',
                    'message' => 'Session rejected successfully'
                ]);
            }else{
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Unable to find tutor'
                    ], 422
                );
            }
        }
        else
            return response()->json(['status'=>'success', 'message'=>'Session expired!']);

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

        return response()->json([
            'status' => 'success',
            'messages' => 'Session updated successfully'
        ]);
    }

    public function sessionCalculationCost(Request $request, SessionCost $sessionCost){
        $this->validate($request,[
            'session_id' => 'required',
            'duration' => 'required'
        ]);
        $findSession = Session::find($request->session_id);
        $hourlyRatePastFirstHour = (int)$findSession->hourly_rate_past_first_hour;
        $student_id  = $findSession->student_id;
        $user = User::find($student_id);

        $originalDuration = $request->duration;


        $date = Carbon::parse($findSession->started_at);
        $now = Carbon::now();

        $durationInHour = ceil($date->diffInSeconds($now) / 60 / 60);

        $totalCostAccordingToHours = $sessionCost->execute($durationInHour, $findSession);

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

            $job = (new SendNotificationOfCalculationCost($totalCostAccordingToHours, $request->session_id, json_encode($user), 'commercial'));
            dispatch($job);
            return response()->json(
                [
                    'status' => 'success',
                    'totalCost' => $totalCostAccordingToHours,
                    'hourly_rate' => $findSession->hourly_rate,
                    'hourly_rate_past_first_hour' =>  $hourlyRatePastFirstHour
                ]
            );
        }
        else{
            $findSession->ended_at = $now;
            $findSession->rate = 0;
            $findSession->status = 'ended';
            $findSession->duration = $originalDuration;
            $findSession->save();

            $job = (new SendNotificationOfCalculationCost($totalCostAccordingToHours, $request->session_id, json_encode($user), 'mentor'));
            dispatch($job);
            return response()->json(
                [
                    'status' => 'success',
                    'totalCost' => 0,
                    'hourly_rate' => $findSession->hourly_rate,
                    'hourly_rate_past_first_hour' =>  $hourlyRatePastFirstHour
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
        $sessionDateTime = Carbon::now()->toDateTimeString();
        $data = [];

        if($roleId == 2){
            //i am tutor
            $session = Session::where('tutor_id', $userId)->whereIn('status', ['booked', 'started'])->with('tutor','student')->orderBy('id', 'desc')->first();
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

        } else {
            $session = Session::where('student_id', $userId)->whereIn('status', ['booked', 'started', 'ended'])->with('tutor','student')->orderBy('id', 'desc')->first();
            if($session) {
                $rating = Rating::where('session_id', $session->id)->first();
                //get tutor avg rating
                $rating_sessions = Session::where('tutor_id', $session->tutor_id)->where('hourly_rate', '!=', 0)->pluck('id');
                $tutor_rating = Rating::whereIn('session_id', $rating_sessions)->get();
            } else {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Session not found.'
                    ]
                );
            }
        }

        if ($session) {
            $paid = SessionPayment::where('session_id', $session->id)->first();
        }
        $session->student->profileImage = \url("images/".$session->student->profileImage);
        $session->tutor->profileImage = \url("images/".$session->tutor->profileImage);


        Log::info('Get latest session ID: '.$session->id);

        $data['program_name'] = $session->programme->name;
        $data['subject_name'] = $session->subject->name;

        if ($session->book_later_at == null){
            $data['session_type'] = 'now';
            $data['tracking_on'] = $session->tracking_on;
        } else {
            $data['session_type'] = 'later';
            $data['tracking_on'] = $session->tracking_on;
        }
        $data['start_session_enable'] = $session->start_session_enable;
        $data['dateTime'] = $sessionDateTime;
        if($roleId == 3) {
            $data['tutor_rating'] = number_format((float)$tutor_rating->avg('rating'), 1, '.', '');
        }
        if($session){
            return response()->json(
                [
                    'status' => 'success',
                    'session' => $session,
                    'rating' => $rating,
                    'data'   => $data,
                    'paid' => $paid
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

    public function cancelSession(Request $request){
        $this->validate($request, [
            'session_id' => 'required',
            'cancelled_from' => 'required'
        ]);
        $userId = Auth::user()->id;
        $cancelledFrom = $request->cancelled_from;
        $session = Session::where('id', $request->session_id)->first();
        if ($session->status == "cancelled") {
            return response()->json([
                'status' => 'success',
                'messages' => 'Session cancelled'
            ]);
        }
        if ($session->start_session_enable == 1) {
            if ($cancelledFrom == 'tutor') {
                return response()->json([
                    'status' => 'error',
                    'messages' => 'Unable to cancel session because your student has arrived'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'messages' => 'Unable to cancel session because your tutor has arrived'
                ]);
            }
        }
        if ($session->status == 'started') {
            return response()->json([
                'status' => 'error',
                'messages' => 'Unable to cancel session because your session has started'
            ]);
        }
        $studentId = $session->student_id;
        $tutorId = $session->tutor_id;
        if ($session){
            $session->update([
                'status' => 'cancelled',
                'cancelled_by' => $userId,
                'cancelled_from' => $cancelledFrom,
            ]);
            if ($cancelledFrom == 'tutor') {
                //send cancelled notification to student
                Log::info('Send student to cancelled session by tutor');
                $job = new CancelledSessionNotification($studentId, $cancelledFrom);
                dispatch($job);
                Log::info('Cancelled session dispatch job DONE');
            } else {
                //send cancelled notification to tutor
                Log::info('Send tutor to cancelled session by student');
                $job = new CancelledSessionNotification($tutorId, $cancelledFrom);
                dispatch($job);
                Log::info('Cancelled session dispatch job DONE');
            }

            return response()->json([
                'status' => 'success',
                'messages' => 'Session cancelled successfully'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'messages' => 'Session not found'
            ]);
        }
    }

    public function sessionPayment(Request $request){
        //card
        if ($request->transaction_platform == "card"){
            $this->validate($request,[
                'session_id' => 'required',
                'transaction_ref_no' => 'required',
                'transaction_type' => 'required',
                'amount' => 'required',
                'insert_date_time' => 'required',
                'transaction_status' => 'required',
            ]);
        }
        //JazzCash
        if ($request->transaction_platform == "jazzcash"){
            $this->validate($request,[
                'session_id' => 'required',
                'transaction_ref_no' => 'required',
                'transaction_type' => 'required',
                'amount' => 'required',
                'insert_date_time' => 'required',
                'transaction_status' => 'required',
                'mobile_number' => 'required',
                'cnic_last_six_digits' => 'required',
            ]);
        }
        //Cash
        if ($request->transaction_platform == "cash"){
            $this->validate($request,[
                'session_id' => 'required',
                'transaction_type' => 'required',
                'insert_date_time' => 'required',
            ]);
        }

        // save
        $transactionPlatform = $request->transaction_platform;
        $sessionPayment = SessionPayment::create($request->all());
        if ($sessionPayment){
            $findSession = Session::find($request->session_id);
            if($findSession){
                $tutorId = $findSession->tutor_id;
                $studentId = $findSession->student_id;
                if ($transactionPlatform == "jazzcash" || $transactionPlatform == "card"){
                    // create disbursement insertion
                    $payType = 'earn';
                    $disbursement = Disbursement::create([
                        'tutor_id' => $tutorId,
                        'type' => $payType,
                        'amount' => $sessionPayment->amount,
                        'paymentable_type' => $sessionPayment->getMorphClass(),
                        'paymentable_id' => $sessionPayment->id
                    ]);
                    $job = (new SessionPaidNotificationToTutor($request->session_id,$findSession->tutor_id, $transactionPlatform));
                    dispatch($job);
                    Log::info('Confirm Noti to tutor '.$findSession->tutor_id.' that Payment DONE '.$transactionPlatform);
                    $jobReceivedPaymentStudent = (new ReceivedPaymentNotification($request->session_id, $findSession->student_id));
                    dispatch($jobReceivedPaymentStudent);
                    Log::info('Confirm Noti to student '.$findSession->student_id.' that session payment DONE '.$transactionPlatform);
                    //Send Email to student
                    $jobSendEmailToStudent = (new SessionPaymentEmail($request->session_id, $studentId, $tutorId));
                    dispatch($jobSendEmailToStudent);
                    return response()->json(
                        [
                            'status' => 'success',
                            'transaction_platform' => $request->transaction_platform,
                            'message' => 'Payment Successfully'
                        ]
                    );
                }
                if ($transactionPlatform == "cash"){
                    $job = (new SessionPaidNotificationToTutor($request->session_id,$findSession->tutor_id, $transactionPlatform));
                    dispatch($job);
                    Log::info('Send Noti to tutorId '.$findSession->tutor_id.' DONE '.$transactionPlatform);
                    return response()->json(
                        [
                            'status' => 'success',
                            'transaction_platform' => $request->transaction_platform,
                            'message' => 'Pay your session amount to tutor. Thank You! '
                        ]
                    );
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'messages' => 'Session not found.'
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'messages' => 'Session payment not saved. Some thing went wrong'
            ]);
        }

    }

    public function studentSessionPaymentsDetail(){
        $userId = Auth::user()->id;
        $session = new Session();
        $data = $session->getStudentSessionPaymentDetail($userId);
        $sessionPaymentDetail = [];
        if ($data) {
            foreach ($data as $user) {
                $user_details = User::where('id', $user->session_user_id)->first();
                if ($user->book_later_at != null || $user->book_later_at != '') {
                    $sessionDate = $user->book_later_at;
                } else {
                    $sessionDate = $user->Session_created_date;
                }
                if ($user_details) {
                    $sessionType = 'now';
                    $checkTrackingOn = $user->book_later_at;
                    if ($checkTrackingOn) {
                        $sessionType = 'later';
                    }
                    $sessionPaymentDetail[] = [
                        'tutorName' => $user_details->firstName . ' ' . $user_details->lastName,
                        'tutor_phone' => $user_details->phone,
                        'status' => $user->session_status,
                        'subject' => $user->s_name,
                        'program' => $user->p_name,
                        'session_Location' => is_null($user->session_location) ? '' : $user->session_location,
                        'session_Duration' => $user->duration,
                        'is_home' => $user->session_is_home,
                        'session_id' => $user->session_id,
                        'session_status' => $user->session_status,
                        'is_group' => $user->session_is_group,
                        'group_members' => $user->session_group_members,
                        'session_rating' => is_null($user->session_rating) ? '' : number_format((float)$user->session_rating, 1, '.', ''),
                        'session_review' => is_null($user->session_review) ? '' : (string)$user->session_review,
                        'Profile_image' => !empty($user_details->profileImage) ? URL::to('/images') . '/' . $user_details->profileImage : '',
                        'book_later_at' => $user->book_later_at,
                        'session_type' => $sessionType,
                        'is_hourly' => $user->is_hourly,
                        'sessionPaymentId' => $user->sessionPaymentId,
                        'sessionPaymentTransactionPlatform' => $user->sessionPaymentTransactionPlatform,
                        'sessionPaymentAmount' => $user->sessionPaymentAmount,
                        'sessionPaymentCreatedAt' => Carbon::parse($user->sessionPaymentCreatedAt)->format('d-m-Y h:iA')
                    ];
                }
            }
            return response()->json(
                [
                    'data' => $sessionPaymentDetail
                ]
            );
        } else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to get session payment'
                ], 422
            );
        }
    }
	public function studentCardSessionPayment(Request $request) {
		$this->validate($request,[
			'amount' => 'required',
			'payment_token' => 'required',
			'agreement' => 'required',
			'sessionId' => 'required'
		]);
		$orderId = rand(100000, 999999);
		$requestBody
			= '{
			"apiOperation": "CREATE_CHECKOUT_SESSION",
			"interaction": {
			"operation": "PURCHASE"
			},
			"order": {
			"id" : "' . $orderId . '",
				"currency" : "PKR"
			}}';
		$ch = curl_init();
		$marchantId = config('alfalah.merchantId');
		$apiPassword = config('alfalah.apiPassword');

		curl_setopt($ch,
			CURLOPT_URL,
			"https://test-bankalfalah.gateway.mastercard.com/api/rest/version/56/merchant/$marchantId/session");// Merchant ID instead of bafl10002
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);  //Post Fields
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$headers = [
			'Authorization: Basic ' . base64_encode("merchant.$marchantId:$apiPassword"),// merchant."Merchant ID":"API Password"
			'Content-Type: application/json',
			'Host: test-bankalfalah.gateway.mastercard.com',
			'Referer: http://tutor4all-api.shayansolutions.com/checkout.php', //Your referrer address
			'cache-control: no-cache',
			'Accept: application/json'
		];

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$server_output = curl_exec($ch);
		curl_close($ch);
		$json = json_decode($server_output, true);
		$sessionId = $json['session']['id'];

		if ($sessionId) {
			$amount        = $request->amount;
			$payment_token = $request->payment_token;
			$agreementID   = $request->agreement;
			$session       = $request->sessionId;

			$transactionId = rand(1, 10);
			$requestBodyPayment
				= '{
				"apiOperation": "PAY",
				"agreement":{
						"id":"' . $agreementID . '",
						"type":"RECURRING",
						"recurring": {
							"amountVariability":"VARIABLE",
							"daysBetweenPayments":"999"
						}
				},
				"session":{
					"id": "' . $sessionId . '"
				},
				"sourceOfFunds": {
					"provided":{
						"card":{
							"storedOnFile":"STORED"
						}
					},
					"type": "SCHEME_TOKEN",
					"token":"' . $payment_token . '"
				},
				"transaction":{
					"source":"MERCHANT"
				},
				"order":{
					"amount":"' . $amount . '",
					"currency": "PKR"
				}
            }';
			$ch = curl_init();
			curl_setopt($ch,
				CURLOPT_URL,
				"https://test-bankalfalah.gateway.mastercard.com/api/rest/version/56/merchant/$marchantId/order/" . $orderId . "/transaction/" . $transactionId);// Merchant ID instead of bafl10002
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBodyPayment);  //Post Fields
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


			$headers = [
				'Authorization: Basic ' . base64_encode("merchant.$marchantId:$apiPassword"),// merchant."Merchant ID":"API Password"
				'Content-Type: application/json',
				'Host: test-bankalfalah.gateway.mastercard.com',
				'Referer: http://tutor4all-api.shayansolutions.com/checkout.php', //Your referrer address
				'cache-control: no-cache',
				'Accept: application/json'
			];

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			$server_output = curl_exec($ch);
			curl_close($ch);

			$json = json_decode($server_output, true);
			if ($json['result'] == 'SUCCESS') {
				$request = new \Illuminate\Http\Request();
				$request->replace([
					'transaction_platform' => 'card',
					'session_id'           => $session,
					'transaction_ref_no'   => $sessionId,
					'transaction_type'     => 'CARD',
					'amount'               => $amount,
					'insert_date_time'     => Carbon::now()->format('yymdhis'),
					'transaction_status'   => 'Paid'
				]);
				return $this->sessionPayment($request);

			} else {
				return response()->json(
					[
						'status'  => 'error',
						'message' => 'payment failed'
					],
					422
				);
			}
		}
	}

}
