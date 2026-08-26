# Sfarfallio, A1 e B1 — i punti 1 e 4 dell'ordine, in un file solo

**Estratto da `VERIFICA-3.281.0.md`, che non è arrivato.** Contiene le tre voci che servono
per partire: la causa e la correzione dello sfarfallio (**punto 1** dell'ordine di
consegna) e le due correzioni da due righe (**punto 4**).

**Questo file è autosufficiente**: non serve avere il documento grande per eseguirlo. Il
resto della verifica della 3.281.0 — A2, B2, B3, D2 — resta in coda al punto 7 e non
blocca niente.

**Nota su un punto trovato da te:** hai segnalato un terzo posto con la scritta «Nascondi
logo», `control-panel.php:764`, che nel documento sul menu non era citato. **È giusto e va
cambiato anche quello** — era esattamente lo scopo dell'istruzione «cerca `Nascondi logo` in
tutto il plugin». Tre punti, non due.

---

# C · Lo sfarfallio: ho trovato la causa, non serve indagare

Hai scritto che stavi per cominciare a indagare sul difetto che Ennio ha segnalato —
*«clicco su Nascondi logo, sfarfalla l'immagine della pagina in modo grave»*. **Il codice
dice esattamente cos'è**, ed è una conseguenza diretta delle modifiche della 3.279 e della
3.280.

## Il giro che si chiude su se stesso

**Cinque pezzi, tutti già nel file:**

| | dove |
|---|---|
| 1. lo scroll aggiunge/toglie la classe | `gaming.js:8138` (`applica()`) e `:8152` (`valutaScroll()`) |
| 2. la classe fa sparire nastro e barra **dal flusso** | `gaming.css:2186` e `:2192` — `display: none !important` |
| 3. un osservatore vede cambiare l'altezza | `gaming.js:8077` — `new ResizeObserver( … gsAllineaSpazioMenu() )`, agganciato **anche a `#gs-nastro-fisso`** |
| 4. il riallineamento **sposta il contenuto** | `gaming.js:8053` — `$middle.css( 'padding-top', ( spazio + nastroH ) + 'px' )` |
| 5. lo spostamento produce un nuovo evento di scorrimento | e si torna al punto 1 |

**In parole:**

1. la persona scorre in giù di più di 8 pixel → si aggiunge `gs-logo-hidden`;
2. il nastro e la barra rosa in cima **spariscono dal flusso** (`display:none`, non
   «invisibili»);
3. il `ResizeObserver` se ne accorge **subito** e chiama `gsAllineaSpazioMenu()`;
4. lì `$nastro.is( ':visible' )` adesso è **falso**, quindi `nastroH` diventa **0**, e il
   `padding-top` del contenuto **si accorcia di colpo**: tutta la pagina sale;
5. la pagina che sale cambia la posizione di scorrimento **da sola** — e se il documento si
   è accorciato, il browser la corregge e manda un altro evento;
6. `valutaScroll()` legge quel movimento come **se fosse stata la persona a scorrere
   all'insù**, `delta < -8`, e chiama `applica( false )`: il logo, il nastro e la barra
   tornano;
7. il contenuto riscende, la posizione cambia di nuovo, il `delta` torna positivo →
   `applica( true )` → **e si ricomincia.**

## Perché è peggiorato adesso e non prima

Fino alla 3.278 la classe collassava solo `.logo_wrap` e `.header_mid` (`gaming.css:3861`
e `:3876`) — il nastro **restava dov'era**, quindi `nastroH` non cambiava e il punto 4 non
scattava.

- la **3.279** ha aggiunto `#gs-nastro-fisso` (`gaming.css:2186`);
- la **3.280** ha aggiunto `#page .header_top` (`gaming.css:2192`).

**Sono esattamente i due pezzi che fanno cambiare l'altezza osservata dal `ResizeObserver` e
il valore di `nastroH`.** Il difetto è nato lì: il commento nel CSS dice l'intenzione
— *«display:none, non solo invisibile — così `gsAllineaSpazioMenu()` misura subito lo
spazio libero e il contenuto sale»* — e **far salire il contenuto è precisamente ciò che
innesca il giro.**

## Correzione

Il problema non è che il contenuto si muove: è che **`valutaScroll()` non sa distinguere un
movimento fatto dalla persona da uno fatto dalla pagina stessa.** Va insegnato.

