<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LigaEscudo extends Model
{
    use SoftDeletes;

    protected $table = 'ligas_escudos';

    protected $fillable = [
        'liga_nome',
        'pais_id',
        'liga_imagem',
    ];

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class);
    }
}
