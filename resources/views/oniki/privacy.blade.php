@extends('oniki.layout')
@section('title',__('oniki.privacy').' | ONIKI')
@section('content')<section class="container oniki-section privacy-content"><span class="kicker">ONIKI / PRIVACY</span><h1>{{ __('oniki.privacyhead') }}</h1><p>{{ __('oniki.privacybody') }}</p><a class="button" href="{{ route('oniki.contact') }}">{{ __('oniki.contact') }} ↗</a></section>@endsection
