<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Liga extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
        'regras',
        'usuario_pontuacao',
        'whatsapp_grupo_link',
        'whatsapp_grupo_jid',
        'imagem',
        'tipo',
        'status',
        'max_times',
        'max_jogadores_por_clube',
        'saldo_inicial',
        'multa_multiplicador',
        'cobranca_salario',
        'venda_min_percent',
        'bloquear_compra_saldo_negativo',
        'confederacao_id',
        'jogo_id',
        'geracao_id',
        'plataforma_id',
    ];

    protected $casts = [
        'max_times' => 'integer',
        'max_jogadores_por_clube' => 'integer',
        'saldo_inicial' => 'integer',
        'usuario_pontuacao' => 'float',
        'multa_multiplicador' => 'decimal:2',
        'venda_min_percent' => 'integer',
        'bloquear_compra_saldo_negativo' => 'boolean',
    ];

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

    public function confederacao(): BelongsTo
    {
        return $this->belongsTo(Confederacao::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'liga_jogador', 'liga_id', 'user_id')->withTimestamps();
    }

    public function clubes(): HasMany
    {
        return $this->hasMany(LigaClube::class);
    }

    public function transferencias(): HasMany
    {
        return $this->hasMany(LigaTransferencia::class);
    }

    public function periodos(): HasMany
    {
        return $this->hasMany(LigaPeriodo::class);
    }

    public function leiloes(): HasMany
    {
        return $this->hasMany(LigaLeilao::class);
    }
}
