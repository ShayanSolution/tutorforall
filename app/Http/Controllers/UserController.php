<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Profile;
use App\Models\Session;
use App\Models\User;
use App\Models\Programme;
use App\Models\Subject;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\Request;
use App\Transformers\UserTransformer;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Queue\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Log;
use Illuminate\Support\Facades\Config;


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

    public function getDashboardTotalOfPieCharts(){
        $users = User::all();
        $invoice = Invoice::all();
        $sessions = Session::all();
        return [
            'users' => count($users),
            'students' => count($users->where('role_id', 3)),
            'tutors' => count($users->where('role_id', 3)),
            'sessions' => count($sessions),
            'earning' => $invoice->sum('total_cost'),
        ];
    }

    /**
     * @SWG\get(
     *     path="/get-students",
     *     operationId="getUser",
     *     summary="Get user",
     *     description="",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *      @SWG\Parameter(
     *         description="Roleid",
     *         in="query",
     *         name="role_id",
     *         required=true,
     *         type="integer",
     *         format="int64",
     *     ),
     *
     *    @SWG\Response(
     *         response=200,
     *         description="User's List",
     *     ),
     *     @SWG\Response(
     *         response=422,
     *         description="Un Processable Entry",
     *     ),
     * )
     */
    public function getStudents(){
        return User::where('role_id', 2)->get();
    }

    public function getTutors(){
        return User::where('role_id', 3)->get();
    }

    public function getUserProfile(Request $request){
        $user_id = $request->all();
        $this->validate($request,[
            'user_id' => 'required'
        ]);
        $user_id = $user_id['user_id'];

        $user = User::
                select('users.*','programmes.name as p_name','subjects.name as s_name','genders.name as g_name','rating')
                ->leftjoin('profiles','profiles.user_id','=','users.id')
                ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
                ->leftjoin('subjects','subjects.id','=','profiles.subject_id')
                ->leftjoin('genders','genders.id','=','users.gender_id')
                ->leftjoin('ratings','ratings.user_id','=','users.id')
                ->where('users.id', $user_id)
                ->first();
        if($user){
            $profile = array(
                'Full Name'=>$user->firstName.' '.$user->lastName,
                'Email'=>$user->email,
                'Phone Number'=>$user->phone,
                'Father Name'=>$user->fatherName,
                'Qualification'=>$user->qualification,
                'Expert Class'=>$user->p_name,
                'Expert Subjects'=>$user->s_name,
                'Gender'=>$user->g_name,
                'Rating'=>$user->rating,
                'Experience'=>$user->experience,
                'Address'=>$user->address,
                'User CNIC'=>$user->cnic_no,
                'Profile_Image' => URL::to('/images').'/'.$user->profileImage,
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
        ]);

        $users = User::select('users.*')
                ->join('profiles','profiles.user_id','=','users.id')
                ->where('profiles.programme_id','=',$data['class_id'])
                ->where('profiles.subject_id','=',$data['subject_id'])
                ->where('profiles.is_home','=',$data['is_home'])
                ->where('profiles.is_group','=',$data['is_group'])
                ->where('users.role_id','=',2)
                ->get();

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
        $this->validate($request,[
            'student_id' => 'required',
            'tutor_id' => 'required',
            'subject_id' => 'required',
            'class_id' => 'required',
        ]);

        $student_id = $data['student_id'];
        $programme_id = $data['class_id'];
        $subject_id = $data['subject_id'];
        $tutors_ids = json_decode($data['tutor_id']);
        $device_token_array = array();
        $class = Programme::find($programme_id);
        $subject = Subject::find($subject_id);
        //update class and subjects for students
        Profile::where('user_id',$student_id)->update(['programme_id'=>$programme_id,'subject_id'=>$subject_id]);
        $users = User::select('users.*')
                ->select('users.*')
                /*->leftjoin('profiles','profiles.user_id','=','users.id')
                ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
                ->leftjoin('subjects','subjects.id','=','profiles.subject_id')*/
                ->where('users.role_id','=',3)
                ->where('users.id','=',$student_id)
                ->first();
        if($users){
            $user_age = Carbon::parse($users->dob)->age;
            for($j=0;$j<count($tutors_ids);$j++){
                //get tutor device token to send notification
                $device = User::where('id','=',$tutors_ids[$j])->select('device_token as token')->first();

                if(!empty($device->token)){
                    $device_token_array[] = $device->token;
                    //notification message
                    $message = PushNotification::Message(
                        $users->firstName.' '.$users->lastName.' wants a session with you',
                        array(
                            'badge' => 1,
                            'sound' => 'example.aiff',
                            'actionLocKey' => 'Action button title!',
                            'locKey' => 'localized key',
                            'locArgs' => array(
                                'localized args',
                                'localized args',
                            ),
                            'launchImage' => 'image.jpg',
                            'custom' => array('custom_data' => array(
                                'Student_Name' => $users->firstName." ".$users->lastName,
                                'Student_id' => $users->id,
                                'Class_Name' => $class->name,
                                'Subject_Name' => $subject->name,
                                'Class_id' => $programme_id,
                                'Subject_id' => $subject_id,
                                'IS_Group' => 0,
                                'Longitude' => $users->longitude,
                                'Latitude' => $users->latitude,
                                'Datetime' => Carbon::now()->toDateTimeString(),
                                'Age' => $user_age>0?$user_age:'',
                                'Profile_Image' => !empty($users->profileImage)?URL::to('/images').'/'.$users->profileImage:'',
                            ))
                        ));

                    //send student info to tutor
                    PushNotification::app('appNameIOS')
                        ->to($device->token)
                        ->send($message);

                }
            }

            return [
                'status' => 'success',
                'messages' => 'Notification sent successfully',
                //'device-tokens' => print_r($device_token_array)
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

    

    public function updateStudentProfile(Request $request){
        $this->validate($request,[
            'firstName' => 'regex:/^[a-zA-Z]+$/u|max:255',
            'lastName' => 'regex:/^[a-zA-Z]+$/u|max:255',
            'email' => 'email',
            'fatherName' => 'regex:/^[a-zA-Z]+$/u|max:255',
            'student_id' => 'Required|numeric',
            'gender_id' => 'numeric',
            'mobile' => 'numeric',
            'profileImage' => 'mimes:jpeg,jpg,png,gif|max:10000',
        ]);

        $data = $request->all();
        $firstName = isset($data['firstName'])?$data['firstName']:'';
        $lastName = isset($data['lastName'])?$data['lastName']:'';
        $email = isset($data['email'])?$data['email']:'';
        $fatherName = isset($data['fatherName'])?$data['fatherName']:'';
        $mobile = isset($data['mobile'])?$data['mobile']:'';
        $student_id = isset($data['student_id'])?$data['student_id']:'';
        $gender_id = isset($data['gender_id'])?$data['gender_id']:'';
        $address = isset($data['address'])?$data['address']:'';
        $qualification = isset($data['qualification'])?$data['qualification']:'';

        $update_array = array();
        if(!empty($firstName)){$update_array['firstName'] = $firstName;}
        if(!empty($lastName)){$update_array['lastName'] = $lastName;}
        if(!empty($email)){$update_array['email'] = $email;}
        if(!empty($fatherName)){$update_array['fatherName'] = $fatherName;}
        if(!empty($mobile)){$update_array['mobile'] = $mobile;}
        if(!empty($gender_id)){$update_array['gender_id'] = $gender_id;}
        if(!empty($address)){$update_array['address'] = $address;}
        if(!empty($qualification)){$update_array['qualification'] = $qualification;}

        $user = User::where('id','=',$student_id)->first();
        if($user){
            //upload file and update user profile image
            if(isset($data['profileImage'])){
                $file = $request->file('profileImage');
                $file_name = $file->getClientOriginalName();
                $destinationPath = base_path().'/public/images';
                $file->move($destinationPath,$file->getClientOriginalName());

                User::where('id','=',$student_id)
                    ->where('role_id','=',Config::get('user-constants.STUDENT_ROLE_ID'))
                    -> update(['profileImage'=>$file_name]);
            }else{
                $file_name = '';
            }

            //update student profile
            User::where('id','=',$student_id)
                ->where('role_id','=',Config::get('user-constants.STUDENT_ROLE_ID'))
                -> update($update_array);
            $student_profile = Profile::where('user_id','=',$student_id)->first();
            if($student_profile){
                Profile::where('user_id','=',$student_id)->update(['programme_id'=>0,'subject_id'=>0]);
            }else{
                $tutor_profile = new Profile();
                $tutor_profile->programme_id = 0;
                $tutor_profile->subject_id = 0;
                $tutor_profile->user_id = $student_id;
                $tutor_profile->save();
            }
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
        $data = $request->all();
        $firstName = isset($data['firstName'])?$data['firstName']:'';
        $lastName = isset($data['lastName'])?$data['lastName']:'';
        $email = isset($data['email'])?$data['email']:'';
        $fatherName = isset($data['fatherName'])?$data['fatherName']:'';
        $mobile = isset($data['mobile'])?$data['mobile']:'';
        $tutor_id = isset($data['tutor_id'])?$data['tutor_id']:'';
        $gender_id = isset($data['gender_id'])?$data['gender_id']:'';
        $address = isset($data['address'])?$data['address']:'';
        $cnic_no = isset($data['cnic_no'])?$data['cnic_no']:'';
        $experience = isset($data['experience'])?$data['experience']:'';
        $qualification = isset($data['qualification'])?$data['qualification']:'';
        $programme_id = isset($data['programme_id'])?$data['programme_id']:'';
        $subject_id = isset($data['subject_id'])?$data['subject_id']:'';

        $update_array = array();
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
         $user = User::where('id','=',$tutor_id)->first();
        if($user){
            //upload file
            if(isset($data['profileImage'])){
                $file = $request->file('profileImage');
                $file_name = $file->getClientOriginalName();
                $destinationPath = base_path().'/public/images';
                $file->move($destinationPath,$file_name);
                User::where('id','=',$tutor_id)
                    ->where('role_id','=',2)
                    -> update(['profileImage'=>$file_name]);

            }else{
                $file_name='';
            }
            //update student profile
            User::where('id','=',$tutor_id)
                ->where('role_id','=',2)
                -> update($update_array);
            $tutor_profile = Profile::where('user_id','=',$tutor_id)->first();
            if($tutor_profile){
                Profile::where('user_id','=',$tutor_id)->update(['programme_id'=>$programme_id,'subject_id'=>$subject_id]);
            }else{
                $tutor_profile = new Profile();
                $tutor_profile->programme_id = $programme_id;
                $tutor_profile->subject_id = $subject_id;
                $tutor_profile->user_id = $tutor_id;
                $tutor_profile->save();
            }

            return [
                'status' => 'success',
                'messages' => 'Tutor profile updated successfully!',
                'Profile_Image' => !empty($user->profileImage)?URL::to('/images').'/'.$file_name:'',

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

   
}
