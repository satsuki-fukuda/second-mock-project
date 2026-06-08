<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;


class AttendanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = AttendanceRecord::class;

    public function definition()
    {
        $userIds = User::pluck('id');
        $userId  = $userIds->random();
        $date     = $this->faker->dateTimeBetween('2024-01-01', '2024-12-31')->format('Y-m-d');
        $clockIn  = Carbon::createFromFormat('Y-m-d H:i:s', $date.' '.$this->faker->time('H:i:s'));
        $clockOut = Carbon::createFromFormat('Y-m-d H:i:s', $date.' '.$this->faker->time('H:i:s'))
            ->addHours(rand(6, 10));
        $totalBreakSeconds = 0;
        $workedSeconds = $clockOut->diffInSeconds($clockIn) - $totalBreakSeconds;
        return [
            'user_id'            => $userId,
            'date'               => $date,
            'clock_in'           => $clockIn->format('H:i'),
            'clock_out'          => $clockOut->format('H:i'),
            'total_break_time'   => $totalBreakSeconds,
            'total_time'         => $workedSeconds,
            'comment'            => $this->faker->optional()->sentence(),
        ];
    }
}
