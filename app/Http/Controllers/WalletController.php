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
        $willWallet = ($amount - $sessionRate) + $studentTotalWalletAmount;
        if ($willWallet < 1000){
            //update session Payment if paid amount is greater than session payment amount
            $sessionPayment = SessionPayment::where('session_id', $sessionId)->first();
            if ($amount > $sessionPayment->amount) {
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
                    'paid_amount'        => $amount,
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
                    'message' => 'Received amount will exceed wallet limit PKR 1000.'
                ]
            );
        }
	}

	public function wallet(Request $request) {
		if ($request->student_id){
            $studentId = $request->student_id;
            list($debit, $credit) = $this->studentWallet($studentId);

        } else {
            $tutorId = $request->tutor_id;
            list($debit, $credit) = $this->tutorWallet($tutorId);
        }

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

	public function studentWallet($studentId) {
        $debit      = Wallet::where('type', 'debit')
            ->where(function ($query) use ($studentId) {
                $query->where('from_user_id', '=', $studentId)
                    ->orWhere('to_user_id', '=', $studentId);
            })->sum('amount');

        $credit = Wallet::where('type', 'credit')
            ->where(function ($query) use ($studentId) {
                $query->where('from_user_id', '=', $studentId)
                    ->orWhere('to_user_id', '=', $studentId);
            })->sum('amount');

        return [$debit, $credit];
    }

    public function tutorWallet($tutorId) {
        $debit      = Wallet::where('type', 'debit')
            ->where(function ($query) use ($tutorId) {
                $query->Where('to_user_id', '=', $tutorId)
                ->whereNotNull('added_by');
            })->sum('amount');

        $credit = Wallet::where('type', 'credit')
            ->where(function ($query) use ($tutorId) {
                $query->Where('to_user_id', '=', $tutorId)
                    ->whereNotNull('added_by');
            })->sum('amount');

        return [$debit, $credit];
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
