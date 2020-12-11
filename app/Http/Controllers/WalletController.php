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
		$session = Session::find($request->session_id);

		// start dev 7 work
		//		$date                      = Carbon::parse($session->started_at);
		//		$now                       = Carbon::parse($session->ended_at);
		//		$durationInHour            = ceil($date->diffInSeconds($now) / 60 / 60);
		//		$totalCostAccordingToHours = app(SessionCost::class)->execute($durationInHour, $session);
		// End dev 7

		$amount = $request->amount;
		//		$wallet             = new Wallet();
		//		$wallet->session_id = $session->id;
		//		$wallet->amount     = $totalCostAccordingToHours;
		//		//            $wallet->amount           =   $amount;
		//		$wallet->type         = 'credit';
		//		$wallet->from_user_id = $session->tutor_id;
		//		$wallet->to_user_id   = $session->student_id;
		//		$wallet->save();
		// New wallet work added by DEV7
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
		//END wallet related work section by DEV7
		//update session Payment
		$sessionPayment = SessionPayment::where('session_id', $request->session_id)->first();

		if ($sessionPayment) {
			$sessionPayment->update([
				'transaction_status' => 'Paid',
				'paid_amount'        => $request->amount
			]);
			if($session->rate > $sessionPayment->amount)
			{
				// Create Extra Session payment Entry
				$walletSession = new SessionPayment();
				$walletSession->session_id = $sessionPayment->session_id;
				$walletSession->transaction_ref_no = NULL;
				$walletSession->transaction_type = NULL;
				$walletSession->transaction_platform = 'wallet';
				$walletSession->amount = $session->rate-$sessionPayment->amount;
				$walletSession->paid_amount = $amount;
				$walletSession->insert_date_time = Carbon::now()->format('yymdhis');
				$walletSession->transaction_status = NULL;
				$walletSession->mobile_number = NULL;
				$walletSession->cnic_last_six_digits = NULL;
				$walletSession->save();

				// Wallet debit entry
				$debitWallet = new Wallet();
				$debitWallet->session_id = $sessionPayment->session_id;
				$debitWallet->amount = $session->rate-$sessionPayment->amount;
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
		dispatch((new ReceivedPaymentNotification($request->session_id, $session->student_id)));
		//Send Email to student
		$jobSendEmailToStudent = (new SessionPaymentEmail($request->session_id,
			$session->student_id,
			$session->tutor_id));
		dispatch($jobSendEmailToStudent);

		return response()->json(
			[
				'status' => 'success',
			]
		);
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
