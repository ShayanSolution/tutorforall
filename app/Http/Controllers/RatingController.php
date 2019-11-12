<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Log;

//Models
use App\Models\Rating;

class RatingController extends Controller
{
    //Save rating against session
    public static function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required',
            'rating' => 'required|numeric',
        ]);
        $response = Rating::generateErrorResponse($validator);
        if($response['code'] == 500){
            return response()->json($response);
        }
        $data = $request->all();
        $session=Session::find($request->session_id);
        $data['user_id'] = $session->tutor_id;

        $rating = Rating::create($data);

        if($rating){
            return response()->json(
                [
                    'status' => 'success',
                    'rating' => $rating,
                ], 200
            );
        }else{

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to save rating'
                ], 422
            );
        }
    }
}
