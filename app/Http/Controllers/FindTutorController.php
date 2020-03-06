<?php

namespace App\Http\Controllers;

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
            'is_group' => 'required',
            'longitude'  => 'required',
            'latitude' => 'required',
            'group_count' => 'required',
            'hourly_rate' => 'required',
            'is_home' => 'required',
            'call_student' => 'required',
            'one_on_one' => 'required',
            'book_type'=>'required',
            'experience' => 'required',
            'category_id' => 'required'
        ]);
        //@todo add validation check for studyFrom
        //@todo receive experience info
        //@todo receive category_id / skill level

        $currentUserId = Auth::user()->id;
        $categoryId = $request->category_id;
        $experience = $request->experience;
        $studyFrom = $request->study_from;
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
        $bookType = $request->book_type;
        $sessionTime = $request->session_time;
        $distanceInKmMin = 0;
        $distanceInKmMax = 2;
        $currentTime = Carbon::parse(Carbon::now());
        $bookLaterRestriction = Setting::where('group_name', 'book-later-restrict-hr')->pluck('value', 'slug');
        $bookLaterRestrictionHours = $bookLaterRestriction['book_later_find_tutor_restriction_hours'];

        $studentProfile = Profile::where('user_id', $request->student_id)->first();

        $roleId = 2;
        $sessionSentGroup = $studentId.'-'.time();
        //@todo fix wrong logic here, booking preference will match with tutor gender (users.gender_id)
        //@todo add logic, match student gender (Auth::user()->gender_id) with profile.teach_to
        $genderMatchingQuery = $studyFrom == 0 ? "" : " AND users.gender_id = $studyFrom  AND profiles.teach_to IN (".Auth::user()->gender_id.",0) ";
        for( $i=0; $i<=3; $i++){

            // Query to find Tutors in range(KM)
            //6371 = Kilometers
            //3959 = Miles

            $isMentor = $studentProfile->is_deserving == 0 ? "0" : "1";
//            $query = "SELECT users.id, users.firstName, users.role_id, users.latitude, users.longitude, users.device_token, profiles.is_mentor, profiles.teach_to, profiles.is_home, profiles.call_student, profiles.is_group, profiles.one_on_one, program_subject.program_id as t_program_id, program_subject.subject_id as t_subject_id,"
//            . "( 6371 "
//            . " * acos ( cos ( radians(" . $studentLat . ") )"
//            . " * cos( radians( `latitude` ) )"
//            . " * cos( radians( `longitude` ) - radians(" . $studentLong . ") )"
//            . " + sin ( radians(" . $studentLat . ") )"
//            . " * sin( radians( `latitude` ) ) ) )"
//            . " AS `distance`"
//            . " FROM `users`"
//            . " JOIN  `profiles` ON users.id = profiles.user_id"
//            . " LEFT JOIN  `program_subject` ON users.id = program_subject.user_id"
//            . " WHERE `role_id` = '$roleId' "
//            . " AND (program_subject.program_id = '$studentClassId' AND program_subject.subject_id = '$studentSubjectId') "
//
//            . " AND profiles.is_mentor = '$isMentor' "
//            . " AND (profiles.is_home = '$isHome' OR profiles.call_student = '$callStudent') " //@todo refactor query to include no preference and accroding to booking option for tution place
//            . " AND (profiles.is_group = '$studentIsGroup' OR profiles.one_on_one = '$oneOnOne') "//@todo refactor query to only include group or one_on_one option   (OR because if tutor select any from settings)
//            . " AND (profiles.min_slider_value >= '$hourlyRate' AND profiles.max_slider_value <= '$hourlyRate') "
//            . $genderMatchingQuery
//            . "HAVING `distance` < $distanceInKmMax AND `distance` > $distanceInKmMin";

// down query comment for restrict >=2 rating tutor for same student
//            $query = "SELECT DISTINCT users.id, users.firstName, users.role_id,
//users.latitude, users.longitude,
//users.device_token, profiles.is_mentor,
//profiles.teach_to, profiles.is_home,
//profiles.call_student, profiles.is_group,
//profiles.one_on_one, program_subject.program_id AS t_program_id,
//program_subject.subject_id AS t_subject_id,(6371 * ACOS (COS (RADIANS(" . $studentLat . ")) * COS(RADIANS(`users`.`latitude`)) * COS(RADIANS(`users`.`longitude`) - RADIANS(" . $studentLong . ")) + SIN (RADIANS(" . $studentLat . ")) * SIN(RADIANS(`users`.`latitude`)))) AS `distance`
//,ROUND(IFNULL((SELECT AVG(ratings.rating) from ratings where users.id = ratings.user_id), 1)) as `ratings`
//,@sum_of_students_whom_learned_in_group := (SELECT SUM(DISTINCT group_members) FROM sessions where sessions.tutor_id = users.id AND sessions.`status` = 'ended' AND sessions.is_group = 1) as `sum_of_students_whom_learned_in_group`
//,@sum_of_students_whom_learned_individually := (SELECT COUNT(DISTINCT group_members) FROM sessions where sessions.tutor_id = users.id AND sessions.`status` = 'ended' AND sessions.is_group = 0) as `sum_of_students_whom_learned_individually`
//,IFNULL(ROUND(@sum_of_students_whom_learned_in_group + @sum_of_students_whom_learned_individually),0) AS `experience`
//FROM `users`
//JOIN `profiles` ON users.id = profiles.user_id
//LEFT JOIN `program_subject` ON users.id = program_subject.user_id
//WHERE `role_id` = '$roleId'
//AND (program_subject.program_id = '$studentClassId' AND program_subject.subject_id = '$studentSubjectId')
//AND profiles.is_mentor = '$isMentor'
//AND ((profiles.is_home = '$isHome' AND profiles.call_student = '$callStudent') OR (profiles.is_home = '1' AND profiles.call_student = '1'))
//AND ((profiles.is_group = '$studentIsGroup' AND profiles.one_on_one = '$oneOnOne') OR (profiles.is_group = '1' AND profiles.one_on_one = '1'))
//$genderMatchingQuery
//AND (profiles.min_slider_value <= '$hourlyRate' AND profiles.max_slider_value >= '$hourlyRate')
//AND (users.is_online = 1 OR users.offline_notification = 1)
//HAVING
//`ratings` >= $categoryId AND
//`experience` >= $experience AND
//`distance` < $distanceInKmMax AND `distance` > $distanceInKmMin";

            //down qury close due to already booked not get session and book later check
//$query = "SELECT DISTINCT users.id,users.firstName, users.role_id,
//            users.latitude, users.longitude,
//            users.device_token, profiles.is_mentor,
//            profiles.teach_to, profiles.is_home,
//            profiles.call_student, profiles.is_group,
//            profiles.one_on_one, program_subject.program_id AS t_program_id,
//            program_subject.subject_id AS t_subject_id,(6371 * ACOS (COS (RADIANS(" . $studentLat . ")) * COS(RADIANS(`users`.`latitude`)) * COS(RADIANS(`users`.`longitude`) - RADIANS(" . $studentLong . ")) + SIN (RADIANS(" . $studentLat . ")) * SIN(RADIANS(`users`.`latitude`)))) AS `distance`
//            ,ROUND(IFNULL((SELECT AVG(ratings.rating) FROM ratings WHERE users.id = ratings.user_id), 1)) AS `ratings`
//            ,@sum_of_students_whom_learned_in_group := (SELECT SUM(DISTINCT group_members) FROM sessions WHERE sessions.tutor_id = users.id AND sessions.`status` = 'ended' AND sessions.is_group = 1) AS `sum_of_students_whom_learned_in_group`
//            ,@sum_of_students_whom_learned_individually := (SELECT COUNT(DISTINCT group_members) FROM sessions WHERE sessions.tutor_id = users.id AND sessions.`status` = 'ended' AND sessions.is_group = 0) AS `sum_of_students_whom_learned_individually`
//            ,IFNULL(ROUND(@sum_of_students_whom_learned_in_group + @sum_of_students_whom_learned_individually),0) AS `experience`
//            ,@rating_received := (SELECT ratings.rating FROM sessions JOIN ratings ON sessions.id = ratings.session_id WHERE sessions.student_id = $currentUserId AND sessions.tutor_id = users.id AND sessions.status = 'ended' ORDER BY ratings.rating ASC LIMIT 1)AS `rating_received`
//            FROM `users`
//            JOIN `profiles` ON users.id = profiles.user_id
//            LEFT JOIN `program_subject` ON users.id = program_subject.user_id
//            LEFT JOIN sessions ON sessions.tutor_id = users.id AND sessions.student_id = $currentUserId
//            WHERE `role_id` = '$roleId'
//                AND (program_subject.program_id = '$studentClassId' AND program_subject.subject_id = '$studentSubjectId' AND program_subject.status = 1)
//                AND profiles.is_mentor = '$isMentor'
//                AND ((profiles.is_home = '$isHome' AND profiles.call_student = '$callStudent') OR (profiles.is_home = '1' AND profiles.call_student = '1'))
//                AND ((profiles.is_group = '$studentIsGroup' AND profiles.one_on_one = '$oneOnOne') OR (profiles.is_group = '1' AND profiles.one_on_one = '1'))
//                $genderMatchingQuery
//                AND (profiles.min_slider_value <= '$hourlyRate' AND profiles.max_slider_value >= '$hourlyRate')
//                AND (users.is_online = 1)
//            HAVING
//            `ratings` >= 0 AND
//            `experience` >= 0 AND
//            `distance` < $distanceInKmMax AND `distance` > $distanceInKmMin AND (`rating_received` IS NULL OR `rating_received` > 2)";
        //\DB::select("SET @session_type:='now'");
        $query = "SELECT DISTINCT users.id,users.firstName, users.role_id, 
            users.latitude, users.longitude, 
            users.device_token, profiles.is_mentor, 
            profiles.teach_to, profiles.is_home, 
            profiles.call_student, profiles.is_group, 
            profiles.one_on_one, program_subject.program_id AS t_program_id,
            program_subject.subject_id AS t_subject_id,(6371 * ACOS (COS (RADIANS(" . $studentLat . ")) * COS(RADIANS(@tutor_location_latitude)) * COS(RADIANS(@tutor_location_longitude) - RADIANS(" . $studentLong . ")) + SIN (RADIANS(" . $studentLat . ")) * SIN(RADIANS(@tutor_location_latitude)))) AS `distance`
            ,ROUND(IFNULL((SELECT AVG(ratings.rating) FROM ratings WHERE users.id = ratings.user_id), 1)) AS `ratings`
            ,@sum_of_students_whom_learned_in_group := (SELECT SUM(DISTINCT group_members) FROM sessions WHERE sessions.tutor_id = users.id AND sessions.`status` = 'ended' AND sessions.is_group = 1) AS `sum_of_students_whom_learned_in_group`
            ,@sum_of_students_whom_learned_individually := (SELECT COUNT(DISTINCT group_members) FROM sessions WHERE sessions.tutor_id = users.id AND sessions.`status` = 'ended' AND sessions.is_group = 0) AS `sum_of_students_whom_learned_individually`
            ,IFNULL(ROUND(@sum_of_students_whom_learned_in_group + @sum_of_students_whom_learned_individually),0) AS `experience`
            ,@rating_received := (SELECT ratings.rating FROM sessions JOIN ratings ON sessions.id = ratings.session_id WHERE sessions.student_id = $currentUserId AND sessions.tutor_id = users.id AND sessions.status = 'ended' ORDER BY ratings.rating ASC LIMIT 1)AS `rating_received`
            ,@book_now_session_status := (select `status` from sessions where tutor_id = users.id and book_later_at is null and `STATUS` = 'booked'  order by id desc limit 1) AS `book_now_session_status`
            ,@hours_in_session_start := (select abs(TIMESTAMPDIFF(HOUR, `book_later_at`, '$currentTime')) from sessions where tutor_id = users.id AND book_later_at IS NOT NULL ORDER BY id  DESC LIMIT 1)AS `hours_in_session_start`
            FROM `users`
            JOIN `profiles` ON users.id = profiles.user_id
            LEFT JOIN `program_subject` ON users.id = program_subject.user_id
            LEFT JOIN sessions ON sessions.tutor_id = users.id AND sessions.student_id = $currentUserId 
            WHERE `role_id` = '$roleId'
                AND (program_subject.program_id = '$studentClassId' AND program_subject.subject_id = '$studentSubjectId' AND program_subject.status = 1)
                AND profiles.is_mentor = '$isMentor'
                AND ((profiles.is_home = '$isHome' AND profiles.call_student = '$callStudent') OR (profiles.is_home = '1' AND profiles.call_student = '1'))
                AND ((profiles.is_group = '$studentIsGroup' AND profiles.one_on_one = '$oneOnOne') OR (profiles.is_group = '1' AND profiles.one_on_one = '1'))
                $genderMatchingQuery
                AND (profiles.min_slider_value <= '$hourlyRate' AND profiles.max_slider_value >= '$hourlyRate')
                AND (users.is_online = 1)
            HAVING 
            `ratings` >= 0
            AND `experience` >= 0
            AND (`book_now_session_status` is null OR `book_now_session_status` not in ('booked','started'))
            AND (`hours_in_session_start` is null OR `hours_in_session_start` > '$bookLaterRestrictionHours')
            AND `distance` < $distanceInKmMax AND `distance` > $distanceInKmMin AND (`rating_received` IS NULL OR `rating_received` > 2)";

            Log::info($query);

            $tutors = \DB::select($query);
//            dd($tutors);
            foreach($tutors as $tutor){
                Log::info("send request to tutor ID is: ".$tutor->id);
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
        sleep(60);
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

        return  ( $hours   != 0 ? "$hours h "     : '').
                ( $minutes > 1 ? "$minutes mins" : '1 min')
            ;
    }
}
