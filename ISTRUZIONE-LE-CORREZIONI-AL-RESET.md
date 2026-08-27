# Istruzione: le correzioni al Reset, prima di premere

**Per Claude Code Ennio 3 — 27/08/2026, scritta su 3.296.0**

Hai in mano il pacchetto `gamingsfogline3.296.0.zip` e due documenti:
`ISTRUZIONE-IL-RESET.md`, che dice **come è stato costruito** il Reset, e questo, che dice
**cosa gli manca**. Dove i due si contraddicono, vale questo: è più recente ed è scritto dopo
aver censito il plugin.

Il Reset che trovi nel pacchetto **funziona**. Anteprima, marcatori, log, cache, la parola
digitata, la Parte 2 sullo username: tutto verificato, tutto a posto. Quello che segue non
sistema cose rotte. Sistema **cose che mancano dall'elenco**, e le cose che mancano da un
elenco non danno errore: si vedono il giorno dopo, sull'account di una persona.

---

## La regola non cambia

> Si cancella tutto quello che comincia per `gs_`, TRANNE un elenco scritto di cose da tenere.

Non toccare quella regola. Tutte le correzioni qui sotto **aggiungono voci agli elenchi**, non
tolgono niente e non capovolgono niente. E non cancellare mai un utente: quella resta una cosa
che decide Ennio, a mano, una persona alla volta.

---

## PARTE 1 — Le tre da fare prima di qualsiasi altra cosa

### 1. La scatola di chi è nel Cestino

`gs_archivia_dati_gaming_utente()` (`sfogline-extra.php:40`) prende **tutti** i meta `gs_` di
una sfoglina che finisce nel Cestino (`sospesa`, `rifiutata`, `eliminata`) e li chiude dentro
un'unica chiave, `gs_archivio_gaming`. I sette risparmiati sono stato, email, genere, data di
nascita: **abbonamento, scadenza, token, Vetrina e sconto finiscono dentro la scatola.**

Quella chiave non è nell'elenco del Reset. Oggi il Reset **cancella la scatola intera**, e il
ripristino dal Cestino non riporta più niente. Quelle persone, per di più, non compaiono nella
tabella dell'anteprima, che scorre solo le sfogline attive: l'anteprima direbbe che è tutto a
posto.

Servono **tre** modifiche, non una. Tenere la scatola e basta non basta.

**1a — tenere la chiave.** In `gs_reset_meta_da_tenere()`, aggiungi il gruppo nuovo in fondo,
con il commento (serve a chi legge fra un anno):

```php
		// --- La scatola di chi è nel Cestino. gs_archivia_dati_gaming_utente()
		// (sfogline-extra.php) ci chiude dentro TUTTI i meta gs_ di una sfoglina
		// sospesa o rifiutata: abbonamento, scadenza, token, Vetrina, sconto.
		// Cancellare questa chiave sola vuol dire cancellarli tutti insieme, e
		// il ripristino dal Cestino non riporterebbe più niente. Chi tocca uno
		// dei due elenchi guardi anche l'altro: è
		// gs_archivio_gaming_meta_esclusi() a decidere cosa finisce qui dentro.
		'gs_archivio_gaming',       // = GS_ARCHIVIO_GAMING_META
```

**1b — ripulire la scatola con la stessa regola di tutto il resto.** Tenerla intera farebbe
tornare i punti di prima il giorno in cui quella sfoglina viene ripristinata dal Cestino:
sarebbe l'unica in tutto il sito con un punteggio anteriore al Reset. Dentro la scatola si
tiene quello che è nell'elenco, e si toglie il resto. In `gs_reset_esegui()`, subito **dopo**
il punto 1 (la `DELETE` sui meta) e prima del punto 2:

```php
	// 1b) La scatola di chi è nel Cestino. La cancellazione qui sopra non la
	// tocca (è nell'elenco da tenere, e deve esserci: dentro ci sono
	// abbonamento, scadenza e token di quella persona). Ma il suo contenuto
	// va trattato con la stessa regola di tutti gli altri meta: si tiene
	// quello che è nell'elenco, si toglie il resto — altrimenti una sfoglina
	// ripristinata dal Cestino dopo il Reset si riprenderebbe i punti di
	// prima, unica in tutto il sito.
	$scatole_ripulite = 0;
	foreach ( get_users( array( 'meta_key' => GS_ARCHIVIO_GAMING_META, 'fields' => 'ID' ) ) as $uid_arch ) {
		$scatola = get_user_meta( $uid_arch, GS_ARCHIVIO_GAMING_META, true );
		if ( ! is_array( $scatola ) || ! $scatola ) { continue; }
		$ripulita = array_intersect_key( $scatola, array_flip( $tenere_meta ) );
		if ( count( $ripulita ) === count( $scatola ) ) { continue; }
		update_user_meta( $uid_arch, GS_ARCHIVIO_GAMING_META, $ripulita );
		$scatole_ripulite++;
	}
```

e aggiungi `'scatole_ripulite' => $scatole_ripulite,` alla voce del log, accanto a
`'sondaggi_svuotati'`.

**1c — farle vedere nell'anteprima.** È la parte che conta di più. Il documento dice che la
tabella «per ogni sfoglina, cosa resta» è la parte importante di tutto il pannello: se non
mostra queste persone, non serve a niente proprio nel caso in cui servirebbe.

```php
/**
 * Per una sfoglina nel Cestino, il valore di una chiave sta DENTRO la
 * scatola dell'archivio, non fra i suoi meta: guardare solo i meta la
 * farebbe sembrare senza abbonamento e senza token, che è l'opposto della
 * verità.
 */
function gs_reset_meta_o_archivio( $uid, $chiave ) {
	$scatola = get_user_meta( $uid, GS_ARCHIVIO_GAMING_META, true );
	if ( is_array( $scatola ) && array_key_exists( $chiave, $scatola ) ) {
		return $scatola[ $chiave ];
	}
	return get_user_meta( $uid, $chiave, true );
}

/** Le sfogline nel Cestino: non sono nell'elenco principale, ma abbonamento e token sono i loro. */
function gs_reset_riepilogo_cestino() {
	$righe = array();
	foreach ( get_users( array( 'meta_key' => GS_ARCHIVIO_GAMING_META, 'orderby' => 'display_name' ) ) as $u ) {
		$scadenza = gs_reset_meta_o_archivio( $u->ID, 'gs_abbonamento_scadenza' );
		$righe[] = array(
			'nome'        => $u->display_name,
			'stato'       => (string) get_user_meta( $u->ID, 'gs_status', true ),
			'abbonamento' => 'scaduto' === gs_reset_meta_o_archivio( $u->ID, 'gs_abbonamento' ) ? 'scaduto (a mano)' : 'attivo',
			'scadenza'    => $scadenza ? $scadenza : 'nessuna (accesso libero)',
			'token'       => (int) gs_reset_meta_o_archivio( $u->ID, 'gs_token_credito' ),
			'vetrina'     => gs_reset_meta_o_archivio( $u->ID, 'gs_vetrina_token_attiva' ) ? 'sì' : 'no',
		);
	}
	return $righe;
}
```

In `gs_reset_anteprima()` aggiungi `'cestino' => gs_reset_riepilogo_cestino(),`.

In `assets/js/gaming.js`, dentro `gsRenderResetAnteprima( d )`, subito **dopo** la tabella
`Per ogni sfoglina, cosa resta dopo il Reset` (che finisce con `out += '</tbody></table>';`):

```js
		if ( d.cestino && d.cestino.length ) {
			out += '<h5>Sfogline nel Cestino — anche a loro resta tutto</h5>';
			out += '<p class="gs-hint">I loro dati stanno dentro l\'archivio del Cestino, non fra i meta: si vedono solo qui.</p>';
			out += '<table class="gs-table"><thead><tr><th>Sfoglina</th><th>Stato</th><th>Abbonamento</th><th>Scadenza</th><th>Token</th><th>Vetrina</th></tr></thead><tbody>';
			d.cestino.forEach( function ( s ) {
				out += '<tr><td>' + gsEsc( s.nome ) + '</td><td>' + gsEsc( s.stato ) + '</td><td>' + gsEsc( s.abbonamento ) + '</td><td>' + gsEsc( s.scadenza ) + '</td><td>' + gsEsc( s.token ) + '</td><td>' + gsEsc( s.vetrina ) + '</td></tr>';
			} );
			out += '</tbody></table>';
		}
```

