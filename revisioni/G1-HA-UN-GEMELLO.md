# G1 ha un gemello, in un file non ancora letto

**Trovato verificando la tua diagnosi sui 130 punti del podio** — che era giusta, e la
confermo: `gs_unlock_badge()` (`badges.php:70-95`) è costruita bene, il controllo
`in_array` esce subito e `gs_add_points()` sta **dentro** il percorso protetto. Il badge
«Top 3 della Settimana» vale davvero +30 una volta sola. **Nessun difetto lì.**

**Ma la domanda interessante era un'altra:** *perché `gs_giuria_chiudi()` non usa
`gs_unlock_badge()`?*

**Perché non può** — quella funzione lavora solo su chiavi fisse (`gs_get_badges_definitions()`),
e le chiavi delle giurie, dei percorsi e delle sfide misurate sono costruite al momento
(`giuria_12`, `percorso_7`…). **Quindi ognuno di quei posti si è riscritto in casa la stessa
cosa.**

Ho cercato **tutti** i punti che scrivono `gs_badges` a mano invece di usare la funzione
buona. **Sono sette**, e sono di due forme diverse.

| Dove | Forma | Punti |
|---|---|---|
| `badges.php:75` | controllo con **uscita anticipata** | ✅ dentro |
| `percorsi-lezioni.php:158, 368, 451` | uscita anticipata (`return false`) | ✅ dentro |
| `streak.php:67` | uscita anticipata | ✅ dentro |
| `buono-sfoglia.php:218` | blocco `if` | — nessun punto |
| **`giuria-turno.php:229`** | **blocco `if`** | 🔴 **fuori** → **G1** |
| **`sfoglia-misurata.php:147`** | **blocco `if`** | 🔴 **fuori** → **nuovo** |

**Le due forme non sono equivalenti**, ed è tutta qui la differenza: con l'uscita anticipata
i punti stanno per forza dentro; con il blocco `if` è facile lasciarli fuori, e in due casi su
tre è successo.

---

# M1 · MEDIO — `gs_misura_chiudi()` è la copia riga per riga di `gs_giuria_chiudi()`, difetto compreso

**File:** `includes/sfoglia-misurata.php:135-158` — VERIFICATO

```php
	$badge_key = 'misura_' . $id;
	$owned     = gs_get_user_badges( $uid );
	if ( ! in_array( $badge_key, $owned, true ) ) {
		…badge, etichetta, storico…
	}

	gs_add_points( $uid, gs_get_points_value( 'misura_vinta', 30 ), … );   // ← fuori dall'if
```

**È G1, parola per parola, in un altro file.** Stessa struttura, stessi 30 punti, stessa
disposizione sbagliata.

**E come in `gs_giuria_chiudi()`, c'è anche la cosa fatta bene:** `gs_misura_chiusa` è
scritto **prima** degli effetti (riga 143). I due file sono gemelli in tutto, nel giusto e
nello sbagliato.

### Correzione

**Identica a G1:** portare `gs_add_points()` **dentro** l'`if`. **Fatele nello stesso giro** —
sono la stessa riga spostata in due file, e separarle vorrebbe dire tornarci.

### E c'è anche il pari merito

`$vincitrice = $classifica[0]`, senza nessuna regola per la parità. **È V2/G3 una terza
volta.** Quando Ennio decide il criterio, va applicato a tutte e tre le gare: sfide, giuria,
sfoglia misurata. **Tre gare dello stesso sito non possono avere tre regole diverse.**

---

## Una riga da aggiungere in `gs_unlock_badge()`

Il vero rimedio non è correggere due punti: è **togliere il motivo per cui la gente riscrive
quella funzione in casa.** `gs_unlock_badge()` rifiuta le chiavi che non sono nell'elenco
fisso:

```php
	$defs = gs_get_badges_definitions();
	if ( ! isset( $defs[ $badge_key ] ) ) {
		return false;
	}
```

**Basterebbe accettare anche una chiave libera, con etichetta e punti passati da fuori**, e i
quattro posti che oggi la riscrivono potrebbero chiamarla. **Ma non farlo adesso**: è una
modifica al cuore dei badge mentre stiamo per aprire le iscrizioni, e il guadagno è pulizia,
non sicurezza.

**Va messa in lista per dopo il lancio**, con questa nota accanto: *«finché quella funzione
rifiuta le chiavi dinamiche, ogni gara nuova si riscriverà la propria — e la prossima volta
il difetto sarà da un'altra parte ancora.»*

---

## Sulle 29 sfide di prova su guru2

**Puliscile**, ma non adesso e non in fretta: sono chiuse e innocue, e in questo momento
tutto quello che tocca dati su guru2 è meglio farlo con calma.

**Vale però la pena dire una cosa**, perché è la terza volta in questo progetto che escono
fuori residui di prove precedenti (prima 10 utenti, poi 536, ora 29 sfide): **conviene che
ogni file di prova, alla fine, cancelli quello che ha creato — e che il resoconto lo dica con
un numero prima/dopo.** Non è una critica: è che i residui non si notano finché non
falsificano una misura, e a quel punto si è già concluso qualcosa di sbagliato.
