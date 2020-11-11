<?php

namespace App\Jobs;

use App\Helpers\Push;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class SessionPaymentEmail extends Job implements ShouldQueue
{
    use Queueable;

    public $session_id;
    public $student_id;
    public $tutor_id;

    /**
     * Create a new job instance.
     *
     * @param $session_id
     * @param $student_id
     *
     * @return void
     */
    public function __construct($session_id, $student_id, $tutor_id)
    {
        $this->session_id  = $session_id;
        $this->student_id  = $student_id;
        $this->tutor_id  = $tutor_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $session_id = $this->session_id;
        $student_id = $this->student_id;
        $tutor_id = $this->tutor_id;
        //get student
        $student = User::find($student_id);
        $studentFirstName = $student->firstName;
        $studentLastName = $student->lastName;
        $studentEmail = $student->email;
        //get tutor
        $tutor = User::find($tutor_id);
        $tutorFirstName = $tutor->firstName;
        $tutorLastName = $tutor->lastName;
        //get session
        $findSession = Session::find($session_id);
        $payAmount = $findSession->rate;
        $sessionDuration = $findSession->duration;
        $sessionDateTime = $findSession->started_at;
        //get class & subject
        $class = Programme::find($findSession->programme_id);
        $subject = Subject::find($findSession->subject_id);
        // Email template data
        $Emailsubject = env("SESSION_PAYMENT_MAIL_SUBJECT", "Tootar Billing");
        $data['studentFirstName'] = $studentFirstName;
        $data['studentLastName'] = $studentLastName;
        $data['class'] = $class->name;
        $data['subject'] = $subject->name;
        $data['sessionDuration'] = $sessionDuration;
        $data['sessionDateTime'] = $sessionDateTime;
        $data['payAmount'] = $payAmount;
        $data['tutorFirstName'] = $tutorFirstName;
        $data['tutorLastName'] = $tutorLastName;
        //send mail
        Mail::send('emails.sessionPaymentEmail', $data, function($message) use ($studentEmail, $Emailsubject, $studentFirstName, $studentLastName) {
            $message->to($studentEmail, $studentFirstName." ".$studentLastName)->subject($Emailsubject);
        });
    }
}
