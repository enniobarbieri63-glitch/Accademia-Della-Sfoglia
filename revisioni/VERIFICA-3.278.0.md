# 3.278.0 verificata — tutto applicato. Ma la serratura di L1 non chiude, e l'errore è mio.

Letto il codice. Diff contro la 3.277.0: tocca **esattamente** i file previsti —
`lezioni-video.php`, `percorsi-lezioni.php`, `points.php`, `token.php`, `calendario.php`,
`gaming.js` — `php -l` pulito su tutti e cinque, `node --check` pulito, **e il file di prova
non è finito nel pacchetto** (controllato: in `includes/` non c'è nessun `test-*.php`).

**L4 e L5 sono corrette e chiuse. L3 è fatta bene** — e meglio di come l'avevo chiesta: i
tre commenti non sono copiati, ognuno nomina la conseguenza precisa di quel modulo (che la
rete di sicurezza di *quel* pannello legge proprio la voce che potrebbe andare persa). È
la differenza fra un avviso e un avviso utile.

**L1 e L2 sono applicate esattamente come le ho scritte. Il problema è che le avevo scritte
male io**, e va detto subito perché cambia cosa resta da fare.

---

# La parte che funziona: il freno nel JavaScript

```js
	if ( det.getAttribute( 'data-gs-vista-inviata' ) ) { return; }
	det.setAttribute( 'data-gs-vista-inviata', '1' );
```

**Questa chiude davvero**, ed è la metà importante. Il JavaScript è a un filo solo: quando
`toggle` scatta, i due ascoltatori (se il file è stato eseguito due volte) vengono chiamati
**uno dopo l'altro**, non insieme. Il primo scrive il contrassegno sull'elemento, il secondo
esce prima di mandare la richiesta.

Quindi **il caso concreto e sistematico che avevo descritto — SiteGround Optimizer che
esegue il blocco due volte, e ogni apertura di ogni lezione che manda due richieste — è
chiuso.** Quello era il rischio vero, e non c'è più.

---

# La parte che non funziona: il contrassegno nel PHP

```php
	$chiave_vista = 'gs_lezione_vista_' . (int) $lid;
	if ( get_user_meta( $uid, $chiave_vista, true ) ) {
		wp_send_json_success();
	}
	update_user_meta( $uid, $chiave_vista, current_time( 'mysql' ) );
```

**Questo è ancora un leggi-poi-scrivi**, esattamente come quello che doveva sostituire. Due
richieste simultanee leggono tutte e due il contrassegno vuoto, lo scrivono tutte e due, e
proseguono tutte e due.

### E c'è una ragione in più, che rende il contrassegno quasi inerte

WordPress carica **tutti** i meta di un utente in una sola lettura, all'inizio della
richiesta: succede quando riconosce chi è collegato (`WP_User` legge i permessi, e con
quelli si porta dietro tutto il resto). Da quel momento ogni `get_user_meta()` risponde
**dalla memoria della richiesta**, non dal database.

Quindi nella richiesta B, `get_user_meta( $uid, 'gs_lezione_vista_123' )` risponde da una
fotografia scattata **prima che la richiesta A scrivesse qualsiasi cosa**. `update_user_meta()`
in A svuota la memoria di A, non quella di B.

**La finestra non è di un millesimo di secondo: è lunga quanto tutta la richiesta A.** È la
stessa identica finestra che aveva il controllo `in_array` di prima.

**Non è colpa dell'applicazione: l'ho specificato io così, e non regge.**

### Non toglierlo

Il contrassegno per lezione **resta la base giusta** — una chiave sola invece di un array,
verificabile, su cui si può mettere sopra una serratura vera. Va tenuto. Va solo smesso di
credere che protegga da solo.

---

# E un difetto che ho introdotto io: il vicolo cieco

Questo è più serio del precedente, perché non è una protezione che manca — è una porta che
si può chiudere per sempre.

Se una richiesta si interrompe **fra** la scrittura del contrassegno e la scrittura di
`gs_lezioni_viste` — connessione caduta, timeout PHP, la sfoglina che chiude la scheda —
resta questo stato:

- `gs_lezione_vista_123` = scritto
- `gs_lezioni_viste` = **non contiene 123**

Da quel momento, per sempre:

1. la lezione risulta **non vista** in tutti gli elenchi (`gs_lezione_e_vista()` legge
   l'array);
2. la sfoglina la riapre — e il contrassegno la respinge, **con `wp_send_json_success()`**,
   cioè senza nessun errore da nessuna parte;
3. `gs_percorso_completato_da()` legge l'array: **quel percorso non si potrà più
   completare**, e con lui il Diploma Finale;
4. **nessuno può accorgersene**, né lei né Ennio.

Nel documento avevo dichiarato il compromesso come *«la sfoglina perde i 5 punti di quella
lezione»*. **Era sbagliato: non perde 5 punti, perde il percorso.** Un difetto raro che
lascia un vicolo cieco invisibile è peggio del difetto che doveva curare.

### Correzione — rimettere a posto invece di respingere

Sostituire il blocco appena aggiunto in `gs_ajax_lezione_segna_vista()` con questo:

```php
	$chiave_vista = 'gs_lezione_vista_' . (int) $lid;
	$viste        = get_user_meta( $uid, 'gs_lezioni_viste', true );
	if ( ! is_array( $viste ) ) { $viste = array(); }

	if ( get_user_meta( $uid, $chiave_vista, true ) ) {
		// Il contrassegno c'è. Se però la lezione non è nell'elenco, un giro
		// precedente si è interrotto a metà (connessione caduta, timeout):
		// senza questa riparazione quella lezione resterebbe "non vista" per
		// sempre — la sfoglina la riapre, il contrassegno la respinge in
		// silenzio, e il percorso non si potrebbe più completare. Si rimette
		// a posto l'elenco SENZA riassegnare i punti.
		if ( ! in_array( $lid, $viste, true ) ) {
			$viste[] = $lid;
			update_user_meta( $uid, 'gs_lezioni_viste', $viste );
		}
		wp_send_json_success();
	}
	update_user_meta( $uid, $chiave_vista, current_time( 'mysql' ) );
```

e più sotto, riusare `$viste` invece di rileggerlo.

**Compromesso, dichiarato bene stavolta:** in quel caso raro la sfoglina perde i 5 punti
della lezione (non li recupera), ma **non perde il percorso**. Cinque punti contro un
Diploma Finale: la scelta non è dubbia.

**La stessa cosa vale per L2**, ma lì è già a posto: il contrassegno `gs_badge_dato_*` e il
badge sono due scritture, e se la prima passa e la seconda no la sfoglina resta senza badge
— ma **niente si blocca**, perché nessun'altra cosa dipende da quel badge. Non serve
riparazione.

---

# Quello che resta davvero aperto: il Percorso di Squadra

Su L1 il contrassegno è **per sfoglina**, quindi anche fosse atomico proteggerebbe solo chi
ha aperto la lezione.

`gs_percorso_squadra_assegna_se_completo()` (`percorsi-lezioni.php:189`) fa un giro su
**tutti i membri**, e chi lo fa partire può essere una qualsiasi delle socie:

```php
	foreach ( gs_squadra_membri( $team ) as $mid ) {
		if ( gs_percorso_squadra_badge_assegna( $pid, $mid ) ) { … }
	}
```

**Due socie diverse che finiscono l'ultima lezione nello stesso momento fanno partire due
giri completi.** Nessun contrassegno per sfoglina lo può vedere: sono due persone diverse,
in due richieste diverse, ognuna col proprio contrassegno vuoto. E il contrassegno
`gs_badge_dato_*` di ciascun membro viene letto — di nuovo — dalla fotografia scattata
all'inizio di ogni richiesta.

**Con una squadra da sei: 360 punti invece di 180.** È il caso che avevo indicato come il
motivo per cui L2 serviva anche facendo L1, e **è ancora esattamente com'era.**

---

# La correzione vera, ed è già nel plugin

Non serve inventare niente: **il lucchetto di MySQL è già usato, sullo stesso host, per lo
stesso tipo di problema.** `calendario.php:571-578`:

```php
	global $wpdb;
	$lock_prenota = 'gs_prenota_' . $corso_id;
	if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_prenota ) ) ) {
		wp_send_json_error( array( 'message' => 'Troppe richieste nello stesso momento su questo corso, riprova tra un attimo.' ) );
	}
```

con sopra il commento che dice come è stato trovato: *«dimostrato con un test di carico il
14/08/2026: un corso con 3 posti ne ha accettate 4»*.

**È lo stesso problema, e la soluzione è già scritta e già provata su SiteGround.**

## a) Il Percorso di Squadra — qui serve, non è opzionale

Il lucchetto va **attorno al giro**, non dentro, e va sulla coppia percorso+squadra (non
sulla sfoglina): sono più persone che agiscono sulla stessa cosa.

```php
function gs_percorso_squadra_assegna_se_completo( $pid, $uid ) {
	$team = gs_get_user_team( $uid );
	if ( ! $team || ! gs_percorso_squadra_completato( $pid, $team ) ) { return false; }

	// Lucchetto sulla coppia percorso+squadra, stesso meccanismo dei posti
	// dei corsi (calendario.php:571): qui non basta un contrassegno per
	// sfoglina, perché due socie DIVERSE che finiscono l'ultima lezione
	// insieme fanno partire due giri completi su tutti i membri, e ognuna
	// legge i contrassegni dalla fotografia dei meta scattata all'inizio
	// della propria richiesta. Con una squadra da sei sono 360 punti invece
	// di 180 (26/08/2026).
	global $wpdb;
	$lock = 'gs_perc_sq_' . (int) $pid . '_' . (int) $team;
	if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
		return false;   // ci sta già pensando un'altra richiesta: va bene così
	}

	$assegnati = false;
	foreach ( gs_squadra_membri( $team ) as $mid ) {
		if ( gs_percorso_squadra_badge_assegna( $pid, $mid ) ) { $assegnati = true; }
	}

	$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	return $assegnati;
}
```

**Due cose da controllare mentre lo scrivi**, e sono quelle che rendono i lucchetti
pericolosi:

1. `$team` deve essere un numero o una stringa breve e stabile — se `gs_get_user_team()`
   restituisce un oggetto, va preso il suo identificativo. **Verificalo prima di scrivere
   la riga**, non dopo;
2. **il lucchetto va rilasciato su ogni uscita.** Nel codice sopra c'è una sola uscita dopo
   la presa, ed è per questo che il `foreach` non deve contenere nessun `return` o
   `wp_send_json_*`. `gs_percorso_squadra_badge_assegna()` non ne ha — **ricontrollalo**,
   perché se un giorno qualcuno ce ne mette uno, il lucchetto resta preso fino a fine
   richiesta e blocca le altre socie per cinque secondi ciascuna.

**E la lettura dentro il lucchetto va fatta dal database, non dalla memoria.** Dentro
`gs_percorso_squadra_badge_assegna()`, prima di leggere il contrassegno:

```php
	wp_cache_delete( $uid, 'user_meta' );   // i meta in memoria sono di inizio richiesta
	if ( get_user_meta( $uid, $chiave_fatto, true ) ) { return false; }
```

**Senza questa riga il lucchetto non serve a niente**, perché dentro si continuerebbe a
leggere la fotografia vecchia. È lo stesso `wp_cache_delete()` che `gs_add_points()` usa già
dopo i suoi incrementi atomici (`points.php`), per la stessa ragione.

## b) La lezione vista — meno urgente, ma cinque righe

Il freno nel JavaScript copre il caso concreto. Restano **due schede aperte** o due
dispositivi: raro, ma vale fino a 135 punti. Stesso meccanismo, chiave per
sfoglina+lezione:

```php
	global $wpdb;
	$lock = 'gs_lez_' . $uid . '_' . (int) $lid;
	if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
		wp_send_json_success();   // ci sta già pensando un'altra richiesta
	}
	wp_cache_delete( $uid, 'user_meta' );
```

subito prima del blocco del contrassegno, e il `RELEASE_LOCK` **su tutte** le uscite —
comprese le due `wp_send_json_success()`.

**Compromesso da dichiarare:** un lucchetto in più su un'operazione che parte a ogni
apertura di una lezione. È leggero (riguarda solo quella sfoglina su quella lezione, e dura
millesimi di secondo), ma è un `GET_LOCK` a ogni apertura di lettore. **Se preferisci non
metterlo, dillo e resta senza** — il JavaScript regge il caso vero. Non lo farei se non
fosse che in fondo a quella richiesta ci sono 135 punti.

---

# Una nota sul metodo, e non è un rimprovero

*«Tutti i 20 controlli passati»* è vero, e non contraddice niente di quello che c'è qui
sopra: **una prova che esegue le cose una dopo l'altra non può far vedere una corsa.** Per
definizione serve che due cose succedano *insieme*.

Il precedente è nel plugin stesso: il difetto dei posti dei corsi non è stato trovato da un
controllo, è stato trovato da un **test di carico** — «un corso con 3 posti ne ha accettate
4». È l'unico modo.

**Se vuoi provare davvero L1 e L2**, la prova è questa, e con il gaming spento si può fare:

```bash
# Due richieste simultanee sulla stessa lezione, dallo stesso utente.
for i in 1 2; do
  curl -s -b cookies.txt -d "action=gs_lezione_segna_vista&nonce=NONCE&lezione=ID" \
    https://…/wp-admin/admin-ajax.php &
done
wait
```

poi si guarda `gs_points_log` di quella sfoglina: **una riga o due?**

E per il Percorso di Squadra, la stessa cosa con **due utenti diversi della stessa squadra**
sull'ultima lezione mancante. Quella è la prova che conta.

**Se non è comodo farla**, va benissimo — ma allora **non scriviamo che L1 e L2 sono
verificate**: scriviamo che sono applicate e che la corsa non è stata provata. Sono due
frasi diverse, e la seconda è quella vera.

---

# Riepilogo

| | Stato |
|---|---|
| **L1 · freno nel JavaScript** | ✅ **chiude il caso vero** (doppia esecuzione del file) |
| **L1 · contrassegno nel PHP** | ⚠️ applicato bene, **ma non protegge dalla corsa** — mio errore di specifica |
| **L1 · vicolo cieco** | 🔴 **da correggere**: un giro interrotto blocca il percorso per sempre, in silenzio |
| **L2 · contrassegni sui tre badge** | ✅ applicati; **ma il Percorso di Squadra resta scoperto** |
| L3 · avvisi sugli storici | ✅ e scritti meglio di come li avevo chiesti |
| L4 · fuso orario dei percorsi stagionali | ✅ |
| L5 · freno sul diploma | ✅ |
| File di prova fuori dal pacchetto | ✅ controllato |

**Da fare, in ordine:**

1. **La riparazione del vicolo cieco** (`gs_ajax_lezione_segna_vista`) — è la più urgente
   delle tre, perché è l'unica che *peggiora* qualcosa rispetto a prima.
2. **Il lucchetto sul Percorso di Squadra**, con il `wp_cache_delete()` dentro. Senza, L2
   sul caso che contava non è stata fatta.
3. **Il lucchetto sulla lezione vista** — solo se sei d'accordo sul compromesso.
4. Poi, se si può, **la prova a due richieste**. Altrimenti si dice che non è stata fatta.

**Nient'altro della 3.278.0 va rifatto.**

---

## Cosa leggo adesso

`voting.php` e `giuria-turno.php` — le sfide e le giurie. Sono i primi due dei diciassette
file che toccano punti o badge, ed è il gruppo dove, dopo L1 e L2, mi aspetto il resto.
