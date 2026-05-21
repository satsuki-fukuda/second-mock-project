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

    protected $casts = [
        'requested_date' => 'date',
        'correction_requested_at' => 'datetime',
    ];

    public function correctionBreaks()
    {
        return $this->hasMany(CorrectionBreak::class);
    }

        public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
