<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cuenta;
use App\Models\Deuda;
use App\Models\Gasto;
use App\Models\Ingreso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanzasController extends Controller
{
    public function index(Request $request): View
    {
        $cuentas = $request->user()->cuentas()->withCount(['ingresos', 'gastos', 'deudas'])->latest()->get();
        $categorias = Categoria::query()->orderBy('nombre_categoria')->get();

        return view('interfaz', [
            'cuentas' => $cuentas,
            'categorias' => $categorias,
            'tab' => $request->string('tab', 'cuentas')->toString(),
        ]);
    }

    public function storeCuenta(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre_cuenta' => ['required', 'string', 'max:120'],
            'saldo' => ['nullable', 'numeric', 'min:0'],
        ]);

        $request->user()->cuentas()->create([
            'nombre_cuenta' => $datos['nombre_cuenta'],
            'saldo' => $datos['saldo'] ?? 0,
        ]);

        return to_route('interfaz')->with('success', 'Cuenta creada.');
    }

    public function updateCuenta(Request $request, Cuenta $cuenta): RedirectResponse
    {
        $this->ensureOwnsAccount($request, $cuenta);

        $datos = $request->validate([
            'nombre_cuenta' => ['required', 'string', 'max:120'],
        ]);

        $cuenta->update($datos);

        return to_route('interfaz')->with('success', 'Cuenta actualizada.');
    }

    public function destroyCuenta(Request $request, Cuenta $cuenta): RedirectResponse
    {
        $this->ensureOwnsAccount($request, $cuenta);
        $cuenta->delete();

        return to_route('interfaz')->with('success', 'Cuenta eliminada.');
    }

    public function storeCategoria(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre_categoria' => ['required', 'string', 'max:100', 'unique:categorias,nombre_categoria'],
        ]);

        Categoria::create($datos);

        return to_route('interfaz', ['tab' => 'categorias'])->with('success', 'Categoría creada.');
    }

    public function updateCategoria(Request $request, Categoria $categoria): RedirectResponse
    {
        $datos = $request->validate([
            'nombre_categoria' => ['required', 'string', 'max:100', 'unique:categorias,nombre_categoria,'.$categoria->id],
        ]);

        $categoria->update($datos);

        return to_route('interfaz', ['tab' => 'categorias'])->with('success', 'Categoría actualizada.');
    }

    public function destroyCategoria(Request $request, Categoria $categoria): RedirectResponse
    {
        if ($categoria->ingresos()->exists() || $categoria->gastos()->exists() || $categoria->deudas()->exists()) {
            return to_route('interfaz', ['tab' => 'categorias'])
                ->with('error', 'No puedes borrar una categoría con movimientos.');
        }

        $categoria->delete();

        return to_route('interfaz', ['tab' => 'categorias'])->with('success', 'Categoría eliminada.');
    }

    public function seguimiento(Request $request): View
    {
        $cuentas = $request->user()->cuentas()->orderBy('nombre_cuenta')->get();
        $categorias = Categoria::query()->orderBy('nombre_categoria')->get();
        $tipo = $request->string('tipo', 'ingresos')->toString();
        $cuentaId = $request->integer('cuenta_id');
        $vista = $request->string('vista', 'registros')->toString();

        $movimientos = $this->movimientosDelUsuario($request, $tipo, $cuentaId)->get();
        $reporte = $this->crearReporte($request, $cuentaId, $categorias);

        return view('seguimiento', compact('cuentas', 'categorias', 'movimientos', 'tipo', 'cuentaId', 'reporte', 'vista'));
    }

    public function storeMovimiento(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', 'in:ingresos,gastos,deudas'],
            'cuenta_id' => ['required', 'integer', 'exists:cuenta,id'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'descripcion' => ['nullable', 'string', 'max:120'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
        ]);

        $cuenta = $request->user()->cuentas()->findOrFail($datos['cuenta_id']);
        $modelo = $this->modeloMovimiento($datos['tipo']);

        DB::transaction(function () use ($modelo, $datos, $cuenta): void {
            $modelo::create([
                'cuenta_id' => $cuenta->id,
                'monto' => $datos['monto'],
                'fecha' => $datos['fecha'],
                'descripcion' => $datos['descripcion'] ?? null,
                'categoria_id' => $datos['categoria_id'],
            ]);

            $cuenta->increment('saldo', $this->impactoSaldo($datos['tipo'], (float) $datos['monto']));
        });

        return to_route('seguimiento', ['tipo' => $datos['tipo'], 'cuenta_id' => $cuenta->id])
            ->with('success', 'Movimiento guardado.');
    }

    public function updateMovimiento(Request $request, string $tipo, int $movimiento): RedirectResponse
    {
        $datos = $request->validate([
            'cuenta_id' => ['required', 'integer', 'exists:cuenta,id'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'descripcion' => ['nullable', 'string', 'max:120'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
        ]);

        $modelo = $this->modeloMovimiento($tipo);
        $movimientoModelo = $modelo::query()->whereKey($movimiento)->whereHas('cuenta', fn ($query) => $query->where('usuario_id', $request->user()->id))->firstOrFail();
        $cuentaNueva = $request->user()->cuentas()->findOrFail($datos['cuenta_id']);

        DB::transaction(function () use ($movimientoModelo, $cuentaNueva, $datos, $tipo): void {
            $cuentaAnterior = $movimientoModelo->cuenta()->lockForUpdate()->firstOrFail();
            $cuentaAnterior->increment('saldo', -$this->impactoSaldo($tipo, (float) $movimientoModelo->monto));
            $movimientoModelo->update($datos);
            $cuentaNueva->increment('saldo', $this->impactoSaldo($tipo, (float) $datos['monto']));
        });

        return to_route('seguimiento', ['tipo' => $tipo, 'cuenta_id' => $cuentaNueva->id])
            ->with('success', 'Movimiento actualizado.');
    }

    public function destroyMovimiento(Request $request, string $tipo, int $movimiento): RedirectResponse
    {
        $modelo = $this->modeloMovimiento($tipo);
        $movimientoModelo = $modelo::query()->whereKey($movimiento)->whereHas('cuenta', fn ($query) => $query->where('usuario_id', $request->user()->id))->firstOrFail();

        DB::transaction(function () use ($movimientoModelo, $tipo): void {
            $cuenta = $movimientoModelo->cuenta()->lockForUpdate()->firstOrFail();
            $cuenta->increment('saldo', -$this->impactoSaldo($tipo, (float) $movimientoModelo->monto));
            $movimientoModelo->delete();
        });

        return to_route('seguimiento', ['tipo' => $tipo])->with('success', 'Movimiento eliminado.');
    }

    private function movimientosDelUsuario(Request $request, string $tipo, int $cuentaId): mixed
    {
        $modelo = $this->modeloMovimiento($tipo);

        return $modelo::query()
            ->with(['cuenta', 'categoria'])
            ->whereHas('cuenta', fn ($query) => $query->where('usuario_id', $request->user()->id))
            ->when($cuentaId > 0, fn ($query) => $query->where('cuenta_id', $cuentaId))
            ->latest('fecha')
            ->latest('id');
    }

    private function crearReporte(Request $request, int $cuentaId, $categorias): ?array
    {
        if (! $request->boolean('reporte') || $cuentaId < 1) {
            return null;
        }

        $fecha = Carbon::parse($request->input('fecha', now()->toDateString()))->startOfDay();
        $periodo = $request->string('periodo', 'mes')->toString();
        $inicio = match ($periodo) {
            'dia' => $fecha->copy(),
            'semana' => $fecha->copy()->startOfWeek(),
            default => $fecha->copy()->startOfMonth(),
        };
        $fin = match ($periodo) {
            'dia' => $fecha->copy()->endOfDay(),
            'semana' => $fecha->copy()->endOfWeek(),
            default => $fecha->copy()->endOfMonth(),
        };
        $categoriaId = $request->integer('categoria_id');
        $totales = ['ingresos' => 0.0, 'gastos' => 0.0, 'deudas' => 0.0];
        $porCategoria = [];

        foreach (['ingresos' => Ingreso::class, 'gastos' => Gasto::class, 'deudas' => Deuda::class] as $nombre => $modelo) {
            $registros = $modelo::query()
                ->with('categoria')
                ->where('cuenta_id', $cuentaId)
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                ->when($categoriaId > 0, fn ($query) => $query->where('categoria_id', $categoriaId))
                ->get();

            foreach ($registros as $registro) {
                $monto = (float) $registro->monto;
                $totales[$nombre] += $monto;
                $categoria = $registro->categoria?->nombre_categoria ?? 'Sin categoría';
                $porCategoria[$categoria] = ($porCategoria[$categoria] ?? 0) + ($nombre === 'ingresos' ? $monto : -$monto);
            }
        }

        return [
            'periodo' => $periodo,
            'fecha' => $fecha->toDateString(),
            'totales' => $totales,
            'resultado' => $totales['ingresos'] - $totales['gastos'] - $totales['deudas'],
            'porCategoria' => $porCategoria,
            'cuenta' => $request->user()->cuentas()->findOrFail($cuentaId),
            'categorias' => $categorias,
        ];
    }

    private function modeloMovimiento(string $tipo): string
    {
        return match ($tipo) {
            'ingresos' => Ingreso::class,
            'gastos' => Gasto::class,
            'deudas' => Deuda::class,
            default => abort(404),
        };
    }

    private function impactoSaldo(string $tipo, float $monto): float
    {
        return $tipo === 'ingresos' ? $monto : -$monto;
    }

    private function ensureOwnsAccount(Request $request, Cuenta $cuenta): void
    {
        abort_unless($cuenta->usuario_id === $request->user()->id, 404);
    }
}
