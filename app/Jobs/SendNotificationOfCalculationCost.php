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
    public $totalCost;
    public $walletBalance;
    public $session_id;
    public $user;
    public $tutorType;
    public $paymentable;

    /**
     * Create a new job instance.
     * @param $totalCostAccordingToHours
     * @param $session_id
     * @param $user
     * @param $tutorType
     * @return void
     */
    public function __construct($totalCostAccordingToHours, $totalCost, $walletBalance, $session_id, $paymentable, $user, $tutorType)
    {
        $this->totalCostAccordingToHours    = $totalCostAccordingToHours;
        $this->totalCost    = $totalCost;
        $this->walletBalance    = $walletBalance;
        $this->session_id                   = $session_id;
        $this->user                         = $user;
        $this->tutorType                   = $tutorType;
        $this->paymentable                   = $paymentable;
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
        $totalCost = $this->totalCost;
        $walletBalance = $this->walletBalance;
        $paymentable = $this->paymentable;

        $title  =   Config::get('user-constants.APP_NAME');
        $body   =   'Your total cost is Rs ' . $totalCost;
        $customData = array(
            'notification_type' => 'session_ended',
            'session_id' => $session_id
        );

        if($this->tutorType == 'commercial')
            $customData['session_cost'] = $totalCost;
            $customData['session_cost_actual'] = $totalCostAccordingToHours;
            $customData['wallet_balance'] = $walletBalance;
            $customData['payment_able'] = $paymentable;

        $user = json_decode($this->user);

        Push::handle($title, $body, $customData, $user);
    }
}
