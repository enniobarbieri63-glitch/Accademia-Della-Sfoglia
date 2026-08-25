# Seconda lettura · Blocco 4 — `percorsi-lezioni.php`, `lezioni-video.php`, `area-pro.php`

I Percorsi Guidati, la Libreria Video e l'Area Professionale: **è qui che i punti si
assegnano da soli**, senza che nessuno prema «assegna». Badge, diplomi, certificati.

Sono le 2.936 righe che stanno fra una sfoglina e i 2500 punti del Buono Sfoglia.

**Cinque voci, L1-L5.** Le prime due sono alte, e **L1 è il difetto più grosso trovato
finora in tutto il lavoro** — non per come è scritto il PHP, ma per come viene chiamato.

Una cosa buona subito, perché è un risultato anche quella: **`area-pro.php` è pulito sul
fronte dei soldi e dei punti.** 778 righe, nessuna chiamata a `gs_add_points()`, nessun
badge, nessun contatore accumulato. Compiti, feedback, diplomi: stato, non grandezze.
L'unica voce che ne esce è L5, ed è piccola.

---

## L1 · ALTO — Segnare una lezione come vista non è protetto da niente, e da lì parte tutta la catena dei punti

**File:** `includes/lezioni-video.php:867-891` (`gs_ajax_lezione_segna_vista()`)
**JavaScript:** `assets/js/gaming.js:4040-4046`
**Stato:** VERIFICATO

### Il codice

```php
	$viste = get_user_meta( $uid, 'gs_lezioni_viste', true );
	if ( ! is_array( $viste ) ) { $viste = array(); }
	if ( ! in_array( $lid, $viste, true ) ) {          // ← leggi e controlla
		$viste[] = $lid;
		update_user_meta( $uid, 'gs_lezioni_viste', $viste );   // ← scrivi
		gs_add_points( $uid, gs_get_points_value( 'lezione_vista', 5 ), '…' );
		do_action( 'gs_lezione_vista', $uid );          // ← e tutto il resto
	}
```

**È il leggi-controlla-scrivi, ancora una volta.** Ma stavolta non su una grandezza sola:
quella `do_action` è l'imbocco di una catena.

### Cosa c'è in fondo alla catena

`gs_lezione_vista` è agganciata a `gs_percorsi_controlla_completamento()`
(`percorsi-lezioni.php:352`), che per **ogni** percorso pubblicato chiama l'assegnazione del
badge, e alla fine il Diploma Finale. Un solo passaggio può quindi valere:

| | punti |
|---|---|
| la lezione vista | **5** |
| il percorso completato (`percorsi-lezioni.php:333`) | **30** |
| tutti i percorsi completati — Diploma Finale (`:407`) | **100** |
| **totale, se quella era l'ultima lezione dell'ultimo percorso** | **135** |

**Eseguito due volte: 270.** Più due email, due aeroplanini, e due voci nello storico che
raccontano la stessa cosa.

### E il modo in cui viene chiamato è peggio del doppio clic

```js
	document.addEventListener( 'toggle', function ( e ) {
		var det = e.target;
		if ( ! det || 'DETAILS' !== det.tagName || ! det.classList.contains( 'gs-lezione-apertura' ) || ! det.open ) { return; }
		var lezione = det.getAttribute( 'data-lezione' );
		if ( ! lezione ) { return; }
		$.post( GS_AJAX.url, { action: 'gs_lezione_segna_vista', nonce: GS_AJAX.nonce, lezione: lezione } );
	}, true );
```

**Non c'è nessun pulsante.** La lezione si segna come vista **quando la sfoglina apre il
lettore**, cioè su un evento `toggle`. Quindi:

- **niente da disabilitare** — non è un `click` su un pulsante, è l'apertura di un
  riquadro. Tutte le altre protezioni scritte finora (`$btn.prop('disabled', true)`) qui non
  hanno dove attaccarsi;
