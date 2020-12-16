<?php

namespace App\Jobs;

use App\Helpers\Push;
use App\Models\SessionPayment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;

class ReceivedPaymentNotification extends Job implements ShouldQueue
{
    use Queueable;

    public $session_id;
    public $student_id;

    /**
     * Create a new job instance.
     *
     * @param $session_id
     * @param $student_id
     *
     * @return void
     */
    public function __construct($session_id, $student_id)
    {
        $this->session_id  = $session_id;
        $this->student_id  = $student_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $session_id = $this->session_id;
        $student_id = $this->student_id;

        $sessionPayment = SessionPayment::where('session_id', $session_id)->first();

        $title  =   Config::get('user-constants.APP_NAME');
        $body = 'Your amount has been received and if you have paid extra, your amount will be added to your wallet.';
        $customData = array(
            'notification_type' => 'session_paid',
            'session_id' => $session_id,
            'paid_amount' => $sessionPayment->paid_amount
        );
        $user = User::find($student_id);

        Push::handle($title, $body, $customData, $user);
    }
}