### 2. Il calendario dei corsi e le prenotazioni

`calendario.php:18` registra due tipi che nessuno dei due elenchi nomina:

- **`gs_corso_cal`** — una data di corso: data, orari, posti, prezzo, acconto, descrizione. La
  scrive il titolare dal pannello (`calendario.php:773`). È catalogo quanto una lezione.
- **`gs_prenotazione`** — la prenotazione di un cliente, con dentro `gs_acconto_versato`,
  `gs_saldo_versato`, `gs_pagamenti_log`, `gs_pagamenti_rif` (i riferimenti dei bonifici) e
  l'attestato.

Oggi il Reset li cancella tutti e due in via definitiva. È «cancellare denaro versato», che il
documento vieta per i token: qui è denaro versato per un corso vero con Rina, e insieme
sparisce il calendario che lo vendeva. In `gs_reset_tipi_da_tenere()`:

```php
		// Il calendario dei corsi e le prenotazioni (calendario.php): dentro
		// ci sono acconti e saldi già versati e i riferimenti dei bonifici.
		// Stessa ragione dei token: è denaro, non punteggio. Le date le
		// scrive il titolare, come le lezioni.
		'gs_corso_cal', 'gs_prenotazione',
```

### 3. Il permesso: il pannello è del titolare, la richiesta no

`gs_pannello_reset()` si mostra solo a `manage_options`, e il commento sopra dice la cosa
giusta. Ma i quattro AJAX controllano `gs_can_manage()`, che è `manage_options` **oppure**
`gs_manage_gaming` (`control-panel.php:24`): i collaboratori. Il nonce ce l'hanno su ogni
pagina del pannello, quindi una sola richiesta POST fa partire il Reset. Il pulsante nascosto
non è una protezione.

In **tutti e quattro** gli handler di `reset.php` (`gs_ajax_reset_anteprima`,
`gs_ajax_reset_esegui`, `gs_ajax_nicename_anteprima`, `gs_ajax_nicename_applica`), sostituisci
il controllo con:

```php
	// Non gs_can_manage(): quello comprende i collaboratori (gs_manage_gaming),
	// e tutto questo pannello è del titolare soltanto — il Reset è l'unica
	// operazione del plugin che non si annulla, e la Parte 2 riscrive gli
	// indirizzi pubblici di tutte.
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
```

---

## PARTE 2 — Le quattro minori, dello stesso tipo

Tutte e quattro sono voci che il documento avrebbe messo nei suoi stessi gruppi, se le avesse
viste. In `gs_reset_meta_da_tenere()`, dentro i gruppi che ci sono già:

```php
		// nel gruppo "Chi è":
		'gs_telefono',          // il numero di telefono: non lo scrive il plugin, è
		                        // messo a mano una volta e serve ai link WhatsApp
		                        // della regia iscritti. Dato di contatto, non punteggio.

		// nel gruppo "La Vetrina pubblica":
		'gs_vetrina_bloccata',  // il blocco amministrativo. Il Reset tiene il contenuto
		                        // della Vetrina (gs_bio_*): cancellare il blocco da solo
		                        // rimetterebbe pubblica una Vetrina bloccata dal titolare.
```

E in `gs_reset_tipi_da_tenere()`:

```php
		// Scritti dal titolare in wp-admin, non dalle sfogline: catalogo.
		'gs_barometro',    // le Guide Stagionali (menu proprio in amministrazione)
		'gs_ingrediente',  // gli Ingredienti Segreti, compresi quelli programmati
		                   // nel futuro (post_status 'future'), che sparirebbero
		                   // prima ancora di uscire
```

---

## PARTE 3 — Quello che NON devi decidere tu

Queste sono scelte, non errori. Portale a Ennio scritte così, con la tua proposta accanto, e
**aspetta la risposta**. Se la risposta non arriva, fai il resto e lascia queste come sono:
sono tutte reversibili tranne il Reset stesso.

