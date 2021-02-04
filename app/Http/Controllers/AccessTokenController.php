<?php

namespace App\Http\Controllers;

use App\Models\LastLogin;
use App\Models\User;
use Illuminate\Http\Request;
use App\Repositories\Contracts\UserRepository;
use Carbon\Carbon;

class AccessTokenController extends Controller
{
    /**
     * Instance of UserRepository
     *
     * @var UserRepository
     */
    private $userRepository;

    /**
     * Constructor
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;

        parent::__construct();
    }

    /**
     * Since, with Laravel|Lumen passport doesn't restrict
     * a client requesting any scope. we have to restrict it.
     * http://stackoverflow.com/questions/39436509/laravel-passport-scopes
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function createAccessToken(Request $request)
    {
        $inputs = $request->all();

        $user = User::where('phone', $request->username)->where('role_id', $request->role_id)->first();

        if(!$user){
            return response()->json(['error'=>'error', 'message'=>'Account does not exist']);
        }

        if($user->is_active == 0){
            return response()->json(['error'=>'error', 'message'=>'Account is Inactive'], 401);
        }
        // last login update column and entry in lasat logins table
        $user_id =  $user->id;
        $role_id =  $user->role_id;
        User::where('id', $user_id)->update([
            'last_login'=> Carbon::now(),
            'is_online' => 1
        ]);
        LastLogin::create([
            'user_id' =>  $user_id,
            'role_id' => $role_id
        ]);
        $roleInMessage = $request->role_id == 2 ? 'Tutor' : 'Student';

//        if($user->role_id != $request->role_id)
//            return response()->json(['error'=>'error', 'message'=> 'You are not a '.$roleInMessage.'. You cannot login here.']);


//        $user = new User();
//        $isActive = $user->isActive($inputs['username']);
//
//        if(!$isActive){
//            return response()->json(
//                [
//                    'status' => 'error',
//                    'message' => 'User is inactive.'
//                ], 422
//            );
//        }

        //Set default scope with full access
        if (!isset($inputs['scope']) || empty($inputs['scope'])) {
            $inputs['scope'] = "*";
        }

        $tokenRequest = $request->create('/oauth/token', 'post', $inputs);

        // forward the request to the oauth token request endpoint
        return app()->dispatch($tokenRequest);
    }
}
