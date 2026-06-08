@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff-attendance-index.css') }}">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=calendar_month" />
@endsection

@section('content')
<div class="staff-attendance-container">
    <h1 class="page-title">{{ $user->name }}さんの勤怠</h1>

    <div class="month-nav">
        <a href="?month={{ $targetMonth->copy()->subMonth()->format('Y-m') }}" class="nav-btn">← 前月</a>
        <div class="date-nav__current">
            <form action="{{ url()->current() }}" method="GET" class="month-form">
                <div class="calendar-picker-wrapper">
                    <span class="material-symbols-outlined custom-calendar-icon">calendar_month</span>
                    <input type="month" name="month" value="{{ $targetMonth->format('Y-m') }}" onchange="this.form.submit()" class="calendar-input-hidden">
                </div>
                <span class="calendar-text-display">
                    {{ $targetMonth->format('Y/m') }}
                </span>
            </form>
        </div>
        <a href="?month={{ $targetMonth->copy()->addMonth()->format('Y-m') }}" class="nav-btn">翌月 →</a>
    </div>

    <div class="table-wrapper">
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
                @foreach($dates as $date)
                @php
                    $formattedDate = $date->format('Y-m-d');
                    $record = $attendances->get($formattedDate);
                @endphp
                <tr>
                    <td class="col-date">{{ $date->format('m/d') }}({{ $date->isoFormat('ddd') }})</td>
                    <td>{{ $record && $record->clock_in && $record->clock_in !== '00:00:00' ? \Carbon\Carbon::parse($record->clock_in)->format('H:i') : '' }}</td>
                    <td>{{ $record && $record->clock_out ? \Carbon\Carbon::parse($record->clock_out)->format('H:i') : '' }}</td>
                    <td>{{ $record && $record->total_break_time > 0 ? gmdate('H:i', $record->total_break_time) : '' }}</td>
                    <td>{{ $record && $record->total_time > 0 ? gmdate('H:i', $record->total_time) : '' }}</td>
                    <td>
                        @if($record)
                            <a href="{{ route('admin.attendance.edit', $record->id) }}" class="detail-link">詳細</a>
                        @else
                            <a href="{{ route('admin.attendance.create', ['user_id' => $user->id, 'date' => $formattedDate]) }}" class="detail-link">詳細</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer-actions">
            <a href="{{ route('admin.attendance.csv', ['id' => $user->id, 'month' => $targetMonth->format('Y-m')]) }}" class="csv-btn">CSV出力</a>
    </div>
</div>
@endsection