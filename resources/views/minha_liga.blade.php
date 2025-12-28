<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MCO | Minha Liga</title>
        <script>
            window.__LIGA__ = @json($liga);
            window.__CLUBE__ = @json($clube);
            window.__ESCUDOS__ = @json($escudos);
            window.__USED_ESCUDOS__ = @json($usedEscudos);
        </script>
        @include('components.app_context', ['appContext' => $appContext ?? null])
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/minha_liga.jsx'])
    </head>
    <body class="antialiased">
        <div id="minha-liga-app"></div>
    </body>
</html>
