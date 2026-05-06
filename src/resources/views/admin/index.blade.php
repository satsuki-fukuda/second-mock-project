@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <h1 class="page-title">{{ \Carbon\Carbon::parse($date)->format('Y年n月j日') }}の勤怠</h1>

    <div class="date-nav">
        <a href="{{ route('admin.attendance.list', ['date' => \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d')]) }}" class="date-nav__btn">&lt; 前日</a>
        <div class="date-nav__current">
            <form action="{{ route('admin.attendance.list') }}" method="GET" style="display: inline-flex; align-items: center;">
            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="calendar-input">
        </form>
        </div>
        <a href="{{ route('admin.attendance.list', ['date' => \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d')]) }}" class="date-nav__btn">翌日 &gt;</a>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $attendance)
    <tr>
        {{-- 名前 --}}
        <td>{{ $attendance->user->name }}</td>

        {{-- 出勤時間 (H:i) --}}
        <td>{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}</td>

        {{-- 退勤時間 (H:i) --}}
        <td>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '--:--' }}</td>

{{-- 休憩合計 --}}
<td>
    @if($attendance->total_break_time)
        {{ \Carbon\Carbon::parse($attendance->total_break_time)->format('H:i') }}
    @else
        0:00
    @endif
</td>

{{-- 勤務合計 --}}
<td>
    @if($attendance->total_time)
        {{ \Carbon\Carbon::parse($attendance->total_time)->format('H:i') }}
    @else
        0:00
    @endif
</td>

        {{-- 詳細リンク --}}
        <td>
            <a href="{{ route('admin.attendance.edit', $attendance->id) }}" class="detail-link">詳細</a>
        </td>
    </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">この日の勤怠データはありません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

