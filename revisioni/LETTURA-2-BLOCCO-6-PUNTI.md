# Seconda lettura — blocco 6: sondaggi, madrina, indovina, piatti, missioni, conversazioni

**Per Claude Code Ennio 2 — 26/08/2026 — versione letta: 3.284.4**

Nove file letti riga per riga: `sondaggi.php`, `madrina.php`, `indovina.php`, `piatti-estinzione.php`, `missions.php`, `badges.php`, `forms.php`, `mappa-squadre.php`, `conversazioni.php` (la parte dei token).

**Otto difetti nuovi, più uno sistematico che tocca 31 punti del plugin** — e che si incastra con il lavoro dei trenta giorni che ti ho appena mandato.

Sono ordinati per quanto fanno male, non per file.

---

## L — Il buco sistematico: un account gratuito che gioca al gaming

**Questo leggilo prima degli altri, perché cambia anche il documento dei trenta giorni.**

In `letture.php:20-28` c'è l'account leggero, quello *«solo per commentare»*: gratuito, immediato, **senza approvazione**, `gs_status = 'lettore'`. La sua descrizione (`letture.php:166`) dice:

> *«senza quota associativa, **senza accesso al resto del gaming**»*

Il controllo che lo dovrebbe tenere fuori è `gs_is_approved()` (`helpers.php:646`), che su `'lettore'` risponde `false`. Funziona. Il problema è **quante volte viene chiamato**:

| Controllo | Quanti handler AJAX |
|---|---|
| `if ( ! $uid \|\| ! gs_is_approved( $uid ) )` — quello giusto | **7** |
| `if ( ! $uid )` — solo "sei collegato?" | **31** |

Trentuno handler chiedono soltanto di essere collegati. Un account «solo per commentare» — che chiunque si fa in trenta secondi, senza che nessuno approvi niente — passa attraverso tutti e trentuno.

I file:

```
biografia (5), piatti-estinzione (2), matterello-parlante (2), lezioni-video (2),
giuria-turno (2), aiuto (2), testimonianze, testamento-sfoglina, tavolo,
shortcodes, sfogline-extra, sfoglia-misurata, sfoglia-insegna, scuole-cucina,
ricettario, promemoria, percorso-staffetta, messaggi, indovina, compleanni,
calendario, artigiani
```

Non tutti danno punti, ma **almeno tre sì**, e li ho verificati uno per uno:

- `gs_ajax_tavolo_invia` (`tavolo.php:185`) — carica una foto nel Tavolo di Lavoro e prende i punti;
- `gs_ajax_indovina_rispondi` (`indovina.php:136`) — risponde al quiz del giorno, 5 punti, tutti i giorni;
- `gs_ajax_piatto_adotta` (`piatti-estinzione.php:164`) — adotta un piatto in via di estinzione, **20 punti**.

Gli altri non danno punti ma lasciano **scrivere contenuti**: ricette nel ricettario, biografie per la Vetrina, testimonianze, registrazioni del Matterello, testamenti.

### Perché questo cambia il documento dei trenta giorni

Nel documento del cancello ti ho scritto che `gs_add_points()` è la porta unica dei punti, e lo è. Ma **i contenuti non passano di lì.** Una sfoglina congelata, con le pagine chiuse, può ancora scrivere una ricetta o una biografia se la richiesta AJAX parte da una scheda rimasta aperta. Il congelamento sarebbe a metà.

I due problemi si chiudono con **la stessa riga**. In `helpers.php`, accanto a `gs_is_approved()`:

```php
/**
 * Questa persona può PARTECIPARE al gaming adesso? Approvata e non congelata.
 *
 * È la porta unica lato AJAX, gemella di gs_gate_riservato() lato pagina:
 * quella decide cosa si vede, questa decide cosa si può scrivere. Nasce da
 * due cose trovate insieme il 26/08/2026 — l'account leggero "solo per
 * commentare" che passava in 31 handler su 38, e il congelamento a trenta
 * giorni che senza questa riga si sarebbe fermato alle pagine.
 */
function gs_puo_partecipare( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid || ! gs_is_approved( $uid ) ) { return false; }
	if ( function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( $uid ) ) { return false; }
	return true;
}
```

