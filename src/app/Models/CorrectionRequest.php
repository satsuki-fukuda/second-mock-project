<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    protected $fillable = [
        'user_id', 'attendance_record_id', 'requested_date',
        'requested_clock_in', 'requested_clock_out',
        'correction_status', 'correction_requested_at', 'comment'
    ];

        /**
     * 💡 日付キャストの追加
     * これにより Blade で ->format() がエラーなく使えるようになります
     */
    protected $casts = [
        'requested_date' => 'date',
        'correction_requested_at' => 'datetime',
    ];

        /**
     * 💡 休憩の申請データとのリレーションを追加
     */
    public function correctionBreaks()
    {
        return $this->hasMany(CorrectionBreak::class);
    }

        public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    // 申請者とのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
