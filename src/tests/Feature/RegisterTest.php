<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;
    //認証機能（一般ユーザー）--名前未入力バリデーション
    public function test_register_user_validate_name()
    {
        $response = $this->post('/register', [
            'name' => "",
            'email' => "test1@example.com",
            'password' => "password",
            'password_confirmation' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');

        $errors = session('errors');
        $this->assertEquals('お名前を入力してください', $errors->first('name'));
    }

    //認証機能（一般ユーザー）--メール未入力バリデーション
    public function test_register_user_validate_email()
    {
        $response = $this->post('/register', [
            'name' => "test",
            'email' => "",
            'password' => "password",
            'password_confirmation' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');

        $errors = session('errors');
        $this->assertEquals('メールアドレスを入力してください', $errors->first('email'));
    }

    //認証機能（一般ユーザー）--パスワード8字未満バリデーション
    public function test_register_user_validate_password_under7()
    {
        $response = $this->post('/register', [
            'name' => "test",
            'email' => "test1@example.com",
            'password' => "passwor",
            'password_confirmation' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');

        $errors = session('errors');
        $this->assertEquals('パスワードは８文字以上で入力してください', $errors->first('password'));
    }

    //認証機能（一般ユーザー）--パスワード不一致バリデーション
    public function test_register_user_validate_password_confirmation()
    {
        $response = $this->post('/register', [
            'name' => "test",
            'email' => "test1@example.com",
            'password' => "password",
            'password_confirmation' => "password1",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password_confirmation');

        $errors = session('errors');
        $this->assertEquals('パスワードと一致しません', $errors->first('password_confirmation'));
    }

    //認証機能（一般ユーザー）--パスワード未入力バリデーション
    public function test_register_user_validate_password()
    {
        $response = $this->post('/register', [
            'name' => "test",
            'email' => "test1@example.com",
            'password' => "",
            'password_confirmation' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');

        $errors = session('errors');
        $this->assertEquals('パスワードを入力してください', $errors->first('password'));
    }

    //認証機能（一般ユーザー）--登録処理
    public function test_register_user()
    {
        config(['fortify.home' => '/email/verify']);
        $email = "test_" . uniqid() . "@example.com";
        $response = $this->post('/register', [
            'name' => "test",
            'email' => $email,
            'password' => "password",
            'password_confirmation' => "password",
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);
    }
}
