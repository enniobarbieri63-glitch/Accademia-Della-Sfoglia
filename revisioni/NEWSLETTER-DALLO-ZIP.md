# La Newsletter dentro il plugin — come farla senza rompere le altre email

**Per Claude Code Ennio 2 — 26/08/2026, scritto su 3.292.0**

Ennio: *«le newsletter le voglio, se le gestisci tu preferisco dallo zip»*.

Si fa. Ma prima due cose che ho verificato nel codice, e una avvertenza che è la parte più importante del documento.

---

## 1. Metà ce l'ha già — e non è la metà che pensa

`messaggi.php:424`, il pannello «Messaggi privati alle sfogline». Ennio può **già oggi** scrivere un messaggio e mandarlo **a tutte**:

```php
$dest = ( 'tutte' === $dest_raw ) ? 0 : (int) $dest_raw;
…
$dove = ( 0 === $dest ) ? 'a tutte le sfogline' : 'alla sfoglina selezionata';
```

**Ma quel messaggio non esce dal sito.** `gs_invia_messaggio()` (riga 34) scrive **un post solo** con `gs_dest = 0`, e ogni sfoglina lo vede quando entra. Nessuna email parte. È una bacheca, non una newsletter.

Quindi: la scrittura, i destinatari, il cestino, la modifica di un messaggio già mandato — **tutto questo esiste**. Manca solo che parta anche per email.

---

## 2. ⚠️ L'avvertenza, che va letta prima di scrivere una riga

**Mandare tante email in una volta dal server del sito può far smettere di funzionare le email che il gaming usa per vivere.**

Non è un timore generico. In questo progetto è già scritto, nero su bianco, dentro `buono-sfoglia.php`:

> *«se il ciclo muore a metà (troppe sfogline per il tempo massimo di esecuzione, **o il limite orario di posta dell'hosting**)»*

Quel limite esiste, l'avete già incontrato, e ci avete già scritto una protezione intorno.

**Il rischio vero però è peggiore del limite orario.** Se il dominio comincia a mandare posta in blocco dal proprio server, i grandi fornitori (Gmail, Libero, Outlook) lo segnano come sospetto. E quando succede, **non è la newsletter a smettere di arrivare: sono tutte le email.** La mail di benvenuto. Quella del trentesimo giorno. Il recupero della password.

Cioè: **tutto il modello dei trenta giorni sta in piedi su email che devono arrivare.** Una newsletter mandata male le porta giù con sé, e ce ne si accorge settimane dopo, quando una sfoglina scrive «non ho mai ricevuto niente».

### Le tre regole che tolgono il rischio

**a) La posta esce da un servizio vero, non dal server del sito.** È un'impostazione di WordPress (un plugin SMTP e le credenziali di un servizio di posta), **non codice del plugin**. Vale mezz'ora e protegge *tutte* le email del sito, non solo la newsletter. **Questa è la cosa che conta di più di tutto il documento.**

**b) Mai tutte insieme.** L'invio si spalma sul cron giornaliero, a piccoli gruppi. La macchina esiste già ed è provata: `gs_mail_benvenuto_differite()` (`registration.php:222`) fa esattamente questo, con il contrassegno scritto **prima** di mandare, così un cron che riparte non manda due volte.

**c) Una alla settimana, non di più.** Lo ha detto Ennio stesso: *«non mandiamo troppe mail, non tediamo gli iscritti»*. Il pannello deve **impedirlo**, non consigliarlo: se l'ultima è partita da meno di sette giorni, il pulsante è spento e dice quando si riaccende.

---

## 3. La specifica

### Le due cose che si aggiungono al messaggio

Al pannello che c'è già, due caselle in più:

```
[ Oggetto                                          ]
[ Testo …                                          ]

Destinatarie:  ( ) una sfoglina   (•) tutte

☑ Manda anche per email          ← la casella nuova
☐ Solo bacheca (nessuna email)

[Invia]     ⚠️ Ultima newsletter: 3 giorni fa — il prossimo invio da lunedì
```

Quando la casella è spuntata, oltre al post si crea una **coda**:

```php
// La coda, non l'invio. Mandare 200 email in un clic significa perderne la
// maggior parte (il limite orario di posta dell'hosting — lo stesso già
// descritto in buono-sfoglia.php) e, peggio, far segnare il dominio come
// sospetto: a quel punto smettono di arrivare anche la mail di benvenuto e
// quella del trentesimo giorno, cioè le due su cui sta in piedi tutto il
// modello dei trenta giorni.
update_post_meta( $mid, 'gs_newsletter_coda', $elenco_uid );
update_post_meta( $mid, 'gs_newsletter_inviate', array() );
```

### L'invio, sul cron giornaliero

```php
add_action( 'gs_daily_cron', 'gs_newsletter_invia_lotto' );
function gs_newsletter_invia_lotto() {
    // Un lotto al giorno, non di più: con 200 sfogline la newsletter finisce
    // di partire in una decina di giorni. Sembra lento, ed è il punto —
    // l'alternativa non è "più veloce", è "non arriva".
    $per_giro = 20;
    …
    foreach ( $lotto as $uid ) {
        // Contrassegno PRIMA di mandare: un cron che riparte non deve poter
        // mandare due volte la stessa newsletter alla stessa persona.
        $inviate[] = $uid;
        update_post_meta( $mid, 'gs_newsletter_inviate', $inviate );
        gs_mail_progetto( $uid, 'newsletter', $oggetto, $corpo . gs_newsletter_pie() );
    }
}
```

