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
        
        // タブ判定（?tab=approved なら承認済み、それ以外は承認待ち）
        // ビュー側のリンクが ['status' => 'approved'] なので 'status' を取得
        $statusParam = $request->query('status', 'pending');
        $dbStatus = ($statusParam === 'approved') ? '承認済み' : '承認待ち';


           // 2. クエリの基本形（リレーション先を読み込んでおく）
        $query = CorrectionRequest::with('user');

        // 3. 管理者でなければ、自分のデータだけに絞り込む
        if (!$user->is_admin) {
            $query->where('user_id', $user->id);
        }


        // 3. データ取得（変数名を applications に統一）
        $data = $query->where('correction_status', $dbStatus)
                             ->orderBy('correction_requested_at', 'desc')
                             ->get();

                // 管理者なら admin 用、ユーザーなら user 用のビューを返す
        $view = $user->is_admin ? 'admin.correction-request-index' : 'user.correction-request-index';
        return view($view, [
        'applications' => $data, // 管理者用ビューが使用
        'requests'     => $data, // ユーザー用ビューが使用
            'status' => $statusParam // 'pending' または 'approved'
        ]);

    }

          /**
     * 承認処理：勤怠レコードを上書き
     */
    public function approve($id)
    {
        DB::transaction(function () use ($id) {
            $request = CorrectionRequest::with('correctionBreaks')->findOrFail($id);

            // 1. 本元の勤怠レコードを更新
            $record = AttendanceRecord::findOrFail($request->attendance_record_id);
            $record->update([
                'clock_in'  => $request->requested_clock_in,
                'clock_out' => $request->requested_clock_out,
                'note'      => $request->comment,
            ]);

            // 2. 休憩データの同期（一度消して申請内容で上書き）
            $record->attendanceBreaks()->delete();
            foreach ($request->correctionBreaks as $cb) {
                $record->attendanceBreaks()->create([
                    'break_start' => $cb->new_break_start,
                    'break_end'   => $cb->new_break_end,
                ]);
            }

            // 3. 申請ステータスを更新
            $request->update(['correction_status' => '承認済み']);
        });

        return redirect()->route('admin.application.show', $id)->with('success', '申請を承認しました。');
    

    }
      /**
     * 申請詳細画面の表示
     */
    public function show($id)
    {
        $application = CorrectionRequest::with('user', 'correctionBreaks')->findOrFail($id);

        // Bladeの変数名にDBの値をセット
        $application->date = $application->requested_date;
    $application->start_time = \Carbon\Carbon::parse($application->requested_clock_in)->format('H:i');
    $application->end_time = \Carbon\Carbon::parse($application->requested_clock_out)->format('H:i');
        $application->reason = $application->comment;
        // Bladeの判定に合わせて変換
        $application->status = ($application->correction_status === '承認済み') ? 'approved' : 'pending';

        return view('admin.correction-request-approval', compact('application'));
    }

    public function update(AttendanceUpdateRequest $request, $id)
{
    DB::transaction(function () use ($request, $id) {
        // 1. 本番データは更新せず、申請レコードを作成
        $correctionRequest = CorrectionRequest::create([
            'user_id' => Auth::id(),
            'attendance_record_id' => $id,
            'requested_date' => $request->date, // フォームから日付も送る必要あり
            'requested_clock_in' => $request->clock_in,
            'requested_clock_out' => $request->end_time,
            'correction_status' => '承認待ち',
            'comment' => $request->note,
            'correction_requested_at' => now(),
        ]);

        // 2. 休憩の申請内容を保存
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