E nei 31 punti, sempre la stessa sostituzione:

```php
// prima
if ( ! $uid ) { wp_send_json_error( array( 'message' => 'Devi accedere.' ) ); }
// dopo
if ( ! $uid || ! gs_puo_partecipare( $uid ) ) {
	wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) );
}
```

**Quattro eccezioni, e sono vere eccezioni** — guardale prima di sostituire:

- **`letture.php`** (i commenti) — è il posto dove il lettore *deve* poter scrivere. Usa già `gs_lettore_puo_commentare()`. Non toccarlo.
- **`aiuto.php`** (2 punti) — «Aiuto e Suggerimenti» è come una sfoglina congelata chiede di rientrare. **Deve restare aperto**, o il cancello si chiude dall'interno.
- **`calendario.php`** — chi ha pagato un corso deve poterlo gestire anche a gaming chiuso.
- **`biografia.php`** (5 punti) — la Vetrina si paga a parte (49 €). Se ha pagato, deve poter modificare la sua biografia anche da congelata. Decidi tu e scrivi perché.

Su queste quattro il controllo giusto è `gs_is_approved()` da solo, senza il congelamento — tranne `letture.php` che resta com'è.

---

## M2 — Madrina & Allieva: punti infiniti con un clic

**`madrina.php:200-218`. È il difetto peggiore che ho trovato in questo giro.**

```php
foreach ( $missioni as &$m ) {
	if ( $m['id'] === $id ) {
		$m['fatta'] = empty( $m['fatta'] );   // ← è un INTERRUTTORE
		$fatta_ora  = $m['fatta'];
	}
}
gs_abbinamento_salva_missioni( $abb_id, $missioni );

// Piccolo premio in punti per entrambe, solo quando si completa (non quando si de-seleziona).
if ( $fatta_ora && function_exists( 'gs_add_points' ) ) {
	gs_add_points( $madrina, 5, ... );
	gs_add_points( $allieva, 5, ... );
}
```

Il commento dice *«solo quando si completa»*, e nel codice è vero: quando si toglie la spunta non dà niente. **Ma quando la si rimette, li ridà.**

Spunta → +5 e +5. Togli → 0. Rispunta → **+5 e +5 di nuovo.**

Cinquanta clic sono 250 punti alla madrina e 250 all'allieva, senza fare nulla. E non serve nemmeno la malafede: una che cambia idea due volte su una mini-missione si ritrova punti che non ha guadagnato, e non lo sa.

Il buco è che da nessuna parte è scritto **che quella missione è già stata pagata**. `fatta` dice com'è adesso, non cosa è già successo. Ovunque altro nel plugin quel fatto c'è: `gs_badge_dato_*`, `gs_buono_mese_*`, `gs_mail_benv_*`, e `gs_unlock_badge()` che è il modello.

**La correzione**, dentro il `foreach`:

```php
foreach ( $missioni as &$m ) {
	if ( $m['id'] !== $id ) { continue; }
	$m['fatta'] = empty( $m['fatta'] );
	// I punti si pagano UNA VOLTA SOLA per missione, la prima volta che
	// viene spuntata. 'fatta' è un interruttore e dice com'è adesso; 'pagata'
	// è un contrassegno e dice cosa è già successo — non si toglie mai più.
	// Senza questa distinzione, spuntare e ri-spuntare la stessa missione
	// dava 5 punti a testa ogni volta, all'infinito (trovato 26/08/2026).
	$fatta_ora = $m['fatta'] && empty( $m['pagata'] );
	if ( $fatta_ora ) { $m['pagata'] = true; }
	break;
}
unset( $m );
gs_abbinamento_salva_missioni( $abb_id, $missioni );   // il contrassegno è già dentro, e va giù PRIMA dei punti
```

