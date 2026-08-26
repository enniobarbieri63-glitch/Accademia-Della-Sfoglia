# Il menu che sale da solo su tutte le pagine, e il pulsante «Nascondi logo»

**Richiesta di Ennio, 26/08/2026:** applicare il meccanismo che porta il menu in alto
automaticamente **a tutte le pagine del sito**, e — se si può — **togliere il pulsante
«Nascondi logo»**, che a quel punto non servirebbe più.

**Ennio ha chiesto di aspettare istruzioni prima di procedere. Queste sono le istruzioni,
e la prima parte è una notizia che cambia la domanda.**

---

# 1. Il meccanismo è **già** su tutte le pagine del sito

Verificato nel codice, non dedotto.

`gaming-sfogline.php:527`:

```php
function gs_enqueue_assets() {
	wp_enqueue_style( 'gaming-sfogline', … );
	wp_enqueue_script( 'gaming-sfogline', … );
```

**Nessuna condizione.** Nessun controllo su quale pagina sia, nessuna limitazione alle
pagine del gaming. È agganciata a `wp_enqueue_scripts` (riga 603), che gira **su ogni pagina
pubblica del sito**. Lo dice anche un commento poco sotto, alla riga 536: *«questa funzione
gira ovunque, non solo dove servono»*.

E dentro `gaming.js`, `gsHeaderToggleInit()` (riga 8130) viene chiamata **senza condizioni**
(riga 8196), su qualunque pagina abbia un `header#header` — cioè tutte, perché è
l'intestazione del tema.

**Quindi non c'è niente da estendere: c'è da capire dove non funziona, e perché.** Sono cose
diverse, e la seconda si risolve in modo diverso.

---

# 2. Le tre ragioni per cui può sembrare che non funzioni

Sono tutte e tre reali e **tutte e tre verificabili**. Non tirare a indovinare quale sia:
possono essercene due insieme.

## a) Lo sfarfallio — e questa è già in lavorazione

Ennio ha segnalato *«clicco su Nascondi logo, sfarfalla l'immagine della pagina in modo
grave»*. **Un meccanismo che sfarfalla viene letto come «non funziona»**, ed è
probabilmente una parte grossa di questa richiesta.

La causa e la correzione sono nella verifica della 3.281.0, sezione **C**: il giro fra
`display:none` → `ResizeObserver` → `padding-top` che si accorcia → la pagina che sale da
sola → `valutaScroll()` che lo legge come uno scorrimento vero.

**E c'è un precedente che rafforza la diagnosi.** `gaming-sfogline.php:534-545`, commento
del **21/08/2026**:

> *«il nuovo font Google bloccava/ritardava il rendering quel tanto che bastava a mandare in
> tilt il calcolo JS dell'altezza del menu fisso del tema (`--gs-header-h`) — intestazione
> duplicata a metà pagina, **sfarfallio**, pagina che si blocca (segnalato da Ennio il
> 21/08/2026 su Rina Poletti, **non è un problema di quella pagina sola**)»*

**Lo stesso sintomo, cinque giorni prima, attribuito a un font.** Il font è stato tolto e il
sintomo è passato — ma è tornato appena la 3.279 e la 3.280 hanno aggiunto due elementi in
più da far sparire.

**Il font non era la causa: era un innesco.** La causa è che `gsAllineaSpazioMenu()` può
essere richiamata mentre la pagina si sta ancora muovendo, e qualunque cosa perturbi
l'altezza dell'intestazione al momento sbagliato fa partire il giro. **Correggere quella,
come nella sezione C, chiude tutti e due gli episodi.**

**Questa va fatta per prima**, e da sola potrebbe bastare a rispondere a metà della richiesta
di Ennio.

## b) Le pagine con un iframe grande — un limite fisico, non un difetto

`gaming.js:8171-8179`, commento già nel codice:

> *«Nato per le pagine con un iframe grande (es. "Lentium Notizie", "Rina Poletti": lì lo
> scroll automatico non può funzionare, perché un iframe di un altro dominio è "opaco" a
> JavaScript per una regola di sicurezza del browser)»*

**È vero, ed è insuperabile.** Quando la rotella scorre sopra un riquadro che contiene un
altro sito, **scorre quel sito, non la pagina**: il browser non manda nessun segnale di
scorrimento alla pagina che lo contiene, e non c'è modo di ottenerlo — è una regola di
sicurezza, non una limitazione del codice.

Su quelle pagine il menu **non salirà mai da solo**, per quanto si lavori. È esattamente
per questo che il pulsante è stato rimesso il 18/08/2026, dieci giorni fa, dopo essere
stato tolto il 17.

**Questo è il motivo per cui il pulsante non si può togliere del tutto.** Ma si può togliere
da quasi tutte le pagine — vedi il punto 4.

## c) Le pagine dove l'intestazione del tema non è «fissa»

`gaming.js:8019`, dentro `gsAllineaSpazioMenu()`:

```js
			if ( $header.css( 'position' ) !== 'fixed' ) {
				$middle.css( 'padding-top', '' );
				$header.css( 'top', '' );
				document.documentElement.style.setProperty( '--gs-header-h', '0px' );
				document.documentElement.style.setProperty( '--gs-nastro-h', '0px' );
				return;
			}
```

**Se l'intestazione del tema non è fissa, la funzione esce subito e non fa niente.**

Il tema Newspaper permette di configurare l'intestazione in più modi, e alcuni modelli di
pagina (a tutta larghezza, pagine di destinazione, pagine costruite con un editor visuale)
possono non usare l'intestazione fissa. **Su quelle pagine il meccanismo è spento per
progetto**, e nessuno se n'è mai accorto perché nessuno ha guardato.

