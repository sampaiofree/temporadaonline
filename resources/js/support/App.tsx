import { ChevronRight, CircleHelp, Clock3, Mail, ShieldCheck } from "lucide-react";

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

const supportEmail = "suporte@legaxi.online";

export default function App() {
  return (
    <div className="min-h-screen bg-legacy-bg text-white overflow-x-hidden relative">
      <nav className="border-b border-legacy-card px-6 py-4 flex justify-between items-center max-w-7xl mx-auto relative z-20">
        <a href="/" className="font-display font-black text-2xl tracking-tighter italic">
          LEGACY<span className="text-legacy-accent">XI</span>
        </a>
        <div className="flex items-center gap-3">
          <a
            href="/"
            className="border border-legacy-accent/40 text-legacy-accent font-display font-black text-xs sm:text-sm px-4 py-2 uppercase italic tracking-tighter"
          >
            Home
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

      <main className="relative max-w-7xl mx-auto px-6 pt-12 pb-20 md:pt-20 md:pb-24">
        <TextureOverlay />
        <section className="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
          <div className="space-y-6">
            <h1 className="font-display font-black text-4xl sm:text-5xl md:text-6xl leading-[0.95] text-legacy-accent uppercase italic tracking-tighter">
              SUPORTE OFICIAL
              <br />
              LEGACY XI
            </h1>
            <div className="h-2 w-24 bg-legacy-accent" />
            <p className="text-base md:text-lg text-gray-300 leading-relaxed max-w-2xl">
              Estamos prontos para ajudar com acesso, conta, ligas, partidas e qualquer duvida sobre a plataforma.
              Nosso canal oficial de suporte e o e-mail abaixo.
            </p>
            <a
              href={`mailto:${supportEmail}`}
              className="group inline-flex items-center gap-3 bg-legacy-accent text-legacy-bg font-display font-black text-base sm:text-lg px-6 sm:px-8 py-4 uppercase tracking-tighter shadow-[6px_6px_0px_0px_rgba(0,0,0,1)]"
            >
              {supportEmail}
              <ChevronRight className="group-hover:translate-x-1 transition-transform" />
            </a>
          </div>

          <div className="bg-legacy-card border border-white/10 p-6 sm:p-8 space-y-5">
            <h2 className="font-display font-black text-2xl text-white uppercase italic tracking-tighter">
              COMO PODEMOS AJUDAR
            </h2>
            <div className="space-y-4">
              <div className="flex gap-4 items-start bg-legacy-bg p-4 border-l-4 border-legacy-accent">
                <CircleHelp className="text-legacy-accent shrink-0 mt-0.5" />
                <div>
                  <p className="font-black uppercase text-sm tracking-wider">Duvidas gerais</p>
                  <p className="text-sm text-gray-400">Cadastro, login, funcionamento de ligas e configuracoes.</p>
                </div>
              </div>
              <div className="flex gap-4 items-start bg-legacy-bg p-4 border-l-4 border-legacy-accent">
                <ShieldCheck className="text-legacy-accent shrink-0 mt-0.5" />
                <div>
                  <p className="font-black uppercase text-sm tracking-wider">Problemas tecnicos</p>
                  <p className="text-sm text-gray-400">Falhas na plataforma, erros em partidas ou comportamento inesperado.</p>
                </div>
              </div>
              <div className="flex gap-4 items-start bg-legacy-bg p-4 border-l-4 border-legacy-accent">
                <Clock3 className="text-legacy-accent shrink-0 mt-0.5" />
                <div>
                  <p className="font-black uppercase text-sm tracking-wider">Atendimento</p>
                  <p className="text-sm text-gray-400">Envie o maximo de detalhes no e-mail para acelerar o retorno.</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="relative z-10 mt-12">
          <div className="bg-legacy-card border border-white/10 p-6 md:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-5">
            <div className="flex items-start gap-4">
              <Mail className="text-legacy-accent mt-1 shrink-0" />
              <div>
                <h3 className="font-display font-black text-xl uppercase italic tracking-tighter">Canal de Suporte</h3>
                <p className="text-gray-400 text-sm md:text-base">
                  Entre em contato com nossa equipe pelo e-mail oficial:
                </p>
                <a href={`mailto:${supportEmail}`} className="text-legacy-accent font-black tracking-wide">
                  {supportEmail}
                </a>
              </div>
            </div>
            <a
              href={`mailto:${supportEmail}`}
              className="w-full md:w-auto text-center bg-legacy-accent text-legacy-bg font-display font-black px-6 py-4 uppercase tracking-tighter"
            >
              Solicitar Suporte
            </a>
          </div>
        </section>
      </main>
    </div>
  );
}
