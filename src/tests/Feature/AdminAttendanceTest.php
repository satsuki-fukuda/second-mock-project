<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;
    //勤怠一覧情報取得機能（管理者）--全ユーザーの勤怠情報が正確に確認できる
    public function test_admin_can_view_attendance_list_and_see_all_users_data()
    {
        $now = \Carbon\Carbon::create(2026, 6, 7, 10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::flushEventListeners();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');

        $user1 = \App\Models\User::where('email', 'user1@example.com')->first();
        $user2 = \App\Models\User::where('email', 'user2@example.com')->first();

        $attendance1 = new \App\Models\AttendanceRecord();
        $attendance1->user_id = $user1->id;
        $attendance1->date = '2026-06-07';
        $attendance1->clock_in = '09:00:00';
        $attendance1->clock_out = '18:00:00';
        $attendance1->save();

        $attendance2 = new \App\Models\AttendanceRecord();
        $attendance2->user_id = $user2->id;
        $attendance2->date = '2026-06-07';
        $attendance2->clock_in = '10:00:00';
        $attendance2->clock_out = '19:00:00';
        $attendance2->save();

        $detailResponse1 = $this->actingAs($admin)->get('/admin/attendance/' . $attendance1->id);
        $detailResponse1->assertStatus(200);
        $detailResponse1->assertSee($user1->name);
        $detailResponse1->assertSee('09:00');
        $detailResponse1->assertSee('18:00');
        $detailResponse2 = $this->actingAs($admin)->get('/admin/attendance/' . $attendance2->id);
        $detailResponse2->assertStatus(200);
        $detailResponse2->assertSee($user2->name);
        $detailResponse2->assertSee('10:00');
        $detailResponse2->assertSee('19:00');
        \Carbon\Carbon::setTestNow();
    }


    //勤怠一覧情報取得機能（管理者）-- 遷移後現在の日付が表示
    public function test_admin_can_view_attendance_list_and_see_current_date()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true; 
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');

        \App\Models\User::flushEventListeners();

        $todayStr = $now->format('Y-m-d');
        $adminListUrl = '/admin/attendance/list?date=' . $todayStr;

        $response = $this->actingAs($admin)->get($adminListUrl);
        $response->assertStatus(200);

        $expectedDateText = $now->isoFormat('YYYY年M月D日');
        $response->assertSee($expectedDateText);
        $response->assertSee('value="' . $todayStr . '"', false);
        \Carbon\Carbon::setTestNow();
    }

    //勤怠一覧情報取得機能（管理者）--「前日」を押下で前の日の勤怠情報が表示
    public function test_admin_can_navigate_to_previous_date()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');

        \App\Models\User::flushEventListeners();

        $currentDateValue = $now->format('Y-m-d');
        $currentDateText  = $now->isoFormat('YYYY年M月D日');
        $prevDateValue    = $now->copy()->subDay()->format('Y-m-d');
        $prevDateText     = $now->copy()->subDay()->isoFormat('YYYY年M月D日');

        $adminListUrl = '/admin/attendance/list';
        $response = $this->actingAs($admin)->get($adminListUrl . '?date=' . $currentDateValue);
        $response->assertStatus(200);
        $response->assertSee($currentDateText);

        $expectedPrevLink = '/admin/attendance/list?date=' . $prevDateValue;
        $response->assertSee($expectedPrevLink, false);

        $prevResponse = $this->actingAs($admin)->get($expectedPrevLink);
        $prevResponse->assertStatus(200);
        $prevResponse->assertSee($prevDateText);
        $prevResponse->assertSee('value="' . $prevDateValue . '"', false);
        \Carbon\Carbon::setTestNow();
    }

    //勤怠一覧情報取得機能（管理者）--「翌日」を押下で次の日の勤怠情報が表示
    public function test_admin_can_navigate_to_next_date()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');

        \App\Models\User::flushEventListeners();

        $currentDateValue = $now->format('Y-m-d');
        $currentDateText  = $now->isoFormat('YYYY年M月D日');

        $nextDateValue    = $now->copy()->addDay()->format('Y-m-d');
        $nextDateText     = $now->copy()->addDay()->isoFormat('YYYY年M月D日');

        $adminListUrl = '/admin/attendance/list';
        $response = $this->actingAs($admin)->get($adminListUrl . '?date=' . $currentDateValue);
        $response->assertStatus(200);
        $response->assertSee($currentDateText);

        $expectedNextLink = '/admin/attendance/list?date=' . $nextDateValue;
        $response->assertSee($expectedNextLink, false);

        $nextResponse = $this->actingAs($admin)->get($expectedNextLink);
        $nextResponse->assertStatus(200);
        $nextResponse->assertSee($nextDateText);
        $nextResponse->assertSee('value="' . $nextDateValue . '"', false);
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報取得・修正機能（管理者）-- 勤怠詳細画面に表示されるデータが選択したものになっている
    public function test_admin_can_view_attendance_detail_and_see_matching_data()
    {
        $now = \Carbon\Carbon::create(2026, 6, 15, 10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        \App\Models\User::flushEventListeners();
        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');
        $user1 = \App\Models\User::where('email', 'user1@example.com')->first();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user1->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:30:00';
        $attendance->clock_out = '18:30:00';
        $attendance->save();

        $adminDetailUrl = '/admin/attendance/' . $attendance->id;
        $response = $this->actingAs($admin)->get($adminDetailUrl);
        $response->assertStatus(200);
        $response->assertSee($user1->name);
        $response->assertSee('2026年  6月15日');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報取得・修正機能（管理者）-- 出勤時間が退勤時間より後になっている場合エラーメッセージが表示
    public function test_admin_attendance_update_fails_if_work_start_is_after_work_end()
    {
        $now = \Carbon\Carbon::create(2026, 6, 15, 10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        \App\Models\User::flushEventListeners();

        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');
        $user1 = \App\Models\User::where('email', 'user1@example.com')->first();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user1->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:30:00';
        $attendance->clock_out = '18:30:00';
        $attendance->save();

        $adminDetailUrl = '/admin/attendance/' . $attendance->id;
        $response = $this->actingAs($admin)->get($adminDetailUrl);
        $response->assertStatus(200);

        $adminUpdateUrl = '/admin/attendance/update/' . $attendance->id;

        $invalidData = [
            'date'     => '2026-06-15',
            'clock_in' => '18:00',
            'end_time' => '09:00',
        ];

        $postResponse = $this->actingAs($admin)->post($adminUpdateUrl, $invalidData);
        $postResponse->assertStatus(302);
        $postResponse->assertRedirect();
        $postResponse->assertSessionHasErrors();
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報取得・修正機能（管理者）-- 休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示
    public function test_admin_attendance_update_fails_if_rest_start_is_after_work_end()
    {
        $now = \Carbon\Carbon::create(2026, 6, 15, 10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        \App\Models\User::flushEventListeners();
        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');
        $user1 = \App\Models\User::where('email', 'user1@example.com')->first();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user1->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:00:00';
        $attendance->clock_out = '18:00:00';
        $attendance->save();

        $adminDetailUrl = '/admin/attendance/' . $attendance->id;
        $response = $this->actingAs($admin)->get($adminDetailUrl);
        $response->assertStatus(200);

        $adminUpdateUrl = '/admin/attendance/update/' . $attendance->id;

        $invalidData = [
            'date'             => '2026-06-15',
            'clock_in'         => '09:00',
            'end_time'         => '18:00',
            'new_break_start'  => '19:00',
            'new_break_end'    => '20:00',
        ];

        $postResponse = $this->actingAs($admin)->post($adminUpdateUrl, $invalidData);
        $postResponse->assertStatus(302);
        $postResponse->assertRedirect();
        $postResponse->assertSessionHasErrors();
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報取得・修正機能（管理者）-- 休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示
    public function test_admin_attendance_update_fails_if_rest_end_is_after_work_end()
    {
        $now = \Carbon\Carbon::create(2026, 6, 15, 10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        \App\Models\User::flushEventListeners();
        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');
        $user1 = \App\Models\User::where('email', 'user1@example.com')->first();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user1->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:00:00';
        $attendance->clock_out = '17:00:00';
        $attendance->save();

        $adminDetailUrl = '/admin/attendance/' . $attendance->id;
        $response = $this->actingAs($admin)->get($adminDetailUrl);
        $response->assertStatus(200);

        $adminUpdateUrl = '/admin/attendance/update/' . $attendance->id;

        $invalidData = [
            'date'             => '2026-06-15',
            'clock_in'         => '09:00',
            'end_time'         => '17:00',
            'new_break_start'  => '12:00',
            'new_break_end'    => '18:00',
        ];

        $postResponse = $this->actingAs($admin)->post($adminUpdateUrl, $invalidData);
        $postResponse->assertStatus(302);
        $postResponse->assertRedirect();
        $postResponse->assertSessionHasErrors();
        \Carbon\Carbon::setTestNow();
    }

    //勤怠詳細情報取得・修正機能（管理者）-- 備考欄が未入力の場合エラーメッセージが表示
    public function test_admin_attendance_update_fails_if_reason_is_empty()
    {
        $now = \Carbon\Carbon::create(2026, 6, 15, 10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        \App\Models\User::flushEventListeners();
        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');
        $user1 = \App\Models\User::where('email', 'user1@example.com')->first();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user1->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:00:00';
        $attendance->clock_out = '18:00:00';
        $attendance->save();

        $adminDetailUrl = '/admin/attendance/' . $attendance->id;
        $response = $this->actingAs($admin)->get($adminDetailUrl);
        $response->assertStatus(200);

        $adminUpdateUrl = '/admin/attendance/update/' . $attendance->id;
        $invalidData = [
            'date'     => '2026-06-15',
            'clock_in' => '09:00',
            'end_time' => '18:00',
            'note'     => '',
        ];

        $postResponse = $this->actingAs($admin)->post($adminUpdateUrl, $invalidData);
        $postResponse->assertStatus(302);
        $postResponse->assertRedirect();
        $postResponse->assertSessionHasErrors();
        \Carbon\Carbon::setTestNow();
    }

    //ユーザー情報取得機能（管理者）-- 管理者が全ユーザーの氏名とメールアドレスを確認できる
    public function test_admin_can_view_staff_list_and_see_all_users_details()
    {
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        \App\Models\User::flushEventListeners();

        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');

        $user1 = User::where('email', 'user1@example.com')->first();
        $user2 = User::where('email', 'user2@example.com')->first();
        $this->assertNotNull($user1, 'user1が見つかりません。');
        $this->assertNotNull($user2, 'user2が見つかりません。');

        $staffListUrl = '/admin/staff/list';
        $response = $this->actingAs($admin)->get($staffListUrl);
        $response->assertStatus(200);

        $response->assertSee($user1->name);
        $response->assertSee($user1->email);

        $response->assertSee($user2->name);
        $response->assertSee($user2->email);
    }


    //ユーザー情報取得機能（管理者）-- ユーザーの勤怠情報の表示
    public function test_admin_can_view_selected_user_attendance_list_accurately()
    {
        $now = \Carbon\Carbon::create(2026, 6, 15, 10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        \App\Models\User::flushEventListeners();

        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');

        $selectedUser = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($selectedUser, '選択対象の一般ユーザーが見つかりません。');

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $selectedUser->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:00:00';
        $attendance->clock_out = '18:00:00';
        $attendance->save();

        $selectedUserListUrl = '/admin/attendance/staff/' . $selectedUser->id . '?month=2026-06';
        $response = $this->actingAs($admin)->get($selectedUserListUrl);
        $response->assertStatus(200);
        $response->assertSee($selectedUser->name);
        $response->assertSee('06/15(月)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        \Carbon\Carbon::setTestNow();
    }

    //ユーザー情報取得機能（管理者）-- 前月を押下で前月表示
    public function test_admin_can_navigate_to_previous_month_in_attendance_list()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);

        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');
        $selectedUser = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($selectedUser, '対象の一般ユーザーが見つかりません。');

        \App\Models\User::flushEventListeners();

        $currentMonthValue = $now->format('Y-m');
        $currentMonthText  = $now->format('Y/m');
        $prevMonthValue    = $now->copy()->subMonth()->format('Y-m');
        $prevMonthText     = $now->copy()->subMonth()->format('Y/m');

        $adminListUrl = '/admin/attendance/staff/' . $selectedUser->id;
        $response = $this->actingAs($admin)->get($adminListUrl . '?month=' . $currentMonthValue);
        $response->assertStatus(200);
        $response->assertSee($currentMonthText);

        $expectedPrevLink = '?month=' . $prevMonthValue;
        $response->assertSee($expectedPrevLink, false);

        $prevMonthUrl = $adminListUrl . '?month=' . $prevMonthValue;
        $prevResponse = $this->actingAs($admin)->get($prevMonthUrl);
        $prevResponse->assertStatus(200);
        $prevResponse->assertSee($prevMonthText);
        $prevResponse->assertSee('value="' . $prevMonthValue . '"', false);
        \Carbon\Carbon::setTestNow();
    }

    //ユーザー情報取得機能（管理者）-- 翌月を押下で翌日表示
    public function test_admin_can_navigate_to_next_month_in_attendance_list()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');
        $selectedUser = \App\Models\User::where('email', 'user1@example.com')->first();
        $this->assertNotNull($selectedUser, '対象の一般ユーザーが見つかりません。');

        \App\Models\User::flushEventListeners();

        $currentMonthValue = $now->format('Y-m');
        $currentMonthText  = $now->format('Y/m');

        $nextMonthValue    = $now->copy()->addMonth()->format('Y-m');
        $nextMonthText     = $now->copy()->addMonth()->format('Y/m');

        $adminListUrl = '/admin/attendance/staff/' . $selectedUser->id;
        $response = $this->actingAs($admin)->get($adminListUrl . '?month=' . $currentMonthValue);
        $response->assertStatus(200);
        $response->assertSee($currentMonthText);

        $expectedNextLink = '?month=' . $nextMonthValue;
        $response->assertSee($expectedNextLink, false);

        $nextMonthUrl = $adminListUrl . '?month=' . $nextMonthValue;
        $nextResponse = $this->actingAs($admin)->get($nextMonthUrl);
        $nextResponse->assertStatus(200);
        $nextResponse->assertSee($nextMonthText);
        $nextResponse->assertSee('value="' . $nextMonthValue . '"', false);
        \Carbon\Carbon::setTestNow();
    }


    //ユーザー情報取得機能（管理者）-- 詳細を押下で勤怠詳細画面遷移
    public function test_admin_can_navigate_from_attendance_list_to_detail_page()
    {
        $now = \Carbon\Carbon::create(2026, 6, 15, 10, 0, 0);
        \Carbon\Carbon::setTestNow($now);
        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');
        $user1 = \App\Models\User::where('email', 'user1@example.com')->first();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user1->id;
        $attendance->date = '2026-06-15';
        $attendance->clock_in = '09:00:00';
        $attendance->clock_out = '18:00:00';
        $attendance->save();

        \App\Models\User::flushEventListeners();

        $adminListUrl = '/admin/attendance/staff/' . $user1->id . '?month=2026-06';
        $response = $this->actingAs($admin)->get($adminListUrl);
        $response->assertStatus(200);

        $adminDetailUrl = '/admin/attendance/' . $attendance->id;
        $response->assertSee($adminDetailUrl, false);

        $detailResponse = $this->actingAs($admin)->get($adminDetailUrl);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee($user1->name);
        $detailResponse->assertSee('2026年  6月15日');
        $detailResponse->assertSee('09:00');
        $detailResponse->assertSee('18:00');
        \Carbon\Carbon::setTestNow();
    }
}