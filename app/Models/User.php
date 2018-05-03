<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Request;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, SoftDeletes, HasApiTokens;

    const ADMIN_ROLE = 'ADMIN_USER';
    const BASIC_ROLE = 'BASIC_USER';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uid',
        'firstName',
        'lastName',
        'middleName',
        'email',
        'password',
        'address',
        'zipCode',
        'username',
        'city',
        'state',
        'country',
        'phone',
        'mobile',
        'role_id',
        'isActive',
        'profileImage',
        'device_token',
        'confirmation_code',
        'confirmation_code',
        'confirmed',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return (isset($this->role) ? $this->role : self::BASIC_ROLE) == self::ADMIN_ROLE;
    }

    public function profile()
    {
        return $this->hasOne('App\Models\Profile');
    }

    public function transaction()
    {
        return $this->hasMany('App\Models\Transaction');
    }

    public function student()
    {
        return $this->hasMany('App\Models\Sessions', 'student_id');
    }

    public function tutor()
    {
        return $this->hasMany('App\Models\Sessions', 'tutor_id');
    }

    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }

    public function findForPassport($username) {
        if(Request::input('role') === 'admin'){
            return self::where('email', $username)->first();
        }else{
            $request  = Request::all();
            $user = self::where('phone', $username)->where('confirmed','=',1)->first();
            if(!$user){
                return $user;
            }
            if(isset($request['device_token']) && !empty($request['device_token'])){
                self::where('id','=',$user->id)->update(['device_token'=>$request['device_token']]);
            }
            return $user;
        }
    }
    
    public function findBookedUser($tutor_id){
        $user = User::select('users.*')
                ->select('users.*','programmes.name as p_name','subjects.name as s_name'
                    ,'programmes.id as p_id','subjects.id as s_id','profiles.is_group',
                    'profiles.is_home as t_is_home')
                ->leftjoin('profiles','profiles.user_id','=','users.id')
                ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
                ->leftjoin('subjects','subjects.id','=','profiles.subject_id')
                ->where('users.role_id','=',2)
                ->where('users.id','=',$tutor_id)
                ->first();
        return $user;
    }

    public function userProfile($user_id){

       return Self::select('users.*','programmes.name as p_name','subjects.name as s_name','genders.name as g_name','rating')
                    ->leftjoin('profiles','profiles.user_id','=','users.id')
                    ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
                    ->leftjoin('subjects','subjects.id','=','profiles.subject_id')
                    ->leftjoin('genders','genders.id','=','users.gender_id')
                    ->leftjoin('ratings','ratings.user_id','=','users.id')
                    ->where('users.id', $user_id)
                    ->first();
    }

    public function getTutorProfile($data){
        return Self::select('users.*')
                ->join('profiles','profiles.user_id','=','users.id')
                ->where('profiles.programme_id','=',$data['class_id'])
                ->where('profiles.subject_id','=',$data['subject_id'])
                ->where('profiles.is_home','=',$data['is_home'])
                ->where('profiles.is_group','=',$data['is_group'])
                ->where('users.role_id','=',2)
                ->get();
    }
    
    public static function updateProfileImage($tutor_id,$file_name,$role_id){
        User::where('id','=',$tutor_id)->where('role_id','=',$role_id)-> update(['profileImage'=>$file_name]);
    }
    
    public static function updateUserProfile($tutor_id,$update_array,$role_id){
        User::where('id','=',$tutor_id)->where('role_id','=',$role_id)-> update($update_array);
    }
    
    public static function registerTutor($request){
        //print_r($request); dd();
        $email = $request['email'];
        $fullName = explode(" ",$request['name']);
        if(count($fullName)>0){
            $firstName = $fullName[0]; $lastName = $fullName[0];
        }
        $password = $request['passwords']['password'];
        $phone = $request['phone'];

        $user = Self::create([
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'password' => Hash::make($password),
            'role_id' => Config::get('user-constants.TUTOR_ROLE_ID'),
            'phone'=>$phone
        ])->id;
        
        return $user;
    }
}
