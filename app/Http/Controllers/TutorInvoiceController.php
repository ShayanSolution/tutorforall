<?php

namespace App\Http\Controllers;

use App\Models\TutorInvoice;
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
			'status'               => 'paid',
			'transaction_ref_no'   => $request->transaction_ref_no,
			'transaction_type'     => $request->transaction_type,
			'transaction_platform' => $request->transaction_platform,
			'transaction_status'   => $request->transaction_status,
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
		$orderId = rand(1000000000, 100000000000000);
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
		$ch      = curl_init();
		curl_setopt($ch,
			CURLOPT_URL,
			"https://test-bankalfalah.gateway.mastercard.com/api/rest/version/56/merchant/Tootar_IO/session");// Merchant ID instead of bafl10002
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);  //Post Fields
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$headers = [
			'Authorization: Basic ' . base64_encode("merchant.Tootar_IO:021f3dc88dcd5f2af85b2d856281f941"),// merchant."Merchant ID":"API Password"
			'Content-Type: application/json',
			'Host: test-bankalfalah.gateway.mastercard.com',
			'Referer: http://tutor4all-api.shayansolutions.com/checkout.php', //Your referrer address
			'cache-control: no-cache',
			'Accept: application/json'
		];

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$server_output = curl_exec($ch);
		curl_close($ch);
		$json      = json_decode($server_output, true);
		$sessionId = $json['session']['id'];

		if ($sessionId) {
			$amount        = $request->amount;
			$payment_token = $request->payment_token;
			$agreementID   = $request->agreement;
			$invoice       = $request->invoiceId;


			$orderId       = rand(1000000000, 100000000000000);
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
			$ch            = curl_init();
			curl_setopt($ch,
				CURLOPT_URL,
				"https://test-bankalfalah.gateway.mastercard.com/api/rest/version/56/merchant/Tootar_IO/order/" . $orderId . "/transaction/" . $transactionId);// Merchant ID instead of bafl10002
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBodyPayment);  //Post Fields
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


			$headers = [
				'Authorization: Basic ' . base64_encode("merchant.Tootar_IO:021f3dc88dcd5f2af85b2d856281f941"),// merchant."Merchant ID":"API Password"
				'Content-Type: application/json',
				'Host: test-bankalfalah.gateway.mastercard.com',
				'Referer: http://tutor4all-api.shayansolutions.com/checkout.php', //Your referrer address
				'cache-control: no-cache',
				'Accept: application/json'
			];

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			$server_output = curl_exec($ch);
			curl_close($ch);

			$json = json_decode($server_output, true);
			if ($json['result'] == 'SUCCESS') {
				$request = new \Illuminate\Http\Request();
				$request->replace([
					'invoice_id'           => $invoice,
					'transaction_platform' => 'card',
					'transaction_ref_no'   => $sessionId,
					'transaction_type'     => 'CARD',
					'transaction_status'   => 'Paid'
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
