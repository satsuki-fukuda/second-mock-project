<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserAttendanceTest extends TestCase
{
    use RefreshDatabase;

    //日時取得機能
    public function test_attendance_screen_displays_current_datetime_correctly()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $expectedDate = $now->isoFormat('YYYY年M月D日(ddd)');
        $expectedTime = $now->format('H:i');

        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);
        Carbon::setTestNow();
    }

   //ステータス確認機能--勤務外
    public function test_attendance_screen_displays_out_of_work_status_for_new_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

   //ステータス確認機能--出勤中
    public function test_attendance_screen_displays_working_status_for_clocked_in_user()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $user = User::factory()->create();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = $now->format('Y-m-d');
        $attendance->clock_in = $now->format('H:i:s');
        $attendance->save();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');
        \Carbon\Carbon::setTestNow();
    }

   //ステータス確認機能--休憩中
    public function test_attendance_screen_displays_resting_status_for_user_on_break()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $user = User::factory()->create();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = $now->format('Y-m-d');
        $attendance->clock_in = $now->copy()->subHours(2)->format('H:i:s');
        $attendance->save();

        $break = new \App\Models\AttendanceBreak();
        $break->attendance_record_id = $attendance->id;
        $break->break_start = $now->copy()->subHour()->format('H:i:s');
        $break->save();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中');
        \Carbon\Carbon::setTestNow();
    }

   //ステータス確認機能--退勤済
    public function test_attendance_screen_displays_clocked_out_status_for_user()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $user = User::factory()->create();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = $now->format('Y-m-d');
        $attendance->clock_in = $now->copy()->subHours(9)->format('H:i:s');
        $attendance->clock_out = $now->copy()->subHours(1)->format('H:i:s');
        $attendance->save();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤済');
        \Carbon\Carbon::setTestNow();
    }

   //出勤機能--出勤ボタン機能
    public function test_user_can_clock_in_and_status_changes_to_working()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('action="/attendance/work-start"', false);
        $response->assertSee('出勤');

        $postResponse = $this->actingAs($user)->post('/attendance/work-start');
        $postResponse->assertStatus(302);
        $postResponse->assertRedirect('/attendance');

        $finalResponse = $this->actingAs($user)->get('/attendance');
        $finalResponse->assertStatus(200);
        $finalResponse->assertSee('出勤中');
    }


   //出勤機能--出勤は1日１回のみ
   public function test_clocked_out_user_cannot_see_clock_in_button()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $user = User::factory()->create();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = $now->format('Y-m-d');
        $attendance->clock_in = $now->copy()->subHours(9)->format('H:i:s');
        $attendance->clock_out = $now->copy()->subHours(1)->format('H:i:s');
        $attendance->save();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertDontSee('action="/attendance/work-start"', false);
        $response->assertDontSee('出勤');
        \Carbon\Carbon::setTestNow();
    }

   //出勤機能--出勤時刻が勤怠一覧画面で確認できる
    public function test_clock_in_records_correct_date_on_attendance_list_screen()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/attendance/work-start');
        $response->assertStatus(302);

        $listResponse = $this->actingAs($user)->get('/attendance/list');
        $listResponse->assertStatus(200);

        $expectedDate = $now->isoFormat('MM/DD(ddd)');
        $listResponse->assertSee($expectedDate);
        $expectedTime = $now->format('H:i');
        $listResponse->assertSee($expectedTime);
        \Carbon\Carbon::setTestNow();
    }

   //休憩機能--休憩ボタン機能
    public function test_working_user_can_start_rest(): void
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $user = User::factory()->create();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = $now->format('Y-m-d');
        $attendance->clock_in = $now->copy()->subHours(2)->format('H:i:s');
        $attendance->save();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');
        $response->assertSee('休憩入');
        $response->assertSee('/attendance/rest-start');

        $actionResponse = $this->actingAs($user)->post('/attendance/rest-start');
        $actionResponse->assertRedirect('/attendance');
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendance->id,
            'break_end' => null,
        ]);

        $finalResponse = $this->actingAs($user)->get('/attendance');
        $finalResponse->assertSee('休憩中');
        $finalResponse->assertSee('休憩戻');
        \Carbon\Carbon::setTestNow();
    }

   //休憩機能--休憩は1日何回でも
    public function test_user_can_start_and_end_rest_and_status_returns_to_working()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $user = User::factory()->create();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = $now->format('Y-m-d');
        $attendance->clock_in = $now->copy()->subHours(2)->format('H:i:s');
        $attendance->save();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('action="/attendance/work-end"', false);
        $response->assertSee('action="/attendance/rest-start"', false);
        $response->assertSee('休憩入');

        $postRestStartResponse = $this->actingAs($user)->post('/attendance/rest-start');
        $postRestStartResponse->assertStatus(302);
        $postRestStartResponse->assertRedirect('/attendance');

        $restingResponse = $this->actingAs($user)->get('/attendance');
        $restingResponse->assertStatus(200);
        $restingResponse->assertSee('休憩中');
        $restingResponse->assertSee('action="/attendance/rest-end"', false);
        $restingResponse->assertSee('休憩戻');

        $postRestEndResponse = $this->actingAs($user)->post('/attendance/rest-end');
        $postRestEndResponse->assertStatus(302);
        $postRestEndResponse->assertRedirect('/attendance');

        $finalResponse = $this->actingAs($user)->get('/attendance');
        $finalResponse->assertStatus(200);
        $finalResponse->assertSee('出勤中');
        $finalResponse->assertSee('action="/attendance/rest-start"', false);
        $finalResponse->assertSee('休憩入');
        \Carbon\Carbon::setTestNow();
    }

   //休憩機能--休憩戻ボタン機能
    public function test_user_starts_rest_shows_end_button_and_returns_to_working_status()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $user = User::factory()->create();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = $now->format('Y-m-d');
        $attendance->clock_in = $now->copy()->subHours(2)->format('H:i:s');
        $attendance->save();

        $postRestStart = $this->actingAs($user)->post('/attendance/rest-start');
        $postRestStart->assertStatus(302);
        $postRestStart->assertRedirect();

        $restInResponse = $this->actingAs($user)->get('/attendance');
        $restInResponse->assertStatus(200);
        $restInResponse->assertSee('action="/attendance/rest-end"', false);
        $restInResponse->assertSee('休憩戻');

        $postRestEnd = $this->actingAs($user)->post('/attendance/rest-end');
        $postRestEnd->assertStatus(302);
        $postRestEnd->assertRedirect();

        $finalResponse = $this->actingAs($user)->get('/attendance');
        $finalResponse->assertStatus(200);
        $finalResponse->assertSee('出勤中');
        \Carbon\Carbon::setTestNow();
    }

   //休憩機能--休憩戻は1日何回でも
    public function test_user_can_take_rest_multiple_times_and_status_changes_to_resting()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $user = User::factory()->create();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = $now->format('Y-m-d');
        $attendance->clock_in = $now->copy()->subHours(4)->format('H:i:s');
        $attendance->save();

        $postRestStart1 = $this->actingAs($user)->post('/attendance/rest-start');
        $postRestStart1->assertStatus(302);
        $postRestStart1->assertRedirect();

        $postRestEnd1 = $this->actingAs($user)->post('/attendance/rest-end');
        $postRestEnd1->assertStatus(302);
        $postRestEnd1->assertRedirect();

        $beforeSecondRestResponse = $this->actingAs($user)->get('/attendance');
        $beforeSecondRestResponse->assertSee('action="/attendance/rest-start"', false);

        $postRestStart2 = $this->actingAs($user)->post('/attendance/rest-start');
        $postRestStart2->assertStatus(302);
        $postRestStart2->assertRedirect();

        $finalResponse = $this->actingAs($user)->get('/attendance');
        $finalResponse->assertStatus(200);
        $finalResponse->assertSee('休憩中');
        $finalResponse->assertSee('action="/attendance/rest-end"', false);
        $finalResponse->assertSee('休憩戻');
        \Carbon\Carbon::setTestNow();
    }

   //休憩機能--休憩時刻が勤怠一覧画面で確認できる
    public function test_user_rest_date_and_time_are_accurately_recorded_in_attendance_list()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $user = User::factory()->create();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = $now->format('Y-m-d');
        $attendance->clock_in = $now->copy()->subHour()->format('H:i:s');
        $attendance->save();

        $postRestStart = $this->actingAs($user)->post('/attendance/rest-start');
        $postRestStart->assertStatus(302);

        $now->addMinutes(15);
        \Carbon\Carbon::setTestNow($now);

        $postRestEnd = $this->actingAs($user)->post('/attendance/rest-end');
        $postRestEnd->assertStatus(302);

        $listResponse = $this->actingAs($user)->get('/attendance/list');
        $listResponse->assertStatus(200);

        $expectedDate = $now->isoFormat('MM/DD(ddd)');
        $listResponse->assertSee($expectedDate);
        $listResponse->assertSee('00:15');
        \Carbon\Carbon::setTestNow();
    }

   //退勤機能--退勤ボタン機能
    public function test_working_user_can_clock_out_and_status_changes_to_completed()
    {
        $now = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($now);
        $user = User::factory()->create();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = $now->format('Y-m-d');
        $attendance->clock_in = $now->copy()->subHours(8)->format('H:i:s');
        $attendance->save();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('action="/attendance/work-end"', false);
        $response->assertSee('退勤');

        $postResponse = $this->actingAs($user)->post('/attendance/work-end');
        $postResponse->assertStatus(302);
        $postResponse->assertRedirect();

        $finalResponse = $this->actingAs($user)->get('/attendance');
        $finalResponse->assertStatus(200);
        $finalResponse->assertSee('退勤済');
        $finalResponse->assertSee('お疲れ様でした。');
        \Carbon\Carbon::setTestNow();
    }

   //退勤機能--退勤時刻が勤怠一覧画面で確認できる
    public function test_user_clock_out_date_and_time_are_accurately_recorded_in_attendance_list()
    {
        $clockInTime = \Carbon\Carbon::now()->subHours(8);
        $clockOutTime = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($clockOutTime);

        $user = User::factory()->create();

        $attendance = new \App\Models\AttendanceRecord();
        $attendance->user_id = $user->id;
        $attendance->date = \Carbon\Carbon::today()->format('Y-m-d');
        $attendance->clock_in = $clockInTime->format('H:i:s');
        $attendance->save();

        \App\Models\User::saving(function () {
            return true;
        });

        $postWorkEnd = $this->actingAs($user)->post('/attendance/work-end');
        $postWorkEnd->assertStatus(302);
        $postWorkEnd->assertRedirect();

        $listResponse = $this->actingAs($user)->get('/attendance/list');
        $listResponse->assertStatus(200);

        $expectedDate = $clockOutTime->isoFormat('MM/DD(ddd)');
        $listResponse->assertSee($expectedDate);
        $expectedEndTime = $clockOutTime->format('H:i');
        $listResponse->assertSee($expectedEndTime);
        \Carbon\Carbon::setTestNow();
    }
}
