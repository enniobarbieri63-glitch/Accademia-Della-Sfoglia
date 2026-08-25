# Seconda lettura · Blocco 2 — `esperti.php` e `token.php`

Le consulenze private a pagamento: la sfoglina compra token con un bonifico, li spende per
fare una domanda a un maestro. **Sono soldi veri, entrati con un contributo associativo.**

**Tre voci.** Le prime due sono **lo stesso difetto di C1**, che abbiamo appena corretto sui
pagamenti del calendario, ripetuto qui in due punti diversi — e uno dei due è rivolto alle
sfogline, non al gestore.

Numerazione **T1-T3**.

---

## T1 · ALTO — Accreditare token è un leggi-somma-riscrivi, e un accredito sbagliato non si può correggere

**File:** `includes/token.php:37-61` (`gs_token_movimento()`),
`includes/token.php:137-172` (`gs_ajax_token_accredita()`)
**Stato:** VERIFICATO

### Cosa succede oggi

```php
// gs_token_movimento()
$nuovo = max( 0, gs_token_saldo( $uid ) + $delta );
update_user_meta( $uid, 'gs_token_credito', $nuovo );
```

**È esattamente la riga che abbiamo appena corretto in `gs_ajax_cal_pagamento`**, su una
grandezza che vale altrettanto: i token si comprano con un bonifico.

`gs_ajax_token_accredita()` non ha **nessuna** protezione contro la ripetizione: né
identificativo dell'operazione, né controllo sull'ultimo movimento. **Un doppio clic su
«Accredita» accredita due volte.**

### E come per il calendario, non si torna indietro

```php
if ( $token_n <= 0 ) {
	wp_send_json_error( array( 'message' => 'Indica quanti token assegnare.' ) );
}
```

**Un numero negativo è rifiutato**, quindi dal pannello non esiste modo di togliere token
accreditati per errore. Identico al problema C1 prima della correzione.

**Una differenza in meglio:** qui **lo storico esiste** (`gs_token_log`, riga 46), quindi i
due accrediti si vedono. È già più di quanto avessero i pagamenti. Ma vedere l'errore e non
poterlo correggere è solo metà del lavoro.

### Una cosa che nasconde gli errori

```php
$nuovo = max( 0, gs_token_saldo( $uid ) + $delta );
```

Il `max( 0, ... )` impedisce al saldo di andare sotto zero. Sembra prudente, **ed è invece
il modo in cui un doppio addebito diventa invisibile**: con 1 token e due consumi da 1, il
saldo finisce a 0 invece che a −1, e nessuno si accorge che è stato tolto un token di
troppo. Lo storico registra due movimenti da −1, ma il saldo racconta un'altra storia.

**Non toglierlo** — un saldo negativo confonderebbe la sfoglina — ma **quando succede va
scritto nello storico**, altrimenti la contabilità dei token mente senza dirlo.

### Correzione

**È la stessa già scritta per C1**, applicata a un altro file. Riusare quella, non
inventarne un'altra:

1. **Identificativo dell'operazione** generato una volta all'apertura della scheda, e
   controllato **prima** di sommare.
2. **Rete di sicurezza sull'ultimo movimento**, per quando l'identificativo non arriva:
   `gs_token_log` esiste già ed è in cima all'array (`array_unshift`), quindi l'ultima voce
   è `$log[0]` — ancora più comodo del registro dei pagamenti.
3. **Permettere la correzione:** accettare un numero negativo **solo dal titolare**, con
   `'tipo' => 'correzione'` nello storico.
4. **Registrare il taglio a zero:** quando `max( 0, ... )` interviene, aggiungere al motivo
   una nota del tipo *«(saldo insufficiente: richiesti N, tolti M)»*.

**Fai T1 e la rete di sicurezza di C1 nello stesso giro:** sono la stessa correzione, e
scriverla due volte in due modi diversi è come si creano le incoerenze.

---

## T2 · ALTO — Il doppio clic su «invia domanda» addebita due volte, e le richieste simultanee regalano domande

**File:** `includes/esperti.php:402-459` (`gs_ajax_esperto_domanda_privata()`)
**Stato:** VERIFICATO

