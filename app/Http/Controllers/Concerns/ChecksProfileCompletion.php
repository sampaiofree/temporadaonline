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

        return filled($profile->plataforma)
            && filled($profile->jogo)
            && filled($profile->nickname)
            && filled($profile->geracao)
            && filled($profile->whatsapp);
    }
}
