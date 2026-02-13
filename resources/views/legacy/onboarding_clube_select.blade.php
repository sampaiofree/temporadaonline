<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Legacy XI | Escolher Liga</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@1,700;1,900&family=Russo+One&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Exo 2', sans-serif;
                background-color: #121212;
                color: #fff;
                margin: 0;
                overflow-x: hidden;
            }

            .font-heading {
                font-family: 'Russo One', sans-serif;
                letter-spacing: -0.05em;
            }
        </style>
        <script>
            window.__LEGACY_ONBOARDING_SELECTOR__ = @json($selectorData ?? []);
        </script>
        @include('components.app_assets')
        @viteReactRefresh
        @vite(['resources/js/legacy/onboarding_clube_select.jsx'])
    </head>
    <body class="antialiased">
        <div id="legacy-onboarding-clube-select-app"></div>
    </body>
</html>
