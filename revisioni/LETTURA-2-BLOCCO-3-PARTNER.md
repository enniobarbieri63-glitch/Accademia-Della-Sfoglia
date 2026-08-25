# Seconda lettura · Blocco 3 — `artigiani.php` e `scuole-cucina.php`

Gli abbonamenti dei partner: pastifici, botteghe e artigiani che pagano **150 €/anno con
bonifico** (`token.php:120`) per tenere online una vetrina. È l'altra entrata a pagamento
del sito, insieme ai token e agli acconti dei corsi.

**Prima cosa da sapere: i due file sono gemelli identici.** Ho confrontato riga per riga
dopo aver normalizzato i prefissi (`gs_scu` → `gs_art`): le uniche differenze sono i nomi,
le classi CSS, un'emoji (🍝 contro 🎓) e le etichette. **Ogni voce di questo documento va
applicata due volte**, una per file, con lo stesso codice. Non ne correggere uno solo: è
esattamente così che due file gemelli smettono di essere gemelli.

**Sette voci.** Le prime due sono alte, e una delle due **è già successa davvero** — c'è
un commento nel codice che la data all'8 agosto 2026.

Numerazione **P1-P7**.

---

## P1 · ALTO — Registrare un bonifico: nessuna protezione dal doppio clic, nessun controllo sull'importo, e la scadenza viene sovrascritta invece che estesa

**File:** `includes/artigiani.php:753-771` (`gs_ajax_art_pagamento()`)
e `includes/scuole-cucina.php:754-772` (`gs_ajax_scu_pagamento()`) — VERIFICATO
**JavaScript:** `assets/js/gaming.js:4468` e `4601`

### Cosa succede oggi

```php
$importo  = (float) str_replace( ',', '.', $_POST['importo'] ?? 0 );
$scadenza = isset( $_POST['scadenza'] ) ? sanitize_text_field( wp_unslash( $_POST['scadenza'] ) ) : '';
...
if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $scadenza ) ) { wp_send_json_error( ... ); }

$log[] = array( 'data' => current_time( 'Y-m-d' ), 'importo' => $importo, 'scadenza' => $scadenza, 'note' => $note );
update_post_meta( $id, 'gs_art_pagamenti', $log );
update_post_meta( $id, 'gs_art_scadenza', $scadenza );
```

**Una cosa è fatta bene e va detta:** lo storico dei bonifici esiste, è dichiarato in
testa al file come *«mai sovrascritto, solo aggiunto»* (riga 21), ed è quello che al
calendario mancava del tutto prima di C1. Il registro c'è.

Ma intorno a quel registro mancano tre controlli, e sono tre problemi diversi.

### Primo — il doppio clic

Il pulsante non si disabilita e non manda nessun identificativo:

```js
$( document ).on( 'click', '.gs-art-pagamento-invia', function ( e ) {
	e.preventDefault();
	...
	$.post( GS_AJAX.url, { action: 'gs_art_pagamento', ... } )
```

Confronta con quello che abbiamo appena messo sul calendario (`gaming.js:2827`,
`pay-rif`) e sull'accredito dei token (`gaming.js:2189`, `$btn.prop('disabled', true)`).
**Qui non c'è né l'uno né l'altro.**

Due clic scrivono **due righe di bonifico** nello storico. La scadenza non ne soffre — è
la stessa data scritta due volte — ma **lo storico dei pagamenti sì**: dice che sono
arrivati 300 € quando ne sono arrivati 150. E quel registro è l'unico posto dove
l'Accademia sa quanto ha incassato da quel partner.

**È lo stesso difetto di C1 e T1**, sul terzo canale a pagamento del sito. Il registro
qui esisteva già; quello che manca è la protezione davanti.

### Secondo — l'importo non è controllato

`$scadenza` viene validata con una regex. `$importo` no, mai. Passano:

- **0** — registra un bonifico da zero euro;
- **un numero negativo** — registra un bonifico da −150 €, che nella tabella compare come
  `€ -150,00`;
- **testo qualsiasi** — `(float) 'centocinquanta'` fa `0`.

Nessuno di questi è pericoloso di per sé, ma tutti e tre finiscono nel registro dei soldi
senza che niente lo segnali.

### Terzo, e il più insidioso — la scadenza si sostituisce, non si allunga

