<?php

namespace App\Jobs;
use App\Helpers\Push;
//Models
use App\Models\Session;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class CancelledSessionNotification extends Job implements ShouldQueue
{
    use Queueable;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $userId, $cancelledFrom, $message, $status;

    public function __construct($userId, $cancelledFrom, $message, $status)
    {
        $this->userId = $userId;
        $this->cancelledFrom = $cancelledFrom;
        $this->message = $message;
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $userId = $this->userId;
        $cancelledFrom = $this->cancelledFrom;
        $bodyMessage = $this->message;
        $sessionPaymentStatus = $this->status;

        //get student device token to send notification
        $user = User::where('id','=', $userId)->first();
        if(!empty($user->device_token)){
            Log::info('Cancelled Session Send TO => UserId'.$userId);
            $title  = Config::get('user-constants.APP_NAME');
            $body = $bodyMessage;
            $customData = array(
                'notification_type' => 'session_cancelled',
                'status' => $sessionPaymentStatus
            );
            Push::handle($title, $body, $customData, $user);
            Log::info('Cancelled Session pushed handler');
        }

    }
}
