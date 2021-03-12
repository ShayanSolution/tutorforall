<?php

namespace App\Models;

use App\Helpers\ReverseGeocode;
use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
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
        'dob',
        'gender_id',
        'offline_notification',
        'last_login',
        'area',
        'province',
        'reverse_geocode_address',
        'final_phone_verification',
        'is_documents_uploaded',
        'term_and_condition',
        'updated_at',
        'cnic_no'
    ];

    public static function findOnlineTutors($request, $hourlyRate){
        //add logic here
        $classId = $request['class_id'];
        $subjectId = $request['subject_id'];
        $category_id = $request['category_id'];
        $is_home        = $request['is_home'];
        $call_student   = $request['call_student'];
        $is_group = isset($request['is_group']) ? $request['is_group'] : 0;
        $selected_rate = isset($request['selected_rate']) ? $request['selected_rate'] : 0;
        $experience = $request['experience'];
        $gender_id = $request['gender_id'];
        $session_type = $request['session_type'];
        $is_hourly = $request['is_hourly'];
        $sessionLat = $request['latitude'];
        $sessionLong = $request['longitude'];
        $bookLaterRestriction = Setting::where('group_name', 'book-later-restrict-hr')->pluck('value', 'slug');

        if(!$classId || !$subjectId){
            return false;
        }

        $queryBuilder = self::whereHas('teaches', function($query) use ($classId, $subjectId){
            return $query->where('program_id',$classId)->where('subject_id',$subjectId)->where('status', 1);
        });

        if($is_home == 1 || $call_student == 1) {
            $queryBuilder = $queryBuilder->whereHas('profile', function($q) use ($is_home, $call_student)
            {
                return $q->where('is_home',  $is_home)->orWhere('call_student',  $call_student);
            });
        }

        if($category_id){
            //add logic for category id
            $queryBuilder = $queryBuilder->whereHas('rating', function($q) use ($category_id) {
                $q->havingRaw('AVG(ratings.rating) >= ?', [$category_id]);
            });
        }
        if($is_group){
            $queryBuilder = $queryBuilder->whereHas('isGroupTutors');
        }

        if ($experience) {
//            $queryBuilder = $queryBuilder->selectRaw(" @sum_of_students_whom_learned_in_group := (SELECT SUM(DISTINCT group_members) FROM sessions where sessions.tutor_id = users.id AND sessions.`status` = 'ended' AND sessions.is_group = 1), @sum_of_students_whom_learned_individually := (SELECT COUNT(DISTINCT group_members) FROM sessions where sessions.tutor_id = users.id AND sessions.`status` = 'ended' AND sessions.is_group = 0), ROUND(@sum_of_students_whom_learned_in_group + @sum_of_students_whom_learned_individually) as experience ");
//
//            $queryBuilder->havingRaw('experience >= ?',[$experience]);
            // @todo Need to change logic according to group counts. Currently on session count if group count more than one currently it's equals to one
            $queryBuilder = $queryBuilder->whereHas('sessions', function ($q) use ($experience){
                $q->havingRaw('COUNT(sessions.tutor_id) >= ?', [$experience])->where('status', 'ended');
            });
        }

        if ($gender_id != 0) {
            $queryBuilder = $queryBuilder->where('users.gender_id', '=', $gender_id);
        }

//        if ($hourlyRate != 0) {
//            $queryBuilder = $queryBuilder->whereHas('profile', function($q) use ($hourlyRate)
//            {
//                return $q->where('min_slider_value', '<=', $hourlyRate)->where('max_slider_value', '>=', $hourlyRate);
//            });
//        }
        if ($selected_rate) {
            $queryBuilder = $queryBuilder->whereHas('profile', function($q) use ($selected_rate)
            {
                return $q->where('min_slider_value', '<=', $selected_rate);
            });
        }
        // check tutor settings
        if ($session_type == 'now'){
            $queryBuilder = $queryBuilder->whereHas('profile', function($q)
            {
                return $q->where('is_book_now',  1);
            });
        } else {
            $queryBuilder = $queryBuilder->whereHas('profile', function($q)
            {
                return $q->where('is_book_later',  1);
            });
        }

        // check tutor settings fo hourly or monthly
        if ($is_hourly == 1){
            $queryBuilder = $queryBuilder->whereHas('profile', function($q)
            {
                return $q->where('is_hourly',  1);
            });
        } else {
            $queryBuilder = $queryBuilder->whereHas('profile', function($q)
            {
                return $q->where('is_monthly',  1);
            });
        }

        // check tutor distance
//        $queryBuilder = $queryBuilder->selectRaw(" @distance_check := ((6371 * ACOS (COS (RADIANS( $latitude )) * COS(RADIANS(`users`.`latitude`)) * COS(RADIANS(`users`.`longitude`) - RADIANS($longitude)) + SIN (RADIANS($latitude)) * SIN(RADIANS(`users`.`latitude`)))) as distance");
//        $queryBuilder->havingRaw('distance' <= 12);

        $queryBuilder = $queryBuilder->where('is_online', 1);

        $result = $queryBuilder->get();
        Log::info($queryBuilder->toSql());
//dd($result->toArray());
        foreach($result as $key => $record){
            if ($record->device_token == null) {
                unset($result[$key]);
            }
            if ($record->is_active == 0) {
                unset($result[$key]);
            }
            if ($record->is_approved == 0) {
                unset($result[$key]);
            }
            // check distance
            //Google API not working currently
//            $distanceByGoogleApi = ReverseGeocode::distanceByGoogleApi($record->latitude, $record->longitude, $sessionLat, $sessionLong);
//            $distanceInKM = round($distanceByGoogleApi * 0.001);
            //distance calculate point to point like in find tutor
            $distanceInKM = ReverseGeocode::calculateDistanceInKM($record->latitude, $record->longitude, $sessionLat, $sessionLong);
            if (round($distanceInKM) > 12) {
                unset($result[$key]);
            }
            if (round($record->rating->avg('rating')) < $category_id){
                unset($result[$key]);
//                $result[] = $record->rating;
            }
            // check student given last session rating to this tutor
            $lastSession = $record->sessions()->where('student_id', Auth::user()->id)->where('status', 'ended')->orderBy('id', 'desc')->first();
            // if student given  rating < 2 than exclude this tutor
            if($lastSession && $lastSession->rating && $lastSession->rating->rating <= 2){
                unset($result[$key]);
            }
            // Check if tutor session cancelled 2 hrs limit
            $getLastSession = Session::where('tutor_id', $record->id)->where(function ($query) {
                $query->where('cancelled_from', 'tutor')
                    ->orWhere('cancelled_from', 'student');
            })->whereNull('demo_ended_at')->orderBy('id', 'desc')->first();
            if ($getLastSession) {
                $now  = Carbon::now();
                $date = Carbon::make($getLastSession->created_at);
                $hours = $now->diffInHours($date);
                $min = $now->diffInMinutes($date);
                if ($min<=120) {
                    unset($result[$key]);
                }
            }
            // Check tutor last session status
            $lastTutorSession = $record->sessions()->where('tutor_id', $record->id)->orderBy('id', 'desc')->first();
            // if tutor last session is booked or started and booked later pre and post 4 hours check than exclude this tutor
            if ($lastTutorSession) {
                // Needs to apply expired && rejected clause because these cases also didn't get request :(
                if ($session_type == 'now') {
                    if (($lastTutorSession->status == 'booked' || $lastTutorSession->status == 'started') && $lastTutorSession->book_later_at == null) {
                        unset($result[$key]);
                    }
                    if (($lastTutorSession->status == 'booked' || $lastTutorSession->status == 'started') && $lastTutorSession->book_later_at != null) {
                        $bookLaterTime = Carbon::parse($lastTutorSession->book_later_at);
                        $currentTime = Carbon::parse(Carbon::now());
                        $hours = $currentTime->diffInHours($bookLaterTime);
                        if ($hours <= intval($bookLaterRestriction['book_later_find_tutor_restriction_hours'])) {
                            unset($result[$key]);
                        }
                    }
                } else if ($session_type == 'later') {
                    if (($lastTutorSession->status == 'booked' || $lastTutorSession->status == 'started') && $lastTutorSession->book_later_at != null) {
                        $bookLaterTime = Carbon::parse($lastTutorSession->book_later_at);
                        $currentTime = Carbon::parse(Carbon::now());
                        $hours = $currentTime->diffInHours($bookLaterTime);
                        if ($hours <= intval($bookLaterRestriction['book_later_find_tutor_restriction_hours'])) {
                            unset($result[$key]);
                        }
                    }
                }
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

    public function sessions()
    {
        return $this->hasMany('App\Models\Session', 'tutor_id');
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
            $user = self::where('phone', $username)->where('role_id', $request['role_id'])->first();
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
        $uid = Str::random(32);
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

    public static function updateTutorLocation($user_id,$longitude,$latitude){
        return User::where('id','=',$user_id)->update(['longitude'=>$longitude,'latitude'=>$latitude]);
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


    public static function findByExactPhoneNumber($phone, $roleId)
    {
        return self::where('phone',$phone)->where('role_id', $roleId)->withTrashed()->first();
    }

    public static function findByExactIdCard($cnic_no, $roleId)
    {
        return self::where('cnic_no',$cnic_no)->where('role_id', $roleId)->withTrashed()->get();
    }


    public function isActive($phone, $roleId)
    {
        return self::where('phone','like','%'.$phone)->where('role_id', $roleId)->where('is_active', 1)->first();
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
            'message'   =>  'Eligible to request for reset password',
            'is_approved' => $user->is_approved,
            'user_email' => $user->email,
            'user_first_name' => $user->firstName,
            'user_last_name' => $user->lastName
        ];
    }

    public static function findTutorsRelatedClassSubject($request){
        $classId = $request['class_id'];
        $subjectId = $request['subject_id'];
        if(!$classId || !$subjectId){
            return false;
        }
        $queryBuilder = self::whereHas('teaches', function($query) use ($classId, $subjectId){
            return $query->where('program_id',$classId)->where('subject_id',$subjectId)->where('status', 1);
        });
        $queryBuilder = $queryBuilder->where('is_online', 1)->orWhere('offline_notification',1);
        $result = $queryBuilder->get();
        return $result;
    }
}
