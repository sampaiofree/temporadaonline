<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>MCO | Perfil</title>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/perfil.jsx'])
    </head>
    <body class="antialiased">
        <div id="perfil-app"></div>
    </body>
</html>
