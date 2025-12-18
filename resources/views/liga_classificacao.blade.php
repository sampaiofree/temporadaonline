<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MCO | Classificação da Liga</title>
        <script>
            window.__LIGA__ = @json($liga);
            window.__CLASSIFICACAO__ = @json($classification);
        </script>
        @include('components.app_context', ['appContext' => $appContext ?? null])
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/liga_classificacao.jsx'])
    </head>
    <body class="antialiased">
        <div id="liga-classificacao-app"></div>
    </body>
</html>
