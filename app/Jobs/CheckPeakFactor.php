<?php

namespace App\Jobs;

use App\Models\PeakFactor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Queue;



class CheckPeakFactor extends Job implements ShouldQueue
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
        $onlineTutorsCount = User::findOnlineTutors($peakfactor, $hourlyRate=0);
        if ($onlineTutorsCount > $this->numTutorsPeakFactor) {
            $peakfactor->delete();
            delete_queued_job_tracking(['model_id' => $peakfactor->id]);
        } else {
            // add queue again after 60min will run
            $timeDelay = Carbon::now()->addMinutes(config('services.check_peak_factor_delay'));
            $jobId = Queue::later($timeDelay, (new CheckPeakFactor($this->peakFactorId, $this->numTutorsPeakFactor)));
            create_queued_job_tracking($jobId, get_class($peakfactor), $this->peakFactorId);
        }
    }
}