```php
update_post_meta( $id, 'gs_art_scadenza', $scadenza );
```

E il modulo, alla riga 638, si presenta **già compilato con la scadenza attuale**:

```php
echo '<p><label>Abbonamento attivo fino al<br><input type="date" name="scadenza" value="' . esc_attr( $a['scadenza'] ) . '"></label></p>';
```

Quindi il gesto naturale — «è arrivato il bonifico, registro» — se non si tocca il campo
della data **registra l'incasso e non sposta niente**. Il partner ha pagato l'anno nuovo e
la vetrina scade lo stesso giorno di prima.

E nell'altro verso è peggio: se si scrive per sbaglio una data **anteriore** a quella in
corso, l'abbonamento **si accorcia in silenzio**. Non c'è nessun avviso, e
`gs_art_pubblicata()` (riga 104) legge quella data per decidere se la vetrina esiste
ancora: **una data sbagliata fa sparire dal sito la vetrina di un partner pagante, nello
stesso momento in cui gli si registra il pagamento.**

### Correzione

Tre pezzi, e i primi due sono **codice che hai già scritto due volte**: riusalo, non
inventarne una terza versione.

**a) L'identificativo, come per il calendario.** Nel JavaScript, generato una volta
all'apertura della scheda del partner (il modulo sta dentro un `<details>`, quindi vale
esattamente il meccanismo di `gaming.js:3987`), più il pulsante disabilitato durante
l'invio. Nel PHP, il controllo prima di scrivere:

```php
	// Stesso meccanismo già usato per i pagamenti del calendario (C1) e per
	// l'accredito dei token (T1): l'identificativo arriva dalla scheda, si
	// controlla e si scrive PRIMA di toccare il registro, mai dopo.
	$rif   = isset( $_POST['rif'] ) ? sanitize_text_field( wp_unslash( $_POST['rif'] ) ) : '';
	$visti = get_post_meta( $id, 'gs_art_pag_rif', true );
	if ( ! is_array( $visti ) ) { $visti = array(); }
	if ( $rif && in_array( $rif, $visti, true ) ) {
		wp_send_json_error( array( 'message' => 'Questo pagamento risulta già registrato.' ) );
	}
	if ( $rif ) {
		$visti[] = $rif;
		update_post_meta( $id, 'gs_art_pag_rif', $visti );
	}
```

Il registro è **per vetrina** e non per persona, quindi qui **non serve il tetto di 50**
che avevi giustamente messo sui token: un partner registra uno o due bonifici l'anno.

**E la rete di sicurezza quando `rif` arriva vuoto** — la stessa scritta per C1, perché la
ragione è la stessa (SiteGround Optimizer tiene in cache il JavaScript combinato, e nei
giorni dopo un aggiornamento un browser può mandare il vecchio):

```php
	if ( ! $rif && $log ) {
		$ultima = end( $log );
		if ( abs( (float) ( $ultima['importo'] ?? 0 ) - $importo ) < 0.005
			&& (string) ( $ultima['scadenza'] ?? '' ) === (string) $scadenza
			&& $ultima['data'] === current_time( 'Y-m-d' ) ) {
			wp_send_json_error( array( 'message' => 'Un bonifico identico è già stato registrato oggi per questo partner: se è davvero un secondo versamento, aggiungi una nota che lo distingua.' ) );
		}
	}
```

Qui la finestra è **la giornata**, non i 15 secondi del calendario: un partner che versa
due volte lo stesso importo con la stessa scadenza nello stesso giorno non esiste, e la
nota dà la via d'uscita.

**b) Controllare l'importo:**

```php
	if ( $importo <= 0 ) {
		wp_send_json_error( array( 'message' => 'Indica l\'importo ricevuto (maggiore di zero).' ) );
	}
```

**Compromesso da dichiarare:** così non si può più registrare una riga da 0 € come
semplice promemoria di rinnovo. Se serve, si usa il campo Note su un bonifico vero — e
comunque una riga da 0 € in un registro di incassi confonde più di quanto aiuti.

**c) Far confermare una scadenza che non allunga niente.** Non bloccarla — una rettifica
può volerla accorciare davvero — ma **non lasciarla passare in silenzio**:

