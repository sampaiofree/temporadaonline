<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MCO | Perfil</title>
        <script>
            window.__PLAYER__ = @json($player);
            window.__PLATAFORMAS__ = @json($plataformas);
            window.__JOGOS__ = @json($jogos);
            window.__GERACOES__ = @json($geracoes);
        </script>
        @include('components.app_context', ['appContext' => ['mode' => 'global', 'liga' => null, 'clube' => null, 'nav' => 'perfil']])
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/perfil.jsx'])
    </head>
    <body class="antialiased">
        <div id="perfil-app"></div>
    </body>
</html>