**Passare da `gs_mail_progetto()` non è un dettaglio**: è la porta unica delle 39 email dirette a una persona, e **rispetta già le preferenze di ciascuna**. Chi ha spento le notifiche per email non la riceve, senza che nessuno debba scrivere una riga in più.

La categoria `'newsletter'` va aggiunta all'elenco delle preferenze, così ogni sfoglina può spegnere **solo** quella tenendo accese le comunicazioni importanti.

### Il piede della mail, che non è facoltativo

```php
function gs_newsletter_pie() {
    return "\n\n— — —\nRicevi questa email perché sei iscritta all'Accademia della Sfoglia.\n"
         . "Per non ricevere più le novità: [link] — le comunicazioni importanti\n"
         . "(iscrizione, scadenze, corsi) continuerai a riceverle.\n";
}
```

Il link porta a una pagina che spegne **solo** la categoria `newsletter` di quella sfoglina. Due ragioni: la legge lo richiede, e senza un modo facile per smettere la gente segna la mail come indesiderata — che è esattamente il gesto che porta al problema del punto 2.

### Il pannello deve mostrare come è andata

Sotto ogni newsletter mandata: **quante sono partite, quante mancano, quando finisce**. Senza, Ennio non ha modo di sapere se sta funzionando, e la prima volta che una sfoglina dice «non l'ho ricevuta» non c'è niente da guardare.

---

## 4. Chi non è iscritto al sito

Una persona che passa dal sito e vorrebbe le novità **non ha un account**, quindi non ha un `uid`, quindi non passa da niente di quello che c'è sopra.

**Non costruite anche questo adesso.** È un secondo elenco, con la sua conferma dell'indirizzo, la sua cancellazione, i suoi obblighi — ed è esattamente il mestiere di MailPoet, che sul sito c'è già.

La cosa sensata: **le sfogline dal plugin** (che le conosce, sa le loro preferenze, e non deve chiedere niente), **i visitatori da MailPoet** con il suo modulo su una pagina pubblica. Se un giorno i due elenchi daranno fastidio, si uniranno; oggi tenerli separati costa zero e fa risparmiare una settimana.

---

## 5. Come si prova

1. Newsletter a **una sola** sfoglina di prova → arriva, con il piede e il link per disiscriversi.
2. Cliccare quel link → **non arriva più la newsletter**, ma la mail di scadenza sì.
3. Newsletter a tutte con 3 sfogline di prova e `$per_giro = 2` → il primo giorno ne partono 2, il secondo 1, **e nessuna due volte**.
4. Far girare il cron **due volte nello stesso giorno** → non riparte niente (è il contrassegno).
5. Provare a mandarne una seconda subito → **il pulsante è spento** e dice da quando si riaccende.
6. Una sfoglina **congelata** → riceve la newsletter? **Sì.** È il messaggio che la invita a rientrare: è l'unico caso in cui scrivere a chi è fuori serve davvero. Da verificare esplicitamente, perché il congelamento blocca quasi tutto il resto.
7. `prova.sh`.

---

## Il conto

| | |
|---|---|
| La casella «manda anche per email» e la coda | mezza giornata |
| L'invio a lotti sul cron | mezza giornata |
| Il piede, la pagina di disiscrizione, la categoria nelle preferenze | mezza giornata |
| Il freno dei sette giorni e il resoconto | due ore |
| **L'impostazione SMTP** (fuori dal plugin) | mezz'ora, **e va fatta per prima** |

**Circa due giornate.** Ma se se ne può fare **una cosa sola**, che sia l'impostazione SMTP: protegge le email che ci sono già, che valgono più di quelle che non ci sono ancora.

---

# Le tre decisioni aperte: chiuse

**1. Newsletter** → si fa nel plugin, come sopra.

**2. La vetrina del partner che va offline quando la modifica** → Ennio: *«non c'è problema»*. **Resta com'è**, non si tocca. La chiudo qui: P4 di `LETTURA-2-BLOCCO-3-PARTNER.md` è archiviata, non è più un difetto aperto.

**3. Lo spareggio in caso di pari merito** → si fa. Le tre sotto-domande le chiudo con le mie proposte, che restano scritte in `PARI-MERITO-SPAREGGIO.md`:

- **se lo spareggio finisce ancora pari** → **pari merito vero**, entrambe prime, nessuna seconda. Lo spareggio è già la seconda occasione; una terza gara fra le stesse due persone stanca tutti.
- **se una delle due non si presenta** → **vince chi partecipa**, e va scritto nel messaggio che ricevono: *«se una delle due non partecipa entro il [data], vince l'altra»*. **Detto prima, non dopo.**
- **lo spareggio dà punti suoi** → **sì**, si sommano al premio. Hanno pubblicato una sfoglia in più davvero: è giusto che valga come le altre.

Sono tre scelte di regolamento, non di codice. **Se Ennio ne cambia una, si cambia una riga** — ma vanno decise prima, perché sono i casi che capitano, e se non sono decisi prima li decide chi scrive il codice.
