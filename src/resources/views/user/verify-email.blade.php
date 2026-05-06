@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-email__content">
  <div class="verify-email__message">
    <p>登録していただいたメールアドレスに認証メールを送付しました。</p>
    <p>メール認証を完了してください。</p>
  </div>

  <div class="verify-email__button">
    <a href="http://localhost:8025" target="_blank" class="verify-email__button-link">認証はこちらから</a>
  </div>

  <div class="verify-email__resend">
    <form class="resend__form" method="POST" action="{{ route('verification.send') }}">
    @csrf
      <button type="submit" class="resend__link-button">
        認証メールを再送する
      </button>
    </form>
  </div>
</div>
@endsection