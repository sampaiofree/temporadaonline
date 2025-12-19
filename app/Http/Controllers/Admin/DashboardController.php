<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jogo;
use App\Models\Liga;
use App\Models\Partida;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalUsers = User::count();
        $totalLigas = Liga::count();
        $ligasAtivas = Liga::where('status', 'ativa')->count();
        $jogosCadastrados = Jogo::count();
        $partidasPendentes = Partida::where('estado', 'confirmacao_necessaria')->count();

        $formatNumber = fn (int $value): string => number_format($value, 0, ',', '.');

        $metrics = [
            [
                'label' => 'Total de usuários',
                'value' => $formatNumber($totalUsers),
                'description' => 'Cadastros confirmados no painel.',
            ],
            [
                'label' => 'Total de ligas',
                'value' => $formatNumber($totalLigas),
                'description' => 'Ligas criadas pelos organizadores.',
            ],
            [
                'label' => 'Ligas ativas',
                'value' => $formatNumber($ligasAtivas),
                'description' => 'Ligas com status definido como ativa.',
            ],
            [
                'label' => 'Jogos cadastrados',
                'value' => $formatNumber($jogosCadastrados),
                'description' => 'Catálogo de jogos que o admin controla.',
            ],
            [
                'label' => 'Partidas pendentes',
                'value' => $formatNumber($partidasPendentes),
                'description' => 'Confirmação necessária para avançar em partidas.',
            ],
        ];

        $alerts = [
            'Login administrativo usa o formulário padrão do Breeze (/login).',
            'Somente usuários com is_admin = true acessam /admin/*.',
            'Registre cada ação crítica na futura tabela admin_logs.',
        ];

        if (Schema::hasTable('admin_logs')) {
            $acoesRecentes = DB::table('admin_logs')
                ->leftJoin('users', 'admin_logs.admin_id', '=', 'users.id')
                ->orderBy('admin_logs.created_at', 'desc')
                ->limit(5)
                ->get([
                    'admin_logs.admin_id',
                    'users.name as admin_name',
                    'admin_logs.acao',
                    'admin_logs.descricao',
                    'admin_logs.entidade',
                    'admin_logs.entidade_id',
                    'admin_logs.created_at',
                ])
                ->map(function ($log) {
                    $data = Carbon::parse($log->created_at);

                    return [
                        'autor' => $log->admin_name ?? "Admin #{$log->admin_id}",
                        'acao' => $log->acao ?? "Operação em {$log->entidade}",
                        'descricao' => $log->descricao ?? 'Registro sem detalhes.',
                        'quando' => $data->diffForHumans(),
                    ];
                });
        } else {
            $acoesRecentes = collect([
                [
                    'autor' => 'Sistema',
                    'acao' => 'Painel administrativo carregado',
                    'descricao' => 'Visão corrente do ecossistema apresentada.',
                    'quando' => 'agora',
                ],
                [
                    'autor' => 'Admin',
                    'acao' => 'Aprimorar logs críticos',
                    'descricao' => 'Planejamento da tabela admin_logs em andamento.',
                    'quando' => 'em breve',
                ],
            ]);
        }

        return view('admin.dashboard.index', [
            'metrics' => $metrics,
            'alerts' => $alerts,
            'acoesRecentes' => $acoesRecentes,
            'lastUpdated' => now(),
        ]);
    }
}
