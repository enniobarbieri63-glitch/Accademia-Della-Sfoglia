# Il modulo Sponsor — e cosa Ennio ha già senza saperlo

**Per Claude Code Ennio 2 — 26/08/2026, scritto su 3.292.0**

Ennio chiede un modulo per aggiungere e togliere gli sponsor a piacimento, e lo stesso per la Vetrina, le scuole e gli artigiani.

**Per tre delle quattro cose che ha chiesto, ce l'ha già.** Manca solo per gli sponsor.

---

## Quello che c'è già — da dire a Ennio, non da costruire

`artigiani.php` e `scuole-cucina.php` hanno **nove comandi ciascuno**, completi:

| | |
|---|---|
| `gs_art_crea` / `gs_scu_crea` | **aggiungere** una vetrina nuova |
| `gs_art_salva` / `gs_scu_salva` | modificare tutto: testo, foto, video, indirizzo |
| `gs_art_logo_rimuovi`, `gs_art_media_rimuovi` | togliere logo e immagini |
| `gs_art_modera` | approvare o rifiutare una modifica |
| **`gs_art_pagamento`** | **registrare un bonifico** con importo e data, e spostare la scadenza |
| `gs_art_cestina` / `gs_art_ripristina` | **togliere** e rimettere, senza perdere niente |
| `gs_art_controlla_scadenze` | il promemoria automatico prima che scada |

**Ennio può già fare tutto questo oggi, dal Pannello di Controllo.** Se non lo sta facendo, il problema non è che manchi: è che non ha trovato dove sta. Vale la pena mostrarglielo prima di costruire qualunque cosa — magari la sua domanda era proprio questa, posta in un altro modo.

E la vetrina di ogni partner **si spegne da sola** quando la scadenza passa: `gs_art_attivo()` la nasconde dalla sezione pubblica finché non registri un nuovo pagamento. Quella parte funziona già.

---

## Quello che manca davvero: gli sponsor del nastro

`nastro-vetrine.php:89-101`. Tre sponsor scritti dentro il codice:

```php
$sponsor = array(
    array( 'nome' => 'Mulino Marino',  'foto' => GS_URL . 'assets/img/mulino-marino-logo.png', … ),
    array( 'nome' => 'Molino Caputo',  … ),
    array( 'nome' => 'Molini Pivetti', … ),
);
```

Niente pannello, niente pagamenti, niente scadenza. E i **loghi stanno dentro il plugin** (`assets/img/`): per aggiungerne uno bisogna metterci un file dentro e rifare il pacchetto.

Quest'ultima cosa è peggio di come sembra: la procedura di installazione che usate è *«Disattiva → **Elimina il vecchio plugin** → carica lo zip»*. **Un logo aggiunto a mano dentro la cartella del plugin sparisce al primo aggiornamento**, e con lui lo sponsor che l'aveva pagato.

---

## Come va costruito — e come NON va costruito

### Non un terzo gemello

`artigiani.php` e `scuole-cucina.php` sono già due copie quasi identiche dello stesso modulo, ~900 righe ciascuna. **Non fatene una terza.**

Uno sponsor ha bisogno di molto meno di un artigiano: non ha una biografia da approvare, non ha una galleria di foto e video, non ha un pannello di autogestione, non riceve messaggi dal modulo contatti. Ha **quattro cose**: un nome, un logo, un indirizzo web, e un pagamento con una scadenza.

Un modulo nuovo e piccolo, sulle **200 righe**, non 900.

### Il debito che state per contrarre, detto chiaro

La cosa giusta, guardando avanti, sarebbe **un solo modulo partner** con un campo «tipo» — artigiano, scuola, sponsor — invece di due copie più una terza cosa simile. È evidente, ed è la strada che qualcuno dovrà fare prima o poi.

**Non fatelo adesso.** Riscrivere due moduli che funzionano, a una settimana dalle iscrizioni, per un guadagno che si vedrà fra sei mesi, è il genere di scelta che fa saltare una scadenza. Costruite il modulo piccolo, e **scrivete nel commento in cima al file che il debito esiste**, così chi ci tornerà lo sa senza doverlo riscoprire.

---

## La specifica

### Come si conservano

Un tipo di contenuto `gs_sponsor`, con la stessa forma di `gs_artigiano`:

| meta | cosa contiene |
|---|---|
| `gs_sponsor_logo` | **l'id dell'allegato** nella Libreria Media — non un percorso dentro il plugin |
| `gs_sponsor_url` | l'indirizzo del sito |
| `gs_sponsor_scadenza` | `AAAA-MM-GG` fino a quando è pagato |
| `gs_sponsor_pagamenti` | lo storico dei bonifici `[ {data, importo, note} ]`, **mai sovrascritto, solo aggiunto** |

**Il logo nella Libreria Media e non nel plugin**: è il punto che risolve il problema vero. Un'immagine caricata dal pannello resta lì anche quando il plugin viene eliminato e reinstallato.

### Il pannello

Una tabella sola, con sotto una riga per aggiungerne uno nuovo:

