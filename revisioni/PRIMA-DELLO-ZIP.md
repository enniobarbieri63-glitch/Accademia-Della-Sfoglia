# Due cose prima di costruire lo zip

Lette le modifiche riportate. **Le tre verifiche sullo sfarfallio le hai fatte prima di
scrivere, ed è la cosa giusta**: adesso la correzione poggia su qualcosa di controllato, non
sulla mia parola.

Due punti, e vanno visti **adesso** perché costano niente ora e un giro intero dopo.

---

# 1 · Un difetto che ho introdotto io: il pulsante può mentire

**File:** `assets/js/gaming.js`, il gestore del clic del pulsante (era a riga 8187 nella
3.281.0) — VERIFICATO sul codice della 3.281.0

Il gestore è fatto così:

```js
			$btn.on( 'click', function () {
				var nuovo = ! $( 'body' ).hasClass( 'gs-logo-hidden' );
				applica( nuovo );
				$btn.attr( 'aria-pressed', nuovo ? 'true' : 'false' )
					.attr( 'aria-label', … )
					.find( '.gs-ht-ico' ).text( … )
					.end().find( '.gs-ht-lbl' ).text( … );
			} );
```

**La scritta del pulsante viene cambiata sempre, qualunque cosa faccia `applica()`.**

E nella correzione C io ti ho fatto mettere, in cima ad `applica()`:

```js
			function applica( nascondi ) {
				if ( inTransizione ) { return; }
				…
```

**Quindi:** se qualcuno preme il pulsante due volte in meno di 320 millesimi di secondo — che
su un interruttore è il gesto più naturale che ci sia, ed è esattamente il caso di **L5**,
che abbiamo corretto due giorni fa sul diploma — il secondo clic **non fa niente**, ma la
scritta cambia lo stesso.

**Risultato: il pulsante dice «Rimetti il logo» mentre il logo è lì, o il contrario.** E da
quel momento è invertito finché non si ricarica la pagina.

**È un mio errore di specifica:** ho scritto l'uscita anticipata in `applica()` senza
guardare che chi la chiama aggiorna la scritta comunque.

## Correzione

`applica()` dice se ha agito davvero, e la scritta si aggiorna solo allora:

```js
			function applica( nascondi ) {
				if ( inTransizione ) { return false; }
				inTransizione = true;
				$( 'body' ).toggleClass( 'gs-logo-hidden', nascondi );
				setTimeout( function () {
					gsAllineaSpazioMenu();
					ultimoScrollY = window.pageYOffset || document.documentElement.scrollTop;
					inTransizione = false;
				}, 320 );
				return true;
			}
```

e nel gestore del clic:

```js
			$btn.on( 'click', function () {
				var nuovo = ! $( 'body' ).hasClass( 'gs-logo-hidden' );
				// La scritta si aggiorna SOLO se applica() ha agito davvero:
				// durante la transizione (320 ms) applica() esce subito, e
				// cambiare la scritta lo stesso lascerebbe il pulsante che dice
				// una cosa mentre la pagina ne mostra un'altra — invertito fino
				// al ricaricamento. Su un interruttore due clic ravvicinati sono
				// il gesto più comune che c'è (è lo stesso caso di L5 sul
				// diploma). Trovato il 26/08/2026.
				if ( ! applica( nuovo ) ) { return; }
				sceltaManuale = true;
				$btn.attr( 'aria-pressed', nuovo ? 'true' : 'false' )
					.attr( 'aria-label', nuovo ? 'Rimetti il logo' : 'Fissa il menu in alto' )
					.find( '.gs-ht-ico' ).text( nuovo ? '▼' : '▲' )
					.end().find( '.gs-ht-lbl' ).text( nuovo ? 'Rimetti il logo' : 'Fissa il menu in alto' );
			} );
```

**Nota l'ordine:** `sceltaManuale = true` va **dopo** il controllo su `applica()`. Un clic
che non ha fatto niente non deve nemmeno spegnere lo scorrimento automatico.

**Lo stesso controllo serve anche nel pulsante gemello del Pannello di Controllo**
(`.gs-toggle-logo-pannello`, era a `gaming.js:1423`), se anche lì la scritta viene cambiata
dopo aver chiamato qualcosa che può non agire. **Guardalo**, non darlo per scontato: quel
gestore è scritto in modo diverso e usa `toggleClass` direttamente.

---

# 2 · Una domanda: il punto 3 c'è?

Nel tuo resoconto leggo, nell'ordine: la correzione C, poi A1, poi B1, poi «i tre pezzi del
punto 4 (scelta manuale)», poi le scritte nuove, poi gli avanzi da togliere.

**Non vedo il pezzo che mostra il pulsante solo dove serve** — `gsServeIlPulsante()` e
l'`append` condizionato, sezione **1** di `MENU-DECISIONI-DI-ENNIO.md`.

Può darsi che sia dentro la modifica da +45 righe e che il resoconto non lo nomini. **Ma
conferma prima dello zip**, perché è **la cosa che Ennio ha chiesto per prima**:

> *«nelle altre pagine elimina il pulsante nascondi logo»*

Senza quel pezzo, il pulsante continua a comparire ovunque — con il nome nuovo, ma ovunque —
e la richiesta non è stata fatta.

## E una nota sul perché è facile perderselo

**Ci sono due numerazioni diverse in giro, e collidono.** In
`MENU-DECISIONI-DI-ENNIO.md` il «punto 5» sono gli avanzi da togliere; in `DA-FARE-ADESSO.md`
il «punto 5» è il Tavolo. Nel tuo resoconto compaiono tutti e due con lo stesso numero, a
poche righe di distanza.

**Colpa mia**: ho scritto due elenchi numerati che si sovrappongono. Da qui in avanti, per
sicurezza, **cita il nome della cosa e non il numero** — «il pulsante solo dove serve»,
«gli avanzi», «il Tavolo» — così non c'è modo di sbagliare.

---

# Il resto

Tutto quello che hai riportato è coerente con quanto scritto, e due cose meritano di essere
dette:

- **hai verificato i tre passaggi prima di applicare la correzione C**, invece di fidarti — è
  la cosa che ho chiesto e la ragione per cui adesso possiamo dire che il giro è quello;
- **hai trovato il terzo punto con la scritta «Nascondi logo»** (`control-panel.php:764`) che
  io non avevo citato, eseguendo l'istruzione invece di fermarti al mio elenco.

**Vai avanti col Tavolo.** Quando lo zip è pronto lo verifico tutto insieme.
