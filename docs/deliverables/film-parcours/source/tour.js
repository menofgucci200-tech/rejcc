// REJCC — « Le parcours d'adhésion » : film de 2 min 30 calé sur la voix off (cues.js).
// Tout est fonction du temps : render(t). Format paysage 1920×1080 ou vertical 1080×1920 (window.VERTICAL).
const VERT = !!window.VERTICAL;
const SW = VERT ? 1080 : 1920, SH = VERT ? 1920 : 1080;
const DUR = window.FILM_END;
const C = window.CUES;
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
const S = (tag, attrs = {}, parent) => { const el = document.createElementNS(NS, tag); for (const [k, v] of Object.entries(attrs)) el.setAttribute(k, v); if (parent) parent.appendChild(el); return el; };
const H = (html, parent) => { const d = document.createElement('div'); d.innerHTML = html.trim(); const el = d.firstChild; parent.appendChild(el); return el; };
const stage = $('stage');
stage.style.width = SW + 'px'; stage.style.height = SH + 'px';

const maskY = (el, t, inAt, outAt = Infinity, dIn = 0.9, dOut = 0.55) => {
  if (!el) return;
  const y = (1 - P(t, inAt, dIn)) * 135 - P(t, outAt, dOut, E.io4) * 135;
  el.style.transform = `translateY(${y}%)`;
};
const soft = (el, t, inAt, outAt = Infinity, dy = 28) => {
  if (!el) return;
  const a = P(t, inAt, 0.8), b = P(t, outAt, 0.45, E.io);
  el.style.opacity = a * (1 - b);
  el.style.transform = `translateY(${(1 - a) * dy - b * dy}px)`;
};
const pop = (el, t, inAt, outAt = Infinity) => {
  const a = P(t, inAt, 0.7, E.back), b = P(t, outAt, 0.35, E.io);
  el.style.opacity = clamp(a * 1.4) * (1 - b);
  el.style.transform = `scale(${0.6 + 0.4 * a - 0.1 * b})`;
};
const draw = (el, p) => { el.style.strokeDasharray = '1 1'; el.style.strokeDashoffset = 1 - p; };
function icon(name, size, parent, { color = 'currentColor', sw = 1.7 } = {}) {
  const svg = S('svg', { viewBox: '0 0 24 24', width: size, height: size, class: 'ico', 'stroke-width': sw, stroke: color }, parent);
  svg.innerHTML = window.ICONS[name] || '';
  const parts = [...svg.querySelectorAll('path,circle,line,rect,polyline,ellipse,polygon')];
  parts.forEach((p) => { p.setAttribute('pathLength', 1); p.style.strokeDasharray = '1 1'; });
  svg.draw = (p) => parts.forEach((el) => (el.style.strokeDashoffset = 1 - p));
  svg.draw(1);
  return svg;
}
const cue = (i) => C[i];

/* ---------------- Constructeurs ---------------- */
function layer(cls) { const el = H(`<div class="layer ${cls}"></div>`, stage); return el; }

// Bloc titre : kicker + lignes Anton + paragraphe
function title(parent, { x, y, w = 760, kicker, lines, body, align = 'left', size = 92, dark = false }) {
  const col = dark ? '#fff' : 'var(--navy)';
  const el = H(`<div class="abs" style="left:${x}px;top:${y}px;width:${w}px;text-align:${align};color:${col}"></div>`, parent);
  const k = H(`<div class="kicker" style="${align === 'center' ? 'justify-content:center;' : ''}color:${dark ? 'rgba(255,255,255,.82)' : 'var(--navy)'}"><i></i><span class="mask"><span>${kicker}</span></span></div>`, el);
  const a = H(`<div class="anton" style="font-size:${size}px;margin-top:26px"></div>`, el);
  const ls = lines.map((l) => H(`<span class="mask"><span>${l}</span></span>`, a).firstChild);
  const b = body ? H(`<p class="body" style="margin-top:26px;color:${dark ? 'rgba(255,255,255,.78)' : 'var(--ink)'}">${body}</p>`, el) : null;
  return { el, rule: k.querySelector('i'), k: k.querySelector('.mask > span'), ls, b };
}
function titleAnim(tb, t, inAt, outAt = Infinity) {
  tb.rule.style.transform = `scaleX(${P(t, inAt, 0.7, E.io) * (1 - P(t, outAt, 0.4, E.io))})`;
  maskY(tb.k, t, inAt + 0.1, outAt);
  tb.ls.forEach((l, i) => maskY(l, t, inAt + 0.2 + i * 0.12, outAt + i * 0.04));
  soft(tb.b, t, inAt + 0.55, outAt);
}

// Ordinateur portable / téléphone avec une pile d'écrans
function laptop(parent, x, y, w) {
  const h = Math.round(w / 1.6);
  const el = H(`<div class="laptop" style="left:${x}px;top:${y}px;width:${w + 35}px"><div class="lid"><div class="screen" style="width:${w}px;height:${h}px"></div></div><div class="base"></div></div>`, parent);
  return { el, scr: el.querySelector('.screen'), sw: w, sh: h, sx: x + 17.5, sy: y + 17.5, imgW: 1440, items: [] };
}
function phone(parent, x, y, w) {
  const h = Math.round(w * 844 / 390);
  const el = H(`<div class="phone" style="left:${x}px;top:${y}px"><div class="island"></div><div class="screen" style="width:${w}px;height:${h}px"></div></div>`, parent);
  return { el, scr: el.querySelector('.screen'), sw: w, sh: h, sx: x + 13, sy: y + 13, imgW: 780, items: [] };
}
// Ajoute un écran : src, à l'instant at ; scroll = [de, à, début, durée] en pixels d'image
function scr(dev, src, at, scroll) {
  const img = H(`<img src="shots/${src}" alt="">`, dev.scr);
  // Zoom sur la zone utile des pages étroites (formulaire, suivi, connexion) côté ordinateur
  const zoom = dev.imgW === 1440 && /^d_adh_/.test(src) ? 1.42 : 1;
  img.style.transformOrigin = '50% 0';
  dev.items.push({ img, at, scroll, zoom });
  return dev;
}
function renderDev(dev, t) {
  let cur = 0;
  dev.items.forEach((it, k) => { if (t >= it.at) cur = k; });
  const k2 = dev.sw / dev.imgW;
  dev.items.forEach((it, k) => {
    const p = k === 0 ? 1 : P(t, it.at, 0.65, E.io4);
    const show = k === cur || (k === cur - 1 && P(t, dev.items[cur].at, 0.65, E.io4) < 1);
    it.img.style.display = show ? 'block' : 'none';
    if (!show) return;
    it.img.style.clipPath = k === cur && k > 0 ? `inset(0 0 0 ${100 * (1 - p)}%)` : 'none';
    let y = 0;
    if (it.scroll) y = lerp(it.scroll[0], it.scroll[1], P(t, it.scroll[2], it.scroll[3], E.io));
    it.img.style.transform = `translateY(${-y * k2}px) scale(${it.zoom})`;
  });
}
const devPt = (dev, ix, iy, z = 1) => { const k = dev.sw / dev.imgW; return [dev.sx + dev.sw / 2 + (ix - dev.imgW / 2) * k * z, dev.sy + iy * k * z]; };
function devAnim(dev, t, inAt, outAt = Infinity, dy = 160) {
  const a = P(t, inAt, 1.1), b = P(t, outAt, 0.5, E.inExpo);
  dev.el.style.transform = `translateY(${(1 - a) * dy + b * dy * 1.4}px)`;
  dev.el.style.opacity = clamp(a * 1.5) * (1 - b);
}