```php
	// La data arriva già compilata con la scadenza in corso: registrare un
	// bonifico senza toccarla è il modo più facile di incassare un rinnovo
	// e non rinnovare niente. Una scadenza che non allunga si può fare, ma
	// va confermata apposta.
	$scadenza_ora = (string) get_post_meta( $id, 'gs_art_scadenza', true );
	$conferma     = ! empty( $_POST['conferma'] );
	if ( $scadenza_ora && $scadenza <= $scadenza_ora && ! $conferma ) {
		wp_send_json_error( array(
			'message'  => 'La scadenza indicata (' . gs_data_it( $scadenza ) . ') non è successiva a quella attuale (' . gs_data_it( $scadenza_ora ) . '): la vetrina non resterà online più a lungo. Se è voluto, conferma.',
			'conferma' => true,
		) );
	}
```

e nel JavaScript, quando la risposta contiene `conferma`, un `confirm()` che ripete il
messaggio e rimanda la stessa richiesta con `conferma: 1`. Il modello è già nel plugin,
nei pulsanti con `confirm()` di `gaming.js:4487`.

**Priorità alta.** Sono soldi, il registro è l'unica contabilità che esiste, e il terzo
punto è un errore che si fa senza accorgersene.

---

## P2 · ALTO — Creare un partner con l'email di qualcuno che è già sul sito lo trasforma in partner e lo fa sparire da tutto il gaming

**File:** `includes/artigiani.php:672-726` (`gs_ajax_art_crea()`)
e `includes/scuole-cucina.php:673-727` (`gs_ajax_scu_crea()`) — VERIFICATO

### Non è un'ipotesi: è già successo

Nel codice c'è scritto. `artigiani.php:779-782`:

> *«Se l'account collegato era stato reso "artigiano" da questa vetrina (es. per errore,
> agganciando l'email di una sfoglina già esistente — **caso reale del 2026-08-08**),
> cestinare la vetrina la fa tornare una sfoglina normale.»*

**È stata costruita la cura e non è stata chiusa la causa.** La causa è ancora tutta lì.

### Cosa succede oggi

```php
if ( email_exists( $email ) ) {
	$existing = get_user_by( 'email', $email );
	$user_id  = $existing ? $existing->ID : null;
}
if ( ! $user_id ) {
	$user_id = wp_insert_user( ... );
	...
}
update_user_meta( $user_id, 'gs_status', 'artigiano' );
```

Quella penultima riga **non guarda chi sia quell'account.** Se l'email appartiene già a
una sfoglina — un errore di battitura, un partner che è anche allieva, un indirizzo
riusato — quella persona diventa `gs_status = 'artigiano'`.

### Cosa comporta, in concreto

`helpers.php:579`, dentro `gs_e_sfoglina_vera()` — che il commento sopra definisce
*«LA funzione da usare ovunque si elenchino le sfogline»*:

```php
if ( in_array( $stato, array( 'artigiano', 'scuola_cucina', 'lettore' ), true ) ) {
	return false;
}
```

Da quel momento quella persona **sparisce**: dalla pagina «Le Sfogline», dal nastro delle
vetrine, dal contatore della comunità che abbiamo appena installato, dagli elenchi del
pannello, dalle esportazioni. I suoi punti restano scritti nel database, ma lei non è più
in nessuna classifica. **E nessuno riceve nessun avviso**, né lei né Ennio: il messaggio
di conferma dice solo *«è stato collegato a quell'account esistente»*, senza dire di chi
sia quell'account né cosa gli è appena successo.

### E l'unica via d'uscita è nascosta in un commento

Il rimedio esiste — cestinare la vetrina — ma **non è scritto da nessuna parte che una
persona possa leggerlo**: sta in un commento del codice e nella conferma del pulsante
«Sposta nel cestino». Chi non sa cosa cercare non ha modo di collegare *«Patrizia non
compare più fra le sfogline»* a *«il mese scorso ho creato una vetrina con la sua email»*.

### Correzione

**a) Guardare chi è, prima di toccarlo.**

