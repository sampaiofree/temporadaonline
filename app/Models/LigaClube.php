<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LigaClube extends Model
{
    protected $table = 'liga_clubes';

    protected $fillable = [
        'liga_id',
        'confederacao_id',
        'user_id',
        'nome',
        'escudo_clube_id',
        'esquema_tatico_imagem',
        'esquema_tatico_layout',
    ];

    protected $casts = [
        'esquema_tatico_layout' => 'array',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function confederacao(): BelongsTo
    {
        return $this->belongsTo(Confederacao::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function escudo(): BelongsTo
    {
        return $this->belongsTo(EscudoClube::class, 'escudo_clube_id');
    }

    public function clubeElencos(): HasMany
    {
        return $this->hasMany(LigaClubeElenco::class);
    }

    public function conquistas(): HasMany
    {
        return $this->hasMany(LigaClubeConquista::class, 'liga_clube_id');
    }

    public function financeiro(): HasOne
    {
        return $this->hasOne(LigaClubeFinanceiro::class, 'clube_id');
    }
}
