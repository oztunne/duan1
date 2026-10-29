@extends('layout')
@section('title','Tài khoản — Dự án 1')
@section('content')
<main class="auth"><div class="card"><span class="eyebrow">Tài khoản của bạn</span><h1>Đổi mật khẩu</h1><p>{{ auth()->user()->name }}<br>{{ auth()->user()->email }}</p><form method="post" action="{{ route('password.update') }}">@csrf @method('PUT')<label for="current_password">Mật khẩu hiện tại</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required><label for="password">Mật khẩu mới</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required><p class="note">Ít nhất 12 ký tự, có chữ và số.</p><label for="password_confirmation">Nhập lại mật khẩu mới</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button class="wide">Lưu mật khẩu</button></form></div></main>
@endsection
