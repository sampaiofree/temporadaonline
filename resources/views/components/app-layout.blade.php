@props([
    'title' => config('app.name', 'Painel Administrativo'),
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        @include('components.app_assets')
        @vite(['resources/css/admin.css'])
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="h-full font-sans antialiased text-slate-900">
        <div class="flex min-h-screen">
            <aside class="w-64 flex-shrink-0 border-r border-slate-200 bg-white">
                <div class="flex h-full flex-col">
                    <div class="flex h-16 items-center px-6 border-b border-slate-100">
                        <span class="text-xl font-bold text-blue-600 tracking-tight">ADMIN<span class="text-slate-400">CORE</span></span>
                    </div>

                    <nav class="flex-1 space-y-4 px-3 py-4">
                        <a href="{{ route('admin.dashboard') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-600' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                            Dashboard
                        </a>

                        <x-sidebar-group
                            title="CADASTRO"
                            icon="plus-circle"
                            :active="request()->routeIs(['admin.jogos.*', 'admin.geracoes.*', 'admin.plataformas.*', 'admin.elenco-padrao.*', 'admin.confederacoes.*', 'admin.ligas.*', 'admin.conquistas.*', 'admin.patrocinios.*', 'admin.premiacoes.*', 'admin.idioma-regiao.*', 'admin.clube-tamanho.*'])"
                        >
                            <div class="space-y-1 py-1">
                                <x-nav-link href="{{ route('admin.jogos.index') }}" :active="request()->routeIs('admin.jogos.*')">Jogos</x-nav-link>
                                <x-nav-link href="{{ route('admin.geracoes.index') }}" :active="request()->routeIs('admin.geracoes.*')">Gerações</x-nav-link>
                                <x-nav-link href="{{ route('admin.plataformas.index') }}" :active="request()->routeIs('admin.plataformas.*')">Plataformas</x-nav-link>
                                <x-nav-link href="{{ route('admin.elenco-padrao.index') }}" :active="request()->routeIs('admin.elenco-padrao.*')">Elenco Padrão</x-nav-link>
                                <x-nav-link href="{{ route('admin.confederacoes.index') }}" :active="request()->routeIs('admin.confederacoes.*')">Confederação</x-nav-link>
                                <x-nav-link href="{{ route('admin.ligas.index') }}" :active="request()->routeIs('admin.ligas.*')">Liga</x-nav-link>
                                <x-nav-link href="{{ route('admin.conquistas.index') }}" :active="request()->routeIs('admin.conquistas.*')">Conquistas</x-nav-link>
                                <x-nav-link href="{{ route('admin.patrocinios.index') }}" :active="request()->routeIs('admin.patrocinios.*')">Patrocínios</x-nav-link>
                                <x-nav-link href="{{ route('admin.premiacoes.index') }}" :active="request()->routeIs('admin.premiacoes.*')">Premiações</x-nav-link>
                                <x-nav-link href="{{ route('admin.idioma-regiao.index') }}" :active="request()->routeIs('admin.idioma-regiao.*')">Idiomas e Regiões</x-nav-link>
                                <x-nav-link href="{{ route('admin.clube-tamanho.index') }}" :active="request()->routeIs('admin.clube-tamanho.*')">Clube Tamanho</x-nav-link>
                            </div>
                        </x-sidebar-group>

                        <x-sidebar-group
                            title="ADMINISTRAÇÃO"
                            icon="shield-check"
                            :active="request()->routeIs(['admin.clubes.*', 'admin.ligas-usuarios.*', 'admin.users.*', 'admin.partidas-denuncias.*', 'admin.whatsapp.*', 'admin.logs.*'])"
                        >
                            <div class="space-y-1 py-1">
                                <x-nav-link href="{{ route('admin.clubes.index') }}" :active="request()->routeIs('admin.clubes.*')">Clubes</x-nav-link>
                                <x-nav-link href="{{ route('admin.ligas-usuarios.index') }}" :active="request()->routeIs('admin.ligas-usuarios.*')">Usuários por Liga</x-nav-link>
                                <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">Usuários</x-nav-link>
                                <x-nav-link href="{{ route('admin.partidas-denuncias.index') }}" :active="request()->routeIs('admin.partidas-denuncias.*')">Denúncias de Partida</x-nav-link>
                                <x-nav-link href="{{ route('admin.whatsapp.index') }}" :active="request()->routeIs('admin.whatsapp.*')">WhatsApp</x-nav-link>
                                <x-nav-link href="{{ route('admin.logs.index') }}" :active="request()->routeIs('admin.logs.*')">Logs</x-nav-link>
                            </div>
                        </x-sidebar-group>

                        <x-sidebar-group
                            title="UPLOAD IMAGENS"
                            icon="image-plus"
                            :active="request()->routeIs(['admin.paises.*', 'admin.ligas-escudos.*', 'admin.escudos-clubes.*', 'admin.playstyles.*', 'admin.app-assets.*'])"
                        >
                            <div class="space-y-1 py-1">
                                <x-nav-link href="{{ route('admin.paises.index') }}" :active="request()->routeIs('admin.paises.*')">Países</x-nav-link>
                                <x-nav-link href="{{ route('admin.ligas-escudos.index') }}" :active="request()->routeIs('admin.ligas-escudos.*')">Escudos de Ligas</x-nav-link>
                                <x-nav-link href="{{ route('admin.escudos-clubes.index') }}" :active="request()->routeIs('admin.escudos-clubes.*')">Escudos de Clubes</x-nav-link>
                                <x-nav-link href="{{ route('admin.playstyles.index') }}" :active="request()->routeIs('admin.playstyles.*')">Playstyles</x-nav-link>
                                <x-nav-link href="{{ route('admin.app-assets.edit') }}" :active="request()->routeIs('admin.app-assets.*')">Imagens do App</x-nav-link>
                            </div>
                        </x-sidebar-group>
                    </nav>

                    <div class="border-t border-slate-100 p-4">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                Sair do sistema
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="flex flex-1 flex-col overflow-hidden">
                <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-8">
                    <h1 class="text-sm font-medium uppercase tracking-wider text-slate-500">{{ $title }}</h1>
                    <div class="flex items-center gap-4">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">AD</span>
                    </div>
                </header>

                <main class="flex-1 overflow-y-auto bg-slate-50 p-8">
                    <div class="mx-auto max-w-7xl">
                        @isset($header)
                            <div class="mb-8">
                                {{ $header }}
                            </div>
                        @endisset
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
        <script>window.addEventListener('DOMContentLoaded', () => { if (window.lucide) { lucide.createIcons(); } });</script>
    </body>
</html>