/* ---------------- Curseur et clics ---------------- */
const cursor = H(`<svg id="cursor" viewBox="0 0 24 24"><path d="M4 2 L4 19 L8.6 14.8 L11.6 21.4 L14.4 20.1 L11.5 13.7 L17.6 13.4 Z" fill="#fff" stroke="#031d59" stroke-width="1.5" stroke-linejoin="round"/></svg>`, stage);
const ripple = H('<div id="ripple"></div>', stage);
const tracks = []; // { show:[a,b], keys:[[t,x,y]...], clicks:[t...], touch }
function renderCursor(t) {
  const tr = tracks.find((k) => t >= k.show[0] && t < k.show[1]);
  cursor.style.visibility = 'hidden'; ripple.style.visibility = 'hidden';
  if (!tr) return;
  let x = tr.keys[0][1], y = tr.keys[0][2];
  for (let i = 1; i < tr.keys.length; i++) {
    const [t0, x0, y0] = tr.keys[i - 1], [t1, x1, y1] = tr.keys[i];
    if (t >= t0) { const p = P(t, t0, t1 - t0, E.io); x = lerp(x0, x1, p); y = lerp(y0, y1, p); }
  }
  const fade = P(t, tr.show[0], 0.3, E.io) * (1 - P(t, tr.show[1] - 0.3, 0.3, E.io));
  let sc = 1;
  tr.clicks.forEach((tc) => {
    const d = t - tc;
    if (d > -0.12 && d < 0.18) sc = 0.82;
    if (d >= 0 && d < 0.6) {
      const q = d / 0.6;
      ripple.style.visibility = 'visible';
      const r = 14 + 46 * E.out3(q);
      ripple.style.left = `${x - r}px`; ripple.style.top = `${y - r}px`;
      ripple.style.width = ripple.style.height = `${2 * r}px`;
      ripple.style.opacity = 1 - q;
    }
  });
  if (tr.touch) {
    cursor.style.visibility = 'hidden';
    ripple.style.visibility = ripple.style.visibility; // le tap ne montre que l'onde
    return;
  }
  cursor.style.visibility = 'visible';
  cursor.style.opacity = fade;
  cursor.style.transform = `translate(${x - 6}px, ${y - 3}px) scale(${sc})`;
}

/* ---------------- Suivi d'étapes (6 étapes) ---------------- */
const STEPS = [['search', 'Découvrir'], ['file-text', 'Candidater'], ['eye', 'Suivre'], ['shield-check', 'Validation'], ['smartphone', 'Activer'], ['sparkles', 'Profiter']];
const tracker = H('<div id="tracker"></div>', stage);
const tks = STEPS.map(([ic, label], i) => {
  const el = H(`<div class="tk"><span class="d">${i + 1}</span><span class="l">${label}</span></div>`, tracker);
  return el;
});
if (VERT) { tracker.style.top = '120px'; tracker.style.flexWrap = 'wrap'; tracker.style.padding = '0 70px'; tracker.style.rowGap = '10px'; }
function renderTracker(t, active, dark, show) {
  tracker.style.visibility = show > 0 ? 'visible' : 'hidden';
  tracker.style.opacity = show;
  tks.forEach((el, i) => {
    const on = i === active, done = i < active;
    el.style.background = on ? (dark ? '#fff' : 'var(--navy)') : 'transparent';
    el.style.color = on ? (dark ? 'var(--navy)' : '#fff') : dark ? 'rgba(255,255,255,.6)' : 'rgba(3,29,89,.5)';
    el.style.border = on ? '0' : `1.5px solid ${dark ? 'rgba(255,255,255,.18)' : 'rgba(3,29,89,.14)'}`;
    const d = el.querySelector('.d');
    d.style.background = on ? 'var(--red)' : done ? (dark ? 'rgba(255,255,255,.18)' : 'rgba(3,29,89,.12)') : 'transparent';
    d.style.color = on ? '#fff' : 'inherit';
    d.textContent = done ? '✓' : String(i + 1);
    if (VERT) el.querySelector('.l').style.display = on ? 'inline' : 'none';
  });
}

/* ================= Scènes ================= */
const T = {
  s1: cue(2).start - 0.6, s2: cue(4).start - 0.9, s3: cue(5).start - 0.9, s4: cue(6).start - 0.8,
  s5: cue(12).start - 1.0, s6: cue(13).start - 0.9, s7: cue(14).start - 0.9, s8: cue(15).start - 0.9,
  s9: cue(16).start - 0.9, s10: cue(17).start - 0.9, s11: cue(18).start - 1.3,
};
const L = {}; // calques
const scenes = [];

