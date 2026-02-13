<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Legacy XI | Onboarding do Clube</title>
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
        @php
            $clubeOnboardingData = [
                'liga' => $liga ?? null,
                'confederacao_nome' => $confederacao_nome ?? null,
                'clube' => $clube ?? null,
                'escudos' => $escudos ?? null,
                'paises' => $paises ?? [],
                'ligas_escudos' => $ligasEscudos ?? [],
                'used_escudos' => $usedEscudos ?? [],
                'filters' => $filters ?? [],
                'routes' => $routes ?? [],
            ];
        @endphp
        <script>
            window.__CLUBE_ONBOARDING__ = @json($clubeOnboardingData);
        </script>
        @include('components.app_assets')
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/legacy/onboarding_clube.jsx'])
    </head>
    <body class="antialiased">
        <div id="legacy-onboarding-clube-app"></div>
    </body>
</html>
