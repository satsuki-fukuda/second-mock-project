<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AdminCorrectionTest extends TestCase
{
    use RefreshDatabase;
    //勤怠情報修正機能（管理者）--証人待ちの修正申請が全て表示
    public function test_admin_can_view_pending_requests_of_all_users()
    {
        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');

        $user1 = User::where('email', 'user1@example.com')->first();
        $user2 = User::where('email', 'user2@example.com')->first();

        $attendance1 = \App\Models\AttendanceRecord::create([
            'user_id' => $user1->id,
            'date' => '2026-06-07',
            'clock_in' => '09:00:00'
        ]);
        $attendance2 = \App\Models\AttendanceRecord::create([
            'user_id' => $user2->id,
            'date' => '2026-06-07',
            'clock_in' => '09:00:00'
        ]);

        $request1 = new \App\Models\CorrectionRequest();
        $request1->attendance_record_id = $attendance1->id;
        $request1->user_id = $user1->id;
        $request1->requested_date = '2026-06-07';
        $request1->requested_clock_in = '09:00:00';
        $request1->requested_clock_out = '18:00:00';
        $request1->correction_status = '承認待ち';
        $request1->correction_requested_at = '2026-06-07';
        $request1->comment = '修正の理由1';
        $request1->save();

        $request2 = new \App\Models\CorrectionRequest();
        $request2->attendance_record_id = $attendance2->id;
        $request2->user_id = $user2->id;
        $request2->requested_date = '2026-06-07';
        $request2->requested_clock_in = '09:00:00';
        $request2->requested_clock_out = '18:00:00';
        $request2->correction_status = '承認待ち';
        $request2->correction_requested_at = '2026-06-07';
        $request2->comment = '修正の理由2';
        $request2->save();

        $pendingTabUrl = '/stamp_correction_request/list?tab=pending';
        $response = $this->actingAs($admin)->get($pendingTabUrl);
        $response->assertStatus(200);

        $response->assertSee($user1->name);
        $response->assertSee($request1->comment);

        $response->assertSee($user2->name);
        $response->assertSee($request2->comment);
    }

    //勤怠情報修正機能（管理者）--証人済みの修正申請が全て表示
    public function test_admin_can_view_approved_requests_of_all_users()
    {
        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');

        $user1 = User::where('email', 'user1@example.com')->first();
        $user2 = User::where('email', 'user2@example.com')->first();

        $attendance1 = \App\Models\AttendanceRecord::create([
            'user_id' => $user1->id,
            'date' => '2026-06-07',
            'clock_in' => '09:00:00'
        ]);
        $attendance2 = \App\Models\AttendanceRecord::create([
            'user_id' => $user2->id,
            'date' => '2026-06-07',
            'clock_in' => '09:00:00'
        ]);

        $request1 = new \App\Models\CorrectionRequest();
        $request1->attendance_record_id = $attendance1->id;
        $request1->user_id = $user1->id;
        $request1->requested_date = '2026-06-07';
        $request1->requested_clock_in = '09:00:00';
        $request1->requested_clock_out = '18:00:00';
        $request1->correction_status = '承認済み';
        $request1->correction_requested_at = '2026-06-07';
        $request1->comment = '修正の理由1';
        $request1->save();

        $request2 = new \App\Models\CorrectionRequest();
        $request2->attendance_record_id = $attendance2->id;
        $request2->user_id = $user2->id;
        $request2->requested_date = '2026-06-07';
        $request2->requested_clock_in = '09:00:00';
        $request2->requested_clock_out = '18:00:00';
        $request2->correction_status = '承認済み';
        $request2->correction_requested_at = '2026-06-07';
        $request2->comment = '修正の理由2';
        $request2->save();

        $approvedTabUrl = '/stamp_correction_request/list?status=approved';
        $response = $this->actingAs($admin)->get($approvedTabUrl);
        $response->assertStatus(200);

        $response->assertSee($user1->name);
        $response->assertSee($request1->comment);

        $response->assertSee($user2->name);
        $response->assertSee($request2->comment);
    }

    //勤怠情報修正機能（管理者）--修正申請の詳細内容が正しく表示
    public function test_admin_can_view_request_detail_and_see_matching_data()
    {
        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');

        $user1 = User::where('email', 'user1@example.com')->first();

        $attendance = \App\Models\AttendanceRecord::create([
            'user_id' => $user1->id,
            'date' => '2026-06-07',
            'clock_in' => '09:00:00'
        ]);

        $requestData = new \App\Models\CorrectionRequest();
        $requestData->attendance_record_id = $attendance->id;
        $requestData->user_id = $user1->id;
        $requestData->requested_date = '2026-06-07';
        $requestData->requested_clock_in = '09:30:00';
        $requestData->requested_clock_out = '18:30:00';
        $requestData->correction_status = '承認待ち';
        $requestData->correction_requested_at = '2026-06-07';
        $requestData->comment = '電車の遅延のため';
        $requestData->save();

        $adminRequestDetailUrl = '/admin/stamp_correction_request/approve/' . $requestData->id;
        $response = $this->actingAs($admin)->get($adminRequestDetailUrl);
        $response->assertStatus(200);

        $response->assertSee($user1->name);
        $response->assertSee('2026年  6月7日');
        $response->assertSee(\Carbon\Carbon::parse($requestData->requested_clock_in)->format('H:i'));
        $response->assertSee(\Carbon\Carbon::parse($requestData->requested_clock_out)->format('H:i'));
        $response->assertSee($requestData->comment);
    }

    //勤怠情報修正機能（管理者）--修正申請の承認処理が正しく行われる
        public function test_admin_can_approve_request_and_update_attendance_data()
    {
        \App\Models\User::saving(function () {
            return true;
        });

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, '管理者ユーザーが見つかりません。');

        $user1 = User::where('email', 'user1@example.com')->first();

        $attendance = \App\Models\AttendanceRecord::create([
            'user_id' => $user1->id,
            'date' => '2026-06-07',
            'clock_in' => '09:00:00',
            'clock_out' => null
        ]);

        $requestData = new \App\Models\CorrectionRequest();
        $requestData->attendance_record_id = $attendance->id;
        $requestData->user_id = $user1->id;
        $requestData->requested_date = '2026-06-07';
        $requestData->requested_clock_in = '10:00:00';
        $requestData->requested_clock_out = '19:00:00';
        $requestData->correction_status = '承認待ち';
        $requestData->correction_requested_at = '2026-06-07';
        $requestData->comment = '電車の遅延のため';
        $requestData->save();

        $approveUrl = '/admin/stamp_correction_request/approve/' . $requestData->id;
        $postResponse = $this->actingAs($admin)->patch($approveUrl);
        $postResponse->assertStatus(302);

        $this->assertDatabaseHas('correction_requests', [
            'id' => $requestData->id,
            'correction_status' => '承認済み',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'id'        => $attendance->id,
            'user_id'   => $user1->id,
            'clock_in'  => '10:00:00',
            'clock_out' => '19:00:00',
        ]);
    }
}