Le missioni già spuntate oggi su guru2 non hanno `pagata`: la prima volta che qualcuno le ri-spunta prendono 5 punti una volta ancora, e poi mai più. **Con il reset in arrivo non ha nessuna importanza** — ma sappilo prima di installare, invece di scoprirlo dopo.

---

## C6 — Il token rimborsato due volte

**`conversazioni.php:762-765`. Questo è il contrassegno-dopo-gli-effetti, sui soldi.**

```php
$nuovo = gs_token_movimento( $sfoglina, (int) $m['token_costo'], 'rimborso', ... );  // 1. il token torna
$msgs[ $trovato ]['rimborsato'] = true;
update_post_meta( $cid, 'gs_msgs', $msgs );                                          // 2. il contrassegno
```

Il controllo `if ( ! empty( $m['rimborsato'] ) )` (riga 752) legge da `gs_conv_msgs( $cid )`. Fra quella lettura e la scrittura di riga 765 c'è tutto il rimborso.

Due clic su «↩️ Rimborsa token» — un doppio clic, una connessione lenta, oppure l'esperto e Ennio che ci arrivano insieme — e **la sfoglina si ritrova due token per una domanda sola**. Il token si compra con un bonifico: sono soldi veri, non punti.

È esattamente la regola nata dalla chiusura di luglio: **il contrassegno prima degli effetti.** Qui è al contrario.

E c'è il secondo pezzo: `update_post_meta( $cid, 'gs_msgs', $msgs )` riscrive **tutto** l'array dei messaggi letto in cima alla funzione. Se nel frattempo qualcuno scrive in quella conversazione, quel messaggio sparisce.

**La correzione** — prenota il diritto, poi muovi i soldi, tutto dentro un lucchetto:

```php
global $wpdb;
$lucchetto = 'gs_conv_' . (int) $cid;
$wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lucchetto ) );
try {
	// Rileggi DENTRO il lucchetto: la lettura di prima è di un altro momento.
	$msgs = gs_conv_msgs( $cid );
	$trovato = -1;
	foreach ( $msgs as $i => $m2 ) { if ( $m2['id'] === $mid ) { $trovato = $i; break; } }
	if ( $trovato < 0 || ! empty( $msgs[ $trovato ]['rimborsato'] ) ) {
		wp_send_json_error( array( 'message' => 'Già rimborsata.' ) );
	}
	// Contrassegno PRIMA del movimento: se il token si muove e poi qualcosa
	// va storto, il peggio è un rimborso perso — non un rimborso doppio.
	$msgs[ $trovato ]['rimborsato'] = true;
	update_post_meta( $cid, 'gs_msgs', $msgs );
	$nuovo = gs_token_movimento( $sfoglina, (int) $msgs[ $trovato ]['token_costo'], 'rimborso', '…' );
} finally {
	$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lucchetto ) );
}
```

Il `try/finally` non è ornamento: senza, un `wp_send_json_error()` in mezzo esce dalla funzione lasciando il lucchetto chiuso, e la conversazione resta bloccata fino alla fine della connessione MySQL. Stesso schema di `gs_get_posts_by_author()` (`helpers.php:722`).

---

## PE1 — Due custodi per lo stesso piatto

**`piatti-estinzione.php:127-142`**, dentro `gs_piatto_adotta_uid()`:

```php
if ( get_post_meta( $pid, 'gs_custode_tipo', true ) ) { return 'Questo piatto ha già una custode.'; }
...
update_post_meta( $pid, 'gs_custode_tipo', 'sfoglina' );
update_post_meta( $pid, 'gs_custode_id', $uid );
gs_add_points( $uid, 20, ... );
```

Un piatto ha **un posto solo**. Due sfogline che cliccano «Adotta» nello stesso secondo: leggono tutte e due il posto vuoto, passano tutte e due il controllo, scrivono tutte e due. La seconda cancella la prima.

Alla prima il sito ha detto *«Sei ora la custode di "Tagliatelle di San Nicola"!»*, le ha dato 20 punti, e **non è la custode**. Lo scopre quando torna sulla pagina e vede un altro nome.

È il caso del corso con tre posti che ne accettava quattro, su una risorsa che di posti ne ha uno.

**La correzione è più semplice del lucchetto**, ed è già usata nel punto meglio protetto del plugin (`gs_ajax_vota()`): prenotare il posto con `$unique = true` e lasciare che sia MySQL a rifiutare il secondo.

```php
// add_post_meta con $unique = true: MySQL rifiuta il secondo inserimento
// sulla stessa chiave e la funzione ritorna false. Non c'è più una finestra
// fra il controllo e la scrittura, perché il controllo E la scrittura sono
// la stessa operazione — è lo schema di gs_ajax_vota(), il posto meglio
// protetto del plugin. Funziona anche dopo una rinuncia, perché
// gs_piatto_libera_uid() cancella davvero il meta (delete_post_meta).
$tipo = ( 'squadra' === $come ) ? 'squadra' : 'sfoglina';
if ( ! add_post_meta( $pid, 'gs_custode_tipo', $tipo, true ) ) {
	return array( 'ok' => false, 'message' => 'Questo piatto ha già una custode.' );
}
```

e da lì in poi il resto come adesso. **Attenzione all'ordine**: per la squadra, `gs_get_user_team( $uid )` va controllato **prima** di prenotare, o una senza squadra si prende il piatto e poi fallisce, lasciandolo occupato da nessuno.

---

## S1 — Sondaggi: i voti che spariscono

**`sondaggi.php:225-240`**

```php
$voti = get_post_meta( $id, 'gs_sond_voti', true );   // ← array di TUTTE
if ( isset( $voti[ $uid ] ) ) { wp_send_json_error( 'Hai già votato' ); }
...
$voti[ $uid ] = $proposta_id;
update_post_meta( $id, 'gs_sond_voti', $voti );
gs_add_points( $uid, 5, 'Voto dato in un sondaggio' );
```

Quell'array non è suo: è di tutte. Due sfogline votano nello stesso momento:

| | Anna | Bruna |
|---|---|---|
| legge | `[]` | `[]` |
| scrive | `[Anna => 1]` | `[Bruna => 2]` |

L'ultima scrittura vince. **Il voto di Anna non c'è più** — ma Anna ha letto *«Voto registrato, grazie!»*, ha preso i 5 punti, e sulla pagina il suo voto non compare. Se ricarica e rivota, ne prende altri 5.

È lo stesso identico errore che il test di carico aveva scoperto sul totale dei punti il 14/08. Lì fu risolto con un `UPDATE` atomico; qui non si può, perché è un array serializzato: serve il lucchetto.

E si vede solo quando **molte votano insieme** — cioè esattamente il giorno in cui Ennio manda un messaggio a tutte con «vota il sondaggio». Con due sfogline alla volta non succede mai.

**La correzione**: `GET_LOCK` su `'gs_sond_' . $id`, la rilettura dentro il lucchetto, `try/finally`. Stessa forma di C6 qui sopra.

## S2 — Sondaggi: la proposta persa, con l'id sbagliato

**`sondaggi.php:270-280`**, stessa forma su `gs_sond_proposte`, con un danno in più:

```php
$proposte[] = array( 'id' => gs_sondaggio_prossimo_id_proposta( $proposte ), 'testo' => $testo, 'autore' => $uid );
```

`gs_sondaggio_prossimo_id_proposta()` (riga 103) è «il più alto che c'è, più uno». Due proposte insieme: **entrambe si assegnano lo stesso id**, una delle due sparisce nella riscrittura, e chi l'ha scritta prende comunque i suoi 10 punti per una proposta che non esiste.

Se invece per una qualsiasi ragione sopravvivessero tutte e due con lo stesso id, `gs_sondaggio_conteggio()` (riga 84) le fonderebbe in un contatore solo: due proposte diverse, un risultato solo. **Il sondaggio darebbe un risultato sbagliato senza che nulla sembri rotto.**

Stesso lucchetto di S1, sullo stesso sondaggio: usa la **stessa chiave** `'gs_sond_' . $id` per tutti e due gli handler, così voto e proposta non si pestano i piedi fra loro.

---

## I1 — Indovina la Sfoglia: due risposte, due volte i punti

**`indovina.php:145-160`**

```php
if ( gs_indovina_stato_oggi( $uid ) ) { wp_send_json_error( 'Hai già risposto' ); }
...
update_user_meta( $uid, 'gs_indovina', array( 'data' => ..., 'corretta' => $corretta ) );
if ( $corretta ) { gs_add_points( $uid, 5, ... ); }
```

Il contrassegno è **prima** dei punti: quella parte è giusta. Ma il controllo legge da `get_user_meta`, e i meta utente li carica WordPress **una volta sola a inizio richiesta**: la seconda richiesta non vede la scrittura della prima nemmeno se è già finita. Due clic ravvicinati sul pulsante «Rispondi» danno 5 punti due volte.

Sono 5 punti al giorno: **il danno è piccolo, la forma è la stessa di tutte le altre**. Va corretta con `GET_LOCK` su `'gs_indovina_' . $uid` più `wp_cache_delete( $uid, 'user_meta' )` dentro il lucchetto — la cancellazione della cache non è un di più, senza quella il lucchetto non serve a niente perché la rilettura risponde comunque dalla fotografia di inizio richiesta.

## I2 — La domanda di oggi cambia sotto i piedi

**`indovina.php:79-81`**

```php
$giorno = (int) floor( current_time( 'timestamp' ) / DAY_IN_SECONDS );
$indice = $giorno % count( $domande );
return $domande[ $indice ];
```

La domanda del giorno è la posizione `giorno % quante ce ne sono`. **Se Ennio aggiunge o elimina una domanda a metà giornata, il resto scorre e la domanda di oggi diventa un'altra**, per tutte, all'istante.

Chi ha già risposto la mattina se ne accorge subito, perché `gs_sc_indovina()` (riga 120) mostra il riquadro «hai già risposto» usando la domanda di **oggi** invece di quella a cui ha risposto davvero. Legge così:

> *«❌ Non era esatta. La risposta era: **Farina 00**. Hai risposto: «tre uova»»*

dove «Farina 00» è la risposta di un'altra domanda. Sembra un errore del sistema, e in un certo senso lo è.

Il dato giusto **c'è già**: `$stato['domanda_id']` viene salvato (riga 154) e non viene mai letto. La correzione è di poche righe: quando `$stato` esiste, cerca la domanda per `domanda_id` nell'elenco attivo **e nel cestino**, e mostra quella; se non la trovi più, di' *«la domanda di stamattina è stata rimossa»* invece di mostrarne una a caso.

Non è urgente: si vede solo se Ennio tocca l'elenco a giornata iniziata. Ma è un'ora di lavoro e toglie di mezzo una segnalazione che sembrerebbe un bug grave e non lo è.

---

## MI1 — Le missioni si contano male quando le cose capitano insieme

**`missions.php:36-53`**, `gs_advance_mission()`: legge `$state`, controlla, incrementa, assegna, scrive. La stessa forma di tutti gli altri, su `gs_missions`.

Due azioni ravvicinate della stessa sfoglina — due voti dati di seguito, o un voto e un commento — leggono lo stesso `$state`, incrementano tutte e due da 2 a 3, arrivano tutte e due all'obiettivo e **assegnano tutte e due i punti della missione**. Poi la seconda scrittura copre la prima, e il contatore resta a 3 invece che a 4.

