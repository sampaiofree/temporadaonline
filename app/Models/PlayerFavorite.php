<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'liga_id',
        'confederacao_id',
        'elencopadrao_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function confederacao(): BelongsTo
    {
        return $this->belongsTo(Confederacao::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Elencopadrao::class, 'elencopadrao_id');
    }
}
