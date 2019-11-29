<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PercentageCostForMultistudentGroup;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\ProgramSubject;
use App\Models\User;
use App\Package;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Services\ApplyPeakFactor;
use App\Services\CostCalculation\CategoryCost;
use App\Services\CostCalculation\GroupCost;

class PackageController extends Controller
{
    /**
     * @method packageCost
     * -------------------
     * @param Request $request
     * @param ApplyPeakFactor $peakFactorAction
     * @param CategoryCost $categoryCostAction
     * @param GroupCost $groupCostAction
     *
     * @return mixed hourly package rate
     */
    public function packageCost(Request $request, ApplyPeakFactor $peakFactorAction, CategoryCost $categoryCostAction, GroupCost $groupCostAction){
        $this->validate($request,[
            'class_id' => 'required',
            'subject_id'=> 'required',
        ]);

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
        $categoryId = $request['category_id'];
        $isGroup = $request['is_group'];
        $groupCount = $request['group_count'];
        $peakFactor = "off";
        //Get online tutors
        $onlineTutorsCount = User::findOnlineTutors($request);

        // Class Subjects cost
        $classSubject = Subject::where('id', $subjectId)->where('programme_id', $classId)->first();
        $classSubjectPrice = $classSubject->price;

        if ($classSubjectPrice) {
            $hourlyRate = $classSubjectPrice;
            // Cost estimation when category selected
            if ($categoryId != 0) {
                $categoryCostRate = $categoryCostAction->execute($categoryId, $hourlyRate);
                $hourlyRate = $hourlyRate + $categoryCostRate;
            }
            //cost Estimations when is group on
            if ($isGroup == 1){
                $hourlyRate = $groupCostAction->execute($groupCount, $hourlyRate, $categoryCostAction);
            }
            // get peakfactor
            list($hourlyRate, $peakFactor) = $peakFactorAction->execute($onlineTutorsCount, $hourlyRate, $request, $peakFactor);
            if ($hourlyRate) {
                return response()->json(
                    [
                        'status' => 'success',
                        'hourly_rate' => round($hourlyRate),
                        'online_tutors' => $onlineTutorsCount,
                        'peakFactor' => $peakFactor
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
