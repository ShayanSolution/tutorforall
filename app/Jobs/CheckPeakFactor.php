<?php

namespace App\Jobs;

use App\Models\PeakFactor;
use App\Models\User;

class CheckPeakFactor extends Job
{
    public $peakFactorId;
    public $numTutorsPeakFactor;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $NumTutorsPeakFactor)
    {
        $this->peakFactorId = $id;
        $this->numTutorsPeakFactor = $NumTutorsPeakFactor;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $peakfactor = PeakFactor::find($this->peakFactorId);
        $onlineTutorsCount = User::findOnlineTutors($peakfactor);
        if ($onlineTutorsCount > $this->numTutorsPeakFactor) {
            $peakfactor->delete();
            delete_queued_job_tracking(['model_id' => $peakfactor->id]);
        } else {
            // add queue again after 60min will run
            $timeDelay = Carbon::now()->addMinutes(60);
            $jobId = Queue::later($timeDelay, (new CheckPeakFactor($this->peakFactorId, $this->numTutorsPeakFactor)));
            create_queued_job_tracking($jobId, get_class($peakfactor), $this->peakFactorId);
            dd("queue added");
        }
    }
}
