<?php

namespace App\Models;

use App\Models\Elencopadrao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaClubeElenco extends Model
{
    protected $table = 'liga_clube_elencos';

    protected $fillable = [
        'confederacao_id',
        'liga_id',
        'liga_clube_id',
        'elencopadrao_id',
        'value_eur',
        'wage_eur',
        'ativo',
    ];

    protected $casts = [
        'value_eur' => 'integer',
        'wage_eur' => 'integer',
        'ativo' => 'boolean',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function confederacao(): BelongsTo
    {
        return $this->belongsTo(Confederacao::class);
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
