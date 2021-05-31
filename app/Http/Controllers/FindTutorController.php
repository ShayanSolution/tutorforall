<?php

namespace App\Http\Controllers;

use App\Jobs\SendSessionConnectedNotification;
use App\Models\Profile;
use App\Models\Session;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FindTutorController extends Controller
{
    public function findTutor(Request $request){

        $this->validate($request,[
            'study_from' => 'required',
            'student_id' => 'required',
            'class_id' => 'required',
            'subject_id' => 'required',
            'longitude'  => 'required',
            'latitude' => 'required',
        ]);

        $currentUserId = Auth::user()->id;
        $studyFrom = $request->study_from;
        $studentId = $request->student_id;
        $studentClassId = $request->class_id;
        $studentSubjectId = $request->subject_id;
        $studentLong = $request->longitude;
        $studentLat = $request->latitude;
        $distanceInKmMin = 0;
        $distanceInKmMax = 2;
        $currentTime = Carbon::parse(Carbon::now());
        $roleId = 2;
        $sessionSentGroup = $studentId.'-'.time();
            // Query to find Tutors in range(KM)
            //6371 = Kilometers
            //3959 = Miles
        $genderMatchingQuery = $studyFrom == 0 ? "" : " AND users.gender_id = $studyFrom  AND profiles.teach_to IN (".Auth::user()->gender_id.",0) ";
        for( $i=0; $i<=3; $i++){
        $query = "SELECT DISTINCT users.id,users.firstName, users.role_id, 
            users.latitude, users.longitude, 
            users.device_token, profiles.is_mentor, 
            profiles.teach_to, profiles.is_home,
            program_subject.program_id AS t_program_id,
            program_subject.subject_id AS t_subject_id,
            ROUND(6371 * acos(cos(radians(" . $studentLat . ")) * cos(radians(users.latitude)) * cos(radians(users.longitude) - radians(" . $studentLong . ")) + sin(radians(" . $studentLat . ")) * sin(radians(users.latitude)))) as distance
            FROM `users`
            JOIN `profiles` ON users.id = profiles.user_id
            LEFT JOIN `program_subject` ON users.id = program_subject.user_id
            LEFT JOIN sessions ON sessions.tutor_id = users.id AND sessions.student_id = $currentUserId 
            WHERE `role_id` = '$roleId'
                AND (program_subject.program_id = '$studentClassId' AND program_subject.subject_id = '$studentSubjectId' AND program_subject.status = 1)
                $genderMatchingQuery
                AND (users.is_online = 1)
            HAVING 
            `distance` < $distanceInKmMax AND `distance` >= $distanceInKmMin ";
            Log::info($query);
            $tutors = \DB::select($query);
//            dd($tutors);
            foreach($tutors as $tutor) {
                Log::info('Send Request To tutor => ' . $tutor->id);
                Log::info("send request to tutor ID is: " . $tutor->id);
                $distanceInKms = number_format((float)$tutor->distance, 2, '.', '');
                $tutorId = $tutor->id;
                $params = [
                    'student_id' => (int)$studentId,
                    'tutor_id' => json_encode([$tutorId]),
                    'subject_id' => (int)$studentSubjectId,
                    'class_id' => (int)$studentClassId,
                    'latitude' => floatval($studentLat),
                    'longitude' => floatval($studentLong),
                    'session_sent_group' => $sessionSentGroup,
                    'is_group' => (int)0,
                    'group_members' => (int)0,
                    'is_home' => 0,
                    'hourly_rate' => 0,
                    'original_hourly_rate' => 0,
                    'hourly_rate_past_first_hour' => 0,
                    'call_student' => 0,
                    'one_on_one' => 0,
                    'group_count' => 0,
                    'book_type' => 'basic-version',
                    'session_time' => $currentTime,
                    'distance' => $distanceInKms . ' km',
                    'is_hourly' => 0
                ];
                // dd($params);
                $request->request->add($params);

                $proxy = Request::create('/tutor-notification', 'POST', $request->request->all());
                app()->dispatch($proxy);
            }
            sleep(10);
            $distanceInKmMin = $distanceInKmMin+2;
            $distanceInKmMax = $distanceInKmMax+2;

        }
        sleep(30);
        // After search complete. if online tutors who din't accept request than accept from 1st forcelly
        $tutorWhoGetFirstRequest = Session::where('session_sent_group', $sessionSentGroup)->where('status', 'pending')->orderBy('id', 'asc')->first();
        if ($tutorWhoGetFirstRequest) {
            $sessionRequest = new \Illuminate\Http\Request();
            $sessionRequest->replace([
                'session_id'         => $tutorWhoGetFirstRequest->id,
                'rate'               => "1", //1 for package category
                'session_sent_group' => $tutorWhoGetFirstRequest->session_sent_group
            ]);
            $sessionController = new SessionController();
            $sessionAcceptedForcelly = $sessionController->bookedTutor($sessionRequest);
            $message = "You are connected with a student";
            $job = new SendSessionConnectedNotification($tutorWhoGetFirstRequest->tutor_id, $message);
            dispatch($job);
        }
        Session::where('session_sent_group', $sessionSentGroup)
                ->where('status', 'pending')
                ->update([
                    'status' => 'expired'
                ]);
        return response()->json(
            [
                'status' => 'Complete',
                'message'=> 'Complete Process'
            ]
        );
    }

}
