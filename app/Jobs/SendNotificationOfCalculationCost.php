<?php

namespace App\Jobs;

use App\Helpers\Push;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;

class SendNotificationOfCalculationCost extends Job implements ShouldQueue
{
    use Queueable;

    public $totalCostAccordingToHours;
    public $session_id;
    public $user;
    public $tutorType;

    /**
     * Create a new job instance.
     * @param $totalCostAccordingToHours
     * @param $session_id
     * @param $user
     * @param $tutorType
     * @return void
     */
    public function __construct($totalCostAccordingToHours, $session_id, $user, $tutorType)
    {
        $this->totalCostAccordingToHours    = $totalCostAccordingToHours;
        $this->session_id                   = $session_id;
        $this->user                         = $user;
        $this->tutorType                   = $tutorType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $session_id = $this->session_id;
        $totalCostAccordingToHours = $this->totalCostAccordingToHours;

        $title  =   Config::get('user-constants.APP_NAME');
        $body   =   'Your total cost is Rs ' . $totalCostAccordingToHours;
        $customData = array(
            'notification_type' => 'session_ended',
            'session_id' => $session_id
        );

        if($this->tutorType == 'commercial')
            $customData['session_cost'] = $totalCostAccordingToHours;

        $user = json_decode($this->user);

        Push::handle($title, $body, $customData, $user);
    }
}
