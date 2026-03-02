<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>Politica de Privacidade | Legacy XI</title>
        <meta
            name="description"
            content="Conheca a politica de privacidade do Legacy XI, incluindo coleta, uso, compartilhamento e direitos dos titulares de dados."
        />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="{{ url('/politica-privacidade') }}" />

        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="Legacy XI" />
        <meta property="og:title" content="Politica de Privacidade | Legacy XI" />
        <meta
            property="og:description"
            content="Saiba como o Legacy XI trata seus dados pessoais em conformidade com a LGPD."
        />
        <meta property="og:url" content="{{ url('/politica-privacidade') }}" />

        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" content="Politica de Privacidade | Legacy XI" />
        <meta
            name="twitter:description"
            content="Saiba como o Legacy XI trata seus dados pessoais em conformidade com a LGPD."
        />

        @include('components.app_assets')
        @viteReactRefresh
        @vite(['resources/js/privacy/main.tsx'])
    </head>
    <body>
        <div id="root"></div>
    </body>
</html>
