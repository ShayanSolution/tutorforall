<?php

namespace App\Http\Controllers;

use App\Jobs\SessionPaymentEmail;
use App\Models\Disbursement;
use App\Models\Profile;
use App\Models\SessionPayment;
use App\Services\CostCalculation\SessionCost;
use App\Wallet;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Jobs\ReceivedPaymentNotification;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller {

	public function receivePayment(Request $request) {
		$this->validate($request,
			[
				'session_id' => 'required',
				'amount'     => 'required',
			]);
        $sessionId = $request->session_id;
        $amount = $request->amount;
		$session = Session::find($sessionId);
        //check stdudent wallet and received amount will not exceed PKR 1000
        $request = new \Illuminate\Http\Request();
        $request->replace([
            'student_id' => $session->student_id
        ]);
        $studentWalletAmount = $this->walletStudent($request);
        $studentTotalWalletAmount = $studentWalletAmount->getData()->total_amount;
        $sessionRate = $session->rate;
        $willWallet = ($sessionRate - $amount) + $studentTotalWalletAmount;
        if ($willWallet < 1000){
            if ($amount > $session->rate) {
                $wallet               = new Wallet();
                $wallet->session_id   = $session->id;
                $wallet->amount       = $amount - $session->rate;
                $wallet->type         = 'credit';
                $wallet->from_user_id = $session->student_id;
                $wallet->to_user_id   = $session->tutor_id;
                $wallet->notes        = "(session_id : $session->id)(paid_amount : $amount) (session_amount : $session->rate) (wallet : $wallet->amount)";
                $wallet->save();
            }
            //update session Payment if paid amount is greater than session payment amount
            $sessionPayment = SessionPayment::where('session_id', $sessionId)->first();
            if ($sessionPayment->amount < $amount) {
                $wallet               = new Wallet();
                $wallet->session_id   = $session->id;
                $wallet->amount       = $amount - $sessionPayment->amount;
                $wallet->type         = 'credit';
                $wallet->from_user_id = $session->student_id;
                $wallet->to_user_id   = $session->tutor_id;
                $wallet->notes        = "(session_id : $session->id)(paid_amount : $amount) (session_amount : $session->rate) (wallet : $wallet->amount)";
                $wallet->save();
            }
            if ($sessionPayment) {
                $sessionPayment->update([
                    'transaction_status' => 'Paid',
                    'paid_amount'        => $request->amount,
                    'wallet_payment' => $session->rate-$sessionPayment->amount,
                ]);
                if($session->rate > $sessionPayment->amount)
                {
                    // Wallet debit entry
                    $debitWallet = new Wallet();
                    $debitWallet->session_id = $sessionPayment->session_id;
                    $debitWallet->amount = $session->rate-$sessionPayment->amount == 0 ? $sessionPayment->amount : $session->rate-$sessionPayment->amount;
                    $debitWallet->type = 'debit';
                    $debitWallet->from_user_id = $session->student_id;
                    $debitWallet->to_user_id = $session->tutor_id;
                    $debitWallet->notes = "(sessionid : $sessionPayment->session_id) (paid_amount : $amount) (session_amount : $session->rate) (wallet : $session->rate-$sessionPayment->amount)";
                    $debitWallet->save();
                }
                // Create disbursement
                $payType      = 'earn';
                $disbursement = Disbursement::create([
                    'tutor_id'         => $session->tutor_id,
                    'type'             => $payType,
                    'amount'           => $sessionPayment->amount,
                    'paymentable_type' => $sessionPayment->getMorphClass(),
                    'paymentable_id'   => $sessionPayment->id
                ]);
            }
            dispatch((new ReceivedPaymentNotification($sessionId, $session->student_id)));
            //Send Email to student
            $jobSendEmailToStudent = (new SessionPaymentEmail($sessionId,
                $session->student_id,
                $session->tutor_id));
            dispatch($jobSendEmailToStudent);

            return response()->json(
                [
                    'status' => 'success',
                ]
            );
        } else {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Received amount will exceed wallet amount greater PKR 1000.'
                ]
            );
        }
	}

	public function walletStudent(Request $request) {
		$this->validate($request,
			[
				'student_id' => 'required',
			]);
		$student_id = $request->student_id;
		$debit      = Wallet::where('type', 'debit')
							->where(function ($query) use ($student_id) {
								$query->where('from_user_id', '=', $student_id)
									  ->orWhere('to_user_id', '=', $student_id);
							})->sum('amount');

		$credit = Wallet::where('type', 'credit')
						->where(function ($query) use ($student_id) {
							$query->where('from_user_id', '=', $student_id)
								  ->orWhere('to_user_id', '=', $student_id);
						})->sum('amount');

		if ($credit >= 0 && $debit >= 0) {
			$totalAmount = $credit - $debit;
			return response()->json(
				[
					'status'       => 'success',
					'total_amount' => (string)$totalAmount
				]
			);
		} else {
			return response()->json(
				[
					'status'  => 'error',
					'message' => 'Wallet does not exist.'
				]
			);
		}
	}

	public function useWalletFirst(Request $request){
        $this->validate($request,
            [
                'use_wallet_first' => 'required',
            ]);
        // update
        $userId        = Auth::user()->id;
        $useWalletFirst = $request->use_wallet_first;
        $useWallet = Profile::where('user_id', $userId)->first();
        if ($useWallet){
            $useWallet->update([
                'use_wallet_first' => $useWalletFirst
            ]);
            return response()->json(
                [
                    'status'  => 'success',
                    'message' => 'Save wallet setting'
                ]
            );
        } else {
            return response()->json(
                [
                    'status'  => 'error',
                    'message' => 'User not found'
                ]
            );
        }
    }
}
