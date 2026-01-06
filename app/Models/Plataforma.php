<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Plataforma extends Model
{
    protected $fillable = [
        'nome',
        'slug',
        'imagem',
    ];

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }

    public function ligas(): HasMany
    {
        return $this->hasMany(Liga::class);
    }
}
