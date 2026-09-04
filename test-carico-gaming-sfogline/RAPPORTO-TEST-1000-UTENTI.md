# Gaming Sfogline 3.321.84 — test completo con 1000 utenti collegati

**Data:** 4 settembre 2026
**Oggetto:** `gamingsfogline3.321.84.zip` (plugin WordPress "Gaming Sfogline")
**Dimensione del codice:** 103 file PHP, 48.847 righe, 1.522 funzioni `gs_`, 381 azioni AJAX, 66 shortcode, 36 tipi di contenuto personalizzati

---

## 0. Premessa sul "Local con guru2"

**Non sono riuscito a collegarmi al tuo Local.** Questa sessione gira in un contenitore isolato
nel cloud: non ha alcuna via di rete verso il tuo computer, e il sito `guru2` di Local vive solo
lì (di norma su `http://guru2.local`, risolto dal file hosts della tua macchina). Nessun tunnel,
nessun `localhost` condiviso: dall'esterno quell'indirizzo semplicemente non esiste.

Per non lasciare il test a metà ho **ricostruito da zero un sito WordPress equivalente qui dentro**
e ho fatto su quello tutte le prove. Se ti serve il test *esattamente* sul tuo `guru2` (stessi dati
veri, stesso tema, stessi altri plugin), le strade sono due: avviare Local con l'opzione **Live Link**
di ngrok e passarmi l'URL pubblico, oppure eseguire tu gli script che trovi in questa cartella
(`harness/`) dentro il tuo Local — sono autonomi e riproducono la stessa prova.

## 1. Ambiente di prova ricostruito

| | |
|---|---|
| WordPress | 7.1 (sorgente ufficiale) |
| PHP | 8.4.19, OPcache attivo |
| Database | SQLite tramite il plugin ufficiale *SQLite Database Integration* (MySQL non disponibile nel contenitore) |
| Server | server PHP integrato, 16 worker |
| Macchina | 4 CPU, 16 GB RAM (contenitore condiviso) |
| Utenti creati | **1.000 sfogline approvate**, con punti, punti del mese, punti dell'anno, streak, squadra, data di nascita |
| Contenuti creati | 4.259 post: 2.000 sfoglie su 6 sfide, 220 messaggi, 200 conversazioni da 10 messaggi, 200 ricette, 80 lezioni, 300 pagine di diario, e tutti gli altri tipi |
| Pagine di prova | 66, una per shortcode |
| Sessioni reali | 1.000 cookie di autenticazione veri (token di sessione WordPress) + 1.000 nonce `gs_ajax` |

**Attenzione a come leggere i numeri.** SQLite non è MySQL e il server PHP integrato non è
PHP-FPM: i **millisecondi assoluti** qui sotto sono indicativi e sul tuo hosting saranno diversi.
Quello che invece **non cambia** — ed è il cuore del rapporto — è il *numero di query SQL*, il
*peso dell'HTML*, il *picco di memoria* e le *condizioni di gara*: quelli dipendono solo dal
codice del plugin e da quanti utenti ci sono.

---

## 2. Risultato in tre righe

1. **Il plugin è solido dal punto di vista della correttezza e della sicurezza.** Zero errori di
   sintassi, zero warning PHP in oltre 12.000 richieste HTTP, nessuna SQL injection, nessun XSS,
   nonce e controlli di proprietà presenti ovunque li ho cercati.
2. **Non è pronto a reggere 1.000 utenti**, non per un errore isolato ma per una scelta di fondo:
   quasi tutto scorre l'elenco completo degli utenti a ogni caricamento di pagina.
3. **Ci sono tre difetti concreti e dimostrati** (perdita di dati, blocco di tutti gli accessi,
   variabile non definita) che vanno corretti a prescindere dal numero di iscritte.

---

## 3. Bug confermati con prova alla mano

### 3.1 🔴 Le spunte "messaggio letto" si perdono quando più sfogline leggono insieme

**Dove:** `includes/messaggi.php:75` (`gs_segna_letto()`)

```php
$letti = get_post_meta( $mid, 'gs_letto_da', true );   // legge
$letti[] = (int) $uid;                                 // modifica
update_post_meta( $mid, 'gs_letto_da', $letti );       // riscrive tutto
```

Tre operazioni separate su un unico array condiviso, senza lucchetto. Se due sfogline aprono la
pagina Messaggi nello stesso istante, la seconda riscrive l'array che aveva letto *prima* che la
prima lo aggiornasse: la spunta della prima sparisce.

**Prova eseguita.** Ho azzerato `gs_letto_da` su un messaggio "a tutte" e ho fatto aprire la
pagina Messaggi a 100 sfogline diverse in parallelo. Risultato, ripetuto tre volte:

| Richieste in parallelo | Spunte attese | Spunte registrate | **Perse** |
|---|---|---|---|
| 10 | 100 | 88 | 12 % |
| 25 | 100 | 72 | 28 % |
| 50 | 100 | 78 | 22 % |
| 25 (prima prova) | 100 | 63 | 37 % |

**Effetto reale:** da un quarto a un terzo delle sfogline continua a vedere il pallino "non letto"
su un messaggio che ha già aperto, e il conteggio dei letti che vedi tu nel pannello è sbagliato
per difetto. Con 1.000 iscritte e un annuncio inviato a tutte, questo succede *ogni volta*.

**Come si risolve.** Il campo giusto per "chi ha letto cosa" non è un array serializzato dentro
un post meta condiviso, ma **una riga per ogni coppia messaggio/utente**: `add_user_meta( $uid,
'gs_msg_letto_' . $mid, 1 )` (o una tabella propria). Le scritture non si sovrappongono più perché
ognuna tocca una riga diversa. In alternativa, un `GET_LOCK` attorno alle tre righe — ma è la
soluzione peggiore, perché mette in fila tutte le lettrici.

Lo stesso schema leggi-modifica-riscrivi c'è anche in:
- `includes/conversazioni.php` — array `letti` dentro `gs_conv_msgs`
- `includes/volo-notifiche.php:40-47` — coda `gs_voli_pendenti` (qui il rischio è minore perché
  la coda è per singolo utente, ma la sequenza "leggi la coda / cancella la coda" in
  `gs_ajax_voli_preleva()` perde comunque gli avvisi arrivati nel mezzo)
- `includes/antispam.php:60-66` — contatore del limite orario (qui la gara *abbassa* il conteggio,
  cioè indebolisce la protezione)

### 3.2 🔴 Cinque password sbagliate bloccano l'accesso a tutto il sito

**Dove:** `includes/login.php:35-52` + `includes/helpers.php:566` (`gs_get_ip()`)

Il blocco anti-forza-bruta conta i tentativi falliti **per indirizzo IP**, e `gs_get_ip()` legge
solo `$_SERVER['REMOTE_ADDR']`. Se il sito sta dietro Cloudflare, un load balancer, un proxy
dell'hosting — oppure se le sfogline sono tutte nella stessa aula, nella stessa scuola di cucina
o sulla stessa rete Wi-Fi — **`REMOTE_ADDR` è lo stesso per tutte**, e il contatore è uno solo per
tutte.

**Prova eseguita.** Cinque tentativi con password sbagliata, ognuno con un *utente diverso*, dallo
stesso indirizzo. Poi un accesso perfettamente valido di una sesta utente:

```
tentativo 1..5  →  {"success":false,"message":"Utente o password non corretti."}
accesso valido  →  {"success":false,"message":"Troppi tentativi con password sbagliata
                     da questo indirizzo. Riprova tra qualche minuto."}
```

**Effetto reale:** basta che cinque persone sbaglino la password perché **nessuno** possa più
entrare per 15 minuti. Dietro un proxy è garantito che succeda ogni giorno; a un corso in
presenza, con tutte sulla stessa rete, succede al primo tentativo andato male.

