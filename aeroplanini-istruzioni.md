# Aeroplanini con striscione — istruzioni

Scena a schermo pieno: aeroplanini disegnati a vettori che girano per il cielo,
ognuno con uno striscione al traino su cui si scrivono messaggi e si appendono
loghi e volti. Stesso stile grafico dei palloncini dell'Accademia.

- Codice: `aeroplanini-striscione.html`
- Online: https://claude.ai/code/artifact/35da58a7-95f3-4cdf-8b36-875a0c0e2528
- Branch: `claude/animated-planes-banner-7tejec`

---

## 1. Per usarlo

### Aprirlo e basta
Il file nel repository e' il **corpo** dell'artifact: comincia da `<title>`, senza
`<!doctype>`, `<html>`, `<head>`, `<body>`, perche' quelli li mette l'artifact
quando pubblica. Per aprirlo con doppio clic o metterlo su un sito serve la
versione completa: basta avvolgerlo cosi'.

```html
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
  <!-- qui dentro tutto il contenuto di aeroplanini-striscione.html -->
</body>
</html>
```

Il `meta viewport` non e' un dettaglio: senza, sul telefono la sala comandi
resta larga come su un computer e i comandi escono dallo schermo.

### Metterlo dentro un sito
Sta bene come pagina intera. Dentro una pagina che ha gia' altro, va in un
riquadro suo:

```html
<iframe src="aeroplanini.html" title="Aeroplanini dell'Accademia"
        style="width:100%;height:70vh;border:0"></iframe>
```

La pagina occupa tutto lo spazio che le dai e non fa scorrere niente: `body` ha
`overflow:hidden` e la scena e' `position:fixed`.

### Serve internet?
Solo per i caratteri (Bodoni Moda e Archivo da Google Fonts). Senza rete il
disegno resta identico, cambiano solo le lettere che ripiegano su un serif di
sistema. Tutto il resto — aerei, volti, striscioni, motori — nasce dentro il
file: nessuna immagine, nessun file audio, nessuna libreria.

---

## 2. La sala comandi

| Comando | Cosa fa |
|---|---|
| Aeroplanini | da 1 a 16 |
| Velocita' | da fermo a 3× |
| Dimensione | da 0,5× a 2,2× |
| Volume / Motori | il rombo, costruito dal vivo, parte al primo tocco |
| Pausa, Nuovi aerei | ferma tutto; rifa la squadriglia da capo |
| Testo / Volti / Testo e volti | cosa porta lo striscione |
| Messaggi | una riga per striscione, si ripetono se gli aerei sono di piu' |
| In coda | quali volti e loghi volano: tutti, nessuno, o a scelta |
| + Loghi e foto | carica immagini dal computer |

Un clic su un aeroplanino gli fa fare il giro della morte.

Le scelte restano nel browser di chi guarda (`localStorage`, chiave
`accademia.aeroplanini.v1`): messaggi, volti scelti, loghi caricati e la
posizione dei cursori. Le immagini caricate **non partono dal computer di chi
guarda**: vengono ridotte a 240px e tenute li'.

---

## 3. Per Claude: come e' fatto dentro

Un file solo, nessuna dipendenza. Ordine: `<style>`, il segno della scena, la
sala comandi, poi uno `<script>` con tutto.

### Le funzioni che contano

| Funzione | Cosa fa |
|---|---|
| `voltoSVG(r, id)` | il volto della sfoglina in un riquadro 100×100 — lo stesso dei palloncini |
| `aereoSVG(r, id)` | l'aeroplanino: naso a destra a x=+58, coda a x=-52, centro a 0,0 |
| `nuovoAereo(i)` | crea un aereo: elemento, disegno, posizione, rotta |
| `misura(p)` | quanto e' grande sullo schermo |
| `ricomponi(p)` | rifa `viewBox`, larghezza e perno quando cambia verso o carico |
| `costruisciStriscione(p)` | costruisce nastro, scritta e medaglie (una volta sola) |
| `aggiornaStriscione(p, dt)` | l'onda, fotogramma per fotogramma |
| `assegnaCarichi()` | decide chi porta cosa; da chiamare dopo ogni cambio nei comandi |
| `larghezzaTesto(t, fs)` | misura vera della scritta con un righello nascosto |
| `motore` | il rombo: due seghe filtrate, un soffio d'aria, il battito dell'elica |

