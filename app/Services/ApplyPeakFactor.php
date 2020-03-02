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
use App\Models\User;
use App\Models\Subject;
use App\Jobs\PeakFactorNotification;

class ApplyPeakFactor
{
    public function __construct(){

    }

    public function execute($onlineTutorsCount, $hourlyRate, $request, $peakFactorStatus){
        $isPeakFactor = Setting::where('group_name', 'peak-factor')->pluck('value', 'slug');
        if ($isPeakFactor['peak-factor-on-off'] == 1) {
            //@todo make a query by passing $request object to check if peak factor is already active ?
            $alreadyPeakFactor = PeakFactor::where('class_id', $request['class_id'])->
                                                where('subject_id', $request['subject_id'])->
                                                where('category_id', $request['category_id'])->
                                                where('is_group', $request['is_group'])
                                                ->first();

            if ($onlineTutorsCount <= $isPeakFactor['peak-factor-no-of-tutors']) {
                // Check if peakfactor send tutors notification
                $tutors = User::findTutorsRelatedClassSubject($request);
                $classSubjectName = Subject::where('id', $request['subject_id'])->where('programme_id', $request['class_id'])->with('programme')->first();
                if ($classSubjectName){
                    $className = $classSubjectName->programme->name;
                    $subjectName = $classSubjectName->name;
                    // Send tutors notification that peakfactor applied
                    foreach ($tutors as $tutor){
                        $tutorId = $tutor->id;
                        $job = new PeakFactorNotification($tutorId, $className, $subjectName);
                        dispatch($job);
                    }
                }
                $applyPeakFactor = ($isPeakFactor['peak-factor-percentage']/100) * $hourlyRate;
                $hourlyRate = $applyPeakFactor + $hourlyRate;
                $peakFactorStatus = "on";
                //add record into table for peack factor combinations
                if ($alreadyPeakFactor == null) {
                    $peakFactor = PeakFactor::create($request);
                    //add queue which will execute after 1 hour to check if peak factor will remain or remove
                    $NumTutorsPeakFactor = $isPeakFactor['peak-factor-no-of-tutors'];
                    $timeDelay = Carbon::now()->addMinutes(config('services.check_peak_factor_delay'));
                    $jobId = Queue::later($timeDelay, (new CheckPeakFactor($peakFactor->id, $NumTutorsPeakFactor)));
                    create_queued_job_tracking($jobId, get_class($peakFactor), $peakFactor->id);
                }
            }
        }
        return [$hourlyRate, $peakFactorStatus];
    }
}
