<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Profile;

trait ChecksProfileCompletion
{
    protected function hasCompleteProfile(?Profile $profile): bool
    {
        if (! $profile) {
            return false;
        }

        return filled($profile->regiao_id)
            && filled($profile->idioma_id)
            && filled($profile->plataforma_id)
            && filled($profile->jogo_id)
            && filled($profile->nickname)
            && filled($profile->geracao_id)
            && filled($profile->whatsapp);
    }
}
