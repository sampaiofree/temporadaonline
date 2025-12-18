<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MCO | Ligas</title>
        <script>
            window.__ALL_LIGAS__ = @json($ligas);
            window.__MY_LIGAS__ = @json($myLigas);
        </script>
        @include('components.app_context', ['appContext' => $appContext ?? null])
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/ligas.jsx'])
    </head>
    <body class="antialiased">
        <div id="ligas-app"></div>
    </body>
</html>
