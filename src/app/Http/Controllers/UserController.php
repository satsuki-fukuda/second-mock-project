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
        // すでに管理者としてログイン済みならスタッフ一覧へ
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.staff.index');
        }
        return view('admin.login');
    }

     public function index(Request $request)
    {
    // 1. 日付を取得（クエリパラメータ 'date' があればそれ、なければ今日）
    $date = $request->query('date', Carbon::today()->format('Y-m-d'));
    // 💡 勤怠レコードを取得し、関連するユーザー情報(user)も一緒に読み込む
    $attendances = AttendanceRecord::with('user')
        ->whereDate('date', $date) // カラム名が 'date' の場合
        ->get();

    // 変数名をビューが期待している $attendances にして渡す
    return view('admin.index', compact('attendances', 'date'));
    }

    public function staffIndex()
{
    // 一般スタッフのみ取得
    $users = User::where('is_admin', false)->get();

    // 💡 admin フォルダ内の staff-index.blade.php を表示
    return view('admin.staff-index', compact('users'));
}

    /**
     * スタッフ別 月次勤怠詳細
     */
    public function staffDetail(Request $request, $id)
    {
        $user = User::findOrFail($id);
        // ここで該用ユーザーの勤怠レコードを取得するロジックを記述
         // 1. 対象月の設定（クエリパラメータがなければ今月）
    $monthParam = $request->query('month', Carbon::now()->format('Y-m'));
    $targetMonth = Carbon::parse($monthParam)->startOfMonth();
    $endOfMonth = $targetMonth->copy()->endOfMonth();

    // 2. その月の勤怠データを取得
    $attendances = AttendanceRecord::with('attendanceBreaks')
            ->where('user_id', $id)
            ->whereBetween('date', [$targetMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function($item) {
                // DBのdateが文字列の場合でもCarbon型でも対応できるように
                return Carbon::parse($item->date)->format('Y-m-d');
            });
    // 3. 1日から末日までの全日付リストを作成
    $dates = [];
    for ($date = $targetMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
        $dates[] = $date->copy();
    }
        return view('admin.staff-attendance-index', compact('user', 'targetMonth', 'dates', 'attendances'));
    }

    /**
 * 月次勤怠CSV出力
 */
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
        
        // 文字化け防止（Excel対応のBOM）
        fwrite($handle, "\xEF\xBB\xBF");

        // ヘッダー行
        fputcsv($handle, ['日付', '出勤', '退勤', '休憩1開始', '休憩1終了', '合計勤務時間', '備考']);

        // データ行
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
