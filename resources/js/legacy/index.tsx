import React, { useEffect, useMemo, useRef, useState } from 'react';
import ReactDOM from 'react-dom/client';

const LEGACY_CONFIG = (window as any).__LEGACY_CONFIG__ || {};
const CSRF_TOKEN = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content || '';

const DAY_LABEL_TO_INDEX: Record<string, number> = {
  DOM: 0,
  SEG: 1,
  TER: 2,
  QUA: 3,
  QUI: 4,
  SEX: 5,
  SAB: 6,
};

const INDEX_TO_DAY_LABEL: Record<number, string> = {
  0: 'DOM',
  1: 'SEG',
  2: 'TER',
  3: 'QUA',
  4: 'QUI',
  5: 'SEX',
  6: 'SAB',
};

const buildEmptyAvailability = () => ({
  SEG: [],
  TER: [],
  QUA: [],
  QUI: [],
  SEX: [],
  SAB: [],
  DOM: [],
});

const jsonRequest = async (url: string, options: RequestInit = {}) => {
  const response = await fetch(url, {
    credentials: 'same-origin',
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': CSRF_TOKEN,
      ...(options.headers || {}),
    },
  });

  const payload = await response.json().catch(() => ({}));
  if (!response.ok) {
    const errors = payload?.errors;
    const firstError =
      errors && typeof errors === 'object'
        ? Object.values(errors).flat().find(Boolean)
        : null;

    throw new Error((firstError as string) || payload?.message || 'Falha na requisição.');
  }

  return payload;
};

// --- Configurações de Dados (Mock) ---
const CONFED_CONFIG: any = {
  UEFA: { 
    name: 'UEFA Europe', 
    color: '#0045e6', 
    icon: 'fa-euro-sign', 
    trophy: 'CHAMPIONS CUP',
    leagues: [
      { id: 'u-elite', name: 'ELITE DIVISION', tier: 'S', players: '20/20', status: 'ACTIVE' },
      { id: 'u-pro', name: 'PRO LEAGUE', tier: 'A', players: '18/20', status: 'OPEN' },
      { id: 'u-dev', name: 'DEVELOPMENT CUP', tier: 'B', players: '10/20', status: 'OPEN' }
    ]
  }
};

const MOCK_INBOX_MESSAGES = [
  { id: 1, type: 'NEGOCIAÇÃO', title: 'PROPOSTA POR VINÍCIUS JR.', sender: 'MANCHESTER CITY', content: 'O City ofereceu M$ 185M para fechar o negócio agora.', date: '14:20', urgent: true, action: 'TRANSFER' },
  { id: 2, type: 'SÚMULA', title: 'PLACAR REGISTRADO', sender: 'GOLIAS_FC', content: 'Seu adversário registrou a derrota (1x2) na Elite Division.', date: '12:05', urgent: false, action: 'MATCH' },
  { id: 3, type: 'CONVITE', title: 'COPA DO BRASIL LEGACY', sender: 'ADMIN MCO', content: 'Você foi pré-selecionado para o Draft da Temporada 26.', date: 'ONTEM', urgent: false, action: 'INVITE' },
  { id: 4, type: 'SISTEMA', title: 'PREMIAÇÃO DISPONÍVEL', sender: 'LEGACY XI', content: 'Seu bônus de Uber Score de 4.8 estrelas foi creditado: M$ 25M.', date: 'ONTEM', urgent: false, action: 'FINANCE' },
];

const MOCK_LEADERBOARD = [
  { rank: 1, name: 'TREINADOR_ALPHA', club: 'PSG ESPORTS', skill: 98, trophies: 12, uber: 5.0 },
  { rank: 2, name: 'GOLIAS_FC', club: 'REAL MADRID', skill: 95, trophies: 10, uber: 4.9 },
  { rank: 3, name: 'SKILL_MASTER', club: 'MAN CITY', skill: 94, trophies: 8, uber: 4.7 },
  { rank: 12, name: 'RODRIGO_MANAGER', club: 'CRUZEIRO EC', skill: 92, trophies: 3, uber: 4.8, isUser: true },
  { rank: 4, name: 'THE_KING_XI', club: 'BAYERN', skill: 93, trophies: 7, uber: 4.5 },
  { rank: 5, name: 'ULTIMATE_PRO', club: 'LIVERPOOL', skill: 91, trophies: 6, uber: 4.4 },
];

const MOCK_RECENT_RESULTS = [
  { id: 201, opponent: 'MANCHESTER CITY', scoreH: 2, scoreA: 1, competition: 'ELITE DIVISION', date: 'HOJE', status: 'REPORTED' },
  { id: 202, opponent: 'FC BAYERN', scoreH: 0, scoreA: 0, competition: 'CHAMPIONS CUP', date: 'ONTEM', status: 'REPORTED' },
];

const MOCK_PENDING_MATCHES = [
  { id: 301, opponent: 'ARSENAL FC', competition: 'ELITE DIVISION', deadline: '24H', reportedScoreH: 1, reportedScoreA: 3 },
];

const MOCK_TO_SCHEDULE = {
  LIGA: [
    { id: 401, opponent: 'BORUSSIA DORTMUND', deadline: 'DOMINGO 23:59', status: 'ABERTO' },
    { id: 402, opponent: 'INTER MILAN', deadline: 'DOMINGO 23:59', status: 'ABERTO' }
  ],
  COPA: [
    { id: 501, opponent: 'FC BARCELONA', deadline: 'QUARTA 22:00', status: 'URGENTE' }
  ],
  CONTINENTAL: [
    { id: 601, opponent: 'PSG', deadline: 'SEXTA 20:00', status: 'ABERTO' }
  ]
};

const MOCK_LEAGUE_TABLE = [
  { pos: 1, club: 'REAL MADRID CF', p: 14, v: 12, pts: 36 },
  { pos: 2, club: 'MANCHESTER CITY', p: 14, v: 11, pts: 34 },
  { pos: 3, club: 'CRUZEIRO EC', p: 14, v: 10, pts: 31, isUser: true },
  { pos: 4, club: 'FC BAYERN', p: 14, v: 9, pts: 28 },
  { pos: 5, club: 'LIVERPOOL FC', p: 14, v: 8, pts: 26 },
  { pos: 6, club: 'PSG', p: 14, v: 7, pts: 23 },
  { pos: 7, club: 'ARSENAL', p: 14, v: 7, pts: 22 },
  { pos: 8, club: 'INTER MILAN', p: 14, v: 6, pts: 20 },
  { pos: 9, club: 'FC BARCELONA', p: 14, v: 6, pts: 19 },
  { pos: 10, club: 'BORUSSIA DORTMUND', p: 14, v: 5, pts: 17 },
  { pos: 11, club: 'ATLETICO MADRID', p: 14, v: 5, pts: 16 },
  { pos: 12, club: 'AC MILAN', p: 14, v: 4, pts: 14 },
  { pos: 13, club: 'JUVENTUS', p: 14, v: 4, pts: 13 },
  { pos: 14, club: 'BENFICA', p: 14, v: 3, pts: 10 },
  { pos: 15, club: 'FC PORTO', p: 14, v: 2, pts: 8 },
  { pos: 16, club: 'AJAX', p: 14, v: 1, pts: 4 },
];

const MOCK_CUP_BRACKET = [
  { stage: 'OITAVAS DE FINAL', matches: [
    { home: 'CRUZEIRO EC', away: 'PSG', scoreH: 2, scoreA: 2, pensH: 5, pensA: 4, isUser: true, status: 'FINISHED' },
    { home: 'REAL MADRID', away: 'BENFICA', scoreH: 3, scoreA: 1, status: 'FINISHED' },
    { home: 'MAN CITY', away: 'AJAX', scoreH: 4, scoreA: 0, status: 'FINISHED' },
    { home: 'BAYERN', away: 'PORTO', scoreH: 2, scoreA: 1, status: 'FINISHED' },
    { home: 'LIVERPOOL', away: 'MILAN', scoreH: 1, scoreA: 2, status: 'FINISHED' },
    { home: 'ARSENAL', away: 'JUVENTUS', scoreH: 0, scoreA: 0, pensH: 3, pensA: 5, status: 'FINISHED' },
  ]}
];

const MOCK_CONTINENTAL_GROUPS = {
  'GRUPO A': [
    { pos: 1, club: 'REAL MADRID', p: 6, v: 5, pts: 15 },
    { pos: 2, club: 'MILAN', p: 6, v: 3, pts: 10 },
    { pos: 3, club: 'BENFICA', p: 6, v: 2, pts: 7 },
    { pos: 4, club: 'PORTO', p: 6, v: 0, pts: 1 },
  ],
  'GRUPO B': [
    { pos: 1, club: 'CRUZEIRO EC', p: 6, v: 4, pts: 13, isUser: true },
    { pos: 2, club: 'MAN CITY', p: 6, v: 3, pts: 11 },
    { pos: 3, club: 'JUVENTUS', p: 6, v: 2, pts: 7 },
    { pos: 4, club: 'AJAX', p: 6, v: 0, pts: 1 },
  ],
  'GRUPO C': [
    { pos: 1, club: 'BAYERN', p: 6, v: 5, pts: 16 },
    { pos: 2, club: 'BARCELONA', p: 6, v: 4, pts: 12 },
    { pos: 3, club: 'INTER MILAN', p: 6, v: 1, pts: 4 },
    { pos: 4, club: 'PSG', p: 6, v: 0, pts: 2 },
  ],
  'GRUPO D': [
    { pos: 1, club: 'LIVERPOOL', p: 6, v: 5, pts: 15 },
    { pos: 2, club: 'ARSENAL', p: 6, v: 4, pts: 12 },
    { pos: 3, club: 'DORTMUND', p: 6, v: 2, pts: 6 },
    { pos: 4, club: 'ATLÉTICO', p: 6, v: 0, pts: 1 },
  ],
};

const MOCK_CONTINENTAL_BRACKET = [
  { stage: 'QUARTAS DE FINAL', matches: [
    { home: 'REAL MADRID', away: 'BARCELONA', scoreH: 2, scoreA: 1, status: 'FINISHED' },
    { home: 'CRUZEIRO EC', away: 'ARSENAL', scoreH: 3, scoreA: 0, isUser: true, status: 'FINISHED' },
    { home: 'BAYERN', away: 'MILAN', scoreH: 2, scoreA: 2, pensH: 4, pensA: 2, status: 'FINISHED' },
    { home: 'LIVERPOOL', away: 'MAN CITY', scoreH: 1, scoreA: 2, status: 'FINISHED' },
  ]}
];

const MOCK_HISTORICAL_STATS = [
  { season: 'TEMP 24', league: 'ELITE DIVISION', pos: '4º', wins: 22, draws: 8, losses: 10, goalsFor: 85, goalsAgainst: 55, trophy: null },
  { season: 'TEMP 23', league: 'ELITE DIVISION', pos: '1º', wins: 32, draws: 4, losses: 2, goalsFor: 110, goalsAgainst: 40, trophy: 'CHAMPION' },
  { season: 'TEMP 22', league: 'PRO LEAGUE', pos: '1º', wins: 35, draws: 3, losses: 0, goalsFor: 125, goalsAgainst: 22, trophy: 'PRO CUP' },
];

const MOCK_STATS_TEMPLATE = { PAC: 85, SHO: 80, PAS: 75, DRI: 85, DEF: 50, PHY: 70 };
const MOCK_DETAILED_TEMPLATE = {
  PACE: { 'Velocidade': 85 },
  SHOOTING: { 'Finalização': 80 },
  PASSING: { 'Visão': 75 },
  DRIBBLING: { 'Drible': 85 },
  DEFENSE: { 'Intercepção': 50 },
  PHYSICAL: { 'Força': 70 }
};

const MOCK_SQUAD = [
  { 
    id: 1, name: 'VINÍCIUS JR.', ovr: 90, pos: 'ATA', age: 23, salary: 12.5, marketValue: 150,
    photo: 'https://img.asmedia.epimg.net/resizer/v2/LALQ7O7P2ZGVPL6J5H5A3X4V6E.jpg?auth=f8c5b0b1b1b1b1b1b1b1b1b1b1b1b1b1&width=1200&height=1200&smart=true',
    stats: { PAC: 95, SHO: 82, PAS: 78, DRI: 91, DEF: 34, PHY: 68 },
    detailedStats: {
      PACE: { 'Aceleração': 96, 'Pique': 94 },
      SHOOTING: { 'Posicionamento': 89, 'Finalização': 84, 'Força do Chute': 76, 'Chute Longo': 70, 'Voleio': 75 },
      PASSING: { 'Visão': 81, 'Cruzamento': 77, 'Passe Curto': 82, 'Passe Longo': 68, 'Curva': 84 },
      DRIBBLING: { 'Agilidade': 94, 'Equilíbrio': 86, 'Reação': 89, 'Controle': 90, 'Drible': 92, 'Compostura': 84 },
      DEFENSE: { 'Interceptação': 25, 'Cabeceio': 50, 'Noção Defensiva': 32, 'Dividida': 24 },
      PHYSICAL: { 'Salto': 75, 'Fôlego': 84, 'Força': 60, 'Agressividade': 65 }
    },
    skillMoves: 5, weakFoot: 4, 
    playstyles: ['FINESSE SHOT', 'QUICK STEP', 'TRIVELA', 'TECHNICAL']
  },
  { 
    id: 2, name: 'BELLINGHAM', ovr: 88, pos: 'MC', age: 20, salary: 10.2, marketValue: 180,
    photo: 'https://publish.onefootball.com/wp-content/uploads/sites/10/2023/12/Real-Madrid-v-SSC-Napoli-Group-C-UEFA-Champions-League-202324-1701511242-1000x750.jpg',
    stats: { PAC: 78, SHO: 80, PAS: 85, DRI: 88, DEF: 78, PHY: 82 },
    detailedStats: {
      PACE: { 'Aceleração': 76, 'Pique': 80 },
      SHOOTING: { 'Posicionamento': 85, 'Finalização': 82, 'Força do Chute': 81, 'Chute Longo': 79, 'Penalidade': 72 },
      PASSING: { 'Visão': 88, 'Cruzamento': 76, 'Passe Curto': 89, 'Passe Longo': 84, 'Curva': 80 },
      DRIBBLING: { 'Agilidade': 82, 'Equilíbrio': 78, 'Reação': 90, 'Controle': 88, 'Drible': 88, 'Compostura': 90 },
      DEFENSE: { 'Interceptação': 80, 'Cabeceio': 75, 'Noção Defensiva': 78, 'Dividida': 81 },
      PHYSICAL: { 'Salto': 79, 'Fôlego': 90, 'Força': 82, 'Agressividade': 84 }
    },
    skillMoves: 4, weakFoot: 4,
    playstyles: ['RELENTLESS', 'POWER SHOT', 'INTERCEPT', 'ANTICIPATE']
  },
];

const MOCK_MARKET_PLAYERS = [
  { id: 101, name: 'E. HAALAND', ovr: 91, pos: 'ATA', age: 23, value: 200, status: 'CONTRATADO', club: 'MAN CITY', photo: 'https://cdn.resfu.com/media/img_news/agencias/afp/2023/11/04/ea203b879a9f99e3f9a7b9e9e9e9e9e9.jpg?size=1000x', stats: MOCK_STATS_TEMPLATE, detailedStats: MOCK_DETAILED_TEMPLATE, playstyles: ['POWER SHOT'], skillMoves: 3, weakFoot: 3, salary: 25 },
  { id: 102, name: 'K. MBAPPÉ', ovr: 91, pos: 'ATA', age: 25, value: 220, status: 'CONTRATADO', club: 'REAL MADRID', photo: 'https://images2.minutemediacdn.com/image/upload/c_crop,w_4444,h_2499,x_0,y_156/c_fill,w_720,ar_16:9,f_auto,q_auto,g_auto/images%2FGettyImages%2Fmmsport%2F90min_en_international_web%2F01hznh0k0f4q9104b9p3.jpg', stats: MOCK_STATS_TEMPLATE, detailedStats: MOCK_DETAILED_TEMPLATE, playstyles: ['QUICK STEP'], skillMoves: 5, weakFoot: 4, salary: 30 },
  { id: 103, name: 'K. DE BRUYNE', ovr: 90, pos: 'MC', age: 32, value: 120, status: 'LIVRE', club: 'AGENTE LIVRE', photo: 'https://tmssl.akamaized.net/images/foto/galerie/kevin-de-bruyne-manchester-city-2023-24-1710497555-132338.jpg', stats: MOCK_STATS_TEMPLATE, detailedStats: MOCK_DETAILED_TEMPLATE, playstyles: ['INCISIVE PASS'], skillMoves: 4, weakFoot: 5, salary: 20 },
  { id: 106, name: 'SALAH', ovr: 89, pos: 'PD', age: 31, value: 90, status: 'CONTRATADO', club: 'LIVERPOOL', photo: 'https://tmssl.akamaized.net/images/foto/galerie/mohamed-salah-liverpool-2023-24-1701103043-122915.jpg', stats: MOCK_STATS_TEMPLATE, detailedStats: MOCK_DETAILED_TEMPLATE, playstyles: ['FINESSE SHOT'], skillMoves: 4, weakFoot: 3, salary: 22 },
  { id: 107, name: 'VANDIJK', ovr: 89, pos: 'ZAG', age: 32, value: 85, status: 'CONTRATADO', club: 'LIVERPOOL', photo: 'https://tmssl.akamaized.net/images/foto/galerie/virgil-van-dijk-liverpool-2023-24-1698759325-120713.jpg', stats: MOCK_STATS_TEMPLATE, detailedStats: MOCK_DETAILED_TEMPLATE, playstyles: ['ANTICIPATE'], skillMoves: 2, weakFoot: 3, salary: 19 },
];

