<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

    protected $table = 'profiles';

    protected $fillable = [
        'user_id',
        'nickname',
        'avatar',
        'whatsapp',
        'regiao',
        'idioma',
        'reputacao_score',
        'nivel',
        'plataforma_id',
        'jogo_id',
        'geracao_id',
        'regiao_id',
        'idioma_id',
    ];

    protected $casts = [
        'reputacao_score' => 'integer',
        'nivel' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plataformaRegistro(): BelongsTo
    {
        return $this->belongsTo(Plataforma::class, 'plataforma_id');
    }

    public function jogoRegistro(): BelongsTo
    {
        return $this->belongsTo(Jogo::class, 'jogo_id');
    }

    public function geracaoRegistro(): BelongsTo
    {
        return $this->belongsTo(Geracao::class, 'geracao_id');
    }

    public function regiaoRegistro(): BelongsTo
    {
        return $this->belongsTo(Regiao::class, 'regiao_id');
    }

    public function idiomaRegistro(): BelongsTo
    {
        return $this->belongsTo(Idioma::class, 'idioma_id');
    }

    public function getPlataformaNomeAttribute(): ?string
    {
        return $this->plataformaRegistro?->nome ?? $this->attributes['plataforma'] ?? null;
    }

    public function getJogoNomeAttribute(): ?string
    {
        return $this->jogoRegistro?->nome ?? $this->attributes['jogo'] ?? null;
    }

    public function getGeracaoNomeAttribute(): ?string
    {
        return $this->geracaoRegistro?->nome ?? $this->attributes['geracao'] ?? null;
    }

    public function getRegiaoNomeAttribute(): ?string
    {
        return $this->regiaoRegistro?->nome ?? $this->attributes['regiao'] ?? null;
    }

    public function getIdiomaNomeAttribute(): ?string
    {
        return $this->idiomaRegistro?->nome ?? $this->attributes['idioma'] ?? null;
    }
}
