<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Legacy XI | Primeiro Acesso</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@1,700;1,900&family=Russo+One&display=swap" rel="stylesheet">
        <style>
            :root {
                --legacy-bg: #121212;
                --legacy-surface: #1e1e1e;
                --legacy-gold: #ffd700;
            }

            body {
                font-family: 'Exo 2', sans-serif;
                background-color: var(--legacy-bg);
                color: #fff;
                -webkit-tap-highlight-color: transparent;
                margin: 0;
                overflow-x: hidden;
            }

            .font-heading {
                font-family: 'Russo One', sans-serif;
                letter-spacing: -0.05em;
            }

            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background:
                    linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.1) 50%),
                    linear-gradient(90deg, rgba(255, 0, 0, 0.02), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.02));
                background-size: 100% 2px, 3px 100%;
                pointer-events: none;
                z-index: 0;
                opacity: 0.3;
            }
        </style>
        <script>
            window.__LEGACY_FIRST_ACCESS__ = @json($firstAccessData ?? []);
        </script>
        @include('components.app_assets')
        @viteReactRefresh
        @vite(['resources/js/legacy/primeiro_acesso.jsx'])
    </head>
    <body class="antialiased">
        <div id="legacy-primeiro-acesso-app" class="relative z-10"></div>
    </body>
</html>
