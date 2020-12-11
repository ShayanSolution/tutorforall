<?php

namespace App\Jobs;
use App\Helpers\Push;
//Models
use App\Models\Session;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;

class DemoReviewSessionNotification extends Job implements ShouldQueue
{
    use Queueable;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $sessionId;
    protected $demoSessionReview;

    public function __construct($sessionId, $demoSessionReview)
    {
        $this->sessionId = $sessionId;
        $this->demoSessionReview = $demoSessionReview;
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
        $user = User::where('id','=',$session->tutor_id)->first();
        $demoReview =$this->demoSessionReview;
        if(!empty($user->device_token)){
            $title  = Config::get('user-constants.APP_NAME');
                $body   = 'You can start session.';
                $customData = array(
                    'notification_type' => 'demo_session_review',
                    'session_id' => (string)$sessionId,
                    'demo_session_review' => $demoReview
                );
            Push::handle($title, $body, $customData, $user);
        }

    }
}
