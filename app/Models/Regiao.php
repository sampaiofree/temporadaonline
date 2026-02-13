<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Regiao extends Model
{
    use HasFactory;

    protected $table = 'regioes';

    protected $fillable = [
        'nome',
        'slug',
    ];

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'regiao_id');
    }
}
