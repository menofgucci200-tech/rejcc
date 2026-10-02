// REJCC — film explicatif « Qu'est-ce que le REJCC ? » — 60 s, 1920×1080.
// Tout est une fonction pure du temps : render(t). Capture image par image via window.renderAt(t).
const DUR = 60;
const VERT = !!window.VERTICAL;
const SW = VERT ? 1080 : 1920, SH = VERT ? 1920 : 1080;
const CFG = VERT ? {
  home: [80, 920, 220, 1480], clampX: [60, 1020], clampY: [180, 1700], net: [540, 900, 430, 640],
  ring: [540, 760, 300], shift: [0, -280],
  pillars: [[540, 560, 430, 832, 352, 790], [540, 930, 650, 832, 728, 790], [540, 1300, 540, 1022, 540, 1110]],
  rRow: 176, rV: 182, inter: [540, 895], HUB: [400, 1100], nodeA: [118, 12.4], nodeR: 225, pillR: 225, pillDx: 30,
  card: (i) => [80 + (i % 2) * 480, 640 + Math.floor(i / 2) * 270],
  stairs: 'M80 1520 H400 V1270 H700 V1020 H990 V880', arrow: 'M970 902 L990 880 L1010 902',
  steps: [[100, 1405], [420, 1155], [720, 905]], stepFont: 52,
  tile: (i) => [85 + (i % 3) * 310, 930 + Math.floor(i / 3) * 170],
  val: (i) => [250, 600 + i * 210], valLab: (i) => [370, 600 + i * 210 - 52], valLine: [250, 600, 250, 1440], valAlign: 'left',
  mapOff: [-20, 140],
} : {
  home: [140, 1780, 150, 930], clampX: [80, 1840], clampY: [100, 980], net: [960, 520, 820, 420],
  ring: [960, 540, 300], shift: [-440, 0],
  pillars: [[520, 600, 835, 492, 748, 440], [960, 600, 1085, 492, 1172, 440], [1400, 600, 960, 708, 960, 808]],
  rRow: 200, rV: 196, inter: [960, 564], HUB: [1190, 560], nodeA: [112, 13.6], nodeR: 255, pillR: 250, pillDx: 50,
  card: (i) => [170 + (i % 3) * 540, 400 + Math.floor(i / 3) * 276],
  stairs: 'M150 930 H640 V780 H1130 V630 H1620 V500', arrow: 'M1600 522 L1620 500 L1640 522',
  steps: [[180, 815], [670, 665], [1160, 515]], stepFont: 66,
  tile: (i) => [870 + (i % 3) * 326, 289 + Math.floor(i / 3) * 176],
  val: (i) => [960 + (i - 2) * 330, 560], valLab: (i) => [960 + (i - 2) * 330 - 160, 690], valLine: [300, 560, 1620, 560], valAlign: 'center',
  mapOff: [0, 0],
};
const $ = (id) => document.getElementById(id);
const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
const lerp = (a, b, p) => a + (b - a) * p;
const E = {
  outExpo: (t) => (t >= 1 ? 1 : 1 - Math.pow(2, -10 * t)),
  inExpo: (t) => (t <= 0 ? 0 : Math.pow(2, 10 * t - 10)),
  io: (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2),
  io4: (t) => (t < 0.5 ? 8 * t ** 4 : 1 - Math.pow(-2 * t + 2, 4) / 2),
  out3: (t) => 1 - Math.pow(1 - t, 3),
  back: (t) => 1 + 2.4 * Math.pow(t - 1, 3) + 1.4 * Math.pow(t - 1, 2),
};
const P = (t, a, d, e = E.outExpo) => e(clamp((t - a) / d));
const NS = 'http://www.w3.org/2000/svg';
const S = (tag, attrs = {}, parent) => {
  const el = document.createElementNS(NS, tag);
  for (const [k, v] of Object.entries(attrs)) el.setAttribute(k, v);
  if (parent) parent.appendChild(el);
  return el;
};
const H = (html, parent) => { const d = document.createElement('div'); d.innerHTML = html.trim(); const el = d.firstChild; parent.appendChild(el); return el; };

// Animations élémentaires
const maskY = (el, t, inAt, outAt = Infinity, dIn = 0.9, dOut = 0.55) => {
  const y = (1 - P(t, inAt, dIn)) * 135 - P(t, outAt, dOut, E.io4) * 135;
  el.style.transform = `translateY(${y}%)`;
};
const soft = (el, t, inAt, outAt = Infinity, dy = 28) => {
  const a = P(t, inAt, 0.9), b = P(t, outAt, 0.45, E.io);
  el.style.opacity = a * (1 - b);
  el.style.transform = `translateY(${(1 - a) * dy - b * dy}px)`;
};
const rule = (el, t, inAt, outAt = Infinity) => {
  el.style.transform = `scaleX(${P(t, inAt, 0.7, E.io) * (1 - P(t, outAt, 0.4, E.io))})`;
};
const kicker = (base, t, inAt, outAt) => { rule($(base + '0'), t, inAt, outAt); maskY($(base), t, inAt + 0.1, outAt); };

