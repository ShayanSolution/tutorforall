<?php

namespace App\Jobs;
use App\Helpers\Push;
//Models
use App\Models\Session;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;

class StartSessionNotification extends Job implements ShouldQueue
{
    use Queueable;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $sessionId;

    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $sessionId = $this->sessionId;

        $session = Session::find($this->sessionId);

        //get student device token to send notification
        $user = User::where('id','=',$session->student_id)->first();
        if(!empty($user->device_token)){

            $title  = Config::get('user-constants.APP_NAME');
            $body   = 'Your session started.';
            $customData = array(
                'notification_type' => 'session_started',
                'session_id' => (string)$sessionId,
            );
            Push::handle($title, $body, $customData, $user);
        }

    }
}
