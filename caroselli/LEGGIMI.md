# Sei caroselli per accademiadellasfoglia.it

Sei componenti indipendenti, pronti da incollare nelle pagine del sito.
Nessuna libreria esterna: solo HTML, CSS e un po' di JavaScript, circa 3 KB
l'uno. Stessa palette e stessi caratteri del volantino in `design/`.

| # | File | Cos'è | Dove sta bene |
|---|------|-------|---------------|
| 01 | `01-slideshow-apertura.html` | Slideshow a tutta larghezza, tre messaggi in dissolvenza | Testata della home |
| 02 | `02-corsi-schede.html` | Schede dei corsi che scorrono di lato | Pagina corsi, home |
| 03 | `03-testimonianze.html` | Una testimonianza alla volta, in corsivo | Sotto i corsi, prima dei contatti |
| 04 | `04-galleria-bottega.html` | Foto grande più provini, con didascalie | Pagina "la bottega" |
| 05 | `05-passo-passo.html` | Quattro passi illustrati, con barra di avanzamento | Pagina "il metodo" |
| 06 | `06-nastro-scorrevole.html` | Nastro infinito su due righe | Divisorio fra due sezioni |

`index.html` è la pagina di anteprima con tutti e sei insieme: si apre nel
browser con un doppio clic, senza server.

## Come si inserisce un carosello nel sito

Ogni file è una pagina completa, ma la parte da copiare è solo quella fra i
due marcatori:

```html
<!-- ▼ COPIA DA QUI ▼ -->
   ... stile + markup + script del carosello ...
<!-- ▲ FINO A QUI ▲ -->
```

Si incolla quel blocco nel punto della pagina in cui deve comparire e
funziona subito. I nomi delle classi cominciano tutti per `ads-`, quindi non
si accavallano con il resto del sito, e i sei caroselli possono convivere
nella stessa pagina (l'anteprima lo dimostra).

### I caratteri

Bodoni Moda e Archivo vanno caricati **una volta sola** nel `<head>` del
sito:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400;0,6..96,500;0,6..96,700;1,6..96,400;1,6..96,500&family=Archivo:wght@400;500;600&display=swap">
```

Se non si caricano, i caroselli restano leggibili: la scaletta di riserva è
Georgia per i titoli e Helvetica/Arial per il testo.

### Le immagini

I riquadri colorati sono segnaposto fatti con sfumature CSS. Per mettere le
foto vere basta cambiare la variabile `--img` sull'elemento e togliere la
classe `is-segnaposto` (è quella che disegna la trama a righine):

```html
<!-- prima -->
<div class="ads-hero__media is-segnaposto" style="--img:radial-gradient(...)"></div>
<!-- dopo -->
<div class="ads-hero__media" style="--img:url('/immagini/sfoglia-01.jpg')"></div>
```

Misure consigliate: 2000×1125 px per lo slideshow e la galleria (16:9),
1200×900 px per le schede dei corsi (4:3), 400×300 px per i provini.

### I dati da sostituire

Tutto ciò che è fra parentesi quadre va sostituito con i dati veri:
`[PREZZO]`, `[GG]`, `[mese]`, `[ANNO]`, `[N]`, `[Nome Cognome]`. Sono gli
stessi segnaposto del volantino.

**Le testimonianze del carosello 03 sono testi fac-simile**: servono a far
vedere l'impaginazione e vanno sostituite con recensioni reali, con il nome
di chi le ha scritte davvero.

### I colori

Ogni componente dichiara la palette in cima al proprio blocco di stile:

```css
--ads-crema:#FAF5EC;  --ads-carta:#F2E9DA;  --ads-inchiostro:#2A1F17;
--ads-rosso:#A93B27;  --ads-oro:#D9A227;    --ads-grigio:#6B5A4C;
```

Se si preferisce tenerle in un punto solo, si spostano su `:root` nel foglio
di stile del sito e si cancellano dai singoli blocchi.

## Cosa è già previsto

- **Tastiera**: frecce destra e sinistra dentro ogni carosello, tutti i
  comandi raggiungibili con il Tab, contorno di messa a fuoco visibile.
- **Lettori di schermo**: `aria-roledescription="carosello"`, etichette in
  italiano sui comandi, diapositive non attive escluse dal Tab con `inert`.
- **Dito**: si scorre trascinando, su tutti e sei.
- **Movimento ridotto**: con `prefers-reduced-motion` lo scorrimento
  automatico e le animazioni si spengono da soli.
- **Scorrimento automatico**: solo nei caroselli 01 e 03, in pausa al
  passaggio del mouse, con il Tab dentro il componente o a scheda nascosta.
  Il 01 ha anche il tasto di pausa.
- **Senza JavaScript**: il carosello 02 resta scorrevole (usa lo scroll
  nativo), il 06 resta leggibile ma fermo, gli altri mostrano la prima
  diapositiva.

## Rigenerare l'anteprima

`index.html` è generato: raccoglie i blocchi fra i marcatori dei sei file.
Dopo una modifica a un componente basta:

```sh
node caroselli/costruisci-anteprima.mjs
```

Serve Node 18 o successivo. Con `node costruisci-anteprima.mjs uscita.html
--artifact` si ottiene la sola parte interna, per incorporarla altrove.
