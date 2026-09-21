<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión</title>
    <link rel="stylesheet" href="{{ asset('css/generica/inicio-estilo.css') }}">
</head>
<body>
    <main class="login-card">
        <h1>Iniciar sesión</h1>
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <input type="hidden" name="legacy" value="1">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            <label for="remember"><input id="remember" name="remember" type="checkbox"> Recordarme</label>
            <button type="submit">Entrar</button>
        </form>
        @if ($errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif
        <p class="footer">¿No tienes cuenta? <a href="{{ route('registro') }}">Regístrate</a></p>
    </main>
</body>
</html>
