<?php
/**
 * pagina-supporter.php — Pagina "Diventa Supporter dell'Accademia della Sfoglia".
 *
 * Prima questa pagina era scritta a mano nell'editor di WordPress: ogni
 * salvataggio veniva riscomposto dall'editor in modo imprevedibile,
 * rompendo tag e link (Ennio, 13/08/2026 — "devi ragionare in modo
 * differente per risolvere"). Ora la genera il plugin, esattamente come
 * Calendario Corsi o La Mia Sfoglia: uno shortcode, [gs_pagina_supporter],
 * non c'è più nulla che l'editor possa rompere. Nella pagina resta solo
 * quella riga.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'gs_pagina_supporter', 'gs_sc_pagina_supporter' );
function gs_sc_pagina_supporter() {
	$gs_vc_ico = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="8" y1="2.5" x2="8" y2="6.5"></line><line x1="16" y1="2.5" x2="16" y2="6.5"></line></svg>';
	ob_start();
	?>
<style>
#gs-supporter-page{
  --paper:#faf3e2; --paper2:#f3e8cc; --card:#fffdf6;
  --ink:#2a1f14; --ink-soft:#5c4a34;
  --gold:#a6790c; --gold-deep:#7a5608; --gold-pale:#e9c66b; --gold-bright:#f3c53a;
  --wine:#7a2b28; --wine-deep:#5c1f1d;
  --line:rgba(42,31,20,.14);
  --shadow: 0 1px 2px rgba(42,31,20,.08), 0 20px 40px -20px rgba(42,31,20,.35);
  --shadow-lift: 0 2px 4px rgba(42,31,20,.10), 0 30px 60px -25px rgba(42,31,20,.45);
}
#gs-supporter-page, #gs-supporter-page *{ box-sizing:border-box; }
#gs-supporter-page{
  margin:0; padding:0; background:var(--paper); color:var(--ink);
  font-family:Georgia,"Iowan Old Style","Palatino Linotype",serif;
}
#gs-supporter-page .sans{ font-family:-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }
#gs-supporter-page h1,
#gs-supporter-page h2,
#gs-supporter-page h3,
#gs-supporter-page h4{
  font-family:Georgia,"Iowan Old Style","Palatino Linotype",serif;
  margin:0; line-height:1.28 !important; text-wrap:balance; font-weight:700;
}
#gs-supporter-page p{ margin:0 0 16px; line-height:1.65; }
#gs-supporter-page ul{ margin:0; }
#gs-supporter-page img{ display:block; max-width:100%; }
#gs-supporter-page a{ text-decoration:none; }
#gs-supporter-page .wrap{ max-width:1080px; margin:0 auto; padding:0 32px; }
#gs-supporter-page .eyebrow{
  font-family:-apple-system,"Segoe UI",Roboto,sans-serif; font-size:12px; font-weight:700;
  letter-spacing:.2em; text-transform:uppercase; color:var(--gold-deep);
  display:block; margin:0 0 10px; text-align:left !important;
}
#gs-supporter-page .btn{
  font-family:-apple-system,sans-serif; font-weight:700; font-size:15px; letter-spacing:.02em;
  padding:15px 26px; border-radius:40px; display:inline-block; white-space:normal;
  background:linear-gradient(180deg,#f7e3ae,var(--gold-pale)); color:#3a2408;
  box-shadow:0 10px 22px -8px rgba(42,31,20,.55); border:1px solid rgba(122,86,8,.35);
  transition:transform .15s ease, box-shadow .15s ease; text-align:center !important;
}
#gs-supporter-page .btn:hover{ transform:translateY(-2px); box-shadow:0 16px 30px -10px rgba(42,31,20,.6); }
#gs-supporter-page .btn-line{
  background:transparent; color:#4a2f0a; border:1.5px solid rgba(74,47,10,.45); box-shadow:none;
}
#gs-supporter-page .btn-line:hover{ background:rgba(255,255,255,.28); transform:translateY(-2px); box-shadow:none; }
#gs-supporter-page .btn-ghost{
  background:transparent; color:#fff3d9; border:1.5px solid rgba(233,198,107,.55); box-shadow:none;
}
#gs-supporter-page .btn-ghost:hover{ background:rgba(233,198,107,.14); transform:translateY(-2px); box-shadow:none; }
#gs-supporter-page .hero{
  position:relative; overflow:hidden;
  background:linear-gradient(158deg,var(--gold-pale) 0%,var(--gold) 46%,var(--gold-deep) 100%);
}
#gs-supporter-page .hero::before{
  content:""; position:absolute; inset:0; pointer-events:none;
  background-image:
    radial-gradient(circle at 16% 18%, rgba(255,255,255,.42), transparent 42%),
    radial-gradient(circle at 84% 76%, rgba(42,31,20,.20), transparent 46%);
}
#gs-supporter-page .hero-grid{
  position:relative; z-index:1;
  display:grid; grid-template-columns:1.18fr .82fr; align-items:center; gap:54px;
  padding-top:84px; padding-bottom:190px;
}
#gs-supporter-page .hero .eyebrow{ color:#5c3d05; text-align:left !important; }
#gs-supporter-page .hero h1{
  font-size:clamp(31px,4vw,47px); color:#2a1a08; margin:0 0 20px;
  text-shadow:0 1px 0 rgba(255,255,255,.3); text-align:left !important;
}
#gs-supporter-page .hero .lede{
  font-size:17.5px; color:#3a2a10; max-width:48ch; text-align:left !important;
}
#gs-supporter-page .price-tag{
  display:inline-flex; align-items:baseline; gap:10px; margin-top:26px;
  background:var(--wine-deep); color:#fff3d9; padding:13px 22px; border-radius:10px;
  box-shadow:var(--shadow);
}
#gs-supporter-page .price-tag .n{ font-size:26px; font-weight:700; line-height:1 !important; }
#gs-supporter-page .price-tag .l{
  font-family:-apple-system,sans-serif; font-size:11px; font-weight:700;
  text-transform:uppercase; letter-spacing:.1em; color:var(--gold-pale);
}
#gs-supporter-page .hero-actions{ display:flex; flex-wrap:wrap; gap:12px; align-items:center; margin-top:22px; }
#gs-supporter-page .hero-photo{ position:relative; justify-self:center; }
#gs-supporter-page .hero-photo .frame{
  position:relative; width:290px; max-width:78vw; border-radius:8px; overflow:hidden;
  box-shadow:var(--shadow-lift); transform:rotate(-2.5deg); border:7px solid #fffdf6;
}
#gs-supporter-page .hero-photo .frame img{ width:100%; height:auto; }
#gs-supporter-page .hero-photo .tag{
  position:absolute; bottom:-18px; right:-14px; background:var(--wine-deep); color:#fff3d9;
  font-family:-apple-system,sans-serif; font-size:11px; font-weight:700; letter-spacing:.11em;
  text-transform:uppercase; padding:11px 17px; border-radius:5px;
  box-shadow:var(--shadow); transform:rotate(3deg); text-align:center !important;
}
#gs-supporter-page .wave-hero{ position:absolute; left:0; right:0; bottom:-1px; height:150px; pointer-events:none; z-index:0; }
#gs-supporter-page .wave-hero svg{ width:100%; height:100%; display:block; }
#gs-supporter-page .proof{ position:relative; z-index:3; }
#gs-supporter-page .proof-bar{
  margin-top:-62px; background:var(--card); border:1px solid var(--line); border-radius:16px;
  box-shadow:var(--shadow-lift); display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); overflow:hidden;
}
#gs-supporter-page .proof-cell{ padding:24px 16px; text-align:center !important; border-right:1px solid var(--line); }
#gs-supporter-page .proof-cell:last-child{ border-right:none; }
#gs-supporter-page .proof-cell .big{ font-size:27px; font-weight:700; color:var(--wine); line-height:1.1 !important; text-align:center !important; }
#gs-supporter-page .proof-cell .lab{
  font-family:-apple-system,sans-serif; font-size:11.5px; letter-spacing:.09em; text-transform:uppercase;
  color:var(--ink-soft); margin-top:7px; text-align:center !important; line-height:1.45;
}
#gs-supporter-page .sec-head{ max-width:700px; margin:0 0 40px; }
#gs-supporter-page .sec-head h2{ font-size:clamp(26px,3vw,35px); margin:0 0 14px; text-align:left !important; }
#gs-supporter-page .sec-head p{ font-size:16.5px; color:var(--ink-soft); text-align:left !important; }
#gs-supporter-page .perche{ padding:74px 0 84px; }
#gs-supporter-page .benefits{ display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:20px; }
#gs-supporter-page .benefit{
  background:var(--card); border:1px solid var(--line); border-radius:16px; padding:26px 24px;
  box-shadow:var(--shadow); transition:transform .18s ease, box-shadow .18s ease;
}
#gs-supporter-page .benefit:hover{ transform:translateY(-4px); box-shadow:var(--shadow-lift); }
#gs-supporter-page .benefit.wide{ grid-column:span 2; }
#gs-supporter-page .benefit .ico{
  width:46px; height:46px; border-radius:50%; background:rgba(233,198,107,.30);
  display:flex; align-items:center; justify-content:center; margin:0 0 15px;
}
#gs-supporter-page .benefit .ico svg{ width:23px; height:23px; stroke:var(--gold-deep); fill:none; stroke-width:1.7; }
#gs-supporter-page .benefit h3{ font-size:17px; margin:0 0 8px; color:var(--wine-deep); text-align:left !important; }
#gs-supporter-page .benefit p{ font-size:14.5px; color:var(--ink-soft); text-align:left !important; }
#gs-supporter-page .corsi{ background:var(--paper2); padding:78px 0; border-top:1px solid var(--line); border-bottom:1px solid var(--line); }

/* Vetrina dei Corsi — stesso scaffale di bottega usato in Calendario Corsi,
   riscritto qui (con prefisso #gs-supporter-page) per restare una pagina
   autosufficiente: vedi la nota in cima al file. */
