# Istruzione: la prova nel browser, e le quattro decisioni

**Per Claude Code Ennio — 27/08/2026, scritta su 3.297.1**

Il Reset, dal lato del codice, **è finito**: le tre correzioni bloccanti e le quattro minori
sono dentro, verificate sul pacchetto (`VERIFICA-RESET-3.297.0.md`, con in coda il seguito
sulla 3.297.1). Non c'è più niente da correggere.

Restano due cose, e nessuna delle due è codice:

1. **La prova nel browser su guru2** — l'unica che guarda da fuori la cosa che il Reset non
   perdona. Nessuna sessione l'ha ancora potuta fare: l'ambiente in cui girano non raggiunge
   `guru2.local`. Se il tuo lo raggiunge, falla tu. Se no, è di Ennio, ed è scritta qui sotto
   in modo che possa farla lui in cinque minuti.
2. **Le quattro decisioni**, che sono di Ennio e non tue.

---

## PARTE 1 — La prova che conta

Serve a vedere **una cosa sola**: che una sfoglina finita nel Cestino compare nell'anteprima
del Reset con il suo abbonamento, la sua scadenza e i suoi token. Se compare, il pericolo più
grosso di tutto il reset è visibile da fuori. Se non compare, non si preme niente.

Si fa **su guru2, mai in produzione**, e **non esegue nessun Reset**.

### Preparare una sfoglina di prova

Serve una sfoglina che abbia qualcosa da perdere: abbonamento con scadenza, token, e — se c'è
— la Vetrina. Da riga di comando su guru2 (sostituisci `<ID>` con quello di una sfoglina di
prova, **mai** uno dei sette account veri):

```bash
wp user meta update <ID> gs_abbonamento_scadenza 2026-12-31
wp user meta update <ID> gs_token_credito 3
wp user meta update <ID> gs_status sospesa
```

Poi fai partire l'archiviazione, che è quella che chiude i suoi dati dentro la scatola:

```bash
wp eval 'gs_utenti_sfogline_cestino();'
wp user meta get <ID> gs_archivio_gaming
```

L'ultimo comando **deve** stampare un array che contiene `gs_abbonamento_scadenza` e
`gs_token_credito`. Se è vuoto o non esiste, l'archiviazione non è partita: fermati qui e
scrivilo, perché vuol dire che il pericolo è cambiato di forma e va guardato di nuovo.

Se `wp` non c'è, la stessa cosa dal pannello: sospendi la sfoglina, poi **apri l'elenco del
Cestino** — è aprirlo che fa partire l'archiviazione.

### La prova

1. Apri il pannello, sezione **«⚠️ Il Reset del gioco e lo username fuori dalla rete»**.
2. Premi **«Anteprima: mostra cosa verrebbe cancellato»**. Non cancella niente: si può premere
   quante volte si vuole.
3. Guarda in fondo al risultato. Deve esserci la tabella
   **«Sfogline nel Cestino — anche a loro resta tutto»**, con dentro la sfoglina di prova,
   stato `sospesa`, scadenza `2026-12-31`, token `3`.
4. Guarda in cima al risultato: **non** deve esserci la riga rossa dei tipi non classificati.
   Se c'è, leggi quali tipi nomina e scrivilo — vuol dire che qualcosa è stato aggiunto al
   plugin dopo l'ultima verifica.
5. Prova il cancello: scrivi `reset` minuscolo nella casella e premi il pulsante rosso. Deve
   rifiutare senza contattare il server.

### Rimettere a posto

```bash
wp user meta update <ID> gs_status approvata
wp eval 'gs_ripristina_dati_gaming_utente( <ID> );'
```

e ricontrolla che si ritrovi scadenza e token.

### Se qualcosa non torna

Non correggere di tua iniziativa: **scrivi cosa hai visto**, con il numero del passo. Questa
prova serve a guardare, non a sistemare.

---

## PARTE 2 — Le quattro domande, da leggere a Ennio

Sono scritte per essere lette ad alta voce, senza spiegazioni tecniche. Una risposta per
ognuna, e finisce lì.

> **1.** Ogni sfoglina ha un suo elenco di «Cose da Fare», promemoria che si scrive da sola.
> Il Reset oggi li cancella. Li teniamo?
> *(La mia proposta: **tenerli**. È lo stesso motivo per cui teniamo il Testamento — l'ha
> scritto lei, non è un punteggio.)*

> **2.** Il Diario dell'Impasto e i Consigli, scritti dalle sfogline. Il Reset oggi li
> cancella, mentre tiene testamenti, ricette e letture. Li teniamo o li azzeriamo?
> *(Non ho una proposta: dipende da cosa vuoi che le sfogline ritrovino il giorno dopo.
> Oggi sono di prova; fra un anno no.)*

