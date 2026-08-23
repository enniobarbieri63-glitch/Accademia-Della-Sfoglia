# Prima i tre controlli post-installazione, poi il GIRO 2

Giro 0 e Giro 1 sono installati. Prima di aprire il Giro 2, chiudi i tre controlli
rimasti — sono rapidi e servono a sapere se quello che abbiamo messo in produzione sta
davvero facendo il suo mestiere.

---

# PARTE 1 — I tre controlli, sul sito vero

### 1 · La correzione funziona sulla pagina «Le Sfogline» vera?

Il controllo nuovo cerca lo shortcode dentro `post_content`. Su guru2 funziona, ma la
pagina di produzione l'ha composta Ennio a mano e alcuni costruttori salvano il contenuto
altrove. Con Query Monitor su `/le-sfogline/` verifica che **la scansione della tabella
utenti compaia una volta sola, non due**.

**Se ne vedi ancora due: segnalalo e basta, non correggere.** Non è un guasto — quella
pagina resta esattamente com'era prima dell'aggiornamento, perché il CSS continua a
nascondere il nastro piccolo. Vuol dire solo che l'ottimizzazione non si applica lì.

### 2 · Il numero per Ennio

Totale query di una pagina qualsiasi (la home va bene), a freddo e poi ricaricando.
Due numeri, quelli veri della pagina intera.

### 3 · La conta rimasta dal Giro 0

Su `/le-sfogline/`: **quante sfogline vere** compaiono nel nastro grande, e **se lascia
vuoti** mentre scorre. Se il vuoto c'è, la correzione è quella che ti ho già dato
(più ripetizioni delle stesse persone, mai criteri più larghi, mai nomi inventati).

**Riporta questi tre punti e fermati.** Il Giro 2 parte dopo.

---

# PARTE 1-bis — Una riga, trovata rileggendo la 3.271.4

Non c'entra con il Giro 2: è nel lavoro parallelo sul pulsante «Accedi / La Mia Sfoglia»,
spostato sotto la barra del menu. **Difetto piccolo, correzione di una riga, fallo insieme
al resto.**

`assets/js/gaming.js`, nel blocco che inserisce il pulsante:

```js
var $nav = $( '#navigation' ).first();
if ( ! $nav.length || $nav.find( '.gs-nav-accedi' ).length ) { return; }
```

Quel controllo serve a non inserire il pulsante due volte. **Ma cerca dentro `#navigation`,
e il pulsante adesso viene inserito FUORI** (`$ancora.after( $riga )`, dove `$ancora` è un
antenato di `#navigation`). Da quando il pulsante è stato spostato, quel controllo non lo
trova più: **è diventato inefficace.**

Oggi non si vede, perché il blocco gira una volta sola al caricamento della pagina. Ma
diventa concreto se `gaming.js` viene incluso o rieseguito due volte — cosa che i plugin
di ottimizzazione fanno (**SiteGround Optimizer, che è attivo su questo sito**, combina e
rimanda i JavaScript). In quel caso comparirebbero due pulsanti «Accedi».

**Correzione:** cercare in tutta la pagina invece che dentro il menu.

```js
if ( ! $nav.length || $( '.gs-nav-accedi' ).length ) { return; }
```

Verifica: apri una pagina e controlla che di pulsante «Accedi» ce ne sia **uno solo**, sia
da collegato che da scollegato.

---

# PARTE 2 — GIRO 2: la chiusura del mese

**Questo è il giro più delicato fatto finora: tocca soldi veri.** Un errore qui assegna
sconti doppi o li cancella. Leggi tutto prima di scrivere una riga.

C'è una scadenza: il difetto scatta **il 1° settembre**. Non è urgente stanotte, ma non
va oltre.

Tutte le modifiche sono in `includes/buono-sfoglia.php`. **Rileggi il file prima di
toccarlo** — è stato aggiornato più volte, non fidarti dei numeri di riga qui sotto.

---

## 2a · Il difetto principale: la protezione è scritta alla fine

`gs_buono_sfoglia_chiudi_mese()` cicla su tutti gli utenti e, per ognuno, manda **due**
messaggi e — se ha vinto — aggiunge **+2,5%** di sconto. La protezione contro la doppia
esecuzione, `update_option( 'gs_buono_sfoglia_mese_chiuso', $ym )`, è scritta **solo dopo
il ciclo**.

