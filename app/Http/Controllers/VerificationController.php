<?php

namespace App\Http\Controllers;

use App\Models\PhoneCode;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
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
}
