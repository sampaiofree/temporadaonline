<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Models\User;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'nome' => ['required_without:name', 'string', 'max:255'],
            'name' => ['required_without:nome', 'string', 'max:255'],
            'nickname' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('profiles', 'nickname')->ignore($user?->profile?->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'plataforma_id' => ['nullable', 'integer', 'exists:plataformas,id'],
            'jogo_id' => ['nullable', 'integer', 'exists:jogos,id'],
            'geracao_id' => ['nullable', 'integer', 'exists:geracoes,id'],
        ];
    }
}