```
Sponsor              Logo    Sito                      Scadenza     Stato
──────────────────────────────────────────────────────────────────────────────
Mulino Marino        [img]   mulinomarino.it           31/12/2026   ✅ attivo
Molino Caputo        [img]   mulinocaputo.it           15/09/2026   ⚠️ fra 20 giorni
Molini Pivetti       [img]   molinipivetti.it          02/08/2026   ❌ scaduto
                                                     [+ Registra bonifico]  [🗑]
──────────────────────────────────────────────────────────────────────────────
[ Nome ] [ Carica logo ] [ Indirizzo sito ]                        [Aggiungi]
```

- **«Registra bonifico»** apre lo stesso modulino di `artigiani.php:645`: importo, data, note, e la nuova scadenza. **Copiatelo da lì**, compresa la protezione contro il doppio invio dello stesso importo nello stesso giorno (`artigiani.php:848`) — è già stata pensata una volta.
- Il **cestino**, non la cancellazione: uno sponsor tolto si può rimettere, e lo storico dei bonifici non si perde mai.
- Il **prezzo di listino** dalle impostazioni, già scritto nel campo dell'importo, così non lo ridigita ogni volta.

### Il nastro legge dal database

`nastro-vetrine.php:89` diventa una riga:

```php
$sponsor = gs_sponsor_attivi();
```

dove:

```php
/**
 * Gli sponsor da mostrare nel nastro: solo quelli con la scadenza non
 * passata. Uno sponsor che ha smesso di pagare esce dal nastro da solo il
 * giorno dopo la scadenza, senza che nessuno debba ricordarsene — è la
 * ragione per cui questo modulo esiste (Ennio, 26/08/2026).
 */
function gs_sponsor_attivi() { … }
```

**È questa la funzione che vale davvero il lavoro.** Il pannello è comodo; questa riga è quella che evita di trovarsi un logo di chi non paga più che gira sul sito per mesi.

### Il promemoria prima della scadenza

Copiate `gs_art_controlla_scadenze()` (`artigiani.php:912`): tre avvisi — preavviso, ultimo giorno, scaduto — con il contrassegno `scadenza|fase` che impedisce di rimandare lo stesso avviso ogni giorno. **Non riscrivetela: è già stata sistemata bene il 25/08.**

### I tre sponsor di oggi

Alla prima attivazione, se non esiste nessuno sponsor nel database, createli dai tre scritti nel codice, con i loghi già presenti caricati nella Libreria Media. **Una volta sola, con un contrassegno in un'opzione** perché non si ricrei a ogni aggiornamento — la stessa regola del contrassegno-prima-degli-effetti.

Lasciate la scadenza **vuota** su tutti e tre: sarà Ennio a metterla registrando il primo bonifico. Una scadenza inventata da noi li farebbe sparire dal nastro da soli, ed è l'ultima cosa che vogliamo.

---

## E già che siete lì: il difetto del nastro

Mentre riscrivete quel pezzo, sistemate anche **N2**, che è aperto da giorni ed è un errore mio.

`nastro-vetrine.php:104-122` costruisce il nastro ripetendo le voci per un numero di «giri», poi arrotonda il numero a pari perché l'animazione scorre di `-50%`. **La parità fa combaciare la geometria ma non il contenuto**: le due metà contengono sponsor diversi, quindi al momento del riavvolgimento i loghi saltano.

La correzione è più semplice del codice attuale: **si costruisce una metà sola e la si raddoppia.**

```php
// Una metà, e poi la stessa metà due volte: l'animazione scorre di -50%,
// quindi al riavvolgimento il nastro deve ritrovarsi lo STESSO contenuto,
// non solo la stessa larghezza. Prima si arrotondava il numero di giri a
// pari, che fa combaciare la geometria ma non ciò che c'è dentro: con una
// voce e tre sponsor le due metà erano diverse e i loghi saltavano.
$meta = gs_nastro_intervalla( $voci_ripetute, $sponsor );
$voci_intervallate = array_merge( $meta, $meta );
```

Spariscono `$giri` e il controllo sulla parità: due righe al posto di sei.

---

## Come si prova

1. Aggiungere uno sponsor dal pannello con un logo caricato → **compare nel nastro**.
2. Metterlo nel cestino → **sparisce dal nastro**, e lo storico dei bonifici c'è ancora.
3. Scadenza a ieri → **esce dal nastro da solo**, senza toccare niente.
4. Registrare un bonifico → **rientra**, con la scadenza nuova.
5. Aggiornare il plugin (elimina e ricarica) → **i loghi ci sono ancora** e gli sponsor pure. *È la prova che conta di più: è il problema che questo modulo esiste per risolvere.*
6. Guardare il nastro girare per due giri interi → **nessun salto dei loghi al riavvolgimento** (N2).
7. `prova.sh`.

---

## Il conto

| | |
|---|---|
| Il modulo sponsor (~200 righe, copiando da `artigiani.php`) | mezza giornata |
| Il nastro che legge dal database | poche righe |
| La migrazione dei tre di oggi | poche righe |
| N2, il difetto del nastro | due righe |
| Il prezzo di listino nelle impostazioni | mezz'ora |

**Circa una giornata**, ed è tutto lavoro di copia da codice che funziona già.

**Ma resta dietro alle email di scadenza, agli undici testi, alla mail di benvenuto e al reset.** Quelle hanno settembre addosso; questa no — a meno che uno sponsor non stia per scadere adesso, e questo lo sa solo Ennio.
