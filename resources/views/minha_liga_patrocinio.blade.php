<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MCO | Patrocínios</title>
        @php
            $patrocinioData = [
                'liga' => $liga ?? null,
                'clube' => $clube ?? null,
                'patrocinios' => $patrocinios ?? [],
                'fans' => $fans ?? 0,
            ];
        @endphp
        <script>
            window.__PATROCINIOS__ = @json($patrocinioData);
        </script>
        @include('components.app_context', ['appContext' => $appContext ?? null])
        @include('components.app_assets')
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/minha_liga_patrocinio.jsx'])
    </head>
    <body class="antialiased">
        <div id="minha-liga-patrocinio-app"></div>
    </body>
</html>
