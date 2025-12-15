@extends('layouts.auth')

@section('content')
<div class="container">
    <h2>Editar rol</h2>

    <form action="{{ route('swift-auth.roles.update', $role->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name" class="form-label">Nombre:</label>
            <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Descripción:</label>
            <textarea name="description" class="form-control">{{ $role->description }}</textarea>
        </div>

        <div class="mb-3">
            <label for="actions" class="form-label">Acciones:</label>
            @foreach(config('swift-auth.actions') as $action => $label)
                {{ $label }}
                <input
                    type="checkbox"
                    name="action[]"
                    value="{{ $action }}"
                    {{ in_array($action, $role->actions) ?'checked':''  }}
                />
            @endforeach
        </div>

        <button type="submit" class="btn btn-success">Actualizar</button>
        <a href="{{ route('swift-auth.roles.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
