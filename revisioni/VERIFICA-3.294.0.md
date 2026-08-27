# Verifica di 3.294.0

**P3 è chiuso, e la correzione è quella giusta.** Resta una svista di una riga, cosmetica.

---

## ✅ Il calcolo dei giorni

`abbonamenti.php:185`:

```php
$giorni_mancanti = (int) round( ( strtotime( $scadenza ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS );
```

Due date attraverso la stessa funzione, `round()` e non `floor()`. Esatto.

**Provato giorno per giorno**, con scadenza al 10 settembre, mettendo a confronto le due parti che prima si contraddicevano:

| il cron gira | giorni | la mail dice | il cancello dice |
|---|---|---|---|
| 3 set | 7 | preavviso | ancora dentro |
| 9 set | 1 | ultimo giorno | ancora dentro |
| **10 set — il giorno stesso** | **0** | **ultimo giorno** | **ancora dentro** ✅ |
| 11 set | −1 | la prova è finita | **fuori** ✅ |

**Adesso dicono la stessa cosa tutti i giorni.** Prima, il 10 settembre, la mail annunciava la fine mentre il sito la faceva ancora entrare.

E il preavviso parte davvero a sette giorni, non a otto.

**Il commento accanto spiega il perché**, non solo il cosa, e cita il caso concreto. È il modo giusto: chi ci tornerà fra un anno capirà perché quella riga non va "semplificata".

### Due cose che ho controllato apposta

**`if ( false === $ts ) { continue; }` è stato tolto**, e va bene: alla riga 172 c'è ancora `preg_match( '/^\d{4}-\d{2}-\d{2}$/', $scadenza )` prima del calcolo, quindi la data che arriva a `strtotime()` è sempre ben formata. Il controllo tolto era già coperto.

**Il commento in cima alla funzione è stato riscritto.** Prima diceva che la scadenza *«è solo un PROMEMORIA: non spegne da sola l'accesso»* — non era più vero da quando esiste `gs_sfoglina_congelata()`. **Un commento che mente è peggio di nessun commento**, e averlo notato senza che nessuno lo chiedesse è la cosa migliore di questo pacchetto.

## ✅ L'elenco dei segnaposto — fatto meglio di come l'avevo chiesto

Avevo suggerito una riga con i segnaposto di quella mail. È stato fatto **leggendoli dal testo vero**:

```php
preg_match_all( '/\{\{[A-Z_]+\}\}/', $def['corpo'], $segnaposto_trovati );
```

Non un elenco scritto a mano accanto a ogni mail — che prima o poi si sarebbe scollato dal testo. Così non può succedere.

E legge da `$def['corpo']`, il testo **originale**, non da quello già modificato: quindi dice quali segnaposto quella mail *dovrebbe* usare, anche se ne hai già cancellato uno. È la scelta giusta, ed è quella meno ovvia delle due.

### ⚠️ Ma c'è una riga da sistemare

`mail-area-riservata.php:857`:

```php
echo esc_html( implode( '</code>, <code>', $segnaposto_usati ) );
```

`esc_html()` viene applicato a **tutta la stringa già unita**, comprese le etichette `</code>, <code>` usate come separatore. Quindi le trasforma in testo visibile.

Quello che Ennio legge a schermo, provato:

```
Segnaposto usati in questa mail: {{NOME}}</code>, <code>{{DATA_INIZIO}}</code>, <code>{{DATA_FINE}}
```

**Non è pericoloso** — niente esce dal sito, niente si rompe. È solo brutto, in una riga che serve a rassicurare.

La correzione: proteggere **ogni voce**, poi unirle.

```php
// esc_html su OGNI voce, non sulla stringa già unita: altrimenti protegge
// anche i separatori </code>, <code> e Ennio se li ritrova scritti in
// mezzo ai segnaposto.
echo '<code>' . implode( '</code>, <code>', array_map( 'esc_html', $segnaposto_usati ) ) . '</code>';
```

*(e va tolto il `<code>` che oggi sta fuori dalla chiamata, o si raddoppia.)*

Provata:

```
Segnaposto usati in questa mail: {{NOME}}, {{DATA_INIZIO}}, {{DATA_FINE}}
```

## La batteria

```
97 file — 0 errori di sintassi
0 funzioni chiamate e non definite · 0 doppie
0 pulsanti morti · 0 gestori rotti · 0 ricorsioni per sbaglio
```

---

## In due righe

**Il pacchetto si installa.** La cosa che contava — mail e cancello che si contraddicono il giorno della scadenza — è chiusa e provata sul confine esatto.

Resta una riga cosmetica nel pannello delle mail: non blocca niente, si sistema quando ci tornate.
