<?php

namespace App\Jobs;

use App\Helpers\Push;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Log;

class SendBlockNotification extends Job {

	protected $user;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct(User $user) {
		$this->user = $user;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle() {
		if (!empty($this->user->device_token)) {
			Log::info('Sending notification to user for blocking = ' . $this->user->id);
			$title      = Config::get('user-constants.APP_NAME');
			$body       = "Your are blocked due to unpaid invoice. Please pay as soon as possible and start earning. Good day";
			$customData = array(
				'notification_type' => 'invoice_notification',
				'user_id'           => (string)$this->user->id,
			);
			Push::handle($title, $body, $customData, $this->user);
		}
	}
}
