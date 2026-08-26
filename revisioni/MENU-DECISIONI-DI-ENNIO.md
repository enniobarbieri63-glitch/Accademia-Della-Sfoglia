# «Fissa il menu in alto» — le decisioni di Ennio, e una cosa trovata per strada

**Ennio ha risposto (26/08/2026):**

> *«il menu non sale in Rina Poletti e Lentium Notizie, per cui nelle altre pagine elimina il
> pulsante nascondi logo, che io cambierei nel nome: "fissa il menu in alto", che è più
> comprensibile»*

**Le due pagine sono esattamente le due previste**: quelle con dentro un altro sito. Quindi
la causa è la **(b)** del documento precedente — il limite del browser — e **le altre due
cause non riguardano le pagine che interessano a Ennio.**

Questo semplifica tutto. Ci sono tre cose da fare, e una quarta che ho trovato guardando.

---

# 1. Il pulsante resta solo dove serve

**Non elencare le due pagine a mano.** Se Ennio ne aggiunge una terza con un video o un altro
sito dentro, un elenco scritto nel codice non la conosce e il pulsante non comparirebbe.

**Si guarda cosa c'è nella pagina, non come si chiama.** In `gsHeaderToggleInit()`, al posto
di `$( 'body' ).append( $btn );` (`gaming.js:8186`):

```js
			// Il pulsante manuale serve SOLO dove lo scorrimento automatico non
			// può funzionare: se la pagina è occupata da un riquadro che
			// contiene un altro sito, la rotella scorre QUEL sito e il browser
			// non lo dice alla pagina che lo contiene — è una regola di
			// sicurezza, non una limitazione nostra. Confermato da Ennio il
			// 26/08/2026: succede su "Rina Poletti" e "Lentium Notizie".
			// Si guarda l'ALTEZZA del riquadro e non il nome della pagina, così
			// una pagina nuova con un altro sito dentro funziona da sola: e si
			// guarda l'altezza e non la semplice presenza, perché la mappa di un
			// artigiano e un video di YouTube sono riquadri anche loro, ma
			// piccoli, e lì intorno c'è tutta la pagina da scorrere.
			function gsServeIlPulsante() {
				var serve = false;
				$( 'iframe' ).each( function () {
					if ( this.getBoundingClientRect().height > window.innerHeight * 0.6 ) {
						serve = true;
						return false;
					}
				} );
				return serve;
			}
			function gsMettiIlPulsanteSeServe() {
				if ( $btn.parent().length ) { return; }        // già messo
				if ( gsServeIlPulsante() ) { $( 'body' ).append( $btn ); }
			}
			gsMettiIlPulsanteSeServe();
			// Un riquadro che contiene un altro sito ha altezza 0 finché non ha
			// finito di caricare: la prima misura da sola non basta.
			$( window ).on( 'load', gsMettiIlPulsanteSeServe );
			setTimeout( gsMettiIlPulsanteSeServe, 1500 );
```

**Due cose da non sbagliare:**

1. **`$btn` va creato come adesso, sempre.** Solo l'`append` diventa condizionato: il resto
   della funzione lo usa (`$btn.on( 'click', … )`), e non crearlo romperebbe il codice sotto.
2. **La misura va rifatta dopo il caricamento.** Un riquadro non ancora caricato è alto zero,
   e al primo controllo la pagina sembrerebbe una pagina normale. Le tre chiamate qui sopra
   servono tutte e tre; `if ( $btn.parent().length ) { return; }` impedisce di aggiungerlo due
   volte.

---

# 2. Il nome nuovo

Il pulsante cambia stato a ogni clic, quindi servono **due scritte**, non una. Quella che si
vede deve dire **cosa succede se lo premi**, non com'è adesso.

| Situazione | Scritta | Icona |
|---|---|---|
| logo visibile → premendo si compatta | **«Fissa il menu in alto»** | ▲ |
| menu già compatto → premendo si torna indietro | **«Rimetti il logo»** | ▼ |

In `gaming.js`, la creazione (righe 8181-8185) e il gestore del clic (8187-8194):

