<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class CMSTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('c_m_s')->insert([
            'content' => 'Tootar Teacher T&c',
            'user_role_id' => 2,
            'type' => 'terms_and_conditions',
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);

        DB::table('c_m_s')->insert([
            'content' => 'Tootar T&c',
            'user_role_id' => 3,
            'type' => 'terms_and_conditions',
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);

        DB::table('c_m_s')->insert([
            'content' => 'Tootar Teacher Home Page Note',
            'user_role_id' => 2,
            'type' => 'home_page_note',
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);

        DB::table('c_m_s')->insert([
            'content' => 'Tootar Home Page Note',
            'user_role_id' => 3,
            'type' => 'home_page_note',
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);

        DB::table('c_m_s')->insert([
            'content' => 'Tootar Teacher WhatsApp SMS',
            'user_role_id' => 2,
            'type' => 'whatsapp_sms',
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);

        DB::table('c_m_s')->insert([
            'content' => 'Tootar  WhatsApp SMS',
            'user_role_id' => 3,
            'type' => 'whatsapp_sms',
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);
    }
}
