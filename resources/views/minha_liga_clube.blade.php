<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MCO | Meu Clube</title>
        @php
            $clubeEditorData = [
                'liga' => $liga ?? null,
                'clube' => $clube ?? null,
                'escudos' => $escudos ?? null,
                'paises' => $paises ?? [],
                'ligas_escudos' => $ligasEscudos ?? [],
                'used_escudos' => $usedEscudos ?? [],
                'filters' => $filters ?? [],
            ];
        @endphp
        <script>
            window.__CLUBE_EDITOR__ = @json($clubeEditorData);
        </script>
        @include('components.app_context', ['appContext' => $appContext ?? null])
        @include('components.app_assets')
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/minha_liga_clube.jsx'])
    </head>
    <body class="antialiased">
        <div id="minha-liga-clube-app"></div>
    </body>
</html>
