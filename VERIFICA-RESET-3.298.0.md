# Verifica di gaming-sfogline 3.298.0

**27/08/2026 — le quattro decisioni, applicate.** Confronto `3.297.1` contro `3.298.0`, sul
pacchetto intero.

## Cosa è cambiato

Tre file: `includes/reset.php`, `gaming-sfogline.php`, `readme.txt`. Nient'altro toccato.

## Le quattro decisioni

| | verificato |
|---|---|
| **1 — Le Cose da Fare** | `gs_todos` e `gs_todos_cestino` nell'elenco da tenere, nel gruppo giusto, con la data della decisione nel commento. |
| **2 — Diario e Consigli** | `gs_diario` e `gs_consiglio` fra i tipi da tenere. |
| **3 — Errori didattici** | `gs_errore_didattico` fra i tipi da tenere, promossi e non. |
| **4 — I piatti** | Il custode si svuota per tutti (`sfoglina` e `squadra`), tranne i piatti adottati da Rina Poletti. `piatti_liberati` nel log, nel messaggio di fine reset e nella cronologia, con `isset()` come per `scatole_ripulite`. |

**Lo spostamento è stato fatto da tutte e due le parti**, che era la cosa su cui il documento
insisteva: i tre tipi sono stati tolti da `gs_reset_tipi_da_cancellare_voluti()`, non solo
aggiunti all'altro elenco.

## Il conto, rifatto da capo

Censimento indipendente sul pacchetto nuovo, senza fidarmi di quello dichiarato:

- **36 tipi registrati = 26 da tenere + 10 da cancellare-voluti**;
- nessun tipo non classificato, **nessun tipo in tutti e due gli elenchi**;
- 39 chiavi nell'elenco da tenere (37 + le due delle Cose da Fare).

`php -l` pulito. Versione 3.298.0 nei tre punti, changelog che dice quale decisione, di chi e
quando.

---

## Una cosa da correggere: l'eccezione di Rina non si vede finché non è troppo tardi

`gs_e_rina_poletti()` decide se un piatto resta suo confrontando il nome così:

```php
return $u && 'Rina Poletti' === trim( $u->display_name );
```

È un confronto **esatto**: maiuscole comprese, e con un solo spazio in mezzo. Se l'account di
Rina si chiama `RINA POLETTI`, `Rina`, `Rina Poletti – Accademia`, o ha due spazi o uno spazio
unificatore incollato da un copia-incolla, la funzione risponde **falso in silenzio** e il
Reset le libera tutti i piatti. Nessun errore, nessun avviso: si scopre dopo, cercando i suoi
piatti e non trovandoli più.

E l'anteprima **non dice niente sui piatti**: né quanti si liberano, né quanti restano a Rina.
La regola decide qualcosa di irreversibile e non è visibile prima di premere — è esattamente il
problema che abbiamo già risolto per le sfogline nel Cestino, ricomparso su un'altra riga.

Le correzioni (confronto tollerante, riquadro nell'anteprima, avviso rosso quando **nessun
account** porta quel nome) sono scritte con il codice esatto in
`ISTRUZIONE-L-ECCEZIONE-DI-RINA.md`.

## Una cosa da sapere

Il blocco `3d` (i piatti) sta fisicamente **prima** del `3c` (le opzioni segnaposto): i
commenti numerati sono fuori ordine. Solo estetica, il codice fa la cosa giusta.

## Resta

La prova nel browser su guru2, con una sfoglina vera nel Cestino — l'unica che nessuna sessione
può fare al posto di Ennio, e ora c'è un secondo motivo per farla: guardare che il numero dei
piatti che restano a Rina sia quello che ci si aspetta.

---

# Seguito: 3.298.1 — verificata

