<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AttendanceRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class UserController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.staff.index');
        }
        return view('admin.login');
    }

    public function index(Request $request)
    {
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        $users = User::where('is_admin', false)
            ->get();
        $attendances = $users->map(function ($user) use ($date) {
            $record = AttendanceRecord::firstOrCreate([
                'user_id' => $user->id,
                'date'    => $date,
            ], [
                'clock_in'         => '00:00:00',
                'clock_out'        => null,
                'total_break_time' => 0,
                'total_time'       => 0,
            ]);
            return (object)[
                'id'               => $record->id,
                'user'             => $user,
                'clock_in'         => $record->clock_in === '00:00:00' ? null : $record->clock_in,
                'clock_out'        => $record->clock_out,
                'total_break_time' => $record->total_break_time,
                'total_time'       => $record->total_time,
            ];
        });
        return view('admin.index', compact('attendances', 'date'));
    }

    public function staffIndex()
    {
        $users = User::where('is_admin', false)->get();
        return view('admin.staff-index', compact('users'));
    }

 // スタッフ別 月次勤怠詳細
    public function staffDetail(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $monthParam = $request->query('month', Carbon::now()->format('Y-m'));
        $targetMonth = Carbon::parse($monthParam)->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();
        $attendances = AttendanceRecord::with('attendanceBreaks')
            ->where('user_id', $id)
            ->whereBetween('date', [$targetMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });
        $dates = [];
        for ($date = $targetMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dates[] = $date->copy();
        }
        return view('admin.staff-attendance-index', compact('user', 'targetMonth', 'dates', 'attendances'));
    }

 // 月次勤怠CSV出力
    public function exportCsv(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $monthParam = $request->query('month', now()->format('Y-m'));
        $targetMonth = Carbon::parse($monthParam);
        $attendances = AttendanceRecord::where('user_id', $id)
            ->whereYear('date', $targetMonth->year)
            ->whereMonth('date', $targetMonth->month)
            ->orderBy('date', 'asc')
            ->get();
        $response = new StreamedResponse(function () use ($user, $targetMonth, $attendances) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩1開始', '休憩1終了', '合計勤務時間', '備考']);
            foreach ($attendances as $record) {
                fputcsv($handle, [
                    $record->date,
                    $record->clock_in,
                    $record->clock_out,
                    $record->total_break_time,
                    $record->total_time,
                    $record->comment,
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$user->name}_{$targetMonth->format('Ym')}_attendance.csv",
        ]);
        return $response;
    }
}