#gs-supporter-page .gs-vc-cornice{
  background:var(--card); border:1px solid #e3d5b8; border-radius:14px;
  padding:22px; box-shadow:0 3px 14px rgba(0,0,0,.06); overflow:hidden;
}
#gs-supporter-page .gs-vc-bottega{
  background:
    repeating-linear-gradient(90deg, rgba(0,0,0,.05) 0 2px, transparent 2px 26px),
    linear-gradient(160deg, #8a5a30, #6b4020 70%);
  border-radius:12px; padding:22px 20px 12px; box-shadow:inset 0 0 40px rgba(0,0,0,.35);
}
#gs-supporter-page .gs-vc-insegna{
  text-align:center !important; color:#ffe9c2 !important; font-weight:800; letter-spacing:.1em;
  font-size:13px; text-transform:uppercase; margin:0 0 18px; text-shadow:0 2px 6px rgba(0,0,0,.5);
}
#gs-supporter-page .gs-vc-scaffale{ margin-bottom:22px; }
#gs-supporter-page .gs-vc-ripiano-oggetti{
  display:flex; gap:16px; justify-content:center; align-items:flex-end; flex-wrap:wrap; padding-bottom:8px;
}
#gs-supporter-page .gs-vc-ripiano-asse{
  height:12px; border-radius:3px; background:linear-gradient(180deg,#a9743f,#6d4220 55%,#4a2c14);
  box-shadow:0 7px 14px rgba(0,0,0,.42);
}
#gs-supporter-page a.gs-vc-oggetto{
  width:158px; text-decoration:none; color:inherit; display:block; background:#fffdf6;
  border-radius:8px 8px 4px 4px; padding:7px 7px 9px; box-shadow:0 5px 10px rgba(0,0,0,.35);
  transition:transform .28s cubic-bezier(.2,.9,.3,1.2), box-shadow .28s; transform-origin:bottom center;
}
#gs-supporter-page a.gs-vc-oggetto:hover{
  transform:translateY(-13px) scale(1.05); box-shadow:0 16px 26px rgba(0,0,0,.45), 0 0 22px rgba(255,214,140,.5);
}
#gs-supporter-page .gs-vc-ph{
  display:flex; align-items:center; justify-content:center; height:78px; border-radius:5px; margin-bottom:6px;
}
#gs-supporter-page .gs-vc-ph svg{ width:28px; height:28px; color:#fff; filter:drop-shadow(0 2px 4px rgba(0,0,0,.25)); }
#gs-supporter-page .gs-vc-titolo{
  font-size:12.5px; font-weight:800; line-height:1.25; text-align:center !important; color:#4a3a28; margin:0;
}
#gs-supporter-page .gs-vc-luogo{
  font-family:-apple-system,sans-serif; font-size:10.5px; color:#8a7a5c; text-align:center !important; margin:3px 0 0;
}
#gs-supporter-page .gs-vc-cartellino{
  display:block; margin:7px auto 0; width:fit-content; font-family:-apple-system,sans-serif;
  font-size:9px; font-weight:800; letter-spacing:.05em; text-transform:uppercase;
  background:#1f6e37; color:#fff; padding:2px 8px; border-radius:999px;
}
#gs-supporter-page .registri{ padding:78px 0; }
#gs-supporter-page .reg-grid{ display:grid; grid-template-columns:1fr 1fr; gap:26px; }
#gs-supporter-page .reg{
  background:var(--card); border:1px solid var(--line); border-radius:18px; padding:32px 30px;
  box-shadow:var(--shadow); position:relative; overflow:hidden;
}
#gs-supporter-page .reg::before{
  content:""; position:absolute; top:0; left:0; width:100%; height:4px;
  background:linear-gradient(90deg,var(--gold-bright),var(--gold-deep));
}
#gs-supporter-page .reg.pro::before{ background:linear-gradient(90deg,var(--wine),var(--wine-deep)); }
#gs-supporter-page .reg h3{ font-size:20px; margin:0 0 16px; color:var(--wine-deep); text-align:left !important; }
#gs-supporter-page .flow{ display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:16px; }
#gs-supporter-page .flow .pair{ display:inline-flex; align-items:center; gap:8px; }
#gs-supporter-page .chip{
  font-family:-apple-system,sans-serif; font-size:12px; font-weight:700; letter-spacing:.02em;
  padding:8px 14px; border-radius:20px; background:var(--paper2); color:var(--ink);
  border:1px solid var(--line); white-space:normal; text-align:center !important;
}
#gs-supporter-page .chip.end{ background:var(--wine); color:#fff3d9; border-color:var(--wine-deep); }
#gs-supporter-page .chip.end-gold{ background:var(--gold-pale); color:#3a2408; border-color:var(--gold-deep); }
#gs-supporter-page .arrow{ color:var(--gold-deep); font-size:15px; font-weight:700; line-height:1; }
#gs-supporter-page .reg p{ font-size:14.5px; color:var(--ink-soft); text-align:left !important; }
#gs-supporter-page .dettagli{ padding:0 0 84px; }
#gs-supporter-page .avanzato-detail{
  background:linear-gradient(158deg,var(--wine) 0%,var(--wine-deep) 100%);
  border-radius:20px; padding:46px 44px; box-shadow:var(--shadow-lift); position:relative; overflow:hidden;
}
#gs-supporter-page .avanzato-detail::before{
  content:""; position:absolute; top:-140px; right:-120px; width:400px; height:400px; border-radius:50%;
  background:radial-gradient(circle,rgba(233,198,107,.16) 0%,rgba(233,198,107,0) 70%); pointer-events:none;
}
#gs-supporter-page .avanzato-detail > *{ position:relative; z-index:1; }
#gs-supporter-page .avanzato-detail .eyebrow{ color:var(--gold-pale); }
#gs-supporter-page .avanzato-detail h3{ font-size:25px; margin:0 0 28px; color:#fff8ea; text-align:left !important; }
#gs-supporter-page .ad-grid{ display:grid; grid-template-columns:1fr 1fr; gap:34px; }
#gs-supporter-page .ad-block + .ad-block{ margin-top:24px; }
#gs-supporter-page .ad-block .lbl{
  font-family:-apple-system,sans-serif; font-size:10.5px; font-weight:700; letter-spacing:.13em;
  text-transform:uppercase; color:var(--gold-pale); margin:0 0 8px; display:block; text-align:left !important;
}
#gs-supporter-page .ad-block p{ font-size:14.5px; color:#eaddc8; text-align:left !important; }
#gs-supporter-page .ad-block ul{ padding-left:0; list-style:none; }
#gs-supporter-page .ad-block ul li{ font-size:14.5px; color:#eaddc8; padding-left:20px; position:relative; text-align:left !important; line-height:1.6; }
#gs-supporter-page .ad-block ul li + li{ margin-top:6px; }
#gs-supporter-page .ad-block ul li::before{
  content:""; position:absolute; left:2px; top:9px; width:7px; height:7px; border-radius:50%; background:var(--gold-pale);
}
#gs-supporter-page .aderire{ background:var(--paper2); padding:76px 0; border-top:1px solid var(--line); }
#gs-supporter-page .steps{ display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:26px; position:relative; }
#gs-supporter-page .steps::before{
  content:""; position:absolute; top:26px; left:16%; right:16%; height:2px;
  background:repeating-linear-gradient(90deg,var(--gold-pale) 0 8px,transparent 8px 16px);
}
#gs-supporter-page .step{ text-align:center !important; position:relative; z-index:1; }
#gs-supporter-page .step .n{
  width:54px; height:54px; border-radius:50%; background:var(--wine); color:#fff3d9;
  display:flex; align-items:center; justify-content:center; margin:0 auto 16px;
  font-size:20px; font-weight:700; box-shadow:0 8px 18px -8px rgba(42,31,20,.7); border:4px solid var(--paper2);
}
#gs-supporter-page .step p{ font-size:14.5px; color:var(--ink-soft); text-align:center !important; max-width:28ch; margin:0 auto; }
#gs-supporter-page .step p a{ color:var(--wine); font-weight:700; border-bottom:1px solid rgba(122,43,40,.4); }
#gs-supporter-page .cta{
  position:relative; overflow:hidden; padding:88px 32px;
  background:linear-gradient(158deg,var(--wine) 0%,var(--wine-deep) 100%); color:#f6e9d8;
}
#gs-supporter-page .cta::before{
  content:""; position:absolute; top:-130px; right:-110px; width:440px; height:440px; border-radius:50%;
  background:radial-gradient(circle,rgba(233,198,107,.18) 0%,rgba(233,198,107,0) 70%); pointer-events:none;
}
#gs-supporter-page .cta-grid{
  position:relative; z-index:1; max-width:1010px; margin:0 auto;
  display:grid; grid-template-columns:1.25fr .85fr; align-items:center; gap:52px;
}
#gs-supporter-page .cta .eyebrow{ color:var(--gold-pale); text-align:left !important; }
#gs-supporter-page .cta h2{ font-size:clamp(27px,3.5vw,40px); margin:0 0 18px; max-width:17ch; color:#fff8ea; text-align:left !important; }
#gs-supporter-page .cta p{ font-size:16px; max-width:46ch; margin:0 0 30px; color:#eaddc8; text-align:left !important; }
#gs-supporter-page .cta p strong{ color:var(--gold-pale); font-weight:700; }
#gs-supporter-page .cta-actions{ display:flex; flex-wrap:wrap; gap:12px; align-items:center; }
#gs-supporter-page .cta-visual{ display:flex; align-items:center; justify-content:center; }
#gs-supporter-page .cta-visual .ring{
  position:relative; width:230px; height:230px; border-radius:50%;
  background:rgba(255,255,255,.05); border:1px solid rgba(233,198,107,.38);
  display:flex; align-items:center; justify-content:center;
}
#gs-supporter-page .cta-visual .ring::before{
  content:""; position:absolute; width:184px; height:184px; border-radius:50%;
  border:1px dashed rgba(233,198,107,.28);
}
#gs-supporter-page .cta-visual .ring img{
  width:170px; height:170px; object-fit:contain; padding:8px; border-radius:50%;
  background:var(--paper);
  box-shadow:0 12px 26px -8px rgba(0,0,0,.55), 0 0 0 1px rgba(233,198,107,.45);
}
#gs-supporter-page .footer-brand{ position:relative; overflow:hidden; padding:172px 0 52px; background:var(--paper); }
#gs-supporter-page .wave-footer{ position:absolute; left:0; right:0; top:-1px; height:130px; pointer-events:none; background:var(--wine-deep); }
#gs-supporter-page .wave-footer svg{ width:100%; height:100%; display:block; }
#gs-supporter-page .footer-brand .wrap{ position:relative; z-index:1; display:flex; align-items:center; justify-content:center; gap:44px; flex-wrap:wrap; }
#gs-supporter-page .footer-brand img{ height:140px; width:auto; flex:none; }
#gs-supporter-page .footer-brand .brand-legal{ text-align:center !important; font-family:-apple-system,sans-serif; font-size:13.5px; line-height:1.75; color:var(--ink-soft); }
#gs-supporter-page .footer-brand .brand-legal b{ display:block; color:var(--ink); font-size:15.5px; margin-bottom:7px; text-align:center !important; }
#gs-supporter-page .footer-brand .brand-caption{ margin-top:12px; font-size:11px; letter-spacing:.17em; text-transform:uppercase; color:var(--gold-deep); text-align:center !important; }
@media (max-width:900px){
  #gs-supporter-page .benefits{ grid-template-columns:minmax(0,1fr) minmax(0,1fr); }
  #gs-supporter-page .benefit.wide{ grid-column:span 2; }
  #gs-supporter-page .proof-bar{ grid-template-columns:minmax(0,1fr) minmax(0,1fr); }
  #gs-supporter-page .proof-cell:nth-child(2){ border-right:none; }
  #gs-supporter-page .proof-cell:nth-child(1), #gs-supporter-page .proof-cell:nth-child(2){ border-bottom:1px solid var(--line); }
  #gs-supporter-page .reg-grid{ grid-template-columns:1fr; }
}
@media (max-width:760px){
  #gs-supporter-page .wrap{ padding:0 22px; }
  #gs-supporter-page .hero-grid{ grid-template-columns:1fr; gap:38px; padding-top:56px; padding-bottom:190px; }
  #gs-supporter-page .hero .eyebrow, #gs-supporter-page .hero h1, #gs-supporter-page .hero .lede{
    text-align:center !important; margin-left:auto; margin-right:auto; max-width:100%;
  }
  #gs-supporter-page .hero-actions{ justify-content:center; }
  #gs-supporter-page .hero-photo{ order:-1; }
  #gs-supporter-page .benefits,
  #gs-supporter-page .ad-grid, #gs-supporter-page .steps{ grid-template-columns:1fr; }
  #gs-supporter-page .benefit.wide{ grid-column:span 1; }
  #gs-supporter-page .steps::before{ display:none; }
  #gs-supporter-page .sec-head{ margin-left:auto; margin-right:auto; }
  #gs-supporter-page .sec-head h2, #gs-supporter-page .sec-head p, #gs-supporter-page .sec-head .eyebrow{ text-align:center !important; }
  #gs-supporter-page .flow{ justify-content:center; }
  #gs-supporter-page .avanzato-detail{ padding:34px 26px; }
  #gs-supporter-page .cta{ padding:64px 22px; }
  #gs-supporter-page .cta-grid{ grid-template-columns:1fr; gap:34px; }
  #gs-supporter-page .cta .eyebrow, #gs-supporter-page .cta h2, #gs-supporter-page .cta p{
    text-align:center !important; max-width:100%; margin-left:auto; margin-right:auto;
  }
  #gs-supporter-page .cta-actions{ justify-content:center; }
  #gs-supporter-page .cta-visual{ order:-1; }
  #gs-supporter-page .cta-visual .ring{ width:168px; height:168px; }
  #gs-supporter-page .cta-visual .ring::before{ width:134px; height:134px; }
  #gs-supporter-page .cta-visual .ring img{ width:118px; height:118px; }
  #gs-supporter-page .footer-brand{ padding-top:162px; }
  #gs-supporter-page .footer-brand img{ height:112px; }
}
</style>
<div id="gs-supporter-page">

