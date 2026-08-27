# Istruzione: il Reset, e lo username fuori dalla rete

**Per Claude Code Ennio 2 — 26/08/2026, scritta su 3.292.0**

Ennio ha approvato. Ha anche detto una cosa che cambia il peso del lavoro:

> *«In questo momento è tutto dimostrativo nel sito, non ci sono problemi di scadenze, puoi cancellare modificare sistemare in un secondo tempo.»*

**Questo alleggerisce la rete di sicurezza, non l'elenco delle esclusioni.** I contenuti sono di prova, ma i sette account sono persone vere — Ennio, i collaboratori, gli amici, i giornalisti, Rosemma — e le loro identità e i loro abbonamenti non devono muoversi di un millimetro. È lì che va l'attenzione, non sui contenuti.

---

## Le eccezioni, dette prima

**Niente di quello che segue tocca:**

- gli **utenti** stessi (nessun `wp_delete_user`, mai — il reset azzera i dati di gioco, non cancella persone);
- il **catalogo** che avete scritto voi: lezioni, corsi, percorsi, piatti, FAQ, novità, letture, premi, locandine, direttive, vetrine di artigiani e scuole;
- le **impostazioni** del plugin (`GS_OPTION`) — tranne i tre segnaposto elencati più sotto;
- la **Libreria Media**: nessuna immagine viene cancellata, mai. Se un contenuto sparisce, la sua foto resta negli allegati. È voluto: recuperare una foto è possibile, ricrearla no.

---

## PARTE 1 — Il Reset

### Le sei decisioni, chiuse

Ennio ha approvato le mie proposte. Sono queste, e vanno scritte nel commento in cima al file perché fra un anno si sappia **perché**:

| | esito | perché |
|---|---|---|
| **Testamento della sfoglina** | **si tiene** | l'ha scritto lei, non è un punteggio |
| **Sconto sui corsi guadagnato** | **si tiene** | vale soldi veri su un corso vero; toglierlo a chi se l'era guadagnato è una brutta figura |
| **Buono Sfoglia** | **si azzera** | è una gara mensile, e il mese riparte da zero: tenerlo darebbe un vantaggio a chi giocava prima |
| **Ricettario delle Famiglie** | **si tiene** | sono ricette di nonne, raccolte una volta sola. Si azzerano i punti che hanno dato, non le ricette |
| **Testamenti delle Maestre e Letture** | **si tengono** | stesso ragionamento |
| **Conversazioni con gli esperti** | **si tengono** | dentro c'è la risposta a una domanda pagata con un token: cancellarle è cancellare quello che la sfoglina ha comprato |

### La regola, che è una sola

> **Si cancella tutto quello che comincia per `gs_`, TRANNE un elenco scritto di cose da tenere.** Mai il contrario.

Il motivo è misurato, non teorico: le chiavi sono **63 fisse più 29 costruite a runtime** (`gs_points_mese_2026-08`, `gs_badge_dato_…`, `gs_lezione_vista_…`, `gs_buono_mese_…`). Un elenco di cose da cancellare ne dimenticherebbe alcune e lascerebbe sporcizia invisibile che ricompare fra sei mesi. Un elenco di cose da tenere, se sbaglia, cancella qualcosa che **si vede subito**.

### L'elenco delle cose da tenere — copiatelo così com'è

```php
/**
 * Le chiavi meta che il Reset NON tocca mai. Ogni gruppo ha una ragione, ed
 * è scritta: chi aggiungerà una chiave qui dentro fra un anno deve poter
 * capire in quale gruppo va, senza chiedere a nessuno.
 */
function gs_reset_meta_da_tenere() {
    return array(
        // --- Chi è. Non si tocca mai, per nessun motivo. ---
        'gs_status', 'gs_genere', 'gs_birthdate', 'gs_team',
        'gs_conta_come_sfoglina', 'gs_titolo_onorario',
        'gs_email_verificata', 'gs_email_verify_token',

        // --- L'abbonamento e i trenta giorni. Il gruppo più pericoloso:
        // se questi si cancellano, ogni sfoglina già dentro perde la sua
        // scadenza e il congelamento la tratta come se non avesse mai
        // pagato, il giorno dopo il reset.
        'gs_abbonamento', 'gs_abbonamento_scadenza',
        'gs_abbonamento_avviso_per', 'gs_data_approvazione',

        // --- I soldi. I token si comprano con un bonifico: cancellarli è
        // cancellare denaro versato.
        'gs_token_credito', 'gs_token_log', 'gs_token_rif',
        'gs_vetrina_token_attiva',

        // --- La Vetrina pubblica: si paga a parte, 49 euro.
        'gs_bio_testo', 'gs_bio_media', 'gs_bio_foto', 'gs_bio_stato', 'gs_bio_cestino',

        // --- Le sue scelte, le sue cose, le vostre note.
        'gs_notifiche_pref', 'gs_promemoria_ora', 'gs_bday_hidden',
        'gs_note_gestore', 'gs_soggiorno_scelta', 'gs_richiesta_cancellazione',
        'gs_lettore_bloccato', 'gs_lettore_bloccato_fino',
        'gs_testamento', 'gs_testamento_proponi',          // decisione 1
        'gs_sconto_pct', 'gs_sconto_livello', 'gs_sconto_log',  // decisione 2
    );
}
```

E i **tipi di contenuto da tenere**, con lo stesso criterio:

```php
function gs_reset_tipi_da_tenere() {
    return array(
        // Il catalogo: l'avete scritto voi, non le sfogline.
        'gs_lezione', 'gs_corso', 'gs_percorso_lezioni', 'gs_piatto',
        'gs_faq', 'gs_novita', 'gs_lettura', 'gs_premio', 'gs_locandina',
        'gs_direttiva', 'gs_artigiano', 'gs_scuola', 'gs_cassaforte',
        'gs_sondaggio',        // il sondaggio resta, i voti dentro si azzerano (vedi sotto)
        // Le decisioni 4, 5 e 6.
        'gs_ricetta', 'gs_testimonianza', 'gs_conversazione', 'gs_domanda',
    );
}
```

**I tipi da cancellare NON si elencano a mano.** `cpt.php:71` li registra in un ciclo da un array: un tipo aggiunto il mese prossimo entrerebbe nel reset da solo, invece di essere dimenticato in un elenco scritto oggi.

```php
$tutti = array_filter( get_post_types(), fn( $t ) => str_starts_with( $t, 'gs_' ) );
$da_cancellare = array_diff( $tutti, gs_reset_tipi_da_tenere() );
```

### I tre casi che non sono né carne né pesce

**1. I voti dentro i sondaggi.** Il sondaggio si tiene, ma `gs_sond_voti` e i contrassegni `gs_sondaggio_proposta_pagata_*` vanno svuotati: altrimenti il sondaggio riparte con i voti di prima e nessuno può più votare. Meta di post, non di utente.

**2. Le sfide chiuse.** `gs_chiusa` e i premi già assegnati: le sfide vecchie non le tocca il reset (sono contenuto vostro), ma se ne resta una **aperta** con dentro i voti di prova, va chiusa o svuotata a mano. **Segnalatelo nella prova a vuoto**, non decidetelo voi.

**3. Le opzioni segnaposto.** Tre opzioni globali tengono il conto di «fin dove siamo arrivati» e vanno **azzerate insieme ai dati**, o il primo mese di gioco vero verrà saltato:
`gs_buono_sfoglia_mese_chiuso`, `gs_year_prize_assigned_*`, `gs_mappa_territori_vincitrice` (e la sua data).

### Il pannello: due pulsanti, e il secondo si digita

**Primo pulsante — «Mostra cosa verrebbe cancellato».** Non cancella niente. Scrive un elenco:

```
CHIAVI DEI DATI          1.284 righe in 63 chiavi + 29 prefissi
CONTENUTI                gs_tavolo 340 · gs_voce 22 · gs_misura 18 · …
SI TENGONO               gs_ricetta 47 · gs_conversazione 9 · …

PER OGNI SFOGLINA, COSA RESTA
  Rosemma      abbonamento: attivo, scade 12/10/2026 · token: 3 · vetrina: sì
  Ennio        nessuna scadenza (accesso libero) · token: 0 · vetrina: no
  …
```

**Quest'ultima tabella è la parte importante di tutto il pannello.** Con sette account, Ennio la legge in due minuti e sa — non spera: **sa** — che gli abbonamenti e i token sono al loro posto. È la stessa verifica che a ottobre, con duecento sfogline, non si potrebbe più fare.

**Secondo pulsante — «Cancella».** Attivo solo dopo che il primo è stato premuto nella stessa pagina, e **dietro una parola da digitare**: chi lo preme scrive `RESET` a mano. Non una spunta, non un `confirm()`. È l'unico pulsante del pannello che non si può annullare.

### Le tre cose che si dimenticano sempre

**Il contrassegno prima degli effetti.** Il reset scrive **prima** in un'opzione *«reset iniziato, da chi, quando»*, poi cancella, poi scrive *«finito»*. Se muore a metà — e con qualche migliaio di righe può succedere — si sa che è successo e a che punto era. È la regola di luglio, applicata alla cosa più grossa che il plugin farà mai.

**Svuotare la cache dopo** (`wp_cache_flush()`), o il sito continua a mostrare i vecchi punteggi finché qualcuno non ricarica tutto, e sembra che il reset non abbia funzionato.