C'è anche un secondo effetto: `gs_ajax_login()` prende un `GET_LOCK` sullo stesso indirizzo IP con
attesa fino a 10 secondi. Con tutte le utenti dietro lo stesso IP, **gli accessi si mettono in fila
uno dietro l'altro**: al lancio di un corso, con cento persone che entrano insieme, l'ultima aspetta.

**Come si risolve.**
1. Leggere l'IP vero dietro proxy (`CF-Connecting-IP`, `X-Forwarded-For` — solo se il proxy è
   fidato, altrimenti si può falsificare) e in ogni caso **contare i tentativi per *account*,
   non solo per IP** — è la chiave che conta davvero.
2. Togliere il `GET_LOCK` per IP, o restringerlo alla chiave dell'account.
3. Stessa correzione va fatta al limite orario di `includes/antispam.php:59`, che è anch'esso
   per IP: con 1.000 utenti dietro un proxy, `max_per_hour` diventa un tetto per *tutto il sito*.

### 3.3 🟠 Variabile non definita in "blocca corso" — il titolo sparisce dall'avviso

**Dove:** `includes/calendario.php:969`

```php
function gs_ajax_cal_blocca_corso() {
    ...
    gs_inbox_crea( 'Corso bloccato: ' . $corso['titolo'], ... );   // $corso non esiste qui
```

`$corso` non è mai valorizzato dentro questa funzione (le funzioni vicine fanno
`$corso = gs_cal_corso_get(...)`, qui manca).

**Prova eseguita** — chiamata reale all'endpoint, come amministratore:

```
[04-Sep-2026 19:43:08 UTC] PHP Warning: Undefined variable $corso in .../includes/calendario.php on line 969
[04-Sep-2026 19:43:08 UTC] PHP Warning: Trying to access array offset on null in .../includes/calendario.php on line 969
```

**Effetto reale:** il messaggio che arriva nella tua casella interna dice *"Corso bloccato: "* senza
il nome del corso, e se `WP_DEBUG_DISPLAY` fosse acceso in produzione i due warning finirebbero
dentro la risposta JSON rompendo la schermata. È l'**unico** warning PHP che il plugin ha prodotto
in tutta la campagna di prova.

**Correzione:** aggiungere `$corso = gs_cal_corso_get( $id );` prima della riga 969.

---

## 4. Il problema di scala: tutto scorre l'elenco completo delle iscritte

Questo non è un bug isolato, è il modo in cui il plugin è costruito. Ho contato **125 query senza
limite** (`'number' => -1`, `'posts_per_page' => -1`, o `get_users()` senza `number`, che significa
la stessa cosa). Il modello tipico è:

```php
foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
    if ( ! gs_e_sfoglina_vera( (int) $uid ) ) { continue; }   // 1 query utente + 1 query meta
    $punti = (int) get_user_meta( $uid, 'gs_points', true );
}
```

Con `'fields' => 'ID'` WordPress **non prepara la cache**: ogni giro fa due query. Mille utenti,
duemila query. Per *ogni* caricamento di quella pagina, di *ogni* visitatore.

### 4.1 Costo misurato per shortcode (1.000 utenti, cache fredda)

| Shortcode | Query | Tempo | HTML prodotto |
|---|---:|---:|---:|
| `gs_cassaforte_sapere` | **2.006** | 1.405 ms | 9 KB |
| `gs_galleria_sfida` | **2.005** | 975 ms | **2,8 MB** |
| `gs_testamenti` | **2.004** | 854 ms | 0,4 KB |
| `gs_ultimi_traguardi` | **1.003** | 555 ms | 0,4 KB |
| `gs_messaggi` | 212 | 108 ms | 21 KB |
| `gs_sfogline` | 6 | 189 ms | **293 KB** |
| `gs_sfogline_palloncini` | 5 | 178 ms | 176 KB |
| gli altri 59 | ≤ 77 | ≤ 190 ms | — |
| **totale 66 shortcode** | **7.749** | **5,7 s** | — |

Nota amara su `gs_cassaforte_sapere` e `gs_testamenti`: fanno duemila query **per mostrare
nove caratteri di testo**. `gs_cassaforte_sapere` è anche peggio, perché chiama
`gs_cassaforte_conteggio()` due volte per ogni cassaforte (`includes/cassaforte-sapere.php:56` e
`:70`, la seconda dentro `gs_cassaforte_sbloccata()`).

### 4.2 Il pannello di controllo è ingestibile

Questo è il numero più grave del rapporto. `[gs_pannello]` visto da te, con 1.000 iscritte:

| | |
|---|---|
| Query SQL | **5.861** |
| Tempo del server | **8,8 secondi** |
| HTML in una sola pagina | **25,3 MB** |
| Picco di memoria PHP | **152 MB** |

Su un hosting condiviso normale (`max_execution_time` 30 s, `memory_limit` 128 o 256 MB) questa
pagina **non si apre**: o va in timeout, o esaurisce la memoria. E anche dove si apre, 25 MB di
HTML in una pagina sola bloccano il browser per parecchi secondi.

Le sezioni responsabili, misurate una per una:

| Sezione | Query | Tempo | HTML |
|---|---:|---:|---:|
| `gs_pannello_mail_generale` | 4.762 | 5.381 ms | 1,7 MB |
| `gs_pannello_iscritti` | 4.666 | 5.361 ms | 2,7 MB |
| `gs_pannello_messaggi_sfogline` | 3.866 | 5.078 ms | 1,6 MB |
| `gs_pannello_cassaforte_sapere` | 2.007 | 1.470 ms | 55 KB |
| `gs_pannello_giudici_gara` | 2.005 | 914 ms | 877 KB |
| `gs_pannello_stato_generale` | 1.004 | 544 ms | 21 KB |
| `gs_pannello_token` | 1.001 | 515 ms | 293 KB |
| `gs_pannello_vuoi_anche` | 1.001 | 507 ms | 2,5 KB |

Le forme di query più ripetute dentro il pannello:

```
x2005   SELECT wp_posts.ID FROM wp_posts INNER JOIN wp_postmeta ...
x1001   SELECT * FROM wp_users WHERE ID = 'N' LIMIT 1
x1000   SELECT wp_posts.ID FROM wp_posts WHERE post_author = N ...
x900    SELECT user_id, meta_key, meta_value FROM wp_usermeta WHERE user_id IN (N)
```

Sono le firme inconfondibili di un ciclo N+1: una query per utente, moltiplicata per il numero
di sezioni che rifanno lo stesso giro.

**Come si risolve.** Tre interventi, in ordine di resa:
1. **Paginare il pannello.** Nessuna sezione deve stampare 1.000 righe: 25 per pagina, con
   ricerca. È l'intervento che da solo riporta la pagina sotto il secondo.
2. **Caricare gli utenti in blocco.** Sostituire `get_users(['fields'=>'ID'])` + `get_user_meta`
   nel ciclo con `get_users()` completo (WordPress prepara la cache dei meta in *una* query) o,
   meglio ancora, con una sola query `SELECT user_id, meta_value FROM wp_usermeta WHERE
   meta_key = 'gs_points'`. Duemila query diventano una.
3. **Mettere in cache i conteggi collettivi.** `gs_cassaforte_conteggio()`, il numero di sfogline
   per livello, la classifica: sono numeri che cambiano di rado. Un `set_transient` da 5-15 minuti
   li azzera come costo.

### 4.3 Il polling: 200 richieste al secondo per non dire niente

`assets/js/gaming.js` fa partire, per **ogni** utente collegato e su **ogni** pagina, tre
interrogazioni ogni 15 secondi:

| Riga in `gaming.js` | Azione | Intervallo | Chi |
|---|---|---|---|
| 7435 | `gs_voli_preleva` | 15 s | tutti |
| 7826 | `gs_palloncini_ultimo` | 15 s | tutti |
| 8248 | `gs_palloncino_gigante_ultimo` | 15 s | tutti |
| 7413 | `gs_aeroplanino_ultimo` | 15 s | solo chi gestisce |
| 9089 | `gs_classifica_mensile_dati` | 20 s | chi sta sulla Classifica |

Con 1.000 utenti collegati: **3 × 1000 ÷ 15 = 200 richieste al secondo**, ognuna un avvio completo
di WordPress. Misurato qui: ~24 ms di CPU ciascuna, cioè **quasi 5 secondi di CPU per ogni secondo
di orologio** — cinque processi PHP occupati a tempo pieno per dire "non è successo niente".
La misura di throughput su questa macchina si è fermata a **88-100 richieste al secondo** per
endpoint: metà del necessario.

C'è un lato buono: c'è il controllo `if (document.hidden) return;`, quindi le schede in secondo
piano non interrogano. E il costo per singola richiesta è tutto avvio di WordPress, non lavoro
del plugin (con il plugin spento la stessa richiesta costa 21 ms invece di 24: **il caricamento
dei 103 file pesa solo ~2,5 ms**, merito di OPcache).

**Come si risolve.** Unire le tre interrogazioni in **una sola** (`gs_stato_diretta`, che
restituisce coda + palloncini + palloncino gigante in un colpo): 200 richieste al secondo
diventano 67. Portare l'intervallo a 30 secondi le dimezza ancora, a 33. E se in futuro il volume
cresce ancora, la strada giusta non è il polling ma un canale push (SSE / WebSocket) o un file
statico con il timestamp dell'ultimo evento, servito senza toccare PHP.

### 4.4 `gs_classifica_mensile_dati` è aperta agli anonimi e costa 147 ms a chiamata

`includes/classifica-mensile.php:43` carica **tutti** gli utenti con `'number' => -1` ordinati per
meta, per poi tenerne dieci. L'endpoint AJAX è registrato anche come `wp_ajax_nopriv_`, e il nonce
che serve è quello pubblico `gs_ajax`, **identico per tutti i visitatori non collegati** e leggibile
in chiaro nel sorgente di qualunque pagina.

**Prova eseguita:** chiamata anonima riuscita, `200 OK` in 205 ms; 20 chiamate di fila da un solo
client in 2,9 secondi. Un singolo visitatore, senza account, può tenere occupata una CPU intera del
server con un ciclo `while`.

**Come si risolve.** La classifica dei primi dieci va calcolata con una query sola
(`WP_User_Query` con `number => 50` e ordinamento sul meta, poi il filtro in PHP sui pochi
risultati) e messa in un transient da 60 secondi. In più, un limite di frequenza per sessione
sull'endpoint pubblico.

### 4.5 Un annuncio a tutte costa 2.000 query e 74 MB

`gs_aeroplanino_invia_messaggio()` (`includes/volo-notifiche.php:918`) cicla su tutte le sfogline
e chiama `gs_accoda_volo()`, che per ognuna fa una lettura e una scrittura di user meta.

**Misurato:** 1.000 destinatarie → **985 ms, 2.009 query, 74 MB di picco di memoria**, dentro una
singola richiesta HTTP.

Il punto delicato è *chi* paga questo costo. `gs_ajax_voli_preleva()` — l'interrogazione che ogni
utente fa ogni 15 secondi — chiama `gs_programma_esegui_dovuti()`, che può far partire un invio
programmato. Quindi il conto di quei 2.000 write lo paga **la sfoglina a cui è capitato di
interrogare per prima in quel minuto**: la sua richiesta si blocca per un secondo abbondante.
Con un elenco più lungo o un hosting più lento, va in timeout e l'invio resta a metà.

Va detto che l'autore si è posto il problema: c'è un `GET_LOCK` (`volo-notifiche.php:526`) e un
ricontrollo dei dati freschi dopo averlo preso — è fatto bene. Il problema non è la correttezza,
è che **il lavoro sta nel posto sbagliato**: dovrebbe essere una coda smaltita a blocchi da WP-Cron
(200 destinatarie per giro), non un lavoro sincrono dentro la richiesta di un utente.

### 4.6 Il digest settimanale manda 1.000 email dentro una sola esecuzione

`gs_digest_settimanale()` (`includes/notifications.php:125`) cicla su tutte le utenti e chiama
`wp_mail()` una per una. **Misurato: 5.971 ms e 1.008 query** — e qui le email non partivano
davvero (nessun sendmail nel contenitore). Con un SMTP vero, a 0,2-1 secondo per messaggio, sono
**da 3 a 15 minuti in un unico processo PHP**: ben oltre qualunque `max_execution_time`.

Peggio: `update_user_meta( $u->ID, 'gs_ultimo_digest', ... )` viene scritto **prima** dell'invio, e
WP-Cron toglie l'evento dalla coda quando lo avvia. Se il processo viene ucciso a metà, chi era in
coda dopo il punto di interruzione **non riceve il digest e non lo riceverà**: al giro dopo il
`continue` dei 6 giorni non c'entra, ma l'evento settimanale è già passato.

**Come si risolve:** spezzare l'invio in blocchi (`wp_schedule_single_event` ricorsivo, 100
destinatari per volta) e scrivere `gs_ultimo_digest` **dopo** l'invio riuscito.

### 4.7 Peso lato browser

`gs_enqueue_assets()` (`gaming-sfogline.php:573`) carica CSS e JS **su ogni pagina del sito**,
senza controllare se lì c'è davvero uno shortcode del plugin — anche per i visitatori non collegati
e sulla home del tema.

| File | Non compresso | Gzip |
|---|---:|---:|
| `assets/css/gaming.css` | 300,8 KB | 77,3 KB |
| `assets/js/gaming.js` | 515,3 KB | 103,5 KB |
| **totale** | **816 KB** | **181 KB** |

Più le immagini della cartella `assets/img`: 2,7 MB, fra cui `logo-accademia-sigillo.png` da
**1,1 MB** e tre PNG da oltre 400 KB — vanno ridimensionati e convertiti in WebP.

Le pagine misurate via HTTP: dashboard 139 KB, `gs_sfogline` 400 KB, `gs_galleria_sfida` **2,9 MB**,
pannello **25 MB**. Nella prova di carico mista, 1.000 utenti che aprono una pagina e fanno un giro
di interrogazioni hanno prodotto **721 MB di traffico**.

---

## 5. Prova di carico HTTP — numeri grezzi

Tutte le richieste con cookie di sessione veri e nonce validi, un utente distinto per richiesta.

**Polling, 1.000 utenti, 20 in parallelo**

| Endpoint | Esiti | media | p50 | p90 | p99 | req/s |
|---|---|---:|---:|---:|---:|---:|
| `gs_voli_preleva` | 1000 × `200` | 177 ms | 163 | 275 | 432 | 87,8 |
| `gs_palloncini_ultimo` | 1000 × `200` | 139 ms | 132 | 220 | 339 | 99,8 |
| `gs_palloncino_gigante_ultimo` | 1000 × `200` | 149 ms | 139 | 232 | 340 | 98,7 |

**Costo seriale per endpoint** (nessun parallelismo — è il costo puro)

