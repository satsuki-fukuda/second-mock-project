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
        // 対象のユーザーID（1:admin, 2:user1, 3:user2 など）
        $userIds = [1, 2, 3];

        foreach ($userIds as $userId) {
            // 1年前から昨日（yesterday）まで
            $start = Carbon::now()->subYear();
            $end = Carbon::now()->subDay();

            // ループを抜ける条件を明確にするため clone を使用
            for ($date = $start->copy(); $date <= $end; $date->addDay()) {
                // 土日をスキップ
                if ($date->isWeekend()) {
                    continue;
                }

                // 1. 勤務レコードの作成
                $record = AttendanceRecord::create([
                    'user_id' => $userId,
                    'date' => $date->format('Y-m-d'),
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                    'total_time' => 480 * 60,       // 8時間 = 480分
                    'total_break_time' => 60 * 60,
                    // 合計勤務時間は必要に応じてここに含める（例：'work_time' => '08:00:00'）
                ]);

                // 2. 休憩レコードの作成（attendance_breaksテーブル）
                // 勤務レコードのIDを紐付ける
                AttendanceBreak::create([
                    'attendance_record_id' => $record->id,
                    'break_start' => '12:00:00',
                    'break_end' => '13:00:00',
                ]);
            }
        }
    }
}
