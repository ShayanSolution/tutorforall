<?php

namespace App\Jobs;

use App\Helpers\Push;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Log;

class SendOfflineNotification extends Job {

	protected $userId;
	protected $message;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($userId, $message) {
		$this->userId = $userId;
		$this->message = $message;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle() {
        $userId = $this->userId;
        //get tutor device token to send notification
        $user = User::where('id','=', $userId)->first();
		if (!empty($user->device_token)) {
			Log::info('Sending notification for offline ===== Reason ===>'.$this->message);
			$title      = Config::get('user-constants.APP_NAME');
			$body       = $this->message;
			$customData = array(
				'notification_type' => 'offline_notification',
			);
			Push::handle($title, $body, $customData, $user);
		}
	}
}
