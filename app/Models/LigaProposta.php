<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaProposta extends Model
{
    protected $table = 'liga_propostas';

    protected $fillable = [
        'confederacao_id',
        'liga_origem_id',
        'liga_destino_id',
        'elencopadrao_id',
        'clube_origem_id',
        'clube_destino_id',
        'valor',
        'oferta_elencopadrao_ids',
        'status',
    ];

    protected $casts = [
        'valor' => 'integer',
        'oferta_elencopadrao_ids' => 'array',
    ];

    public function ligaOrigem(): BelongsTo
    {
        return $this->belongsTo(Liga::class, 'liga_origem_id');
    }

    public function ligaDestino(): BelongsTo
    {
        return $this->belongsTo(Liga::class, 'liga_destino_id');
    }

    public function confederacao(): BelongsTo
    {
        return $this->belongsTo(Confederacao::class);
    }

    public function clubeOrigem(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'clube_origem_id');
    }

    public function clubeDestino(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'clube_destino_id');
    }

    public function elencopadrao(): BelongsTo
    {
        return $this->belongsTo(Elencopadrao::class, 'elencopadrao_id');
    }
}
