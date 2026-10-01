import sherpa_onnx, soundfile as sf, numpy as np, json, os
V='vits-piper-fr_FR-tom-medium'; LS=1.1; SR=44100
tts=sherpa_onnx.OfflineTts(sherpa_onnx.OfflineTtsConfig(model=sherpa_onnx.OfflineTtsModelConfig(vits=sherpa_onnx.OfflineTtsVitsModelConfig(
    model=f'{V}/fr_FR-tom-medium.onnx', tokens=f'{V}/tokens.txt', data_dir=f'{V}/espeak-ng-data',
    noise_scale=0.667, noise_scale_w=0.0, length_scale=LS), num_threads=4)))
C={}
def clip(key, text, ls=None):
    a=tts.generate(text,sid=0,speed=LS/ls if ls else 1.0); s=np.array(a.samples)
    idx=np.where(np.abs(s)>0.015)[0]; s=s[max(idx[0]-int(.02*SR),0):idx[-1]+int(.1*SR)]
    f=int(.008*SR); s[:f]*=np.linspace(0,1,f); s[-f:]*=np.linspace(1,0,f)
    C[key]=(s,text); return len(s)/SR
D={}
D['c1']=clip('c1',"Foi. Innovation. Entrepreneuriat.")
D['c2']=clip('c2',"Bienvenue au Rèje, le Réseau Entrepreneurial des Jeunes Chrétiens Catholiques.")
D['w1']=clip('w1',"Un réseau."); D['w2']=clip('w2',"Des talents."); D['w3']=clip('w3',"Une même foi.")
D['c4']=clip('c4',"Notre vision, c'est d'être le premier incubateur de talents et d'entreprises, au service de l'Église et de la société, en Côte d'Ivoire.")
D['c5']=clip('c5',"Sur ordinateur, le site vous accueille avec une identité forte, et vous présente l'essentiel du réseau, simplement.")
D['c6']=clip('c6',"Sur mobile, la même exigence : un site fluide, lisible, que vous pouvez installer comme une application.")
D['c7']=clip('c7',"Découvrez nos activités, nos trente-trois domaines d'activité, notre agenda, et adhérez en ligne, étape par étape.")
D['c8']=clip('c8',"Nos valeurs :")
for i,v in enumerate(["La foi.","L'excellence.","La solidarité.","L'impact.","Et la création de richesse."]): D[f'v{i}']=clip(f'v{i}',v)
D['c9']=clip('c9',"Plus de trois cent cinquante membres, et trente-trois domaines d'activité.")
D['c10']=clip('c10',"Et si le prochain, c'était vous ?")
D['c11']=clip('c11',"Le Rèje.")
D['c12']=clip('c12',"Ensemble, pour l'excellence.")
print({k:round(v,2) for k,v in D.items()})

place={}; A=[(0.0,0.0),(5.9,5.9)]  # (out, render)
place['c1']=0.35
place['c2']=max(place['c1']+D['c1']+0.6, 3.0)
w1=place['c2']+D['c2']+0.7; place['w1']=w1
place['w2']=w1+D['w1']+0.4; place['w3']=place['w2']+D['w2']+0.4
A+= [(w1-0.2,6.55),(place['w2']-0.2,7.95),(place['w3']-0.2,9.3)]
c4=place['w3']+D['w3']+0.8; place['c4']=c4
A+= [(c4-0.4,10.55),(c4-0.1,10.85),(c4+D['c4']+0.4,13.5),(c4+D['c4']+1.1,14.2)]
def at(r):  # out time of render r using scene offset (unstretched after last anchor)
    o,rr=A[-1]; return o+(r-rr)
s3=A[-1][0]
place['c5']=s3+1.2; A+=[(place['c5']-0.1,15.4),(place['c5']+D['c5']+0.7,24.6),(place['c5']+D['c5']+1.1,25.0)]
s4=A[-1][0]
place['c6']=s4+1.2; A+=[(place['c6']-0.1,26.1),(place['c6']+D['c6']+0.6,35.6),(place['c6']+D['c6']+1.0,36.0)]
s5=A[-1][0]
place['c7']=s5+1.1; A+=[(place['c7']-0.1,37.0)]
def at(r):
    o,rr=A[-1]; return o+(r-rr)
place['c8']=place['c7']+D['c7']+0.7
A+=[(place['c8']-0.05,44.65)]
cut=place['c8']+D['c8']+0.2
for i in range(5):
    A.append((cut,45.2+1.5*i)); place[f'v{i}']=cut+0.2; cut=place[f'v{i}']+D[f'v{i}']+0.45
A.append((cut+0.1,52.7))
place['c9']=cut+0.45; A.append((place['c9'],53.0))
place['c10']=place['c9']+D['c9']+0.6; A.append((place['c10']-0.15,54.5))
A.append((place['c10']+D['c10']+0.6,56.2))
o=A[-1][0]
place['c11']=o+1.1; A.append((place['c11']+0.1,57.2))
place['c12']=place['c11']+D['c11']+0.6; A.append((place['c12']-0.1,57.75)); A.append((place['c12']+0.5,58.3))
END=place['c12']+D['c12']+2.2; A.append((END,58.3+(END-place['c12']-0.5)))
A=sorted(A)
for i in range(1,len(A)): assert A[i][0]>A[i-1][0] and A[i][1]>=A[i-1][1], (A[i-1],A[i])
print('END', round(END,2))
mix=np.zeros(int((END+0.1)*SR)); rows=[]
for k,t0 in sorted(place.items(), key=lambda x:x[1]):
    s,txt=C[k]; i=int(t0*SR); mix[i:i+len(s)]+=s; rows.append((round(t0,2),round(t0+len(s)/SR,2),txt))
sf.write('v2/voix_off_guide.wav',mix,SR)
json.dump(rows,open('v2/rows.json','w'),ensure_ascii=False,indent=0)
open('../film/anchors.js','w').write('window.ANCHORS='+json.dumps([[round(a,3),round(b,3)] for a,b in A])+';window.FILM_END='+str(round(END,2))+';\n')
for r in rows: print(r)
