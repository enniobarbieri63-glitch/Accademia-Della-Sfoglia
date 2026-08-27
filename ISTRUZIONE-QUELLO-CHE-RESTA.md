# Istruzione: quello che resta del Reset

**Per Claude Code Ennio — 27/08/2026, scritta su 3.298.2**
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

**Non rimettere mano al codice del Reset.** Qui non c'è niente da correggere: ci sono tre cose
da **guardare**, e una sola di queste può portare a una modifica.

---

## PARTE 1 — La prova nel browser, su guru2

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

## PARTE 2 — C'è altro di Rina che il Reset porterebbe via?

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

## PARTE 3 — I sedici test che falliscono da prima

Ogni consegna finisce con «stessi 16 file pre-esistenti, nessuna nuova rottura». È
probabilmente vero, ma nessuno l'ha mai verificato: i test stanno fuori dal pacchetto, quindi
chi verifica le consegne non li vede.

Serve solo una risposta, non una correzione:

```bash
./run.sh 2>&1 | grep -i "fail\|error" | sort -u
```

Scrivi **l'elenco dei 16 file** e, per ognuno, una riga: cosa prova, e se tocca il Reset
(`reset.php`, `sfogline-extra.php`, `piatti-estinzione.php`, `calendario.php`) oppure no.

- Se **nessuno** tocca il Reset: dillo, e il giro è chiuso.
- Se **qualcuno** lo tocca: fermati lì e scrivi quale e cosa dice il messaggio d'errore. Non
  correggerlo di tua iniziativa — un test rosso da prima può essere rosso per un motivo che
  qualcuno conosce.

---

## Cosa riferire, alla fine

Cinque righe bastano:

1. La Parte 1 l'hai potuta fare, sì o no. Se sì, quale dei cinque punti non tornava (o
   «tornano tutti e cinque»).
2. Il risultato dei due comandi della Parte 2.
3. L'elenco dei 16, e se qualcuno tocca il Reset.

---

## Le cose da non fare

- **Non eseguire il Reset.** Né in produzione, né su guru2, né «per vedere se funziona». La
  prova a vuoto la legge Ennio, il backup lo fa lui, il pulsante lo preme lui.
- **Non cancellare nessun utente**, per nessun motivo, in nessuno script.
- **Non decidere niente al posto di Ennio.** Se una delle tre parti apre una domanda, si
  scrive la domanda e ci si ferma.
