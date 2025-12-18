<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MCO | Admin')</title>
    @vite(['resources/css/app.css'])
    <style>
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
    </style>
</head>
<body class="bg-[#121212] text-white min-h-screen" style="font-family: var(--font-body);">
    <div class="min-h-screen flex">
        <aside class="w-64 bg-[#1e1e1e] border-r-4 border-[#ffd700] p-6 flex flex-col gap-6">
            <div class="flex items-center justify-between">
                <div class="text-xl font-bold uppercase tracking-wide" style="font-family: var(--font-heading);">MCO Admin</div>
                <span class="text-xs bg-[#ffd700] text-black px-2 py-1 font-semibold" style="font-family: var(--font-heading);">beta</span>
            </div>
            <nav class="flex flex-col gap-3 text-sm uppercase tracking-wide">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between bg-[#2b2b2b] px-3 py-2 border-l-4 border-[#ffd700]">Dashboard</a>
                <a class="flex items-center justify-between px-3 py-2 bg-[#1e1e1e] border-l-4 border-transparent" href="#">Jogos</a>
                <a class="flex items-center justify-between px-3 py-2 bg-[#1e1e1e] border-l-4 border-transparent" href="#">Gerações</a>
                <a class="flex items-center justify-between px-3 py-2 bg-[#1e1e1e] border-l-4 border-transparent" href="#">Plataformas</a>
                <a class="flex items-center justify-between px-3 py-2 bg-[#1e1e1e] border-l-4 border-transparent" href="#">Elencos</a>
                <a class="flex items-center justify-between px-3 py-2 bg-[#1e1e1e] border-l-4 border-transparent" href="#">Ligas</a>
                <a class="flex items-center justify-between px-3 py-2 bg-[#1e1e1e] border-l-4 border-transparent" href="#">Usuários</a>
            </nav>
            <div class="text-xs text-gray-300 leading-relaxed">
                <p class="font-semibold" style="font-family: var(--font-heading);">Regras de Ouro</p>
                <ul class="list-disc list-inside space-y-1 mt-2">
                    <li>Sem React no admin</li>
                    <li>Sem delete físico</li>
                    <li>Toda ação gera log</li>
                </ul>
            </div>
        </aside>

        <main class="flex-1 flex flex-col">
            <header class="border-b-4 border-[#ffd700] bg-[#1b1b1b] px-8 py-5 flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-300 uppercase">Ambiente Administrativo</p>
                    <h1 class="text-2xl font-bold uppercase" style="font-family: var(--font-heading);">@yield('page_title', 'Dashboard')</h1>
                </div>
                <div class="flex gap-3 text-sm">
                    <span class="bg-[#ffd700] text-black px-4 py-2 font-semibold" style="font-family: var(--font-heading);">Admin</span>
                    <button class="bg-[#333333] px-4 py-2 border-b-4 border-[#ffd700] uppercase font-semibold" style="font-family: var(--font-heading);">Sair</button>
                </div>
            </header>

            <section class="flex-1 p-8 bg-[#121212]">
                @yield('content')
            </section>
        </main>
    </div>
</body>
</html>