// Pictogrammes linéaires (mêmes icônes que le site, style « outline » de la charte)
function icon(name, size, parent, { x, y, color = 'currentColor', sw = 1.6 } = {}) {
  const svg = S('svg', { viewBox: '0 0 24 24', width: size, height: size, class: 'ico', 'stroke-width': sw, stroke: color }, parent);
  if (x !== undefined) { svg.setAttribute('x', x); svg.setAttribute('y', y); }
  svg.innerHTML = window.ICONS[name] || '';
  const parts = [...svg.querySelectorAll('path,circle,line,rect,polyline,ellipse,polygon')];
  parts.forEach((p) => { p.setAttribute('pathLength', 1); p.style.strokeDasharray = '1 1'; });
  svg.draw = (p) => parts.forEach((el) => (el.style.strokeDashoffset = 1 - p));
  svg.draw(0);
  return svg;
}
const draw = (el, p) => { el.style.strokeDasharray = '1 1'; el.style.strokeDashoffset = 1 - p; };

// Graine pseudo-aléatoire stable
let seed = 7;
const rnd = () => ((seed = (seed * 16807) % 2147483647) / 2147483647);

/* =================== A : constat → proverbe → logo =================== */
const pts = $('pts');
const N = 44;
const dots = [];
for (let i = 0; i < N; i++) {
  const home = [CFG.home[0] + rnd() * (CFG.home[1] - CFG.home[0]), CFG.home[2] + rnd() * (CFG.home[3] - CFG.home[2])];
  const d1 = [clamp(home[0] + (rnd() - 0.5) * 520, ...CFG.clampX), clamp(home[1] + (rnd() - 0.5) * 360, ...CFG.clampY)];
  const d2 = [clamp(d1[0] + (rnd() - 0.5) * 520, ...CFG.clampX), clamp(d1[1] + (rnd() - 0.5) * 360, ...CFG.clampY)];
  // réseau : ellipse autour du centre, en évitant la bande de texte
  const ang = (i / N) * Math.PI * 2 + rnd() * 0.3, rr = 0.55 + rnd() * 0.45;
  const net = [CFG.net[0] + Math.cos(ang) * CFG.net[2] * rr, CFG.net[1] + Math.sin(ang) * CFG.net[3] * rr];
  const ringA = (i / N) * Math.PI * 2;
  dots.push({ home, d1, d2, net, ringA, r: 3.5 + rnd() * 3.5, dart: 3.0 + (i % 9) * 0.22, el: S('circle', { r: 4, fill: '#fff' }, pts) });
}
// liens du réseau : 2 plus proches voisins
const links = [];
dots.forEach((a, i) => {
  dots.map((b, j) => [j, Math.hypot(a.net[0] - b.net[0], a.net[1] - b.net[1])]).filter(([j]) => j !== i)
    .sort((x, y) => x[1] - y[1]).slice(0, 2).forEach(([j]) => {
      if (!links.find((l) => (l.a === j && l.b === i))) links.push({ a: i, b: j, el: S('line', { stroke: 'rgba(255,255,255,.28)', 'stroke-width': 1.5, pathLength: 1 }, pts) });
    });
});
pts.querySelectorAll('circle').forEach((c) => pts.appendChild(c)); // points au-dessus des liens

// Monogramme blanc (pièces officielles)
const SYM = ['stem-r', 'stem-uc', 'arc', 'inner-c', 'bar-1', 'bar-2', 'cross-j'];
const OFF = { 'stem-r': [-140, -110], 'stem-uc': [-110, 140], arc: [130, -110], 'inner-c': [170, 110], 'bar-1': [-230, 0], 'bar-2': [-230, 0], 'cross-j': [0, 230] };
const markPieces = window.LOGO.filter((p) => SYM.includes(p.id)).map((p, i) => ({ el: S('path', { d: p.d, fill: '#fff' }, $('mark')), off: OFF[p.id], d: i * 0.08 }));

