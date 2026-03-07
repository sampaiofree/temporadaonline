<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LigaClubeVendaMercado extends Model
{
    protected $table = 'liga_clube_vendas_mercado';

    protected $fillable = [
        'user_id',
        'confederacao_id',
        'liga_id',
        'liga_clube_id',
        'elencopadrao_id',
        'valor_base',
        'valor_credito',
        'tax_percent',
        'tax_value',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'confederacao_id' => 'integer',
        'liga_id' => 'integer',
        'liga_clube_id' => 'integer',
        'elencopadrao_id' => 'integer',
        'valor_base' => 'integer',
        'valor_credito' => 'integer',
        'tax_percent' => 'integer',
        'tax_value' => 'integer',
    ];
}
