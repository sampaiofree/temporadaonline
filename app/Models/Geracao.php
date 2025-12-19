<?php

namespace App\Models;

use App\Models\Liga;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Geracao extends Model
{
    protected $table = 'geracoes';

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
}
