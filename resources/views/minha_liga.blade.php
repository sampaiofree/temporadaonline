<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>MCO | Minha Liga</title>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/minha_liga.jsx'])
    </head>
    <body class="antialiased">
        <div id="minha-liga-app"></div>
    </body>
</html>
