@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/attendance.css') }}">
@endsection

@section('content')
<div class="main">
<div class="card">
    {{-- ステータスバッジの出し分け --}}
    <p class="status-badge">{{ $status }}</p>

    <h1 class="date">{{ $displayDate }}</h1>
    <div class="time">{{ $displayTime }}</div>

    <div class="button-group">
        @if($status === '勤務外')
            {{-- 出勤ボタンのみ --}}
            <form action="/attendance/work-start" method="POST">
                @csrf
                <button type="submit" class="btn btn-black">出勤</button>
            </form>

        @elseif($status === '出勤中')
            {{-- 退勤（黒）と 休憩入（白）を横並び --}}
            <div class="button-container">
                <form action="/attendance/work-end" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-black">退勤</button>
                </form>
                <form action="/attendance/rest-start" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-white">休憩入</button>
                </form>
            </div>

        @elseif($status === '休憩中')
            {{-- 休憩戻（白）のみ --}}
            <form action="/attendance/rest-end" method="POST">
                @csrf
                <button type="submit" class="btn btn-white">休憩戻</button>
            </form>

        @elseif($status === '退勤済')
            {{-- ボタンなし・メッセージ --}}
            <p class="finish-message">お疲れ様でした。</p>
        @endif
    </div>
</div>
</div>
@endsection