<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Profile;
use App\Models\Session;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\Request;
use App\Transformers\UserTransformer;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Queue\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;


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
                'Qualification'=>$user->p_name,
                'Expert Subjects'=>$user->s_name,
                'Gender'=>$user->g_name,
                'Rating'=>$user->rating,
                'Experience'=>$user->experience,
                'Profile Image'=>base_path().'/images/'.$user->profileImage,
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
        //update class and subjects for students
        Profile::where('user_id',$student_id)->update(['programme_id'=>$programme_id,'subject_id'=>$subject_id]);
        $users = User::select('users.*')
                ->select('users.*','programmes.name as p_name','subjects.name as s_name','programmes.id as p_id','subjects.id as s_id','profiles.is_group')
                ->leftjoin('profiles','profiles.user_id','=','users.id')
                ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
                ->leftjoin('subjects','subjects.id','=','profiles.subject_id')
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
                                'Class_Name' => $users->p_name,
                                'Subject_Name' => $users->s_name,
                                'Class_id' => $users->p_id,
                                'Subject_id' => $users->s_id,
                                'IS_Group' => $users->is_group,
                                'Longitude' => $users->longitude,
                                'Latitude' => $users->longitude,
                                'Datetime' => Carbon::now()->toDateTimeString(),
                                'Age' => $user_age>0?$user_age:''
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

    public function bookedTutor(Request $request){
        $data = $request->all();
        $this->validate($request,[
            'student_id' => 'required',
            'tutor_id' => 'required',
            'subject_id' => 'required',
            'class_id' => 'required',
        ]);
        $tutor_id = $data['tutor_id'];
        $student_id = $data['student_id'];
        $programme_id = $data['class_id'];
        $subject_id = $data['subject_id'];
        //get tutor profile
        $users = User::select('users.*')
                ->select('users.*','programmes.name as p_name','subjects.name as s_name'
                    ,'programmes.id as p_id','subjects.id as s_id','profiles.is_group',
                    'profiles.is_home as t_is_home')
                ->leftjoin('profiles','profiles.user_id','=','users.id')
                ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
                ->leftjoin('subjects','subjects.id','=','profiles.subject_id')
                ->where('users.role_id','=',2)
                ->where('users.id','=',$tutor_id)
                ->first();
        $student = User::where('id','=',$student_id)->first();
        $session = Session::
                            where('student_id','=',$student_id)
                            ->where('programme_id','=',$programme_id)
                            ->where('subject_id','=',$subject_id)
                            ->where('status','=','booked')
                            ->first();
        
        //if student session already exists.
        if($session){
            return [
                'status' => 'fail',
                'messages' => 'Session already booked!'
            ];
        }else{
            $session = new Session;
            $session->tutor_id = $tutor_id;
            $session->student_id = $student_id;
            $session->programme_id = $programme_id;
            $session->subject_id = $subject_id;
            $session->status = 'booked';
            $session->subscription_id = 3;
            $session->meeting_type_id = 1;
            $session->save();

            //get tutor device token
            $device = User::where('id','=',$student_id)->select('device_token as token')->first();

            $message = PushNotification::Message(
                $users->firstName.' '.$users->lastName.' accepted your request',
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
                        'Tutor_Name' => $users->firstName." ".$users->lastName,
                        'Class_Name' => $users->p_name,
                        'Subject_Name' => $users->s_name,
                        'Class_id' => $users->p_id,
                        'Subject_id' => $users->s_id,
                        'is_group' => $users->is_group,
                        'tutor_is_home' => $users->t_is_home,
                        'tutor_lat' => $users->latitude,
                        'tutor_lat' => $users->latitude,
                        'tutor_long' => $users->longitude,
                        'student_lat' => $student->latitude,
                        'student_long' => $student->longitude,
                    ))
                ));
            //send student info to student
