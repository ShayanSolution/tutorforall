<?php

namespace App\Jobs;
use App\Helpers\Push;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;

class PeakFactorNotification extends Job implements ShouldQueue
{
    use Queueable;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $tutorId, $className, $subjectName;

    public function __construct($tutorId, $className, $subjectName)
    {
        $this->tutorId = $tutorId;
        $this->className = $className;
        $this->subjectName = $subjectName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $userId = $this->tutorId;
        $className = $this->className;
        $subjectName = $this->subjectName;

        //get student device token to send notification
        $user = User::where('id','=',$userId)->first();
        if(!empty($user->device_token)){

            $title  = Config::get('user-constants.APP_NAME');
            $body   = 'Peak factor is applied for '. $subjectName. ' of '. $className. ' and will be effective for next two hours.';
            $customData = array(
                'notification_type' => 'session_started',
            );
            Push::handle($title, $body, $customData, $user);
        }
    }
}
