# Verifica di 3.287.0

**Letto il codice, non il rapporto. Confronto riga per riga con 3.286.0, più la batteria di prove.**

Sette file cambiati. **I tre difetti sono chiusi, e sono chiusi meglio di come li avevo specificati io.** Resta una cosa, ed è una cosa vera.

---

## Prima di tutto: avete ragione sulla mia prova 7, e non solo un po'

L'osservazione è esatta:

> *«un'interlacciatura scriptata può "passare" anche con un lucchetto che non blocca davvero sotto concorrenza reale»*

È giusto e va detto senza attenuanti. La mia prova 7 esegue due letture e due scritture **dentro lo stesso processo**, in un ordine che decido io. Serve a una cosa sola — far vedere che in un codice **senza** lucchetto la sequenza è possibile — e per quello va bene. **Ma non potrebbe mai promuovere una correzione**, perché non c'è niente da bloccare: un lucchetto finto e un lucchetto vero si comportano identici in un processo solo.

Due processi PHP separati con `&`/`wait`, attraverso i veri handler AJAX, sono **lo strumento giusto e io qui non ce l'ho**: in questa macchina non c'è MySQL, quindi `GET_LOCK` non può essere reale nemmeno volendo. Le mie prove restano utili per quello che sono — logica pura, contrassegni, tetti, integrità del pacchetto — e per i lucchetti la vostra prova è l'unica che conta. **Quando c'è di mezzo un lucchetto, la vostra prova batte la mia. Fatela voi.**

## E la vostra scoperta sulla cache scopre un buco nelle mie specifiche

Questa è la parte importante di tutto il giro, e non è vostra soltanto perché l'avete trovata: è vostra perché **io avevo scritto la specifica sbagliata**.

Nel documento su I1 (`indovina.php`) avevo scritto, testuale, che il lucchetto va accompagnato da `wp_cache_delete( $uid, 'user_meta' )` e che *«senza quella il lucchetto non serve a niente perché la rilettura risponde comunque dalla fotografia di inizio richiesta»*.

Poi, per C6 e per S1/S2, ho scritto solo *«rileggi DENTRO il lucchetto»* — **e mi sono dimenticato la cache.** Avevo applicato la regola ai meta utente e non l'ho portata ai meta dei post, come se il problema fosse dei primi. Non lo è:

> **`get_post_meta()` su una sola chiave carica e mette in cache TUTTI i meta di quel post.**

Quindi qualunque lettura fatta *prima* del lucchetto — anche di una chiave che non c'entra niente, anche un `get_posts()` — avvelena ogni lettura fatta *dentro*. Nei sondaggi bastava il `get_post_meta( $id, 'gs_sond_stato' )` dell'handler; nel cron dei token bastava il `get_posts()` in cima.

Se aveste seguito la mia specifica alla lettera avreste messo il lucchetto e basta, e il difetto sarebbe rimasto — con l'aggravante di sembrare risolto. **L'avete trovato voi perché avete provato attraverso il percorso vero invece che chiamando la funzione.** È esattamente il motivo per cui quel metodo è migliore, e il vostro «il mio primo giro di test era passato per errore» è la parte più utile del rapporto.

Da adesso la regola nei miei documenti è una sola, per tutti e due i tipi di meta: **lucchetto, svuota la cache, poi rileggi.**

---

## Le tre correzioni, verificate

### Sondaggi ✅

`gs_sondaggio_vota_uid()` e `gs_sondaggio_proponi_uid()`, estratte dagli handler. Lucchetto `'gs_sond_' . $id` — **la stessa chiave per il voto e per la proposta**, come serviva perché i due non si pestino i piedi. `wp_cache_delete( $id, 'post_meta' )` subito dentro. `try/finally`.

E l'ordine è giusto: `update_post_meta( 'gs_sond_voti' )` **prima** di `gs_add_points()`. Il contrassegno prima degli effetti.

### Madrina & Allieva ✅ — provata

Contrassegno `pagata` che non si toglie mai, e `gs_abbinamento_salva_missioni()` chiamata **prima** del pagamento. Questa è logica pura, senza lucchetti, quindi la mia prova qui vale: ho eseguito il cuore vero della funzione, dieci clic sulla stessa casella.

```
3.286.0 (prima)  → madrina 25, allieva 25 (totale 50)   ✗✗ pagata 5 volte
3.287.0 (dopo)   → madrina 5,  allieva 5  (totale 10)   ✓ pagata una volta sola
                    l'interruttore funziona ancora, contrassegno 'pagata' presente
```

### Token ✅ per la concorrenza — con una riserva qui sotto

Lucchetto condiviso `'gs_conv_rimborso_' . $cid` fra il rimborso a mano e quello del cron, cache svuotata, `try/finally` in tutti e due i file. Il `continue` del cron quando il lucchetto è occupato, con «ci pensa il prossimo giro», è la scelta giusta.

### E in più, i due suggerimenti minori ✅

`try/finally` sull'adozione del piatto al posto dei rilasci a mano, e lo stesso lucchetto anche sulla rinuncia. Non li avevo chiesti come urgenti e sono stati fatti bene.

### La batteria di prove sul pacchetto ✅

```
96 file — 0 errori di sintassi
1361 funzioni definite, 0 chiamate e non definite, 0 doppie
342 azioni AJAX in PHP ↔ 289 dal JavaScript — 0 pulsanti morti, 0 gestori rotti
```

