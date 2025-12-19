@php
    $appContext ??= ['mode' => 'global', 'liga' => null, 'clube' => null, 'nav' => 'home'];
    $checklist ??= [];
    $showChecklist ??= false;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>MCO | Dashboard</title>
        @include('components.app_context', ['appContext' => $appContext])
        <script>
            window.__CHECKLIST__ = @json([
                'show' => $showChecklist ?? false,
                'items' => $checklist ?? [],
            ]);
        </script>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>
