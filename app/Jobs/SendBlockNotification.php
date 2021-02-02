<?php

namespace App\Jobs;

use App\Helpers\Push;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Log;

class SendBlockNotification extends Job {

	protected $user;
	protected $message;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct(User $user, $message) {
		$this->user = $user;
		$this->message = $message;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle() {
		if (!empty($this->user->device_token)) {
			Log::info('Sending notification to user for blocking = ' . $this->user->id.'===== Reason ===>'.$this->message);
			$title      = Config::get('user-constants.APP_NAME');
			$body       = $this->message;
			$customData = array(
				'notification_type' => 'invoice_notification',
				'user_id'           => (string)$this->user->id,
			);
			Push::handle($title, $body, $customData, $this->user);
		}
	}
}
