<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;

class UserTest extends TestCase
{
    use RefreshDatabase;

    //勤怠一覧情報取得機能（一般ユーザー）--自身の勤怠情報の全表示
    public function test_seeded_user_can_see_all_attendance_records()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);

        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');
        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = $now->format('Y-m-d');
        $attendance->clock_in = $now->copy()->subHours(8)->format('H:i:s');
        $attendance->clock_out = $now->format('H:i:s');
        $attendance->save();

        $response = $this->actingAs($user)->get('/attendance/list?month=' . $now->format('Y-m'));
        $response->assertStatus(200);

        $expectedDate = $now->isoFormat('MM/DD(ddd)');
        $expectedClockIn = $now->copy()->subHours(8)->format('H:i');
        $expectedClockOut = $now->format('H:i');

        $response->assertSee($expectedDate);
        $response->assertSee($expectedClockIn);
        $response->assertSee($expectedClockOut);
        \Carbon\Carbon::setTestNow();
    }


    //勤怠一覧情報取得機能（一般ユーザー）--勤怠一覧画面に遷移した際、現在の月が表示
    public function test_seeded_user_can_view_attendance_list_and_see_current_month()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');
        $user->markEmailAsVerified();

        \App\Models\User::saving(function () {
            return true;
        });

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);

        $expectedMonthText = $now->format('Y/m');
        $response->assertSee($expectedMonthText);
        $expectedMonthValue = $now->format('Y-m');
        $response->assertSee($expectedMonthValue);
        \Carbon\Carbon::setTestNow();
    }


    //勤怠一覧情報取得機能（一般ユーザー）--「前月」を押下で表示月の前月が表示
    public function test_seeded_user_can_navigate_to_previous_month()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');
        $user->markEmailAsVerified();

        \App\Models\User::saving(function () {
            return true;
        });

        $currentMonthValue = $now->format('Y-m');
        $currentMonthText  = $now->format('Y/m');

        $prevMonthValue    = $now->copy()->subMonth()->format('Y-m');
        $prevMonthText     = $now->copy()->subMonth()->format('Y/m');

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee($currentMonthText);

        $expectedPrevLink = '?month=' . $prevMonthValue;
        $response->assertSee($expectedPrevLink, false);

        $prevMonthUrl = '/attendance/list?month=' . $prevMonthValue;
        $prevResponse = $this->actingAs($user)->get($prevMonthUrl);
        $prevResponse->assertStatus(200);
        $prevResponse->assertSee($prevMonthText);
        $prevResponse->assertSee('value="' . $prevMonthValue . '"', false);
        \Carbon\Carbon::setTestNow();
    }

    //勤怠一覧情報取得機能（一般ユーザー）--「翌月」を押下で表示月の翌月が表示
    public function test_seeded_user_can_navigate_to_next_month()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');
        $user->markEmailAsVerified();

        \App\Models\User::saving(function () {
            return true;
        });

        $currentMonthValue = $now->format('Y-m');
        $currentMonthText  = $now->format('Y/m');

        $nextMonthValue    = $now->copy()->addMonth()->format('Y-m');
        $nextMonthText     = $now->copy()->addMonth()->format('Y/m');

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee($currentMonthText);

        $expectedNextLink = '?month=' . $nextMonthValue;
        $response->assertSee($expectedNextLink, false);

        $nextMonthUrl = '/attendance/list?month=' . $nextMonthValue;
        $nextResponse = $this->actingAs($user)->get($nextMonthUrl);
        $nextResponse->assertStatus(200);

        $nextResponse->assertSee($nextMonthText);
        $nextResponse->assertSee('value="' . $nextMonthValue . '"', false);
        \Carbon\Carbon::setTestNow();
    }

    //勤怠一覧情報取得機能（一般ユーザー）--「詳細」を押下でその日の詳細画面に遷移
    public function test_seeded_user_can_navigate_to_attendance_detail_page()
    {
        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');
        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '10:00:00';
        $attendance->clock_out = '19:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();

        $detailUrl = '/attendance/detail/' . $attendance->id;

        $detailResponse = $this->actingAs($user)->get($detailUrl);
        $detailResponse->assertStatus(200);

        $detailResponse->assertSee($user->name);
        $detailResponse->assertSee('2026年  6月15日');
        $detailResponse->assertSee('10:00');
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報取得機能（一般ユーザー）--勤怠詳細画面の名前がログインユーザーの氏名になっている
    public function test_seeded_user_can_view_attendance_detail_and_see_correct_name()
    {

        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);

        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');

        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '10:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();

        $detailUrl = '/attendance/detail/' . $attendance->id;
        $response = $this->actingAs($user)->get($detailUrl);

        $response->assertStatus(200);
        $response->assertSee($user->name);
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報取得機能（一般ユーザー）--勤怠詳細画面の日付が選択した日付になっている
    public function test_seeded_user_can_view_attendance_detail_and_see_correct_date()
    {
        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);

        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');

        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '10:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();

        $detailUrl = '/attendance/detail/' . $attendance->id;
        $response = $this->actingAs($user)->get($detailUrl);
        $response->assertStatus(200);
        $response->assertSee('2026年  6月15日');
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報取得機能（一般ユーザー）--出勤退勤にて記されている時間がログインユーザーの打刻と一致
    public function test_seeded_user_can_view_attendance_detail_and_see_matching_clock_in_out_time()
    {
        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');
        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '10:00:00';
        $attendance->clock_out = '19:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();

        $detailUrl = '/attendance/detail/' . $attendance->id;
        $response = $this->actingAs($user)->get($detailUrl);
        $response->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報取得機能（一般ユーザー）--休憩にて記されている時間がログインユーザーの打刻と一致
    public function test_seeded_user_can_view_attendance_detail_and_see_matching_rest_time()
    {
        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);

        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');

        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '10:00:00';
        $attendance->clock_out = '19:00:00';
        $attendance->save();

        $break = new \App\Models\AttendanceBreak();
        $break->attendance_record_id = $attendance->id;
        $break->break_start = '12:00:00';
        $break->break_end = '13:00:00';
        $break->save();

        \App\Models\User::flushEventListeners();

        $detailUrl = '/attendance/detail/' . $attendance->id;
        $response = $this->actingAs($user)->get($detailUrl);
        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報修正機能（一般ユーザー）--出勤時間が退勤時間より後になっている場合エラーメッセージが表示
    public function test_attendance_update_fails_if_work_start_is_after_work_end()
    {
        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');
        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '10:00:00';
        $attendance->clock_out = '19:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();
        $detailUrl = '/attendance/detail/' . $attendance->id;

        $updateUrl = '/attendance/update/' . $attendance->id;

        $invalidData = [
            'date'     => '2026-06-15',
            'clock_in' => '18:00',
            'end_time' => '09:00',
            'note'     => '修正の申請理由テスト',
        ];

        $postResponse = $this->actingAs($user)->post($updateUrl, $invalidData);
        $postResponse->assertStatus(302);
        $postResponse->assertRedirect();
        $postResponse->assertSessionHasErrors();
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報修正機能（一般ユーザー）--休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示
    public function test_attendance_update_fails_if_rest_start_is_after_work_end()
    {
        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true; 
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');
        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:00:00';
        $attendance->clock_out = '18:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();

        $detailUrl = '/attendance/detail/' . $attendance->id;
        $updateUrl = '/attendance/update/' . $attendance->id;

        $invalidData = [
            'date'             => '2026-06-15',
            'clock_in'         => '09:00',
            'end_time'         => '18:00',
            'new_break_start'  => '19:00',
            'new_break_end'    => '20:00',
            'note'             => '修正の申請理由テスト',
        ];

        $postResponse = $this->actingAs($user)->post($updateUrl, $invalidData);
        $postResponse->assertStatus(302);
        $postResponse->assertRedirect();
        $postResponse->assertSessionHasErrors();
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報修正機能（一般ユーザー）--休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示
    public function test_attendance_update_fails_if_rest_end_is_after_work_end()
    {
        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');
        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:00:00';
        $attendance->clock_out = '17:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();

        $detailUrl = '/attendance/detail/' . $attendance->id;
        $updateUrl = '/attendance/update/' . $attendance->id;

        $invalidData = [
            'date'             => '2026-06-15',
            'clock_in'         => '09:00',
            'end_time'         => '17:00',
            'new_break_start'  => '12:00',
            'new_break_end'    => '18:00',
            'note'             => '修正の申請理由テスト',
        ];

        $postResponse = $this->actingAs($user)->post($updateUrl, $invalidData);
        $postResponse->assertStatus(302);
        $postResponse->assertRedirect();
        $postResponse->assertSessionHasErrors();
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報修正機能（一般ユーザー）--備考欄が未入力の場合エラーメッセージが表示
    public function test_attendance_update_fails_if_reason_is_empty()
    {
        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'Seederで作成されたユーザーが見つかりません。');
        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:00:00';
        $attendance->clock_out = '18:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();

        $detailUrl = '/attendance/detail/' . $attendance->id;
        $updateUrl = '/attendance/update/' . $attendance->id;

        $invalidData = [
            'date'     => '2026-06-15',
            'clock_in' => '09:00',
            'end_time' => '18:00',
            'note'     => '',
        ];

        $postResponse = $this->actingAs($user)->post($updateUrl, $invalidData);
        $postResponse->assertStatus(302);
        $postResponse->assertRedirect();
        $postResponse->assertSessionHasErrors();
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報修正機能（一般ユーザー）--修正申請処理が実行される
    public function test_user_request_is_displayed_on_admin_approval_and_list_screens()
    {
        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, '一般ユーザーが見つかりません。');
        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:00:00';
        $attendance->clock_out = '18:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();

        $updateUrl = '/attendance/update/' . $attendance->id;
        $validRequestData = [
            'date'     => '2026-06-15',
            'clock_in' => '10:00',
            'end_time' => '19:00',
            'note'     => '電車の遅延のため修正します。',
        ];

        $postResponse = $this->actingAs($user)->post($updateUrl, $validRequestData);
        $postResponse->assertStatus(302);

        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');

        $adminListResponse = $this->actingAs($admin)->get('/stamp_correction_request/list?status=pending');
        $adminListResponse->assertStatus(200);
        $adminListResponse->assertSee($user->name);
        $adminListResponse->assertSee('電車の遅延のため修正します。');

        $requestData = \App\Models\CorrectionRequest::where('user_id', $user->id)->first();
        $this->assertNotNull($requestData, '管理者向けの申請データが生成されていません。');

        $adminApprovalUrl = '/admin/stamp_correction_request/approve/' . $requestData->id;
        $adminApprovalResponse = $this->actingAs($admin)->get($adminApprovalUrl);
        $adminApprovalResponse->assertStatus(200);

        $adminApprovalResponse->assertSee($user->name);
        $adminApprovalResponse->assertSee('10:00');
        $adminApprovalResponse->assertSee('19:00');
        $adminApprovalResponse->assertSee('電車の遅延のため修正します。');
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報修正機能（一般ユーザー）--承認待ちにログインユーザーが行った申請が全て表示
    public function test_user_can_see_all_of_their_own_attendance_requests_in_list()
    {
        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'ログインユーザーが見つかりません。');
        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:00:00';
        $attendance->clock_out = '18:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();

        $updateUrl = '/attendance/update/' . $attendance->id;

        $requestData = [
            'date'     => '2026-06-15',
            'clock_in' => '10:00',
            'end_time' => '19:00',
            'note'     => '体調不良による時間変更',
        ];

        $postResponse = $this->actingAs($user)->post($updateUrl, $requestData);
        $postResponse->assertStatus(302);
        $myRequestsUrl = '/stamp_correction_request/list?status=pending';

        $listResponse = $this->actingAs($user)->get($myRequestsUrl);
        $listResponse->assertStatus(200);
        $listResponse->assertSee('承認待ち');
        $listResponse->assertSee($user->name);
        $listResponse->assertSee('2026/06/15');
        $listResponse->assertSee('体調不良による時間変更');
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報修正機能（一般ユーザー）--承認済みに管理者が承認した修正申請が全て表示
    public function test_user_can_see_approved_requests_in_their_request_list()
    {
        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, '一般ユーザーが見つかりません。');
        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:00:00';
        $attendance->clock_out = '18:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();

        $updateUrl = '/attendance/update/' . $attendance->id;

        $requestData = [
            'date'     => '2026-06-15',
            'clock_in' => '10:00',
            'end_time' => '19:00',
            'note'     => '事前申請忘れのための修正',
        ];

        $postResponse = $this->actingAs($user)->post($updateUrl, $requestData);
        $postResponse->assertStatus(302);

        $appliedData = \App\Models\CorrectionRequest::where('user_id', $user->id)->first();
        $this->assertNotNull($appliedData, '申請データが生成されていません。');

        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');

        $approveUrl = '/admin/stamp_correction_request/approve/' . $appliedData->id;
        $approveResponse = $this->actingAs($admin)->patch($approveUrl);
        $approveResponse->assertStatus(302);

        $myRequestsUrl = '/stamp_correction_request/list?status=approved';
        $listResponse = $this->actingAs($user)->get($myRequestsUrl);
        $listResponse->assertStatus(200);
        $listResponse->assertSee('承認済み');
        $listResponse->assertSee($user->name);
        $listResponse->assertSee('2026/06/15');
        $listResponse->assertSee('事前申請忘れのための修正');
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報修正機能（一般ユーザー）--各申請の詳細を押下すると勤怠詳細画面に遷移
    public function test_user_can_navigate_from_request_list_to_attendance_detail()
    {
        $now = \Carbon\Carbon::now()->startOfMonth()->addDays(14)->setTime(10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($user, 'ログインユーザーが見つかりません。');
        $user->markEmailAsVerified();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:00:00';
        $attendance->clock_out = '18:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();

        $updateUrl = '/attendance/update/' . $attendance->id;

        $requestData = [
            'date'     => '2026-06-15',
            'clock_in' => '10:00',
            'end_time' => '19:00',
            'note'     => '詳細画面への遷移テストのための修正',
        ];

        $postResponse = $this->actingAs($user)->post($updateUrl, $requestData);
        $postResponse->assertStatus(302);

        $myRequestsUrl = '/stamp_correction_request/list?status=pending';
        $listResponse = $this->actingAs($user)->get($myRequestsUrl);
        $listResponse->assertStatus(200);

        $detailUrl = '/attendance/detail/' . $attendance->id;
        $listResponse->assertSee($detailUrl, false);

        $detailResponse = $this->actingAs($user)->get($detailUrl);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee($user->name);
        $detailResponse->assertSee('2026年  6月15日');
        \Carbon\Carbon::setTestNow();
    }
}
