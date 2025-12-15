@extends('layouts.guest')

@section('title', 'Password reset sent')

@section('content')
    <h2 class="text-center">Instrucciones enviadas</h2>

    <p class="text-center">Si existe una cuenta asociada con el correo proporcionado, hemos enviado instrucciones para restablecer la contraseña.</p>

    <div class="mt-4">
        <a href="{{ route('swift-auth.login.form') }}" class="btn btn-outline-primary w-100">Volver al inicio de sesión</a>
    </div>
@endsection
