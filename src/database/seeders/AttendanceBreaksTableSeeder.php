<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AttendanceBreaksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AttendanceBreak::factory()->count(50)->create();
    }
}