<section class="hero">
<div class="wrap hero-grid">
<div>
<span class="eyebrow sans">Sostieni l'Accademia</span>
<h1>Diventa Supporter dell'Accademia della Sfoglia.</h1>
<p class="lede">Non solo per i professionisti: anche per chi ama la sfoglia fatta a mano e vuole sostenere concretamente il riconoscimento di questa tradizione.</p>
<p class="lede">L'iscrizione all'Accademia della Sfoglia è gratuita per tutti e dà diritto a un mese di accesso gratuito all'area riservata, ricca di sorprese mai viste prima.</p>
<div class="price-tag"><span class="n">29,00 €</span><span class="l sans">quota annuale</span></div>
<div class="hero-actions">
<a class="btn" href="https://accademiadellasfoglia.it/iscrizione/">Iscriviti come Supporter</a>
<a class="btn btn-line" href="https://accademiadellasfoglia.it/corsi/">Scopri i corsi</a>
</div>
</div>
<div class="hero-photo">
<div class="frame"><img src="https://accademiadellasfoglia.it/wp-content/uploads/2026/06/cropped-Rina-Poletti-Mattarello-occhio-Lentium-860x1142-1.webp" alt="La Maestra Rina Poletti con il matterello"></div>
<div class="tag sans">Maestra Rina Poletti</div>
</div>
</div>
<div class="wave-hero" aria-hidden="true"><svg viewBox="0 0 1200 150" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><path d="M0,150 L1200,150 L1200,60 C1050,90 900,20 600,50 C300,80 150,20 0,55 Z" fill="#faf3e2"/></svg></div>
</section>

