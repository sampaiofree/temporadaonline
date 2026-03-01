import { ArrowUpRight, Database, LockKeyhole, Mail, ShieldCheck, Users } from "lucide-react";

const supportEmail = "suporte@legaxi.online";

const TextureOverlay = () => (
  <div className="absolute inset-0 overflow-hidden pointer-events-none z-0">
    <div
      className="absolute inset-0 opacity-[0.03]"
      style={{
        backgroundImage:
          "linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px)",
        backgroundSize: "40px 40px",
      }}
    />
    <div
      className="absolute inset-0 opacity-[0.02]"
      style={{
        backgroundImage: "repeating-linear-gradient(45deg, #fff, #fff 1px, transparent 1px, transparent 15px)",
      }}
    />
  </div>
);

export default function App() {
  return (
    <div className="min-h-screen bg-legacy-bg text-white overflow-x-hidden relative">
      <nav className="border-b border-legacy-card px-6 py-4 flex justify-between items-center max-w-7xl mx-auto relative z-20">
        <a href="/" className="font-display font-black text-2xl tracking-tighter italic">
          LEGACY<span className="text-legacy-accent">XI</span>
        </a>
        <div className="flex items-center gap-3">
          <a
            href="/suporte"
            className="border border-legacy-accent/40 text-legacy-accent font-display font-black text-xs sm:text-sm px-4 py-2 uppercase italic tracking-tighter"
          >
            Suporte
          </a>
          <a
            href="/register"
            target="_blank"
            rel="noopener noreferrer"
            className="bg-legacy-accent text-legacy-bg font-display font-black text-xs sm:text-sm px-4 sm:px-6 py-2 sm:py-3 uppercase italic tracking-tighter shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]"
          >
            Criar Conta
          </a>
        </div>
      </nav>

      <main className="relative max-w-7xl mx-auto px-6 pt-12 pb-16 md:pt-20 md:pb-20">
        <TextureOverlay />

        <section className="relative z-10 space-y-7">
          <div className="space-y-4">
            <div className="inline-flex items-center gap-2 bg-legacy-card border border-white/10 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.2em] text-legacy-accent">
              <ShieldCheck size={16} />
              Documento Legal
            </div>
            <h1 className="font-display font-black text-4xl sm:text-5xl md:text-6xl leading-[0.95] text-legacy-accent uppercase italic tracking-tighter">
              Politica de Privacidade
            </h1>
            <div className="h-2 w-24 bg-legacy-accent" />
            <p className="text-base md:text-lg text-gray-300 leading-relaxed max-w-4xl">
              Esta Politica de Privacidade descreve como o Legacy XI coleta, usa, armazena e protege dados pessoais de
              usuarios, visitantes e membros da comunidade. Ao utilizar nossos servicos, voce concorda com as praticas
              descritas neste documento.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-legacy-card border border-white/10 p-5 space-y-3">
              <Database className="text-legacy-accent" />
              <p className="font-display font-black uppercase italic tracking-tighter text-xl">Coleta Responsavel</p>
              <p className="text-sm text-gray-400 leading-relaxed">Somente dados necessarios para operar a plataforma.</p>
            </div>
            <div className="bg-legacy-card border border-white/10 p-5 space-y-3">
              <LockKeyhole className="text-legacy-accent" />
              <p className="font-display font-black uppercase italic tracking-tighter text-xl">Seguranca</p>
              <p className="text-sm text-gray-400 leading-relaxed">Controles tecnicos e organizacionais para protecao.</p>
            </div>
            <div className="bg-legacy-card border border-white/10 p-5 space-y-3">
              <Users className="text-legacy-accent" />
              <p className="font-display font-black uppercase italic tracking-tighter text-xl">Direitos LGPD</p>
              <p className="text-sm text-gray-400 leading-relaxed">Atendimento de solicitacoes dos titulares de dados.</p>
            </div>
          </div>
        </section>

        <section className="relative z-10 mt-10 bg-legacy-card border border-white/10 p-6 md:p-8 space-y-8">
          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">1. Informacoes Gerais</h2>
            <p className="text-gray-300 leading-relaxed">
              O Legacy XI e uma plataforma digital voltada a organizacao de ligas, partidas, comunidades e recursos de
              gestao para jogos de futebol virtual. Esta politica se aplica ao uso do site, das paginas publicas, das
              areas autenticadas e de servicos relacionados fornecidos por nos.
            </p>
          </article>

          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">2. Dados Coletados</h2>
            <p className="text-gray-300 leading-relaxed">
              Podemos coletar dados fornecidos diretamente por voce, como nome, apelido, e-mail, identificadores de
              conta, informacoes de perfil, dados de ligas, dados de disponibilidade de horarios e informacoes
              necessarias para autenticacao e suporte.
            </p>
            <p className="text-gray-300 leading-relaxed">
              Tambem podemos coletar dados tecnicos de navegacao, como endereco IP, dispositivo, navegador, logs de
              acesso, data e hora de uso, paginas acessadas e eventos operacionais da plataforma para seguranca,
              estabilidade e melhoria continua do servico.
            </p>
          </article>

          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">3. Finalidades do Tratamento</h2>
            <p className="text-gray-300 leading-relaxed">
              Os dados pessoais sao tratados para: criar e manter contas de usuario; permitir participacao em ligas e
              clubes; viabilizar funcionalidades de calendario, mercado e classificacao; prevenir fraudes e acessos
              indevidos; responder solicitacoes de suporte; cumprir obrigacoes legais e regulatarias; e aprimorar a
              experiencia do usuario na plataforma.
            </p>
          </article>

          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">4. Bases Legais (LGPD)</h2>
            <p className="text-gray-300 leading-relaxed">
              O tratamento de dados pessoais ocorre com fundamento nas bases legais previstas na Lei Geral de Protecao
              de Dados (Lei no 13.709/2018), incluindo execucao de contrato, cumprimento de obrigacao legal ou
              regulatoria, exercicio regular de direitos, legitimo interesse e, quando aplicavel, consentimento.
            </p>
          </article>

          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">5. Compartilhamento de Dados</h2>
            <p className="text-gray-300 leading-relaxed">
              Poderemos compartilhar dados com prestadores de servico essenciais para hospedagem, monitoramento,
              autenticacao, comunicacao e operacao tecnica do Legacy XI, sempre observando criterios de necessidade e
              seguranca. Tambem podera haver compartilhamento quando exigido por lei, ordem judicial ou autoridade
              competente.
            </p>
            <p className="text-gray-300 leading-relaxed">
              Nao comercializamos dados pessoais.
            </p>
          </article>

          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">6. Retencao e Descarte</h2>
            <p className="text-gray-300 leading-relaxed">
              Os dados pessoais sao mantidos pelo tempo necessario para cumprir as finalidades descritas nesta politica,
              respeitando prazos legais e regulatorios aplicaveis. Ao fim do periodo de retencao, os dados sao
              eliminados, anonimizados ou bloqueados, conforme o caso.
            </p>
          </article>

          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">7. Seguranca da Informacao</h2>
            <p className="text-gray-300 leading-relaxed">
              Adotamos medidas tecnicas e administrativas para proteger os dados pessoais contra acessos nao autorizados,
              destruicao, perda, alteracao, comunicacao ou difusao indevida. Embora nenhum sistema seja totalmente
              invulneravel, trabalhamos continuamente para elevar nossos padroes de seguranca.
            </p>
          </article>

          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">8. Direitos do Titular</h2>
            <p className="text-gray-300 leading-relaxed">
              Nos termos da LGPD, voce pode solicitar confirmacao da existencia de tratamento, acesso, correcao de
              dados incompletos ou desatualizados, anonimização, bloqueio ou eliminacao de dados desnecessarios,
              portabilidade, informacao sobre compartilhamentos, revogacao de consentimento e oposicao ao tratamento
              quando cabivel.
            </p>
            <p className="text-gray-300 leading-relaxed">
              Para exercer seus direitos, envie solicitacao para nosso canal oficial de suporte.
            </p>
          </article>

          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">9. Cookies e Tecnologias Semelhantes</h2>
            <p className="text-gray-300 leading-relaxed">
              Podemos utilizar cookies e tecnologias semelhantes para autenticacao, manutencao de sessao, seguranca,
              medicao de desempenho e melhoria de navegacao. Voce pode configurar seu navegador para bloquear cookies,
              ciente de que determinadas funcionalidades podem ser impactadas.
            </p>
          </article>

          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">10. Transferencia Internacional</h2>
            <p className="text-gray-300 leading-relaxed">
              Dependendo da infraestrutura utilizada por nossos provedores, os dados poderao ser processados fora do
              Brasil. Nesses casos, adotaremos salvaguardas adequadas, com observancia das exigencias legais aplicaveis.
            </p>
          </article>

          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">11. Privacidade de Menores</h2>
            <p className="text-gray-300 leading-relaxed">
              A plataforma nao e direcionada a menores de 13 anos. Caso identifiquemos tratamento indevido de dados de
              criancas ou adolescentes em desacordo com a legislacao aplicavel, adotaremos as medidas cabiveis para
              regularizacao e/ou exclusao.
            </p>
          </article>

          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">12. Atualizacoes Desta Politica</h2>
            <p className="text-gray-300 leading-relaxed">
              Esta Politica de Privacidade pode ser atualizada periodicamente para refletir melhorias de produto,
              alteracoes legais ou mudancas operacionais. A versao vigente estara sempre disponivel nesta pagina com
              indicacao de data de atualizacao.
            </p>
          </article>

          <article className="space-y-4">
            <h2 className="font-display font-black text-2xl text-legacy-accent uppercase italic tracking-tighter">13. Contato</h2>
            <p className="text-gray-300 leading-relaxed">
              Em caso de duvidas sobre esta politica ou sobre o tratamento de dados pessoais no Legacy XI, entre em
              contato pelo e-mail oficial:
            </p>
            <a
              href={`mailto:${supportEmail}`}
              className="inline-flex items-center gap-2 text-legacy-accent font-black tracking-wide hover:underline"
            >
              <Mail size={16} />
              {supportEmail}
            </a>
          </article>
        </section>

        <section className="relative z-10 mt-10">
          <div className="bg-legacy-card border border-white/10 p-6 md:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-5">
            <div>
              <h3 className="font-display font-black text-xl uppercase italic tracking-tighter">Data de vigencia</h3>
              <p className="text-gray-400 text-sm md:text-base">Ultima atualizacao: 1 de marco de 2026.</p>
            </div>
            <a
              href="/suporte"
              className="w-full md:w-auto text-center inline-flex items-center justify-center gap-2 bg-legacy-accent text-legacy-bg font-display font-black px-6 py-4 uppercase tracking-tighter"
            >
              Falar com o suporte
              <ArrowUpRight size={16} />
            </a>
          </div>
        </section>
      </main>
    </div>
  );
}
