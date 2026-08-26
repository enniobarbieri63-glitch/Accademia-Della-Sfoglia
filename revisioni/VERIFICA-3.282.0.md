# 3.282.0 verificata — tutto applicato bene. Quattro cose, e una è quella che abbiamo appena chiuso, rimasta aperta altrove.

Diff completo contro la 3.281.0. `php -l` pulito su tutti e cinque i file PHP toccati,
`node --check` pulito sul JavaScript.

**Lo sfarfallio, il pulsante condizionato, le scritte nuove, A1, B1, il Tavolo: tutto
applicato, e applicato bene.** Sotto ci sono quattro punti, nessuno grave, e uno è
semplicemente una cosa dimenticata per strada.

---

## Tre cose fatte meglio di come le avevo chieste

**1. L'elenco degli utenti in Diagnostica.** Avevo scritto «elencare senza toccare» e le
cinque esclusioni. Il codice le applica tutte e cinque **e** fa due cose che non avevo
scritto:

- `gs_art_elenco( array( 'publish', 'trash' ) )` — guarda anche le **vetrine cestinate**,
  perché una cestinata torna con un clic. Nel mio documento questa era una nota a margine
  fra le «cose minori», non un'istruzione: è stata raccolta lo stesso;
- il filtro sugli autori è fatto **in PHP e non con `'author'` nella query** — cioè ha
  applicato da sola la lezione del bug del tema a un codice nuovo, invece di ripetere
  l'errore in un posto dove nessuno l'aveva ancora segnalato. **È la differenza fra
  correggere e aver capito.**

**2. Il testo del Pannello di Controllo.** Avevo chiesto di cambiare la scritta del
pulsante. È stato riscritto anche il testo di spiegazione, che ora dice il vero — *«il
pulsante che compare da solo sulle pagine dove serve (quelle con un sito esterno
incorporato, es. "Rina Poletti" o "Lentium Notizie")»* — invece della versione vecchia, che
avrebbe promesso una cosa non più esistente. **Non l'avevo chiesto e sarebbe rimasta lì.**

**3. Il difetto che avevo segnalato prima dello zip è corretto**, e nel modo giusto:
`applica()` restituisce se ha agito, e la scritta si aggiorna solo allora.

### Una differenza dalla mia istruzione, che ho controllato: non fa differenza

Avevo scritto *«`sceltaManuale = true` va **dopo** il controllo su `applica()`»*. Nel codice
sta **prima**. **Ho verificato: è indifferente**, e vale la pena dire perché invece di
chiedere una modifica inutile.

`sceltaManuale` si mette solo a vero, mai a falso. Il primo clic non trova mai una
transizione in corso, quindi `applica()` restituisce sempre vero e le due righe vengono
eseguite entrambe. Un clic che *può* essere ignorato è per forza un clic successivo — e a
quel punto `sceltaManuale` è già vero da prima. **L'ordine non è mai osservabile. Lascia
com'è.**

---

# 1 · Il pulsante del Pannello è rimasto indietro — ed è lo stesso problema che abbiamo appena chiuso

**File:** `assets/js/gaming.js:1423-1432` (`.gs-toggle-logo-pannello`) — VERIFICATO

La scritta è stata cambiata, e va bene. Ma il gestore **non è stato toccato nel resto**, e
adesso è l'unico pezzo che non passa per il meccanismo nuovo:

```js
		var nascosto = $( 'body' ).toggleClass( 'gs-logo-hidden' ).hasClass( 'gs-logo-hidden' );
		$btn.text( nascosto ? 'Rimetti il logo' : 'Fissa il menu in alto' )…
		setTimeout( function () { $( window ).trigger( 'resize' ); }, 260 );
```

**Tre differenze, e la prima conta davvero:**

| | Il pulsante nuovo | Quello del Pannello |
|---|---|---|
| **imposta `sceltaManuale`** | sì | **no** |
| passa da `applica()` (e quindi da `inTransizione`) | sì | no |
| quando rimisura | **320 ms** | 260 ms |

## Perché la prima conta

`sceltaManuale` è la cosa che abbiamo appena aggiunto perché *«una scelta fatta a mano non
viene disfatta dallo scorrimento»*. **Il pulsante del Pannello non la imposta.**

Quindi: Ennio apre il Pannello di Controllo — che è una pagina lunga — preme **«Fissa il
menu in alto»**, poi scorre per lavorare. Appena risale di una schermata, **il menu torna
da solo**, e la sua scelta è stata disfatta.

**È esattamente il difetto che abbiamo chiuso stamattina per l'altro pulsante**, rimasto
aperto nell'unico posto dove non abbiamo guardato. E il pulsante del Pannello è quello che
Ennio usa più spesso.

## Le altre due, minori ma vanno insieme

- **Non passa da `applica()`**: se lo si preme mentre lo scorrimento automatico sta già
  facendo una transizione, la classe cambia sotto, mentre `applica()` sta per riallineare
  `ultimoScrollY` e azzerare `inTransizione` per conto suo. Non ho un modo concreto in cui
  questo rompa qualcosa, ma sono due meccanismi che scrivono la stessa cosa senza sapere
  l'uno dell'altro.
- **Rimisura a 260 ms, non 320.** Il 320 è stato scelto apposta per aspettare che la
  transizione CSS finisca: **a 260 si misura ancora a metà**, ed è il difetto che
  `gsAllineaSpazioMenu()` doveva evitare fin dall'inizio.

**Una cosa va detta a suo favore:** la scritta di questo pulsante **non può invertirsi**,
perché è ricavata dallo stato vero (`.toggleClass( … ).hasClass( … )`) e non da una
variabile calcolata prima. Il difetto che ho segnalato ieri qui non c'era.

## Correzione

Il flag deve essere leggibile da tutti e due i punti, che stanno in due parti diverse del
file. **Il modo più semplice è metterlo dove sono già tutti gli altri stati: sul `body`.**

In `gsHeaderToggleInit()`, al posto della variabile:

```js
			// Il contrassegno sta sul body e non in una variabile locale: lo
			// stesso comando esiste anche nel Pannello di Controllo
			// (.gs-toggle-logo-pannello, molto più su in questo file), e i due
			// pulsanti devono condividere la stessa scelta — altrimenti quello
			// del Pannello viene disfatto dallo scorrimento, che è esattamente
			// il difetto appena corretto per l'altro (26/08/2026).
			// (niente più "var sceltaManuale")
```

e ovunque compariva `sceltaManuale`:

- in `valutaScroll()`: `if ( document.body.classList.contains( 'gs-scelta-manuale' ) ) { … }`
- nel clic del pulsante: `document.body.classList.add( 'gs-scelta-manuale' );`
- **e nel gestore del Pannello, riga 1423**, la stessa riga più il resto:

```js
	$( document ).on( 'click', '.gs-toggle-logo-pannello', function () {
		var $btn = $( this );
		…
		var nascosto = $( 'body' ).toggleClass( 'gs-logo-hidden' ).hasClass( 'gs-logo-hidden' );
		// Stessa scelta manuale del pulsante che compare sulle pagine con un
		// sito dentro: da qui in avanti lo scorrimento non la disfa. E si
		// rimisura a 320 ms come fa applica(), non a 260: sotto quel tempo la
		// transizione CSS non è finita e si legge un'altezza a metà.
		document.body.classList.add( 'gs-scelta-manuale' );
		…
		setTimeout( function () { $( window ).trigger( 'resize' ); }, 320 );
	} );
```

**Compromesso da dichiarare:** dopo aver premuto uno dei due pulsanti, su quella pagina lo
scorrimento automatico non lavora più fino al ricaricamento. **È voluto** — è la definizione
stessa di «scelta manuale» — ma adesso vale anche nel Pannello, dove prima non valeva. Vale
la pena che Ennio lo sappia prima di provarlo, così non lo scambia per un difetto.

---

# 2 · A2 non è stato fatto

**File:** `includes/diagnostica.php` — VERIFICATO: la stringa
`the_newspaper_post_author_archive` **non compare** nel file.

In Diagnostica sono state aggiunte due cose nuove e utili (l'elenco utenti e il conto del
Tavolo), ma **non quella che avevo chiesto**: la riga che dice se l'aggancio del tema è
ancora al suo posto.

**Non è una dimenticanza da poco.** Quella riga è l'unica protezione contro il modo in cui
questo difetto può tornare: `gs_get_posts_by_author()` cerca una funzione del tema **per
nome**. Se il tema cambia, o la rinomina in un aggiornamento, `has_action()` restituisce
`false`, l'helper diventa una `get_posts()` normale, **e tutto torna rotto in silenzio** —
come lo è stato per settimane.

Il codice è nella verifica della 3.281.0, sezione **A2**. **Adattalo ai nomi dei campi delle
altre righe di `diagnostica.php`**, non copiarlo alla lettera.

---

# 3 · `gs_tavolo_di_oggi()` è rimasta senza nessuno che la chiami

**File:** `includes/tavolo.php:59` — VERIFICATO: in tutto il plugin compare **solo nella
propria definizione e in due commenti.**

Nel documento avevo scritto: *«Se dopo le modifiche non la chiama più nessuno, dimmelo
invece di cancellarla — decidiamo insieme.»* **Non l'hai cancellata, ed è la scelta
prudente giusta. Ma non me l'hai detto, e adesso è una funzione morta in più.**

**Decisione:** tenerla, con due righe di commento che dicono perché.

```php
/**
 * La foto di oggi di una sfoglina, o null.
 *
 * Da quando le foto sono libere (26/08/2026) nessuno la chiama più: è tenuta
 * apposta perché è la funzione su cui il bug del tema sulle ricerche "per
 * autore" è stato trovato e provato (vedi gs_get_posts_by_author in
 * helpers.php) — se un domani quel problema si ripresenta, è da qui che si
 * riparte. Non è un avanzo dimenticato: se serve toglierla, si toglie
 * apposta.
 */
```

**Una funzione morta con scritto perché è viva non è un avanzo. Una senza, sì** — ed è
esattamente la voce **G1** del primo documento, dove `gs_inbox_non_letti()` è rimasta
scollegata per mesi senza che nessuno sapesse se era dimenticata o rinviata.

---

# 4 · I due elenchi nuovi di Diagnostica girano a ogni apertura del pannello

**File:** `includes/diagnostica.php`, `gs_diag_elenco_utenti_extra()` e
`gs_diag_tavolo_punti_pregressi()` — VERIFICATO

Tutte e due fanno `get_users()` **su tutti gli utenti del sito**, e la seconda, per ognuno,
legge e deserializza `gs_points_log` — fino a 100 voci a testa. **Girano tutte e due ogni
volta che qualcuno apre la Diagnostica**, anche solo per controllare il cron.

Oggi non è un problema: gli utenti sono poche decine. **Ma sono elenchi nati per essere
guardati una volta** — l'uno prima del reset, l'altro prima di installare il Tavolo — e
resteranno lì per sempre.

### Correzione

Metterli dietro un pulsante, come già si fa per l'email di prova nello stesso pannello:

```php
	echo '<p><button class="gs-btn gs-btn-sm gs-diag-elenco-utenti">Mostra l\'elenco degli account</button></p>';
```

con il calcolo spostato in una funzione AJAX. **Oppure**, se preferisci non toccare niente
adesso, va bene lo stesso — **ma allora scrivi nel commento che sono due scansioni complete
della tabella utenti**, così chi le trova fra un anno sa cosa sta guardando.

**Priorità bassa. La cosa che non deve succedere è che restino lì senza che nessuno sappia
cosa costano.**

---

# Da provare, non da correggere: la nuova tolleranza

La soglia nuova — risalire per **una schermata intera** prima che il menu torni — è quello
che ha chiesto Ennio e nel codice è scritta bene: la distanza si accumula salendo e si azzera
appena si scende, e vicino alla cima il menu torna subito senza soglia. Corretto.

**Ma va provata sul telefono, non solo sul computer.** `window.innerHeight` su un telefono
è molto più piccolo, e soprattutto **cambia da solo** quando il browser nasconde e rimostra
la propria barra degli indirizzi durante lo scorrimento. Una schermata di risalita fatta col
dito è parecchio lavoro.

**Non cambiare niente adesso.** Se Ennio prova sul telefono e gli sembra troppo, il valore da
toccare è uno solo (`window.innerHeight` nella condizione) e si può mettere un tetto:
`Math.min( window.innerHeight, 600 )`.

---

# Su D1, che avevo dichiarato caduto

Il conto dei punti del Tavolo è stato costruito. **Nel documento del reset avevo scritto che
non serviva più**, perché si azzera tutto prima del 1° ottobre.

**Non è un errore e non c'è niente da disfare:** è sola lettura, non tocca niente, e finché
il reset non è stato fatto dà comunque un'immagine di cosa è successo. **Ma non è una cosa
su cui spendere altro tempo** — se qualcosa non torna in quella tabella, non è un problema
da risolvere: sono numeri che stanno per essere azzerati.

---

# Riepilogo

| | Stato |
|---|---|
| Sfarfallio (C) | ✅ e le tre verifiche fatte prima di scrivere |
| `applica()` che dice se ha agito | ✅ il difetto segnalato ieri è chiuso |
| Pulsante solo dove serve | ✅ con le tre misure (subito, `load`, 1,5 s) |
| Scritte nuove — pulsante, gemello JS, PHP (3 punti) | ✅ |
| Tolleranza «una schermata» | ✅ scritta bene — **da provare sul telefono** |
| A1 · priorità di `remove_action` | ✅ esatta, con `try/finally` |
| B1 · `md5()` sul nome della squadra | ✅ |
| Il Tavolo senza limite | ✅ tutti e cinque i pezzi |
| Avanzi tolti (CSS + JS + commento falso) | ✅ |
| Elenco utenti in Diagnostica | ✅ **e meglio di come l'avevo chiesto** |
| **Pulsante del Pannello** | 🔴 **non imposta `sceltaManuale`: la scelta si disfa** |
| **A2 · controllo dell'aggancio del tema** | 🔴 **non fatto** |
| `gs_tavolo_di_oggi()` senza chiamanti | ⚠ decidere: tenerla col commento |
| Due scansioni utenti a ogni apertura | ⚠ dietro un pulsante, o almeno scritto |

**Da fare, in ordine:** il pulsante del Pannello (è il difetto appena chiuso, riaperto
altrove), poi A2, poi le due minori.

**Niente della 3.282.0 va rifatto.** Ennio può installare e provare il gesto su/giù — con
l'avvertenza che **nel Pannello di Controllo la scelta gli verrà ancora disfatta dallo
scorrimento** finché non arriva la correzione 1.