function sceneA(t) {
  // Textes
  maskY($('a1'), t, 0.35, 2.75); maskY($('a2'), t, 0.75, 2.8);
  maskY($('b1'), t, 3.0, 5.25);
  maskY($('b2'), t, 5.5, 8.0); maskY($('b3'), t, 6.0, 8.05);
  // Points
  const conv = P(t, 8.2, 1.2, E.io4);
  const sh = P(t, 10.1, 0.9, E.io4), shX = sh * CFG.shift[0], shY = sh * CFG.shift[1];
  const spin = t * 0.06;
  dots.forEach((d, i) => {
    const app = P(t, 0.1 + i * 0.035, 0.6, E.back);
    let x = d.home[0], y = d.home[1];
    const k1 = P(t, d.dart, 0.55), k2 = P(t, d.dart + 1.05, 0.55);
    x = lerp(lerp(x, d.d1[0], k1), d.d2[0], k2); y = lerp(lerp(y, d.d1[1], k1), d.d2[1], k2);
    const kn = P(t, 5.3 + (i % 11) * 0.04, 1.2, E.io4);
    x = lerp(x, d.net[0], kn); y = lerp(y, d.net[1], kn);
    const ra = d.ringA + spin, R = CFG.ring[2] + (i % 3) * 14;
    x = lerp(x, CFG.ring[0] + Math.cos(ra) * R, conv) + shX; y = lerp(y, CFG.ring[1] + Math.sin(ra) * R, conv) + shY;
    d.el.setAttribute('cx', x); d.el.setAttribute('cy', y);
    // seul : gris isolé ; ensemble : blanc ; anneau : plus fin
    const lone = P(t, 2.9, 0.4) * (1 - P(t, 5.3, 0.6));
    d.el.setAttribute('r', d.r * clamp(app) * (1 - 0.35 * conv));
    d.el.setAttribute('fill', i % 7 === 0 && conv > 0.5 ? '#ac0100' : '#fff');
    d.el.setAttribute('opacity', clamp(app) * (0.75 - 0.4 * lone) * (1 - 0.25 * conv));
  });
  links.forEach((l, k) => {
    const a = dots[l.a].el, b = dots[l.b].el;
    l.el.setAttribute('x1', a.getAttribute('cx')); l.el.setAttribute('y1', a.getAttribute('cy'));
    l.el.setAttribute('x2', b.getAttribute('cx')); l.el.setAttribute('y2', b.getAttribute('cy'));
    draw(l.el, P(t, 6.0 + (k % 23) * 0.06, 0.9, E.io));
    l.el.setAttribute('opacity', 1 - P(t, 8.0, 0.5, E.io));
  });
  // Logo
  markPieces.forEach(({ el, off, d }) => {
    const p = P(t, 8.9 + d, 1.25);
    el.setAttribute('transform', `translate(${off[0] * (1 - p)} ${off[1] * (1 - p)})`);
    el.setAttribute('opacity', clamp(p * 1.7));
  });
  $('mark').style.transform = `translate(${shX}px, ${shY}px)`;
  kicker('nk', t, 10.55, Infinity); maskY($('n1'), t, 10.7); maskY($('n2'), t, 10.95); maskY($('n3'), t, 11.08);
}

