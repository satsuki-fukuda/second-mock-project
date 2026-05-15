@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/detail.css') }}">
@endsection

@section('content')
<div class="detail-container">
    <div class="page-title">
        <span class="title-line"></span>
        <h1>勤怠詳細</h1>
    </div>

    {{-- 💡 修正ポイント1: IDの有無でアクションURLを動的に切り替える --}}
    <form action="{{ $attendance->id ? url('/attendance/update/' . $attendance->id) : url('/attendance/update') }}" method="POST">
        @csrf
        
        {{-- 💡 修正ポイント2: 未打刻データ作成用に、対象の日付を必ず隠しデータで送信する --}}
        <input type="hidden" name="date" value="{{ \Carbon\Carbon::parse($attendance->date)->format('Y-m-d') }}">

        <table class="detail-table">
            <tr>
                <th>名前</th>
                <td>{{ $attendance->user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                {{-- 💡 修正ポイント3: 文字列でもCarbonでも安全にフォーマットできるようにparseを挟む --}}
                <td class="date-text">{{ \Carbon\Carbon::parse($attendance->date)->isoFormat('YYYY年  M月D日') }}</td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td class="time-inputs">
                    @if($isPending)
                        {{ \Carbon\Carbon::parse($pendingRequest->requested_clock_in)->format('H:i') }}
                        <span> ～ </span>
                        {{ \Carbon\Carbon::parse($pendingRequest->requested_clock_out)->format('H:i') }}
                    @else
                        {{-- 💡 修正ポイント4: clock_in / clock_out が null でも parse で落ちないように三項演算子で対応 --}}
                        <input type="time" name="clock_in" value="{{ old('clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}">
                        <span> ～ </span>
                        <input type="time" name="end_time" value="{{ old('end_time', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}">
                        @error('end_time') <p class="error-msg">{{ $message }}</p> @enderror
                    @endif
                </td>
            </tr>

            @if($isPending)
                @foreach($pendingRequest->correctionBreaks as $index => $break)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td class="time-inputs">
                        {{ \Carbon\Carbon::parse($break->new_break_start)->format('H:i') }}
                        <span> ～ </span>
                        {{ \Carbon\Carbon::parse($break->new_break_end)->format('H:i') }}
                    </td>
                </tr>
                @endforeach
            @else
                @foreach($attendance->attendanceBreaks as $break)
                <tr>
                    <th>休憩{{ $loop->iteration }}</th>
                    <td class="time-inputs">
                        <input type="hidden" name="breaks[{{ $break->id }}][id]" value="{{ $break->id }}">
                        <input type="time" name="breaks[{{ $break->id }}][start]" 
                               value="{{ old("breaks.{$break->id}.start", \Carbon\Carbon::parse($break->break_start)->format('H:i')) }}">
                        <span> ～ </span>
                        <input type="time" name="breaks[{{ $break->id }}][end]" 
                               value="{{ old("breaks.{$break->id}.end", \Carbon\Carbon::parse($break->break_end)->format('H:i')) }}">
                        @error("breaks.{$break->id}.start") <p class="error-msg">{{ $message }}</p> @enderror
                        @error("breaks.{$break->id}.end") <p class="error-msg">{{ $message }}</p> @enderror
                    </td>
                </tr>
                @endforeach

                {{-- 新規休憩入力欄 --}}
                <tr>
                    <th>休憩{{ $attendance->attendanceBreaks ? $attendance->attendanceBreaks->count() + 1 : 1 }}</th>
                    <td class="time-inputs">
                        <input type="time" name="new_break_start" value="{{ old('new_break_start') }}">
                        <span> ～ </span>
                        <input type="time" name="new_break_end" value="{{ old('new_break_end') }}">
                        @error('new_break_start') <p class="error-msg">{{ $message }}</p> @enderror
                        @error('new_break_end') <p class="error-msg">{{ $message }}</p> @enderror
                    </td>
                </tr>
            @endif

            <tr>
                <th>備考</th>
                <td>
                    @if($isPending)
                        {{ $pendingRequest->comment }}
                    @else
                        <textarea name="note" rows="3">{{ old('note', $attendance->comment) }}</textarea>
                        @error('note') <p class="error-msg">{{ $message }}</p> @enderror
                    @endif
                </td>
            </tr>
        </table>

        @if($isPending)
            <p class="status-message">*承認待ちのため修正はできません。</p>
        @else
            <div class="button-area">
                <button type="submit" class="btn-update">修正</button>
            </div>
        @endif
    </form>
</div>
@endsection