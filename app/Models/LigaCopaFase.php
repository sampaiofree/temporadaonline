<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LigaCopaFase extends Model
{
    protected $table = 'liga_copa_fases';

    protected $fillable = [
        'liga_id',
        'tipo',
        'ordem',
        'status',
    ];

    protected $casts = [
        'ordem' => 'integer',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function cupMatches(): HasMany
    {
        return $this->hasMany(LigaCopaPartida::class, 'fase_id');
    }
}
