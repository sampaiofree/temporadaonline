<?php

namespace App\Models;

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
}
