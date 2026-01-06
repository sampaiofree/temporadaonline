<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappConnection extends Model
{
    protected $table = 'whatsapp_connections';

    protected $fillable = [
        'instance_name',
        'status',
        'qr_code',
        'connected_at',
        'last_status_at',
        'created_by',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'last_status_at' => 'datetime',
    ];
}
