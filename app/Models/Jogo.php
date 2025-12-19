<?php

namespace App\Models;

use App\Models\Elencopadrao;
use App\Models\Liga;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Jogo extends Model
{
    protected $fillable = [
        'nome',
        'slug',
    ];

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }

    public function ligas(): HasMany
    {
        return $this->hasMany(Liga::class);
    }

    public function elencoPadrao(): HasMany
    {
        return $this->hasMany(Elencopadrao::class, 'jogo_id');
    }
}