<div class="proof">
<div class="wrap">
<div class="proof-bar">
<div class="proof-cell"><div class="big">50+</div><div class="lab sans">Anni di esperienza<br>della Maestra</div></div>
<div class="proof-cell"><div class="big">5</div><div class="lab sans">Percorsi<br>di formazione</div></div>
<div class="proof-cell"><div class="big">4</div><div class="lab sans">Allievi al massimo<br>nel Corso Professionale</div></div>
<div class="proof-cell"><div class="big">80%</div><div class="lab sans">Del corso<br>è pratica</div></div>
</div>
</div>
</div>

<section class="perche">
<div class="wrap">
<div class="sec-head">
<span class="eyebrow sans">Perché diventare Supporter</span>
<h2>Un contributo che conta davvero</h2>
<p>L'Accademia della Sfoglia accoglie con entusiasmo non solo i professionisti, ma anche tutti gli appassionati che amano profondamente l'arte della sfoglia fatta a mano. Se pratichi la sfoglia a livello amatoriale o desideri sostenere la salvaguardia di questa antica tradizione, puoi aderire come Supporter.</p>
</div>
<div class="benefits">
<div class="benefit"><div class="ico"></div><h3>La missione dell'Accademia</h3><p>Contribuire concretamente al riconoscimento culturale ufficiale della sfoglia e delle Sfogline e degli Sfoglini.</p></div>
<div class="benefit"><div class="ico"></div><h3>Una comunità internazionale</h3><p>Far parte di una comunità internazionale di appassionati e professionisti.</p></div>
<div class="benefit"><div class="ico"></div><h3>Eventi e iniziative</h3><p>Partecipare a eventi, dimostrazioni in piazza, concorsi e iniziative di beneficenza.</p></div>
<div class="benefit"><div class="ico"></div><h3>Aggiornamenti e priorità</h3><p>Ricevere aggiornamenti esclusivi e priorità di iscrizione ai corsi.</p></div>
<div class="benefit wide"><div class="ico"></div><h3>Un percorso di crescita personale</h3><p>Come Supporter avrai la possibilità di perfezionarti attraverso i corsi tenuti direttamente dalla Maestra Rina Poletti. Con impegno e passione potrai migliorare la tua tecnica e aspirare, nel tempo, a superare la valutazione ufficiale per entrare nel Registro Pubblico delle Sfogline e Sfoglini, con scheda personale visibile sul sito.</p></div>
</div>
</div>
</section>