```php
	if ( email_exists( $email ) ) {
		$existing = get_user_by( 'email', $email );
		$user_id  = $existing ? $existing->ID : null;
	}

	// Un account che esiste già può essere una sfoglina, o Ennio stesso.
	// Renderlo "artigiano" la toglie da Le Sfogline, dal nastro, dal
	// contatore e dalle classifiche, in silenzio: è già successo davvero
	// l'8 agosto 2026. Da qui in avanti si può fare, ma solo dicendo di chi
	// si tratta e facendolo confermare.
	if ( $user_id && empty( $_POST['conferma'] ) ) {
		$stato_ora = get_user_meta( $user_id, 'gs_status', true );
		$e_admin   = user_can( $user_id, 'manage_options' );
		if ( $e_admin || ! in_array( $stato_ora, array( 'artigiano', 'scuola_cucina', 'lettore' ), true ) ) {
			$chi = get_userdata( $user_id );
			wp_send_json_error( array(
				'message'  => 'L\'email ' . $email . ' è già di ' . $chi->display_name
					. ( $e_admin ? ' (un amministratore del sito)' : ' (una sfoglina del gaming)' )
					. '. Collegandogli questa vetrina, quell\'account diventa un partner ed esce da Le Sfogline, dal nastro e dalle classifiche. Se è voluto, conferma; altrimenti usa un\'altra email.',
				'conferma' => true,
			) );
		}
	}
```

**b) Dirlo anche quando è voluto.** Nel messaggio di successo, aggiungere il nome:

> *«Collegato all'account esistente di Patrizia Lai: da adesso è un partner e non compare
> più fra le sfogline. Per annullare, sposta la vetrina nel cestino.»*

**c) Una vetrina per account.** Oggi niente impedisce di crearne due sullo stesso
indirizzo: `gs_art_owner_post()` (riga 110) ne restituisce **una sola**, quindi la seconda
resta nel pannello e il partner non potrà mai modificarla.

```php
	if ( $user_id && gs_art_owner_post( $user_id ) ) {
		wp_send_json_error( array( 'message' => 'Questo account ha già una vetrina: modificala invece di crearne una seconda (la seconda resterebbe invisibile al partner).' ) );
	}
```

**Priorità alta.** È l'unica voce di questo blocco che è già costata qualcosa, e il
contatore della comunità appena installato è una delle cose che sballa.

---

## P3 · MEDIO-ALTO — L'abbonamento avvisa una volta sola, sette giorni prima, e poi tace: compreso il giorno in cui la vetrina sparisce

**File:** `includes/artigiani.php:820-853` (`gs_art_controlla_scadenze()`),
`includes/scuole-cucina.php:819-852`, e **lo stesso difetto** in
`includes/abbonamenti.php:111-140` (`gs_abbonamento_controlla_scadenze()`, gli abbonamenti
delle sfogline) — VERIFICATO

### Cosa succede oggi

```php
$giorni_mancanti = (int) floor( ( $ts - $oggi ) / DAY_IN_SECONDS );
if ( $giorni_mancanti < 0 || $giorni_mancanti > $giorni_preavviso ) { continue; }

$gia_avvisato = get_post_meta( $p->ID, 'gs_art_avviso_per', true );
if ( $gia_avvisato === $scadenza ) { continue; }
```

Il marcatore è **la data di scadenza**. Quindi il primo giorno in cui il cron entra nella
finestra dei sette, manda l'email e scrive il marcatore — e **da lì in poi, per quella
scadenza, non parte più niente.**

Un solo avviso, sette giorni prima. Poi:

- il **giorno prima**: silenzio;
- il **giorno della scadenza**: silenzio (e `floor()` su una frazione negativa lo esclude
  comunque: alle 9 del mattino del giorno di scadenza `$giorni_mancanti` vale **−1**);
- il **giorno dopo**, quando `gs_art_attivo()` diventa falso e **la vetrina sparisce
  davvero dal sito**: silenzio.

Il partner ha ricevuto una email sette giorni prima, e questo è tutto. La vetrina che paga
si spegne senza che nessuno glielo ricordi.

### Perché conta

Il commento sopra la funzione dice a cosa serviva:

> *«Richiesto da Ennio l'11/08/2026: prima l'abbonamento scaduto nascondeva la vetrina in
> silenzio, senza avvisare nessuno.»*

**Il silenzio è stato spostato, non tolto.** Prima era silenzio totale; adesso è un avviso
sette giorni prima e poi di nuovo silenzio, proprio nei giorni in cui serve.

E vale **anche per gli abbonamenti delle sfogline** (`abbonamenti.php`): stessa forma,
stesso marcatore, stessa finestra. Lì la conseguenza è che una sfoglina perde l'accesso
alle aree private senza che nulla glielo ricordi.