- **nessun controllo nel JavaScript**, di nessun tipo: né un identificativo, né una
  memoria di cosa è già stato mandato;
- **`toggle` scatta a ogni apertura.** Apri, chiudi per guardare l'elenco, riapri: due
  richieste. Non è un errore della sfoglina, è come si usa una pagina.

Le riaperture lente sono innocue — al secondo giro `gs_lezioni_viste` contiene già la
lezione e il controllo regge. **Il problema sono le due richieste che si sovrappongono.**

### Perché si sovrappongono davvero, e non solo in teoria

**Sul sito vero c'è un motivo preciso**, e l'abbiamo già incontrato: nella 3.271.4 avevo
trovato che **SiteGround Optimizer, combinando i JavaScript, può far eseguire due volte lo
stesso blocco** — è il difetto per cui abbiamo aggiunto la protezione al pulsante «Accedi».

Se succede qui, `document.addEventListener( 'toggle', … )` viene registrato **due volte**.
Allora non è più una corsa che capita ogni tanto: **ogni singola apertura di ogni lezione
manda due richieste simultanee**, e la catena dei punti si sdoppia sistematicamente.

Non ci sono utenti dentro adesso, quindi non sta succedendo niente. Ma è esattamente il
genere di cosa che si scopre in classifica tre settimane dopo il lancio.

### Correzione — tre pezzi, e servono tutti e tre

**a) Un contrassegno per lezione, scritto prima di qualunque effetto.** Il controllo
`in_array` su un array non è atomico; una chiave dedicata sì, perché `add_user_meta()` con
un valore unico si può verificare subito:

```php
	// Serratura per lezione, indipendente dall'array: gs_lezioni_viste è un
	// leggi-controlla-scrivi, e due richieste simultanee (il lettore si
	// apre con un evento "toggle", che non ha un pulsante da disabilitare e
	// scatta a ogni apertura) passano entrambe il controllo prima che una
	// qualsiasi abbia scritto. Da qui parte gs_lezione_vista, e con essa
	// badge di percorso (30 punti) e Diploma Finale (100): eseguirla due
	// volte vale 270 punti invece di 135. Il contrassegno si scrive PRIMA
	// di tutto, come il marcatore della chiusura del mese.
	$chiave_vista = 'gs_lezione_vista_' . (int) $lid;
	if ( get_user_meta( $uid, $chiave_vista, true ) ) {
		wp_send_json_success();   // già vista: niente di nuovo, e nessun errore da mostrare
	}
	update_user_meta( $uid, $chiave_vista, current_time( 'mysql' ) );
```

subito prima del blocco esistente, che resta com'è (l'array `gs_lezioni_viste` continua a
servire per gli elenchi e i conteggi).

**Compromesso da dichiarare:** se il caricamento si interrompe fra il contrassegno e
`gs_add_points()`, la sfoglina perde i 5 punti di quella lezione e non li recupera. È lo
stesso compromesso già accettato per il cooldown delle domande, e in questo verso — meglio
un punto perso che duecentosettanta regalati.

**Nota sul numero di righe nel database:** una chiave per lezione vista significa una riga
di `usermeta` in più per ogni lezione guardata da ogni sfoglina. Con 100 lezioni e 200
sfogline sono al massimo 20.000 righe: **è niente** per `usermeta`, che è indicizzata
proprio su `user_id` + `meta_key`. Non è una ragione per non farlo.

**b) Una protezione anche nel JavaScript**, perché il PHP non deve essere l'unica:

```js
	// Una lezione si segna una volta sola per caricamento di pagina: "toggle"
	// scatta a ogni apertura, e se il file venisse eseguito due volte (succede
	// con i JavaScript combinati da SiteGround Optimizer — è il difetto già
	// corretto sul pulsante "Accedi") ogni apertura manderebbe due richieste
	// simultanee. Il contrassegno vive sull'elemento, quindi vale anche con
	// due ascoltatori registrati.
	if ( det.getAttribute( 'data-gs-vista-inviata' ) ) { return; }
	det.setAttribute( 'data-gs-vista-inviata', '1' );
```

subito dopo il controllo su `data-lezione`.

**c) Verificare se il file viene davvero eseguito due volte sul sito vero.** È la domanda
sotto a questa voce, e vale anche per tutto il resto del plugin. **Con il gaming spento si
può guardare adesso:** apri una pagina del gaming sul sito vero, apri gli strumenti per
sviluppatori del browser, scheda «Rete», e apri una lezione. **Se parte una sola richiesta a
`admin-ajax.php`, il file gira una volta. Se ne partono due, gira due volte** — e allora
non è solo questa voce, è una cosa che riguarda ogni pulsante del plugin.

**Riporta l'esito**: cambia il peso di parecchie cose.

---

## L2 · ALTO — I badge dei percorsi si guardano dal doppio, ma i punti no

**File:** `includes/percorsi-lezioni.php:316-348` (percorso individuale),
`:151-181` (Percorso di Squadra), `:391-420` (Diploma Finale) — VERIFICATO

### Il codice, uguale in tutte e tre

```php
	$badge_key = 'percorso_' . $pid;
	$owned     = gs_get_user_badges( $uid );
	if ( in_array( $badge_key, $owned, true ) ) { return false; }   // ← il controllo

	$owned[] = $badge_key;
	update_user_meta( $uid, 'gs_badges', $owned );
	...
	gs_add_points( $uid, gs_get_points_value( 'percorso_completato', 30 ), '…' );
```

Il controllo `in_array` sui badge è quello che nella prima analisi avevo indicato come **uno
dei tre punti dove il plugin fa la cosa giusta**. E lo è, per il badge: due esecuzioni
simultanee scrivono tutt'e due lo stesso array, l'ultima vince, e il badge resta **uno**.

**Ma i punti non stanno nell'array.** `gs_add_points()` viene chiamata una volta per
esecuzione, e — giustamente — somma in modo atomico. **Due esecuzioni, sessanta punti, un
badge solo.** E lo storico mostra due righe da +30 con la stessa motivazione, mentre i badge
ne mostrano uno: la contabilità si contraddice da sola.

### Perché non basta correggere L1

Per il percorso individuale e per il Diploma Finale, sistemare L1 chiude anche questi: sono
raggiungibili **solo** attraverso `gs_lezione_vista`.

**Il Percorso di Squadra no**, ed è la parte che conta:

```php
function gs_percorso_squadra_assegna_se_completo( $pid, $uid ) {
	$team = gs_get_user_team( $uid );
	if ( ! $team || ! gs_percorso_squadra_completato( $pid, $team ) ) { return false; }
	foreach ( gs_squadra_membri( $team ) as $mid ) {
		if ( gs_percorso_squadra_badge_assegna( $pid, $mid ) ) { … }   // ← a TUTTI i membri
	}
```

Il contrassegno di L1 è **per sfoglina**: protegge chi ha aperto la lezione. Ma qui
l'assegnazione è **per tutta la squadra**, e chi la fa scattare può essere una qualsiasi
delle socie.

**Due membri diverse che finiscono l'ultima lezione nello stesso momento** — perfettamente
normale in una squadra che sta completando un percorso insieme — fanno partire **due giri
completi sull'intero elenco dei membri**. Nessun contrassegno per sfoglina lo impedisce:
sono due persone diverse che agiscono.

**Con una squadra da sei: 30 punti × 6 membri, due volte. 360 punti invece di 180.**

### Correzione

Scrivere il badge **prima** degli effetti, con una chiave dedicata invece che dentro
l'array — la stessa forma della correzione della chiusura del mese:

```php
function gs_percorso_squadra_badge_assegna( $pid, $uid ) {
	$badge_key = 'percorso_' . $pid;
	if ( in_array( $badge_key, gs_get_user_badges( $uid ), true ) ) { return false; }

	// Contrassegno dedicato, scritto PRIMA di punti ed email: l'array
	// gs_badges è un leggi-controlla-scrivi e non regge due esecuzioni
	// simultanee — il badge resta uno (l'ultima scrittura vince) ma i punti
	// si sommano due volte. Per i Percorsi di Squadra questo succede senza
	// bisogno di un doppio clic: bastano due socie che finiscono l'ultima
	// lezione insieme, e ognuna fa partire un giro su tutti i membri.
	$chiave_fatto = 'gs_badge_dato_' . $badge_key;
	if ( get_user_meta( $uid, $chiave_fatto, true ) ) { return false; }
	update_user_meta( $uid, $chiave_fatto, current_time( 'mysql' ) );

	$percorso = gs_percorso_get( $pid );
	$owned    = gs_get_user_badges( $uid );
	$owned[]  = $badge_key;
	update_user_meta( $uid, 'gs_badges', $owned );
	…
```

**Le stesse sei righe vanno in tutte e tre le funzioni** (`:151`, `:316`, `:391`), con
`$badge_key` che è già diverso in ognuna. Scriverle una volta in una funzione condivisa
sarebbe più pulito, ma sono tre punti e la ripetizione qui si legge meglio di
un'astrazione — **basta che siano identiche.**

**Attenzione al passaggio:** le sfogline che hanno **già** un badge di percorso non hanno il
contrassegno nuovo. Con il codice sopra il primo `in_array` le ferma prima, quindi non
succede niente — **ma l'ordine dei due controlli conta**, e va lasciato così com'è scritto:
prima `in_array`, poi il contrassegno.

---

## L3 · MEDIO — Lo storico dei punti può perdere righe proprio quando servirebbe

**File:** `includes/points.php:124-137` (`gs_log_points()`) — VERIFICATO

```php
function gs_log_points( $user_id, $points, $reason, $total ) {
	$log = get_user_meta( $user_id, 'gs_points_log', true );
	…
	array_unshift( $log, array( … ) );
	$log = array_slice( $log, 0, 100 );
	update_user_meta( $user_id, 'gs_points_log', $log );
}
```

`gs_add_points()` è costruita bene: gli incrementi sono atomici, e il commento del 14/08
spiega perché. **Ma il suo storico no**: è un leggi-modifica-scrivi come tutti gli altri.

Due assegnazioni quasi simultanee sulla stessa sfoglina leggono lo stesso elenco, ognuna ci
mette in cima la propria riga, la seconda scrittura vince: **una delle due righe sparisce,
mentre i punti sono stati sommati tutti e due.** Il totale è giusto e lo storico no.

**Perché conta più di quanto sembri.** Su questo lavoro abbiamo costruito **due reti di
sicurezza che leggono uno storico**: quella dei pagamenti del calendario (C1) e quella
dell'accredito token (T1) — «se l'ultima voce è identica a questa, è un doppio clic». Se lo
storico può perdere proprio la voce scritta un istante prima, **la rete ha un buco esattamente
nel caso che deve coprire.**

`gs_pagamenti_log` e `gs_token_log` hanno la stessa forma. Qui non è ancora un difetto
osservato — è la stessa causa di cui abbiamo già visto gli effetti altrove.

### Correzione

