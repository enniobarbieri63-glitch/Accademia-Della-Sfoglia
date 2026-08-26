# Il Tavolo di Lavoro senza limite giornaliero — cosa cambiare

**Deciso da Ennio il 26/08/2026:** togliere il limite di una foto al giorno sul Tavolo di
Lavoro. Una sfoglina può caricare quante foto vuole.

**Non è togliere una riga.** Il modulo è costruito tutto attorno a «una foto al giorno»: il
testo lo promette, **il modulo di caricamento sparisce** dopo la prima foto, e i punti e
l'avviso ai maestri partono a ogni caricamento. Qui c'è tutto quello che va toccato, in
ordine.

---

## La decisione sui punti, che è la sola cosa delicata

**Oggi ogni foto vale +5 punti** (`tavolo.php:192`). Il limite giornaliero era l'unica cosa
che li teneva a freno — non per scelta, per effetto collaterale.

Tolto il limite senza toccare altro: 20 foto al giorno per 30 giorni fanno **3.000 punti**,
mentre la soglia del Buono Sfoglia è **2.500 al mese**. Il Buono si vincerebbe caricando
foto, non facendo sfoglia.

**Quindi si separano le due cose, che finora erano una sola:**

- **quante foto può caricare** → **nessun limite**, come chiede Ennio;
- **quante volte al giorno quelle foto danno punti** → **una**.

Chi ne carica cinque le vede tutte e cinque, i maestri le commentano tutte e cinque, e i
punti restano quelli di prima. **È quello che Ennio ha chiesto senza il difetto che si
porterebbe dietro.**

**Se invece Ennio vuole i punti a ogni foto**, è **una riga sola** da togliere — quella
indicata al punto **b** qui sotto — e va detto a lui che così la soglia mensile diventa
raggiungibile caricando foto. **Non deciderlo tu: la riga sta lì apposta, evidenziata.**

---

## a) Togliere il blocco

**File:** `includes/tavolo.php:172`, dentro `gs_ajax_tavolo_invia()`

```php
	if ( gs_tavolo_di_oggi( $uid ) ) { wp_send_json_error( array( 'message' => 'Hai già caricato la tua foto di oggi. Torna domani per la prossima.' ) ); }
```

**Cancellare questa riga.** È tutto, per la parte del blocco.

---

## b) I punti una volta al giorno, e non con una query

**File:** `includes/tavolo.php:192`

Al posto di:

```php
	gs_add_points( $uid, gs_get_points_value( 'tavolo_foto', 5 ), 'Il Tavolo di Lavoro: foto del giorno' );
```

scrivere:

```php
	// I punti restano UNA volta al giorno, anche se le foto adesso sono
	// libere (Ennio, 26/08/2026: "togliamo il limite della foto
	// giornaliera"). Le due cose erano una sola per effetto collaterale, non
	// per scelta: senza questa distinzione, 20 foto al giorno per 30 giorni
	// fanno 3.000 punti contro i 2.500 della soglia del Buono Sfoglia, e il
	// Buono si vincerebbe caricando foto invece che facendo sfoglia.
	//
	// Il contrassegno è un meta con la data, NON una ricerca fra i post: il
	// blocco precedente si appoggiava a gs_tavolo_di_oggi(), ed è proprio
	// quella ricerca che il tema ha alterato in silenzio per settimane
	// (vedi gs_get_posts_by_author in helpers.php) lasciando i punti senza
	// alcun freno. Un contrassegno diretto non dipende da nessuna query e
	// non può rompersi allo stesso modo.
	//
	// ►►► Se Ennio decide che ogni foto deve dare punti, si toglie il blocco
	//     if/else qui sotto e resta la sola gs_add_points(). ◄◄◄
	$oggi_ymd = current_time( 'Y-m-d' );
	if ( get_user_meta( $uid, 'gs_tavolo_punti_giorno', true ) !== $oggi_ymd ) {
		update_user_meta( $uid, 'gs_tavolo_punti_giorno', $oggi_ymd );
		gs_add_points( $uid, gs_get_points_value( 'tavolo_foto', 5 ), 'Il Tavolo di Lavoro: foto del giorno' );
	}
```

**Il contrassegno si scrive prima di assegnare**, come in tutte le altre correzioni di
questo lavoro.

**Compromesso da dichiarare:** se la creazione della foto fallisse dopo questa riga, la
sfoglina perderebbe i 5 punti di quel giorno. Ma qui la riga sta **dopo** `wp_insert_post()`
e dopo le due `update_post_meta()`: a quel punto la foto esiste già, quindi il caso non si
presenta.

---

## c) L'avviso ai maestri una volta al giorno, non a ogni foto

**File:** `includes/tavolo.php:194-197`

