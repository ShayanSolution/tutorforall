<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FindTutorController extends Controller
{
    public function findTutor(Request $request){

        $this->validate($request,[
            'student_id' => 'required',
            'class_id' => 'required',
            'subject_id' => 'required',
            'is_group' => 'required',
            'longitude'  => 'required',
            'latitude' => 'required',
            'group_count' => 'required',
            'hourly_rate' => 'required',
            'is_home' => 'required',
            'call_student' => 'required',
            'one_on_one' => 'required',
            'book_type'=>'required'
        ]);


        $studyFrom = $request->study_from;
        $bookType = $request->book_type;
        $studentId = $request->student_id;
        $studentClassId = $request->class_id;
        $studentSubjectId = $request->subject_id;
        $studentLong = $request->longitude;
        $studentLat = $request->latitude;
        $studentIsGroup = $request->is_group;
        $studentGroupCount = $request->group_count;
        $isHome = $request->is_home;
        $callStudent = $request->call_student;
        $oneOnOne = $request->one_on_one;
        $hourlyRate = $request->hourly_rate;
        $sessionTime = $request->session_time;
        $distanceInKmMin = 0;
        $distanceInKmMax = 2;

        $studentProfile = Profile::where('user_id', $request->student_id)->first();

        $roleId = 2;
        $sessionSentGroup = $studentId.'-'.time();
        $genderMatchingQuery = " AND (profiles.teach_to = $studyFrom OR profiles.teach_to = 0) ";
        for( $i=0; $i<=3; $i++){
                
            // Query to find Tutors in range(KM)
            //6371 = Kilometers
            //3959 = Miles

            $isMentor = $studentProfile->is_deserving == 0 ? "0" : "1";
            $query = "SELECT users.id, users.firstName, users.role_id, users.latitude, users.longitude, users.device_token, profiles.is_mentor, profiles.teach_to, profiles.is_home, profiles.call_student, profiles.is_group, profiles.one_on_one, program_subject.program_id as t_program_id, program_subject.subject_id as t_subject_id,"
            . "( 6371 "
            . " * acos ( cos ( radians(" . $studentLat . ") )"
            . " * cos( radians( `latitude` ) )"
            . " * cos( radians( `longitude` ) - radians(" . $studentLong . ") )"
            . " + sin ( radians(" . $studentLat . ") )"
            . " * sin( radians( `latitude` ) ) ) )"
            . " AS `distance`"
            . " FROM `users`"
            . " JOIN  `profiles` ON users.id = profiles.user_id"
            . " LEFT JOIN  `program_subject` ON users.id = program_subject.user_id"
            . " WHERE `role_id` = '$roleId' "
            . " AND (program_subject.program_id = '$studentClassId' AND program_subject.subject_id = '$studentSubjectId') "

            . " AND profiles.is_mentor = '$isMentor' "
            . " AND (profiles.is_home = '$isHome' OR profiles.call_student = '$callStudent') "
            . " AND (profiles.is_group = '$studentIsGroup' OR profiles.one_on_one = '$oneOnOne') "
            . $genderMatchingQuery
            . "HAVING `distance` < $distanceInKmMax AND `distance` > $distanceInKmMin";


            Log::info($query);

            $tutors = \DB::select($query);
//                dd($tutors);
            foreach($tutors as $tutor){
                $distanceInKms = number_format((float)$tutor->distance, 2, '.', '');
                $approachingTime = $this->getApproachingTimeUsingDistance($distanceInKms);
                $tutorId = $tutor->id;
                $params = [
                    'student_id' => (int)$studentId,
                    'tutor_id' => json_encode([$tutorId]),
                    'subject_id' => (int)$studentSubjectId,
                    'class_id' => (int)$studentClassId,
                    'latitude' => floatval($studentLat),
                    'longitude' => floatval($studentLong),
                    'session_sent_group'=>$sessionSentGroup,
                    'is_group'  => (int)$studentIsGroup,
                    'group_members' => (int)$studentGroupCount,
                    'is_home'=>$isHome,
                    'hourly_rate'=>$hourlyRate,

                    //-----New fields-----/
                    'call_student'=>$callStudent,
                    'one_on_one'=>$oneOnOne,
                    'group_count'=>$studentGroupCount,
                    'book_type'=>$bookType,
                    'session_time'=>$sessionTime,
                    'distance'=>$distanceInKms.' km',
                    'approaching_time'=>$approachingTime
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

    private function getApproachingTimeUsingDistance($distanceInKms){
        $averageBikeSpeedInMetresPerSecond = '8.05556';
        $timeInSeconds = ($distanceInKms * 1000 ) / $averageBikeSpeedInMetresPerSecond;

        $hours = floor($timeInSeconds / 3600);
        $minutes = floor(($timeInSeconds / 60) % 60);
        $seconds = $timeInSeconds % 60;

        return  ( $hours   != 0 ? "$hours hours"     : '').
                ( $minutes != 0 ? "$minutes minutes" : '').
                ( $seconds != 0 ? "$seconds seconds" : '');
    }
}