// ---------- S0 : ouverture ----------
L.A = layer('navy');
{
  const mark = S('svg', { viewBox: '280 44 442 660', class: 'abs', style: VERT ? 'left:390px;top:330px;width:300px;height:448px;overflow:visible' : 'left:810px;top:150px;width:300px;height:448px;overflow:visible' }, L.A);
  const SYM = ['stem-r', 'stem-uc', 'arc', 'inner-c', 'bar-1', 'bar-2', 'cross-j'];
  const OFF = { 'stem-r': [-140, -110], 'stem-uc': [-110, 140], arc: [130, -110], 'inner-c': [170, 110], 'bar-1': [-230, 0], 'bar-2': [-230, 0], 'cross-j': [0, 230] };
  const pieces = window.LOGO.filter((p) => SYM.includes(p.id)).map((p, i) => ({ el: S('path', { d: p.d, fill: '#fff' }, mark), off: OFF[p.id], d: i * 0.08 }));
  const wordsY = VERT ? 900 : 680;
  const words = (VERT ? ['Une idée.', 'Un projet.', 'Une ambition ?'] : ['Une idée.', 'Un projet.', 'Une ambition ?']).map((w, i) => {
    const html = w.replace('.', '<b class="red">.</b>').replace('?', '<b class="red">?</b>');
    const pos = VERT ? `left:0;right:0;top:${wordsY + i * 150}px;text-align:center` : `left:${[210, 760, 1250][i]}px;top:${wordsY}px`;
    return H(`<div class="abs anton" style="${pos};font-size:${VERT ? 130 : 104}px"><span class="mask"><span>${html}</span></span></div>`, L.A).querySelector('.mask > span');
  });
  const urlY = VERT ? 1000 : 700;
  const bar = H(`<div class="abs" style="left:${VERT ? 90 : 560}px;top:${urlY}px;width:${VERT ? 900 : 800}px;height:96px;border-radius:999px;background:#fff;display:flex;align-items:center;gap:22px;padding:0 34px;color:var(--navy);font-weight:800;font-size:44px;box-shadow:0 30px 60px -30px rgba(0,0,0,.5)"><span class="lock"></span><span class="url"></span><span class="caret" style="width:3px;height:46px;background:var(--red)"></span></div>`, L.A);
  icon('shield-check', 40, bar.querySelector('.lock'), { color: '#1f9d55', sw: 2 });
  const cap1 = H(`<div class="abs center caslon" style="top:${urlY - 120}px;font-size:${VERT ? 56 : 58}px"><span class="mask"><span>Le REJCC vous ouvre ses portes.</span></span></div>`, L.A).querySelector('.mask > span');
  const cap2 = H(`<div class="abs center" style="top:${urlY + 140}px;font-weight:800;font-size:26px;letter-spacing:.24em;text-transform:uppercase;color:rgba(255,255,255,.75)"><span class="mask"><span>Tout commence en ligne</span></span></div>`, L.A).querySelector('.mask > span');
  scenes.push({ L: L.A, from: 0, to: T.s1 + 0.8, dark: true, chap: 'Introduction', f: (t) => {
    pieces.forEach(({ el, off, d }) => { const p = P(t, 0.25 + d, 1.3); el.setAttribute('transform', `translate(${off[0] * (1 - p)} ${off[1] * (1 - p)})`); el.setAttribute('opacity', clamp(p * 1.7)); });
    const up = P(t, 5.6, 0.8, E.io4);
    mark.style.transform = `translateY(${-up * (VERT ? 120 : 40)}px) scale(${1 - 0.25 * up})`;
    words.forEach((w, i) => maskY(w, t, cue(0).start + 0.1 + i * 0.85, 5.6 + i * 0.05));
    soft(bar, t, 6.1, T.s1 - 0.4, 30);
    const full = 'rejcc.site', n = Math.floor(clamp((t - 6.7) / 1.1) * full.length);
    bar.querySelector('.url').textContent = full.slice(0, n);
    bar.querySelector('.caret').style.opacity = Math.floor(t * 2.2) % 2 ? 1 : 0.15;
    maskY(cap1, t, cue(1).start + 0.1, T.s1 - 0.4); maskY(cap2, t, cue(1).start + 1.9, T.s1 - 0.35);
  } });
}

// ---------- S1 : la plateforme ----------
L.B = layer('cloud');
{
  const tb = title(L.B, VERT ? { x: 90, y: 220, w: 900, kicker: 'La plateforme', lines: ['Une plateforme,', 'trois espaces<b class="red">.</b>'], size: 100 } : { x: 150, y: 210, w: 640, kicker: 'La plateforme', lines: ['Une plateforme,', 'trois espaces<b class="red">.</b>'], size: 92 });
  const feats = [['globe', 'Un site pour découvrir', 'le réseau, ses activités, ses domaines'], ['user-check', 'Un espace membre pour grandir', 'formations, annuaire, opportunités'], ['users', 'Des outils pour la communauté', 'événements, groupes, marketplace']].map(([ic, b, s], i) => {
    const el = H(`<div class="feat abs" style="left:${VERT ? 90 : 150}px;top:${(VERT ? 520 : 520) + i * 116}px"><div class="ic" style="background:var(--navy);color:#fff"></div><div><b>${b}</b><span>${s}</span></div></div>`, L.B);
    icon(ic, 36, el.querySelector('.ic'), { color: '#fff' });
    return el;
  });
  const tags = ['Simple', 'Rapide', 'Accessible'].map((w, i) => H(`<div class="abs pill white" style="left:${(VERT ? 90 : 150) + i * 230}px;top:${VERT ? 900 : 900}px"><span style="width:10px;height:10px;border-radius:50%;background:var(--red);display:block"></span>${w}</div>`, L.B));
  const lap = laptop(L.B, VERT ? 60 : 830, VERT ? 1080 : 210, VERT ? 760 : 860);
  const ph = phone(L.B, VERT ? 760 : 1560, VERT ? 1260 : 420, VERT ? 250 : 270);
  const c2 = cue(2).start, c3 = cue(3).start;
  scr(lap, 'd_home_full.jpg', 0, [0, 2400, c2, 6]); scr(lap, 'd_membre_accueil.jpg', c2 + 3.3); scr(lap, 'd_membre_groupes.jpg', c2 + 5.6); scr(lap, 'd_home.jpg', c3 + 0.4);
  scr(ph, 'm_home_full.jpg', 0, [0, 3600, c2, 6]); scr(ph, 'm_membre_accueil.jpg', c2 + 3.4); scr(ph, 'm_membre_groupes.jpg', c2 + 5.7); scr(ph, 'm_home.jpg', c3 + 0.5);
  scenes.push({ L: L.B, from: T.s1, to: T.s2 + 0.8, chap: 'La plateforme', f: (t) => {
    titleAnim(tb, t, T.s1 + 0.5, T.s2 - 0.5);
    feats.forEach((el, i) => { soft(el, t, c2 + [0.9, 3.2, 5.6][i], T.s2 - 0.5 + i * 0.03, 30); el.querySelector('svg').draw(P(t, c2 + [0.9, 3.2, 5.6][i] + 0.1, 0.9, E.io)); });
    tags.forEach((el, i) => pop(el, t, c3 + 1.6 + i * 0.75, T.s2 - 0.4));
    devAnim(lap, t, T.s1 + 0.4, T.s2 - 0.5); devAnim(ph, t, T.s1 + 0.7, T.s2 - 0.45);
    renderDev(lap, t); renderDev(ph, t);
  } });
}