```php
	if ( function_exists( 'gs_inbox_crea' ) ) {
		$u = get_userdata( $uid );
		gs_inbox_crea( 'Nuova foto sul Tavolo di Lavoro', … );
	}
```

Con le foto libere, **una sfoglina che ne carica dieci riempie la Posta interna di dieci
avvisi identici.** E la Posta interna è la casella dove arrivano le disdette con acconto da
restituire e, da novembre, i rendiconti della chiusura del mese: **annegarla di avvisi
ripetuti è il modo di far smettere di guardarla.**

Un avviso per sfoglina al giorno, con lo stesso contrassegno:

```php
	// Un avviso al giorno per sfoglina, non uno per foto: con le foto libere
	// (26/08/2026) dieci caricamenti farebbero dieci avvisi identici nella
	// stessa casella dove arrivano le disdette con acconto da restituire e i
	// rendiconti di fine mese. Il pannello del Tavolo mostra comunque tutte
	// le foto, con quelle senza commento in cima.
	if ( function_exists( 'gs_inbox_crea' )
		&& get_user_meta( $uid, 'gs_tavolo_avviso_giorno', true ) !== $oggi_ymd ) {
		update_user_meta( $uid, 'gs_tavolo_avviso_giorno', $oggi_ymd );
		$u = get_userdata( $uid );
		gs_inbox_crea(
			'Nuove foto sul Tavolo di Lavoro',
			( $u ? $u->display_name : '' ) . ' ha caricato una o più foto oggi.',
			array( 'from' => $u ? $u->display_name : 'Sfoglina' )
		);
	}
```

**Il pannello del gestore non va toccato**: `gs_pannello_tavolo()` mostra già tutte le foto
con quelle senza commento in cima (`tavolo.php:95-101`), e funziona identico con più foto
al giorno.

---

## d) Il modulo deve restare sempre visibile — questa è la parte vera

**File:** `includes/tavolo.php:123-146`, dentro `gs_sc_tavolo()`

Oggi:

```php
	$oggi_id = gs_tavolo_di_oggi( $uid );
	if ( $oggi_id ) {
		… mostra la foto di oggi …
	} else {
		… mostra il modulo di caricamento …
	}
```

**Il modulo sta nell'`else`.** Cioè: tolto il blocco nell'AJAX, **una sfoglina non avrebbe
comunque nessun posto dove caricare la seconda foto** — il modulo è già sparito. Senza
questo pezzo, la modifica non si vede.

### Come riscriverlo

Serve **le foto di oggi al plurale**. Aggiungere accanto a `gs_tavolo_di_oggi()`:

```php
/** Tutte le foto caricate oggi da una sfoglina (le più recenti prima). */
function gs_tavolo_di_oggi_tutte( $uid ) {
	$oggi = current_time( 'Y-m-d' );
	return array_values( array_filter( gs_tavolo_tutti_di( $uid, 50 ), function ( $p ) use ( $oggi ) {
		return substr( $p->post_date, 0, 10 ) === $oggi;
	} ) );
}
```

e nel corpo dello shortcode, **il modulo sempre fuori dalla condizione**:

```php
	$uid       = get_current_user_id();
	$di_oggi   = gs_tavolo_di_oggi_tutte( $uid );

	$out .= '<div class="gs-todo-riquadro">';

	// Il modulo resta SEMPRE disponibile: le foto non hanno più un limite
	// giornaliero (Ennio, 26/08/2026). Prima stava nell'"else" — tolto il
	// blocco lato server senza toccare questo, la seconda foto non si
	// sarebbe comunque potuta caricare, perché il modulo era già sparito.
	$out .= '<form class="gs-form gs-form-tavolo" onsubmit="return false">';
	$out .= '<p>Aggiungi una foto: ' . gs_msg_file_field() . '</p>';
	$out .= '<p><label>Una riga per raccontarla (facoltativa)<br><input type="text" name="didascalia" autocomplete="off" style="width:100%" placeholder="Es. Prima volta con i pizzicati…"></label></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-tavolo-invia">Carica la foto</button> <span class="gs-tavolo-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';

	if ( $di_oggi ) {
		$out .= '<p class="gs-hint" style="font-size:16px;margin-top:14px"><strong>'
			. ( count( $di_oggi ) > 1 ? 'Le tue foto di oggi' : 'La tua foto di oggi' ) . '</strong></p>';
		foreach ( $di_oggi as $p ) {
			$c = gs_tavolo_get( $p->ID );
			if ( $c['media'] ) { $out .= gs_msg_media_html( $c['media']['url'], isset( $c['media']['type'] ) ? $c['media']['type'] : 'image' ); }
			if ( $c['didascalia'] ) { $out .= '<p class="gs-hint">' . esc_html( $c['didascalia'] ) . '</p>'; }
			$out .= $c['commento']
				? '<p class="gs-hint"><strong>💬 Un maestro ha scritto:</strong><br>' . nl2br( esc_html( $c['commento'] ) ) . '</p>'
				: '<p class="gs-hint">In attesa del commento di un maestro.</p>';
		}
	}
	$out .= '</div>';
```

