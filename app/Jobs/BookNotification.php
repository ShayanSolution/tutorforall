<?php

namespace App\Jobs;

use App\Helpers\Push;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class BookNotification extends Job implements ShouldQueue
{
    use Queueable;

    protected $student;
    protected $user;
    protected $session;
    protected $rating;
    /**
     * Create a new job instance.
     *
     * @param $student
     * @param $user
     * @param $session
     * @param $rating
     * @return void
     */
    public function __construct($student, $user, $session, $rating)
    {
        $this->student      = $student;
        $this->user         = $user;
        $this->session      = $session;
        $this->rating       = $rating;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user       = $this->user;
        $student    = $this->student;
        $session    = $this->session;
        $rating     = $this->rating;

        $title = Config::get('user-constants.APP_NAME');
        $body = $user->firstName.' '.$user->lastName.' accepted your request';
        //get tutor device token


        $customData = array(
            'notification_type' => 'session_booked',
            'session_id' => $session->sessionId,
            'Tutor_Name' => $this->user->firstName." ".$this->user->lastName,
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
            'Profile_Image' => !empty($user->profileImage) ? URL::to('/images').'/'.$user->profileImage:'',
        );

        Push::handle($title, $body, $customData, $student);
    }
}
