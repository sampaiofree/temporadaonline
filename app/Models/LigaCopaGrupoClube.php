<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaCopaGrupoClube extends Model
{
    protected $table = 'liga_copa_grupo_clubes';

    protected $fillable = [
        'grupo_id',
        'liga_clube_id',
        'ordem',
    ];

    protected $casts = [
        'ordem' => 'integer',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(LigaCopaGrupo::class, 'grupo_id');
    }

    public function ligaClube(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'liga_clube_id');
    }
}
