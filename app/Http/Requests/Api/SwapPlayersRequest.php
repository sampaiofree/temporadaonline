<?php

namespace App\Http\Requests\Api;

use App\Models\Liga;
use App\Models\LigaClube;
use Illuminate\Foundation\Http\FormRequest;

class SwapPlayersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $liga = $this->route('liga');
        $clube = $this->route('clube');

        if (! $user || ! $liga instanceof Liga || ! $clube instanceof LigaClube) {
            return false;
        }

        if ((int) $clube->user_id !== (int) $user->id) {
            return false;
        }

        if ((int) $clube->liga_id !== (int) $liga->id) {
            return false;
        }

        return $user->ligas()->where('ligas.id', $liga->id)->exists();
    }

    public function rules(): array
    {
        return [
            'jogador_a_id' => ['required', 'integer', 'exists:elencopadrao,id'],
            'clube_b_id' => ['required', 'integer', 'exists:liga_clubes,id'],
            'jogador_b_id' => ['required', 'integer', 'exists:elencopadrao,id'],
            'ajuste_valor' => ['nullable', 'integer'],
        ];
    }
}

