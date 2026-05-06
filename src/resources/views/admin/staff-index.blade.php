@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff-index.css') }}">
@endsection

@section('content')
<div class="staff-container">
    <h2 class="page-title">スタッフ一覧</h2>

    <div class="table-wrapper">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        {{-- スタッフごとの月次勤怠詳細ページへのリンク --}}
                        <a href="{{ route('admin.attendance.staff', $user->id) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection