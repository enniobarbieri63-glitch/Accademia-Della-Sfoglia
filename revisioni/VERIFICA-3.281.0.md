# 3.281.0 verificata — il bug del tema è chiuso bene. Due cose nell'helper, tre sui lucchetti, e **ho trovato la causa dello sfarfallio**.

Letto il codice delle tre versioni insieme (3.279, 3.280, 3.281), diff completo contro la
3.278.0. `php -l` e `node --check` puliti.

**Tutto quello che avevo chiesto nella verifica della 3.278.0 è stato fatto, e bene.** Il
bug del tema è la scoperta più importante di tutto il progetto finora, ed è chiusa in modo
pulito. Sotto ci sono cinque punti da sistemare, nessuno grave, e in fondo una cosa che
serve subito: **so perché sfarfalla**, e non c'è bisogno di indagare.

---

## Prima: la prova di corsa, che è la cosa che conta di più

Nel changelog della 3.280:

> *«Provato stavolta con una vera prova di corsa (due processi paralleli distinti, non una
> simulazione in sequenza): risultato corretto su tutti i membri.»*

**Questa è la differenza fra un controllo e una prova**, ed è quella che ha reso vera la
correzione invece che plausibile. Non c'è altro modo di verificare una corsa, e da qui in
avanti vale per qualunque cosa tocchi punti, saldi o contatori.

E la diagnosi del bug del tema è fatta come si deve: il commento in `helpers.php:697-716`
non dice «il tema interferisce», dice **quale funzione**
(`the_newspaper_post_author_archive()`), **da quale aggancio** (`pre_get_posts`), **e
perché scatta** — `WP_Query` segna `is_author = true` appena vede un parametro `author`, e
il tema controlla proprio quel segnale, pensato per le pagine archivio. **Questa è la
causa, non il sintomo**, ed è per questo che la correzione funziona in tutti i punti invece
che in quelli provati.

**Verificato da me:** undici chiamate convertite in dieci file, più le due già sistemate
diversamente nella 3.277.0 (`gs_art_owner_post`, `gs_scu_owner_post`) — **nessuna query con
`author` è rimasta fuori.** Ho ricontrollato con grep tutto il plugin.

---

# A · Due cose da correggere nell'helper

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

## A2 · MEDIO — Se il tema cambia, la correzione sparisce senza dirlo

Il nome della funzione del tema è scritto a mano. Se Ennio cambia tema, se il tema la
rinomina in un aggiornamento, o se un domani un altro plugin fa la stessa cosa,
`has_action()` restituisce `false`, l'helper diventa una `get_posts()` normale, e **tutto
torna rotto esattamente come prima — in silenzio.**

Questo bug è stato invisibile per settimane in produzione. **Non deve poter tornare
invisibile.**

### Correzione: una riga in Diagnostica

`includes/diagnostica.php` controlla già cron, permalink, pagine, cartella uploads,
ZipArchive, GD, mbstring, ffmpeg. Aggiungere:

```php
	// Il bug del tema sulle ricerche "per autore" (vedi gs_get_posts_by_author
	// in helpers.php) è stato invisibile per settimane: se un giorno il tema
	// cambia o rinomina quella funzione, la correzione smette di servire
	// senza che nessuno se ne accorga. Questa riga lo rende visibile.
	$agg = function_exists( 'has_action' ) ? has_action( 'pre_get_posts', 'the_newspaper_post_author_archive' ) : false;
	$righe[] = array(
		'nome'  => 'Correzione ricerche "per autore" (tema)',
		'ok'    => ( false !== $agg ),
		'testo' => ( false !== $agg )
			? 'Attiva: l\'aggancio del tema è presente (priorità ' . (int) $agg . ') e viene aggirato durante le ricerche del plugin.'
			: 'ATTENZIONE: l\'aggancio del tema non è più presente. O il tema è cambiato (e allora questa correzione non serve più), oppure la funzione è stata rinominata e la correzione NON sta più funzionando. Da controllare.',
	);
```

**Adatta i nomi dei campi a come sono scritte le altre righe di `diagnostica.php`** — non
copiarlo alla lettera senza guardare.

---

# B · Tre dettagli sui lucchetti

I lucchetti di L1 e L2 sono scritti bene: ho controllato che il rilascio ci sia su **tutte**
le uscite (sono tre in `gs_ajax_lezione_segna_vista`, e la prima — quando il lucchetto non
si prende — giustamente non rilascia niente). Restano tre cose.

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

## B2 · BASSO — Due lucchetti annidati: una verifica da fare una volta sola

`gs_ajax_lezione_segna_vista()` prende `gs_lez_…`, poi chiama `do_action( 'gs_lezione_vista' )`,
che porta a `gs_percorso_squadra_assegna_se_completo()`, che prende `gs_perc_sq_…`. **Due
lucchetti tenuti insieme, sulla stessa connessione.**

Non c'è rischio di stallo — l'ordine è sempre lo stesso, prima la lezione poi la squadra —
ma c'è una cosa da sapere: **fino a MySQL 5.7.5 una connessione poteva tenere un lucchetto
solo, e prenderne un secondo rilasciava il primo automaticamente.** Su MySQL 5.7.5+ e su
MariaDB 10.0.2+ si possono tenere insieme, e sono le versioni che SiteGround usa da anni.

**Va confermato una volta e poi non ci si pensa più.** In Diagnostica, o con una query
qualsiasi:

```sql
SELECT VERSION();
```

Se esce MySQL ≥ 5.7.5 o MariaDB ≥ 10.0.2, i due lucchetti annidati vanno bene così.
**Riporta il numero**, non «è aggiornato».

## B3 · MEDIO — Le email partono mentre il lucchetto è ancora chiuso

**File:** `includes/percorsi-lezioni.php:216-238` — VERIFICATO

Il lucchetto sta **attorno al giro su tutti i membri**, e dentro quel giro
`gs_percorso_squadra_badge_assegna()` manda, per ogni membro: un aeroplanino e **una
email** (`wp_mail`).

Con una squadra da sei, sono **sei email spedite con il lucchetto ancora chiuso** — e sopra
c'è anche il lucchetto della lezione. Se il server di posta è lento (due secondi a
messaggio non è raro), il lucchetto resta preso per oltre dieci secondi.

Non si perde niente: le altre richieste aspettano cinque secondi, poi escono con `false`,
e quella che ha il lucchetto fa comunque il lavoro per tutti. **Ma è una lentezza che nasce
da una scelta evitabile**, e se un giorno la spedizione si blocca del tutto il lucchetto
resta chiuso fino alla fine della richiesta.

### Correzione

Separare le due cose: **dentro** il lucchetto si scrivono badge, contrassegni e punti;
**fuori** si mandano avvisi ed email.

```php
	$assegnati = array();
	foreach ( gs_squadra_membri( $team ) as $mid ) {
		if ( gs_percorso_squadra_badge_assegna( $pid, $mid, false ) ) {   // false = non avvisare adesso
			$assegnati[] = $mid;
		}
	}
	$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );

	// Avvisi ed email FUORI dal lucchetto: con sei membri sono sei wp_mail(),
	// e tenere chiuso un lucchetto di MySQL mentre si aspetta il server di
	// posta blocca le altre socie per niente. Quello che doveva essere
	// protetto — badge, contrassegni, punti — è già stato scritto.
	foreach ( $assegnati as $mid ) {
		gs_percorso_squadra_avvisa( $pid, $mid );
	}
	return ! empty( $assegnati );
```

con un terzo parametro `$avvisa = true` su `gs_percorso_squadra_badge_assegna()` (così le
altre eventuali chiamate non cambiano comportamento) e la parte degli avvisi spostata in
`gs_percorso_squadra_avvisa()`.

**Priorità media.** Non perde niente; rende il sistema più lento proprio nel momento in cui
una squadra festeggia.

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

# D · Due cose per Ennio

## D1 · Il limite di una foto al giorno non ha funzionato per settimane: i punti vanno guardati

Il changelog della 3.281 dice, correttamente:

> *«il limite di una foto al giorno nel Tavolo di Oggi non veniva rispettato»*

Cioè: **fino a ieri, chi caricava più foto in un giorno prendeva +5 punti ogni volta.** È
la conseguenza che avevo indicato leggendo `gs_tavolo_di_oggi()`, ed era vera.

Il gaming non è ancora partito, quindi non ci sono classifiche da rifare e non c'è nessun
Buono Sfoglia assegnato per sbaglio. **Ma i punti accumulati restano scritti**, e il 1°
novembre la prima chiusura vera leggerà i totali di ottobre.

**Da fare prima del lancio, non dopo:** contare, per ogni sfoglina, quante voci ci sono
nel suo `gs_points_log` con la motivazione *«Il Tavolo di Lavoro: foto del giorno»*, e
confrontarle con i giorni davvero trascorsi. Se qualcuna ne ha molte di più, sono punti
arrivati da un difetto, non dal gioco.

**Non correggere niente di tua iniziativa**: porta i numeri a Ennio e decide lui. È
possibile che la risposta giusta sia «lascia stare, tanto sono punti di prova» — ma va
decisa, non scoperta a novembre.

## D2 · guru2 e il sito vero non hanno il tema configurato allo stesso modo

Nel changelog della 3.279 c'è scritto, con onestà, una cosa importante:

> *«non sono riuscito a provare lo scroll dal vivo su guru2 perché quel sito ha le opzioni
> del tema non configurate e gli avvisi PHP che ne escono rompono la struttura
> dell'intestazione»*

**Questa frase vale più di quanto sembri.** Vuol dire che guru2 e il sito vero **non sono la
stessa cosa dal lato del tema** — ed è proprio il tema che ha causato il difetto più grosso
di tutto il progetto.

Questa volta è andata bene: il difetto si riproduceva anche su guru2. Ma d'ora in poi,
**ogni prova che riguarda il tema — l'intestazione, il nastro, lo scroll, le query per
autore — vale solo fino a prova contraria**, e va detto quando si scrive «verificato».

**Vale la pena chiedere a Ennio se può esportare le opzioni del tema dal sito vero e
importarle su guru2.** Nel tema Newspaper è una funzione che esiste (Pannello del tema →
Import/Export). Sarebbe mezz'ora di lavoro per lui, e renderebbe affidabili tutte le prove
future su tutto quello che tocca l'aspetto.

---

# Riepilogo

| | Stato |
|---|---|
| **Bug del tema — diagnosi** | ✅ causa vera, non sintomo |
| **Bug del tema — 13 punti corretti** | ✅ verificato: nessuna query con `author` è rimasta fuori |
| **L1 · lucchetto + riparazione del vicolo cieco** | ✅ tutte e tre le uscite rilasciano |
| **L2 · lucchetto sul Percorso di Squadra** | ✅ **e provato con una vera prova di corsa** |
| A1 · priorità di `remove_action` | ⚠ da correggere |
| A2 · la correzione può sparire in silenzio | ⚠ una riga in Diagnostica |
| B1 · nome della squadra nel lucchetto | ⚠ `md5()` |
| B2 · due lucchetti annidati | ❓ una `SELECT VERSION()` e si chiude |
| B3 · email dentro il lucchetto | ⚠ spostarle fuori |
| **C · sfarfallio** | 🔴 **causa trovata, correzione scritta** |
| D1 · punti del Tavolo da contare | ❓ prima del lancio |
| D2 · guru2 ≠ sito vero sul tema | ❓ chiedere a Ennio l'export delle opzioni |

**In ordine:** **C** (è quello che Ennio vede), poi **A1 + B1** (due righe ciascuno, chiudono
due modi silenziosi di rompersi), poi B3, poi le domande B2/D1/D2.

**Niente della 3.281.0 va rifatto.**

---

## Cosa leggo adesso

`voting.php` e `giuria-turno.php`. Erano il prossimo passo prima di questo giro, e restano
il prossimo: sono i primi due dei diciassette file che toccano punti o badge.
