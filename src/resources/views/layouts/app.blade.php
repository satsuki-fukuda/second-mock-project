<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>coachtech</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header-inner">
            <div class="header-logo"> <img src="{{ asset('img/COACHTECHヘッダーロゴ.png') }}" alt="COACHTECH" class="logo-img"></div>
        </div>

        @unless(Route::is('login', 'register', 'email'))

        <!-- ナビゲーション -->
        <nav class="header-nav">
            <ul class="nav-list">
                        @auth
            @if(auth()->user()->is_admin)
                <!-- 管理者用ナビ -->
                <li><a href="/admin/attendance/list" class="nav-link">勤怠一覧</a></li>
                <li><a href="/admin/staff/list" class="nav-link">スタッフ一覧</a></li>
                <li><a href="/stamp_correction_request/list" class="nav-link">申請一覧</a></li>
            @else
                <!-- 一般ユーザー用ナビ -->
            <li><a href="/attendance" class="nav-link">勤怠</a></li>
            <li><a href="/attendance/list" class="nav-link">勤怠一覧</a></li>
            <li><a href="/attendance/correction-requests" class="nav-link">申請</a></li>
            @endif
                <li>
                    <form action="/logout" method="POST" class="nav-form">
                    @csrf
                    <button type="submit" class="nav-link-btn">ログアウト</button>
                    </form>
                </li>
                @endauth
            </ul>
        </nav>
        @endunless
    </header>

    <main>
        @yield('content')
    </main>
</body>

</html>