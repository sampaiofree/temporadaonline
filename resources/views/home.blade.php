<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>Legacy XI | Modo Carreira Online EAFC</title>
        <meta
            name="description"
            content="Legacy XI: o Modo Carreira Online definitivo. Gestao nativa via app, mercado de transferencias e acesso alpha gratuito por tempo limitado."
        />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="{{ url('/') }}" />

        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="Legacy XI" />
        <meta property="og:title" content="Legacy XI | Modo Carreira Online EAFC" />
        <meta
            property="og:description"
            content="Legacy XI: o Modo Carreira Online definitivo. Gestao nativa via app, mercado de transferencias e acesso alpha gratuito por tempo limitado."
        />
        <meta property="og:url" content="{{ url('/') }}" />

        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" content="Legacy XI | Modo Carreira Online EAFC" />
        <meta
            name="twitter:description"
            content="Legacy XI: o Modo Carreira Online definitivo. Gestao nativa via app, mercado de transferencias e acesso alpha gratuito por tempo limitado."
        />

        @viteReactRefresh
        @vite(['resources/js/home/main.tsx'])
    </head>
    <body>
        <div id="root"></div>
    </body>
</html>
