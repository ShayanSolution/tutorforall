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
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function meetingType()
    {
        return $this->belongsTo('App\Models\MeetingType');
    }
    
    public static function createUserProfile($data){
        $tutor_id = isset($data['tutor_id'])?$data['tutor_id']:'';
        $programme_id = isset($data['programme_id'])?$data['programme_id']:'';
        $subject_id = isset($data['subject_id'])?$data['subject_id']:'';
        
        $tutor_profile = new Profile();
        $tutor_profile->programme_id = $programme_id;
        $tutor_profile->subject_id = $subject_id;
        $tutor_profile->user_id = $tutor_id;
        $tutor_profile->is_home = 0;
        $tutor_profile->is_group = 0;
        $tutor_profile->save();
    }
    
    public static function updateUserProfile($update_profile_values){
        Profile::where('user_id','=',$update_profile_values['user_id'])->update($update_profile_values);
    }
}