//            Queue::push(PushNotification::app('appStudentIOS')
//                ->to($device->token)
//                ->send($message));
            PushNotification::app('appStudentIOS')
                            ->to($device->token)
                            ->send($message);

        }

        if($session){
            return [
                'status' => 'success',
                'messages' => 'Session Created successfully'
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
        $data = $request->all();
        $this->validate($request,[
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'email|required',
            'fatherName' => 'required',
            'gender_id' => 'required',
            'mobile' => 'required',
            'student_id' => 'required',
            'middleName' => 'required',
            'address' => 'required',
            'profileImage' => 'mimes:jpeg,bmp,png|max:500000',
        ]);
        $firstName = $data['firstName'];
        $lastName = $data['lastName'];
        $email = $data['email'];
        $fatherName = $data['fatherName'];
        $mobile = $data['mobile'];
        $student_id = $data['student_id'];
        $middleName = $data['middleName'];
        $gender_id = $data['gender_id'];
        $address = $data['address'];

        $user = User::where('id','=',$student_id)->first();
        if($user){
            //upload file
            if(isset($data['profileImage'])){
                $file = $request->file('profileImage');
                $file_name = $file->getClientOriginalName();
                $destinationPath = base_path().'/images';
                $file->move($destinationPath,$file->getClientOriginalName());
            }
            //strtolower($data['gender_id']) == 'male'?$gender_id= 1:$gender_id= 2;

            //update student profile
            User::where('id','=',$student_id)
                  ->where('role_id','=',3)
                  -> update(['firstName'=>$firstName,'lastName'=>$lastName,'fatherName'=>$fatherName,'email'=>$email,
                  'gender_id'=>$gender_id,'mobile'=>$mobile,'middleName'=>$middleName,'profileImage'=>$file_name,'address'=>$address]);
            return [
                'status' => 'success',
                'messages' => 'Student profile updated successfully!',
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
        $this->validate($request,[
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'email|required',
            'fatherName' => 'required',
            'gender_id' => 'required',
            'mobile' => 'required',
            'tutor_id' => 'required',
            'cnic_no' => 'required',
            'experience' => 'required',
            'qualification' => 'required',
            'programme_id' => 'required',
            'subject_id' => 'required',
            'address' => 'required',
            'profileImage' => 'mimes:jpeg,bmp,png|max:500000',
        ]);

        $firstName = $data['firstName'];
        $lastName = $data['lastName'];
        $email = $data['email'];
        $fatherName = $data['fatherName'];
        $mobile = $data['mobile'];
        $tutor_id = $data['tutor_id'];
        $gender_id = $data['gender_id'];
        $address = $data['address'];
        $cnic_no = $data['cnic_no'];
        $experience = $data['experience'];
        $qualification = $data['qualification'];
        $programme_id = $data['programme_id'];
        $subject_id = $data['subject_id'];

        $user = User::where('id','=',$tutor_id)->first();
        if($user){
            //upload file
            if(isset($data['profileImage'])){
                $file = $request->file('profileImage');
                $file_name = $file->getClientOriginalName();
                $destinationPath = base_path().'/images';
                $file->move($destinationPath,$file->getClientOriginalName());
            }
            //update student profile
            User::where('id','=',$tutor_id)
                ->where('role_id','=',2)
                -> update(['firstName'=>$firstName,'lastName'=>$lastName,'fatherName'=>$fatherName,'email'=>$email,
                    'gender_id'=>$gender_id,'mobile'=>$mobile,'profileImage'=>$file_name,
                    'address'=>$address,'cnic_no'=>$cnic_no,'experience'=>$experience,'qualification'=>$qualification]);
            $tutor_profile = Profile::where('user_id','=',$tutor_id)->first();
            if($tutor_profile){
                Profile::where('user_id','=',$tutor_id)->update(['programme_id'=>$programme_id,'subject_id'=>$subject_id]);
            }else{
                $tutor_profile = new Profile();
                $tutor_profile->programme_id = $programme_id;
                $tutor_profile->subject_id = $subject_id;
                $tutor_profile->save();
            }

            return [
                'status' => 'success',
                'messages' => 'Tutor profile updated successfully!',
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
