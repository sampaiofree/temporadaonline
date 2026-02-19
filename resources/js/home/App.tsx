/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { motion } from "motion/react";
import { 
  ChevronRight, Shield, Trophy, Users, Instagram, Facebook, Disc, 
  Home, User, Mail, ArrowLeft, Repeat, LayoutGrid, Star 
} from "lucide-react";
import { useState } from "react";

const ElaborateShield = () => (
  <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" className="text-legacy-bg">
    <path d="M24 4L6 12V22C6 33.04 13.68 43.32 24 46C34.32 43.32 42 33.04 42 22V12L24 4Z" fill="currentColor"/>
    <path d="M24 8L38 14V22C38 31.04 31.68 39.32 24 42C16.32 39.32 10 31.04 10 22V14L24 8Z" fill="#121212" fillOpacity="0.2"/>
    <path d="M24 12L24 38" stroke="currentColor" strokeWidth="2" strokeLinecap="square"/>
    <path d="M14 22H34" stroke="currentColor" strokeWidth="2" strokeLinecap="square"/>
    <path d="M24 18L26.5 23H21.5L24 18Z" fill="currentColor"/>
    <rect x="18" y="28" width="12" height="4" fill="currentColor"/>
  </svg>
);

const ElaborateTrophy = () => (
  <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" className="text-legacy-bg">
    <path d="M12 10V14C12 16.21 13.79 18 16 18H18V10H12Z" fill="currentColor"/>
    <path d="M36 10V14C36 16.21 34.21 18 32 18H30V10H36Z" fill="currentColor"/>
    <path d="M32 6H16V22C16 26.42 19.58 30 24 30C28.42 30 32 26.42 32 22V6Z" fill="currentColor"/>
    <path d="M28 34H20V30H28V34Z" fill="currentColor"/>
    <path d="M34 38H14V34H34V38Z" fill="currentColor"/>
    <path d="M24 12L26 16.5H21.5L24 12Z" fill="#121212" fillOpacity="0.3"/>
    <rect x="20" y="20" width="8" height="2" fill="#121212" fillOpacity="0.3"/>
  </svg>
);

const ElaborateCalendar = () => (
  <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" className="text-legacy-bg">
    <rect x="8" y="10" width="32" height="30" fill="currentColor"/>
    <rect x="8" y="10" width="32" height="8" fill="#121212" fillOpacity="0.2"/>
    <path d="M14 6V14" stroke="#121212" strokeWidth="3" strokeLinecap="square"/>
    <path d="M34 6V14" stroke="#121212" strokeWidth="3" strokeLinecap="square"/>
    <rect x="14" y="22" width="4" height="4" fill="#121212" fillOpacity="0.3"/>
    <rect x="22" y="22" width="4" height="4" fill="#121212" fillOpacity="0.3"/>
    <rect x="30" y="22" width="4" height="4" fill="#121212" fillOpacity="0.3"/>
    <rect x="14" y="30" width="4" height="4" fill="#121212" fillOpacity="0.3"/>
    <rect x="22" y="30" width="4" height="4" fill="#121212" fillOpacity="0.3"/>
  </svg>
);

const ElaborateScanner = () => (
  <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" className="text-legacy-bg">
    <path d="M10 14H38V38H10V14Z" fill="currentColor" fillOpacity="0.2"/>
    <path d="M10 14H38V18H10V14Z" fill="currentColor"/>
    <path d="M14 24H34" stroke="currentColor" strokeWidth="2"/>
    <path d="M14 30H28" stroke="currentColor" strokeWidth="2"/>
    <path d="M6 10V6H10" stroke="currentColor" strokeWidth="3"/>
    <path d="M38 6H42V10" stroke="currentColor" strokeWidth="3"/>
    <path d="M42 38V42H38" stroke="currentColor" strokeWidth="3"/>
    <path d="M10 42H6V38" stroke="currentColor" strokeWidth="3"/>
    <motion.rect 
      x="6" y="12" width="36" height="2" fill="currentColor"
      animate={{ y: [0, 24, 0] }}
      transition={{ duration: 2, repeat: Infinity, ease: "linear" }}
    />
  </svg>
);

const ElaborateStadium = () => (
  <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" className="text-legacy-bg">
    {/* Outer Structure - Grid/Mesh */}
    <path d="M4 32C4 22 16.5 14 32 14C47.5 14 60 22 60 32C60 42 47.5 50 32 50C16.5 50 4 42 4 32Z" stroke="currentColor" strokeWidth="1.5" />
    <path d="M4 38C4 48 16.5 56 32 56C47.5 56 60 48 60 38" stroke="currentColor" strokeWidth="1.5" />
    
    {/* Vertical Grid Lines */}
    <line x1="10" y1="35" x2="10" y2="42" stroke="currentColor" strokeWidth="1" />
    <line x1="20" y1="45" x2="20" y2="52" stroke="currentColor" strokeWidth="1" />
    <line x1="32" y1="50" x2="32" y2="56" stroke="currentColor" strokeWidth="1" />
    <line x1="44" y1="45" x2="44" y2="52" stroke="currentColor" strokeWidth="1" />
    <line x1="54" y1="35" x2="54" y2="42" stroke="currentColor" strokeWidth="1" />
    
    {/* Inner Tiers */}
    <path d="M12 32C12 26 21 21 32 21C43 21 52 26 52 32C52 38 43 43 32 43C21 43 12 38 12 32Z" stroke="currentColor" strokeWidth="1" fill="currentColor" fillOpacity="0.1" />
    
    {/* Pitch */}
    <rect x="22" y="28" width="20" height="8" stroke="currentColor" strokeWidth="1" />
    <line x1="32" y1="28" x2="32" y2="36" stroke="currentColor" strokeWidth="1" />
    <circle cx="32" cy="32" r="2" stroke="currentColor" strokeWidth="1" />
    
    {/* Floodlights */}
    <g opacity="0.8">
      <path d="M15 18L15 12M13 12H17" stroke="currentColor" strokeWidth="1.5" />
      <path d="M32 14L32 8M30 8H34" stroke="currentColor" strokeWidth="1.5" />
      <path d="M49 18L49 12M47 12H51" stroke="currentColor" strokeWidth="1.5" />
    </g>
  </svg>
);