<section class="corsi">
<div class="wrap">
<div class="sec-head">
<span class="eyebrow sans">I corsi di formazione</span>
<h2>Cinque percorsi, un solo metodo</h2>
<p>La Maestra Rina Poletti, con oltre 50 anni di esperienza e docente presso ALMA – Scuola Internazionale di Cucina, tiene corsi di alto livello per ogni esigenza.</p>
</div>
<?php
// Prima era una quarta copia scritta a mano della "Vetrina dei Corsi"
// (stessi dati di gs_percorsi_corsi_dati() ma incollati qui a parte, con
// la vecchia grafica "a bottega") — si era disallineata dal resto pur
// dicendo di non doverlo fare. Ora usa la stessa veste "gs-cs" di "In
// Vetrina" e della pagina Corsi, leggendo la STESSA fonte dati, così un
// cambiamento in gs_percorsi_corsi_dati() aggiorna anche qui davvero
// (21/08/2026, richiesto da Ennio: "sostituisci queste schede nella
// pagina CORSI con le stesse schede della vetrina dei corsi").
if ( function_exists( 'gs_carosello_gs_cs_statico_html' ) && function_exists( 'gs_percorsi_corsi_dati' ) ) {
	$gs_vc_ico_corsi = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="8" y1="2.5" x2="8" y2="6.5"></line><line x1="16" y1="2.5" x2="16" y2="6.5"></line></svg>';
	$gs_vc_schede_corsi = '';
	foreach ( gs_percorsi_corsi_dati() as $gs_vc_c ) {
		$gs_vc_schede_corsi .= gs_carosello_gs_cs_scheda_semplice_html(
			$gs_vc_c['href'], '', $gs_vc_ico_corsi, $gs_vc_c['c1'], $gs_vc_c['c2'], $gs_vc_c['tag'], $gs_vc_c['titolo'], $gs_vc_c['testo']
		);
	}
	echo gs_carosello_gs_cs_statico_html( 'I corsi di formazione', '📅 La Vetrina dei Corsi', '', $gs_vc_schede_corsi );
}
?>
</div>
</section>

