<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de usuario</title>
    <link rel="stylesheet" href="{{ asset('css/generica/registro-estilo.css') }}">
</head>
<body>
    <main class="registro-contenedor">
        <h1>Registro de usuario</h1>
        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <input type="hidden" name="legacy" value="1">
            <div class="campo"><label for="name">Nombre completo</label><input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name"></div>
            <div class="campo"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></div>
            <div class="campo"><label for="password">Contraseña</label><input id="password" name="password" type="password" required autocomplete="new-password"></div>
            <div class="campo"><label for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div>
            <div class="acciones"><button type="submit">Registrarse</button></div>
        </form>
        @if ($errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif
        <p class="nota"><a href="{{ route('inicio') }}">Ya tengo una cuenta</a></p>
    </main>
</body>
</html>
