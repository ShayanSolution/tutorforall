<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Programme;

class ProgramSubject extends Model
{

    const STATUS_PENDING    = 2;
    const STATUS_ACCEPTED   = 1;
    const STATUS_REJECTED   = 0;

    protected $table = 'program_subject';

    protected $fillable = ['user_id', 'subject_id', 'program_id', 'document_id', 'status', 'verified_by', 'verified_at', 'rejection_reason'];

    public function program(){
        return $this->belongsTo('App\Models\Programme','program_id');
    }
    public function subject(){
        return $this->belongsTo('App\Models\Subject','subject_id');
    }

    public function getStatusAttribute($value){

        if($value == 0)
            $status = 'Rejected';
        else if($value == 1)
            $status = 'Accepted';
        else
            $status = 'Pending';

        return $status;
    }

    public function getSubjectsDetail($userId)
    {
//        $subjects = '';
//        $programSubjects = $this->where('user_id', $userId)->with('program', 'subject')->get();
//        foreach ($programSubjects as $programSubject){
//            $subjects .= $programSubject->program->name.' - '.$programSubject->subject->name.', ';
//        }
//        return $string = rtrim($subjects, ', ');

        // @todo after M-3 release uper code comment and down code uncomment
        $subjects = [];

        $programSubjects = $this->where('user_id', $userId)->where('status', self::STATUS_ACCEPTED)->with('program', 'subject')->get();
        foreach ($programSubjects as $programSubject){
            if ($programSubject->program['status'] != 2) {
                $subjects[$programSubject->program->name][] = $programSubject->subject->name;
            }
        }

        $programSubjectsArray = [];
        foreach ($subjects as $program => $subject){
            $programSubjectsArray[$program] = implode(', ', $subject);
        }

        return $string = $programSubjectsArray;
    }

    public function document(){
        return $this->belongsTo(Document::class);
    }
}