C'è anche un secondo pezzo, più piccolo: `$state['done'][]` viene messo in memoria **prima** di `gs_add_points()`, ma su disco ci finisce **dopo** (`update_user_meta` a riga 53). Se qualcosa muore dentro `gs_add_points`, i punti restano e il contrassegno no: alla prossima azione la missione si ripaga. È il contrassegno-dopo-gli-effetti in una forma più difficile da vedere, perché la riga *sembra* essere prima.

Stessa correzione: `GET_LOCK` su `'gs_missioni_' . $user_id`, `wp_cache_delete`, rilettura dentro, `update_user_meta` del contrassegno **prima** di `gs_add_points()`.

---

## Cose che ho guardato e vanno bene

Perché tu non le rilegga:

- **`gs_unlock_badge()` (`badges.php:68`)** — resta il modello. Ritorno anticipato, contrassegno prima, punti dentro. Le venti chiamate in `badges.php` passano tutte da qui e sono a posto.
- **`gs_ajax_lezione_segna_vista` (`lezioni-video.php:868`)** — ha già il `GET_LOCK` per sfoglina+lezione, con la nota che spiega perché. È la protezione fatta bene, quella da copiare.
- **`gs_mappa_territori_verifica_vincitrice` (`mappa-squadre.php:155`)** — il contrassegno (`update_option`) è **prima** dei 50 punti: ordine giusto. Resta una piccola corsa su un premio che si dà una volta sola nella storia dell'Accademia; non vale il lucchetto, e te lo dico solo perché tu non la ritrovi da capo fra un mese pensando che sia nuova.
- **`gs_piatto_libera_uid()`** — controlla la proprietà prima di liberare, e cancella davvero i meta. Corretta.

---

## Una cosa che non è un difetto ma va detta a Ennio

**`forms.php:56`** — ogni voce di diario vale 15 punti, e **non c'è nessun tetto**. Venti voci in un pomeriggio sono 300 punti. Lo stesso vale per i consigli (`forms.php:125`, 20 punti l'uno).

Non è un errore di programmazione: è una scelta di gioco che nessuno ha mai preso davvero. Era già sulla lista come «tetto giornaliero ai punti», e a gaming spento non fa danno. **Ma il 1° ottobre parte una classifica vera con un premio vero in fondo**, e la prima che se ne accorge la vince scrivendo, non impastando.

Va deciso prima di accendere, e lo deve decidere Ennio: un tetto al giorno per categoria, oppure niente tetto e si accetta. Non è una cosa da sistemare adesso — è una cosa da non dimenticare a settembre.

---

## In che ordine

Se devi scegliere, questo è l'ordine giusto:

1. **M2** — punti infiniti, tre righe. Adesso.
2. **C6** — soldi veri, un rimborso doppio. Adesso.
3. **L** — il buco dell'account leggero, e serve comunque per i trenta giorni. **Falla insieme al documento del cancello**, non separata: è la stessa mano di lavoro e la stessa funzione nuova.
4. **PE1** — sei righe, e la forma (`add_post_meta` unico) va imparata perché torna.
5. **S1 + S2** — un lucchetto solo per tutti e due.
6. **I1, MI1** — stessa forma, poco danno, ma è la stessa che ho trovato dieci volte: se resta, torna.
7. **I2** — quando c'è tempo.

Il tetto ai punti del diario **non è tuo**: è una domanda per Ennio.

---

## Quanto manca

Con questo blocco i file che toccano i punti sono letti. **Restano quattro file più piccoli** — `traguardi.php`, `riepilogo-anno.php`, `year-prize.php`, `streak.php` — che ho già attraversato cercando i punti e i badge (nessuna sorpresa: `year-prize.php:60-70` è uno dei sette badge scritti a mano che sai già, `streak.php` l'ho trattato nel documento del congelamento). Li rileggo per intero nel blocco 7, ma non aspettarti bombe.

Poi resta la roba dei giri 3-6, che non ho mai cominciato e che **serve per l'apertura del gaming, non per settembre**: il doppio clic che brucia due livelli di corso, il tetto ai punti, e una dozzina di voci minori.
