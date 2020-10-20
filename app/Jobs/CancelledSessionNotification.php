<?php

namespace App\Jobs;
use App\Helpers\Push;
//Models
use App\Models\Session;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;

class CancelledSessionNotification extends Job implements ShouldQueue
{
    use Queueable;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $userId, $cancelledFrom;

    public function __construct($userId, $cancelledFrom)
    {
        $this->userId = $userId;
        $this->cancelledFrom = $cancelledFrom;
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

        //get student device token to send notification
        $user = User::where('id','=', $userId)->first();
        if(!empty($user->device_token)){

            $title  = Config::get('user-constants.APP_NAME');
            $body   = 'Session cancelled by'.$cancelledFrom;
            $customData = array(
                'notification_type' => 'session_cancelled',
            );
            Push::handle($title, $body, $customData, $user);
        }

    }
}
