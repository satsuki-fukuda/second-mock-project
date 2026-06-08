@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/detail.css') }}">
@endsection

@section('content')
<div class="detail-container">
    <div class="page-title">
        <span class="title-line"></span>
        <h1>勤怠詳細</h1>
    </div>

    @if(session('message'))
        <p class="success-msg">{{ session('message') }}</p>
    @endif
    <form action="{{ $attendance->id ? url('/admin/attendance/update/' . $attendance->id) : url('/admin/attendance/update') }}" method="POST">
        @csrf
        @method('PATCH')

        <table class="detail-table">
            <tr>
                <th>名前</th>
                <td>{{ $attendance->user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td class="date-text">{{ \Carbon\Carbon::parse($attendance->date)->isoFormat('YYYY年  M月D日') }}</td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td class="time-inputs">
                    <input type="time" name="clock_in" value="{{ old('clock_in', \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')) }}" {{ $isPending ? 'disabled' : '' }}>
                    <span> ～ </span>
                    <input type="time" name="end_time" value="{{ old('end_time', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}" {{ $isPending ? 'disabled' : '' }}>
                    @error('clock_in') <p class="error-msg">{{ $message }}</p> @enderror
                    @error('end_time') <p class="error-msg">{{ $message }}</p> @enderror
                </td>
            </tr>

            @foreach($attendance->attendanceBreaks as $index => $break)
            <tr>
                <th>休憩{{ $index + 1 }}</th>
                <td class="time-inputs">
                    <input type="hidden" name="breaks[{{ $break->id }}][id]" value="{{ $break->id }}">
                    <input type="time" name="breaks[{{ $break->id }}][start]"
                        value="{{ old('breaks.{$break->id}.start', \Carbon\Carbon::parse($break->break_start)->format('H:i')) }}" {{ $isPending ? 'disabled' : '' }}>
                    <span> ～ </span>
                    <input type="time" name="breaks[{{ $break->id }}][end]"
                        value="{{ old('breaks.{$break->id}.end', \Carbon\Carbon::parse($break->break_end)->format('H:i')) }}" {{ $isPending ? 'disabled' : '' }}>
                    @error("breaks.{$break->id}.start") <p class="error-msg">{{ $message }}</p> @enderror
                    @error("breaks.{$break->id}.end") <p class="error-msg">{{ $message }}</p> @enderror
                </td>
            </tr>
            @endforeach

            <tr>
                <th>休憩{{ $attendance->attendanceBreaks->count() + 1 }}</th>
                <td class="time-inputs">
                    <input type="time" name="new_break_start" value="{{ old('new_break_start') }}" {{ $isPending ? 'disabled' : '' }}>
                    <span> ～ </span>
                    <input type="time" name="new_break_end" value="{{ old('new_break_end') }}" {{ $isPending ? 'disabled' : '' }}>
                    @error('new_break_start') <p class="error-msg">{{ $message }}</p> @enderror
                    @error('new_break_end') <p class="error-msg">{{ $message }}</p> @enderror
                </td>
            </tr>

            <tr>
                <th>備考</th>
                <td>
                    <textarea name="note" rows="3" {{ $isPending ? 'disabled' : '' }}>{{ old('note', $attendance->comment) }}</textarea>
                    @error('note') <p class="error-msg">{{ $message }}</p> @enderror
                </td>
            </tr>
        </table>
        <div class="button-area">
            @if(!$isPending)
            <button type="submit" class="btn-update">修正</button>
            @else
            <p class="status-message">※承認待ちのため修正はできません。</p>
            @endif
        </div>
    </form>
</div>
@endsection