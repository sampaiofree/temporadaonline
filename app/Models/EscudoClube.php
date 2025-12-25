<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EscudoClube extends Model
{
    use SoftDeletes;

    protected $table = 'escudos_clubes';

    protected $fillable = [
        'clube_nome',
        'pais_id',
        'liga_id',
        'clube_imagem',
    ];

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class);
    }

    public function liga(): BelongsTo
    {
        return $this->belongsTo(LigaEscudo::class, 'liga_id');
    }
}
