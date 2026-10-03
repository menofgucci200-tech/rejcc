# Piste voix « pierre » + mixage avec la musique. Traitement volontairement léger
# (pas d'accentuation des aigus, compression douce) pour éviter le timbre métallique.
import json, subprocess, numpy as np, soundfile as sf
from scipy.signal import fftconvolve, resample_poly
FF='/usr/local/lib/python3.11/dist-packages/imageio_ffmpeg/binaries/ffmpeg-linux-x86_64-v7.0.2'
SR=44100; cues=json.load(open('cues.json')); END=cues['end']
N=int((END+0.5)*SR); v=np.zeros(N)
for c in cues['cues']:
    s,sr=sf.read(f"vo2/c{c['i']:02d}.wav"); s=resample_poly(s,SR,sr)
    i=int(c['start']*SR); v[i:i+len(s)]+=s[:N-i]
sf.write('vo2/voice_track.wav',v,SR)
subprocess.run([FF,'-y','-loglevel','error','-i','vo2/voice_track.wav','-af',
  'highpass=f=70,lowpass=f=10500,equalizer=f=220:t=q:w=1:g=1.5,equalizer=f=6500:t=q:w=1.5:g=-2,'
  'acompressor=threshold=-22dB:ratio=1.6:attack=15:release=200:makeup=1','vo2/voice_proc.wav'],check=True)
v,_=sf.read('vo2/voice_proc.wav')
# petite pièce (≈0,35 s), très discrète : donne de l'air à la voix sans écho audible
rng=np.random.default_rng(3); n=int(.35*SR); t=np.arange(n)/SR
ir=rng.standard_normal(n)*np.exp(-t*16); ir[:int(.008*SR)]=0; ir/=np.sqrt((ir**2).sum())
v=v+fftconvolve(v,ir)[:len(v)]*0.07
act=np.abs(v)>1e-4; rms=np.sqrt(np.mean(v[act]**2)); v*=10**(-17/20)/rms
m,_=sf.read('music_eq.wav'); M=min(len(m),len(v)); m=m[:M]; v=v[:M]
k=int(.25*SR); env=np.convolve(np.abs(v),np.ones(k)/k,'same')
env=np.convolve(env,np.ones(k)/k,'same')   # double lissage : le ducking respire
duck=1-0.645*np.clip(env/0.05,0,1)
mix=m*10**(-15/20)*duck[:,None]+v[:,None]
sf.write('vo2/mix.wav',mix,SR)
subprocess.run([FF,'-y','-loglevel','error','-i','vo2/mix.wav','-af','loudnorm=I=-16:TP=-1.5:LRA=9','-ar','48000','vo2/mix_48k.wav'],check=True)
print('ok',M/SR)
