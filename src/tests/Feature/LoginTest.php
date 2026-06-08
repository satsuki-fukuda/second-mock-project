<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    //ログイン認証機能（一般ユーザー）--メール未入力バリデーション
    public function test_login_user_validate_email()
    {
        $response = $this->post('/login', [
            'email' => "",
            'password' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');

        $errors = session('errors');
        $this->assertEquals('メールアドレスを入力してください', $errors->first('email'));
    }

    //ログイン認証機能（一般ユーザー）--パスワード未入力バリデーション
    public function test_login_user_validate_password()
    {
        $response = $this->post('/login', [
            'email' => "user1@example.com",
            'password' => "",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');

        $errors = session('errors');
        $this->assertEquals('パスワードを入力してください', $errors->first('password'));
    }

    //ログイン認証機能（一般ユーザー）--入力情報バリデーション
    public function test_login_user_validate_wrong_credentials()
    {
        $response = $this->post('/login', [
            'email' => "notfound@example.com",
            'password' => "wrong-password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');

        $errors = session('errors');
        $this->assertEquals('ログイン情報が登録されていません', $errors->first('email'));
    }

    //ログイン認証機能（管理者）--メール未入力バリデーション
    public function test_login_admin_user_validate_email()
    {
        $response = $this->post('/login', [
            'email' => "",
            'password' => "password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    //ログイン認証機能（管理者）--パスワード未入力バリデーション
    public function test_login_admin_user_validate_password()
    {
        $response = $this->post('/login', [
            'email' => "admin@example.com",
            'password' => "",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    //ログイン認証機能（管理者）--入力情報バリデーション
    public function test_login_admin_user_validate_wrong_credentials()
    {
        $response = $this->post('/login', [
            'email' => "notfound@example.com",
            'password' => "wrong-password",
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);
    }

}
