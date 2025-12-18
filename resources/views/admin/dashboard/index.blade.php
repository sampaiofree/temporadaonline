@extends('admin.layout')

@section('title', 'MCO | Admin Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div class="flex flex-col gap-8">
    <section class="bg-[#1e1e1e] border-b-4 border-[#ffd700] p-6 shadow-none">
        <header class="flex items-center justify-between mb-4">
            <div>
                <p class="text-xs uppercase text-gray-400">Visão geral</p>
                <h2 class="text-xl font-bold" style="font-family: var(--font-heading);">Status do ecossistema</h2>
            </div>
            <button class="bg-[#ffd700] text-black px-4 py-2 font-semibold uppercase" style="font-family: var(--font-heading);">Reset seguro</button>
        </header>
        <div class="admin-grid">
            @foreach ($metrics as $metric)
                <div class="p-4 bg-[#2a2a2a] border-l-4 @class([
                    'border-[#ffd700]' => $metric['highlight'] === 'gold' || $metric['highlight'] === 'global',
                    'border-[#ff3b30]' => $metric['highlight'] === 'danger',
                    'border-gray-500' => $metric['highlight'] === 'muted',
                ])">
                    <p class="text-xs uppercase text-gray-400">{{ $metric['label'] }}</p>
                    <p class="text-3xl font-black" style="font-family: var(--font-heading);">{{ $metric['value'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-[#1e1e1e] border-b-4 border-[#ffd700] p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs uppercase text-gray-400">Alertas críticos</p>
                    <h3 class="text-lg font-bold" style="font-family: var(--font-heading);">Atenção imediata</h3>
                </div>
                <span class="text-xs bg-[#ff3b30] px-3 py-1 font-semibold" style="font-family: var(--font-heading);">Auditável</span>
            </div>
            <div class="space-y-4">
                @foreach ($alertasCriticos as $alerta)
                    <div class="bg-[#2a2a2a] p-4 border-l-4 border-[#ff3b30]">
                        <p class="text-sm font-semibold" style="font-family: var(--font-heading);">{{ $alerta['titulo'] }}</p>
                        <p class="text-sm text-gray-200 mt-1">{{ $alerta['descricao'] }}</p>
                        <p class="text-xs uppercase text-gray-400 mt-2">Prioridade: {{ strtoupper($alerta['nivel']) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="bg-[#1e1e1e] border-b-4 border-[#ffd700] p-6">
            <p class="text-xs uppercase text-gray-400">Próximos passos</p>
            <h3 class="text-lg font-bold mb-4" style="font-family: var(--font-heading);">Checklist do admin</h3>
            <ul class="space-y-3 text-sm">
                <li class="flex justify-between items-center bg-[#2a2a2a] px-3 py-2">
                    <span>Configurar login dedicado (/admin/login)</span>
                    <span class="text-xs bg-[#ffd700] text-black px-2 py-1 font-semibold">TODO</span>
                </li>
                <li class="flex justify-between items-center bg-[#2a2a2a] px-3 py-2">
                    <span>Implementar admin_logs</span>
                    <span class="text-xs bg-[#ffd700] text-black px-2 py-1 font-semibold">TODO</span>
                </li>
                <li class="flex justify-between items-center bg-[#2a2a2a] px-3 py-2">
                    <span>Aplicar motivo obrigatório no financeiro</span>
                    <span class="text-xs bg-[#ffd700] text-black px-2 py-1 font-semibold">TODO</span>
                </li>
            </ul>
        </div>
    </section>

    <section class="bg-[#1e1e1e] border-b-4 border-[#ffd700] p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-xs uppercase text-gray-400">Log operacional</p>
                <h3 class="text-lg font-bold" style="font-family: var(--font-heading);">Últimas ações</h3>
            </div>
            <a href="#" class="text-xs uppercase underline">Ver todo o histórico</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
            @foreach ($acoesRecentes as $acao)
                <div class="bg-[#2a2a2a] p-4 border-l-4 border-[#ffd700] flex flex-col gap-1">
                    <p class="text-sm font-semibold" style="font-family: var(--font-heading);">{{ $acao['acao'] }}</p>
                    <p class="text-xs text-gray-300">Responsável: {{ $acao['autor'] }}</p>
                    <p class="text-xs text-gray-500 uppercase">{{ $acao['quando'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="bg-[#1e1e1e] border-b-4 border-[#ffd700] p-6">
        <p class="text-xs uppercase text-gray-400">Acesso rápido</p>
        <h3 class="text-lg font-bold mb-4" style="font-family: var(--font-heading);">Domine cada módulo</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ([
                ['titulo' => 'Jogos', 'descricao' => 'Cadastre jogos e versões do EAFC/PES.'],
                ['titulo' => 'Gerações', 'descricao' => 'Monte ciclos Nova/Antiga geração.'],
                ['titulo' => 'Plataformas', 'descricao' => 'Controle PS5, Xbox, PC e status.'],
                ['titulo' => 'Elenco Padrão', 'descricao' => 'Importe CSV e corrija overalls.'],
                ['titulo' => 'Ligas', 'descricao' => 'Configure regras, saldo e plataforma.'],
                ['titulo' => 'Clubes', 'descricao' => 'Ajuste escudos e saldos por liga.'],
                ['titulo' => 'Financeiro', 'descricao' => 'Audite transferências e folhas.'],
                ['titulo' => 'Usuários', 'descricao' => 'Suspenda, bane e acompanhe histórico.'],
            ] as $card)
                <div class="bg-[#2a2a2a] p-4 border-l-4 border-[#ffd700]">
                    <p class="text-sm font-semibold" style="font-family: var(--font-heading);">{{ $card['titulo'] }}</p>
                    <p class="text-sm text-gray-200 mt-1">{{ $card['descricao'] }}</p>
                    <button class="mt-3 text-xs uppercase bg-[#ffd700] text-black px-3 py-2 font-semibold" style="font-family: var(--font-heading);">Abrir</button>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
