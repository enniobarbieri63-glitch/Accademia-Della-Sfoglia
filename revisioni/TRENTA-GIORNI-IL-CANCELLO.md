# I trenta giorni: il cancello, il congelamento, i testi

**Per Claude Code Ennio 2 — 26/08/2026**
**Questo è il lavoro con la scadenza vera: le sfogline arrivano a settembre.**

---

## Quello che Ennio ha deciso, in cinque righe

1. **Entrare nell'Accademia è gratis.** Non si paga nulla per registrarsi.
2. All'approvazione parte un **regalo di 30 giorni** di accesso completo al gaming.
3. **Al trentesimo giorno l'accesso al gaming cessa**: *«vedono solo le pagine in chiaro non del gaming»*.
4. Niente va perso: **«congelato significa salvato»**. Tutto resta, in attesa.
5. Si riapre con un **bonifico di 29 euro**. Il bonifico e la riattivazione li fa Ennio a mano; **tutto il resto deve essere automatico**.

---

## La scoperta che cambia il lavoro

Prima di scrivere una riga, leggi questa. Cambia la dimensione del compito.

Oggi in `includes/sezioni.php:141` c'è:

```php
if ( 'superiore' === $liv && gs_abbonamento_scaduto( $uid ) ) { return false; }
```

Un abbonamento scaduto chiude **solo** le sezioni di livello `'superiore'`. Nel registro di `gs_sez_registry()` (`sezioni.php:22-108`) le sezioni `'superiore'` sono **sette**:

`lezioni`, `calendario`, `area_pro`, `messaggi`, `esperto`, `conversazioni`, `inbox`.

**Tutto il gaming è `'base'`**: sfida, classifica, sfogline, ricettario, indovina, tavolo, misurata, giuria, sondaggi, piatti in estinzione, matterello, cassaforte, sfoglia che insegna, letture, promemoria, percorso personale, riepilogo anno, badge, diario, consigli, galleria, registro, traguardi, compleanni.

Quindi: **se oggi metti una sfoglina a "scaduto", continua a giocare a tutto il gaming.** Le si chiudono i corsi e i messaggi con i docenti, nient'altro.

Ennio vuole l'opposto: al trentesimo giorno il gaming si chiude e restano le pagine in chiaro. **Non basta aggiungere la scadenza automatica: va cambiata la regola del cancello.** Questa è la parte che nessuno aveva ancora guardato.

---

## Il confine fra "in chiaro" e "riservato" esiste già, ed è disegnato bene

Non inventarne uno nuovo. Nel plugin il confine è già tracciato, un pezzo alla volta, da chi ha scritto ogni shortcode: **le sezioni riservate cominciano con un controllo di accesso**.

```php
if ( ! is_user_logged_in() ) { return gs_login_notice(); }
```

`gs_login_notice()` è definita in `shortcodes.php:27` ed è chiamata in **29 punti**, sparsi in 25 file:

```
piatti-estinzione, anno-fa-oggi, artigiani, sfoglia-insegna, promemoria,
sfoglia-misurata, scuole-cucina, indovina, esperti, ricerca-globale,
giuria-turno, messaggi, riepilogo-anno, ricettario, control-panel,
cronologia, tavolo, matterello-parlante, testamento-sfoglina, area-pro,
sondaggi, cassaforte-sapere, shortcodes, lezioni-video, seo
```

Le pagine che **non** hanno quel controllo sono le pagine in chiaro: Galleria, Registro Ufficiale, Le Sfogline, Classifica, Badge, Vetrina, Letture, FAQ, Novità, Dicono di Noi, Compleanni, Traguardi.

**È esattamente la frase di Ennio.** Una sfoglina congelata deve vedere quello che vede un visitatore qualsiasi: quelle pagine lì, e non le altre.

Questo rende il lavoro meccanico invece che di giudizio: **non devi decidere sezione per sezione cosa chiudere.** Devi solo far sì che una sfoglina congelata attraversi quei 29 cancelli come se non fosse entrata.

---

## A6 — Un solo stato, una sola funzione

### A6.1 — La data diventa vincolante

In `abbonamenti.php` esiste già `gs_abbonamento_scadenza`, una data `Y-m-d` per sfoglina, già modificabile dal pannello «Abbonamenti delle sfogline», già con le email di preavviso. Ma il commento in cima al file (`abbonamenti.php:12-14`) dice:

> *«non cambia da sola lo stato: resta sempre il gestore a impostare "scaduto" a mano, la data serve solo per avvisare prima che succeda»*

**Quella data va promossa da promemoria a scadenza vera.** Non serve un meta nuovo, non serve un pannello nuovo: c'è già tutto, cambia solo cosa significa.

Aggiungi in `abbonamenti.php`, accanto a `gs_abbonamento_scaduto()`:

```php
/**
 * True se questa sfoglina è CONGELATA: fuori dall'area riservata.
 *
 * Due strade, una sola risposta:
 *  • lo stato messo a mano dal pannello ('scaduto');
 *  • la data gs_abbonamento_scadenza passata — che dal 26/08/2026 non è più
 *    solo un promemoria ma la scadenza vera (Ennio: "certo scadenza
 *    automatica, noi di manuale controlliamo solo il bonifico").
 *
 * Nessuna data e stato "attivo" = accesso aperto. È una scelta deliberata,
 * non una dimenticanza: gli account di Ennio, dei collaboratori, degli amici
 * e dei giornalisti non hanno una scadenza e non devono averla. Il prezzo è
 * che una data cancellata per sbaglio riapre l'accesso invece di chiuderlo —
 * accettato, perché l'errore opposto (chiudere fuori chi ha pagato) è molto
 * peggio.
 */
function gs_sfoglina_congelata( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) { return false; }
	if ( user_can( $uid, 'gs_manage_gaming' ) || user_can( $uid, 'manage_options' ) ) {
		return false; // i gestori non si congelano mai
	}
	if ( 'scaduto' === get_user_meta( $uid, 'gs_abbonamento', true ) ) {
		return true;
	}
	$scadenza = get_user_meta( $uid, 'gs_abbonamento_scadenza', true );
	if ( ! $scadenza || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $scadenza ) ) {
		return false;
	}
	// Due date attraverso la stessa funzione, mai un timestamp contro una
	// mezzanotte: è l'errore di P3 (25/08/2026), trovato tre volte.
	return strtotime( current_time( 'Y-m-d' ) ) > strtotime( $scadenza );
}
```

Nota il `>` e non `>=`: **il giorno della scadenza è ancora dentro.** Trenta giorni di regalo vuol dire trenta giorni interi.

### A6.2 — I trenta giorni partono dall'approvazione

In `registration.php:200` (dentro `gs_approve_user()`) oggi c'è:

```php
update_user_meta( $user_id, 'gs_data_approvazione', current_time( 'Y-m-d' ) );
```

Diventa:

```php
// La data di approvazione si scrive UNA VOLTA SOLA. Se questa funzione
// viene richiamata su una sfoglina già approvata (doppio clic, riapprovazione
// dopo il bonifico) l'orologio dei trenta giorni NON deve ripartire da capo:
// è lo stesso schema del contrassegno-prima-degli-effetti usato ovunque nel
// plugin. Senza questa riga, riapprovare una sfoglina le regalerebbe un
// secondo mese gratis.
if ( ! get_user_meta( $user_id, 'gs_data_approvazione', true ) ) {
	update_user_meta( $user_id, 'gs_data_approvazione', current_time( 'Y-m-d' ) );

	// I trenta giorni in regalo (Ennio, 26/08/2026: "iscrizione è gratis,
	// vale 30 giorni la prova sul gaming"). Da qui in poi la scadenza è
	// automatica: il gestore la sposta a mano solo quando arriva il bonifico.
	update_user_meta(
		$user_id,
		'gs_abbonamento_scadenza',
		date( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +30 days' ) )
	);
	update_user_meta( $user_id, 'gs_abbonamento', 'attivo' );
}
```

### A6.3 — Il pannello dice la verità

`abbonamenti.php:66` oggi dice alla riga di aiuto:

> *«La data di scadenza è facoltativa: serve solo a mandare un promemoria via email qualche giorno prima (di default 7). Non cambia da sola lo stato "Attivo/Scaduto": quello resta sempre una scelta tua.»*

Non è più vero. Va riscritta:

> *«La data di scadenza è la data in cui l'accesso all'area riservata si chiude da solo. Alla registrazione viene messa in automatico a 30 giorni dall'approvazione: è la prova in regalo. Quando arriva il bonifico di 29 euro, sposta la data in avanti di un anno — nient'altro. Se togli la data, l'accesso resta aperto senza scadenza: usalo per i collaboratori, per gli amici e per i giornalisti. Lo stato "Scaduto" chiude subito, senza aspettare la data.»*

