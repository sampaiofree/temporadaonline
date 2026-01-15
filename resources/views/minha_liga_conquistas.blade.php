<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MCO | Conquistas</title>
        @php
            $conquistasData = [
                'liga' => $liga ?? null,
                'clube' => $clube ?? null,
                'conquistas' => $conquistas ?? [],
                'progress' => $progress ?? [],
            ];
        @endphp
        <script>
            window.__CONQUISTAS__ = @json($conquistasData);
        </script>
        @include('components.app_context', ['appContext' => $appContext ?? null])
        @include('components.app_assets')
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/minha_liga_conquistas.jsx'])
    </head>
    <body class="antialiased">
        <div id="minha-liga-conquistas-app"></div>
    </body>
</html>
