<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalUsers = User::count();
        $ligasAtivas = Liga::where('status', 'ativa')->count();

        $metrics = [
            [
                'label' => 'Total de usuários',
                'value' => $totalUsers,
                'highlight' => 'global',
            ],
            [
                'label' => 'Ligas ativas',
                'value' => $ligasAtivas,
                'highlight' => 'gold',
            ],
            [
                'label' => 'Partidas pendentes',
                'value' => 0,
                'highlight' => 'muted',
            ],
            [
                'label' => 'Alertas críticos',
                'value' => 0,
                'highlight' => 'danger',
            ],
        ];

        $alertasCriticos = [
            [
                'titulo' => 'Logs administrativos não configurados',
                'descricao' => 'Habilite a tabela admin_logs para registrar toda ação crítica.',
                'nivel' => 'alto',
            ],
            [
                'titulo' => 'Financeiro sem motivo obrigatório',
                'descricao' => 'Garanta que todo ajuste manual registre um motivo claro.',
                'nivel' => 'medio',
            ],
        ];

        $acoesRecentes = [
            [
                'autor' => 'Sistema',
                'acao' => 'Estrutura inicial do painel criada',
                'quando' => 'agora',
            ],
            [
                'autor' => 'Admin',
                'acao' => 'Próximas tarefas: configurar login e logs',
                'quando' => 'em breve',
            ],
        ];

        return view('admin.dashboard.index', [
            'metrics' => $metrics,
            'alertasCriticos' => $alertasCriticos,
            'acoesRecentes' => $acoesRecentes,
        ]);
    }
}
