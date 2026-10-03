import os, sherpa_onnx, soundfile as sf, numpy as np, json, difflib, re, unicodedata
B='vo/'   # modèles sherpa-onnx (Piper UPMC, Whisper small) des releases k2-fsa/sherpa-onnx
V=B+'vits-piper-fr_FR-upmc-medium'
def mk(ls):
    return sherpa_onnx.OfflineTts(sherpa_onnx.OfflineTtsConfig(model=sherpa_onnx.OfflineTtsModelConfig(vits=sherpa_onnx.OfflineTtsVitsModelConfig(
        model=f'{V}/fr_FR-upmc-medium.onnx', tokens=f'{V}/tokens.txt', data_dir=f'{V}/espeak-ng-data',
        noise_scale=0.62, noise_scale_w=0.75, length_scale=ls), num_threads=4)))
tts=mk(1.0)
W=B+'sherpa-onnx-whisper-small'
asr=sherpa_onnx.OfflineRecognizer.from_whisper(encoder=f'{W}/small-encoder.int8.onnx', decoder=f'{W}/small-decoder.int8.onnx', tokens=f'{W}/small-tokens.txt', language='fr', task='transcribe', num_threads=4)
PH=[('Mobile Money','Mobile Moni'),('marketplace','markète-plèsse'),('e-mail','i-mèl'),('QR code','Q R code'),('francs CFA','francs, C F A'),
    ('Rèje point site','Raije, point, site'),('dès aujourd','dèss aujourd'),('Rèje','Raije'),('Puis votre diocèse','Puis, votre diocèse'),('événements','évènements')]
cues=json.load(open('cues.json'))['cues']; rows=[]
def synth(text, speed):
    a=tts.generate(text,sid=1,speed=speed); s=np.array(a.samples); sr=a.sample_rate
    idx=np.where(np.abs(s)>0.012)[0]; s=s[max(idx[0]-int(.02*sr),0):idx[-1]+int(.14*sr)]
    f=int(.01*sr); s[:f]*=np.linspace(0,1,f); s[-f:]*=np.linspace(1,0,f); return s,sr
def norm(x):
    x=unicodedata.normalize('NFD',x.lower()); x=''.join(ch for ch in x if unicodedata.category(ch)!='Mn')
    x=x.replace('reje','rege').replace('10 000','dix mille').replace('8 ','huit ').replace('6 ','six ')
    return re.sub(r'[^a-z ]+',' ',x).split()
def hear(s,sr):
    s16=np.interp(np.arange(0,len(s),sr/16000),np.arange(len(s)),s).astype(np.float32)
    st=asr.create_stream(); st.accept_waveform(16000,s16); asr.decode_stream(st); return st.result.text
TRIES=int(os.environ.get('TRIES',8)); ONLY=os.environ.get('ONLY')
old={r['i']:r for r in json.load(open('vo2/clips.json'))['rows']} if ONLY else {}
for c in cues:
    if ONLY and c['i'] not in map(int,ONLY.split(',')): rows.append(old[c['i']]); continue
    say=c['text']
    for a,b in PH: say=say.replace(a,b)
    window=c['end']-c['start']+0.55   # marge avant la phrase suivante
    ref=norm(c['text']); best=None
    for k in range(TRIES):            # le modèle est stochastique : on garde la prise la plus intelligible
        speed=0.80
        s,sr=synth(say,speed); d=len(s)/sr
        if d>window: speed=min(1.0, speed*d/window*1.02); s,sr=synth(say,speed); d=len(s)/sr
        if d>window: continue
        txt=hear(s,sr); score=difflib.SequenceMatcher(None,ref,norm(txt)).ratio()
        if best is None or score>best[0]: best=(score,s,sr,d,speed,txt)
        if score>0.97: break
    score,s,sr,d,speed,txt=best
    sf.write(f"vo2/c{c['i']:02d}.wav",s,sr)
    rows.append({'i':c['i'],'dur':round(d,2),'window':round(window,2),'speed':round(speed,2),'score':round(score,3)})
    print(f"{c['i']:02d} {d:5.2f}/{window:5.2f}s v{speed:.2f} {score:.2f} | {txt}",flush=True)
json.dump({'sr':sr,'rows':rows},open('vo2/clips.json','w'))