In `gsHeaderToggleInit()`, `gaming.js:8138`:

```js
			// Lo sfarfallio (Ennio, 26/08/2026) nasce da qui: nascondere il
			// nastro e la barra in cima con display:none li toglie dal
			// flusso, il ResizeObserver rimisura, il padding del contenuto
			// si accorcia e la pagina sale DA SOLA. Quel movimento arriva a
			// valutaScroll() indistinguibile da uno scorrimento vero: viene
			// letto come "sta risalendo", il logo ritorna, il contenuto
			// riscende, e il giro si chiude su se stesso.
			// Finché la pagina si sta risistemando, non si valuta niente; a
			// cose ferme si riparte dalla posizione NUOVA, non da quella di
			// prima.
			var inTransizione = false;
			function applica( nascondi ) {
				if ( inTransizione ) { return; }
				inTransizione = true;
				$( 'body' ).toggleClass( 'gs-logo-hidden', nascondi );
				setTimeout( function () {
					gsAllineaSpazioMenu();
					ultimoScrollY = window.pageYOffset || document.documentElement.scrollTop;
					inTransizione = false;
				}, 320 );
			}
```

e in `valutaScroll()` (`gaming.js:8152`), come **prima** riga dopo `tickPianificato = false;`:

```js
				if ( inTransizione ) { return; }
```

**Le due parti servono tutte e due**, e per ragioni diverse: il blocco impedisce di
reagire mentre la pagina si muove da sé, il riallineamento di `ultimoScrollY` impedisce che
appena finito si legga come «salto» la differenza accumulata.

### E una seconda rete, se dopo la correzione sfarfalla ancora vicino alla cima

`sogliaCima` vale **60** (`gaming.js:8149`): sopra i 60 pixel di scorrimento il logo può
nascondersi. Ma nastro + barra rosa insieme **sono più alti di 60 pixel**, quindi
nascondendoli la pagina può risalire sotto la soglia, che rimostra tutto, che riscende, e
così via — la stessa altalena, un piano più sotto.

Se succede, alzare la soglia sopra l'altezza totale di quello che sparisce:

```js
			var sogliaCima = 200;   // più alta della somma di logo + nastro + barra in cima
```

**Non farlo adesso**: prima la correzione sopra, poi si guarda. Cambiare due cose insieme
significa non sapere quale delle due ha funzionato.

### Come si prova

Sul sito vero, scorrendo lentamente in giù con la rotella, **una tacca per volta**, in un
punto della pagina intorno ai 100-150 pixel dalla cima. È lì che si vede: se il logo va e
viene da solo senza che tu tocchi più niente, il giro c'è ancora.

---

---

# Le due correzioni da due righe (punto 4)

## A1 · MEDIO — `remove_action()` con la priorità sbagliata non toglie niente, e poi ne aggiunge una copia

**File:** `includes/helpers.php:722-732` — VERIFICATO

```php
function gs_get_posts_by_author( $args ) {
	$aggancio_attivo = has_action( 'pre_get_posts', 'the_newspaper_post_author_archive' );
	if ( $aggancio_attivo ) {
		remove_action( 'pre_get_posts', 'the_newspaper_post_author_archive' );
	}
	$risultato = get_posts( $args );
	if ( $aggancio_attivo ) {
		add_action( 'pre_get_posts', 'the_newspaper_post_author_archive' );
	}
	return $risultato;
}
```

`has_action()` **non restituisce vero o falso: restituisce la priorità** con cui l'aggancio
è registrato. `remove_action()` e `add_action()`, chiamate senza priorità, usano **10**.

Quindi, se il tema registra la sua funzione con una priorità diversa da 10:

1. `has_action()` restituisce, per esempio, `20` — che è vero, quindi si entra nell'`if`;
2. `remove_action( …, 10 )` **non trova niente e non toglie niente** — WordPress non segnala
   nulla;
3. `get_posts()` gira con l'interferenza ancora attiva: **la correzione non fa niente**;
4. `add_action( …, 10 )` **registra una seconda copia** della funzione del tema, a una
   priorità diversa: da lì in poi, per tutto il resto della richiesta, quella funzione gira
   **due volte** su ogni query.

E c'è un secondo caso, più raro ma dello stesso tipo: se la priorità fosse **0**,
`has_action()` restituirebbe `0`, che in PHP è **falso** — l'`if` non scatterebbe mai e la
correzione sarebbe inerte in silenzio.