**Questo è rivolto alle sfogline, non al gestore**: è l'unico difetto di questa famiglia
che una persona può incontrare per sbaglio, o sfruttare apposta.

### L'ordine delle operazioni

```php
$lim = gs_domande_limiti_ok( $uid );          // ← controlla il cooldown
...
if ( gs_token_saldo( $uid ) < $costo ) { ... } // ← controlla il saldo
$cid = gs_conv_trova_o_crea( ... );
gs_token_movimento( $uid, -$costo, 'consumo', ... );   // ← addebita
gs_conv_aggiungi( ... );                               // ← crea la domanda
$cooldown = (int) gs_esperti_limiti()['cooldown'];
if ( $cooldown > 0 ) { set_transient( 'gs_dom_cd_' . $uid, 1, $cooldown ); }  // ← SOLO ORA
```

**Il freno — il cooldown di 20 secondi — è messo per ultimo.** È la stessa forma del
marcatore scritto alla fine del ciclo che abbiamo corretto nella chiusura del mese, qui su
una singola operazione.

### Due esiti opposti, tutti e due sbagliati

**Doppio clic (le due richieste si susseguono):** la prima finisce e scala il token, la
seconda parte quando il cooldown c'è già… **se è già stato scritto.** Se il primo giro non
ha ancora raggiunto l'ultima riga, la seconda passa: **due token consumati e due domande
identiche al maestro.** La sfoglina paga due volte per una domanda sola.

**Richieste simultanee (due schede, o un invio ripetuto):** entrambe leggono lo stesso
saldo prima che una qualsiasi abbia scritto. Con 5 token e costo 1, tutte e due calcolano
`5 − 1 = 4` e scrivono 4. **Un token scalato, due domande inviate.** Ripetibile: si ottengono
N consulenze al prezzo di una.

**Il primo caso danneggia la sfoglina. Il secondo l'Accademia.** Entrambi nascono dalla
stessa riga messa nel posto sbagliato.

### Il confronto che rende evidente il difetto

Nello stesso plugin, `gs_ajax_cal_prenota()` protegge **i posti di un corso** con un
lucchetto di MySQL, perché un test aveva mostrato che un corso da 3 posti ne accettava 4.

**Qui non è protetto niente — e questi non sono posti, sono crediti comprati con un
bonifico.**

### Correzione

La minima, e coerente con tutto il resto del lavoro: **spostare il freno prima degli
effetti**, invece di aggiungere un meccanismo nuovo.

Subito dopo il controllo dei limiti, **prima** del controllo del saldo:

```php
	// Il cooldown va scritto PRIMA di toccare token e conversazioni, non dopo:
	// messo alla fine, due richieste ravvicinate (doppio clic, due schede)
	// passano entrambe il controllo qui sopra prima che una qualsiasi l'abbia
	// scritto — e allora o si addebita due volte la stessa domanda, o si
	// creano due domande scalando un token solo. Stessa lezione del marcatore
	// per sfoglina nella chiusura del mese (trovato il 23/08/2026).
	$cooldown = (int) gs_esperti_limiti()['cooldown'];
	if ( $cooldown > 0 ) {
		set_transient( 'gs_dom_cd_' . $uid, 1, $cooldown );
	}
```

e **togliere le tre righe corrispondenti alla fine della funzione.**

**Compromesso da dichiarare:** se l'invio fallisce dopo questo punto — canale rotto,
errore nel creare la conversazione — la sfoglina resta in attesa per la durata del
cooldown pur non avendo mandato niente. Sono 20 secondi e il messaggio dice già
*«Aspetta qualche secondo»*: è un prezzo accettabile per non farle pagare due volte.

**Se il cooldown fosse impostato a 0** (è configurabile dal pannello), questa protezione
sparisce. Vale la pena impedirlo: `max( 5, $cooldown )` per il solo scopo del blocco,
indipendentemente da quanto Ennio l'ha impostato per l'attesa visibile.

---

## T3 · MEDIO — Per mandare una domanda, il plugin rilegge tre volte tutte le conversazioni

