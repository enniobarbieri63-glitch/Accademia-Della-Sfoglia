# Seconda lettura — blocco 7: premio di fine anno, traguardi, riepilogo, streak

**Per Claude Code Ennio 2 — 26/08/2026 — versione letta: 3.284.4**

Quattro file, letti per intero: `year-prize.php` (105 righe), `traguardi.php` (101), `riepilogo-anno.php` (152), `streak.php` (165). **Con questo la seconda lettura dei file che toccano i punti è finita.**

Un difetto vero, due cose da sapere, e una correzione a me stesso.

---

## Prima di tutto: una cosa che avevo detto male

Guardando `year-prize.php` da solo avevo concluso che il Premio di Fine Anno non avesse **nessuna** protezione: la funzione `gs_year_prize_assigned()` esiste (riga 100) ma dentro `gs_assign_year_prize()` non viene mai chiamata, e i due pannelli lanciano l'assegnazione senza controllarla. Era vero come lettura del file, e sbagliato come conclusione — perché la protezione c'è, solo non è lì.

Sono andato a leggere i due pannelli e il JavaScript. **Ci sono tre freni, tutti e tre a posto:**

- `control-panel.php:406` e `admin.php:938` — se il premio risulta già assegnato, scrivono in chiaro: *«Premio già assegnato il [data]. **Riassegnando invierai di nuovo le email.**»*
- `admin.php:940` — `onclick="return confirm('Assegnare il premio e inviare le email alle vincitrici?')"`
- `gaming.js:406-411` — lo stesso `confirm()` sul pannello davanti, **più `$btn.prop( 'disabled', true )`** prima della chiamata.

Quindi non è la chiusura di luglio: là un cron partiva da solo e nessuno sapeva niente. Qui c'è una persona che clicca, un avviso che dice esattamente cosa succederà, e una domanda di conferma. **Riassegnare è una scelta prevista, non un incidente** — e ha senso che lo sia, se un'email è tornata indietro o un nome era sbagliato.

Lo scrivo perché avevo già detto a Ennio che era grave. Non lo è. Quello che resta sotto è più piccolo, ma è reale.

---

## Y1 — Nessuno sa chi ha vinto

**`year-prize.php:52-95`.** Questo è il difetto vero del blocco, e non è il doppio invio: è che **il premio non lascia traccia di chi l'ha vinto.**

Alla fine dell'assegnazione (riga 95):

```php
update_option( 'gs_year_prize_assigned_' . $year, current_time( 'mysql' ) );
```

Una data. Solo una data. I nomi delle vincitrici vengono restituiti alla pagina che ha chiamato, mostrati una volta in un messaggio verde, e **persi**.

Il che vuol dire che l'elenco delle vincitrici non esiste da nessuna parte: esiste solo `gs_year_leaderboard( $year, $n )`, che **ricalcola la classifica adesso**, ogni volta che qualcuno apre il pannello. E quella classifica si muove: i punti dell'anno continuano ad arrivare fino al 13 dicembre, Ennio può correggere i punti di una sfoglina dal pannello apposta, un account può essere cancellato.

Due conseguenze concrete:

**1. Una riassegnazione può premiare persone diverse.** Ennio riassegna a gennaio per rimandare un'email tornata indietro. Nel frattempo la classifica si è mossa di una posizione. La seconda passata scrive il badge e manda l'email a una sfoglina che la prima volta era quinta e adesso è quarta — e a quel punto **i badge assegnati sono cinque per quattro posti**, senza che nessuno se ne accorga. Il badge della prima non si toglie: `gs_unlock_badge` e il codice qui non tolgono mai niente, giustamente.

**2. Fra sei mesi non si può più rispondere a «chi ha vinto nel 2026?»** se non riaprendo il pannello e fidandosi di una classifica che nel frattempo è cambiata. Il Premio di Fine Anno è un corso vero con Rina Poletti: è la cosa più importante che l'Accademia dà in un anno, e non c'è un registro.

**La correzione è piccola e va fatta**, perché il primo anno di gioco vero si chiude il 13 dicembre:

```php
// Non solo la data: anche CHI. Senza i nomi, l'unico elenco delle vincitrici
// è gs_year_leaderboard(), che ricalcola la classifica adesso — e la
// classifica si muove (punti fino al 13 dicembre, correzioni dal pannello,
// account cancellati). Con i nomi qui dentro, una riassegnazione riguarda
// le stesse persone della prima volta, e fra un anno si sa ancora chi ha
// vinto.
update_option( 'gs_year_prize_assigned_' . $year, array(
	'data'       => current_time( 'mysql' ),
	'vincitrici' => $ids,          // gli ID, non i nomi: i nomi cambiano
) );
```

**Attenzione a `gs_year_prize_assigned()`** (riga 100): oggi fa `(bool) get_option(...)` e i due pannelli ne stampano il valore come una data (`'Premio già assegnato il ' . $assegnato`). Se ci metti dentro un array, quella riga stampa `Array`. Vanno sistemate insieme tutte e tre — la funzione, `control-panel.php:406`, `admin.php:938` — e va gestito il caso di un'opzione vecchia che è ancora una stringa.

E già che ci sei: quando il premio è già assegnato, **usa l'elenco salvato** invece di ricalcolare. Così riassegnare vuol dire davvero «rimanda le stesse email alle stesse persone», che è quello che l'avviso promette.

---

## Y2 — Il badge è protetto, l'annuncio no

**`year-prize.php:60-70`.** È il settimo badge scritto a mano, e ha la forma che ormai conosciamo:

```php
if ( ! in_array( $badge_key, $badges, true ) ) {
	$badges[] = $badge_key;
	update_user_meta( $user->ID, 'gs_badges', $badges );
	update_user_meta( $user->ID, 'gs_badge_label_' . $badge_key, '🎓 Corso con Rina Poletti ' . $year );
}

// ↓ FUORI dal blocco: email + Aeroplanino
```

È la stessa forma di G1 e M1: il controllo protegge **solo quello che sta dentro le graffe**, e l'annuncio sta fuori. **Qui però la conseguenza è voluta** — l'avviso del pannello dice esattamente *«Riassegnando invierai di nuovo le email»*, ed è il motivo per cui si riassegna.

Quindi non c'è niente da correggere. Lo scrivo solo per chiudere il conto: dei sette posti che sbloccano un badge a mano invece di passare da `gs_unlock_badge()`, ora li abbiamo guardati tutti. **Due erano rotti (G1, M1), uno è a posto per scelta (questo), e uno è fatto bene** — vedi `streak.php` qui sotto.

Resta valida la nota di fondo: `gs_unlock_badge()` non accetta chiavi costruite al volo (`corso_rina_2026`, `streak_scudo_8`, `percorso_...`), ed è per questo che sette posti se la riscrivono. Il giorno che qualcuno le fa accettare le chiavi dinamiche, questi sette diventano una riga sola ciascuno.

---

## R1 — «Il Tuo Anno» conta i punti da una parte e la classifica dall'altra

**`riepilogo-anno.php:26` contro `riepilogo-anno.php:63`. Questo è il difetto più netto del blocco.**

In `points.php` ci sono **due contatori annuali diversi**, e li scrive tutti e due `gs_add_points()`:

| Meta | Cosa conta | Chi lo legge |
|---|---|---|
| `gs_points_{ANNO}` | l'anno solare intero, 1 gen → 31 dic | `gs_get_year_points()` |
| `gs_points_anno_{ANNO}` | **l'anno di gioco**, 1 gen → 13 dic (`points.php:92`) | `gs_get_anno_gioco_points()` |

Il secondo è nato il 19-20/08 perché Ennio ha deciso che *«il gioco si chiude il 13 dicembre»*. Il commento a `points.php:88` dice che il primo *«continua a esistere per compatibilità ma non è più letto da year-prize.php»*.

Ed è vero che `year-prize.php` non lo legge più. Ma qualcun altro sì:

```php
riepilogo-anno.php:26   $punti_anno = gs_get_year_points( $user_id, $year );        // ← anno SOLARE
riepilogo-anno.php:63   foreach ( gs_year_leaderboard( $year, 1000 ) as $i => $riga )  // ← anno di GIOCO
```

