<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LigaCopaGrupo extends Model
{
    protected $table = 'liga_copa_grupos';

    protected $fillable = [
        'liga_id',
        'ordem',
        'label',
    ];

    protected $casts = [
        'ordem' => 'integer',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(LigaCopaGrupoClube::class, 'grupo_id');
    }

    public function cupMatches(): HasMany
    {
        return $this->hasMany(LigaCopaPartida::class, 'grupo_id');
    }
}