| Endpoint | ms/richiesta |
|---|---:|
| avvio di WordPress a vuoto, plugin spento | 21,0 |
| avvio di WordPress a vuoto, plugin acceso | 23,4 |
| `gs_voli_preleva` | 24,5 |
| `gs_palloncini_ultimo` | 25,1 |
| `gs_palloncino_gigante_ultimo` | 23,8 |
| `gs_msg_conteggio` | 28,5 |
| `gs_conv_conteggio` | 32,5 |
| **`gs_classifica_mensile_dati`** | **147,5** |

**Carico misto — 1.000 utenti, una pagina a caso + tre interrogazioni ciascuno, 40 in parallelo**

```
4.000 richieste, esiti: 4.000 × HTTP 200, zero errori PHP
media 1.167 ms · p50 440 ms · p90 2.612 ms · p99 7.167 ms · max 12.087 ms
throughput 33,4 req/s · traffico totale 721 MB
```

Nessuna richiesta ha fallito, nessun errore fatale, nessun warning: **il plugin regge senza
rompersi**. Ma p90 a 2,6 s e p99 a 7 s a soli 33 req/s dicono che il margine non c'è.

---

## 6. Sicurezza — quello che ho trovato e quello che non ho trovato

Ho scandagliato tutte le 381 azioni AJAX con un analizzatore che segue anche le funzioni-guardia
chiamate a catena, e ho fatto prove dal vivo.

**Quello che è a posto (e merita di essere detto):**

- **Nonce ovunque.** 381 azioni su 381 hanno un `check_ajax_referer` nella catena di chiamata.
  Zero eccezioni.
- **Nessuna SQL injection.** Tutte le query dirette a `$wpdb` usano `prepare()`, comprese quelle
  con `IN (...)` costruite con i segnaposto (`includes/reset.php:145`, `includes/diagnostica.php:160`).
- **Nessun XSS.** Ho inviato una richiesta di aiuto contenente
  `XSSPROBE<img src=x onerror=alert(1)><script>alert(2)</script>"onmouseover="alert(3)` attraverso
  il flusso vero, e l'ho riletta dalla pagina: i tag erano stati rimossi da `gs_msg_clean()` e gli
  apici erano usciti come `&quot;`. Nessuna esecuzione.
- **Controllo di proprietà corretto.** Dove un'azione tocca il contenuto di qualcuno,
  l'oggetto è ricavato **dall'utente corrente**, non dall'ID che arriva nella richiesta
  (`gs_art_owner_post()`, `gs_voce_puo_modificare()`, `gs_ajax_cal_disdici()` che confronta
  `gs_cliente` con `get_current_user_id()`). Ho controllato 27 candidati IDOR: tutti coperti,
  o dalla guardia amministrativa o dal controllo di proprietà.
- **Niente `eval`, `extract`, `unserialize` su dati esterni.** Solo un `maybe_unserialize` su un
  valore già in database.
- **Upload:** il tipo è dedotto dall'estensione con `wp_check_filetype()`, non dal `Content-Type`
  dichiarato dal browser, e passa comunque da `media_handle_upload()`.
- **Password dimenticata:** stessa risposta che l'account esista o no, chiave nativa di WordPress.

**Quello su cui intervenire:**

| | Problema | Dove |
|---|---|---|
| 🔴 | Blocco accessi per IP condiviso (§3.2) | `login.php:35`, `helpers.php:566` |
| 🟠 | `gs_classifica_mensile_dati` accessibile senza account e costosa (§4.4) | `classifica-mensile.php:64` |
| 🟡 | Nonce unico `gs_ajax` per tutte le 381 azioni: un nonce rubato da una pagina vale per ogni azione che quell'utente può fare. Meglio nonce distinti almeno per le azioni distruttive | ovunque |
| 🟡 | Limite antispam per IP: dietro proxy diventa un tetto valido per tutto il sito (§3.2) | `antispam.php:59` |
| 🟡 | `GET_LOCK` non esiste su tutti i motori (MariaDB Galera, alcuni cluster gestiti): dove manca, i due lucchetti che il codice usa smettono silenziosamente di proteggere | `login.php:104`, `volo-notifiche.php:526` |

