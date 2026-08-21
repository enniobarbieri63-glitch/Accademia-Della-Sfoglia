// Elenco dei caroselli e lettura del blocco da copiare.
// Lo usano sia costruisci-anteprima.mjs sia costruisci-da-incollare.mjs.

import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

export const QUI = dirname(fileURLToPath(import.meta.url));
const APERTURA = '<!-- ▼ COPIA DA QUI ▼ -->';
const CHIUSURA = '<!-- ▲ FINO A QUI ▲ -->';

export const CAROSELLI = [
  {
    file: '01-slideshow-apertura.html',
    nome: "Slideshow d'apertura",
    dove: 'Testata della home',
    testo: 'Tre messaggi che si alternano da soli in dissolvenza, con barra di avanzamento sui punti, tasto di pausa, frecce e scorrimento col dito.'
  },
  {
    file: '02-corsi-schede.html',
    nome: 'Corsi in programma',
    dove: 'Pagina corsi, home',
    testo: 'Schede affiancate che scorrono con scroll-snap: dito, rotellina e frecce. Funziona anche a JavaScript spento, il JS aggiunge solo frecce e puntini.'
  },
  {
    file: '03-testimonianze.html',
    nome: 'Le voci della bottega',
    dove: 'Sotto i corsi, prima del modulo di contatto',
    testo: 'Una testimonianza alla volta, in corsivo Bodoni. I testi qui sono fac-simile: vanno sostituiti con recensioni reali.'
  },
  {
    file: '04-galleria-bottega.html',
    nome: 'Dentro la bottega',
    dove: 'Pagina "chi siamo" o "la bottega"',
    testo: 'Immagine grande più provini in basso, contatore e didascalia. Lo scatto grande viene generato dai provini: la lista da aggiornare è una sola.'
  },
  {
    file: '05-passo-passo.html',
    nome: 'Come nasce una sfoglia',
    dove: 'Pagina "il metodo"',
    testo: 'Quattro passi illustrati a tratto, con rotaia di avanzamento in alto. Racconta il metodo senza bisogno di fotografie.'
  },
  {
    file: '06-nastro-scorrevole.html',
    nome: 'Nastro scorrevole',
    dove: 'Divisorio fra due sezioni',
    testo: 'Marquee infinito su due righe in direzioni opposte, in pausa al passaggio del mouse. Con i loghi al posto delle parole diventa la fascia dei partner.'
  }
];

export function estrai(file) {
  const testo = readFileSync(resolve(QUI, file), 'utf8');
  const a = testo.indexOf(APERTURA);
  const b = testo.indexOf(CHIUSURA);
  if (a === -1 || b === -1) throw new Error(`Marcatori mancanti in ${file}`);
  return testo.slice(a + APERTURA.length, b).trim();
}

export const FONT = 'https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400;0,6..96,500;0,6..96,700;1,6..96,400;1,6..96,500&family=Archivo:wght@400;500;600&display=swap';