const MOCK_WATCHLIST = [
  { id: 104, name: 'MUSIALA', ovr: 87, pos: 'MEI', age: 21, value: 95, status: 'CONTRATADO', club: 'FC BAYERN', photo: 'https://static.dw.com/image/67272895_605.jpg', stats: MOCK_STATS_TEMPLATE, detailedStats: MOCK_DETAILED_TEMPLATE, playstyles: ['TECHNICAL'], skillMoves: 5, weakFoot: 4, salary: 15 },
  { id: 105, name: 'RODRYGO', ovr: 86, pos: 'ATA', age: 23, value: 110, status: 'CONTRATADO', club: 'REAL MADRID', photo: 'https://tmssl.akamaized.net/images/foto/galerie/rodrygo-real-madrid-2023-24-1712733934-134591.jpg', stats: MOCK_STATS_TEMPLATE, detailedStats: MOCK_DETAILED_TEMPLATE, playstyles: ['FINESSE SHOT'], skillMoves: 4, weakFoot: 4, salary: 18 },
];

// --- Utilitários de Estilo ---
const AGGRESSIVE_CLIP = "polygon(16px 0, 100% 0, 100% calc(100% - 16px), calc(100% - 16px) 100%, 0 100%, 0 16px)";
const SHIELD_CLIP = "polygon(0 0, 100% 0, 100% 85%, 50% 100%, 0 85%)";
const SLANTED_PATTERN = "repeating-linear-gradient(45deg, rgba(255,215,0,0.05) 0, rgba(255,215,0,0.05) 1px, transparent 0, transparent 10px)";

// --- Componentes de Design System ---

const MCOButton = ({ children, onClick, variant = 'primary', className = '', disabled = false }: any) => {
  const baseStyles = "relative px-8 py-4 font-black uppercase tracking-tighter transition-all active:translate-y-1 text-xs italic font-heading outline-none border-none cursor-pointer overflow-hidden";
  const variants: any = {
    primary: "bg-[#FFD700] text-[#121212]",
    outline: "bg-[#1E1E1E] text-[#FFD700] border-2 border-[#FFD700]",
    ghost: "bg-transparent text-white/50 hover:text-white",
    danger: "bg-[#B22222] text-white",
    success: "bg-[#008000] text-white"
  };
  
  return (
    <button onClick={onClick} disabled={disabled} className={`${baseStyles} ${variants[variant] || ''} ${className}`} style={{ clipPath: AGGRESSIVE_CLIP }}>
      <span className="relative z-10">{children}</span>
    </button>
  );
};

const MCOCard = ({ children, title, className = "", onClick, active = false, accentColor = "#FFD700" }: any) => (
  <div onClick={onClick} className={`bg-[#1E1E1E] transition-all cursor-pointer relative ${className}`} style={{ clipPath: AGGRESSIVE_CLIP, borderBottom: `3px solid ${active ? accentColor : 'transparent'}` }}>
    {title && (
      <div className="flex justify-between items-center mb-4 px-2">
        <h3 className="text-[10px] font-black uppercase tracking-[0.2em] italic font-heading" style={{ color: accentColor }}>{title}</h3>
        {active && <div className="w-2 h-2 bg-[#FFD700] shadow-[0_0_8px_#FFD700]"></div>}
      </div>
    )}
    <div className="relative z-10">{children}</div>
    <div className="absolute right-0 top-0 bottom-0 w-[3px]" style={{ backgroundColor: active ? accentColor : 'rgba(255,255,255,0.05)' }}></div>
  </div>
);

const MCOBottomNav = ({ activeView, onViewChange }: { activeView: string, onViewChange: (v: string) => void }) => {
  const navItems = [
    { id: 'hub-global', icon: 'fa-house', label: 'INÍCIO' },
    { id: 'match-center', icon: 'fa-shield-halved', label: 'CONFRONTOS' },
    { id: 'inbox', icon: 'fa-envelope', label: 'INBOX', badge: true },
    { id: 'profile', icon: 'fa-user', label: 'PERFIL' }
  ];
  return (
    <nav className="fixed bottom-0 left-0 right-0 bg-[#1E1E1E] border-t-[3px] border-[#FFD700] flex justify-around p-2 safe-area-bottom z-50">
      {navItems.map((item) => {
        const isClubSubView = ['my-club', 'squad', 'achievements', 'finance', 'trophies'].includes(activeView);
        const active = activeView === item.id || 
                       (item.id === 'match-center' && (activeView === 'report-match' || activeView === 'confirm-match' || activeView === 'schedule-matches')) || 
                       (item.id === 'hub-global' && (activeView === 'tournaments' || isClubSubView || activeView === 'market' || activeView === 'league-table' || activeView === 'cup-detail' || activeView === 'continental-detail' || activeView === 'public-club-profile' || activeView === 'season-stats' || activeView === 'leaderboard'));
        return (
          <button key={item.id} onClick={() => onViewChange(item.id)} className={`relative flex flex-col items-center p-3 transition-all ${active ? 'text-[#FFD700]' : 'text-white/20'}`}>
            {item.badge && <div className="absolute top-2 right-2 w-2 h-2 bg-[#B22222] shadow-[0_0_8px_#B22222] animate-pulse"></div>}
            <i className={`fas ${item.icon} text-xl mb-1`}></i>
            <span className="text-[9px] font-black uppercase italic font-heading tracking-widest">{item.label}</span>
          </button>
        );
      })}
    </nav>
  );
};

const MCOTopBar = ({ careers, currentCareer, onCareerChange, uberScore = 4.5, skillRating = 88 }: any) => {
  const [isOpen, setIsOpen] = useState(false);
  
  const renderStars = (score: number) => {
    const stars = [];
    for (let i = 1; i <= 5; i++) {
      if (score >= i) stars.push(<i key={i} className="fas fa-star"></i>);
      else if (score >= i - 0.5) stars.push(<i key={i} className="fas fa-star-half-stroke"></i>);
      else stars.push(<i key={i} className="far fa-star opacity-20"></i>);
    }
    return stars;
  };

  return (
    <div className="fixed top-0 left-0 right-0 bg-[#1E1E1E] h-16 border-b-[3px] border-[#FFD700] z-[60] flex items-center px-4">
      <div className="relative shrink-0 z-20">
        <button 
          onClick={() => setIsOpen(!isOpen)}
          className="flex items-center gap-2 bg-[#121212] px-3 py-2 border-r-[2px] border-[#FFD700]"
          style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}
        >
          <i className="fas fa-briefcase text-[#FFD700] text-xs"></i>
          <span className="text-[9px] font-black italic uppercase text-white truncate max-w-[80px]">
            {currentCareer.name}
          </span>
          <i className={`fas fa-caret-down text-[8px] text-[#FFD700] transition-transform ${isOpen ? 'rotate-180' : ''}`}></i>
        </button>
        {isOpen && (
          <div className="absolute top-full left-0 mt-2 w-56 bg-[#1E1E1E] border-[2px] border-[#FFD700] shadow-2xl animate-in fade-in zoom-in-95 duration-200" style={{ clipPath: AGGRESSIVE_CLIP }}>
            {careers.map((c: any) => (
              <button 
                key={c.id} 
                onClick={() => { onCareerChange(c.id); setIsOpen(false); }}
                className={`w-full text-left p-4 text-[10px] font-black italic uppercase border-b border-white/5 last:border-0 hover:bg-[#FFD700] hover:text-[#121212] transition-colors ${currentCareer.id === c.id ? 'bg-[#FFD700]/10 text-[#FFD700]' : 'text-white'}`}
              >
                {c.name}
              </button>
            ))}
            <button className="w-full text-left p-4 text-[10px] font-black italic uppercase text-[#FFD700]/40 hover:text-[#FFD700] transition-colors bg-[#121212]/50">
              <i className="fas fa-plus mr-2"></i> NOVA GESTÃO
            </button>
          </div>
        )}
      </div>

      <div className="ml-auto flex items-center gap-3 shrink-0 z-20 pr-1">
        <div className="flex flex-col items-end">
          <div className="flex gap-0.5 text-[8px] text-[#FFD700]">
            {renderStars(uberScore)}
          </div>
          <span className="text-[6px] font-black italic text-white/30 uppercase tracking-[0.2em] mt-0.5">UBER SCORE</span>
        </div>
        <div className="bg-[#121212] h-10 px-3 flex flex-col items-center justify-center border-l-[3px] border-[#FFD700] relative overflow-hidden" style={{ clipPath: "polygon(0 0, calc(100% - 6px) 0, 100% 6px, 100% 100%, 0 100%)" }}>
           <div className="absolute inset-0 opacity-5 pointer-events-none" style={{ backgroundImage: SLANTED_PATTERN }}></div>
           <span className="text-lg font-black italic font-heading text-[#FFD700] leading-none relative z-10">{skillRating}</span>
           <span className="text-[6px] font-black italic text-white/40 leading-none relative z-10 tracking-tighter mt-0.5">SKILL RATING</span>
        </div>
      </div>
    </div>
  );
};

// --- New Inbox View ---

const InboxView = ({ onBack, onAction }: { onBack: () => void, onAction: (type: string) => void }) => {
  return (
    <div className="min-h-screen bg-[#121212] pt-8 pb-32 px-6 overflow-y-auto">
      <header className="mb-8">
        <MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40">
          <i className="fas fa-arrow-left mr-2"></i> VOLTAR
        </MCOButton>
        <h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">MCO INBOX</h2>
        <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">CENTRAL DE NEGÓCIOS</p>
      </header>

      <div className="space-y-4">
        {MOCK_INBOX_MESSAGES.map((msg) => (
          <div key={msg.id} className={`bg-[#1E1E1E] p-6 border-r-[3px] transition-all ${msg.urgent ? 'border-[#B22222]' : 'border-[#FFD700]'}`} style={{ clipPath: AGGRESSIVE_CLIP }}>
             <div className="flex justify-between items-start mb-4">
                <div>
                   <span className={`text-[8px] font-black px-2 py-0.5 italic tracking-tighter ${msg.urgent ? 'bg-[#B22222] text-white' : 'bg-[#FFD700] text-[#121212]'}`}>{msg.type}</span>
                   <h4 className="text-[13px] font-black italic uppercase text-white mt-2 leading-none">{msg.title}</h4>
                   <p className="text-[9px] font-bold text-[#FFD700] uppercase italic mt-1">DE: {msg.sender}</p>
                </div>
                <span className="text-[8px] font-black text-white/20 italic">{msg.date}</span>
             </div>
             <p className="text-[10px] text-white/60 leading-relaxed uppercase italic font-bold mb-6">{msg.content}</p>
             <div className="flex gap-2">
                <button 
                  onClick={() => onAction(msg.action)}
                  className="flex-1 bg-[#121212] border-2 border-[#FFD700] text-[#FFD700] text-[9px] font-black italic py-3 transition-colors active:bg-[#FFD700] active:text-[#121212]"
                  style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}
                >
                  ACESSAR PENDÊNCIA
                </button>
                <button className="bg-white/5 text-white/20 p-3" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>
                  <i className="fas fa-trash-can text-xs"></i>
                </button>
             </div>
          </div>
        ))}
      </div>
    </div>
  );
};

// --- Leaderboard View ---