/* =================== B : ADN (Venn) → mission =================== */
const venn = $('venn');
const pillars = [
  { label: 'Foi', icon: 'flame', desc: ['Des valeurs chrétiennes', 'comme boussole.'] },
  { label: 'Innovation', icon: 'sparkles', desc: ['Des solutions', 'technologiques durables.'] },
  { label: 'Entrepreneuriat', icon: 'rocket', desc: ['Créer, développer', 'et réussir.'] },
];
pillars.forEach((p, i) => { const c = CFG.pillars[i]; p.row = [c[0], c[1]]; p.v = [c[2], c[3]]; p.lab = [c[4], c[5]]; });
const HUB = CFG.HUB;
pillars.forEach((p) => {
  p.g = S('g', {}, venn);
  p.c = S('circle', { r: 200, fill: 'rgba(3,29,89,.05)', stroke: '#031d59', 'stroke-width': 3, pathLength: 1 }, p.g);
  p.ico = icon(p.icon, 76, venn, { x: 0, y: 0, color: '#031d59', sw: 1.5 });
  p.lg = S('g', {}, venn);
  p.lt = S('text', { 'text-anchor': 'middle', 'font-family': 'Anton', 'font-size': 46, fill: '#031d59' }, p.lg);
  p.lt.textContent = p.label.toUpperCase();
  p.dt = S('text', { 'text-anchor': 'middle', 'font-family': 'Manrope', 'font-weight': 500, 'font-size': VERT ? 21 : 24, fill: '#333' }, venn);
  p.desc.forEach((line, k) => { const ts = S('tspan', { x: 0, dy: k ? (VERT ? 28 : 32) : 0 }, p.dt); ts.textContent = line; });
});
const hubLogo = S('g', {}, venn);
window.LOGO.filter((p) => SYM.includes(p.id)).forEach((p) => S('path', { d: p.d, fill: p.c === 'red' ? '#ac0100' : '#031d59' }, hubLogo));
// réseau de la mission
const mNodes = [];
for (let i = 0; i < 11; i++) {
  const a = ((CFG.nodeA[0] + i * CFG.nodeA[1]) * Math.PI) / 180, R = CFG.nodeR + (i % 2) * 30;
  const x = HUB[0] + Math.cos(a) * R, y = HUB[1] + Math.sin(a) * R;
  const ex = HUB[0] + Math.cos(a) * 168, ey = HUB[1] + Math.sin(a) * 168;
  const line = S('line', { x1: x, y1: y, x2: ex, y2: ey, stroke: 'rgba(3,29,89,.25)', 'stroke-width': 2, pathLength: 1 }, venn);
  const dot = S('circle', { cx: x, cy: y, r: 9, fill: '#031d59' }, venn);
  dot.style.transformOrigin = `${x}px ${y}px`;
  const pulse = S('circle', { r: 5, fill: '#ac0100', opacity: 0 }, venn);
  mNodes.push({ x, y, ex, ey, line, dot, pulse, d: i * 0.05 });
}
pillars.forEach((p) => { venn.appendChild(p.g); venn.appendChild(p.ico); }); // cercles au-dessus des liens
venn.appendChild(hubLogo);
pillars.forEach((p) => { venn.appendChild(p.lg); venn.appendChild(p.dt); });
const outcomes = [['Co-création', 'users', -42], ["Partage d'expériences", 'message-circle', 0], ['Solutions durables', 'leaf', 42]];
const pills = outcomes.map(([txt, ic, deg], i) => {
  const a = (deg * Math.PI) / 180, R = CFG.pillR;
  const x = HUB[0] + Math.cos(a) * R + CFG.pillDx, y = HUB[1] + Math.sin(a) * R * 1.05;
  const conn = S('line', { x1: HUB[0] + Math.cos(a) * 168, y1: HUB[1] + Math.sin(a) * 168, x2: x, y2: y, stroke: '#ac0100', 'stroke-width': 2.5, pathLength: 1 }, venn);
  const el = H(`<div class="pill" style="left:${x}px;top:${y - 34}px"><i></i>${txt}</div>`, $('B'));
  return { el, conn, d: i * 0.4 };
});

function sceneB(t) {
  kicker('bk', t, 13.1, 18.55); maskY($('bt'), t, 13.3, 18.6);
  const toV = P(t, 16.5, 1.0, E.io4), toH = P(t, 18.7, 1.0, E.io4);
  pillars.forEach((p, i) => {
    const di = i * 0.15;
    const cx = lerp(lerp(p.row[0], p.v[0], toV), HUB[0], toH), cy = lerp(lerp(p.row[1], p.v[1], toV), HUB[1], toH);
    const r = lerp(lerp(CFG.rRow, CFG.rV, toV), 160, toH);
    p.c.setAttribute('cx', cx); p.c.setAttribute('cy', cy); p.c.setAttribute('r', r);
    draw(p.c, P(t, 13.6 + di, 1.1, E.io));
    p.c.setAttribute('fill', `rgba(3,29,89,${0.05 * (1 - toH)})`);
    if (toH > 0) p.c.setAttribute('fill', toH > 0.5 ? '#ffffff' : 'rgba(3,29,89,.02)');
    // icône
    p.ico.setAttribute('x', p.row[0] - 38); p.ico.setAttribute('y', p.row[1] - 120);
    p.ico.draw(P(t, 13.9 + di, 1.0, E.io)); p.ico.setAttribute('opacity', 1 - P(t, 16.15, 0.35, E.io));
    // libellé
    const lx = lerp(p.row[0], p.lab[0], toV), ly = lerp(p.row[1] + 20, p.lab[1], toV), ls = lerp(1, 0.78, toV);
    const la = P(t, 14.1 + di, 0.8);
    p.lg.setAttribute('transform', `translate(${lx} ${ly + (1 - la) * 24}) scale(${ls})`);
    p.lg.setAttribute('opacity', la * (1 - P(t, 18.6, 0.4, E.io)));
    p.dt.setAttribute('transform', `translate(${p.row[0]} ${p.row[1] + 68})`);
    p.dt.querySelectorAll('tspan').forEach((ts) => ts.setAttribute('x', 0));
    p.dt.setAttribute('opacity', P(t, 14.4 + di, 0.8) * (1 - P(t, 16.1, 0.35, E.io)));
  });
  // monogramme au croisement, puis au centre du réseau
  const lp = P(t, 17.2, 0.9, E.back);
  const lx = lerp(CFG.inter[0], HUB[0], toH), ly = lerp(CFG.inter[1], HUB[1], toH), sc = lerp(0.19, 0.3, toH) * clamp(lp);
  hubLogo.setAttribute('transform', `translate(${lx} ${ly}) scale(${sc}) translate(-501 -373)`);
  hubLogo.setAttribute('opacity', clamp(lp));
  maskY($('vcap'), t, 17.5, 18.55);
  // mission
  kicker('mk', t, 19.2, Infinity); maskY($('m1'), t, 19.35); maskY($('m2'), t, 19.5);
  soft($('m3'), t, 20.0);
  mNodes.forEach((n) => {
    const a = P(t, 19.9 + n.d, 0.6, E.back);
    n.dot.style.transform = `scale(${a})`; n.dot.setAttribute('opacity', clamp(a));
    draw(n.line, P(t, 20.2 + n.d, 0.8, E.io));
    const local = t - (21.0 + n.d * 6);
    if (local > 0) {
      const ph = (local % 2.4) / 2.4, e = E.io(ph);
      n.pulse.setAttribute('cx', lerp(n.x, n.ex, e)); n.pulse.setAttribute('cy', lerp(n.y, n.ey, e));
      n.pulse.setAttribute('opacity', ph < 0.1 ? ph * 10 : ph > 0.85 ? (1 - ph) / 0.15 : 1);
    } else n.pulse.setAttribute('opacity', 0);
  });
  pills.forEach(({ el, conn, d }) => {
    draw(conn, P(t, 21.4 + d, 0.6, E.io));
    const a = P(t, 21.75 + d, 0.8, E.back);
    el.style.opacity = clamp(a * 1.5); el.style.transform = `scale(${0.6 + 0.4 * a})`; el.style.transformOrigin = 'left center';
  });
}

