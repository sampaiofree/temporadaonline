<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Elencopadrao;
use App\Models\Jogo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ElencoPadraoController extends Controller
{
    private const MATCH_STRATEGY_PLAYER_ID = 'player_id';
    private const IMPORT_BATCH_SIZE = 500;

    public function index(Request $request): View
    {
        $import = session('elenco_import');
        $search = trim((string) $request->query('q', ''));
        $jogos = Jogo::orderBy('nome')->get(['id', 'nome']);
        $jogosResumo = Jogo::withCount('elencoPadrao')
            ->orderByDesc('elenco_padrao_count')
            ->get(['id', 'nome']);
        $totalPlayers = $jogosResumo->sum('elenco_padrao_count');
        $playersQuery = Elencopadrao::query()
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
            ->orderBy('long_name');

        if ($search !== '') {
            $playersQuery->where(function ($query) use ($search) {
                $query->where('long_name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%");
            });
        }

        $players = $playersQuery->paginate(100)->withQueryString();
        $fields = $this->importableFields();
        $labels = $this->fieldLabels($fields);

        $step = $import ? 2 : 1;
        $columns = [];
        $mapping = array_fill_keys($fields, '');
        $previewFields = [];
        $previewRows = [];
        $jogoSelecionado = null;
        $matchStrategy = self::MATCH_STRATEGY_PLAYER_ID;

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
            'requiredMappingFields' => $this->requiredMappingFields($matchStrategy),
            'search' => $search,
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
                    'match_strategy' => self::MATCH_STRATEGY_PLAYER_ID,
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
        $matchStrategy = self::MATCH_STRATEGY_PLAYER_ID;

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
            'M d, Y',
            'M j, Y',
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

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $exception) {
            return null;
        }
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
        // For now the only supported strategy is player_id
        $matchStrategy = self::MATCH_STRATEGY_PLAYER_ID;
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
        $casts = (new Elencopadrao())->getCasts();
        $columnTypes = $this->resolveImportColumnTypes($mappedFields);
        $created = 0;
        $updated = 0;
        $ignored = 0;
        $batch = [];

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

            $prepared = $this->prepareImportRow($payload, $jogoId, $casts, $columnTypes);
            if ($prepared === null) {
                $ignored++;
                continue;
            }

            $batch[] = $prepared;

            if (count($batch) >= self::IMPORT_BATCH_SIZE) {
                $summary = $this->flushImportBatch($jogoId, $batch, $mappedFields);
                $created += $summary['created'];
                $updated += $summary['updated'];
                $ignored += $summary['ignored'];
                $batch = [];
            }
        }

        if ($batch !== []) {
            $summary = $this->flushImportBatch($jogoId, $batch, $mappedFields);
            $created += $summary['created'];
            $updated += $summary['updated'];
            $ignored += $summary['ignored'];
        }

        fclose($handle);

        return [
            'created' => $created,
            'updated' => $updated,
            'ignored' => $ignored,
        ];
    }

    private function prepareImportRow(array $payload, int $jogoId, array $casts, array $columnTypes): ?array
    {
        $matchValue = isset($payload['player_id']) ? trim((string) $payload['player_id']) : '';
        $posicao = isset($payload['player_positions']) ? trim((string) $payload['player_positions']) : '';
        $overall = $payload['overall'] ?? null;

        if ($matchValue === '' || $posicao === '' || $overall === null || $overall === '') {
            return null;
        }

        if (! is_numeric($overall)) {
            return null;
        }

        $overallValue = (int) round((float) $overall);
        if ($overallValue < 1 || $overallValue > 99) {
            return null;
        }

        $payload['player_id'] = $matchValue;
        $payload['player_positions'] = $posicao;
        $payload['overall'] = $overallValue;
        $payload = $this->normalizeImportPayload($payload, $casts, $columnTypes);
        $payload['jogo_id'] = $jogoId;

        return $payload;
    }

    private function normalizeImportPayload(array $payload, array $casts, array $columnTypes): array
    {
        foreach ($payload as $field => $value) {
            if ($value === null) {
                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
                $payload[$field] = $value === '' ? null : $value;
            }

            if ($payload[$field] === null) {
                continue;
            }

            $cast = $casts[$field] ?? null;
            $columnType = $columnTypes[$field] ?? null;

            if ($cast === 'integer' || in_array($columnType, ['integer', 'bigint', 'smallint'], true)) {
                $payload[$field] = $this->normalizeIntegerValue($payload[$field]);
                continue;
            }

            if ($cast === 'boolean' || $columnType === 'boolean') {
                $payload[$field] = $this->normalizeBooleanValue($payload[$field]);
                continue;
            }

            if ($cast === 'date' || $columnType === 'date') {
                $payload[$field] = $this->parseDateString((string) $payload[$field]);
            }
        }

        return $payload;
    }

    private function normalizeIntegerValue(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value);
        }

        $rawValue = Str::of((string) $value)
            ->trim()
            ->replace("\u{00A0}", ' ')
            ->upper()
            ->replace('€', '')
            ->replace('$', '')
            ->replace(' ', '')
            ->value();

        if ($rawValue === '' || $rawValue === '?' || $rawValue === '#VALOR!') {
            return null;
        }

        if (preg_match('/^(-?\d+(?:[.,]\d+)?)([KMB])$/', $rawValue, $matches) === 1) {
            $number = (float) str_replace(',', '.', $matches[1]);
            $multiplier = match ($matches[2]) {
                'K' => 1_000,
                'M' => 1_000_000,
                'B' => 1_000_000_000,
                default => 1,
            };

            return (int) round($number * $multiplier);
        }

        $normalized = Str::of($rawValue)
            ->replace(',', '.')
            ->replaceMatches('/[^0-9.\-]/', '')
            ->value();

        if ($normalized === '' || $normalized === '-' || ! is_numeric($normalized)) {
            return null;
        }

        return (int) round((float) $normalized);
    }

    private function normalizeBooleanValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = Str::lower(trim((string) $value));

        return match ($normalized) {
            '1', 'true', 'yes', 'sim' => true,
            '0', 'false', 'no', 'nao', 'não' => false,
            default => null,
        };
    }

    private function flushImportBatch(int $jogoId, array $batch, array $mappedFields): array
    {
        if ($batch === []) {
            return ['created' => 0, 'updated' => 0, 'ignored' => 0];
        }

        $deduplicated = [];
        foreach ($batch as $row) {
            $deduplicated[(string) $row['player_id']] = $row;
        }

        $rows = array_values($deduplicated);
        $playerIds = array_keys($deduplicated);
        $existing = DB::table('elencopadrao')
            ->where('jogo_id', $jogoId)
            ->whereIn('player_id', $playerIds)
            ->pluck('id', 'player_id')
            ->all();

        $rowsToPersist = [];
        $created = 0;
        $updated = 0;
        $ignored = count($batch) - count($rows);

        foreach ($rows as $row) {
            $playerId = (string) $row['player_id'];
            $exists = array_key_exists($playerId, $existing);

            if (! $exists && ($row['long_name'] ?? null) === null) {
                $ignored++;
                continue;
            }

            $rowsToPersist[] = $row;

            if ($exists) {
                $updated++;
            } else {
                $created++;
            }
        }

        if ($rowsToPersist === []) {
            return ['created' => 0, 'updated' => 0, 'ignored' => $ignored];
        }

        $updateColumns = array_values(array_filter(
            $mappedFields,
            fn (string $field): bool => $field !== 'player_id',
        ));

        try {
            DB::table('elencopadrao')->upsert(
                $rowsToPersist,
                ['jogo_id', 'player_id'],
                $updateColumns,
            );

            return [
                'created' => $created,
                'updated' => $updated,
                'ignored' => $ignored,
            ];
        } catch (QueryException $exception) {
            $created = 0;
            $updated = 0;

            foreach ($rowsToPersist as $row) {
                $playerId = (string) $row['player_id'];
                $exists = array_key_exists($playerId, $existing);

                try {
                    DB::table('elencopadrao')->updateOrInsert(
                        [
                            'jogo_id' => $jogoId,
                            'player_id' => $playerId,
                        ],
                        $row,
                    );

                    if ($exists) {
                        $updated++;
                    } else {
                        $created++;
                    }
                } catch (QueryException $rowException) {
                    $ignored++;
                }
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'ignored' => $ignored,
        ];
    }

    private function resolveImportColumnTypes(array $fields): array
    {
        $types = [];

        foreach ($fields as $field) {
            $types[$field] = Schema::getColumnType('elencopadrao', $field);
        }

        return $types;
    }

    private function sanitizeMatchStrategy(?string $matchStrategy): string
    {
        return self::MATCH_STRATEGY_PLAYER_ID;
    }

    private function requiredMappingFields(string $matchStrategy): array
    {
        return ['player_id', 'long_name', 'player_positions', 'overall'];
    }
}