### Correzione

Tre momenti invece di uno, con il marcatore che tiene **la scadenza e la fase**, così
ognuna parte una volta sola:

```php
		// Tre momenti invece di uno: sette giorni prima, l'ultimo giorno, e il
		// giorno in cui la vetrina si è nascosta davvero. Il marcatore tiene
		// scadenza + fase, così ogni fase parte una volta sola anche se il cron
		// gira più volte o salta dei giorni. (Un solo avviso sette giorni prima
		// lascia in silenzio proprio i giorni che contano.)
		$fase = null;
		if ( $giorni_mancanti >= -3 && $giorni_mancanti <= -1 )      { $fase = 'scaduto'; }
		elseif ( $giorni_mancanti >= 0 && $giorni_mancanti <= 1 )    { $fase = 'ultimo'; }
		elseif ( $giorni_mancanti <= $giorni_preavviso )             { $fase = 'preavviso'; }
		else { continue; }

		$marcatore = $scadenza . '|' . $fase;
		if ( get_post_meta( $p->ID, 'gs_art_avviso_per', true ) === $marcatore ) { continue; }

		// Scritto PRIMA di mandare, non dopo: la regola della chiusura del mese.
		update_post_meta( $p->ID, 'gs_art_avviso_per', $marcatore );
```

poi tre testi diversi — il preavviso che c'è già, un *«scade domani»*, e un *«la tua
vetrina non è più visibile: si riaccende non appena registriamo il rinnovo»* — e **la
copia in Posta interna solo per le fasi `ultimo` e `scaduto`**, che sono quelle su cui
Ennio deve agire.

**Il marcatore va scritto prima dell'email.** È la regola che abbiamo stabilito con la
chiusura del mese, e qui vale lo stesso: **compromesso da dichiarare**, se `wp_mail()`
fallisce quell'avviso è perso e non si ripete. Preferibile all'opposto — un cron
giornaliero che riparte da capo e manda la stessa email al partner tutti i giorni — e in
ogni caso la copia in Posta interna arriva comunque a Ennio.

**Attenzione al marcatore vecchio.** Sulle vetrine già avvisate `gs_art_avviso_per` vale
oggi `2026-11-01`, non `2026-11-01|preavviso`: al primo giro col codice nuovo il confronto
non corrisponde e il preavviso **riparte una volta sola**. È accettabile — un avviso in
più, non uno in meno — ma **va detto a Ennio prima dell'installazione**, non dopo.

**Priorità medio-alta.** Non perde soldi da sola: fa perdere un rinnovo, che è la stessa
cosa detta con più passaggi.

---

## P4 · MEDIO — Un partner che corregge una virgola toglie da solo dal sito la vetrina che ha pagato, e nessuno gliel'ha detto

**File:** `includes/artigiani.php:496-552` (`gs_ajax_art_salva()`, riga **542**),
`includes/artigiani.php:556` e `566` (rimozione logo e foto),
più i gemelli in `scuole-cucina.php` — VERIFICATO

### Cosa succede oggi

In fondo al salvataggio:

```php
	// Ogni modifica torna in attesa di approvazione.
	update_post_meta( $id, 'gs_art_stato', 'in_attesa' );
```

E `gs_art_pubblicata()` (riga 104) pretende `'approvata'`. Quindi **appena il partner
salva, la vetrina esce dalla sezione pubblica** — e ci resta fuori finché qualcuno non
riapre il pannello e clicca «Approva».

Vale per qualsiasi modifica: cambiare un numero di telefono, correggere un refuso,
togliere una foto sbagliata. **La moderazione preventiva su una vetrina già approvata e
già pagata costa al partner tutte le ore che passano prima che Ennio guardi.**

### E il testo del pannello dice il contrario

`artigiani.php:438` (e `scuole-cucina.php:443`), il riquadro «Come funziona» che legge il partner:

> *«Ogni modifica torna "in attesa di approvazione": l'Accademia la controlla e la
> pubblica. **La vetrina resta online finché l'abbonamento è attivo**…»*

La seconda frase è falsa nel momento esatto in cui la prima si avvera. Un partner che
legge questo si aspetta che la sua vetrina resti su.

### Una cosa fatta bene, che però non basta

