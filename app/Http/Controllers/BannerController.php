<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\BannerStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BannerController extends Controller
{
    public function getBanner(Request $request){
        $userId = Auth::user()->id;
        if ($userId) {
            $banner = BannerStatus::with('banner')->where('receiver_id', $userId)->where('is_read', 0)->orderBy('id', 'desc')->first();
            if ($banner){
                $data = [];
                $data['id'] = $banner['banner']->id;
                $data['text'] = $banner['banner']->text;
                $data['hyperlink'] = $banner['banner']->hyperlink;
                $data['path'] = $banner['banner']->path;
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Banner are',
                    'data' => $data
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
        $userId = Auth::user()->id;
        $getBanner = Banner::where('id', $request->id)->get();
        if ($getBanner->always_show_banner == 0) {
            BannerStatus::where('banner_id', $request->id)->where('receiver_id', $userId)->update([
                'is_read' => 1
            ]);
        }
        return response()->json([
            'status'  => 'success',
            'message' => 'Banner read Successfully',
        ]);
    }
}
