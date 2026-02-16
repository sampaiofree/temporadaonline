<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ElencoController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ElencoPadraoController as AdminElencoPadraoController;
use App\Http\Controllers\Admin\GeracaoController as AdminGeracaoController;
use App\Http\Controllers\Admin\JogoController as AdminJogoController;
use App\Http\Controllers\Admin\LigaController as AdminLigaController;
use App\Http\Controllers\Admin\ConfederacaoController as AdminConfederacaoController;
use App\Http\Controllers\Admin\PlataformaController as AdminPlataformaController;
use App\Http\Controllers\Admin\PaisController as AdminPaisController;
use App\Http\Controllers\Admin\ClubeController as AdminClubeController;
use App\Http\Controllers\Admin\LigaEscudoController as AdminLigaEscudoController;
use App\Http\Controllers\Admin\LigaJogadorController as AdminLigaJogadorController;
use App\Http\Controllers\Admin\ClubeTamanhoController as AdminClubeTamanhoController;
use App\Http\Controllers\Admin\EscudoClubeController as AdminEscudoClubeController;
use App\Http\Controllers\Admin\PlaystyleController as AdminPlaystyleController;
use App\Http\Controllers\Admin\PartidaDenunciaController as AdminPartidaDenunciaController;
use App\Http\Controllers\Admin\AppAssetController as AdminAppAssetController;
use App\Http\Controllers\Admin\IdiomaRegiaoController as AdminIdiomaRegiaoController;
use App\Http\Controllers\Admin\ConquistaController as AdminConquistaController;
use App\Http\Controllers\Admin\ConquistaImagemController as AdminConquistaImagemController;
use App\Http\Controllers\Admin\PatrocinioController as AdminPatrocinioController;
use App\Http\Controllers\Admin\PatrocinioImagemController as AdminPatrocinioImagemController;
use App\Http\Controllers\Admin\PremiacaoController as AdminPremiacaoController;
use App\Http\Controllers\Admin\PremiacaoImagemController as AdminPremiacaoImagemController;
use App\Http\Controllers\Admin\UserDisponibilidadeController as AdminUserDisponibilidadeController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WhatsappConnectionController as AdminWhatsappConnectionController;
use App\Http\Controllers\Admin\TemporadaController as AdminTemporadaController;
use App\Http\Controllers\LigaClassificacaoController;
use App\Http\Controllers\LigaController;
use App\Http\Controllers\LigaElencoController;
use App\Http\Controllers\LigaMercadoController;
use App\Http\Controllers\LigaPartidasController;
use App\Http\Controllers\LigaClubePerfilController;
use App\Http\Controllers\MinhaLigaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\UserDisponibilidadeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/legacy');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('confederacoes', AdminConfederacaoController::class, [
        'parameters' => ['confederacoes' => 'confederacao'],
    ])->except(['show']);
    Route::patch('ligas/{liga}/finalizar', [AdminLigaController::class, 'finalize'])->name('ligas.finalize');
    Route::resource('ligas', AdminLigaController::class)->except(['show']);
    Route::resource('geracoes', AdminGeracaoController::class)->except(['show']);
    Route::resource('jogos', AdminJogoController::class)->except(['show']);
    Route::resource('plataformas', AdminPlataformaController::class)->except(['show']);
    Route::resource('paises', AdminPaisController::class, ['parameters' => ['paises' => 'pais']])
        ->except(['show', 'create']);
    Route::delete('paises/bulk-destroy', [AdminPaisController::class, 'bulkDestroy'])->name('paises.bulk-destroy');
    Route::resource('playstyles', AdminPlaystyleController::class)->only(['index', 'store', 'destroy']);
    Route::delete('playstyles/bulk-destroy', [AdminPlaystyleController::class, 'bulkDestroy'])->name('playstyles.bulk-destroy');
    Route::post('conquistas/upload-massa', [AdminConquistaController::class, 'bulkStore'])->name('conquistas.bulk-store');
    Route::resource('conquistas', AdminConquistaController::class)->except(['show']);
    Route::resource('conquistas-imagens', AdminConquistaImagemController::class, [
        'parameters' => ['conquistas-imagens' => 'conquista_imagem'],
    ])->except(['show', 'create']);
    Route::post('patrocinios/upload-massa', [AdminPatrocinioController::class, 'bulkStore'])->name('patrocinios.bulk-store');
    Route::resource('patrocinios', AdminPatrocinioController::class)->except(['show']);
    Route::resource('patrocinios-imagens', AdminPatrocinioImagemController::class, [
        'parameters' => ['patrocinios-imagens' => 'patrocinio_imagem'],
    ])->except(['show', 'create']);
    Route::post('premiacoes/upload-massa', [AdminPremiacaoController::class, 'bulkStore'])->name('premiacoes.bulk-store');
    Route::resource('premiacoes', AdminPremiacaoController::class, [
        'parameters' => ['premiacoes' => 'premiacao'],
    ])->except(['show']);
    Route::resource('premiacoes-imagens', AdminPremiacaoImagemController::class, [
        'parameters' => ['premiacoes-imagens' => 'premiacao_imagem'],
    ])->except(['show', 'create']);
    Route::get('idioma-regiao', [AdminIdiomaRegiaoController::class, 'index'])->name('idioma-regiao.index');
    Route::post('idioma-regiao/idiomas', [AdminIdiomaRegiaoController::class, 'storeIdioma'])->name('idioma-regiao.idiomas.store');
    Route::patch('idioma-regiao/idiomas/{idioma}', [AdminIdiomaRegiaoController::class, 'updateIdioma'])->name('idioma-regiao.idiomas.update');
    Route::delete('idioma-regiao/idiomas/{idioma}', [AdminIdiomaRegiaoController::class, 'destroyIdioma'])->name('idioma-regiao.idiomas.destroy');
    Route::post('idioma-regiao/regioes', [AdminIdiomaRegiaoController::class, 'storeRegiao'])->name('idioma-regiao.regioes.store');
    Route::patch('idioma-regiao/regioes/{regiao}', [AdminIdiomaRegiaoController::class, 'updateRegiao'])->name('idioma-regiao.regioes.update');
    Route::delete('idioma-regiao/regioes/{regiao}', [AdminIdiomaRegiaoController::class, 'destroyRegiao'])->name('idioma-regiao.regioes.destroy');
    Route::resource('clubes', AdminClubeController::class)
        ->only(['index', 'edit', 'update', 'destroy']);
    Route::resource('ligas-usuarios', AdminLigaJogadorController::class, [
        'parameters' => ['ligas-usuarios' => 'liga_jogador'],
    ])->only(['index', 'destroy']);
    Route::get('temporada', [AdminTemporadaController::class, 'index'])->name('temporadas.index');
    Route::post('temporada', [AdminTemporadaController::class, 'store'])->name('temporadas.store');
    Route::patch('temporada/{temporada}', [AdminTemporadaController::class, 'update'])->name('temporadas.update');
    Route::get('app-assets', [AdminAppAssetController::class, 'edit'])->name('app-assets.edit');
    Route::put('app-assets', [AdminAppAssetController::class, 'update'])->name('app-assets.update');
    Route::get('whatsapp', [AdminWhatsappConnectionController::class, 'index'])->name('whatsapp.index');
    Route::post('whatsapp/instance', [AdminWhatsappConnectionController::class, 'createInstance'])->name('whatsapp.instance.create');
    Route::post('whatsapp/{connection}/connect', [AdminWhatsappConnectionController::class, 'connect'])->name('whatsapp.connect');
    Route::post('whatsapp/{connection}/status', [AdminWhatsappConnectionController::class, 'refreshStatus'])->name('whatsapp.status');
    Route::post('whatsapp/{connection}/restart', [AdminWhatsappConnectionController::class, 'restart'])->name('whatsapp.restart');
    Route::post('whatsapp/{connection}/logout', [AdminWhatsappConnectionController::class, 'logout'])->name('whatsapp.logout');
    Route::get('partidas-denuncias', [AdminPartidaDenunciaController::class, 'index'])
        ->name('partidas-denuncias.index');
    Route::delete('ligas-escudos/bulk-destroy', [AdminLigaEscudoController::class, 'bulkDestroy'])->name('ligas-escudos.bulk-destroy');
    Route::resource('ligas-escudos', AdminLigaEscudoController::class, [
        'parameters' => ['ligas-escudos' => 'liga_escudo'],
    ])->except(['show', 'create']);
    Route::delete('escudos-clubes/bulk-destroy', [AdminEscudoClubeController::class, 'bulkDestroy'])->name('escudos-clubes.bulk-destroy');
    Route::resource('escudos-clubes', AdminEscudoClubeController::class, [
        'parameters' => ['escudos-clubes' => 'escudo_clube'],
    ])->except(['show', 'create']);
    Route::resource('users', AdminUserController::class)->except(['show', 'destroy']);
    Route::resource('clube-tamanho', AdminClubeTamanhoController::class, [
        'parameters' => ['clube-tamanho' => 'clube_tamanho'],
    ])->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('users/{user}/horarios', [AdminUserDisponibilidadeController::class, 'index'])->name('users.horarios.index');
    Route::post('users/{user}/horarios', [AdminUserDisponibilidadeController::class, 'store'])->name('users.horarios.store');
    Route::put('users/{user}/horarios/{disponibilidade}', [AdminUserDisponibilidadeController::class, 'update'])->name('users.horarios.update');
    Route::delete('users/{user}/horarios/{disponibilidade}', [AdminUserDisponibilidadeController::class, 'destroy'])->name('users.horarios.destroy');
    Route::get('/elenco-padrao', [AdminElencoPadraoController::class, 'index'])->name('elenco-padrao.index');
    Route::post('/elenco-padrao/importar', [AdminElencoPadraoController::class, 'importar'])->name('elenco-padrao.importar');
    Route::get('/elenco-padrao/jogadores', [AdminElencoPadraoController::class, 'jogadores'])->name('elenco-padrao.jogadores');
    Route::get('/elenco-padrao/jogadores/{player}/editar', [AdminElencoPadraoController::class, 'edit'])->name('elenco-padrao.jogadores.edit');
    Route::put('/elenco-padrao/jogadores/{player}', [AdminElencoPadraoController::class, 'update'])->name('elenco-padrao.jogadores.update');
    Route::delete('/elenco-padrao/jogadores/{player}', [AdminElencoPadraoController::class, 'destroyJogador'])
        ->name('elenco-padrao.jogadores.destroy-player');
    Route::delete('/elenco-padrao/jogos/{jogo}/jogadores', [AdminElencoPadraoController::class, 'destroyJogadores'])
        ->name('elenco-padrao.jogadores.destroy');
});