Con 200 sfogline sono 400 invii in una sola richiesta PHP. Se scatta il tempo massimo di
esecuzione o il limite orario di posta dell'hosting, il ciclo muore a metà e l'opzione non
viene mai scritta. Alla ripresa **si riparte da capo**: chi era già stato elaborato riceve
di nuovo le email e — questo è il punto grave — **si prende un altro 2,5%**, perché la
percentuale non ha nessun controllo di doppione. Il badge sì, la percentuale no.

### La correzione

Un marcatore **per singola sfoglina**, scritto **prima** degli effetti, non dopo. Dentro il
`foreach`, subito dopo il controllo `gs_e_sfoglina_vera()`:

```php
	foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
		$uid = (int) $uid;
		if ( ! gs_e_sfoglina_vera( $uid ) ) {
			continue;
		}

		// Marcatore per utente, scritto PRIMA di mandare messaggi e assegnare
		// la percentuale: se il ciclo muore a metà, la ripresa riparte da dove
		// si era fermata invece che da capo. Prima di questa riga, una ripresa
		// rimandava le email già mandate e sommava un secondo 2,5%.
		$chiave_fatto = 'gs_buono_mese_' . $ym;
		if ( get_user_meta( $uid, $chiave_fatto, true ) ) {
			continue; // già elaborata per questo mese
		}
		update_user_meta( $uid, $chiave_fatto, 1 );

		$punti_mese = gs_get_month_points( $uid, $ym );
		// ... da qui in poi tutto invariato ...
```

**Non serve elaborare a scaglioni.** Il documento principale lo proponeva, ma con la
correzione 2b qui sotto diventa inutile: il cron gira **ogni giorno**, i marcatori rendono
la ripresa automatica, e in due o tre giorni il giro si completa da solo. Una funzionalità
in meno da scrivere e da rompere.

---

## 2b · Il mese può non chiudersi mai

```php
function gs_buono_sfoglia_controlla_chiusura_mese() {
	if ( '01' !== date( 'd', current_time( 'timestamp' ) ) ) {
		return;
	}
	gs_buono_sfoglia_chiudi_mese();
}
```

Il cron di WordPress **non è un vero cron**: gira solo quando qualcuno visita il sito. Se
il 1° del mese il traffico è scarso e il cron non parte in quella finestra, la chiusura
**non avviene e non avverrà mai più** per quel mese: nessun Buono, nessun resoconto, e
nessuno se ne accorge.

### La correzione

Togliere il controllo sulla data e usare quello sullo stato, **che c'è già**:

```php
function gs_buono_sfoglia_controlla_chiusura_mese() {
	// Non più "solo il giorno 1": il cron di WordPress dipende dal traffico e
	// può saltare quella finestra, e allora il mese non si chiuderebbe mai.
	// Ogni giorno si chiede: c'è un mese finito e non ancora chiuso?
	$mese_scorso = date( 'Y-m', strtotime( 'first day of last month', current_time( 'timestamp' ) ) );
	if ( get_option( 'gs_buono_sfoglia_mese_chiuso' ) === $mese_scorso ) {
		return;
	}
	gs_buono_sfoglia_chiudi_mese( $mese_scorso );
}
```

### Il dettaglio collegato, e non è facoltativo

Dentro `gs_buono_sfoglia_chiudi_mese()` c'è ancora:

```php
	$ym = date( 'Y-m', strtotime( '-1 month', current_time( 'timestamp' ) ) );
```

**Con il controllo sulla data rimosso, questa riga diventa pericolosa**: `-1 month` il 31
marzo dà il 3 marzo, quindi il mese calcolato sarebbe quello sbagliato. Finché girava solo
il giorno 1 il problema non si vedeva. Sostituiscila:

```php
	$ym = date( 'Y-m', strtotime( 'first day of last month', current_time( 'timestamp' ) ) );
```

---

## 2c · La scadenza annuale ha una sola giornata utile — e attenzione a una trappola

```php
	if ( date( 'm-d', $oggi ) !== '02-01' ) {
		return;
	}
```

Un solo giorno all'anno. Se il cron non parte quel giorno, i Buoni non scadono per tutto
l'anno successivo e si sommano ai nuovi.

### ⚠ Prima di correggere, leggi questo

La correzione ovvia — passare da `!== '02-01'` a `< '02-01'` — **cancellerebbe subito tutti
i Buoni Sfoglia esistenti**, oggi stesso.

Perché: l'opzione `gs_buono_sfoglia_scaduto_anno` **non è mai stata scritta** (il Buono
Sfoglia è nato il 19-20 agosto 2026, dopo il 1° febbraio). Siamo ad agosto, quindi
`date('m-d') >= '02-01'` è vero e il marcatore è vuoto: al primo passaggio del cron la
funzione azzererebbe la percentuale di **tutte** le sfogline.