**Oggi probabilmente non morde**: quasi tutti i temi usano la priorità predefinita, e la
prova su guru2 è passata, il che dice che lì è 10. **Ma è esattamente il genere di cosa che
smette di funzionare dopo un aggiornamento del tema, senza nessun segnale.**

### Correzione

```php
function gs_get_posts_by_author( $args ) {
	// has_action() restituisce la PRIORITÀ, non vero/falso: va riusata sia
	// per togliere sia per rimettere. Con la priorità predefinita (10) su un
	// aggancio registrato altrove, remove_action() non toglierebbe niente
	// — in silenzio — e add_action() finirebbe per registrarne una seconda
	// copia. E con priorità 0, has_action() restituisce 0, che è falso:
	// il controllo va fatto con !== false, non con la verità del valore.
	$priorita = has_action( 'pre_get_posts', 'the_newspaper_post_author_archive' );
	if ( false === $priorita ) {
		return get_posts( $args );   // aggancio non presente: niente da aggirare
	}

	remove_action( 'pre_get_posts', 'the_newspaper_post_author_archive', $priorita );
	try {
		return get_posts( $args );
	} finally {
		// "finally" garantisce che l'aggancio del tema torni al suo posto
		// anche se la query solleva un errore: lasciarlo tolto per il resto
		// della richiesta romperebbe le pagine archivio del tema.
		add_action( 'pre_get_posts', 'the_newspaper_post_author_archive', $priorita );
	}
}
```

Il `try/finally` è supportato da PHP 5.5 in poi e il plugin richiede 7.4: nessun problema.

## B1 · MEDIO — Il nome della squadra finisce dentro il nome del lucchetto

**File:** `includes/percorsi-lezioni.php:228` — VERIFICATO

```php
$lock = 'gs_perc_sq_' . (int) $pid . '_' . $team;
```

`$team` è il **nome della squadra scritto a mano da Ennio** in una casella di testo
(`admin.php:1084`, salvato con `sanitize_text_field`, **senza limite di lunghezza**). Oggi
sono «Team Nord», «Team Centro», «Team Sud e Isole» — corti, e il lucchetto sta largo.

Ma **MySQL non accetta nomi di lucchetto più lunghi di 64 caratteri**: oltre quella
lunghezza `GET_LOCK()` non restituisce `1`, e allora:

```php
if ( '1' !== $wpdb->get_var( … ) ) {
	return false; // ci sta già pensando un'altra richiesta: va bene così
}
```

**Il badge non viene assegnato a nessuno**, e il commento dice che va bene — mentre non
c'è nessun'altra richiesta: è solo il nome troppo lungo. Basta che Ennio chiami una squadra
«Le Sfogline della Bassa Romagna e dintorni» e i Percorsi di Squadra smettono di premiare,
senza nessun messaggio.

### Correzione — una funzione, non una riga

```php
$lock = 'gs_perc_sq_' . (int) $pid . '_' . md5( (string) $team );
```

Lunghezza fissa (50 caratteri circa), e regge qualunque cosa Ennio scriva: accenti, spazi,
apostrofi. **Il lucchetto non deve essere leggibile, deve essere univoco.**

---

# Una nota sul metodo, visto che l'hai chiesto

Mi hai chiesto se procedere a cercare la causa da solo o aspettare. **Adesso hai la
diagnosi, quindi non serve rifarla da zero — ma non prenderla per buona.**

La cosa utile non è ripetere la ricerca: è **verificare i cinque passaggi del giro** contro
il codice, come hai fatto con le citazioni degli altri due documenti. In particolare questi
tre, che sono quelli su cui la spiegazione sta o cade:

1. il `ResizeObserver` a `gaming.js:8077` osserva **davvero** `#gs-nastro-fisso`, non solo
   `header#header`;
2. `$nastro.is( ':visible' )` a `gaming.js:8051` restituisce **falso** con `display:none`
   (e quindi `nastroH` diventa 0);
3. `$middle.css( 'padding-top', … )` a `gaming.js:8053` **accorcia davvero** lo spazio, cioè
   la pagina sale.

**Se uno di questi tre non regge, la mia spiegazione è sbagliata e va detto** — meglio
scoprirlo prima di scrivere il flag che dopo. Se reggono tutti e tre, il giro è quello e la
correzione si può applicare.
