# Il reset: si può fare, e conviene farlo adesso

**Per Ennio e per Claude Code Ennio 2 — 26/08/2026, misurato su 3.292.0**

Ennio chiede: se il reset è un problema grave lo saltiamo, altrimenti si procede.

**Non è un problema grave. È un problema di decisioni, non di programmazione** — e le decisioni sono sei, tutte concrete, tutte da prendere in dieci minuti.

Ma c'è una cosa che conta più di tutte, ed è il motivo per cui rispondo di procedere.

---

## Perché adesso è il momento più sicuro che ci sarà mai

**Sul sito ci sono sette account.**

Dopo un reset fatto oggi, potete aprirli **tutti e sette a mano** e guardare se è rimasto tutto quello che doveva restare. Ci vuole un quarto d'ora, e alla fine sapete — non credete: sapete — che è andata bene.

A ottobre, con duecento sfogline dentro, la stessa verifica è impossibile. Si può solo sperare che la lista delle esclusioni fosse giusta.

**Il reset non diventa più pericoloso perché il codice peggiora: diventa più pericoloso perché il controllo diventa impossibile.** Ogni settimana che passa è una settimana in cui l'unica rete di sicurezza vera — guardare col proprio occhio — si assottiglia.

Quindi la risposta è: **si può fare, ed è più sicuro farlo adesso che fra un mese.**

---

## Cosa c'è davvero da cancellare — i numeri

Li ho contati sul codice, non a memoria:

| | quanti |
|---|---|
| chiavi fisse dei dati delle sfogline | **63** |
| **prefissi costruiti a runtime** (`gs_points_mese_2026-08`, `gs_badge_dato_…`, `gs_lezione_vista_…`) | **29** |
| tipi di contenuto del plugin | **28** |
| impostazioni globali | 31 |

**I ventinove prefissi sono il motivo per cui l'elenco delle cose da cancellare non si può scrivere a mano.** Nessuno può elencare `gs_points_mese_2026-08`, `gs_points_mese_2026-09`, `gs_badge_dato_prima_fontana`… perché nascono man mano.

Da qui la regola, che è la stessa che ci ha già salvato altrove:

> **Si cancella tutto quello che comincia per `gs_`, TRANNE un elenco scritto di cose da tenere.**
> Mai il contrario. Un elenco di cose da cancellare dimentica; un elenco di cose da tenere, se dimentica, cancella qualcosa che si vede subito.

---

## Cosa deve sopravvivere

**Questa è la lista che conta**, ed è quella su cui va chiesto a Ennio se è d'accordo, riga per riga.

### Chi è (non si tocca mai)
`gs_status` · `gs_genere` · `gs_birthdate` · `gs_team` · `gs_conta_come_sfoglina` · `gs_titolo_onorario` · `gs_email_verificata` · `gs_email_verify_token`

### L'abbonamento e i trenta giorni
`gs_abbonamento` · `gs_abbonamento_scadenza` · `gs_abbonamento_avviso_per` · `gs_data_approvazione`

Se questi si cancellano, **ogni sfoglina già dentro perde la sua scadenza** e il congelamento la tratta come se non avesse mai pagato. È il gruppo più pericoloso di tutti.

### I soldi
`gs_token_credito` · `gs_token_log` · `gs_token_rif` · `gs_vetrina_token_attiva`

I token si comprano con un bonifico. Cancellarli è cancellare denaro versato.

### La Vetrina (49 euro)
`gs_bio_testo` · `gs_bio_media` · `gs_bio_foto` · `gs_bio_stato` · `gs_bio_cestino`

### Le sue scelte e le vostre note
`gs_notifiche_pref` · `gs_promemoria_ora` · `gs_bday_hidden` · `gs_note_gestore` · `gs_soggiorno_scelta` · `gs_richiesta_cancellazione` · `gs_lettore_bloccato` · `gs_lettore_bloccato_fino`

---

## Cosa se ne va

Punti (tutti e tre i contatori più il registro), livello, badge e il loro storico, streak e scudi, missioni, indovinelli, trimestri, contatori (olio, commenti, sfoglie), Buono Sfoglia, lezioni viste, i contrassegni giornalieri, il cestino dei promemoria, l'onboarding, i contrassegni delle email di benvenuto.

E i **contenuti di gioco**: sfoglie pubblicate, voti, commenti, ricette, diario, consigli, foto del Tavolo, voci del Matterello, misure, auguri, giurie, messaggi interni.

**Il catalogo NON si tocca**: lezioni, corsi, percorsi, piatti, FAQ, novità, letture, premi, locandine, vetrine dei partner. Sono cose che avete scritto voi, non le sfogline.

---

## Le sei decisioni per Ennio

Sono queste, e non le prendo io perché non sono tecniche.

**1. Il Testamento della sfoglina** (`gs_testamento`) — è una cosa che *lei* ha scritto, di suo, non un punteggio. Hai detto *«i dati che inseriscono in La Mia Sfoglia non vanno cancellati»*. **Io direi di tenerlo.**

**2. Lo sconto sui corsi guadagnato** (`gs_sconto_pct`, `gs_sconto_livello`, `gs_sconto_log`) — è un premio del gaming, ma vale soldi veri su un corso vero. **Io direi di tenerlo**: toglierlo a chi se l'era guadagnato è una brutta figura, e sono poche persone.

**3. Il Buono Sfoglia** (`gs_buono_sfoglia_pct`, `gs_buono_sfoglia_log`) — stessa domanda dello sconto. **Io direi di azzerarlo**, perché è legato al mese di gioco e il gioco riparte da zero: tenerlo darebbe un vantaggio nel mese uno a chi giocava prima.
*(2 e 3 possono avere risposte diverse: il primo è un livello raggiunto, il secondo è una gara mensile.)*

**4. Il Ricettario delle Famiglie** — le ricette sono contenuto di gioco o patrimonio dell'Accademia? Alcune sono ricette di nonne, raccolte una volta sola. **Io direi di tenerle** e di azzerare solo i punti che hanno dato.

**5. I Testamenti delle Maestre e le Letture** — stesso ragionamento del Ricettario. **Io direi di tenerli.**

**6. Le conversazioni private con gli esperti** (`gs_conversazione`) — lì dentro c'è la risposta a una domanda pagata con un token. **Io direi di tenerle**: se cancelli quelle, cancelli quello che la sfoglina ha comprato.

Se rispondi «fai tu», faccio queste sei scelte così come le ho scritte e le segno nel documento. Ma preferisco che le dica tu.

---

## Le tre condizioni — e sono queste che rendono il reset non pericoloso

**1. Backup completo del database prima, verificato.** Non «SiteGround fa i backup»: un backup fatto quel giorno, scaricato, e di cui qualcuno ha aperto il file per vedere che non è vuoto. Se qualcosa va storto, si torna indietro in dieci minuti invece di riscrivere un anno di lavoro.

**2. Prima una prova a vuoto, che non cancella niente.** Il pannello deve avere **due pulsanti**: «Mostra cosa verrebbe cancellato» e, solo dopo, «Cancella». Il primo scrive un elenco: *tot chiavi, tot contenuti, e per ogni sfoglina cosa resta*. Ennio lo legge. Se c'è dentro qualcosa che non doveva esserci, non è successo niente.

**3. Prima su guru2 con una copia dei dati veri, poi in produzione.** Non su dati finti: su una copia di quelli veri, perché i casi strani stanno lì.

**Con queste tre, il reset è un'operazione noiosa.** Senza anche una sola, è una scommessa.

---

## Come va scritto

**Non un elenco a mano di cosa cancellare.** Il codice deve chiedere al database cosa c'è:

```php
// I tipi di contenuto NON si elencano a mano: si chiedono a WordPress.
// Un tipo aggiunto il mese prossimo entrerebbe nel reset da solo, invece
// di essere dimenticato da un elenco scritto oggi.
$tipi = array_filter( get_post_types(), fn( $t ) => str_starts_with( $t, 'gs_' ) );
$tipi = array_diff( $tipi, $tipi_da_tenere );   // il catalogo

// Le chiavi meta: si cancella per prefisso 'gs_%' MENO l'elenco da tenere.
// Sono 63 chiavi fisse e 29 prefissi costruiti a runtime: un elenco di cose
// da cancellare ne dimenticherebbe qualcuna e resterebbe sporcizia
// invisibile che riappare fra sei mesi.
$wpdb->query( $wpdb->prepare(
    "DELETE FROM {$wpdb->usermeta}
      WHERE meta_key LIKE 'gs\\_%'
        AND meta_key NOT IN ( … l'elenco da tenere … )",
) );
```

Due cose che il codice deve fare e che di solito si dimenticano:

- **Svuotare la cache degli oggetti dopo** (`wp_cache_flush()`), o il sito continua a mostrare i vecchi punteggi finché qualcuno non ricarica tutto.
- **Scrivere nel registro chi l'ha fatto e quando**, in un'opzione. Fra un anno, se qualcuno chiede «ma i punti di prima?», la risposta deve essere scritta da qualche parte.

E il pulsante «Cancella» deve stare **dietro una parola da digitare** — non una spunta e non un `confirm()`. Chi lo preme deve scrivere `RESET` a mano. È l'unico pulsante del pannello che non si può annullare.

---

## L'altra cosa che scade insieme: lo username in rete

Va fatta **nello stesso momento**, e per la stessa ragione: dopo, non si può più.

`helpers.php:854`:

```php
return add_query_arg( 'sfoglina', rawurlencode( $user->user_login ), get_permalink( $page_id ) );
```

L'indirizzo della Vetrina pubblica di ogni sfoglina contiene **il suo nome utente**, cioè metà delle sue credenziali di accesso. È la cosa su cui Ennio è stato più netto: *«nessun dato personale di accesso deve andare in rete»*.

Va sostituito con un identificatore pubblico separato — WordPress ne ha già uno pronto, `user_nicename`, che oggi però viene copiato dallo username. Serve:

1. staccarlo alla registrazione (`user_nicename` costruito dal **nome visibile**, non dallo username);
2. una passata una-tantum sui sette account esistenti;
3. cambiare `helpers.php:854` e i punti che leggono quel parametro.

**Perché va fatto prima delle iscrizioni:** appena le vetrine sono online, quei link finiscono nei messaggi WhatsApp, nelle email, e su Google. Cambiarli dopo vuol dire romperli tutti.

Se vuoi, questa la specifico a parte e per bene: qui la nomino solo perché **le due cose vanno fatte nella stessa giornata**, prima delle iscrizioni, e non hanno senso separate.

---

## In due righe

**Il reset si può fare, non è pericoloso, e ogni settimana che aspetti diventa un po' più difficile da verificare** — non perché il codice peggiori, ma perché sette account si controllano a mano e duecento no.

Servono: un backup vero, una prova a vuoto prima, e una passata su guru2 con dati veri. E le sei risposte qui sopra.

Dammi le sei risposte (o dimmi «fai tu») e scrivo l'istruzione vera per l'altra sessione.
