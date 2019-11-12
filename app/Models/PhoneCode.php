<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneCode extends Model
{
    protected $fillable = [
        'phone',
        'code'
    ];
    public static function getPhoneNumber($phone){
        $phoneWithoutCode = substr($phone,-10);
        return  self::where('phone','like','%'.$phoneWithoutCode)->first();
    }
    public function generateRandomCode($digits = 4){
        return rand(pow(10, $digits-1), pow(10, $digits)-1);
    }
    public function verifyPhoneCode($request){
        $phone = $request['phone'];
        $phoneWithoutCode = substr($phone,-10);
        $code = $request['code'];
        $phone_code = self::where('phone', 'like','%'.$phoneWithoutCode)->where('code','=',$code)->first();

        if ($phone_code){
            $new_code = $this->generateRandomCode();
            self::where('phone','=',$phone_code->phone)->where('code','=',$phone_code->code)->update(['verified'=>1,'code'=>$new_code]);
            return $new_code;
        }else{
            return false;
        }
    }
}
