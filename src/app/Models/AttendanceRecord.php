<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'total_time',
        'total_break_time',
        'comment'
    ];

    protected $casts = [
    'date' => 'date',
    ];

    /**
     * この勤怠レコードに紐づく複数の休憩レコードを取得
     */
    public function attendanceBreaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }
        /**
     * この勤怠レコードを所有するユーザーを取得
     */

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}