Tre funzioni in più rispetto a 3.286.0: le tre `_uid` estratte. Tutte e tre chiamate.

---

## La cosa che resta: il contrassegno è ancora dopo i soldi

**Il lucchetto e l'ordine di scrittura risolvono due problemi diversi, e qui ne è stato risolto uno solo.**

Il lucchetto protegge da **due cose che accadono insieme**. L'ordine protegge da **una cosa che si interrompe a metà**. Il primo non copre il secondo.

In tutti e due i percorsi di rimborso l'ordine è ancora questo:

```php
gs_token_movimento( $sfoglina, ..., 'rimborso', ... );   // 1. il token torna
$msgs[ $i ]['rimborsato'] = true;
update_post_meta( $cid, 'gs_msgs', $msgs );              // 2. il contrassegno
```

Se il processo muore fra la riga 1 e la riga 3 — errore fatale, tempo massimo di esecuzione, l'hosting che stacca — **il token è restituito e nessuno lo sa**. Il giro dopo, il lucchetto si prende regolarmente, la rilettura (adesso pulita) trova `rimborsato` vuoto, e restituisce di nuovo.

**Nel cron è molto peggio, e non di poco.** In `token.php` la scrittura non è dentro il ciclo dei messaggi: è **dopo**.

```php
foreach ( $msgs as $i => $m ) {
    …
    gs_token_movimento( … );          // restituisce
    $msgs[ $i ]['rimborsato'] = true; // solo in memoria
    gs_mail_progetto( … );            // e manda un'email
}
if ( $cambiato ) {
    update_post_meta( $c->ID, 'gs_msgs', $msgs );   // ← una volta sola, alla fine
}
```

Una conversazione con cinque domande scadute restituisce cinque token e manda cinque email **prima di scrivere un solo contrassegno**. Se il ciclo muore alla terza, tre token sono usciti e il registro è pulito: il giorno dopo escono di nuovo tutti e cinque.

E non è un'ipotesi di scuola. Sta scritto in questo stesso plugin, in `buono-sfoglia.php`, nel commento che spiega perché lì il contrassegno è per singola sfoglina e viene scritto prima:

> *«se il ciclo muore a metà (troppe sfogline per il tempo massimo di esecuzione, o il limite orario di posta dell'hosting), la ripresa riparte da dove si era fermata invece che da capo»*

**Il limite orario di posta dell'hosting è esattamente ciò che uccide un ciclo che manda un'email per giro.** La lezione è già stata pagata una volta su questo progetto, su un ciclo della stessa forma. Qui girano i token, che sono soldi.

### La correzione, in tutti e due i file

Il contrassegno va scritto **per singolo messaggio, prima del movimento**:

```php
// Contrassegno PRIMA del movimento e PRIMA dell'email, scritto per ogni
// singola domanda invece che una volta alla fine del ciclo. Il lucchetto
// impedisce a due rimborsi di partire insieme, ma non impedisce a UNO di
// morire a metà: se il ciclo si ferma (tempo massimo, limite orario di
// posta dell'hosting), i token gia' usciti devono risultare usciti. È la
// stessa protezione già scritta in buono-sfoglia.php, per lo stesso motivo.
$msgs[ $i ]['rimborsato'] = true;
update_post_meta( $c->ID, 'gs_msgs', $msgs );
gs_token_movimento( $sfoglina, (int) $m['token_costo'], 'rimborso', '…' );
gs_mail_progetto( … );
```

Costa una scrittura per rimborso invece di una per conversazione. **I rimborsi sono rari**: è un prezzo che non si vede.

Il verso dell'errore cambia, ed è quello giusto: se qualcosa si rompe adesso il peggio è **un rimborso perso** — che Ennio vede e rimette a mano dal pannello — invece di **un rimborso doppio**, che non vede nessuno.

---

## La risposta alla domanda: sì, ma sposta tre righe prima

**Zippate pure 3.287.0, dopo aver spostato il contrassegno prima del movimento in tutti e due i file.** Sono tre righe in `conversazioni.php` e tre in `token.php`, non toccano niente di quello che avete appena provato, e il vostro test a processi paralleli continuerà a passare identico — perché state correggendo l'altro problema, non quello.

Se preferite tenerle separate va bene lo stesso, ma allora **fatelo prima che il cron giri su dati veri**: `gs_token_controlla_rimborsi` è agganciato a `gs_daily_cron` e parte da solo.

## `prova.sh`

È nel repository, in `revisioni/prove/`. Ennio ve lo passa insieme a questo documento. Si usa così:

```bash
./prova.sh /percorso/alla/cartella/che/contiene/gaming-sfogline
```

Servono solo `php` da riga di comando e `unzip`. Le tre prove generali (sintassi, funzioni mancanti, JavaScript ↔ PHP) costano dieci secondi e prendono l'intera classe di errori che nasce quando si toglie o si rinomina qualcosa — **che è il rischio del reset e del lavoro sui trenta giorni, cioè quello che viene adesso**.

Le altre (`test_piatto.php`, `test_madrina2.php`, `test_diario.php`…) sono mirate e confrontano due versioni: prima falliscono, dopo passano. Prendetele come modello, non come vincolo — **per tutto ciò che ha un lucchetto dentro, il vostro metodo a processi paralleli è migliore del mio e va usato quello.**
