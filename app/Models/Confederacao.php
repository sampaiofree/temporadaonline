<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Confederacao extends Model
{
    protected $table = 'confederacoes';

    protected $fillable = [
        'nome',
        'descricao',
        'imagem',
        'timezone',
        'jogo_id',
        'geracao_id',
        'plataforma_id',
    ];

    public function ligas(): HasMany
    {
        return $this->hasMany(Liga::class);
    }

    public function temporadas(): HasMany
    {
        return $this->hasMany(Temporada::class);
    }

    public function periodos(): HasMany
    {
        return $this->hasMany(LigaPeriodo::class, 'confederacao_id');
    }

    public function leiloes(): HasMany
    {
        return $this->hasMany(LigaLeilao::class, 'confederacao_id');
    }

    public function jogo(): BelongsTo
    {
        return $this->belongsTo(Jogo::class);
    }

    public function geracao(): BelongsTo
    {
        return $this->belongsTo(Geracao::class);
    }

    public function plataforma(): BelongsTo
    {
        return $this->belongsTo(Plataforma::class);
    }
}
