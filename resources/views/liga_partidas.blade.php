<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MCO | Partidas da Liga</title>
        <script>
            window.__LIGA__ = @json($liga);
            window.__CLUBE__ = @json($clube);
            window.__PARTIDAS__ = @json(['minhas_partidas' => $minhas_partidas, 'todas_partidas' => $todas_partidas]);
        </script>
        @include('components.app_context', ['appContext' => $appContext ?? null])
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/liga_partidas.jsx'])
    </head>
    <body class="antialiased">
        <div id="liga-partidas-app"></div>
    </body>
</html>
