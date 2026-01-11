<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Elencopadrao;
use App\Models\Pais;
use App\Models\Playstyle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ElencopadraoController extends Controller
{
    public function show(Elencopadrao $player): JsonResponse
    {
        $player->loadMissing('jogo:id,nome');

        $data = $player->toArray();
        if (array_key_exists('jogo', $data)) {
            unset($data['jogo']);
        }

        $data['jogo_nome'] = $player->jogo?->nome;

        $flagUrl = null;
        $nationalityName = trim((string) ($player->nationality_name ?? ''));
        if ($nationalityName !== '') {
            $pais = Pais::query()
                ->whereRaw('LOWER(nome) = ?', [Str::lower($nationalityName)])
                ->first();

            if ($pais?->imagem) {
                $path = $pais->imagem;
                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/storage/')) {
                    $flagUrl = $path;
                } else {
                    $flagUrl = Storage::disk('public')->url($path);
                }
            }
        }

        $data['nationality_flag_url'] = $flagUrl;

        $playstyleBadges = [];
        $rawTags = (string) ($player->player_traits ?? ''); 
        $tags = collect(explode(',', $rawTags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->map(fn ($tag) => ltrim($tag, '#'))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique(fn ($tag) => Str::lower($tag))
            ->values();

        if ($tags->isNotEmpty()) {
            $lowerTags = $tags->map(fn ($tag) => Str::lower($tag))->values();
            $placeholders = $lowerTags->map(fn () => '?')->implode(',');

            $playstyles = Playstyle::query()
                ->whereRaw("LOWER(nome) in ({$placeholders})", $lowerTags->all())
                ->get(['nome', 'imagem']);

            $playstylesMap = $playstyles->keyBy(fn ($item) => Str::lower($item->nome));

            $playstyleBadges = $tags
                ->map(function ($tag) use ($playstylesMap) {
                    $match = $playstylesMap->get(Str::lower($tag));
                    if (! $match || ! $match->imagem) {
                        return null;
                    }

                    $path = $match->imagem;
                    $url = str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/storage/')
                        ? $path
                        : Storage::disk('public')->url($path);

                    return [
                        'name' => $match->nome,
                        'image_url' => $url,
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        $data['playstyle_badges'] = $playstyleBadges;

        return response()->json([
            'player' => $data,
        ]);
    }
}
