import sherpa_onnx, soundfile as sf, json, sys
V='vits-piper-fr_FR-siwis-medium'
cfg=sherpa_onnx.OfflineTtsConfig(model=sherpa_onnx.OfflineTtsModelConfig(vits=sherpa_onnx.OfflineTtsVitsModelConfig(
    model=f'{V}/fr_FR-siwis-medium.onnx', tokens=f'{V}/tokens.txt', data_dir=f'{V}/espeak-ng-data',
    noise_scale=0.6, noise_scale_w=0.7, length_scale=1.0), num_threads=4))
tts=sherpa_onnx.OfflineTts(cfg)
lines=json.load(open('script.json'))
speed=float(sys.argv[1]) if len(sys.argv)>1 else 1.0
out=[]
for i,(t,txt) in enumerate(lines):
    a=tts.generate(txt, sid=0, speed=speed)
    f=f'seg_{i:02d}.wav'; sf.write(f,a.samples,a.sample_rate)
    d=len(a.samples)/a.sample_rate; out.append((t,d,txt)); print(f'{t:5.1f} {d:4.2f} end {t+d:5.2f}  {txt}')
json.dump(out,open('timing.json','w'),ensure_ascii=False)