const TikTokIcon = () => (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5" />
  </svg>
);

const AGGRESSIVE_CLIP = "polygon(16px 0, 100% 0, 100% calc(100% - 16px), calc(100% - 16px) 100%, 0 100%, 0 16px)";
const SHIELD_CLIP = "polygon(0 0, 100% 0, 100% 85%, 50% 100%, 0 85%)";
const SLANTED_PATTERN = "repeating-linear-gradient(45deg, rgba(246,207,1,0.05) 0, rgba(246,207,1,0.05) 1px, transparent 0, transparent 10px)";

const SmartphoneMockup = () => {
  const [view, setView] = useState('onboarding');
  const accentColor = "#F6CF01";

  const renderContent = () => {
    switch(view) {
      case 'onboarding':
        return (
          <div className="p-6 flex flex-col h-full animate-in fade-in slide-in-from-right-4">
            <header className="mb-8">
              <h2 className="text-2xl font-black italic uppercase font-display text-white leading-none">IDENTIFIQUE-SE</h2>
              <p className="text-[8px] text-legacy-accent font-bold tracking-[0.3em] uppercase italic mt-1">NOME DO TREINADOR</p>
            </header>
            <div className="bg-legacy-card p-4 border-l-4 border-legacy-accent mb-8" style={{ clipPath: AGGRESSIVE_CLIP }}>
              <div className="w-full bg-legacy-bg border-none text-legacy-accent p-3 font-black italic uppercase text-sm outline-none">
                SIR ALEX FERGUSON
              </div>
            </div>
            <div className="flex-1 flex flex-col justify-center items-center space-y-6">
               <div className="w-24 h-24 bg-legacy-card border-2 border-legacy-accent flex items-center justify-center relative" style={{ clipPath: SHIELD_CLIP }}>
                  <Shield className="w-12 h-12 text-legacy-accent" />
               </div>
               <p className="text-[10px] text-gray-500 uppercase font-bold text-center px-4">Escolha seu universo e funde seu legado hoje mesmo.</p>
            </div>
            <button 
              onClick={() => setView('hq')}
              className="w-full bg-legacy-accent text-legacy-bg font-display font-black py-4 text-xs uppercase tracking-tighter mb-4 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:translate-y-1 transition-all"
              style={{ clipPath: "polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px)" }}
            >
              INICIAR MEU LEGADO
            </button>
          </div>
        );
      case 'hq':
        return (
          <div className="flex flex-col h-full animate-in fade-in slide-in-from-right-4">
            <header className="p-6 bg-legacy-card border-b-2 border-legacy-accent">
              <div className="flex justify-between items-start mb-6">
                <button onClick={() => setView('onboarding')} className="text-[8px] font-black text-white/40 uppercase italic flex items-center gap-1">
                  <ArrowLeft size={10} /> UNIVERSOS
                </button>
                <div className="text-right">
                  <h2 className="text-[8px] font-black italic uppercase tracking-[0.2em] text-legacy-accent">UEFA / COMMAND CENTER</h2>
                  <h1 className="text-xl font-black italic uppercase font-display text-white">LEGACY UNITED</h1>
                </div>
              </div>
              <div className="grid grid-cols-3 gap-2">
                 <div className="bg-legacy-bg p-2 text-center border-b-2 border-red-600 relative" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>
                    <Mail size={12} className="text-white/30 mx-auto mb-1" />
                    <p className="text-[7px] font-black italic text-white uppercase tracking-widest">INBOX</p>
                    <div className="absolute top-1 right-1 w-1.5 h-1.5 bg-red-600 rounded-full animate-pulse"></div>
                 </div>
                 <div className="bg-legacy-bg p-2 text-center border-b-2 border-legacy-accent" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>
                    <Repeat size={12} className="text-white/30 mx-auto mb-1" />
                    <p className="text-[7px] font-black italic text-white uppercase tracking-widest">MERCADO</p>
                 </div>
                 <div className="bg-legacy-bg p-2 text-center border-b-2 border-white/10" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>
                    <Users size={12} className="text-white/30 mx-auto mb-1" />
                    <p className="text-[7px] font-black italic text-white uppercase tracking-widest">ELENCO</p>
                 </div>
              </div>
            </header>
            <main className="p-6 space-y-6 overflow-y-auto flex-1">
              <h3 className="text-[9px] font-black text-white/30 uppercase italic tracking-[0.3em] px-1">SETORES DE COMPETIÇÃO</h3>
              <div className="bg-legacy-card p-4 border-b-4 border-legacy-accent" style={{ clipPath: AGGRESSIVE_CLIP }}>
                <div className="flex justify-between items-center">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 bg-legacy-bg flex items-center justify-center font-black italic text-lg text-legacy-accent" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>S</div>
                    <div>
                      <p className="text-sm font-black italic uppercase leading-none text-legacy-accent">ELITE DIVISION</p>
                      <p className="text-[8px] font-bold text-white/30 mt-1 uppercase italic tracking-widest">Apenas os melhores.</p>
                    </div>
                  </div>
                  <div className="bg-legacy-accent text-legacy-bg text-[7px] font-black px-2 py-0.5 italic tracking-widest">INSCRITO</div>
                </div>
              </div>
              <div className="bg-legacy-card p-4 border-b-4 border-blue-600 opacity-50" style={{ clipPath: AGGRESSIVE_CLIP }}>
                <div className="flex justify-between items-center">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 bg-legacy-bg flex items-center justify-center font-black italic text-lg text-blue-600" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>A</div>
                    <div>
                      <p className="text-sm font-black italic uppercase leading-none text-white">PRO LEAGUE</p>
                      <p className="text-[8px] font-bold text-white/30 mt-1 uppercase italic tracking-widest">O degrau para a glória.</p>
                    </div>
                  </div>
                </div>
              </div>
            </main>
          </div>
        );
      default:
        return null;
    }
  };

  return (
    <div className="relative w-[280px] h-[580px] bg-legacy-bg border-4 border-legacy-card shadow-2xl overflow-hidden flex flex-col">
      <div className="absolute inset-0 opacity-[0.03] pointer-events-none" style={{ backgroundImage: SLANTED_PATTERN }}></div>
      
      {/* Status Bar */}
      <div className="h-6 bg-legacy-card flex justify-between items-center px-4 text-[8px] font-mono z-10">
        <span className="font-bold">LEGACY XI</span>
        <span>12:00</span>
      </div>

      <div className="flex-1 overflow-hidden relative z-10">
        {renderContent()}
      </div>

      {/* Navigation Bar */}
      <div className="h-14 bg-legacy-card flex justify-around items-center border-t-2 border-legacy-accent z-20">
        <button onClick={() => setView('hq')} className={`p-2 transition-all flex flex-col items-center gap-0.5 ${view === 'hq' ? 'text-legacy-accent' : 'text-white/20'}`}>
          <Home size={16} />
          <span className="text-[7px] font-black uppercase italic tracking-widest leading-none">PORTAL</span>
        </button>
        <button className="p-2 transition-all flex flex-col items-center gap-0.5 text-white/20">
          <Shield size={16} />
          <span className="text-[7px] font-black uppercase italic tracking-widest leading-none">CLUBE</span>
        </button>
        <button className="p-2 transition-all flex flex-col items-center gap-0.5 text-white/20">
          <LayoutGrid size={16} />
          <span className="text-[7px] font-black uppercase italic tracking-widest leading-none">RANKING</span>
        </button>
        <button onClick={() => setView('onboarding')} className={`p-2 transition-all flex flex-col items-center gap-0.5 ${view === 'onboarding' ? 'text-legacy-accent' : 'text-white/20'}`}>
          <User size={16} />
          <span className="text-[7px] font-black uppercase italic tracking-widest leading-none">CONTA</span>
        </button>
      </div>
    </div>
  );
};

