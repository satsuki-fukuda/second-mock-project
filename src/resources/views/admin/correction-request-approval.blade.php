@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/correction-request-approval.css') }}">
@endsection

@section('content')
<div class="detail-container">
    <div class="page-title">
        <span class="title-line"></span>
        <h1>勤怠詳細</h1>
    </div>

    <div class="detail-table-wrapper">
        <table class="detail-table">
            <tr>
                <th>名前</th>
                <td>{{ $application->user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td class="date-text">
                    {{ \Carbon\Carbon::parse($application->date)->isoFormat('YYYY年  M月D日') }}
                </td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td class="time-inputs">
                    {{ $application->start_time }}
                    <span> ～ </span>
                    {{ $application->end_time }}
                </td>
            </tr>

            {{-- 休憩部分のループ表示 --}}
            {{-- コントローラーでセットした個別変数ではなく、リレーションを直接回すと正確です --}}
            @foreach($application->correctionBreaks as $index => $break)
            <tr>
                <th>休憩{{ $index + 1 }}</th>
                <td class="time-inputs">
                    {{ \Carbon\Carbon::parse($break->new_break_start)->format('H:i') }}
                    <span> ～ </span>
                    {{ \Carbon\Carbon::parse($break->new_break_end)->format('H:i') }}
                </td>
            </tr>
            @endforeach

            <tr>
                <th>備考</th>
                <td>
                    {{ $application->comment }}
                </td>
            </tr>
        </table>

        <div class="button-area">
            @if($application->status === 'approved')
                <button type="button" class="btn-approve" disabled>承認済み</button>
            @else
                <form action="{{ route('admin.application.approve', $application->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-approve">承認</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection