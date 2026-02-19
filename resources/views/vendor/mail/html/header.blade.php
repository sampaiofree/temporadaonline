@php
    $assets = $appAssets ?? null;
    $version = $assets?->updated_at?->timestamp;

    $buildAbsoluteUrl = function (?string $path) use ($version) {
        if (! $path) {
            return null;
        }

        $rawUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);

        if ($version) {
            $rawUrl .= '?v='.$version;
        }

        if (preg_match('/^https?:\/\//i', $rawUrl)) {
            return $rawUrl;
        }

        return url($rawUrl);
    };

    $logoUrl = $buildAbsoluteUrl($assets?->logo_padrao);
    $brandName = trim((string) config('app.name', 'Legacy XI'));
@endphp

<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($logoUrl)
<img src="{{ $logoUrl }}" class="logo" alt="{{ $brandName }} Logo">
@else
{{ $brandName !== '' ? $brandName : $slot }}
@endif
</a>
</td>
</tr>
