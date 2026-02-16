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
        'inicio' => 'date',
        'fim' => 'date',
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
        $today = Carbon::now($tz)->toDateString();

        $ranges = self::query()
            ->where('confederacao_id', $liga->confederacao_id)
            ->whereDate('inicio', '<=', $today)
            ->whereDate('fim', '>=', $today)
            ->get(['inicio', 'fim']);

        if ($ranges->isEmpty()) {
            return null;
        }

        $start = $ranges->sortBy('inicio')->first()?->inicio;
        $end = $ranges->sortByDesc('fim')->first()?->fim;

        $startDate = $start instanceof Carbon ? $start : Carbon::parse((string) $start, $tz);
        $endDate = $end instanceof Carbon ? $end : Carbon::parse((string) $end, $tz);

        return [
            'inicio' => $startDate->toDateString(),
            'fim' => $endDate->toDateString(),
            'inicio_label' => $startDate->format('d/m/Y'),
            'fim_label' => $endDate->format('d/m/Y'),
            'timezone' => $tz,
        ];
    }
}
