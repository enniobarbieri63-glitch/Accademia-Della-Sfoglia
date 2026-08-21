#!/usr/bin/env node
// Costruisce la pagina "da-incollare.html": i sei blocchi di codice pronti
// da copiare, con le istruzioni per chi (o cosa) li inserirà nel sito.
// Uso:  node costruisci-da-incollare.mjs [percorso/di/uscita.html] [--artifact]

import { writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { CAROSELLI, QUI, estrai, FONT } from './blocchi.mjs';

const esc = t => t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

const ISTRUZIONI = `Ti passo sei componenti carosello, già scritti e verificati, per il sito accademiadellasfoglia.it.

Come inserirli:
- Ogni blocco è autosufficiente: contiene stile, markup e script del carosello. Va incollato nella pagina così com'è, senza riscriverlo e senza estrarne i pezzi.
- Le classi cominciano tutte per "ads-": non toccarle, servono a non far collidere questi stili con il resto del sito.
- I sei blocchi possono convivere nella stessa pagina.
- Nel <head> del sito devono essere caricati una volta sola i caratteri Bodoni Moda e Archivo:
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="${FONT}">

Cosa va cambiato, e solo questo:
- Il testo fra parentesi quadre ([PREZZO], [GG], [mese], [ANNO], [N], [Nome Cognome]) va sostituito con i dati veri.
- Le immagini sono segnaposto in CSS: per metterci le foto si cambia la variabile --img in url('/percorso/foto.jpg') e si toglie la classe "is-segnaposto".
- Gli href dei link (#corsi, #prenota, #contatti…) vanno puntati alle pagine vere.
- Le testimonianze del carosello 3 sono testi fac-simile: vanno sostituite con recensioni reali.

Da non toccare: la struttura ARIA, gli attributi inert, i blocchi @media (prefers-reduced-motion) e la logica JavaScript. Servono a far funzionare i caroselli con la tastiera, con i lettori di schermo e per chi ha chiesto meno animazioni.`;

const CSS = `
  :root{color-scheme:light}
  body{margin:0;background:#FAF5EC;color:#2A1F17;
       font-family:Archivo,'Helvetica Neue',Helvetica,Arial,sans-serif}
  .cd-testata{padding:clamp(44px,7vw,80px) clamp(20px,6vw,80px) clamp(28px,4vw,44px)}
  .cd-marchio{display:flex;align-items:center;gap:12px;margin-bottom:24px}
  .cd-marchio span{font-family:'Bodoni Moda','Bodoni MT',Didot,Georgia,serif;font-size:18px;font-weight:700}
  .cd-titolo{margin:0;font-family:'Bodoni Moda','Bodoni MT',Didot,Georgia,serif;font-weight:400;
             font-size:clamp(36px,5.6vw,68px);line-height:1;letter-spacing:-.02em;text-wrap:balance}
  .cd-titolo em{font-style:italic;color:#A93B27}
  .cd-occhio{margin:0 0 14px;font-size:13px;letter-spacing:.24em;text-transform:uppercase;color:#A93B27}
  .cd-intro{margin:20px 0 0;max-width:66ch;font-size:clamp(16px,1.5vw,18px);line-height:1.6;color:#4A3B30;text-wrap:pretty}
  .cd-blocco{border-top:2px solid #2A1F17;padding:26px clamp(20px,6vw,80px) clamp(34px,4vw,52px)}
  .cd-riga{display:flex;align-items:flex-start;gap:clamp(16px,3vw,34px);flex-wrap:wrap;margin-bottom:18px}
  .cd-numero{font-family:'Bodoni Moda','Bodoni MT',Didot,Georgia,serif;font-size:40px;line-height:1;
             color:#A93B27;font-variant-numeric:tabular-nums}
  .cd-dati{flex:1 1 300px;min-width:0}
  .cd-nome{margin:0 0 6px;font-family:'Bodoni Moda','Bodoni MT',Didot,Georgia,serif;font-weight:400;
           font-size:26px;line-height:1.12}
  .cd-testo{margin:0;max-width:64ch;font-size:15px;line-height:1.6;color:#4A3B30;text-wrap:pretty}
  .cd-lato{display:flex;flex-direction:column;align-items:flex-start;gap:10px;flex:0 1 auto;min-width:0}
  .cd-dove{font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#6B5A4C}
  .cd-file{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;color:#A93B27;overflow-wrap:anywhere}
  .cd-copia{display:inline-flex;align-items:center;gap:9px;padding:11px 20px;cursor:pointer;
            border:1px solid #2A1F17;background:transparent;color:#2A1F17;font-family:inherit;
            font-size:12px;letter-spacing:.14em;text-transform:uppercase;
            transition:background .2s ease,color .2s ease}
  .cd-copia:hover{background:#2A1F17;color:#FAF5EC}
  .cd-copia.is-fatto{background:#A93B27;border-color:#A93B27;color:#FAF5EC}
  .cd-copia--pieno{background:#A93B27;border-color:#A93B27;color:#FAF5EC}
  .cd-copia--pieno:hover{background:#8A2F1F;border-color:#8A2F1F;color:#FAF5EC}
  .cd-codice{position:relative;background:#241A13;border:1px solid #3D2E22}
  .cd-codice pre{margin:0;max-height:min(52vh,460px);overflow:auto;padding:20px 22px}
  .cd-codice code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12.5px;
                  line-height:1.65;color:#EFE3D2;white-space:pre;tab-size:2}
  .cd-misura{padding:9px 22px;border-top:1px solid #3D2E22;font-size:12px;color:#B49B80;
             display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap}
  .cd-brief{background:#F2E9DA;border:1px solid #C9B79C}
  .cd-brief pre{margin:0;max-height:min(46vh,380px);overflow:auto;padding:20px 22px;
                white-space:pre-wrap;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
                font-size:13px;line-height:1.6;color:#3A2C22}
  .cd-piede{border-top:2px solid #2A1F17;padding:clamp(34px,5vw,60px) clamp(20px,6vw,80px);
            font-size:14px;line-height:1.7;color:#4A3B30}
  .cd-piede code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:#A93B27}
  :focus-visible{outline:2px solid #A93B27;outline-offset:3px}
`;

const MARCHIO = `
  <div class="cd-marchio">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#A93B27" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="9"></circle>
      <path d="M4.6 9.2h14.8M4.6 14.8h14.8M9.2 3.4v17.2M14.8 3.4v17.2"></path>
    </svg>
    <span>Accademia della Sfoglia</span>
  </div>`;

const blocchi = CAROSELLI.map((c, i) => {
  const n = String(i + 1).padStart(2, '0');
  const codice = estrai(c.file);
  const righe = codice.split('\n').length;
  const peso = (Buffer.byteLength(codice, 'utf8') / 1024).toFixed(1);
  return `
<section class="cd-blocco" id="blocco-${n}">
  <div class="cd-riga">
    <div class="cd-numero">${n}</div>
    <div class="cd-dati">
      <h2 class="cd-nome">${c.nome}</h2>
      <p class="cd-testo">${c.testo}</p>
    </div>
    <div class="cd-lato">
      <div class="cd-dove">${c.dove}</div>
      <div class="cd-file">caroselli/${c.file}</div>
      <button type="button" class="cd-copia" data-copia="#codice-${n}">Copia il codice</button>
    </div>
  </div>
  <div class="cd-codice">
    <pre id="codice-${n}" tabindex="0"><code>${esc(codice)}</code></pre>
    <div class="cd-misura"><span>${righe} righe · ${peso} KB</span><span>stile + markup + script, tutto qui dentro</span></div>
  </div>
</section>`;
}).join('\n');

const testata = `
<header class="cd-testata">${MARCHIO}
  <p class="cd-occhio">Sei caroselli · da consegnare a chi mette mano al sito</p>
  <h1 class="cd-titolo">Il codice<br><em>da incollare</em></h1>
  <p class="cd-intro">Ogni blocco qui sotto è un carosello completo — stile, markup e script insieme — e va incollato nella pagina così com'è. Il primo riquadro è il messaggio da passare a chi lo inserirà: dice dove va ogni pezzo e cosa si può cambiare.</p>
</header>

<section class="cd-blocco" id="istruzioni">
  <div class="cd-riga">
    <div class="cd-numero">00</div>
    <div class="cd-dati">
      <h2 class="cd-nome">Il messaggio per chi inserisce</h2>
      <p class="cd-testo">Da incollare per primo, prima dei sei blocchi di codice: spiega come vanno usati, cosa va sostituito e cosa non va toccato.</p>
    </div>
    <div class="cd-lato">
      <button type="button" class="cd-copia cd-copia--pieno" data-copia="#istruzioni-testo">Copia il messaggio</button>
      <button type="button" class="cd-copia" data-tutto="1">Copia messaggio + sei blocchi</button>
    </div>
  </div>
  <div class="cd-brief">
    <pre id="istruzioni-testo" tabindex="0">${esc(ISTRUZIONI)}</pre>
  </div>
</section>`;

const piede = `
<footer class="cd-piede">
  <p><strong>Se serve il file intero.</strong> Ogni carosello vive anche come pagina a sé in <code>caroselli/</code>, apribile nel browser per vederlo da solo; il blocco qui sopra è la parte compresa fra i marcatori <code>▼ COPIA DA QUI ▼</code> e <code>▲ FINO A QUI ▲</code>.</p>
  <p><strong>Questa pagina è generata.</strong> Dopo una modifica ai componenti si rifà con <code>node caroselli/costruisci-da-incollare.mjs</code>, così il codice mostrato resta quello vero.</p>
</footer>`;

const SCRIPT = `
<script>
(function () {
  function scrivi(bottone, testo) {
    var vecchio = bottone.dataset.etichetta || bottone.textContent;
    bottone.dataset.etichetta = vecchio;
    bottone.textContent = testo;
    bottone.classList.add('is-fatto');
    window.setTimeout(function () {
      bottone.textContent = bottone.dataset.etichetta;
      bottone.classList.remove('is-fatto');
    }, 2200);
  }

  function copia(testo, bottone, elemento) {
    var riuscito = false;
    try {
      var area = document.createElement('textarea');
      area.value = testo;
      area.setAttribute('readonly', '');
      area.style.position = 'fixed';
      area.style.top = '-1000px';
      area.style.opacity = '0';
      document.body.appendChild(area);
      area.select();
      riuscito = document.execCommand('copy');
      area.remove();
    } catch (e) { riuscito = false; }

    if (riuscito) { scrivi(bottone, 'Copiato'); return; }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(testo).then(function () {
        scrivi(bottone, 'Copiato');
      }).catch(function () { seleziona(bottone, elemento); });
      return;
    }
    seleziona(bottone, elemento);
  }

  // Ultima spiaggia: se gli appunti sono negati, almeno selezioniamo il testo.
  function seleziona(bottone, elemento) {
    if (!elemento) { scrivi(bottone, 'Copia a mano'); return; }
    var intervallo = document.createRange();
    intervallo.selectNodeContents(elemento);
    var scelta = window.getSelection();
    scelta.removeAllRanges();
    scelta.addRange(intervallo);
    elemento.focus();
    scrivi(bottone, 'Selezionato: Ctrl+C');
  }

  function tuttoIlPacchetto() {
    var parti = [document.querySelector('#istruzioni-testo').textContent];
    document.querySelectorAll('[id^="codice-"]').forEach(function (pre) {
      var sezione = pre.closest('.cd-blocco');
      var nome = sezione.querySelector('.cd-nome').textContent;
      var numero = sezione.querySelector('.cd-numero').textContent;
      parti.push('--- ' + numero + ' · ' + nome + ' ---\\n\\n' + pre.textContent);
    });
    return parti.join('\\n\\n');
  }

  document.querySelectorAll('.cd-copia').forEach(function (bottone) {
    bottone.addEventListener('click', function () {
      if (bottone.dataset.tutto) { copia(tuttoIlPacchetto(), bottone, null); return; }
      var elemento = document.querySelector(bottone.dataset.copia);
      copia(elemento.textContent, bottone, elemento);
    });
  });
})();
<\/script>`;

const corpo = `${testata}\n${blocchi}\n${piede}\n${SCRIPT}`;
const soloCorpo = process.argv.includes('--artifact');
const uscita = process.argv[2] && !process.argv[2].startsWith('--')
  ? resolve(process.cwd(), process.argv[2])
  : resolve(QUI, 'da-incollare.html');

const pagina = soloCorpo
  ? `<title>Il codice da incollare</title>\n<style>@import url("${FONT}");\n${CSS}</style>\n${corpo}\n`
  : `<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Il codice da incollare — Accademia della Sfoglia</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="${FONT}">
<style>${CSS}</style>
</head>
<body>
${corpo}
</body>
</html>
`;

writeFileSync(uscita, pagina, 'utf8');
console.log(`Scritto ${uscita} (${(pagina.length / 1024).toFixed(1)} KB, ${CAROSELLI.length} blocchi)`);