> **3.** Gli errori che le sfogline hanno raccontato e che tu hai **promosso** a materiale
> didattico. Il Reset oggi li cancella insieme a quelli mai promossi. Li teniamo?
> *(La mia proposta: **tenerli tutti**, promossi e non. Quelli non promossi si cancellano a
> mano in cinque minuti, e non si rischia di perdere il materiale.)*

> **4.** I piatti in via d'estinzione restano «adottati» dalle sfogline di prima, e nessuna
> nuova sfoglina potrà più adottarli — il sito le risponderà «questo piatto ha già una
> custode». Li liberiamo?
> *(La mia proposta: **liberarli**. È esattamente quello che abbiamo già deciso per i voti
> dentro i sondaggi: il piatto resta, chi lo teneva no.)*

**Non decidere nessuna delle quattro al posto suo.** Se la risposta non arriva, lascia tutto
com'è: il pacchetto 3.297.1 è coerente e pronto anche così.

---

## PARTE 3 — Cosa fare per ogni risposta

Tutto in `includes/reset.php`.

### Se **sì** alla 1 — le Cose da Fare

In `gs_reset_meta_da_tenere()`, nel gruppo «Le sue scelte, le sue cose, le vostre note»:

```php
		'gs_todos', 'gs_todos_cestino',   // i promemoria personali: li scrive lei, non
		                                  // sono punteggio (decisione di Ennio, __/__/2026)
```

### Se **sì** alla 2 — Diario e Consigli · Se **sì** alla 3 — gli errori didattici

Questi tre tipi oggi stanno nel **secondo** elenco, quello dei tipi che si cancellano apposta.
Spostarli vuol dire **due** modifiche, non una:

```php
// 1) in gs_reset_tipi_da_tenere(), aggiungi:
		'gs_diario', 'gs_consiglio',      // decisione di Ennio, __/__/2026
		'gs_errore_didattico',            // decisione di Ennio, __/__/2026

// 2) in gs_reset_tipi_da_cancellare_voluti(), TOGLI gli stessi nomi.
```

**Il secondo passo non è facoltativo.** Un tipo che resta in tutti e due gli elenchi non dà
errore e non si vede: il conto smette solo di tornare. Dopo la modifica deve restare vero che
**tipi registrati = da tenere + da cancellare-voluti**, oggi 36 = 23 + 13 (diventerebbe
36 = 26 + 10 con tutti e tre spostati).

### Se **sì** alla 4 — i piatti

In `gs_reset_esegui()`, al punto 3, subito dopo il blocco dei sondaggi:

```php
	// 3d — i piatti restano (sono catalogo), ma il custode è stato di gioco:
	// senza svuotarlo i piatti ripartono già adottati da sfogline che non
	// hanno più niente, e nessuna nuova sfoglina può più adottarli — lo
	// stesso motivo per cui si svuotano i voti dentro i sondaggi
	// (decisione di Ennio, __/__/2026).
	$piatti_liberati = 0;
	if ( post_type_exists( 'gs_piatto' ) ) {
		foreach ( get_posts( array( 'post_type' => 'gs_piatto', 'post_status' => $stati_da_cancellare, 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $pid ) {
			if ( ! get_post_meta( $pid, 'gs_custode_tipo', true ) ) { continue; }
			delete_post_meta( $pid, 'gs_custode_tipo' );
			delete_post_meta( $pid, 'gs_custode_id' );
			delete_post_meta( $pid, 'gs_custode_team' );
			$piatti_liberati++;
		}
	}
```

e aggiungilo alla voce del log (`'piatti_liberati' => $piatti_liberati,`), al messaggio di fine
reset e alla cronologia del pannello — con `isset()` nella cronologia, come già fatto per
`scatole_ripulite`: le voci di log vecchie quella chiave non ce l'hanno.

---

## La consegna

1. `php -l` sui file toccati, graffe bilanciate nel JS.
2. Il test dedicato: un caso per ogni decisione applicata. Per la 4, un piatto adottato prima
   del Reset che dopo risulta libero e riadottabile.
3. Versione **3.298.0** nei tre punti, changelog che dica **quale decisione** è stata presa,
   **da chi** e **quando** — fra un anno la domanda sarà «ma i piatti perché sono liberi?», e
   la risposta deve essere scritta.
4. Sincronizza su guru2.

---

## Una cosa da non fare

**Non eseguire il Reset.** Né in produzione, né su guru2 «per vedere se funziona» senza dirlo.
La prova a vuoto la legge Ennio, il backup lo fa lui, e il secondo pulsante lo preme lui.

E non cancellare nessun utente. Il Reset azzera il gioco; le persone le decide Ennio, una alla
volta.
