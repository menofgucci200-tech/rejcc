const { chromium } = require('playwright');
const { execSync } = require('child_process');
const BASE='http://127.0.0.1:8020';
const EMAIL='jeanmarc.kouadio@rejcc-demo.ci', PASS=process.env.DEMO_PASSWORD;
const DB='/home/user/rejcc/REJCC-Backend/database/database.sqlite';
(async()=>{
 const b=await chromium.launch({executablePath:'/opt/pw-browsers/chromium',args:['--no-sandbox']});
 const mk=async(kind)=>{ const ctx=await b.newContext(kind==='d'?{viewport:{width:1440,height:900}}:{viewport:{width:390,height:844},deviceScaleFactor:2,isMobile:true,hasTouch:true});
   await ctx.addInitScript(()=>{try{sessionStorage.setItem('rejcc_intro_seen','1')}catch(e){}}); const p=await ctx.newPage(); p.on('dialog',d=>d.accept()); return {ctx,p}; };
 const shot=async(p,kind,name,full=false)=>{ await p.evaluate(()=>{window.scrollTo(0,0);document.getElementById('page-loader')?.remove();}); await p.waitForTimeout(700);
   await p.screenshot({path:`shots/${kind}_${name}.jpg`,type:'jpeg',quality:90}); if(full) await p.screenshot({path:`shots/${kind}_${name}_full.jpg`,type:'jpeg',quality:88,fullPage:true}); };
 const go=async(p,u)=>{ await p.goto(BASE+u,{waitUntil:'networkidle'}); await p.waitForTimeout(600); };
 const status=async(kind,tag)=>{ const {ctx,p}=await mk(kind); await go(p,'/suivre-ma-candidature'); await shot(p,kind,`suivi_${tag}_vide`);
   await p.fill('#statut-email',EMAIL); await p.click('form button[type=submit]'); await p.waitForLoadState('networkidle'); await p.waitForTimeout(900); await shot(p,kind,`suivi_${tag}`); await ctx.close(); };
 const login=async(p,kind,email,pass,tag)=>{ await go(p,'/connexion'); await p.fill('#email',email); await p.fill('#password',pass); if(tag) await shot(p,kind,tag);
   await p.click('form button[type=submit]'); await p.waitForURL(u=>!u.toString().includes('/connexion'),{timeout:20000}); await p.waitForLoadState('networkidle'); await p.waitForTimeout(800); };
 for (const kind of ['d','m']) { const {ctx,p}=await mk(kind); await login(p,kind,EMAIL,PASS);
   await shot(p,kind,'membre_accueil',true);
   for (const [u,n] of [['/espace-membre/carte','carte'],['/espace-membre/annuaire','annuaire'],['/espace-membre/messagerie','messagerie'],['/espace-membre/abonnement','abonnement_actif']]) { await go(p,u); await shot(p,kind,'membre_'+n); }
   await ctx.close(); }
 await b.close(); console.log('ok');
})().catch(e=>{console.error('FATAL',e.message);process.exit(1)});
