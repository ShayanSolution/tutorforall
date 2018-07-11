<?php

namespace App\Jobs;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Log;

class SendPushNotification extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $tutorsIds;

    public function __construct($tutors_ids)
    {
        $this->tutorsIds = $tutors_ids;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach($this->tutorsIds as $tutorId){
            Log::info('Tutor ID: '. $tutorId);
        }

    }
}
