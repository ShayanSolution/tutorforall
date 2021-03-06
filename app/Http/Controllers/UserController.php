<?php

namespace App\Http\Controllers;

use App\Models\CMS;
use App\Models\CreditCard;
use App\Models\Invoice;
use App\Models\Profile;
use App\Models\ProgramSubject;
use App\Models\Session;
use App\Models\User;
use App\Models\Programme;
use App\Models\Subject;
use App\Models\Rating;
//helpers
use Illuminate\Support\Str;
use TimeZoneHelper;

use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\Request;
use App\Transformers\UserTransformer;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Queue\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use App\Jobs\SendPushNotification;
use App\Models\Setting;


class UserController extends Controller
{
    /**
     * Instance of UserRepository
     *
     * @var UserRepository
     */
    private $userRepository;

    /**
     * Instanceof UserTransformer
     *
     * @var UserTransformer
     */
    private $userTransformer;

    /**
     * Constructor
     *
     * @param UserRepository $userRepository
     * @param UserTransformer $userTransformer
     */
    public function __construct(UserRepository $userRepository, UserTransformer $userTransformer)
    {
        $this->userRepository = $userRepository;
        $this->userTransformer = $userTransformer;

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $users = $this->userRepository->findBy($request->all());

        return $this->respondWithCollection($users, $this->userTransformer);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function show($id)
    {
        $user = $this->userRepository->findOne($id);

        if (!$user instanceof User) {
            return $this->sendNotFoundResponse("The user with id {$id} doesn't exist");
        }

        // Authorization
        $this->authorize('show', $user);

        return $this->respondWithItem($user, $this->userTransformer);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function store(Request $request)
    {
        // Validation
        $validatorResponse = $this->validateRequest($request, $this->storeRequestValidationRules($request));

        // Send failed response if validation fails
        if ($validatorResponse !== true) {
            return $this->sendInvalidFieldResponse($validatorResponse);
        }

        $user = $this->userRepository->save($request->all());

        if (!$user instanceof User) {
            return $this->sendCustomResponse(500, 'Error occurred on creating User');
        }

        return $this->setStatusCode(201)->respondWithItem($user, $this->userTransformer);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Validation
        $validatorResponse = $this->validateRequest($request, $this->updateRequestValidationRules($request));

        // Send failed response if validation fails
        if ($validatorResponse !== true) {
            return $this->sendInvalidFieldResponse($validatorResponse);
        }

        $user = $this->userRepository->findOne($id);

        if (!$user instanceof User) {
            return $this->sendNotFoundResponse("The user with id {$id} doesn't exist");
        }

        // Authorization
        $this->authorize('update', $user);


        $user = $this->userRepository->update($user, $request->all());

        return $this->respondWithItem($user, $this->userTransformer);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function destroy($id)
    {
        $user = $this->userRepository->findOne($id);

        if (!$user instanceof User) {
            return $this->sendNotFoundResponse("The user with id {$id} doesn't exist");
        }

        // Authorization
        $this->authorize('destroy', $user);

        $this->userRepository->delete($user);

        return response()->json(null, 204);
    }

    /**
     * Store Request Validation Rules
     *
     * @param Request $request
     * @return array
     */
    private function storeRequestValidationRules(Request $request)
    {
        $rules = [
            'email'                 => 'email|required|unique:users',
            'firstName'             => 'required|max:100',
            'middleName'            => 'max:50',
            'lastName'              => 'required|max:100',
            'username'              => 'max:50',
            'address'               => 'max:255',
            'zipCode'               => 'max:10',
            'phone'                 => 'max:20',
            'mobile'                => 'max:20',
            'city'                  => 'max:100',
            'state'                 => 'max:100',
            'country'               => 'max:100',
            'password'              => 'min:5'
        ];

        $requestUser = $request->user();

        // Only admin user can set admin role.
        if ($requestUser instanceof User && $requestUser->role === User::ADMIN_ROLE) {
            $rules['role'] = 'in:BASIC_USER,ADMIN_USER';
        } else {
            $rules['role'] = 'in:BASIC_USER';
        }

        return $rules;
    }

    /**
     * Update Request validation Rules
     *
     * @param Request $request
     * @return array
     */
    private function updateRequestValidationRules(Request $request)
    {
        $userId = $request->segment(2);
        $rules = [
            'email'                 => 'email|unique:users,email,'. $userId,
            'firstName'             => 'max:100',
            'middleName'            => 'max:50',
            'lastName'              => 'max:100',
            'username'              => 'max:50',
            'address'               => 'max:255',
            'zipCode'               => 'max:10',
            'phone'                 => 'max:20',
            'mobile'                => 'max:20',
            'city'                  => 'max:100',
            'state'                 => 'max:100',
            'country'               => 'max:100',
            'password'              => 'min:5'
        ];

        $requestUser = $request->user();

        // Only admin user can update admin role.
        if ($requestUser instanceof User && $requestUser->role === User::ADMIN_ROLE) {
            $rules['role'] = 'in:BASIC_USER,ADMIN_USER';
        } else {
            $rules['role'] = 'in:BASIC_USER';
        }

        return $rules;
    }

	private function updateCardRequestValidationRules(Request $request) {
		$rules = [
			'user_id'      => 'required',
			'is_default'   => 'required',
			'brand_name'   => 'required',
			'name_on_card' => 'required',
			'card_number'  => 'required',
			'token_id'     => 'required',
			'session_id'   => 'required',
			'agreement_id' => 'required'
		];

		$requestUser = $request->user();

		// Only admin user can update admin role.
		if ($requestUser instanceof User && $requestUser->role === User::ADMIN_ROLE) {
			$rules['role'] = 'in:BASIC_USER,ADMIN_USER';
		} else {
			$rules['role'] = 'in:BASIC_USER';
		}

		return $rules;
	}

	public function getDashboardTotalOfPieCharts() {
		$users    = User::all();
		$invoice  = Invoice::all();
		$sessions = Session::all();
		return [
			'users'    => count($users),
			'students' => count($users->where('role_id', 3)),
			'tutors'   => count($users->where('role_id', 3)),
			'sessions' => count($sessions),
			'earning'  => $invoice->sum('total_cost'),
		];
	}

    public function getStudents()
    {
       return $students =  User::getStudents();

    }

    public function getUserProfile(Request $request){
        $user_id = $request->all();
        $this->validate($request,[
            'user_id' => 'required'
        ]);
        $user_id = $user_id['user_id'];
        $user = new User();
        $user = $user->userProfile($user_id);

        if($user){
            //get tutor rating
            $user_rating = 0;
            $subjectList = '';
            if($user->role_id == 2){
                $rating_sessions = Session::where('tutor_id', $user_id)->where('hourly_rate', '!=', 0)->pluck('id');
                $rating = Rating::whereIn('session_id', $rating_sessions)->get();
                $user_rating = $rating->avg('rating');
                $subject = new ProgramSubject;
                $subjectList = $subject->getSubjectsDetail($user_id);
                //get Min Max slider defaults values from settings
                // Need to refactor
                $sliderDefaultValue = Setting::where('group_name', 'tutor-setting-slider')->pluck('value', 'slug');
            }

            $profile = array(
                'Full Name'=>$user->firstName.' '.$user->lastName,
                'Email'=>$user->email,
                'First Name'=>$user->firstName,
                'Last Name'=>$user->lastName,
                'Phone Number'=>$user->phone,
                'Mobile Number'=>$user->mobile,
                'Father Name'=>$user->fatherName,
                'Qualification'=>$user->qualification,
                'Expert Class'=>$user->p_name,
                'Expert Subjects'=>$user->s_name,
                'Gender'=>$user->g_name,
                'Experience'=>$user->experience,
                'Address'=>$user->address,
                'User CNIC'=>$user->cnic_no,
                'Is Mentor'=>$user->is_mentor,
                'Is Deserving'=>$user->is_deserving,
                'One On One'=>$user->one_on_one,
                'Call Tutor'=>$user->call_tutor,
                'Call Student'=>$user->call_student,
                'Is Home'=>$user->is_home,
                'Is Group'=>$user->is_group,
                'Meeting Type Id'=>$user->meeting_type_id,
                'Programme Id'=>$user->programme_id,
                'Subject Id'=>$user->subject_id,
                'Subjects List'=>$subjectList,
                'Rating' => number_format((float)$user_rating, 1, '.', ''),
                'Profile_Image' => $user->profileImage,
                'teach_to' => $user->teach_to,
                'min_thumb_slider' => $user->min_slider_value,
                'max_thumb_slider' => $user->max_slider_value,
                'min_slider' => isset($sliderDefaultValue['tutor-setting-slider-min-value']) ? $sliderDefaultValue['tutor-setting-slider-min-value'] : '',
                'max_slider' => isset($sliderDefaultValue['tutor-setting-slider-max-value']) ? $sliderDefaultValue['tutor-setting-slider-max-value'] : '',
                'offline_notification' => $user->offline_notification,
                'is_book_now' => $user->is_book_now,
                'is_book_later' => $user->is_book_later,
                'book_later_longitude' => $user->book_later_longitude,
                'book_later_latitude' => $user->book_later_latitude,
                'book_later_address' => $user->book_later_address,
                'book_later_current_location' => $user->book_later_current_location,
                'is_hourly' => $user->is_hourly,
                'is_monthly' => $user->is_monthly,
            );
            return $profile;
        }
        else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find profile'
                ], 422
            );
        }

    }

