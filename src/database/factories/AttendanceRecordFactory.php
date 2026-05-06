<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User; // 👈 追加
use App\Models\AttendanceRecord; // 👈 追加（モデル名に合わせて調整してください）
use Carbon\Carbon; // 👈 追加


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
                // ユーザーID
        $userIds = User::pluck('id');
        $userId  = $userIds->random();

        // 日付と打刻（実際にはCarbonインスタンスを渡し、モデルのキャストでH:iに）
        $date     = $this->faker->dateTimeBetween('2024-01-01', '2024-12-31')->format('Y-m-d');
        $clockIn  = Carbon::createFromFormat('Y-m-d H:i:s', $date.' '.$this->faker->time('H:i:s'));
        $clockOut = Carbon::createFromFormat('Y-m-d H:i:s', $date.' '.$this->faker->time('H:i:s'))
                      ->addHours(rand(6, 10)); // 出退勤間隔は6〜10時間のランダム

        // 休憩時間合計はあとで BreaksTableSeeder が入れるので、ここではゼロでもOK
        $totalBreakSeconds = 0;

        // 実働時間（秒） = 出退勤差 - 休憩
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
