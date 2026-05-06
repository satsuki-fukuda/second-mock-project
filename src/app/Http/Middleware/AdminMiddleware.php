<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
            // ログインしていて、かつ is_admin が true (1) かを判定
    if (auth()->check() && auth()->user()->is_admin) {
        return $next($request);
    }

    // 管理者でなければ一般ユーザー用のホームへ飛ばす
    return redirect('/attendance')->with('error', '管理者権限がありません。');

    }
}