Il salvataggio chiama già `gs_inbox_crea()` (riga 545) e mette l'avviso in Posta interna.
Giusto. Ma **la Posta interna non ha ancora un campanello** — è la decisione ancora aperta
dal blocco precedente — quindi l'avviso resta lì finché Ennio non apre il pannello di sua
iniziativa. **Questa voce è la ragione più concreta per chiudere quella decisione:** non è
solo Ennio che aspetta di sapere, è un partner pagante che aspetta di tornare online.

### Correzione — due strade, la scelta è di Ennio

**La minima e onesta:** lasciare il comportamento com'è e **dire la verità nel testo**:

> *«Ogni modifica torna in attesa di approvazione: **finché l'Accademia non la controlla,
> la vetrina non è visibile nella sezione pubblica.** Di solito è questione di poche ore.»*

**Quella che consiglio:** una vetrina **già approvata** resta online mentre la modifica
aspetta. Serve un contrassegno separato invece di abbassare lo stato:

```php
	// Una vetrina già approvata resta visibile mentre la modifica aspetta:
	// il partner paga per essere online, e una virgola corretta non è una
	// ragione per spegnerla. Solo le vetrine mai approvate passano da
	// "in attesa". Il pannello mostra comunque il pallino rosso.
	if ( 'approvata' === get_post_meta( $id, 'gs_art_stato', true ) ) {
		update_post_meta( $id, 'gs_art_modifica_in_attesa', current_time( 'mysql' ) );
	} else {
		update_post_meta( $id, 'gs_art_stato', 'in_attesa' );
	}
```

e nel pannello, il pallino rosso e l'evidenziazione anche quando c'è
`gs_art_modifica_in_attesa`, cancellandolo su «Approva».

**Compromesso da dichiarare, ed è quello che decide:** con questa strada un partner
approvato può mettere online un testo o una foto **prima** che qualcuno li veda. Il freno
resta il pulsante «Sospendi», che è immediato. **È una scelta su quanto Ennio si fida dei
partner che ha accettato** — non una scelta tecnica, e per questo la mette davanti a lui
invece di deciderla nel codice.

**Priorità media**, e sale a ogni partner che si aggiunge.

---

## P5 · BASSO — L'interruttore «Abilitata come sfoglina», dichiarato «il SÌ definitivo», non lo è

**File:** `includes/helpers.php:571-600` (`gs_e_sfoglina_vera()`) — VERIFICATO

L'ordine dei controlli:

```php
	// riga 579
	if ( in_array( $stato, array( 'artigiano', 'scuola_cucina', 'lettore' ), true ) ) {
		return false;
	}
	...
	// riga 591
	if ( '1' === get_user_meta( $user->ID, 'gs_conta_come_sfoglina', true ) ) {
		return true;
	}
```

Il commento sopra la riga 591 dice:

> *«È il SÌ definitivo — vale anche per un docente che resta Editor o Amministratore per i
> suoi permessi wp-admin (Rina Poletti, Bruno Cingolani), senza doverne cambiare il
> ruolo.»*

**Non è definitivo: il controllo sopra gli passa davanti.** Su un account diventato
`artigiano` — cioè esattamente il caso di P2 — la funzione esce a riga 579 e l'interruttore
non viene mai letto. Stessa cosa in `sfogline-extra.php:534`, dove il `continue` arriva
prima del controllo alla riga 539.

Non c'è un errore visibile oggi, perché nessuno è insieme partner e sfoglina. Ma **c'è una
regola scritta che non è vera**, e le regole non vere si scoprono nel momento peggiore:
Ennio accende l'interruttore su una persona sparita per l'incidente di P2, non succede
niente, e non c'è modo di capire perché.

### Correzione

Spostare il controllo dell'interruttore **sopra** quello sul tipo di account, in tutti e
due i punti:

```php
	// L'interruttore va letto per primo: è un gesto manuale e deliberato di
	// Ennio sulla scheda personale, mentre il tipo di account può essere
	// arrivato lì per sbaglio (vedi P2). Se dice "questa è una sfoglina",
	// quella è la risposta — altrimenti "il SÌ definitivo" non è definitivo.
	if ( '1' === get_user_meta( $user->ID, 'gs_conta_come_sfoglina', true ) ) {
		return true;
	}
	if ( in_array( $stato, array( 'artigiano', 'scuola_cucina', 'lettore' ), true ) ) {
		return false;
	}
```