E `abbonamenti.php:54` (l'aiuto del pannello) dove dice *«accede solo al gaming pubblico di primo livello»*: adesso non accede più al gaming, punto. Riscrivilo di conseguenza.

Nel pannello aggiungi anche **una colonna che mostra i giorni che mancano**: Ennio deve poter guardare la tabella e vedere chi sta per scadere senza fare i conti. Nient'altro, nessuna funzione nuova: `gs_sfoglina_congelata()` e la data ce l'hai già.

---

## A6.4 — Il cancello

### Il cancello delle pagine

In `shortcodes.php`, accanto a `gs_login_notice()`:

```php
/**
 * Il cancello dell'area riservata: una sola porta per due modi di restarne
 * fuori. Ritorna '' se si può passare, altrimenti l'HTML da mostrare al
 * posto della sezione.
 *
 * Sostituisce, uno a uno, i 29 "if ( ! is_user_logged_in() ) return
 * gs_login_notice();" già sparsi nel plugin: quel confine — chi chiede
 * l'accesso e chi no — è già la distinzione fra le pagine "in chiaro" e il
 * gaming che Ennio ha chiesto il 26/08/2026, ed è disegnato meglio di
 * qualunque elenco scritto adesso a mano.
 */
function gs_gate_riservato() {
	if ( ! is_user_logged_in() ) {
		return gs_login_notice();
	}
	if ( function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( get_current_user_id() ) ) {
		return gs_congelata_avviso();
	}
	return '';
}
```

E l'avviso, in `abbonamenti.php` accanto a `gs_abbonamento_avviso()` (che questo sostituisce — vedi sotto):

```php
/** Il riquadro che una sfoglina congelata vede al posto di ogni sezione del gaming. */
function gs_congelata_avviso() {
	$s = gs_settings();
	$importo = $s['registration']['importo_quota'] ?? '29,00';
	return '<div class="gs-box gs-notice gs-box-congelata">'
		. '<h3>Il tuo mese di prova è finito</h3>'
		. '<p><strong>Non hai perso niente.</strong> I tuoi punti, i tuoi badge, il tuo percorso, '
		. 'le tue ricette, le tue foto e tutto quello che hai scritto in «La Mia Sfoglia» sono '
		. 'salvati esattamente come li hai lasciati. Sono congelati, non cancellati: il giorno '
		. 'in cui rientri, ritrovi tutto al suo posto.</p>'
		. '<p>Per riaprire questa parte del sito serve un contributo di <strong>' . esc_html( $importo ) . ' €</strong> '
		. 'a sostegno dell\'Accademia. Trovi come farlo nella tua pagina «La Mia Sfoglia».</p>'
		. '<p>Nel frattempo le pagine aperte del sito restano tue: la Galleria, il Registro, '
		. 'la Classifica, le Sfogline, le Letture e la tua Vetrina.</p>'
		. '</div>';
}
```

**Poi, nei 29 punti**, questa sostituzione — sempre uguale, mai adattata:

```php
// prima
if ( ! is_user_logged_in() ) { return gs_login_notice(); }
// dopo
if ( $g = gs_gate_riservato() ) { return $g; }
```

Due avvertenze:

- In `control-panel.php` e in `seo.php` la sostituzione è innocua ma inutile (là non arriva mai una sfoglina congelata, e un gestore non si congela): falla lo stesso, così la riga è identica ovunque e nessuno dovrà chiedersi perché due sono diverse.
- **Non toccare `shortcodes.php:124`**, il controllo dentro `gs_sc_dashboard()`. Quello ha un trattamento suo, qui sotto.

### Il cancello dei punti

Chiudere le pagine non basta. Gli handler AJAX (`wp_ajax_gs_*`) restano raggiungibili da una scheda del browser lasciata aperta: la pagina non si vede più, ma la richiesta arriva lo stesso. Serve un secondo cancello **dove i punti si scrivono davvero**, che è un posto solo: `gs_add_points()` in `points.php:22`.

Subito dopo `$points = (int) $points;`:

```php
// Una sfoglina congelata non guadagna punti, nemmeno da una scheda del
// browser rimasta aperta o da una richiesta AJAX costruita a mano: le pagine
// sono chiuse, ma gli handler wp_ajax_* restano raggiungibili. Qui si scrive
// il totale, ed è l'unico posto: chiuderlo qui li chiude tutti.
//
// I punti NEGATIVI passano sempre (sono le correzioni del gestore), e passa
// tutto quello che fa un gestore per conto di qualcun altro dal pannello
// «Correggi punti di una sfoglina».
if ( $points > 0
	&& function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( $user_id )
	&& ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) {
	return (int) get_user_meta( $user_id, 'gs_points', true );
}
```

Il valore di ritorno resta il totale attuale, come nel ramo `0 === $points` due righe sopra: chi chiama non deve accorgersi di niente di nuovo.

### La dashboard resta aperta — e questo è un giudizio mio

`gs_sc_dashboard()` (`shortcodes.php:123`) ha già tre cancelli in fila: non collegata → non approvata → blackout. **Il quarto non lo aggiungo, e ti spiego perché.**

«La Mia Sfoglia» non è una pagina del gaming: è la sua cartella personale. È il posto dove Ennio ha chiesto che si veda che *«è tutto salvato e congelato»*, ed è il posto dove deve stare scritto come si fa il bonifico da 29 euro. Se la chiudi, la sfoglina congelata non ha più nessun posto dove leggere né l'una né l'altra cosa — e l'unica pagina che le resta è la schermata di accesso.

Quindi:

- La dashboard **si apre** anche da congelata.
- In cima, al posto di `gs_abbonamento_avviso()` (`shortcodes.php:157`), va `gs_congelata_avviso()` con le istruzioni per il bonifico. `gs_abbonamento_avviso()` (`abbonamenti.php:39`) dice ancora *«Puoi continuare a usare il gaming pubblico di primo livello»*: **è diventata falsa, va sostituita, non affiancata.**
- I numeri (punti, streak, badge, livello) **si vedono**, fermi. È il senso di "congelato significa salvato": deve vederli, non deve poterli muovere. E non può, perché il cancello dei punti è chiuso e le pagine dove si guadagnano anche.
- Le missioni di oggi, l'ingrediente segreto e i pulsanti che portano alle sezioni chiuse: **nascondili**, non lasciarli lì a portare su un cancello. Un pulsante che non porta da nessuna parte è una promessa rotta.

**Questa è la mia lettura, non una parola di Ennio.** Alla lettera *«vedono solo le pagine in chiaro»* chiuderebbe anche questa. Segnalagliela quando gli riferisci: se dice di chiuderla, si chiude, ma allora le istruzioni per il bonifico devono finire nell'email del trentesimo giorno, perché non resterebbe nessun altro posto.

---

## A5 — Il congelamento vero: quattro posti che oggi non lo rispettano

Chiudere il cancello non basta. Ci sono cose che **continuano a girare da sole** su una sfoglina che non c'è più, e ognuna rompe la promessa di Ennio in un modo diverso.

### A5.1 — La streak si azzera (`streak.php:129`)

```php
$users = get_users( array(
	'meta_key'     => 'gs_streak',
	'meta_compare' => 'EXISTS',
	'number'       => -1,
) );
```

Prende **tutte** le sfogline che hanno una streak, senza guardare se sono dentro o fuori. Su una congelata succede questo: settimana dopo settimana il controllo scatta, **brucia uno scudo per volta** (`streak.php:148`), le manda pure l'aeroplanino *«IL TUO SCUDO HA SALVATO LA STREAK!»* — e quando gli scudi finiscono, `update_user_meta( $user->ID, 'gs_streak', 0 )`.

Una sfoglina che torna dopo due mesi trova la streak a zero e gli scudi consumati. **Non è congelato, è distrutto piano.**

Dentro il `foreach`, subito dopo `$streak = (int) get_user_meta(...)`:

```php
// Congelata: la sua serie non corre e non si azzera — sta ferma dov'era.
// "Congelato significa salvato" (Ennio, 26/08/2026): se il controllo
// continuasse a girare su di lei, le brucerebbe uno scudo a settimana e
// poi le porterebbe la streak a zero, mentre lei è fuori e non può farci
// niente.
if ( function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( $user->ID ) ) { continue; }
```

### A5.2 — La chiusura del mese le manda «hai fatto 0 punti» (`buono-sfoglia.php:89`)

```php
foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
	if ( ! gs_e_sfoglina_vera( $uid ) ) { continue; }
```

Nessun filtro sul congelamento. Il primo del mese la sfoglina congelata riceve **due messaggi** — un'email (`gs_mail_progetto`, riga 123) e un messaggio privato sul sito (`gs_invia_messaggio`, riga 130) — che dicono:

> *«A settembre hai totalizzato 0 punti nel percorso del mese. Non hai raggiunto la soglia di 2500 punti per il Buono Sfoglia questo mese: mancavano 2500 punti.»*

**È il caso di luglio 2026 che si ripresenta con un'altra faccia.** Allora era il gaming spento; adesso è la sfoglina spenta. Stesso danno: un rimprovero automatico per non aver giocato a una partita a cui non poteva partecipare.

Subito dopo il controllo `gs_e_sfoglina_vera`:

```php
// Congelata: nessun resoconto del mese. Non ha potuto giocare — dirle che
// le mancavano 2500 punti è il rimprovero di luglio 2026 in un'altra forma.
// Il contrassegno del mese NON si scrive: quando rientra, il mese in cui
// era fuori resta semplicemente un mese senza resoconto, non un mese
// "già gestito" che poi salta fuori a sorpresa.
if ( function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( $uid ) ) { continue; }
```

Attenzione all'ordine: il `continue` va **prima** di `update_user_meta( $uid, $chiave_fatto, 1 )` (riga 105), non dopo.

### A5.3 — I promemoria delle lezioni continuano ad arrivare (`lezioni-video.php:248`)

`gs_lezioni_promemoria_non_viste()` scorre le assegnazioni e scrive a chi non ha ancora visto una lezione. Una sfoglina congelata **non può** vederla: le Lezioni sono `'superiore'`, chiuse anche oggi. Le arriva *«Una lezione ti aspetta»* più l'aeroplanino, per una porta sbarrata.

Dentro il `foreach ( $assegnazioni as &$a )`, prima del calcolo dei giorni:

```php
// Congelata: la lezione c'è ma lei non può aprirla. Non si manda niente e
// NON si segna promemoria_inviato: quando rientra, il promemoria è ancora
// lì e le arriva quando può davvero servire.
if ( function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( $a['user_id'] ) ) { continue; }
```

Il *non* segnare è deliberato ed è il contrario di quello che si fa negli altri due casi: qui il promemoria non è "saltato", è **rimandato**.

### A5.4 — La passata su tutti gli agganci del cron giornaliero

Sono **quattordici**. Vanno guardati uno per uno, non a occhio: la domanda è sempre la stessa — *questo, su una sfoglina congelata, le toglie qualcosa o le scrive?*

| # | File:riga | Funzione | Cosa fare |
|---|---|---|---|
| 1 | `streak.php:127` | `gs_check_streaks` | **A5.1** — brucia scudi e azzera |
| 2 | `buono-sfoglia.php:146` | `gs_buono_sfoglia_controlla_chiusura_mese` | **A5.2** — «0 punti» |
| 3 | `lezioni-video.php:247` | `gs_lezioni_promemoria_non_viste` | **A5.3** — promemoria per una porta chiusa |
| 4 | `registration.php:222` | `gs_mail_benvenuto_differite` | già a posto (3.284.1), ma **cambia il controllo** da `gs_abbonamento_scaduto()` a `gs_sfoglina_congelata()` |
| 5 | `abbonamenti.php:111` | `gs_abbonamento_controlla_scadenze` | **da riscrivere** — vedi A6.5 |
| 6 | `compleanni.php:106` | `gs_bday_annuncio_giornaliero` | **guardalo tu**: se annuncia in pubblico il compleanno di chi è fuori, decidi e motiva. Non ho letto il file. |
| 7 | `calendario.php:1741` | `gs_cal_promemoria_domani` | promemoria di un corso pagato: **deve arrivare comunque**. Chi ha pagato un corso lo frequenta anche se il gaming è chiuso. Verifica e lascia stare. |
| 8 | `token.php:340` | `gs_token_controlla_rimborsi` | soldi: **deve girare comunque**. Verifica e lascia stare. |
| 9 | `buono-sfoglia.php:273` | `gs_buono_sfoglia_controlla_scadenza` | scadenza annuale di uno sconto già guadagnato: **guardalo**, non deve scadere mentre lei è congelata e non può usarlo |
| 10 | `artigiani.php:912` | `gs_art_controlla_scadenze` | partner, non sfogline: non c'entra |
| 11 | `scuole-cucina.php:912` | `gs_scu_controlla_scadenze` | partner, non sfogline: non c'entra |
| 12 | `voting.php:610` | `gs_close_expired_challenges` | chiude sfide, non tocca persone: non c'entra |
| 13 | `sconto-corsi.php:136` | `gs_sconto_reset_annuale` | **guardalo**: se azzera uno sconto guadagnato, è lo stesso problema del 9 |
| 14 | `media-backup.php:211` | `gs_backup_giornaliero` | file, non persone: non c'entra |

Sette non c'entrano, tre li ho scritti io qui sopra, **quattro (6, 9, 13 e la verifica di 7 e 8) li devi guardare tu** — non li ho letti e non voglio dirti cosa fare in file che non ho aperto. Riferisci cosa hai trovato.

### A6.5 — Le email di scadenza vanno riscritte da capo

`gs_abbonamento_controlla_scadenze()` (`abbonamenti.php:112`) oggi è costruita su un presupposto che sta per cadere: che **la data non chiuda niente**. Lo dice il commento alla riga 133 e lo fa il codice: nella fase `'scaduto'` avvisa **Ennio** con un messaggio in Posta interna, perché *«non sarebbe vero»* dire alla sfoglina che qualcosa si è spento da solo.

Dal momento in cui la data diventa vincolante, **diventa vero**, e quel messaggio va alla persona sbagliata.

La struttura buona (marcatore-prima-di-mandare, le tre fasi, `$scadenza . '|' . $fase`) **tienila tutta**: è già stata sistemata bene il 25/08. Cambia i destinatari e i testi:

- **`'preavviso'` (7 giorni prima)** → alla sfoglina. «Fra una settimana il tuo mese di prova finisce. Ecco cosa succede e come continuare.» **Deve contenere le due date**: quando ha cominciato, quando finisce.
- **`'ultimo'` (oggi o domani)** → alla sfoglina. L'ultimo giorno utile, con l'IBAN e i 29 euro.
- **`'scaduto'` (dopo)** → **una email alla sfoglina** («è tutto salvato, si riapre così») **e** la voce in Posta interna per Ennio, che ora serve a un'altra cosa: sapere chi è appena uscito, per richiamarlo.

E aggiungi il filtro che manca: `if ( gs_abbonamento_scaduto( $u->ID ) ) { continue; }` (riga 115) salta chi è già "scaduto" a mano, ma **non** chi è congelato dalla data. Vanno saltate entrambe, o al preavviso ci finisce anche chi è già fuori.

---

## A4 — Undici testi che dicono ancora "prima paghi, poi entri"

Non erano cinque: sono undici, più due impostazioni. Li ho cercati tutti. **Il messaggio da togliere è sempre lo stesso: che l'accesso dipende da un pagamento verificato prima.** Adesso l'iscrizione è gratis e il pagamento arriva dopo trenta giorni, se la persona vuole.

La riga che Ennio ha dato e che va usata come chiave di tutti questi testi:

> **«Siamo una communitas che difende l'arte e la professione della sfoglia a mano.»**

| # | Dove | Cosa dice oggi (in breve) |
|---|---|---|
| 1 | `shortcodes.php:77` | «il tuo account resta in attesa finché la segreteria non verifica la quota associativa» |
| 2 | `shortcodes.php:96-107` | il riquadro «Quota associativa annuale: 29 €» con IBAN, **nel modulo di iscrizione** |
| 3 | `shortcodes.php:106` | «L'account resta in attesa finché la segreteria non verifica l'arrivo del bonifico.» |
| 4 | `shortcodes.php:109` | la spunta obbligatoria `testo_quota` |
| 5 | `shortcodes.php:130` | «in attesa di approvazione… non appena la segreteria avrà verificato la quota associativa» |
| 6 | `registration.php:40` | «Devi confermare l'adesione alla quota associativa.» |
| 7 | `registration.php:101` | «Il tuo account sarà attivato dopo la verifica della quota associativa.» |
| 8 | `registration.php:131` | email di verifica: «l'attivazione dipende dalla verifica della quota associativa» |
| 9 | `login.php:243` | «sarà comunque attivato dalla segreteria dopo la verifica della quota associativa» |
| 10 | `faq.php:73` | «la registrazione è gratuita, ma l'accesso viene approvato… dopo la verifica della quota» |
| 11 | `faq.php:74` | «La segreteria verifica il pagamento della quota associativa prima di attivare l'account.» |
| + | `admin.php:868` | (pannello, solo per Ennio) «Approva ogni richiesta solo dopo aver verificato il pagamento» |
| + | `helpers.php:159` | il valore di default di `testo_quota` |

Nota su **#2, il riquadro dell'IBAN nel modulo**: non toglierlo dal sito, **spostalo**. Serve ancora — ma dopo, quando i trenta giorni finiscono, e sta bene nella dashboard e nelle email di scadenza. Nel modulo di iscrizione, adesso, è la cosa che fa chiudere la pagina.

Nota su **#4, la spunta obbligatoria**: oggi la sfoglina deve confermare *«di aver aderito alla quota associativa annuale»* per potersi iscrivere. **Va tolta come obbligo**, perché ora non ha aderito a niente e non deve. Il posto si libera: è dove va la spunta del consenso alla Vetrina (A3), che Ennio ha chiesto di fare *«mentre compilano»*.

E `helpers.php:160`, `'importo_quota' => '29,00'`: **è già giusto**. I 29 euro di Ennio sono già lì. Cambia quando si chiedono, non quanto.

---

## La mail di benvenuto, versione finale

Va in `gs_approve_user()` (`registration.php:181-186`), al posto del corpo attuale. È l'unica email che riceve nel primo giorno, e deve dire tre cose e basta: **è gratis, hai trenta giorni, non perderai niente.**

```
Ciao %NOME%,

benvenuta nell'Accademia della Sfoglia.

Iscriversi è gratuito, e lo resta: siamo una communitas che difende l'arte
e la professione della sfoglia a mano, non un club a pagamento.

Come regalo di benvenuto, da oggi hai trenta giorni di accesso completo
alla parte riservata del sito — il percorso, le sfide, la classifica, il
ricettario, il Tavolo di Lavoro, e la tua pagina «La Mia Sfoglia» dove
tenere le tue cose.

I tuoi trenta giorni vanno dal %DATA_INIZIO% al %DATA_FINE%.

Il %DATA_FINE% l'accesso a quella parte si chiude. Non perdi niente:
punti, badge, ricette, foto e tutto quello che avrai scritto restano
salvati esattamente dove li hai lasciati, congelati e pronti. Il resto
del sito — la Galleria, il Registro, la Classifica, le Letture, la tua
Vetrina — resta aperto come per tutti.

Per riaprire la parte riservata basta un contributo di 29 euro a sostegno
dell'Accademia. Te lo ricorderemo per tempo, con le istruzioni: adesso non
devi fare nulla.

Accedi qui: %LINK%

Buona sfoglia.
Accademia della Sfoglia
```

**Le due date devono stare in questa email**, calcolate da `gs_data_approvazione` e da `gs_abbonamento_scadenza` che hai appena scritto due righe sopra nella stessa funzione. È la correzione di un errore mio: quando ho distanziato le tre email di benvenuto, ho spostato «Accesso e Vetrina» al quinto giorno senza accorgermi che era quella legata all'inizio della prova. Oggi non fa danno perché nessuna delle tre nomina la prova. **Da adesso sì, e la regola è: le date della prova stanno nell'email del giorno 0, mai in quelle rimandate.** Se il giorno 2 o il giorno 5 non partono, la sfoglina deve avere le sue date lo stesso.

Le altre due email di benvenuto (giorno 2 e giorno 5) **non toccarle**: funzionano e sono già distanziate.

---

## L'ordine di lavoro

Uno per volta, in questo ordine, perché ognuno si appoggia al precedente:

1. **`gs_sfoglina_congelata()`** in `abbonamenti.php`. Da sola non cambia niente: nessuno la chiama ancora. Installala e verifica su guru2 che il sito stia in piedi.
2. **`gs_approve_user()`**: la scrittura una-volta-sola e i trenta giorni. Prova ad approvare una sfoglina di prova e guarda i due meta.
3. **Il cancello dei punti** in `gs_add_points()`. È una riga e chiude la superficie di scrittura più larga.
4. **`gs_gate_riservato()`** e le 29 sostituzioni. **Questo è il passo grosso**: fallo tutto in una volta o non farlo, perché a metà il sito è incoerente.
5. **A5**, i tre `continue` più la passata sui quattordici agganci del cron.
6. **A6.5**, le email di scadenza.
7. **A4**, gli undici testi.
8. **La mail di benvenuto.**

### La prova che vale

Su guru2, con una sfoglina finta:

- data di scadenza a **ieri** → prova ad aprire il Tavolo, la Sfida, il Ricettario, il Percorso: tutte devono dare il riquadro «il tuo mese di prova è finito». La Galleria, il Registro, la Classifica, le Sfogline e le Letture devono aprirsi normalmente.
- Con quella stessa sfoglina, **prova a votare da una scheda già aperta prima di scadere**: i punti non devono muoversi.
- data di scadenza a **oggi** → deve essere ancora dentro a tutto. È il `>` invece del `>=`.
- **nessuna data, stato "attivo"** → dentro a tutto, per sempre. È il caso di Ennio e degli amici.
- Fai girare `gs_daily_cron` a mano su una congelata con una streak e degli scudi: **né la streak né gli scudi devono cambiare di un'unità.**

### Quello che non devi fare

- **Non toccare `'livello' => 'superiore'` nel registro** e non riclassificare le sezioni. Quella distinzione serve ancora a un'altra cosa (chi ha pagato i corsi vede il calendario). Il congelamento è un'altra dimensione, e si aggiunge, non sostituisce.
- **Non cancellare `gs_abbonamento_scaduto()`.** È chiamata in giro; `gs_sfoglina_congelata()` la usa dentro. Aggiungi, non sostituire.
- **Non spegnere niente per "sicurezza"** durante il congelamento: la sfoglina congelata deve poter fare login, cambiare password, vedere la sua Vetrina e leggere le pagine in chiaro. Se un cancello che metti le impedisce di rientrare quando paga, è un cancello sbagliato.

---

## Sul rapporto che hai appena mandato

**Newsletter tolta**: chiara e fatta bene. Mettere i due contenuti nel cestino invece che cancellarli è la scelta giusta — e detto che gli iscritti erano zero, non c'è niente da recuperare. Nessuna obiezione.

**L'avviso PHP in `regia-iscritti.php`**: l'ho cercato in 3.284.4 e **non l'ho trovato**. Ho letto il file per intero (434 righe) e ho controllato una per una le funzioni che chiama — `gs_cal_prenotazioni`, `gs_cal_corso_get`, `gs_cal_pren_pagato`, `gs_conv_di_utente`, `gs_get_messaggi_utente`, `gs_data_it`, `gs_cal_attestato_toggle`, `gs_cal_attestato_url`, `gs_regia_e_sospesa`: nessuna ritorna mai un `WP_Error`, e non c'è nessun punto in cui un `WP_Error` finisca dentro un'operazione numerica.

Delle due l'una: o l'avviso arriva da una funzione più in fondo nella catena e il numero di riga che vedi punta a `regia-iscritti.php` solo perché è il chiamante, **oppure hai in mano una versione del file più recente di quella che ho io**.

Quando puoi, **mandami l'avviso letterale** — il testo esatto con file e riga come lo scrive PHP. Da quello si arriva subito. Che sia un compito a parte è giusto: non c'entra con la Newsletter e non c'entra con questo documento.

---

## Le due cose che deve dire Ennio

Riferiscigliele quando gli passi il documento. **Non aspettare la risposta per cominciare**: i passi 1, 2, 3 e 5 non dipendono da nessuna delle due.

1. **«La Mia Sfoglia» resta aperta a chi è congelata?** Io dico di sì, e sopra ho spiegato perché: è dove si vede che è tutto salvato ed è dove stanno le istruzioni per i 29 euro. Se dice di no, quelle istruzioni devono finire nell'email del trentesimo giorno, perché non resterebbe nessun altro posto dove leggerle.

2. **Le sfogline che sono già dentro adesso — le sei più Rosemma — che scadenza hanno?** La regola che ho scritto (nessuna data = accesso aperto) le lascia dentro per sempre, e credo sia quello che vuole: sono amici e giornalisti, non hanno fatto nessun bonifico da 29 euro. Ma è una cosa che deve dire lui, non io.
