# Istruzione: quello che resta del Reset

**Per Claude Code Ennio — 27/08/2026, scritta su 3.298.2.**

> **CHIUSA il 27/08/2026 sera: tutte e tre le parti sono state fatte. Questo documento resta
> come traccia di cosa è stato controllato e come — non c'è più niente da eseguire qui
> dentro.** I risultati sono in fondo a ogni parte.
**Sostituisce quello che restava aperto in `ISTRUZIONE-LA-PROVA-E-LE-QUATTRO-DECISIONI.md` e
in `ISTRUZIONE-L-ECCEZIONE-DI-RINA.md`: quei due sono stati eseguiti, tranne le parti che
ritrovi qui.**

## Dove siamo — non rifare quello che è già fatto

Il Reset è finito dal lato del codice, e verificato pacchetto per pacchetto da 3.296.0 a
3.298.2:

- l'elenco delle chiavi da tenere è a 39 voci; i tipi di contenuto sono classificati tutti,
  **36 registrati = 26 da tenere + 10 da cancellare-voluti**, e un controllo permanente segnala
  in anteprima qualunque tipo nuovo che nessuno abbia classificato;
- le quattro decisioni di Ennio sono applicate; l'eccezione sui piatti di Rina, nata da un
  malinteso, è stata tolta;
- `php -l`, `node --check`, e la suite dedicata a 51 controlli: verdi.

**Non rimettere mano al codice del Reset.** Qui non c'è niente da correggere: ci sono due cose
da **guardare** (la terza è già stata fatta, la trovi in fondo), e possono portare a una
modifica solo se salta fuori qualcosa.

---

## PARTE 1 — La prova nel browser, su guru2 · FATTA

**Fatta il 27/08/2026 sera, su guru2: tutti e cinque i punti tornano.** Quello che segue è
come è stata fatta, per la prossima volta.

È l'unica prova che nessuna sessione ha ancora potuto fare, ed è quella che conta: guarda da
fuori la cosa che il Reset non perdona. Se il tuo ambiente raggiunge il sito di Local, falla.
Se non lo raggiunge, **dillo subito e passa alla Parte 2** — non fingere di averla fatta e non
sostituirla con un test PHP: l'ultima volta è stata proprio questa prova a scoprire quattro
pulsanti che non rispondevano, e nessun test PHP li vedeva.

**Solo su guru2. Non esegue nessun Reset.**

### Preparare una sfoglina di prova

`<ID>` è una sfoglina di prova, **mai** uno dei sette account veri:

```bash
wp user meta update <ID> gs_abbonamento_scadenza 2026-12-31
wp user meta update <ID> gs_token_credito 3
wp user meta update <ID> gs_status sospesa
wp eval 'gs_utenti_sfogline_cestino();'
wp user meta get <ID> gs_archivio_gaming
```

L'ultimo comando **deve** stampare un array che contiene `gs_abbonamento_scadenza` e
`gs_token_credito`. Se stampa vuoto, fermati e scrivilo: vuol dire che l'archiviazione non
parte più, e tutto il ragionamento su cui poggia la correzione più importante va riguardato.

Senza `wp`: sospendi la sfoglina dal pannello e **apri l'elenco del Cestino** — è aprirlo che fa
partire l'archiviazione.

### Le cinque cose da guardare

Apri il pannello, sezione **«⚠️ Il Reset del gioco e lo username fuori dalla rete»**, e premi
**«Anteprima»** (non cancella niente, si può premere quante volte si vuole):

1. **In fondo**: la tabella «Sfogline nel Cestino — anche a loro resta tutto», con la sfoglina
   di prova, stato `sospesa`, scadenza `2026-12-31`, token `3`.
2. **Sempre in fondo**: la riga dei piatti — «N piatti in via d'estinzione torneranno
   liberi…». Controlla che concordi: singolare con uno, plurale con più di uno.
3. **In cima**: **non** deve esserci la riga rossa dei tipi non classificati. Se c'è, scrivi
   quali tipi nomina: vuol dire che qualcosa è stato aggiunto al plugin dopo l'ultima verifica.
4. **Il cancello**: scrivi `reset` minuscolo nella casella e premi il pulsante rosso. Deve
   rifiutare **senza contattare il server** (guarda la scheda Rete: nessuna chiamata
   `gs_reset_esegui`).
5. **Gli altri tre pulsanti** (Anteprima e Applica dello username, Anteprima del Reset):
   devono rispondere tutti. È il controllo che l'ultima volta ha trovato il difetto vero.