// ---------- S2 : le parcours en 6 étapes ----------
L.C = layer('navy');
{
  const tb = title(L.C, { x: 0, y: VERT ? 230 : 190, w: SW, kicker: 'Le parcours', lines: VERT ? ['6 étapes pour', 'nous rejoindre<b class="red">.</b>'] : ['6 étapes pour nous rejoindre<b class="red">.</b>'], align: 'center', size: VERT ? 100 : 96, dark: true });
  const svg = S('svg', { class: 'abs', width: SW, height: SH, viewBox: `0 0 ${SW} ${SH}`, style: 'left:0;top:0;overflow:visible' }, L.C);
  const descs = ['le site du réseau', 'le formulaire en ligne', "l'état de votre demande", 'par le bureau du REJCC', 'votre abonnement', 'de tout le réseau'];
  const pos = (i) => VERT ? [270 + (i % 2) * 540, 720 + Math.floor(i / 2) * 330] : [300 + i * 264, 610];
  const line = VERT ? null : S('line', { x1: 300, y1: 610, x2: 1620, y2: 610, stroke: 'rgba(255,255,255,.18)', 'stroke-width': 4 }, svg);
  const fill = VERT ? null : S('line', { x1: 300, y1: 610, x2: 300, y2: 610, stroke: '#ac0100', 'stroke-width': 4 }, svg);
  const nodes = STEPS.map(([ic, label], i) => {
    const [x, y] = pos(i);
    const c = S('circle', { cx: x, cy: y, r: 66, fill: '#031d59', stroke: 'rgba(255,255,255,.3)', 'stroke-width': 3 }, svg);
    const g = H(`<div class="abs" style="left:${x - 36}px;top:${y - 36}px;width:72px;height:72px;display:grid;place-items:center"></div>`, L.C);
    const ico = icon(ic, 46, g, { color: '#fff', sw: 1.7 });
    const lab = H(`<div class="abs" style="left:${x - 150}px;top:${y + 92}px;width:300px;text-align:center"><div class="anton" style="font-size:40px">${label}</div><div style="font-weight:600;font-size:21px;color:rgba(255,255,255,.7);margin-top:6px">${descs[i]}</div></div>`, L.C);
    const num = H(`<div class="abs" style="left:${x + 34}px;top:${y - 70}px;width:36px;height:36px;border-radius:50%;background:var(--red);display:grid;place-items:center;font-weight:800;font-size:18px">${i + 1}</div>`, L.C);
    return { c, g, ico, lab, num };
  });
  const c4 = cue(4).start;
  const at = (i) => c4 + 2.9 + i * 1.05 + (i === 5 ? 0.45 : 0);
  scenes.push({ L: L.C, from: T.s2, to: T.s3 + 0.8, dark: true, chap: 'Le parcours', f: (t) => {
    titleAnim(tb, t, T.s2 + 0.5, T.s3 - 0.4);
    nodes.forEach((n, i) => {
      const a = P(t, T.s2 + 0.9 + i * 0.12, 0.7, E.back), on = P(t, at(i), 0.4, E.io);
      n.c.setAttribute('r', 66 * clamp(a, 0, 1.2)); n.c.setAttribute('fill', on > 0.5 ? '#ac0100' : '#031d59'); n.c.setAttribute('stroke', on > 0.5 ? '#ac0100' : 'rgba(255,255,255,.3)');
      n.g.style.opacity = clamp(a); n.ico.draw(P(t, T.s2 + 1.1 + i * 0.12, 0.9, E.io));
      soft(n.lab, t, T.s2 + 1.2 + i * 0.12, T.s3 - 0.4); pop(n.num, t, T.s2 + 1.3 + i * 0.12, T.s3 - 0.4);
      const sc = 1 + 0.12 * Math.sin(Math.PI * clamp((t - at(i)) / 0.5));
      n.g.style.transform = `scale(${sc})`;
    });
    if (fill) fill.setAttribute('x2', lerp(300, 1620, clamp((t - at(0)) / (at(5) - at(0)))));
  } });
}

