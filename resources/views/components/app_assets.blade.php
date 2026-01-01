@php
    $assets = $appAssets ?? null;
    $version = $assets?->updated_at?->timestamp;
    $buildUrl = function (?string $path) use ($version) {
        if (! $path) {
            return null;
        }

        $url = Storage::disk('public')->url($path);

        if ($version) {
            $url .= '?v='.$version;
        }

        return $url;
    };

    $faviconUrl = $buildUrl($assets?->favicon);
    $logoPadraoUrl = $buildUrl($assets?->logo_padrao);
    $logoDarkUrl = $buildUrl($assets?->logo_dark);
    $imagemCampoUrl = $buildUrl($assets?->imagem_campo);
    $backgroundAppUrl = $buildUrl($assets?->background_app);
    $appAssetsPayload = [
        'favicon_url' => $faviconUrl,
        'logo_padrao_url' => $logoPadraoUrl,
        'logo_dark_url' => $logoDarkUrl,
        'imagem_campo_url' => $imagemCampoUrl,
        'background_app_url' => $backgroundAppUrl,
    ];
@endphp

@if ($faviconUrl)
    <link rel="icon" href="{{ $faviconUrl }}">
@endif

<script>
    window.__APP_ASSETS__ = @json($appAssetsPayload);
</script>
