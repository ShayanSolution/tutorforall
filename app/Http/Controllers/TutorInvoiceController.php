<?php

namespace App\Http\Controllers;

use App\Models\CreditCard;
use App\Models\TutorInvoice;
use App\Support\Alfalah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TutorInvoiceController extends Controller {

	public function getTutorInvoice() {
		$tutorId       = Auth::user()->id;
		$tutorInvoices = TutorInvoice::where('tutor_id', $tutorId)->get();
		if ($tutorInvoices) {
			return response()->json(
				[
					'status'   => 'success',
					'invoices' => $tutorInvoices
				]
			);
		} else {
			return response()->json(
				[
					'status'  => 'error',
					'message' => 'No invoice found'
				]
			);
		}
	}

	public function tutorPayInvoice(Request $request) {
		$this->validate($request,
			[
				'invoice_id'           => 'required',
				'transaction_ref_no'   => 'required',
				'transaction_type'     => 'required',
				'transaction_platform' => 'required',
				'transaction_status'   => 'required',
			]);
		$invoiceId                  = $request->invoice_id;
		$payInvoice                 = TutorInvoice::where('id', $invoiceId)->update([
			'status'                 => 'paid',
			'transaction_ref_no'     => $request->transaction_ref_no,
			'transaction_type'       => $request->transaction_type,
			'transaction_platform'   => $request->transaction_platform,
			'transaction_status'     => $request->transaction_status,
			'transaction_session_id' => $request->transaction_session_id ?? NULL,
			'credit_card_id'         => $request->credit_card_id ?? NULL
		]);
		$invoice                    = TutorInvoice::find($invoiceId);
		$invoice->tutor->is_blocked = 0;
		$invoice->tutor->save();
		if ($payInvoice) {
			return response()->json(
				[
					'status'  => 'success',
					'message' => 'Invoice paid successfully'
				]
			);
		} else {
			return response()->json(
				[
					'status'  => 'error',
					'message' => 'Invoice not paid'
				]
			);
		}
	}

	public function teacherCardInvoicePayment(Request $request) {
		$this->validate($request,
			[
				'amount'        => 'required',
				'payment_token' => 'required',
				'agreement'     => 'required',
				'invoiceId'     => 'required'
			]);
		$marchantId = config('alfalah.merchantId');
		$gatewayUrl = config('alfalah.gatewayUrl');
		$apiVersion = config('alfalah.apiVersion');
		$orderId = rand(100000, 999999);
		$requestBody
				 = '{
			"apiOperation": "CREATE_CHECKOUT_SESSION",
			"interaction": {
			"operation": "PURCHASE"
			},
			"order": {
			"id" : "' . $orderId . '",
				"currency" : "PKR"
			}}';


		$url = "https://$gatewayUrl/api/rest/version/$apiVersion/merchant/$marchantId/session";
		$json = app(Alfalah::class)->alfalahPayments($url,$requestBody,CURLOPT_POST,1);

		$sessionId = $json['session']['id'];

		if ($sessionId) {
			$amount        = $request->amount;
			$payment_token = $request->payment_token;
			$agreementID   = $request->agreement;
			$invoice       = $request->invoiceId;


			$transactionId = rand(1, 10);
			$requestBodyPayment
						   = '{
				"apiOperation": "PAY",
				"agreement":{
						"id":"' . $agreementID . '",
						"type":"RECURRING",
						"recurring": {
							"amountVariability":"VARIABLE",
							"daysBetweenPayments":"999"
						}
				},
				"session":{
					"id": "' . $sessionId . '"
				},
				"sourceOfFunds": {
					"provided":{
						"card":{
							"storedOnFile":"STORED"
						}
					},
					"type": "SCHEME_TOKEN",
					"token":"' . $payment_token . '"
				},
				"transaction":{
					"source":"MERCHANT"
				},
				"order":{
					"amount":"' . $amount . '",
					"currency": "PKR"
				}
            }';
			$url = "https://$gatewayUrl/api/rest/version/$apiVersion/merchant/$marchantId/order/" . $orderId . "/transaction/" . $transactionId;
			$json = app(Alfalah::class)->alfalahPayments($url,$requestBodyPayment,CURLOPT_CUSTOMREQUEST,"PUT");
			$credit_cards = CreditCard::whereTokenId($payment_token)->first();
			if ($json['result'] == 'SUCCESS') {
				$request = new \Illuminate\Http\Request();
				$request->replace([
					'invoice_id'             => $invoice,
					'transaction_platform'   => 'card',
					'transaction_ref_no'     => $orderId,
					'transaction_type'       => 'CARD',
					'transaction_status'     => 'Paid',
					'transaction_session_id' => $sessionId,
					'credit_card_id'         => $credit_cards->id
				]);
				return $this->tutorPayInvoice($request);

			} else {
				return response()->json(
					[
						'status'  => 'error',
						'message' => 'payment failed'
					],
					422
				);
			}
		}
	}
}
