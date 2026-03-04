<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Legacy XI | Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@1,700;1,900&family=Russo+One&display=swap" rel="stylesheet">
        @include('components.app_assets')
        <script>
            document.documentElement.classList.add('legacy-login-splash-enabled');
        </script>
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

            .legacy-login-splash {
                display: none;
            }

            .legacy-login-splash-enabled .legacy-login-splash {
                display: flex;
            }

            .legacy-login-splash.is-hiding {
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 200ms ease, visibility 0s linear 200ms;
            }

            .legacy-login-splash-logo {
                animation: legacyLoginSplashPulse 1.1s ease-out forwards;
            }

            .legacy-login-splash-bar {
                transform: translateX(-100%);
                animation: legacyLoginSplashLoad 1.1s linear forwards;
            }

            @keyframes legacyLoginSplashPulse {
                0% { transform: scale(0.96); opacity: 0.75; }
                60% { transform: scale(1.02); opacity: 1; }
                100% { transform: scale(1); opacity: 1; }
            }

            @keyframes legacyLoginSplashLoad {
                from { transform: translateX(-100%); }
                to { transform: translateX(0%); }
            }
        </style>
    </head>
    <body class="min-h-screen antialiased">
        <div id="legacy-login-splash" class="legacy-login-splash fixed inset-0 z-[9999] items-center justify-center bg-[#121212]">
            <div class="relative flex flex-col items-center gap-5">
                <div class="absolute -inset-10 opacity-20 pointer-events-none"
                     style="background: repeating-linear-gradient(135deg, #1E1E1E 0 12px, #121212 12px 24px);">
                </div>

                <img
                    id="legacy-login-splash-logo"
                    src=""
                    alt="Legacy XI"
                    class="legacy-login-splash-logo h-16 w-auto relative opacity-0"
                    style="filter: drop-shadow(0 0 14px rgba(255,215,0,.18));"
                >

                <div class="w-44 h-1 bg-[#1E1E1E] relative overflow-hidden">
                    <div class="legacy-login-splash-bar h-full bg-[#FFD700]"></div>
                </div>

                <button
                    type="button"
                    id="legacy-login-splash-skip"
                    class="text-white/70 text-xs uppercase tracking-widest hover:text-white relative"
                >
                    Pular
                </button>
            </div>
        </div>

        <main id="legacy-login-main" class="relative z-10 min-h-screen flex items-center justify-center px-6 py-10">
            <section class="w-full max-w-md bg-[#1e1e1e] border-l-[6px] border-[#ffd700] p-8 legacy-clip shadow-[0_0_35px_rgba(0,0,0,0.55)]">
                <header class="mb-8">
                    <p class="text-[10px] text-[#ffd700] font-black tracking-[0.35em] uppercase italic mb-2">Legacy XI</p>
                    <h1 class="text-4xl font-black italic uppercase font-heading text-white leading-none tracking-tight">Login</h1>
                    <p class="text-[10px] text-white/40 font-bold uppercase italic mt-3">Acesse para entrar no hub legado</p>
                </header>

                @if (session('status'))
                    <div class="mb-5 bg-[#166534]/25 border border-[#22c55e] text-white px-4 py-3 legacy-clip text-xs font-bold uppercase italic">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 bg-[#b22222]/25 border border-[#b22222] text-white px-4 py-3 legacy-clip text-xs font-bold uppercase italic">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('legacy.login.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-[10px] font-black text-[#ffd700] uppercase italic tracking-[0.22em] mb-2">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            class="w-full bg-[#121212] text-white px-4 py-3 border border-white/10 focus:border-[#ffd700] focus:outline-none legacy-clip"
                            placeholder="voce@exemplo.com"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-[10px] font-black text-[#ffd700] uppercase italic tracking-[0.22em] mb-2">Senha</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="w-full bg-[#121212] text-white px-4 py-3 border border-white/10 focus:border-[#ffd700] focus:outline-none legacy-clip"
                            placeholder="********"
                        >
                    </div>

                    <div class="flex items-center justify-between gap-4 text-[10px] font-black uppercase italic tracking-[0.14em]">
                        <a href="{{ route('register') }}" class="text-white/60 hover:text-white transition-colors">
                            Criar cadastro
                        </a>
                        <a href="{{ route('password.request') }}" class="text-[#ffd700] hover:text-white transition-colors">
                            Recuperacao de senha
                        </a>
                    </div>

                    <button type="submit" class="w-full mt-3 bg-[#ffd700] text-[#121212] px-6 py-4 font-black uppercase italic text-xs tracking-[0.15em] legacy-clip hover:brightness-95 active:translate-y-[1px] transition">
                        Entrar no Legacy
                    </button>
                </form>
            </section>
        </main>
        <script>
            (() => {
                const splash = document.getElementById('legacy-login-splash');
                if (!splash) return;

                const logo = document.getElementById('legacy-login-splash-logo');
                const skipButton = document.getElementById('legacy-login-splash-skip');
                const appAssets = window.__APP_ASSETS__ || {};
                const logoUrl = appAssets.logo_dark_url || appAssets.logo_padrao_url || '';
                const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const displayMs = prefersReducedMotion ? 450 : 1100;
                let hidden = false;

                if (logo && logoUrl) {
                    logo.setAttribute('src', logoUrl);
                    logo.classList.remove('opacity-0');
                } else if (logo) {
                    logo.classList.remove('opacity-0');
                }

                const hideSplash = () => {
                    if (hidden) return;
                    hidden = true;
                    splash.classList.add('is-hiding');
                    window.setTimeout(() => {
                        splash.remove();
                    }, 260);
                };

                if (skipButton) {
                    skipButton.addEventListener('click', hideSplash);
                }

                window.setTimeout(hideSplash, displayMs);
                window.setTimeout(hideSplash, 3500);
            })();
        </script>
    </body>
</html>
