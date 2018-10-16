<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\FindTutor;
use App\Notify;
use Illuminate\Support\Facades\Route;

class FindTutorController extends Controller
{
    public function findTutor(Request $request){
//        dd($request->toArray());
        $this->validate($request,[
            'student_id' => 'Required',
            'class_id' => 'Required',
            'subject_id' => 'Required',
            'is_group' => 'Required',
            'longitude'  => 'Required',
            'latitude' => 'Required',
            'group_count' => 'Required',
            'book_type' => 'Required',
            'hourly_rate' => 'required',
            'is_home' => 'required',
            'call_student' => 'required',
            'one_on_one' => 'required'
        ]);
//        dd($studentTableId);
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
        $distanceInKmMin = 0;
        $distanceInKmMax = 2;

        $studentProfile = Profile::where('user_id', $request->student_id)->first();

        $roleId = 2;
        
        for( $i=0; $i<=3; $i++){
                
            // Query to find Tutors in range(KM)
            //6371 = Kilometers
            //3959 = Miles
            if($studentProfile->is_deserving == 0) {
                    $query = "SELECT users.id, users.firstName, users.role_id, users.latitude, users.longitude, users.device_token, profiles.is_mentor, profiles.is_home, profiles.call_student, profiles.is_group, profiles.one_on_one, program_subject.program_id as t_program_id, program_subject.subject_id as t_subject_id,"
                    . "( 6371 "
                    . " * acos ( cos ( radians(" . $studentLat . ") )"
                    . " * cos( radians( `latitude` ) )"
                    . " * cos( radians( `longitude` ) - radians(" . $studentLong . ") )"
                    . " + sin ( radians(" . $studentLat . ") )"
                    . " * sin( radians( `latitude` ) ) ) )"
                    . " AS `distance`"
                    . " FROM `users`"
                    . " JOIN  `profiles` ON users.id = profiles.user_id"
                    . " JOIN  `program_subject` ON users.id = program_subject.user_id"
                    . " WHERE `role_id` = '$roleId' "
                    . " AND t_program_id = '$studentClassId' "
                    . " AND t_subject_id = '$studentSubjectId' "
                    . " AND profiles.is_mentor = '0' "
                    . " AND (profiles.is_home = '$isHome' OR profiles.call_student = '$callStudent') "
                    . " AND (profiles.is_group = '$studentIsGroup' OR profiles.one_on_one = '$oneOnOne') "
                    . "HAVING `distance` < $distanceInKmMax AND `distance` > $distanceInKmMin";
            }else{
                $query = "SELECT users.id, users.firstName, users.role_id, users.latitude, users.longitude, users.device_token, profiles.is_mentor, profiles.is_home, profiles.call_student, profiles.is_group, profiles.one_on_one, program_subject.program_id as t_program_id, program_subject.subject_id as t_subject_id,"
                    . "( 6371 "
                    . " * acos ( cos ( radians(" . $studentLat . ") )"
                    . " * cos( radians( `latitude` ) )"
                    . " * cos( radians( `longitude` ) - radians(" . $studentLong . ") )"
                    . " + sin ( radians(" . $studentLat . ") )"
                    . " * sin( radians( `latitude` ) ) ) )"
                    . " AS `distance`"
                    . " FROM `users`"
                    . " JOIN  `profiles` ON users.id = profiles.user_id"
                    . " JOIN  `program_subject` ON users.id = program_subject.user_id"
                    . " WHERE `role_id` = '$roleId' "
                    . " AND t_program_id = '$studentClassId' "
                    . " AND t_subject_id = '$studentSubjectId' "
                    . " AND profiles.is_mentor = '1' "
                    . " AND (profiles.is_home = '$isHome' OR profiles.call_student = '$callStudent') "
                    . " AND (profiles.is_group = '$studentIsGroup' OR profiles.one_on_one = '$oneOnOne') "
                    . "HAVING `distance` < $distanceInKmMax AND `distance` > $distanceInKmMin";
            }

            Log::info($query);

            $tutors = \DB::select($query);
//                dd($tutors);
            foreach($tutors as $tutor){
                $tutorId = $tutor->id;
                $params = [
                    'student_id' => (int)$studentId,
                    'tutor_id' => json_encode([$tutorId]),
                    'subject_id' => (int)$studentSubjectId,
                    'class_id' => (int)$studentClassId,
                    'latitude' => floatval($studentLat),
                    'longitude' => floatval($studentLong),
                    'is_group'  => (int)$studentIsGroup,
                    'group_members' => (int)$studentGroupCount
                ];
//                    dd($params);
                $request->request->add($params);

                $proxy = Request::create('/tutor-notification', 'POST', $request->request->all());

                app()->dispatch($proxy);
            }
            sleep(10);
            $distanceInKmMin = $distanceInKmMin+2;
            $distanceInKmMax = $distanceInKmMax+2;
        }
        return response()->json(
            [
                'status' => 'Complete',
                'message'=> 'Complete Process'
            ]
        );
    }
}