**Compromesso da dichiarare:** un partner con l'interruttore acceso comparirebbe fra le
sfogline. È precisamente quello che l'interruttore significa, e nessuno lo accende per
sbaglio: sta sulla scheda personale della singola persona.

**Se invece Ennio preferisce che il tipo di account vinca sempre**, allora **va corretto
il commento**, non il codice — perché così com'è dice una cosa falsa. In un modo o
nell'altro, i due non possono restare in disaccordo.

**Priorità bassa** come rischio, **alta come chiarezza**: costa cinque righe.

---

## P6 · BASSO — I due moduli di contatto si limitano a vicenda

**File:** `includes/artigiani.php:381` e `includes/scuole-cucina.php:386` — VERIFICATO

```php
// artigiani.php
$check = gs_antispam_check( $_POST, 'art_contatta' );
// scuole-cucina.php
$check = gs_antispam_check( $_POST, 'art_contatta' );   // ← stesso contesto
```

Il limite antispam è **per IP + contesto** (`antispam.php:58-68`), e il contesto è lo
stesso: le due sezioni condividono lo stesso contatore orario. Chi ha scritto a qualche
artigiano si vede rifiutare il messaggio a una scuola di cucina con *«Troppi invii dallo
stesso indirizzo»*, senza aver mai scritto a nessuna scuola.

È un residuo del copia-e-incolla con cui è nato il file gemello.

### Correzione

Una parola:

```php
$check = gs_antispam_check( $_POST, 'scu_contatta' );
```

**Priorità bassa** — ma è trenta secondi, e vale la pena controllare anche gli altri
contesti mentre ci sei: `esperti.php:375` e `esperti.php:441` usano tutti e due
`'domanda'`, e lì è **giusto** (sono due modi di fare la stessa cosa, e devono condividere
il limite). Questo invece no.

---

## P7 · BASSO — Le foto della galleria sono indirizzate per posizione

**File:** `includes/artigiani.php:566-580` (`gs_ajax_art_media_rimuovi()`) e il gemello —
VERIFICATO

```php
$i = isset( $_POST['i'] ) ? (int) $_POST['i'] : -1;
...
unset( $media[ $i ] );
update_post_meta( $post->ID, 'gs_art_media', array_values( $media ) );
```

`array_values()` **rinumera l'array a ogni rimozione**, e il pulsante manda l'indice che
aveva al momento in cui la pagina è stata disegnata. È lo stesso schema di **E7** — le
risposte dei messaggi indirizzate per posizione.

Il rischio pratico è piccolo: la pagina si ricarica dopo ogni rimozione
(`gaming.js:4398`) e c'è un `confirm()` che blocca il doppio clic. Ma con **due schede
aperte** — o una scheda lasciata aperta e ripresa dopo — si cancella la foto sbagliata,
senza nessun modo di accorgersene se non guardando.

### Correzione

La stessa che serve per E7, e conviene farle insieme quando arriva il Giro 5: **indirizzare
per contenuto invece che per posizione.** Qui è più semplice che nei messaggi, perché
l'URL della foto è già univoco:

```php
	$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
	if ( '' === $url ) { wp_send_json_error( array( 'message' => 'Foto non indicata.' ) ); }
	$media = get_post_meta( $post->ID, 'gs_art_media', true );
	if ( ! is_array( $media ) ) { $media = array(); }
	$prima = count( $media );
	$media = array_values( array_filter( $media, function ( $m ) use ( $url ) {
		return ( $m['url'] ?? '' ) !== $url;
	} ) );
	if ( count( $media ) === $prima ) {
		wp_send_json_error( array( 'message' => 'Questa foto non è più nella galleria.' ) );
	}
```

e nel template `data-url="…"` al posto di `data-i="…"`.

**Priorità bassa.** Sono foto, non soldi. Ma è gratis farla mentre si tocca E7.

---

## Cose minori, da tenere a mente senza farne un giro

- **`gs_data_it()` abita nel file sbagliato.** È definita in `artigiani.php:488` e usata da
  `scuole-cucina.php`, `sfogline-extra.php` e `regia-iscritti.php`. Oggi funziona perché il
  caricatore include `artigiani.php` per primo (`gaming-sfogline.php:121`), ma è una
  funzione di uso generale in un modulo specifico: **il posto giusto è `helpers.php`.** Da
  spostare quando si tocca l'uno o l'altro, non prima.
