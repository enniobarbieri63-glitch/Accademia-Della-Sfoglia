#!/usr/bin/env node
// Costruisce "per-claude.md": un unico file di testo con le istruzioni e i
// sei blocchi di codice, da allegare o incollare in un'altra conversazione.
// Uso:  node costruisci-per-claude.mjs [percorso/di/uscita.md]

import { writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { CAROSELLI, QUI, estrai, FONT } from './blocchi.mjs';

const testa = `# Sei caroselli per accademiadellasfoglia.it

Questo file contiene sei componenti carosello già scritti e verificati, con
le istruzioni per inserirli nel sito. I blocchi di codice vanno usati così
come sono.

## Come inserirli

- Ogni blocco è autosufficiente: contiene stile, markup e script del
  carosello. Va incollato nella pagina indicata senza riscriverlo e senza
  estrarne i pezzi.
- Le classi cominciano tutte per \`ads-\`: non vanno rinominate, servono a non
  far collidere questi stili con il resto del sito.
- I sei blocchi possono convivere nella stessa pagina.
- Nel \`<head>\` del sito vanno caricati una volta sola i caratteri Bodoni Moda
  e Archivo:

\`\`\`html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="${FONT}">
\`\`\`

## Cosa va cambiato, e solo questo

- Il testo fra parentesi quadre (\`[PREZZO]\`, \`[GG]\`, \`[mese]\`, \`[ANNO]\`,
  \`[N]\`, \`[Nome Cognome]\`) va sostituito con i dati veri.
- Le immagini sono segnaposto in CSS: per metterci le foto si cambia la
  variabile \`--img\` in \`url('/percorso/foto.jpg')\` e si toglie la classe
  \`is-segnaposto\`.
- Gli \`href\` dei link (\`#corsi\`, \`#prenota\`, \`#contatti\`…) vanno puntati alle
  pagine vere.
- Le testimonianze del carosello 3 sono testi fac-simile: vanno sostituite
  con recensioni reali.
- Nel carosello 2 il calendario si aggiorna dagli attributi delle schede:
  \`data-corso\`, \`data-tipo\` (base, intensivo, ripieni, sumisura),
  \`data-posti\` (0 = esaurito, 1–2 = ultimi posti) e \`data-totale\`. Il
  cartellino e la riga della disponibilità li scrive il JavaScript da questi
  numeri. L'indirizzo per le prenotazioni è la costante \`MAIL\` in cima allo
  script.

## Da non toccare

La struttura ARIA, gli attributi \`inert\`, i blocchi
\`@media (prefers-reduced-motion)\` e la logica JavaScript: servono a far
funzionare i caroselli con la tastiera, con i lettori di schermo e per chi
ha chiesto meno animazioni.

---
`;

const sezioni = CAROSELLI.map((c, i) => {
  const n = String(i + 1).padStart(2, '0');
  const codice = estrai(c.file);
  return `
## ${n} · ${c.nome}

**Dove va:** ${c.dove}
**File di origine:** \`caroselli/${c.file}\`

${c.testo}

\`\`\`html
${codice}
\`\`\`
`;
}).join('\n---\n');

const uscita = process.argv[2]
  ? resolve(process.cwd(), process.argv[2])
  : resolve(QUI, 'per-claude.md');

const testo = testa + sezioni;
writeFileSync(uscita, testo, 'utf8');
console.log(`Scritto ${uscita} (${(testo.length / 1024).toFixed(1)} KB, ${CAROSELLI.length} blocchi)`);