```js
			var $btn = $(
				'<button type="button" class="gs-header-toggle" aria-pressed="false" aria-label="Fissa il menu in alto">' +
					'<span class="gs-ht-ico">▲</span><span class="gs-ht-lbl">Fissa il menu in alto</span>' +
				'</button>'
			);
```

e nel clic, al posto delle due coppie `'Mostra logo' : 'Nascondi logo'`:

```js
					.attr( 'aria-label', nuovo ? 'Rimetti il logo' : 'Fissa il menu in alto' )
					.find( '.gs-ht-ico' ).text( nuovo ? '▼' : '▲' )
					.end().find( '.gs-ht-lbl' ).text( nuovo ? 'Rimetti il logo' : 'Fissa il menu in alto' );
```

**«Rimetti il logo» è una mia proposta, non una richiesta di Ennio:** lui ha indicato solo la
scritta del primo stato. Se preferisce altro, è una parola.

**E c'è un altro posto da cambiare, che è facile dimenticare.** Il pulsante esiste **anche
dentro il Pannello di Controllo** (`gaming.js:1423-1432`, classe
`.gs-toggle-logo-pannello`), con le stesse scritte «Nascondi logo» / «Mostra logo». Va
allineato, altrimenti lo stesso comando si chiama in due modi diversi nello stesso sito.

**Da controllare anche nel PHP**: cerca `Nascondi logo` in tutto il plugin — quel pulsante è
disegnato da qualche parte in `control-panel.php` o `pannello-nuovo.php`, e la scritta
iniziale sta lì, non nel JavaScript.

---

# 3. Il CSS resta dov'è

`gaming.css:3771-3807` (`.gs-header-toggle`): **non cancellare niente.** Il pulsante continua
a esistere, solo su meno pagine. Cambia la scritta, non l'aspetto.

---

# 4. La cosa trovata per strada: su quelle due pagine il pulsante si disfa da solo

Questa non era nella richiesta, ma riguarda esattamente le due pagine di cui parla Ennio, e
probabilmente è **il motivo per cui il pulsante non dà la soddisfazione che dovrebbe.**

## Cosa succede

`valutaScroll()` (`gaming.js:8152`) contiene:

```js
				if ( y <= sogliaCima ) {
					if ( $( 'body' ).hasClass( 'gs-logo-hidden' ) ) { applica( false ); }
				}
```

`sogliaCima` vale **60 pixel**. Quindi: **vicino alla cima della pagina, il logo torna
sempre.**

Su una pagina occupata da un altro sito, **la pagina che lo contiene non scorre quasi
niente**: si resta sempre entro quei 60 pixel. Quindi la persona preme «Fissa il menu in
alto», il menu si compatta — e **al primo movimento, anche accidentale, torna tutto
com'era.**

**È l'unico posto dove quel pulsante è l'unica possibilità, ed è anche il posto dove viene
disfatto più facilmente.**

## La protezione esiste già nel codice — ma sorveglia una porta murata

`gsAllineaSpazioMenu()`, riga **8015**:

```js
			// Con il menu nascosto apposta (pulsante "Nascondi intestazione")
			// lo spazio va tenuto a zero: non ricalcolarlo qui, altrimenti un
			// resize/ridisegno lo rimetterebbe a posto da solo vanificando il
			// click dell'utente.
			if ( $( 'body' ).hasClass( 'gs-header-hidden' ) ) { return; }
```

*«vanificando il click dell'utente»* — **il problema era già stato capito, e la protezione
scritta.**

Ma **`gs-header-hidden` non viene messa da nessuno.** Ho cercato in tutto il plugin — JS, PHP
e CSS: quella classe compare in **due soli punti, e tutti e due la leggono soltanto**
(`gaming.js:8015` e `gaming.css:3839`). È un avanzo della vecchia funzione «Nascondi menu»,
sostituita il 17/08/2026 da «Nascondi logo», che usa una classe diversa
(`gs-logo-hidden`).

**La protezione è rimasta a sorvegliare una porta che non c'è più.**

## La correzione

