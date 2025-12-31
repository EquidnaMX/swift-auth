@extends('layouts.guest')

@section('title', @lang('swift-auth::auth.login_title'))

@section('content')
    <h2 class="text-center">@lang('swift-auth::auth.login_title')</h2>

    <form method="POST" action="{{ route('swift-auth.login') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">@lang('swift-auth::auth.email'):</label>
            <input type="email" class="form-control" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">@lang('swift-auth::auth.password'):</label>
            <input type="password" class="form-control" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">@lang('swift-auth::auth.login_button')</button>

        <div class="text-center mt-3">
            <a href="{{ route('swift-auth.public.register') }}" class="text-decoration-none">@lang('swift-auth::auth.no_account')</a>
            <br>
            <a href="{{ route('swift-auth.password.request.form') }}" class="text-decoration-none">@lang('swift-auth::auth.forgot_password')</a>
        </div>
    </form>
@endsection
