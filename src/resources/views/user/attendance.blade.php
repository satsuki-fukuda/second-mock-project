@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/attendance.css') }}">
@endsection

@section('content')
<div class="main">
    <div class="card">
        <p class="status-badge">{{ $status }}</p>
        <h1 class="date">{{ $displayDate }}</h1>
        <div class="time"  id="realtime-time">{{ $displayTime }}</div>
            <div class="button-group">
            @if($status === '勤務外')
                <form action="/attendance/work-start" method="POST">
                @csrf
                    <button type="submit" class="btn btn-black">出勤</button>
                </form>
            @elseif($status === '出勤中')
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
                <form action="/attendance/rest-end" method="POST">
                @csrf
                    <button type="submit" class="btn btn-white">休憩戻</button>
                </form>
            @elseif($status === '退勤済')
                <p class="finish-message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const timeDisplay = document.getElementById('realtime-time');

    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        timeDisplay.textContent = `${hours}:${minutes}`;
    }
    setInterval(updateTime, 1000);
});
</script>
@endsection