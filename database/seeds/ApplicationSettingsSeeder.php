<?php

use Illuminate\Database\Seeder;

class ApplicationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $entries = [
//            [
//                "slug"=> "percent-discount-on-go-to-tutor",
//                "value"=> "25",
//                "label"=> "Discount on go to tutor",
//                "group_name"=> "discount"
//            ],
//            [
//                "slug"=> "experience-slider-min-value",
//                "value"=> "1",
//                "label"=> "Experience Slider Min Value",
//                "group_name"=> "experience-slider"
//            ],
//            [
//                "slug"=> "experience-slider-max-value",
//                "value"=> "100",
//                "label"=> "Experience Slider Max Value",
//                "group_name"=> "experience-slider"
//            ],
//            [
//                "slug"=> "experience-slider-spread",
//                "value"=> "10",
//                "label"=> "Experience Slider Spread",
//                "group_name"=> "experience-slider"
//            ],
//            [
//                "slug"=> "default-percentage-for-group-students",
//                "value"=> "10",
//                "label"=> "Default percentage for Group Students",
//                "group_name"=> "default-percentage-group-students"
//            ],
//            [
//                "slug"=> "peak-factor-percentage",
//                "value"=> "12",
//                "label"=> "Percentage of Peak Factor",
//                "group_name"=> "peak-factor"
//            ],
//            [
//                "slug"=> "peak-factor-on-off",
//                "value"=> "0",
//                "label"=> "Peak Factor Active/Inactive",
//                "group_name"=> "peak-factor"
//            ],
//            [
//                "slug"=> "peak-factor-no-of-tutors",
//                "value"=> "2",
//                "label"=> "Peak Factor (Number of Tutors)",
//                "group_name"=> "peak-factor"
//            ],
//            [
//                "slug"=> "tutor-setting-slider-min-value",
//                "value"=> "0",
//                "label"=> "Tutor Setting Slider Min Value",
//                "group_name"=> "tutor-setting-slider"
//            ],
//            [
//                "slug"=> "tutor-setting-slider-max-value",
//                "value"=> "2000",
//                "label"=> "Tutor Setting Slider Max Value",
//                "group_name"=> "tutor-setting-slider"
//            ],
//            [
//                "slug"=> "percent-discount-on-go-to-tutor-status",
//                "value"=> "0",
//                "label"=> "Discount on go to tutor status",
//                "group_name"=> "discount"
//            ],
//            [
//                "slug"=> "book_later_find_tutor_restriction_hours",
//                "value"=> "0",
//                "label"=> "On book later find tutor restriction hours",
//                "group_name"=> "book-later-restrict-hr"
//            ],
            [
                "slug"=> "flat_discount_next_hour_price_percentage",
                "value"=> "0",
                "label"=> "Next hour dicount on subject price percentage",
                "group_name"=> "next-hour-discount-on-subject-price-percentage"
            ]
        ];

        foreach($entries as $entry)
        {
            \App\Models\Setting::updateOrCreate(
                [
                    'slug'  =>  $entry['slug']
                ],
                $entry
            );
        }
    }
}