### Le manopole nel codice

```js
var ATTACCO = 90;   // quanto sta indietro l'inizio dello striscione
var ALT = 46;       // altezza del nastro
var RAG = 18;       // raggio delle medaglie in coda
var CORPO = 114;    // lunghezza dell'aeroplanino in unita' di disegno
```

- Colori: `TINTE`, `FAZZOLETTI`, `CAPELLI`, `INCHIOSTRO`, `CARTA` (le stesse dei palloncini).
- Grandezza sullo schermo: il `0.135` dentro `misura()`.
- Onda dello striscione: `k` (lunghezza d'onda) e `amp` (ampiezza) in `nuovoAereo()`.
- Assetto: il `0.86` nel giro di `passo()` tiene la rotta entro una trentina di
  gradi dall'orizzontale. Alzalo e volano piu' piatti, abbassalo e impennano.
- Quanti volti per striscione: i numeri `2` (testo e volti) e `5` (solo volti)
  in `assegnaCarichi()`.

### Mettere loghi fissi nel file
Oggi la galleria parte con otto volti disegnati (`nuoviVolti(8)`). Per avere
loghi sempre presenti, aggiungili li' con l'immagine gia' dentro il codice:

```js
galleria = nuoviVolti(6).concat([
  { id: "logo-accademia", tipo: "immagine", nome: "Accademia",
    dati: "data:image/png;base64,iVBORw0KGgo..." }
]);
```

Quadrate e sui 240px: le medaglie sono tonde e ritagliano al centro.

### Le trappole, gia' pagate una volta

1. **`clip-path` e `transform` sullo stesso gruppo non convivono.** Il ritaglio
   viene trasformato pure lui e il volto sparisce. Ci vogliono due gruppi: quello
   fuori porta il `clip-path`, quello dentro il `transform`.
2. **`filter: drop-shadow` sugli aerei costava tre quarti dei fotogrammi**
   (12 fps invece di 43 con 16 aerei): la sfocatura ripassa su striscioni lunghi
   centinaia di pixel. La profondita' ora la da' l'opacita', che non costa nulla.
   Non rimettere l'ombra.
3. **Lo striscione sta sempre dietro.** Quando la rotta punta a sinistra
   (`p.verso === -1`) il disegno dell'aereo si specchia con `scale(-1,1)`, il
   nastro passa dall'altra parte e la pagina ruota di `angolo - 180`. E' l'unico
   modo perche' le scritte restino dritte: specchiare tutto rovescerebbe anche
   le lettere. C'e' una zona morta (`cos` fra -0,2 e 0,2) per non farlo
   sfarfallare quando un aereo vola quasi verticale.
4. **La lunghezza della scritta va misurata, non indovinata.** Stimarla a
   caratteri tagliava le frasi lunghe. Il righello nascosto misura davvero, e
   quando i caratteri di Google arrivano (`document.fonts.ready`) si rimisura e
   si rifanno i nastri.
5. **L'audio non parte prima di un tocco**: nessun browser lo permette. I motori
   nascono accesi ma muti e prendono vita al primo `pointerdown`.
6. **Occhio agli `id` dentro l'SVG**: gradienti, ritagli e il tracciato del testo
   si richiamano per `id`, e in cielo ci sono molti aerei. C'e' un `contatore`
   che li tiene diversi; se aggiungi elementi con `id`, passa da li'.

### Provarlo
Il repository non ha impalcature di prova. Con Playwright:

```js
const { chromium } = require('playwright');
const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
const page = await b.newPage({ viewport: { width: 1440, height: 900 } });
page.on('pageerror', e => console.log('ERRORE', e.message));
await page.goto('file:///.../involucro.html');   // la versione completa, con il meta viewport
```

Vale la pena guardare sempre tre cose: che non ci siano errori in console, che i
fotogrammi reggano con 16 aerei alla dimensione massima, e uno sguardo vero allo
schermo — molti guai qui si vedono solo a occhio.