- **Un account può essere insieme artigiano e scuola di cucina.** Sono due CPT diversi, ma
  un solo `gs_status`: il secondo sovrascrive il primo, e cestinando una delle due vetrine
  `gs_status` viene cancellato del tutto mentre l'altra vetrina resta. Non fa danni visibili
  (i pannelli guardano il post, non lo stato), ma quella persona torna a contare come
  sfoglina pur essendo un partner. Il controllo di P2/c — una vetrina per account — non
  copre questo caso, perché guarda solo il proprio tipo.
- **`wp_untrash_post()`** riporta la vetrina allo stato precedente solo da WordPress 5.6 in
  poi; prima tornava in bozza, e `gs_art_elenco()` chiede `post_status => 'publish'`. Con
  WordPress aggiornato non è un problema — **vale la pena guardare la versione in
  Diagnostica una volta e non pensarci più.**

---

## Riepilogo del blocco

| # | Cosa | File:riga (× 2 file) | Gravità |
|---|---|---|---|
| **P1** | Bonifico: doppio clic, importo non controllato, scadenza sovrascritta | `artigiani.php:753` · `scuole-cucina.php:754` | **Alto** |
| **P2** | Creare un partner su un'email esistente fa sparire una sfoglina | `artigiani.php:672` · `scuole-cucina.php:673` | **Alto** |
| P3 | Un solo avviso di scadenza, sette giorni prima, poi silenzio | `artigiani.php:820` · `scuole-cucina.php:819` · **`abbonamenti.php:111`** | Medio-alto |
| P4 | Ogni modifica spegne la vetrina pagata, e il testo dice il contrario | `artigiani.php:542` · gemello | Medio |
| P5 | «Il SÌ definitivo» non è definitivo | `helpers.php:579` · `sfogline-extra.php:534` | Basso |
| P6 | I due moduli di contatto condividono il limite antispam | `scuole-cucina.php:386` | Basso |
| P7 | Foto della galleria indirizzate per posizione | `artigiani.php:566` · gemello | Basso |

**P1 e P2 vanno insieme:** sono entrambe in `gs_ajax_*_pagamento` e `gs_ajax_*_crea`, si
provano nello stesso pannello, e P2 sistema una cosa già successa.

**P1 riusa il codice di C1 e T1.** Non riscriverlo diversamente: la terza versione dello
stesso controllo scritta in un terzo modo è come nascono le incoerenze fra file.

**P3 tocca anche `abbonamenti.php`**, che non è un file di partner: stessa funzione,
stesso difetto, e lì riguarda le sfogline.

---

## Lo schema, alla quinta volta

C1 (pagamenti del calendario), T1 (accredito token), T2 (invio domanda), A2 (doppio clic
sullo sconto), B5 (salita di livello) e adesso **P1** (bonifico dei partner): **la stessa
operazione che tocca un valore accumulato senza niente che impedisca di eseguirla due
volte**, sul sesto punto in cui il plugin muove soldi.

Con P1 chiusa, **tutti e tre i canali a pagamento del sito** — acconti dei corsi, token
delle consulenze, abbonamenti dei partner — hanno la stessa protezione, scritta nello
stesso modo. Vale la pena dirlo esplicitamente in un commento in cima a
`gs_ajax_art_pagamento()`, così chi scriverà il quarto canale sa dove guardare.

E c'è un secondo schema, che questo blocco fa vedere per la prima volta: **la cura
costruita senza chiudere la causa.** L'incidente dell'8 agosto ha prodotto il ripristino
nel cestino (P2), la richiesta dell'11 agosto ha prodotto un avviso a sette giorni (P3):
in tutti e due i casi il rimedio è stato scritto bene e il punto in cui il problema nasce è
rimasto uguale. È lo stesso motivo per cui la chiusura di luglio è partita.

---

## Cosa leggo adesso

`area-pro.php` (778 righe) e `percorsi-lezioni.php` (1.229): i corsi online, i diplomi e i
punti automatici dei percorsi. È dove A2 — il doppio clic che brucia due livelli — è già
stato trovato dalla prima lettura, quindi ci arrivo sapendo già cosa cercare.