/* =================== C : l'offre =================== */
const offers = [
  ['network', 'Networking', "Des liens d'affaires et des collaborations durables."],
  ['graduation-cap', 'Formations', 'Compétences entrepreneuriales, techniques et managériales.'],
  ['users', 'Mentorat', 'Un accompagnement par des entrepreneurs confirmés.'],
  ['mic', 'Conférences', 'Des leaders qui partagent leur vision et leur expérience.'],
  ['building-2', "Visites d'entreprises", "Découvrir des modèles qui réussissent et s'en inspirer."],
  ['rocket', 'Accélération de projets', "Opportunités d'affaires, appels à projets, financements."],
];
const cards = offers.map(([ic, title, text], i) => {
  const [x, y] = CFG.card(i);
  const el = H(`<div class="card" style="left:${x}px;top:${y}px"><div class="ic"></div><h3>${title}</h3><p>${text}</p></div>`, $('cards'));
  const svg = icon(ic, 36, el.querySelector('.ic'), { color: '#fff', sw: 1.7 });
  return { el, svg, d: i * 0.2 };
});
function sceneC(t) {
  kicker('ck', t, 25.5, 32.7); maskY($('c1'), t, 25.65, 32.75); maskY($('c2'), t, 25.85, 32.8);
  cards.forEach(({ el, svg, d }, i) => {
    soft(el, t, 26.3 + d, 32.7 + i * 0.03, 40);
    svg.draw(P(t, 26.5 + d, 0.9, E.io));
    const act = P(t, 28.4 + i * 0.65, 0.3, E.io) * (1 - P(t, 29.05 + i * 0.65, 0.4, E.io));
    el.style.borderColor = `rgba(255,255,255,${0.12 + 0.5 * act})`;
    el.style.background = `rgba(255,255,255,${0.055 + 0.06 * act})`;
  });
}

/* =================== D : pour qui =================== */
const stairs = $('stairs');
const stairPath = S('path', { d: CFG.stairs, fill: 'none', stroke: '#031d59', 'stroke-width': 5, 'stroke-linejoin': 'round', pathLength: 1 }, stairs);
const arrow = S('path', { d: CFG.arrow, fill: 'none', stroke: '#031d59', 'stroke-width': 5, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, stairs);
const climber = S('circle', { r: 15, fill: '#ac0100' }, stairs);
const ring = S('circle', { r: 15, fill: 'none', stroke: '#ac0100', 'stroke-width': 3 }, stairs);
const stepsData = [['Entreprendre', 'Étudiants & jeunes diplômés'], ['Grandir', 'Entrepreneurs débutants'], ['Réussir', 'Entrepreneurs confirmés']].map((s, i) => [...s, ...CFG.steps[i]]);
const steps = stepsData.map(([v, who, x, y]) => H(`<div class="abs" style="left:${x}px;top:${y - (VERT ? 60 : 30)}px;${VERT ? 'width:270px;' : ''}color:var(--navy)"><div class="anton" style="font-size:${CFG.stepFont}px">${v}<b class="red">.</b></div><div style="font-weight:700;font-size:25px;color:#333;margin-top:6px">${who}</div></div>`, $('steps')));
function sceneD(t) {
  kicker('dk', t, 33.1, 38.4); maskY($('d1'), t, 33.25, 38.45); maskY($('d2'), t, 33.55, 38.5);
  draw(stairPath, P(t, 33.9, 1.5, E.io));
  arrow.setAttribute('opacity', P(t, 35.2, 0.3));
  steps.forEach((el, i) => soft(el, t, 34.3 + i * 0.45, 38.4));
  const c = P(t, 35.6, 2.2, E.io), L = stairPath.getTotalLength();
  const pt = stairPath.getPointAtLength(L * c);
  climber.setAttribute('cx', pt.x); climber.setAttribute('cy', pt.y);
  climber.setAttribute('opacity', P(t, 35.4, 0.3));
  const burst = P(t, 37.8, 0.9, E.out3);
  ring.setAttribute('cx', pt.x); ring.setAttribute('cy', pt.y); ring.setAttribute('r', 15 + 50 * burst);
  ring.setAttribute('opacity', burst > 0 ? 1 - burst : 0);
}

/* =================== E : domaines =================== */
const poles = [['sprout', 'Agriculture & Agro'], ['cpu', 'Tech & Numérique'], ['megaphone', 'Communication & Création'], ['landmark', 'Finance & Services'], ['heart-pulse', 'Éducation & Santé'], ['building-2', 'Immobilier & BTP'], ['shopping-bag', 'Commerce & Mobilité'], ['scissors', 'Artisanat & Mode'], ['leaf', 'Impact & Énergie']];
const tiles = poles.map(([ic, name], i) => {
  const [x, y] = CFG.tile(i);
  const el = H(`<div class="tile" style="left:${x}px;top:${y}px"><span></span><b>${name}</b></div>`, $('tiles'));
  const svg = icon(ic, 38, el.querySelector('span'), { color: '#fff', sw: 1.7 });
  return { el, svg };
});
function sceneE(t) {
  kicker('ek', t, 39.1, 44.0);
  maskY($('e1'), t, 39.3, 44.05); $('e1').textContent = Math.round(33 * P(t, 39.3, 1.6, E.out3));
  maskY($('e2'), t, 39.6, 44.1); maskY($('e3'), t, 39.8, 44.15);
  tiles.forEach(({ el, svg }, i) => { soft(el, t, 40.0 + i * 0.1, 44.0 + i * 0.02, 30); svg.draw(P(t, 40.2 + i * 0.1, 0.9, E.io)); });
}

/* =================== F : valeurs =================== */
const vals = $('vals');
const values = [['flame', 'Foi', 'Des valeurs chrétiennes'], ['award', 'Excellence', 'Qualité et professionnalisme'], ['heart-handshake', 'Solidarité', 'Avancer ensemble'], ['target', 'Impact', "Servir l'Église et la société"], ['gem', 'Création<br>de richesse', 'Des entreprises viables']];
const vLine = S('line', { x1: CFG.valLine[0], y1: CFG.valLine[1], x2: CFG.valLine[2], y2: CFG.valLine[3], stroke: '#ac0100', 'stroke-width': 3, pathLength: 1 }, vals);
const vItems = values.map(([ic, name, desc], i) => {
  const [cx, cy] = CFG.val(i); const [lx, ly] = CFG.valLab(i);
  const c = S('circle', { cx, cy, r: 92, fill: '#f4f6f8', stroke: '#031d59', 'stroke-width': 3, pathLength: 1 }, vals);
  const svg = icon(ic, 70, vals, { x: cx - 35, y: cy - 35, color: '#031d59', sw: 1.5 });
  const lab = H(`<div class="abs" style="left:${lx}px;top:${ly}px;width:${VERT ? 640 : 320}px;text-align:${CFG.valAlign};color:var(--navy)"><div class="anton" style="font-size:${VERT ? 50 : 42}px;line-height:1.02">${VERT ? name.replace('<br>', ' ') : name}</div><div style="font-weight:600;font-size:21px;color:#333;margin-top:10px">${desc}</div></div>`, $('valLabels'));
  return { c, svg, lab };
});
function sceneF(t) {
  kicker('fk', t, 44.6, 49.4); maskY($('f1'), t, 44.8, 49.45);
  draw(vLine, P(t, 45.0, 1.3, E.io));
  vItems.forEach(({ c, svg, lab }, i) => {
    draw(c, P(t, 45.1 + i * 0.12, 0.9, E.io));
    svg.draw(P(t, 45.3 + i * 0.12, 0.9, E.io));
    soft(lab, t, 45.5 + i * 0.12, 49.4);
    const f = P(t, 46.6 + i * 0.45, 0.4, E.io);
    c.setAttribute('fill', f > 0.5 ? '#031d59' : '#f4f6f8');
    c.setAttribute('transform', `translate(${c.getAttribute('cx')} ${c.getAttribute('cy')}) scale(${1 + 0.08 * Math.sin(Math.PI * f)}) translate(${-c.getAttribute('cx')} ${-c.getAttribute('cy')})`);
    svg.setAttribute('stroke', f > 0.5 ? '#ffffff' : '#031d59');
  });
}

/* =================== G : vision =================== */
const map = $('map');
map.setAttribute('viewBox', `${-CFG.mapOff[0]} ${-CFG.mapOff[1]} ${SW} ${SH}`);
const civ = S('path', { d: window.MAP.d, fill: 'rgba(10,44,110,0)', stroke: '#fff', 'stroke-width': 2.5, 'stroke-linejoin': 'round', pathLength: 1 }, map);
const cities = Object.entries(window.MAP.cities);
const abj = window.MAP.cities['Abidjan'];
const labelled = ['Abidjan', 'Yamoussoukro', 'Bouaké', 'Korhogo', 'San-Pédro', 'Man'];
const cityEls = cities.filter(([n]) => n !== 'Abidjan').map(([n, [x, y]], i) => {
  const line = S('line', { x1: x, y1: y, x2: abj[0], y2: abj[1], stroke: 'rgba(255,255,255,.3)', 'stroke-width': 1.5, pathLength: 1 }, map);
  const pulse = S('circle', { r: 4, fill: '#ac0100', opacity: 0 }, map);
  return { n, x, y, line, pulse, i };
});
cityEls.forEach((c) => {
  c.dot = S('circle', { cx: c.x, cy: c.y, r: 6.5, fill: '#fff' }, map); c.dot.style.transformOrigin = `${c.x}px ${c.y}px`;
  if (labelled.includes(c.n)) { c.lab = S('text', { x: c.x + 14, y: c.y + 6, fill: 'rgba(255,255,255,.78)', 'font-family': 'Manrope', 'font-weight': 700, 'font-size': 19 }, map); c.lab.textContent = c.n; }
});
const abjRing = S('circle', { cx: abj[0], cy: abj[1], r: 12, fill: 'none', stroke: '#ac0100', 'stroke-width': 3 }, map);
const abjDot = S('circle', { cx: abj[0], cy: abj[1], r: 11, fill: '#ac0100' }, map);
const abjLab = S('text', { x: abj[0] + 20, y: abj[1] + 30, fill: '#fff', 'font-family': 'Manrope', 'font-weight': 800, 'font-size': 22 }, map); abjLab.textContent = 'Abidjan';
function sceneG(t) {
  draw(civ, P(t, 49.6, 1.7, E.io));
  civ.setAttribute('fill', `rgba(10,44,110,${0.85 * P(t, 50.9, 0.9, E.io)})`);
  cityEls.forEach((c) => {
    const a = P(t, 51.0 + c.i * 0.08, 0.6, E.back);
    c.dot.style.transform = `scale(${a})`; c.dot.setAttribute('opacity', clamp(a));
    if (c.lab) c.lab.setAttribute('opacity', P(t, 51.2 + c.i * 0.08, 0.5, E.io));
    draw(c.line, P(t, 51.4 + c.i * 0.06, 0.8, E.io));
    const local = t - (52.1 + c.i * 0.17);
    if (local > 0) {
      const ph = (local % 2.2) / 2.2, e = E.io(ph);
      c.pulse.setAttribute('cx', lerp(c.x, abj[0], e)); c.pulse.setAttribute('cy', lerp(c.y, abj[1], e));
      c.pulse.setAttribute('opacity', ph < 0.1 ? ph * 10 : ph > 0.85 ? (1 - ph) / 0.15 : 1);
    } else c.pulse.setAttribute('opacity', 0);
  });
  const ab = P(t, 50.8, 0.6, E.back);
  abjDot.setAttribute('r', 11 * clamp(ab)); abjLab.setAttribute('opacity', P(t, 51.0, 0.5));
  const ph = ((t - 51.2) % 1.6) / 1.6;
  abjRing.setAttribute('r', t > 51.2 ? 12 + 40 * ph : 12); abjRing.setAttribute('opacity', t > 51.2 ? 1 - ph : 0);
  kicker('gk', t, 49.8, Infinity);
  ['g1', 'g2', 'g3', 'g4', 'g5'].forEach((id, i) => maskY($(id), t, 50.1 + i * 0.13, Infinity, 1.1));
}

/* =================== H : signature =================== */
const OFF2 = { 'stem-r': [-90, -70], 'stem-uc': [-70, 90], arc: [80, -70], 'inner-c': [110, 70], 'bar-1': [-150, 0], 'bar-2': [-150, 0], 'cross-j': [0, 150] };
const endPieces = window.LOGO.map((p, i) => {
  const u = S('path', { d: p.d, fill: p.c === 'red' ? '#ac0100' : '#031d59' }, $('endLogo'));
  const off = OFF2[p.id] || (p.id.startsWith('w-') ? [0, 60] : [0, 40]);
  return { u, off, d: p.id.startsWith('w-') ? 0.55 + i * 0.03 : p.id.startsWith('line') ? 0.9 + (i - 12) * 0.08 : i * 0.07 };
});
function sceneH(t) {
  endPieces.forEach(({ u, off, d }) => {
    const p = P(t, 55.1 + d, 1.2);
    u.setAttribute('transform', `translate(${off[0] * (1 - p)} ${off[1] * (1 - p)})`);
    u.setAttribute('opacity', clamp(p * 1.6));
  });
  maskY($('h1'), t, 56.4, Infinity, 1.1);
  soft($('cta'), t, 57.0, Infinity, 30);
}

/* =================== Calques & transitions =================== */
const edge = H('<div class="abs" style="left:0;right:0;height:10px;background:var(--red);z-index:55;visibility:hidden"></div>', $('stage'));
const LAYERS = [
  { id: 'A', from: 0, to: 13.5 },
  { id: 'B', from: 12.6, to: 25.8, circle: [CFG.ring[0] + CFG.shift[0], CFG.ring[1] + CFG.shift[1]] },
  { id: 'C', from: 25.0, to: 33.4 },
  { id: 'D', from: 32.6, to: 39.4 },
  { id: 'E', from: 38.6, to: 44.9 },
  { id: 'F', from: 44.1, to: 49.9 },
  { id: 'G', from: 49.1, to: 55.4 },
  { id: 'H', from: 54.6, to: 61, circle: null },
];
LAYERS[7].circle = [window.MAP.cities['Abidjan'][0] + CFG.mapOff[0], window.MAP.cities['Abidjan'][1] + CFG.mapOff[1]];
const scenes = { A: sceneA, B: sceneB, C: sceneC, D: sceneD, E: sceneE, F: sceneF, G: sceneG, H: sceneH };
const light = { A: false, B: true, C: false, D: true, E: false, F: true, G: false, H: true };
const chapters = [[0, '01 · Le constat'], [5.4, '02 · Notre réponse'], [12.9, '03 · Notre ADN'], [18.7, '04 · Notre mission'], [25.3, '05 · Notre offre'], [32.9, '06 · Pour qui ?'], [38.9, '07 · Nos domaines'], [44.4, '08 · Nos valeurs'], [49.4, '09 · Notre vision']];

function render(t) {
  let top = 'A';
  let edgeY = null;
  LAYERS.forEach((L) => {
    const el = $(L.id);
    const on = t >= L.from && t < L.to;
    el.style.visibility = on ? 'visible' : 'hidden';
    if (!on) return;
    if (L.id !== 'A') {
      const p = P(t, L.from, L.circle ? 0.85 : 0.7, E.io4);
      if (L.circle) el.style.clipPath = `circle(${p * 2300}px at ${L.circle[0]}px ${L.circle[1]}px)`;
      else { el.style.clipPath = `inset(${100 * (1 - p)}% 0 0 0)`; if (p > 0 && p < 1) edgeY = SH * (1 - p); }
      if (p > 0.5) top = L.id;
    }
    scenes[L.id](t);
  });
  if (edgeY !== null) { edge.style.visibility = 'visible'; edge.style.top = `${edgeY - 5}px`; } else edge.style.visibility = 'hidden';
  // HUD
  const hud = $('hud');
  hud.style.color = light[top] ? '#031d59' : '#fff';
  hud.style.opacity = P(t, 0.6, 0.6, E.io) * (1 - P(t, 54.7, 0.3, E.io));
  $('chap').textContent = chapters.filter(([a]) => t >= a).pop()[1];
  $('bar').style.transform = `scaleX(${t / DUR})`;
}

window.renderAt = async (t) => { render(t); await document.fonts.ready; };
function fit() { const s = Math.min(innerWidth / SW, innerHeight / SH); $('stage').style.transform = `scale(${s})`; }
if (!location.search.includes('capture')) {
  fit(); addEventListener('resize', fit);
  const start = performance.now();
  const loop = (now) => { render(((now - start) / 1000) % DUR); requestAnimationFrame(loop); };
  requestAnimationFrame(loop);
} else render(0);
