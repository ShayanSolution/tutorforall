<?php

namespace App\Models;

use Carbon\Carbon;
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
use App\Models\Profile;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, SoftDeletes, HasApiTokens;

    const ADMIN_ROLE = 'ADMIN_USER';
    const BASIC_ROLE = 'BASIC_USER';
    const ADMIN_ROLE_ID = 1;
    const TUTOR_ROLE_ID = 2;
    const STUDENT_ROLE_ID = 3;
    protected $dates = ['deleted_at'];

    public $appends = ['fullName'];
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
        'is_active',
        'profileImage',
        'device_token',
        'confirmation_code',
        'confirmation_code',
        'confirmed',
        'dob'
    ];

    public static function findOnlineTutors($request){
        $onlineTutorCount = 0;
        //add logic here
        $classId = $request['class_id'];
        $subjectId = $request['subject_id'];
        $category_id = $request['category_id'];
        $is_group = isset($request['is_group']) ? $request['is_group'] : 0;

        if($classId && $subjectId){
            $queryBuilder = User::whereHas('teaches',function($query) use ($classId,$subjectId){ return $query->where('program_id',$classId)->where('subject_id',$subjectId); });
        }
        if($category_id){
            //add logic for category id
            $queryBuilder->with('rating');
        }
        if($is_group){
            $queryBuilder->whereHas('isGroupTutors');
        }

        $queryBuilder->where('is_online',1);

        $result = $queryBuilder->get();
        foreach($result as $record){
            if (round($record->rating->avg('rating')) == $category_id){
                $result[] = $record->rating;
            }
        }
        $onlineTutorCount = count($result);
        return $onlineTutorCount;
    }

    public function teaches(){
        return $this->hasMany(ProgramSubject::class, 'user_id', 'id');

    }

    public function isGroupTutors(){
        return $this->hasMany(Profile::class, 'user_id', 'id')->where('is_group', 1);
    }

    public function rating(){
        return $this->hasMany(Rating::class,'user_id','id');
    }

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function getFullNameAttribute(){
        return $this->firstName.' '.$this->lastName;
    }

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

    public function subjects(){
        return $this->belongsToMany('App\Models\Subject', 'program_subject', 'user_id', 'subject_id');
    }

    public function AauthAcessToken(){
        return $this->hasMany('App\Models\OauthAccessToken');
    }

    public function findForPassport($username) {
        if(Request::input('role') === 'admin'){
            return self::where('email', $username)->first();
        }else{
            $request  = Request::all();
            //$user = self::where('phone', $username)->where('confirmed','=',1)->first();
            $user = self::where('phone', $username)->first();
            if(!$user){
                return $user;
            }
            if(isset($request['device_token']) && !empty($request['device_token'])){
                self::where('id','=',$user->id)->update(['device_token'=>$request['device_token']]);
            }
            return $user;
        }
    }
    
    public function findBookedUser($tutorId, $sessionId){
        $user = User::select('users.*')
                ->select('users.*','programmes.name as p_name','subjects.name as s_name'
                    ,'programmes.id as p_id','subjects.id as s_id','sessions.is_group','sessions.hourly_rate','sessions.is_home as s_is_home', 'sessions.group_members as s_group_members',
                    'profiles.is_home as t_is_home')
                ->join('sessions','sessions.tutor_id','=','users.id')
                ->leftjoin('profiles','profiles.user_id','=','users.id')
                ->leftjoin('programmes','programmes.id','=','sessions.programme_id')
                ->leftjoin('subjects','subjects.id','=','sessions.subject_id')
                ->where('users.role_id','=',2)
                ->where('users.id','=',$tutorId)
                ->where('sessions.id','=',$sessionId)
                ->first();
        return $user;
    }

    public function userProfile($user_id){

       return Self::select('users.*', 'profiles.*','programmes.name as p_name','subjects.name as s_name','genders.name as g_name')
                    ->leftjoin('profiles','profiles.user_id','=','users.id')
                    ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
                    ->leftjoin('subjects','subjects.id','=','profiles.subject_id')
                    ->leftjoin('genders','genders.id','=','users.gender_id')
                    ->where('users.id', $user_id)
                    ->first();
    }

    public function getTutorProfile($data){
        if(isset($data['student_id'])){
            $student_id = $data['student_id'];
            $group = $data['is_group'];
            Profile::updateStudentGroup($student_id,$group);
        }

        $is_mentor = $data['is_deserving'] == 1 ? 1 : 0;

        $query = Self::select('users.*')
            ->join('profiles','profiles.user_id','=','users.id')
            ->join('program_subject','program_subject.user_id','=','users.id')
            ->where('profiles.is_mentor','=', $is_mentor)
            ->where('program_subject.program_id','=',$data['class_id']);

        if($data['gender_id'] != '0')
            $query = $query->where('users.gender_id', '=', $data['gender_id']);

        return $query->where('program_subject.subject_id','=',$data['subject_id'])
            ->where(function ($query) use ($data){
                $query->where('profiles.is_home','=',$data['is_home'])
                    ->orWhere('profiles.call_student','=',$data['call_student']);
            })
            ->where(function ($query) use ($data){
                $query->where('profiles.is_group','=',$data['is_group'])
                    ->orWhere('profiles.one_on_one','=',$data['one_on_one']);
            })
            ->where('users.role_id','=',Config::get('user-constants.TUTOR_ROLE_ID'))
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
        if(count($fullName)>1){
            $firstName = $fullName[0]; $lastName = $fullName[1];
        }else{
            $firstName = $fullName[0]; $lastName = '';
        }

        if(isset($request['passwords']))
            $password = $request['passwords']['password'];
        else
            $password = $request['password'];

        $phone = $request['phone'];
        $uid = str_random(32);
        $user = Self::create([
            'email' => $email,
            'firstName' => $firstName,
            'uid' => $uid,
            'lastName' => $lastName,
            'password' => Hash::make($password),
            'role_id' => Config::get('user-constants.TUTOR_ROLE_ID'),
            'phone'=>$phone
        ])->id;
        
        return $user;
    }

    public static function getStudents(){
        $students = self::select('users.*','profiles.is_deserving')
                    ->join('profiles','profiles.user_id','=','users.id')
                    ->where('role_id', Config::get('user-constants.STUDENT_ROLE_ID'))
                    ->get();
        $student_detail=[];
        $index = 0;
        foreach ($students as $student){
            $student_detail[$index]['id'] = $student->id;
            $student_detail[$index]['firstName'] = $student->firstName;
            $student_detail[$index]['lastName'] = $student->lastName;
            $student_detail[$index]['username'] = $student->username;
            $student_detail[$index]['email'] = $student->email;
            $student_detail[$index]['city'] = $student->city;
            $student_detail[$index]['country'] = $student->country;
            if($student->is_deserving == '1'){
                $student_detail[$index]['is_deserving'] = 'Yes';
            }else{
                $student_detail[$index]['is_deserving'] = 'No';
            }
            if($student->is_active == '1'){
                $student_detail[$index]['is_active'] = 'Yes';
            }else{
                $student_detail[$index]['is_active'] = 'No';
            }
            $index++;
        }
        return $student_detail;
    }
    
    public static function updateUserActiveStatus($id){
        $user = Self::where('id',$id)->first();
        if($user->is_active == 0){
            $is_active = 1;
        }else{
            $is_active = 0;
        }
        self::where('id',$id)->update(['is_active'=>$is_active]);
    }

    public static function getTutors(){
        $tutors = self::select('users.*','profiles.is_deserving')
            ->join('profiles','profiles.user_id','=','users.id')
            ->where('role_id', Config::get('user-constants.TUTOR_ROLE_ID'))
            ->get();
        $tutor_detail=[];
        $index = 0;
        foreach ($tutors as $tutor){
            $tutor_detail[$index]['id'] = $tutor->id;
            $tutor_detail[$index]['firstName'] = $tutor->firstName;
            $tutor_detail[$index]['lastName'] = $tutor->lastName;
            $tutor_detail[$index]['username'] = $tutor->username;
            $tutor_detail[$index]['email'] = $tutor->email;
            $tutor_detail[$index]['city'] = $tutor->city;
            $tutor_detail[$index]['country'] = $tutor->country;

            if($tutor->is_active == '1'){
                $tutor_detail[$index]['is_active'] = 'Yes';
            }else{
                $tutor_detail[$index]['is_active'] = 'No';
            }
            $index++;
        }
        return $tutor_detail;
    }

    public static function updateTutorLocation($user_id,$longitude,$latitude, $address){
        return User::where('id','=',$user_id)->update(['longitude'=>$longitude,'latitude'=>$latitude, 'address'=>$address]);
    }

    public static function updateUserValues($id,$update_profile_values){
        self::where('id','=',$id)->update($update_profile_values);
    }

    public static function updateToken($request){

        return self::where('id',$request['user_id'])->update(['device_token'=>$request['device_token']]);
    }

    public static function updateWhere($where, $update){

        return self::where($where)->update($update);
    }


    public static function generateErrorResponse($validator){
        $response = null;
        if ($validator->fails()) {
            $response = $validator->errors()->toArray();
            $response['error'] = $validator->errors()->toArray();
            $response['code'] = 500;
            $response['message'] = 'Error occured';
        }
        else{
            $response['code'] = 200;
            $response['message'] = 'operation completed successfully';
        }
        return $response;
    }
    
    
    public static function findByPhoneNumber($phone)
    {
        $phoneWithoutCode = substr($phone,-10);
        return self::where('phone','like','%'.$phoneWithoutCode)->withTrashed()->first();
    }


    public static function findByExactPhoneNumber($phone)
    {
        return self::where('phone',$phone)->withTrashed()->first();
    }


    public function isActive($phone)
    {
        return self::where('phone','like','%'.$phone)->where('is_active', 1)->first();
    }

    public static function isEligibleToRequestResetPassword($phone, $roleId){

        $phoneCodeAlreadyGeneratedWithinOneDay = PhoneCode::where('phone', 'LIKE', '%'.substr($phone,-10))
            ->where('verified', 0)
            ->where('created_at', '>', Carbon::now()->subDay())
            ->first();

        $user = self::where('phone', $phone)->where('role_id', $roleId)->first();

        if(!$user || $user->is_active != 1 || $phoneCodeAlreadyGeneratedWithinOneDay){

            $message = '';

            if(!$user)
                $message    =   'User is not registered!';
            elseif ($user->is_active != 1)
                $message    =   'User is not active!';
            elseif ($phoneCodeAlreadyGeneratedWithinOneDay)
                $message    =   'You have already applied for Password Reset. You can avail this facility after   '.Carbon::parse($phoneCodeAlreadyGeneratedWithinOneDay->created_at)->addDay()->format('M d, Y h:i A');

            return [
                'status'    =>  'error',
                'message'   =>  $message
            ];
        }

        return [
            'status'    =>  'success',
            'message'   =>  'Eligible to request for reset password'
        ];
    }
}
