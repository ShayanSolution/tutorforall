<?php

namespace App\Models;

use App\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Session extends Model
{
    protected $fillable = [
        'name',
        'student_id',
        'tutor_id',
        'programme_id',
        'subject_id',
        'subscription_id',
        'meeting_type_id',
        'is_group',
        'group_members',
        'status',
        'started_at',
        'ended_at',
        'duration',
        'latitude',
        'longitude',
        'session_location',
        'book_later_at',
        'hourly_rate',
        'is_home',
        'session_sent_group',
        'hourly_rate_past_first_hour',
        'original_hourly_rate',
        'tracking_on',
        'start_session_enable',
        'area',
        'city',
        'province',
        'country',
        'cancelled_by',
        'cancelled_from',
        'is_hourly',
        'demo_started_at',
        'demo_ended_at'
    ];

    public function student()
    {
        return $this->belongsTo('App\Models\User', 'student_id');
    }

    public function tutor()
    {
        return $this->belongsTo('App\Models\User', 'tutor_id');
    }

    public function programme()
    {
        return $this->belongsTo('App\Models\Programme');
    }
    public function subject()
    {
        return $this->belongsTo('App\Models\Subject');
    }

    public function meetingType()
    {
        return $this->belongsTo('App\Models\MeetingType');
    }

    public function rating()
    {
        return $this->hasOne('App\Models\Rating');
    }

    public function invoice()
    {
        return $this->hasOne('App\Models\Invoice');
    }


    public function addSession($data){
        $data['subscription_id']= 3;
        $data['meeting_type_id']= 1;
        
        $session = Session::create($data);
        return $session;
    }

    public function createOrUpdateSession($data){
        Log::info('createOrUpdateSession called: '.print_r($data, true));
        $data['subscription_id']= 3;
        $data['meeting_type_id']= 1;

        $session = new Session();

        $getSession = $session->where(
                        [
                            'tutor_id'=>$data['tutor_id'],
                            'student_id'=>$data['student_id'],
                            'programme_id'=>$data['programme_id'],
                            'subject_id'=>$data['subject_id'],
                        ])
                        ->where(function ($query) {
                            $query->where('status','pending')->orWhere('status','reject');
                        })
                        ->whereDate('started_at', '=', date('Y-m-d', strtotime($data['started_at'])))->first();

        if(!$getSession){
            $updateSession = Session::create($data);
            Log::info('New session created with id: '.$updateSession->id);
            return $updateSession;
        }
        else{
            $updateSession = $getSession->update($data);
            Log::info('Session updated with id: '.$getSession->id);
            return $getSession;
        }

        
    }

    public function findSessionById($session_id){
        return Session::find($session_id);
    }
    

    public function saveSession($data){
        $tutor_id = $data['tutor_id'];
        $student_id = $data['student_id'];
        $programme_id = $data['class_id'];
        $subject_id = $data['subject_id'];

        if(isset($data['status'])){
            $status = 'reject';
        }else{
            $status = 'booked';
        }
        //check if session already exist.
        $session = Session::where(['tutor_id'=>$tutor_id, 'student_id'=>$student_id, 'programme_id'=>$programme_id, 'subject_id'=> $subject_id])->first();
        if(!$session){
            $session = new Session;
        }
        
        $session->tutor_id = $tutor_id;
        $session->student_id = $student_id;
        $session->programme_id = $programme_id;
        $session->subject_id = $subject_id;
        $session->status = $status;
        $session->subscription_id = 3;
        $session->meeting_type_id = 1;
        if(isset($data['longitude'])){
            $session->longitude = $data['longitude'];
        }
        if(isset($data['latitude'])){
            $session->latitude = $data['latitude'];
        }
        if(isset($data['rate'])){
            $session->rate = $data['rate'];
        }
        if(isset($data['duration'])){
            $session->duration = $data['duration'];
        }
        $session->save();
        return $session;
    }

    public function findStudentSession($data){
        $student_id = $data['student_id'];
        $programme_id = $data['class_id'];
        $subject_id = $data['subject_id'];
        return Session::where('student_id','=',$student_id)
                ->where('programme_id','=',$programme_id)
                ->where('subject_id','=',$subject_id)
                ->where('status','=','booked')
                ->first();
    }
    
    public function findRequestSession($tutor_id){
        return User::select('users.*','sessions.created_at as Session_created_date','programmes.name as p_name','profiles.is_group','sessions.duration'
                    ,'profiles.programme_id','profiles.subject_id','sessions.rate','sessions.hourly_rate','sessions.duration','sessions.longitude','sessions.latitude','sessions.session_location'
                    ,'sessions.status as session_status','subjects.name as s_name','sessions.student_id as session_user_id','sessions.id as session_id')
                ->join('sessions','sessions.tutor_id','=','users.id')
                ->join('profiles','profiles.user_id','=','users.id')
                ->join('programmes','programmes.id','=','sessions.programme_id')
                ->join('subjects','subjects.id','=','sessions.subject_id')
                ->where('users.role_id','=',Config::get('user-constants.TUTOR_ROLE_ID'))
                ->where('users.id','=',$tutor_id)
                ->where(function($q){
                    $q->where('sessions.status','=','pending')
                    ->orWhere('sessions.status','=','reject');
                })
                ->orderBy('sessions.updated_at', 'Desc')
                ->get();
    }

    public function getTutorSessionDetail($tutor_id){
        $tutor_session_detail = User::select('users.*','sessions.created_at as Session_created_date','programmes.name as p_name', 'sessions.id as session_id', 'sessions.student_id','sessions.tracking_on','sessions.start_session_enable','sessions.is_hourly','sessions.demo_started_at'
                                    ,'sessions.book_later_at','sessions.session_location','rate','sessions.hourly_rate','sessions.hourly_rate_past_first_hour','sessions.duration' ,'sessions.status as session_status', 'sessions.is_group as session_is_group'
                                    , 'sessions.is_home as session_is_home', 'sessions.latitude as session_latitude', 'sessions.longitude as session_longitude', 'sessions.group_members as session_group_members'
                                    ,'subjects.name as s_name','sessions.student_id as session_user_id'
                                    ,'ratings.rating as session_rating','ratings.review as session_review')
                                ->join('sessions','sessions.tutor_id','=','users.id')
                                /*->join('profiles','profiles.user_id','=','users.id')*/
                                ->join('programmes','programmes.id','=','sessions.programme_id')
                                ->join('subjects','subjects.id','=','sessions.subject_id')
                                ->leftJoin('ratings','ratings.session_id','=','sessions.id')
                                ->where('users.role_id','=',Config::get('user-constants.TUTOR_ROLE_ID'))
                                ->where('users.id','=',$tutor_id)
                                ->where(function($q){
                                    $q->where('sessions.status','=','booked')
                                        ->orWhere('sessions.status','=','ended');
                                })
                                ->orderBy('sessions.updated_at', 'DESC')
                                ->get();
        $session_detail=[];
        $index = 0;
        foreach ($tutor_session_detail as $session){
//            $trackingON = 0;
            $student_detail = User::where('id',$session->student_id)->first();
            $sessionPayment = SessionPayment::where('session_id', $session->session_id)->first();
            if($session->book_later_at != null || $session->book_later_at != ''){
                $sessionDate = $session->book_later_at;
                $sessionType = 'later';
//                $bookLaterTime = Carbon::parse($sessionDate);
//                $currentTime = Carbon::parse(Carbon::now());
//                $hours = $currentTime->diffInHours($bookLaterTime);
//                if ($hours <= 1) {
//                    $trackingON = 1;
//                }
            }else{
                $sessionDate = $session->Session_created_date;
                $sessionType = 'now';
//                $trackingON = 0;
            }
            $session_detail[$index]['session_id'] = $session->session_id;
            $session_detail[$index]['session_status'] = $session->session_status;
            $session_detail[$index]['session_duration'] = $session->duration;
            $session_detail[$index]['session_rating'] = is_null($session->session_rating)?'':number_format((float)$session->session_rating, 1, '.', '');
            $session_detail[$index]['session_review'] = is_null($session->session_review)?'':(string)$session->session_review;
            $session_detail[$index]['session_amount'] = $session->rate;
            if ($session->session_status == 'ended') {
                $session_detail[$index]['amount'] = isset($sessionPayment->amount)?$sessionPayment->amount:'';
                $session_detail[$index]['received_amount'] = isset($sessionPayment->paid_amount)?$sessionPayment->paid_amount:'';
                $session_detail[$index]['received_amount_platform'] = isset($sessionPayment->transaction_platform)?$sessionPayment->transaction_platform:'';
                $session_detail[$index]['wallet_payment'] = isset($sessionPayment->wallet_payment)?$sessionPayment->wallet_payment:'';
                $session_detail[$index]['transaction_status'] = isset($sessionPayment->transaction_status)?$sessionPayment->transaction_status:'';
            }
            $session_detail[$index]['s_name'] = $session->s_name;
            $session_detail[$index]['p_name'] = $session->p_name;
            $session_detail[$index]['student_id'] = $session->student_id;
            $session_detail[$index]['firstName'] = !empty($student_detail->firstName)?$student_detail->firstName:'';
            $session_detail[$index]['lastName'] = !empty($student_detail->lastName)?$student_detail->lastName:'';
            $session_detail[$index]['student_phone'] = $student_detail->phone;
            $session_detail[$index]['id'] = $session->id;
            $session_detail[$index]['is_home'] = $session->session_is_home;
            $session_detail[$index]['is_group'] = $session->session_is_group;
            $session_detail[$index]['group_members'] = $session->session_group_members;
            $session_detail[$index]['tutor_Longitude'] = $session->longitude;
            $session_detail[$index]['tutor_Latitude'] = $session->latitude;
            $session_detail[$index]['Student_Longitude'] = $student_detail->longitude;
            $session_detail[$index]['Student_Latitude'] = $student_detail->latitude;
            $session_detail[$index]['Session_Location'] = is_null($session->session_location)?'':$session->session_location;
            $session_detail[$index]['Session_Latitude'] = $session->session_latitude;
            $session_detail[$index]['Session_Longitude'] = $session->session_longitude;
            $session_detail[$index]['Hour'] = $session->duration;
            $session_detail[$index]['Price'] = $session->rate;
            $session_detail[$index]['hourly_rate'] = $session->hourly_rate;
            $session_detail[$index]['hourly_rate_past_first_hour'] = $session->hourly_rate_past_first_hour;
            $session_detail[$index]['Date'] = $sessionDate;
            $session_detail[$index]['Age'] = Carbon::parse($session->dob)->age;
            $session_detail[$index]['Profile_image'] = !empty($student_detail->profileImage)?URL::to('/images').'/'.$student_detail->profileImage:'';
            $session_detail[$index]['book_later_at'] = $session->book_later_at;
            $session_detail[$index]['session_type'] = $sessionType;
            $session_detail[$index]['tracking_on'] = $session->tracking_on;
            $session_detail[$index]['start_session_enable'] = $session->start_session_enable;
            $session_detail[$index]['is_hourly'] = $session->is_hourly;
            $session_detail[$index]['demo_started_at'] = $session->demo_started_at;

            $index++;
        }
       // echo "<pre>"; print_r($session_detail); dd();
        return $session_detail;
    }
    
    public function getStudentSessionDetail($student_id){
        $student_session_detail = User::select('users.*', 'sessions.created_at as Session_created_date','sessions.longitude','sessions.latitude','sessions.hourly_rate','sessions.tracking_on','sessions.hourly_rate_past_first_hour','sessions.session_location','rate','sessions.duration','sessions.is_hourly', 'sessions.demo_started_at'
                                        ,'sessions.book_later_at','sessions.status as session_status','subjects.name as s_name', 'programmes.name as p_name','sessions.tutor_id as session_user_id','sessions.id as session_id'
                                        ,'sessions.is_home as session_is_home', 'sessions.is_group as session_is_group', 'sessions.group_members as session_group_members'
                                        ,'ratings.rating as session_rating','ratings.review as session_review')
                                    ->join('sessions','sessions.student_id','=','users.id')
                                    /*->join('profiles','profiles.user_id','=','users.id')*/
                                    ->join('programmes','programmes.id','=','sessions.programme_id')
                                    ->join('subjects','subjects.id','=','sessions.subject_id')
                                    ->leftJoin('ratings','ratings.session_id','=','sessions.id')
                                    ->where('users.role_id','=',Config::get('user-constants.STUDENT_ROLE_ID'))
                                    ->where('users.id','=',$student_id)
                                    ->where(function($q){
                                        $q->where('sessions.status','=','booked')
                                        ->orWhere('sessions.status','=','ended');
                                    })
                                    ->orderBy('sessions.updated_at', 'DESC')
                                    ->get();
        return $student_session_detail;
    }

    public function updateSession($where, $update){
        $session = self::where($where)->update($update);
        return $session;
    }

    public function getStudentSessionPaymentDetail($student_id){
        $student_session_detail = User::select('users.*', 'session_payments.id as sessionPaymentId', 'session_payments.transaction_platform as sessionPaymentTransactionPlatform', 'session_payments.amount as sessionPaymentAmount', 'session_payments.paid_amount as sessionPaidAmount', 'session_payments.wallet_payment as walletPaidAmount', 'session_payments.created_at as sessionPaymentCreatedAt'
            , 'sessions.created_at as Session_created_date','sessions.longitude','sessions.latitude','sessions.hourly_rate','sessions.tracking_on','sessions.hourly_rate_past_first_hour'
            ,'sessions.session_location','rate','sessions.duration','sessions.is_hourly'
            ,'sessions.book_later_at','sessions.status as session_status','subjects.name as s_name', 'programmes.name as p_name','sessions.tutor_id as session_user_id','sessions.id as session_id'
            ,'sessions.is_home as session_is_home', 'sessions.is_group as session_is_group', 'sessions.group_members as session_group_members'
            ,'ratings.rating as session_rating','ratings.review as session_review')
            ->join('sessions','sessions.student_id','=','users.id')
            ->join('session_payments','session_payments.session_id','=','sessions.id')
            ->join('programmes','programmes.id','=','sessions.programme_id')
            ->join('subjects','subjects.id','=','sessions.subject_id')
            ->leftJoin('ratings','ratings.session_id','=','sessions.id')
            ->where('users.role_id','=',Config::get('user-constants.STUDENT_ROLE_ID'))
            ->where('users.id','=',$student_id)
            ->where(function($q){
                $q->Where('sessions.status','=','ended');
            })
            ->orderBy('sessions.updated_at', 'DESC')
            ->get();
        return $student_session_detail;
    }

    public function getTutorSessionPaymentDetail($tutorId){
        $tutor_session_detail = User::select('users.*', 'session_payments.id as sessionPaymentId', 'session_payments.transaction_platform as sessionPaymentTransactionPlatform', 'session_payments.amount as sessionPaymentAmount', 'session_payments.paid_amount as sessionPaidAmount', 'session_payments.wallet_payment as walletPaidAmount', 'session_payments.created_at as sessionPaymentCreatedAt'
            , 'sessions.created_at as Session_created_date','sessions.longitude','sessions.latitude','sessions.hourly_rate','sessions.tracking_on','sessions.hourly_rate_past_first_hour'
            ,'sessions.session_location','rate','sessions.duration','sessions.is_hourly'
            ,'sessions.book_later_at','sessions.status as session_status','subjects.name as s_name', 'programmes.name as p_name','sessions.student_id as session_user_id','sessions.id as session_id'
            ,'sessions.is_home as session_is_home', 'sessions.is_group as session_is_group', 'sessions.group_members as session_group_members'
            ,'ratings.rating as session_rating','ratings.review as session_review')
            ->join('sessions','sessions.tutor_id','=','users.id')
            ->join('session_payments','session_payments.session_id','=','sessions.id')
            ->join('programmes','programmes.id','=','sessions.programme_id')
            ->join('subjects','subjects.id','=','sessions.subject_id')
            ->leftJoin('ratings','ratings.session_id','=','sessions.id')
            ->where('users.role_id','=',Config::get('user-constants.TUTOR_ROLE_ID'))
            ->where('users.id','=',$tutorId)
            ->where(function($q){
                $q->Where('sessions.status','=','ended');
            })
            ->orderBy('sessions.updated_at', 'DESC')
            ->get();
        return $tutor_session_detail;
    }
    

}
