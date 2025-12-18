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
        'plataforma',
        'geracao',
        'jogo',
        'regiao',
        'idioma',
        'reputacao_score',
        'nivel',
        'plataforma_id',
        'jogo_id',
        'geracao_id',
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
}
