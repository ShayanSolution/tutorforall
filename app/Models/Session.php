<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

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
        $session = new Session;
        $session->tutor_id = $tutor_id;
        $session->student_id = $student_id;
        $session->programme_id = $programme_id;
        $session->subject_id = $subject_id;
        $session->status = $status;
        $session->subscription_id = 3;
        $session->meeting_type_id = 1;
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
        return User::select('users.*')
                ->select('users.*','sessions.created_at as Session_created_date','programmes.name as p_name','profiles.is_group'
                    ,'sessions.status as session_status','subjects.name as s_name','sessions.student_id as session_user_id')
                ->join('sessions','sessions.tutor_id','=','users.id')
                ->join('profiles','profiles.user_id','=','users.id')
                ->join('programmes','programmes.id','=','profiles.programme_id')
                ->join('subjects','subjects.id','=','profiles.subject_id')
                ->where('users.role_id','=',Config::get('user-constants.TUTOR_ROLE_ID'))
                ->where('users.id','=',$tutor_id)
                ->where('sessions.status','=','pending')
                ->orWhere('sessions.status','=','reject')
                ->get();
    }

    public function getTutorSessionDetail($tutor_id){
        $tutor_session_detail = User::select('users.*')
                                ->select('users.*','sessions.created_at as Session_created_date'
                                    ,'sessions.status as session_status','subjects.name as s_name','sessions.student_id as session_user_id')
                                ->join('sessions','sessions.tutor_id','=','users.id')
                                ->join('profiles','profiles.user_id','=','users.id')
                                ->join('programmes','programmes.id','=','profiles.programme_id')
                                ->join('subjects','subjects.id','=','profiles.subject_id')
                                ->where('users.role_id','=',Config::get('user-constants.TUTOR_ROLE_ID'))
                                ->where('users.id','=',$tutor_id)
                                ->where('sessions.status','=','booked')
                                ->orWhere('sessions.status','=','ended')
                                ->get();
        return $tutor_session_detail;
    }
    
    public function getStudentSessionDetail($student_id){
        $student_session_detail = User::select('users.*')
                                    ->select('users.*','sessions.created_at as Session_created_date'
                                        ,'sessions.status as session_status','subjects.name as s_name','sessions.tutor_id as session_user_id')
                                    ->join('sessions','sessions.student_id','=','users.id')
                                    ->join('profiles','profiles.user_id','=','users.id')
                                    ->join('programmes','programmes.id','=','profiles.programme_id')
                                    ->join('subjects','subjects.id','=','profiles.subject_id')
                                    ->where('users.role_id','=',Config::get('user-constants.STUDENT_ROLE_ID'))
                                    ->where('users.id','=',$student_id)
                                    ->where('sessions.status','=','booked')
                                    ->orWhere('sessions.status','=','ended')
                                    ->get();
        return $student_session_detail;
    }
}
