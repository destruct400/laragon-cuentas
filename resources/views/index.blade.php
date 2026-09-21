<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyecto</title>
    <link rel="stylesheet" href="{{ asset('css/generica/index-estilo.css') }}">
</head>
<body>
    <main class="container">
        <h1>Proyecto de ejemplo</h1>
        <h3>Gestión financiera</h3>
        <div class="actions">
              <a class="btn" href="{{ route('inicio') }}">Iniciar sesión</a>
              <a class="btn" href="{{ route('registro') }}">Registrarse</a>
        </div>
    </main>
</body>
</html>
