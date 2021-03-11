<?php

namespace App\Jobs;

use App\Helpers\Push;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;

class SessionPaidNotificationToTutor extends Job implements ShouldQueue
{
    use Queueable;

    public $session_id;
    public $tutor_id;
    public $transaction_platform;

    /**
     * Create a new job instance.
     *
     * @param $session_id
     * @param $tutor_id
     *
     * @return void
     */
    public function __construct($session_id, $tutor_id, $transaction_platform)
    {
        $this->session_id  = $session_id;
        $this->tutor_id  = $tutor_id;
        $this->transaction_platform  = $transaction_platform;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $session_id = $this->session_id;
        $tutor_id = $this->tutor_id;
        $transaction_platform = $this->transaction_platform;

        $title  =   Config::get('user-constants.APP_NAME');
        $body = 'Student paid for session using '.$transaction_platform;
        $customData = array(
            'notification_type' => 'session_paid',
            'session_id' => $session_id,
            'transaction_platform' => $transaction_platform
        );
        $user = User::find($tutor_id);

        Push::handle($title, $body, $customData, $user);
    }
}
