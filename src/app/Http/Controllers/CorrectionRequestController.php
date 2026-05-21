<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CorrectionRequestRequest;
use App\Models\CorrectionRequest;
use App\Models\CorrectionBreak;
use App\Models\AttendanceRecord;
use App\Models\AttendanceBreak;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CorrectionRequestController extends Controller
{
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
    public function approve($id)
    {
        DB::transaction(function () use ($id) {
            $request = CorrectionRequest::with('correctionBreaks')->findOrFail($id);
            $record = AttendanceRecord::findOrFail($request->attendance_record_id);
            $record->update([
                'clock_in'  => $request->requested_clock_in,
                'clock_out' => $request->requested_clock_out,
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
        return redirect()->route('admin.application.show', $id)->with('success', '申請を承認しました。');
    }

 // 申請詳細画面の表示
    public function show($id)
    {
        $application = CorrectionRequest::with('user', 'correctionBreaks')->findOrFail($id);
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
