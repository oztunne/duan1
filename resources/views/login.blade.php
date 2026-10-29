@extends('layout')
@section('title','Đăng nhập — Dự án 1')
@section('content')
<main class="auth"><div class="card"><span class="eyebrow">Chào mừng trở lại</span><h1>Vào không gian<br>của bạn.</h1><p>Dùng tài khoản quản trị viên hoặc nhân viên đã được cấp.</p><form method="post" action="{{ route('login') }}">@csrf<label for="email">Địa chỉ email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus maxlength="255"><label for="password">Mật khẩu</label><input id="password" name="password" type="password" required autocomplete="current-password" maxlength="255"><button class="wide">Đăng nhập ↗</button></form><p class="note">Chưa có tài khoản hoặc quên mật khẩu? Liên hệ quản trị viên của bạn.</p></div></main>
@endsection