**La stessa pagina mostra il punteggio preso da un contatore e la posizione in classifica calcolata sull'altro.**

Dal 14 al 31 dicembre i due numeri si separano davvero: i punti continuano ad arrivare su `gs_points_{ANNO}` e non più su `gs_points_anno_{ANNO}`. Quindi in quelle due settimane una sfoglina apre «Il Tuo Anno in Accademia» e vede **il suo totale che sale mentre la posizione non si muove** — e nessuno le ha mai spiegato che ci sono due conteggi. Non c'è nessun modo, dalla pagina, di capire perché.

C'è di peggio: **il numero che la sfoglina vede come «i miei punti dell'anno» non è quello che decide il premio.** Il premio si vince sull'altro.

E la prova che è uno scambio e non una scelta: **`gs_get_anno_gioco_points()` non è chiamata da nessuna parte.** L'ho cercata in tutto il plugin. La funzione scritta apposta per l'anno di gioco è codice morto, mentre le due pagine che dovrebbero usarla usano l'altra. `gs_year_leaderboard()` non la chiama nemmeno lei: legge la chiave meta a mano (`year-prize.php:22`).

**La correzione**, in `riepilogo-anno.php`:

```php
// L'anno di gioco (1 gennaio → 13 dicembre), lo stesso su cui si vince il
// Premio di Fine Anno e su cui gs_year_leaderboard() calcola la posizione
// mostrata dieci righe più sotto. Prima qui c'era gs_get_year_points(), che
// conta l'anno solare intero: la pagina mostrava un punteggio preso da un
// contatore e una posizione presa dall'altro, e dal 14 dicembre i due
// numeri si separavano sotto gli occhi della sfoglina.
$punti_anno      = gs_get_anno_gioco_points( $user_id, $year );
$punti_anno_prec = gs_get_anno_gioco_points( $user_id, (string) ( (int) $year - 1 ) );
```

E, per non lasciare in giro la stessa trappola, **fai leggere anche a `gs_year_leaderboard()` la sua chiave attraverso `gs_get_anno_gioco_points()`** invece che a mano: così esiste un solo posto al mondo che sa come si chiama quel meta.

**Da guardare, non da correggere a occhio:** `export-dati.php:44` esporta `gs_get_year_points()` in una colonna chiamata «punti dell'anno». Se quell'export serve a Ennio per controllare la classifica, ha lo stesso problema; se serve per la contabilità dell'anno solare, è giusto così. **Non lo so, e non lo cambio senza sapere** — chiedilo a Ennio e riferisci.

---

## ST1 — La streak si aggiorna leggendo e riscrivendo

**`streak.php:16-40`**, `gs_update_streak()`: legge `gs_streak_last_week`, controlla, calcola, riscrive, e dà 10 punti. La forma è quella che ho trovato dieci volte in questa lettura.

Perché scatti servono **due sfoglie pubblicate nello stesso secondo dalla stessa sfoglina**: raro davvero, molto più raro di un doppio clic su un pulsante. Il danno è 10 punti.

La segnalo per completezza e la metto in fondo alla lista, non in cima. Se un giorno metti il lucchetto sulle missioni (MI1 del blocco 6), mettilo anche qui: è la stessa riga, lo stesso `wp_cache_delete( $user_id, 'user_meta' )`, e sono cinque minuti in più.

---

## Cose che ho guardato e vanno bene

**`gs_streak_scudo_controlla_traguardo()` (`streak.php:62-84`) — questo è fatto bene, ed è il contrario di G1.**

```php
$owned = gs_get_user_badges( $uid );
if ( in_array( $badge_key, $owned, true ) ) { return false; }   // ← ritorno anticipato
$owned[] = $badge_key;
update_user_meta( $uid, 'gs_badges', $owned );
...
update_user_meta( $uid, 'gs_streak_scudi', gs_streak_scudi( $uid ) + 1 );
gs_accoda_volo( $uid, 'HAI GUADAGNATO UNO SCUDO SALVA-STREAK! 🛡️ …' );
```

