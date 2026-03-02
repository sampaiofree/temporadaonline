<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>Suporte | Legacy XI</title>
        <meta
            name="description"
            content="Precisa de ajuda no Legacy XI? Entre em contato com o suporte e receba orientacao para conta, ligas e funcionalidades."
        />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="{{ url('/suporte') }}" />

        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="Legacy XI" />
        <meta property="og:title" content="Suporte | Legacy XI" />
        <meta
            property="og:description"
            content="Canal oficial de suporte do Legacy XI para duvidas de acesso, ligas e uso da plataforma."
        />
        <meta property="og:url" content="{{ url('/suporte') }}" />

        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" content="Suporte | Legacy XI" />
        <meta
            name="twitter:description"
            content="Canal oficial de suporte do Legacy XI para duvidas de acesso, ligas e uso da plataforma."
        />

        @include('components.app_assets')
        @viteReactRefresh
        @vite(['resources/js/support/main.tsx'])
    </head>
    <body>
        <div id="root"></div>
    </body>
</html>