**Questa è la causa che non è mai stata verificata**, ed è quella che potrebbe spiegare le
pagine che Ennio ha in mente.

---

# 3. Cosa fare per primo: scoprire dove non funziona

Non riscrivere niente finché non sai su quali pagine il problema c'è davvero. **Chiedi a
Ennio l'elenco delle pagine dove il menu non sale**, e per ognuna guarda tre cose con gli
strumenti per sviluppatori del browser:

| Da guardare | Come | Cosa significa |
|---|---|---|
| `getComputedStyle(document.querySelector('header#header')).position` | consolle | se **non** è `fixed` → causa **c** |
| c'è un `iframe` che occupa gran parte della pagina? | a occhio | → causa **b**, insuperabile |
| scorrendo piano, il logo va e viene da solo? | a occhio | → causa **a**, lo sfarfallio |

**Riporta la tabella compilata prima di scrivere codice.** Tre pagine bastano, se sono
diverse fra loro.

---

# 4. Sul togliere «Nascondi logo»

**Ennio ha ragione sul principio**, e la risposta è **sì, ma non del tutto e non adesso.**

## Perché non del tutto

Sulle pagine con un iframe grande (punto **b**) il meccanismo automatico **non può
funzionare**, mai. Togliere il pulsante lì significa che su quelle pagine **non c'è più
nessun modo di guadagnare spazio** — si torna indietro rispetto a oggi.

E sono proprio le pagine dove serve di più: un sito dentro il sito occupa tutto lo schermo,
e l'intestazione sopra ruba quel poco che resta.

## Perché non adesso

Finché lo sfarfallio c'è, **non si può giudicare se il meccanismo automatico basta**: la
prova sarebbe falsata da un difetto che stiamo già correggendo. Togliere il pulsante prima
vorrebbe dire togliere l'unica cosa che oggi funziona.

## Come farlo bene: il pulsante solo dove serve davvero

Invece di togliere o tenere, **mostrarlo solo dove il meccanismo automatico non arriva**.
In `gsHeaderToggleInit()`, al posto di `$( 'body' ).append( $btn );` (riga 8186):

```js
			// Il pulsante manuale serve SOLO dove lo scorrimento automatico non
			// può funzionare: una pagina occupata da un riquadro che contiene un
			// altro sito (iframe di un altro dominio) non manda nessun segnale
			// di scorrimento alla pagina che lo contiene — è una regola di
			// sicurezza del browser, non una limitazione nostra (è la ragione
			// per cui il pulsante era stato rimesso il 18/08/2026 dopo essere
			// stato tolto il 17). Ovunque altro il menu sale da solo e il
			// pulsante è solo un oggetto in più sullo schermo (Ennio,
			// 26/08/2026).
			// Si guarda l'ALTEZZA del riquadro, non la sua presenza: la mappa di
			// un artigiano e un video di YouTube sono iframe anche loro, ma
			// piccoli, e in quelle pagine resta tutt'intorno spazio da scorrere.
			var serveIlPulsante = false;
			$( 'iframe' ).each( function () {
				if ( this.getBoundingClientRect().height > window.innerHeight * 0.6 ) {
					serveIlPulsante = true;
					return false;
				}
			} );
			if ( serveIlPulsante ) {
				$( 'body' ).append( $btn );
			}
```

**Attenzione a due cose, o non funziona:**

1. **Il momento della misura.** Gli iframe possono avere altezza 0 finché non sono
   caricati. Questo controllo va fatto **al `load` della finestra**, non al `ready` del
   documento — oppure ripetuto una seconda volta dopo un secondo, come già si fa per
   `gsAllineaSpazioMenu()` (riga 8062).
2. **Il pulsante va creato comunque**, e solo *aggiunto* in modo condizionato: il resto della
   funzione lo usa (`$btn.on( 'click', … )`), e togliere la creazione romperebbe il codice
   sotto.

## E il CSS

`gaming.css:3771-3807` (`.gs-header-toggle`) **va lasciato dov'è**: il pulsante continua a
esistere, solo su meno pagine. **Non cancellare quelle righe.**

---

# 5. L'ordine, e non va cambiato

| | Cosa | Perché in questa posizione |
|---|---|---|
| **1** | **Correggere lo sfarfallio** (verifica 3.281.0, sezione C) | finché c'è, nessuna prova su questo argomento vale niente |
| **2** | **Ennio prova le pagine** e dice se il menu adesso sale | può darsi che qui finisca tutto |
| **3** | **Compilare la tabella del punto 3** sulle pagine che restano | dice quale delle tre cause è, e sono rimedi diversi |
| **4** | **Il pulsante solo dove serve** (punto 4) | ultimo, quando si sa dove serve davvero |

**Non fare il 4 prima del 2.** È l'errore già fatto il 17 agosto — il pulsante tolto
confidando nello scorrimento automatico, e rimesso il giorno dopo perché su certe pagine non
bastava. **Rifarlo uguale sarebbe la terza volta.**

---

# Cosa serve da Ennio

**Una cosa sola, e dopo il punto 1:** *quali sono le pagine dove il menu non sale?* Tre nomi
bastano.

Perché la risposta cambia tutto:

- se sono **le pagine con l'altro sito dentro** (Lentium Notizie, Rina Poletti) → è il limite
  del browser, il pulsante lì resta, e non c'è altro da fare;
- se sono **pagine normali** → è la causa **c**, l'intestazione non fissa, e si corregge;
- se **dopo la correzione dello sfarfallio salgono tutte** → era solo quello, e il pulsante si
  può togliere quasi ovunque.
