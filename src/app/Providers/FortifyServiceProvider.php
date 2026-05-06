<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use App\Http\Requests\LoginRequest as MyLoginRequest;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
                    // 👇 ここから追加：アクションの紐付け
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        // 👆 ここまで追加

        $this->app->bind(FortifyLoginRequest::class, MyLoginRequest::class);
  Fortify::authenticateUsing(function (Request $request) {

        // 1. ユーザーをメールアドレスで検索
        $user = \App\Models\User::where('email', $request->email)->first();

        // 2. パスワードの一致確認
        if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            
            // 3. 管理者か判定してリダイレクト先をセッションに予約する
            // ここでは is_admin カラムを想定しています
            if ($user->is_admin) {
                session(['url.intended' => url('/admin/attendance/list')]);
            } else {
                session(['url.intended' => url('/attendance')]);
            }

            return $user;
        }
        return null;
    });

    // --- ビューの設定 ---
    Fortify::registerView(fn() => view('user.register'));
    Fortify::loginView(fn() => view('user.login'));
    Fortify::verifyEmailView(fn() => view('user.verify-email'));


        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(10)->by($email . $request->ip());
        });

    // ログアウト後の遷移先を /login に指定
    config(['fortify.home' => '/login']); 

        Fortify::verifyEmailView(function () {
        return view('user.verify-email'); // 保存したファイルパスに合わせて変更
    });


    }

}
