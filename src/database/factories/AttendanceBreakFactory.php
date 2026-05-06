<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AttendanceBreak;

class AttendanceBreakFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */

    protected $model = AttendanceBreak::class;
    public function definition()
    {
        return [
            'break_start' => '12:00',
            'break_end' => '13:00',

        ];
    }
}
