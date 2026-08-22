# Risposta alla tua domanda sull'eliminazione dei messaggi

**No. Non togliere niente.** Hai fatto bene a fermarti e a segnalare, ma la causa che hai
individuato non è quella giusta, e la soluzione che proponi non risolverebbe il problema.

## Cosa hai visto bene

Che i due meccanismi coesistono sugli stessi dati e non si vedono a vicenda: vero.
Che si può finire per eliminare la risposta sbagliata in un thread: vero.
Che `moderazione.php` copre sette sistemi e l'altro solo due: vero.

Fermarti a chiedere invece di correggere è stato il comportamento giusto.

## Cosa ti è sfuggito, e cambia la conclusione

**1 · La causa non è la duplicazione, è che le risposte non hanno un identificativo.**

```php
// messaggi.php:240 — la risposta nasce senza id
$tutte[ $thread_uid ][] = array( 'autore' => $autore_uid, 'testo' => $testo, 'data' => current_time( 'mysql' ) );

// messaggi.php:267 — e viene indirizzata per posizione
$tutte[ $thread ][ $i ]['gs_eliminato'] = ( 'elimina' === $azione );
```

Qualunque cosa rinumeri l'array fa puntare alla riga sbagliata i pulsanti già disegnati.

**2 · Le sorgenti di rinumerazione sono due, non una.** Oltre all'`array_values()` di
`moderazione.php`, c'è questa, in `messaggi.php:241`, che **non c'entra niente con la
moderazione**:

```php
if ( count( $tutte[ $thread_uid ] ) > 100 ) { $tutte[ $thread_uid ] = array_slice( $tutte[ $thread_uid ], -100 ); }
```

**Superate le 100 risposte, ogni nuova risposta scarta la più vecchia e rinumera l'intero
thread da sola.** Il difetto si presenta quindi anche con un solo sistema attivo e un solo
gestore al lavoro. **Togliere il tuo non lo elimina**: `moderazione.php` continua a
rinumerare con `array_values()` e la sua stessa lista è costruita per indice
(`gs_mod_elimina( $sistema, $post_id, $indice )`).

**3 · Togliere il tuo sistema farebbe ricomparire messaggi già nascosti.** `gs_eliminato`
è letto **solo** da `conversazioni.php` e `messaggi.php` — nessun altro file lo conosce.
La 3.271.0 è installata e in uso: se qualcuno ha già nascosto dei messaggi con quel
pulsante, **rimuovendo il sistema quei messaggi tornano visibili in chiaro**, e nessuno se
ne accorge finché non ricompare qualcosa che era stato tolto apposta. Questo effetto
collaterale non era nella tua analisi e da solo basterebbe a fermare la proposta.

**4 · In Conversazioni l'identificativo esiste già.** `conversazioni.php:99` crea ogni
messaggio con `'id' => uniqid( 'm' )`. Lì non manca niente: manca solo usarlo.

## Cosa devi fare adesso

**Niente su questo.** Non togliere il tuo sistema, non toccare `moderazione.php`, non
modificare `messaggi.php`. È un difetto reale ma latente: perché scatti serve un thread
sopra le 100 risposte, o due gestori che moderano nello stesso momento, o una pagina
lasciata aperta durante una moderazione. Nel frattempo A1 può raddoppiare sconti veri il
1° del mese e A3 costa a ogni singola visita del sito.

**È stato registrato come voce E7 nel documento** (Blocco E, con la correzione completa
scritta) **e assegnato al Giro 5.** Non anticiparlo.

Per memoria, la correzione giusta quando ci arriveremo — **non farla ora**: dare un
`'id' => uniqid( 'r' )` alle nuove risposte, far cercare a entrambi i sistemi la risposta
per id invece che per posizione, e ricadere sull'indice solo quando l'id manca, così i
messaggi già esistenti non vanno migrati. Fatto questo i due sistemi convivono senza
rischio, e quale tenere torna a essere una scelta di comodità di Ennio, non una necessità
tecnica.

## Torna al Giro 0 e chiudilo

Hai fatto la modifica (13 righe tolte da `nastro-vetrine.php`) e il lint, ma **non hai
riportato l'esito della verifica sul sito**. Serve quello per chiudere il giro:

1. Conferma che `grep -rn "nomi_demo\|Marta Colombo\|DATI DEMO" includes/` dia **zero
   risultati**.
2. Apri la pagina «Le Sfogline» e dimmi **quante sfogline vere** restano nel nastro grande.
   Se sono zero e il nastro non compare, va bene così: è il comportamento previsto.
3. Verifica che le altre pagine del sito non siano cambiate: quella modifica tocca solo lo
   shortcode `[gs_nastro_grande_sfogline]`, che sta unicamente su «Le Sfogline».

Poi scrivi a Ennio il riepilogo del Giro 0 e **fermati lì**, come previsto. Il Giro 1 parte
solo dopo il suo via libera.