Route::middleware(['auth', 'roster.limit', 'legacy.first_access'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/ligas', [LigaController::class, 'index'])->name('ligas');
    Route::post('/ligas/{liga}/entrar', [LigaController::class, 'join'])->name('ligas.join');
    Route::get('/perfil', [ProfileController::class, 'show'])->name('perfil');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('perfil.update');
    Route::delete('/perfil', [ProfileController::class, 'destroy'])->name('perfil.destroy');
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);
    Route::get('/minha_liga', [MinhaLigaController::class, 'show'])->name('minha_liga');
    Route::get('/minha_liga/clube', [MinhaLigaController::class, 'clube'])->name('minha_liga.clube');
    Route::get('/minha_liga/onboarding-clube', [MinhaLigaController::class, 'onboardingClube'])
        ->name('minha_liga.onboarding_clube');
    Route::get('/minha_liga/clube/conquistas', [MinhaLigaController::class, 'conquistas'])->name('minha_liga.conquistas');
    Route::post('/minha_liga/clube/conquistas/{conquista}/claim', [MinhaLigaController::class, 'claimConquista'])
        ->name('minha_liga.conquistas.claim');
    Route::get('/minha_liga/clube/patrocinio', [MinhaLigaController::class, 'patrocinios'])->name('minha_liga.patrocinio');
    Route::post('/minha_liga/clube/patrocinio/{patrocinio}/claim', [MinhaLigaController::class, 'claimPatrocinio'])
        ->name('minha_liga.patrocinio.claim');
    Route::get('/minha_liga/clube/conquistas', [MinhaLigaController::class, 'conquistas'])->name('minha_liga.conquistas');
    Route::post('/minha_liga/clube/conquistas/{conquista}/claim', [MinhaLigaController::class, 'claimConquista'])
        ->name('minha_liga.conquistas.claim');
    Route::get('/minha_liga/meu-elenco', [MinhaLigaController::class, 'meuElenco'])->name('minha_liga.meu_elenco');
    Route::get('/minha_liga/esquema-tatico', [MinhaLigaController::class, 'esquemaTatico'])
        ->name('minha_liga.esquema_tatico');
    Route::post('/minha_liga/esquema-tatico', [MinhaLigaController::class, 'salvarEsquemaTatico'])
        ->name('minha_liga.esquema_tatico.salvar');
    Route::get('/minha_liga/financeiro', [MinhaLigaController::class, 'financeiro'])->name('minha_liga.financeiro');
    Route::get('/liga/mercado', [LigaMercadoController::class, 'index'])->name('liga.mercado');
    Route::get('/liga/mercado/propostas', [LigaMercadoController::class, 'propostas'])->name('liga.mercado.propostas');
    Route::get('/liga/partidas', [LigaPartidasController::class, 'index'])->name('liga.partidas');
    Route::get('/liga/partidas/{partida}/finalizar', [LigaPartidasController::class, 'finalizar'])
        ->name('liga.partidas.finalizar');
    Route::get('/liga/classificacao', [LigaClassificacaoController::class, 'index'])->name('liga.classificacao');
    Route::get('/liga/elenco', [LigaElencoController::class, 'index'])->name('liga.elenco');
    Route::get('/liga/clubes/{clube}', [LigaClubePerfilController::class, 'show'])->name('liga.clube.perfil');
    Route::post('/minha_liga/clube/elenco', [MinhaLigaController::class, 'addPlayerToClub'])->name('minha_liga.clube.elenco');
    // Rota legada: redireciona antiga lista de elenco para o mercado da liga
    Route::get('/minha_liga/elenco', function (Illuminate\Http\Request $request) {
        $ligaId = $request->query('liga_id');
        $target = $ligaId ? route('liga.mercado', ['liga_id' => $ligaId]) : route('liga.mercado');
        return redirect()->to($target);
    })->name('minha_liga.elenco.legacy');
    Route::patch('/elenco/{elenco}/valor', [ElencoController::class, 'updateValor'])->name('elenco.updateValor');
    Route::post('/elenco/{elenco}/vender-mercado', [ElencoController::class, 'venderMercado'])->name('elenco.venderMercado');
    Route::post('/elenco/{elenco}/listar-mercado', [ElencoController::class, 'listarMercado'])->name('elenco.listarMercado');
    Route::post('/minha_liga/clubes', [MinhaLigaController::class, 'storeClube'])->name('minha_liga.clubes');

    // Disponibilidades (reuso do controller da API, para tela de perfil)
    Route::get('/me/disponibilidades', [UserDisponibilidadeController::class, 'index'])->name('me.disponibilidades.index');
});

require __DIR__.'/auth.php';
require __DIR__.'/legacy.php';
