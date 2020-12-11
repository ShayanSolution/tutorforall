<?php

namespace App\Jobs;
use App\Helpers\Push;
//Models
use App\Models\Session;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;

class DemoSessionNotification extends Job implements ShouldQueue
{
    use Queueable;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $sessionId;
    protected $demoSessionStatus;

    public function __construct($sessionId, $demoSessionStatus)
    {
        $this->sessionId = $sessionId;
        $this->demoSessionStatus = $demoSessionStatus;
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
        $demoStatus =$this->demoSessionStatus;
        if(!empty($user->device_token)){
            $title  = Config::get('user-constants.APP_NAME');
            if ($demoStatus == 'started'){
                $body   = 'Your demo session is started.';
                $customData = array(
                    'notification_type' => 'demo_session_started',
                    'session_id' => (string)$sessionId,
                );
            } else {
                $body   = 'Your demo session is ended.';
                $customData = array(
                    'notification_type' => 'demo_session_ended',
                    'session_id' => (string)$sessionId,
                );
            }
            Push::handle($title, $body, $customData, $user);
        }

    }
}
