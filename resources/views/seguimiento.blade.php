<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento financiero</title>
    <link rel="stylesheet" href="{{ asset('css/generica/seguimiento-estilo.css') }}">
</head>
<body>
    <main>
        <header class="page-header">
            <div><h1>Seguimiento financiero</h1><p>Registra y consulta tus movimientos.</p></div>
            <a class="volver" href="{{ route('interfaz') }}">Volver a cuentas</a>
        </header>

        @if (session('success')) <p class="mensaje success">{{ session('success') }}</p> @endif
        @if ($errors->any()) <p class="mensaje error">{{ $errors->first() }}</p> @endif

        <nav class="botones" aria-label="Secciones de seguimiento">
            <a class="tab {{ $vista === 'registros' ? 'activo' : '' }}" href="{{ route('seguimiento', ['vista' => 'registros', 'tipo' => $tipo, 'cuenta_id' => $cuentaId ?: null]) }}">Registros</a>
            <a class="tab {{ $vista === 'reportes' ? 'activo' : '' }}" href="{{ route('seguimiento', ['vista' => 'reportes', 'cuenta_id' => $cuentaId ?: null]) }}">Reportes</a>
        </nav>

        @if ($vista === 'registros')
            <nav class="botones" aria-label="Tipo de movimiento">
                @foreach (['ingresos' => 'Ingresos', 'gastos' => 'Gastos', 'deudas' => 'Deudas'] as $tipoClave => $tipoNombre)
                    <a class="tab {{ $tipo === $tipoClave ? 'activo' : '' }}" href="{{ route('seguimiento', ['vista' => 'registros', 'tipo' => $tipoClave, 'cuenta_id' => $cuentaId ?: null]) }}">{{ $tipoNombre }}</a>
                @endforeach
            </nav>

            <section class="panel">
            <h2>Registrar {{ $tipo === 'ingresos' ? 'ingreso' : ($tipo === 'gastos' ? 'gasto' : 'deuda') }}</h2>
            <form method="POST" action="{{ route('movimientos.store') }}" class="form-grid" data-ajax>
                @csrf
                <input type="hidden" name="tipo" value="{{ $tipo }}">
                <label>Cuenta
                    <select name="cuenta_id" required>
                        <option value="">Selecciona una cuenta</option>
                        @foreach ($cuentas as $cuenta)
                            <option value="{{ $cuenta->id }}" @selected($cuentaId === $cuenta->id)>{{ $cuenta->nombre_cuenta }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Monto <input name="monto" type="number" min="0.01" step="0.01" required></label>
                <label>Fecha <input name="fecha" type="date" value="{{ now()->toDateString() }}" required></label>
                <label>Categoría
                    <select name="categoria_id" required>
                        <option value="">Selecciona una categoría</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}">{{ $categoria->nombre_categoria }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="wide">Descripción <input name="descripcion" maxlength="120"></label>
                <button type="submit">Guardar movimiento</button>
            </form>
            </section>

            <section class="panel">
            <h2>Registros de {{ $tipo }}</h2>
            <form method="GET" action="{{ route('seguimiento') }}" class="filter-form" data-ajax>
                <input type="hidden" name="tipo" value="{{ $tipo }}">
                <label>Filtrar por cuenta
                    <select name="cuenta_id" onchange="this.form.submit()">
                        <option value="">Todas las cuentas</option>
                        @foreach ($cuentas as $cuenta)
                            <option value="{{ $cuenta->id }}" @selected($cuentaId === $cuenta->id)>{{ $cuenta->nombre_cuenta }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
            <div class="lista">
                @forelse ($movimientos as $movimiento)
                    <div class="item movement-item">
                        <div><strong>${{ number_format((float) $movimiento->monto, 2, ',', '.') }}</strong><small>{{ $movimiento->descripcion ?: 'Sin descripción' }} · {{ $movimiento->cuenta->nombre_cuenta }} · {{ $movimiento->fecha->format('d/m/Y') }}</small></div>
                        <details>
                            <summary>Editar</summary>
                            <form method="POST" action="{{ route('movimientos.update', [$tipo, $movimiento->id]) }}" class="edit-form" data-ajax>
                                @csrf @method('PATCH')
                                <input name="cuenta_id" type="hidden" value="{{ $movimiento->cuenta_id }}">
                                <input name="monto" type="number" min="0.01" step="0.01" value="{{ $movimiento->monto }}" required>
                                <input name="fecha" type="date" value="{{ $movimiento->fecha->toDateString() }}" required>
                                <select name="categoria_id" required>@foreach ($categorias as $categoria)<option value="{{ $categoria->id }}" @selected($movimiento->categoria_id === $categoria->id)>{{ $categoria->nombre_categoria }}</option>@endforeach</select>
                                <input name="descripcion" value="{{ $movimiento->descripcion }}" maxlength="120">
                                <button type="submit">Guardar</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('movimientos.destroy', [$tipo, $movimiento->id]) }}" data-ajax>
                            @csrf @method('DELETE')
                            <button type="submit" class="danger">Borrar</button>
                        </form>
                    </div>
                @empty
                    <p>No hay registros todavía.</p>
                @endforelse
            </div>
            </section>
        @else
            <section class="panel reporte-panel">
            <h2>Reportes</h2>
            <form method="GET" action="{{ route('seguimiento') }}" class="form-grid" data-ajax>
                <input type="hidden" name="reporte" value="1">
                <input type="hidden" name="vista" value="reportes">
                <label>Cuenta
                    <select name="cuenta_id" required>
                        <option value="">Selecciona una cuenta</option>
                        @foreach ($cuentas as $cuenta)
                            <option value="{{ $cuenta->id }}" @selected($reporte && $reporte['cuenta']->id === $cuenta->id)>{{ $cuenta->nombre_cuenta }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Periodo
                    <select name="periodo"><option value="dia">Diario</option><option value="semana">Semanal</option><option value="mes" selected>Mensual</option></select>
                </label>
                <label>Fecha de referencia <input name="fecha" type="date" value="{{ $reporte['fecha'] ?? now()->toDateString() }}" required></label>
                <label>Categoría
                    <select name="categoria_id"><option value="0">Todas las categorías</option>@foreach ($categorias as $categoria)<option value="{{ $categoria->id }}">{{ $categoria->nombre_categoria }}</option>@endforeach</select>
                </label>
                <button type="submit">Ver reporte</button>
            </form>
            @if ($reporte)
                <div class="resumen"><div>Ingresos<strong class="positivo">${{ number_format($reporte['totales']['ingresos'], 2, ',', '.') }}</strong></div><div>Gastos<strong class="negativo">${{ number_format($reporte['totales']['gastos'], 2, ',', '.') }}</strong></div><div>Deudas<strong class="negativo">${{ number_format($reporte['totales']['deudas'], 2, ',', '.') }}</strong></div></div>
                <p><strong>Resultado del periodo: ${{ number_format($reporte['resultado'], 2, ',', '.') }}</strong></p>
                @if (count($reporte['porCategoria']))
                    <div class="lista">@foreach ($reporte['porCategoria'] as $nombre => $total)<div class="item"><span>{{ $nombre }}</span><strong>${{ number_format($total, 2, ',', '.') }}</strong></div>@endforeach</div>
                @else
                    <p>No hay registros en este periodo.</p>
                @endif
            @endif
            </section>
        @endif
    </main>
    <script src="{{ asset('js/ajax-forms.js') }}" defer></script>
</body>
</html>
