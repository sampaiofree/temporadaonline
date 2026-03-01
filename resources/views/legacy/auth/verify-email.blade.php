<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Legacy XI | Verificar Email</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@1,700;1,900&family=Russo+One&display=swap" rel="stylesheet">
        <style>
            :root {
                --legacy-bg: #121212;
                --legacy-surface: #1e1e1e;
                --legacy-gold: #ffd700;
                --legacy-red: #b22222;
            }

            body {
                font-family: 'Exo 2', sans-serif;
                background-color: var(--legacy-bg);
                color: #fff;
                -webkit-tap-highlight-color: transparent;
                margin: 0;
                overflow-x: hidden;
            }

            .font-heading {
                font-family: 'Russo One', sans-serif;
                letter-spacing: -0.05em;
            }

            .legacy-clip {
                clip-path: polygon(16px 0, 100% 0, 100% calc(100% - 16px), calc(100% - 16px) 100%, 0 100%, 0 16px);
            }

            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background:
                    linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.1) 50%),
                    linear-gradient(90deg, rgba(255, 0, 0, 0.02), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.02));
                background-size: 100% 2px, 3px 100%;
                pointer-events: none;
                z-index: 0;
                opacity: 0.3;
            }
        </style>
    </head>
    <body class="min-h-screen antialiased">
        <main class="relative z-10 min-h-screen flex items-center justify-center px-6 py-10">
            <section class="w-full max-w-md bg-[#1e1e1e] border-l-[6px] border-[#ffd700] p-8 legacy-clip shadow-[0_0_35px_rgba(0,0,0,0.55)]">
                <header class="mb-8">
                    <p class="text-[10px] text-[#ffd700] font-black tracking-[0.35em] uppercase italic mb-2">Legacy XI</p>
                    <h1 class="text-4xl font-black italic uppercase font-heading text-white leading-none tracking-tight">Verificar Email</h1>
                    <p class="text-[10px] text-white/40 font-bold uppercase italic mt-3">Digite o codigo enviado para liberar o acesso</p>
                </header>

                <div class="mb-5 bg-[#121212] border border-white/10 text-white px-4 py-4 legacy-clip text-xs font-bold uppercase italic leading-relaxed">
                    Enviamos um codigo de verificacao para:
                    <span class="text-[#ffd700]">{{ $email }}</span>
                </div>

                @if ($status)
                    <div class="mb-5 bg-[#166534]/25 border border-[#22c55e] text-white px-4 py-3 legacy-clip text-xs font-bold uppercase italic">
                        {{ $status }}
                    </div>
                @endif

                @if ($errors->has('code'))
                    <div class="mb-5 bg-[#b22222]/25 border border-[#b22222] text-white px-4 py-3 legacy-clip text-xs font-bold uppercase italic">
                        {{ $errors->first('code') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('verification.code.verify') }}" class="space-y-4 mb-3">
                    @csrf

                    <div>
                        <label for="code" class="block text-[10px] font-black text-[#ffd700] uppercase italic tracking-[0.22em] mb-2">Codigo de verificacao</label>
                        <input
                            id="code"
                            name="code"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            required
                            autofocus
                            value="{{ old('code') }}"
                            class="w-full bg-[#121212] text-white px-4 py-3 border border-white/10 focus:border-[#ffd700] focus:outline-none legacy-clip tracking-[0.5em] text-center text-lg font-black"
                            placeholder="000000"
                        >
                    </div>

                    <button type="submit" class="w-full bg-[#ffd700] text-[#121212] px-6 py-4 font-black uppercase italic text-xs tracking-[0.15em] legacy-clip hover:brightness-95 active:translate-y-[1px] transition">
                        Confirmar Codigo
                    </button>
                </form>

                <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
                    @csrf

                    <button type="submit" class="w-full bg-[#ffd700] text-[#121212] px-6 py-4 font-black uppercase italic text-xs tracking-[0.15em] legacy-clip hover:brightness-95 active:translate-y-[1px] transition">
                        Reenviar Codigo
                    </button>
                </form>

                <form method="POST" action="{{ route('legacy.logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full bg-transparent border border-white/20 text-white/80 px-6 py-3 font-black uppercase italic text-[11px] tracking-[0.15em] legacy-clip hover:text-white hover:border-white/40 transition">
                        Sair da Conta
                    </button>
                </form>
            </section>
        </main>
    </body>
</html>