---

## 7. Analisi statica

**Sintassi:** `php -l` su tutti i 103 file → **zero errori**.

**PHPStan 2.2.13** (livello 3 e 5, con gli stub ufficiali di WordPress): **una sola** segnalazione
di sostanza, l'`$corso` non definito di §3.3. Il resto è rumore:
- 30 × «Closure invocata con 4 parametri, 1 richiesto» in `area-pro.php:154-196`: la closure `$add`
  usa `func_get_args()`, in PHP è legittimo. Non è un bug, ma vale la pena dichiarare i quattro
  parametri per leggibilità.
- 27 × coercizioni di tipo innocue (`get_userdata()` con un ID stringa, `esc_html()` con un intero).
- `control-panel.php:1585`: `! empty( $voluto ) && '0' !== $voluto` — la seconda condizione è
  irraggiungibile, perché `empty('0')` è già vero. Codice morto, non un difetto.

**Runtime:** in oltre 12.000 richieste HTTP e in tutte le prove interne, il log di debug ha
registrato **esattamente due righe**, entrambe del bug §3.3. Per un plugin di 49.000 righe su
PHP 8.4 è un risultato notevole.

---

## 8. Cosa farei, in ordine

**Prima di aprire a 1.000 iscritte**

1. Correggere `$corso` in `calendario.php:969` — cinque minuti.
2. Riscrivere `gs_segna_letto()` con una riga per coppia messaggio/utente (§3.1).
3. Contare i tentativi di accesso per *account* e leggere l'IP reale dietro proxy (§3.2).
4. Paginare le otto sezioni pesanti del pannello (§4.2) — è quello che sblocca la pagina.

**Nel mese successivo**

5. Unire le tre interrogazioni ogni-15-secondi in una sola, e portarla a 30 secondi (§4.3).
6. Mettere in cache con transient i conteggi collettivi: casseforti, classifica, numero di
   sfogline per livello (§4.1, §4.4).
7. Sostituire i cicli `get_users(['fields'=>'ID'])` + `get_user_meta` con un caricamento in blocco:
   sono 125 punti, ma i primi dieci coprono la maggior parte del costo.
8. Spezzare in blocchi l'annuncio a tutte e il digest settimanale (§4.5, §4.6).

**Quando c'è tempo**

9. Caricare CSS e JS solo dove servono davvero, e comprimere le immagini di `assets/img` (§4.7).
10. Nonce distinti per le azioni distruttive.
11. Attivare un object cache persistente (Redis o Memcached): è disponibile su quasi tutti gli
    hosting seri e taglia da solo gran parte delle query ripetute. **Non sostituisce** le correzioni
    ai cicli N+1, ma è il moltiplicatore più economico.

---

## 9. Rieseguire questa prova

In `harness/` trovi gli script usati, nell'ordine:

| Script | Cosa fa |
|---|---|
| `01-setup-wordpress.sh` | scarica WordPress + SQLite, installa, attiva il plugin |
| `02-seed-users.php` | crea 1.000 sfogline approvate con punti, streak, squadre |
| `03-seed-content.php` | crea sfide, sfoglie, messaggi, conversazioni, catalogo |
| `04-make-pages.php` | una pagina per ognuno dei 66 shortcode |
| `05-make-auth.php` | 1.000 cookie di sessione veri + nonce `gs_ajax` |
| `06-profile-shortcodes.php` | query/tempo/memoria per shortcode |
| `07-profile-panel.php` | query/tempo/HTML per sezione del pannello |
| `08-load-test.sh` | prove HTTP: polling, carico misto, gara sulle spunte |
| `09-scan-static.php` | scansione: query senza limite, guardie AJAX, IDOR |

Sul tuo Local con `guru2` basta puntare `WP_PATH` alla cartella del sito e usare `wp eval-file`
al posto del piccolo bootstrap incluso.
