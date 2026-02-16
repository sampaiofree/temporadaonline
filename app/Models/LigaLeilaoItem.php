<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LigaLeilaoItem extends Model
{
    protected $table = 'liga_leilao_itens';

    protected $fillable = [
        'confederacao_id',
        'elencopadrao_id',
        'clube_lider_id',
        'valor_inicial',
        'valor_atual',
        'expira_em',
        'status',
        'motivo_cancelamento',
        'finalized_at',
    ];

    protected $casts = [
        'valor_inicial' => 'integer',
        'valor_atual' => 'integer',
        'expira_em' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function confederacao(): BelongsTo
    {
        return $this->belongsTo(Confederacao::class, 'confederacao_id');
    }

    public function elencopadrao(): BelongsTo
    {
        return $this->belongsTo(Elencopadrao::class, 'elencopadrao_id');
    }

    public function clubeLider(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'clube_lider_id');
    }

    public function lances(): HasMany
    {
        return $this->hasMany(LigaLeilaoLance::class, 'liga_leilao_item_id');
    }
}