<section class="registri">
<div class="wrap">
<div class="sec-head">
<span class="eyebrow sans">I registri dell'Accademia</span>
<h2>Dove porta il tuo percorso</h2>
<p>Ogni corso ha uno sbocco preciso: due registri ufficiali dell'Accademia, con scheda personale pubblicata sul sito.</p>
</div>
<div class="reg-grid">
<div class="reg">
<h3>Registro degli Amatori</h3>
<div class="flow">
<span class="chip sans">I Primi Passi della Sfoglia</span>
<span class="pair"><span class="arrow">&rarr;</span><span class="chip sans">Sfoglia Intermedia</span></span>
<span class="pair"><span class="arrow">&rarr;</span><span class="chip sans end-gold">Registro degli Amatori</span></span>
</div>
<p>Chi affina gesti, impasti e tecnica con la Sfoglia Intermedia può accedere al Registro degli Amatori dell'Accademia della Sfoglia.</p>
</div>
<div class="reg pro">
<h3>Registro dei Professionisti</h3>
<div class="flow">
<span class="chip sans">Corso Professionale</span>
<span class="pair"><span class="arrow">&rarr;</span><span class="chip sans">Esame finale</span></span>
<span class="pair"><span class="arrow">&rarr;</span><span class="chip sans end">Registro dei Professionisti</span></span>
</div>
<p>Chi supera l'esame di valutazione diretto dalla Maestra Rina Poletti viene inserito nel Registro dei Professionisti dell'Accademia della Sfoglia.</p>
</div>
</div>
</div>
</section>

