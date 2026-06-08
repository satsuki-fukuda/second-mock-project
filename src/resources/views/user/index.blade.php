@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/index.css') }}">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=calendar_month" />
@endsection

@section('content')
<div class="attendance-container">
    <header class="attendance-header">
        <h1>勤怠一覧</h1>
        <div class="month-selector">
            <a href="?month={{ \Carbon\Carbon::parse($month)->subMonth()->format('Y-m') }}" class="month-nav-link">← 前月</a>
                <form action="" method="GET" class="month-form">
                    <div class="calendar-picker-wrapper">
                        <span class="material-symbols-outlined custom-calendar-icon">calendar_month</span>
                        <input type="month" name="month" value="{{ \Carbon\Carbon::parse($month)->format('Y-m') }}" onchange="this.form.submit()" class="calendar-input-hidden">
                    </div>
                    <span class="calendar-text-display">
                        {{ \Carbon\Carbon::parse($month)->format('Y/m') }}
                    </span>
                </form>
            <a href="?month={{ \Carbon\Carbon::parse($month)->addMonth()->format('Y-m') }}" class="month-nav-link">翌月 →</a>
        </div>
    </header>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $date => $attendance)
            <tr>
                <td>{{ \Carbon\Carbon::parse($date)->locale('ja')->isoFormat('MM/DD(ddd)') }}</td>
            @if($attendance)
                <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                <td>{{ $attendance->total_break_time ? sprintf('%d:%02d', floor($attendance->total_break_time / 3600), ($attendance->total_break_time / 60) % 60) : '0:00' }}</td>
                <td>{{ $attendance->total_time ? sprintf('%d:%02d', floor($attendance->total_time / 3600), ($attendance->total_time / 60) % 60) : '0:00' }}</td>
                <td><a href="{{ route('attendance.detail', ['id' => $attendance->id]) }}" class="detail-link">詳細</a></td>
            @else
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td><a href="{{ route('attendance.detail', ['date' => $date]) }}" class="detail-link">詳細</a></td>
            @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection