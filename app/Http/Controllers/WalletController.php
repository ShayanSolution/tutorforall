<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\User;
use App\Wallet;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function receivePayment(Request $request){
        $this->validate($request,[
            'session_id' => 'required',
            'amount' => 'required',
        ]);
        $session = Session::find($request->session_id);
        $amount  = $request->amount;
            $wallet                   =   new Wallet();
            $wallet->session_id       =   $session->id;
            $wallet->amount           =   $amount;
            $wallet->type             =   'credit';
            $wallet->from_user_id     =   $session->tutor_id;
            $wallet->to_user_id       =   $session->student_id;
            $wallet->save();

            $message = PushNotification::message(
                'Your amount has been received and if you have paid extra, your amount will be added to your wallet.',
                array(
                    'badge' => 1,
                    'sound' => 'example.aiff',
                    'actionLocKey' => 'Action button title!',
                    'locKey' => 'localized key',
                    'locArgs' => array(
                        'localized args',
                        'localized args',
                    ),
                    'launchImage' => 'image.jpg',
                    'custom' => array('custom_data' => array(
                        'notification_type' => 'session_paid',
                        'session_id' => $request->session_id
                    ))
                ));

            $user = User::find($session->student_id);
                if($user->device_type == 'android') {
                    PushNotification::app('appNameAndroid')->to($user->device_token)->send($message);
                }else{
                    PushNotification::app('appStudentIOS')->to($user->device_token)->send($message);
                }
            return response()->json(
                [
                   'status'=> 'success',
                ]
            );
    }

    public function walletStudent(Request $request){
        $this->validate($request,[
            'student_id' => 'required',
        ]);
        $student_id = $request->student_id;
        $debit = Wallet::where('type', 'debit')
        ->where(function ($query) use ($student_id) {
            $query->where('from_user_id', '=', $student_id)
                ->orWhere('to_user_id', '=', $student_id);
        })->sum('amount');

        $credit = Wallet::where('type', 'credit')
            ->where(function ($query) use ($student_id) {
                $query->where('from_user_id', '=', $student_id)
                    ->orWhere('to_user_id', '=', $student_id);
            })->sum('amount');
        if($credit && $debit) {
            $totalAmount = $credit - $debit;
            return response()->json(
                [
                    'status' => 'success',
                    'total_amount' => (string)$totalAmount
                ]
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Wallet does not exist.'
                ]
            );
        }
    }
}