E più sotto, «Le tue foto passate» deve escludere **tutte** quelle di oggi, non una sola:

```php
	$ids_oggi = array_map( function ( $p ) { return $p->ID; }, $di_oggi );
	$passate  = array_values( array_filter( gs_tavolo_tutti_di( $uid ), function ( $p ) use ( $ids_oggi ) {
		return ! in_array( $p->ID, $ids_oggi, true );
	} ) );
```

---

## e) I testi, che oggi promettono il contrario

**File:** `includes/tavolo.php:121` — il riquadro «Come funziona»:

> *«…Una foto al giorno; domani puoi caricarne un'altra.»*

diventa:

> *«…Puoi caricarne quante vuoi: ogni foto avrà il suo commento. I punti del gioco si
> prendono una volta al giorno, ma le foto no — carica pure quando ti va.»*

(**Se Ennio sceglie i punti a ogni foto**, la seconda frase salta.)

E in cima al file, il commento di intestazione (`tavolo.php:18-21`) dice ancora *«Una sola
foto al giorno per sfoglina (stesso schema "confronta la data" di missions.php e
indovina.php)»*: **va riscritto**, altrimenti fra sei mesi qualcuno legge una regola che non
esiste più e ci ragiona sopra.

---

## Cosa NON toccare

- **`gs_tavolo_di_oggi()` resta.** Non è più usata per bloccare, ma continua a servire e
  soprattutto **è la funzione su cui è stata provata la correzione del bug del tema**:
  toglierla vorrebbe dire perdere quel riferimento. Se dopo le modifiche non la chiama più
  nessuno, **dimmelo invece di cancellarla** — decidiamo insieme.
- **`gs_pannello_tavolo()`**: funziona già identico con più foto al giorno.
- **`gs_tavolo_totale_senza_commento()`** e la riga nella Bacheca
  (`control-panel.php:567`): il numero salirà, ed è giusto che salga — è il numero di foto
  che aspettano un maestro. Vedi sotto.

---

## Due conseguenze che Ennio deve sapere

**1. I maestri.** Ogni foto aspetta un commento, e il contatore in Bacheca conta le foto
senza commento. Con le foto libere quel numero può crescere in fretta, e **il valore del
Tavolo è proprio il commento**: una foto senza risposta è peggio di una foto non caricata.
Non c'è niente da cambiare nel codice — ma se dopo qualche settimana il numero non scende
più, la risposta giusta non è tecnica: è decidere se i maestri commentano tutto o solo la
prima del giorno.

**2. Lo spazio su disco.** Ogni foto è un file caricato. Il plugin comprime già le immagini
nel browser prima di mandarle e ha un limite per file, quindi non c'è niente da fare
adesso — **ma senza limite giornaliero la cartella dei caricamenti cresce senza tetto**, e
`media-backup.php` la copia tutti i giorni. Vale la pena guardare quanto spazio dà
SiteGround fra un mese, non oggi.

---

## Come provarlo

1. Da sfoglina, **carica tre foto di fila**. Devono passare tutte e tre, e vedersi tutte e
   tre sotto «Le tue foto di oggi».
2. Guarda `gs_points_log` di quella sfoglina: **una sola voce** *«Il Tavolo di Lavoro»*,
   non tre.
3. Guarda la Posta interna: **un solo avviso**, non tre.
4. Torna il giorno dopo (o cambia a mano `gs_tavolo_punti_giorno`): la prima foto del nuovo
   giorno deve dare di nuovo i 5 punti.
5. Nel pannello del gestore, tutte le foto devono comparire, quelle senza commento in cima.

**Il punto 2 è quello che conta**: è la ragione per cui questa modifica non è una riga.

---

## E una cosa che resta da fare da prima

Nella verifica della 3.281.0 c'era il punto **D1**: fino a ieri il limite **non funzionava**
per il bug del tema, quindi chi ha caricato più foto in un giorno **ha già preso più volte i
5 punti**. Quei punti sono ancora scritti.

**Contali prima di installare questa modifica**, non dopo — così si distingue quello che è
arrivato dal difetto da quello che arriverà dalla scelta di Ennio: per ogni sfoglina, quante
voci ci sono in `gs_points_log` con motivazione *«Il Tavolo di Lavoro: foto del giorno»*, e
in quanti giorni distinti. **Porta i numeri a Ennio, non correggere niente di tua
iniziativa.**
