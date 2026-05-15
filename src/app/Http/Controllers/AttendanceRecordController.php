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

    public function index()
    {
    $user = Auth::user();
    $today = Carbon::today();
    $displayDate = now()->isoFormat('YYYY年M月D日(ddd)');
    $displayTime = now()->format('H:i');


    // 今日の勤怠レコードを取得（最新1件）
    $attendance = AttendanceRecord::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();


        // --- ステータス判定ロジック ---
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
    /**
     * 休憩中かどうかを判定するヘルパー
     */
    private function isResting($attendance)
    {
        // 終了時間(break_end)が空の休憩レコードが存在すれば「休憩中」
        return $attendance->breaks()->whereNull('break_end')->exists();
    }

    // --- 出勤 (Work Start) ---
public function workStart()
{
    AttendanceRecord::create([
        'user_id' => Auth::id(),
        'date' => Carbon::today(),
        'clock_in' => Carbon::now()->format('H:i:s'),
        'comment' => '', // migrationがnullableでない場合
    ]);

    return redirect()->back()->with('success', '出勤しました');
}

// --- 休憩開始 (Rest Start) ---
public function restStart()
{
    $record = AttendanceRecord::where('user_id', Auth::id())
        ->where('date', Carbon::today())
        ->first();


        // 新しい休憩レコードを作成して開始時刻を入れる
        $record->breaks()->create([
            'break_start' => Carbon::now()->format('H:i:s'),
        ]);

    return redirect()->back()->with('success', '休憩に入りました');
}

// --- 休憩戻 (Rest End) ---
public function restEnd()
{
    $record = AttendanceRecord::where('user_id', Auth::id())
        ->where('date', Carbon::today())
        ->first();
    
    $now = Carbon::now();
        // 終了していない(break_endがNULL)最新の休憩を取得
        $latestBreak = $record->breaks()->whereNull('break_end')->latest()->first();


    if ($latestBreak) {
        $latestBreak->update(['break_end' => $now->format('H:i:s')]);

        // 合計「秒数」を計算
        $totalSeconds = 0;
        foreach ($record->breaks()->whereNotNull('break_end')->get() as $break) {
            $totalSeconds += Carbon::parse($break->break_start)->diffInSeconds(Carbon::parse($break->break_end));
        }
            
        // 文字列ではなく「整数」を保存
        $record->update([
            'total_break_time' => $totalSeconds
        ]);
    }

    return redirect()->back()->with('success', '休憩を終了しました');

}

// --- 退勤 (Work End) ---
public function workEnd()
{
    $record = AttendanceRecord::where('user_id', Auth::id())
        ->where('date', Carbon::today())
        ->first();

    $now = Carbon::now();
    $record->clock_out = $now->format('H:i:s');

    // 1. 出勤から退勤までの総秒数を計算
    $workInSeconds = Carbon::parse($record->clock_in)->diffInSeconds($now);


    // 2. 合計休憩秒数（すでに整数で入っている）
    $breakSeconds = $record->total_break_time ?? 0;

    // 3. 実労働秒数を保存（整数）
    $record->total_time = max(0, $workInSeconds - $breakSeconds);
    $record->save();

    return redirect()->back()->with('success', '退勤しました。お疲れ様でした！');
}

 // 詳細画面の表示
    public function show(Request $request, $id = null)
    {
            if ($id) {
        // IDがある場合（既存データの表示）
        $attendance = AttendanceRecord::with(['user', 'attendanceBreaks'])->findOrFail($id);
        $date = $attendance->date;
    } else {
        // 💡 IDがない場合（未打刻の日の表示）
        $date = $request->query('date');
        $attendance = new AttendanceRecord([
            'date' => $date,
            'user_id' => auth()->id()
        ]);
        $attendance->setRelation('user', auth()->user());
        $attendance->setRelation('attendanceBreaks', collect());
    }

    // 2. 💡 修正申請テーブルから「承認待ち」のデータを1件取得
        $pendingRequest = null;
        if ($id) {
            $pendingRequest = \App\Models\CorrectionRequest::with('correctionBreaks')
                ->where('attendance_record_id', $id)
                ->where('correction_status', '承認待ち')
                ->first();
        } else {
            // 未打刻の日の場合、日付とユーザーIDから未承認の申請があるかを探す
            $pendingRequest = \App\Models\CorrectionRequest::with('correctionBreaks')
                ->where('user_id', auth()->id())
                ->where('requested_date', $date)
        ->where('correction_status', '承認待ち')
        ->first();
        }

    // 3. 💡 ここで変数 $isPending を確実に定義する
    // $pendingRequest が存在すれば true, なければ false
    $isPending = !empty($pendingRequest);

    // 4. compact にすべての変数を漏れなく含める
    return view('user.detail', compact('attendance', 'isPending', 'pendingRequest'));
    }

  /**
     * 管理者用：勤怠詳細新規登録（未打刻）画面の表示
     */
    public function adminCreate(Request $request)
    {
        // クエリパラメータから情報を取得
        $user = User::findOrFail($request->user_id);
        $date = $request->date;

        // データベースに保存されていない「空の」勤怠レコードを作成（メモリ上のみ）
        $attendance = new AttendanceRecord([
            'user_id' => $user->id,
            'date'    => $date,
        ]);
        
        // ビューで $attendance->user->name やループ処理が落ちないよう初期化
        $attendance->setRelation('user', $user);
        $attendance->setRelation('attendanceBreaks', collect()); // 空のコレクション

        // 既存の編集用ビューをそのまま使う
        return view('admin.detail', compact('attendance'));
    }

    // 修正申請の保存処理
    public function update(CorrectionRequestRequest $request, $id = null)

    {
        // 💡 1. IDがある場合は既存レコードを取得、ない場合はその場で新規作成する
        if ($id) {
            $attendance = AttendanceRecord::findOrFail($id);
        } else {
            // 未打刻の日の場合、まず「空の勤務レコード」を仮作成してIDを発行する
            $attendance = AttendanceRecord::create([
                'user_id' => auth()->id(),
                'date' => $request->input('date'), // Bladeのhiddenから取得
                'clock_in' => '00:00:00', // 仮の初期値（null不可の場合）
                'clock_out' => null,
                'total_time' => 0,
                'total_break_time' => 0,
            ]);
        }

        $requestedDate = $attendance->date;

        \DB::transaction(function () use ($request, $attendance, $requestedDate) {
            // 1. 修正申請の作成
            $corrRequest = CorrectionRequest::create([
                'user_id'                 => auth()->id(),
                'attendance_record_id'    => $attendance->id, 
                'requested_date'          => $requestedDate, // 確実に変数が渡る
                'requested_clock_in'      => $request->clock_in,
                'requested_clock_out'     => $request->end_time,
                'correction_status'       => '承認待ち',
                'correction_requested_at' => now(),
                'comment'                 => $request->note,
            ]);
        // 2. 既存の休憩（修正分）を申請用テーブルに保存
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

        // 3. 新規の休憩を申請用テーブルに保存
        if ($request->filled(['new_break_start', 'new_break_end'])) {
            $corrRequest->correctionBreaks()->create([
                'new_break_start' => $request->new_break_start,
                'new_break_end'   => $request->new_break_end,
            ]);
        }
    });

    return redirect()->route('attendance.requests')->with('success', '修正申請を送信しました。');
}


  /**
     * 管理者用：勤怠詳細編集画面の表示
     */
    public function adminEdit($id)
    {
        $attendance = AttendanceRecord::with(['user', 'attendanceBreaks'])->findOrFail($id);

        return view('admin.detail', compact('attendance'));
    }


    /**
     * 管理者用：勤怠データの更新処理
     */
 public function adminUpdate(CorrectionRequestRequest $request, $id = null)
    {
        // 💡 1. フォームクラスのバリデーション済みデータを取得
        $validated = $request->validated();

        if ($id) {
            // 既存データの編集
            $attendance = AttendanceRecord::with('attendanceBreaks')->findOrFail($id);
        } else {
            // 💡 2. 未打刻の日（新規登録）の場合、勤務レコードをその場で作成
            $attendance = AttendanceRecord::create([
                'user_id'          => $request->input('user_id'), // もしくは適切な紐付けID
                'date'             => $request->input('date'),
                'clock_in'         => '00:00:00',
                'clock_out'        => null,
                'total_time'       => 0,
                'total_break_time' => 0,
            ]);
        }

        \DB::transaction(function () use ($request, $attendance) {
            // 3. 本番の勤務データを更新（Bladeの変更に伴い clock_in を使用）
            $attendance->update([
                'clock_in'  => $request->clock_in,
                'clock_out' => $request->end_time,
                'comment'   => $request->note,
            ]);

            // 4. 既存の休憩データのループ更新（Bladeの name="breaks[id][start]" と連動）
            if ($request->has('breaks')) {
                foreach ($request->breaks as $breakId => $breakData) {
                    $break = $attendance->attendanceBreaks->find($breakId);
                    if ($break) {
                        $break->update([
                            'break_start' => $breakData['start'],
                            'break_end'   => $breakData['end'],
                        ]);
                    }
                }
            }

            // 5. 新規休憩データの追加（Bladeの name="new_break_start" と連動）
            if ($request->filled(['new_break_start', 'new_break_end'])) {
                $attendance->attendanceBreaks()->create([
                    'break_start' => $request->new_break_start,
                    'break_end'   => $request->new_break_end,
                ]);
            }

            // 6. この勤怠レコードに対する「承認待ち」申請があれば自動で「承認済み」にする
            \App\Models\CorrectionRequest::where('attendance_record_id', $attendance->id)
                ->where('correction_status', '承認待ち')
                ->update(['correction_status' => '承認済み']);
        });

        // 新規作成から来た場合は、戻り先が破綻しないよう一覧等にリダイレクトさせるのが安全
        if (!$id) {
            return redirect()->route('attendance.list')->with('message', '勤怠データを新規登録しました');
        }

        return redirect()->back()->with('message', '勤怠データを修正しました');
    }
}