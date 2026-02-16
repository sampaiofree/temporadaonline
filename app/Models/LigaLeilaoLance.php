<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaLeilaoLance extends Model
{
    protected $table = 'liga_leilao_lances';

    protected $fillable = [
        'liga_leilao_item_id',
        'confederacao_id',
        'elencopadrao_id',
        'clube_id',
        'valor',
        'expira_em',
    ];

    protected $casts = [
        'valor' => 'integer',
        'expira_em' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(LigaLeilaoItem::class, 'liga_leilao_item_id');
    }

    public function confederacao(): BelongsTo
    {
        return $this->belongsTo(Confederacao::class, 'confederacao_id');
    }

    public function elencopadrao(): BelongsTo
    {
        return $this->belongsTo(Elencopadrao::class, 'elencopadrao_id');
    }

    public function clube(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'clube_id');
    }
}