**File:** `includes/esperti.php:213-224` (`gs_domande_conteggio()`),
chiamata tre volte da `gs_domande_limiti_ok()` (righe 231, 234, 237) — VERIFICATO

```php
function gs_domande_conteggio( $uid, $giorni ) {
	foreach ( gs_conv_di_utente( $uid ) as $c ) {          // fino a 100 conversazioni
		foreach ( gs_conv_msgs( $c->ID ) as $m ) {         // + tutti i messaggi di ognuna
```

`gs_domande_limiti_ok()` la chiama **tre volte** — per il giorno, la settimana e il mese —
e ogni chiamata rifà da capo lo stesso lavoro: una `get_posts` con `meta_query` in OR fino a
100 conversazioni, e per ognuna la lettura e deserializzazione dell'array completo dei
messaggi.

**Tre passate complete su tutta la corrispondenza della sfoglina, per rispondere a tre
domande che si potrebbero contare in una passata sola.** E succede mentre la sfoglina
aspetta che parta la sua domanda.

### Correzione

Contare una volta sola, sulla finestra più larga, e derivare le altre due:

```php
/** Conteggi delle domande a token negli ultimi 1, 7 e 30 giorni, in una passata sola. */
function gs_domande_conteggi( $uid ) {
	$ora = time();
	$out = array( 1 => 0, 7 => 0, 30 => 0 );
	if ( ! function_exists( 'gs_conv_di_utente' ) ) { return $out; }
	foreach ( gs_conv_di_utente( $uid ) as $c ) {
		foreach ( gs_conv_msgs( $c->ID ) as $m ) {
			if ( empty( $m['consulenza'] ) || (int) $m['from'] !== (int) $uid ) { continue; }
			$eta = $ora - (int) $m['time'];
			if ( $eta < 30 * DAY_IN_SECONDS ) { $out[30]++; }
			if ( $eta < 7 * DAY_IN_SECONDS )  { $out[7]++; }
			if ( $eta < DAY_IN_SECONDS )      { $out[1]++; }
		}
	}
	return $out;
}
```

e in `gs_domande_limiti_ok()` chiamarla una volta sola, usando `$n[1]`, `$n[7]`, `$n[30]`.

**Tieni `gs_domande_conteggio()`** finché non hai verificato che non la chiami nessun altro:
al momento risulta usata solo qui, ma va controllato prima di toglierla.

**Priorità media.** Oggi le conversazioni sono poche; il difetto cresce con l'uso, ed è
sulla strada di un'operazione a pagamento.

---

## Riepilogo del blocco

| # | Cosa | File:riga | Gravità |
|---|---|---|---|
| **T1** | Accredito token: doppio clic raddoppia, e non si corregge | `token.php:37, 137` | **Alto** |
| **T2** | Doppio clic sulla domanda: paga due volte, o ne regala una | `esperti.php:402` | **Alto** |
| T3 | Tre riletture di tutte le conversazioni per un invio | `esperti.php:213` | Medio |

**T1 va fatta insieme alla rete di sicurezza di C1**: è la stessa correzione su un altro
file, e vanno scritte nello stesso modo.

**T2 è la più urgente delle tre** perché è l'unica che una sfoglina può incontrare da sola,
e riguarda soldi che ha versato.

---

## Lo schema che si sta ripetendo, e vale la pena dirlo

C1, T1, T2, A2 (il doppio clic sullo sconto) e B5 (la salita di livello doppia) **sono tutti
la stessa cosa**: un'operazione che tocca un valore accumulato, senza niente che impedisca
di eseguirla due volte.

Il plugin sa già farlo bene in tre punti — il lucchetto sui posti del corso, l'incremento
atomico dei punti in `points.php`, il controllo `in_array()` sui badge. **Non è una lacuna
di competenza: è una regola applicata dove qualcuno si è accorto del problema, e non dove
non se n'è accorto nessuno.**

Per i moduli che restano da leggere, quella è la prima cosa che cerco.

---

## Cosa leggo adesso

`artigiani.php` e `scuole-cucina.php` — gli abbonamenti dei partner, che sono l'altra
entrata a pagamento del sito.
