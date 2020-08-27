<?php

namespace App\Jobs;

use App\Helpers\Push;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class BookNotification extends Job implements ShouldQueue
{
    use Queueable;

    public $student;
    public $users;
    public $session;
    public $rating;
    public $session_id;
    /**
     * Create a new job instance.
     *
     * @param $student
     * @param $user
     * @param $session
     * @param $rating
     * @return void
     */
    public function __construct($student, $users, $session, $rating, $session_id)
    {
        $this->student      = $student;
        $this->users        = $users;
        $this->session      = $session;
        $this->rating       = $rating;
        $this->session_id   = $session_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user       = json_decode($this->users);
        $student    = json_decode($this->student);
        $session    = $this->session;
        $rating     = $this->rating;

        $title = Config::get('user-constants.APP_NAME');
        $body = $user->firstName.' '.$user->lastName.' accepted your request';
        //get tutor device token
        // get session type
        $sessionDateTime = Carbon::now()->toDateTimeString();
        $dateTime = explode(" ",$sessionDateTime);
        $sessionType = 'now';
        $bookLaterAt  = '';
        if ($session->book_later_at != null){
            $sessionType = 'later';
            $sessionDateTime = $session->book_later_at;
            $bookLaterAt = $session->book_later_at;
            $dateTime = explode(" ",$sessionDateTime);
        }


        $customData = array(
            'notification_type' => 'session_booked',
            'session_id' => $this->session_id,
            'Tutor_Name' => $user->firstName." ".$user->lastName,
            'tutor_phone' => $user->phone,
            'tutor_device_token' => $user->device_token,
            'Class_Name' => $user->p_name,
            'Subject_Name' => $user->s_name,
            'Class_id' => $user->p_id,
            'Subject_id' => $user->s_id,
            'is_group' => $user->is_group,
            'group_members' => $user->s_group_members,
            'is_home' => $user->s_is_home,
            'hourly_rate' => $user->hourly_rate,
            'tutor_is_home' => $user->t_is_home,
            'tutor_lat' => (string)$user->latitude,
            'tutor_long' => (string)$user->longitude,
            'student_lat' => $student->latitude,
            'student_long' => $student->longitude,
            'session_lat' => (string)$session->latitude,
            'session_long' => (string)$session->longitude,
            'session_location' => $session->session_location,
            'session_rating' => number_format((float)$rating->avg('rating'), 1, '.', ''),
            'Profile_Image' => !empty($user->profileImage) ? env('ASSET_BASE_URL').'/images/'.$user->profileImage : '',
            'book_later_at' => $bookLaterAt,
            'dateTime' => $sessionDateTime,
            'date' => $dateTime[0],
            'time' => date("g:i a", strtotime($dateTime[1])),
            'session_type' => $sessionType,
            'tracking_on' => $session->tracking_on
        );

        Push::handle($title, $body, $customData, $student);
    }
}
