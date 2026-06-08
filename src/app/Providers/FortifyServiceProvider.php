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
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        $this->app->bind(FortifyLoginRequest::class, MyLoginRequest::class);
        Fortify::authenticateUsing(function (Request $request) {
            $user = \App\Models\User::where('email', $request->email)->first();
            if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                if ($user->is_admin) {
                    session(['url.intended' => url('/admin/attendance/list')]);
                    } else {
                        session(['url.intended' => url('/attendance')]);
                    }
                return $user;
            }
            return null;
        });

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function ($event) {
                $user = $event->user;
                if (request()->is('register*')) {
                    return;
                }
                if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                    $user->sendEmailVerificationNotification();
                }
            }
        );

        Fortify::registerView(fn() => view('user.register'));
        Fortify::loginView(fn() => view('user.login'));
        Fortify::verifyEmailView(fn() => view('user.verify-email'));
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(10)->by($email . $request->ip());
        });

        config(['fortify.home' => '/login']);
    }
}