Oggi il danno sarebbe piccolo — nessun Buono è ancora stato assegnato in automatico,
perché il primo 1° del mese utile è il 1° settembre — ma se Ennio ne ha assegnato qualcuno
**a mano dal pannello**, quello sparirebbe senza traccia. **Non correre il rischio.**

### La correzione, con la protezione

```php
function gs_buono_sfoglia_controlla_scadenza() {
	$oggi = current_time( 'timestamp' );
	$anno = date( 'Y', $oggi );

	// Primo passaggio dopo questo aggiornamento: il marcatore non è mai stato
	// scritto (il Buono Sfoglia è nato ad agosto 2026, dopo il 1° febbraio).
	// Senza questa protezione, il controllo qui sotto farebbe scadere SUBITO
	// tutti i Buoni esistenti, a metà anno. Segna l'anno in corso come già
	// gestito: la prima scadenza vera sarà il 1° febbraio prossimo.
	if ( '' === (string) get_option( 'gs_buono_sfoglia_scaduto_anno', '' ) && date( 'm-d', $oggi ) >= '02-01' ) {
		update_option( 'gs_buono_sfoglia_scaduto_anno', $anno );
		return;
	}

	// Non più "solo il 1° febbraio": da lì in poi, finché non è stato fatto
	// per quest'anno. Così una giornata di cron saltata non salta la scadenza.
	if ( date( 'm-d', $oggi ) < '02-01' ) {
		return;
	}
	if ( get_option( 'gs_buono_sfoglia_scaduto_anno' ) === $anno ) {
		return; // già fatto quest'anno
	}

	// ... il ciclo che azzera, invariato ...
}
```

**Se hai dubbi sul fatto che qualche Buono sia già stato assegnato a mano, fermati e
chiedi a Ennio prima di installare 2c.** Le altre due correzioni si possono installare
comunque: sono indipendenti.

---

## Le prove da fare su Local — e questa volta contano

La prova n. 2 è quella che dà senso a tutto il giro. **Senza, non consegnare.**

1. **`php -l includes/buono-sfoglia.php`**.

2. **La prova della ripresa a metà.** Su Local, con una ventina di utenti finti che abbiano
   punti nel mese di prova:
   - annota la percentuale di partenza di due o tre sfogline sopra la soglia;
   - lancia `gs_buono_sfoglia_chiudi_mese( '2026-07' )` e **interrompila a metà**
     (un `die()` temporaneo dentro il ciclo dopo N utenti, poi rimuovilo);
   - rilancia la stessa chiamata;
   - **verifica che nessuna sfoglina abbia preso il 2,5% due volte** e che chi era già
     stato elaborato non riceva un secondo messaggio.

   Questa è la prova che dice se il giro è riuscito. Riporta i numeri prima/dopo.

3. **Il mese giusto.** Verifica che `gs_buono_sfoglia_controlla_chiusura_mese()` calcoli
   il mese precedente corretto **anche se eseguita il 31 di un mese** (è il caso che
   `-1 month` sbagliava). Forza la data di sistema o chiama la riga isolata con un
   timestamp del 31 marzo e controlla che dia `2026-02`, non `2026-03`.

4. **La scadenza non scatta oggi.** Dopo aver applicato 2c, esegui
   `gs_buono_sfoglia_controlla_scadenza()` su Local con qualche sfoglina che ha una
   percentuale accumulata: **la percentuale non deve azzerarsi**, e l'opzione
   `gs_buono_sfoglia_scaduto_anno` deve risultare valorizzata con l'anno in corso.

5. **Idempotenza a giro completo.** Lancia `gs_buono_sfoglia_chiudi_mese( '2026-07' )` due
   volte di fila, senza interruzioni: la seconda deve restituire `0` e non fare niente.

---

## Una nota che non richiede codice

I marcatori `gs_buono_mese_AAAA-MM` sono una riga di usermeta per sfoglina per mese, che
nessuno cancella. Con 200 sfogline sono circa 2.400 righe l'anno: accettabile, ma va messo
a calendario un ripulisci annuale (è la voce G3 del documento). **Non farlo adesso** — è
esattamente il tipo di ciclo su tutti gli utenti che stiamo imparando a scrivere con
prudenza, e va progettato con lo stesso schema a marcatori, non improvvisato in coda a
questo giro.

---

## Poi fermati

Riporta l'esito delle cinque prove con i numeri veri, e **non installare** finché non
arriva il via libera. Se qualcosa non torna nella prova 2, **fermati e chiedi**: su questa
funzione è meglio una consegna in ritardo che una sbagliata.
