#!/usr/bin/env node
// Assembla la pagina di anteprima (index.html) estraendo da ogni file
// il blocco compreso fra i marcatori "COPIA DA QUI" e "FINO A QUI".
// Uso:  node costruisci-anteprima.mjs [percorso/di/uscita.html] [--artifact]
// Con --artifact produce solo il contenuto del <body>, senza intestazione.

import { writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { CAROSELLI, QUI, estrai, FONT } from './blocchi.mjs';

const CSS_ANTEPRIMA = `
  :root{color-scheme:light}
  body{margin:0;background:#FAF5EC;color:#2A1F17;
       font-family:Archivo,'Helvetica Neue',Helvetica,Arial,sans-serif}
  .demo-testata{padding:clamp(46px,7vw,86px) clamp(20px,6vw,86px) clamp(32px,4vw,52px)}
  .demo-marchio{display:flex;align-items:center;gap:12px;margin-bottom:26px}
  .demo-marchio span{font-family:'Bodoni Moda','Bodoni MT',Didot,Georgia,serif;font-size:18px;font-weight:700}
  .demo-titolo{margin:0;font-family:'Bodoni Moda','Bodoni MT',Didot,Georgia,serif;font-weight:400;
               font-size:clamp(38px,6vw,74px);line-height:.98;letter-spacing:-.02em}
  .demo-titolo em{font-style:italic;color:#A93B27}
  .demo-sottotitolo{margin:20px 0 0;max-width:62ch;font-size:clamp(16px,1.5vw,19px);
                    line-height:1.6;color:#4A3B30;text-wrap:pretty}
  .demo-indice{display:flex;flex-wrap:wrap;gap:10px;margin-top:30px;padding:0;list-style:none}
  .demo-indice a{display:inline-block;padding:9px 16px;border:1px solid #C9B79C;color:#2A1F17;
                 text-decoration:none;font-size:13px;letter-spacing:.1em;text-transform:uppercase;
                 transition:background .2s ease,color .2s ease}
  .demo-indice a:hover{background:#2A1F17;color:#FAF5EC}
  .demo-scheda{border-top:2px solid #2A1F17;margin-top:clamp(40px,6vw,72px)}
  .demo-scheda-testata{display:flex;gap:clamp(18px,3vw,40px);flex-wrap:wrap;
                       padding:22px clamp(20px,6vw,86px) 26px}
  .demo-numero{font-family:'Bodoni Moda','Bodoni MT',Didot,Georgia,serif;font-size:44px;
               line-height:1;color:#A93B27;font-variant-numeric:tabular-nums}
  .demo-dati{flex:1 1 320px}
  .demo-nome{margin:0 0 6px;font-family:'Bodoni Moda','Bodoni MT',Didot,Georgia,serif;
             font-weight:400;font-size:28px;line-height:1.1}
  .demo-testo{margin:0;max-width:64ch;font-size:15px;line-height:1.6;color:#4A3B30;text-wrap:pretty}
  .demo-meta{display:flex;flex-direction:column;gap:8px;flex:0 1 auto;min-width:0;font-size:12px;
             letter-spacing:.16em;text-transform:uppercase;color:#6B5A4C}
  .demo-meta code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;
                  letter-spacing:0;text-transform:none;color:#A93B27;overflow-wrap:anywhere}
  .demo-piede{padding:clamp(46px,6vw,80px) clamp(20px,6vw,86px);border-top:2px solid #2A1F17;
              font-size:14px;line-height:1.7;color:#4A3B30}
  .demo-piede strong{font-weight:600;color:#2A1F17}
`;

const MARCHIO = `
  <div class="demo-marchio">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#A93B27" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="9"></circle>
      <path d="M4.6 9.2h14.8M4.6 14.8h14.8M9.2 3.4v17.2M14.8 3.4v17.2"></path>
    </svg>
    <span>Accademia della Sfoglia</span>
  </div>`;

const sezioni = CAROSELLI.map((c, i) => {
  const n = String(i + 1).padStart(2, '0');
  return `
<section class="demo-scheda" id="carosello-${n}">
  <div class="demo-scheda-testata">
    <div class="demo-numero">${n}</div>
    <div class="demo-dati">
      <h2 class="demo-nome">${c.nome}</h2>
      <p class="demo-testo">${c.testo}</p>
    </div>
    <div class="demo-meta">
      <div>${c.dove}</div>
      <code>caroselli/${c.file}</code>
    </div>
  </div>
${estrai(c.file)}
</section>`;
}).join('\n');

const indice = CAROSELLI.map((c, i) =>
  `    <li><a href="#carosello-${String(i + 1).padStart(2, '0')}">${String(i + 1).padStart(2, '0')} · ${c.nome}</a></li>`
).join('\n');

const testata = `
<header class="demo-testata">${MARCHIO}
  <h1 class="demo-titolo">Sei caroselli<br><em>per il sito</em></h1>
  <p class="demo-sottotitolo">Sei componenti indipendenti, senza librerie esterne: si incollano nella pagina così come sono. Stessa palette del volantino, tastiera e lettori di schermo supportati, animazioni disattivate per chi ha chiesto meno movimento. Dove il testo è fra parentesi quadre va sostituito con i dati veri.</p>
  <ul class="demo-indice">
${indice}
  </ul>
</header>`;

const piede = `
<footer class="demo-piede">
  <p><strong>Come si inseriscono.</strong> Ogni file in <code>caroselli/</code> contiene un blocco delimitato da <code>▼ COPIA DA QUI ▼</code> e <code>▲ FINO A QUI ▲</code>: dentro ci sono lo stile, il markup e lo script del componente. Si incolla nella pagina e funziona; nessuna dipendenza da caricare.</p>
  <p><strong>I caratteri.</strong> Bodoni Moda e Archivo vanno caricati una volta sola nel <code>&lt;head&gt;</code> del sito, come già fa il volantino.</p>
  <p><strong>Le immagini.</strong> I riquadri colorati sono segnaposto: si sostituiscono impostando <code>--img:url('/immagini/nome.jpg')</code> e togliendo la classe <code>is-segnaposto</code>.</p>
</footer>`;

const corpo = `${testata}\n${sezioni}\n${piede}`;
const soloCorpo = process.argv.includes('--artifact');
const uscita = process.argv[2] && !process.argv[2].startsWith('--')
  ? resolve(process.cwd(), process.argv[2])
  : resolve(QUI, 'index.html');

const pagina = soloCorpo
  ? `<title>Caroselli della Sfoglia</title>\n<style>@import url("${FONT}");\n${CSS_ANTEPRIMA}</style>\n${corpo}\n`
  : `<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sei caroselli per accademiadellasfoglia.it</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="${FONT}">
<style>${CSS_ANTEPRIMA}</style>
</head>
<body>
${corpo}
</body>
</html>
`;

writeFileSync(uscita, pagina, 'utf8');
console.log(`Scritto ${uscita} (${(pagina.length / 1024).toFixed(1)} KB, ${CAROSELLI.length} caroselli)`);