Non serve rianimare quella riga — faceva una cosa diversa (teneva lo spazio a zero, che qui
sarebbe sbagliato: l'intestazione esiste ancora, è solo più bassa). Serve la cosa che
intendeva: **una scelta fatta a mano non viene disfatta dallo scorrimento.**

In `gsHeaderToggleInit()`, accanto a `var ultimoScrollY` (riga 8148):

```js
			// Una scelta fatta col pulsante vale finché non si ripreme il
			// pulsante: lo scorrimento non la disfa. Serve soprattutto sulle
			// pagine con dentro un altro sito, dove la pagina contenitore non
			// scorre quasi (si resta entro i 60 pixel di sogliaCima) e quindi
			// il logo tornerebbe al primo movimento — proprio dove il pulsante
			// è l'unica possibilità. L'intenzione era già nel codice a riga
			// 8015, ma sorvegliava la classe "gs-header-hidden", che dal
			// 17/08/2026 non viene più messa da nessuno (26/08/2026).
			var sceltaManuale = false;
```

in cima a `valutaScroll()`, subito dopo `tickPianificato = false;`:

```js
				if ( sceltaManuale ) { ultimoScrollY = window.pageYOffset || document.documentElement.scrollTop; return; }
```

*(riallineare `ultimoScrollY` anche qui, altrimenti al momento di riprendere il controllo si
legge come un salto tutta la distanza percorsa nel frattempo)*

e dentro il gestore del clic del pulsante, come prima riga:

```js
				sceltaManuale = true;
```

**Compromesso da dichiarare:** dopo aver premuto il pulsante, su quella pagina lo scorrimento
automatico non funziona più fino al ricaricamento. **È giusto così**: chi preme un pulsante
sta dicendo cosa vuole, e non deve vederselo cambiare sotto le dita. E sulle pagine dove il
pulsante ora comparirà, lo scorrimento automatico **non funzionava comunque** — è tutta la
ragione per cui il pulsante è lì.

## E già che ci sei: due avanzi da togliere

1. **`gaming.css:3839-3845`**, il blocco `body.gs-header-hidden #page header#header { … }`:
   nessuno mette quella classe, **quelle regole non si applicano mai.** Da cancellare.
2. **`gaming.js:8015`**, il controllo che le corrisponde: da cancellare **insieme** al CSS,
   non prima e non dopo — così restano coerenti.
3. **Un commento che dice il falso**, `gaming.js:8127-8129`:
   > *«La scelta si ricorda per la sessione di navigazione (sessionStorage), così chi lo
   > nasconde non se lo ritrova acceso di nuovo passando da una pagina all'altra del sito.»*

   **Nel codice non c'è nessun `sessionStorage`.** L'ho cercato: la funzione non ne usa. È il
   racconto di un comportamento che è stato tolto e mai cancellato dal commento. **Riscrivi
   il commento su quello che il codice fa davvero** — o, se Ennio quel comportamento lo
   vuole, dillo e lo specifichiamo, ma non lasciare un commento che promette una cosa che non
   c'è.

---

# L'ordine

| | Cosa | Nota |
|---|---|---|
| **1** | La correzione dello sfarfallio (verifica 3.281.0, sezione **C**) | resta da fare comunque: Ennio l'ha segnalata, e non riguarda solo queste due pagine |
| **2** | Il punto **4** qui sopra — la scelta manuale che non si disfa | è quello che fa funzionare il pulsante dove serve |
| **3** | Il punto **1** — il pulsante solo dove serve | dopo, altrimenti si prova un pulsante che ancora si disfa |
| **4** | Il punto **2** — le scritte nuove, JS **e** pannello **e** PHP | ultimo, è solo testo |
| **5** | Gli avanzi da togliere | quando conviene |

## Come si prova

**Su «Rina Poletti» o «Lentium Notizie»**, sul sito vero:

1. il pulsante **c'è**;
2. premendolo il menu si compatta e **resta compatto** anche muovendo la rotella sopra il
   riquadro e attorno;
3. la scritta è diventata **«Rimetti il logo»**, e premendola si torna indietro.

**Su una pagina normale** (la home, «Le Sfogline», il pannello):

4. il pulsante **non c'è più**;
5. scorrendo in giù il menu sale da solo, **senza sfarfallare**;
6. risalendo, torna.

**Il punto 5 è la prova che vale per tutto il resto del sito**, ed è quella su cui Ennio
deve dire sì o no.
