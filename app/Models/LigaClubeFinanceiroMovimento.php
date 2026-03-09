<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigaClubeFinanceiroMovimento extends Model
{
    public const OPERATION_CREDIT = 'credit';
    public const OPERATION_DEBIT = 'debit';
    public const OPERATION_SNAPSHOT_OPENING = 'snapshot_abertura';

    protected $table = 'liga_clube_financeiro_movimentos';

    protected $fillable = [
        'liga_id',
        'clube_id',
        'operacao',
        'descricao',
        'valor',
        'saldo_antes',
        'saldo_depois',
        'metadata',
    ];

    protected $casts = [
        'operacao' => 'string',
        'valor' => 'integer',
        'saldo_antes' => 'integer',
        'saldo_depois' => 'integer',
        'metadata' => 'array',
    ];

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class);
    }

    public function clube(): BelongsTo
    {
        return $this->belongsTo(LigaClube::class, 'clube_id');
    }
}

