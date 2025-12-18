<?php

namespace App\Http\Requests\Api;

use App\Models\Liga;
use Illuminate\Foundation\Http\FormRequest;

class ChargePayrollRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'rodada' => $this->route('rodada'),
        ]);
    }

    public function authorize(): bool
    {
        $user = $this->user();
        $liga = $this->route('liga');

        if (! $user || ! $liga instanceof Liga) {
            return false;
        }

        return $user->ligas()->where('ligas.id', $liga->id)->exists();
    }

    public function rules(): array
    {
        return [
            'rodada' => ['required', 'integer', 'min:1'],
        ];
    }
}