const TextureOverlay = () => (
  <div className="absolute inset-0 overflow-hidden pointer-events-none z-0">
    <div className="absolute inset-0 opacity-[0.03]" 
         style={{ backgroundImage: 'linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px)', backgroundSize: '40px 40px' }}>
    </div>
    <div className="absolute inset-0 opacity-[0.02]"
         style={{ backgroundImage: 'repeating-linear-gradient(45deg, #fff, #fff 1px, transparent 1px, transparent 15px)' }}>
    </div>
  </div>
);

export default function App() {
  return (
    <div className="min-h-screen bg-legacy-bg selection:bg-legacy-accent selection:text-legacy-bg overflow-x-hidden relative">
      {/* Header/Nav */}
      <nav className="border-b border-legacy-card px-6 py-4 flex justify-between items-center max-w-7xl mx-auto relative z-50">
        <div className="font-display font-black text-2xl tracking-tighter italic">
          LEGACY<span className="text-legacy-accent">XI</span>
        </div>
        <a 
          href="https://temporadaonline.3f7.org/register" 
          target="_blank" 
          rel="noopener noreferrer"
          className="bg-legacy-accent text-legacy-bg font-display font-black text-xs sm:text-sm px-4 sm:px-6 py-2 sm:py-3 uppercase italic tracking-tighter hover:scale-105 transition-transform active:scale-95 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]"
        >
          Criar Conta
        </a>
      </nav>

      {/* Hero Section */}
      <main className="relative max-w-7xl mx-auto px-6 pt-12 pb-24 md:pt-24 md:pb-32">
        <TextureOverlay />
        <div className="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
          
          {/* Left Content */}
          <motion.div 
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.6 }}
            className="space-y-8"
          >
            <div className="space-y-4">
              <h1 className="font-display font-black text-4xl sm:text-5xl md:text-7xl lg:text-8xl leading-[0.9] text-legacy-accent uppercase italic tracking-tighter">
                SEJA UM FUNDADOR.<br />
                O PRÉ-ALPHA DO LEGACY XI CHEGOU.
              </h1>
              <div className="h-2 w-24 bg-legacy-accent"></div>
            </div>

            <p className="text-base md:text-xl text-gray-300 max-w-xl leading-relaxed font-medium">
              O Modo Carreira Online definitivo abre as portas. Experimente a gestão 100% nativa via App, domine o mercado de transferências e garanta seu lugar entre os pioneiros. Acesso gratuito por tempo limitado.
            </p>

            <div className="pt-4">
              <a 
                href="https://chat.whatsapp.com/EOng6iq0Wqs54jb5l7sudo" 
                target="_blank" 
                rel="noopener noreferrer"
                className="group relative bg-legacy-accent text-legacy-bg font-display font-black text-base sm:text-xl px-6 sm:px-10 py-5 sm:py-6 uppercase tracking-tighter hover:scale-105 transition-transform active:scale-95 flex items-center gap-3 sm:gap-4 whitespace-nowrap w-fit"
              >
                GARANTIR ACESSO AO ALPHA
                <ChevronRight className="group-hover:translate-x-1 transition-transform w-5 h-5 sm:w-6 sm:h-6" />
              </a>
            </div>

            <div className="flex items-center gap-6 sm:gap-8 pt-8 border-t border-legacy-card">
              <div className="flex flex-col">
                <span className="text-2xl sm:text-3xl font-display font-black italic">100%</span>
                <span className="text-[9px] sm:text-[10px] uppercase font-bold text-gray-500 tracking-widest">Nativo Mobile</span>
              </div>
              <div className="flex flex-col">
                <span className="text-2xl sm:text-3xl font-display font-black italic">EAFC</span>
                <span className="text-[9px] sm:text-[10px] uppercase font-bold text-gray-500 tracking-widest">Meta-Game</span>
              </div>
              <div className="flex flex-col">
                <span className="text-2xl sm:text-3xl font-display font-black italic">ALPHA</span>
                <span className="text-[9px] sm:text-[10px] uppercase font-bold text-gray-500 tracking-widest">Acesso Limitado</span>
              </div>
            </div>
          </motion.div>

          {/* Right Mockup */}
          <motion.div 
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ duration: 0.8, delay: 0.2 }}
            className="relative flex justify-center lg:justify-end"
          >
            {/* Background Decorative Element */}
            <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] bg-legacy-accent/5 rotate-45 -z-10"></div>
            
            <div className="relative">
              <SmartphoneMockup />
              
              {/* Floating Badge */}
              <div className="absolute -top-6 -right-6 bg-legacy-accent text-legacy-bg font-display font-black p-4 text-xs uppercase italic tracking-tighter rotate-12 shadow-xl z-30">
                Legacy XI Mobile
              </div>
            </div>
          </motion.div>

        </div>
      </main>

      {/* Block 2: Features/Ecosystem Section */}
      <section className="relative bg-legacy-card py-24 md:py-32 border-y border-white/5">
        <TextureOverlay />
        <div className="relative z-10 max-w-7xl mx-auto px-6">
          <div className="mb-16 space-y-4">
            <h2 className="font-display font-black text-3xl md:text-5xl text-white uppercase italic tracking-tighter">
              NÃO É APENAS UM TIME.<br />
              É O SEU <span className="text-legacy-accent">LEGADO.</span>
            </h2>
            <div className="h-1 w-20 bg-legacy-accent"></div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {/* Card 1 */}
            <motion.div 
              whileHover={{ y: -5 }}
              className="bg-legacy-bg border border-legacy-accent p-8 space-y-6"
            >
              <div className="w-20 h-20 bg-legacy-accent flex items-center justify-center">
                <ElaborateShield />
              </div>
              <div className="space-y-3">
                <h3 className="font-display font-black text-xl text-legacy-accent uppercase italic">Fundação</h3>
                <p className="text-gray-400 text-sm leading-relaxed">
                  Crie seu clube com nome e escudos autênticos. Defina a identidade que será temida nos gramados virtuais.
                </p>
              </div>
            </motion.div>

            {/* Card 2 */}
            <motion.div 
              whileHover={{ y: -5 }}
              className="bg-legacy-bg border border-legacy-accent p-8 space-y-6"
            >
              <div className="w-20 h-20 bg-legacy-accent flex items-center justify-center">
                <ElaborateTrophy />
              </div>
              <div className="space-y-3">
                <h3 className="font-display font-black text-xl text-legacy-accent uppercase italic">Conquistas</h3>
                <p className="text-gray-400 text-sm leading-relaxed">
                  Ganhe troféus exclusivos que validam sua trajetória como gestor. Cada título é uma marca eterna na sua história.
                </p>
              </div>
            </motion.div>

            {/* Card 3 */}
            <motion.div 
              whileHover={{ y: -5 }}
              className="bg-legacy-bg border border-legacy-accent p-8 space-y-6"
            >
              <div className="w-20 h-20 bg-legacy-accent flex items-center justify-center">
                <ElaborateStadium />
              </div>
              <div className="space-y-3">
                <h3 className="font-display font-black text-xl text-legacy-accent uppercase italic">Torcida</h3>
                <p className="text-gray-400 text-sm leading-relaxed">
                  Veja sua torcida crescer das arquibancadas conforme seu legado aumenta. O engajamento da massa é o seu maior trunfo.
                </p>
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Block 3: Market and Economy Section */}
      <section className="relative bg-legacy-bg py-24 md:py-32 overflow-hidden">
        <TextureOverlay />
        <div className="relative z-10 max-w-7xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            
            {/* Left Content: Text */}
            <motion.div 
              initial={{ opacity: 0, x: -20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.6 }}
              className="space-y-12"
            >
              <div className="space-y-4">
                <h2 className="font-display font-black text-3xl md:text-5xl text-legacy-accent uppercase italic tracking-tighter leading-none">
                  MERCADO INTELIGENTE:<br />
                  GESTÃO ALÉM DAS QUATRO LINHAS.
                </h2>
                <div className="h-1 w-20 bg-legacy-accent"></div>
              </div>

              <div className="space-y-10">
                <div className="space-y-3">
                  <h3 className="font-display font-black text-xl text-white uppercase italic flex items-center gap-3">
                    <div className="w-2 h-6 bg-legacy-accent"></div>
                    Teto Salarial (CAP)
                  </h3>
                  <p className="text-gray-400 text-base leading-relaxed max-w-lg">
                    Gerencie seu elenco dentro do limite técnico para garantir a paridade e o fair play. O equilíbrio financeiro é o que separa os amadores dos grandes gestores.
                  </p>
                </div>

                <div className="space-y-3">
                  <h3 className="font-display font-black text-xl text-white uppercase italic flex items-center gap-3">
                    <div className="w-2 h-6 bg-legacy-accent"></div>
                    Mercado Global
                  </h3>
                  <p className="text-gray-400 text-base leading-relaxed max-w-lg">
                    Contrate jogadores baseados em Tiers e multas rescisórias automáticas. A janela de transferências nunca dorme no Legacy XI.
                  </p>
                </div>
              </div>
            </motion.div>

            {/* Right Content: Visual Mockup (Finance Panel) */}
            <motion.div 
              initial={{ opacity: 0, x: 20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.6 }}
              className="relative"
            >
              <div className="bg-legacy-card border-2 border-white/5 p-8 space-y-8 relative z-10">
                {/* Header of Panel */}
                <div className="flex justify-between items-end border-b border-white/10 pb-4">
                  <div>
                    <span className="text-[10px] uppercase font-bold text-gray-500 tracking-widest">Financeiro</span>
                    <h4 className="font-display font-black text-2xl text-white uppercase italic leading-none">Painel de Gestão</h4>
                  </div>
                  <div className="text-right">
                    <span className="text-[10px] uppercase font-bold text-gray-500 tracking-widest">Saldo Atual</span>
                    <div className="text-legacy-accent font-display font-black text-xl italic">M$ 45.280.000</div>
                  </div>
                </div>

                {/* Salary Cap Bar */}
                <div className="space-y-4">
                  <div className="flex justify-between items-end">
                    <h5 className="text-xs font-bold uppercase text-white tracking-widest">Uso do Teto Salarial (CAP)</h5>
                    <span className="text-xs font-mono text-legacy-accent">120M / 150M</span>
                  </div>
                  <div className="h-4 bg-legacy-bg border border-white/10 p-0.5">
                    <motion.div 
                      initial={{ width: 0 }}
                      whileInView={{ width: '80%' }}
                      viewport={{ once: true }}
                      transition={{ duration: 1, delay: 0.5 }}
                      className="h-full bg-legacy-accent"
                    ></motion.div>
                  </div>
                  <div className="flex justify-between text-[9px] font-bold text-gray-500 uppercase">
                    <span>0M</span>
                    <span>Limite de Fair Play</span>
                    <span>150M</span>
                  </div>
                </div>

                {/* Tier Badges & Transfer List */}
                <div className="space-y-4">
                  <h5 className="text-xs font-bold uppercase text-white tracking-widest">Jogadores por Tier</h5>
                  <div className="grid grid-cols-4 gap-2">
                    <div className="bg-legacy-bg border border-legacy-accent p-3 flex flex-col items-center justify-center gap-1">
                      <span className="text-legacy-accent font-display font-black text-lg italic leading-none">S+</span>
                      <span className="text-[8px] font-bold text-gray-500 uppercase">02</span>
                    </div>
                    <div className="bg-legacy-bg border border-white/10 p-3 flex flex-col items-center justify-center gap-1">
                      <span className="text-white font-display font-black text-lg italic leading-none">A</span>
                      <span className="text-[8px] font-bold text-gray-500 uppercase">05</span>
                    </div>
                    <div className="bg-legacy-bg border border-white/10 p-3 flex flex-col items-center justify-center gap-1">
                      <span className="text-white font-display font-black text-lg italic leading-none">B</span>
                      <span className="text-[8px] font-bold text-gray-500 uppercase">11</span>
                    </div>
                    <div className="bg-legacy-bg border border-white/10 p-3 flex flex-col items-center justify-center gap-1">
                      <span className="text-white font-display font-black text-lg italic leading-none">C</span>
                      <span className="text-[8px] font-bold text-gray-500 uppercase">04</span>
                    </div>
                  </div>
                </div>

                {/* Recent Activity */}
                <div className="space-y-3">
                  <h5 className="text-xs font-bold uppercase text-white tracking-widest">Últimas Movimentações</h5>
                  <div className="space-y-2">
                    <div className="bg-legacy-bg p-3 flex justify-between items-center border-l-2 border-legacy-accent">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 bg-legacy-card flex items-center justify-center text-[10px] font-black italic text-legacy-accent border border-legacy-accent/20">S+</div>
                        <div>
                          <div className="text-[11px] font-bold text-white uppercase">V. Vinícius Jr.</div>
                          <div className="text-[9px] text-gray-500 uppercase">Contratação (Multa)</div>
                        </div>
                      </div>
                      <div className="text-[11px] font-mono text-legacy-accent">- M$ 85M</div>
                    </div>
                    <div className="bg-legacy-bg p-3 flex justify-between items-center border-l-2 border-white/20 opacity-50">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 bg-legacy-card flex items-center justify-center text-[10px] font-black italic text-white border border-white/10">A</div>
                        <div>
                          <div className="text-[11px] font-bold text-white uppercase">R. Rodrygo</div>
                          <div className="text-[9px] text-gray-500 uppercase">Renovação Contratual</div>
                        </div>
                      </div>
                      <div className="text-[11px] font-mono text-white">- M$ 12M</div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Decorative Background Element */}
              <div className="absolute -bottom-12 -right-12 w-64 h-64 bg-legacy-accent/5 -z-0"></div>
            </motion.div>

          </div>
        </div>
      </section>

      {/* Block 4: Match Center and Technology Section */}
      <section className="relative bg-legacy-card py-24 md:py-32 border-t border-white/5">
        <TextureOverlay />
        <div className="relative z-10 max-w-7xl mx-auto px-6">
          <div className="mb-16 space-y-4 text-center md:text-left">
            <h2 className="font-display font-black text-3xl md:text-5xl text-white uppercase italic tracking-tighter">
              JOGUE MAIS, <span className="text-legacy-accent">DIGITE MENOS.</span>
            </h2>
            <div className="h-1 w-20 bg-legacy-accent mx-auto md:mx-0"></div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {/* Card A: Match Center */}
            <motion.div 
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
              className="group relative bg-legacy-bg border-2 border-legacy-accent p-10 md:p-12 space-y-8 overflow-hidden"
            >
              {/* Alpha Badge */}
              <div className="absolute top-0 right-0 bg-legacy-accent text-legacy-bg font-display font-black px-3 py-1 text-[9px] uppercase tracking-widest">
                Alpha Feature
              </div>

              <div className="w-20 h-20 bg-legacy-accent flex items-center justify-center">
                <ElaborateCalendar />
              </div>

              <div className="space-y-4">
                <h3 className="font-display font-black text-2xl text-legacy-accent uppercase italic">Logística Elite</h3>
                <p className="text-gray-300 text-lg leading-relaxed">
                  Agendamento nativo integrado. Encontre seu adversário e defina o horário em segundos. Sem burocracia, apenas futebol.
                </p>
              </div>

              {/* Decorative Icon Background */}
              <div className="absolute -bottom-8 -right-8 opacity-5 group-hover:opacity-10 transition-opacity">
                <div className="w-32 h-32 border-8 border-white"></div>
              </div>
            </motion.div>

            {/* Card B: Súmula IA */}
            <motion.div 
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: 0.1 }}
              className="group relative bg-legacy-bg border-2 border-legacy-accent p-10 md:p-12 space-y-8 overflow-hidden"
            >
              {/* Alpha Badge */}
              <div className="absolute top-0 right-0 bg-legacy-accent text-legacy-bg font-display font-black px-3 py-1 text-[9px] uppercase tracking-widest">
                Alpha Feature
              </div>

              <div className="w-20 h-20 bg-legacy-accent flex items-center justify-center">
                <ElaborateScanner />
              </div>

              <div className="space-y-4">
                <h3 className="font-display font-black text-2xl text-legacy-accent uppercase italic">Upload de Resultados Automatizado</h3>
                <p className="text-gray-300 text-lg leading-relaxed">
                  Envio de resultados via foto. Nossa tecnologia processa os dados e atualiza a liga em tempo real. O futuro da gestão é automático.
                </p>
              </div>

              {/* Decorative Icon Background */}
              <div className="absolute -bottom-8 -right-8 opacity-5 group-hover:opacity-10 transition-opacity">
                <div className="w-32 h-32 rounded-full border-8 border-white"></div>
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Block 5: Glory and Achievements Section */}
      <section className="relative bg-legacy-bg py-24 md:py-32 overflow-hidden">
        <TextureOverlay />
        <div className="relative z-10 max-w-7xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            
            {/* Left Content: Trophy Showcase */}
            <motion.div 
              initial={{ opacity: 0, scale: 0.95 }}
              whileInView={{ opacity: 1, scale: 1 }}
              viewport={{ once: true }}
              transition={{ duration: 0.8 }}
              className="relative flex justify-center items-center py-12"
            >
              {/* Decorative Background Grid */}
              <div className="absolute inset-0 grid grid-cols-6 gap-4 opacity-5 pointer-events-none">
                {Array.from({ length: 24 }).map((_, i) => (
                  <div key={i} className="aspect-square border border-white"></div>
                ))}
              </div>

              {/* Trophy Showcase Container */}
              <div className="relative flex items-end gap-4 md:gap-8">
                {/* Small Trophy */}
                <div className="w-16 md:w-24 h-32 md:h-48 bg-legacy-card border-t-4 border-white/10 flex flex-col items-center justify-end pb-4">
                  <Trophy className="text-white/20 w-8 md:w-12 h-8 md:h-12 mb-2" />
                  <div className="w-8 md:w-12 h-1 bg-white/10"></div>
                </div>
                
                {/* Main Trophy */}
                <div className="w-24 md:w-32 h-48 md:h-64 bg-legacy-card border-t-4 border-legacy-accent flex flex-col items-center justify-end pb-8 relative">
                  <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-legacy-accent text-legacy-bg p-2 rotate-45">
                    <Trophy size={20} className="-rotate-45" />
                  </div>
                  <Trophy className="text-legacy-accent w-12 md:w-16 h-12 md:h-16 mb-4" />
                  <div className="w-16 md:w-20 h-2 bg-legacy-accent"></div>
                </div>

                {/* Medium Trophy */}
                <div className="w-20 md:w-28 h-40 md:h-56 bg-legacy-card border-t-4 border-white/10 flex flex-col items-center justify-end pb-6">
                  <Trophy className="text-white/40 w-10 md:w-14 h-10 md:h-14 mb-3" />
                  <div className="w-12 md:w-16 h-1.5 bg-white/10"></div>
                </div>
              </div>

              {/* Floating Badge */}
              <div className="absolute bottom-4 right-4 md:right-12 bg-legacy-accent text-legacy-bg font-display font-black p-4 text-[10px] uppercase italic tracking-tighter rotate-3">
                Vitrine de Conquistas
              </div>
            </motion.div>

            {/* Right Content: Highlights */}
            <div className="space-y-12">
              <div className="space-y-4">
                <h2 className="font-display font-black text-3xl md:text-5xl text-legacy-accent uppercase italic tracking-tighter leading-none">
                  CADA VITÓRIA<br />
                  CONSTRÓI SUA TORCIDA.
                </h2>
                <div className="h-1 w-20 bg-legacy-accent"></div>
              </div>

              <div className="space-y-8">
                {/* Highlight 1: Legado Eternizado */}
                <motion.div 
                  initial={{ opacity: 0, x: 20 }}
                  whileInView={{ opacity: 1, x: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.5 }}
                  className="relative p-8 border-l-4 border-legacy-accent bg-legacy-card group"
                >
                  {/* Geometric Frame Detail */}
                  <div className="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-legacy-accent opacity-50"></div>
                  <div className="absolute bottom-0 left-0 w-8 h-8 border-b-2 border-l-2 border-legacy-accent opacity-50"></div>
                  
                  <div className="flex gap-6 items-start">
                    <div className="bg-legacy-accent p-3 shrink-0">
                      <Trophy className="text-legacy-bg" size={24} strokeWidth={2.5} />
                    </div>
                    <div className="space-y-2">
                      <h3 className="font-display font-black text-xl text-white uppercase italic">Legado Eternizado</h3>
                      <p className="text-gray-400 text-sm leading-relaxed">
                        Seus títulos do Alpha geram medalhas exclusivas no seu perfil de Fundador. Marque seu nome na história desde o primeiro dia.
                      </p>
                    </div>
                  </div>
                </motion.div>

                {/* Highlight 2: Arquibancada Pulsante */}
                <motion.div 
                  initial={{ opacity: 0, x: 20 }}
                  whileInView={{ opacity: 1, x: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.5, delay: 0.1 }}
                  className="relative p-8 border-l-4 border-legacy-accent bg-legacy-card group"
                >
                  {/* Geometric Frame Detail */}
                  <div className="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-legacy-accent opacity-50"></div>
                  <div className="absolute bottom-0 left-0 w-8 h-8 border-b-2 border-l-2 border-legacy-accent opacity-50"></div>

                  <div className="flex gap-6 items-start">
                    <div className="bg-legacy-accent p-3 shrink-0">
                      <Users className="text-legacy-bg" size={24} strokeWidth={2.5} />
                    </div>
                    <div className="space-y-2">
                      <h3 className="font-display font-black text-xl text-white uppercase italic">Arquibancada Pulsante</h3>
                      <p className="text-gray-400 text-sm leading-relaxed">
                        Desbloqueie níveis de torcida e veja o prestígio do seu clube crescer a cada temporada. O estádio cheio é o reflexo da sua glória.
                      </p>
                    </div>
                  </div>
                </motion.div>
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* Block 6: Closing and Institutional Footer */}
      <section className="relative bg-legacy-card py-24 md:py-32 border-t border-white/5 pb-48">
        <TextureOverlay />
        <div className="relative z-10 max-w-7xl mx-auto px-6 text-center space-y-16">
          
          {/* Closing Headline */}
          <div className="space-y-6">
            <h2 className="font-display font-black text-3xl md:text-6xl text-white uppercase italic tracking-tighter leading-none">
              UMA COMUNIDADE FEITA<br />
              POR <span className="text-legacy-accent">GESTORES REAIS.</span>
            </h2>
            <div className="h-1 w-24 bg-legacy-accent mx-auto"></div>
          </div>

          {/* Social Proof Badge */}
          <div className="inline-flex flex-col items-center gap-4 bg-legacy-bg border-2 border-legacy-accent p-8 md:p-10">
            <div className="flex gap-2">
              {Array.from({ length: 5 }).map((_, i) => (
                <Star key={i} className="w-6 h-6 text-legacy-accent fill-legacy-accent" />
              ))}
            </div>
            <div className="space-y-1">
              <div className="font-display font-black text-xl text-white uppercase italic tracking-tighter">
                SCORE SOCIAL: 5.0
              </div>
              <div className="text-[10px] font-bold text-legacy-accent uppercase tracking-[0.3em]">
                Fair Play Garantido
              </div>
            </div>
          </div>

          {/* Final CTA Button */}
          <div className="flex justify-center pt-8 w-full">
            <a 
              href="https://chat.whatsapp.com/EOng6iq0Wqs54jb5l7sudo" 
              target="_blank" 
              rel="noopener noreferrer"
              className="group relative bg-legacy-accent text-legacy-bg font-display font-black text-lg sm:text-2xl px-6 sm:px-16 py-4 sm:py-8 uppercase tracking-tighter hover:scale-105 transition-transform active:scale-95 flex items-center justify-center gap-2 sm:gap-4 sm:whitespace-nowrap shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] sm:shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] w-full sm:w-auto max-w-[90vw] sm:max-w-none"
            >
              GARANTIR ACESSO AO ALPHA
              <ChevronRight className="group-hover:translate-x-1 transition-transform w-5 h-5 sm:w-8 sm:h-8 shrink-0" />
            </a>
          </div>

          {/* Institutional Footer Content */}
          <div className="pt-24 grid grid-cols-1 md:grid-cols-3 gap-12 items-start border-t border-white/10 text-left">
            {/* Brand Column */}
            <div className="space-y-6">
              <div className="font-display font-black text-3xl tracking-tighter italic">
                LEGACY<span className="text-legacy-accent">XI</span>
              </div>
              <p className="text-gray-500 text-xs font-bold uppercase tracking-widest leading-loose">
                O Futuro da Gestão de Carreira Online.<br />
                Desenvolvido para a elite do EAFC.
              </p>
            </div>

            {/* Links Column */}
            <div className="grid grid-cols-2 gap-8">
              <div className="space-y-4">
                <h4 className="text-[10px] font-black text-white uppercase tracking-[0.2em] mb-6">Plataforma</h4>
                <ul className="space-y-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest">
                  <li><a href="#" className="hover:text-legacy-accent transition-colors">Home</a></li>
                  <li><a href="#" className="hover:text-legacy-accent transition-colors">Sobre o Alpha</a></li>
                  <li><a href="#" className="hover:text-legacy-accent transition-colors">FAQ</a></li>
                </ul>
              </div>
              <div className="space-y-4">
                <h4 className="text-[10px] font-black text-white uppercase tracking-[0.2em] mb-6">Legal</h4>
                <ul className="space-y-3 text-[11px] font-bold text-gray-500 uppercase tracking-widest">
                  <li><a href="#" className="hover:text-legacy-accent transition-colors">Regras de Conduta</a></li>
                  <li><a href="#" className="hover:text-legacy-accent transition-colors">Privacidade</a></li>
                  <li><a href="#" className="hover:text-legacy-accent transition-colors">Termos</a></li>
                </ul>
              </div>
            </div>

            {/* Social Column */}
            <div className="space-y-6 md:text-right">
              <h4 className="text-[10px] font-black text-white uppercase tracking-[0.2em]">Siga o Legacy XI</h4>
              <div className="flex md:justify-end gap-4">
                <a 
                  href="https://www.instagram.com/legaxi.online/" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  className="w-10 h-10 bg-black hover:bg-legacy-accent hover:text-legacy-bg transition-all flex items-center justify-center border border-white/10 text-white"
                >
                  <Instagram size={18} />
                </a>
                <a 
                  href="https://www.tiktok.com/@legaxi.online?is_from_webapp=1&sender_device=pc" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  className="w-10 h-10 bg-black hover:bg-legacy-accent hover:text-legacy-bg transition-all flex items-center justify-center border border-white/10 text-white"
                >
                  <TikTokIcon />
                </a>
                <a 
                  href="https://www.facebook.com/legaxi.online/" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  className="w-10 h-10 bg-black hover:bg-legacy-accent hover:text-legacy-bg transition-all flex items-center justify-center border border-white/10 text-white"
                >
                  <Facebook size={18} />
                </a>
                <a 
                  href="https://discord.gg/WWWP953w" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  className="w-10 h-10 bg-black hover:bg-legacy-accent hover:text-legacy-bg transition-all flex items-center justify-center border border-white/10 text-white"
                >
                  <Disc size={18} />
                </a>
              </div>
              <div className="text-[10px] font-bold text-gray-600 uppercase tracking-widest pt-4">
                © 2026 Legacy XI - O Futuro da Gestão de Carreira Online.
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Footer / Bottom Bar */}
      <footer className="fixed bottom-0 w-full bg-legacy-card border-t border-white/5 py-3 px-6 z-50">
        <div className="max-w-7xl mx-auto flex justify-between items-center text-[10px] font-bold uppercase tracking-[0.2em] text-gray-500">
          <span>LEGACY XI © 2026</span>
          <div className="flex gap-6">
            <span className="text-legacy-accent">Status: Online</span>
            <span>Powered by MCO</span>
          </div>
        </div>
      </footer>
    </div>
  );
}
