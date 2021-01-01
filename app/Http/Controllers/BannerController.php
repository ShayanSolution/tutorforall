<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BannerController extends Controller
{
    public function getBanner(Request $request){
        $userId = Auth::user()->id;
        if ($userId) {
            $banner = Banner::where('user_id', $userId)->where('is_read', 0)->first();
            if ($banner){
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Banner are',
                    'data' => $banner
                ]);
            } else {
                return response()->json([
                        'status'  => 'error',
                        'message' => 'No banner for user'
                    ], 422);
            }
        } else {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unable to find user'
            ], 422);
        }
    }

    public function readBanner(Request $request){
        $this->validate($request,[
            'id' => 'required',
        ]);
        $banner = Banner::where('id', $request->id)->update([
            'is_read' => 1
        ]);
        return response()->json([
            'status'  => 'success',
            'message' => 'Banner read Successfully',
        ]);
    }
}
