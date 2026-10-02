import sherpa_onnx, soundfile as sf, numpy as np, json, sys, re
V='/tmp/claude-0/-home-user-rejcc/d3e7fc6e-4393-57ac-bfe5-5fef559d3787/scratchpad/vo/vits-piper-fr_FR-tom-medium'
tts=sherpa_onnx.OfflineTts(sherpa_onnx.OfflineTtsConfig(model=sherpa_onnx.OfflineTtsModelConfig(vits=sherpa_onnx.OfflineTtsVitsModelConfig(
    model=f'{V}/fr_FR-tom-medium.onnx', tokens=f'{V}/tokens.txt', data_dir=f'{V}/espeak-ng-data', noise_scale=0.667, noise_scale_w=0.0, length_scale=1.1), num_threads=4)))
W='/tmp/claude-0/-home-user-rejcc/d3e7fc6e-4393-57ac-bfe5-5fef559d3787/scratchpad/vo/sherpa-onnx-whisper-small'
asr=sherpa_onnx.OfflineRecognizer.from_whisper(encoder=f'{W}/small-encoder.int8.onnx', decoder=f'{W}/small-decoder.int8.onnx', tokens=f'{W}/small-tokens.txt', language='fr', task='transcribe', num_threads=4)
# graphies phonétiques pour la synthèse (le texte affiché garde l'orthographe normale)
PH=[('Mobile Money','Mobile Monèï'),('marketplace','markète-plèsse'),('e-mail','i-mèl'),('QR code','Q R code'),('CFA','C F A')]
rows=json.load(open('script.json')); out=[]
for i,(sec,txt) in enumerate(rows):
    say=txt
    for a,b in PH: say=say.replace(a,b)
    a=tts.generate(say,sid=0,speed=1.0); s=np.array(a.samples); sr=a.sample_rate
    idx=np.where(np.abs(s)>0.015)[0]; s=s[max(idx[0]-int(.02*sr),0):idx[-1]+int(.12*sr)]
    f=int(.008*sr); s[:f]*=np.linspace(0,1,f); s[-f:]*=np.linspace(1,0,f)
    sf.write(f'vo/c{i:02d}.wav',s,sr)
    s16=np.interp(np.arange(0,len(s),sr/16000),np.arange(len(s)),s).astype(np.float32)
    st=asr.create_stream(); st.accept_waveform(16000,s16); asr.decode_stream(st)
    out.append({'i':i,'sec':sec,'text':txt,'dur':round(len(s)/sr,2),'sr':sr})
    print(f'{i:02d} {len(s)/sr:5.2f}s | {st.result.text}')
json.dump(out,open('vo/clips.json','w'),ensure_ascii=False,indent=1)
print('total', round(sum(o['dur'] for o in out),1))
