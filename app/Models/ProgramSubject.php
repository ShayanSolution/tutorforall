<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Programme;

class ProgramSubject extends Model
{
    protected $table = 'program_subject';

    public function program(){
        return $this->belongsTo('App\Models\Programme','program_id');
    }
    public function subject(){
        return $this->belongsTo('App\Models\Subject','subject_id');
    }

    public function getSubjectsDetail($userId)
    {
        $subjects = '';
        $programSubjects = $this->where('user_id', $userId)->with('program', 'subject')->get();
        foreach ($programSubjects as $programSubject){
            $subjects .= $programSubject->program->name.' - '.$programSubject->subject->name.', ';
        }
        return $string = rtrim($subjects, ', ');
    }
}