| | la cosa | oggi | proposta |
|---|---|---|---|
| **A** | **Le «Cose da Fare»** (`gs_todos`, `gs_todos_cestino`) — i promemoria che la sfoglina si scrive da sola | si cancellano | **tenerle.** Stesso argomento della decisione 1 sul Testamento: «l'ha scritto lei, non è un punteggio». Il codice stesso dice «niente cancellazione definitiva» |
| **B** | **Il Diario dell'Impasto** (`gs_diario`) e **i Consigli** (`gs_consiglio`) | si cancellano | **decide Ennio.** Il documento tiene testamenti, ricette e letture con l'argomento «l'ha scritto lei», ma non nomina questi due. Oggi sono di prova: fra un anno no |
| **C** | **Gli errori didattici promossi** (`gs_errore_didattico` con `gs_stato = 'promosso'`) — materiale didattico scelto dal titolare, pubblicato | si cancellano tutti | **tenere il tipo intero.** Tenere solo i promossi vuol dire un caso particolare in più; i non promossi si cancellano a mano in cinque minuti |
| **D** | **I piatti in via d'estinzione restano adottati.** `gs_piatto` è tenuto (giusto: è catalogo) ma si porta dietro `gs_custode_tipo`, `gs_custode_id`, `gs_custode_team` | i piatti restano adottati da sfogline che non hanno più niente, e a chiunque provi ad adottarli il sito risponde «Questo piatto ha già una custode» | **svuotarli, come i voti dei sondaggi.** È lo stesso identico caso che il documento ha già deciso una volta: il contenuto resta, lo stato di gioco che ci sta attaccato si azzera |

Se Ennio approva **D**, il codice va nel punto 3 di `gs_reset_esegui()`, accanto ai sondaggi:

```php
	// 3d — i piatti restano (sono catalogo), ma il custode è stato di gioco:
	// senza svuotarlo i piatti ripartono già adottati da sfogline che non
	// hanno più niente, e nessuno può più adottarli — esattamente il motivo
	// per cui si svuotano i voti dentro i sondaggi.
	$piatti_liberati = 0;
	if ( post_type_exists( 'gs_piatto' ) ) {
		foreach ( get_posts( array( 'post_type' => 'gs_piatto', 'post_status' => $stati_da_cancellare, 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $pid ) {
			if ( ! get_post_meta( $pid, 'gs_custode_tipo', true ) ) { continue; }
			delete_post_meta( $pid, 'gs_custode_tipo' );
			delete_post_meta( $pid, 'gs_custode_id' );
			delete_post_meta( $pid, 'gs_custode_team' );
			$piatti_liberati++;
		}
	}
```

Ci sono altre tre cose che restano attaccate ai contenuti tenuti: le risposte al quiz sotto le
lezioni (`gs_risposte`, `gs_assegnazioni`), i compiti e i diplomi dei corsi dell'Area Pro, i
commenti sotto le letture (mentre il contatore di chi li ha scritti torna a zero). Nessuna
delle tre blocca il gioco nuovo — restano solo in vista. **Segnalale a Ennio e non toccarle.**

---

## PARTE 4 — Il controllo che rende inutile ricordarsi tutto questo

Fallo. È la parte che vale più di tutte le altre messe insieme.

Le tre cose gravi qui sopra sono tutte dello stesso tipo: **roba aggiunta al plugin dopo, che
nessuno ha ricollegato all'elenco del Reset.** Il calendario dei corsi è arrivato dopo;
l'archivio delle sfogline nel Cestino è di dieci giorni fa. Nessuna prova le prende, perché non
c'è niente che non funzioni.

La regola del documento — elenco di cose da tenere, mai di cose da cancellare — è giusta per
**decidere**. Per **accorgersene** serve l'opposto: un secondo elenco, che non decide niente e
serve solo a far comparire quello che nessuno ha ancora guardato.

```php
/**
 * I tipi gs_ che il Reset cancella APPOSTA. Non serve al Reset — che decide
 * per differenza, ed è giusto così: un tipo nuovo entra da solo. Serve a chi
 * legge l'anteprima: ogni tipo che non è né qui né in
 * gs_reset_tipi_da_tenere() è un tipo che nessuno ha ancora classificato, e
 * l'anteprima lo segnala invece di lasciarlo passare in silenzio.
 *
 * Chi aggiunge un tipo nuovo al plugin non deve ricordarsi del Reset: deve
 * trovarsi una riga rossa nell'anteprima che glielo ricorda.
 */
function gs_reset_tipi_da_cancellare_voluti() {
	return array(
		'gs_sfoglia', 'gs_diario', 'gs_consiglio', 'gs_misura', 'gs_giuria',
		'gs_messaggio', 'gs_msg_interno', 'gs_aiuto', 'gs_augurio',
		'gs_abbinamento', 'gs_tavolo', 'gs_voce', 'gs_errore_didattico',
	);
}

/** I tipi che il Reset cancellerebbe senza che nessuno l'abbia mai scritto da nessuna parte. */
function gs_reset_tipi_non_classificati() {
	return array_values( array_diff( gs_reset_tipi_da_cancellare(), gs_reset_tipi_da_cancellare_voluti() ) );
}
```

