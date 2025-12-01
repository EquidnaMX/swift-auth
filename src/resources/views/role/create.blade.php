@extends('layouts.auth')

@section('content')
    <h2>Crear rol</h2>

    <form action="{{ route('swift-auth.roles.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Nombre:</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Descripción:</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label for="actions" class="form-label">Acciones:</label>
            @foreach(config('swift-auth.actions') as $action => $label)
                {{ $label }}
                <input
                    type="checkbox"
                    name="action[]"
                    value="{{ $action }}"
                />
            @endforeach
        </div>

        <button type="submit" class="btn btn-success">Crear</button>
        <a href="{{ route('swift-auth.roles.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection
