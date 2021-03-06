<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PercentageCostForMultistudentGroup;
use App\Models\Profile;
use App\Models\Programme;
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

        $goToTutorFirstHourFlatDiscountApplied = false;
        $discount = 0;

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
        $callTutor = $request['is_home'];
        $callStudent = $request['call_student'];
        $peakFactor = "off";
        $selected_rate = isset($request['selected_rate']) ? $request['selected_rate'] : 0;
        //Get online tutors
        $onlineTutorsCount = User::findOnlineTutors($request, $hourlyRate=0);

        // Class Subjects cost
        $classSubject = Subject::where('id', $subjectId)->where('programme_id', $classId)->first();
        $classSubjectPrice = $classSubject->price;

//        if ($classSubjectPrice) {
        if ($selected_rate !== "0") {
//            $hourlyRate = $classSubjectPrice;
            $hourlyRate = $selected_rate;
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
            //save hourly rate before applying discount
            $originalHourlyRate = $hourlyRate;
            // discount on go to tutor
            if ($callTutor == 0 && $callStudent == 1) {
                $isDiscount = Setting::where('group_name', 'discount')->pluck('value', 'slug');
                if ($isDiscount['percent-discount-on-go-to-tutor-status'] == 1) {
                    $discountPercentage = $isDiscount['percent-discount-on-go-to-tutor'];
                    $discount = ($discountPercentage/100) * $hourlyRate;
                    $hourlyRate = $hourlyRate - $discount;
                    $goToTutorFirstHourFlatDiscountApplied = true;
                }
            }
            //Get online tutors after checking tutor slider range
            $onlineTutorsCount = User::findOnlineTutors($request, $hourlyRate);
            // next hour discount on subject price
            $hourlyRatePastFirstHour = hourly_rate_past_first_hour($goToTutorFirstHourFlatDiscountApplied ? $hourlyRate + $discount : $hourlyRate);
            $hourlyRatePastFirstHourPerMinuteCal = $hourlyRatePastFirstHour/60;
            $hourlyRatePastFirstHourPerMinute = round($hourlyRatePastFirstHourPerMinuteCal, 2);
            if ($hourlyRate) {
                return response()->json(
                    [
                        'status' => 'success',
                        'original_hourly_rate' => round($originalHourlyRate),
                        'hourly_rate' => round($hourlyRate),
                        'online_tutors' => $onlineTutorsCount,
                        'peak_factor' => $peakFactor,
                        'hourly_rate_past_first_hour' => round($hourlyRatePastFirstHourPerMinute)
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
