<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

class MailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    //メール認証機能--会員登録後の認証メール送信
    public function test_registration_sends_verification_email()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(302);
        $user = User::where('email', 'newuser@example.com')->first();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    //メール認証機能--ボタン押下でメール認証サイトに遷移
    public function test_click_verification_button_navigates_to_verification_url()
    {
        Notification::fake();

        $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'newuser@example.com')->first();
        $verificationUrl = '';

        Notification::assertSentTo($user, VerifyEmail::class, function ($notification) use ($user, &$verificationUrl) {
            $verificationUrl = $notification->toMail($user)->actionUrl;
            return true;
        });

        $response = $this->actingAs($user)->get($verificationUrl);
        $response->assertStatus(302);
    }

    //メール認証機能--認証サイトにてメール認証完了後勤怠登録画面に遷移
    public function test_email_verification_completes_and_redirects_to_attendance_screen()
    {
        Notification::fake();

        $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'newuser@example.com')->first();

        $this->assertFalse($user->hasVerifiedEmail());

        $verificationUrl = '';
        Notification::assertSentTo($user, VerifyEmail::class, function ($notification) use ($user, &$verificationUrl) {
            $verificationUrl = $notification->toMail($user)->actionUrl;
            return true;
        });

        $response = $this->actingAs($user)->get($verificationUrl);
        $response->assertRedirect('/login?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}

