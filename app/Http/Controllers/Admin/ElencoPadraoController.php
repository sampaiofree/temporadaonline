<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Elencopadrao;
use App\Models\Jogo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ElencoPadraoController extends Controller
{
    public function index(): View
    {
        $import = session('elenco_import');
        $jogos = Jogo::orderBy('nome')->get(['id', 'nome']);
        $fields = $this->importableFields();
        $labels = $this->fieldLabels($fields);

        $step = $import ? 2 : 1;
        $columns = [];
        $mapping = array_fill_keys($fields, '');
        $previewFields = [];
        $previewRows = [];
        $jogoSelecionado = null;

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
            }
        }

        return view('admin.elenco_padrao.index', [
            'jogos' => $jogos,
            'fields' => $fields,
            'labels' => $labels,
            'step' => $step,
            'columns' => $columns,
            'mapping' => $mapping,
            'previewFields' => $previewFields,
            'previewRows' => $previewRows,
            'jogoSelecionado' => $jogoSelecionado,
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

        $errors = $this->validateMapping($mapping);
        if (isset($errors['duplicate'])) {
            return redirect()->route('admin.elenco-padrao.index')
                ->withErrors(['mapping' => $errors['duplicate']]);
        }

        if (isset($errors['required'])) {
            session()->put('elenco_import.mapping', $mapping);
            return redirect()->route('admin.elenco-padrao.index')
                ->withErrors(['mapping' => $errors['required']]);
        }

        session()->put('elenco_import.mapping', $mapping);

        if ($step === 'preview') {
            return redirect()->route('admin.elenco-padrao.index');
        }

        if ($step === 'confirm') {
            $this->runImport($import['path'], (int) $import['jogo_id'], $columns, $mapping);
            Storage::delete($import['path']);
            session()->forget('elenco_import');

            return redirect()->route('admin.elenco-padrao.index')
                ->with('success', 'Elenco importado com sucesso');
        }

        return redirect()->route('admin.elenco-padrao.index');
    }

    private function importableFields(): array
    {
        $columns = Schema::getColumnListing('elencopadrao');
        $blocked = ['id', 'jogo_id', 'created_at', 'updated_at'];

        return array_values(array_filter($columns, fn ($column) => ! in_array($column, $blocked, true)));
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

    private function validateMapping(array $mapping): array
    {
        $errors = [];
        $required = ['long_name', 'player_positions', 'overall'];
        $missing = array_filter($required, fn ($field) => ($mapping[$field] ?? '') === '');

        if (! empty($missing)) {
            $errors['required'] = 'Mapeie os campos obrigatórios: nome, posição e overall.';
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

    private function runImport(string $path, int $jogoId, array $columns, array $mapping): void
    {
        if (! Storage::exists($path)) {
            return;
        }

        $handle = fopen(Storage::path($path), 'r');
        if ($handle === false) {
            return;
        }

        fgetcsv($handle, 0, ';');
        $indexes = array_flip($columns);
        $mappedFields = array_keys(array_filter($mapping));

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

            if ($nome === null || $nome === '' || $posicao === null || $posicao === '' || $overall === null || $overall === '') {
                continue;
            }

            if (! is_numeric($overall)) {
                continue;
            }

            $overallValue = (int) $overall;
            if ($overallValue < 1 || $overallValue > 99) {
                continue;
            }

            $payload['overall'] = $overallValue;

            if (array_key_exists('potential', $payload)) {
                $payload['potential'] = $payload['potential'] !== null && is_numeric($payload['potential'])
                    ? (int) $payload['potential']
                    : null;
            }

            Elencopadrao::updateOrCreate(
                [
                    'jogo_id' => $jogoId,
                    'long_name' => $nome,
                ],
                $payload
            );
        }

        fclose($handle);
    }
}
