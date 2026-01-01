<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppAsset extends Model
{
    protected $table = 'app_assets';

    protected $fillable = [
        'favicon',
        'logo_padrao',
        'logo_dark',
        'imagem_campo',
        'background_app',
    ];
}