**Non riscrivere gli storici adesso.** La correzione seria (una tabella, o l'append via SQL)
è sproporzionata al danno, e siamo in un momento in cui conviene chiudere le cose grandi.

Quello che va fatto è **due righe di commento sopra ciascuna delle tre funzioni**, che
dicano cosa lo storico garantisce e cosa no:

```php
/**
 * Storico leggibile delle assegnazioni. ATTENZIONE: è un leggi-modifica-scrivi
 * su un solo meta, quindi due assegnazioni simultanee sulla stessa sfoglina
 * possono far perdere una delle due righe — i PUNTI non si perdono (l'aumento
 * è atomico, vedi gs_add_points), lo storico sì. Non usare questo elenco come
 * unica prova di cosa è successo, e tenerne conto nelle reti di sicurezza che
 * lo leggono (calendario, token).
 */
```

**Priorità media, come lavoro; alta come cosa da sapere.** Un limite scritto è un limite;
un limite non scritto è una trappola per chi ci lavorerà dopo.

**E c'è un secondo aspetto, già visto:** `array_slice( $log, 0, 100 )` tiene le ultime 100
voci. È **la stessa forma** dello `array_slice( …, -100 )` che in `messaggi.php:241`
rinumera le risposte di una conversazione (voce **E7**). Qui non fa danni — nessuno indirizza
le voci per posizione — ma quando si tocca E7 vale la pena guardare tutti gli
`array_slice` del plugin insieme.

---

## L4 · MEDIO — I percorsi stagionali aprono e chiudono un paio d'ore prima o dopo

**File:** `includes/percorsi-lezioni.php:78-85` (`gs_percorso_in_stagione()`) — VERIFICATO

```php
	$adesso = current_time( 'timestamp' );
	if ( $c['data_inizio'] && $adesso < strtotime( $c['data_inizio'] . ' 00:00:00' ) ) { return false; }
	if ( $c['data_fine'] && $adesso > strtotime( $c['data_fine'] . ' 23:59:59' ) ) { return false; }
```

**È lo stesso errore di P3**, trovato ieri sugli avvisi di scadenza: `current_time('timestamp')`
è UTC **più lo scarto di WordPress** (Roma: +2 d'estate, +1 d'inverno), `strtotime()` lavora
nel fuso del **server** (di norma UTC). **Non sono lo stesso metro**, e la differenza è di
una o due ore.

In pratica: un «Percorso di Natale» impostato dal 1° al 26 dicembre **si apre alle 23:00 del
30 novembre** e **si chiude alle 23:00 del 26**. Nessuno se ne accorge quasi mai — ma se
Ennio annuncia un percorso a tempo e qualcuno lo trova aperto la sera prima, la spiegazione
è questa.

### Correzione

La stessa già scritta per P3: **confrontare due date, non un timestamp e una mezzanotte.**

```php
	// Confronto fra date nello stesso fuso: current_time('timestamp') è UTC
	// più lo scarto di WordPress, strtotime() lavora nel fuso del server, e
	// mescolarli sposta l'apertura di una o due ore (stesso errore di P3
	// sugli avvisi di scadenza, 25/08/2026).
	$oggi = current_time( 'Y-m-d' );
	if ( $c['data_inizio'] && $oggi < $c['data_inizio'] ) { return false; }
	if ( $c['data_fine']   && $oggi > $c['data_fine'] )   { return false; }
```

Le date sono in formato `AAAA-MM-GG`, quindi si ordinano da sole come stringhe — è già il
metodo usato da `gs_art_attivo()`.

**Da guardare insieme:** ovunque nel plugin ci sia `current_time('timestamp')` messo a
confronto con uno `strtotime()` di una data, l'errore è lo stesso. Vale la pena cercarli
tutti in una passata sola invece che uno per volta.

---

## L5 · BASSO — Il diploma dell'Area Professionale è un interruttore senza freno

**File:** `includes/area-pro.php:658-667` (`gs_pro_diploma_toggle()`),
`assets/js/gaming.js:1717` — VERIFICATO

```php
function gs_pro_diploma_toggle( $corso_id ) {
	$nuovo = '1' !== get_post_meta( $corso_id, 'gs_diploma_rina', true );
	update_post_meta( $corso_id, 'gs_diploma_rina', $nuovo ? '1' : '' );
```

È un **interruttore**: ogni chiamata inverte lo stato. Il pulsante nel JavaScript non si
disabilita, e la pagina si ricarica **700 millisecondi dopo**.

**Un doppio clic assegna il diploma e poi lo revoca**, mentre il messaggio a schermo dice
*«Diploma assegnato.»* — perché è la risposta della prima richiesta ad arrivare per prima.
La pagina si ricarica e il diploma non c'è. Nessun errore, nessun avviso: solo un diploma
che qualcuno crede di aver dato.

Non ci sono punti in gioco (`gs_diploma_assegnato` fa partire solo un aeroplanino,
`volo-notifiche.php:1387`), quindi la gravità è bassa. **Ma è l'unico caso in cui il secondo
clic disfa il primo invece di ripeterlo**, ed è quello che lo rende confuso.

### Correzione

Due righe nel JavaScript, la forma già usata ovunque nel file:

```js
		var $btn = $( this );
		if ( $btn.prop( 'disabled' ) ) { return; }
		$btn.prop( 'disabled', true );
```

**Non trasformarlo in due pulsanti separati** («Assegna» / «Revoca»): l'interruttore va
bene, ed è coerente con il resto del pannello. Manca solo il freno.

---

## Riepilogo del blocco

| # | Cosa | File:riga | Gravità |
|---|---|---|---|
| **L1** | Segnare una lezione vista non è protetto, e da lì partono 135 punti | `lezioni-video.php:867` · `gaming.js:4040` | **Alto** |
| **L2** | I badge si guardano dal doppio, i punti no — e la squadra non è coperta da L1 | `percorsi-lezioni.php:151, 316, 391` | **Alto** |
| L3 | Lo storico dei punti può perdere la riga che le reti di sicurezza cercano | `points.php:124` | Medio |
| L4 | I percorsi stagionali aprono e chiudono a ore sbagliate | `percorsi-lezioni.php:78` | Medio |
| L5 | Il diploma è un interruttore senza freno: il secondo clic lo revoca | `area-pro.php:658` · `gaming.js:1717` | Basso |

**L1 e L2 vanno insieme**, e in quest'ordine: L1 chiude l'imbocco, L2 chiude il caso della
squadra che L1 non può vedere. **Fare solo L1 lascia scoperti i Percorsi di Squadra**, che
sono anche quelli che moltiplicano di più.

**Prima di L1, la prova del doppio caricamento** (punto c della correzione): due minuti con
la scheda «Rete» del browser, e adesso si può fare perché non c'è nessuno dentro.

---

## Lo schema, alla settima e all'ottava volta — e stavolta dice qualcosa di nuovo

C1, T1, T2, A2, B5, P1, e adesso **L1 e L2**.

Ma L1 non è come gli altri, e vale la pena dirlo con precisione. In tutti i casi precedenti
la protezione mancante era **un pulsante da disabilitare e un identificativo da mandare**:
si sapeva dove metterla, mancava solo qualcuno che ci pensasse.

**Qui non c'è nessun pulsante.** La lezione si segna aprendo un riquadro, e nessuna delle
protezioni che abbiamo scritto finora avrebbe potuto attaccarsi lì. Ecco perché è passata
inosservata attraverso il briefing, i controlli meccanici e la prima lettura: **non
assomiglia a un'operazione, assomiglia a una pagina che si apre.**

È la ragione per cui la seconda lettura andava fatta.

E c'è un secondo motivo per cui questo blocco pesa: **F1 del primo documento — nessun tetto
ai punti giornalieri — smette di essere una precauzione.** Con la catena da 135 punti
raggiungibile aprendo un riquadro, e con il Tavolo di Lavoro che regala +5 a ripetizione se
la diagnosi sul tema conferma il sospetto, un tetto giornaliero non è più «una cosa
prudente»: è la rete che raccoglie tutto quello che ci sfugge. **Va spostato in alto nella
lista dei Giri.**

---

## Cosa leggo adesso

`sfogline-extra.php` (1.140 righe) e il resto di `lezioni-video.php`, che ho letto solo
attorno a L1. Poi `voting.php` e `teams.php` — le squadre sono appena diventate
interessanti.
