<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 11/21/2019
 * Time: 4:35 PM
 */

namespace App\Services;


use App\Jobs\CheckPeakFactor;
use App\Models\PeakFactor;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

class ApplyPeakFactor
{
    public function __construct(){

    }

    public function execute($onlineTutorsCount, $hourlyRate, $request, $peakFactorStatus){
        $isPeakFactor = Setting::where('group_name', 'peak-factor')->pluck('value', 'slug');
        if ($isPeakFactor['peak-factor-on-off'] == 1) {
            //@todo make a query by passing $request object to check if peak factor is already active ?
            if ($isPeakFactor['peak-factor-no-of-tutors'] <= $onlineTutorsCount ) {
                $applyPeakFactor = ($isPeakFactor['peak-factor-percentage']/100) * $hourlyRate;
                $hourlyRate = $applyPeakFactor + $hourlyRate;
                $peakFactorStatus = "on";
                //@todo add record into table for peack factor combinations
                $peakFactor = PeakFactor::create($request);
                //@todo add queue which will execute after 1 hour to check if peak factor will remain or remove
                $NumTutorsPeakFactor = $isPeakFactor['peak-factor-no-of-tutors'];
                $timeDelay = Carbon::now()->addMinutes(60);
                $jobId = Queue::later($timeDelay, (new CheckPeakFactor($peakFactor->id, $NumTutorsPeakFactor)));
                create_queued_job_tracking($jobId, get_class($peakFactor), $peakFactor->id);
            }
        }
        return [$hourlyRate, $peakFactorStatus];
    }
}
