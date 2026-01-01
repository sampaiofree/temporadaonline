<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MCO | Elenco da Liga</title>
        <script>
            window.__LIGA__ = @json($liga);
            window.__ELENCO_LIGA__ = @json($elenco);
        </script>
        @include('components.app_context', ['appContext' => $appContext ?? null])
        @include('components.app_assets')
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/liga_elenco.jsx'])
    </head>
    <body class="antialiased">
        <div id="liga-elenco-app"></div>
    </body>
</html>
