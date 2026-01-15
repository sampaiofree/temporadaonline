<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaClubeConquista extends Model
{
    protected $fillable = [
        'liga_id',
        'liga_clube_id',
        'conquista_id',
        'claimed_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function clube(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'liga_clube_id');
    }

    public function conquista(): BelongsTo
    {
        return $this->belongsTo(Conquista::class);
    }
}
