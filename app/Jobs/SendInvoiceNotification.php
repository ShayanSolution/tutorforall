<?php

namespace App\Jobs;

use App\Helpers\Push;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Log;

class SendInvoiceNotification extends Job {

	protected $invoice;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($invoice) {
		Log::info('Tutor Invoice push notification constructor called at ' . Carbon::now());
		$this->invoice = $invoice;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle() {
		$invoiceID = $this->invoice->id;
		//get user device token to send notification\
		$user = User::where('id', '=', $this->invoice->tutor_id)->first();
		if (!empty($user->device_token)) {

			$title = Config::get('user-constants.APP_NAME');
			if ($this->invoice->payable > 0)
				$body = "Your pending amount is " . $this->invoice->payable . ' We will pay as per our policy';
			else
				$body = "Your due amount is " . $this->invoice->receiveable .
					' Your invoice due date is ' . Carbon::make($this->invoice->due_date)->format('d-m-y')
					. ' please pay your invoice with in due date';
			$customData = array(
				'notification_type' => 'invoice_notification',
				'invoice_id'        => (string)$invoiceID,
			);
			Push::handle($title, $body, $customData, $user);
		}
	}
}
