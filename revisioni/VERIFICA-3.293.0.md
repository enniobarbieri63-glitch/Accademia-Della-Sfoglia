# Verifica di 3.293.0

**Il lavoro è fatto bene. Ma c'è una cosa da correggere, ed è nella funzione che avete appena riscritto.**

---

## ⚠️ Il calcolo dei giorni: corretto nel test, non nel codice

Nel rapporto scrivete di aver sistemato *«un calcolo di giorni che dipendeva dall'ora del giorno»*, precisando che era nel test e non nel codice.

**Nel test sì. Nel codice è ancora lì**, `abbonamenti.php:168-177`:

```php
$oggi = current_time( 'timestamp' );          // adesso, CON l'ora
…
$ts   = strtotime( $scadenza . ' 00:00:00' ); // una mezzanotte
$giorni_mancanti = (int) floor( ( $ts - $oggi ) / DAY_IN_SECONDS );
```

Un momento con l'ora, meno una mezzanotte. È **P3**, lo stesso difetto trovato tre volte su questo progetto — e stavolta è nella funzione che decide quale delle tre mail parte.

### Cosa succede davvero — conti fatti, non stimati

Scadenza al **10 settembre**:

| il cron gira | giorni calcolati | mail che parte |
|---|---|---|
| 2 set | 7 | preavviso ← **8 giorni prima, non 7** |
| 3 set (7 giorni prima) | 6 | preavviso *(saltata: già mandata il 2)* |
| 9 set | 0 | «ultimo giorno» |
| **10 set — il giorno stesso** | **−1** | **«la prova è finita»** |
| 11 set | −2 | «la prova è finita» *(saltata)* |

E il cancello, che confronta due mezzanotte, dice un'altra cosa:

```
il 10 settembre → NON congelata, è ancora dentro
il 11 settembre → congelata
```

**Il 10 settembre la mail le dice che è finita e il sito la fa entrare.**

O legge la mail e non entra — e perde l'ultimo giorno che le spettava. O entra e trova tutto aperto, e non capisce più a cosa credere. In tutti e due i casi succede **il giorno che conta di più** di tutto il modello dei trenta giorni.

E c'è la seconda faccia: **il giorno vero della scadenza non riceve nessuna mail giusta.** «Ultimo giorno» arriva il 9, e il 10 arriva quella sbagliata.

### La correzione

La solita, la stessa delle altre tre volte: **due date attraverso la stessa funzione.**

```php
// Due DATE, non un momento contro una mezzanotte: current_time('timestamp')
// porta con sé l'ora, quindi il 10 settembre alle 09:00 mancano "−1 giorni"
// alla mezzanotte del 10 e parte la mail sbagliata — mentre il cancello,
// che confronta due mezzanotte, la considera ancora dentro. Le due parti
// della stessa scadenza devono contare i giorni allo stesso modo.
// round() e non floor(): con l'ora legale un giorno dura 23 o 25 ore.
$giorni_mancanti = (int) round(
    ( strtotime( $scadenza ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS
);
```

Provata con gli stessi giorni:

| il cron gira | giorni | mail | il cancello dice |
|---|---|---|---|
| 3 set | 7 | preavviso ✅ *sette giorni prima* | dentro |
| 9 set | 1 | «scade oggi o domani» ✅ | dentro |
| **10 set** | **0** | **«scade oggi o domani»** ✅ | **dentro** ✅ |
| 11 set | −1 | «la prova è finita» ✅ | **fuori** ✅ |

**Adesso la mail e il cancello dicono la stessa cosa tutti i giorni.** Prima si contraddicevano esattamente il giorno della scadenza.

---

## ✅ Il resto è fatto bene

**Nove mail nel registro** (erano tre): `benvenuto`, `conferma_email`, `richiesta_non_accolta`, `scadenza_preavviso`, `scadenza_ultimo`, `scadenza_scaduto`, più le tre di prima. Tutte modificabili e provabili.

**Il pannello era davvero già generico** — l'ho verificato: le voci nuove compaiono da sole, nessuna interfaccia scritta. Era la scommessa del documento e ha tenuto.

**Le due date della prova ci sono**, `{{DATA_INIZIO}}` e `{{DATA_FINE}}`, in tre mail (benvenuto, preavviso, ultimo), sostituite a `mail-area-riservata.php:636-637` dai meta veri.

**E la prova inventa date plausibili invece di mostrare un segnaposto vuoto** (riga 586-596). Era una richiesta piccola del documento e non solo è stata fatta: è stata fatta **spiegando perché**, nel commento. È il modo giusto.

**«Ripristina il testo originale» c'è.** Serve il giorno in cui si cancella mezza frase per sbaglio.

**La fase «scaduto» adesso scrive anche alla sfoglina**, non più solo a Ennio in Posta interna — con il ragionamento nel commento: *«con la scadenza automatica ora vera, non solo un promemoria di un aggiornamento mancato»*. È esattamente il punto A6.5, e il motivo è quello giusto.

**Il contrassegno resta scritto prima di mandare.**

### La batteria

```
97 file — 0 errori di sintassi
1366 funzioni — 0 chiamate e non definite — 0 doppie
342 azioni AJAX ↔ 289 dal JavaScript — 0 pulsanti morti, 0 gestori rotti
funzioni che chiamano se stesse per sbaglio: 0
```

---

## Una cosa piccola, per quando ci tornate

**Il pannello non avverte se si cancella un segnaposto.** Se Ennio, riscrivendo la mail di benvenuto, toglie `{{DATA_FINE}}`, la sfoglina non sa più quando finisce la prova — e nessuno se ne accorge, perché la mail parte lo stesso e sembra a posto.

Basta una riga sotto il riquadro del testo: *«Se togli {{DATA_FINE}} la data non comparirà»*, con l'elenco dei segnaposto usati da quella mail. Non è urgente: lo diventa il giorno in cui Ennio riscrive davvero quei testi.

---

## In due righe

**Il pacchetto è buono e la tappa 1 è chiusa davvero.** Da correggere una cosa sola, ma va corretta prima che il cancello dei trenta giorni entri in funzione: **la mail e il cancello devono contare i giorni allo stesso modo**, o il giorno della scadenza si contraddicono davanti alla sfoglina.
