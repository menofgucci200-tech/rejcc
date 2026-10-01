const { chromium } = require('playwright');
const BASE='http://127.0.0.1:8020';
const pages=process.argv[2]?process.argv[2].split(','):['/','/a-propos','/activites','/domaines','/evenements','/actualites','/adhesion','/partenaires'];
(async()=>{
 const b=await chromium.launch({executablePath:'/opt/pw-browsers/chromium',args:['--no-sandbox']});
 for (const [kind,vp,dpr] of [['d',{width:1440,height:900},1],['m',{width:390,height:844},2]]) {
  const ctx=await b.newContext({viewport:vp,deviceScaleFactor:dpr,reducedMotion:'reduce'});
  await ctx.addInitScript(()=>{try{sessionStorage.setItem('rejcc_intro_seen','1')}catch(e){}});
  const p=await ctx.newPage();
  for (const u of pages) {
    await p.goto(BASE+u,{waitUntil:'networkidle'}); await p.waitForTimeout(500);
    await p.evaluate(()=>{document.querySelectorAll('[data-reveal]').forEach(e=>e.classList.add('is-visible'));
      document.querySelectorAll('[data-counter]').forEach(e=>e.textContent=Number(e.dataset.counterValue||0).toLocaleString('fr-FR')+(e.dataset.counterSuffix||''));
      document.getElementById('page-loader')?.remove();});
    await p.waitForTimeout(900);
    const name=(u==='/'?'home':u.slice(1));
    await p.screenshot({path:`assets/shots/${kind}_${name}.jpg`,type:'jpeg',quality:90});
    if (['home','activites','evenements','domaines'].includes(name)) await p.screenshot({path:`assets/shots/${kind}_${name}_full.jpg`,type:'jpeg',quality:88,fullPage:true});
  }
  await ctx.close();
 }
 await b.close();
})();
