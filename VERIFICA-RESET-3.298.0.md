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
