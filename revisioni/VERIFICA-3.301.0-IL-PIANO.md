# Verifica 3.301.0 — il Piano di Lavoro, provato

**Per Claude Code Ennio 2 — 28/08/2026**

Ho letto `includes/piano-lavoro.php` (54 KB, 10 funzioni) e le sue radici in
`control-panel.php` e `pannello-nuovo.php`. Le prove generali sono pulite:

```
99 file · 0 errori di sintassi
1404 funzioni definite · 0 chiamate ma non definite
346 azioni AJAX · 0 pulsanti che non fanno niente · 0 gestori senza funzione
0 ricorsioni per sbaglio
```

**Non c'e niente di rotto.** Quello che segue sono nove cose da sistemare, in
ordine di conseguenza. Tre sono da fare subito, sei possono aspettare.

---

## Prima: cosa avete fatto bene, perche non lo tocchiate

**La Tappa 1 e fatta come si doveva.** `gs_riepilogo_dati()` tiene le liste
*accanto* ai conteggi, non al posto loro. I 73 riquadri del pannello vecchio
non se ne accorgono. Il commento spiega il perche. Questa era la parte
delicata ed e andata.

**Il pannello vecchio e vivo.** `gs_pn_pagina()` delega al Piano solo se non
c'e `?classico`, e resta un link per tornarci. E' una soluzione migliore di
quella che avevo scritto io (una voce di menu in piu): meno confusione, e si
torna indietro in un secondo.

**La lineetta al posto della data inventata.** Le iscrizioni non hanno una
data d'attesa e voi avete messo `—` con il commento che spiega perche non si
usa la data di registrazione. **Esattamente giusto.**

**Avete tolto una voce dal mio disegno perche il campo non esiste** («la
lista della spesa dei corsi»), e l'avete scritto nel commento. Meglio una
voce in meno che una inventata: e la cosa piu importante che avete fatto in
questo pacchetto, e non e codice.

**Un solo pulsante acceso per coda** (`i === 0`), verde solo per «Approva»,
verbi diversi su ogni coda: tutti e sei corrispondono.

---

## DA FARE SUBITO

### 1. Una didascalia scritta da una sfoglina puo rompere il pannello

**E la sola cosa di sicurezza, e la correzione e una parola.**

`piano-lavoro.php:543-551` mette i dati dentro un blocco `<script>`:

```php
riquadri: <?php echo wp_json_encode( $riquadri, JSON_UNESCAPED_UNICODE ); ?>,
torre:    <?php echo wp_json_encode( $torre, JSON_UNESCAPED_UNICODE ); ?>,
```

`wp_json_encode()` **non trasforma il carattere `<`**. Quindi se in uno di
quei dati compare la sequenza `</script>`, il browser crede che il blocco sia
finito li, e tutto quello che segue viene letto come pagina invece che come
dati.

**E non e teorico: la strada c'e.** In `gs_pl_torre()`:

- riga ~318 — `'testo' => $m['testo']` (il corpo dei messaggi della posta
  interna), **non ripulito**;
- riga ~358 — `'testo' => $didasc`, la **didascalia del Tavolo di Lavoro**,
  scritta dalla sfoglina, **non ripulita**.

Una sfoglina che scrive `</script>` nella didascalia di una sua foto rompe il
pannello di Ennio. Se ci scrive qualcosa di peggio, quel qualcosa gira nel
browser di Ennio, che e amministratore.

**La correzione:**

```php
// JSON_HEX_TAG trasforma < e > : senza, un "</script>" dentro un dato
// scritto da una sfoglina chiude il blocco script e finisce nella pagina.
$flag = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
```

e usarlo in tutte e sei le `wp_json_encode()` della pagina. Gli accenti
restano leggibili (`JSON_UNESCAPED_UNICODE` resta).

**Come si prova**, in un minuto: metti come didascalia di una foto del Tavolo
la stringa `</script><b>prova</b>` e apri il Piano. Prima della correzione
vedi la scritta **prova** in grassetto in mezzo alla pagina; dopo, la vedi
scritta per intero dentro la riga, come deve essere.

### 2. Il lavoro risparmiato viene speso due volte

`gs_riepilogo_dati()` **non tiene memoria** di quello che ha calcolato, e il
Piano la chiama **due volte**:

- riga 167, dentro `gs_pl_riquadri()`, tramite `gs_riepilogo_liste()`;
- riga 281, dentro `gs_pl_polso()`, direttamente.

Tutto il caricamento — iscrizioni, conversazioni, ricette, testimonianze,
biografie, artigiani, scuole, corsi, sfoglie di oggi — **si rifa da capo la
seconda volta.** Cosi il risparmio della Tappa 1 viene restituito indietro.

E le chiamate sono anche altrove (`pannello-nuovo.php:303` e `:325`,
`admin.php:180`), quindi il problema non riguarda solo questa pagina.

**La correzione, tre righe**, in cima a `gs_riepilogo_dati()`:

```php
function gs_riepilogo_dati() {
    // Il calcolo e' pesante e la stessa pagina lo chiede piu' volte
    // (il Piano di Lavoro due volte: le liste e il polso). Si fa una
    // volta sola per caricamento.
    static $memo = null;
    if ( null !== $memo ) { return $memo; }
    ...
    return $memo = $riepilogo;   // al posto di return $riepilogo;
}
```

**Come si prova.** Stampa `get_num_queries()` in fondo alla pagina. Apri il
Piano e segna il numero; applica la memoria; riapri. **Deve scendere in modo
evidente.** Se non scende, la memoria non e stata presa.

### 3. Due code su sei accendono il pulsante sbagliato

L'idea di tutto il pannello e «il piu vecchio in cima, e il pulsante acceso e
suo». Ma `gs_pl_riquadri()` **non ordina niente**: si fida dell'ordine in cui
la lista arriva. E quell'ordine, per due code su sei, non e quello.

| coda | come arriva | va bene? |
|---|---|---|
| ricette | `orderby date ASC` | **si** |
| testimonianze | `orderby date ASC` | **si** |
| biografie | `get_users( orderby => display_name )` | **no — alfabetico** |
| conversazioni | nessun `orderby` → WordPress mette la piu recente per prima | **no — al contrario** |
| iscrizioni | nessuna data (lineetta) | — |
| artigiani | come arriva da `gs_art_elenco()` | da verificare |

Quindi: nella coda delle **biografie** il pulsante acceso e di chi ha il nome
che comincia per A. Nelle **conversazioni** e di chi ha scritto **per ultimo**,
cioe esattamente di chi ha aspettato **meno**.

**La correzione va messa in un posto solo**, dentro `gs_pl_riquadri()`, dopo
aver costruito `$voci` — cosi vale per tutte e sei le code e non si rompe se
domani qualcuno cambia una di quelle funzioni:

```php
// Il piu' vecchio in cima, sempre, qualunque ordine avesse la lista
// d'origine. Le righe senza data ("—") vanno in fondo: non sappiamo
// quanto aspettano, e metterle in testa sposterebbe l'attenzione su
// una cosa che nessuno ha misurato.
usort( $voci, function ( $a, $b ) {
    $ta = isset( $a['attesa_ts'] ) ? $a['attesa_ts'] : PHP_INT_MAX;
    $tb = isset( $b['attesa_ts'] ) ? $b['attesa_ts'] : PHP_INT_MAX;
    return $ta <=> $tb;
} );
```

Serve che `gs_pl_riga_da()` restituisca anche `attesa_ts` (il timestamp
grezzo, oltre alla scritta «3 giorni fa»), e **niente** per le righe senza
data. Non un vecchio timestamp, non zero: **la chiave proprio assente**, cosi
`PHP_INT_MAX` le manda in fondo.

---

## DA FARE, MA NON OGGI

### 4. Una lettura del database dentro il ciclo delle righe

`gs_pl_riga_da()` ha sopra questo commento:

> *«Nessuna lettura del database: usa solo i campi che l'oggetto porta gia
> con se.»*

e quattro righe sotto fa:

```php
$autore = get_the_author_meta( 'display_name', $riga->post_author );
```

che una lettura la fa, per ogni autore diverso.

**Il commento sbagliato e peggio del codice**: il codice si corregge, il
commento invece verra creduto dalla prossima persona che legge.

Si aggiusta prima del ciclo, una volta per coda:

```php
// Una sola lettura per tutti gli autori della coda, invece di una per riga.
cache_users( wp_list_pluck( $elenco, 'post_author' ) );
```

`cache_users()` e di WordPress ed e fatta apposta. Poi il commento va corretto
in *«nessuna lettura dentro il ciclo: gli autori sono gia stati caricati
tutti insieme»*.

Stessa cosa in `gs_pl_torre()` riga ~356 (li sono tre righe, quindi conta
poco, ma il principio e lo stesso).

### 5. Si disegnano tutte le righe, non le prime

`r.voci.forEach(...)` disegna **ogni** riga di **ogni** coda. E le code
arrivano lunghe: ricette e testimonianze fino a 100, conversazioni **senza
limite**. Con sei code piene, la pagina diventa un elenco lunghissimo — che e
l'opposto di «tutto in una schermata».

Nel disegno le righe visibili erano poche. Si fa cosi: **le prime 5**, e sotto
una riga *«e altre 12»* che apre la sezione. Il numero grande in cima al
riquadro dice gia quante sono in tutto.

### 6. Il testo e molto piu piccolo di quanto Ennio ha chiesto

Ennio ha chiesto un pannello «ben leggibile, che **non stanca gli occhi**».
Quello che c'e:

| | ora | dovrebbe |
|---|---|---|
| nome nella riga della coda | **13px** | **19px** |
| da quanto aspetta | **10,5px** | 14px |
| pulsante | 12,5px | 15px |
| testo di base | 15px | 16px |