// ---------- S3 + S4 : découvrir puis candidater (mêmes appareils) ----------
L.D = layer('cloud');
const STEP_TITLES = ['Informations générales', 'Diocèse & paroisse', 'Profil', 'Compétences', 'Entrepreneuriat', 'Projet futur', 'Attentes', 'Récapitulatif'];
{
  const tb3 = title(L.D, VERT ? { x: 90, y: 300, w: 900, kicker: 'Étape 1 · Découvrir', lines: ["Tout commence", "sur l'accueil<b class=\"red\">.</b>"], body: 'Un clic sur « Adhérer » et le parcours démarre.', size: 100 } : { x: 150, y: 250, w: 600, kicker: 'Étape 1 · Découvrir', lines: ['Tout commence', "sur l'accueil<b class=\"red\">.</b>"], body: 'Un clic sur « Adhérer » et le parcours démarre.', size: 88 });
  const k4 = title(L.D, VERT ? { x: 90, y: 260, w: 900, kicker: 'Étape 2 · Candidater', lines: [], size: 10 } : { x: 150, y: 210, w: 600, kicker: 'Étape 2 · Candidater', lines: [], size: 10 });
  const counter = H(`<div class="abs anton" style="left:${VERT ? 90 : 150}px;top:${VERT ? 320 : 270}px;font-size:${VERT ? 150 : 150}px;color:var(--navy);line-height:1"><span class="mask"><span><span class="num">1</span><span style="color:rgba(3,29,89,.25)">/8</span></span></span></div>`, L.D);
  const prog = H(`<div class="abs" style="left:${VERT ? 90 : 150}px;top:${VERT ? 500 : 440}px;width:${VERT ? 900 : 560}px;height:8px;border-radius:4px;background:rgba(3,29,89,.1)"><div style="height:100%;width:0;border-radius:4px;background:var(--red)"></div></div>`, L.D);
  const list = H(`<div class="abs steplist" style="left:${VERT ? 70 : 132}px;top:${VERT ? 540 : 475}px;width:${VERT ? 940 : 600}px;${VERT ? 'display:grid;grid-template-columns:1fr 1fr;column-gap:10px' : ''}"></div>`, L.D);
  const rows = STEP_TITLES.map((s, i) => H(`<div class="row"><span class="n">${i + 1}</span><span>${s}</span></div>`, list));
  const adapt = H(`<div class="abs pill white" style="left:${VERT ? 120 : 830}px;top:${VERT ? 1640 : 880}px;z-index:4"><span style="color:var(--red)">✦</span> Le formulaire s'adapte à votre situation</div>`, L.D);
  const done = H(`<div class="abs" style="left:${VERT ? 90 : 150}px;top:${VERT ? 300 : 260}px;width:${VERT ? 900 : 620}px;color:var(--navy)"><div style="width:110px;height:110px;border-radius:50%;background:var(--green);display:grid;place-items:center"></div><div class="anton" style="font-size:${VERT ? 110 : 96}px;margin-top:30px">Demande<br>enregistrée<b class="red">.</b></div><p class="body" style="margin-top:22px;color:var(--ink)">C'est aussi simple que ça.</p></div>`, L.D);
  const check = icon('check', 64, done.firstChild, { color: '#fff', sw: 2.6 });
  const lap = laptop(L.D, VERT ? 60 : 780, VERT ? 1060 : 200, VERT ? 760 : 960);
  const ph = phone(L.D, VERT ? 770 : 1600, VERT ? 1250 : 440, VERT ? 240 : 250);
  const c5 = cue(5).start, c7 = cue(7).start, c8 = cue(8).start, c9 = cue(9).start, c10 = cue(10).start, c11 = cue(11).start;
  const click1 = c5 + 3.4;
  const sched = [ // [instant, image ordinateur, image mobile, étape]
    [click1 + 0.35, 'd_adh_01_etape1_vide.jpg', 'm_adh_01_etape1_vide.jpg', 0],
    [c7 + 0.6, 'd_adh_02_etape1_rempli.jpg', 'm_adh_02_etape1_rempli.jpg', 0],
    [c8 + 0.2, 'd_adh_04_etape2_rempli.jpg', 'm_adh_04_etape2_rempli.jpg', 1],
    [c8 + 1.7, 'd_adh_06_etape3_rempli.jpg', 'm_adh_06_etape3_rempli.jpg', 2],
    [c8 + 3.2, 'd_adh_08_etape4_rempli.jpg', 'm_adh_08_etape4_rempli.jpg', 3],
    [c9 + 0.2, 'd_adh_09_etape5_vide.jpg', 'm_adh_09_etape5_vide.jpg', 4],
    [c9 + 1.4, 'd_adh_10_etape5_rempli_oui.jpg', 'm_adh_10_etape5_rempli_oui.jpg', 4],
    [c9 + 4.3, 'd_adh_11_etape6.jpg', 'm_adh_11_etape6.jpg', 5],
    [c10 + 0.2, 'd_adh_13_etape7_rempli.jpg', 'm_adh_13_etape7_rempli.jpg', 6],
    [c10 + 3.0, 'd_adh_14_etape8_recap.jpg', 'm_adh_14_etape8_recap.jpg', 7],
  ];
  const submit = cue(10).end + 0.55;
  scr(lap, 'd_home.jpg', 0); scr(ph, 'm_home.jpg', 0);
  sched.forEach(([at, d, m], i) => { scr(lap, d, at, i === sched.length - 1 ? [0, 410, at + 0.8, 1.2] : null); scr(ph, m, at + 0.08); });
  scr(lap, 'd_adh_15_confirmation.jpg', submit + 0.4); scr(ph, 'm_adh_15_confirmation.jpg', submit + 0.48);
  // curseur : vers « Adhérer », puis vers « Envoyer ma demande »
  const [ax, ay] = devPt(lap, 1243, 36), [sx, sy] = devPt(lap, 828, 881 - 410, 1.42);
  tracks.push({ show: [c5 + 1.2, click1 + 0.7], keys: [[c5 + 1.2, lap.sx + lap.sw * 0.5, lap.sy + lap.sh * 0.6], [click1 - 0.1, ax, ay]], clicks: [click1] });
  tracks.push({ show: [cue(10).end - 1.2, submit + 0.6], keys: [[cue(10).end - 1.2, lap.sx + lap.sw * 0.55, lap.sy + lap.sh * 0.5], [submit - 0.1, sx, sy]], clicks: [submit] });
  const stepAt = (t) => { let s = 0; sched.forEach(([at, , , st]) => { if (t >= at) s = st; }); return s; };
  scenes.push({ L: L.D, from: T.s3, to: T.s5 + 0.8, chap: 'Découvrir · Candidater', step: (t) => (t < T.s4 ? 0 : 1), f: (t) => {
    titleAnim(tb3, t, T.s3 + 0.5, T.s4 - 0.3);
    k4.rule.style.transform = `scaleX(${P(t, T.s4, 0.7, E.io) * (1 - P(t, submit + 0.5, 0.4, E.io))})`; maskY(k4.k, t, T.s4 + 0.1, submit + 0.5);
    const st = stepAt(t);
    counter.querySelector('.num').textContent = st + 1;
    maskY(counter.querySelector('.mask > span'), t, T.s4 + 0.25, submit + 0.5);
    soft(prog, t, T.s4 + 0.4, submit + 0.5, 0);
    prog.firstChild.style.width = `${((st + (t >= submit ? 1 : 0.5)) / 8) * 100}%`;
    rows.forEach((r, i) => {
      soft(r, t, T.s4 + 0.5 + i * 0.07, submit + 0.5 + i * 0.02, 16);
      const on = i === st && t < submit, ok = i < st || t >= submit;
      r.style.background = on ? 'var(--navy)' : 'transparent';
      r.style.color = on ? '#fff' : ok ? 'var(--navy)' : 'rgba(3,29,89,.42)';
      r.querySelector('.n').textContent = ok ? '✓' : String(i + 1);
      r.querySelector('.n').style.background = ok ? 'var(--navy)' : on ? 'var(--red)' : 'transparent';
      r.querySelector('.n').style.borderColor = ok ? 'var(--navy)' : on ? 'var(--red)' : 'currentColor';
      r.querySelector('.n').style.color = ok || on ? '#fff' : 'inherit';
    });
    pop(adapt, t, c9 + 1.7, cue(9).end + 0.2);
    soft(done, t, submit + 0.7, T.s5 - 0.3, 30); check.draw(P(t, submit + 0.9, 0.7, E.io));
    devAnim(lap, t, T.s3 + 0.4, T.s5 - 0.5); devAnim(ph, t, T.s3 + 0.7, T.s5 - 0.45);
    renderDev(lap, t); renderDev(ph, t);
  } });
}