**Lasciare traccia.** Un'opzione `gs_reset_log` con data, chi, e i numeri di quello che è stato cancellato. Fra un anno, alla domanda «ma i punti di prima?», la risposta deve essere scritta da qualche parte.

### Le condizioni, ridotte a due

Ennio ha detto che i contenuti sono dimostrativi. Quindi:

1. **Backup del database prima.** Resta, e non è negoziabile: i sette account sono veri.
2. **La prova a vuoto, letta da Ennio prima di premere il secondo pulsante.**

La terza — la prova su guru2 con dati veri — **decade**: con contenuti di prova e sette account, la prova a vuoto in produzione dice già tutto quello che direbbe una prova su guru2.

---

## PARTE 2 — Lo username fuori dalla rete

**Da fare nella stessa giornata**, prima delle iscrizioni, perché dopo i link sono in giro e cambiarli vuol dire romperli.

### Cosa c'è oggi

`helpers.php:854`:

```php
return add_query_arg( 'sfoglina', rawurlencode( $user->user_login ), get_permalink( $page_id ) );
```

L'indirizzo della Vetrina pubblica di ogni sfoglina contiene **il suo nome utente**, cioè metà delle sue credenziali. È la cosa su cui Ennio è stato più netto: *«nessun dato personale di accesso deve andare in rete»*.

### La correzione, in tre pezzi

**1. Un identificatore pubblico separato.** WordPress ne ha già uno — `user_nicename` — che però alla registrazione viene copiato dallo username. Va staccato: costruito dal **nome visibile**, non dallo username.

```php
// In gs_ajax_registrati(), subito dopo wp_insert_user().
// user_nicename è l'identificatore PUBBLICO: finisce negli indirizzi che
// girano su WhatsApp e su Google. WordPress lo copia dallo username, che è
// metà delle credenziali di accesso — qui lo stacchiamo e lo costruiamo dal
// nome visibile (Ennio, 26/08/2026: "nessun dato personale di accesso deve
// andare in rete").
$base = sanitize_title( $nome );
$slug = $base; $n = 2;
while ( get_user_by( 'slug', $slug ) ) { $slug = $base . '-' . $n++; }   // due "Maria Rossi" convivono
wp_update_user( array( 'ID' => $user_id, 'user_nicename' => $slug ) );
```

Il ciclo che aggiunge `-2` non è un dettaglio: **due sfogline con lo stesso nome sono normali**, e senza quello la seconda registrazione fallisce in silenzio.

**2. Una passata una-tantum sui sette account già dentro**, dal pannello, con lo stesso schema: prima mostra cosa cambierebbe, poi applica. Sette righe da leggere.

**3. Cambiare chi legge e chi scrive quel parametro.** `helpers.php:854` passa a `$user->user_nicename`, e **tutti i punti che leggono `?sfoglina=`** vanno cercati e passati da `get_user_by( 'slug', … )` invece di `get_user_by( 'login', … )`.

**Cercateli tutti prima di cambiarne uno.** Se ne resta uno che cerca per login mentre l'indirizzo porta un nicename, quella pagina smette di trovare la sfoglina — e siccome è una pagina pubblica, l'errore si vede da fuori.

### Nota

Questo **non cambia lo username**: la sfoglina continua ad accedere con quello che ha sempre usato. Cambia solo cosa finisce nell'indirizzo pubblico. Vale la pena dirlo nel commento, perché la prossima persona che legge quel codice si chiederà se stiate cambiando le credenziali di qualcuno.

---

## L'ordine, e come si prova

1. **Backup.** Fatto, scaricato, aperto per vedere che non sia vuoto.
2. **Parte 2** (lo username) — prima del reset, perché tocca gli account e conviene farlo su un sito ancora fermo.
3. **La prova a vuoto** del reset. Ennio la legge.
4. **Il reset.**
5. **Aprire i sette account a mano** e guardare: abbonamento, scadenza, token, vetrina. **Questa è la verifica vera**, e oggi si può fare in un quarto d'ora.
6. `prova.sh`.

Poi, e solo poi, tutto il resto: le email di scadenza, gli undici testi, la mail di benvenuto, il giro sul cron, il modulo sponsor, «guarda e commenta».

---

## Una cosa da non fare

**Non cancellate nessun utente.** Ennio aveva detto, tempo fa, di eliminare gli account «oltre i sei» tenendo Rosemma. **Quella è un'operazione diversa da questa e va fatta a mano, guardando i nomi uno per uno.** Un reset che cancella persone è un reset che può cancellare la persona sbagliata, e non c'è backup che renda piacevole quella telefonata.

Il reset azzera il gioco. Le persone le decide Ennio, una alla volta.
