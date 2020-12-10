<?php

namespace App\Http\Controllers;

use App\Jobs\SessionPaymentEmail;
use App\Models\Disbursement;
use App\Models\SessionPayment;
use App\Services\CostCalculation\SessionCost;
use App\Wallet;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Jobs\ReceivedPaymentNotification;

class WalletController extends Controller {

	public function receivePayment(Request $request) {
		$this->validate($request,
			[
				'session_id' => 'required',
				'amount'     => 'required',
			]);
		$session = Session::find($request->session_id);

		// start dev 7 work
		$date                      = Carbon::parse($session->started_at);
		$now                       = Carbon::parse($session->ended_at);
		$durationInHour            = ceil($date->diffInSeconds($now) / 60 / 60);
		$totalCostAccordingToHours = app(SessionCost::class)->execute($durationInHour, $session);
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
		if ($amount > $totalCostAccordingToHours) {
			$extraWallet               = new Wallet();
			$extraWallet->session_id   = $session->id;
			$extraWallet->amount       = $amount - $totalCostAccordingToHours;
			$extraWallet->type         = 'extra';
			$extraWallet->from_user_id = $session->student_id;
			$extraWallet->to_user_id   = $session->tutor_id;
			$extraWallet->save();
		}
		//END wallet related work section by DEV7
		//update session Payment
		$sessionPayment = SessionPayment::where('session_id', $request->session_id)->first();
		if ($sessionPayment) {
			$sessionPayment->update([
				'transaction_status' => 'Paid'
			]);
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
		$extra      = Wallet::where('type', 'extra')
							->where(function ($query) use ($student_id) {
								$query->where('from_user_id', '=', $student_id)
									  ->orWhere('to_user_id', '=', $student_id);
							})->sum('amount');

//		$credit = Wallet::where('type', 'credit')
//						->where(function ($query) use ($student_id) {
//							$query->where('from_user_id', '=', $student_id)
//								  ->orWhere('to_user_id', '=', $student_id);
//						})->sum('amount');
		if ($extra) {
			$totalAmount = $extra;
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
}
