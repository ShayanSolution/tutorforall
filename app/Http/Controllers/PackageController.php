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
     *
     * retufn hourly package rate
     */
    public function packageCost(Request $request, ApplyPeakFactor $peakFactorAction, CategoryCost $categoryCostAction,
                                GroupCost $groupCostAction){
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
        $peakFactor = "off";

        $onlineTutorsCount = User::findOnlineTutors($request);

        // Class Subjects cost
        $classSubject = Subject::where('id', $subjectId)->where('programme_id', $classId)->first();
        $classSubjectPrice = $classSubject->price;

        if ($classSubjectPrice) {
            $hourly_rate = $classSubjectPrice;
            // Cost estimation when category selected
            if ($category_id != 0) {
                //@todo $categoryCostAction->execute($category_id);
                $category = Category::where('id', $category_id)->first();
                $calculationsForCategory = ($category->percentage/100) * $hourly_rate;
                $hourly_rate = $calculationsForCategory + $classSubjectPrice;
            }
            //cost Estimations when is group on
            if ($is_group == 1){
                //@todo $groupCostAction->execute($group_count);
                $PercentageCostForMultistudentGroup = PercentageCostForMultistudentGroup::where('number_of_students', $group_count)->first();
                $calculationsForGroup = ($PercentageCostForMultistudentGroup->percentage/100) * $hourly_rate;
                $hourly_rate = $calculationsForGroup + $calculationsForCategory + $classSubjectPrice;

                // Check online tutors
                // @todo remove code of count from here
                $onlineTutors = ProgramSubject::whereHas('onlineTutors')->whereHas('isGroupTutors')->where('program_id', $classId)->where('subject_id', $subjectId)->get();
                $onlineTutorsCount = count($onlineTutors);
            }
            // get peakfactor
            $peakFactorAction->execute($onlineTutorsCount);
            $isPeakFactor = Setting::where('group_name', 'peak-factor')->pluck('value', 'slug');
            if ($isPeakFactor['peak-factor-on-off'] == 1) {
                //@todo make a query by passing $request object to check if peak factor is already active ?
                if ($isPeakFactor['peak-factor-no-of-tutors'] <= $onlineTutorsCount ) {
                    $applyPeakFactor = ($isPeakFactor['peak-factor-percentage']/100) * $hourly_rate;
                    $hourly_rate = $applyPeakFactor + $hourly_rate;
                    $peakFactor = "on";
                    //@todo add record into table for peack factor combinations
                    //@todo add queue which will execute after 1 hour to check if peak factor will remain or remove
                }
            }
            if ($hourly_rate) {
                return response()->json(
                    [
                        'status' => 'success',
                        'hourly_rate' => round($hourly_rate),
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