// ---------- Scènes « appareils + titre » génériques ----------
function deviceScene({ key, cls, from, to, kicker, lines, body, step, d, m, chap, extra }) {
  const dark = cls === 'navy';
  L[key] = layer(cls);
  const tb = title(L[key], VERT ? { x: 90, y: 300, w: 900, kicker, lines, body, size: 100, dark } : { x: 150, y: 260, w: 600, kicker, lines, body, size: 88, dark });
  const lap = laptop(L[key], VERT ? 60 : 780, VERT ? 1060 : 200, VERT ? 760 : 960);
  const ph = phone(L[key], VERT ? 770 : 1600, VERT ? 1250 : 440, VERT ? 240 : 250);
  d.forEach(([src, at, scroll]) => scr(lap, src, at, scroll));
  m.forEach(([src, at, scroll]) => scr(ph, src, at, scroll));
  const ex = extra ? extra({ L: L[key], lap, ph, dark }) : null;
  scenes.push({ L: L[key], from, to: to + 0.8, dark, chap, step: () => step, f: (t) => {
    titleAnim(tb, t, from + 0.5, to - 0.4);
    devAnim(lap, t, from + 0.4, to - 0.5); devAnim(ph, t, from + 0.7, to - 0.45);
    renderDev(lap, t); renderDev(ph, t);
    if (ex) ex(t);
  } });
}
const statusPill = (parent, txt, color, x, y) => H(`<div class="abs pill" style="left:${x}px;top:${y}px;background:${color};color:#fff;z-index:5;box-shadow:0 20px 40px -20px rgba(0,0,0,.5)"><span style="width:12px;height:12px;border-radius:50%;background:#fff;display:block"></span>${txt}</div>`, parent);

// S5 : suivre
{
  const c = cue(12).start;
  deviceScene({ key: 'E', cls: 'navy', from: T.s5, to: T.s6, step: 2, chap: 'Suivre', kicker: 'Étape 3 · Suivre', lines: ['Suivez votre', 'demande<b class="red">.</b>'], body: 'À tout moment, avec votre adresse e-mail.',
    d: [['d_suivi_attente_vide.jpg', 0], ['d_suivi_attente.jpg', c + 1.6]], m: [['m_suivi_attente_vide.jpg', 0], ['m_suivi_attente.jpg', c + 1.7]],
    extra: ({ L: P0 }) => { const p = statusPill(P0, 'En attente de validation', 'var(--amber)', VERT ? 90 : 830, VERT ? 1700 : 880); return (t) => pop(p, t, c + 2.3, T.s6 - 0.3); } });
}
// S6 : validation
{
  const c = cue(13).start, click = c + 3.2;
  deviceScene({ key: 'F', cls: 'cloud', from: T.s6, to: T.s7, step: 3, chap: 'Validation', kicker: 'Étape 4 · Validation', lines: ['Le bureau', 'valide<b class="red">.</b>'], body: 'Votre compte est créé et un message de bienvenue vous est envoyé.',
    d: [['d_admin_adhesions.jpg', 0], ['d_admin_adhesions_apres.jpg', click + 0.35]], m: [['m_suivi_attente.jpg', 0], ['m_suivi_acceptee.jpg', click + 1.0]],
    extra: ({ L: P0, lap }) => {
      const [x, y] = devPt(lap, 1231, 264);
      tracks.push({ show: [c + 1.0, click + 0.8], keys: [[c + 1.0, lap.sx + lap.sw * 0.45, lap.sy + lap.sh * 0.7], [click - 0.1, x, y]], clicks: [click] });
      const ok = statusPill(P0, 'Candidature approuvée', 'var(--green)', VERT ? 90 : 830, VERT ? 1700 : 880);
      const mail = H(`<div class="abs" style="left:${VERT ? 520 : 1290}px;top:${VERT ? 1660 : 130}px;width:${VERT ? 470 : 460}px;padding:22px 26px;border-radius:22px;background:#fff;box-shadow:0 30px 60px -30px rgba(3,29,89,.55);border:1.5px solid rgba(3,29,89,.1);display:flex;gap:18px;align-items:center;z-index:6;color:var(--navy)"><div class="ic" style="width:60px;height:60px;border-radius:16px;background:var(--red);display:grid;place-items:center;flex:none"></div><div><b style="font-size:22px;font-weight:800;display:block">Bienvenue au REJCC !</b><span style="font-size:18px;font-weight:600;color:#5b677a">Votre espace membre est prêt.</span></div></div>`, P0);
      icon('send', 30, mail.querySelector('.ic'), { color: '#fff', sw: 2 });
      return (t) => { pop(ok, t, click + 0.6, T.s7 - 0.3); soft(mail, t, c + 5.4, T.s7 - 0.3, -30); };
    } });
}
// S7 : connexion
{
  const c = cue(14).start, click = c + 1.6;
  deviceScene({ key: 'G', cls: 'navy', from: T.s7, to: T.s8, step: 4, chap: 'Connexion', kicker: 'Étape 5 · Activer', lines: ['Votre espace', 'vous attend<b class="red">.</b>'], body: 'Sur ordinateur comme sur votre téléphone.',
    d: [['d_connexion.jpg', 0], ['d_membre_accueil_nonabonne.jpg', click + 0.4]], m: [['m_connexion.jpg', 0], ['m_membre_accueil_nonabonne.jpg', click + 0.7]],
    extra: ({ lap, ph }) => {
      const [x, y] = devPt(lap, 783, 886); const [px, py] = devPt(ph, 553, 1593);
      tracks.push({ show: [T.s7 + 1.2, click + 0.7], keys: [[T.s7 + 1.2, lap.sx + lap.sw * 0.6, lap.sy + lap.sh * 0.5], [click - 0.1, x, y]], clicks: [click] });
      tracks.push({ show: [click + 0.7, click + 1.2], keys: [[click + 0.7, px, py]], clicks: [click + 0.72], touch: true });
      return null;
    } });
}
// S8 : abonnement
{
  const c = cue(15).start, click = c + 4.6;
  deviceScene({ key: 'Hb', cls: 'cloud', from: T.s8, to: T.s9, step: 4, chap: 'Abonnement', kicker: 'Étape 5 · Activer', lines: ['10 000 F CFA', 'par an<b class="red">.</b>'], body: 'Pour débloquer toutes les fonctionnalités du réseau.',
    d: [['d_abonnement.jpg', 0], ['d_membre_abonnement_actif.jpg', click + 1.1]], m: [['m_abonnement.jpg', 0], ['m_membre_abonnement_actif.jpg', click + 1.3]],
    extra: ({ L: P0, lap }) => {
      const [x, y] = devPt(lap, 1134, 251);
      tracks.push({ show: [c + 2.6, click + 0.7], keys: [[c + 2.6, lap.sx + lap.sw * 0.4, lap.sy + lap.sh * 0.6], [click - 0.1, x, y]], clicks: [click] });
      const chips = [['smartphone', 'Mobile Money'], ['shield-check', 'Carte bancaire'], ['check-circle', 'Paiement sécurisé']].map(([ic, w], i) => {
        const el = H(`<div class="abs pill white" style="left:${VERT ? 90 + (i % 2) * 420 : 150}px;top:${VERT ? 700 + Math.floor(i / 2) * 90 : 640 + i * 92}px"><span class="ii" style="display:grid;place-items:center;color:var(--red)"></span>${w}</div>`, P0);
        icon(ic, 28, el.querySelector('.ii'), { color: '#ac0100', sw: 2 }); return el;
      });
      const ok = statusPill(P0, 'Abonnement actif', 'var(--green)', VERT ? 90 : 830, VERT ? 1700 : 880);
      return (t) => { chips.forEach((el, i) => pop(el, t, c + 6.2 + i * 0.4, T.s9 - 0.3)); pop(ok, t, click + 1.5, T.s9 - 0.3); };
    } });
}

