<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CorrectionRequest;
use App\Models\CorrectionBreak;
use App\Models\AttendanceRecord;
use App\Models\AttendanceBreak;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CorrectionRequestController extends Controller
{
 //申請一覧表示
    public function list(Request $request)
    {
        $user = Auth::user();
        if ($user && $user->is_admin) {
            return $this->index($request);
        }
        if ($user && $user->hasVerifiedEmail()) {
            return $this->index($request);
        }
        return redirect('/email/verify');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $statusParam = $request->query('status', 'pending');
        $dbStatus = ($statusParam === 'approved') ? '承認済み' : '承認待ち';
        $query = CorrectionRequest::with('user');
        if (!$user->is_admin) {
            $query->where('user_id', $user->id);
        }
        $data = $query->where('correction_status', $dbStatus)
            ->orderBy('correction_requested_at', 'desc')
            ->get();
        $view = $user->is_admin ? 'admin.correction-request-index' : 'user.correction-request-index';
        return view($view, [
            'applications' => $data,
            'requests'     => $data,
            'status' => $statusParam
        ]);

    }

 // 承認処理
    public function approve($attendance_correct_request_id)
    {
        DB::transaction(function () use ($attendance_correct_request_id) {
            $request = CorrectionRequest::with('correctionBreaks')->findOrFail($attendance_correct_request_id);
            $record = AttendanceRecord::findOrFail($request->attendance_record_id);
            $totalBreakSeconds = 0;
            foreach ($request->correctionBreaks as $cb) {
                if ($cb->new_break_start && $cb->new_break_end) {
                    $start = Carbon::parse($cb->new_break_start);
                    $end = Carbon::parse($cb->new_break_end);
                    $totalBreakSeconds += $start->diffInSeconds($end);
                }
            }
            $totalBreakTime =  $totalBreakSeconds;
            $totalTime = 0;
            if ($request->requested_clock_in && $request->requested_clock_out) {
                $clockIn = Carbon::parse($request->requested_clock_in);
                $clockOut = Carbon::parse($request->requested_clock_out);
                $workingSeconds = $clockIn->diffInSeconds($clockOut);
                $actualWorkingSeconds = max(0, $workingSeconds - $totalBreakSeconds);
                $totalTime = $actualWorkingSeconds;
            }
            $record->update([
                'clock_in'  => $request->requested_clock_in,
                'clock_out' => $request->requested_clock_out,
                'total_break_time'  => $totalBreakTime,
                'total_time'        => $totalTime,
                'note'      => $request->comment,
            ]);
            $record->attendanceBreaks()->delete();
            foreach ($request->correctionBreaks as $cb) {
                $record->attendanceBreaks()->create([
                    'break_start' => $cb->new_break_start,
                    'break_end'   => $cb->new_break_end,
                ]);
            }
            $request->update(['correction_status' => '承認済み']);
        });
        return redirect()->route('admin.application.show', ['attendance_correct_request_id' => $attendance_correct_request_id])
            ->with('success', '申請を承認しました。');
    }

 // 申請詳細画面の表示
    public function show($attendance_correct_request_id)
    {
        $application = CorrectionRequest::with('user', 'correctionBreaks')->findOrFail($attendance_correct_request_id);
        $application->date = $application->requested_date;
        $application->start_time = \Carbon\Carbon::parse($application->requested_clock_in)->format('H:i');
        $application->end_time = \Carbon\Carbon::parse($application->requested_clock_out)->format('H:i');
        $application->reason = $application->comment;
        $application->status = ($application->correction_status === '承認済み') ? 'approved' : 'pending';
        return view('admin.correction-request-approval', compact('application'));
    }

    public function update(AttendanceUpdateRequest $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $correctionRequest = CorrectionRequest::create([
                'user_id' => Auth::id(),
                'attendance_record_id' => $id,
                'requested_date' => $request->date,
                'requested_clock_in' => $request->clock_in,
                'requested_clock_out' => $request->end_time,
                'correction_status' => '承認待ち',
                'comment' => $request->note,
                'correction_requested_at' => now(),
            ]);
            if ($request->has('breaks')) {
                foreach ($request->breaks as $data) {
                    $correctionRequest->correctionBreaks()->create([
                        'new_break_start' => $data['start'],
                        'new_break_end' => $data['end'],
                    ]);
                }
            }
        });
        return redirect()->back()->with('success', '修正申請を送信しました');
    }
}
