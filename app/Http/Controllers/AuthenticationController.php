<?php

namespace App\Http\Controllers;

use App\Helpers\TwilioHelper;
use App\Models\PhoneCode;
use App\Models\User;
use Carbon\Carbon;
use App\Helpers\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Profile;
use App\Location;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use DB;
use Twilio\Rest\Client;

class AuthenticationController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/get-phone-code",
     *     summary="Get a phone verification pin code",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         description="Phone number to generate code",
     *         in="query",
     *         name="phone",
     *         required=true,
     *         type="integer",
     *         format="int64",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *     @SWG\Response(
     *         response="422",
     *         description="Invalid phone value",
     *     )
     * )
     */
    public function getPhoneVerificationCode(Request $request){
        $this->validate($request,[
            'phone' => 'required|digits_between:11,20'
        ]);
        $phone = $request->phone;
        $user = new User;
        $phoneExist = $user->findByExactPhoneNumber($phone);

        if ($phoneExist && $phoneExist->deleted_at){
            $phoneExist->restore();
        }

        $phoneCode = PhoneCode::getPhoneNumber($phone);

        if ($phoneCode) {

            return JsonResponse::generateResponse([
                    'status' => 'error',
                    'message' => 'Phone number already verified.'
                ],500);

        } else {
            return $this->generateRandomCodeAndSendThroughTwilio($phone, $phoneCode);
        }
    }

    public function generateRandomCodeAndSendThroughTwilio($phone, $phoneCode = null){
        $code = $this->generateRandomCode();
        $toNumber = $this->sanitizePhoneNumber($phone);
//                // Use the client to do fun stuff like send text messages!
        $response = TwilioHelper::sendCodeSms($toNumber, $code);

        if ($response){

            if ($phoneCode && $phoneCode->verified == 0){
                $phoneCode->code = $code;
                $phoneCode->save();
            }elseif ($phoneCode && $phoneCode->verified == 1){
                $phoneCode->code = $code;
                $phoneCode->verified = 0;
                $phoneCode->save();
            } else{
                $phoneCode = new PhoneCode();
                $phoneCode->phone  = $toNumber;
                $phoneCode->code = $code;
                $phoneCode->save();
            }

            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'message' => 'Phone code created successfully'
                ],200
            );

        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to send SMS.'
                ],500
            );
        }
    }


    public function generateRandomCode($digits = 4){
        return rand(pow(10, $digits-1), pow(10, $digits)-1);
    }
    public function sanitizePhoneNumber($number)
    {
        if (substr($number,0,2) == 92){
            return '+'.$number;
        }elseif((substr($number,0,1) == '0') && substr($number,0,3) == '092'){
            if ((substr($number,0,3) == '092')){
                $number = substr_replace($number, '+', 0, 1);
                return $number;
            }else{
                $number = substr_replace($number, '+92', 0, 1);
                return $number;
            }
        }elseif((substr($number,0,1) == '0') && substr($number,0,2) != 92){
            $number = substr_replace($number, '+92', 0, 1);
            return $number;
        }
    }

    /**
     * @SWG\Post(
     *     path="/verify-phone-code",
     *     summary="Save phone verification code",
     *     description="",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *      @SWG\Parameter(
     *         description="Phone number to generate code",
     *         in="query",
     *         name="phone",
     *         required=true,
     *         type="integer",
     *         format="int64",
     *     ),
     *
     *     @SWG\Parameter(
     *         description="Code number",
     *         in="query",
     *         name="code",
     *         required=true,
     *         type="integer",
     *         format="int64",
     *     ),
     *
     *    @SWG\Response(
     *         response=200,
     *         description="Phone code has been verified",
     *     ),
     *     @SWG\Response(
     *         response=422,
     *         description="Invalid or expired phone code",
     *     ),
     * )
     */
    public function postPhoneVerificationCode(Request $request){
        $this->validate($request,[
            'phone' => 'required_without:|digits_between:11,20',
            'code' => 'required_without:|digits:4',
        ]);
        $request = $request->all();
        $phone_code = new PhoneCode();
        $phone_verified = $phone_code->verifyPhoneCode($request);
        if ($phone_verified){
            return JsonResponse::generateResponse(
                [
                    'status'    =>  'success',
                    'message'   =>  'Phone code verified successfully!'
                ],200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status'    => 'error',
                    'message'   => 'Unable to verify phone number'
                ],500
            );
        }
    }
    /**
     * @SWG\post(
     *     path="/register-student",
     *     operationId="addPet",
     *     summary="Register student",
     *     description="",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *      @SWG\Parameter(
     *         description="Email address",
     *         in="query",
     *         name="email",
     *         required=true,
     *         type="string",
     *     ),
     *
     *     @SWG\Parameter(
     *         description="Phone number",
     *         in="query",
     *         name="phone",
     *         required=true,
     *         type="string",
     *     ),
     *
     *     @SWG\Parameter(
     *         description="Phone code",
     *         in="query",
     *         name="code",
     *         required=true,
     *         type="integer",
     *     ),
     *
     *    @SWG\Response(
     *         response=200,
     *         description="Phone code has been verified",
     *     ),
     *     @SWG\Response(
     *         response=422,
     *         description="Invalid or expired phone code",
     *     ),
     * )
     */
    public function postRegister(Request $request){

        $validation_array = [
            'email' => 'required|email|unique:users',
            'phone' => 'required|digits_between:11,20',
            'password' => 'required|min:6|confirmed',
            'device_token' => 'required',
            'role_id'       =>  'required'
        ];

        if($request->role_id == 2)
        {
            $validation_array['firstName']  = 'required';
            $validation_array['lastName']   = 'required';
            $validation_array['is_mentor']   = 'required';
        }

        $this->validate($request, $validation_array);

        $email = $request->email;
        $phone = $request->phone;
        $password = $request->password;
        $role_id = $request->role_id;
        $device_token = $request->device_token;
        $genderId = 0;
        if ($request->has('gender_id')) {
            $genderId = $request->gender_id;
        }
        $confirmation_code = str_random(30);
        try {

            $userDataArray = [
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($password),
                'uid' => md5(microtime()),
                'role_id' => $role_id,
                'is_active' => 1,
                'device_token' => $device_token,
                'confirmation_code' => $confirmation_code,
                'gender_id' => $genderId
            ];

            $isMentor = 0;

            if($role_id == 2){
                $userDataArray['firstName'] = $request->firstName;
                $userDataArray['lastName']  = $request->lastName;
                $isMentor                   = $request->is_mentor;
            }


            $user = User::updateOrCreate(['phone' => $phone], $userDataArray)->id;

            if ($user) {

                Profile::registerUserProfile($user, $isMentor);

                $user = User::where('id', $user)->first();

                return [
                    'status'    => 'success',
                    'pswrd'     => $password,
                    'user_id'   => $user,
                    'messages'  => 'User has been created'
                ];
            } else {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Unable to create User'
                    ], 422
                );
            }
        }catch (\Exception $e){
            $errorCode = $e->errorInfo[1];
            if($errorCode == 1062){
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Email already exist.'
                    ], 422
                );
            }
        }
    }



    public function postUpdateLocation(Request $request){

        $this->validate($request,[
            'longitude' => 'required',
            'latitude' => 'required',
            'user_id' => 'required',
        ]);
        $data = $request->all();
        $longitude = $data['longitude'];
        $latitude = $data['latitude'];
        $user_id = $data['user_id'];
        $address = '';
        if(isset($data['address']) && $data['address'] != ''){
            $address = $data['address'];
        }

        $user = User::where('id', '=', $user_id)->first();
        if($user){
            $location = User::updateTutorLocation($user_id,$longitude,$latitude, $address);
        }else{

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update location'
                ], 422
            );
        }
        if($location){
            return [
                'status' => 'success',
                'messages' => 'Location updated'
            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update location'
                ], 422
            );
        }

    }

    public function postRegisterTutor(Request $request){
        $this->validate($request,[
            'email' => 'required|email|unique:users',
            'name' => 'required',
            'phone' => 'required|digits_between:11,20|unique:users',
        ]);
        $request = $request->all();
        //register students
        $user = User::registerTutor($request);
        //insert user profile
        Profile::registerUserProfile($user);
        return [
            'status' => 'success',
            'messages' => 'Tutor registered'
        ];
    }

    public function confirm($confirmation_code)
    {
        if( ! $confirmation_code)
        {
            throw new InvalidConfirmationCodeException;
        }

        $user = User::whereConfirmationCode($confirmation_code)->first();

        if ( ! $user)
        {
            throw new InvalidConfirmationCodeException;
        }

        $user->confirmed = 1;
        $user->confirmation_code = null;
        $user->save();

        return [
            'status' => 'success',
            'messages' => 'You have successfully verified your account.'
        ];
    }

    public function updateUser(Request $request){
        $request = $request->all();
        $update_arr = [];
        if(isset($request['userid'])){
            $userid = $request['userid'];
            $password = isset($request['password'])&&!empty($request['password'])?$request['password']:'';
            $name = isset($request['name'])&&!empty($request['name'])?$request['name']:'';
            $email = isset($request['emailf'])&&!empty($request['emailf'])?$request['emailf']:'';
            $phone = isset($request['phonef'])&&!empty($request['phonef'])?$request['phonef']:'';
            if(!empty($password)){ $update_arr['password'] = $password;}
            if(!empty($name)){
                $fullName = explode(" ",$request['name']);
                if(count($fullName)>1){
                    $firstName = $fullName[0]; $lastName = $fullName[1];
                }else{
                    $firstName = $fullName[0]; $lastName = '';
                }
                $update_arr['firstName'] = $firstName;
                $update_arr['lastName'] = $lastName;
            }
            if(!empty($email)){ $update_arr['email'] = $email;}
            if(!empty($phone)){ $update_arr['phone'] = $phone;}
            //return $update_arr;
            User::where('id','=',$userid)->update($update_arr);
            //DB::statement("UPDATE users  SET  firstName = 'wasim' where id = 9");
            return [
                'status' => 'success',
                'user_id' => $userid,
                'messages' => 'User updated'
            ];
        }
        return response()->json(
            [
                'status' => 'error',
                'message' => 'Unable to update user'
            ], 422
        );
    }

    public function getPasswordResetCode(Request $request){

        $this->validate($request, [
            'phone'     => 'required|digits_between:11,20',
            'role_id'   =>  'required'
        ]);

        $phone  = $request->phone;
        $roleId = $request->role_id;

        $isEligibleOrNot = User::isEligibleToRequestResetPassword($phone, $roleId);

        if($isEligibleOrNot['status'] == 'error')
            return response()->json($isEligibleOrNot);

        return $this->generateRandomCodeAndSendThroughTwilio($phone);
    }

    public function resetPassword(Request $request){

        $this->validate($request, [
            'phone' => 'required|digits_between:11,20',
            'password' => 'required|min:6|confirmed'
        ]);

        $password = $request->password;

        $phone = $request->phone;

        $userInitObject = new User();

        $activeUser = $userInitObject->isActive(substr($phone,-10));

        if(!$activeUser)
            return response()->json(['status'=>'error', 'message'=>'Either user does not exists or is not active!']);

        $activeUser->password = Hash::make($password);

        $isSaved = $activeUser->save();

        if(!$isSaved)
            return response()->json(['status'=>'error', 'message'=>'Oops! could not update password!']);

        PhoneCode::where('phone', 'LIKE', '%'.substr($phone,-5))->where('verified', 0)->update(['verified'=>1]);

        return response()->json(['status'=>'success', 'message'=>'Password updated successfully']);

    }
}
