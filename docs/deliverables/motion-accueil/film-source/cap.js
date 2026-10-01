const { chromium } = require('playwright');
const path=require('path'), fs=require('fs');
// node cap.js stills "t1,t2" prefix   |  node cap.js video outDir fps [from to]
(async()=>{
 const [mode,a,b,c,d]=process.argv.slice(2);
 const br=await chromium.launch({executablePath:'/opt/pw-browsers/chromium',args:['--no-sandbox','--allow-file-access-from-files','--force-color-profile=srgb']});
 const PAGE=process.env.PAGE||'index.html'; const VW=+(process.env.VW||1920), VH=+(process.env.VH||1080);
 const p=await br.newPage({viewport:{width:VW,height:VH}});
 p.on('pageerror',e=>console.log('pageerror',e.message)); p.on('console',m=>{if(m.type()==='error')console.log('console',m.text())});
 await p.goto('file://'+path.resolve(PAGE)+'?capture=1',{waitUntil:'load'});
 await p.evaluate(()=>document.fonts.ready);
 if(mode==='stills'){ for(const t of a.split(',').map(Number)){ await p.evaluate(t=>window.renderAt(t),t); await p.screenshot({path:`${b}_${t.toFixed(1).padStart(4,'0')}.png`}); } }
 else { fs.mkdirSync(a,{recursive:true}); const fps=+b, from=+(c||0), to=+(d||60);
   for(let i=Math.round(from*fps);i<Math.round(to*fps);i++){ await p.evaluate(t=>window.renderAt(t),i/fps); await p.screenshot({path:`${a}/f${String(i).padStart(5,'0')}.jpg`,type:'jpeg',quality:95}); } }
 await br.close();
})().catch(e=>{console.error(e);process.exit(1)});