Aggiungi `'non_classificati' => gs_reset_tipi_non_classificati(),` all'anteprima, e nel JS,
**in cima** al risultato (prima di tutto il resto, non in fondo):

```js
		if ( d.non_classificati && d.non_classificati.length ) {
			out += '<p style="color:#b03a2e"><strong>Da guardare prima di procedere:</strong> ';
			out += 'questi tipi di contenuto verrebbero cancellati, ma nessuno li ha mai classificati — ';
			out += 'sono arrivati nel plugin dopo l\'ultima volta che si è guardato questo elenco: ';
			out += d.non_classificati.map( gsEsc ).join( ', ' ) + '</p>';
		}
```

Se esiste `prova.sh` e ha un posto dove appoggiarsi, mettici lo stesso controllo, in modo che
fallisca prima ancora di aprire il pannello. Se non ce l'ha, l'anteprima basta: è comunque il
posto dove qualcuno guarda prima di premere.

---

## La consegna

Come sempre, in quest'ordine:

1. `php -l` su ogni file toccato, graffe bilanciate in JS.
2. Versione **3.297.0** nei tre punti: intestazione di `gaming-sfogline.php`, `GS_VERSION`,
   `Stable tag` in `readme.txt`.
3. Voce di changelog che dica **cosa mancava e cosa sarebbe successo**, non «corretti alcuni
   bug». Le tre righe che contano:
   - la scatola del Cestino: dentro una sola chiave c'erano abbonamento, scadenza, token,
     Vetrina e sconto di ogni sfoglina sospesa o rifiutata, e non comparivano nell'anteprima;
   - il calendario dei corsi e le prenotazioni, con gli acconti già versati;
   - i quattro AJAX del pannello aperti anche ai collaboratori mentre il pannello è del
     titolare soltanto.
4. Sincronizza su guru2.

---

## Come si prova, senza eseguire il Reset

L'anteprima non cancella niente: si può premere quante volte si vuole. Prova queste cinque, con
dati veri, non a mente:

1. Metti una sfoglina di prova nel Cestino (`sospesa`), apri l'elenco del Cestino perché
   l'archiviazione parta, poi **apri l'anteprima**: deve comparire nella tabella nuova, con
   l'abbonamento, la scadenza e i token giusti — quelli che aveva prima di finire nel Cestino.
2. Sulla stessa sfoglina, guarda dentro `gs_archivio_gaming` prima e dopo un Reset **su
   guru2**: dentro devono restare solo le chiavi dell'elenco. Poi ripristinala dal Cestino e
   controlla che si ritrovi abbonamento e token, e **non** i punti di prima.
3. Anteprima: `gs_corso_cal` e `gs_prenotazione` devono comparire fra i contenuti che
   **restano**, non fra quelli da cancellare. Stessa cosa per Guide Stagionali e Ingredienti
   Segreti.
4. Da un utente collaboratore (`gs_manage_gaming` ma non `manage_options`): il pannello non si
   vede, e una chiamata diretta a `gs_reset_esegui` deve rispondere «Permesso negato».
5. Registra un tipo `gs_` finto per un minuto e riapri l'anteprima: deve comparire la riga
   rossa dei non classificati. Poi toglilo.

---

## Una cosa da non fare

**Non eseguire il Reset.** Nemmeno su guru2 senza dirlo, nemmeno «per provare che funziona».
La prova a vuoto la legge Ennio, e il secondo pulsante lo preme lui, dopo il backup — è scritto
nel documento e non è cambiato.

E non cancellare nessun utente. Il Reset azzera il gioco. Le persone le decide Ennio, una alla
volta.
