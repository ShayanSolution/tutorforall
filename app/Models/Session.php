<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
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
        'longitude'
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
                    ,'profiles.programme_id','profiles.subject_id','sessions.rate','sessions.duration','sessions.longitude','sessions.latitude'
                    ,'sessions.status as session_status','subjects.name as s_name','sessions.student_id as session_user_id')
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
                ->get();
    }

    public function getTutorSessionDetail($tutor_id){
        $tutor_session_detail = User::select('users.*','sessions.created_at as Session_created_date','programmes.name as p_name','sessions.student_id'
                                   ,'sessions.longitude','sessions.latitude','rate','duration' ,'sessions.status as session_status',
                                    'subjects.name as s_name','sessions.student_id as session_user_id')
                                ->join('sessions','sessions.tutor_id','=','users.id')
                                ->join('profiles','profiles.user_id','=','users.id')
                                ->join('programmes','programmes.id','=','sessions.programme_id')
                                ->join('subjects','subjects.id','=','sessions.subject_id')
                                ->where('users.role_id','=',Config::get('user-constants.TUTOR_ROLE_ID'))
                                ->where('users.id','=',$tutor_id)
                                ->get();
        $session_detail=[];
        $index = 0;
        foreach ($tutor_session_detail as $session){
            $student_detail = User::where('id',$session->student_id)->first();
            $session_detail[$index]['session_status'] = $session->session_status;
            $session_detail[$index]['s_name'] = $session->s_name;
            $session_detail[$index]['p_name'] = $session->p_name;
            $session_detail[$index]['student_id'] = $session->student_id;
            $session_detail[$index]['firstName'] = $student_detail->firstName;
            $session_detail[$index]['lastName'] = $student_detail->lastName;
            $session_detail[$index]['id'] = $session->id;
            $session_detail[$index]['Student_Longitude'] = $session->longitude;
            $session_detail[$index]['Student_Latitude'] = $session->latitude;
            $session_detail[$index]['Hour'] = $session->duration;
            $session_detail[$index]['Price'] = $session->rate;
            $session_detail[$index]['Date'] = $session->Session_created_date;
            $session_detail[$index]['Age'] = Carbon::parse($session->dob)->age;
            $session_detail[$index]['Profile_image'] = $session->profileImage;
            $index++;
        }
       // echo "<pre>"; print_r($session_detail); dd();
        return $session_detail;
    }
    
    public function getStudentSessionDetail($student_id){
        $student_session_detail = User::select('users.*','sessions.created_at as Session_created_date','sessions.longitude','sessions.latitude','rate','duration'
                                        ,'sessions.status as session_status','subjects.name as s_name','sessions.tutor_id as session_user_id','sessions.id as session_id')
                                    ->join('sessions','sessions.student_id','=','users.id')
                                    ->join('profiles','profiles.user_id','=','users.id')
                                    ->join('programmes','programmes.id','=','sessions.programme_id')
                                    ->join('subjects','subjects.id','=','sessions.subject_id')
                                    ->where('users.role_id','=',Config::get('user-constants.STUDENT_ROLE_ID'))
                                    ->where('users.id','=',$student_id)
                                    ->where(function($q){
                                        $q->where('sessions.status','=','booked')
                                        ->orWhere('sessions.status','=','ended');
                                    })
                                    ->get();
        
        return $student_session_detail;
    }



    public function updateSession($data){
        $tutor_id = $data['tutor_id'];
        $student_id = $data['student_id'];
        $programme_id = $data['class_id'];
        $subject_id = $data['subject_id'];
        $status = $data['status'];

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


}