### Rimettere a posto

```bash
wp user meta update <ID> gs_status approvata
wp eval 'gs_ripristina_dati_gaming_utente( <ID> );'
```

e ricontrolla che la sfoglina si ritrovi scadenza e token.

---

## PARTE 2 — C'è altro di Rina che il Reset porterebbe via? · FATTA

**Fatta il 27/08/2026 sera. Risposta: no.** Rina non ha pubblicato niente come sfoglina — né
nel Matterello Parlante né altrove — quindi il Reset non le porta via niente, e i suoi Consigli
restano perché `gs_consiglio` si tiene per intero. Nessuna modifica al codice.

Ennio ha chiesto di tenere i Consigli scritti da lei, e quelli sono salvi: `gs_consiglio` è fra
i tipi da tenere, chiunque li scriva. Ma vale la pena guardare, **una volta sola**, se c'è
altro suo fra i tipi che si cancellano — per esempio nel Matterello Parlante (`gs_voce`), che
nella sua stessa intestazione si descrive come «archivio vocale di ricordi e **consigli**
registrati a voce».

Su guru2, e **solo per guardare**:

```bash
# 1) trovare il suo account
wp eval 'foreach ( get_users( array( "fields" => array( "ID", "display_name" ) ) ) as $u ) { if ( false !== stripos( $u->display_name, "rina" ) ) { echo $u->ID . "  " . $u->display_name . "\n"; } }'

# 2) cosa perderebbe, per tipo (metti l'ID trovato sopra al posto di <ID>)
wp eval 'foreach ( gs_reset_tipi_da_cancellare() as $t ) { $ids = get_posts( array( "post_type" => $t, "post_status" => array( "any", "trash" ), "author" => <ID>, "posts_per_page" => -1, "fields" => "ids" ) ); if ( $ids ) { echo $t . ": " . count( $ids ) . "\n"; } }'
```

Se il secondo comando non stampa niente, è finita: non c'è altro di suo da salvare.

**Se stampa qualcosa, scrivi cosa hai trovato e fermati.** Che si tenga o no è una decisione di
Ennio, non tua, e non si prende «già che ci siamo».

---

## PARTE 3 — I sedici test che falliscono da prima · FATTA, non rifarla

Fatta il 27/08/2026. Risultato: **nessuno dei sedici tocca il Reset.**

Quattro cause, tutte estranee: un mock di test che non imposta il ruolo dell'utente finto (la
maggioranza dei casi), un refactor delle mail non ancora riflesso in un test, una pagina
«Newsletter» mancante, e la scansione dei due pannelli generali che trova sette funzioni
`gs_pannello_*()` presenti in uno solo dei due.

Alcuni dei sedici richiedono file che il Reset usa anche lui — `sfogline-extra.php`,
`calendario.php`, `piatti-estinzione.php` — ma sono richiami a helper condivisi, non controlli
sulla sua logica.

Una sola riga di quella scansione riguardava il Reset, ed è a posto: `gs_pannello_reset()` è in
**tutti e due** i pannelli generali (`control-panel.php:285` e `admin.php:290`, più la
registrazione in `pannello-nuovo.php:70`), e non è fra le sette funzioni segnalate mancanti.

## Com'è finita

Tutte e tre le parti fatte il 27/08/2026:

1. **La prova nel browser su guru2**: tutti e cinque i punti tornano.
2. **L'altro contenuto di Rina**: non ce n'è. Niente da decidere, niente da cambiare.
3. **I sedici test**: nessuno tocca il Reset.

Una cosa vista strada facendo, e va saputa **prima** di aprire l'anteprima il giorno vero: nel
Cestino ci sono una dozzina di account `test_b1_rifiuto_…`, dati di prova lasciati da uno
script il 14/08/2026. Compariranno nella tabella «Sfogline nel Cestino» dell'anteprima, ed è
giusto così: il Reset non cancella nessun utente, mai. Se danno fastidio, si tolgono a mano —
guardando i nomi, una alla volta, e non con uno script.

---

## Le cose da non fare

- **Non eseguire il Reset.** Né in produzione, né su guru2, né «per vedere se funziona». La
  prova a vuoto la legge Ennio, il backup lo fa lui, il pulsante lo preme lui.
- **Non cancellare nessun utente**, per nessun motivo, in nessuno script.
- **Non decidere niente al posto di Ennio.** Se una delle due parti apre una domanda, si
  scrive la domanda e ci si ferma.
