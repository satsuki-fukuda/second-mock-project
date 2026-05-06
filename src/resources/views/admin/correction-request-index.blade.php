@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/correction-request-index.css') }}">
@endsection

@section('content')
<div class="app-container">
    <h1 class="page-title">申請一覧</h1>

    <!-- タブ切り替え -->
    <div class="tab-nav">
        <a href="{{ route('admin.application.index', ['status' => 'pending']) }}" 
           class="tab-item {{ $status == 'pending' ? 'is-active' : '' }}">承認待ち</a>
        <a href="{{ route('admin.application.index', ['status' => 'approved']) }}" 
           class="tab-item {{ $status == 'approved' ? 'is-active' : '' }}">承認済み</a>
    </div>

    <!-- 申請テーブル -->
    <div class="table-wrapper">
        <table class="app-table">
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
                @foreach($applications as $app)
                <tr>
                    <td>{{ $app->correction_status }}</td>
                    <td>{{ $app->user->name }}</td>
                    <td>{{ $app->requested_date->format('Y/m/d') }}</td>
                    <td class="reason-cell">{{ $app->comment }}</td>
                    <td>{{ $app->created_at->format('Y/m/d') }}</td>
                    <td>
                        <a href="{{ route('admin.application.show', $app->id) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection