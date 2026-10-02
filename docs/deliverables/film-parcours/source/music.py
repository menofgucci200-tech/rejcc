# Musique originale du film « Le parcours d'adhésion » — synthèse 100 % code (aucun échantillon externe).
# Afro-pop douce : kalimba, marimba, nappe, basse ronde, percussions légères. 100 BPM, la mineur / do majeur.
import json, numpy as np, soundfile as sf
from scipy.signal import fftconvolve

SR = 44100
END = json.load(open('cues.json'))['end']
N = int((END + 0.5) * SR)
L = np.zeros(N); R = np.zeros(N)
BPM = 100; BEAT = 60 / BPM; BAR = 4 * BEAT
rng = np.random.default_rng(7)

def hz(m): return 440.0 * 2 ** ((m - 69) / 12)
def env_exp(n, dec, att=0.004):
    t = np.arange(n) / SR; a = np.minimum(1, t / att); return a * np.exp(-t * dec)
def put(sig, t0, gain=1.0, pan=0.0, busL=None, busR=None):
    i = int(t0 * SR)
    if i >= N: return
    sig = sig[: N - i] * gain
    bl = L if busL is None else busL; br = R if busR is None else busR
    bl[i:i + len(sig)] += sig * np.sqrt(0.5 * (1 - pan)); br[i:i + len(sig)] += sig * np.sqrt(0.5 * (1 + pan))

# Bus d'effets (réverbération)
VL = np.zeros(N); VR = np.zeros(N)

# ---------- Instruments ----------
def kalimba(m, dur=1.6):
    n = int(dur * SR); t = np.arange(n) / SR; f = hz(m)
    s = np.sin(2*np.pi*f*t) * env_exp(n, 3.2) + 0.32*np.sin(2*np.pi*f*5.95*t) * env_exp(n, 14) + 0.10*np.sin(2*np.pi*f*2.0*t) * env_exp(n, 6)
    return s
def marimba(m, dur=1.0):
    n = int(dur * SR); t = np.arange(n) / SR; f = hz(m)
    return np.sin(2*np.pi*f*t) * env_exp(n, 5.5) + 0.42*np.sin(2*np.pi*f*4.0*t) * env_exp(n, 18) + 0.12*np.sin(2*np.pi*f*9.9*t) * env_exp(n, 40)
def bell(m, dur=2.4):
    n = int(dur * SR); t = np.arange(n) / SR; f = hz(m)
    return (np.sin(2*np.pi*f*t) + 0.45*np.sin(2*np.pi*f*2.0*t)*np.exp(-t*3) + 0.25*np.sin(2*np.pi*f*3.01*t)*np.exp(-t*5)) * env_exp(n, 1.9, 0.006)
def pad(notes, dur, att=0.9, rel=1.2):
    n = int((dur + rel) * SR); t = np.arange(n) / SR; s = np.zeros(n)
    for m in notes:
        f = hz(m)
        for det in (-0.0035, 0.0035):
            for h in range(1, 8):
                s += np.sin(2*np.pi*f*(1+det)*h*t + h*0.7) / (h ** 1.55)
    e = np.minimum(1, t / att) * np.where(t < dur, 1, np.exp(-(t - dur) * 3.2))
    return s * e / (len(notes) * 4.5)
def bass(m, dur):
    n = int(dur * SR); t = np.arange(n) / SR; f = hz(m)
    s = np.sin(2*np.pi*f*t) + 0.28*np.sin(2*np.pi*2*f*t) + 0.08*np.sin(2*np.pi*3*f*t)
    return np.tanh(1.6 * s * env_exp(n, 2.4, 0.008)) * 0.8
def kick():
    n = int(0.45 * SR); t = np.arange(n) / SR
    ph = 2*np.pi*np.cumsum(45 + 85*np.exp(-t*28)) / SR
    return np.sin(ph) * np.exp(-t*7.5) + 0.15*rng.standard_normal(n)*np.exp(-t*220)
def shaker(n_ms=55):
    n = int(n_ms/1000 * SR); x = rng.standard_normal(n); x = np.diff(np.concatenate([[0], x]))
    t = np.arange(n) / SR; return x * np.minimum(1, t/0.006) * np.exp(-t*60) * 0.35
def rim():
    n = int(0.07 * SR); t = np.arange(n) / SR
    return (np.sin(2*np.pi*1750*t) + 0.6*np.sin(2*np.pi*2630*t)) * np.exp(-t*85) * 0.5
def conga(m=52):
    n = int(0.35 * SR); t = np.arange(n) / SR
    ph = 2*np.pi*np.cumsum(hz(m)*(1 + 0.25*np.exp(-t*40))) / SR
    return np.sin(ph) * np.exp(-t*11) * 0.8

# ---------- Harmonie : Am – F – C – G (une mesure par accord) ----------
CH = [[57, 60, 64], [53, 57, 60], [48, 52, 55], [55, 59, 62]]   # la min, fa, do, sol
ROOT = [45, 41, 48, 43]
def chord(bar): return CH[bar % 4]
total_bars = int(END / BAR) + 1
S_A, S_B, S_BRK, S_C, S_OUT = 4, 16, 37, 43, 57   # mesures de début de section

for b in range(total_bars):
    t0 = b * BAR
    if t0 > END - 1: break
    c = chord(b)
    in_intro, in_A, in_B, in_brk, in_C, in_out = b < S_A, S_A <= b < S_B, S_B <= b < S_BRK, S_BRK <= b < S_C, S_C <= b < S_OUT, b >= S_OUT
    last = b >= int((END - 6) / BAR)
    # Nappe (toujours présente, plus large en fin)
    pd = pad([m + 12 for m in c] + ([c[0] + 24] if (in_C or in_brk) else []), BAR, att=0.6 if b else 1.8)
    g_pad = 0.55 if (in_intro or in_brk or in_out) else 0.38
    put(pd, t0, g_pad, -0.15); put(pd, t0, g_pad * 0.5, 0.15, VL, VR)
    # Kalimba : arpège en croches (intro, A, pause, fin)
    if in_intro or in_A or in_brk or in_out:
        arp = [c[0] + 12, c[1] + 12, c[2] + 12, c[0] + 24, c[2] + 12, c[1] + 12, c[2] + 12, c[0] + 24]
        for k, m in enumerate(arp):
            if in_out and t0 + k * BEAT / 2 > END - 3.5: break
            v = 0.16 if k % 2 == 0 else 0.11
            if in_intro and b == 0 and k < 2: v *= 0.6
            ka = kalimba(m); put(ka, t0 + k * BEAT / 2, v, -0.35 if k % 2 else 0.35); put(ka, t0 + k * BEAT / 2, v * 0.7, 0, VL, VR)
    # Basse
    if in_A or in_B or in_C:
        r = ROOT[b % 4]
        pattern = [(0, 1.2, r), (1.5, 0.5, r), (2.5, 0.6, r + 12 if b % 2 else r + 7), (3.5, 0.45, r)] if not in_A else [(0, 2.0, r), (2.5, 1.2, r)]
        for pos, d, m in pattern:
            put(bass(m, d * BEAT + 0.15), t0 + pos * BEAT, 0.42 if in_A else 0.5, 0)
    # Percussions
    if in_A or in_B or in_C:
        for k in range(16):   # shaker en doubles croches
            acc = 1.0 if k % 4 == 2 else (0.55 if k % 2 == 0 else 0.35)
            if in_A: acc *= 0.6
            put(shaker(), t0 + k * BEAT / 4, acc * 0.5, 0.45)
        for pos in ([0, 2] if in_A else [0, 1.5, 2, 3.25]):
            put(kick(), t0 + pos * BEAT, 0.62 if in_A else 0.75, 0)
        if in_B or in_C:
            clave = [0, 0.75, 1.5, 2.5, 3.0] if b % 2 == 0 else [0.5, 1.0, 2.0, 3.0]   # clave afro (3-2 / 2-3)
            for pos in clave: put(rim(), t0 + pos * BEAT, 0.42, -0.4)
            for pos, m in [(1.0, 52), (1.75, 55), (3.5, 52)]: put(conga(m), t0 + pos * BEAT, 0.32, 0.3)
    # Marimba : motif accrocheur sur 2 mesures (sections B et C)
    if in_B or in_C:
        motif = [(0, 76), (0.75, 74), (1.5, 72), (2.5, 69), (3.0, 72)] if b % 2 == 0 else [(0, 74), (0.5, 72), (1.5, 69), (2.0, 67), (3.0, 69), (3.5, 72)]
        tr = {0: 0, 1: -4, 2: -9 + 12, 3: -2}[b % 4] if False else 0
        cs = set(m % 12 for m in chord(b))
        for pos, m in motif:
            # aligne chaque note sur l'accord courant (note la plus proche de l'accord ou de la gamme pentatonique)
            pent = [57, 60, 62, 64, 67, 69, 72, 74, 76, 79]
            mm = min(pent, key=lambda p: abs(p - m) + (0 if p % 12 in cs else 0.6))
            ma = marimba(mm); put(ma, t0 + pos * BEAT, 0.2, 0.25); put(ma, t0 + pos * BEAT, 0.12, 0, VL, VR)
    # Cloche mélodique (section C) : phrase de 4 mesures
    if in_C:
        phrase = {0: [(0, 76, 1.2), (1.5, 74, 0.6), (2.0, 72, 1.6)], 1: [(0, 69, 1.4), (2.0, 72, 0.7), (3.0, 74, 0.9)],
                  2: [(0, 76, 0.9), (1.0, 79, 1.0), (2.0, 76, 0.8), (3.0, 74, 0.9)], 3: [(0, 74, 1.6), (2.0, 71, 1.0), (3.0, 74, 0.9)]}[(b - S_C) % 4]
        for pos, m, d in phrase:
            be = bell(m); put(be, t0 + pos * BEAT, 0.13, -0.1); put(be, t0 + pos * BEAT, 0.16, 0, VL, VR)

# Montée (riser) avant la reprise de la section C
rs, re_ = (S_C - 2) * BAR, S_C * BAR
n = int((re_ - rs) * SR); t = np.arange(n) / SR
noise = rng.standard_normal(n); noise = np.convolve(noise, np.ones(6) / 6, 'same')
put(noise * (t / t[-1]) ** 2.2 * 0.18, rs, 1.0, 0); put(noise * (t / t[-1]) ** 2.2 * 0.12, rs, 1.0, 0, VL, VR)
put(kick() * 1.1, S_C * BAR, 1.0, 0)
# Accord final tenu
put(pad([57, 60, 64, 69, 72], 4.5, att=0.3, rel=3.0), S_OUT * BAR + 2 * BAR, 0.55, 0)

# Réverbération (convolution, ~2 s)
ir_n = int(2.2 * SR); ti = np.arange(ir_n) / SR
irL = rng.standard_normal(ir_n) * np.exp(-ti * 3.1); irR = rng.standard_normal(ir_n) * np.exp(-ti * 3.1)
irL[: int(0.012 * SR)] = 0; irR[: int(0.017 * SR)] = 0
irL /= np.sqrt((irL ** 2).sum()); irR /= np.sqrt((irR ** 2).sum())
L += fftconvolve(VL, irL)[:N] * 0.55; R += fftconvolve(VR, irR)[:N] * 0.55

# Fondu d'entrée / sortie
tt = np.arange(N) / SR
fade = np.minimum(1, tt / 1.2) * np.clip((END - tt) / 3.0, 0, 1)
L *= fade; R *= fade
peak = max(np.abs(L).max(), np.abs(R).max()); L /= peak; R /= peak
sf.write('music.wav', np.stack([L, R], 1) * 0.89, SR)
print('musique', round(N / SR, 1), 's')
