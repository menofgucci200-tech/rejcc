// Film de présentation REJCC — 60 s, 1920×1080.
// Tout est une fonction pure du temps : render(t). La capture appelle
// window.renderAt(t) image par image ; l'aperçu joue en temps réel.
const DUR = 60;
const $ = (id) => document.getElementById(id);
const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
const E = {
  lin: (t) => t,
  outExpo: (t) => (t >= 1 ? 1 : 1 - Math.pow(2, -10 * t)),
  inExpo: (t) => (t <= 0 ? 0 : Math.pow(2, 10 * t - 10)),
  io: (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2),
  io4: (t) => (t < 0.5 ? 8 * t ** 4 : 1 - Math.pow(-2 * t + 2, 4) / 2),
  out3: (t) => 1 - Math.pow(1 - t, 3),
  back: (t) => 1 + 2.2 * Math.pow(t - 1, 3) + 1.2 * Math.pow(t - 1, 2),
};
const P = (t, a, d, e = E.outExpo) => e(clamp((t - a) / d));

const tf = (el, x = 0, y = 0, s = 1) => { el.style.transform = `translate(${x}px,${y}px) scale(${s})`; };
const op = (el, o) => { el.style.opacity = o; };
const vis = (el, on) => { el.style.visibility = on ? 'visible' : 'hidden'; };
// Texte dans un masque : entre par le bas, sort par le haut.
const maskY = (el, t, inAt, outAt = Infinity, dIn = 0.9, dOut = 0.55) => {
  const y = (1 - P(t, inAt, dIn)) * 135 - P(t, outAt, dOut, E.io4) * 135;
  el.style.transform = `translateY(${y}%)`;
};
// Variante « push » (entrée/sortie à la même courbe).
const pushY = (el, t, inAt, outAt, d = 0.55) => {
  const y = (1 - P(t, inAt, d, E.io4)) * 135 - P(t, outAt, d, E.io4) * 135;
  el.style.transform = `translateY(${y}%)`;
};
const soft = (el, t, inAt, outAt = Infinity, dy = 24) => {
  const a = P(t, inAt, 0.9), b = P(t, outAt, 0.45, E.io);
  el.style.opacity = a * (1 - b);
  el.style.transform = `translateY(${(1 - a) * dy - b * dy}px)`;
};
const rule = (el, t, inAt, outAt = Infinity) => {
  el.style.transform = `scaleX(${P(t, inAt, 0.7, E.io) * (1 - P(t, outAt, 0.4, E.io))})`;
};

/* ---------- Séquences d'images ---------- */
const pending = [];
function setSrc(img, src) {
  if (img.dataset.src === src) return;
  img.dataset.src = src;
  img.src = src;
  pending.push(img.decode().catch(() => {}));
}
const pad = (n) => String(n).padStart(4, '0');

/* ---------- Constellation (fond bleu) ---------- */
const NS = 'http://www.w3.org/2000/svg';
const net = $('net');
const C = { x: 1460, y: 540 };
const orbits = [230, 340, 450, 560];
const nodes = [];
[[230, [-150, -40, 60, 170]], [340, [-112, -8, 96, 142, 212]], [450, [-74, 22, 122, 188, 248]], [560, [-30, 80, 160, 230]]].forEach(([r, angs]) =>
  angs.forEach((a) => nodes.push({ r, a: (a * Math.PI) / 180 })));
const pt = (r, a) => [C.x + r * Math.cos(a), C.y + r * Math.sin(a)];
const netEls = { orbits: [], links: [], nodes: [], pulses: [] };
orbits.forEach((r) => {
  const c = document.createElementNS(NS, 'circle');
  c.setAttribute('cx', C.x); c.setAttribute('cy', C.y); c.setAttribute('r', r);
  c.setAttribute('pathLength', 1); c.setAttribute('stroke', 'rgba(255,255,255,.12)'); c.setAttribute('stroke-dasharray', '1 1');
  net.appendChild(c); netEls.orbits.push(c);
});
nodes.forEach((n) => {
  const [x1, y1] = pt(150, n.a), [x2, y2] = pt(n.r, n.a);
  const l = document.createElementNS(NS, 'line');
  Object.entries({ x1: x2, y1: y2, x2: x1, y2: y1, pathLength: 1, stroke: 'rgba(255,255,255,.14)', 'stroke-dasharray': '1 1' }).forEach(([k, v]) => l.setAttribute(k, v));
  net.appendChild(l); netEls.links.push(l);
});
nodes.forEach((n, i) => {
  const [x, y] = pt(n.r, n.a);
  const g = document.createElementNS(NS, 'g');
  g.innerHTML = `<circle cx="${x}" cy="${y}" r="${i % 3 ? 6 : 9}" fill="#031d59" stroke="rgba(255,255,255,.35)"/><circle cx="${x}" cy="${y}" r="3" fill="rgba(255,255,255,.85)"/>`;
  g.style.transformOrigin = `${x}px ${y}px`;
  net.appendChild(g); netEls.nodes.push(g);
});
[1, 4, 6, 9, 11, 14, 16].forEach((ni, k) => {
  const n = nodes[ni];
  const c = document.createElementNS(NS, 'circle');
  c.setAttribute('r', 4.5); c.setAttribute('fill', '#ac0100');
  net.appendChild(c); netEls.pulses.push({ c, n, d: k * 0.47 });
});
function renderNet(t) {
  const t0 = 6.1;
  netEls.orbits.forEach((c, i) => c.setAttribute('stroke-dashoffset', 1 - P(t, t0 + i * 0.15, 1.6, E.io)));
  netEls.links.forEach((l, i) => l.setAttribute('stroke-dashoffset', 1 - P(t, t0 + 0.5 + i * 0.04, 1.2, E.io)));
  netEls.nodes.forEach((g, i) => { const p = P(t, t0 + 0.8 + i * 0.04, 0.6, E.back); g.style.transform = `scale(${p})`; g.style.opacity = clamp(p); });
  netEls.pulses.forEach(({ c, n, d }) => {
    const local = t - (t0 + 2 + d);
    if (local < 0) { c.setAttribute('opacity', 0); return; }
    const ph = (local % 3.3) / 3.3, e = E.io(ph);
    const [x, y] = pt(n.r + (150 - n.r) * e, n.a);
    c.setAttribute('cx', x); c.setAttribute('cy', y);
    c.setAttribute('opacity', ph < 0.12 ? ph / 0.12 : ph > 0.85 ? (1 - ph) / 0.15 : 1);
  });
}

/* ---------- S5 : pages ---------- */
const pages = [
  ['À propos', 'a-propos', 'Notre histoire, notre mission, notre vision.'],
  ['Activités', 'activites', 'Formations, mentorat, conférences, networking.'],
  ['Domaines', 'domaines', "33 domaines d'activité, réunis en 9 pôles."],
  ['Événements', 'evenements', "L'agenda du réseau, avec inscription en ligne."],
  ['Adhésion', 'adhesion', 'Rejoindre le réseau en ligne, étape par étape.'],
];
const S5T = (i) => 37.0 + 1.55 * i;
const s5 = { labels: [], descs: [], lap: [], ph: [], dots: [] };
pages.forEach(([label, slug, desc], i) => {
  const m = document.createElement('span'); m.className = 'mask abs'; m.style.cssText = 'left:0;top:0;white-space:nowrap';
  m.innerHTML = `<span>${label}<b class="red">.</b></span>`; $('s5labels').appendChild(m); s5.labels.push(m.firstChild);
  const d = document.createElement('p'); d.className = 'abs'; d.style.cssText = 'left:0;top:0;white-space:nowrap'; d.textContent = desc;
  $('s5descs').appendChild(d); s5.descs.push(d);
  const a = document.createElement('img'); a.src = `assets/shots/d_${slug}.jpg`; $('lap2screen').appendChild(a); s5.lap.push(a);
  const b = document.createElement('img'); b.src = `assets/shots/m_${slug}.jpg`; $('ph2screen').appendChild(b); s5.ph.push(b);
  const dot = document.createElement('i'); dot.style.cssText = 'display:block;height:6px;border-radius:3px'; $('s5dots').appendChild(dot); s5.dots.push(dot);
});

/* ---------- S6 : valeurs ---------- */
const values = [['Foi', '#031d59', '#fff'], ['Excellence', '#ac0100', '#fff'], ['Solidarité', '#f4f6f8', '#031d59'], ['Impact', '#031d59', '#fff'], ['Création<br>de richesse', '#ac0100', '#fff'], ['', '#031d59', '#fff']];
const S6T = (i) => 45.2 + 1.5 * i;
const s6 = values.map(([word, bg, fg], i) => {
  const panel = document.createElement('div'); panel.className = 'layer'; panel.style.background = bg; panel.style.color = fg;
  if (word) {
    const two = word.includes('<br>');
    panel.innerHTML = `<div class="abs" style="left:0;right:0;top:${two ? 250 : 330}px;text-align:center">
      <div class="kicker" style="justify-content:center;opacity:.8"><span class="mask"><span>Valeur 0${i + 1} / 05</span></span></div>
      <div class="anton" style="font-size:${two ? 250 : 330}px;margin-top:30px;line-height:.98">${word.split('<br>').map((w, k) => `<span class="mask"><span>${w}${k === word.split('<br>').length - 1 ? `<b style="color:${bg === '#ac0100' ? '#fff' : '#ac0100'}">.</b>` : ''}</span></span>`).join('')}</div></div>`;
  }
  $('s6').appendChild(panel);
  return { panel, inners: [...panel.querySelectorAll('.mask > span')] };
});

/* ---------- S8 : logo final ---------- */
const offsets = { 'stem-r': [-90, -70], 'stem-uc': [-70, 90], arc: [80, -70], 'inner-c': [110, 70], 'bar-1': [-150, 0], 'bar-2': [-150, 0], 'cross-j': [0, 150] };
const endPieces = window.LOGO.map((p, i) => {
  const u = document.createElementNS(NS, 'path');
  u.setAttribute('d', p.d); u.setAttribute('fill', p.c === 'red' ? '#ac0100' : '#031d59');
  $('endLogo').appendChild(u);
  const off = offsets[p.id] || (p.id.startsWith('w-') ? [0, 60] : [0, 40]);
  return { u, off, d: p.id.startsWith('w-') ? 0.55 + i * 0.03 : p.id.startsWith('line') ? 0.9 + (i - 12) * 0.08 : i * 0.07 };
});

