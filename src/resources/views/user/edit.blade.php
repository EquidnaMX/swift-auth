@extends('layouts.auth')

@section('content')
<div class="container">
    <h2>Editar usuario</h2>

    <form action="{{ route('swift-auth.users.update', $user->getKey()) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name" class="form-label">Nombre:</label>
            <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Correo electronico:</label>
            <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
        </div>

        <div class="mb-3">
            <label for="role" class="form-label">Rol:</label>
            <select name="role" class="form-select">
                @foreach($roles as $role)
                    <option value="{{ $role->id_role }}" @if($user->roles->contains('id_role', $role->id_role)) selected @endif>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-success">Actualizar</button>
        <a href="{{ route('swift-auth.users.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
