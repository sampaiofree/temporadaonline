<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaClubePatrocinio extends Model
{
    protected $table = 'liga_clube_patrocinios';

    protected $fillable = [
        'liga_id',
        'liga_clube_id',
        'patrocinio_id',
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

    public function patrocinio(): BelongsTo
    {
        return $this->belongsTo(Patrocinio::class);
    }
}
