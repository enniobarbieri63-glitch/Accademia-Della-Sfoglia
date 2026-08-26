# 3.284.4 verificata — applicate bene. Ma la mia correzione N1 era incompleta, e me ne accorgo solo adesso.

Diff contro la 3.284.3: tocca solo `nastro-vetrine.php`, `sfoglia-misurata.php`, il file
principale e il changelog. `php -l` pulito, **nessun file di prova nel pacchetto**.

**M1 è chiusa e giusta.** I punti de «La Sfoglia Misurata» sono adesso dentro il controllo
del badge, con il commento che rimanda a G1. Quel file è finalmente identico al suo gemello.

**N1 è applicata esattamente come l'avevo scritta** — e il commento è più completo del mio,
perché cita **sia** la riga del CSS **sia** la funzione JavaScript che calcola la velocità.
Chi lo legge fra un anno ha tutte e due le prove sotto gli occhi.

**Ho simulato l'aritmetica su sei casi**: `$giri` è sempre pari, e tutti e tre gli sponsor
compaiono sempre.

**Poi ho scritto il nastro pezzo per pezzo, e ho visto una cosa che non avevo previsto.**

---

# N2 · BASSO — Le due metà non sono ancora identiche, e la parità da sola non basta

**File:** `includes/nastro-vetrine.php:110-121` — VERIFICATO scrivendo il risultato a mano

Con **una voce sola (A) e tre sponsor**, `$giri` diventa 4 e il nastro risulta:

```
[A][s0][A][s1][A][s2][A][s0]
 └────── metà 1 ──────┘└─── metà 2 ───┘
      A,s0,A,s1            A,s2,A,s0
```

**Le due metà hanno gli stessi ingombri ma non gli stessi loghi.**

L'animazione scorre fino a `-50%` e **rientra di scatto a zero**. In quel momento chi guarda
vede il contenuto che stava a metà tornare al punto di partenza — e siccome le due metà
contengono sponsor diversi, **i loghi cambiano di colpo.**

**Non è il sobbalzo di prima**: le pillole non si spostano, hanno la stessa larghezza e le
stesse posizioni. **È un lampo**: un logo che diventa un altro logo. Molto meno grave, ma si
vede.

## E questo l'ho introdotto io

Il codice **vecchio** — quello con la lista costruita una volta e disegnata due — aveva le
due metà **identiche per costruzione**: era letteralmente la stessa lista, stampata due
volte. **Era sbagliato per un'altra ragione** (non faceva mai comparire il terzo sponsor),
ma su questo era giusto.

**La mia N1 ha sistemato la geometria e non l'identità.** La parità fa combaciare gli
ingombri; non fa combaciare *il contenuto*, che è quello che serve davvero perché il giro
non si veda.

**È un mio errore di analisi**, ed è dello stesso tipo di quelli che sto trovando in giro:
ho guardato la condizione (`-50%`) senza scrivere il risultato.

## Correzione — costruire una metà sola e ripeterla due volte

```php
	// Quante volte ripetere le voci DENTRO UNA METÀ, perché la rotazione
	// faccia in tempo a mostrare tutti gli sponsor almeno una volta.
	$giri_meta = max( 1, (int) ceil( count( $sponsor ) / max( 1, count( $voci ) ) ) );

	// Si costruisce UNA metà e la si ripete due volte identica: l'animazione
	// scorre di -50% e rientra a zero (gaming.css:2213), quindi il contenuto
	// a metà nastro deve essere lo stesso di quello iniziale — non basta che
	// gli ingombri combacino, devono combaciare i loghi. Con una metà
	// costruita a sé e ripetuta, il rientro è invisibile per costruzione,
	// come lo era nel codice originale (26/08/2026).
	$voci_meta = array();
	for ( $g = 0; $g < $giri_meta; $g++ ) { $voci_meta = array_merge( $voci_meta, $voci ); }
	$meta = gs_nastro_intervalla( $voci_meta, $sponsor );
	$voci_intervallate = array_merge( $meta, $meta );
```

e **la stessa cosa per la fila di destra**, con `array_reverse( $voci )`.

**Con questa versione la variabile `$giri` e il controllo sulla parità spariscono**: non
servono più, perché la ripetizione ×2 è esplicita nell'ultima riga. **Toglili entrambi**,
altrimenti restano due meccanismi che fanno la stessa cosa.

### Cosa dà, sui casi di prima

| voci | ripetizioni per metà | una metà | sponsor diversi |
|---|---|---|---|
| 1 | 3 | `A s0 A s1 A s2` | 3/3 |
| 2 | 2 | `A s0 B s1 A s2 B s0` | 3/3 |
| 3 | 1 | `A s0 B s1 C s2` | 3/3 |
| 10 | 1 | 10 voci + 10 sponsor a rotazione | 3/3 |

**In tutti i casi le due metà sono identiche**, e in tutti gli sponsor compaiono tutti.

### Come si prova

**Uguale a prima, ma guardando i loghi invece delle posizioni:** una voce sola nel nastro,
si sta a guardare **il momento in cui il nastro rientra**. Con la correzione, in quell'attimo
**non cambia niente** — né posizione né logo.

---

## Priorità: bassa, e dico perché

**Non è un difetto che qualcuno segnalerà.** Un logo che cambia in un lampo, una volta ogni
quarantadue secondi, su una fascia che scorre: la maggior parte delle persone non lo nota, e
chi lo nota pensa che sia voluto.

**Ma è due righe, ed è il momento giusto**, perché il codice è aperto adesso e fra un mese
nessuno si ricorderà perché quella ripetizione doveva essere pari.

**Se preferisci lasciarla così, va bene**: il difetto grosso — il sobbalzo di posizione — è
chiuso davvero, e questo è un residuo estetico. **Ma allora togliamo il commento sulla
parità**, che a quel punto spiegherebbe una regola più debole di quella che serve.

---

# Riepilogo

| | Stato |
|---|---|
| **M1** · punti dentro il controllo del badge | ✅ chiusa, file allineato al gemello |
| **N1** · ripetizioni pari | ✅ applicata com'era scritta, con un commento migliore del mio |
| **N2** · le due metà non sono identiche | ⚠ **due righe — errore mio di analisi, non vostro** |
| File di prova fuori dal pacchetto | ✅ |

**A parte N2, niente va rifatto. Si può installare.**

E N2 non blocca nulla: **è l'unica cosa aperta in questo momento**, ed è la più piccola di
tutte quelle che abbiamo visto finora.
