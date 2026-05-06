@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff-attendance-index.css') }}">
@endsection

@section('content')
<div class="staff-attendance-container">
    <h2 class="page-title">{{ $user->name }}さんの勤怠</h2>

    <!-- 月ナビゲーション -->
    <div class="month-nav">
        <a href="?month={{ $targetMonth->copy()->subMonth()->format('Y-m') }}" class="nav-btn">← 前月</a>
    <div class="date-nav__current">
        <form action="{{ url()->current() }}" method="GET" style="display: inline-flex; align-items: center;">
            <!-- 💡 アイコンの span を削除しました -->
            <input type="month" name="month" value="{{ $targetMonth->format('Y-m') }}" onchange="this.form.submit()" class="calendar-input">
        </form>
    </div>
        <a href="?month={{ $targetMonth->copy()->addMonth()->format('Y-m') }}" class="nav-btn">翌月 →</a>
    </div>

    <!-- 勤怠テーブル -->
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

        {{-- 出勤・退勤 (H:i形式) --}}
        <td>{{ $record && $record->clock_in ? \Carbon\Carbon::parse($record->clock_in)->format('H:i') : '' }}</td>
        <td>{{ $record && $record->clock_out ? \Carbon\Carbon::parse($record->clock_out)->format('H:i') : '' }}</td>

        {{-- 休憩合計 --}}
        <td>{{ $record && $record->total_break_time ? \Carbon\Carbon::parse($record->total_break_time)->format('H:i') : '' }}</td>

        {{-- 勤務合計 --}}
        <td>{{ $record && $record->total_time ? \Carbon\Carbon::parse($record->total_time)->format('H:i') : '' }}</td>

        <td>
            @if($record)
    <a href="{{ $record ? route('admin.attendance.edit', $record->id) : route('admin.attendance.create', ['user_id' => $user->id, 'date' => $formattedDate]) }}" class="detail-link">
        詳細
    </a>
            @endif
        </td>
    </tr>
    @endforeach
            </tbody>
        </table>
    </div>
    <!-- CSV出力ボタン -->
    <div class="footer-actions">
            <a href="{{ route('admin.attendance.csv', ['id' => $user->id, 'month' => $targetMonth->format('Y-m')]) }}" class="csv-btn">
        CSV出力
    </a>
    </div>
</div>
@endsection