**10,5 px e sotto la soglia di comodita per chiunque**, e quello e il dato che
Ennio guardera piu spesso di tutti. E' la cosa che notera per prima aprendo
il pannello, prima di ogni altro difetto di questo elenco.

Alzare i corpi allarghera i riquadri: e voluto. Meglio cinque righe che si
leggono che venti che si strizzano.

### 7. Le icone sono emoji

`var ICONE = { fare:'✅', richieste:'🧾', biografie:'📖', ricette:'🍝', … }`

Le emoji **cambiano faccia su ogni dispositivo**: la pentola di Windows non e
quella del Mac, e su Android sono altre ancora. A schermo grande sembrano
appiccicate sopra il disegno invece di farne parte.

Nel disegno (`design/pannello/Piano.dc.html`) ci sono **disegni SVG**, tutti
dello stesso stile e dello stesso spessore di linea: si possono copiare da li.
Non e urgente, ma va fatto prima che il pannello diventi quello vero.

### 8. Manca la versione scura

Nel pacchetto non c'e (`prefers-color-scheme` non compare da nessuna parte).
Ennio lavora anche di sera, e il disegno scuro esiste gia:
`design/pannello/anteprime/PianoScuro.png`.

### 9. Manca la pastiglia «e altre 2 cose sue»

Era la Tappa 4 del documento, e non c'e.

La stessa persona compare in piu code contemporaneamente — ha mandato una
ricetta, aspetta la biografia, e ti ha scritto. Oggi la incontri tre volte in
tre punti diversi e non te ne accorgi.

**Con le liste gia in mano costa dieci righe e nessuna lettura in piu:**

```php
$conta = array();
foreach ( $liste as $coda => $righe ) {
    foreach ( $righe as $r ) {
        $uid = isset( $r->ID ) && isset( $r->display_name ) ? $r->ID
             : ( isset( $r->post_author ) ? (int) $r->post_author : 0 );
        if ( $uid ) { $conta[ $uid ][] = $coda; }
    }
}
// chi ha piu' di una voce prende la pastiglia
```

---

## Due cose piu piccole, gia che ci siete

**Il conto dei giorni della sfida ha di nuovo il difetto di luglio.**
`gs_pl_cose_da_fare()`, sezione 2:

```php
$fine   = strtotime( get_post_meta( $sfida->ID, 'gs_data_fine', true ) );  // mezzanotte
$giorni = floor( ( $fine - $ora ) / DAY_IN_SECONDS );                       // $ora ha l'orario
```

Una mezzanotte meno un orario: **il risultato cambia a seconda dell'ora in cui
si apre il pannello.** Alle 23 della sera prima, una sfida che chiude domani
scrive «chiude oggi». E' lo stesso difetto corretto in `abbonamenti.php` con
la 3.294.0, ricomparso in codice nuovo. Due date passate dalla stessa
funzione, e `round` non `floor`:

```php
$giorni = (int) round( ( strtotime( $data_fine ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS );
```

Stessa cosa nella sezione 1 (i corsi): li le due date sono gia tutt'e due
mezzanotti — giusto — ma c'e `floor`, e due volte l'anno un giorno dura 23 o
25 ore e `floor` sbaglia di uno. `round` no.

**Il doppio ribaltamento del Tavolo di Lavoro.** `gs_pl_torre()`, riga ~351:

```php
$senza = array_reverse( array_slice( array_reverse( $senza ), 0, 3 ) );
```

Il commento sopra dice «la piu VECCHIA in cima». Il codice fa l'opposto: la
lista arriva dalla piu recente, il primo `array_reverse` la mette dalla piu
vecchia, `array_slice` prende le tre piu vecchie — **e il secondo
`array_reverse` le rimette al contrario**, cosi la piu vecchia delle tre
finisce in fondo. Si toglie il secondo `array_reverse`.

---

## L'ordine, e cosa mandare

1. **La numero 1** (`JSON_HEX_TAG`), da sola. Prova con la didascalia
   `</script><b>prova</b>`. **Zip.**
2. **La 2** (la memoria) e **la 3** (l'ordinamento). Prova con
   `get_num_queries()`, e guarda che nelle biografie e nelle conversazioni il
   pulsante acceso sia sulla riga piu vecchia. **Zip — e qui fermati.**
3. Poi 4, 5, 6, e le due piu piccole.
4. Alla fine 7, 8, 9.

**Fermati dopo il punto 2 e manda lo zip.** Con l'ordinamento giusto e il
testo ancora piccolo, Ennio puo gia dire se il pannello gli serve — ed e
meglio saperlo prima di rifare le icone.

## Una cosa da non fare

**Non togliete `?classico`.** Finche Ennio non avra usato il Piano per un
mese, la strada per tornare al pannello di prima deve restare aperta. E' la
sola ragione per cui questo pacchetto si puo installare senza paura.
