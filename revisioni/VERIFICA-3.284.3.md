# 3.284.3 verificata — le due cose applicate. Ma la correzione del nastro ha un caso che la rompe.

Diff contro la 3.284.1. Sintassi pulita, **nessun file di prova nel pacchetto**, e i due
loghi sono davvero dentro `assets/img/` (li ho visti nell'elenco dei file nuovi, non solo nel
codice).

**Le due voci del documento precedente sono chiuse:** loghi dentro il plugin, indirizzo di
prova da `get_option( 'admin_email' )`. **E anche il testo del pulsante è stato cambiato** —
diceva ancora *«Manda la mail a info@lentium.it»*, e sarebbe rimasto lì a nominare un
indirizzo che non usa più nessuno.

**La diagnosi sul terzo sponsor è giusta**, e vale la pena dirlo: il difetto era che la lista
intervallata veniva costruita **una volta sola** e poi **disegnata due volte** — quindi con
due voci si generavano solo due caselle da sponsor, e Molini Pivetti non compariva mai.
**Trovato guardando il nastro dal vivo dopo l'installazione**, che è il modo in cui si
trovano queste cose.

**Ma la correzione ha un caso che la rompe**, e non è teorico.

---

# N1 · MEDIO — Il numero di ripetizioni deve essere pari, o il nastro sobbalza

**File:** `includes/nastro-vetrine.php:104` — VERIFICATO

```php
	$giri = max( 2, count( $sponsor ) > 0 && count( $voci ) > 0 ? (int) ceil( count( $sponsor ) / count( $voci ) ) : 2 );
```

## Perché il «due» di prima non era un numero a caso

`assets/css/gaming.css:2213`:

```css
@keyframes gs-nastro-scorre { from { transform: translateX(0); } to { transform: translateX(-50%); } }
```

**Il nastro scorre esattamente di metà della propria larghezza, e poi riparte da zero.**
Perché il salto non si veda, **le due metà devono essere identiche** — ed è per questo che
il codice vecchio disegnava la lista **due volte**. Non era una scelta estetica: era il
meccanismo.

Lo conferma anche il JavaScript (`gaming.js:8117`):

```js
	var durata = ( larghezza / 2 ) / pixelAlSecondo;
```

**Anche la velocità è calcolata sulla metà.**

## Il caso che rompe

`$giri` adesso può valere **qualsiasi numero ≥ 2**, e con un numero **dispari** le due metà
non coincidono più: il salto a `-50%` cade **in mezzo a una copia**, e a ogni giro il nastro
**sobbalza visibilmente.**

Quando succede: `ceil( 3 sponsor / N voci ) = 3` → **quando nel nastro c'è una voce sola.**

**E una voce sola non è un caso di scuola.** Il nastro mostra solo chi ha la vetrina attiva.
Il commento appena scritto dice che la situazione trovata dal vivo era *«appena 2 voci
(Bruno Cingolani e Rina Poletti)»* — **una è a un passo.** E dopo il reset si riparte da
zero: le prime settimane il nastro avrà una voce, o nessuna.

**Con quattro sponsor invece va bene** (`ceil(4/1) = 4`, pari): è proprio il tre che casca
male.

## Correzione — due righe

```php
	$giri = max( 2, (int) ceil( count( $sponsor ) / max( 1, count( $voci ) ) ) );
	// Deve essere PARI: l'animazione scorre di -50% e riparte (gaming.css:2213),
	// quindi le due metà del nastro devono essere identiche — è la ragione per
	// cui prima si disegnava la lista esattamente due volte. Con un numero
	// dispari il salto cade in mezzo a una copia e il nastro sobbalza a ogni
	// giro. Succede con 3 sponsor e una voce sola, che dopo il reset è
	// esattamente come ripartirà il sito (26/08/2026).
	if ( 0 !== $giri % 2 ) { $giri++; }
```

**Il `max( 1, … )` nel divisore** serve anche a togliere di mezzo il controllo doppio: se
`$voci` fosse vuoto la funzione è già uscita prima (riga 83), ma così la riga si legge senza
doverlo ricordare.

**Come si prova:** togli tutte le voci dal nastro tranne una (dagli esclusi nel pannello, o
disattivando le vetrine su guru2), guarda il nastro scorrere **per un giro intero**, e vedi
se al momento di ripartire fa uno scatto. Con la correzione non lo fa.

---

## Una cosa piccola sui cartellini «Maestro» e «Maestra»

```php
	$tag_personalizzati = array(
		6 => 'Maestro',
		9 => 'Maestra',
	);
```

**I due numeri sono identificativi di account.** Va bene, e il commento nomina le persone,
che è la cosa giusta da fare.

**L'unico caso in cui si rompe** è se un giorno quegli account venissero ricreati da zero:
i numeri passerebbero a due persone qualsiasi, che si ritroverebbero il cartellino «Maestro»
senza motivo. **Il reset previsto non li ricrea** — nasconde, non cancella — quindi oggi non
succede.

**Non c'è niente da fare adesso.** Ma se un domani si volesse renderla solida, la strada è
un contrassegno sulla scheda della persona (`gs_titolo_onorario` **esiste già** come meta
utente) invece di un numero scritto nel codice.

---

# Riepilogo

| | Stato |
|---|---|
| Loghi dentro il plugin | ✅ file presenti in `assets/img/` |
| Indirizzo di prova da `admin_email` | ✅ **e il testo del pulsante aggiornato** |
| Diagnosi del terzo sponsor mancante | ✅ giusta, e trovata guardando dal vivo |
| **Ripetizioni pari** | 🔴 **due righe — con una voce sola il nastro sobbalza** |
| Cartellini per identificativo | ⚠ solido finché quegli account non si ricreano |
| M1 (`sfoglia-misurata.php`) | ⏸ sempre in coda, sempre una riga |

**A parte N1, niente va rifatto.**

**N1 e M1 stanno bene insieme nello stesso giro:** sono due righe in due file, e sono le
uniche due cose aperte in questo momento.
