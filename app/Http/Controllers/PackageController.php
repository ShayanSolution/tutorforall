<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PercentageCostForMultistudentGroup;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\ProgramSubject;
use App\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class PackageController extends Controller
{
    /**
     *
     * retufn hourly package rate
     */
    public function packageCost(Request $request){
        $this->validate($request,[
            'class_id' => 'required',
            'subject_id'=> 'required',
        ]);

        $userId = Auth::user()->id;
        $studentProfile = Profile::where('user_id', Auth::user()->id)->first();
        if($studentProfile->is_deserving == 1){
            return response()->json(
                [
                    'status' => 'success',
                    'hourly_rate' => 0
                ]
            );
        }

        $request = $request->all();
        $classId = $request['class_id'];
        $subjectId = $request['subject_id'];
        $category_id = $request['category_id'];
        $is_group = $request['is_group'];
        $group_count = $request['group_count'];
        $calculationsForGroup = 0;
        $calculationsForCategory = 0;

        //Online check need
        $onlineTutors = ProgramSubject::whereHas('onlineTutors')->where('program_id', $classId)->where('subject_id', $subjectId)->get();
        $onlineTutorsCount = count($onlineTutors);
        // Class Subjects cost
        $classSubject = Subject::where('id', $subjectId)->where('programme_id', $classId)->first();
        $classSubjectPrice = $classSubject->price;

        if ($classSubjectPrice) {
            //cost Estimations when is group on
            if ($is_group == 1){
                $PercentageCostForMultistudentGroup = PercentageCostForMultistudentGroup::where('number_of_students', $group_count)->first();
                $calculationsForGroup = ($PercentageCostForMultistudentGroup->percentage/100) * $classSubjectPrice;
            }
            // Cost estimation when category selected
            if ($category_id != 0) {
                $category = Category::where('id', $category_id)->first();
                $calculationsForCategory = ($category->percentage/100) * $classSubjectPrice;
            }
            $hourly_rate = $calculationsForGroup + $calculationsForCategory + $classSubjectPrice;
            if ($hourly_rate) {
                return response()->json(
                    [
                        'status' => 'success',
                        'hourly_rate' => $hourly_rate,
                        'online_tutors' => $onlineTutorsCount,
                    ]
                );
            }
        } else {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find user hourly rate'
                ], 422
            );
        }
    }

    public function getPackageCategories(){
        // Experience Slider
        $experienceSlider = Setting::all();
        foreach ($experienceSlider as $data){
            if ($data->slug == "experience-slider-min-value"){
                $minSlider = $data->value;
            }
            if ($data->slug == "experience-slider-max-value"){
                $maxSlider = $data->value;
            }
            if ($data->slug == "experience-slider-spread"){
                $intervalSlider = $data->value;
            }

        }
        // Number of groups
        $minStudentGroups = PercentageCostForMultistudentGroup::min('number_of_students');
        $maxStudentGroups = PercentageCostForMultistudentGroup::max('number_of_students');
        // Categories
        $categories = Category::where('status',1)->get();
        if($categories){
            $package_categories = [];
            foreach ($categories as $category){
                $package_categories[] = [
                    'id'=>$category->id,
                    'name'=>$category->name,
                ];
            }
            return response()->json(
                [
                    'status' => 'success',
                    'data' => $package_categories,
                    'min_experience_slider' => $minSlider,
                    'max_experience_slider' => $maxSlider,
                    'interval_experience_slider' => $intervalSlider,
                    'min_student_groups' => $minStudentGroups,
                    'max_student_groups' => $maxStudentGroups
                ]
            );

        }else{

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find user session'
                ], 422
            );
        }

    }
}