/* ---------- Rendu ---------- */
function render(t) {
  // S1 — intro réelle du site
  vis($('s1'), t < 5.9);
  if (t < 5.9) setSrc($('introImg'), `assets/seq_intro/${pad(Math.min(177, Math.floor(t * 30)))}.jpg`);

  // Fond bleu + constellation
  renderNet(t);
  op($('net'), t < 25 ? 0.7 : t < 37 ? 0.45 : 0.7);

  // S2 — manifeste
  vis($('s2'), t >= 5.9 && t < 14.8);
  pushY($('w1').firstChild, t, 6.55, 7.95);
  pushY($('w2').firstChild, t, 7.95, 9.3);
  pushY($('w3').firstChild, t, 9.3, 10.55);
  rule($('visRule'), t, 10.6, 13.5);
  maskY($('visK'), t, 10.7, 13.5);
  ['v1', 'v2', 'v3'].forEach((id, i) => maskY($(id), t, 10.85 + i * 0.15, 13.55 + i * 0.06, 1.1));

  // Fond gris : entre (14.0), sort (24.7), revient (35.8)
  let top = 100, bottom = 0;
  if (t >= 14 && t < 24.7) top = 100 * (1 - P(t, 14, 0.7, E.io4));
  else if (t >= 24.7 && t < 35.8) { top = 0; bottom = 100 * P(t, 24.7, 0.6, E.io4); }
  else if (t >= 35.8) top = 100 * (1 - P(t, 35.8, 0.7, E.io4));
  $('bgCloud').style.clipPath = `inset(${top}% 0 ${bottom}% 0)`;
  vis($('bgCloud'), top < 100 && bottom < 100 && t < 53);

  // S3 — ordinateur
  const on3 = t >= 14.2 && t < 25.3;
  vis($('s3'), on3);
  if (on3) {
    const a = P(t, 14.6, 1.2), b = P(t, 24.6, 0.55, E.inExpo);
    tf($('lap1'), 0, 200 * (1 - a) + 260 * b); op($('lap1'), clamp(a * 1.4) * (1 - b));
    let frame = 0;
    if (t >= 15.3 && t < 19.3) frame = Math.floor((t - 15.3) * 45);
    else if (t >= 19.3) frame = 176 + Math.floor((t - 19.3) * 30);
    setSrc($('lap1seq'), `assets/seq_d/${pad(clamp(frame, 0, 236))}.jpg`);
    const full = t >= 21.3;
    vis($('lap1full'), full);
    $('lap1full').style.transform = `translateY(${-P(t, 21.7, 2.8, E.io) * 2170}px)`;
    rule($('s3rule'), t, 14.9, 24.5); maskY($('s3k'), t, 15.0, 24.5);
    maskY($('s3a1'), t, 15.1, 20.9); maskY($('s3a2'), t, 15.22, 20.96);
    maskY($('s3b1'), t, 21.3, 24.5); maskY($('s3b2'), t, 21.42, 24.56);
    soft($('s3pa'), t, 15.6, 20.8); soft($('s3pb'), t, 21.7, 24.45);
  }

  // S4 — mobile
  const on4 = t >= 24.9 && t < 36.4;
  vis($('s4'), on4);
  if (on4) {
    [['phC', 25.4], ['phL', 25.6], ['phR', 25.75]].forEach(([id, at], i) => {
      const a = P(t, at, 1.3), b = P(t, 35.65 + i * 0.06, 0.6, E.inExpo);
      tf($(id), 0, 420 * (1 - a) + 700 * b); op($(id), clamp(a * 1.5));
    });
    const f = t < 26 ? 0 : Math.floor((t - 26) * 30);
    setSrc($('phCimg'), `assets/seq_m/${pad(clamp(f, 0, 269))}.jpg`);
    $('phLimg').style.transform = `translateY(${-P(t, 26.6, 8.4, E.io) * 2300}px)`;
    $('phRimg').style.transform = `translateY(${-P(t, 26.9, 8.2, E.io) * 2700}px)`;
    rule($('s4rule'), t, 25.7, 35.4); maskY($('s4k'), t, 25.8, 35.4);
    maskY($('s4a1'), t, 25.9, 35.45); maskY($('s4a2'), t, 26.02, 35.5);
    soft($('s4p'), t, 26.4, 35.4);
  }

  // S5 — tout le réseau
  const on5 = t >= 36.0 && t < 45.6;
  vis($('s5'), on5);
  if (on5) {
    const a = P(t, 36.5, 1.2), b = P(t, 44.85, 0.5, E.inExpo);
    tf($('lap2'), 0, 200 * (1 - a) + 300 * b); op($('lap2'), clamp(a * 1.4) * (1 - b));
    const c = P(t, 36.75, 1.2), d = P(t, 44.9, 0.5, E.inExpo);
    tf($('ph2'), 0, 300 * (1 - c) + 400 * d); op($('ph2'), clamp(c * 1.4) * (1 - d));
    rule($('s5rule'), t, 36.7, 44.7); maskY($('s5k'), t, 36.8, 44.7);
    pages.forEach((_, i) => {
      const ti = S5T(i), tn = i < pages.length - 1 ? S5T(i + 1) : 44.7;
      pushY(s5.labels[i], t, ti, tn);
      const o = P(t, ti + 0.15, 0.4, E.io) * (1 - P(t, tn - 0.1, 0.3, E.io));
      s5.descs[i].style.opacity = o; s5.descs[i].style.transform = `translateY(${(1 - o) * 12}px)`;
      const r1 = i === 0 ? 1 : P(t, ti, 0.75, E.io4), r2 = i === 0 ? 1 : P(t, ti + 0.08, 0.75, E.io4);
      s5.lap[i].style.clipPath = `inset(0 0 0 ${100 * (1 - r1)}%)`;
      s5.ph[i].style.clipPath = `inset(0 0 0 ${100 * (1 - r2)}%)`;
      const active = t >= ti && t < tn + 0.05;
      s5.dots[i].style.width = active ? '46px' : '16px';
      s5.dots[i].style.background = active ? '#ac0100' : 'rgba(3,29,89,.2)';
    });
    op($('s5dots'), P(t, 37.0, 0.5, E.io) * (1 - P(t, 44.6, 0.3, E.io)));
  }

  // S6 — valeurs
  const on6 = t >= 45.2 && t < 56.9;
  vis($('s6'), on6);
  if (on6) {
    s6.forEach(({ panel, inners }, i) => {
      const ti = S6T(i);
      const p = P(t, ti, 0.6, E.io4);
      panel.style.clipPath = `inset(${100 * (1 - p)}% 0 0 0)`;
      vis(panel, p > 0);
      inners.forEach((el, k) => maskY(el, t, ti + 0.15 + k * 0.08, Infinity, 0.9));
    });
  }

  // S7 — chiffres
  const on7 = t >= 52.9 && t < 57.2;
  vis($('s7'), on7);
  if (on7) {
    maskY($('n1'), t, 53.0); maskY($('n2'), t, 53.15);
    $('n1').textContent = Math.round(350 * P(t, 53.0, 1.7, E.out3)) + '+';
    $('n2').textContent = Math.round(33 * P(t, 53.15, 1.7, E.out3));
    maskY($('n1l'), t, 53.4); maskY($('n2l'), t, 53.55);
    maskY($('n3'), t, 54.5, Infinity, 1.1);
  }

  // S8 — signature finale
  const on8 = t >= 56.2;
  vis($('s8'), on8);
  if (on8) {
    $('s8').style.clipPath = `circle(${P(t, 56.2, 0.85, E.io4) * 1250}px at 960px 540px)`;
    endPieces.forEach(({ u, off, d }) => {
      const p = P(t, 56.65 + d, 1.2);
      u.setAttribute('transform', `translate(${off[0] * (1 - p)} ${off[1] * (1 - p)})`);
      u.setAttribute('opacity', clamp(p * 1.6));
    });
    maskY($('e1'), t, 57.75, Infinity, 1.1);
    soft($('cta'), t, 58.3, Infinity, 30);
  }

  // HUD
  const hudOn = t >= 6.2 && t < 56.2;
  vis($('hud'), hudOn);
  op($('hud'), P(t, 6.2, 0.6, E.io) * (1 - P(t, 55.9, 0.3, E.io)));
  const light = (t >= 14.35 && t < 25.0) || (t >= 36.15 && t < 45.4) || (t >= S6T(2) + 0.3 && t < S6T(3) + 0.3);
  $('hud').style.color = light ? '#031d59' : '#fff';
  const chapters = [[5.9, '01 — Le réseau'], [14.35, '02 — Sur ordinateur'], [25.0, '03 — Sur mobile'], [36.15, '04 — Tout le réseau'], [45.4, '05 — Nos valeurs'], [53.0, '06 — En chiffres']];
  $('chap').textContent = chapters.filter(([a]) => t >= a).pop()?.[1] ?? '';
  $('bar').style.transform = `scaleX(${t / 61.9})`;
}

// Calage sur la voix off : temps du film (sortie) → temps des animations,
// interpolation linéaire entre les repères de anchors.js (s'il est chargé).
function remap(t) {
  const A = window.ANCHORS;
  if (!A) return t;
  if (t <= A[0][0]) return A[0][1];
  for (let i = 1; i < A.length; i++) {
    if (t <= A[i][0]) {
      const [o0, r0] = A[i - 1], [o1, r1] = A[i];
      return r0 + ((t - o0) / (o1 - o0)) * (r1 - r0);
    }
  }
  return A[A.length - 1][1];
}
const OUT_DUR = window.FILM_END || DUR;

window.renderAt = async (t) => {
  pending.length = 0;
  render(remap(t));
  await Promise.all(pending);
  await document.fonts.ready;
};

// Aperçu temps réel (sauf en capture)
function fit() {
  const s = Math.min(innerWidth / 1920, innerHeight / 1080);
  $('stage').style.transform = `scale(${s})`;
}
if (!location.search.includes('capture')) {
  fit(); addEventListener('resize', fit);
  const start = performance.now();
  const loop = (now) => { render(remap(((now - start) / 1000) % OUT_DUR)); requestAnimationFrame(loop); };
  requestAnimationFrame(loop);
} else {
  render(0);
}