    public function postTutorProfile(Request $request){
        $data = $request->all();
        $this->validate($request,[
            'subject_id' => 'required',
            'class_id' => 'required',
            'is_home' => 'required',
            'is_group' => 'required',
            'call_student' => 'required',
            'one_on_one' => 'required'
        ]);
        $userProfile = Profile::where('user_id', Auth::user()->id)->first();
        $data['is_deserving'] = $userProfile->is_deserving;
        $user = new User();
        $users = $user->getTutorProfile($data);


        if($users){
            return response()->json(['data' => $users]);
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find tutor'
                ], 422
            );
        }

    }

    public function tutorSessionInfo(Request $request){
        $data = $request->all();

        $student_id = $data['student_id'];
        $programme_id = $data['class_id'];
        $subject_id = $data['subject_id'];
        $tutors_ids = json_decode($data['tutor_id']);

        Profile::where('user_id',$student_id)->update(['programme_id'=>$programme_id,'subject_id'=>$subject_id]);
        $student = User::select('users.*')
                    ->select('users.*','profiles.is_group')
                    ->leftjoin('profiles','profiles.user_id','=','users.id')
                    ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
                    ->leftjoin('subjects','subjects.id','=','profiles.subject_id')
                    ->where('users.role_id','=',3)
                    ->where('users.id','=',$student_id)
                    ->first();

        if($student){

            //send student info to tutor
            $job = new SendPushNotification($data, $tutors_ids, $student);
            dispatch($job);
            return [
                'status' => 'success',
                'messages' => 'Notification sent successfully',
                //'device-tokens' => print_r($device_token_array)
            ];

        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find student'
                ], 422
            );
        }

    }



    public function updateStudentProfile(Request $request){
        $this->validate($request,[
            'firstName' => 'regex:/^[a-zA-Z0-9 ]*$/u|max:255',
            'lastName' => 'regex:/^[a-zA-Z0-9 ]*$/u|max:255',
            'email' => 'email',
            'student_id' => 'Required|numeric',
            'gender_id' => 'numeric',
            'mobile' => 'numeric',
            'profileImage' => 'mimes:jpeg,jpg,png,gif|max:10000',
        ]);
        $data = $request->all();
        $student_id = isset($data['student_id'])?$data['student_id']:'';
        $update_array = $this->getUpdatedValues($data);
        $user = User::where('id','=',$student_id)->first();
        $role_id = Config::get('user-constants.STUDENT_ROLE_ID');
        if($user){
            //upload file and update user profile image
            if(isset($data['profileImage'])){
                $file = $request->file('profileImage');
                $strRandom = Str::random(12);
                $file_name = $strRandom.'.'.$file->getClientOriginalExtension();
                $destinationPath = base_path().'/public/images';
                $file->move($destinationPath,$file_name);
                User::updateProfileImage($student_id,$file_name,$role_id);
            }
            //update student profile
            User::updateUserProfile($student_id,$update_array,$role_id);
            //get student profile image
            $student_info = User::where('id','=',$student_id)->first();
            return [
                'status' => 'success',
                'messages' => 'Student profile updated successfully!',
                'Profile_Image' => !empty($student_info->profileImage)?URL::to('/images').'/'.$student_info->profileImage:'',
            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update user'
                ], 422
            );
        }
    }


    public function updateTutorProfile(Request $request){
        $this->validate($request,[
            'firstName' => 'regex:/^[a-zA-Z0-9 ]*$/u|max:255',
            'lastName' => 'regex:/^[a-zA-Z0-9 ]*$/u|max:255',
            'email' => 'email',
            'tutor_id' => 'Required|numeric',
            'gender_id' => 'numeric',
            'mobile' => 'numeric',
            'profileImage' => 'mimes:jpeg,jpg,png,gif|max:10000',
        ]);
        $data = $request->all();
        $tutor_id = isset($data['tutor_id'])?$data['tutor_id']:'';
        $role_id = Config::get('user-constants.TUTOR_ROLE_ID');
        //build array to be updated.
        $update_array = $this->getUpdatedValues($data);
        $user = User::where('id','=',$tutor_id)->first();
        if($user){
            //upload file
            if(isset($data['profileImage'])){
                $file = $request->file('profileImage');
                $strRandom = Str::random(12);
                $file_name = $strRandom.'.'.$file->getClientOriginalExtension();
                $destinationPath = base_path().'/public/images';
                $file->move($destinationPath,$file_name);
                User::updateProfileImage($tutor_id,$file_name,$role_id);
            }
            //update tutor profile
            User::updateUserProfile($tutor_id,$update_array,$role_id);
//            $tutor_profile = Profile::where('user_id','=',$tutor_id)->first();
//            if($tutor_profile){
//                $update_profile_values = $this->getProfileUpdatedValues($data);
//                $update_user_values = $this->getUserUpdatedValues($data);
//                User::updateUserValues($data['tutor_id'],$update_user_values);
//                Profile::updateUserProfile($data['tutor_id'],$update_profile_values);
//            }else{
//                $update_profile_values = $this->getProfileUpdatedValues($data);
//                $update_user_values = $this->getUserUpdatedValues($data);
//                User::updateUserValues($data['tutor_id'],$update_user_values);
//                Profile::createUserProfile($data['tutor_id'],$update_profile_values);
//            }
            //get student profile image
            $tutor_info = User::where('id','=',$tutor_id)->first();
            return [
                'status' => 'success',
                'messages' => 'Tutor profile updated successfully!',
                'Profile_Image' => !empty($user->profileImage)?URL::to('/images').'/'.$tutor_info->profileImage:'',

            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update user'
                ], 422
            );
        }
    }

    public function getUpdatedValues($data){
        $update_array = array();
        $firstName = isset($data['firstName'])?$data['firstName']:'';
        $lastName = isset($data['lastName'])?$data['lastName']:'';
        $email = isset($data['email'])?$data['email']:'';
        $fatherName = isset($data['fatherName'])?$data['fatherName']:'';
        $mobile = isset($data['mobile'])?$data['mobile']:'';
        $gender_id = isset($data['gender_id'])?$data['gender_id']:'';
        $address = isset($data['address'])?$data['address']:'';
        $cnic_no = isset($data['cnic_no'])?$data['cnic_no']:'';
        $experience = isset($data['experience'])?$data['experience']:'';
        $qualification = isset($data['qualification'])?$data['qualification']:'';
        $dob = isset($data['dob'])?$data['dob']:'';


        if(!empty($firstName)){$update_array['firstName'] = $firstName;}
        if(!empty($lastName)){$update_array['lastName'] = $lastName;}
        if(!empty($email)){$update_array['email'] = $email;}
        if(!empty($fatherName)){$update_array['fatherName'] = $fatherName;}
        if(!empty($mobile)){$update_array['mobile'] = $mobile;}
        if(!empty($gender_id)){$update_array['gender_id'] = $gender_id;}
        if(!empty($address)){$update_array['address'] = $address;}
        if(!empty($cnic_no)){$update_array['cnic_no'] = $cnic_no;}
        if(!empty($experience)){$update_array['experience'] = $experience;}
        if(!empty($qualification)){$update_array['qualification'] = $qualification;}
        if(!empty($dob)){$update_array['dob'] = $dob;}

        return $update_array;
    }


    public function getUserUpdatedValues($data){
        $update_array = array();

        $firstName = isset($data['firstName'])?$data['firstName']:'';
        $lastName = isset($data['lastName'])?$data['lastName']:'';
        $email = isset($data['email'])?$data['email']:'';
        $fatherName = isset($data['fatherName'])?$data['fatherName']:'';
        $gender_id = isset($data['gender_id'])?$data['gender_id']:'';
        $mobile = isset($data['mobile'])?$data['mobile']:'';
        $cnic_no = isset($data['cnic_no'])?$data['cnic_no']:'';
        $address = isset($data['address'])?$data['address']:'';
        $experience = isset($data['experience'])?$data['experience']:'';
        $qualification = isset($data['qualification'])?$data['qualification']:'';
        $dob = isset($data['dob'])?$data['dob']:'';

        if(!empty($firstName)){$update_array['firstName'] = $firstName;}
        if(!empty($lastName)){$update_array['lastName'] = $lastName;}
        if(!empty($email)){$update_array['email'] = $email;}
        if(!empty($fatherName)){$update_array['fatherName'] = $fatherName;}
        if(!empty($mobile)){$update_array['mobile'] = $mobile;}
        if(!empty($cnic_no)){$update_array['cnic_no'] = $cnic_no;}
        if(!empty($address)){$update_array['address'] = $address;}
        if(!empty($dob)){$update_array['dob'] = $dob;}
        if(!empty($experience)){$update_array['experience'] = $experience;}
        if(!empty($gender_id)){$update_array['gender_id'] = $gender_id;}
        if(!empty($qualification)){$update_array['qualification'] = $qualification;}

        return $update_array;

    }

    public function getProfileUpdatedValues($data){
        $update_array = array();

//        $is_home = isset($data['is_home'])?$data['is_home']:'';
//        $is_group = isset($data['is_group'])?$data['is_group']:'';
//        $is_mentor = isset($data['is_mentor'])?$data['is_mentor']:'';
        $programme_id = isset($data['programme_id'])?$data['programme_id']:'';
        $subject_id = isset($data['subject_id'])?$data['subject_id']:'';
//        $tutor_id = isset($data['tutor_id'])?$data['tutor_id']:'';
//        $student_id = isset($data['student_id'])?$data['student_id']:'';
//        $one_on_one = isset($data['one_on_one'])?$data['one_on_one']:'';
//        $call_tutor = isset($data['call_tutor'])?$data['call_tutor']:'';
//        $call_student = isset($data['call_student'])?$data['call_student']:'';

        if(!empty($subject_id)){$update_array['subject_id'] = $subject_id;}
        if(!empty($programme_id)){$update_array['programme_id'] = $programme_id;}
//        if(!empty($is_home) || ($is_home == 0) ){$update_array['is_home'] = $is_home;}
//        if(!empty($is_group) || ($is_group == 0)){$update_array['is_group'] = $is_group;}
//        if(!empty($is_mentor) || ($is_mentor == 0)){$update_array['is_mentor'] = $is_mentor;}
//        //if(!empty($tutor_id)){$update_array['user_id'] = $tutor_id;}
//        if(!empty($student_id)){$update_array['user_id'] = $student_id;}
//        if(!empty($one_on_one) || ($one_on_one == 0) ){$update_array['one_on_one'] = $one_on_one;}
//        if(!empty($call_tutor) || ($call_tutor == 0)){$update_array['call_tutor'] = $call_tutor;}
//        if(!empty($call_student) || ($call_student == 0)){$update_array['call_student'] = $call_student;}
        return $update_array;

    }

    public function updateTutorProfileSetting(Request $request)
    {
        $this->validate($request, [
            'tutor_id' => 'Required|numeric',
            'is_home' => 'numeric',
            'is_group' => 'numeric',
            'is_mentor' => 'numeric',
            'teach_to' => 'numeric',
            'call_student' => 'numeric',
            'min_slider_value' => 'numeric',
            'max_slider_value' => 'numeric',
            'offline_notification' => 'required',
            'is_book_later' => 'required',
            'is_book_now' => 'required',
            'book_later_current_location' => 'required',
            'is_hourly' => 'required',
            'is_monthly' => 'required',
        ]);
        $data = $request->all();
        $profile = new Profile();
        $update_profile = $profile->updateUserProfile(
            $data['tutor_id'],
            $request->only([
                'is_home',
                'is_group',
                'is_mentor',
                'teach_to',
                'call_student',
                'one_on_one',
                'min_slider_value',
                'max_slider_value',
                'book_later_longitude',
                'book_later_latitude',
                'is_book_later',
                'is_book_now',
                'book_later_address',
                'book_later_current_location',
                'is_hourly',
                'is_monthly'
                ])
        );
        User::where('id','=',$request->tutor_id)->update([
            'offline_notification'=>$request->offline_notification,
            'latitude' => $request->book_now_latitude,
            'longitude' => $request->book_now_longitude
        ]);
        if($update_profile){
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Settings updated successfully.'
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Error in profile setting updates.'
                ], 422
            );
        }
    }

    public function getTutors(){
        return User::getTutors();
    }

    public function updateUserActiveStatus($id){
        //update student deserving status
        User::updateUserActiveStatus($id);
        $students =  User::getStudents();
        return $students;
    }

    //Remove user account
    public function removeUser(){
        $user = User::find(Auth::user()->id);
        if($user){
            if($user->delete()){

                $user->update(['device_token'=>'']);//Remove user device token
                Auth::user()->AauthAcessToken()->delete();//Delete user access token

                return response()->json(
                    [
                        'status' => 'success',
                        'message' => 'User account deleted successfully'
                    ], 200
                );
            }else{
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Unable to delete user account'
                    ], 422
                );
            }
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find user account'
                ], 422
            );
        }

    }

    public function userProfile($id){
        $user = new User();
        return $user->userProfile($id);

    }


    public function updateUserDeviceToken(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'device_token' => 'required',
        ]);
        $response = User::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }
        $inputs = $request->all();
        if(isset($inputs['device_type'])){
            $updateUser = new User();
            $updateUser->updateWhere(['id'=>$inputs['user_id']], ['device_type'=>$inputs['device_type']]);
        }

        try{
            $token = User::updateToken($request);
            if($token){
                return response()->json(
                    [
                        'status'    =>  'success',
                        'message'   =>  'Device token updated successfully',
                    ], 200
                );
            }else{
                return response()->json(
                    [
                        'status' => 'success',
                        'message' => 'device token already updated.'
                    ], 200
                );
            }
        }catch (Exception $e){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update device token.'
                ], 422
            );
        }




    }


    public function getUser(Request $request){

        $inputs = $request->all();
        $user = Auth::user();

        if(isset($inputs['device_type'])){
            $updateUser = new User();
            $updateUser->updateWhere(['id'=>$user->id], ['device_type'=>$inputs['device_type']]);
        }

        if($user){
            return response()->json(
                [
                    'status' => 'success',
                    'user' => $user,
                ], 200
            );
        }else{

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find user'
                ], 422
            );
        }

	}

    public function logout()
    {
        if (Auth::check()) {
            $user_id =  Auth::user()->id;
            User::where('id', $user_id)->update([
                'device_token'=> '',
                'is_online'=> 0,
            ]);
            Auth::user()->AauthAcessToken()->delete();

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Successfully logout',
                ], 200
            );
        }
    }

	public function online(Request $request) {
		if (Auth::check()) {
			$user_id = Auth::user()->id;
			$user = User::find($user_id);
			if($user->is_blocked != 1)
			{
				User::where('id', $user_id)->update([
					'is_online'  => $request->is_online,
					'last_login' => Carbon::now()
				]);
			}

			if ($request->is_online == 1) {
				if ($user->is_blocked == 1) {
					return response()->json(
						[
							'status'  => 'error',
							'message' => 'You are blocked due to unpaid invoice.',
						],
						200
					);
				} else {
					return response()->json(
						[
							'status'  => 'success',
							'message' => 'Successfully online',
						],
						200
					);
				}
			} else {

				return response()->json(
					[
						'status'  => 'success',
						'message' => 'Successfully Offline'
					],
					200
				);
			}
		} else {
			return response()->json(
				[
					'status'  => 'error',
					'message' => 'Not Authorized'
				],
				422
			);
		}
	}

    public function scriptNumber(Request $request){
       return view('partials.number_list');
    }

    public function scriptNumberSearch(Request $request){
        $users = User::where('phone', $request->number)->get();
        return (string)(view("partials.number_item", compact('users')));
    }

    public function scriptNumberDelete(Request $request){
        $user = User::find($request->id);
        $roleName = strtolower($user->role->name);
        Session::where($roleName.'_id', $user->id)->delete();
        Profile::where('user_id', $user->id)->delete();
        $user->forceDelete();
        return response()->json(['status'=>'success'], 200);
    }

    public function offlineNotification(Request $request)
    {
        if (Auth::check()) {
            $user_id =  Auth::user()->id;
            User::where('id', $user_id)->update(['offline_notification'=>$request->offline_notification]);

            if($request->offline_notification == 1){
                return response()->json(
                    [
                        'status' => 'success',
                        'message' => 'You will get notifications when you are offline',
                    ], 200
                );
            }else{

				return response()->json(
					[
						'status'  => 'success',
						'message' => 'You will not get notifications when you are offline'
					],
					200
				);
			}
		} else {
			return response()->json(
				[
					'status'  => 'error',
					'message' => 'Not Authorized'
				],
				422
			);
		}
	}

	public function addCard(Request $request) {
		// Validation
		$validatorResponse = $this->validateRequest($request, $this->updateCardRequestValidationRules($request));

		// Send failed response if validation fails
		if ($validatorResponse !== true) {
			return $this->sendInvalidFieldResponse($validatorResponse);
		}

		$card =  CreditCard::create($request->all());

		if ($card) {
			return response()->json(
				[
					'status'  => 'success',
					'message' => 'Card added successfully!',
				],
				200
			);
		} else {
			return response()->json(
				[
					'status'  => 'error',
					'message' => 'Card not added successfully!',
				],
				200
			);
		}
	}
	public function getCard(Request $request)
	{
		if(Auth::user()->id)
		{
			$cards = CreditCard::whereUserId(Auth::user()->id)->get();
			return response()->json(
				[
					'status'  => 'success',
					'message' => 'Card getting successfully!',
					'data' => $cards
				],
				200
			);
		}
		else {
			return response()->json(
				[
					'status'  => 'error',
					'message' => 'Error getting cards!',
					'data' => []
				],
				200
			);
		}
	}
	public function deleteCard(Request $request)
	{
		$this->validate($request,[
			'id' => 'required'
		]);
		if(Auth::user()->id)
		{
			$cards = CreditCard::find($request->id)->delete();
			if($cards)
			{
				return response()->json(
					[
						'status'  => 'success',
						'message' => 'Card deleted successfully!'
					],
					200
				);
			}
			else{
				return response()->json(
					[
						'status'  => 'error',
						'message' => 'Card not deleted successfully!'
					],
					200
				);
			}
		}
		else {
			return response()->json(
				[
					'status'  => 'error',
					'message' => 'Error getting cards!',
					'data' => []
				],
				200
			);
		}
	}

	public function acceptTermAndCondition(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->update([
                'term_and_condition' => 1
            ]);
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Terms and Conditions accept successfully!'
                ],
                200
            );
        } else {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Not Authorized'
                ],
                422
            );
        }
    }

    public function getTermAndCondition(Request $request) {
        $user = Auth::user();
        if ($user) {
            $userRoleId = $user->role_id;
            $content = CMS::where('user_role_id', $userRoleId)->first();
            return response()->json(
                [
                    'status'  => 'success',
                    'message' => 'Terms and Conditions are',
                    'data' => $content
                ],
                200
            );
        } else {
            return response()->json(
                [
                    'status'  => 'error',
                    'message' => 'Not Authorized'
                ],
                422
            );
        }
    }

}
