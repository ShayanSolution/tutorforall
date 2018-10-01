<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
            'booking_type' => 'Required'
        ]);
//        dd($studentTableId);
        $studentId = $request->student_id;
        $studentClassId = $request->class_id;
        $studentSubjectId = $request->subject_id;
        $studentLong = $request->longitude;
        $studentLat = $request->latitude;
        $studentIsGroup = $request->is_group;
        $studentGroupCount = $request->group_count;
        $distanceInKmMin = 0;
        $distanceInKmMax = 2;
        
        
        for( $i=0; $i<=3; $i++){
                
            // Query to find Tutors in range(KM)
            //6371 = Kilometers
            //3959 = Miles
            $query = "SELECT users.id, users.firstName, users.role_id, users.latitude, users.longitude, users.device_token, "
                    . "( 6371 "
                    . " * acos ( cos ( radians(". $studentLat .") )"
                    . " * cos( radians( `latitude` ) )"
                    . " * cos( radians( `longitude` ) - radians(".  $studentLong .") )"
                    . " + sin ( radians(". $studentLat .") )"
                    . " * sin( radians( `latitude` ) ) ) )"
                    . " AS `distance`"
                    . " FROM `users`"
                    . " JOIN  `profiles` ON users.id = profiles.user_id"
                    . " WHERE `role_id` = 2 "
                    . " AND `programme_id` = '$studentClassId' "
                    . " AND `subject_id` = '$studentSubjectId' "
                    . "HAVING `distance` < $distanceInKmMax AND `distance` > $distanceInKmMin";

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
