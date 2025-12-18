<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MCO | Elenco da Liga</title>
        <script>
            window.__LIGA__ = @json($liga);
            window.__ELENCO__ = @json($elenco);
            window.__USER_CLUB__ = @json($userClub);
            window.__CLUBE_ELENCO_IDS__ = @json($clubeElencoIds);
        </script>
        @include('components.app_context', ['appContext' => $appContext ?? null])
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/minha_liga_elenco.jsx'])
    </head>
    <body class="antialiased">
        <div id="minha-liga-elenco-app"></div>
    </body>
</html>
