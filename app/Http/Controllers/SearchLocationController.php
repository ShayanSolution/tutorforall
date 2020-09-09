<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\SearchLocationInterface;
use Illuminate\Support\Facades\Auth;

class SearchLocationController extends Controller
{
    public function __construct(SearchLocationInterface $searchLocationInterface)
    {
        $this->searchLocationInterface = $searchLocationInterface;
    }

    public function createSearchLocation(Request $request){
        $this->validate($request,[
            'user_id' => 'required',
            'place_name'  => 'required',
            'latitude'  => 'required',
            'longitude'  => 'required',
            'type'  => 'required',
            'modify_type' => 'required'
        ]);
        if ($request->modify_type == 'update'){
            $this->validate($request,[
                'update_id' =>'required'
            ]);
        }
        //search location create or update
        $searchLocation = $this->searchLocationInterface->searchLocation($request);

        if ($searchLocation){
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Location Saved Successfully'
                ], 200
            );
        } else {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to save location'
                ], 422
            );
        }
    }

    public function getSearchLocation($id){
        $user = Auth::user($id);
        if ($user) {
            $searchLocations = $this->searchLocationInterface->locations($id);
            if ($searchLocations) {
                return response()->json(
                    [
                        'status' => 'success',
                        'message' => 'Search Location',
                        'data' => $searchLocations,
                    ], 200
                );
            } else {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Search location error'
                    ], 422
                );
            }
        } else {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'No user found'
                ], 422
            );
        }
    }
}
