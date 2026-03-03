<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaPeriodo extends Model
{
    protected $table = 'liga_periodos';

    protected $fillable = [
        'confederacao_id',
        'inicio',
        'fim',
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

        $periods = self::query()
            ->where('confederacao_id', $liga->confederacao_id)
            ->where('inicio', '<=', $nowString)
            ->where('fim', '>=', $nowString)
            ->toBase()
            ->get(['inicio', 'fim']);

        if ($periods->isEmpty()) {
            return null;
        }

        $start = $periods->sortBy('inicio')->first()?->inicio;
        $end = $periods->sortByDesc('fim')->first()?->fim;

        $startDate = Carbon::parse((string) $start, $tz);
        $endDate = Carbon::parse((string) $end, $tz);

        return [
            'inicio' => $startDate->format('Y-m-d\TH:i:s'),
            'fim' => $endDate->format('Y-m-d\TH:i:s'),
            'inicio_label' => $startDate->format('d/m/Y H:i'),
            'fim_label' => $endDate->format('d/m/Y H:i'),
            'timezone' => $tz,
        ];
    }
}
