<?php

namespace App\Http\Controllers;

use App\Models\PhoneCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Location;

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

        $code = PhoneCode::where('phone', $phone)
            ->where('verified', 0)
            ->where('created_at', '>=', Carbon::today())
            ->orderBy('id')
            ->first();

        if(!$code){
            $record = [
                'phone' => $phone,
                'code' => $this->generateRandomCode(),
            ];
            PhoneCode::create($record);
            unset($record['phone']);
            return $record;
        }else{
            return [
                'code' => $code->code
            ];
        }
    }

    public function generateRandomCode($digits = 4){
        return rand(pow(10, $digits-1), pow(10, $digits)-1);
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
        if(is_array($request)){
            $phone = $request['phone'];
            $code = $request['code'];
        }else{
            $phone = $request->phone;
            $code = $request->code;
        }

        $code = PhoneCode::where('phone', $phone)
            ->where('code', $code)
            ->where('verified', 0)
            ->where('created_at', '>=', Carbon::today())
            ->orderBy('id')
            ->first();

        if($code){
            $code->verified = 1;
            $code->save();
            return [
                'status' => 'success',
                'message' => 'Phone code has been verified'
            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Invalid or expired phone code'
                ], 422
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
    public function postRegisterStudent(Request $request){
        $this->validate($request,[
            'email' => 'required|email|unique:users',
            'phone' => 'required|digits_between:11,20|unique:users',
            'code' => 'required|digits:4',
        ]);

        $email = $request->email;
        $phone = $request->phone;
        $code = $request->code;

        $code = PhoneCode::where('phone', $phone)
            ->where('code', $code)
            ->where('verified', 1)
            ->where('created_at', '>=', Carbon::today()) //TODO: This check can disabled if we need to validate code not generated on same day
            ->orderBy('id')
            ->first();

        if($code){

            $user = User::create([
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($code),
                'uid' => md5(microtime()),
                'role_id' => 2
            ])->id;

            if($user){
                return [
                    'status' => 'success',
                    'messages' => 'Student has been created'
                ];
            }else{
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Unable to create student'
                    ], 422
                );
            }

        } else {

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Invalid or expired phone verification'
                ], 422
            );

        }
    }



    public function postRegisterLocation(Request $request){
        $this->validate($request,[
            'longitude' => 'required',
            'latitude' => 'required',
        ]);

        $longitude = $request->longitude;
        $latitude = $request->latitude;

        $location = Location::create([
            'longitude' => $longitude,
            'latitude' => $latitude,
        ])->id;

        if($location){
            return [
                'status' => 'success',
                'messages' => 'Location has been created'
            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to create location'
                ], 422
            );
        }
        
    }

}
