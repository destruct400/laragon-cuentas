<?php

use App\Models\Categoria;
use App\Models\Cuenta;
use App\Models\Ingreso;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('muestra la interfaz financiera del usuario autenticado', function () {
    $user = User::factory()->create();

    actingAs($user);

    get(route('interfaz'))
        ->assertOk()
        ->assertSee('Cuentas')
        ->assertSee($user->name);
});

it('crea cuentas y actualiza el saldo con movimientos', function () {
    $user = User::factory()->create();
    $categoria = Categoria::factory()->create();

    actingAs($user);

    post(route('cuentas.store'), [
        'nombre_cuenta' => 'Cuenta principal',
        'saldo' => 100,
    ])->assertRedirect(route('interfaz'));

    $cuenta = Cuenta::query()->where('usuario_id', $user->id)->firstOrFail();

    post(route('movimientos.store'), [
        'tipo' => 'ingresos',
        'cuenta_id' => $cuenta->id,
        'monto' => 50,
        'fecha' => '2026-09-04',
        'descripcion' => 'Pago',
        'categoria_id' => $categoria->id,
    ])->assertRedirect(route('seguimiento', ['tipo' => 'ingresos', 'cuenta_id' => $cuenta->id]));

    post(route('movimientos.store'), [
        'tipo' => 'gastos',
        'cuenta_id' => $cuenta->id,
        'monto' => 20,
        'fecha' => '2026-09-04',
        'descripcion' => 'Servicio',
        'categoria_id' => $categoria->id,
    ])->assertRedirect(route('seguimiento', ['tipo' => 'gastos', 'cuenta_id' => $cuenta->id]));

    expect((float) $cuenta->fresh()->saldo)->toBe(130.0);
});

it('no permite modificar una cuenta de otro usuario', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $cuenta = Cuenta::factory()->for($owner, 'usuario')->create();

    actingAs($intruder);

    $this->patch(route('cuentas.update', $cuenta), [
        'nombre_cuenta' => 'Cuenta ajena',
    ])->assertNotFound();

    expect($cuenta->fresh()->nombre_cuenta)->not->toBe('Cuenta ajena');
});

it('no permite borrar una categoría que tiene movimientos', function () {
    $user = User::factory()->create();
    $categoria = Categoria::factory()->create();
    $cuenta = Cuenta::factory()->for($user, 'usuario')->create();

    actingAs($user);

    post(route('movimientos.store'), [
        'tipo' => 'ingresos',
        'cuenta_id' => $cuenta->id,
        'monto' => 10,
        'fecha' => '2026-09-04',
        'categoria_id' => $categoria->id,
    ]);

    $this->delete(route('categorias.destroy', $categoria))
        ->assertRedirect(route('interfaz', ['tab' => 'categorias']))
        ->assertSessionHas('error');

    expect(Categoria::query()->find($categoria->id))->not->toBeNull();
});

it('permite editar movimientos y consultar un reporte por periodo', function () {
    $user = User::factory()->create();
    $categoria = Categoria::factory()->create();
    $cuenta = Cuenta::factory()->for($user, 'usuario')->create(['saldo' => 0]);

    actingAs($user);

    post(route('movimientos.store'), [
        'tipo' => 'ingresos',
        'cuenta_id' => $cuenta->id,
        'monto' => 40,
        'fecha' => '2026-09-04',
        'categoria_id' => $categoria->id,
    ]);

    $ingreso = Ingreso::query()->firstOrFail();

    $this->patch(route('movimientos.update', ['tipo' => 'ingresos', 'movimiento' => $ingreso->id]), [
        'cuenta_id' => $cuenta->id,
        'monto' => 60,
        'fecha' => '2026-09-04',
        'categoria_id' => $categoria->id,
        'descripcion' => 'Actualizado',
    ])->assertRedirect(route('seguimiento', ['tipo' => 'ingresos', 'cuenta_id' => $cuenta->id]));

    expect((float) $cuenta->fresh()->saldo)->toBe(60.0);

    get(route('seguimiento', [
        'reporte' => 1,
        'cuenta_id' => $cuenta->id,
        'periodo' => 'mes',
        'fecha' => '2026-09-04',
    ]))->assertOk()->assertSee('Resultado del periodo');
});
