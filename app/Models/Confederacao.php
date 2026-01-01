<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Confederacao extends Model
{
    protected $table = 'confederacoes';

    protected $fillable = [
        'nome',
        'descricao',
        'imagem',
    ];

    public function ligas(): HasMany
    {
        return $this->hasMany(Liga::class);
    }
}
