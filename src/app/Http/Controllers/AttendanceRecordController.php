<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CorrectionRequestRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceBreak;
use App\Models\CorrectionRequest;
use App\Models\CorrectionBreak;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceRecordController extends Controller
{
    public function list(Request $request)
    {
        $user = Auth::user();
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $targetDate = Carbon::parse($month);
        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth = $targetDate->copy()->endOfMonth();
        $allDays = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $allDays[$date->format('Y-m-d')] = null;
        }
        $attendances = AttendanceRecord::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(function($item) {
                return \Carbon\Carbon::parse($item->date)->format('Y-m-d');
            });
        foreach ($allDays as $date => $value) {
            if (isset($attendances[$date])) {
                $allDays[$date] = $attendances[$date];
            }
        }
        return view('user.index', [
            'attendances' => $allDays,
            'month' => $month
        ]);
    }

 // 出退勤機能
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $displayDate = now()->isoFormat('YYYY年M月D日(ddd)');
        $displayTime = now()->format('H:i');
        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
        $status = '勤務外';
        if ($attendance) {
            if ($attendance->clock_out) {
                $status = '退勤済';
            } elseif ($this->isResting($attendance)) {
                $status = '休憩中';
            } else {
                $status = '出勤中';
            }
        }
        return view('user.attendance',  [
            'attendance' => $attendance,
            'status' => $status,
            'displayDate' => $displayDate,
            'displayTime' => $displayTime
        ]);
    }

    private function isResting($attendance)
    {
        return $attendance->attendanceBreaks()->whereNull('break_end')->exists();
    }

    public function workStart()
    {
        AttendanceRecord::create([
            'user_id' => Auth::id(),
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->format('H:i:s'),
            'comment' => '',
        ]);
        return redirect()->back()->with('success', '出勤しました');
    }

    public function restStart()
    {
        $record = AttendanceRecord::where('user_id', Auth::id())
            ->where('date', Carbon::today())
            ->first();
        $record->attendanceBreaks()->create([
            'break_start' => Carbon::now()->format('H:i:s'),
        ]);
        return redirect()->back()->with('success', '休憩に入りました');
    }

    public function restEnd()
    {
        $record = AttendanceRecord::where('user_id', Auth::id())
            ->where('date', Carbon::today())
            ->first();
        $now = Carbon::now();
        $latestBreak = $record->attendanceBreaks()->whereNull('break_end')->latest()->first();
        if ($latestBreak) {
            $latestBreak->update(['break_end' => $now->format('H:i:s')]);
            $totalSeconds = 0;
            foreach ($record->attendanceBreaks()->whereNotNull('break_end')->get() as $break) {
                $totalSeconds += Carbon::parse($break->break_start)->diffInSeconds(Carbon::parse($break->break_end));
            }
            $record->update([
                'total_break_time' => $totalSeconds
            ]);
        }
        return redirect()->back()->with('success', '休憩を終了しました');
    }

    public function workEnd()
    {
        $user = Auth::user();
        $record = AttendanceRecord::where('user_id', Auth::id())
            ->where('date', Carbon::today())
            ->first();
        $now = Carbon::now();
        $record->clock_out = $now->format('H:i:s');
        $workInSeconds = Carbon::parse($record->clock_in)->diffInSeconds($now);
        $breakSeconds = $record->total_break_time ?? 0;
        $record->total_time = max(0, $workInSeconds - $breakSeconds);
        $record->save();
        $user->status = '退勤済';
        $user->save();
        return redirect()->back()->with('success', '退勤しました。お疲れ様でした！');
    }

 // 詳細画面の表示
    public function show(Request $request, $id = null)
    {
        if ($id) {
            $attendance = AttendanceRecord::with(['user', 'attendanceBreaks'])->findOrFail($id);
            $date = $attendance->date;
        } else {
            $date = $request->query('date');
            $attendance = new AttendanceRecord([
                'date' => $date,
                'user_id' => auth()->id()
            ]);
            $attendance->setRelation('user', auth()->user());
            $attendance->setRelation('attendanceBreaks', collect());
        }
        $pendingRequest = null;
        if ($id) {
            $pendingRequest = \App\Models\CorrectionRequest::with('correctionBreaks')
                ->where('attendance_record_id', $id)
                ->where('correction_status', '承認待ち')
                ->first();
        } else {
            $pendingRequest = \App\Models\CorrectionRequest::with('correctionBreaks')
                ->where('user_id', auth()->id())
                ->where('requested_date', $date)
                ->where('correction_status', '承認待ち')
                ->first();
        }
        $isPending = !empty($pendingRequest);
        return view('user.detail', compact('attendance', 'isPending', 'pendingRequest'));
    }

 // 管理者用：勤怠詳細新規登録
    public function adminCreate(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $date = $request->date;
        $attendance = new AttendanceRecord([
            'user_id' => $user->id,
            'date'    => $date,
        ]);
        $attendance->setRelation('user', $user);
        $attendance->setRelation('attendanceBreaks', collect());
        $isPending = false;
        return view('admin.detail', compact('attendance'));
    }

 // 修正申請の保存処理
    public function update(CorrectionRequestRequest $request, $id = null)
    {
        if ($id) {
            $attendance = AttendanceRecord::findOrFail($id);
        } else {
            $attendance = AttendanceRecord::create([
                'user_id' => auth()->id(),
                'date' => $request->input('date'),
                'clock_in' => '00:00:00',
                'clock_out' => null,
                'total_time' => 0,
                'total_break_time' => 0,
            ]);
        }
        $requestedDate = $attendance->date;
        \DB::transaction(function () use ($request, $attendance, $requestedDate) {
            $corrRequest = CorrectionRequest::create([
                'user_id'                 => auth()->id(),
                'attendance_record_id'    => $attendance->id,
                'requested_date'          => $requestedDate,
                'requested_clock_in'      => $request->clock_in,
                'requested_clock_out'     => $request->end_time,
                'correction_status'       => '承認待ち',
                'correction_requested_at' => now(),
                'comment'                 => $request->note,
            ]);
            if ($request->has('breaks')) {
                foreach ($request->breaks as $breakData) {
                    if (!empty($breakData['start'])) {
                        $corrRequest->correctionBreaks()->create([
                            'new_break_start' => $breakData['start'],
                            'new_break_end'   => $breakData['end'],
                        ]);
                    }
                }
            }
            if ($request->filled(['new_break_start', 'new_break_end'])) {
                $corrRequest->correctionBreaks()->create([
                    'new_break_start' => $request->new_break_start,
                    'new_break_end'   => $request->new_break_end,
                ]);
            }
        });

        return redirect()->route('attendance.detail', ['id' => $attendance->id])
            ->with('success', '修正申請を送信しました。');
    }


 // 管理者用：勤怠詳細編集画面の表示
    public function adminEdit($id)
    {
        $attendance = AttendanceRecord::with(['user', 'attendanceBreaks'])->findOrFail($id);
        if ($attendance->clock_in === '00:00:00') {
            $attendance->clock_in = null;
        }
        $isPending = \App\Models\CorrectionRequest::where('attendance_record_id', $attendance->id)
            ->where('correction_status', '承認待ち')
            ->exists();
        return view('admin.detail', compact('attendance', 'isPending'));
    }


 // 管理者用：勤怠データの更新処理
    public function adminUpdate(CorrectionRequestRequest $request, $id = null)
    {
        $targetId = $id ?? $request->route('id');
        if ($targetId) {
            $attendance = AttendanceRecord::findOrFail($targetId);
                        $isPending = \App\Models\CorrectionRequest::where('attendance_record_id', $attendance->id)
                ->where('correction_status', '承認待ち')
                ->exists();
            if ($isPending) {
                return redirect()->back()->withErrors(['error' => '承認待ちの修正申請があるため、この勤怠は修正できません。']);
            }

        } else {
            $attendance = AttendanceRecord::create([
                'user_id'          => $request->input('user_id'),
                'date'             => $request->input('date'),
                'clock_in'         => '00:00:00',
                'clock_out'        => null,
                'total_time'       => 0,
                'total_break_time' => 0,
            ]);
        }

        \DB::transaction(function () use ($request, $attendance) {
            $totalBreakSeconds = 0;
            if ($request->has('breaks')) {
                foreach ($request->breaks as $breakId => $breakData) {
                    $break = $attendance->attendanceBreaks()->find($breakId);
                    if ($break && !empty($breakData['start']) && !empty($breakData['end'])) {
                        $break->update([
                            'break_start' => $breakData['start'],
                            'break_end'   => $breakData['end'],
                        ]);
                        $totalBreakSeconds += Carbon::parse($breakData['start'])->diffInSeconds(Carbon::parse($breakData['end']));
                    }
                }
            }
            if ($request->filled(['new_break_start', 'new_break_end'])) {
                $attendance->attendanceBreaks()->create([
                    'break_start' => $request->new_break_start,
                    'break_end'   => $request->new_break_end,
                ]);
                $totalBreakSeconds += Carbon::parse($request->new_break_start)->diffInSeconds(Carbon::parse($request->new_break_end));
            }
            $totalTimeSeconds = 0;
            $clockIn  = $request->input('clock_in');
            $clockOut = $request->input('clock_out') ?? $request->input('end_time');
            $comment  = $request->input('comment') ?? $request->input('note');
            if ($clockIn && $clockOut) {
                $workingSeconds = Carbon::parse($clockIn)->diffInSeconds(Carbon::parse($clockOut));
                $totalTimeSeconds = max(0, $workingSeconds - $totalBreakSeconds);
            }
            $attendance->update([
                'clock_in'          => $clockIn ?? '00:00:00',
                'clock_out'         => $clockOut,
                'total_break_time'  => $totalBreakSeconds,
                'total_time'        => $totalTimeSeconds,
                'comment'           => $comment,
            ]);
            \App\Models\CorrectionRequest::where('attendance_record_id', $attendance->id)
                ->where('correction_status', '承認待ち')
                ->update(['correction_status' => '承認済み']);
        });
        if (!$id) {
            return redirect()->route('admin.attendance.staff', ['id' => $attendance->user_id])
                ->with('success', '勤怠データを新規登録しました');
        }
        return redirect()->route('admin.attendance.staff', ['id' => $attendance->user_id])
            ->with('success', '勤怠データを修正しました');
    }
}