const LeaderboardView = ({ onBack, onOpenProfile }: { onBack: () => void, onOpenProfile: (name: string) => void }) => {
  return (
    <div className="min-h-screen bg-[#121212] pt-8 pb-32 px-6 overflow-y-auto">
      <header className="mb-8">
        <MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40">
          <i className="fas fa-arrow-left mr-2"></i> VOLTAR
        </MCOButton>
        <h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">RANKING GLOBAL</h2>
        <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">OS MELHORES DA TEMPORADA</p>
      </header>

      <div className="space-y-3">
        {MOCK_LEADERBOARD.sort((a, b) => a.rank - b.rank).map((m, idx) => (
          <div 
            key={idx} 
            onClick={() => onOpenProfile(m.name)}
            className={`flex items-center gap-4 p-4 border-r-[3px] transition-all cursor-pointer active:scale-[0.98] ${m.isUser ? 'bg-[#FFD700] border-[#121212]' : 'bg-[#1E1E1E] border-[#FFD700]'}`} 
            style={{ clipPath: "polygon(10px 0, 100% 0, 100% 100%, 0 100%, 0 10px)" }}
          >
            <div className={`w-10 h-10 flex items-center justify-center font-black italic text-xl ${m.isUser ? 'text-[#121212]' : 'text-[#FFD700]'}`}>
              #{m.rank}
            </div>
            <div className="flex-grow overflow-hidden">
               <p className={`text-[12px] font-black italic uppercase truncate ${m.isUser ? 'text-[#121212]' : 'text-white'}`}>{m.name}</p>
               <p className={`text-[8px] font-bold uppercase italic opacity-60 ${m.isUser ? 'text-[#121212]' : 'text-[#FFD700]'}`}>{m.club}</p>
            </div>
            <div className="text-right shrink-0">
               <div className={`text-lg font-black italic font-heading ${m.isUser ? 'text-[#121212]' : 'text-white'}`}>{m.skill}</div>
               <div className={`text-[7px] font-black uppercase ${m.isUser ? 'text-[#121212]/40' : 'text-white/20'}`}>SKILL RATING</div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

// --- Season Stats View ---

const SeasonStatsView = ({ onBack, userStats }: { onBack: () => void, userStats: any }) => {
  const [tab, setTab] = useState<'current' | 'history'>('current');

  return (
    <div className="min-h-screen bg-[#121212] pt-8 pb-32 px-6 overflow-y-auto">
      <header className="mb-8">
        <MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40">
          <i className="fas fa-arrow-left mr-2"></i> VOLTAR
        </MCOButton>
        <h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">ESTATÍSTICAS</h2>
        <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">ANÁLISE DE PERFORMANCE</p>
      </header>

      <div className="flex gap-2 mb-10 bg-[#1E1E1E] p-1" style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}>
        <button 
          onClick={() => setTab('current')}
          className={`flex-1 py-4 text-[9px] font-black italic uppercase transition-all ${tab === 'current' ? 'bg-[#FFD700] text-[#121212]' : 'text-white/30'}`}
          style={{ clipPath: "polygon(6px 0, 100% 0, 100% 100%, 0 100%, 0 6px)" }}
        >
          TEMPORADA ATUAL
        </button>
        <button 
          onClick={() => setTab('history')}
          className={`flex-1 py-4 text-[9px] font-black italic uppercase transition-all ${tab === 'history' ? 'bg-[#FFD700] text-[#121212]' : 'text-white/30'}`}
          style={{ clipPath: "polygon(6px 0, 100% 0, 100% 100%, 0 100%, 0 6px)" }}
        >
          HISTÓRICO LEGACY
        </button>
      </div>

      <div className="animate-in fade-in slide-in-from-right-4 duration-300">
        {tab === 'current' ? (
          <div className="space-y-6">
             <div className="grid grid-cols-2 gap-4">
               <div className="bg-[#1E1E1E] p-6 border-l-[3px] border-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}>
                  <p className="text-[8px] font-black text-white/30 uppercase italic mb-1">GOLS REGISTRADOS</p>
                  <p className="text-3xl font-black italic font-heading text-white">{userStats.goals}</p>
               </div>
               <div className="bg-[#1E1E1E] p-6 border-l-[3px] border-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}>
                  <p className="text-[8px] font-black text-white/30 uppercase italic mb-1">VITÓRIAS TOTAL</p>
                  <p className="text-3xl font-black italic font-heading text-white">{userStats.wins}</p>
               </div>
             </div>

             <div className="bg-[#1E1E1E] p-8 border-r-[3px] border-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}>
                <div className="flex justify-between items-end mb-4">
                  <div>
                    <p className="text-[8px] font-black text-[#FFD700] uppercase italic mb-1">APROVEITAMENTO</p>
                    <h3 className="text-4xl font-black italic uppercase font-heading text-white">78.5%</h3>
                  </div>
                  <i className="fas fa-arrow-trend-up text-[#008000] text-2xl"></i>
                </div>
                <div className="h-2 bg-[#121212] w-full" style={{ clipPath: "polygon(2px 0, 100% 0, 100% 100%, 0 100%, 0 2px)" }}>
                  <div className="h-full bg-[#FFD700] shadow-[0_0_10px_#FFD700]" style={{ width: '78.5%' }}></div>
                </div>
             </div>

             <div className="bg-[#1E1E1E] p-6 border-b-[3px] border-white/5" style={{ clipPath: AGGRESSIVE_CLIP }}>
               <h4 className="text-[10px] font-black text-white/40 uppercase italic mb-4 tracking-widest">MÉTRICAS POR PARTIDA</h4>
               <div className="space-y-4">
                  <div className="flex justify-between items-center">
                    <span className="text-[11px] font-black italic uppercase">GOLS / JOGO</span>
                    <span className="text-lg font-black italic font-heading text-[#FFD700]">3.2</span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-[11px] font-black italic uppercase">ASSISTS / JOGO</span>
                    <span className="text-lg font-black italic font-heading text-white">1.8</span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-[11px] font-black italic uppercase">CLEAN SHEETS</span>
                    <span className="text-lg font-black italic font-heading text-white">42</span>
                  </div>
               </div>
             </div>
          </div>
        ) : (
          <div className="space-y-4">
            {MOCK_HISTORICAL_STATS.map((h, idx) => (
              <div key={idx} className="bg-[#1E1E1E] p-6 flex justify-between items-center border-l-[4px] border-white/5" style={{ clipPath: AGGRESSIVE_CLIP }}>
                <div>
                   <p className="text-[10px] font-black text-[#FFD700] uppercase italic tracking-widest">{h.season}</p>
                   <h4 className="text-lg font-black italic uppercase font-heading text-white">{h.league}</h4>
                   <p className="text-[9px] font-bold text-white/30 uppercase mt-1 italic">{h.wins}V - {h.draws}E - {h.losses}D</p>
                </div>
                <div className="text-right">
                   <div className="text-2xl font-black italic font-heading text-white mb-1">{h.pos}</div>
                   {h.trophy && <i className="fas fa-trophy text-[#FFD700] text-lg drop-shadow-[0_0_8px_#FFD700]"></i>}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

// --- Public Club Profile View ---

const PublicClubProfileView = ({ clubData, onBack }: { clubData: any, onBack: () => void }) => {
  const [activeTab, setActiveTab] = useState<'status' | 'elenco'>('status');

  const renderStars = (score: number) => {
    const stars = [];
    for (let i = 1; i <= 5; i++) {
      if (score >= i) stars.push(<i key={i} className="fas fa-star text-[#FFD700]"></i>);
      else stars.push(<i key={i} className="far fa-star text-white/10"></i>);
    }
    return stars;
  };

  return (
    <div className="min-h-screen bg-[#121212] pb-32 overflow-x-hidden">
      {/* Header Estilizado */}
      <div className="relative h-[300px] overflow-hidden">
         <div className="absolute inset-0 bg-gradient-to-b from-transparent via-[#121212]/80 to-[#121212] z-10"></div>
         <div className="absolute inset-0 opacity-10 blur-xl scale-150 rotate-12 bg-[radial-gradient(circle_at_center,_#FFD700_0%,_transparent_70%)]"></div>
         
         {/* Botão Voltar */}
         <button onClick={onBack} className="absolute top-8 left-6 z-30 bg-[#1E1E1E] text-white p-3 border-r-2 border-[#FFD700]" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>
           <i className="fas fa-arrow-left"></i>
         </button>

         {/* Escudo de Fundo */}
         <div className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 opacity-5 scale-[2.5] z-0">
           <i className="fas fa-shield-halved text-[#FFD700]"></i>
         </div>

         <div className="absolute inset-0 z-20 flex flex-col items-center justify-center pt-10">
            <div className="w-24 h-24 bg-[#1E1E1E] border-[3px] border-[#FFD700] flex items-center justify-center mb-4 shadow-[0_0_30px_rgba(255,215,0,0.2)]" style={{ clipPath: SHIELD_CLIP }}>
              <i className="fas fa-shield text-4xl text-[#FFD700]/40"></i>
            </div>
            <h2 className="text-4xl font-black italic uppercase font-heading text-white tracking-tighter leading-none mb-2">{clubData.clubName}</h2>
            <div className="flex items-center gap-3">
               <div className="flex gap-0.5 text-[10px]">
                 {renderStars(clubData.uberScore)}
               </div>
               <span className="w-1 h-1 bg-white/20 rounded-full"></span>
               <span className="text-[10px] font-black text-[#FFD700] italic uppercase tracking-widest">{clubData.skillRating} SKILL RATING</span>
            </div>
         </div>
      </div>

      {/* Tabs de Navegação Interna */}
      <div className="px-6 -mt-8 relative z-30 mb-8">
        <div className="bg-[#1E1E1E] p-1 flex gap-1" style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}>
          <button 
            onClick={() => setActiveTab('status')}
            className={`flex-1 py-4 text-[10px] font-black italic uppercase transition-all ${activeTab === 'status' ? 'bg-[#FFD700] text-[#121212]' : 'text-white/30'}`}
            style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}
          >
            STATUS DO LEGADO
          </button>
          <button 
            onClick={() => setActiveTab('elenco')}
            className={`flex-1 py-4 text-[10px] font-black italic uppercase transition-all ${activeTab === 'elenco' ? 'bg-[#FFD700] text-[#121212]' : 'text-white/30'}`}
            style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}
          >
            ELENCO PRINCIPAL
          </button>
        </div>
      </div>

      <div className="px-6 space-y-10">
        {activeTab === 'status' ? (
          <>
            {/* Status Geral */}
            <section className="space-y-4">
              <h4 className="text-[11px] font-black uppercase text-white/40 italic tracking-[0.2em] px-2">NÍVEL DE PRESTÍGIO</h4>
              <div className="bg-[#1E1E1E] p-8 border-l-[6px] border-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}>
                <div className="flex justify-between items-center">
                  <div>
                    <p className="text-[9px] text-[#FFD700] font-black uppercase italic tracking-widest mb-1">CLASSIFICAÇÃO ATUAL</p>
                    <h3 className="text-4xl font-black italic uppercase font-heading text-white">GIGANTE</h3>
                  </div>
                  <div className="text-right">
                    <p className="text-[9px] text-white/30 font-black uppercase italic tracking-widest mb-1">TORCIDA</p>
                    <p className="text-2xl font-black italic font-heading text-white">{(clubData.fans / 1000000).toFixed(1)}M</p>
                  </div>
                </div>
              </div>
            </section>

            {/* Galeria de Troféus */}
            <section className="space-y-4">
               <h4 className="text-[11px] font-black uppercase text-white/40 italic tracking-[0.2em] px-2">SALA DE TROFÉUS</h4>
               <div className="grid grid-cols-2 gap-4">
                 {clubData.wonTrophies.map((trophyId: string) => (
                   <div key={trophyId} className="bg-[#1E1E1E] p-6 text-center border-b-[3px] border-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}>
                      <i className="fas fa-trophy text-3xl text-[#FFD700] mb-3"></i>
                      <p className="text-[9px] font-black italic text-white uppercase leading-none truncate">TEMP 23</p>
                      <p className="text-[11px] font-black italic uppercase text-white/40 mt-1">ELITE DIV</p>
                   </div>
                 ))}
                 {clubData.wonTrophies.length === 0 && (
                    <div className="col-span-2 py-10 bg-[#1E1E1E] text-center opacity-20" style={{ clipPath: AGGRESSIVE_CLIP }}>
                      <p className="text-[10px] font-black italic uppercase tracking-widest">NENHUMA TAÇA REGISTRADA</p>
                    </div>
                 )}
               </div>
            </section>

            {/* Estatísticas Legacy */}
            <section className="space-y-4">
               <h4 className="text-[11px] font-black uppercase text-white/40 italic tracking-[0.2em] px-2">MÉTRICAS COMPETITIVAS</h4>
               <div className="grid grid-cols-3 gap-3">
                 <div className="bg-[#1E1E1E] p-4 text-center" style={{ clipPath: AGGRESSIVE_CLIP }}>
                    <p className="text-[8px] font-black text-[#FFD700] uppercase italic mb-1">VITÓRIAS</p>
                    <p className="text-xl font-black italic font-heading text-white">{clubData.wins}</p>
                 </div>
                 <div className="bg-[#1E1E1E] p-4 text-center" style={{ clipPath: AGGRESSIVE_CLIP }}>
                    <p className="text-[8px] font-black text-[#FFD700] uppercase italic mb-1">GOLS</p>
                    <p className="text-xl font-black italic font-heading text-white">{clubData.goals}</p>
                 </div>
                 <div className="bg-[#1E1E1E] p-4 text-center" style={{ clipPath: AGGRESSIVE_CLIP }}>
                    <p className="text-[8px] font-black text-[#FFD700] uppercase italic mb-1">ASSISTS</p>
                    <p className="text-xl font-black italic font-heading text-white">{clubData.assists}</p>
                 </div>
               </div>
            </section>
          </>
        ) : (
          <section className="space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-300">
             <h4 className="text-[11px] font-black uppercase text-white/40 italic tracking-[0.2em] px-2">CONTRATAÇÕES ATIVAS</h4>
             <div className="space-y-3">
               {MOCK_SQUAD.map(player => (
                 <div key={player.id} className="bg-[#1E1E1E] p-4 flex items-center gap-4 border-r-[3px] border-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}>
                    <div className="w-12 h-12 bg-[#121212] flex items-center justify-center border-b-2 border-[#FFD700]" style={{ clipPath: SHIELD_CLIP }}>
                      <img src={player.photo} className="w-full h-full object-cover grayscale opacity-60" />
                    </div>
                    <div className="flex-grow">
                      <p className="text-[12px] font-black italic uppercase text-white leading-none">{player.name}</p>
                      <p className="text-[9px] font-bold text-[#FFD700] uppercase italic mt-1">{player.pos} • OVR {player.ovr}</p>
                    </div>
                    <div className="text-right">
                       <p className="text-[8px] font-black text-white/20 uppercase italic">VALOR</p>
                       <p className="text-[10px] font-black italic text-white uppercase">M$ {player.marketValue}M</p>
                    </div>
                 </div>
               ))}
             </div>
          </section>
        )}
      </div>
    </div>
  );
};

// --- Schedule Matches View ---

const ScheduleMatchesView = ({ onBack }: { onBack: () => void }) => {
  const [schedFilter, setSchedFilter] = useState<'LIGA' | 'COPA' | 'CONTINENTAL'>('LIGA');
  const schedItems = MOCK_TO_SCHEDULE[schedFilter] || [];

  return (
    <div className="min-h-screen bg-[#121212] pt-8 pb-32 px-6">
      <header className="mb-8">
        <MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40">
          <i className="fas fa-arrow-left mr-2"></i> VOLTAR
        </MCOButton>
        <h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">AGENDAR PARTIDAS</h2>
        <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">GERENCIAMENTO DE AGENDA</p>
      </header>
      <div className="flex gap-2 mb-8 bg-[#1E1E1E] p-1" style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}>
        {(['LIGA', 'COPA', 'CONTINENTAL'] as const).map(f => (
          <button 
            key={f}
            onClick={() => setSchedFilter(f)}
            className={`flex-1 py-4 text-[9px] font-black italic uppercase transition-all ${schedFilter === f ? 'bg-[#FFD700] text-[#121212]' : 'text-white/30'}`}
            style={{ clipPath: "polygon(6px 0, 100% 0, 100% 100%, 0 100%, 0 6px)" }}
          >
            {f === 'LIGA' ? 'LIGA NAC.' : f === 'COPA' ? 'COPA LIGA' : 'CONTINENTAL'}
          </button>
        ))}
      </div>
      <div className="space-y-4">
        {schedItems.length > 0 ? schedItems.map(item => (
          <div key={item.id} className="bg-[#1E1E1E] p-6 border-l-[4px] border-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}>
            <div className="flex justify-between items-center">
              <div>
                <p className="text-[10px] text-[#FFD700] font-black uppercase italic tracking-[0.2em] mb-1">RODADA ABERTA</p>
                <h4 className="text-xl font-black italic uppercase font-heading text-white">{item.opponent}</h4>
                <div className="flex items-center gap-2 mt-3">
                  <i className="far fa-clock text-[#FFD700] text-xs"></i>
                  <span className="text-[9px] font-bold text-white/40 uppercase italic tracking-widest">LIMITE: {item.deadline}</span>
                </div>
              </div>
              <button 
                onClick={() => alert(`PROPONDO HORÁRIO PARA ${item.opponent}`)}
                className="bg-[#121212] border-2 border-[#FFD700] text-[#FFD700] text-[9px] font-black italic px-4 py-3 leading-none transition-transform active:scale-95"
                style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}
              >
                PROPOR HORÁRIO
              </button>
            </div>
          </div>
        )) : (
          <div className="text-center py-24 opacity-10">
            <i className="fas fa-check-double text-6xl mb-4"></i>
            <p className="text-sm font-black uppercase italic tracking-[0.4em]">SEM JOGOS PENDENTES</p>
          </div>
        )}
      </div>
      <div className="mt-12 p-6 bg-[#1E1E1E]/40 border-t border-white/5" style={{ clipPath: AGGRESSIVE_CLIP }}>
         <p className="text-[8px] font-bold text-white/30 uppercase italic text-center leading-relaxed">
           * AS PARTIDAS NÃO AGENDADAS ATÉ O PRAZO PODEM SER RESOLVIDAS VIA W.O. PELA ADMINISTRAÇÃO.
         </p>
      </div>
    </div>
  );
};

// --- Match Center View ---

const MatchCenterView = ({ onRegisterResult, onConfirmResult, onOpenSchedule, onOpenProfile }: { onRegisterResult: () => void, onConfirmResult: (match: any) => void, onOpenSchedule: () => void, onOpenProfile: (name: string) => void }) => {
  return (
    <div className="min-h-screen bg-[#121212] pt-16 pb-32">
       <header className="px-6 mb-8 mt-4">
         <h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">MATCH CENTER</h2>
         <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">SÚMULAS E CONFRONTOS</p>
       </header>
       <div className="px-4 space-y-8">
         <section className="space-y-4">
           <h4 className="text-[11px] font-black uppercase text-white/40 italic tracking-[0.2em] px-2">CONFRONTO ATIVO</h4>
           <div className="bg-[#1E1E1E] p-8 border-l-[6px] border-[#FFD700] relative overflow-hidden" style={{ clipPath: AGGRESSIVE_CLIP }}>
             <div className="flex justify-between items-center relative z-10">
               <div className="text-center w-1/3">
                 <div className="w-16 h-16 bg-[#121212] mx-auto mb-2 flex items-center justify-center border-b-2 border-[#FFD700]" style={{ clipPath: SHIELD_CLIP }}>
                   <i className="fas fa-shield text-2xl text-[#FFD700]/20"></i>
                 </div>
                 <p className="text-[9px] font-black italic uppercase text-white">CRUZEIRO EC</p>
               </div>
               <div className="flex flex-col items-center">
                 <span className="text-xs font-black text-[#FFD700] italic">VERSUS</span>
                 <div className="w-8 h-[2px] bg-white/10 my-2"></div>
                 <span className="bg-[#FFD700] text-[#121212] text-[8px] font-black px-2 py-0.5 italic">PENDENTE</span>
               </div>
               <div className="text-center w-1/3" onClick={() => onOpenProfile("REAL MADRID CF")}>
                 <div className="w-16 h-16 bg-[#121212] mx-auto mb-2 flex items-center justify-center border-b-2 border-white/10 cursor-pointer active:scale-95 transition-transform" style={{ clipPath: SHIELD_CLIP }}>
                   <i className="fas fa-shield text-2xl text-white/5"></i>
                 </div>
                 <p className="text-[9px] font-black italic uppercase text-white">REAL MADRID</p>
               </div>
             </div>
             <div className="mt-8 grid grid-cols-2 gap-3">
               <MCOButton onClick={onRegisterResult} className="!py-5 !px-2 !text-[9px]">REGISTRAR RESULTADO</MCOButton>
               <MCOButton variant="outline" onClick={() => alert('REAGENDAMENTO SOLICITADO')} className="!py-5 !px-2 !text-[9px]">REAGENDAR HORÁRIO</MCOButton>
             </div>
           </div>
         </section>
         <section className="space-y-4">
           <h4 className="text-[11px] font-black uppercase text-white/40 italic tracking-[0.2em] px-2">GESTÃO DE AGENDA</h4>
           <MCOCard onClick={onOpenSchedule} className="p-6" active={true} accentColor="#FFD700">
             <div className="flex justify-between items-center">
               <div className="flex items-center gap-5">
                 <div className="w-12 h-12 bg-[#121212] flex items-center justify-center border-b-2 border-[#FFD700]" style={{ clipPath: SHIELD_CLIP }}>
                   <i className="fas fa-calendar-alt text-[#FFD700] text-xl"></i>
                 </div>
                 <div>
                   <h4 className="text-lg font-black italic uppercase font-heading text-white">AGENDAR PARTIDAS</h4>
                   <p className="text-[8px] text-white/30 font-bold uppercase italic mt-1 tracking-widest">4 JOGOS PENDENTES NA SEMANA</p>
                 </div>
               </div>
               <i className="fas fa-chevron-right text-[#FFD700] opacity-30 text-xs"></i>
             </div>
           </MCOCard>
         </section>
         <section className="space-y-4">
           <h4 className="text-[11px] font-black uppercase text-white/40 italic tracking-[0.2em] px-2">SÚMULAS PENDENTES</h4>
           {MOCK_PENDING_MATCHES.map(match => (
             <div 
                key={match.id} 
                onClick={() => onConfirmResult(match)}
                className="bg-[#1E1E1E] p-4 flex justify-between items-center border-r-[3px] border-[#FFD700] cursor-pointer active:opacity-50 transition-opacity" 
                style={{ clipPath: AGGRESSIVE_CLIP }}
             >
                <div>
                  <p className="text-[11px] font-black italic text-white uppercase truncate">VS {match.opponent}</p>
                  <p className="text-[8px] font-bold text-[#FFD700] uppercase italic tracking-widest">{match.competition}</p>
                </div>
                <div className="text-right">
                  <p className="text-[8px] font-black text-white/30 uppercase italic">AGUARDANDO</p>
                  <p className="text-xs font-black italic text-white">CONFIRMAÇÃO</p>
                </div>
             </div>
           ))}
         </section>
         <section className="space-y-4">
           <h4 className="text-[11px] font-black uppercase text-white/40 italic tracking-[0.2em] px-2">RESULTADOS RECENTES</h4>
           <div className="space-y-2">
             {MOCK_RECENT_RESULTS.map(res => (
               <div key={res.id} className="bg-[#1E1E1E] p-4 flex items-center justify-between" style={{ clipPath: "polygon(6px 0, 100% 0, 100% 100%, 0 100%, 0 6px)" }}>
                 <div className="flex-1">
                   <p className="text-[9px] font-black italic text-white/60 uppercase">{res.competition}</p>
                   <p className="text-[11px] font-black italic text-white uppercase">{res.opponent}</p>
                 </div>
                 <div className="bg-[#121212] px-4 py-2 flex items-center gap-3 border-l-2 border-[#FFD700]/30" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>
                   <span className="text-lg font-black italic font-heading text-white">{res.scoreH}</span>
                   <span className="text-[8px] text-white/10 font-black italic">X</span>
                   <span className="text-lg font-black italic font-heading text-white">{res.scoreA}</span>
                 </div>
               </div>
             ))}
           </div>
         </section>
       </div>
    </div>
  );
};

// --- Report Match View ---

const ReportMatchView = ({ onBack, opponentName = "REAL MADRID CF" }: any) => {
  const [homePhoto, setHomePhoto] = useState<string | null>(null);
  const [awayPhoto, setAwayPhoto] = useState<string | null>(null);
  const [uberScore, setUberScore] = useState(0);

  const simulateUpload = (type: 'home' | 'away') => {
    const placeholder = type === 'home' 
      ? "https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&q=80&w=1000" 
      : "https://images.unsplash.com/photo-1518091043644-c1d445bee519?auto=format&fit=crop&q=80&w=1000";
    
    if (type === 'home') setHomePhoto(placeholder);
    else setAwayPhoto(placeholder);
  };

  const handleSubmit = () => {
    if (!homePhoto || !awayPhoto || uberScore === 0) {
      alert("ERRO: COMPLETE TODOS OS CAMPOS E UPLOADS ANTES DE ENVIAR A SÚMULA.");
      return;
    }
    alert("SÚMULA ENVIADA COM SUCESSO! AGUARDANDO PROCESSAMENTO OCR.");
    onBack();
  };

  return (
    <div className="min-h-screen bg-[#121212] pt-8 pb-32 px-6">
      <header className="mb-8">
        <MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40">
          <i className="fas fa-arrow-left mr-2"></i> VOLTAR
        </MCOButton>
        <h2 className="text-4xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">REGISTRO DE SÚMULA</h2>
        <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">VALIDAÇÃO IA/OCR</p>
      </header>
      <div className="space-y-10">
        <section className="space-y-6">
          <h4 className="text-[11px] font-black uppercase text-white/40 italic tracking-[0.2em] border-l-2 border-[#FFD700] pl-2">CAPTURAR ESTATÍSTICAS</h4>
          <div className="grid grid-cols-1 gap-6">
            <div className="space-y-3">
              <p className="text-[9px] font-black uppercase italic text-white/60 tracking-widest text-center">TIME MANDANTE (CRUZEIRO EC)</p>
              <div 
                onClick={() => simulateUpload('home')}
                className="aspect-video bg-[#1E1E1E] border-2 border-dashed border-[#FFD700]/20 flex flex-col items-center justify-center cursor-pointer overflow-hidden relative"
                style={{ clipPath: AGGRESSIVE_CLIP }}
              >
                {homePhoto ? (
                  <img src={homePhoto} className="w-full h-full object-cover grayscale brightness-50" />
                ) : (
                  <>
                    <i className="fas fa-camera text-3xl text-[#FFD700] mb-2"></i>
                    <span className="text-[10px] font-black text-white italic">ENVIAR FOTO</span>
                  </>
                )}
                {homePhoto && (
                  <div className="absolute inset-0 flex items-center justify-center">
                    <span className="bg-[#008000] text-white text-[10px] font-black px-4 py-1 italic shadow-2xl">CARREGADO</span>
                  </div>
                )}
              </div>
            </div>
            <div className="space-y-3">
              <p className="text-[9px] font-black uppercase italic text-white/60 tracking-widest text-center">TIME VISITANTE ({opponentName})</p>
              <div 
                onClick={() => simulateUpload('away')}
                className="aspect-video bg-[#1E1E1E] border-2 border-dashed border-white/5 flex flex-col items-center justify-center cursor-pointer overflow-hidden relative"
                style={{ clipPath: AGGRESSIVE_CLIP }}
              >
                {awayPhoto ? (
                  <img src={awayPhoto} className="w-full h-full object-cover grayscale brightness-50" />
                ) : (
                  <>
                    <i className="fas fa-camera text-3xl text-white/20 mb-2"></i>
                    <span className="text-[10px] font-black text-white italic">ENVIAR FOTO</span>
                  </>
                )}
                {awayPhoto && (
                  <div className="absolute inset-0 flex items-center justify-center">
                    <span className="bg-[#008000] text-white text-[10px] font-black px-4 py-1 italic shadow-2xl">CARREGADO</span>
                  </div>
                )}
              </div>
            </div>
          </div>
        </section>
        <section className="space-y-6">
          <h4 className="text-[11px] font-black uppercase text-white/40 italic tracking-[0.2em] border-l-2 border-[#FFD700] pl-2">UBER SCORE DO OPONENTE</h4>
          <div className="bg-[#1E1E1E] p-8 text-center" style={{ clipPath: AGGRESSIVE_CLIP }}>
            <p className="text-[10px] text-[#FFD700] font-black uppercase italic mb-6 tracking-widest">AVALIE A CONDUTA DE {opponentName}</p>
            <div className="flex justify-center gap-4">
              {[1, 2, 3, 4, 5].map((star) => (
                <button 
                  key={star}
                  onClick={() => setUberScore(star)}
                  className={`text-3xl transition-all ${uberScore >= star ? 'text-[#FFD700] scale-125' : 'text-white/10'}`}
                >
                  <i className={`fas fa-star ${uberScore >= star ? 'drop-shadow-[0_0_8px_#FFD700]' : ''}`}></i>
                </button>
              ))}
            </div>
            <p className="mt-6 text-[8px] font-bold text-white/20 uppercase italic tracking-widest leading-relaxed">
              * AVALIAÇÃO OBRIGATÓRIA PARA MANUTENÇÃO DO FAIR PLAY.
            </p>
          </div>
        </section>
        <MCOButton onClick={handleSubmit} className="w-full py-6 text-lg shadow-[0_10px_20px_rgba(255,215,0,0.2)]">ENVIAR SÚMULA</MCOButton>
      </div>
    </div>
  );
};

// --- Confirm Result View ---

const ConfirmResultView = ({ onBack, match }: any) => {
  const [uberScore, setUberScore] = useState(0);

  const handleConfirm = () => {
    if (uberScore === 0) {
      alert("ERRO: VOCÊ DEVE AVALIAR O OPONENTE ANTES DE CONFIRMAR O RESULTADO.");
      return;
    }
    alert("RESULTADO CONFIRMADO! O LEGADO DO CLUBE FOI ATUALIZADO.");
    onBack();
  };

  const handleDispute = () => {
    alert("DISPUTA ABERTA. UM ADMINISTRADOR DA LIGA IRÁ ANALISAR O CASO.");
    onBack();
  };

  if (!match) return null;

  return (
    <div className="min-h-screen bg-[#121212] pt-8 pb-32 px-6">
      <header className="mb-8">
        <MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40">
          <i className="fas fa-arrow-left mr-2"></i> VOLTAR
        </MCOButton>
        <h2 className="text-4xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">CONFIRMAR RESULTADO</h2>
        <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">VALIDAÇÃO DO OPONENTE</p>
      </header>
      <div className="space-y-8">
        <div className="bg-[#1E1E1E] p-10 relative overflow-hidden text-center" style={{ clipPath: AGGRESSIVE_CLIP }}>
           <div className="absolute top-0 left-0 w-full h-1 bg-[#FFD700]/30"></div>
           <p className="text-[9px] font-black italic text-[#FFD700] uppercase tracking-[0.4em] mb-6">PLACAR ENVIADO POR {match.opponent}</p>
           <div className="flex justify-center items-center gap-8">
             <div className="text-center">
               <div className="w-12 h-12 bg-[#121212] flex items-center justify-center mx-auto mb-2 border-b-2 border-[#FFD700]" style={{ clipPath: SHIELD_CLIP }}>
                 <i className="fas fa-shield text-xl text-[#FFD700]/20"></i>
               </div>
               <p className="text-[9px] font-black italic text-white uppercase">CRUZEIRO</p>
             </div>
             <div className="flex items-center gap-4">
                <span className="text-5xl font-black italic font-heading text-white">{match.reportedScoreH}</span>
                <span className="text-sm font-black italic text-[#FFD700]/20">X</span>
                <span className="text-5xl font-black italic font-heading text-white">{match.reportedScoreA}</span>
             </div>
             <div className="text-center">
               <div className="w-12 h-12 bg-[#121212] flex items-center justify-center mx-auto mb-2 border-b-2 border-white/5" style={{ clipPath: SHIELD_CLIP }}>
                 <i className="fas fa-shield text-xl text-white/5"></i>
               </div>
               <p className="text-[9px] font-black italic text-white uppercase truncate max-w-[60px]">{match.opponent}</p>
             </div>
           </div>
           <div className="mt-8 pt-8 border-t border-white/5">
             <p className="text-[11px] font-black italic text-white/40 uppercase mb-4">AVALIE A CONDUTA DO ADVERSÁRIO</p>
             <div className="flex justify-center gap-3">
                {[1, 2, 3, 4, 5].map((star) => (
                  <button 
                    key={star}
                    onClick={() => setUberScore(star)}
                    className={`text-2xl transition-all ${uberScore >= star ? 'text-[#FFD700]' : 'text-white/10'}`}
                  >
                    <i className={`fas fa-star ${uberScore >= star ? 'drop-shadow-[0_0_8px_#FFD700]' : ''}`}></i>
                  </button>
                ))}
             </div>
           </div>
        </div>
        <div className="space-y-3">
          <MCOButton onClick={handleConfirm} className="w-full py-6 text-lg">CONFIRMAR RESULTADO</MCOButton>
          <MCOButton variant="outline" onClick={handleDispute} className="w-full py-5 !text-[10px] text-[#B22222] border-[#B22222]/30">DISCORDAR / ABRIR DISPUTA</MCOButton>
        </div>
        <p className="text-[8px] font-bold text-white/20 uppercase italic tracking-widest text-center px-4 leading-relaxed">
           * AO CONFIRMAR, VOCÊ CONCORDA QUE O PLACAR ACIMA É VERÍDICO E QUE O JOGO OCORREU DENTRO DAS REGRAS.
        </p>
      </div>
    </div>
  );
};

// --- Widgets ---

const FanProgressWidget = ({ fans }: { fans: number }) => {
  const tiers = [
    { name: 'PEQUENO', min: 0, max: 500000 },
    { name: 'EMERGENTE', min: 500000, max: 2000000 },
    { name: 'TRADICIONAL', min: 2000000, max: 5000000 },
    { name: 'GIGANTE', min: 5000000, max: 10000000 },
    { name: 'DINASTIA', min: 10000000, max: 25000000 },
  ];
  const currentTier = tiers.find(t => fans >= t.min && fans < t.max) || tiers[tiers.length - 1];
  const progress = Math.min(((fans - currentTier.min) / (currentTier.max - currentTier.min)) * 100, 100);
  const formatFans = (n: number) => n >= 1000000 ? (n / 1000000).toFixed(1) + 'M' : n >= 1000 ? (n / 1000).toFixed(0) + 'K' : n.toString();

  return (
    <div className="bg-[#1E1E1E] p-6 border-r-[3px] border-[#FFD700] mb-8" style={{ clipPath: AGGRESSIVE_CLIP }}>
      <div className="flex justify-between items-end mb-4">
        <div>
          <p className="text-[9px] text-[#FFD700] font-black uppercase italic tracking-[0.3em]">CLASSIFICAÇÃO DO CLUBE</p>
          <h3 className="text-3xl font-black italic uppercase font-heading text-white">{currentTier.name}</h3>
        </div>
        <div className="text-right">
          <p className="text-[9px] text-white/30 font-black uppercase italic tracking-widest">TORCIDA TOTAL</p>
          <p className="text-xl font-black italic font-heading text-white">{formatFans(fans)}</p>
        </div>
      </div>
      <div className="relative h-4 bg-[#121212] overflow-hidden" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>
        <div className="absolute top-0 left-0 h-full bg-[#FFD700] transition-all duration-1000 shadow-[0_0_15px_rgba(255,215,0,0.5)]" style={{ width: `${progress}%` }}></div>
      </div>
    </div>
  );
};

// --- Player Detail Components ---

const LegacyUTCard = ({ player }: { player: any }) => {
  const statsEntries = Object.entries(player.stats || {});
  return (
    <div className="relative w-full max-w-[280px] mx-auto aspect-[1/1.5] group select-none">
      <div className="absolute inset-0 bg-[#FFD700] opacity-10 blur-3xl animate-pulse"></div>
      <div className="relative w-full h-full bg-[#1E1E1E] border-[3px] border-[#FFD700] overflow-hidden shadow-2xl transition-transform duration-500" style={{ clipPath: SHIELD_CLIP }}>
        <div className="absolute inset-0 opacity-5 pointer-events-none"><div className="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_center,_#FFD700_1px,_transparent_1px)] bg-[size:20px_20px]"></div></div>
        <div className="relative h-1/2 flex pt-6 px-4">
          <div className="z-20 shrink-0 flex flex-col items-center">
            <span className="text-5xl font-black italic font-heading leading-none text-[#FFD700] drop-shadow-[0_2px_4px_rgba(0,0,0,1)]">{player.ovr}</span>
            <span className="text-xl font-black italic font-heading uppercase tracking-tighter text-white opacity-80">{player.pos}</span>
            <i className="fa-brands fa-playstation mt-4 text-[#FFD700] text-xl opacity-40"></i>
          </div>
          <div className="absolute right-0 top-0 bottom-0 w-3/4 overflow-hidden">
            <img src={player.photo} className="w-full h-full object-cover object-top filter contrast-125 saturate-110 drop-shadow-[-10px_0_15px_rgba(0,0,0,0.8)]" alt={player.name} />
          </div>
        </div>
        <div className="relative z-10 bg-gradient-to-r from-transparent via-[#FFD700] to-transparent py-1 my-2">
           <h3 className="text-xl font-black italic uppercase font-heading text-center text-[#121212] tracking-tighter whitespace-nowrap overflow-hidden px-2">{player.name}</h3>
        </div>
        <div className="grid grid-cols-2 gap-x-0 px-4 pt-4">
          <div className="flex flex-col gap-2 border-r border-[#FFD700]/10 pr-2">
            {statsEntries.slice(0, 3).map(([stat, val]) => (
              <div key={stat} className="flex items-center gap-3"><span className="text-lg font-black italic font-heading text-white">{val as number}</span><span className="text-[10px] font-black italic uppercase text-white/30 tracking-widest">{stat}</span></div>
            ))}
          </div>
          <div className="flex flex-col gap-2 pl-4">
            {statsEntries.slice(3, 6).map(([stat, val]) => (
              <div key={stat} className="flex items-center gap-3"><span className="text-lg font-black italic font-heading text-white">{val as number}</span><span className="text-[10px] font-black italic uppercase text-white/30 tracking-widest">{stat}</span></div>
            ))}
          </div>
        </div>
        <div className="absolute bottom-10 left-0 right-0 flex justify-center gap-2 px-6">
          {(player.playstyles || []).slice(0, 4).map((ps: string, idx: number) => (
             <div key={idx} className="w-5 h-5 bg-[#FFD700]/5 border border-[#FFD700]/10 flex items-center justify-center transform rotate-45"><i className="fas fa-bolt text-[8px] text-[#FFD700] transform -rotate-45"></i></div>
          ))}
        </div>
      </div>
    </div>
  );
};

const DetailedAttributes = ({ player }: { player: any }) => {
  const sections = player.detailedStats ? Object.entries(player.detailedStats) : [];
  return (
    <div className="w-full bg-[#1E1E1E] p-6 overflow-y-auto max-h-[70vh] space-y-8 relative" style={{ clipPath: AGGRESSIVE_CLIP }}>
      <div className="absolute inset-0 opacity-40 pointer-events-none" style={{ backgroundImage: SLANTED_PATTERN }}></div>
      <div className="relative z-10 space-y-6">
        <div className="flex items-center gap-4 border-b-2 border-[#FFD700] pb-4">
          <div className="w-12 h-12 bg-[#FFD700] text-[#121212] flex items-center justify-center font-black italic font-heading text-2xl" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>{player.ovr}</div>
          <div className="flex-grow">
            <p className="text-2xl font-black italic uppercase font-heading text-white leading-none">{player.name}</p>
            <p className="text-[10px] font-bold text-[#FFD700] uppercase italic tracking-[0.2em]">{player.pos} • {player.age || '??'} ANOS</p>
          </div>
        </div>
        
        <div className="grid grid-cols-2 gap-4">
           <div className="bg-[#121212] p-4 border-l-[3px] border-[#FFD700]/30" style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}>
             <p className="text-[8px] font-black text-white/30 uppercase italic mb-1 tracking-widest">SALÁRIO MENSAL</p>
             <p className="text-xl font-black italic font-heading text-white">M$ {player.salary || '0'}M</p>
           </div>
           <div className="bg-[#121212] p-4 border-r-[3px] border-[#FFD700]/30 text-right" style={{ clipPath: "polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 0 100%)" }}>
             <p className="text-[8px] font-black text-white/30 uppercase italic mb-1 tracking-widest">VALOR DE MERCADO</p>
             <p className="text-xl font-black italic font-heading text-[#FFD700]">M$ {player.marketValue || player.value}M</p>
           </div>
        </div>

        <div className="flex justify-between items-center bg-[#1E1E1E] border border-white/5 p-4" style={{ clipPath: AGGRESSIVE_CLIP }}>
           <div className="flex flex-col items-center">
             <span className="text-[9px] font-black text-white/40 uppercase italic mb-1 tracking-widest">SKILL MOVES</span>
             <div className="flex gap-1 text-[#FFD700]">
               {[...Array(5)].map((_, i) => <i key={i} className={`fas fa-star text-xs ${i < (player.skillMoves || 0) ? '' : 'opacity-10'}`}></i>)}
             </div>
           </div>
           <div className="w-[1px] h-8 bg-white/10"></div>
           <div className="flex flex-col items-center">
             <span className="text-[9px] font-black text-white/40 uppercase italic mb-1 tracking-widest">PERNA RUIM</span>
             <div className="flex gap-1 text-[#FFD700]">
               {[...Array(5)].map((_, i) => <i key={i} className={`fas fa-star text-xs ${i < (player.weakFoot || 0) ? '' : 'opacity-10'}`}></i>)}
             </div>
           </div>
        </div>

        <div className="space-y-4">
           <h4 className="text-[11px] font-black uppercase text-[#FFD700] italic tracking-[0.2em] border-l-2 border-[#FFD700] pl-2">PLAYSTYLES</h4>
           <div className="grid grid-cols-2 gap-2">
             {(player.playstyles || []).map((ps: string) => (
               <div key={ps} className="bg-[#121212] p-3 flex items-center gap-2" style={{ clipPath: "polygon(6px 0, 100% 0, 100% 100%, 0 100%, 0 6px)" }}>
                 <i className="fas fa-bolt text-[10px] text-[#FFD700]"></i>
                 <span className="text-[9px] font-black text-white uppercase italic tracking-tighter">{ps}</span>
               </div>
             ))}
           </div>
        </div>

        {sections.map(([title, stats]: [string, any]) => (
          <div key={title} className="space-y-4">
            <h4 className="text-[11px] font-black uppercase text-[#FFD700] italic tracking-[0.2em]">{title}</h4>
            <div className="grid grid-cols-1 gap-3">
              {Object.entries(stats).map(([label, value]: [string, any]) => (
                <div key={label} className="flex items-center justify-between group">
                  <span className="text-[10px] font-bold uppercase italic text-white/40">{label}</span>
                  <div className="flex items-center gap-4 flex-grow ml-6">
                    <div className="flex-grow h-[4px] bg-white/5 relative overflow-hidden" style={{ clipPath: "polygon(2px 0, 100% 0, 100% 100%, 0 100%, 0 2px)" }}>
                      <div className={`absolute h-full transition-all duration-1000 ${value >= 85 ? 'bg-[#FFD700]' : 'bg-white/40'}`} style={{ width: `${value}%` }}></div>
                    </div>
                    <span className={`text-xs font-black italic font-heading w-6 text-right ${value >= 85 ? 'text-[#FFD700]' : 'text-white'}`}>{value}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

// --- Views: Club Subs ---

const SquadView = ({ onBack }: any) => {
  const [selectedPlayer, setSelectedPlayer] = useState<any>(null);
  const [showDetailed, setShowDetailed] = useState(false);
  const closePlayer = () => { setSelectedPlayer(null); setShowDetailed(false); };
  return (
    <div className="min-h-screen bg-[#121212] p-6 pb-32">
      <header className="mb-10"><MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40"><i className="fas fa-arrow-left mr-2"></i> VOLTAR</MCOButton><h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">MEU ELENCO</h2></header>
      <div className="space-y-4">
        {MOCK_SQUAD.map((player) => (
          <div key={player.id} className="flex items-center gap-4 bg-[#1E1E1E] p-4 border-r-[3px] border-[#FFD700] cursor-pointer" onClick={() => setSelectedPlayer(player)} style={{ clipPath: AGGRESSIVE_CLIP }}>
            <img src={player.photo} className="w-12 h-12 object-cover" style={{ clipPath: "polygon(6px 0, 100% 0, 100% 100%, 0 100%, 0 6px)" }} />
            <div className="flex-grow">
              <p className="text-lg font-black italic font-heading text-white uppercase">{player.name}</p>
              <p className="text-[10px] text-[#FFD700] font-bold uppercase italic">{player.pos} • OVR {player.ovr}</p>
            </div>
          </div>
        ))}
      </div>
      {selectedPlayer && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/90 backdrop-blur-sm">
          <div className="absolute inset-0" onClick={closePlayer}></div>
          <div className="relative w-full max-w-sm flex flex-col items-center">
            <div className="w-full flex justify-end mb-4"><button onClick={closePlayer} className="bg-[#1E1E1E] text-[#FFD700] w-12 h-12 flex items-center justify-center border-b-[3px] border-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}><i className="fas fa-times text-xl"></i></button></div>
            {showDetailed ? <DetailedAttributes player={selectedPlayer} /> : <LegacyUTCard player={selectedPlayer} />}
            <div className="mt-8 w-full"><MCOButton variant={showDetailed ? "primary" : "outline"} className="w-full py-5" onClick={() => setShowDetailed(!showDetailed)}>{showDetailed ? "VER CARD ULTIMATE" : "FICHA TÉCNICA COMPLETA"}</MCOButton></div>
          </div>
        </div>
      )}
    </div>
  );
};

const AchievementsView = ({ onBack, userStats }: any) => {
  const achievementGroups = [
    { id: 'goals', title: 'ARTILHEIRO NATO', icon: 'fa-futbol', current: userStats.goals, milestones: [50, 100, 500, 1000] },
    { id: 'assists', title: 'MESTRE DO PASSE', icon: 'fa-hand-holding-heart', current: userStats.assists, milestones: [25, 50, 150, 300] },
    { id: 'matches', title: 'LEGADO EM CAMPO', icon: 'fa-stopwatch', current: userStats.wins + 40, milestones: [20, 50, 100, 200] }
  ];
  return (
    <div className="min-h-screen bg-[#121212] p-6 pb-32">
      <header className="mb-10"><MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40"><i className="fas fa-arrow-left mr-2"></i> VOLTAR</MCOButton><h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">CONQUISTAS</h2></header>
      <div className="space-y-8">
        {achievementGroups.map((group) => (
          <div key={group.id} className="bg-[#1E1E1E] p-8 border-l-[4px] border-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}>
            <div className="flex justify-between items-end mb-6"><div><h4 className="text-[10px] font-black uppercase text-[#FFD700] italic mb-1 tracking-widest">{group.title}</h4><p className="text-xl font-black italic uppercase font-heading text-white">{group.current} REGISTRADOS</p></div><i className={`fas ${group.icon} text-white/5 text-3xl`}></i></div>
            <div className="flex justify-between items-center gap-2">
              {group.milestones.map((m, idx) => {
                const isAchieved = group.current >= m;
                return (
                  <div key={m} className="flex-1 flex flex-col items-center gap-2">
                    <div className={`w-full aspect-square flex items-center justify-center transition-all ${isAchieved ? 'bg-[#FFD700] text-[#121212] shadow-[0_0_10px_#FFD700]' : 'bg-[#121212] text-white/10'}`} style={{ clipPath: "polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%)" }}><i className={`fas ${idx === 3 ? 'fa-crown' : 'fa-trophy'} text-xs`}></i></div>
                    <span className={`text-[8px] font-black italic ${isAchieved ? 'text-[#FFD700]' : 'text-white/20'}`}>{m}</span>
                  </div>
                );
              })}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

const FinanceView = ({ onBack, userStats }: any) => {
  const totalBalance = 1105;
  const salaryCost = 145; 
  const investmentPower = totalBalance - salaryCost;
  const sponsorTiers = [
    { id: 'p', name: 'PEQUENO', value: 30, reqFans: 0, icon: 'fa-store' },
    { id: 'e', name: 'EMERGENTE', value: 50, reqFans: 500000, icon: 'fa-plane-departure' },
    { id: 't', name: 'TRADICIONAL', value: 100, reqFans: 2000000, icon: 'fa-globe-americas' },
    { id: 'g', name: 'GIGANTE', value: 200, reqFans: 5000000, icon: 'fa-landmark' }
  ];
  return (
    <div className="min-h-screen bg-[#121212] p-6 pb-32 overflow-y-auto">
      <header className="mb-10"><MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40"><i className="fas fa-arrow-left mr-2"></i> VOLTAR</MCOButton><h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">FINANCEIRO</h2></header>
      <div className="space-y-10">
        <div className="space-y-4">
          <div className="bg-[#1E1E1E] p-8 border-r-[3px] border-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}>
            <p className="text-[9px] text-[#FFD700] font-black uppercase italic mb-1 tracking-widest">SALDO EM CAIXA</p>
            <p className="text-4xl font-black italic font-heading text-white">M$ {totalBalance}M</p>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="bg-[#1E1E1E] p-5 border-l-[3px] border-[#B22222]" style={{ clipPath: AGGRESSIVE_CLIP }}><p className="text-[8px] text-[#B22222] font-black uppercase italic mb-1 tracking-widest">RESERVA SALARIAL</p><p className="text-xl font-black italic font-heading text-white">M$ {salaryCost}M</p></div>
            <div className="bg-[#1E1E1E] p-5 border-l-[3px] border-[#008000]" style={{ clipPath: AGGRESSIVE_CLIP }}><p className="text-[8px] text-[#008000] font-black uppercase italic mb-1 tracking-widest">PODER DE INV.</p><p className="text-xl font-black italic font-heading text-white">M$ {investmentPower}M</p></div>
          </div>
        </div>
        <div className="space-y-6">
          <h4 className="text-[11px] font-black uppercase text-[#FFD700] italic tracking-[0.2em] border-l-2 border-[#FFD700] pl-2">RECEITAS DE PATROCÍNIO</h4>
          <div className="grid grid-cols-2 gap-4">
            {sponsorTiers.map((tier) => {
              const isAchieved = userStats.fans >= tier.reqFans;
              return (
                <div key={tier.id} className={`p-6 text-center transition-all ${isAchieved ? 'bg-[#1E1E1E] border-b-[3px] border-[#FFD700]' : 'bg-[#181818] opacity-20'}`} style={{ clipPath: AGGRESSIVE_CLIP }}>
                  <i className={`fas ${tier.icon} text-3xl mb-4 ${isAchieved ? 'text-[#FFD700]' : 'text-white/10'}`}></i>
                  <p className="text-[9px] font-black italic uppercase text-white tracking-widest leading-none">{tier.name}</p>
                  <p className="text-lg font-black italic font-heading text-[#FFD700] mt-1">M$ {tier.value}M</p>
                </div>
              );
            })}
          </div>
        </div>
        <div className="space-y-6">
          <h4 className="text-[11px] font-black uppercase text-[#FFD700] italic tracking-[0.2em] border-l-2 border-[#FFD700] pl-2">RECEITAS DE PREMIAÇÃO</h4>
          <div className="space-y-3">
            {userStats.prizeHistory.map((prize: any, idx: number) => (
              <div key={idx} className="bg-[#181818] p-5 flex justify-between items-center border-r-[2px] border-[#FFD700]/30" style={{ clipPath: "polygon(10px 0, 100% 0, 100% 100%, 0 100%, 0 10px)" }}>
                <div><p className="text-[10px] font-black italic text-white uppercase tracking-tighter">{prize.name}</p><p className="text-[8px] font-bold text-white/20 uppercase italic tracking-widest">{prize.season}</p></div>
                <p className="text-xl font-black italic font-heading text-[#FFD700]">M$ {prize.value}M</p>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

const TrophiesView = ({ onBack, userStats }: any) => {
  const trophyGallery = [
    { id: 'elite-23', name: 'ELITE DIVISION', season: 'TEMP 23', icon: 'fa-shield-halved', category: 'LIGA NACIONAL' },
    { id: 'champions-22', name: 'CHAMPIONS CUP', season: 'TEMP 22', icon: 'fa-trophy', category: 'COPA CONTINENTAL' },
    { id: 'g4-24', name: 'COPA G4', season: 'TEMP 24', icon: 'fa-crown', category: 'META-GAME ELITE' }
  ];
  return (
    <div className="min-h-screen bg-[#121212] p-6 pb-32">
      <header className="mb-10"><MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40"><i className="fas fa-arrow-left mr-2"></i> VOLTAR</MCOButton><h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">TROFÉUS</h2></header>
      <div className="grid grid-cols-1 gap-6">
        {trophyGallery.map((t) => (
          <div key={t.id} className="relative p-8 text-center bg-[#1E1E1E] border-b-[3px] border-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}>
             <p className="text-[9px] text-[#FFD700]/40 font-black uppercase italic mb-4 tracking-widest leading-none">{t.category}</p>
             <i className={`fas ${t.icon} text-6xl text-[#FFD700] mb-6 drop-shadow-[0_0_15px_#FFD700]`}></i>
             <p className="text-xl font-black italic uppercase font-heading text-white leading-tight">{t.name}</p>
             <div className="mt-4"><span className="text-[10px] font-black bg-[#FFD700] text-[#121212] px-4 py-1 italic tracking-widest">{t.season}</span></div>
          </div>
        ))}
      </div>
    </div>
  );
};

// --- Tournament Sub Views ---

const LeagueTableView = ({ onBack, onOpenClub }: { onBack: () => void, onOpenClub: (name: string) => void }) => {
  return (
    <div className="min-h-screen bg-[#121212] p-6 pb-32 flex flex-col">
      <header className="mb-8">
        <MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40"><i className="fas fa-arrow-left mr-2"></i> VOLTAR</MCOButton>
        <h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">LIGA NACIONAL</h2>
        <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">TABELA DE CLASSIFICAÇÃO</p>
      </header>
      <div className="flex-grow overflow-y-auto">
        <div className="min-w-full">
           <div className="grid grid-cols-[30px_1fr_40px_40px_50px] gap-2 px-4 py-3 bg-[#1E1E1E] border-b-[2px] border-[#FFD700]/30 mb-4" style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}>
              <span className="text-[8px] font-black text-white/40 italic uppercase">POS</span>
              <span className="text-[8px] font-black text-white/40 italic uppercase">CLUBE</span>
              <span className="text-[8px] font-black text-white/40 italic uppercase text-center">P</span>
              <span className="text-[8px] font-black text-white/40 italic uppercase text-center">V</span>
              <span className="text-[8px] font-black text-[#FFD700] italic uppercase text-right">PTS</span>
           </div>
           <div className="space-y-1">
              {MOCK_LEAGUE_TABLE.map((row) => (
                <div 
                  key={row.pos} 
                  onClick={() => onOpenClub(row.club)}
                  className={`grid grid-cols-[30px_1fr_40px_40px_50px] gap-2 px-4 py-4 items-center transition-all cursor-pointer active:scale-95 ${row.isUser ? 'bg-[#FFD700] text-[#121212]' : 'bg-[#1E1E1E] text-white'}`} 
                  style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}
                >
                  <span className="text-[10px] font-black italic font-heading">{row.pos}º</span>
                  <span className={`text-[11px] font-black italic uppercase truncate`}>{row.club}</span>
                  <span className="text-[10px] font-black italic font-heading text-center opacity-60">{row.p}</span>
                  <span className="text-[10px] font-black italic font-heading text-center opacity-60">{row.v}</span>
                  <span className="text-xs font-black italic font-heading text-right">{row.pts}</span>
                </div>
              ))}
           </div>
        </div>
      </div>
    </div>
  );
};

const LeagueCupView = ({ onBack, onOpenClub }: { onBack: () => void, onOpenClub: (name: string) => void }) => {
  const currentStage = MOCK_CUP_BRACKET[0];
  return (
    <div className="min-h-screen bg-[#121212] p-6 pb-32 flex flex-col">
      <header className="mb-8"><MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40"><i className="fas fa-arrow-left mr-2"></i> VOLTAR</MCOButton><h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">COPA DA LIGA</h2><p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">{currentStage.stage}</p></header>
      <div className="space-y-10">
        {currentStage.matches.filter(m => m.isUser).map((match, idx) => (
          <div key={idx} className="bg-[#1E1E1E] p-8 border-l-[4px] border-[#FFD700] relative overflow-hidden" style={{ clipPath: AGGRESSIVE_CLIP }}>
            <div className="flex justify-between items-center mb-8 relative z-10">
              <div className="text-center w-1/3 cursor-pointer active:opacity-60" onClick={() => onOpenClub(match.home)}><div className="w-16 h-16 bg-[#121212] mx-auto mb-3 flex items-center justify-center border-b-2 border-[#FFD700]" style={{ clipPath: SHIELD_CLIP }}><i className="fas fa-shield text-2xl text-[#FFD700]/40"></i></div><p className="text-[10px] font-black italic uppercase text-white tracking-tighter">{match.home}</p></div>
              <div className="flex flex-col items-center"><div className="flex items-center gap-4"><span className="text-4xl font-black italic font-heading text-white">{match.scoreH}</span><span className="text-xs font-black text-[#FFD700] opacity-50">X</span><span className="text-4xl font-black italic font-heading text-white">{match.scoreA}</span></div><span className="bg-[#FFD700] text-[#121212] text-[8px] font-black px-2 py-0.5 mt-2 italic">FINALIZADO</span></div>
              <div className="text-center w-1/3 cursor-pointer active:opacity-60" onClick={() => onOpenClub(match.away)}><div className="w-16 h-16 bg-[#121212] mx-auto mb-3 flex items-center justify-center border-b-2 border-white/10" style={{ clipPath: SHIELD_CLIP }}><i className="fas fa-shield text-2xl text-white/10"></i></div><p className="text-[10px] font-black italic uppercase text-white tracking-tighter">{match.away}</p></div>
            </div>
            {match.pensH !== undefined && (
              <div className="bg-[#121212]/50 p-5 mt-4 border border-white/5 relative z-10" style={{ clipPath: "polygon(10px 0, 100% 0, 100% 100%, 0 100%, 0 10px)" }}>
                 <p className="text-[9px] font-black italic text-[#FFD700] uppercase text-center mb-4 tracking-[0.2em]">DISPUTA DE PÊNALTIS</p>
                 <div className="flex justify-between items-center px-4">
                    <div className="flex gap-2">{[...Array(5)].map((_, i) => (<div key={i} className={`w-3 h-3 rotate-45 border ${i < (match.pensH || 0) ? 'bg-[#FFD700] border-[#FFD700]' : 'border-white/10'}`}></div>))}</div>
                    <div className="flex flex-col items-center"><span className="text-2xl font-black italic font-heading text-[#FFD700] leading-none">{match.pensH}</span><span className="text-[8px] text-white/20 font-black italic">VS</span><span className="text-2xl font-black italic font-heading text-white leading-none">{match.pensA}</span></div>
                    <div className="flex gap-2">{[...Array(5)].map((_, i) => (<div key={i} className={`w-3 h-3 rotate-45 border ${i < (match.pensA || 0) ? 'bg-white/40 border-white/40' : 'border-white/10'}`}></div>))}</div>
                 </div>
              </div>
            )}
          </div>
        ))}
        <div className="space-y-4">
           <h4 className="text-[11px] font-black uppercase text-white/40 italic tracking-[0.2em] px-2">OUTROS CONFRONTOS</h4>
           <div className="grid grid-cols-1 gap-2">
              {currentStage.matches.filter(m => !m.isUser).map((match, idx) => (
                <div key={idx} className="bg-[#1E1E1E] p-4 flex justify-between items-center" style={{ clipPath: "polygon(6px 0, 100% 0, 100% 100%, 0 100%, 0 6px)" }}>
                  {/* FIXED: changed msg.home to match.home to fix 'Cannot find name msg' error */}
                  <div className="flex-1 text-left cursor-pointer" onClick={() => onOpenClub(match.home)}><span className="text-[10px] font-black uppercase italic text-white/60">{match.home}</span></div>
                  <div className="flex items-center gap-3 px-4 bg-[#121212] py-1" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}><span className="text-xs font-black italic font-heading text-white">{match.scoreH}</span><span className="text-[8px] text-white/10 font-black italic">X</span><span className="text-xs font-black italic font-heading text-white">{match.scoreA}</span></div>
                  <div className="flex-1 text-right cursor-pointer" onClick={() => onOpenClub(match.away)}><span className="text-[10px] font-black uppercase italic text-white/60">{match.away}</span></div>
                </div>
              ))}
           </div>
        </div>
      </div>
    </div>
  );
};

const ContinentalTournamentView = ({ onBack, onOpenClub }: { onBack: () => void, onOpenClub: (name: string) => void }) => {
  const [activeTab, setActiveTab] = useState<'groups' | 'knockout'>('groups');
  const bracketStage = MOCK_CONTINENTAL_BRACKET[0];

  return (
    <div className="min-h-screen bg-[#121212] p-6 pb-32 flex flex-col">
      <header className="mb-8">
        <MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40"><i className="fas fa-arrow-left mr-2"></i> VOLTAR</MCOButton>
        <h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">TORNEIO CONTINENTAL</h2>
        <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">META-GAME CHAMPIONS</p>
      </header>
      <div className="flex gap-2 mb-8 bg-[#1E1E1E] p-1" style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}>
        <button 
          onClick={() => setActiveTab('groups')}
          className={`flex-1 py-3 text-[10px] font-black italic uppercase transition-all ${activeTab === 'groups' ? 'bg-[#FFD700] text-[#121212]' : 'text-white/40'}`}
          style={{ clipPath: "polygon(6px 0, 100% 0, 100% 100%, 0 100%, 0 6px)" }}
        >
          FASE DE GRUPOS
        </button>
        <button 
          onClick={() => setActiveTab('knockout')}
          className={`flex-1 py-3 text-[10px] font-black italic uppercase transition-all ${activeTab === 'knockout' ? 'bg-[#FFD700] text-[#121212]' : 'text-white/40'}`}
          style={{ clipPath: "polygon(6px 0, 100% 0, 100% 100%, 0 100%, 0 6px)" }}
        >
          MATA-MATA
        </button>
      </div>
      <div className="flex-grow overflow-y-auto space-y-10">
        {activeTab === 'groups' ? (
          Object.entries(MOCK_CONTINENTAL_GROUPS).map(([groupName, teams]) => (
            <div key={groupName} className="space-y-4">
              <h3 className="text-[11px] font-black uppercase text-[#FFD700] italic tracking-[0.2em] border-l-2 border-[#FFD700] pl-2">{groupName}</h3>
              <div className="min-w-full">
                <div className="grid grid-cols-[30px_1fr_40px_40px_50px] gap-2 px-4 py-3 bg-[#1E1E1E] border-b-[2px] border-[#FFD700]/30 mb-2" style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}>
                   <span className="text-[8px] font-black text-white/40 italic uppercase">POS</span>
                   <span className="text-[8px] font-black text-white/40 italic uppercase">CLUBE</span>
                   <span className="text-[8px] font-black text-white/40 italic uppercase text-center">P</span>
                   <span className="text-[8px] font-black text-white/40 italic uppercase text-center">V</span>
                   <span className="text-[8px] font-black text-[#FFD700] italic uppercase text-right">PTS</span>
                </div>
                <div className="space-y-1">
                  {teams.map((row) => (
                    <div key={row.club} onClick={() => onOpenClub(row.club)} className={`grid grid-cols-[30px_1fr_40px_40px_50px] gap-2 px-4 py-3 items-center transition-all cursor-pointer active:opacity-70 ${row.isUser ? 'bg-[#FFD700] text-[#121212]' : (row.pos <= 2 ? 'bg-[#1E1E1E] border-l-[3px] border-[#008000]' : 'bg-[#1E1E1E] border-l-[3px] border-transparent opacity-60')}`} style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>
                      <span className="text-[9px] font-black italic font-heading">{row.pos}º</span>
                      <span className="text-[10px] font-black italic uppercase truncate">{row.club}</span>
                      <span className="text-[9px] font-black italic text-center opacity-40">{row.p}</span>
                      <span className="text-[9px] font-black italic text-center opacity-40">{row.v}</span>
                      <span className="text-xs font-black italic text-right">{row.pts}</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          ))
        ) : (
          <div className="space-y-8">
            <h3 className="text-[11px] font-black uppercase text-[#FFD700] italic tracking-[0.2em] text-center">{bracketStage.stage}</h3>
            <div className="space-y-6">
              {bracketStage.matches.map((match, idx) => (
                <div key={idx} className={`bg-[#1E1E1E] p-6 relative overflow-hidden ${match.isUser ? 'border-l-[4px] border-[#FFD700]' : ''}`} style={{ clipPath: AGGRESSIVE_CLIP }}>
                  <div className="flex justify-between items-center">
                    <div className="flex-1 text-left cursor-pointer" onClick={() => onOpenClub(match.home)}>
                       <p className={`text-[10px] font-black uppercase italic ${match.isUser ? 'text-[#FFD700]' : 'text-white'}`}>{match.home}</p>
                    </div>
                    <div className="flex items-center gap-4 px-6">
                       <span className="text-2xl font-black italic font-heading text-white">{match.scoreH}</span>
                       <span className="text-[8px] font-black text-white/10 italic">X</span>
                       <span className="text-2xl font-black italic font-heading text-white">{match.scoreA}</span>
                    </div>
                    <div className="flex-1 text-right cursor-pointer" onClick={() => onOpenClub(match.away)}>
                       <p className="text-[10px] font-black uppercase italic text-white">{match.away}</p>
                    </div>
                  </div>
                  {match.pensH !== undefined && (
                    <div className="mt-4 pt-4 border-t border-white/5 flex justify-center gap-6">
                       <span className="text-[9px] font-black italic text-[#FFD700] uppercase">PK {match.pensH}</span>
                       <span className="text-[9px] font-black italic text-white/20 uppercase">VS</span>
                       <span className="text-[9px] font-black italic text-white/40 uppercase">PK {match.pensA}</span>
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

// --- Profile Components (Widgets) ---

const ProfileUserWidget = ({ data, onChange }: any) => (
  <div className="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
    <div className="space-y-4">
      <div className="space-y-2">
        <label className="text-[9px] font-black text-white/30 uppercase italic tracking-widest">NOME COMPLETO</label>
        <input 
          type="text" 
          value={data.name}
          onChange={(e) => onChange('name', e.target.value)}
          className="w-full bg-[#1E1E1E] border-none text-white p-4 font-black italic uppercase text-sm outline-none focus:ring-1 focus:ring-[#FFD700]"
          style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}
        />
      </div>
      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <label className="text-[9px] font-black text-white/30 uppercase italic tracking-widest">TELEFONE</label>
          <input 
            type="text" 
            value={data.phone}
            onChange={(e) => onChange('phone', e.target.value)}
            className="w-full bg-[#1E1E1E] border-none text-white p-4 font-black italic uppercase text-sm outline-none focus:ring-1 focus:ring-[#FFD700]"
            style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}
          />
        </div>
        <div className="space-y-2">
          <label className="text-[9px] font-black text-white/30 uppercase italic tracking-widest">GAMERTAG</label>
          <input 
            type="text" 
            value={data.gamertag}
            onChange={(e) => onChange('gamertag', e.target.value)}
            className="w-full bg-[#1E1E1E] border-none text-[#FFD700] p-4 font-black italic uppercase text-sm outline-none focus:ring-1 focus:ring-[#FFD700]"
            style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}
          />
        </div>
      </div>
      <div className="space-y-2">
        <label className="text-[9px] font-black text-white/30 uppercase italic tracking-widest">E-MAIL</label>
        <input 
          type="email" 
          value={data.email}
          onChange={(e) => onChange('email', e.target.value)}
          className="w-full bg-[#1E1E1E] border-none text-white/60 p-4 font-black italic uppercase text-sm outline-none focus:ring-1 focus:ring-[#FFD700]"
          style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}
        />
      </div>
    </div>
    <div className="flex gap-4 pt-4 border-t border-white/5">
      <MCOButton variant="outline" className="flex-1 py-4 !text-[10px]">ALTERAR SENHA</MCOButton>
      <MCOButton variant="outline" className="flex-1 py-4 !text-[10px]">PAGAMENTO</MCOButton>
    </div>
  </div>
);

const ProfileGameWidget = ({ data, onChange }: any) => {
  const geracoes = data.options?.geracoes || [];
  const plataformas = data.options?.plataformas || [];
  const jogos = data.options?.jogos || [];

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
      <div className="space-y-2">
        <label className="text-[9px] font-black text-white/30 uppercase italic tracking-widest">GERAÇÃO DO CONSOLE</label>
        <select
          value={data.geracao_id || ''}
          onChange={(e) => onChange('geracao_id', e.target.value ? Number(e.target.value) : null)}
          className="w-full bg-[#1E1E1E] border-none text-white p-4 font-black italic uppercase text-sm outline-none"
          style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}
        >
          <option value="">SELECIONE</option>
          {geracoes.map((geracao: any) => (
            <option key={geracao.id} value={geracao.id}>{geracao.nome}</option>
          ))}
        </select>
      </div>
      <div className="space-y-2">
        <label className="text-[9px] font-black text-white/30 uppercase italic tracking-widest">PLATAFORMA ATIVA</label>
        <select
          value={data.plataforma_id || ''}
          onChange={(e) => onChange('plataforma_id', e.target.value ? Number(e.target.value) : null)}
          className="w-full bg-[#1E1E1E] border-none text-white p-4 font-black italic uppercase text-sm outline-none"
          style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}
        >
          <option value="">SELECIONE</option>
          {plataformas.map((plataforma: any) => (
            <option key={plataforma.id} value={plataforma.id}>{plataforma.nome}</option>
          ))}
        </select>
      </div>
      <div className="space-y-2">
        <label className="text-[9px] font-black text-white/30 uppercase italic tracking-widest">VERSÃO DO JOGO</label>
        <select
          value={data.jogo_id || ''}
          onChange={(e) => onChange('jogo_id', e.target.value ? Number(e.target.value) : null)}
          className="w-full bg-[#1E1E1E] border-none text-white p-4 font-black italic uppercase text-sm outline-none"
          style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}
        >
          <option value="">SELECIONE</option>
          {jogos.map((jogo: any) => (
            <option key={jogo.id} value={jogo.id}>{jogo.nome}</option>
          ))}
        </select>
      </div>
    </div>
  );
};

const ProfileScheduleWidget = ({ availability, onAddSlot, onRemoveSlot, onTimeChange }: any) => {
  const daysOfWeek = ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB', 'DOM'];
  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
      <p className="text-[10px] text-white/30 uppercase italic tracking-widest leading-relaxed">CADASTRAR MÚLTIPLOS HORÁRIOS PARA CADA DIA DA SEMANA.</p>
      <div className="space-y-4">
        {daysOfWeek.map((day) => {
          const daySlots = availability[day] || [];
          const hasSlots = daySlots.length > 0;
          return (
            <div key={day} className={`bg-[#1E1E1E] p-4 transition-all ${hasSlots ? 'border-l-[3px] border-[#FFD700]' : 'opacity-30'}`} style={{ clipPath: "polygon(10px 0, 100% 0, 100% 100%, 0 100%, 0 10px)" }}>
              <div className="flex justify-between items-center mb-3">
                <div className="flex items-center gap-2">
                  <span className={`w-10 h-10 flex items-center justify-center font-black italic text-xs ${hasSlots ? 'bg-[#FFD700] text-[#121212]' : 'bg-[#121212] text-white/20'}`} style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>{day}</span>
                  <span className="text-[10px] font-black text-white uppercase italic tracking-widest">{hasSlots ? `${daySlots.length} SLOT(S)` : 'INDISPONÍVEL'}</span>
                </div>
                <button 
                  onClick={() => onAddSlot(day)}
                  className="w-8 h-8 bg-white/5 hover:bg-[#FFD700] hover:text-[#121212] transition-all flex items-center justify-center text-xs"
                  style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}
                >
                  <i className="fas fa-plus"></i>
                </button>
              </div>
              <div className="space-y-2">
                {daySlots.map((slot: any, idx: number) => (
                  <div key={idx} className="flex items-center gap-3 bg-[#121212] p-3" style={{ clipPath: "polygon(6px 0, 100% 0, 100% 100%, 0 100%, 0 6px)" }}>
                    <div className="flex-grow flex items-center gap-2">
                      <input 
                        type="time" 
                        value={slot.from}
                        onChange={(e) => onTimeChange(day, idx, 'from', e.target.value)}
                        className="flex-1 bg-transparent text-white font-black italic text-xs outline-none"
                      />
                      <span className="text-[8px] text-[#FFD700] font-black italic">ATÉ</span>
                      <input 
                        type="time" 
                        value={slot.to}
                        onChange={(e) => onTimeChange(day, idx, 'to', e.target.value)}
                        className="flex-1 bg-transparent text-white font-black italic text-xs outline-none"
                      />
                    </div>
                    <button onClick={() => onRemoveSlot(day, idx)} className="text-white/20 hover:text-[#B22222] transition-colors p-1"><i className="fas fa-trash-can text-[10px]"></i></button>
                  </div>
                ))}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

// --- Container Views ---

const HubGlobalView = ({ onOpenMyClub, onOpenTournaments, onOpenMarket, onOpenStats, onOpenLeaderboard, onOpenInbox, careers, currentCareer, onCareerChange, userStats, onOpenOwnProfile }: any) => (
  <div className="min-h-screen bg-[#121212] pt-16 pb-32">
    <MCOTopBar careers={careers} currentCareer={currentCareer} onCareerChange={onCareerChange} uberScore={userStats.uberScore} skillRating={userStats.skillRating} />
    
    <div className="p-4 space-y-6">
      <header className="px-4 mb-4 flex justify-between items-end">
        <div>
          <h2 className="text-4xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">DASHBOARD</h2>
          <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">MANAGER HUB</p>
        </div>
        <button onClick={onOpenOwnProfile} className="bg-[#1E1E1E] text-[#FFD700] text-[9px] font-black italic px-4 py-2 border-r-2 border-[#FFD700]" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>
          VER PERFIL
        </button>
      </header>

      {/* NOVO: ALERTA DE INBOX RÁPIDO */}
      <section onClick={onOpenInbox} className="mx-4 bg-[#B22222] p-4 flex items-center gap-4 cursor-pointer animate-pulse" style={{ clipPath: AGGRESSIVE_CLIP }}>
         <i className="fas fa-triangle-exclamation text-white text-xl"></i>
         <div className="flex-grow">
            <p className="text-[10px] font-black italic uppercase text-white leading-none">AÇÕES PENDENTES</p>
            <p className="text-[8px] font-bold text-white/60 uppercase italic">VOCÊ TEM 1 NOVA PROPOSTA DE TRANSFERÊNCIA</p>
         </div>
         <i className="fas fa-chevron-right text-white/40 text-xs"></i>
      </section>

      <div className="grid grid-cols-1 gap-4">
        <section onClick={onOpenTournaments} className="bg-[#1E1E1E] border-l-[6px] border-[#FFD700] p-6 relative overflow-hidden group cursor-pointer" style={{ clipPath: AGGRESSIVE_CLIP }}>
          <div className="relative z-10 flex justify-between items-center">
            <div>
              <h4 className="text-[10px] font-black uppercase text-[#FFD700] italic mb-1 tracking-widest">COMPETIÇÕES</h4>
              <p className="text-2xl font-black italic uppercase font-heading text-white tracking-tighter">TORNEIOS</p>
            </div>
            <div className="bg-[#FFD700] w-12 h-12 flex items-center justify-center italic text-[#121212] text-2xl font-black" style={{ clipPath: "polygon(10px 0, 100% 0, 100% 100%, 0 100%, 0 10px)" }}>
              <i className="fas fa-sitemap"></i>
            </div>
          </div>
        </section>

        <section onClick={onOpenMarket} className="bg-[#1E1E1E] border-l-[6px] border-[#FFD700] p-6 relative overflow-hidden group cursor-pointer" style={{ clipPath: AGGRESSIVE_CLIP }}>
          <div className="relative z-10 flex justify-between items-center">
            <div>
              <h4 className="text-[10px] font-black uppercase text-[#FFD700] italic mb-1 tracking-widest">JANELA DE NEGÓCIOS</h4>
              <p className="text-2xl font-black italic uppercase font-heading text-white tracking-tighter">MERCADO</p>
            </div>
            <div className="bg-[#FFD700] w-12 h-12 flex items-center justify-center italic text-[#121212] text-2xl font-black" style={{ clipPath: "polygon(10px 0, 100% 0, 100% 100%, 0 100%, 0 10px)" }}>
              <i className="fas fa-right-left"></i>
            </div>
          </div>
        </section>

        <section onClick={onOpenLeaderboard} className="bg-[#1E1E1E] border-l-[6px] border-[#FFD700] p-6 relative overflow-hidden group cursor-pointer" style={{ clipPath: AGGRESSIVE_CLIP }}>
          <div className="relative z-10 flex justify-between items-center">
            <div>
              <h4 className="text-[10px] font-black uppercase text-[#FFD700] italic mb-1 tracking-widest">ESTADO DO META</h4>
              <p className="text-2xl font-black italic uppercase font-heading text-white tracking-tighter">RANKING GLOBAL</p>
            </div>
            <div className="bg-[#FFD700] w-12 h-12 flex items-center justify-center italic text-[#121212] text-2xl font-black" style={{ clipPath: "polygon(10px 0, 100% 0, 100% 100%, 0 100%, 0 10px)" }}>
              <i className="fas fa-ranking-star"></i>
            </div>
          </div>
        </section>

        <section onClick={onOpenStats} className="bg-[#1E1E1E] border-l-[6px] border-[#FFD700] p-6 relative overflow-hidden group cursor-pointer" style={{ clipPath: AGGRESSIVE_CLIP }}>
          <div className="relative z-10 flex justify-between items-center">
            <div>
              <h4 className="text-[10px] font-black uppercase text-[#FFD700] italic mb-1 tracking-widest">LEGADO HISTÓRICO</h4>
              <p className="text-2xl font-black italic uppercase font-heading text-white tracking-tighter">ESTATÍSTICAS</p>
            </div>
            <div className="bg-[#FFD700] w-12 h-12 flex items-center justify-center italic text-[#121212] text-2xl font-black" style={{ clipPath: "polygon(10px 0, 100% 0, 100% 100%, 0 100%, 0 10px)" }}>
              <i className="fas fa-chart-line"></i>
            </div>
          </div>
        </section>

        <section onClick={onOpenMyClub} className="bg-[#1E1E1E] border-l-[6px] border-[#FFD700] p-6 relative overflow-hidden group cursor-pointer" style={{ clipPath: AGGRESSIVE_CLIP }}>
          <div className="relative z-10 flex justify-between items-center">
            <div>
              <h4 className="text-[10px] font-black uppercase text-[#FFD700] italic mb-1 tracking-widest">GESTÃO DE CLUBE</h4>
              <p className="text-2xl font-black italic uppercase font-heading text-white tracking-tighter">MEU CLUBE</p>
            </div>
            <div className="bg-[#FFD700] w-12 h-12 flex items-center justify-center italic text-[#121212] text-2xl font-black" style={{ clipPath: "polygon(10px 0, 100% 0, 100% 100%, 0 100%, 0 10px)" }}>
              <i className="fas fa-landmark"></i>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
);

const ProfileView = ({ onBack }: any) => {
  const [activeWidget, setActiveWidget] = useState<'user' | 'game' | 'schedule'>('user');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [options, setOptions] = useState<any>({ jogos: [], plataformas: [], geracoes: [] });
  const [userData, setUserData] = useState({ name: '', email: '', phone: '', gamertag: '' });
  const [gameData, setGameData] = useState<any>({ geracao_id: null, plataforma_id: null, jogo_id: null, options: { jogos: [], plataformas: [], geracoes: [] } });
  const [availability, setAvailability] = useState<any>(buildEmptyAvailability());

  const widgets = [
    { id: 'user', icon: 'fa-user-gear', label: 'USUÁRIO' },
    { id: 'game', icon: 'fa-gamepad', label: 'JOGO' },
    { id: 'schedule', icon: 'fa-calendar-days', label: 'HORÁRIOS' }
  ];

  useEffect(() => {
    const loadSettings = async () => {
      if (!LEGACY_CONFIG.profileSettingsUrl) {
        setLoading(false);
        return;
      }

      try {
        const payload = await jsonRequest(LEGACY_CONFIG.profileSettingsUrl, { method: 'GET' });
        const profile = payload?.profile || {};
        const profileOptions = payload?.options || { jogos: [], plataformas: [], geracoes: [] };
        const currentAvailability = buildEmptyAvailability();

        (payload?.disponibilidades || []).forEach((item: any) => {
          const dayLabel = INDEX_TO_DAY_LABEL[item.dia_semana];
          if (!dayLabel) return;
          currentAvailability[dayLabel].push({
            id: item.id,
            from: item.hora_inicio,
            to: item.hora_fim,
          });
        });

        setOptions(profileOptions);
        setUserData({
          name: profile.name || '',
          email: profile.email || '',
          phone: profile.whatsapp || '',
          gamertag: profile.nickname || '',
        });
        setGameData({
          geracao_id: profile.geracao_id || null,
          plataforma_id: profile.plataforma_id || null,
          jogo_id: profile.jogo_id || null,
          options: profileOptions,
        });
        setAvailability(currentAvailability);
      } catch (error: any) {
        alert(error?.message || 'Falha ao carregar configurações.');
      } finally {
        setLoading(false);
      }
    };

    loadSettings();
  }, []);

  const handleSave = async () => {
    if (!LEGACY_CONFIG.profileUpdateUrl || !LEGACY_CONFIG.profileDisponibilidadesSyncUrl) {
      alert('Configuração legacy ausente para salvar dados.');
      return;
    }

    setSaving(true);
    try {
      await jsonRequest(LEGACY_CONFIG.profileUpdateUrl, {
        method: 'PUT',
        body: JSON.stringify({
          name: userData.name,
          email: userData.email,
          nickname: userData.gamertag,
          whatsapp: userData.phone ? userData.phone.replace(/\D/g, '') : null,
          plataforma_id: gameData.plataforma_id,
          geracao_id: gameData.geracao_id,
          jogo_id: gameData.jogo_id,
        }),
      });

      const entries = Object.entries(availability).flatMap(([day, slots]: any) =>
        (slots || [])
          .filter((slot: any) => slot.from && slot.to)
          .map((slot: any) => ({
            dia_semana: DAY_LABEL_TO_INDEX[day],
            hora_inicio: slot.from,
            hora_fim: slot.to,
          })),
      );

      await jsonRequest(LEGACY_CONFIG.profileDisponibilidadesSyncUrl, {
        method: 'PUT',
        body: JSON.stringify({ entries }),
      });

      alert('ALTERAÇÕES SALVAS!');
    } catch (error: any) {
      alert(error?.message || 'Falha ao salvar configurações.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#121212] p-6 pb-40 overflow-y-auto">
      <header className="mb-8">
        <MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40"><i className="fas fa-arrow-left mr-2"></i> VOLTAR</MCOButton>
        <h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">CONFIGURAÇÕES</h2>
        <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">GERENCIAMENTO DE CONTA</p>
      </header>
      <div className="flex gap-2 mb-10 bg-[#1E1E1E] p-1" style={{ clipPath: "polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px)" }}>
        {widgets.map(w => (
          <button key={w.id} onClick={() => setActiveWidget(w.id as any)} className={`flex-1 py-4 flex flex-col items-center gap-1 transition-all ${activeWidget === w.id ? 'bg-[#FFD700] text-[#121212]' : 'text-white/20'}`} style={{ clipPath: "polygon(6px 0, 100% 0, 100% 100%, 0 100%, 0 6px)" }}>
            <i className={`fas ${w.icon} text-sm`}></i>
            <span className="text-[8px] font-black italic uppercase tracking-tighter">{w.label}</span>
          </button>
        ))}
      </div>
      <div className="mb-12">
        {loading && (
          <div className="text-center py-16 text-white/40 text-xs font-black italic uppercase tracking-[0.2em]">
            CARREGANDO CONFIGURAÇÕES...
          </div>
        )}
        {activeWidget === 'user' && <ProfileUserWidget data={userData} onChange={(f: any, v: any) => setUserData({...userData, [f]: v})} />}
        {activeWidget === 'game' && <ProfileGameWidget data={{ ...gameData, options }} onChange={(f: any, v: any) => setGameData({...gameData, [f]: v})} />}
        {activeWidget === 'schedule' && <ProfileScheduleWidget availability={availability} onAddSlot={(day: string) => setAvailability({...availability, [day]: [...availability[day], {from: '19:00', to: '22:00'}]})} onRemoveSlot={(day: string, idx: number) => setAvailability({...availability, [day]: availability[day].filter((_: any, i: number) => i !== idx)})} onTimeChange={(day: string, idx: number, f: string, v: string) => { const nd = [...availability[day]]; nd[idx] = {...nd[idx], [f]: v}; setAvailability({...availability, [day]: nd}); }} />}
      </div>
      <div className="fixed bottom-24 left-6 right-6 z-40">
        <MCOButton variant="primary" className="w-full py-5 text-lg" onClick={handleSave} disabled={saving}>
          {saving ? 'SALVANDO...' : 'SALVAR ALTERAÇÕES'}
        </MCOButton>
      </div>
    </div>
  );
};

const MyClubView = ({ onBack, onOpenSubView, userStats }: any) => {
  const menus = [
    { id: 'squad', title: 'MEU ELENCO', icon: 'fa-users-line', desc: 'GESTÃO DE ATLETAS' },
    { id: 'achievements', title: 'CONQUISTAS', icon: 'fa-award', desc: 'OBJETIVOS E METAS' },
    { id: 'finance', title: 'FINANCEIRO', icon: 'fa-sack-dollar', desc: 'BALANÇO E PATROCÍNIOS' },
    { id: 'trophies', title: 'TROFÉUS', icon: 'fa-trophy', desc: 'GALERIA DE GLÓRIAS' }
  ];

  return (
    <div className="min-h-screen bg-[#121212] p-6 pb-32 overflow-y-auto">
      <header className="mb-10">
        <MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40">
          <i className="fas fa-arrow-left mr-2"></i> VOLTAR
        </MCOButton>
        <h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">MEU CLUBE</h2>
        <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">{userStats.clubName}</p>
      </header>
      <FanProgressWidget fans={userStats.fans} />
      <div className="grid grid-cols-2 gap-4">
        {menus.map((m) => (
          <MCOCard key={m.id} onClick={() => onOpenSubView(m.id)} className="p-6">
            <i className={`fas ${m.icon} text-2xl text-[#FFD700] mb-4`}></i>
            <h4 className="text-xs font-black italic uppercase font-heading text-white">{m.title}</h4>
            <p className="text-[8px] text-white/30 font-bold uppercase italic mt-1">{m.desc}</p>
          </MCOCard>
        ))}
      </div>
    </div>
  );
};

const TournamentsView = ({ onBack, onSelectTournament }: any) => {
  const tournaments = [
    { id: 'liga', title: 'LIGA NACIONAL', icon: 'fa-shield-halved', status: 'EM ANDAMENTO', accent: '#FFD700' },
    { id: 'cup', title: 'COPA DA LIGA', icon: 'fa-trophy', status: 'OITAVAS DE FINAL', accent: '#FFD700' },
    { id: 'continental', title: 'CHAMPIONS CUP', icon: 'fa-globe', status: 'QUARTAS DE FINAL', accent: '#0045e6' }
  ];

  return (
    <div className="min-h-screen bg-[#121212] p-6 pb-32 overflow-y-auto">
      <header className="mb-10">
        <MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40">
          <i className="fas fa-arrow-left mr-2"></i> VOLTAR
        </MCOButton>
        <h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">TORNEIOS</h2>
        <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">COMPETIÇÕES ATIVAS</p>
      </header>
      <div className="space-y-4">
        {tournaments.map((t) => (
          <MCOCard key={t.id} onClick={() => onSelectTournament(t.id)} accentColor={t.accent} active={true} className="p-8">
            <div className="flex justify-between items-center">
              <div className="flex items-center gap-6">
                <i className={`fas ${t.icon} text-4xl`} style={{ color: t.accent }}></i>
                <div>
                  <h4 className="text-xl font-black italic uppercase font-heading text-white">{t.title}</h4>
                  <span className="text-[9px] font-black bg-white/5 text-white/40 px-3 py-1 italic tracking-widest mt-2 block w-max" style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}>{t.status}</span>
                </div>
              </div>
              <i className="fas fa-chevron-right text-white/10"></i>
            </div>
          </MCOCard>
        ))}
      </div>
    </div>
  );
};

const MarketView = ({ onBack, userStats, careers, currentCareer, onCareerChange }: any) => {
  const [subMode, setSubMode] = useState<'menu' | 'list' | 'watchlist'>('menu');
  const [selectedPlayer, setSelectedPlayer] = useState<any>(null);
  const [showDetailed, setShowDetailed] = useState(false);
  
  const [filterStatus, setFilterStatus] = useState('TODOS');
  const [filterPos, setFilterPos] = useState('TODAS');
  const [filterQuality, setFilterQuality] = useState('TODAS');
  const [filterValMin, setFilterValMin] = useState('');
  const [filterValMax, setFilterValMax] = useState('');
  const [sortBy, setSortBy] = useState('OVR_DESC');

  const closePlayer = () => { setSelectedPlayer(null); setShowDetailed(false); };

  const filteredPlayers = useMemo(() => {
    const list = subMode === 'list' ? MOCK_MARKET_PLAYERS : MOCK_WATCHLIST;
    
    return list.filter(p => {
      const matchStatus = filterStatus === 'TODOS' || (p as any).status === filterStatus;
      let matchPos = filterPos === 'TODAS';
      if (!matchPos) {
        if (filterPos === 'ATACANTES') matchPos = ['ATA', 'PE', 'PD', 'SA'].includes(p.pos);
        else if (filterPos === 'MEIO') matchPos = ['MC', 'MEI', 'VOL', 'ME', 'MD'].includes(p.pos);
        else if (filterPos === 'DEFESA') matchPos = ['ZAG', 'LD', 'LE', 'LWB', 'RWB'].includes(p.pos);
        else matchPos = p.pos === filterPos;
      }
      let matchQuality = filterQuality === 'TODAS';
      if (!matchQuality) {
        if (filterQuality === '90+') matchQuality = p.ovr >= 90;
        else if (filterQuality === '89-88') matchQuality = p.ovr >= 88 && p.ovr <= 89;
        else if (filterQuality === '87-84') matchQuality = p.ovr >= 84 && p.ovr <= 87;
        else if (filterQuality === '83-80') matchQuality = p.ovr >= 80 && p.ovr <= 83;
        else if (filterQuality === '79-73') matchQuality = p.ovr >= 73 && p.ovr <= 79;
        else if (filterQuality === '72-') matchQuality = p.ovr <= 72;
      }
      const matchValMin = !filterValMin || p.value >= parseInt(filterValMin);
      const matchValMax = !filterValMax || p.value <= parseInt(filterValMax);
      return matchStatus && matchPos && matchQuality && matchValMin && matchValMax;
    }).sort((a, b) => {
      if (sortBy === 'OVR_DESC') return b.ovr - a.ovr;
      if (sortBy === 'OVR_ASC') return a.ovr - b.ovr;
      if (sortBy === 'VAL_DESC') return b.value - a.value;
      if (sortBy === 'VAL_ASC') return a.value - b.value;
      return 0;
    });
  }, [subMode, filterStatus, filterPos, filterQuality, filterValMin, filterValMax, sortBy]);

  const renderPlayerList = (title: string, subtitle: string) => (
    <div className="min-h-screen bg-[#121212] pt-16 pb-32 overflow-y-auto animate-in fade-in slide-in-from-right-4 duration-300">
      <MCOTopBar careers={careers} currentCareer={currentCareer} onCareerChange={onCareerChange} uberScore={userStats.uberScore} skillRating={userStats.skillRating} />
      <div className="p-4">
        <header className="mb-6">
          <MCOButton variant="ghost" onClick={() => setSubMode('menu')} className="!px-0 !py-0 mb-6 opacity-40">
            <i className="fas fa-arrow-left mr-2"></i> VOLTAR
          </MCOButton>
          <h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">{title}</h2>
          <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">{subtitle}</p>
        </header>
        <div className="bg-[#1E1E1E] p-4 mb-8 space-y-4 border-l-[3px] border-[#FFD700]" style={{ clipPath: "polygon(10px 0, 100% 0, 100% 100%, 0 100%, 0 10px)" }}>
          <h4 className="text-[9px] font-black uppercase text-[#FFD700] italic tracking-widest mb-2"><i className="fas fa-filter mr-2"></i> FILTROS DE BUSCA</h4>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1">
              <label className="text-[7px] font-black text-white/30 uppercase italic">STATUS</label>
              <select value={filterStatus} onChange={(e) => setFilterStatus(e.target.value)} className="w-full bg-[#121212] text-white text-[10px] p-2 outline-none border-none italic font-black uppercase">
                <option value="TODOS">TODOS</option>
                <option value="LIVRE">LIVRE</option>
                <option value="CONTRATADO">CONTRATADO</option>
              </select>
            </div>
            <div className="space-y-1">
              <label className="text-[7px] font-black text-white/30 uppercase italic">QUALIDADE</label>
              <select value={filterQuality} onChange={(e) => setFilterQuality(e.target.value)} className="w-full bg-[#121212] text-white text-[10px] p-2 outline-none border-none italic font-black uppercase">
                <option value="TODAS">TODAS</option>
                <option value="90+">90 OU MAIS</option>
                <option value="89-88">89 A 88</option>
                <option value="87-84">87 A 84</option>
                <option value="83-80">83 A 80</option>
                <option value="79-73">79 A 73</option>
                <option value="72-">72 OU MENOS</option>
              </select>
            </div>
          </div>
          <div className="space-y-1">
            <label className="text-[7px] font-black text-white/30 uppercase italic">POSIÇÃO</label>
            <select value={filterPos} onChange={(e) => setFilterPos(e.target.value)} className="w-full bg-[#121212] text-white text-[10px] p-2 outline-none border-none italic font-black uppercase">
              <option value="TODAS">TODAS</option>
              <option value="ATACANTES">CATEGORIA: ATACANTES</option>
              <option value="MEIO">CATEGORIA: MEIO CAMPISTAS</option>
              <option value="DEFESA">CATEGORIA: DEFENSORES</option>
              <option value="GOL">GOL</option>
              <option value="ZAG">ZAG</option>
              <option value="LD">LD</option>
              <option value="LE">LE</option>
              <option value="VOL">VOL</option>
              <option value="MC">MC</option>
              <option value="MEI">MEI</option>
              <option value="PD">PD</option>
              <option value="PE">PE</option>
              <option value="ATA">ATA</option>
            </select>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1">
              <label className="text-[7px] font-black text-white/30 uppercase italic">VALOR MÍNIMO</label>
              <input type="number" placeholder="M$ MIN" value={filterValMin} onChange={(e) => setFilterValMin(e.target.value)} className="w-full bg-[#121212] text-white text-[10px] p-2 outline-none border-none italic font-black uppercase" />
            </div>
            <div className="space-y-1">
              <label className="text-[7px] font-black text-white/30 uppercase italic">VALOR MÁXIMO</label>
              <input type="number" placeholder="M$ MAX" value={filterValMax} onChange={(e) => setFilterValMax(e.target.value)} className="w-full bg-[#121212] text-white text-[10px] p-2 outline-none border-none italic font-black uppercase" />
            </div>
          </div>
          <div className="space-y-1">
            <label className="text-[7px] font-black text-white/30 uppercase italic">ORDENAR POR</label>
            <select value={sortBy} onChange={(e) => setSortBy(e.target.value)} className="w-full bg-[#121212] text-white text-[10px] p-2 outline-none border-none italic font-black uppercase">
              <option value="OVR_DESC">OVERALL (MAIOR)</option>
              <option value="OVR_ASC">OVERALL (MENOR)</option>
              <option value="VAL_DESC">VALOR (MAIOR)</option>
              <option value="VAL_ASC">VALOR (MENOR)</option>
            </select>
          </div>
        </div>
        <div className="min-w-full space-y-3">
          <div className="grid grid-cols-[70px_1fr_80px_100px] gap-2 px-2 py-3 bg-[#1E1E1E] border-b border-white/5 opacity-50">
            <span className="text-[8px] font-black italic uppercase text-center">ATLETA</span>
            <span className="text-[8px] font-black italic uppercase">NOME</span>
            <span className="text-[8px] font-black italic uppercase text-right pr-2">VALOR</span>
            <span className="text-[8px] font-black italic uppercase text-center">AÇÕES</span>
          </div>
          {filteredPlayers.length > 0 ? filteredPlayers.map((player) => (
            <div 
              key={player.id} 
              className="grid grid-cols-[70px_1fr_80px_100px] gap-2 px-2 py-3 bg-[#1E1E1E] items-center border-r-[3px] border-[#FFD700] min-h-[70px]" 
              style={{ clipPath: "polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px)" }}
            >
              <div className="flex justify-center">
                <div className="relative w-14 h-14 bg-[#121212] border-b-2 border-[#FFD700]/30" style={{ clipPath: SHIELD_CLIP }}>
                  <img src={player.photo} className="w-full h-full object-cover object-top" />
                  <div className="absolute bottom-0 right-0 bg-[#FFD700] text-[#121212] text-[9px] font-black px-1 italic leading-none border-t border-l border-[#121212]/20">
                    {player.ovr}
                  </div>
                  <div className="absolute bottom-0 left-0 bg-[#1E1E1E] text-[#FFD700] text-[7px] font-black px-1 italic leading-none border-t border-r border-white/5">
                    {player.pos}
                  </div>
                </div>
              </div>
              <div className="overflow-hidden cursor-pointer active:opacity-50 px-1" onClick={() => setSelectedPlayer(player)}>
                <p className="text-[11px] font-black italic uppercase text-white truncate leading-tight">{player.name}</p>
                <p className="text-[7px] font-bold uppercase italic text-white/20 truncate mt-0.5">{player.club}</p>
              </div>
              <div className="text-right pr-2">
                <p className="text-[9px] font-black italic font-heading text-white">M$ {player.value}M</p>
              </div>
              <div className="flex flex-col gap-1.5 px-1">
                <button className="bg-[#FFD700] text-[#121212] text-[8px] font-black italic uppercase py-2 px-1 leading-none transition-transform active:scale-95" style={{ clipPath: "polygon(2px 0, 100% 0, 100% 100%, 0 100%, 0 2px)" }}>COMPRAR</button>
                <button className="bg-white/5 text-white/50 text-[8px] font-black italic uppercase py-2 px-1 border border-white/10 leading-none transition-transform active:scale-95" style={{ clipPath: "polygon(2px 0, 100% 0, 100% 100%, 0 100%, 0 2px)" }}>OBSERVAR</button>
              </div>
            </div>
          )) : (
            <div className="text-center py-20 bg-[#1E1E1E]/20" style={{ clipPath: AGGRESSIVE_CLIP }}>
               <i className="fas fa-search-minus text-4xl text-white/5 mb-4"></i>
               <p className="text-[9px] font-black italic uppercase text-white/20">NENHUM ATLETA ENCONTRADO COM ESTES FILTROS</p>
            </div>
          )}
        </div>
      </div>
      {selectedPlayer && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/95 backdrop-blur-md">
          <div className="absolute inset-0" onClick={closePlayer}></div>
          <div className="relative w-full max-w-sm flex flex-col items-center">
            <div className="w-full flex justify-end mb-4">
              <button onClick={closePlayer} className="bg-[#1E1E1E] text-[#FFD700] w-12 h-12 flex items-center justify-center border-b-[3px] border-[#FFD700]" style={{ clipPath: AGGRESSIVE_CLIP }}>
                <i className="fas fa-times text-xl"></i>
              </button>
            </div>
            {showDetailed ? <DetailedAttributes player={selectedPlayer} /> : <LegacyUTCard player={selectedPlayer} />}
            <div className="mt-8 w-full">
              <MCOButton variant={showDetailed ? "primary" : "outline"} className="w-full py-5 !text-[11px]" onClick={() => setShowDetailed(!showDetailed)}>
                {showDetailed ? "VER CARD ULTIMATE" : "FICHA TÉCNICA COMPLETA"}
              </MCOButton>
            </div>
          </div>
        </div>
      )}
    </div>
  );

  if (subMode === 'list') return renderPlayerList('MERCADO', 'JANELA ABERTA');
  if (subMode === 'watchlist') return renderPlayerList('OBSERVAÇÃO', 'LISTA DE SCOUTING');

  return (
    <div className="min-h-screen bg-[#121212] pt-16 pb-32 overflow-y-auto">
      <MCOTopBar careers={careers} currentCareer={currentCareer} onCareerChange={onCareerChange} uberScore={userStats.uberScore} skillRating={userStats.skillRating} />
      <div className="p-6">
        <header className="mb-10">
          <MCOButton variant="ghost" onClick={onBack} className="!px-0 !py-0 mb-6 opacity-40">
            <i className="fas fa-arrow-left mr-2"></i> VOLTAR
          </MCOButton>
          <h2 className="text-5xl font-black italic uppercase font-heading text-white leading-none tracking-tighter">MERCADO</h2>
          <p className="text-[10px] text-[#FFD700] font-bold tracking-[0.4em] uppercase italic">TRANSFERÊNCIAS E NEGÓCIOS</p>
        </header>
        <div className="grid grid-cols-1 gap-4">
          <MCOCard onClick={() => setSubMode('list')} className="p-8" active={true} accentColor="#FFD700">
            <div className="flex items-center gap-6">
              <div className="w-16 h-16 bg-[#121212] flex items-center justify-center border-b-2 border-[#FFD700]" style={{ clipPath: SHIELD_CLIP }}>
                <i className="fas fa-right-left text-2xl text-[#FFD700]"></i>
              </div>
              <div>
                <h4 className="text-xl font-black italic uppercase font-heading text-white">MERCADO ABERTO</h4>
                <p className="text-[8px] text-white/30 font-bold uppercase italic mt-1 tracking-widest">LISTA GLOBAL DE ATLETAS</p>
              </div>
            </div>
          </MCOCard>
          <MCOCard onClick={() => setSubMode('watchlist')} className="p-8" active={true} accentColor="#FFD700">
            <div className="flex items-center gap-6">
              <div className="w-16 h-16 bg-[#121212] flex items-center justify-center border-b-2 border-[#FFD700]" style={{ clipPath: SHIELD_CLIP }}>
                <i className="fas fa-binoculars text-2xl text-[#FFD700]"></i>
              </div>
              <div>
                <h4 className="text-xl font-black italic uppercase font-heading text-white">EM OBSERVAÇÃO</h4>
                <p className="text-[8px] text-white/30 font-bold uppercase italic mt-1 tracking-widest">ATLETAS MONITORADOS</p>
              </div>
            </div>
          </MCOCard>
        </div>
      </div>
    </div>
  );
};

const App = () => {
  const [view, setView] = useState('hub-global');
  const [selectedPendingMatch, setSelectedPendingMatch] = useState<any>(null);
  const [clubProfileToView, setClubProfileToView] = useState<any>(null);
  
  const [careers] = useState([
    { id: 'main', name: 'CARREIRA PRINCIPAL' },
    { id: 'draft', name: 'DRAFT LEGACY' },
    { id: 'meta', name: 'META SQUAD XI' }
  ]);
  const [currentCareerId, setCurrentCareerId] = useState('main');
  const currentCareer = careers.find(c => c.id === currentCareerId) || careers[0];

  const [userStats] = useState({ 
    clubName: 'CRUZEIRO EC', 
    activeConfed: 'UEFA', 
    activeLeague: 'u-elite', 
    fans: 3250000, 
    wins: 142, 
    goals: 430, 
    assists: 145, 
    wonTrophies: ['elite-23', 'champions-22', 'g4-24'],
    totalPrizes: 850, 
    prizeHistory: [{ name: 'ELITE DIVISION (1º LUGAR)', season: 'TEMP 23', value: 500 }, { name: 'CHAMPIONS CUP (WINNER)', season: 'TEMP 22', value: 300 }, { name: 'COPA G4 (SEMI-FINAL)', season: 'TEMP 24', value: 50 }],
    uberScore: 4.8,
    skillRating: 92
  });
  
  const handleTournamentSelect = (id: string) => {
    if (id === 'liga') setView('league-table');
    else if (id === 'cup') setView('cup-detail');
    else if (id === 'continental') setView('continental-detail');
  };

  const handleConfirmResult = (match: any) => {
    setSelectedPendingMatch(match);
    setView('confirm-match');
  };

  const handleOpenClubProfile = (name: string) => {
    const mockProfile = {
      clubName: name,
      fans: 1500000 + Math.random() * 5000000,
      uberScore: 3.5 + Math.random() * 1.5,
      skillRating: 80 + Math.random() * 15,
      wins: 80 + Math.floor(Math.random() * 100),
      goals: 200 + Math.floor(Math.random() * 300),
      assists: 60 + Math.floor(Math.random() * 100),
      wonTrophies: Math.random() > 0.5 ? ['t1'] : []
    };
    setClubProfileToView(mockProfile);
    setView('public-club-profile');
  };

  const renderContent = () => {
    switch(view) {
      case 'hub-global': return <HubGlobalView onOpenMyClub={() => setView('my-club')} onOpenTournaments={() => setView('tournaments')} onOpenMarket={() => setView('market')} onOpenStats={() => setView('season-stats')} onOpenLeaderboard={() => setView('leaderboard')} onOpenInbox={() => setView('inbox')} careers={careers} currentCareer={currentCareer} onCareerChange={setCurrentCareerId} userStats={userStats} onOpenOwnProfile={() => { setClubProfileToView(userStats); setView('public-club-profile'); }} />;
      case 'public-club-profile': return <PublicClubProfileView clubData={clubProfileToView} onBack={() => setView('hub-global')} />;
      case 'season-stats': return <SeasonStatsView userStats={userStats} onBack={() => setView('hub-global')} />;
      case 'leaderboard': return <LeaderboardView onBack={() => setView('hub-global')} onOpenProfile={handleOpenClubProfile} />;
      case 'inbox': return <InboxView onBack={() => setView('hub-global')} onAction={(t) => {
        if (t === 'TRANSFER') setView('market');
        else if (t === 'MATCH') setView('match-center');
        else if (t === 'FINANCE') setView('finance');
        else setView('hub-global');
      }} />;
      case 'match-center': return <MatchCenterView onRegisterResult={() => setView('report-match')} onConfirmResult={handleConfirmResult} onOpenSchedule={() => setView('schedule-matches')} onOpenProfile={handleOpenClubProfile} />;
      case 'schedule-matches': return <ScheduleMatchesView onBack={() => setView('match-center')} />;
      case 'report-match': return <ReportMatchView onBack={() => setView('match-center')} />;
      case 'confirm-match': return <ConfirmResultView onBack={() => { setView('match-center'); setSelectedPendingMatch(null); }} match={selectedPendingMatch} />;
      case 'market': return <MarketView onBack={() => setView('hub-global')} userStats={userStats} careers={careers} currentCareer={currentCareer} onCareerChange={setCurrentCareerId} />;
      case 'my-club': return <MyClubView onBack={() => setView('hub-global')} onOpenSubView={(id: string) => setView(id)} userStats={userStats} />;
      case 'squad': return <SquadView onBack={() => setView('my-club')} />;
      case 'achievements': return <AchievementsView onBack={() => setView('my-club')} userStats={userStats} />;
      case 'finance': return <FinanceView onBack={() => setView('my-club')} userStats={userStats} />;
      case 'trophies': return <TrophiesView onBack={() => setView('my-club')} userStats={userStats} />;
      case 'tournaments': return <TournamentsView onBack={() => setView('hub-global')} onSelectTournament={handleTournamentSelect} />;
      case 'league-table': return <LeagueTableView onBack={() => setView('tournaments')} onOpenClub={handleOpenClubProfile} />;
      case 'cup-detail': return <LeagueCupView onBack={() => setView('tournaments')} onOpenClub={handleOpenClubProfile} />;
      case 'continental-detail': return <ContinentalTournamentView onBack={() => setView('tournaments')} onOpenClub={handleOpenClubProfile} />;
      case 'profile': return <ProfileView onBack={() => setView('hub-global')} />;
      default: return <HubGlobalView onOpenMyClub={() => setView('my-club')} onOpenStats={() => setView('season-stats')} userStats={userStats} careers={careers} currentCareer={currentCareer} onCareerChange={setCurrentCareerId} />;
    }
  };

  return (<>{renderContent()}{!['report-match', 'confirm-match', 'schedule-matches', 'public-club-profile', 'season-stats', 'leaderboard', 'inbox'].includes(view) && <MCOBottomNav activeView={view} onViewChange={setView} />}</>);
};

const rootElement = document.getElementById('legacy-app');
if (rootElement) ReactDOM.createRoot(rootElement).render(<React.StrictMode><App /></React.StrictMode>);




