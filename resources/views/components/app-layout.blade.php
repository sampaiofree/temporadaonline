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
        @vite(['resources/css/admin.css'])
    </head>
    <body class="h-full font-sans antialiased text-slate-900">
        <div class="flex min-h-screen">
            <aside class="w-64 flex-shrink-0 border-r border-slate-200 bg-white">
                <div class="flex h-full flex-col">
                    <div class="flex h-16 items-center px-6 border-b border-slate-100">
                        <span class="text-xl font-bold text-blue-600 tracking-tight">ADMIN<span class="text-slate-400">CORE</span></span>
                    </div>

                    <nav class="flex-1 space-y-1 px-3 py-4">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Dashboard
                        </a>
                        
                        <a href="{{ route('admin.geracoes.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Gerações
                        </a>
                        <a href="{{ route('admin.jogos.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Jogos
                        </a>
                        <a href="{{ route('admin.plataformas.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Plataformas
                        </a>
                        <a href="{{ route('admin.paises.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Países
                        </a>
                        <a href="{{ route('admin.ligas-escudos.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Escudos de ligas
                        </a>
                       
                        <a href="{{ route('admin.escudos-clubes.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Escudos de clubes
                        </a>
                        <a href="{{ route('admin.elenco-padrao.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Elenco padrão
                        </a>
                        <a href="{{ route('admin.ligas.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Ligas
                        </a>
                         <a href="{{ route('admin.clubes.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Clubes por liga
                        </a>
                        <a href="{{ route('admin.ligas-usuarios.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Usuários por liga
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                            Usuários
                        </a>
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
        <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
        <script>window.addEventListener('DOMContentLoaded', () => { if (window.lucide) { lucide.createIcons(); } });</script>
    </body>
</html>