Il ritorno anticipato invece del blocco `if`: **tutto quello che viene dopo è protetto per costruzione**, compreso l'incremento dello scudo e l'annuncio. Chi lo ha scritto non ha dovuto ricordarsi di mettere niente dentro le graffe, perché non ci sono graffe da cui restare fuori.

È la differenza esatta fra i due modi di scrivere la stessa cosa, e vale la pena vederla accanto a G1, M1 e Y2 qui sopra: **la forma a ritorno anticipato non ha mai prodotto un difetto in questo plugin, la forma a blocco `if` ne ha prodotti due.**

**`gs_is_previous_week()` (`streak.php:98-118`)** — gestisce il cambio d'anno ISO (`$cy - $ly === 1 && 1 === $cw && $lw >= 52`), che è il posto dove queste funzioni sbagliano di solito. Corretta, e regge sia gli anni da 52 settimane sia quelli da 53.

**`traguardi.php`** — solo lettura, nessuna scrittura, nessun punto. Legge i badge dinamici via `gs_badge_label_*` con il fallback giusto (riga 60), quindi i badge scritti a mano dei sette posti ci finiscono comunque. Pagina pubblica senza login e senza cancello del blackout: **è scritto nell'intestazione del file che è voluto**, e va bene così.

Una nota che non è un difetto: `gs_traguardi_recenti()` scorre **tutte** le sfogline e legge un meta per ciascuna, a ogni visita, su una pagina pubblica senza cache. Con quaranta sfogline non si sente. Con quattrocento sì. Non c'è niente da fare adesso — mettilo nella lista delle cose da guardare quando le sfogline saranno tante, insieme a `riepilogo-anno.php:63`, che per calcolare una posizione carica la classifica delle prime mille.

---

## La seconda lettura è finita

Tutti i file che toccano punti, badge, token o euro sono stati letti riga per riga. Il conto dei difetti trovati in questa seconda lettura, per come si comportano:

| Famiglia | Quanti | Dove |
|---|---|---|
| Un'operazione che tocca un valore accumulato, senza niente che impedisca di eseguirla due volte | **13** | C1, T1, T2, A2, B5, P1, L1, L2, V1, G1, M1, M2, C6 |
| Legge-modifica-riscrive su un dato condiviso: scritture che si perdono | **5** | S1, S2, PE1, MI1, ST1 |
| Date mescolate fra fusi orari diversi | **3** | P3, L4, V3 |
| Controlli d'accesso troppo deboli | **1**, in 31 punti | L |
| Numeri che non tornano fra due pagine | **1** | R1 |

**Una famiglia sola fa più della metà del lavoro.** Non è mai stata la stessa svista ripetuta: è sempre stato lo stesso modo di scrivere, ripetuto da mani diverse in momenti diversi — controlla, poi fai. Fra il controllo e il fare c'è sempre una porta aperta.

Le due forme che chiudono quella porta esistono già nel plugin, provate sul serio, e sono due sole:

- **`GET_LOCK` / `RELEASE_LOCK`** con `try/finally` — `calendario.php:571`, nato da un test di carico vero («un corso con 3 posti ne ha accettate 4»);
- **`add_post_meta( ..., $unique = true )` che ritorna `false`** — `gs_ajax_vota()`, il punto meglio protetto del plugin, dove il controllo e la scrittura sono la stessa operazione e quindi non c'è finestra.

Più la regola che viene da luglio, che non è codice ma ordine: **il contrassegno si scrive prima degli effetti, sempre.**

---

## Cosa resta da leggere

Non i punti: quelli sono finiti. Restano **i giri 3-6**, che non ho mai cominciato e che servono all'apertura del gaming, **non a settembre**:

- il doppio clic che brucia due livelli di corso (A2 del giro 3);
- il tetto giornaliero ai punti (F1) — quello che ho segnalato nel blocco 6 sul diario a 15 punti l'uno, senza limite;
- una dozzina di voci minori: E4, A5, E5, B1-B6, E7, F2, F3, D1-D3.

**Prima di tutto questo viene il documento dei trenta giorni**, che ha la scadenza vera addosso. Questo blocco e il blocco 6 si possono fare quando c'è tempo, tranne la voce **L** del blocco 6, che va fatta insieme al cancello perché è la stessa mano di lavoro.
