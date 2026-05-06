@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/index.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <header class="attendance-header">
        <h1>勤怠一覧</h1>
        <div class="month-selector">
<!-- 前月・翌月のリンクも動的に生成 -->
        <a href="?month={{ \Carbon\Carbon::parse($month)->subMonth()->format('Y-m') }}" class="month-nav-link">← 前月</a>
<!-- カレンダー機能を実装 -->
    <form action="" method="GET" style="display: inline-flex; align-items: center;">
        <input type="month" name="month" value="{{ \Carbon\Carbon::parse($month)->format('Y-m') }}" onchange="this.form.submit()" class="calendar-input">
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
            {{-- 日付は $date から生成 --}}
            <td>{{ \Carbon\Carbon::parse($date)->locale('ja')->isoFormat('MM/DD(ddd)') }}</td>

            @if($attendance)
                {{-- データがある場合 --}}
                <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
            {{-- 秒数(整数)を H:i 形式に変換して表示 --}}
            <td>{{ $attendance->total_break_time ? gmdate('H:i', $attendance->total_break_time) : '0:00' }}</td>
            <td>{{ $attendance->total_time ? gmdate('H:i', $attendance->total_time) : '0:00' }}</td>
                <td><a href="{{ route('attendance.detail', ['id' => $attendance->id]) }}" class="detail-link">詳細</a></td>
            @else
                {{-- データがない日は空欄（またはハイフンなど） --}}
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