**27/08/2026.** Quattro file cambiati (`reset.php`, `gaming.js`, l'intestazione, `readme.txt`),
nient'altro toccato.

- **L'eccezione è sparita davvero**: `gs_e_rina_poletti()` non esiste più e
  `grep -rn "rina_poletti"` nel codice non trova più niente — zero riferimenti, né in PHP né
  nel JS. Il blocco `3d` non ha più il ramo speciale, e i commenti sono tornati in ordine
  `3a → 3b → 3c → 3d`.
- **L'anteprima dice quanti piatti tornano liberi.** `gs_reset_conteggio_piatti_da_liberare()`
  conta con gli stessi stati (`any` + `trash`) che usa il Reset per liberarli: il numero
  dell'anteprima e quello del log non possono divergere.
- `php -l` e `node --check` puliti. Censimento rifatto: **36 tipi = 26 da tenere + 10 da
  cancellare-voluti**, nessuno scoperto. Versione 3.298.1 nei tre punti, changelog che dice
  perché l'eccezione è stata tolta.

## Una cosa da correggere, ed è mia

La riga nuova dell'anteprima non concorda al plurale — con più di un piatto scrive
«3 piatti in via d'estinzione **tornerà libero**». L'errore era nello snippet che avevo scritto
io, copiato fedelmente. È la pagina che si legge prima di premere il pulsante che non si
annulla, quindi vale la pena sistemarla: la correzione è in
`ISTRUZIONE-LA-RIGA-DEI-PIATTI.md`.

## Resta, e ora è tutto quello che resta

1. **La prova nel browser su guru2**, con una sfoglina vera nel Cestino.
2. **Il controllo della Parte 3** — se ci sia altro scritto da Rina fra i tipi che il Reset
   cancella (il Matterello Parlante, `gs_voce`, parla di «ricordi e consigli registrati a
   voce»). Due comandi, in `ISTRUZIONE-L-ECCEZIONE-DI-RINA.md`.

---

# Seguito: 3.298.2 — verificata, e chiude il giro

**27/08/2026.** Tre file: `gaming.js`, l'intestazione, `readme.txt`. Solo la frase.

Non l'ho letta: ho **eseguito il blocco consegnato** con `d.piatti_da_liberare` a 1, 3 e 0.

- `1` → «1 piatto in via d'estinzione tornerà libero: il piatto resta, la sua custode no, e chiunque potrà adottarlo di nuovo.»
- `3` → «3 piatti in via d'estinzione torneranno liberi: i piatti restano, le custodi di prima no, e chiunque potrà adottarli di nuovo.»
- `0` → la riga non compare.

`node --check` pulito, versione 3.298.2 nei tre punti, changelog che dice cosa si leggeva prima.

**Dal codice non resta niente.** Restano le due cose che nessuna sessione può fare al posto di
chi ha il Mac in mano:

1. **La prova nel browser su guru2**, con una sfoglina vera nel Cestino — i passi sono in
   `ISTRUZIONE-LA-PROVA-E-LE-QUATTRO-DECISIONI.md`.
2. **I due comandi della Parte 3** — se ci sia altro scritto da Rina fra i tipi che il Reset
   cancella, in `ISTRUZIONE-L-ECCEZIONE-DI-RINA.md`.

Poi: backup, Parte 2 (lo username), anteprima letta da Ennio, Reset, i sette account aperti a
mano. Come dice il documento di partenza, che da qui non è cambiato.

---

# Seguito: la Parte 3 (i sedici test), e lo zip ricontrollato

**27/08/2026, sera.**

Il pacchetto rimandato è **identico** a quello già verificato: `diff -rq` fra i due zip
3.298.2 non trova nessuna differenza. Niente è cambiato in silenzio sotto lo stesso numero di
versione.

**I sedici test che falliscono da prima: nessuno tocca il Reset.** La sessione che li ha
guardati li ha elencati uno per uno, con cosa prova ciascuno, e ha attribuito i fallimenti a
quattro cause estranee (un mock che non imposta il ruolo dell'utente finto, un refactor delle
mail, una pagina «Newsletter» mancante, sette funzioni di pannello presenti in un solo pannello
generale).

Quella tabella non la posso verificare: i test stanno fuori dal pacchetto. **L'unica riga che
riguardava il Reset l'ho controllata da qui, e torna**: `gs_pannello_reset()` è presente in
tutti e due i pannelli generali (`control-panel.php:285`, `admin.php:290`) e registrato in
`pannello-nuovo.php:70` — non è fra le sette funzioni scoperte.

**Restano due cose, tutte e due sul Mac:** la prova nel browser su guru2 e i due comandi che
cercano se ci sia altro scritto da Rina fra i tipi che il Reset cancella.