<section class="dettagli">
<div class="wrap">
<div class="avanzato-detail">
<span class="eyebrow sans">Corso Professionale — dettagli</span>
<h3>Dalla tecnica al Registro ufficiale</h3>
<div class="ad-grid">
<div>
<div class="ad-block"><span class="lbl">Obiettivo</span><p>Formare Sfogline e Sfoglini in grado di raggiungere un'eccellenza tecnica riconosciuta dall'Accademia, preparandoli alla valutazione ufficiale per l'inserimento nel Registro dei Professionisti dell'Accademia della Sfoglia.</p></div>
<div class="ad-block"><span class="lbl">Durata</span><p>Tipicamente da 1 a 5 giornate intensive, a seconda del programma scelto (possibilità di formula weekend o settimanale su richiesta).</p></div>
<div class="ad-block"><span class="lbl">Modalità</span>
<ul>
<li>Classi a numero chiuso: massimo 8–10 partecipanti per i livelli Base e Intermedio, per garantire un insegnamento altamente personalizzato</li>
<li>Corso Professionale: massimo 4 allievi per corso</li>
<li>Forte componente pratica (oltre l'80% del corso)</li>
<li>Dimostrazioni dal vivo della Maestra ed esercitazioni supervisionate</li>
<li>Materiale didattico e dispense fornite</li>
</ul>
</div>
<div class="ad-block"><span class="lbl">A chi è rivolto</span><p>Supporter dell'Accademia, amatori evoluti, cuochi professionisti, chef e chiunque desideri trasformare la propria passione in una competenza certificata di alto livello.</p></div>
</div>
<div>
<div class="ad-block"><span class="lbl">Programma dettagliato</span>
<ul>
<li>Tiratura della sfoglia a mano a diversi spessori, con perfezionamento estremo del gesto e della postura</li>
<li>Gestione avanzata degli impasti (idratazioni, tempi di riposo, farine speciali e antiche)</li>
<li>Tecniche di stesura, arrotolamento e taglio tradizionale</li>
<li>Realizzazione di tutte le paste classiche emiliano-romagnole: tagliatelle, tortellini, cappelletti, lasagne, ravioli</li>
<li>Studio delle varianti regionali e delle interpretazioni personali della Maestra</li>
<li>Segreti di conservazione, cottura ottimale e abbinamenti</li>
<li>Presentazione e finitura dei piatti per un livello professionale</li>
<li>Valutazione e correzione individuale dei gesti di ogni partecipante</li>
</ul>
</div>
<div class="ad-block"><span class="lbl">Requisiti di accesso</span><p>Aver frequentato almeno «I Primi Passi della Sfoglia» o dimostrare una buona padronanza di base della sfoglia fatta a mano (verifica preliminare con la Maestra).</p></div>
<div class="ad-block"><span class="lbl">Esame finale</span><p>Al termine del Corso Professionale i partecipanti potranno sostenere l'esame di valutazione diretto dalla Maestra Rina Poletti. Chi supera l'esame viene inserito nel Registro dei Professionisti dell'Accademia della Sfoglia, con scheda personale pubblicata sul sito.</p></div>
</div>
</div>
</div>
</div>
</section>

<section class="aderire">
<div class="wrap">
<div class="sec-head">
<span class="eyebrow sans">Come aderire</span>
<h2>Tre passi per iniziare</h2>
</div>
<div class="steps">
<div class="step"><div class="n">1</div><p>Compila il modulo di iscrizione come Supporter</p></div>
<div class="step"><div class="n">2</div><p>Scegli e prenota il <a href="https://accademiadellasfoglia.it/calendario-corsi/">corso più adatto al tuo livello</a></p></div>
<div class="step"><div class="n">3</div><p>Inizia il tuo percorso all'interno dell'Accademia della Sfoglia</p></div>
</div>
</div>
</section>

<section class="cta">
<div class="cta-grid">
<div>
<span class="eyebrow sans">Custodi della tradizione e Maestri del futuro</span>
<h2>Sei pronto a portare la tua passione al livello successivo?</h2>
<p>La quota di adesione come Supporter è di <strong>29,00 euro all'anno</strong> e contribuisce direttamente al sostegno delle attività culturali, di formazione e di beneficenza dell'Associazione.</p>
<div class="cta-actions">
<a class="btn" href="https://accademiadellasfoglia.it/iscrizione/">Iscriviti ora come Supporter</a>
<a class="btn btn-ghost" href="https://accademiadellasfoglia.it/calendario-corsi/">Date e prenotazioni</a>
</div>
</div>
<div class="cta-visual">
<div class="ring"><img src="https://accademiadellasfoglia.it/wp-content/uploads/2026/08/logo-Accademia-Della-Sfoglia.png" alt="Logo dell'Accademia della Sfoglia"></div>
</div>
</div>
</section>

<div class="footer-brand">
<div class="wave-footer" aria-hidden="true"><svg viewBox="0 0 1200 130" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><path d="M0,0 L1200,0 L1200,90 C1050,60 900,130 600,100 C300,70 150,130 0,95 Z" fill="#5c1f1d"/></svg></div>
<div class="wrap">
<img src="https://accademiadellasfoglia.it/wp-content/uploads/2026/08/logo-Accademia-Della-Sfoglia.png" alt="Accademia della Sfoglia di Rina Poletti - marchio registrato">
<div class="brand-legal">
<b>Accademia della Sfoglia di Missione Matterello</b>
Sede legale: Via Chiesa, 26 – 44041 Reno Centese (FE)<br>
Codice Fiscale 90012930385
<div class="brand-caption">Marchio registrato</div>
</div>
</div>
</div>

</div>
	<?php
	return ob_get_clean();
}
