<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interfaz financiera</title>
    <link rel="stylesheet" href="{{ asset('css/generica/interfaz-estilo.css') }}">
</head>
<body>
    <main>
        <header class="page-header">
            <div>
                <h1>Bienvenido, {{ auth()->user()->name }}</h1>
                <p>Administra tus cuentas y categorías.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Cerrar sesión</button>
            </form>
        </header>

        @if (session('success')) <p class="mensaje success">{{ session('success') }}</p> @endif
        @if (session('error')) <p class="mensaje error">{{ session('error') }}</p> @endif
        @if ($errors->any()) <p class="mensaje error">{{ $errors->first() }}</p> @endif

        <nav class="botones" aria-label="Secciones">
            <a class="tab {{ $tab === 'cuentas' ? 'activo' : '' }}" href="{{ route('interfaz', ['tab' => 'cuentas']) }}">Cuentas</a>
            <a class="tab {{ $tab === 'categorias' ? 'activo' : '' }}" href="{{ route('interfaz', ['tab' => 'categorias']) }}">Categorías</a>
        </nav>

        @if ($tab === 'categorias')
            <section class="panel">
                <h2>Categorías</h2>
                <form method="POST" action="{{ route('categorias.store') }}" class="form-grid" data-ajax>
                    @csrf
                    <label>Nombre de categoría <input name="nombre_categoria" required maxlength="100"></label>
                    <button type="submit">Crear categoría</button>
                </form>
                <div class="lista">
                    @forelse ($categorias as $categoria)
                        <div class="item">
                            <form method="POST" action="{{ route('categorias.update', $categoria) }}" class="inline-form" data-ajax>
                                @csrf @method('PATCH')
                                <input name="nombre_categoria" value="{{ $categoria->nombre_categoria }}" required maxlength="100">
                                <button type="submit">Guardar</button>
                            </form>
                            <form method="POST" action="{{ route('categorias.destroy', $categoria) }}" data-ajax>
                                @csrf @method('DELETE')
                                <button type="submit" class="danger">Borrar</button>
                            </form>
                        </div>
                    @empty
                        <p>No hay categorías todavía.</p>
                    @endforelse
                </div>
            </section>
        @else
            <section class="panel">
                <h2>Cuentas</h2>
                <form method="POST" action="{{ route('cuentas.store') }}" class="form-grid" data-ajax>
                    @csrf
                    <label>Nombre de cuenta <input name="nombre_cuenta" required maxlength="120"></label>
                    <label>Saldo inicial <input name="saldo" type="number" min="0" step="0.01" value="0"></label>
                    <button type="submit">Crear cuenta</button>
                </form>
                <div class="lista">
                    @forelse ($cuentas as $cuenta)
                        <div class="item account-item">
                            <form method="POST" action="{{ route('cuentas.update', $cuenta) }}" class="inline-form" data-ajax>
                                @csrf @method('PATCH')
                                <input name="nombre_cuenta" value="{{ $cuenta->nombre_cuenta }}" required maxlength="120">
                                <strong>${{ number_format((float) $cuenta->saldo, 2, ',', '.') }}</strong>
                                <button type="submit">Guardar</button>
                            </form>
                            <a class="button-link" href="{{ route('seguimiento', ['cuenta_id' => $cuenta->id]) }}">Seguimiento</a>
                            <form method="POST" action="{{ route('cuentas.destroy', $cuenta) }}" data-ajax>
                                @csrf @method('DELETE')
                                <button type="submit" class="danger">Borrar</button>
                            </form>
                        </div>
                    @empty
                        <p>No hay cuentas todavía.</p>
                    @endforelse
                </div>
            </section>
        @endif
    </main>
    <script src="{{ asset('js/ajax-forms.js') }}" defer></script>
</body>
</html>
