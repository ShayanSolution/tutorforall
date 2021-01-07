<?php

namespace App\Http\Controllers;

use App\Helpers\Geocode;
use App\Helpers\ReverseGeocode;
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
            'phone' => 'required|digits_between:11,20',
            'role_id'=> 'required'
        ]);
        $phone = $request->phone;
        $roleId = $request->role_id;
        $user = new User;
        $phoneExist = $user->findByExactPhoneNumber($phone, $roleId);
        //if both account have than show error
        if ($phoneExist && $phoneExist->deleted_at == null){
            if ($roleId == 2) {
                $appNameForCode = 'Tootar Teacher';
            } else {
                $appNameForCode = 'Tootar';
            }
            return JsonResponse::generateResponse([
                    'status' => 'error',
                    'message' => 'You have already '.$appNameForCode.' account. Please login'
                ],500);
        }
        // If account soft delete than set deleted_at null
        if ($phoneExist && $phoneExist->deleted_at){
            $phoneExist->restore();
        }

        return $this->generateRandomCodeAndSendThroughTwilio($phone, $phoneCode = null,$roleId);
    }

    public function generateRandomCodeAndSendThroughTwilio($phone, $phoneCode = null, $roleId){
        $code = $this->generateRandomCode();
        $toNumber = $this->sanitizePhoneNumber($phone);
//                // Use the client to do fun stuff like send text messages!
        $response = TwilioHelper::sendCodeSms($toNumber, $code, $roleId);

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

    public function generateRandomCodeAndSendThroughEmail($phone, $phoneCode = null, $roleId, $userEmail, $userFirstName, $userLastName){
        $code = $this->generateRandomCode();
        $toNumber = $this->sanitizePhoneNumber($phone);
        $subject = env("MAIL_SUBJECT", "Verification Code is: ");
        // Email with verification code
        $data['code'] = $code;
        $data['userFirstName'] = $userFirstName;
        $data['userLastName'] = $userLastName;
        Mail::send('emails.resetpassword', $data, function($message) use ($userEmail, $subject, $userFirstName, $userLastName) {
            $message->to($userEmail, $userFirstName." ".$userLastName)->subject($subject);
        });
        //save code in DB
        $phoneCode = new PhoneCode();
        $phoneCode->phone  = $toNumber;
        $phoneCode->code = $code;
        $phoneCode->save();

        return JsonResponse::generateResponse(
            [
                'status' => 'success',
                'message' => 'Phone code created successfully'
            ],200
        );
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
        } else {
            $number = substr_replace($number, '+1', 0, 1);
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
            'email' => 'required',
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
        // check already account
        $user = new User;
        $phoneExist = $user->findByExactPhoneNumber($phone, $role_id);
        //if both account have than show error
        if ($phoneExist && $phoneExist->deleted_at == null){
            if ($role_id == 2) {
                $appNameForCode = 'Tootar Teacher';
            } else {
                $appNameForCode = 'Tootar';
            }
            return JsonResponse::generateResponse([
                'status' => 'error',
                'message' => 'You have already '.$appNameForCode.' account. Please login'
            ],500);
        }
        // If account soft delete than set deleted_at null
        if ($phoneExist && $phoneExist->deleted_at){
            $phoneExist->restore();
        }

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


            $user = User::updateOrCreate(['phone' => $phone, 'role_id' => $role_id], $userDataArray)->id;

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
            $location = User::updateTutorLocation($user_id,$longitude,$latitude);
            $geoReverseCoding = ReverseGeocode::reverseGeoCoding($latitude, $longitude);
            if($geoReverseCoding){
                $user->update([
                    'area' => $geoReverseCoding['full_area'],
                    'city' => $geoReverseCoding['city'],
                    'province' => $geoReverseCoding['province'],
                    'country' => $geoReverseCoding['country'],
                    'reverse_geocode_address' => $geoReverseCoding['reverse_geocode_address'],
                ]);
            }
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
        if ($isEligibleOrNot['is_approved'] == 0) {
            $userEmail = $isEligibleOrNot['user_email'];
            $userFirstName = $isEligibleOrNot['user_first_name'];
            $userLastName = $isEligibleOrNot['user_last_name'];
            return $this->generateRandomCodeAndSendThroughEmail($phone, $phoneCode = null, $roleId, $userEmail, $userFirstName, $userLastName);
        } else {
            return $this->generateRandomCodeAndSendThroughTwilio($phone, $phoneCode = null, $roleId);
        }
    }

    public function resetPassword(Request $request){

        $this->validate($request, [
            'phone' => 'required|digits_between:11,20',
            'password' => 'required|min:6|confirmed',
            'role_id' => 'required'
        ]);

        $password = $request->password;
        $phone = $request->phone;
        $roleId = $request->role_id;

        $userInitObject = new User();

        $activeUser = $userInitObject->isActive(substr($phone,-10), $roleId);
        if(!$activeUser)
            return response()->json(['status'=>'error', 'message'=>'Either user does not exists or is not active!']);

        $activeUser->password = Hash::make($password);

        $isSaved = $activeUser->save();

        if(!$isSaved)
            return response()->json(['status'=>'error', 'message'=>'Oops! could not update password!']);

        PhoneCode::where('phone', 'LIKE', '%'.substr($phone,-5))->where('verified', 0)->update(['verified'=>1]);

        return response()->json(['status'=>'success', 'message'=>'Password updated successfully']);

    }

    public function getFinalVerificationCode(Request $request){
        $this->validate($request,[
            'phone' => 'required|digits_between:11,20',
            'role_id'=> 'required'
        ]);
        $userId = Auth::user();
        $phone = $request->phone;
        $roleId = $request->role_id;
        // check phone number exist
        $user = new User;
        $phoneExist = $user->findByExactPhoneNumber($phone, $roleId);
        // if user already verified phone number
        if ($userId->final_phone_verification == 1) {
            return JsonResponse::generateResponse([
                'status' => 'error',
                'message' => $userId->phone.' is already verified.'
            ],500);
        }
        // if already account
        if ($phoneExist && $phoneExist->id != $userId->id) {
            return JsonResponse::generateResponse([
                'status' => 'error',
                'message' => 'This phone number have already active account. Please use another phone number.'
            ],500);
        }
        // update phone number
        User::where('id', $userId->id)->update([
            'phone' => $phone,
        ]);
        // send SMS
        return $this->generateRandomCodeAndSendThroughTwilio($phone, $phoneCode = null,$roleId);
    }

    public function postVerifyFinalVerificationCode(Request $request){
        $this->validate($request,[
            'phone' => 'required_without:|digits_between:11,20',
            'code' => 'required_without:|digits:4',
        ]);
        $userId = Auth::user()->id;
        $request = $request->all();
        $phone_code = new PhoneCode();
        $phone_verified = $phone_code->verifyPhoneCode($request);
        if ($phone_verified){
            User::where('id', $userId)->update([
                'final_phone_verification' => 1,
                'is_online' => 1
            ]);
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
}