// ---------- S9 : la carte de membre ----------
L.I = layer('navy');
{
  const tb = title(L.I, VERT ? { x: 90, y: 300, w: 900, kicker: 'Étape 6 · Profiter', lines: ['Votre carte', 'de membre<b class="red">.</b>'], body: 'Numérique, avec un QR code vérifiable.', size: 100, dark: true } : { x: 150, y: 280, w: 600, kicker: 'Étape 6 · Profiter', lines: ['Votre carte', 'de membre<b class="red">.</b>'], body: 'Numérique, avec un QR code vérifiable.', size: 88, dark: true });
  const cw = VERT ? 760 : 620;
  const front = H(`<img class="abs" src="shots/card_front.jpg" style="left:${VERT ? 160 : 780}px;top:${VERT ? 800 : 200}px;width:${cw}px;border-radius:22px;box-shadow:0 50px 90px -40px rgba(0,0,0,.7)">`, L.I);
  const back = H(`<img class="abs" src="shots/card_back.jpg" style="left:${VERT ? 160 : 1200}px;top:${VERT ? 1260 : 610}px;width:${cw}px;border-radius:22px;box-shadow:0 50px 90px -40px rgba(0,0,0,.7)">`, L.I);
  const ph = phone(L.I, VERT ? 60 : 1580, VERT ? 1380 : 120, VERT ? 200 : 230);
  scr(ph, 'm_membre_carte.jpg', 0);
  const c = cue(16).start;
  scenes.push({ L: L.I, from: T.s9, to: T.s10 + 0.8, dark: true, chap: 'Votre carte', step: () => 5, f: (t) => {
    titleAnim(tb, t, T.s9 + 0.5, T.s10 - 0.4);
    const a = P(t, T.s9 + 0.6, 1.2), b = P(t, c + 1.6, 1.2), out = P(t, T.s10 - 0.5, 0.5, E.inExpo);
    front.style.opacity = clamp(a * 1.5) * (1 - out);
    front.style.transform = `perspective(1400px) rotateY(${(1 - a) * -24}deg) rotateX(${(1 - a) * 10}deg) translateY(${(1 - a) * 80 + out * 200}px)`;
    back.style.opacity = clamp(b * 1.5) * (1 - out);
    back.style.transform = `perspective(1400px) rotateY(${(1 - b) * 24}deg) translateY(${(1 - b) * 80 + out * 200}px) scale(${1 + 0.04 * Math.sin(Math.PI * P(t, c + 3, 1.2, E.io))})`;
    if (VERT) ph.el.style.display = 'none'; else { devAnim(ph, t, T.s9 + 1.0, T.s10 - 0.45); renderDev(ph, t); }
  } });
}

// ---------- S10 : les services ----------
L.J = layer('cloud');
{
  const tb = title(L.J, { x: 0, y: VERT ? 250 : 180, w: SW, kicker: 'Tout le réseau', lines: VERT ? ['À portée', 'de main<b class="red">.</b>'] : ['Tout le réseau, à portée de main<b class="red">.</b>'], align: 'center', size: VERT ? 100 : 80 });
  const items = [['d_membre_annuaire.jpg', 'Annuaire'], ['d_membre_messagerie.jpg', 'Messagerie'], ['d_membre_catalogue.jpg', 'Formations'], ['d_membre_evenements.jpg', 'Événements'], ['d_membre_groupes.jpg', 'Groupes sectoriels'], ['d_membre_marketplace.jpg', 'Marketplace'], ['d_membre_emplois.jpg', "Offres d'emploi"], ['d_membre_carte.jpg', 'Carte membre']];
  const cards = items.map(([src, lbl], i) => {
    const w = VERT ? 440 : 380, h = Math.round(w / 1.6);
    const x = VERT ? 80 + (i % 2) * 480 : 150 + (i % 4) * 420, y = VERT ? 620 + Math.floor(i / 2) * 330 : 400 + Math.floor(i / 4) * 330;
    return H(`<div class="browser" style="left:${x}px;top:${y}px;width:${w}px"><div class="bar"><i></i><i></i><i></i></div><div class="shot" style="height:${h}px"><img src="shots/${src}"></div><div class="lbl">${lbl}</div></div>`, L.J);
  });
  const c = cue(17).start;
  scenes.push({ L: L.J, from: T.s10, to: T.s11 + 0.8, chap: 'Les services', step: () => 5, f: (t) => {
    titleAnim(tb, t, T.s10 + 0.5, T.s11 - 0.4);
    cards.forEach((el, i) => {
      soft(el, t, T.s10 + 0.8 + i * 0.08, T.s11 - 0.4 + i * 0.02, 40);
      const act = P(t, c + 0.3 + i * 0.85, 0.3, E.io) * (1 - P(t, c + 1.15 + i * 0.85, 0.4, E.io));
      el.style.boxShadow = `0 30px 60px -30px rgba(3,29,89,.5), 0 0 0 ${4 * act}px #ac0100`;
      el.style.transform += ` scale(${1 + 0.05 * act})`;
    });
  } });
}

