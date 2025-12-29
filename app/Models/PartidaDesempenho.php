<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartidaDesempenho extends Model
{
    protected $table = 'partida_desempenhos';

    protected $fillable = [
        'partida_id',
        'liga_clube_id',
        'elencopadrao_id',
        'nota',
        'gols',
        'assistencias',
    ];

    protected $casts = [
        'nota' => 'float',
        'gols' => 'integer',
        'assistencias' => 'integer',
    ];

    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class);
    }

    public function ligaClube(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class);
    }

    public function elencopadrao(): BelongsTo
    {
        return $this->belongsTo(Elencopadrao::class, 'elencopadrao_id');
    }
}
