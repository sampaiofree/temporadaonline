<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Elencopadrao;
use App\Models\Jogo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ElencoPadraoController extends Controller
{
    public function index(): View
    {
        return view('admin.elenco_padrao.index', [
            'jogos' => Jogo::orderBy('nome')->get(),
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
        $data = $request->validate([
            'jogo_id' => 'required|exists:jogos,id',
            'csv' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $jogoId = $data['jogo_id'];
        $handle = fopen($request->file('csv')->getRealPath(), 'r');

        if ($handle !== false) {
            $header = fgetcsv($handle, 0, ';');

            if ($header !== false) {
                $columns = array_map(function ($column) {
                    $clean = trim(str_replace("\xEF\xBB\xBF", '', (string) $column));
                    return mb_strtolower($clean);
                }, $header);

                $indexes = array_flip($columns);

                while (($row = fgetcsv($handle, 0, ';')) !== false) {
                    $row = array_map(fn ($value) => trim((string) $value), $row);

                    $nome = $this->pluckColumn($row, $indexes, 'nome');
                    $posicao = $this->pluckColumn($row, $indexes, 'posicao');
                    $overall = $this->pluckColumn($row, $indexes, 'overall');
                    $clube = $this->pluckColumn($row, $indexes, 'clube');

                    if ($nome === '' || $posicao === '' || $overall === '') {
                        continue;
                    }

                    if (! is_numeric($overall)) {
                        continue;
                    }

                    $overallValue = (int) $overall;
                    if ($overallValue < 1 || $overallValue > 99) {
                        continue;
                    }

                    $payload = [
                        'player_positions' => $posicao,
                        'overall' => $overallValue,
                    ];

                    if ($clube !== '') {
                        $payload['club_name'] = $clube;
                    }

                    Elencopadrao::updateOrCreate(
                        [
                            'jogo_id' => $jogoId,
                            'long_name' => $nome,
                        ],
                        $payload
                    );
                }
            }

            fclose($handle);
        }

        return redirect()->route('admin.elenco-padrao.index')
            ->with('success', 'Elenco importado com sucesso');
    }

    private function pluckColumn(array $row, array $indexes, string $column): string
    {
        if (! isset($indexes[$column])) {
            return '';
        }

        return $row[$indexes[$column]] ?? '';
    }
}
