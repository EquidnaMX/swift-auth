@extends('layouts.guest')

@section('title', @lang('swift-auth::auth.register_title'))

@section('content')
    <h2 class="text-center">@lang('swift-auth::auth.register_title')</h2>

    <form method="POST" action="{{ route('swift-auth.users.store') }}">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">@lang('swift-auth::auth.name'):</label>
            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
            @error('name')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">@lang('swift-auth::auth.email'):</label>
            <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
            @error('email')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">@lang('swift-auth::auth.password'):</label>
            <input type="password" class="form-control" name="password" required>
            @error('password')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">@lang('swift-auth::auth.password_confirmation'):</label>
            <input type="password" class="form-control" name="password_confirmation" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">@lang('swift-auth::auth.register_button')</button>

        <div class="text-center mt-3">
            <a href="{{ route('swift-auth.login.form') }}" class="text-decoration-none">@lang('swift-auth::auth.already_have_account')</a>
        </div>
    </form>
@endsection
