<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MCO | Dashboard da Liga</title>
        @php
            $dashboardData = compact('hasClub', 'nextMatch', 'classification', 'actions');
        @endphp
        <script>
            window.__LIGA__ = @json($liga);
            window.__CLUBE__ = @json($clube);
            window.__DASHBOARD__ = @json($dashboardData);
        </script>
        @include('components.app_context', ['appContext' => $appContext ?? null])
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/liga_dashboard.jsx'])
    </head>
    <body class="antialiased">
        <div id="liga-dashboard-app"></div>
    </body>
</html>
