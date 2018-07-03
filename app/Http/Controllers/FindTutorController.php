<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FindTutor;
use App\Notify;

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
        ]);
        
        $findTutor = FindTutor::create([
            
            'student_id' => $request->student_id,
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'is_group' => $request->is_group,
            'longitude'  => $request->longitude,
            'latitude' => $request->latitude,
//            'status' => $status,
            
        ]);
        
        $studentTableId = \DB::getPdo()->lastInsertId();
//        dd($studentTableId);
        $studentId = $request->student_id;
        $studentClassId = $request->class_id;
        $studentSubjectId = $request->subject_id;
        $studentLat = $request->longitude;
        $studentLong = $request->latitude;
        $distanceInKmMin = 0;
        $distanceInKmMax = 2;
        
        
        for( $i=0; $i<=3; $i++){
            
            // Check if tutor has accepted so break loop
            $findTutorStatus = \DB::table('find_tutors')->where('id', $studentTableId)->first();

            if ($findTutorStatus->status == 0){
                
                // Query to find Tutors in range(KM)
                //6371 = Kilometers
                //3959 = Miles
                $query = "SELECT id, firstName, role_id, latitude, longitude, device_token, "
                        . "( 6371 "
                        . " * acos ( cos ( radians(". $studentLong .") )"
                        . " * cos( radians( `latitude` ) )"
                        . " * cos( radians( `longitude` ) - radians(".  $studentLat .") )"
                        . " + sin ( radians(". $studentLong .") )"
                        . " * sin( radians( `latitude` ) ) ) )"
                        . " AS `distance`"
                        . " FROM `Users`"
                        . " WHERE `role_id` = 2 "
                        . "HAVING `distance` < $distanceInKmMax AND `distance` > $distanceInKmMin";
                
                $tutors = \DB::select($query);
                dd($tutors);
                foreach($tutors as $tutor){
                    $tutorId = $tutor->id;
                    $notify = new Notify();
                    $message = "I am Tutor";
                    $postData = [
                        "action" => "Booked"
                    ];

                    $notify->sendNotification($tutorId, "TutorForAll", $message, $postData);
                } 
            } else {
                    dd("Seesion is already booked");
            }
        sleep(10);
        $distanceInKmMin = $distanceInKmMin+2;
        $distanceInKmMax = $distanceInKmMax+2;
        }
        return response()->json(
            [
                'status' => 'success',
            ]
        );
    }
}
