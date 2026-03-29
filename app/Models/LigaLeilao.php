<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaLeilao extends Model
{
    protected $table = 'liga_leiloes';

    protected $fillable = [
        'confederacao_id',
        'inicio',
        'fim',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fim' => 'datetime',
    ];

    public function confederacao(): BelongsTo
    {
        return $this->belongsTo(Confederacao::class, 'confederacao_id');
    }

    public static function activeRangeForLiga(Liga $liga): ?array
    {
        if (! $liga->confederacao_id) {
            return null;
        }

        $tz = $liga->resolveTimezone();
        $now = Carbon::now($tz);
        $nowString = $now->format('Y-m-d H:i:s');

        $ranges = self::query()
            ->where('confederacao_id', $liga->confederacao_id)
            ->where('inicio', '<=', $nowString)
            ->where('fim', '>=', $nowString)
            ->toBase()
            ->get(['inicio', 'fim']);

        if ($ranges->isEmpty()) {
            return null;
        }

        $start = $ranges->sortBy('inicio')->first()?->inicio;
        $end = $ranges->sortByDesc('fim')->first()?->fim;

        $startDate = Carbon::parse((string) $start, $tz);
        $endDate = Carbon::parse((string) $end, $tz);

        return [
            'inicio' => $startDate->format('Y-m-d H:i:s'),
            'fim' => $endDate->format('Y-m-d H:i:s'),
            'inicio_label' => $startDate->format('d/m/Y H:i'),
            'fim_label' => $endDate->format('d/m/Y H:i'),
            'timezone' => $tz,
        ];
    }
}
