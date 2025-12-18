<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaTransferencia extends Model
{
    protected $table = 'liga_transferencias';

    protected $fillable = [
        'liga_id',
        'elencopadrao_id',
        'clube_origem_id',
        'clube_destino_id',
        'tipo',
        'valor',
        'observacao',
    ];

    protected $casts = [
        'valor' => 'integer',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function elencopadrao(): BelongsTo
    {
        return $this->belongsTo(Elencopadrao::class, 'elencopadrao_id');
    }

    public function clubeOrigem(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'clube_origem_id');
    }

    public function clubeDestino(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'clube_destino_id');
    }
}

