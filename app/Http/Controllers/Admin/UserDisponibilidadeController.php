<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDisponibilidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserDisponibilidadeController extends Controller
{
    public function index(User $user): View
    {
        $disponibilidades = UserDisponibilidade::query()
            ->where('user_id', $user->id)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();

        return view('admin.users.horarios', [
            'user' => $user,
            'disponibilidades' => $disponibilidades,
            'dayLabels' => $this->dayLabels(),
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        try {
            $data = $this->validateDisponibilidade($request);
            $this->assertNoOverlap($user->id, $data);
        } catch (ValidationException $exception) {
            return $this->backWithModalErrors($user, 'create', $exception);
        }

        UserDisponibilidade::create(array_merge($data, ['user_id' => $user->id]));

        return redirect()
            ->route('admin.users.horarios.index', $user)
            ->with('success', 'Horário adicionado com sucesso.');
    }

    public function update(Request $request, User $user, UserDisponibilidade $disponibilidade): RedirectResponse
    {
        $disponibilidade = UserDisponibilidade::query()
            ->where('user_id', $user->id)
            ->findOrFail($disponibilidade->id);

        try {
            $data = $this->validateDisponibilidade($request);
            $this->assertNoOverlap($user->id, $data, $disponibilidade->id);
        } catch (ValidationException $exception) {
            return $this->backWithModalErrors($user, 'edit', $exception, $disponibilidade->id);
        }

        $disponibilidade->fill($data);
        $disponibilidade->save();

        return redirect()
            ->route('admin.users.horarios.index', $user)
            ->with('success', 'Horário atualizado com sucesso.');
    }

    public function destroy(User $user, UserDisponibilidade $disponibilidade): RedirectResponse
    {
        $disponibilidade = UserDisponibilidade::query()
            ->where('user_id', $user->id)
            ->findOrFail($disponibilidade->id);

        $disponibilidade->delete();

        return redirect()
            ->route('admin.users.horarios.index', $user)
            ->with('success', 'Horário removido com sucesso.');
    }

    private function validateDisponibilidade(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'dia_semana' => ['required', 'integer', 'min:0', 'max:6'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $inicio = $request->input('hora_inicio');
            $fim = $request->input('hora_fim');

            if ($inicio && $fim && $inicio >= $fim) {
                $validator->errors()->add('hora_inicio', 'Hora início deve ser menor que hora fim.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function assertNoOverlap(int $userId, array $data, ?int $ignoreId = null): void
    {
        $query = UserDisponibilidade::query()
            ->where('user_id', $userId)
            ->where('dia_semana', $data['dia_semana'])
            ->when($ignoreId, fn ($q) => $q->where('id', '<>', $ignoreId))
            ->where(function ($q) use ($data) {
                $q->where('hora_inicio', '<=', $data['hora_fim'])
                    ->where('hora_fim', '>=', $data['hora_inicio']);
            });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'hora_inicio' => ['Horário se sobrepõe ou encosta em outra janela existente.'],
            ]);
        }
    }

    private function backWithModalErrors(
        User $user,
        string $mode,
        ValidationException $exception,
        ?int $disponibilidadeId = null,
    ): RedirectResponse {
        $response = redirect()
            ->route('admin.users.horarios.index', $user)
            ->withErrors($exception->errors())
            ->withInput()
            ->with('horarios_modal', $mode);

        if ($disponibilidadeId) {
            $response->with('horarios_modal_id', $disponibilidadeId);
        }

        return $response;
    }

    private function dayLabels(): array
    {
        return [
            0 => 'Domingo',
            1 => 'Segunda',
            2 => 'Terça',
            3 => 'Quarta',
            4 => 'Quinta',
            5 => 'Sexta',
            6 => 'Sábado',
        ];
    }
}
