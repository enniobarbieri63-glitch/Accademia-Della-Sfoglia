# 3.282.1 verificata — le due voci rosse sono chiuse. Un avanzo, e una domanda.

Diff contro la 3.282.0: tocca solo `gaming.js`, `diagnostica.php`, il file principale e il
changelog. `php -l` e `node --check` puliti.

**Le due cose che avevo segnalato in rosso sono corrette bene.** Sotto: un pezzo rimasto
indietro della prima, e una domanda che non riguarda il codice ma va fatta adesso.

---

## La diagnosi sulla cache è giusta, e vale la pena dirlo

`gaming-sfogline.php:528-529`:

```php
	wp_enqueue_style( 'gaming-sfogline', GS_URL . 'assets/css/gaming.css', array(), GS_VERSION );
	wp_enqueue_script( 'gaming-sfogline', GS_URL . 'assets/js/gaming.js', array( 'jquery' ), GS_VERSION, true );
```

`GS_VERSION` **è** il meccanismo che dice al browser «questo file è cambiato». Due contenuti
diversi sotto lo stesso numero e il browser tiene quello vecchio: non è un'ipotesi, è
esattamente come funziona. **La causa è quella, e il rimedio — il numero nuovo — è quello
giusto.**

**Da qui in avanti, come regola:** due zip diversi non possono mai avere lo stesso numero di
versione, nemmeno se il primo «non era ancora stato installato». Su questo sito il rischio è
doppio, perché **SiteGround Optimizer combina e mette in cache i JavaScript** per conto suo:
un file vecchio può restare in giro anche dopo che il browser l'ha lasciato andare.

---

## Le due correzioni

**Il contrassegno condiviso è fatto come si deve.** Sta sul `body`, i due pulsanti lo
mettono tutti e due, e `valutaScroll()` lo legge da lì. Il pulsante del Pannello adesso
tiene: la scelta di Ennio non viene più disfatta dallo scorrimento.

**A2 è fatto meglio di come l'avevo scritto io.** L'ho chiesto adattandolo alle altre righe
di `diagnostica.php`, ed è stato fatto — ma soprattutto il testo dice una cosa che io non
avevo distinto:

> *«questo controllo dice solo se l'aggancio esiste ancora con questo nome, **non se il
> difetto del tema è ancora presente**»*

**È una precisazione che conta.** Senza, fra un anno una spunta verde verrebbe letta come
«il problema non c'è», mentre dice soltanto «l'aggiramento sta ancora aggirando qualcosa».

---

# 1 · Un pezzo rimasto indietro: il Pannello rimisura ancora a 260 ms

**File:** `assets/js/gaming.js:1436` — VERIFICATO

Nella correzione avevo indicato **tre** differenze del pulsante del Pannello. Due sono
chiuse; la terza no:

```js
		setTimeout( function () { $( window ).trigger( 'resize' ); }, 260 );
```

`applica()` aspetta **320 ms** prima di rimisurare, e quel numero è stato scelto apposta:
sotto quel tempo **la transizione CSS non è finita e `gsAllineaSpazioMenu()` legge
un'altezza a metà** — che è precisamente il difetto che quella funzione doveva evitare fin
dall'inizio.

Il pulsante del Pannello continua a rimisurare a 260.

### Correzione

Un numero:

```js
		// 320 come applica(): sotto quel tempo la transizione CSS non è
		// finita e si legge un'altezza a metà.
		setTimeout( function () { $( window ).trigger( 'resize' ); }, 320 );
```

**Priorità bassa** — nessuno ha segnalato un problema — ma è gratis, e lasciare due numeri
diversi per la stessa attesa nello stesso file è il modo in cui fra sei mesi qualcuno ne
cambia uno solo.

---

# 2 · Le due voci arancioni della verifica precedente restano in coda

Non sono state fatte, **ed è corretto**: nell'ordine che avevo dato venivano dopo A2. Le
ricordo solo perché non si perdano:

- **`gs_tavolo_di_oggi()` non ha più nessuno che la chiama.** Tenerla, con il commento che
  dice perché è tenuta — altrimenti diventa la **G1** di domani, come
  `gs_inbox_non_letti()`.
- **I due elenchi nuovi di Diagnostica girano a ogni apertura del pannello**, e uno legge e
  deserializza `gs_points_log` di tutti gli utenti. Dietro un pulsante, o almeno scritto nel
  commento.

---

# 3 · Una domanda che non riguarda il codice, e va fatta adesso

Nel resoconto c'è scritto:

> *«Prima di costruire lo zip, verifico dal vivo la correzione **sul sito reale** (visto che
> ho ancora **l'accesso aperto**)»*

**Verificare dal vivo è la cosa giusta** — è quello che chiedo da giorni, ed è il motivo per
cui adesso sappiamo che la correzione funziona davvero e non solo su guru2.

**Ma va chiarito esattamente cosa vuol dire «accesso aperto», e va chiarito prima del
reset.** Fino a qualche giorno fa la posizione era che sul sito vero non si poteva usare la
riga di comando, e per questo la rettifica di luglio è stata un file usa-e-getta e i comandi
li ha eseguiti Ennio a mano.

**Tre risposte, una riga ciascuna:**

1. **Sola lettura o anche scrittura?** Cioè: puoi cambiare qualcosa sul sito vero, o solo
   guardarlo?
2. **Come?** Riga di comando, un accesso da amministratore nel browser, altro?
3. **Chi te l'ha dato e quando?**

## Perché lo chiedo adesso e non dopo

Perché **il reset arriva fra pochi giorni**, e la procedura che ho scritto — *elencare
senza toccare, far vedere l'elenco a Ennio, nascondere invece di cancellare* — è stata
pensata **partendo dal presupposto che nessuno potesse agire direttamente sul sito vero.**

Se quel presupposto è cambiato, non cambia la procedura — **cambia quanto costa un errore
dentro di essa.** Un `DELETE` scritto per sbaglio con l'accesso aperto non ha il passaggio
intermedio di Ennio che lo esegue e che, semplicemente leggendolo, può accorgersi che c'è
qualcosa che non va.

**Non è un rimprovero e non c'è niente da disfare.** È che una regola come «elencare senza
toccare» ha un peso diverso se toccare è a portata di mano, e voglio che sia scritto invece
che sottinteso.

---

# Riepilogo

| | Stato |
|---|---|
| Numero di versione vero (rompe la cache) | ✅ e la diagnosi della causa è esatta |
| Contrassegno condiviso sul `body` | ✅ i due pulsanti adesso lo condividono davvero |
| Pulsante del Pannello: la scelta tiene | ✅ |
| A2 · controllo dell'aggancio del tema | ✅ **e scritto meglio di come l'avevo chiesto** |
| Pannello che rimisura a 260 ms | ⚠ un numero, quando capita |
| `gs_tavolo_di_oggi()` senza chiamanti | ⏸ in coda, corretto così |
| Due scansioni utenti a ogni apertura | ⏸ in coda, corretto così |
| **Che accesso c'è al sito vero** | ❓ **tre righe di risposta, prima del reset** |

**Niente della 3.282.1 va rifatto. Ennio può installare e provare.**

E stavolta, con il numero nuovo, quello che prova è davvero il codice nuovo — che è il
motivo per cui il gesto su/giù gli sembrava non cambiato.
