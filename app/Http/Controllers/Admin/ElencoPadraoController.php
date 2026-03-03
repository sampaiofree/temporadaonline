<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Elencopadrao;
use App\Models\Jogo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ElencoPadraoController extends Controller
{
    private const MATCH_STRATEGY_JOGO_LONG_NAME = 'jogo_long_name';
    private const MATCH_STRATEGY_PLAYER_ID = 'player_id';

    public function index(): View
    {
        $import = session('elenco_import');
        $jogos = Jogo::orderBy('nome')->get(['id', 'nome']);
        $jogosResumo = Jogo::withCount('elencoPadrao')
            ->orderByDesc('elenco_padrao_count')
            ->get(['id', 'nome']);
        $totalPlayers = $jogosResumo->sum('elenco_padrao_count');
        $players = Elencopadrao::query()
            ->select([
                'id',
                'jogo_id',
                'long_name',
                'short_name',
                'player_positions',
                'overall',
                'club_name',
                'player_face_url',
            ])
            ->with('jogo:id,nome')
            ->orderBy('long_name')
            ->paginate(100);
        $fields = $this->importableFields();
        $labels = $this->fieldLabels($fields);

        $step = $import ? 2 : 1;
        $columns = [];
        $mapping = array_fill_keys($fields, '');
        $previewFields = [];
        $previewRows = [];
        $jogoSelecionado = null;
        $matchStrategy = self::MATCH_STRATEGY_JOGO_LONG_NAME;

        if ($import) {
            $path = $import['path'] ?? null;
            if (! $path || ! Storage::exists($path)) {
                session()->forget('elenco_import');
                $step = 1;
            } else {
                $columns = $import['columns'] ?? [];
                $mapping = $import['mapping'] ?? $mapping;
                $mapping = $this->sanitizeMapping($mapping, $fields, $columns);
                $previewFields = array_keys(array_filter($mapping));
                $previewRows = $this->buildPreview($path, $columns, $mapping, 5);
                $jogoSelecionado = $jogos->firstWhere('id', $import['jogo_id'] ?? null);
                $matchStrategy = $this->sanitizeMatchStrategy($import['match_strategy'] ?? null);
            }
        }

        return view('admin.elenco_padrao.index', [
            'jogos' => $jogos,
            'jogosResumo' => $jogosResumo,
            'totalPlayers' => $totalPlayers,
            'players' => $players,
            'fields' => $fields,
            'labels' => $labels,
            'step' => $step,
            'columns' => $columns,
            'mapping' => $mapping,
            'previewFields' => $previewFields,
            'previewRows' => $previewRows,
            'jogoSelecionado' => $jogoSelecionado,
            'matchStrategy' => $matchStrategy,
            'matchStrategies' => $this->matchStrategies(),
            'requiredMappingFields' => $this->requiredMappingFields($matchStrategy),
        ]);
    }

    public function jogadores(): View
    {
        $jogos = Jogo::withCount('elencoPadrao')->orderByDesc('elenco_padrao_count')->get();
        $total = $jogos->sum('elenco_padrao_count');
        $players = Elencopadrao::query()
            ->select([
                'id',
                'jogo_id',
                'long_name',
                'short_name',
                'player_positions',
                'overall',
                'club_name',
                'player_face_url',
            ])
            ->with('jogo:id,nome')
            ->orderBy('long_name')
            ->paginate(100);

        return view('admin.elenco_padrao.jogadores', [
            'totalPlayers' => $total,
            'players' => $players,
            'jogos' => $jogos,
        ]);
    }

    public function destroyJogadores(Request $request, Jogo $jogo): RedirectResponse
    {
        $redirectTo = $request->input('redirect_to', route('admin.elenco-padrao.jogadores'));
        $total = $jogo->elencoPadrao()->count();
        $deleted = $jogo->elencoPadrao()
            ->whereDoesntHave('ligaClubeElencos')
            ->whereDoesntHave('ligaTransferencias')
            ->delete();
        $blocked = $total - $deleted;

        if ($deleted === 0 && $blocked > 0) {
            return redirect()
                ->to($redirectTo)
                ->with('error', 'Nenhum jogador removido. Existem jogadores vinculados a ligas ou transferencias.');
        }

        $label = $deleted === 1 ? 'jogador' : 'jogadores';
        $message = sprintf('%d %s removidos do jogo %s.', $deleted, $label, $jogo->nome);

        if ($blocked > 0) {
            $message .= sprintf(' %d jogadores nao puderam ser removidos por estarem vinculados a ligas ou transferencias.', $blocked);
        }

        return redirect()
            ->to($redirectTo)
            ->with('success', $message);
    }

    public function edit(Request $request, Elencopadrao $player): View
    {
        $fields = $this->editableFields();

        return view('admin.elenco_padrao.edit', [
            'player' => $player->load('jogo'),
            'fields' => $fields,
            'labels' => $this->fieldLabels($fields),
            'casts' => $player->getCasts(),
            'redirectTo' => $request->query('redirect', route('admin.elenco-padrao.jogadores')),
        ]);
    }

    public function update(Request $request, Elencopadrao $player): RedirectResponse
    {
        $fields = $this->editableFields();
        $casts = $player->getCasts();
        $rules = $this->buildEditRules($fields, $casts);

        $validated = $request->validate($rules);
        $payload = $this->normalizeEditPayload($validated, $casts);

        $player->update($payload);

        $redirectTo = $request->input('redirect_to', route('admin.elenco-padrao.jogadores'));

        return redirect()
            ->to($redirectTo)
            ->with('success', 'Jogador atualizado com sucesso.');
    }

    public function destroyJogador(Elencopadrao $player): RedirectResponse
    {
        if ($player->ligaClubeElencos()->exists() || $player->ligaTransferencias()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Nao e possivel excluir este jogador porque ele esta vinculado a ligas ou transferencias.');
        }

        $player->delete();

        return redirect()
            ->back()
            ->with('success', 'Jogador removido com sucesso.');
    }

    public function importar(Request $request): RedirectResponse
    {
        $step = $request->input('step', 'upload');

        if ($step === 'upload') {
            $previous = session('elenco_import');
            if ($previous && ! empty($previous['path'])) {
                Storage::delete($previous['path']);
            }
            session()->forget('elenco_import');

            $data = $request->validate([
                'jogo_id' => 'required|exists:jogos,id',
                'csv' => 'required|file|mimes:csv,txt|max:20480',
                'match_strategy' => 'required|in:'.implode(',', array_keys($this->matchStrategies())),
            ]);

            $path = $request->file('csv')->store('tmp');
            $columns = $this->readColumns($path);

            if (count($columns) === 0) {
                Storage::delete($path);
                return redirect()->route('admin.elenco-padrao.index')
                    ->withErrors(['csv' => 'Não foi possível ler o cabeçalho do CSV.']);
            }

            $mapping = $this->autoMap($columns);

            session([
                'elenco_import' => [
                    'path' => $path,
                    'jogo_id' => (int) $data['jogo_id'],
                    'columns' => $columns,
                    'mapping' => $mapping,
                    'match_strategy' => $this->sanitizeMatchStrategy($data['match_strategy']),
                ],
            ]);

            return redirect()->route('admin.elenco-padrao.index');
        }

        $import = session('elenco_import');
        if (! $import) {
            return redirect()->route('admin.elenco-padrao.index');
        }

        if ($step === 'cancel') {
            if (! empty($import['path'])) {
                Storage::delete($import['path']);
            }

            session()->forget('elenco_import');

            return redirect()->route('admin.elenco-padrao.index')
                ->with('success', 'Importação cancelada com sucesso');
        }

        $fields = $this->importableFields();
        $columns = $import['columns'] ?? [];
        $mappingInput = $request->input('mapping', []);
        $mapping = $this->sanitizeMapping($mappingInput, $fields, $columns);
        $matchStrategy = $this->sanitizeMatchStrategy(
            $request->input('match_strategy', $import['match_strategy'] ?? null)
        );

        $errors = $this->validateMapping($mapping, $matchStrategy);
        if (isset($errors['duplicate'])) {
            return redirect()->route('admin.elenco-padrao.index')
                ->withErrors(['mapping' => $errors['duplicate']]);
        }

        if (isset($errors['required'])) {
            session()->put('elenco_import.mapping', $mapping);
            session()->put('elenco_import.match_strategy', $matchStrategy);
            return redirect()->route('admin.elenco-padrao.index')
                ->withErrors(['mapping' => $errors['required']]);
        }

        session()->put('elenco_import.mapping', $mapping);
        session()->put('elenco_import.match_strategy', $matchStrategy);

        if ($step === 'preview') {
            return redirect()->route('admin.elenco-padrao.index');
        }

        if ($step === 'confirm') {
            $summary = $this->runImport(
                $import['path'],
                (int) $import['jogo_id'],
                $columns,
                $mapping,
                $matchStrategy,
            );
            Storage::delete($import['path']);
            session()->forget('elenco_import');

            $successMessage = sprintf(
                'Importacao concluida. Criados: %d | Atualizados: %d | Ignorados: %d.',
                (int) ($summary['created'] ?? 0),
                (int) ($summary['updated'] ?? 0),
                (int) ($summary['ignored'] ?? 0),
            );

            return redirect()->route('admin.elenco-padrao.index')
                ->with('success', $successMessage);
        }

        return redirect()->route('admin.elenco-padrao.index');
    }

    private function importableFields(): array
    {
        $columns = Schema::getColumnListing('elencopadrao');
        $blocked = ['id', 'jogo_id', 'created_at', 'updated_at'];

        return array_values(array_filter($columns, fn ($column) => ! in_array($column, $blocked, true)));
    }

    private function editableFields(): array
    {
        $columns = Schema::getColumnListing('elencopadrao');
        $blocked = ['id', 'jogo_id', 'created_at', 'updated_at'];

        return array_values(array_filter($columns, fn ($column) => ! in_array($column, $blocked, true)));
    }

    private function buildEditRules(array $fields, array $casts): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $cast = $casts[$field] ?? null;
            $rule = 'nullable';

            if ($cast === 'integer') {
                $rule .= '|integer';
            } elseif ($cast === 'boolean') {
                $rule .= '|boolean';
            } elseif ($cast === 'date') {
                $rule .= '|date';
            } else {
                $rule .= '|string';
            }

            $rules[$field] = $rule;
        }

        return $rules;
    }

    private function normalizeEditPayload(array $payload, array $casts): array
    {
        foreach ($payload as $field => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $payload[$field] = $value === '' ? null : $value;
            }

            if ($payload[$field] === null) {
                continue;
            }

            $cast = $casts[$field] ?? null;
            if ($cast === 'integer') {
                $payload[$field] = (int) $payload[$field];
            } elseif ($cast === 'boolean') {
                $payload[$field] = (bool) $payload[$field];
            }
        }

        return $payload;
    }

    private function readColumns(string $path): array
    {
        if (! Storage::exists($path)) {
            return [];
        }

        $handle = fopen(Storage::path($path), 'r');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle, 0, ';');
        fclose($handle);

        if (! is_array($header)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($column) {
            return trim(str_replace("\xEF\xBB\xBF", '', (string) $column));
        }, $header), fn ($column) => $column !== ''));
    }

    private function normalize(string $value): string
    {
        return Str::lower(trim($value));
    }

    private function autoMap(array $columns): array
    {
        $fields = $this->importableFields();
        $map = array_fill_keys($fields, '');
        $normalized = [];
        $used = [];

        foreach ($columns as $column) {
            $normalized[$this->normalize($column)] = $column;
        }

        $this->applyAutoMap($map, $normalized, $used, 'long_name', ['long_name', 'nome', 'player_name']);
        $this->applyAutoMap($map, $normalized, $used, 'player_id', ['player_id', 'id_player', 'sofifa_id']);
        $this->applyAutoMap($map, $normalized, $used, 'short_name', ['short_name', 'apelido', 'nickname']);
        $this->applyAutoMap($map, $normalized, $used, 'overall', ['overall', 'ovr', 'rating']);
        $this->applyAutoMap($map, $normalized, $used, 'potential', ['potential', 'overall_potencial', 'potencial']);
        $this->applyAutoMap($map, $normalized, $used, 'player_positions', ['player_positions', 'posicao', 'position']);
        $this->applyAutoMap($map, $normalized, $used, 'club_name', ['club_name', 'clube', 'team', 'club']);
        $this->applyAutoMap($map, $normalized, $used, 'player_face_url', ['player_face_url', 'face_url', 'photo', 'imagem']);

        foreach ($fields as $field) {
            if ($map[$field] !== '') {
                continue;
            }

            $key = $this->normalize($field);
            if (! isset($normalized[$key])) {
                continue;
            }

            $column = $normalized[$key];
            if (in_array($column, $used, true)) {
                continue;
            }

            $map[$field] = $column;
            $used[] = $column;
        }

        if (isset($map['long_name']) && $map['long_name'] === '' && ($map['short_name'] ?? '') !== '') {
            $map['long_name'] = $map['short_name'];
            $map['short_name'] = '';
        }

        return $map;
    }

    private function applyAutoMap(array &$map, array $normalized, array &$used, string $field, array $candidates): void
    {
        if (! array_key_exists($field, $map)) {
            return;
        }

        foreach ($candidates as $candidate) {
            $key = $this->normalize($candidate);
            if (! isset($normalized[$key])) {
                continue;
            }

            $column = $normalized[$key];
            if (in_array($column, $used, true)) {
                continue;
            }

            $map[$field] = $column;
            $used[] = $column;
            break;
        }
    }

    private function normalizePayloadDates(array &$payload): void
    {
        foreach (['dob', 'club_joined_date'] as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $payload[$field] = $this->parseDateString($payload[$field]);
        }
    }

    private function parseDateString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = [
            'd/m/Y',
            'd/M/Y',
            'Y-m-d',
            'Y-m-d H:i:s',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
            } catch (\Exception $exception) {
                continue;
            }

            return $date->toDateString();
        }

        return null;
    }

    private function sanitizeMapping(array $mapping, array $fields, array $columns): array
    {
        $sanitized = array_fill_keys($fields, '');
        $allowed = array_flip($columns);

        foreach ($fields as $field) {
            $value = $mapping[$field] ?? '';
            $value = is_string($value) ? trim($value) : '';
            $sanitized[$field] = $value !== '' && isset($allowed[$value]) ? $value : '';
        }

        return $sanitized;
    }

    private function validateMapping(array $mapping, string $matchStrategy): array
    {
        $errors = [];
        $required = $this->requiredMappingFields($matchStrategy);
        $missing = array_filter($required, fn ($field) => ($mapping[$field] ?? '') === '');

        if (! empty($missing)) {
            $fieldNames = collect($missing)
                ->map(fn (string $field) => $this->fieldLabels([$field])[$field] ?? $field)
                ->implode(', ');

            $errors['required'] = 'Mapeie os campos obrigatorios: '.$fieldNames.'.';
        }

        $selected = array_values(array_filter($mapping));
        if (count($selected) !== count(array_unique($selected))) {
            $errors['duplicate'] = 'Uma coluna do CSV foi usada mais de uma vez.';
        }

        return $errors;
    }

    private function fieldLabels(array $fields): array
    {
        $custom = [
            'long_name' => 'Nome completo',
            'short_name' => 'Nome curto',
            'player_positions' => 'Posição',
            'overall' => 'Overall',
            'potential' => 'Potencial',
            'club_name' => 'Clube',
            'player_face_url' => 'Foto do jogador',
        ];

        $labels = [];
        foreach ($fields as $field) {
            if (isset($custom[$field])) {
                $labels[$field] = $custom[$field];
                continue;
            }

            $labels[$field] = Str::title(str_replace('_', ' ', $field));
        }

        return $labels;
    }

    private function buildPreview(string $path, array $columns, array $mapping, int $limit): array
    {
        if (! Storage::exists($path)) {
            return [];
        }

        $handle = fopen(Storage::path($path), 'r');
        if ($handle === false) {
            return [];
        }

        fgetcsv($handle, 0, ';');

        $indexes = array_flip($columns);
        $rows = [];
        $mappedFields = array_keys(array_filter($mapping));

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($rows) >= $limit) {
                break;
            }

            $entry = [];
            foreach ($mappedFields as $field) {
                $column = $mapping[$field];
                $index = $indexes[$column] ?? null;
                $entry[$field] = $index === null ? '' : trim((string) ($row[$index] ?? ''));
            }

            if (count($entry) > 0) {
                $rows[] = $entry;
            }
        }

        fclose($handle);

        return $rows;
    }

    private function runImport(
        string $path,
        int $jogoId,
        array $columns,
        array $mapping,
        string $matchStrategy,
    ): array
    {
        if (! Storage::exists($path)) {
            return ['created' => 0, 'updated' => 0, 'ignored' => 0];
        }

        $handle = fopen(Storage::path($path), 'r');
        if ($handle === false) {
            return ['created' => 0, 'updated' => 0, 'ignored' => 0];
        }

        fgetcsv($handle, 0, ';');
        $indexes = array_flip($columns);
        $mappedFields = array_keys(array_filter($mapping));
        $updated = 0;
        $ignored = 0;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $payload = [];
            foreach ($mappedFields as $field) {
                $column = $mapping[$field];
                $index = $indexes[$column] ?? null;
                $payload[$field] = $index === null ? '' : trim((string) ($row[$index] ?? ''));
            }

            foreach ($payload as $field => $value) {
                $payload[$field] = $value === '' ? null : $value;
            }

            $nome = $payload['long_name'] ?? null;
            $posicao = $payload['player_positions'] ?? null;
            $overall = $payload['overall'] ?? null;
            $matchValue = $matchStrategy === self::MATCH_STRATEGY_PLAYER_ID
                ? ($payload['player_id'] ?? null)
                : $nome;

            if ($matchValue === null || $matchValue === '' || $posicao === null || $posicao === '' || $overall === null || $overall === '') {
                $ignored++;
                continue;
            }

            if (! is_numeric($overall)) {
                $ignored++;
                continue;
            }

            $overallValue = (int) $overall;
            if ($overallValue < 1 || $overallValue > 99) {
                $ignored++;
                continue;
            }

            $payload['overall'] = $overallValue;

            if (array_key_exists('potential', $payload)) {
                $payload['potential'] = $payload['potential'] !== null && is_numeric($payload['potential'])
                    ? (int) $payload['potential']
                    : null;
            }

            $this->normalizePayloadDates($payload);

            $target = null;

            if ($matchStrategy === self::MATCH_STRATEGY_PLAYER_ID) {
                $targets = Elencopadrao::query()
                    ->where('player_id', $matchValue)
                    ->limit(2)
                    ->get();

                if ($targets->count() !== 1) {
                    $ignored++;
                    continue;
                }

                $target = $targets->first();
            } else {
                $targets = Elencopadrao::query()
                    ->where('jogo_id', $jogoId)
                    ->where('long_name', $matchValue)
                    ->limit(2)
                    ->get();

                if ($targets->count() !== 1) {
                    $ignored++;
                    continue;
                }

                $target = $targets->first();
            }

            $target->update($payload);
            $updated++;
        }

        fclose($handle);

        return [
            'created' => 0,
            'updated' => $updated,
            'ignored' => $ignored,
        ];
    }

    private function matchStrategies(): array
    {
        return [
            self::MATCH_STRATEGY_JOGO_LONG_NAME => 'jogo_id + long_name',
            self::MATCH_STRATEGY_PLAYER_ID => 'player_id',
        ];
    }

    private function sanitizeMatchStrategy(?string $matchStrategy): string
    {
        $value = is_string($matchStrategy) ? trim($matchStrategy) : '';

        return array_key_exists($value, $this->matchStrategies())
            ? $value
            : self::MATCH_STRATEGY_JOGO_LONG_NAME;
    }

    private function requiredMappingFields(string $matchStrategy): array
    {
        $required = ['player_positions', 'overall'];

        if ($matchStrategy === self::MATCH_STRATEGY_PLAYER_ID) {
            $required[] = 'player_id';
        } else {
            $required[] = 'long_name';
        }

        return $required;
    }
}
