@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/correction-request-index.css') }}">
@endsection

@section('content')
<div class="requests-container">
    {{-- タイトル --}}
    <div class="page-header">
        <h1 class="page-title">申請一覧</h1>
    </div>

    {{-- タブメニュー --}}
    <div class="tab-menu">
        <a href="?status=pending" class="tab-item {{ request('status') != 'approved' ? 'active' : '' }}">承認待ち</a>
        <a href="?status=approved" class="tab-item {{ request('status') == 'approved' ? 'active' : '' }}">承認済み</a>
</div>
    </div>

    {{-- テーブルカード --}}
    <div class="table-card">
        <table class="requests-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $request)
                <tr>
                    <td>{{ $request->correction_status }}</td>
                    <td>{{ $request->user->name }}</td>
                    <td>{{ $request->requested_date->format('Y/m/d') }}</td>
                    <td>{{ $request->comment }}</td>
                    <td>{{ $request->correction_requested_at->format('Y/m/d') }}</td>
                    <td><a href="/attendance/detail/{{ $request->attendance_record_id }}" class="detail-link">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection