import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/js/app.jsx',
                'resources/js/ligas.jsx',
                'resources/js/perfil.jsx',
                'resources/js/minha_liga.jsx',
                'resources/js/minha_liga_financeiro.jsx',
                'resources/js/minha_liga_meu_elenco.jsx',
                'resources/js/liga_dashboard.jsx',
                'resources/js/liga_mercado.jsx',
                'resources/js/liga_partidas.jsx',
                'resources/js/liga_classificacao.jsx',
                'resources/js/liga_clube_perfil.jsx',
                'resources/js/register.jsx',
                'resources/js/login.jsx',
            ],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
