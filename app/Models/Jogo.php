<?php

namespace App\Models;

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
}
