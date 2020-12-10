<?php
/**
 * Created by PhpStorm.
 * User: NSC
 * Date: 12/10/2020
 * Time: 11:35 AM
 */

namespace App\Support;


class Alfalah {

	public function __construct() {
	}

	public function alfalahPayments($url, $requestBody, $requestMethod, $value) {
		$marchantId  = config('alfalah.merchantId');
		$apiPassword = config('alfalah.apiPassword');
		$gatewayUrl  = config('alfalah.gatewayUrl');
		$ch          = curl_init();
		curl_setopt($ch,
			CURLOPT_URL,
			$url
		);// Merchant ID instead of bafl10002
		curl_setopt($ch, $requestMethod, $value);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);  //Post Fields
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$headers = [
			'Authorization: Basic ' . base64_encode("merchant.$marchantId:$apiPassword"),// merchant."Merchant ID":"API Password"
			'Content-Type: application/json',
			'Host: ' . $gatewayUrl,
			'Referer: http://tutor4all-api.shayansolutions.com/checkout.php', //Your referrer address
			'cache-control: no-cache',
			'Accept: application/json'
		];

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$server_output = curl_exec($ch);
		curl_close($ch);
		return json_decode($server_output, true);
		// client code
		//
		//		$client = new \GuzzleHttp\Client([
		//			'Authorization: Basic ' . base64_encode("merchant.$marchantId:$apiPassword"),// merchant."Merchant ID":"API Password"
		//			'Content-Type: application/json',
		//			'Host: test-bankalfalah.gateway.mastercard.com',
		//			'Referer: http://tutor4all-api.shayansolutions.com/checkout.php', //Your referrer address
		//			'cache-control: no-cache',
		//			'Accept: application/json'
		//		]);

		//		dd($client);
		//		$json = $client->request('POST',$url)->withBody($requestBody)->withHeader($headers);
		//		dd($json);
		//
	}

}