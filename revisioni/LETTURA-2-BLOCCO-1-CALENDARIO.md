# Seconda lettura · Blocco 1 — `calendario.php`

Prima lettura riga per riga delle 1.803 righe del file più grande del plugin, quello che il
briefing nominava fra i moduli che muovono soldi e di cui, nella prima analisi, avevo letto
solo gli endpoint AJAX.

**Cinque voci nuove.** La prima è la più seria trovata dall'inizio del lavoro, dopo A1.

Numerazione **C1-C5** per non confondersi con le voci del primo documento.

---

## C1 · ALTO — Un doppio clic su «Registra pagamento» raddoppia l'importo, e non si può correggere

**File:** `includes/calendario.php:813-833` (`gs_ajax_cal_pagamento()`)
**Stato:** VERIFICATO

### Cosa succede oggi

```php
$importo = (float) str_replace( ',', '.', $_POST['importo'] ?? 0 );
if ( $importo <= 0 ) { wp_send_json_error( array( 'message' => 'Importo non valido.' ) ); }
$key = ( 'saldo' === $tipo ) ? 'gs_saldo_versato' : 'gs_acconto_versato';
$corrente = (float) get_post_meta( $pid, $key, true );
update_post_meta( $pid, $key, $corrente + $importo );
```

È un **leggi-somma-riscrivi su denaro**, senza nessuna protezione contro la ripetizione.
Un doppio clic sul pulsante manda due richieste: la prima legge 0 e scrive 50, la seconda
legge 50 e scrive 100. **Sulla prenotazione risultano 100 € versati invece di 50.**

Non è un caso raro: è un pulsante in un pannello, e i doppi clic capitano a tutti.

### E qui viene il punto peggiore

**Non c'è modo di correggere l'errore dal pannello.**

- Il pannello offre solo «Registra pagamento», che **somma**. Non esiste un campo per
  impostare il totale.
- Un importo negativo per stornare **è rifiutato** dalla riga
  `if ( $importo <= 0 )`.
- **Non esiste nessun registro dei pagamenti.** Delle percentuali di sconto il plugin tiene
  lo storico (`gs_sconto_log_aggiungi`, `gs_buono_sfoglia_log_aggiungi`); **degli euro no.**
  C'è solo il totale corrente, senza traccia di come ci si è arrivati.

Quindi: registri 500 invece di 50 per un errore di battitura, e quella prenotazione dice
500 € per sempre. L'unico modo di sistemarla è mettere le mani nel database.

### Il confronto che rende evidente il difetto

**Nello stesso file, a 250 righe di distanza**, la prenotazione è protetta con cura:

```php
// gs_ajax_cal_prenota(), riga 570
$lock_prenota = 'gs_prenota_' . $corso_id;
if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_prenota ) ) ) { ... }
```

con un commento che spiega che senza il lucchetto un corso da 3 posti ne accettava 4,
dimostrato con un test di carico il 14/08/2026.

**I posti sono protetti da una corsa. I soldi no.** È la stessa attenzione applicata a metà.

### Correzione

Tre pezzi, e il terzo è quello che conta di più.

**1 · Registrare ogni pagamento, non solo il totale.** Stesso schema già usato per gli
sconti:

```php
function gs_cal_pagamento_log_aggiungi( $pid, $voce ) {
	$log = get_post_meta( $pid, 'gs_pagamenti_log', true );
	$log = is_array( $log ) ? $log : array();
	$voce['data']  = current_time( 'mysql' );
	$voce['da']    = get_current_user_id();
	$log[]         = $voce;
	update_post_meta( $pid, 'gs_pagamenti_log', $log );
}
```

**2 · Impedire il doppio invio.** Il client manda un identificativo dell'operazione, il
server lo rifiuta se l'ha già visto:

```php
$rif = isset( $_POST['rif'] ) ? sanitize_key( wp_unslash( $_POST['rif'] ) ) : '';
$visti = get_post_meta( $pid, 'gs_pagamenti_rif', true );
$visti = is_array( $visti ) ? $visti : array();
if ( $rif && in_array( $rif, $visti, true ) ) {
	wp_send_json_error( array( 'message' => 'Questo pagamento risulta già registrato.' ) );
}
$visti[] = $rif;
update_post_meta( $pid, 'gs_pagamenti_rif', $visti );   // PRIMA di sommare
```

In `gaming.js`, il pulsante genera il riferimento una volta all'apertura del modulo, non a
ogni clic. **In più: disabilita il pulsante al primo clic** — non risolve da solo (due
schede aperte lo aggirano) ma toglie il caso più frequente.

**3 · Permettere la correzione.** Un pagamento sbagliato deve poter essere stornato. La via
più semplice e più onesta: accettare un importo negativo **solo dal titolare**, e scriverlo
nel registro come «correzione»:

```php
if ( 0.0 === $importo ) { wp_send_json_error( array( 'message' => 'Importo non valido.' ) ); }
if ( $importo < 0 && ! gs_is_titolare() ) {
	wp_send_json_error( array( 'message' => 'Solo il titolare può registrare una correzione.' ) );
}
```

**Compromesso da dichiarare:** con lo storno il totale può scendere sotto zero se qualcuno
sbaglia due volte. Aggiungere `max( 0, ... )` nasconderebbe l'errore invece di mostrarlo:
**meglio lasciare che il numero sbagliato si veda**, con il registro accanto che spiega
come ci si è arrivati.

### Priorità

**Alta, e prima del 1° ottobre.** Oggi il calendario ha pochi movimenti; da ottobre, con il
gioco lanciato e i corsi di gennaio in vendita, ogni acconto sbagliato è una telefonata.

---

## C2 · MEDIO — La sfoglina può annullare una prenotazione in qualunque stato, anche dopo il corso

**File:** `includes/calendario.php:620-637` (`gs_ajax_cal_disdici()`)
**Stato:** VERIFICATO

### Cosa succede oggi

L'endpoint controlla due cose — che sia una prenotazione, e che sia sua — e poi scrive:

```php
update_post_meta( $pid, 'gs_stato', $in_tempo ? 'annullato' : 'annullato_tardi' );
```

**Non guarda mai lo stato attuale.** Quindi una sfoglina può disdire:

- una prenotazione che il gestore ha già segnato **`rimborsato`** → lo stato diventa
  `annullato`, e **la registrazione del rimborso sparisce**;
- una che il gestore ha segnato **`no_show`** (assente) → il fatto di essere stata assente
  viene riscritto;
- una **`confermato` di un corso già avvenuto** → cancella il fatto di aver partecipato.

L'ultimo caso ha un seguito: `gs_cal_pren_puo_eliminare()` (riga 396) consente di togliere
dalla lista le prenotazioni `annullato`. Quindi si può **annullare e poi rimuovere** una
prenotazione di un corso davvero frequentato, facendola sparire dalla propria lista.

### Un limite al danno, verificato

**Il Registro Ufficiale non è toccato.** `gs_registro_da_attestati_calendario()`
(`registro.php:72`) seleziona le prenotazioni per il meta `gs_cal_attestato`, non per
`gs_stato`: chi ha l'attestato resta nel Registro anche se la prenotazione risulta
annullata. È una fortuna, non una scelta — ma vale.

### Correzione

Rifiutare la disdetta quando la prenotazione non è più annullabile:

```php
$stato_ora = get_post_meta( $pid, 'gs_stato', true ) ?: 'prenotato';
if ( ! in_array( $stato_ora, array( 'prenotato', 'confermato', 'lista_attesa' ), true ) ) {
	wp_send_json_error( array( 'message' => 'Questa prenotazione è già chiusa: per modificarla scrivi all\'Accademia.' ) );
}
$corso_data = $corso['data'] ? strtotime( $corso['data'] ) : 0;
if ( $corso_data && $corso_data < current_time( 'timestamp' ) ) {
	wp_send_json_error( array( 'message' => 'Il corso si è già svolto: non è più disdicibile.' ) );
}
```

**Compromesso:** una sfoglina che vuole davvero togliere dalla lista un corso passato dovrà
chiedere. È il verso giusto — quel record è anche la prova che ha partecipato.

---

## C3 · MEDIO — Una disdetta non avvisa nessuno, e il rimborso promesso non viene registrato

**File:** `includes/calendario.php:620-637` — VERIFICATO

Quando una sfoglina **prenota**, partono un'email e un avviso in Posta interna al gestore
(righe 613-616). Quando **disdice**, non parte niente: né email, né Posta interna, né
aeroplanino.

Il gestore scopre la disdetta solo se va a guardare. Con acconti da restituire e trattenute
a copertura spese, è la cosa che non deve passare inosservata.

**Peggio:** il messaggio alla sfoglina promette

> «Disdetta registrata entro i termini: l'eventuale acconto ti sarà restituito.»

ma **da nessuna parte viene scritto che c'è un rimborso da fare.** Lo stato diventa
`annullato`, non `rimborsato`; nessuna nota, nessuna riga in una lista di cose da fare.
Se il gestore non se ne accorge, quel rimborso non parte — e il sito l'ha promesso.

### Correzione

Un avviso in Posta interna, con l'informazione che serve per agire:

```php
	if ( function_exists( 'gs_inbox_crea' ) ) {
		$cu  = get_userdata( $uid );
		$acc = (float) get_post_meta( $pid, 'gs_acconto_versato', true );
		gs_inbox_crea(
			'Disdetta: ' . $corso['titolo'],
			( $cu ? $cu->display_name : 'Una sfoglina' ) . ' ha disdetto il corso del '
				. ( $corso['data'] ? date_i18n( 'j F Y', strtotime( $corso['data'] ) ) : '' ) . ".\n\n"
				. ( $in_tempo
					? ( $acc > 0
						? '⚠️ Disdetta NEI TERMINI con acconto di € ' . number_format( $acc, 2, ',', '.' ) . ' già versato: va restituito.'
						: 'Disdetta nei termini, nessun acconto versato.' )
					: 'Disdetta fuori termine: l\'acconto resta trattenuto a copertura spese.' ),
			array( 'from' => $cu ? $cu->display_name : 'Cliente', 'link_pren' => $pid )
		);
	}
```

Da fare **insieme a C2**: sono la stessa funzione, e sarebbe sciocco aprirla due volte.

---

## C4 · BASSO — Per sapere se un cliente ha già versato, il plugin carica tutte le prenotazioni del sito

**File:** `includes/calendario.php:441-449` (`gs_cal_ha_acconto()`), chiamata da
`gs_cal_mail_prenotazione()` alla riga 460 — VERIFICATO

```php
function gs_cal_ha_acconto( $uid ) {
	foreach ( gs_cal_prenotazioni() as $p ) {          // ← TUTTE, senza filtro
		if ( (int) get_post_meta( $p->ID, 'gs_cliente', true ) === (int) $uid ...
```

`gs_cal_prenotazioni()` senza argomenti fa un `get_posts` con `posts_per_page => -1` su
tutte le prenotazioni mai create, poi il filtro per cliente avviene **in PHP**, leggendo un
meta per ognuna.

Oggi non si nota. Fra due anni di corsi, con qualche centinaio di prenotazioni in archivio,
significa caricare tutto l'archivio a ogni prenotazione nuova — dentro la funzione che
manda l'email, cioè sul percorso che la sfoglina sta aspettando.

**Correzione:** chiedere al database quello che serve, invece di filtrare dopo.

```php
function gs_cal_ha_acconto( $uid ) {
	$q = get_posts( array(
		'post_type'      => 'gs_prenotazione',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => 'gs_cliente', 'value' => (int) $uid ),
			array( 'key' => 'gs_acconto_versato', 'value' => 0, 'compare' => '>', 'type' => 'DECIMAL(10,2)' ),
		),
	) );
	return ! empty( $q );
}
```

**Priorità bassa** — è un problema che arriva col tempo, non oggi. Ma è tre righe, e va
fatto quando si apre il file per C1.

---

## C5 · Conferma di una voce già nota

`gs_cal_totale_cliente()` (riga 431) ha lo **stesso identico difetto** di C4 — carica tutte
le prenotazioni e filtra in PHP. È già nell'elenco delle funzioni mai chiamate (voce **G1**
del primo documento): **cancellarla**, invece di correggerla. Se un giorno servirà, si
riscrive con la meta_query di C4.

---

## Una cosa fatta bene, che vale come modello

`gs_ajax_cal_prenota()` (riga 553) è **la funzione meglio scritta di tutto il plugin fra
quelle che ho letto**: sezione critica delimitata da un lock nominato di MySQL, uno per
corso; lock rilasciato su **tutti** i percorsi di uscita, compresi gli errori; rilasciato
prima delle email invece di tenerlo durante; e un commento che spiega perché esiste,
citando il test di carico che ha dimostrato il problema.

È esattamente lo standard a cui va portato `gs_ajax_cal_pagamento` — che sta 250 righe più
sotto e non ne ha nessuna.

---

## Riepilogo del blocco

| # | Cosa | Riga | Gravità |
|---|---|---|---|
| **C1** | **Doppio clic raddoppia il pagamento, e non si corregge** | 813 | **Alto** |
| C2 | Disdetta possibile in qualunque stato, anche a corso avvenuto | 620 | Medio |
| C3 | Disdetta silenziosa, rimborso promesso e non registrato | 620 | Medio |
| C4 | Carica tutte le prenotazioni per una domanda su una persona | 441 | Basso |
| C5 | `gs_cal_totale_cliente()` — stesso difetto, ma è morta: cancellare | 431 | Basso |

**C1, C2 e C3 vanno fatte prima del 1° ottobre.** C2 e C3 sono la stessa funzione: un solo
intervento. C4 e C5 si fanno quando si apre il file per C1.

---

## Cosa leggo adesso

In ordine, gli altri moduli che muovono soldi o stato: **`esperti.php`** (consulenze a
token, cioè crediti comprati), **`artigiani.php`** e **`scuole-cucina.php`** (abbonamenti
dei partner), **`area-pro.php`**, **`percorsi-lezioni.php`** e **`lezioni-video.php`**
(punti e badge automatici).

Consegno a blocchi come questo, così le correzioni possono cominciare senza aspettare la
fine.
