<?php

namespace App\Jobs;

use App\Helpers\Push;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Log;

class SendOfflineNotification extends Job {

	protected $user;
	protected $message;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($user, $message) {
		$this->user = $user;
		$this->message = $message;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle() {
		if (!empty($this->user)) {
			Log::info('Sending notification for offline ===== Reason ===>'.$this->message);
			$title      = Config::get('user-constants.APP_NAME');
			$body       = $this->message;
			$customData = array(
				'notification_type' => 'admin_notification',
			);
			Push::handle($title, $body, $customData, $this->user);
		}
	}
}
