<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AttendanceRecord;
use App\Models\AttendanceBreak;
use Carbon\Carbon;

class AttendanceRecordsTableSeeder extends Seeder
{
    public function run()
    {
        $userIds = [2, 3];
        foreach ($userIds as $userId) {
            $start = Carbon::now()->subYear();
            $end = Carbon::now()->subDay();
            for ($date = $start->copy(); $date <= $end; $date->addDay()) {
                if ($date->isWeekend()) {
                    continue;
                }
                $record = AttendanceRecord::create([
                    'user_id' => $userId,
                    'date' => $date->format('Y-m-d'),
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                    'total_time' => 480 * 60,
                    'total_break_time' => 60 * 60,
                ]);
                AttendanceBreak::create([
                    'attendance_record_id' => $record->id,
                    'break_start' => '12:00:00',
                    'break_end' => '13:00:00',
                ]);
            }
        }
    }
}
