<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Profile extends Model
{
    protected $fillable = [
        'name',
        'is_mentor',
        'is_deserving',
        'meeting_type_id',
        'user_id',
        'subject_id',
        'programme_id',
        'is_home',
        'is_group',
        'one_on_one',
        'call_tutor',
        'call_student',
        'teach_to',
        'min_slider_value',
        'max_slider_value',
        'is_book_now',
        'is_book_later',
        'book_later_longitude',
        'book_later_latitude',
        'book_later_address',
        'book_later_current_location'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function meetingType()
    {
        return $this->belongsTo('App\Models\MeetingType');
    }
    
    public static function createUserProfile($id,$update_profile_values){
        $update_profile_values['user_id'] = $id;
        //dd($update_profile_values);
        Profile::create($update_profile_values);
    }
    
    public static function updateUserProfile($id,$update_profile_values){
        return Profile::where('user_id','=',$id)->update($update_profile_values);
    }
    
    public static function registerUserProfile($tutor_id, $isMentor = 0){
        $profile = Self::updateOrCreate(
            [
                'user_id'=>$tutor_id,
            ],
            [
                'is_mentor' => $isMentor,
                'is_deserving' => 0,
                'is_home' => 1,
                'is_group' => 1,
                'meeting_type_id' => 0,
                'user_id' => $tutor_id,
                'programme_id' => 0,
                'subject_id' => 0,
                'call_student' => 1,
                'one_on_one' => 1,
                'min_slider_value' => 0,
                'max_slider_value' => 1000,
        ])->id;

        
        return $profile;
    }

    public static function updateStudentGroup($student_id,$group){
        self::where('user_id',$student_id)->update(['is_group'=>$group]);
    }

    public static function updateDerserveStatus($student_id){
        $result = Self::where('user_id',$student_id)->first();
        if($result->is_deserving == 0){
            $deserving_status = 1;
        }else{
            $deserving_status = 0;
        }
        self::where('user_id',$student_id)->update(['is_deserving'=>$deserving_status]);

    }
}