// ---------- S11 : signature ----------
L.K = layer('navy');
{
  const lw = VERT ? 400 : 300, lh = lw * 950 / 538;
  const logo = S('svg', { viewBox: '236 28 538 950', class: 'abs', style: `left:${(SW - lw) / 2}px;top:${VERT ? 300 : 110}px;width:${lw}px;height:${lh}px;overflow:visible` }, L.K);
  const OFF2 = { 'stem-r': [-90, -70], 'stem-uc': [-70, 90], arc: [80, -70], 'inner-c': [110, 70], 'bar-1': [-150, 0], 'bar-2': [-150, 0], 'cross-j': [0, 150] };
  const pcs = window.LOGO.map((p, i) => ({ u: S('path', { d: p.d, fill: '#fff' }, logo), off: OFF2[p.id] || (p.id.startsWith('w-') ? [0, 60] : [0, 40]), d: p.id.startsWith('w-') ? 0.55 + i * 0.03 : p.id.startsWith('line') ? 0.9 + (i - 12) * 0.08 : i * 0.07 }));
  const y0 = VERT ? 300 + lh + 60 : 110 + lh + 40;
  const slogan = H(`<div class="abs center caslon" style="top:${y0}px;font-size:${VERT ? 66 : 58}px"><span class="mask"><span>Ensemble pour l'excellence<b class="red">.</b></span></span></div>`, L.K).querySelector('.mask > span');
  const cta = H(`<div class="abs center" style="top:${y0 + 110}px;display:flex;justify-content:center"><div style="display:flex;align-items:center;gap:20px;background:var(--red);color:#fff;border-radius:999px;padding:24px 44px;font-weight:800;font-size:31px;box-shadow:0 26px 50px -22px rgba(0,0,0,.6)">Rejoignez le réseau <span style="opacity:.6">·</span> rejcc.site</div></div>`, L.K);
  const note = H(`<div class="abs center" style="top:${y0 + 230}px;font-weight:700;font-size:22px;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.7)">Adhésion 100 % en ligne</div>`, L.K);
  const c18 = cue(18).start;
  scenes.push({ L: L.K, from: T.s11, to: DUR + 1, dark: true, chap: '', circle: true, f: (t) => {
    pcs.forEach(({ u, off, d }) => { const p = P(t, T.s11 + 0.5 + d, 1.2); u.setAttribute('transform', `translate(${off[0] * (1 - p)} ${off[1] * (1 - p)})`); u.setAttribute('opacity', clamp(p * 1.6)); });
    maskY(slogan, t, c18 + 1.2, Infinity, 1.1);
    soft(cta, t, cue(19).start + 0.2, Infinity, 30); soft(note, t, cue(19).start + 0.8, Infinity, 20);
  } });
}

/* ---------------- HUD ---------------- */
const hud = H(`<div class="layer" id="hud"><span class="c tl"></span><span class="c tr"></span><span class="c bl"></span><span class="c br"></span>
  <span class="m m1">REJCC</span><span class="m m2" id="chap"></span><span class="m m3">${VERT ? "Le parcours d'adhésion" : "Le parcours d'adhésion · ordinateur et mobile"}</span><span class="m m4">rejcc.site</span><span class="bar" id="bar"></span></div>`, stage);
stage.appendChild(tracker); stage.appendChild(ripple); stage.appendChild(cursor);
const edge = H('<div class="abs" style="left:0;right:0;height:10px;background:var(--red);z-index:55;visibility:hidden"></div>', stage);

function render(t) {
  let top = scenes[0], edgeY = null;
  scenes.forEach((sc, i) => {
    const on = t >= sc.from && t < sc.to;
    sc.L.style.visibility = on ? 'visible' : 'hidden';
    if (!on) return;
    if (i > 0) {
      const p = P(t, sc.from, sc.circle ? 0.9 : 0.7, E.io4);
      if (sc.circle) sc.L.style.clipPath = `circle(${p * 1.25 * Math.hypot(SW, SH) / 2}px at ${SW / 2}px ${SH / 2}px)`;
      else { sc.L.style.clipPath = `inset(${100 * (1 - p)}% 0 0 0)`; if (p > 0 && p < 1) edgeY = SH * (1 - p); }
      if (p > 0.5) top = sc;
    }
    sc.f(t);
  });
  if (edgeY !== null) { edge.style.visibility = 'visible'; edge.style.top = `${edgeY - 5}px`; } else edge.style.visibility = 'hidden';
  const dark = !!top.dark;
  hud.style.color = dark ? '#fff' : '#031d59';
  hud.style.opacity = P(t, 0.6, 0.6, E.io) * (1 - P(t, T.s11, 0.4, E.io));
  $('chap').textContent = top.chap || '';
  $('bar').style.transform = `scaleX(${t / DUR})`;
  const show = P(t, T.s3 + 0.3, 0.5, E.io) * (1 - P(t, T.s11 - 0.2, 0.4, E.io));
  renderTracker(t, top.step ? top.step(t) : 0, dark, t >= T.s3 && t < T.s11 + 0.3 ? show : 0);
  renderCursor(t);
}

window.renderAt = async (t) => { render(t); await document.fonts.ready; };
function fit() { const s = Math.min(innerWidth / SW, innerHeight / SH); stage.style.transform = `scale(${s})`; }
if (!location.search.includes('capture')) {
  fit(); addEventListener('resize', fit);
  const start = performance.now();
  const loop = (now) => { render(((now - start) / 1000) % DUR); requestAnimationFrame(loop); };
  requestAnimationFrame(loop);
} else render(0);
